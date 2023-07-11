<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Illuminate\Http\Request;
use Session;
use Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Bank;
use App\Models\BillPayable;
use App\Models\Customer;
use App\Models\Department;
use App\Models\General;
use App\Models\LockManage;
use App\Models\Matter;
use App\Models\Notice;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Payment;
use App\Models\ProductCustomerPrice;
use App\Models\PurchaseLineItem;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\RequestPayment;
use App\Models\Requests;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\Supplier;
use Auth;
use DB;
use Storage;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PaymentListController extends Controller
{
    const SCREEN_NAME = 'payment-list';

    const REQUEST_TEXT = '支払予定申請がされました。承認処理を実施してください。';
    const REQUEST_CANCEL_TEXT = '支払予定申請が取消されました。確認してください。';
    const REQUEST_APPROVAL_TEXT = '支払予定が承認されました。確認してください。';
    const REQUEST_REJECT_TEXT = '支払予定が否認されました。確認してください。';

    const ERROR_HAS_AUTH = '承認権限がありません。';
    const ERROR_APPLYING_SALES = '仕入金額を売上情報に更新できないため承認処理を中断しました。再度実行してください。';

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
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'));

        // 承認権限
        // $authList = [];
        $authList = collect([
            'approval_staff_1' => Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_1')),
            'approval_staff_2' => Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_2')),
            'approval_staff_3' => Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_3')),
        ]);
        // $isApprovalStaff = Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_1'));

        $Supplier = new Supplier();
        $General = new General();
        $Department = new Department();
        $Matter = new Matter();
        $Customer = new Customer();
        $Bank = new Bank();

        // 仕入先リスト取得
        $kbn[] = config('const.supplierMakerKbn.supplier');
        $supplierList = $Supplier->getBySupplierKbn($kbn);
        // 安全会費区分取得
        $safetyFeeList = $General->getCategoryList(config('const.general.safetyfeetype'));
        // 現金区分取得
        $cashList = $General->getCategoryList(config('const.general.cashtype'));
        // 仕入区分取得
        $purchaseTypeList = $General->getListByCategoryCode(config('const.general.purchasetype'));
        // 手形種別
        $billsType = $General->getListByCategoryCode(config('const.general.billstype'));
        // 得意先リスト取得
        $customerList = $Customer->getComboList();
        // 案件リスト取得
        $matterList = $Matter->getRebateComboList();
        // 部門リスト取得
        $departmentList = $Department->getComboList();
        // 銀行リスト取得
        $bankList = $Bank->getBankList();


        return view('Payment.payment-list')
                ->with('isEditable', $isEditable)
                ->with('supplierList', $supplierList)
                ->with('safetyFeeList', $safetyFeeList)
                ->with('cashList', $cashList)
                ->with('purchaseTypeList', $purchaseTypeList)
                ->with('customerList', $customerList)
                ->with('matterList', $matterList)
                ->with('departmentList', $departmentList)
                ->with('billsType', $billsType)
                ->with('bankList', $bankList)
                ->with('authList', $authList)
                ;
    }
    
    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        $rtnData = [];
        // リクエストから検索条件取得
        $params = $request->request->all();
        $this->isValid($request);
        $Payment = new Payment();
        $BillPayable = new BillPayable();
        $Requests = new Requests();
        $Customer = new Customer();
        $PurchaseLineItem = new PurchaseLineItem();
        try {
            $list = $Payment->getPaymentList($params);

            foreach($list as $key => $row) {
                $amount = 0;
                $csRate = 0;
                $chkRate = 0;
                $bRate = 0;
                $trRate = 0;
                
                // 支払条件
                $General = new General();
                $paySightList = $General->getListByCategoryCode(config('const.general.paysight'));
                $ps = $paySightList->where('value_code', $list[$key]['payment_sight'])->first();
                $list[$key]['payment_condition'] = $list[$key]['closing_day']. '／'. $ps['value_text_1'];
                if ($list[$key]['rebate'] == 0 || $list[$key]['status'] == config('const.paymentStatus.val.unsettled')) {
                    // リベート内訳
                    $list[$key]['rebate'] = (int)$list[$key]['rebate_detail']; 
                }                
                $cDate = Carbon::parse($list[$key]['purchase_closing_day'])->addDays((int)$list[$key]['bill_deadline'])->format('Y/m/d');
                $list[$key]['calc_date'] = $cDate;


                $list[$key]['has_bills_data'] = config('const.flg.off');
                $billData = $BillPayable->getPaymentListInfoByPaymentId($list[$key]['id']);
                if (count($billData) > 0) {
                    // 手形情報
                    $list[$key]['has_bills_data'] = config('const.flg.on');

                    // 手形期日　昇順1件目をセット
                    $list[$key]['bill_deadline'] = $billData[0]['bill_of_exchange_due'];
                    $list[$key]['bank_name'] = $billData[0]['bank_name'];
                    
                    $typeText = '';
                    $endorseedText = '';
                    $cnt = 0;
                    $endorseedCnt = 0;
                    
                    $uniqueType = collect($billData)->unique('type');
                    $billsType = $General->getListByCategoryCode(config('const.general.billstype'));
                    foreach($uniqueType as $uKey => $uRow) {
                        // 種類　","区切りで表示
                        $tData = collect($billsType)->where('value_code', $uniqueType[$uKey]['type'])->first();

                        if (!empty($typeText)) {
                            $typeText .= ',';
                        }
                        $typeText .= $tData['value_text_1'];
                    }
                    foreach($billData as $bKey => $bRow) {
                        // 廻し
                        if ($billData[$bKey]['endorseed_bill'] == config('const.flg.on')) {
                            $endorseedCnt++;
                        }
                        $cnt++;
                    }
                    $endorseedText = $cnt .'枚(' .$endorseedCnt.'枚)';

                    $list[$key]['type'] = $typeText;
                    $list[$key]['endorseed_bill'] = $endorseedText;
                } else {
                    $list[$key]['bill_deadline'] = '';
                    $list[$key]['bank_name'] = '';
                    $list[$key]['type'] = '';
                    $list[$key]['endorseed_bill'] = '';
                }

                $list[$key]['download_status'] = '';

                $statusKey = $list[$key]['status'];
                $list[$key]['status_text'] = config('const.paymentStatus.list.'. $statusKey);
                if ($statusKey == config('const.paymentStatus.val.unsettled')) {
                    $offsetCustomerList = $Customer->getOffsetSupplier($list[$key]['supplier_id']);
                    $sumReceivable = 0;
                    foreach ($offsetCustomerList as $customer) {
                        // 売掛残
                        $receivableData = $Requests->getCustomerTotalSales($customer['id']);
                        $sumReceivable += $receivableData;
                    }
                    $list[$key]['receivable'] = $sumReceivable;
                }
                if ($list[$key]['requested_amount'] == 0) {   
                    // 確定処理が行われていない場合、金額割合を計算
                    $list[$key]['requested_amount'] = (int)$list[$key]['fix_cost_total'];
                    $amount = $list[$key]['requested_amount'];
                    
                    // 安全会費割合
                    $sfRate = (int)$list[$key]['safety_org_cost'];
                    // 受取リベート割合
                    $rebRate = (int)$list[$key]['receive_rebate'];
                    // 現金割合
                    $csRate = (int)$list[$key]['cash_rate'];
                    // 小切手割合
                    $chkRate = (int)$list[$key]['check_rate'];
                    // 振込割合
                    $trRate = (int)$list[$key]['transfer_rate'];
                    // 手形割合
                    $bRate = (int)$list[$key]['bill_rate'];
                    
                    // 安全会費、リベートを計算
                    // 安全会費計算
                    $list[$key]['safety_fee'] = (int)round($amount * ($sfRate / 100), 0);
                    // 支払時リベート計算
                    $list[$key]['paid_rebate'] = (int)round($amount * ($rebRate / 100), 0);

                    // 安全会費、リベートを引いた請求金額から計算
                    $amount = $amount - ($list[$key]['safety_fee'] + $list[$key]['paid_rebate']);
                    // 現金計算
                    $list[$key]['cash'] = (int)round($amount * ($csRate / 100), 0);
                    // 小切手計算
                    $list[$key]['check'] = (int)round($amount * ($chkRate / 100), 0);
                    // 振込計算
                    $list[$key]['transfer'] = (int)round($amount * ($trRate / 100), 0);
                    // 手形金額計算
                    if ($list[$key]['bills'] == 0) {
                        $list[$key]['bills'] = (int)round($amount * ($bRate / 100), 0);
                    } else {
                        $list[$key]['bills'] = (int)$list[$key]['bills'];
                    }

                    // 売掛金取得
                    $receivable = $Payment->getReceivableBySupplierId($list[$key]['supplier_id']);
                    if (count($receivable) > 0) {
                        $list[$key]['receivable'] = (int)$receivable[0]['receivable'];      
                    }              
                } else {
                    if ($list[$key]['status'] == config('const.paymentStatus.val.unsettled')) {
                        // 確定→解除していた場合、請求金額を再計算
                        $list[$key]['requested_amount'] = (int)$list[$key]['fix_cost_total'];
                    } else {
                        $list[$key]['requested_amount'] = (int)$list[$key]['requested_amount'];
                    }
                    
                    // 安全会費計算
                    $list[$key]['safety_fee'] = (int)$list[$key]['safety_fee'];
                    // 支払時リベート計算
                    $list[$key]['paid_rebate'] = (int)$list[$key]['paid_rebate'];
                    // 現金計算
                    $list[$key]['cash'] = (int)$list[$key]['cash'];
                    // 小切手計算
                    $list[$key]['check'] = (int)$list[$key]['check'];
                    // 振込計算
                    $list[$key]['transfer'] = (int)$list[$key]['transfer'];
                    // 手形金額計算
                    $list[$key]['bills'] = (int)$list[$key]['bills'];
                    $list[$key]['receivable'] = (int)$list[$key]['receivable'];

                    $list[$key]['fee'] = (int)$list[$key]['fee'];
                    $list[$key]['issuance_fee'] = (int)$list[$key]['issuance_fee'];
                    $list[$key]['rebate'] = (int)$list[$key]['rebate'];
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }    

    /**
     * リベート内訳データ取得
     *
     * @param Request $request
     * @return void
     */
    public function searchRebateData(Request $request) 
    {
        $rtnData = [];
        // リクエストから検索条件取得
        $params = $request->request->all();
        $PurchaseLineItem = new PurchaseLineItem();
        try {
            $list = $PurchaseLineItem->getRebateListByPaymentId($params['id']);

            $sumAmount = 0;
            $difference = 0;

            foreach($list as $key => $row) {
                $list[$key]['cost_total'] = $list[$key]['cost_unit_price'] * $list[$key]['quantity'];
                $sumAmount += $list[$key]['cost_total'];
            }

            $rtnData['header'] = [];
            $rtnData['header']['payment_id'] = $list[0]['payment_id'];
            $rtnData['header']['supplier_id'] = $list[0]['supplier_id'];
            $rtnData['header']['supplier_name'] = $list[0]['supplier_name'];
            $rtnData['header']['planned_payment_date'] = $list[0]['planned_payment_date'];
            $rtnData['header']['sum_amount'] = $sumAmount;

            $rtnData['list'] = $list;

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($rtnData);
    }

     /**
     * 銀行データから支店データ取得
     *
     * @param Request $request
     * @return void
     */
    public function searchBranch(Request $request) 
    {
        // リクエストから検索条件取得
        $params = $request->request->all();
        $Bank = new Bank();
        try {
            $list = $Bank->getBranchByBankCode($params['bank_code']);

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }

    /**
     * 手形情報データ取得
     *
     * @param Request $request
     * @return void
     */
    public function searchBillsData(Request $request) 
    {
        // リクエストから検索条件取得
        $rtnData = [];
        $params = $request->request->all();
        $PurchaseLineItem = new PurchaseLineItem();
        $Bank = new Bank();
        try {
            $list = $PurchaseLineItem->getBillsListByPaymentId($params['id']);

            $rtnData['list'] = $list;

            $bankCodeList = [];
            foreach($list as $key => $row) {
                if (!empty($list[$key]['bank_code'])) {
                    $bankCodeList[] = $list[$key]['bank_code'];
                }
            }

            $branchList = $Bank->getBranchListByBankCode($bankCodeList);

            $rtnData['branchList'] = $branchList;

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($rtnData);
    }


    /**
     * リベート内訳 保存
     *
     * @param [type] $params
     * @return void
     */
    public function saveRebate(Request $request) 
    {
        $resultSts = false;

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'))) {
            $hasEditAuth = true;
        }
        // リクエストデータ取得
        $params = $request->request->all();
        $paymentData = json_decode($params['paymentData'], true);
        $rebateList = json_decode($params['rebateList'], true);
        
        $Payment = new Payment();
        $PurchaseLineItem = new PurchaseLineItem();
        $Customer = new Customer();
        $Matter = new Matter();
        $Department = new Department();
        $Supplier = new Supplier();
        DB::beginTransaction();
        try{
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;

            foreach($rebateList as $key => $row) {
                $newFlg = false;

                if (!empty($rebateList[$key]['discount_reason']) || !empty($rebateList[$key]['quantity'])) {
                    if(empty($rebateList[$key]['id'])) {
                        $newFlg = true;
                    }

                    if($newFlg) {
                        // 新規
                        $customerData = $Customer->getById($rebateList[$key]['customer_id']);
                        $matterData = $Matter->getById($rebateList[$key]['matter_id']);
                        $departmentData = $Department->getById($rebateList[$key]['charge_department_id']);

                        $rebateList[$key]['customer_name'] = $customerData['customer_name'];
                        $rebateList[$key]['matter_name'] = $matterData['matter_name'];
                        $rebateList[$key]['charge_department_name'] = $departmentData['department_name'];
                        $rebateList[$key]['product_id'] = 0;

                        $pType = $rebateList[$key]['purchase_type'];
                        // 商品コード
                        switch($pType) {
                            case config('const.purchaseType.val.outside_product'):
                                // 商品外値引
                                $rebateList[$key]['product_code'] = 'NEBIKI';
                                $rebateList[$key]['product_name'] = '商品外値引';
                                break;
                            case config('const.purchaseType.val.rebate'):
                                // リベート
                                $rebateList[$key]['product_code'] = 'REBATE';
                                $rebateList[$key]['product_name'] = 'リベート';
                                break;
                            case config('const.purchaseType.val.sponsor'):
                                // SR協賛金
                                $rebateList[$key]['product_code'] = 'KYOUSANKIN';
                                $rebateList[$key]['product_name'] = 'SR協賛金';
                                break;
                            case config('const.purchaseType.val.warranty'):
                                // 差入保証金
                                $rebateList[$key]['product_code'] = 'HOSHOUKIN';
                                $rebateList[$key]['product_name'] = '差入保証金';
                                break;
                            case config('const.purchaseType.val.income'):
                                // 雑収入
                                $rebateList[$key]['product_code'] = 'ZATUSHUNYU';
                                $rebateList[$key]['product_name'] = '雑収入';
                                break;
                            case config('const.purchaseType.val.request'):
                                // 仕入外請求
                                $rebateList[$key]['product_code'] = 'SHIREGAI';
                                $rebateList[$key]['product_name'] = '仕入外請求';
                                break;
                        }

                        $rebateList[$key]['order_id'] = 0;
                        $rebateList[$key]['order_no'] = '';
                        $rebateList[$key]['order_detail_id'] = 0;
                        $rebateList[$key]['arrival_id'] = 0;
                        $rebateList[$key]['return_id'] = 0;
                        if (empty($rebateList[$key]['planned_payment_date'])) {
                            $rebateList[$key]['arrival_date'] = null;
                        }else {
                            $rebateList[$key]['arrival_date'] = $rebateList[$key]['planned_payment_date'];
                        }
                        $rebateList[$key]['model'] = '';
                        $rebateList[$key]['maker_id'] = null;
                        $rebateList[$key]['maker_name'] = null;
                        $rebateList[$key]['min_quantity'] = 1.00;
                        $rebateList[$key]['stock_quantity'] = $rebateList[$key]['quantity'];
                        $rebateList[$key]['cost_kbn'] = 0;
                        $rebateList[$key]['fix_cost_unit_price'] = $rebateList[$key]['cost_unit_price'];
                        $rebateList[$key]['cost_makeup_rate'] = 0;
                        $rebateList[$key]['fix_cost_makeup_rate'] = 0.00;
                        $rebateList[$key]['vendors_request_no'] = '';
                        $rebateList[$key]['fix_cost_total'] = $rebateList[$key]['cost_total'];
                        $rebateList[$key]['confirmed_staff_id'] = Auth::user()->id;
                        $rebateList[$key]['fixed_date'] = Carbon::now();

                        $saveResult = $PurchaseLineItem->add($rebateList[$key]);

                    } else {
                        if ($rebateList[$key]['del_flg'] == config('const.flg.on')) {
                            // 削除
                            $saveResult = $PurchaseLineItem->deleteById($rebateList[$key]['id']);
                        } else {
                            // 更新
                            $customerData = $Customer->getById($rebateList[$key]['customer_id']);
                            $matterData = $Matter->getById($rebateList[$key]['matter_id']);
                            $departmentData = $Department->getById($rebateList[$key]['charge_department_id']);

                            $rebateList[$key]['customer_name'] = $customerData['customer_name'];
                            $rebateList[$key]['matter_name'] = $matterData['matter_name'];
                            $rebateList[$key]['charge_department_name'] = $departmentData['department_name'];

                            // 商品コード
                            $pType = $rebateList[$key]['purchase_type'];
                            switch($pType) {
                                case config('const.purchaseType.val.outside_product'):
                                    // 商品外値引
                                    $rebateList[$key]['product_code'] = 'NEBIKI';
                                    $rebateList[$key]['product_name'] = '商品外値引';
                                    break;
                                case config('const.purchaseType.val.rebate'):
                                    // リベート
                                    $rebateList[$key]['product_code'] = 'REBATE';
                                    $rebateList[$key]['product_name'] = 'リベート';
                                    break;
                                case config('const.purchaseType.val.sponsor'):
                                    // SR協賛金
                                    $rebateList[$key]['product_code'] = 'KYOUSANKIN';
                                    $rebateList[$key]['product_name'] = 'SR協賛金';
                                    break;
                                case config('const.purchaseType.val.warranty'):
                                    // 差入保証金
                                    $rebateList[$key]['product_code'] = 'HOSHOUKIN';
                                    $rebateList[$key]['product_name'] = '差入保証金';
                                    break;
                                case config('const.purchaseType.val.income'):
                                    // 雑収入
                                    $rebateList[$key]['product_code'] = 'ZATUSHUNYU';
                                    $rebateList[$key]['product_name'] = '雑収入';
                                    break;
                                case config('const.purchaseType.val.request'):
                                    // 仕入外請求
                                    $rebateList[$key]['product_code'] = 'SHIREGAI';
                                    $rebateList[$key]['product_name'] = '仕入外請求';
                                    break;
                            }
                            $rebateList[$key]['fix_cost_unit_price'] = $rebateList[$key]['cost_unit_price'];
                            $rebateList[$key]['cost_makeup_rate'] = 0;
                            $rebateList[$key]['fix_cost_makeup_rate'] = 0.00;
                            $rebateList[$key]['fix_cost_total'] = $rebateList[$key]['cost_total'];

                            $saveResult = $PurchaseLineItem->updateById($rebateList[$key]);
                        }
                    }
                }
            }

            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 承認可能なリスト取得
     *
     * @param Request $request
     * @return void
     */
    public function searchRequest(Request $request) 
    {
        // リクエストから検索条件取得
        $rtnData = [];
        $params = $request->request->all();
        $RequestPayment = new RequestPayment();
        try {
            $sDate = $params['payment_date']. '/01';
            // $paymentDate = str_replace('/', '', $params['payment_date']);
            $eDate = Carbon::parse($sDate)->lastOfMonth()->format('Y/m/d');
            
            $list = $RequestPayment->getRequestList(Auth::user()->id, $sDate, $eDate);

            foreach ($list as $key => $row) {
                $statusKey = $list[$key]['status'];
                $list[$key]['status_text'] = config('const.paymentRequestStatus.list.'. $statusKey);

                if ($statusKey == config('const.paymentRequestStatus.val.not_approval')) {
                    $list[$key]['status_class'] = 'not-approval';
                }
                if ($statusKey == config('const.paymentRequestStatus.val.approval1')) {
                    $list[$key]['status_class'] = 'not-approval';
                }
                if ($statusKey == config('const.paymentRequestStatus.val.approval2')) {
                    $list[$key]['status_class'] = 'not-approval';
                }
                if ($statusKey == config('const.paymentRequestStatus.val.approval3')) {
                    $list[$key]['status_class'] = 'approvaled';
                }
                if ($statusKey == config('const.paymentRequestStatus.val.sendBack')) {
                    $list[$key]['status_class'] = 'send-back';
                }
            }

            $rtnData['payment_date'] = Carbon::parse($sDate)->format('Y年m月');
            $rtnData['list'] = $list;

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($rtnData);
    }


     /**
     * 手形情報 保存
     *
     * @param [type] $params
     * @return void
     */
    public function saveBills(Request $request) 
    {
        $resultSts = false;

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'))) {
            $hasEditAuth = true;
        }

        // リクエストデータ取得
        $params = $request->request->all();
        $paymentData = json_decode($params['paymentData'], true);
        $billsList = json_decode($params['billsList'], true);
        
        $Payment = new Payment();
        $PurchaseLineItem = new PurchaseLineItem();
        $BillPayable = new BillPayable();
        $Customer = new Customer();
        $Matter = new Matter();
        $Department = new Department();
        $Supplier = new Supplier();
        DB::beginTransaction();
        try{
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;
            $sumBills = 0;
            foreach($billsList as $key => $row) {
                $newFlg = false;
                if (!empty($billsList[$key]['bills'])) {
                    if(empty($billsList[$key]['id'])) {
                        $newFlg = true;
                    }

                    if ($newFlg) {
                        // 新規
                        $saveResult = $BillPayable->add($billsList[$key]);
                    } else {

                        if ($billsList[$key]['del_flg'] == config('const.flg.on')) {
                            // 削除
                            $saveResult = $BillPayable->deleteById($billsList[$key]['id']);
                        } else {
                            // 更新
                            $saveResult = $BillPayable->updateById($billsList[$key]);
                        }
                    }
                    if ($billsList[$key]['del_flg'] == config('const.flg.off')) {
                        $sumBills += (int)$billsList[$key]['bills'];
                    }
                }
            }

            $updateData = [];
            $updateData['id'] = $paymentData['id'];
            $updateData['bills'] = $sumBills;
            $saveResult = $Payment->updateById($updateData);
            
            
            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json(['result' => $resultSts, 'billsTotal' => $sumBills]);
    }

    /**
     * 保存
     *
     * @param [type] $params
     * @return void
     */
    public function save(Request $request) 
    {
        $resultSts = ['result' => false, 'message' => ''];
        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'))) {
            $hasEditAuth = true;
        }

        // リクエストデータ取得
        $params = $request->request->all();
        $paymentData = json_decode($params['paymentData'], true);
        
        $Payment = new Payment();
        $PurchaseLineItem = new PurchaseLineItem();
        DB::beginTransaction();
        try{
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;
            
            // 仕入明細の最終更新日チェック
            $updateAt = $paymentData['update_at'];
            $data = $PurchaseLineItem->getLastUpdateByPaymentId($paymentData['id']);

            if ($data['update_at'] == $updateAt) {
                $updateData = [];
                $updateData['id'] = $paymentData['id'];
                $updateData['requested_amount'] = $paymentData['requested_amount'];
                $updateData['rebate'] = $paymentData['rebate'];
                $updateData['safety_fee'] = $paymentData['safety_fee'];
                $updateData['safety_fee_type'] = $paymentData['safety_fee_type'];
                $updateData['offset'] = $paymentData['offset'];
                $updateData['receivable'] = $paymentData['receivable'];
                $updateData['paid_rebate'] = $paymentData['paid_rebate'];
                $updateData['cash'] = $paymentData['cash'];
                $updateData['cash_type'] = $paymentData['cash_type'];
                $updateData['check'] = $paymentData['check'];
                $updateData['transfer'] = $paymentData['transfer'];
                $updateData['fee'] = $paymentData['fee'];
                $updateData['bills'] = $paymentData['bills'];
                $updateData['issuance_fee'] = $paymentData['issuance_fee'];
                $updateData['planned_payment_date'] = $paymentData['payment_date'];
                $updateData['status'] = config('const.paymentStatus.val.confirm');
                $updateData['confirmed_staff_id'] = Auth::user()->id;
                $updateData['fixed_date'] = Carbon::now();
                $saveResult = $Payment->updateById($updateData);
            } else {
                $resultSts['message'] = config('message.error.payment_list.updated_purchase_detail');
            }
            
            if ($saveResult) {
                DB::commit();
                $resultSts['result'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts['result'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

     /**
     * 申請
     *
     * @param [type] $params
     * @return void
     */
    public function paymentRequest(Request $request) 
    {
        $resultSts = false;

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'))) {
            $hasEditAuth = true;
        }

        // リクエストデータ取得
        $params = $request->request->all();
        $requestList = json_decode($params['requestList'], true);
        
        $RequestPayment = new RequestPayment();
        $Payment = new Payment();
        DB::beginTransaction();
        try{
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }

            $saveResult = false;
            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }

            // 画像の有無
            $hasImage = $request->hasFile('uploadFile');
            if($hasImage){
                $uploadPath = config('const.uploadPath.paymentRequest');
                // ファイル名取得
                $fileName = $request->file('uploadFile')->getClientOriginalName();
                $uploadFile['file_name'] = $fileName;
            }else {
                $fileName = '';
                $uploadFile['file_name'] = '';
            }

            $sum_amount = 0;
            $paymentIds = [];
            foreach($requestList as $key => $row) {
                $sum_amount += $requestList[$key]['requested_amount'];
                $paymentIds[] = $requestList[$key]['id'];
            }

            // 申請レコード作成
            $requestData = [];            
            $requestData['apply_date'] = Carbon::now();
            $requestData['apply_staff_id'] = Auth::user()->id;
            if ($newFlg) {
                // 新規申請
                $requestData['approval_date1'] = null;
                $requestData['approval_staff_id1'] = null;
                $requestData['approval_date2'] = null;
                $requestData['approval_staff_id2'] = null;
                $requestData['approval_date3'] = null;
                $requestData['approval_staff_id3'] = null;
                $requestData['planned_payment_date'] = $params['payment_date'];
                $requestData['total_amount'] = $sum_amount;
                $requestData['apply_comment'] = $params['apply_comment'];
                $requestData['attachments_file'] = $fileName;
                $requestData['approval_comment'] = null;
                $requestData['status'] = config('const.paymentRequestStatus.val.not_approval');
                
                $requestId = $RequestPayment->add($requestData);

                $saveResult = true;
                
                if($hasImage){
                    // アップロード
                    $request->file('uploadFile')->storeAs($uploadPath.$requestId.'/', $fileName);
                }

                // 支払予定レコード更新
                foreach($requestList as $key => $row) {
                    $updatePaymentData = [];

                    $updatePaymentData['id'] = $requestList[$key]['id']; 
                    $updatePaymentData['status'] = config('const.paymentStatus.val.applying'); 
                    $updatePaymentData['request_payment_id'] = $requestId; 
                    
                    $result = $Payment->updateById($updatePaymentData);
                }
            } else {
                // 申請更新
                // 同商品のアップロードファイル削除
                $files = Storage::files($uploadPath);
                if ($hasImage){
                    foreach($files as $file) {
                        Storage::delete($uploadPath.$file);
                    }
                }
                // アップロード
                $request->file('uploadFile')->storeAs($uploadPath, $fileName);
                
                $requestData['id'] = $params['id'];
                $requestData['approval_date1'] = null;
                $requestData['approval_staff_id1'] = null;
                $requestData['approval_date2'] = null;
                $requestData['approval_staff_id2'] = null;
                $requestData['approval_date3'] = null;
                $requestData['approval_staff_id3'] = null;
                $requestData['planned_payment_date'] = $params['payment_date'];
                $requestData['total_amount'] = $sum_amount;
                $requestData['apply_comment'] = $params['apply_comment'];
                $requestData['attachments_file'] = $fileName;
                $requestData['approval_comment'] = null;
                // $requestData['status'] = config('const.paymentRequestStatus.val.not_approval');

                $saveResult = $RequestPayment->updateById($requestData);
            }

            // 第一承認者を取得
            $Authority = new Authority();
            $staffList = $Authority->getAuthorityStaff(config('const.auth.purchase.approval_staff_1'));

            // 通知作成
            $Notice = new Notice();
            foreach($staffList as $key => $row) {
                $noticeData = [];
                $noticeData['notice_flg'] = config('const.flg.off');
                $noticeData['staff_id'] = $staffList[$key]['staff_id'];
                $noticeData['content'] = self::REQUEST_TEXT;
                $noticeData['redirect_url'] = $params['redirect_url'];

                $noticeResult = $Notice->add($noticeData);
            }

            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 申請解除
     *
     * @param [type] $params
     * @return void
     */
    public function cancelRequest(Request $request) 
    {
        $resultSts = false;
        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'))) {
            $hasEditAuth = true;
        }

        // リクエストデータ取得
        $params = $request->request->all();
        $requestList = json_decode($params['requestList'], true);
        
        $Payment = new Payment();
        $RequestPayment = new RequestPayment();
        DB::beginTransaction();
        try{
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;

            $Ids = collect($requestList)->unique('request_payment_id')->pluck('request_payment_id');
            $paymentList = $Payment->getPaymentByRequestPaymentId($Ids);

            // 支払予定テーブル更新
            foreach ($paymentList as $key => $row) {
                $updateData = [];
                $updateData['id'] = $paymentList[$key]['id'];
                $updateData['status'] = config('const.paymentStatus.val.confirm');
                $updateData['request_payment_id'] = null;

                $saveResult = $Payment->updateById($updateData);
            }

            // 申請削除
            $requestPaymentData = $RequestPayment->getByIdList($Ids);
            foreach ($Ids as $key => $id) {
                $result = $RequestPayment->deleteById($id);
            }

            // 通知作成
            $Notice = new Notice();
            foreach($requestPaymentData as $key => $row) {
                $noticeData = [];
                $noticeData['notice_flg'] = config('const.flg.off');
                $noticeData['content'] = self::REQUEST_CANCEL_TEXT;
                $noticeData['redirect_url'] = $params['redirect_url'];

                // 承認者1へ通知
                if (!empty($requestPaymentData[$key]['approval_staff_id1'])) {
                    $noticeData['staff_id'] = $requestPaymentData[$key]['approval_staff_id1'];
                    $result = $Notice->add($noticeData);
                }
                // 承認者2へ通知
                if (!empty($requestPaymentData[$key]['approval_staff_id2'])) {
                    $noticeData['staff_id'] = $requestPaymentData[$key]['approval_staff_id2'];
                    $result = $Notice->add($noticeData);
                }
                // 承認者3へ通知
                if (!empty($requestPaymentData[$key]['approval_staff_id3'])) {
                    $noticeData['staff_id'] = $requestPaymentData[$key]['approval_staff_id3'];
                    $result = $Notice->add($noticeData);
                }
            }
            
            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 支払締め
     *
     * @param [type] $params
     * @return void
     */
    public function closing(Request $request) 
    {
        $resultSts = false;
        // // 編集権限チェック
        // $hasEditAuth = false;
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'))) {
        //     $hasEditAuth = true;
        // }

        // リクエストデータ取得
        $params = $request->request->all();
        $requestList = json_decode($params['requestList'], true);
        
        $Payment = new Payment();
        $RequestPayment = new RequestPayment();
        $Notice = new Notice();
        DB::beginTransaction();
        try{
            // if(!$hasEditAuth){
            //     // 編集権限なし
            //     throw new \Exception();
            // }
            $saveResult = false;

            // 支払予定テーブル更新
            foreach ($requestList as $key => $row) {
                $updateData = [];
                $updateData['id'] = $requestList[$key]['id'];
                $updateData['status'] = config('const.paymentStatus.val.closing');

                $saveResult = $Payment->updateById($updateData);

                $requestPaymentData = $RequestPayment->getById($requestList[$key]['request_payment_id']);
                // 通知作成
                $noticeMessage = '';
                $noticeMessage .= $requestList[$key]['payment_date']. ':'. $requestList[$key]['supplier_name']. 'の支払予定を締めました。ご確認お願いいたします。';
                $noticeData = [];
                $noticeData['notice_flg'] = config('const.flg.off');
                $noticeData['content'] = $noticeMessage;
                $noticeData['redirect_url'] = $params['redirect_url'];

                // 承認者1へ通知
                if (!empty($requestPaymentData['approval_staff_id1'])) {
                    $noticeData['staff_id'] = $requestPaymentData['approval_staff_id1'];
                    $result = $Notice->add($noticeData);
                }
                // 承認者2へ通知
                if (!empty($requestPaymentData['approval_staff_id2'])) {
                    $noticeData['staff_id'] = $requestPaymentData['approval_staff_id2'];
                    $result = $Notice->add($noticeData);
                }
                // 承認者3へ通知
                if (!empty($requestPaymentData['approval_staff_id3'])) {
                    $noticeData['staff_id'] = $requestPaymentData['approval_staff_id3'];
                    $result = $Notice->add($noticeData);
                }
            }
            
            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 承認
     *
     * @param [type] $params
     * @return void
     */
    public function approval(Request $request) 
    {
        $resultSts = [];
        $resultSts['result'] = false;
        $resultSts['message'] = '';

        // リクエストデータ取得
        $params = $request->request->all();
        $approvalList = json_decode($params['approvalList'], true);
        $denialList = json_decode($params['denialList'], true);
        
        $Payment = new Payment();
        $RequestPayment = new RequestPayment();
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $Quote = new Quote();
        $QuoteDetail = new QuoteDetail();
        $Requests = new Requests();
        $PurchaseLineItem = new PurchaseLineItem();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $SystemUtil = new SystemUtil();
        $Notice = new Notice();
        $ProductCustomerPrice = new ProductCustomerPrice();
        $Customer = new Customer();
        $Authority = new Authority();
        $Matter = new Matter();
        $LockManage = new LockManage();
        
        // ロック取得
        $customerIdList = [];
        foreach ($approvalList as $key => $row) {
            // 最終承認の場合、更新対象に紐付く得意先のロック取得
            $isOwnLock = false;

            $requestData = $RequestPayment->getById(($approvalList[$key]['id']));
            if ($requestData['status'] == config('const.paymentRequestStatus.val.approval2')) {
                if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_3'))) {
                    $paymentIds = [];
                    $paymentDataList = $Payment->getByRequestPaymentId(array($approvalList[$key]['id']));
                    foreach ($paymentDataList as $pKey => $pRow) {
                        $paymentIds[] = $paymentDataList[$pKey]['id'];
                    }
                    $purchaseList = $PurchaseLineItem->getParentDataByPaymentIdList($paymentIds);
                    $idList = [];
                    foreach ($purchaseList as $pKey => $pRow) {
                        if (!empty($purchaseList[$pKey]['quote_detail_id'])) {
                            // 見積明細から売上明細取得
                            $salesData = $Sales->getSalesDataByQuoteDetailId($purchaseList[$pKey]['quote_detail_id'], $purchaseList[$pKey]['cost_kbn']);
                            $matterIds = [];
                            foreach($salesData as $sKey => $sRow) {
                                if (!in_array($salesData[$sKey]['matter_id'], $matterIds, true)) {
                                    $matterIds[] = $salesData[$sKey]['matter_id'];
                                }
                            }                                
                            $customerIds = $Matter->getCustomerIdByIdList($matterIds);
                            foreach($customerIds as $cKey => $sRow) {
                                if (!in_array($customerIds[$cKey]['customer_id'], $idList, true)) {
                                    $idList[] = $customerIds[$cKey]['customer_id'];
                                }
                            }
                        }   
                    }
                    // ロック取得
                    // 画面名から対象テーブルを取得
                    $tableName = config('const.lockList.'.self::SCREEN_NAME)[0];
                    $lockCnt = 0;
                    DB::beginTransaction();
                    foreach ($idList as $i => $id) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $id);
                        if (!$isLocked) {
                            // ロックされていない場合はロック取得
                            $lockDt = Carbon::now();
                            $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $id);
                        }
                        // ロックデータ取得
                        $lockData = $LockManage->getLockData($tableName, $id);
                        if (!is_null($lockData) 
                            && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                            && $lockData->key_id === $id && $lockData->lock_user_id === Auth::user()->id) {
                                $lockCnt++;
                        }
                    }
                    if (count($idList) === $lockCnt) {
                        $isOwnLock = config('const.flg.on');
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                    $lockDataList = $LockManage->getLockDataForList(self::SCREEN_NAME, $Customer->getTable(), $idList);

                    foreach ($idList as $customerId) {
                        array_push($customerIdList, $customerId);
                    }
                }
            }
            $approvalList[$key]['isOwnLock'] = $isOwnLock;
        }

        DB::beginTransaction();
        try{
            $saveResult = false;
            /**
             * 承認処理
             */
            foreach ($approvalList as $key => $row) {
                // 支払申請更新
                $requestUpdateData = [];
                $requestUpdateData['id'] = $approvalList[$key]['id'];
                $requestData = $RequestPayment->getById(($approvalList[$key]['id']));
                $paymentDataList = $Payment->getByRequestPaymentId(array($approvalList[$key]['id']));
                $requestUpdateData['approval_comment'] = $approvalList[$key]['approval_comment'];
                if ($requestData['status'] == config('const.paymentRequestStatus.val.not_approval')) {
                    if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_1'))) {
                        $requestUpdateData['status'] = config('const.paymentRequestStatus.val.approval1');
                        $requestUpdateData['approval_date1'] = Carbon::now();
                        $requestUpdateData['approval_staff_id1'] = Auth::user()->id;

                        // 承認者2へ通知する
                        $approvalStaff = $Authority->getAuthorityStaff(config('const.auth.purchase.approval_staff_2'));
                        foreach ($approvalStaff as $staff) {
                            $noticeData = [];
                            $noticeData['notice_flg'] = config('const.flg.off');
                            $noticeData['staff_id'] = $staff['staff_id'];
                            $noticeData['content'] = self::REQUEST_TEXT;
                            $noticeData['redirect_url'] = $params['redirect_url'];

                            $saveResult = $Notice->add($noticeData);
                        }
                    } else {
                        $resultSts['message'] = self::ERROR_HAS_AUTH;
                        throw new \Exception();
                    }
                }
                if ($requestData['status'] == config('const.paymentRequestStatus.val.approval1')) {
                    if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_2'))) {
                        $requestUpdateData['status'] = config('const.paymentRequestStatus.val.approval2');
                        $requestUpdateData['approval_date2'] = Carbon::now();
                        $requestUpdateData['approval_staff_id2'] = Auth::user()->id;

                        // 承認者3へ通知する
                        $approvalStaff = $Authority->getAuthorityStaff(config('const.auth.purchase.approval_staff_3'));
                        foreach ($approvalStaff as $staff) {
                            $noticeData = [];
                            $noticeData['notice_flg'] = config('const.flg.off');
                            $noticeData['staff_id'] = $staff['staff_id'];
                            $noticeData['content'] = self::REQUEST_TEXT;
                            $noticeData['redirect_url'] = $params['redirect_url'];

                            $saveResult = $Notice->add($noticeData);
                        }
                    } else {
                        $resultSts['message'] = self::ERROR_HAS_AUTH;
                        throw new \Exception();
                    }
                }
                if ($requestData['status'] == config('const.paymentRequestStatus.val.approval2')) {
                    if (Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.approval_staff_3'))) {
                        $requestUpdateData['status'] = config('const.paymentRequestStatus.val.approval3');
                        $requestUpdateData['approval_date3'] = Carbon::now();
                        $requestUpdateData['approval_staff_id3'] = Auth::user()->id;
                    } else {
                        $resultSts['message'] = self::ERROR_HAS_AUTH;
                        throw new \Exception();
                    }
                }
                // 支払申請更新
                if ($requestUpdateData['status'] != config('const.paymentRequestStatus.val.approval3')) {
                    $saveResult = $RequestPayment->updateById($requestUpdateData);
                }

                // 支払申請テーブルが「3:承認済」の時、売上、売上明細を更新する → ※売上更新処理は仕入明細側に移動
                // 支払申請のステータス変更
                $purchaseList = [];
                if ($requestUpdateData['status'] == config('const.paymentRequestStatus.val.approval3')) {
                    if ($approvalList[$key]['isOwnLock']) {
                        // 支払申請更新
                        $saveResult = $RequestPayment->updateById($requestUpdateData);

                        $paymentIds = [];
                        foreach ($paymentDataList as $pKey => $pRow) {
                            $paymentIds[] = $paymentDataList[$pKey]['id'];

                            $pUpdateData = [];
                            $pUpdateData['id'] = $paymentDataList[$pKey]['id'];
                            $pUpdateData['status'] = config('const.paymentStatus.val.approvaled');

                            $saveResult = $Payment->updateById($pUpdateData);
                        }
                        // $purchaseList = $PurchaseLineItem->getParentDataByPaymentIdList($paymentIds);
                        // $idList = [];
                        // foreach ($purchaseList as $pKey => $pRow) {
                        //     if (!empty($purchaseList[$pKey]['quote_detail_id'])) {
                        //         // 仕入明細の得意先IDから申請中の承認が存在しないかを確認
                        //         $hasApply = $Sales->hasApplyingSalesByCustomerId($purchaseList[$pKey]['customer_id']);
                        //         if ($hasApply) {
                        //             // 存在した場合　処理終了
                        //             $customerName = $purchaseList[$pKey]['customer_name'];
                        //             $resultSts['message'] = $customerName . ':'. self::ERROR_APPLYING_SALES;
                        //             throw new \Exception();
                        //         }

                        //         // 見積明細から売上明細取得
                        //         $salesData = $Sales->getSalesDataByQuoteDetailId($purchaseList[$pKey]['quote_detail_id'], $purchaseList[$pKey]['cost_kbn']);

                        //         foreach($salesData as $sKey => $sRow) {
                        //             if ($salesData[$sKey]['invalid_unit_price_flg'] == config('const.flg.off')) {
                        //                 // 売上.単価無効フラグ = 0
                        //                 // 売上明細取得
                        //                 $salesDetailData = $SalesDetail->getListByQuoteDetailId($purchaseList[$pKey]['quote_detail_id']);
                        //                 // 仕入金額更新
                        //                 $costTotal = 0;
                        //                 $salesTotal = 0;
                        //                 foreach ($salesDetailData as $sdKey => $sdRow) {
                        //                     $salesDetailUpdateData = [];
                        //                     $salesDetailUpdateData['id'] = $salesDetailData[$sdKey]['id'];
                        //                     $salesDetailUpdateData['cost_unit_price'] = $purchaseList[$pKey]['fix_cost_unit_price'];
                        //                     $salesDetailUpdateData['cost_total'] = $SystemUtil->calcTotal($salesDetailData[$sdKey]['sales_quantity'], $purchaseList[$pKey]['fix_cost_unit_price'], true);

                        //                     $costTotal += $salesDetailUpdateData['cost_total'];
                        //                     $salesTotal += $salesDetailData[$sdKey]['sales_total'];

                        //                     if ($salesDetailData[$sdKey]['cost_unit_price'] != $salesDetailUpdateData['cost_unit_price']) {
                        //                         $saveResult = $SalesDetail->updateById($salesDetailUpdateData['id'], $salesDetailUpdateData);
                        //                     }
                        //                 }

                        //                 // 売上データ更新
                        //                 $salesUpdateData = [];
                        //                 $salesUpdateData['id'] = $salesData[$sKey]['id'];
                        //                 $salesUpdateData['cost_unit_price'] = $purchaseList[$pKey]['fix_cost_unit_price'];
                        //                 $salesUpdateData['cost_makeup_rate'] = $SystemUtil->calcRate($salesUpdateData['cost_unit_price'], $salesData[$sKey]['regular_price']);
                        //                 $salesUpdateData['update_cost_unit_price'] = $salesData[$sKey]['cost_unit_price'];
                        //                 $salesUpdateData['update_cost_unit_price_d'] = Carbon::now();
                        //                 // 粗利額計算
                        //                 $profitPrice = $SystemUtil->calcProfitTotal($salesTotal, $costTotal);
                        //                 $salesUpdateData['profit_total'] = $profitPrice;
                        //                 // 粗利率計算
                        //                 $profitRate = $SystemUtil->calcRate($profitPrice, $salesTotal);
                        //                 $salesUpdateData['gross_profit_rate'] = $profitRate;
                                        
                        //                 if ($salesData[$sKey]['cost_unit_price'] != $salesUpdateData['cost_unit_price']) {
                        //                     $result = $Sales->updateById($salesUpdateData['id'], $salesUpdateData);
                        //                 }

                        //                 // 請求データ取得
                        //                 $requestData = $Requests->getById($salesData[$sKey]['request_id']);

                        //                 $sumSalesVolume = $SalesDetail->getCostTotalByRequestId($salesData[$sKey]['request_id'])['cost_total'];

                        //                 // 売上明細取得[仕入高計算用]
                        //                 // $calcSalesData = $SalesDetail->getCostTotalByRequestId($requestData['id']);

                        //                 // 請求更新
                        //                 $requestUpdateData = [];
                        //                 $requestUpdateData['id'] = $requestData['id'];
                        //                 $requestUpdateData['purchase_volume'] = $sumSalesVolume;

                        //                 $result = $Requests->updateById($requestUpdateData['id'], $requestUpdateData);
                        //             } else {
                        //                 // 売上.単価無効フラグ = 1
                        //                 // 見積番号と階層パスの「_」までをキーに検索し、販売額利用フラグが設定されている階層の見積明細IDを取得する。
                        //                 if(!empty($purchaseList[$pKey]['quote_no'])) {
                        //                     $quoteDetailData = $QuoteDetail->getSalesUseFlgQuoteByQuoteNo($purchaseList[$pKey]['quote_no']);
                        //                 }

                        //                 if (!empty($quoteDetailData)) {
                        //                     foreach($quoteDetailData as $qKey => $qRow) {                                            
                        //                         $updateTargetSalesData = $SalesDetail->getByQuoteDetailId($quoteDetailData[$qKey]['parent_quote_detail_id']);

                        //                         $escapeDetailId = [];
                        //                         $costUnitPrice = $quoteDetailData[$qKey]['cost_total'];
                        //                         $salesTotal = 0;
                        //                         $costTotal = 0;
                        //                         foreach($updateTargetSalesData as $i => $record) {
                        //                             $salesId = $updateTargetSalesData[$i]['sales_id'];
                        //                             $updateData = [];
                        //                             $updateData['id'] = $updateTargetSalesData[$i]['id'];
                        //                             // $updateData['quote_detail_id'] = $updateTargetSalesData['quote_detail_id'];
                        //                             $updateData['cost_unit_price'] = $quoteDetailData[$qKey]['cost_total'];
                        //                             $updateData['cost_total'] = $SystemUtil->calcTotal($updateTargetSalesData[$i]['sales_quantity'], $quoteDetailData[$qKey]['cost_total'], true);
                                                    
                        //                             $salesTotal += $updateTargetSalesData[$i]['sales_total'];
                        //                             $costTotal += $updateData['cost_total'];

                        //                             $escapeDetailId[] = $updateData['id'];
                        //                             // 売上明細の仕入金額更新
                        //                             $result = $SalesDetail->updateById($updateData['id'], $updateData);
                        //                         }
                        //                         // 売上の仕入金額更新
                        //                         $salesTargetData = $Sales->getById($salesId);

                        //                         $updateSalesData = [];
                        //                         $updateSalesData['id'] = $salesTargetData['id'];
                        //                         $updateSalesData['cost_unit_price'] = $costTotal;
                        //                         $updateSalesData['update_cost_unit_price'] = $salesTargetData['cost_unit_price'];
                        //                         $updateSalesData['update_cost_unit_price_d'] = Carbon::now();
                        //                         $updateSalesData['profit_total'] = $SystemUtil->calcProfitTotal($salesTotal, $costTotal);
                        //                         $updateSalesData['gross_profit_rate'] = $SystemUtil->calcRate($updateSalesData['profit_total'], $salesTotal); 
                                                
                        //                         $result = $Sales->updateById($updateSalesData['id'], $updateSalesData);

                        //                         $salesDetailAllData = $SalesDetail->getByQuoteDetailId($quoteDetailData[$qKey]['quote_detail_id']);
                        //                         $detailCostTotal = 0;
                        //                         foreach($salesDetailAllData as $record) {
                        //                             if (!in_array($record['id'], $escapeDetailId)) {
                        //                                 $detailCostTotal += $record['cost_total'];
                        //                             }
                        //                         }
                        //                         $volumeTotal = $costTotal + $detailCostTotal;
                                                
                        //                         $reqData = $Requests->getById($salesTargetData['request_id']);
                        //                         $sumSalesVolume = $SalesDetail->getCostTotalByRequestId($salesData[$sKey]['request_id'])['cost_total'];

                        //                         // 請求更新 仕入金額更新
                        //                         $reqUpdateData['id'] = $reqData['id'];
                        //                         $reqUpdateData['purchase_volume'] = $sumSalesVolume;
                        //                         $result = $Requests->updateById($reqUpdateData['id'], $reqUpdateData);
                        //                     }
                        //                 }
                        //             }
                        //         }
                        //     }
                        // }
                        // // 「得意先別商品単価」テーブルにデータを生成する。
                        // $payData = $Payment->getByRequestPaymentId(array($approvalList[$key]['id']));

                        // foreach ($payData as $payKey => $payRow) {
                        //     if ($payData[$payKey]['cost_kbn'] == config('const.unitPriceKbn.cutomer_normal')) {
                        //         $customerPriceData = [];
                        //         $customerPriceData['customer_id'] = $payData[$payKey]['customer_id'];
                        //         $customerPriceData['product_id'] = $payData[$payKey]['product_id'];
                        //         $customerPriceData['cost_sales_kbn'] = config('const.flg.on');
                        //         $customerPriceData['price'] = $payData[$payKey]['fix_cost_unit_price'];
                        //         $customerPriceData['quote_detail_id'] = 0;

                        //         $result = $ProductCustomerPrice->add($customerPriceData);
                        //     }
                        // }
                        // 申請者に、承認が完了したことを通知する。
                        $noticeData = [];
                        $noticeData['notice_flg'] = config('const.flg.off');
                        $noticeData['staff_id'] = $approvalList[$key]['apply_staff_id'];
                        $noticeData['content'] = self::REQUEST_APPROVAL_TEXT;
                        $noticeData['redirect_url'] = $params['redirect_url'];

                        $saveResult = $Notice->add($noticeData);
                    } else {
                        // $saveResult = true;
                        $resultSts['message'] = config('message.error.payment_list.request_approval_error');
                    }
                }
            }

            /**
             * 否認処理
             */
            foreach($denialList as $key => $row) {                
                // 「支払予定」テーブルを検索し、承認対象の情報を更新する。
                $paymentList = $Payment->getByRequestPaymentId(array($denialList[$key]['id']));
                foreach($paymentList as $pKey => $pRow) {
                    $paymentUpdateData = [];
                    $paymentUpdateData['id'] = $paymentList[$pKey]['id'];
                    $paymentUpdateData['status'] = config('const.paymentStatus.val.confirm');
                    $paymentUpdateData['request_payment_id'] = null;

                    $saveResult = $Payment->updateById($paymentUpdateData);         
                }       
                // 「支払申請」テーブルを検索し、承認対象の情報を更新する。
                $requestUpdateData = [];
                $requestUpdateData['id'] = $denialList[$key]['id'];
                $requestUpdateData['status'] = config('const.paymentRequestStatus.val.sendBack');
                $requestUpdateData['approval_comment'] = $denialList[$key]['approval_comment'];

                $saveResult = $RequestPayment->updateById($requestUpdateData);

                // 申請者に、否認したことを通知する。
                $noticeData = [];
                $noticeData['notice_flg'] = config('const.flg.off');
                $noticeData['staff_id'] = $denialList[$key]['apply_staff_id'];
                $noticeData['content'] = self::REQUEST_REJECT_TEXT;
                $noticeData['redirect_url'] = $params['redirect_url'];

                $saveResult = $Notice->add($noticeData);
            }

            if (count($approvalList) == 0 && count($denialList) == 0) {
                $saveResult = true;
            }

            if ($saveResult) {
                $LockManage = new LockManage();
                foreach ($customerIdList as $key) { 
                    $LockManage->deleteLockInfo(self::SCREEN_NAME, array($key), Auth::user()->id);
                }
                
                DB::commit();
                $resultSts['result'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts['result'] = false;
            if (empty($resultSts['message'])) {
                $resultSts['message'] = config('message.error.error');
            }
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }


    /**
     * 確定解除
     *
     * @param Request $request
     * @return void
     */
    public function cancel(Request $request) 
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $paymentData = json_decode($params['paymentData'], true);
        
        $Payment = new Payment();
        DB::beginTransaction();
        try{
            $saveResult = false;

            $updateData = [];

            $updateData['id'] = $paymentData['id'];
            $updateData['status'] = config('const.paymentStatus.val.unsettled');
            $updateData['membershipfee_flg'] = config('const.flg.off');
            $updateData['offset_flg'] = config('const.flg.off');
            $updateData['cash_flg'] = config('const.flg.off');
            $updateData['transfer_flg'] = config('const.flg.off');
            $updateData['bills_flg'] = config('const.flg.off');
            $updateData['electricitydebt_flg'] = config('const.flg.off');
            $updateData['transmittal_flg'] = config('const.flg.off');
            $updateData['accounting_flg'] = config('const.flg.off');
            $updateData['confirmed_staff_id'] = null;
            $updateData['fixed_date'] = null;
            $saveResult = $Payment->updateById($updateData);
            
            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function download(Request $request, $id, $fileName){
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.paymentRequest').'/'. $id.'/'.$fileName), $fileName, $headers);
    }

    /**
     * バリデーションチェック
     *
     * @param \App\Http\Controllers\Request $request
     * @return boolean
     */
    public function isValid(Request $request) 
    {   
        
        $this->validate($request, [
            'payment_date' => 'required',
        ]);
    }  
}