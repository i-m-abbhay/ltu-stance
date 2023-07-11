<?php

namespace App\Models;

use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;
use DB;
use Illuminate\Support\Facades\Log;

/**
 * 売上
 */
class Sales extends Model
{
    // テーブル名
    protected $table = 't_sales';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'matter_id',
        'matter_department_id',
        'matter_staff_id',
        'quote_id',
        'quote_detail_id',
        'request_id',
        'sales_flg',
        'construction_id',
        'layer_flg',
        'parent_quote_detail_id',
        'seq_no',
        'depth',
        'tree_path',
        'sales_use_flg',
        'product_id',
        'product_code',
        'product_name',
        'model',
        'maker_id',
        'maker_name',
        'supplier_id',
        'supplier_name',
        'quote_quantity',
        'min_quantity',
        'stock_quantity',
        'unit',
        'stock_unit',
        'regular_price',
        'cost_kbn',
        'sales_kbn',
        'cost_unit_price',
        'update_cost_unit_price',
        'update_cost_unit_price_d',
        'sales_unit_price',
        'cost_makeup_rate',
        'sales_makeup_rate',
        'gross_profit_rate',
        'profit_total',
        'status',
        'apply_staff_id',
        'apply_date',
        'status_dep',
        'approval_staff_id',
        'approval_date',
        'bk_sales_unit_price',
        'details_p_flg',
        'details_c_flg',
        'selling_p_flg',
        'selling_c_flg',
        'invalid_unit_price_flg',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];


    /**
     * IDでデータ取得
     *
     * @param string $id ID
     * @return void
     */
    public function getById($id)
    {
        // Where句作成
        $where = [];
        $where[] = array('id', '=', $id);
        //$where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->first();

        return $data;
    }

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
     * @param int $salesId 売上ID
     * @param array $params 1行の配列
     * @return void
     */
    public function updateById($salesId, $params)
    {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('id', '=', $salesId);

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
     * 請求IDで削除(物理)
     * 紐づく売上明細も削除する
     * @param $requestId
     */
    public function deleteByRequestId($requestId)
    {
        $SalesDetail = new SalesDetail();
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
                    $LogUtil->putByData($deleteData, config('const.logKbn.delete'), $this->table);
                }

                $deleteCnt = $this
                    ->where($where)
                    ->delete();

                if ($deleteCnt >= 1) {
                    // 売上明細の削除
                    foreach ($deleteDataList as $record) {
                        $SalesDetail->deleteBySalesId($record->id);
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
                'matter_id' => $params['matter_id'],
                'matter_department_id' => $params['matter_department_id'],
                'matter_staff_id' => $params['matter_staff_id'],
                'quote_id' => $params['quote_id'],
                'sales_flg' => $params['sales_flg'],
                'layer_flg' => $params['layer_flg'],
                'tree_path' => $params['tree_path'],
                'sales_use_flg' => $params['sales_use_flg'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'product_name' => $params['product_name'],
                'model' => $params['model'],
                'maker_id' => $params['maker_id'],
                'maker_name' => $params['maker_name'],
                'min_quantity' => $params['min_quantity'],
                'unit' => $params['unit'],
                'stock_unit' => $params['stock_unit'],
                'regular_price' => $params['regular_price'],
                'cost_kbn' => $params['cost_kbn'],
                'sales_kbn' => $params['sales_kbn'],
                'cost_unit_price' => $params['cost_unit_price'],
                'sales_unit_price' => $params['sales_unit_price'],
                'gross_profit_rate' => $params['gross_profit_rate'],
                'profit_total' => $params['profit_total'],
                'status' => $params['status'],
                // 'bk_cost_unit_price' => $params['bk_cost_unit_price'],
                'bk_sales_unit_price' => $params['bk_sales_unit_price'],
                // 'bk_cost_makeup_rate' => $params['bk_cost_makeup_rate'],
                // 'bk_sales_makeup_rate' => $params['bk_sales_makeup_rate'],
                // 'bk_gross_profit_rate' => $params['bk_gross_profit_rate'],
                // 'bk_profit_total' => $params['bk_profit_total'],
                'details_p_flg' => $params['details_p_flg'],
                'details_c_flg' => $params['details_c_flg'],
                'selling_p_flg' => $params['selling_p_flg'],
                'selling_c_flg' => $params['selling_c_flg'],
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
     * 請求IDから請求書の明細一覧情報取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceListData($request_id,$request_s_day,$request_e_day)
    {
        $request_s_day="'" . $request_s_day . "'";
        $request_e_day="'" . $request_e_day . "'";
        
        $data = $this->from(DB::raw("
            ((select distinct
            s.id as sales_id
           , sd.id as sales_detail_id
           , m.matter_no
           , m.owner_name as matter_name
           , ifnull(sd.delivery_date,sd.sales_date) as delivery_date
           , d.delivery_no
           , ifnull(s.product_id,qd.product_id) as product_id
           , ifnull(s.product_code,qd.product_code) as product_code
           , ifnull(s.product_name,qd.product_name) as product_name
           , ifnull(s.model,qd.model) as model
           , ifnull(s.unit,qd.unit) as unit
           , case when ifnull(s.layer_flg, qd.layer_flg) = 1 then ifnull(qd.quote_quantity,sd.sales_quantity) else ifnull(sd.sales_quantity,qd.quote_quantity) end as sales_quantity
           , ifnull(sd.sales_unit_price,qd.sales_unit_price) as sales_unit_price
           , ifnull(sd.sales_total,qd.sales_total) as sales_total
           , sd.memo
           , ifnull(s.details_p_flg,qd.row_print_flg) as details_p_flg
           , ifnull(s.details_c_flg,qd.row_print_flg) as details_c_flg
           , ifnull(s.selling_p_flg,qd.price_print_flg) as selling_p_flg
           , ifnull(s.selling_c_flg,qd.price_print_flg) as selling_c_flg
           , case when qd.layer_flg = 1 AND qd.sales_use_flg = 0 then qd.row_print_flg else null end as row_print_flg
		   , case when qd.layer_flg = 1 AND qd.sales_use_flg = 0 then qd.price_print_flg else null end as price_print_flg
           , q.id as quote_id
           , qd.id as quote_detail_id
           , ifnull(s.construction_id,qd.construction_id) as construction_id
           , ifnull(s.layer_flg,qd.layer_flg) as layer_flg
           , ifnull(s.parent_quote_detail_id,qd.parent_quote_detail_id) as parent_quote_detail_id
           , ifnull(s.seq_no,qd.seq_no) as seq_no
           , ifnull(s.depth,qd.depth) as depth
           , ifnull(s.tree_path,qd.tree_path) as tree_path
           , ifnull(s.sales_use_flg,qd.sales_use_flg) as sales_use_flg
           , c.construction_name 
           from t_quote_detail as qd
           left join t_quote as q on q.quote_no = qd.quote_no
           left join t_matter as m on m.matter_no = q.matter_no 
           left join m_construction as c on c.id = qd.construction_id
           left join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . "
           left join t_sales_detail as sd on sd.sales_id = s.id and sd.del_flg = 0 and sd.sales_flg <> 2 and sd.notdelivery_flg <> 2
           left join t_delivery as d on d.id = sd.delivery_id
           where 
           (
           qd.quote_no in (select quote_no from t_quote_detail as qd inner join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . ")
           or
           qd.quote_no in (select quote_no from t_quote_detail as qd inner join t_sales as s on s.parent_quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . ")
           )
           and
          (
          (qd.depth <= (select max(s.depth) from t_quote_detail as qd inner join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . ") or s.id is not null)
          or
          (qd.depth <= (select max(s.depth) from t_quote_detail as qd inner join t_sales as s on s.parent_quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . ") or s.id is not null)
          )
          and
          (
           qd.construction_id in (select s.construction_id from t_quote_detail as qd inner join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . ")
           or
           qd.construction_id in (select s.construction_id from t_quote_detail as qd inner join t_sales as s on s.parent_quote_detail_id = qd.id and s.del_flg = 0 and s.request_id = " . $request_id . ")
         )
           and
           qd.quote_version = 0
           and
           qd.del_flg = 0
           and
		   (
		   	(s.request_id = " . $request_id . " and sd.del_flg = 0 and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . ") or s.id is null
		   ) 
         )
         union all
         (
           select
            s.id as sales_id
           , sd.id as sales_detail_id
           , m.matter_no
           , m.owner_name as matter_name
           , ifnull(sd.delivery_date,sd.sales_date) as delivery_date
           , d.delivery_no
           , s.product_id as product_id
           , s.product_code as product_code
           , s.product_name as product_name
           , s.model as model
           , s.unit as unit
           , ifnull(sd.sales_quantity,0) as sales_quantity
           , ifnull(sd.sales_unit_price,0) as sales_unit_price
           , CEILING(ifnull(sd.sales_quantity * sd.sales_unit_price,0)) as sales_total
           , sd.memo
           , s.details_p_flg
           , s.details_c_flg
           , s.selling_p_flg
           , s.selling_c_flg
           , null as row_print_flg
           , null as price_print_flg
           , sd.quote_id as quote_id
           , sd.quote_detail_id as quote_detail_id
           , s.construction_id
           , s.layer_flg
           , s.parent_quote_detail_id
           , s.seq_no
           , s.depth
           , s.tree_path
           , s.sales_use_flg
           , c.construction_name 
           from t_sales as s
           left join t_sales_detail as sd on sd.sales_id = s.id and sd.sales_flg <> 2 and sd.notdelivery_flg <> 2
           left join t_matter as m on m.id = s.matter_id 
           left join m_construction as c on c.id = s.construction_id
           left join t_delivery as d on d.id = sd.delivery_id
           where s.request_id = " . $request_id . "
           and s.del_flg = 0
           and sd.del_flg = 0
           and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . "
           )
            ) as t
        "))->select("*")->distinct()->orderBy('matter_no', 'asc')->orderBy('tree_path', 'asc')->orderBy('seq_no', 'asc')->get();


        //余分な階層データを削除
        $treeList = [];
        $treeList[] = 0;
        if ($data != null) {

            for ($i = 0; $i < count($data); $i++) {
                if ($data[$i]['sales_id'] !== null && $data[$i]['sales_detail_id'] !== null) {
                    $tmpList = explode('_', $data[$i]['tree_path']);
                    foreach ($tmpList as $row) {
                        $treeList[] = $row;
                    }
                    $treeList[] = $data[$i]['quote_detail_id'];
                }
            }

            $data = $data->whereIn('quote_detail_id',$treeList);
         
        }

        return $data;
    }

    /**
     * 請求IDから請求書の明細一覧情報取得
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceListDataByCustomerId($customer_id,$request_s_day,$request_e_day, $status)
    {
        $rtnRequestSDay = Carbon::parse($request_s_day)->format('Y/m/d');
        $rtnRequestEDay = Carbon::parse($request_e_day)->format('Y/m/d');

        $where = [];
        $where[] = array('rtn.del_flg', '=', config('const.flg.off'));
        $where[] = array('rtn.return_finish', '=', config('const.returnFinish.val.completed'));
        $where[] = array('rtn.customer_id', '=', $customer_id);
        $where[] = array('wm.delivery_id', '<>', 0);
        $where[] = array('wm.move_date', '>=', $rtnRequestSDay);
        $where[] = array('wm.move_date', '<=', $rtnRequestEDay);
        // 売上に保存されていない返品データ
        $returnQuery = DB::table('t_return AS rtn')
                ->join('t_warehouse_move AS wm', 'wm.id', '=', 'rtn.warehouse_move_id')
                ->leftJoin('t_matter AS m', 'm.id', '=', 'wm.matter_id')
                // ->leftJoin('m_customer AS c', 'c.id', '=', 'm.customer_id')
                ->leftJoin('t_sales_detail AS sd', function($join) {
                    $join
                        ->on('sd.return_id', '=', 'rtn.id')
                        ->where('sd.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_sales AS s', function ($join) {
                    $join
                        ->on('s.quote_detail_id', '=', 'wm.quote_detail_id')
                        ->on('sd.sales_id', '=', 's.id')
                        // ->on('s.request_id', '=', 'r.id')
                        ->where('s.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('t_request AS r', 'r.id', '=', 's.request_id')
                ->leftJoin('t_quote_detail AS qd', 'qd.id', '=', 'wm.quote_detail_id')
                ->leftJoin('t_quote AS q', 'q.quote_no', '=', 'qd.quote_no')
                ->leftJoin('m_department AS dep', 'dep.id', '=', 'm.department_id')
                ->leftJoin('m_staff AS staff', 'staff.id', '=', 'm.staff_id')
                ->leftJoin('m_construction AS con', 'con.id', '=', 'qd.construction_id')
                ->leftJoin('t_delivery AS d', 'd.id', '=', 'sd.delivery_id')
                ->where($where)
                ->whereNotNull('wm.delivery_id')
                ->whereNull('sd.id')
                ->select([
                    DB::raw('1 AS rtn_flg'),
                    's.id as sales_id',
                    'sd.id as sales_detail_id',
                    'm.matter_no',
                    'm.owner_name as matter_name',
                    DB::raw('ifnull(sd.delivery_date,sd.sales_date) as delivery_date'),
                    'd.delivery_no',
                    DB::raw('ifnull(s.product_id,qd.product_id) as product_id'),
                    DB::raw('ifnull(s.product_code,qd.product_code) as product_code'),
                    DB::raw('ifnull(s.product_name,qd.product_name) as product_name'),
                    DB::raw('ifnull(s.model,qd.model) as model'),
                    DB::raw('ifnull(s.unit,qd.unit) as unit'),
                    DB::raw('0 - ifnull(rtn.return_quantity, 0) as sales_quantity'),
                    DB::raw('ifnull(sd.sales_unit_price, qd.sales_unit_price) as sales_unit_price'),
                    // DB::raw('0 - ifnull(sd.sales_total, qd.sales_total)as sales_total'),
                    DB::raw('0 - ifnull(sd.sales_unit_price, qd.sales_unit_price) * ifnull(rtn.return_quantity, 0) as sales_total'),
                    'sd.memo',
                    DB::raw('ifnull(s.details_p_flg,qd.row_print_flg) as details_p_flg'),
                    DB::raw('ifnull(s.details_c_flg,qd.row_print_flg) as details_c_flg'),
                    DB::raw('ifnull(s.selling_p_flg,qd.price_print_flg) as selling_p_flg'),
                    DB::raw('ifnull(s.selling_c_flg,qd.price_print_flg) as selling_c_flg'),
                    DB::raw('
                        case
                            when qd.layer_flg = 1 AND qd.sales_use_flg = 0
                                then qd.row_print_flg 
                            else null 
                        end as row_print_flg
                    '),
                    DB::raw('
                        case
                            when qd.layer_flg = 1 AND qd.sales_use_flg = 0
                                then qd.price_print_flg
                            else null
                        end as price_print_flg
                    '),
                    'q.id as quote_id',
                    'qd.id as quote_detail_id',
                    DB::raw('ifnull(s.construction_id,qd.construction_id) as construction_id'),
                    DB::raw('ifnull(s.layer_flg,qd.layer_flg) as layer_flg'),
                    DB::raw('ifnull(s.parent_quote_detail_id,qd.parent_quote_detail_id) as parent_quote_detail_id'),
                    DB::raw('ifnull(s.seq_no,qd.seq_no) as seq_no'),
                    DB::raw('ifnull(s.depth,qd.depth) as depth'),
                    DB::raw('ifnull(s.tree_path,qd.tree_path) as tree_path'),
                    DB::raw('ifnull(s.sales_use_flg,qd.sales_use_flg) as sales_use_flg'),
                    'con.construction_name',
                ])
                // ->distinct()
                // ->get()
                ;
        
        $request_s_day="'" . $request_s_day . "'";
        $request_e_day="'" . $request_e_day . "'";

        $mainQuery = $this->from(DB::raw("
            ((select distinct
            0 AS rtn_flg
           , s.id as sales_id
           , sd.id as sales_detail_id
           , m.matter_no
           , m.owner_name as matter_name
           , ifnull(sd.delivery_date,sd.sales_date) as delivery_date
           , d.delivery_no
           , ifnull(s.product_id,qd.product_id) as product_id
           , ifnull(s.product_code,qd.product_code) as product_code
           , ifnull(s.product_name,qd.product_name) as product_name
           , ifnull(s.model,qd.model) as model
           , ifnull(s.unit,qd.unit) as unit
           , case when ifnull(s.layer_flg, qd.layer_flg) = 1 then ifnull(qd.quote_quantity,sd.sales_quantity) else ifnull(sd.sales_quantity,qd.quote_quantity) end as sales_quantity
           , ifnull(sd.sales_unit_price,qd.sales_unit_price) as sales_unit_price
           , ifnull(sd.sales_total,qd.sales_total) as sales_total
           , sd.memo
           , ifnull(s.details_p_flg,qd.row_print_flg) as details_p_flg
           , ifnull(s.details_c_flg,qd.row_print_flg) as details_c_flg
           , ifnull(s.selling_p_flg,qd.price_print_flg) as selling_p_flg
           , ifnull(s.selling_c_flg,qd.price_print_flg) as selling_c_flg
           , case when qd.layer_flg = 1 AND qd.sales_use_flg = 0 then qd.row_print_flg else null end as row_print_flg
		   , case when qd.layer_flg = 1 AND qd.sales_use_flg = 0 then qd.price_print_flg else null end as price_print_flg
           , q.id as quote_id
           , qd.id as quote_detail_id
           , ifnull(s.construction_id,qd.construction_id) as construction_id
           , ifnull(s.layer_flg,qd.layer_flg) as layer_flg
           , ifnull(s.parent_quote_detail_id,qd.parent_quote_detail_id) as parent_quote_detail_id
           , ifnull(s.seq_no,qd.seq_no) as seq_no
           , ifnull(s.depth,qd.depth) as depth
           , ifnull(s.tree_path,qd.tree_path) as tree_path
           , ifnull(s.sales_use_flg,qd.sales_use_flg) as sales_use_flg
           , c.construction_name 
           from t_quote_detail as qd
           left join t_quote as q on q.quote_no = qd.quote_no
           left join t_matter as m on m.matter_no = q.matter_no 
           left join m_construction as c on c.id = qd.construction_id
           left join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 and m.customer_id = " . $customer_id . "
           left join t_sales_detail as sd on sd.sales_id = s.id and sd.del_flg = 0 and sd.sales_flg <> 2 and sd.notdelivery_flg <> 2 and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . "
           left join t_delivery as d on d.id = sd.delivery_id
           where 
            (
                qd.quote_no in (select quote_no from t_quote_detail as qd inner join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 inner join t_sales_detail as sd on s.id = sd.sales_id and sd.del_flg = 0 inner join t_matter as m on m.id= s.matter_id  where m.customer_id = " . $customer_id . " and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . " )
                or
                qd.quote_no in (select quote_no from t_quote_detail as qd inner join t_sales as s on s.parent_quote_detail_id = qd.id and s.del_flg = 0 inner join t_sales_detail as sd on s.id = sd.sales_id and sd.del_flg = 0 inner join t_matter as m on m.id= s.matter_id  where m.customer_id = " . $customer_id . " and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . " )
            )
            and
            (
                (qd.depth <= (select max(s.depth) from t_quote_detail as qd inner join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 inner join t_sales_detail as sd on s.id = sd.sales_id and sd.del_flg = 0 inner join t_matter as m on m.id= s.matter_id  where m.customer_id = " . $customer_id . " and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . " ) or s.id is not null)
                or
                (qd.depth <= (select max(s.depth) from t_quote_detail as qd inner join t_sales as s on s.parent_quote_detail_id = qd.id and s.del_flg = 0 inner join t_sales_detail as sd on s.id = sd.sales_id and sd.del_flg = 0 inner join t_matter as m on m.id= s.matter_id  where m.customer_id = " . $customer_id . " and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . " ) or s.id is not null)
            )
            and
            (
                qd.construction_id in (select s.construction_id from t_quote_detail as qd inner join t_sales as s on s.quote_detail_id = qd.id and s.del_flg = 0 inner join t_sales_detail as sd on s.id = sd.sales_id and sd.del_flg = 0 inner join t_matter as m on m.id= s.matter_id  where m.customer_id = " . $customer_id . " and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . " )
                or
                qd.construction_id in (select s.construction_id from t_quote_detail as qd inner join t_sales as s on s.parent_quote_detail_id = qd.id and s.del_flg = 0 inner join t_sales_detail as sd on s.id = sd.sales_id and sd.del_flg = 0 inner join t_matter as m on m.id= s.matter_id  where m.customer_id = " . $customer_id . " and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . " )
            )
            and
                qd.quote_version = 0
            and
                qd.del_flg = 0

            and
            (
                (m.customer_id = " . $customer_id . ") or s.id is null
            )

         )
         union all
         (
           select
            0 AS rtn_flg
           , s.id as sales_id
           , sd.id as sales_detail_id
           , m.matter_no
           , m.owner_name as matter_name
           , ifnull(sd.delivery_date,sd.sales_date) as delivery_date
           , d.delivery_no
           , s.product_id as product_id
           , s.product_code as product_code
           , s.product_name as product_name
           , s.model as model
           , s.unit as unit
           , ifnull(sd.sales_quantity,0) as sales_quantity
           , ifnull(sd.sales_unit_price,0) as sales_unit_price
           , CEILING(ifnull(sd.sales_quantity * sd.sales_unit_price,0)) as sales_total
           , sd.memo
           , s.details_p_flg
           , s.details_c_flg
           , s.selling_p_flg
           , s.selling_c_flg
           , null as row_print_flg
           , null as price_print_flg
           , sd.quote_id as quote_id
           , sd.quote_detail_id as quote_detail_id
           , s.construction_id
           , s.layer_flg
           , s.parent_quote_detail_id
           , s.seq_no
           , s.depth
           , s.tree_path
           , s.sales_use_flg
           , c.construction_name 
           from t_sales as s
           left join t_sales_detail as sd on sd.sales_id = s.id and sd.sales_flg <> 2 and sd.notdelivery_flg <> 2
           left join t_matter as m on m.id = s.matter_id 
           left join m_construction as c on c.id = s.construction_id
           left join t_delivery as d on d.id = sd.delivery_id
           where m.customer_id = " . $customer_id . "
           and sd.sales_date >= " . $request_s_day . " and sd.sales_date <= " . $request_e_day . "
           and s.del_flg = 0
           and sd.del_flg = 0
           )
            ) as t
        "))
        // ->mergeBindings($returnQuery)
        ->select("*")
        ->distinct()
        // ->orderBy('matter_no', 'asc')
        // ->orderBy('tree_path', 'asc')
        // ->orderBy('seq_no', 'asc')
        // ->get()
        ;

        if ($status == config('const.requestStatus.val.unprocessed')) {
            // 未確定の場合は、売上に保存されていない返品も表示する
            $data = $mainQuery->UnionAll($returnQuery)
                ->orderBy('matter_no', 'asc')
                ->orderBy('tree_path', 'asc')
                ->orderBy('seq_no', 'asc')
                ->get();
        } else {
            $data = $mainQuery
                ->orderBy('matter_no', 'asc')
                ->orderBy('tree_path', 'asc')
                ->orderBy('seq_no', 'asc')
                ->get();
        }


        //余分な階層データを削除
        $treeList = [];
        $treeList[] = 0;
        if ($data != null) {

            for ($i = 0; $i < count($data); $i++) {
                if ($data[$i]['sales_id'] !== null && $data[$i]['sales_detail_id'] !== null) {
                    $tmpList = explode('_', $data[$i]['tree_path']);
                    foreach ($tmpList as $row) {
                        $treeList[] = $row;
                    }
                    $treeList[] = $data[$i]['quote_detail_id'];
                }
            }

            $data = $data->whereIn('quote_detail_id',$treeList);
         
        }

        return $data;
    }

    /**
     * 請求IDから請求書の明細一覧情報取得(当月以外)
     * @param  $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getInvoiceListDataQuote($request_id, $customer_id = null)
    {
        // Where句作成
        $where = [];
        $where[] = array('s.del_flg', '=', config('const.flg.off'));
        $where[] = array('s.request_id', '=', $request_id);
        if ($request_id == null) {
            $where[] = array('m.customer_id', '=', $customer_id);
        }

        // データ取得
        $data = $this
            ->from('t_sales as s')
            ->leftjoin('t_sales_detail as sd', function ($join) {
                $join->on('sd.sales_id', '=', 's.id')
                    ->where('sd.sales_flg', '<>', 2)
                    ->where('sd.del_flg', '=', config('const.flg.off'));
            })

            ->leftjoin('t_matter as m', 'm.id', '=', 's.matter_id')
            ->leftjoin('t_delivery as d', 'd.id', '=', 'sd.delivery_id')
            ->leftjoin('m_construction as c', 'c.id', '=', 's.construction_id')
            ->leftjoin('t_quote_detail as qd', 'qd.id', '=', 'sd.quote_detail_id')
            ->where($where)
            ->selectRaw(
                's.id as sales_id
                ,sd.id as sales_detail_id
                ,m.matter_no
                ,m.matter_name as matter_name
                ,sd.delivery_date
                ,d.delivery_no
                ,s.product_id
                ,s.product_code
                ,s.product_name
                ,s.model
                ,s.unit
                ,qd.quote_quantity
                ,qd.sales_unit_price
                ,qd.quote_quantity * qd.sales_unit_price as sales_total
                ,null as remarks
                ,s.details_p_flg
                ,s.details_c_flg 
                ,s.selling_p_flg 
                ,s.selling_c_flg 
                ,ifnull(sd.quote_id, s.quote_id) as quote_id
                ,ifnull(sd.quote_detail_id, s.quote_detail_id) as quote_detail_id
                ,s.construction_id
                ,s.layer_flg
                ,s.parent_quote_detail_id
                ,s.seq_no
                ,s.depth
                ,s.tree_path
                ,s.sales_use_flg
                ,c.construction_name'
            )
            ->orderByRaw('m.matter_no,s.tree_path')
            ->get();

        return $data;
    }

    /**
     * 案件に紐づく売上と売上明細を取得する
     * 指定請求IDに紐づく売上データを優先で見積データを上書きする
     * @param $matterId
     * @param $matterNo
     * @param $quoteId
     * @param $requestId
     * @return $result      売上と売上明細データ
     */
    public function getSalesDetailDataList($matterId, $matterNo, $quoteId, $requestId)
    {
        $Quote      = new Quote();
        $Delivery   = new Delivery();
        $Returns    = new Returns();
        $SystemUtil = new SystemUtil();
        $SalesDetail = new SalesDetail();

        $result = [
            'salesDataList' => [],
            'salesDetailDataList' => []
        ];

        $gridDataList       = [];
        $gridDetailDataList = [];

        // グリッドデータ
        $gridDataList   = $SystemUtil->addFilterTreePathInfo($Quote->getSalesDetailData($matterNo, $matterId, $quoteId, $requestId))->toArray();
        // 売上明細データ(子グリッド)
        $tmpSalesDetailList   = $SalesDetail->getEditSalesDetailData($matterId, $quoteId);
        // 見積明細に紐づく売上明細を取得
        $salesDataList = $tmpSalesDetailList->whereIn('sales_flg', [config('const.salesFlg.val.delivery'), config('const.salesFlg.val.not_delivery'), config('const.salesFlg.val.not_sales'), config('const.salesFlg.val.return')]);
        foreach ($gridDataList as $parentRow) {
            $filterTreePath = $parentRow['filter_tree_path'];
            $gridDetailDataList[$filterTreePath] = [];
            $currentSalesDetailList = $salesDataList->where('quote_detail_id', '=', $parentRow['quote_detail_id'])->toArray();
            foreach ($currentSalesDetailList as $row) {
                $row['filter_tree_path']    = $filterTreePath;
                $row['min_quantity']        = $parentRow['min_quantity'];
                if (Common::nullToBlank($row['sales_update_date']) === '') {
                    // 未確定データ
                    switch ($row['sales_flg']) {
                        case config('const.salesFlg.val.delivery'):
                        case config('const.salesFlg.val.return'):
                            // 未来の予定で作られたデータの場合、単価を見積から取得しなおすので先祖返りしている可能性がある そのため売上データ(見積データ)の単価で上書きする
                            $row['sales_unit_price']    = $parentRow['sales_unit_price'];
                            $row['sales_total']         = Common::roundDecimalSalesPrice($row['sales_quantity'] * $row['sales_unit_price']);
                            break;
                    }
                }

                $gridDetailDataList[$filterTreePath][] = $row;
            }
        }
        // 売上データに紐づく売上明細を取得
        $notSalesDataList = $tmpSalesDetailList->whereIn('sales_flg', [config('const.salesFlg.val.discount'), config('const.salesFlg.val.production'), config('const.salesFlg.val.cost_adjust')])->toArray();
        foreach ($notSalesDataList as $row) {
            $keyIndex = array_search($row['sales_id'], array_column($gridDataList, 'sales_id'));
            $parentRow = $gridDataList[$keyIndex];
            $row['filter_tree_path']    = $parentRow['filter_tree_path'];
            $row['min_quantity']        = $parentRow['min_quantity'];

            $gridDetailDataList[$row['filter_tree_path']][] = $row;
        }
        // 納品データ取得
        $deliveryDataList = $Delivery->getEditSalesDetailData($quoteId)->toArray();
        foreach ($deliveryDataList as $row) {
            $keyIndex = array_search($row['quote_detail_id'], array_column($gridDataList, 'quote_detail_id'));
            $parentRow = $gridDataList[$keyIndex];
            $row['filter_tree_path']    = $parentRow['filter_tree_path'];
            $row['min_quantity']        = $parentRow['min_quantity'];
            $row['sales_quantity']      = $parentRow['min_quantity'] * $row['sales_stock_quantity'];
            $row['sales_unit_price']    = $parentRow['sales_unit_price'];
            $row['cost_unit_price']     = $parentRow['cost_unit_price'];
            $row['sales_total']    = Common::roundDecimalSalesPrice($row['sales_quantity'] * $row['sales_unit_price']);
            $row['cost_total']     = Common::roundDecimalStandardPrice($row['sales_quantity'] * $row['cost_unit_price']);
            $gridDetailDataList[$row['filter_tree_path']][] = $row;
        }
        // 返品データ取得
        $returnDataList = $Returns->getEditSalesDetailData($matterId, $quoteId)->toArray();
        foreach ($returnDataList as $row) {
            $keyIndex = array_search($row['quote_detail_id'], array_column($gridDataList, 'quote_detail_id'));
            $parentRow = $gridDataList[$keyIndex];
            $row['filter_tree_path']    = $parentRow['filter_tree_path'];
            $row['min_quantity']        = $parentRow['min_quantity'];
            $row['sales_quantity']      = $parentRow['min_quantity'] * $row['sales_stock_quantity'];
            $row['sales_unit_price']    = $parentRow['sales_unit_price'];
            $row['cost_unit_price']     = $parentRow['cost_unit_price'];
            $row['sales_total']         = (Common::roundDecimalSalesPrice($row['sales_quantity'] * $row['sales_unit_price']) * -1);
            $row['cost_total']          = (Common::roundDecimalStandardPrice($row['sales_quantity'] * $row['cost_unit_price']) * -1);
            $row['sales_quantity']      = $row['sales_quantity'] * -1;
            $row['sales_stock_quantity'] = $row['sales_stock_quantity'] * -1;
            $gridDetailDataList[$row['filter_tree_path']][] = $row;
        }


        foreach ($gridDetailDataList as $filterTreePath => $detailDataList) {
            $tmpSalesDate  = array_column($detailDataList, 'sales_date');
            $tmpNotdeliveryFlg = array_column($detailDataList, 'notdelivery_flg');

            array_multisort(
                $tmpSalesDate,
                SORT_ASC,
                $tmpNotdeliveryFlg,
                SORT_DESC,
                $detailDataList
            );

            $gridDetailDataList[$filterTreePath] = $detailDataList;
        }


        // 未納調整フラグの計算
        foreach ($gridDetailDataList as $filterTreePath => $detailDataList) {
            $notDeliveryQuantity    = 0;
            $deliveryQuantity       = 0;
            foreach ($detailDataList as $key => $row) {
                if (Common::nullorBlankToZero($row['sales_detail_id']) !== 0) {
                    // 保存分
                    if ($row['sales_flg'] === config('const.salesFlg.val.not_delivery') && Common::nullToBlank($row['sales_update_date']) !== '') {
                        // 確定している未納
                        $notDeliveryQuantity += $row['sales_stock_quantity'];
                    } else if ($row['sales_flg'] === config('const.salesFlg.val.delivery')) {
                        // 納品
                        if ($row['notdelivery_flg'] === config('const.notdeliveryFlg.val.create')) {
                            // 調整用データ
                            $deliveryQuantity -= $row['sales_stock_quantity'];
                        } else if ($row['notdelivery_flg'] === config('const.notdeliveryFlg.val.invalid')) {
                            // 無効データ
                            $deliveryQuantity += $row['sales_stock_quantity'];
                        }
                    }
                }
            }

            // 未納数 - (相殺 + 無効)
            $validNotDeliveryQuantity = $notDeliveryQuantity - $deliveryQuantity;
            foreach ($detailDataList as $key => $row) {
                if (Common::nullorBlankToZero($row['sales_detail_id']) === 0) {
                    // 未確定分
                    if ($row['sales_flg'] === config('const.salesFlg.val.delivery')) {
                        // 納品
                        if ($validNotDeliveryQuantity >= 1) {
                            $gridDetailDataList[$filterTreePath][$key]['notdelivery_flg'] = config('const.notdeliveryFlg.val.invalid');
                            $validNotDeliveryQuantity -= $row['sales_stock_quantity'];
                            if ($validNotDeliveryQuantity <= -1) {
                                // 調整データ作成
                                $tmpData                = $row;
                                //$tmpData['delivery_no'] = '';
                                $tmpData['delivery_id'] = 0;
                                $tmpData['offset_delivery_id']  = $row['delivery_id'];
                                //$tmpData['delivery_date'] = null;
                                $tmpData['sales_stock_quantity'] = $validNotDeliveryQuantity * -1;
                                $tmpData['notdelivery_flg']     = config('const.notdeliveryFlg.val.create');
                                $tmpData['sales_quantity']      = $tmpData['min_quantity'] * $tmpData['sales_stock_quantity'];
                                $tmpData['sales_unit_price']    = $tmpData['sales_unit_price'];
                                $tmpData['cost_unit_price']     = $tmpData['cost_unit_price'];
                                $tmpData['sales_total']         = Common::roundDecimalSalesPrice($tmpData['sales_quantity'] * $tmpData['sales_unit_price']);
                                $tmpData['cost_total']          = Common::roundDecimalStandardPrice($tmpData['sales_quantity'] * $tmpData['cost_unit_price']);
                                array_splice($gridDetailDataList[$filterTreePath], $key + 1, 0, [$tmpData]);
                                //$gridDetailDataList[$filterTreePath][] = $tmpData;
                                break;
                            } else if ($validNotDeliveryQuantity >= 1) {
                                continue;
                            } else {
                                // 0 のとき
                                break;
                            }
                        }
                    }
                }
            }
        }

        $result['salesDataList']         = $gridDataList;
        $result['salesDetailDataList']   = $gridDetailDataList;
        return $result;
    }

    /**
     * 申請中のデータを取得する
     * @param $quoteId  見積ID
     * @param $userId   承認者のID
     */
    public function getApplyingSalesList($quoteId, $userId = null)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_sales.quote_id', '=', $quoteId);
        $where[] = array('t_sales.status', '=', config('const.salesStatus.val.applying'));
        $where[] = array('t_sales.del_flg', '=', config('const.flg.off'));

        if ($userId !== null) {
            $where[] = array('m_department.chief_staff_id', '=', $userId);
        }

        // データ取得
        $data = $this
            ->leftjoin('m_department', 't_sales.status_dep', 'm_department.id')
            ->where($where)
            ->select([
                't_sales.*',
                'm_department.chief_staff_id',
            ])
            ->get();

        return $data;
    }


    /**
     * 得意先IDから取得
     * 申請中の売上データが存在するか
     *
     * @param [type] $customer_id
     * @return boolean
     */
    public function hasApplyingSalesByCustomerId($customer_id)
    {
        $where = [];
        $where[] = array('sa.del_flg', '=', config('const.flg.off'));
        $where[] = array('ma.customer_id', '=', $customer_id);
        $where[] = array('sa.status', '=', config('const.salesStatus.val.applying'));

        $data = $this
            ->from('t_sales AS sa')
            ->leftJoin('t_matter AS ma', 'ma.id', '=', 'sa.matter_id')
            ->where($where)
            ->get();

        return (count($data) > 0);
    }


    /**
     * 見積明細IDから取得
     * 関連する売上データ
     *
     * @param [type] $quoteDetailId 見積明細ID
     * @param [type] $costKbn       仕入区分
     * @return void
     */
    public function getSalesDataByQuoteDetailId($quoteDetailId, $costKbn)
    {
        $where = [];
        $where[] = array('sa.del_flg', '=', config('const.flg.off'));
        $where[] = array('sa.quote_detail_id', '=', $quoteDetailId);
        $where[] = array('sa.cost_kbn', '=', $costKbn);
        // $where[] = array('sa.cost_unit_price', '<>', $costUnitPrice);

        $data = $this
            ->from('t_sales AS sa')
            ->where($where)
            ->selectRaw('
                    sa.id,
                    sa.matter_id,
                    sa.regular_price,
                    sa.tree_path,
                    sa.request_id,
                    sa.invalid_unit_price_flg,
                    sa.cost_unit_price,
                    sa.update_cost_unit_price
                ')
            ->get();

        return $data;
    }

    /**
     * 見積明細IDから取得
     *
     * @param [type] $quoteDetailId 見積明細ID
     * @param [type] $params 更新データ
     * @return void
     */
    public function updateByQuoteDetailId($quoteDetailId, $params)
    {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('t_sales_detail.del_flg', '=', config('const.flg.off'));
            $where[] = array('t_sales_detail.quote_detail_id', '=', $quoteDetailId);

            $beUpdateData = $this
                ->from('t_sales_detail')
                ->where($where)
                ->get();

            $params['update_cost_unit_price'] = $beUpdateData['cost_unit_price'];

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $quoteDetailId, config('const.logKbn.update'));

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
     * 売上明細の紐づいていない売上を削除する
     * @param $matterId     案件ID
     * @return $deleteCnt   削除件数
     */
    public function deleteNoRelatedSalesData($matterId)
    {
        $deleteCnt = 0;
        try {
            $where = [];
            $where[] = array('t_sales.matter_id', '=', $matterId);


            $deleteDataList = $this
                ->leftjoin('t_sales_detail', 't_sales.id', 't_sales_detail.sales_id')
                ->select([
                    't_sales.id',
                ])
                ->where($where)
                ->whereNull('t_sales_detail.id')
                ->get();

            if($deleteDataList->count() >= 1){

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                foreach ($deleteDataList as $deleteData) {
                    $LogUtil->putByData($deleteData, config('const.logKbn.delete'), $this->table);
                }

                $deleteCnt = $this
                    ->leftjoin('t_sales_detail', 't_sales.id', 't_sales_detail.sales_id')
                    ->where($where)
                    ->whereNull('t_sales_detail.id')
                    ->delete();
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $deleteCnt;
    }

    /**
     * 見積明細IDから取得
     *
     * @param [type] $quoteDetailId 見積明細ID
     * @param [type] $params 更新データ
     * @return void
     */
    public function getRequestIdByQuoteDetailId($quoteDetailId)
    {
        // 条件
        $where = [];
        $where[] = array('t_sales_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_sales_detail.quote_detail_id', '=', $quoteDetailId);

        $data = $this
            ->leftJoin('t_sales_detail', 't_sales.id', '=', 't_sales_detail.sales_id')
            ->where($where)
            ->select([
                't_sales.request_id'
            ])
            ->distinct()
            ->get();

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
     * 請求IDで削除(論理)
     * 紐づく売上明細も削除する
     * @param $requestId
     */
    public function softDeleteByRequestId($requestId)
    {
        $SalesDetail = new SalesDetail();
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
                        $SalesDetail->softDeleteBySalesId($record->id);
                    }
                }
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $deleteCnt;
    }
    
    /**
     * 売上明細区分が0：見積明細以外の 親見積明細IDの存在確認
     *
     * @param int $matterId 案件ID
     * @param array $quoteDetailIdList 見積明細IDの配列
     * @return boolean true:存在する false：存在しない
     */
    public function hasSalesByParentQuoteDetailIdList($matterId, $quoteDetailIdList)
    {
        $result = $this
            ->where('matter_id', $matterId)
            ->where('del_flg', config('const.flg.off'))
            ->where('sales_flg', '!=', config('const.salesDetailFlg.val.quote'))
            ->whereIn('parent_quote_detail_id', $quoteDetailIdList)
            ->exists();

        return $result;
    }

    /**
     * 返品IDで検索
     * 該当返品が売上明細に保存されているか
     * ※売上期間内に含まれているか
     * @param [type] $returnId
     * @return boolean
     */
    public function hasReturnSalesByReturnId($returnId) 
    {
        $result = false;

        $Requests = new Requests();

        $where = [];
        $where[] = array('sd.del_flg', '=', config('const.flg.off'));
        $where[] = array('sd.return_id', '=', $returnId);
        $where[] = array('sd.sales_flg', '=', config('const.salesFlg.val.return'));

        // 売上明細取得
        $salesData = $this
            ->from('t_sales_detail AS sd')
            ->where($where)
            ->orderBy('sd.sales_date', 'desc')
            ->first()
            ;

        if ($salesData != null) {
            // 請求データ取得
            $requestId = $salesData['request_id'];
            $requestData = $Requests->getById($requestId);

            if ($requestData != null && $requestData['status'] >= config('const.requestStatus.val.complete')) {
                // 売上確定以上であればチェックする
                $requestSDay = $requestData['request_s_day'];
                $requestEDay = $requestData['request_e_day'];

                if ($requestSDay <= $salesData['sales_date'] && $requestEDay >= $salesData['sales_date']) {
                    // 売上期間内に存在する
                    $result = true;
                }

            }
        }
        return $result;
    }
}
