<?php
namespace App\Http\Controllers;

use DB;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Authority;
use App\Models\General;
use App\Models\StaffDepartment;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 権限管理
 */
class AuthorityEditController extends Controller
{

    const SCREEN_NAME = 'order-detail';
    const AUTHORITY_BINDING_PREFIX = 'authoritycode_';

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
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.authority.setting')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        $Department = new Department();
        $Staff = new Staff();
        $StaffDepartment = new StaffDepartment();
        $General = new General();

        // 部門
        $departmentData = $Department->getComboList();
        // 担当
        $staffData = $Staff->getComboList();
        // 担当者部門
        $staffDepartmentData = $StaffDepartment->getCurrentTermList();
        $staffDepartmentData = $staffDepartmentData->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });
        // 権限
        $authorityData = $General->getCategoryList(config('const.general.auth'));

        return view('Authority.authority-edit')
            ->with('authorityBindingPrefix', json_encode(self::AUTHORITY_BINDING_PREFIX))
            ->with('departmentData', $departmentData)
            ->with('staffData', $staffData)
            ->with('staffDepartmentData', $staffDepartmentData)
            ->with('authorityData', $authorityData)
        ;
    }
    
    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)  
    {
        $Staff = new Staff();
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            $staffAuthority = $Staff->getStaffAuthorityList($params);

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($staffAuthority);
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $result['status'] = true;

        // リクエストデータ取得
        $params = $request->request->all();
        $Authority = new Authority();

        DB::beginTransaction();
        try {
            $addList = [];
            $deleteList = [];

            $authorityAllData = $Authority->getAll();
            $gridData = json_decode($params['grid_data']);

            foreach ($gridData as $gKey => $gridRow) {
                foreach ($gridRow as $gRowKey => $gridValue) {
                    // keyに『authoritycode_』が含まれている場合
                    if (strpos($gRowKey, self::AUTHORITY_BINDING_PREFIX) === 0) {
                        // 『authoritycode_〇』権限コードを取得する
                        $authorityCode = explode('_', $gRowKey)[1];
                        // 担当者コードと権限コードでデータを検索
                        $findData = $authorityAllData->where('staff_id', $gridRow->staff_id)->where('authority_code', $authorityCode)->first();
                        
                        // チェック状態の真偽値で判定
                        if ($gridValue) {
                            // チェック状態：真
                            // 権限データ…[有]何もしない，[無]データ追加対象
                            if (!$findData) {
                                $addList[] = [
                                    'staff_id'=> $gridRow->staff_id,
                                    'authority_code'=> $authorityCode,
                                ];
                            }
                        }else{
                            // チェック状態：偽
                            // 権限データ…[有]データ物理削除対象，[無]何もしない
                            if ($findData) {
                                $deleteList[] = $findData->id;
                            }
                        }
                    }
                }
            }
            if($addList > 0){ $Authority->addList($addList); }
            if($deleteList > 0){ $Authority->physicalDeleteList($deleteList); }
            $authorityAllData = $Authority->getAll();
            if ($authorityAllData->where('authority_code', config('const.auth.authority.setting'))->count() == 0) {
                throw new \Exception(config('message.authority_edit.no_authority_holder'));
            }

            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_success', config('message.error.error'));
        }


        return \Response::json($result);
    }
}