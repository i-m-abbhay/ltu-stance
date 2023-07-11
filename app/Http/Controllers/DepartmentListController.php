<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Department;
use App\Models\StaffDepartment;
use App\Models\Staff;
use App\Models\Base;

/**
 * 部門一覧
 */
class DepartmentListController extends Controller
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
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // データ取得
        $Department = new Department();
        $Staff = new Staff();
        $Base = new Base();

        $departmentList = $Department->getComboList();
        $staffList = $Staff->getChiefStaffList();
        $baseList = $Base->getComboList();



        // 初期検索条件
        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $departmentName = $Department->getById($departmentId)['department_name'];
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_name' => $departmentName,
        ]);


        return view('Department.department-list')
                ->with('isEditable', $isEditable)
                ->with('departmentList', $departmentList)
                ->with('staffList', $staffList)
                ->with('baseList', $baseList)
                ->with('initSearchParams', $initSearchParams)
                ;
    }
    
    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            // 一覧データ取得
            $Department = new Department();
            $list = $Department->getDepartmentList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}