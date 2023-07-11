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
class ApprovalDetail extends Model
{
    // テーブル名
    protected $table = 't_approval_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'approval_header_id',
        'approval_staff_id',
        'approval_order',
        'comment',
        'status',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
    ];

    /**
     * 申請データ作成
     *
     * @param [type] $paramsList
     * @param [type] $approvalHeaderId
     * @return void
     */
    public function addList($paramsList, $approvalHeaderId){
        $result = false;

        try {
            $userId = Auth::user()->id;
            $now = Carbon::now();

            $insertData = [];
            $data = [];
            $data['approval_header_id'] = $approvalHeaderId;
            foreach ($paramsList as $params) {
                if(!isset($params['status'])){
                    $data['status'] = config('const.approvalDetailStatus.val.not_approved');
                }
                if(!isset($params['created_user'])){
                    $data['created_user'] = $userId;
                }
                if(!isset($params['created_at'])){
                    $data['created_at'] = $now;
                }
                if(!isset($params['update_user'])){
                    $data['update_user'] = $userId;
                }
                if(!isset($params['update_at'])){
                    $data['update_at'] = $now;
                }
                $registerCol = $this->getFillable();
                foreach($registerCol as $colName){
                    if(array_key_exists($colName, $params)){
                        $data[$colName] = $params[$colName];
                    }
                }
                $insertData[] = $data;
            }
            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 申請データ更新
     *
     * @param [type] $id
     * @param [type] $comment
     * @param [type] $status
     * @return void
     */
    public function updateStatusById($id, $comment, $status){
        $result = false;
        try {
            $updateCnt = $this
                        ->where('id', $id)
                        ->update([
                            'comment' => $comment,
                            'status' => $status,
                            'update_user' => Auth::user()->id,
                            'update_at' => Carbon::now()
                        ]);
                        ;
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 承認ヘッダIDに紐づく承認明細を取得する
     *
     * @param [type] $headerId 配列可
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getByHeaderId($headerId){
        $data = $this
            ->when(is_array($headerId), function($query) use($headerId) {
                return $query->whereIn('approval_header_id', $headerId);
            })
            ->when(!is_array($headerId), function($query) use($headerId) {
                return $query->where('approval_header_id', $headerId);
            })
            ->select([
                'id',
                'approval_header_id',
                'approval_staff_id',
                'approval_order',
                DB::raw('COALESCE(comment, \'\') AS comment'),
                'status',
                'update_at',
            ])
            ->get();
        return $data;
    }

    /**
     * 申請削除
     *
     * @param [type] $headerId
     * @return void
     */
    public function deleteByHeaderId($headerId){
        $result = true;
        try {
            $where = [];
            $where[] = ['approval_header_id', $headerId];

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            $deleteCnt = $this
                    ->where($where)
                    ->delete();
            $result = ($deleteCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
}