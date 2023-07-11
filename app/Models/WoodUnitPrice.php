<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use Carbon\Carbon;
use App\Libs\LogUtil;


/**
 * 木材立米単価
 */
class WoodUnitPrice extends Model
{
    // テーブル名
    protected $table = 't_wood_unitprice';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'product_id',
        'price_date',
        'unitprice',
        'purchasing_cost',
        'purchase_unit_price',
        'sales_unit_price',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    /**
     * 一覧取得 (木材立米単価入力)
     *
     * @param [type] $params
     * @return void
     */
    public function getList($params)
    {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.intangible_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.auto_flg', '=', config('const.flg.off'));

        if (!empty($params['product_code'])) {
            $where[] = array('m_product.product_code', 'LIKE', '%'.$params['product_code'].'%');
        }
        if (!empty($params['product_name'])) {
            $where[] = array('m_product.product_name', 'LIKE', '%'.$params['product_name'].'%');
        }
        if (!empty($params['model'])) {
            $where[] = array('m_product.model', 'LIKE', '%'.$params['model'].'%');
        }

        // 木材ID取得
        $ClassBig = new ClassBig();
        $classBigWood = $ClassBig->getByFormatKbn(config('const.classBigFormatKbn.val.wood'));
        $woodIds = collect($classBigWood)->pluck('id')->toArray();

        $productList = $this
                    ->from('m_product')
                    ->where($where)
                    ->whereIn('class_big_id', $woodIds)
                    ->get()
                    ;
        
        $productIds = collect($productList)->pluck('id')->toArray();

        // 木材立米
        $woodUnitpriceBaseQuery = DB::table('t_wood_unitprice')
                ->select([
                    'product_id',
                    DB::raw('MAX(price_date) AS price_date'),
                ])
                ->whereRaw('
                    del_flg =\''. config('const.flg.off').'\'
                ')
                // ->whereRaw('price_date <= CURRENT_DATE()')
                // ->whereIN('product_id', $productIds)
                ->groupBy('product_id')
                ;

        $woodUnitpriceQuery = DB::table('t_wood_unitprice AS wu1')
                ->join(DB::raw('('.$woodUnitpriceBaseQuery->toSql().') as wu2'), function($join){
                    $join->on('wu1.product_id', 'wu2.product_id')
                    ->on('wu1.price_date', 'wu2.price_date');
                })
                ->mergeBindings($woodUnitpriceBaseQuery)
                ->select([
                    'wu1.id',
                    'wu1.product_id',
                    'wu1.purchase_unit_price',
                    'wu1.price_date',
                    'wu1.unitprice',
                    'wu1.sales_unit_price',
                    'wu1.purchasing_cost',
                ]);

        $productPurchaseBaseQuery = DB::table('t_wood_unitprice_bid') 
                ->select([
                    'product_id',
                    DB::raw('MAX(id) as id')
                ])
                // ->whereIN('product_id', $productIds)
                ->groupBy('product_id')
                ;

        $productPurchaseQuery = DB::table('t_wood_unitprice_bid as wub1') 
                ->join(DB::raw('('.$productPurchaseBaseQuery->toSql().') as wub2'), function($join){
                    $join->on('wub1.product_id', 'wub2.product_id');
                })
                ->mergeBindings($productPurchaseBaseQuery)
                ->select([
                    'wub1.product_id',
                    'wub1.price',
                    'wub1.auction_date',
                ])
                ->whereRaw('
                    del_flg = \''. config('const.flg.off'). '\'
                    AND bid_kbn = \''. config('const.flg.off'). '\'
                ')
                ;


        $data = $this
                ->from('m_product AS p')
                ->leftJoin(DB::raw('('.$woodUnitpriceQuery->toSql().') as wq1'), function($join){
                    $join->on('p.id', 'wq1.product_id');
                })
                ->mergeBindings($woodUnitpriceQuery)
                // ->leftJoin(DB::raw('('.$productPurchaseQuery->toSql().') as wub1'), function($join){
                //     $join->on('p.id', 'wub1.product_id');
                // })
                // ->mergeBindings($productPurchaseQuery)
                ->leftJoin('m_general AS t_spec', function ($join) {
                    $join
                        ->on('t_spec.value_code', '=', 'p.tree_species')
                        ->where('t_spec.category_code', '=', config('const.general.wood'))
                        ;
                })
                ->leftJoin('m_general AS grade', function ($join) {
                    $join
                        ->on('grade.value_code', '=', 'p.grade')
                        ->where('grade.category_code', '=', config('const.general.grade'))
                        ;
                })
                ->whereIn('p.id', $productIds)
                // ->join(DB::raw('('.$productCustomerPriceQuery->toSql().') as pcp2'), function($join){
                //     $join->on('pcp1.id', 'pcp2.id');
                // })
                ->select([
                    'wq1.id',
                    'p.id AS product_id',
                    'p.class_big_id',
                    'p.product_code',
                    'p.product_name',
                    'p.length',
                    'p.thickness',
                    'p.width',
                    'p.unit',
                    // 'p.grade',
                    // 'p.tree_species',
                    't_spec.value_text_1 AS tree_species',
                    'grade.value_text_1 AS grade',
                    'wq1.purchase_unit_price',
                    // 'wq1.price_date',
                    'wq1.unitprice',
                    'wq1.sales_unit_price',
                    'wq1.purchasing_cost',
                    'wq1.purchasing_cost AS last_purchasing_cost',
                ])
                // 0 AS purchasing_cost,
                // 0 AS purchase_unit_price,
                // 0 AS sales_unit_price,
                // 0 AS unitprice,
                // null as price_date,
                ->selectRaw('
                    CONCAT(COALESCE(p.length, \'\'), CONCAT(\'×\'), COALESCE(p.thickness, \'\'), CONCAT(\'×\'), COALESCE(p.width, \'\')) AS size,
                    DATE_FORMAT(wq1.price_date, "%Y/%m/%d") as price_date,
                    DATE_FORMAT(wq1.price_date, "%Y/%m/%d") as last_purchase_date
                ')
                ->get()
                ;

        return $data;
    }


    /**
     * 商品ID、指定日付より未来日のデータ取得
     *
     * @param  $productId 商品ID
     * @param  $priceDate yyyy/mm/dd
     * @return boolean
     */
    public function getFutureDataByProductIdAndDate($productId, $priceDate) 
    {
        $where = [];
        $where[] = array('product_id', '=', $productId);
        $where[] = array('price_date', '>', $priceDate);

        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 登録
     * @param $params 1行の配列
     * @return id
     */
    public function add($params){
        $items = [];

        $userId = Auth::user()->id;
        $now    = Carbon::now();

        try {
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
                    switch($colName){
                        case 'id':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }

            // 登録
            return $this->insertGetId($items);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 更新
     * @param int $id
     * @param array $params 1行の配列
     * @return void
     */
    public function updateById($id, $params) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $id);

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
            $items['update_user']   = Auth::user()->id;
            $items['update_at']     = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

            $result = ($updateCnt == 1);
            // 登録
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 論理削除
     * @param int $id
     * @return boolean True:成功 False:失敗 
     */
    public function softDeleteById($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }



    /**
     * 論理削除
     * @param int $id
     * @return boolean True:成功 False:失敗 
     */
    public function deleteById($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->delete()
                ;

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
}