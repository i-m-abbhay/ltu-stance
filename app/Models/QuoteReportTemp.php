<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;

/**
 * 見積書テンポラリテーブル
 */
class QuoteReportTemp extends Model
{
    // テーブル名
    protected $table = 't_quote_report_temp';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 見積書出力用一時データ登録
     *
     * @param [type] $paramsList [quote_detail_id:見積明細ID]
     * @return void
     */
    public function addList($quoteNo, $quoteVersion, $paramsList) {
        $result = false;
        try {
            $this->where([
                ['quote_no', '=', $quoteNo],
                ['quote_version', '=', $quoteVersion],
                ['created_user', '=', Auth::user()->id],
            ])->delete();

            $insertData = [];
            $data = [];
            $data['created_user'] = Auth::user()->id;
            $data['created_at'] = Carbon::now();
            foreach ($paramsList as $params) {
                $data['quote_no'] = $quoteNo;
                $data['quote_version'] = $quoteVersion;
                $data['quote_detail_id'] = $params['id'];
                $insertData[] = $data;
            }

            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 登録
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @param array $quoteDetailIdList 見積明細IDリスト
     * @return void
     */
    public function addIdList($quoteNo, $quoteVersion, $quoteDetailIdList) {
        try {
            // 削除
            $this
                ->where([
                    ['quote_no', '=', $quoteNo],
                    ['quote_version', '=', $quoteVersion],
                    ['created_user', '=', Auth::user()->id],
                ])
                ->delete();

            // データ作成
            $insertData = [];
            $data = [];
            $data['quote_no'] = $quoteNo;
            $data['quote_version'] = $quoteVersion;
            $data['created_user'] = Auth::user()->id;
            $data['created_at'] = Carbon::now();
            foreach ($quoteDetailIdList as $id) {
                $row = $data;
                $row['quote_detail_id'] = $id;
                $insertData[] = $row;
            }

            // 登録
            $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
    }
    

}