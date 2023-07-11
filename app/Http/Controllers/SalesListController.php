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
use App\Models\Construction;
use App\Models\Credited;
use App\Models\CustomerBranch;
use App\Models\Delivery;
use App\Models\Returns;
use App\Models\Department;
use App\Models\Loading;
use App\Models\Notice;
use App\Models\Payment;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Requests;
use App\Models\Reserve;
use App\Models\Sales;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Warehouse;
use App\Models\SalesDetail;
use App\Models\System;
use Exception;

/**
 * 売上一覧
 */
class SalesListController extends Controller
{
    const SCREEN_NAME = 'sales-list';
    const MATTER_COMP_NAME = 'matter-detail';


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
     * @param $matterId
     * @return type
     */
    public function index(Request $request)
    {    
        // モデル
        $SystemUtil     = new SystemUtil();
        $Department     = new Department();
        $StaffDepartment= new StaffDepartment();
        $Staff          = new Staff();
        $Customer       = new Customer();
        $Matter         = new Matter();
        $QuoteDetail    = new QuoteDetail();
        $LockManage     = new LockManage();
        $General        = new General();
        $Quote          = new Quote();
        $SalesDetail    = new SalesDetail();
        $Delivery       = new Delivery();
        $Returns        = new Returns();
        $Construction   = new Construction();
        $Requests       = new Requests();


        $loginInfo          = null;

        $departmentList     = null;
        $staffDepartmentList= null;
        $staffList          = null;
        $customerList       = null;


        
        // 請求情報
        $defaultFilterInfo  = [
            'other' => [],
        ];
        
        
        
        DB::beginTransaction();
        try {
            $loginInfo  = Auth::user();
            /* デフォルトのフィルター状態 */
            $ZERO_SALES =  $request->query('zero_sales');
            $COST_EDIT  =  $request->query('cost_edit');
            if(Common::nullorBlankToZero($ZERO_SALES) !== 0 && intval($ZERO_SALES) === config('const.salesDetailFilterInfo.OTHER.key.zero_sales')){
                $defaultFilterInfo['other'][] = config('const.salesDetailFilterInfo.OTHER.key.zero_sales');
            }
            if(Common::nullorBlankToZero($COST_EDIT) !== 0 && intval($COST_EDIT) === config('const.salesDetailFilterInfo.OTHER.key.cost_edit')){
                $defaultFilterInfo['other'][] = config('const.salesDetailFilterInfo.OTHER.key.cost_edit');
            }


            /** データ取得 **/
            // 部門リスト取得
            $departmentList         = $Department->getComboList();
            // 担当部門リスト取得
            $staffDepartmentList    = $StaffDepartment->getCurrentTermList();
            // 担当者リスト取得
            $staffList              = $Staff->getComboList();
            // メインの部署をログイン情報に追加
            $tmpStaffDepartment = $staffDepartmentList->where('staff_id', $loginInfo->id)->first();
            if(!empty($tmpStaffDepartment)){
                $departmentId = $tmpStaffDepartment->department_id;
                $loginInfo->department_id = $departmentId;
            }
            // 得意先リスト取得
            $customerList = $Customer->getDetailInfoRegisterComboList(config('const.flg.off'));
            
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }


        return view('Sales.sales-list')
                ->with('loginInfo',             $loginInfo)
                ->with('salesStatusList',       json_encode(config('const.salesStatus')))
                ->with('requestStatusList',     json_encode(config('const.requestStatus')))
                ->with('salesDetailFilterInfoList', json_encode(config('const.salesDetailFilterInfo')))

                ->with('departmentList',        $departmentList)
                ->with('staffDepartmentList',   $staffDepartmentList)
                ->with('staffList',             $staffList)
                ->with('customerList',          $customerList)
                ;
    }

    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        $result     = array('status' => false, 'request_info' => null, 'sales_info' => null, 'message'=> '');
        $params     = $request->request->all();

        $SystemUtil = new SystemUtil();
        $System     = new System();
        $Matter     = new Matter();
        $Sales      = new Sales();
        $Requests   = new Requests();
        


        try {

            $systemList = $System->getByPeriod();
            $TAX_RATE   = $systemList['tax_rate'];

            // その他案件作成
            $createUseSalesMatterMessage = $this->createUseSalesMatter($params['customer_id'], $TAX_RATE);
            $result['message'] = $createUseSalesMatterMessage;
            
            
            // 請求データ情報(売上期間などをセット)
            $requestInfo    = $SystemUtil->getStrictCurrentMonthRequestPeriod($params['customer_id']);

            
            // 得意先IDから過去の請求取得
            // $isClosed = true;
            // $requestList = $Requests->getByCustomerId($requestInfo->customer_id);
            // if (count($requestList) > 0) {
            //     // 「締め済」でない請求が1件でもあればfalse
            //     $isClosed = count(collect($requestList)->whereNotIn('status', [config('const.requestStatus.val.close')])) == 0;
            // }

            if($requestInfo->status === config('const.requestStatus.val.unprocessed')){
                
                $isNew = false;
                if (empty($requestInfo->before_id)) {
                    $isNew = true;
                }

                if(isset($params['request_e_day']) || $isNew){
                    if(!$isNew && $requestInfo->request_e_day !== $params['request_e_day']){
                        // 過去の請求がある
                        $tmpDateData    = $SystemUtil->getCurrentMonthRequestPeriod($requestInfo->customer_id, $params['request_e_day']);

                        if ($tmpDateData['request_mon'] != $requestInfo->request_mon) {
                            // 締前の請求と計上月が違う場合
                            $notColoseRequestMon = $Requests->getNotColoseRequestMon($requestInfo->customer_id);
                            if ($notColoseRequestMon !== null) {
                                // 前回の請求が締められていない場合、同じ計上月を使う
                                $tmpDateData['request_mon'] = $notColoseRequestMon->request_mon;
                            }

                            // 違算+売掛
                            $tmpCarryForwardAmount = $this->getCarryforwardAmount($requestInfo->customer_id);
                            $sumAmount = $tmpCarryForwardAmount['different_amount'] + $tmpCarryForwardAmount['receivable'];
                            // if (!Common::isFlgOn($isClosed) || $sumAmount !== 0) {
                            //     // 過去請求が締められてない or 違算+売掛金がある場合は不可
                            //     $result['message'] = config('message.error.sales.not_closed_request');
                            //     throw new \Exception();
                            // } 
                            // 請求月を日本語化
                            $requestInfo->request_mon_text = '';
                            $requestInfo->request_mon_text = $SystemUtil->formatRequestMon($tmpDateData['request_mon'], true);
                            $requestInfo->request_mon = $tmpDateData['request_mon'];
                        }

                        $requestInfo->request_e_day     = $params['request_e_day'];
                        $requestInfo->shipment_at       = (new Carbon($params['request_e_day']))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');   // 請求発想予定日
                    } else if($isNew){
                        // 初回請求
                        $requestEDay = '';
                        $requestEDay = isset($params['request_e_day']) ? $params['request_e_day'] : $requestInfo->request_e_day;
                        // $tmpDateData = $SystemUtil->getNewCustomerMonthRequestPeriod($params['customer_id'], $params['request_e_day']);
                        $tmpDateData = $SystemUtil->getNewCustomerStrictCurrentMonthRequestPeriod($params['customer_id'], $requestEDay);
                        
                        $requestInfo->request_mon        = $tmpDateData['request_mon'];
                        $requestInfo->request_mon_text   = $tmpDateData['request_mon_text'];
                        $requestInfo->closing_day        = $tmpDateData['closing_day'];
                        $requestInfo->request_s_day      = $tmpDateData['request_s_day'];
                        $requestInfo->request_e_day      = $tmpDateData['request_e_day'];
                        $requestInfo->expecteddeposit_at = $tmpDateData['expecteddeposit_at'];
                        if (!empty($params['request_e_day'])) {
                            $requestInfo->shipment_at        = (new Carbon($params['request_e_day']))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');   // 請求発想予定日
                        }
                    }
                }
                // 前回請求金額、入金金額、相殺その他
                $tmpRequestAmount                   = $this->setRequestAmount($requestInfo->customer_id, $requestInfo->request_s_day, $requestInfo->request_e_day);
                $requestInfo->lastinvoice_amount    = $tmpRequestAmount['lastinvoice_amount'];
                $requestInfo->deposit_amount        = $tmpRequestAmount['deposit_amount'];
                $requestInfo->offset_amount         = $tmpRequestAmount['offset_amount'];

                // 違算、売掛、相殺、繰越
                $tmpCarryForwardAmount              = $this->getCarryforwardAmount($requestInfo->customer_id);
                $requestInfo->different_amount      = $tmpCarryForwardAmount['different_amount'];
                $requestInfo->receivable            = $tmpCarryForwardAmount['receivable'];
                $requestInfo->payment_offset        = $tmpCarryForwardAmount['payment_offset'];             // 相殺カラムは存在しない
                $requestInfo->carryforward_amount   = $tmpCarryForwardAmount['carryforward_amount'];
            }else{
                $requestInfo->payment_offset        = 0;                                                    // 相殺カラムを用意しておく
            }



            $totalSales = 0;
            $totalPurchaseVolume = 0;
            $totalAdditionalDiscountAmount = 0;

            // 案件情報
            $salesInfo      = $Matter->getSalesListData($params);

            foreach($salesInfo as $cnt => $matter){
                $salesData = $Sales->getSalesDetailDataList($matter->matter_id, $matter->matter_no, $matter->quote_id, $requestInfo->id);
                $deliveryQuantity = 0;
                $returnQuantity = 0;
                $sales = 0;
                $purchaseVolume = 0;
                $additionalDiscountAmount = 0;
                $zeroSalesCnt = 0;
                $updateCostUnitPriceCnt = 0;
                foreach($salesData['salesDetailDataList'] as $filterTreePath => $detailDataList){
                    foreach($detailDataList as $key => $row){
                        if($this->isSalesPeriod($row['sales_date'], $requestInfo->request_s_day, $requestInfo->request_e_day)){
                            switch($row['sales_flg']){
                                case config('const.salesFlg.val.delivery'):
                                    if($row['notdelivery_flg'] !== config('const.notdeliveryFlg.val.create')){
                                        // 当月の納品数(実際の物としての納品数を合計するため相殺は対象外)
                                        $deliveryQuantity += Common::nullorBlankToZero($row['sales_stock_quantity']);
                                    }
                                    break;
                                case config('const.salesFlg.val.return'):
                                    // 当月の返品数
                                    $returnQuantity += Common::nullorBlankToZero($row['sales_stock_quantity']);
                                    break;
                                case config('const.salesFlg.val.discount'):
                                    // 当月の値引き額
                                    $additionalDiscountAmount += Common::nullorBlankToZero($row['sales_total']);
                                    break;
                            }
                            if($row['notdelivery_flg'] !== config('const.notdeliveryFlg.val.invalid')){
                                // 相殺元行を除く
                                // 当月の販売合計
                                $sales += Common::nullorBlankToZero($row['sales_total']);
                                // 当月の仕入合計
                                $purchaseVolume += Common::nullorBlankToZero($row['cost_total']);
                            }
                        }
                    }
                }

                foreach($salesData['salesDataList'] as $row){
                    if($row['invalid_unit_price_flg'] === config('const.flg.off')){
                        if(intval(Common::nullorBlankToZero($row['sales_unit_price'])) === 0){
                            $zeroSalesCnt++;
                        }
                    }
                    if(intval(Common::nullorBlankToZero($row['update_cost_unit_price'])) !== 0){
                        $updateCostUnitPriceCnt++;
                    }
                }


                $salesInfo[$cnt]->sales             = $sales;
                $salesInfo[$cnt]->purchase_volume   = $purchaseVolume;
                $salesInfo[$cnt]->profit_total      = $sales - $purchaseVolume;
                $salesInfo[$cnt]->gross_profit_rate = $SystemUtil->calcRate($salesInfo[$cnt]->profit_total, $salesInfo[$cnt]->sales);
                $salesInfo[$cnt]->additional_discount_amount    = $additionalDiscountAmount;
                
                $salesInfo[$cnt]->delivery_quantity = $deliveryQuantity;
                $salesInfo[$cnt]->return_quantity   = $returnQuantity;

                $salesInfo[$cnt]->zero_sales_cnt    = $zeroSalesCnt;
                $salesInfo[$cnt]->update_cost_unit_price_cnt    = $updateCostUnitPriceCnt;

                // 請求の合計
                $totalSales             += $salesInfo[$cnt]->sales;
                $totalPurchaseVolume    += $salesInfo[$cnt]->purchase_volume;
                $totalAdditionalDiscountAmount  += $salesInfo[$cnt]->additional_discount_amount;
                
            }

            if($requestInfo->status === config('const.requestStatus.val.unprocessed')){
                $requestInfo->sales             = $totalSales;
                $requestInfo->purchase_volume   = $totalPurchaseVolume;
                $requestInfo->additional_discount_amount = $totalAdditionalDiscountAmount;

                $requestInfo->consumption_tax_amount    = $this->roundTax($requestInfo->sales * ($TAX_RATE/100));
            }
            // 請求データに粗利はないため確定済みでも計算させる
            $requestInfo->profit_total      = $requestInfo->sales - $requestInfo->purchase_volume;
            $requestInfo->gross_profit_rate = $SystemUtil->calcRate($requestInfo->profit_total, $requestInfo->sales);
            // 画面表示用:税込当月売上合計 = 当月売上高 + 消費税額 + 税調整額
            $requestInfo->display_total_sales = Common::nullorBlankToZero($requestInfo->sales) + Common::nullorBlankToZero($requestInfo->consumption_tax_amount) + Common::nullorBlankToZero($requestInfo->discount_amount);
            // 消費税額 = 消費税額 + 税調整額
            $requestInfo->display_consumption_tax_amount = Common::nullorBlankToZero($requestInfo->consumption_tax_amount) + Common::nullorBlankToZero($requestInfo->discount_amount);
            
            $result['request_info'] = $requestInfo;
            $result['sales_info']   = $salesInfo;
            $result['status']       = true;
        } catch (\Exception $e) {
            $result['status'] = false;
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * その他案件作成
     * @param $customerId   得意先ID
     * @param $TAX_RATE     税率
     */
    private function createUseSalesMatter($customerId, $TAX_RATE){
        $message        = '';
        $Customer       = new Customer();
        $Matter         = new Matter();
        $Quote          = new Quote();
        $QuoteVersion   = new QuoteVersion();
        $NumberManage   = new NumberManage();

        // 売上専用案件を取得　無ければ作成
        $useSalesMatterInfo = $Matter->getByUseSalesFlg(config('const.flg.on'), $customerId)->first();
        if($useSalesMatterInfo === null){
            if($this->lockTransaction($customerId, true)){

                DB::beginTransaction();

                try {
                    $cusomerInfo    = $Customer->getById($customerId);

                    // 案件登録
                    $matterData = [];
                    $matterData['customer_id']      = $customerId;
                    $matterData['department_id']    = $cusomerInfo->charge_department_id;
                    $matterData['staff_id']         = $cusomerInfo->charge_staff_id;
                    $matterData['owner_name']       = config('const.salesMatterInfo.val.owner_name');
                    $matterData['use_sales_flg']    = config('const.flg.on');
                    $matterData['architecture_type']= config('const.salesMatterInfo.val.architecture_type');     // TODO:建築種別
                    $matterId                       = $Matter->add($matterData);

                    $tmpMatter  = $Matter->getById($matterId);
                    $matterNo   = $tmpMatter->matter_no;

                    // 見積登録
                    $quoteData = [];
                    $quoteData['quote_no']      = $NumberManage->getSeqNo(config('const.number_manage.kbn.quote'), Carbon::today()->format('Ym'));
                    $quoteData['matter_no']     = $matterNo;
                    $Quote->add($quoteData);

                    // 見積版0版登録
                    $quoteVerData = [];
                    $quoteVerData['quote_no']           = $quoteData['quote_no'];
                    $quoteVerData['quote_version']      = config('const.quoteCompVersion.number');
                    $quoteVerData['caption']            = config('const.quoteCompVersion.caption');
                    $quoteVerData['department_id']      = $matterData['department_id'];
                    $quoteVerData['staff_id']           = $matterData['staff_id'];
                    $quoteVerData['payment_condition']  = config('const.paycon.usual'); // TODO:支払い条件
                    $quoteVerData['tax_rate']           = $TAX_RATE;

                    $quoteVerData['status']             = config('const.quoteVersionStatus.val.approved');
                    $QuoteVersion->add($quoteVerData);

                    DB::commit();

                } catch (\Exception $e) {
                    DB::rollBack();
                    if($message === ''){
                        $message = config('message.error.sales.create_user_sales_matter');
                    }
                    Log::error($e);
                } finally{
                    // ロックの解除
                    if(!$this->lockTransaction($customerId, false)){
                        // ロック解除に失敗
                    }
                }
            }else{
                $message = config('message.error.sales.create_user_sales_matter_lock');
            }
        }
        return $message;
    }

    /**
     * 期間内か
     * @param $salesDate
     * @param $requestSDay
     * @param $requestEDay
     */
    private function isSalesPeriod($salesDate, $requestSDay, $requestEDay){
        $result = false;
        $salesDate       = new Carbon($salesDate);
        $requestSDay    = new Carbon($requestSDay);
        $requestEDay    = new Carbon($requestEDay);
        if($salesDate->gte($requestSDay) && $salesDate->lte($requestEDay)){
            $result = true;
        }
        return $result;
    }

    /**
     * 保存時のチェック
     * @param Request $request
     * @return type
     */
    public function confirmSave(Request $request)
    {
        $result = array('status' => false, 'message' => '');
        $Matter     = new Matter();

        // リクエストデータ取得
        $params         = $request->request->all();
        $customerId     = $params['customer_id'];       // 得意先ID

        try{
            $confirmMatterList = $Matter->getNotCreateNotDeliveryData($customerId);

            if(count($confirmMatterList) >= 1){
                $result['message'] = config('message.confirm.sales.not_create_notdelivery_data');
                foreach($confirmMatterList as $name){
                    $result['message'] .= '\n・'.$name;
                }
            }else{
                $result['message'] = config('message.confirm.sales.sales_complete');
            }

            $result['status']   = true;
        } catch (\Exception $e) {
            $result['status'] = false;
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
        $result = array('status' => false, 'message' => '');

        // モデル
        $Department     = new Department();
        $Matter         = new Matter();
        $Quote          = new Quote();
        $QuoteDetail    = new QuoteDetail();
        $Sales          = new Sales();
        $SalesDetail    = new SalesDetail();
        $Delivery       = new Delivery();
        $Returns        = new Returns();
        $Requests       = new Requests();
        $Notice         = new Notice();
        $System         = new System();
        $SystemUtil     = new SystemUtil();
        $Credited       = new Credited();

        // 請求情報格納
        $requestInfo    = [];

        // リクエストデータ取得
        $params         = $request->request->all();

        // 画面で変更できる請求の項目
        $requestInfo['customer_id']         = $params['customer_id'];       // 得意先ID
        $requestInfo['request_e_day']       = $params['request_e_day'];     // 売上期間終了
        $requestInfo['shipment_at']         = $params['shipment_at'];       // 請求発送予定
        $requestInfo['expecteddeposit_at']  = $params['expecteddeposit_at'];// 入金予定日
        $requestInfo['discount_amount']     = $params['discount_amount'];   // 税調整額
        $requestInfo['request_mon']         = $params['request_mon'];       // 計上月

        
        // 入力チェック
        //$this->isValidShipment($request);

        if($this->lockTransaction($requestInfo['customer_id'], true)){

            // ロックを持っているorロックを誰も持っていない
            $lockReleaseFlg = false;
            DB::beginTransaction();

            try {
                $userId     = Auth::user()->id;         // 申請者
                $now        = Carbon::now();            // 確定日
                $systemList = $System->getByPeriod();
                $TAX_RATE   = $systemList['tax_rate'];

                $tmpRequestInfo                 = $SystemUtil->getStrictCurrentMonthRequestPeriod($requestInfo['customer_id']);

                if (empty(Common::nullorBlankToZero($tmpRequestInfo->id))) {
                    // 過去の請求データなし(新規)
                    if(isset($params['request_e_day']) || !isset($tmpRequestInfo->id)){
                        if(!isset($tmpRequestInfo->id) || $tmpRequestInfo->request_e_day !== $params['request_e_day']){
                            // $tmpDateData = $SystemUtil->getNewCustomerMonthRequestPeriod($params['customer_id'], $params['request_e_day']);
                            $tmpDateData = $SystemUtil->getNewCustomerStrictCurrentMonthRequestPeriod($requestInfo['customer_id'], $params['request_e_day']);
                            
                            $tmpRequestInfo->request_mon        = $tmpDateData['request_mon'];
                            $tmpRequestInfo->request_mon_text   = $tmpDateData['request_mon_text'];
                            $tmpRequestInfo->closing_day        = $tmpDateData['closing_day'];
                            $tmpRequestInfo->request_s_day      = $tmpDateData['request_s_day'];
                            $tmpRequestInfo->request_e_day      = $tmpDateData['request_e_day'];
                            $tmpRequestInfo->expecteddeposit_at = $tmpDateData['expecteddeposit_at'];
                            // $tmpRequestInfo->shipment_at        = (new Carbon($params['request_e_day']))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');   // 請求発想予定日
                        }
                    }
                }

                $requestInfo['id']                  = $tmpRequestInfo->id;              // ID
                // $requestInfo['request_no']          = $tmpRequestInfo->request_no;      // 請求番号
                $requestInfo['customer_name']       = $tmpRequestInfo->customer_name;   // 得意先名  
                $requestInfo['charge_department_id']    = $tmpRequestInfo->charge_department_id;        // 担当部門ID
                $requestInfo['charge_department_name']  = $tmpRequestInfo->charge_department_name;      // 担当部門名
                $requestInfo['charge_staff_id']         = $tmpRequestInfo->charge_staff_id;             // 担当者ID
                $requestInfo['charge_staff_name']       = $tmpRequestInfo->charge_staff_name;           // 担当者名
                // $requestInfo['request_mon']         = $tmpRequestInfo->request_mon;     // 請求月
                $requestInfo['closing_day']         = $tmpRequestInfo->closing_day;     // 締日
                $requestInfo['request_s_day']       = $tmpRequestInfo->request_s_day;   // 売上開始日
                $requestInfo['be_request_e_day']    = $tmpRequestInfo->be_request_e_day;// 変更前の売上終了日
                $requestInfo['receivable']          = Common::nullorBlankToZero($tmpRequestInfo->receivable);           // 売掛金
                $requestInfo['different_amount']    = Common::nullorBlankToZero($tmpRequestInfo->different_amount);     // 違算
                $requestInfo['carryforward_amount'] = Common::nullorBlankToZero($tmpRequestInfo->carryforward_amount);  // 繰越金額
                $requestInfo['purchase_volume']     = 0;    // 当月仕入高(計算)
                $requestInfo['sales']               = 0;    // 当月売上高(計算)
                $requestInfo['additional_discount_amount']  = 0;// 値引き追加額(計算)
                $requestInfo['consumption_tax_amount']      = 0;// 消費税額(計算)   未使用
                $requestInfo['total_sales']         = 0;        // 売上合計(計算)
                $requestInfo['display_total_sales']         = 0;        // 売上合計(表示)
                $requestInfo['display_consumption_tax_amount']         = 0;        // 消費税額(表示)
                $requestInfo['request_amount']      = 0;        // 請求金額(計算)
                $requestInfo['sales_category']      = config('const.sales_category.val.sales');
                $requestInfo['status']              = config('const.requestStatus.val.complete');


                // 前回請求金額、入金金額、相殺その他
                $tmpRequestAmount                   = $this->setRequestAmount($requestInfo['customer_id'], $requestInfo['request_s_day'], $requestInfo['request_e_day']);
                $requestInfo['lastinvoice_amount']  = $tmpRequestAmount['lastinvoice_amount'];  // 前回請求金額
                $requestInfo['deposit_amount']      = $tmpRequestAmount['deposit_amount'];      // 入金金額
                $requestInfo['offset_amount']       = $tmpRequestAmount['offset_amount'];       // 相殺その他


                // 請求のエラーチェック
                $errMessage = $this->chkRequestData($requestInfo, $params['request_id'], $params['request_s_day']);
                if($errMessage !== ''){
                    $result['message'] = $errMessage;
                    throw new \Exception();
                }

                
                // 請求データ作成/更新
                if(!isset($requestInfo['id']) || Common::nullorBlankToZero($requestInfo['id']) == 0){
                    // 新規請求
                    $requestSaveData    = $requestInfo;
                    unset($requestSaveData['id']);
                    unset($requestSaveData['display_total_sales']);
                    unset($requestSaveData['display_consumption_tax_amount']);
                    $requestInfo['id'] = $Requests->add($requestSaveData);
                }

                // 申請中の売上リスト(キーは案件ID)
                $salesApplyingList  = [];

                // 案件データ取得
                $salesInfo          = $Matter->getSalesListData($requestInfo);

                /* 売上データの登録更新 */
                /* 案件の数だけループ */
                foreach($salesInfo as $cnt => $matter){
                    $salesData = $Sales->getSalesDetailDataList($matter->matter_id, $matter->matter_no, $matter->quote_id, $requestInfo['id']);
                    $salesList = $salesData['salesDataList'];
                    $salesDetailList = $salesData['salesDetailDataList'];

                    // 売上データの新規登録
                    foreach($salesList as $key => $row){
                        $salesId                = $row['sales_id'];
                        $filterTreePath         = $row['filter_tree_path'];
                        
                        if(count($salesDetailList[$filterTreePath]) >= 1){
                            $row['matter_id']       = $matter->matter_id;
                            $row['matter_department_id']    = $matter->department_id;
                            $row['matter_staff_id'] = $matter->staff_id;
                            $row['quote_id']        = $matter->quote_id;
                            $row['update_cost_unit_price_d']    = empty($row['update_cost_unit_price_d']) ? null : $row['update_cost_unit_price_d'];
                            if(Common::nullorBlankToZero($salesId) === 0){
                                // 新規
                                $row['request_id']  = $requestInfo['id'];
                                $row['status_dep']  = $requestInfo['charge_department_id'];
                                $row['sales_id']    = $Sales->add($row);
                            }
                            $salesList[$key] = $row;


                            // 承認が終わっていない申請中のデータのチェック
                            if($row['status'] === config('const.salesStatus.val.applying')){
                                if(!isset($salesApplyingList[$matter->matter_id])){
                                    $salesApplyingList[$matter->matter_id] = $matter->owner_name;
                                }
                            }
                        }
                    }



                    // 売上明細の登録更新
                    foreach($salesList as $key => $salesRow){
                        $salesId            = $salesRow['sales_id'];
                        $filterTreePath     = $salesRow['filter_tree_path'];
                        $salesTotal = 0;
                        $costTotal  = 0;

                        // 期間外の納品が存在するフラグ
                        $outsideSalesPeriodExistFlg = false;
                        // 未確定の未納フラグ
                        $createNotDeliveryExistFlg  = false;

                        // 売上明細が無い場合は処理をしない(無駄な登録/削除を減らすための条件)
                        if(count($salesDetailList[$filterTreePath]) === 0){
                            continue;
                        }

                        // 売上明細の登録更新 売上の金額計算
                        foreach($salesDetailList[$filterTreePath] as $key => $salesDetailRow){
                            if(Common::nullToBlank($salesDetailRow['sales_update_date']) !== ''){
                                // 確定分はスキップ
                                continue;
                            }
                            $salesDetailRow['sales_id']     = $salesId;
                            $salesDetailRow['matter_id']    = $matter->matter_id;
                            $salesDetailRow['quote_id']     = $matter->quote_id;
                            
                            // 売上明細ID
                            $salesDetailId = $salesDetailRow['sales_detail_id'];

                            switch($salesDetailRow['sales_flg']) {
                                case config('const.salesFlg.val.discount'):
                                case config('const.salesFlg.val.production'):
                                case config('const.salesFlg.val.cost_adjust'):
                                    // 値引き、その他、調整金額明細の親は請求IDを更新する
                                    $salesRow['request_id']  = $requestInfo['id'];
                                    break;
                            }


                            // チェック用のフラグ
                            if($salesDetailRow['sales_flg'] === config('const.salesFlg.val.not_delivery')){
                                // 未確定の未納データ
                                $createNotDeliveryExistFlg = true;
                            }


                            
                            // 売上データ用の金額計算
                            if($this->isSalesPeriod($salesDetailRow['sales_date'], $requestInfo['request_s_day'], $requestInfo['request_e_day'])){
                                if($salesDetailRow['notdelivery_flg'] !== config('const.notdeliveryFlg.val.invalid')){
                                    $salesTotal += Common::nullorBlankToZero($salesDetailRow['sales_total']);
                                    $costTotal  += Common::nullorBlankToZero($salesDetailRow['cost_total']);
                                }

                                switch($salesDetailRow['sales_flg']){
                                    case config('const.salesFlg.val.discount'):
                                        // 当月の値引き額
                                        $requestInfo['additional_discount_amount'] += Common::nullorBlankToZero($salesDetailRow['sales_total']);
                                        break;
                                }
                                $salesDetailRow['request_id']           = $requestInfo['id'];
                                $salesDetailRow['sales_update_date']    = $now;
                                $salesDetailRow['sales_update_user']    = $userId;
                            }else{
                                $salesDate      = new Carbon($salesDetailRow['sales_date']);
                                $requestSDay    = new Carbon($requestInfo['request_s_day']);
                                if($requestSDay->gt($salesDate)){
                                    // 売上日が開始日よりも前になってますん
                                    $result['message'] = config('message.error.sales.not_sales_date');
                                    $result['message'] .= '\n・'.$matter->owner_name;
                                    throw new \Exception();
                                }
                                
                                // チェック用のフラグ
                                if($salesDetailRow['sales_flg'] === config('const.salesFlg.val.delivery')){
                                    // 期間外の納品データ
                                    $outsideSalesPeriodExistFlg = true;
                                }
                            }
                            if(Common::nullorBlankToZero($salesDetailId) === 0){
                                // 新規
                                $salesDetailRow['sales_detail_id']      = $SalesDetail->add($salesDetailRow);
                            }else{
                                // 編集
                                $SalesDetail->updateById($salesDetailId, $salesDetailRow);
                            }
                            //$salesDetailList[$filterTreePath][$key]     = $salesDetailRow;
                        }

                        // if($outsideSalesPeriodExistFlg && $createNotDeliveryExistFlg){
                        //     // 未確定の未納データがあるため納品データの売上日を売上期間外には設定できません
                        //     $result['message'] =  config('message.error.sales.outside_period_sales_date');
                        //     throw new \Exception();
                        // }



                        // 粗利
                        $salesRow['profit_total']             = $SystemUtil->calcProfitTotal($salesTotal, $costTotal);
                        // 粗利率
                        $salesRow['gross_profit_rate']        = $SystemUtil->calcRate($salesRow['profit_total'], $salesTotal);

                        // 請求用の売上総額
                        $requestInfo['sales']           += $salesTotal;
                        $requestInfo['purchase_volume'] += $costTotal;

                        // 担当部門、担当者更新
                        $salesRow['matter_department_id']   = $matter->department_id;
                        $salesRow['matter_staff_id']        = $matter->staff_id;

                        // 売上データの更新
                        if($salesRow['sales_flg'] !== config('const.salesDetailFlg.val.quote')){
                            $tmpSalesDetailRow = $salesDetailList[$salesRow['filter_tree_path']][0];
                            if(Common::nullToBlank($tmpSalesDetailRow['sales_update_date']) !== ''){
                                // 確定行
                            }else{
                                // 更新
                                $Sales->updateById($salesId, $salesRow);
                            }
                        }else{
                            // 更新
                            $Sales->updateById($salesId, $salesRow);
                        }

                        $salesList[$key] = $salesRow;
                    }

                    // 売上明細が紐づいていない売上を削除する
                    // 当月の子グリッドが無くても見積明細に紐づく過去の売上明細があれば保存対象とされるので削除
                    // 未来に伸ばした納品が当月に含まれた場合に紐づかなくなった過去の売上データも削除する
                    $Sales->deleteNoRelatedSalesData($matter->matter_id);

                }


                // 承認が終わっていない申請中のデータ
                if(count($salesApplyingList) >= 1){
                    $result['message'] = config('message.error.sales.applying');
                    foreach($salesApplyingList as $name){
                        $result['message'] .= '\n・'.$name;
                    }
                    throw new \Exception();
                }


                // 請求の更新
                // 違算、売掛、繰越
                $tmpCarryForwardAmount              = $this->getCarryforwardAmount($requestInfo['customer_id']);
                $requestInfo['different_amount']    = $tmpCarryForwardAmount['different_amount'];
                $requestInfo['receivable']          = $tmpCarryForwardAmount['receivable'];
                $requestInfo['carryforward_amount'] = $tmpCarryForwardAmount['carryforward_amount'];

                // 税額
                $requestInfo['consumption_tax_amount']    = $this->roundTax($requestInfo['sales'] * ($TAX_RATE/100));
                // 税込当月売上合計 = 当月売上高 + 税額 + 税調整額 + 違算 + (-相殺)
                $requestInfo['total_sales']               = $requestInfo['sales'] + $requestInfo['consumption_tax_amount'] + $requestInfo['discount_amount'] + $requestInfo['different_amount'] + ($tmpCarryForwardAmount['payment_offset'] * -1);
                // 税込当月請求合計 = 税込当月売上合計 + 売掛金
                $requestInfo['request_amount']            = $requestInfo['total_sales'] + $requestInfo['receivable'];

                $Requests->updateById($requestInfo['id'], $requestInfo);

                // ロック解除
                if($this->unLock($requestInfo['customer_id'])){
                    DB::commit();
                    $result['status'] = true;
                } else {
                    DB::rollBack();
                    $result['status'] = false;
                    $result['message'] = config('message.error.getlock');
                }
                $lockReleaseFlg = true;

            } catch (\Exception $e) {
                DB::rollBack();
                $result['status'] = false;
                if($result['message'] === ''){
                    $result['message'] = config('message.error.error');
                }
                Log::error($e);
            } finally {
                if (!$lockReleaseFlg) {
                    // ロックの解除
                    if(!$this->lockTransaction($requestInfo['customer_id'], false)){
                        // ロック解除に失敗
                    }
                }
            }
        }else{
            $result['message'] = config('message.error.getlock');
        }

        return \Response::json($result);
    }

    /**
     * 前回請求金額、入金金額、相殺その他
     * @param $customerId  得意先ID
     * @param $requestSDay  開始日
     * @param $requestEDay  終了日
     * @return $result
     */
    private function setRequestAmount($customerId, $requestSDay, $requestEDay){
        $Requests       = new Requests();
        $Credited       = new Credited();

        $result = [
            'lastinvoice_amount'    => 0,
            'deposit_amount'        => 0,
            'offset_amount'         => 0
        ];

        // 前回請求情報
        $tmpRequestInfo                 = $Requests->getLatestCompleteData($customerId);
        $result['lastinvoice_amount']   = $tmpRequestInfo !== null ? Common::nullorBlankToZero($tmpRequestInfo->request_amount) : 0;
                

        // 入金金額と相殺その他を取得する
        $creditedInfo                   = $Credited->getSalesDataInPeriod($customerId, $requestSDay, $requestEDay);
        if($creditedInfo !== null){
            $result['deposit_amount']   = Common::nullorBlankToZero($creditedInfo->total_deposit);
            $result['offset_amount']    = Common::nullorBlankToZero($creditedInfo->offset_ets);
        }
        return $result;
    }

    /**
     * 違算、売掛、繰越を返す
     * @param $customerId  得意先ID
     * @param $requestId  違算適用請求ID 相殺適用請求ID
     * @return $result
     */
    private function getCarryforwardAmount($customerId, $requestId = null){
        $Credited       = new Credited();
        $Requests       = new Requests();
        $Payment        = new Payment();

        $result = [
            'different_amount'      => 0,
            'receivable'            => 0,
            'payment_offset'        => 0,
            'carryforward_amount'   => 0
        ];

        // 違算を取得
        $result['different_amount']     = $Credited->getUnappliedDifferentAmountToRequest($customerId, $requestId);
        // 売掛を取得　請求.売上合計の合計
        $result['receivable']           = $Requests->getCustomerTotalSales($customerId);
        // 相殺を取得　支払予定.相殺の合計
        $result['payment_offset']       = $Payment->getCustomerTotalOffset($customerId, $requestId);
        // 繰越 = 違算 + 売掛
        $result['carryforward_amount']  = $result['different_amount'] + $result['receivable'];

        return $result;
    }

    /**
     * 請求データのチェック
     * @param $requestInfo  請求データの連想配列
     * @param $requestId    検索時に保持していた請求ID
     * @param $screenRequestSDay    画面上の売上開始日
     */
    private function chkRequestData($requestInfo, $requestId, $screenRequestSDay){
        $errMessage     = '';
        $Requests       = new Requests();

        $requestSDay        = new Carbon($requestInfo['request_s_day']);
        $requestEDay        = new Carbon($requestInfo['request_e_day']);
        $expecteddepositAt  = new Carbon($requestInfo['expecteddeposit_at']);
        $toDay              = new Carbon(Carbon::now()->format('Y/m/d'));


        if(empty($requestInfo['request_e_day'])){
            // 売上終了日が空白
            $errMessage = config('message.error.sales.required_request_e_day');
        }else if(empty($requestInfo['expecteddeposit_at'])){
            // 入金予定日が空白
            $errMessage = config('message.error.sales.required_expecteddeposit_at');
        }else if($requestSDay->gt($requestEDay)){
            // 開始日と終了日が逆転している
            $errMessage = config('message.error.sales.sales_date');
        }else if($toDay->gt($expecteddepositAt)){
            // 入金予定日が今日より前
            $errMessage = config('message.error.sales.expecteddeposit_at');
        }else{
            if($Requests->isExistByRequestSDay($requestInfo['customer_id'], $requestId, $screenRequestSDay)){
                // 同じ開始日の請求データがある(画面を開いている間に誰かが作成した)
                $errMessage = config('message.error.sales.exist_request_data');
            }else if(Common::nullorBlankToZero($requestId) != 0){
                $requestData = $Requests->getById($requestId);
                if($requestData == null || $requestData->status === config('const.requestStatus.val.complete')){
                    // 請求データが消えている or 既に確定している
                    $errMessage = config('message.error.sales.complete_request_status');
                }
            }
        }
        return $errMessage;
    }

    /**
     * 確定解除
     * @param Request $request
     * @return type
     */
    public function release(Request $request)
    {
        $result = array('status' => false, 'message' => '');

        // モデル
        $SalesDetail    = new SalesDetail();
        $Requests       = new Requests();
        $Credited       = new Credited();
        $Payment        = new Payment();

        // リクエストデータ取得
        $params         = $request->request->all();

        if($this->lockTransaction($params['customer_id'], true)){
            // ロックを持っているorロックを誰も持っていない
            $lockReleaseFlg = false;
            DB::beginTransaction();
            try {
    
                $requestData = $Requests->getById($params['request_id']);
                if($requestData == null || $requestData->status !== config('const.requestStatus.val.complete')){
                    // 確定済みでない
                    $result['message'] = config('message.error.sales.release_request_status');
                    throw new \Exception();
                }

                // 請求の更新
                $requestUpdateData = [];
                $requestUpdateData['status']    = config('const.requestStatus.val.unprocessed');
                $Requests->updateById($params['request_id'], $requestUpdateData);

                // 売上明細の更新
                $SalesDetail->updateSalesStatusByRequestId($params['request_id']);


                //  違算を戻して請求IDを削除する　入金の切り戻し
                // $Credited->restoreDifferentAmountToUnapplied($params['request_id']);
                // 相殺のID削除 支払予定の切り戻し
                $Payment->restoreOffsetToUnapplied($params['request_id']);
                
                // ロック解除
                if($this->unLock($params['customer_id'])){
                    DB::commit();
                    $result['status'] = true;
                } else {
                    DB::rollBack();
                    $result['status'] = false;
                    $result['message'] = config('message.error.getlock');
                }
                $lockReleaseFlg = true;

            } catch (\Exception $e) {
                DB::rollBack();
                $result['status'] = false;
                if($result['message'] === ''){
                    $result['message'] = config('message.error.error');
                }
                Log::error($e);
            } finally {
                if (!$lockReleaseFlg) {
                    // ロックの解除
                    if(!$this->lockTransaction($params['customer_id'], false)){
                        // ロック解除に失敗
                    }
                }
            }
        }else{
            $result['message'] = config('message.error.getlock');
        }

        return \Response::json($result);
    }

    /**
     * 削除
     * @param Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $result = array('status' => false, 'message' => '');

        // モデル
        $Sales          = new Sales();
        $Requests       = new Requests();

        // リクエストデータ取得
        $params         = $request->request->all();

        if($this->lockTransaction($params['customer_id'], true)){
            // ロックを持っているorロックを誰も持っていない
            $lockReleaseFlg = false;
            DB::beginTransaction();
            try {
    
                $requestData = $Requests->getById($params['request_id']);
                if($requestData == null || $requestData->status !== config('const.requestStatus.val.unprocessed')){
                    // 未処理でない
                    $result['message'] = config('message.error.sales.delete_request_status');
                    throw new \Exception();
                }

                // 売上と紐づく売上明細の削除
                $Sales->deleteByRequestId($params['request_id']);

                // 請求削除
                $Requests->deleteById($params['request_id']);
                
                // ロック解除
                if($this->unLock($params['customer_id'])){
                    DB::commit();
                    $result['status'] = true;
                } else {
                    DB::rollBack();
                    $result['status'] = false;
                    $result['message'] = config('message.error.getlock');
                }
                $lockReleaseFlg = true;

            } catch (\Exception $e) {
                DB::rollBack();
                $result['status'] = false;
                if($result['message'] === ''){
                    $result['message'] = config('message.error.error');
                }
                Log::error($e);
            } finally {
                if (!$lockReleaseFlg) {
                    // ロックの解除
                    if(!$this->lockTransaction($params['customer_id'], false)){
                        // ロック解除に失敗
                    }
                }
            }
        }else{
            $result['message'] = config('message.error.getlock');
        }

        return \Response::json($result);
    }

    /**
     * 承認ステータスを変更する
     * @param Request $request
     */
    public function changeStatus(Request $request){
        $result = array('status' => false, 'message' => '');

        $Sales          = new Sales();
        $Matter         = new Matter();
        $Department     = new Department();
        $SystemUtil     = new SystemUtil();
        $Notice         = new Notice();


        // リクエストデータ取得
        $params     = $request->request->all();

        if($this->lockTransaction($params['customer_id'], true)){
            // ロックを持っているorロックを誰も持っていない
            $lockReleaseFlg = false;
            DB::beginTransaction();
            try{
                $matterInfo = $Matter->getById($params['matter_id']);

                $applyingSalesList = $Sales->getApplyingSalesList($params['quote_id'], Auth::user()->id);
                if($applyingSalesList->count() >= 1){
                    $noticeData = [];
                    $noticeUrl = $this->createNoticeUrl($params['customer_id']);
                    foreach($applyingSalesList as $key => $salesData){
                        $statusDep = $salesData->status_dep;

                        // 承認部門の担当部門の情報
                        $departmentData = $Department->getById($statusDep);
                        // 次の部門ID
                        $parentDepartmentId = $departmentData->parent_id;

                        $updateResult = $SystemUtil->changeSalesStatus(
                            $params['is_approve'], 
                            $salesData, 
                            $params['request_id'], 
                            $params['customer_name'], 
                            $matterInfo->matter_name, 
                            $matterInfo->department_id, 
                            $parentDepartmentId, 
                            $noticeUrl, 
                            $noticeData
                        );
                    }

                    $notice = array_values($noticeData);
                    $Notice->addList($notice);
                    
                }else{
                    // 申請中のデータなし
                    $result['message'] = config('message.error.sales.approval_data_not_found');
                    throw new \Exception();
                }

                // ロック解除
                if($this->unLock($params['customer_id'])){
                    DB::commit();
                    $result['status'] = true;
                } else {
                    DB::rollBack();
                    $result['status'] = false;
                    $result['message'] = config('message.error.getlock');
                }
                $lockReleaseFlg = true;
                
            } catch (\Exception $e) {
                DB::rollBack();
                $result['status'] = false;
                if($result['message'] === ''){
                    $result['message'] = config('message.error.error');
                }
                Log::error($e);
            } finally {
                if (!$lockReleaseFlg) {
                    // ロックの解除
                    if(!$this->lockTransaction($params['customer_id'], false)){
                        // ロック解除に失敗
                    }
                }
            }
        }else{
            $result['message'] = config('message.error.getlock');
        }
        
        return \Response::json($result);
    }

    /**
     * 案件完了
     *
     * @param Request $request
     * @return void
     */
    public function complete(Request $request)
    {
        $result = ['status' => false, 'msg' => ''];

        // リクエストデータ取得
        $params = $request->request->all();
        $matterId = $params['matter_id'];

        $SystemUtil = new SystemUtil();
        $LockManage = new LockManage();
        $Matter = new Matter();


        // ロック取得で1回コミットする
        DB::beginTransaction();
        try {            
            $matter = $Matter->getById($matterId);
            if (is_null($matter)) {
                throw new \Exception(config('message.error.error').':'.$matterId);
            }

            $tableNameList = config('const.lockList.'.self::MATTER_COMP_NAME);
            $keys = array($matter->id);
            $lockCnt = 0;
            foreach ($tableNameList as $i => $tableName) {
                // ロック確認
                $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                if (!$isLocked) {
                    // ロックされていない場合はロック取得
                    $lockDt = Carbon::now();
                    $LockManage->gainLock($lockDt, self::MATTER_COMP_NAME, $tableName, $keys[$i]);
                }
                // ロックデータ取得
                $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                if (!is_null($lockData) 
                    && $lockData->screen_name === self::MATTER_COMP_NAME && $lockData->table_name === $tableName
                    && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                        $lockCnt++;
                }
            }
            if (count($tableNameList) === $lockCnt) {
                DB::commit();
            } else {
                // 排他ロックが取得できなかった場合、ここで処理終了
                throw new \Exception(config('message.error.getlock').':'.$matterId);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['msg'] = config('message.error.getlock');
            $result['status'] = false;
            return \Response::json($result);
        }
        
            
        DB::beginTransaction();
        try {
            // 案件完了
            $result = $SystemUtil->matterComplete($matterId);
            
            // ロック解放
            $delLock = $LockManage->deleteLockInfo(self::MATTER_COMP_NAME, $keys, Auth::user()->id);
            if (!$delLock) {
                $result['status'] = false;
                $result['msg'] = config('message.error.getlock');
                throw new \Exception(config('message.error.getlock').':'.$matterId);
            }

            if ($result['status']) {
                DB::commit();
                Session::flash('flash_success', config('message.success.complete'));
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['status'] = false;
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }

    /**
     * 消費税の端数処理 TODO
     * @param $value
     */
    private function roundTax($value){
        return Common::roundDecimalSalesPrice($value);
    }

    /**
     * 通知URLを作成
     * @param $customerId
     */
    private function createNoticeUrl($customerId){
        return url('sales-list?'.'customer_id='.$customerId);
    }

    /**
     * ロック処理まとめ(別トランザクション)
     * @param $customerId
     * @param $isGetLock    true:自身がロックを持っていなければロックを取得する　false:ロックを解除する
     * @return false        失敗
     */
    private function lockTransaction($customerId, $isGetLock=true){
        $result = true;
        DB::beginTransaction();
        try{
            if($isGetLock){
                // ロックを持っているか確認
                if(!$this->isOwnLocked($customerId)){
                    if(!$this->getLock($customerId)){
                        // 他の人がロックを持っている
                        $result = false;
                        throw new \Exception();
                    }
                }
            }else{
                // ロック解除
                if(!$this->unLock($customerId)){
                    $result = false;
                    throw new \Exception();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return $result;
    }

    /**
     * ロック取得
     * @param $customerId
     * @return false 誰かがロックしている
     */
    private function getLock($customerId){
        $result = false;

        $LockManage = new LockManage();

        // 画面名から対象テーブルを取得
        $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
        $keys = array(intval($customerId));

        $lockCnt = 0;
        foreach ($tableNameList as $i => $tableName) {
            // ロック確認
            $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
            if (!$isLocked) {
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
            $result = true;
        } 
        return $result;
    }
    
    /**
     * 自分がロックしているか
     * @param $keys
     * @return true：ロックを持っている
     */
    private function isOwnLocked(...$keys){
        $result = true;

        $LockManage = new LockManage();

        $SCREEN_NAME = self::SCREEN_NAME;
        $tableNameList = config("const.lockList.$SCREEN_NAME");
        if(!$LockManage->isOwnLocks($SCREEN_NAME, $tableNameList, $keys)){
            // throw new \Exception(config('message.error.getlock'));
            $result = false;
        }
        return $result;
    }

    /**
     * ロック解除する
     * @param $keys
     * @return true：ロックを解除で来た
     */
    private function unLock(...$keys){
        $result = true;

        $LockManage = new LockManage();

        $SCREEN_NAME = self::SCREEN_NAME;
        if(!$LockManage->deleteLockInfo($SCREEN_NAME, $keys, Auth::user()->id)){
            $result = false;
        }
        return $result;
    }

}