<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;


 /**
 * 商品単価マスタ
 */
class ProductPrice extends Model
{
    // テーブル名
    protected $table = 'm_product_price';
    // タイムスタンプ自動更新Off
    public $timestamps = false;


    /**
     * コンボボックス用データ取得
     * 
     * @param $customerId 得意先ID
     * @param $costSalesKbn 仕入販売区分 const.costSalesKbn
     * @return 
     */
    public function getComboList($customerId = null, $costSalesKbn = null) {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $subQuery = $this
                ->selectRaw('MAX(id) as tmp_id')
                ->whereraw('del_flg = '.config('const.flg.off'))
                ;
        
        if(!empty($customerId)){
            $subQuery->whereraw('customer_id = '.$customerId);
        }
        if(!empty($costSalesKbn)){
            $subQuery->whereraw('cost_sales_kbn = '.$costSalesKbn);
        }
        $sql = $subQuery->groupBy('customer_id', 'product_id', 'unit_price_kbn', 'cost_sales_kbn')->toSql();

        $data = $this
                ->join(DB::raw('('.$sql.') as tmp'), function($join){
                    $join->on('m_product_price.id', 'tmp.tmp_id');
                })
                ->select([
                    'm_product_price.*'
                ])
                ->where($where)
                ->get()
                ;

        return $data;
    }
  
    /**
     * 得意先ID,商品ID,単価区分,仕入販売区分で一意となるリストを取得
     * ※ユニークにならない場合に備えて、最新（idが最大）レコードを取得
     * 
     * @param $customerId 得意先ID
     * @param $costSalesKbn 仕入販売区分 const.costSalesKbn
     * @return 
     */
    public function getList($customerId, $costSalesKbn = null) {
        // 単価区分：キャンペーン特価の取得（得意先IDなし）
        $where1 = [];
        $where1[] = array('del_flg', '=', config('const.flg.off'));
        $where1[] = array('unit_price_kbn', '=', config('const.unitPriceKbn.campaign'));
        if (!is_null($costSalesKbn)) {
            $where1[] = array('cost_sales_kbn', '=', $costSalesKbn);
        }

        $subQuery1 = DB::table('m_product_price') 
                ->select([DB::raw('MAX(id) as id')])
                ->where($where1)
                ->groupBy('product_id', 'unit_price_kbn', 'cost_sales_kbn')
                ;

        $query1 = $this
                ->from('m_product_price AS main1')  
                ->join(DB::raw('('.$subQuery1->toSql().') as sub1'), function($join){
                    $join->on('main1.id', 'sub1.id');
                })
                ->mergeBindings($subQuery1)
                ->where($where1)
                ->select([
                    'main1.*'
                ])
                ;

        // 単価区分：キャンペーン特価以外の取得（得意先ID別）
        $where2 = [];
        $where2[] = array('del_flg', '=', config('const.flg.off'));
        $where2[] = array('unit_price_kbn', '<>', config('const.unitPriceKbn.campaign'));
        $where2[] = array('customer_id', '=', $customerId);
        if (!is_null($costSalesKbn)) {
            $where2[] = array('cost_sales_kbn', '=', $costSalesKbn);
        }
        
        $subQuery2 = DB::table('m_product_price') 
                ->select([DB::raw('MAX(id) as id')])
                ->where($where2)
                ->groupBy('customer_id', 'product_id', 'unit_price_kbn', 'cost_sales_kbn')
                ;
                
        $query2 = $this
                ->from('m_product_price AS main2') 
                ->join(DB::raw('('.$subQuery2->toSql().') as sub2'), function($join){
                    $join->on('main2.id', 'sub2.id');
                })
                ->mergeBindings($subQuery2)
                ->where($where2)
                ->select([
                    'main2.*'
                ])
                ;

        // UNIONで取得
        $mainQuery = $query1->union($query2);
        $data = $mainQuery
                ->orderBy('product_id', 'asc')
                ->orderBy('unit_price_kbn', 'asc')
                ->get()
                ;

        return $data;
    }

}