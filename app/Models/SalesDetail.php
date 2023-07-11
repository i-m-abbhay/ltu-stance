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
 * 売上明細
 */
class SalesDetail extends Model
{
    // テーブル名
    protected $table = 't_sales_detail';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'matter_id',
        'quote_id',
        'quote_detail_id',
        'sales_id',
        'sales_update_date',
        'sales_update_user',
        'delivery_id',
        'return_id',
        'request_id',
        'sales_flg',
        'notdelivery_flg',
        'offset_delivery_id',
        'sales_date',
        'sales_stock_quantity',
        'sales_quantity',
        'sales_unit_price',
        'sales_total',
        'cost_unit_price',
        'cost_total',
        'delivery_date',
        'delivery_quantity',
        'memo',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];


    /**
     * 登録
     * @param $params 1行の配列
     * @return id
     */
    public function add($params)
    {
        $items = [];

        $userId = Auth::user()->id;
        $now    = Carbon::now();

        try {
            if (!isset($params['del_flg'])) {
                $params['del_flg'] = config('const.flg.off');
            }
            if (!isset($params['created_user'])) {
                $params['created_user'] = $userId;
            }
            if (!isset($params['created_at'])) {
                $params['created_at'] = $now;
            }
            if (!isset($params['update_user'])) {
                $params['update_user'] = $userId;
            }
            if (!isset($params['update_at'])) {
                $params['update_at'] = $now;
            }

            $registerCol = $this->getFillable();
            foreach ($registerCol as $colName) {
                if (array_key_exists($colName, $params)) {
                    $items[$colName] = $params[$colName];
                }
            }

            // 登録
            return $this->insertGetId($items);
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * 登録
     * @param $params 1行の配列
     * @return id
     */
    public function addToSalesBatch($params)
    {
        $items = [];

        $userId = config('const.batchUser');
        $now    = Carbon::now();

        try {
            if (!isset($params['del_flg'])) {
                $params['del_flg'] = config('const.flg.off');
            }
            if (!isset($params['created_user'])) {
                $params['created_user'] = $userId;
            }
            if (!isset($params['created_at'])) {
                $params['created_at'] = $now;
            }
            if (!isset($params['update_user'])) {
                $params['update_user'] = $userId;
            }
            if (!isset($params['update_at'])) {
                $params['update_at'] = $now;
            }

            $registerCol = $this->getFillable();
            foreach ($registerCol as $colName) {
                if (array_key_exists($colName, $params)) {
                    $items[$colName] = $params[$colName];
                }
            }

            // 登録
            return $this->insertGetId($items);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 更新
     * @param int $salesId 売上明細ID
     * @param array $params 1行の配列
     * @return void
     */
    public function updateById($salesDetailId, $params)
    {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $salesDetailId);

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
            if (!isset($items['update_user'])) { $items['update_user']  = Auth::user()->id; }
            if (!isset($items['update_at'])) { $items['update_at']      = Carbon::now(); }

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
     * IDで複数行削除(物理)
     * @param $ids 配列
     */
    public function deleteByIds($ids)
    {
        $deleteDataList = $this
            ->whereIn('id', $ids)
            ->get();

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        foreach ($deleteDataList as $deleteData) {
            $LogUtil->putByData($deleteData, config('const.logKbn.delete'), $this->table);
        }

        $deleteCnt = $this
            ->whereIn('id', $ids)
            ->delete();

        return $deleteCnt;
    }

    /**
     * 売上IDで物理削除
     * @param $salesId  売上ID
     * @return void
     */
    public function deleteBySalesId($salesId)
    {
        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('sales_id', '=', $salesId);

            $cnt = $this->where($where)
                ->count();

            if($cnt >= 1){
                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

                // 削除
                $deleteCnt = $this
                    ->where($where)
                    ->delete();
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $deleteCnt;
    }

    /**
     * 売上IDで論理削除
     * @param $salesId  売上ID
     * @return void
     */
    public function softDeleteBySalesId($salesId)
    {
        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('sales_id', '=', $salesId);

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

    /**
     * 売上確定しなかったデータをリセット
     * @param  $customer_id
     * @return boolean True:成功 False:失敗 
     */
    public function dataReset($customer_id)
    {
        $result = false;
        try {
            $updateCnt = $this
                ->from('t_sales_detail as s')
                ->leftjoin('t_matter as m', 's.matter_id', '=', 'm.id')
                ->where('m.customer_id', $customer_id)
                ->where('s.sales_update_date', null)
                ->where('s.del_flg', config('const.flg.off'))

                ->update([
                    's.update_user' => Auth::user()->id,
                    's.update_at' => Carbon::now(),
                ]);
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 売上確定しなかったデータをリセットして削除
     * @param  $customer_id
     * @return boolean True:成功 False:失敗 
     */
    public function dataResetDelete($customer_id)
    {
        $result = false;
        try {
            $updateCnt = $this

                ->from('t_sales_detail as s')
                ->leftjoin('t_matter as m', 's.matter_id', '=', 'm.id')
                ->where('m.customer_id', $customer_id)
                ->where('s.sales_update_date', null)
                ->where('s.del_flg', config('const.flg.off'))
                ->where(function ($query) {
                    $query->where('s.sales_flg', 3)
                        ->orWhere(function ($query) {
                            $query->where('s.notdelivery_flg', null)
                                ->where('s.sales_flg', 0);
                        });
                })

                ->update([
                    's.del_flg' => config('const.flg.on'),
                    's.update_user' => Auth::user()->id,
                    's.update_at' => Carbon::now(),
                ]);


            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * customer_id,request_idで取得
     * @param int $customer_id,$request_id
     * @return type 検索結果データ
     */
    public function getSalesDetailForRequestList($customer_id_list, $request_id_list)
    {
        $where = [];
        $where[] = array('r.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_request as r')
            ->leftjoin('t_sales as s', 's.request_id', '=', 'r.id')
            ->leftjoin('t_sales_detail as sd', 's.id', '=', 'sd.sales_id')
            ->leftjoin('t_matter as m', 'm.id', '=', 'sd.matter_id')
            ->leftjoin('m_staff as matter_staff', 'matter_staff.id', '=', 'm.staff_id')
            ->leftjoin('m_department as matter_department', 'matter_department.id', '=', 'm.department_id')
            ->leftjoin('m_staff as sta', 'sta.id', '=', 's.matter_staff_id')
            ->leftjoin('m_department as dep', 'dep.id', '=', 's.matter_department_id')
            ->leftjoin('t_delivery as d', 'd.id', '=', 'sd.delivery_id')
            ->leftjoin('t_return as rtn', 'rtn.id', '=', 'sd.return_id')
            ->leftjoin('t_warehouse_move as wm', 'wm.id', '=', 'rtn.warehouse_move_id')
            ->leftjoin('t_delivery as d2', 'd2.id', '=', 'wm.delivery_id')
            ->leftjoin('m_product as p', 'p.id', '=', 's.product_id')
            ->leftjoin('t_quote_detail as qd', 'qd.id', '=', 'sd.quote_detail_id')
            ->leftjoin('m_construction as c', 'c.id', '=', 's.construction_id')
            ->leftjoin('m_class_big as cb', 'cb.id', '=', 'p.class_big_id')
            ->leftjoin('m_class_middle as cm', 'cm.id', '=', 'p.class_middle_id')

            ->leftjoin('m_product as p1', function ($join) {
                $join->on('p1.id', '=', 's.product_id')
                    ->on('p1.construction_id_1', '=', 's.construction_id');
            })
            ->leftjoin('m_product as p2', function ($join) {
                $join->on('p2.id', '=', 's.product_id')
                    ->on('p2.construction_id_2', '=', 's.construction_id');
            })
            ->leftjoin('m_product as p3', function ($join) {
                $join->on('p3.id', '=', 's.product_id')
                    ->on('p3.construction_id_3', '=', 's.construction_id');
            })

            ->leftjoin('m_class_small as cs1', function ($join) {
                $join->on('cs1.id', '=', 'p1.class_small_id_1');
            })
            ->leftjoin('m_class_small as cs2', function ($join) {
                $join->on('cs2.id', '=', 'p2.class_small_id_2');
            })
            ->leftjoin('m_class_small as cs3', function ($join) {
                $join->on('cs3.id', '=', 'p3.class_small_id_3');
            })
            ->selectRaw('
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN s.matter_department_id
                ELSE matter_department.id
            END AS charge_department_id,
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN dep.department_name
                ELSE matter_department.department_name
            END AS charge_department_name,
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN s.matter_staff_id
                ELSE matter_staff.id
            END AS charge_staff_id,
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN sta.staff_name
                ELSE matter_staff.staff_name
            END AS charge_staff_name

            ,r.request_no 
            ,r.sales_category
            ,sd.sales_flg
            ,r.customer_id
            ,r.customer_name
            ,m.matter_no
            ,m.owner_name
            ,m.matter_name
            ,sd.delivery_date
            ,ifnull(d.delivery_no,d2.delivery_no) as delivery_no
            ,sd.sales_date
            ,sd.id as sales_detail_id
            ,s.construction_id
            ,c.construction_name
            ,p.class_big_id
            ,cb.class_big_name
            ,p.class_middle_id
            ,cm.class_middle_name
            ,ifnull(cs1.id,ifnull(cs2.id,cs3.id)) as class_small_id
            ,ifnull(cs1.class_small_name,ifnull(cs2.class_small_name,cs3.class_small_name)) as class_small_name
            ,s.product_id
            ,s.product_name
            ,s.product_code
            ,s.model
            ,s.unit
            ,sd.sales_quantity
            ,s.stock_unit
            ,s.stock_quantity
            ,s.cost_unit_price
            ,s.cost_unit_price * sd.sales_quantity as cost_amount
            ,sd.sales_unit_price
            ,sd.sales_unit_price * sd.sales_quantity as sales_amount
            ,((sd.sales_unit_price * sd.sales_quantity) - (s.cost_unit_price * sd.sales_quantity)) as gross_profit_amount
            ,(((sd.sales_unit_price * sd.sales_quantity) - (s.cost_unit_price * sd.sales_quantity)) / (sd.sales_unit_price * sd.sales_quantity)) * 100 as gross_profit_margin
            ,s.regular_price
            ,s.maker_id
            ,s.maker_name
            ,s.supplier_id
            ,s.supplier_name')
            ->where($where)
            ->whereIn('r.id', $request_id_list)
            ->whereIn('r.customer_id', $customer_id_list)
            ->get();

        return $data;
    }

    /**
     * 請求ID、売上期間で取得
     * @param int $request_id
     * @param $startDate
     * @param $endDate
     * @return type 検索結果データ
     */
    public function getSalesDetailByRequestId($requestId, $startDate, $endDate)
    {
        $where = [];
        $where[] = array('r.del_flg', '=', config('const.flg.off'));
        $where[] = array('s.del_flg', '=', config('const.flg.off'));
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));
        $where[] = array('sd.notdelivery_flg', '<>', config('const.notdeliveryFlg.val.invalid'));
        $where[] = array('r.id', '=', $requestId);
        if (!empty($startDate) && !empty($endDate)) {
            $where[] = array('sd.sales_date', '>=', $startDate);
            $where[] = array('sd.sales_date', '<=', $endDate);
        }

        $data = DB::table('t_request as r')
            // ->from('t_request as r')
            ->leftjoin('t_sales as s', 's.request_id', '=', 'r.id')
            ->leftjoin('t_sales_detail as sd', 's.id', '=', 'sd.sales_id')
            ->leftjoin('t_matter as m', 'm.id', '=', 'sd.matter_id')
            ->leftjoin('m_staff as matter_staff', 'matter_staff.id', '=', 'm.staff_id')
            ->leftjoin('m_department as matter_department', 'matter_department.id', '=', 'm.department_id')
            ->leftjoin('m_staff as sta', 'sta.id', '=', 's.matter_staff_id')
            ->leftjoin('m_department as dep', 'dep.id', '=', 's.matter_department_id')
            ->leftjoin('t_delivery as d', 'd.id', '=', 'sd.delivery_id')
            ->leftjoin('t_return as rtn', 'rtn.id', '=', 'sd.return_id')
            ->leftjoin('t_warehouse_move as wm', 'wm.id', '=', 'rtn.warehouse_move_id')
            ->leftjoin('t_delivery as d2', 'd2.id', '=', 'wm.delivery_id')
            ->leftjoin('m_product as p', 'p.id', '=', 's.product_id')
            ->leftjoin('t_quote_detail as qd', 'qd.id', '=', 'sd.quote_detail_id')
            ->leftjoin('m_construction as c', 'c.id', '=', 's.construction_id')
            ->leftjoin('m_class_big as cb', 'cb.id', '=', 'p.class_big_id')
            ->leftjoin('m_class_middle as cm', 'cm.id', '=', 'p.class_middle_id')

            ->leftjoin('m_product as p1', function ($join) {
                $join->on('p1.id', '=', 's.product_id')
                    ->on('p1.construction_id_1', '=', 's.construction_id');
            })
            ->leftjoin('m_product as p2', function ($join) {
                $join->on('p2.id', '=', 's.product_id')
                    ->on('p2.construction_id_2', '=', 's.construction_id');
            })
            ->leftjoin('m_product as p3', function ($join) {
                $join->on('p3.id', '=', 's.product_id')
                    ->on('p3.construction_id_3', '=', 's.construction_id');
            })

            ->leftjoin('m_class_small as cs1', function ($join) {
                $join->on('cs1.id', '=', 'p1.class_small_id_1');
            })
            ->leftjoin('m_class_small as cs2', function ($join) {
                $join->on('cs2.id', '=', 'p2.class_small_id_2');
            })
            ->leftjoin('m_class_small as cs3', function ($join) {
                $join->on('cs3.id', '=', 'p3.class_small_id_3');
            })
            ->selectRaw('
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN s.matter_department_id
                ELSE matter_department.id
            END AS charge_department_id,
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN dep.department_name
                ELSE matter_department.department_name
            END AS charge_department_name,
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN s.matter_staff_id
                ELSE matter_staff.id
            END AS charge_staff_id,
            CASE
                WHEN r.status <> '. config('const.flg.off'). '
                    THEN sta.staff_name
                ELSE matter_staff.staff_name
            END AS charge_staff_name

            ,r.request_no 
            ,r.sales_category
            ,sd.sales_flg
            ,r.customer_id
            ,r.customer_name
            ,m.matter_no
            ,m.owner_name
            ,m.matter_name
            ,sd.delivery_date
            ,ifnull(d.delivery_no,d2.delivery_no) as delivery_no
            ,sd.sales_date
            ,sd.id as sales_detail_id
            ,s.construction_id
            ,c.construction_name
            ,p.class_big_id
            ,cb.class_big_name
            ,p.class_middle_id
            ,cm.class_middle_name
            ,ifnull(cs1.id,ifnull(cs2.id,cs3.id)) as class_small_id
            ,ifnull(cs1.class_small_name,ifnull(cs2.class_small_name,cs3.class_small_name)) as class_small_name
            ,s.product_id
            ,s.product_name
            ,s.product_code
            ,s.model
            ,s.unit
            ,sd.sales_quantity
            ,s.stock_unit
            ,s.stock_quantity
            ,s.cost_unit_price
            ,sd.sales_unit_price
            ,s.regular_price
            ,s.maker_id
            ,s.maker_name
            ,s.supplier_id
            ,s.supplier_name')
            ->where($where)
            ->get();
            // ,s.cost_unit_price * sd.sales_quantity as cost_amount
            // ,sd.sales_unit_price * sd.sales_quantity as sales_amount
            // ,((sd.sales_unit_price * sd.sales_quantity) - (s.cost_unit_price * sd.sales_quantity)) as gross_profit_amount
            // ,(((sd.sales_unit_price * sd.sales_quantity) - (s.cost_unit_price * sd.sales_quantity)) / (sd.sales_unit_price * sd.sales_quantity)) * 100 as gross_profit_margin

        return $data;
    }

    /**
     * 売上画面用のデータを取得する
     * @param int $matterId
     * @param int $quoteId
     * @return type 検索結果データ
     */
    public function getEditSalesDetailData($matterId, $quoteId)
    {
        $whereSalesDetail = [];
        $whereSalesDetail[] = array('t_sales_detail.matter_id', '=', $matterId);
        $whereSalesDetail[] = array('t_sales_detail.quote_id', '=', $quoteId);
        $whereSalesDetail[] = array('t_sales_detail.del_flg', '=', config('const.flg.off'));


        $salesDetailQuery = $this
            ->leftjoin('t_delivery', 't_sales_detail.delivery_id', '=', 't_delivery.id')
            ->leftjoin('t_delivery AS offset_delivery', 't_sales_detail.offset_delivery_id', '=', 'offset_delivery.id')
            ->leftjoin('t_return', 't_sales_detail.return_id', '=', 't_return.id')
            ->leftjoin('t_warehouse_move', function ($join) use ($quoteId) {
                $join->on('t_return.warehouse_move_id', '=', 't_warehouse_move.id')
                    ->where([
                        ['t_warehouse_move.quote_id', '=', $quoteId]
                    ]);
            })
            ->leftjoin('t_order_detail', 't_warehouse_move.order_detail_id', '=', 't_order_detail.id')
            ->select([
                DB::raw('
                        CASE 
                            WHEN t_delivery.id IS NOT NULL 
                                THEN t_delivery.delivery_no
                            WHEN offset_delivery.id IS NOT NULL 
                                THEN offset_delivery.delivery_no
                            WHEN t_return.id IS NOT NULL 
                                THEN t_order_detail.order_no
                            ELSE 
                                null 
                        END AS delivery_no
                    '),
                't_sales_detail.id AS sales_detail_id',
                't_sales_detail.quote_id',
                't_sales_detail.quote_detail_id',
                't_sales_detail.sales_id',
                't_sales_detail.sales_update_date',
                't_sales_detail.sales_update_user',
                't_sales_detail.delivery_id',
                't_sales_detail.return_id',
                't_sales_detail.request_id',
                't_sales_detail.sales_flg',
                't_sales_detail.notdelivery_flg',
                't_sales_detail.offset_delivery_id',
                DB::raw('DATE_FORMAT(t_sales_detail.sales_date, "%Y/%m/%d") as sales_date'),
                't_sales_detail.sales_stock_quantity',
                't_sales_detail.sales_quantity',
                't_sales_detail.sales_unit_price',
                't_sales_detail.sales_total',
                't_sales_detail.cost_unit_price',
                't_sales_detail.cost_total',
                DB::raw('DATE_FORMAT(t_sales_detail.delivery_date, "%Y/%m/%d") as delivery_date'),
                't_sales_detail.delivery_quantity',
                't_sales_detail.memo',
            ])
            ->where($whereSalesDetail);

        $data = $salesDetailQuery->get();
        return $data;
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
                'matter_id' => $params['matter_id'],
                'quote_id' => $params['quote_id'],
                'sales_id' => $params['sales_id'],
                'sales_update_date' => Carbon::now(),
                'sales_update_user' => Auth::user()->id,
                'delivery_id' => $params['delivery_id'],
                'request_id' => $params['request_id'],
                'sales_flg' => $params['sales_flg'],
                'sales_date' => Carbon::now(),
                'sales_stock_quantity' => $params['sales_stock_quantity'],
                'sales_quantity' => $params['sales_quantity'],
                'sales_unit_price' => $params['sales_unit_price'],
                'sales_total' => $params['sales_total'],
                'cost_unit_price' => $params['cost_unit_price'],
                'cost_total' => $params['cost_total'],
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
     * 請求IDから売上合計取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceData($request_id, $customer_id = null, $request_s_day = null, $request_e_day = null)
    {
        // Where句作成
        $where = [];
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));
        $where[] = array('sd.notdelivery_flg', '<>', 2);
        if ($request_id == null) {
            $where[] = array('m.customer_id', '=', $customer_id);
            $where[] = array('sd.sales_date', '>=', $request_s_day);
            $where[] = array('sd.sales_date', '<=', $request_e_day);
        } else {
            $where[] = array('s.request_id', '=', $request_id);
        }

        // データ取得
        $data = $this
            ->from('t_sales_detail as sd')
            ->leftjoin('t_sales as s', 's.id', '=', 'sd.sales_id')
            ->leftjoin('t_matter as m', 'm.id', '=', 'sd.matter_id')
            ->where($where)
            ->groupBy('s.request_id')
            ->selectRaw(
                'sum(sd.sales_total) as sales_total'
            )
            ->first();

        return $data;
    }
    
    /**
     * 請求IDから案件ごとの売上情報取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceDetailData($request_id, $customer_id = null, $request_s_day, $request_e_day)
    {
        // Where句作成
        $where = [];
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));
        $where[] = array('s.request_id', '=', $request_id);
        $where[] = array('sd.sales_date', '>=', $request_s_day);
        $where[] = array('sd.sales_date', '<=', $request_e_day);
        $where[] = array('sd.notdelivery_flg', '<>', 2);
        if ($request_id == null) {
            $where[] = array('m.customer_id', '=', $customer_id);
        }


        // データ取得
        $data = $this
            ->from('t_sales_detail as sd')
            ->leftjoin('t_sales as s', 's.id', '=', 'sd.sales_id')
            ->leftjoin('t_matter as m', 'm.id', '=', 'sd.matter_id')
            ->where($where)
            ->groupBy('m.matter_no')
            ->orderBy('m.matter_no')
            ->selectRaw(
                'm.matter_no,
                 min(m.owner_name) as matter_name,
                 sum(CEILING(sd.sales_unit_price * sd.sales_quantity)) as amount'
            )
            ->get();
        return $data;
    }


    /**
     * 得意先IDと売上期間から案件ごとの金額を取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceDetailDataByCustomerId($customer_id, $request_s_day, $request_e_day)
    {
        // Where句作成
        $where = [];
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));
        $where[] = array('m.customer_id', '=', $customer_id);
        $where[] = array('sd.sales_date', '>=', $request_s_day);
        $where[] = array('sd.sales_date', '<=', $request_e_day);
        $where[] = array('sd.notdelivery_flg', '<>', 2);

        // データ取得
        $data = $this
            ->from('t_sales_detail as sd')
            ->leftjoin('t_sales as s', 's.id', '=', 'sd.sales_id')
            ->leftjoin('t_matter as m', 'm.id', '=', 'sd.matter_id')
            ->where($where)
            ->groupBy('m.matter_no')
            ->orderBy('m.matter_no')
            ->selectRaw(
                'm.matter_no,
                 min(m.owner_name) as matter_name,
                 sum(CEILING(sd.sales_unit_price * sd.sales_quantity)) as amount'
            )
            ->get();
        return $data;
    }

    /**
     * 指定売上ID配下の未確定かつ未売以外の販売単価を変更する
     * 売上期間内の販売総額と仕入総額を返す
     * @param $requestId
     * @param $salesId
     * @param $salesUnitPrice
     * @return $result          当月の販売総額と仕入総額
     */
    public function updateSalesUnitPrice($requestId, $salesId, $salesUnitPrice)
    {

        $result = [
            'sales_total'   => 0,
            'cost_total'    => 0,
        ];

        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('sales_id', '=', $salesId);
        $where[] = array('sales_flg', '!=', config('const.salesFlg.val.not_sales'));

        $SystemUtil = new SystemUtil();

        $dataList = $this
            ->where($where)
            ->whereNull('sales_update_date')
            ->get();

        // 売上日が空の「未売」以外の販売単価の更新
        foreach ($dataList as $data) {
            $updateData = [];
            $updateData['sales_unit_price'] = $salesUnitPrice;
            $updateData['sales_total'] = $SystemUtil->calcTotal($data->sales_quantity, $updateData['sales_unit_price'], false);
            $this->updateById($data->id, $updateData);
        }



        // 売上にセットする、販売総額と仕入総額を計算
        $Requests = new Requests();
        $requestData = $Requests->getById($requestId);

        $calcWhere = [];
        $calcWhere[] = array('del_flg', '=', config('const.flg.off'));
        $calcWhere[] = array('sales_id', '=', $salesId);
        $calcWhere[] = array('sales_date', '>=', $requestData->request_s_day);
        $calcWhere[] = array('sales_date', '<=', $requestData->request_e_day);

        $calcDataList = $this
            ->where($calcWhere)
            ->whereNull('sales_update_date')
            ->get();

        // 
        foreach ($calcDataList as $data) {
            if ($data->notdelivery_flg !== config('const.notdeliveryFlg.val.invalid')) {
                // 未売は単価が0なので合計して問題無い
                $result['sales_total'] += $data->sales_total;
                $result['cost_total'] += $data->cost_total;
            }
        }
        return $result;
    }

    /**
     * 請求の売上確定状態の解除
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateSalesStatusByRequestId($requestId)
    {
        $result = false;
        try {
            // Where句作成
            $where = [];
            $where[] = array('request_id', '=', $requestId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

            $items = [];
            $items['sales_update_date'] = null;
            $items['sales_update_user'] = 0;
            $items['request_id']        = 0;
            $items['update_user']       = Auth::user()->id;
            $items['update_at']         = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where($where)
                ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 売上IDから取得
     *
     * @param [type] $sales_id
     * @return void
     */
    public function getBySalesId($sales_id)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('sales_id', '=', $sales_id);

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 売上IDから取得
     *
     * @param [type] $sales_id
     * @return void
     */
    public function getListByQuoteDetailId($quoteDetailId)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_detail_id', '=', $quoteDetailId);

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 見積明細IDから取得
     *
     * @param [type] $quoteDetailId
     * @return void
     */
    public function getByQuoteDetailId($quoteDetailId)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_detail_id', '=', $quoteDetailId);

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }


    /**
     * 売上IDから取得
     *
     * @param [type] $salesId       売上ID
     * @param [type] $costUnitPrice 調整単価
     * @return void
     */
    public function updateCostUnitPriceBySalesId($params)
    {
        $result = false;
        try {
            // 条件            
            $where = [];
            $where[] = array('sales_id', '=', $params['id']);
            $where[] = array('cost_unit_price', '<>', $params['cost_unit_price']);

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

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 請求IDから取得
     * 仕入金額の合計
     *
     * @param [type] $requestId
     * @return void
     */
    public function getCostTotalByRequestId($requestId)
    {
        $where = [];
        $where[] = array('sd.request_id', '=', $requestId);
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_sales_detail AS sd')
            ->leftJoin('t_sales AS sa', function ($join) {
                $join
                    ->on('sd.sales_id', '=', 'sa.id')
                    ->where('sa.invalid_unit_price_flg', '=', config('const.flg.off'));
            })
            ->selectRaw('
                    sd.request_id,
                    SUM(sd.cost_total) AS cost_total
                ')
            ->where($where)
            ->groupBy('sd.request_id')
            ->first();

        return $data;
    }

    /**
     * 見積IDで検索して結果を真偽値で返す
     *
     * @param [type] $quoteId
     * @return bool
     */
    public function existsByQuoteId($quoteId)
    {
        $cnt = $this->where([
            ['del_flg', config('const.flg.off')],
            ['quote_id', $quoteId]
        ])->count();

        return ($cnt > 0);
    }
    
    /**
     * 見積明細ID群で取得
     *
     * @param array $quoteDetailIdList 見積明細IDの配列
     * @return void
     */
    public function getByQuoteDetailIdList($quoteDetailIdList)
    {
        $data = $this
            ->where('del_flg', config('const.flg.off'))
            ->whereIn('quote_detail_id', $quoteDetailIdList)
            ->get();

        return $data;
    }

    /**
     * 案件IDで取得
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
     * 【案件詳細用】スケジューラ-売上確定
     *
     * @param [type] $matterId 案件ID
     * @return void
     */
    public function getSchedulerSalesForMatterDetail($matterId)
    {
        $data = DB::table('t_delivery')
            ->leftJoin('t_sales_detail', function ($join) {
                $join
                    ->on('t_delivery.id', '=', 't_sales_detail.delivery_id')
                    ->where('t_sales_detail.del_flg', '=', config('const.flg.off'));
            })
            ->where([
                ['t_delivery.matter_id', '=', $matterId],
                ['t_delivery.del_flg', '=', config('const.flg.off')],
            ])
            ->whereNull('t_sales_detail.id')
            ->select(
                DB::raw('DATE_FORMAT(t_delivery.delivery_date, \'%Y/%m/%d\') AS delivery_date')
            )
            ->groupBy('t_delivery.delivery_date')
            ->union(
                DB::table('t_return')
                    ->leftJoin('t_warehouse_move', 't_return.warehouse_move_id', '=', 't_warehouse_move.id')
                    ->leftJoin('t_sales_detail', function ($join) {
                        $join
                            ->on('t_return.id', '=', 't_sales_detail.return_id')
                            ->where('t_sales_detail.del_flg', '=', config('const.flg.off'));
                    })
                    ->where([
                        ['t_return.del_flg', '=', config('const.flg.off')],
                        ['t_return.matter_id', '=', $matterId],
                        ['t_return.return_kbn', '!=', config('const.returnKbn.keep')],
                        ['t_return.return_finish', '=', config('const.returnFinish.val.completed')],
                    ])
                    ->whereNull('t_sales_detail.id')
                    ->select(
                        DB::raw('DATE_FORMAT(to_product_move_at, \'%Y/%m/%d\') AS delivery_date')
                    )
                    ->groupBy('t_warehouse_move.to_product_move_at')
            )
            ->pluck('delivery_date');
        return $data->toArray();
    }

    /**
     * 案件番号を元に未確定データの存在確認
     *
     * @param int $matterId 案件ID
     * @return boolean true:存在する false：存在しない
     */
    public function hasNotSalesByMatter($matterId) {
        $where = [];
        $where[] = array('matter_id', '=', $matterId);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // 存在チェック
        $result = $this
            ->where($where)
            ->whereNull('sales_update_date')
            ->exists();
            
        return $result;
    }

    /**
     * 請求IDから取得
     *
     * @param [type] $requestId
     * @return void
     */
    public function getByRequestId($requestId)
    {
        $where = [];
        $where[] = array('request_id', '=', $requestId);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 請求ID、売上期間開始日、売上期間終了日から取得
     *
     * @param [type] $requestId
     * @param [type] $requestSDay
     * @param [type] $requestEDay
     * @return void
     */
    public function getSalesDetailByTargetPeriod($requestId, $requestSDay, $requestEDay)
    {
        $where = [];
        $where[] = array('request_id', '=', $requestId);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $where[] = array('sales_date', '>=', $requestSDay);
        $where[] = array('sales_date', '<=', $requestEDay);

        $data = $this
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 相殺データを削除する
     *
     * @param [type] $requestId
     * @return void
     */
    public function deleteOffsetSalesByRequestId($requestId)
    {
        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('t_sales.request_id', '=', $requestId);
            $where[] = array('t_sales_detail.notdelivery_flg', '<>', config('const.notdeliveryFlg.val.default'));

            // 相殺データ取得
            $deleteDataList = $this
                ->leftJoin('t_sales', 't_sales.id', '=', 't_sales_detail.sales_id')
                ->where($where)
                ->select([
                    't_sales_detail.id',
                ])
                ->get();
    
            if($deleteDataList->count() >= 1){

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                foreach ($deleteDataList as $row) {
                    $LogUtil->putById($this->table, $row['id'], config('const.logKbn.delete'));
                }

                $deleteCnt = $this
                    ->leftJoin('t_sales', 't_sales.id', '=', 't_sales_detail.sales_id')
                    ->where($where)
                    ->delete();
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $deleteCnt;
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
            $where[] = array('t_sales_detail.return_id', '=', $returnId);

            // データ取得
            $deleteDataList = $this
                ->where($where)
                ->select([
                    't_sales_detail.id',
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
