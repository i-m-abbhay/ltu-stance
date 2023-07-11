<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 見積階層
 */
class QuoteLayer extends Model
{
    // テーブル名
    protected $table = 't_quote_layer';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    // /**
    //  * 見積階層データ存在確認
    //  *
    //  * @param array $param
    //  * @return void
    //  */
    // public function isExist($param) {
    //     // Where句作成
    //     $where = [];
    //     $where[] = array('quote_no', '=',  $param['quote_no']);
    //     $where[] = array('quote_version', '=',  $param['quote_version']);
    //     $where[] = array('parent_quote_layer_id', '=', $param['parent_quote_layer_id']);
    //     $where[] = array('construction_id', '=', $param['construction_id']);
    //     $where[] = array('class_big_id', '=', $param['class_big_id']);
    //     $where[] = array('class_middle_id', '=', $param['class_middle_id']);
    //     $where[] = array('usage_id', '=', $param['usage_id']);

    //     // データ取得
    //     $data = $this
    //             ->where($where)
    //             ->count()
    //             ;

    //     return $data > 0;
    // }

    /**
     * 階層情報から見積階層データ取得
     *
     * @param [type] $param
     * @return void
     */
    public function getData($param) {
        // Where句作成
        $where = [];
        $where[] = array('quote_no', '=',  $param['quote_no']);
        $where[] = array('quote_version', '=',  $param['quote_version']);
        // $where[] = array('parent_quote_layer_id', '=', $param['parent_quote_layer_id']);
        $where[] = array('construction_id', '=', $param['construction_id']);
        $where[] = array('class_big_id', '=', $param['class_big_id']);
        $where[] = array('class_middle_id', '=', $param['class_middle_id']);
        $where[] = array('usage_id', '=', $param['usage_id']);

        // データ取得
        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 見積番号に紐づく階層データ取得
     *
     * @param string $quoteNo
     * @return void
     */
    public function getDataList($quoteNo, $quoteVersion = null) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_layer.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_layer.del_flg', '=', config('const.flg.off'));
        if($quoteVersion !== null){
            $where[] = array('t_quote_layer.quote_version', '=', $quoteVersion);
        }

        // データ取得
        $data = $this
                ->select([
                    't_quote_layer.id AS quote_layer_id',
                    't_quote_layer.quote_no',
                    't_quote_layer.quote_version',
                    't_quote_layer.parent_quote_layer_id',
                    't_quote_layer.display_order',
                    't_quote_layer.class_type',
                    't_quote_layer.construction_id',
                    't_quote_layer.class_big_id',
                    't_quote_layer.class_middle_id',
                    't_quote_layer.usage_id',
                    't_quote_layer.usage_id as class_small_id',
                    't_quote_layer.layer_name',
                    't_quote_layer.complete_flg',
                    't_quote_layer.copy_quote_layer_id',
                    't_quote_layer.copy_complete_flg'
                ])
                ->where($where)
                // ->orderBy('t_quote_layer.quote_no', 'asc')
                ->orderBy('t_quote_layer.quote_version', 'asc')
                // ->orderBy('t_quote_layer.class_type', 'asc')
                ->orderBy('t_quote_layer.construction_id', 'asc')
                ->orderBy('t_quote_layer.class_big_id', 'asc')
                ->orderBy('t_quote_layer.class_middle_id', 'asc')
                ->orderBy('t_quote_layer.usage_id', 'asc')
                ->orderBy('t_quote_layer.display_order', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 見積番号と見積版から見積階層データを取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getDataListByVer($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_layer.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_layer.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_layer.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->select([
                    't_quote_layer.id AS quote_layer_id',
                    't_quote_layer.quote_no',
                    't_quote_layer.quote_version',
                    't_quote_layer.parent_quote_layer_id',
                    't_quote_layer.display_order',
                    't_quote_layer.class_type',
                    't_quote_layer.construction_id',
                    't_quote_layer.class_big_id',
                    't_quote_layer.class_middle_id',
                    't_quote_layer.usage_id',
                    't_quote_layer.usage_id as class_small_id',
                    't_quote_layer.layer_name',
                    't_quote_layer.complete_flg',
                    't_quote_layer.copy_quote_layer_id',
                    't_quote_layer.copy_complete_flg'
                ])
                ->where($where)
                // ->orderBy('t_quote_layer.quote_no', 'asc')
                ->orderBy('t_quote_layer.quote_version', 'asc')
                // ->orderBy('t_quote_layer.class_type', 'asc')
                ->orderBy('t_quote_layer.construction_id', 'asc')
                ->orderBy('t_quote_layer.class_big_id', 'asc')
                ->orderBy('t_quote_layer.class_middle_id', 'asc')
                ->orderBy('t_quote_layer.usage_id', 'asc')
                ->orderBy('t_quote_layer.display_order', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 登録
     *
     * @param array $params
     * @return int 見積階層ID
     */
    public function add($params) {
        try {
            $items = [];
            $items['quote_no'] = $params['quote_no'];
            $items['quote_version'] = $params['quote_version'];
            $items['parent_quote_layer_id'] = $params['parent_quote_layer_id'];
            $items['display_order'] = $params['display_order'];
            $items['class_type'] = $params['class_type'];
            $items['construction_id'] = $params['construction_id'];
            $items['class_big_id'] = $params['class_big_id'];
            $items['class_middle_id'] = $params['class_middle_id'];
            $items['usage_id'] = $params['usage_id'];
            $items['layer_name'] = $params['layer_name'];
            $items['complete_flg'] = config('const.flg.off');
            $items['del_flg'] = config('const.flg.off');
            $items['created_user'] = Auth::user()->id;
            $items['created_at'] = Carbon::now();
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 登録
            $id = $this->insertGetId($items);

            return $id;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * 更新
     *
     * @param int $id 見積階層ID
     * @param array $params 
     * @return void
     */
    public function updateById($quoteLayerId, $params) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $quoteLayerId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            // 更新内容
            $items = [];
            $items['display_order'] = $params['display_order'];
            $items['layer_name'] = $params['layer_name'];
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

            $result = ($updateCnt == 1);
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 見積番号に紐づく階層データ取得
     * 
     * @param $idList 見積階層IDのリスト
     * @return ツリーデータ
     */
    public function getDataListByIdList($idList) {
        $where = [];
        $where[] = array('l1.del_flg', '=', config('const.flg.off'));
        $where[] = array('l2.del_flg', '=', config('const.flg.off'));
        $where[] = array('l3.del_flg', '=', config('const.flg.off'));

        $mainQuery = $this->select([
                't_quote_layer.id AS quote_layer_id',
                't_quote_layer.quote_no',
                't_quote_layer.quote_version',
                't_quote_layer.parent_quote_layer_id',
                't_quote_layer.display_order',
                't_quote_layer.class_type',
                't_quote_layer.construction_id',
                't_quote_layer.class_big_id',
                't_quote_layer.class_middle_id',
                't_quote_layer.usage_id',
                't_quote_layer.usage_id as class_small_id',
                't_quote_layer.layer_name',
                't_quote_layer.complete_flg',
                't_quote_layer.copy_quote_layer_id',
                't_quote_layer.copy_complete_flg',
            ])
            ->whereRaw('id in('.implode(',',$idList).')')
            ->whereRaw('del_flg = '. config('const.flg.off'))
            ->toSql();

        // データ取得
        $data = DB::table(DB::raw('('.$mainQuery.') AS l4'))
                ->join('t_quote_layer as l3', 'l4.parent_quote_layer_id', 'l3.id')
                ->join('t_quote_layer as l2', 'l3.parent_quote_layer_id', 'l2.id')
                ->join('t_quote_layer as l1', 'l2.parent_quote_layer_id', 'l1.id')
                ->select([
                    'l4.*',

                    'l3.id AS l3_quote_layer_id',
                    'l3.quote_no as l3_quote_no',
                    'l3.quote_version as l3_quote_version',
                    'l3.parent_quote_layer_id as l3_parent_quote_layer_id',
                    'l3.display_order as l3_display_order',
                    'l3.class_type as l3_class_type',
                    'l3.construction_id as l3_construction_id',
                    'l3.class_big_id as l3_class_big_id',
                    'l3.class_middle_id as l3_class_middle_id',
                    'l3.usage_id as l3_usage_id',
                    'l3.usage_id as l3_class_small_id',
                    'l3.layer_name as l3_layer_name',
                    'l3.complete_flg as l3_complete_flg',
                    'l3.copy_quote_layer_id as l3_copy_quote_layer_id',
                    'l3.copy_complete_flg as l3_copy_complete_flg',

                    'l2.id AS l2_quote_layer_id',
                    'l2.quote_no as l2_quote_no',
                    'l2.quote_version as l2_quote_version',
                    'l2.parent_quote_layer_id as l2_parent_quote_layer_id',
                    'l2.display_order as l2_display_order',
                    'l2.class_type as l2_class_type',
                    'l2.construction_id as l2_construction_id',
                    'l2.class_big_id as l2_class_big_id',
                    'l2.class_middle_id as l2_class_middle_id',
                    'l2.usage_id as l2_usage_id',
                    'l2.usage_id as l2_class_small_id',
                    'l2.layer_name as l2_layer_name',
                    'l2.complete_flg as l2_complete_flg',
                    'l2.copy_quote_layer_id as l2_copy_quote_layer_id',
                    'l2.copy_complete_flg as l2_copy_complete_flg',

                    'l1.id AS l1_quote_layer_id',
                    'l1.quote_no as l1_quote_no',
                    'l1.quote_version as l1_quote_version',
                    'l1.parent_quote_layer_id as l1_parent_quote_layer_id',
                    'l1.display_order as l1_display_order',
                    'l1.class_type as l1_class_type',
                    'l1.construction_id as l1_construction_id',
                    'l1.class_big_id as l1_class_big_id',
                    'l1.class_middle_id as l1_class_middle_id',
                    'l1.usage_id as l1_usage_id',
                    'l1.usage_id as l1_class_small_id',
                    'l1.layer_name as l1_layer_name',
                    'l1.complete_flg as l1_complete_flg',
                    'l1.copy_quote_layer_id as l1_copy_quote_layer_id',
                    'l1.copy_complete_flg as l1_copy_complete_flg',
                ])
                ->where($where)
                ->orderBy('l4.display_order', 'asc')
                ->get()
                ;

        $COL_LIST = [
            'quote_layer_id',
            'quote_no',
            'quote_version',
            'parent_quote_layer_id',
            'display_order',
            'class_type',
            'construction_id',
            'class_big_id',
            'class_middle_id',
            'usage_id',
            'class_small_id',
            'layer_name',
            'complete_flg',
            'copy_quote_layer_id',
            'copy_complete_flg'
        ];

        $COL_KEY = [
            'l1_',
            'l2_',
            'l3_',
            '',
        ];
        
        $result = [];
        foreach($data as $record){
            foreach($COL_KEY as $KEY){
                $cnvRec = [];
                foreach($COL_LIST as $COL_NAME){
                    $reNameCol = $KEY.$COL_NAME;
                    $cnvRec[$COL_NAME] = $record->$reNameCol;
                }
                $uniqueKey = $cnvRec['construction_id'].'_'.$cnvRec['class_big_id'].'_'.$cnvRec['class_middle_id'].'_'.$cnvRec['class_small_id'];
                if(!isset($result[$uniqueKey])){
                    $result[$uniqueKey] = $cnvRec;
                }
            }
        }

        return array_values($result);
    }

    /**
     * 積算完了へ更新
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @param array $bigIdList 大分類IDの配列
     * @return void
     */
    public function updateCompEstimation($quoteNo, $quoteVersion, $bigIdList) {
        try {
            // 条件
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);
            $where[] = array('quote_version', '=', $quoteVersion);
            $where[] = array('complete_flg', '=', config('const.flg.off'));

            foreach ($bigIdList as $bigId) {
                // 大分類1つずつ処理
                $useWhere = $where;
                $useWhere[] = array('class_big_id', '=', $bigId);

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                $LogUtil->putByWhere($this->table, $useWhere, config('const.logKbn.update'));
                
                // 更新内容
                $items = [];
                $items['complete_flg'] = config('const.flg.on');
                $items['update_user'] = Auth::user()->id;
                $items['update_at'] = Carbon::now();

                // 更新
                $updateCnt = $this
                            ->where($useWhere)
                            ->update($items)
                            ;
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 論理削除
     *
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function deleteByQuoteNo($quoteNo) {
        try {
            // 条件
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);
            $where[] = array('del_flg', '=', config('const.flg.off'));

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.soft_delete'));

            // 更新内容
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                    ->where($where)
                    ->update($items);

        } catch (\Exception $e) {
            throw $e;
        }
    }
}