<?php
namespace App\Http\Controllers;

use App\Libs\Common;
use Illuminate\Http\Request;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Loading;
use App\Models\Delivery;
use Session;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Base;
use App\Models\Company;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Support\Carbon;
use App\Models\Customer;
use App\Models\Matter;
use App\Models\StaffDepartment;
use App\Models\Department;
use App\Models\Order;
use App\Models\Product;;
use App\Models\Supplier;
use App\Models\Staff;
use App\Models\Warehouse;
use App\Models\General;
use App\Models\LockManage;
use DB;
use App\Libs\SystemUtil;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ShippingDeliveryListController extends Controller
{
    const TABLE_NAME = 't_shipment';
    const ORDER_DELIVERY_PREFIX = 'order';
    const ORDER_DETAIL_DELIVERY_PREFIX = 'orderDetail';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 
     * 初期表示
     * @return type
     */
    public function index()
    {
        // 出荷リスト取得
        $Shipment = new Shipment();
        $Customer = new Customer();
        $StaffDepartment = new StaffDepartment();
        $Matter = new Matter();
        $Staff = new Staff();
        $Product = new Product();
        $Department = new Department();
        $Supplier = new Supplier();
        $Warehouse = new Warehouse();
        $Order = new Order();
        $General = new General();

        
        $fieldData = $Shipment->getFieldList();
        $customerData = $Customer->getComboList();
        $departmentData = $Department->getComboList();
        $orderData = $Order->getComboList();
        $supplierData = $Supplier->getComboList();
        // $productData = $Product->getComboList();
        $matterData = $Matter->getComboList();
        $staffData = $Staff->getComboList();
        $warehouseData = $Warehouse->getComboList();
        $issueTimeData = $General->getCategoryList(config('const.general.issue'));
        // 担当者部門リスト取得
        $StaffDepartment = new StaffDepartment();
        $staffDepartmentList = $StaffDepartment->getCurrentTermList();

        // 検索条件　初期値
        $Department = $StaffDepartment->getById(Auth::id())->where('main_flg', config('const.flg.on'))->first();
        $initSearchParams = collect([
            'department_name' => $Department['department_name'],
            'issue_from_date' => Carbon::today()->subDay(3)->format('Y/m/d'),
            'issue_to_date' => Carbon::today()->addDay(3)->format('Y/m/d'),
        ]);
    
        return view('Shipping.shipping-delivery-list')
                ->with('customerData', $customerData)
                ->with('fieldData', $fieldData)
                ->with('departmentData', $departmentData)
                ->with('supplierData', $supplierData)
                ->with('orderData', $orderData)
                // ->with('productData', $productData)
                ->with('matterData', $matterData)
                ->with('staffData', $staffData)
                ->with('warehouseData', $warehouseData)
                ->with('issueTimeData', $issueTimeData)
                ->with('staffDepartmentList', $staffDepartmentList)
                ->with('initSearchParams', $initSearchParams)
                ;
    }

    /**
     * 物理削除
     * @param Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $result['status'] = false;
        $result['message'] = '';
        $deleteResult = false;

        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            $LockManage = new LockManage();
            $Shipment = new Shipment();
            $ShipmentDetail = new ShipmentDetail();
            $Loading = new Loading();

            if (!$Loading->isExistByShipmentId($params['id'])) {
                // 出荷積込が存在しない
                $deleteResult = $Shipment->deleteByShipmentId($params['id']);
                $deleteResult = $ShipmentDetail->deleteByShipmentId($params['id']);
            } else {
                // 出荷積込が存在する
                $result['message'] = config('message.error.shipment.loaded_shipment');
                throw new \Exception();
            }

            DB::commit();
            $result['status'] = true;
            Session::flash('flash_success', config('message.success.delete'));
            
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['message'] == '') {
                $result['message'] = config('message.error.error');
            }
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }

     /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            // 一覧データ取得
            $Shipment = new Shipment();
            $ShipmentDetail = new ShipmentDetail();
            // 親グリッドデータ取得
            $parentData = $Shipment->getShipmentDeliveryList($params);
            $shipmentList = $parentData['shipment'];
            
            $detailList = null;
            if (count($shipmentList) > 0) {
                $shipmentIds = [];
                if ($parentData['shipment'] != null) {
                    $shipmentIds = $parentData['shipment']->pluck('id')->toArray();
                }
                $params['shipment_ids'] = $shipmentIds;

                // 出荷指示がある分の子グリッドデータを取得
                if ($parentData['shipment']->count() >= 1) {
                    $detailList = $ShipmentDetail->getDetailList($params);
                }
            }

            // 直送分の子グリッドデータ取得
            $orderIds = [];
            $orderDetailList = null;
            if ($parentData['order'] != null && $parentData['order']->count() >= 1) {
                $orderIds = $parentData['order']->pluck('id')->toArray();
                $orderDetailList = $ShipmentDetail->getDetailListByOrder($params, $orderIds);
            }
            

            $shipmentList = $shipmentList->keyBy('id');
            // $shipmentIDs = $shipments->pluck('id')->toArray();

            // $detailList = $detailList->keyBy('shipment_detail_id');
            // $allDetailIDs = $detailList->pluck('shipment_detail_id')->toArray();

            // 明細の入荷状況取得, Collectionのキーを明細IDに変更
            // $arrival = $ShipmentDetail->getArrivalStatus($allDetailIDs);

            // 明細の出荷状況取得, Collectionのキーを明細IDに変更
            // $shipping = $ShipmentDetail->getShippingStatus($allDetailIDs);

            // 明細の納品状況取得, Collectionのキーを明細IDに変更
            // $delivery = $ShipmentDetail->getDeliveryStatus($allDetailIDs);

            // foreach ($detailList as $key => $row) {
            //     $rackStr = '';

            //     if (!empty($detailList[$key]['shelf_area'])) {
            //         $rackStr .= $detailList[$key]['shelf_area'];
            //     }

            //     if (!empty($detailList[$key]['shelf_steps'])) {
            //         $rackStr .= ' '. $detailList[$key]['shelf_steps']. '段目';
            //     }
            //     $detailList[$key]['rack'] = $rackStr;
            // }

            // ステータス取得
            foreach ($shipmentList as $key => $value) {
                $details = $detailList->where('shipment_id', '=', $key);
                $count = 0;
                $arrivalCnt = 0;
                $shippingCnt = 0;
                $deliveryCnt = 0;
                $arrivalSum = 0;
                $shippingSum = 0;
                $deliverySum = 0;
                $quoteSum = 0;
                $partArrivalFlg = false;
                $firstProductName = '';
                // 出荷指示明細のステータス取得
                foreach ($details as $keys => $data) {
                    // $arrival = $arrival->keyBy('id');
                    // $shipping = $shipping->keyBy('id');
                    // $delivery = $delivery->keyBy('id');
                    $quoteSum += (int)$details[$keys]['quote_quantity'];

                    if($firstProductName === ''){
                        $firstProductName = Common::nullToBlank($data['product_name']);
                    }

                    // 入荷
                    if (isset($details[$keys])) {
                        $arrivalKey = $details[$keys]['arrival_status'];
                        $details[$keys]['arrival_status'] = array(
                            'id' => $details[$keys]['shipment_detail_id'],
                            'text' => config('const.shippingDeliveryStatus.arrival.'. $arrivalKey. '.text'),
                            'class' => config('const.shippingDeliveryStatus.arrival.'. $arrivalKey. '.class'),
                        );
                        if ($arrivalKey == config('const.shippingDeliveryStatus.status.done_arrival')
                            || $arrivalKey == config('const.shippingDeliveryStatus.status.stock_reserve')) {
                            $arrivalCnt++;
                        }
                        if ($arrivalKey == config('const.shippingDeliveryStatus.status.part_arrival')) {
                            $partArrivalFlg = true;
                        }
                    }

                    // 出荷
                    if (isset($details[$keys])) {
                        $shippingKey = $details[$keys]['shipping_status'];
                        $shippingSum = (int)$shippingSum += (int)$details[$keys]['shipment_quantity'];
                        // if ((float)$details[$keys]['quote_quantity'] == (float)$details[$keys]['shipment_quantity']) {
                        //     $shippingCnt++;
                        // }
                        if ($details[$keys]['loading_finish_flg'] == config('const.flg.on') && ($details[$keys]['shipment_kind'] == config('const.shipmentKind.val.maker') || $details[$keys]['shipment_kind'] == config('const.shipmentKind.val.takeoff'))) {
                            $details[$keys]['shipping_status'] = array(
                                'id' => $details[$keys]['shipment_detail_id'],
                                'text' => $details[$keys]['delivery_staff'],
                                'time' => $details[$keys]['delivery_created_at'],
                                'class' => config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.class'),
                            );
                            $shippingCnt++;
                        } else if ($shippingKey == config('const.shippingDeliveryStatus.status.done_shipping')) {
                            $details[$keys]['shipping_status'] = array(
                                'id' => $details[$keys]['shipment_detail_id'],
                                'text' => $details[$keys]['shipping_staff'],
                                'time' => $details[$keys]['shipping_created_at'],
                                'class' => config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.class'),
                            );
                            $shippingCnt++;
                        } else {
                            $details[$keys]['shipping_status'] = array(
                                'id' => $details[$keys]['shipment_detail_id'],
                                'text' => config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.text'),
                                'class' => config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.class'),
                            );
                        }
                    }

                    // 納品
                    if (isset($details[$keys])) {
                        $deliveryKey = $details[$keys]['delivery_status'];
                        $deliverySum = (int)$deliverySum + $details[$keys]['delivery_quantity'];
                        // if ((float)$details[$keys]['quote_quantity'] == (float)$details[$keys]['shipment_quantity']) {
                        //     $deliveryCnt++;
                        // }
                        if ($deliveryKey == config('const.shippingDeliveryStatus.status.done_delivery')) {
                            $details[$keys]['delivery_status'] = array(
                                'id' => $details[$keys]['shipment_detail_id'],
                                'text' => $details[$keys]['delivery_staff'],
                                'time' => $details[$keys]['delivery_created_at'],
                                'class' => config('const.shippingDeliveryStatus.delivery.'. $deliveryKey. '.class'),
                            );
                            $deliveryCnt++;
                        } else {
                            $details[$keys]['delivery_status'] = array(
                                'id' => $details[$keys]['shipment_detail_id'],
                                'text' => config('const.shippingDeliveryStatus.delivery.'. $deliveryKey. '.text'),
                                'class' => config('const.shippingDeliveryStatus.delivery.'. $deliveryKey. '.class'),
                            );
                        }
                    }
                }

                $count = count($details);
                // $arrival = $arrival->keyBy('shipment_id');
                // $shipping = $shipping->keyBy('shipment_id');
                // $delivery = $delivery->keyBy('shipment_id');
                // $detailData = $detailList->keyBy('shipment_id');
                // 入荷
                // $arrivalKey = $detailData[$key]['arrival_status'];
                if ($count == $arrivalCnt){
                    // 詳細全てが入荷済
                    $shipmentList[$key]['arrival_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.arrival.done_arrival.text'),
                        'class' => config('const.shippingDeliveryStatus.arrival.done_arrival.class'),
                        'val' => config('const.arrivalStatus.val.complete'),
                    );
                } else if($count > $arrivalCnt && $arrivalCnt != config('const.arrivalStatus.val.not_arrival') || $partArrivalFlg) {
                    // 一部入荷
                    $shipmentList[$key]['arrival_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.arrival.part_arrival.text'),
                        'class' => config('const.shippingDeliveryStatus.arrival.part_arrival.class'),
                        'val' => config('const.arrivalStatus.val.part_arrival'),
                    );
                } else {
                    // 未入荷
                    $shipmentList[$key]['arrival_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.arrival.not_arrival.text'),
                        'class' => config('const.shippingDeliveryStatus.arrival.not_arrival.class'),
                        'val' => config('const.arrivalStatus.val.not_arrival'),
                    );
                }
                // 出荷
                // $shippingKey = $detailData[$key]['shipping_status'];
                if ($count == $shippingCnt){
                    // 詳細全てが出荷済
                    $shipmentList[$key]['shipping_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.shipping.done_shipping.text'),
                        'class' => config('const.shippingDeliveryStatus.shipping.done_shipping.class'),
                        'val' => config('const.shippingStatus.val.complete'),
                    );
                } else if($count > $shippingCnt && $shippingCnt != 0) {
                    // 一部出荷済
                    $shipmentList[$key]['shipping_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.shipping.part_shipping.text'),
                        'class' => config('const.shippingDeliveryStatus.shipping.part_shipping.class'),
                        'val' => config('const.shippingStatus.val.part_shipping'),
                    );
                } else {
                    // 未出荷
                    $shipmentList[$key]['shipping_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.shipping.not_shipping.text'),
                        'class' => config('const.shippingDeliveryStatus.shipping.not_shipping.class'),
                        'val' => config('const.shippingStatus.val.not_shipping'),
                    );
                }
                // 納品
                // $deliveryKey = $detailData[$key]['delivery_status'];
                if ($count == $deliveryCnt && $shipmentList[$key]['sum_delivery_quantity'] >= $shipmentList[$key]['sum_shipment_quantity']) {
                    // 納品済
                    $shipmentList[$key]['delivery_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.delivery.done_delivery.text'),
                        'class' => config('const.shippingDeliveryStatus.delivery.done_delivery.class'),
                        'val' => config('const.deliveryStatus.val.complete'),
                    );
                } else if((int)$shipmentList[$key]['sum_delivery_quantity'] < (int)$shipmentList[$key]['sum_shipment_quantity'] && (int)$shipmentList[$key]['sum_delivery_quantity'] > 0) {
                    // 一部納品済
                    $shipmentList[$key]['delivery_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.delivery.part_delivery.text'),
                        'class' => config('const.shippingDeliveryStatus.delivery.part_delivery.class'),
                        'val' => config('const.deliveryStatus.val.part_delivery'),
                    );
                } else {
                    // 未納品
                    $shipmentList[$key]['delivery_status'] = array(
                        'text' => config('const.shippingDeliveryStatus.delivery.not_delivery.text'),
                        'class' => config('const.shippingDeliveryStatus.delivery.not_delivery.class'),
                        'val' => config('const.deliveryStatus.val.not_delivery'),
                    );
                }
                // 明細１レコード目の商品名 ＋ 他(明細数)品目
                if ($count > 1) {
                    $shipmentList[$key]['product_name'] = $firstProductName . ' 他' .($count - 1) . '品目';
                } else {
                    $shipmentList[$key]['product_name'] = $firstProductName;
                }
            }

            $parentDataList = $shipmentList->values()->toArray();
            $detailDataList = [];
            if (!is_null($detailList)) {
                $detailDataList = $detailList->toArray();
            }

            $issetList = [];
            if($parentData['order'] !== null && $parentData['order']->count() >= 1){
                $orderDataList = $parentData['order']->toArray();
                
                foreach ($orderDataList as $key => $value) {
                    $orderId = $value['id'];
                    if(!isset($issetList[$orderId])){

                        $issetList[$orderId] = true;
                        $value['id'] = self::ORDER_DELIVERY_PREFIX.$orderId;

                        $details = $orderDetailList->where('shipment_id', '=', $orderId)->toArray();
                        $count = 0;
                        $firstProductName = '';
                        foreach ($details as $detailKey => $detailValue) {
                            $count++;
                            if($count === 1){
                                $firstProductName = Common::nullToBlank($detailValue['product_name']);
                            }

                            $orderDetailId = $detailValue['shipment_detail_id'];
                            $detailValue['shipment_id'] = $value['id'];
                            $detailValue['shipment_detail_id'] = self::ORDER_DETAIL_DELIVERY_PREFIX.$orderDetailId;

                            // 入荷ステータス　入荷済み
                            $detailValue['arrival_status'] = array(
                                'id' => $detailValue['shipment_detail_id'],
                                'text' => config('const.shippingDeliveryStatus.arrival.done_arrival.text'),
                                'class' => config('const.shippingDeliveryStatus.arrival.done_arrival.class'),
                            );

                            // 出荷ステータス　出荷済み
                            $detailValue['shipping_status'] = array(
                                'id' => $detailValue['shipment_detail_id'],
                                'text' => $detailValue['shipping_staff'],
                                'time' => $detailValue['shipping_created_at'],
                                'class' => config('const.shippingDeliveryStatus.shipping.done_shipping.class'),
                            );

                             // 納品ステータス　納品済み
                            $detailValue['delivery_status'] = array(
                                'id' => $detailValue['shipment_detail_id'],
                                'text' => $detailValue['delivery_staff'],
                                'time' => $detailValue['delivery_created_at'],
                                'class' => config('const.shippingDeliveryStatus.delivery.done_delivery.class'),
                            );

                            $detailDataList[] = $detailValue;
                        }



                        // 入荷
                        $value['arrival_status'] = array(
                            'text' => config('const.shippingDeliveryStatus.arrival.done_arrival.text'),
                            'class' => config('const.shippingDeliveryStatus.arrival.done_arrival.class'),
                            'val' => config('const.arrivalStatus.val.complete'),
                        );
                        // 出荷
                        $value['shipping_status'] = array(
                            'text' => config('const.shippingDeliveryStatus.shipping.done_shipping.text'),
                            'class' => config('const.shippingDeliveryStatus.shipping.done_shipping.class'),
                            'val' => config('const.shippingStatus.val.complete'),
                        );
                        // 納品
                        $value['delivery_status'] = array(
                            'text' => config('const.shippingDeliveryStatus.delivery.done_delivery.text'),
                            'class' => config('const.shippingDeliveryStatus.delivery.done_delivery.class'),
                            'val' => config('const.deliveryStatus.val.complete'),
                        );

                        // 商品名のラベル加工
                        if ($count > 1) {
                            $value['product_name'] = $firstProductName . ' 他' .($count - 1) . '品目';
                        } else {
                            $value['product_name'] = $firstProductName;
                        }
                        $parentDataList[] = $value;
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json(['shipments' => $parentDataList, 'details' => $detailDataList]);
    }    
    

    /**
     * 納品サイン/写真表示
     *
     * @param Request $request
     * @param [type] $id
     * @return void
     */
    public function show(Request $request,  $id = null) 
    {
        $Shipment = new Shipment();
        $Delivery = new Delivery();
        $shipmentData   = null;
        $deliveryList   = null;
        if(strpos($id, self::ORDER_DELIVERY_PREFIX) === 0){
            // 発注データがメインテーブルの場合
            $orderId = str_replace(self::ORDER_DELIVERY_PREFIX, '', $id);
            $shipmentData = $Shipment;
            $deliveryList = $Delivery->getDeliveryListByOrderId($orderId);
        }else{
            // 既存　出荷指示がメインテーブルの場合
            $shipmentData = $Shipment->getById($id);
            $deliveryList = $Delivery->getDeliveryListByShipmentId($id);
        }
        
        return view('Shipping.shipping-delivery-list-photo')
                ->with('shipmentData', $shipmentData)
                ->with('deliveryList', $deliveryList)
                ;
    }


    /**
     * 納品書印刷
     *
     * @param Request $request
     * @return void
     */
    public function print(Request $request) 
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();

        DB::beginTransaction();
        $Delivery = new Delivery();
        $Base = new Base();
        $Department = new Department();
        $StaffDepartment = new StaffDepartment();
        $Company = new Company();
        $result = false;
        try {
            // 納品データ取得
            $data = $Delivery->getByDeliveryNo($params['delivery_no']);
            $headerData = $data[0];
            $sd = $StaffDepartment->getMainDepartmentByStaffId($headerData['created_staff_id']);
            $dep = $Department->getById($sd['department_id']);
            $baseData = $Base->getById($dep['base_id']);
            // 会社情報(1レコードしか無い想定)
            $companyInfo = $Company::all()->first();

            $hData = [];
            $detailData = [];
            // 表示用に整形
            $headerData['customer_name'] = SystemUtil::truncate($headerData['customer_name'], 50);
            $headerData['address'] = SystemUtil::truncate($headerData['address'], 40);
            $headerData['staff_name'] = SystemUtil::truncate($headerData['staff_name'], 50);
            $matterName = SystemUtil::truncate($headerData['matter_name'], 45);
            // $matterName = $headerData['matter_name'];            
            $baseName = SystemUtil::truncate($companyInfo->company_name .'　'. $baseData['base_name'], 50);


            // 発行枚数
            $hData['number_total'] = str_pad(1, 2, 0, STR_PAD_LEFT);

            $formatIdNum = "";
            $formatIdIdx = 0;

            $detailTxt = '';
            // 明細情報
            foreach ($data as $key => $row) {
                $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=2";                

                $detailTxt .= '&__format[' . $formatIdIdx . '].product_code=' . rawurlencode(SystemUtil::truncate($row['product_code'], 45));
                $detailTxt .= '&__format[' . $formatIdIdx . '].product_name=' . rawurlencode(SystemUtil::truncate($row['product_name'], 45));
                $detailTxt .= '&__format[' . $formatIdIdx . '].model=' . rawurlencode(SystemUtil::truncate($row['model'], 45));
                $detailTxt .= '&__format[' . $formatIdIdx . '].quantity=' . rawurlencode(SystemUtil::truncate($row['delivery_quantity'], 9));
                $detailTxt .= '&__format[' . $formatIdIdx . '].unit=' . rawurlencode(SystemUtil::truncate($row['unit'], 8));
                $detailTxt .= '&__format[' . $formatIdIdx . '].memo=' . rawurlencode(SystemUtil::truncate($row['memo'], 45));

                // 末尾商品以外に区切り文字挿入
                if (count($data) > $key+1) {
                    $formatIdIdx++;
                    $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=3";
                }

                $formatIdIdx++;
            }

            $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=1";

            // ヘッダー情報
            $headerTxt = '';

            $headerTxt .= "&__format[" . $formatIdIdx . "].delivery_no=" . rawurlencode($headerData['delivery_no']);
            $headerTxt .= "&__format[" . $formatIdIdx . "].delivery_date=" . rawurlencode($headerData['delivery_date']);
            $headerTxt .= "&__format[" . $formatIdIdx . "].customer_name=" . rawurlencode($headerData['customer_name']);
            $headerTxt .= "&__format[" . $formatIdIdx . "].matter_name=" . rawurlencode($matterName);
            $headerTxt .= "&__format[" . $formatIdIdx . "].address=" . rawurlencode($headerData['address']);
            $headerTxt .= "&__format[" . $formatIdIdx . "].base_name=" . rawurlencode($baseName);
            $headerTxt .= "&__format[" . $formatIdIdx . "].staff_name=" . rawurlencode($headerData['staff_name']);

            $isSecure = false;
            if(isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                // HTTPS通信の場合の処理
                $isSecure = true;
            }

            // 印刷用URL生成
            $query = '?__success_redirect_url=googlechrome://&__failure_redirect_url=' . url('/smapri-failed', null, $isSecure) . '&__format_archive_url=' . url(config('const.qrLabelPath') . config('const.printer.printDeliveryFileName'), null, $isSecure) . $formatIdNum;
            // .'&__format_id_number=1&start_number=01';
            $query .=  $detailTxt . $headerTxt;
            // $query .=  '&' . http_build_query($hData);

            // 使用端末がiosか判定
            $isIos = SystemUtil::judgeDevice();

            // ドメイン取得
            // $domain = url('/', null, $isSecure);
            $domain = (empty($_SERVER["HTTPS"]) ? "http://" : "http://"). 'localhost';
            $host = parse_url($domain)['host'];
            $port = '';
            $url = '';
            if ($isIos) {
                // ios端末
                $domain = 'smapri:';
                $url = $domain . '/Format/Print' . $query;
                // return \Response::json(['url' => $url]);
            } else {
                $port = ':' . config('const.printer.port');

                $url = $domain . $port . '/Format/Print' . $query;
                header('Location: '. $url);
                exit();
            }

            // $headers = [
            //     'Content-Type: text/html; charset=UTF-8',
            //     'Host: ' . $host . ':9090'
            // ];

            // $ch = curl_init();
            // curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            // curl_setopt($ch, CURLINFO_HEADER_OUT, true);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            // curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
            // curl_setopt($ch, CURLOPT_POSTFIELDS, $query);
            // curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_URL, $domain . $port . '/Format/Print' . $query);

            // // 戻り値 
            // $result = curl_exec($ch);

            // // 結果
            // $curl_info = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // curl_close($ch);

            // // HTTP_STATUS判定
            // switch ($curl_info) {
            //     case 200:
            //     case 302:
            //         $result = true;
            //         break;
            //     default:
            //         $result = false;
            //         break;
            // }

            // if ($result) {
            // $resultSts = true;
            // }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($url);
    }

    /**
     * エクセル出力
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function exportExcel(Request $request) 
    {
        $response = new StreamedResponse();
        $spreadsheet = null;
        $params = $request->request->all();

        // 一覧データ取得
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        // $Warehouse = new Warehouse();
        // 親グリッドデータ取得
        $parentData = $Shipment->getShipmentDeliveryList($params);
        $shipmentList = $parentData['shipment'];

        $detailList = null;
        if (count($shipmentList) > 0) {
            $shipmentIds = [];
            if ($parentData['shipment'] != null) {
                $shipmentIds = $parentData['shipment']->pluck('id')->toArray();
            }
            $params['shipment_ids'] = $shipmentIds;

            // 出荷指示がある分の子グリッドデータを取得
            if ($parentData['shipment']->count() >= 1) {
                $detailList = $ShipmentDetail->getDetailList($params);
            }
        }
        
        // 直送分の子グリッドデータ取得
        $orderIds = [];
        $orderDetailList = null;
        if ($parentData['order'] != null && $parentData['order']->count() >= 1) {
            $orderIds = $parentData['order']->pluck('id')->toArray();
            $orderDetailList = $ShipmentDetail->getDetailListByOrder($params, $orderIds);
        }

        $shipmentList = $shipmentList->keyBy('id');
        // ステータス取得
        foreach ($shipmentList as $key => $value) {
            $details = $detailList->where('shipment_id', '=', $key);
            $count = 0;
            $arrivalCnt = 0;
            $shippingCnt = 0;
            $deliveryCnt = 0;
            $arrivalSum = 0;
            $shippingSum = 0;
            $deliverySum = 0;
            $quoteSum = 0;
            $partArrivalFlg = false;
            // $firstProductName = '';
            // 出荷指示明細のステータス取得
            foreach ($details as $keys => $data) {
                
                // if($firstProductName === ''){
                //     $firstProductName = Common::nullToBlank($data['product_name']);
                // }

                $quoteSum += (int)$details[$keys]['quote_quantity'];
                // 入荷
                if (isset($details[$keys])) {
                    $arrivalKey = $details[$keys]['arrival_status'];
                    $details[$keys]['arrival_status'] = config('const.shippingDeliveryStatus.arrival.'. $arrivalKey. '.text');

                    if ($arrivalKey == config('const.shippingDeliveryStatus.status.done_arrival')
                        || $arrivalKey == config('const.shippingDeliveryStatus.status.stock_reserve')) {
                        $arrivalCnt++;
                    }
                    if ($arrivalKey == config('const.shippingDeliveryStatus.status.part_arrival')) {
                        $partArrivalFlg = true;
                    }
                }

                // 出荷
                if (isset($details[$keys])) {
                    $shippingKey = $details[$keys]['shipping_status'];
                    $shippingSum = (int)$shippingSum += (int)$details[$keys]['shipment_quantity'];
                    if ($details[$keys]['loading_finish_flg'] == config('const.flg.on') && ($details[$keys]['shipment_kind'] == config('const.shipmentKind.val.maker') || $details[$keys]['shipment_kind'] == config('const.shipmentKind.val.takeoff'))) {
                        $details[$keys]['shipping_status'] = config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.text');
                        $details[$keys]['loading_date'] = $details[$keys]['delivery_created_at'];
                        $details[$keys]['loading_staff'] = $details[$keys]['delivery_staff'];

                        $shippingCnt++;
                    } else if ($shippingKey == config('const.shippingDeliveryStatus.status.done_shipping')) {
                        $details[$keys]['shipping_status'] = config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.text');
                        $details[$keys]['loading_date'] = $details[$keys]['shipping_created_at'];
                        $details[$keys]['loading_staff'] = $details[$keys]['shipping_staff'];
                        
                        $shippingCnt++;
                    } else {
                        $details[$keys]['shipping_status'] = config('const.shippingDeliveryStatus.shipping.'. $shippingKey. '.text');
                    }
                }

                // 納品
                if (isset($details[$keys])) {
                    $deliveryKey = $details[$keys]['delivery_status'];
                    $deliverySum = (int)$deliverySum + $details[$keys]['delivery_quantity'];
                    if ($deliveryKey == config('const.shippingDeliveryStatus.status.done_delivery')) {
                        $details[$keys]['delivery_status'] = config('const.shippingDeliveryStatus.delivery.'. $deliveryKey. '.text');
                        $details[$keys]['delivery_date'] = $details[$keys]['delivery_created_at'];
                        $details[$keys]['delivery_staff'] = $details[$keys]['delivery_staff'];

                        $deliveryCnt++;
                    } else {
                        $details[$keys]['delivery_status'] = config('const.shippingDeliveryStatus.delivery.'. $deliveryKey. '.text');
                    }
                }
                // 棚名
                $details[$keys]['shelf_area'] = $details[$keys]['rack'];
            }

            $count = count($details);
            // $detailData = $detailList->keyBy('shipment_id');
            // 入荷
            // $arrivalKey = $detailData[$key]['arrival_status'];
            if ($count == $arrivalCnt){
                // 詳細全てが入荷済
                $shipmentList[$key]['parent_arrival_status'] = config('const.shippingDeliveryStatus.arrival.done_arrival.text');

            } else if($count > $arrivalCnt && $arrivalCnt != config('const.arrivalStatus.val.not_arrival') || $partArrivalFlg) {
                // 一部入荷
                $shipmentList[$key]['parent_arrival_status'] = config('const.shippingDeliveryStatus.arrival.part_arrival.text');

            } else {
                // 未入荷
                $shipmentList[$key]['parent_arrival_status'] = config('const.shippingDeliveryStatus.arrival.not_arrival.text');

            }
            // 出荷
            // $shippingKey = $detailData[$key]['shipping_status'];
            if ($count == $shippingCnt){
                // 詳細全てが出荷済
                $shipmentList[$key]['parent_shipping_status'] = config('const.shippingDeliveryStatus.shipping.done_shipping.text');

            } else if($count > $shippingCnt && $shippingCnt != 0) {
                // 一部出荷済
                $shipmentList[$key]['parent_shipping_status'] = config('const.shippingDeliveryStatus.shipping.part_shipping.text');

            } else {
                // 未出荷
                $shipmentList[$key]['parent_shipping_status'] = config('const.shippingDeliveryStatus.shipping.not_shipping.text');

            }
            // 納品
            // $deliveryKey = $detailData[$key]['delivery_status'];
            if ($count == $deliveryCnt && $shipmentList[$key]['sum_delivery_quantity'] >= $shipmentList[$key]['sum_shipment_quantity']) {
                // 納品済
                $shipmentList[$key]['parent_delivery_status'] = config('const.shippingDeliveryStatus.delivery.done_delivery.text');

            } else if((int)$shipmentList[$key]['sum_delivery_quantity'] < (int)$shipmentList[$key]['sum_shipment_quantity'] && (int)$shipmentList[$key]['sum_delivery_quantity'] > 0) {
                // 一部納品済
                $shipmentList[$key]['parent_delivery_status'] = config('const.shippingDeliveryStatus.delivery.part_delivery.text');

            } else {
                // 未納品
                $shipmentList[$key]['parent_delivery_status'] = config('const.shippingDeliveryStatus.delivery.not_delivery.text');

            }
            // // 明細１レコード目の商品名 ＋ 他(明細数)品目
            // if ($count > 1) {
            //     $shipmentList[$key]['product_name'] = $firstProductName . ' 他' .($count - 1) . '品目';
            // } else {
            //     $shipmentList[$key]['product_name'] = $firstProductName;
            // }

            // 表示加工
            // $shipmentList[$key]['warehouse_name'] = $Warehouse->getById($shipmentList[$key]['from_warehouse_id'])['warehouse_name'];
            // 重量物
            if ($shipmentList[$key]['heavy_flg'] == config('const.flg.on')) {
                $shipmentList[$key]['heavy_flg'] = '重量物';
            } else {
                $shipmentList[$key]['heavy_flg'] = '';
            }
            // ユニック
            if ($shipmentList[$key]['unic_flg'] == config('const.flg.on')) {
                $shipmentList[$key]['unic_flg'] = 'ユニック';
            } else {
                $shipmentList[$key]['unic_flg'] = '';
            }
            // 雨天
            if ($shipmentList[$key]['rain_flg'] == config('const.flg.on')) {
                $shipmentList[$key]['rain_flg'] = '雨天';
            } else {
                $shipmentList[$key]['rain_flg'] = '';
            }
            // 小運搬
            if ($shipmentList[$key]['transport_flg'] == config('const.flg.on')) {
                $shipmentList[$key]['transport_flg'] = '小運搬';
            } else {
                $shipmentList[$key]['transport_flg'] = '';
            }
            // 納品希望時刻
            $deliveryTime = $shipmentList[$key]['delivery_time'];
            if (empty($deliveryTime)) {
                // 日時が0はブランク表示
                $shipmentList[$key]['delivery_time'] = '';
            } else if(strlen($deliveryTime) == 4) {
                // 日時が4桁　例：1230 => 12:30
                $shipmentList[$key]['delivery_time'] = substr($deliveryTime, 0, 2). ':' .substr($deliveryTime, 2, 2);
            } else if (strlen($deliveryTime) == 3) {
                // 日時が3桁　例：130 => 01:30
                $shipmentList[$key]['delivery_time'] = '0'. substr($deliveryTime, 0, 1). ':'. substr($deliveryTime, 1, 2);
            } else if (strlen($deliveryTime) == 2) {
                // 日時が2桁　例：30 => 00:30
                $shipmentList[$key]['delivery_time'] = '00:'. substr($deliveryTime, 0, 2);
            } else if (strlen($deliveryTime) == 1) {
                // 日時が1桁　例:3 => 00:03
                $shipmentList[$key]['delivery_time'] = '00:0'. substr($deliveryTime, 0, 1);
            } else {
                $shipmentList[$key]['delivery_time'] = '';
            }
        }

        $parentDataList = $shipmentList->values()->toArray();
        $detailDataList = [];
        if (!is_null($detailList)) {
            $detailDataList = $detailList->toArray();
        }
        
        $issetList = [];
        if($parentData['order'] !== null && $parentData['order']->count() >= 1){
            $orderDataList = $parentData['order']->toArray();
            
            foreach ($orderDataList as $key => $value) {
                $orderId = $orderDataList[$key]['id'];
                if(!isset($issetList[$orderId])){

                    $issetList[$orderId] = true;
                    $orderDataList[$key]['id'] = self::ORDER_DELIVERY_PREFIX.$orderId;

                    $details = $orderDetailList->where('shipment_id', '=', $orderId)->toArray();
                    $count = 0;
                    // $firstProductName = '';
                    foreach ($details as $detailKey => $detailValue) {
                        $count++;
                        // if($count === 1){
                        //     $firstProductName = Common::nullToBlank($detailValue['product_name']);
                        // }
                        $orderDetailId = $detailValue['shipment_detail_id'];
                        $detailValue['shipment_id'] = $orderDataList[$key]['id'];
                        $detailValue['shipment_detail_id'] = self::ORDER_DETAIL_DELIVERY_PREFIX.$orderDetailId;

                        // 入荷ステータス　入荷済み
                        $detailValue['arrival_status'] = config('const.shippingDeliveryStatus.arrival.done_arrival.text');

                        // 出荷ステータス　出荷済み
                        $detailValue['shipping_status'] = config('const.shippingDeliveryStatus.shipping.done_shipping.text');
                        $detailValue['loading_date'] = $detailValue['shipping_created_at'];
                        $detailValue['loading_staff'] = $detailValue['shipping_staff'];

                         // 納品ステータス　納品済み
                        $detailValue['delivery_status'] = config('const.shippingDeliveryStatus.delivery.done_delivery.text');
                        $detailValue['delivery_date'] = $detailValue['delivery_created_at'];
                        $detailValue['delivery_staff'] = $detailValue['delivery_staff'];

                        $detailDataList[] = $detailValue;
                    }

                    // 入荷
                    $orderDataList[$key]['parent_arrival_status'] = config('const.shippingDeliveryStatus.arrival.done_arrival.text');

                    // 出荷
                    $orderDataList[$key]['parent_shipping_status'] = config('const.shippingDeliveryStatus.shipping.done_shipping.text');

                    // 納品
                    $orderDataList[$key]['parent_delivery_status'] = config('const.shippingDeliveryStatus.delivery.done_delivery.text');

                    // // 商品名のラベル加工
                    // if ($count > 1) {
                    //     $value['product_name'] = $firstProductName . ' 他' .($count - 1) . '品目';
                    // } else {
                    //     $value['product_name'] = $firstProductName;
                    // }
                    // 重量物
                    if ($orderDataList[$key]['heavy_flg'] == config('const.flg.on')) {
                        $orderDataList[$key]['heavy_flg'] = '重量物';
                    } else {
                        $orderDataList[$key]['heavy_flg'] = '';
                    }
                    // ユニック
                    if ($orderDataList[$key]['unic_flg'] == config('const.flg.on')) {
                        $orderDataList[$key]['unic_flg'] = 'ユニック';
                    } else {
                        $orderDataList[$key]['unic_flg'] = '';
                    }
                    // 雨天
                    if ($orderDataList[$key]['rain_flg'] == config('const.flg.on')) {
                        $orderDataList[$key]['rain_flg'] = '雨天';
                    } else {
                        $orderDataList[$key]['rain_flg'] = '';
                    }
                    // 小運搬
                    if ($orderDataList[$key]['transport_flg'] == config('const.flg.on')) {
                        $orderDataList[$key]['transport_flg'] = '小運搬';
                    } else {
                        $orderDataList[$key]['transport_flg'] = '';
                    }
                    // 納品希望時刻
                    $deliveryTime = $orderDataList[$key]['delivery_time'];
                    if (empty($deliveryTime)) {
                        // 日時が0
                        $orderDataList[$key]['delivery_time'] = '';
                    } else if(strlen($deliveryTime) == 4) {
                        // 日時が4桁　例：1230 => 12:30
                        $orderDataList[$key]['delivery_time'] = substr($deliveryTime, 0, 2). ':' .substr($deliveryTime, 2, 2);
                    } else if (strlen($deliveryTime) == 3) {
                        // 日時が3桁　例：130 => 01:30
                        $orderDataList[$key]['delivery_time'] = '0'. substr($deliveryTime, 0, 1). ':'. substr($deliveryTime, 1, 2);
                    } else if (strlen($deliveryTime) == 2) {
                        // 日時が2桁　例：30 => 00:30
                        $orderDataList[$key]['delivery_time'] = '00:'. substr($deliveryTime, 0, 2);
                    } else if (strlen($deliveryTime) == 1) {
                        // 日時が1桁　例:3 => 00:03
                        $orderDataList[$key]['delivery_time'] = '00:0'. substr($deliveryTime, 0, 1);
                    } else {
                        $orderDataList[$key]['delivery_time'] = '';
                    }

                    $parentDataList[] = $orderDataList[$key];
                }
            }
        }

        // ファイル名
        $FILE_NAME = 'shipping_delivery_list';
        // 開始列
        $START_COL = 'A';
        // 開始行
        $START_ROW = 2;
        // 終了列
        $END_COL = 'AC';
        
        $PARENT_HEADER_INFO = [
            'parent_arrival_status',
            'delivery_date',
            'delivery_time',
            'matter_no',
            'matter_name',
            'customer_name',
            'address',
            'warehouse_name',
            'heavy_flg',
            'unic_flg',
            'rain_flg',
            'transport_flg',
            'parent_shipping_status',
            'parent_delivery_status',
        ];
        $DETAIL_HEADER_INFO = [
            'arrival_status',
            'shelf_area',
            'product_code',
            'product_name',
            'model',
            'stock_quantity',
            'quote_quantity',
            'shipment_quantity',
            'return_quantity',
            'shipping_status',
            'loading_date',
            'loading_staff',
            'delivery_status',
            'delivery_date',
            'delivery_staff',
        ];

        try{
            // スプレッドシートを作成
            //$spreadsheet = new Spreadsheet();
            $spreadsheet = IOFactory::load(resource_path(config('const.templatePath').DIRECTORY_SEPARATOR.config('const.excelTemplateName.shippingDeliveryList')));
            
            // ファイルのプロパティを設定
            $properties = $spreadsheet->getProperties();
            $properties->setCreator(Auth::user()->staff_name);
            $properties->setLastModifiedBy(Auth::user()->staff_name);
            
            // シート作成
            $spreadsheet->getActiveSheet('Sheet1')->UnFreezePane();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->freezePane('A2');

            // 値
            $outputDataList = [];
            foreach($parentDataList as $key => $shipment){
                $detailGridData = collect($detailDataList)->where('shipment_id', $shipment['id']);
                foreach($detailGridData as $detailKey => $record) {
                    $val = [];
                    // 親グリッド
                    foreach($PARENT_HEADER_INFO as $column){
                        if (array_key_exists($column, $shipment)) {
                            $val[] = $shipment[$column];
                        } else {
                            $val[] = '';
                        }
                    }
                    // 子グリッド
                    foreach($DETAIL_HEADER_INFO as $column){
                        if (array_key_exists($column, $record)) {
                            $val[] = $record[$column];
                        } else {
                            $val[] = '';
                        }
                    }
                    $outputDataList[] = $val;
                }
            }


            $sheet->fromArray($outputDataList, null, $START_COL. $START_ROW, true);
            $style = $sheet->getStyle($START_COL. $START_ROW.':'.$END_COL. ((count($outputDataList)-1) + $START_ROW));
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


            ob_end_clean();
            $response->setCallBack(function() use($spreadsheet){
                $writer = new XlsxWriter($spreadsheet);
                $writer->save('php://output');
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Description', 'File Transfer');
            $response->headers->set('Content-Disposition', 'attachment; filename='.$FILE_NAME.'_'.Carbon::now()->format('YmdHis').'.xlsx');
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Expires', '0');
            $response->send();
        } catch (\Exception $ex) {
            Log::error($ex);
        } finally {
            if(!is_null($spreadsheet)){
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }
        return $response;
    }
}