<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;

/**
 * 拠点別発注点管理
 */
class OrderLimit extends Model
{
    // テーブル名
    protected $table = 't_order_limit';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params)
    {
        $result = false;
        try {
            $result = $this->insertGetId([
                'product_id'  => $params['product_id'],
                'base_id'  => $params['base_id'],
                'order_limit' => $params['order_limit'],
                'del_flg'       => config('const.flg.off'),
                'created_user'  => Auth::user()->id,
                'created_at'    => Carbon::now(),
                'update_user'   => Auth::user()->id,
                'update_at'     => Carbon::now(),
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
    public function updateById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'order_limit' => $params['order_limit'],
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
     * @param $id
     * @return void
     */
    public function deleteById($id)
    {
        $result = false;

        try {
            // Where句
            $where[] = array('id', '=', $id);
            $updateCnt = $this
                ->where($where)
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
}
