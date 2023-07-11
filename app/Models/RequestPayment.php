<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 支払申請
 */
class RequestPayment extends Model
{
    // テーブル名
    protected $table = 't_request_payment';
    // タイムスタンプ自動更新Off
    public $timestamps = false;


    /**
     * 登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'apply_date' => $params['apply_date'],
                'apply_staff_id' => $params['apply_staff_id'],
                'approval_date1' => $params['approval_date1'],
                'approval_staff_id1' => $params['approval_staff_id1'],
                'approval_date2' => $params['approval_date2'],
                'approval_staff_id2' => $params['approval_staff_id2'],
                'approval_date3' => $params['approval_date3'],
                'approval_staff_id3' => $params['approval_staff_id3'],
                'planned_payment_date' => $params['planned_payment_date'],
                'total_amount' => $params['total_amount'],
                'apply_comment' => $params['apply_comment'],
                'attachments_file' => $params['attachments_file'],
                'approval_comment' => $params['approval_comment'],
                'status' => $params['status'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if(!isset($params['update_user']) || empty($params['update_user'])){
                $params['update_user'] = Auth::user()->id;
            }
            if(!isset($params['update_at']) || empty($params['update_at'])){
                $params['update_at'] = Carbon::now();
            }
            
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($params)
                    ;
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     *
     * @param int $matterId ID
     * @return void
     */
    public function deleteById($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            // 更新内容
            $items['status'] = config('const.paymentRequestStatus.val.not_approval');
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where('id', $id)
                ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * IDから取得
     *
     * @param [type] $id
     * @return void
     */
    public function getById($id)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('id', '=', $id);

        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * IDから取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getByIdList($ids)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->whereIn('id', $ids)
                ->get()
                ;

        return $data;
    }


    /**
     * 担当者IDから取得
     * 承認可能な申請リスト
     *
     * @param $userId        ログインユーザID
     * @param $startDate   支払月 yyyy/mm/dd
     * @param $endDate   支払月 yyyy/mm/dd
     * @return void
     */
    public function getRequestList($userId, $startDate, $endDate) 
    {
        $where = [];
        $where[] = array('rp.del_flg', '=', config('const.flg.off'));
        $betWeen = array($startDate. ' 00:00:00', $endDate. ' 23:59:59');
        // $where[] = array('planned_payment_date', 'LIKE', '%'.$startDate.'%');

        $query = $this
                ->from('t_request_payment AS rp')
                ->leftJoin('m_staff AS app_staff', 'app_staff.id', '=', 'rp.apply_staff_id')
                ->where($where)
                ->whereBetween('rp.apply_date', $betWeen)
                ;

        // ユーザーの承認権限取得
        $query->where(function($q) use($userId) {
            $str = '';
            if(Authority::hasAuthority($userId, config('const.auth.purchase.approval_staff_1'))) {
                $str .= 'rp.approval_staff_id1 IS NULL AND rp.status = '. config('const.paymentRequestStatus.val.not_approval');
                // $q->whereRaw('rp.approval_staff_id1 IS NULL');
            }
            if(Authority::hasAuthority($userId, config('const.auth.purchase.approval_staff_2'))) {
                if (strlen($str) > 0) {
                    $str .= ' OR ';
                }
                $str .= 'rp.approval_staff_id2 IS NULL AND rp.status = '. config('const.paymentRequestStatus.val.approval1');
                // $q->whereRaw('rp.approval_staff_id2 IS NULL');
            }
            if(Authority::hasAuthority($userId, config('const.auth.purchase.approval_staff_3'))) {
                if (strlen($str) > 0) {
                    $str .= ' OR ';
                }
                $str .= 'rp.approval_staff_id3 IS NULL AND rp.status = '. config('const.paymentRequestStatus.val.approval2');
                // $q->whereRaw('rp.approval_staff_id3 IS NULL');
            }
            $q->whereRaw($str);
        });
        

        $data = $query
                ->selectRaw('
                    rp.id,
                    DATE_FORMAT(rp.apply_date, "%Y/%m/%d") AS apply_date,
                    rp.apply_staff_id,
                    DATE_FORMAT(rp.approval_date1, "%Y/%m/%d") AS approval_date1,
                    rp.approval_staff_id1,
                    DATE_FORMAT(rp.approval_date2, "%Y/%m/%d") AS approval_date2,
                    rp.approval_staff_id2,
                    DATE_FORMAT(rp.approval_date3, "%Y/%m/%d") AS approval_date3,
                    rp.approval_staff_id3,
                    DATE_FORMAT(rp.planned_payment_date, "%Y/%m/%d") AS planned_payment_date,
                    rp.total_amount,
                    rp.apply_comment,
                    rp.attachments_file,
                    rp.approval_comment,
                    rp.status,
                    rp.del_flg,
                    app_staff.staff_name AS apply_staff_name
                ')
                ->orderBy('rp.status', 'asc')
                ->get()
                ;

        return $data;
    }
}
