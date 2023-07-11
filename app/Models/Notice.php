<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;

/**
 * 通知
 */
class Notice extends Model
{
    // テーブル名
    protected $table = 't_notice';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;
    
    /**
     * 引数で指定した担当者の通知を取得
     * 基本的にはログイン者を想定
     *
     * @param integer $staffId
     * @return object
     */
    public function getNoticeById(int $staffId){
        // Where句作成
        $where = [];
        $where[] = array('t_notice.target_staff_id', '=', $staffId);
        
        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'desc')
                ->get();

        return $data;
    }

    /**
     * 引数で指定した担当者の通知を取得
     * 基本的にはログイン者を想定
     *
     * @param integer $staffId
     * @return object
     */
    public function getNoticesByStaffId(int $staffId){
        // Where句作成
        $where = [];
        $where[] = array('t_notice.staff_id', '=', $staffId);

        // データ取得
        $data = $this
                    ->select(
                        't_notice.*', 
                        'm_staff.staff_name AS created_user_name'
                    )
                    ->leftjoin('m_staff', 'm_staff.id', '=', 't_notice.created_user')
                    ->where($where)
                    ->orderBy('id', 'desc')
                    ->get();

        return $data;
    }

    /**
     * 登録
     * @param array $params(通知対象:staff_id, 内容:content, リダイレクト先:redirect_url)
     * @return boolean True:成功 False:失敗 
     */
    public function add($params) {
        $result = false;
        try {
            $result = $this->insert([
                'notice_flg' => $params['notice_flg'],
                'staff_id' => $params['staff_id'],
                'content' => $params['content'],
                'redirect_url' => $params['redirect_url'],
                //'read_at' => Carbon::now(),
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 一括登録
     * 
     * @param array $paramsList (通知対象:staff_id, 内容:content, リダイレクト先:redirect_url)の配列
     * @return boolean True:成功 False:失敗 
     */
    public function addList($paramsList) {
        $result = false;
        try {
            $insertData = [];
            $data = [];
            $data['del_flg'] = config('const.flg.off');
            $data['created_user'] = Auth::user()->id;
            $data['created_at'] = Carbon::now();
            $data['update_user'] = Auth::user()->id;
            $data['update_at'] = Carbon::now();
            foreach ($paramsList as $params) {
                $data['notice_flg'] = $params['notice_flg'];
                $data['staff_id'] = $params['staff_id'];
                $data['content'] = $params['content'];
                $data['redirect_url'] = $params['redirect_url'];
                $insertData[] = $data;
            }

            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 既読にする（失敗しても何もしない）
     *
     * @param integer $id
     * @return void
     */
    public function MarkAsRead(int $id): void{
        try {
            $this
                ->where('id', $id)
                ->update([
                    'read_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            // 何もしない
        }
    }


    /**
     * 通知種別ごとに最新の200件を取得する
     *
     * @param integer $staffId
     * @param integer $notice_flg
     * @return void
     */
    public function getNoticesByNoticeflag(int $staffId ,int $notice_flg)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_notice.staff_id', '=', $staffId);
        $where[] = array('t_notice.notice_flg', '=', $notice_flg);
        // データ取得
        $data = $this
                    ->select(
                        't_notice.*', 
                        'm_staff.staff_name AS created_user_name'
                    )
                    ->leftjoin('m_staff', 'm_staff.id', '=', 't_notice.created_user')
                    ->where($where)
                    ->orderBy('id', 'desc')
                    ->limit(200)
                    ->get();

        return $data;
    }
}