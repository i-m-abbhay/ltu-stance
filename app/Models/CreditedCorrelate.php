<?php

namespace App\Models;

use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 入金紐づけ
 */
class CreditedCorrelate extends Model
{
    // テーブル名
    protected $table = 't_credited_correlate';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    /**
     * 論理削除
     * @param $customer_bank
     * @return void
     */
    public function deleteByBank($customer_bank)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $customer_bank, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('customer_bank', '=', $customer_bank)
                ->where('del_flg', '=', config('const.flg.off'))
                ->update([
                    'del_flg' => config('const.flg.on'),
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
     * 新規登録
     *
     * @param $params
     * @return $result
     */
    public function add($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'customer_bank' => $params['customer_bank'],
                'del_flg' => config('const.flg.off'),
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
     * 取得
     * @param
     * @return type 
     */
    public function getData($params)
    {
        $where = [];
        $where[] = array('t_credited_correlate.customer_id', '=', $params['customer_id']);
        if (array_key_exists('customer_bank', $params)) {
            $where[] = array('t_credited_correlate.customer_bank', '=', $params['customer_bank']);
        }
        $where[] = array('t_credited_correlate.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }
}
