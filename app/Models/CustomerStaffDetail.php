<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;

/**
 * 得意先担当者詳細
 */
class CustomerStaffDetail extends Model
{
    // テーブル名
    protected $table = 't_customer_staff_detail';
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

        try{
            $result = $this->insertGetId([
                'customer_staff_id' => $params['customer_staff_id'],
                'customer_id' => $params['customer_id'],
                'from_department_id' => $params['from_department_id'],
                'from_staff_id' => $params['from_staff_id'],
                'to_department_id' => $params['to_department_id'],
                'to_staff_id' => $params['to_staff_id'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);


            return $result;
        }catch (\Exception $e){
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
            $items = [];
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            if (!empty($params['detail_id'])) {
                $LogUtil->putById($this->table, $params['detail_id'], config('const.logKbn.update'));
            } else {
                $items['created_user'] = Auth::user()->id;
                $items['created_at'] = Carbon::now();
                $items['del_flg'] = config('const.flg.off');
            }
            $items['customer_staff_id'] = $params['customer_staff_id'];
            $items['customer_id'] = $params['customer_id'];
            $items['from_department_id'] = $params['from_department_id'];
            $items['from_staff_id'] = $params['from_staff_id'];
            $items['to_department_id'] = $params['to_department_id'];
            $items['to_staff_id'] = $params['to_staff_id'];
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $result = $this
                    ->updateOrInsert(
                        ['customer_staff_id' => $params['customer_staff_id'], 'customer_id' => $params['customer_id']],
                        $items
                    );

            $result = true;
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
        try{
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
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param $id
     * @return void
     */
    public function physicalDeleteById($id)
    {
        $result = false;
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->delete();
            $result = ($updateCnt > 0);
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 削除
     * @param $id
     * @return void
     */
    public function deleteByHeaderId($headerId)
    {
        $result = false;
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $headerId, config('const.logKbn.delete'));

            $result = $this
                ->where('customer_staff_id', $headerId)
                ->delete()
                ;
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 得意先担当者変更IDから取得
     *
     * @param  $headerId
     * @return void
     */
    public function getByHeaderId($headerId)
    {
        $where = [];
        $where[] = array('customer_staff_id', '=', $headerId);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->get()
                ;

        return $data;
    }
}