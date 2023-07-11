<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * QRコード管理詳細
 */
class QrDetail extends Model
{
    // テーブル名
    protected $table = 't_qr_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'qr_id',
        'product_id',
        'matter_id',
        'customer_id',
        'arrival_id',
        'quantity',
        'warehouse_id',
        'shelf_number_id',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
    ];

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_qr_detail.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('t_qr_detail.id', '=', $params['id']);
        }
        if (!empty($params['qr_id'])) {
            $where[] = array('t_qr_detail.qr_id', '=', $params['qr_id']);
        }
        if (!empty($params['product_id'])) {
            $where[] = array('t_qr_detail.product_id', '=', $params['product_id']);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('t_qr_detail.id', 'asc')
            ->select(
                't_qr_detail.*'
            )
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'qr_id' => $params['qr_id'],
                'product_id' => $params['product_id'],
                'matter_id' => $params['matter_id'],
                'customer_id' => $params['customer_id'],
                'arrival_id' => $params['arrival_id'],
                'quantity' => $params['quantity'],
                'warehouse_id' => $params['warehouse_id'],
                'shelf_number_id' => $params['shelf_number_id'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
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

            $items = [];
            if (!empty($params['qr_id'])) {
                $items['qr_id'] = $params['qr_id'];
            }
            if (isset($params['product_id'])) {
                $items['product_id'] = $params['product_id'];
            }
            if (isset($params['matter_id'])) {
                $items['matter_id'] = $params['matter_id'];
            }
            if (isset($params['customer_id'])) {
                $items['customer_id'] = $params['customer_id'];
            }
            if (isset($params['arrival_id'])) {
                $items['arrival_id'] = $params['arrival_id'];
            }
            if (isset($params['quantity'])) {
                $items['quantity'] = $params['quantity'];
            }
            if (isset($params['warehouse_id'])) {
                $items['warehouse_id'] = $params['warehouse_id'];
            }
            if (isset($params['shelf_number_id'])) {
                $items['shelf_number_id'] = $params['shelf_number_id'];
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where('id', $params['id'])
                ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param $id
     * @return void
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
     * @param $id
     * @return void
     */
    public function deleteByQrId($qrId)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $qrId, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('qr_id', $qrId)
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
     * 物理削除
     * @param $id 
     * @return void
     */
    public function deleteByDetailId($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $result = $this
                ->where('id', $id)
                ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param $id 
     * @return void
     */
    public function deleteByDetailQrId($QrId)
    {
        $result = false;
        try {
            $where = [];
            $where[] = array('qr_id', '=', $QrId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            $result = $this
                ->where($where)
                ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 一覧取得(在庫照会)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getStockList($productId = null, $warehouseId = null, $qrCode = null, $params)
    {
        // Where句作成
        $where = [];
        $where[] = array('qr_detail.del_flg', '=', config('const.flg.off'));
        $joinWhere = [];
        $joinWhere['matter'][] = array('matter.del_flg', '=', config('const.flg.off'));
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));

        if (!empty($productId)) {
            $where[] = array('qr_detail.product_id', '=', $productId);
        }
        if (!empty($productId)) {
            $where[] = array('qr_detail.warehouse_id', '=', $warehouseId);
        }
        if (!empty($qrCode)) {
            $where[] = array('qr.qr_code', '=', $qrCode);
        }

        if (!empty($params['shelf_area'])) {
            $where[] = array('shelf_number.shelf_area', 'LIKE', '%'.$params['shelf_area'].'%');
        }
        if (!empty($params['matter_id'])) {
            $where[] = array('qr_detail.matter_id', '=', $params['matter_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('qr_detail.customer_id', '=', $params['customer_id']);
        }

        // データ取得
        $data = $this
            ->from('t_qr_detail as qr_detail')
            ->leftJoin('t_qr as qr', 'qr.id', '=', 'qr_detail.qr_id')
            ->leftJoin('m_shelf_number as shelf_number', 'shelf_number.id', '=', 'qr_detail.shelf_number_id')
            ->leftJoin('t_matter as matter', 'matter.id', '=', 'qr_detail.matter_id')
            ->leftJoin('m_customer as customer', 'customer.id', '=', 'qr_detail.customer_id')
            ->leftJoin('m_warehouse as warehouse', 'warehouse.id', '=', 'qr_detail.warehouse_id')
            ->where($where)
            ->select(
                'qr_detail.id as detail_id',
                'qr_detail.qr_id as qr_id',
                'qr_detail.warehouse_id as warehouse_id',
                'warehouse.warehouse_name',
                'matter.matter_name',
                'customer.customer_name',
                'qr_detail.product_id as product_id',
                'qr_detail.quantity as quantity',
                'qr.qr_code as qr_code',
                'shelf_number.shelf_area as shelf_area',
                'shelf_number.shelf_steps as shelf_steps'
            )
            ->get();

        return $data;
    }

    /**
     * 一覧取得(QR分割)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getQrSplitList($qrCode)
    {
        // Where句作成
        $where = [];
        $where[] = array('qr_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('qr.qr_code', '=', $qrCode);
        $joinWhere = [];
        $joinWhere['qr'][] = array('qr.del_flg', '=', config('const.flg.off'));
        $joinWhere['shelf_number'][] = array('shelf_number.del_flg', '=', config('const.flg.off'));
        $joinWhere['matter'][] = array('matter.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse'][] = array('warehouse.del_flg', '=', config('const.flg.off'));
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));
        $joinWhere['customer'][] = array('customer.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('t_qr_detail as qr_detail')

            ->leftJoin('t_qr as qr', function ($join) use ($joinWhere) {
                $join->on('qr.id', '=', 'qr_detail.qr_id')
                    ->where($joinWhere['qr']);
            })

            ->leftJoin('m_shelf_number as shelf_number', function ($join) use ($joinWhere) {
                $join->on('shelf_number.id', '=', 'qr_detail.shelf_number_id')
                    ->where($joinWhere['shelf_number']);
            })

            ->leftJoin('t_matter as matter', function ($join) use ($joinWhere) {
                $join->on('matter.id', '=', 'qr_detail.matter_id')
                    ->where($joinWhere['matter']);
            })

            ->leftJoin('m_warehouse as warehouse', function ($join) use ($joinWhere) {
                $join->on('warehouse.id', '=', 'qr_detail.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })


            ->leftJoin('m_product as product', function ($join) use ($joinWhere) {
                $join->on('product.id', '=', 'qr_detail.product_id')
                    ->where($joinWhere['product']);
            })


            ->leftJoin('m_customer as customer', function ($join) use ($joinWhere) {
                $join->on('customer.id', '=', 'qr_detail.customer_id')
                    ->where($joinWhere['customer']);
            })

            ->leftJoin('m_general', function ($join) {
                $join->on('customer.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })

            ->where($where)
            ->selectRaw(
                'qr_detail.id as detail_id,
                qr_detail.qr_id as qr_id,
                qr_detail.matter_id as matter_id,
                qr_detail.customer_id as customer_id,
                qr_detail.arrival_id as arrival_id,
                qr_detail.warehouse_id as warehouse_id,
                qr_detail.shelf_number_id as shelf_number_id,
                warehouse.warehouse_name,
                matter.matter_name,
                CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                qr_detail.product_id as product_id,
                product.product_code as product_code,
                product.product_name as product_name,
                qr_detail.quantity as quantity,
                qr.qr_code as qr_code,
                shelf_number.shelf_area as shelf_area,
                shelf_number.shelf_steps as shelf_steps'
            )
            ->orderBy('qr_detail.product_id')
            ->orderBy('qr_detail.quantity','desc')
            ->get();

        return $data;
    }

    /**
     * 一覧取得(棚移動)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getShelfMove($shelfNumberId)
    {
        // Where句作成
        $where = [];
        // $where[] = array(DB::raw('ifnull(qd.shelf_number_id, ps.shelf_number_id)'), '=', $shelfNumberId);
        $where[] = array('ps.shelf_number_id', '=', $shelfNumberId);
        $joinWhere = [];
        $joinWhere['t_qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));
        $joinWhere['t_qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere['t_matter'][] = array('m.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_customer'][] = array('c.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_warehouse'][] = array('w.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('m_shelf_number as s')


            ->leftJoin('m_warehouse as w', function ($join) use ($joinWhere) {
                $join->on('s.warehouse_id', '=', 'w.id')
                    ->where($joinWhere['m_warehouse']);
            })

            ->leftJoin('t_productstock_shelf as ps', function ($join) {
                $join->on('s.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('s.id', '=', 'ps.shelf_number_id');
            })


            ->leftJoin('t_qr_detail as qd', function ($join) use ($joinWhere) {
                $join->on('qd.product_id', '=', 'ps.product_id')
                    ->on('qd.matter_id', '=', 'ps.matter_id')
                    ->on('qd.customer_id', '=', 'ps.customer_id')
                    ->on('qd.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('qd.shelf_number_id', '=', 'ps.shelf_number_id')
                    ->where($joinWhere['t_qr_detail']);
            })
            ->leftJoin('t_qr as q', function ($join) use ($joinWhere) {
                $join->on('q.id', '=', 'qd.qr_id')
                    ->where($joinWhere['t_qr']);
            })
            ->leftJoin('m_product as p', function ($join) use ($joinWhere) {
                $join->on('p.id', '=', DB::raw('ifnull(qd.product_id,ps.product_id)'))
                    ->where($joinWhere['m_product']);
            })
            ->leftJoin('t_matter as m', function ($join) use ($joinWhere) {
                $join->on('m.id', '=', 'ps.matter_id')
                    ->where($joinWhere['t_matter']);
            })
            ->leftJoin('m_customer as c', function ($join) use ($joinWhere) {
                $join->on('c.id', '=', 'ps.customer_id')
                    ->where($joinWhere['m_customer']);
            })
            ->leftJoin('m_general', function ($join) {
                $join->on('c.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })

            ->where($where)
            ->selectRaw(
                'ps.id as productstock_shelf_id
                , ps.stock_flg
                , p.id as product_id
                , p.product_code
                , p.product_name
                , m.id as matter_id
                , m.matter_name
                , c.id as customer_id
                , CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name
                , ifnull(qd.quantity, ps.quantity) as quantity
                , null as confirmed_quantity
                , qd.id as qr_detail_id
                , q.id as qr_id
                , q.qr_code
                , s.warehouse_id as warehouse_id
                , s.id as shelf_number_id
                , s.shelf_area as shelf_area
                , s.shelf_kind
                , w.warehouse_name as warehouse_name
                , 0 as check_box'
            )
            ->get();
        return $data;
    }

    /**
     * 一覧取得(棚卸)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getShelfMoveQr($shelfNumberId)
    {
        $data = $this->from(DB::raw("
        ((select
            ps.id as productstock_shelf_id
            , ps.stock_flg
            , s.shelf_kind
            , p.id as product_id
            , p.product_code
            , p.product_name
            , m.id as matter_id
            , m.matter_name
            , c.id as customer_id
            , c.customer_name
            , ifnull(qd.quantity, ps.quantity) as quantity
            , null as confirmed_quantity
            , qd.id as qr_detail_id
            , q.id as qr_id
            , q.qr_code
            , s.warehouse_id as warehouse_id
            , s.id as shelf_number_id
            , s.shelf_area as shelf_area
            , w.warehouse_name as warehouse_name 
            from
            m_shelf_number as s
            left join m_warehouse as w 
                on s.warehouse_id = w.id 
                and (w.del_flg = 0) 
            left join t_productstock_shelf as ps
                on s.warehouse_id = ps.warehouse_id 
                and s.id = ps.shelf_number_id 
            left join t_qr_detail as qd
                on qd.product_id = ps.product_id 
                and qd.warehouse_id = ps.warehouse_id 
                and qd.shelf_number_id = ps.shelf_number_id 
                and qd.matter_id = ps.matter_id
                and qd.customer_id = ps.customer_id
                and (qd.del_flg = 0) 
            left join t_qr as q 
                on q.id = qd.qr_id 
                and (q.del_flg = 0) 
            left join m_product as p 
                on p.id = ifnull(qd.product_id, ps.product_id) 
            left join t_matter as m 
                on m.id = qd.matter_id 
                and (m.del_flg = 0) 
            left join m_customer as c 
                on c.id = qd.customer_id 
                and (c.del_flg = 0) 
            where
                ps.stock_flg <> 1 || (ps.stock_flg = 1 and s.shelf_kind <> 3)
            )
            union all
            (
            select
            ps.id as productstock_shelf_id
            , ps.stock_flg
            , s.shelf_kind
            , p.id as product_id
            , p.product_code
            , p.product_name
            , m.id as matter_id
            , m.matter_name
            , c.id as customer_id
            , c.customer_name
            , ifnull(qd.quantity, ps.quantity) as quantity
            , null as confirmed_quantity
            , qd.id as qr_detail_id
            , q.id as qr_id
            , q.qr_code
            , s.warehouse_id as warehouse_id
            , s.id as shelf_number_id
            , s.shelf_area as shelf_area
            , w.warehouse_name as warehouse_name 
            from
            m_shelf_number as s
            left join m_warehouse as w 
                on s.warehouse_id = w.id 
                and (w.del_flg = 0) 
            left join t_productstock_shelf as ps 
                on s.warehouse_id = ps.warehouse_id 
                and s.id = ps.shelf_number_id 
            left join t_qr_detail as qd 
                on qd.product_id = ps.product_id 
                and qd.warehouse_id = ps.warehouse_id 
                and qd.shelf_number_id = ps.shelf_number_id 
                and (qd.del_flg = 0) 
            left join t_qr as q 
                on q.id = qd.qr_id 
                and (q.del_flg = 0) 
            left join m_product as p 
                on p.id = ifnull(qd.product_id, ps.product_id) 
            left join t_matter as m 
                on m.id = qd.matter_id 
                and (m.del_flg = 0) 
            left join m_customer as c 
                on c.id = qd.customer_id 
                and (c.del_flg = 0) 
            where
            stock_flg = 1
            and s.shelf_kind = 3
            ))as t
    "))->select("*")
            ->where('shelf_number_id', '=', $shelfNumberId)
            // ->orderby('product_code', 'asc')
            ->distinct()->get();

        return $data;
    }



    /**
     * 一覧取得(棚移動完了画面)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getShelfMoveScanQr($qrInfo)
    {
        // Where句作成
        $where = [];

        $qr_codes =[];
        $ids = [];
        foreach ($qrInfo as $data) {
            if ($data['qr_code'] != null) {
                $qr_codes[] = $data['qr_code'];
            } else {
                $ids[] = $data['productstock_shelf_id'];
            }
        }
        // if (count($qr_codes) > 0) {
        //     $where[] = array('q.qr_code', $qr_codes);
        // } 
        // if (count($ids) > 0) {
        //     $where[] = array('ps.id', $ids);
        // }
        $joinWhere = [];
        $joinWhere['t_qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));
        $joinWhere['t_qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere['t_matter'][] = array('m.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_customer'][] = array('c.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_warehouse'][] = array('w.del_flg', '=', config('const.flg.off'));
        $joinWhere['m_shelf_number'][] = array('s.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('t_productstock_shelf as ps')
            ->leftJoin('t_qr_detail as qd', function ($join) use ($joinWhere) {
                $join->on('qd.product_id', '=', 'ps.product_id')
                    ->on('qd.matter_id', '=', 'ps.matter_id')
                    ->on('qd.customer_id', '=', 'ps.customer_id')
                    ->on('qd.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('qd.shelf_number_id', '=', 'ps.shelf_number_id')
                    ->where($joinWhere['t_qr_detail']);
            })
            ->leftJoin('t_qr as q', function ($join) use ($joinWhere) {
                $join->on('q.id', '=', 'qd.qr_id')
                    ->where($joinWhere['t_qr']);
            })
            ->leftJoin('m_product as p', function ($join) use ($joinWhere) {
                $join->on('p.id', '=', DB::raw('ifnull(qd.product_id,ps.product_id)'))
                    ->where($joinWhere['m_product']);
            })
            ->leftJoin('t_matter as m', function ($join) use ($joinWhere) {
                $join->on('m.id', '=', 'ps.matter_id')
                    ->where($joinWhere['t_matter']);
            })
            ->leftJoin('m_customer as c', function ($join) use ($joinWhere) {
                $join->on('c.id', '=', 'ps.customer_id')
                    ->where($joinWhere['m_customer']);
            })
            ->leftJoin('m_warehouse as w', function ($join) use ($joinWhere) {
                $join->on('w.id', '=', DB::raw('ifnull(qd.warehouse_id, ps.warehouse_id)'))
                    ->where($joinWhere['m_warehouse']);
            })
            ->leftJoin('m_shelf_number as s', function ($join) use ($joinWhere) {
                $join->on('s.id', '=', DB::raw('ifnull(qd.shelf_number_id, ps.shelf_number_id)'))
                    ->where($joinWhere['m_shelf_number']);
            })
            ->leftJoin('m_general', function ($join) {
                $join->on('c.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->when(count($qr_codes)>0 && count($ids)<=0, function($query) use ($qr_codes) {
                return $query->whereIn('q.qr_code',$qr_codes);
            })
            ->when(count($ids)>0 && count($qr_codes)<=0, function($query) use ($ids) {
                return $query->whereIn('ps.id', $ids);
            })
            ->when(count($ids)>0 && count($qr_codes)>0, function($query) use ($ids,$qr_codes) {
                return $query->whereIn('ps.id', $ids)->orWhereIn('q.qr_code',$qr_codes);
            })
            ->selectRaw(
                'p.id as product_id
                , p.product_code
                , p.product_name
                , m.id as matter_id
                , m.matter_name
                , c.id as customer_id
                , CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name
                , ifnull(qd.quantity, ps.quantity) as quantity
                , q.id as qr_id
                , q.qr_code
                , ifnull(qd.warehouse_id, ps.warehouse_id) as warehouse_id
                , ifnull(qd.shelf_number_id, ps.shelf_number_id) as shelf_number_id
                , w.warehouse_name
                , s.shelf_area'

            )
            ->get();
        return $data;
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateWareHouseAndShelf($qrId, $wareHouseId, $shelfNumberId)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $qrId, config('const.logKbn.update'));

            $items = [];
            $items['warehouse_id'] = $wareHouseId;
            $items['shelf_number_id'] = $shelfNumberId;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where('qr_id', $qrId)
                ->update($items);

            $result = ($updateCnt >= 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
    

    /**
     * 一覧取得(返品受入れ)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getAcceptingReturnsList($qrCode)
    {
        // Where句作成
        $where = [];
        $where[] = array('qr_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('qr.qr_code', '=', $qrCode);
        $joinWhere = [];
        $joinWhere['qr'][] = array('qr.del_flg', '=', config('const.flg.off'));
        $joinWhere['shelf_number'][] = array('shelf_number.del_flg', '=', config('const.flg.off'));
        $joinWhere['matter'][] = array('matter.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse'][] = array('warehouse.del_flg', '=', config('const.flg.off'));
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));
        $joinWhere['customer'][] = array('customer.del_flg', '=', config('const.flg.off'));
        $joinWhere['arrival'][] = array('arrival.del_flg', '=', config('const.flg.off'));
        $joinWhere['order_detail'][] = array('order_detail.del_flg', '=', config('const.flg.off'));
        $joinWhere['quote_detail'][] = array('quote_detail.del_flg', '=', config('const.flg.off'));
        $joinWhere['quote'][] = array('quote.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('t_qr_detail as qr_detail')

            ->leftJoin('t_qr as qr', function ($join) use ($joinWhere) {
                $join->on('qr.id', '=', 'qr_detail.qr_id')
                    ->where($joinWhere['qr']);
            })

            ->leftJoin('m_shelf_number as shelf_number', function ($join) use ($joinWhere) {
                $join->on('shelf_number.id', '=', 'qr_detail.shelf_number_id')
                    ->where($joinWhere['shelf_number']);
            })

            ->leftJoin('t_matter as matter', function ($join) use ($joinWhere) {
                $join->on('matter.id', '=', 'qr_detail.matter_id')
                    ->where($joinWhere['matter']);
            })

            ->leftJoin('m_warehouse as warehouse', function ($join) use ($joinWhere) {
                $join->on('warehouse.id', '=', 'qr_detail.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })


            ->leftJoin('m_product as product', function ($join) use ($joinWhere) {
                $join->on('product.id', '=', 'qr_detail.product_id')
                    ->where($joinWhere['product']);
            })


            ->leftJoin('m_customer as customer', function ($join) use ($joinWhere) {
                $join->on('customer.id', '=', 'qr_detail.customer_id')
                    ->where($joinWhere['customer']);
            })

            ->leftJoin('m_general', function ($join) {
                $join->on('customer.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })

            ->leftJoin('t_arrival as arrival', function ($join) use ($joinWhere) {
                $join->on('arrival.id', '=', 'qr_detail.arrival_id')
                    ->where($joinWhere['arrival']);
            })

            ->leftJoin('t_order_detail as order_detail', function ($join) use ($joinWhere) {
                $join->on('order_detail.id', '=', 'arrival.order_detail_id')
                    ->where($joinWhere['order_detail']);
            })

            ->leftJoin('t_quote_detail as quote_detail', function ($join) use ($joinWhere) {
                $join->on('quote_detail.id', '=', 'order_detail.quote_detail_id')
                    ->where($joinWhere['quote_detail']);
            })

            ->leftJoin('t_quote as quote', function ($join) use ($joinWhere) {
                $join->on('quote.quote_no', '=', 'quote_detail.quote_no')
                    ->where($joinWhere['quote']);
            })

            ->where($where)
            ->selectRaw(
                'qr_detail.id as detail_id,
                qr_detail.qr_id as qr_id,
                qr_detail.matter_id as matter_id,
                qr_detail.customer_id as customer_id,
                qr_detail.arrival_id as arrival_id,
                qr_detail.warehouse_id as warehouse_id,
                qr_detail.shelf_number_id as shelf_number_id,
                warehouse.warehouse_name,
                matter.matter_name,
                CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                qr_detail.product_id as product_id,
                product.product_code as product_code,
                product.product_name as product_name,
                qr_detail.quantity as quantity,
                qr.qr_code as qr_code,
                shelf_number.shelf_area as shelf_area,
                shelf_number.shelf_steps as shelf_steps,
                order_detail.id as order_detail_id,
                order_detail.quote_detail_id,
                quote.id as quote_id'
            )
            ->get();

        return $data;
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateQuantity($id, $quantity)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

            $items = [];
            $items['quantity'] = $quantity;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where('id', $id)
                ->update($items);

            $result = ($updateCnt >= 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * QRIDから取得
     *
     * @return void
     */
    public function getByQrId($qrId)
    {
        $data = $this
            ->where('qr_id', '=', $qrId)
            ->get();

        return $data;
    }

    /**
     * IDから取得
     *
     * @return void
     */
    public function getById($id)
    {
        $data = $this
            ->where('id', '=', $id)
            ->first();

        return $data;
    }

    /**
     * 在庫照会(PC)　子グリッドデータ取得
     *
     * @param [type] $params
     * @return void
     */
    public function getStockSearchQrList($params)
    {
        // Where句作成
        $where = [];
        if (!empty($params['product_code'])) {
            $where[] = array('product.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name'])) {
            $where[] = array('product.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['model'])) {
            $where[] = array('product.model', 'LIKE', '%' . $params['model'] . '%');
        }
        if (!empty($params['supplier_name'])) {
            $where[] = array('maker.supplier_name', 'LIKE', '%' . $params['supplier_name'] . '%');
        }
        if (!empty($params['warehouse_name'])) {
            $where[] = array('warehouse.warehouse_name', 'LIKE', '%' . $params['warehouse_name'] . '%');
        }
        if (!empty($params['shelf_area'])) {
            $where[] = array('shelf_number.shelf_area', 'LIKE', '%' . $params['shelf_area'] . '%');
        }
        if (!empty($params['matter_id'])) {
            $where[] = array('p_shelf.matter_id', '=', $params['matter_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('p_shelf.customer_id', '=', $params['customer_id']);
        }


        $data = $this
            ->from('t_productstock_shelf as p_shelf')
            ->join('m_product AS product', 'p_shelf.product_id', '=', 'product.id')
            ->leftJoin('m_supplier as maker', 'maker.id', '=', 'product.maker_id')
            ->join('m_warehouse AS warehouse', 'p_shelf.warehouse_id', '=', 'warehouse.id')
            ->join('m_shelf_number AS shelf_number', 'p_shelf.shelf_number_id', '=', 'shelf_number.id')
            ->leftJoin('t_matter as matter', 'p_shelf.matter_id', '=', 'matter.id')
            ->leftJoin('m_customer as customer', 'p_shelf.customer_id', '=', 'customer.id')
            ->leftJoin('t_qr_detail as qr_detail', function ($join) {
                $join
                    ->on('p_shelf.shelf_number_id', '=', 'qr_detail.shelf_number_id')
                    ->on('p_shelf.product_id', '=', 'qr_detail.product_id')
                    ->on(function ($query) {
                        $query
                            ->where(function ($subQuery) {
                                $subQuery
                                    ->where('shelf_number.shelf_kind', '<>', config('const.shelfKind.return'))
                                    ->whereIn('p_shelf.stock_flg', [config('const.stockFlg.val.order'), config('const.stockFlg.val.keep')])
                                    ->on('p_shelf.matter_id', '=', 'qr_detail.matter_id')
                                    ->on('p_shelf.customer_id', '=', 'qr_detail.customer_id');
                            })
                            ->orWhere('shelf_number.shelf_kind', '=', config('const.shelfKind.return'));
                    })
                    ->where('qr_detail.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('t_qr as qr', 'qr_detail.qr_id', '=', 'qr.id')
            ->where($where)
            ->select([
                'qr_detail.id AS detail_id',
                'p_shelf.product_id',
                'p_shelf.product_code',
                'p_shelf.warehouse_id',
                'warehouse.warehouse_name',
                'p_shelf.shelf_number_id',
                'shelf_number.shelf_area',
                'shelf_number.shelf_steps',
                'p_shelf.stock_flg',
                'matter.matter_name',
                'customer.customer_name',
                'p_shelf.quantity',
                'qr.qr_code',
                'qr_detail.warehouse_id as qr_warehouse_id',
                'qr_detail.shelf_number_id as qr_shelf_number_id',
                'qr_detail.quantity as qr_quantity',
            ])
            ->distinct()
            ->orderBy('p_shelf.product_id', 'p_shelf.warehouse_id', 'p_shelf.shelf_number_id', 'p_shelf.stock_flg')
            ->get();

        return $data;
    }

     /**
     * 商品重複QR詳細取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getDuplicateProduct($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_qr_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_qr_detail.id', '<>', $params['id']);
        $where[] = array('t_qr_detail.qr_id', '=', $params['qr_id']);
        $where[] = array('t_qr_detail.product_id', '=', $params['product_id']);

        // データ取得
        $data = $this
            ->where($where)
            ->select(
                't_qr_detail.*'
            )
            ->first();

        return $data;
    }
}
