<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 仕様初期値
 */
class SpecDefault extends Model
{
    // テーブル名
    protected $table = 't_spec_default';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * データ取得
     * 
     * @param int $customerId 得意先ID
     * @return 
     */
    public function getByCustomerId($customerId) {
        // Where句作成
        $where = [];
        $where[] = array('t_spec_default.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_spec_default.customer_id', '=', $customerId);

        // データ取得
        $data = $this
                ->join('t_spec_item_detail', 't_spec_item_detail.id', 't_spec_default.spec_item_detail_id')
                ->join('m_item', 'm_item.id', 't_spec_item_detail.item_id')
                ->where($where)
                ->select([
                    't_spec_default.quote_request_kbn',
                    't_spec_default.spec_item_header_id',
                    't_spec_default.spec_item_detail_id',
                    't_spec_default.input_value01',
                    't_spec_default.input_value02',
                    't_spec_default.input_value03',
                    'm_item.item_type'
                ])
                ->get()
                ;

        return $data;
    }

    /**
     * 得意先IDと見積依頼項目をキーとして一括登録
     *
     * @param int $customerId 得意先ID
     * @param array $params 登録データリスト
     * @return void
     */
    public function addList($customerId, $params) {
        try {
            $insertData = [];
            foreach ($params as $row) {
                $items = [];
                $items['customer_id'] = $customerId;
                $items['quote_request_kbn'] = $row['quote_request_kbn'];
                $items['spec_item_header_id'] = $row['spec_item_header_id'];
                $items['spec_item_detail_id'] = $row['spec_item_detail_id'];

                $items['input_value01'] = null;
                if (isset($row['input_value01'])) {
                    $items['input_value01'] = $row['input_value01'];
                }
                $items['input_value02'] = null;
                if (isset($row['input_value02'])) {
                    $items['input_value02'] = $row['input_value02'];
                }
                $items['input_value03'] = null;
                if (isset($row['input_value03'])) {
                    $items['input_value03'] = $row['input_value03'];
                }

                $items['del_flg'] = config('const.flg.off');
                $items['created_user'] = Auth::user()->id;
                $items['created_at'] = Carbon::now();
                $items['update_user'] = Auth::user()->id;
                $items['update_at'] = Carbon::now();
                $insertData[] = $items;
            }
            // 登録
            $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * 更新（得意先IDと見積依頼項目をキーとして削除、得意先IDで登録）
     *
     * @param int $customerId 得意先ID
     * @param int $quoteRequestKbns 見積依頼項目
     * @param array $params 更新データリスト
     * @return void
     */
    public function updateList($customerId, $quoteRequestKbns, $params) {
        $result = false;
        try {
            if (count($quoteRequestKbns) > 0) {
                foreach ($quoteRequestKbns as $kbn) {
                    // 削除条件
                    $where = [];
                    $where[] = array('customer_id', '=', $customerId);
                    $where[] = array('quote_request_kbn', '=', $kbn);

                    // ログテーブルへの書き込み
                    $LogUtil = new LogUtil();
                    $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

                    // 一旦全削除
                    $this->where($where)->delete();
                }

                // 登録
                $this->addList($customerId, $params);
            }
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
    
}