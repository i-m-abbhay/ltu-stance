<?php

namespace App\Models;

use App\Exceptions\ValidationException;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;
use Validator;
use App\Libs\Common;

/**
 * 見積版
 */
class QuoteVersion extends Model
{
    // テーブル名
    protected $table = 't_quote_version';
    public $timestamps = false;
    
    protected $fillable = [
        'id',                           // ID
        'quote_no',                     // 見積番号
        'quote_version',                // 見積版
        'caption',                      // 見出し
        'department_id',                // 担当部門ID
        'staff_id',                     // 担当者ID
        'quote_create_date',            // 見積作成日
        'quote_limit_date',             // 見積提出期限
        'quote_enabled_limit_date',     // 見積有効期限
        'payment_condition',            // 支払い条件
        'cost_total',                   // 仕入総計
        'sales_total',                  // 売上総計
        'profit_total',                 // 粗利総計
        'tax_rate',                     // 税率
        'sales_support_comment',        // 営業支援コメント
        'approval_comment',             // 稟議用コメント
        'customer_comment',             // 顧客向けコメント
        'print_date',                   // 印刷日
        'status',                       // 見積状況
        'del_flg',                      // 削除フラグ
        'created_user',                 // 作成ユーザ
        'created_at',                   // 作成日時
        'update_user',                  // 更新ユーザ
        'update_at',                    // 更新日時
    ];

    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句作成
        $where = [];

        $where[] = array('quote.del_flg', '=', config('const.flg.off'));
        // 得意先ID
        if (!empty($params['customer_id'])) {
            $where[] = array('matter.customer_id', '=', $params['customer_id']);
        }
        // 案件番号
        if (!empty($params['matter_no'])) {
            $where[] = array('quote.matter_no', '=', $params['matter_no']);
        }
        // 見積番号
        if (!empty($params['quote_no'])) {
            // $where[] = array('quote_version.quote_no', '=', $params['quote_no']);
            $where[] = array('quote_version.quote_no', 'LIKE', '%'.$params['quote_no'].'%');
        }
        // 得意先別単価の使用(radioなので0 or 1)
        $where[] = array('quote.special_flg', '=', $params['special_flg']);
        // 部門ID
        if (!empty($params['department_id'])) {
            $where[] = array('quote_version.department_id', '=', $params['department_id']);
        }
        // 担当者ID
        if (!empty($params['staff_id'])) {
            $where[] = array('quote_version.staff_id', '=', $params['staff_id']);
        }
        // 見積依頼日(FROM-TO)
        if (!empty($params['create_date_from'])) {
            $where[] = array('quote_version.update_at', '>=', $params['create_date_from']);
        }
        if (!empty($params['create_date_to'])) {
            $where[] = array('quote_version.update_at', '<', date('Y/m/d', strtotime($params['create_date_to']. ' +1 day')));
        }

        $quoteDetailQuery = DB::table('t_quote_detail')
                            ->select(
                                'quote_no',
                                'quote_version',
                                DB::raw('JSON_ARRAYAGG(construction_id) AS quote_item')
                            )
                            ->where([
                                ['del_flg', config('const.flg.off')],
                                ['depth', config('const.quoteConstructionInfo.depth')],
                                ['add_flg', config('const.flg.off')],
                            ])
                            ->groupBy('quote_no', 'quote_version')
                            ;

        $data = $this
                    ->from('t_quote_version as quote_version')
                    ->join('t_quote as quote' , function($join){
                        $join
                            ->on('quote_version.quote_no', '=', 'quote.quote_no')
                            ->where('quote.own_stock_flg', '=', config('const.flg.off'));
                    })
                    ->leftjoin(DB::raw('('.$quoteDetailQuery->toSql().') as quote_detail'), function($join){
                        $join
                            ->on('quote_version.quote_no', 'quote_detail.quote_no')
                            ->on('quote_version.quote_version', 'quote_detail.quote_version')
                            ;
                    })
                    ->mergeBindings($quoteDetailQuery)
                    ->join('t_matter as matter', function ($join) {
                        $join->on('quote.matter_no', '=', 'matter.matter_no')
                            ->where('matter.use_sales_flg', '=', config('const.flg.off'));  // 売上利用フラグ
                    })
                    ->leftJoin('m_customer as customer', 'matter.customer_id', '=', 'customer.id')
                    // ->join('m_customer as customer', function ($join) {
                    //     $join->on('matter.customer_id', '=', 'customer.id')
                    //         ->where('customer.use_sales_flg', '=', config('const.flg.off'));  // 売上利用フラグ
                    // })
                    ->leftJoin('m_department as depart', 'quote_version.department_id', '=', 'depart.id')
                    ->leftJoin('m_staff as staff', 'quote_version.staff_id', '=', 'staff.id')
                    ->where($where)
                    // 見積項目の検索条件が有効な場合に実行
                    ->when(!empty($params['quote_item']), function($query) use($params) {
                        return $query
                            ->whereRaw(
                                'JSON_CONTAINS(quote_detail.quote_item, ?)',
                                [$params['quote_item']]
                            );
                    })
                    ->select(
                        'quote_version.id',
                        'quote_version.quote_version',
                        DB::raw('FORMAT(quote_version.sales_total, 0) AS sales_total'),
                        DB::raw('FORMAT(quote_version.profit_total, 0) AS profit_total'),
                        DB::raw('CONCAT(TRUNCATE(quote_version.profit_total / quote_version.sales_total * 100, 2), \'%\') AS profit_per'),
                        DB::raw('DATE_FORMAT(quote_version.update_at, \'%Y/%m/%d\') AS create_date'),
                        'quote_version.quote_no',
                        'quote_version.status',
                        'quote.id AS quote_id',
                        DB::raw('
                            CASE WHEN quote_detail.quote_item IS NULL
                              THEN JSON_ARRAY()
                              ELSE quote_detail.quote_item
                            END AS quote_item
                        '),
                        'matter.id AS matter_id',
                        'matter.matter_no',
                        'matter.matter_name',
                        'depart.department_name',
                        'staff.staff_name'
                    )
                    ->orderBy('quote_no', 'asc')
                    ->orderBy('quote_version', 'asc')
                    ->get()
                ;
        return $data;

    }

    /**
     * 見積番号に紐づく版データ取得
     *
     * @param string $quoteNo
     * @return void
     */
    public function getDataList($quoteNo) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_version.quote_no', '=', $quoteNo);
        // $where[] = array('t_quote_version.del_flg', '=', config('const.flg.off')); 　// 画面で削除フラグを見る

        // データ取得
        $data = $this
                ->leftJoin('t_approval_header' , function($join){
                    $join
                        ->on('t_approval_header.process_id', '=', 't_quote_version.id')
                        ->where('t_approval_header.process_kbn', '=', config('const.approvalProcessKbn.quote'))
                        ->where('t_approval_header.past_flg', '=', config('const.flg.off'));
                })
                ->select([
                    't_quote_version.id AS quote_version_id',
                    't_quote_version.quote_version',
                    't_quote_version.caption',
                    't_quote_version.department_id',
                    't_quote_version.staff_id',
                    't_quote_version.quote_create_date',
                    't_quote_version.quote_limit_date',
                    't_quote_version.quote_enabled_limit_date',
                    't_quote_version.payment_condition',
                    't_quote_version.cost_total',
                    't_quote_version.sales_total',
                    't_quote_version.profit_total',
                    't_quote_version.tax_rate',
                    't_quote_version.sales_support_comment',
                    't_quote_version.print_date',
                    't_quote_version.approval_comment',
                    't_quote_version.customer_comment',
                    't_quote_version.status',
                    't_quote_version.del_flg',
                    DB::raw('t_approval_header.status AS approval_status'),
                ])
                ->where($where)
                ->orderBy('t_quote_version.quote_version', 'desc')
                ->get()
                ;

        return $data;
    }

    /**
     * 見積版データ存在確認
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function isExist($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('quote_no', '=', $quoteNo);
        $where[] = array('quote_version', '=', $quoteVersion);

        // データ取得
        $data = $this
                ->where($where)
                ->count()
                ;

        return $data > 0;
    }
    
    /**
     * 登録
     * @param array $params
     * @return 見積版ID
     */
    public function add(array $params) {
        try {
            $items = [];
            $items['quote_no'] = $params['quote_no'];
            $items['quote_version'] = $params['quote_version'];
            $items['caption'] = $params['caption'];
            $items['department_id'] = $params['department_id'];
            $items['staff_id'] = $params['staff_id'];
            if (!empty($params['quote_create_date'])) {
                $items['quote_create_date'] = $params['quote_create_date'];
            }
            if (!empty($params['quote_limit_date'])) {
                $items['quote_limit_date'] = $params['quote_limit_date'];
            }
            if (!empty($params['quote_enabled_limit_date'])) {
                $items['quote_enabled_limit_date'] = $params['quote_enabled_limit_date'];
            }
            if (!empty($params['payment_condition'])) {
                $items['payment_condition'] = $params['payment_condition'];
            }
            if (isset($params['cost_total'])) {
                $items['cost_total'] = $params['cost_total'];
            }
            if (isset($params['sales_total'])) {
                $items['sales_total'] = $params['sales_total'];
            }
            if (isset($params['profit_total'])) {
                $items['profit_total'] = $params['profit_total'];
            }
            if (isset($params['tax_rate'])) {
                $items['tax_rate'] = $params['tax_rate'];
            }
            if (!empty($params['sales_support_comment'])) {
                $items['sales_support_comment'] = $params['sales_support_comment'];
            }
            if (!empty($params['approval_comment'])) {
                $items['approval_comment'] = $params['approval_comment'];
            }
            if (!empty($params['customer_comment'])) {
                $items['customer_comment'] = $params['customer_comment'];
            }
            if (isset($params['status'])) {
                $items['status'] = $params['status'];
            } else {
                $items['status'] = config('const.quoteVersionStatus.val.editing');
            }
            $items['del_flg'] = config('const.flg.off');
            $items['created_user'] = Auth::user()->id;
            $items['created_at'] = Carbon::now();
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $this->validation($items);

            // 登録
            $rtn = $this->insertGetId($items);
            
            return $rtn;
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
            $LogUtil = new LogUtil();

            // Where句作成
            $where = [];
            if (isset($params['id'])) {
                $where[] = array('id', '=', $params['id']);
                $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));
            }else {
                $where[] = array('quote_no', '=', $params['quote_no']);
                $where[] = array('quote_version', '=', $params['quote_version']);
                $LogUtil->putByWhere($this->table, $where, config('const.logKbn.update'));
            }

            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'quote_create_date':
                        case 'quote_limit_date':
                            $items[$colName] = $params[$colName];
                            // 日付が空白ならnullを代入する
                            if ($params[$colName] == "") {
                                $items[$colName] = null;
                            }
                            break;
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

            $this->validation($items);

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
     * 明細を元に金額更新
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return void
     */
    public function updateTotalKng($quoteNo, $quoteVersion) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $logWhere = [];
            $logWhere[] = array('quote_no', '=', $quoteNo);
            $logWhere[] = array('quote_version', '=', $quoteVersion);
            $LogUtil = new LogUtil();

            // Where句作成
            $subWhere = [];
            $subWhere[] = array('t_quote_detail.quote_no', '=', $quoteNo);
            $subWhere[] = array('t_quote_detail.quote_version', '=', $quoteVersion);
            $subWhere[] = array('t_quote_detail.depth', '=', config('const.quoteConstructionInfo.depth'));
            $subWhere[] = array('t_quote_detail.del_flg', '=', config('const.flg.off'));

            // 見積明細の内、工事区分階層の金額をサマリ
            $subQuery = $this->from('t_quote_detail')
                        ->where($subWhere)
                        ->groupBy('t_quote_detail.quote_no', 't_quote_detail.quote_version')
                        ->select([
                            't_quote_detail.quote_no',
                            't_quote_detail.quote_version',
                            DB::raw('SUM(t_quote_detail.cost_total) AS cost_total'),
                            DB::raw('SUM(t_quote_detail.sales_total) AS sales_total')
                        ])
                        ->get()
                        ;
            
            // 更新
            if (count($subQuery) > 0) {
                $LogUtil->putByWhere($this->table, $logWhere, config('const.logKbn.update'));
            
                foreach ($subQuery as $key => $record) {
                    $updateWhere = [];
                    $updateWhere[] = array('t_quote_version.quote_no', '=', $record['quote_no']);
                    $updateWhere[] = array('t_quote_version.quote_version', '=', $record['quote_version']);
                    $record['profit_total'] = Common::nullorBlankToZero($record['sales_total']) - Common::nullorBlankToZero($record['cost_total']);

                    $updateCnt = $this
                                ->where($updateWhere)
                                ->update([
                                    't_quote_version.cost_total' => Common::nullorBlankToZero($record['cost_total']),
                                    't_quote_version.sales_total' => Common::nullorBlankToZero($record['sales_total']),
                                    't_quote_version.profit_total' => Common::nullorBlankToZero($record['profit_total']),
                                    't_quote_version.update_user' => DB::raw(Auth::user()->id),
                                    't_quote_version.update_at' => DB::raw("'".Carbon::now()."'"),
                                ]);
                }
            }

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 見積番号と版番号から見積版データを取得
     *
     * @param string $quoteNo 見積番号
     * @param int $quoteVersion 見積版
     * @return int 見積版データ
     */
    public function getByVer($quoteNo, $quoteVersion) {
        // Where句作成
        $where = [];
        $where[] = array('quote_no', '=', $quoteNo);
        $where[] = array('quote_version', '=', $quoteVersion);

        // データ取得
        $data = $this
                ->where($where)
                ->first()
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
     * 論理削除
     *
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function deleteByQuoteNo($quoteNo) {
        try {
            // 条件
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);
            $where[] = array('del_flg', '=', config('const.flg.off'));

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.soft_delete'));

            // 更新内容
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                    ->where($where)
                    ->update($items);

        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 物理削除
     *
     * @param string $quoteNo 見積番号
     * @return void
     */
    public function physicalDeleteByQuoteNo($quoteNo) {
        try {
            // 条件
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            // 更新
            $result = $this
                    ->where($where)
                    ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 見積書用データ取得
     *
     * @param string $id 見積版ID
     * @return int 見積版データ
     */
    public function getQuoteReportDataById($id) {
        // Where句作成
        $where = [];
        $where[] = array('qv.id', '=', $id);
        $where[] = array('qv.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->from('t_quote_version AS qv')
                ->leftJoin('t_quote AS q', 'qv.quote_no', 'q.quote_no')
                ->leftJoin('t_matter AS m', 'q.matter_no', 'm.matter_no')
                ->leftJoin('m_address AS a', 'm.address_id', 'a.id')
                ->leftJoin('m_customer AS c', 'm.customer_id', 'c.id')
                ->leftJoin('m_general AS g_qv', function($query){
                    $query
                        ->on('qv.payment_condition', 'g_qv.value_code')
                        ->where('g_qv.category_code', config('const.general.paycon'));
                })
                ->leftJoin('m_general as g_c_juridical', function($join) {
                    $join->on('c.juridical_code', '=', 'g_c_juridical.value_code')
                         ->where('g_c_juridical.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftJoin('m_general AS g_c_tax', function($query){
                    $query
                        ->on('c.tax_rounding', 'g_c_tax.value_code')
                        ->where('g_c_tax.category_code', config('const.general.taxrounding'));
                })
                ->where($where)
                ->select([
                    'qv.quote_no',
                    'qv.quote_version',
                    DB::raw('DATE_FORMAT(qv.quote_create_date, \'%Y年%c月%e日\') AS quote_create_date'),
                    'quote_enabled_limit_date',
                    'qv.customer_comment',
                    'qv.staff_id',
                    'qv.tax_rate',
                    'qv.status',
                    'q.construction_period',
                    'q.construction_outline',
                    DB::raw('
                        CASE WHEN q.quote_report_to IS NULL OR q.quote_report_to = \'\'
                          THEN
                            CONCAT(
                                COALESCE(g_c_juridical.value_text_2, \'\'),
                                COALESCE(c.customer_name, \'\'),
                                COALESCE(g_c_juridical.value_text_3, \'\'),
                                \' \',
                                COALESCE(c.honorific, \'\')
                            )
                          ELSE
                            q.quote_report_to
                        END AS customer_name'),
                    'c.honorific',
                    'm.owner_name',
                    'a.address1',
                    'a.address2',
                    'g_qv.value_text_1 AS payment_condition',
                    'g_c_tax.value_code AS tax_rounding'
                ])
                ->first()
                ;

        return $data;
    }    
    
    /**
     * 見積0版取得 (在庫引当)
     *
     * @return void
     */
    public function getVersionZeroByMatter($params) {
        // Where句作成
        $where = [];
        $where[] = array('version.del_flg', '=', config('const.flg.off'));
        $where[] = array('version.quote_version', '=', config('const.flg.off'));
        $where[] = array('detail.layer_flg', '=', config('const.flg.off'));

        if (!empty($params['customer_name'])) {
            $where[] = array('cust.customer_name', 'LIKE', '%'.$params['customer_name'].'%');
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('matter.matter_no', 'LIKE', '%'.$params['matter_no'].'%');
        }
        if (!empty($params['matter_name'])) {
            $where[] = array('matter.matter_name', 'LIKE', '%'.$params['matter_name'].'%');
        }
        if (!empty($params['department_name'])) {
            $where[] = array('dep.department_name', 'LIKE', '%'.$params['department_name'].'%');
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('staff.staff_name', 'LIKE', '%'.$params['staff_name'].'%');
        }

        $Construction = new Construction();
        $constructionData = $Construction->getAddFlgData();

        // データ取得
        $data = $this
                ->from('t_quote_version as version')
                ->where($where)
                ->leftJoin('t_quote_detail as detail', function($join) use($constructionData) {
                    $join
                        ->on('detail.quote_no', '=', 'version.quote_no')
                        ->where('detail.quote_version', '=', config('const.flg.off'))
                        ->where('detail.construction_id', '<>', $constructionData->id)
                        ;
                })
                ->leftJoin('t_quote as quote', 'quote.quote_no', '=', 'version.quote_no')
                ->leftJoin('t_matter as matter', 'matter.matter_no', '=', 'quote.matter_no')
                ->leftJoin('m_customer as cust', 'cust.id', '=', 'matter.customer_id')
                ->leftJoin('m_department as dep', 'dep.id', '=', 'version.department_id')
                ->leftJoin('m_staff as staff', 'staff.id', '=', 'version.staff_id')
                ->leftJoin('m_construction as construct', 'construct.id', '=', 'detail.construction_id')
                ->leftJoin('t_order_detail as order_detail', 'order_detail.quote_detail_id', '=', 'detail.id')
                ->leftjoin('m_product as product', function($join) {
                    $join
                        ->on('product.id', '=', 'detail.product_id')
                        ->where('product.intangible_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftJoin('m_supplier as maker', 'maker.id', '=', 'detail.maker_id')
                ->leftJoin('m_supplier as supplier', 'supplier.id', '=', 'detail.supplier_id')
                ->leftJoin('m_general as maker_general', function($join) {
                    $join->on('maker.juridical_code', '=', 'maker_general.value_code')
                         ->where('maker_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftJoin('m_general as supplier_general', function($join) {
                    $join->on('supplier.juridical_code', '=', 'supplier_general.value_code')
                         ->where('supplier_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftJoin('t_shipment_detail as shipment_detail', function($join) {
                    $join->on('detail.id', '=', 'shipment_detail.quote_detail_id')
                         ->where('shipment_detail.del_flg', '=', config('const.flg.off'))
                         ;
                })
                ->leftjoin('t_quote_detail AS parent_detail', 'detail.parent_quote_detail_id', '=', 'parent_detail.id')
                ->selectRaw('
                    detail.id as id,
                    detail.tree_path,
                    detail.quote_version,
                    quote.id as quote_id,
                    detail.parent_quote_detail_id,
                    matter.id as matter_id,
                    matter.customer_id,
                    quote.quote_no,
                    quote.matter_no,
                    construct.id AS construction_id,
                    construct.construction_name as construction_name,
                    product.id as product_id,
                    parent_detail.product_name as parent_name,
                    product.product_code as product_code,
                    product.product_name as product_name,
                    detail.model as model,
                    product.min_quantity,
                    product.order_lot_quantity,
                    CONCAT(COALESCE(maker_general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(maker_general.value_text_3, \'\')) as maker_name,
                    CONCAT(COALESCE(supplier_general.value_text_2, \'\'), COALESCE(supplier.supplier_name, \'\'), COALESCE(supplier_general.value_text_3, \'\')) as supplier_name,
                    detail.quote_quantity as quote_quantity,
                    detail.stock_quantity as stock_quantity,
                    detail.unit as unit,
                    detail.stock_unit as stock_unit,
                    CASE
                        WHEN shipment_detail.id IS NULL
                            THEN \''. config('const.flg.off'). '\'
                        ELSE \''. config('const.flg.on'). '\'
                    END AS status,

                    CASE
                        WHEN parent_detail.set_flg ='. config('const.flg.on'). '
                            THEN '. config('const.flg.on'). '
                        ELSE '. config('const.flg.off'). '
                    END AS set_flg  
                ')
                ->whereNotNull('product.id')
                ->orderBy('construct.id')
                ->distinct()
                ->get()
                ;
        
        return $data;
    }

    /**
     * IDで取得
     * @param int $id 見積版ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->where(['id' => $id])
                ->first()
                ;
        return $data;
    }


    /**
     * 担当者/担当部門変更
     *
     * @param  $params
     * @return void
     */
    public function updateStaffByQuoteNo($params) 
    {
        $result = false;
        try {
            $result = $this
                    ->where('quote_no', $params['quote_no'])
                    ->update([
                        'department_id' => $params['department_id'],
                        'staff_id' => $params['staff_id'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 担当者/担当部門変更
     *
     * @param  $quoteNo         見積番号
     * @param  $departmentId    部門ID
     * @param  $staffId         担当者ID
     * @return void
     */
    public function updateDepartmentStaffByQuoteNo($quoteNo, $departmentId = null, $staffId = null) 
    {
        $result = false;
        try {
            // Where句作成
            $where = [];
            $where[] = array('quote_no', '=', $quoteNo);

            // データ取得
            $data = $this
                    ->where($where)
                    ->get()
                    ;
            
            $updateData = [];
            if($departmentId !== null){
                $updateData['department_id'] = $departmentId; 
            }
            if($staffId !== null){
                $updateData['staff_id'] = $staffId; 
            }

            foreach($data as $row){
                $updateData['id'] = $row->id;
                $result = $this->updateById($updateData);
            }
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

    /**
     * [案件詳細専用]スケジューラ-見積
     *
     * @return void
     */
    public function getSchedulerQuoteForMatterDetail($quoteNo){
        $subQuoteDetail = DB::table('t_quote_detail')
                            ->where([
                                ['del_flg', '=', config('const.flg.off')],
                                ['quote_no', '=', $quoteNo],
                                ['depth', '=', 0],
                            ])
                            ->select(
                                'quote_no',
                                'quote_version',
                                DB::raw('JSON_ARRAYAGG(construction_id) AS construction_id_list')
                            )
                            ->groupBy(
                                'quote_no', 'quote_version'
                            );
        $data = $this
                    ->leftJoin(DB::raw('('. $subQuoteDetail->toSql(). ') AS t_quote_detail'), function($join){
                        $join
                            ->on('t_quote_version.quote_no', '=', 't_quote_detail.quote_no')
                            ->on('t_quote_version.quote_version', '=', 't_quote_detail.quote_version');
                    })
                    ->mergeBindings($subQuoteDetail)
                    ->where([
                        ['t_quote_version.del_flg', '=', config('const.flg.off')],
                        ['t_quote_version.quote_no', '=', $quoteNo],
                        ['t_quote_version.quote_version', '!=', config('const.quoteCompVersion.number')],
                    ])
                    ->whereNotNull('t_quote_version.quote_limit_date')
                    ->select(
                        't_quote_version.quote_no',
                        't_quote_version.quote_version',
                        't_quote_version.quote_limit_date',
                        't_quote_version.print_date',
                        't_quote_version.status',
                        't_quote_detail.construction_id_list'
                    )
                    ->get()
                    ;
        return $data;
    }

}