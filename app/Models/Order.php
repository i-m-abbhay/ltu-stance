<?php

namespace App\Models;

use App\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;
use Validator;
use App\Libs\Common;

/**
 * 発注データ
 */
class Order extends Model
{
    // テーブル名
    protected $table = 't_order';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    public function order(){
        return $this->belongsTo('App\Models\Order');
    }
    
    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句作成
        $where = [];
        $where[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.status', '=', 4);
        $where[] = array('o.delivery_address_kbn', '=', 2);
        $where[] = array('od.product_id', '<>', 0);
        $where[] = array('p.intangible_flg ', '<>', 1);
        if (!empty($params['customer_id'])) {
            $where[] = array('c.id', '=', $params['customer_id']);
        }
        if (!empty($params['department_name'])) {
            $where[] = array('d.department_name', '=', $params['department_name']);
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('s.staff_name', '=', $params['staff_name']);
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('m.matter_no', '=', $params['matter_no']);
        }
        if (!empty($params['matter_name'])) {
            $where[] = array('m.matter_name', '=', $params['matter_name']);
        }
        if (!empty($params['product_code'])) {
            $where[] = array('od.product_code', 'LIKE', '%'.$params['product_code'].'%');
        }
        if (!empty($params['maker_name'])) {
            $where[] = array('maker.supplier_name', '=', $params['maker_name']);
        }
        if (!empty($params['supplier_name'])) {
            $where[] = array('su.supplier_name', '=', $params['supplier_name']);
        }
        if (!empty($params['order_no'])) {
            $where[] = array('o.order_no', '=', $params['order_no']);
        }
        if (!empty($params['warehouse_name'])) {
            $where[] = array('w.warehouse_name', '=', $params['warehouse_name']);
        }
        
        if (!empty($params['from_date']) && empty($params['to_date'])) {
            $whereDate = '(od.arrival_plan_date >= \''.$params['from_date'].'\' or ifnull(od.arrival_plan_date,\'\') = \'\')';
        }
        if (!empty($params['to_date'])&& empty($params['from_date'])) {
            $whereDate = '(od.arrival_plan_date <= \''.$params['to_date'].'\' or ifnull(od.arrival_plan_date,\'\') = \'\')';
        }
        if (!empty($params['to_date'])&& !empty($params['from_date'])) {
            $whereDate ='od.arrival_plan_date >= \''.$params['from_date'] . '\' and od.arrival_plan_date <= \''.$params['to_date'].'\'';
        }
        if (empty($params['to_date'])&& empty($params['from_date'])) {
            $whereDate ='0=0';
        }
        
        // データ取得
        $data = DB::table('t_order_detail as od')
            ->join('t_order as o', 'od.order_no', '=', 'o.order_no')
            ->leftJoin(DB::raw('(select order_no, (count(product_id) - 1) as product_count from t_order_detail group by order_no) as odt'), 'odt.order_no', '=', 'o.order_no')
            ->leftjoin('t_matter as m', 'o.matter_no', '=', 'm.matter_no')
            ->leftjoin('m_staff as s', 'm.staff_id', '=', 's.id')
            ->leftjoin('m_department as d', 'm.department_id', '=', 'd.id')
            ->leftJoin('m_customer as c', 'c.id', '=', 'm.customer_id')
            ->leftJoin('m_general as c_general', function($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                     ->where('c_general.category_code', '=', config('const.general.juridical'))
                     ;
            })
            ->leftjoin('m_product as p', 'p.id', '=', 'od.product_id')
            ->leftjoin('m_warehouse as w', 'o.delivery_address_id', '=', 'w.id')
            ->leftjoin('m_address as a', 'o.delivery_address_id', '=', 'a.id')

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

            ->selectRaw('o.id,
                         o.order_no,
                         min(od.arrival_plan_date) as arrival_plan_date,
                         min(od.product_id) as product_id,
                         min(od.product_code) as product_code,
                         min(od.product_name) as product_name,
                         min(odt.product_count) as product_count,
                         min(m.id) as matter_id,
                         min(m.matter_no) as matter_no,
                         min(m.matter_name) as matter_name,
                         CONCAT(COALESCE(min(c_general.value_text_2), \'\'), COALESCE(min(c.customer_name), \'\'), COALESCE(min(c_general.value_text_3), \'\')) as customer_name,
                         (CASE min(o.delivery_address_kbn) WHEN 1 THEN CONCAT(min(a.address1), min(a.address2)) ELSE min(w.warehouse_name) END) AS warehouse_name,
                         CONCAT(COALESCE(min(g.value_text_2), \'\'), COALESCE(min(su.supplier_name), \'\'), COALESCE(min(g.value_text_3), \'\')) AS supplier_name,
                         CONCAT(COALESCE(min(maker_general.value_text_2), \'\'), COALESCE(min(maker.supplier_name), \'\'), COALESCE(min(maker_general.value_text_3), \'\')) as maker_name,
                         (select id from m_shelf_number where warehouse_id = w.id and del_flg = 0 and shelf_kind = 1) as temp_shelf_number_id,
                         0 as check_box
                         ')
            ->where($where)
            ->whereRaw($whereDate)
            ->groupBy('o.id','o.order_no')
            ->get()    
            ;
        return $data;
    }

    /**
     * IDで取得
     * @param int $id 発注ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->select(
                    't_order.*'
                )
                ->where(['t_order.id' => $id])
                ->first()
                ;

        return $data;
    }

    /**
     * 一時保存データを見積番号で取得
     * @param int $quoteNo 見積番号
     * @return type 検索結果データ（1件）
     */
    public function getTmpSaveDataByQuoteNo($quoteNo) {
        $data = $this
                ->select(
                    't_order.*'
                )
                ->where(['t_order.quote_no' => $quoteNo])
                ->where(['t_order.status' => config('const.orderStatus.val.not_ordering')])
                ->first()
                ;

        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params) {
        $result = false;
        try {

            $this->validation($params);

            $result = $this->insertGetId([
                    'order_no'  => $params['order_no'],
                    'quote_no'  => $params['quote_no'],
                    'matter_no' => $params['matter_no'],
                    'maker_id'  => $params['maker_id'],
                    'supplier_id'   => $params['supplier_id'],
                    'person_id'     => $params['person_id'],
                    'delivery_address_kbn'  => $params['delivery_address_kbn'],
                    'delivery_address_id'   => $params['delivery_address_id'],
                    'status'    => $params['status'],
                    'order_apply_datetime'  => $params['order_apply_datetime'],
                    'order_approval_datetime'   => $params['order_approval_datetime'],
                    'order_datetime'    => $params['order_datetime'],
                    'order_staff_id'    => $params['order_staff_id'],
                    'cost_total'    => $params['cost_total'],
                    'sales_total'   => $params['sales_total'],
                    'profit_total'  => $params['profit_total'],
                    'account_customer_name' => $params['account_customer_name'],
                    'account_owner_name'    => $params['account_owner_name'],
                    'map_print_flg' => $params['map_print_flg'],
                    'desired_delivery_date' => $params['desired_delivery_date'],
                    'sales_support_comment' => $params['sales_support_comment'],
                    'supplier_comment'      => $params['supplier_comment'],
                    'arrival_complete_flg'  => $params['arrival_complete_flg'],
                    'own_stock_flg'         => $params['own_stock_flg'],
                    'del_flg'       => config('const.flg.off'),
                    'created_user'  => Auth::user()->id,
                    'created_at'    => Carbon::now(),
                    'update_user'   => Auth::user()->id,
                    'update_at'     => Carbon::now(),
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

            $this->validation($params);

            if(!isset($params['update_user']) || empty($params['update_user'])){
                $params['update_user'] = Auth::user()->id;
            }
            if(!isset($params['update_at']) || empty($params['update_at'])){
                $params['update_at'] = Carbon::now();
            }

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
     * 入荷完了
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateComplete($params) {
        $result = true;
        try {
            // Where句作成
            $where = [];
            $where[] = array('od.del_flg', '=', config('const.flg.off'));
            
            // データ取得
            $data = DB::table('t_order_detail as od')
                ->join('t_order as o', 'od.order_id', '=', 'o.id')
                ->select('o.id',
                            'o.arrival_complete_flg',
                            'od.stock_quantity',
                            'od.arrival_quantity'
                            )
                ->where($where)
                ->where('od.order_no', $params['order_no'])
                ->get()    
                ;
            
            if (count($data) === 0) {
                // 取得データ0件はありえない
                $result = false;
            } else {
                // 発注データの情報取得 
                $orderId = $data[0]->id;
                $arrivalCompleteFlg = $data[0]->arrival_complete_flg;

                // 入荷完了フラグが立っていない場合のみ入荷済み数をチェックする
                if ($arrivalCompleteFlg !== config('const.flg.on')) {
                    // 入荷済数チェック
                    $cnt = 0;
                    foreach($data as $value) {
                        // 入荷済
                        // if ($value->arrival_complete_flg == 1) {
                        //     return $result;
                        // }
                        if ($value->stock_quantity <= $value->arrival_quantity) {
                            $cnt++;
                        }
                    }
                    if ($cnt === count($data)) {
                        // 入荷完了フラグ更新
                        if(isset(Auth::user()->id)){
                            $user_id = Auth::user()->id;
                        }else{
                            $user_id = 0;
                        }

                        // ログテーブルへの書き込み
                        $LogUtil = new LogUtil();
                        $LogUtil->putById($this->table, $orderId, config('const.logKbn.update'));
                        
                        $updateCnt = $this
                                // ->where('order_no', $params['order_no'])
                                ->where('id', $orderId)
                                ->update([
                                    'arrival_complete_flg' => config('const.flg.on'),
                                    'update_user' => $user_id,
                                    'update_at' => Carbon::now(),
                                ]);
                        $result = ($updateCnt > 0);
                    }
                }
            }
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * 入荷完了フラグを判定して更新 (完了にする／未完了に戻す)
     *
     * @param int $orderId 発注ID
     * @return void
     */
    public function judgeArrivalCompleteFlg($orderId) {
        try {
            // データ取得
            $orderMainQuery = DB::table('t_order')
                ->where('id', $orderId)
                ;
            $data = $this
                ->from(DB::raw('('.$orderMainQuery->toSql().') as o'))
                ->mergeBindings($orderMainQuery)
                ->join('t_order_detail as od', 'od.order_id', '=', 'o.id')
                ->where('o.id', $orderId)
                ->where('od.order_id', $orderId)
                ->select(
                    'od.stock_quantity',
                    'od.arrival_quantity'
                )
                ->get()    
                ;
            
            if (count($data) === 0) {
                // 発注明細がない発注データは何もしない
            } else {
                // 入荷済数チェック
                $arrivalCnt = 0;
                foreach($data as $rec) {
                    // 発注数（管理数）と入荷済み数を比較して、入荷完了している発注明細数をカウント
                    if ($rec->stock_quantity <= $rec->arrival_quantity) {
                        $arrivalCnt++;
                    }
                }

                $arrivalCompleteFlg = config('const.flg.off');
                if ($arrivalCnt === count($data)) {
                    // すべての発注明細が入荷完了している場合、入荷完了フラグを立てる
                    $arrivalCompleteFlg = config('const.flg.on');
                }

                // ログテーブルへの書き込み
                $LogUtil = new LogUtil();
                $LogUtil->putById($this->table, $orderId, config('const.logKbn.update'));
                
                // 更新
                $updateCnt = $this
                    ->where('id', $orderId)
                    ->update([
                        'arrival_complete_flg' => $arrivalCompleteFlg,
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            }
        } catch (\Exception $e) {
            throw $e;
        }
    }

    // /**
    //  * 総計更新
    //  *
    //  * @param [type] $id:配列可 IDでサマリ
    //  * @return void
    //  */
    // public function updateTotalKngById($id){
    //     $result = false;

    //     // ログテーブルへの書き込み
    //     $LogUtil = new LogUtil();

    //     try {
    //         $tmpIds = $id;
    //         if (!is_array($tmpIds)) {
    //             $tmpIds = [$tmpIds];
    //         }
    //         foreach ($tmpIds as $value) {
    //             $logWhere = [
    //                 ['del_flg', config('const.flg.off')],
    //                 ['id', $value]
    //             ];
    //             $LogUtil->putByWhere($this->table, $logWhere, config('const.logKbn.update'));
    //         }

    //         $summaryQuery = $this->from('t_order_detail')
    //             ->select([
    //                 'order_id',
    //                 DB::raw('SUM(cost_total) AS cost_total'),
    //                 DB::raw('SUM(sales_total) AS sales_total'),
    //                 DB::raw('SUM(profit_total) AS profit_total')
    //             ])
    //             ->groupBy('order_id')
    //             ->where('del_flg', config('const.flg.off'))
    //             ->when(isset($id), function($query) use($id) {
    //                 if (is_array($id)) {
    //                     $query->whereIn('order_id', $id);
    //                 }else{
    //                     $query->where('order_id', $id);
    //                 }
    //             })
    //             ->get()
    //             ;

    //         if (count($summaryQuery) > 0) {
    //             foreach ($summaryQuery as $key => $record) {
    //                 $updateWhere = [];
    //                 $updateWhere[] = array('o.id', '=', $record['order_id']);

    //                 $updateCnt = $this->from('t_order AS o')
    //                     ->where($updateWhere)
    //                     ->update([
    //                         'o.cost_total' => $record['cost_total'],
    //                         'o.sales_total' => $record['sales_total'],
    //                         'o.profit_total' => $record['profit_total'],
    //                         'o.update_user' => DB::raw(Auth::user()->id),
    //                         'o.update_at' => DB::raw("'".Carbon::now()."'"),
    //                     ]);
    //             }
    //         }
    //             $result = ($updateCnt > 0);
    //     } catch (\Exception $e) {
    //         throw $e;
    //     }
    //     return $result;
    // }

    /**
     * 総計更新
     *
     * @param [type] $quoteNo 見積番号
     * @return void
     */
    public function updateTotalKngByQuoteNo($quoteNo){
        $result = false;

        // ログテーブルへの書き込み
        $LogUtil = new LogUtil();

        try {
            $logWhere = [
                ['del_flg', config('const.flg.off')],
                ['quote_no', $quoteNo],
                ['status', '!=', config('const.orderStatus.val.not_ordering')]
            ];

            $where = [
                ['o.del_flg', config('const.flg.off')],
                ['o.quote_no', $quoteNo],
                ['o.status','!=', config('const.orderStatus.val.not_ordering')],
            ];
            $summaryQuery = $this->from('t_order AS o')
                ->leftJoin('t_order_detail AS od', 'o.id', '=', 'od.order_id')
                ->select([
                    'o.id AS order_id',
                    DB::raw('SUM(od.cost_total) AS cost_total'),
                    DB::raw('SUM(od.sales_total) AS sales_total'),
                    DB::raw('SUM(od.profit_total) AS profit_total')
                ])
                ->groupBy('o.id')
                ->where($where)
                ->where(function($query) {
                    $query->where('od.parent_order_detail_id',0)
                          ->orWhereNull('od.parent_order_detail_id');
                })
                ->get();

            
            if (count($summaryQuery) > 0) {
                $LogUtil->putByWhere($this->table, $logWhere, config('const.logKbn.update'));

                foreach ($summaryQuery as $key => $record) {
                    // Where作成
                    $updateWhere = [];
                    $updateWhere[] = array('main.id', '=', $record['order_id']);

                    $updateCnt = $this->from('t_order AS main')
                        ->where($updateWhere)
                        ->update([
                            'main.cost_total' => DB::raw('IFNULL('.Common::nullorBlankToZero($record['cost_total']).',0)'),
                            //'main.sales_total' => DB::raw('sumData.sales_total'),
                            //'main.profit_total' => DB::raw('sumData.profit_total'),
                            'main.update_user' => DB::raw(Auth::user()->id),
                            'main.update_at' => DB::raw("'".Carbon::now()."'"),
                        ]);
                }

                $result = ($updateCnt > 0);
            }

        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param int $id 発注ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteById($id) {
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
     * コンボボックス用データ取得
     * @param $matterNo     案件番号
     * @return 
     */
    public function getComboList($matterNo = null) {
        // Where句作成
        $where = [];
        $where[] = array('t_order.del_flg', '=', config('const.flg.off'));

        if($matterNo !== null){
            $where[] = array('t_order.matter_no', '=', $matterNo);
        }
        
        // データ取得
        $data = $this
                ->where($where)
                ->whereNotNull('t_order.order_no')
                ->leftjoin('m_warehouse', 'm_warehouse.id', '=', 't_order.delivery_address_id')
                ->orderBy('t_order.id', 'asc')
                ->select([
                    't_order.id',
                    't_order.order_no',
                    DB::raw("(CASE delivery_address_kbn WHEN 1 THEN '' ELSE m_warehouse.warehouse_name END) AS warehouse_name")
                ])
                ->get()
                ;
        
        return $data;
    }
    
    /**
     * 入荷用届け先コンボリスト
     * @param 
     * @return 
     */
    public function getArrivalComboList() {
        // Where句作成
        $where = [];
        $where[] = array('t_order.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_order.arrival_complete_flg', '=', config('const.flg.off'));
        $where[] = array('t_order.delivery_address_kbn', '=', 2);
        $where[] = array('t_order.status', '=', 4);

        // データ取得
        $data = $this
                ->where($where)
                ->whereNotNull('m_warehouse.warehouse_name')
                ->leftjoin('m_warehouse', 'm_warehouse.id', '=', 't_order.delivery_address_id')
                ->selectRaw('m_warehouse.id as warehouse_id,m_warehouse.warehouse_name')
                ->distinct()
                ->get()
                ;
        
        return $data;
    }

    /**
     * ステータス更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateStatusById($id, $status) {
        $result = false;
        try {
            // Where句作成
            $where = [];
            $where[] = array('id', '=', $id);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));

            $items = [];
            $items['status'] = $status;
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

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
     * 発注データ取得
     *
     * @param string $matterNo 案件番号
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function getByMatterQuote($matterNo, $quoteNo) {
        // 案件番号または見積番号が一致する発注データを取得
        $data = $this
                ->where('del_flg', '=', config('const.flg.off'))
                ->where(function($query) use ($matterNo, $quoteNo) {
                    $query->where('matter_no', '=', $matterNo)
                          ->orWhere('quote_no', '=', $quoteNo);
                })
                ->get()
                ;

        return $data;
    }

    /**
     * 倉庫、商品ごとに次回入荷日取得(１件)
     *
     * @param $warehouseId　倉庫ID
     * @param $productId    商品ID
     * @return void
     */
    public function getNextArrivalDate($warehouseId, $productId) 
    {
        $where = [];
        $where[] = array('order_detail.product_id', '=', $productId);
        $where[] = array('t_order.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
        $where[] = array('t_order.delivery_address_id', '=', $warehouseId);
        $where[] = array('order_detail.arrival_quantity', '=', 0);
        $now = Carbon::now()->format('Y-m-d');

        $data = $this
                ->from('t_order_detail as order_detail')
                ->leftJoin('t_order', 'order_detail.order_id', '=', 't_order.id')
                ->selectRaw('
                        DATE_FORMAT(MAX(order_detail.arrival_plan_date), "%Y/%m/%d") as next_arrival_date
                ')
                // ->whereRaw('
                //     order_detail.arrival_plan_date >= \''. $now .'\' 
                // ')
                ->where($where)
                ->orderBy('order_detail.arrival_plan_date')
                ->first()
                ;

        return $data;
    }

    /**
     * 発注詳細画面用のデータ取得
     *
     * @param [type] $id 発注ID
     * @return void
     */
    public function getOrderDetailEditData($id){
        // Where句作成
        $where = [];
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.id', '=', $id);

        // データ取得
        $data = $this
            ->from('t_order as o')
            ->leftJoin('t_quote as q', 'o.quote_no', '=', 'q.quote_no')
            ->leftjoin('t_matter as m', 'o.matter_no', '=', 'm.matter_no')
            //->leftjoin('m_address as o_a', 'o.delivery_address_id', '=', 'o_a.id')
            ->leftjoin('m_address as m_a', 'm.address_id', '=', 'm_a.id')
            ->leftjoin('m_staff as s', 'm.staff_id', '=', 's.id')
            ->leftjoin('m_customer as c', 'm.customer_id', '=', 'c.id')
            ->leftJoin('m_general as c_general', function($join) {
                $join->on('c.juridical_code', '=', 'c_general.value_code')
                     ->where('c_general.category_code', '=', config('const.general.juridical'))
                     ;
            })
            ->leftjoin('m_supplier as ma', 'o.maker_id', '=', 'ma.id')
            ->leftjoin('m_supplier as su', 'o.supplier_id', '=', 'su.id')
            ->select(
                'o.id',
                'o.order_no',
                'o.person_id',
                'o.delivery_address_kbn',
                'o.delivery_address_id',
                // DB::raw('
                //     CASE 
                //       WHEN o.delivery_address_kbn = '. config('const.deliveryAddressKbn.val.site'). '
                //         THEN CONCAT(COALESCE(m_a.address1, \'\'), COALESCE(m_a.address2, \'\'))
                //       WHEN o.delivery_address_kbn in('. config('const.deliveryAddressKbn.val.company'). ', '. config('const.deliveryAddressKbn.val.supplier'). ')
                //         THEN CONCAT(COALESCE(o_a.address1, \'\'), COALESCE(o_a.address2, \'\'))
                //         ELSE NULL
                //     END AS delivery_address
                // '),
                'o.account_customer_name',
                'o.account_owner_name',
                'o.map_print_flg',
                'o.desired_delivery_date',
                'o.sales_support_comment',
                'o.supplier_comment',
                'q.id as quote_id',
                'q.quote_no as quote_no',
                'o.own_stock_flg',
                'o.status',
                DB::raw('DATE_FORMAT(o.order_apply_datetime, \'%Y/%m/%d\') AS order_apply_datetime'),
                DB::raw('DATE_FORMAT(o.order_approval_datetime, \'%Y/%m/%d\') AS order_approval_datetime'),
                DB::raw('DATE_FORMAT(o.order_datetime, \'%Y/%m/%d\') AS order_datetime'),
                DB::raw('DATE_FORMAT(o.update_at, \'%Y/%m/%d\') AS update_at'),
                'm.id as matter_id',
                'm.matter_no',
                'm.matter_name',
                'm.address_id as matter_address_id',
                DB::raw('CONCAT(COALESCE(m_a.address1, \'\'), COALESCE(m_a.address2, \'\')) as matter_address'),
                'm.complete_flg',
                'c.id as customer_id',
                DB::raw('CONCAT(COALESCE(c_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(c_general.value_text_3, \'\')) as customer_name'),
                'ma.id as maker_id',
                'ma.supplier_name as maker_name',
                'su.id as supplier_id',
                'su.supplier_name as supplier_name'
            )
            ->where($where)
            ->first()  
            ;
        return $data;
    }

    /**
     * 発注書用データ取得
     *
     * @param [type] $orderId
     * @return void
     */
    public function getOrderReportDataById($orderId)
    {
        $data = $this
                ->where('o.id', $orderId)
                ->from('t_order AS o')
                ->leftJoin('m_supplier AS o_maker', 'o.maker_id', 'o_maker.id')
                ->leftJoin('m_general as maker_general', function($join) {
                    $join->on('o_maker.juridical_code', '=', 'maker_general.value_code')
                         ->where('maker_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftJoin('m_supplier AS o_supplier', 'o.supplier_id', 'o_supplier.id')
                ->leftJoin('m_general as supplier_general', function($join) {
                    $join->on('o_supplier.juridical_code', '=', 'supplier_general.value_code')
                         ->where('supplier_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftjoin('m_address as o_address', 'o.delivery_address_id', '=', 'o_address.id')
                ->leftJoin('m_warehouse AS o_warehouse', 'o.delivery_address_id', 'o_warehouse.id')
                ->leftJoin('m_staff AS o_staff', 'o.order_staff_id', 'o_staff.id')
                ->leftJoin('t_matter AS m', 'o.matter_no', 'm.matter_no')
                ->leftJoin('m_department AS m_depart', 'm.department_id', 'm_depart.id')
                ->leftJoin('m_staff AS m_staff', 'm.staff_id', 'm_staff.id')
                ->leftJoin('m_staff AS chief_staff', 'm_depart.chief_staff_id', 'chief_staff.id')
                ->leftjoin('m_customer as m_customer', 'm.customer_id', 'm_customer.id')
                ->leftJoin('m_general as customer_general', function($join) {
                    $join->on('m_customer.juridical_code', '=', 'customer_general.value_code')
                         ->where('customer_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->select([
                    'o.order_no',                                       // 注文No.
                    DB::raw('
                      CASE
                        WHEN o.order_datetime IS NULL
                          THEN DATE_FORMAT(now(), \'%Y年%c月%e日\')
                        ELSE
                          DATE_FORMAT(o.order_datetime, \'%Y年%c月%e日\')
                      END AS order_datetime
                    '),                                                // 発行
                    DB::raw('
                        CONCAT(
                            COALESCE(supplier_general.value_text_2, \'\'),
                            COALESCE(o_supplier.supplier_name, \'\'),
                            COALESCE(supplier_general.value_text_3, \'\'),
                            \' \',
                            COALESCE(o_supplier.honorific, \'\')
                        ) as supplier_name'),                           // 仕入先名
                    DB::raw('
                        CONCAT(
                            COALESCE(maker_general.value_text_2, \'\'),
                            COALESCE(o_maker.supplier_name, \'\'),
                            COALESCE(maker_general.value_text_3, \'\')
                        ) as maker_name'),                              // メーカー名
                    'm_depart.department_name AS matter_department',    // 担当部門

                    'chief_staff.staff_name AS chief_staff',            // 責任者
                    'm_staff.staff_name AS matter_staff',               // 営業担当者
                    'o_staff.staff_name AS order_staff',                // 発注担当者
                    
                    DB::raw('
                        CASE
                          WHEN o.delivery_address_kbn = '. config('const.deliveryAddressKbn.val.site'). '
                            THEN \''. config('const.directDeliveryAddressName'). '\'
                          WHEN o.delivery_address_kbn in('. config('const.deliveryAddressKbn.val.company'). ', '. config('const.deliveryAddressKbn.val.supplier'). ')
                            THEN o_warehouse.warehouse_name
                          ELSE
                            NULL
                        END AS delivery_name
                    '),                                                 // 送り先名
                    'm_depart.tel AS department_tel',                   // 担当連絡先
                    DB::raw('
                        CASE 
                          WHEN o.delivery_address_kbn = '. config('const.deliveryAddressKbn.val.site'). '
                            THEN CONCAT(COALESCE(o_address.address1, \'\'), COALESCE(o_address.address2, \'\'))
                          WHEN o.delivery_address_kbn in('. config('const.deliveryAddressKbn.val.company'). ', '. config('const.deliveryAddressKbn.val.supplier'). ')
                            THEN CONCAT(COALESCE(o_warehouse.address1, \'\'), COALESCE(o_warehouse.address2, \'\'))
                          ELSE NULL
                        END AS delivery_address
                    '),                                                 // 送り先住所
                    DB::raw('
                        CONCAT(
                            COALESCE(customer_general.value_text_2, \'\'),
                            COALESCE(m_customer.customer_name, \'\'),
                            COALESCE(customer_general.value_text_3, \'\')
                        ) as customer_name'),                           // 得意先名（得意先名）
                    'o.account_customer_name AS account_customer_name', // 得意先名（使用口座先得意先名）
                    'm.owner_name AS owner_name',                       // 現場名（施主名）
                    'o.account_owner_name AS account_owner_name',       // 現場名（使用口座先得意先名）
                    'o.supplier_comment',                               // 備考

                    DB::raw('DATE_FORMAT(o.desired_delivery_date, \'%Y/%m/%d\') AS desired_delivery_date') // 納期依頼（発注書明細部）
                ])
                ->first()
                ;
        return $data;
    }

    /**
     * 受発注一覧　検索
     *
     * @param array $params 検索条件
     * @return void
     */
    public function getOrderList($params) {
        // 無形品含むチェック有は、引当は検索しない
        if (!empty($params['chk_intangible_flg'])) {
            $params['reserved'] = config('const.flg.off');
        }
        // 入荷予定日なし含むチェック有は、発注済以外は検索しない
        if(!empty($params['chk_no_arrival_plan_date_flg'])) {
            $params['not_ordering'] = config('const.flg.off');
            $params['editing'] = config('const.flg.off');
            // $params['ordered'] = config('const.flg.on');
            $params['not_treated'] = config('const.flg.off');
            $params['reserved'] = config('const.flg.off');
        }
        // 発注番号・発注担当者・発注登録日From-To・入荷先の指定がある場合、発注のみを見ることになる（未発注or発注済のみを検索する）
        if (!empty($params['order_no']) || !empty($params['order_staff_id']) 
            || !empty($params['order_process_date_from']) || !empty($params['order_process_date_to'])
            || !empty($params['chk_address_kbn_site_flg']) || !empty($params['chk_address_kbn_company_flg']) || !empty($params['chk_address_kbn_supplier_flg'])
            || !empty($params['chk_no_arrival_plan_date_flg'])) {
                // $params['not_ordering'] = config('const.flg.on');
                $params['editing'] = config('const.flg.off');
                // $params['ordered'] = config('const.flg.on');
                $params['not_treated'] = config('const.flg.off');
                $params['reserved'] = config('const.flg.off');
        }

        // 受注登録日の指定がある場合、見積番号を絞り込む：未発注or発注済or編集中のみで使用
        // $quoteNoList = null;
        $quoteNoWhere = [];
        if (!empty($params['order_register_date_from']) || !empty($params['order_register_date_to'])) {
            if (!empty($params['quote_no'])) {
                // $quoteNoWhere[] = array('quote_no', '=', $params['quote_no']);
                $quoteNoWhere[] = array('quote_no', 'LIKE', '%'.$params['quote_no'].'%');
            } 
            $quoteNoWhere[] = array('quote_version', '=', config('const.quoteCompVersion.number'));
            $quoteNoWhere[] = array('layer_flg', '=', config('const.flg.off'));
            $quoteNoWhere[] = array('over_quote_detail_id', '=', 0);
            $quoteNoWhere[] = array('del_flg', '=', config('const.flg.off'));
            if (!empty($params['order_register_date_from'])) {
                $quoteNoWhere[] = array('created_at', '>=', $params['order_register_date_from']);
            }
            if (!empty($params['order_register_date_to'])) {
                $quoteNoWhere[] = array('created_at', '<', date('Y/m/d', strtotime($params['order_register_date_to']. ' +1 day')));
            }

            // $quoteNoList = DB::table('t_quote_detail AS qd')
            //     ->where($quoteNoWhere)
            //     // ->when(!empty($params['order_register_date_from']), function ($query) use ($params) {
            //     //     return $query->where('qd.created_at', '>=', $params['order_register_date_from']);
            //     // })
            //     // ->when(!empty($params['order_register_date_to']), function ($query) use ($params) {
            //     //     return $query->where('qd.created_at', '<', date('Y/m/d', strtotime($params['order_register_date_to']. ' +1 day')));
            //     // })
            //     ->select('quote_no')
            //     // ->distinct()
            //     // ->pluck('quote_no')
            //     // ->toArray()
            //     ;
        }
        
        // 案件テーブルの検索条件が指定されている場合サブクエリで絞る
        $matterQuery = null;
        if (empty($params['matter_no']) && empty($params['customer_id']) && empty($params['department_id']) && empty($params['sales_staff_id'])) {
            // nop
        } else {
            $matterWhere = [];
            if (!empty($params['matter_no'])) {
                $matterWhere[] = array('matter_no', '=', $params['matter_no']);
            }
            if (!empty($params['customer_id'])) {
                $matterWhere[] = array('customer_id', '=', $params['customer_id']);
            }
            if (!empty($params['department_id'])) {
                $matterWhere[] = array('department_id', '=', $params['department_id']);
            }
            if (!empty($params['sales_staff_id'])) {
                $matterWhere[] = array('staff_id', '=', $params['sales_staff_id']);
            }

            $matterQuery = DB::table('t_matter')
                ->where($matterWhere)
                ;
        }

        // ------ メインSQL ------

        // 未発注or発注済が検索条件に指定されている場合のみ
        $query1 = null;
        if (!empty($params['not_ordering']) || !empty($params['ordered'])) {
            // メインテーブル　FROM句をサブクエリで絞る
            $orderFromWhere = [];
            if (!empty($params['order_no'])) {
                // $orderFromWhere[] = array('order_no', '=', $params['order_no']);
                $orderFromWhere[] = array('order_no', 'LIKE', '%'.$params['order_no'].'%');
            }
            if (!empty($params['matter_no'])) {
                $orderFromWhere[] = array('matter_no', '=', $params['matter_no']);
            }
            if (!empty($params['quote_no'])) {
                // $orderFromWhere[] = array('quote_no', '=', $params['quote_no']);
                $orderFromWhere[] = array('quote_no', 'LIKE', '%'.$params['quote_no'].'%');
            }
            if (!empty($params['maker_id'])) {
                $orderFromWhere[] = array('maker_id', '=', $params['maker_id']);
            }
            if (!empty($params['supplier_id'])) {
                $orderFromWhere[] = array('supplier_id', '=', $params['supplier_id']);
            }
            if (!empty($params['order_staff_id'])) {
                $orderFromWhere[] = array('created_user', '=', $params['order_staff_id']);
            }
            if (!empty($params['order_process_date_from'])) {
                $orderFromWhere[] = array('created_at', '>=', $params['order_process_date_from']);
            }
            if (!empty($params['order_process_date_to'])) {
                $orderFromWhere[] = array('created_at', '<', date('Y/m/d', strtotime($params['order_process_date_to']. ' +1 day')));
            }
            $orderFromWhere[] = array('status', '!=', config('const.orderStatus.val.not_ordering'));  // 編集中以外（実際は承認フローの仕様がなくなったため1,2,5は未使用なので編集中を除外すればOK）
            if (empty($params['not_ordering'])) {
                $orderFromWhere[] = array('status', '!=', config('const.orderStatus.val.approved'));
            }
            if (empty($params['ordered'])) {
                $orderFromWhere[] = array('status', '!=', config('const.orderStatus.val.ordered'));
            }
            $orderFromWhere[] = array('del_flg', '=', config('const.flg.off'));

            // 入荷先
            $isUseDeliveryAddressKbn = false;
            if (!empty($params['chk_address_kbn_site_flg']) || !empty($params['chk_address_kbn_company_flg']) || !empty($params['chk_address_kbn_supplier_flg'])) {
                $isUseDeliveryAddressKbn = true;
            }

            $orderMainQuery = DB::table('t_order')
                ->where($orderFromWhere)
                ->when($isUseDeliveryAddressKbn, function ($q1) use ($params) {
                    $q1->where(function($q1) use ($params) {
                        if (!empty($params['chk_address_kbn_site_flg'])) {
                            $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.site'));
                        }
                        if (!empty($params['chk_address_kbn_company_flg'])) {
                            $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
                        }
                        if (!empty($params['chk_address_kbn_supplier_flg'])) {
                            $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.supplier'));
                        }
                    });
                })
                ;
            
            $query1 = $this
                ->from(DB::raw('('.$orderMainQuery->toSql().') as o'))
                ->mergeBindings($orderMainQuery)
                ;
                
            // 見積明細で絞る場合・商品・無形品含むチェック有・入荷予定日なし含むチェック有が指定されている場合は、内部結合で発注レコードを絞る
            if (count($quoteNoWhere) > 0 || !empty($params['product_name']) || !empty($params['product_code'])
                || !empty($params['chk_intangible_flg']) || !empty($params['chk_no_arrival_plan_date_flg'])) {
                $odWhere = [];
                $odWhere[] = array('del_flg', '=', config('const.flg.off'));
                if (!empty($params['product_name'])) {
                    $odWhere[] = array('product_name', 'LIKE', '%'.$params['product_name'].'%');
                }
                if (!empty($params['product_code'])) {
                    $odWhere[] = array('product_code', '=', $params['product_code']);
                }
                if (!empty($params['chk_intangible_flg'])) {
                    $odWhere[] = array('product_code', '=', '');
                }

                $odProductQuery = DB::table('t_order_detail as od_core')
                    ->where($odWhere)
                    ->when(!empty($params['chk_no_arrival_plan_date_flg']), function ($q2) {
                        return $q2->whereNull('arrival_plan_date');
                    })
                    ;
                if (count($quoteNoWhere) > 0) {
                    $odProductQuery
                        ->whereExists(function ($query) use($quoteNoWhere) {
                            $query 
                                ->select('qd_quote_no.quote_no')
                                ->from('t_quote_detail as qd_quote_no')
                                ->where($quoteNoWhere)
                                ->whereRaw('od_core.quote_detail_id = qd_quote_no.id')
                                ;
                        })
                        ;
                }
                $odProductQuery
                    ->select('order_id')
                    ->distinct()
                    ;

                $query1
                    ->join(DB::raw('('.$odProductQuery->toSql().') as od_product'), function($join){
                        $join->on('o.id', 'od_product.order_id');
                    })
                    ->mergeBindings($odProductQuery)
                    ;
            }
            
            // 案件テーブルの検索条件が指定されている場合サブクエリで絞る、指定がない場合はそのまま結合する
            if (is_null($matterQuery)) {
                $query1
                    ->leftJoin('t_matter as matter', 'o.matter_no', '=', 'matter.matter_no')
                    ;
            } else {
                $query1
                    ->join(DB::raw('('.$matterQuery->toSql().') as matter'), function($join){
                        $join->on('o.matter_no', 'matter.matter_no');
                    })
                    ->mergeBindings($matterQuery)
                    ;
            }

            // // ここまでで絞り込まれている発注番号を一度取得     // バインド変数が多すぎ or SQLが長すぎで上手くいかなくなる？
            // $orderNoList = $query1
            //     ->select('order_no')
            //     ->distinct()
            //     ->pluck('order_no')
            //     ->toArray()
            //     ;

            $odQuery = DB::table('t_order_detail as od_core2')
                ->where('del_flg', '=', config('const.flg.off'))
                // ->whereIn('order_no', $orderNoList)
                ->whereExists(function ($query) use($orderFromWhere, $isUseDeliveryAddressKbn, $params) {
                    $query 
                        ->select('o_order_no.id')
                        ->from('t_order as o_order_no')
                        ->where($orderFromWhere)
                        ->when($isUseDeliveryAddressKbn, function ($q1) use ($params) {
                            $q1->where(function($q1) use ($params) {
                                if (!empty($params['chk_address_kbn_site_flg'])) {
                                    $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.site'));
                                }
                                if (!empty($params['chk_address_kbn_company_flg'])) {
                                    $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
                                }
                                if (!empty($params['chk_address_kbn_supplier_flg'])) {
                                    $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.supplier'));
                                }     
                            });
                        })
                        ->whereRaw('od_core2.order_id = o_order_no.id')
                        ;
                })
                ;

            // 発注額取得
            $costQuery = 
                DB::table('t_order_detail as od_core1')
                    ->where('del_flg', '=', config('const.flg.off'))
                    // ->whereIn('order_no', $orderNoList)
                    ->whereExists(function ($query) use($orderFromWhere, $isUseDeliveryAddressKbn, $params) {
                        $query 
                            ->select('o_order_no.id')
                            ->from('t_order as o_order_no')
                            ->where($orderFromWhere)
                            ->when($isUseDeliveryAddressKbn, function ($q1) use ($params) {
                                $q1->where(function($q1) use ($params) {
                                    if (!empty($params['chk_address_kbn_site_flg'])) {
                                        $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.site'));
                                    }
                                    if (!empty($params['chk_address_kbn_company_flg'])) {
                                        $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company'));
                                    }
                                    if (!empty($params['chk_address_kbn_supplier_flg'])) {
                                        $q1->orWhere('delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.supplier'));
                                    }
                                });
                            })
                            ->whereRaw('od_core1.order_id = o_order_no.id')
                            ;
                    })
                    ->select([
                        'order_no',
                        DB::raw('SUM(COALESCE(cost_total,0)) AS cost_total_sum')
                    ])
                    ->groupBy('order_no')
                    ;

            // 販売額取得 ：一式子部品ではない場合は、発注明細.販売総額 ：一式子部品の場合は見積明細の親明細の販売総額
            $noSetSalesTotal = 
                DB::table(DB::raw('('.$odQuery->toSql().') as od_sub'))
                    ->mergeBindings($odQuery)
                    ->leftJoin('t_quote_detail AS qd_link', 'od_sub.quote_detail_id', '=', 'qd_link.id')
                    ->leftJoin('t_quote_detail AS qd_parent', 'qd_link.parent_quote_detail_id', '=', 'qd_parent.id')
                    ->where('qd_parent.set_flg', '=', config('const.flg.off'))   // 工事区分階層が必ず存在するため、親見積明細が存在しないことは考慮しない
                    ->select([
                        'od_sub.order_no',
                        DB::raw('COALESCE(od_sub.sales_total,0) AS sales_total')
                    ])
                    ;
            $setSalesTotal = 
                DB::table(DB::raw('('.$odQuery->toSql().') as od_sub'))
                    ->mergeBindings($odQuery)
                    ->leftJoin('t_quote_detail AS qd_link', 'od_sub.quote_detail_id', '=', 'qd_link.id')
                    ->leftJoin('t_quote_detail AS qd_parent', 'qd_link.parent_quote_detail_id', '=', 'qd_parent.id')
                    ->where('qd_parent.set_flg', '=', config('const.flg.on'))
                    ->select([
                        'od_sub.order_no',
                        DB::raw('COALESCE(qd_parent.sales_total,0) AS sales_total')
                    ])
                    ->distinct()  // 重複して計算に使用しないようにDISTINCT
                    ;
            $salesUnion = $noSetSalesTotal->unionAll($setSalesTotal);   // 一式子部品と通常明細は混ざることもあるのでUNION ALLで全部取得

            $costSalesQuery = DB::table(DB::raw('('.$costQuery->toSql().') as od_cost'))
                    ->mergeBindings($costQuery)
                    ->leftJoin(DB::raw('('.$salesUnion->toSql().') as qd_sales'), function($join){
                        $join->on('od_cost.order_no', 'qd_sales.order_no');
                    })
                    ->mergeBindings($salesUnion)
                    ->groupBy('od_cost.order_no', 'cost_total_sum')
                    ->select([
                        'od_cost.order_no', 
                        'od_cost.cost_total_sum',
                        DB::raw('SUM(qd_sales.sales_total) AS sales_total_sum'),
                    ])
                    ;
            
            $query1
                ->leftJoin(DB::raw('('.$costSalesQuery->toSql().') as od'), function($join){
                    $join->on('o.order_no', 'od.order_no');
                })
                ->mergeBindings($costSalesQuery)
                ;

            // // 明細の1件目の商品名取得  // MEMO:サブクエリを外部結合するよりSELECT句で取得したほうが速い？
            // $odProductDispQuery = DB::table('t_order_detail')
            //     ->where('del_flg', '=', config('const.flg.off'))
            //     ->where('seq_no', '=', 1)  // 明細の1件目のみ
            //     // ->whereIn('order_no', $orderNoList)
            //     ->whereNotNull('order_no')
            //     ->select([
            //         'order_id',
            //         'order_no',
            //         'product_name'
            //     ])
            //     ;

            // MEMO:仕様変更で住所表示は不要になったが念のためコメントアウトで残しておく
            $query1
                // ->leftJoin(DB::raw('('.$odProductDispQuery->toSql().') as od_product_disp'), function($join){
                //     $join->on('o.id', 'od_product_disp.order_id');
                // })
                // ->mergeBindings($odProductDispQuery)
                ->leftJoin('t_quote as quote', 'o.quote_no', '=', 'quote.quote_no')
                ->leftJoin('m_supplier as maker', 'o.maker_id', '=', 'maker.id')
                ->leftJoin('m_supplier as supplier', 'o.supplier_id', '=', 'supplier.id')
                ->leftJoin('m_staff as order_staff', 'o.created_user', '=', 'order_staff.id')
                ->leftJoin('m_staff as sales_staff', 'matter.staff_id', '=', 'sales_staff.id')
                ->leftJoin('m_department as department', 'matter.department_id', '=', 'department.id')
                // ->leftJoin('m_address as addr', 'o.delivery_address_id', '=', 'addr.id')
                // ->leftJoin('m_warehouse as warehouse', 'o.delivery_address_id', '=', 'warehouse.id')
                ->select([
                    'o.id AS order_id',
                    'quote.id AS quote_id',
                    'matter.id AS matter_id',
                    'o.order_no',
                    'o.status AS disp_status',
                    'o.own_stock_flg',
                    'department.department_name',
                    'sales_staff.staff_name',
                    'maker.supplier_name AS maker_name',
                    'supplier.supplier_name',
                    'matter.matter_no',
                    'matter.matter_name',
                    DB::raw('COALESCE(od.cost_total_sum,0) AS cost_total'),
                    DB::raw('COALESCE(od.sales_total_sum,0) AS sales_total'),
                    DB::raw('(COALESCE(od.sales_total_sum,0) - COALESCE(od.cost_total_sum,0)) AS profit_total'),
                    DB::raw('NULL AS gross_profit_rate'),
                    DB::raw('DATE_FORMAT(o.created_at, "%Y/%m/%d %H:%i:%s") as order_datetime'),
                    'order_staff.staff_name AS order_staff_name',
                    // 'od_product_disp.product_name AS product_name',
                    DB::raw('(SELECT product_name FROM t_order_detail od_product_disp WHERE od_product_disp.order_id = o.id AND seq_no = 1) AS product_name'),
                    'o.sales_support_comment',
                    // DB::raw('
                    //     CASE
                    //     WHEN o.delivery_address_kbn = '. config('const.deliveryAddressKbn.val.site'). '
                    //         THEN CONCAT(COALESCE(addr.address1, \'\'), COALESCE(addr.address2, \'\'))
                    //     WHEN o.delivery_address_kbn IN ('. config('const.deliveryAddressKbn.val.company'). ', '. config('const.deliveryAddressKbn.val.supplier'). ')
                    //         THEN CONCAT(COALESCE(warehouse.address1, \'\'), COALESCE(warehouse.address2, \'\'))
                    //     END AS address
                    // '),
                    'matter.complete_flg'
                ])
                ;
        }

        // 未処理or編集中or引当が検索条件に指定されている場合のみ
        $query2 = null;
        $query3 = null;
        if (!empty($params['not_treated']) || !empty($params['editing']) || !empty($params['reserved'])) {
            
            // 編集中（発注一時保存データ）
            if (!empty($params['editing'])) {
                // メインテーブル　FROM句をサブクエリで絞る
                $notOrderFromWhere = [];
                if (!empty($params['order_no'])) {
                    // $notOrderFromWhere[] = array('order_no', '=', $params['order_no']);
                    $notOrderFromWhere[] = array('order_no', 'LIKE', '%'.$params['order_no'].'%');
                }
                if (!empty($params['matter_no'])) {
                    $notOrderFromWhere[] = array('matter_no', '=', $params['matter_no']);
                }
                if (!empty($params['quote_no'])) {
                    // $notOrderFromWhere[] = array('quote_no', '=', $params['quote_no']);
                    $notOrderFromWhere[] = array('quote_no', 'LIKE', '%'.$params['quote_no'].'%');
                }
                $notOrderFromWhere[] = array('status', '=', config('const.orderStatus.val.not_ordering'));  // 編集中
                $notOrderFromWhere[] = array('own_stock_flg', '=', config('const.flg.off'));  // 当社在庫専用案件の未処理と編集中は不要なため除外
                $notOrderFromWhere[] = array('del_flg', '=', config('const.flg.off'));

                $notOrderMainQuery = DB::table('t_order as o_core')
                    ->where($notOrderFromWhere)
                    ;
                // MEMO:一度取得してIN句で書くよりExists使ったほうが速い？
                // if (!is_null($quoteNoList)) {
                //     $notOrderMainQuery
                //         ->whereIn('quote_no', $quoteNoList);
                // }
                if (count($quoteNoWhere) > 0) {
                    $notOrderMainQuery
                        ->whereExists(function ($query) use($quoteNoWhere) {
                            $query 
                                ->select('qd_quote_no.quote_no')
                                ->from('t_quote_detail as qd_quote_no')
                                ->where($quoteNoWhere)
                                ->whereRaw('o_core.quote_no = qd_quote_no.quote_no')
                                ;
                        })
                        ;
                }
                
                $query2 = $this
                    ->from(DB::raw('('.$notOrderMainQuery->toSql().') as o'))
                    ->mergeBindings($notOrderMainQuery)
                    ;

                // 検索条件でメーカー・仕入先・商品・無形品含むチェック有が指定されている場合のみ、内部結合で発注レコードを絞る：編集中のみで使用
                if (!empty($params['maker_id']) || !empty($params['supplier_id']) || !empty($params['product_name']) || !empty($params['product_code'])
                    || !empty($params['chk_intangible_flg'])) {
                    $odProducrWhere = [];
                    if (!empty($params['maker_id'])) {
                        $odProducrWhere[] = array('maker_id', '=', $params['maker_id']);
                    }
                    if (!empty($params['supplier_id'])) {
                        $odProducrWhere[] = array('supplier_id', '=', $params['supplier_id']);
                    }
                    if (!empty($params['product_name'])) {
                        $odProducrWhere[] = array('product_name', 'LIKE', '%'.$params['product_name'].'%');
                    }
                    if (!empty($params['product_code'])) {
                        $odProducrWhere[] = array('product_code', '=', $params['product_code']);
                    }
                    if (!empty($params['chk_intangible_flg'])) {
                        $odProducrWhere[] = array('product_code', '=', '');
                    }
                    $odProducrWhere[] = array('del_flg', '=', config('const.flg.off'));

                    $odProductQuery = DB::table('t_order_detail')
                        ->where($odProducrWhere)
                        ->select('order_id')
                        ->distinct()
                        ;

                    $query2
                        ->join(DB::raw('('.$odProductQuery->toSql().') as od_product'), function($join){
                            $join->on('o.id', 'od_product.order_id');
                        })
                        ->mergeBindings($odProductQuery)
                        ;
                }

                // 案件テーブルの検索条件が指定されている場合サブクエリで絞る、指定がない場合はそのまま結合する
                if (is_null($matterQuery)) {
                    $query2
                        ->leftJoin('t_matter as matter', 'o.matter_no', '=', 'matter.matter_no')
                        ;
                } else {
                    $query2
                        ->join(DB::raw('('.$matterQuery->toSql().') as matter'), function($join){
                            $join->on('o.matter_no', 'matter.matter_no');
                        })
                        ->mergeBindings($matterQuery)
                        ;
                }

                $query2
                    ->leftJoin('t_quote as quote', 'o.quote_no', '=', 'quote.quote_no')
                    ->leftJoin('m_staff as sales_staff', 'matter.staff_id', '=', 'sales_staff.id')
                    ->leftJoin('m_department as department', 'matter.department_id', '=', 'department.id')
                    ->select([
                        'o.id AS order_id',
                        'quote.id AS quote_id',
                        'matter.id AS matter_id',
                        DB::raw('NULL AS order_no'),
                        DB::raw(config('const.orderListStatus.statusVal.editing') . ' AS disp_status'),
                        'o.own_stock_flg',
                        'department.department_name',
                        'sales_staff.staff_name',
                        DB::raw('NULL AS maker_name'),
                        DB::raw('NULL AS supplier_name'),
                        'matter.matter_no',
                        'matter.matter_name',
                        DB::raw('NULL AS cost_total'),
                        DB::raw('NULL AS sales_total'),
                        DB::raw('NULL AS profit_total'),
                        DB::raw('NULL AS gross_profit_rate'),
                        DB::raw('NULL AS order_datetime'),
                        DB::raw('NULL AS order_staff_name'),
                        DB::raw('NULL AS product_name'),
                        DB::raw('NULL AS sales_support_comment'),
                        // DB::raw('NULL AS address'),
                        'matter.complete_flg'
                    ])
                    ;
            }

            // 未処理or引当
            if (!empty($params['not_treated']) || !empty($params['reserved'])) {

                $qdMainWhere = [];
                if (!empty($params['quote_no'])) {
                    // $qdMainWhere[] = array('quote_no', '=', $params['quote_no']);
                    $qdMainWhere[] = array('quote_no', 'LIKE', '%'.$params['quote_no'].'%');
                } 
                $qdMainWhere[] = array('quote_version', '=', config('const.quoteCompVersion.number'));
                $qdMainWhere[] = array('layer_flg', '=', config('const.flg.off'));
                $qdMainWhere[] = array('over_quote_detail_id', '=', 0);
                $qdMainWhere[] = array('del_flg', '=', config('const.flg.off'));
                if (!empty($params['maker_id'])) {
                    $qdMainWhere[] = array('maker_id', '=', $params['maker_id']);
                }
                if (!empty($params['supplier_id'])) {
                    $qdMainWhere[] = array('supplier_id', '=', $params['supplier_id']);
                }
                if (!empty($params['product_name'])) {
                    $qdMainWhere[] = array('product_name', 'LIKE', '%'.$params['product_name'].'%');
                }
                if (!empty($params['product_code'])) {
                    $qdMainWhere[] = array('product_code', '=', $params['product_code']);
                }
                if (!empty($params['product_code'])) {
                    $qdMainWhere[] = array('product_code', '=', '');
                }

                $qdMainQuery = DB::table('t_quote_detail')
                    ->where($qdMainWhere)
                    ->when(!empty($params['order_register_date_from']), function ($query) use ($params) {
                        return $query->where('created_at', '>=', $params['order_register_date_from']);
                    })
                    ->when(!empty($params['order_register_date_to']), function ($query) use ($params) {
                        return $query->where('created_at', '<', date('Y/m/d', strtotime($params['order_register_date_to']. ' +1 day')));
                    })
                    ;
                
                // 未処理・引当の条件に一致するレコードの見積番号をそれぞれ取得
                $qdQuery = DB::table(DB::raw('('.$qdMainQuery->toSql().') as qd_main'))
                    ->mergeBindings($qdMainQuery)
                    ->leftJoin('t_order_detail as od', 'qd_main.id', '=', 'od.quote_detail_id')
                    ->leftJoin('t_reserve as r', 'qd_main.id', '=', 'r.quote_detail_id')
                    ->whereRaw(
                        '(od.id IS NULL AND r.id IS NULL) ' . 
                        ' OR r.stock_flg = ' . config('const.stockFlg.val.stock') . 
                        ' OR r.stock_flg = '. config('const.stockFlg.val.keep') 
                    )
                    ->select([
                        'qd_main.quote_no',
                        DB::raw(
                            ' CASE WHEN r.id IS NULL ' .
                            '   THEN ' . config('const.orderListStatus.statusVal.not_treated') .
                            '   ELSE '. config('const.orderListStatus.statusVal.reserved') .
                            ' END AS statusFlg'
                        ),
                    ])
                    ->distinct()
                    ;
                
                $query3 = 
                    // DB::table(DB::raw('('.$qdQuery->toSql().') as main'))
                    $this
                        ->from(DB::raw('('.$qdQuery->toSql().') as main'))
                        ->mergeBindings($qdQuery)
                        ->join('t_quote AS quote', 'main.quote_no', '=', 'quote.quote_no')
                        ;

                // 案件テーブルの検索条件が指定されている場合サブクエリで絞る、指定がない場合はそのまま結合する
                if (is_null($matterQuery)) {
                    $query3
                        ->leftJoin('t_matter as matter', 'quote.matter_no', '=', 'matter.matter_no')
                        ;
                } else {
                    $query3
                        ->join(DB::raw('('.$matterQuery->toSql().') as matter'), function($join){
                            $join->on('quote.matter_no', 'matter.matter_no');
                        })
                        ->mergeBindings($matterQuery)
                        ;
                }

                $query3
                    ->leftJoin('m_staff as sales_staff', 'matter.staff_id', '=', 'sales_staff.id')
                    ->leftJoin('m_department as department', 'matter.department_id', '=', 'department.id')
                    // 当社在庫専用案件の未処理と編集中は不要なため除外
                    ->whereRaw(
                        ' !(main.statusFlg = '.config('const.orderListStatus.statusVal.not_treated') . 
                        ' AND matter.own_stock_flg = '.config('const.flg.on') . ') '
                    )
                    ->select([
                        DB::raw('NULL AS order_id'),
                        'quote.id AS quote_id',
                        'matter.id AS matter_id',
                        DB::raw('NULL AS order_no'),
                        'main.statusFlg AS disp_status',
                        'matter.own_stock_flg',
                        'department.department_name',
                        'sales_staff.staff_name',
                        DB::raw('NULL AS maker_name'),
                        DB::raw('NULL AS supplier_name'),
                        'matter.matter_no',
                        'matter.matter_name',
                        DB::raw('NULL AS cost_total'),
                        DB::raw('NULL AS sales_total'),
                        DB::raw('NULL AS profit_total'),
                        DB::raw('NULL AS gross_profit_rate'),
                        DB::raw('NULL AS order_datetime'),
                        DB::raw('NULL AS order_staff_name'),
                        DB::raw('NULL AS product_name'),
                        DB::raw('NULL AS sales_support_comment'),
                        // DB::raw('NULL AS address'),
                        'matter.complete_flg'
                    ])
                    ;
                
                if (empty($params['reserved'])) {
                    $query3
                        ->where('main.statusFlg', '!=', config('const.orderListStatus.statusVal.reserved'));
                }
                if (empty($params['not_treated'])) {
                    $query3
                        ->where('main.statusFlg', '!=', config('const.orderListStatus.statusVal.not_treated'));
                }
            }
        }

        // UNION  MEMO:UNIONとUNION ALLの結果が一緒ならUNION ALLの方が速い
        $unionQuery = null;
        if (!is_null($query1)) {
            $unionQuery = $query1;
        }
        if (!is_null($query2)) {
            if (is_null($unionQuery)) {
                $unionQuery = $query2;
            } else {
                $unionQuery->unionAll($query2);
            }
        }
        if (!is_null($query3)) {
            if (is_null($unionQuery)) {
                $unionQuery = $query3;
            } else {
                $unionQuery->unionAll($query3);
            }
        }

        // データ取得
        $data = [];
        if (!is_null($unionQuery)) {
            $data = $unionQuery
                ->orderBy('matter_no', 'asc')
                ->orderBy('disp_status', 'asc')
                ->orderBy('order_no', 'asc')
                ->get()
                ->toArray()
                ;
        }

        return $data;
    }

    /**
     * 見積明細IDから発注済数取得
     *
     * @param  $quoteDetailId
     * @return void
     */
    public function getSumOrderQuantity($quoteDetailId)
    {
        $where = [];
        $where[] = array('od.quote_detail_id', '=', $quoteDetailId);
        $where[] = array('od.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('t_order_detail AS od')
                ->leftJoin('t_order AS order', 'order.id', '=', 'od.order_id')
                ->where($where)
                ->where(function ($query) {
                    $query
                        ->where('order.status', '=', config('const.orderStatus.val.applying'))
                        ->orWhere('order.status', '=', config('const.orderStatus.val.approving'))
                        ->orWhere('order.status', '=', config('const.orderStatus.val.approved'))
                        ->orWhere('order.status', '=', config('const.orderStatus.val.ordered'))
                        ;
                })
                ->selectRaw('
                    od.quote_detail_id AS quote_detail_id,
                    SUM(od.order_quantity) AS sum_order_quantity,
                    SUM(od.stock_quantity) AS sum_stock_quantity
                ')
                ->groupBy('od.quote_detail_id')
                ->get()
                ;

        return $data;
    }

    /**
     * 【案件詳細専用】スケジューラ-入荷
     *
     * @return void
     */
    public function getSchedulerArrivalForMatterDetail($matterNo)
    {
        $data = $this
                    ->where([
                        ['t_order.del_flg', '=', config('const.flg.off')],
                        ['t_order.matter_no', '=', $matterNo],
                        ['t_order.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company')],
                    ])
                    ->whereNotNull('t_order_detail.arrival_plan_date')
                    ->leftJoin('t_order_detail', function($join){
                        $join
                            ->on('t_order.id', '=', 't_order_detail.order_id')
                            ->where('t_order_detail.del_flg', '=', config('const.flg.off'));
                    })
                    ->leftJoin('t_quote_detail', function($join){
                        $join
                            ->on('t_order_detail.quote_detail_id', '=', 't_quote_detail.id')
                            ->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
                    })
                    ->select(
                        't_order_detail.arrival_plan_date',
                        't_quote_detail.construction_id',
                        DB::raw('
                          MIN( 
                            CASE 
                              WHEN t_order_detail.stock_quantity > t_order_detail.arrival_quantity 
                                THEN '. config('const.flg.off'). ' 
                                ELSE '. config('const.flg.on'). ' 
                            END
                          ) AS arrival_completed_flg')
                    )
                    ->groupBy('t_order_detail.arrival_plan_date', 't_quote_detail.construction_id')
                    ->get();
        return $data;
    }

     /**
     * 発注番号で取得
     * @param  $params 発注番号
     * @return  該当発注番号メーカー倉庫が存在するかどうか
     */
    public function isExistMakerWarehouse($orderNo)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('arrival_complete_flg', '=', config('const.flg.off'));
        $where[] = array('delivery_address_kbn', '=', 3);
        $where[] = array('order_no', '=', $orderNo);
        $where[] = array('status', '=',  config('const.orderStatus.val.ordered'));
       
        // データ取得
        $data = $this
            ->where($where)
            ->select('*')
            ->get();

        if(count($data) >0){
            return true;
        }else{
            return false;
        }
    }
     /**
     * 発注番号で取得
     * @param  $params 発注番号
     * @return  検索結果
     */
    public function getMakerWarehouse($orderNo)
    {
        // Where句作成
        $where = [];
        $where[] = array('o.del_flg', '=', config('const.flg.off'));
        $where[] = array('o.delivery_address_kbn', '=', 3);
        $where[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));
        $where[] = array('o.order_no', '=', $orderNo);
        $where[] = array('o.status', '=',  config('const.orderStatus.val.ordered'));
       
        // データ取得
        $data = $this
            ->from('t_order as o')
            ->leftJoin('t_order_detail as od', function($join){
                $join
                    ->on('o.id', '=', 'od.order_id')
                    ->where('od.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_product as p', function($join){
                $join
                    ->on('p.id', '=', 'od.product_id')
                    ->where('p.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('t_matter as m', function($join){
                $join
                    ->on('m.matter_no', '=', 'o.matter_no')
                    ->where('m.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_warehouse as w', function($join){
                $join
                    ->on('w.id', '=', 'o.delivery_address_id')
                    ->where('w.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('t_quote as q', function($join){
                $join
                    ->on('q.quote_no', '=', 'o.quote_no')
                    ->where('q.del_flg', '=', config('const.flg.off'));
            })
            ->where($where)
            ->selectRaw('
                0 as check_box,
                m.id as matter_id,
                q.id as quote_id,
                o.id as order_id,
                o.order_no,
                o.matter_no,
                o.own_stock_flg,
                o.delivery_address_kbn,
                o.delivery_address_id,
                o.status,
                od.id as order_detail_id,
                od.quote_detail_id,
                od.product_id,
                od.product_code,
                od.product_name,
                od.model,
                od.stock_quantity as quantity,
                od.arrival_quantity,
                od.regular_price,
                od.sales_total,
                od.cost_unit_price as price,
                p.draft_flg,
                p.draft_flg,
                w.zipcode,
                w.pref,
                w.address1,
                w.address2,
                w.latitude_jp,
                w.longitude_jp,
                w.latitude_world,
                w.longitude_world
            ')
            ->get();

        return $data;
    }

    /**
     * 案件番号を元に未入荷の発注の存在確認
     *
     * @param string $matterNo 案件番号
     * @return boolean true:存在する false:存在しない
     */
    public function hasNotArrivalByMatter($matterNo) {
        $where = [];
        $where[] = array('t_order.matter_no', '=', $matterNo);
        $where[] = array('t_order.status', '=', config('const.orderStatus.val.ordered'));
        $where[] = array('t_order.del_flg', '=', config('const.flg.off'));
        // $where[] = array('t_order_detail.product_code', '!=', '');
        // $where[] = array('t_order_detail.arrival_quantity', '=', 0);

        // 存在チェック
        $result = $this
            ->leftJoin('t_order_detail', 't_order.id', '=', 't_order_detail.order_id')
            ->where($where)
            // ->whereNotNull('t_order_detail.product_code')
            ->whereRaw('t_order_detail.stock_quantity > COALESCE(t_order_detail.arrival_quantity,0)')
            ->exists();
        
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
                'cost_total'        => 'nullable|numeric|between:-999999999,999999999',
                'sales_total'       => 'nullable|numeric|between:-999999999,999999999',
                'profit_total'      => 'nullable|numeric|between:-999999999,999999999',
            ],
            [
                'cost_total.between'        => '仕入総額が、' .  config('message.error.exceeded_length'),
                'sales_total.between'       => '販売総額が、' .  config('message.error.exceeded_length'),
                'profit_total.between'      => '粗利が、' .      config('message.error.exceeded_length'),
            ]
        );

        if ($validator->fails()) {
            throw new ValidationException($validator->errors());
        }
    }
}