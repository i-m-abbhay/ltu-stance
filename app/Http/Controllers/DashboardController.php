<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffDepartment;
use Auth;
use Carbon\Carbon;
use DB;
use Session;
use ZipArchive;
use App\Models\System;
use Storage;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\PeriodYear;
use App\Models\Requests;
use App\Models\Sales;
use App\Models\SalesDetail;
use Illuminate\Support\Facades\Log;

/**
 * ダッシュボード
 */
class DashboardController extends Controller
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
        $Customer = new Customer();
        $Staff = new Staff();
        $StaffDepartment = new StaffDepartment();
        $Department = new Department();

        // リスト取得
        $staffList = $Staff->getComboList();
        $departmentList = $Department->getComboList();
        $customerList = $Customer->getAllList();
        $staffDepartmentList = $StaffDepartment->getCurrentTermList();
        $staffDepartmentList = $staffDepartmentList->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });

        // 検索条件の初期値
        $departmentId = $StaffDepartment->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_id' => $departmentId,
            'sales_date' => Carbon::now()->format('Y/m/d'),
        ]);


        return view('dashboard')
                ->with('staffList', $staffList)
                ->with('departmentList', $departmentList)
                ->with('customerList', $customerList)
                ->with('staffDepartmentList', $staffDepartmentList)
                ->with('initSearchParams', $initSearchParams)
                ;
    }


    /**
     * 検索
     * 売上情報
     * @param Request $request
     * @return type
     */
    public function salesInfoSearchData(Request $request)
    {
        $result     = array('status' => false, 'message' => '', 'salesInfo' => null);
        $params     = $request->request->all();   
        
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $Requests = new Requests();
        $SystemUtil = new SystemUtil();
        $PeriodYear = new PeriodYear();

        try {
            $salesInfo = [];

            // 売上期間の取得
            $nowDate = Carbon::parse($params['sales_date'].'/1');
            $startDate = '';
            $endDate = '';
            if ($params['period_kbn'] == config('const.flg.off')) {
                // 当月
                // 対象月の初日
                $startDate = $nowDate->firstOfMonth()->format('Y/m/d');
                // 対象月の月末
                $endDate   = $nowDate->lastOfMonth()->format('Y/m/d');
            } else {
                // 全期
                // 年度取得
                $periodData = $PeriodYear->getPeriodYearByYmd($nowDate->format('Ymd'), 0);
                // 年度マスタに存在しない場合はエラー
                if ($periodData === null) {
                    $result['message'] = config('message.error.dashboard.undefined_period');
                    throw new \Exception($result['message']);
                }
                // 開始月
                $startDate = Carbon::parse($periodData['start_ymd'])->format('Y/m/d');
                // 終了月
                $endDate = Carbon::parse($periodData['end_ymd'])->format('Y/m/d');
            }

            // 売上情報取得
            $params['start_date'] = $startDate;
            $params['end_date'] = $endDate;

            // 売上額取得
            $salesAmountObj = $Requests->getSalesAmountByBetweenDate($params);

            $salesInfo['sales_amount'] = $salesAmountObj->sales_total;
            $result['salesInfo'] = $salesInfo;


            $result['status'] = true;

        } catch (\Exception $e) {
            $result['status'] = false;
            Log::error($e);
        }

        return \Response::json($result);
    }
    public function salesInfofetchData(Request $request)
    {
        
        $department_id=$request->input('department_id');
        $staff_id=$request->input('staff_id');
        $customer_id=$request->input('department_id');
        $sales_date=$request->input('department_id');
        $total_profit= DB::select("select sum(profit_total) as sum from t_sales where matter_department_id =$department_id limit 100");
        $total_sales= DB::select("select sum(sales_unit_price) as sum from t_sales where matter_department_id =$department_id limit 100");
        $sales_data=[
            'total_profit'=>$total_profit[0],
            'total_sales'=>$total_sales[0]
        ];
        return ($sales_data);
        // // return DB::table('t_sales')->where('matter_department_id','17');
        // return $request->input('department_id');
    }
}