<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 入金明細
 */
class CreditedDetail extends Model
{
    // テーブル名
    protected $table = 't_credited_detail';
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
                'request_id' => $params['request_id'],
                'credited_id' => $params['credited_id'],
                'credited_no' => $params['credited_no'],
                'credited_date' => $params['credited_date'],
                'actual_credited date' => $params['actual_credited_date'],
                'financials_flg' => $params['financials_flg'],
                'cash' => $params['cash'],
                'credit_flg' => $params['credit_flg'],
                'cheque' => $params['cheque'],
                'receivingdept_id' => $params['receivingdept_id'],
                'transfer' => $params['transfer'],
                'transfer_charges' => $params['transfer_charges'],
                'bills' => $params['bills'],
                'bills_date' => $params['bills_date'],
                'bank_code' => $params['bank_code'],
                'bank_name' => $params['bank_name'],
                'branch_code' => $params['branch_code'],
                'branch_name' => $params['branch_name'],
                'bills_no' => $params['bills_no'],
                'endorsement_flg' => $params['endorsement_flg'],
                'sales_promotion_expenses' => $params['sales_promotion_expenses'],
                'deposits' => $params['deposits'],
                'accounts_payed' => $params['accounts_payed'],
                'discount' => $params['discount'],
                'status' => $params['status'],
                'status_dep' => $params['status_dep'],
                'discount_app_id' => $params['discount_app_id'],
                'discount_app_date' => $params['discount_app_date'],
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
                    'credited_no' => $params['credited_no'],
                    'request_id' => $params['request_id'],
                    'credited_date' => $params['credited_date'],
                    'actual_credited date' => $params['actual_credited_date'],
                    'financials_flg' => $params['financials_flg'],
                    'cash' => $params['cash'],
                    'credit_flg' => $params['credit_flg'],
                    'cheque' => $params['cheque'],
                    'receivingdept_id' => $params['receivingdept_id'],
                    'transfer' => $params['transfer'],
                    'transfer_charges' => $params['transfer_charges'],
                    'bills' => $params['bills'],
                    'bills_date' => $params['bills_date'],
                    'bank_code' => $params['bank_code'],
                    'bank_name' => $params['bank_name'],
                    'branch_code' => $params['branch_code'],
                    'branch_name' => $params['branch_name'],
                    'bills_no' => $params['bills_no'],
                    'endorsement_flg' => $params['endorsement_flg'],
                    'sales_promotion_expenses' => $params['sales_promotion_expenses'],
                    'deposits' => $params['deposits'],
                    'accounts_payed' => $params['accounts_payed'],
                    'discount' => $params['discount'],
                    'status' => $params['status'],
                    'status_dep' => $params['status_dep'],
                    'discount_app_id' => $params['discount_app_id'],
                    'discount_app_date' => $params['discount_app_date'],
                    'del_flg' => config('const.flg.off'),
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
     * 新規登録(いきなり売上)
     *
     * @param $params
     * @return $result
     */
    public function addCounterSale($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'request_id' => $params['request_id'],
                'credited_id' => $params['credited_id'],
                'credited_no' => $params['credited_no'],
                'credited_date' => Carbon::now(),
                'actual_credited date' => Carbon::now(),
                'financials_flg' => $params['financials_flg'],
                'cash' => $params['cash'],
                'credit_flg' => $params['credit_flg'],
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
     * 請求書情報を取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceData($customer_id, $request_s_day, $request_e_day)
    {
        // Where句作成
        $where = [];
        $where[] = array('customer_id', '=', $customer_id);
        // $where[] = array('status_confirm', '>=', $request_s_day . ' 00:00:00');
        // $where[] = array('status_confirm', '<=', $request_e_day . ' 23:59:59');
        $where[] = array('cd.credited_date', '>=', $request_s_day . ' 00:00:00');
        $where[] = array('cd.credited_date', '<=', $request_e_day . ' 23:59:59');
        $where[] = array('cr.del_flg', '=', config('const.flg.off'));
        $statusList = [config('const.creditedStatus.val.transferred'), config('const.creditedStatus.val.payment')];


        // データ取得
        $data = $this
            ->from('t_credited_detail  as cd')
            ->leftJoin('t_credited as cr', function ($join) {
                $join->on('cr.id', '=', 'cd.credited_id');
            })
            ->where($where)
            ->whereIn('cr.status',$statusList)
            ->groupBy('cd.credited_date', 'cd.credited_no')
            ->selectRaw(
                'cd.credited_date
                        ,cd.credited_no 
                        ,sum(cd.cash) as cash
                        ,sum(cd.cheque) as cheque
                        ,sum(cd.transfer) as transfer
                        ,sum(cd.bills) as bills
                        ,sum(cd.sales_promotion_expenses + cd.deposits + cd.accounts_payed + cd.discount + transfer_charges) as offsets'
            )
            ->get();

        return $data;
    }

    /**
     * 入金明細一覧を取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getCreditedDetailList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('cd.del_flg', '=', config('const.flg.off'));

        if (array_key_exists('credited_id', $params) && $params['credited_id'] != null) {
            $where[] = array('cd.credited_id', '=', $params['credited_id']);
        }

        // データ取得
        $data = $this
            ->from('t_credited_detail  as cd')
            ->leftJoin('t_credited as cr', function ($join) {
                $join->on('cr.id', '=', 'cd.credited_id');
            })
            ->leftJoin('m_customer as c', function ($join) {
                $join->on('c.id', '=', 'cr.customer_id');
            })
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })
            ->where($where)
            ->selectRaw(
                "cd.id
                        ,cd.credited_id
                        ,false as checked
                        ,cd.credited_no
                        ,DATE_FORMAT(cd.credited_date,'%Y/%m/%d') as credited_date
                        ,DATE_FORMAT(cd.`actual_credited date` ,'%Y/%m/%d') as actual_credited_date
                        ,cd.financials_flg
                        ,cd.cash
                        ,cd.credit_flg
                        ,cd.receivingdept_id
                        ,cd.cheque
                        ,cd.receivingdept_id
                        ,cd.transfer
                        ,cd.transfer_charges
                        ,cd.bills
                        ,DATE_FORMAT(cd.bills_date,'%Y/%m/%d') as bills_date
                        ,cd.bank_code
                        ,cd.branch_code
                        ,cd.bank_name
                        ,cd.branch_name
                        ,cd.bills_no
                        ,cd.endorsement_flg
                        ,cd.sales_promotion_expenses
                        ,cd.deposits
                        ,cd.accounts_payed
                        ,cd.discount
                        ,cd.discount as before_discount
                        ,cd.status
                        ,cd.discount_app_id
                        ,cd.discount_app_date
                        ,cd.status_dep
                        ,cd.discount_auth_id
                        ,cd.discount_auth_date
                        ,cd.receipt_day
                        ,cd.receipt_user
                        ,cr.request_no
                        ,cr.customer_id
                        ,cr.collection_kbn as collection_kbn_code
                        ,CONCAT(COALESCE(c_general.value_text_2, \"\"), COALESCE(c.customer_name, \"\"), COALESCE(c_general.value_text_3, \"\")) as customer_name
                    "
            )
            ->orderBy('cd.credited_id')
            ->get();

        return $data;
    }

     /**
     * 入金明細一覧を取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getCreditedDetailListByIds($ids)
    {
        // Where句作成
        $where = [];
        $where[] = array('cd.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('t_credited_detail  as cd')
            ->leftJoin('t_credited as cr', function ($join) {
                $join->on('cr.id', '=', 'cd.credited_id');
            })
            ->leftJoin('m_customer as c', function ($join) {
                $join->on('c.id', '=', 'cr.customer_id');
            })
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })
            ->where($where)
            ->whereIn('cd.id',$ids)
            ->selectRaw(
                "cd.id
                        ,cd.credited_id
                        ,false as checked
                        ,cd.credited_no
                        ,DATE_FORMAT(cd.credited_date,'%Y/%m/%d') as credited_date
                        ,DATE_FORMAT(cd.`actual_credited date` ,'%Y/%m/%d') as actual_credited_date
                        ,cd.financials_flg
                        ,cd.cash
                        ,cd.credit_flg
                        ,cd.receivingdept_id
                        ,cd.cheque
                        ,cd.receivingdept_id
                        ,cd.transfer
                        ,cd.transfer_charges
                        ,cd.bills
                        ,DATE_FORMAT(cd.bills_date,'%Y-%m-%d') as bills_date
                        ,cd.bank_code
                        ,cd.branch_code
                        ,cd.bank_name
                        ,cd.branch_name
                        ,cd.bills_no
                        ,cd.endorsement_flg
                        ,cd.sales_promotion_expenses
                        ,cd.deposits
                        ,cd.accounts_payed
                        ,cd.discount
                        ,cd.discount as before_discount
                        ,cd.status
                        ,cd.discount_app_id
                        ,cd.discount_app_date
                        ,cd.status_dep
                        ,cd.discount_auth_id
                        ,cd.discount_auth_date
                        ,cd.receipt_day
                        ,cd.receipt_user
                        ,cr.request_no
                        ,cr.collection_kbn as collection_kbn_code
                        ,CONCAT(COALESCE(c_general.value_text_2, \"\"), COALESCE(c.customer_name, \"\"), COALESCE(c_general.value_text_3, \"\")) as customer_name
                    "
            )
            ->orderBy('cd.credited_id')
            ->get();

        return $data;
    }

    /**
     * 値引申請情報更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateDiscountById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'discount' => $params['discount'],
                    'status' => $params['status'],
                    'status_dep' => $params['status_dep'],
                    'discount_auth_id' => $params['discount_auth_id'],
                    'discount_auth_date' => $params['discount_auth_date'],
                    'del_flg' => config('const.flg.off'),
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
     * 新規登録(CSV紐づけ)
     *
     * @param $params
     * @return $result
     */
    public function addCorrelate($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'request_id' => $params['request_id'],
                'credited_id' => $params['credited_id'],
                'credited_date' => $params['credited_date'],
                'actual_credited date' => $params['actual_credited_date'],
                'financials_flg' => $params['financials_flg'],
                'transfer' => $params['transfer'],
                'transfer_charges' => $params['transfer_charges'],
                'status' => $params['status'],
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
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 入金明細を取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getlListByCreditedId($creditedId)
    {
        // Where句作成
        $where = [];
        $where[] = array('cd.del_flg', '=', config('const.flg.off'));
        $where[] = array('cd.credited_id', '=', $creditedId);

        // データ取得
        $data = $this
            ->from('t_credited_detail  as cd')
            ->where($where)
            ->selectRaw("*")
            ->get();

        return $data;
    }

    /**
     * 入金IDから削除
     *
     * @param [type] $creditedId
     * @return void
     */
    public function deleteByCreditedId($creditedId)
    {
        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('credited_id', '=', $creditedId);

            $cnt = $this->where($where)
                ->count();

            if($cnt >= 1){
                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                $LogUtil->putByWhere($this->table, $where, config('const.logKbn.soft_delete'));

                // 削除
                $deleteCnt = $this
                    ->where($where)
                    ->update([
                        'del_flg' => config('const.flg.on'),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $deleteCnt;
    }
}
