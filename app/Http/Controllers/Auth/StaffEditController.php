<?php

namespace App\Http\Controllers\Auth;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\User;
use App\Http\Controllers\Controller;
use App\Models\Staff;
use App\Models\Department;
use App\Models\General;
use App\Models\StaffDepartment;
use Validator;
use DB;
use Session;
use Log;
use Storage;
use Auth;
use App\Models\Authority;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class StaffEditController extends Controller
{
    const SCREEN_NAME = 'staff-edit';

     /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'staff_name', 
    //     'staff_kana',
    //     'staff_short_name',
    //     'employee_code',
    //     'position_code',
    //     'email', 
    //     'login_id',
    //     'password',
    //     'tel_1',
    //     'tel_2',
    //     'mobile_email',
    //     'retirement_kbn', 
    //     'del_flg',
    //     'created_user',
    //     'created_at',
    //     'update_user',
    //     'update_at',
    // ];

    /*
    |--------------------------------------------------------------------------
    | Staff Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/warehouse-list';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * 初期表示
     * @return type
     */
    public function index(Request $request, $id = null)
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $Staff = new Staff();
        $StaffDepartment = new StaffDepartment();
        $staffData = null;
        $stDepartData = null;
        $departmentData = null;
        $posData = null;
        $LockManage = new LockManage();
        $lockData = null;

        $categoryCode = config('const.general.pos');

        try{
            if (is_null($id)) {
                // 新規
                $staffData = $Staff;
                $stDepartData = $StaffDepartment;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $staffData = $Staff->getById($id);
                $stDepartData = $StaffDepartment->getById($id);
                
                $staffData['password'] = config('const.default.default');
                // 編集権限がある場合
                if ($isEditable === config('const.authority.has')) {
                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                    $keys = array($id);

                    DB::beginTransaction();
                    $lockCnt = 0;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // GETパラメータからモード取得
                        $mode = $request->query(config('const.query.mode'));
                        if (!$isLocked && $mode == config('const.mode.edit')) {
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
                        $isOwnLock = config('const.flg.on');
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                }
            }

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        // 部門リスト取得
        $Department = new Department();
        $departmentData = $Department->getList(array());

        // 役職リストの取得
        $General = new General();
        $posData = $General->getCategoryList($categoryCode);
    

        return view('Staff.staff-edit')
                ->with('staffData', $staffData)
                ->with('stDepartData', $stDepartData)
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('departmentData', $departmentData)
                ->with('posData', $posData)
                ;
    }


    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $rtnList = array('status' => false, 'msg' => '', 'url' => \Session::get('url.intended', url('/')));

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->all();
            $hasStamp = $request->hasFile('stamp');
            $uploadPath = config('const.uploadPath.staff_stamp');
            // 印影ファイル名取得
            if($hasStamp){
                $stampName = $request->file('stamp')->getClientOriginalName();
                $params['stamp'] = $stampName;
                $this->imageValid($request);
            }

            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }

            // バリデーションチェック
            $this->isValid($request);
        }
        

        DB::beginTransaction();
        $Staff = new Staff(); 
        try{
            if(!$hasEditAuth) {
                // 編集権限なし
                throw new \Exception();
            }
            $StaffDepartment = new StaffDepartment();
            $saveResult = false;
            $delLock = false;
            if ($newFlg) {
                // 登録
                $newStaffId = $Staff->add($params);
                $StaffDepartment->add($params, $newStaffId);
                if($hasStamp){
                    // アップロード
                    $request->file('stamp')->storeAs($uploadPath.$newStaffId, $stampName);
                }
                $saveResult = true;
                $delLock = true;
            } else {
                // 更新
                $Staff->updateById($params);
                $StaffDepartment->updateById($params);
                if($hasStamp){
                    // 担当者ID取得
                    $staffId = $params['id'];
                    // 前回の画像データ削除
                    Storage::deleteDirectory($uploadPath.$staffId);
                    // 画像アップロード
                    $request->file('stamp')->storeAs($uploadPath.$staffId, $stampName);
                }
                // ロック解放
                $keys = array($params['id']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
                $saveResult = true;
            }
            
            if ($saveResult && $delLock) {
                DB::commit();
                $rtnList['status'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));  
            }
        } catch (\Exception $e) {
            DB::rollback();
            $rtnList['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($rtnList);
    }


    /**
     * 論理削除
     *
     * @param Request $request
     * @return void
     */
    public function delete(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            // 編集権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
                throw new \Exception();
            }
            $Staff = new Staff();

            // 削除
            $deleteResult = $Staff->deleteById($params['id']);
            // ロック解放
            $keys = array($params['id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

            if ($deleteResult && $delLock) {
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
     * Get a validator for an incoming registration request.
     *
     * @param  $request
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function isValid(Request $request)
    {
        if($request->id){
            $unique = 'unique:m_staff,login_id,'.$request->id;
        }else {
            $unique = 'unique:m_staff,login_id';
        }

        return $this->validate($request, [
            'staff_name' => 'required|max:30',
            'staff_kana' => 'required|max:30',
            'staff_short_name' => 'max:3',
            'employee_code' => 'max:10',
            'position_code' => 'max:15',
            'email' => 'required|max:255',
            'login_id' => 'required|max:11|'.$unique,
            'password' => 'required|min:6',
            'retirement_kbn' => 'required',
            'department_main' => 'required',
        ]);
    }

    /**
     * 画像・バリデーション
     *
     * @param Request $request
     * @return void
     */
    protected function imageValid(Request $request)
    {
        return $this->validate($request, [
            'stamp' => 'image|mimes:jpeg,png,jpg',
        ]);
    }
}
