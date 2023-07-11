<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 商品仕入単価
 */
class ProductCostPrice extends Model
{
    // テーブル名
    protected $table = 't_product_cost_price';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

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
                    'price' => $params['price'],
                    'arrival_id' => $params['arrival_id'],
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
}
