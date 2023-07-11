<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;

/**
 * 得意先担当者変更
 */
class CustomerStaff extends Model
{
    // テーブル名
    protected $table = 't_customer_staff';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 一覧取得
     *
     * @param  $params
     * @return void
     */
    public function getList($params)
    {
        $where = [];
        $where[] = array('cs.del_flg', '=', config('const.flg.off'));

        if (!empty($params['staff_name'])) {
            $where[] = array('cs.apply_staff_id', '=', $params['staff_name']);
        }
        if (!empty($params['apply_date'])) {
            $where[] = array('cs.apply_date', '=', $params['apply_date']);
        }

        $data = $this
                ->from('t_customer_staff AS cs')
                ->where($where)
                ->leftJoin('t_customer_staff_detail AS cs_detail', function($join) {
                    $join
                        ->on('cs.id', '=', 'cs_detail.customer_staff_id')
                        ->where('cs_detail.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('m_staff AS to_staff', 'to_staff.id', '=', 'cs_detail.to_staff_id')
                ->leftJoin('m_staff AS from_staff', 'from_staff.id', '=', 'cs_detail.from_staff_id')
                ->leftJoin('m_department AS to_department', 'to_department.id', '=', 'cs_detail.to_department_id')
                ->leftJoin('m_department AS from_department', 'from_department.id', '=', 'cs_detail.from_department_id')
                ->selectRaw('
                    cs.id AS id,
                    DATE_FORMAT(cs.apply_date, "%Y/%m/%d") as apply_date,
                    cs.apply_staff_comment,
                    cs.apply_staff_id,
                    cs.approval_staff_id,
                    DATE_FORMAT(cs.approval_at, "%Y/%m/%d") as approval_at,
                    cs.approval_comment,
                    cs_detail.id AS detail_id,
                    cs_detail.customer_id,
                    cs_detail.from_department_id,
                    cs_detail.from_staff_id,
                    cs_detail.to_department_id,
                    cs_detail.to_staff_id,
                    CASE
                        WHEN cs.approval_status ='. config('const.customerStaffApprovalStatus.val.not_approval'). '
                            THEN \''. config('const.customerStaffApprovalStatus.status.not_approval'). '\'
                        WHEN cs.approval_status ='. config('const.customerStaffApprovalStatus.val.approvaled'). '
                            THEN \''. config('const.customerStaffApprovalStatus.status.approvaled'). '\'
                        WHEN cs.approval_status ='. config('const.customerStaffApprovalStatus.val.rejection'). '
                            THEN \''. config('const.customerStaffApprovalStatus.status.rejection'). '\'
                    END AS approval_status
                ')
                ->get()
                ;

        return $data;
    }

     /**
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;

        try{
            $result = $this->insertGetId([
                'apply_staff_id' => Auth::user()->id,
                'apply_date' => $params['apply_date'],
                'apply_staff_comment' => $params['apply_staff_comment'],
                'approval_status' => config('const.flg.off'),
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);


            return $result;
        }catch (\Exception $e){
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

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        // 'apply_staff_id' => Auth::user()->id,
                        'apply_date' => $params['apply_date'],
                        'apply_staff_comment' => $params['apply_staff_comment'],
                        'approval_status' => config('const.flg.off'),
                        'del_flg' => config('const.flg.off'),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param $id
     * @return void
     */
    public function deleteById($id)
    {
        $result = false;
        try{
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
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param $id
     * @return void
     */
    public function physicalDeleteById($id)
    {
        $result = false;
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $result = $this
                ->where('id', $id)
                ->delete()
                ;
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 否認
     *
     * @param $id
     * @param $status
     * @return void
     */
    public function rejection($params)
    {
        $result = false;

        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'approval_status' => config('const.customerStaffApprovalStatus.val.rejection'),
                    'approval_staff_id' => Auth::user()->id,
                    'approval_at' => Carbon::now(),
                    'approval_comment' => $params['approval_comment'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ])
                ;
            $result = ($updateCnt > 0);
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;

    }


    /**
     * 承認
     *
     * @param  $params
     * @return void
     */
    public function approval($params) 
    {
        $result = false;

        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'approval_status' => config('const.customerStaffApprovalStatus.val.approvaled'),
                    'approval_staff_id' => Auth::user()->id,
                    'approval_at' => Carbon::now(),
                    'approval_comment' => $params['approval_comment'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ])
                ;
            $result = ($updateCnt > 0);
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;

    }


}