<?php

namespace App\Models;

use DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;


/**
 * 倉庫マスタ
 */
class Warehouse extends Model
{

    // テーブル名
    protected $table = 'm_warehouse';
    public $timestamps  = false;

    /**
     * 一覧取得
     * @param $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {

        // TODO: 検索条件
        $where = [];
        $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.owner_kbn', '=', config('const.ownerKbn.company'));
        if (isset($params['id']) && $params['id'] !== '') {
            $where[] = array('m_warehouse.id', '=', $params['id']);
        }
        if (isset($params['warehouse_name']) && $params['warehouse_name'] !== '') {
            $param = $params['warehouse_name'];
            $where[] = array('warehouse_name', 'LIKE', "%$param%");
        }
        if (isset($params['select_base_id']) && $params['select_base_id'] !== '') {
            $where[] = array('owner_id', '=', $params['select_base_id']);
        }
        if (array_key_exists('truck_flg', $params) && $params['truck_flg'] !== null) {
            $where[] = array('truck_flg', '=', $params['truck_flg']);
        }

        // データ取得
        $data = $this
            ->leftJoin('m_base', 'm_base.id', '=', 'm_warehouse.owner_id')
            ->select('m_warehouse.*', 'm_base.base_name')
            ->where($where)
            ->orderBy('m_warehouse.id', 'asc')
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
            ->leftjoin('m_staff', 'm_staff.id', '=', 'm_warehouse.update_user')
            ->select('m_warehouse.*', 'm_staff.staff_name')
            ->where(['m_warehouse.id' => $id])
            ->first();

        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params, $ownerId, $ownerKbn)
    {
        $result = false;

        if (!empty($params['truck_flg'])) {
            $truckFlg = $params['truck_flg'];
        } else {
            $truckFlg = config('const.truckFlg.warehouse');
        }

        try {
            $result = $this->insert([
                'warehouse_name' => $params['warehouse_name'],
                'warehouse_short_name' => $params['warehouse_short_name'],
                'owner_id' => $ownerId,
                'owner_kbn' => $ownerKbn,
                'zipcode' => $params['zipcode'],
                'address1' => $params['address1'],
                'address2' => $params['address2'],
                'latitude_jp' => $params['latitude_jp'],
                'longitude_jp' => $params['longitude_jp'],
                'latitude_world' => $params['latitude_world'],
                'longitude_world' => $params['longitude_world'],
                // 'truck_flg' => $truckFlg,
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
    public function updateById($params, $owner_id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if (!empty($params['base_id'])) {
                $owner_id = $params['base_id'];
            }

            if (!empty($params['truck_flg'])) {
                $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'warehouse_name' => $params['warehouse_name'],
                        'warehouse_short_name' => $params['warehouse_short_name'],
                        'owner_id' => $owner_id,
                        'zipcode' => $params['zipcode'],
                        'address1' => $params['address1'],
                        'address2' => $params['address2'],
                        'latitude_jp' => $params['latitude_jp'],
                        'longitude_jp' => $params['longitude_jp'],
                        'latitude_world' => $params['latitude_world'],
                        'longitude_world' => $params['longitude_world'],
                        'truck_flg' => $params['truck_flg'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            } else {
                $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'warehouse_name' => $params['warehouse_name'],
                        'warehouse_short_name' => $params['warehouse_short_name'],
                        'owner_id' => $owner_id,
                        'zipcode' => $params['zipcode'],
                        'address1' => $params['address1'],
                        'address2' => $params['address2'],
                        'latitude_jp' => $params['latitude_jp'],
                        'longitude_jp' => $params['longitude_jp'],
                        'latitude_world' => $params['latitude_world'],
                        'longitude_world' => $params['longitude_world'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            }
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param int $id 拠点ID
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
     * コンボボックス用データ取得
     *
     * @return void
     */
    public function getComboList()
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('truck_flg', '=', config('const.flg.off'));
        $where[] = array('owner_kbn', '=', config('const.ownerKbn.company'));

        $data = $this
            ->where($where)
            ->orderBy('id')
            ->get();

        return $data;
    }

    /**
     * 返品棚を持つ倉庫取得
     *
     * @return void
     */
    public function getExistReturnShelfWarehouse()
    {
        $where = [];
        // $where[] = array('shelf.shelf_kind', '=', config('const.shelfKind.return'));
        $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        $where[] = array('shelf.shelf_kind', '=', config('const.shelfKind.return'));

        $data = $this
            ->leftJoin('m_shelf_number as shelf', 'shelf.warehouse_id', '=', 'm_warehouse.id')
            ->where($where)
            ->orderBy('m_warehouse.id')
            ->selectRaw('
                        m_warehouse.id,
                        m_warehouse.warehouse_name
                    ')
            ->groupBy('m_warehouse.id', 'm_warehouse.warehouse_name')
            ->get();

        return $data;
    }

    /**
     * 入荷先用コンボボックス取得
     *
     * @param [type] $supplierId
     * @param [type] $matterAddressId（値が無い場合、直送の選択肢が無くなる）
     * @return void
     */
    public function getDeliveryAddressComboList($supplierId, $matterAddressId)
    {
        $supplier = $this
            ->where([
                ['del_flg', config('const.flg.off')],
                ['owner_kbn', config('const.ownerKbn.supplier')],
                ['truck_flg', config('const.truckFlg.warehouse')],
                ['owner_id', $supplierId],
            ])
            ->select([
                DB::raw('CONCAT(' . config('const.deliveryAddressKbn.val.supplier') . ', \'_\', id) as unique_key'),
                'id',
                'warehouse_name',
                DB::raw('CONCAT(COALESCE(address1, \'\'), COALESCE(address2, \'\')) as address'),
                DB::raw(config('const.deliveryAddressKbn.val.supplier') . ' as delivery_address_kbn'),
                DB::raw('3 as display_order')
            ]);

        // メインクエリ(会社倉庫), UNION(仕入先,直送)
        $data = $this
            ->where([
                ['del_flg', '=', config('const.flg.off')],
                ['owner_kbn', '=', config('const.ownerKbn.company')],
                ['truck_flg', '=', config('const.truckFlg.warehouse')],
            ])
            ->select([
                DB::raw('CONCAT(' . config('const.deliveryAddressKbn.val.company') . ', \'_\', id) as unique_key'),
                'id',
                'warehouse_name',
                DB::raw('CONCAT(COALESCE(address1, \'\'), COALESCE(address2, \'\')) as address'),
                DB::raw(config('const.deliveryAddressKbn.val.company') . ' as delivery_address_kbn'),
                DB::raw('1 as display_order'),
            ])
            // 仕入先倉庫
            ->union($supplier)
            // 直送
            ->when($matterAddressId, function ($query) use ($matterAddressId) {
                return $query->union(
                    DB::table('m_address AS a')
                        ->Join('t_matter AS m', function ($join) use ($matterAddressId) {
                            $join
                                ->on('a.id', 'm.address_id')
                                ->where('m.address_id', $matterAddressId);
                        })
                        ->select([
                            DB::raw('CONCAT(' . config('const.deliveryAddressKbn.val.site') . ', \'_\', a.id) as unique_key'),
                            'a.id',
                            DB::raw('\'' . config('const.directDeliveryAddressName') . '\' AS warehouse_name'),
                            DB::raw('CONCAT(COALESCE(a.address1, \'\'), COALESCE(a.address2, \'\')) as address'),
                            DB::raw(config('const.deliveryAddressKbn.val.site') . ' as delivery_address_kbn'),
                            DB::raw('2 as display_order'),
                        ])
                );
            })
            ->orderBy('display_order')
            ->get();
        return $data;
    }

    /**
     * owner_idで取得
     * @param int $id 所有者ID
     * @return type 検索結果データ
     */
    public function getByOwnerId($ownerId, $kbn)
    {
        $where = [];
        $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.owner_id', '=', $ownerId);
        $where[] = array('m_warehouse.owner_kbn', '=', $kbn);

        $data = $this
            ->leftjoin('m_staff', 'm_staff.id', '=', 'm_warehouse.update_user')
            ->select('m_warehouse.*', 'm_staff.staff_name')
            ->where($where)
            ->orderBy('m_warehouse.id')
            ->get();

        return $data;
    }

    /**
     * 現在値から一番近い倉庫を取得
     * @param  $latitude 緯度
     * @param  $longitude 経度
     * @return type 検索結果データ
     */
    public function getByLocation($latitude, $longitude)
    {
        $where = [];
        $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_warehouse.truck_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('m_warehouse')
            ->selectRaw('id')
            ->orderByRaw("sqrt(power((latitude_jp - ${latitude}) * 111000, 2) + power((longitude_jp - ${longitude}) * 91000, 2)) LIMIT 1")
            ->get();

        return $data;
    }

    /**
     * 現在値から近い順にログイン者の該当倉庫を取得
     * @param  $latitude 緯度
     * @param  $longitude 経度
     * @return type 検索結果データ
     */
    public function getOrderByLocation($latitude, $longitude)
    {

        $data = DB::select("
                select distinct *
                from
                (
                select
                    w.id,w.warehouse_name
                from
                    m_staff_department as sd
                left join
                    m_department as d on d.id = sd.department_id
                left join
                    m_warehouse as w on w.owner_id = d.base_id
                where
                    w.del_flg = 0 and w.truck_flg = 0 and sd.staff_id = " . Auth::user()->id . "
                order by 
                    sqrt(power((w.latitude_jp - ${latitude}) * 111000, 2) + power((w.longitude_jp - ${longitude}) * 91000, 2))
                ) as t");

        for ($i=0; $i < count($data); $i++) { 
            $data[$i] = json_decode(json_encode($data[$i]), true);
        }

        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @param  $truckFlg config('const.truckFlg')
     * @return void
     */
    public function getCompanyComboList()
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('owner_kbn', '=', config('const.ownerKbn.company'));

        $data = $this
            ->where($where)
            ->orderBy('id')
            ->get();

        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @return void
     */
    public function getAllWarehouseComboList()
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('truck_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->orderBy('id')
            ->get();

        return $data;
    }
}
