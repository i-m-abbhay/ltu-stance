<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Libs\LogUtil;

/**
 * 案件詳細
 */
class MatterDetail extends Model
{
    // テーブル名
    protected $table = 't_matter_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',                 // ID
        'matter_id',          // 案件ID
        'matter_no',          // 案件番号
        'type',               // 種別
        'add_flg',            // 追加工程フラグ
        'construction_id',    // 工事区分ID
        'class_small_id',     // 小分類ID
        'quote_detail_id',    // 見積明細ID
        'text',               // タスク名
        'duration',           // 期間
        'base_date_type',     // 基準日種別
        'progress',           // 進捗
        'start_date',         // 開始日
        'parent',             // 親ID
        'sortorder',          // 並び順
        'construction_period_days',     // 工期日数
        'date_calc_flg',      // 日付計算フラグ
        'start_date_calc_days',         // 開始日計算日数
        'order_timing',       // 発注タイミング
        'rain_flg',           // 雨延期フラグ
        'hidden_flg',         // 非表示フラグ
        'created_user',       // 作成ユーザ
        'created_at',         // 作成日時
        'update_user',        // 更新ユーザ
        'update_at',          // 更新日時
    ];

    /**
     * 案件IDで取得
     *
     * @param [type] $matterId
     * @return void
     */
    public function getByMatterId($matterId){
        $where = [];
        $where[] = array('matter_id', '=', $matterId);

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 一括登録（配列）
     *
     * @param array $paramsList
     * @return void
     */
    public function addList($paramsList){
        $userId = Auth::user()->id;
        $now = Carbon::now();

        $itemsList = [];
        foreach ($paramsList as $key => $params) {
            $items = [];
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

            $itemsList[] = $items;
        }

        $this->insert($itemsList);
    }

    /**
     * 更新
     *
     * @param array $params
     * @return void
     */
    public function updateById($params){
        $userId = Auth::user()->id;
        $now = Carbon::now();

        $LogUtil = new LogUtil();

        $where = [];

        // ログテーブルへの書き込み
        $where[] = array('id', '=', $params['id']);
        $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

        $items = [];
        $registerCol = $this->getFillable();
        foreach($registerCol as $colName){
            if(array_key_exists($colName, $params)){
                switch($colName){
                    case 'start_date':
                        $items[$colName] = $params[$colName];
                        // 日付が空白の場合はnullを代入する ※date型に空白は登録出来ない為
                        if ($params[$colName] == "") {
                            $items[$colName] = null;
                        }
                        break;
                    case 'created_user':
                    case 'created_at':
                        break;
                    default:
                        $items[$colName] = $params[$colName];
                        break;
                }
            }
        }
        $items['update_user'] = $userId;
        $items['update_at'] = $now;

        // 更新
        $updateCnt = $this
            ->where($where)
            ->update($items);

        return ($updateCnt > 0);
    }

    /**
     * 物理削除（案件ID）
     *
     * @param [type] $matterId
     * @return void
     */
    public function physicalDeleteByMatterId($matterId){
        $where = [];
        $where[] = array('matter_id', $matterId);

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

        // 更新
        $result = $this
                ->where($where)
                ->delete();
    }
}