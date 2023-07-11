<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 年度マスタ
 */
class PeriodYear extends Model
{
    // テーブル名
    protected $table = 'm_period_year';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 期の取得
     *
     * @param $ymd (yyyymmdd)
     * @param $period 取得する期　-1: 前期 0: 当期 1: 来期
     * @return void
     */
    public function getPeriodYearByYmd($ymd, $period) 
    {
        $where = [];

        $query = $this
                ->from('m_period_year AS py')
                ;

        if ($period >= 0) {
            $limit = $period + 1;          
            $where[] = array('py.end_ymd', '>=', $ymd);
            $query = $query
                    ->orderBy('py.start_ymd');
            
        } else {
            $limit = abs($period) + 1;
            $where[] = array('py.start_ymd', '<=', $ymd);  
            $query = $query
                    ->orderBy('py.start_ymd', 'desc');
        }

        $data = $query
                ->where($where)
                ->limit($limit)
                ->get()
                ;

        $arr = $data->toArray();
        $rtnData = array_pop($arr);

        return $rtnData;
    }
}
