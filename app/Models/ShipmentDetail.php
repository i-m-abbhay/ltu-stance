<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;
// use Illuminate\Support\Facades\Log;

/**
 * 出荷指示明細
 */
class ShipmentDetail extends Model
{
    // テーブル名
    protected $table = 't_shipment_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',				
		'shipment_id',				
		'order_detail_id',				
		'quote_id',				
		'quote_detail_id',				
        'reserve_id',	
        'stock_flg',			
		'product_id',				
		'product_code',				
		'shipment_quantity',				
		'loading_finish_flg',				
		'delivery_finish_flg',				
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
    public function getList($params) {
        // Where句作成
        $where = [];
        $where[] = array('t_shipment_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_shipment_detail.loading_finish_flg', '=', config('const.flg.off'));
        $ids = [];
        if (!empty($params['shipment_id'])) {
            $ids = explode(",", $params['shipment_id']);
        }
        
        // データ取得
        $data = $this
                ->leftjoin('t_shipment', 't_shipment.id', '=', 't_shipment_detail.shipment_id')
                ->leftjoin('t_quote_detail', 't_quote_detail.id', '=', 't_shipment_detail.quote_detail_id')
                ->leftjoin('t_matter', 't_matter.id', '=', 't_shipment.matter_id')
                ->leftjoin('m_customer', 'm_customer.id', '=', 't_matter.customer_id')
                ->leftjoin('m_product', 'm_product.id', '=', 't_shipment_detail.product_id')
                ->leftjoin('t_reserve', 't_reserve.id', '=', 't_shipment_detail.reserve_id')
                // ->leftJoin('t_productstock_shelf', 't_productstock_shelf.product_id', '=', 't_shipment_detail.product_id', function ($join) {
                //     $join
                //         ->on('t_productstock_shelf.warehouse_id', '=', 't_shipment.from_warehouse_id' )
                //         ->where('t_productstock_shelf.stock_flg', '=',3)
                //         ->where('t_productstock_shelf.shelf_number_id', '=',)
                //         ;
                // })
                ->where($where)
                ->whereIn('shipment_id', $ids)
                ->selectRaw(
                        't_shipment_detail.shipment_id,
                        t_shipment_detail.id as shipment_detail_id,
                        t_shipment_detail.order_detail_id,
                        t_shipment_detail.quote_id,
                        t_shipment_detail.quote_detail_id,
                        t_shipment_detail.reserve_id,
                        t_shipment_detail.stock_flg,
                        t_shipment.shipment_kind,
                        t_shipment.pref,
                        t_shipment.zipcode,
                        t_shipment.address1,
                        t_shipment.address2,
                        t_shipment.latitude_jp,
                        t_shipment.longitude_jp,
                        t_shipment.latitude_world,
                        t_shipment.longitude_world,
                        t_shipment_detail.product_id,
                        t_shipment_detail.product_code,
                        t_shipment_detail.shipment_quantity,
                        t_shipment_detail.shipment_quantity as loading_quantity,
                        t_shipment_detail.order_detail_id,
                        t_shipment.matter_id,
                        t_shipment.from_warehouse_id,                       
                        null as from_shelf_number_id,                       
                        null as to_warehouse_id,                       
                        null as to_shelf_number_id,
                        (select quantity from t_productstock_shelf as ps
                            inner join m_shelf_number sn on sn.id = ps.shelf_number_id and sn.del_flg = 0
                            where sn.shelf_kind = 0 
                            and ps.stock_flg = 1 
                            and ps.warehouse_id = t_shipment.from_warehouse_id
                            and ps.product_id = m_product.id
                            and ps.matter_id = 0
                            and ps.customer_id = 0
                        ) as stock_quantity,                      
                        t_matter.matter_no,
                        t_matter.matter_name,
                        ifnull(t_matter.customer_id,0) as customer_id,
                        m_customer.customer_name,
                        m_product.id as product_id,
                        m_product.product_code,
                        m_product.product_name,
                        t_quote_detail.model,
                        m_product.stock_unit as unit,
                        t_shipment_detail.stock_flg,
                        0 as from_shelf_number_id,
                        ifnull(t_reserve.reserve_quantity_validity,0) as reserve_quantity_validity,
                        null as qrData'
                )
                ->distinct()
                ->orderBy('t_shipment_detail.product_id')
                ->orderBy('t_shipment_detail.shipment_quantity','desc')
                ->get()
                ;
        
        return $data;
    }

    /**
     * 案件一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getMatterList($params) {
        // Where句作成
        $where = [];
        $where[] = array('t_shipment.del_flg', '=', config('const.flg.off'));
        if (!empty($params['shipment_id'])) {
            $ids = explode(",", $params['shipment_id']);
        }
        
        // データ取得
        $sub = $this
                ->leftjoin('t_shipment', 't_shipment.id', '=', 't_shipment_detail.shipment_id')
                ->select(
                        't_shipment.matter_id',
                        't_shipment_detail.product_id'
                )
                ->groupBy('t_shipment.matter_id', 't_shipment_detail.product_id')
                ;
        $data = DB::table('t_shipment')
                ->leftjoin('t_matter', 't_matter.id', '=', 't_shipment.matter_id')
                ->leftJoin(DB::raw('('.$sub->toSql().') as sub'), function ($join) use($ids) {
                    $join
                        ->on('t_shipment.matter_id', '=', 'sub.matter_id' )
                        ->whereIn('t_shipment.id', $ids)
                        ;
                })
                ->whereIn('t_shipment.id', $ids)
                ->where($where)
                ->select(
                        't_matter.id',
                        't_matter.matter_name',
                        DB::raw('count(sub.product_id) as product_count')
                )
                ->orderBy('t_shipment.matter_id', 'asc')
                ->groupBy('t_shipment.matter_id')
                ->get()
                ;
        
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

        try{
            $result = $this->insertGetId([
                'shipment_id' => $params['shipment_id'],				
                'order_detail_id' => $params['order_detail_id'],				
                'quote_id' => $params['quote_id'],				
                'quote_detail_id' => $params['quote_detail_id'],				
                'reserve_id' => $params['reserve_id'],				
                'product_id' => $params['product_id'],				
                'product_code' => $params['product_code'],				
                'shipment_quantity' => $params['shipment_quantity'],				
                'loading_finish_flg' => $params['loading_finish_flg'],				
                'delivery_finish_flg' => $params['delivery_finish_flg'],				
                'stock_flg' => $params['stock_flg'],				
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
     * 複数データ新規登録
     * 
     * @param $paramsList
     * @return bool
     */
    public function addList($paramsList) : bool{
        $result = false;
        try {
            $insertData = [];
            $userId = Auth::user()->id;
            $now = Carbon::now();

            foreach ($paramsList as $data) {
                $record = [];
                foreach($this->fillable as $column){
                    switch($column){
                        case 'id' :
                            break;
                        case 'loading_finish_flg':
                        case 'delivery_finish_flg':
                        case 'del_flg':
                            $record[$column] = config('const.flg.off');
                            break;
                        case 'created_user':
                        case 'update_user':
                            $record[$column] = $userId;
                            break;
                        case 'created_at':
                        case 'update_at':
                            $record[$column] = $now;
                            break;
                        default :
                            if(is_object($data)){
                                $record[$column] = $data->$column;
                            }else{
                                $record[$column] = $data[$column];
                            }
                            break;
                    }
                }
                $insertData[] = $record;
            }
            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 複数データ新規登録
     * いきなり納品
     * 
     * @param $paramsList
     * @return bool
     */
    public function addSuddenList($paramsList) : bool{
        $result = false;
        try {
            $insertData = [];
            $userId = Auth::user()->id;
            $now = Carbon::now();

            foreach ($paramsList as $data) {
                $record = [];
                foreach($this->fillable as $column){
                    switch($column){
                        case 'id' :
                            break;
                        case 'del_flg':
                            $record[$column] = config('const.flg.off');
                            break;
                        case 'created_user':
                        case 'update_user':
                            $record[$column] = $userId;
                            break;
                        case 'created_at':
                        case 'update_at':
                            $record[$column] = $now;
                            break;
                        default :
                            if(is_object($data)){
                                $record[$column] = $data->$column;
                            }else{
                                $record[$column] = $data[$column];
                            }
                            break;
                    }
                }
                $insertData[] = $record;
            }
            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 更新
     * 
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if(!isset($params['update_user']) || empty($params['update_user'])){
                $params['update_user'] = Auth::user()->id;
            }
            if(!isset($params['update_at']) || empty($params['update_at'])){
                $params['update_at'] = Carbon::now();
            }

            // 更新
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($params);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 出荷積込完了
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateLoadingFinish($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['shipment_detail_id'])
                    ->update([
                        'loading_finish_flg' => 1,
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
     * 納品完了
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateDeliveryFinish($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['shipment_detail_id'])
                    ->update([
                        'delivery_finish_flg' => 1,
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

            $result = $this
                ->where('id', $id)
                ->delete()
                ;

        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param $id 出荷指示ヘッダID
     * @return void
     */
    public function deleteByShipmentId($id)
    {
        $result = false;
        try{
            $where = [];
            $where[] = array('shipment_id', '=', $id);
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            $result = $this
                ->where($where)
                ->delete()
                ;

        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除（配列）
     *
     * @param [type] $idList
     * @return void
     */
    public function physicalDeleteByShipmentIdList($shipmentIdList)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $query = $this->where($where)->whereIn('shipment_id', $shipmentIdList);

        // 削除対象データ取得
        $deleteData = $query->get();

        $LogUtil = new LogUtil();
        foreach ($deleteData as $value) {
            $LogUtil->putByData($value, config('const.logKbn.delete'));
        }

        // 削除
        $deleteCnt = 0;
        if (count($deleteData) > 0) {
            $deleteCnt = $query->delete();
        }

        $result = ($deleteCnt > 0);

        return $result;
    }

    /**
     * 物理削除
     * @param $reserveId 引当ID
     * @return void
     */
    public function deleteByReserveId($reserveId)
    {
        $Shipment = new Shipment();
        $where = [];
        $where[] = array('reserve_id', '=', $reserveId);
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();

            $shipmentIdList = [];
            $deleteDataList = $this->where($where)->get();

            if (count($deleteDataList) > 0) {
                foreach ($deleteDataList as $deleteData) {
                    $LogUtil->putByData($deleteData, config('const.logKbn.delete'), $this->table);
                    $shipmentIdList[$deleteData->shipment_id] = true;
                }

                $result = $this
                    ->where($where)
                    ->delete()
                    ;

                foreach($shipmentIdList as $shipmentId => $data){
                    $validShipmentDetailCnt = $this
                        ->where(['shipment_id'=>$shipmentId])
                        ->get()
                        ->count();
                    if($validShipmentDetailCnt === 0){
                        // 出荷指示明細の無い出荷指示は削除する
                        $Shipment->deleteByShipmentId($shipmentId);
                    }
                }
            }

        } catch(\Exception $e) {
            throw $e;
        }
    }

     /**
     * 一覧取得（出荷納品一覧）
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getDetailList($params) {
        // Where句作成
        $where = [];
        $where[] = array('t_shipment_detail.del_flg', '=', config('const.flg.off'));
        if (!empty($params['shipment_id'])) {
            $where[] = array('t_shipment.id', '=', $params['shipment_id']);
        }
        $isExistShipmentIds = false;
        $shipmentIds = [];
        if (!empty($params['shipment_ids'])) {
            $isExistShipmentIds = true;
            $shipmentIds = '('. implode(',', $params['shipment_ids']). ')';
        }

        // 返品数を引いた出荷数
        $query = $this
                ->leftJoin('t_warehouse_move AS wm', function($join) {
                    $join
                        ->on('wm.shipment_detail_id', '=', 't_shipment_detail.id')
                        ->whereRaw('wm.move_kind = '. config('const.moveKind.return'))
                        ;
                })
                ->leftJoin('t_delivery AS del', 'del.shipment_detail_id', '=', 't_shipment_detail.id')
                ->selectRaw('
                    t_shipment_detail.id AS shipment_detail_id,
                    CASE
                        WHEN MAX(wm.approval_status) = '. config('const.returnApprovalStatus.val.approvaled'). '
                            THEN SUM(wm.quantity)
                        ELSE 0
                    END AS return_quantity,
                    SUM(del.delivery_quantity) AS delivery_quantity
                ')
                ->whereRaw('t_shipment_detail.del_flg = '. config('const.flg.off'))
                ->when($isExistShipmentIds, function($query) use($shipmentIds) {
                    $query
                        ->whereRaw('t_shipment_detail.shipment_id IN '. $shipmentIds);
                })
                ->groupBy('t_shipment_detail.id')
                ->toSql()
                ;

        // 入荷ステータス
        $arrivalQuery = $this
                ->from('t_shipment_detail')
                ->whereRaw('t_shipment_detail.del_flg = '. config('const.flg.off'))
                ->when($isExistShipmentIds, function($query) use($shipmentIds) {
                    $query
                        ->whereRaw('t_shipment_detail.shipment_id IN '. $shipmentIds);
                })
                ->leftJoin('t_shipment as shipment', 'shipment.id', '=', 't_shipment_detail.shipment_id')
                ->leftJoin('t_order_detail as order_detail', function($join) {
                    $join->on('order_detail.id', '=', 't_shipment_detail.order_detail_id')
                         ->whereRaw('order_detail.del_flg = '. config('const.flg.off'))
                         ;
                })
                ->leftJoin('t_order', function($join) {
                    $join->on('t_order.id', '=', 'order_detail.order_id')
                         ->whereRaw('t_order.del_flg = '. config('const.flg.off'))
                         ;
                })
                ->selectRaw('
                    t_shipment_detail.id as shipment_detail_id,
                    CASE
                        WHEN t_shipment_detail.stock_flg = '. config('const.stockFlg.val.stock'). '
                            THEN \''. config('const.shippingDeliveryStatus.status.stock_reserve'). '\' 
                        WHEN t_shipment_detail.stock_flg = '. config('const.stockFlg.val.keep'). '
                            THEN \''. config('const.shippingDeliveryStatus.status.stock_reserve'). '\' 
                        WHEN order_detail.id IS NULL
                            THEN \''. config('const.shippingDeliveryStatus.status.not_arrival'). '\' 
                        WHEN order_detail.arrival_quantity = '. config('const.arrivalStatus.val.not_arrival'). '
                            THEN \''. config('const.shippingDeliveryStatus.status.not_arrival'). '\'
                        WHEN order_detail.arrival_quantity = order_detail.stock_quantity'.'
                            THEN \''. config('const.shippingDeliveryStatus.status.done_arrival'). '\'
                        ELSE \''. config('const.shippingDeliveryStatus.status.part_arrival'). '\'
                    END AS arrival_status
                ')
                ->toSql()
                ;

        // 出荷ステータス
        $shippingQuery = $this
                ->from('t_shipment_detail')
                ->whereRaw('t_shipment_detail.del_flg = '. config('const.flg.off'))
                ->when($isExistShipmentIds, function($query) use($shipmentIds) {
                    $query
                        ->whereRaw('t_shipment_detail.shipment_id IN '. $shipmentIds);
                })
                ->leftJoin('t_loading as loading', function($join) {
                    $join->on('loading.shipment_detail_id', '=', 't_shipment_detail.id')
                            ->whereRaw('loading.del_flg = '. config('const.flg.off'))
                            ;
                })
                ->leftJoin('t_shipment as shipment', 'shipment.id', '=', 't_shipment_detail.shipment_id')
                ->leftJoin('m_staff', 'm_staff.id', '=', 'loading.created_user')
                ->selectRaw('
                        t_shipment_detail.id as shipment_detail_id,
                        m_staff.staff_name,
                        loading.created_at,
                        shipment.shipment_kind,
                        CASE
                            WHEN shipment.shipment_kind = '. config('const.shipmentKind.val.maker').'
                            AND t_shipment_detail.loading_finish_flg = '. config('const.shippingStatus.val.complete').'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_shipping'). '\'
                            WHEN shipment.shipment_kind = '. config('const.shipmentKind.val.takeoff').'
                            AND t_shipment_detail.loading_finish_flg = '. config('const.shippingStatus.val.complete').'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_shipping'). '\'
                            WHEN t_shipment_detail.loading_finish_flg = '. config('const.shippingStatus.val.not_shipping').'
                                THEN \''. config('const.shippingDeliveryStatus.status.not_shipping'). '\'
                            WHEN t_shipment_detail.loading_finish_flg = '. config('const.shippingStatus.val.complete').'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_shipping'). '\'
                            ELSE  \''. config('const.shippingDeliveryStatus.status.not_shipping'). '\' 
                        END AS shipping_status
                    ')
                ->toSql()
                ;

        // 納品ステータス
        $deliveryQuery = $this
                ->from('t_shipment_detail')
                ->whereRaw('t_shipment_detail.del_flg = '. config('const.flg.off'))
                ->when($isExistShipmentIds, function($query) use($shipmentIds) {
                    $query
                        ->whereRaw('t_shipment_detail.shipment_id IN '. $shipmentIds);
                })
                ->leftJoin('t_delivery as delivery', function ($join) {
                    $join
                        ->on('delivery.shipment_detail_id', '=', 't_shipment_detail.id')
                        ->whereRaw('delivery.del_flg = '. config('const.flg.off'))
                        ;
                })
                ->leftJoin('m_staff', 'm_staff.id', '=', 'delivery.created_user')
                ->selectRaw('
                        t_shipment_detail.id as shipment_detail_id,
                        m_staff.staff_name,
                        delivery.created_at,
                        CASE
                            WHEN delivery.shipment_detail_id IS NULL
                                THEN \''. config('const.shippingDeliveryStatus.status.not_delivery'). '\' 
                            WHEN t_shipment_detail.delivery_finish_flg = '. config('const.deliveryStatus.val.not_delivery').'
                                THEN \''. config('const.shippingDeliveryStatus.status.not_delivery'). '\'
                            WHEN t_shipment_detail.delivery_finish_flg = '. config('const.deliveryStatus.val.complete').'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_delivery'). '\'
                        END AS delivery_status
                    ')
                ->toSql()
                ;

        // 出荷積込ID (SUM)
        $sumLoadingIdQuery = $this
                ->from('t_shipment_detail')
                ->whereRaw('t_shipment_detail.del_flg = '. config('const.flg.off'))
                ->when($isExistShipmentIds, function($query) use($shipmentIds) {
                    $query
                        ->whereRaw('t_shipment_detail.shipment_id IN '. $shipmentIds);
                })
                ->leftJoin('t_delivery as delivery', function ($join) {
                    $join
                        ->on('delivery.shipment_detail_id', '=', 't_shipment_detail.id')
                        ->whereRaw('delivery.del_flg = '. config('const.flg.off'))
                        ;
                })
                ->selectRaw('
                        t_shipment_detail.id as shipment_detail_id,
                        SUM(delivery.loading_id) as sum_loading_id
                ')
                ->groupBy('t_shipment_detail.id')
                ->toSql()
                ;

        // $stockQuery = $this
        //             ->from('t_productstock_shelf AS stock')
        //             ->leftJoin('m_shelf_number as snumber', 'stock.shelf_number_id', '=', 'snumber.id')
        //             ->select([
        //                 'stock.product_id',
        //                 'stock.warehouse_id',
        //                 'stock.stock_flg',
        //                 'stock.matter_id',
        //                 'stock.customer_id',
        //             ])
        //             ->whereRaw('
        //                 snumber.shelf_kind = \''. config('const.shelfKind.normal'). '\'
        //                 OR snumber.shelf_kind = \''. config('const.shelfKind.keep'). '\'
        //                 OR snumber.shelf_kind = \''. config('const.shelfKind.temporary'). '\'
        //             ')
        //             ->selectRaw('
        //                 GROUP_CONCAT(CONCAT(COALESCE(snumber.shelf_area, \'\'), \' \', COALESCE(snumber.shelf_steps, \'\'), \'段目\')SEPARATOR \'/\') AS rack
        //             ')
        //             ->groupBy(
        //                 'stock.product_id',
        //                 'stock.warehouse_id',
        //                 'stock.stock_flg',
        //                 'stock.matter_id',
        //                 'stock.customer_id'
        //             )
        //             ->toSql()
        //             ;


        // データ取得
        $data = $this
                ->leftjoin('t_shipment', 't_shipment.id', '=', 't_shipment_detail.shipment_id')
                ->leftjoin('t_matter', 't_matter.id', '=', 't_shipment.matter_id')
                ->leftjoin('m_customer', 'm_customer.id', '=', 't_matter.customer_id')
                ->leftJoin('m_product', 'm_product.id', '=', 't_shipment_detail.product_id')
                ->leftJoin('t_quote_detail as quote_detail', 'quote_detail.id', '=', 't_shipment_detail.quote_detail_id')
                ->leftJoin('t_loading AS load', function($join) {
                    $join
                        ->on('load.shipment_detail_id', '=', 't_shipment_detail.id')
                        ->where('load.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_delivery AS de', function($join) {
                    $join
                        ->on('de.shipment_detail_id', '=', 't_shipment_detail.id')
                        ->where('de.del_flg', '=', config('const.flg.off'))
                        ;
                })
                // ->leftJoin('t_productstock_shelf as stock', function($join) {
                //     $join->on('stock.product_id', '=', 't_shipment_detail.product_id')
                //          ->on('warehouse_id', '=', 't_shipment.from_warehouse_id')
                //          ;
                // })
                // ->leftJoin('m_shelf_number as snumber', 'stock.shelf_number_id', '=', 'snumber.id')
                // ->leftJoin('t_warehouse_move AS wm', 'wm.shipment_detail_id', '=', 't_shipment_detail.id')
                ->leftJoin(DB::raw('('.$query.') as warehouse_move'), function($join){
                    $join->on('warehouse_move.shipment_detail_id', 't_shipment_detail.id');
                })
                ->leftJoin(DB::raw('('.$arrivalQuery.') as arrival_s'), function($join){
                    $join->on('arrival_s.shipment_detail_id', 't_shipment_detail.id');
                })
                ->leftJoin(DB::raw('('.$shippingQuery.') as shipping_s'), function($join){
                    $join->on('shipping_s.shipment_detail_id', 't_shipment_detail.id');
                })
                ->leftJoin(DB::raw('('.$deliveryQuery.') as delivery_s'), function($join){
                    $join->on('delivery_s.shipment_detail_id', 't_shipment_detail.id');
                })
                ->leftJoin(DB::raw('('.$sumLoadingIdQuery.') as sum_loading'), function($join){
                    $join->on('sum_loading.shipment_detail_id', 't_shipment_detail.id');
                })
                //->leftJoin('t_reserve AS res', 'res.id', '=', 't_shipment_detail.reserve_id')
                // ->leftJoin(DB::raw('('.$stockQuery.') as stock'), function($join){
                //     $join
                //         ->on('stock.product_id', '=', 't_shipment_detail.product_id')
                //         ->on('stock.warehouse_id', '=', 't_shipment.from_warehouse_id')
                //         ->on('stock.stock_flg', '=', 't_shipment_detail.stock_flg')
                //         ->on('stock.matter_id', '=', 't_matter.id')
                //         ->on('stock.customer_id', '=', 't_matter.customer_id')
                //         // ->on('stock.shelf_number_id', '=', 'res.from_shelf_number_id')
                //         ;
                // })
                // ->leftJoin('t_order_detail AS order_detail', 'order_detail.id', '=', 't_shipment_detail.order_detail_id')
                ->where($where)
                ->when($isExistShipmentIds, function($query) use($shipmentIds) {
                    $query
                        ->whereRaw('t_shipment_detail.shipment_id IN '. $shipmentIds);
                })
                ->orderBy('t_shipment_detail.id', 'asc')
                ->select([
                    't_shipment_detail.shipment_id',
                    't_shipment_detail.id as shipment_detail_id',
                    't_shipment_detail.product_code',
                    't_shipment_detail.product_id',
                    't_shipment_detail.stock_flg',
                    't_shipment_detail.loading_finish_flg',
                    'quote_detail.id as quote_detail_id',
                    'quote_detail.seq_no as seq_no',
                    'quote_detail.product_id as quote_product_id',
                    'quote_detail.quote_quantity as quote_quantity',
                    'quote_detail.stock_quantity as stock_quantity',
                    // 'warehouse_move.shipment_quantity',
                    // 'warehouse_move.delivery_quantity as shipment_quantity',
                    'warehouse_move.return_quantity',
                    'm_product.product_name',
                    'quote_detail.model',
                    'm_product.unit',
                    'arrival_s.arrival_status AS arrival_status',
                    'shipping_s.staff_name AS shipping_staff',
                    DB::raw('DATE_FORMAT(shipping_s.created_at, "%Y/%m/%d %T") AS shipping_created_at'),
                    // 'shipping_s.shipping_status AS shipping_status',
                    'shipping_s.shipment_kind',
                    'delivery_s.staff_name AS delivery_staff',
                    DB::raw('DATE_FORMAT(delivery_s.created_at, "%Y/%m/%d %T") AS delivery_created_at'),
                    'delivery_s.delivery_status AS delivery_status',
                    // 'stock.shelf_area',
                    // 'stock.shelf_steps',
                    // 'stock.rack',
                    'sum_loading.sum_loading_id',
                ])
                ->selectRaw('
                    (
                        SELECT GROUP_CONCAT(CONCAT(COALESCE(snumber.shelf_area, \'\'), \' \', COALESCE(snumber.shelf_steps, \'\'), \'段目\')SEPARATOR \'/\')  
                        FROM t_productstock_shelf as stock 
                        LEFT JOIN m_shelf_number as snumber 
                            ON stock.shelf_number_id = snumber.id 
                        WHERE
                            ( snumber.shelf_kind = ' . config('const.shelfKind.normal') 
                            .' OR snumber.shelf_kind = ' . config('const.shelfKind.keep') 
                            .' OR snumber.shelf_kind = ' . config('const.shelfKind.temporary') 
                            .' )
                            AND stock.product_id = t_shipment_detail.product_id 
                            AND stock.warehouse_id = t_shipment.from_warehouse_id 
                            AND stock.stock_flg = t_shipment_detail.stock_flg 
                            AND stock.matter_id = t_matter.id 
                            AND stock.customer_id = t_matter.customer_id
                    ) AS rack,

                    CASE
                        WHEN de.id IS NOT NULL
                            THEN de.delivery_quantity
                        WHEN load.id IS NOT NULL
                            THEN load.loading_quantity
                        ELSE t_shipment_detail.shipment_quantity
                    END AS shipment_quantity,
                    
                    CASE
                        WHEN sum_loading.sum_loading_id IS NULL
                            THEN shipping_s.shipping_status
                        WHEN sum_loading.sum_loading_id = 0
                            THEN \''. config('const.shippingDeliveryStatus.status.done_shipping'). '\'
                        ELSE shipping_s.shipping_status
                    END AS shipping_status
                ')
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 一覧取得（出荷納品一覧） 直送分
     * 得意先/案件の絞り込みは有効
     * @param array $params 検索条件の配列
     * @param array $orderIds 親グリッドで対象となった発注IDの配列
     * @return type 検索結果データ
     */
    public function getDetailListByOrder($params, $orderIds) {

        // Where句作成
        $where = [];
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.status', '=', config('const.orderStatus.val.ordered'));
        $where[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.site'));
        $where[] = array('de.delivery_quantity', '>', 0);

        if (!empty($params['matter_no_id'])) {
            $where[] = array('m.id', '=', $params['matter_no_id']);
        } else {
            if (!empty($params['matter_no'])) {
                $where[] = array('m.matter_no', 'LIKE', '%'.$params['matter_no'].'%');
            }
        }
        if (!empty($params['matter_name_id'])) {
            $where[] = array('m.id', '=', $params['matter_name_id']);
        } else {
            if (!empty($params['matter_name'])) {
                $where[] = array('m.matter_name', 'LIKE', '%'.$params['matter_name'].'%');
            }
        }

        // 返品数を引いた出荷数
        $returnQuery = 
                DB::table('t_order_detail as od')
                // ->from('m_customer as cus')
                // ->leftJoin('t_matter as m', 'm.customer_id', '=', 'cus.id')
                // ->leftJoin('t_order as o', 'o.matter_no', '=', 'm.matter_no')
                // ->leftJoin('t_order_detail as od', 'o.id', '=', 'od.order_id')
                // ->leftJoin('t_quote_detail as qd', 'qd.id', '=', 'od.quote_detail_id')
                ->leftJoin('t_warehouse_move AS wm', function($join) {
                    $join
                        ->on('wm.order_detail_id', '=', 'od.id')
                        ->whereRaw('wm.move_kind = '. config('const.moveKind.return'))
                        ;
                })
                ->selectRaw('
                    od.id AS order_detail_id,
                    CASE
                        WHEN MAX(wm.approval_status) = '. config('const.returnApprovalStatus.val.approvaled'). '
                            THEN SUM(wm.quantity)
                        ELSE 0
                    END AS return_quantity
                ')
                ->whereIn('od.order_id', $orderIds)
                ->groupBy('od.id')
                ;

        // データ取得
        $data = $this
                // ->from('m_customer as cus')
                // ->leftJoin('t_matter as m', 'm.customer_id', '=', 'cus.id')
                // ->leftJoin('t_order as o', 'o.matter_no', '=', 'm.matter_no')
                ->from('t_order as o')
                ->leftJoin('t_matter as m', 'o.matter_no', '=', 'm.matter_no')
                ->leftJoin('m_customer as cus', 'm.customer_id', '=', 'cus.id')
                ->leftJoin('t_order_detail as od', 'o.id', '=', 'od.order_id')
                ->leftJoin('t_quote_detail as qd', 'qd.id', '=', 'od.quote_detail_id')
                ->leftJoin('m_product as pdt', 'pdt.id', '=', 'od.product_id')
                ->leftJoin('t_delivery AS de', function($join) {
                    $join
                        ->on('de.order_detail_id', '=', 'od.id')
                        ->where('de.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('m_staff as st', 'st.id', '=', 'de.created_user')
                ->leftJoin('m_general', function($join) {
                    $join->on('m_general.value_code', '=', 'cus.juridical_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftJoin(DB::raw('('.$returnQuery->toSql().') as warehouse_move'), function($join){
                    $join->on('warehouse_move.order_detail_id', '=', 'od.id');
                })
                ->mergeBindings($returnQuery)
                ->whereIn('o.id', $orderIds)
                ->where($where)
                // ->where(function($query){
                //     $query->where('pdt.intangible_flg', config('const.flg.on'))
                //           ->orWhere('od.product_id', 0);
                // })
                ->when(!empty($params['customer_name']), function ($query) use ($params) {
                    if (!empty($params['customer_id'])) {
                        $query
                            ->where('cus.id', $params['customer_id']);
                    } else {
                        $query
                            ->whereRaw(
                                'CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) LIKE ?',
                                ['%' . $params['customer_name'] . '%']
                            );
                    }
                    return $query;
                })
                
                ->orderBy('od.id', 'asc')
                ->select([
                    'o.id as shipment_id',
                    'od.id as shipment_detail_id',
                    'od.product_code',
                    'od.product_id',
                    'de.stock_flg',
                    DB::raw('0 as quote_detail_id'),
                    DB::raw('0 as seq_no'),
                    DB::raw('0 as quote_product_id'),
                    // 'od.stock_quantity as quote_quantity',
                    'qd.quote_quantity as quote_quantity',
                    'qd.stock_quantity as stock_quantity',
                    'de.delivery_quantity as delivery_quantity',
                    'warehouse_move.return_quantity',
                    DB::raw('COALESCE(pdt.product_name, od.product_name) as product_name'),
                    DB::raw('COALESCE(qd.model, od.model) as model'),
                    DB::raw('COALESCE(pdt.unit, od.unit) as unit'),
                    DB::raw(config('const.arrivalStatus.val.done_arrival').' as arrival_status'),
                    'st.staff_name AS shipping_staff',
                    DB::raw('DATE_FORMAT(de.created_at, "%Y/%m/%d %T") AS shipping_created_at'),
                    DB::raw(config('const.shippingStatus.val.complete').' as shipping_status'),
                    'st.staff_name AS delivery_staff',
                    DB::raw('DATE_FORMAT(de.created_at, "%Y/%m/%d %T") AS delivery_created_at'),
                    DB::raw(config('const.deliveryStatus.val.complete').' as delivery_status'),
                    DB::raw("'' as rack"),
                    'de.delivery_quantity as shipment_quantity',
                ])
                ->distinct()
                ->get()
                ;

        return $data;
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
     * 発注IDで取得
     * 
     * @param int $orderId 発注ID
     */
    public function getByOrderId($orderId) {
        $data = $this
                ->join('t_reserve', 't_reserve.id', '=', 't_shipment_detail.reserve_id')
                ->select([
                    't_shipment_detail.*',
                ])
                ->where(['t_reserve.order_id' => $orderId])
                ->where(['t_reserve.stock_flg' => config('const.stockFlg.val.order')])
                ->where(['t_reserve.del_flg' => config('const.flg.off')])
                ->where(['t_shipment_detail.del_flg' => config('const.flg.off')])
                ->get()
                ;

        return $data;
    }

    /**
     * 発注明細IDで取得
     * 
     * @param int $orderDetailId 発注明細ID
     * @return type 検索結果データ（1件）
     */
    public function getByOrderDetailId($orderDetailId) {
        $data = $this
                ->where(['order_detail_id' => $orderDetailId])
                ->where(['t_shipment_detail.del_flg' => config('const.flg.off')])
                ->get()
                ;

        return $data;
    }


    /**
     * 出荷指示と紐付いた明細を１件取得
     *
     * @param [type] $shipmentID
     * @return void
     */
    public function getFirstByShipmentID($shipmentID) {
        $where = [];
        $where[] = array('shipment_detail.shipment_id', '=', $shipmentID);
        $where[] = array('shipment_detail.seq_no', '=', config('const.flg.on'));


        $data = $this
                ->from('t_shipment_detail as shipment_detail')
                ->where($where)
                ->leftJoin('m_product as product', 'shipment_detail.product_id', '=', 'product.id')
                ->select('product.product_name')
                ->first()
                ;

        return $data;
    }
    /**
     * 出荷納品一覧　入荷状況取得
     *
     * @param int $DetailIDs
     * @return void
     */
    public function getArrivalStatus($DetailIDs){
        $where = [];
        $where[] = array('shipment.del_flg', '=', config('const.flg.off'));

        $joinWhere['order_detail'][] = array('order_detail.del_flg', '=', config('const.flg.off'));
        $joinWhere['order'][] = array('order.del_flg', '=', config('const.flg.off'));
        $data = $this
                ->from('t_shipment_detail as shipment_detail')
                ->where($where)
                ->whereIn('shipment_detail.id', $DetailIDs)
                ->leftJoin('t_shipment as shipment', 'shipment.id', '=', 'shipment_detail.shipment_id')
                ->leftJoin('t_order_detail as order_detail', function($join) use($joinWhere) {
                    $join->on('order_detail.id', '=', 'shipment_detail.order_detail_id')
                         ->where($joinWhere['order_detail'])
                         ;
                })
                ->leftJoin('t_order as order', function($join) use($joinWhere) {
                    $join->on('order.order_no', '=', 'order_detail.order_no')
                         ->where($joinWhere['order'])
                         ;
                })
                ->selectRaw('
                        shipment_detail.id as id,
                        shipment_detail.shipment_id as shipment_id,
                        order.id as order_id,
                        order_detail.id as order_detail_id,
                        order_detail.order_no as order_no,
                        CASE
                            WHEN order_detail.id IS NULL
                                THEN \''. config('const.shippingDeliveryStatus.status.not_arrival'). '\' 
                            WHEN order_detail.arrival_quantity = '. config('const.arrivalStatus.val.not_arrival'). '
                                THEN \''. config('const.shippingDeliveryStatus.status.not_arrival'). '\'
                            WHEN order_detail.arrival_quantity = order_detail.order_quantity'.'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_arrival'). '\'
                            ELSE \''. config('const.shippingDeliveryStatus.status.part_arrival'). '\'
                        END AS arrival_status
                    ')
                ->get()
                ;

        return $data;
    }

    /**
     * 出荷納品一覧　出荷状況取得
     *
     * @param int $DetailIDs
     * @return void
     */
    public function getShippingStatus($DetailIDs){
        $where = [];
        $where[] = array('shipment_detail.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->whereIn('shipment_detail.id', $DetailIDs)
                ->from('t_shipment_detail as shipment_detail')
                ->leftJoin('t_loading as loading', function($join) {
                    $join->on('loading.shipment_detail_id', '=', 'shipment_detail.id')
                         ->where('loading.del_flg', '=', config('const.flg.off'))
                         ;
                })
                ->leftJoin('m_staff', 'm_staff.id', '=', 'loading.created_user')
                ->selectRaw('
                        shipment_detail.id as id,
                        shipment_detail.shipment_id as shipment_id,
                        m_staff.staff_name as staff,
                        loading.created_at,
                        CASE
                            WHEN loading.shipment_detail_id IS NULL
                                THEN \''. config('const.shippingDeliveryStatus.status.not_shipping'). '\' 
                            WHEN shipment_detail.loading_finish_flg = '. config('const.shippingStatus.val.not_shipping').'
                                THEN \''. config('const.shippingDeliveryStatus.status.not_shipping'). '\'
                            WHEN shipment_detail.loading_finish_flg = '. config('const.shippingStatus.val.complete').'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_shipping'). '\'
                        END AS shipping_status
                    ')
                ->get()
                ;

        return $data;
    }

    /**
     * 出荷納品一覧　納品状況取得
     *
     * @param int $DetailIDs
     * @return void
     */
    public function getDeliveryStatus($DetailIDs){
        $where = [];
        $where[] = array('shipment_detail.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->whereIn('shipment_detail.id', $DetailIDs)
                ->from('t_shipment_detail as shipment_detail')
                ->leftJoin('t_delivery as delivery', 'delivery.shipment_detail_id', '=', 'shipment_detail.id')
                ->leftJoin('m_staff', 'm_staff.id', '=', 'delivery.created_user')
                ->selectRaw('
                        shipment_detail.id as id,
                        shipment_detail.shipment_id as shipment_id,
                        m_staff.staff_name as staff,
                        delivery.created_at,
                        CASE
                            WHEN delivery.shipment_detail_id IS NULL
                                THEN \''. config('const.shippingDeliveryStatus.status.not_delivery'). '\' 
                            WHEN shipment_detail.delivery_finish_flg = '. config('const.deliveryStatus.val.not_delivery').'
                                THEN \''. config('const.shippingDeliveryStatus.status.not_delivery'). '\'
                            WHEN shipment_detail.delivery_finish_flg = '. config('const.deliveryStatus.val.complete').'
                                THEN \''. config('const.shippingDeliveryStatus.status.done_delivery'). '\'
                        END AS delivery_status
                    ')
                ->get()
                ;

        return $data;
    }

    /**
     * 引当IDから取得
     *
     * @param $reserveId
     * @return void
     */
    public function getByReserveId($reserveIds)
    {
        $where = [];
        $where[] = array('t_reserve.del_flg', '=', config('const.flg.off'));

        // 引当ごとの出荷指示数取得
        $sumShipmentQuantity  = $this
                ->leftJoin('t_shipment', 't_shipment.id', '=', 't_shipment_detail.shipment_id')
                ->join('t_reserve', function ($join) {
                    $join->on('t_reserve.id', 't_shipment_detail.reserve_id');
                    $join->on('t_reserve.product_id', 't_shipment_detail.product_id');
                })
                ->select([
                    't_reserve.id as reserve_id',
                ])
                // SUM(t_shipment_detail.shipment_quantity) as sum_shipment_quantity
                ->selectRaw('
                    SUM(t_shipment_detail.shipment_quantity) as sum_shipment_quantity
                ')
                // ->whereRaw(t_reserve.matter_id = '.$matterId)
                ->whereRaw('
                    t_shipment_detail.del_flg = "'. config('const.flg.off') .'"   
                    AND t_shipment.loading_finish_flg = "'. config('const.flg.off') .'"
                    AND t_shipment_detail.loading_finish_flg = "'. config('const.flg.off') .'"
                ')
                ->groupBy('t_reserve.id')
                ->toSql()
                ;

        $data = $this
                ->from('t_reserve')
                ->leftjoin(DB::raw('('.$sumShipmentQuantity.') as t_shipment_detail'), function($join){
                    $join->on('t_reserve.id', 't_shipment_detail.reserve_id');
                })
                // ->leftJoin('t_shipment_detail', 't_shipment_detail.reserve_id', '=', 't_reserve.id')
                ->leftJoin('t_order_detail', 't_order_detail.id', '=', 't_reserve.order_detail_id')
                ->leftJoin('t_matter_detail', function ($join) {
                    $join
                        ->on('t_matter_detail.quote_detail_id', '=', 't_reserve.quote_detail_id')
                        ->where('t_matter_detail.type', '=', config('const.matterTaskType.val.hope_arrival_plan_date'))
                        ;
                })
                ->where($where)
                ->whereIn('t_reserve.id', $reserveIds)
                ->selectRaw('
                    t_reserve.id AS reserve_id,
                    t_reserve.stock_flg,
                    t_reserve.from_warehouse_id,
                    t_reserve.matter_id,
                    t_reserve.product_id,
                    t_reserve.product_code,
                    t_reserve.reserve_quantity AS reserve_quantity,
                    t_reserve.reserve_quantity_validity AS reserve_quantity_validity,
                    t_shipment_detail.sum_shipment_quantity,
                    t_order_detail.arrival_quantity,
                    t_order_detail.id as order_detail_id,
                    t_reserve.quote_id as quote_id,
                    t_reserve.quote_detail_id as quote_detail_id,
                    t_order_detail.stock_quantity as order_stock_quantity,
                    t_order_detail.order_quantity,
                    DATE_FORMAT(t_order_detail.arrival_plan_date, "%Y/%m/%d") as arrival_plan_date,
                    CASE
                        WHEN t_reserve.stock_flg = '. config('const.stockFlg.val.order').'
                            THEN DATE_FORMAT(t_order_detail.hope_arrival_plan_date, "%Y/%m/%d")
                        ELSE DATE_FORMAT(t_matter_detail.start_date, "%Y/%m/%d")
                    END AS hope_arrival_plan_date,
                    CASE
                        WHEN t_reserve.stock_flg = \''. config('const.stockFlg.val.order') .'\'
                            THEN t_reserve.id
                        ELSE \'0\'
                    END AS order_reserve_id,
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
                ->orderBy('t_reserve.id')
                ->orderBy('t_reserve.stock_flg')
                ->get()
                ;

        return $data;
    }

    /**
     * 発注画面で商品が変更になった場合に呼ぶ
     * 　在庫/預の出荷指示明細は削除
     * 　削除した出荷指示明細の出荷指示ヘッダーに紐づく出荷指示明細が無ければ出荷指示ヘッダーを削除
     * 　発注の出荷指示明細は商品IDと商品コード変更
     * @param int $quoteDetailId 見積明細ID
     * @param int $productId 商品ID(変更後)
     * @param string $productCode 商品コード
     */
    function changeOrderProduct($quoteDetailId, $productId, $productCode){
        $Shipment = new Shipment();

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

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();

        $deleteDetaList = $this
            ->select([
                'shipment_id'
            ])
            ->where($whereDelete)
            ->distinct()
            ->get()
            ;
        
        if (count($deleteDetaList) > 0) {
            $LogUtil->putByWhere($this->table, $whereDelete, config('const.logKbn.delete'));

            $deleteCnt = $this
                ->where($whereDelete)
                ->delete()
                ;
        }
        
        foreach($deleteDetaList as $record){
            $validShipmentDetailCnt = $this
                ->where(['shipment_id'=>$record->shipment_id])
                ->get()
                ->count();
            if($validShipmentDetailCnt === 0){
                // 出荷指示明細の無い出荷指示は削除する
                $Shipment->deleteByShipmentId($record->shipment_id);
            }
        }

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();

        $userId = Auth::user()->id;
        $now    = Carbon::now();

        $updateDataList = $this
                ->where($whereUpdate)
                ->get()
                ;
        
        if (count($updateDataList) > 0) {
            $LogUtil->putByWhere($this->table, $whereUpdate, config('const.logKbn.update'));

            foreach ($updateDataList as $key => $record) {
                $updateCnt = $this
                    ->where('id', $record['id'])
                    ->update([
                        'product_id' => $productId,
                        'product_code' => $productCode,
                        'update_user' => $userId,
                        'update_at' => $now,
                    ]);
            }
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
     * 引当IDから取得
     *
     * @param $reserveId
     * @return void
     */
    public function getShipmentDataByReserveId($reserveId)
    {
        $where = [];
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));
        $where[] = array('sd.reserve_id', '=', $reserveId);

        $data = $this
                ->from('t_shipment_detail as sd')
                ->leftJoin('t_shipment as shipment', 'shipment.id', '=', 'sd.shipment_id')
                ->where($where)
                ->select([
                    'shipment.id as shipment_id',
                    'sd.id as shipment_detail_id',
                    'sd.order_detail_id',
                    'sd.quote_id',
                    'sd.quote_detail_id',
                    'sd.reserve_id',
                    'sd.stock_flg',
                    'sd.product_id',
                    'sd.product_code',
                    'sd.shipment_quantity',
                    'sd.loading_finish_flg',
                ])
                ->get()
                ;

        return $data;
    }
    
    /**
     * 見積IDで取得
     *
     * @param [type] $quoteId
     * @return void
     */
    public function getByQuoteId($quoteId){
        $data = $this
                ->where([
                    ['del_flg', config('const.flg.off')],
                    ['quote_id', $quoteId],
                ])
                ->get()
                ;
        return $data;
    }

    /**
     * 未出荷の出荷指示数合計を取得
     *
     * @param [type] $reserveId
     * @return void
     */
    public function getUntreatedQuantityByReserveId($reserveId)
    {
        $where = [];
        $where[] = array('t_shipment_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_shipment_detail.reserve_id', '=', $reserveId);
        $where[] = array('t_shipment_detail.loading_finish_flg', '=', config('const.flg.off'));
        $where[] = array('shipment.loading_finish_flg', '=', config('const.flg.off'));

        $data = $this
            ->leftJoin('t_shipment as shipment', 'shipment.id', '=', 't_shipment_detail.shipment_id')
            ->where($where)
            ->selectRaw('
                t_shipment_detail.reserve_id,    
                SUM(t_shipment_detail.shipment_quantity) as sum_shipment_quantity
            ')
            ->groupBy('t_shipment_detail.reserve_id')
            ->first()
            ;
        
        return $data;
    }

    /**
     * 【案件詳細専用】スケジューラ-配送
     *
     * @return array
     */
    public function getSchedulerShipmentForMatterDetail($matterId)
    {
        $data = $this
                    ->join('t_shipment', function($join){
                        $join
                            ->on('t_shipment_detail.shipment_id', '=', 't_shipment.id')
                            ->where('t_shipment.del_flg', '=', config('const.flg.off'));
                    })
                    ->join('t_reserve', function($join){
                        $join
                            ->on('t_shipment_detail.reserve_id', '=', 't_reserve.id')
                            ->where('t_reserve.del_flg', '=', config('const.flg.off'));
                    })
                    ->join('t_quote_detail', function($join){
                        $join
                            ->on('t_reserve.quote_detail_id', '=', 't_quote_detail.id')
                            ->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
                    })
                    ->where([
                        ['t_shipment_detail.del_flg', '=', config('const.flg.off')],
                        ['t_shipment.matter_id', '=', $matterId]
                    ])
                    ->select(
                        't_shipment.delivery_date',
                        't_quote_detail.construction_id',
                        DB::raw('MIN(t_shipment_detail.delivery_finish_flg) AS min_delivery_finish_flg')
                    )
                    ->groupBy('t_shipment.delivery_date', 't_quote_detail.construction_id')
                    ->get();
        return $data;
    }
}