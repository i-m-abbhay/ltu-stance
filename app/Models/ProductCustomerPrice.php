<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;
use Illuminate\Support\Collection;


 /**
 * 得意先別商品単価
 */
class ProductCustomerPrice extends Model
{
    // テーブル名
    protected $table = 't_product_customer_price';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    /**
     * 保存
     *
     * @param  $params
     * @return True: OK False: NG
     */
    public function add($params)
    {
        $result = false;
        $userId = Auth::user()->id;
        $date = Carbon::now();

        try{
            $result = $this->insertGetId([
                'customer_id' => $params['customer_id'],
                'product_id' => $params['product_id'],
                'cost_sales_kbn' => $params['cost_sales_kbn'],
                'price' => $params['price'],
                'quote_detail_id' => $params['quote_detail_id'],
                'created_user' => $userId,
                'created_at' => $date,
                'update_user' => $userId,
                'update_at' => $date,
            ]);

            return $result;
        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 物理削除
     * @param $where
     * @return void
     */
    public function deleteByWhere($where)
    {
        // $result = false;
        try{
            $deleteTargetCnt = $this
                ->where($where)
                ->count()
                ;
            
            if ($deleteTargetCnt > 0) {
                $LogUtil = new LogUtil();
                $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

                $deleteCnt = $this
                    ->where($where)
                    ->delete()
                    ;
            }
                
            // $result = ($deleteCnt >= 1);
        } catch(\Exception $e) {
            throw $e;
        }
        // return $result;
    }

    /**
     * 物理削除[配列]
     *
     * @param [type] $id 発注明細IDの配列
     * @return void
     */
    public function deleteListByIDs($ids){
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($ids as $id) {
                $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));
            }

            $deleteCnt = $this
                ->whereIn('id', $ids)
                ->delete();
            $result = ($deleteCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

}