<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * メーカー返品
 */
class ReturnMaker extends Model
{
    // テーブル名
    protected $table = 't_return_maker';
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
                    'return_id' => $params['return_id'],
                    'qr_code' => $params['qr_code'],
                    'stock_flg' => $params['stock_flg'],
                    'product_id' => $params['product_id'],
                    'product_code' => $params['product_code'],
                    'matter_id' => $params['matter_id'],
                    'customer_id' => $params['customer_id'],
                    'supplier_id' => $params['supplier_id'],
                    'quantity' => $params['quantity'],
                    'maker_return_date' => $params['maker_return_date'],
                    'product_move_id' => $params['product_move_id'],
                    'cancel_kbn' => $params['cancel_kbn'],
                    'cancel_product_move_id' => $params['cancel_product_move_id'],
                    'del_flg' => config('const.flg.off'),
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
     * 返品IDからキャンセル
     *
     * @param  $returnId
     * @return void
     */
    public function cancelByReturnId($returnId)
    {
        $result = false;
        try {            
            $updateCnt = $this
                ->where('return_id', $returnId)
                ->update([
                    'cancel_kbn' => config('const.flg.on'),
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
