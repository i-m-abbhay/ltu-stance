<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 呼び名マスタ
 */
class ProductNickname extends Model
{
    // テーブル名
    protected $table = 'm_product_nickname';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;


    /**
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'product_id' => $params['product_id'],
                'another_name' => $params['another_name'],
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
            return $result;
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
                        'product_id' => $params['product_id'],
                        'another_name' => $params['another_name'],
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
     * @param $id
     * @return void
     */
    public function deleteById($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

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

    /**
     * 論理削除
     * @param $id
     * @return void
     */
    public function deleteByProductId($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('product_id', $id)
                ->delete()
                ;
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * 商品IDから呼び名取得
     *
     * @param $productId
     * @return void
     */
    public function getByProductId($productId) 
    {
        $where = [];
        $where[] = array('m_product_nickname.product_id', '=', $productId);

        $data = $this
                ->where($where)
                ->leftJoin('m_staff', 'm_staff.id', '=', 'm_product_nickname.update_user')
                ->select(
                    'm_product_nickname.*',
                    'm_staff.staff_name as update_user_name'
                )
                ->get()
                ;

        return $data;
    }

    /**
     * 呼び名の存在チェック
     *
     * @param $productId
     * @return void
     */
    public function isExistNickname($productId) 
    {
        $where = [];
        $where[] = array('m_product_nickname.product_id', '=', $productId);

        $cnt = $this
                ->where($where)
                ->count()
                ;

        return $cnt > 0;
    }
}

