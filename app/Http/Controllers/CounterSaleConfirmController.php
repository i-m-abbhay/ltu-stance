<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\System;
use App\Models\Matter;
use App\Models\Quote;
use App\Models\NumberManage;
use App\Models\QuoteVersion;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\Company;
use App\Models\Delivery;
use App\Models\Credited;
use App\Models\CreditedDetail;
use App\Models\ProductstockShelf;
use Session;
use Carbon\Carbon;
use DB;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Requests;
use Exception;
use App\Libs\SystemUtil;
use App\Models\Product;

/**
 * 販売確認
 */
class CounterSaleConfirmController extends Controller
{

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
        try {
            DB::beginTransaction();

            // 期、税率取得
            $System = new System();
            $systemList = $System->getByPeriod();
            $period = null;
            $taxRate = 1;
            if ($systemList != null) {
                $period = $systemList['period'];
                $taxRate =  $systemList['tax_rate'] / 100;
            }

            // 得意先、案件取得
            $Customer = new Customer();
            $Matter = new Matter();

            $customerList = $Customer->getByLoginUser($period);

            //ログインユーザーが存在しない場合
            if ($customerList == null || count($customerList) <= 0) {
                throw new Exception("ログイン中のユーザー情報が存在しません。");
            }

            //得意先マスタが存在しない場合
            if ($customerList[0]['customer_id'] == null) {
                //新規得意先作成
                $params['customer_name'] = '一般売上_' . $customerList[0]['department_name'];
                $params['customer_short_name'] = '一般売上_' . $customerList[0]['department_name'];
                $params['customer_kana'] = 'いっぱんうりあげ';
                $params['charge_department_id'] = $customerList[0]['department_id'];
                $params['charge_staff_id'] = $customerList[0]['chief_staff_id'];
                $params['use_sales_flg'] = 1;
                $params['closing_day'] = 99;
                $params['collection_sight'] = 1;

                $customerList[0]['customer_id'] = $Customer->addOfCounterSale($params);
            }

            //案件が存在しない場合
            if ($customerList[0]['matter_id'] == null) {
                //新規案件作成
                $params['customer_id'] = $customerList[0]['customer_id'];
                $params['owner_name'] = 'その他';
                $params['staff_id'] = $customerList[0]['staff_id'];
                $params['department_id'] = $customerList[0]['department_id'];
                $params['use_sales_flg'] = 1;

                $matterList = $Matter->addOfCounterSale($params);
                $customerList[0]['matter_id'] = $matterList['matter_id'];
                $customerList[0]['matter_no'] = $matterList['matter_no'];
                $customerList[0]['matter_name'] = $matterList['matter_name'];
            }

            //見積検索
            $Quote = new Quote();
            $NumberManage = new NumberManage();

            //案件に対する見積がない場合作成する
            $quoteList = $Quote->getByMatterNo($customerList[0]['matter_no']);
            if ($quoteList == null) {
                $quoteNo = $NumberManage->getSeqNo(config('const.number_manage.kbn.quote'), Carbon::today()->format('Ym'));
                $params['quote_no']  = $quoteNo;
                $params['matter_no']  = $customerList[0]['matter_no'];
                $params['status']  =  config('const.flg.off');
                $params['own_stock_flg']  = config('const.flg.off');
                $quoteId = $Quote->add($params);
            } else {
                $quoteId = $quoteList['id'];
                $quoteNo = $quoteList['quote_no'];
            }
            $customerList[0]['quote_id'] = $quoteId;

            //見積0版検索
            $QuoteVersion = new QuoteVersion();

            //案件に対する見積がない場合作成する   
            if (!$QuoteVersion->isExist($quoteNo, 0)) {
                $params['quote_no']  = $quoteNo;
                $params['quote_version']  = 0;
                $params['caption']  =  "完成見積";
                $params['department_id']  = $customerList[0]['department_id'];
                $params['staff_id']  = $customerList[0]['staff_id'];
                $params['tax_rate']  = $taxRate;
                $params['status']  = config('const.quoteVersionStatus.val.approved');
                $QuoteVersion->add($params);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Sales.counter-sale-confirm')
            ->with('customerList', $customerList[0])
            ->with('taxRate', $taxRate);
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $data = $request->request->all();
        $tableData = $data['tableData'];
        $customerList = $data['customerList'];
        $total = $data['total'];
        $sign = $data['sign'];
        $tax = $data['tax'];
        $taxTotal = $data['taxTotal'];
        $purchaseTotal = $data['purchaseTotal'];
        $creditFlg = $data['creditFlg'];

        // // バリデーションチェック
        // $this->isValid($request);

        $SystemUtil = new SystemUtil();
        $Requests = new Requests();
        $NumberManage = new NumberManage();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $Delivery = new Delivery();
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $ProductstockShelf = new ProductstockShelf();
        $stockErr = [];

        DB::beginTransaction();

        try {
            $stockResult = false;

            //請求情報作成
            $params = null;
            $params['customer_id'] = $customerList['customer_id'];
            $requestInfo = $Requests->getNewRequestOfCounterSale($params);
            // 請求月(request_mon)生成
            $requestMon = Carbon::now()->format('Ym');
            $requestInfo['request_mon'] = $requestMon;

            $requestInfo['request_no'] = $NumberManage->getSeqNo(config('const.number_manage.kbn.request'), Carbon::today()->format('Ym'));
            $requestInfo['purchase_volume'] = $purchaseTotal;
            $requestInfo['sales'] = $total;
            $requestInfo['consumption_tax_amount'] = $tax;
            $requestInfo['total_sales'] = $taxTotal;
            $requestInfo['request_amount'] = $taxTotal;
            $requestInfo['sales_category'] = 1;
            $requestInfo['status'] = 2;
            $requestInfo['image_sign'] = $sign;
            $requestId = $Requests->addCounterSale($requestInfo);

            //入金情報作成
            $CreditedInfo['request_id'] = $requestId;
            $CreditedInfo['request_no'] = $requestInfo['request_no'];
            $CreditedInfo['customer_id'] = $requestInfo['customer_id'];
            $CreditedInfo['customer_name'] = $requestInfo['customer_name'];
            $CreditedInfo['charge_department_id'] = $requestInfo['charge_department_id'];
            $CreditedInfo['charge_department_name'] = $requestInfo['charge_department_name'];
            $CreditedInfo['charge_staff_id'] = $requestInfo['charge_staff_id'];
            $CreditedInfo['charge_staff_name'] = $requestInfo['charge_staff_name'];
            $CreditedInfo['total_sales'] = $requestInfo['total_sales'];
            $CreditedInfo['request_amount'] =  $requestInfo['request_amount'];
            $CreditedInfo['cash'] =  $requestInfo['request_amount'];
            $CreditedInfo['status'] = 3;
            $CreditedId = $Credited->addCounterSale($CreditedInfo);

            //入金明細情報作成
            $CreditedDetailInfo['request_id'] = $requestId;
            $CreditedDetailInfo['credited_id'] = $CreditedId;
            $CreditedDetailInfo['credited_no'] =  $NumberManage->getSeqNo(config('const.number_manage.kbn.credited'), Carbon::today()->format('Ym'));
            $CreditedDetailInfo['financials_flg'] = 0;
            $CreditedDetailInfo['cash'] = $requestInfo['request_amount'];
            $CreditedDetailInfo['credit_flg'] = $creditFlg;
            $CreditedDetailId = $CreditedDetail->addCounterSale($CreditedDetailInfo);

            $index = 0;
            foreach ($tableData as $row) {

                //在庫数チェック
                $stockData = $ProductstockShelf->getProduct($row['product_id'], $row['warehouse_id']);
                //入力数が在庫数よりも多かった場合
                if ($stockData['actual_quantity'] < $row['quantity']) {
                    $stockErr[] = $row['product_code'] . '　' . $row['product_name'] . '　' . $row['quantity'];
                }

                //共通関数で在庫更新
                $params['product_id'] = $row['product_id'];
                $params['product_code'] = $row['product_code'];
                $params['from_warehouse_id'] = $row['warehouse_id'];
                $params['from_shelf_number_id'] = null;
                $params['to_warehouse_id'] = 0;
                $params['to_shelf_number_id'] = null;
                $params['stock_flg'] = 1;
                $params['matter_id'] = 0;
                $params['customer_id'] = 0;
                $params['move_kind'] = 2;
                $params['move_flg'] = 0;
                $params['quantity'] = $row['quantity'];
                $params['order_id'] = 0;
                $params['order_no'] = 0;
                $params['order_detail_id'] = 0;
                $params['reserve_id'] = 0;
                $params['shipment_id'] = 0;
                $params['shipment_detail_id'] = 0;
                $params['arrival_id'] = 0;
                $params['sales_id'] = 0;
                $SystemUtil->MoveInventory($params);
                
                //納品情報作成
                $DeliveryNo = "";
                $LastDeliveryNo = $Delivery->getDeliveryNo();
                if ($LastDeliveryNo != null) {
                    $seqNo = (int)substr($LastDeliveryNo['delivery_no'], -3);
                    $seqNo += 1;
                    $DeliveryNo = substr($LastDeliveryNo['delivery_no'], 0, 8) . sprintf('%03d', $seqNo);
                } else {
                    $DeliveryNo = 'NH' . substr(Carbon::now()->format('Y'), -2) .  Carbon::now()->format('md') . '001';
                }
                $deliveryInfo['delivery_no'] = $DeliveryNo;
                $deliveryInfo['matter_id'] = $customerList['matter_id'];
                $deliveryInfo['quote_id'] = $customerList['quote_id'];
                $deliveryInfo['stock_flg'] = 1;
                $deliveryInfo['product_id'] = $row['product_id'];
                $deliveryInfo['product_code'] = $row['product_code'];
                $deliveryInfo['delivery_quantity'] = $row['quantity'];
                $deliveryInfo['intangible_flg'] = 0;
                $deliveryId = $Delivery->addCounterSale($deliveryInfo);

                //売上情報作成
                $salesInfo['request_id'] = $requestId;
                $salesInfo['matter_id'] = $customerList['matter_id'];
                $salesInfo['matter_department_id'] = $customerList['department_id'];
                $salesInfo['matter_staff_id'] = $customerList['staff_id'];
                $salesInfo['quote_id'] = $customerList['quote_id'];
                $salesInfo['sales_flg'] = 2;
                $salesInfo['layer_flg'] = 0;
                $salesInfo['tree_path'] = '';
                $salesInfo['sales_use_flg'] = 0;
                $salesInfo['product_id'] = $row['product_id'];
                $salesInfo['product_code'] = $row['product_code'];
                $salesInfo['product_name'] = $row['product_name'];
                $salesInfo['model'] = $row['model'];
                $salesInfo['maker_id'] = $row['maker_id'];
                $salesInfo['maker_name'] = $row['supplier_name'];
                $salesInfo['min_quantity'] = $row['min_quantity'];
                $salesInfo['unit'] = $row['unit'];
                $salesInfo['stock_unit'] = $row['stock_unit'];
                $salesInfo['regular_price'] = $row['price'];
                $salesInfo['cost_kbn'] = 0;
                $salesInfo['sales_kbn'] = 0;
                $salesInfo['cost_unit_price'] = $row['purchase_price'];

                $salesInfo['sales_unit_price'] = $row['normal_sales_price'];
                $salesInfo['gross_profit_rate'] = 0;
                $salesInfo['profit_total'] = 0;
                $salesInfo['status'] = 0;
                $salesInfo['bk_cost_unit_price'] = 0;
                $salesInfo['bk_sales_unit_price'] = 0;
                $salesInfo['bk_cost_makeup_rate'] = 0;
                $salesInfo['bk_sales_makeup_rate'] = 0;
                $salesInfo['bk_gross_profit_rate'] = 0;
                $salesInfo['bk_profit_total'] = 0;
                $salesInfo['details_p_flg'] = 1;
                $salesInfo['details_c_flg'] = 1;
                $salesInfo['selling_p_flg'] = 1;
                $salesInfo['selling_c_flg'] = 1;
                $salesId = $Sales->addCounterSale($salesInfo);

                //売上明細情報作成
                $salesDetailInfo['matter_id'] = $customerList['matter_id'];
                $salesDetailInfo['quote_id'] = $customerList['quote_id'];
                $salesDetailInfo['sales_id'] = $salesId;
                $salesDetailInfo['request_id'] = $requestId;
                $salesDetailInfo['delivery_id'] = $deliveryId;
                $salesDetailInfo['sales_flg'] = 6; //いきなり売上
                $salesDetailInfo['sales_sel_flg'] = 1;
                $salesDetailInfo['sales_stock_quantity'] = $row['quantity'];
                $salesDetailInfo['sales_quantity'] = $row['quantity'] * $row['min_quantity'];
                $salesDetailInfo['sales_unit_price'] = $row['normal_sales_price'];
                $salesDetailInfo['sales_total'] = $row['normal_sales_price'] * $row['quantity'] * $row['min_quantity'];
                $salesDetailInfo['cost_unit_price'] = $row['purchase_price'];
                $salesDetailInfo['cost_total'] = $row['purchase_price'] * $row['quantity'] * $row['min_quantity'];
                $salesDetailId = $SalesDetail->addCounterSale($salesDetailInfo);

                $index = $index + 1;
            }

            //在庫数にエラーがあった場合ロールバック
            if (count($stockErr) > 0) {
                DB::rollBack();
                return \Response::json($stockErr);
            } else {
                DB::commit();
            }

            return \Response::json(true);
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
    }

    /**
     * 領収書印刷
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function print(Request $request)
    {
        try {
            $Product = new Product();
            $params = $request->request->all();
            $customerlist = $params['customerList'];
            $customerName = SystemUtil::truncate($params['address'], 40);                                          //宛名
            $salesDate = Carbon::now()->format('Y年m月d日');
            $departmentName = SystemUtil::truncate($customerlist['department_name'], 25);    //販売店名（部門名）
            $staffName = SystemUtil::truncate($customerlist['staff_name'], 25);    //販売担当者（スタッフ名）
            $tableData = $params['tableData'];                                      //商品情報テーブル
            $total = $params['total'];                                              //小計
            $tax = $params['tax'];                                                  //消費税
            $taxTotal = $params['taxTotal'];                                        //合計
            $Company = new Company();
            $companyData = $Company->first();
            $companyName = $companyData['company_name'];                            //(自社)会社名
            $companyAddress = $companyData['address1'] . $companyData['address2'];  //(自社)住所
            $companyTel = $companyData['tel'];                                      //(自社)電話番号
            // $sign = $params['sign'];                                                //サイン※2毎目の控えのみ

            $copyFlg = $params['copy_flg'];


            //領収書発行---------------
            // 発行枚数
            $hData['number_total'] = str_pad(1, 2, 0, STR_PAD_LEFT);

            $formatIdNum = "";
            $formatIdIdx = 0;

            // フッター情報
            if (!$copyFlg) {
                // 1枚目
                $footerTxt = '';

                $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=1";

                // ヘッダー情報
                $headerTxt = '';

                $headerTxt .= "&__format[" . $formatIdIdx . "].customer_name=" . rawurlencode($customerName);
                $headerTxt .= "&__format[" . $formatIdIdx . "].sales_date=" . rawurlencode($salesDate);
                $headerTxt .= "&__format[" . $formatIdIdx . "].department_name=" . rawurlencode($departmentName);
                $headerTxt .= "&__format[" . $formatIdIdx . "].staff_name=" . rawurlencode($staffName);

                $formatIdIdx++;

                $detailTxt = '';
                // 明細情報
                foreach ($tableData as $key => $row) {
                    $productData = $Product->getById($row['product_id']);
                    $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=2";

                    $detailTxt .= '&__format[' . $formatIdIdx . '].product_code=' . rawurlencode(SystemUtil::truncate($row['product_code'], 40));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].product_name=' . rawurlencode(SystemUtil::truncate($row['product_name'], 40));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].model=' . rawurlencode(SystemUtil::truncate($row['model'], 40));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].quantity=' . rawurlencode(SystemUtil::truncate($row['quantity'], 10, '.0'));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].price=' . rawurlencode(SystemUtil::truncate($row['sales_amount'], 10));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].unit=' . rawurlencode(SystemUtil::truncate($row['unit'], 10));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].memo=' . rawurlencode(SystemUtil::truncate($productData['memo'], 10));

                    // 末尾商品以外に区切り文字挿入
                    if (count($tableData) > $key+1) {
                        $formatIdIdx++;
                        $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=4";
                    }

                    $formatIdIdx++;
                }

                $footerTxt .= "&__format[" . $formatIdIdx . "].price=" . rawurlencode($total);
                $footerTxt .= "&__format[" . $formatIdIdx . "].tax=" . rawurlencode($tax);
                $footerTxt .= "&__format[" . $formatIdIdx . "].total_price=" . rawurlencode($taxTotal);
                $footerTxt .= "&__format[" . $formatIdIdx . "].company_name=" . rawurlencode($companyName);
                $footerTxt .= "&__format[" . $formatIdIdx . "].address=" . rawurlencode($companyAddress);
                $footerTxt .= "&__format[" . $formatIdIdx . "].tel=" . rawurlencode($companyTel);
                // $footerTxt .= "&__format[". $formatIdIdx. "].sign=".urlencode($sign);

                $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=3";
            } else {
                // 二枚目(控え)
                $footerTxt = '';

                $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=5";

                // ヘッダー情報
                $headerTxt = '';

                $headerTxt .= "&__format[" . $formatIdIdx . "].customer_name=" . rawurlencode($customerName);
                $headerTxt .= "&__format[" . $formatIdIdx . "].sales_date=" . rawurlencode($salesDate);
                $headerTxt .= "&__format[" . $formatIdIdx . "].department_name=" . rawurlencode($departmentName);
                $headerTxt .= "&__format[" . $formatIdIdx . "].staff_name=" . rawurlencode($staffName);

                $formatIdIdx++;

                $detailTxt = '';
                // 明細情報
                foreach ($tableData as $key => $row) {
                    $productData = $Product->getById($row['product_id']);
                    $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=6";

                    $detailTxt .= '&__format[' . $formatIdIdx . '].product_code=' . rawurlencode(SystemUtil::truncate($row['product_code'], 40));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].product_name=' . rawurlencode(SystemUtil::truncate($row['product_name'], 40));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].model=' . rawurlencode(SystemUtil::truncate($row['model'], 40));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].quantity=' . rawurlencode(SystemUtil::truncate($row['quantity'], 10, '.0'));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].price=' . rawurlencode(SystemUtil::truncate($row['sales_amount'], 10));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].unit=' . rawurlencode(SystemUtil::truncate($row['unit'], 10));
                    $detailTxt .= '&__format[' . $formatIdIdx . '].memo=' . rawurlencode(SystemUtil::truncate($productData['memo'], 10));

                    // $detailTxt .= '&__format[' . $formatIdIdx . '].product_code=' . rawurlencode($row['product_code']);
                    // $detailTxt .= '&__format[' . $formatIdIdx . '].product_name=' . rawurlencode($row['product_name']);
                    // $detailTxt .= '&__format[' . $formatIdIdx . '].model=' . rawurlencode($row['model']);
                    // $detailTxt .= '&__format[' . $formatIdIdx . '].price=' . rawurlencode($row['sales_amount']);
                    // $detailTxt .= '&__format[' . $formatIdIdx . '].quantity=' . rawurlencode($row['quantity'] . '.0');
                    // $detailTxt .= '&__format[' . $formatIdIdx . '].unit=' . rawurlencode($row['unit']);
                    // $detailTxt .= '&__format[' . $formatIdIdx . '].memo=' . rawurlencode($productData['memo']);

                    // 末尾商品以外に区切り文字挿入
                    if (count($tableData) > $key+1) {
                        $formatIdIdx++;
                        $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=8";
                    }

                    $formatIdIdx++;
                }

                $footerTxt .= "&__format[" . $formatIdIdx . "].price=" . rawurlencode($total);
                $footerTxt .= "&__format[" . $formatIdIdx . "].tax=" . rawurlencode($tax);
                $footerTxt .= "&__format[" . $formatIdIdx . "].total_price=" . rawurlencode($taxTotal);
                $footerTxt .= "&__format[" . $formatIdIdx . "].company_name=" . rawurlencode($companyName);
                $footerTxt .= "&__format[" . $formatIdIdx . "].address=" . rawurlencode($companyAddress);
                $footerTxt .= "&__format[" . $formatIdIdx . "].tel=" . rawurlencode($companyTel);
                // $footerTxt .= "&__format[". $formatIdIdx. "].sign=".urlencode($sign);

                $formatIdNum .= "&__format_id_number[" . $formatIdIdx . "]=7";
            }

            $isSecure = false;
            if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
                // HTTPS通信の場合の処理
                $isSecure = true;
            }


            // 印刷用URL生成
            $query = '?__success_redirect_url=googlechrome://&__failure_redirect_url=' . url('/smapri-failed', null, $isSecure) . '&__format_archive_url=' . url(config('const.qrLabelPath') . config('const.printer.printCounterSalesName'), null, $isSecure) . $formatIdNum;
            // .'&__format_id_number=1&start_number=01';
            $query .=  $headerTxt . $detailTxt . $footerTxt;
            // $query .=  '&' . http_build_query($hData);

            // 使用端末がiosか判定
            $SystemUtil = new SystemUtil();
            $isIos = $SystemUtil->judgeDevice();

            // ドメイン取得
            // $domain = url('/', null, $isSecure);
            $domain = (empty($_SERVER["HTTPS"]) ? "http://" : "http://"). 'localhost';
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
            $result = true;
            //         break;
            //     default:
            //         $result = false;
            //         break;
            // }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($result);
    }
}
