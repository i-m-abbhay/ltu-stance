<?php

namespace App\Models;

use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;


 /**
 * 支払予定
 */
class Payment extends Model
{
    // テーブル名
    protected $table = 't_payment';
    // タイムスタンプ自動更新Off
    public $timestamps = false;


    /**
     * 仕入先IDから取得
     * 仕入詳細画面ヘッダー部　請求合計
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getPeriodBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('pay.del_flg', '=', config('const.flg.off'));
        $where[] = array('pay.supplier_id', '=', $supplier_id);

        $data = $this
                ->from('t_payment AS pay')
                ->where($where)
                ->selectRaw('
                    pay.id,
                    pay.status,
                    pay.supplier_id,
                    pay.supplier_name,
                    pay.closing_day,
                    pay.payment_mon,
                    pay.payment_sight,
                    DATE_FORMAT(pay.purchase_closing_day, "%Y/%m/%d") as purchase_closing_day,
                    DATE_FORMAT(pay.payment_period_sday, "%Y/%m/%d") as payment_period_sday,
                    DATE_FORMAT(pay.payment_period_eday, "%Y/%m/%d") as payment_period_eday,
                    DATE_FORMAT(pay.planned_payment_date, "%Y/%m/%d") as planned_payment_date
                ')
                ->orderBy('pay.payment_mon', 'desc')
                ->first()
                ;

        return $data;
    }

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
                'payment_no' => $params['payment_no'],
                'supplier_id' => $params['supplier_id'],
                'supplier_name' => $params['supplier_name'],
                'payment_mon' => $params['payment_mon'],
                'closing_day' => $params['closing_day'],
                'payment_sight' => $params['payment_sight'],
                'purchase_closing_day' => $params['purchase_closing_day'],
                'payment_period_sday' => $params['payment_period_sday'],
                'payment_period_eday' => $params['payment_period_eday'],
                'requested_amount' => $params['requested_amount'],
                'rebate' => $params['rebate'],
                'safety_fee' => $params['safety_fee'],
                'safety_fee_type' => $params['safety_fee_type'],
                'offset' => $params['offset'],
                'paid_rebate' => $params['paid_rebate'],
                'cash' => $params['cash'],
                'cash_type' => $params['cash_type'],
                'check' => $params['check'],
                'transfer' => $params['transfer'],
                'fee' => $params['fee'],
                'bills' => $params['bills'],
                'issuance_fee' => $params['issuance_fee'],
                'planned_payment_date' => $params['planned_payment_date'],
                'status' => $params['status'],
                'confirmed_staff_id' => $params['confirmed_staff_id'],
                'fixed_date' => $params['fixed_date'],
                'request_payment_id' => $params['request_payment_id'],
                'membershipfee_flg' => $params['membershipfee_flg'],
                'offset_flg' => $params['offset_flg'],
                'cash_flg' => $params['cash_flg'],
                'transfer_flg' => $params['transfer_flg'],
                'bills_flg' => $params['bills_flg'],
                'electricitydebt_flg' => $params['electricitydebt_flg'],
                'transmittal_flg' => $params['transmittal_flg'],
                'accounting_flg' => $params['accounting_flg'],
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

            if(!isset($params['update_user']) || empty($params['update_user'])){
                $params['update_user'] = Auth::user()->id;
            }
            if(!isset($params['update_at']) || empty($params['update_at'])){
                $params['update_at'] = Carbon::now();
            }

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($params)
                    ;
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * 仕入先IDで取得
     *
     * @param [type] $supplier_id
     * @param [type] $paymentMon YYYYMM
     * @return boolean
     */
    public function getPaymentBySupplierIdAndMonth($supplier_id, $paymentMon)
    {
        $where = [];
        $where[] = array('supplier_id', '=', $supplier_id);
        $where[] = array('payment_mon', '=', $paymentMon);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }


    /**
     * 支払予定IDから取得
     *
     * @param [type] $id
     * @return void
     */
    public function getById($id) 
    {
        $where = [];
        $where[] = array('id', '=', $id);

        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 仕入先IDから取得
     * 「締め済」以外の支払予定が存在するか
     *
     * @param [type] $supplierId
     * @return void
     */
    public function isExistNotClosingPayment($supplierId)
    {
        $where = [];
        $where[] = array('supplier_id', '=', $supplierId);
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('status', '<>', config('const.paymentStatus.val.closing'));

        $data = $this
                ->where($where)
                ->get()
                ;

        return (count($data) > 0);
    }


    /**
     * 支払予定一覧取得
     *
     * @param [type] $params
     * @return void
     */
    public function getPaymentList($params) 
    {
        $where = [];
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));
        // $where[] = array('pay.status', '=', config('const.paymentStatus.val.unsettled'));
        $where[] = array('pay.del_flg', '=', config('const.flg.off'));

        if (!empty($params['supplier_id'])){
            $where[] = array('pay.supplier_id', '=', $params['supplier_id']);
        }
        if (!empty($params['payment_date'])) {
            $Ym = str_replace('/', '', $params['payment_date']);
            $where[] = array('pay.payment_mon', '=', $Ym);
        }

        $amountSql = DB::table('t_purchase_line_item AS pl')
                ->selectRaw('
                    pl.payment_id,
                    SUM(pl.fix_cost_total) AS fix_cost_total
                ')
                ->groupBy('pl.payment_id')
                ->toSql()
                ;

        $rebateSql = $this
                    ->from('t_purchase_line_item AS pl')
                    ->whereRaw('
                        pl.del_flg ='. config('const.flg.off').'
                    ')
                    ->whereNotNull('pl.purchase_type')
                    ->selectRaw('
                        pl.payment_id,
                        SUM(pl.fix_cost_total) AS rebate_detail
                    ')
                    ->groupBy('pl.payment_id')
                    ->toSql()
                    ;

        $billsSql = $this
                    ->from('t_bill_payable AS bp')
                    ->whereRaw('
                        bp.del_flg ='. config('const.flg.off').'
                    ')
                    ->leftJoin('m_bank AS bank', 'bank.bank_code', '=', 'bp.bank_code')
                    ->selectRaw('
                        bp.payment_id,
                        bp.bill_of_exchange_due,
                        bank.bank_name,
                        bp.type,
                        bp.endorseed_bill
                    ')
                    ->orderBy('bp.bill_of_exchange_due')
                    ->toSql()
                    ;

        $updateTimeQuery = $this
                    ->from('t_purchase_line_item')
                    ->whereRaw('
                        del_flg ='. config('const.flg.off').'
                    ')
                    ->selectRaw('
                        payment_id,
                        MAX(update_at) AS update_at
                    ')
                    ->groupBy('payment_id')
                    ->toSql()
                    ;

        $data = $this
                ->from('t_payment AS pay')
                ->leftJoin(DB::raw('('. $amountSql. ') AS am'), 'am.payment_id', '=', 'pay.id')
                // ->mergeBindings($amountSql)
                ->leftJoin(DB::raw('('. $rebateSql. ') AS reb'), 'reb.payment_id', '=', 'pay.id')
                // ->mergeBindings($rebateSql)
                ->leftJoin(DB::raw('('. $updateTimeQuery. ') AS up1'), 'up1.payment_id', '=', 'pay.id')
                // ->mergeBindings($updateTimeQuery)
                ->leftJoin('t_purchase_line_item AS pl', 'pl.payment_id', '=', 'pay.id')
                ->leftJoin('m_supplier AS sup', 'sup.id', '=', 'pay.supplier_id')
                ->leftJoin('m_bank AS bank', 'bank.id', '=', 'sup.bill_issuing_bank_id')
                ->selectRaw('
                    pay.id AS id,
                    pay.payment_no,
                    pay.supplier_id,
                    pay.payment_mon,
                    pay.closing_day,
                    pay.payment_sight,
                    DATE_FORMAT(pay.purchase_closing_day, "%Y/%m/%d") AS purchase_closing_day,
                    DATE_FORMAT(pay.payment_period_sday, "%Y/%m/%d") AS payment_period_sday,
                    DATE_FORMAT(pay.payment_period_eday, "%Y/%m/%d") AS payment_period_eday,
                    pay.requested_amount,
                    am.fix_cost_total,
                    reb.rebate_detail,
                    pay.rebate,
                    pay.safety_fee,
                    pay.safety_fee_type,
                    pay.offset,
                    pay.paid_rebate,
                    pay.cash,
                    pay.cash_type,
                    pay.check,
                    pay.transfer,
                    pay.fee,
                    pay.receivable,
                    pay.bills,
                    pay.issuance_fee,
                    DATE_FORMAT(pay.planned_payment_date, "%Y/%m/%d") AS payment_date,
                    pay.status,
                    pay.confirmed_staff_id,
                    pay.fixed_date,
                    pay.request_payment_id,
                    pay.membershipfee_flg,
                    pay.offset_flg,
                    pay.cash_flg,
                    pay.transfer_flg,
                    pay.bills_flg,
                    pay.electricitydebt_flg,
                    pay.transmittal_flg,
                    pay.accounting_flg,
                    sup.id AS supplier_id,
                    sup.supplier_name,
                    sup.payment_day,
                    sup.cash_rate,
                    sup.check_rate,
                    sup.bill_rate,
                    sup.transfer_rate,
                    sup.bill_min_price,
                    sup.bill_deadline,
                    sup.bill_fee,
                    sup.bill_issuing_bank_id,
                    bank.bank_name,
                    sup.fee_kbn,
                    sup.safety_org_cost,
                    sup.receive_rebate,
                    sup.sponsor_cost,
                    up1.update_at
                ')
                ->where($where)
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 仕入先IDで取得
     * 対象の仕入先に対しての売掛残
     *
     * @param [type] $supplierId
     * @return void
     */
    public function getReceivableBySupplierId($supplierId)
    {
        // Where句作成
        $where = [];
        $where[] = array('cr.del_flg', '=', config('const.flg.off'));

        $queryWhere = [];
        $queryWhere[] = array('offset_supplier_id', '=', $supplierId);

        $customerQuery = $this
                        ->from('m_customer')
                        ->where($queryWhere)
                        ->select([
                            'id'
                        ])
                        ->get()
                        ->toArray()
                        ;

        $data = $this
                ->from('t_credited AS cr')
                ->where(function ($query) {
                    // 未入金or違算あり
                    $query
                        ->where('cr.status', '=', config('const.creditedStatus.val.unsettled'))
                        ->orWhere('cr.status', '=', config('const.creditedStatus.val.miscalculation'))
                        ;
                })
                ->selectRaw('
                    SUM(receivable) AS receivable
                ')
                ->where($where)
                ->whereIn('cr.customer_id', $customerQuery)
                ->groupBy('cr.customer_id')
                ->get()
                ;

        return $data;
    }

    /**
     * 指定得意先の相殺を取得
     * ステータスは　承認済 or 支払済 or 締済
     * @param $customerId  得意先ID
     * @param $offsetRequestId  相殺適用請求ID
     * @return void
     */
    public function getCustomerTotalOffset($customerId, $offsetRequestId = null)
    {
        $result = 0;

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();

        $Customer   = new Customer();
        $customerData = $Customer->getById($customerId);
        $offsetSupplierId = Common::nullorBlankToZero($customerData->offset_supplier_id);

        if(!empty($offsetSupplierId)){
            $where = [];
            $where[] = array('supplier_id', '=', $offsetSupplierId);
            $where[] = array('del_flg', '=', config('const.flg.off'));
            $statusList = [
                config('const.paymentStatus.val.approvaled'),
                config('const.paymentStatus.val.paid'),
                config('const.paymentStatus.val.closing')
            ];

            // 該当得意先の相殺を合計して取得
            $data = $this
                    ->where($where)
                    ->where(function($query) {  // 請求に未適応の相殺が対象
                        $query->where('offset_request_id',0)
                              ->orWhereNull('offset_request_id');
                    })
                    ->whereIn('status', $statusList)
                    ->get();

            $items = [];
            $items['offset_request_id'] = $offsetRequestId;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();
            
            foreach($data as $row){
                $result += intval(Common::nullorBlankToZero($row->offset));
                if(!empty($offsetRequestId)){
                    $updateWhere = [];
                    $LogUtil->putByData($row, config('const.logKbn.update'), $this->table);
                    // 更新
                    $updateWhere[]                  = array('id', '=', $row->id);
                    $updateCnt = $this
                        ->where($updateWhere)
                        ->update($items);
                }
            }
        }

        return $result;
    }

    /**
     * 指定請求に適用した相殺を未適用に戻す
     * @param $offsetRequestId  相殺適用請求ID
     * @return void
     */
    public function restoreOffsetToUnapplied($offsetRequestId)
    {
        $result = true;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();

            $where = [];
            $where[] = array('offset_request_id', '=', $offsetRequestId);
            $where[] = array('del_flg', '=', config('const.flg.off'));

            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

            $items = [];
            $items['offset_request_id'] = 0;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $updateCnt = $this
                        ->where($where)
                        ->update($items);

            $result = $updateCnt > 0;

        } catch (\Exception $e) {
            throw $e;
        }

        return $result;
    }

    /**
     * 支払番号取得
     *
     * @return void
     */
    public function getPaymentNoList() 
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->select([
                    'payment_no'
                ])
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateByRequestPaymentId($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if(!isset($params['update_user']) || empty($params['update_user'])){
                $params['update_user'] = Auth::user()->id;
            }
            if(!isset($params['update_at']) || empty($params['update_at'])){
                $params['update_at'] = Carbon::now();
            }

            $updateCnt = $this
                    ->where('request_payment_id', $params['request_payment_id'])
                    ->update($params)
                    ;
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 支払申請IDから取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getByRequestPaymentId($ids) 
    {
        $where = [];
        $where[] = array('t_payment.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_purchase_line_item.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->join('t_purchase_line_item', 't_purchase_line_item.payment_id', '=', 't_payment.id')
                ->where($where)
                ->whereIn('t_payment.request_payment_id', $ids)
                ->select([
                    't_payment.*',
                    't_purchase_line_item.cost_kbn',
                    't_purchase_line_item.customer_id',
                    't_purchase_line_item.product_id',
                    't_purchase_line_item.fix_cost_unit_price',
                ])
                ->distinct()
                ->get()
                ;

        return $data;
    }


     /**
     * 支払申請IDから取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getPaymentByRequestPaymentId($ids) 
    {
        $where = [];
        $where[] = array('t_payment.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->whereIn('t_payment.request_payment_id', $ids)
                ->select([
                    't_payment.*',
                ])
                ->get()
                ;

        return $data;
    }

    /**
     * 支払申請IDから取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getPaymentDataByRequestPaymentId($requestPaymentId) 
    {
        $where = [];
        $where[] = array('t_payment.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_payment.request_payment_id', '=', $requestPaymentId);

        $data = $this
                ->leftJoin('t_purchase_line_item AS pl', 'pl.payment_id', '=', 't_payment.id')
                ->where($where)
                ->select([
                    't_payment.id',
                    'pl.id AS purchase_line_item_id',
                    'pl.cost_kbn',
                ])
                ->get()
                ;

        return $data;
    }


    /**
     * 仕入先IDから支払番号取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getPaymentNoBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('pay.del_flg', '=', config('const.flg.off'));
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));
        $where[] = array('pl.supplier_id', '=', $supplier_id);

        $data = $this
                ->from('t_purchase_line_item as pl')
                ->leftJoin('t_payment as pay', 'pay.id', '=', 'pl.payment_id')
                ->select([
                    'pay.payment_no',
                ])
                ->where($where)
                ->whereNotNull('pay.payment_no')
                ->distinct()
                ->get()
                ;
        
        return $data;
    }
}