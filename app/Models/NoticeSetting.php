<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use DB;

/**
 * 通知設定
 */
class NoticeSetting extends Model
{
    // テーブル名
    protected $table = 't_notice_setting';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;
    
    /**
     * 通知先担当者ID一覧取得
     *
     * @param int $type 通知種別
     * @param int $fromDepartmentId 通知元部門ID
     * @param Datetime $startDate 適用開始日
     * @param int $kbn 通知区分
     * @return void
     */
    public function getToStaffList($type, $fromDepartmentId, $startDate, $kbn = null) {
        // Where句
        $where = [];
        $where[] = array('t_notice_setting.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_notice_setting.type', '=', $type);
        $where[] = array('t_notice_setting.from_department_id', '=', $fromDepartmentId);
        $where[] = array('t_notice_setting.send_notice', '=', config('const.flg.on'));
        if (!is_null($kbn)) {
            $where[] = array('t_notice_setting.kbn', '=', $kbn);
        }
        $where[] = array('t_notice_setting.start_date' , '<=', $startDate);

        // 通知設定テーブル（メイン）
        $mainQuery = DB::table('t_notice_setting')
            ->where($where)
            ->select([
                't_notice_setting.id'
                , 't_notice_setting.kbn'
            ])
            ->orderBy('t_notice_setting.start_date', 'desc')
            ->limit(1)
            ;
        
        $data = $this
            ->join(
                DB::raw('('. $mainQuery->toSql(). ') as main'),
                't_notice_setting.id', '=', 'main.id'
                )
            ->mergeBindings($mainQuery)
            ->leftjoin('t_notice_setting_detail as detail', 'main.id', '=', 'detail.notice_setting_id')
            ->select([
                'main.id',
                'main.kbn',
                'detail.to_staff_id'
            ])
            ->get()
            ;
        
        return $data;
    }
}