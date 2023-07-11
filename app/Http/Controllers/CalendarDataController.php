<?php
namespace App\Http\Controllers;

use App\Libs\Common;
use App\Models\Authority;
use App\Models\Calendar;
use App\Models\CalendarData;
use DB;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\LockManage;

use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * カレンダー
 */
class CalendarDataController extends Controller
{

    const SCREEN_NAME = 'calendar-data';                  // 通常のロック用
    private $INIT_ROW = [];

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
        // 休日のカレンダーデータ行の初期化
        $this->INIT_ROW   = [
            'id'            => 0,
            'calendar_date' => '',                                          // 休日
            'holiday_flg'   => config('const.flg.on'),                      // 祝日フラグ
            'calendar_week' => config('const.calendarWeek.val.sunday'),     // 曜日
            'businessday'   => config('const.businessdayKbn.val.holiday'),  // 営業日区分(休業日固定)
            'comment'       => '',
        ];
    }

    /**
     * 初期表示
     * @return type
     */
    public function index(Request $request)
    {
        $CalendarData   = new CalendarData();
        $LockManage = new LockManage();

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');
        $lockData = null;

        // 現在
        $NOW            = Carbon::now();
        $NOW_YEAR       = (int)$this->formatDateTime($NOW, 'Y');
        $START_YEAR     = $NOW_YEAR;
        $tmpStartYear   = $CalendarData->getFirstYear();
        if($tmpStartYear !== null && Common::nullorBlankToZero($tmpStartYear->calendar_Year) != 0){
            $firstYear = intval($tmpStartYear->calendar_Year);
            if($START_YEAR > $firstYear){
                // カレンダーデータが一件もない場合に来年分を先に作ってしまったときのため
                $START_YEAR = $firstYear;
            }
        }

        // 年リスト
        $yearList = [];
        for($year=$START_YEAR; $year <= ($NOW_YEAR+config('const.calendarScreenSetting.add_year_end')); $year++){
            $yearList[] = $year;
        }

        // 権限(ボタン制御)
        $hasAuth = [
            'inquiry'   => $this->hasInquiryAuth(),
            'edit'      => $this->hasEditAuth(),
        ];
      
        DB::beginTransaction();
        try{
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
        
        

        return view('Calendar.calendar-data')
            ->with('hasAuth',   json_encode($hasAuth))
            ->with('isOwnLock', $isOwnLock)
            ->with('lockData',  $lockData)
            ->with('lockKey',   config('const.calendarScreenSetting.lock_manage.lock_key'))
            ->with('yearList',  json_encode($yearList))
            ->with('nowYear',   $NOW_YEAR)
            ->with('businessdayKbn',   json_encode(config('const.businessdayKbn')))
        ;

    }
    
    /**
     * カレンダーのデータを取得する
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function getScheduleData(Request $request)
    {
        $result = array('status' => false, 'calendar' => [], 'not_data_flg' => false);
        $CalendarData = new CalendarData();
        
        
        $params = $request->request->all();
        $from   = $params['calendar_year'].'/1/1';
        $to     = $params['calendar_year'].'/12/31';
        $calendarList = $CalendarData->getFromTo($from, $to)->toArray();
        if(count($calendarList) === 0){
            $result['not_data_flg'] = true;
            $calendarList = array_values($this->calcHoliday($params['calendar_year']));
        }else{
            // テスト用
            //$this->calcHoliday($params['calendar_year']);
            foreach($calendarList as $key => $row){
                $calendarList[$key]['calendar_date'] = str_replace('-', '/', $row['calendar_date']);
            }
        }

        

        $result['calendar'] = $calendarList;
        $result['status'] = true;
        return \Response::json($result);
    }

    /**
     * カレンダーマスターのルールから休日を計算
     * @param $year
     */
    private function calcHoliday($year){
        $Calendar           = new Calendar();
        $calendarMsterList  = $Calendar->getByYear($year);

        $result     = [];


        $START_YMD  = new Carbon($year.'/01/01');
        $END_YMD    = new Carbon($year.'/12/31');

        foreach($calendarMsterList as $CALENDAR_MASTER){
            $CALENDAR_MASTER->holiday_name = Common::nullToBlank($CALENDAR_MASTER->holiday_name);

            switch($CALENDAR_MASTER->repeat_kbn){
                case config('const.calendarRepeatKbn.val.default'):
                    // なし
                    if(checkdate($CALENDAR_MASTER->month, $CALENDAR_MASTER->day, $CALENDAR_MASTER->year)){
                        $calendarDate       = new Carbon($CALENDAR_MASTER->year.'/'.$this->dayZerPadding($CALENDAR_MASTER->month).'/'.$this->dayZerPadding($CALENDAR_MASTER->day));
                        $addCalendar        = $this->INIT_ROW;
                        $addCalendar['calendar_date']   = $this->formatDateTime($calendarDate, 'Y/m/d');
                        $addCalendar['holiday_flg']     = $this->convCalendarKbn($CALENDAR_MASTER->kbn);
                        $addCalendar['calendar_week']   = $calendarDate->dayOfWeek;
                        $addCalendar['comment']         = $CALENDAR_MASTER->holiday_name;
                        $result[$addCalendar['calendar_date']] = $addCalendar;
                    }
                    break;
                case config('const.calendarRepeatKbn.val.week'):
                    // 毎週　◆例：毎週土曜日
                    $addDay = $this->getNextWeekDayAddDay($START_YMD->dayOfWeek, $CALENDAR_MASTER->week);
                    // 参照を切る
                    $currentStartDay = $this->disconnectReference($START_YMD)->addDay($addDay);

                    while($currentStartDay->lte($END_YMD)){
                        $addCalendar = $this->INIT_ROW;
                        $addCalendar['calendar_date']   = $this->formatDateTime($currentStartDay, 'Y/m/d');
                        $addCalendar['holiday_flg']     = $this->convCalendarKbn($CALENDAR_MASTER->kbn);
                        $addCalendar['calendar_week']   = $currentStartDay->dayOfWeek;
                        $addCalendar['comment']         = $CALENDAR_MASTER->holiday_name;
                        $currentStartDay->addDay(config('const.calendarScreenSetting.day_of_week_cnt'));
                        $result[$addCalendar['calendar_date']] = $addCalendar;
                    }
                    break;
                case config('const.calendarRepeatKbn.val.month'):
                    // 毎月(日) ◆例：毎月10日
                    for($month=1; $month<=config('const.calendarScreenSetting.month_cnt'); $month++){
                        if(checkdate($month, $CALENDAR_MASTER->day, $year)){
                            $addCalendar        = $this->INIT_ROW;
                            $calendarDate       = new Carbon($year.'/'.$this->dayZerPadding($month).'/'.$this->dayZerPadding($CALENDAR_MASTER->day));
                            $addCalendar['calendar_date']   = $this->formatDateTime($calendarDate, 'Y/m/d');
                            $addCalendar['holiday_flg']     = $this->convCalendarKbn($CALENDAR_MASTER->kbn);
                            $addCalendar['calendar_week']   = $calendarDate->dayOfWeek;
                            $addCalendar['comment']         = $CALENDAR_MASTER->holiday_name;
                            $result[$addCalendar['calendar_date']] = $addCalendar;
                        }
                    }
                    break;
                case config('const.calendarRepeatKbn.val.month_week_day'):
                    // 毎月(曜日) ◆例：毎月 第2土曜日
                    for($month=1; $month<=config('const.calendarScreenSetting.month_cnt'); $month++){
                        $currentStartDay = $this->getNonthWeekWeekNumberTarget($year, $month, $CALENDAR_MASTER->week, $CALENDAR_MASTER->week_number);
                        // 年月がズレていないか
                        if($this->formatDateTime($currentStartDay, 'Ym') === $year.$this->dayZerPadding($month)){
                            $addCalendar        = $this->INIT_ROW;
                            $addCalendar['calendar_date']   = $this->formatDateTime($currentStartDay, 'Y/m/d');
                            $addCalendar['holiday_flg']     = $this->convCalendarKbn($CALENDAR_MASTER->kbn);
                            $addCalendar['calendar_week']   = $currentStartDay->dayOfWeek;
                            $addCalendar['comment']         = $CALENDAR_MASTER->holiday_name;
                            $result[$addCalendar['calendar_date']] = $addCalendar;
                        }
                    }
                    break;
                case config('const.calendarRepeatKbn.val.year'):
                    // 毎年(月日) ◆例：毎年1月1日
                    if(checkdate($CALENDAR_MASTER->month, $CALENDAR_MASTER->day, $year)){
                        $calendarDate       = new Carbon($year.'/'.$this->dayZerPadding($CALENDAR_MASTER->month).'/'.$this->dayZerPadding($CALENDAR_MASTER->day));
                        $addCalendar        = $this->INIT_ROW;
                        $addCalendar['calendar_date']   = $this->formatDateTime($calendarDate, 'Y/m/d');
                        $addCalendar['holiday_flg']     = $this->convCalendarKbn($CALENDAR_MASTER->kbn);
                        $addCalendar['calendar_week']   = $calendarDate->dayOfWeek;
                        $addCalendar['comment']         = $CALENDAR_MASTER->holiday_name;
                        $result[$addCalendar['calendar_date']] = $addCalendar;
                    }
                    break;
                case config('const.calendarRepeatKbn.val.year_month_week_day'):
                    // 毎年(月/曜日) ◆例：毎年1月の第2の月曜日
                    $currentStartDay = $this->getNonthWeekWeekNumberTarget($year, $CALENDAR_MASTER->month, $CALENDAR_MASTER->week, $CALENDAR_MASTER->week_number);
                    // 年月がズレていないか
                    if($this->formatDateTime($currentStartDay, 'Ym') === $year.$this->dayZerPadding($CALENDAR_MASTER->month)){
                        $addCalendar        = $this->INIT_ROW;
                        $addCalendar['calendar_date']   = $this->formatDateTime($currentStartDay, 'Y/m/d');
                        $addCalendar['holiday_flg']     = $this->convCalendarKbn($CALENDAR_MASTER->kbn);
                        $addCalendar['calendar_week']   = $currentStartDay->dayOfWeek;
                        $addCalendar['comment']         = $CALENDAR_MASTER->holiday_name;
                        $result[$addCalendar['calendar_date']] = $addCalendar;
                    }
                    break;
            }
        }

        // 平日の初期化
        $incrementYmd = $this->disconnectReference($START_YMD);
        while($incrementYmd->lte($END_YMD)){
            $calendarDate = $this->formatDateTime($incrementYmd, 'Y/m/d');
            if(!isset($result[$calendarDate])){
                $addCalendar        = $this->INIT_ROW;
                $addCalendar['calendar_date']   = $calendarDate;
                $addCalendar['holiday_flg']     = config('const.flg.off');
                $addCalendar['calendar_week']   = $incrementYmd->dayOfWeek;
                $addCalendar['comment']         = '';
                $addCalendar['businessday']     = config('const.businessdayKbn.val.businessday');
                $result[$addCalendar['calendar_date']] = $addCalendar;
            }
            $incrementYmd->addDay();
        }
        return $result;
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result['status'] = false;

        $NOW            = Carbon::now();

        $CalendarData   = new CalendarData();

        // リクエストデータ取得
        $params             = $request->request->all();
        $calendarYear       = $params['calendar_year'];
        $calendarDataList   = json_decode($params['calendar_data_list'], true);

        $startYmd   = new Carbon($calendarYear.'/01/01');
        $END_YMD    = new Carbon($calendarYear.'/12/31');

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


            // 365日回す
            while($startYmd->lte($END_YMD)){
                $saveRow        = $this->INIT_ROW;
                $calendarDate   = $this->formatDateTime($startYmd, 'Y/m/d');
                $saveKey        = array_search($calendarDate, array_column($calendarDataList, 'calendar_date'));
                if($saveKey === false){ 
                    // 通らない想定
                    $saveRow['calendar_date']   = $calendarDate;
                    $saveRow['holiday_flg']     = config('const.flg.off');
                    $saveRow['calendar_week']   = $startYmd->dayOfWeek;
                    $saveRow['comment']         = '';
                    $saveRow['businessday']     = config('const.businessdayKbn.val.businessday');
                }else{
                    $tmpSaveRow                 = $calendarDataList[$saveKey];
                    $saveRow['id']              = $tmpSaveRow['calendar_id'];
                    $saveRow['calendar_date']   = $tmpSaveRow['calendar_date'];
                    $saveRow['holiday_flg']     = $tmpSaveRow['holiday_flg'];
                    $saveRow['calendar_week']   = $tmpSaveRow['calendar_week'];
                    $saveRow['businessday']     = $tmpSaveRow['businessday'];
                    $saveRow['comment']         = $tmpSaveRow['comment'];
                }

                if(Common::nullorBlankToZero($saveRow['id']) === 0){
                    // 新規登録
                    $insertCarendarId = $CalendarData->add($saveRow);
                    if(!$insertCarendarId){
                        $result['message'] = config('message.error.save');
                        throw new \Exception();
                    }
                }else{
                    // 更新
                    if($startYmd->gt($NOW)){
                        // 明日以降でないと更新させない
                        $CalendarData->updateById($saveRow['id'], $saveRow);
                    }
                }
                
                // 日付加算
                $startYmd->addDay();
            }

            // ロック解除
            if(!$this->unLock(self::SCREEN_NAME, config('const.calendarScreenSetting.lock_manage.lock_key'))){
                throw new \Exception();
            }
            

            DB::commit();
            $result['status'] = true;
            Session::flash('flash_success', config('message.success.save'));
            
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

        $CalendarData       = new CalendarData();

        // リクエストデータ取得
        $params         = $request->request->all();
        $calendarYear   = $params['calendar_year'];

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

            // 削除
            if(!$CalendarData->deleteByYear($calendarYear)){
                throw new \Exception();
            }

            // ロック解除
            if(!$this->unLock(self::SCREEN_NAME, config('const.calendarScreenSetting.lock_manage.lock_key'))){
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

    /**
     * DateTime型の日付をフォーマットして文字列で返す
     * @param $now
     * @param $format
     */
    private function formatDateTime($now, $format){
        return $now->format($format);
    }
    /**
     * Carbonの参照を切る
     * @param $ymd 
     */
    private function disconnectReference($ymd){
        return $ymd->copy();
    }
    /**
     * 1桁の文字を0埋めして2桁にする
     * @param $target
     * @param $length
     */
    private function dayZerPadding($target, $length=2){
        return str_pad($target, $length, '0', STR_PAD_LEFT);
    }
    /**
     * カレンダーマスタの区分が「祝日」の場合は「1」を返す
     * @param $kbn  カレンダーマスタの区分
     */
    private function convCalendarKbn($kbn){
        return intval(Common::nullorBlankToZero($kbn)) === config('const.calendarKbn.val.public_holiday') ? config('const.flg.on') : config('const.flg.off');
    }
    /**
     * 指定曜日(第一引数)の次に来る指定曜日(第二引数)までの加算日数を返す
     * @param $target
     * @param $length
     */
    private function getNextWeekDayAddDay($currentWeek, $targetWeek){
        $addDay = 0;
        if($currentWeek ===  $targetWeek){

        }else if($currentWeek <  $targetWeek){
            $addDay =  $targetWeek - $currentWeek;
        }else if($currentWeek > $targetWeek){
            $addDay = config('const.calendarScreenSetting.day_of_week_cnt') - ($currentWeek -  $targetWeek);
        }
        return $addDay;
    }
    /**
     * 指定年　指定月　指定週　指定曜日の日付を返す
     * @param $year         年
     * @param $month        月
     * @param $week         第n
     * @param $weekNumber   曜日
     */
    private function getNonthWeekWeekNumberTarget($year, $month, $week, $weekNumber){
        $currentStartDay = new Carbon($year.'/'.$this->dayZerPadding($month).'/01');
        $addDay = $this->getNextWeekDayAddDay($currentStartDay->dayOfWeek, $week);
        // 月の最初の指定曜日
        $currentStartDay    = $currentStartDay->addDay($addDay);
        $addWeekNumber      = Common::nullorBlankToZero($weekNumber) - 1;

        // n回目の曜日
        if($addWeekNumber >= 1){
            $currentStartDay->addWeeks($addWeekNumber);
        }
        return $currentStartDay;
    }
}