<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Models\NumberManage;
use App\Models\Customer;
use App\Libs\LogUtil;
use App\Libs\SystemUtil;

/**
 * 案件テーブル
 */
class Matter extends Model
{
    // テーブル名
    protected $table = 't_matter';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'matter_no',
        'matter_name',
        'matter_create_date',
        'customer_id',
        'owner_name',
        'architecture_type',
        'address_id',
        'department_id',
        'staff_id',
        'construction_date',
        'ridgepole_raising_date',
        'delivery_expected_date',
        'complete_flg',
        'from_warehouse_id1',
        'from_warehouse_id2',
        'own_stock_flg',
        'use_sales_flg',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
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

        $where[] = array('matter.del_flg', '=', config('const.flg.off'));
        // 当社在庫品フラグ
        $where[] = array('matter.own_stock_flg', '=', config('const.flg.off'));
        // 売上利用フラグ
        $where[] = array('matter.use_sales_flg', '=', config('const.flg.off'));

        // 得意先ID
        if (!empty($params['customer_id'])) {
            $where[] = array('matter.customer_id', '=', $params['customer_id']);
        }
        // 案件ID
        if (!empty($params['matter_no'])) {
            $where[] = array('matter.matter_no', '=', $params['matter_no']);
        }
        // 部門ID
        if (!empty($params['department_id'])) {
            $where[] = array('matter.department_id', '=', $params['department_id']);
        }
        // 担当者ID
        if (!empty($params['staff_id'])) {
            $where[] = array('matter.staff_id', '=', $params['staff_id']);
        }
        // 案件登録日(FROM-TO)
        if (!empty($params['matter_create_date_from'])) {
            $where[] = array('matter.matter_create_date', '>=', $params['matter_create_date_from']);
        }
        if (!empty($params['matter_create_date_to'])) {
            $where[] = array('matter.matter_create_date', '<', date('Y/m/d', strtotime($params['matter_create_date_to'] . ' +1 day')));
        }

        // 一覧系のLIMIT値が決まり次第、LIMITを設定する
        $data = $this
            ->where($where)
            ->when(!empty($params['address']), function ($query) use ($params) {
                return $query
                    ->whereRaw(
                        'CONCAT(COALESCE(address.address1, \'\'), COALESCE(address.address2, \'\')) LIKE ?',
                        ['%' . $params['address'] . '%']
                    );
            })
            ->from('t_matter as matter')
            ->leftJoin('m_customer as customer', 'matter.customer_id', '=', 'customer.id')
            // ->join('m_customer as customer', function ($join) {
            //     $join->on('matter.customer_id', '=', 'customer.id')
            //         ->where('customer.use_sales_flg', '=', config('const.flg.off'));  // 売上利用フラグ
            // })
            ->leftJoin('m_general as general', function ($join) {
                $join->on('customer.juridical_code', '=', 'general.value_code')
                    ->where('general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_department as depart', 'matter.department_id', '=', 'depart.id')
            ->leftJoin('m_staff as staff', 'matter.staff_id', '=', 'staff.id')
            ->leftJoin('m_address as address', 'matter.address_id', '=', 'address.id')
            ->select([
                'matter.id',
                'matter.matter_no',
                'matter.matter_name',
                'matter.matter_create_date',
                DB::raw('CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(customer.customer_name, \'\'), COALESCE(general.value_text_3, \'\')) as customer_name'),
                'depart.department_name',
                'staff.staff_name',
            ])
            ->selectRaw('CONCAT(COALESCE(address.address1, \'\'), COALESCE(address.address2, \'\')) as address')
            ->get();
        return $data;
    }

    /**
     * 案件一覧 進捗状況取得[見積依頼]
     *
     * @param int $matterIDs
     * @return void
     */
    public function getQuoteRequestStatus($matterIDs)
    {
        $where = [];
        $where[] = array('matter.del_flg', '=', config('const.flg.off'));

        $joinWhere['quote_request'][] = array('quote_request.del_flg', '=', config('const.flg.off'));
        $data = $this
            ->where($where)
            ->whereIn('matter.id', $matterIDs)
            ->from('t_matter as matter')
            ->leftJoin('t_quote_request as quote_request', function ($join) use ($joinWhere) {
                $join
                    ->on('matter.matter_no', '=', 'quote_request.matter_no')
                    ->where($joinWhere['quote_request']);
            })
            ->selectRaw('
                        matter.id,
                        matter.matter_no,
                        quote_request.id AS quote_request_id,
                        CASE 
                          WHEN quote_request.id IS NULL 
                            THEN \'' . config('const.matterListProgress.status.not_implemented') . '\' 
                          WHEN quote_request.status = ' . config('const.quoteRequestStatus.val.editing') . '
                            THEN \'' . config('const.matterListProgress.status.editing') . '\' 
                          WHEN quote_request.status = ' . config('const.quoteRequestStatus.val.requested') . '
                            THEN \'' . config('const.matterListProgress.status.requested') . '\' 
                        END AS quote_request_status 
                    ')
            ->get();
        return $data;
    }

    /**
     * 案件一覧 進捗状況取得[見積]
     * 　未実施：見積なし
     * 　申請中：申請中の見積版が存在する
     * 　承認済：申請中の見積版が存在せず、承認済の見積版が存在する
     * 　編集中：上記以外
     *
     * @param int $matterIDs
     * @return void
     */
    public function getQuoteStatus($matterIDs)
    {
        $where = [];
        $where[] = array('matter.del_flg', '=', config('const.flg.off'));

        // 見積（存在チェック用）
        $quoteQuery = DB::table('t_quote')
            ->whereraw('del_flg = ' . config('const.flg.off'));

        // // (見積版)全てのレコードが作成中であるか
        // $quoteVerEditQuery = DB::table('t_quote_version')
        //     ->select([
        //         'quote_no',
        //     ])
        //     ->whereraw('del_flg = ' . config('const.flg.off'))
        //     ->groupBy('quote_no')
        //     ->havingraw('max(status) = ' . config('const.quoteVersionStatus.val.editing'));

        // (見積版)申請中を含んでいるか
        $quoteVerApplyingQuery = DB::table('t_quote_version')
            ->select([
                'quote_no',
            ])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.quoteVersionStatus.val.applying')
            )
            ->groupBy('quote_no');

        // (見積版)承認済みを含んでいるか
        $quoteVerApprovedQuery = DB::table('t_quote_version')
            ->select([
                'quote_no',
            ])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.quoteVersionStatus.val.approved')
            )
            ->groupBy('quote_no');

        $data = $this
            ->from('t_matter as matter')
            ->leftjoin(
                DB::raw('(' . $quoteQuery->toSql() . ') as quote'),
                'matter.matter_no',
                '=',
                'quote.matter_no'
            )
            // ->leftjoin(
            //     DB::raw('(' . $quoteVerEditQuery->toSql() . ') as quote_edit'),
            //     'quote.quote_no',
            //     '=',
            //     'quote_edit.quote_no'
            // )
            ->leftjoin(
                DB::raw('(' . $quoteVerApplyingQuery->toSql() . ') as quote_applying'),
                'quote.quote_no',
                '=',
                'quote_applying.quote_no'
            )
            ->leftjoin(
                DB::raw('(' . $quoteVerApprovedQuery->toSql() . ') as quote_approved'),
                'quote.quote_no',
                '=',
                'quote_approved.quote_no'
            )
            ->where($where)
            ->whereIn('matter.id', $matterIDs)
            ->selectRaw('
                        matter.id,
                        matter.matter_no,
                        CASE 
                          WHEN quote.matter_no IS NULL 
                            THEN \'' . config('const.matterListProgress.status.not_implemented') . '\' 
                          WHEN quote.status = ' . config('const.quoteStatus.val.incomplete') . ' 
                          AND quote_applying.quote_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.applying') . '\' 
                          WHEN quote.status = ' . config('const.quoteStatus.val.incomplete') . ' 
                          AND quote_applying.quote_no IS NULL 
                          AND quote_approved.quote_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.approved') . '\' 
                          WHEN quote.status = ' . config('const.quoteStatus.val.complete') . ' 
                            THEN \'' . config('const.matterListProgress.status.complete') . '\' 
                          ELSE \'' . config('const.matterListProgress.status.editing') . '\' 
                          END AS quote_status 
                    ')
            ->get();
        return $data;
    }

    /**
     * 案件一覧 進捗状況取得[発注]
     *
     * @param int $matterIDs
     * @return void
     */
    public function getOrderStatus($matterIDs)
    {
        $where = [];
        $where[] = array('matter.del_flg', '=', config('const.flg.off'));

        // 見積（見積版にアクセスする用）
        $quoteQuery = DB::table('t_quote')
            ->whereraw('del_flg = ' . config('const.flg.off'));

        // 見積明細
        $quoteDetailQuery = DB::table('t_quote_detail')
            ->select(['quote_no'])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and received_order_flg = ' . config('const.flg.on')
            )
            ->groupBy('quote_no');
        // 発注（存在チェック用）
        $oHasQuery = DB::table('t_order')
            ->select(['matter_no'])
            ->whereraw('del_flg = ' . config('const.flg.off'))
            ->groupBy('matter_no');
        // 発注（編集中チェック用）
        $oEditQuery = DB::table('t_order')
            ->select(['matter_no'])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.orderStatus.val.not_ordering')
            )
            ->groupBy('matter_no');
        // 発注（申請中チェック用）
        $oApplyingQuery = DB::table('t_order')
            ->select(['matter_no'])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.orderStatus.val.applying')
            )
            ->groupBy('matter_no');
        // 発注（承認中チェック用）
        $oApprovingQuery = DB::table('t_order')
            ->select(['matter_no'])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.orderStatus.val.approving')
            )
            ->groupBy('matter_no');
        // 発注（承認済チェック用）
        $oApprovedQuery = DB::table('t_order')
            ->select(['matter_no'])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.orderStatus.val.approved')
            )
            ->groupBy('matter_no');
        // 発注（発注済チェック用）
        $oOrderedQuery = DB::table('t_order')
            ->select(['matter_no'])
            ->whereraw(
                'del_flg = ' . config('const.flg.off')
                    . ' and status = ' . config('const.orderStatus.val.ordered')
            )
            ->groupBy('matter_no');
        $data = $this
            ->from('t_matter as matter')
            ->leftjoin(
                DB::raw('(' . $quoteQuery->toSql() . ') as quote'),
                'matter.matter_no',
                '=',
                'quote.matter_no'
            )
            ->leftjoin(
                DB::raw('(' . $quoteDetailQuery->toSql() . ') as quote_detail'),
                'quote.quote_no',
                '=',
                'quote_detail.quote_no'
            )
            ->leftjoin(
                DB::raw('(' . $oHasQuery->toSql() . ') as o_has'),
                'matter.matter_no',
                '=',
                'o_has.matter_no'
            )
            ->leftjoin(
                DB::raw('(' . $oEditQuery->toSql() . ') as o_edit'),
                'matter.matter_no',
                '=',
                'o_edit.matter_no'
            )
            ->leftjoin(
                DB::raw('(' . $oApplyingQuery->toSql() . ') as o_applying'),
                'matter.matter_no',
                '=',
                'o_applying.matter_no'
            )
            ->leftjoin(
                DB::raw('(' . $oApprovingQuery->toSql() . ') as o_approving'),
                'matter.matter_no',
                '=',
                'o_approving.matter_no'
            )
            ->leftjoin(
                DB::raw('(' . $oApprovedQuery->toSql() . ') as o_approved'),
                'matter.matter_no',
                '=',
                'o_approved.matter_no'
            )
            ->leftjoin(
                DB::raw('(' . $oOrderedQuery->toSql() . ') as o_ordered'),
                'matter.matter_no',
                '=',
                'o_ordered.matter_no'
            )
            ->where($where)
            ->whereIn('matter.id', $matterIDs)
            ->selectRaw('
                        matter.id,
                        matter.matter_no,
                        CASE 
                          /* 未処理か？ */
                          WHEN quote_detail.quote_no IS NOT NULL AND o_has.matter_no IS NULL
                            THEN \'' . config('const.matterListProgress.status.not_treated') . '\' 
                          /* 未実施か？ */
                          WHEN o_has.matter_no IS NULL 
                            THEN \'' . config('const.matterListProgress.status.not_implemented') . '\' 
                          /* 編集中か？ */
                          WHEN o_edit.matter_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.editing') . '\' 
                          /* 申請中か？ */
                          WHEN o_applying.matter_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.applying') . '\' 
                          /* 承認中か？ */
                          WHEN o_approving.matter_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.approving') . '\' 
                          /* 承認済か？ */
                          WHEN o_approved.matter_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.approved') . '\' 
                          /* 発注済か？ */
                          WHEN o_ordered.matter_no IS NOT NULL 
                            THEN \'' . config('const.matterListProgress.status.ordered') . '\' 
                        END order_status 
                    ')
            // ->toSql()
            ->get();
        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @param int $completeFlg  完了フラグ config('const.flg.off') または config('const.flg.on')
     * @param int $ownStockFlg  当社在庫品フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @param int $useSalesFlg  売上利用フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @return void
     */
    public function getComboList($completeFlg = null, $ownStockFlg = 0, $useSalesFlg = 0)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        if (!is_null($completeFlg)) {
            $where[] = array('t_matter.complete_flg', '=', $completeFlg);
        }
        if (!is_null($ownStockFlg)) {
            $where[] = array('t_matter.own_stock_flg', '=', $ownStockFlg);
        }
        if (!is_null($useSalesFlg)) {
            $where[] = array('t_matter.use_sales_flg', '=', $useSalesFlg);
        }

        // // 得意先絞り込み
        // $customerQuery = DB::table('m_customer')
        //         ->select(
        //             'm_customer.id'
        //         );
        // if (!is_null($useSalesFlg)) {
        //     $customerQuery->where('m_customer.use_sales_flg', '=', $useSalesFlg);  // 売上利用フラグ
        // }

        // データ取得
        $data = $this
            // ->join(DB::raw('('.$customerQuery->toSql().') as customer'), function($join){
            //     $join->on('t_matter.customer_id', '=', 'customer.id');
            // })
            // ->mergeBindings($customerQuery)
            ->where($where)
            ->orderBy('t_matter.id', 'asc')
            ->select([
                't_matter.id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.customer_id',
                't_matter.owner_name'
            ])
            ->get();

        return $data;
    }
    
    /**
     * コンボボックス用データ取得
     *
     * @param int $completeFlg  完了フラグ config('const.flg.off') または config('const.flg.on')
     * @param int $ownStockFlg  当社在庫品フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @param int $useSalesFlg  売上利用フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @return void
     */
    public function getComboListThin($completeFlg = null, $ownStockFlg = 0, $useSalesFlg = 0)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        if (!is_null($completeFlg)) {
            $where[] = array('t_matter.complete_flg', '=', $completeFlg);
        }
        if (!is_null($ownStockFlg)) {
            $where[] = array('t_matter.own_stock_flg', '=', $ownStockFlg);
        }
        if (!is_null($useSalesFlg)) {
            $where[] = array('t_matter.use_sales_flg', '=', $useSalesFlg);
        }

        // // 得意先絞り込み
        // $customerQuery = DB::table('m_customer')
        //         ->select(
        //             'm_customer.id'
        //         );
        // if (!is_null($useSalesFlg)) {
        //     $customerQuery->where('m_customer.use_sales_flg', '=', $useSalesFlg);  // 売上利用フラグ
        // }

        // データ取得
        $data = $this
            // ->join(DB::raw('('.$customerQuery->toSql().') as customer'), function($join){
            //     $join->on('t_matter.customer_id', '=', 'customer.id');
            // })
            // ->mergeBindings($customerQuery)
            ->where($where)
            ->orderBy('t_matter.id', 'asc')
            ->select([
                // 't_matter.id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.customer_id',
                // 't_matter.owner_name'
            ])
            ->get();

        return $data;
    }

    /**
     * 支払予定一覧
     * コンボボックス用データ取得
     *
     * @param int $completeFlg  完了フラグ config('const.flg.off') または config('const.flg.on')
     * @param int $ownStockFlg  当社在庫品フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @param int $useSalesFlg  売上利用フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @return void
     */
    public function getRebateComboList($completeFlg = null, $ownStockFlg = 0, $useSalesFlg = 0) 
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        if (!is_null($completeFlg)) {
            $where[] = array('t_matter.complete_flg', '=', $completeFlg);
        }
        if (!is_null($ownStockFlg)) {
            $where[] = array('t_matter.own_stock_flg', '=', $ownStockFlg);
        }
        if (!is_null($useSalesFlg)) {
            $where[] = array('t_matter.use_sales_flg', '=', $useSalesFlg);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('t_matter.id', 'asc')
            ->select([
                't_matter.id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.customer_id',
                't_matter.owner_name',
                't_matter.department_id',
                't_matter.staff_id',
            ])
            ->get();

        return $data;
    }

    // /**
    //  * コンボボックス専用データ取得
    //  *
    //  * @param array $params  検索したい項目のkey-value
    //  * @return void
    //  */
    // public function getComboListByWhere($params=[])
    // {
    //     // Where句作成
    //     $where = [];

    //     // 削除フラグ
    //     if (isset($params['del_flg'])) {
    //         $where[] = array('del_flg', '=', $params['del_flg']);
    //     }else{
    //         $where[] = array('del_flg', '=', config('const.flg.off'));
    //     }

    //     // 他条件
    //     if(count($params) > 0){
    //         $searchCol = $this->getFillable();
    //         foreach($params as $colName => $value){
    //             if(in_array($colName, $searchCol)){
    //                 switch ($colName) {
    //                     case 'matter_create_date':
    //                         if (is_array($params[$colName])) {
    //                             if (isset($params[$colName]['from'])) {
    //                                 $where[] = array($colName, '>=', $params[$colName]['from']);
    //                             }
    //                             if (isset($params[$colName]['to'])) {
    //                                 $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName]['to'] . ' +1 day')));
    //                             }
    //                         }else{
    //                             $where[] = array($colName, '>=', $params[$colName]);
    //                             $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName] . ' +1 day')));
    //                         }
    //                         break;
    //                     case 'construction_date':
    //                         if (is_array($params[$colName])) {
    //                             if (isset($params[$colName]['from'])) {
    //                                 $where[] = array($colName, '>=', $params[$colName]['from']);
    //                             }
    //                             if (isset($params[$colName]['to'])) {
    //                                 $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName]['to'] . ' +1 day')));
    //                             }
    //                         }else{
    //                             $where[] = array($colName, '>=', $params[$colName]);
    //                             $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName] . ' +1 day')));
    //                         }
    //                         break;
    //                     case 'ridgepole_raising_date':
    //                         if (is_array($params[$colName])) {
    //                             if (isset($params[$colName]['from'])) {
    //                                 $where[] = array($colName, '>=', $params[$colName]['from']);
    //                             }
    //                             if (isset($params[$colName]['to'])) {
    //                                 $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName]['to'] . ' +1 day')));
    //                             }
    //                         }else{
    //                             $where[] = array($colName, '>=', $params[$colName]);
    //                             $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName] . ' +1 day')));
    //                         }
    //                         break;
    //                     case 'delivery_expected_date':
    //                         if (is_array($params[$colName])) {
    //                             if (isset($params[$colName]['from'])) {
    //                                 $where[] = array($colName, '>=', $params[$colName]['from']);
    //                             }
    //                             if (isset($params[$colName]['to'])) {
    //                                 $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName]['to'] . ' +1 day')));
    //                             }
    //                         }else{
    //                             $where[] = array($colName, '>=', $params[$colName]);
    //                             $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName] . ' +1 day')));
    //                         }
    //                         break;
    //                     case 'created_at':
    //                         if (is_array($params[$colName])) {
    //                             if (isset($params[$colName]['from'])) {
    //                                 $where[] = array($colName, '>=', $params[$colName]['from']);
    //                             }
    //                             if (isset($params[$colName]['to'])) {
    //                                 $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName]['to'] . ' +1 day')));
    //                             }
    //                         }else{
    //                             $where[] = array($colName, '>=', $params[$colName]);
    //                             $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName] . ' +1 day')));
    //                         }
    //                         break;
    //                     case 'update_at':
    //                         if (is_array($params[$colName])) {
    //                             if (isset($params[$colName]['from'])) {
    //                                 $where[] = array($colName, '>=', $params[$colName]['from']);
    //                             }
    //                             if (isset($params[$colName]['to'])) {
    //                                 $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName]['to'] . ' +1 day')));
    //                             }
    //                         }else{
    //                             $where[] = array($colName, '>=', $params[$colName]);
    //                             $where[] = array($colName, '<', date('Y/m/d', strtotime($params[$colName] . ' +1 day')));
    //                         }
    //                         break;
    //                     case 'del_flg':
    //                         break;
    //                     default:
    //                         $where[] = array($colName, '=', $params[$colName]);
    //                         break;
    //                 }
    //             }
    //         }
    //     }

    //     // データ取得
    //     $data = $this
    //         ->where($where)
    //         ->orderBy('id', 'asc')
    //         ->select([
    //             'id',
    //             'matter_no',
    //             'matter_name',
    //             'customer_id',
    //             'owner_name'
    //         ])
    //         ->get();

    //     return $data;
    // }

    /**
     * コンボボックス用データ取得
     * 得意先と施主を使用して類似案件のみを取得
     *
     * @param int $customerId 得意先ID
     * @param string $ownerName 施主名
     * @param int $completeFlg  完了フラグ config('const.flg.off') または config('const.flg.on')
     * @return void
     */
    public function getCombiComboList($customerId, $ownerName, $completeFlg = null)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.own_stock_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.use_sales_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.customer_id', '=', $customerId);
        $where[] = array('t_matter.owner_name', 'LIKE', '%' . $ownerName . '%');
        if (!is_null($completeFlg)) {
            $where[] = array('t_matter.complete_flg', '=', $completeFlg);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('id', 'desc')
            ->selectRaw('
                    MAX(t_matter.id) AS id,
                    t_matter.matter_no,
                    CONCAT(t_matter.matter_no, \':\', t_matter.matter_name) AS matter_no_name,
                    COALESCE(MAX(t_quote_request.id), \'\') AS quote_request_id
                ')
            // ->select([
            //     't_matter.id',
            //     't_matter.matter_no',
            //     DB::raw("CONCAT(t_matter.matter_no, ':', t_matter.matter_name) AS matter_no_name"),
            //     DB::raw("COALESCE(t_quote_request.id, '') AS quote_request_id")
            // ])
            ->leftjoin('t_quote_request', 't_quote_request.matter_no', 't_matter.matter_no')
            ->groupBy('t_matter.matter_no', 't_matter.matter_name')
            ->get();

        return $data;
    }

    /**
     * 施主名コンボボックス用データ取得
     *
     * @return void
     */
    public function getOwnerList()
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('owner_name', 'asc')
            ->select([
                'owner_name',
            ])
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 顧客と関連のある施主名を取得
     *
     * @return void [顧客ID] => [['owner_name' => '施主名1'], ['owner_name' => '施主名2'], ['owner_name' => '施主名3']]
     */
    public function getCustomerOwnerList()
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->select([
                'customer_id',
                'owner_name',
            ])
            ->distinct()
            ->get();
        $data = $data->mapToGroups(function ($item, $key) {
            return [$item['customer_id'] => ['owner_name' => $item['owner_name']]];
        });

        return $data;
    }

    /**
     * 見積依頼・見積が存在する案件データ取得
     *
     * @return void
     */
    public function getQuoteExistList()
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.use_sales_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->join('t_quote_request', 't_quote_request.matter_no', 't_matter.matter_no')
            ->join('t_quote', 't_quote.matter_no', 't_quote_request.matter_no')
            ->where($where)
            // ->orderBy('t_matter.id', 'asc')
            ->selectRaw('
                    t_matter.matter_no,
                    t_matter.matter_name
                ')
            // t_matter.id AS id,
            // t_matter.customer_id,
            //     t_matter.owner_name
            ->groupBy('t_matter.matter_no', 't_matter.matter_name')
            ->get();

        return $data;
    }

    /**
     * 案件番号で案件データ取得
     *
     * @param string $matterNo 案件番号
     * @return void
     */
    public function getByMatterNo($matterNo)
    {
        // Where句作成
        $where = [];
        $where[] = array('matter_no', '=', $matterNo);

        // データ取得
        $data = $this
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 見積依頼明細データ取得
     *
     * @param string $matterNo 案件番号
     * @return void
     */
    public function getQuoteRequestDetail($matterNo)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.matter_no', '=', $matterNo);
        $where[] = array('t_quote_request.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->leftjoin('t_quote_request', 't_quote_request.matter_no', 't_matter.matter_no')
            ->leftjoin('t_quote_request_detail', 't_quote_request_detail.quote_request_id', 't_quote_request.id')
            ->join('t_spec_item_detail', 't_spec_item_detail.id', 't_quote_request_detail.spec_item_detail_id')
            ->join('m_item', 'm_item.id', 't_spec_item_detail.item_id')
            ->where($where)
            ->select([
                't_quote_request_detail.seq_no',
                't_quote_request_detail.quote_request_id',
                't_quote_request_detail.quote_request_kbn',
                't_quote_request_detail.spec_item_header_id',
                't_quote_request_detail.spec_item_detail_id',
                't_quote_request_detail.input_value01',
                't_quote_request_detail.input_value02',
                't_quote_request_detail.input_value03',
                'm_item.item_type'
            ])
            ->orderBy('t_quote_request_detail.quote_request_id')
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 登録
     *
     * @param array $params
     * @return 案件ID
     */
    public function addFromQreq($params)
    {
        try {
            $items = [];
            $items['matter_no'] = $params['matter_no'];
            $items['matter_name'] = $params['matter_name'];

            $matterCreateDate = $params['matter_create_date'];
            if (empty($params['matter_create_date'])) {
                $matterCreateDate = Carbon::today();
            }
            $items['matter_create_date'] = $matterCreateDate;

            if (!empty($params['customer_id'])) {
                $items['customer_id'] = $params['customer_id'];
            }
            if (!empty($params['owner_name'])) {
                $items['owner_name'] = $params['owner_name'];
            }
            if (!empty($params['architecture_type'])) {
                $items['architecture_type'] = $params['architecture_type'];
            }
            if (!empty($params['address_id'])) {
                $items['address_id'] = $params['address_id'];
            }
            if (!empty($params['department_id'])) {
                $items['department_id'] = $params['department_id'];
            }
            if (!empty($params['staff_id'])) {
                $items['staff_id'] = $params['staff_id'];
            }
            if (!empty($params['construction_date'])) {
                $items['construction_date'] = $params['construction_date'];
            }
            if (!empty($params['ridgepole_raising_date'])) {
                $items['ridgepole_raising_date'] = $params['ridgepole_raising_date'];
            }
            if (!empty($params['delivery_expected_date'])) {
                $items['delivery_expected_date'] = $params['delivery_expected_date'];
            }

            $items['complete_flg'] = config('const.flg.off');
            $items['del_flg'] = config('const.flg.off');
            $items['created_user'] = Auth::user()->id;
            $items['created_at'] = Carbon::now();
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 登録
            $id = $this->insertGetId($items);

            return $id;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params)
    {
        $result = false;

        // 案件NO
        $matterNo = (new NumberManage())->getSeqNo(config('const.number_manage.kbn.matter'), Carbon::now()->format('Ym'));
        if ($matterNo == config('const.number_manage.result.fail')) {
            throw new \Exception(config('message.error.save'));
        }

        $Customer = Customer::find($params['customer_id']);
        // 担当部門セット
        $departmentId = $params['department_id'] ?? null;
        if (is_null($departmentId)) {
            $departmentId = $Customer['charge_department_id'];
        }

        // 担当者セット
        $staffId = $params['staff_id'] ?? null;
        if (is_null($staffId)) {
            $staffId = $Customer['charge_staff_id'];
        }

        // 案件名
        $matterName = (new SystemUtil())->createMatterName($params['owner_name'], null, $Customer['id']);

        try {
            $result = $this->insertGetId([
                'matter_no' => $matterNo,
                'matter_name' => $matterName,
                'matter_create_date' => Carbon::now(),
                'customer_id' => $params['customer_id'],
                'owner_name' => $params['owner_name'],
                'architecture_type' => $params['architecture_type'],
                'address_id' => null,
                'department_id' => $departmentId,
                'staff_id' => $staffId,
                'complete_flg' => config('const.flg.off'),
                'use_sales_flg' => isset($params['use_sales_flg']) ? $params['use_sales_flg']: config('const.flg.off'),
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now()
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
    public function updateById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $Customer = Customer::find($params['customer_id']);
            // 担当部門セット
            $departmentId = $params['department_id'] ?? null;
            if (is_null($departmentId)) {
                $departmentId = $Customer['charge_department_id'];
            }

            // 担当者セット
            $staffId = $params['staff_id'] ?? null;
            if (is_null($staffId)) {
                $staffId = $Customer['charge_staff_id'];
            }

            // 案件名
            $matterName = (new SystemUtil())->createMatterName($params['owner_name'], null, $Customer['id']);

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'matter_name' => $matterName,
                    'customer_id' => $params['customer_id'],
                    'owner_name' => $params['owner_name'],
                    'architecture_type' => $params['architecture_type'],
                    'department_id' => $departmentId,
                    'staff_id' => $staffId,
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
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateByIdEx($params){
        $userId = Auth::user()->id;
        $now = Carbon::now();

        $LogUtil = new LogUtil();
        try {
            $where = [];

            // ログテーブルへの書き込み
            $where[] = array('id', '=', $params['id']);
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'matter_create_date':
                        case 'construction_date':
                        case 'ridgepole_raising_date':
                        case 'delivery_expected_date':
                            $items[$colName] = $params[$colName];
                            // 日付が空白の場合はnullを代入する ※date型に空白は登録出来ない為
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
            $items['update_user'] = $userId;
            $items['update_at'] = $now;
    
            // 更新
            $updateCnt = $this
                ->where($where)
                ->update($items);

            return ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * IDで取得
     * @param int $id 案件ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id)
    {
        $data = $this
            ->where(['id' => $id])
            ->first();

        return $data;
    }

    /**
     * 案件テーブル　住所ID(address_id)を更新
     *
     * @param [type] $matterId
     * @param [type] $addressId
     * @return void
     */
    public function updateForAddress($matterId, $addressId)
    {
        $result = false;

        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $matterId, config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', '=', $matterId)
                ->update([
                    'address_id' => $addressId,
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
     * 案件IDから見積データ取得
     *
     * @param int $matterId 案件ID
     * @return void
     */
    public function getQuoteData($matterId)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.id', '=', $matterId);

        // データ取得
        $data = $this
            ->leftJoin('t_quote', function ($join) {
                $join
                    ->on('t_quote.matter_no', 't_matter.matter_no')
                    ->where('t_quote.del_flg', config('const.flg.off'));
            })
            ->leftjoin('m_customer', 'm_customer.id', 't_matter.customer_id')
            ->leftjoin('m_department', 'm_department.id', 't_matter.department_id')
            ->leftjoin('m_staff', 'm_staff.id', 't_matter.staff_id')
            ->where($where)
            ->select([
                't_matter.id AS matter_id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.owner_name',
                't_matter.complete_flg',
                't_quote.id AS quote_id',
                't_quote.quote_no',
                't_quote.special_flg',
                't_quote.person_id',
                't_quote.construction_period',
                't_quote.construction_outline',
                't_quote.quote_report_to',
                'm_customer.id AS customer_id',
                'm_customer.customer_name',
                'm_department.id AS department_id',
                'm_department.department_name',
                'm_staff.id AS staff_id',
                'm_staff.staff_name',
            ])
            ->first();

        return $data;
    }

    /**
     * 論理削除
     *
     * @param int $matterId 案件ID
     * @return void
     */
    public function deleteById($matterId)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $matterId, config('const.logKbn.soft_delete'));

            // 更新内容
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                ->where('id', $matterId)
                ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * CSVデータ取得
     *
     * @param $matterIDs
     * @return [ 案件番号,　案件名,　得意先名, 郵便番号, 住所1, 住所2,　緯度（日本測地計）, 経度（日本測地計）, 緯度（世界測地計）, 経度（世界測地計） ]
     */
    public function getCsvData($matterIDs)
    {
        $data = $this
            ->from('t_matter as matter')
            ->leftjoin('m_customer', 'm_customer.id', 'matter.customer_id')
            ->leftjoin('m_address', 'm_address.id', 'matter.address_id')
            ->select(
                'matter.matter_no',
                'matter.matter_name',
                'm_customer.customer_name',
                'm_address.zipcode',
                'm_address.address1',
                'm_address.address2',
                'm_address.latitude_jp',
                'm_address.longitude_jp',
                'm_address.latitude_world',
                'm_address.longitude_world'
            )
            ->whereIn('matter.id', $matterIDs)
            ->get();

        return $data;
    }

    /**
     * 発注画面用のデータ取得
     *
     * @param string $matterId 案件ID
     * @return void
     */
    public function getOrderEditData($matterId)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.id', '=', $matterId);

        // 売上総計
        $salesTotal = DB::table('t_quote_version')
            ->select([
                'id',
                'quote_no',
                'sales_total'
            ])
            ->whereRaw('del_flg = ' . config('const.flg.off'))
            ->whereRaw('quote_version = ' . config('const.quoteCompVersion.number'))
            ->toSql();

        // 仕入れ総計の合計
        $sumCostTotal = DB::table('t_order')
            ->select([
                'matter_no',
            ])
            ->selectRaw(
                'SUM(cost_total) as sum_cost_total'
            )
            ->whereRaw('status = ' . config('const.orderStatus.val.ordered'))
            ->whereRaw('del_flg = ' . config('const.flg.off'))
            ->groupBy('matter_no')
            ->toSql();

        // データ取得
        $data = $this
            ->leftjoin('m_customer', 't_matter.customer_id', 'm_customer.id')
            ->leftJoin('m_general', function ($join) {
                $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftjoin('m_address', 't_matter.address_id', 'm_address.id')
            ->leftjoin('m_department', 't_matter.department_id', 'm_department.id')
            ->leftjoin('m_staff', 't_matter.staff_id', 'm_staff.id')
            ->leftjoin('t_quote', 't_matter.matter_no', 't_quote.matter_no')
            ->leftjoin(DB::raw('(' . $salesTotal . ') as t_quote_version'), function ($join) {
                $join->on('t_quote.quote_no', 't_quote_version.quote_no');
            })
            ->leftjoin(DB::raw('(' . $sumCostTotal . ') as t_order_sum'), function ($join) {
                $join->on('t_matter.matter_no', 't_order_sum.matter_no');
            })
            ->where($where)
            ->select([
                't_matter.id as matter_id',
                't_matter.matter_no',
                't_matter.customer_id',
                't_matter.matter_name',
                't_matter.address_id',
                't_matter.department_id',
                't_matter.staff_id',
                't_matter.complete_flg',

                //'m_customer.customer_name as customer_name',

                'm_address.address1 as matter_address1',
                'm_address.address2 as matter_address2',

                'm_department.department_name as department_name',

                'm_staff.staff_name as staff_name',

                't_quote.id as quote_id',
                't_quote.quote_no as quote_no',

                't_quote_version.id as quote_version_id',
                't_quote_version.sales_total as sales_total',

                't_order_sum.sum_cost_total as sum_cost_total',
            ])
            ->selectRaw('
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name
                ')
            ->first();

        return $data;
    }

    /**
     * 重複チェック
     */
    public function isDuplicate($params)
    {
        $result = false;
        $where = [];
        if ($params['id']) {
            $where[] = array('id', '<>', $params['id']);
        }
        $where[] = array('customer_id', '=', $params['customer_id']);
        $where[] = array('owner_name', '=', $params['owner_name']);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $count = $this
            ->where($where)
            ->whereRaw(
                'DATE_FORMAT(matter_create_date, \'%Y%m\')',
                [Carbon::today()->format('Ym')]
            )->count();

        if ($count > 0) {
            $result = true;
        }

        return $result;
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateWarehouseById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $items = [];

            if (!empty($params['from_warehouse_id1'])) {
                $items['from_warehouse_id1'] = $params['from_warehouse_id1'];
            }
            if (!empty($params['from_warehouse_id2'])) {
                $items['from_warehouse_id2'] = $params['from_warehouse_id2'];
            }
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
     * 当社在庫品専用の案件を取得
     */
    public function getOwnStockMatter()
    {
        // Where句作成
        $where = [];
        $where[] = array('own_stock_flg', '=', config('const.flg.on'));

        // データ取得
        $data = $this
            ->where($where)
            ->first();

        return $data;
    }

    /**
     * 担当者/担当部門変更
     *
     * @param  $params
     * @return void
     */
    public function updateStaffByCustomerId($params)
    {
        $result = false;
        try {
            $where = [];
            $where[] = array('customer_id', '=', $params['customer_id']);

            $data = $this
                    ->where($where)
                    ->get()
                    ;

            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $updateCnt = 0;
            foreach ($data as $key => $row) {
                $LogUtil->putById($this->table, $row->id, config('const.logKbn.update'));

                $updateCnt = $this
                    ->where('id', $row->id)
                    ->update([
                        'department_id' => $params['department_id'],
                        'staff_id' => $params['staff_id'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);

                $result = ($updateCnt > 0);
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 得意先IDから取得
     *
     * @param $customerId
     * @return void
     */
    public function getByCustomerId($customerId)
    {
        $where = [];
        $where[] = array('customer_id', '=', $customerId);

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 売上専用の案件を取得する
     * 得意先IDから取得
     *
     * @param $useSalesFlg  売上専用の案件フラグ
     * @param $customerId
     * @return void
     */
    public function getByUseSalesFlg($useSalesFlg, $customerId=null)
    {
        $where = [];
        $where[] = array('use_sales_flg', '=', $useSalesFlg);

        if($customerId !== null){
            $where[] = array('customer_id', '=', $customerId);
        }

        $data = $this
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * 登録(いきなり売り上げ)
     * @param $params 
     * @return $rtnList 
     */
    public function addOfCounterSale($params)
    {
        $result = false;

        // 案件NO
        $matterNo = (new NumberManage())->getSeqNo(config('const.number_manage.kbn.matter'), Carbon::now()->format('Ym'));
        if ($matterNo == config('const.number_manage.result.fail')) {
            throw new \Exception(config('message.error.save'));
        }

        // 案件名
        $matterName = (new SystemUtil())->createMatterName($params['owner_name'], null, $params['customer_id']);

        try {
            $result = $this->insertGetId([
                'matter_no' => $matterNo,
                'matter_name' => $matterName,
                'matter_create_date' => Carbon::now(),
                'owner_name' => $params['owner_name'],
                'department_id' => $params['department_id'],
                'staff_id' => $params['staff_id'],
                'customer_id' => $params['customer_id'],
                'use_sales_flg' => $params['use_sales_flg'],
                'complete_flg' => config('const.flg.off'),
                'own_stock_flg' => config('const.flg.off'),
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now()
            ]);
            
            $rtnList['matter_id'] = $result;
            $rtnList['matter_no'] = $matterNo;
            $rtnList['matter_name'] = $matterName;

        } catch (\Exception $e) {
            throw $e;
        }
        return $rtnList;
    }


    /**
     * 仕入先IDから取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getMatterListBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);
        // $where[] = array('pro.del_flg', '=', config('const.flg.off'));

        // $data = $this
        //         ->from('t_order_detail AS od')
        //         ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
        //         ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
        //         ->where($where)
        //         ->select([
        //             'm.matter_name',
        //             'm.matter_no',
        //         ])
        //         ->whereNotNull('m.matter_name')
        //         ->distinct()
        //         ->get()
        //         ;

        $arrivalData = $this
                ->from('t_arrival as ar')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'ar.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
                ->where($where)
                ->select([
                    'm.matter_no',
                    'm.matter_name',
                ])
                ->distinct()
                ;

        $returnData = $this
                ->from('t_return as re')
                ->leftJoin('t_warehouse_move as wm', 'wm.id', '=', 're.warehouse_move_id')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
                ->where($where)
                ->select([
                    'm.matter_no',
                    'm.matter_name',
                ])
                ->distinct()
                ;

        $data = $arrivalData->union($returnData)->get();

        return $data;
    }

    /**
     * 案件IDから見積データ取得 売上明細用
     *
     * @param int $matterId 案件ID
     * @return void
     */
    public function getSalesDetailData($matterId)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.id', '=', $matterId);

        // データ取得
        $data = $this
            ->join('t_quote', function ($join) {
                $join
                    ->on('t_quote.matter_no', 't_matter.matter_no')
                    ->where('t_quote.del_flg', config('const.flg.off'));
            })
            ->leftjoin('m_customer', 'm_customer.id', 't_matter.customer_id')
            ->leftjoin('m_department', 'm_department.id', 't_matter.department_id')
            ->leftjoin('m_staff', 'm_staff.id', 't_matter.staff_id')
            ->where($where)
            ->select([
                't_matter.id AS matter_id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.owner_name',
                't_matter.complete_flg',
                't_quote.id AS quote_id',
                't_quote.quote_no',
                't_quote.special_flg',
                't_quote.person_id',
                't_quote.construction_period',
                't_quote.construction_outline',
                't_quote.quote_report_to',
                'm_customer.id AS customer_id',
                'm_customer.customer_name',
                'm_department.id AS department_id',
                'm_department.department_name',
                'm_staff.id AS staff_id',
                'm_staff.staff_name',
            ])
            ->first();

        return $data;
    }

    /**
     * 売上一覧用のデータ取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getSalesListData($params)
    {
        // Where句作成
        $where = [];

        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.own_stock_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.complete_flg', '=', config('const.flg.off'));
        // 得意先ID
        if (!empty($params['customer_id'])) {
            $where[] = array('t_matter.customer_id', '=', $params['customer_id']);
        }
        // // 部門ID
        // if (!empty($params['department_id'])) {
        //     $where[] = array('t_matter.department_id', '=', $params['department_id']);
        // }
        // // 担当者ID
        // if (!empty($params['staff_id'])) {
        //     $where[] = array('t_matter.staff_id', '=', $params['staff_id']);
        // }

        // 一覧系のLIMIT値が決まり次第、LIMITを設定する
        $data = $this
            ->where($where)
            ->from('t_matter')
            ->join('t_quote', 't_matter.matter_no', 't_quote.matter_no')
            ->leftJoin('m_customer', 't_matter.customer_id', '=', 'm_customer.id')
            ->leftJoin('m_general as general', function ($join) {
                $join->on('m_customer.juridical_code', '=', 'general.value_code')
                    ->where('general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_department as depart', 't_matter.department_id', '=', 'depart.id')
            ->leftJoin('m_staff as staff', 't_matter.staff_id', '=', 'staff.id')
            ->select([
                't_matter.id AS matter_id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.owner_name',
                't_matter.customer_id',
                't_matter.use_sales_flg',
                't_quote.id AS quote_id',
                't_quote.quote_no',
                DB::raw('CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(general.value_text_3, \'\')) as customer_name'),
                't_matter.department_id',
                'depart.department_name',
                't_matter.staff_id',
                'staff.staff_name',
                DB::raw('0 AS delivery_quantity'),
                DB::raw('0 AS return_quantity'),
                DB::raw('0 AS purchase_volume'),
                DB::raw('0 AS sales'),
                DB::raw('0 AS additional_discount_amount'),
                DB::raw('0 AS profit_total'),
                DB::raw('0.0 AS gross_profit_rate'),
                DB::raw('0 AS zero_sales_cnt'),
                DB::raw('0 AS update_cost_unit_price_cnt'),
            ])
            ->orderBy('t_matter.use_sales_flg', 'asc')
            ->orderBy('t_matter.matter_no', 'asc')
            ->get();
        return $data;
    }

    /**
     * 販売金額利用フラグ配下に納品があって販売額利用フラグ階層に未納を作成していない案件のリストを返す
     * 入れ子の場合、最上位の販売額利用フラグの階層
     * @param array $customerId 得意先ID
     * @return $result  案件のリスト
     */
    public function getNotCreateNotDeliveryData($customerId)
    {
        $result = [];
        // Where句作成
        $where = [];

        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.own_stock_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.complete_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.customer_id', '=', $customerId);

        $salesUseData = $this
            ->where($where)
            ->join('t_quote', 't_matter.matter_no', 't_quote.matter_no')
            ->join('t_quote_detail', function ($join) {
                $join->on('t_quote.quote_no', '=', 't_quote_detail.quote_no')
                    ->where('t_quote_detail.layer_flg', '=',config('const.flg.on'))
                    ->where('t_quote_detail.sales_use_flg', '=',config('const.flg.on'))
                    ->where('t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number'))
                    ->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
            })
            ->leftjoin('t_sales_detail', function ($join) {
                $join->on('t_quote_detail.id', '=', 't_sales_detail.quote_detail_id')
                    ->where('t_sales_detail.del_flg', '=', config('const.flg.off'))
                    ->where('t_sales_detail.sales_flg', '=', config('const.salesFlg.val.not_delivery'));
            })
            ->select([
                't_matter.id AS matter_id',
                // 't_matter.matter_no',
                // 't_matter.matter_name',
                't_matter.owner_name',
                't_quote_detail.id AS quote_detail_id',
                't_quote_detail.tree_path',
                't_sales_detail.id AS sales_detail_id',
                DB::raw(config('const.flg.off').' AS invalid_unit_price_flg'),
            ])
            ->get();

        foreach($salesUseData as $key => $row){
            $treePathList = explode(config('const.treePathSeparator'), $row->tree_path);
            foreach($treePathList as $quoteDetailId){
                if($row->invalid_unit_price_flg === config('const.flg.off')){
                    // 販売額利用フラグが立っている見積明細IDを含むツリーパスか
                    $salesUseFlgData = $salesUseData->firstWhere('quote_detail_id', '=', $quoteDetailId);
                    if($salesUseFlgData !== null){
                        $salesUseData[$key]->invalid_unit_price_flg = config('const.flg.on');
                        break;
                    }
                }
            }
        }
        // 未納を作っていない販売額利用フラグの最上位階層
        $createNotDeliveryList = $salesUseData->where('invalid_unit_price_flg', '=', config('const.flg.off'))->where('sales_detail_id', '=', null);

        if($createNotDeliveryList->count() >= 1){
            $whereDelivery[] = array('t_matter.del_flg', '=', config('const.flg.off'));
            $whereDelivery[] = array('t_matter.own_stock_flg', '=', config('const.flg.off'));
            $whereDelivery[] = array('t_matter.complete_flg', '=', config('const.flg.off'));
            $whereDelivery[] = array('t_matter.customer_id', '=', $customerId);
            $whereDelivery[] = array('t_delivery.del_flg', '=', config('const.flg.off'));
            $whereDelivery[] = array('t_delivery.delivery_date', '>=', config('const.salesDeliveryValidDate'));

            // 全納品データ
            $deliveryDataList = $this
                ->where($whereDelivery)
                ->join('t_delivery', 't_matter.id', 't_delivery.matter_id')
                ->join('t_quote_detail', function ($join) {
                    $join->on('t_delivery.quote_detail_id', '=', 't_quote_detail.id')
                        ->where('t_quote_detail.quote_version', '=', config('const.quoteCompVersion.number'))
                        ->where('t_quote_detail.del_flg', '=', config('const.flg.off'));
                })
                ->select([
                    't_matter.id AS matter_id',
                    't_quote_detail.id AS quote_detail_id',
                    't_quote_detail.tree_path',
                ])
                ->distinct()
                ->get();

            foreach($deliveryDataList as $row){
                if(isset($result[$row->matter_id])){
                    continue;
                }
                // 納品データがあるツリーパス上の見積明細に販売額利用フラグの階層が含まれているか
                $treePathList = explode(config('const.treePathSeparator'), $row->tree_path);
                foreach($treePathList as $quoteDetailId){
                    $salesUseFlgData = $createNotDeliveryList->firstWhere('quote_detail_id', '=', $quoteDetailId);
                    if($salesUseFlgData !== null){
                        $result[$salesUseFlgData->matter_id] = $salesUseFlgData->owner_name;
                    }
                }
            }
        }
        
        return $result;
    }

    /**
     * 案件詳細用データ
     *
     * @param [type] $id 案件ID
     * @return void
     */
    public function getMatterDetailData($id)
    {
        $matterInfo = $this->getById($id);

        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_matter.id', '=', $id);

        // 見積版IDリスト 取得
        $quoteVerIdlistQuery = DB::table('t_quote')
                ->leftJoin('t_quote_version', function($join) use($matterInfo){
                    $join
                        ->on('t_quote.quote_no', '=', 't_quote_version.quote_no')
                        ->where('t_quote_version.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->where([
                    ['t_quote.del_flg', '=', config('const.flg.off')],
                    ['t_quote.matter_no', '=', $matterInfo->matter_no],
                ])
                ->select([
                    't_quote.matter_no',
                    't_quote.id',
                    't_quote.quote_no',
                    DB::raw('JSON_ARRAYAGG(t_quote_version.id) as quote_version_id'),
                ])
                ->groupBy('t_quote.matter_no', 't_quote.id', 't_quote.quote_no')
                ;

        // 発注IDリスト 取得
        $orderIdlistQuery = DB::table('t_order')
                ->where([
                    ['t_order.del_flg', '=', config('const.flg.off')],
                    ['t_order.matter_no', '=', $matterInfo->matter_no],
                ])
                ->select([
                    't_order.matter_no',
                    DB::raw('JSON_ARRAYAGG(t_order.id) as order_id'),
                ])
                ->groupBy('t_order.matter_no')
                ;

        $data = $this
                    ->leftJoin(DB::raw('('. $quoteVerIdlistQuery->toSql(). ') AS quotes'), 't_matter.matter_no', 'quotes.matter_no')
                    ->mergeBindings($quoteVerIdlistQuery)
                    ->leftJoin(DB::raw('('. $orderIdlistQuery->toSql(). ') AS orders'), 't_matter.matter_no', 'orders.matter_no')
                    ->mergeBindings($orderIdlistQuery)
                    ->leftjoin('m_address', 't_matter.address_id', 'm_address.id')
                    ->leftjoin('m_customer', 't_matter.customer_id', 'm_customer.id')
                    ->leftjoin('m_department', 't_matter.department_id', 'm_department.id')
                    ->leftjoin('m_staff', 't_matter.staff_id', 'm_staff.id')
                    ->where($where)
                    ->select([
                        't_matter.id AS matter_id',
                        't_matter.matter_no',
                        't_matter.matter_name',
                        't_matter.owner_name',
                        DB::raw('DATE_FORMAT(t_matter.construction_date, \'%Y/%m/%d\') AS construction_date'),
                        DB::raw('DATE_FORMAT(t_matter.ridgepole_raising_date, \'%Y/%m/%d\') AS ridgepole_raising_date'),
                        't_matter.complete_flg',
                        'quotes.id AS quote_id',
                        'quotes.quote_no',
                        DB::raw('
                            CASE
                              WHEN quotes.quote_version_id IS NULL
                                THEN JSON_ARRAY()
                                ELSE quotes.quote_version_id
                            END AS quote_version_id_list
                        '),
                        DB::raw('
                            CASE
                              WHEN orders.order_id IS NULL
                                THEN JSON_ARRAY()
                                ELSE orders.order_id
                            END AS order_id_list
                        '),
                        'm_address.zipcode',
                        'm_address.address1',
                        'm_address.address2',
                        'm_customer.id AS customer_id',
                        'm_customer.customer_name',
                        'm_department.id AS department_id',
                        'm_department.department_name',
                        'm_staff.id AS staff_id',
                        'm_staff.staff_name',
                    ])
                    ->first();
        return $data;
    }
    
    /**
     * コンボボックスデータ（顧客情報込み）
     * @return type 検索結果データ
     */
    public function getCustomerComboList()
    {
        // Where句作成
        $where = [];

        $where[] = array('matter.del_flg', '=', config('const.flg.off'));
       
        // 一覧系のLIMIT値が決まり次第、LIMITを設定する
        $data = $this
           
            ->from('t_matter as matter')
            ->leftJoin('m_customer as customer', 'matter.customer_id', '=', 'customer.id')
            ->leftJoin('m_general as general', function ($join) {
                $join->on('customer.juridical_code', '=', 'general.value_code')
                    ->where('general.category_code', '=', config('const.general.juridical'));
            })
            ->select([
                'matter.id',
                'matter.matter_no',
                'matter.matter_name',
                'matter.customer_id',
                'matter.owner_name',
                DB::raw('CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(customer.customer_name, \'\'), COALESCE(general.value_text_3, \'\')) as customer_name')
            ])
            ->get();
        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @param int $completeFlg  完了フラグ config('const.flg.off') または config('const.flg.on')
     * @param int $ownStockFlg  当社在庫品フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @param int $useSalesFlg  売上利用フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     * @return void
     */
    public function getHasQuoteComboList($completeFlg = null, $ownStockFlg = 0, $useSalesFlg = 0)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_matter.del_flg', '=', config('const.flg.off'));
        if (!is_null($completeFlg)) {
            $where[] = array('t_matter.complete_flg', '=', $completeFlg);
        }
        if (!is_null($ownStockFlg)) {
            $where[] = array('t_matter.own_stock_flg', '=', $ownStockFlg);
        }
        if (!is_null($useSalesFlg)) {
            $where[] = array('t_matter.use_sales_flg', '=', $useSalesFlg);
        }

        $query = DB::table('t_matter')
                ->join('t_quote', 't_quote.matter_no', '=', 't_matter.matter_no')
                ->where($where)
                ->select([
                    't_matter.id',
                ])
                ;

        // データ取得
        $data = $this
            ->join(DB::raw('('.$query->toSql().') as mt'), function($join){
                $join->on('t_matter.id', '=', 'mt.id');
            })
            ->mergeBindings($query)
            ->where($where)
            ->orderBy('t_matter.id', 'asc')
            ->select([
                't_matter.id',
                't_matter.matter_no',
                't_matter.matter_name',
                't_matter.customer_id',
                't_matter.owner_name'
            ])
            ->get();

        return $data;
    }

    /**
     * IDで取得
     * @param int $ids 案件ID
     * @return type 得意先IDリスト
     */
    public function getCustomerIdByIdList($ids)
    {
        $data = $this
            ->whereIn('id', $ids)
            ->select('customer_id')
            ->groupBy('customer_id')
            ->get()
            ;

        return $data;
    }
}
