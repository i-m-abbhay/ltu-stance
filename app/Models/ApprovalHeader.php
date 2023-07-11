<?php

namespace App\Models;

use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;
use App\Libs\LogUtil;

/**
 * 承認ヘッダ
 */
class ApprovalHeader extends Model
{
    // テーブル名
    protected $table = 't_approval_header';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * IDで取得
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->where(['id' => $id])
                ->first()
                ;
        return $data;
    }

    /**
     * 申請データ作成
     *
     * @param [type] $params
     * @return void
     */
    public function add($params){
        $result = false;

        try {
            // 既に申請が存在するなら過去フラグを立てる
            $where = [
                ['process_kbn', $params['process_kbn']],
                ['process_id', $params['process_id']],
                ['past_flg', config('const.flg.off')]
            ];
            $exists = $this->where($where)->exists();
            if ($exists) {
                $this->where($where)->update([
                    'past_flg' => config('const.flg.on'),
                    'update_user' => Auth::id(),
                    'update_at' => Carbon::now()
                ]);
            }
            // 申請追加
            $result = $this->insertGetId([
                'process_kbn' => $params['process_kbn'],
                'process_id' => $params['process_id'],
                'apply_staff_id' => $params['apply_staff_id'],
                'final_approval_order' => $params['final_approval_order'],
                'status' => config('const.approvalHeaderStatus.val.not_approved'),
                'past_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now()
            ]);

        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 承認ヘッダ取得
     *
     * @param [type] $processKbn 処理区分
     * @param [type] $processIds 処理ID 配列可
     * @param [type] $isPast 過去フラグ
     * @param [type] $isLock 占有ロック
     * @return void
     */
    public function getByProcessId($processKbn, $processId, $isPast, $isLock=false){
        $where = [];

        $where[] = array('process_kbn', '=', $processKbn);
        $where[] = array('past_flg', '=', $isPast);

        $data = $this
            ->when(is_array($processId), function($query) use($processId) {
                return $query->whereIn('process_id', $processId);
            })
            ->when(!is_array($processId), function($query) use($processId) {
                return $query->where('process_id', $processId);
            })
            ->where($where)
            ->select([
                'id',
                'process_kbn',
                'process_id',
                'apply_staff_id',
                'final_approval_order',
                'status',
                'update_at',
            ])
            ->when($isLock , function($query) {
                return $query->lockForUpdate();
            })
            ->get();
        return $data;
    }

    /**
     * ステータス更新
     *
     * @param [type] $id
     * @param [type] $status
     * @return void
     */
    public function updateStatusById($id, $status){
        $result = false;
        try {
            $updateCnt = $this
            ->where('id', $id)
            ->update([
                'status' => $status,
                'update_user' => Auth::id(),
                'update_at' => Carbon::now()
            ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 承認明細の状態を見て、承認ヘッダのステータスを更新する
     *
     * @param [type] $id
     * @return void
     */
    public function updateStatusAutoJudgeById($id){
        try {
            $ApprovalHeader = new ApprovalHeader();
            $ApprovalDetail = new ApprovalDetail();

            $approvalHeader = $ApprovalHeader->getById($id);
            $approvalDetails = $ApprovalDetail->getByHeaderId($id);

            $updateItem = [
                'update_user' => Auth::id(),
                'update_at' => Carbon::now()
            ];
            
            if ($approvalDetails->count() > 0) {
                // 承認済み(最終承認者が承認している Or 全承認権限保持者が承認している)
                $finalApprovedCnt = $approvalDetails->where('approval_order', $approvalHeader->final_approval_order)->where('status', config('const.approvalDetailStatus.val.approved'))->count();
                $fullApprovedCnt = $approvalDetails->where('approval_order', config('const.fullApprovalOrder'))->where('status', config('const.approvalDetailStatus.val.approved'))->count();
                $sendbackCnt = $approvalDetails->where('status', config('const.approvalDetailStatus.val.sendback'))->count();
                $approvedCnt = $approvalDetails->where('status', config('const.approvalDetailStatus.val.approved'))->count();

                if ($finalApprovedCnt > 0 || $fullApprovedCnt > 0) {
                    // 承認済
                    $updateItem['status'] = config('const.approvalHeaderStatus.val.approved');
                }else if($sendbackCnt > 0){
                    // 差戻
                    $updateItem['status'] = config('const.approvalHeaderStatus.val.sendback');
                }else if($approvedCnt > 0){
                    // 承認中
                    $updateItem['status'] = config('const.approvalHeaderStatus.val.approving');
                }else{
                    // 未承認
                    $updateItem['status'] = config('const.approvalHeaderStatus.val.not_approved');
                }
            }else{
                // 承認明細がない=得意先ランクを満たしたことによる自動承認
                $updateItem['status'] = config('const.approvalHeaderStatus.val.approved');
            }

            // ログ書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

            // アップデート
            $this->where('id', $id)->update($updateItem);

        } catch(\Exception $e) {
            throw $e;
        }
        return $updateItem['status'];
    }

    /**
     * 申請削除
     *
     * @param [type] $id
     * @return void
     */
    public function deleteById($id){
        $result = true;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $deleteCnt = $this
                ->where('id',  $id)
                ->delete();

            $result = ($deleteCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 過去フラグを更新する
     * 
     * @param [type] $processKbn
     * @param [type] $processId
     * @param [type] $pastFlg
     * @return bool
     */
    public function updatePastFlgByProcessId($processKbn, $processId, $pastFlg){
        $result = false;
        try {
            $where = [
                ['process_kbn', $processKbn],
                ['process_id', $processId],
            ];
            $exists = $this->where($where)->exists();
            if ($exists) {
                $updateCnt = $this->where($where)->update([
                    'past_flg' => $pastFlg,
                    'update_user' => Auth::id(),
                    'update_at' => Carbon::now()
                ]);
                $result = ($updateCnt > 0);
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

}