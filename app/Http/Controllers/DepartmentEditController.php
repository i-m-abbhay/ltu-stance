<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Department;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Models\Base;
use App\Models\Staff;
use App\Models\OwnBank;

/**
 * 部門編集
 */
class DepartmentEditController extends Controller
{
    const SCREEN_NAME = 'department-edit';

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
     * @param Request $request
     * @param $id 拠点ID
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

        $Department = new Department();
        $departmentData = null;
        $LockManage = new LockManage();
        $lockData = null;
        $Base = new Base();
        $baseList = $Base->getComboList();
        $Department = new Department();
        $departmentList = $Department->getComboList();
        $OwnBank = new OwnBank();
        $bankList = $OwnBank->getComboList();
        foreach ($bankList as $key => $row) {
            $nm = '';

            $nm = $bankList[$key]['bank_name'];
            if (!empty($bankList[$key]['account_type'])) {
                $typeKey = $bankList[$key]['account_type'];
                $nm .= '／'. config('const.accountType.list.'. $typeKey);
            }
            if (!empty($bankList[$key]['account_number'])) {
                $nm .= '／'. $bankList[$key]['account_number'];
            }
            if (!empty($bankList[$key]['account_name'])) {
                $nm .= '／'. $bankList[$key]['account_name'];
            }
            $bankList[$key]['bank_name'] = $nm;
        }

        $Staff = new Staff();
        $staffList = $Staff->getComboList();

        try {
            if (is_null($id)) {
                // 新規
                $departmentData = $Department;
                Common::initModelProperty($departmentData);
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $departmentData = $Department->getById($id);
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

        return view('Department.department-edit') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('departmentData', $departmentData)
                ->with('baseList', $baseList)
                ->with('departmentList', $departmentList)
                ->with('bankList', $bankList)
                ->with('staffList', $staffList)
                ;
    }
    
    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;
        $validSts = true;

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;
            $errors = array(
                'department_name' => '',
                'base_id' => '',
                'parent_id' => '',
                'department_symbol' => '',
                'tel' => '',
                'fax' => '',
                'own_bank_id' => '',
                'chief_staff_id' => '',
                'standard_gross_profit_rate' => '',
                // 'standard_cost_total' => '',
            );
            
            // リクエストデータ取得
            $params = $request->request->all();

            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }

            // 銀行の重複チェック
            $checkArr = array(
                $params['own_bank_id_1'],
                $params['own_bank_id_2'],
                $params['own_bank_id_3'],
                $params['own_bank_id_4'],
                $params['own_bank_id_5']
            );
            $isSameCheck = $this->checkSame($checkArr);

            // 粗利率をfloatにキャスト
            $params['standard_gross_profit_rate'] = (float)$params['standard_gross_profit_rate'];
            // バリデーションチェック
            $this->isValid($request);
        }

        DB::beginTransaction();
        $Department = new Department();
        try {
            if (!$hasEditAuth) {
                // 編集権限なし
                throw new \Exception();
            }

            $saveResult = false;
            $delLock = false;
            $isCheck = true;
            if ($isSameCheck) {
                if ($newFlg) {
                    // 登録
                    $saveResult = $Department->add($params);
                    $delLock = true;
                } else {
                    $id = $params['id'];
                    // 入力した親部門が子部門に含まれいないか
                    $isCheck = $this->checkChild($id, $params['parent_id']);

                    if ($isCheck) {
                        // 更新
                        $saveResult = $Department->updateById($params);
                        
                        // ロック解放
                        $keys = array($params['id']);
                        $LockManage = new LockManage();
                        $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
                    }
                }
            }
            if (!$isSameCheck && !$isCheck) {
                $errors['own_bank_id'] = config('message.error.department.duplication_bank');
                // 子部門に存在した場合
                $errors['parent_id'] = config('message.error.department.parent_circulate');
                $validSts = false;
                throw new \Exception();
            }
            if (!$isSameCheck) {
                $errors['own_bank_id'] = config('message.error.department.duplication_bank');
                $validSts = false;
                throw new \Exception();
            }
            if (!$isCheck) {
                // 子部門に存在した場合
                $errors['parent_id'] = config('message.error.department.parent_circulate');
                $validSts = false;
                throw new \Exception();
            }
            
            
            if ($saveResult && $delLock && $isSameCheck) {
                DB::commit();
                $resultSts= true;
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
        return \Response::json(['result' => $resultSts, 'validSts' => $validSts, 'errors' => $errors]);
    }

    /**
     * 削除
     * @param Request $request
     * @return type
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

            $Department = new Department();

            // 削除
            $deleteResult = $Department->deleteById($params['id']);

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
     * 銀行の重複チェック
     *
     * @param $obj
     * @return void
     */
    public function checkSame($obj) {
        for ($i = 0; $i < count($obj); $i++) {     
            for ($j = $i + 1; $j < count($obj); $j++) {
                if ($obj[$i] != '0' || $obj[$j] != '0') {
                    if ($obj[$i] == $obj[$j]) {
                        return false;
                    }
                }
            }
        }
        return true;
    }


    /**
     * 引数の部門IDの子部門ID全てを返す
     *
     * @param  $departmentId
     * @return void
     */
    public function checkChild($departmentId, $inputParentId)
    {
        $Department = new Department();
        $result = true;
        // 部門の関連が無くなるまで
        $departments = $Department->getComboList(); 
        while (true) {
            $department = $departments->where('parent_id', '=', $departmentId)->first();
            if (!$department) {
                break;
            }
            
            $departmentId = $department->id;
            if ($inputParentId == $departmentId) {
                // 子部門の中に親部門IDが含まれている
                $result = false;
                break;
            }
        }

        return $result;
    }


    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid(Request $request)
    {
        $this->validate($request, [
            'department_name' => 'required|max:30',
            'base_id' => 'required',
            'standard_gross_profit_rate' => 'required|numeric|between:0.00,99.99',
            'chief_staff_id' => 'required',
        ]);
    }

}