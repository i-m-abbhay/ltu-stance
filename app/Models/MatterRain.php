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
class MatterRain extends Model
{
    // テーブル名
    protected $table = 't_matter_rain';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',                 // ID
        'matter_id',          // 案件ID
        'matter_no',          // 案件番号
        'rain_date',          // 雨延期日
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
    public function getListByMatterId($matterId){
        $data = $this
                ->where([
                    ['matter_id', $matterId],
                ])
                ->select(
                    DB::raw('DATE_FORMAT(rain_date, \'%Y/%m/%d\') AS rain_date')
                )
                ->get()
                ;
        return $data;
    }
}