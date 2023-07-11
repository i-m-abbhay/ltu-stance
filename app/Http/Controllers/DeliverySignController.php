<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Loading;
use App\Models\WarehouseMove;
use App\Models\ProductstockShelf;
use App\Libs\SystemUtil;
use App\Models\Base;
use App\Models\Authority;
use App\Models\Company;
use App\Models\Delivery;
use App\Models\Department;
use App\Models\StaffDepartment;
use Auth;
use Carbon\Carbon;

/**
 * 納品サイン
 */
class DeliverySignController extends Controller
{
    const SCREEN_NAME = 'delivery-sign';

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
        // TODO: 権限チェック

        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Delivery.delivery-sign')
            ->with('isEditable', $isEditable);
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {

        $resultSts = false;

        // リクエストデータ取得
        $reqData = $request->request->all();
        $tableData = $reqData['tableData'];
        $scanData = $reqData['scanData'];
        $imgData = $reqData['sign'];

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();

        $Loading = new Loading();
        $Delivery = new Delivery();
        $Shipment = new Shipment();
        $WarehouseMove = new WarehouseMove();
        $ShipmentDetail = new ShipmentDetail();
        $SystemUtil = new SystemUtil();
        $ProductstockShelf = new ProductstockShelf();

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
            $saveResult = false;
            foreach ($tableData as $params) {

                //サイン保存
                $params['loading_id'] = $params['id'];
                $singParams['id'] = $params['shipment_id'];
                $singParams['image_sign'] = $imgData;

                $saveResult = $Shipment->updateById($singParams);

                //納品データ保存
                // 't_matter.matter_name',
                // 'm_product.product_name',
                // 'm_product.model',
                // 't_loading.id as loading_id',
                // '0 as delivery_quantity',
                // 't_qr.qr_code',
                // 't_qr_detail.quantity as qr_quantity'
                // matter_id				
                // shipment_id				
                // shipment_detail_id				
                // order_detail_id				
                // quote_id				
                // quote_detail_id				
                // shipment_kind				
                // zipcode				
                // pref				
                // address1				
                // address2				
                // latitude_jp				
                // longitude_jp				
                // latitude_world				
                // longitude_world				
                // product_id				
                // product_code				
                // from_warehouse_kbn				
                // from_warehouse_id				
                // to_warehouse_id				
                // to_shelf_number_id				
                // loading_date				
                // loading_quantity				
                // delivery_finish_flg	
                $params['delivery_no'] = $DeliveryNo;
                $params['delivery_date']=null;
                if ($params['delivery_quantity'] != 0) {
                    // 納品登録
                    $list = $Delivery->getList($params);
                    if (count($list) > 0) {
                        // 更新
                        $params['id'] = $list[0]->id;
                        $saveResult = $Delivery->updateById($params);
                    } else {
                        // 追加
                        $saveResult = $Delivery->add($params);
                    }
                    if (!$saveResult) {
                        throw new \Exception(config('message.error.save'));
                    }
                    // 入出庫登録
                    $params['move_kind'] = 8;
                    $params['move_flg'] = 0;
                    $params['quantity'] = $params['delivery_quantity'];
                    $moveParams['to_warehouse_id'] = $params['from_warehouse_id'];
                    $params['from_warehouse_id'] = $params['to_warehouse_id'];
                    $params['from_shelf_number_id'] = $params['to_shelf_number_id'];
                    $params['to_warehouse_id'] = 0;
                    $params['to_shelf_number_id'] = 0;
                    $params['order_id'] = 0;
                    $params['order_no'] = 0;
                    $params['reserve_id'] = 0;
                    $params['arrival_id'] = 0;
                    $params['sales_id'] = 0;
                    //在庫品の場合、案件と顧客IDは0
                    if ($params['stock_flg'] == 1) {
                        $params['matter_id'] = 0;
                        $params['customer_id'] = 0;
                    }
                    //預かり品の場合、案件IDは0
                    else if ($params['stock_flg'] == 2) {
                        $params['matter_id'] = 0;
                    }
                    try {
                        $move_product_id = $SystemUtil->MoveInventory($params);
                    } catch (\Exception $e) {
                        throw new \Exception(config('message.error.save'));
                    }
                    // 納品完了フラグ更新
                    $saveResult = $ShipmentDetail->updateDeliveryFinish($params);
                    $saveResult = $Loading->updateDeliveryFinish($params);

                    //倉庫移動レコード作成（再配送がある場合のみ）
                    if ($params['delivery_quantity'] != $params['loading_quantity']) {

                        $moveParams['move_kind'] = 1;
                        $moveParams['from_warehouse_id'] = 0;
                        $moveParams['stock_flg'] = $params['stock_flg'];
                        $moveParams['finish_flg'] = 1;
                        $moveParams['from_product_move_id'] = $move_product_id;
                        $moveParams['to_product_move_id'] = null;
                        $moveParams['product_code'] = $params['product_code'];
                        $moveParams['product_id'] = $params['product_id'];
                        $moveParams['quantity'] = $params['loading_quantity'] - $params['delivery_quantity'];
                        $moveParams['shipment_detail_id'] = $params['shipment_detail_id'];
                        $moveParams['approval_status'] = 0;
                        $moveParams['approval_user'] = null;
                        $moveParams['approval_at'] = null;
                        $moveParams['returnproc_kbn'] = 0;
                        $moveParams['returnproc_date'] = null;
                        // $moveParams['returnproc_check'] = null;
                        // $moveParams['returnproc_check_user'] = 0;
                        $moveParams['returnproc_finish'] = 0;
                        $moveParams['matter_id'] = $params['matter_id'];
                        $moveParams['customer_id'] = $params['customer_id'];
                        $moveParams['delivery_id'] = null;
                        $moveParams['order_detail_id'] = $params['order_detail_id'];
                        $moveParams['quote_id'] = $params['quote_id'];
                        $moveParams['quote_detail_id'] = $params['quote_detail_id'];

                        $saveResult = $WarehouseMove->add($moveParams);

                        if (!$saveResult) {
                            throw new \Exception(config('message.error.save'));
                        }
                    }
                }
            }

            foreach ($scanData as $qr) {
                $SystemUtil->deleteQr($qr['qr_id']);
            }

            DB::commit();
            $resultSts = true;
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
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
        $tableList = json_decode($params['tableData'], true);

        $delIdList = collect($tableList)->pluck('loading_id')->toArray();

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
            $data = $Delivery->getListInId($delIdList);
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
            $query = '?__success_redirect_url=googlechrome://&__failure_redirect_url=' . url('/smapri-failed', null, $isSecure) . '&__format_archive_url=' . url(config('const.qrLabelPath') . config('const.printer.printDeliveryFileName'), null, $isSecure) . $formatIdNum;
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
