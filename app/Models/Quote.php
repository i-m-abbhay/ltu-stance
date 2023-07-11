<?php

namespace App\Models;

use App\Libs\Common;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Auth;
use DB;
use Illuminate\Support\Collection;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;

/**
 * 見積
 */
class Quote extends Model
{
    // テーブル名
    protected $table = 't_quote';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',                       // ID
        'quote_no',                 // 見積番号
        'matter_no',                // 案件番号
        'special_flg',              // 得意先別特別単価使用フラグ
        'person_id',                // 相手先担当者
        'construction_period',      // 工事期間
        'construction_outline',     // 工事概要
        'quote_report_to',          // 見積書宛名
        'quote_report_seq_no',      // 見積書連番
        'status',                   // 進捗状況
        'own_stock_flg',            // 当社在庫品フラグ
        'del_flg',                  // 削除フラグ
        'created_user',             // 作成ユーザ
        'created_at',               // 作成日時
        'update_user',              // 更新ユーザ
        'update_at',                // 更新日時
    ];

    /**
     * 見積依頼明細データ取得
     *
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function getQuoteRequestDetail($quoteNo) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_request.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->leftjoin('t_quote_request', 't_quote_request.matter_no', 't_quote.matter_no')
                ->leftjoin('t_quote_request_detail', 't_quote_request_detail.quote_request_id', 't_quote_request.id')
                ->join('t_spec_item_detail', 't_spec_item_detail.id', 't_quote_request_detail.spec_item_detail_id')
                ->join('m_item', 'm_item.id', 't_spec_item_detail.item_id')
                ->where($where)
                ->select([
                    't_quote_request_detail.seq_no',
                    't_quote_request_detail.quote_request_id',
                    't_quote_request_detail.quote_request_kbn',
                    't_quote_request_detail.spec_item_header_id',
                    't_quote_request_detail.spec_item_detail_id',
                    't_quote_request_detail.input_value01',
                    't_quote_request_detail.input_value02',
                    't_quote_request_detail.input_value03',
                    'm_item.item_type'
                ])
                ->orderBy('t_quote_request_detail.quote_request_id')
                ->distinct()
                ->get()
                ;

        return $data;
    }
    
    /**
     * コンボボックス用データ取得
     *
     * @return void
     */
    public function getComboList() {
        // Where句作成
        $where = [];
        $where[] = array('t_quote.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote.own_stock_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->join('t_matter', function ($join) {
                    $join->on('t_quote.matter_no', '=', 't_matter.matter_no')
                        ->where('t_matter.use_sales_flg', '=', config('const.flg.off'));  // 売上利用フラグ
                })
                ->where($where)
                ->orderBy('t_quote.id', 'asc')
                ->select([
                    't_quote.id',
                    't_quote.quote_no',
                    't_quote.matter_no',
                ])
                ->get()
                ;
        
        return $data;
    }
    
    /**
     * IDで見積データ取得
     *
     * @param string $id ID
     * @return void
     */
    public function getById($id) {
        // Where句作成
        $where = [];
        $where[] = array('id', '=', $id);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 見積番号で見積データ取得
     *
     * @param string $matterNo 案件番号
     * @return void
     */
    public function getByQuoteNo($quoteNo) {
        // Where句作成
        $where = [];
        $where[] = array('quote_no', '=', $quoteNo);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 案件番号で見積情報を取得
     *
     * @return void
     */
    public function getByMatterNo($matterNo){
        // Where句作成
        $where = [];
        $where[] = array('matter_no', '=', $matterNo);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 案件番号で見積情報を取得
     *
     * @return void
     */
    public function getByInMatterNo($matterNo){
        // Where句作成
        $where = [];
        // $where[] = array('matter_no', '=', $matterNo);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->whereIn('matter_no', $matterNo)
                ->get()
                ;

        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return int 見積ID
     */
    public function add(array $params) {
        try {
            $items = [];
            $items['quote_no'] = $params['quote_no'];
            $items['matter_no'] = $params['matter_no'];
            if (isset($params['special_flg'])) {
                $items['special_flg'] = $params['special_flg'];
            }
            if (isset($params['person_id'])) {
                $items['person_id'] = $params['person_id'];
            }
            if (isset($params['construction_period'])) {
                $items['construction_period'] = $params['construction_period'];
            }
            if (isset($params['construction_outline'])) {
                $items['construction_outline'] = $params['construction_outline'];
            }
            if (isset($params['quote_report_to'])) {
                $items['quote_report_to'] = $params['quote_report_to'];
            }
            if (isset($params['status'])) {
                $items['status'] = $params['status'];
            } else {
                $items['status'] = config('const.quoteStatus.val.incomplete');
            }
            $items['del_flg'] = config('const.flg.off');
            $items['created_user'] = Auth::user()->id;
            $items['created_at'] = Carbon::now();
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $items = [];
 
            $userId = Auth::user()->id;
            $now = Carbon::now();
            if(!isset($params['del_flg'])){
                $params['del_flg'] = config('const.flg.off');
            }
            if(!isset($params['created_user'])){
                $params['created_user'] = $userId;
            }
            if(!isset($params['created_at'])){
                $params['created_at'] = $now;
            }
            if(!isset($params['update_user'])){
                $params['update_user'] = $userId;
            }
            if(!isset($params['update_at'])){
                $params['update_at'] = $now;
            }
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    $items[$colName] = $params[$colName];
                }
            }

            // 登録
            $id = $this->insertGetId($items);

            return $id;
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

            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                            ->where('id', $items['id'])
                            ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     *
     * @param int $quoteId 見積ID
     * @return void
     */
    public function deleteById($quoteId) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $quoteId, config('const.logKbn.soft_delete'));

            // 更新内容
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                    ->where('id', $quoteId)
                    ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     *
     * @param int $quoteId 見積ID
     * @return void
     */
    public function physicalDeleteById($quoteId) {
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $quoteId, config('const.logKbn.delete'));

            // 更新
            $result = $this
                    ->where('id', $quoteId)
                    ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // /**
    //  * 見積ステータスを完了に更新
    //  *
    //  * @param int $id 見積ID
    //  * @return void
    //  */
    // public function updateToComplete($id) {
    //     $result = false;
    //     try {
    //         // ログテーブルへの書き込み
    //         $LogUtil = new LogUtil();
    //         $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

    //         $items = [];
    //         $items['status'] = config('const.quoteStatus.val.complete');
    //         $items['update_user'] = Auth::user()->id;
    //         $items['update_at'] = Carbon::now();

    //         // 更新
    //         $updateCnt = $this
    //                 ->where('id', $id)
    //                 ->update($items);

    //         $result = ($updateCnt === 1);
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    //     return $result;
    // }

    /**
     * 売上明細画面専用のデータ取得
     *
     * @param string $matterNo
     * @param int $matterId
     * @param int $quoteId
     * @param int $requestId
     * @return void
     */
    public function getSalesDetailData($matterNo, $matterId, $quoteId, $requestId) : Collection{

        $SystemUtil = new SystemUtil();

        // Where句作成
        $where = [];
        $where[] = array('t_quote.matter_no', '=', $matterNo);
        $where[] = array('t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number'));
        $where[] = array('t_quote.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));

        $whereSales[] = array('t_sales.matter_id', '=', $matterId);
        $whereSales[] = array('t_sales.quote_id', '=', $quoteId);
        $whereSales[] = array('t_sales.del_flg', '=', config('const.flg.off'));
        $whereSales[] = array('t_sales.sales_flg', '!=', config('const.salesDetailFlg.val.quote'));

        $whereSalesOverRide[] = array('t_sales.matter_id', '=', $matterId);
        $whereSalesOverRide[] = array('t_sales.quote_id', '=', $quoteId);
        $whereSalesOverRide[] = array('t_sales.request_id', '=', $requestId);
        $whereSalesOverRide[] = array('t_sales.del_flg', '=', config('const.flg.off'));
        $whereSalesOverRide[] = array('t_sales.sales_flg', '=', config('const.salesDetailFlg.val.quote'));

        $whereCostUnitPriceOverRide[] = array('t_sales.matter_id', '=', $matterId);
        $whereCostUnitPriceOverRide[] = array('t_sales.quote_id', '=', $quoteId);
        $whereCostUnitPriceOverRide[] = array('t_sales.del_flg', '=', config('const.flg.off'));
        $whereCostUnitPriceOverRide[] = array('t_sales.sales_flg', '=', config('const.salesDetailFlg.val.quote'));

        $whereSalesUse = [];
        $whereSalesUse[] = array('t_quote.matter_no', '=', $matterNo);
        $whereSalesUse[] = array('t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number'));
        $whereSalesUse[] = array('t_quote.del_flg', '=', config('const.flg.off'));
        $whereSalesUse[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        $whereSalesUse[] = array('t_quote_detail.sales_use_flg', '=', config('const.flg.on'));



        // 生きている売上(見積明細を上書き)　請求のステータスが「未処理」「売上確定」
        $tmpSalesOverRide = DB::table('t_sales')
                ->join('t_request', 't_sales.request_id', 't_request.id')
                ->leftjoin('m_department', 't_sales.status_dep', 'm_department.id')
                ->select([
                    't_sales.id AS sales_id',
                    't_sales.quote_detail_id',
                    't_sales.product_code',
                    't_sales.product_name',
                    't_sales.model',
                    't_sales.regular_price',
                    't_sales.cost_kbn',
                    't_sales.sales_kbn',
                    't_sales.cost_unit_price',
                    't_sales.cost_makeup_rate',
                    't_sales.sales_unit_price',
                    't_sales.sales_makeup_rate',
                    't_sales.status',

                    't_sales.update_cost_unit_price',
                    't_sales.bk_sales_unit_price',
                    't_sales.details_p_flg',
                    't_sales.details_c_flg',
                    't_sales.selling_p_flg',
                    't_sales.selling_c_flg',
                    'm_department.chief_staff_id',
                ])
                ->where($whereSalesOverRide)
                ->whereIn('t_request.status', [config('const.requestStatus.val.unprocessed'), config('const.requestStatus.val.complete')])
                ->get();
                    
        // 仕入単価の上書き
        $tmpSalesOverRideCostUnitPrice = DB::table('t_sales')
                ->join('t_request', 't_sales.request_id', 't_request.id')
                ->select([
                    't_sales.quote_detail_id',
                    't_sales.cost_unit_price',
                    't_sales.update_cost_unit_price',
                    DB::raw('MAX(t_sales.update_cost_unit_price_d) AS update_cost_unit_price_d')
                ])
                ->where($whereCostUnitPriceOverRide)
                ->whereNotNull('t_sales.update_cost_unit_price_d')
                ->whereIn('t_request.status', [config('const.requestStatus.val.request_complete'), config('const.requestStatus.val.close'), config('const.requestStatus.val.release')])
                ->groupBy('t_sales.quote_detail_id', 't_sales.cost_unit_price', 't_sales.update_cost_unit_price')
                ;

        // 発注番号リスト 取得
        $orderNoListQuery = DB::table('t_order')
                ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
                ->select([
                    't_order_detail.quote_detail_id AS quote_detail_id',
                    DB::raw('GROUP_CONCAT(t_order.order_no separator "／") AS order_no_list')
                ])
                ->where('t_order.matter_no', '=' ,$matterNo)
                ->where('t_order.status', '<>', config('const.orderStatus.val.not_ordering'))
                ->where('t_order.del_flg', '=', config('const.flg.off'))
                ->where('t_order_detail.del_flg', '=', config('const.flg.off'))
                ->groupBy('t_order_detail.quote_detail_id')
                ;

        // データ取得
        $quoteQuery = $this
                ->join('t_quote_detail', 't_quote_detail.quote_no', 't_quote.quote_no')
                ->leftjoin('m_product as m_product', 't_quote_detail.product_id', '=', 'm_product.id')
                ->leftJoin(
                    DB::raw('('. $orderNoListQuery->toSql(). ') AS t_order'),
                    't_quote_detail.id', '=', 't_order.quote_detail_id'
                )->mergeBindings($orderNoListQuery)
                ->leftJoin(
                    DB::raw('('. $tmpSalesOverRideCostUnitPrice->toSql(). ') AS t_sales_over_ride_cost_unit_price'),
                    't_quote_detail.id', '=', 't_sales_over_ride_cost_unit_price.quote_detail_id'
                )->mergeBindings($tmpSalesOverRideCostUnitPrice)
                ->select([
                    DB::raw('0 AS sales_id'),
                    't_order.order_no_list',
                    't_quote.id AS quote_id',
                    't_quote_detail.id AS quote_detail_id',
                    DB::raw(config('const.salesDetailFlg.val.quote').' AS sales_flg'),
                    't_quote_detail.quote_no',
                    't_quote_detail.product_id',
                    't_quote_detail.product_code',
                    't_quote_detail.product_name',
                    't_quote_detail.model',
                    't_quote_detail.maker_id',
                    't_quote_detail.maker_name',
                    't_quote_detail.supplier_id',
                    't_quote_detail.supplier_name',
                    't_quote_detail.quote_quantity',
                    't_quote_detail.min_quantity',
                    't_quote_detail.unit',
                    't_quote_detail.stock_unit',
                    't_quote_detail.regular_price',
                    't_quote_detail.cost_kbn',
                    't_quote_detail.sales_kbn',
                    DB::raw('
                        CASE 
                            WHEN t_sales_over_ride_cost_unit_price.quote_detail_id IS NOT NULL 
                                THEN t_sales_over_ride_cost_unit_price.cost_unit_price
                            ELSE 
                                t_quote_detail.cost_unit_price 
                        END AS cost_unit_price
                    '),
                    't_quote_detail.sales_unit_price',
                    't_quote_detail.cost_makeup_rate',
                    't_quote_detail.sales_makeup_rate',
                    't_quote_detail.cost_total',
                    't_quote_detail.sales_total',
                    't_quote_detail.gross_profit_rate',
                    't_quote_detail.profit_total',
                    DB::raw(config('const.salesStatus.val.not_applying').' AS status'),
                    't_sales_over_ride_cost_unit_price.update_cost_unit_price AS update_cost_unit_price',
                    DB::raw('DATE_FORMAT(t_sales_over_ride_cost_unit_price.update_cost_unit_price_d, "%Y/%m/%d") AS update_cost_unit_price_d'),
                    DB::raw('t_quote_detail.sales_unit_price AS bk_sales_unit_price'),
                    DB::raw(config('const.flg.off').' AS details_p_flg'),
                    DB::raw(config('const.flg.off').' AS details_c_flg'),
                    DB::raw(config('const.flg.off').' AS selling_p_flg'),
                    DB::raw(config('const.flg.off').' AS selling_c_flg'),
                    

                    't_quote_detail.construction_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.seq_no',
                    't_quote_detail.depth',
                    't_quote_detail.tree_path',
                    't_quote_detail.set_flg',
                    't_quote_detail.sales_use_flg',
                    DB::raw('0 AS chief_staff_id'),
                    'm_product.intangible_flg',
                    'm_product.draft_flg'
                    
                ])
                ->where($where);


        // データ取得　「値引き」「出来高」「仕入調整」のみ取得
        $salesQuery = DB::table('t_sales')
                ->leftjoin('m_department', 't_sales.status_dep', 'm_department.id')
                ->select([
                    't_sales.id AS sales_id',
                    DB::raw('"" AS order_no_list'),
                    't_sales.quote_id',
                    't_sales.quote_detail_id',
                    't_sales.sales_flg',
                    DB::raw('0 AS quote_no'),
                    't_sales.product_id',
                    't_sales.product_code',
                    't_sales.product_name',
                    't_sales.model',
                    't_sales.maker_id',
                    't_sales.maker_name',
                    't_sales.supplier_id',
                    't_sales.supplier_name',
                    't_sales.quote_quantity',
                    't_sales.min_quantity',
                    't_sales.unit',
                    't_sales.stock_unit',
                    't_sales.regular_price',
                    't_sales.cost_kbn',
                    't_sales.sales_kbn',
                    't_sales.cost_unit_price',
                    't_sales.sales_unit_price',
                    't_sales.cost_makeup_rate',
                    't_sales.sales_makeup_rate',
                    DB::raw('0 AS cost_total'),
                    DB::raw('0 AS sales_total'),
                    't_sales.gross_profit_rate',
                    't_sales.profit_total',
                    't_sales.status',

                    't_sales.update_cost_unit_price',
                    DB::raw('DATE_FORMAT(t_sales.update_cost_unit_price_d, "%Y/%m/%d") AS update_cost_unit_price_d'),
                    't_sales.bk_sales_unit_price',
                    't_sales.details_p_flg',
                    't_sales.details_c_flg',
                    't_sales.selling_p_flg',
                    't_sales.selling_c_flg',

                    
                    't_sales.construction_id',
                    't_sales.layer_flg',
                    't_sales.parent_quote_detail_id',
                    't_sales.seq_no',
                    't_sales.depth',
                    't_sales.tree_path',
                    DB::raw(config('const.flg.off').' AS set_flg'),
                    't_sales.sales_use_flg',
                    'm_department.chief_staff_id',
                    DB::raw(config('const.flg.on').' AS intangible_flg'),
                    DB::raw(config('const.flg.off').' AS draft_flg'),
                    
                ])
                ->where($whereSales);

            $data = $quoteQuery->union($salesQuery)
                ->orderBy('sales_id', 'asc')
                ->orderBy('depth', 'asc')
                ->orderBy('seq_no', 'asc')
                ->get()
                ;

        // 販売額利用フラグが立っている見積明細IDを取得
        $salesUseData = $this
                ->join('t_quote_detail', 't_quote_detail.quote_no', 't_quote.quote_no')
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                ])
                ->where($whereSalesUse)
                ->get();
            
        // 売上優先の上書き処理　無形品フラグのセット処理
        foreach($data as $key => $row){
            $salesData = $tmpSalesOverRide->firstWhere('quote_detail_id', '=', $row->quote_detail_id);
            if($salesData !== null){
                foreach($salesData as $colName => $val){
                    $data[$key]->$colName = $val;
                }  
            }
            
            // 無形品フラグをセットする
            if($row->intangible_flg === null){
                if($row->layer_flg === config('const.flg.on')){
                    $data[$key]->intangible_flg = config('const.flg.on');
                }else if(Common::nullToBlank($row->product_code) === ''){
                    $data[$key]->intangible_flg = config('const.flg.on');
                }else{
                    $data[$key]->intangible_flg = config('const.flg.off');
                }
            }
        }

        // 仕入単価と販売単価を0円として扱う行に単価無効フラグを立てる
        // 販売額利用フラグ配下にフラグを立てる
        // 仕入単価と販売単価を0にする
        foreach($data as $key => $row){
            if(Common::isFlgOn($row->layer_flg) && !Common::isFlgOn($row->sales_use_flg)){
                $data[$key]->sales_unit_price   = 0;
                $data[$key]->bk_sales_unit_price= 0;
                $data[$key]->cost_unit_price    = 0;
                $data[$key]->sales_makeup_rate  = 0.0;
                $data[$key]->cost_makeup_rate   = 0.0;
                $data[$key]->invalid_unit_price_flg  = config('const.flg.on');
            }else{
                $find =false;
                $treePathList = explode(config('const.treePathSeparator'), $row->tree_path);
                foreach($treePathList as $quoteDetailId){
                    // 販売額利用フラグが立っている見積明細IDを含むツリーパスか
                    $salesUseFlgData = $salesUseData->firstWhere('quote_detail_id', '=', $quoteDetailId);
                    if($salesUseFlgData !== null){
                        $find = true;
                        break;
                    }
                }

                if($find){
                    $data[$key]->sales_unit_price   = 0;
                    $data[$key]->bk_sales_unit_price= 0;
                    $data[$key]->cost_unit_price    = 0;
                    $data[$key]->sales_makeup_rate  = 0.0;
                    $data[$key]->cost_makeup_rate   = 0.0;
                    $data[$key]->invalid_unit_price_flg = config('const.flg.on');
                }else{
                    $data[$key]->invalid_unit_price_flg = config('const.flg.off');
                }
            }

            if(Common::nullorBlankToZero($row->sales_id) === 0){
                $data[$key]->details_p_flg  = config('const.flg.on');
                $data[$key]->details_c_flg  = config('const.flg.on');
                if($data[$key]->invalid_unit_price_flg === config('const.flg.on')){
                    $data[$key]->selling_p_flg  = config('const.flg.off');
                    $data[$key]->selling_c_flg  = config('const.flg.off');
                }else{
                    $data[$key]->selling_p_flg  = config('const.flg.on');
                    $data[$key]->selling_c_flg  = config('const.flg.on');
                }
            }

            $data[$key]->cost_makeup_rate   = $SystemUtil->calcRate($data[$key]->cost_unit_price, $data[$key]->regular_price); 
        }
                
        return $data;
    }

    /**
     * 商品を使用している案件番号、発注番号を取得する
     *
     * @param array $productIdList 商品ID
     * @return void
     */
    public function getMatterNoByProductIds($productIdList) {
        // 見積明細を商品IDで絞る
        $subQdQuery = DB::table('t_quote_detail')
                ->whereIn('product_id', $productIdList)
                ;

        $data = $this
            ->from('t_quote as q')
            ->join(DB::raw('('.$subQdQuery->toSql().') as qd'), function($join){
                $join->on('q.quote_no', 'qd.quote_no');
            })
            ->mergeBindings($subQdQuery)
            ->leftJoin('t_order_detail as od', 'od.quote_detail_id', 'qd.id')
            ->leftJoin('t_order as o', 'o.id', 'od.order_id')
            ->select([
                'qd.product_id',
                'o.id AS order_id',
                'o.order_no',
                'q.matter_no'
            ])
            ->groupBy('qd.product_id', 'o.id', 'o.order_no', 'q.matter_no')
            ->get()
            ;

        return $data;

    }

}