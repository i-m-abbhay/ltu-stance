<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Libs\LogUtil;

/**
 * 案件詳細リンク
 */
class MatterDetailLink extends Model
{
    // テーブル名
    protected $table = 't_matter_detail_link';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',           // ID,
        'matter_id',    // 案件ID,
        'matter_no',    // 案件番号,
        'type',         // 種別,
        'source',       // リンク元,
        'target',       // リンク先,
        'created_user', // 作成ユーザ,
        'created_at',   // 作成日時,
        'update_user',  // 更新ユーザ,
        'update_at',    // 更新日時,
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