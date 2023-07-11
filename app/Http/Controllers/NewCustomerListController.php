<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Matter;
use Session;
use Log;
use App\Models\Authority;
use App\Models\StaffDepartment;
use Auth;

class NewCustomerListController extends Controller
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
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // 得意先リスト取得
        $Customer = new Customer();
        $customerList = $Customer->getAllList();
        // 案件リスト取得
        $Matter = new Matter();
        $matterList = $Matter->getComboList();
        // 部門リスト取得
        $Department = new Department();
        $departmentList = $Department->getList(array());
         // 担当者リスト取得
        $Staff = new Staff();
        $staffList = $Staff->getComboList();
        // 担当者部門リスト取得
        $StaffDepartment = new StaffDepartment();
        $staffDepartmentList = $StaffDepartment->getCurrentTermList();



        return view('Customer.new-customer-list')
                ->with('isEditable', $isEditable)
                ->with('customerList', $customerList)
                ->with('matterList', $matterList)
                ->with('departmentList', $departmentList)
                ->with('staffDepartmentList', $staffDepartmentList)
                ->with('staffList', $staffList)
                ;
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
            $Customer = new Customer();
            $list = $Customer->getList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
    
}