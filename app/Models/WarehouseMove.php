<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 倉庫移動
 */
class WarehouseMove extends Model
{
    // テーブル名
    protected $table = 't_warehouse_move';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'id',
        'move_kind',
        'from_warehouse_id',
        'to_warehouse_id',
        'finish_flg',
        'from_product_move_id',
        'to_product_move_id',
        'move_date',
        'stock_flg',
        'qr_code',
        'product_code',
        'product_id',
        'quantity',
        'shipment_detail_id',
        'approval_status',
        'approval_user',
        'approval_at',
        'returnproc_kbn',
        'returnproc_date',
        'returnproc_finish',
        'matter_id',
        'delivery_id',
        'order_detail_id',
        'quote_id',
        'quote_detail_id',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
    ];


    /**
     * 在庫移管一覧
     *
     * @param [type] $params
     * @return void
     */
    public function getTransferList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('move.move_kind', '=', config('const.moveKind.transfer'));
        if (!empty($params['from_warehouse'])) {
            $where[] = array('from_wh.warehouse_name', 'LIKE', '%' . $params['from_warehouse'] . '%');
        }
        if (!empty($params['to_warehouse'])) {
            $where[] = array('to_wh.warehouse_name', 'LIKE', '%' . $params['to_warehouse'] . '%');
        }
        if (!empty($params['product_code'])) {
            $where[] = array('pro.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name'])) {
            $where[] = array('pro.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['model'])) {
            $where[] = array('pro.model', 'LIKE', '%' . $params['model'] . '%');
        }
        if (!empty($params['qr_code'])) {
            $where[] = array('move.qr_code', 'LIKE', '%' . $params['qr_code'] . '%');
        }
        if (!empty($params['from_date'])) {
            $where[] = array('move.move_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $where[] = array('move.move_date', '<=', $params['to_date']);
        }


        $data = $this
            ->from('t_warehouse_move as move')
            ->leftJoin('m_warehouse as from_wh', 'from_wh.id', '=', 'move.from_warehouse_id')
            ->leftJoin('m_warehouse as to_wh', 'to_wh.id', '=', 'move.to_warehouse_id')
            ->leftJoin('m_product as pro', 'pro.id', '=', 'move.product_id')
            // ->leftJoin('t_qr_detail as qr_detail', function($join) use ($params) {
            //     $join->on('qr_detail.product_id', '=', 'move.product_id')
            //          ->on('qr_detail.warehouse_id', '=', 'move.from_warehouse_id')
            //          ;
            // })
            // ->leftJoin('t_qr as qr', 'qr.id', '=', 'qr_detail.qr_id')
            ->where(function ($query) use ($params) {
                // チェックボックス
                if (!empty($params['order'])) {
                    $query->orWhere('move.stock_flg', '=', config('const.stockFlg.val.order'));
                }
                if (!empty($params['stock'])) {
                    $query->orWhere('move.stock_flg', '=', config('const.stockFlg.val.stock'));
                }
                if (!empty($params['keep'])) {
                    $query->orWhere('move.stock_flg', '=', config('const.stockFlg.val.keep'));
                }
            })
            ->where($where)
            ->selectRaw('
                    move.id,
                    DATE_FORMAT(move.move_date, "%Y/%m/%d") as move_date,
                    from_wh.warehouse_name as from_warehouse_name,
                    to_wh.warehouse_name as to_warehouse_name,
                    pro.id as product_id,
                    pro.product_code,
                    pro.product_name,
                    move.stock_flg,
                    move.qr_code,
                    move.quantity,
                    CASE
                        WHEN move.stock_flg = ' . config('const.stockFlg.val.order') . '
                            THEN \'発注品\' 
                        WHEN move.stock_flg = ' . config('const.stockFlg.val.stock') . '
                            THEN \'在庫品\' 
                        WHEN move.stock_flg = ' . config('const.stockFlg.val.keep') . '
                            THEN \'預かり品\' 
                    END AS stock_status, 
                    CASE 
                        WHEN move.finish_flg IS NULL 
                            THEN \'' . config('const.warehouseMoveStatus.status.not_move') . '\' 
                        WHEN move.finish_flg = ' . config('const.warehouseMoveStatus.val.not_move') . '
                            THEN \'' . config('const.warehouseMoveStatus.status.not_move') . '\' 
                        WHEN move.finish_flg = ' . config('const.warehouseMoveStatus.val.moving') . '
                            THEN \'' . config('const.warehouseMoveStatus.status.moving') . '\' 
                        WHEN move.finish_flg = ' . config('const.warehouseMoveStatus.val.moved') . '
                            THEN \'' . config('const.warehouseMoveStatus.status.moved') . '\' 
                    END AS move_status 
                ')
            ->get();

        return $data;
    }

    /**
     * 在庫移管指示登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function addWarehouseMove($params)
    {
        $result = false;

        $kbn = config('const.moveKind.transfer');
        try {
            $result = $this->insertGetId(
                [
                    'finish_flg' => config('const.flg.off'),
                    'from_warehouse_id' => $params['from_warehouse_id'],
                    'to_warehouse_id' => $params['to_warehouse_id'],
                    'move_date' => $params['move_date'],
                    'stock_flg' => $params['stock_flg'],
                    'qr_code' => $params['qr_code'],
                    'move_kind' => $kbn,
                    'from_warehouse_id' => $params['from_warehouse_id'],
                    'to_warehouse_id' => $params['to_warehouse_id'],
                    'move_date' => $params['move_date'],
                    'stock_flg' => $params['stock_flg'],
                    'product_code' => $params['product_code'],
                    'product_id' => $params['product_id'],
                    'matter_id' => $params['matter_id'],
                    'customer_id' => $params['customer_id'],
                    'quantity' => $params['quantity'],
                    'qr_code' => $params['qr_code'],
                    'memo' => $params['memo'],
                    'order_detail_id' => $params['order_detail_id'],
                    'quote_id' => $params['quote_id'],
                    'quote_detail_id' => $params['quote_detail_id'],
                    'from_product_move_staff_id' => Auth::user()->id,
                    'from_product_move_at' => Carbon::now(),
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]
            );
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 在庫移管指示更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateWarehouseMoveById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'from_warehouse_id' => $params['from_warehouse_id'],
                    'to_warehouse_id' => $params['to_warehouse_id'],
                    'move_date' => $params['move_date'],
                    'product_code' => $params['product_code'],
                    'product_id' => $params['product_id'],
                    'quantity' => $params['quantity'],
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
     * @param int $id 倉庫移動ID
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
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param int $qrCode
     * @return boolean True:成功 False:失敗 
     */
    public function deleteByQrCode($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('qr_code', $params['qr_code'])
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * IDで取得
     *
     * @param  $id
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
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;
        if(!array_key_exists('qr_code',$params)){
            $params['qr_code'] = null;
        }

        try {
            $result = $this->insertGetId([
                'move_kind' => $params['move_kind'],
                'from_warehouse_id' => $params['from_warehouse_id'],
                'to_warehouse_id' => $params['to_warehouse_id'],
                'finish_flg' => $params['finish_flg'],
                'from_product_move_id' => $params['from_product_move_id'],
                'to_product_move_id' => $params['to_product_move_id'],
                'move_date' => Carbon::now(),
                'product_code' => $params['product_code'],
                'product_id' => $params['product_id'],
                'quantity' => $params['quantity'],
                'shipment_detail_id' => $params['shipment_detail_id'],
                'approval_status' => $params['approval_status'],
                'approval_user' => $params['approval_user'],
                'approval_at' => $params['approval_at'],
                'stock_flg' => $params['stock_flg'],
                'qr_code' => $params['qr_code'],
                // 'returnproc_kbn' => $params['returnproc_kbn'],
                // 'returnproc_date' => $params['returnproc_date'],
                // 'returnproc_check' => $params['returnproc_check'],
                // 'returnproc_check_user' => $params['returnproc_check_user'],
                // 'returnproc_finish' => $params['returnproc_finish'],
                'matter_id' => $params['matter_id'],
                'customer_id' => $params['customer_id'],
                'delivery_id' => $params['delivery_id'],
                'order_detail_id' => $params['order_detail_id'],
                'quote_id' => $params['quote_id'],
                'quote_detail_id' => $params['quote_detail_id'],
                'from_product_move_staff_id' => Auth::user()->id,
                'from_product_move_at' => Carbon::now(),
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
     * 在庫移管受入画面の一覧を取得
     * 
     * @return 
     */
    public function getListTransferAcceptance(array $params)
    {
        // 倉庫移管レコードの取得
        $where1 = [];
        $where1[] = array('w.finish_flg', '=', config('const.flg.on'));

        if ($params['warehouse_id'] != null && $params['warehouse_id'] != "" && $params['warehouse_id'] != 0) {
            $where1[] = array('w.to_warehouse_id', '=', $params['warehouse_id']);
        }
        if (isset($params['move_kind'])) {
            $where1[] = array('w.move_kind', '=', $params['move_kind']);
        }
        
        $joinWhere1 = [];
        $joinWhere1['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere1['matter'][] = array('m.del_flg', '=', config('const.flg.off'));
        $joinWhere1['qr'][] = array('q.del_flg', '=', config('const.flg.off'));

        $query1 = $this
            ->from('t_warehouse_move as w')
            ->leftJoin('m_product as p', function ($join) use ($joinWhere1) {
                $join->on('p.id', '=', 'w.product_id')
                    ->where($joinWhere1['product']);
            })
            ->leftJoin('t_matter as m', function ($join) use ($joinWhere1) {
                $join->on('m.id', '=', 'w.matter_id')
                    ->where($joinWhere1['matter']);
            })
            ->leftJoin('t_qr as q', function ($join) use ($joinWhere1) {
                $join->on('q.qr_code', '=', 'w.qr_code')
                    ->where($joinWhere1['qr']);
            })
            ->leftJoin('t_product_move as pm', function ($join){
                $join->on('pm.id', '=', 'w.from_product_move_id');
            })
            ->leftJoin('t_shipment_detail as sd', function ($join){
                $join->on('sd.id', '=', 'w.shipment_detail_id');
            })
            ->leftJoin('t_reserve as r', function ($join){
                $join->on('r.id', '=', 'sd.reserve_id');
            })

            ->where($where1)
            // ->whereIn('w.move_kind', [0, 1, 2])
            ->selectRaw( 
                "w.id,
                w.product_id,
                r.id as reserve_id,
                r.reserve_quantity_validity,
                r.issue_quantity,
                w.product_code,
                CONCAT(p.product_name ,'/' , p.model) as product_name,
                p.product_name as product_name_only,
                w.quantity,
                p.stock_unit as unit,
                true as is_transfar,
                w.stock_flg,
                w.move_kind,
                w.matter_id,
                m.matter_no,
                m.matter_name,
                w.customer_id,
                r.order_no,
                r.order_id,
                w.order_detail_id,
                w.quote_id,
                w.quote_detail_id,

                case when w.move_kind = 1 then pm.from_warehouse_id else ifnull(pm.to_warehouse_id,w.from_warehouse_id) end as from_warehouse_id,
                case when w.move_kind = 1 then pm.from_shelf_number_id else ifnull(pm.to_shelf_number_id,
                (select shelf_number_id from t_qr_detail as qd where qd.qr_id = q.id order by qd.id limit 1)
                ) end as from_shelf_number_id,

                w.qr_code,
                q.id as qr_id,
                w.memo,
                0 as acceptance_quantity"
            );

        if ($params['move_kind'] == config('const.warehouseMoveKind.val.redelivery')) {
            // 出荷積込レコードの取得
            $where2 = [];
            $where2[] = array('l.delivery_finish_flg', '=', config('const.flg.off'));
            if ($params['warehouse_id'] != null && $params['warehouse_id'] != ""  && $params['warehouse_id'] != 0) {
                $where2[] = array('l.from_warehouse_id', '=', $params['warehouse_id']);
            }
            $where2[] = array('l.del_flg', '=', config('const.flg.off'));
            $joinWhere2 = [];
            $joinWhere2['product'][] = array('p.del_flg', '=', config('const.flg.off'));
            $joinWhere2['matter'][] = array('m.del_flg', '=', config('const.flg.off'));
            $joinWhere2['shipment'][] = array('s.del_flg', '=', config('const.flg.off'));

            $query2 = $this
                ->from('t_loading as l')
                ->leftJoin('m_product as p', function ($join) use ($joinWhere2) {
                    $join->on('p.id', '=', 'l.product_id')
                        ->where($joinWhere2['product']);
                })
                ->leftJoin('t_matter as m', function ($join) use ($joinWhere2) {
                    $join->on('m.id', '=', 'l.matter_id')
                        ->where($joinWhere2['matter']);
                })
                ->leftJoin('t_shipment as s', function ($join) use ($joinWhere2) {
                    $join->on('s.id', '=', 'l.shipment_id')
                        ->where($joinWhere2['shipment']);
                })
                ->leftJoin('t_shipment_detail as sd', function ($join){
                    $join->on('sd.id', '=', 'l.shipment_detail_id');
                })
                ->leftJoin('t_reserve as r', function ($join){
                    $join->on('r.id', '=', 'sd.reserve_id');
                })
                ->where($where2)
                ->selectRaw(
                    "l.id,
                        l.product_id,
                        r.id as reserve_id,
                        r.reserve_quantity_validity,
                        r.issue_quantity,
                        l.product_code,
                        CONCAT(p.product_name ,'/' , p.model) as product_name,
                        p.product_name as product_name_only,
                        l.loading_quantity as quantity,
                        p.stock_unit as unit,
                        false as is_transfar,
                        l.stock_flg,
                        3 as move_kind,
                        ifnull(l.matter_id,0) as matter_id,
                        m.matter_no,
                        m.matter_name,
                        ifnull(m.customer_id,0) as customer_id,
                        r.order_no,
                        r.order_id,
                        l.order_detail_id,
                        l.quote_id,
                        l.quote_detail_id,
                        l.to_warehouse_id as from_warehouse_id,
                        l.to_shelf_number_id as from_shelf_number_id,
                        null as qr_code,
                        null as qr_id,
                        s.shipment_comment as memo,
                        0 as acceptance_quantity"
                );

            // UNIONで取得
            $mainQuery = $query1->union($query2);
        } else {
            $mainQuery = $query1;
        }
        $data = $mainQuery
            ->orderBy('matter_id', 'asc')
            ->orderBy('product_code', 'asc')
            ->get();

        return $data;
    }

    /**
     * 在庫移管画面の一覧を取得
     * 
     * @return 
     */
    public function getListTransfer(array $params)
    {
        // 倉庫移管レコードの取得
        $where = [];
        $where[] = array('wm.finish_flg', '=', config('const.flg.off'));
        if (!empty($params['from_date'])) {
            $where[] = array('wm.move_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $where[] = array('wm.move_date', '<=', $params['to_date']);
        }
        if (!empty($params['warehouse_id'])&& $params['warehouse_id'] != 0) {
            $where[] = array('wm.from_warehouse_id', '=', $params['warehouse_id']);
        }
        $where[] = array('s.shelf_kind', '=', 0);
        $joinWhere = [];
        $joinWhere['productstock_shelf'][] = array('ps.matter_id', '=', '0');
        $joinWhere['productstock_shelf'][] = array('ps.customer_id', '=', '0');
        $joinWhere['productstock_shelf'][] = array('ps.stock_flg', '=', '1');
        $joinWhere['productstock_shelf'][] = array('wm.stock_flg', '=', '1');
        $joinWhere['shelf_number'][] = array('s.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse1'][] = array('w1.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse2'][] = array('w2.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_warehouse_move as wm')
            ->leftJoin('m_warehouse as w1', function ($join) use ($joinWhere) {
                $join->on('w1.id', '=', 'wm.from_warehouse_id')
                    ->where($joinWhere['warehouse1']);
            })
            ->leftJoin('m_warehouse as w2', function ($join) use ($joinWhere) {
                $join->on('w2.id', '=', 'wm.to_warehouse_id')
                    ->where($joinWhere['warehouse2']);
            })

            ->leftJoin('t_qr as q', function ($join) use ($joinWhere) {
                $join->on('q.qr_code', '=', 'wm.qr_code')
                    ->where($joinWhere['qr']);
            })

            ->leftJoin('t_qr_detail as qd', function ($join) use ($joinWhere) {
                $join->on('qd.qr_id', '=', 'q.id')
                    ->where($joinWhere['qr_detail']);
            })
            ->leftJoin('t_productstock_shelf as ps', function ($join) use ($joinWhere) {
                $join->on('ps.product_id', '=', 'wm.product_id')
                    ->on('ps.warehouse_id', '=', 'wm.from_warehouse_id')
                    ->where($joinWhere['productstock_shelf']);
            })

            ->leftJoin('m_shelf_number as s', function ($join) use ($joinWhere) {
                $join->on('s.id', '=', DB::raw('ifnull(qd.shelf_number_id,ps.shelf_number_id)'))
                    ->where($joinWhere['shelf_number']);
            })


            ->where($where)
            ->selectRaw(
                "wm.from_warehouse_id
                    ,wm.to_warehouse_id
                    ,w1.warehouse_name as from_warehouse_name
                    ,w2.warehouse_name as to_warehouse_name
                    ,count(distinct ifnull(qd.product_id,wm.product_id)) as quantity
                    ,0 as check_box"
            )
            ->groupBy('wm.from_warehouse_id')
            ->groupBy('wm.to_warehouse_id')
            ->groupBy('w1.warehouse_name')
            ->groupBy('w2.warehouse_name')
            ->orderBy('wm.from_warehouse_id', 'asc')
            ->orderBy('wm.to_warehouse_id', 'asc')
            ->get();
        return $data;
    }


    /**
     * 在庫移管画面(商材登録)の一覧を取得
     * 
     * @return 
     */
    public function getListStockTransfer(array $params)
    {
        // 倉庫移管レコードの取得
        $where = [];
        $where[] = array('w.finish_flg', '=', config('const.flg.off'));
        if (!empty($params['from_date'])) {
            $where[] = array('w.move_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $where[] = array('w.move_date', '<=', $params['to_date']);
        }
        $where[] = array('s.shelf_kind', '=', 0);

        $joinWhere = [];
        $joinWhere['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        // $joinWhere['qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));
        // $joinWhere['qr_detail'][] = array('qd.quantity', '=', 'w.quantity');

        //$joinWhere['productstock_shelf'][] = array('ps.product_id', '=', 'w.product_id');
        //$joinWhere['productstock_shelf'][] = array('ps.warehouse_id', '=', 'w.from_warehouse_id');
        $joinWhere['productstock_shelf'][] = array('ps.matter_id', '=', '0');
        $joinWhere['productstock_shelf'][] = array('ps.customer_id', '=', '0');
        $joinWhere['productstock_shelf'][] = array('ps.stock_flg', '=', '1');
        $joinWhere['productstock_shelf'][] = array('w.stock_flg', '=', '1');

        //$joinWhere['shelf_number'][] = array('s.id', '=', 'ifnull(qd.shelf_number_id,ps.shelf_number_id)');
        $joinWhere['shelf_number'][] = array('s.del_flg', '=', config('const.flg.off'));

        $subQuery = $this
                ->from('t_qr as qr')
                ->leftJoin('t_qr_detail as qrd', function($join) {
                    $join
                        ->on('qrd.qr_id', '=', 'qr.id')
                        ->whereRaw('qrd.del_flg ='. config('const.flg.off'))
                        ;
                })
                ->selectRaw('
                    qr.qr_code
                    , qrd.warehouse_id
                    , qrd.shelf_number_id 
                ')
                ->whereRaw('
                    qr.del_flg = '. config('const.flg.off').'
                ')
                ->distinct()
                ;
                
        $data = $this
            ->from('t_warehouse_move as w')
            ->leftJoin(DB::raw('('. $subQuery->toSql() .') as qrd'), 
                [['qrd.qr_code', '=', 'w.qr_code'], ['qrd.warehouse_id', '=', 'w.from_warehouse_id']]
            )
            // ->mergeBindings($subQuery)
            ->leftJoin('t_qr as q', function ($join) use ($joinWhere) {
                $join->on('q.qr_code', '=', 'w.qr_code')
                    ->where($joinWhere['qr']);
            })
            // ->join('t_qr_detail as qd', function ($join) use ($joinWhere) {
            //     $join->on('qd.qr_id', '=', 'q.id')
            //         ->where($joinWhere['qr_detail'])
            //         // ->where('qd.quantity', '=', 'w.quantity')
            //         ;
            // })
            ->leftJoin('m_product as p', function ($join) use ($joinWhere) {
                $join->on('p.id', '=', 'w.product_id')
                    ->where($joinWhere['product']);
            })

            ->leftJoin('t_productstock_shelf as ps', function ($join) use ($joinWhere) {
                $join->on('ps.product_id', '=', 'w.product_id')
                    ->on('ps.warehouse_id', '=', 'w.from_warehouse_id')
                    ->where($joinWhere['productstock_shelf']);
            })

            ->leftJoin('m_shelf_number as s', function ($join) use ($joinWhere) {
                $join->on('s.id', '=', DB::raw('ifnull(qrd.shelf_number_id,ps.shelf_number_id)'))
                    ->where($joinWhere['shelf_number']);
            })
            ->where($where)
            ->whereIn('w.from_warehouse_id', $params['from_warehouse_id'])
            ->whereIn('w.to_warehouse_id', $params['to_warehouse_id'])
            ->selectRaw(
                "w.id,
                w.product_id as product_id,
                p.product_code,
                p.product_name,
                p.model,
                w.quantity as quantity,
                w.matter_id as matter_id,
                w.customer_id as customer_id,
                0 as delivery_quantity,
                p.stock_unit as unit,
                w.stock_flg,
                w.move_kind,
                w.to_warehouse_id, 
                w.from_warehouse_id,
                w.qr_code,
                w.quote_detail_id,
                w.order_detail_id,
                q.id as qr_id,
                ifnull(qrd.shelf_number_id,ps.shelf_number_id) as shelf_number_id,
                s.shelf_area"
            )
            // ifnull(qd.shelf_number_id,ps.shelf_number_id) as shelf_number_id,
            ->orderBy('p.product_code', 'asc')
            ->get();
        return $data;
    }

    /**
     * 在庫移管画面の一覧を取得(トラック登録)
     * 
     * @return 
     */
    public function getListTransferTruck(array $params)
    {
        //倉庫移管レコードの取得
        $joinWhere = [];
        $joinWhere['m_warehouse'][] = array('w.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_warehouse_move as wm')
            ->leftJoin('m_warehouse as w', function ($join) use ($joinWhere) {
                $join->on('w.id', '=', 'wm.to_warehouse_id')
                    ->where($joinWhere['m_warehouse']);
            })

            ->leftJoin('t_qr as q', function ($join) use ($joinWhere) {
                $join->on('q.qr_code', '=', 'wm.qr_code')
                    ->where($joinWhere['qr']);
            })

            ->leftJoin('t_qr_detail as qd', function ($join) use ($joinWhere) {
                $join->on('qd.qr_id', '=', 'q.id')
                    ->where($joinWhere['qr_detail']);
            })

            ->whereIn('wm.id', $params['id'])
            // ->where($where)
            ->selectRaw(
                "w.warehouse_name as to_warehouse_name,
                count(distinct ifnull(qd.product_id,wm.product_id)) as product_quantity"
            )
            ->groupBy('w.warehouse_name')
            ->get();
        return $data;
    }

    /**
     * 在庫移管画面(トラック登録)の一覧を取得（共通関数用にグルーピング）
     * 
     * @return 
     */
    public function getListStockTransferMoveInventory(array $params)
    {
        // 倉庫移管レコードの取得
        $joinWhere = [];
        $joinWhere['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinWhere['qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));
        $joinWhere['productstock_shelf'][] = array('ps.matter_id', '=', '0');
        $joinWhere['productstock_shelf'][] = array('ps.customer_id', '=', '0');
        $joinWhere['productstock_shelf'][] = array('ps.stock_flg', '=', '1');
        $joinWhere['productstock_shelf'][] = array('w.stock_flg', '=', '1');


        $subQuery = $this
                ->from('t_warehouse_move as w')
                ->leftJoin('t_qr as q', function ($join) use ($joinWhere) {
                    $join->on('q.qr_code', '=', 'w.qr_code')
                        ->where($joinWhere['qr']);
                })
                ->leftJoin('t_qr_detail as qd', function ($join) use ($joinWhere) {
                    $join->on('qd.qr_id', '=', 'q.id')
                        ->where($joinWhere['qr_detail']);
                })
                ->leftJoin('m_product as p', function ($join) use ($joinWhere) {
                    $join->on('p.id', '=', DB::raw('ifnull(qd.product_id,w.product_id)'))
                        ->where($joinWhere['product']);
                })
                ->leftJoin('t_productstock_shelf as ps', function ($join) use ($joinWhere) {
                    $join->on('ps.product_id', '=', 'w.product_id')
                        ->on('ps.warehouse_id', '=', 'w.from_warehouse_id')
                        ->where($joinWhere['productstock_shelf']);
                })
                ->whereIn('w.id', $params['id'])
                ->selectRaw('
                    w.from_warehouse_id
                    , w.stock_flg
                    , ifnull(qd.shelf_number_id, ps.shelf_number_id) as shelf_number_id
                    , ifnull(qd.matter_id, 0) as matter_id
                    , ifnull(qd.customer_id, 0) as customer_id
                    , p.id as product_id
                    , p.product_code
                    , qd.id as qr_detail_id
                    , ifnull(qd.quantity,0) as quantity
                ')
                ->distinct();

        $data = $this
            ->from(DB::raw('('. $subQuery->toSql(). ') as w_move'))
            ->mergeBindings($subQuery->getQuery())
            ->selectRaw('
                w_move.from_warehouse_id
                , w_move.stock_flg
                , w_move.shelf_number_id
                , w_move.matter_id
                , w_move.customer_id
                , w_move.product_id
                , w_move.product_code
                , sum(w_move.quantity) as quantity
            ')
            ->groupBy('w_move.stock_flg')
            ->groupBy('w_move.matter_id')
            ->groupBy('w_move.customer_id')
            ->groupBy('w_move.from_warehouse_id')
            ->groupBy('w_move.shelf_number_id')
            ->groupBy('w_move.product_id')
            ->groupBy('w_move.product_code')
            ->get()
            ;

        return $data;
    }

    /**
     * 移動完了（出庫トラン）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateMoveFinish($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'finish_flg' => 2,
                    'to_product_move_id' => $params['to_product_move_id'],
                    'to_product_move_staff_id' => Auth::user()->id,
                    'to_product_move_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt >= 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 移動種別から取得
     *
     * @return void
     */
    public function getByMoveKind($kind)
    {
        $where = [];
        $where[] = array('move.move_kind', '=', $kind);
        $where[] = array('move.finish_flg', '<>', config('const.warehouseMoveFinishFlg.val.completed'));

        $data = $this
            ->from('t_warehouse_move as move')
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 状況(未)の移管数取得
     *
     * @return void
     */
    public function getMoveQuantity($warehouseId, $kind)
    {
        $where = [];
        $where[] = array('move.move_kind', '=', $kind);
        $where[] = array('move.from_warehouse_id', '=', $warehouseId);
        $where[] = array('move.finish_flg', '=', config('const.warehouseMoveStatus.val.not_move'));
        $where[] = array('move.stock_flg', '=', config('const.stockFlg.val.stock'));

        $data = $this
            ->from('t_warehouse_move as move')
            ->where($where)
            ->selectRaw('
                    move.stock_flg,
                    move.from_warehouse_id,
                    move.product_id,
                    SUM(move.quantity) as quantity
                ')
            ->groupBy('move.stock_flg', 'move.from_warehouse_id', 'move.product_id')
            ->get();

        return $data;
    }

    /**
     * 移動完了（入庫トラン）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateMoveFinishFromProductMove($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'finish_flg' => 1,
                    'from_product_move_id' => $params['from_product_move_id'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt >= 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 返品一覧 取得
     *
     * @param $params
     * @return void
     */
    public function getReturnList($params) 
    {
        $where = [];
        $where[] = array('wm.move_kind', '=', config('const.moveKind.return'));

        if (!empty($params['customer_name'])) {
            $where[] = array('cName.customer_name', 'LIKE', '%' . $params['customer_name'] . '%');
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('m.matter_no', 'LIKE', '%' . $params['matter_no'] . '%');
        }
        if (!empty($params['matter_name'])) {
            $where[] = array('m.matter_name', 'LIKE', '%' . $params['matter_name'] . '%');
        }
        if (!empty($params['product_code'])) {
            $where[] = array('p.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name'])) {
            $where[] = array('p.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['supplier_name'])) {
            $where[] = array('p_maker.supplier_name', 'LIKE', '%' . $params['supplier_name'] . '%');
        }
        if (!empty($params['qr_code'])) {
            $where[] = array('wm.qr_code', 'LIKE', '%' . $params['qr_code'] . '%');
        }        
        if (!empty($params['from_move_date'])) {
            $where[] = array('wm.move_date', '>=', $params['from_move_date']);
        }
        if (!empty($params['to_move_date'])) {
            $where[] = array('wm.move_date', '<=', $params['to_move_date']);
        }
        if (!empty($params['department_name'])) {
            $where[] = array('matter_department.department_name', 'LIKE', '%' . $params['department_name'] . '%');
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('s.staff_name', 'LIKE', '%' . $params['staff_name'] . '%');
        }

        $cNameQuery = $this
            ->from('m_customer as cus')
            ->leftJoin('m_general', function ($join) {
                $join->on('cus.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->selectRaw('
                cus.id as customer_id,
                CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name
            ');

        $data = $this
                ->from('t_warehouse_move as wm')
                ->where($where)
                ->leftJoin('t_matter as m', 'm.id', '=', 'wm.matter_id')
                ->leftJoin('m_department as matter_department', 'matter_department.id', '=', 'm.department_id')
                ->leftJoin('m_staff as matter_staff', 'm.staff_id', '=', 'matter_staff.id')
                ->leftJoin('m_staff_department as matter_sd', function($join) {
                    $join
                        ->on('matter_sd.staff_id', '=', 'matter_staff.id')
                        ->where('matter_sd.main_flg', '=', config('const.flg.on'))
                        ;
                })
                ->leftJoin('m_department as ms_department', 'ms_department.id', '=', 'matter_sd.department_id')
                // ->leftJoin('m_customer as c', 'c.id', '=', 'wm.customer_id')
                ->leftjoin(DB::raw('(' . $cNameQuery->toSql() . ') as cName'),'cName.customer_id','=','wm.customer_id')
                ->mergeBindings($cNameQuery->getQuery())
                ->leftJoin('m_product as p', 'p.id', '=', 'wm.product_id')
                ->leftJoin('m_supplier as p_maker', 'p_maker.id', '=', 'p.maker_id')
                ->leftJoin('m_general as p_general', function($join) {
                    $join
                        ->on('p_maker.juridical_code', '=', 'p_general.value_code')
                        ->where('p_general.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftJoin('m_warehouse as w', 'wm.to_warehouse_id', '=', 'w.id')
                ->leftJoin('m_staff as s', 's.id', '=', 'wm.from_product_move_staff_id')
                ->leftJoin('m_staff_department as sd', function($join) {
                    $join
                        ->on('sd.staff_id', '=', 's.id')
                        ->where('sd.main_flg', '=', config('const.flg.on'))
                        ;
                })
                ->leftJoin('m_department as d', 'd.id', '=', 'sd.department_id')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('t_product_move AS to_pm', 'to_pm.id', '=', 'wm.to_product_move_id')
                ->selectRaw('
                    wm.id,
                    wm.move_kind,
                    wm.from_warehouse_id,
                    wm.to_warehouse_id,
                    wm.from_product_move_id,
                    wm.to_product_move_id,
                    to_pm.to_shelf_number_id,
                    wm.finish_flg,
                    DATE_FORMAT(wm.move_date, "%Y/%m/%d") as move_date,
                    wm.stock_flg,
                    wm.product_id,
                    wm.product_code,
                    p.product_name,
                    p.model,
                    wm.matter_id,
                    wm.customer_id,
                    wm.quantity,
                    wm.qr_code,
                    wm.shipment_detail_id,
                    wm.approval_user,
                    wm.approval_at,
                    wm.delivery_id,
                    wm.order_detail_id,
                    wm.quote_id,
                    wm.quote_detail_id,
                    w.warehouse_name,
                    m.matter_no,
                    m.matter_name,
                    wm.order_detail_id,
                    od.supplier_id,
                    CONCAT(COALESCE(p_general.value_text_2, \'\'), COALESCE(p_maker.supplier_name, \'\'), COALESCE(p_general.value_text_3, \'\')) as maker_name,
                    CASE
                        WHEN wm.id IS NULL
                            THEN \''. config('const.returnApprovalStatus.status.not_approval'). '\' 
                        WHEN wm.approval_status ='. config('const.returnApprovalStatus.val.not_approval'). '
                            THEN \''. config('const.returnApprovalStatus.status.not_approval'). '\'
                        WHEN wm.approval_status ='. config('const.returnApprovalStatus.val.approvaled'). '
                            THEN \''. config('const.returnApprovalStatus.status.approvaled'). '\'
                        WHEN wm.approval_status ='. config('const.returnApprovalStatus.val.rejection'). '
                            THEN \''. config('const.returnApprovalStatus.status.rejection'). '\'
                        ELSE \''. config('const.returnApprovalStatus.status.not_approval'). '\'
                    END AS approval_status,

                    matter_staff.id AS matter_staff_id,
                    ms_department.chief_staff_id AS matter_staff_chief_id
                ')
                ->get()
                ;

        return $data;
    }

    /**
     * 返品承認/否認
     *
     * @param $params
     * @param $status   config('const.returnApprovalStatus.val)
     * @return void
     */
    public function applyApprovalProcess($params, $status)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if ($status == config('const.returnApprovalStatus.val.approvaled')) {
                $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'to_warehouse_id' => $params['to_warehouse_id'],
                        'approval_status' => $status,
                        'finish_flg' => config('const.flg.on'),
                        'approval_user' => Auth::user()->id,
                        'approval_at' => Carbon::now(),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            } else if ($status == config('const.returnApprovalStatus.val.rejection')) {
                $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'approval_status' => $status,
                        'approval_user' => Auth::user()->id,
                        'approval_at' => Carbon::now(),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            }
            $result = ($updateCnt >= 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

     /**
     * Qrコード更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateQr($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'qr_code' => $params['qr_code'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt >= 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 見積IDで取得
     *
     * @param [type] $quoteId
     * @return void
     */
    public function getByQuoteId($quoteId)
    {
        $data = $this
                ->where([
                    ['quote_id', $quoteId],
                ])
                ->get()
                ;
        return $data;
    }


    /**
     * 見積明細から返品数取得
     *
     * @param [type] $quoteDetailId
     * @return void
     */
    public function getSumReturnByQuoteDetailAndProduct($quoteDetailId) {
        // Where句作成
        $where = [];
        $where[] = array('t_warehouse_move.quote_detail_id', '=', $quoteDetailId);

        // 返品数
        $data = $this
            ->from('t_warehouse_move')
            ->join('t_shipment_detail', 't_warehouse_move.shipment_detail_id', 't_shipment_detail.id')
            ->join('t_shipment', 't_shipment_detail.shipment_id', 't_shipment.id')
            ->join('m_product', 't_shipment_detail.product_id', 'm_product.id')
            ->where($where)
            ->select([
                't_warehouse_move.quote_detail_id',
                DB::raw('SUM(t_warehouse_move.quantity) as return_quantity')
            ])
            ->where('t_warehouse_move.move_kind', config('const.moveKind.return'))
            ->where('t_warehouse_move.approval_status', config('const.approvalDetailStatus.val.approved'))
            // ->where('t_shipment_detail.stock_flg', '!=', config('const.stockFlg.val.order'))
            ->groupBy('t_warehouse_move.quote_detail_id')
            ->first()
            ;

        return $data;
    }

    /**
     * 移管予定数取得
     *
     * @return void
     */
    public function getNotFinishTransfer($params)
    {
        $where = [];
        $where[] = array('move.move_kind', '=', config('const.moveKind.transfer'));
        $where[] = array('move.finish_flg', '=', config('const.warehouseMoveFinishFlg.val.unfinished'));

        if (isset($params['stock_flg'])) {
            $where[] = array('move.stock_flg', '=', $params['stock_flg']);
        }
        if (!empty($params['from_warehouse_id'])) {
            $where[] = array('move.from_warehouse_id', '=', $params['from_warehouse_id']);
        }
        if (!empty($params['product_id'])) {
            $where[] = array('move.product_id', '=', $params['product_id']);
        }
        if (!empty($params['matter_id'])) {
            $where[] = array('move.matter_id', '=', $params['matter_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('move.customer_id', '=', $params['customer_id']);
        }
        if (!empty($params['order_detail_id'])) {
            $where[] = array('move.order_detail_id', '=', $params['order_detail_id']);
        }

        $data = $this
            ->from('t_warehouse_move as move')
            ->where($where)
            ->select([
                DB::raw('SUM(move.quantity) AS sum_quantity')
            ])
            ->first()
            ;

        return $data;
    }


    /**
     * 未承認、承認済みの返品受入が存在するか
     *
     * @param [type] $qrCode
     * @return boolean
     */
    public function isExistReturnQr($qrCode) 
    {
        $where = [];
        $where[] = array('move_kind', '=', config('const.moveKind.return'));
        $where[] = array('approval_status', '<>', config('const.returnApprovalStatus.val.rejection'));  // 未承認、承認済み
        $where[] = array('qr_code', '=', $qrCode);

        $cnt = $this
            ->where($where)
            ->count()
            ;
        
        return $cnt > 0;
    }

    /**
     * 返品受入
     * 返品済み数取得
     *
     * @param [type] $deliveryId
     * @return void
     */
    public function getSumReturnedQuantity($deliveryId)
    {
        $where = [];
        $where[] = array('t_warehouse_move.delivery_id', '=', $deliveryId);
        $where[] = array('t_warehouse_move.move_kind', '=', config('const.moveKind.return'));

        $statusList = [config('const.returnApprovalStatus.val.not_approval'), config('const.returnApprovalStatus.val.approvaled')];

        $data = $this
            ->from('t_warehouse_move')
            ->selectRaw('
                sum(quantity) as quantity
            ')
            ->where($where)
            ->whereIn('t_warehouse_move.approval_status', $statusList)
            ->groupBy('delivery_id')
            ->first()
            ;

        return $data;
    }

        /**
     * 返品受入
     * 返品済み数取得
     *
     * @param [type] $params
     * @return void
     */
    public function getSumStockReturnedQuantity($params)
    {
        $where = [];
        $where[] = array('t_warehouse_move.stock_flg', '=', config('const.stockFlg.val.stock'));
        $where[] = array('t_warehouse_move.move_kind', '=', config('const.moveKind.return'));

        if (!empty($params['from_warehouse_id'])) {
            $where[] = array('t_warehouse_move.from_warehouse_id', '=', $params['from_warehouse_id']);
        }
        if (!empty($params['product_id'])) {
            $where[] = array('t_warehouse_move.product_id', '=', $params['product_id']);
        }

        $statusList = [config('const.returnApprovalStatus.val.not_approval'), config('const.returnApprovalStatus.val.approvaled')];
        $finishFlgList = [config('const.warehouseMoveFinishFlg.val.unfinished'), config('const.warehouseMoveFinishFlg.val.issued')];

        $data = $this
            ->from('t_warehouse_move')
            ->selectRaw('
                sum(quantity) as quantity
            ')
            ->where($where)
            ->whereIn('t_warehouse_move.approval_status', $statusList)
            ->whereIn('t_warehouse_move.finish_flg', $finishFlgList)
            ->groupBy('delivery_id')
            ->first()
            ;

        return $data;
    }
}
