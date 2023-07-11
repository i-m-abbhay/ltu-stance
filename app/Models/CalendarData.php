<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * カレンダーデータ
 */
class CalendarData extends Model
{
    // テーブル名
    protected $table = 'm_calendar_data';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'calendar_date',
        'calendar_week',
        'holiday_flg',
        'businessday',
        'comment',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
    ];

    /**
     * 指定日の翌営業日を取得する
     * @param $calendarDate 年月日
     * @param $isList リストを返すか
     * @return type 検索結果データ
     */
    public function getNextBusinessDay($calendarDate, $isList = false) {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('businessday', '=', config('const.businessdayKbn.val.businessday'));
        $where[] = array('calendar_date', '>', $calendarDate);
        
        // データ取得
        $query = $this
                ->where($where)
                ->select([
                    'id',
                    'calendar_week',
                    'holiday_flg',
                    'businessday',
                    'comment',
                    'del_flg',
                    'created_user',
                    'created_at',
                    'update_user',
                    'update_at'
                ])
                ->selectRaw(
                    'DATE_FORMAT(calendar_date, "%Y/%m/%d") as calendar_date,
                     CASE 
                          WHEN calendar_week = '. config('const.calendarWeek.val.sunday'). '
                            THEN \''. config('const.calendarWeek.text.sunday'). '\' 
                          WHEN calendar_week = '. config('const.calendarWeek.val.monday'). '
                            THEN \''. config('const.calendarWeek.text.monday'). '\' 
                          WHEN calendar_week = '. config('const.calendarWeek.val.tuesday'). '
                            THEN \''. config('const.calendarWeek.text.tuesday'). '\' 
                          WHEN calendar_week = '. config('const.calendarWeek.val.wednesday'). '
                            THEN \''. config('const.calendarWeek.text.wednesday'). '\' 
                          WHEN calendar_week = '. config('const.calendarWeek.val.thursday'). '
                            THEN \''. config('const.calendarWeek.text.thursday'). '\' 
                          WHEN calendar_week = '. config('const.calendarWeek.val.friday'). '
                            THEN \''. config('const.calendarWeek.text.friday'). '\' 
                          WHEN calendar_week = '. config('const.calendarWeek.val.saturday'). '
                            THEN \''. config('const.calendarWeek.text.saturday'). '\' 
                        END AS calendar_week_text'
                )
                ->orderBy('calendar_date', 'asc')
                ;

            $data = null;
            if($isList){
                $data = $query->get();
            }else{
                $data = $query->first();
            }

        return $data;
    }

    /**
     * n営業日後の日付データを取得
     *
     * @param int $afterDays 日数n
     * @param string $calendarDate 計算の起点となる年月日（nullの場合、当日システム日付とする）
     * @return 1レコード
     */
    public function getAfterBusinessDay($afterDays, $calendarDate = null) {

      if (is_null($calendarDate)) {
        $calendarDate = Carbon::today()->format('Y/m/d');
      }

      // Where句作成
      $where = [];
      $where[] = array('del_flg', '=', config('const.flg.off'));
      $where[] = array('businessday', '=', config('const.businessdayKbn.val.businessday'));
      $where[] = array('calendar_date', '>=', $calendarDate);
      
      // データ取得
      $data = $this
              ->where($where)
              ->select([
                'calendar_week',
                'holiday_flg',
                'businessday',
                'comment'
              ])
              ->selectRaw(
                'DATE_FORMAT(calendar_date, "%Y/%m/%d") as calendar_date'
              )
              ->offset($afterDays)
              ->first()
              ;

      return $data;
  }

  /**
   * カレンダーデータ取得（期間指定）
   *
   * @param [type] $from yyyymmdd
   * @param [type] $to yyyymmdd
   * @return void
   */
  public function getFromTo($from, $to){
      $where = [];

      $where[] = array('del_flg', '=', config('const.flg.off'));
      $where[] = array('calendar_date', '>=', $from);
      $where[] = array('calendar_date', '<', date('Y/m/d', strtotime($to . ' +1 day')));
      $data = $this->where($where)->get();

      return $data;
  }

  /**
   * カレンダーデータにある最古の年を取得
   * @return void
   */
  public function getFirstYear(){
    $where = [];
    $where[] = array('del_flg', '=', config('const.flg.off'));
    
    $data = $this
            ->select([
              DB::raw('DATE_FORMAT(MIN(calendar_date), "%Y") AS calendar_Year'),
            ])
            ->where($where)
            ->first();

    return $data;
  }

  /**
   * 登録
   * @param $params 1行の配列
   * @return id
   */
  public function add($params){
    $items = [];

    $userId = Auth::user()->id;
    $now    = Carbon::now();

    try {
        if(!isset($params['del_flg'])){
            $params['del_flg'] = config('const.flg.off');
        }
        if(!isset($params['created_user'])){
            $params['created_user'] = $userId;
        }
        if(!isset($params['created_at'])){
            $params['created_at'] = $now;
        }
        if(!isset($params['update_user'])){
            $params['update_user'] = $userId;
        }
        if(!isset($params['update_at'])){
            $params['update_at'] = $now;
        }

        $registerCol = $this->getFillable();
        foreach($registerCol as $colName){
            if(array_key_exists($colName, $params)){
                $items[$colName] = $params[$colName];
            }
        }

        // 登録
        return $this->insertGetId($items);
    } catch (\Exception $e) {
        throw $e;
    }
  }

  /**
   * 更新
   * @param int $id
   * @param array $params 1行の配列
   * @return void
   */
  public function updateById($id, $params) {
    $result = false;
    try {
        // 条件
        $where = [];
        $where[] = array('id', '=', $id);

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
        
        // 更新内容
        $items = [];
        $registerCol = $this->getFillable();
        foreach($registerCol as $colName){
            if(array_key_exists($colName, $params)){
                switch($colName){
                    case 'created_user':
                    case 'created_at':
                        break;
                    default:
                        $items[$colName] = $params[$colName];
                        break;
                }
            }
        }
        $items['update_user']   = Auth::user()->id;
        $items['update_at']     = Carbon::now();

        // 更新
        $updateCnt = $this
                    ->where($where)
                    ->update($items)
                    ;

        $result = ($updateCnt == 1);
        // 登録
        return $result;
    } catch (\Exception $e) {
        throw $e;
    }
  }

   /**
     * 指定年のデータを削除する(物理)
     * @param $requestId
     */
    public function deleteByYear($calendarYear){
      $deleteCnt = 0;
      try {
          $where = [];
          $where[] = array('calendar_date', '>=', $calendarYear.'/01/01');
          $where[] = array('calendar_date', '<=', $calendarYear.'/12/31');

          
          $deleteDataList = $this
              ->where($where)
              ->get()
              ;

          // ログテーブルへの書き込み
          $LogUtil = new LogUtil();
          foreach ($deleteDataList as $deleteData) {
              $LogUtil->putByData($deleteData, config('const.logKbn.delete'), $this->table);
          }

          $deleteCnt = $this
              ->where($where)
              ->delete()
              ;
              
      } catch (\Exception $e) {
          throw $e;
      }

      return $deleteCnt;
  }
}
