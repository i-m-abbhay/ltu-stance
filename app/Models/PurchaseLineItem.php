<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;

/**
 * 仕入テーブル
 */
class PurchaseLineItem extends Model
{
    // テーブル名
    protected $table = 't_purchase_line_item';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'payment_id',
        'customer_id',
        'customer_name',
        'matter_id',
        'charge_department_id',
        'charge_department_name',
        'order_id',
        'order_no',
        'order_detail_id',
        'purchase_type',
        'discount_reason',
        'arrival_id',
        'return_id',
        'supplier_id',
        'supplier_name',
        'arrival_date',
        'product_id',
        'product_code',
        'product_name',
        'model',
        'maker_id',
        'maker_name',
        'min_quantity',
        'stock_quantity',
        'quantity',
        'unit',
        'cost_kbn',
        'cost_unit_price',
        'fix_cost_unit_price',
        'cost_makeup_rate',
        'fix_cost_makeup_rate',
        'cost_total',
        'fix_cost_total',
        'vendors_request_no',
        'confirmed_staff_id',
        'fixed_date',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];
    
    /**
     * 仕入確定担当者取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getConfirmStaffBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));
        $where[] = array('pl.supplier_id', '=', $supplier_id);

        $data = $this
                ->from('t_purchase_line_item AS pl')
                ->leftJoin('m_staff AS staff', 'staff.id', '=', 'pl.confirmed_staff_id')
                ->where($where)
                ->select([
                    'staff.id',
                    'staff.staff_name'
                ])
                ->whereNotNull('staff.id')
                ->distinct()
                ->get()
                ;
                    
        return $data;
    }


    /**
     * 仕入先IDから請求番号取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getRequestNoBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));
        $where[] = array('pl.supplier_id', '=', $supplier_id);

        $data = $this
                ->from('t_purchase_line_item AS pl')
                ->where($where)
                ->whereNotNull('pl.vendors_request_no')
                ->select([
                    'pl.vendors_request_no',
                ])
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 仕入先請求番号リスト取得
     *
     * @return void
     */
    public function getRequestNoComboList()
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('vendors_request_no', '<>', '');

        $data = $this
                ->where($where)
                ->select([
                    'vendors_request_no',
                ])
                ->whereNotNull('vendors_request_no')
                ->distinct()
                ->get()
                ;

        return $data;
    }


    /**
     * 仕入明細
     * 確定済データ取得
     *
     * @param [type] $params
     * @return void
     */
    public function getPurchaseData($params) 
    {
        $where = [];
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));
        // $where[] = array('pl.arrival_date', '>=', config('const.purchaseArrivalValidDate'));

        if (!empty($params['supplier_id'])) {
            $where[] = array('pl.supplier_id', '=', $params['supplier_id']);
        }
        if (!empty($params['maker_id'])) {
            $where[] = array('od.maker_id', '=', $params['maker_id']);
        }
        if (!empty($params['order_no'])) {
            $where[] = array('od.order_no', '=', $params['order_no']);
        }
        if (!empty($params['order_staff_id'])) {
            $where[] = array('o.order_staff_id', '=', $params['order_staff_id']);
        }
        // if (!empty($params['from_arrival_date']) || !empty($params['to_arrival_date'])) {
        //     if (!empty($params['from_arrival_date']) && !empty($params['to_arrival_date'])) {
        //         // FROM-TOに入力があった場合
        //         $whereBetween = array($params['from_arrival_date'], $params['to_arrival_date']);
        //     } else if (!empty($params['from_arrival_date'])) {
        //         // FROMのみ
        //         $where[] = array('ar.arrival_date', '>=', $params['from_arrival_date']);
        //     } else if (!empty($params['to_arrival_date'])) {
        //         // TOのみ
        //         $where[] = array('ar.arrival_date', '<=', $params['to_arrival_date']);
        //     }
        // }
        if (!empty($params['department_id'])) {
            $where[] = array('m.department_id', '=', $params['department_id']);
        }
        if (!empty($params['staff_id'])) {
            $where[] = array('m.staff_id', '=', $params['staff_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('pl.customer_id', '=', $params['customer_id']);
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('m.matter_no', '=', $params['matter_no']);
        }
        if (!empty($params['class_big_id'])) {
            $where[] = array('qd.class_big_id', '=', $params['class_big_id']);
        }
        if (!empty($params['construction_id'])) {
            $where[] = array('qd.construction_id', '=', $params['construction_id']);
        }
        if (!empty($params['product_id'])) {
            $where[] = array('pl.product_id', '=', $params['product_id']);
        }
        if (!empty($params['payment_no'])) {
            $where[] = array('pay.payment_no', '=', $params['payment_no']);
        }
        if (!empty($params['vendors_request_no'])) {
            $where[] = array('pl.vendors_request_no', '=', $params['vendors_request_no']);
        }

        // 売上取得
        $subWhere = [];
        $subWhere[] = array('s.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('sd.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('qd.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('s.supplier_id', '=', $params['supplier_id']);

        $salesSubQuery = $this
                ->from('t_sales As s')
                ->join('t_sales_detail As sd', 's.id', '=', 'sd.sales_id')
                ->leftJoin('t_quote_detail As qd', 's.quote_detail_id', '=', 'qd.id')
                ->select([
                    's.quote_detail_id',
                    DB::raw('MAX(s.id) As sales_max_id'),
                    DB::raw('MAX(sd.sales_date) As sales_date')
                ])
                ->where($subWhere)
                ->groupBy('s.quote_detail_id')
                ;

        $salesQuery = $this
                ->from('t_sales As s')
                ->join('t_sales_detail As sd', 's.id', '=', 'sd.sales_id')
                ->leftJoin('t_quote_detail As qd', 's.quote_detail_id', '=', 'qd.id')
                ->join(DB::raw('(' .$salesSubQuery->toSql() . ') as sub_query'), function ($join) {
                    // 直近の売上
                    $join
                        ->on('s.quote_detail_id', '=', 'sub_query.quote_detail_id')
                        ->on('sub_query.sales_max_id', '=', 's.id');
                })
                ->mergeBindings($salesSubQuery->getQuery())
                ->where($subWhere)
                ->select([
                    's.quote_detail_id',
                    DB::raw('1 As sales_recording'),
                    DB::raw('s.sales_unit_price As sales_unit_price'),
                    DB::raw('sub_query.sales_date As sales_date')
                ])
                ;


        $data = $this
                ->from('t_purchase_line_item AS pl')
                ->leftJoin('t_payment AS pay', 'pay.id', '=', 'pl.payment_id')
                ->leftJoin('t_order_detail AS od', 'od.id', '=', 'pl.order_detail_id')
                ->leftJoin('t_order AS o', 'o.id', '=', 'pl.order_id')
                ->leftJoin('t_matter AS m', 'm.id', '=', 'pl.matter_id')
                ->leftJoin('t_quote_detail AS qd', 'qd.id', '=', 'od.quote_detail_id')
                // ->leftJoin('t_sales_detail AS sd', 'sd.quote_detail_id', '=', 'qd.id')
                // ->leftJoin('t_request AS req', 'req.id', '=', 'sd.request_id')
                ->leftJoin('m_customer AS cus', 'cus.id', '=', 'pl.customer_id')
                ->leftJoin('m_department AS dep', 'dep.id', '=', 'm.department_id')
                ->leftJoin(DB::raw('(' .$salesQuery->toSql() . ') as sales_query'), function ($join) {
                    $join->on('sales_query.quote_detail_id', '=', 'od.quote_detail_id');
                })
                ->mergeBindings($salesQuery->getQuery())
                ->where($where)
                ->whereRaw('
                    (
                        pl.arrival_date >= '. config('const.purchaseArrivalValidDate'). '
                        OR
                        pl.arrival_date IS NULL
                    )
                ')
                ->where(function ($query) use ($params) {
                    if (!empty($params['from_arrival_date']) || !empty($params['to_arrival_date'])) {
                        if (!empty($params['from_arrival_date']) && !empty($params['to_arrival_date'])) {
                            // FROM-TOに入力があった場合
                            $query->whereBetween('pl.arrival_date', array($params['from_arrival_date'], $params['to_arrival_date']));
                        } else if (!empty($params['from_arrival_date'])) {
                            // FROMのみ
                            $query->where('pl.arrival_date', '>=', $params['from_arrival_date']);
                        } else if (!empty($params['to_arrival_date'])) {
                            // TOのみ
                            $query->where('pl.arrival_date', '<=', $params['to_arrival_date']);
                        }
                    }
                })
                // req.status AS request_status,
                ->selectRaw('
                    pay.status,
                    COALESCE(sales_query.sales_recording, 0) as sales_recording,
                    COALESCE(sales_query.sales_unit_price, 0) As sales_unit_price,
                    COALESCE(sales_query.sales_unit_price * pl.quantity, 0) As sales_total,
                    pl.purchase_type,
                    pl.id AS id,
                    pl.arrival_id,
                    pl.return_id,
                    pl.payment_id AS payment_id,
                    pl.order_id AS order_id,
                    pl.order_detail_id AS order_detail_id,
                    DATE_FORMAT(sales_query.sales_date, "%Y/%m/%d") As sales_date,
                    DATE_FORMAT(pl.arrival_date, "%Y/%m/%d") AS arrival_date,
                    pl.vendors_request_no AS request_no,
                    pl.product_id,
                    pl.product_code,
                    pl.product_name,
                    pl.model,
                    pl.maker_id,
                    pl.maker_name,
                    pl.supplier_id,
                    pl.supplier_name,
                    pl.discount_reason,
                    od.regular_price,
                    cus.customer_name,
                    m.owner_name,
                    o.account_customer_name,
                    o.account_owner_name,
                    pl.order_no,
                    pl.stock_quantity,
                    pl.min_quantity,
                    pl.quantity,
                    pl.unit,
                    pl.cost_kbn,
                    pl.cost_unit_price AS cost_unit_price,
                    pl.fix_cost_unit_price AS fix_cost_unit_price,
                    pl.cost_makeup_rate AS cost_makeup_rate,
                    pl.fix_cost_makeup_rate AS fix_cost_makeup_rate,
                    pl.cost_total,
                    pl.fix_cost_total,
                    pl.vendors_request_no,
                    pl.confirmed_staff_id,
                    DATE_FORMAT(pl.fixed_date, "%Y/%m/%d") As fixed_date,
                    m.customer_id,
                    m.id AS matter_id,
                    m.matter_no AS matter_no,
                    m.department_id,
                    m.staff_id,
                    dep.department_name
                ')
                ->distinct()
                ->get()
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
                'payment_id' => $params['payment_id'],
                'customer_id' => $params['customer_id'],
                'customer_name' => $params['customer_name'],
                'matter_id' => $params['matter_id'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_department_name' => $params['charge_department_name'],
                'order_id' => $params['order_id'],
                'order_no' => $params['order_no'],
                'order_detail_id' => $params['order_detail_id'],
                'purchase_type' => $params['purchase_type'],
                'discount_reason' => $params['discount_reason'],
                'arrival_id' => $params['arrival_id'],
                'return_id' => $params['return_id'],
                'supplier_id' => $params['supplier_id'],
                'supplier_name' => $params['supplier_name'],
                'arrival_date' => $params['arrival_date'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'product_name' => $params['product_name'],
                'model' => $params['model'],
                'maker_id' => $params['maker_id'],
                'maker_name' => $params['maker_name'],
                'min_quantity' => $params['min_quantity'],
                'stock_quantity' => $params['stock_quantity'],
                'quantity' => $params['quantity'],
                'unit' => $params['unit'],
                'cost_kbn' => $params['cost_kbn'],
                'cost_unit_price' => $params['cost_unit_price'],
                'fix_cost_unit_price' => $params['fix_cost_unit_price'],
                'cost_makeup_rate' => $params['cost_makeup_rate'],
                'fix_cost_makeup_rate' => $params['fix_cost_makeup_rate'],                
                'cost_total' => $params['cost_total'],
                'fix_cost_total' => $params['fix_cost_total'],
                'vendors_request_no' => $params['vendors_request_no'],
                'confirmed_staff_id' => $params['confirmed_staff_id'],
                'fixed_date' => $params['fixed_date'],
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

            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($items);
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
     * 確定解除
     *
     * @param [type] $id
     * @return void
     */
    public function cancelConfirm($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $items = [];
            if (empty($params['confirmed_staff_id'])) {
                $items['confirmed_staff_id'] = null;
            }
            if (empty($params['fixed_date'])) {
                $items['fixed_date'] = null;
            }
            $items['payment_id'] = null;
            $items['del_flg'] = config('const.flg.off');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($items)
                    ;

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * 支払予定IDから取得
     * リベート内訳データ
     *
     * @param [type] $paymentId
     * @return void
     */
    public function getRebateListByPaymentId($paymentId)
    {
        $where = [];
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));
        $where[] = array('pl.payment_id', '=', $paymentId);

        $data = $this
                ->from('t_purchase_line_item AS pl')
                ->where($where)
                ->whereNotNull('pl.purchase_type')
                ->leftJoin('m_supplier AS sup', 'sup.id', '=', 'pl.supplier_id')
                ->leftJoin('m_customer AS cus', 'cus.id', '=', 'pl.customer_id')
                ->leftJoin('t_matter AS m', 'm.id', '=', 'pl.matter_id')
                ->leftJoin('t_payment AS pay', 'pay.id', '=', 'pl.payment_id')
                ->leftJoin('m_department AS dep', 'dep.id', '=', 'pl.charge_department_id')
                ->selectRaw('
                    pl.id AS id,
                    pl.payment_id AS payment_id,
                    pl.purchase_type,
                    pl.discount_reason,
                    pl.supplier_id,
                    sup.supplier_name,
                    DATE_FORMAT(pay.planned_payment_date, "%Y/%m/%d") as planned_payment_date,
                    pl.customer_id,
                    cus.customer_name,
                    pl.matter_id,
                    m.matter_no,
                    m.matter_name,
                    pl.charge_department_id,
                    dep.department_name,
                    pl.quantity,
                    pl.unit,
                    pl.fix_cost_unit_price AS cost_unit_price,
                    pl.fix_cost_makeup_rate AS cost_makeup_rate,
                    pl.fix_cost_total AS cost_total
                ')
                ->get()
                ;

        return $data;
    }

    /**
     * 支払予定IDから取得
     * 手形情報データ
     *
     * @param [type] $paymentId
     * @return void
     */
    public function getBillsListByPaymentId($paymentId)
    {
        $where = [];
        $where[] = array('bp.del_flg', '=', config('const.flg.off'));
        // $where[] = array('pl.purchase_type', '<>', '');
        $where[] = array('bp.payment_id', '=', $paymentId);

        $data = $this
                ->from('t_bill_payable AS bp')
                ->where($where)
                // ->whereNotNull('pl.purchase_type')
                ->leftJoin('t_payment AS pay', 'pay.id', '=', 'bp.payment_id')
                // ->leftJoin('t_purchase_line_item AS pl', 'pl.payment_id', '=', 'pay.id')
                ->leftJoin('m_supplier AS sup', 'sup.id', '=', 'pay.supplier_id')
                // ->leftJoin('m_customer AS cus', 'cus.id', '=', 'pay.customer_id')
                // ->leftJoin('t_matter AS m', 'm.id', '=', 'pay.matter_id')
                // ->leftJoin('m_department AS dep', 'dep.id', '=', 'pay.charge_department_id')
                // pl.id AS purchase_line_item_id,
                ->selectRaw('
                    pay.id AS payment_id,
                    bp.id AS id,
                    sup.supplier_name AS supplier_name,
                    DATE_FORMAT(pay.planned_payment_date, "%Y/%m/%d") as planned_payment_date,
                    bp.bills,
                    bp.type,
                    bp.bank_code,
                    bp.branch_code,
                    DATE_FORMAT(bp.bill_of_exchange_due, "%Y/%m/%d") as bill_of_exchange_due,
                    bp.endorseed_bill,
                    bp.del_flg
                ')
                ->distinct()
                ->orderBy('bill_of_exchange_due')
                ->get()
                ;

        return $data;
    }


    /**
     * 支払予定IDから取得
     * 支払申請承認時の関連情報
     *
     * @param [type] $ids
     * @return void
     */
    public function getParentDataByPaymentIdList($ids)
    {
        $where = [];
        $where[] = array('pl.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('t_purchase_line_item AS pl')
                ->leftJoin('t_order_detail AS od', function($join){
                    $join
                        ->on('od.id', '=', 'pl.order_detail_id')
                        ->where('od.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_quote_detail AS qd', function ($join){
                    $join
                        ->on('qd.id', '=', 'od.quote_detail_id')
                        ->where('qd.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->whereIn('pl.payment_id', $ids)
                ->where($where)
                ->selectRaw('
                    pl.id,
                    pl.payment_id,
                    od.id AS order_detail_id,
                    qd.id AS quote_detail_id,
                    qd.quote_no,
                    pl.cost_kbn,
                    pl.fix_cost_unit_price,
                    pl.customer_id,
                    pl.customer_name,
                    qd.tree_path
                ')
                ->get()
                ;

        return $data;
    } 

    /**
     * IDから取得
     * 売上情報更新時の関連データ
     *
     * @param [type] $id
     * @return void
     */
    public function getSalesUpdateDataById($id)
    {
        $where = [];
        $where[] = array('pl.id', '=', $id);

        $data = $this
                ->from('t_purchase_line_item AS pl')
                ->leftJoin('t_order_detail AS od', function($join){
                    $join
                        ->on('od.id', '=', 'pl.order_detail_id')
                        ->where('od.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_quote_detail AS qd', function ($join){
                    $join
                        ->on('qd.id', '=', 'od.quote_detail_id')
                        ->where('qd.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->where($where)
                ->selectRaw('
                    pl.id,
                    pl.payment_id,
                    od.id AS order_detail_id,
                    qd.id AS quote_detail_id,
                    qd.quote_no,
                    pl.cost_kbn,
                    pl.fix_cost_unit_price,
                    pl.customer_id,
                    pl.customer_name,
                    qd.tree_path
                ')
                ->first()
                ;

        return $data;
    }

    /**
     * 最終更新日時を取得
     *
     * @param  $paymentId
     * @return void
     */
    public function getLastUpdateByPaymentId($paymentId)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('payment_id', '=', $paymentId);

        $data = $this
                ->where($where)
                ->whereNotNull('confirmed_staff_id')
                ->whereNotNull('fixed_date')
                ->selectRaw('
                    payment_id,
                    MAX(update_at) AS update_at
                ')
                ->groupBy('payment_id')
                ->first()
                ;

        return $data;
    }

    /**
     * 案件IDでデータを取得
     *
     * @param [type] $matterId
     * @return void
     */
    public function getByMatterId($matterId)
    {
        // 条件
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('matter_id', '=', $matterId);

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 返品IDで取得
     * 仕入確定状態かどうか
     * @param [type] $returnId
     * @return boolean
     */
    public function hasReturnPurchaseByReturnId($returnId) 
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('return_id', '=', $returnId);

        $data = $this
            ->where($where)
            ->whereNotNull('confirmed_staff_id')
            ->get()
            ;

        return count($data) > 0;
    }

    
    /**
     * 返品IDで論理削除
     *
     * @param [type] $returnId
     * @return void
     */
    public function deleteByReturnId($returnId)
    {

        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('return_id', '=', $returnId);

            // データ取得
            $deleteDataList = $this
                ->where($where)
                ->select([
                    'id',
                ])
                ->get();
    
            if($deleteDataList->count() >= 1){

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                foreach ($deleteDataList as $row) {
                    $LogUtil->putById($this->table, $row['id'], config('const.logKbn.soft_delete'));
                }

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