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
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\Credited;
use App\Models\CreditedDetail;
use App\Models\Company;
use App\Models\ReceivablesHistory;
use DB;
use App\Models\LockManage;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\StaffDepartment;
use App\Models\Authority;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Delivery;
use App\Models\Matter;
use App\Models\Qr;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use App\Models\NumberManage;
use App\Models\Payment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PHPUnit\Framework\Test;

/**
 * 請求一覧
 */
class RequestListController extends Controller
{
    const SCREEN_NAME = 'request-list';

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

        $customerList = $Customer->getAllList();
        $departmentList = $Department->getComboList();
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

        return view('Payment.request-list')
            ->with('customerList', $customerList)
            ->with('departmentList', $departmentList)
            ->with('staffList', $staffList)
            ->with('initSearchParams', $initSearchParams)
            ->with('authClosing', $authClosing)
            ->with('authInvoice', $authInvoice);
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
            $Requests = new Requests();
            $list = $Requests->getRequestList($params);

            $index = 0;
            foreach ($list as $row) {
                // statusを日本語対応
                $status = $row->status;
                if ($status == "printable") {
                    $list[$index]->status = '印刷可';
                } else if ($status == "issued") {
                    $list[$index]->status = '発行済';
                } else if ($status == "closed") {
                    $list[$index]->status = '締め済';
                } else if ($status == "undecided") {
                    $list[$index]->status = '未確定';
                }

                //フォーマット修正
                $request_mon = $row->request_mon;
                if ($request_mon != null && strlen($request_mon) == 6) {
                    $list[$index]->request_mon = substr($request_mon, 0, 4) . "/" . substr($request_mon, 4, 2) . "月";
                } elseif ($request_mon == 0) {
                    $list[$index]->request_mon  = null;
                }

                // $closing_day =  str_pad($list[$index]->closing_day, 2, '0', STR_PAD_LEFT);
                // if ($closing_day == 99 || $closing_day == 0) {
                //     $list[$index]->closing_day = date('Y-m-d', strtotime('last day of ' . (substr($request_mon, 0, 4) . "-" . substr($request_mon, 4, 2))));
                // } else {
                //     $list[$index]->closing_day = substr($request_mon, 0, 4) . "-" . substr($request_mon, 4, 2) . "-" . str_pad($list[$index]->closing_day, 2, '0', STR_PAD_LEFT);
                // }

                $list[$index]->matter_count  = floatval(round($list[$index]->matter_count));
                $list[$index]->lastinvoice_amount  = floatval(round($list[$index]->lastinvoice_amount));
                $list[$index]->offset_amount  = floatval(round($list[$index]->offset_amount));
                $list[$index]->deposit_amount  = floatval(round($list[$index]->deposit_amount));
                $list[$index]->carryforward_amount  = floatval(round($list[$index]->carryforward_amount));
                $list[$index]->discount  = floatval(round($list[$index]->discount));
                $list[$index]->discount_amount  = floatval(round($list[$index]->discount_amount));
                $list[$index]->current_month_sales  = floatval(round($list[$index]->current_month_sales));
                $list[$index]->consumption_tax  = floatval(round($list[$index]->consumption_tax));
                $list[$index]->current_month_sales_total  = floatval(round($list[$index]->current_month_sales_total));
                $list[$index]->billing_amount  = floatval(round($list[$index]->billing_amount));
                $list[$index]->display_total_sales  = $list[$index]->sales + $list[$index]->consumption_tax_amount + $list[$index]->discount_amount;
                $list[$index]->display_consumption_tax  = $list[$index]->consumption_tax_amount + $list[$index]->discount_amount;

                $index++;
            }
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($list);
    }

    /**
     * 発行済み解除
     *
     * @param Request $request
     * @return void
     */
    public function cancellation(Request $request)
    {
        // リクエストデータ取得
        $params = $request->request->all();
        $request_id = $params['request_id'];
        $customer_id = $params['customer_id'];

        $Requests = new Requests();
        $Credited = new Credited();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();

        DB::beginTransaction();

        try {
            $saveResult = false;

            $crediteCheck = $Credited->isRequestDeposited($request_id);
            if ($crediteCheck) {
                return \Response::json("本請求書は既に入金処理済みです。入金データを確認の上再度実行してください。");
            }

            // 一時削除されている請求があればエラー
            $tmpDeleteRequestData = $Requests->getTemporaryDeleteRequestData($customer_id);
            if ($tmpDeleteRequestData !== null) {
                return \Response::json("既に次の売上処理がされています。解除できません。");
            }

            // 最新請求取得
            $lastRequestData = $Requests->getLatestSalesPeriodRequests($customer_id);
            if ($lastRequestData !== null && $lastRequestData['id'] != $request_id && $lastRequestData['status'] < config('const.requestStatus.val.request_complete')) {
                // 最新の請求を一時削除する場合
                // 翌月の請求を削除フラグ[1]、状態フラグ[5]に更新する
                $updateRequestData = [];
                $updateRequestData['id'] = $lastRequestData['id'];
                $updateRequestData['status'] = config('const.requestStatus.val.temporary_delete');
                $updateRequestData['del_flg'] = config('const.flg.on');

                $Requests->updateById($updateRequestData['id'], $updateRequestData);

                // 一時削除を行った請求の相殺データを削除する
                $SalesDetail->deleteOffsetSalesByRequestId($updateRequestData['id']);
            }

            $isExistsRequestSales = $Requests->isExistsRequestSales($customer_id, $request_id);
            if ($isExistsRequestSales) {
                DB::rollBack();
                return \Response::json("既に次の売上処理がされています。解除できません。");
            }

            
            $saveResult = $Requests->cancellationById($request_id);
            // 発行時に作成された入金削除
            $saveResult = $Credited->deleteByRequestId($request_id);
            // 発行時に反映された違算の切り戻し
            $Credited->restoreDifferentAmountToUnapplied($request_id);

            DB::commit();

            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $saveResult = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($saveResult);
    }


    /**
     * 締めチェック処理
     *
     * @param Request $request
     * @return void
     */
    public function confirmClosing(Request $request)
    {
        $result = ['status' => false, 'message' => ''];
        // リクエストデータ取得
        $params = $request->request->all();
        $checkList = json_decode($params['requestList']);

        $Requests = new Requests();
        $SystemUtil = new SystemUtil();

        // 未処理の請求が存在することのメッセージを表示する。
        foreach ($checkList as $key => $row) {
            $requestData = $Requests->getById($row->request_id);
            $requestList = $Requests->getByCustomerId($row->customer_id);

            $requestMon = $requestData['request_mon'];            
            
            $notClosingRequest = [];
            foreach($requestList as $i => $data) {
                $tmpDateData = $SystemUtil->getCurrentMonthRequestPeriod($data['customer_id'], Carbon::parse($data['request_e_day']));

                // 計上月、売上期間が一致して、statusが1の請求
                if ($requestMon == $data['request_mon'] && $tmpDateData['request_mon'] == $data['request_mon'] && $data['status'] == config('const.requestStatus.val.complete')) {
                    array_push($notClosingRequest, $data);
                }
            }
            
            // $notClosingRequest[] = collect($requestList)
            //                         ->whereIn('status', [config('const.requestStatus.val.complete')])
            //                         ->where('request_mon', $requestMon)
            //                         // ->where('request_s_day', $requestSDay)
            //                         // ->where('request_e_day', $requestEDay)
            //                         ;

            $result['status'] = true;

            if (count($notClosingRequest) > 0) {
                // 確認メッセージ
                $result['message'] = config('message.error.request.not_closing_request');
                $result['status'] = false;
            }
        }

        return \Response::json($result);
    }

    /**
     * 締め確定
     *
     * @param Request $request
     * @return void
     */
    public function closing(Request $request)
    {
        // リクエストデータ取得
        $params = $request->request->all();
        $table = $params['data'];

        $Requests = new Requests();
        $LockManage = new LockManage();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $Credited = new Credited();
        $Customer = new Customer();
        $ReceivablesHistory = new ReceivablesHistory();
        $SystemUtil = new SystemUtil();

        $saveResult = false;

        DB::beginTransaction();

        try {

            //ロック&&データチェック
            $lockedFlg = false;
            $lockedMsg = "選択した請求の中に編集中の得意先が存在します。\n確認の上、再度実行してください。";

            $customerIdList = [];
            $customerIdList = collect($table)->pluck('customer_id')->unique()->toArray();
            foreach ($customerIdList as $customer_id) {

                // $request_id = $data['request_id'];
                // $customer_id = $data['customer_id'];
                // $customer_name = $data['customer_name'];

                //得意先のロック確認
                $tableName = 'm_customer';
                $isLocked = $LockManage->isLocked($tableName, $customer_id);
                if ($isLocked) {
                    $lockedFlg = true;
                    $customerData = $Customer->getById($customer_id);
                    $lockedMsg = $lockedMsg . "\n" . $customerData['customer_name'];
                } else {
                    //得意先の編集ロック
                    $LockManage->gainLock(Carbon::now(), self::SCREEN_NAME, $tableName, $customer_id);
                }
                $isLocked = false;

                //データチェック
                // $isExistSalesConfirmed = $Requests->isExistSalesConfirmed($customer_id);
                // if ($isExistSalesConfirmed) {
                //     DB::rollBack();
                //     return \Response::json("未完了の請求情報があります。完了後再度実行してください。");
                // }
            }

            if ($lockedFlg) {
                DB::rollBack();
                return \Response::json($lockedMsg);
            }
            DB::commit();

            DB::beginTransaction();
            foreach ($table as $data) {

                $request_id = $data['request_id'];
                $customer_id = $data['customer_id'];
                $customer_name = $data['customer_name'];

                //請求テーブルの締め情報更新
                $params['customer_id'] = $customer_id;
                $params['request_id'] = $request_id;
                $requestMonth = $Requests->getRequestMonthForRequestList($params);
                $saveResult = $Requests->updateClosingInfo($customer_id, $requestMonth);

                if ($data['sales_category'] !== 1) {
                    // 処理中の請求データの計上月を変更する
                    $requestList = $Requests->getByCustomerId($customer_id);
                    $requestList = collect($requestList)->whereIn('status', [config('const.requestStatus.val.unprocessed'), config('const.requestStatus.val.complete')]);

                    foreach ($requestList as $rKey => $rRow) {
                        $tmpDateData = $SystemUtil->getCurrentMonthRequestPeriod($rRow['customer_id'], Carbon::parse($rRow['request_e_day']));
                        $tmpExpectedData = $SystemUtil->getStrictCurrentMonthRequestPeriod($rRow['customer_id']);

                        $updateRow = [];
                        $updateRow['id'] = $rRow['id'];
                        $updateRow['request_mon'] = $tmpDateData['request_mon'];
                        $updateRow['expecteddeposit_at'] = (new Carbon($tmpExpectedData['expecteddeposit_at']))->addMonth(1)->format('Y/m/d');

                        $saveResult = $Requests->updateById($updateRow['id'], $updateRow);
                    }
                

                    //請求書発行情報新規作成
                    $newParams['customer_id'] = $data['customer_id'];
                    $newParams['customer_name'] = $data['customer_name'];
                    $newParams['charge_department_id'] = $data['department_id'];
                    $newParams['charge_department_name'] = $data['department_name'];
                    $newParams['charge_staff_id'] = $data['staff_id'];
                    $newParams['charge_staff_name'] = $data['staff_name'];
                    $newParams['closing_day'] =  $data['closing_code'];
                    $newParams['request_no'] = null;
                    $newParams['lastinvoice_amount'] = null;
                    $newParams['offset_amount'] = null;
                    $newParams['deposit_amount'] = null;
                    $newParams['receivable'] = null;
                    $newParams['different_amount'] = null;
                    $newParams['carryforward_amount'] = null;
                    $newParams['purchase_volume'] = null;
                    $newParams['sales'] = null;
                    $newParams['additional_discount_amount'] = null;
                    $newParams['consumption_tax_amount'] = null;
                    $newParams['discount_amount'] = null;
                    $newParams['total_sales'] = null;
                    $newParams['request_amount'] = null;
                    $newParams['request_day'] = null;
                    $newParams['request_user'] = null;
                    $newParams['request_up_at'] = null;
                    $newParams['request_up_user'] = null;
                    $newParams['sales_category'] = 0;
                    $newParams['image_sign'] = null;
                    $newParams['status'] = 0;

                    $customerInfo = $Customer->getById($customer_id);

                    //請求月
                    $request_mon = str_replace("月", "", str_replace("/", "", $data['request_mon']));
                    $year = substr($request_mon, 0, 4);
                    $month = substr($request_mon, 4, 2);

                    $month = intval($month) + 1;
                    if ($month < 10) {
                        $request_mon = $year . '0' . $month;
                    } elseif ($month < 13) {
                        $request_mon = $year . $month;
                    } else {
                        $request_mon = (intval($year) + 1) . '01';
                    }

                    $newParams['request_mon'] = $request_mon;

                    if ($request_mon !== null && $data['closing_code'] !== null) {

                        //終了日
                        $request_e_day = null;
                        $year = substr($request_mon, 0, 4);
                        $month = substr($request_mon, 4, 2);
                        $request_mon = $year . '-' . $month;
                        if ($data['closing_code'] == null || $data['closing_code'] == 99 || $data['closing_code'] == 0) {
                            $request_e_day =  date('Y-m-d', strtotime('last day of ' . $request_mon));
                            $request_s_day =  date('Y-m-d', strtotime('first day of ' . $request_mon));
                        } elseif ($data['closing_code'] < 10) {
                            $request_e_day =  $request_mon . '-0' . $data['closing_code'];
                            $request_s_day = date("Y-m-d", strtotime($request_e_day . "-1 month"));
                            $request_s_day = date("Y-m-d", strtotime($request_s_day . "+1 day"));
                        } else {
                            $request_e_day =  $request_mon . '-' . $data['closing_code'];
                            $request_s_day = date("Y-m-d", strtotime($request_e_day . "-1 month"));
                            $request_s_day = date("Y-m-d", strtotime($request_s_day . "+1 day"));
                        }

                        $newParams['request_e_day'] = $request_e_day;
                        $newParams['request_s_day'] = $request_s_day;
                        $newParams['be_request_e_day'] =  $request_e_day;
                        $newParams['shipment_at'] = $request_e_day == null ? null : date("Y-m-d", strtotime($request_e_day . "+1 day"));

                        if ($customerInfo['collection_day'] == null || $customerInfo['collection_day'] == 99 || $customerInfo['collection_day'] == 0) {
                            $expecteddeposit_at =  date('Y-m-d', strtotime('last day of ' . $request_mon));
                        } elseif ($customerInfo['collection_day'] < 10) {
                            $expecteddeposit_at =  $request_mon . '-0' . $customerInfo['collection_day'];
                        } else {
                            $expecteddeposit_at =  $request_mon . '-' . $customerInfo['collection_day'];
                        }

                        switch ($customerInfo['collection_sight']) {
                            case 0:
                                $newParams['expecteddeposit_at'] = $expecteddeposit_at;

                                break;
                            case 1:
                                $newParams['expecteddeposit_at'] = date("Y-m-d", strtotime($expecteddeposit_at .  "+1 month"));

                                break;
                            case 2:
                                $newParams['expecteddeposit_at'] = date("Y-m-d", strtotime($expecteddeposit_at .  "+2 month"));

                                break;
                            case 3:
                                $newParams['expecteddeposit_at'] = date("Y-m-d", strtotime($expecteddeposit_at .  "+3 month"));

                                break;

                            case 4:
                                $newParams['expecteddeposit_at'] = date("Y-m-d", strtotime($expecteddeposit_at .  "+4 month"));

                                break;
                        }
                    }

                    //不具合要望 No370によりコメントアウト　
                    // //新規登録
                    // $Requests->addNextMonth($newParams);


                    //債権一覧用のデータ生成
                    $params['request_mon'] = $requestMonth;
                    $salesAmount = $Requests->getSalesAmount($params);
                    $params['receivable']  = $Requests->getCustomerTotalSales($customer_id);
                    $bill = null;
                    if ($requestMonth != null) {
                        $bill = $Credited->getBill($params);
                    }

                    $params['customer_name'] = $customer_name;

                    //債権履歴生成
                    $params['offset_amount'] = $salesAmount['offset_amount'] == null ? 0 : $salesAmount['offset_amount'];
                    $params['deposit_amount'] = $salesAmount['deposit_amount'] == null ? 0 : $salesAmount['deposit_amount'];
                    $params['sales_total'] = $salesAmount['sales'] == null ? 0 : $salesAmount['sales'];
                    $params['bills'] = $bill['bills'] == null ? 0 : $bill['bills'];
                    $ReceivablesHistory->add($params);
                }
            }

            //ロック解除
            foreach ($table as $data) {

                $request_id = $data['request_id'];
                $customer_id = $data['customer_id'];
                $customer_name = $data['customer_name'];
                //得意先ロック解除
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, [$customer_id], Auth::user()->id);
            }

            DB::commit();

            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $saveResult = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($saveResult);
    }



    /**
     * 締め解除
     *
     * @param Request $request
     * @return void
     */
    public function closingCancel(Request $request)
    {
        // リクエストデータ取得
        $params = $request->request->all();
        $customer_id = $params['data'][0]['customer_id'];
        $customer_name = $params['data'][0]['customer_name'];
        $request_month = $params['data'][0]['request_mon'];
        $request_id = $params['data'][0]['request_id'];
        $sales_category = $params['data'][0]['sales_category'];


        $Requests = new Requests();
        $ReceivablesHistory = new ReceivablesHistory();
        $LockManage = new LockManage();
        $Credited = new Credited();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $Matter = new Matter();

        $saveResult = false;

        DB::beginTransaction();
        try {
            //既に締め解除していないかどうか
            $isCancel = $Requests->isCancel($customer_id);
            if ($isCancel) {
                return \Response::json("既に解除処理がされています。実施できません。");
            }

            // 一時削除されている請求があればエラー
            $tmpDeleteRequestData = $Requests->getTemporaryDeleteRequestData($customer_id);
            if ($tmpDeleteRequestData !== null) {
                return \Response::json("既に次の売上処理がされています。解除できません。");
            }

            //最後に締め処理をされた以前のデータの場合無効
            $lastRequestMonth = $Requests->isLastClosed($customer_id);
            if ($request_month == null || $lastRequestMonth != str_replace("月", "", str_replace("/", "", $request_month))) {
                return \Response::json("この請求は締め解除できません。最新の締め済データを選択してください。");
            }

            //既にデータが存在しないか
            $isExistNextRequestData = false;
            $dataList = $Requests->getNotClosing($customer_id, $request_id);
            $nextRequestData = collect($dataList)->first();
            if ($dataList != null && count($dataList) >= 2) {
                // 2件以上あれば不可
                return \Response::json("既に別の請求データが存在します。実施できません。");
            } else if ($dataList != null && count($dataList) == 1) {
                if ($nextRequestData['status'] >= config('const.requestStatus.val.request_complete')) {
                    // 「発行済」以降の場合は不可
                    return \Response::json("既に別の請求データが存在します。実施できません。");
                } else {
                    $isExistNextRequestData = true;
                }
            }
            //  elseif ($dataList != null && count($dataList) == 1 && $dataList[0]['status'] <> 0) {
            //     return \Response::json("既に別の請求データが存在します。実施できません。");
            // }

            //得意先のロック確認
            $tableName = 'm_customer';
            $isLocked = $LockManage->isLocked($tableName, $customer_id);
            if ($isLocked) {
                return \Response::json("選択した請求の中に編集中の得意先が存在します。\n確認の上、再度実行してください。\n" . $customer_name);
            } else {
                //得意先の編集ロック
                $LockManage->gainLock(Carbon::now(), self::SCREEN_NAME, $tableName, $customer_id);
            }


            //作成済みの最新請求データを削除
            // if ($dataList != null && count($dataList) == 1 && $dataList[0]['status'] == 0) {
            //     $Requests->deleteById($dataList[0]['id']);
            // }

            if (!$isExistNextRequestData) {
                // 通常の締め解除
                // 案件完了チェック
                $checkResult = $this->isMatterCheck($request_id);
                if ($checkResult['isClosed']) {
                    DB::rollback();
                    return \Response::json($checkResult['message']);
                }

                $saveResult = $Requests->closingCancel($customer_id);
            } else {
                // 最新の請求を一時削除する場合
                $requestMon = str_replace("月", "", str_replace("/", "", $request_month));

                // 翌月の請求を削除フラグ[1]、状態フラグ[5]に更新する
                $updateRequestData = [];
                $updateRequestData['id'] = $nextRequestData['id'];
                $updateRequestData['status'] = config('const.requestStatus.val.temporary_delete');
                $updateRequestData['del_flg'] = config('const.flg.on');

                $Requests->updateById($updateRequestData['id'], $updateRequestData);

                // 一時削除を行った請求の相殺データを削除する
                $SalesDetail->deleteOffsetSalesByRequestId($updateRequestData['id']);


                // 前月の請求一覧取得
                $closedRequestList = $Requests->getClosedRequestData($customer_id, $requestMon);
                $lastRequestEDay = collect($closedRequestList)->pluck('request_e_day')->max();

                // 締め解除
                foreach ($closedRequestList as $key => $row) {
                    // 案件完了チェック
                    $checkResult = $this->isMatterCheck($row['id']);
                    if ($checkResult['isClosed']) {
                        DB::rollback();
                        return \Response::json($checkResult['message']);
                    }

                    $cancelRequestData = [];
                    $cancelRequestData['id'] = $row['id'];
                    $cancelRequestData['request_up_at']     = null;     // 請求確定日
                    $cancelRequestData['request_up_user']   = null;     // 請求確定者

                    if ($lastRequestEDay == $row['request_e_day']) {
                        // 期間内で最新の請求は印刷可[1]に更新

                        // 入金データチェック
                        $isDeposited = $Credited->isRequestDeposited($cancelRequestData['id']);
                        if ($isDeposited) {
                            DB::rollBack();
                            return \Response::json("本請求書は既に入金処理済みです。入金データを確認の上再度実行してください。");
                        }

                        // 更新データ
                        $cancelRequestData['status'] = config('const.requestStatus.val.complete');
                        $cancelRequestData['request_no']    = null;     // 請求書番号
                        $cancelRequestData['request_day']   = null;     // 請求書発行日
                        $cancelRequestData['request_user']  = null;     // 請求書発行者

                        // 発行時に作成された入金削除
                        $saveResult = $Credited->deleteByRequestId($cancelRequestData['id']);
                        // 発行時に反映された違算の切り戻し
                        $Credited->restoreDifferentAmountToUnapplied($cancelRequestData['id']);
                    } else {
                        // 発行済[4]に更新
                        $cancelRequestData['status'] = config('const.requestStatus.val.release');
                    }

                    $saveResult = $Requests->updateById($cancelRequestData['id'], $cancelRequestData);
                }
            }

            if ($sales_category !== 1) {
                $params['customer_id'] = $customer_id;
                $params['request_mon'] = $lastRequestMonth;
                $saveResult = $ReceivablesHistory->deleteByCustomerId($params);
            }

            //得意先ロック解除
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, [$customer_id], Auth::user()->id);

            DB::commit();

            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $saveResult = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($saveResult);
    }


    /**
     * 案件完了されていないか
     */
    protected function isMatterCheck($requestId) 
    {
        $result = ['isClosed' => false, 'message' => config('message.error.request.matter_closed')];

        $Requests = new Requests();
        $Matter = new Matter();
        $SalesDetail = new SalesDetail();

        $requestData = $Requests->getById($requestId);
        // 売上期間内の売上明細を取得
        $salesDetailList = $SalesDetail->getSalesDetailByTargetPeriod($requestId, $requestData['request_s_day'], $requestData['request_e_day']);

        $matterIdList = collect($salesDetailList)->pluck('matter_id')->unique()->toArray();
        foreach ($matterIdList as $matterId) {
            $matterData = $Matter->getById($matterId);

            if ($matterData !== null && Common::isFlgOn($matterData['complete_flg'])) {
                // 案件完了されている
                $result['isClosed'] = true;
                $result['message'] .= '\n　施主名:'. $matterData['owner_name'];
            }
        }

        return $result;
    }

    /**
     * 売上明細出力
     *
     * @param Request $request
     * @return void
     */
    public function outputSalesDetail(Request $request)
    {
        // リクエストデータ取得
        $params = $request->request->all();

        $customer_id_list = [];
        $request_id_list = [];

        try {
            // 一覧データ取得
            $SalesDetail = new SalesDetail();
            $list = [];
            foreach ($params['data'] as $data) {
                // array_push($customer_id_list, $data['customer_id']);
                // array_push($request_id_list, $data['request_id']);

                $requestList = $SalesDetail->getSalesDetailByRequestId($data['request_id'], $data['request_s_day'], $data['request_e_day']);
                $list = array_merge($list, $requestList->toArray());
            }

            // $list = $SalesDetail->getSalesDetailForRequestList($customer_id_list, $request_id_list);

            //Excel出力
            $response = new StreamedResponse();
            $spreadsheet = null;
            // ファイル名
            $FILE_NAME = 'sales_detail_list';
            // 開始列
            $START_COL = 'A';
            // 開始行
            $START_ROW = 3;
            // 終了列
            $END_COL = 'AP';

            $HEADER_INFO = [
                'charge_department_id',
                'charge_department_name',
                'charge_staff_id',
                'charge_staff_name',
                'request_no',
                'sales_category',
                'sales_flg',
                'customer_id',
                'customer_name',
                'matter_no',
                // 'matter_name',
                'owner_name',
                'delivery_date',
                'delivery_no',
                'sales_date',
                'sales_detail_id',
                'construction_id',
                'construction_name',
                'class_big_id',
                'class_big_name',
                'class_middle_id',
                'class_middle_name',
                'class_small_id',
                'class_small_name',
                'product_id',
                'product_code',
                'product_name',
                'model',
                'sales_quantity',
                'unit',
                'stock_unit',
                'stock_quantity',
                'cost_unit_price',
                'cost_amount',
                'sales_unit_price',
                'sales_amount',
                'gross_profit_amount',
                'gross_profit_margin',
                'regular_price',
                'maker_id',
                'maker_name',
                'supplier_id',
                'supplier_name',
            ];

            try {
                // スプレッドシートを作成
                //$spreadsheet = new Spreadsheet();
                $spreadsheet = IOFactory::load(resource_path(config('const.templatePath') . DIRECTORY_SEPARATOR . config('const.excelTemplateName.salesDetailList')));
                // $spreadsheet = IOFactory::load("./template/reports/sales_detail_list.xlsx");

                // ファイルのプロパティを設定
                $properties = $spreadsheet->getProperties();
                $properties->setCreator(Auth::user()->staff_name);
                $properties->setLastModifiedBy(Auth::user()->staff_name);

                // シート作成
                $spreadsheet->getActiveSheet('Sheet1')->UnFreezePane();
                $sheet = $spreadsheet->getActiveSheet();

                // 値
                $SystemUtil = new SystemUtil();
                $outputDataList = [];
                foreach ($list as $key => $record) {
                    $val = [];
                    foreach ($HEADER_INFO as $column) {
                        if ($column === 'sales_category') {
                            $val[] = config('const.sales_category.list.' . $record->{$column});
                        } else if ($column === 'sales_flg') {
                            $val[] = config('const.sales_flg.list.' . $record->{$column});
                        } else if ($column === 'cost_amount') {
                            // 仕入金額
                            $val[] = $SystemUtil->calcTotal($record->sales_quantity, $record->cost_unit_price, false);
                        } else if ($column === 'sales_amount') {
                            // 販売金額
                            $val[] = $SystemUtil->calcTotal($record->sales_quantity, $record->sales_unit_price, false);
                        } else if ($column === 'gross_profit_amount') {
                            // 粗利額
                            $val[] = $SystemUtil->calcProfitTotal($SystemUtil->calcTotal($record->sales_quantity, $record->sales_unit_price, false), $SystemUtil->calcTotal($record->sales_quantity, $record->cost_unit_price, false));
                        } else if ($column === 'gross_profit_margin') {
                            // 粗利率
                            $val[] = $SystemUtil->calcRate($SystemUtil->calcProfitTotal($SystemUtil->calcTotal($record->sales_quantity, $record->sales_unit_price, false), $SystemUtil->calcTotal($record->sales_quantity, $record->cost_unit_price, false)), $SystemUtil->calcTotal($record->sales_quantity, $record->sales_unit_price, false));
                        } else {
                            $val[] = $record->{$column};
                        }
                    }
                    $outputDataList[] = $val;
                }


                $sheet->fromArray($outputDataList, null, $START_COL . $START_ROW, true);
                $style = $sheet->getStyle($START_COL . $START_ROW . ':' . $END_COL . ((count($outputDataList) - 1) + $START_ROW));
                $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


                ob_end_clean();
                $response->setCallBack(function () use ($spreadsheet) {
                    $writer = new XlsxWriter($spreadsheet);
                    $writer->save('php://output');
                });

                $response->setStatusCode(200);
                $response->headers->set('Content-Description', 'File Transfer');
                $response->headers->set('Content-Disposition', 'attachment; filename=' . $FILE_NAME . '.xlsx');
                $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
                $response->headers->set('Content-Transfer-Encoding', 'binary');
                $response->headers->set('Expires', '0');
                $response->send();
            } catch (\Exception $ex) {
            } finally {
                if (!is_null($spreadsheet)) {
                    $spreadsheet->disconnectWorksheets();
                    unset($spreadsheet);
                }
            }
            return $response;
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }

    /**
     * 請求処理前のチェック処理
     * 
     */
    public function confirmPrint(Request $request)
    {
        $result = ['status' => false, 'message' => ''];
        // リクエストデータ取得
        $params = $request->request->all();
        $table = $params['data'];

        $Requests = new Requests();
        $Credited = new Credited();
        $Customer = new Customer();
        
        try {
            $isUnsettledErr = false;
            $isNotFinishErr = false;
            $errMessage = '請求書発行を続行しますか？';
            $message1 = '\n未入金の入金データが存在します。';
            $message2 = '\n入金処理が完了していない入金データが存在します。';

            foreach ($table as $data) {
                $request_id = $data['request_id'];
                $customer_id = $data['customer_id'];
                $status = $data['status'];
                $request_mon = str_replace("月", "", str_replace("/", "", $data['request_mon']));
                $isThismonth = $request_mon == Carbon::now()->format('Ym');

                if ($request_id != null) {
                    if ($status == 0 || $status == 1) {

                        $customerData = $Customer->getById($customer_id);
                        
                        $updInfo = $Requests->getById($request_id);
                        // $updParams['id'] = $request_id;
                        $request_s_day = $updInfo['request_s_day'];
                        $request_s_day = $request_s_day == null ? null : date('Y-m-d',  strtotime($request_s_day));
                        $request_e_day = $updInfo['request_e_day'];
                        $request_e_day = $request_e_day == null ? null : date('Y-m-d',  strtotime($request_e_day));
                        $creData = $Credited->getSalesDataInPeriod($customer_id, $request_s_day, $request_e_day);

                        // 未入金のチェック
                        $isUnsettled = $Credited->isUnsettledByCustomerId($customer_id, $request_e_day);
                        if ($isUnsettled) {
                            $isUnsettledErr = true;
                            $message1 .= '\n　・'. $customerData['customer_name'];
                        }
                        // 未処理の入金チェック
                        $isNotFinish = $Credited->isNotFinishByCustomerId($customer_id, $request_e_day);
                        if ($isNotFinish) {
                            $isNotFinishErr = true;
                            $message2 .= '\n　・'. $customerData['customer_name'];
                        }
                    }
                }
            }

            if ($isUnsettledErr) {
                $errMessage .= $message1;
            }
            if ($isNotFinishErr) {
                $errMessage .= $message2;
            }
            $result['message'] = $errMessage;

            if (!$isUnsettledErr && !$isNotFinishErr) {
                $result['status'] = true;
            }

        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }

    /******************************************************************************
     * 請求書発行
     *
     * @param Request $request
     * @return void
     */
    public function print(Request $request)
    {
        $result = ['status' => false, 'invoiceData' => null, 'message' => ''];
        // リクエストデータ取得
        $params = $request->request->all();
        $table = $params['data'];

        $Requests = new Requests();
        $SalesDetail = new SalesDetail();
        $Sales = new Sales();
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Company = new Company();
        $SalesDetail = new SalesDetail();
        $ReceivablesHistory = new ReceivablesHistory();
        $NumberManage = new NumberManage();
        $LockManage = new LockManage();
        $SystemUtil = new SystemUtil();
        // 期、税率取得
        $System = new System();
        $systemList = $System->getByPeriod();
        $period = null;
        $taxRate = 1;
        if ($systemList != null) {
            $period = $systemList['period'];
            $taxRate =  $systemList['tax_rate'] / 100;
        }

        //請求書データ
        $invoiceData = [];
        $saveResult = false;

        DB::beginTransaction();

        try {
            foreach ($table as $data) {
                $request_id = $data['request_id'];
                $customer_id = $data['customer_id'];
                // $status = $data['status'];
                $request_mon = str_replace("月", "", str_replace("/", "", $data['request_mon']));
                $isThismonth = $request_mon == Carbon::now()->format('Ym');

                if ($request_id != null) {
                    
                    $updInfo = $Requests->getById($request_id);
                    // $updParams['id'] = $request_id;
                    $request_s_day = $updInfo['request_s_day'];
                    $request_s_day = $request_s_day == null ? null : date('Y-m-d',  strtotime($request_s_day));
                    $request_e_day = $updInfo['request_e_day'];
                    $request_e_day = $request_e_day == null ? null : date('Y-m-d',  strtotime($request_e_day));
                    $creData = $Credited->getSalesDataInPeriod($customer_id, $request_s_day, $request_e_day);
                    $status = $updInfo['status'];

                    //既に発行済みでない場合は情報更新
                    if ($status == 0 || $status == 1) {
                        // 請求データ、売上期間取得
                        $requestData = $Requests->getById($request_id);
                        $tmpDateData = $SystemUtil->getCurrentMonthRequestPeriod($customer_id, Carbon::parse($requestData['request_e_day']));

                        if ($tmpDateData['request_mon'] != $requestData['request_mon']) {
                            // 計上月、売上期間が一致していない請求書はスキップ
                            $result['message'] = config('message.error.request.excluded_print');
                            continue;
                        }


                        //得意先のロック確認
                        $tableName = 'm_customer';
                        $isLocked = $LockManage->isLocked($tableName, $customer_id);
                        if ($isLocked) {
                            $result['message'] = config('message.error.getlock');
                            return \Response::json($result);
                        }

                        //得意先の編集ロック
                        $LockManage->gainLock(Carbon::now(), self::SCREEN_NAME, $tableName, $customer_id);

                        $updateRequestInfo = [];
                        // 請求の更新
                        $updateRequestInfo['id'] = $updInfo['id'];
                        // 違算、売掛、繰越
                        $tmpCarryForwardAmount                    = $this->getCarryforwardAmount($updInfo['customer_id'], $updateRequestInfo['id']);
                        $updateRequestInfo['different_amount']    = $tmpCarryForwardAmount['different_amount'];
                        $updateRequestInfo['receivable']          = $tmpCarryForwardAmount['receivable'];
                        $updateRequestInfo['carryforward_amount'] = $tmpCarryForwardAmount['carryforward_amount'];

                        // 入金額
                        $updateRequestInfo['deposit_amount']      = Common::nullorBlankToZero($creData['total_deposit']);
                        // 相殺その他
                        $updateRequestInfo['offset_amount']       = Common::nullorBlankToZero($creData['offset_ets']);

                        // 税額
                        $updateRequestInfo['consumption_tax_amount']    = $this->roundTax($updInfo['sales'] * $taxRate);
                        // 税込当月売上合計 = 当月売上高 + 税額 + 税調整額 + 違算 + (-相殺)
                        $updateRequestInfo['total_sales']               = $updInfo['sales'] + $updateRequestInfo['consumption_tax_amount'] + $updInfo['discount_amount'] + $updateRequestInfo['different_amount'] + ($tmpCarryForwardAmount['payment_offset'] * -1);
                        // 税込当月請求合計 = 税込当月売上合計 + 売掛金
                        $updateRequestInfo['request_amount']            = $updateRequestInfo['total_sales'] + $updateRequestInfo['receivable'];

                        // $params['request_id'] = $request_id;
                        // $params['customer_id'] = $customer_id;
                        $updateRequestInfo['status'] = config('const.requestStatus.val.request_complete');
                        $updateRequestInfo['request_day'] = $params['request_day'];
                        if ($status == 1) {
                            $updateRequestInfo['request_no'] = $NumberManage->getSeqNo(config('const.number_manage.kbn.request'), Carbon::today()->format('Ym'));
                        }
                        $updateRequestInfo['request_user'] = Auth::user()->id;

                        $saveResult = $Requests->updateById($updateRequestInfo['id'], $updateRequestInfo);
                        // $saveResult = $Requests->updateInvoiceStatus($params);

                        // 一時削除されている請求を復活
                        $tmpRequestData = $Requests->getTemporaryDeleteRequestData($customer_id);
                        $updateParams = [];
                        if ($tmpRequestData != null) {
                            $updateParams['id']          = $tmpRequestData['id'];
                            $updateParams['request_mon'] = $requestData['request_mon'];
                            $updateParams['status']      = config('const.requestStatus.val.unprocessed');
                            $updateParams['del_flg']     = config('const.flg.off');
                            
                            $Requests->updateById($updateParams['id'], $updateParams);
                        }

                        // //売上確定しなかったデータを削除
                        // $saveResult = $SalesDetail->dataReset($customer_id);

                        //入金情報を生成する
                        // $params['request_mon'] = $request_mon;
                        $list = $Requests->getCreditedList($request_id);
                        $list['request_different_amount'] = $list['different_amount'];
                        $list['different_amount'] = 0;
                        $saveResult = $Credited->addByRequest($list);

                        //得意先ロック解除
                        $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, [$customer_id], Auth::user()->id);
                    }

                    // $updParams['deposit_amount'] = $creData['total_deposit'];
                    // $updParams['offset_amount'] = $creData['offset_ets'];
                    // $updParams['receivable']  = $Requests->getCustomerTotalSales($customer_id);
                    // 「印刷可」の場合は繰越金を修正
                    // if ($status == 1) {
                    //     $updParams['receivable'] += (($updInfo['sales'] + $updInfo['consumption_tax_amount'] + $updInfo['different_amount'] + $updInfo['discount_amount']) * -1);
                    // }
                    // $updParams['carryforward_amount'] = $updParams['receivable'] + $updInfo['different_amount'];
                    // $updParams['request_amount'] = $updParams['receivable'] +  $updInfo['total_sales'];
                    // if (
                    //     ($updParams['deposit_amount'] != $updInfo['deposit_amount'] ||
                    //     $updParams['receivable'] != $updInfo['receivable'] ||
                    //     $updParams['carryforward_amount'] != $updInfo['carryforward_amount'] ||
                    //     $updParams['request_amount'] != $updInfo['request_amount']) && ($status == 0 || $status == 1)
                    // ) {

                        // $Requests->updateInvoice($updParams);
                    // }

                    //請求書情報取得
                    $requestInfo = $Requests->getInvoiceData($data);
                    $s_date = Carbon::parse($requestInfo['request_s_day'])->format('Y/m/d');
                    $e_date = Carbon::parse($requestInfo['request_e_day'])->format('Y/m/d');
                    // if ($isThismonth) {
                    $salesDetailInfo = $SalesDetail->getInvoiceData($request_id);
                    $requestDetailInfo = $SalesDetail->getInvoiceDetailData($request_id, null, $s_date, $e_date);
                    $InvoiceListInfo = $Sales->getInvoiceListData($request_id, $s_date, $e_date);
                    $data['request_s_day'] = $requestInfo['request_s_day'] ? $requestInfo['request_s_day'] : 0;
                    $data['request_e_day'] = $requestInfo['request_e_day'] ? $requestInfo['request_e_day'] : 0;
                    $creditedDetailInfo = $CreditedDetail->getInvoiceData($customer_id, $request_s_day, $request_e_day);

                    $companyInfo = $Company
                        ->from('m_company as c')
                        ->selectRaw('
                            c.*
                        ')
                        ->first();
                    // 社印
                    $companyStamp  = null;
                    $stampFilePath = config('const.uploadPath.company_stamp') . $companyInfo['id'] . '/' . $companyInfo['stamp'];
                    if (Storage::exists($stampFilePath)) {
                        $companyStamp = base64_encode(Storage::get($stampFilePath));
                    }
                    //口座種別
                    $account_type1 = null;
                    if ($requestInfo['account_type1'] == 1) {
                        $account_type1 = "普通";
                    } else if ($requestInfo['account_type1'] == 2) {
                        $account_type1 = "当座";
                    }
                    $account_type2 = null;
                    if ($requestInfo['account_type2'] == 1) {
                        $account_type2 = "普通";
                    } else if ($requestInfo['account_type2'] == 2) {
                        $account_type2 = "当座";
                    }

                    $json = array(
                        'request_id' => $request_id,
                        'sales' => $requestInfo['sales'],
                        //ヘッダ
                        'ヘッダ' => [
                            '請求先郵便番号' => substr($requestInfo['zipcode'], 0, 3) . '-' . substr($requestInfo['zipcode'], 3, 4),
                            '請求先住所' => $requestInfo['address1'] . $requestInfo['address2'],
                            '請求先名' => $requestInfo['customer_name'],
                            'お客様コード' =>  $requestInfo['customer_code'],
                            '締切日' =>  $requestInfo['request_day'],
                            '発行日' =>  $requestInfo['print_day'],
                            '請求No' =>  $requestInfo['request_no'],
                            '自社名' =>  $companyInfo['company_name'],
                            '自社支店名' =>  $requestInfo['department_name'],
                            '自社郵便番号' =>  substr($requestInfo['base_zipcode'], 0, 3) . '-' . substr($requestInfo['base_zipcode'], 3, 4),
                            '自社住所' => $requestInfo['base_address1'] . $requestInfo['base_address2'],
                            '自社電話番号' =>  $requestInfo['department_tel'],
                            '自社FAX番号' =>  $requestInfo['department_fax'],
                            '自社振込先銀行名1' =>  $requestInfo['bank_name1'] . '　' .  $requestInfo['branch_name1'],
                            '自社振込先銀行名2' =>  $requestInfo['bank_name2'] . '　' .  $requestInfo['branch_name2'],
                            '自社振込先口座番号1' => $account_type1 . '　' . $requestInfo['account_number1'],
                            '自社振込先口座番号2' => $account_type2 . '　' .  $requestInfo['account_number2'],
                            '自社振込先会社名1' =>  $requestInfo['account_name1'],
                            '自社振込先会社名2' =>  $requestInfo['account_name2'],
                            '担当者名' =>  $requestInfo['staff_name'],
                            '印鑑' =>  $companyStamp,
                        ],
                        //請求情報
                        '請求情報' => [
                            '前回ご請求額' => $requestInfo['carryforward_amount'],
                            'ご入金額' => $requestInfo['deposit_amount'] + $requestInfo['offset_amount'],
                            '繰越額' => $requestInfo['next_carryforward_amount'],
                            '今回お買上額' => $requestInfo['sales'],
                            '消費税額' => $requestInfo['consumption_tax_amount'] + $requestInfo['discount_amount'],
                            '今回請求額' => $requestInfo['next_carryforward_amount'] + $requestInfo['sales'] + $requestInfo['consumption_tax_amount'] + $requestInfo['discount_amount']
                        ]
                    );

                    // 入金情報
                    $json['入金情報'] = [];
                    foreach ($creditedDetailInfo as $value) {

                        //現金
                        if ($value['cash'] != null && $value['cash'] > 0) {
                            $json['入金情報'][] = [
                                '入金日' => $value['credited_date'],
                                '入金番号' => $value['credited_no'],
                                '種別' => '現金',
                                '卸入金額' => $value['cash'],
                            ];
                        }
                        //小切手
                        if ($value['cheque'] != null && $value['cheque'] > 0) {
                            $json['入金情報'][] = [
                                '入金日' => $value['credited_date'],
                                '入金番号' => $value['credited_no'],
                                '種別' => '小切手',
                                '卸入金額' => $value['cheque'],
                            ];
                        }
                        //振込
                        if ($value['transfer'] != null && $value['transfer'] > 0) {
                            $json['入金情報'][] = [
                                '入金日' => $value['credited_date'],
                                '入金番号' => $value['credited_no'],
                                '種別' => '振込',
                                '卸入金額' => $value['transfer'],
                            ];
                        }
                        //手形
                        if ($value['bills'] != null && $value['bills'] > 0) {
                            $json['入金情報'][] = [
                                '入金日' => $value['credited_date'],
                                '入金番号' => $value['credited_no'],
                                '種別' => '手形',
                                '卸入金額' => $value['bills'],
                            ];
                        }
                        //相殺
                        if ($value['offsets'] != null && $value['offsets'] > 0) {
                            $json['入金情報'][] = [
                                '入金日' => $value['credited_date'],
                                '入金番号' => $value['credited_no'],
                                '種別' => '相殺',
                                '卸入金額' => $value['offsets'],
                            ];
                        }
                    }
                    if (count($json['入金情報']) <= 0) {

                        $json['入金情報'][] = [
                            '入金日' => '',
                            '入金番号' => '',
                            '種別' => '',
                            '卸入金額' => '',
                        ];
                    }

                    // 請求明細情報
                    $json['請求明細情報'] = [];
                    foreach ($requestDetailInfo as $value) {

                        $json['請求明細情報'][] = [
                            '案件No' => $value['matter_no'],
                            '案件名' => $value['matter_name'],
                            '金額' => Common::roundDecimalSalesPrice($value['amount']),
                            '消費税' =>  Common::roundDecimalStandardPrice($value['amount'] * $taxRate),
                            '備考' => null,
                        ];
                    }

                    // 請求明細一覧情報 SalesDetail
                    $json['請求明細一覧'] = [];
                    $InvoiceListGroupByMatter = $InvoiceListInfo->groupBy('matter_no');

                    foreach ($InvoiceListGroupByMatter as $matterList) {
                        $detailData = [];
                        $constractionIdList = $matterList->groupBy('construction_id');
                        $matterTotal = 0;
                        $matterNo = $matterList[0]['matter_no'];
                        $matterName = $matterList[0]['matter_name'];;

                        foreach ($constractionIdList as $List) {

                            // 親見積IDごと
                            $layers['total']    // 小計,合計
                                = $layers['cnt']    // 階層配下の明細数（階層配下の小計,合計、 自階層の小計,合計含む）
                                = $List->where('layer_flg', config('const.flg.on'))->mapWithKeys(function ($item) {
                                    return [$item['quote_detail_id'] => 0];
                                })->toArray();

                            // 工事区分の親見積明細IDは0なのでセット
                            $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
                                = $layers['cnt'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
                                = 0;

                            $maxDepth = $List->max('depth');
                            for ($i = $maxDepth; $i > -1; $i--) {
                                // 見積明細取得（深さ別）
                                $detailsByDepth = $List->where('depth', $i);
                                foreach ($detailsByDepth as $key => $detail) {
                                    if ($detail->layer_flg) {
                                        // 階層
                                        if ($detail->sales_use_flg) {
                                            // 販売額使用
                                            if ($detail->sales_id != null && $detail->sales_detail_id != null) {
                                                $layers['total'][$detail->quote_detail_id] = $detail->sales_total;              // 自合計書き換え
                                                $layers['total'][$detail->parent_quote_detail_id] += $detail->sales_total;      // 親合計に販売額を加算
                                            } else {
                                                $layers['total'][$detail->quote_detail_id] = 0;                                 // 自合計書き換え
                                                $layers['total'][$detail->parent_quote_detail_id] += 0;                         // 親合計に販売額を加算
                                            }
                                        } else {
                                            // 販売額不使用
                                            $layers['total'][$detail->parent_quote_detail_id] += $layers['total'][$detail->quote_detail_id]; // 親合計に自合計を加算
                                        }

                                        // 階層は小計,合計行を含んでカウントする為、+1ではなく+2
                                        $layers['cnt'][$detail->quote_detail_id] = $layers['cnt'][$detail->quote_detail_id] + 2;
                                        $layers['cnt'][$detail->parent_quote_detail_id] = $layers['cnt'][$detail->parent_quote_detail_id] + $layers['cnt'][$detail->quote_detail_id];
                                    } else {
                                        // 明細
                                        $layers['total'][$detail->parent_quote_detail_id] += $detail->sales_total;          // 親合計に販売額を加算
                                        $layers['cnt'][$detail->parent_quote_detail_id]++;
                                    }
                                }
                            }

                            // 出力データに見積データを格納する為の再帰処理
                            // 出力データ
                            $template = clone $SalesDetail;
                            Common::initModelProperty($template);
                            $insRec = function ($childRecords) use (&$insRec, &$detailData, $List, $layers, $template) {
                                foreach ($childRecords as $key => $record) {
                                    $tmpArray = array_keys($childRecords->toArray());
                                    $endKey = array_pop($tmpArray);
                                    // 明細印字フラグが立っている
                                    if ($record->details_c_flg) {
                                        $detailData[] = $this->formatRec($record, $List);
                                    }


                                    $template->product_name = config('const.quoteReport.product_name.subtotal');
                                    if ($record->layer_flg == config('const.flg.on')) {
                                        // 階層
                                        // 子データ取得
                                        $grandChildRecords = $List->where('parent_quote_detail_id', $record->quote_detail_id)->sortBy('seq_no');
                                        if ($grandChildRecords->count() > 0 && !is_null($record->quote_detail_id)) {
                                            // 子データが存在
                                            $insRec($grandChildRecords);
                                        } else {
                                            // 子データが存在しない
                                            // 『小計』行印字, 明細印字フラグ・売価印字フラグがたっている
                                            if ($record->details_c_flg && $record->selling_c_flg && $record->row_print_flg) {
                                                $template->construction_id = $record->construction_id;
                                                $template->sales_total = $layers['total'][$record->quote_detail_id];
                                                $template->depth = $record->depth;
                                                $template->tree_path = $record->tree_path;
                                                $detailData[] = $this->formatRec($template, $List);
                                            }
                                        }
                                    } else {
                                        // 明細
                                        $parentQuoteDetail = $List->firstWhere('quote_detail_id', $record->parent_quote_detail_id);
                                        $maxSalesDetail = $childRecords->where('quote_detail_id', $record->quote_detail_id)->max('sales_detail_id');

                                        // 『小計』行印字
                                        // 親階層の明細印字フラグ・売価印字フラグがたっている
                                        // 深さ2以上（深さ0=工事区分には小計行を作成しない）
                                        // 同一階層のソート最大値(seq_no) == レコードのソート(seq_no)　→　階層ごとの最終行
                                        if (
                                            $parentQuoteDetail->details_p_flg && $parentQuoteDetail->selling_p_flg &&
                                            // $childRecords->max('seq_no') == $record->seq_no
                                            $endKey == $key 
                                            && $record->depth > 1 && $parentQuoteDetail->price_print_flg
                                            && $parentQuoteDetail->row_print_flg && $maxSalesDetail == $record->sales_detail_id
                                        ) {
                                            $template->construction_id = $parentQuoteDetail->construction_id;
                                            $template->sales_total = $layers['total'][$record->parent_quote_detail_id];
                                            $template->product_name = "【" . $parentQuoteDetail->product_name . " 小計】";
                                            $template->depth = $record->depth - 1;
                                            $template->tree_path = $List->firstWhere('quote_detail_id', $record->parent_quote_detail_id)->tree_path;
                                            $detailData[] = $this->formatRec($template, $List);
                                        }
                                    }
                                }
                            };

                            Common::initModelProperty($template);
                            $detailsDepth0 = $List->where('depth', 0)->sortBy('seq_no');
                            // 見積明細の最初は各工事種別の合計値
                            foreach ($detailsDepth0 as $key => $detail) {
                                // if ($isTarget) {
                                //     // 指定印刷の場合は階層金額を変更する
                                //     $detail->sales_total = $layers['total'][$detail->quote_detail_id];
                                // }

                                //子明細取得
                                $childRecords = $List->where('parent_quote_detail_id', $detail->quote_detail_id)->sortBy('seq_no');
                                foreach ($childRecords as $key => $value) {
                                    if (
                                        $detail->sales_detail_id != null &&
                                        $childRecords[$key]['sales_detail_id'] != null &&
                                        $childRecords[$key]['parent_quote_detail_id'] == 0 &&
                                        $childRecords[$key]['quote_detail_id'] == 0 &&
                                        $childRecords[$key]['sales_detail_id'] !== $detail->sales_detail_id
                                    ) {
                                        unset($childRecords[$key]);
                                    }
                                }

                                if ($detail->sales_detail_id != null || count($childRecords) > 0) {
                                    //工事種別名の表示
                                    if ($detail->construction_name != null && $detail->construction_name != '') {
                                        $constractionRow = clone $SalesDetail;
                                        Common::initModelProperty($constractionRow);
                                        $constractionRow->delivery_no =  '【' . $detail->construction_name . '】';
                                        $detailData[] = $this->formatRec($constractionRow, $List, true);
                                    }
                                    // //階層表示
                                    // if (!$detail->sales_use_flg) {
                                    //     $detail->sales_total = null;
                                    // } else {
                                    //     $detail->sales_total = $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')];
                                    // }
                                    // $detailData[] = $this->formatRec($detail, $List, true);
                                }


                                Common::initModelProperty($template);

                                // 明細行印字フラグがたっている
                                if ($detail->row_print_flg && $detail->tree_path != 0) {
                                    $detailData[] = $this->formatRec($detail, $List);
                                }
                                $insRec($childRecords);
                                // $template->construction_id = $detail->construction_id;
                                // $template->product_name = '【' .  $detail->product_name . '　小計】';
                                // $template->sales_total = $layers['total'][$detail->quote_detail_id];
                                // $template->depth = $detail->depth;
                                // $template->tree_path = $detail->tree_path;
                                // // 『合計』行印字, 明細行印字フラグ・売価印字フラグがたっている
                                // if ($detail->row_print_flg && $detail->price_print_flg) {
                                //     $detailData[] = $this->formatRec($template, $List);
                                // }
                            }



                            // 工事区分合計
                            $template->product_name = '【' . $List[0]['construction_name'] . '　小計】';
                            $template->sales_total = $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')];
                            $matterTotal += $template->sales_total;
                            $template->depth = config('const.quoteConstructionInfo.depth');
                            $template->tree_path = config('const.quoteConstructionInfo.tree_path');
                            $detailData[] = $this->formatRec($template, $List, true);
                        }

                        //案件合計
                        $template->product_name = '【' . mb_strimwidth($matterName, 0, 20) . '　総合計】';
                        $template->sales_total = $matterTotal;
                        $template->depth = config('const.quoteConstructionInfo.depth');
                        $template->tree_path = config('const.quoteConstructionInfo.tree_path');
                        $detailData[] = $this->formatRec($template, $List, true);


                        $detailData = $this->processPageBreak($detailData, $layers);

                        foreach ($detailData as $value) {

                            $json['請求明細一覧'][] = [
                                '納品日' => $value['delivery_date'],
                                '納品No' => $value['delivery_no'],
                                '商品番号' => $value['product_code'],
                                '商品名' => $value['product_info_r1'],
                                '型式規格' => $value['product_info_r2'],
                                '数量' => $value['sales_quantity'],
                                '単位' => $value['unit'],
                                '単価' => $value['sales_unit_price'],
                                '金額' => $value['sales_total'],
                                '備考' => $value['memo'],
                                '案件No' => $matterNo,
                                '案件名' => $matterName,
                            ];
                        }
                    }

                    $invoiceData[] = $json;
                }
            }

            DB::commit();

            if (count($invoiceData) > 0) {
                $result['invoiceData'] = $invoiceData;
                $result['status'] = true;
            } else {
                $result['status'] = false;
            }

            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }


    /**
     * 印刷用にフォーマット
     *
     * @param [type] $value
     * @param boolean $isReal
     * @return void
     */
    private function formatRec($value, $quoteDetails, $isSummaryPage = false)
    {
        // ActiveReportsJSが半角スペースを勝手に省略するので基本的に全角スペース
        // nullやemptyを結合したらActiveReportsJSで半角スペースが印字されている為、三項演算子で分岐など 
        $salesTotal = $value['sales_total'] === null || $value['sales_total'] === '' ? null : (int)$value['sales_total'];
        $data = [
            'quote_detail_id' => null,
            'construction_id' => ($isSummaryPage) ? 0 : $value->construction_id,
            'product_info_r1' => str_repeat('　', $value->depth),
            'product_info_r2' => str_repeat('　', $value->depth),
            'product_code' => $value->product_id == 0 ? '' : ($value->product_code !== '?' ?  $value->product_code : ''),
            'product_name' => $value->product_name,
            'model' => $value->model,
            'unit' => $value->unit,
            'memo' => $value->memo,
            'sales_quantity' => null,
            'sales_unit_price' => null,
            'sales_total' => $salesTotal,
            'is_layer' => false,
            'matter_no' => $value->matter_no,
            'matter_name' => $value->matter_name,
            'delivery_date' => $value->delivery_date,
            'delivery_no' => $value->delivery_no,
            'remarks' => null,
        ];

        // 小計、合計行ではない場合の処理（金額隠しの処理が必要）
        if ($value->quote_detail_id || $value->sales_detail_id) {
            $data['quote_detail_id'] = $value->quote_detail_id;
            // 数値系は文字になっているのでintキャストする　※見積書にカンマがつかない
            $data['sales_quantity'] = (float)$value->sales_quantity;
            $data['sales_unit_price'] = (int)$value->sales_unit_price;
            // 目隠し処理など
            if ($isSummaryPage) {
                // サマリページはただの明細という扱いで処理したいので、元データが階層行でも階層フラグをたてない
                $data['product_info_r1'] = $value->product_name;
                $data['sales_unit_price'] = null;
            } else {


                $data['sales_quantity'] = (float)$value->sales_quantity;
                $data['sales_unit_price'] = (int)$value->sales_unit_price;
                $data['matter_no'] = $value->matter_no;
                $data['matter_name'] = $value->matter_name;
                $data['delivery_date'] = $value->delivery_date;
                $data['delivery_no'] = $value->delivery_no;


                if ($value->layer_flg) {
                    // 階層
                    $data['is_layer'] = true;
                    $data['product_info_r1'] = $value->product_name;
                    $data['product_info_r2'] = $value->model;
                } else {
                    // 明細
                    $data['product_info_r1'] = $value->product_name;
                    $data['product_info_r2'] = $value->model;
                }

                // 階層行 Or 売価印字フラグが立っていない
                // if (!$value->selling_c_flg) {
                if (($value->layer_flg && !$value->sales_use_flg) || !$value->selling_c_flg) {
                    $data['sales_unit_price'] = null;
                    $data['sales_total'] = null;
                }

                if ($value->sales_use_flg && $value->sales_id == null && $value->sales_detail_id == null) {
                    $data['sales_unit_price'] = null;
                    $data['sales_total'] = null;
                }
            }
        } else {
            $data['product_info_r2'] = $value->product_name;
            $data['delivery_no'] = $value->delivery_no;
        }

        // // 親階層の売価印字フラグをチェック
        // foreach (explode('_', $value->tree_path) as $pQuoteDetailId) {
        //     $quoteDetail = $quoteDetails->firstWhere('quote_detail_id', $pQuoteDetailId);
        //     // 存在する && 売価印字フラグがたっていない
        //     if ($quoteDetail && !$quoteDetail->selling_p_flg) {
        //         $data['sales_unit_price'] = null;
        //         $data['sales_total'] = null;
        //         break;
        //     }
        // }

        return $data;
    }

    /**
     * 改ページ処理
     *
     * @param [type] $data
     * @param [type] $layers
     * @return void
     */
    private function processPageBreak($data, $layers)
    {
        $NUMBER_OF_LINES = config('const.quoteReport.number_of_lines');
        $EMPTY_RECORD = [
            'product_info_r1' => null, 'product_info_r2' => null, 'product_name' => null, 'model' => null,
            'unit' => null, 'sales_quantity' => null, 'sales_unit_price' => null, 'sales_total' => null, 'memo' => null,
        ];

        $printData = [];
        for ($i = 0, $lineCnt = 0; $i < count($data); $i++, $lineCnt++) {
            // 既定行数に達したらカウントリセット
            if ($NUMBER_OF_LINES == $lineCnt) {
                $lineCnt = 0;
            }

            // // 明細の1行目は改ページしない
            // if ($lineCnt != 0) {
            //     // 指定条件の場合、改ページとカウントリセットを行う
            //     if (
            //         // 工事区分IDが前読込んだ工事区分IDと異なる
            //         ($data[$i]['construction_id'] != $data[$i-1]['construction_id'])
            //         // 階層行 && 明細数のオーバーフローを許容しない && 自階層の明細数(自階層小計含む)が見積書明細の残行数を上回っている 
            //         || ($data[$i]['is_layer'] && !$layers['forgive_overflow'][$data[$i]['quote_detail_id']] && ($layers['cnt'][$data[$i]['quote_detail_id']] > ($NUMBER_OF_LINES-$lineCnt)))
            //     ){
            //         // 改ページ処理
            //         for ($j=0; $j < ($NUMBER_OF_LINES - $lineCnt); $j++) { 
            //             $printData[] = $EMPTY_RECORD;
            //         }
            //         // カウントリセット
            //         $lineCnt = 0;
            //     }
            // }

            // // 階層 & 許容フラグがたっていない
            // if ($data[$i]['is_layer'] && !$layers['forgive_overflow'][$data[$i]['quote_detail_id']]) {
            //     for ($j=$i; $j < count($data); $j++) { 
            //         // 明細行ならbreak
            //         if (!$data[$j]['is_layer']) {
            //             break;
            //         }
            //         // 階層に対して明細数のオーバーフローを許容するフラグをたてる
            //         $layers['forgive_overflow'][$data[$j]['quote_detail_id']] = config('const.flg.on');
            //     }
            // }
            $printData[] = $data[$i];
        }
        return $printData;
    }


    /**
     * 表示用請求書発行
     *
     * @param Request $request
     * @return void
     */
    public function showPdf(Request $request)
    {
        // リクエストデータ取得
        $params = $request->request->all();
        $data = $params['data'];

        $Requests = new Requests();
        $SalesDetail = new SalesDetail();
        $Sales = new Sales();
        $Credited = new Credited();
        $CreditedDetail = new CreditedDetail();
        $Company = new Company();
        $SalesDetail = new SalesDetail();
        $ReceivablesHistory = new ReceivablesHistory();
        $NumberManage = new NumberManage();
        $LockManage = new LockManage();
        // 期、税率取得
        $System = new System();
        $systemList = $System->getByPeriod();
        $period = null;
        $taxRate = 1;
        if ($systemList != null) {
            $period = $systemList['period'];
            $taxRate =  $systemList['tax_rate'] / 100;
        }

        try {
            $request_id = $data['request_id'];
            $customer_id = $data['customer_id'];
            // $status = $data['status'];   
            $request_mon = str_replace("月", "", str_replace("/", "", $data['request_mon']));

            //請求情報更新
            $updInfo = $Requests->getById($request_id);
            // $updParams['id'] = $request_id;
            $request_s_day = $updInfo['request_s_day'];
            $request_s_day = $request_s_day == null ? null : date('Y-m-d',  strtotime($request_s_day));
            $request_e_day = $updInfo['request_e_day'];
            $request_e_day = $request_e_day == null ? null : date('Y-m-d',  strtotime($request_e_day));
            $creData = $Credited->getSalesDataInPeriod($customer_id, $request_s_day, $request_e_day);
            $status = $updInfo['status'];
            
            // 「未確定」「印刷可」の場合は違算、売掛を更新
            if ($status == 0 || $status == 1) {
                $updateRequestInfo = [];
                // 請求の更新
                $updateRequestInfo['id'] = $updInfo['id'];
                // 違算、売掛、繰越
                $tmpCarryForwardAmount                    = $this->getCarryforwardAmount($updInfo['customer_id']);
                $updateRequestInfo['different_amount']    = $tmpCarryForwardAmount['different_amount'];
                $updateRequestInfo['receivable']          = $tmpCarryForwardAmount['receivable'];
                $updateRequestInfo['carryforward_amount'] = $tmpCarryForwardAmount['carryforward_amount'];

                // 入金額
                $updateRequestInfo['deposit_amount']      = Common::nullorBlankToZero($creData['total_deposit']);
                // 相殺その他
                $updateRequestInfo['offset_amount']       = Common::nullorBlankToZero($creData['offset_ets']);

                // 税額
                $updateRequestInfo['consumption_tax_amount']    = $this->roundTax($updInfo['sales'] * $taxRate);
                // 税込当月売上合計 = 当月売上高 + 税額 + 税調整額 + 違算 + (-相殺)
                $updateRequestInfo['total_sales']               = $updInfo['sales'] + $updateRequestInfo['consumption_tax_amount'] + $updInfo['discount_amount'] + $updateRequestInfo['different_amount'] + ($tmpCarryForwardAmount['payment_offset'] * -1);
                // 税込当月請求合計 = 税込当月売上合計 + 売掛金
                $updateRequestInfo['request_amount']            = $updateRequestInfo['total_sales'] + $updateRequestInfo['receivable'];

                $saveResult = $Requests->updateById($updateRequestInfo['id'], $updateRequestInfo);
            }

            //請求書情報取得
            $requestInfo = $Requests->getInvoiceData($data);
            $data['request_s_day'] = $requestInfo['request_s_day'] ? $requestInfo['request_s_day'] : 0;
            $data['request_e_day'] = $requestInfo['request_e_day'] ? $requestInfo['request_e_day'] : 0;
            $salesDetailInfo = $SalesDetail->getInvoiceData(null, $customer_id, $request_s_day, $request_e_day);
            $requestDetailInfo = $SalesDetail->getInvoiceDetailDataByCustomerId($customer_id, $request_s_day, $request_e_day);
            $creditedDetailInfo = $CreditedDetail->getInvoiceData($customer_id, $request_s_day, $request_e_day);
            $InvoiceListInfo = $Sales->getInvoiceListDataByCustomerId($customer_id, $request_s_day, $request_e_day, $status);

            $companyInfo = $Company
                ->from('m_company as c')
                ->selectRaw('
                c.*
            ')
                ->first();

            //口座種別
            $account_type1 = null;
            if ($requestInfo['account_type1'] == 1) {
                $account_type1 = "普通";
            } else if ($requestInfo['account_type1'] == 2) {
                $account_type1 = "当座";
            }
            $account_type2 = null;
            if ($requestInfo['account_type2'] == 1) {
                $account_type2 = "普通";
            } else if ($requestInfo['account_type2'] == 2) {
                $account_type2 = "当座";
            }

            $json = array(
                'request_id' => $request_id,
                'sales' => $requestInfo['sales'],
                //ヘッダ
                'ヘッダ' => [
                    '請求先郵便番号' => substr($requestInfo['zipcode'], 0, 3) . '-' . substr($requestInfo['zipcode'], 3, 4),
                    '請求先住所' => $requestInfo['address1'] . $requestInfo['address2'],
                    '請求先名' => $requestInfo['customer_name'],
                    'お客様コード' =>  $requestInfo['customer_code'],
                    '締切日' =>  $requestInfo['request_day'],
                    '発行日' =>  $requestInfo['print_day'],
                    '請求No' =>  $requestInfo['request_no'],
                    '自社名' =>  $companyInfo['company_name'],
                    '自社支店名' =>  $requestInfo['department_name'],
                    '自社郵便番号' =>  substr($requestInfo['base_zipcode'], 0, 3) . '-' . substr($requestInfo['base_zipcode'], 3, 4),
                    '自社住所' => $requestInfo['base_address1'] . $requestInfo['base_address2'],
                    '自社電話番号' =>  $requestInfo['department_tel'],
                    '自社FAX番号' =>  $requestInfo['department_fax'],
                    '自社振込先銀行名1' =>  $requestInfo['bank_name1'] . '　' .  $requestInfo['branch_name1'],
                    '自社振込先銀行名2' =>  $requestInfo['bank_name2'] . '　' .  $requestInfo['branch_name2'],
                    '自社振込先口座番号1' => $account_type1 . '　' . $requestInfo['account_number1'],
                    '自社振込先口座番号2' => $account_type2 . '　' .  $requestInfo['account_number2'],
                    '自社振込先会社名1' =>  $requestInfo['account_name1'],
                    '自社振込先会社名2' =>  $requestInfo['account_name2'],
                    '担当者名' =>  $requestInfo['staff_name'],
                    '印鑑' =>  null,
                ],
                //請求情報
                '請求情報' => [
                    '前回ご請求額' => $requestInfo['carryforward_amount'],
                    'ご入金額' => $requestInfo['deposit_amount'] + $requestInfo['offset_amount'],
                    '繰越額' => $requestInfo['next_carryforward_amount'],
                    '今回お買上額' => $requestInfo['sales'],
                    '消費税額' => $requestInfo['consumption_tax_amount'] + $requestInfo['discount_amount'],
                    '今回請求額' => $requestInfo['next_carryforward_amount'] + $requestInfo['sales'] + $requestInfo['consumption_tax_amount'] + $requestInfo['discount_amount']
                ]
            );

            // 入金情報
            $json['入金情報'] = [];
            foreach ($creditedDetailInfo as $value) {

                //現金
                if ($value['cash'] != null && $value['cash'] > 0) {
                    $json['入金情報'][] = [
                        '入金日' => $value['credited_date'],
                        '入金番号' => $value['credited_no'],
                        '種別' => '現金',
                        '卸入金額' => $value['cash'],
                    ];
                }
                //小切手
                if ($value['cheque'] != null && $value['cheque'] > 0) {
                    $json['入金情報'][] = [
                        '入金日' => $value['credited_date'],
                        '入金番号' => $value['credited_no'],
                        '種別' => '小切手',
                        '卸入金額' => $value['cheque'],
                    ];
                }
                //振込
                if ($value['transfer'] != null && $value['transfer'] > 0) {
                    $json['入金情報'][] = [
                        '入金日' => $value['credited_date'],
                        '入金番号' => $value['credited_no'],
                        '種別' => '振込',
                        '卸入金額' => $value['transfer'],
                    ];
                }
                //手形
                if ($value['bills'] != null && $value['bills'] > 0) {
                    $json['入金情報'][] = [
                        '入金日' => $value['credited_date'],
                        '入金番号' => $value['credited_no'],
                        '種別' => '手形',
                        '卸入金額' => $value['bills'],
                    ];
                }
                //相殺
                if ($value['offsets'] != null && $value['offsets'] > 0) {
                    $json['入金情報'][] = [
                        '入金日' => $value['credited_date'],
                        '入金番号' => $value['credited_no'],
                        '種別' => '相殺',
                        '卸入金額' => $value['offsets'],
                    ];
                }
            }
            if (count($json['入金情報']) <= 0) {
                $json['入金情報'][] = [
                    '入金日' => '',
                    '入金番号' => '',
                    '種別' => '',
                    '卸入金額' => '',
                ];
            }

            // 請求明細情報
            $json['請求明細情報'] = [];
            foreach ($requestDetailInfo as $value) {

                $json['請求明細情報'][] = [
                    '案件No' => $value['matter_no'],
                    '案件名' => $value['matter_name'],
                    '金額' => Common::roundDecimalSalesPrice($value['amount']),
                    '消費税' =>  Common::roundDecimalStandardPrice($value['amount'] * $taxRate),
                    '備考' => null,
                ];
            }

            // 請求明細一覧情報 SalesDetail
            $json['請求明細一覧'] = [];
            $InvoiceListGroupByMatter = $InvoiceListInfo->groupBy('matter_no');

            foreach ($InvoiceListGroupByMatter as $matterList) {
                $detailData = [];
                $constractionIdList = $matterList->groupBy('construction_id');
                $matterTotal = 0;
                $matterNo = $matterList[0]['matter_no'];
                $matterName = $matterList[0]['matter_name'];;

                foreach ($constractionIdList as $List) {

                    // 親見積IDごと
                    $layers['total']    // 小計,合計
                        = $layers['cnt']    // 階層配下の明細数（階層配下の小計,合計、 自階層の小計,合計含む）
                        = $List->where('layer_flg', config('const.flg.on'))->mapWithKeys(function ($item) {
                            return [$item['quote_detail_id'] => 0];
                        })->toArray();

                    // 工事区分の親見積明細IDは0なのでセット
                    $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
                        = $layers['cnt'][config('const.quoteConstructionInfo.parent_quote_detail_id')]
                        = 0;

                    $maxDepth = $List->max('depth');
                    for ($i = $maxDepth; $i > -1; $i--) {
                        // 見積明細取得（深さ別）
                        $detailsByDepth = $List->where('depth', $i);
                        foreach ($detailsByDepth as $key => $detail) {
                            if (Common::isFlgOn($detail->layer_flg) || Common::isFlgOn($detail->rtn_flg) || ($detail->delivery_date !== null && !Common::isFlgOn($detail->layer_flg))) {
                                if ($detail->layer_flg) {
                                    // 階層
                                    if ($detail->sales_use_flg) {
                                        // 販売額使用
                                        if ($detail->sales_id != null && $detail->sales_detail_id != null) {
                                            $layers['total'][$detail->quote_detail_id] = $detail->sales_total;                           // 自合計書き換え
                                            $layers['total'][$detail->parent_quote_detail_id] += $detail->sales_total;      // 親合計に販売額を加算
                                        } else {
                                            $layers['total'][$detail->quote_detail_id] = 0;                           // 自合計書き換え
                                            $layers['total'][$detail->parent_quote_detail_id] += 0;      // 親合計に販売額を加算
                                        }
                                    } else {
                                        // 販売額不使用
                                        $layers['total'][$detail->parent_quote_detail_id] += $layers['total'][$detail->quote_detail_id]; // 親合計に自合計を加算
                                    }

                                    // 階層は小計,合計行を含んでカウントする為、+1ではなく+2
                                    $layers['cnt'][$detail->quote_detail_id] = $layers['cnt'][$detail->quote_detail_id] + 2;
                                    $layers['cnt'][$detail->parent_quote_detail_id] = $layers['cnt'][$detail->parent_quote_detail_id] + $layers['cnt'][$detail->quote_detail_id];
                                } else {
                                    // 明細
                                    $layers['total'][$detail->parent_quote_detail_id] += $detail->sales_total;          // 親合計に販売額を加算
                                    $layers['cnt'][$detail->parent_quote_detail_id]++;
                                }
                            }
                        }
                    }

                    // 出力データに見積データを格納する為の再帰処理
                    // 出力データ
                    $template = clone $SalesDetail;
                    Common::initModelProperty($template);
                    $insRec = function ($childRecords) use (&$insRec, &$detailData, $List, $layers, $template) {
                        foreach ($childRecords as $key => $record) {
                            $tmpArray = array_keys($childRecords->toArray());
                            $endKey = array_pop($tmpArray);
                            // 明細印字フラグが立っている
                            if ($record->details_c_flg && (Common::isFlgOn($record->layer_flg) || Common::isFlgOn($record->rtn_flg) || ($record->delivery_date !== null && !Common::isFlgOn($record->layer_flg)))) {
                                $detailData[] = $this->formatRec($record, $List);
                            }

                            $template->product_name = config('const.quoteReport.product_name.subtotal');
                            if ($record->layer_flg == config('const.flg.on')) {
                                // 階層
                                // 子データ取得
                                $grandChildRecords = $List->where('parent_quote_detail_id', $record->quote_detail_id)->sortBy('seq_no');
                                if ($grandChildRecords->count() > 0 && !is_null($record->quote_detail_id)) {
                                    // 子データが存在
                                    $insRec($grandChildRecords);
                                } else {
                                    // 子データが存在しない
                                    // 『小計』行印字, 明細印字フラグ・売価印字フラグがたっている
                                    if ($record->details_c_flg && $record->selling_c_flg && $record->row_print_flg) {
                                        $template->construction_id = $record->construction_id;
                                        $template->sales_total = $layers['total'][$record->quote_detail_id];
                                        $template->depth = $record->depth;
                                        $template->tree_path = $record->tree_path;
                                        $detailData[] = $this->formatRec($template, $List);
                                    }
                                }
                            } else {
                                // 明細
                                $parentQuoteDetail = $List->firstWhere('quote_detail_id', $record->parent_quote_detail_id);
                                $maxSalesDetail = $childRecords->where('quote_detail_id', $record->quote_detail_id)->max('sales_detail_id');

                                // 『小計』行印字
                                // 親階層の明細印字フラグ・売価印字フラグがたっている
                                // 深さ2以上（深さ0=工事区分には小計行を作成しない）
                                // 同一階層のソート最大値(seq_no) == レコードのソート(seq_no)　→　階層ごとの最終行
                                if (
                                    $parentQuoteDetail->details_p_flg && $parentQuoteDetail->selling_p_flg &&
                                    // $childRecords->max('seq_no') == $record->seq_no 
                                    $endKey == $key
                                    && $record->depth > 1 && $parentQuoteDetail->price_print_flg
                                    && $parentQuoteDetail->row_print_flg && $maxSalesDetail == $record->sales_detail_id
                                ) {
                                    $template->construction_id = $parentQuoteDetail->construction_id;
                                    $template->sales_total = $layers['total'][$record->parent_quote_detail_id];
                                    $template->product_name = "【" . $parentQuoteDetail->product_name . " 小計】";
                                    $template->depth = $record->depth - 1;
                                    $template->tree_path = $List->firstWhere('quote_detail_id', $record->parent_quote_detail_id)->tree_path;
                                    $detailData[] = $this->formatRec($template, $List);
                                }
                            }
                        }
                    };

                    Common::initModelProperty($template);
                    $detailsDepth0 = $List->where('depth', 0)->sortBy('seq_no');
                    // 見積明細の最初は各工事種別の合計値
                    foreach ($detailsDepth0 as $key => $detail) {
                        // if ($isTarget) {
                        //     // 指定印刷の場合は階層金額を変更する
                        //     $detail->sales_total = $layers['total'][$detail->quote_detail_id];
                        // }

                        //子明細取得
                        $childRecords = $List->where('parent_quote_detail_id', $detail->quote_detail_id)->sortBy('seq_no');
                        foreach ($childRecords as $key => $value) {
                            if (
                                $detail->sales_detail_id != null &&
                                $childRecords[$key]['sales_detail_id'] != null &&
                                $childRecords[$key]['parent_quote_detail_id'] == 0 &&
                                $childRecords[$key]['quote_detail_id'] == 0 &&
                                $childRecords[$key]['sales_detail_id'] !== $detail->sales_detail_id
                            ) {
                                unset($childRecords[$key]);
                            }
                        }

                        if ($detail->sales_detail_id != null || count($childRecords) > 0) {
                            //工事種別名の表示
                            if ($detail->construction_name != null && $detail->construction_name != '') {

                                $constractionRow = clone $SalesDetail;
                                Common::initModelProperty($constractionRow);
                                $constractionRow->delivery_no =  '【' . $detail->construction_name . '】';
                                $detailData[] = $this->formatRec($constractionRow, $List, true);
                            }
                            // //階層表示
                            // if (!$detail->sales_use_flg) {
                            //     $detail->sales_total = null;
                            // } else {
                            //     $detail->sales_total = $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')];
                            // }
                            // $detailData[] = $this->formatRec($detail, $List, true);
                        }


                        Common::initModelProperty($template);
                        // 明細行印字フラグがたっている
                        if ($detail->row_print_flg && $detail->tree_path != 0) {
                            $detailData[] = $this->formatRec($detail, $List);
                        }
                        $insRec($childRecords);
                        // $template->construction_id = $detail->construction_id;
                        // $template->product_name = '【' .  $detail->product_name . '　小計】';
                        // $template->sales_total = $layers['total'][$detail->quote_detail_id];
                        // $template->depth = $detail->depth;
                        // $template->tree_path = $detail->tree_path;
                        // // 『合計』行印字, 売価印字フラグがたっている
                        // if ($detail->price_print_flg && $detail->tree_path != 0) {
                        //     $detailData[] = $this->formatRec($template, $List);
                        // }
                    }

                    // 工事区分合計
                    $template->product_name = '【' . $List[0]['construction_name'] . '　小計】';
                    $template->sales_total = $layers['total'][config('const.quoteConstructionInfo.parent_quote_detail_id')];
                    $matterTotal += $template->sales_total;
                    $template->depth = config('const.quoteConstructionInfo.depth');
                    $template->tree_path = config('const.quoteConstructionInfo.tree_path');
                    $detailData[] = $this->formatRec($template, $List, true);
                }

                //案件合計
                $template->product_name = '【' . mb_strimwidth($matterName, 0, 20) . '　総合計】';
                $template->sales_total = $matterTotal;
                $template->depth = config('const.quoteConstructionInfo.depth');
                $template->tree_path = config('const.quoteConstructionInfo.tree_path');
                $detailData[] = $this->formatRec($template, $List, true);


                $detailData = $this->processPageBreak($detailData, $layers);

                foreach ($detailData as $value) {

                    $json['請求明細一覧'][] = [
                        '納品日' => $value['delivery_date'],
                        '納品No' => $value['delivery_no'],
                        '商品番号' => $value['product_code'],
                        '商品名' => $value['product_info_r1'],
                        '型式規格' => $value['product_info_r2'],
                        '数量' => $value['sales_quantity'],
                        '単位' => $value['unit'],
                        '単価' => $value['sales_unit_price'],
                        '金額' => $value['sales_total'] === 0 && $value['quote_detail_id'] != null  && !$value['is_layer'] ? '★' : $value['sales_total'],
                        '備考' => $value['memo'],
                        '案件No' => $matterNo,
                        '案件名' => $matterName,
                    ];
                }
            }

            $result = $json;

            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            $result = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }


    /**
     * PDF保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function uploadPdf(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $result = true;
            $files = request()->file;

            $i = 0;
            $filePathList = [];
            foreach ($files as $file) {
                $file->storeAs(config('const.uploadPath.request') . '/' . $params['request_id'][$i], 'invoice_' . $params['request_id'][$i] . '.pdf');
                array_push(
                    $filePathList,
                    [
                        'fullPath' => str_replace('/', DIRECTORY_SEPARATOR, storage_path("app/" . config('const.uploadPath.request') . $params['request_id'][$i] . '/invoice_' . $params['request_id'][$i] . '.pdf')), 'fileName' => 'invoice_' . $params['request_id'][$i] . '.pdf'
                    ]
                );
                $i++;
            }

            //zipファイル生成
            $zipName = "請求書_" . Carbon::Now()->format("YmdHis") . '.zip';
            $zipPath = str_replace('/', DIRECTORY_SEPARATOR, storage_path("app/" . config('const.uploadPath.request') . $zipName));
            $headers = [['Content-Type' => 'application/zip']];
            $zip = new ZipArchive();
            $zip->open($zipPath, ZipArchive::CREATE);
            ob_end_clean();
            foreach ($filePathList as $filePath) {
                $zip->addFile($filePath['fullPath'], $filePath['fileName']);
            }
            $zip->close();

            return response()->download($zipPath, $zipName, $headers)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * PDF表示
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function openPdf(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $result = true;
            $files = request()->file;

            $i = 0;
            $filePathList = [];
            foreach ($files as $file) {
                $file->storeAs(config('const.uploadPath.request') . '/' . $params['request_id'][$i], 'invoice_' . $params['request_id'][$i] . '.pdf');
                array_push(
                    $pdfList,
                    [
                        'fullPath' => str_replace('/', DIRECTORY_SEPARATOR, storage_path("app/" . config('const.uploadPath.request') . $params['request_id'][$i] . '/invoice_' . $params['request_id'][$i] . '.pdf')), 'fileName' => 'invoice_' . $params['request_id'][$i] . '.pdf'
                    ]
                );
                $i++;
            }



            $headers = [['Content-Type' => 'application/pdf']];


            return response()->download($zipPath, $zipName, $headers)->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error($e);
            return false;
        }
    }

    /**
     * 店頭販売 削除
     *
     * @param Request $request
     * @return void
     */
    public function deleteRequest(Request $request)
    {
        $result = false;

        // リクエストから検索条件取得
        $params = $request->request->all();
        $requestId = $params['request_id'];

        $Requests = new Requests();
        $Sales = new Sales();
        $Credited = new Credited();

        DB::beginTransaction();
        try {
            // 請求テーブルを請求IDで検索し請求データに削除フラグを設定する。
            $Requests->softDeleteById($requestId);

            // 入金テーブルを請求IDで検索し削除フラグを設定する。
            // 入金テーブルのIDで、入金明細テーブルを検索し入金明細に削除フラグを設定する。
            $Credited->deleteWithDetailByRequestId($requestId);

            // 売上テーブルを請求IDで検索し削除フラグを設定する。
            // 削除フラグを設定した売上テーブルの明細の売上IDで売上明細テーブルを検索し削除フラグを設定する。
            $Sales->softDeleteByRequestId($requestId);

            $result = true;
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if($result['message'] === ''){
                $result['message'] = config('message.error.error');
            }
            Log::error($e);
        }

        return \Response::json($result);
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
     * 消費税の端数処理 TODO
     * @param $value
     */
    private function roundTax($value){
        return Common::roundDecimalSalesPrice($value);
    }

}
