<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Auth;
use App\Libs\LogUtil;
use DB;


/**
 * 棚番在庫マスタ
 */
class ProductstockShelf extends Model
{
    // テーブル名
    protected $table = 't_productstock_shelf';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'product_id',
        'product_code',
        'warehouse_id',
        'shelf_number_id',
        'quantity',
        'matter_id',
        'customer_id',
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
        // Where句
        $where = [];
        if (isset($params['id'])) {
            $where[] = array('id', '=', $params['id']);
        }
        if (isset($params['product_id'])) {
            $where[] = array('product_id', '=', $params['product_id']);
        }
        if (isset($params['warehouse_id'])) {
            $where[] = array('warehouse_id', '=', $params['warehouse_id']);
        }
        if (isset($params['shelf_number_id']) && $params['shelf_number_id'] != 0) {
            $where[] = array('shelf_number_id', '=', $params['shelf_number_id']);
        }
        if (isset($params['matter_id'])) {
            $where[] = array('matter_id', '=', $params['matter_id']);
        }
        if (isset($params['customer_id'])) {
            $where[] = array('customer_id', '=', $params['customer_id']);
        }
        if (isset($params['stock_flg'])) {
            $where[] = array('stock_flg', '=', $params['stock_flg']);
        }
        // データ取得
        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 一覧取得 (在庫照会)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getStockRefList($params)
    {
        // Where句
        $where = [];
        $joinWhere = [];
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));
        $joinWhere['supplier'][] = array('supplier.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse'][] = array('warehouse.del_flg', '=', config('const.flg.off'));

        if (!empty($params['product_code'])) {
            $where[] = array('product.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        // if (!empty($params['product_name'])) {
        //     $where[] = array('product.product_name', 'LIKE', '%'.$params['product_name'].'%');
        // }
        if (!empty($params['model'])) {
            $where[] = array('product.model', 'LIKE', '%' . $params['model'] . '%');
        }
        if (!empty($params['supplier_name'])) {
            $where[] = array('supplier.supplier_name', 'LIKE', '%' . $params['supplier_name'] . '%');
        }
        if (!empty($params['warehouse_name'])) {
            $where[] = array('warehouse.warehouse_name', 'LIKE', '%' . $params['warehouse_name'] . '%');
        }
        if (!empty($params['shelf_area'])) {
            $where[] = array('shelf_num.shelf_area', 'LIKE', '%' . $params['shelf_area'] . '%');
        }
        if (!empty($params['matter_id'])) {
            $where[] = array('stock.matter_id', '=', $params['matter_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('stock.customer_id', '=', $params['customer_id']);
        }

        $quantityQuery = DB::table('t_productstock_shelf AS s')
            ->join('m_shelf_number', function($join) {
                $join
                    ->on('s.shelf_number_id', '=', 'm_shelf_number.id')
                    ->where(function ($query) {
                        $query
                            ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                            ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                            ;
                    })
                    ;
            })
            ->selectRaw('
                    s.warehouse_id,
                    s.product_id,
                    SUM(s.quantity) as quantity
            ')
            ->groupBy('s.product_id', 's.warehouse_id')
            ;

        // 自社在庫数
        $stockQuery = DB::table('t_productstock_shelf AS ps')
            ->join('m_shelf_number', function($join) {
                $join
                    ->on('ps.shelf_number_id', '=', 'm_shelf_number.id')
                    ->where(function ($query) {
                        $query
                            ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                            ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                            ;
                    })
                    ;
            })
            ->whereRaw('
                ps.stock_flg = '. config('const.stockFlg.val.stock'). '
            ')
            ->selectRaw('
                ps.warehouse_id as warehouse_id,
                ps.product_id as product_id,
                SUM(ps.quantity) as inventory_quantity
            ')
            ->groupBy('ps.warehouse_id', 'ps.product_id')
            ;
            
        // データ取得
        $data = $this
            ->from('t_productstock_shelf as stock')
            ->leftjoin(DB::raw('(' . $quantityQuery->toSql() . ') as s'), function ($join) {
                $join->on('stock.warehouse_id', '=', 's.warehouse_id')
                    ->on('stock.product_id', '=', 's.product_id');
            })
            ->mergeBindings($quantityQuery)
            // ->mergeBindings($quantityQuery)
            ->leftjoin(DB::raw('(' . $stockQuery->toSql() . ') as inventory'), function ($join) {
                $join->on('stock.warehouse_id', '=', 'inventory.warehouse_id')
                    ->on('stock.product_id', '=', 'inventory.product_id');
            })
            ->mergeBindings($stockQuery)
            ->leftJoin('m_product as product', function ($join) use ($joinWhere) {
                $join->on('product.id', '=', 'stock.product_id')
                    ->where($joinWhere['product']);
            })
            ->leftJoin('m_warehouse as warehouse', function ($join) use ($joinWhere) {
                $join->on('warehouse.id', '=', 'stock.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })
            ->leftJoin('m_supplier as supplier', function ($join) use ($joinWhere) {
                $join->on('supplier.id', '=', 'product.maker_id')
                    ->where($joinWhere['supplier']);
            })
            ->leftJoin('m_general', function ($join) {
                $join->on('m_general.value_code', '=', 'supplier.juridical_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_shelf_number as shelf_num', 'shelf_num.id', '=', 'stock.shelf_number_id')
            ->leftJoin('m_product_nickname as nickname', 'product.id', '=', 'nickname.product_id')
            ->selectRaw('
                        MIN(stock.id) as id,
                        stock.product_id as product_id,
                        MIN(product.product_code) as product_code,
                        MIN(product.product_name) as product_name,
                        stock.warehouse_id as warehouse_id,
                        MIN(warehouse.warehouse_name) as warehouse_name,
                        MIN(CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\'))) as supplier_name,
                        MIN(product.model) as model,
                        s.quantity as actual_quantity,
                        inventory.inventory_quantity,
                        MIN(product.product_image) as file_name
                        ')
            ->where($where)
            ->groupBy('stock.warehouse_id', 'stock.product_id')
            ->when(!empty($params['product_name']), function ($query) use ($params) {
                $query->where('product.product_name', 'LIKE', '%' . $params['product_name'] . '%')
                    ->orWhere('nickname.another_name', 'LIKE', '%' . $params['product_name'] . '%');
            })
            ->orderBy('stock.warehouse_id')
            ->whereNotNull('stock.id')
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 商品、倉庫IDを指定して取得 (在庫照会)
     * @param $productId 商品ID
     * @param $warehouseId 倉庫ID
     * @return type 検索結果データ
     */
    public function getStockByPWId($productId, $warehouseId, $params)
    {
        // Where句
        $where = [];
        $joinWhere = [];
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));
        $joinWhere['supplier'][] = array('supplier.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse'][] = array('warehouse.del_flg', '=', config('const.flg.off'));

        if (!empty($productId)) {
            $where[] = array('product.id', '=', $productId);
        }
        if (!empty($productId)) {
            $where[] = array('warehouse.id', '=', $warehouseId);
        }
        if (!empty($params['shelf_area'])) {
            $where[] = array('shelf_num.shelf_area', 'LIKE', '%'.$params['shelf_area'].'%');
        }
        if (!empty($params['matter_id'])) {
            $where[] = array('stock.matter_id', '=', $params['matter_id']);
        }
        if (!empty($params['customer_id'])) {
            $where[] = array('stock.customer_id', '=', $params['customer_id']);
        }

        // データ取得
        $data = $this
            ->from('t_productstock_shelf as stock')
            ->leftJoin('m_product as product', function ($join) use ($joinWhere) {
                $join->on('product.id', '=', 'stock.product_id')
                    ->where($joinWhere['product']);
            })
            ->leftJoin('m_warehouse as warehouse', function ($join) use ($joinWhere) {
                $join->on('warehouse.id', '=', 'stock.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })
            ->leftJoin('m_supplier as supplier', function ($join) use ($joinWhere) {
                $join->on('supplier.id', '=', 'product.maker_id')
                    ->where($joinWhere['supplier']);
            })
            ->leftJoin('m_shelf_number as shelf_num', 'shelf_num.id', '=', 'stock.shelf_number_id')
            ->select(
                'stock.id as id',
                'stock.product_id as product_id',
                'product.product_code as product_code',
                'product.product_name as product_name',
                'stock.warehouse_id as warehouse_id',
                'warehouse.warehouse_name as warehouse_name',
                'supplier.supplier_name as supplier_name',
                'product.model as model',
                'stock.quantity as actual_quantity',
                'product.product_image as file_name'
            )
            ->where($where)
            ->orderBy('warehouse.id')
            ->get();

        return $data;
    }


    /**
     * 一覧取得(共通関数：棚種別「0のみ」)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getListMoveInventory($params)
    {
        // Where句
        $where = [];
        if (isset($params['id'])) {
            $where[] = array('p.id', '=', $params['id']);
        }
        if (isset($params['product_id'])) {
            $where[] = array('p.product_id', '=', $params['product_id']);
        }
        if (isset($params['warehouse_id'])) {
            $where[] = array('p.warehouse_id', '=', $params['warehouse_id']);
        }
        if (isset($params['shelf_number_id']) && $params['shelf_number_id'] != 0) {
            $where[] = array('p.shelf_number_id', '=', $params['shelf_number_id']);
        }
        if (isset($params['matter_id'])) {
            $where[] = array('p.matter_id', '=', $params['matter_id']);
        }
        if (isset($params['customer_id'])) {
            $where[] = array('p.customer_id', '=', $params['customer_id']);
        }
        if (isset($params['stock_flg'])) {
            $where[] = array('p.stock_flg', '=', $params['stock_flg']);
        }

        $where[] = array('s.shelf_kind', '=', $params['shelf_kind']);

        //JOIN句
        $joinWhere = [];
        $joinWhere['shelf_number'][] = array('s.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('t_productstock_shelf as p')
            ->leftJoin('m_shelf_number as s', function ($join) use ($joinWhere) {
                $join->on('s.id', '=', 'p.shelf_number_id')
                    ->where($joinWhere['shelf_number']);
            })
            ->where($where)
            ->selectRaw(
                "p.*"
            )
            ->get();
        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'stock_flg' => $params['stock_flg'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'warehouse_id' => $params['warehouse_id'],
                'shelf_number_id' => $params['shelf_number_id'],
                'quantity' => $params['quantity'],
                'matter_id' => $params['matter_id'],
                'customer_id' => $params['customer_id'],
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
     * 更新
     *
     * @param array $params
     * @return void
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
                    'stock_flg' => $params['stock_flg'],
                    'product_id' => $params['product_id'],
                    'product_code' => $params['product_code'],
                    'warehouse_id' => $params['warehouse_id'],
                    'shelf_number_id' => $params['shelf_number_id'],
                    'quantity' => $params['quantity'],
                    'matter_id' => $params['matter_id'],
                    'customer_id' => $params['customer_id'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now()
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * IDで取得
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id)
    {
        $data = $this
            ->select('t_productstock_shelf.*')
            ->where(['t_productstock_shelf.id' => $id])
            ->first();

        return $data;
    }

    /**
     * 物理削除
     * @param $id
     * @return void
     */
    public function deleteById($params)
    {
        $result = false;

        try {
            // Where句
            $where = [];
            if (isset($params['id'])) {
                $where[] = array('id', '=', $params['id']);
            }
            if (isset($params['product_id'])) {
                $where[] = array('product_id', '=', $params['product_id']);
            }
            if (isset($params['warehouse_id'])) {
                $where[] = array('warehouse_id', '=', $params['warehouse_id']);
            }
            if (isset($params['shelf_number_id'])) {
                $where[] = array('shelf_number_id', '=', $params['shelf_number_id']);
            }

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            $updateCnt = $this
                ->where($where)
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 倉庫、商品ごとに実在庫,有効在庫,引当数,入荷予定数 取得
     *
     * @return void
     */
    public function getAllQuantity()
    {
        // Where句作成
        $where = [];
        $joinWhere = [];
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));
        $joinWhere['supplier'][] = array('supplier.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse'][] = array('warehouse.del_flg', '=', config('const.flg.off'));

        $date = Carbon::now()->format('Y/m/d');

        // 最終入荷日取得
        $lastArrivalQuery = DB::table('t_arrival as arrival')
            ->selectRaw('
                        MAX(arrival.product_id) as product_id,
                        MAX(arrival.to_warehouse_id) as warehouse_id,
                        DATE_FORMAT(MAX(arrival.arrival_date), "%Y/%m/%d") as last_arrival_date
                    ')
            // ->where('arrival.arrival_date', '>=', $date)
            ->groupBy(
                'arrival.product_id',
                'arrival.to_warehouse_id'
            );

        $nextArrivalQuery = DB::table('t_order_detail as detail')
            ->leftJoin('t_order as odr', 'detail.order_id', '=', 'odr.id')
            ->selectRaw('
                        MAX(odr.delivery_address_id) as warehouse_id,
                        MAX(detail.product_id) as product_id,
                        MAX(
                            CASE 
                                WHEN odr.id IS NULL 
                                    THEN \'\' 
                                WHEN detail.arrival_plan_date IS NULL
                                    THEN \'\' 
                                ELSE
                                    DATE_FORMAT(detail.arrival_plan_date, "%Y/%m/%d")' . '
                        END) AS next_arrival_date
            ')
            // ->where('detail.arrival_plan_date', '>=', $date)
            ->groupBy('odr.delivery_address_id', 'detail.product_id');

        // 引当数
        $reserveQuery = DB::table('t_reserve as reserve')
            ->selectRaw('
                reserve.from_warehouse_id, 
                reserve.product_id,
                reserve.stock_flg,
                SUM(reserve.reserve_quantity) as reserve_quantity,
                SUM(reserve.issue_quantity) as issue_quantity
            ')
            ->groupBy(
                'reserve.from_warehouse_id',
                'reserve.product_id',
                'reserve.stock_flg'
            )
            ->whereRaw('
                reserve.finish_flg = \'' . config('const.flg.on') . '\'
            ');

        // 入荷予定数
        // $kbn = config('const.deliveryAddressKbn.val.company');


        $arrivalQuery = DB::table('t_order_detail as detail')
            ->leftJoin('t_order as odr', 'odr.id', '=', 'detail.order_id')
            ->selectRaw('
                    odr.delivery_address_id as warehouse_id,
                    detail.product_id,
                    SUM(detail.order_quantity) as arrival_quantity
            ')
            ->whereRaw('
                odr.delivery_address_kbn = ' .  config('const.deliveryAddressKbn.val.company') . '
                AND detail.arrival_quantity = 0
            ')
            // AND detail.arrival_plan_date >= '. $date .'
            ->groupBy('odr.delivery_address_id', 'detail.product_id');

        // メインデータ
        $data = DB::table('m_product as product')
            ->leftJoin('t_productstock_shelf as stock', function ($join) use ($joinWhere) {
                $join->on('product.id', '=', 'stock.product_id')
                    ->where($joinWhere['product']);
            })
            ->leftJoin('m_warehouse as warehouse', function ($join) use ($joinWhere) {
                $join->on('warehouse.id', '=', 'stock.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })
            // ->leftjoin(
            //     DB::raw('('.$reserveQuery->toSql().') as reserve'),
            //     [
            //         ['reserve.product_id', '=', 'stock.product_id'],
            //         ['reserve.from_warehouse_id', '=', 'stock.warehouse_id'],
            //         ['reserve.stock_flg', '=', 'stock.stock_flg']
            //     ] 
            // )
            ->leftjoin(
                DB::raw('(' . $arrivalQuery->toSql() . ') as arrival'),
                [
                    ['arrival.product_id', '=', 'stock.product_id'],
                    ['arrival.warehouse_id', '=', 'stock.warehouse_id']
                ]
            )
            ->mergeBindings($arrivalQuery)
            ->leftjoin(
                DB::raw('(' . $lastArrivalQuery->toSql() . ') as lastDate'),
                [
                    ['lastDate.product_id', '=', 'stock.product_id'],
                    ['lastDate.warehouse_id', '=', 'stock.warehouse_id']
                ]
            )
            ->mergeBindings($lastArrivalQuery)
            ->leftjoin(
                DB::raw('(' . $nextArrivalQuery->toSql() . ') as nextDate'),
                [
                    ['nextDate.product_id', '=', 'stock.product_id'],
                    ['nextDate.warehouse_id', '=', 'stock.warehouse_id']
                ]
            )
            ->mergeBindings($nextArrivalQuery)
            ->leftJoin('m_shelf_number as shelf_num', function ($join) {
                $join
                    ->on('shelf_num.id', '=', 'stock.shelf_number_id')
                    // ->where('shelf_num.shelf_kind', '=', config('const.shelfKind.normal'))
                ;
            })
            ->whereRaw('
                shelf_num.shelf_kind = \'' . config('const.shelfKind.normal') . '\'
                OR shelf_num.shelf_kind = \'' .  config('const.shelfKind.keep') . '\'
            ')
            // stock.id as stock_id,
            ->selectRaw('
                    stock.product_id as product_id,
                    stock.stock_flg as stock_flg,
                    stock.warehouse_id as warehouse_id,
                    warehouse.warehouse_name as warehouse_name,
                    stock.customer_id,
                    product.product_code,
                    product.product_name,
                    product.model as model,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.stock') . '\'
                            THEN SUM(stock.quantity)
                        ELSE \'' . 0 . '\'
                    END AS actual_quantity,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.keep') . '\'
                            THEN SUM(stock.quantity)
                        ELSE \'' . 0 . '\'
                    END AS keep_quantity,
                    CASE
                        WHEN stock.stock_flg <> \'' . config('const.stockFlg.val.order') . '\'
                            THEN SUM(stock.quantity)
                        ELSE \'' . 0 . '\'
                    END AS all_stock,
                    SUM(arrival.arrival_quantity) as arrival_quantity,
                    MIN(lastDate.last_arrival_date) as last_arrival_date,
                    MIN(nextDate.next_arrival_date) as next_arrival_date
            ')
            ->orderBy('warehouse.id')
            ->groupBy('stock.product_id', 'stock.warehouse_id', 'stock.customer_id', 'warehouse.warehouse_name', 'stock.stock_flg')
            ->get();


        return $data;
    }

    /**
     * 一覧取得(在庫移管)
     *
     * @param  $params
     * @return void
     */
    public function getQrStockList($params)
    {
        $where = [];
        // $where[] = array('stock.del_flg', '=', config('const.flg.off'));
        $where[] = array('stock.warehouse_id', '=', $params['from_warehouse']);
        if (!empty($params['product_code'])) {
            $where[] = array('pro.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name'])) {
            $where[] = array('pro.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['model'])) {
            $where[] = array('pro.model', 'LIKE', '%' . $params['model'] . '%');
        }
        if (!empty($params['matter_name'])) {
            $where[] = array('matter.matter_name', 'LIKE', '%' . $params['matter_name'] . '%');
        }

        $data = $this
            ->from('t_productstock_shelf as stock')
            ->leftJoin('t_qr_detail as qr_detail', function ($join) {
                $join->on('qr_detail.product_id', '=', 'stock.product_id')
                    ->on('qr_detail.warehouse_id', '=', 'stock.warehouse_id')
                    ->on('qr_detail.shelf_number_id', '=', 'stock.shelf_number_id')
                    ->on('qr_detail.matter_id', '=', 'stock.matter_id')
                    ->on('qr_detail.customer_id', '=', 'stock.customer_id')
                    ;
            })
            ->join('m_shelf_number', function($join) {
                $join
                    ->on('stock.shelf_number_id', '=', 'm_shelf_number.id')
                    ->where(function ($query) {
                        $query
                            ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                            ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                            ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.keep'))
                            ;
                    })
                    ;
            })
            ->leftJoin('t_qr as qr', 'qr.id', '=', 'qr_detail.qr_id')
            ->leftJoin('t_arrival', 't_arrival.id', '=', 'qr_detail.arrival_id')
            ->leftJoin('t_order_detail', function($join) {
                $join->on('t_arrival.order_detail_id', '=', 't_order_detail.id');
                $join->where('t_order_detail.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('t_quote_detail', function($join) {
                $join->on('t_quote_detail.id', '=', 't_order_detail.quote_detail_id');
                $join->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_product as pro', 'pro.id', '=', 'stock.product_id')
            ->leftjoin('t_matter as matter', 'stock.matter_id', '=', 'matter.id')
            ->leftJoin('m_customer as cus', 'stock.customer_id', '=', 'cus.id')
            ->where($where)
            // ->whereNotNull('qr.id')
            ->where(function ($query) use ($params) {
                // チェックボックス
                if (!empty($params['order'])) {
                    $query->orWhere('stock.stock_flg', '=', config('const.stockFlg.val.order'));
                }
                if (!empty($params['stock'])) {
                    $query->orWhere('stock.stock_flg', '=', config('const.stockFlg.val.stock'));
                }
                if (!empty($params['keep'])) {
                    $query->orWhere('stock.stock_flg', '=', config('const.stockFlg.val.keep'));
                }
            })
            // stock.quantity,
            ->whereNotNull('stock.quantity')
            // ->whereNotNull('qr_detail.quantity')
            ->selectRaw('
                    stock.id as id,
                    stock.product_id as product_id,
                    stock.warehouse_id as warehouse_id,
                    stock.stock_flg as stock_flg,
                    stock.matter_id,
                    stock.customer_id,
                    pro.product_code as product_code,
                    pro.product_name as product_name,
                    pro.model as model,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.stock') . '\'
                            THEN stock.quantity
                        ELSE qr_detail.quantity
                    END AS active_quantity,
                    stock.quantity AS actual_quantity,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.stock') . '\'
                            THEN \'\'
                        ELSE qr.qr_code
                    END AS qr_code,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.stock') . '\'
                            THEN \'0\'
                        ELSE qr_detail.quantity
                    END AS quantity,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.order') . '\'
                            THEN matter.matter_name
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.keep') . '\'
                            THEN cus.customer_name
                        ELSE \'\'
                    END AS matter_name,
                    CASE
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.stock') . '\'
                            THEN \'在庫品\'
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.order') . '\'
                            THEN \'発注品\'
                        WHEN stock.stock_flg = \'' . config('const.stockFlg.val.keep') . '\'
                            THEN \'預かり品\'
                        ELSE \'\'
                    END AS stock_status,

                    t_arrival.order_detail_id,
                    t_order_detail.quote_detail_id
            ')
            // ->groupBy('stock.stock_flg', 'stock.product_id', 'stock.warehouse_id')
            ->orderBy('stock.stock_flg')
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 一覧取得(返品受入れ)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getAcceptingReturnsList($params)
    {
        // Where句作成
        $where = [];
        if ($params['product_code'] != null && $params['product_code'] != "") {
            $where[] = array('p.product_code', '=', $params['product_code']);
        }
        if ($params['product_name'] != null && $params['product_name'] != "") {
            $where[] = array('product.product_name', '=', $params['product_name']);
        }
        $where[] = array('p.warehouse_id', '=', $params['warehouse_id']);
        $where[] = array('p.matter_id', '=', 0);
        $where[] = array('p.customer_id', '=', 0);
        $where[] = array('shelf_number.shelf_kind', '=', 0);
        $joinWhere = [];
        $joinWhere['shelf_number'][] = array('shelf_number.del_flg', '=', config('const.flg.off'));
        $joinWhere['warehouse'][] = array('warehouse.del_flg', '=', config('const.flg.off'));
        $joinWhere['product'][] = array('product.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('t_productstock_shelf as p')


            ->leftJoin('m_shelf_number as shelf_number', function ($join) use ($joinWhere) {
                $join->on('shelf_number.id', '=', 'p.shelf_number_id')
                    ->where($joinWhere['shelf_number']);
            })

            ->leftJoin('m_warehouse as warehouse', function ($join) use ($joinWhere) {
                $join->on('warehouse.id', '=', 'p.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })

            ->leftJoin('m_product as product', function ($join) use ($joinWhere) {
                $join->on('product.id', '=', 'p.product_id')
                    ->where($joinWhere['product']);
            })

            ->where($where)
            ->selectRaw(
                'p.id as productstock_shelf_id,
                null as qr_id,
                p.matter_id as matter_id,
                p.customer_id as customer_id,
                p.stock_flg,
                null as arrival_id,
                p.warehouse_id as warehouse_id,
                p.shelf_number_id as shelf_number_id,
                warehouse.warehouse_name,
                null as matter_name,
                null as customer_name,
                p.product_id as product_id,
                product.product_code as product_code,
                product.product_name as product_name,
                p.quantity as quantity,
                null as qr_code,
                shelf_number.shelf_area as shelf_area,
                shelf_number.shelf_steps as shelf_steps,
                null as order_detail_id,
                null as quote_detail_id,
                null as quote_id'
            )
            ->get();

        return $data;
    }

    /**
     * 在庫のみ取得
     *
     * @return void
     */
    public function getStockList()
    {
        $where = [];
        $where[] = array('s.matter_id', '=', config('const.flg.off'));
        $where[] = array('s.customer_id', '=', config('const.flg.off'));
        // $where[] = array('shelf_number.shelf_kind', '<>', config('const.shelfKind.return'));

        // データ取得
        $data = $this
            ->from('t_productstock_shelf AS s')
            ->leftJoin('m_shelf_number as shelf_number', 'shelf_number.id', '=', 's.shelf_number_id')
            ->leftJoin('m_warehouse as warehouse', 'warehouse.id', '=', 's.warehouse_id')
            ->leftJoin('t_qr_detail AS qr_detail', function ($join) {
                $join
                    ->on('qr_detail.product_id', '=', 's.product_id')
                    ->on('qr_detail.warehouse_id', '=', 's.warehouse_id')
                    ->on('qr_detail.matter_id', '=', 's.matter_id')
                    ->on('qr_detail.customer_id', '=', 's.customer_id')
                    ->on('qr_detail.shelf_number_id', '=', 's.shelf_number_id');
            })
            ->where($where)
            // ->whereNull('qr_detail.id')
            ->whereRaw('
                qr_detail.id IS NULL
            ')
            ->select(
                's.id AS id',
                's.warehouse_id AS warehouse_id',
                's.product_id AS product_id',
                'warehouse.warehouse_name',
                's.quantity AS quantity',
                'shelf_number.shelf_area AS shelf_area',
                'shelf_number.shelf_steps AS shelf_steps'
            )
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 棚番在庫取得
     *
     * @param  $productId 
     * @param  $warehouseId
     * @param  $stockFlg
     * @return void
     */
    public function getStockByProductIdAndWarehouseId($productId, $warehouseId, $stockFlg, $matterId, $customerId)
    {
        $where = [];
        $where[] = array('stock.matter_id', '=', $matterId);
        $where[] = array('stock.customer_id', '=', $customerId);
        $where[] = array('stock.product_id', '=', $productId);
        $where[] = array('stock.warehouse_id', '=', $warehouseId);
        $where[] = array('stock.stock_flg', '=', $stockFlg);
        if ($stockFlg == config('const.stockFlg.val.stock')) {
            $where[] = array('shelf_number.shelf_kind', '=', config('const.shelfKind.normal'));
        }
        if ($stockFlg == config('const.stockFlg.val.keep')) {
            $where[] = array('shelf_number.shelf_kind', '=', config('const.shelfKind.keep'));
        }

        // データ取得
        $data = $this
            ->from('t_productstock_shelf AS stock')
            ->leftJoin('m_shelf_number as shelf_number', 'shelf_number.id', '=', 'stock.shelf_number_id')
            ->where($where)
            ->select(
                'stock.id AS id',
                'stock.warehouse_id AS warehouse_id',
                'stock.product_id AS product_id',
                'stock.shelf_number_id',
                'stock.quantity AS quantity',
                'shelf_number.shelf_area AS shelf_area',
                'shelf_number.shelf_steps AS shelf_steps'
            )
            ->distinct()
            ->first();

        return $data;
    }

    /**
     * 倉庫別在庫管理（倉庫単位）
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getStockOrderPointWarehouse($params)
    {
        // Where句
        $where = [];
        $joinWhere = [];
        $joinWhere['warehouse'][] = array('w.del_flg', '=', config('const.flg.off'));
        $joinWhere['base'][] = array('b.del_flg', '=', config('const.flg.off'));
        $joinWhere['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinWhere['order_detail'][] = array('od.del_flg', '=', config('const.flg.off'));

        if (!empty($params['product_code']) && $params['product_code'] != "") {
            $where[] = array('p.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name']) && $params['product_name'] != "") {
            $where[] = array('p.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['model']) && $params['model'] != "") {
            $where[] = array('p.model', 'LIKE', '%' . $params['model'] . '%');
        }
        if (!empty($params['base_name'])) {
            $where[] = array('b.base_name', 'LIKE', '%' . $params['base_name'] . '%');
        }

        $where[] = array('ps.stock_flg', '=', 1);


        //返品棚数取得サブクエリ
        $returnQuery = $this
            ->from('t_productstock_shelf as ps')
            ->leftJoin('m_shelf_number as s', function ($join) use ($joinWhere) {
                $join->on('s.id', '=', 'ps.shelf_number_id')
                    ->on('s.warehouse_id', '=', 'ps.warehouse_id')
                    ->whereRaw('s.del_flg = 0');
            })
            ->selectRaw('
                                    ps.product_id,
                                    ps.warehouse_id,
                                    SUM(quantity) as quantity
                                ')
            ->whereRaw('ps.stock_flg = 1')
            ->groupBy('ps.product_id', 'ps.warehouse_id')
            ->whereRaw('s.shelf_kind = 3')
            ->toSql();

        //引当数取得サブクエリ
        $reserveQuery = $this
            ->from('t_reserve')
            ->selectRaw('
                                product_id,
                                from_warehouse_id as warehouse_id,
                                SUM(reserve_quantity_validity) as reserve_quantity
                            ')
            ->whereRaw('stock_flg = 1')
            ->groupBy('product_id', 'from_warehouse_id')
            ->toSql();

        //発注数取得サブクエリ
        $orderQuery = $this
            ->from('t_order as o')
            ->leftJoin('t_order_detail as od', function ($join) use ($joinWhere) {
                $join->on('o.id', '=', 'od.order_id')
                    ->whereRaw('od.del_flg = 0');
            })
            ->selectRaw('
                                od.product_id
                                , o.delivery_address_id as warehouse_id
                                , SUM(od.order_quantity - od.arrival_quantity) as order_quantity
                            ')
            ->whereRaw('o.delivery_address_kbn = 2 and o.own_stock_flg = 1')
            ->groupBy('od.product_id', 'o.delivery_address_id')
            ->toSql();


        // データ取得
        $data = $this
            ->from('t_productstock_shelf as ps')
            ->leftJoin('m_warehouse as w', function ($join) use ($joinWhere) {
                $join->on('w.id', '=', 'ps.warehouse_id')
                    ->where($joinWhere['warehouse']);
            })
            ->leftJoin('m_base as b', function ($join) use ($joinWhere) {
                $join->on('b.id', '=', 'w.owner_id')
                    ->where($joinWhere['base']);
            })
            ->leftJoin('m_product as p', function ($join) use ($joinWhere) {
                $join->on('p.id', '=', 'ps.product_id')
                    ->where($joinWhere['product']);
            })
            ->leftjoin(DB::raw('(' . $reserveQuery . ') as r'), function ($join) {
                $join->on('r.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('r.product_id', '=', 'ps.product_id');
            })
            ->leftjoin(DB::raw('(' . $orderQuery . ') as o'), function ($join) {
                $join->on('o.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('o.product_id', '=', 'ps.product_id');
            })
            ->leftjoin(DB::raw('(' . $returnQuery . ') as re'), function ($join) {
                $join->on('re.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('re.product_id', '=', 'ps.product_id');
            })

            ->selectRaw('
                            b.id as base_id
                            , b.base_name
                            , ps.warehouse_id
                            , w.warehouse_name
                            , ps.product_id
                            , p.product_code
                            , p.product_name
                            , p.model
                            , p.unit
                            , sum(ps.quantity) - ifnull(reserve_quantity, 0) - ifnull(re.quantity, 0) as active_quantity
                            , sum(ps.quantity) as actual_quantity
                            , ifnull(min(r.reserve_quantity), 0) as reserve_quantity
                            , ifnull(min(o.order_quantity), 0) as order_quantity 
                        ')
            ->where($where)
            ->groupBy('ps.warehouse_id', 'ps.product_id')
            ->orderByRaw('b.id,ps.product_id,ps.warehouse_id')
            ->get();
        Log::error($data);

        return $data;
    }

    /**
     * 倉庫別在庫管理（倉庫単位）
     * @param array $params 検索条件の配列
     * @return string sql
     */
    public function getStockOrderPointWarehouseSql($params)
    {
        $where = '1 = 1';
        if (!empty($params['product_code'])) {
            $where = $where . " and p.product_code LIKE '%" . $params['product_code'] . "%'";
        }
        if (!empty($params['product_name'])) {
            $where = $where . " and p.product_name LIKE '%" . $params['product_name'] . "%'";
        }
        if (!empty($params['model'])) {
            $where = $where . " and p.model LIKE '%" . $params['model'] . "%'";
        }
        if (!empty($params['base_name'])) {
            $where = $where . " and b.base_name LIKE '%" . $params['base_name'] . "%'";
        }

        $where = $where . " and ps.stock_flg = 1";

        //返品棚数取得サブクエリ
        $returnQuery = $this
            ->from('t_productstock_shelf as ps')
            ->leftJoin('m_shelf_number as s', function ($join) {
                $join->on('s.id', '=', 'ps.shelf_number_id')
                    ->on('s.warehouse_id', '=', 'ps.warehouse_id')
                    ->whereRaw('s.del_flg = 0');
            })
            ->selectRaw('
                                  ps.product_id,
                                  ps.warehouse_id,
                                  SUM(quantity) as quantity
                              ')
            ->groupBy('ps.product_id', 'ps.warehouse_id')
            ->whereRaw('s.shelf_kind = 3 and ps.stock_flg = 1')
            ->toSql();

        //引当数取得サブクエリ
        $reserveQuery = $this
            ->from('t_reserve')
            ->selectRaw('
                                product_id,
                                from_warehouse_id as warehouse_id,
                                SUM(reserve_quantity_validity) as reserve_quantity
                            ')
            ->whereRaw('stock_flg = 1')
            ->groupBy('product_id', 'from_warehouse_id')
            ->toSql();

        //発注数取得サブクエリ
        $orderQuery = $this
            ->from('t_order as o')
            ->leftJoin('t_order_detail as od', function ($join) {
                $join->on('o.id', '=', 'od.order_id')
                    ->whereRaw('od.del_flg = 0');
            })
            ->selectRaw('
                                od.product_id
                                , o.delivery_address_id as warehouse_id
                                , SUM(od.order_quantity - od.arrival_quantity) as order_quantity
                            ')
            ->whereRaw('o.delivery_address_kbn = 2 and o.own_stock_flg = 1')
            ->groupBy('od.product_id', 'o.delivery_address_id')
            ->toSql();


        // データ取得
        $sql = $this
            ->from('t_productstock_shelf as ps')
            ->leftJoin('m_warehouse as w', function ($join) {
                $join->on('w.id', '=', 'ps.warehouse_id')
                    ->whereRaw('w.del_flg = 0');
            })
            ->leftJoin('m_base as b', function ($join) {
                $join->on('b.id', '=', 'w.owner_id')
                    ->whereRaw('b.del_flg = 0');
            })
            ->leftJoin('m_product as p', function ($join) {
                $join->on('p.id', '=', 'ps.product_id')
                    ->whereRaw('p.del_flg = 0');
            })
            ->leftjoin(DB::raw('(' . $reserveQuery . ') as r'), function ($join) {
                $join->on('r.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('r.product_id', '=', 'ps.product_id');
            })
            ->leftjoin(DB::raw('(' . $orderQuery . ') as o'), function ($join) {
                $join->on('o.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('o.product_id', '=', 'ps.product_id');
            })
            ->leftjoin(DB::raw('(' . $returnQuery . ') as re'), function ($join) {
                $join->on('re.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('re.product_id', '=', 'ps.product_id');
            })

            ->selectRaw('
                            b.id as base_id
                            , b.base_name
                            , ps.warehouse_id
                            , w.warehouse_name
                            , ps.product_id
                            , p.product_code
                            , p.product_name
                            , p.model
                            , p.unit
                            , sum(ps.quantity) - ifnull(r.reserve_quantity, 0) - ifnull(re.quantity, 0) as active_quantity
                            , sum(ps.quantity) as actual_quantity
                            , ifnull(min(r.reserve_quantity), 0) as reserve_quantity
                            , ifnull(min(o.order_quantity), 0) as order_quantity 
                        ')
            ->whereRaw($where)
            ->groupBy('ps.warehouse_id', 'ps.product_id')
            ->orderByRaw('b.id,ps.product_id,ps.warehouse_id')
            ->toSql();

        return $sql;
    }

    /**
     * 倉庫別在庫管理（拠点単位）
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getStockOrderPointBase($params)
    {
        // 倉庫別取得サブクエリ
        $subQuery = $this->getStockOrderPointWarehouseSql($params);


        // データ取得
        $sql1 = $this
            ->from(DB::Raw('(' . $subQuery . ') as t'))
            ->leftJoin('t_order_limit as ol', function ($join) {
                $join->on('ol.base_id', '=', 't.base_id')
                    ->on('ol.product_id', '=', 't.product_id')
                    ->whereRaw('ol.del_flg = 0');
            })

            ->selectRaw('
                            case 
                            when ol.order_limit is not null 
                            and SUM(t.active_quantity) < ol.order_limit 
                            then 1 
                            else 0 
                            end as should_order
                        , t.base_id
                        , t.base_name
                        , t.product_id
                        , t.product_code
                        , t.product_name
                        , t.model
                        , t.unit
                        , ol.id as order_limit_id
                        , ol.order_limit as order_limit
                        , 0 as input_order_limit
                        , SUM(t.active_quantity) as active_quantity
                        , SUM(t.actual_quantity) as actual_quantity
                        , SUM(t.reserve_quantity) as reserve_quantity
                        , SUM(t.order_quantity) as order_quantity 
                        ,CONCAT(t.base_id,"_",t.product_id) AS id
                ')
            ->groupBy('t.base_id', 't.product_id')
            ->orderByRaw('t.base_id , t.product_id')
            ->toSql();

        $sql2 = $this
            ->from(DB::Raw('(' . $subQuery . ') as t'))
            ->rightJoin('t_order_limit as ol', function ($join) {
                $join->on('ol.base_id', '=', 't.base_id')
                    ->on('ol.product_id', '=', 't.product_id')
                    ->whereRaw('ol.del_flg = 0');
            })
            ->leftJoin('m_base as b', function ($join) {
                $join->on('ol.base_id', '=', 'b.id');
            })
            ->leftJoin('m_product as p', function ($join) {
                $join->on('ol.product_id', '=', 'p.id');
            })

            ->selectRaw('
                            case 
                            when ol.order_limit is not null 
                            and SUM(ifnull(t.active_quantity,0)) < ol.order_limit 
                            then 1 
                            else 0 
                            end as should_order
                        , ol.base_id
                        , b.base_name
                        , ol.product_id
                        , p.product_code
                        , p.product_name
                        , p.model
                        , p.unit
                        , ol.id as order_limit_id
                        , ol.order_limit as order_limit
                        , 0 as input_order_limit
                        , SUM(t.active_quantity) as active_quantity
                        , SUM(t.actual_quantity) as actual_quantity
                        , SUM(t.reserve_quantity) as reserve_quantity
                        , SUM(t.order_quantity) as order_quantity 
                        , CONCAT(ol.base_id,"_",ol.product_id) AS id
                ')
            ->groupBy('ol.base_id', 'ol.product_id')
            ->orderByRaw('t.base_id , t.product_id')
            ->toSql();

        //取得結果に連番を付与
        $where = [];
        if (!empty($params['base_name'])) {
            $where[] = array('sub_table.base_name', '=', $params['base_name']);
        }
        if (!empty($params['product_code']) && $params['product_code'] != "") {
            $where[] = array('sub_table.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name']) && $params['product_name'] != "") {
            $where[] = array('sub_table.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['model'])) {
            $where[] = array('sub_table.model', 'LIKE', '%' . $params['model'] . '%');
        }
        $convertQuery = DB::table(DB::raw("(({$sql1}) UNION ALL ({$sql2})) sub_table"));
        $data = $convertQuery->select([
            'sub_table.*'
        ])
            ->where($where)
            ->distinct()
            ->get();
        Log::error($data);

        return $data;
    }

    /**
     * 一覧取得　商品転換
     *
     * @param  $params
     * @return void
     */
    public function getProductList($params)
    {
        $where = [];
        $where[] = array('p.del_flg', '=', config('const.flg.off'));
        $where[] = array('stock.stock_flg', '=', config('const.stockFlg.val.stock'));
        $where[] = array('sn.shelf_kind', '=', config('const.shelfKind.normal'));

        if (!empty($params['warehouse_name'])) {
            $where[] = array('w.id', '=', $params['warehouse_name']);
        }
        if (!empty($params['product_code']) && $params['product_code'] != "") {
            $where[] = array('p.product_code', 'LIKE', '%' . $params['product_code'] . '%');
        }
        if (!empty($params['product_name']) && $params['product_name'] != "") {
            $where[] = array('p.product_name', 'LIKE', '%' . $params['product_name'] . '%');
        }
        if (!empty($params['model'])) {
            $where[] = array('p.model', 'LIKE', '%' . $params['model'] . '%');
        }

        $reserveQuery = DB::table('t_reserve AS res')
            ->whereRaw('
                            res.finish_flg = ' . config('const.flg.off') . '
                            AND res.stock_flg = ' . config('const.stockFlg.val.stock'))
            ->selectRaw('
                            res.product_id,
                            res.from_warehouse_id,
                            res.from_shelf_number_id,
                            SUM(res.reserve_quantity - res.issue_quantity) AS reserve_quantity
                        ')
            ->groupBy('res.product_id', 'res.from_warehouse_id', 'res.from_shelf_number_id')
            ->toSql();

        $data = $this
            ->from('t_productstock_shelf AS stock')
            ->leftJoin('m_product AS p', 'p.id', '=', 'stock.product_id')
            ->leftJoin('m_warehouse AS w', 'w.id', '=', 'stock.warehouse_id')
            ->leftJoin('m_shelf_number AS sn', 'sn.id', '=', 'stock.shelf_number_id')
            ->leftjoin(DB::raw('(' . $reserveQuery . ') as rs'), function ($join) {
                $join
                    ->on('stock.warehouse_id', '=', 'rs.from_warehouse_id')
                    ->on('stock.product_id', '=', 'rs.product_id')
                    ->on('stock.shelf_number_id', '=', 'rs.from_shelf_number_id');
            })
            ->where($where)
            ->selectRaw('
                    stock.id,
                    stock.product_id,
                    stock.warehouse_id,
                    p.product_code,
                    p.product_name,
                    p.model,
                    sn.id AS shelf_number_id,
                    sn.shelf_area,
                    CASE
                        WHEN stock.id IS NULL
                            THEN \'\'
                        WHEN rs.reserve_quantity > 0
                            THEN stock.quantity - rs.reserve_quantity
                        ELSE stock.quantity
                    END AS stock_quantity
                ')
            ->orderBy('p.product_code')
            ->distinct()
            // ->groupBy('stock.stock.product_id', 'stock.warehouse_id')
            // stock.quantity - rs.reserve_quantity AS stock_quantity
            ->get();

        return $data;
    }
    /**
     * コンボボックスリスト取得(いきなり売上)
     *
     * @return 
     */
    public function getComboCounterSale($warehouseId)
    {
        $where = [];
        $where[] = array('ps.stock_flg', '=', 1);
        $where[] = array('ps.warehouse_id', '=', $warehouseId);
        $where[] = array('sn.shelf_kind', '=', 0);


        // //入荷予定数のサブクエリ
        // $arrivalQuantityQuery = $this
        //     ->from('t_arrival as arrival')
        //     ->selectRaw('
        //         arrival.product_id,
        //         arrival.to_warehouse_id as warehouse_id,
        //         SUM(arrival.quantity) as arrival_quantity
        //         ')
        //     ->whereRaw('arrival.del_flg = ' . config('const.flg.off'))
        //     ->groupBy('arrival.product_id', 'arrival.to_warehouse_id')
        //     ->toSql();

        // $whereSub[] = array('od.del_flg', '=', config('const.flg.off'));
        // $whereSub[] = array('o.del_flg', '=', config('const.flg.off'));
        // $whereSub[] = array('o.own_stock_flg', '=', 1);
        // $whereSub[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        // $whereSub[] = array('od.arrival_quantity', '=', 0);

        $arrivalQuantityQuery = $this
            ->from('t_order_detail AS od')
            ->whereRaw('od.del_flg = 0')
            ->whereRaw('o.del_flg = 0')
            ->whereRaw('o.own_stock_flg = 1')
            ->whereRaw('o.delivery_address_kbn = ' . config('const.deliveryAddressKbn.val.company'))
            ->whereRaw('od.arrival_quantity = 0')
            ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
            ->selectRaw('
                        o.delivery_address_id AS warehouse_id,
                        od.product_id,
                        SUM(od.order_quantity) AS arrival_quantity
                    ')
            ->groupBy('od.product_id', 'o.delivery_address_id')
            ->toSql();

        //入荷予定日のサブクエリ
        $arrivalDateQuery = $this
            ->from('t_order_detail as order_detail')
            ->leftJoin('t_order as order', 'order_detail.order_id', '=', 'order.id')
            ->selectRaw('order_detail.product_id,
                            order.delivery_address_id as warehouse_id,
                             
                            CASE 
                                WHEN min(order.id) IS NULL 
                                    THEN \'' . '' . '\' 
                                WHEN min(order_detail.arrival_plan_date) IS NULL
                                    THEN \'' . '' . '\' 
                                ELSE
                                    DATE_FORMAT(min(order_detail.arrival_plan_date), "%Y/%m/%d")' . '
                            END AS next_arrival_date
                            ')
            ->whereRaw('order.delivery_address_kbn = ' . config('const.deliveryAddressKbn.val.company') . ' and order_detail.arrival_plan_date is not null 
                        and order.del_flg = ' . config('const.flg.off'))
            ->groupBy('order_detail.product_id', 'order.delivery_address_id')
            ->toSql();

        //引当数取得サブクエリ
        $reserveQuery = $this
            ->from('t_reserve')
            ->selectRaw('
                product_id,
                from_warehouse_id as warehouse_id,
                SUM(reserve_quantity_validity) as reserve_quantity_validity
            ')
            ->whereRaw('del_flg = 0 and finish_flg = 0 and stock_flg = 1')
            ->groupBy('product_id', 'from_warehouse_id')
            ->toSql();

        //木材立米単価サブクエリのサブクエリ
        $woodSubQuery = $this
            ->from('t_wood_unitprice')
            ->selectRaw('product_id,max(price_date) as price_date')
            ->whereRaw("del_flg = 0 and price_date <= DATE('" . Carbon::now() . "')")
            ->groupBy('product_id')
            ->toSql();

        //木材立米単価サブクエリ
        $woodQuery = $this
            ->from('t_wood_unitprice as wu')
            ->join(DB::raw('(' . $woodSubQuery . ') as s1'), function ($join) {
                $join->on('wu.product_id', '=', 's1.product_id')
                    ->on('wu.price_date', '=', 's1.price_date');
            })
            ->selectRaw(' wu.product_id
                         ,wu.purchase_unit_price
                         ,wu.sales_unit_price')
            ->toSql();

        //商品仕入単価サブクエリのサブクエリ
        $productCostSubQuery = $this
            ->from('t_product_cost_price')
            ->selectRaw('product_id, max(id) as id')
            ->groupBy('product_id')
            ->toSql();

        //商品仕入単価サブクエリ
        $productCostQuery = $this
            ->from('t_product_cost_price as pcp')
            ->join(DB::raw('(' . $productCostSubQuery . ') as s2'), function ($join) {
                $join->on('pcp.id', '=', 's2.id')
                    ->on('pcp.product_id', '=', 's2.product_id');
            })
            ->selectRaw(' pcp.product_id,
                          pcp.price')
            ->toSql();

        // データ取得
        $data = $this
            ->from('t_productstock_shelf as ps')

            ->leftjoin(DB::raw('(' . $arrivalQuantityQuery . ') as aq'), function ($join) {
                $join->on('aq.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('aq.product_id', '=', 'ps.product_id');
            })

            ->leftjoin(DB::raw('(' . $arrivalDateQuery . ') as ad'), function ($join) {
                $join->on('ad.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('ad.product_id', '=', 'ps.product_id');
            })

            ->leftjoin(DB::raw('(' . $reserveQuery . ') as r'), function ($join) {
                $join->on('r.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('r.product_id', '=', 'ps.product_id');
            })

            ->leftJoin('m_product as p', 'p.id', '=', 'ps.product_id')
            ->leftJoin('m_supplier as s', 's.id', '=', 'p.maker_id')

            ->leftJoin(DB::raw('(' . $woodQuery . ') as wu'), function ($join) {
                $join->on('wu.product_id', '=', 'p.id');
            })

            ->leftJoin(DB::raw('(' . $productCostQuery . ') as pcp'), function ($join) {
                $join->on('pcp.product_id', '=', 'p.id');
            })
            ->leftJoin('m_shelf_number as sn', function ($join) {
                $join->on('sn.id', '=', 'ps.shelf_number_id')
                    ->on('sn.warehouse_id', '=', 'ps.warehouse_id')
                    ->whereRaw('sn.del_flg = 0');
            })


            ->where($where)
            ->whereRaw('(ps.quantity - ifnull(r.reserve_quantity_validity,0)) > 0')
            ->selectRaw(
                'p.id,
                 p.product_code,
                 p.product_name,
                 p.maker_id,
                 p.model,
                 p.unit,
                 p.price,
                 p.min_quantity,
                 p.normal_purchase_price,
                 p.stock_unit,
                 ifnull(wu.sales_unit_price,p.normal_sales_price) as normal_sales_price,
                 ifnull(wu.purchase_unit_price ,ifnull(pcp.price,p.normal_purchase_price)) as purchase_price,
                 (ps.quantity - ifnull(r.reserve_quantity_validity,0)) as actual_quantity,
                 ps.warehouse_id,
                 s.supplier_name,
                 ad.next_arrival_date,
                 ifnull(aq.arrival_quantity,0) as arrival_quantity
                 '
            )
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 商品の在庫情報取得(いきなり売上)
     *
     * @return 
     */
    public function getProduct($productId, $warehouseId)
    {
        $where = [];
        $where[] = array('ps.stock_flg', '=', 1);
        $where[] = array('sn.shelf_kind', '=', 0);
        $where[] = array('ps.product_id', '=', $productId);
        $where[] = array('ps.warehouse_id', '=', $warehouseId);


        // //入荷予定数のサブクエリ
        // $arrivalQuantityQuery = $this
        //     ->from('t_arrival as arrival')
        //     ->selectRaw('
        //         arrival.product_id,
        //         arrival.to_warehouse_id as warehouse_id,
        //         SUM(arrival.quantity) as arrival_quantity
        //         ')
        //     ->whereRaw('arrival.del_flg = ' . config('const.flg.off'))
        //     ->groupBy('arrival.product_id', 'arrival.to_warehouse_id')
        //     ->toSql();

        // $whereSub[] = array('od.del_flg', '=', config('const.flg.off'));
        // $whereSub[] = array('o.del_flg', '=', config('const.flg.off'));
        // $whereSub[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        // $whereSub[] = array('o.own_stock_flg', '=', 1);
        // $whereSub[] = array('od.arrival_quantity', '=', 0);

        $arrivalQuantityQuery = $this
        ->from('t_order_detail AS od')
        ->whereRaw('od.del_flg = 0')
        ->whereRaw('o.del_flg = 0')
        ->whereRaw('o.own_stock_flg = 1')
        ->whereRaw('o.delivery_address_kbn = ' . config('const.deliveryAddressKbn.val.company'))
        ->whereRaw('od.order_quantity <> 0')
        ->whereRaw('o.status = ' . config('const.orderStatus.val.ordered'))
        ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
        ->selectRaw('
                    o.delivery_address_id AS warehouse_id,
                    od.product_id,
                    SUM(od.stock_quantity - od.arrival_quantity) AS arrival_quantity
                ')
        ->groupBy('od.product_id', 'o.delivery_address_id')
        ->toSql();


        //入荷予定日のサブクエリ
        $arrivalDateQuery = $this
            ->from('t_order_detail as order_detail')
            ->leftJoin('t_order as order', 'order_detail.order_id', '=', 'order.id')
            ->selectRaw('order_detail.product_id,
                            order.delivery_address_id as warehouse_id,
                             
                            CASE 
                                WHEN max(order.id) IS NULL 
                                    THEN \'' . '' . '\' 
                                WHEN max(order_detail.arrival_plan_date) IS NULL
                                    THEN \'' . '' . '\' 
                                ELSE
                                    DATE_FORMAT(max(order_detail.arrival_plan_date), "%Y/%m/%d")' . '
                            END AS next_arrival_date
                            ')
            ->whereRaw('order.delivery_address_kbn = ' . config('const.deliveryAddressKbn.val.company') . ' and order_detail.arrival_plan_date is not null 
                        and order.del_flg = ' . config('const.flg.off'))
            ->groupBy('order_detail.product_id', 'order.delivery_address_id')
            ->toSql();

        //引当数取得サブクエリ
        $reserveQuery = $this
            ->from('t_reserve')
            ->selectRaw('
                product_id,
                from_warehouse_id as warehouse_id,
                SUM(reserve_quantity_validity) as reserve_quantity_validity
            ')
            ->whereRaw('del_flg = 0 and finish_flg = 0 and stock_flg = 1')
            ->groupBy('product_id', 'from_warehouse_id')
            ->toSql();

        //木材立米単価サブクエリのサブクエリ
        $woodSubQuery = $this
            ->from('t_wood_unitprice')
            ->selectRaw('product_id,max(price_date) as price_date')
            ->whereRaw("del_flg = 0 and price_date <= DATE('" . Carbon::now() . "')")
            ->groupBy('product_id')
            ->toSql();

        //木材立米単価サブクエリ
        $woodQuery = $this
            ->from('t_wood_unitprice as wu')
            ->join(DB::raw('(' . $woodSubQuery . ') as s1'), function ($join) {
                $join->on('wu.product_id', '=', 's1.product_id')
                    ->on('wu.price_date', '=', 's1.price_date');
            })
            ->selectRaw(' wu.product_id
                         ,wu.purchase_unit_price
                         ,wu.sales_unit_price')
            ->toSql();

        //商品仕入単価サブクエリのサブクエリ
        $productCostSubQuery = $this
            ->from('t_product_cost_price')
            ->selectRaw('product_id, max(id) as id')
            ->groupBy('product_id')
            ->toSql();

        //商品仕入単価サブクエリ
        $productCostQuery = $this
            ->from('t_product_cost_price as pcp')
            ->join(DB::raw('(' . $productCostSubQuery . ') as s2'), function ($join) {
                $join->on('pcp.id', '=', 's2.id')
                    ->on('pcp.product_id', '=', 's2.product_id');
            })
            ->selectRaw(' pcp.product_id,
                          pcp.price')
            ->toSql();

        // データ取得
        $data = $this
            ->from('t_productstock_shelf as ps')

            ->leftjoin(DB::raw('(' . $arrivalQuantityQuery . ') as aq'), function ($join) {
                $join->on('aq.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('aq.product_id', '=', 'ps.product_id');
            })

            ->leftjoin(DB::raw('(' . $arrivalDateQuery . ') as ad'), function ($join) {
                $join->on('ad.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('ad.product_id', '=', 'ps.product_id');
            })

            ->leftjoin(DB::raw('(' . $reserveQuery . ') as r'), function ($join) {
                $join->on('r.warehouse_id', '=', 'ps.warehouse_id')
                    ->on('r.product_id', '=', 'ps.product_id');
            })

            ->leftJoin('m_product as p', 'p.id', '=', 'ps.product_id')
            ->leftJoin('m_supplier as s', 's.id', '=', 'p.maker_id')

            ->leftJoin(DB::raw('(' . $woodQuery . ') as wu'), function ($join) {
                $join->on('wu.product_id', '=', 'p.id');
            })

            ->leftJoin(DB::raw('(' . $productCostQuery . ') as pcp'), function ($join) {
                $join->on('pcp.product_id', '=', 'p.id');
            })

            ->leftJoin('m_shelf_number as sn', function ($join) {
                $join->on('sn.id', '=', 'ps.shelf_number_id')
                    ->on('sn.warehouse_id', '=', 'ps.warehouse_id')
                    ->whereRaw('sn.del_flg = 0');
            })

            ->where($where)
            ->whereRaw('(ps.quantity - ifnull(r.reserve_quantity_validity,0)) > 0')
            ->selectRaw(
                'p.id,
                 p.product_code,
                 p.product_name,
                 p.maker_id,
                 p.model,
                 p.unit,
                 p.price,
                 p.min_quantity,
                 p.stock_unit,
                 ifnull(wu.sales_unit_price , p.normal_sales_price) as normal_sales_price,
                 ifnull(wu.purchase_unit_price , ifnull(pcp.price,p.normal_purchase_price)) as purchase_price,
                 (ps.quantity - ifnull(r.reserve_quantity_validity,0)) as actual_quantity,
                 ps.warehouse_id,
                 s.supplier_name,
                 ad.next_arrival_date,
                 ifnull(aq.arrival_quantity,0) as arrival_quantity
                 '
            )
            ->first();

        return $data;
    }


    /**
     * 返品棚、預かり品の在庫数取得
     *
     * @param [type] $warehouseId
     * @param [type] $productId
     * @return void
     */
    public function getReturnAndKeepQuantity($warehouseId, $productId)
    {
        $where = [];
        $where[] = array('stock.warehouse_id', '=', $warehouseId);
        $where[] = array('stock.product_id', '=', $productId);
        $where[] = array('sn.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_productstock_shelf AS stock')
            ->leftJoin('m_shelf_number AS sn', 'sn.id', '=', 'stock.shelf_number_id')
            ->where(function ($query) {
                $query
                    ->where('sn.shelf_kind', '=', config('const.shelfKind.keep'))
                    ->orWhere('sn.shelf_kind', '=', config('const.shelfKind.return'));
            })
            ->selectRaw('
                    stock.product_id,
                    stock.warehouse_id,
                    SUM(stock.quantity) AS quantity
                ')
            ->whereNotNull('sn.id')
            ->where($where)
            ->groupBy('stock.warehouse_id', 'stock.product_id')
            ->first();

        return $data;
    }


    /**
     * 同商品の在庫がある棚番を返す
     * なければ通常棚を返す
     *
     * @param $productId
     * @param $warehouseId
     * @return boolean
     */
    public function getStockShelfList($productId, $warehouseId, $stockFlg, $customerId = null)
    {
        $data = null;

        $where = [];
        // $where[] = array('m_shelf_number.del_flg', '=', config('const.flg.off'));
        $where[] = array('stock.product_id', '=', $productId);
        $where[] = array('stock.warehouse_id', '=', $warehouseId);
        $where[] = array('stock.stock_flg', '=', $stockFlg);
        if ($stockFlg == config('const.stockFlg.val.keep')) {
            $where[] = array('stock.customer_id', '=', $customerId);
        }

        $shelfStock = $this
            ->from('t_productstock_shelf as stock')
            ->leftJoin('m_shelf_number', function ($join) {
                $join
                    ->on('stock.shelf_number_id', '=', 'm_shelf_number.id')
                    ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                    ->where('m_shelf_number.del_flg', '=', config('const.flg.off'));
            })
            ->where($where)
            ->select([
                'm_shelf_number.*',
            ])
            ->whereNotNull('m_shelf_number.id')
            ->get();

        $ShelfNumber = new ShelfNumber();
        if (count($shelfStock) > 0) {
            $data = $shelfStock;
        } else {
            $params = [];
            $params['product_id'] = $productId;
            $params['warehouse_id'] = $warehouseId;
            $params['shelf_kind'] = config('const.shelfKind.normal');

            $data = $ShelfNumber->getList($params);
        }
        if (empty($data)) {
            $data = [];
        }

        return $data;
    }


    /**
     * 在庫引当　子グリッドデータ取得
     *
     * @param int $customerId       得意先ID
     * @param array $productIdList  商品ID配列
     * @return void
     */
    public function getQuantityList($customerId, $productIdList)
    {
        // Where句作成
        $where = [];

        // 自社在庫数
        $stockQuery = DB::table('t_productstock_shelf AS ps')
                ->join('m_shelf_number', function($join) {
                    $join
                        ->on('ps.shelf_number_id', '=', 'm_shelf_number.id')
                        ->where(function ($query) {
                            $query
                                ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                                ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                                ;
                        })
                        ;
                })
                ->whereRaw('
                    ps.stock_flg = '. config('const.stockFlg.val.stock'). '
                ')
                ->selectRaw('
                    ps.warehouse_id as warehouse_id,
                    ps.product_id as product_id,
                    SUM(ps.quantity) as inventory_quantity
                ')
                ->groupBy('ps.warehouse_id', 'ps.product_id')
                ;

        // 得意先の預かり在庫
        $keepCustomerQuery = DB::table('t_productstock_shelf AS ps')
                ->join('m_shelf_number', function($join) {
                    $join
                        ->on('ps.shelf_number_id', '=', 'm_shelf_number.id')
                        // ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.keep'))
                        ->where(function ($query) {
                            $query
                                ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                                ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                                ;
                        })
                        ;
                })
                ->whereRaw('
                    ps.stock_flg = '. config('const.stockFlg.val.keep'). '
                    AND ps.customer_id = '. $customerId. '
                    AND (
                        ps.matter_id IS NULL
                        OR ps.matter_id = 0
                    )
                ')
                ->selectRaw('
                    ps.warehouse_id as warehouse_id,
                    ps.product_id as product_id,
                    SUM(ps.quantity) as quantity
                ')
                ->groupBy('ps.warehouse_id', 'ps.product_id', 'ps.stock_flg', 'ps.customer_id')
                ;
        
        // 預かり在庫
        $keepQuery = DB::table('t_productstock_shelf AS ps')
                ->join('m_shelf_number', function($join) {
                    $join
                        ->on('ps.shelf_number_id', '=', 'm_shelf_number.id')
                        // ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.keep'))
                        ->where(function ($query) {
                            $query
                                ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                                ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                                ;
                        })
                        ;
                })
                ->whereRaw('
                    ps.stock_flg = '. config('const.stockFlg.val.keep'). '
                ')
                ->selectRaw('
                    ps.warehouse_id as warehouse_id,
                    ps.product_id as product_id,
                    SUM(ps.quantity) as quantity
                ')
                ->groupBy('ps.warehouse_id', 'ps.product_id', 'ps.stock_flg')
                ;

        // 発注品/在庫の実在庫
        $orderAndStockQuery = DB::table('t_productstock_shelf AS ps')
                ->join('m_shelf_number', function($join) {
                    $join
                        ->on('ps.shelf_number_id', '=', 'm_shelf_number.id')
                        // ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.keep'))
                        ->where(function ($query) {
                            $query
                                ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                                ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                                ;
                        })
                        ;
                })
                ->whereRaw('
                    ps.stock_flg = '. config('const.stockFlg.val.stock'). '
                    OR ps.stock_flg = '. config('const.stockFlg.val.order'). '
                ')
                ->selectRaw('
                    ps.warehouse_id as warehouse_id,
                    ps.product_id as product_id,
                    SUM(ps.quantity) as quantity
                ')
                ->groupBy('ps.warehouse_id', 'ps.product_id')
                ;


        // 実在庫数
        $actualQuery = DB::table('t_productstock_shelf')
                ->join('m_shelf_number', function($join) {
                    $join
                        ->on('t_productstock_shelf.shelf_number_id', '=', 'm_shelf_number.id')
                        ->where(function ($query) {
                            $query
                                ->where('m_shelf_number.shelf_kind', '=', config('const.shelfKind.normal'))
                                ->orWhere('m_shelf_number.shelf_kind', '=', config('const.shelfKind.temporary'))
                                ;
                        })
                        ;
                })
                ->leftjoin(
                    DB::raw('(' . $stockQuery->toSql() . ') as stock'),
                    [
                        ['stock.product_id', '=', 't_productstock_shelf.product_id'],
                        ['stock.warehouse_id', '=', 't_productstock_shelf.warehouse_id']
                    ]
                )
                ->mergeBindings($stockQuery)
                ->leftjoin(
                    DB::raw('(' . $keepCustomerQuery->toSql() . ') as keep_customer'),
                    [
                        ['keep_customer.product_id', '=', 't_productstock_shelf.product_id'],
                        ['keep_customer.warehouse_id', '=', 't_productstock_shelf.warehouse_id']
                    ]
                )
                ->mergeBindings($keepCustomerQuery)
                ->leftjoin(
                    DB::raw('(' . $keepQuery->toSql() . ') as keep'),
                    [
                        ['keep.product_id', '=', 't_productstock_shelf.product_id'],
                        ['keep.warehouse_id', '=', 't_productstock_shelf.warehouse_id']
                    ]
                )
                ->mergeBindings($keepQuery)
                ->leftjoin(
                    DB::raw('(' . $orderAndStockQuery->toSql() . ') as order_stock'),
                    [
                        ['order_stock.product_id', '=', 't_productstock_shelf.product_id'],
                        ['order_stock.warehouse_id', '=', 't_productstock_shelf.warehouse_id']
                    ]
                )
                ->mergeBindings($orderAndStockQuery)
                ->selectRaw('
                    t_productstock_shelf.warehouse_id as warehouse_id,
                    t_productstock_shelf.product_id as product_id,
                    stock.inventory_quantity,
                    keep.quantity as keep_quantity,
                    keep_customer.quantity as customer_keep_quantity,
                    order_stock.quantity as quantity,
                    SUM(t_productstock_shelf.quantity) as all_quantity,
                    0 as arrival_quantity
                ')
                ->whereIn('t_productstock_shelf.product_id', $productIdList)
                ->groupBy('t_productstock_shelf.warehouse_id', 't_productstock_shelf.product_id')
                // ->get()
                ;

        $acutalData = $actualQuery->get();

        $notInIdList = [];
        foreach ($acutalData as $key => $row) {

            $tmpObj = [];
            $tmpObj['product_id'] = $row->product_id;
            $tmpObj['warehouse_id'] = $row->warehouse_id;
            $notInIdList[] = $tmpObj;
        }

        // 入荷予定
        $arrivalWhere = [];
        $arrivalWhere[] = array('o.del_flg', '=', config('const.flg.off'));
        $arrivalWhere[] = array('od.del_flg', '=', config('const.flg.off'));
        $arrivalWhere[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        $arrivalWhere[] = array('od.order_quantity', '<>', 0);
        $arrivalWhere[] = array('o.status', '=', config('const.orderStatus.val.ordered'));
        $arrivalWhere[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));

        $arrivalQuery = DB::table('t_order_detail as od')
            ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
            ->selectRaw('
                o.delivery_address_id as warehouse_id,
                od.product_id as product_id,
                0 as inventory_quantity,
                0 as keep_quantity,
                0 as customer_keep_quantity,
                0 as quantity,
                0 as all_quantity,
                SUM(od.stock_quantity - od.arrival_quantity) as arrival_quantity
            ')
            ->where($arrivalWhere)
            ->whereIn('od.product_id', $productIdList)
            ->when(count($notInIdList) > 0, function($query) use($notInIdList) {
                foreach($notInIdList as $row) {
                    $query
                        ->whereRaw('
                            NOT(
                                od.product_id = '. $row['product_id'] .'
                                AND
                                o.delivery_address_id = '. $row['warehouse_id'] .'
                            )
                        ')
                        ;
                }
            })
            ->groupBy('o.delivery_address_id', 'od.product_id')
            // ->get()
            ;
        
        $data = $actualQuery->union($arrivalQuery)->get();

        return $data;
    }

     /**
     * 同一倉庫に既に自社在庫が存在するかどうか
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function isExistOwnStock($warehouseId,$fromShelfNumberId,$toShelfNumberId,$productId)
    {
        $where = [];
        $where[] = array('ps.warehouse_id', '=', $warehouseId);
        $where[] = array('ps.shelf_number_id', '<>', $fromShelfNumberId);
        $where[] = array('ps.shelf_number_id', '<>', $toShelfNumberId);
        $where[] = array('ps.product_id', '=', $productId);
        $where[] = array('s.shelf_kind', '=', 0);
        $where[] = array('ps.stock_flg', '=', 1);

        $data = $this
            ->from('t_productstock_shelf as ps')
            ->leftJoin('m_shelf_number as s', 's.id', '=', 'ps.shelf_number_id')
            ->selectRaw('
                    *
                ')
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 自社在庫数取得
     *
     * @param int $warehouseId
     * @param int $fromShelfNumberId
     * @param int $stockFlg
     * @return boolean
     */
    public function getOwnStock($productId, $warehouseId, $stockFlg)
    {
        $result = false;

        $where = [];
        $where[] = array('ps.warehouse_id', '=', $warehouseId);
        $where[] = array('ps.product_id', '=', $productId);
        $where[] = array('ps.stock_flg', '=', $stockFlg);
        $where[] = array('s.shelf_kind', '=', config('const.shelfKind.normal'));

        $subWhere = [];
        $subWhere[] = array('t_reserve.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('t_reserve.finish_flg', '=', config('const.flg.off'));
        $subWhere[] = array('t_reserve.stock_flg', '=', $stockFlg);

        //引当数取得サブクエリ
        $reserveQuery = DB::table('t_reserve')
            ->selectRaw('
                t_reserve.product_id,
                t_reserve.from_warehouse_id as warehouse_id,
                SUM(t_reserve.reserve_quantity_validity) as reserve_quantity_validity
            ')
            ->where($subWhere)
            ->groupBy('product_id', 'from_warehouse_id');

        // 在庫数
        $data = $this->from('t_productstock_shelf AS ps')
                ->join('m_shelf_number as s', function($join) {
                    $join
                        ->on('ps.shelf_number_id', '=', 's.id')
                        ->where(function ($query) {
                            $query
                                ->where('s.shelf_kind', '=', config('const.shelfKind.normal'))
                                ;
                        })
                        ;
                })
                ->leftjoin(DB::raw('(' . $reserveQuery->toSql() . ') as reserve'), function ($join) {
                    $join->on('ps.warehouse_id', '=', 'reserve.warehouse_id')
                        ->on('ps.product_id', '=', 'reserve.product_id');
                })
                ->mergeBindings($reserveQuery)
                ->where($where)
                ->selectRaw('
                    ps.warehouse_id as warehouse_id,
                    ps.product_id as product_id,
                    SUM(ps.quantity) as inventory_quantity,
                    reserve.reserve_quantity_validity
                ')
                ->groupBy('ps.warehouse_id', 'ps.product_id')
                ->first()
                ;


        return $data;
    }

    /**
     * 在庫数取得
     *
     * @param int $warehouseId
     * @param int $fromShelfNumberId
     * @param int $stockFlg
     * @param int $shefKind
     * @return boolean
     */
    public function getStockByFlgAndKind($productId, $warehouseId, $stockFlg, $shefKind)
    {
        $result = false;

        $where = [];
        $where[] = array('ps.warehouse_id', '=', $warehouseId);
        $where[] = array('ps.product_id', '=', $productId);
        $where[] = array('ps.stock_flg', '=', $stockFlg);
        $where[] = array('s.shelf_kind', '=', $shefKind);

        $subWhere = [];
        $subWhere[] = array('t_reserve.del_flg', '=', config('const.flg.off'));
        $subWhere[] = array('t_reserve.finish_flg', '=', config('const.flg.off'));
        $subWhere[] = array('t_reserve.stock_flg', '=', $stockFlg);

        //引当数取得サブクエリ
        $reserveQuery = DB::table('t_reserve')
            ->selectRaw('
                t_reserve.product_id,
                t_reserve.from_warehouse_id as warehouse_id,
                SUM(t_reserve.reserve_quantity_validity) as reserve_quantity_validity
            ')
            ->where($subWhere)
            ->groupBy('product_id', 'from_warehouse_id');

        // 在庫数
        $data = $this->from('t_productstock_shelf AS ps')
                ->join('m_shelf_number as s', function($join) {
                    $join
                        ->on('ps.shelf_number_id', '=', 's.id')
                        ;
                })
                ->leftjoin(DB::raw('(' . $reserveQuery->toSql() . ') as reserve'), function ($join) {
                    $join->on('ps.warehouse_id', '=', 'reserve.warehouse_id')
                        ->on('ps.product_id', '=', 'reserve.product_id');
                })
                ->mergeBindings($reserveQuery)
                ->where($where)
                ->selectRaw('
                    ps.warehouse_id as warehouse_id,
                    ps.product_id as product_id,
                    SUM(ps.quantity) as inventory_quantity,
                    reserve.reserve_quantity_validity
                ')
                ->groupBy('ps.warehouse_id', 'ps.product_id')
                ->first()
                // ->toSql()
                ;


        return $data;
    }

    /**
     * 商品IDで取得
     *
     * @param [type] $productId
     * @return void
     */
    public function isExistByProductId($productId) 
    {
        $where = [];
        $where[] = array('product_id', '=', $productId);

        $data = $this
                ->where($where)
                ->get()
                ;

        return count($data) > 0;
    }

    /**
     * 商品が存在するか
     *
     * @param [type] $productId
     * @param [type] $warehouseId
     * @param [type] $shelfNumberId
     * @param [type] $stockFlg
     * @param [type] $matterId
     * @param [type] $customerId
     * @return boolean
     */
    public function isOwnStock($productId, $warehouseId, $shelfNumberId, $stockFlg, $matterId, $customerId) 
    {
        $where = [];
        $where[] = array('product_id', '=', $productId);
        $where[] = array('warehouse_id', '=', $warehouseId);
        $where[] = array('shelf_number_id', '=', $shelfNumberId);
        $where[] = array('stock_flg', '=', $stockFlg);
        $where[] = array('matter_id', '=', $matterId);
        $where[] = array('customer_id', '=', $customerId);

        $data = $this
                ->where($where)
                ->get()
                ;

        return count($data) > 0;
    }

    /**
     * 案件在庫（発注品の在庫）の存在確認
     *
     * @param int $matterId 案件ID
     * @return boolean true:存在する false：存在しない
     */
    public function hasMatterStock($matterId) {
        // Where句
        $where = [];
        $where[] = array('matter_id', '=', $matterId);

        // 存在チェック
        $result = $this
                ->where($where)
                ->exists()
                ;

        return $result;
    }
}
