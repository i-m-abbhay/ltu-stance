<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Models\NumberManage;
use App\Models\Customer;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;
use App\Libs\Common;


 /**
 * 引当
 */
class Reserve extends Model
{
    // テーブル名
    protected $table = 't_reserve';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'stock_flg',
        'matter_id',
        'order_id',
        'order_no',
        'order_detail_id',
        'quote_id',
        'quote_detail_id',
        'product_id',
        'product_code',
        'from_warehouse_id',
        'from_shelf_number_id',
        'reserve_limit_date',
        'reserve_quantity',
        'reserve_quantity_validity',
        'allocation_date',
        'reserve_date',
        'issue_quantity',
        'finish_flg',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    /**
     * 出荷指示のデータ取得
     * 
     * @param $matterId 案件ID
     * @param $arrivalPlanDateFrom 入荷予定日from
     * @param $arrivalPlanDateTo 入荷予定日to
     * @param $hopeArrivalPlanDateFrom 希望納品予定日from
     * @param $hopeArrivalPlanDateTo 希望納品予定日to
     */
    public function getReserveInsutructionList($matterId, $arrivalPlanDateFrom, $arrivalPlanDateTo, $hopeArrivalPlanDateFrom, $hopeArrivalPlanDateTo){
        $where = [];
        $hasArrivalPlanDate = false;

        $where[] = array($this->table.'.matter_id', '=', $matterId);
        $where[] = array($this->table.'.del_flg', '=', config('const.flg.off'));
        // $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        // $where[] = array('t_order_detail.del_flg', '=', config('const.flg.off'));
        // $where[] = array('m_customer.del_flg', '=', config('const.flg.off'));
        // $where[] = array('m_address.del_flg', '=', config('const.flg.off'));
        // $where[] = array('m_warehouse.del_flg', '=', config('const.flg.off'));
        // $where[] = array('t_quote.del_flg', '=', config('const.flg.off'));
        // $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        // $where[] = array('t_quote_layer.del_flg', '=', config('const.flg.off'));

        /* 出荷予定日(旧：希望出荷予定日)は同じ見積明細IDを持つ引当は全て同じ出荷予定日が入っている */
        if(!empty($hopeArrivalPlanDateFrom)){
            $where[] = array($this->table.'.reserve_date', '>=', $hopeArrivalPlanDateFrom.' 00:00:00');
        }
        if(!empty($hopeArrivalPlanDateTo)){
            $where[] = array($this->table.'.reserve_date', '<=', $hopeArrivalPlanDateTo.' 23:59:59');
        }

        /* 入荷予定日の絞り込み 見積もり明細を絞る */
        $searchArrivalPlanDateQuery = $this
                ->join('t_order_detail', function ($join) {
                    $join->on($this->table.'.order_detail_id', 't_order_detail.id');
                })
                ->select([
                    $this->table.'.quote_detail_id as quote_detail_id',
                    $this->table.'.from_warehouse_id as from_warehouse_id',
                ])
                ->distinct()
                ->whereRaw($this->table.'.matter_id = '. $matterId)
                ->whereRaw($this->table.'.del_flg = '. config('const.flg.off'))
                ->whereRaw('t_order_detail.del_flg = '. config('const.flg.off'))
                ;

        if(!empty($arrivalPlanDateFrom)){
            $hasArrivalPlanDate = true;
            $searchArrivalPlanDateQuery
                ->whereRaw('t_order_detail.arrival_plan_date >= "'. $arrivalPlanDateFrom.' 00:00:00"');
        }
        if(!empty($arrivalPlanDateTo)){
            $hasArrivalPlanDate = true;
            $searchArrivalPlanDateQuery
                ->whereRaw('t_order_detail.arrival_plan_date <= "'. $arrivalPlanDateTo.' 23:59:59"');
        }
        /* 入荷予定日の絞り込み 見積もり明細を絞る 終了 */
        
        

        // 引当ごとの出荷指示数取得
        $sumShipmentQuantity  = $this
                ->join('t_shipment_detail', function ($join) {
                    $join->on($this->table.'.id', 't_shipment_detail.reserve_id');
                    $join->on($this->table.'.product_id', 't_shipment_detail.product_id');
                })
                ->select([
                    $this->table.'.id as reserve_id',
                ])
                ->selectRaw(
                    'SUM(t_shipment_detail.shipment_quantity - '.$this->table. '.issue_quantity) as sum_shipment_quantity'
                )
                ->whereRaw($this->table.'.matter_id = '.$matterId)
                ->whereRaw('t_shipment_detail.del_flg = "'. config('const.flg.off') .'"')
                ->groupBy($this->table.'.id')
                ->toSql()
                ;

        // 返品数取得
        $returnQuery = DB::table('t_warehouse_move')
                ->join('t_shipment_detail', 't_warehouse_move.shipment_detail_id', 't_shipment_detail.id')
                ->join('t_shipment', 't_shipment_detail.shipment_id', 't_shipment.id')
                ->join('m_product', 't_shipment_detail.product_id', 'm_product.id')
                ->select([
                    't_warehouse_move.quote_detail_id',
                    't_shipment.from_warehouse_id',
                    DB::raw('SUM(t_warehouse_move.quantity * m_product.min_quantity) as return_quantity')
                ])
                ->whereRaw('t_warehouse_move.matter_id ="'. $matterId .'"')
                ->whereRaw('t_warehouse_move.move_kind ="'. config('const.moveKind.return') .'"')
                ->whereRaw('t_warehouse_move.approval_status ="'. config('const.approvalDetailStatus.val.approved').'"')
                // ->where('t_shipment_detail.stock_flg', '!=', config('const.stockFlg.val.order'))
                ->groupBy('t_warehouse_move.quote_detail_id','t_shipment.from_warehouse_id')
                ;

        // 引当ごとの納品数取得
        $sumDeliveryQuantity = $this
                ->join('t_shipment_detail', function ($join) {
                    $join->on($this->table.'.id', 't_shipment_detail.reserve_id');
                    $join->on($this->table.'.product_id', 't_shipment_detail.product_id');
                })
                // ->leftjoin(DB::raw('('.$returnQuery->toSql().') as return_query'), function($join){
                //     $join->on('t_reserve.quote_detail_id', 'return_query.quote_detail_id');
                //     $join->on('t_reserve.from_warehouse_id', 'return_query.from_warehouse_id');
                // })
                ->join('t_delivery', 't_shipment_detail.id', 't_delivery.shipment_detail_id')
                ->select([
                    $this->table.'.id as reserve_id',
                    // DB::raw('SUM(t_delivery.delivery_quantity - return_query.return_quantity) as sum_delivery_quantity')
                ])
                ->selectRaw('
                    SUM(t_delivery.delivery_quantity) AS sum_delivery_quantity
                ')
                //     CASE
                //     WHEN SUM(return_query.return_quantity) IS NULL
                //         THEN SUM(t_delivery.delivery_quantity)
                //     ELSE SUM(t_delivery.delivery_quantity - return_query.return_quantity)
                // END as sum_delivery_quantity
                ->whereRaw($this->table.'.matter_id = '.$matterId)
                ->whereRaw('t_shipment_detail.del_flg = "'. config('const.flg.off') .'"')
                ->whereRaw('t_delivery.del_flg = "'. config('const.flg.off') .'"')
                ->groupBy($this->table.'.id')
                ->toSql()
                ;

        // データ取得
        $data = $this
                ->leftjoin('t_matter', $this->table.'.matter_id', 't_matter.id')
                ->leftJoin('t_order_detail', function ($join) {
                    $join->on($this->table.'.order_detail_id', 't_order_detail.id');
                    $join->on($this->table.'.product_id', 't_order_detail.product_id');
                    $join->where('t_order_detail.del_flg', '=', config('const.flg.off'));
                })
                ->leftjoin('m_customer', 't_matter.customer_id', 'm_customer.id')
                ->leftJoin('m_general', function($join) {
                    $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftjoin('m_address', 't_matter.address_id', 'm_address.id')
                ->leftjoin('m_warehouse', $this->table.'.from_warehouse_id', 'm_warehouse.id')
                ->leftjoin('t_quote', $this->table.'.quote_id', 't_quote.id')
                ->leftjoin('t_quote_detail', $this->table.'.quote_detail_id', 't_quote_detail.id')
                ->leftjoin(DB::raw('('.$sumShipmentQuantity.') as t_shipment_detail'), function($join){
                    $join->on($this->table.'.id', 't_shipment_detail.reserve_id');
                })
                ->leftjoin(DB::raw('('.$sumDeliveryQuantity.') as t_delivery'), function($join){
                    $join->on($this->table.'.id', 't_delivery.reserve_id');
                })
                ->when($hasArrivalPlanDate, function($query) use($searchArrivalPlanDateQuery) {
                    // 入荷予定日の絞り込み
                    return $query->join(DB::raw('('.$searchArrivalPlanDateQuery->toSql().') as search_arrival_plan_date'), function($join){
                        $join->on($this->table.'.quote_detail_id', 'search_arrival_plan_date.quote_detail_id');
                        $join->on($this->table.'.from_warehouse_id', 'search_arrival_plan_date.from_warehouse_id');
                    });
                })
                ->leftJoin('t_matter_detail', function($join) {
                    $join
                        ->on('t_matter_detail.quote_detail_id', '=', $this->table.'.quote_detail_id')
                        ->where('t_matter_detail.type', '=', config('const.matterTaskType.val.hope_arrival_plan_date'))
                        ;
                })
                ->leftjoin(DB::raw('('.$returnQuery->toSql().') as return_query'), function($join){
                    $join->on($this->table.'.quote_detail_id', 'return_query.quote_detail_id');
                    $join->on($this->table.'.from_warehouse_id', 'return_query.from_warehouse_id');
                })
                ->where($where)
                ->select([
                    $this->table.'.id as reserve_id',
                    $this->table.'.stock_flg',
                    $this->table.'.product_id',
                    $this->table.'.product_code',
                    $this->table.'.reserve_quantity',
                    $this->table.'.reserve_quantity_validity',
                    $this->table.'.from_warehouse_id',

                    't_shipment_detail.sum_shipment_quantity',

                    't_matter.id as matter_id',
                    't_matter.customer_id',
                    't_matter.address_id',
                    't_matter.matter_name',

                    't_order_detail.id as order_detail_id',
                    't_order_detail.stock_quantity as order_stock_quantity',
                    't_order_detail.arrival_quantity',

                    //'m_customer.customer_name',

                    'm_address.address1',
                    'm_address.address2',
                    
                    'm_warehouse.warehouse_name',

                    't_quote.id as quote_id',
                    't_quote_detail.id as quote_detail_id',
                    't_quote_detail.quote_no',
                    't_quote_detail.quote_version',
                    't_quote_detail.construction_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.seq_no',
                    't_quote_detail.depth',
                    't_quote_detail.tree_path',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.product_name',
                    't_quote_detail.model',
                    't_quote_detail.stock_unit as quote_unit',
                    't_quote_detail.stock_unit',
                    't_quote_detail.stock_quantity',
                ])
                ->selectRaw(
                    'CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                    DATE_FORMAT(t_order_detail.arrival_plan_date, "%Y/%m/%d") as arrival_plan_date,
                    DATE_FORMAT(t_order_detail.hope_arrival_plan_date, "%Y/%m/%d") as hope_arrival_plan_date,
                    DATE_FORMAT(t_matter_detail.start_date, "%Y/%m/%d") as start_date,
                    COALESCE(t_delivery.sum_delivery_quantity, 0) - COALESCE(return_query.return_quantity, 0) AS sum_delivery_quantity'
                )
                ->orderBy('t_quote_detail.depth', 'asc')
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->orderBy($this->table.'.id', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 倉庫・商品ごとの引当数取得
     *
     * @param $warehouseId  倉庫ID
     * @param $productId    商品ID
     * @return void
     */
    public function getReserveQuantity($warehouseId, $productId)
    {
        // Where句作成
        $where = [];
        $where[] = array('reserve.del_flg', '=', config('const.flg.off'));
        $where[] = array('reserve.finish_flg', '=', config('const.flg.off'));
        $where[] = array('reserve.from_warehouse_id', '=', $warehouseId);
        $where[] = array('reserve.product_id', '=', $productId);
        $where[] = array('reserve.reserve_quantity_validity', '<>', 0);

        $data = $this
                ->from('t_reserve as reserve')
                ->selectRaw('SUM(reserve.reserve_quantity_validity) as reserve_quantity')
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 引当リスト取得
     *
     * @param $productId    商品ID
     * @param $warehouseId  倉庫ID
     * @return void
     */
    public function getReserveList($productId = null, $warehouseId = null) {
        // Where句作成
        $where = [];
        $where[] = array('reserve.del_flg', '=', config('const.flg.off'));
        $where[] = array('reserve.finish_flg', '=', config('const.flg.off'));
        $where[] = array('reserve.reserve_quantity_validity', '<>', 0);

        if (!empty($productId)) {
            $where[] = array('reserve.product_id', '=', $productId);
        }
        if (!empty($warehouseId)) {
            $where[] = array('reserve.from_warehouse_id', '=', $warehouseId);
        }

        $shipmentQuery = $this
                ->from('t_shipment_detail')
                ->leftJoin('t_shipment', 't_shipment.id', '=', 't_shipment_detail.shipment_id')
                ->selectRaw('
                    t_shipment_detail.reserve_id,
                    MIN(t_shipment.delivery_date) as delivery_date
                ')
                ->whereRaw('
                    t_shipment.loading_finish_flg = '. config('const.flg.off').'
                ')
                ->groupBy('t_shipment_detail.reserve_id')
                ;

        $data = $this
                ->from('t_reserve as reserve')
                ->leftJoin('t_matter as matter', 'matter.id', '=', 'reserve.matter_id')
                ->leftJoin('m_address as address', 'matter.address_id', '=', 'address.id')
                ->leftJoin(DB::raw('('. $shipmentQuery->toSql(). ') AS shipment'), 'shipment.reserve_id', '=', 'reserve.id')
                ->selectRaw('
                            reserve.id as reserve_id,
                            reserve.reserve_quantity_validity as reserve_quantity,
                            matter.matter_name as matter_name,
                            reserve.product_id,
                            reserve.from_warehouse_id,
                            CONCAT(COALESCE(address.address1, \'\'), COALESCE(address.address2, \'\')) as address,
                            DATE_FORMAT(shipment.delivery_date, "%Y/%m/%d") as delivery_date
                            ')
                ->where($where)
                ->get()
                ;

        return $data;
    }


    /**
     * 見積明細と倉庫と在庫or発注ごとの引き当て数のサマリを返す
     *
     * @param $quoteId 見積ID
     * @return void
     */
    public function getSumEachWarehouseAndDetail($quoteId, $quoteNo) {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // 在庫/預かりの返品数
        $stockReturn = DB::table('t_warehouse_move')
            ->join('t_shipment_detail', 't_warehouse_move.shipment_detail_id', 't_shipment_detail.id')
            ->join('t_shipment', 't_shipment_detail.shipment_id', 't_shipment.id')
            ->join('m_product', 't_shipment_detail.product_id', 'm_product.id')
            ->select([
                't_warehouse_move.quote_detail_id',
                't_shipment.from_warehouse_id',
                't_shipment_detail.stock_flg',
                DB::raw('SUM(t_warehouse_move.quantity * m_product.min_quantity) as return_quantity')
            ])
            ->where('t_warehouse_move.move_kind', config('const.moveKind.return'))
            ->where('t_warehouse_move.approval_status', config('const.approvalDetailStatus.val.approved'))
            ->where('t_shipment_detail.stock_flg', '!=', config('const.stockFlg.val.order'))
            ->groupBy('t_warehouse_move.quote_detail_id','t_shipment.from_warehouse_id','t_shipment_detail.stock_flg');

        // 発注の返品数
        $orderReturn = DB::table('t_warehouse_move')
            ->join('t_shipment_detail', 't_warehouse_move.shipment_detail_id', 't_shipment_detail.id')
            ->join('m_product', 't_shipment_detail.product_id', 'm_product.id')
            ->select([
                't_warehouse_move.quote_detail_id',
                DB::raw('SUM(t_warehouse_move.quantity * m_product.min_quantity) as return_quantity')
                
            ])
            ->where('t_warehouse_move.move_kind', config('const.moveKind.return'))
            ->where('t_warehouse_move.approval_status', config('const.approvalDetailStatus.val.approved'))
            ->where('t_shipment_detail.stock_flg', config('const.stockFlg.val.order'))
            ->groupBy('t_warehouse_move.quote_detail_id');


        // 在庫/預かりの引当数
        $query1 = $this
            ->join('m_warehouse', 't_reserve.from_warehouse_id', 'm_warehouse.id')
            ->leftjoin('m_product as m_product', 't_reserve.product_id', '=', 'm_product.id')
            ->leftjoin(DB::raw('('.$stockReturn->toSql().') as stock_return'), function($join){
                $join->on('t_reserve.quote_detail_id', 'stock_return.quote_detail_id');
                $join->on('t_reserve.from_warehouse_id', 'stock_return.from_warehouse_id');
                $join->on('t_reserve.stock_flg', 'stock_return.stock_flg');
            })->mergeBindings($stockReturn)
            ->select([
                't_reserve.quote_detail_id',
                't_reserve.from_warehouse_id',
                'm_warehouse.warehouse_name',
                't_reserve.stock_flg',
                'stock_return.return_quantity',
                DB::raw('SUM(t_reserve.reserve_quantity * m_product.min_quantity) AS sum_warehouse_min_reserve_quantity')
            ])
            ->whereRaw('t_reserve.quote_id = '.$quoteId)
            ->whereRaw('t_reserve.del_flg = '. config('const.flg.off') )
            ->whereRaw('t_reserve.stock_flg != '. config('const.stockFlg.val.order') )
            ->groupBy('t_reserve.quote_detail_id','t_reserve.from_warehouse_id','m_warehouse.warehouse_name','t_reserve.stock_flg','stock_return.return_quantity');
        
        // 発注数
        $query2 = DB::table('t_order')
            ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
            ->leftjoin(DB::raw('('.$orderReturn->toSql().') as order_return'), function($join){
                $join->on('t_order_detail.quote_detail_id', 'order_return.quote_detail_id');
            })->mergeBindings($orderReturn)
            ->select([
                't_order_detail.quote_detail_id',
                DB::raw('0 AS from_warehouse_id'),
                DB::raw("' ' AS warehouse_name"),
                DB::raw(config('const.stockFlg.val.order').' AS stock_flg'),
                'order_return.return_quantity',
                DB::raw('SUM(t_order_detail.order_quantity) AS sum_warehouse_min_reserve_quantity'),
            ])
            ->where('t_order.quote_no', $quoteNo)
            ->where('t_order.status', '!=', config('const.orderStatus.val.not_ordering'))
            ->where('t_order.status', '!=', config('const.orderStatus.val.sendback'))
            ->where('t_order.del_flg', config('const.flg.off') )
            ->where('t_order_detail.del_flg', config('const.flg.off'))
            ->groupBy('t_order_detail.quote_detail_id', 'order_return.return_quantity');

        $mainQuery = $query1->union($query2);
        $data = $mainQuery
                ->get()
                ;

        return $data;
    }

    /**
     * 保存
     *
     * @param  $params
     * @param  $stockFlg   0: 発注品 1: 在庫品 2: 預かり品
     * @return True: OK False: NG
     */
    public function add($params, $stockFlg)
    {
        $result = false;

        try{
            $result = $this->insertGetId([
                'stock_flg' => $stockFlg,
                'matter_id' => $params['matter_id'],
                'quote_id' => $params['quote_id'],
                'quote_detail_id' => $params['quote_detail_id'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'from_warehouse_id' => $params['from_warehouse_id'],
                'reserve_quantity' => $params['reserve_quantity'],
                'allocation_date' => $params['allocation_date'],
                'reserve_quantity_validity' => $params['reserve_quantity_validity'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);

            return $result;
        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 保存（複数）
     *
     * @param [type] $paramsList
     * @return void
     */
    public function addList($paramsList){
        $result = false;
        try {
            $insertData = [];
            $data = [];
            $data['del_flg'] = config('const.flg.off');
            $data['created_user'] = Auth::user()->id;
            $data['created_at'] = Carbon::now();
            $data['update_user'] = Auth::user()->id;
            $data['update_at'] = Carbon::now();
            foreach ($paramsList as $params) {
                $data['stock_flg'] = $params['stock_flg'];
                $data['matter_id'] = $params['matter_id'];
                $data['quote_id'] = $params['quote_id'];
                $data['quote_detail_id'] = $params['quote_detail_id'];
                $data['product_id'] = $params['product_id'];
                $data['from_warehouse_id'] = $params['from_warehouse_id'];
                $data['reserve_quantity'] = $params['reserve_quantity'];
                $data['reserve_quantity_validity'] = $params['reserve_quantity_validity'];
                // ↓NOT NULL制約ついてないカラム
                if(isset($params['order_id'])){ $data['order_id'] = $params['order_id']; }
                if(isset($params['order_no'])){ $data['order_no'] = $params['order_no']; }
                if(isset($params['order_detail_id'])){ $data['order_detail_id'] = $params['order_detail_id']; }
                if(isset($params['product_code'])){ $data['product_code'] = $params['product_code']; }                
                if(isset($params['from_shelf_number_id'])){ $data['from_shelf_number_id'] = $params['from_shelf_number_id']; }
                if(isset($params['reserve_limit_date'])){ $data['reserve_limit_date'] = $params['reserve_limit_date']; }
                if(isset($params['reserve_date'])){ $data['reserve_date'] = $params['reserve_date']; }
                $insertData[] = $data;
            }

            $result = $this->insert($insertData);
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
    public function updateById($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            // 更新内容
            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();


            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($items)
                    ;
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
    public function deleteById($id)
    {
        $result = false;
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));
            
            $updateCnt = $this
                ->where('id', $id)
                ->delete();
            $result = ($updateCnt > 0);
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

     /**
     * 見積明細、引当、倉庫
     *
     * @param 
     * @return void
     */
    public function getReserveByMatterId($matterId) {
        // Where句作成
        $where = [];
        $where[] = array('t_reserve.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_reserve.matter_id', '=', $matterId);

        $data = $this
            ->join('m_warehouse', 't_reserve.from_warehouse_id', 'm_warehouse.id')
            ->leftjoin('m_product as m_product', 't_reserve.product_id', '=', 'm_product.id')
            ->select([
                't_reserve.id as reserve_id',
                't_reserve.quote_detail_id',
                't_reserve.from_warehouse_id',
                'm_warehouse.warehouse_name',
                't_reserve.stock_flg as stock_flg',
                't_reserve.finish_flg as finish_flg',
            ])
            ->selectRaw('
                t_reserve.reserve_quantity as reserve_quantity,
                t_reserve.reserve_quantity_validity as reserve_quantity_validity,
                t_reserve.issue_quantity,
                t_reserve.product_id as product_id
            ')
            ->where($where)
            // ->groupBy('t_reserve.quote_detail_id','t_reserve.from_warehouse_id','m_warehouse.warehouse_name','t_reserve.stock_flg')
            ->get();

        return $data;
    }

     /**
     * 出荷指示画面　子グリッド用データ取得
     *
     * @param 
     * @return void
     */
    public function getShippingInstructChildList() {
        // Where句作成
        $where = [];
        $where[] = array('t_reserve.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_shipment_detail.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->join('m_warehouse', 't_reserve.from_warehouse_id', 'm_warehouse.id')
            ->leftjoin('m_product as m_product', 't_reserve.product_id', '=', 'm_product.id')
            ->leftJoin('t_order_detail', 't_order_detail.id', '=', 't_reserve.order_detail_id')
            ->select([
                't_reserve.id as reserve_id',
                't_reserve.quote_detail_id',
                't_reserve.from_warehouse_id',
                'm_warehouse.warehouse_name',
                't_reserve.stock_flg as stock_flg',
            ])
            ->selectRaw('
                t_reserve.reserve_quantity as reserve_quantity,
                t_reserve.product_id as product_id,
                CASE
                    WHEN t_reserve.stock_flg = \''. config('const.stockFlg.val.stock') .'\'
                        THEN \'在庫品\'
                    WHEN t_reserve.stock_flg = \''. config('const.stockFlg.val.order') .'\'
                        THEN \'発注品\'
                    WHEN t_reserve.stock_flg = \''. config('const.stockFlg.val.keep') .'\'
                        THEN \'預かり品\'
                    ELSE \'\'
                END AS stock_flg_txt
            ')
            ->where($where)
            // ->groupBy('t_reserve.quote_detail_id','t_reserve.from_warehouse_id','m_warehouse.warehouse_name','t_reserve.stock_flg')
            ->get();

        return $data;
    }

    /**
     * 見積明細IDで取得
     *
     * @param array $quoteDetailIdList
     * @return 
     */
    public function getByQuoteDetailIdList($quoteDetailIdList) {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where('del_flg', config('const.flg.off'))
            ->whereIn('quote_detail_id', $quoteDetailIdList)
            ->get();

        return $data;
    }

    /**
     * 案件IDまたは見積IDで取得
     *
     * @param int $matterId 案件ID
     * @param int $quoteId 見積ID
     * @return void
     */
    public function getByMatterQuote($matterId, $quoteId) {

        $data = $this
                ->where('del_flg', '=', config('const.flg.off'))
                ->where(function($query) use ($matterId, $quoteId) {
                    $query->where('matter_id', '=', $matterId)
                          ->orWhere('quote_id', '=', $quoteId);
                })
                ->get()
                ;

        return $data;
    }

    /**
     * 発注IDで取得
     *
     * @param int $orderId 発注ID
     * @return void
     */
    public function getByOrderId($orderId) {
        $data = $this
                ->where('del_flg', '=', config('const.flg.off'))
                ->where('order_id', '=', $orderId)
                ->where('stock_flg', '=', config('const.stockFlg.val.order'))
                ->get()
                ;

        return $data;
    }

    /**
     * 発注画面で商品が変更になった場合に呼ぶ
     * 　在庫/預の引当は削除
     * 　発注の引当は商品変更
     * @param int $quoteDetailId 見積明細ID
     * @param int $productId 商品ID(変更後)
     * @param string $productCode 商品コード
     */
    function changeOrderProduct($quoteDetailId, $productId, $productCode){
        $whereDelete = [];
        $whereDelete[] = array('del_flg', '=', config('const.flg.off'));
        $whereDelete[] = array('quote_detail_id', '=', $quoteDetailId);
        $whereDelete[] = array('product_id', '!=', $productId);
        $whereDelete[] = array('stock_flg', '!=', config('const.stockFlg.val.order'));

        $whereUpdate = [];
        $whereUpdate[] = array('del_flg', '=', config('const.flg.off'));
        $whereUpdate[] = array('quote_detail_id', '=', $quoteDetailId);
        $whereUpdate[] = array('product_id', '!=', $productId);
        $whereUpdate[] = array('stock_flg', '=', config('const.stockFlg.val.order'));

        $LogUtil = new LogUtil();
        
        // 削除対象が存在する場合のみ、削除を実行する
        $deleteTargetCount = $this->where($whereDelete)->count();
        if ($deleteTargetCount > 0) {
            // ログテーブルへの書き込み
            $LogUtil->putByWhere($this->table, $whereDelete, config('const.logKbn.delete'));

            $deleteCnt = $this
                ->where($whereDelete)
                ->delete()
                ;
        }

        // 更新対象が存在する場合のみ、更新を実行する
        $updateTargetCount = $this->where($whereUpdate)->count();
        if ($updateTargetCount > 0) {
            // ログテーブルへの書き込み
            $LogUtil->putByWhere($this->table, $whereUpdate, config('const.logKbn.update'));

            $updateCnt = $this
                ->where($whereUpdate)
                ->update([
                    'product_id' => $productId,
                    'product_code' => $productCode,
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
        }
    }

    /**
     * 発注IDや発注明細IDを元に引当を削除する
     * 　それに紐づく出荷指示も削除する
     * @param int $orderId 発注ID
     * @param int $orderDetailId 発注明細ID
     */
    function deleteByOrderData($orderId=null, $orderDetailId=null){
        $ShipmentDetail = new ShipmentDetail();
        try {
            $reserveWhere = [];
            $reserveWhere[] = array('del_flg', '=', config('const.flg.off'));
            // 発注ID
            if($orderId !== null){
                $reserveWhere[] = array('order_id', '=', $orderId);
            }
            // 発注明細ID
            if($orderDetailId !== null){
                $reserveWhere[] = array('order_detail_id', '=', $orderDetailId);
            }
            $reserveWhere[] = array('stock_flg', '=', config('const.stockFlg.val.order'));

            
            $deleteDataList = $this
                ->where($reserveWhere)
                ->get()
                ;

            if (count($deleteDataList) > 0) {
                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                foreach ($deleteDataList as $deleteData) {
                    $LogUtil->putByData($deleteData, config('const.logKbn.delete'), $this->table);
                }

                $deleteCnt = $this
                    ->where($reserveWhere)
                    ->delete()
                    ;

                if($deleteCnt >= 1){
                    // 出荷指示の削除
                    foreach($deleteDataList as $record){
                        $ShipmentDetail->deleteByReserveId($record->id);
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 更新
     *
     * @param int $productId 商品ID
     * @param array $params 
     * @param bool $integrate 統合フラグ　ON = 統合(商品IDを更新)
     * @return void
     */
    public function updateByProductId($productId, $params, $integrate) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('product_id', '=', $productId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            // 更新内容
            $items = [];
            if ($integrate) {
                $items['product_id'] = $params['id'];
            }
            if (!empty($params['product_code'])) {
                $items['product_code'] = $params['product_code'];
            }

            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

            $result = true;
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * IDで取得
     * 
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->where(['id' => $id])
                ->first()
                ;

        return $data;
    }

    /**
     * 更新(出荷配送)
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateShipping($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'issue_quantity' => $params['issue_quantity'],
                        'reserve_quantity_validity' => $params['reserve_quantity_validity'],
                        'finish_flg' => $params['finish_flg'],
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
     * 更新(有効引当数)
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateReserveByOrderDetailAndWarehouseId($params) {
        $result = false;

        $where = [];
        $where[] = array('order_detail_id', '=', $params['order_detail_id']);
        $where[] = array('from_warehouse_id', '=', $params['from_warehouse_id']);

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

        try {

            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $updateCnt = $this
                    ->where($where)
                    ->update($items)
                    ;
            // $result = ($updateCnt > 0);
            // $updateCnt = $this
            //         ->where($where)
            //         ->update([
            //             'reserve_quantity_validity' => $params['reserve_quantity_validity'],
            //             'issue_quantity' => $params['issue_quantity'],
            //             'finish_flg' => $params['finish_flg'],
            //             'update_user' => Auth::user()->id,
            //             'update_at' => Carbon::now(),
            //        ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 発注情報からIDを取得
     *
     * @param 
     * @return 
     */
    public function getByOrder($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('stock_flg', '=', $params['stock_flg']);
        $where[] = array('matter_id', '=', $params['matter_id']);
        $where[] = array('order_detail_id', '=', $params['order_detail_id']);
        $where[] = array('quote_detail_id', '=', $params['quote_detail_id']);
        $where[] = array('from_warehouse_id', '=', $params['from_warehouse_id']);

        $data = $this
                ->where($where)
                ->selectRaw('id')
                ->first()
                ;

        return $data;
    }


    /**
     * 発注引当数取得
     *
     * @param [type] $quoteDetailId
     * @return void
     */
    public function getSumOrderReserveQuantity($quoteDetailId)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_detail_id', '=', $quoteDetailId);
        // $where[] = array('finish_flg', '=', config('const.flg.off'));
        $where[] = array('stock_flg', '=', config('const.stockFlg.val.order'));

        $data = $this
                ->where($where)
                ->selectRaw('
                    SUM(reserve_quantity) AS sum_reserve_quantity
                ')
                ->first()
                ;

        return $data;
    }

    /**
     * 案件で引当を一括解除する（在庫引当, 預り品）
     *
     * @param int $matterId 案件ID
     * @param int $stockFlg 在庫フラグ config('const.stockFlg.val.
     * @return void
     */
    public function cancelReserveByMatterId($matterId, $stockFlg = null)
    {
        if ($stockFlg === config('const.stockFlg.val.order')) {
            return true;
        }

        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('matter_id', '=', $matterId);

        $query = $this->where($where);
        if (is_null($stockFlg)) {
            $query->whereIn('stock_flg', [
                config('const.stockFlg.val.stock'), config('const.stockFlg.val.keep')
            ]);
        }else{
            $query->where([
                array('stock_flg', '=', $stockFlg)
            ]);
        }

        // 更新対象データ取得
        $updateData = $query->get();

        $LogUtil = new LogUtil();
        foreach ($updateData as $key => $value) {
            $LogUtil->putByData($value, config('const.logKbn.update'));
        }

        // 更新
        $updateCnt = 0;
        if (count($updateData) > 0) {
            $updateCnt = $query
                            ->update([
                                'reserve_quantity_validity' => 0,
                                'update_user' => Auth::user()->id,
                                'update_at' => Carbon::now(),
                            ]);
        }

        $result = ($updateCnt > 0);
        return $result;
    }

    /**
     * 【案件詳細専用】スケジューラ-出荷指示
     *
     * @return void
     */
    public function getSchedulerShipmentForMatterDetail($matterId)
    {
        // 出荷指示（発注引当）
        $orderReserve = $this
                            ->leftJoin('t_order_detail', function($join){
                                $join
                                    ->on('t_reserve.order_detail_id', '=', 't_order_detail.id')
                                    ->where('t_order_detail.del_flg', '=', config('const.flg.off'));
                            })
                            ->leftJoin('t_quote_detail', function($join){
                                $join
                                    ->on('t_order_detail.quote_detail_id', '=', 't_quote_detail.id')
                                    ->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
                            })
                            ->leftJoin('t_shipment_detail', function($join){
                                $join
                                    ->on('t_reserve.id', '=', 't_shipment_detail.reserve_id')
                                    ->where('t_shipment_detail.del_flg', '=', config('const.flg.off'))
                                    ->where('t_shipment_detail.delivery_finish_flg', '!=', config('const.deliveryStatus.val.part_delivery'));
                            })
                            ->where([
                                ['t_reserve.del_flg', '=', config('const.flg.off')],
                                ['t_reserve.matter_id', '=', $matterId],
                                // ['t_shipment_detail.delivery_finish_flg', '=', config('const.deliveryStatus.val.part_delivery')],
                            ])
                            ->whereNotNUll('t_order_detail.hope_arrival_plan_date')
                            ->select(
                                't_order_detail.hope_arrival_plan_date AS plan_date',
                                't_quote_detail.construction_id',
                                't_reserve.reserve_quantity',
                                DB::raw('COALESCE(t_shipment_detail.shipment_quantity, 0) AS shipment_quantity')
                                // DB::raw('SUM(t_reserve.reserve_quantity) AS sum_reserve_quantity'),
                                // DB::raw('SUM(t_shipment_detail.shipment_quantity) AS sum_shipment_quantity')
                            );
                            // ->groupBy('t_order_detail.hope_arrival_plan_date', 't_quote_detail.construction_id');

        // 出荷指示（在庫引当）
        $stockReserve = $this
                            ->leftJoin('t_quote_detail', function($join){
                                $join
                                    ->on('t_reserve.quote_detail_id', '=', 't_quote_detail.id')
                                    ->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
                            })
                            ->leftJoin('t_shipment_detail', function($join){
                                $join
                                    ->on('t_reserve.id', '=', 't_shipment_detail.reserve_id')
                                    ->where('t_shipment_detail.del_flg', '=', config('const.flg.off'))
                                    ->where('t_shipment_detail.delivery_finish_flg', '!=', config('const.deliveryStatus.val.part_delivery'));
                            })
                            ->leftJoin('t_matter_detail', 't_quote_detail.id', '=', 't_matter_detail.quote_detail_id')
                            ->where([
                                ['t_reserve.del_flg', '=', config('const.flg.off')],
                                ['t_reserve.matter_id', '=', $matterId],
                                ['t_matter_detail.type', '=', config('const.matterTaskType.val.hope_arrival_plan_date')],
                            ])
                            ->whereIn('t_reserve.stock_flg', [config('const.stockFlg.val.stock'), config('const.stockFlg.val.keep')])
                            ->select(
                                't_matter_detail.start_date AS plan_date',
                                't_quote_detail.construction_id',
                                't_reserve.reserve_quantity',
                                DB::raw('COALESCE(t_shipment_detail.shipment_quantity, 0) AS shipment_quantity')
                                // DB::raw('SUM(t_reserve.reserve_quantity) AS sum_reserve_quantity'),
                                // DB::raw('SUM(t_shipment_detail.shipment_quantity) AS sum_shipment_quantity')
                            )
                            // ->groupBy('t_matter_detail.start_date', 't_quote_detail.construction_id')
                            ->union($orderReserve);

        $table1 = $stockReserve;
        $data = DB::table(DB::raw('('. $table1->toSql(). ') AS t'))
                    ->mergeBindings($table1->getQuery())
                    ->select(
                        'plan_date',
                        'construction_id',
                        DB::raw('SUM(reserve_quantity) AS sum_reserve_quantity'),
                        DB::raw('SUM(shipment_quantity) AS sum_shipment_quantity')
                    )
                    ->groupBy('plan_date', 'construction_id')
                    ->get();
        return $data;
    }


    /**
     * 各種別の引当数取得
     *
     * @param [type] $warehouseId
     * @param [type] $productId
     * @return void
     */
    public function getReserveQuantityByWarehouseAndProduct($warehouseId, $productId)
    {
        $where = [];
        $where[] = array('t_reserve.from_warehouse_id', '=', $warehouseId);
        $where[] = array('t_reserve.product_id', '=', $productId);
        $where[] = array('t_reserve.reserve_quantity_validity', '<>', 0);

        $data = $this
                ->where($where)
                ->selectRaw('
                    t_reserve.from_warehouse_id,
                    t_reserve.product_id,
                    t_reserve.stock_flg,
                    SUM(t_reserve.reserve_quantity_validity) as reserve_quantity
                ')
                ->groupBy('t_reserve.from_warehouse_id', 't_reserve.product_id', 't_reserve.stock_flg')
                ->get()
                ;

        return $data;
    }

     /**
     * 各種別の引当数取得
     *
     * @param [type] $warehouseId
     * @param [type] $productId
     * @return void
     */
    public function getReserveQuantityGroupByWarehouseAndProduct()
    {
        $where = [];
        // $where[] = array('t_reserve.from_warehouse_id', '=', $warehouseId);
        // $where[] = array('t_reserve.product_id', '=', $productId);
        $where[] = array('t_reserve.reserve_quantity_validity', '<>', 0);

        $data = $this
                ->where($where)
                ->selectRaw('
                    t_reserve.from_warehouse_id,
                    t_reserve.product_id,
                    t_reserve.stock_flg,
                    SUM(t_reserve.reserve_quantity_validity) as reserve_quantity
                ')
                ->groupBy('t_reserve.from_warehouse_id', 't_reserve.product_id', 't_reserve.stock_flg')
                ->get()
                ;

        return $data;
    }
    

    /**
     * 引当数取得
     *
     * @param [type] $quoteDetailId
     * @param [type] $stockFlg
     * @return void
     */
    public function getSumReserveQuantityByStockFlg($quoteDetailId, $stockFlg)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_detail_id', '=', $quoteDetailId);
        // $where[] = array('finish_flg', '=', config('const.flg.off'));
        $where[] = array('stock_flg', '=', $stockFlg);

        $data = $this
                ->where($where)
                ->selectRaw('
                    SUM(reserve_quantity) AS sum_reserve_quantity
                ')
                ->first()
                ;

        return $data;
    }

    /**
     * 倉庫IDと発注明細IDで取得
     *
     * @param [type] $warehouseId
     * @param [type] $orderDetailId
     * @return void
     */
    public function getByWarehouseAndOrderDetailId($warehouseId, $orderDetailId) {
        $where = [];
        $where[] = array('from_warehouse_id', '=', $warehouseId);
        $where[] = array('order_detail_id', '=', $orderDetailId);

        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 引当から有効引当数をマイナスする
     * 有効引当数 < マイナス数なら、該当案件の別引当からマイナスする
     *
     * @param [type] $warehouseId
     * @param [type] $orderDetailId
     * @param [type] $minusQuantity
     * @return void
     */
    public function minusReserveQuantityByWarehouseAndOrder($warehouseId, $orderDetailId, $minusQuantity)
    {
        $Reserve = new Reserve();
        $reserveData = $Reserve->getByWarehouseAndOrderDetailId($warehouseId, $orderDetailId);

        if ($reserveData !== null) {
            if($reserveData['reserve_quantity_validity'] >= $minusQuantity) {
                // そのまま引当数をマイナスする
                
                // 引当レコードから有効引当数減算
                $updateData = [];
                $updateData['id'] = $reserveData['id'];
                $updateData['reserve_quantity_validity'] = Common::nullorBlankToZero($reserveData['reserve_quantity_validity']) - $minusQuantity;
                if ($updateData['reserve_quantity_validity'] <= 0) {
                    $updateData['finish_flg'] = config('const.flg.on');
                } else {
                    $updateData['finish_flg'] = config('const.flg.off');
                }
                $Reserve->updateById($updateData);
            } else if ($reserveData['reserve_quantity_validity'] < $minusQuantity) {
                // 該当の引当数がマイナスになってしまう場合

                // 該当案件で有効引当数>=引く数のレコードを探す
                $reserveList = $Reserve->getReserveByMatterProductId($reserveData['matter_id'], $reserveData['product_id']);
                
                $targetReserveData = collect($reserveList)->where('reserve_quantity_validity', '>=', $minusQuantity)->first();
                if ($targetReserveData !== null) {
                    // 有効引当数>=マイナス数が存在する

                    // 引当レコードから有効引当数減算
                    $updateData = [];
                    $updateData['id'] = $targetReserveData['id'];
                    $updateData['reserve_quantity_validity'] = Common::nullorBlankToZero($targetReserveData['reserve_quantity_validity']) - $minusQuantity;
                    if ($updateData['reserve_quantity_validity'] <= 0) {
                        $updateData['finish_flg'] = config('const.flg.on');
                    } else {
                        $updateData['finish_flg'] = config('const.flg.off');
                    }
                    $Reserve->updateById($updateData);

                } else {
                    // 有効引当数>=マイナス数がない
                    // 複数引当からマイナスする
                    foreach($reserveList as $key => $row) {
                        $tmpQuantity = 0;

                        if ($minusQuantity == 0) {
                            break;
                        }
                        
                        if ($row['reserve_quantity_validity'] > 0) {

                            if ($row['reserve_quantity_validity'] <= $minusQuantity) {
                                // 引く数よりも有効引当数が少ない
                                // 返品数からマイナスする
                                $minusQuantity -= $row['reserve_quantity_validity'];
                            } else {
                                // 引く数よりも有効引当数が多い
                                $tmpQuantity = $row['reserve_quantity_validity'] - $minusQuantity;
                                $minusQuantity = 0;
                            }

                            // 引当レコードから有効引当数減算
                            $updateData = [];
                            $updateData['id'] = $row['id'];
                            $updateData['reserve_quantity_validity'] = $tmpQuantity;
                            if ($updateData['reserve_quantity_validity'] <= 0) {
                                $updateData['finish_flg'] = config('const.flg.on');
                            } else {
                                $updateData['finish_flg'] = config('const.flg.off');
                            }
                            $Reserve->updateById($updateData);

                        }

                    }
                }

            }
        }

    }

    /**
     * 案件、商品IDで引当検索
     *
     * @param [type] $matterId
     * @param [type] $productId
     * @return void
     */
    public function getReserveByMatterProductId($matterId, $productId) 
    {
        $where = [];
        $where[] = array('matter_id', '=', $matterId);
        $where[] = array('product_id', '=', $productId);
        $where[] = array('stock_flg', '=', config('const.stockFlg.val.order'));

        $data = $this
                ->where($where)
                ->orderBy('id')
                ->get()
                ;

        return $data;


    }

    /**
     * 在庫情報から取得
     *
     * @param [type] $params
     * @return void
     */
    public function getReserveDataByStockInfo($params)
    {
        $where = [];

        if (!empty($params['stock_flg'])) {
            $where[] = array('stock_flg', '=', $params['stock_flg']);
        }
        if (!empty($params['product_id'])) {
            $where[] = array('product_id', '=', $params['product_id']);
        }
        if (!empty($params['from_warehouse_id'])) {
            $where[] = array('from_warehouse_id', '=', $params['from_warehouse_id']);
        }
        if (!empty($params['matter_id'])) {
            $where[] = array('matter_id', '=', $params['matter_id']);
        }
        
        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

     /**
     * 倉庫・商品ごとの引当数取得
     *
     * @param $warehouseId  倉庫ID
     * @param $productId    商品ID
     * @param $stockFlg
     * @return void
     */
    public function getReserveQuantityValidity($warehouseId, $productId, $stockFlg)
    {
        // Where句作成
        $where = [];
        $where[] = array('reserve.del_flg', '=', config('const.flg.off'));
        $where[] = array('reserve.finish_flg', '=', config('const.flg.off'));
        $where[] = array('reserve.from_warehouse_id', '=', $warehouseId);
        $where[] = array('reserve.product_id', '=', $productId);
        $where[] = array('reserve.stock_flg', '=', $stockFlg);
        $where[] = array('reserve.reserve_quantity_validity', '<>', 0);

        $data = $this
                ->from('t_reserve as reserve')
                ->selectRaw('SUM(reserve.reserve_quantity_validity) as reserve_quantity')
                ->where($where)
                ->first()
                ;

        return $data;
    }
}