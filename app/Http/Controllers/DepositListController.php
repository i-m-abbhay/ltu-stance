<?php

namespace App\Http\Controllers;

use Session;
use Auth;
use ZipArchive;
use App\Models\System;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use App\Models\Arrival;
use App\Models\Requests;
use App\Models\ImportHistory;
use App\Models\Bank;
use App\Models\Credited;
use App\Models\CreditedDetail;
use App\Models\Notice;
use App\Models\Staff;
use DB;
use App\Models\LockManage;
use App\Models\Department;
use App\Models\CreditedCorrelate;
use App\Models\Customer;
use App\Models\StaffDepartment;
use App\Models\Authority;
use App\Libs\Common;
use App\Models\Delivery;
use App\Models\Qr;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\NumberManage;
use Exception;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Test;

/**
 * 入金一覧
 */
class DepositListController extends Controller
{
    const SCREEN_NAME = 'deposit-list';

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
        // データ取得
        $Customer = new Customer();
        $Department = new Department();
        $Staff = new Staff();
        $Bank = new Bank();

        $customerList = $Customer->getAllList();
        $departmentList = $Department->getComboList();
        $bankList = $Bank->getBankList();
        $branchList = $Bank->getBankBranchList();
        $staffList = $Staff->getStaffAndDepartmentList();

        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_id' => $departmentId,
        ]);

        //権限チェック
        $Authority = new Authority();

        $authClosing = $Authority->hasAuthority(Auth::id(), config('const.auth.close.closing'));
        $authInvoice = $Authority->hasAuthority(Auth::id(), config('const.auth.invoice.output'));


        return view('Payment.deposit-list')
            ->with('customerList', $customerList)
            ->with('bankList', $bankList)
            ->with('branchList', $branchList)
            ->with('departmentList', $departmentList)
            ->with('staffList', $staffList)
            ->with('initSearchParams', $initSearchParams)
            ->with('authClosing', $authClosing)
            ->with('authInvoice', $authInvoice);
    }

    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();

            // 一覧データ取得
            $Credited = new Credited();
            $CreditedDetail = new CreditedDetail();
            $creditedList = $Credited->getCreditedList($params);
            //数値列をintに
            for ($i = 0; $i < count($creditedList); $i++) {
                // 売掛残高 = 繰越金額＋売上高＋消費税＋税調整額
                $creditedList[$i]['carryforward_amount'] = (int)$creditedList[$i]['carryforward_amount'] + (int)$creditedList[$i]['sales'] + (int)$creditedList[$i]['consumption_tax_amount'] + (int)$creditedList[$i]['discount_amount'];
                // $creditedList[$i]['carryforward_amount'] = (int)$creditedList[$i]['carryforward_amount'];
                $creditedList[$i]['request_amount'] = (int)$creditedList[$i]['request_amount'];
                $creditedList[$i]['cash_total'] = (int)$creditedList[$i]['cash_total'];
                $creditedList[$i]['cheque_total'] = (int)$creditedList[$i]['cheque_total'];
                $creditedList[$i]['transfer_total'] = (int)$creditedList[$i]['transfer_total'];
                $creditedList[$i]['bills_total'] = (int)$creditedList[$i]['bills_total'];
                $creditedList[$i]['credited_total'] = (int)$creditedList[$i]['credited_total'];
                $creditedList[$i]['offset_others'] = (int)$creditedList[$i]['offset_others'];
                $creditedList[$i]['balance'] = (int)$creditedList[$i]['balance'];
                $creditedList[$i]['count'] = (int)$creditedList[$i]['count'];
            }

            $creditedDetailList = $CreditedDetail->getCreditedDetailList($params);
            $creditedDetailTable = [];
            foreach ($creditedDetailList as $row) {
                //チェックボックス列の型をbooleanに
                $row['checked'] = false;
                if ($row['credit_flg'] == 1) {
                    $row['credit_flg'] = true;
                } else {
                    $row['credit_flg'] = false;
                }
                if ($row['endorsement_flg'] == 1) {
                    $row['endorsement_flg'] = true;
                } else {
                    $row['endorsement_flg'] = false;
                }
                //数値列をintに
                $row['cash'] = (int)$row['cash'];
                $row['cheque'] = (int)$row['cheque'];
                $row['transfer'] = (int)$row['transfer'];
                $row['transfer_charges'] = (int)$row['transfer_charges'];
                $row['bills'] = (int)$row['bills'];
                $row['sales_promotion_expenses'] = (int)$row['sales_promotion_expenses'];
                $row['deposits'] = (int)$row['deposits'];
                $row['accounts_payed'] = (int)$row['accounts_payed'];
                $row['discount'] = (int)$row['discount'];
                $row['before_discount'] = (int)$row['before_discount'];
                $creditedDetailTable[$row['credited_id']][] = $row;
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json(['creditedList' => $creditedList, 'creditedDetailList' => $creditedDetailTable]);
    }


    /**
     * 新規入力
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function newInput(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Customer = new Customer();
        $NumberManage = new NumberManage();
        $Authority = new Authority();

        //権限チェック
        if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
            return \Response::json("新規入力する権限がありません。");
        }

        DB::beginTransaction();

        try {

            //得意先情報取得
            $cutomerList = $Customer->getCredited($params);
            $addParams['request_id'] = null;
            $addParams['request_no'] = null;
            $addParams['customer_id'] = $cutomerList['customer_id'];
            $addParams['customer_name'] = $cutomerList['customer_name'];
            $addParams['charge_department_id'] = $cutomerList['charge_department_id'];
            $addParams['charge_department_name'] = $cutomerList['department_name'];
            $addParams['charge_staff_id'] = $cutomerList['charge_staff_id'];
            $addParams['charge_staff_name'] = $cutomerList['staff_name'];
            $addParams['customer_closing_day'] = null;
            $addParams['expecteddeposit_at'] = null;
            $addParams['closing_day'] = $cutomerList['closing_day'];
            $addParams['collection_sight'] = $cutomerList['collection_sight'];
            $addParams['collection_day'] = $cutomerList['collection_day'];
            $addParams['collection_kbn'] = $cutomerList['collection_kbn'];
            $addParams['bill_min_price'] = $cutomerList['bill_min_price'];
            $addParams['bill_rate'] = $cutomerList['bill_rate'];
            $addParams['fee_kbn'] = $cutomerList['fee_kbn'];
            $addParams['tax_calc_kbn'] = $cutomerList['tax_calc_kbn'];
            $addParams['tax_rounding'] = $cutomerList['tax_rounding'];
            $addParams['offset_supplier_id'] = $cutomerList['offset_supplier_id'];
            $addParams['bill_sight'] = $cutomerList['bill_sight'];
            $addParams['receivable'] = null;
            $addParams['different_amount'] = null;
            $addParams['be_different_amount'] = null;
            $addParams['carryforward_amount'] = null;
            $addParams['total_sales'] = null;
            $addParams['request_amount'] = null;
            $addParams['cash'] = null;
            $addParams['cheque'] = null;
            $addParams['transfer'] = null;
            $addParams['bills'] = null;
            $addParams['total_deposit'] = null;
            $addParams['offset_ets'] = null;
            $addParams['status'] = 0;
            $addParams['status_confirm'] = null;
            $addParams['miscalculation_dep'] = null;
            $addParams['miscalculation_status'] = 0;
            $addParams['miscalculation_app_com'] = null;
            $addParams['miscalculation_app_info'] = null;
            $addParams['miscalculation_auth_com'] = null;
            $addParams['miscalculation_auth_info'] = null;

            $creditedId = $Credited->add($addParams);

            $detailParams['request_id'] = null;
            $detailParams['credited_id'] = $creditedId;
            $detailParams['credited_no'] = null;
            $detailParams['credited_date'] = null;
            $detailParams['actual_credited_date'] = null;
            $detailParams['financials_flg'] = 0;
            $detailParams['cash'] = null;
            $detailParams['credit_flg'] = null;
            $detailParams['cheque'] = null;
            $detailParams['receivingdept_id'] = null;
            $detailParams['transfer'] = null;
            $detailParams['transfer_charges'] = null;
            $detailParams['bills'] = null;
            $detailParams['bills_date'] = null;
            $detailParams['bank_code'] = null;
            $detailParams['bank_name'] = null;
            $detailParams['branch_code'] = null;
            $detailParams['branch_name'] = null;
            $detailParams['bills_no'] = null;
            $detailParams['endorsement_flg'] = null;
            $detailParams['sales_promotion_expenses'] = null;
            $detailParams['deposits'] = null;
            $detailParams['accounts_payed'] = null;
            $detailParams['discount'] = null;
            $detailParams['status'] = 0;
            $detailParams['status_dep'] = null;
            $detailParams['discount_app_id'] = null;
            $detailParams['discount_app_date'] = null;
            $creditedDetailId = $CreditedDetail->add($detailParams);


            $creditedParams['credited_id'] = $creditedId;
            $creditedList = $Credited->getCreditedList($creditedParams);
            $creditedDetailList = $CreditedDetail->getCreditedDetailList($creditedParams);

            $resultSts = ['creditedList' => $creditedList, 'creditedDetailList' => $creditedDetailList];
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 入金情報確定
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function comfirmDeposit(Request $request)
    {
        $resultSts = ['status' => false, 'message' => ''];
        $isInputCheckErr = false;
        $inputErrMessage = '';
        $dateErrMessage = '';

        // リクエストデータ取得
        $params = $request->request->all();
        $creditedList = $params['creditedList'];
        $creditedDetailList = $params['creditedDetailList'];
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Department = new Department();
        $NumberManage = new NumberManage();
        $Bank = new Bank();
        $LockManage = new LockManage();
        $Notice = new Notice();
        $Authority = new Authority();

        //権限チェック
        if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
            return \Response::json("入金情報を確定する権限がありません。");
        }

        DB::beginTransaction();

        try {

            //親明細ごとの繰り返し
            foreach ($creditedList as $mainRow) {

                //入金済み、繰越済以外
                if ($mainRow['status'] != 2 && $mainRow['status'] != 3) {

                    //入金のロック確認
                    $tableName = 't_credited';
                    $isLocked = $LockManage->isLocked($tableName, $mainRow['id']);
                    if ($isLocked) {
                        $result = config('message.error.getlock');
                        return \Response::json($result);
                    }

                    //入金の編集ロック
                    $LockManage->gainLock(Carbon::now(), self::SCREEN_NAME, $tableName, $mainRow['id']);

                    //入金テーブル更新用の値保持
                    $creditedParams['cash'] = 0;
                    $creditedParams['cheque'] = 0;
                    $creditedParams['transfer'] = 0;
                    $creditedParams['transfer_charges'] = 0;
                    $creditedParams['bills'] = 0;
                    $creditedParams['total_deposit'] = 0;
                    $creditedParams['offset_ets'] = 0;

                    //値引申請フラグ
                    $isDiscountRequest = false;
                    $departmentData = $Department->getById($mainRow['charge_department_id']);

                    //明細更新フラグ
                    $isDetailUpdated = false;

                    //子明細ごとの繰り返し
                    if (array_key_exists($mainRow['id'], $creditedDetailList)) {
                        foreach ($creditedDetailList[$mainRow['id']] as $detailRow) {
                            // 金額系カラムをNullから0へ変換
                            $detailRow['cash']                      = Common::nullorBlankToZero($detailRow['cash']);
                            $detailRow['cheque']                    = Common::nullorBlankToZero($detailRow['cheque']);
                            $detailRow['transfer']                  = Common::nullorBlankToZero($detailRow['transfer']);
                            $detailRow['transfer_charges']          = Common::nullorBlankToZero($detailRow['transfer_charges']);
                            $detailRow['bills']                     = Common::nullorBlankToZero($detailRow['bills']);
                            $detailRow['sales_promotion_expenses']  = Common::nullorBlankToZero($detailRow['sales_promotion_expenses']);
                            $detailRow['deposits']                  = Common::nullorBlankToZero($detailRow['deposits']);
                            $detailRow['accounts_payed']            = Common::nullorBlankToZero($detailRow['accounts_payed']);
                            $detailRow['discount']                  = Common::nullorBlankToZero($detailRow['discount']);

                            //入金更新用の値取得
                            if (array_key_exists('cash', $detailRow)) {
                                $creditedParams['cash'] +=  $detailRow['cash'];
                                $creditedParams['total_deposit'] += $detailRow['cash'];
                            }
                            if (array_key_exists('cheque', $detailRow)) {
                                $creditedParams['cheque'] += $detailRow['cheque'];
                                $creditedParams['total_deposit'] += $detailRow['cheque'];
                            }
                            if (array_key_exists('transfer', $detailRow)) {
                                $creditedParams['transfer'] += $detailRow['transfer'];
                                $creditedParams['total_deposit'] += $detailRow['transfer'];
                            }
                            if (array_key_exists('transfer_charges', $detailRow)) {
                                $creditedParams['transfer_charges'] += $detailRow['transfer_charges'];
                                $creditedParams['total_deposit'] += $detailRow['transfer_charges'];
                            }
                            if (array_key_exists('bills', $detailRow)) {
                                $creditedParams['bills'] += $detailRow['bills'];
                                $creditedParams['total_deposit'] += $detailRow['bills'];
                            }
                            if (array_key_exists('sales_promotion_expenses', $detailRow)) {
                                $creditedParams['offset_ets'] += $detailRow['sales_promotion_expenses'];
                            }
                            if (array_key_exists('deposits', $detailRow)) {
                                $creditedParams['offset_ets'] += $detailRow['deposits'];
                            }
                            if (array_key_exists('accounts_payed', $detailRow)) {
                                $creditedParams['offset_ets'] += $detailRow['accounts_payed'];
                            }
                            if (array_key_exists('discount', $detailRow)) {
                                $creditedParams['offset_ets'] += $detailRow['discount'];
                            }

                            if ($detailRow['checked']) {
                                $isDetailUpdated = true;
                                //入金明細更新は子明細にチェックが付いていて、親明細のステイタスが「入金済」「繰越済」以外のものが対象
                                if (
                                    array_key_exists('checked', $detailRow) && $detailRow['checked'] &&
                                    $mainRow['status'] != config('const.creditedStatus.val.payment') &&
                                    $mainRow['status'] != config('const.creditedStatus.val.transferred')
                                ) {

                                    //入金明細更新のパラメータ設定
                                    if (array_key_exists('credited_date', $detailRow)) {
                                        $CreditedDetailInfo['credited_date'] = $detailRow['credited_date'] == null ? null : str_replace('/', '-', substr($detailRow['credited_date'], 0, 10));
                                    } else {
                                        $CreditedDetailInfo['credited_date'] = null;
                                    }
                                    if (array_key_exists('actual_credited_date', $detailRow)) {
                                        $CreditedDetailInfo['actual_credited_date'] = $detailRow['actual_credited_date'] == null ? null : str_replace('/', '-', substr($detailRow['actual_credited_date'], 0, 10));
                                    } else {
                                        $CreditedDetailInfo['actual_credited_date'] = null;
                                    }

                                    $CreditedDetailInfo['financials_flg'] = 0;

                                    if (array_key_exists('cash', $detailRow)) {
                                        $CreditedDetailInfo['cash'] = $detailRow['cash'];
                                    } else {
                                        $CreditedDetailInfo['cash'] = null;
                                    }
                                    if (array_key_exists('credit_flg', $detailRow)) {
                                        $CreditedDetailInfo['credit_flg'] = $detailRow['credit_flg'];
                                    } else {
                                        $CreditedDetailInfo['credit_flg'] = null;
                                    }
                                    if (array_key_exists('cheque', $detailRow)) {
                                        $CreditedDetailInfo['cheque'] = $detailRow['cheque'];
                                    } else {
                                        $CreditedDetailInfo['cheque'] = null;
                                    }
                                    if (array_key_exists('receivingdept_id', $detailRow)) {
                                        $CreditedDetailInfo['receivingdept_id'] = $detailRow['receivingdept_id'];
                                    } else {
                                        $CreditedDetailInfo['receivingdept_id'] = null;
                                    }
                                    if (array_key_exists('transfer', $detailRow)) {
                                        $CreditedDetailInfo['transfer'] = $detailRow['transfer'];
                                    } else {
                                        $CreditedDetailInfo['transfer'] = null;
                                    }
                                    if (array_key_exists('transfer_charges', $detailRow)) {
                                        $CreditedDetailInfo['transfer_charges'] = $detailRow['transfer_charges'];
                                    } else {
                                        $CreditedDetailInfo['transfer_charges'] = null;
                                    }
                                    if (array_key_exists('bills', $detailRow)) {
                                        $CreditedDetailInfo['bills'] = $detailRow['bills'];
                                    } else {
                                        $CreditedDetailInfo['bills'] = null;
                                    }
                                    if (array_key_exists('bills_date', $detailRow)) {
                                        if ($detailRow['bills_date'] != null && $detailRow['bills_date'] != "") {
                                            $CreditedDetailInfo['bills_date'] = substr($detailRow['bills_date'], 0, 10);
                                        } else {
                                            $CreditedDetailInfo['bills_date'] = null;
                                        }
                                    } else {
                                        $CreditedDetailInfo['bills_date'] = null;
                                    }
                                    if (array_key_exists('bank_code', $detailRow)) {
                                        $CreditedDetailInfo['bank_code'] = $detailRow['bank_code'];
                                        $bankData = $Bank->getBankBranch(['bank_code' => $detailRow['bank_code']]);
                                        $bankName = $bankData['bank_name'];
                                    } else {
                                        $CreditedDetailInfo['bank_code'] = null;
                                        $bankName = null;
                                    }

                                    $CreditedDetailInfo['bank_name'] = $bankName;

                                    if (array_key_exists('branch_code', $detailRow)) {
                                        $CreditedDetailInfo['branch_code'] = $detailRow['branch_code'];
                                        $bankData = $Bank->getBankBranch(['bank_code' => $detailRow['bank_code'], 'branch_code' => $detailRow['branch_code']]);
                                        $branchName = $bankData['branch_name'];
                                    } else {
                                        $CreditedDetailInfo['branch_code'] = null;
                                        $branchName = null;
                                    }

                                    $CreditedDetailInfo['branch_name'] = $branchName;

                                    if (array_key_exists('bills_no', $detailRow)) {
                                        $CreditedDetailInfo['bills_no'] = $detailRow['bills_no'];
                                    } else {
                                        $CreditedDetailInfo['bills_no'] = null;
                                    }
                                    if (array_key_exists('endorsement_flg', $detailRow)) {
                                        $CreditedDetailInfo['endorsement_flg'] = $detailRow['endorsement_flg'];
                                    } else {
                                        $CreditedDetailInfo['endorsement_flg'] = null;
                                    }
                                    if (array_key_exists('sales_promotion_expenses', $detailRow)) {
                                        $CreditedDetailInfo['sales_promotion_expenses'] = $detailRow['sales_promotion_expenses'];
                                    } else {
                                        $CreditedDetailInfo['sales_promotion_expenses'] = null;
                                    }
                                    if (array_key_exists('deposits', $detailRow)) {
                                        $CreditedDetailInfo['deposits'] = $detailRow['deposits'];
                                    } else {
                                        $CreditedDetailInfo['deposits'] = null;
                                    }
                                    if (array_key_exists('accounts_payed', $detailRow)) {
                                        $CreditedDetailInfo['accounts_payed'] = $detailRow['accounts_payed'];
                                    } else {
                                        $CreditedDetailInfo['accounts_payed'] = null;
                                    }
                                    if (array_key_exists('discount', $detailRow)) {
                                        $CreditedDetailInfo['discount'] = $detailRow['discount'];
                                    } else {
                                        $CreditedDetailInfo['discount'] = null;
                                    }
                                    if (array_key_exists('status', $detailRow)) {
                                        $CreditedDetailInfo['status'] = $detailRow['status'];
                                    } else {
                                        $CreditedDetailInfo['status'] = 0;
                                    }

                                    //値引申請
                                    //値引額が入力されている場合、申請を行う
                                    if (
                                        array_key_exists('discount', $detailRow)
                                        && $detailRow['discount'] != null
                                        && $detailRow['discount'] != 0
                                        && (!array_key_exists('before_discount', $detailRow)
                                            || $detailRow['discount'] != $detailRow['before_discount'])
                                    ) {
                                        $isDiscountRequest = true;
                                        $CreditedDetailInfo['discount_app_id'] = Auth::user()->id;
                                        $CreditedDetailInfo['discount_app_date'] = Carbon::now();
                                        $CreditedDetailInfo['status'] = 1;
                                        $CreditedDetailInfo['status_dep'] = $mainRow['charge_department_id'];
                                    } else {
                                        $CreditedDetailInfo['status_dep'] = null;
                                        $CreditedDetailInfo['discount_app_id'] = null;
                                        $CreditedDetailInfo['discount_app_date'] = null;
                                    }

                                    //入金明細の更新
                                    $CreditedDetailInfo['request_id'] = $mainRow['request_id'];
                                    $CreditedDetailInfo['credited_id'] = $mainRow['id'];
                                    if (array_key_exists('id', $detailRow) && $detailRow['id'] != null) {
                                        $CreditedDetailInfo['id'] = $detailRow['id'];
                                        if (!array_key_exists('credited_no', $detailRow) || $detailRow['credited_no'] == null || $detailRow['credited_no'] == "") {
                                            $CreditedDetailInfo['credited_no'] =  $NumberManage->getSeqNo(config('const.number_manage.kbn.credited'), Carbon::today()->format('Ym'));
                                        } else {
                                            $CreditedDetailInfo['credited_no'] = $detailRow['credited_no'];
                                        }
                                        $CreditedDetailId = $CreditedDetail->updateById($CreditedDetailInfo);
                                    }
                                    //入金明細の登録
                                    else {
                                        $CreditedDetailInfo['credited_no'] =  $NumberManage->getSeqNo(config('const.number_manage.kbn.credited'), Carbon::today()->format('Ym'));
                                        $CreditedDetailId = $CreditedDetail->add($CreditedDetailInfo);
                                    }
                                }
                            }
                        }
                    }

                    if ($isDetailUpdated) {

                        //入金テーブルの更新***
                        //tmpA = 「違算＋売上合計」
                        $tmpA = 0;
                        if ($mainRow['total_sales'] != null) {
                            $tmpA += (int)$mainRow['total_sales'];
                        }
                        //tmpB = 「入金合計+相殺その他」
                        $tmpB = $creditedParams['total_deposit'] + $creditedParams['offset_ets'];

                        //入金一覧で生成したデータは差額0に⇒DEV-438によりなし
                        if ($mainRow['request_no'] == null) {
                               $mainRow['total_sales']  = $mainRow['total_sales'] == null ? 0:$mainRow['total_sales'];
                            // $tmpA   = $tmpB;
                            // $creditedParams['request_amount'] =  $tmpB;

                        } else {
                            // $creditedParams['request_amount'] = $mainRow['request_amount'];
                        }
                        $creditedParams['request_amount'] = $mainRow['request_amount'];

                        //ステータス
                        $creditedParams['different_amount'] =  $mainRow['different_amount'];
                        if ($tmpA == $tmpB) {
                            
                            // 入金明細の実入金日、入金処理日チェック
                            $isDateError = self::isDateErrCheck($mainRow, $creditedDetailList[$mainRow['id']]);
                            if ($isDateError['input']) {
                                if (Common::nullToBlank($inputErrMessage) == '') {
                                    $inputErrMessage = config('message.error.deposit.input_credited_date'). '\n';
                                }
                                $inputErrMessage .= '・'. $mainRow['customer_name']. '\n';
                                $isInputCheckErr = true;
                            }
                            if ($isDateError['before_date']) {
                                if (Common::nullToBlank($dateErrMessage) == '') {
                                    $inputErrMessage = config('message.error.deposit.confirm_credited_date'). '\n';
                                }
                                $dateErrMessage .= '・'. $mainRow['customer_name']. '\n';
                                $isInputCheckErr = true;
                            }

                            $creditedParams['status'] = config('const.creditedStatus.val.payment');
                            $creditedParams['status_confirm'] = Carbon::now();
                        } else {
                            // if ($mainRow['request_no'] == null) {
                            //     $creditedParams['different_amount'] =
                            //     (
                            //          0 - ($creditedParams['total_deposit']==null?null:$creditedParams['total_deposit'] )
                            //          - ($creditedParams['offset_ets']==null?null:$creditedParams['offset_ets'] ) 
                            //          - ($mainRow['total_sales']==null?null:$mainRow['total_sales']) 
                            //     );
                            //     $creditedParams['status'] = config('const.creditedStatus.val.transferred');
                            // } else {
                            $creditedParams['status'] = config('const.creditedStatus.val.miscalculation');
                            // }
                            $creditedParams['status_confirm'] = null;
                        }
                        $creditedParams['id'] = $mainRow['id'];
                        $creditedParams['total_sales'] = $mainRow['total_sales'];
                        //新規入力の場合、請求金額を設定
                        if ($mainRow['request_no'] == null) {
                        }
                        $saveResult = $Credited->updateDepositConfirm($creditedParams);

                        //担当者通知***
                        if ($isDiscountRequest) {
                            $noticeParams['notice_flg'] = 0;
                            $noticeParams['redirect_url'] = '/deposit-list?request_no=' . $mainRow['request_no'] . '&customer_id=' . $mainRow['customer_id'];
                            $noticeParams['staff_id'] = $departmentData['chief_staff_id'];
                            $noticeParams['content'] = $mainRow['customer_name'] . '：　入金明細で、値引き申請があります。確認してください。';
                            $saveResult = $Notice->add($noticeParams);
                        }
                    }

                    //入金ロック解除
                    $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, [$mainRow['id']], Auth::user()->id);
                }
            }

            if ($isInputCheckErr) {
                // エラーメッセージ
                if (Common::nullToBlank($inputErrMessage) != '') {
                    $resultSts['message'] .= $inputErrMessage;
                }
                if (Common::nullToBlank($dateErrMessage) != '') {
                    $resultSts['message'] .= $dateErrMessage;
                }
                throw new \Exception();
            }

            $resultSts['status'] = true;
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts['status'] = false;
            if ($resultSts['message'] == '') {
                $resultSts['message'] = config('message.error.error');
            }
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 入金明細の日付入力チェック
     *
     * @param [type] $mainRow       親明細
     * @param [type] $detailList    子明細リスト
     * @return boolean  'input': true->未入力　'before_date'： true->売上期間開始日よりも前の日付
     */
    public function isDateErrCheck($mainRow, $detailList) 
    {
        $isErrList = ['input' => false, 'before_date' => false];
        $Requests = new Requests();

        try {
            $requestDay = null;

            //入金済み、繰越済以外
            if ($mainRow['status'] != 2 && $mainRow['status'] != 3) {
            
                // 得意先に対する最新の請求を取得する
                $requestList = $Requests->getByCustomerId($mainRow['customer_id']);

                if (count($requestList) > 0) {
                    $status = [config('const.requestStatus.val.unprocessed'), config('const.requestStatus.val.complete')];
                    $requestData = collect($requestList)->whereIn('status', $status)->first();

                    if ($requestData != null) {
                        $requestDay = Carbon::parse($requestData['request_s_day'])->format('Y/m/d');
                    } else if ($requestData == null) {
                        // 進行中の請求データが存在しない
                        // 最新の請求終了日を基準にする
                        $requestDay = Carbon::parse(collect($requestList)->max('request_e_day'))->addDay(1)->format('Y/m/d');
                    }
                }
                
                foreach ($detailList as $i => $row) {
                    // 入力チェック
                    if (Common::nullToBlank($row['credited_date']) == '' || Common::nullToBlank($row['actual_credited_date']) == '') {
                        // 未入力
                        $isErrList['input'] = true;
                    }

                    if ($requestDay != null && !$isErrList['input']) {
                        if ($row['credited_date'] < $requestDay) {
                            // ※請求データが存在しない場合はチェックしない
                            // 売上期間開始日よりも前の日付
                            $isErrList['before_date'] = true;
                        }
                    }
                }
            }

        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return $isErrList;
    }

    /**
     * 違算処理前のチェック処理
     *
     * @param Request $request
     * @return void
     */
    public function confirmMiscalculation(Request $request)
    {
        $resultSts = ['status' => false, 'message' => ''];
        //違算処理用定数
        $APPROVAL = "APPROVAL";

        // リクエストデータ取得
        $params = $request->request->all();
        $mainRow = $params['mainRow'];
        $detailRows = $params['detailRows'];
        $btnKind = $params['btnKind'];

        try {
            // エラーチェック
            if ($btnKind == $APPROVAL) {
                $isDateError = self::isDateErrCheck($mainRow, $detailRows);
            }

            $resultSts['status'] = true;
            if ($isDateError['before_date']) {
                $resultSts['message'] = config('message.error.deposit.approval_credited_date');
            }

            if ($resultSts['message'] == "") {
                $resultSts['message'] = config('message.confirm.deposit.miscalculation_approval');
            }
        } catch (\Exception $e) {
            $resultSts['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return $resultSts;
    }

    /**
     * 違算処理
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function miscalculation(Request $request)
    {
        $resultSts = false;
        //違算処理用定数
        $APPLYING = "APPLYING";
        $APPROVAL = "APPROVAL";
        $DENIAL = "DENIAL";
        $CANCEL = "CANCEL";

        // リクエストデータ取得
        $params = $request->request->all();
        $mainRow = $params['mainRow'];
        $detailRows = $params['detailRows'];
        $btnKind = $params['btnKind'];
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Department = new Department();
        $Staff = new Staff();
        $Notice = new Notice();
        //入金テーブル更新用の値保持
        $creditedParams['cash'] = 0;
        $creditedParams['cheque'] = 0;
        $creditedParams['transfer'] = 0;
        $creditedParams['transfer_charges'] = 0;
        $creditedParams['bills'] = 0;
        $creditedParams['total_deposit'] = 0;
        $creditedParams['offset_ets'] = 0;
        $Authority = new Authority();

        //権限チェック（申請者の場合）
        if ($btnKind == "APPLYING" || $btnKind == "CANCEL") {
            // if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
            //     return \Response::json("違算処理をする権限がありません。");
            // }
        } else {
            $authStaffId = $Department->getByChief($mainRow['miscalculation_dep'])['chief_staff_id'];
            if ($authStaffId != null && $authStaffId != Auth::user()->id) {
                return \Response::json("違算処理をする権限がありません。");
            }
        }

        DB::beginTransaction();

        try {

            //子明細ごとの繰り返し

            foreach ($detailRows as $detailRow) {

                //入金更新用の値取得
                if (array_key_exists('cash', $detailRow)) {
                    $creditedParams['cash'] +=  $detailRow['cash'];
                    $creditedParams['total_deposit'] += $detailRow['cash'];
                }
                if (array_key_exists('cheque', $detailRow)) {
                    $creditedParams['cheque'] += $detailRow['cheque'];
                    $creditedParams['total_deposit'] += $detailRow['cheque'];
                }
                if (array_key_exists('transfer', $detailRow)) {
                    $creditedParams['transfer'] += $detailRow['transfer'];
                    $creditedParams['total_deposit'] += $detailRow['transfer'];
                }
                if (array_key_exists('transfer_charges', $detailRow)) {
                    $creditedParams['transfer_charges'] += $detailRow['transfer_charges'];
                    $creditedParams['total_deposit'] += $detailRow['transfer_charges'];
                }

                if (array_key_exists('bills', $detailRow)) {
                    $creditedParams['bills'] += $detailRow['bills'];
                    $creditedParams['total_deposit'] += $detailRow['bills'];
                }
                if (array_key_exists('sales_promotion_expenses', $detailRow)) {
                    $creditedParams['offset_ets'] += $detailRow['sales_promotion_expenses'];
                }
                if (array_key_exists('deposits', $detailRow)) {
                    $creditedParams['offset_ets'] += $detailRow['deposits'];
                }
                if (array_key_exists('accounts_payed', $detailRow)) {
                    $creditedParams['offset_ets'] += $detailRow['accounts_payed'];
                }
                if (array_key_exists('discount', $detailRow)) {
                    $creditedParams['offset_ets'] += $detailRow['discount'];
                }
            }

            //入金テーブルの更新***
            //集計した各種金額を反映
            $mainRow['cash'] = $creditedParams['cash'];
            $mainRow['cheque'] = $creditedParams['cheque'];
            $mainRow['transfer'] = $creditedParams['transfer'] + $creditedParams['transfer_charges'];
            $mainRow['bills'] = $creditedParams['bills'];
            $mainRow['total_deposit'] = $creditedParams['total_deposit'];
            $mainRow['offset_ets'] = $creditedParams['offset_ets'];

            switch ($btnKind) {
                    //申請
                case $APPLYING:

                    $mainRow['miscalculation_status'] =  1;
                    $mainRow['miscalculation_app_info'] = Carbon::Now()->toDateTimeString() . '：' . Auth::user()->staff_name;
                    $mainRow['miscalculation_app_id'] = Auth::user()->id;
                    $mainRow['miscalculation_app_date'] = Carbon::Now();
                    $mainRow['miscalculation_dep'] = $mainRow['charge_department_id'];

                    $chief_staff_id = $Department->getByChief($mainRow['charge_department_id'])['chief_staff_id'];
                    $noticeParams['staff_id'] = $chief_staff_id;
                    $noticeParams['content'] = $mainRow['customer_name'] . '：　入金一覧で、違算申請があります。確認してください。';

                    break;
                    //承認
                case $APPROVAL:
                    // 1段階承認
                    $mainRow['status_confirm'] =   Carbon::now();
                    $mainRow['status'] =  config('const.creditedStatus.val.transferred');
                    $mainRow['miscalculation_dep'] = null;
                    $mainRow['miscalculation_status'] = 2;
                    $mainRow['different_amount'] = $mainRow['total_sales'] - ($mainRow['total_deposit'] + $mainRow['offset_ets']);
                    if ($mainRow['miscalculation_auth_info'] != null && $mainRow['miscalculation_auth_info'] != "") {
                        $mainRow['miscalculation_auth_info'] .= ",";
                    }
                    $mainRow['miscalculation_auth_info'] .= Carbon::Now()->toDateTimeString() . '：' . Auth::user()->staff_name . ',';
                    $noticeParams['staff_id'] = $mainRow['miscalculation_app_id'];
                    $noticeParams['content'] = $mainRow['customer_name'] . '：　入金一覧で、違算申請が承認されました。確認してください。';

                    //違算差額設定 $mainRow['total_deposit'] = $creditedParams['total_deposit'];
                    // $mainRow['different_amount'] = abs($mainRow['request_amount'] - $mainRow['total_deposit'] - $mainRow['offset_ets']);
                    $mainRow['miscalculation_auth_id'] = Auth::user()->id;
                    $mainRow['miscalculation_auth_date'] = Carbon::now();
                    break;
                    //否認
                case $DENIAL:
                    $mainRow['miscalculation_status'] = 0;
                    $mainRow['miscalculation_dep'] = null;
                    $noticeParams['staff_id'] = $mainRow['miscalculation_app_id'];
                    $noticeParams['content'] = $mainRow['customer_name'] . '：　入金一覧で、違算申請が否認されました。確認してください。';
                    break;
                    //取り消し
                case $CANCEL:
                    $mainRow['miscalculation_status'] = 0;
                    $mainRow['miscalculation_dep'] = null;
                    $mainRow['miscalculation_app_com'] = null;
                    $mainRow['miscalculation_app_info'] = null;
                    $mainRow['miscalculation_app_id'] = null;
                    $mainRow['miscalculation_app_date'] = null;

                    $mainRow['miscalculation_auth_com'] = null;
                    $mainRow['miscalculation_auth_info'] = null;
                    $mainRow['miscalculation_auth_id'] = null;
                    $mainRow['miscalculation_auth_date'] = null;
                    break;
            }

            $saveResult = $Credited->updateMiscalculation($mainRow);

            //担当者通知***
            if ($btnKind != $CANCEL) {
                $noticeParams['notice_flg'] = 0;
                $noticeParams['redirect_url'] = '/deposit-list?request_no=' . $mainRow['request_no'] . '&customer_id=' . $mainRow['customer_id'];

                $saveResult = $Notice->add($noticeParams);
            }

            $resultSts = true;
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 値引承認
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function discount(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $mainRow = $params['mainRow'];
        $detailRow = $params['detailRow'];
        $creditedDetailList = $params['creditedDetailList'];
        $CreditedDetail = new CreditedDetail();
        $Credited = new Credited();
        $Department = new Department();
        $Staff = new Staff();
        $Notice = new Notice();

        DB::beginTransaction();
        try {
            //権限チェック
            $authStaffId = $Department->getByChief($detailRow['status_dep'])['chief_staff_id'];
            if ($authStaffId != null && $authStaffId != Auth::user()->id) {
                return \Response::json("値引申請を承認する権限がありません。");
            }

            //入金テーブル更新用の値保持
            $creditedParams['cash'] = 0;
            $creditedParams['cheque'] = 0;
            $creditedParams['transfer'] = 0;
            $creditedParams['transfer_charges'] = 0;
            $creditedParams['bills'] = 0;
            $creditedParams['total_deposit'] = 0;
            $creditedParams['offset_ets'] = 0;

            //子明細ごとの繰り返し
            if (array_key_exists($mainRow['id'], $creditedDetailList)) {
                foreach ($creditedDetailList[$mainRow['id']] as $row) {
                    //入金更新用の値取得
                    if (array_key_exists('cash', $row)) {
                        $creditedParams['cash'] +=  $row['cash'];
                        $creditedParams['total_deposit'] += $row['cash'];
                    }
                    if (array_key_exists('cheque', $row)) {
                        $creditedParams['cheque'] += $row['cheque'];
                        $creditedParams['total_deposit'] += $row['cheque'];
                    }
                    if (array_key_exists('transfer', $row)) {
                        $creditedParams['transfer'] += $row['transfer'];
                        $creditedParams['total_deposit'] += $row['transfer'];
                    }
                    if (array_key_exists('transfer_charges', $row)) {
                        $creditedParams['transfer_charges'] += $row['transfer_charges'];
                        $creditedParams['total_deposit'] += $row['transfer_charges'];
                    }
                    if (array_key_exists('bills', $row)) {
                        $creditedParams['bills'] += $row['bills'];
                        $creditedParams['total_deposit'] += $row['bills'];
                    }
                    if (array_key_exists('sales_promotion_expenses', $row)) {
                        $creditedParams['offset_ets'] += $row['sales_promotion_expenses'];
                    }
                    if (array_key_exists('deposits', $row)) {
                        $creditedParams['offset_ets'] += $row['deposits'];
                    }
                    if (array_key_exists('accounts_payed', $row)) {
                        $creditedParams['offset_ets'] += $row['accounts_payed'];
                    }
                    if (array_key_exists('discount', $row)) {
                        $creditedParams['offset_ets'] += $row['discount'];
                    }
                }
            }

            //入金テーブルの更新***
            $updateStatus = [];
            //tmpA = 「違算＋売上合計」
            $tmpA = 0;
            if ($mainRow['total_sales'] != null) {
                $tmpA += (int)$mainRow['total_sales'];
            }
            //tmpB = 「入金合計+相殺その他」
            $tmpB = $creditedParams['total_deposit'] + $creditedParams['offset_ets'];

            //入金一覧で生成したデータは差額0に⇒DEV-438によりなし
            if ($mainRow['request_no'] == null) {
                $mainRow['total_sales']  = $mainRow['total_sales'] == null ? 0:$mainRow['total_sales'];
            }

            $creditedParams['request_amount'] = $mainRow['request_amount'];

            //ステータス
            $creditedParams['different_amount'] =  $mainRow['different_amount'];
            if ($tmpA == $tmpB) {
                $updateStatus['status'] = config('const.creditedStatus.val.payment');
            } else {
                // if ($mainRow['request_no'] == null) {
                //     $creditedParams['different_amount'] =
                //     (
                //             0 - ($creditedParams['total_deposit']==null? 0 : $creditedParams['total_deposit'] )
                //             - ($creditedParams['offset_ets']==null? 0 : $creditedParams['offset_ets'] ) 
                //             - ($mainRow['total_sales']==null? 0 : $mainRow['total_sales']) 
                //     );
                //     $updateStatus['status'] = config('const.creditedStatus.val.transferred');
                // } else {
                $updateStatus['status'] = config('const.creditedStatus.val.miscalculation');
                // }
            }
            $updateStatus['id'] = $mainRow['id'];
            

            //入金明細テーブルの更新***
            // 1段階承認
            $detailRow['status_dep'] = null;
            $detailRow['status'] = 2;

            $detailRow['discount_auth_id'] = Auth::user()->id;
            $detailRow['discount_auth_date'] = Carbon::now();

            $saveResult = $CreditedDetail->updateDiscountById($detailRow);

            //担当者通知***
            $noticeParams['notice_flg'] = 0;
            $noticeParams['redirect_url'] = '/deposit-list?request_no=' . $detailRow['request_no'] . '&customer_id=' . $detailRow['customer_id'];
            $noticeParams['staff_id'] = $detailRow['discount_app_id'];
            $noticeParams['content'] = $detailRow['customer_name'] . '：　入金一覧で、値引きが承認されました。確認してください。';

            //親明細更新
            // if ($Credited->isCredited($detailRow['credited_id'])) {
            //     $Credited->updateDiscountFinish($detailRow['credited_id']);
            // }
            $Credited->updateStatus($updateStatus);
            
            $saveResult = $Notice->add($noticeParams);

            $resultSts = true;
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 値引否認
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function discountCancel(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $detailRow = $params['detailRow'];
        $CreditedDetail = new CreditedDetail();
        $Department = new Department();
        $Staff = new Staff();
        $Notice = new Notice();

        try {
            //権限チェック
            $authStaffId = $Department->getByChief($detailRow['status_dep'])['chief_staff_id'];
            if ($authStaffId != null && $authStaffId != Auth::user()->id) {
                return \Response::json("値引申請を否認する権限がありません。");
            }

            DB::beginTransaction();


            //入金明細テーブルの更新***
            $noticeParams['discount'] =  $detailRow['discount'];

            $detailRow['status_dep'] = null;
            $detailRow['status'] = 0;
            $detailRow['discount'] = 0;

            $saveResult = $CreditedDetail->updateDiscountById($detailRow);

            //担当者通知***
            $noticeParams['notice_flg'] = 0;
            $noticeParams['redirect_url'] = '/deposit-list?request_no=' . $detailRow['request_no'] . '&customer_id=' . $detailRow['customer_id'];
            $noticeParams['staff_id'] = $detailRow['discount_app_id'];
            $noticeParams['content'] = $detailRow['customer_name'] . '：　入金一覧で、値引きが否認されました。確認してください。(変更前値引き：' . $noticeParams['discount'] . ')';

            $saveResult = $Notice->add($noticeParams);

            $resultSts = true;
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * ファイル取込
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function importFile(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $file = $params['file'];
        $fileName = $params['fileName'];
        $fileDate = $params['fileDate'];
        $crc = $params['crc'];
        $CreditedCorrelate = new CreditedCorrelate();
        $Credited = new Credited();
        $Authority = new Authority();
        $Staff = new Staff();
        $ImportHistory = new ImportHistory();

        //CSVファイルのカラムインデックス（使用分のみ）
        $CSV_IDX_TRADE_NAME = 12;
        $CSV_IDX_HANDLE_MONTH   = 15;   // 取扱日付月
        $CSV_IDX_HANDLE_DAY     = 16;   // 取扱日付日
        $CSV_IDX_TRADE_MONTH = 17;
        $CSV_IDX_TRADE_DAY = 18;
        $CSV_IDX_AMOUNT = 19;
        $CSV_IDX_CUSTOMER_NAME = 21;

        //CSVファイルのデータ開始行インデックス
        $CSV_START_ROW = 4;

        //CSVファイルのカラム数
        $CSV_COLUMN = 30;

        //CSVファイルの対象取引名
        $CSV_TARGET_TRADE_COLUMN_NAME = "取引名";
        $CSV_TARGET_TRADE_NAME = "振込入金";

        try {

            //権限チェック
            if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
                return \Response::json("振込データを取込する権限がありません。");
            }

            //CSVフォーマットチェック
            if ($file == null || count($file) < $CSV_START_ROW || count($file[$CSV_START_ROW]) < $CSV_COLUMN) {
                return \Response::json("選択されたファイルの形式が正しくありません。再度選択してください。");
            }

            //インポート履歴から同ファイル読込チェック
            $historyList = $ImportHistory->getByCrc($crc, $fileName);
            if ($historyList != null) {
                return \Response::json("既に取込済みのファイルが選択されています。");
            }

            //対象データの取り出し
            $index = -1;
            $dataList = [];
            foreach ($file as $row) {
                $index++;
                if ($index < $CSV_START_ROW || count($row) < $CSV_COLUMN || $row[$CSV_IDX_TRADE_NAME] != $CSV_TARGET_TRADE_NAME) {
                    continue;
                } else {

                    //入金日作成
                    $year = Carbon::now()->format('Y');
                    $month = Carbon::now()->format('m');
                    /** 入金月 */
                    // 起算日月が未入力の場合は取扱日付月を使う
                    $creditedMonth = $row[$CSV_IDX_TRADE_MONTH] != null ? $row[$CSV_IDX_TRADE_MONTH] : $row[$CSV_IDX_HANDLE_MONTH];
                    /** 入金日 */
                    // 起算日日が未入力の場合は取扱日付日を使う
                    $creditedDay = $row[$CSV_IDX_TRADE_DAY] != null ? $row[$CSV_IDX_TRADE_DAY] : $row[$CSV_IDX_HANDLE_DAY];
                    
                    if (intval($month) == 1 && intval($creditedMonth) == 12) {
                        $year = intval($year) - 1;
                    }
                    $creditedDate = $year . '-' . str_pad($creditedMonth, 2, 0, STR_PAD_LEFT) . '-' . str_pad($creditedDay, 2, 0, STR_PAD_LEFT);

                    $dataList[] = [
                        'trade_name' => $row[$CSV_IDX_TRADE_NAME],
                        'credited_date' => $creditedDate,
                        'amount' => $row[$CSV_IDX_AMOUNT],
                        'customer_name_kana' => mb_convert_kana($row[$CSV_IDX_CUSTOMER_NAME], "k"),
                        'customer_name' => null,
                        'customer_id' => null,
                        'request_id' => null,
                        'request_no' => null,
                        'credited_id' => null,
                        'total_sales' => null,
                        'transfer_amount' => null,
                        'deposit_difference' => null,
                        'select' => 1,
                        'select_before' => 1,
                        'out' => 1,
                        'check' => false,
                    ];
                }
            }

            //CSVフォーマットチェック
            if (count($dataList) <= 0) {
                return \Response::json("選択されたファイルの形式が正しくありません。再度選択してください。");
            }

            $creditedList = $Credited->getCorrelate();
            $correlateList = $CreditedCorrelate->getData(['customer_id' => null]);
            $customer_name = array_column($dataList, 'customer_name');
            $credited_date = array_column($dataList, 'credited_date');

            array_multisort($customer_name, SORT_ASC, $credited_date, SORT_ASC, $dataList);

            //紐づけリスト作成
            $checkCustomerList = [];
            for ($i = 0; $i < count($dataList); $i++) {
                foreach ($correlateList as $correlate) {
                    if ($dataList[$i]['customer_name_kana'] == $correlate['customer_bank']) {
                        $dataList[$i]['customer_name'] = '除外';
                        $dataList[$i]['select'] = 2;
                        $dataList[$i]['select_before'] = 2;
                        $dataList[$i]['out'] = 2;
                    }
                }

                if ($dataList[$i]['out'] != 2) {

                    foreach ($creditedList as $credited) {
                        // if ($dataList[$i]['customer_name_kana']  == $credited['customer_bank'] && ($dataList[$i]['amount'] == $credited['total_sales'] || (1000 > ($dataList[$i]['amount'] - $credited['total_sales']) && ($dataList[$i]['amount'] - $credited['total_sales']) > 0))) {
                        if ($dataList[$i]['customer_name_kana']  == $credited['customer_bank'] && $credited['radio_button'] != 1 && $dataList[$i]['select'] != 0) {

                            $dataList[$i]['customer_name'] = $credited['customer_name'];
                            $dataList[$i]['customer_id'] = $credited['customer_id'];
                            $dataList[$i]['request_id'] = $credited['request_id'];
                            $dataList[$i]['request_no'] = $credited['request_no'];
                            $dataList[$i]['credited_id'] = $credited['credited_id'];
                            $dataList[$i]['total_sales'] = $credited['total_sales'];

                            $credited['radio_button'] = 1;
                            $checkCustomerList[] = $credited['request_no'];

                            if (1000 >= ($credited['total_sales'] - $dataList[$i]['amount']) && ($credited['total_sales'] - $dataList[$i]['amount']) > 0) {
                                $dataList[$i]['transfer_amount'] = $credited['total_sales'] - $dataList[$i]['amount'];
                            } else {
                                $dataList[$i]['transfer_amount'] = 0;
                            }
                            $dataList[$i]['deposit_difference'] = $credited['total_sales'] - $dataList[$i]['amount'] - $dataList[$i]['transfer_amount'];

                            $dataList[$i]['select'] = 0;
                            $dataList[$i]['select_before'] = 0;
                            $dataList[$i]['out'] = 0;
                        }
                    }
                }
            }

            $creditedList = $Credited->getCorrelateCustomer();
            foreach ($creditedList as $credited) {
                foreach ($checkCustomerList as $request_no) {
                    if ($credited['request_no'] === $request_no) {
                        $credited['radio_button'] = 1;
                    }
                }
            }

            $rtnList = ['csvList' => $dataList, 'creditedList' => $creditedList];
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($rtnList);
    }
    /**
     * 取込完了
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function completeImport(Request $request)
    {

        // リクエストデータ取得
        $params = $request->request->all();
        $data = $params['data'];
        $list = $data['csvList'];
        $fileName = $data['fileName'];
        $fileDate = $data['fileDate'];
        $crc = $data['crc'];
        $result = true;


        $CreditedDetail = new CreditedDetail();
        $CreditedCorrelate = new CreditedCorrelate();
        $Credited = new Credited();
        $Authority = new Authority();
        $ImportHistory = new ImportHistory();

        if ($fileName != null && $list != null) {

            try {

                DB::beginTransaction();


                $addIdList = [];
                foreach ($list as $row) {

                    //入金用得意先紐付データ削除
                    if ($row['check'] == true) {
                        $CreditedCorrelate->deleteByBank($row['customer_name_kana']);
                    }

                    //紐づけされた振込データのみ入金明細データ作成
                    if ($row['select'] == 0) {
                        $CreditedDetailParams['request_id'] = $row['request_id'];
                        $CreditedDetailParams['credited_id'] = $row['credited_id'];
                        $CreditedDetailParams['credited_date'] = Carbon::parse($row['credited_date'])->format('Y/m/d');
                        $CreditedDetailParams['actual_credited_date'] = Carbon::parse($row['credited_date'])->format('Y/m/d');
                        $CreditedDetailParams['financials_flg'] = 0;
                        $CreditedDetailParams['transfer'] = $row['amount'];
                        $CreditedDetailParams['transfer_charges'] = $row['transfer_amount'];
                        $CreditedDetailParams['status'] = 0;
                        $addIdList[] = $CreditedDetail->addCorrelate($CreditedDetailParams);

                        //入金用得意先紐付データ作成
                        if ($row['check'] == true) {

                            $CreditedCorrelateParams['customer_id'] = $row['customer_id'];
                            $CreditedCorrelateParams['customer_name'] = $row['customer_name'];
                            $CreditedCorrelateParams['customer_bank'] = $row['customer_name_kana'];
                            $data = $CreditedCorrelate->getData($CreditedCorrelateParams);
                            if ($data == null || count($data) <= 0) {
                                $CreditedCorrelate->add($CreditedCorrelateParams);
                            }
                        }
                    } else if ($row['check'] == true && $row['select'] == 2) {
                        //入金用得意先紐付データ作成
                        if ($row['select_before'] != 2) {
                            $CreditedCorrelateParams['customer_id'] = null;
                            $CreditedCorrelateParams['customer_name'] = null;
                            $CreditedCorrelateParams['customer_bank'] = $row['customer_name_kana'];
                            $data = $CreditedCorrelate->getData($CreditedCorrelateParams);
                            if ($data == null || count($data) <= 0) {
                                $CreditedCorrelate->add($CreditedCorrelateParams);
                            }
                        }
                    }
                }

                //入金用取込ファイル履歴データ作成
                $ImportHistoryParams['importfile_name'] = $fileName;
                $ImportHistoryParams['importfile_create'] = $fileDate;
                $ImportHistoryParams['crc'] = $crc;
                $ImportHistory->add($ImportHistoryParams);

                //追加した入金明細を取得
                if (count($addIdList) > 0) {
                    $result = $CreditedDetail->getCreditedDetailListByIds($addIdList);
                    foreach ($result as $row) {
                        $row['checked'] = true;
                        $row['credit_flg'] = false;
                        $row['endorsement_flg'] = false;
                    }
                }

                DB::commit();

                Session::flash('flash_success', config('message.success.save'));
            } catch (\Exception $e) {
                DB::rollBack();
                $result = false;
                Log::error($e);
                Session::flash('flash_error', config('message.error.error'));
            }
        }
        return \Response::json($result);
    }

    /**
     * 明細削除
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = true;
        // リクエストデータ取得
        $params = $request->request->all();
        $mainRow = $params['mainRow'];
        $deleteIdList = $params['deleteIdList'];
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Customer = new Customer();
        $NumberManage = new NumberManage();
        $Authority = new Authority();

        //権限チェック
        if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
            return \Response::json("明細を削除する権限がありません。");
        }

        DB::beginTransaction();

        try {

            foreach ($deleteIdList as $id) {
                $CreditedDetail->deleteById($id);
            }

            //入金に紐づく明細リスト取得
            $list = $CreditedDetail->getlListByCreditedId($mainRow['id']);

            //請求番号がない、かつ子明細が全て削除された場合に親明細の削除
            if ($mainRow['request_no'] == null || $mainRow['request_no'] == '') {
                if ($list == null || count($list) <= 0) {
                    $Credited->deleteById($mainRow['id']);
                }
            }
            //請求から生成された場合は親明細更新
            else {
                if ($list == null || count($list) <= 0) {
                    $Credited->clearById($mainRow['id']);
                }
            }

            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 明細追加
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function add(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        $mainRow = $params['mainRow'];
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Customer = new Customer();
        $NumberManage = new NumberManage();
        $Authority = new Authority();

        //権限チェック
        if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
            return \Response::json("明細追加する権限がありません。");
        }

        DB::beginTransaction();

        try {
            $detailParams['request_id'] = null;
            $detailParams['credited_id'] = $mainRow['id'];
            $detailParams['credited_no'] = null;
            $detailParams['credited_date'] = null;
            $detailParams['actual_credited_date'] = null;
            $detailParams['financials_flg'] = 0;
            $detailParams['cash'] = null;
            $detailParams['credit_flg'] = null;
            $detailParams['cheque'] = null;
            $detailParams['receivingdept_id'] = null;
            $detailParams['transfer'] = null;
            $detailParams['transfer_charges'] = null;
            $detailParams['bills'] = null;
            $detailParams['bills_date'] = null;
            $detailParams['bank_code'] = null;
            $detailParams['bank_name'] = null;
            $detailParams['branch_code'] = null;
            $detailParams['branch_name'] = null;
            $detailParams['bills_no'] = null;
            $detailParams['endorsement_flg'] = null;
            $detailParams['sales_promotion_expenses'] = null;
            $detailParams['deposits'] = null;
            $detailParams['accounts_payed'] = null;
            $detailParams['discount'] = null;
            $detailParams['status'] = 0;
            $detailParams['status_dep'] = null;
            $detailParams['discount_app_id'] = null;
            $detailParams['discount_app_date'] = null;
            $creditedDetailId = $CreditedDetail->add($detailParams);

            $resultSts = true;
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 戻す
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function back(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        $mainRow = $params['mainRow'];
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Customer = new Customer();
        $NumberManage = new NumberManage();
        $Authority = new Authority();

        //権限チェック
        if ($Authority->hasAuthority(Auth::user()->id, config('const.auth.deposit.input')) == config('const.authority.none')) {
            return \Response::json("権限がありません。");
        }

        DB::beginTransaction();

        try {
            $updateParams = [];
            $updateParams['id'] = $mainRow['id'];
            $updateParams['different_amount']            = 0;
            $updateParams['be_different_amount']         = 0;
            $updateParams['different_amount_request_id'] = 0;

            switch($mainRow['status']) {
                case config('const.creditedStatus.val.payment'):
                    // 入金済　→　未入金へ更新
                    $updateParams['status'] = config('const.creditedStatus.val.unsettled');
                    // $Credited->updateStatus($updateParams);
                    break;
                case config('const.creditedStatus.val.transferred'):
                    // 繰越済　→　違算有へ更新
                    $updateParams['status']                     = config('const.creditedStatus.val.miscalculation');
                    $updateParams['miscalculation_dep']         = null;
                    $updateParams['miscalculation_status']      = 0;    // 未申請
                    $updateParams['miscalculation_app_com']     = null;
                    $updateParams['miscalculation_app_info']    = null;
                    $updateParams['miscalculation_app_id']      = null;
                    $updateParams['miscalculation_app_date']    = null;
                    $updateParams['miscalculation_auth_com']    = null;
                    $updateParams['miscalculation_auth_info']   = null;
                    $updateParams['miscalculation_auth_id']     = null;
                    $updateParams['miscalculation_auth_date']   = null;

                    // $Credited->updateMiscalCancel($updateParams);
                    break;
            }
            
            $Credited->updateById($updateParams['id'], $updateParams);

            $resultSts = true;
            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
}
