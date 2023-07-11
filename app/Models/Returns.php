<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 返品
 */
class Returns extends Model
{
    // テーブル名
    protected $table = 't_return';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    /**
     * 更新（廃棄画面）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateByWarehouseMoveId($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['warehouse_move_id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('warehouse_move_id', $params['warehouse_move_id'])
                ->where('return_kbn', $params['return_kbn'])
                ->update([
                    'return_finish' => $params['return_finish'],
                    'return_user' => Auth::user()->id,
                    'return_at' => Carbon::now(),
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
     * 返品処置画面　一覧
     *
     * @return void
     */
    public function getReturnList()
    {
        $where = [];
        $where[] = array('rtn.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_return as rtn')
            ->leftJoin('m_product as p', 'p.id', '=', 'rtn.product_id')
            ->leftJoin('t_matter as m', 'm.id', '=', 'rtn.matter_id')
            ->leftJoin('m_customer as c', 'c.id', '=', 'rtn.customer_id')
            ->leftJoin('m_general as kbn', function ($join) {
                $join
                    ->on('kbn.value_code', '=', 'rtn.return_kbn')
                    ->where('kbn.category_code', '=', config('const.general.rtnprocess'));
            })
            ->leftJoin('t_warehouse_move AS wm', 'wm.id', '=', 'rtn.warehouse_move_id')
            ->leftJoin('t_product_move AS t_pm', 't_pm.id', '=', 'wm.to_product_move_id')
            ->leftJoin('t_purchase_line_item AS pl', function($join) {
                $join
                    ->on('pl.return_id', '=', 'rtn.id')
                    ->where('pl.del_flg', '=', config('const.flg.off'))
                    ;
            })
            ->where($where)
            ->selectRaw('
                    rtn.id,
                    pl.id AS purchase_id,
                    rtn.warehouse_move_id,
                    t_pm.to_shelf_number_id AS shelf_number_id,
                    rtn.qr_code,
                    rtn.stock_flg,
                    rtn.product_id,
                    rtn.product_code,
                    rtn.matter_id,
                    rtn.customer_id,
                    rtn.return_kbn,
                    rtn.sp_flg,
                    rtn.return_quantity,
                    rtn.return_finish,
                    rtn.product_move_id,
                    rtn.image_sign,
                    p.product_name,
                    kbn.value_text_1,
                    CASE
                        WHEN rtn.return_finish = \'' . config('const.flg.off') . '\'
                            THEN DATE_FORMAT(rtn.maker_return_date, "%Y/%m/%d")
                        ELSE DATE_FORMAT(rtn.return_at, "%Y/%m/%d")
                    END AS maker_return_date,

                    CASE
                        WHEN rtn.return_finish = \'' . config('const.flg.off') . '\'
                            THEN \'未\'
                        ELSE \'済\'
                    END AS return_status_txt,

                    CASE
                        WHEN rtn.return_kbn = \'' . config('const.returnKbn.keep') . '\'
                            THEN \'' . config('const.returnKbn.status.keep') . '\'
                        WHEN rtn.return_kbn = \'' . config('const.returnKbn.stock') . '\'
                            THEN \'' . config('const.returnKbn.status.stock') . '\'
                        WHEN rtn.return_kbn = \'' . config('const.returnKbn.maker') . '\'
                            THEN \'' . config('const.returnKbn.status.maker') . '\'
                        WHEN rtn.return_kbn = \'' . config('const.returnKbn.discard') . '\'
                            THEN \'' . config('const.returnKbn.status.discard') . '\'
                    END AS return_kbn_txt
                ')
            ->get();

        return $data;
    }

    /**
     * キャンセルされたメーカー返品があるか
     *
     * @param  $id
     * @return boolean
     */
    public function isExistCanceledMakerReturnByMoveId($warehouseMoveId)
    {
        $where = [];
        $where[] = array('rtn.del_flg', '=', config('const.flg.on'));
        $where[] = array('rtn.warehouse_move_id', '=', $warehouseMoveId);
        $where[] = array('rtn.return_kbn', '=', config('const.returnKbn.maker'));

        $cnt = $this
            ->from('t_return AS rtn')
            ->where($where)
            ->count();

        return $cnt > 0;
    }

    /**
     * 論理削除
     * @param int $id 拠点ID
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
                    'return_finish' => config('const.returnFinish.val.cancel'),
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
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params)
    {
        $result = false;
        try {
            $result = $this->insertGetId([
                'warehouse_move_id' => $params['warehouse_move_id'],
                'qr_code' => $params['qr_code'],
                'stock_flg' => $params['stock_flg'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'matter_id' => $params['matter_id'],
                'customer_id' => $params['customer_id'],
                'return_kbn' => $params['return_kbn'],
                'sp_flg' => $params['sp_flg'],
                'return_quantity' => $params['return_quantity'],
                'return_finish' => $params['return_finish'],
                'return_user' => Auth::user()->id,
                'return_at' => Carbon::now(),
                'product_move_id' => $params['product_move_id'],
                'maker_return_date' => $params['maker_return_date'],
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
     * 更新（画面）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateMakerReturnById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['return_id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['return_id'])
                ->update([
                    'return_finish' => $params['return_finish'],
                    'product_move_id' => $params['product_move_id'],
                    'image_sign' => $params['image_sign'],
                    'return_user' => Auth::user()->id,
                    'return_at' => Carbon::now(),
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
     * ﾒｰｶｰ引渡一覧を取得
     * 
     * @return 
     */
    public function getListMakerDelivery(array $params)
    {
        $where = [];
        $where[] = array('r.return_kbn', '=', 3); //ﾒｰｶｰ返品処理
        $where[] = array('r.return_finish', '=', config('const.flg.off'));
        $where[] = array('r.del_flg', '=', config('const.flg.off'));

        if ($params['maker_id'] != null && $params['maker_id'] != ""  && $params['maker_id'] != 0) {
            $where[] = array('p.maker_id', '=', $params['maker_id']);
        }

        $joinwhere = [];
        $joinwhere['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinwhere['supplier'][] = array('s.del_flg', '=', config('const.flg.off'));
        $joinwhere['qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinwhere['qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_return as r')

            ->leftJoin('m_product as p', function ($join) use ($joinwhere) {
                $join->on('p.id', '=', 'r.product_id')
                    ->where($joinwhere['product']);
            })

            ->leftJoin('m_supplier as s', function ($join) use ($joinwhere) {
                $join->on('s.id', '=', 'p.maker_id')
                    ->where($joinwhere['product']);
            })

            ->leftJoin('m_general', function ($join) {
                $join->on('s.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })

            ->leftJoin('t_qr as q', function ($join) use ($joinwhere) {
                $join->on('q.qr_code', '=', 'r.qr_code')
                    ->where($joinwhere['qr']);
            })

            ->leftJoin('t_qr_detail as qd', function ($join) use ($joinwhere) {
                $join->on('qd.qr_id', '=', 'q.id')
                    ->where($joinwhere['qr_detail']);
            })

            ->leftJoin('t_warehouse_move as w', function ($join) use ($joinwhere) {
                $join->on('w.id', '=', 'r.warehouse_move_id');
            })

            ->leftJoin('t_productstock_shelf as ps', function ($join) use ($joinwhere) {
                $join->on('ps.product_id', '=', 'r.product_id')
                    ->on('ps.warehouse_id', '=', 'w.to_warehouse_id')
                    ->on('ps.matter_id', '=', 'r.matter_id')
                    ->on('ps.customer_id', '=', 'r.customer_id');
            })

            ->where($where)
            ->selectRaw(
                'r.id as return_id
                , r.warehouse_move_id
                , r.product_id
                , p.product_code
                , p.model
                , p.product_name
                , r.return_quantity
                , p.stock_unit as unit
                , r.matter_id as matter_id
                , r.customer_id as customer_id
                , r.stock_flg
                , ifnull(min(qd.warehouse_id), w.to_warehouse_id) as warehouse_id
                , ifnull(min(qd.shelf_number_id), min(ps.shelf_number_id)) as shelf_number_id
                , r.qr_code as qr_code
                , q.id as qr_id
                , p.maker_id
                , CONCAT(COALESCE(min(m_general.value_text_2), \'\'), COALESCE(s.supplier_name, \'\'), COALESCE(min(m_general.value_text_3), \'\')) as supplier_name
                , 0 as input_quantity'
            )
            ->groupBy(
                DB::Raw('r.id,r.qr_code')
            )
            ->get();

        return $data;
    }


    /**
     * 仕入明細
     * 返品データから取得
     *
     * @param [type] $params
     * @return void
     */
    public function getPurchaseData($params) 
    {
        $where = [];
        $where[] = array('rtn.del_flg', '=', config('const.flg.off'));
        $where[] = array('rtn.return_kbn', '=', config('const.returnKbn.maker'));
        $where[] = array('rtn.return_at', '>=', config('const.purchaseReturnValidDate'));

        if (!empty($params['supplier_id'])) {
            $where[] = array('od.supplier_id', '=', $params['supplier_id']);
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
            $where[] = array('m.customer_id', '=', $params['customer_id']);
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
            $where[] = array('rtn.product_id', '=', $params['product_id']);
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
                ->from('t_return AS rtn')
                ->leftJoin('t_purchase_line_item AS pl', function($join) {
                    $join
                        ->on('pl.return_id', '=', 'rtn.id')
                        ->where('pl.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_payment AS pay', 'pay.id', '=', 'pl.payment_id')
                ->leftJoin('t_warehouse_move AS wm', 'wm.id', '=', 'rtn.warehouse_move_id')
                ->leftJoin('t_order_detail AS od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id') 
                ->leftJoin('t_matter AS m', 'm.id', '=', 'rtn.matter_id')
                ->leftJoin('t_quote_detail AS qd', 'qd.id', '=', 'od.quote_detail_id')
                // ->leftJoin('t_sales_detail AS sd', 'sd.quote_detail_id', '=', 'qd.id')
                // ->leftJoin('t_request AS req', 'req.id', '=', 'sd.request_id')
                ->leftJoin('m_customer AS cus', 'cus.id', '=', 'rtn.customer_id')
                ->leftJoin('m_department AS dep', 'dep.id', '=', 'm.department_id')
                ->leftJoin(DB::raw('(' .$salesQuery->toSql() . ') as sales_query'), function ($join) {
                    $join->on('sales_query.quote_detail_id', '=', 'od.quote_detail_id');
                })
                ->mergeBindings($salesQuery->getQuery())
                ->where($where)
                ->where(function ($query) use ($params) {
                    if (!empty($params['from_arrival_date']) || !empty($params['to_arrival_date'])) {
                        if (!empty($params['from_arrival_date']) && !empty($params['to_arrival_date'])) {
                            // FROM-TOに入力があった場合
                            $query->whereBetween('rtn.return_at', array($params['from_arrival_date'], $params['to_arrival_date']));
                        } else if (!empty($params['from_arrival_date'])) {
                            // FROMのみ
                            $query->where('rtn.return_at', '>=', $params['from_arrival_date']);
                        } else if (!empty($params['to_arrival_date'])) {
                            // TOのみ
                            $query->where('rtn.return_at', '<=', $params['to_arrival_date']);
                        }
                    }
                })
                ->whereNull('pl.id')
                // \''.config('const.flg.off').'\' AS status,
                ->selectRaw('
                    pl.id AS id,
                    pl.payment_id,
                    pay.status,
                    DATE_FORMAT(pl.fixed_date, "%Y/%m/%d") As fixed_date,
                    DATE_FORMAT(sales_query.sales_date, "%Y/%m/%d") As sales_date,
                    COALESCE(sales_query.sales_recording, 0) as sales_recording,
                    COALESCE(sales_query.sales_unit_price, 0) As sales_unit_price,
                    COALESCE(sales_query.sales_unit_price * rtn.return_quantity, 0) As sales_total,
                    \''.config('const.flg.off').'\' AS purchase_status,
                    rtn.id AS return_id,
                    pl.arrival_id AS arrival_id,
                    o.id AS order_id,
                    od.id AS order_detail_id,
                    DATE_FORMAT(rtn.return_at, "%Y/%m/%d") AS arrival_date,
                    od.product_id,
                    od.product_code,
                    od.product_name,
                    od.model,
                    od.maker_id,
                    od.maker_name,
                    od.supplier_id,
                    od.supplier_name,
                    cus.customer_name,
                    m.owner_name,
                    o.account_customer_name,
                    o.account_owner_name,
                    od.order_no,
                    rtn.return_quantity AS quantity,
                    od.order_quantity AS order_quantity,
                    od.stock_quantity AS stock_quantity,
                    od.min_quantity AS min_quantity,
                    od.unit,
                    od.regular_price,
                    qd.cost_kbn,
                    od.cost_unit_price AS cost_unit_price,
                    od.cost_makeup_rate AS cost_makeup_rate,
                    od.sales_makeup_rate,
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
     * 売上明細画面用の返品データを取得
     * 売上明細テーブルに存在する返品データは除外する
     * @param $matterId     案件ID
     * @param $quoteId      見積ID
     * @return $data
     */
    public function getEditSalesDetailData($matterId, $quoteId){
        $where = [];
        $where[] = array('t_return.matter_id', '=', $matterId);
        $where[] = array('t_return.return_kbn', '!=', config('const.returnKbn.keep'));
        $where[] = array('t_return.return_finish', '=', config('const.returnFinish.val.completed'));
        $where[] = array('t_return.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_warehouse_move.to_product_move_at', '>=', config('const.salesReturnValidDate'));
        $where[] = array('t_warehouse_move.delivery_id', '<>', 0);

        $returnQuery = $this
                ->join('t_warehouse_move', function ($join) use ($quoteId) {
                    $join->on('t_return.warehouse_move_id', '=', 't_warehouse_move.id')
                        ->where([
                            ['t_warehouse_move.quote_id', '=', $quoteId]
                        ]);
                })
                ->whereNotNull('t_warehouse_move.delivery_id')
                ->leftjoin('t_sales_detail', function ($join) {
                    $join->on('t_return.id', '=', 't_sales_detail.return_id')
                        ->where([
                            ['t_sales_detail.del_flg', '=', config('const.flg.off')]
                        ]);
                })
                ->leftjoin('t_quote_detail', 't_warehouse_move.quote_detail_id', '=', 't_quote_detail.id')
                ->leftjoin('t_order_detail', 't_warehouse_move.order_detail_id', '=', 't_order_detail.id')
                ->select([
                    't_order_detail.order_no AS delivery_no',
                    DB::raw('0 AS sales_detail_id'),
                    't_warehouse_move.quote_id AS quote_id',
                    't_warehouse_move.quote_detail_id AS quote_detail_id',
                    DB::raw('0 AS sales_id'),
                    DB::raw('null AS sales_update_date'),
                    DB::raw('0 AS sales_update_user'),
                    DB::raw('0 AS delivery_id'),
                    't_return.id AS return_id',
                    DB::raw('0 AS request_id'),
                    DB::raw(config('const.salesFlg.val.return').' AS sales_flg'),
                    DB::raw(config('const.notdeliveryFlg.val.default').' AS notdelivery_flg'),
                    DB::raw('0 AS offset_delivery_id'),
                    DB::raw('DATE_FORMAT(t_warehouse_move.to_product_move_at, "%Y/%m/%d") AS sales_date'),      // 倉庫移動.入庫処理日時
                    DB::raw('t_return.return_quantity AS sales_stock_quantity'),
                    DB::raw('0 AS sales_quantity'),     // 親の金額をセット
                    DB::raw('0 AS sales_unit_price'),   // 親の金額をセット
                    DB::raw('0 AS sales_total'),        // 親の金額をセット
                    DB::raw('0 AS cost_unit_price'),    // 親の金額をセット
                    DB::raw('0 AS cost_total'),         // 親の金額をセット
                    DB::raw('DATE_FORMAT(t_warehouse_move.to_product_move_at, "%Y/%m/%d") AS delivery_date'),   // 倉庫移動.入庫処理日時
                    DB::raw('0 AS delivery_quantity'),
                    DB::raw('null AS memo'),
                ])
                ->where($where)
                ->whereNull('t_sales_detail.id');       // ※返品にあり、売上明細に存在しないレコードを取得する
        $data = $returnQuery->get();

        return $data;
    }

    /**
     * 案件IDで取得
     *
     * @param [type] $matterId
     * @return void
     */
    public function getByMatterId($matterId){
        $data = $this
                ->where([
                    ['del_flg', config('const.flg.off')],
                    ['matter_id', $matterId],
                ])
                ->get()
                ;
        return $data;
    }
}
