<?php

namespace App\Models;

use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;
use DB;

/**
 * 入金
 */
class Credited extends Model
{
    // テーブル名
    protected $table = 't_credited';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'request_id',
        'request_no',
        'customer_id',
        'customer_name',
        'charge_department_id',
        'charge_department_name',
        'charge_staff_id',
        'charge_staff_name',
        'customer_closing_day',
        'expecteddeposit_at',
        'closing_day',
        'collection_sight',
        'collection_day',
        'collection_kbn',
        'bill_min_price',
        'bill_rate',
        'fee_kbn',
        'tax_calc_kbn',
        'tax_rounding',
        'offset_supplier_id',
        'bill_sight',
        'receivable',
        'request_different_amount',
        'carryforward_amount',
        'total_sales',
        'request_amount',
        'cash',
        'cheque',
        'transfer',
        'bills',
        'total_deposit',
        'offset_ets',
        'different_amount',
        'be_different_amount',
        'different_amount_request_id',
        'status',
        'status_confirm',
        'miscalculation_dep',
        'miscalculation_status',
        'miscalculation_app_com',
        'miscalculation_app_info',
        'miscalculation_app_id',
        'miscalculation_app_date',
        'miscalculation_auth_com',
        'miscalculation_auth_info',
        'miscalculation_auth_id',
        'miscalculation_auth_date',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    /**
     * 論理削除
     * @param $request_id
     * @return void
     */
    public function deleteByRequestId($requrstId)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $requrstId, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('request_id', '=', $requrstId)
                ->where('status', '=', config('const.flg.off'))
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
     * 論理削除
     * 紐付く明細も削除
     * @param $request_id
     * @return void
     */
    public function deleteWithDetailByRequestId($requestId)
    {
        $CreditedDetail = new CreditedDetail();
        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('request_id', '=', $requestId);

            $deleteDataList = $this
                ->where($where)
                ->get();

            if($deleteDataList->count() >= 1){

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                foreach ($deleteDataList as $deleteData) {
                    $LogUtil->putByData($deleteData, config('const.logKbn.soft_delete'), $this->table);
                }

                $deleteCnt = $this
                    ->where($where)
                    ->update([
                        'del_flg' => config('const.flg.on'),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);

                if ($deleteCnt >= 1) {
                    // 売上明細の削除
                    foreach ($deleteDataList as $record) {
                        $CreditedDetail->deleteByCreditedId($record->id);
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $deleteCnt;
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
                'request_no' => $params['request_no'],
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_department_name' => $params['charge_department_name'],
                'charge_staff_id' => $params['charge_staff_id'],
                'charge_staff_name' => $params['charge_staff_name'],
                'total_sales' => $params['total_sales'],
                'request_amount' => $params['request_amount'],
                'cash' => $params['cash'],
                'status' => $params['status'],
                'expecteddeposit_at' => Carbon::now(),
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
     * 締め月の売掛金取得
     * 
     * @return 
     */
    public function getReceivable($params)
    {
        $where = [];
        $where[] = array('customer_id', '=', $params['customer_id']);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->whereIn('status', [0, 1])
            ->groupBy('customer_id')
            ->selectRaw("
                            sum(total_sales) as total_sales
                        ")
            ->first();

        return $data;
    }

    /**
     * 締め月の手形取得
     * 
     * @return 
     */
    public function getBill($params)
    {
        $where = [];
        $where[] = array('c.customer_id', '=', $params['customer_id']);
        $where[] = array('cd.bills', '<>', "");
        $where[] = array('c.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_credited as c')
            ->leftJoin('t_credited_detail as cd', function ($join) {
                $join->on('c.id', '=', 'cd.credited_id')
                    ->where('cd.del_flg', '=', config('const.flg.off'));
            })
            ->where($where)
            ->whereIn('c.status', [2, 3])
            ->whereRaw("DATE_FORMAT(cd.`bills_date`,'%Y%m') > " . $params['request_mon'])
            ->groupBy('c.customer_id')
            ->selectRaw("
                            sum(cd.bills) as bills
                        ")
            ->first();

        return $data;
    }

    /**
     * 新規登録(請求一覧)
     *
     * @param $params
     * @return $result
     */
    public function addByRequest($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'request_id' => $params['request_id'],
                'request_no' => $params['request_no'],
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_department_name' => $params['charge_department_name'],
                'charge_staff_id' => $params['charge_staff_id'],
                'charge_staff_name' => $params['charge_staff_name'],
                'customer_closing_day' => $params['customer_closing_day'],
                'expecteddeposit_at' => $params['expecteddeposit_at'],
                'closing_day' => $params['closing_day'],
                'collection_sight' => $params['collection_sight'],
                'collection_day' => $params['collection_day'],
                'collection_kbn' => $params['collection_kbn'],
                'bill_min_price' => $params['bill_min_price'],
                'bill_rate' => $params['bill_rate'],
                'fee_kbn' => $params['fee_kbn'],
                'tax_calc_kbn' => $params['tax_calc_kbn'],
                'tax_rounding' => $params['tax_rounding'],
                'offset_supplier_id' => $params['offset_supplier_id'],
                'bill_sight' => $params['bill_sight'],
                'receivable' => $params['receivable'],
                'request_different_amount' => $params['request_different_amount'],
                'different_amount' => $params['different_amount'],
                'carryforward_amount' => $params['carryforward_amount'],
                'total_sales' => $params['total_sales'],
                'request_amount' => $params['request_amount'],
                'status' => config('const.flg.off'),
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
     * 入金一覧取得
     * 
     * @return 
     */
    public function getCreditedList($params)
    {
        $where = [];
        $where[] = array('cr.del_flg', '=', config('const.flg.off'));
        if (array_key_exists('customer_id', $params) && $params['customer_id'] != null && $params['customer_id'] != 0) {
            $where[] = array('c.id', '=', $params['customer_id']);
        }
        if (array_key_exists('department_id', $params) && $params['department_id'] != null && $params['department_id'] != 0) {
            $where[] = array('d.id', '=', $params['department_id']);
        }
        if (array_key_exists('staff_id', $params) && $params['staff_id'] != null && $params['staff_id'] != 0) {
            $where[] = array('c.charge_staff_id', '=', $params['staff_id']);
        }

        if (array_key_exists('request_no', $params) && $params['request_no'] != null) {
            $where[] = array('cr.request_no', '=', $params['request_no']);
        }

        if (array_key_exists('request_amount_from', $params) && $params['request_amount_from'] != null && $params['request_amount_from'] != "") {
            $where[] = array('cr.request_amount', '>=', $params['request_amount_from']);
        }
        if (array_key_exists('request_amount_to', $params) && $params['request_amount_to'] != null && $params['request_amount_to'] != "") {
            $where[] = array('cr.request_amount', '<=', $params['request_amount_to']);
        }

        if (array_key_exists('deposit_month', $params) && $params['deposit_month'] != null && $params['deposit_month'] != "") {
            $where[] = array('cr.expecteddeposit_at', '>=', date('Y-m-d', strtotime('first day of ' . $params['deposit_month'])));
        }
        if (array_key_exists('deposit_month', $params) && $params['deposit_month'] != null && $params['deposit_month'] != "") {
            $where[] = array('cr.expecteddeposit_at', '<=', date('Y-m-d', strtotime('last day of ' . $params['deposit_month'])));
        }

        if (array_key_exists('credited_id', $params) && $params['credited_id'] != null && $params['credited_id'] != "") {
            $where[] = array('cr.id', '=', $params['credited_id']);
        }

        $whereRaw = ' 0=0';
        if (array_key_exists('deposit_no', $params) && $params['deposit_no'] != null && $params['deposit_no'] != "") {
            $whereRaw = $whereRaw.  ' and cr.id in (select credited_id from t_credited_detail where credited_no = \''. $params['deposit_no'] .'\')';
        }

        //入金明細サブクエリ
        $detailQuery = $this
            ->from('t_credited_detail')
            ->selectRaw('
                credited_id
                ,count(*) as count
                ,sum(cash) as cash_total
                ,sum(cheque) as cheque_total
                ,sum(transfer) as transfer_total
                ,sum(transfer_charges) as transfer_charges
                ,sum(bills) as bills_total
                ,sum(sales_promotion_expenses) as sales_promotion_expenses_total
                ,sum(deposits) as deposits_total
                ,sum(accounts_payed) as accounts_payed_total
                ,sum(discount) as discount_total
                ,max(`actual_credited date`) as new_date
                ,null as offset_others
                ,null as blance
          ')
            ->whereRaw('del_flg = 0')
            ->groupBy('credited_id')
            ->toSql();


        $data = $this
            ->from('t_credited as cr')

            ->leftJoin('m_customer as c', function ($join) {
                $join->on('c.id', '=', 'cr.customer_id');
            })
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_department as d', function ($join) {
                $join->on('d.id', '=', 'cr.charge_department_id');
            })
            ->leftjoin(DB::raw('(' . $detailQuery . ') as cd'), function ($join) {
                $join->on('cd.credited_id', '=', 'cr.id');
            })
            ->leftJoin('t_request as r', function ($join) {
                $join->on('r.id', '=', 'cr.request_id');
            })

            ->where($where)
            ->whereRaw($whereRaw)
            ->selectRaw("
                    cr.id
                    ,cr.status as status
                    ,cr.request_id as request_id
                    ,cr.request_no as request_no
                    ,cr.customer_id as customer_id
                    ,CONCAT(COALESCE(c_general.value_text_2, \"\"), COALESCE(c.customer_name, \"\"), COALESCE(c_general.value_text_3, \"\")) as customer_name
                    ,cr.collection_sight as collection_sight
                    ,cr.collection_kbn as collection_kbn
                    ,cr.collection_kbn as collection_kbn_code
                    ,cr.bill_sight as bill_sight
                    ,cr.charge_department_id
                    ,d.department_name as department_name
                    ,cr.charge_staff_id
                    ,cr.charge_staff_name as charge_staff_name
                    ,DATE_FORMAT(r.request_e_day,'%Y/%m/%d') as closing_day
                    ,r.sales_category as sales_category
                    ,DATE_FORMAT(cr.expecteddeposit_at,'%Y/%m/%d') as expecteddeposit_at
                    ,cr.carryforward_amount as carryforward_amount
                    ,r.sales
                    ,r.consumption_tax_amount
                    ,r.discount_amount
                    ,cr.total_sales as request_amount
                    ,cr.different_amount
                    ,cr.total_sales
                    ,cr.status_confirm
                    ,cr.miscalculation_dep
                    ,cr.miscalculation_status
                    ,cr.miscalculation_app_com
                    ,cr.miscalculation_app_info
                    ,cr.miscalculation_app_id
                    ,cr.miscalculation_app_date
                    ,cr.miscalculation_auth_com
                    ,cr.miscalculation_auth_info
                    ,cr.miscalculation_auth_id
                    ,cr.miscalculation_auth_date
                    ,cd.cash_total as cash_total
                    ,cd.cheque_total as cheque_total
                    ,cd.transfer_total as transfer_total
                    ,cd.bills_total as bills_total
                    ,cd.cash_total + cd.cheque_total + cd.transfer_total + cd.bills_total as credited_total
                    ,cd.sales_promotion_expenses_total + cd.deposits_total + cd.accounts_payed_total + cd.discount_total + cd.transfer_charges as offset_others

                    ,cr.total_sales
                    - ifnull(cd.cash_total + cd.cheque_total + cd.transfer_total + cd.bills_total,0)
                    -ifnull(cd.sales_promotion_expenses_total + cd.deposits_total + cd.accounts_payed_total + cd.discount_total + cd.transfer_charges,0)
                    as balance

                    ,cd.count as count
                    ,DATE_FORMAT(cd.new_date,'%Y/%m/%d') as new_date
                    ,(select status from t_credited_detail as cd where cd.credited_id = cr.id and del_flg = 0 and status = 0 limit 1) as status_unapplied
                    ,(select status from t_credited_detail as cd where cd.credited_id = cr.id and del_flg = 0 and status = 1 limit 1) as status_applying
                    ,(select status from t_credited_detail as cd where cd.credited_id = cr.id and del_flg = 0 and status = 2 limit 1) as status_approval
                        ")
            ->get();

        return $data;
    }

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
                'request_id' => $params['request_id'],
                'request_no' => $params['request_no'],
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_department_name' => $params['charge_department_name'],
                'charge_staff_id' => $params['charge_staff_id'],
                'charge_staff_name' => $params['charge_staff_name'],
                'customer_closing_day' => $params['customer_closing_day'],
                'expecteddeposit_at' => $params['expecteddeposit_at'],
                'closing_day' => $params['closing_day'],
                'collection_sight' => $params['collection_sight'],
                'collection_day' => $params['collection_day'],
                'collection_kbn' => $params['collection_kbn'],
                'bill_min_price' => $params['bill_min_price'],
                'bill_rate' => $params['bill_rate'],
                'fee_kbn' => $params['fee_kbn'],
                'tax_calc_kbn' => $params['tax_calc_kbn'],
                'tax_rounding' => $params['tax_rounding'],
                'offset_supplier_id' => $params['offset_supplier_id'],
                'bill_sight' => $params['bill_sight'],
                'receivable' => $params['receivable'],
                'different_amount' => $params['different_amount'],
                'be_different_amount' => $params['be_different_amount'],
                'carryforward_amount' => $params['carryforward_amount'],
                'total_sales' => $params['total_sales'],
                'request_amount' => $params['request_amount'],
                'cash' => $params['cash'],
                'cheque' => $params['cheque'],
                'transfer' => $params['transfer'],
                'bills' => $params['bills'],
                'total_deposit' => $params['total_deposit'],
                'offset_ets' => $params['offset_ets'],
                'status' => $params['status'],
                'status_confirm' => $params['status_confirm'],
                'miscalculation_dep' => $params['miscalculation_dep'],
                'miscalculation_status' => $params['miscalculation_status'],
                'miscalculation_app_com' => $params['miscalculation_app_com'],
                'miscalculation_app_info' => $params['miscalculation_app_info'],
                'miscalculation_auth_com' => $params['miscalculation_auth_com'],
                'miscalculation_auth_info' => $params['miscalculation_auth_info'],
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
     * 更新（入金確定）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateDepositConfirm($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'different_amount' => $params['different_amount'],
                    'cash' => $params['cash'],
                    'cheque' => $params['cheque'],
                    'transfer' => $params['transfer'],
                    'total_sales' => $params['total_sales'],
                    'bills' => $params['bills'],
                    'total_deposit' => $params['total_deposit'],
                    'request_amount' => $params['request_amount'],
                    'offset_ets' => $params['offset_ets'],
                    'status' => $params['status'],
                    'status_confirm' => $params['status_confirm'],
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
    * 更新（ステータス）
    * @param array $params 
    * @return boolean True:成功 False:失敗 
    */
   public function updateStatus($params)
   {
       $result = false;
       try {
           // ログテーブルへの書き込み
           $LogUtil = new LogUtil();
           $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

           $updateCnt = $this
               ->where('id', $params['id'])
               ->update([
                   'status' => $params['status'],
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
     * 更新（違算処理）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateMiscalculation($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'cash' => $params['cash'],
                    'cheque' => $params['cheque'],
                    'transfer' => $params['transfer'],
                    'bills' => $params['bills'],
                    'total_deposit' => $params['total_deposit'],
                    'offset_ets' => $params['offset_ets'],
                    'status' => $params['status'],
                    'different_amount' => $params['different_amount'],
                    'status_confirm' => $params['status_confirm'],
                    'miscalculation_dep' => $params['miscalculation_dep'],
                    'miscalculation_status' => $params['miscalculation_status'],
                    'miscalculation_app_com' => $params['miscalculation_app_com'],
                    'miscalculation_app_info' => $params['miscalculation_app_info'],
                    'miscalculation_app_id' => $params['miscalculation_app_id'],
                    'miscalculation_app_date' => $params['miscalculation_app_date'],
                    'miscalculation_auth_com' => $params['miscalculation_auth_com'],
                    'miscalculation_auth_info' => $params['miscalculation_auth_info'],
                    'miscalculation_auth_id' => $params['miscalculation_auth_id'],
                    'miscalculation_auth_date' => $params['miscalculation_auth_date'],
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
     * 指定期間内に確定された入金金額と相殺その他を取得
     * 入金ステータスは繰越済or入金済み
     * @param $customerId
     * @param $startDate
     * @param $endDate
     * @return $result
     */
    public function getSalesDataInPeriod($customerId, $startDate, $endDate)
    {
        $where = [];
        $where[] = array('customer_id', '=', $customerId);
        // $where[] = array('status_confirm', '>=', $startDate . ' 00:00:00');
        // $where[] = array('status_confirm', '<=', $endDate . ' 23:59:59');
        // $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('cd.credited_date', '>=', $startDate . ' 00:00:00');
        $where[] = array('cd.credited_date', '<=', $endDate . ' 23:59:59');
        $where[] = array('cr.del_flg', '=', config('const.flg.off'));
        $statusList = [config('const.creditedStatus.val.transferred'), config('const.creditedStatus.val.payment')];

        $data = $this
            ->from('t_credited_detail  as cd')
            ->leftJoin('t_credited as cr', function ($join) {
                $join->on('cr.id', '=', 'cd.credited_id');
            })
            ->where($where)
            ->whereIn('cr.status', $statusList)
            // ->groupBy('cd.credited_date', 'cd.credited_no')
            ->groupBy('customer_id')
            ->select([
                // DB::raw("SUM(total_deposit) AS total_deposit"),
                // DB::raw("SUM(offset_ets) AS offset_ets"),
                DB::raw("SUM(cd.cash + cd.cheque + cd.transfer + cd.bills) AS total_deposit"),
                DB::raw("SUM(cd.sales_promotion_expenses + cd.deposits + cd.accounts_payed + cd.discount + transfer_charges) as offset_ets"),             
            ])
            ->first();

        return $data;
    }

    /**
     * 請求に違算として適用されていない違算を取得する
     * 
     * @param $customerId  得意先ID
     * @param $differentAmountRequestId  違算適用請求ID
     * @return $result
     */
    public function getUnappliedDifferentAmountToRequest($customerId, $differentAmountRequestId = null)
    {
        $result = 0;
        
        $SystemUtil = new SystemUtil();
        $tmpRequestInfo = $SystemUtil->getStrictCurrentMonthRequestPeriod($customerId);
        $requestEDay = $tmpRequestInfo['request_e_day'];

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();

        $subWhere = [];
        $subWhere[] = array('t_request.customer_id', '=', $customerId);
        $subWhere[] = array('t_request.del_flg', '=', config('const.flg.off'));
        // $subWhere[] = array('t_request.status', '!=', config('const.requestStatus.val.unprocessed'));
        $statusList = [config('const.creditedStatus.val.transferred')];

        // 入金処理日の最新を取得
        $newestCreditedQuery = DB::table('t_credited AS c')
                ->leftJoin('t_request', 't_request.id', '=', 'c.request_id')
                ->leftJoin('t_credited_detail AS cd', function($join) {
                    $join
                        ->on('c.id', '=', 'cd.credited_id')
                        ->where('cd.del_flg', '=', config('const.flg.off'));
                })
                ->where($subWhere)
                // ->where(function ($query) {
                //     $query->orWhere('c.status', config('const.creditedStatus.val.transferred'));
                //     $query->orWhere('c.status', config('const.creditedStatus.val.payment'));
                //     $query->orWhereNull('c.id');
                // })
                ->select([
                    'c.id AS credited_id',
                    DB::raw('MAX(cd.credited_date) AS credited_date'),
                ])
                ->groupBy('c.id')
                // ->get()
                ;
                
        $where = [];
        $where[] = array('t_credited.customer_id', '=', $customerId);
        $where[] = array('t_credited.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_credited')
            ->leftJoin('t_request', 't_request.id', '=', 't_credited.request_id')
            ->leftJoin(DB::raw('('. $newestCreditedQuery->toSql() .') AS t_credited_detail'), 't_credited_detail.credited_id', '=', 't_credited.id')
            ->mergeBindings($newestCreditedQuery)
            ->where(function ($query) use($requestEDay) {
                $query->orWhere('t_credited_detail.credited_date', '<=', $requestEDay);
                $query->orWhereNull('t_credited_detail.credited_date');
            })
            ->where($where)
            ->where(function($query) {  // 請求に未適応の違算が対象
                $query->where('different_amount_request_id',0)
                      ->orWhereNull('different_amount_request_id');
            })
            ->whereIn('t_credited.status', $statusList)
            ->select([
                't_credited.*'
            ])
            ->get();

        $items = [];
        $items['different_amount_request_id'] = $differentAmountRequestId;
        $items['update_user'] = Auth::user()->id;
        $items['update_at'] = Carbon::now();
        
        foreach($data as $row){
            $result += intval(Common::nullorBlankToZero($row->different_amount));
            if(!empty($differentAmountRequestId) && Common::nullorBlankToZero($row->different_amount) != 0){
                $updateWhere = [];
                $LogUtil->putByData($row, config('const.logKbn.update'), $this->table);
                // 更新
                $items['different_amount']      = 0;
                $items['be_different_amount']   = $row->different_amount;
                $updateWhere[]                  = array('id', '=', $row->id);
                $updateCnt = $this
                    ->where($updateWhere)
                    ->update($items);
            }
        }

        return $result;
    }

    /**
     * 指定請求に適用した違算を未適用に戻す
     * @param $differentAmountRequestId  違算適用請求ID
     * @return $result
     */
    public function restoreDifferentAmountToUnapplied($differentAmountRequestId)
    {
        $result = true;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();

            $where = [];
            $where[] = array('different_amount_request_id', '=', $differentAmountRequestId);
            $where[] = array('del_flg', '=', config('const.flg.off'));
            
            $data = $this
                ->where($where)
                ->get();

            $items = [];
            $items['different_amount_request_id'] = 0;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();
            
            foreach($data as $row){
                $updateWhere = [];
                $LogUtil->putByData($row, config('const.logKbn.update'), $this->table);
                // 更新
                $items['different_amount']      = $row->be_different_amount;
                $items['be_different_amount']   = 0;
                $updateWhere[]                  = array('id', '=', $row->id);
                $updateCnt = $this
                    ->where($updateWhere)
                    ->update($items);
                   
                if($result){
                    $result = $updateCnt > 0;
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
    
     /**
     * 入金紐づけ用のデータ取得
     * @param $differentAmountRequestId  違算適用請求ID
     * @return $result
     */
    public function getCorrelate()
    {
       // Where句作成
       $where = [];
       $where[] = array('c.del_flg', '=', config('const.flg.off'));

       // データ取得
       $data = $this
           ->from('t_credited  as c')
           ->leftJoin('t_credited_correlate as cc', function ($join) {
               $join->on('cc.customer_id', '=', 'c.customer_id')
               ->where('cc.del_flg', '=', config('const.flg.off'))
               ;
           })
           
           ->where($where)
           ->whereIn('c.status',[0,1])
           ->selectRaw(
               "
               cc.customer_bank
                ,c.id as credited_id
                ,c.customer_id
                ,c.customer_name
                ,c.expecteddeposit_at
                ,c.request_id
                ,c.request_no
                ,c.total_sales
                ,c.transfer
                ,0 as radio_button
                ,c.created_at
                "
           )
           ->orderBy('cc.customer_bank')
           ->orderBy('c.created_at','desc')
           ->get();

       return $data;
    }

     /**
     * 入金紐づけ用のデータ取得
     * @param $differentAmountRequestId  違算適用請求ID
     * @return $result
     */
    public function getCorrelateCustomer()
    {
       // Where句作成
       $where = [];
       $where[] = array('c.del_flg', '=', config('const.flg.off'));

       // データ取得
       $data = $this
           ->from('t_credited  as c')
          
           
           ->where($where)
           ->whereIn('c.status',[0,1])
           ->selectRaw(
               "
                c.id as credited_id
                ,c.customer_id
                ,c.customer_name
                ,c.expecteddeposit_at
                ,c.request_id
                ,c.request_no
                ,c.total_sales
                ,c.transfer
                ,0 as radio_button
                "
           )
           ->orderBy('c.customer_name')
           ->distinct()
           ->get();

       return $data;
    }


    /**
     * 入金済みであるかどうか
     * @param $id
     * @return $result
     */
    public function isCredited($id)
    {
        $where = [];
        $where[] = array('c.id', '=', $id);
        $where[] = array('c.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_credited as c')
            ->leftJoin('t_credited_detail as cd', function ($join) {
                $join->on('cd.credited_id', '=', 'c.id')
                ->where('cd.status', '=', 1)
                ;
            })
            ->where($where)
            ->Where(function ($query) {
                $query->where('cd.status', 1)
                      ->orWhereColumn('c.request_amount', '<>', 'c.total_deposit');
            })
            ->selectRaw("*")
            ->get();

        if($data != null && count($data) > 0){
            return false;
        }else{
            return true;
        }

    }

    /**
     * 更新（値引完了）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateDiscountFinish($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'status' => 3,
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
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
     
    /**
     * 更新（未入金のサラ状態に）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function clearById($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'status' => 0,
                    'cash' => 0,
                    'cheque' => 0,
                    'transfer' => 0,
                    'bills' => 0,
                    'total_deposit' => 0,
                    'offset_ets' => 0,
                    'different_amount' => 0,
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
    * 更新
    * 繰越済　→　違算有
    * @param array $params 
    * @return boolean True:成功 False:失敗 
    */
   public function updateMiscalCancel($params)
   {
       $result = false;
       try {
           // ログテーブルへの書き込み
           $LogUtil = new LogUtil();
           $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));	

           $updateCnt = $this
               ->where('id', $params['id'])
               ->update([
                   'status' => $params['status'],
                   'different_amount' => $params['different_amount'],
                   'miscalculation_dep' => $params['miscalculation_dep'],
                   'miscalculation_status' => $params['miscalculation_status'],
                   'miscalculation_app_com' => $params['miscalculation_app_com'],
                   'miscalculation_app_info' => $params['miscalculation_app_info'],
                   'miscalculation_app_id' => $params['miscalculation_app_id'],
                   'miscalculation_app_date' => $params['miscalculation_app_date'],
                   'miscalculation_auth_com' => $params['miscalculation_auth_com'],
                   'miscalculation_auth_info' => $params['miscalculation_auth_info'],
                   'miscalculation_auth_id' => $params['miscalculation_auth_id'],
                   'miscalculation_auth_date' => $params['miscalculation_auth_date'],
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
     * 更新
     * @param int $id
     * @param array $params 1行の配列
     * @return void
     */
    public function updateById($id, $params)
    {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $id);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

            // 更新内容
            $items = [];
            $registerCol = $this->getFillable();
            foreach ($registerCol as $colName) {
                if (array_key_exists($colName, $params)) {
                    switch ($colName) {
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user']   = Auth::user()->id;
            $items['update_at']     = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where($where)
                ->update($items);

            $result = ($updateCnt == 1);
            // 登録
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 入金済みかどうか
     *
     * @param [type] $requestId
     * @return boolean true: 入金済み　false: 未入金
     */
    public function isRequestDeposited($requestId) 
    {
        $where = [];
        $where[] = array('t_credited.request_id', '=', $requestId);
        $where[] = array('t_credited.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_credited_detail.del_flg', '=', config('const.flg.off'));
        // $where[] = array('status', '<>', config('const.creditedStatus.val.unsettled'));

        $cnt = $this
                ->leftJoin('t_credited_detail', 't_credited_detail.credited_id', '=', 't_credited.id')
                ->where($where)
                ->count()
                ;
        
        return $cnt > 0;
    }

    /**
     * 未入金チェック
     * 入金予定日が売上期間終了日よりも前、ステータスが「0：未入金」
     *
     * @param [type] $customerId
     * @param [type] $requestEDay
     * @return boolean
     */
    public function isUnsettledByCustomerId($customerId, $requestEDay)
    {
        $where = [];
        $where[] = array('customer_id', '=', $customerId);
        $where[] = array('status', '=', config('const.creditedStatus.val.unsettled'));
        $where[] = array('expecteddeposit_at', '<=', $requestEDay);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $cnt = $this
            ->where($where)
            ->count()
            ;

        return $cnt > 0;
    }

     /**
     * 違算チェック
     * 入金予定日が売上期間終了日よりも前、ステータスが「1：違算有」もしくは「4：値引申請」
     *
     * @param [type] $customerId
     * @param [type] $requestEDay
     * @return boolean
     */
    public function isNotFinishByCustomerId($customerId, $requestEDay)
    {
        $where = [];
        $where[] = array('customer_id', '=', $customerId);
        $where[] = array('expecteddeposit_at', '<=', $requestEDay);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $cnt = $this
            ->where($where)
            ->where(function ($query) {
                $query->where('status', config('const.creditedStatus.val.miscalculation'));
                $query->orWhere('status', config('const.creditedStatus.val.discount'));
            })
            ->count()
            ;

        return $cnt > 0;
    }

}
