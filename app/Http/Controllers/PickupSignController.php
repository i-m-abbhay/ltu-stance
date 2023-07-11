<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\General;
use Storage;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Libs\Common;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Loading;
use App\Models\WarehouseMove;
use App\Models\ProductCostPrice;
use App\Models\Reserve;
use App\Libs\SystemUtil;
use App\Models\Base;
use App\Models\Arrival;
use App\Models\Authority;
use App\Models\Company;
use App\Models\Delivery;
use App\Models\ShelfNumber;
use App\Models\NumberManage;
use App\Models\Department;
use App\Models\QrDetail;
use App\Models\StaffDepartment;
use Auth;
use Carbon\Carbon;

/**
 * 納品サイン
 */
class PickupSignController extends Controller
{
    const SCREEN_NAME = 'pickup-sign';

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
     * @return type
     */
    public function index()
    {

        return view('Shipping.pickup-sign');
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $result = ['status' => false, 'delivery_id' => null, 'message' => ''];
        // $resultSts = false;

        // リクエストデータ取得
        $reqData = $request->request->all();
        $tableData = $reqData['tableData'];
        $scanData = $reqData['scanData'];
        $imgData = $reqData['captures'];
        $image_photo1 = null;
        $image_photo2 = null;
        if (count($imgData) > 0) {
            $image_photo1 = $imgData[0];
        }
        if (count($imgData) > 1) {
            $image_photo2 = $imgData[1];
        }
        $image_sign = $reqData['sign'];

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();

        $Loading = new Loading();
        $Delivery = new Delivery();
        $Shipment = new Shipment();
        $WarehouseMove = new WarehouseMove();
        $ShelfNumber = new ShelfNumber();
        $Reserve = new Reserve();
        $QrDetail = new QrDetail();
        $SystemUtil = new SystemUtil();
        $Order = new Order();
        $ShipmentDetail = new ShipmentDetail();

        //納品番号採番
        // 採番ルール
        // 頭:NH(納品の略)
        // 西暦:20(下2桁)
        // 月日:0717(7月17日)
        // 順番:001(当日出荷数の順番)←その日の納品作業の回数的な
        // 例:NH200717001

        $DeliveryNo = "";
        $LastDeliveryNo = $Delivery->getDeliveryNo();
        if ($LastDeliveryNo != null) {
            $seqNo = (int)substr($LastDeliveryNo['delivery_no'], -3);
            $seqNo += 1;

            $DeliveryNo = substr($LastDeliveryNo['delivery_no'], 0, 8) . sprintf('%03d', $seqNo);
        } else {
            $DeliveryNo = 'NH' . substr(Carbon::now()->format('Y'), -2) .  Carbon::now()->format('md') . '001';
        }


        try {
            $deliveryIds = [];
            foreach ($tableData as $params) {
                // 出荷指示が処理済みかどうか
                $shipmentDetailData = $ShipmentDetail->getById($params['shipment_detail_id']);
                if ($shipmentDetailData['loading_finish_flg'] == config('const.flg.on')) {
                    $result['message'] = config('message.error.loading.loaded_shipment');
                    throw new \Exception(config('message.error.loading.loaded_shipment'));
                }

                //出荷
                $shipmentParams['id'] =  $params['shipment_id'];
                $shipmentParams['image_sign'] = $image_sign;
                $shipmentParams['image_photo1'] = $image_photo1;
                $shipmentParams['image_photo2'] = $image_photo2;
                $shipmentParams['loading_finish_flg'] = 1;
                $saveResult = $Shipment->updateById($shipmentParams);

                //出荷明細
                $shipmentDetailParams['id'] = $params['shipment_detail_id'];
                $shipmentDetailParams['loading_finish_flg'] = 1;
                $shipmentDetailParams['delivery_finish_flg'] = 1;
                $ShipmentDetail->updateById($shipmentDetailParams);

                //納品
                $deliveryParams['delivery_no'] = $DeliveryNo;
                $deliveryParams['matter_id'] = $params['matter_id'];
                $deliveryParams['stock_flg'] = $params['stock_flg'];
                $deliveryParams['shipment_id'] = $params['shipment_id'];
                $deliveryParams['shipment_detail_id'] = $params['shipment_detail_id'];
                $deliveryParams['loading_id'] = 0;
                $deliveryParams['order_detail_id'] = $params['order_detail_id'];
                $deliveryParams['quote_id'] = $params['quote_id'];
                $deliveryParams['quote_detail_id'] = $params['quote_detail_id'];
                $deliveryParams['shipment_kind'] = $params['shipment_kind'];
                $deliveryParams['zipcode'] = $params['zipcode'];
                $deliveryParams['pref'] = $params['pref'];
                $deliveryParams['address1'] = $params['address1'];
                $deliveryParams['address2'] = $params['address2'];
                $deliveryParams['latitude_jp'] = $params['latitude_jp'];
                $deliveryParams['longitude_jp'] = $params['longitude_jp'];
                $deliveryParams['latitude_world'] = $params['latitude_world'];
                $deliveryParams['longitude_world'] = $params['longitude_world'];
                $deliveryParams['product_id'] = $params['product_id'];
                $deliveryParams['product_code'] = $params['product_code'];
                $deliveryParams['delivery_date'] = Carbon::now();
                $deliveryParams['delivery_quantity'] = $params['loading_quantity'];
                $deliveryId = $Delivery->add($deliveryParams);
                $deliveryIds[] = $deliveryId;


                 // 入出庫登録(複数QRがスキャンされた場合は数に応じて入出庫も作成)
                 $params['move_kind'] = 2;
                 $params['move_flg'] = 0;
                 $params['order_id'] = 0;
                 $params['order_no'] = 0;
                 // $params['reserve_id'] = 0;
                 $params['arrival_id'] = 0;
                 $params['sales_id'] = 0;
                 $params['to_warehouse_id'] = 0;
                 $params['to_warehouse_id'] = 0;

                //QRがスキャンされてない場合（在庫品）
                if ($params['qrData'] == null) {
                    $params['quantity'] = $params['loading_quantity'];
                    $params['matter_id'] = 0;
                    $params['customer_id'] = 0;
                    $params['matter_no'] = 0;

                    $saveResult = $SystemUtil->MoveInventory($params);
                }
                //QRがスキャンされている場合
                else {
                    $qrGroup = [];
                    foreach ($params['qrData'] as $qrInfo) {
                        // 移動前のQRデータ
                        $beforeQrData = $QrDetail->getById($qrInfo['detail_id']);

                        //QR削除
                        $saveResult = $SystemUtil->deleteQr($qrInfo['qr_id']);

                        //QRを倉庫、棚ごとに集計
                        if (Count($qrGroup) > 0) {
                            $isUpdate = false;
                            for ($i = 0; $i < Count($qrGroup); $i++) {
                                if ($qrGroup[$i]['warehouse_id'] == $qrInfo['warehouse_id'] && $qrGroup[$i]['shelf_number_id'] == $qrInfo['shelf_number_id']) {
                                    $qrGroup[$i]['quantity'] = $qrGroup[$i]['quantity'] + $qrInfo['quantity'];
                                    $isUpdate = true;
                                }
                            }
                            if (!$isUpdate) {
                                array_push($qrGroup, ['detail_id' => $qrInfo['detail_id'], 'beforeQrData' => $beforeQrData, 'warehouse_id' => $qrInfo['warehouse_id'], 'shelf_number_id' => $qrInfo['shelf_number_id'], 'quantity' => $qrInfo['quantity']]);
                            }
                        } else {
                            array_push($qrGroup, ['detail_id' => $qrInfo['detail_id'], 'beforeQrData' => $beforeQrData, 'warehouse_id' => $qrInfo['warehouse_id'], 'shelf_number_id' => $qrInfo['shelf_number_id'], 'quantity' => $qrInfo['quantity'], 'detail_id' => $qrInfo['detail_id']]);
                        }
                    }

                    //預かり品の場合、案件ID=0
                    if ($params['stock_flg'] == 2) {
                        $params['matter_id'] = 0;
                    }

                    //入出庫トラン作成
                    foreach ($qrGroup as $qrInfo) {
                        $params['from_warehouse_id'] = $qrInfo['warehouse_id'];
                        $params['from_shelf_number_id'] = $qrInfo['shelf_number_id'];
                        $params['quantity'] = $qrInfo['quantity'];
                        
                        // 棚移動されている可能性があるため、再取得したQRの棚番IDを指定する
                        if ($qrInfo['beforeQrData'] !== null && ($qrInfo['beforeQrData']['warehouse_id'] !== $qrInfo['warehouse_id'] || $qrInfo['beforeQrData']['shelf_number_id'] !== $qrInfo['shelf_number_id'])) {
                            $params['from_warehouse_id'] = $qrInfo['beforeQrData']['warehouse_id'];
                            $params['from_shelf_number_id'] = $qrInfo['beforeQrData']['shelf_number_id'];
                        }

                        $SystemUtil = new SystemUtil();
                        $saveResult = $SystemUtil->MoveInventory($params);
                    }
                }

                //引当テーブル更新
                $rsvData = $Reserve->getById($params['reserve_id']);
                if ($rsvData != null && $rsvData['id'] != null) {
                    $rsvParams['id'] = $rsvData['id'];
                    $rsvParams['issue_quantity'] = (int)$rsvData['issue_quantity'] + (int)$params['loading_quantity'];
                    $rsvParams['reserve_quantity_validity'] = (int)$rsvData['reserve_quantity_validity'] - (int)$params['loading_quantity'];
                    if ((int)$params['quantity'] >= (int)$rsvData['reserve_quantity_validity']) {
                        $rsvParams['finish_flg'] = 1;
                    } else {
                        $rsvParams['finish_flg'] = 0;
                    }
                    $saveResult = $Reserve->updateShipping($rsvParams);
                }


                // 納品完了フラグ更新
                $saveResult = $ShipmentDetail->updateDeliveryFinish($params);
            }
            DB::commit();
            $result['status'] = true;
            $result['delivery_id'] = $deliveryIds;
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['message'] == '') {
                $result['message'] = config('message.error.error');
                Session::flash('flash_error', config('message.error.error'));
            }
            Log::error($e);
        }
        return \Response::json($result);
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
        $deliveryIds = $params['deliveryIds'];

        // // バリデーションチェック
        // $this->isValid($request);

        DB::beginTransaction();
        $Delivery = new Delivery();
        $SystemUtil = new SystemUtil();
        $Base = new Base();
        $Department = new Department();
        $StaffDepartment = new StaffDepartment();
        $Company = new Company();
        $result = false;
        try {
            // 納品データ取得
            $data = $Delivery->getDeliveryIds($deliveryIds);
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
            $baseName = SystemUtil::truncate($companyInfo->company_name . '　' . $baseData['base_name'], 50);
            // $hData['delivery_no'] = "&__format[0].".$headerData['delivery_no'];
            // $hData['delivery_date'] = "&__format[0].".$headerData['delivery_date'];
            // $hData['customer_name'] = "&__format[0].".$headerData['customer_name'];
            // $hData['matter_name'] = "&__format[0].".$matterName;
            // $hData['address'] = "&__format[0].".$headerData['address'];
            // $hData['base_name'] = "&__format[0].".$baseName;
            // $hData['staff_name'] = "&__format[0].".$headerData['staff_name'];

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

                // $detailTxt .= '&__format[' . $formatIdIdx . '].product_code=' . urlencode($row['product_code']);
                // $detailTxt .= '&__format[' . $formatIdIdx . '].product_name=' . urlencode($row['product_name']);
                // $detailTxt .= '&__format[' . $formatIdIdx . '].model=' . urlencode($row['model']);
                // $detailTxt .= '&__format[' . $formatIdIdx . '].quantity=' . urlencode($row['delivery_quantity']);
                // $detailTxt .= '&__format[' . $formatIdIdx . '].unit=' . urlencode($row['unit']);
                // $detailTxt .= '&__format[' . $formatIdIdx . '].memo=' . urlencode($row['memo']);

                // 末尾商品以外に区切り文字挿入
                if (count($data) > $key + 1) {
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
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                // HTTPS通信の場合の処理
                $isSecure = true;
            }

            // 印刷用URL生成
            $query = '?__success_redirect_url=' . url('/smapri-success', null, $isSecure) . '&__failure_redirect_url=' . url('/smapri-failed', null, $isSecure) . '&__format_archive_url=' . url(config('const.qrLabelPath') . config('const.printer.printDeliveryFileName'), null, $isSecure) . $formatIdNum;
            // .'&__format_id_number=1&start_number=01';
            $query .=  $detailTxt . $headerTxt;
            // $query .=  '&' . http_build_query($hData);

            // 使用端末がiosか判定
            $isIos = $SystemUtil->judgeDevice();

            // ドメイン取得
            // $domain = url('/', null, $isSecure);
            $domain = (empty($_SERVER["HTTPS"]) ? "http://" : "http://") . 'localhost';
            $host = parse_url($domain)['host'];
            $port = '';
            if ($isIos) {
                // ios端末
                $domain = 'smapri:';
                $url = $domain . '/Format/Print' . $query;
                return \Response::json(['url' => $url]);
            } else {
                $port = ':' . config('const.printer.port');

                $url = $domain . $port . '/Format/Print' . $query;
                header('Location: ' . $url);
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
            $resultSts = true;
            // }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request)
    {
        $this->validate($request, [
            'change_quantity' => 'numeric',
        ]);
    }
}
