<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Libs\LogUtil;
use DB;


/**
 * 在庫転換
 */
class ProductstockConversion extends Model
{
    // テーブル名
    protected $table = 't_productstock_conversion';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 登録
     *
     * @param array $params
     * @return ID
     */
    public function add($params) {
        try {
            $items = [];
            $items['conversion_datetime'] = Carbon::now();
            $items['org_product_id'] = $params['org_product_id'];
            $items['org_product_code'] = $params['org_product_code'];
            $items['org_warehouse_id'] = $params['org_warehouse_id'];
            $items['org_shelf_number_id'] = $params['org_shelf_number_id'];
            $items['org_quantity'] = $params['org_quantity'];

            if (!empty($params['conv1_product_id'])) {
                $items['conv1_product_id'] = $params['conv1_product_id'];
            }
            if (!empty($params['conv1_product_code'])) {
                $items['conv1_product_code'] = $params['conv1_product_code'];
            }
            if (!empty($params['conv1_warehouse_id'])) {
                $items['conv1_warehouse_id'] = $params['conv1_warehouse_id'];
            }
            if (!empty($params['conv1_shelf_number_id'])) {
                $items['conv1_shelf_number_id'] = $params['conv1_shelf_number_id'];
            }
            if (!empty($params['conv1_quantity'])) {
                $items['conv1_quantity'] = $params['conv1_quantity'];
            }

            if (!empty($params['conv2_product_id'])) {
                $items['conv2_product_id'] = $params['conv2_product_id'];
            }
            if (!empty($params['conv2_product_code'])) {
                $items['conv2_product_code'] = $params['conv2_product_code'];
            }
            if (!empty($params['conv2_warehouse_id'])) {
                $items['conv2_warehouse_id'] = $params['conv2_warehouse_id'];
            }
            if (!empty($params['conv2_shelf_number_id'])) {
                $items['conv2_shelf_number_id'] = $params['conv2_shelf_number_id'];
            }
            if (!empty($params['conv2_quantity'])) {
                $items['conv2_quantity'] = $params['conv2_quantity'];
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
                        'conversion_datetime' => $params['conversion_datetime'],
                        'org_product_id' => $params['org_product_id'],
                        'org_product_code' => $params['org_product_code'],
                        'org_warehouse_id' => $params['org_warehouse_id'],
                        'org_shelf_number_id' => $params['org_shelf_number_id'],
                        'org_quantity' => $params['org_quantity'],
                        'conv1_product_id' => $params['conv1_product_id'],
                        'conv1_product_code' => $params['conv1_product_code'],
                        'conv1_warehouse_id' => $params['conv1_warehouse_id'],
                        'conv1_shelf_number_id' => $params['conv1_shelf_number_id'],
                        'conv1_quantity' => $params['conv1_quantity'],
                        'conv2_product_id' => $params['conv2_product_id'],
                        'conv2_product_code' => $params['conv2_product_code'],
                        'conv2_warehouse_id' => $params['conv2_warehouse_id'],
                        'conv2_shelf_number_id' => $params['conv2_shelf_number_id'],
                        'conv2_quantity' => $params['conv2_quantity'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
}
