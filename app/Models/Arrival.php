<?php

namespace App\Models;

use App\Libs\LogUtil;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;

/**
 * 入荷データ
 */
class Arrival extends Model
{
    // テーブル名
    protected $table = 't_arrival';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    public function Arrival(){
        return $this->belongsTo('App\Models\Arrival');
    }
    
    protected $fillable = [
        'product_id',				
        'product_code',				
        'to_warehouse_id',				
        'to_shelf_number_id',				
        'quantity',				
        'arrival_date',				
        'order_id',				
        'order_no',				
        'order_detail_id',				
        'reserve_id',				
        'del_flg',				
        'created_user',				
        'created_at',				
        'update_user',				
        'update_at',				
    ];

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句作成
        $where = [];
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.status', '=', config('const.orderStatus.val.ordered'));
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.product_id', '<>', config('const.flg.off'));
        $where[] = array('od.order_quantity', '>', '0');

        if (!empty($params['customer_name'])) {
            $where[] = array('customer.id', '=', $params['customer_name']);
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('matter.matter_no', 'LIKE', '%'.$params['matter_no'].'%');
        }
        if (!empty($params['matter_name'])) {
            $where[] = array('matter.matter_name', 'LIKE', '%'.$params['matter_name'].'%');
        }
        if (!empty($params['warehouse_name'])) {
            $where[] = array('warehouse.warehouse_name', 'LIKE', '%'.$params['warehouse_name'].'%');
        }
        if (!empty($params['department_name'])) {
            $where[] = array('department.department_name', 'LIKE', '%'.$params['department_name'].'%');
        }
        if (!empty($params['order_no'])) {
            $where[] = array('o.order_no', 'LIKE', '%'.$params['order_no'].'%');
        }
        if (!empty($params['maker_name'])) {
            $where[] = array('maker.supplier_name', 'LIKE', '%'.$params['maker_name'].'%');
            $where[] = array('maker.supplier_maker_kbn', '!=', '1');
        }
        if (!empty($params['supplier_name'])) {
            $where[] = array('supplier.supplier_name', 'LIKE', '%'.$params['supplier_name'].'%');
            $where[] = array('supplier.supplier_maker_kbn', '=', '1');
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('staff.staff_name', 'LIKE', '%'.$params['staff_name'].'%');
        }
        if (!empty($params['qr_code'])) {
            $where[] = array('qr.qr_code', 'LIKE', '%'.$params['qr_code'].'%');
        }
        if (!empty($params['product_code'])) {
            $where[] = array('product.product_code', 'LIKE', '%'.$params['product_code'].'%');
        }        
        if (!empty($params['from_order_date'])) {
            $where[] = array('o.order_datetime', '>=', $params['from_order_date']);
        }
        if (!empty($params['to_order_date'])) {
            $where[] = array('o.order_datetime', '<=', $params['to_order_date']);
        }
        if (!empty($params['from_arrival_date'])) {
            $where[] = array('od.arrival_plan_date', '>=', $params['from_arrival_date']);
        }
        if (!empty($params['to_arrival_date'])) {
            $where[] = array('od.arrival_plan_date', '<=', $params['to_arrival_date']);
        }
        if (!empty($params['from_shipping_date'])) {
            $where[] = array('shipment.delivery_date', '>=', $params['from_shipping_date']);
        }
        if (!empty($params['to_shipping_date'])) {
            $where[] = array('shipment.delivery_date', '<=', $params['to_shipping_date']);
        }

        $minAbsShipmentQuery = $this
                ->from('t_shipment_detail As sd')
                ->leftJoin('t_shipment As s', 's.id', '=', 'sd.shipment_id')
                ->selectRaw('
                    MIN(sd.id) As shipment_detail_id,
                    sd.order_detail_id,
                    MIN(abs(datediff(s.delivery_date, curdate()))) As date_abs
                ')
                ->groupBy('sd.order_detail_id')
                ;

        $shipmentQuery = $this
                ->from('t_shipment_detail As sd')
                ->join(DB::raw('('. $minAbsShipmentQuery->toSql() .') as sub_sd'), 'sd.id', '=', 'sub_sd.shipment_detail_id')
                ->selectRaw('
                    sd.*
                ')
                ;

        $query = $this
                ->from('t_order_detail AS od')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('t_matter as matter', 'matter.matter_no', '=', 'o.matter_no')
                ->leftJoin('m_customer as customer', 'matter.customer_id', '=', 'customer.id')
                ->leftJoin('t_arrival as arrival', function($join) {
                    $join
                        ->on('arrival.order_detail_id', '=', 'od.id')
                        ->where('arrival.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftjoin(DB::raw('('. $shipmentQuery->toSql() .') as sd'), 'sd.order_detail_id', '=', 'od.id')
                // ->leftJoin('t_shipment_detail AS sd', 'sd.order_detail_id', '=', 'od.id')
                ->leftJoin('t_shipment as shipment', 'shipment.id', '=', 'sd.shipment_id')
                ->leftJoin('t_qr_detail as qr_detail', 'qr_detail.id', '=', 'arrival.qr_detail')
                ->leftJoin('t_qr as qr', 'qr.id', '=', 'qr_detail.qr_id')
                ->leftJoin('m_product as product', 'product.id', '=', 'od.product_id')
                ->leftJoin('m_supplier as supplier', 'supplier.id', '=', 'od.supplier_id')
                ->leftJoin('m_supplier as maker', 'maker.id', '=', 'od.maker_id')
                ->leftJoin('m_department as department', 'department.id', '=', 'matter.department_id')
                ->leftJoin('m_staff as staff', 'staff.id', '=', 'matter.staff_id')
                ->leftJoin('m_warehouse as warehouse', 'warehouse.id', '=', 'o.delivery_address_id')
                ->leftJoin('m_general as sup_gene', function($join) {
                    $join->on('sup_gene.value_code', '=', 'supplier.juridical_code')
                        ->where('sup_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ;

        if (!empty($params['product_name'])) {
            $query->leftJoin('m_product_nickname as nickname', 'product.id', '=', 'nickname.product_id')
                  ->where('nickname.another_name', 'LIKE', '%'.$params['product_name'].'%')
                  ->orWhere('product.product_name', 'LIKE', '%'.$params['product_name'].'%')
                  ;
            // $where[] = array('product.product_name', 'LIKE', '%'.$params['product_name'].'%');
        }

        $qrQuery = $this
                ->from('t_qr')
                ->leftJoin('t_qr_detail as qr_detail', 'qr_detail.qr_id', '=', 't_qr.id')
                ->selectRaw('
                    t_qr.qr_code,
                    COUNT(qr_detail.id) as cnt
                ')
                ->groupBy('t_qr.qr_code')
                ;
        
        $loadingQuery = $this
                ->from('t_loading as load')
                ->selectRaw('
                    load.order_detail_id,
                    COUNT(load.id) as cnt
                ')
                ->whereRaw('
                    load.order_detail_id <> 0
                    AND load.del_flg = '.config('const.flg.off').'
                ')
                ->groupBy('load.order_detail_id')
                ;

        $purchaseQuery = $this
                ->from('t_purchase_line_item as pl')
                ->selectRaw('
                    pl.arrival_id,
                    COUNT(pl.id) as cnt
                ')
                ->whereRaw('
                    pl.del_flg = '.config('const.flg.off').'
                ')
                ->groupBy('pl.arrival_id')
                ;

        // データ取得
        $data = $query
                ->leftJoin(DB::raw('('. $qrQuery->toSql() .') as sub_qr'), 
                    [['sub_qr.qr_code', '=', 'qr.qr_code']]
                )
                ->leftJoin(DB::raw('('. $loadingQuery->toSql() .') as sub_loading'), 
                    [['sub_loading.order_detail_id', '=', 'od.id']]
                )
                ->leftJoin(DB::raw('('. $purchaseQuery->toSql() .') as sub_purchase'), 
                    [['sub_purchase.arrival_id', '=', 'arrival.id']]
                )
                ->selectRaw('
                        o.id as order_id,
                        od.id as id,
                        arrival.id as arrival_id,
                        o.own_stock_flg,
                        CASE
                            WHEN sub_qr.qr_code IS NULL
                                THEN \'0\'
                            ELSE sub_qr.cnt
                        END AS qr_cnt,
                        CASE
                            WHEN sub_loading.order_detail_id IS NULL
                                THEN \'0\'
                            ELSE sub_loading.cnt
                        END AS load_cnt,
                        CASE
                            WHEN sub_purchase.arrival_id IS NULL
                                THEN \'0\'
                            ELSE sub_purchase.cnt
                        END AS purchase_cnt,
                        DATE_FORMAT(od.arrival_plan_date, "%Y/%m/%d") as arrival_plan_date,
                        DATE_FORMAT(arrival.arrival_date, "%Y/%m/%d") as arrival_date,
                        DATE_FORMAT(shipment.delivery_date, "%Y/%m/%d") as delivery_date,
                        matter.id as matter_id,
                        matter.matter_name,
                        customer.id as customer_id,
                        o.order_no,
                        product.id as product_id,
                        product.product_code,
                        product.product_name,
                        qr.qr_code,
                        product.model,
                        warehouse.id as warehouse_id,
                        warehouse.warehouse_name,
                        od.order_quantity,
                        od.stock_quantity,
                        CASE
                            WHEN arrival.quantity IS NULL
                                THEN \'0\'
                            ELSE arrival.quantity
                        END AS arrival_quantity,
                        CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\'),\'／\', COALESCE(sup_gene.value_text_2, \'\'), COALESCE(supplier.supplier_name, \'\'), COALESCE(sup_gene.value_text_3, \'\')) as supplier_name,
                        CASE
                            WHEN od.id IS NULL
                                THEN \''. config('const.arrivalListStatus.status.not_arrival'). '\' 
                            WHEN od.arrival_quantity = '. config('const.arrivalStatus.val.not_arrival'). '
                                THEN \''. config('const.arrivalListStatus.status.not_arrival'). '\'
                            WHEN od.arrival_quantity >= od.stock_quantity'.'
                                THEN \''. config('const.arrivalListStatus.status.done_arrival'). '\'
                            ELSE \''. config('const.arrivalListStatus.status.part_arrival'). '\'
                        END AS arrival_status
                ')
                ->where($where)
                ->orderBy('od.id', 'desc')
                ->orderBy('arrival.id', 'desc')
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getListById($params) {
        // Where句作成
        $where = [];
        $where[] = array('t_arrival.del_flg', '=', config('const.flg.off'));
        if (!empty($params['arrival_id'])) {
            $where[] = array('id', '=', $params['arrival_id']);
        }
        
        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('id', 'asc')
            ->get()
            ;

        return $data;
    }

    /**
     * 最新ID取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getLastId($params) {
        // Where句作成
        $where = [];
        $where[] = array('t_arrival.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->where($where)
                ->select(
                    DB::raw('max(t_arrival.id) as arrival_id')
                )
                ->get()
                ;

        return $data[0];
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

        if(isset(Auth::user()->id)){
            $user_id = Auth::user()->id;
        }else{
            $user_id = 0;
        }

        if(!array_key_exists('qr_prinf_flg',$params)){
            $params['qr_prinf_flg'] = 0;
        }
        if(!array_key_exists('qr_detail',$params)){
            $params['qr_detail'] = null;
        }
        if(!array_key_exists('arrival_date',$params)){
            $params['arrival_date'] = Carbon::now();
        }
        try{
            $result = $this->insertGetId([
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'to_warehouse_id' => $params['to_warehouse_id'],
                'to_shelf_number_id' => $params['to_shelf_number_id'],
                'quantity' => $params['quantity'],
                'arrival_date' => $params['arrival_date'],
                'order_id' => $params['order_id'],
                'order_no' => $params['order_no'],
                'order_detail_id' => $params['order_detail_id'],
                'reserve_id' => $params['reserve_id'],
                'qr_prinf_flg' => $params['qr_prinf_flg'],
                'qr_detail' => $params['qr_detail'],
                'del_flg' => config('const.flg.off'),
                'created_user' => $user_id,
                'created_at' => Carbon::now(),
                'update_user' => $user_id,
                'update_at' => Carbon::now(),
            ]);

            return $result;
        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'product_id' => $params['product_id'],
                        'product_code' => $params['product_code'],
                        'to_warehouse_id' => $params['to_warehouse_id'],
                        'to_shelf_number_id' => $params['to_shelf_number_id'],
                        'quantity' => $params['quantity'],
                        'arrival_date' => Carbon::now(),
                        'order_id' => $params['order_id'],
                        'order_no' => $params['order_no'],
                        'order_detail_id' => $params['order_detail_id'],
                        'reserve_id' => $params['reserve_id'],
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
    public function deleteById($id)
    {
        $result = false;
        try{
            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param $id
     * @return void
     */
    public function physicalDeleteById($id)
    {
        $result = false;
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->delete()
                ;
            $result = ($updateCnt > 0);
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * IDで取得
     * @param int $id ID
     * @return type 入荷データ
     */
    public function getById($id) {
        $data = false;
        
        $data = $this
                ->where(['id' => $id])
                ->where(['del_flg' => config('const.flg.off')])
                ->get()
                ;

        return $data;
    }

    /**
     * 倉庫、商品ごとに最終入荷日取得（１件）
     *
     * @param $warehouseId　倉庫ID
     * @param $productId    商品ID
     * @return void
     */
    public function getLastArrivalDate($warehouseId, $productId) 
    {
        // Where句作成
        $where = [];
        $where[] = array('arrival.to_warehouse_id', '=', $warehouseId);
        $where[] = array('arrival.product_id', '=', $productId);

        $data = $this
                ->from('t_arrival as arrival')
                ->selectRaw('MAX(DATE_FORMAT(arrival.arrival_date, "%Y/%m/%d")) as arrival_date')
                ->where($where)
                ->orderBy('arrival.arrival_date', 'desc')
                ->first()
                ;

        return $data;
    }

    /**
     * 倉庫・商品ごとの入荷予定数取得
     *
     * @param $warehouseId 倉庫ID
     * @param $productId   商品ID
     * @return void
     */
    public function getArrivalQuantity($warehouseId, $productId)
    {
        // Where句作成
        $where = [];
        // $where[] = array('arrival.to_warehouse_id', '=', $warehouseId);
        // $where[] = array('arrival.product_id', '=', $productId);

        // $data = $this
        //         ->from('t_arrival as arrival')
        //         ->selectRaw('SUM(arrival.quantity) as arrival_quantity')
        //         ->where($where)
        //         ->first()
        //         ;
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.product_id', '=', $productId);
        $where[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        $where[] = array('o.delivery_address_id', '=', $warehouseId);
        $where[] = array('od.order_quantity', '<>', 0);
        $where[] = array('o.status', '=', config('const.orderStatus.val.ordered'));

        $data = $this
                ->from('t_order_detail AS od')
                ->where($where)
                ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
                ->selectRaw('
                    o.delivery_address_id AS warehouse_id,
                    od.product_id,
                    SUM(od.stock_quantity - od.arrival_quantity) AS arrival_quantity
                ')
                ->groupBy('od.product_id', 'o.delivery_address_id')
                ->first()
                ;

        return $data;
    }

    /**
     * 入荷データから仕入データ取得
     *
     * @param [type] $params
     * @return void
     */
    public function getPurchaseData($params)
    {
        $where = [];
        $whereBetween = null;
        $where[] = array('ar.del_flg', '=', config('const.flg.off'));
        $where[] = array('ar.arrival_date', '>=', config('const.purchaseArrivalValidDate'));
        // $where[] = array('ar.id', '<>', 'pl.arrival_id');

        if (!empty($params['supplier_id'])) {
            $where[] = array('od.supplier_id', '=', $params['supplier_id']);
        }
        if (!empty($params['maker_id'])) {
            $where[] = array('od.maker_id', '=', $params['maker_id']);
        }
        if (!empty($params['order_no'])) {
            $where[] = array('o.order_no', '=', $params['order_no']);
        }
        if (!empty($params['order_staff_id'])) {
            $where[] = array('o.order_staff_id', '=', $params['order_staff_id']);
        }
        // if (!empty($params['from_arrival_date']) || !empty($params['to_arrival_date'])) {
        //     if (!empty($params['from_arrival_date']) && !empty($params['to_arrival_date'])) {
        //         // FROM-TOに入力があった場合
        //         $whereBetween = array($params['from_arrival_date'], $params['to_arrival_date']);
        //     } else if (!empty($params['from_arrival_date'])) {
        //         // FROMのみ
        //         $where[] = array('ar.arrival_date', '>=', $params['from_arrival_date']);
        //     } else if (!empty($params['to_arrival_date'])) {
        //         // TOのみ
        //         $where[] = array('ar.arrival_date', '<=', $params['to_arrival_date']);
        //     }
        // }
        if (!empty($params['department_id'])) {
            $where[] = array('m.department_id', '=', $params['department_id']);
        }
        if (!empty($params['staff_id'])) {
            $where[] = array('m.staff_id', '=', $params['staff_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('m.customer_id', '=', $params['customer_id']);
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('m.matter_no', '=', $params['matter_no']);
        }
        if (!empty($params['class_big_id'])) {
            $where[] = array('qd.class_big_id', '=', $params['class_big_id']);
        }
        if (!empty($params['construction_id'])) {
            $where[] = array('qd.construction_id', '=', $params['construction_id']);
        }
        if (!empty($params['product_id'])) {
            $where[] = array('ar.product_id', '=', $params['product_id']);
        }
        if (!empty($params['payment_no'])) {
            $where[] = array('pay.payment_no', '=', $params['payment_no']);
        }
        if (!empty($params['vendors_request_no'])) {
            $where[] = array('pl.vendors_request_no', '=', $params['vendors_request_no']);
        }
        
        // 売上取得
        $subWhere = [];
        $subWhere[] = array('s.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('sd.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('qd.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('s.supplier_id', '=', $params['supplier_id']);

        $salesSubQuery = $this
                ->from('t_sales As s')
                ->join('t_sales_detail As sd', 's.id', '=', 'sd.sales_id')
                ->leftJoin('t_quote_detail As qd', 's.quote_detail_id', '=', 'qd.id')
                ->select([
                    's.quote_detail_id',
                    DB::raw('MAX(s.id) As sales_max_id'),
                    DB::raw('MAX(sd.sales_date) As sales_date')
                ])
                ->where($subWhere)
                ->groupBy('s.quote_detail_id')
                ;

        $salesQuery = $this
                ->from('t_sales As s')
                ->join('t_sales_detail As sd', 's.id', '=', 'sd.sales_id')
                ->leftJoin('t_quote_detail As qd', 's.quote_detail_id', '=', 'qd.id')
                ->join(DB::raw('(' .$salesSubQuery->toSql() . ') as sub_query'), function ($join) {
                    // 直近の売上
                    $join
                        ->on('s.quote_detail_id', '=', 'sub_query.quote_detail_id')
                        ->on('sub_query.sales_max_id', '=', 's.id');
                })
                ->mergeBindings($salesSubQuery->getQuery())
                ->where($subWhere)
                ->select([
                    's.quote_detail_id',
                    DB::raw('1 As sales_recording'),
                    DB::raw('s.sales_unit_price As sales_unit_price'),
                    DB::raw('sub_query.sales_date As sales_date')
                ])
                ;
        
        $data = $this
                ->from('t_arrival AS ar')
                ->leftJoin('t_purchase_line_item AS pl', function ($join) {
                    $join
                        ->on('pl.arrival_id', '=', 'ar.id')
                        ->where('pl.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_payment AS pay', 'pl.payment_id', '=', 'pay.id')
                ->leftJoin('t_order_detail AS od', 'od.id', '=', 'ar.order_detail_id')
                ->leftJoin('t_order AS o', 'o.id', '=', 'ar.order_id')
                ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
                ->leftJoin('t_quote_detail AS qd', 'qd.id', '=', 'od.quote_detail_id')
                // ->leftJoin('t_sales_detail AS sd', 'sd.quote_detail_id', '=', 'qd.id')
                // ->leftJoin('t_request AS req', 'req.id', '=', 'sd.request_id')
                ->leftJoin('m_customer AS cus', 'cus.id', '=', 'm.customer_id')
                ->leftJoin('m_department AS dep', 'dep.id', '=', 'm.department_id')
                ->leftJoin(DB::raw('(' .$salesQuery->toSql() . ') as sales_query'), function ($join) {
                    $join->on('sales_query.quote_detail_id', '=', 'od.quote_detail_id');
                })
                ->mergeBindings($salesQuery->getQuery())
                ->where($where)
                ->where(function ($query) use ($params) {
                    if (!empty($params['from_arrival_date']) || !empty($params['to_arrival_date'])) {
                        if (!empty($params['from_arrival_date']) && !empty($params['to_arrival_date'])) {
                            // FROM-TOに入力があった場合
                            $query->whereBetween('ar.arrival_date', array($params['from_arrival_date'], $params['to_arrival_date']));
                        } else if (!empty($params['from_arrival_date'])) {
                            // FROMのみ
                            $query->where('ar.arrival_date', '>=', $params['from_arrival_date']);
                        } else if (!empty($params['to_arrival_date'])) {
                            // TOのみ
                            $query->where('ar.arrival_date', '<=', $params['to_arrival_date']);
                        }
                    }
                })
                ->whereRaw('pl.arrival_id IS NULL')
                // \''.config('const.flg.off').'\' AS status,
                ->selectRaw('
                    pl.id AS id,
                    pl.payment_id,
                    pay.status,
                    ar.id AS arrival_id,
                    DATE_FORMAT(pl.fixed_date, "%Y/%m/%d") As fixed_date,
                    DATE_FORMAT(sales_query.sales_date, "%Y/%m/%d") As sales_date,
                    COALESCE(sales_query.sales_recording, 0) as sales_recording,
                    COALESCE(sales_query.sales_unit_price, 0) As sales_unit_price,
                    COALESCE(sales_query.sales_unit_price * ar.quantity, 0) As sales_total,
                    \''.config('const.flg.off').'\' AS purchase_status,
                    DATE_FORMAT(ar.arrival_date, "%Y/%m/%d") AS arrival_date,
                    od.product_id,
                    od.product_name,
                    od.product_code,
                    od.model,
                    od.maker_id,
                    od.maker_name,
                    od.supplier_id,
                    od.supplier_name,
                    cus.customer_name,
                    m.owner_name,
                    o.account_customer_name,
                    o.account_owner_name,
                    od.order_no,
                    ar.quantity AS quantity,
                    od.order_quantity AS order_quantity,
                    od.min_quantity AS min_quantity,
                    od.stock_quantity AS stock_quantity,
                    od.unit,
                    od.regular_price,
                    qd.cost_kbn,
                    od.cost_unit_price AS cost_unit_price,
                    od.cost_makeup_rate AS cost_makeup_rate,
                    od.id AS order_detail_id,
                    o.id AS order_id,
                    pl.return_id,
                    od.product_id AS product_id,
                    m.customer_id,
                    m.id AS matter_id,
                    m.matter_no AS matter_no,
                    m.department_id,
                    m.staff_id,
                    dep.department_name
                ')
                ->distinct()
                ->get()
                ;
        
        return $data;
    }

    /**
     * 発注IDで取得
     * @param array $id ID
     * @return type 入荷データ
     */
    public function getByOrderIds($orderIds) {
        $data = $this
                ->where('del_flg', config('const.flg.off'))
                ->whereIn('order_id', $orderIds)
                ->get()
                ;

        return $data;
    }

     /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateQr($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'qr_detail' => $params['qr_detail'],
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
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateQrPrinfFlg($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'qr_prinf_flg' => $params['qr_prinf_flg'],
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
     * 全商品の入荷予定数取得
     *
     * @return void
     */
    public function getAllProductArrivalQuantity()
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        $where[] = array('od.arrival_quantity', '=', 0);

        $now = Carbon::now()->format('Y-m-d');
        $nextArrivalQuery = $this
                ->from('t_order_detail as order_detail')
                ->leftJoin('t_order as o', 'order_detail.order_id', '=', 'o.id')
                ->selectRaw('
                        o.delivery_address_id AS warehouse_id,
                        order_detail.product_id,
                        MIN(DATE_FORMAT(order_detail.arrival_plan_date, "%Y/%m/%d")) AS next_arrival_date
                ')
                ->whereRaw('
                    o.delivery_address_kbn =\''. config('const.deliveryAddressKbn.val.company'). '\'
                    AND order_detail.arrival_plan_date >= \''. $now .'\' 
                ')
                ->groupBy('order_detail.product_id', 'o.delivery_address_id')
                ;

        $data = $this
                ->from('t_order_detail AS od')
                ->where($where)
                ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
                ->leftJoin(DB::raw('('. $nextArrivalQuery->toSql() .') as next_arrival'), [['next_arrival.warehouse_id', '=', 'o.delivery_address_id'],['next_arrival.product_id', '=', 'od.product_id']])
                // ->mergeBindings($nextArrivalQuery)    
                ->selectRaw('
                    o.delivery_address_id AS warehouse_id,
                    od.product_id,
                    SUM(od.stock_quantity) AS arrival_quantity,
                    MIN(next_arrival.next_arrival_date) as next_arrival_date
                ')
                ->groupBy('od.product_id', 'o.delivery_address_id')
                ->get()
                ;

        return $data;
    }

    /**
     * 入荷予定数、予定日を取得
     *
     * @param [type] $matterNo
     * @return void
     */
    public function getArrivalDataByWarehouseAndProduct($matterNo = null)
    {
        $where = [];
        // $where[] = array('od.product_id', '=', $productId);
        $where[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        // $where[] = array('o.delivery_address_id', '=', $warehouseId);
        $where[] = array('o.status', '=', config('const.orderStatus.val.ordered'));
        $where[] = array('od.order_quantity', '<>', 0);
        $where[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));

        // 入荷予定日取得
        $nextDateQuery = DB::table('t_order_detail AS od')
                ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
                ->where($where)
                ->when(!empty($matterNo), function($query) use($matterNo) {
                    $query
                        ->where('o.matter_no', '=', $matterNo)
                        ->orWhere('o.own_stock_flg', config('const.flg.on'))
                        ;
                })
                ->selectRaw('
                    od.product_id,
                    o.delivery_address_id,
                    DATE_FORMAT(MAX(od.arrival_plan_date), "%Y/%m/%d") as next_arrival_date
                ')
                ->groupBy('od.product_id', 'o.delivery_address_id')
                ;

        // 発注数取得
        $orderArrivalQuery = $this
                ->from('t_order_detail AS od')
                ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
                ->leftJoin(DB::raw('('. $nextDateQuery->toSql() .') as next_date'), 
                    [
                        ['next_date.delivery_address_id', '=', 'o.delivery_address_id'],
                        ['next_date.product_id', '=', 'od.product_id']
                    ]
                )
                ->mergeBindings($nextDateQuery)
                ->where($where)
                ->where('o.own_stock_flg', config('const.flg.off'))
                ->when(!empty($matterNo), function($query) use($matterNo){
                    $query
                        ->where('o.matter_no', $matterNo)
                        ;
                })
                ->selectRaw('
                    od.product_id,
                    o.delivery_address_id,
                    o.own_stock_flg,
                    MAX(next_date.next_arrival_date) as next_arrival_date,
                    SUM(od.stock_quantity - od.arrival_quantity) as arrival_quantity
                ')
                ->groupBy('od.product_id', 'o.delivery_address_id', 'o.own_stock_flg')
                // ->get()
                ;
        
        // 在庫品発注数取得
        $mainQuery = $this
                ->from('t_order_detail AS od')
                ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
                ->leftJoin(DB::raw('('. $nextDateQuery->toSql() .') as next_date'), 
                    [
                        ['next_date.delivery_address_id', '=', 'o.delivery_address_id'],
                        ['next_date.product_id', '=', 'od.product_id']
                    ]
                )
                ->mergeBindings($nextDateQuery)
                ->where($where)
                ->where('o.own_stock_flg', config('const.flg.on'))
                ->selectRaw('
                    od.product_id,
                    o.delivery_address_id,
                    o.own_stock_flg,
                    MAX(next_date.next_arrival_date) as next_arrival_date,
                    SUM(od.stock_quantity - od.arrival_quantity) as arrival_quantity
                ')
                ->groupBy('od.product_id', 'o.delivery_address_id', 'o.own_stock_flg')
                // ->toSql()
                // ->get()
                ;

        $data = $mainQuery->union($orderArrivalQuery)->get();
        return $data;
    }
}