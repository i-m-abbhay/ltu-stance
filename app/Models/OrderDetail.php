<?php

namespace App\Models;

use App\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;
use Validator;

/**
 * 発注明細データ
 */
class OrderDetail extends Model
{
    // テーブル名
    protected $table = 't_order_detail';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'order_id',
        'order_no',
        'add_kbn',
        'seq_no',
        'quote_detail_id',
        'add_kbn',
        'product_id',
        'product_code',
        'product_name',
        'model',
        'maker_id',
        'maker_name',
        'supplier_id',
        'supplier_name',
        'order_quantity',
        'min_quantity',
        'stock_quantity',
        'unit',
        'stock_unit',
        'regular_price',
        'sales_kbn',
        'cost_unit_price',
        'sales_unit_price',
        'cost_makeup_rate',
        'sales_makeup_rate',
        'cost_total',
        'sales_total',
        'gross_profit_rate',
        'profit_total',
        'hope_arrival_plan_date',
        'arrival_plan_date',
        'arrival_quantity',
        'memo',
        'parent_order_detail_id',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    public function orderDetail()
    {
        return $this->belongsTo('App\Models\OrderDetail');
    }

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.product_id', '<>', 0);
        $where[] = array('p.intangible_flg ', '<>', 1);
        if (!empty($params['order_no'])) {
            $where[] = array('od.order_no', '=', $params['order_no']);
        }

        // データ取得
        $data = DB::table('t_order_detail as od')
            ->join('t_order as o', 'od.order_no', '=', 'o.order_no')
            ->leftjoin('t_matter as m', 'o.matter_no', '=', 'm.matter_no')
            ->leftjoin('m_customer as c', 'm.customer_id', '=', 'c.id')
            ->leftJoin('m_general as c_general', function($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                     ->where('c_general.category_code', '=', config('const.general.juridical'))
                     ;
            })

            ->leftjoin('m_department as d', 'm.department_id', '=', 'd.id')
            ->leftjoin('m_staff as s', 'm.staff_id', '=', 's.id')
            ->leftjoin('m_product as p', 'p.id', '=', 'od.product_id')
            
            ->leftjoin('m_supplier as su', 'o.supplier_id', '=', 'su.id')
            ->leftJoin('m_general as g', function($join) {
                $join->on('su.juridical_code', '=', 'g.value_code')
                     ->where('g.category_code', '=', config('const.general.juridical'))
                     ;
            })
            ->leftjoin('m_supplier as maker', 'o.maker_id', '=', 'maker.id')
            ->leftJoin('m_general as maker_general', function($join) {
                $join->on('maker.juridical_code', '=', 'maker_general.value_code')
                     ->where('maker_general.category_code', '=', config('const.general.juridical'))
                     ;
            })

            ->leftjoin('m_warehouse as w', 'o.delivery_address_id', '=', 'w.id')
            ->leftjoin('m_address as a', 'o.delivery_address_id', '=', 'a.id')
            ->leftjoin(DB::raw('(select ars.arrival_id, order_id, order_detail_id, to_warehouse_id, to_shelf_number_id,qr_prinf_flg
                                    from t_arrival
                                    inner join
                                    (select max(id) as arrival_id from t_arrival group by order_id, order_detail_id) as ars
                                    on ars.arrival_id = t_arrival.id) as ar'), function ($join) {
                $join->on('od.order_id', '=', 'ar.order_id');
                $join->on('od.id', '=', 'ar.order_detail_id');
            })
            ->selectRaw(
                'od.product_id,
                od.product_code,
                od.product_name,
                p.draft_flg,
                od.model,
                od.stock_quantity as order_quantity,
                od.quote_detail_id,
                od.arrival_quantity,
                od.cost_unit_price as price,
                o.id as order_id,
                o.order_no,
                o.own_stock_flg,
                od.id as order_detail_id,
                o.delivery_address_id,
                CONCAT(COALESCE(g.value_text_2, \'\'), COALESCE(su.supplier_name, \'\'), COALESCE(g.value_text_3, \'\')) AS supplier_name,
                CONCAT(COALESCE(maker_general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_general.value_text_3, \'\')) as maker_name,
                m.id as matter_id,
                o.matter_no,
                m.matter_name,
                m.customer_id,
                CONCAT(COALESCE(c_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(c_general.value_text_3, \'\')) as customer_name,
                od.arrival_plan_date,
                CASE o.delivery_address_kbn WHEN 1 THEN CONCAT(a.address1, a.address2) ELSE w.warehouse_name END AS warehouse_name,
                d.department_name,
                s.staff_name,
                ar.arrival_id,
                ar.to_warehouse_id as warehouse_id,
                ar.to_shelf_number_id as shelf_number_id,
                case when (od.stock_quantity < ifnull(od.arrival_quantity,0)) or p.draft_flg = 1 then 0 else round(od.stock_quantity - ifnull(od.arrival_quantity,0)) end as check_quantity,
                1 as print_number,
                1 as collect_number,
                false AS check_value,
                null as qr_id,
                null as qr_detail_id,
                false as qr_collect_print,
                false as qr_prinf_flg'
            )
            ->where($where)
            ->orderBy('o.id')
            ->orderBy('od.seq_no')
            ->orderBy('od.product_id')
            ->get();
        return $data;
    }

     /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getByOrderNos($orderNos)
    {
        // Where句作成
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.product_id', '<>', 0);
        $where[] = array('p.intangible_flg ', '<>', 1);

        // データ取得
        $data = DB::table('t_order_detail as od')
            ->join('t_order as o', 'od.order_no', '=', 'o.order_no')
            ->leftjoin('t_matter as m', 'o.matter_no', '=', 'm.matter_no')
            ->leftjoin('m_customer as c', 'm.customer_id', '=', 'c.id')
            ->leftJoin('m_general as c_general', function($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                     ->where('c_general.category_code', '=', config('const.general.juridical'))
                     ;
            })

            ->leftjoin('m_department as d', 'm.department_id', '=', 'd.id')
            ->leftjoin('m_staff as s', 'm.staff_id', '=', 's.id')
            ->leftjoin('m_product as p', 'p.id', '=', 'od.product_id')
            
            ->leftjoin('m_supplier as su', 'o.supplier_id', '=', 'su.id')
            ->leftJoin('m_general as g', function($join) {
                $join->on('su.juridical_code', '=', 'g.value_code')
                     ->where('g.category_code', '=', config('const.general.juridical'))
                     ;
            })
            ->leftjoin('m_supplier as maker', 'o.maker_id', '=', 'maker.id')
            ->leftJoin('m_general as maker_general', function($join) {
                $join->on('maker.juridical_code', '=', 'maker_general.value_code')
                     ->where('maker_general.category_code', '=', config('const.general.juridical'))
                     ;
            })

            ->leftjoin('m_warehouse as w', 'o.delivery_address_id', '=', 'w.id')
            ->leftjoin('m_address as a', 'o.delivery_address_id', '=', 'a.id')
            ->leftjoin(DB::raw('(select ars.arrival_id, order_id, order_detail_id, to_warehouse_id, to_shelf_number_id,qr_prinf_flg
                                    from t_arrival
                                    inner join
                                    (select max(id) as arrival_id from t_arrival group by order_id, order_detail_id) as ars
                                    on ars.arrival_id = t_arrival.id) as ar'), function ($join) {
                $join->on('od.order_id', '=', 'ar.order_id');
                $join->on('od.id', '=', 'ar.order_detail_id');
            })
            ->selectRaw(
                'od.product_id,
                od.product_code,
                od.product_name,
                p.draft_flg,
                od.model,
                od.stock_quantity as order_quantity,
                od.quote_detail_id,
                od.arrival_quantity,
                od.cost_unit_price as price,
                o.id as order_id,
                o.order_no,
                o.own_stock_flg,
                od.id as order_detail_id,
                o.delivery_address_id,
                CONCAT(COALESCE(g.value_text_2, \'\'), COALESCE(su.supplier_name, \'\'), COALESCE(g.value_text_3, \'\')) AS supplier_name,
                CONCAT(COALESCE(maker_general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_general.value_text_3, \'\')) as maker_name,
                m.id as matter_id,
                o.matter_no,
                m.matter_name,
                m.customer_id,
                CONCAT(COALESCE(c_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(c_general.value_text_3, \'\')) as customer_name,
                od.arrival_plan_date,
                CASE o.delivery_address_kbn WHEN 1 THEN CONCAT(a.address1, a.address2) ELSE w.warehouse_name END AS warehouse_name,
                d.department_name,
                s.staff_name,
                ar.arrival_id,
                ar.to_warehouse_id as warehouse_id,
                ar.to_shelf_number_id as shelf_number_id,
                case when (od.stock_quantity < ifnull(od.arrival_quantity,0)) or p.draft_flg = 1 then 0 else round(od.stock_quantity - ifnull(od.arrival_quantity,0)) end as check_quantity,
                1 as print_number,
                1 as collect_number,
                false AS check_value,
                null as qr_id,
                null as qr_detail_id,
                false as qr_collect_print,
                false as qr_prinf_flg'
            )
            ->where($where)
            ->whereIn('od.order_no',$orderNos)
            ->orderBy('o.id')
            ->orderBy('od.seq_no')
            ->orderBy('od.product_id')
            ->get();
        return $data;
    }

    /**
     * IDで取得
     * @param int $id 発注明細ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id)
    {
        $data = $this
            ->select(
                't_order_detail.*'
            )
            ->where(['t_order_detail.id' => $id])
            ->first();

        return $data;
    }

    /**
     * 発注IDで取得
     * @param int $id 発注ID
     * @return type 検索結果データ（1件）
     */
    public function getByOrderId($orderId)
    {
        $data = $this
            ->select(
                't_order_detail.*'
            )
            ->where([
                ['t_order_detail.del_flg', config('const.flg.off')],
                ['t_order_detail.order_id', $orderId]
            ])
            ->get();

        return $data;
    }

    /**
     * 発注IDで取得
     * @param int $id 発注ID
     * @return type 検索結果データ（複数件）
     */
    public function getByOrderIds($orderIds)
    {
        $data = $this
            ->select(
                't_order_detail.*'
            )
            ->where([
                ['t_order_detail.del_flg', config('const.flg.off')]
            ])
            ->whereIn('t_order_detail.order_id', $orderIds)
            ->get();

        return $data;
    }

    /**
     * テーブルのCOMMENTを取得
     *
     * @return Collection $data[物理名]=>論理名
     */
    public function getColumnComment()
    {
        return
            DB::table('INFORMATION_SCHEMA.COLUMNS')
            ->where('TABLE_NAME', $this->table)
            ->pluck('COLUMN_COMMENT', 'COLUMN_NAME');
    }

    /**
     * 発注明細データ取得（発注詳細画面用）
     *
     * @param [type] $quoteNo
     * @param [type] $orderId
     * @return void
     */
    public function getOrderDetailEditData($quoteNo, $orderId)
    {

        // 一式フラグ=1の見積明細取得
        $setData = DB::table('t_quote_detail')
            ->leftjoin('m_product as m_product', 't_quote_detail.product_id', '=', 'm_product.id')
            ->select([
                DB::raw('0 as id'),
                't_quote_detail.id AS quote_detail_id',
                DB::raw('0 as add_kbn'),
                DB::raw('null as hope_arrival_plan_date'),
                DB::raw('null as arrival_plan_date'),
                't_quote_detail.construction_id',
                't_quote_detail.parent_quote_detail_id',
                't_quote_detail.tree_path',
                't_quote_detail.set_flg',
                't_quote_detail.sales_use_flg',
                't_quote_detail.layer_flg',
                't_quote_detail.product_id',
                't_quote_detail.product_code',
                't_quote_detail.product_name',
                't_quote_detail.model',
                't_quote_detail.maker_id',
                't_quote_detail.maker_name',
                't_quote_detail.supplier_id',
                't_quote_detail.supplier_name',
                DB::raw('t_quote_detail.quote_quantity as order_quantity'),
                't_quote_detail.unit',
                't_quote_detail.stock_quantity',
                't_quote_detail.stock_unit',
                't_quote_detail.min_quantity',
                't_quote_detail.order_lot_quantity',
                't_quote_detail.quote_quantity',
                'm_product.intangible_flg',
                'm_product.draft_flg',
                'm_product.auto_flg',
                't_quote_detail.regular_price',
                't_quote_detail.cost_kbn',
                't_quote_detail.sales_kbn',
                't_quote_detail.cost_unit_price',
                't_quote_detail.sales_unit_price',
                't_quote_detail.cost_makeup_rate',
                't_quote_detail.sales_makeup_rate',
                't_quote_detail.cost_total',
                't_quote_detail.sales_total',
                't_quote_detail.gross_profit_rate',
                't_quote_detail.profit_total',
                't_quote_detail.memo',

                't_quote_detail.quantity_per_case',
                't_quote_detail.set_kbn',
                't_quote_detail.class_big_id',
                't_quote_detail.class_middle_id',
                't_quote_detail.class_small_id',
                't_quote_detail.tree_species',
                't_quote_detail.grade',
                't_quote_detail.length',
                't_quote_detail.thickness',
                't_quote_detail.width',
                
                DB::raw(config('const.flg.off').' as child_parts_flg')
            ])
            ->where('t_quote_detail.quote_no', $quoteNo)
            ->where('t_quote_detail.quote_version', config('const.quoteCompVersion.number'))
            ->where('t_quote_detail.set_flg', config('const.flg.on'))
            ->where('t_quote_detail.del_flg', config('const.flg.off'))
            ->get();

        // 発注明細取得
        $data = $this
            ->from('t_order_detail as od')
            ->join('t_quote_detail', 'od.quote_detail_id', '=', 't_quote_detail.id')
            ->leftjoin('m_supplier as ma', 'od.maker_id', '=', 'ma.id')
            ->leftjoin('m_product as m_product', 't_quote_detail.product_id', '=', 'm_product.id')
            ->where(['od.order_id' => $orderId])
            ->select(
                'od.id',
                'od.quote_detail_id',
                'od.add_kbn',
                DB::raw('date_format(od.hope_arrival_plan_date, \'%Y/%m/%d\') as hope_arrival_plan_date'),
                DB::raw('date_format(od.arrival_plan_date, \'%Y/%m/%d\') as arrival_plan_date'),
                't_quote_detail.construction_id',
                't_quote_detail.parent_quote_detail_id',
                't_quote_detail.tree_path',
                't_quote_detail.set_flg',
                't_quote_detail.sales_use_flg',
                't_quote_detail.layer_flg',
                'od.product_id',
                'od.product_code',
                'od.product_name',
                'od.model',
                'od.maker_id',
                'od.maker_name',
                'od.supplier_id',
                'od.supplier_name',
                'od.order_quantity',
                'od.unit',
                'od.stock_quantity',
                // 'od.stock_unit',
                DB::raw('
                    CASE WHEN m_product.id IS NULL
                    THEN od.stock_unit 
                    ELSE m_product.stock_unit 
                    END AS stock_unit
                '),                                       // 更新があるためマスタ優先
                'od.min_quantity',
                't_quote_detail.order_lot_quantity',
                't_quote_detail.quote_quantity',
                'm_product.intangible_flg',
                'm_product.draft_flg',
                'm_product.auto_flg',
                'od.regular_price',
                't_quote_detail.cost_kbn',
                't_quote_detail.sales_kbn',
                'od.cost_unit_price',
                'od.sales_unit_price',
                'od.cost_makeup_rate',
                'od.sales_makeup_rate',
                'od.cost_total',
                'od.sales_total',
                'od.gross_profit_rate',
                'od.profit_total',
                'od.memo',

                't_quote_detail.quantity_per_case',
                't_quote_detail.set_kbn',
                // 't_quote_detail.class_big_id',
                DB::raw('
                    CASE WHEN m_product.id IS NULL
                    THEN t_quote_detail.class_big_id 
                    ELSE m_product.class_big_id 
                    END AS class_big_id
                '),                                       // 更新があるためマスタ優先
                // 't_quote_detail.class_middle_id',
                DB::raw('
                    CASE WHEN m_product.id IS NULL
                    THEN t_quote_detail.class_middle_id 
                    ELSE m_product.class_middle_id 
                    END AS class_middle_id
                '),                                       // 更新があるためマスタ優先
                't_quote_detail.class_small_id',
                't_quote_detail.tree_species',
                't_quote_detail.grade',
                't_quote_detail.length',
                't_quote_detail.thickness',
                't_quote_detail.width',

                DB::raw(config('const.flg.off').' as child_parts_flg')
            )
            ->orderBy('od.seq_no', 'asc')
            ->get();

        // 必要な一式明細(親明細)を挿入
        $quoteDetailIdList = [];
        $result = [];
        foreach($data as $row){
            $parentQuoteDetailId =  $row['parent_quote_detail_id'];
            $parentData = $setData->firstWhere('quote_detail_id', '=', $parentQuoteDetailId);
            if($parentData !== null){
                $row->child_parts_flg = config('const.flg.on');
                if(!isset($quoteDetailIdList[$parentQuoteDetailId])){
                    $quoteDetailIdList[$parentQuoteDetailId] = true;
                    $result[] = (array)$parentData;
                }
            }
            $result[] = $row->attributes;
        }

        return $result;
    }

    /**
     * 子部品データ取得（発注詳細画面用）
     *
     * @param [type] $quoteNo
     * @param [type] $parentQuoteDetailId
     * @return void
     */
    public function getOrderDetailChildPartsEditData($quoteNo, $parentQuoteDetailId)
    {

        $result = DB::table('t_quote_detail')
            ->select([
                DB::raw('0 as id'),
                't_quote_detail.id AS quote_detail_id',
                't_quote_detail.parent_quote_detail_id',
                't_quote_detail.product_id',
                't_quote_detail.quote_quantity',
                't_quote_detail.regular_price',
                't_quote_detail.cost_unit_price',
                't_quote_detail.sales_unit_price',
                't_quote_detail.cost_total',
                't_quote_detail.sales_total',
            ])
            ->where('t_quote_detail.quote_no', $quoteNo)
            ->where('t_quote_detail.parent_quote_detail_id', $parentQuoteDetailId)
            ->where('t_quote_detail.set_flg', config('const.flg.off'))
            ->where('t_quote_detail.del_flg', config('const.flg.off'))
            ->get();

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
            $result = $this->insert([
                'order_no' => $params['order_no'],
                'quote_layer_id' => $params['quote_layer_id'],
                'seq_no' => $params['seq_no'],
                'quote_detail_id' => $params['quote_detail_id'],
                'add_kbn' => $params['add_kbn'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'product_name' => $params['product_name'],
                'model' => $params['model'],
                'maker_name' => $params['maker_name'],
                'supplier_name' => $params['supplier_name'],
                'order_quantity' => $params['order_quantity'],
                'unit' => $params['unit'],
                'regular_price' => $params['regular_price'],
                'sales_kbn' => $params['sales_kbn'],
                'purchase_unit_price' => $params['purchase_unit_price'],
                'sales_unit_price' => $params['sales_unit_price'],
                'purchase_makeup_rate' => $params['purchase_makeup_rate'],
                'sales_makeup_rate' => $params['sales_makeup_rate'],
                'gross_profit_rate' => $params['gross_profit_rate'],
                'hope_arrival_plan_date' => $params['hope_arrival_plan_date'],
                'arrival_plan_date' => $params['arrival_plan_date'],
                'arrival_quantity' => $params['arrival_quantity'],
                'memo' => $params['memo'],
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
     * 複数データ新規登録
     * 
     * @param $paramsList
     * @return bool
     */
    public function addList($paramsList): bool
    {
        $result = false;
        try {
            $insertData = [];
            $userId = Auth::user()->id;
            $now = Carbon::now();

            foreach ($paramsList as $data) {
                $record = [];
                foreach ($this->fillable as $column) {
                    switch ($column) {
                        case 'id':
                            break;
                        case 'del_flg':
                            $record[$column] = config('const.flg.off');
                            break;
                        case 'created_user':
                        case 'update_user':
                            $record[$column] = $userId;
                            break;
                        case 'created_at':
                        case 'update_at':
                            $record[$column] = $now;
                            break;
                        default:
                            if (is_object($data)) {
                                $record[$column] = $data->$column;
                            } else {
                                $record[$column] = $data[$column];
                            }
                            break;
                    }
                }
                $this->validation($record);
                $insertData[] = $record;
            }
            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
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

            if (!isset($params['update_user']) || empty($params['update_user'])) {
                $params['update_user'] = Auth::user()->id;
            }
            if (!isset($params['update_at']) || empty($params['update_at'])) {
                $params['update_at'] = Carbon::now();
            }

            $this->validation($params);

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update($params);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 入荷済数更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateQuantity($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['order_detail_id'], config('const.logKbn.update'));

            if(isset(Auth::user()->id)){
                $user_id = Auth::user()->id;
            }else{
                $user_id = 0;
            }
            $updateCnt = $this
                ->where('id', $params['order_detail_id'])
                ->update([
                    'arrival_quantity' => $params['arrival_quantity'],
                    'update_user' => $user_id,
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
     * @param int $id 発注明細ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteById($id)
    {
        $result = false;
        try {
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
     * 発注IDに紐づくデータの物理削除 
     * @param $orderId
     * @return void
     */
    public function physicalDeleteByOrderId($orderId)
    {
        $result = false;
        try {
            $where = [];
            $where[] = array('order_id', '=', $orderId);
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            $result = $this
                ->where($where)
                ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除[配列]
     *
     * @param [type] $id 発注明細IDの配列
     * @return void
     */
    public function physicalDeleteListByIds($ids)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($ids as $id) {
                $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));
            }

            $deleteCnt = $this
                ->whereIn('id', $ids)
                ->delete();
            $result = ($deleteCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 見積もり明細IDリストから発注詳細を検索する
     *  
     * @param [type] $quoteDetailIdList
     * @return コレクション
     **/
    function getDataByQuoteDetalId($quoteDetailIdList)
    {
        $where[] = array('t_order_detail.del_flg', '=', config('const.flg.off'));
        $data = $this
            ->leftjoin('t_delivery', 't_order_detail.id', 't_delivery.order_detail_id')
            ->select([
                't_order_detail.*',
                't_delivery.order_detail_id',
            ])
            ->where($where)
            ->whereIn('t_order_detail.quote_detail_id', $quoteDetailIdList)
            ->get();

        return $data;
    }

    /**
     * 見積明細IDリストで発注明細を取得
     *  
     * @param array $quoteDetailIdList 見積明細IDの配列
     * @return 発注明細データリスト
     **/
    function getDataByQuoteDetailIdList($quoteDetailIdList)
    {
        $data = $this
            ->where('t_order_detail.del_flg', '=', config('const.flg.off'))
            ->whereIn('t_order_detail.quote_detail_id', $quoteDetailIdList)
            ->get();

        return $data;
    }

    /**
     * 発注IDで取得
     *
     * @param [type] $orderId
     * @return Collection
     */
    public function getOrderReportDataByOrderId($orderId)
    {
        $data = $this
            ->leftJoin('t_quote_detail', 't_order_detail.quote_detail_id', 't_quote_detail.id')
            ->select([
                't_order_detail.*',
                't_quote_detail.construction_id',
                't_quote_detail.parent_quote_detail_id',
            ])
            ->where(['t_order_detail.order_id' => $orderId])
            ->orderBy('seq_no')
            ->get();

        return $data;
    }

    /**
     * 発注IDから引当データの出来てない発注明細を取得する
     *
     * @param [type] $orderId
     * @return Collection
     */
    public function getNotReserveOrderData($orderId)
    {
        $data = $this
            ->join('t_quote_detail', 't_order_detail.quote_detail_id', 't_quote_detail.id')
            ->leftJoin('t_reserve', 't_order_detail.id', 't_reserve.order_detail_id')
            ->join('m_product', 't_order_detail.product_id', 'm_product.id')
            ->select([
                't_order_detail.id AS order_detail_id',
                't_order_detail.quote_detail_id',
                't_order_detail.product_id',
                'm_product.intangible_flg',
                't_order_detail.product_code',
                't_order_detail.order_quantity',
                't_order_detail.stock_quantity',
            ])
            ->where(['t_quote_detail.set_flg' => config('const.flg.off')])
            ->where(['t_quote_detail.layer_flg' => config('const.flg.off')])
            ->where(['t_order_detail.order_id' => $orderId])
            ->whereNull('t_reserve.id')
            ->get();

        return $data;
    }

    /**
     * 発注登録時に一時保存データの発注数を0で初期化する
     * @param array $quoteDetailIdList 
     * @return boolean True:成功 False:失敗 
     */
    public function resetTemporaryQuantity($quoteDetailIdList)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($quoteDetailIdList as $value) {
                $logWhere = [
                    ['order_no', null],
                    ['quote_detail_id', $value]
                ];
                $LogUtil->putByWhere($this->table, $logWhere, config('const.logKbn.update'));
            }



            $items['order_quantity'] = 0;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $updateCnt = $this
                ->whereNull('order_no')
                ->whereIn('quote_detail_id', $quoteDetailIdList)
                ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * バリデーションチェック
     * @param $params   登録更新対象の行データ
     */
    private function validation($params)
    {
        $validator = Validator::make(
            $params,
            [
                'order_quantity'    => 'nullable|numeric|between:0,9999999.99',
                'min_quantity'      => 'nullable|numeric|between:0,9999999.99',
                'stock_quantity'    => 'nullable|integer',
                'regular_price'     => 'nullable|numeric|between:-999999999,999999999',
                'cost_unit_price'   => 'nullable|numeric|between:-999999999,999999999',
                'sales_unit_price'  => 'nullable|numeric|between:-999999999,999999999',
                'cost_makeup_rate'  => 'nullable|numeric|between:-999.99,999.99',
                'sales_makeup_rate' => 'nullable|numeric|between:-999.99,999.99',
                'cost_total'        => 'nullable|numeric|between:-999999999,999999999',
                'sales_total'       => 'nullable|numeric|between:-999999999,999999999',
                'gross_profit_rate' => 'nullable|numeric|between:-99999.99,99999.99',
                'profit_total'      => 'nullable|numeric|between:-999999999,999999999',
            ],
            [
                'order_quantity.between'    => '発注数が、' .    config('message.error.exceeded_length'),
                'min_quantity.between'      => '最小単位数が、' . config('message.error.exceeded_length'),
                'stock_quantity.between'    => '管理数が、' .    config('message.error.exceeded_length'),
                'regular_price.between'     => '定価が、' .      config('message.error.exceeded_length'),
                'cost_unit_price.between'   => '仕入単価が、' .  config('message.error.exceeded_length'),
                'sales_unit_price.between'  => '販売単価が、' .  config('message.error.exceeded_length'),
                'cost_makeup_rate.between'  => '仕入掛率が、' .  config('message.error.exceeded_length'),
                'sales_makeup_rate.between' => '販売掛率が、' .  config('message.error.exceeded_length'),
                'cost_total.between'        => '仕入総額が、' .  config('message.error.exceeded_length'),
                'sales_total.between'       => '販売総額が、' .  config('message.error.exceeded_length'),
                'gross_profit_rate.between' => '粗利率が、' .    config('message.error.exceeded_length'),
                'profit_total.between'      => '粗利が、' .      config('message.error.exceeded_length'),
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
    }

    /**
     * 直送納品データ取得
     * @return
     */
    public function getDirectDelivery()
    {

        $where = [];
        $where[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));
        $where[] = array('o.status', '=', 4); //発注済み
        $where[] = array('o.delivery_address_kbn', '<>', 3); 
        $where[] = array('od.arrival_quantity', '=', 0);
        $where[] = array('od.arrival_plan_date', '<=', Carbon::now()->toDateString());


        $joinwhere = [];
        $joinwhere['t_order_detail'][] = array('od.del_flg', '=', config('const.flg.off'));
        $joinwhere['m_product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinwhere['m_warehouse'][] = array('w.del_flg', '=', config('const.flg.off'));
        $joinwhere['m_address'][] = array('a.del_flg', '=', config('const.flg.off'));
        $joinwhere['t_matter'][] = array('m.del_flg', '=', config('const.flg.off'));
        $joinwhere['t_quote'][] = array('q.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_order as o')

            ->leftJoin('t_order_detail as od', function ($join) use ($joinwhere) {
                $join->on('od.order_id', '=', 'o.id')
                    ->where($joinwhere['t_order_detail']);
            })

            ->leftJoin('m_product as p', function ($join) use ($joinwhere) {
                $join->on('p.id', '=', 'od.product_id')
                    ->where($joinwhere['m_product']);
            })

            ->leftJoin('m_warehouse as w', function ($join) use ($joinwhere) {
                $join->on('w.id', '=', 'o.delivery_address_id')
                    ->where($joinwhere['m_warehouse']);
            })

            ->leftJoin('m_address as a', function ($join) use ($joinwhere) {
                $join->on('a.id', '=', 'o.delivery_address_id')
                    ->where($joinwhere['m_address']);
            })

            ->leftJoin('t_matter as m', function ($join) use ($joinwhere) {
                $join->on('o.matter_no', '=', 'm.matter_no')
                    ->where($joinwhere['t_matter']);
            })

            ->leftJoin('t_quote as q', function ($join) use ($joinwhere) {
                $join->on('q.quote_no', '=', 'o.quote_no')
                    ->where($joinwhere['t_quote']);
            })

            ->where($where)
            ->where(function ($query) {
                $query->whereIn('o.delivery_address_kbn', [1, 3])
                    ->orWhere('p.intangible_flg', '=', config('const.flg.on'))
                    ->orWhereRaw("ifnull(od.product_code,'') = ''");
            })
            ->selectRaw(
                "m.id as matter_id
                ,0 as shipment_id
                ,0 as shipment_detail_id
                ,0 as loading_id
                ,o.id as order_id
                ,od.id as order_detail_id
                ,od.order_no
                ,q.id as quote_id
                ,od.quote_detail_id
                ,case when o.delivery_address_kbn = 1 then 0 when o.delivery_address_kbn = 3 then 3 else 0 end as shipment_kind 
                ,case when o.delivery_address_kbn = 1 then a.zipcode else w.zipcode end as zipcode
                ,case when o.delivery_address_kbn = 1 then a.pref else w.pref end as pref
                ,case when o.delivery_address_kbn = 1 then a.address1 else w.address1 end as address1
                ,case when o.delivery_address_kbn = 1 then a.address2 else w.address2 end as address2
                ,case when o.delivery_address_kbn = 1 then a.latitude_jp else w.latitude_jp end as latitude_jp
                ,case when o.delivery_address_kbn = 1 then a.longitude_jp else w.longitude_jp end as longitude_jp
                ,case when o.delivery_address_kbn = 1 then a.latitude_world else w.latitude_world end as latitude_world
                ,case when o.delivery_address_kbn = 1 then a.longitude_world else w.longitude_world end as longitude_world
                ,0 as stock_flg
                ,od.product_id
                ,od.product_code
                ,od.arrival_plan_date as delivery_date
                ,od.stock_quantity as delivery_quantity
                ,case when p.intangible_flg = 1 or ifnull(od.product_code,'') = '' then 1 else 0 end as intangible_flg"
            )
            ->orderBy('od.order_no')
            ->orderBy('od.arrival_plan_date')
            ->get();

        return $data;
    }


    /**
     * 類似商品
     *
     * @return void
     */
    public function getMasterCheckList($IDs)
    {
        $where = [];
        $where[] = array('od.add_kbn', '=', config('const.flg.off'));
        $where[] = array('od.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('t_order_detail as od')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('t_quote_detail AS qd', 'qd.id', '=', 'od.quote_detail_id')
                ->leftJoin('t_quote AS q', 'q.quote_no', '=', 'qd.quote_no')
                ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
                ->leftJoin('m_product as p', 'p.id', '=', 'od.product_id')
                ->whereNotNull('p.id')
                ->whereIn('od.product_id', $IDs)
                ->where($where)
                ->selectRaw('
                    od.id,
                    od.order_id,
                    od.quote_detail_id,
                    q.quote_no,
                    q.id AS quote_id,
                    m.id AS matter_id,
                    o.matter_no,
                    od.product_id,
                    od.product_code,
                    od.product_name,
                    od.model,
                    od.maker_id,
                    od.maker_name,
                    od.supplier_id,
                    od.supplier_name,
                    od.min_quantity,
                    od.stock_quantity,
                    od.unit,
                    od.stock_unit,
                    od.cost_unit_price,
                    od.sales_unit_price,
                    od.cost_makeup_rate,
                    od.sales_makeup_rate,
                    od.gross_profit_rate,
                    p.new_product_id
                ')
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 商品IDをキーに取得
     *
     * @param int $productId 商品ID
     * @return 
     */
    public function getDataByProduct($productId) {
        $data = $this
                ->from('t_order_detail AS od')
                ->leftJoin('t_quote_detail AS qd1', 'od.quote_detail_id', '=', 'qd1.id')
                ->leftJoin('t_quote AS q1', 'qd1.quote_no', '=', 'q1.quote_no')
                ->where([
                    ['od.product_id', '=', $productId],
                    ['od.del_flg', '=', config('const.flg.off')],
                    ['qd1.del_flg', '=', config('const.flg.off')],
                    ['q1.del_flg', '=', config('const.flg.off')]
                ])
                ->select([
                    'q1.id AS quote_id',
                    'od.id AS order_detail_id',
                    'od.order_id',
                    'qd1.quote_no',
                    'qd1.construction_id',
                    'od.product_code',
                    'od.maker_id'
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 更新
     *
     * @param int $productId 商品ID
     * @param array $params 
     * @param bool $integrate 統合フラグ　ON = 統合(商品IDを更新)
     * @return void
     */
    public function updateByProductId($productId, $params, $integrate) {
        $result = false;
        try {
            // 条件
            $where = [];
            $where[] = array('product_id', '=', $productId);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            
            // 更新内容
            $items = [];
            if ($integrate) {
                $items['product_id'] = $params['id'];
            }
            if (!empty($params['product_code'])) {
                $items['product_code'] = $params['product_code'];
            }
            if (!empty($params['product_name'])) {
                $items['product_name'] = $params['product_name'];
            }
            if (!empty($params['unit'])) {
                $items['unit'] = $params['unit'];
            }
            if (!empty($params['stock_unit'])) {
                $items['stock_unit'] = $params['stock_unit'];
            }

            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where($where)
                        ->update($items)
                        ;

            $result = true;
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 仕入先IDから取得
     * 入荷 or 返品がある発注番号のみに絞る
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getOrderNoBySupplierId($supplier_id)
    {
        // Where句
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);
        // $where[] = array('pro.del_flg', '=', config('const.flg.off'));

        // 入荷 or 返品が存在する発注番号
        // $data = $this
        //         ->from('t_order AS o')
        //         ->leftJoin('t_order_detail as od', 'od.order_id', '=', 'o.id')
        //         ->leftJoin('t_arrival AS ar', 'ar.order_id', '=', 'o.id')
        //         ->leftJoin('t_warehouse_move AS wm', 'wm.order_detail_id', '=', 'od.id')
        //         ->leftJoin('t_return AS re', 're.warehouse_move_id', '=', 'wm.id')
        //         ->where($where)
        //         ->select([
        //             'o.order_no'
        //         ])
        //         ->whereNotNull('o.order_no')
        //         ->groupBy('o.order_no')
        //         ->get()
        //         ;

        $arrivalData = $this
                ->from('t_arrival as ar')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'ar.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->where($where)
                ->select([
                    'o.order_no',
                ])
                ->distinct()
                ;

        $returnData = $this
                ->from('t_return as re')
                ->leftJoin('t_warehouse_move as wm', 'wm.id', '=', 're.warehouse_move_id')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->where($where)
                ->select([
                    'o.order_no',
                ])
                ->distinct()
                ;

        $data = $arrivalData->union($returnData)->get();
        return $data;
    }

    /**
     * 発注データを並び替える
     * @param $orderId
     */
    public function updateSortByQuoteData($orderId){
        $result = true;
        try {
            $where = [];
            $where[] = array('t_order_detail.order_id', '=', $orderId);
            $where[] = array('t_order_detail.del_flg', '=', config('const.flg.off'));

            $data = $this->from('t_order_detail')
                    ->join('t_quote_detail', 't_order_detail.quote_detail_id', '=', 't_quote_detail.id')
                    ->select([
                        't_order_detail.id AS order_detail_id',
                        't_quote_detail.id AS quote_detail_id',
                        't_quote_detail.depth AS depth',
                        't_quote_detail.seq_no AS seq_no',
                        't_quote_detail.tree_path AS tree_path',
                    ])
                    ->where($where)
                    ->get()
                    ->toArray()
                    ;
            
            $tmp_depth      = array_column($data, 'depth');
            $tmp_seqNo      = array_column($data, 'seq_no');
            $tmp_TreePath   = array_column($data, 'tree_path');

            array_multisort(
                    $tmp_depth,SORT_ASC,
                    $tmp_TreePath, SORT_ASC,
                    $tmp_seqNo, SORT_ASC,
                    $data);
                    
            $seqNo = 1;
            foreach($data as $row){
                $updateRow = [];
                $updateRow['id']        = $row['order_detail_id'];
                $updateRow['seq_no']    = $seqNo;
                $seqNo++;
                $res = $this->updateById($updateRow);
                $result = $result ? $res : false;
            }
            
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 商品IDから入荷予定数取得
     *
     * @param [type] $productId
     * @return void
     */
    public function getArrivalPlanQuantity($productId) 
    {
        $where = [];
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        $where[] = array('od.product_id', '=', $productId);
        $where[] = array('od.order_quantity', '<>', 0);
        $where[] = array('o.status', '=', config('const.orderStatus.val.ordered'));
        $where[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));

        $data = DB::table('t_order_detail as od')
            ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
            ->selectRaw('
                o.delivery_address_id as warehouse_id,
                od.product_id as product_id,
                SUM(od.stock_quantity - od.arrival_quantity) as arrival_quantity
            ')
            ->where($where)
            ->groupBy('o.delivery_address_id', 'od.product_id')
            ->get()
            ;

        return $data;
    }
}
