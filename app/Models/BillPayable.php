<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 手形支払明細
 */
class BillPayable extends Model
{
    // テーブル名
    protected $table = 't_bill_payable';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;


    /**
     * 登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'payment_id' => $params['payment_id'],
                'bills' => $params['bills'],
                'type' => $params['type'],
                'bank_code' => $params['bank_code'],
                'branch_code' => $params['branch_code'],
                'bill_of_exchange_due' => $params['bill_of_exchange_due'],
                'endorseed_bill' => $params['endorseed_bill'],
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
                        'payment_id' => $params['payment_id'],
                        'bills' => $params['bills'],
                        'type' => $params['type'],
                        'bank_code' => $params['bank_code'],
                        'branch_code' => $params['branch_code'],
                        'bill_of_exchange_due' => $params['bill_of_exchange_due'],
                        'endorseed_bill' => $params['endorseed_bill'],
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
     * @param int $id ID
     * @return boolean True:成功 False:失敗 
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
     * 支払予定一覧　表示項目取得
     *
     * @param [type] $paymentId
     * @return void
     */
    public function getPaymentListInfoByPaymentId($paymentId)
    {
        $where = [];
        $where[] = array('bp.del_flg', '=', config('const.flg.off'));
        $where[] = array('bp.payment_id', '=', $paymentId);

        $data = $this
                ->from('t_bill_payable AS bp')
                ->whereRaw('
                    bp.del_flg ='. config('const.flg.off').'
                ')
                ->leftJoin('m_bank AS bank', 'bank.bank_code', '=', 'bp.bank_code')
                ->selectRaw('
                    bp.id,
                    bp.payment_id,
                    DATE_FORMAT(bp.bill_of_exchange_due, "%Y/%m/%d") AS bill_of_exchange_due,
                    bank.bank_name,
                    bp.type,
                    bp.endorseed_bill
                ')
                ->where($where)
                ->distinct()
                ->orderBy('bill_of_exchange_due')
                ->get()
                ;

        return $data;
    }
}