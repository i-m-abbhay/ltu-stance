<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 見積依頼明細
 */
class QuoteRequestDetail extends Model
{
    // テーブル名
    protected $table = 't_quote_request_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 登録
     *
     * @param int $quoteRequestId 見積依頼ID
     * @param array $params
     * @return void
     */
    public function addList($quoteRequestId, $params) {
        try {

            foreach ($params as $i => $row) {
                $items = $row;
                $items['quote_request_id'] = $quoteRequestId;
                $items['seq_no'] = $i + 1;
                $items['created_user'] = Auth::user()->id;
                $items['created_at'] = Carbon::now();
                $items['update_user'] = Auth::user()->id;
                $items['update_at'] = Carbon::now();

                // 登録
                $this->insert($items);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * 更新（削除して登録）
     *
     * @param int $quoteRequestId 見積依頼ID
     * @param array $params 
     * @return void
     */
    public function updateList($quoteRequestId, $params) {
        $result = false;
        try {
            // 削除条件
            $where = [];
            $where[] = array('quote_request_id', '=', $quoteRequestId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            // 一旦全削除
            $delResult = $this
                        ->where($where)
                        ->delete()
                        ;

            if ($delResult >= 0) {
                // 登録
                $this->addList($quoteRequestId, $params);
            }

            $result = true;
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     *
     * @param int $quoteRequestId 見積依頼ID
     * @return void
     */
    public function deleteList($quoteRequestId) {
        try {
            // 削除条件
            $where = [];
            $where[] = array('quote_request_id', '=', $quoteRequestId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.soft_delete'));

            // 更新内容
            $items = [];
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $resultCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 見積依頼項目ごとに削除
     *
     * @param int $quoteRequestId 見積依頼項目ID
     * @param int $qreqKbn 見積依頼項目区分（工事区分ID）
     * @return void
     */
    public function deleteByQuoteRequestKbn($quoteRequestId, $qreqKbn) {
        try {
            // 削除条件
            $where = [];
            $where[] = array('quote_request_id', '=', $quoteRequestId);
            $where[] = array('quote_request_kbn', '=', $qreqKbn);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            // 削除
            $delResult = $this
                        ->where($where)
                        ->delete()
                        ;

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 見積依頼明細データ 仕様項目存在確認
     *
     * @param array $param
     * @return void
     */
    public function isExistSpecItem($specItemHeaderId) {
        // Where句作成
        $where = [];
        $where[] = array('spec_item_header_id', '=', $specItemHeaderId);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->count()
                ;

        return $data > 0;
    }
}