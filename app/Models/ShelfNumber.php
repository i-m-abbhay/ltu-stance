<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 棚番マスタ
 */
class ShelfNumber extends Model
{

    // テーブル名
    protected $table = 'm_shelf_number';
    public $timestamps  = false;

    /**
     * 一覧取得
     * @param type $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {

        // TODO: 検索条件
        $where = [];
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        if (isset($params['warehouse_id']) && $params['warehouse_id'] !== '') {
            $where[] = array('m_shelf_number.warehouse_id', '=', $params['warehouse_id']);
        }
        if (isset($params['shelf_kind']) && $params['shelf_kind'] !== '') {
            $where[] = array('m_shelf_number.shelf_kind', '=', $params['shelf_kind']);
        }
        if (isset($params['shelf_area']) && $params['shelf_area'] !== '') {
            $where[] = array('m_shelf_number.shelf_area', '=', $params['shelf_area']);
        }
        // データ取得
        $data = $this
            ->select('m_shelf_number.*')
            ->where($where)
            ->orderBy('m_shelf_number.id', 'asc')
            ->get();

        return $data;
    }

    /**
     * IDで取得
     * @param int $id 倉庫ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id)
    {
        // TODO: 更新者名取得
        $data = $this
            ->select('m_shelf_number.*')
            ->where(['m_shelf_number.id' => $id])
            ->first();

        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params)
    {
        $result = false;
        try {
            $result = $this->insert([
                'warehouse_id' => $params['warehouse_id'],
                'shelf_id' => $params['shelf_id'],
                'shelf_area' => $params['shelf_area'],
                'shelf_steps' => $params['shelf_steps'],
                'shelf_kind' => $params['shelf_kind'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'warehouse_id' => $params['warehouse_id'],
                    'shelf_id' => $params['shelf_id'],
                    'shelf_area' => $params['shelf_area'],
                    'shelf_steps' => $params['shelf_steps'],
                    'shelf_kind' => $params['shelf_kind'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param int $id 棚番ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteById($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * 論理削除
     * @param int $id 倉庫ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteByWarehouseId($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('warehouse_id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * コンボボックス用データ取得
     *
     * @return 
     */
    public function getComboList()
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('id', 'asc')
            ->select([
                'id',
                'warehouse_id',
                'shelf_id',
                'shelf_area',
                'shelf_steps',
                'shelf_kind'
            ])
            ->get();

        return $data;
    }

    /**
     * 倉庫IDから取得
     *
     * @param  $id　倉庫ID
     * @return void
     */
    public function getByWarehouseId($id)
    {
        $where = [];
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_shelf_number.warehouse_id', '=', $id);

        $data = $this
            ->where($where)
            ->leftJoin('m_warehouse', 'm_warehouse.id', '=', 'm_shelf_number.warehouse_id')
            ->leftJoin('m_base', function ($join) {
                $join
                    ->on('m_base.id', '=', 'm_warehouse.owner_id')
                    ->where('m_warehouse.owner_kbn', '=', config('const.ownerKbn.company'));
            })
            ->leftJoin('m_staff', 'm_staff.id', '=', 'm_shelf_number.update_user')
            ->leftJoin('t_productstock_shelf as stock_shelf', 'stock_shelf.shelf_number_id', '=', 'm_shelf_number.id')
            ->selectRaw('
                    m_shelf_number.id AS id,
                    m_shelf_number.shelf_id AS shelf_id,
                    m_shelf_number.shelf_area,
                    m_shelf_number.shelf_steps,
                    m_shelf_number.shelf_kind,
                    m_shelf_number.del_flg,
                    m_warehouse.id AS warehouse_id,
                    m_warehouse.warehouse_name,
                    m_warehouse.warehouse_short_name,
                    m_base.id AS base_id,
                    m_base.base_name,
                    m_shelf_number.update_at,
                    m_staff.staff_name AS update_user_name,
                    CASE
                        WHEN stock_shelf.id IS NULL
                            THEN 0
                        ELSE 1
                    END AS used_flg
                ')
            ->distinct()
            ->orderBy('m_shelf_number.id')
            ->get();


        return $data;
    }

    /**
     * 倉庫IDから返品棚取得
     *
     * @param  $id　倉庫ID
     * @param  $shelfKind 棚種別
     * @return void 
     */
    public function getByWarehouseIdAndShelfKind($id, $shelfKind)
    {
        $where = [];
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_shelf_number.warehouse_id', '=', $id);
        $where[] = array('m_shelf_number.shelf_kind', '=', $shelfKind);

        $data = $this
            ->where($where)
            ->leftJoin('m_warehouse', 'm_warehouse.id', '=', 'm_shelf_number.warehouse_id')
            ->leftJoin('m_staff', 'm_staff.id', '=', 'm_shelf_number.update_user')
            ->leftJoin('t_productstock_shelf as stock_shelf', 'stock_shelf.shelf_number_id', '=', 'm_shelf_number.id')
            ->selectRaw('
                    m_shelf_number.id AS id,
                    m_shelf_number.shelf_id AS shelf_id,
                    m_shelf_number.shelf_area,
                    m_shelf_number.shelf_steps,
                    m_shelf_number.shelf_kind,
                    m_shelf_number.del_flg,
                    m_warehouse.id AS warehouse_id,
                    m_warehouse.warehouse_name,
                    m_warehouse.warehouse_short_name
                ')
            ->distinct()
            ->orderBy('m_shelf_number.id')
            ->get();


        return $data;
    }

    /**
     * 拠点に返品棚があるか返す
     *
     * @param  $baseId 拠点ID
     * @return boolean true: 返品棚有 false: 返品棚無
     */
    public function isExistReturnShelf($baseId)
    {
        $where = [];
        $where[] = array('m_base.id', '=', $baseId);
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_shelf_number.shelf_kind', '=', config('const.shelfKind.return'));

        $cnt = $this
            ->leftJoin('m_warehouse', 'm_warehouse.id', '=', 'm_shelf_number.warehouse_id')
            ->leftJoin('m_base', function ($join) {
                $join
                    ->on('m_base.id', '=', 'm_warehouse.owner_id')
                    ->where('m_warehouse.owner_kbn', '=', config('const.ownerKbn.company'));
            })
            ->where($where)
            ->count();

        return $cnt > 0;
    }

    /**
     * IDから取得
     *
     * @param  $id　ID
     * @return void
     */
    public function getWarehouseById($id)
    {
        $where = [];
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_shelf_number.id', '=', $id);

        $data = $this
            ->where($where)
            ->leftJoin('m_warehouse', 'm_warehouse.id', '=', 'm_shelf_number.warehouse_id')
            ->selectRaw('
                    m_shelf_number.id AS id,
                    m_shelf_number.shelf_id AS shelf_id,
                    m_shelf_number.shelf_area,
                    m_shelf_number.shelf_steps,
                    m_shelf_number.shelf_kind,
                    m_shelf_number.del_flg,
                    m_warehouse.id AS warehouse_id,
                    m_warehouse.warehouse_name,
                    m_warehouse.warehouse_short_name
                ')
            ->first();

        return $data;
    }

    /**
     * トラック一覧を取得
     *
     * @return void
     */
    public function getTruckList()
    {
        $where = [];
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.truck_flg', '=', config('const.flg.on'));

        $data = $this
            ->where($where)
            ->Join('m_warehouse', function ($join) {
                $join
                    ->on('m_warehouse.id', '=', 'm_shelf_number.warehouse_id');
            })

            ->selectRaw('
                    m_shelf_number.id AS id,
                    m_shelf_number.shelf_id AS shelf_id,
                    m_shelf_number.shelf_area,
                    m_shelf_number.shelf_steps,
                    m_shelf_number.shelf_kind,
                    m_warehouse.id AS warehouse_id,
                    m_warehouse.warehouse_name,
                    m_warehouse.warehouse_short_name 
                ')
            ->orderBy('m_shelf_number.id')
            ->get();

        return $data;
    }


    /**
     * CSV一覧を取得
     *
     * @return void
     */
    public function getCsvData($warehouseId)
    {
        $where = [];
        $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.id', '=', $warehouseId);

        $data = $this
            ->where($where)
            ->leftJoin('m_warehouse', 'm_warehouse.id', '=', 'm_shelf_number.warehouse_id')
            ->leftJoin('m_base', function($join) {
                $join
                    ->on('m_base.id', '=', 'm_warehouse.owner_id')
                    ->where('m_warehouse.owner_kbn', '=', config('const.ownerKbn.company'))
                    ;
            })
            ->selectRaw('
                    m_shelf_number.id AS id,
                    m_base.base_name,
                    m_warehouse.warehouse_name,
                    m_warehouse.warehouse_short_name,
                    m_shelf_number.shelf_area,
                    m_shelf_number.shelf_steps,
                    m_shelf_number.shelf_kind,
                    CASE
                        WHEN m_shelf_number.shelf_kind ='.config('const.shelfKind.normal').'
                            THEN \'通常\'
                        WHEN m_shelf_number.shelf_kind ='.config('const.shelfKind.temporary').'
                            THEN \'入荷一時置場\'
                        WHEN m_shelf_number.shelf_kind ='.config('const.shelfKind.keep').'
                            THEN \'預かり\'
                        WHEN m_shelf_number.shelf_kind ='.config('const.shelfKind.return').'
                            THEN \'返品\'
                        ELSE \'\'
                    END AS shelf_kind
                ')
            ->orderBy('m_shelf_number.id')
            ->get();

        return $data;
    }

}
