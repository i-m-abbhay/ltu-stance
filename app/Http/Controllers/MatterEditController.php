<?php
namespace App\Http\Controllers;

use DB;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\General;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LockManage;
use App\Models\Quote;
use App\Models\QuoteRequest;
use App\Models\QuoteVersion;
use App\Models\Staff;
use App\Models\StaffDepartment;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 案件編集
 */
class MatterEditController extends Controller
{

    const SCREEN_NAME = 'matter-edit';                  // 通常のロック用(案件IDロック用)
    const UPDATE_SCREEN_NAME = 'matter-edit_update';    // 更新時の見積IDロック用
    

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
    public function index(Request $request, $id = null)
    {
        $Customer   = new Customer();
        $General    = new General();
        $Matter     = new Matter();
        $Department = new Department();
        $StaffDepartment    = new StaffDepartment();
        $Staff      = new Staff();
        $LockManage = new LockManage();

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');
        $lockData = null;

        
        // 得意先リスト
        $customerData = $Customer->getComboList();
        // 施主名
        $ownerData = $Matter->getOwnerList();
        // 建築種別
        $architectureData = $General->getCategoryList(config('const.general.arch'));

        // 部門リスト
        $departmentList     = $Department->getComboList();
        // 担当部門リスト
        $staffDepartmentList = $StaffDepartment->getCurrentTermList();
        $staffDepartmentList = $staffDepartmentList->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });
        // 担当者リスト
        $staffList          = $Staff->getComboList();


        if (is_null($id)) {
            // 新規
            $matterData = $Matter;
        } else {
            // 編集
            $matterData = $Matter->find($id);
            if (is_null($matterData)) {
                throw new NotFoundHttpException();
            }

            DB::beginTransaction();
            try{
                /** ロック処理 **/
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($matterData->id);

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
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error($e);
                Session::flash('flash_error', config('message.error.error'));
            }

        }

        if (is_null($lockData)) {
            $lockData = $LockManage;
        }
        
        return view('Matter.matter-edit')
            ->with('isOwnLock', $isOwnLock)
            ->with('lockData', $lockData)
            ->with('matterData', $matterData)
            ->with('customerData', $customerData)
            ->with('ownerData', $ownerData)
            ->with('architectureData', $architectureData)
            ->with('departmentList', $departmentList)
            ->with('staffDepartmentList', $staffDepartmentList)
            ->with('staffList', $staffList)
        ;

    }
    
    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result = array('status' => false, 'message' => '', 'id' => null);

        $Matter         = new Matter();
        $QuoteRequest   = new QuoteRequest();
        $Quote          = new Quote();
        $QuoteVersion   = new QuoteVersion();

        // リクエストデータ取得
        $params = $request->request->all();

        $newFlg     = false;
        // 見積変更時のロックを取得出来たか
        $validLock  = true;
        // 部門/担当者の変更がある場合のみ入る
        $beforeMatterNo = null;
        // 更新する見積の情報
        $quoteData  = null;

        // バリデーションチェック
        $this->isValid($request);

        if (empty($params['id'])) {
            $newFlg = true;
        }else{
            // 変更前の案件情報
            $beforeMatterData = $Matter->getById($params['id']);
            // 担当部門 or 担当者に変更があったか
            if($beforeMatterData->department_id !== intval($params['department_id']) || $beforeMatterData->staff_id !== intval($params['staff_id'])){
                // 変更あり！のため「見積依頼」「見積版」の更新を実行する
                $beforeMatterNo         = $beforeMatterData->matter_no;
                $quoteData              = $Quote->getByMatterNo($beforeMatterNo);
                if($quoteData !== null){
                    $validLock = $this->lockTransaction(self::UPDATE_SCREEN_NAME, $quoteData->id);
                }
            }
        }
        
        DB::beginTransaction();
        try {
            $saveResult = false;
            $isOwnLock = true;
            if ($newFlg) {
                // 登録
                $saveResult = $Matter->add($params);
                if (is_numeric($saveResult)) {
                    $result['id'] = $saveResult;
                    $saveResult = true;
                    $isOwnLock = true;
                }
            } else {
                // 更新
                // 案件のロックを持っているか確認 見積版を更新するときは見積のロックを持っているか確認
                if(!$this->isOwnLocked(self::SCREEN_NAME, $params['id']) || ($quoteData !== null && !$validLock)){
                    $result['message'] = config('message.error.getlock');
                    // throw new \Exception();
                    $isOwnLock = false;
                }

                if ($isOwnLock) {
                    $saveResult = $Matter->updateById($params);
                    $result['id'] = $params['id'];

                    // 見積依頼と見積版の更新
                    if($beforeMatterNo !== null){
                        // 見積依頼
                        $QuoteRequest->updateDepartmentStaffByMatterNo($beforeMatterNo, $params['department_id'], $params['staff_id']);
                        
                        // 見積版
                        if($quoteData !== null){
                            $QuoteVersion->updateDepartmentStaffByQuoteNo($quoteData->quote_no, $params['department_id'], $params['staff_id']);
                        }
                    }

                    // ロック解除 案件
                    if(!$this->unLock(self::SCREEN_NAME, $params['id'])){
                        $result['message'] = config('message.error.getlock');
                        $isOwnLock = false;
                        // throw new \Exception();
                    }
                    // ロック解除 見積
                    if($quoteData !== null){
                        if(!$this->unLock(self::UPDATE_SCREEN_NAME, $quoteData->id)){
                            $result['message'] = config('message.error.getlock');
                            $isOwnLock = false;
                            // throw new \Exception();
                        }
                    }
                }
            }
            
            if ($saveResult && $isOwnLock) {
                DB::commit();
                $result['status'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                DB::rollBack();
                if ($result['message'] === '') {
                    $result['message'] = config('message.error.save');
                }
                Session::flash('flash_error', $result['message']);
                // throw new \Exception();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }

    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid($request)
    {
        $validator = \Validator::make($request->all(), [
            'customer_id' => 'required',
            'owner_name' => 'required|max:50',
            'architecture_type' => 'required',
            'staff_id' => 'required',
            'department_id' => 'required',
        ]);
        $validator->after(function ($validator) {
            if ((new Matter())->isDuplicate($validator->getData())) {
                $validator->errors()->add('duplicated', config('message.error.matter.duplicated'));
            }
        })->validate();
    }

    /**
     * ロック処理まとめ(別トランザクション)
     * @param $SCREEN_NAME  処理名
     * @param $keyId        キー
     * @return false        失敗
     */
    private function lockTransaction($SCREEN_NAME, $keyId){
        $result = true;
        DB::beginTransaction();
        try{
            // ロックを持っているか確認
            if(!$this->isOwnLocked($SCREEN_NAME, $keyId)){
                if(!$this->getLock($SCREEN_NAME, $keyId)){
                    // 他の人がロックを持っている
                    $result = false;
                    throw new \Exception();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return $result;
    }

    /**
     * ロック取得
     * @param $keyId
     * @return false 誰かがロックしている
     */
    private function getLock($SCREEN_NAME, $keyId){
        $result = false;

        $LockManage = new LockManage();

        // 画面名から対象テーブルを取得
        $tableNameList = config('const.lockList.'.$SCREEN_NAME);
        $keys = array(intval($keyId));

        $lockCnt = 0;
        foreach ($tableNameList as $i => $tableName) {
            // ロック確認
            $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
            if (!$isLocked) {
                // 編集モードかつ、ロックされていない場合はロック取得
                $lockDt = Carbon::now();
                $LockManage->gainLock($lockDt, $SCREEN_NAME, $tableName, $keys[$i]);
            }
            // ロックデータ取得
            $lockData = $LockManage->getLockData($tableName, $keys[$i]);
            if (!is_null($lockData) 
                && $lockData->screen_name === $SCREEN_NAME && $lockData->table_name === $tableName
                && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                    $lockCnt++;
            }
        }
        if (count($tableNameList) === $lockCnt) {
            $result = true;
        } 
        return $result;
    }
    
    /**
     * 自分がロックしているか
     * @param $keys
     * @return true：ロックを持っている
     */
    private function isOwnLocked($SCREEN_NAME, ...$keys){
        $result = true;

        $LockManage = new LockManage();

        $tableNameList = config("const.lockList.$SCREEN_NAME");
        if(!$LockManage->isOwnLocks($SCREEN_NAME, $tableNameList, $keys)){
            $result = false;
        }
        return $result;
    }

    /**
     * ロック解除する
     * @param $keys
     * @return true：ロックを解除で来た
     */
    private function unLock($SCREEN_NAME, ...$keys){
        $result = true;

        $LockManage = new LockManage();

        if(!$LockManage->deleteLockInfo($SCREEN_NAME, $keys, Auth::user()->id)){
            $result = false;
        }
        return $result;
    }
}