<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Carbon\Carbon;
use App\Libs\LogUtil;


/**
 * 案件テーブル
 */
class UpdateHistory extends Model
{
    // テーブル名
    protected $table = 't_update_history';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * ヘッダーキーでデータ取得
     *
     * @param [type] $kbn
     * @param [type] $headerKey
     * @return void
     */
    public function getUpdatedColumnsByHeaderKey($kbn, $headerKey){
        $where = [];
        $where[] = array('table_kbn', $kbn);
        $where[] = array('header_key', $headerKey);

        $data = $this
                ->from('t_update_history as uh')
                ->leftJoin('m_staff as s', 'uh.created_user', 's.id')
                ->where($where)
                ->select([
                    'uh.detail_key',
                    DB::raw('DATE_FORMAT(uh.created_at, \'%Y/%m/%d %H:%i\') AS created_at'),
                    's.staff_name',
                    DB::raw('JSON_OBJECTAGG(uh.p_col_name, uh.l_col_name) AS columns'),
                ])
                ->groupBy('uh.detail_key', 'uh.created_at', 's.staff_name')
                ->orderBy('uh.detail_key', 'uh.created_at')
                ->get();
        return $data;
    }

    /**
     * 登録（複数）
     *
     * @param [type] $paramsList
     * @return void
     */
    public function addList($paramsList){
        $result = false;
        try {
            $insertData = [];
            $data = [];
            $data['created_user'] = Auth::user()->id;
            $data['created_at'] = Carbon::now();
            foreach ($paramsList as $params) {
                $data['table_kbn'] = $params['kbn'];
                $data['header_key'] = $params['header_key'];
                $data['detail_key'] = $params['detail_key'];
                $data['p_col_name'] = $params['p_col_name'];
                $data['l_col_name'] = $params['l_col_name'];
                $data['from_val'] = $params['from_val'];
                $data['to_val'] = $params['to_val'];
                $insertData[] = $data;
            }

            $insertCnt =  $this->insert($insertData);
            $result = ($insertCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * ヘッダーキーでデータ取得
     *
     * @param [type] $kbn
     * @param [type] $headerKey
     * @return void
     */
    public function getUpdatedFromToValueByHeaderKey($kbn, $headerKey){
        $where = [];
        $where[] = array('table_kbn', $kbn);
        $where[] = array('header_key', $headerKey);

        $data = $this
                ->from('t_update_history as uh')
                ->leftJoin('m_staff as s', 'uh.created_user', 's.id')
                ->where($where)
                ->select([
                    'uh.detail_key',
                    DB::raw('DATE_FORMAT(uh.created_at, \'%Y/%m/%d %H:%i\') AS created_at'),
                    's.staff_name',
                    DB::raw('JSON_OBJECTAGG(uh.p_col_name, uh.l_col_name) AS columns'),
                    DB::raw('JSON_OBJECTAGG(uh.p_col_name, uh.from_val) AS from_value'),
                    DB::raw('JSON_OBJECTAGG(uh.p_col_name, uh.to_val) AS to_value'),
                ])
                ->groupBy('uh.detail_key', 'uh.created_at', 's.staff_name')
                ->orderBy('uh.detail_key', 'uh.created_at')
                ->get();
        return $data;
    }

    /**
     * 特定の明細の特定のカラムのデータのみを取得する
     *
     * @param int $tableKbn テーブル区分 config('const.updateHistoryKbn.val.quote_detail') or config('const.updateHistoryKbn.val.order_detail')
     * @param int $headerKey ヘッダーキー
     * @param int $detailKeyList 明細キー
     * @param string $pColName カラム物理名
     * @return void
     */
    public function getByPColName($tableKbn, $headerKey, $detailKeyList, $pColName){
        $data = $this
                ->from('t_update_history')
                ->where([
                    ['table_kbn', '=', $tableKbn],
                    ['header_key', '=', $headerKey],
                    ['p_col_name', '=', $pColName]
                ])
                ->whereIn('detail_key', $detailKeyList)
                ->select([
                    'from_val'
                ])
                ->get();

        return $data;
    }

}