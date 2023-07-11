<?php

namespace App\Models;

use App\Exceptions\ValidationException;
use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;
use Illuminate\Support\Collection;
use Validator;

/**
 * 見積明細
 */
class QuoteDetail extends Model
{
    // テーブル名
    protected $table = 't_quote_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'quote_no',
        'quote_version',
        'construction_id',
        'layer_flg',
        'parent_quote_detail_id',
        'seq_no',
        'depth',
        'tree_path',
        'set_flg',
        'sales_use_flg',
        'product_id',
        'product_code',
        'product_name',
        'model',
        'maker_id',
        'maker_name',
        'supplier_id',
        'supplier_name',
        'quote_quantity',
        'min_quantity',
        'stock_quantity',
        'unit',
        'stock_unit',
        'order_lot_quantity',
        'quantity_per_case',
        'set_kbn',
        'class_big_id',
        'class_middle_id',
        'class_small_id',
        'tree_species',
        'grade',
        'length',
        'thickness',
        'width',
        'regular_price',
        'allocation_kbn',
        'cost_kbn',
        'sales_kbn',
        'cost_unit_price',
        'sales_unit_price',
        'cost_makeup_rate',
        'sales_makeup_rate',
        'cost_total',
        'sales_total',
        'gross_profit_rate',
        'profit_total',
        'memo',
        'row_print_flg',			
        'price_print_flg',
        'received_order_flg',
        'complete_flg',
        'copy_quote_detail_id',
        'copy_received_order_flg',
        'copy_complete_flg',
        'add_flg',
        'over_quote_detail_id',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',		
    ];

    /**
     * 登録
     *
     * @param int $quoteNo 見積番号
     * @param array $params
     * @return void
     */
    public function addList($quoteNo, $params) {
        try {
            $insertData = [];
            foreach ($params as $i => $row) {
                $items = $row;
                $items['quote_no'] = $quoteNo;
                $items['seq_no'] = $i + 1;
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
     * 見積明細データ存在確認
     *
     * @param array $param
     * @return void
     */
    public function isExist($quoteDetailId) {
        // Where句作成
        $where = [];
        $where[] = array('quote_detail_id', '=', $quoteDetailId);

        // データ取得
        $data = $this
                ->where($where)
                ->count()
                ;

        return $data > 0;
    }

    /**
     * テーブルのCOMMENTを取得
     *
     * @return Collection $data[物理名]=>論理名
     */
    public function getColumnComment()
    {
        return
            DB::table('INFORMATION_SCHEMA.COLUMNS')
                ->where('TABLE_NAME', $this->table)
                ->pluck('COLUMN_COMMENT', 'COLUMN_NAME');
    }

    /**
     * 見積番号に紐づく見積明細データ取得
     *
     * @param string $quoteNo 見積番号
     * @param string $quoteVersion 見積版
     * @return Collection
     */
    public function getDataList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));

        // 発注IDリスト 取得
        $orderIdlistQuery = DB::table('t_order')
                ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
                ->select([
                    't_order_detail.quote_detail_id as quote_detail_id',
                    DB::raw('GROUP_CONCAT(t_order.id separator ",") as order_id_list'),
                ])
                ->where('t_order.quote_no', '=', $quoteNo)
                ->where('t_order.status', '<>', config('const.orderStatus.val.not_ordering'))
                ->where('t_order.del_flg', '=', config('const.flg.off'))
                ->where('t_order_detail.del_flg', '=',  config('const.flg.off'))
                ->groupBy('t_order_detail.quote_detail_id')
                ;

        // データ取得
        $data = $this
                ->select([
                    't_quote_detail.*',
                    't_quote_detail.id AS quote_detail_id',
                    't_order.order_id_list',
                    'm_product.maker_id AS product_maker_id',
                    'm_product.intangible_flg AS intangible_flg',
                    'm_product.draft_flg AS draft_flg',
                    'm_product.auto_flg AS auto_flg',
                ])
                ->leftJoin(DB::raw('('. $orderIdlistQuery->toSql(). ') as t_order'), 't_quote_detail.id', 't_order.quote_detail_id')
                ->mergeBindings($orderIdlistQuery)
                ->leftJoin('m_product', 'm_product.id', 't_quote_detail.product_id')
                ->where($where)
                ->orderBy('t_quote_detail.quote_version', 'desc')
                ->orderBy('t_quote_detail.depth', 'asc')
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 見積番号・見積版に紐づく見積明細を全取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getPerVersion($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);

        // データ取得
        $data = $this
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.quote_no',
                    't_quote_detail.quote_version',
                    't_quote_detail.seq_no'
                ])
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 一式商品を取得する
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getSetProductList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.set_flg', '=', config('const.flg.on'));
        

        // データ取得
        $data = $this
                ->leftjoin('m_product as m_product', 't_quote_detail.product_id', '=', 'm_product.id')
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.construction_id',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.tree_path',
                    't_quote_detail.set_flg',
                    't_quote_detail.depth',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.layer_flg',
                    't_quote_detail.product_id',
                    't_quote_detail.product_code',
                    't_quote_detail.product_name',
                    't_quote_detail.model',
                    't_quote_detail.maker_id',
                    't_quote_detail.maker_name',
                    't_quote_detail.supplier_id',
                    't_quote_detail.supplier_name',
                    't_quote_detail.unit',
                    't_quote_detail.stock_quantity',
                    't_quote_detail.stock_unit',
                    't_quote_detail.min_quantity',
                    't_quote_detail.order_lot_quantity',
                    't_quote_detail.quote_quantity',
                    'm_product.intangible_flg',
                    'm_product.draft_flg',
                    't_quote_detail.regular_price',
                    't_quote_detail.cost_kbn',
                    't_quote_detail.sales_kbn',
                    't_quote_detail.cost_unit_price',
                    't_quote_detail.sales_unit_price',
                    't_quote_detail.cost_makeup_rate',
                    't_quote_detail.sales_makeup_rate',
                    't_quote_detail.cost_total',
                    't_quote_detail.sales_total',
                    't_quote_detail.gross_profit_rate',
                    't_quote_detail.profit_total',
                    't_quote_detail.memo',

                    't_quote_detail.quantity_per_case',
                    't_quote_detail.set_kbn',
                    't_quote_detail.class_big_id',
                    't_quote_detail.class_middle_id',
                    't_quote_detail.class_small_id',
                    't_quote_detail.tree_species',
                    't_quote_detail.grade',
                    't_quote_detail.length',
                    't_quote_detail.thickness',
                    't_quote_detail.width',
                ])
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 子部品を取得する
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getChildPartsList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.set_flg', '=', config('const.flg.on'));
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $setQuery = DB::table('t_quote_detail')
                ->where($where)
                ;

        // データ取得
        $data = $this
                ->join(DB::raw('('.$setQuery->toSql().') as set_quote'), function($join){
                    $join->on('t_quote_detail.parent_quote_detail_id', 'set_quote.id');
                })
                ->mergeBindings($setQuery)
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.product_id',
                    't_quote_detail.quote_quantity',
                    't_quote_detail.regular_price',
                    't_quote_detail.cost_unit_price',
                    't_quote_detail.sales_unit_price',
                    't_quote_detail.cost_total',
                    't_quote_detail.sales_total',
                ])
                ->where('t_quote_detail.quote_no', $quoteNo)
                ->where('t_quote_detail.quote_version', $quoteVersion)
                ->where('t_quote_detail.set_flg', config('const.flg.off'))
                ->where('t_quote_detail.del_flg', config('const.flg.off'))
                ->get();

        return $data;
    }


    /**
     * 登録
     *
     * @param array $params
     * @return 見積明細ID
     */
    public function add($params) {
        try {
            $items = [];
            $items['quote_no'] = $params['quote_no'];
            $items['quote_version'] = $params['quote_version'];
            $items['construction_id'] = $params['construction_id'];
            $items['layer_flg'] = $params['layer_flg'];

            if (!empty($params['parent_quote_detail_id'])) {
                $items['parent_quote_detail_id'] = $params['parent_quote_detail_id'];
            }

            $items['seq_no'] = $params['seq_no'];
            $items['depth'] = $params['depth'];
            $items['tree_path'] = $params['tree_path'];
            $items['sales_use_flg'] = $params['sales_use_flg'];

            if (!empty($params['product_id'])) {
                $items['product_id'] = $params['product_id'];
            }
            if (!empty($params['product_code'])) {
                $items['product_code'] = $params['product_code'];
            }
            if (!empty($params['product_name'])) {
                $items['product_name'] = $params['product_name'];
            }
            if (!empty($params['model'])) {
                $items['model'] = $params['model'];
            }
            if (!empty($params['maker_id'])) {
                $items['maker_id'] = $params['maker_id'];
            }
            if (!empty($params['maker_name'])) {
                $items['maker_name'] = $params['maker_name'];
            }
            if (!empty($params['supplier_id'])) {
                $items['supplier_id'] = $params['supplier_id'];
            }
            if (!empty($params['supplier_name'])) {
                $items['supplier_name'] = $params['supplier_name'];
            }
            if (!empty($params['quote_quantity'])) {
                $items['quote_quantity'] = $params['quote_quantity'];
            }
            if (!empty($params['min_quantity'])) {
                $items['min_quantity'] = $params['min_quantity'];
            }
            if (!empty($params['stock_quantity'])) {
                $items['stock_quantity'] = $params['stock_quantity'];
            }
            if (!empty($params['unit'])) {
                $items['unit'] = $params['unit'];
            }
            if (!empty($params['stock_unit'])) {
                $items['stock_unit'] = $params['stock_unit'];
            }
            if (!empty($params['regular_price'])) {
                $items['regular_price'] = $params['regular_price'];
            }
            if (!empty($params['allocation_kbn'])) {
                $items['allocation_kbn'] = $params['allocation_kbn'];
            }
            if (!empty($params['cost_kbn'])) {
                $items['cost_kbn'] = $params['cost_kbn'];
            }
            if (!empty($params['sales_kbn'])) {
                $items['sales_kbn'] = $params['sales_kbn'];
            }
            if (!empty($params['cost_unit_price'])) {
                $items['cost_unit_price'] = $params['cost_unit_price'];
            }
            if (!empty($params['sales_unit_price'])) {
                $items['sales_unit_price'] = $params['sales_unit_price'];
            }
            if (!empty($params['cost_makeup_rate'])) {
                $items['cost_makeup_rate'] = $params['cost_makeup_rate'];
            }
            if (!empty($params['sales_makeup_rate'])) {
                $items['sales_makeup_rate'] = $params['sales_makeup_rate'];
            }
            if (!empty($params['cost_total'])) {
                $items['cost_total'] = $params['cost_total'];
            }
            if (!empty($params['sales_total'])) {
                $items['sales_total'] = $params['sales_total'];
            }
            if (!empty($params['gross_profit_rate'])) {
                $items['gross_profit_rate'] = $params['gross_profit_rate'];
            }
            if (!empty($params['profit_total'])) {
                $items['profit_total'] = $params['profit_total'];
            }
            if (!empty($params['memo'])) {
                $items['memo'] = $params['memo'];
            }
            if (!empty($params['received_order_flg'])) {
                $items['received_order_flg'] = $params['received_order_flg'];
            }
            if (!empty($params['copy_quote_detail_id'])) {
                $items['copy_quote_detail_id'] = $params['copy_quote_detail_id'];
            }
            if (!empty($params['copy_received_order_flg'])) {
                $items['copy_received_order_flg'] = $params['copy_received_order_flg'];
            }
            if (!empty($params['add_flg'])) {
                $items['add_flg'] = $params['add_flg'];
            }
            // TODO
            if (isset($params['over_quote_detail_id'])) {
                $items['over_quote_detail_id'] = $params['over_quote_detail_id'];
            }

            $items['del_flg'] = config('const.flg.off');
            $items['created_user'] = Auth::user()->id;
            $items['created_at'] = Carbon::now();
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 登録
            $rtn = $this->insertGetId($items);

            return $rtn;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 登録(得意先標準単価登録機能付き)
     *
     * @param array $params
     * @param $customerId
     * @return 見積明細ID
     */
    public function addEx($params, $customerId = null) {
        try {
            $ProductCustomerPrice = new ProductCustomerPrice();

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
            
            // バリデーション
            $this->validation($items);

            // 登録
            $quoteDetailId = $this->insertGetId($items);

            // 登録完了し得意先IDがある場合、得意先標準単価を登録する
            if(!empty($quoteDetailId) && !empty($customerId)){
                $productCustomerPriceData = [
                    'customer_id'   => $customerId,
                    'product_id'    => 0,
                    'cost_sales_kbn'=> 0,
                    'price'         => 0,
                    'quote_detail_id' => $quoteDetailId,
                ];
                
                // 商品IDがあるか
                if(isset($items['product_id']) && !empty($items['product_id'])){
                    $productCustomerPriceData['cost_sales_kbn'] = config('const.costSalesKbn.cost');
                    $productCustomerPriceData['price']          = isset($items['cost_unit_price'])?$items['cost_unit_price']:0;
                    $productCustomerPriceData['product_id']     = $items['product_id'];
                    
                    // 仕入単価
                    if($items['cost_kbn'] === config('const.unitPriceKbn.cutomer_normal')){
                        $ProductCustomerPrice->add($productCustomerPriceData);
                    }


                    // 販売単価
                    $productCustomerPriceData['cost_sales_kbn'] = config('const.costSalesKbn.sales');
                    $productCustomerPriceData['price']          = isset($items['sales_unit_price'])?$items['sales_unit_price']:0;
                    
                    if($items['sales_kbn'] === config('const.unitPriceKbn.cutomer_normal')){
                        $ProductCustomerPrice->add($productCustomerPriceData);
                    }
                }
                
            }

            return $quoteDetailId;
        } catch (\Exception $e) {
            throw $e;
        }
    }
    
    /**
     * 更新
     *
     * @param int $quoteDetailId 見積明細ID
     * @param array $params 
     * @return void
     */
    public function updateById($quoteDetailId, $params) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $quoteDetailId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            // 更新内容
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
     * 更新(見積数)
     *
     * @param array $params 
     * @return void
     */
    public function updateQuantityById($params)
    {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $params['id']);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            $params['update_user'] = Auth::user()->id;
            $params['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($params)
                        ;

            $result = ($updateCnt == 1);
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 更新(得意先標準単価変更機能付き)
     *
     * @param int $quoteDetailId 見積明細ID
     * @param array $params 
     * @param array $customerId 
     * @return void
     */
    public function updateByIdEx($quoteDetailId, $params, $customerId = null) {
        $result = false;
        $ProductCustomerPrice = new ProductCustomerPrice();
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $quoteDetailId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            $beforeData = $this->where($where)->first()->toArray();
            
            // 更新内容
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

            // バリデーション
            $this->validation($items);

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

            $result = ($updateCnt == 1);

            if($result && !empty($customerId)){
                $productCustomerPriceData = [
                    'customer_id'   => $customerId,
                    'product_id'    => 0,
                    'cost_sales_kbn'=> 0,
                    'price'         => 0,
                    'quote_detail_id' => $quoteDetailId,
                ];
                if(!isset($items['product_id']) || $items['product_id'] !== $beforeData['product_id']){
                    $ProductCustomerPrice->deleteByWhere(['quote_detail_id'=>$quoteDetailId]);
                }
                
                if(isset($items['product_id']) && !empty($items['product_id'])){
                    $productCustomerPriceData['cost_sales_kbn'] = config('const.costSalesKbn.cost');
                    $productCustomerPriceData['price']          = $items['cost_unit_price'];
                    $productCustomerPriceData['product_id']     = $items['product_id'];
                    $deleteWhere = [
                        'quote_detail_id'=>$quoteDetailId, 
                        'cost_sales_kbn'=>config('const.costSalesKbn.cost')
                    ];
                    
                    // 仕入単価
                    if($items['cost_kbn'] === config('const.unitPriceKbn.cutomer_normal')){
                        // 得意先別標準を選択
                        if($items['cost_kbn'] !== $beforeData['cost_kbn'] || $items['product_id'] !== $beforeData['product_id']){
                            $ProductCustomerPrice->add($productCustomerPriceData);
                        }else if($items['cost_unit_price'] != $beforeData['cost_unit_price']){
                            $ProductCustomerPrice->deleteByWhere($deleteWhere);
                            $ProductCustomerPrice->add($productCustomerPriceData);
                        }
                    }else{
                        $ProductCustomerPrice->deleteByWhere($deleteWhere);
                    }


                    // 販売単価
                    $productCustomerPriceData['cost_sales_kbn'] = config('const.costSalesKbn.sales');
                    $productCustomerPriceData['price']          = $items['sales_unit_price'];
                    $deleteWhere['cost_sales_kbn']  = config('const.costSalesKbn.sales');
                    
                    if($items['sales_kbn'] === config('const.unitPriceKbn.cutomer_normal')){
                        // 得意先別標準を選択
                        if($items['sales_kbn'] !== $beforeData['sales_kbn'] || $items['product_id'] !== $beforeData['product_id']){
                            $ProductCustomerPrice->add($productCustomerPriceData);
                        }else if($items['sales_unit_price'] != $beforeData['sales_unit_price']){
                            $ProductCustomerPrice->deleteByWhere($deleteWhere);
                            $ProductCustomerPrice->add($productCustomerPriceData);
                        }
                    }else{
                        $ProductCustomerPrice->deleteByWhere($deleteWhere);
                    }
                }
                
            }

        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 数量超過分の見積明細の登録/更新/削除
     * @param $params 数量超過時に登録更新するデータ
     */
    public function saveOverQuantity($params){
        // 数量超過の見積明細を取得
        $overQuoteDetailData = $this
            ->where(['over_quote_detail_id' => $params['over_quote_detail_id']])
            ->first()
            ;
        if($overQuoteDetailData !== null){
            if($params['quote_quantity'] > 0){
                $this->updateByIdEx($overQuoteDetailData->id, $params);
            }else{
                $this->deleteList([$overQuoteDetailData->id]);
            }
        }else{
            if($params['quote_quantity'] > 0){
                $this->addEx($params);
            }
        }
    }

    /**
     * 物理削除
     *
     * @param array $quoteDetailIdList 見積明細IDの配列
     * @return void
     */
    public function deleteList($quoteDetailIdList) {
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($quoteDetailIdList as $id) {
                $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));
            }

            // 削除
            $this
                ->whereIn('id', $quoteDetailIdList)
                ->delete();

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

    /**
     * 物理削除
     *
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function physicalDeleteByQuoteNo($quoteNo) {
        try {
            // 条件
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            // 更新
            $result = $this
                    ->where($where)
                    ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 受注確定フラグ更新
     *
     * @param array $quoteDetailIdList
     * @param bool $isCancel
     * @return 更新件数
     */
    public function updateReceivedOrderFlg($quoteDetailIdList, $isCancel = false) {
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($quoteDetailIdList as $id) {
                $LogUtil->putById($this->table, $id, config('const.logKbn.update'));
            }

            $receivedOrderFlg = config('const.flg.on');
            if ($isCancel) {
                $receivedOrderFlg = config('const.flg.off');
            }

            // 更新内容
            $items = [];
            $items['received_order_flg'] = $receivedOrderFlg;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->whereIn('id', $quoteDetailIdList)
                        ->update($items)
                        ;

            return $updateCnt;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * コピー元受注選択フラグ更新
     *
     * @param array $quoteDetailIdList
     * @param bool $isCancel
     * @return 更新件数
     */
    public function updateCopyReceivedOrderFlg($quoteDetailIdList, $isCancel = false) {
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($quoteDetailIdList as $id) {
                $LogUtil->putById($this->table, $id, config('const.logKbn.update'));
            }

            $receivedOrderFlg = config('const.flg.on');
            if ($isCancel) {
                $receivedOrderFlg = config('const.flg.off');
            }

            // 更新内容
            $items = [];
            $items['copy_received_order_flg'] = $receivedOrderFlg;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->whereIn('id', $quoteDetailIdList)
                        ->update($items)
                        ;

            return $updateCnt;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 見積明細IDで取得
     *
     * @param array $quoteDetailIdList
     * @return 更新件数
     */
    public function getDataListByIds($quoteDetailIdList) {

        // データ取得
        $dataList = $this
                ->whereIn('t_quote_detail.id', $quoteDetailIdList)
                ->orderBy('t_quote_detail.depth', 'asc')
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ;

        return $dataList;
    }

    /**
     * 数量超過元明細IDで取得
     *
     * @param array $quoteDetailId
     * @return 更新件数
     */
    public function getDataListByOverIds($quoteDetailId) {

        // データ取得
        $data = $this
                ->where('t_quote_detail.over_quote_detail_id', '=', $quoteDetailId)
                ->first()
                ;

        return $data;
    }

    // /**
    //  * 一覧用データ取得[受発注一覧]
    //  *
    //  * @param [type] $params
    //  * @return void
    //  */
    // public function getOrderList($params){

    //     // 見積明細（メインテーブル）
    //     $mainSubWhere = [];
    //     $mainSubWhere[] = array('del_flg', '=', config('const.flg.off'));
    //     $mainSubWhere[] = array('quote_version', '=', config('const.quoteCompVersion.number'));
    //     $mainSubWhere[] = array('layer_flg', '=', config('const.flg.off'));
    //     $mainSubWhere[] = array('over_quote_detail_id', '=', 0);
    //     // 対象となる見積番号を絞る
    //     $whereInQuoteNo = null;
    //     if (!empty($params['quote_no'])) {
    //         // 見積番号
    //         $whereInQuoteNo[] = $params['quote_no'];
    //     }else if(!empty($params['order_register_date_from']) || !empty($params['order_register_date_to'])){
    //         // 受注登録日(FROM-TO)
    //         $quoteNos = DB::table('t_quote_detail AS qd')
    //             ->when(!empty($params['order_register_date_from']), function ($query) use ($params) {
    //                 return $query->where('qd.created_at', '>=', $params['order_register_date_from']);
    //             })
    //             ->when(!empty($params['order_register_date_to']), function ($query) use ($params) {
    //                 return $query->where('qd.created_at', '<', date('Y/m/d', strtotime($params['order_register_date_to']. ' +1 day')));
    //             })
    //             ->distinct()
    //             ->pluck('qd.quote_no');
    //         $whereInQuoteNo = $quoteNos->toArray();
    //     }
    //     $quoteDetailQuery = DB::table('t_quote_detail')
    //             ->where($mainSubWhere)
    //             ->when($whereInQuoteNo, function ($query) use ($whereInQuoteNo) {
    //                 return $query->whereIn('quote_no', $whereInQuoteNo);
    //             })
    //             ->select([
    //                 'id'
    //                 , 'quote_no'
    //                 , 'product_id'
    //                 , 'product_name'
    //                 , 'maker_id'
    //                 , 'supplier_id'
    //             ]);
        
    //     // 案件
    //     $matterWhere = [];
    //     // 得意先ID
    //     if (!empty($params['customer_id'])) {
    //         $matterWhere[] = array('customer_id', '=', $params['customer_id']);
    //     }
    //     // 案件番号
    //     if (!empty($params['matter_no'])) {
    //         $matterWhere[] = array('matter_no', '=', $params['matter_no']);
    //     }
    //     // 部門ID
    //     if (!empty($params['department_id'])) {
    //         $matterWhere[] = array('department_id', '=', $params['department_id']);
    //     }
    //     // 営業担当者（担当者ID）
    //     if (!empty($params['sales_staff_id'])) {
    //         $matterWhere[] = array('staff_id', '=', $params['sales_staff_id']);
    //     }
    //     $matterQuery = DB::table('t_matter')
    //             ->where($matterWhere)
    //             ->select(
    //                 'id',
    //                 'matter_no',
    //                 'matter_name',
    //                 'department_id',
    //                 'staff_id'
    //             );

    //     // 発注明細
    //     $oDetailQuery = DB::table('t_order_detail')
    //             ->where('del_flg', '=', config('const.flg.off'))
    //             ->distinct()
    //             ->select([
    //                 'quote_detail_id'
    //             ]);

    //     // 引当
    //     $reserveQuery = DB::table('t_reserve')
    //             ->where('del_flg', '=', config('const.flg.off'))
    //             ->whereIn('stock_flg', [ config('const.stockFlg.val.stock'), config('const.stockFlg.val.keep') ])
    //             ->distinct()
    //             ->select([
    //                 'quote_detail_id'
    //             ]);

    //     $data = $this
    //                 // ->from('t_quote_detail AS q_detail')
    //                 ->from(DB::raw('('.$quoteDetailQuery->toSql().') as q_detail'))
    //                 ->mergeBindings($quoteDetailQuery)
    //                 ->join('t_quote AS quote', 'q_detail.quote_no', 'quote.quote_no')
    //                 ->join(DB::raw('('.$matterQuery->toSql().') as matter'), function($join){
    //                     $join->on('quote.matter_no', 'matter.matter_no');
    //                 })
    //                 ->mergeBindings($matterQuery)
    //                 ->leftJoin(DB::raw('('.$oDetailQuery->toSql().') as o_detail'), function($join){
    //                     $join->on('q_detail.id', 'o_detail.quote_detail_id');
    //                 })
    //                 ->mergeBindings($oDetailQuery)
    //                 ->leftJoin(DB::raw('('.$reserveQuery->toSql().') as reserve'), function($join){
    //                     $join->on('q_detail.id', 'reserve.quote_detail_id');
    //                 })
    //                 ->mergeBindings($reserveQuery)
    //                 ->leftJoin('m_staff as staff', 'matter.staff_id', 'staff.id')
    //                 ->leftJoin('m_department as department', 'matter.department_id', 'department.id')
    //                 ->select([
    //                     'q_detail.id',
    //                     'q_detail.quote_no',
    //                     'q_detail.product_id',
    //                     'q_detail.product_name',
    //                     'q_detail.maker_id',
    //                     'q_detail.supplier_id',
    //                     'quote.id AS quote_id',
    //                     'quote.own_stock_flg',
    //                     'staff.staff_name',
    //                     'department.department_name',
    //                     'matter.id AS matter_id',
    //                     'matter.matter_no',
    //                     'matter.matter_name',
    //                     DB::raw('CASE WHEN o_detail.quote_detail_id IS NULL THEN '. config('const.flg.off'). ' ELSE '. config('const.flg.on'). ' END AS referenced_order'),
    //                     DB::raw('CASE WHEN reserve.quote_detail_id IS NULL THEN '. config('const.flg.off'). ' ELSE '. config('const.flg.on'). ' END AS referenced_reserve'),
    //                 ])
    //                 ->get();
    //     return $data;
    // }

    /**
     * CSV用データ
     *
     * @param $quoteDetailId 見積明細ID
     * @return void
     */
    public function getCsvData($params) 
    {
        // Where句作成
        $where = [];
        $where[] = array('quote_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_detail.quote_no', '=', $params['quote_no']);
        $where[] = array('quote_detail.quote_version', '=', $params['quote_version']);
        // $joinWhere['layer'][] = array('quote_layer.del_flg', '=', config('const.flg.off'));
        // $joinWhere['layer'][] = array('quote_layer.quote_no', '=', $params['quote_no']);
        // $joinWhere['version'][] = array('quote_version.del_flg', '=', config('const.flg.off'));
        $joinWhere['version'][] = array('quote_version.quote_version', '=', $params['quote_version']);
        $joinWhere['version'][] = array('quote_version.quote_no', '=', $params['quote_no']);
        $joinWhere['general'][] = array('general.category_code', '=', config('const.general.paycon'));

        $data = $this
                ->select('quote_detail.quote_no as quote_no'
                        , 'quote.matter_no'
                        , 'quote.special_flg'
                        , 'person.name as person_name'
                        , 'quote_detail.quote_version'
                        , 'quote_version.caption'
                        , 'department.department_name'
                        , 'staff.staff_name'
                        , 'quote_version.quote_create_date'
                        , 'quote_version.quote_limit_date'
                        , 'quote_version.quote_enabled_limit_date'
                        , 'general.value_text_1'
                        , 'quote_version.cost_total as version_cost_total'
                        , 'quote_version.sales_total as version_sales_total'
                        , 'quote_version.profit_total as version_profit_total' 
                        , 'quote_version.sales_support_comment'
                        , 'quote_version.approval_comment'
                        , 'quote_version.customer_comment'
                        , 'construction.construction_name'
                        // , 'layer_parent_3.layer_name as construction'
                        // , 'layer_parent_2.layer_name as class_big'
                        // , 'layer_parent_1.layer_name as class_middle'
                        // , 'quote_layer.layer_name as usage'
                        , 'parent_detail.product_name AS layer_name'  // 階層名
                        , 'quote_detail.product_name'
                        , 'quote_detail.model'
                        , 'quote_detail.maker_name'
                        , 'quote_detail.supplier_name'
                        , 'quote_detail.quote_quantity'
                        , 'quote_detail.unit'
                        , 'quote_detail.stock_quantity'
                        , 'quote_detail.stock_unit'
                        , 'quote_detail.regular_price'
                        , 'quote_detail.min_quantity'
                        // , 'quote_detail.allocation_kbn'
                        , 'quote_detail.cost_kbn'
                        , 'quote_detail.sales_kbn'
                        , 'quote_detail.cost_unit_price'
                        , 'quote_detail.sales_unit_price'
                        , 'quote_detail.cost_makeup_rate'
                        , 'quote_detail.sales_makeup_rate'
                        , 'quote_detail.cost_total as detail_cost_total'
                        , 'quote_detail.sales_total as detail_sales_total'
                        , 'quote_detail.gross_profit_rate as detail_gross_profit_rate'
                        , 'quote_detail.profit_total as detail_profit_total'
                        , 'quote_detail.memo'
                        )
                ->from('t_quote_detail as quote_detail')
                // ->leftJoin('t_quote_layer as quote_layer', function ($join) use ($joinWhere) {
                //     $join->on('quote_detail.quote_layer_id', '=', 'quote_layer.id')
                //          ->where($joinWhere['layer'])
                //          ;
                // })
                ->leftJoin('t_quote_detail as parent_detail', 'quote_detail.parent_quote_detail_id', '=', 'parent_detail.id')
                ->leftJoin('t_quote as quote', 'quote_detail.quote_no', '=', 'quote.quote_no')
                ->leftJoin('t_quote_version as quote_version', function ($join) use ($joinWhere) {
                    $join->on('quote_detail.quote_version', '=', 'quote_version.quote_version')
                         ->where($joinWhere['version'])
                         ;
                })
                // 支払い方法
                ->leftJoin('m_general as general', function ($join) use ($joinWhere) {
                    $join->on('quote_version.payment_condition', '=', 'general.value_code')
                         ->where($joinWhere['general'])
                         ;
                })
                ->leftJoin('m_department as department', 'quote_version.department_id', '=', 'department.id')
                ->leftJoin('m_person as person', 'quote.person_id', '=', 'person.id')
                ->leftJoin('m_staff as staff', 'quote_version.staff_id', '=', 'staff.id')
                ->leftJoin('m_construction as construction', 'quote_detail.construction_id', '=', 'construction.id')
                // ->leftJoin('t_quote_layer as layer_parent_1', 'quote_layer.parent_quote_layer_id', '=', 'layer_parent_1.id')
                // ->leftJoin('t_quote_layer as layer_parent_2', 'layer_parent_1.parent_quote_layer_id', '=', 'layer_parent_2.id')
                // ->leftJoin('t_quote_layer as layer_parent_3', 'layer_parent_2.parent_quote_layer_id', '=', 'layer_parent_3.id')
                ->where($where)
                ->orderBy('quote_detail.construction_id', 'asc')
                ->orderBy('quote_detail.parent_quote_detail_id', 'asc')
                ->orderBy('quote_detail.seq_no', 'asc')
                ->get()
                ;


        return $data;
    }

    /**
     * 見積番号に紐づく見積もり明細データ取得
     *
     * @param string $quoteNo
     * @param string $quoteId
     * @param string $quoteVersion
     * @return void
     */
    public function getDataListByQuoteVersion($quoteNo, $quoteId, $quoteVersion) : Collection{
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));

        // 発注IDリスト 取得
        $orderIdlist = DB::table('t_order')
                ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
                ->select([
                    't_order_detail.quote_detail_id as quote_detail_id',
                ])
                ->selectRaw(
                    'GROUP_CONCAT(t_order.id separator ",") as order_id_list'
                )
                ->whereRaw('t_order.quote_no = '.$quoteNo)
                ->whereRaw('t_order.status != '. config('const.orderStatus.val.not_ordering'))
                ->whereRaw('t_order.del_flg = '. config('const.flg.off'))
                ->whereRaw('t_order_detail.del_flg = '. config('const.flg.off'))
                ->groupBy('t_order_detail.quote_detail_id')
                ->toSql()
                ;
                
        // 納品データ 取得
        $deliveryIdlist = $this
                ->join('t_delivery', 't_quote_detail.id', 't_delivery.quote_detail_id')
                ->select([
                    't_quote_detail.id as quote_detail_id',
                ])
                ->selectRaw(
                    'GROUP_CONCAT(t_delivery.id separator ",") as delivery_id_list'
                )
                ->whereRaw('t_quote_detail.quote_no = '.$quoteNo)
                ->whereRaw('t_quote_detail.quote_version = '.$quoteVersion)
                ->whereRaw('t_quote_detail.del_flg = '. config('const.flg.off'))
                ->whereRaw('t_delivery.delivery_quantity >= '. 1)
                ->whereRaw('t_delivery.del_flg = '. config('const.flg.off'))
                ->groupBy('t_quote_detail.id')
                ->toSql()
                ;

        // 過去の発注数 取得(未処理/差戻 除外)
        // $orderDetailData = DB::table('t_order')
        //         ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
        //         ->select([
        //             't_order_detail.quote_detail_id as quote_detail_id',
        //         ])
        //         ->selectRaw(
        //             'SUM(t_order_detail.order_quantity) 
        //             AS sum_order_quantity'
        //         )
        //         ->selectRaw(
        //             'GROUP_CONCAT(t_order_detail.id separator ",") as order_detail_id_list'
        //         )
        //         ->whereRaw('t_order.quote_no = '.$quoteNo)
        //         ->whereRaw('t_order.status != '. config('const.orderStatus.val.not_ordering'))
        //         ->whereRaw('t_order.status != '. config('const.orderStatus.val.sendback'))
        //         ->whereRaw('t_order.del_flg = '. config('const.flg.off'))
        //         ->whereRaw('t_order_detail.del_flg = '. config('const.flg.off'))
        //         ->groupBy('t_order_detail.quote_detail_id')
        //         ->toSql()
        //         ;

        // 一時保存データ　MEMO：見積明細に紐づくstatus=0の発注明細は0～1件。SQL高速化のためにGroupByを使用
        $tmpSaveOrderDetail = DB::table('t_order')
                ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
                ->selectRaw(
                    't_order_detail.quote_detail_id
                    , MAX(t_order_detail.id) AS id
                    , MAX(t_order_detail.order_quantity) AS order_quantity
                    , MAX(t_order_detail.stock_quantity) AS stock_quantity
                    , MAX(t_order_detail.memo) AS memo'
                )
                ->whereRaw('t_order.quote_no = '.$quoteNo)
                ->whereRaw('t_order.status = '. config('const.orderStatus.val.not_ordering'))
                ->whereRaw('t_order.del_flg = '. config('const.flg.off'))
                ->whereRaw('t_order_detail.del_flg = '. config('const.flg.off'))
                ->groupBy('t_order_detail.quote_detail_id')
                ->toSql()
                ;

        // 一式のデータ
        $setDataQuery = DB::table('t_quote_detail')
            ->select([
                't_quote_detail.id AS quote_detail_id',
                't_quote_detail.parent_quote_detail_id',
            ])
            ->whereRaw('t_quote_detail.quote_no = '.$quoteNo)
            ->whereRaw('t_quote_detail.set_flg = '.config('const.flg.on'))
            ->whereRaw('t_quote_detail.del_flg = '.config('const.flg.off'))
            ->whereRaw('t_quote_detail.quote_version = '.$quoteVersion)
            ->toSql();

 
        $Construction = new Construction();
        $constructionData = $Construction->getAddFlgData();
        // 発注数超過の見積明細
        $overQuantityQuery = $this
            ->select([
                't_quote_detail.id as t_quote_detail_id',
            ])
            ->whereRaw('t_quote_detail.construction_id = '.$constructionData->id)
            //->whereRaw('t_quote_detail.add_flg = '. config('const.flg.off'))
            ->whereRaw('t_quote_detail.del_flg = '. config('const.flg.off'))
            ->whereRaw('t_quote_detail.quote_no = '.$quoteNo)
            ->whereRaw('t_quote_detail.quote_version = '.$quoteVersion)
            ->toSql();

        

        // データ取得
        $data = $this
                ->leftjoin(DB::raw('('.$orderIdlist.') as t_order'), function($join){
                    $join->on('t_quote_detail.id', 't_order.quote_detail_id');
                })
                ->leftjoin(DB::raw('('.$deliveryIdlist.') as t_delivery'), function($join){
                    $join->on('t_quote_detail.id', 't_delivery.quote_detail_id');
                })
                // ->leftjoin(DB::raw('('.$orderDetailData.') as t_order_detail'), function($join){
                //     $join->on('t_quote_detail.id', 't_order_detail.quote_detail_id');
                // })
                ->leftjoin(DB::raw('('.$tmpSaveOrderDetail.') as save_order_detail'), function($join){
                    $join->on('t_quote_detail.id', 'save_order_detail.quote_detail_id');
                })
                ->leftjoin(DB::raw('('.$setDataQuery.') as t_set_quote_detail'), function($join){
                    $join->on('t_quote_detail.parent_quote_detail_id', 't_set_quote_detail.quote_detail_id');
                })
                ->leftjoin('m_product as m_product', 't_quote_detail.product_id', '=', 'm_product.id')
                ->leftjoin('t_matter_detail', function ($join) use ($quoteId) {
                    $join->on('t_quote_detail.id', '=', 't_matter_detail.quote_detail_id')
                        ->where([
                            ['t_matter_detail.type', '=', config('const.matterTaskType.val.order_timing')]
                        ]);
                })
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.quote_no',
                    't_quote_detail.quote_version',
                    't_quote_detail.seq_no',
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
                    't_quote_detail.order_lot_quantity',
                    't_quote_detail.quantity_per_case',     // 仮登録用
                    't_quote_detail.set_kbn',               // 仮登録用
                    // 't_quote_detail.class_big_id',          // 仮登録用
                    DB::raw('
                      CASE WHEN m_product.id IS NULL
                        THEN t_quote_detail.class_big_id 
                        ELSE m_product.class_big_id 
                      END AS class_big_id
                    '),                                     // 発注から更新があるためマスタ優先
                    // 't_quote_detail.class_middle_id',       // 仮登録用
                    DB::raw('
                      CASE WHEN m_product.id IS NULL
                        THEN t_quote_detail.class_middle_id 
                        ELSE m_product.class_middle_id 
                      END AS class_middle_id
                    '),                                     // 発注から更新があるためマスタ優先
                    't_quote_detail.class_small_id',        // 仮登録用
                    't_quote_detail.tree_species',          // 仮登録用
                    't_quote_detail.grade',                 // 仮登録用
                    't_quote_detail.length',                // 仮登録用
                    't_quote_detail.thickness',             // 仮登録用
                    't_quote_detail.width',                 // 仮登録用
                    //'t_quote_detail.stock_quantity',
                    't_quote_detail.unit',
                    // 't_quote_detail.stock_unit',
                    DB::raw('
                      CASE WHEN m_product.id IS NULL
                        THEN t_quote_detail.stock_unit 
                        ELSE m_product.stock_unit 
                      END AS stock_unit
                    '),                                     // 発注から更新があるためマスタ優先
                    't_quote_detail.regular_price',
                    't_quote_detail.allocation_kbn',
                    't_quote_detail.cost_kbn',
                    't_quote_detail.sales_kbn',
                    't_quote_detail.cost_unit_price',
                    't_quote_detail.sales_unit_price',
                    't_quote_detail.cost_makeup_rate',
                    't_quote_detail.sales_makeup_rate',
                    't_quote_detail.cost_total',
                    't_quote_detail.sales_total',
                    't_quote_detail.gross_profit_rate',
                    't_quote_detail.profit_total',
                    //'t_quote_detail.memo',
                    't_quote_detail.received_order_flg',
                    't_quote_detail.copy_quote_detail_id',
                    't_quote_detail.copy_received_order_flg',
                    
                    't_quote_detail.construction_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.seq_no',
                    't_quote_detail.depth',
                    't_quote_detail.tree_path',
                    't_quote_detail.set_flg',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.add_flg',

                    //'m_product.order_lot_quantity',
                    'm_product.intangible_flg',
                    'm_product.maker_id AS product_maker_id',
                    'm_product.draft_flg',
                    'm_product.auto_flg',
                    
                    't_order.order_id_list as order_id_list',

                    //'t_order_detail.sum_order_quantity as sum_order_quantity',

                    't_delivery.delivery_id_list as delivery_id_list',

                    'save_order_detail.id as save_order_detail_id',
                    'save_order_detail.order_quantity as order_quantity',
                    'save_order_detail.stock_quantity as stock_quantity',
                    'save_order_detail.memo as memo',
                    't_set_quote_detail.quote_detail_id AS set_quote_detail_id',
                    DB::raw('DATE_FORMAT(t_matter_detail.start_date, "%Y/%m/%d") AS matter_detail_start_date'),
                ])
                ->where($where)
                ->whereRaw('t_quote_detail.id NOT IN ('. $overQuantityQuery. ')')
                ->orderBy('t_quote_detail.depth', 'asc')
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ;

        foreach($data as $key => $row){
            if(Common::nullorBlankToZero($row->set_quote_detail_id) === 0){
                $data[$key]->child_parts_flg = config('const.flg.off');
            }else{
                $data[$key]->child_parts_flg = config('const.flg.on');
            }

        }

        return $data;
    }

    /**
     * 階層のトップだけを返す
     * 
     * @param array $addProperty 任意のプロパティ追加
     */
    public static function getTopTreeData($addProperty = array()){
        $result = [];
        $result[] = array_merge(config('const.topTreeData'), $addProperty);
        return $result;
    }
    
    /**
     * 見積もり階層データを取得
     *
     * @param string $quoteNo       見積もり番号(検索条件)
     * @param int $quoteVersion     見積もり版(検索条件)
     * @param int $layerFlg         階層フラグ(検索条件)  const.flg
     * @param int $exclusionOverQuantityFlg  追加部材配下 かつ 追加部材フラグが立っていないものを除外する(検索条件)
     * @param array  $idList        見積もり明細ID(検索条件)
     * @param array  $addTopProperty    トップ階層に追加したいプロパティ
     * @param int $exclusionAddLayerFlg  追加部材配下を除外する(検索条件)
     * @return void
     */
    public function getTreeData($quoteNo = null, $quoteVersion = null, $layerFlg = null, $exclusionOverQuantityFlg = null, $idList = array(), $addTopProperty = array(), $exclusionAddLayerFlg = null) {
        // Where句作成
        $where = [];
        $overQuantityQuery = '';
        $addLayerQuery = '';
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        if($quoteNo !== null){
            $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        }
        if($quoteVersion !== null){
            $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        }
        if($layerFlg !== null){
            $where[] = array('t_quote_detail.layer_flg', '=', $layerFlg);
        }

        $Construction = new Construction();
        $constructionData = $Construction->getAddFlgData();

        if($exclusionAddLayerFlg === config('const.flg.on')){
            // 追加部材配下
            $subQuery = $this
            ->select([
                't_quote_detail.id as t_quote_detail_id',
            ])
            ->whereRaw('t_quote_detail.construction_id = '.$constructionData->id)
            ->whereRaw('t_quote_detail.del_flg = '. config('const.flg.off'));
            if($quoteNo !== null){
                $subQuery->whereRaw('t_quote_detail.quote_no = '.$quoteNo);
            }
            if($quoteVersion !== null){
                $subQuery->whereRaw('t_quote_detail.quote_version = '.$quoteVersion);
            }

            $addLayerQuery = $subQuery->toSql();
        }

        if($exclusionOverQuantityFlg === config('const.flg.on')){
            // 発注数超過の見積明細
            $subQuery = $this
            ->select([
                't_quote_detail.id as t_quote_detail_id',
            ])
            ->whereRaw('t_quote_detail.construction_id = '.$constructionData->id)
            ->whereRaw('t_quote_detail.add_flg = '. config('const.flg.off'))
            ->whereRaw('t_quote_detail.del_flg = '. config('const.flg.off'));
            if($quoteNo !== null){
                $subQuery->whereRaw('t_quote_detail.quote_no = '.$quoteNo);
            }
            if($quoteVersion !== null){
                $subQuery->whereRaw('t_quote_detail.quote_version = '.$quoteVersion);
            }
            if($layerFlg !== null){
                $subQuery->whereRaw('t_quote_detail.layer_flg = '.$layerFlg);
            }

            $overQuantityQuery = $subQuery->toSql();
        }
        
        // データ取得
        $dataList = $this
                ->select([
                    't_quote_detail.id',
                    't_quote_detail.construction_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.seq_no',
                    't_quote_detail.depth',
                    't_quote_detail.tree_path',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.product_name',
                    't_quote_detail.add_flg',
                    't_quote_detail.set_flg',
                    DB::raw(config('const.flg.off').' as top_flg')
                ])
                ->where($where)
                ->when($exclusionAddLayerFlg === config('const.flg.on'), function($query) use($addLayerQuery) {
                    return $query->whereRaw('t_quote_detail.id NOT IN ('. $addLayerQuery. ')');
                })
                ->when($exclusionOverQuantityFlg === config('const.flg.on'), function($query) use($overQuantityQuery) {
                    return $query->whereRaw('t_quote_detail.id NOT IN ('. $overQuantityQuery. ')');
                })
                ->when(count($idList)>=1, function($query) use($idList) {
                    return $query->whereIn('t_quote_detail.id', $idList);
                })
                ->orderBy('t_quote_detail.depth', 'asc')
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ->toArray()
                ;

        $result = [];
        $treeData = [];
        foreach ($dataList as $cnt => $record){
            if($record['depth'] === config('const.quoteConstructionInfo.depth')){
                // 工事区分
                $record['header'] = $record['product_name'];
                $record['filter_tree_path'] = strval($cnt);
                $record['items'] = [];
                $treeData[] = $record;
            }else{
                // 工事区分以外
                $depth = explode(config('const.treePathSeparator'), $record['tree_path']);
                $current = null;
                foreach($depth as $key => $treeId){
                    if($key === 0){
                        $current = &$treeData[array_search($treeId, array_column($treeData, 'id'))];
                    }else{
                        $current = &$current['items'][array_search($treeId, array_column($current['items'], 'id'))]; 
                    }
                }
                $record['header'] = $record['product_name'];
                $record['filter_tree_path'] = $current['filter_tree_path'].config('const.treePathSeparator').count($current['items']);
                $record['items'] = [];
                $current['items'][] = $record;
                unset($current);
            }
        }

        $result = self::getTopTreeData($addTopProperty);
        $result[0]['items'] = $treeData;
        
        return $result;
    }

    /**
     * 積算完了へ更新
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @param array $idList 工事区分IDの配列
     * @return void
     */
    public function updateCompEstimation($quoteNo, $quoteVersion, $isEstimationRelease, $idList) {
        try {
            // 条件
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);
            $where[] = array('quote_version', '=', $quoteVersion);
            $where[] = array('depth', '=', config('const.quoteConstructionInfo.depth'));
            
            if ($isEstimationRelease) {
                $where[] = array('complete_flg', '=', config('const.flg.on'));
            }else{
                $where[] = array('complete_flg', '=', config('const.flg.off'));
            }

            foreach ($idList as $constructionId) {
                // 工事区分を1つずつ処理
                $useWhere = $where;
                $useWhere[] = array('construction_id', '=', $constructionId);

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                $LogUtil->putByWhere($this->table, $useWhere, config('const.logKbn.update'));
                
                // 更新内容
                $items = [];
                $items['complete_flg'] = $isEstimationRelease ? config('const.flg.off') : config('const.flg.on');
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
     * 積算完了明細を工事区分別にカウント取得
     *
     * @param array $quoteNos 見積番号の配列
     * @return 
     */
    public function getCountComplete($quoteNos) {
        // データ取得
        $data = $this
                ->select([
                    'construction_id',
                    DB::raw('COUNT(id) as cnt'),
                ])
                ->where('del_flg', config('const.flg.off'))
                ->where('complete_flg', config('const.flg.on'))
                ->whereIn('quote_no', $quoteNos)
                ->groupBy('construction_id')
                ->get()
                ;
        
        return $data;
    }

    /**
     * 見積書データ取得（指定印刷）
     *
     * @param [type] $quoteNo
     * @param [type] $quoteVersion
     * @param boolean $targetPrint
     * @return void
     */
    public function getQuoteReportByKey($quoteNo, $quoteVersion, $isTargetPrint=false){
        $where = [];
        $where[] = array('qd.quote_no', '=', $quoteNo);
        $where[] = array('qd.quote_version', '=', $quoteVersion);

        $IDs = [];
        if ($isTargetPrint) {
            $tmpIDs = DB::table('t_quote_detail AS qd')
                        ->join('t_quote_report_temp AS qrt', function($query){
                            $query
                                ->on('qd.quote_no', 'qrt.quote_no')
                                ->on('qd.quote_version', 'qrt.quote_version')
                                ->on('qd.id', 'qrt.quote_detail_id')
                                ->where('qrt.created_user',  Auth::user()->id);
                        })
                        ->where($where)
                        ->pluck('qd.tree_path', 'qd.id');
            foreach ($tmpIDs as $key => $treePath) {
                if (!in_array($key, $IDs)) {
                    $IDs[] = $key;
                }
                foreach (explode('_', $treePath) as $qDetailId) {
                    // 工事区分階層行のtree_pathではない && 配列に存在しない
                    if ($qDetailId != "0" && !in_array($qDetailId, $IDs)) {
                        $IDs[] = $qDetailId;
                    }
                }
            }
        }

        $data = $this
                    ->from('t_quote_detail AS qd')
                    ->leftJoin('m_supplier AS s', function($join){
                        $join
                            ->on('qd.maker_id', 's.id')
                            ->where('s.print_exclusion_flg', config('const.flg.off'));
                    })
                    ->when($isTargetPrint, function($query) use($IDs){
                        $query->whereIn('qd.id', $IDs);
                    })
                    ->when(!$isTargetPrint, function($query) use($where){
                        $query->where($where);
                    })
                    ->select([
                        'qd.id',
                        'qd.quote_no',
                        'qd.quote_version',
                        'qd.construction_id',
                        'qd.layer_flg',
                        'qd.parent_quote_detail_id',
                        'qd.seq_no',
                        'qd.depth',
                        'qd.tree_path',
                        'qd.row_print_flg',
                        'qd.price_print_flg',
                        'qd.sales_use_flg',
                        DB::raw('
                            CASE
                              WHEN qd.product_code = (SELECT value_text_1 FROM m_general WHERE category_code = \'NOPRODUCTCODE\')
                                THEN NULL 
                                ELSE qd.product_code
                            END AS product_code 
                        '),
                        'qd.product_name',
                        'qd.model', 
                        'qd.quote_quantity',
                        'qd.unit',
                        'qd.regular_price',
                        'qd.sales_unit_price',
                        'qd.sales_makeup_rate',
                        'qd.sales_total',
                        'qd.memo',
                        's.supplier_name',
                    ])
                    ->get();

        return $data;
    }

    /**
     * 見積番号・見積版・工事区分・深さ に紐づく見積明細を全取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @param int $constructionId 工事区分ID
     * @param int $depth 深さ
     * @return void
     */
    public function getConstractionDepthData($quoteNo, $quoteVersion, $constructionId, $depth) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.construction_id', '=', $constructionId);
        $where[] = array('t_quote_detail.depth', '=', $depth);
        

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 指定見積明細直下の見積明細を取得
     *
     * @param string $quoteNo 見積番号
     * @param int $parentQuoteId 親見積もり明細ID
     * @return void
     */
    public function getChildQuoteDetailData($quoteNo, $parentQuoteId) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.parent_quote_detail_id', '=', $parentQuoteId);
        

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 見積番号と版番号から見積版データを取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return int 見積明細データ
     */
    public function getByVer($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('quote_no', '=', $quoteNo);
        $where[] = array('quote_version', '=', $quoteVersion);

        // データ取得
        $data = $this
                ->where($where)
                ->get()
                ;
        return $data;
    }

    /**
     * ID(配列)でデータ取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getByIds($ids){
        // データ取得
        $data = $this
                ->whereIn('id', $ids)
                ->get()
                ;
        return $data;
    }

    /**
     * コピー元見積明細ID(配列)でデータ取得
     * 　0版は除外
     *
     * @param array $ids 見積明細IDの配列
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function getByCopyIds($ids, $quoteNo){
        // データ取得
        $data = $this
                ->where('quote_no', $quoteNo)
                ->whereIn('copy_quote_detail_id', $ids)
                ->where('quote_version', '!=', config('const.quoteCompVersion.number'))
                ->select('id')
                ->get()
                ;
        return $data;
    }

    /**
     * 超過元明細IDでデータ取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getByOverQuoteDetailId($id){
        // データ取得
        $data = $this
                ->where('over_quote_detail_id', $id)
                ->first()
                ;
        return $data;
    }

    /**
     * 金額更新
     *
     * @param int $quoteDetailId 見積明細ID
     * @param array $params 
     * @return void
     */
    public function updateTotalKng($quoteDetailId, $params) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $quoteDetailId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            // 更新内容
            $items = [];
            $items['cost_unit_price'] = $params['cost_unit_price'];
            $items['sales_unit_price'] = $params['sales_unit_price'];
            $items['cost_makeup_rate'] = $params['cost_makeup_rate'];
            $items['sales_makeup_rate'] = $params['sales_makeup_rate'];
            $items['cost_total'] = $params['cost_total'];
            $items['sales_total'] = $params['sales_total'];
            $items['gross_profit_rate'] = $params['gross_profit_rate'];
            $items['profit_total'] = $params['profit_total'];
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $this->validation($items);
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
     * 受注確定データ取得（発注詳細画面で使う用）
     *
     * @param [type] $params
     * @return void
     */
    public function getReceivedOrderListForOrderDetail($params){

        // 追加部材情報取得
        $Construction = new Construction();
        $constructionAddFlgData = $Construction->getAddFlgData();

        $where = [];

        $where[] = array('qd.quote_no', '=', $params['quote_no']);
        $where[] = array('qd.quote_version', '=', config('const.quoteCompVersion.number'));
        $where[] = array('qd.layer_flg', '=', config('const.flg.off'));
        $where[] = array('qd.add_flg', '=', config('const.flg.off'));
        $where[] = array('qd.construction_id', '<>', $constructionAddFlgData->id);
        //$where[] = array('qd.maker_id', '=', $params['maker_id']);

        if ($params['construction_id']) {
            $where[] = array('qd.construction_id', '=', $params['construction_id']);
        }

        if ($params['product_code']) {
            $where[] = array('qd.product_code', 'LIKE', '%'.$params['product_code'].'%');
        }

        if ($params['product_name']) {
            $where[] = array('qd.product_name', 'LIKE', '%'.$params['product_name'].'%');
        }

        // メインテーブル
        $quoteDetailQuery = DB::table('t_quote_detail as qd')
            ->where($where)
            ;
        
        // 全ての返品数
        $returnQuery = DB::table('t_warehouse_move')
            ->join('m_product', 't_warehouse_move.product_id', 'm_product.id')
            ->select([
                't_warehouse_move.quote_detail_id',
                DB::raw('SUM(t_warehouse_move.quantity * m_product.min_quantity) as return_quantity')
                
            ])
            ->where('t_warehouse_move.move_kind', config('const.moveKind.return'))
            ->where('t_warehouse_move.approval_status', config('const.approvalDetailStatus.val.approved'))
            ->groupBy('t_warehouse_move.quote_detail_id');


        // 在庫/預かりの引当数
        $stockQuery = DB::table('t_reserve')
            ->leftjoin('m_product as m_product', 't_reserve.product_id', '=', 'm_product.id')
            ->select([
                't_reserve.quote_detail_id',
                DB::raw('SUM(t_reserve.reserve_quantity * m_product.min_quantity) AS reserve_quantity')
            ])
            ->where('t_reserve.quote_id', $params['quote_id'])
            ->where('t_reserve.del_flg', config('const.flg.off'))
            ->where('t_reserve.stock_flg', '!=', config('const.stockFlg.val.order'))
            ->groupBy('t_reserve.quote_detail_id');
        

        // 発注数
        $orderQuery = DB::table('t_order')
            ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
            ->select([
                't_order_detail.quote_detail_id', 
                DB::raw('SUM(t_order_detail.order_quantity) AS order_quantity'),
            ])
            ->where('t_order.quote_no', $params['quote_no'])
            ->where('t_order.status', '!=', config('const.orderStatus.val.not_ordering'))
            ->where('t_order.status', '!=', config('const.orderStatus.val.sendback'))
            ->where('t_order.del_flg', config('const.flg.off'))
            ->where('t_order_detail.del_flg', config('const.flg.off') )
            ->groupBy('t_order_detail.quote_detail_id');


        $mainWhere = [];
        if (!is_null($params['parent_layer_name'])) {
            $mainWhere[] = array('parent_qd.product_name', 'LIKE', '%'.$params['parent_layer_name'].'%');
        }

        // データ取得
        $data = $this
                    // ->from('t_quote_detail as qd')
                    ->from(DB::raw('('.$quoteDetailQuery->toSql().') as qd'))
                    ->mergeBindings($quoteDetailQuery)
                    ->leftJoin(
                        DB::raw('('. $returnQuery->toSql(). ') as ret'),
                        'qd.id', '=', 'ret.quote_detail_id'
                    )->mergeBindings($returnQuery)
                    ->leftJoin(
                        DB::raw('('. $stockQuery->toSql(). ') as stock'),
                        'qd.id', '=', 'stock.quote_detail_id'
                    )->mergeBindings($stockQuery)
                    ->leftJoin(
                        DB::raw('('. $orderQuery->toSql(). ') as orders'),
                        'qd.id', '=', 'orders.quote_detail_id'
                    )->mergeBindings($orderQuery)
                    ->leftJoin('m_construction as c', 'qd.construction_id', 'c.id')
                    ->leftJoin('m_product as p', 'qd.product_id', 'p.id')
                    ->leftJoin('t_quote_detail as parent_qd', 'qd.parent_quote_detail_id', 'parent_qd.id')
                    ->where($mainWhere)
                    //->whereNotNull('product_id')
                    ->when(Common::isFlgOn($params['not_treated']), function($query){
                        $query
                            ->whereNull('ret.quote_detail_id')
                            ->whereNull('stock.quote_detail_id')
                            ->whereNull('orders.quote_detail_id');
                    })
                    ->select([
                        'qd.id AS quote_detail_id',
                        'qd.construction_id',
                        'qd.parent_quote_detail_id',
                        'qd.layer_flg',
                        'qd.sales_use_flg',
                        'qd.tree_path',
                        'qd.set_flg',
                        'qd.product_id',
                        'qd.product_code',
                        'qd.product_name',
                        'qd.maker_id',
                        'qd.maker_name',
                        'qd.supplier_id',
                        'qd.supplier_name',
                        'qd.unit',
                        'qd.stock_unit',
                        'qd.model',
                        'qd.cost_kbn',
                        'qd.min_quantity',
                        'qd.order_lot_quantity',
                        'p.intangible_flg',
                        'p.draft_flg',
                        'p.maker_id AS product_maker_id',

                        'qd.regular_price',
                        'qd.cost_unit_price',
                        'qd.cost_makeup_rate',
                        'qd.sales_unit_price',
                        'qd.sales_makeup_rate',
                        'qd.quote_quantity',
                        'qd.sales_kbn',

                        DB::raw('parent_qd.product_name AS parent_layer_name'),
                        'c.construction_name',
                        DB::raw('orders.order_quantity'),
                        DB::raw('stock.reserve_quantity'),
                        DB::raw('ret.return_quantity'),

                        'qd.quantity_per_case',
                        'qd.set_kbn',
                        'qd.class_big_id',
                        'qd.class_middle_id',
                        'qd.class_small_id',
                        'qd.tree_species',
                        'qd.grade',
                        'qd.length',
                        'qd.thickness',
                        'qd.width',
                    ])
                    ->orderBy('qd.tree_path', 'asc')
                    ->orderBy('qd.seq_no', 'asc')
                    ->get()
                    ;

        return $data;
    }

    /**
     * 見積番号・見積版に紐づく見積明細と出荷指示を取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getShipmentDataList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->join('t_shipment_detail', 't_quote_detail.id', 't_shipment_detail.quote_detail_id')
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.quote_no',
                    't_quote_detail.product_id',
                    't_quote_detail.product_code',
                    't_shipment_detail.id AS shipment_detail_id',
                    't_shipment_detail.reserve_id',
                    't_shipment_detail.loading_finish_flg',
                ])
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 見積番号・見積版に紐づく見積明細と一時保存を除いた発注明細を取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getOrderDetailList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_order_detail.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->join('t_order_detail', 't_quote_detail.id', 't_order_detail.quote_detail_id')
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.quote_no',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.product_id',
                    't_quote_detail.product_code',
                    't_quote_detail.product_name',
                    't_quote_detail.model',
                    't_quote_detail.maker_id',
                    't_quote_detail.maker_name',
                    //'t_quote_detail.supplier_id',
                    //'t_quote_detail.supplier_name',
                    //'t_quote_detail.quote_quantity',
                    't_quote_detail.min_quantity',
                    't_quote_detail.stock_quantity',
                    't_quote_detail.unit',
                    't_quote_detail.stock_unit',
                    't_quote_detail.regular_price',
                    //'t_quote_detail.allocation_kbn',
                    //'t_quote_detail.cost_kbn',
                    't_quote_detail.sales_kbn',
                    't_quote_detail.cost_unit_price',
                    't_quote_detail.sales_unit_price',
                    't_quote_detail.cost_makeup_rate',
                    't_quote_detail.sales_makeup_rate',
                    't_order_detail.id AS order_detail_id',
                    't_order_detail.product_id AS order_product_id',
                    't_order_detail.order_quantity',
                    't_order_detail.arrival_quantity',
                    't_order_detail.cost_total',
                    't_order_detail.sales_total',
                    't_order_detail.parent_order_detail_id',
                ])
                ->where($where)
                ->whereNotNull('t_order_detail.order_no')
                ->get()
                ;

        return $data;
    }

    /**
     * 見積番号・見積版に紐づく見積明細と納品を取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getDeliveryDataList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_delivery.delivery_quantity', '>=', 1);
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_delivery.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->join('t_delivery', 't_quote_detail.id', 't_delivery.quote_detail_id')
                ->select([
                    't_quote_detail.*',
                    't_delivery.id AS delivery_id'
                ])
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 見積番号・見積版に紐づく見積明細と売上明細を取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getSalesDetailDataList($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_sales_detail.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->join('t_sales_detail', 't_quote_detail.id', 't_sales_detail.quote_detail_id')
                ->select([
                    't_quote_detail.*',
                ])
                ->where($where)
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 数量超過の見積明細の登録/更新/削除をする
     *
     * @param $quoteId 見積ID
     * @param $quoteNo 見積番号
     * @param $addQuoteDetailInfo 追加部材情報
     * @return void
     */
    public function updateOverQuoteDetailData($quoteId, $quoteNo, $addQuoteDetailInfo) {

        $SystemUtil = new SystemUtil();

        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // 全ての返品数
        $returnQuery = DB::table('t_warehouse_move')
            ->join('m_product', 't_warehouse_move.product_id', 'm_product.id')
            ->select([
                't_warehouse_move.quote_detail_id',
                DB::raw('SUM(t_warehouse_move.quantity * m_product.min_quantity) as return_quantity')
                
            ])
            ->where('t_warehouse_move.move_kind', config('const.moveKind.return'))
            ->where('t_warehouse_move.approval_status', config('const.approvalDetailStatus.val.approved'))
            ->groupBy('t_warehouse_move.quote_detail_id');


        // 在庫/預かりの引当数
        $stockQuery = DB::table('t_reserve')
            ->leftjoin('m_product as m_product', 't_reserve.product_id', '=', 'm_product.id')
            ->select([
                't_reserve.quote_detail_id',
                DB::raw('SUM(t_reserve.reserve_quantity * m_product.min_quantity) AS reserve_quantity')
            ])
            ->where('t_reserve.quote_id', $quoteId)
            ->where('t_reserve.del_flg', config('const.flg.off'))
            ->where('t_reserve.stock_flg', '!=', config('const.stockFlg.val.order'))
            ->groupBy('t_reserve.quote_detail_id');
        

        // 発注数
        $orderQuery = DB::table('t_order')
            ->join('t_order_detail', 't_order.id', 't_order_detail.order_id')
            ->select([
                't_order_detail.quote_detail_id', 
                DB::raw('SUM(t_order_detail.order_quantity) AS order_quantity'),
            ])
            ->where('t_order.quote_no', $quoteNo)
            ->where('t_order.status', '!=', config('const.orderStatus.val.not_ordering'))
            ->where('t_order.status', '!=', config('const.orderStatus.val.sendback'))
            ->where('t_order.del_flg', config('const.flg.off'))
            ->where('t_order_detail.del_flg', config('const.flg.off') )
            ->groupBy('t_order_detail.quote_detail_id');

        // 取得データは見積明細単位
        $data = $this
            ->leftJoin(
                DB::raw('('. $returnQuery->toSql(). ') as ret'),
                't_quote_detail.id', '=', 'ret.quote_detail_id'
            )->mergeBindings($returnQuery)
            ->leftJoin(
                DB::raw('('. $stockQuery->toSql(). ') as stock'),
                't_quote_detail.id', '=', 'stock.quote_detail_id'
            )->mergeBindings($stockQuery)
            ->leftJoin(
                DB::raw('('. $orderQuery->toSql(). ') as orders'),
                't_quote_detail.id', '=', 'orders.quote_detail_id'
            )->mergeBindings($orderQuery)
            ->select([
                't_quote_detail.*',
                DB::raw('orders.order_quantity'),
                DB::raw('stock.reserve_quantity'),
                DB::raw('ret.return_quantity'),
            ])
            ->where('t_quote_detail.del_flg', config('const.flg.off') )
            ->where('t_quote_detail.quote_no', $quoteNo)
            ->where('t_quote_detail.quote_version', config('const.quoteCompVersion.number'))
            ->get()->toArray();

        // 数量超過元明細IDは0以外は見積明細IDと1:1で紐づく想定で、ループ処理内で同一明細が複数回処理されることはないので先に一括取得
        $overQuoteDetailDataList = $this
            ->where('quote_no', $quoteNo)
            ->where('quote_version', config('const.quoteCompVersion.number'))
            ->where('over_quote_detail_id', '!=', 0)
            ->get();

        foreach($data as $row){
            $overQuantity = (Common::nullorBlankToZero($row['order_quantity']) + Common::nullorBlankToZero($row['reserve_quantity']) - Common::nullorBlankToZero($row['return_quantity'])) - $row['quote_quantity'];
            // $overQuoteDetailData = $this
            //     ->where(['over_quote_detail_id' => $row['id']])
            //     ->first()
            //     ;
            $overQuoteDetailData = $overQuoteDetailDataList->where('over_quote_detail_id', $row['id'])->first();

            if($overQuoteDetailData !== null){
                if($overQuantity > 0){
                    $overQuantityData = $overQuoteDetailData->toArray();
                    $overQuantityData['quote_quantity'] = $overQuantity;
                    // 管理数
                    $overQuantityData['stock_quantity'] = $SystemUtil->calcStock($overQuantityData['quote_quantity'], $overQuantityData['min_quantity']);
                    // 仕入総額 = 数量 * 仕入単価
                    $overQuantityData['cost_total']     = $SystemUtil->calcTotal($overQuantityData['quote_quantity'], $overQuantityData['cost_unit_price'], true);
                    // 販売総額 = 数量 * 販売単価
                    $overQuantityData['sales_total']    = $SystemUtil->calcTotal($overQuantityData['quote_quantity'], $overQuantityData['sales_unit_price'], false);
                    // 粗利総額 = 販売総額 - 仕入総額
                    $overQuantityData['profit_total']   = $SystemUtil->calcProfitTotal($overQuantityData['sales_total'], $overQuantityData['cost_total']);
                    // 粗利率 = 粗利総額 / 販売総額 * 100
                    $overQuantityData['gross_profit_rate']  = $SystemUtil->calcRate($overQuantityData['profit_total'], $overQuantityData['sales_total']); 
                    $this->updateByIdEx($overQuoteDetailData->id, $overQuantityData);
                }else{
                    $this->deleteList([$overQuoteDetailData->id]);
                }
            }else{
                if($overQuantity > 0){
                    $overQuantityData = $row;
                    $overQuantityData['construction_id']        = $addQuoteDetailInfo['construction_id'];
                    $overQuantityData['parent_quote_detail_id'] = $addQuoteDetailInfo['parent_quote_detail_id'];
                    $overQuantityData['depth']          = $addQuoteDetailInfo['depth'];
                    $overQuantityData['seq_no']         = 99999;
                    $overQuantityData['tree_path']      = $addQuoteDetailInfo['tree_path'];
                    $overQuantityData['add_flg']        = config('const.flg.off');
                    $overQuantityData['quote_quantity'] = $overQuantity;
                    unset($overQuantityData['created_user']);
                    unset($overQuantityData['created_at']);
                    unset($overQuantityData['update_user']);
                    unset($overQuantityData['update_at']);

                    // 管理数
                    $overQuantityData['stock_quantity'] = $SystemUtil->calcStock($overQuantityData['quote_quantity'], $overQuantityData['min_quantity']);
                    // 仕入総額 = 数量 * 仕入単価
                    $overQuantityData['cost_total']     = $SystemUtil->calcTotal($overQuantityData['quote_quantity'], $overQuantityData['cost_unit_price'], true);
                    // 販売総額 = 数量 * 販売単価
                    $overQuantityData['sales_total']    = $SystemUtil->calcTotal($overQuantityData['quote_quantity'], $overQuantityData['sales_unit_price'], false);
                    // 粗利総額 = 販売総額 - 仕入総額
                    $overQuantityData['profit_total']   = $SystemUtil->calcProfitTotal($overQuantityData['sales_total'], $overQuantityData['cost_total']);
                    // 粗利率 = 粗利総額 / 販売総額 * 100
                    $overQuantityData['gross_profit_rate']  = $SystemUtil->calcRate($overQuantityData['profit_total'], $overQuantityData['sales_total']); 
                    
                    $overQuantityData['over_quote_detail_id']   = $overQuantityData['id'];
                    $this->addEx($overQuantityData);
                }
            }
        }
    }


    /**
     * バリデーションチェック
     * @param $params   登録更新対象の行データ
     */
    private function validation($params){
        $validator = Validator::make(
            $params, 
            [
                'quote_quantity'    => 'nullable|numeric|between:0,9999999.99',
                'min_quantity'      => 'nullable|numeric|between:0,9999999.99',
                'order_lot_quantity'=> 'nullable|numeric|between:0,9999999.99',
                'stock_quantity'    => 'nullable|integer',
                'regular_price'     => 'nullable|numeric|between:-999999999,999999999',
                'cost_unit_price'   => 'nullable|numeric|between:-999999999,999999999',
                'sales_unit_price'  => 'nullable|numeric|between:-999999999,999999999',
                'cost_makeup_rate'  => 'nullable|numeric|between:-999.99,999.99',
                'sales_makeup_rate' => 'nullable|numeric|between:-999.99,999.99',
                'cost_total'        => 'nullable|numeric|between:-999999999,999999999',
                'sales_total'       => 'nullable|numeric|between:-999999999,999999999',
                'gross_profit_rate' => 'nullable|numeric|between:-99999.99,99999.99',
                'profit_total'      => 'nullable|numeric|between:-999999999,999999999',
            ],
            [
                'quote_quantity.between'    => '見積数が、'.    config('message.error.exceeded_length'),
                'min_quantity.between'      => '最小単位数が、'.config('message.error.exceeded_length'),
                'order_lot_quantity.between'=> '発注ロット数が、'.config('message.error.exceeded_length'),
                'stock_quantity.between'    => '管理数が、'.    config('message.error.exceeded_length'),
                'regular_price.between'     => '定価が、'.      config('message.error.exceeded_length'),
                'cost_unit_price.between'   => '仕入単価が、'.  config('message.error.exceeded_length'),
                'sales_unit_price.between'  => '販売単価が、'.  config('message.error.exceeded_length'),
                'cost_makeup_rate.between'  => '仕入掛率が、'.  config('message.error.exceeded_length'),
                'sales_makeup_rate.between' => '販売掛率が、'.  config('message.error.exceeded_length'),
                'cost_total.between'        => '仕入総額が、'.  config('message.error.exceeded_length'),
                'sales_total.between'       => '販売総額が、'.  config('message.error.exceeded_length'),
                'gross_profit_rate.between' => '粗利率が、'.    config('message.error.exceeded_length'),
                'profit_total.between'      => '粗利が、'.      config('message.error.exceeded_length'),
            ]);

        if ($validator->fails()) {
           throw new ValidationException($validator->errors());
        }

    }

    /**
     * 更新
     *
     * @param int $productId 商品ID
     * @param array $params 
     * @param bool $integrate 統合フラグ　ON = 統合(商品IDを更新)
     * @return void
     */
    public function updateByProductId($productId, $params, $integrate) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('product_id', '=', $productId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            // 更新内容
            $items = [];
            if ($integrate) {
                $items['product_id'] = $params['id'];
            }
            if (!empty($params['product_code'])) {
                $items['product_code'] = $params['product_code'];
            }
            if (!empty($params['product_name'])) {
                $items['product_name'] = $params['product_name'];
            }
            if (!empty($params['unit'])) {
                $items['unit'] = $params['unit'];
            }
            if (!empty($params['stock_unit'])) {
                $items['stock_unit'] = $params['stock_unit'];
            }
            if (!empty($params['order_lot_quantity'])) {
                $items['order_lot_quantity'] = $params['order_lot_quantity'];
            }

            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

            $result = true;
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 見積番号から取得
     * 販売額利用フラグが立っているデータ
     *
     * @param [type] $quoteNo   見積番号
     * @return void
     */
    public function getSalesUseFlgQuoteByQuoteNo($quoteNo)
    {
        $SystemUtil = new SystemUtil();
        $where = [];
        $rtnData = [];
        // $where[] = array('qd.id', '=', $quoteDetailId);
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number'));
        // $where[] = array('t_quote_detail.sales_use_flg', '=', config('const.flg.on'));
        $where[] = array('t_quote_detail.layer_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));

        $whereSalesUse = [];
        $whereSalesUse[] = array('t_quote.quote_no', '=', $quoteNo);
        $whereSalesUse[] = array('t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number'));
        $whereSalesUse[] = array('t_quote.del_flg', '=', config('const.flg.off'));
        $whereSalesUse[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        $whereSalesUse[] = array('t_quote_detail.sales_use_flg', '=', config('const.flg.on'));
        // $whereSalesUse[] = array('t_quote_detail.layer_flg', '=', config('const.flg.off'));

        $quoteDetailData = $this
                ->where([['quote_no', '=', $quoteNo], ['layer_flg', '=', config('const.flg.off')]])
                ->select([
                    'id',
                    'tree_path',
                    't_quote_detail.layer_flg',
                    't_quote_detail.sales_use_flg',
                ])
                ->get()
                ;

        $quoteDetailIds = [];
        foreach($quoteDetailData as $key => $row){
            $treePathList = explode(config('const.treePathSeparator'), $row->tree_path);
            $quoteDetailIds[] = end($treePathList);
        }
        $quoteDetailIds = collect($quoteDetailIds)->unique();

        // 販売額利用フラグが立っている見積明細を取得
        $salesUseData = $this
                ->join('t_quote', 't_quote_detail.quote_no', 't_quote.quote_no')
                ->leftjoin('t_order_detail', 't_order_detail.quote_detail_id', '=', 't_quote_detail.id')
                ->leftjoin('t_purchase_line_item AS pl', 'pl.order_detail_id', 't_order_detail.id')
                ->whereIn('t_quote_detail.id', $quoteDetailIds)
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.quote_no',
                    't_quote_detail.cost_unit_price',
                    't_quote_detail.quote_quantity',
                    'pl.fix_cost_total',
                    't_quote_detail.tree_path',     
                ])
                ->where($whereSalesUse)
                ->get()
                ;

        $data = $this
                ->where($where)
                ->join('t_order_detail AS od', 'od.quote_detail_id', '=', 't_quote_detail.id')
                ->join('t_order AS o', function($join) {
                    $join
                        ->on('od.order_id', '=', 'o.id')
                        ->where('o.status', '=', config('const.orderStatus.val.ordered'))
                        ;
                })
                ->leftjoin('t_purchase_line_item AS pl', 'pl.order_detail_id', '=', 'od.id')
                ->select([
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.quote_no',
                    't_quote_detail.cost_unit_price',
                    'pl.fix_cost_total',
                    't_quote_detail.quote_quantity',
                    't_quote_detail.tree_path',
                ])
                ->get()
                ;
 
        $salesDetailData = [];
        foreach($data as $key => $row){
            $costPrice = 0;
            if(!Common::isFlgOn($row->layer_flg) && !Common::isFlgOn($row->sales_use_flg)){
                $find =false;
                $treePathList = explode(config('const.treePathSeparator'), $row->tree_path);
                foreach($treePathList as $quoteDetailId){
                    // 販売額利用フラグが立っている見積明細IDを含むツリーパスか
                    $salesUseFlgData = $salesUseData->firstWhere('quote_detail_id', '=', $quoteDetailId);
                    if($salesUseFlgData !== null){
                        $find = true;
                        $detailData = $row;
                        break;
                    }
                }

                if ($find) {
                    if (!empty($detailData['fix_cost_total'])) {
                        // 仕入明細があれば、調整金額を使用する
                        $costPrice = $detailData['fix_cost_total'];
                    } else {
                        $costPrice = $SystemUtil->calcTotal($detailData['quote_quantity'], $detailData['cost_unit_price'], true);
                    }
                    if (empty($salesDetailData[$salesUseFlgData['quote_detail_id']]['cost_total'])) {
                        $salesDetailData[$salesUseFlgData['quote_detail_id']]['cost_total'] = 0;
                    }
                    
                    $salesDetailData[$salesUseFlgData['quote_detail_id']]['quote_detail_id'] = $detailData['quote_detail_id'];
                    $salesDetailData[$salesUseFlgData['quote_detail_id']]['parent_quote_detail_id'] = $salesUseFlgData['quote_detail_id'];
                    $salesDetailData[$salesUseFlgData['quote_detail_id']]['cost_total'] += $costPrice;
                }
            }
        }        
        
        foreach ($salesDetailData as $key => $row) {
            $rtnData[] = $row;
        }


        return $rtnData;
    }

    /**
     * 受注確定データを取得(案件詳細画面用)
     *
     * @param [type] $quoteNo 見積番号
     * @param [type] $isDetailsOnly 明細データのみ 
     * @return void
     */
    public function getReceivedOrderForMatterDetail($matterNo, $quoteNo){
        $sub = DB::table('t_matter_detail')
                ->where([
                    ['matter_no', '=', $matterNo],
                    ['quote_detail_id', '<>', 0],
                ])
                ->select('quote_detail_id')
                ->distinct();
        $data = $this
                    ->leftJoin('t_quote_detail AS over_quote_detail', 't_quote_detail.id', 'over_quote_detail.over_quote_detail_id')
                    ->leftJoin(DB::raw('('. $sub->toSql(). ') AS matter_detail'), function($join){
                        $join
                            ->on('t_quote_detail.id', 'matter_detail.quote_detail_id');
                    })
                    ->mergeBindings($sub)
                    ->where([
                        ['t_quote_detail.quote_no', '=', $quoteNo],
                        ['t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number')],
                        ['t_quote_detail.over_quote_detail_id', '=', 0],
                        ['t_quote_detail.layer_flg', '=', config('const.flg.off')],
                        ['t_quote_detail.del_flg', '=', config('const.flg.off')],
                    ])
                    ->select(
                        't_quote_detail.id',
                        't_quote_detail.product_id',
                        't_quote_detail.construction_id',
                        't_quote_detail.class_small_id',
                        't_quote_detail.product_name',
                        DB::raw('(t_quote_detail.stock_quantity + COALESCE(over_quote_detail.stock_quantity, 0)) AS stock_quantity'),
                        DB::raw('
                            CASE
                              WHEN matter_detail.quote_detail_id IS NULL
                                THEN '. config('const.flg.off'). ' ELSE '. config('const.flg.on'). '
                            END AS matter_detail_exists
                        ')
                    )
                    ->get();
        return $data;
    }

    /**
     * 商品IDをキーに取得
     *
     * @param int $productId 商品ID
     * @return 
     */
    public function getDataByProduct($productId) {
        $data = $this
                ->from('t_quote_detail AS qd2')
                ->leftJoin('t_quote AS q2', 'qd2.quote_no', '=', 'q2.quote_no')
                ->where([
                    ['qd2.product_id', '=', $productId],
                    ['qd2.del_flg', '=', config('const.flg.off')],
                    ['q2.del_flg', '=', config('const.flg.off')]
                ])
                ->select([
                    'q2.id AS quote_id',
                    'qd2.id AS quote_detail_id',
                    'qd2.quote_no',
                    'qd2.construction_id',
                    'qd2.product_code',
                    'qd2.maker_id'
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 見積版内で使用されている商品データを取得する
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function getProductsByQuoteVersion($quoteNo, $quoteVersion) {
        // Where句作成
        $mainWhere = [];
        $mainWhere[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $mainWhere[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        $mainWhere[] = array('t_quote_detail.product_id', '!=', 0);
        $mainWhere[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $mainQuery = DB::table('t_quote_detail')
                ->where($mainWhere)
                ->groupBy('t_quote_detail.product_id')
                ->select('t_quote_detail.product_id')
                ;

        // データ取得
        $data = $this
                ->from(DB::raw('('.$mainQuery->toSql().') as qd'))
                ->mergeBindings($mainQuery)
                ->leftjoin('m_product', 'qd.product_id', '=', 'm_product.id')
                ->selectRaw('m_product.*')
                ->where('m_product.del_flg', config('const.flg.off'))
                ->get();

        return $data;
    }

    /**
     * 積算完了項目の工事区分IDを取得する
     *
     * @return array
     */
    public function getCompEstimationListByQuoteNo($quoteNo, $quoteVersion=null)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_no', '=', $quoteNo);
        $where[] = array('quote_version', '!=', config('const.quoteCompVersion.number'));
        if ($quoteVersion) {
            $where[] = array('quote_version', '=', $quoteVersion);
        }
        $where[] = array('depth', '=', 0);
        $where[] = array('complete_flg', '=', config('const.flg.on'));

        $data = $this
                    ->where($where)
                    ->pluck('construction_id');
        return $data->toArray();
    }

    /**
     * [案件詳細専用]工事区分で最大の見積作成日を取得（0版以外）
     *
     * @param [type] $quoteNo
     * @return void
     */
    public function getSchedulerQuoteFollowForMatterDetail($quoteNo){
        $subQuoteVersion = DB::table('t_quote_version')
                                ->where([
                                    ['del_flg', '=', config('const.flg.off')],
                                    ['quote_no', '=', $quoteNo],
                                    ['quote_version', '!=', config('const.quoteCompVersion.number')]
                                ])
                                ->select(
                                    'quote_no',
                                    'quote_version',
                                    'quote_create_date'
                                );
        $data = $this
                    ->leftJoin(DB::raw('('. $subQuoteVersion->toSql(). ') AS t_quote_version'), function($join){
                        $join
                            ->on('t_quote_detail.quote_no', '=', 't_quote_version.quote_no')
                            ->on('t_quote_detail.quote_version', '=', 't_quote_version.quote_version');
                    })
                    ->mergeBindings($subQuoteVersion)
                    ->where([
                        ['t_quote_detail.del_flg', '=', config('const.flg.off')],
                        ['t_quote_detail.quote_no', '=', $quoteNo],
                        ['t_quote_detail.quote_version', '!=', config('const.quoteCompVersion.number')],
                    ])
                    ->select(
                        't_quote_detail.quote_no',
                        't_quote_detail.construction_id',
                        DB::raw('MAX(t_quote_version.quote_create_date) AS quote_create_date')
                    )
                    ->groupBy('t_quote_detail.quote_no', 't_quote_detail.construction_id')
                    ->get()
                    ;
        return $data;
    }

    /**
     * 0版に存在する工事区分
     *
     * @param [type] $quoteNo
     * @return array
     */
    public function getReceivedConstruction($quoteNo){
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_no', '=', $quoteNo);
        $where[] = array('quote_version', '=', config('const.quoteCompVersion.number'));
        $where[] = array('layer_flg', '=', config('const.flg.on'));
        $where[] = array('add_flg', '=', config('const.flg.off'));
        $where[] = array('depth', '=', 0);

        $data = $this
                    ->where($where)
                    ->pluck('construction_id');
        return $data->toArray();
    }

    /**
     * 明細に対して1段上位の階層名を取得する
     *
     * @param [type] $quoteNo
     * @param [type] $quoteVersion
     * @return void
     */
    public function getNearestParentLayerName($quoteNo, $quoteVersion=null)
    {
        $where = [];
        $where[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote_detail.quote_no', '=', $quoteNo);
        $where[] = array('t_quote_detail.layer_flg', '=', config('const.flg.off'));
        if (!is_null($quoteVersion)) {
            $where[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
        }

        $data = $this
                    ->leftJoin('t_quote_detail AS parent_quote_detail', function($join){
                        $join
                            ->on('t_quote_detail.parent_quote_detail_id', '=', 'parent_quote_detail.id')
                            ->where('parent_quote_detail.del_flg', '=', config('const.flg.off'));
                    })
                    ->where($where)
                    ->select(
                        't_quote_detail.id AS quote_detail_id',
                        'parent_quote_detail.id AS parent_quote_detail_id',
                        'parent_quote_detail.product_name AS layer_name'
                    )
                    ->get();
        return $data;
    }

    /**
     * IDでデータ取得
     * ヘッダ情報と紐付け
     *
     * @param [type] $ids
     * @return void
     */
    public function getQuoteByOrderDetailId($id){
        $where = [];
        $where[] = array('t_quote_detail.id', '=', $id);

        // データ取得
        $data = $this
            ->from('t_quote_detail')
            ->leftJoin('t_quote', 't_quote.quote_no', '=', 't_quote_detail.quote_no')
            ->where($where)
            ->select([
                't_quote_detail.*',
                't_quote.id AS quote_id'
            ])
            ->first()
            ;

        return $data;
    }
}