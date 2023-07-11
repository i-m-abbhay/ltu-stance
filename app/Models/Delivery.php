<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * 納品
 */
class Delivery extends Model
{
    // テーブル名
    protected $table = 't_delivery';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'matter_id',
        'shipment_id',
        'shipment_detail_id',
        'loading_id',
        'order_detail_id',
        'quote_id',
        'quote_detail_id',
        'shipment_kind',
        'zipcode',
        'pref',
        'address1',
        'address2',
        'latitude_jp',
        'longitude_jp',
        'latitude_world',
        'longitude_world',
        'product_id',
        'product_code',
        'delivery_date',
        'delivery_quantity',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
    ];

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_delivery.del_flg', '=', config('const.flg.off'));
        if (!empty($params['loading_id'])) {
            $where[] = array('t_delivery.loading_id', '=', $params['loading_id']);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('t_delivery.id', 'asc')
            ->select('t_delivery.id')
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add($params)
    {
        $result = false;

        try {
            if (isset(Auth::user()->id)) {
                $user_id = Auth::user()->id;
            } else {
                $user_id = 0;
            }
            if($params['delivery_date']==null){
                $params['delivery_date'] = Carbon::now();
            }
            $result = $this->insertGetId([
                'delivery_no' => $params['delivery_no'],
                'matter_id' => $params['matter_id'],
                'stock_flg' => $params['stock_flg'],
                'shipment_id' => $params['shipment_id'],
                'shipment_detail_id' => $params['shipment_detail_id'],
                'loading_id' => $params['loading_id'],
                'order_detail_id' => $params['order_detail_id'],
                'quote_id' => $params['quote_id'],
                'quote_detail_id' => $params['quote_detail_id'],
                'shipment_kind' => $params['shipment_kind'],
                'zipcode' => $params['zipcode'],
                'pref' => $params['pref'],
                'address1' => $params['address1'],
                'address2' => $params['address2'],
                'latitude_jp' => $params['latitude_jp'],
                'longitude_jp' => $params['longitude_jp'],
                'latitude_world' => $params['latitude_world'],
                'longitude_world' => $params['longitude_world'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'delivery_date' => $params['delivery_date'],
                'delivery_quantity' => $params['delivery_quantity'],
                'del_flg' => config('const.flg.off'),
                'created_user' => $user_id,
                'created_at' => Carbon::now(),
                'update_user' => $user_id,
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
                    'delivery_no' => $params['delivery_no'],
                    'matter_id' => $params['matter_id'],
                    'shipment_id' => $params['shipment_id'],
                    'shipment_detail_id' => $params['shipment_detail_id'],
                    'loading_id' => $params['loading_id'],
                    'order_detail_id' => $params['order_detail_id'],
                    'quote_id' => $params['quote_id'],
                    'quote_detail_id' => $params['quote_detail_id'],
                    'shipment_kind' => $params['shipment_kind'],
                    'zipcode' => $params['zipcode'],
                    'pref' => $params['pref'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
                    'latitude_jp' => $params['latitude_jp'],
                    'longitude_jp' => $params['longitude_jp'],
                    'latitude_world' => $params['latitude_world'],
                    'longitude_world' => $params['longitude_world'],
                    'stock_flg' => $params['stock_flg'],
                    'product_id' => $params['product_id'],
                    'product_code' => $params['product_code'],
                    'delivery_date' => Carbon::now(),
                    'delivery_quantity' => $params['delivery_quantity'],
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
     * IDで取得
     * @param int $id 選択肢ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id)
    {
        $data = $this
            ->leftjoin('m_staff', 'm_staff.id', '=', 't_delivery.update_user')
            ->select(
                't_delivery.*',
                'm_staff.staff_name AS update_user_name'
            )
            ->where(['t_delivery.id' => $id])
            ->first();

        return $data;
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
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'modified_user' => Auth::user()->id,
                    'modified' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * 納品一覧(返品受入れ)を取得
     * 
     * @return 
     */
    public function getListAcceptingReturns(array $params)
    {
        $where = [];
        $where[] = array('d.del_flg', '=', config('const.flg.off'));

        if (!empty($params['matter_id'])) {
            $where[] = array('d.matter_id', '=', $params['matter_id']);
        }

        if (!empty($params['product_code']) && $params['product_code'] != null) {
            $where[] = array('p.product_code', '=', $params['product_code']);
        }

        if (!empty($params['product_name']) && $params['product_name'] != null) {
            $where[] = array('p.product_name', '=', $params['product_name']);
        }

        if (!empty($params['construction_id']) && $params['construction_id'] != null) {
            $where[] = array('qd.construction_id', '=', $params['construction_id']);
        }

        if (!empty($params['delivery_date'])) {
            $where[] = array('d.delivery_date', '=', $params['delivery_date']);
        }

        $joinWhere = [];
        $joinWhere['matter'][] = array('m.del_flg', '=', config('const.flg.off'));
        $joinWhere['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere['quote_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));
        $joinWhere['construction'][] = array('c.del_flg', '=', config('const.flg.off'));

        $sub = $this
            ->from('t_warehouse_move')
            ->whereRaw('move_kind=2 and approval_status in(0,1)')
            ->selectRaw('
                delivery_id,
                sum(quantity) as quantity
            ')
            ->groupBy('delivery_id')
            ->toSql();

        $data = $this
            ->from('t_delivery as d')
            ->leftJoin('t_matter as m', function ($join) use ($joinWhere) {
                $join->on('m.id', '=', 'd.matter_id')
                    ->where($joinWhere['matter']);
            })

            ->leftJoin('m_product as p', function ($join) use ($joinWhere) {
                $join->on('p.id', '=', 'd.product_id')
                    ->where($joinWhere['product']);
            })

            ->leftJoin('t_quote_detail as qd', function ($join) use ($joinWhere) {
                $join->on('qd.id', '=', 'd.quote_detail_id')
                    ->where($joinWhere['quote_detail']);
            })

            ->leftJoin('m_construction as c', function ($join) use ($joinWhere) {
                $join->on('c.id', '=', 'qd.construction_id')
                    ->where($joinWhere['construction']);
            })



            ->leftJoin(DB::raw('(' .$sub . ') as wm'), function ($join) {
                $join->on('d.id', '=', 'wm.delivery_id');
            })
           
            ->where($where)
            ->selectRaw(
                'd.delivery_date,
                qd.construction_id,
                c.construction_name,
                CONCAT(COALESCE(d.address1, \'\'), COALESCE(d.address2, \'\')) as address_name,
                d.product_id,
                d.product_code,
                d.shipment_detail_id,
                d.stock_flg,
                d.matter_id,
                m.customer_id,
                d.order_detail_id,
                d.quote_id,
                d.quote_detail_id,
                min(d.id) as delivery_id,
                p.product_name,
                sum(d.delivery_quantity) as delivery_quantity,
                d.quote_id,
                d.quote_detail_id,
                ifnull(sum(wm.quantity),0) as returned_quantity
                '
            )

            ->groupBy('d.delivery_date')
            ->groupBy('qd.construction_id')
            ->groupBy('c.construction_name')
            ->groupBy(DB::Raw('CONCAT(COALESCE(d.address1, \'\'), COALESCE(d.address2, \'\'))'))
            ->groupBy('d.product_id')
            ->groupBy('d.product_code')
            ->groupBy('d.shipment_detail_id')
            ->groupBy('d.stock_flg')
            ->groupBy('d.matter_id')
            ->groupBy('m.customer_id')
            ->groupBy('d.order_detail_id')
            ->groupBy('d.quote_id')
            ->groupBy('d.quote_detail_id')
            ->groupBy('p.product_name')
            ->groupBy('d.quote_id')
            ->groupBy('d.quote_detail_id')
            ->get();
        return $data;
    }

    /**
     * 納品番号取得
     * @return type 検索結果データ（1件）
     */
    public function getDeliveryNo($deliveryDate = null)
    {
        if (empty($deliveryDate)) {
            $deliveryDate = Carbon::now()->toDateString();
        }
        $where = [];
        $where[] = array('t_delivery.delivery_date', '=', $deliveryDate);
        $data = $this
            ->where($where)
            ->orderBy('t_delivery.delivery_no', 'desc')
            ->select(
                't_delivery.delivery_no'
            )
            ->first();

        return $data;
    }

    /**
     * IDで取得
     * @param int $IDs 納品ID
     * @return type 検索結果データ
     */
    public function getListInId($IDs)
    {
        $data = $this
            ->leftjoin('m_staff', 'm_staff.id', '=', 't_delivery.created_user')
            ->leftJoin('t_matter as m', 'm.id', '=', 't_delivery.matter_id')
            ->leftJoin('m_product as p', 'p.id', '=', 't_delivery.product_id')
            ->leftJoin('m_supplier as maker', 'p.maker_id', '=', 'maker.id')
            ->leftJoin('m_general as general', function ($join) {
                $join->on('maker.juridical_code', '=', 'general.value_code')
                    ->where('general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_customer as c', 'c.id', '=', 'm.customer_id')
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('t_order_detail AS od', 'od.id', '=', 't_delivery.order_detail_id')
            ->selectRaw('
                t_delivery.id,
                t_delivery.matter_id,
                t_delivery.delivery_no,
                t_delivery.product_id,
                COALESCE(DATE_FORMAT(t_delivery.delivery_date, "%Y/%m/%d"), \'\') AS delivery_date,
                COALESCE(t_delivery.delivery_quantity, \'\') AS delivery_quantity,
                COALESCE(m.matter_name, \'\') AS matter_name,
                COALESCE(m.matter_no, \'\') AS matter_no,
                COALESCE(p.product_code, \'\') AS product_code,
                COALESCE(p.product_name, \'\') AS product_name,
                COALESCE(p.unit, \'\') AS unit,
                COALESCE(od.memo, \'\') AS memo,
                m_staff.id AS created_staff_id,
                m_staff.staff_name AS staff_name
            ')
            ->selectRaw('
                CONCAT(COALESCE(t_delivery.address1, \'\'), COALESCE(t_delivery.address2, \'\')) as address,
                CONCAT(COALESCE(c_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(c_general.value_text_3, \'\')) as customer_name,
                CASE 
                    WHEN maker.print_exclusion_flg =' . config('const.flg.off') . '
                        THEN CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(general.value_text_3, \'\'), \'　／　\', p.model)
                    ELSE p.model
                END AS model
            ')
            ->whereIn('t_delivery.loading_id', $IDs)
            ->get();

        return $data;
    }

        /**
     * IDで取得
     * @param int $IDs 納品ID
     * @return type 検索結果データ
     */
    public function getDeliveryIds($IDs)
    {
        $data = $this
            ->leftjoin('m_staff', 'm_staff.id', '=', 't_delivery.created_user')
            ->leftJoin('t_matter as m', 'm.id', '=', 't_delivery.matter_id')
            ->leftJoin('m_product as p', 'p.id', '=', 't_delivery.product_id')
            ->leftJoin('m_supplier as maker', 'p.maker_id', '=', 'maker.id')
            ->leftJoin('m_general as general', function ($join) {
                $join->on('maker.juridical_code', '=', 'general.value_code')
                    ->where('general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_customer as c', 'c.id', '=', 'm.customer_id')
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('t_order_detail AS od', 'od.id', '=', 't_delivery.order_detail_id')
            ->selectRaw('
                t_delivery.id,
                t_delivery.matter_id,
                t_delivery.delivery_no,
                t_delivery.product_id,
                COALESCE(DATE_FORMAT(t_delivery.delivery_date, "%Y/%m/%d"), \'\') AS delivery_date,
                COALESCE(t_delivery.delivery_quantity, \'\') AS delivery_quantity,
                COALESCE(m.matter_name, \'\') AS matter_name,
                COALESCE(m.matter_no, \'\') AS matter_no,
                COALESCE(p.product_code, \'\') AS product_code,
                COALESCE(p.product_name, \'\') AS product_name,
                COALESCE(p.unit, \'\') AS unit,
                COALESCE(od.memo, \'\') AS memo,
                m_staff.id AS created_staff_id,
                m_staff.staff_name AS staff_name
            ')
            ->selectRaw('
                CONCAT(COALESCE(t_delivery.address1, \'\'), COALESCE(t_delivery.address2, \'\')) as address,
                CONCAT(COALESCE(c_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(c_general.value_text_3, \'\')) as customer_name,
                CASE 
                    WHEN maker.print_exclusion_flg =' . config('const.flg.off') . '
                        THEN CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(general.value_text_3, \'\'), \'　／　\', p.model)
                    ELSE p.model
                END AS model
            ')
            ->whereIn('t_delivery.id', $IDs)
            ->get();

        return $data;
    }

    /**
     * 納品番号から取得
     * 納品書印刷のデータ
     *
     * @param [type] $deliveryNo
     * @return void
     */
    public function getByDeliveryNo($deliveryNo) 
    {
        $data = $this
            ->leftjoin('m_staff', 'm_staff.id', '=', 't_delivery.created_user')
            ->leftJoin('t_matter as m', 'm.id', '=', 't_delivery.matter_id')
            ->leftJoin('m_product as p', 'p.id', '=', 't_delivery.product_id')
            ->leftJoin('m_supplier as maker', 'p.maker_id', '=', 'maker.id')
            ->leftJoin('m_general as general', function ($join) {
                $join->on('maker.juridical_code', '=', 'general.value_code')
                    ->where('general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_customer as c', 'c.id', '=', 'm.customer_id')
            ->leftJoin('m_general as c_general', function ($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                    ->where('c_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('t_order_detail AS od', 'od.id', '=', 't_delivery.order_detail_id')
            ->selectRaw('
                t_delivery.id,
                t_delivery.matter_id,
                t_delivery.delivery_no,
                t_delivery.product_id,
                COALESCE(DATE_FORMAT(t_delivery.delivery_date, "%Y/%m/%d"), \'\') AS delivery_date,
                COALESCE(t_delivery.delivery_quantity, \'\') AS delivery_quantity,
                COALESCE(m.matter_name, \'\') AS matter_name,
                COALESCE(m.matter_no, \'\') AS matter_no,
                COALESCE(p.product_code, COALESCE(od.product_code, \'\')) AS product_code,
                COALESCE(p.product_name, COALESCE(od.product_name, \'\')) AS product_name,
                COALESCE(p.unit, COALESCE(od.unit, \'\')) AS unit,
                COALESCE(od.memo, \'\') AS memo,
                m_staff.id AS created_staff_id,
                m_staff.staff_name AS staff_name
            ')
            ->selectRaw('
                CONCAT(COALESCE(t_delivery.address1, \'\'), COALESCE(t_delivery.address2, \'\')) as address,
                CONCAT(COALESCE(c_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(c_general.value_text_3, \'\')) as customer_name,
                CASE 
                    WHEN maker.print_exclusion_flg =' . config('const.flg.off') . '
                        THEN CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(general.value_text_3, \'\'), \'　／　\', COALESCE(p.model, od.model))
                    ELSE COALESCE(p.model, od.model)
                END AS model
            ')
            ->where('t_delivery.delivery_no', $deliveryNo)
            ->get()
            ;

        return $data;
    }

    /**
     * 新規登録(いきなり売上)
     *
     * @param array $params
     * @return $result
     */
    public function addCounterSale($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'delivery_no' => $params['delivery_no'],
                'matter_id' => $params['matter_id'],
                'quote_id' => $params['quote_id'],
                'stock_flg' => $params['stock_flg'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'delivery_date' => Carbon::now(),
                'delivery_quantity' => $params['delivery_quantity'],
                'intangible_flg' => $params['intangible_flg'],
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
     * 売上明細画面用の納品データを取得
     * 売上明細テーブルに存在する納品データは除外する
     * @param $quoteId      見積ID
     * @return $data
     */
    public function getEditSalesDetailData($quoteId)
    {
        $whereDelivery = [];
        $whereDelivery[] = array('t_delivery.quote_id', '=', $quoteId);
        $whereDelivery[] = array('t_delivery.del_flg', '=', config('const.flg.off'));
        $whereDelivery[] = array('t_delivery.delivery_date', '>=', config('const.salesDeliveryValidDate'));

        $deliveryQuery = $this
            ->leftjoin('t_sales_detail', function ($join) {
                $join->on('t_delivery.id', '=', 't_sales_detail.delivery_id')
                    ->where([
                        ['t_sales_detail.del_flg', '=', config('const.flg.off')]
                    ]);
            })
            ->select([
                't_delivery.delivery_no',
                DB::raw('0 AS sales_detail_id'),
                't_delivery.quote_id',
                't_delivery.quote_detail_id',
                DB::raw('0 AS sales_id'),
                DB::raw('null AS sales_update_date'),
                DB::raw('0 AS sales_update_user'),
                't_delivery.id AS delivery_id',
                DB::raw('0 AS return_id'),
                DB::raw('0 AS request_id'),
                DB::raw(config('const.salesFlg.val.delivery') . ' AS sales_flg'),
                DB::raw(config('const.notdeliveryFlg.val.default') . ' AS notdelivery_flg'),
                DB::raw('0 AS offset_delivery_id'),
                DB::raw('DATE_FORMAT(t_delivery.delivery_date, "%Y/%m/%d") AS sales_date'),
                DB::raw('t_delivery.delivery_quantity AS sales_stock_quantity'),
                DB::raw('0 AS sales_quantity'),     // 親の金額をセット
                DB::raw('0 AS sales_unit_price'),   // 親の金額をセット
                DB::raw('0 AS sales_total'),        // 親の金額をセット
                DB::raw('0 AS cost_unit_price'),    // 親の金額をセット
                DB::raw('0 AS cost_total'),         // 親の金額をセット
                DB::raw('DATE_FORMAT(t_delivery.delivery_date, "%Y/%m/%d") AS delivery_date'),
                't_delivery.delivery_quantity',
                DB::raw('null AS memo'),
            ])
            ->where($whereDelivery)
            ->whereNull('t_sales_detail.id');       // ※納品にあり、売上明細に存在しないレコードを取得する
        $data = $deliveryQuery->get();

        return $data;
    }

    /**
     * 案件IDで納品データを取得
     *
     * @param [type] $matterId
     * @return void
     */
    public function getByMatterId($matterId)
    {
        $data = $this
            ->where([
                ['del_flg', config('const.flg.off')],
                ['matter_id', $matterId],
            ])
            ->get();
        return $data;
    }


    /**
     * 出荷指示IDで取得
     * 出荷納品一覧　証明写真表示画面用データ
     * 
     * @param int $id ID
     * @return type 検索結果データ
     */
    public function getDeliveryListByShipmentId($id) 
    {
        $where = [];
        $where[] = array('shipment_id', '=', $id);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->selectRaw('
                    GROUP_CONCAT(id separator ",") as delivery_id_list,
                    delivery_no,
                    COALESCE(DATE_FORMAT(delivery_date, "%Y/%m/%d"), \'\') AS delivery_date,
                    GROUP_CONCAT(product_code separator ",") as product_code_list,
                    MIN(created_at) as created_at
                ')
                ->groupBy('delivery_no', 'delivery_date')
                ->get()
                ;

        return $data;
    }


    /**
     * 発注IDで取得
     * 出荷納品一覧　納品番号と納品日どとに印刷対象の選択肢を生成するためのデータを返す
     * TODO：無形品も納品するが「product_code」が無いと思うが問題無いか
     *     ：納品番号は必須だと思うが問題無いか
     * @param int $id ID
     * @return type 検索結果データ
     */
    public function getDeliveryListByOrderId($id) 
    {
        $where = [];
        $where[] = array('t_order.id', '=', $id);
        $where[] = array('t_order.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_order_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_delivery.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('t_order')
                ->join('t_order_detail', 't_order.id', '=', 't_order_detail.order_id')
                ->join('t_delivery', 't_order_detail.id', '=', 't_delivery.order_detail_id')
                ->where($where)
                ->selectRaw('
                    GROUP_CONCAT(t_delivery.id separator ",") as delivery_id_list,
                    t_delivery.delivery_no,
                    COALESCE(DATE_FORMAT(t_delivery.delivery_date, "%Y/%m/%d"), \'\') as delivery_date,
                    GROUP_CONCAT(t_delivery.product_code separator ",") as product_code_list,
                    MIN(t_delivery.created_at) as created_at
                ')
                ->groupBy('t_delivery.delivery_no', 't_delivery.delivery_date')
                ->get()
                ;

        return $data;
    }

    /**
     * 発注明細IDで取得
     * 
     * @param int $id ID
     * @return type 検索結果データ
     */
    public function getByOrderDetailId($orderDetailId) 
    {
        $where = [];
        $where[] = array('t_delivery.order_detail_id', '=', $orderDetailId);
        $where[] = array('t_delivery.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('t_delivery')
                ->leftJoin('m_product', 'm_product.id', '=', 't_delivery.product_id')
                ->where($where)
                ->select([
                    't_delivery.*',
                    'm_product.product_name'
                ])
                ->first()
                ;

        return $data;
    }
    
}
