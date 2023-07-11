<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\QuoteVersion;
use App\Models\QuoteLayer;
use App\Models\Customer;
use App\Models\General;
use App\Models\LockManage;
use App\Models\NumberManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Address;
use App\Models\CalendarData;
use App\Models\CustomerBranch;
use App\Models\Delivery;
use App\Models\Loading;
use App\Models\ProductstockShelf;
use App\Models\QuoteDetail;
use App\Models\Reserve;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Warehouse;
use App\Models\WarehouseMove;

/**
 * 出荷指示
 */
class ShippingInstructionController extends Controller
{
    const SCREEN_NAME = 'shipping-instruction';

    const SHIPMENT_KIND_LIST = [
        'matter' => [
            'shipment_kind' => 0,
            'lbl' => '案件現場',
            'unique_key' => '',
            'id'  =>'',
            'zipcode'   =>'',
            'pref'      =>'',
            'address1'  =>'',
            'address2'  =>'',
            'latitude_jp'   =>'',
            'longitude_jp'  =>'',
            'latitude_world'=>'',
            'longitude_world'   =>'',
        ],
        'customer' => [
            'shipment_kind' => 1,
            'lbl' => '',
            'unique_key' => '',
            'id'  =>'',
            'zipcode'   =>'',
            'pref'      =>'',
            'address1'  =>'',
            'address2'  =>'',
            'latitude_jp'   =>'',
            'longitude_jp'  =>'',
            'latitude_world'=>'',
            'longitude_world'   =>'',
        ],
        'customer_branch' => [
            'shipment_kind' => 2,
            'lbl' => '',
            'unique_key' => '',
            'id'  =>'',
            'zipcode'   =>'',
            'pref'      =>'',
            'address1'  =>'',
            'address2'  =>'',
            'latitude_jp'   =>'',
            'longitude_jp'  =>'',
            'latitude_world'=>'',
            'longitude_world'   =>'',
        ],
        'take_off' => [
            'shipment_kind' => 4,
            'lbl' => '引取',
            'unique_key' => '',
            'id'  =>'',
            'zipcode'   =>'',
            'pref'      =>'',
            'address1'  =>'',
            'address2'  =>'',
            'latitude_jp'   =>'',
            'longitude_jp'  =>'',
            'latitude_world'=>'',
            'longitude_world'   =>'',
        ]
    ];

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 初期表示
     * @param Request $request
     * @param int $shipmentId 案件ID
     * @return type
     */
    public function index(Request $request, $shipmentId = null)
    {
        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $Shipment   = new Shipment();
        $Matter     = new Matter();
        $QuoteVersion   = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();
        $LockManage = new LockManage();
        $CalendarData   = new CalendarData();
        $ShipmentDetail = new ShipmentDetail();
        //$General    = new General();

        $mainData   = null;
        $quoteVerList   = [];
        $lockData   = null;
        $gridDataList   = [];
        $treeDataList = $QuoteDetail;
        $shipmentKindDataList   = [];
        $reserveList = [];
        
        DB::beginTransaction();
        try {

            // 案件リスト取得
            $matterList = $Matter->getComboList();
            
            if (is_null($shipmentId)) {
                // 新規
                $mainData = $Shipment;
                $quoteVerList[] = $QuoteVersion;
                Common::initModelProperty($mainData);
                $mainData->version_list = [];
            } else {
                // 編集時
                $shipmentId = (int)$shipmentId;
                $mainData = $Shipment->getAllDataById($shipmentId);
                if($mainData === null){
                    throw new NotFoundHttpException();
                }
                $mainData->version_list = [];
                $shipmentDetailDataList = $Shipment->getShipmentEditDataList($shipmentId, $mainData->matter_id);
                if ($shipmentDetailDataList->count() === 0) {
                    throw new NotFoundHttpException();
                }
                
                $idList = [];
                $reserveIds = [];
                $groupData = [];
                foreach($shipmentDetailDataList as $key => $data){
                    $tmp = explode('_', $data->tree_path);
                    $idList = array_merge($idList, $tmp);
                    
                    $data->next_businessday = null;
                    if(!empty($data->arrival_plan_date)){
                        $calendar = $CalendarData->getNextBusinessDay($data->arrival_plan_date);
                        if($calendar !== null){
                            $data->next_businessday = $calendar->calendar_date;
                        }
                    }

                    $groupData[$data->quote_detail_id][] = $data;
                }
                // 同じ見積もり明細レコードを1つにまとめる
                foreach($groupData as $quoteDetailId => $quoteDetailGroupList){
                    $quoteDetailGroupData = null;
                    $Ids = [];
                    foreach($quoteDetailGroupList as $key => $row){
                        $this->convertGridData($quoteDetailGroupData, $key, $row, true);
                        $Ids[] = $row->reserve_id;
                        $reserveIds[] = $row->reserve_id;
                        // $reserveIds[] = $row->reserve_id;
                        // $quoteDetailGroupList[$key]->reserve_ids[] = $row->reserve_id;
                    }
                    $quoteDetailGroupData->reserve_ids = $Ids;

                    $gridDataList[] = $quoteDetailGroupData;
                }

                $reserveList = $ShipmentDetail->getByReserveId($reserveIds);
                // $reserveList = collect($reserveList)->toArray();

                // ツリー
                $treeDataList = $QuoteDetail->getTreeData(null,null,config('const.flg.on'),null,array_unique(array_map('intval', $idList)));
                // お届け先種別
                $shipmentKindDataList[] = $this->createShipmentKindList($mainData->address_id, $mainData->customer_id, $mainData->from_warehouse_id);


                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($mainData->id);

                $lockCnt = 0;
                foreach ($tableNameList as $i => $tableName) {
                    // ロック確認
                    $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                    // GETパラメータからモード取得
                    // $mode = config('const.mode.show');
                    $mode = $request->query(config('const.query.mode'));
                    if (!$isLocked && $mode == config('const.mode.edit')) {
                        // 編集モードかつ、ロックされていない場合はロック取得
                        $lockDt = Carbon::now();
                        $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $keys[$i]);
                    }
                    // ロックデータ取得
                    $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                    if (!is_null($lockData) 
                        && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                        && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                            $lockCnt++;
                    }
                }
                if (count($tableNameList) === $lockCnt) {
                    $isOwnLock = config('const.flg.on');
                } else {
                    DB::rollBack();
                }
                
            }

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }


        return view('Shipping.shipping-instruction') 
                ->with('matterList',    $matterList)
                ->with('isOwnLock',     $isOwnLock)
                ->with('lockData',      $lockData)
                ->with('mainData',      $mainData)
                ->with('treeDataList',    json_encode($treeDataList))
                ->with('shippingLimitList', json_encode(config('const.shippingLimitList')))
                ->with('gridDataList',      json_encode($gridDataList))
                ->with('shipmentKindDataList',  json_encode($shipmentKindDataList))
                ->with('reserveList', json_encode($reserveList))
                ;
    }
    
    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)  
    {
        $result = array();

        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $result = $this->getTabData($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($result);
    }

    /**
     * タブ内のデータを取得する
     * @param $params 検索パラメータ
     */
    private function getTabData($params){
        $result = [
            'treeDataList'          => [],  // 階層
            'shipmentDetailList'    => [],  // グリッドやヘッダー
            'shipmentKindDataList'  => [],  // 届け先種別
        ];

        $matterNo               = $params['matter_no'];
        $arrivalPlanDateFrom    = $params['arrival_plan_date_from'];
        $arrivalPlanDateTo      = $params['arrival_plan_date_to'];
        $hopeArrivalPlanDateFrom= $params['hope_arrival_plan_date_from'];
        $hopeArrivalPlanDateTo  = $params['hope_arrival_plan_date_to'];

        $Matter = new Matter();
        $Reserve = new Reserve();
        $QuoteDetail = new QuoteDetail();
        $CalendarData = new CalendarData();
        $ShipmentDetail = new ShipmentDetail();
        $WarehouseMove = new WarehouseMove();
        $matterData = $Matter->getByMatterNo($matterNo);
        if($matterData !== null){
            $tmpReservList = $Reserve->getReserveInsutructionList(
                $matterData->id,
                $arrivalPlanDateFrom,
                $arrivalPlanDateTo,
                $hopeArrivalPlanDateFrom,
                $hopeArrivalPlanDateTo
            );

            $groupData = [];
            foreach($tmpReservList as $data){
                $data->matter_address = $data->address1.  $data->address2;
                $data->next_businessday = null;
                if(!empty($data->arrival_plan_date)){
                    $calendar = $CalendarData->getNextBusinessDay($data->arrival_plan_date);
                    if($calendar !== null){
                        $data->next_businessday = $calendar->calendar_date;
                    }
                }

                $groupData[$data->from_warehouse_id][$data->quote_detail_id][] = $data;
            }

            $reserveIds = [];
            foreach($groupData as $data){
                foreach($data as $warehouseGridData){
                    $groupByQuoteDetail = null;
                    $Ids = [];
                    foreach($warehouseGridData as $key => $row){
                        $this->convertGridData($groupByQuoteDetail, $key, $row);
                        $Ids[] = $row->reserve_id;
                        $reserveIds[] = $row->reserve_id;
                    }
                    $groupByQuoteDetail->reserve_ids = $Ids;

                    $result['shipmentDetailList'][$groupByQuoteDetail->from_warehouse_id][] = $groupByQuoteDetail;
                }
            }

            if(count($tmpReservList) > 0){
                foreach($result['shipmentDetailList'] as $key => $data){
                    $idList = [];
                    foreach($data as $record){
                        //$idList[] = $record->quote_detail_id;
                        $tmp = explode('_', $record->tree_path);
                        $idList = array_merge($idList, $tmp);
                    }
                    
                    $result['treeDataList'][$key] = $QuoteDetail->getTreeData(null,null,config('const.flg.on'),null,array_unique(array_map('intval', $idList)));
                    $result['shipmentKindDataList'][$key] = 
                    $this->createShipmentKindList(
                        $tmpReservList[0]->address_id,
                        $tmpReservList[0]->customer_id,
                        $key
                    );
                }
            }

            $allReserveList = $ShipmentDetail->getByReserveId($reserveIds);
            $allReserveList = $allReserveList->toArray();
            
            foreach($allReserveList as $key => $row) {
                // 移管予定数
                $sumPlanData = $WarehouseMove->getNotFinishTransfer($row);
                $allReserveList[$key]['sum_move_quantity'] = Common::nullorBlankToZero($sumPlanData['sum_quantity']);
            }

            $result['allReserveList'] = $allReserveList;
        }
        
        return $result;
    }

    /**
     * いきなり納品
     * @param Request $request
     * @return type
     */
    public function delivery(Request $request) 
    {
        $result = array('status' => false, 'message' => '', 'id' => 0, 'is_delete' => false);
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        $Loading = new Loading();
        $Reserve = new Reserve();
        $Delivery = new Delivery();
        $SystemUtil = new SystemUtil();


        // リクエストデータ取得
        $params = $request->request->all();
        $gridDataList = json_decode($params['grid_data'], true);
        $shipmentId = $params['shipment_id'];
        $shipmentList = json_decode($params['shipment_list'], true);

        // 入力チェック
        $this->isValidShipment($request);

        DB::beginTransaction();
        
        try {
            // 出荷指示データを登録可能形式に変換
            $shipmentData = $this->convertShipmentData($params);
            $shipmentData['loading_finish_flg'] = config('const.flg.on');

            $newFlg = false;
            if (empty($shipmentId)) {
                $newFlg = true;
            }
            
            if($newFlg){
                // 出荷指示登録
                $shipmentId = $Shipment->add($shipmentData);
                if(!$shipmentId){
                    throw new \Exception(config('message.error.save'));
                }

                // 出荷指示明細加工
                $isOrderShipment = false;
                $isStockOver = false;
                $addShipmentDetailDataList = [];
                foreach($shipmentList as $record){
                    $tmpSaveData = $this->convertSaveShipmentDetailData($shipmentId, $record);
                    $tmpSaveData['loading_finish_flg'] = config('const.flg.on');
                    $tmpSaveData['delivery_finish_flg'] = config('const.flg.on');

                    // 有効引当数 - 未処理の出荷指示の合計 >= 入力値
                    $reserveData = $Reserve->getById($record['reserve_id']);
                    $untreatedShipment = $ShipmentDetail->getUntreatedQuantityByReserveId($record['reserve_id']);
                    $remQuantity = Common::nullorBlankToZero($reserveData['reserve_quantity_validity']) - Common::nullorBlankToZero($untreatedShipment['sum_shipment_quantity']);
                    if ($remQuantity < $record['shipment_quantity']) {
                        throw new \Exception(config('message.error.shipment.over_reserve'));
                    }
                    // 全て自社在庫の出荷かチェック
                    if ($record['stock_flg'] == config('const.stockFlg.val.order')) {
                        $isOrderShipment = true;
                    }
                    
                    // 在庫数チェック
                    if (!$this->checkOwnStock($record)) {
                        $isStockOver = true;
                    }

                    if(count($tmpSaveData['add']) >= 1){
                        $addShipmentDetailDataList = array_merge($addShipmentDetailDataList, $tmpSaveData['add']);
                    }
                }

                if ($isOrderShipment) {
                    throw new \Exception(config('message.error.shipment.order_sudden_shipment'));
                }
                if ($isStockOver) {
                    throw new \Exception(config('message.error.shipment.over_stock'));
                }
                // foreach($gridDataList as $record){
                //     $tmpSaveData = $this->convertSaveShipmentDetailData($shipmentId, $record);
                //     if(count($tmpSaveData['add']) >= 1){
                //         $addShipmentDetailDataList = array_merge($addShipmentDetailDataList, $tmpSaveData['add']);
                //     }
                // }
                // 出荷指示明細登録
                if(count($addShipmentDetailDataList) >= 1){
                    foreach($addShipmentDetailDataList as $key => $record) {
                        $shipmentDetailId = $ShipmentDetail->add($addShipmentDetailDataList);

                        // 納品データ作成
                        $deliveryNo = "";
                        $lastDeliveryNo = $Delivery->getDeliveryNo();
                        if ($lastDeliveryNo != null) {
                            $seqNo = (int)substr($lastDeliveryNo['delivery_no'], -3);
                            $seqNo += 1;
                            $deliveryNo = substr($lastDeliveryNo['delivery_no'], 0, 8) . sprintf('%03d', $seqNo);
                        } else {
                            $deliveryNo = 'NH' . substr(Carbon::now()->format('Y'), -2) .  Carbon::now()->format('md') . '001';
                        }

                        $deliveryData = [];
                        $deliveryData['delivery_no'] = $deliveryNo;
                        $deliveryData['matter_id'] = $shipmentData['matter_id'];
                        $deliveryData['stock_flg'] = config('const.stockFlg.val.stock');
                        $deliveryData['shipment_id'] = $shipmentId;
                        $deliveryData['shipment_detail_id'] = $shipmentDetailId;
                        $deliveryData['loading_id'] = 0;
                        $deliveryData['order_detail_id'] = $record['order_detail_id'];
                        $deliveryData['quote_id'] = $record['quote_id'];
                        $deliveryData['quote_detail_id'] = $record['quote_detail_id'];
                        $deliveryData['shipment_kind'] = $shipmentData['shipment_kind'];
                        $deliveryData['zipcode'] = $shipmentData['zipcode'];
                        $deliveryData['pref'] = $shipmentData['pref'];
                        $deliveryData['address1'] = $shipmentData['address1'];
                        $deliveryData['address2'] = $shipmentData['address2'];
                        $deliveryData['latitude_jp'] = $shipmentData['latitude_jp'];
                        $deliveryData['longitude_jp'] = $shipmentData['longitude_jp'];
                        $deliveryData['latitude_world'] = $shipmentData['latitude_world'];
                        $deliveryData['longitude_world'] = $shipmentData['longitude_world'];
                        $deliveryData['product_id'] = $record['product_id'];
                        $deliveryData['product_code'] = $record['product_code'];
                        $deliveryData['delivery_date'] = Carbon::now();
                        $deliveryData['delivery_quantity'] = $record['shipment_quantity'];

                        $Delivery->add($deliveryData);
        
                        // 在庫から減らす
                        $moveData = [];
        
                        // 引当データ更新
                        $upReserveData = [];


                    }
                }
            }else{
                // 出荷積込があれば変更不可
                if($Loading->isExistByShipmentId($shipmentId)){
                    throw new \Exception(config('message.error.shipment.existLoadingData'));
                }

                $deleteflg = true;
                // 出荷指示更新
                $editData = [];
                $editData['id'] = $shipmentId;
                $editData['matter_id'] = $params['matter_id'];  // 変更無し
                $editData['shipment_kind'] = $shipmentData['shipment_kind'];
                $editData['shipment_kind_id'] = $shipmentData['shipment_kind_id'];
                $editData['zipcode'] = $shipmentData['zipcode'];
                $editData['pref'] = $shipmentData['pref'];
                $editData['address1'] = $shipmentData['address1'];
                $editData['address2'] = $shipmentData['address2'];
                $editData['latitude_jp'] = $shipmentData['latitude_jp'];
                $editData['longitude_jp'] = $shipmentData['longitude_jp'];
                $editData['latitude_world'] = $shipmentData['latitude_world'];
                $editData['longitude_world'] = $shipmentData['longitude_world'];
                $editData['from_warehouse_id'] = $params['from_warehouse_id'];  // 変更無し
                $editData['delivery_date'] = $shipmentData['delivery_date'];
                $editData['delivery_time'] = $shipmentData['delivery_time'];
                $editData['heavy_flg'] = $shipmentData['heavy_flg'];
                $editData['unic_flg'] = $shipmentData['unic_flg'];
                $editData['rain_flg'] = $shipmentData['rain_flg'];
                $editData['transport_flg'] = $shipmentData['transport_flg'];
                $editData['shipment_comment'] = $shipmentData['shipment_comment'];
                //$editData['loading_finish_flg'] = $params['loading_finish_flg'];

                if(!$Shipment->updateById($editData)){
                    throw new \Exception(config('message.error.save'));
                }


            }
            
            $delLock = true;
            if (!$newFlg) {
                // ロック解放
                $keys = array($shipmentId);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            }
            
            if ($delLock) {
                DB::commit();
                $result['status'] = true;
                $result['message'] = config('message.success.save');
                $result['id'] = $shipmentId;
                if(!$newFlg){
                    Session::flash('flash_success', config('message.success.save'));
                }
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result = array('status' => false, 'message' => '', 'id' => 0, 'is_delete' => false);
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        $Loading = new Loading();
        $Reserve = new Reserve();
        $WarehouseMove = new WarehouseMove();

        // リクエストデータ取得
        $params = $request->request->all();
        $gridDataList = json_decode($params['grid_data'], true);
        $shipmentId = $params['shipment_id'];
        $shipmentList = json_decode($params['shipment_list'], true);

        // 入力チェック
        $this->isValidShipment($request);

        DB::beginTransaction();
        
        try {
            // 出荷指示データを登録可能形式に変換
            $shipmentData = $this->convertShipmentData($params);

            $newFlg = false;
            if (empty($shipmentId)) {
                $newFlg = true;
            }
            
            if($newFlg){
                // 出荷指示登録
                $shipmentId = $Shipment->add($shipmentData);
                if(!$shipmentId){
                    throw new \Exception(config('message.error.save'));
                }

                // 出荷指示明細加工
                $addShipmentDetailDataList = [];
                foreach($shipmentList as $record){
                    $tmpSaveData = $this->convertSaveShipmentDetailData($shipmentId, $record);
                    // 有効引当数 - 未処理の出荷指示の合計 - 未処理の移管予定数　>= 入力値
                    $reserveData = $Reserve->getById($record['reserve_id']);
                    $untreatedShipment = $ShipmentDetail->getUntreatedQuantityByReserveId($record['reserve_id']);
                    
                    // 移管予定数
                    $sumPlanData = $WarehouseMove->getNotFinishTransfer($record);
                    // Common::nullorBlankToZero($sumPlanData['sum_quantity']);

                    $remQuantity = Common::nullorBlankToZero($reserveData['reserve_quantity_validity']) - Common::nullorBlankToZero($untreatedShipment['sum_shipment_quantity']) - Common::nullorBlankToZero($sumPlanData['sum_quantity']);

                    if ($remQuantity < $record['shipment_quantity']) {
                        throw new \Exception(config('message.error.shipment.over_reserve'));
                    }

                    if(count($tmpSaveData['add']) >= 1){
                        $addShipmentDetailDataList = array_merge($addShipmentDetailDataList, $tmpSaveData['add']);
                    }
                }
                // foreach($gridDataList as $record){
                //     $tmpSaveData = $this->convertSaveShipmentDetailData($shipmentId, $record);
                //     if(count($tmpSaveData['add']) >= 1){
                //         $addShipmentDetailDataList = array_merge($addShipmentDetailDataList, $tmpSaveData['add']);
                //     }
                // }
                // 出荷指示明細登録
                if(count($addShipmentDetailDataList) >= 1){
                    if(!$ShipmentDetail->addList($addShipmentDetailDataList)){
                        throw new \Exception(config('message.error.save'));
                    }
                }
            }else{
                // 出荷積込があれば変更不可
                if($Loading->isExistByShipmentId($shipmentId)){
                    throw new \Exception(config('message.error.shipment.existLoadingData'));
                }

                $deleteflg = true;
                // 出荷指示更新
                $editData = [];
                $editData['id'] = $shipmentId;
                $editData['matter_id'] = $params['matter_id'];  // 変更無し
                $editData['shipment_kind'] = $shipmentData['shipment_kind'];
                $editData['shipment_kind_id'] = $shipmentData['shipment_kind_id'];
                $editData['zipcode'] = $shipmentData['zipcode'];
                $editData['pref'] = $shipmentData['pref'];
                $editData['address1'] = $shipmentData['address1'];
                $editData['address2'] = $shipmentData['address2'];
                $editData['latitude_jp'] = $shipmentData['latitude_jp'];
                $editData['longitude_jp'] = $shipmentData['longitude_jp'];
                $editData['latitude_world'] = $shipmentData['latitude_world'];
                $editData['longitude_world'] = $shipmentData['longitude_world'];
                $editData['from_warehouse_id'] = $params['from_warehouse_id'];  // 変更無し
                $editData['delivery_date'] = $shipmentData['delivery_date'];
                $editData['delivery_time'] = $shipmentData['delivery_time'];
                $editData['heavy_flg'] = $shipmentData['heavy_flg'];
                $editData['unic_flg'] = $shipmentData['unic_flg'];
                $editData['rain_flg'] = $shipmentData['rain_flg'];
                $editData['transport_flg'] = $shipmentData['transport_flg'];
                $editData['shipment_comment'] = $shipmentData['shipment_comment'];
                //$editData['loading_finish_flg'] = $params['loading_finish_flg'];

                if(!$Shipment->updateById($editData)){
                    throw new \Exception(config('message.error.save'));
                }

                // 出荷指示明細加工
                // $addShipmentDetailDataList = [];
                // $editShipmentDetailDataList = [];
                // $delShipmentDetailDataList = [];
                // foreach($gridDataList as $record){
                //     $tmpSaveData = $this->convertSaveShipmentDetailData($shipmentId, $record);
                //     if(count($tmpSaveData['add']) >= 1){
                //         $addShipmentDetailDataList = array_merge($addShipmentDetailDataList, $tmpSaveData['add']);
                //     }
                //     if(count($tmpSaveData['edit']) >= 1){
                //         $editShipmentDetailDataList = array_merge($editShipmentDetailDataList, $tmpSaveData['edit']);
                //     }
                //     if(count($tmpSaveData['del']) >= 1){
                //         $delShipmentDetailDataList = array_merge($delShipmentDetailDataList, $tmpSaveData['del']);
                //     }
                // }
                
                // // 出荷指示明細登録
                // if(count($addShipmentDetailDataList) >= 1){
                //     if(!$ShipmentDetail->addList($addShipmentDetailDataList)){
                //         throw new \Exception(config('message.error.save'));
                //     }
                //     $deleteflg = false;
                // }
                // // 出荷指示明細更新
                // foreach($editShipmentDetailDataList as $data){
                //     if(!$ShipmentDetail->updateById($data)){
                //         throw new \Exception(config('message.error.save'));
                //     }
                //     $deleteflg = false;
                // }
                // // 出荷指示明細削除
                // foreach($delShipmentDetailDataList as $data){
                //     if(!$ShipmentDetail->physicalDeleteById($data['id'])){
                //         throw new \Exception(config('message.error.save'));
                //     }
                // }
                // // 出荷指示削除
                // if($deleteflg){
                //     if(!$Shipment->deleteByShipmentId($shipmentId)){
                //         throw new \Exception(config('message.error.save'));
                //     }else{
                //         $result['is_delete'] = true;
                //     }
                // }

            }
            
            $delLock = true;
            if (!$newFlg) {
                // ロック解放
                $keys = array($shipmentId);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            }
            
            if ($delLock) {
                DB::commit();
                $result['status'] = true;
                $result['message'] = config('message.success.save');
                $result['id'] = $shipmentId;
                if(!$newFlg){
                    Session::flash('flash_success', config('message.success.save'));
                }
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * POSTされた出荷指示データを登録可能な形式に変換
     * @param array $params
     * @return array
     */
    private function convertShipmentData(array $params) : array{
        $shipmentData = array();
        $shippingLimitCheck = $params['shipping_limit_check_list'];
        $shippingLimitCheckList = $shippingLimitCheck !== null ? explode(',', $shippingLimitCheck) : array();

        foreach($params as $key => $val){
            if($key !== 'grid_data' && $key !== 'shipping_limit_check_list'){
                $shipmentData[$key] = $val;
            }
        }
        // チェックボックスにフラグセット
        foreach(config('const.shippingLimitList') as $column => $record){
            if(in_array($record['val'], $shippingLimitCheckList)){
                $shipmentData[$column] = config('const.flg.on');
            }else{
                $shipmentData[$column] = config('const.flg.off');
            }
        }
        $shipmentData['loading_finish_flg'] = config('const.flg.off');

        return $shipmentData;
    }

    /**
     * バリデーションチェック 出荷指示
     * @param Request $request
     * @return type
     */
    private function isValidShipment(Request $request)
    {
        $this->validate($request, [
            //'matter_id' => 'numeric',
            'shipment_kind' => 'required',
            'zipcode'   => 'max:7',
            //'pref'      =>'numeric',
            'address1'  => 'required|max:50',
            'address2'  => 'max:50',
            //'latitude_jp'   => 'numeric',
            //'longitude_jp'  => 'numeric',
            //'latitude_world'=> 'numeric',
            //'longitude_world'   => 'numeric',
            //'from_warehouse_id' => 'numeric',
            'delivery_date' => 'required',
            'delivery_time' => 'required|numeric',
        ]);
    }

    /**
     * お届け先種類のリストデータを返す
     * @param $addressId 得意先に紐づく住所ID
     * @param $customerId 得意先に紐づく得意先ID
     * @param $warehouseId 引当に紐づく倉庫ID
     */
    private function createShipmentKindList($addressId, $customerId, $warehouseId){
        $result = [];
        $Address = new Address();
        $AddressData = $Address->getById($addressId);
        if($AddressData !== null){
            $result[] = $this->convertShipmentKindRecord('matter', $AddressData);
        }

        $Customer = new Customer();
        $CustomerData = $Customer->getById($customerId);
        if($CustomerData !== null){
            $result[] = $this->convertShipmentKindRecord('customer', $CustomerData);
        }

        $CustomerBranch = new CustomerBranch();
        $CustomerBranchData = $CustomerBranch->getById($customerId);
        foreach($CustomerBranchData as $key => $record){
            $result[] = $this->convertShipmentKindRecord('customer_branch', $record);
        }

        $Warehouse = new Warehouse();
        $warehouseData = $Warehouse->getById($warehouseId);
        $result[] = $this->convertShipmentKindRecord('take_off', $warehouseData);

        return $result;
    }

    /**
     * お届け先種類のリストを加工する
     * @param $kbn constに定義しているお届け先種類のキー
     * @param $record 加工対象の1レコードのデータ
     */
    private function convertShipmentKindRecord($kbn, $record){
        $result = [];
        $LIST = self::SHIPMENT_KIND_LIST[$kbn];
        foreach($LIST as $column => $val){
            if($val === ''){
                if($column === 'unique_key'){
                    $result[$column] = $LIST['shipment_kind'].'_'.$record->id;
                }else if($column === 'lbl'){
                    switch($kbn){
                        case 'customer':
                            $result[$column] = $record->customer_name;
                        break;
                        case 'customer_branch':
                            $result[$column] = $record->branch_name;
                        break;
                    }
                }else{
                    $result[$column] = $record->$column;
                }
            }else{
                $result[$column] = $val;
            }
        }
        return $result;
    }

    /**
     * グリッドに表示するデータの加工
     * 
     * @param $groupByQuoteDetail グリッドの1行のデータ
     * @param $key 見積もり明細IDごとの連番
     * @param $row DBから取得した1行のデータ
     * @param $isEdit 編集時のデータ加工か
     */
    private function convertGridData(&$groupByQuoteDetail, $key, $row, $isEdit = false){
        if($key === 0){
            // 初期化
            $groupByQuoteDetail = $row;
            $groupByQuoteDetail->order_reserve_id   = 0;    // 引当ID
            $groupByQuoteDetail->stock_reserve_id   = 0;    // 引当ID
            $groupByQuoteDetail->keep_reserve_id    = 0;    // 引当ID

            $groupByQuoteDetail->order_shipment_detail_id   = 0;    // 出荷指示ID
            $groupByQuoteDetail->stock_shipment_detail_id   = 0;    // 出荷指示ID
            $groupByQuoteDetail->keep_shipment_detail_id    = 0;    // 出荷指示ID

            $groupByQuoteDetail->order_reserve_quantity = 0;    // 引当数
            $groupByQuoteDetail->stock_reserve_quantity = 0;    // 引当数
            $groupByQuoteDetail->keep_reserve_quantity  = 0;    // 引当数
        }
        // 引当レコードの在庫種別によって分岐させる
        switch($row->stock_flg){
            case config('const.stockFlg.val.order'):
                $groupByQuoteDetail->order_reserve_id       = $row->reserve_id;         // 引当ID
                $groupByQuoteDetail->order_reserve_quantity += $this->rmNullZero((int)$row->reserve_quantity);   // 引当数量
                $groupByQuoteDetail->order_reserve_quantity_validity += $this->rmNullZero((int)$row->reserve_quantity) - $this->rmNullZero((int)$row->reserve_quantity_validity);   // 引当数量
                $groupByQuoteDetail->order_detail_id        = $row->order_detail_id;    // 発注明細ID
                $groupByQuoteDetail->sum_order_shipment_quantity    += $this->rmNullZero((int)$row->sum_shipment_quantity);   // 出荷指示数合計
                $groupByQuoteDetail->sum_order_delivery_quantity    += $this->rmNullZero((int)$row->sum_delivery_quantity);   // 納品数合計
                $groupByQuoteDetail->order_stock_quantity           = $row->order_stock_quantity;   // 発注数(管理数)
                $groupByQuoteDetail->arrival_quantity       += $row->arrival_quantity;       // 入荷数
                $groupByQuoteDetail->arrival_plan_date      = $row->arrival_plan_date;      // 入荷予定日
                $groupByQuoteDetail->hope_arrival_plan_date = $row->hope_arrival_plan_date; // 希望出荷予定日

                if($isEdit){
                    $groupByQuoteDetail->order_shipment_detail_id   = $row->shipment_detail_id; // 出荷指示ID
                    $groupByQuoteDetail->order_shipment_quantity    = $row->shipment_quantity;  // 出荷指示数
                }
            break;
            case config('const.stockFlg.val.stock'):
                $groupByQuoteDetail->stock_reserve_id       = $row->reserve_id;         // 引当ID
                $groupByQuoteDetail->stock_reserve_quantity += $this->rmNullZero((int)$row->reserve_quantity);   // 引当数量
                $groupByQuoteDetail->stock_reserve_quantity_validity += $this->rmNullZero((int)$row->reserve_quantity) - $this->rmNullZero((int)$row->reserve_quantity_validity);   // 引当数量
                $groupByQuoteDetail->sum_stock_shipment_quantity    += $this->rmNullZero($row->sum_shipment_quantity);   // 出荷指示数合計
                $groupByQuoteDetail->sum_stock_delivery_quantity    += $this->rmNullZero($row->sum_delivery_quantity);   // 納品数合計
                $groupByQuoteDetail->hope_arrival_plan_date = $row->start_date; // 希望出荷予定日

                if($isEdit){
                    $groupByQuoteDetail->stock_shipment_detail_id   = $row->shipment_detail_id; // 出荷指示ID
                    $groupByQuoteDetail->stock_shipment_quantity    = $row->shipment_quantity;  // 出荷指示数
                }
            break;
            case config('const.stockFlg.val.keep'):
                $groupByQuoteDetail->keep_reserve_id = $row->reserve_id;                // 引当ID
                $groupByQuoteDetail->keep_reserve_quantity += $this->rmNullZero((int)$row->reserve_quantity);    // 引当数量
                $groupByQuoteDetail->keep_reserve_quantity_validity += $this->rmNullZero((int)$row->reserve_quantity) - $this->rmNullZero((int)$row->reserve_quantity_validity);   // 引当数量
                $groupByQuoteDetail->sum_keep_shipment_quantity += $this->rmNullZero($row->sum_shipment_quantity);   // 出荷指示数合計
                $groupByQuoteDetail->sum_keep_delivery_quantity += $this->rmNullZero($row->sum_delivery_quantity);   // 納品数合計
                $groupByQuoteDetail->hope_arrival_plan_date = $row->start_date; // 希望出荷予定日

                if($isEdit){
                    $groupByQuoteDetail->keep_shipment_detail_id    = $row->shipment_detail_id; // 出荷指示ID
                    $groupByQuoteDetail->keep_shipment_quantity     = $row->shipment_quantity;  // 出荷指示数
                }
            break;
        }
    }

    /**
     * 出荷指示明細データを登録可能な形式に変換する
     * 
     * @param $shipmentId 
     * @param $record
     * @return $result
     */
    private function convertSaveShipmentDetailData($shipmentId, $record){
        $result = [
            'add'   =>[],
            'edit'  =>[],
            'del'   =>[],
        ];
        // foreach(config('const.stockFlg.val') as $key => $stockFlg){
            $data = [];
            $data['id']             = 0;
            $data['shipment_id']    = $shipmentId;
            $data['quote_id']       = $record['quote_id'];
            $data['quote_detail_id']= $record['quote_detail_id'];
            $data['product_id']     = $record['product_id'];
            $data['product_code']   = $record['product_code'];
            $data['reserve_id']     = $record['reserve_id'];
            $data['shipment_quantity']  = $record['shipment_quantity'];
            $data['stock_flg']      = $record['stock_flg'];
            switch($record['stock_flg']){
                case config('const.stockFlg.val.order'):
                    // $data['id']         = $record->order_shipment_detail_id;
                    // $data['reserve_id'] = $record->order_reserve_id;
                    // $data['stock_flg']  = $stockFlg;
                    // $data['shipment_quantity']  = $record->order_shipment_quantity;
                    $data['order_detail_id']    = $record['order_detail_id'];
                break;
                case config('const.stockFlg.val.stock'):
                    // $data['id']         = $record->stock_shipment_detail_id;
                    // $data['reserve_id'] = $record->stock_reserve_id;
                    // $data['stock_flg']  = $stockFlg;
                    // $data['shipment_quantity']  = $record->stock_shipment_quantity;
                    $data['order_detail_id']    = 0;
                break;
                case config('const.stockFlg.val.keep'):
                    // $data['id']         = $record->keep_shipment_detail_id;
                    // $data['reserve_id'] = $record->keep_reserve_id;
                    // $data['stock_flg']  = $stockFlg;
                    // $data['shipment_quantity']  = $record->keep_shipment_quantity;
                    $data['order_detail_id']    = 0;
                break;
            }
            if(!empty($data['reserve_id'])){
                if(empty($data['id'])){
                    // 出荷指示IDがない(新規登録の場合)
                    if(!empty($data['shipment_quantity'])){
                        $result['add'][]    = $data;
                    }
                }else{
                    if(!empty($data['shipment_quantity'])){
                        // 更新
                        $result['edit'][]   = $data;
                    }else{
                        // 削除
                        $result['del'][]    = $data;
                    }
                }
            }  
        // }
        return $result;
    }

    /**
     * 在庫数チェック
     *
     * @param [type] $record
     * @return void
     */
    private function checkOwnStock($record) 
    {
        $result = false;
        $Reserve = new Reserve();
        $ProductstockShelf = new ProductstockShelf();

        $reserveData = $Reserve->getById($record['reserve_id']);

        $productId = $record['product_id'];
        $warehouseId = $reserveData['from_warehouse_id'];

        $stockData = $ProductstockShelf->getOwnStock($productId, $warehouseId, $record['stock_flg']);
        // 在庫数チェック
        if ($record['shipment_quantity'] <= $stockData['inventory_quantity']) {
            $result = true;
        }

        return $result;
    }

    /**
     * nullの場合0を返す
     * @param $data
     */
    private function rmNullZero($data){
        $result = 0;
        if($data != null){
            $result = $data;
        }
        return $result;
    }
}