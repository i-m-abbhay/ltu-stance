<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerStaff;
use App\Models\CustomerStaffDetail;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffDepartment;
use Carbon\Carbon;
use App\Models\Authority;
use App\Models\Matter;
use App\Models\PeriodYear;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\QuoteVersion;
use App\Models\Requests;
use App\Models\SalesTarget;
use Session;
use DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

/**
 * 得意先担当者設定
 */
class CustomerStaffController extends Controller
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
     * 初期表示
     * @return type
     */
    public function index()
    {
        // リスト取得
        $customerList = (new Customer)->getComboList();
        $departmentList = (new Department())->getComboList();
        $staffList = (new Staff)->getComboList();
        $staffDepartList = (new StaffDepartment())->getCurrentTermList();

        $PeriodYear = new PeriodYear();
        $ymd = Carbon::now()->format('Ymd');
        $py = $PeriodYear->getPeriodYearByYmd($ymd, -1);

        return view('Customer.customer-staff')
            ->with('customerList', $customerList)
            ->with('departmentList', $departmentList)
            ->with('staffList', $staffList)
            ->with('staffDepartList', $staffDepartList)
            ;
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
        $params = $request->request->all();
        $dataList = json_decode($params['list'], true);
        $deleteIdList = json_decode($params['deleteDetailId'], true);

        $newFlg = false;
        if (empty($params['id'])) {
            $newFlg = true;
        }
        // バリデーションチェック
        $this->isValid($request);        

        DB::beginTransaction();
        $CustomerStaff = new CustomerStaff();
        $CustomerStaffDetail = new CustomerStaffDetail();
        try {
            $saveResult = false;
            if ($newFlg) {
                // 登録
                $headerData = [];
                $detailData = [];

                // ヘッダー情報作成
                $headerData['apply_staff_id']       = Auth::user()->id;
                $headerData['apply_date']           = $params['apply_date'];
                $headerData['apply_staff_comment']  = $params['apply_staff_comment'];
                $headerData['approval_status']      = config('const.flg.off');
                $headerData['approval_staff_id']    = null;
                $headerData['approval_at']          = null;
                $headerData['approval_comment']     = null;
                foreach ($dataList as $dKey => $data) {
                    if (!empty($dataList[$dKey]['staff_id'])) {
                        foreach ($data['cus'] as $cKey => $cus) {
                            if ($cus['isEdit'] == config('const.flg.on')) {
                                if ($cus['fromFlg'] == config('const.flg.on') || $cus['toFlg'] == config('const.flg.on')) {
                                    $detailData[$cus['id']]['customer_id'] = $cus['id'];
                                }
                                // 移動元
                                if ($cus['fromFlg'] == config('const.flg.on')) {
                                    $detailData[$cus['id']]['from_staff_id'] = $dataList[$dKey]['staff_id'];
                                    $detailData[$cus['id']]['from_department_id'] = $dataList[$dKey]['department_id'];
                                }
                                // 移動先
                                if ($cus['toFlg'] == config('const.flg.on')) {
                                    $detailData[$cus['id']]['to_staff_id'] = $dataList[$dKey]['staff_id'];
                                    $detailData[$cus['id']]['to_department_id'] = $dataList[$dKey]['department_id'];
                                }
                            }
                        }
                    }
                }

                $headerId = $CustomerStaff->add($headerData);
                foreach($detailData as $key => $val) {
                    $detailData[$key]['customer_staff_id'] = $headerId;

                    $saveResult = $CustomerStaffDetail->add($detailData[$key]);
                }
            } else {
                // 更新
                $headerData = [];
                $detailData = [];

                $headerData['id']                   = $params['id']; 
                // $headerData['apply_staff_id']       = Auth::user()->id;
                $headerData['apply_date']           = $params['apply_date'];
                $headerData['apply_staff_comment']  = $params['apply_staff_comment'];
                $headerData['approval_status']      = config('const.flg.off');
                $headerData['approval_staff_id']    = null;
                $headerData['approval_at']          = null;
                $headerData['approval_comment']     = null;

                // データ整形
                foreach ($dataList as $dKey => $data) {
                    if (!empty($dataList[$dKey]['staff_id'])) {
                        foreach ($data['cus'] as $cKey => $cus) {
                            if ($cus['isEdit'] == config('const.flg.on') || $cus['cancelFlg'] == config('const.flg.on')) {
                                if ($cus['fromFlg'] == config('const.flg.on') || $cus['toFlg'] == config('const.flg.on')) {
                                    $detailData[$cus['id']]['customer_id'] = $cus['id'];
                                }
                                // 移動元
                                if ($cus['fromFlg'] == config('const.flg.on')) {
                                    if (!empty($dataList[$dKey]['detail_id'])) {
                                        $detailData[$cus['id']]['detail_id'] = $dataList[$dKey]['detail_id'];
                                    }
                                    $detailData[$cus['id']]['from_staff_id'] = $dataList[$dKey]['staff_id'];
                                    $detailData[$cus['id']]['from_department_id'] = $dataList[$dKey]['department_id'];
                                }
                                // 移動先
                                if ($cus['toFlg'] == config('const.flg.on')) {
                                    if (!empty($dataList[$dKey]['detail_id'])) {
                                        $detailData[$cus['id']]['detail_id'] = $dataList[$dKey]['detail_id'];
                                    }
                                    $detailData[$cus['id']]['to_staff_id'] = $dataList[$dKey]['staff_id'];
                                    $detailData[$cus['id']]['to_department_id'] = $dataList[$dKey]['department_id'];
                                }
                                if ($cus['cancelFlg'] == config('const.flg.on') && $cus['toFlg'] == config('const.flg.off') && $cus['fromFlg'] == config('const.flg.off')) {
                                    if (!empty($cus['detail_id'])) {
                                        $detailData[$cus['id']]['detail_id'] = $cus['detail_id'];
                                    }
                                }
                            }
                        }
                    }
                }
                $headerId = $params['id'];

                $saveResult = $CustomerStaff->updateById($headerData);
                foreach($deleteIdList as $key => $val) {
                    $hoge = collect($detailData)->whereIn('detail_id', $val);
                    if (count(collect($detailData)->whereIn('detail_id', $val)) > 0) {
                        $deleteResult = $CustomerStaffDetail->deleteById($deleteIdList[$key]);
                        $detailData = collect($detailData)->whereNotIn('detail_id', $val)->toArray();
                    }
                }
                foreach($detailData as $key => $val) {
                    // 更新/登録
                    $detailData[$key]['customer_staff_id'] = $headerId;

                    $saveResult = $CustomerStaffDetail->updateById($detailData[$key]);
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
     * 削除
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            $CustomerStaff = new CustomerStaff();
            $CustomerStaffDetail = new CustomerStaffDetail();

            // 削除
            $deleteResult = $CustomerStaff->physicalDeleteById($params['customer_staff_id']);

            $deleteResult = $CustomerStaffDetail->deleteByHeaderId($params['customer_staff_id']);

            if ($deleteResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.delete'));
            } else {
                throw new \Exception(config('message.error.delete'));
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
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)  
    {
        // リクエストから検索条件取得
        $params = $request->request->all();
        $CustomerStaff = new CustomerStaff();
        $Customer = new Customer();
        $PeriodYear = new PeriodYear();
        $Requests = new Requests();
        $SalesTarget = new SalesTarget();

        $this->isValid($request);
        $list = $CustomerStaff->getList($params);

        $staffIDList = [];
        $staffIDList = array_merge($staffIDList, collect($list)->pluck('from_staff_id')->toArray(), collect($list)->pluck('to_staff_id')->toArray());
        $staffIDList = collect($staffIDList)->unique();
        // $staffIDList = collect($list)->pluck('to_staff_id')->toArray();
        $departmentIDList = [];
        $departmentIDList = array_merge($departmentIDList, collect($list)->pluck('from_department_id')->toArray(), collect($list)->pluck('to_department_id')->toArray());
        $departmentIDList = collect($departmentIDList)->unique();

        $customerList = [];
        $departmentIDs = [];
        $headerData = null;
        $cusStaffList = null;
        if (count($list) > 0) {
            $headerData = $list[0];
            $headerData['isEditable'] = false;
            $headerData['updateLock'] = false;
            // ステータスにclass、text付与
            $approvalKey = $headerData['approval_status'];
            $headerData['approval_status'] = array(
                'id' => $headerData['id'],
                'text' => config('const.customerStaffApprovalStatus.'. $approvalKey. '.text'),
                'class' => config('const.customerStaffApprovalStatus.'. $approvalKey. '.class'),
                'val' => config('const.customerStaffApprovalStatus.val.'. $approvalKey),
            );

            foreach($list as $key => $row) {
                if ($key == 0) {
                    continue;
                }
                $approvalKey = $list[$key]['approval_status'];
                $list[$key]['approval_status'] = array(
                    'id' => $headerData['id'],
                    'text' => config('const.customerStaffApprovalStatus.'. $approvalKey. '.text'),
                    'class' => config('const.customerStaffApprovalStatus.'. $approvalKey. '.class'),
                    'val' => config('const.customerStaffApprovalStatus.val.'. $approvalKey),
                );
            }

            // 承認権限者かどうか
            $mainDepartment = (new StaffDepartment)->getMainDepartmentByStaffId($headerData['apply_staff_id']);
            $approvalStaff = (new Department())->getById($mainDepartment['department_id']);

            // 部門長
            if ($approvalStaff['chief_staff_id'] == Auth::user()->id) {
                $headerData['isEditable'] = true;
            }
            // 全承認権限者
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.fullApproval'))) {
                $headerData['isEditable'] = true;
            }
            // 承認済
            if ($headerData['approval_status']['val'] != config('const.customerStaffApprovalStatus.val.not_approval')) {
                $headerData['isEditable'] = false;
            }
            if ($headerData['approval_status']['val'] == config('const.customerStaffApprovalStatus.val.approvaled')) {
                $headerData['updateLock'] = true;
            }
            if ($headerData['apply_staff_id'] !== Auth::user()->id) {
                $headerData['updateLock'] = true;
            }
            
            // 得意先担当者取得
            $fromStaffList = [];
            $toStaffList = [];
            foreach ($list as $key => $value) {
                // $customerList[] = $Customer->getByChargeId($list[$key]['from_department_id'], $list[$key]['from_staff_id']);
                // $customerList[] = $Customer->getByChargeId($list[$key]['to_department_id'], $list[$key]['to_staff_id']);
                // $customerList[] = $Customer->getById($list[$key]['customer_id']);
                $fromStaffList[$key]['staff_id'] = $list[$key]['from_staff_id'];
                $fromStaffList[$key]['department_id'] = $list[$key]['from_department_id'];
                $toStaffList[$key]['staff_id'] = $list[$key]['to_staff_id'];
                $toStaffList[$key]['department_id'] = $list[$key]['to_department_id'];
            }
            $Staff = new Staff();
            $staffList = [];

            $ftStaffList = [];
            $ftStaffList = array_merge($fromStaffList, $toStaffList);
            $ftStaffList = collect($ftStaffList)->unique(function($item) {
                return $item['staff_id'].$item['department_id'];
            });
            foreach ($ftStaffList as $key => $row) {
                $staffData = $Staff->getByIdWithDepartmentId($ftStaffList[$key]['staff_id'], $ftStaffList[$key]['department_id']);
                $staffList[] = $staffData;
            }
            $chargeList = [];
            foreach ($staffList as $key => $row) {
                $chargeList[$key] = $Customer->getByChargeId($staffList[$key]['department_id'], $staffList[$key]['id']);
                foreach($chargeList[$key] as $data) {
                    foreach($list as $lKey => $lRow) {
                        if ($data['id'] == $lRow['customer_id']) {
                            $data['detail_id'] = $lRow['detail_id'];
                            $data['to_staff_id'] = $lRow['to_staff_id'];
                            $data['from_staff_id'] = $lRow['from_staff_id'];
                            $data['to_department_id'] = $lRow['to_department_id'];
                            $data['from_department_id'] = $lRow['from_department_id'];
                            $data['approval_status'] = $lRow['approval_status']; 
                        }
                        
                        $customerList[] = $data;
                    }
                    
                }
            }
            $staffList = collect($staffList)->toArray();

            $cusList = collect($customerList)->unique('id')->toArray();
            $cData = [];
            $updatedData = [];

            $cusList = array_values($cusList);
            $cusIdList = [];
            $ProcessIDs = [];
            
            foreach ($cusList as $key => $cus) {
                $cusIdList[] = $cusList[$key]['id'];

                $cusList[$key]['fromFlg'] = false;
                $cusList[$key]['toFlg'] = false;
                $cusList[$key]['isCheck'] = false;
                $cusList[$key]['isEdit'] = false;
                $cusList[$key]['cancelFlg'] = false;
                $cusList[$key]['updatedFlg'] = false;
                $cusList[$key]['sales'] = $this->calcCustomerSales($cusList[$key]['id']);
            }
            // 表示用データ整形
            foreach ($staffList as $key => $val) {
                $cusStaffList[$key]['staff_id'] = $staffList[$key]['id'];
                $cusStaffList[$key]['department_id'] = $staffList[$key]['department_id'];
                $cusStaffList[$key]['staff_name'] = $staffList[$key]['staff_name'];
                $cusStaffList[$key]['department_name'] = $staffList[$key]['department_name'];
                foreach($cusList as $cKey => $row) {
                    if (!empty($cusList[$cKey]['from_staff_id'])) {
                        if ($staffList[$key]['id'] == $cusList[$cKey]['from_staff_id'] && $staffList[$key]['department_id'] == $cusList[$cKey]['from_department_id']) {
                            $row['fromFlg'] = true;
                            $cusStaffList[$key]['cus'][] = $row;
                            continue;
                        }
                    }
                    if (!empty($cusList[$cKey]['to_staff_id'])) {
                        if ($staffList[$key]['id'] == $cusList[$cKey]['to_staff_id'] && $staffList[$key]['department_id'] == $cusList[$cKey]['to_department_id']) {
                            if (!empty($cusList[$cKey]['approval_status']['val']) && $cusList[$cKey]['approval_status']['val'] == config('const.customerStaffApprovalStatus.val.approvaled')){
                                // 承認済みの場合
                                $row['updatedFlg'] = true;
                            } else {
                                // 申請中の場合
                                $row['toFlg'] = true;
                            }
                            $cusStaffList[$key]['cus'][] = $row;
                            continue;
                        }
                    }
                    if (!empty($cusList[$cKey]['charge_staff_id'])) {
                        if ($staffList[$key]['id'] == $cusList[$cKey]['charge_staff_id'] && $staffList[$key]['department_id'] == $cusList[$cKey]['charge_department_id']) {
                            $cusStaffList[$key]['cus'][] = $row;
                            continue;
                        }
                    }
                }
            }

            // 売上計算        
            $year = Carbon::now()->format('Y');
            // 前期間取得
            $ymd = Carbon::now()->format('Ymd');
            $subPeriod = $PeriodYear->getPeriodYearByYmd($ymd, -1);

            // 先月取得
            $subMonth = Carbon::now()->subMonth()->format('Ym');

            // 前年取得
            $subYear = Carbon::now()->subYear()->format('Ym');

            foreach($cusStaffList as $key => $row) {
                // 前期売上合計取得
                $previousSales = $Requests->getPreviousYearSalesByStaffId($row['staff_id'], $subPeriod['start_ym'], $subPeriod['end_ym'] );
                // 売上目標取得
                $targetSales = $SalesTarget->getTargetPriceByStaffId($row['staff_id'], $year);

                $avgSales = 0;
                if (count($previousSales) > 0) {
                    $avgSales = round((int)$previousSales[0]['total_sales'] / 12);
                }
                $avgTarget = 0;
                if (count($targetSales) > 0) {
                    $avgTarget = round((int)$targetSales[0]['target_price'] / 12);
                }
                $target = $avgSales + $avgTarget;
                if ($target > 0) {
                    // 千円単位切り上げ
                    $target = ceil($target / 1000) * 1000;
                }

                $cusStaffList[$key]['targetSales'] = $target;
            }
            $cusStaffList = array_values($cusStaffList);  
        }

            
        return \Response::json(['headerData' => $headerData, 'cusStaffData' => $cusStaffList]);
    }

    
    /**
     * 担当者の売上目標、得意先売上取得
     *
     * @param $staffId
     * @param $customerId
     * @return void
     */
    public function calcCustomerSales($customerId)
    {
        $rtnArr = [];
        $rtnArr['subMonthSales'] = 0;
        $rtnArr['avgSales'] = 0;
        $rtnArr['total_gross_profit_rate'] = 0;
        $rtnArr['purchase_volume'] = 0;
        $rtnArr['total_sales'] = 0;

        $PeriodYear = new PeriodYear();
        $Requests = new Requests();
        $SalesTarget = new SalesTarget();
        // 前期間取得
        $ymd = Carbon::now()->format('Ymd');
        $subPeriod = $PeriodYear->getPeriodYearByYmd($ymd, -1);
        // 先月取得
        $subMonth = Carbon::now()->subMonth()->format('Ym');
        // 前年取得
        $subYear = Carbon::now()->subYear()->format('Ym');

        // 先月売上
        $sales = 0;
        $subMonthSales = $Requests->getLastMonthSales($customerId, $subMonth);
        foreach ($subMonthSales as $key => $row) {
            $sales += $row['sales'] + $row['additional_discount_amount'];
        }
        $rtnArr['subMonthSales'] = $sales;
        // 過去12ヶ月売上取得
        $subYearSales = $Requests->getSalesByYearMonth($customerId, $subYear, $subMonth);
        // 月平均算出
        if ($sales > 0) {
            $rtnArr['avgSales'] = round((int)$sales / 12);
        }

        // 総粗利取得
        $subYearSales = $subYearSales[0];
        if ($subYearSales['total_sales'] != 0 && $subYearSales['total_purchase_volume'] != 0) {
            $rtnArr['purchase_volume'] = $subYearSales['total_purchase_volume'];
            $rtnArr['total_sales'] = $subYearSales['total_sales'];
            $rtnArr['total_gross_profit_rate'] = round((((int)$subYearSales['total_sales'] - (int)$subYearSales['total_purchase_volume']) / (int)$subYearSales['total_sales'] * 100), 2);
        }

        return $rtnArr;
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchCustomer(Request $request)  
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $Requests = new Requests();
            $SalesTarget = new SalesTarget();
            $PeriodYear = new PeriodYear();
            
            $rtnArr = [];
            $rtnArr['targetSales'] = 0;

            $year = Carbon::now()->format('Y');
            $ymd = Carbon::now()->format('Ymd');
            $subPeriod = $PeriodYear->getPeriodYearByYmd($ymd, -1);

            $list = (new Customer())->getByChargeId($params['department_id'], $params['staff_id']);
            
            // 前期売上合計取得
            $previousSales = $Requests->getPreviousYearSalesByStaffId($params['staff_id'], $subPeriod['start_ym'], $subPeriod['end_ym'] );
            // 売上目標取得
            $targetSales = $SalesTarget->getTargetPriceByStaffId($params['staff_id'], $year);

            $avgSales = 0;
            if (count($previousSales) > 0) {
                $avgSales = round((int)$previousSales[0]['total_sales'] / 12);
            }
            $avgTarget = 0;
            if (count($targetSales) > 0) {
                $avgTarget = round((int)$targetSales[0]['target_price'] / 12);
            }
            $target = $avgSales + $avgTarget;
            if ($target > 0) {
                // 千円単位切り上げ
                $target = ceil($target / 1000) * 1000;
            }

            $rtnArr['targetSales'] = $target;


            foreach ($list as $key => $val) {
                $list[$key]['isEdit'] = false;
                $list[$key]['sales'] = $this->calcCustomerSales($list[$key]['id']);
            }

            $rtnArr['cus'] = $list->toArray();


        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($rtnArr);
    }

    /**
     * 承認
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function approval(Request $request)  
    {
        $resultSts = false;
        DB::beginTransaction();
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $CustomerStaff = new CustomerStaff();
            $CustomerStaffDetail = new CustomerStaffDetail();

            $list = $CustomerStaffDetail->getByHeaderId($params['id']);

            // 承認ステータス更新
            $saveResult = $CustomerStaff->approval($params);

            $Customer = new Customer();
            $Matter = new Matter();
            $QuoteRequest = new QuoteRequest();
            $QuoteVersion = new QuoteVersion();

            $cSaveResult = false;
            $mSaveResult = false;
            $rSaveResult = false;
            $vSaveResult = false;
            $updateCnt = 0;
            foreach ($list as $key => $val) {
                // 得意先担当者更新
                $updateData = [];
                $updateData['id'] = $list[$key]['customer_id'];
                $updateData['charge_department_id'] = $list[$key]['to_department_id'];
                $updateData['charge_staff_id'] = $list[$key]['to_staff_id'];
                $cSaveResult = $Customer->updateStaffById($updateData);

                // 案件担当者更新
                $updateData = [];
                $updateData['customer_id'] = $list[$key]['customer_id'];
                $updateData['department_id'] = $list[$key]['to_department_id'];
                $updateData['staff_id'] = $list[$key]['to_staff_id'];
                $matterData = $Matter->getByCustomerId($updateData['customer_id']);
                $matterNo = collect($matterData)->pluck('matter_no')->toArray();

                $mSaveResult = $Matter->updateStaffByCustomerId($updateData);

                // 見積依頼担当者更新
                foreach ($matterNo as $key => $row) {
                    $updateData['matter_no'] = $row;
                    // $rSaveResult = $QuoteRequest->updateStaffByMatterNo($updateData);
                    $rSaveResult = $QuoteRequest->updateDepartmentStaffByMatterNo($updateData['matter_no'], $updateData['department_id'], $updateData['staff_id']);
                }

                // 見積版担当者更新
                $quoteData = (new Quote())->getByInMatterNo($matterNo);
                $quoteNo = collect($quoteData)->pluck('quote_no')->toArray();

                foreach ($quoteNo as $key => $No) {
                    $updateData['quote_no'] = $No;
                    // $vSaveResult = $QuoteVersion->updateStaffByQuoteNo($updateData);
                    $vSaveResult = $QuoteVersion->updateDepartmentStaffByQuoteNo($updateData['quote_no'], $updateData['department_id'], $updateData['staff_id']);
                }

                if ($cSaveResult) {
                    $updateCnt++;
                }
            }
            if ($updateCnt == count($list)) {
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
     * 否認
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function rejection(Request $request)  
    {
        $resultSts = false;
        DB::beginTransaction();
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $CustomerStaff = new CustomerStaff();
            
            $saveResult = $CustomerStaff->rejection($params);

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
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid($request)
    {
        $this->validate($request, [
            'apply_date' => 'required',
        ]);
    }
}