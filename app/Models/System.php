<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;

/**
 * システム管理マスタ
 */
class System extends Model
{
    // テーブル名
    protected $table = 'm_system';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $appends = ["open"];

    public function getOpenAttribute(){
        return true;
    }


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'period',
        'tax_rate',
        'tax_rate_lock_flg',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    /**
     * 最新期の取得
     * @return type 検索結果データ（１件）
     */
    public function getByPeriod() {
        $data = $this
                ->select([
                    'period',
                    'tax_rate',
                    'tax_rate_lock_flg'
                ])
                ->orderBy('period', 'desc')
                ->first()
                ;

        return $data;
    }
}