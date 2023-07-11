<?php
namespace App\Libs;

use DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;
use Auth;

/**
 * ログ
 */
class LogUtil
{
    /**
     * テーブルログ登録（モデルインスタンス）
     *
     * @param Model $data       データ（モデルインスタンス）
     * @param string $logKbn    ログ区分  config('const.logKbn'）
     * @param string $tableName テーブル名
     * @return void
     */
    public function putByData($data, $logKbn, $tableName = null) {
        // テーブル名
        if (is_null($tableName)) {
            $tableName = $data->getTable();
        }
        // カラム名取得
        $columnList = Schema::getColumnListing($tableName);
        // ログテーブル
        $logTable = DB::table($tableName.'_logs');

        // バッチ処理の場合、ログインユーザーが存在しない
        $userId = 0;
        if (Auth::user() && isset(Auth::user()->id)) {
            $userId = Auth::user()->id;
        }

        // ログデータ作成
        $insertData = [];
        $insertData['log_kbn'] = $logKbn;
        $insertData['log_create_user'] = $userId;
        $insertData['log_create_date'] = Carbon::now();
        $insertData['element_id'] = $data->id;
        foreach ($columnList as $colName) {
            if ($colName === 'id') {
                continue;
            }
            $insertData[$colName] = $data->$colName;
        }
        
        $logTable->insert($insertData);
    }

    /**
     * テーブルログ登録（テーブル名とPrimaryKey）
     *
     * @param string $tableName  テーブル名
     * @param int $id            プライマリーキー（id）
     * @param string $logKbn     ログ区分  config('const.logKbn'）
     * @return void
     */
    public function putById($tableName, $id, $logKbn) {
        // 元テーブル
        $table = DB::table($tableName);

        // ログに残すデータを取得（必ず1件）
        $data = $table->where('id', $id)->first();
        if (!is_null($data)) {
            $this->putByData($data, $logKbn, $tableName);
        }
    }

    /**
     * テーブルログ登録（削除条件）削除条件
     *
     * @param string $tableName  テーブル名
     * @param array $deleteWhere 削除条件の配列
     * @param string $logKbn     ログ区分  config('const.logKbn'）
     * @return void
     */
    public function putByWhere($tableName, $deleteWhere, $logKbn) {
        // 元テーブル
        $table = DB::table($tableName);

        // 削除対象データ取得
        $deleteDataList = $table->where($deleteWhere)->get(); 
        foreach ($deleteDataList as $deleteData) {
            $this->putByData($deleteData, $logKbn, $tableName);
        }
    }

}