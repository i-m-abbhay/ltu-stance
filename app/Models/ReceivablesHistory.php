<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 債権履歴
 */
class ReceivablesHistory extends Model
{
    // テーブル名
    protected $table = 't_receivables_history';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

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
                'request_mon' => $params['request_mon'],
                'offset_amount' => $params['offset_amount'],
                'deposit_amount' => $params['deposit_amount'],
                'sales_total' => $params['sales_total'],
                'receivable' => $params['receivable'],
                'bills' => $params['bills'],
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
     * 論理削除
     * @param $request_id
     * @return void
     */
    public function deleteByCustomerId($params)
    {
        $result = false;
        try {
            $updateCnt = $this
                ->where('customer_id','=', $params['customer_id'])
                ->where('request_mon', '=', $params['request_mon'])
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
}
