<?php
namespace App\Http\Controllers;

use App\Libs\Common;
use App\Models\Authority;
use App\Models\Calendar;
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
use Validator;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Exceptions\ValidationException;

/**
 * カレンダー編集
 */
class CalendarEditController extends Controller
{
    const SCREEN_NAME = 'calendar-edit';
    
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
    public function index(Request $request)
    {
        $Calendar   = new Calendar();
        $LockManage = new LockManage();

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');
        $lockData = null;
        $gridData = [];


        // 権限(ボタン制御)
        $hasAuth = [
            'inquiry'   => $this->hasInquiryAuth(),
            'edit'      => $this->hasEditAuth(),
        ];

        // 閲覧権限無し
        if(!$hasAuth['inquiry']){
            throw new NotFoundHttpException();
        }

        DB::beginTransaction();
        try{
            $gridData = $Calendar->getAllData()->toArray();

            /** ロック処理 **/
            // 画面名から対象テーブルを取得
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            $keys = array(config('const.calendarScreenSetting.lock_manage.lock_key'));

            $lockCnt = 0;

            if($hasAuth['edit']){
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

        

        if (is_null($lockData)) {
            $lockData = $LockManage;
        }
        
        return view('Calendar.calendar-edit')
            ->with('hasAuth',       json_encode($hasAuth))
            ->with('isOwnLock',     $isOwnLock)
            ->with('lockData',      $lockData)
            ->with('lockKey',       config('const.calendarScreenSetting.lock_manage.lock_key'))
            ->with('calendarKbn',   json_encode(config('const.calendarKbn')))
            ->with('calendarRepeatKbn', json_encode(config('const.calendarRepeatKbn')))
            ->with('calendarWeek',      json_encode(config('const.calendarWeek')))
            ->with('repeatKbnCtrl',     json_encode(config('const.calendarScreenSetting.repeat_kbn_ctrl')))
            ->with('gridData',      json_encode($gridData))
        ;

    }
    
    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result['status'] = false;

        $Calendar       = new Calendar();

        // リクエストデータ取得
        $params = $request->request->all();
        $editRowData =  json_decode($params['edit_row_data'], true);

        DB::beginTransaction();
        
        try {
            // 編集権限確認
            if(!$this->hasEditAuth()){
                throw new \Exception();
            }       
            // ロック確認
            if(!$this->isOwnLocked(self::SCREEN_NAME, config('const.calendarScreenSetting.lock_manage.lock_key'))){
                $result['message'] = config('message.error.getlock');
                throw new \Exception();
            }

            $saveData = [];
            $saveData['kbn']            = $editRowData['kbn'];
            $saveData['holiday_name']   = $editRowData['holiday_name'];
            $saveData['year']           = $editRowData['year'];
            $saveData['month']          = $editRowData['month'];
            $saveData['day']            = $editRowData['day'];
            $saveData['week']           = $editRowData['week'];
            $saveData['repeat_kbn']     = $editRowData['repeat_kbn'];
            $saveData['week_number']    = $editRowData['week_number'];
            
            $this->validation($saveData);
            
            if(intval(Common::nullorBlankToZero($editRowData['id'])) === 0){
                // 新規登録
                $Calendar->add($saveData);
            }else{
                // 編集
                $Calendar->updateById($editRowData['id'], $saveData);
            }
            
            DB::commit();
            $result['status'] = true;
            Session::flash('flash_success', config('message.success.save'));
           
        } catch (ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getMessages();
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
        }
        return \Response::json($result);
    }

    /**
     * 削除
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $result['status'] = false;

        $Calendar       = new Calendar();

        // リクエストデータ取得
        $params = $request->request->all();
        $calendarId =  $params['calendar_id'];

        DB::beginTransaction();
        
        try {
            // 編集権限確認
            if(!$this->hasEditAuth() || intval(Common::nullorBlankToZero($calendarId)) === 0){
                throw new \Exception();
            }       
            // ロック確認
            if(!$this->isOwnLocked(self::SCREEN_NAME, config('const.calendarScreenSetting.lock_manage.lock_key'))){
                $result['message'] = config('message.error.getlock');
                throw new \Exception();
            }

            // 削除
            if(!$Calendar->softDeleteById($calendarId)){
                throw new \Exception();
            }
            
            DB::commit();
            $result['status'] = true;
            Session::flash('flash_success', config('message.success.delete'));

        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
        }
        return \Response::json($result);
    }

    /**
     * バリデーションチェック
     * @param $params   登録更新対象の行データ 不要な項目は初期化して返す
     */
    private function validation(&$params){
        // 繰り返し区分によって変動するチェック項目
        $validation = [
            'year'          => 'required|integer|between:1500,2500',    // 年：1500～2500
            'month'         => 'required|integer|between:1,12',         // 月：1～12
            'day'           => 'required|integer|between:1,31',         // 日：1～31
            'week'          => 'required|integer|between:0,6',          // 曜日：0～6
            'week_number'   => 'required|integer|between:1,4',          // 月の第n：1～4
        ];

        // 実行するバリデーションチェック
        $executeValidationList = [
            'kbn'           => 'required|integer',
            'holiday_name'  => 'nullable|max:30',
            'repeat_kbn'    => 'required|integer',
        ];

        $repeatKbnName = array_search($params['repeat_kbn'], config('const.calendarRepeatKbn.val'));
        if($repeatKbnName !== false){
            foreach(config('const.calendarScreenSetting.repeat_kbn_ctrl.'.$repeatKbnName) as $colName => $isRequired){
                if(Common::isFlgOn($isRequired)){
                    $executeValidationList[$colName] = $validation[$colName];
                }else{
                    // 不要な項目の初期化
                    $params[$colName] = 0;
                }
            }
        }

        // バリデーション
        $validator = validator::make(
            $params, 
            $executeValidationList
        );

        if ($validator->fails()) {
           throw new ValidationException($validator->errors());
        }
    }

    /**
     * 閲覧権限があるか
     * 
     */
    private function hasInquiryAuth(){
        return Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.has');
    }

    /**
     * 編集権限があるか
     */
    private function hasEditAuth(){
        return Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has');
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