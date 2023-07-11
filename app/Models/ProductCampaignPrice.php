<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;

 /**
 * 商品キャンペーン単価マスタ
 * 同一商品、同一仕入れ販売区分で適用期間は重複しない
 */
class ProductCampaignPrice extends Model
{
    // テーブル名
    protected $table = 'm_product_campaign_price';
    // タイムスタンプ自動更新Off
    public $timestamps = false;
  
    // /**
    //  * キャンペーン特価の仕入/販売単価
    //  * 得意先別標準の仕入/販売単価
    //  * 標準の仕入単価
    //  * 上記の3区分の情報を返す
    //  * @param $customerId 得意先ID
    //  * @param $costSalesKbn 仕入販売区分 const.costSalesKbn
    //  * @param $toDay キャンペーン期間の日付 Y/m/d
    //  * @return 
    //  */
    // public function getList($customerId, $costSalesKbn = null, $toDay = null) {
    //     // 開始日
    //     if($toDay === null){
    //         $toDay = Carbon::today()->format('Y/m/d');
    //     }
    //     // キャンペーン単価
    //     $campaignPriceWhere = [];
    //     $campaignPriceWhere[] = array('start_date', '<=', $toDay);
    //     $campaignPriceWhere[] = array('end_date', '>=', $toDay);
        
    //     if (!is_null($costSalesKbn)) {
    //         $campaignPriceWhere[] = array('cost_sales_kbn', '=', $costSalesKbn);
    //     }
                
    //     $query1 = $this
    //             ->where($campaignPriceWhere)
    //             ->select([
    //                 'product_id',
    //                 'cost_sales_kbn',
    //                 'price',
    //             ])
    //             ->selectRaw(
    //                 config('const.unitPriceKbn.campaign').' AS unit_price_kbn'
    //             )
    //             ;


    //     // 得意先別商品単価
    //     $customerPriceWhere = [];
    //     $customerPriceWhere[] = array('customer_id', '=', $customerId);
    //     if (!is_null($costSalesKbn)) {
    //         $customerPriceWhere[] = array('cost_sales_kbn', '=', $costSalesKbn);
    //     }

    //     $productCustomerPriceQuery = DB::table('t_product_customer_price') 
    //             ->select([
    //                 DB::raw('MAX(id) as id')
    //             ])
    //             ->where($customerPriceWhere)
    //             ->groupBy('customer_id', 'product_id', 'cost_sales_kbn')
    //             ;

    //     $query2 = DB::table('t_product_customer_price as main1') 
    //             ->join(DB::raw('('.$productCustomerPriceQuery->toSql().') as sub1'), function($join){
    //                 $join->on('main1.id', 'sub1.id');
    //             })
    //             ->mergeBindings($productCustomerPriceQuery)
    //             ->where($customerPriceWhere)
    //             ->select([
    //                 'main1.product_id',
    //                 'main1.cost_sales_kbn',
    //                 'main1.price',
    //             ])
    //             ->selectRaw(
    //                 config('const.unitPriceKbn.cutomer_normal').' AS unit_price_kbn'
    //             )
    //             ;

    //     $query3 = DB::table('m_product')
    //                 ->leftJoin('m_class_big', 'm_product.class_big_id', 'm_class_big.id')
    //                 // 木材立米
    //                 ->leftjoin(DB::raw('
    //                 (
    //                     SELECT
    //                       wu1.product_id, wu1.unitprice
    //                     FROM
    //                       t_wood_unitprice wu1
    //                     INNER JOIN  (
    //                       SELECT product_id, MAX(price_date) AS price_date
    //                       FROM t_wood_unitprice
    //                       WHERE del_flg='. config('const.flg.off'). ' AND price_date <= CURRENT_DATE() GROUP BY product_id) wu2
    //                     ON wu1.product_id = wu2.product_id
    //                     AND wu1.price_date = wu2.price_date
    //                 ) AS wood_unitprice'), function($join){
    //                     $join->on('m_product.id', '=', 'wood_unitprice.product_id');
    //                 })
    //                 // 商品仕入単価
    //                 ->leftjoin(DB::raw('
    //                 (
    //                     SELECT
    //                       pcp1.product_id, pcp1.price
    //                     FROM
    //                       t_product_cost_price pcp1
    //                     INNER JOIN
    //                       (SELECT MAX(id) AS id FROM t_product_cost_price GROUP BY product_id) pcp2
    //                     ON pcp1.id = pcp2.id
    //                 ) AS product_cost_price'), function($join){
    //                     $join->on('m_product.id', '=', 'product_cost_price.product_id');
    //                 })
    //                 ->whereRaw('
    //                   (m_class_big.format_kbn='. config('const.classBigFormatKbn.val.wood'). ' AND wood_unitprice.product_id IS NOT NULL)
    //                   OR (product_cost_price.product_id IS NOT NULL)
    //                 ')
    //                 ->select([
    //                     'm_product.id AS product_id',
    //                     DB::raw(config('const.costSalesKbn.cost').' AS cost_sales_kbn'),
    //                     DB::raw('
    //                         CASE
    //                           WHEN m_class_big.format_kbn='. config('const.classBigFormatKbn.val.wood'). ' AND wood_unitprice.product_id IS NOT NULL
    //                             THEN wood_unitprice.unitprice
    //                           WHEN product_cost_price.product_id IS NOT NULL
    //                             THEN product_cost_price.price
    //                         END AS price
    //                     '),
    //                     DB::raw(config('const.unitPriceKbn.normal').' AS unit_price_kbn'),
    //                 ])
    //                 ;


    //     // UNIONで取得
    //     $mainQuery = $query1->union($query2)->when($costSalesKbn !== config('const.costSalesKbn.sales'), function($query) use($query3) {
    //         // 標準の仕入単価
    //         return $query->union($query3);
    //     });

    //     $data = $mainQuery
    //             ->orderBy('product_id', 'asc')
    //             ->orderBy('unit_price_kbn', 'asc')
    //             ->get()
    //             ;

    //     return $data;
    // }

    /**
     * 商品IDで取得
     *
     * @param $productId
     * @return void
     */
    public function getByProductId($productId) 
    {
        $where = [];
        $where[] = array('m_product_campaign_price.product_id', '=', $productId);

        // costSalesKbn
        $data = $this
                ->where($where)
                ->leftJoin('m_general', function($join) {
                    $join
                        ->on('m_general.value_code', '=', 'm_product_campaign_price.cost_sales_kbn')
                        ->where('m_general.category_code', '=', config('const.general.costsales'))
                        ;
                })
                ->selectRaw('
                    m_product_campaign_price.id,
                    m_product_campaign_price.product_id,
                    m_product_campaign_price.cost_sales_kbn,
                    m_product_campaign_price.price,
                    DATE_FORMAT(m_product_campaign_price.start_date, "%Y/%m/%d") as start_date,
                    DATE_FORMAT(m_product_campaign_price.end_date, "%Y/%m/%d") as end_date,
                    DATE_FORMAT(m_product_campaign_price.update_at, "%Y/%m/%d %H:%i:%s") as update_at,
                    m_general.value_text_1 AS cost_sales_kbn_txt
                ')
                ->orderByRaw('m_product_campaign_price.start_date ASC, m_product_campaign_price.cost_sales_kbn ASC')
                ->get()
                ;

        return $data;
    }

    
     /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params) {
        $result = false;
        try {
            $result = $this->insertGetId([
                    'product_id' => $params['product_id'],
                    'cost_sales_kbn' => $params['cost_sales_kbn'],
                    'price' => $params['price'],
                    'start_date' => $params['start_date'],
                    'end_date' => $params['end_date'],
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);

        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
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

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'product_id' => $params['product_id'],
                        'cost_sales_kbn' => $params['cost_sales_kbn'],
                        'price' => $params['price'],
                        'start_date' => $params['start_date'],
                        'end_date' => $params['end_date'],
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
     * 物理削除
     * @param int $id 拠点ID
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