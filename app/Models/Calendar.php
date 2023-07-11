<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * カレンダー
 */
class Calendar extends Model
{
    // テーブル名
    protected $table = 'm_calendar';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'kbn',
        'holiday_name',
        'year',
        'month',
        'day',
        'week',
        'repeat_kbn',
        'week_number',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

   
    /**
     * データ取得
     * @return void
     */
    public function getAllData(){
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $data = $this->where($where)->get();

        return $data;
    }

    /**
     * データ取得
     * @return void
     */
    public function getByYear($year){
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        
        $data = $this
                ->where($where)
                ->where(function($query) use ($year) {
                    $query->where('year', $year)
                          ->orWhere('year', 0)
                          ->orWhereNull('year');
                })
                ->get();

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
     * 論理削除
     * @param int $id
     * @return boolean True:成功 False:失敗 
     */
    public function softDeleteById($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
}
