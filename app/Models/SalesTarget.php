<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 担当者別売上目標
 */
class SalesTarget extends Model
{
    // テーブル名
    protected $table = 't_salestarget';
    // タイムスタンプ自動更新Off
    public $timestamps = false;


    /**
     * 売上目標取得
     *
     * @param $staff_id     担当者ID
     * @param $target_year  年度(yyyy)
     * @return void
     */
    public function getTargetPriceByStaffId($staff_id, $target_year)
    {
        $where = [];
        $where[] = array('staff_id', '=', $staff_id);
        $where[] = array('target_year', '=', $target_year);

        $data = $this
                ->where($where)
                ->selectRaw('
                    staff_id,
                    target_price
                ')
                ->get()
                ;

        return $data;
    }
}
