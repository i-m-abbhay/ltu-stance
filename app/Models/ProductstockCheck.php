<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Libs\LogUtil;
use DB;


/**
 * 棚番在庫マスタ
 */
class ProductstockCheck extends Model
{
    // テーブル名
    protected $table = 't_productstock_check';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'stock_flg',
        'qr_code',
        'product_id',
        'product_code',
        'warehouse_id',
        'shelf_number_id',
        'matter_id',
        'customer_id',
        'quantity_logic',
        'quantity_real',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
    ];

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'stock_flg' => $params['stock_flg'],
                'qr_code' => $params['qr_code'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'warehouse_id' => $params['warehouse_id'],
                'shelf_number_id' => $params['shelf_number_id'],
                'matter_id' => $params['matter_id'],
                'customer_id' => $params['customer_id'],
                'quantity_logic' => $params['quantity_logic'],
                'quantity_real' => $params['quantity_real'],
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
}
