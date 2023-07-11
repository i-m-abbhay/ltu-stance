<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;
use App\Libs\Common;

/**
 * 商品マスタ
 */
class Product extends Model
{
    // テーブル名
    protected $table = 'm_product';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句作成
        $where = [];
        $whereDate = [];
        $hasDate = false;
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        if (!empty($params['class_big_id'])) {
            $where[] = array('m_product.class_big_id', '=', $params['class_big_id']);
        }
        if (!empty($params['class_middle_id'])) {
            $where[] = array('m_product.class_middle_id', '=', $params['class_middle_id']);
        }
        if (!empty($params['class_small_id'])) {
            $usage = $params['class_small_id'];
        }
        if (!empty($params['construction_id'])) {
            $const = $params['construction_id'];
        }
        if (!empty($params['product_code'])) {
            $where[] = array('m_product.product_code', 'LIKE', '%'.$params['product_code'].'%');
        }
        // if (empty($params['product_name'])) {
            // $where[] = array('m_product.product_name', 'LIKE', '%'.$params['product_name'].'%');
            // $params['product_name'] = "''";
        // }
        if (!empty($params['model'])) {
            $where[] = array('m_product.model', 'LIKE', '%'.$params['model'].'%');
        }
        if (!empty($params['maker_id'])) {
            $where[] = array('m_product.maker_id', '=', $params['maker_id']);
        }
        if (!empty($params['created_from_date'])) {
            $hasDate = true;
            $whereDate[] = array('m_product.created_at', '>=', $params['created_from_date'].' 00:00:00');
        }
        if (!empty($params['created_to_date'])) {
            $hasDate = true;
            $whereDate[] = array('m_product.created_at', '<=', $params['created_to_date'].' 23:59:59');
        }
        if (!empty($params['updated_from_date'])) {
            $hasDate = true;
            $whereDate[] = array('m_product.update_at', '>=', $params['updated_from_date'].' 00:00:00');
        }
        if (!empty($params['updated_to_date'])) {
            $hasDate = true;
            $whereDate[] = array('m_product.update_at', '<=', $params['updated_to_date'].' 23:59:59');
        }
        
        if (isset($params['draft_flg']) && isset($params['auto_flg'])) {
            if (Common::isFlgOn($params['draft_flg']) && !Common::isFlgOn($params['auto_flg'])) {
                $where[] = array('m_product.auto_flg', '=', config('const.flg.off'));
            }
            if (!Common::isFlgOn($params['draft_flg']) && Common::isFlgOn($params['auto_flg'])) {
                $where[] = array('m_product.auto_flg', '=', config('const.flg.on'));
            }
        }


        // クエリ作成
        $query = $this
                ->leftJoin('m_class_big as big', 'big.id', '=', 'm_product.class_big_id')
                ->leftJoin('m_class_middle as mid', 'mid.id', '=', 'm_product.class_middle_id')
                ->leftJoin('m_construction AS con1', 'con1.id', '=', 'm_product.construction_id_1')
                ->leftJoin('m_construction AS con2', 'con2.id', '=', 'm_product.construction_id_2')
                ->leftJoin('m_construction AS con3', 'con3.id', '=', 'm_product.construction_id_3')
                ->leftJoin('m_supplier as sup', 'sup.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general', function($join) {
                    $join->on('sup.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })                
                ;

        if (!empty($params['class_small_id'])){
        $query->where(function ($query) use($usage){
                    $query->where('m_product.class_small_id_1', '=', $usage)
                          ->orWhere('m_product.class_small_id_2', '=', $usage)
                          ->orWhere('m_product.class_small_id_3', '=', $usage);
                });
        }
        if (!empty($params['construction_id'])){
            $query->where(function ($query) use($const){
                        $query->where('m_product.construction_id_1', '=', $const)
                              ->orWhere('m_product.construction_id_2', '=', $const)
                              ->orWhere('m_product.construction_id_3', '=', $const);
                });
        }
        // if (!empty($params['product_name'])) {
        // $query->where(function ($query) use ($params) {
        //         $query->where('m_product.product_name', '=', $params['product_name'])
        //               ->orWhere('nickname.another_name', '=', $params['product_name'])
        //               ;
        // });
        //     $productQuery = DB::table('m_product')
        //                     ->leftJoin('m_product_nickname as nickname', function($join) {
        //                         $join->on('nickname.product_id', '=', 'm_product.id')                                     
        //                                 ;
        //                     })
        //                     ->where(function($query) use($params) {
        //                         $query->where('nickname.another_name','LIKE', '%'.'?'.'%')
        //                                 ->orWhere('m_product.product_name', 'LIKE', '%'.'?'.'%')
        //                                 ;
        //                     })
        //                     ->select('m_product.id')
        //                     ->toSql();
        //                     ;
        // } else {
        //     $productQuery = DB::table('m_product')
        //                     ->select('m_product.id')
        //                     ->toSql();
        //                     ;
        // }
        
        $query = $query
                ->where($where)                
                ->when($hasDate, function($query) use($whereDate) {
                    return $query->where($whereDate);
                })
                ->selectRaw('
                    big.class_big_name,
                    mid.class_middle_name,
                    con1.construction_name AS construction_name_1,
                    con2.construction_name AS construction_name_2,
                    con3.construction_name AS construction_name_3,
                    m_product.*,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(sup.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as supplier_name
                ')
                ->orderBy('m_product.id', 'asc')
                ;
                
        // データ取得
        if (!empty($params['product_name'])) {
            $nickNameQuery = $this
                            ->leftJoin('m_product_nickname AS nickname', 'nickname.product_id', '=', 'm_product.id')
                            ->where('nickname.another_name', 'LIKE', '%'.$params['product_name'].'%')
                            ->orWhere('m_product.product_name', 'LIKE', '%'.$params['product_name'].'%')
                            ->select('m_product.id')
                            ->get()
                            ;

            $nickNameQuery->toArray();

            $data = $query
                    ->whereIn('m_product.id', $nickNameQuery)
                    ->get()
                    ;
                    // nickname.another_name LIKE \'%'.$params['product_name'].'%\'
                    // or m_product.product_name LIKE \'%'.$params['product_name'].'%\'
        } else {
            $data = $query->get();
        }
        
        return $data;
    }

     /**
     * 登録 CSV取込
     *
     * @param array $params
     * @return boolean
     */
    public function importCsv($params) {
        try {
            $where = [];
            $where[] = array('product_code', '=', $params['product_code']);
            $where[] = array('maker_id', '=', $params['maker_id']);
            $where[] = array('del_flg', '=', config('const.flg.off'));
            $where[] = array('draft_flg', '=', config('const.flg.off'));

            // 存在チェック
            $data = $this
                        ->where($where)
                        ->first()
                        ;

            $isExist = ($data != null);

            $items = [];
            $items['product_code'] = $params['product_code'];
            $items['product_name'] = $params['product_name'];
            
            if (!empty($params['product_short_name'])) {
                $items['product_short_name'] = $params['product_short_name'];
            }
            if (!empty($params['class_big_id'])) {
                $items['class_big_id'] = $params['class_big_id'];
            }
            if (!empty($params['class_middle_id'])) {
                $items['class_middle_id'] = $params['class_middle_id'];
            }
            if (!empty($params['construction_id_1'])) {
                $items['construction_id_1'] = $params['construction_id_1'];
            }
            if (!empty($params['construction_id_2'])) {
                $items['construction_id_2'] = $params['construction_id_2'];
            }
            if (!empty($params['construction_id_3'])) {
                $items['construction_id_3'] = $params['construction_id_3'];
            }
            if (!empty($params['class_small_id_1'])) {
                $items['class_small_id_1'] = $params['class_small_id_1'];
            }
            if (!empty($params['class_small_id_2'])) {
                $items['class_small_id_2'] = $params['class_small_id_2'];
            }
            if (!empty($params['class_small_id_3'])) {
                $items['class_small_id_3'] = $params['class_small_id_3'];
            }
            if (!empty($params['set_kbn'])) {
                $items['set_kbn'] = $params['set_kbn'];
            }
            if (!empty($params['model'])) {
                $items['model'] = $params['model'];
            }
            if (!empty($params['tree_species'])) {
                $items['tree_species'] = $params['tree_species'];
            }
            if (!empty($params['grade'])) {
                $items['grade'] = $params['grade'];
            }
            if (!empty($params['length'])) {
                $items['length'] = $params['length'];
            }
            if (!empty($params['thickness'])) {
                $items['thickness'] = $params['thickness'];
            }
            if (!empty($params['width'])) {
                $items['width'] = $params['width'];
            }
            if (!empty($params['weight'])) {
                $items['weight'] = $params['weight'];
            }
            if (!empty($params['maker_id'])) {
                $items['maker_id'] = $params['maker_id'];
            }
            if (!empty($params['price'])) {
                $items['price'] = $params['price'];
            }
            if (!empty($params['open_price_flg'])) {
                $items['open_price_flg'] = $params['open_price_flg'];
            }
            if (!empty($params['min_quantity'])) {
                $items['min_quantity'] = $params['min_quantity'];
            }
            if (!empty($params['stock_unit'])) {
                $items['stock_unit'] = $params['stock_unit'];
            }
            if (!empty($params['quantity_per_case'])) {
                $items['quantity_per_case'] = $params['quantity_per_case'];
            }
            if (!empty($params['lead_time'])) {
                $items['lead_time'] = $params['lead_time'];
            }
            if (!empty($params['order_lot_quantity'])) {
                $items['order_lot_quantity'] = $params['order_lot_quantity'];
            }
            if (!empty($params['purchase_makeup_rate'])) {
                $items['purchase_makeup_rate'] = $params['purchase_makeup_rate'];
            }
            if (!empty($params['normal_purchase_price'])) {
                $items['normal_purchase_price'] = $params['normal_purchase_price'];
            }
            if (!empty($params['unit'])) {
                $items['unit'] = $params['unit'];
            }
            if (!empty($params['sales_makeup_rate'])) {
                $items['sales_makeup_rate'] = $params['sales_makeup_rate'];
            }
            if (!empty($params['normal_sales_price'])) {
                $items['normal_sales_price'] = $params['normal_sales_price'];
            }
            if (!empty($params['normal_gross_profit_rate'])) {
                $items['normal_gross_profit_rate'] = $params['normal_gross_profit_rate'];
            }
            if (!empty($params['tax_type'])) {
                $items['tax_type'] = $params['tax_type'];
            }
            if (!empty($params['product_image'])) {
                $items['product_image'] = $params['product_image'];
            }
            if (!empty($params['start_date'])) {
                $items['start_date'] = $params['start_date'];
            }
            if (!empty($params['end_date'])) {
                $items['end_date'] = $params['end_date'];
            }
            if (!empty($params['warranty_term'])) {
                $items['warranty_term'] = $params['warranty_term'];
            }
            if (!empty($params['housing_history_transfer_kbn'])) {
                $items['housing_history_transfer_kbn'] = $params['housing_history_transfer_kbn'];
            }
            if (!empty($params['new_product_id'])) {
                $items['new_product_id'] = $params['new_product_id'];
            }
            if (!empty($params['memo'])) {
                $items['memo'] = $params['memo'];
            }
            if (!empty($params['intangible_flg'])) {
                $items['intangible_flg'] = $params['intangible_flg'];
            }
            if (!$isExist) {
                $items['created_user'] = Auth::user()->id;
                $items['created_at'] = Carbon::now();
            }
            $items['draft_flg'] = config('const.flg.off');
            $items['del_flg'] = config('const.flg.off');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            if (!$isExist) {
                // 登録
                $rtn['result'] = $this->insert($items);
                $rtn['kbn'] = config('const.flg.off');
            } else {
                // 更新
                $updateCnt = $this
                            ->where('id', $data['id'])
                            ->update($items)
                            ;

                $rtn['result'] = ($updateCnt > 0);
                $rtn['kbn'] = config('const.flg.on');
            }

        } catch (\Exception $e) {
            throw $e;
        }
        
        return $rtn;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params) {
        $result = false;
        try {
            $result = $this->insertGetId([
                    'product_code' => $params['product_code'],
                    'product_name' => $params['product_name'],
                    'product_short_name' => $params['product_short_name'],
                    'class_big_id' => $params['class_big_id'],
                    'class_middle_id' => $params['class_middle_id'],
                    'construction_id_1' => $params['construction_id_1'],
                    'construction_id_2' => $params['construction_id_2'],
                    'construction_id_3' => $params['construction_id_3'],
                    'class_small_id_1' => $params['class_small_id_1'],
                    'class_small_id_2' => $params['class_small_id_2'],
                    'class_small_id_3' => $params['class_small_id_3'],
                    'unit' => $params['unit'],
                    'model' => $params['model'],
                    'tree_species' => $params['tree_species'],
                    'grade' => $params['grade'],
                    'length' => $params['length'],
                    'thickness' => $params['thickness'],
                    'width' => $params['width'],
                    'weight' => $params['weight'],
                    'maker_id' => $params['maker_id'],
                    'price' => $params['price'],
                    'open_price_flg' => $params['open_price_flg'],
                    'min_quantity' => $params['min_quantity'],
                    'stock_unit' => $params['stock_unit'],
                    'quantity_per_case' => $params['quantity_per_case'],
                    'lead_time' => $params['lead_time'],
                    'order_lot_quantity' => $params['order_lot_quantity'],
                    'purchase_makeup_rate' => $params['purchase_makeup_rate'],
                    'normal_purchase_price' => $params['normal_purchase_price'],
                    // 'sales_unit' => $params['sales_unit'],
                    'sales_makeup_rate' => $params['sales_makeup_rate'],
                    'normal_sales_price' => $params['normal_sales_price'],
                    'normal_gross_profit_rate' => $params['normal_gross_profit_rate'],
                    'tax_type' => $params['tax_type'],
                    // 'tax_kbn' => $params['tax_kbn'],
                    'product_image' => $params['product_image'],
                    'start_date' => $params['start_date'],
                    'end_date' => $params['end_date'],
                    'warranty_term' => $params['warranty_term'],
                    'housing_history_transfer_kbn' => $params['housing_history_transfer_kbn'],
                    'new_product_id' => $params['new_product_id'],
                    'memo' => $params['memo'],
                    'intangible_flg' => $params['intangible_flg'],
                    'draft_flg' => config('const.flg.off'),
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

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'product_code' => $params['product_code'],
                        'product_name' => $params['product_name'],
                        'product_short_name' => $params['product_short_name'],
                        'class_big_id' => $params['class_big_id'],
                        'class_middle_id' => $params['class_middle_id'],
                        'construction_id_1' => $params['construction_id_1'],
                        'construction_id_2' => $params['construction_id_2'],
                        'construction_id_3' => $params['construction_id_3'],
                        'class_small_id_1' => $params['class_small_id_1'],
                        'class_small_id_2' => $params['class_small_id_2'],
                        'class_small_id_3' => $params['class_small_id_3'],
                        'unit' => $params['unit'],
                        'model' => $params['model'],
                        'tree_species' => $params['tree_species'],
                        'grade' => $params['grade'],
                        'length' => $params['length'],
                        'thickness' => $params['thickness'],
                        'width' => $params['width'],
                        'weight' => $params['weight'],
                        'maker_id' => $params['maker_id'],
                        'price' => $params['price'],
                        'open_price_flg' => $params['open_price_flg'],
                        'min_quantity' => $params['min_quantity'],
                        'stock_unit' => $params['stock_unit'],
                        'quantity_per_case' => $params['quantity_per_case'],
                        'lead_time' => $params['lead_time'],
                        'order_lot_quantity' => $params['order_lot_quantity'],
                        'purchase_makeup_rate' => $params['purchase_makeup_rate'],
                        'normal_purchase_price' => $params['normal_purchase_price'],
                        // 'sales_unit' => $params['sales_unit'],
                        'sales_makeup_rate' => $params['sales_makeup_rate'],
                        'normal_sales_price' => $params['normal_sales_price'],
                        'normal_gross_profit_rate' => $params['normal_gross_profit_rate'],
                        'tax_type' => $params['tax_type'],
                        // 'tax_kbn' => $params['tax_kbn'],
                        'product_image' => $params['product_image'],
                        'start_date' => $params['start_date'],
                        'end_date' => $params['end_date'],
                        'warranty_term' => $params['warranty_term'],
                        'housing_history_transfer_kbn' => $params['housing_history_transfer_kbn'],
                        'new_product_id' => $params['new_product_id'],
                        'memo' => $params['memo'],
                        'intangible_flg' => $params['intangible_flg'],
                        'draft_flg' => $params['draft_flg'],
                        'auto_flg' => $params['auto_flg'],
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
     * IDから削除（論理削除）
     *
     * @param int $id 商品ID
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
     * 適用終了日更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateEndDate($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $this
                    ->where('id', $params['id'])
                    ->where(function ($query) {
                        $query
                            ->where('end_date','>', Carbon::now()->format('Y-m-d'))
                            ->orWhereNull('end_date')
                            ;
                    })
                    ->update([
                        'end_date' => Carbon::now()->format('Y-m-d'),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
    
            $result = true;
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
    
    /**
     * IDで取得
     * @param int $id 商品ID
     * @return type 検索結果データ
     */
    public function getInId($IDs) 
    {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        // 案件Where句
        $matterWhere = [];
        // $matterWhere[] = array('m.del_flg', '=', config('const.flg.off'));
        // $matterWhere[] = array('m.complete_flg', '=', config('const.flg.off'));
        $matterWhere[] = array('p.draft_flg', '=', config('const.flg.off'));
        $matterWhere[] = array('p.del_flg', '=', config('const.flg.off'));
        // 見積明細Where句
        $quoteDetailWhere = [];
        $quoteDetailWhere[] = array('qd.del_flg', '=', config('const.flg.off'));
        // 見積Where句
        $quoteWhere = [];
        $quoteWhere[] = array('q.del_flg', '=', config('const.flg.off'));

        /** 案件情報取得 */ 
        // 案件取得のサブクエリ
        $matterSubQuery = DB::table('t_matter AS m')
                ->leftJoin('t_quote AS q', function($join) use($quoteWhere){
                    $join
                        ->on('q.matter_no', '=', 'm.matter_no')
                        ->where($quoteWhere)
                        ;
                })
                ->join('t_quote_detail AS qd', function($join) use($quoteDetailWhere, $IDs){
                    $join
                        ->on('q.quote_no', '=', 'qd.quote_no')
                        ->where($quoteDetailWhere)
                        ->whereIn('qd.product_id', $IDs)
                        ;
                })
                ->leftJoin('m_product AS p', function($join) {
                    $join
                        ->on('p.id', '=', 'qd.product_id')
                        ;
                })
                ->where($matterWhere)
                ->select(DB::raw('MAX(m.id) AS matter_id'), 'qd.product_id')
                ->groupBy('qd.product_id')
                ;
        
        // 画面上に表示するのはMAX(案件ID)
        $matterQuery = DB::table('t_matter AS m1')
                ->join(DB::raw('('.$matterSubQuery->toSql().') AS m2'), function($join){
                    $join->on('m2.matter_id', 'm1.id');
                })
                ->mergeBindings($matterSubQuery)
                ->select(
                    'm1.id AS matter_id',
                    'm1.matter_no',
                    'm1.matter_name',
                    'm2.product_id'
                )
                ->orderBy('m2.product_id')
                // ->get()
                ;

        $data = $this
                ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftjoin(DB::raw('('.$matterQuery->toSql().') AS matter'), function($join){
                    $join
                        ->on('m_product.id', 'matter.product_id');
                })
                ->mergeBindings($matterQuery)
                ->leftJoin('m_product AS new_product', 'new_product.id', '=', 'm_product.new_product_id')
                ->leftJoin('m_staff AS created_staff', 'created_staff.id', '=', 'm_product.created_user')
                ->whereIn('m_product.id', $IDs)
                ->where($where)
                ->selectRaw('
                    m_product.id,
                    m_product.product_code,
                    m_product.product_name,
                    m_product.product_short_name,
                    m_product.class_big_id,
                    m_product.class_middle_id,
                    m_product.construction_id_1,
                    m_product.construction_id_2,
                    m_product.construction_id_3,
                    m_product.class_small_id_1,
                    m_product.class_small_id_2,
                    m_product.class_small_id_3,
                    m_product.set_kbn,
                    m_product.model,
                    m_product.tree_species,
                    m_product.grade,
                    m_product.length,
                    m_product.thickness,
                    m_product.width,
                    m_product.weight,
                    m_product.maker_id,
                    m_product.price,
                    m_product.open_price_flg,
                    m_product.min_quantity,
                    m_product.stock_unit,
                    m_product.quantity_per_case,
                    m_product.lead_time,
                    m_product.order_lot_quantity,
                    m_product.purchase_makeup_rate,
                    m_product.normal_purchase_price,
                    m_product.unit,
                    m_product.sales_makeup_rate,
                    m_product.normal_sales_price,
                    m_product.normal_gross_profit_rate,
                    m_product.tax_type,
                    m_product.product_image,
                    m_product.start_date,
                    m_product.end_date,
                    m_product.warranty_term,
                    m_product.housing_history_transfer_kbn,
                    m_product.new_product_id,
                    new_product.product_code AS new_product_code,
                    m_product.memo,
                    m_product.intangible_flg,
                    m_product.draft_flg,
                    m_product.auto_flg,
                    m_product.del_flg,
                    m_product.created_user,
                    m_product.created_at,
                    m_product.update_user,
                    m_product.update_at,
                    CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\')) as maker_name,
                    CASE 
                          WHEN m_product.open_price_flg = \''. config('const.flg.off') .'\' 
                            THEN \'いいえ\' 
                          WHEN m_product.open_price_flg = \''. config('const.flg.on') .'\' 
                            THEN \'はい\'
                    END AS open_price_txt,
                    CASE 
                          WHEN m_product.intangible_flg = \''. config('const.flg.off') .'\' 
                            THEN \'有形品\' 
                          WHEN m_product.intangible_flg = \''. config('const.flg.on') .'\' 
                            THEN \'無形品\'
                    END AS intangible_flg_txt,
                    matter.matter_id AS matter_id,
                    matter.matter_no AS matter_no,
                    matter.matter_name AS matter_name,
                    created_staff.staff_name AS created_staff
                ')
                // ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * IDで取得
     * @param int $id 商品ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->where(['m_product.id' => $id])
                ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftJoin('m_product AS new_product', 'new_product.id', '=', 'm_product.new_product_id')
                ->selectRaw('
                    m_product.id,
                    m_product.product_code,
                    m_product.product_name,
                    m_product.product_short_name,
                    m_product.class_big_id,
                    m_product.class_middle_id,
                    m_product.construction_id_1,
                    m_product.construction_id_2,
                    m_product.construction_id_3,
                    m_product.class_small_id_1,
                    m_product.class_small_id_2,
                    m_product.class_small_id_3,
                    m_product.new_product_id,
                    new_product.product_code AS new_product_code,
                    m_product.set_kbn,
                    m_product.model,
                    m_product.tree_species,
                    m_product.grade,
                    m_product.length,
                    m_product.thickness,
                    m_product.width,
                    m_product.weight,
                    m_product.maker_id,
                    m_product.price,
                    m_product.open_price_flg,
                    m_product.min_quantity,
                    m_product.stock_unit,
                    m_product.quantity_per_case,
                    m_product.lead_time,
                    m_product.order_lot_quantity,
                    m_product.purchase_makeup_rate,
                    m_product.normal_purchase_price,
                    m_product.unit,
                    m_product.sales_makeup_rate,
                    m_product.normal_sales_price,
                    m_product.normal_gross_profit_rate,
                    m_product.tax_type,
                    m_product.product_image,
                    m_product.start_date,
                    m_product.end_date,
                    m_product.warranty_term,
                    m_product.housing_history_transfer_kbn,
                    m_product.memo,
                    m_product.intangible_flg,
                    m_product.draft_flg,
                    m_product.auto_flg,
                    m_product.del_flg,
                    m_product.created_user,
                    m_product.created_at,
                    m_product.update_user,
                    m_product.update_at,
                    CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\')) as maker_name,
                    CASE 
                          WHEN m_product.open_price_flg = \''. config('const.flg.off') .'\' 
                            THEN \'いいえ\' 
                          WHEN m_product.open_price_flg = \''. config('const.flg.on') .'\' 
                            THEN \'はい\'
                    END AS open_price_txt,
                    CASE 
                          WHEN m_product.intangible_flg = \''. config('const.flg.off') .'\' 
                            THEN \'有形品\' 
                          WHEN m_product.intangible_flg = \''. config('const.flg.on') .'\' 
                            THEN \'無形品\'
                    END AS intangible_flg_txt  
                ')
                ->first()
                ;

        return $data;
    }

    /**
     * IDで取得
     *
     * @param [type] $ids
     * @return void
     */
    public function getByIds($ids){
        $data = $this
            ->select(
                'm_product.*'
            )
            ->where([
                ['m_product.del_flg', config('const.flg.off')],
            ])
            ->whereIn('m_product.id', $ids)
            ->get();

        return $data;
    }

    /**
     * 商品コードで商品を取得する
     * @param $productCodes 商品コードのリスト
     * @param $intangibleFlg 無形品フラグ
     * @param $draftFlg 仮登録フラグ
     * @param $autoFlg　自動登録フラフ
     */
    public function getByProductCodes($productCodes, $intangibleFlg = null, $draftFlg = null, $autoFlg = null){

        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));

        if($intangibleFlg !== null){
            $where[] = array('m_product.intangible_flg', '=', $intangibleFlg);
        }
        if($draftFlg !== null){
            $where[] = array('m_product.draft_flg', '=', $draftFlg);
        }
        if($autoFlg !== null){
            $where[] = array('m_product.auto_flg', '=', $autoFlg);
        }

        $data = $this
            ->select(
                'm_product.id AS product_id',
                'm_product.product_code',
                'm_product.product_name',
                'm_product.maker_id',
                'm_product.model',
                'm_product.unit',
                'm_product.price',
                'm_product.stock_unit',
                'm_product.min_quantity',
                'm_product.order_lot_quantity',
                'm_product.intangible_flg',
                'm_product.draft_flg',
                'm_product.auto_flg',

                'm_product.quantity_per_case',
                'm_product.set_kbn',
                'm_product.class_big_id',
                'm_product.class_middle_id',
                'm_product.class_small_id_1',
                'm_product.tree_species',
                'm_product.grade',
                'm_product.length',
                'm_product.thickness',
                'm_product.width'
            )
            ->where($where)
            ->whereIn('m_product.product_code', $productCodes)
            ->get();

        return $data;
    }

    /**
     * 商品リスト取得
     *
     * @return void
     */
    public function getComboList() {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));

        $data = $this  
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 商品リスト取得 (本登録されている商品のみ)
     *
     * @return void
     */
    public function getFormalComboList() {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));

        $data = $this  
                ->where($where)
                ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftJoin('m_product AS new_product', 'new_product.id', '=', 'm_product.new_product_id')
                ->selectRaw('
                    m_product.id,
                    m_product.product_code,
                    m_product.product_name,
                    m_product.product_short_name,
                    m_product.class_big_id,
                    m_product.class_middle_id,
                    m_product.construction_id_1,
                    m_product.construction_id_2,
                    m_product.construction_id_3,
                    m_product.class_small_id_1,
                    m_product.class_small_id_2,
                    m_product.class_small_id_3,
                    m_product.set_kbn,
                    m_product.model,
                    m_product.tree_species,
                    m_product.grade,
                    m_product.length,
                    m_product.thickness,
                    m_product.width,
                    m_product.weight,
                    m_product.maker_id,
                    m_product.price,
                    m_product.open_price_flg,
                    m_product.min_quantity,
                    m_product.stock_unit,
                    m_product.quantity_per_case,
                    m_product.lead_time,
                    m_product.order_lot_quantity,
                    m_product.purchase_makeup_rate,
                    m_product.normal_purchase_price,
                    m_product.unit,
                    m_product.sales_makeup_rate,
                    m_product.normal_sales_price,
                    m_product.normal_gross_profit_rate,
                    m_product.tax_type,
                    m_product.product_image,
                    m_product.start_date,
                    m_product.end_date,
                    m_product.warranty_term,
                    m_product.housing_history_transfer_kbn,
                    m_product.new_product_id,
                    new_product.product_code AS new_product_code,
                    m_product.memo,
                    m_product.intangible_flg,
                    m_product.draft_flg,
                    m_product.auto_flg,
                    m_product.del_flg,
                    m_product.created_user,
                    m_product.created_at,
                    m_product.update_user,
                    m_product.update_at,
                    CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\')) as maker_name,
                    CASE 
                          WHEN m_product.open_price_flg = \''. config('const.flg.off') .'\' 
                            THEN \'いいえ\' 
                          WHEN m_product.open_price_flg = \''. config('const.flg.on') .'\' 
                            THEN \'はい\'
                    END AS open_price_txt,
                    CASE 
                          WHEN m_product.intangible_flg = \''. config('const.flg.off') .'\' 
                            THEN \'有形品\' 
                          WHEN m_product.intangible_flg = \''. config('const.flg.on') .'\' 
                            THEN \'無形品\'
                    END AS intangible_flg_txt  
                ')
                ->get()
                ;

        return $data;
    }

    /**
     * 商品リスト取得 (仮登録されている商品のみ)
     *
     * @return void
     */
    public function getDraftComboList() {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.on'));

        // 案件Where句
        $matterWhere = [];
        $matterWhere[] = array('m.del_flg', '=', config('const.flg.off'));
        $matterWhere[] = array('m.complete_flg', '=', config('const.flg.off'));
        $matterWhere[] = array('p.draft_flg', '=', config('const.flg.on'));
        $matterWhere[] = array('p.del_flg', '=', config('const.flg.off'));
        // 見積明細Where句
        $quoteDetailWhere = [];
        $quoteDetailWhere[] = array('qd.del_flg', '=', config('const.flg.off'));
        // 見積Where句
        $quoteWhere = [];
        $quoteWhere[] = array('q.del_flg', '=', config('const.flg.off'));

        /** 案件情報取得 */ 
        // 案件取得のサブクエリ
        $matterSubQuery = DB::table('t_matter AS m')
                ->leftJoin('t_quote AS q', function($join) use($quoteWhere){
                    $join
                        ->on('q.matter_no', '=', 'm.matter_no')
                        ->where($quoteWhere)
                        ;
                })
                ->join('t_quote_detail AS qd', function($join) use($quoteDetailWhere){
                    $join
                        ->on('q.quote_no', '=', 'qd.quote_no')
                        ->where($quoteDetailWhere)
                        ;
                })
                ->leftJoin('m_product AS p', function($join) {
                    $join
                        ->on('p.id', '=', 'qd.product_id')
                        ;
                })
                ->where($matterWhere)
                ->select(DB::raw('MAX(m.id) AS matter_id'), 'qd.product_id')
                ->groupBy('qd.product_id')
                ;
        
        // 画面上に表示するのはMAX(案件ID)
        $matterQuery = DB::table('t_matter AS m1')
                ->join(DB::raw('('.$matterSubQuery->toSql().') AS m2'), function($join){
                    $join->on('m2.matter_id', 'm1.id');
                })
                ->mergeBindings($matterSubQuery)
                ->select(
                    'm1.id AS matter_id',
                    'm1.matter_no',
                    'm1.matter_name',
                    'm2.product_id'
                )
                ->orderBy('m2.product_id')
                // ->get()
                ;

        $data = $this  
                ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftjoin(DB::raw('('.$matterQuery->toSql().') AS matter'), function($join){
                    $join
                        ->on('m_product.id', 'matter.product_id');
                })
                ->mergeBindings($matterQuery)
                ->leftJoin('m_staff AS created_staff', 'created_staff.id', '=', 'm_product.created_user')
                ->leftJoin('m_product AS new_product', 'new_product.id', '=', 'm_product.new_product_id')
                ->where($where)
                ->selectRaw('
                    m_product.id,
                    m_product.product_code,
                    m_product.product_name,
                    m_product.product_short_name,
                    m_product.class_big_id,
                    m_product.class_middle_id,
                    m_product.construction_id_1,
                    m_product.construction_id_2,
                    m_product.construction_id_3,
                    m_product.class_small_id_1,
                    m_product.class_small_id_2,
                    m_product.class_small_id_3,
                    m_product.set_kbn,
                    m_product.model,
                    m_product.tree_species,
                    m_product.grade,
                    m_product.length,
                    m_product.thickness,
                    m_product.width,
                    m_product.weight,
                    m_product.maker_id,
                    m_product.price,
                    m_product.open_price_flg,
                    m_product.min_quantity,
                    m_product.stock_unit,
                    m_product.quantity_per_case,
                    m_product.lead_time,
                    m_product.order_lot_quantity,
                    m_product.purchase_makeup_rate,
                    m_product.normal_purchase_price,
                    m_product.unit,
                    m_product.sales_makeup_rate,
                    m_product.normal_sales_price,
                    m_product.normal_gross_profit_rate,
                    m_product.tax_type,
                    m_product.product_image,
                    m_product.start_date,
                    m_product.end_date,
                    m_product.warranty_term,
                    m_product.housing_history_transfer_kbn,
                    m_product.new_product_id,
                    new_product.product_code AS new_product_code,
                    m_product.memo,
                    m_product.intangible_flg,
                    m_product.draft_flg,
                    m_product.auto_flg,
                    m_product.del_flg,
                    m_product.created_user,
                    m_product.created_at,
                    m_product.update_user,
                    m_product.update_at,
                    CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\')) as maker_name,
                    CASE 
                          WHEN m_product.open_price_flg = \''. config('const.flg.off') .'\' 
                            THEN \'いいえ\' 
                          WHEN m_product.open_price_flg = \''. config('const.flg.on') .'\' 
                            THEN \'はい\'
                    END AS open_price_txt,
                    CASE 
                          WHEN m_product.intangible_flg = \''. config('const.flg.off') .'\' 
                            THEN \'有形品\' 
                          WHEN m_product.intangible_flg = \''. config('const.flg.on') .'\' 
                            THEN \'無形品\'
                    END AS intangible_flg_txt,
                    matter.matter_id AS matter_id,
                    matter.matter_no AS matter_no,
                    matter.matter_name AS matter_name,
                    created_staff.staff_name AS created_staff
                ')
                ->orderBy('created_at')
                ->get()
                ;

        return $data;
    }

    /**
     * 見積用商品リスト取得
     *
     * @return void
     */
    public function getQuoteProductList() {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));

        $DELIMITER = ',';

        // 商品ごとに別称を取得
        $productNicknameQuery = DB::table('m_product_nickname') 
                ->select([
                    'product_id',
                    DB::raw("GROUP_CONCAT(m_product_nickname.another_name separator '{$DELIMITER}') AS another_name"),
                ])
                ->groupBy('product_id')
                ->toSql()
                ;

        $data = $this 
                ->leftJoin(DB::raw("({$productNicknameQuery}) as m_product_nickname"), function($join){
                    $join->on('m_product.id', 'm_product_nickname.product_id');
                })
                ->where($where)
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    DB::raw("(
                        CASE 
                            WHEN COALESCE(m_product.product_short_name, '') <> '' 
                            AND COALESCE(m_product_nickname.another_name, '') <> '' 
                                THEN CONCAT( 
                                    COALESCE(m_product.product_short_name, ''), '{$DELIMITER}', COALESCE(m_product_nickname.another_name, '')
                                ) 
                            WHEN COALESCE(m_product.product_short_name, '') <> '' 
                                THEN m_product.product_short_name 
                            WHEN COALESCE(m_product_nickname.another_name, '') <> '' 
                                THEN m_product_nickname.another_name 
                            ELSE '' 
                        END) AS product_short_name
                    "),
                    //'m_product.product_short_name',
                    'm_product.model',
                    'm_product.length',
                    'm_product.thickness',
                    'm_product.width',
                    'm_product.maker_id',
                    'm_product.price',
                    'm_product.min_quantity',
                    'm_product.stock_unit',
                    'm_product.order_lot_quantity',
                    'm_product.unit',
                    'm_product.purchase_makeup_rate',
                    'm_product.normal_purchase_price',
                    // 'm_product.sales_unit',
                    'm_product.sales_makeup_rate',
                    'm_product.normal_sales_price',
                    'm_product.normal_gross_profit_rate',
                    'm_product.tax_type',
                    'm_product.start_date',
                    'm_product.end_date',
                    'm_product.new_product_id',
                    'm_product.intangible_flg',
                    'm_product.quantity_per_case',
                    'm_product.set_kbn',
                    'm_product.class_big_id',
                    'm_product.class_middle_id',
                    'm_product.class_small_id_1',
                    'm_product.tree_species',
                    'm_product.grade',
                    'm_product.length',
                    'm_product.thickness',
                    'm_product.width'
                )
                ->get()
                ;

        return $data;
    }

    /**
     * 仮登録
     *
     * @param array $params
     * @return 商品ID
     */
    public function addDraft($params) {
        try {
            $items = [];
            $items['product_code'] = $params['product_code'];
            $items['product_name'] = $params['product_name'];

            if (!empty($params['product_short_name'])) {
                $items['product_short_name'] = $params['product_short_name'];
            }
            if (!empty($params['class_big_id'])) {
                $items['class_big_id'] = $params['class_big_id'];
            }
            if (!empty($params['class_middle_id'])) {
                $items['class_middle_id'] = $params['class_middle_id'];
            }
            if (!empty($params['construction_id_1'])) {
                $items['construction_id_1'] = $params['construction_id_1'];
            }
            if (!empty($params['construction_id_2'])) {
                $items['construction_id_2'] = $params['construction_id_2'];
            }
            if (!empty($params['construction_id_3'])) {
                $items['construction_id_3'] = $params['construction_id_3'];
            }
            if (!empty($params['class_small_id_1'])) {
                $items['class_small_id_1'] = $params['class_small_id_1'];
            }
            if (!empty($params['class_small_id_2'])) {
                $items['class_small_id_2'] = $params['class_small_id_2'];
            }
            if (!empty($params['class_small_id_3'])) {
                $items['class_small_id_3'] = $params['class_small_id_3'];
            }
            if (!empty($params['set_kbn'])) {
                $items['set_kbn'] = $params['set_kbn'];
            }
            if (!empty($params['model'])) {
                $items['model'] = $params['model'];
            }
            if (!empty($params['tree_species'])) {
                $items['tree_species'] = $params['tree_species'];
            }
            if (!empty($params['grade'])) {
                $items['grade'] = $params['grade'];
            }
            if (!empty($params['length'])) {
                $items['length'] = $params['length'];
            }
            if (!empty($params['thickness'])) {
                $items['thickness'] = $params['thickness'];
            }
            if (!empty($params['width'])) {
                $items['width'] = $params['width'];
            }
            if (!empty($params['weight'])) {
                $items['weight'] = $params['weight'];
            }
            if (!empty($params['maker_id'])) {
                $items['maker_id'] = $params['maker_id'];
            }
            if (!empty($params['price'])) {
                $items['price'] = $params['price'];
            }
            if (!empty($params['open_price_flg'])) {
                $items['open_price_flg'] = $params['open_price_flg'];
            }
            if (!empty($params['min_quantity'])) {
                $items['min_quantity'] = $params['min_quantity'];
            }
            if (!empty($params['stock_unit'])) {
                $items['stock_unit'] = $params['stock_unit'];
            }
            if (!empty($params['quantity_per_case'])) {
                $items['quantity_per_case'] = $params['quantity_per_case'];
            }
            if (!empty($params['lead_time'])) {
                $items['lead_time'] = $params['lead_time'];
            }
            if (!empty($params['order_lot_quantity'])) {
                $items['order_lot_quantity'] = $params['order_lot_quantity'];
            }
            if (!empty($params['purchase_makeup_rate'])) {
                $items['purchase_makeup_rate'] = $params['purchase_makeup_rate'];
            }
            if (!empty($params['normal_purchase_price'])) {
                $items['normal_purchase_price'] = $params['normal_purchase_price'];
            }
            if (!empty($params['unit'])) {
                $items['unit'] = $params['unit'];
            }
            if (!empty($params['sales_makeup_rate'])) {
                $items['sales_makeup_rate'] = $params['sales_makeup_rate'];
            }
            if (!empty($params['normal_sales_price'])) {
                $items['normal_sales_price'] = $params['normal_sales_price'];
            }
            if (!empty($params['normal_gross_profit_rate'])) {
                $items['normal_gross_profit_rate'] = $params['normal_gross_profit_rate'];
            }
            if (!empty($params['tax_type'])) {
                $items['tax_type'] = $params['tax_type'];
            }
            if (!empty($params['product_image'])) {
                $items['product_image'] = $params['product_image'];
            }
            if (!empty($params['start_date'])) {
                $items['start_date'] = $params['start_date'];
            }
            if (!empty($params['end_date'])) {
                $items['end_date'] = $params['end_date'];
            }
            if (!empty($params['warranty_term'])) {
                $items['warranty_term'] = $params['warranty_term'];
            }
            if (!empty($params['housing_history_transfer_kbn'])) {
                $items['housing_history_transfer_kbn'] = $params['housing_history_transfer_kbn'];
            }
            if (!empty($params['new_product_id'])) {
                $items['new_product_id'] = $params['new_product_id'];
            }
            if (!empty($params['memo'])) {
                $items['memo'] = $params['memo'];
            }
            if (!empty($params['auto_flg'])) {
                $items['auto_flg'] = $params['auto_flg'];
            }

            $items['intangible_flg'] = $params['intangible_flg'];
            $items['draft_flg'] = $params['draft_flg'];
            $items['del_flg'] = $params['del_flg'];
            $items['created_user'] = Auth::user()->id;
            $items['created_at'] = Carbon::now();
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 登録
            $rtn = $this->insertGetId($items);

            return $rtn;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * 一覧取得　在庫があれば有効在庫数表示
     *
     * @param  $params
     * @return void
     */
    public function getProductList($params) 
    {
        $where = [];
        $where[] = array('p.del_flg', '=', config('const.flg.off'));
        $where[] = array('p.intangible_flg', '=', config('const.flg.off'));
        $where[] = array('p.draft_flg', '=', config('const.flg.off'));
        $joinWhere = [];
        $joinWhere[] = array('stock.stock_flg', '=', config('const.stockFlg.val.stock'));
        // $joinWhere[] = array('stock.id', 'IN', 'subQuery.join_id');
        // $where[] = array('sn.shelf_kind', '=', config('const.shelfKind.normal'));
        
        if (!empty($params['warehouse_name'])) {
            $joinWhere[] = array('stock.warehouse_id', '=', $params['warehouse_name']);
        }
        if (!empty($params['product_code'])) {
            $where[] = array('p.product_code', 'LIKE', '%'.$params['product_code'].'%');
        }
        if (!empty($params['product_name'])) {
            $where[] = array('p.product_name', 'LIKE', '%'.$params['product_name'].'%');
        }
        if (!empty($params['model'])) {
            $where[] = array('p.model', 'LIKE', '%'.$params['model'].'%');
        }

        $reserveQuery = DB::table('t_reserve AS res')
                        ->whereRaw('
                            res.finish_flg = '. config('const.flg.off'). '
                            AND res.stock_flg = '. config('const.stockFlg.val.stock')
                        )
                        ->selectRaw('
                            res.product_id,
                            res.from_warehouse_id,
                            res.from_shelf_number_id,
                            SUM(res.reserve_quantity - res.issue_quantity) AS reserve_quantity
                        ')
                        ->groupBy('res.product_id', 'res.from_warehouse_id', 'res.from_shelf_number_id')
                        ->toSql()
                        ;

        $normalStockQuery = $this
                ->from('t_productstock_shelf as stock')
                ->leftJoin('m_shelf_number as sn', function ($join) {
                    $join
                        ->on('sn.id', '=', 'stock.shelf_number_id')
                        ;
                })
                ->where([
                    ['sn.shelf_kind', '=', config('const.shelfKind.normal')],
                    ['stock.stock_flg', '=', config('const.stockFlg.val.stock')],
                ])
                ->select([
                    'stock.id as join_id'
                ])
                ->groupBy('stock.id')
                ->get()
                ;
        
        $ids = collect($normalStockQuery)->pluck('join_id')->toArray();
        $stockIds = '('. implode(',', $ids) .')';

        $data = $this
                ->from('m_product AS p')
                ->leftjoin('t_productstock_shelf AS stock', function($join) use($joinWhere, $ids) {
                    $join
                        ->on('p.id', '=', 'stock.product_id')
                        ->where($joinWhere)
                        ->whereIn('stock.id', $ids)
                        ;
                })
                ->leftJoin('m_warehouse AS w',function($join){
                    $join
                        ->on('w.id', '=', 'stock.warehouse_id')
                        ;
                }) 
                ->leftjoin('m_shelf_number AS sn', function($join) {
                    $join
                        ->on('sn.id', '=', 'stock.shelf_number_id')
                        // ->where('sn.shelf_kind', '=', config('const.shelfKind.normal'))
                        ;
                })
                ->leftjoin(DB::raw('(' . $reserveQuery . ') as rs'), function ($join) {
                    $join
                        ->on('stock.warehouse_id', '=', 'rs.from_warehouse_id')
                        ->on('stock.product_id', '=', 'rs.product_id')
                        ->on('stock.shelf_number_id', '=', 'rs.from_shelf_number_id')
                        ;
                })
                // ->whereRaw('
                //     stock.id IN '. $stockIds.'
                //     OR sn.id IS NULL
                // ')
                ->where($where)
                ->selectRaw('
                    p.id AS product_id,
                    stock.warehouse_id,
                    p.product_code,
                    p.product_name,
                    p.model,
                    stock.id as sid,
                    sn.id AS shelf_number_id,
                    sn.shelf_area,
                    sn.shelf_kind,
                    CASE
                        WHEN stock.id IS NULL
                            THEN \'0\'
                        WHEN sn.id IS NULL
                            THEN \'0\'
                        WHEN sn.shelf_kind <>'. config('const.shelfKind.normal').'
                            THEN \'0\'
                        WHEN rs.reserve_quantity > 0
                            THEN stock.quantity - rs.reserve_quantity
                        ELSE stock.quantity
                    END AS stock_quantity
                ')
                ->orderBy('p.product_code')
                ->distinct()
                // ->groupBy('stock.stock.product_id', 'stock.warehouse_id')
                // stock.quantity - rs.reserve_quantity AS stock_quantity
                ->get()
                ;
        
        return $data;
    }


    /**
     * 仕入先IDから取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getProductBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);
        $where[] = array('pro.del_flg', '=', config('const.flg.off'));

        // $data = $this
        //         ->from('t_order_detail AS od')
        //         ->leftJoin('m_product AS p', 'od.product_id', '=', 'p.id')
        //         ->where($where)
        //         ->select([
        //             'p.id as product_id',
        //             'p.product_code',
        //             'p.product_name',
        //         ])
        //         ->distinct()
        //         ->get()
        //         ;

        $arrivalData = $this
                ->from('t_arrival as ar')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'ar.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->where($where)
                ->select([
                    'pro.id as product_id',
                    'pro.product_code',
                    'pro.product_name',
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
                    'pro.id as product_id',
                    'pro.product_code',
                    'pro.product_name',
                ])
                ->distinct()
                ;

        $data = $arrivalData->union($returnData)->get();

        return $data;
    }

    /**
     * 商品情報取得
     * @param $productId 商品ID
     * @return void
     */
    public function getProductInfo($productId) {
        $where = [];
        $where[] = array('m_product.id', '=', $productId);
        //$where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        //$where[] = array('m_product.draft_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    'm_product.model',
                    'm_product.length',
                    'm_product.thickness',
                    'm_product.width',
                    'm_product.maker_id',
                    'm_product.price',
                    'm_product.min_quantity',
                    'm_product.stock_unit',
                    'm_product.order_lot_quantity',
                    'm_product.unit',
                    'm_product.purchase_makeup_rate',
                    'm_product.sales_makeup_rate',
                    'm_product.normal_purchase_price',
                    'm_product.normal_sales_price',
                    'm_product.tax_type',
                    'm_product.start_date',
                    'm_product.end_date',
                    'm_product.new_product_id',
                    'm_product.intangible_flg',
                    'm_product.quantity_per_case',
                    'm_product.set_kbn',
                    'm_product.class_big_id',
                    'm_product.class_middle_id',
                    'm_product.class_small_id_1',
                    'm_product.tree_species',
                    'm_product.grade',
                    'm_product.length',
                    'm_product.thickness',
                    'm_product.width'
                )
                ->get()->first()
                ;

        return $data;
    }

    /**
     * 商品指定する
     * キャンペーン特化の仕入/販売単価
     * 得意先別標準の仕入/販売単価
     * 標準の仕入単価
     * 上記の3区分の情報を返す
     * @param $customerId 得意先ID
     * @param $productId  商品ID
     * @param $costSalesKbn 仕入販売区分 const.costSalesKbn
     * @param $toDay キャンペーン期間の日付 Y/m/d
     * @return 
     */
    public function getPriceDataList($customerId, $productId, $costSalesKbn = null, $toDay = null) {
        $result = [
            'costProductPriceList'  => [],
            'salesProductPriceList' => [],
        ];
        $priceList = $this->getPriceData($customerId, [$productId], $costSalesKbn, $toDay);
        //$product = $this->getById($productId);
        foreach ($priceList as $record) {
            if ($record->cost_sales_kbn === config('const.costSalesKbn.cost')) {
                // 仕入単価
                $result['costProductPriceList'][$record->unit_price_kbn] = $record;
            } else {
                // 販売単価
                $result['salesProductPriceList'][$record->unit_price_kbn] = $record;
            }
        }
        return $result;
    }

    /**
     * 商品コードのオートコンプリートのリストを取得する
     * 商品コードで部分一致
     * @param $productCode
     * @param $draftFlg
     * @param $autoFlg
     * @return 
     */
    public function getProductCodeComboList($productCode, $draftFlg = null, $autoFlg = null) {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));

        if($draftFlg !== null){
            $where[] = array('m_product.draft_flg', '=', $draftFlg);
        }
        if($autoFlg !== null){
            $where[] = array('m_product.auto_flg', '=', $autoFlg);
        }

        $where[] = array('m_product.product_code', 'LIKE', '%' . $productCode . '%');

        $data = $this
                ->where($where)
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    'm_product.maker_id',           // チェック処理用
                    'm_product.min_quantity',       // チェック処理用
                    'm_product.order_lot_quantity', // チェック処理用
                    'm_product.intangible_flg'      // チェック処理用
                )
                ->limit(config('const.productAutoCompleteSetting.max_list_count') + 1)
                ->get()
                ;

        return $data;
    }

    /**
     * 商品名のオートコンプリートのリストを取得する
     * 商品名、略称、ニックネームから探す
     * @param $productName
     * @param $draftFlg
     * @param $autoFlg
     * @return 
     */
    public function getProductNameComboList($productName, $draftFlg = null, $autoFlg = null) {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));

        if($draftFlg !== null){
            $where[] = array('m_product.draft_flg', '=', $draftFlg);
        }
        if($autoFlg !== null){
            $where[] = array('m_product.auto_flg', '=', $autoFlg);
        }
        

        $DELIMITER = ',';

        // 商品ごとに別称を取得
        $productNicknameQuery = DB::table('m_product_nickname') 
                ->select([
                    'product_id',
                    DB::raw("GROUP_CONCAT(m_product_nickname.another_name separator '{$DELIMITER}') AS another_name"),
                ])
                ->groupBy('product_id')
                ->toSql()
                ;

        $data = $this
                ->leftJoin(DB::raw("({$productNicknameQuery}) as m_product_nickname"), function($join){
                    $join->on('m_product.id', 'm_product_nickname.product_id');
                })
                ->where($where)
                ->where(function($query)use($productName){
                    $query->orWhere('m_product.product_name', 'LIKE', '%' . $productName . '%')
                          ->orWhere('m_product.product_short_name', 'LIKE', '%' . $productName . '%')
                          ->orWhere('m_product_nickname.another_name', 'LIKE', '%' . $productName . '%');
                })
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    DB::raw("(
                        CASE 
                            WHEN COALESCE(m_product.product_short_name, '') <> '' 
                            AND COALESCE(m_product_nickname.another_name, '') <> '' 
                                THEN CONCAT( 
                                    COALESCE(m_product.product_short_name, ''), '{$DELIMITER}', COALESCE(m_product_nickname.another_name, '')
                                ) 
                            WHEN COALESCE(m_product.product_short_name, '') <> '' 
                                THEN m_product.product_short_name 
                            WHEN COALESCE(m_product_nickname.another_name, '') <> '' 
                                THEN m_product_nickname.another_name 
                            ELSE '' 
                        END) AS product_short_name
                    "),
                    'm_product.maker_id',           // チェック処理用
                    'm_product.min_quantity',       // チェック処理用
                    'm_product.order_lot_quantity', // チェック処理用
                    'm_product.intangible_flg'      // チェック処理用
                )
                ->limit(config('const.productAutoCompleteSetting.max_list_count') + 1)
                ->get()
                ;

        return $data;
    }

    /**
     * 商品指定する
     *  キャンペーン特価の仕入/販売単価
     *  得意先別標準の仕入/販売単価
     *  標準の仕入単価（木材立米単価・商品仕入単価・商品マスタ標準）
     * 上記の3区分の価格情報を返す
     * @param $customerId 得意先ID
     * @param $productIds  商品IDの配列
     * @param $costSalesKbn 仕入販売区分 const.costSalesKbn
     * @param $toDay キャンペーン期間の日付 Y/m/d
     * @return 
     */
    public function getPriceData($customerId, $productIds, $costSalesKbn = null, $toDay = null) {
        // 開始日
        if($toDay === null){
            $toDay = Carbon::today()->format('Y/m/d');
        }
        // キャンペーン単価
        $campaignPriceWhere = [];
        $campaignPriceWhere[] = array('start_date', '<=', $toDay);
        $campaignPriceWhere[] = array('end_date', '>=', $toDay);
        //$campaignPriceWhere[] = array('product_id', '=', $productId);
        
        if (!is_null($costSalesKbn)) {
            $campaignPriceWhere[] = array('cost_sales_kbn', '=', $costSalesKbn);
        }
                
        $query1 = DB::table('m_product_campaign_price')
                ->where($campaignPriceWhere)
                ->whereIN('product_id', $productIds)
                ->select([
                    'product_id',
                    'cost_sales_kbn',
                    'price',
                ])
                ->selectRaw(
                    config('const.unitPriceKbn.campaign').' AS unit_price_kbn'
                )
                ;


        // 得意先別商品単価
        $customerPriceWhere = [];
        $customerPriceWhere[] = array('customer_id', '=', $customerId);
        //$customerPriceWhere[] = array('product_id', '=', $productId);
        if (!is_null($costSalesKbn)) {
            $customerPriceWhere[] = array('cost_sales_kbn', '=', $costSalesKbn);
        }

        $productCustomerPriceQuery = DB::table('t_product_customer_price') 
                ->select([
                    DB::raw('MAX(id) as id')
                ])
                ->where($customerPriceWhere)
                ->whereIN('product_id', $productIds)
                ->groupBy('customer_id', 'product_id', 'cost_sales_kbn')
                ;

        $query2 = DB::table('t_product_customer_price as main1') 
                ->join(DB::raw('('.$productCustomerPriceQuery->toSql().') as sub1'), function($join){
                    $join->on('main1.id', 'sub1.id');
                })
                ->mergeBindings($productCustomerPriceQuery)
                ->where($customerPriceWhere)
                ->select([
                    'main1.product_id',
                    'main1.cost_sales_kbn',
                    'main1.price',
                ])
                ->selectRaw(
                    config('const.unitPriceKbn.cutomer_normal').' AS unit_price_kbn'
                )
                ;

        // 木材立米
        $woodUnitpriceBaseQuery = DB::table('t_wood_unitprice')
                ->select([
                    'product_id',
                    DB::raw('MAX(price_date) AS price_date'),
                ])
                ->where([
                    ['del_flg', '=', config('const.flg.off')],
                ])
                ->whereRaw('price_date <= CURRENT_DATE()')
                ->whereIN('product_id', $productIds)
                ->groupBy('product_id')
                ;

        $woodUnitpriceQuery = DB::table('t_wood_unitprice AS wu1')
                ->join(DB::raw('('.$woodUnitpriceBaseQuery->toSql().') as wu2'), function($join){
                    $join->on('wu1.product_id', 'wu2.product_id')
                    ->on('wu1.price_date', 'wu2.price_date');
                })
                ->mergeBindings($woodUnitpriceBaseQuery)
                ->select([
                    'wu1.product_id',
                    'wu1.purchase_unit_price',
                    'wu1.sales_unit_price'
                ]);
        
        // 商品仕入単価
        $productCustomerPriceBaseQuery = DB::table('t_product_cost_price') 
                ->select([
                    DB::raw('MAX(id) as id')
                ])
                ->whereIN('product_id', $productIds)
                ->groupBy('product_id')
                ;

        $productCustomerPriceQuery = DB::table('t_product_cost_price as pcp1') 
                ->join(DB::raw('('.$productCustomerPriceBaseQuery->toSql().') as pcp2'), function($join){
                    $join->on('pcp1.id', 'pcp2.id');
                })
                ->mergeBindings($productCustomerPriceBaseQuery)
                ->select([
                    'pcp1.product_id',
                    'pcp1.price',
                ]);


        // 木材と商品仕入単価
        $costQuery = DB::table('m_product')
                    ->leftJoin('m_class_big', 'm_product.class_big_id', 'm_class_big.id')
                    // 木材立米
                    ->leftjoin(DB::raw('('.$woodUnitpriceQuery->toSql().') AS wood_unitprice'), function($join){
                        $join->on('m_product.id', 'wood_unitprice.product_id');
                    })
                    ->mergeBindings($woodUnitpriceQuery)
                    // 商品仕入単価
                    ->leftjoin(DB::raw('('.$productCustomerPriceQuery->toSql().') AS product_cost_price'), function($join){
                        $join->on('m_product.id', 'product_cost_price.product_id');
                    })
                    ->mergeBindings($productCustomerPriceQuery)
                    ->whereRaw('
                      (m_class_big.format_kbn='. config('const.classBigFormatKbn.val.wood'). ' AND wood_unitprice.product_id IS NOT NULL)
                      OR (product_cost_price.product_id IS NOT NULL)
                    ')
                    ->select([
                        'm_product.id AS product_id',
                        DB::raw(config('const.costSalesKbn.cost').' AS cost_sales_kbn'),
                        DB::raw('
                            CASE
                              WHEN m_class_big.format_kbn='. config('const.classBigFormatKbn.val.wood'). ' AND wood_unitprice.product_id IS NOT NULL
                                THEN ROUND(wood_unitprice.purchase_unit_price, 0)
                              WHEN product_cost_price.product_id IS NOT NULL
                                THEN product_cost_price.price
                            END AS price
                        '),
                        DB::raw(config('const.unitPriceKbn.normal').' AS unit_price_kbn'),
                    ])
                    ;

        // 標準仕入単価
        $query3 = DB::table('m_product')
                    // 木材立米
                    ->leftjoin(DB::raw('('.$costQuery->toSql().') AS cost_price'), function($join){
                        $join->on('m_product.id', 'cost_price.product_id');
                    })
                    ->mergeBindings($costQuery)
                    ->whereIN('id', $productIds)
                    ->select([
                        'm_product.id AS product_id',
                        DB::raw(config('const.costSalesKbn.cost').' AS cost_sales_kbn'),
                        DB::raw('
                            CASE
                              WHEN cost_price.product_id IS NOT NULL
                                THEN cost_price.price
                              ELSE m_product.normal_purchase_price
                            END AS price
                        '),
                        DB::raw(config('const.unitPriceKbn.normal').' AS unit_price_kbn'),
                    ])
                    ;
                    
        // 木材立米単価の販売単価
        $salesQuery = DB::table('m_product')
            ->leftJoin('m_class_big', 'm_product.class_big_id', 'm_class_big.id')
            // 木材立米
            ->leftjoin(DB::raw('('.$woodUnitpriceQuery->toSql().') AS wood_unitprice'), function($join){
                $join->on('m_product.id', 'wood_unitprice.product_id');
            })
            ->mergeBindings($woodUnitpriceQuery)
            ->whereRaw('m_class_big.format_kbn='. config('const.classBigFormatKbn.val.wood'). ' AND wood_unitprice.product_id IS NOT NULL')
            ->select([
                'm_product.id AS product_id',
                DB::raw(config('const.costSalesKbn.sales').' AS cost_sales_kbn'),
                DB::raw('
                    CASE
                    WHEN m_class_big.format_kbn='. config('const.classBigFormatKbn.val.wood'). ' AND wood_unitprice.product_id IS NOT NULL
                        THEN ROUND(wood_unitprice.sales_unit_price, 0)
                    END AS price
                '),
                DB::raw(config('const.unitPriceKbn.normal').' AS unit_price_kbn'),
            ])
            ;

        // 標準の販売単価
        $query4 = DB::table('m_product')
                    // 木材立米
                    ->leftjoin(DB::raw('('.$salesQuery->toSql().') AS sales_price'), function($join){
                        $join->on('m_product.id', 'sales_price.product_id');
                    })
                    ->mergeBindings($salesQuery)
                    ->whereIN('id', $productIds)
                    ->select([
                        'm_product.id AS product_id',
                        DB::raw(config('const.costSalesKbn.sales').' AS cost_sales_kbn'),
                        DB::raw('
                            CASE
                              WHEN sales_price.product_id IS NOT NULL
                                THEN sales_price.price
                              ELSE m_product.normal_sales_price
                            END AS price
                        '),
                        DB::raw(config('const.unitPriceKbn.normal').' AS unit_price_kbn'),
                    ])
                    ;

        // UNIONで取得
        $mainQuery = $query1->union($query2)
            ->when($costSalesKbn !== config('const.costSalesKbn.sales'), function($query) use($query3) {
                // 標準の仕入単価
                return $query->union($query3);
            })
            ->when($costSalesKbn !== config('const.costSalesKbn.cost'), function($query) use($query4) {
                // 標準の販売単価
                return $query->union($query4);
            });

        $data = $mainQuery
                ->orderBy('unit_price_kbn', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * IDで取得
     *
     * @param $productName
     * @return 
     */
    public function getByNameAndMaker($params){
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.id', '=', $params['product_id']);
        if(!empty($params['maker_id'])){
            $where[] = array('m_product.maker_id', '=', $params['maker_id']);
        }
        // if(!empty($params['product_name'])){
        //     $where[] = array('m_product.product_name', '=', $params['product_name']);
        // }

        $data = $this
            ->select(
                'm_product.*'
            )
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * IDで取得
     *
     * @param $productName
     * @return 
     */
    public function getByCodeAndMaker($params){
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        if(!empty($params['maker_id'])){
            $where[] = array('m_product.maker_id', '=', $params['maker_id']);
        }
        if($params['isProductName']){
            $where[] = array(DB::raw('trim(m_product.product_name)'), '=', $params['product_name']);
        }else{
            $where[] = array(DB::raw('trim(m_product.product_code)'), '=', $params['product_code']);
        }

        $data = $this
            ->select(
                'id as product_id',
                'product_code',
                'product_name'
            )
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * メーカーID一致
     *
     * @param  $productCode
     * @param  $makerId
     * @param  $newProductId
     * @return void
     */
    public function getProductByMakerId($makerId) 
    {
        $where = [];
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.maker_id', '=', $makerId);

        $data = $this          
                ->where($where)
                ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftJoin('m_product AS new_product', 'new_product.id', '=', 'm_product.new_product_id')
                ->selectRaw('
                    m_product.id,
                    m_product.product_code,
                    m_product.product_name,
                    m_product.product_short_name,
                    m_product.class_big_id,
                    m_product.class_middle_id,
                    m_product.construction_id_1,
                    m_product.construction_id_2,
                    m_product.construction_id_3,
                    m_product.class_small_id_1,
                    m_product.class_small_id_2,
                    m_product.class_small_id_3,
                    m_product.set_kbn,
                    m_product.model,
                    m_product.tree_species,
                    m_product.grade,
                    m_product.length,
                    m_product.thickness,
                    m_product.width,
                    m_product.weight,
                    m_product.maker_id,
                    m_product.price,
                    m_product.open_price_flg,
                    m_product.min_quantity,
                    m_product.stock_unit,
                    m_product.quantity_per_case,
                    m_product.lead_time,
                    m_product.order_lot_quantity,
                    m_product.purchase_makeup_rate,
                    m_product.normal_purchase_price,
                    m_product.unit,
                    m_product.sales_makeup_rate,
                    m_product.normal_sales_price,
                    m_product.normal_gross_profit_rate,
                    m_product.tax_type,
                    m_product.product_image,
                    m_product.start_date,
                    m_product.end_date,
                    m_product.warranty_term,
                    m_product.housing_history_transfer_kbn,
                    m_product.new_product_id,
                    new_product.product_code AS new_product_code,
                    m_product.memo,
                    m_product.intangible_flg,
                    m_product.draft_flg,
                    m_product.auto_flg,
                    m_product.del_flg,
                    m_product.created_user,
                    m_product.created_at,
                    m_product.update_user,
                    m_product.update_at,
                    CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\')) as maker_name,
                    CASE 
                          WHEN m_product.open_price_flg = \''. config('const.flg.off') .'\' 
                            THEN \'いいえ\' 
                          WHEN m_product.open_price_flg = \''. config('const.flg.on') .'\' 
                            THEN \'はい\'
                    END AS open_price_txt,
                    CASE 
                          WHEN m_product.intangible_flg = \''. config('const.flg.off') .'\' 
                            THEN \'有形品\' 
                          WHEN m_product.intangible_flg = \''. config('const.flg.on') .'\' 
                            THEN \'無形品\'
                    END AS intangible_flg_txt  
                ')
                ->get()
                ;

        return $data;
    }

    /**
     * 商品コードまたは後継商品と、メーカーIDを元に本登録商品を取得
     * ※商品コードは大文字・小文字を区別しない
     *
     * @param string $productCode 商品コード
     * @param array $makerIdList メーカーIDの配列
     * @return 
     */
    public function getSimilarProductData($productCode, $makerIdList) 
    {
        $query1 = $this
                ->from('m_product AS p1')
                ->whereRaw('convert(p1.product_code using utf8) COLLATE utf8_unicode_ci = ?', [$productCode])
                ->where([
                    ['p1.del_flg', '=', config('const.flg.off')],
                    ['p1.draft_flg', '=', config('const.flg.off')]
                ])
                ->select(['p1.id'])
                ;
        if (count($makerIdList) > 0) {
            $query1->whereIn('p1.maker_id', $makerIdList);
        }
        
        $query2 = $this
                ->from('m_product AS p2')
                ->whereIn('p2.id', function ($subQuery) use ($productCode, $makerIdList) {
                    $subQuery
                        ->select('sub.id')
                        ->from('m_product AS sub')
                        ->whereRaw('convert(sub.product_code using utf8) COLLATE utf8_unicode_ci = ?', [$productCode])
                        ->where([
                            ['sub.del_flg', '=', config('const.flg.off')],
                            ['sub.draft_flg', '=', config('const.flg.off')],
                        ]);
                    if (count($makerIdList) > 0) {
                        $subQuery->whereIn('sub.maker_id', $makerIdList);
                    }
                    return $subQuery;
                })
                ->select(['p2.id'])
                ;
        
        $data = $query1->union($query2)
                ->get()
                ;

        return $data;
    }

    /**
     * 本登録商品の重複チェック
     *
     * @param [type] $productCode 商品番号
     * @param [type] $makerId   メーカーID
     * @param [type] $id          商品ID　重複対象から除外
     * @return boolean True: 存在する False: 存在しない
     */
    public function isExistRegistered($productCode, $makerId, $id = null) 
    {
        $where = [];
        if (!empty($id)) {
            $where[] = array('id', '<>', $id);
        }
        $where[] = array('product_code', '=', $productCode);
        $where[] = array('maker_id', '=', $makerId);
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('draft_flg', '=', config('const.flg.off'));
        $where[] = array('auto_flg', '=', config('const.flg.off'));

        // 存在チェック
        $data = $this
                ->where($where)
                ->count()
                ;

        return $data > 0;
    }


     /**
     * IDで取得 商品マスタチェック表示用
     * @param int $id 商品ID
     * @return type 検索結果データ（1件）
     */
    public function getProductCheckDataById($id) {
        // 案件Where句
        $matterWhere = [];
        $matterWhere[] = array('m.del_flg', '=', config('const.flg.off'));
        $matterWhere[] = array('m.complete_flg', '=', config('const.flg.off'));
        // 見積明細Where句
        $quoteDetailWhere = [];
        $quoteDetailWhere[] = array('qd.product_id', '=', $id);
        $quoteDetailWhere[] = array('qd.del_flg', '=', config('const.flg.off'));
        // 見積Where句
        $quoteWhere = [];
        $quoteWhere[] = array('q.del_flg', '=', config('const.flg.off'));

        /** 案件情報取得 */ 
        // 案件取得のサブクエリ
        $matterSubQuery = DB::table('t_matter AS m')
                ->leftJoin('t_quote AS q', function($join) use($quoteWhere){
                    $join
                        ->on('q.matter_no', '=', 'm.matter_no')
                        ->where($quoteWhere)
                        ;
                })
                ->join('t_quote_detail AS qd', function($join) use($quoteDetailWhere){
                    $join
                        ->on('q.quote_no', '=', 'qd.quote_no')
                        ->where($quoteDetailWhere)
                        ;
                })
                ->leftJoin('m_product AS p', function($join) {
                    $join
                        ->on('p.id', '=', 'qd.product_id')
                        ;
                })
                ->where($matterWhere)
                ->select(DB::raw('MAX(m.id) AS matter_id'), 'qd.product_id')
                ->groupBy('qd.product_id')
                ;
        
        // 画面上に表示するのはMAX(案件ID)
        $matterQuery = DB::table('t_matter AS m1')
                ->join(DB::raw('('.$matterSubQuery->toSql().') AS m2'), function($join){
                    $join->on('m2.matter_id', 'm1.id');
                })
                ->mergeBindings($matterSubQuery)
                ->select(
                    'm1.id AS matter_id',
                    'm1.matter_no',
                    'm1.matter_name',
                    'm2.product_id'
                )
                ->orderBy('m2.product_id')
                // ->get()
                ;

        // 商品情報取得
        $data = $this
                ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
                ->leftJoin('m_general as maker_gene', function($join) {
                    $join->on('maker_gene.value_code', '=', 'maker.juridical_code')
                        ->where('maker_gene.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftjoin(DB::raw('('.$matterQuery->toSql().') AS matter'), function($join){
                    $join
                        ->on('m_product.id', 'matter.product_id');
                })
                ->mergeBindings($matterQuery)
                ->leftJoin('m_product AS new_product', 'new_product.id', '=', 'm_product.new_product_id')
                ->leftJoin('m_staff AS created_staff', 'created_staff.id', '=', 'm_product.created_user')
                ->where(['m_product.id' => $id])
                ->selectRaw('
                    m_product.id,
                    m_product.product_code,
                    m_product.product_name,
                    m_product.product_short_name,
                    m_product.class_big_id,
                    m_product.class_middle_id,
                    m_product.construction_id_1,
                    m_product.construction_id_2,
                    m_product.construction_id_3,
                    m_product.class_small_id_1,
                    m_product.class_small_id_2,
                    m_product.class_small_id_3,
                    m_product.set_kbn,
                    m_product.model,
                    m_product.tree_species,
                    m_product.grade,
                    m_product.length,
                    m_product.thickness,
                    m_product.width,
                    m_product.weight,
                    m_product.maker_id,
                    m_product.price,
                    m_product.open_price_flg,
                    m_product.min_quantity,
                    m_product.stock_unit,
                    m_product.quantity_per_case,
                    m_product.lead_time,
                    m_product.order_lot_quantity,
                    m_product.purchase_makeup_rate,
                    m_product.normal_purchase_price,
                    m_product.unit,
                    m_product.sales_makeup_rate,
                    m_product.normal_sales_price,
                    m_product.normal_gross_profit_rate,
                    m_product.tax_type,
                    m_product.product_image,
                    m_product.start_date,
                    m_product.end_date,
                    m_product.warranty_term,
                    m_product.housing_history_transfer_kbn,
                    m_product.new_product_id,
                    new_product.product_code AS new_product_code,
                    m_product.memo,
                    m_product.intangible_flg,
                    m_product.draft_flg,
                    m_product.auto_flg,
                    m_product.del_flg,
                    m_product.created_user,
                    m_product.created_at,
                    m_product.update_user,
                    m_product.update_at,
                    CONCAT(COALESCE(maker_gene.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_gene.value_text_3, \'\')) as maker_name,
                    CASE 
                          WHEN m_product.open_price_flg = \''. config('const.flg.off') .'\' 
                            THEN \'いいえ\' 
                          WHEN m_product.open_price_flg = \''. config('const.flg.on') .'\' 
                            THEN \'はい\'
                    END AS open_price_txt,
                    CASE 
                          WHEN m_product.intangible_flg = \''. config('const.flg.off') .'\' 
                            THEN \'有形品\' 
                          WHEN m_product.intangible_flg = \''. config('const.flg.on') .'\' 
                            THEN \'無形品\'
                    END AS intangible_flg_txt,
                    matter.matter_id AS matter_id,
                    matter.matter_no AS matter_no,
                    matter.matter_name AS matter_name,
                    created_staff.staff_name AS created_staff
                ')
                // ->distinct()
                ->first()
                ;

        return $data;
    }

    /**
     * 商品番号とメーカーIDで本登録の商品データを取得する
     * 1件しか取得できない想定だが、複数取得した場合は最古の1件を返す
     *
     * @param string $productCode 商品番号
     * @param int $makerId メーカーID
     * @return void
     */
    public function getProductByCodeAndMaker($productCode, $makerId) {
        // WHERE句
        $where = [];
        $where[] = array('m_product.product_code', '=', $productCode);
        $where[] = array('m_product.maker_id', '=', $makerId);
        $where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.auto_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->first()
                ;

        return $data;
    }

    /**
     * 更新
     *
     * @param int $productId 商品マスタID
     * @param array $params 更新内容
     * @return void
     */
    public function updateFromOrder($productId, $params) {

        // 更新内容
        $items = [];

        // 商品名
        if (isset($params['product_name'])) {
            $items['product_name'] = $params['product_name'];
        }

        // 分類系
        if (isset($params['class_big_id'])) {
            $items['class_big_id'] = $params['class_big_id'];
        }
        if (isset($params['class_middle_id'])) {
            $items['class_middle_id'] = $params['class_middle_id'];
        }
        if (isset($params['construction_id_1'])) {
            $items['construction_id_1'] = $params['construction_id_1'];
        }
        if (isset($params['construction_id_2'])) {
            $items['construction_id_2'] = $params['construction_id_2'];
        }
        if (isset($params['construction_id_3'])) {
            $items['construction_id_3'] = $params['construction_id_3'];
        }
        // if (isset($params['class_small_id_1'])) {
        //     $items['class_small_id_1'] = $params['class_small_id_1'];
        // }
        // if (isset($params['class_small_id_2'])) {
        //     $items['class_small_id_2'] = $params['class_small_id_2'];
        // }
        // if (isset($params['class_small_id_3'])) {
        //     $items['class_small_id_3'] = $params['class_small_id_3'];
        // }

        // 価格系
        if (isset($params['price'])) {
            $items['price'] = $params['price'];
        }
        if (isset($params['open_price_flg'])) {
            $items['open_price_flg'] = $params['open_price_flg'];
        }
        if (isset($params['purchase_makeup_rate'])) {
            $items['purchase_makeup_rate'] = $params['purchase_makeup_rate'];
        }
        if (isset($params['normal_purchase_price'])) {
            $items['normal_purchase_price'] = $params['normal_purchase_price'];
        }
        if (isset($params['sales_makeup_rate'])) {
            $items['sales_makeup_rate'] = $params['sales_makeup_rate'];
        }
        if (isset($params['normal_sales_price'])) {
            $items['normal_sales_price'] = $params['normal_sales_price'];
        }
        // TODO: 粗利率（仕入価格・販売価格のどちらか一方のみ更新する場合の計算がここではできない）

        // 単位系
        if (isset($params['stock_unit'])) {
            $items['stock_unit'] = $params['stock_unit'];
        }
        if (isset($params['unit'])) {
            $items['unit'] = $params['unit'];
        }

        // 仮登録フラグ
        if (isset($params['draft_flg'])) {
            $items['draft_flg'] = $params['draft_flg'];
        }
        // 自動登録フラグ（1回限り登録）
        if (isset($params['auto_flg'])) {
            $items['auto_flg'] = $params['auto_flg'];
        }

        $items['update_user'] = Auth::user()->id;
        $items['update_at'] = Carbon::now();

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();
        $LogUtil->putById($this->table, $productId, config('const.logKbn.update'));
        
        // 更新
        $this
            ->where('id', $productId)
            ->update($items)
        ;

    }

    /**
     * 商品IDからメーカーを取得
     *
     * @param $productId 商品ID
     * @return 
     */
    public function getProductMaker($productId){
        $where = [];
        $where[] = array('m_product.id', '=', $productId);

        $data = $this
            ->leftJoin('m_supplier AS maker', 'maker.id', '=', 'm_product.maker_id')
            ->where($where)
            ->select(
                'm_product.id as product_id',
                'maker.id AS maker_id',
                'maker.supplier_name AS maker_name'
            )
            ->first();

        return $data;
    }

}
