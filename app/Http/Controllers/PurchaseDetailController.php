<?php
namespace App\Http\Controllers;

use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Arrival;
use Illuminate\Http\Request;
use Session;
use Validator;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\ClassBig;
use App\Models\Construction;
use App\Models\Customer;
use App\Models\Department;
use App\Models\General;
use App\Models\LockManage;
use App\Models\Matter;
use App\Models\NumberManage;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\PurchaseLineItem;
use App\Models\Requests;
use App\Models\Returns;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Supplier;
use App\Models\Payment;
use App\Models\ProductCustomerPrice;
use App\Models\QuoteDetail;
use App\Models\RequestPayment;
use App\Models\Sales;
use App\Models\SalesDetail;
use Auth;
use DB;
use Carbon\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class PurchaseDetailController extends Controller
{
    const SCREEN_NAME = 'purchase-detail';

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
        // 捜査権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.purchase.staff'));

        // リスト取得
        $Matter = new Matter();
        $Order = new Order();
        $Staff = new Staff();
        $PurchaseLineItem = new PurchaseLineItem();
        $Department = new Department();
        $StaffDepartment = new StaffDepartment();
        $Customer = new Customer();
        $ClassBig = new ClassBig();
        $Construction = new Construction();
        $Product = new Product();
        $Supplier = new Supplier();
        $Payment = new Payment();

        $matterList = $Matter->getComboList();
        $orderList = $Order->getComboList();
        $staffList = $Staff->getComboList();
        $requestNoList = $PurchaseLineItem->getRequestNoComboList();
        $departmentList = $Department->getComboList();
        $staffDepartList = $StaffDepartment->getCurrentTermList();
        $customerList = $Customer->getComboList();
        $classBigList = $ClassBig->getComboList();
        $constList = $Construction->getComboList();
        $paymentList = $Payment->getPaymentNoList();
        // $productList = $Product->getComboList();
        // 仕入先リスト取得
        $kbn[] = config('const.supplierMakerKbn.supplier');
        $kbn[] = config('const.supplierMakerKbn.direct');
        $supplierList = $Supplier->getBySupplierKbn($kbn);
        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);

        $orderStaffList = $Staff->getComboList();;
        $confirmStaffList = $Staff->getComboList();;

        $General = new General();
        $purchasetypeList = $General->getListByCategoryCode(config('const.general.purchasetype'));

        $priceList = $General->getCategoryList(config('const.general.price'));


        $initParams = collect([
            // 当月初め
            'from_arrival_date' => Carbon::now()->firstOfMonth()->format('Y/m/d'),
            // 当日
            'to_arrival_date' => Carbon::now()->format('Y/m/d'),
        ]);


        return view('Purchase.purchase-detail')
                ->with('isEditable', $isEditable)
                ->with('matterList', $matterList)
                ->with('orderList', $orderList)
                ->with('staffList', $staffList)
                ->with('allRequestNoList', $requestNoList)
                ->with('requestNoList', $requestNoList)
                ->with('departmentList', $departmentList)
                ->with('staffDepartList', $staffDepartList)
                ->with('customerList', $customerList)
                ->with('classBigList', $classBigList)
                ->with('constList', $constList)
                // ->with('productList', $productList)
                ->with('supplierList', $supplierList)
                ->with('makerList', $makerList)
                ->with('orderStaffList', $orderStaffList)
                ->with('confirmStaffList', $confirmStaffList)
                ->with('purchasetypeList', $purchasetypeList)
                ->with('priceList', $priceList)
                ->with('initParams', $initParams)
                ->with('paymentList', $paymentList)
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
        $this->isValidSearch($request);
        try {
            // 仕入先情報
            $Supplier = new Supplier();
            $supplierData = $Supplier->getById($params['supplier_id']);
            
            $Payment = new Payment();
            $paidData = $Payment->getPeriodBySupplierId($params['supplier_id']);

            $rtnData['id'] = $params['supplier_id'];

            $Common = new Common();

            $header = [];
            // 締日算出
            if (empty($paidData['id'])) {
                // 支払予定無
                $rtnData['payment_id'] = null;
                $rtnData['status'] = null;
                $rtnData['supplier_name'] = $supplierData['supplier_name'];
                $rtnData['payment_sight'] = $supplierData['payment_sight'];
                $rtnData['payment_day'] = $supplierData['payment_day'];
                // 仕入先の締日算出
                $rtnData['closing_day'] = $this->calcClosingDay($supplierData['closing_day']);
                // 支払日算出
                $rtnData['payment_date'] = Carbon::parse($this->calcClosingDay($supplierData['payment_day']))->addMonth()->format('Y/m/d');

                $rtnData['purchase_start_date'] = Carbon::parse($rtnData['closing_day'])->firstOfMonth()->format('Y/m/d');
                $rtnData['purchase_end_date'] = Carbon::parse($rtnData['closing_day'])->format('Y/m/d');
                // $rtnData['purchase_end_date'] = Carbon::parse($rtnData['closing_day'])->lastOfMonth()->format('Y/m/d');
            }else {
                $rtnData['payment_id'] = $paidData['id'];
                $rtnData['status'] = $paidData['status'];
                $rtnData['supplier_name'] = $paidData['supplier_name'];
                $rtnData['payment_sight'] = $paidData['payment_sight'];
                $rtnData['payment_day'] = $supplierData['payment_day'];
                $rtnData['payment_mon'] = $paidData['payment_mon'];
                
                // 支払予定有
                if ($paidData['status'] == config('const.paymentStatus.val.confirm')) {                    
                    // 確定
                    $closingDate = $paidData['closing_day'];
                    $rtnData['closing_day'] = $this->calcClosingDay($closingDate);
                    $rtnData['payment_date'] = $paidData['planned_payment_date'];

                    // 仕入期間
                    // 仕入開始日算出
                    $rtnData['purchase_start_date'] = $paidData['payment_period_sday'];
                    // 仕入終了日
                    $rtnData['purchase_end_date'] = $paidData['payment_period_eday'];
                }  else {
                    // // 仕入先の締日算出
                    // $supClosingDate = $this->calcClosingDay($supplierData['closing_day']);

                    // if ($toBePaidDate > $supClosingDate) {
                    //     $rtnData['closing_day'] = $toBePaidDate;                        
                    //     $rtnData['payment_date'] = $toBePaidDate;
                    // } else {
                    //     $rtnData['closing_day'] = $supClosingDate;
                    //     $rtnData['payment_date'] = $toBePaidDate;
                    // }
                    // // 仕入期間
                    // // 仕入開始日算出
                    // $paymentPeriodSday = $paidData['payment_period_sday'];
                    // $rtnData['purchase_start_date'] = Carbon::parse($paymentPeriodSday)->addMonth()->format('Y/m/d');

                    // // 仕入終了日
                    // $rtnData['purchase_end_date'] = $rtnData['closing_day'];
                    if ($paidData['status'] == config('const.paymentStatus.val.paid')) {
                        // 支払済
                        // 締日算出
                        $closingDate = $paidData['closing_day'];
                        $paidMonth = substr($paidData['payment_mon'], 4, strlen($paidData['payment_mon']));

                        $supClosingDate = $this->calcClosingDay($supplierData['closing_day']);

                        // 支払予定締日_翌月算出
                        if ($closingDate == 99) {
                            $date = Carbon::parse($this->calcClosingDay($closingDate, $paidMonth))->endOfMonth()->format('Y/m/d');
                        } else {
                            $date = $this->calcClosingDay($closingDate, $paidMonth);
                        }
                        $month = Carbon::parse($date)->format('m');
                        if ($closingDate == 99) {
                            $toBePaidDate = Carbon::parse($this->calcClosingDay($closingDate, $month))->addMonth()->endOfMonth()->format('Y/m/d');
                        } else {
                            $toBePaidDate = Carbon::parse($this->calcClosingDay($closingDate, $month))->addMonth()->format('Y/m/d');
                        }
                        // $toBePaidDate = Carbon::parse($date)->addMonth()->format('Y/m/d');
                        
                        if ($toBePaidDate > $supClosingDate) {
                            // $rtnData['closing_day'] = $toBePaidDate;                        
                            $rtnData['payment_date'] = $toBePaidDate;
                        } else {
                            // $rtnData['closing_day'] = $supClosingDate;
                            // $rtnData['payment_date'] = $toBePaidDate;
                            $rtnData['payment_date'] = Carbon::parse($supClosingDate)->addMonth()->format('Y/m/d');
                        }
                    }    
                    if ($paidData['status'] == config('const.paymentStatus.val.unsettled')) {
                        // 未確定
                        $rtnData['closing_day'] = Carbon::parse($this->calcClosingDay($paidData['closing_day']))->format('Y/m/d');
                        
                        // $rtnData['closing_day'] =  $paidData['purchase_closing_day'];
                        // 仕入期間
                        // 仕入開始日算出
                        $rtnData['purchase_start_date'] = $paidData['payment_period_sday'];
                        // 仕入終了日
                        $rtnData['purchase_end_date'] = $paidData['payment_period_eday'];
                    }    
                    
                    // 仕入先の締日算出
                    if ($supplierData['closing_day'] == 99) {
                        $supClosingDate = Carbon::parse($this->calcClosingDay($supplierData['closing_day']))->endOfMonth()->format('Y/m/d');
                    } else {
                        $supClosingDate = $this->calcClosingDay($supplierData['closing_day']);
                    }
                    if ($paidData['closing_day'] == 99) {
                        $paidClosing = Carbon::parse($this->calcClosingDay($paidData['closing_day']))->addMonth()->format('Y/m/d');
                    } else {
                        $paidClosing = Carbon::parse($this->calcClosingDay($paidData['closing_day']))->addMonth()->endOfMonth()->format('Y/m/d');
                    }

                    $payStart = Carbon::parse($paidClosing)->firstOfMonth()->format('Y/m/d');
                    $payEnd = Carbon::parse($paidClosing)->format('Y/m/d');

                    if (empty($rtnData['purchase_start_date'])) {
                        if ($supClosingDate > $paidClosing) {
                            // $rtnData['purchase_start_date'] = Carbon::parse($supClosingDate)->addDay()->format('Y/m/d');
                            // $rtnData['purchase_end_date'] = Carbon::parse($supClosingDate)->addMonth()->format('Y/m/d');
                            $rtnData['purchase_start_date'] = Carbon::parse($supClosingDate)->firstOfMonth()->format('Y/m/d');
                            $rtnData['purchase_end_date'] = Carbon::parse($supClosingDate)->format('Y/m/d');
                        } else {
                            $rtnData['purchase_start_date'] = $payStart;
                            $rtnData['purchase_end_date'] = $payEnd;
                        }
                    }

                    if (empty($rtnData['closing_day'])) {
                        if ($supClosingDate > $paidClosing) {
                            $rtnData['closing_day'] = $supClosingDate;

                            // $rtnData['purchase_start_date'] = Carbon::parse($supClosingDate)->subMonth()->addDay()->format('Y/m/d');
                            // $rtnData['purchase_end_date'] = Carbon::parse($supClosingDate)->format('Y/m/d');
                        } else {
                            $rtnData['closing_day'] = $paidClosing;

                            // $rtnData['purchase_start_date'] = Carbon::parse($paidClosing)->subMonth()->addDay()->format('Y/m/d');
                            // $rtnData['purchase_end_date'] = Carbon::parse($paidClosing)->format('Y/m/d');
                        }
                    }

                    // その他ステータス
                    if (empty($rtnData['payment_date'])) {
                        $month = Carbon::parse($rtnData['closing_day'])->format('m');
                        $payDay = Carbon::parse($this->calcClosingDay($supplierData['payment_day'], $month))->addMonth()->format('Y/m/d');
                        $rtnData['payment_date'] = $payDay;
                    }


                }
                
                if ($paidData['status'] != config('const.paymentStatus.val.closing')) {
                    $rtnData['closing_day'] =  $paidData['purchase_closing_day'];
                }
            }

            // 未確定データ取得
            // 入荷から取得
            $Arrival = new Arrival();
            $arrivalData = $Arrival->getPurchaseData($params);

            // 返品データから取得
            $Returns = new Returns();
            $returnData = $Returns->getPurchaseData($params);

            // 確定データ取得
            $PurchaseLineItem = new PurchaseLineItem();
            $purchaseData = $PurchaseLineItem->getPurchaseData($params);

            $createdBy = [
                'arrival' => 0,
                'return' => 1,
                'confirm' => 2,
            ];
            // 得意先名/施主名結合
            foreach ($arrivalData as $key => $row) {
                $arrivalData[$key]['purchase_status'] = '未確定';
                $arrivalData[$key]['purchase_status_val'] = 0;
                $arrivalData[$key]['payment_status'] = null;
                $arrivalData[$key]['payment_status_val'] = null;
                $arrivalData[$key]['purchase_type'] = null;
                $arrivalData[$key]['discount_reason'] = '';

                // 売上計上状況
                if (Common::nullorBlankToZero($arrivalData[$key]['sales_recording']) == 0) {
                    $arrivalData[$key]['sales_recording_txt'] = '未計上';
                } else {
                    $arrivalData[$key]['sales_recording_txt'] = '計上済';
                }


                $cusName = '';
                if (!empty($arrivalData[$key]['account_customer_name'])) {
                    $cusName .= $arrivalData[$key]['account_customer_name'];
                    $arrivalData[$key]['only_customer_name'] = $arrivalData[$key]['account_customer_name'];
                } else {
                    $cusName .= $arrivalData[$key]['customer_name'];
                    $arrivalData[$key]['only_customer_name'] = $arrivalData[$key]['customer_name'];
                }

                if (!empty($arrivalData[$key]['account_owner_name']) || !empty($arrivalData[$key]['owner_name'])) {
                    $cusName .= '／';
                }
                if (!empty($arrivalData[$key]['account_owner_name'])) {
                    $cusName .= $arrivalData[$key]['account_owner_name'];
                } else {
                    $cusName .= $arrivalData[$key]['owner_name'];
                }

                
                if (!empty($arrivalData[$key]['status'])) {
                    $statusKey = $arrivalData[$key]['status'];
                    $arrivalData[$key]['payment_status'] = config('const.paymentStatus.list.'. $statusKey);
                    $arrivalData[$key]['payment_status_val'] = $arrivalData[$key]['status'];
                } else {
                    $arrivalData[$key]['payment_status_val'] = config('const.flg.off');
                }


                $arrivalData[$key]['customer_name'] = $cusName;
                $arrivalData[$key]['confrim_flg'] = config('const.flg.off');
                $arrivalData[$key]['rebate_flg'] = config('const.flg.off');
                $arrivalData[$key]['discount_flg'] = config('const.flg.off');
                $arrivalData[$key]['add_flg'] = config('const.flg.off');
                $arrivalData[$key]['quantity'] = $arrivalData[$key]['quantity'] * $arrivalData[$key]['min_quantity'];

                $arrivalData[$key]['request_no'] = '';
                $arrivalData[$key]['vendors_request_no'] = '';
                $arrivalData[$key]['regular_price'] = (int)$arrivalData[$key]['regular_price'];
                $arrivalData[$key]['sales_unit_price'] = (int)$arrivalData[$key]['sales_unit_price'];
                $arrivalData[$key]['sales_total'] = (int)$arrivalData[$key]['sales_total'];
                $arrivalData[$key]['cost_unit_price'] = (int)$arrivalData[$key]['cost_unit_price'];
                $arrivalData[$key]['fix_cost_unit_price'] = (int)$arrivalData[$key]['cost_unit_price'];
                $arrivalData[$key]['fix_cost_makeup_rate'] = $arrivalData[$key]['cost_makeup_rate'];
                $arrivalData[$key]['cost_total'] = $arrivalData[$key]['quantity'] * $arrivalData[$key]['cost_unit_price'];
                $arrivalData[$key]['cost_total'] = (int)$arrivalData[$key]['cost_total'];
                $arrivalData[$key]['fix_cost_total'] = $arrivalData[$key]['quantity'] * $arrivalData[$key]['cost_unit_price'];
                $arrivalData[$key]['fix_cost_total'] = (int)$arrivalData[$key]['fix_cost_total'];

                $arrivalData[$key]['createdBy'] = null;
            }

            foreach ($returnData as $key => $row) {
                $returnData[$key]['purchase_status'] = '未確定';
                $returnData[$key]['purchase_status_val'] = 0;
                $returnData[$key]['payment_status'] = null;
                $returnData[$key]['payment_status_val'] = null;
                $returnData[$key]['purchase_type'] = null;
                $returnData[$key]['discount_reason'] = '';      

                $cusName = '';
                if (!empty($returnData[$key]['account_customer_name'])) {
                    $cusName .= $returnData[$key]['account_customer_name'];                    
                    $returnData[$key]['only_customer_name'] = $returnData[$key]['account_customer_name'];
                } else {
                    $cusName .= $returnData[$key]['customer_name'];
                    $returnData[$key]['only_customer_name'] = $returnData[$key]['customer_name'];
                }

                if (!empty($returnData[$key]['account_owner_name']) || !empty($returnData[$key]['owner_name'])) {
                    $cusName .= '／';
                }
                if (!empty($returnData[$key]['account_owner_name'])) {
                    $cusName .= $returnData[$key]['account_owner_name'];
                } else {
                    $cusName .= $returnData[$key]['owner_name'];
                }

                // 売上計上状況
                if (Common::nullorBlankToZero($returnData[$key]['sales_recording']) == 0) {
                    $returnData[$key]['sales_recording_txt'] = '未計上';
                } else {
                    $returnData[$key]['sales_recording_txt'] = '計上済';
                }

                if (!empty($returnData[$key]['status'])) {
                    $statusKey = $returnData[$key]['status'];
                    $returnData[$key]['payment_status'] = config('const.paymentStatus.list.'. $statusKey);
                    $returnData[$key]['payment_status_val'] = $returnData[$key]['status'];
                } else {
                    $returnData[$key]['payment_status_val'] = config('const.flg.off');
                }

                // フラグ系追加
                $returnData[$key]['customer_name'] = $cusName;          
                $returnData[$key]['confrim_flg'] = config('const.flg.off');
                $returnData[$key]['rebate_flg'] = config('const.flg.off');
                $returnData[$key]['discount_flg'] = config('const.flg.off');
                $returnData[$key]['add_flg'] = config('const.flg.off');

                $returnData[$key]['request_no'] = '';
                $returnData[$key]['vendors_request_no'] = '';
                $returnData[$key]['quantity'] = (float)-$returnData[$key]['quantity'] * $returnData[$key]['min_quantity'];
                $returnData[$key]['cost_unit_price'] = (int)$returnData[$key]['cost_unit_price'];
                $returnData[$key]['cost_makeup_rate'] = (float)$returnData[$key]['cost_makeup_rate'];
                $returnData[$key]['regular_price'] = (int)$returnData[$key]['regular_price'];
                $returnData[$key]['sales_unit_price'] = (int)$returnData[$key]['sales_unit_price'];
                $returnData[$key]['sales_total'] = (int)$returnData[$key]['sales_total'];
                $returnData[$key]['fix_cost_unit_price'] = (int)$returnData[$key]['cost_unit_price'];
                $returnData[$key]['fix_cost_makeup_rate'] = (float)$returnData[$key]['cost_makeup_rate'];
                $returnData[$key]['cost_total'] = $returnData[$key]['quantity'] * $returnData[$key]['cost_unit_price'];
                $returnData[$key]['cost_total'] = (int)$returnData[$key]['cost_total'];
                $returnData[$key]['fix_cost_total'] = $returnData[$key]['quantity'] * $returnData[$key]['cost_unit_price'];
                $returnData[$key]['fix_cost_total'] = (int)$returnData[$key]['fix_cost_total'];
                
                $returnData[$key]['createdBy'] = null;
            }

            foreach ($purchaseData as $key => $row) {
                $cusName = '';
                if (!empty($purchaseData[$key]['account_customer_name'])) {
                    $cusName .= $purchaseData[$key]['account_customer_name'];
                    $purchaseData[$key]['only_customer_name'] = $purchaseData[$key]['account_customer_name'];
                } else {
                    $cusName .= $purchaseData[$key]['customer_name'];
                    $purchaseData[$key]['only_customer_name'] = $purchaseData[$key]['customer_name'];
                }

                if (!empty($purchaseData[$key]['account_owner_name']) || !empty($purchaseData[$key]['owner_name'])) {
                    $cusName .= '／';
                }
                if (!empty($purchaseData[$key]['account_owner_name'])) {
                    $cusName .= $purchaseData[$key]['account_owner_name'];
                } else {
                    $cusName .= $purchaseData[$key]['owner_name'];
                }
                $purchaseData[$key]['customer_name'] = $cusName;  
                $purchaseData[$key]['confrim_flg'] = config('const.flg.on');

                // 明細行の追加フラグをONにする
                if ($purchaseData[$key]['product_id'] == 0) {
                    if (empty($purchaseData[$key]['regular_price'])) {
                        $purchaseData[$key]['regular_price'] = (int)$purchaseData[$key]['fix_cost_unit_price'];
                        $purchaseData[$key]['regular_price'] = (int)$purchaseData[$key]['regular_price'];
                    }
                    $purchaseData[$key]['add_flg'] = config('const.flg.on');
                } else{ 
                    $purchaseData[$key]['add_flg'] = config('const.flg.off');
                }

                // 値引きフラグ
                if (isset($purchaseData[$key]['purchase_type']) && $purchaseData[$key]['purchase_type'] == config('const.purchaseType.val.outside_product')) {
                    $purchaseData[$key]['discount_flg'] = config('const.flg.on');
                } else {
                    $purchaseData[$key]['discount_flg'] = config('const.flg.off');
                }
                // リベートフラグ
                if (isset($purchaseData[$key]['purchase_type']) && $purchaseData[$key]['purchase_type'] == config('const.purchaseType.val.rebate')) {    
                    $purchaseData[$key]['rebate_flg'] = config('const.flg.on');
                } else {
                    $purchaseData[$key]['rebate_flg'] = config('const.flg.off');
                }

                // $purchaseData[$key]['fix_cost_unit_price'] = $purchaseData[$key]['cost_unit_price'];
                // $purchaseData[$key]['cost_total'] = $purchaseData[$key]['quantity'] * $purchaseData[$key]['cost_unit_price'];
                // $purchaseData[$key]['fix_cost_total'] = $purchaseData[$key]['quantity'] * $purchaseData[$key]['cost_unit_price'];

                // 仕入状況設定
                if (Common::nullToBlank($purchaseData[$key]['confirmed_staff_id']) == '') {
                    $purchaseData[$key]['purchase_status'] = '未確定';
                    $purchaseData[$key]['purchase_status_val'] = 0;
                } else {
                    $purchaseData[$key]['purchase_status'] = '確定済';
                    $purchaseData[$key]['purchase_status_val'] = 1;
                }               

                // 売上計上状況
                if (Common::nullorBlankToZero($purchaseData[$key]['sales_recording']) == 0) {
                    $purchaseData[$key]['sales_recording_txt'] = '未計上';
                } else {
                    $purchaseData[$key]['sales_recording_txt'] = '計上済';
                }
                
                if (!empty($purchaseData[$key]['status'])) {
                    $statusKey = $purchaseData[$key]['status'];
                    $purchaseData[$key]['payment_status'] = config('const.paymentStatus.list.'. $statusKey);
                    $purchaseData[$key]['payment_status_val'] = $purchaseData[$key]['status'];
                } else {
                    $purchaseData[$key]['payment_status_val'] = config('const.flg.off');
                }

                if (!empty($purchaseData[$key]['arrival_id'])) {
                    $purchaseData[$key]['createdBy'] = $createdBy['arrival'];
                }
                if (!empty($purchaseData[$key]['return_id'])) {
                    $purchaseData[$key]['createdBy'] = $createdBy['return'];
                }
                if (empty($purchaseData[$key]['arrival_id']) && empty($purchaseData[$key]['return_id'])) {
                    $purchaseData[$key]['createdBy'] = $createdBy['confirm'];
                }

                $purchaseData[$key]['sales_unit_price'] = (int)$purchaseData[$key]['sales_unit_price'];
                $purchaseData[$key]['sales_total'] = (int)$purchaseData[$key]['sales_total'];
                $purchaseData[$key]['cost_unit_price'] = (int)$purchaseData[$key]['cost_unit_price'];
                $purchaseData[$key]['fix_cost_unit_price'] = (int)$purchaseData[$key]['fix_cost_unit_price'];
                $purchaseData[$key]['cost_total'] = (int)$purchaseData[$key]['cost_total'];
                $purchaseData[$key]['fix_cost_total'] = (int)$purchaseData[$key]['fix_cost_total'];
                
            }
            // グリッドデータ作成
            $rtnData['gridData'] = array_merge($arrivalData->toArray(), $returnData->toArray(), $purchaseData->toArray());


        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($rtnData);
    }    

    
    /**
     * 保存
     *
     * @param [type] $params
     * @return void
     */
    public function save(Request $request) 
    {
        $resultSts = ['status' => false, 'message' => ''];

        // リクエストデータ取得
        $params = $request->request->all();
        $beforePaymentId = $params['payment_id'];
        $status = $params['status'];
        $list = json_decode($params['gridData'], true);
        
        $PurchaseLineItem = new PurchaseLineItem();
        $Payment = new Payment();
        $Matter = new Matter();
        $Department = new Department();
        $NumberManage = new NumberManage();
        $Requests = new Requests();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $RequestPayment = new RequestPayment();
        $LockManage = new LockManage();
        $Customer = new Customer();
        $SystemUtil = new SystemUtil();
        $QuoteDetail = new QuoteDetail();
        $ProductCustomerPrice = new ProductCustomerPrice();

        // ロック取得
        $customerIdList = collect($list)->pluck('customer_id')->unique()->toArray();

        // 更新対象に紐付く得意先のロック取得
        $lockedCustomerIdList = [];
        // 画面名から対象テーブルを取得
        $tableName = config('const.lockList.'.self::SCREEN_NAME)[0];
        $lockCnt = 0;
        DB::beginTransaction();
        foreach ($customerIdList as $i => $customerId) {
            // ロック確認
            $isLocked = $LockManage->isLocked($tableName, $customerId);
            if (!$isLocked) {
                // ロックされていない場合はロック取得
                $lockDt = Carbon::now();
                $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $customerId);
            }
            // ロックデータ取得
            $lockData = $LockManage->getLockData($tableName, $customerId);
            if (!is_null($lockData) 
                && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                && $lockData->key_id === $customerId && $lockData->lock_user_id === Auth::user()->id) {
                    $lockCnt++;
            }
            $lockedCustomerIdList[] = $customerId;
        }
        // ロック確定
        if ($lockCnt > 0) {
            DB::commit();
        } else {
            DB::rollBack();
        }
        // ロック状況を配列に格納
        foreach($list as $key => $row) {
            $isOwnLock = $LockManage->isOwnLocked(self::SCREEN_NAME, $tableName, $row['customer_id']);
            $list[$key]['isOwnLock'] = $isOwnLock;
        }

        DB::beginTransaction();
        try{
            $saveResult = false;
            $delLock = false;
            $paymentNo = $NumberManage->getSeqNo(config('const.number_manage.kbn.payment'), Carbon::today()->format('Ym'));
            
            $paymentMon = Carbon::parse($params['closing_day'])->format('Ym');

            if (!empty($beforePaymentId) && $status == config('const.paymentStatus.val.unsettled')) {
                $pData = $Payment->getById($beforePaymentId);
            } else {
                $pData = $Payment->getPaymentBySupplierIdAndMonth($params['supplier_id'], $paymentMon);

                if ($pData['status'] == config('const.paymentStatus.val.confirm') 
                    || $pData['status'] == config('const.paymentStatus.val.applying') 
                    || $pData['status'] == config('const.paymentStatus.val.approvaled')
                    || $pData['status'] == config('const.paymentStatus.val.paid')) 
                {
                    // 「未確定」「締め済」以外であれば処理終了
                    DB::rollBack();
                    $resultSts['status'] = false;
                    $resultSts['message'] = config('message.error.purchase_detail.payment_status_confirm');
    
                    return \Response::json($resultSts);
                }

                if (empty($pData)) {
                    // 過去の支払予定に「締め済」以外が存在するか
                    $isExist = $Payment->isExistNotClosingPayment($params['supplier_id']);
                    
                    if ($isExist) {
                        // 存在する場合、処理終了
                        DB::rollBack();
                        $resultSts['status'] = false;
                        $resultSts['message'] = config('message.error.purchase_detail.payment_status_not_closing');
    
                        return \Response::json($resultSts);
                    }
                }
            }
            
            if (!empty($pData)) {
                $paymentId = $pData['id'];

                $paymentRecord = [];
                $paymentRecord['id'] = $paymentId;
                $paymentRecord['payment_mon'] = Carbon::parse($params['closing_day'])->format('Ym');
                $paymentRecord['closing_day'] = Carbon::parse($params['closing_day'])->format('d');
                $paymentRecord['purchase_closing_day'] = $params['closing_day'];
                $paymentRecord['payment_period_sday'] = $params['payment_period_sday'];
                $paymentRecord['payment_period_eday'] = $params['payment_period_eday'];

                $result = $Payment->updateById($paymentRecord);
            } else {
                // 新規作成
                // 支払予定作成
                $paymentRecord = [];
                $paymentRecord['payment_no'] = $paymentNo;
                $paymentRecord['supplier_id'] = $params['supplier_id'];
                $paymentRecord['supplier_name'] = $params['supplier_name'];
                $paymentRecord['payment_mon'] = Carbon::parse($params['closing_day'])->format('Ym');
                $paymentRecord['closing_day'] = Carbon::parse($params['closing_day'])->format('d');
                $paymentRecord['payment_sight'] = $params['payment_sight'];
                $paymentRecord['payment_date'] = $params['payment_sight'];
                $paymentRecord['purchase_closing_day'] = $params['closing_day'];
                $paymentRecord['payment_period_sday'] = $params['payment_period_sday'];
                $paymentRecord['payment_period_eday'] = $params['payment_period_eday'];
                $paymentRecord['requested_amount'] = 0;
                $paymentRecord['rebate'] = 0;
                $paymentRecord['safety_fee'] = 0;
                $paymentRecord['safety_fee_type'] = config('const.safetyFeeType.val.other');
                $paymentRecord['offset'] = 0;
                $paymentRecord['paid_rebate'] = 0;
                $paymentRecord['cash'] = 0;
                $paymentRecord['cash_type'] = 0;
                $paymentRecord['check'] = 0;
                $paymentRecord['transfer'] = 0;
                $paymentRecord['fee'] = 0;
                $paymentRecord['bills'] = 0;
                $paymentRecord['issuance_fee'] = 0;
                $paymentRecord['planned_payment_date'] = $params['payment_date'];
                $paymentRecord['status'] = config('const.paymentStatus.val.unsettled');
                $paymentRecord['confirmed_staff_id'] = null;
                $paymentRecord['fixed_date'] = null;
                $paymentRecord['request_payment_id'] = null;
                $paymentRecord['membershipfee_flg'] = config('const.flg.off');
                $paymentRecord['offset_flg'] = config('const.flg.off');
                $paymentRecord['cash_flg'] = config('const.flg.off');
                $paymentRecord['transfer_flg'] = config('const.flg.off');
                $paymentRecord['bills_flg'] = config('const.flg.off');
                $paymentRecord['electricitydebt_flg'] = config('const.flg.off');
                $paymentRecord['transmittal_flg'] = config('const.flg.off');
                $paymentRecord['accounting_flg'] = config('const.flg.off');

                $paymentId = $Payment->add($paymentRecord);
            }

            foreach($list as $key => $row) {
                if (!$list[$key]['isOwnLock']) {
                    // ロックを取得していない
                    $customerName = $list[$key]['only_customer_name'];
                    if ($resultSts['message'] == '') {
                        $resultSts['message'] = config('message.error.purchase_detail.customer_locked');
                    }
                    $resultSts['message'] .= '\n・'. $customerName;
                } else {
                    // ロックを取得している
                    $purchaseRecord = [];
                    if (empty($list[$key]['id'])) {
                        // 仕入明細作成
                        if ($list[$key]['add_flg'] == config('const.flg.on')) {
                            // 明細追加行
                            // 案件から担当部門取得
                            $matterData = $Matter->getById($list[$key]['matter_id']);
                            $departData = $Department->getById($matterData['department_id']);
                            $list[$key]['department_id'] = $departData['id'];
                            $list[$key]['department_name'] = $departData['department_name'];
                        }
                        $purchaseRecord['payment_id'] = $paymentId;
                        $purchaseRecord['customer_id'] = $list[$key]['customer_id'];
                        $purchaseRecord['customer_name'] = $list[$key]['only_customer_name'];
                        $purchaseRecord['matter_id'] = $list[$key]['matter_id'];
                        $purchaseRecord['charge_department_id'] = $list[$key]['department_id'];
                        $purchaseRecord['charge_department_name'] = $list[$key]['department_name'];
                        $purchaseRecord['order_id'] = $list[$key]['order_id'];
                        $purchaseRecord['order_no'] = $list[$key]['order_no'];
                        $purchaseRecord['order_detail_id'] = $list[$key]['order_detail_id'];
                        $purchaseRecord['purchase_type'] = $list[$key]['purchase_type'];
                        $purchaseRecord['discount_reason'] = $list[$key]['discount_reason'];
                        $purchaseRecord['arrival_id'] = $list[$key]['arrival_id'];
                        $purchaseRecord['return_id'] = $list[$key]['return_id'];
                        $purchaseRecord['supplier_id'] = $list[$key]['supplier_id'];
                        $purchaseRecord['supplier_name'] = $list[$key]['supplier_name'];
                        $purchaseRecord['arrival_date'] = $list[$key]['arrival_date'];
                        $purchaseRecord['product_id'] = $list[$key]['product_id'];
                        $purchaseRecord['product_code'] = $list[$key]['product_code'];
                        $purchaseRecord['product_name'] = $list[$key]['product_name'];
                        $purchaseRecord['model'] = $list[$key]['model'];
                        $purchaseRecord['maker_id'] = $list[$key]['maker_id'];
                        $purchaseRecord['maker_name'] = $list[$key]['maker_name'];
                        $purchaseRecord['min_quantity'] = $list[$key]['min_quantity'];
                        $purchaseRecord['stock_quantity'] = $list[$key]['stock_quantity'];
                        $purchaseRecord['quantity'] = $list[$key]['quantity'];
                        $purchaseRecord['unit'] = $list[$key]['unit'];
                        $purchaseRecord['cost_kbn'] = $list[$key]['cost_kbn'];
                        $purchaseRecord['cost_unit_price'] = $list[$key]['cost_unit_price'];
                        $purchaseRecord['fix_cost_unit_price'] = $list[$key]['fix_cost_unit_price'];
                        $purchaseRecord['cost_makeup_rate'] = $list[$key]['cost_makeup_rate'];
                        $purchaseRecord['fix_cost_makeup_rate'] = $list[$key]['fix_cost_makeup_rate'];
                        $purchaseRecord['cost_total'] = $list[$key]['cost_total'];
                        $purchaseRecord['fix_cost_total'] = $list[$key]['fix_cost_total'];
                        $purchaseRecord['vendors_request_no'] = $list[$key]['vendors_request_no'];
                        $purchaseRecord['confirmed_staff_id'] = Auth::user()->id;
                        if (Common::nullToBlank($list[$key]['fixed_date']) == "") {
                            $purchaseRecord['fixed_date'] = Carbon::now()->format('Y/m/d H:i:s');
                        } else {
                            $purchaseRecord['fixed_date'] = Carbon::parse($list[$key]['fixed_date']. ' 00:00:01')->format('Y/m/d H:i:s');
                        }

                        $purchaseLineItemId = $PurchaseLineItem->add($purchaseRecord);

                    } else {
                        // 更新
                        $purchaseRecord['id'] = $list[$key]['id'];
                        $purchaseRecord['payment_id'] = $paymentId;
                        $purchaseRecord['customer_id'] = $list[$key]['customer_id'];
                        $purchaseRecord['customer_name'] = $list[$key]['only_customer_name'];
                        $purchaseRecord['matter_id'] = $list[$key]['matter_id'];
                        $purchaseRecord['charge_department_id'] = $list[$key]['department_id'];
                        $purchaseRecord['charge_department_name'] = $list[$key]['department_name'];
                        $purchaseRecord['order_id'] = $list[$key]['order_id'];
                        $purchaseRecord['order_no'] = $list[$key]['order_no'];
                        $purchaseRecord['order_detail_id'] = $list[$key]['order_detail_id'];
                        $purchaseRecord['purchase_type'] = $list[$key]['purchase_type'];
                        $purchaseRecord['discount_reason'] = $list[$key]['discount_reason'];
                        $purchaseRecord['arrival_id'] = $list[$key]['arrival_id'];
                        $purchaseRecord['return_id'] = $list[$key]['return_id'];
                        $purchaseRecord['supplier_id'] = $list[$key]['supplier_id'];
                        $purchaseRecord['supplier_name'] = $list[$key]['supplier_name'];
                        $purchaseRecord['arrival_date'] = $list[$key]['arrival_date'];
                        $purchaseRecord['product_id'] = $list[$key]['product_id'];
                        $purchaseRecord['product_code'] = $list[$key]['product_code'];
                        $purchaseRecord['product_name'] = $list[$key]['product_name'];
                        $purchaseRecord['model'] = $list[$key]['model'];
                        $purchaseRecord['maker_id'] = $list[$key]['maker_id'];
                        $purchaseRecord['maker_name'] = $list[$key]['maker_name'];
                        $purchaseRecord['min_quantity'] = $list[$key]['min_quantity'];
                        $purchaseRecord['stock_quantity'] = $list[$key]['stock_quantity'];
                        $purchaseRecord['quantity'] = $list[$key]['quantity'];
                        $purchaseRecord['unit'] = $list[$key]['unit'];
                        $purchaseRecord['cost_kbn'] = $list[$key]['cost_kbn'];
                        $purchaseRecord['cost_unit_price'] = $list[$key]['cost_unit_price'];
                        $purchaseRecord['fix_cost_unit_price'] = $list[$key]['fix_cost_unit_price'];
                        $purchaseRecord['cost_makeup_rate'] = $list[$key]['cost_makeup_rate'];
                        $purchaseRecord['fix_cost_makeup_rate'] = $list[$key]['fix_cost_makeup_rate'];
                        $purchaseRecord['cost_total'] = $list[$key]['cost_total'];
                        $purchaseRecord['fix_cost_total'] = $list[$key]['fix_cost_total'];
                        $purchaseRecord['vendors_request_no'] = $list[$key]['vendors_request_no'];
                        $purchaseRecord['confirmed_staff_id'] = Auth::user()->id;
                        if (Common::nullToBlank($list[$key]['fixed_date']) == "") {
                            $purchaseRecord['fixed_date'] = Carbon::now()->format('Y/m/d H:i:s');
                        } else {
                            $purchaseRecord['fixed_date'] = Carbon::parse($list[$key]['fixed_date']. ' 00:00:01')->format('Y/m/d H:i:s');
                        }

                        $saveResult = $PurchaseLineItem->updateById($purchaseRecord);
                    }
                    
                    // 売上、売上明細を更新する
                    $id = empty($list[$key]['id']) ? $purchaseLineItemId : $list[$key]['id'];

                    // $purchaseList = $PurchaseLineItem->getParentDataByPaymentIdList(array($purchaseRecord['payment_id']));
                    $purchaseData = $PurchaseLineItem->getSalesUpdateDataById($id);
                    // foreach ($purchaseList as $pKey => $pRow) {
                    if (!empty($purchaseData['quote_detail_id'])) {
                        // 得意先IDから申請中の承認が存在しないかを確認
                        $hasApply = $Sales->hasApplyingSalesByCustomerId($purchaseData['customer_id']);
                        if ($hasApply) {
                            // 存在した場合　処理終了
                            $customerName = $purchaseData['customer_name'];
                            $resultSts['message'] = $customerName . ':'. self::ERROR_APPLYING_SALES;
                            throw new \Exception();
                        }

                        // 見積明細から売上明細取得
                        $salesData = $Sales->getSalesDataByQuoteDetailId($purchaseData['quote_detail_id'], $purchaseData['cost_kbn']);

                        foreach($salesData as $sKey => $sRow) {
                            if ($salesData[$sKey]['invalid_unit_price_flg'] == config('const.flg.off')) {
                                // 売上.単価無効フラグ = 0
                                // 売上明細取得
                                $salesDetailData = $SalesDetail->getListByQuoteDetailId($purchaseData['quote_detail_id']);
                                // 仕入金額更新
                                $costTotal = 0;
                                $salesTotal = 0;
                                foreach ($salesDetailData as $sdKey => $sdRow) {
                                    $salesDetailUpdateData = [];
                                    $salesDetailUpdateData['id'] = $salesDetailData[$sdKey]['id'];
                                    $salesDetailUpdateData['cost_unit_price'] = $purchaseData['fix_cost_unit_price'];
                                    $salesDetailUpdateData['cost_total'] = $SystemUtil->calcTotal($salesDetailData[$sdKey]['sales_quantity'], $purchaseData['fix_cost_unit_price'], true);

                                    $costTotal += $salesDetailUpdateData['cost_total'];
                                    $salesTotal += $salesDetailData[$sdKey]['sales_total'];

                                    if ($salesDetailData[$sdKey]['cost_unit_price'] != $salesDetailUpdateData['cost_unit_price']) {
                                        $saveResult = $SalesDetail->updateById($salesDetailUpdateData['id'], $salesDetailUpdateData);
                                    }
                                }

                                // 売上データ更新
                                $salesUpdateData = [];
                                $salesUpdateData['id'] = $salesData[$sKey]['id'];
                                $salesUpdateData['cost_unit_price'] = $purchaseData['fix_cost_unit_price'];
                                $salesUpdateData['cost_makeup_rate'] = $SystemUtil->calcRate($salesUpdateData['cost_unit_price'], $salesData[$sKey]['regular_price']);
                                if (empty($salesData[$sKey]['update_cost_unit_price'])) {
                                    // 反映前_仕入単価がnullの場合は更新
                                    $salesUpdateData['update_cost_unit_price'] = $salesData[$sKey]['cost_unit_price'];
                                    $salesUpdateData['update_cost_unit_price_d'] = Carbon::now();
                                }
                                // 粗利額計算
                                $profitPrice = $SystemUtil->calcProfitTotal($salesTotal, $costTotal);
                                // 粗利率計算
                                $profitRate = $SystemUtil->calcRate($profitPrice, $salesTotal);
                                
                                // 販売額が0円の場合粗利額、粗利率は0にする
                                if ($salesTotal === 0) {
                                    $profitPrice = 0;
                                    $profitRate = 0;
                                }
                                // データセット
                                $salesUpdateData['profit_total'] = $profitPrice;
                                $salesUpdateData['gross_profit_rate'] = $profitRate;
                                
                                if ($salesData[$sKey]['cost_unit_price'] != $salesUpdateData['cost_unit_price']) {
                                    $result = $Sales->updateById($salesUpdateData['id'], $salesUpdateData);
                                }

                                // 請求データ取得
                                $requestData = $Requests->getById($salesData[$sKey]['request_id']);
                                if ($requestData['status'] == config('const.requestStatus.val.complete')) {
                                    // 売上確定済みの場合、請求更新
                                    $sumSalesVolume = $SalesDetail->getCostTotalByRequestId($salesData[$sKey]['request_id'])['cost_total'];

                                    // 売上明細取得[仕入高計算用]
                                    // $calcSalesData = $SalesDetail->getCostTotalByRequestId($requestData['id']);

                                    $requestUpdateData = [];
                                    $requestUpdateData['id'] = $requestData['id'];
                                    $requestUpdateData['purchase_volume'] = $sumSalesVolume;

                                    $result = $Requests->updateById($requestUpdateData['id'], $requestUpdateData);
                                }
                            } else {
                                // 売上.単価無効フラグ = 1
                                // 見積番号と階層パスの「_」までをキーに検索し、販売額利用フラグが設定されている階層の見積明細IDを取得する。
                                if(!empty($purchaseData['quote_no'])) {
                                    $quoteDetailData = $QuoteDetail->getSalesUseFlgQuoteByQuoteNo($purchaseData['quote_no']);
                                }

                                if (!empty($quoteDetailData)) {
                                    foreach($quoteDetailData as $qKey => $qRow) {                                            
                                        $updateTargetSalesData = $SalesDetail->getByQuoteDetailId($quoteDetailData[$qKey]['parent_quote_detail_id']);

                                        if (count($updateTargetSalesData) > 0) {
                                            $escapeDetailId = [];
                                            $costUnitPrice = $quoteDetailData[$qKey]['cost_total'];
                                            $salesTotal = 0;
                                            $costTotal = 0;
                                            foreach($updateTargetSalesData as $i => $record) {
                                                $salesId = $updateTargetSalesData[$i]['sales_id'];
                                                $updateData = [];
                                                $updateData['id'] = $updateTargetSalesData[$i]['id'];
                                                // $updateData['quote_detail_id'] = $updateTargetSalesData['quote_detail_id'];
                                                $updateData['cost_unit_price'] = $quoteDetailData[$qKey]['cost_total'];
                                                $updateData['cost_total'] = $SystemUtil->calcTotal($updateTargetSalesData[$i]['sales_quantity'], $quoteDetailData[$qKey]['cost_total'], true);
                                                
                                                $salesTotal += $updateTargetSalesData[$i]['sales_total'];
                                                $costTotal += $updateData['cost_total'];

                                                $escapeDetailId[] = $updateData['id'];
                                                // 売上明細の仕入金額更新
                                                $result = $SalesDetail->updateById($updateData['id'], $updateData);
                                            }
                                            // 売上の仕入金額更新
                                            $salesTargetData = $Sales->getById($salesId);

                                            $updateSalesData = [];
                                            $updateSalesData['id'] = $salesTargetData['id'];
                                            $updateSalesData['cost_unit_price'] = $costTotal;
                                            if (empty($salesData[$sKey]['update_cost_unit_price'])) {
                                                $updateSalesData['update_cost_unit_price'] = $salesTargetData['cost_unit_price'];
                                                $updateSalesData['update_cost_unit_price_d'] = Carbon::now();
                                            }
                                            // 粗利額計算
                                            $profitPrice = $SystemUtil->calcProfitTotal($salesTotal, $costTotal);
                                            // 粗利率計算
                                            $profitRate = $SystemUtil->calcRate($profitPrice, $salesTotal);

                                            // 販売額が0円の場合粗利額、粗利率は0にする
                                            if ($salesTotal === 0) {
                                                $profitPrice = 0;
                                                $profitRate = 0;
                                            }
                                            // データセット
                                            $updateSalesData['profit_total'] = $profitPrice;
                                            $updateSalesData['gross_profit_rate'] = $profitRate; 
                                            
                                            $result = $Sales->updateById($updateSalesData['id'], $updateSalesData);

                                            $salesDetailAllData = $SalesDetail->getByQuoteDetailId($quoteDetailData[$qKey]['quote_detail_id']);
                                            $detailCostTotal = 0;
                                            foreach($salesDetailAllData as $record) {
                                                if (!in_array($record['id'], $escapeDetailId)) {
                                                    $detailCostTotal += $record['cost_total'];
                                                }
                                            }
                                            $volumeTotal = $costTotal + $detailCostTotal;
                                            
                                            $reqData = $Requests->getById($salesTargetData['request_id']);
                                            if ($reqData['status'] == config('const.requestStatus.val.complete')) {
                                                $sumSalesVolume = $SalesDetail->getCostTotalByRequestId($salesData[$sKey]['request_id'])['cost_total'];

                                                // 売上確定済みの場合、請求更新
                                                $reqUpdateData['id'] = $reqData['id'];
                                                $reqUpdateData['purchase_volume'] = $sumSalesVolume;
                                                $result = $Requests->updateById($reqUpdateData['id'], $reqUpdateData);
                                            }
                                        }
                                    }
                                }
                            }
                        }
                        // }
                    }
                    // 「得意先別商品単価」テーブルにデータを生成する。
                    $payData = $Payment->getByRequestPaymentId(array($paymentId));

                    foreach ($payData as $payKey => $payRow) {
                        if ($payData[$payKey]['cost_kbn'] == config('const.unitPriceKbn.cutomer_normal')) {
                            $customerPriceData = [];
                            $customerPriceData['customer_id'] = $payData[$payKey]['customer_id'];
                            $customerPriceData['product_id'] = $payData[$payKey]['product_id'];
                            $customerPriceData['cost_sales_kbn'] = config('const.flg.on');
                            $customerPriceData['price'] = $payData[$payKey]['fix_cost_unit_price'];
                            $customerPriceData['quote_detail_id'] = 0;

                            $result = $ProductCustomerPrice->add($customerPriceData);
                        }
                    }
                }
            }

            foreach ($customerIdList as $key) { 
                $LockManage->deleteLockInfo(self::SCREEN_NAME, array($key), Auth::user()->id);
            }

            DB::commit();
            $resultSts['status'] = true;
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts['status'] = false;
            if ($resultSts['message'] == "") {
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
     * @param [type] $params
     * @return void
     */
    public function cancel(Request $request) 
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $list = json_decode($params['gridData'], true);
        
        $PurchaseLineItem = new PurchaseLineItem();
        DB::beginTransaction();
        try{
            $saveResult = false;

            foreach($list as $key => $row) {
                $list[$key]['confirmed_staff_id'] = null;
                $list[$key]['fixed_date'] = null;

                $saveResult = $PurchaseLineItem->cancelConfirm($list[$key]);
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
     * 明細削除
     * 仕入明細画面から追加された明細のみ削除可能
     *
     * @param [type] $params
     * @return void
     */
    public function delete(Request $request) 
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $list = json_decode($params['gridData'], true);
        
        $PurchaseLineItem = new PurchaseLineItem();
        DB::beginTransaction();
        try{
            $saveResult = false;

            foreach($list as $key => $row) {
                $saveResult = $PurchaseLineItem->deleteById($list[$key]['id']);
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
     * オートコンプリート生成
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function selectSupplier(Request $request)
    {
        // リクエストから検索条件取得
        $params = $request->request->all();
        try {
            $rtnData = [];

            // メーカー一覧取得
            $Supplier = new Supplier();
            $rtnData['makerList'] = $Supplier->getMakerBySupplierId($params['supplier_id']);

            // 発注番号一覧取得
            $OrderDetail = new OrderDetail();
            $rtnData['orderNoList'] = $OrderDetail->getOrderNoBySupplierId($params['supplier_id']);

            // 発注担当者取得
            $Staff = new Staff();
            $rtnData['orderStaffList'] = $Staff->getOrderStaffBySupplierId($params['supplier_id']);

            // 明細確定担当者取得
            $PurchaseLineItem = new PurchaseLineItem();
            $rtnData['confirmStaffList'] = $PurchaseLineItem->getConfirmStaffBySupplierId($params['supplier_id']);
            // 仕入先請求番号取得
            $rtnData['requestNoList'] = $PurchaseLineItem->getRequestNoBySupplierId($params['supplier_id']);

            // 部門取得
            $Department = new Department();
            $rtnData['departmentList'] = $Department->getDepartmentBySupplierId($params['supplier_id']);

            // 営業担当者取得
            $rtnData['staffList'] = $Department->getMatterStaffBySupplierId($params['supplier_id']);

            // 得意先リスト取得
            $Customer = new Customer();
            $rtnData['customerList'] = $Customer->getCustomerBySupplierId($params['supplier_id']);

            // 案件リスト取得
            $Matter = new Matter();
            $rtnData['matterList'] = $Matter->getMatterListBySupplierId($params['supplier_id']);

            // 支払番号リスト取得
            $Payment = new Payment();
            $rtnData['paymentList'] = $Payment->getPaymentNoBySupplierId($params['supplier_id']);

            // 大分類リスト取得
            $ClassBig = new ClassBig();
            $rtnData['classBigList'] = $ClassBig->getClassBigBySupplierId($params['supplier_id']);

            // 工事区分取得
            $Construction = new Construction();
            $rtnData['constList'] = $Construction->getConstructionBySupplierId($params['supplier_id']);

            // 商品リスト取得
            $Product = new Product();
            $rtnData['productList'] = $Product->getProductBySupplierId($params['supplier_id']);

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($rtnData);
    }   
    

    /**
     * 締日から月日取得
     *
     * @param [type] $closingDay
     * @return void
     */
    protected function calcClosingDay($closingDay, $month = null) 
    {
        $rtnDate = null;

        if (!isset($closingDay)) {
            // 空白の場合はNULLを返す
            return null;
        }

        if ($closingDay == 99) {
            // 月末日
            if (empty($month)) {
                $rtnDate = Carbon::now()->endOfMonth()->format('Y/m/d');
            }else {
                $year = Carbon::now()->format('Y/');

                $rtnDate = Carbon::parse($year.$month)->endOfMonth()->format('Y/m/d');
            }

        } else if($closingDay == 0) {
            $nowDay = Carbon::now()->format('d');
            if (empty($month)) {
                $nowMonth = Carbon::now()->format('Y/m/');
                $rtnDate = $nowMonth.$nowDay;
            } else {
                $year = Carbon::now()->format('Y/');
                $rtnDate = $year. $month. '/'. $nowDay;
            }
        }else if($closingDay >= 1 && $closingDay <= 31) {
            // 締日が1 ~ 31の場合
            // 日付計算処理
            if (empty($month)) {
                $nowMonth = Carbon::now()->format('Y/m/');
                $rtnDate = $nowMonth.$closingDay;
            } else {
                $year = Carbon::now()->format('Y/');
                $rtnDate = $year. $month. '/'. $closingDay;
            }

        }
        return $rtnDate;
    }


   /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValidSearch(Request $request)
    {
        $this->validate($request, [
            'supplier_id' => 'required',
        ], 
        [
            'supplier_id.required' => '必須です。',
        ]);
    }
}