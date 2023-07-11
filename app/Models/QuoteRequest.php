<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 見積依頼
 */
class QuoteRequest extends Model
{
    // テーブル名
    protected $table = 't_quote_request';
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

        $where[] = array('quote_request.del_flg', '=', config('const.flg.off'));
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
            $where[] = array('quote_request.department_id', '=', $params['department_id']);
        }
        // 担当者ID
        if (!empty($params['staff_id'])) {
            $where[] = array('quote_request.staff_id', '=', $params['staff_id']);
        }
        // 見積依頼日(FROM-TO)
        if (!empty($params['request_date_from'])) {
            $where[] = array('quote_request.request_date', '>=', $params['request_date_from']);
        }
        if (!empty($params['request_date_to'])) {
            $where[] = array('quote_request.request_date', '<', date('Y/m/d', strtotime($params['request_date_to']. ' +1 day')));
        }
        // 見積提出期限日(FROM-TO)
        if (!empty($params['quote_limit_date'])) {
            $where[] = array('quote_request.quote_limit_date', '=', $params['quote_limit_date']);
        }

        $data = $this
                    ->where($where)
                    ->when(!empty($params['quote_request_kbn']), function($query) use($params) {
                        return $query
                                ->whereRaw(
                                    'JSON_CONTAINS(quote_request.quote_request_kbn, ?)',
                                    [$params['quote_request_kbn']]
                                );
                    })
                    ->from('t_quote_request as quote_request')
                    ->leftJoin('t_quote as quote', 'quote_request.matter_no', '=', 'quote.matter_no')
                    ->leftJoin('t_matter as matter', 'quote_request.matter_no', '=', 'matter.matter_no')
                    ->leftJoin('m_customer as customer', 'matter.customer_id', '=', 'customer.id')
                    ->leftJoin('m_department as depart', 'quote_request.department_id', '=', 'depart.id')
                    ->leftJoin('m_staff as staff', 'quote_request.staff_id', '=', 'staff.id')
                    ->select([
                        'quote_request.id',
                        DB::raw('DATE_FORMAT(quote_request.request_date, \'%Y/%m/%d\') AS request_date'),
                        DB::raw('DATE_FORMAT(quote_request.quote_limit_date, \'%Y/%m/%d\') AS quote_limit_date'),
                        'quote_request.quote_request_kbn',
                        'quote_request.status',
                        'quote.id AS quote_id',
                        'matter.id AS matter_id',
                        'matter.matter_no',
                        'matter.matter_name',
                        'depart.department_name',
                        'staff.staff_name'
                    ])
                    ->get()
                ;
        return $data;

    }

    /**
     * 見積依頼データ取得
     *
     * @param int $id 見積依頼ID
     * @return void
     */
    public function getQuoteRequest($id) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_request.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote_request.id', '=', $id);

        // データ取得
        $data = $this
                ->leftjoin('t_matter', 't_quote_request.matter_no', 't_matter.matter_no')
                ->leftjoin('m_general', function ($join) {
                    $join
                        ->on('m_general.value_code', '=', 't_quote_request.spec_kbn')
                        ->where('m_general.category_code', '=', config('const.general.spec'))
                        ->where('m_general.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->leftjoin('m_customer', 'm_customer.id', '=', 't_matter.customer_id')
                ->leftjoin('m_general as juridical', function($join) {
                    $join
                        ->on('m_customer.juridical_code', '=', 'juridical.value_code')
                        ->where('juridical.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftjoin('m_general as type', function($join) {
                    $join
                        ->on('t_matter.architecture_type', '=', 'type.value_code')
                        ->where('type.category_code', '=', config('const.general.arch'))
                        ;
                })
                ->where($where)
                ->select([
                    't_matter.id as matter_id',
                    't_matter.matter_no',
                    't_matter.matter_name',
                    't_matter.customer_id',
                    't_matter.owner_name',
                    't_matter.architecture_type',
                    't_quote_request.id AS quote_request_id',
                    't_quote_request.quote_limit_date',
                    't_quote_request.spec_kbn',
                    't_quote_request.semi_fireproof_area_flg',
                    't_quote_request.builder_standard_flg',
                    't_quote_request.use_ref_quote_flg',
                    't_quote_request.ref_matter_no',
                    't_quote_request.ref_quote_no',
                    't_quote_request.quote_request_kbn',
                    't_quote_request.quote_requested_kbn',
                    't_quote_request.status',
                    'm_general.value_text_1 AS spec_kbn_text',
                    'type.value_text_1 AS architecture_type_text'
                ])
                ->selectRaw('
                    CONCAT(COALESCE(juridical.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(juridical.value_text_3, \'\')) as customer_name
                ')
                ->first()
                ;

        return $data;
    }
    
    /**
     * 見積依頼明細データ取得
     *
     * @param int $id 見積依頼ID
     * @return void
     */
    public function getQuoteRequestDetail($id) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_request.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote_request.id', '=', $id);

        // データ取得
        $data = $this
                ->leftjoin('t_quote_request_detail', 't_quote_request_detail.quote_request_id', 't_quote_request.id')
                ->join('t_spec_item_detail', 't_spec_item_detail.id', 't_quote_request_detail.spec_item_detail_id')
                ->join('m_item', 'm_item.id', 't_spec_item_detail.item_id')
                ->where($where)
                ->select([
                    't_quote_request_detail.seq_no',
                    't_quote_request_detail.quote_request_kbn',
                    't_quote_request_detail.spec_item_header_id',
                    't_quote_request_detail.spec_item_detail_id',
                    't_quote_request_detail.input_value01',
                    't_quote_request_detail.input_value02',
                    't_quote_request_detail.input_value03',
                    'm_item.item_type'
                ])
                ->get()
                ;

        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return int ID（auto_increment）
     */
    public function add($params) {
        try {
            $items = [];
            $items['matter_no'] = $params['matter_no'];
            if (!empty($params['department_id'])) {
                $items['department_id'] = $params['department_id'];
            }
            if (!empty($params['staff_id'])) {
                $items['staff_id'] = $params['staff_id'];
            }
            if (!empty($params['quote_limit_date'])) {
                $items['quote_limit_date'] = $params['quote_limit_date'];
            }
            if (!empty($params['spec_kbn'])) {
                $items['spec_kbn'] = $params['spec_kbn'];
            }

            $semiFireproofAreaFlg = config('const.flg.off');
            if (!empty($params['semi_fireproof_area_flg'])) {
                $semiFireproofAreaFlg = config('const.flg.on');
            }
            $items['semi_fireproof_area_flg'] = $semiFireproofAreaFlg;

            $builderStandardFlg = config('const.flg.off');
            if (!empty($params['builder_standard_flg'])) {
                $builderStandardFlg = config('const.flg.on');
            }
            $items['builder_standard_flg'] = $builderStandardFlg;

            $useRefQuoteFlg = config('const.flg.off');
            if (!empty($params['use_ref_quote_flg'])) {
                $useRefQuoteFlg = config('const.flg.on');
            }
            $items['use_ref_quote_flg'] = $useRefQuoteFlg;

            if (!empty($params['ref_matter_no'])) {
                $items['ref_matter_no'] = $params['ref_matter_no'];
            }
            if (!empty($params['ref_quote_no'])) {
                $items['ref_quote_no'] = $params['ref_quote_no'];
            }
            if (!empty($params['quote_request_kbn'])) {
                $items['quote_request_kbn'] = $params['quote_request_kbn'];
            }
            if (!empty($params['quote_requested_kbn'])) {
                $items['quote_requested_kbn'] = $params['quote_requested_kbn'];
            }
            if (!empty($params['status'])) {
                $items['status'] = $params['status'];
                // 見積依頼済みの登録時に依頼日を更新
                if ($params['status'] === config('const.quoteRequestStatus.val.requested')) {
                    $items['request_date'] = Carbon::today();
                }
            }
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
     * 更新
     * 案件番号・担当部門・担当者・削除フラグ・登録者情報は更新対象外
     *
     * @param array $params 
     * @return void
     */
    public function updateById($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['quote_request_id'], config('const.logKbn.update'));

            // 更新内容
            $items = [];
            if (!empty($params['quote_limit_date'])) {
                $items['quote_limit_date'] = $params['quote_limit_date'];
            }
            if (!empty($params['spec_kbn'])) {
                $items['spec_kbn'] = $params['spec_kbn'];
            }

            $semiFireproofAreaFlg = config('const.flg.off');
            if (!empty($params['semi_fireproof_area_flg'])) {
                $semiFireproofAreaFlg = config('const.flg.on');
            }
            $items['semi_fireproof_area_flg'] = $semiFireproofAreaFlg;

            $builderStandardFlg = config('const.flg.off');
            if (!empty($params['builder_standard_flg'])) {
                $builderStandardFlg = config('const.flg.on');
            }
            $items['builder_standard_flg'] = $builderStandardFlg;

            $useRefQuoteFlg = config('const.flg.off');
            if (!empty($params['use_ref_quote_flg'])) {
                $useRefQuoteFlg = config('const.flg.on');
            }
            $items['use_ref_quote_flg'] = $useRefQuoteFlg;

            if (!empty($params['ref_matter_no'])) {
                $items['ref_matter_no'] = $params['ref_matter_no'];
            }
            if (!empty($params['ref_quote_no'])) {
                $items['ref_quote_no'] = $params['ref_quote_no'];
            }
            if (!empty($params['quote_request_kbn'])) {
                $items['quote_request_kbn'] = $params['quote_request_kbn'];
            }
            if (!empty($params['quote_requested_kbn'])) {
                $items['quote_requested_kbn'] = $params['quote_requested_kbn'];
            }
            if (isset($params['status'])) {
                // 初回の見積依頼済みの更新時に依頼日を更新
                if ($params['status'] === config('const.quoteRequestStatus.val.requested')) {
                    // 更新前データ取得
                    $beforeData = $this->find($params['quote_request_id']);
                    if ($beforeData['status'] === config('const.quoteRequestStatus.val.editing')) {
                        $items['status'] = $params['status'];
                        $items['request_date'] = Carbon::today();
                    }
                }
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                        ->where('id', (int)$params['quote_request_id'])
                        ->update($items)
                        ;

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
    
    /**
     * 案件番号から見積依頼項目データ取得
     *
     * @param string $matterNo 案件番号
     * @return void
     */
    public function getQuoteRequestByMatterNo($matterNo) {
        // Where句作成
        $where = [];
        $where[] = array('t_quote_request.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_quote_request.status', '=', config('const.flg.on'));
        $where[] = array('t_quote_request.matter_no', '=', $matterNo);

        // データ取得
        $data = $this
                ->where($where)
                ->select([
                    't_quote_request.id',
                    't_quote_request.quote_request_kbn',
                    't_quote_request.quote_limit_date',
                ])
                ->get()
                ;

        return $data;
    }

    /**
     * 依頼済かつ見積未完了の見積依頼レコード取得
     *
     * @param array $quoteRequestKbn 見積依頼項目
     * @return 見積依頼データリスト
     */
    public function getRequestingData($quoteRequestKbn) {
        // 依頼済かつ選択した見積依頼項目が含まれる見積依頼にしぼる
        $mainQuery = $this
                ->whereRaw('t_quote_request.del_flg = '.config('const.flg.off'))
                ->whereRaw('t_quote_request.status = '.config('const.quoteRequestStatus.val.requested'))
                ->when((count($quoteRequestKbn) > 0), function ($whenQuery) use ($quoteRequestKbn) {
                    $whenQuery->where(function ($inQuery) use ($quoteRequestKbn) {
                        foreach ($quoteRequestKbn as $key => $val) {
                            if ($key === 0) {
                                $inQuery
                                    ->whereRaw(
                                        "JSON_CONTAINS(t_quote_request.quote_request_kbn, '".$val."')"
                                    );
                            } else {
                                $inQuery
                                    ->orWhereRaw(
                                        "JSON_CONTAINS(t_quote_request.quote_request_kbn, '".$val."')"
                                    );
                            }
                        }
                    });
                    return $whenQuery;
                })
                ;

        // // 見積明細を工事区分階層のレコードのみに絞る（版を区別せずに、見積番号別・工事区分別で、積算完了フラグ=1を優先）
        // $quoteDetailQuery = DB::table('t_quote_detail') 
        //         ->select([
        //             'quote_no',
        //             'construction_id',
        //             DB::raw('MAX(complete_flg) as complete_flg'),
        //         ])
        //         ->where('t_quote_detail.del_flg', config('const.flg.off'))
        //         ->where('t_quote_detail.depth', config('const.quoteConstructionInfo.depth'))
        //         ->groupBy('quote_no', 'construction_id')
        //         ;

        $query = $this
                ->from(DB::raw('('.$mainQuery->toSql().') AS main'))
                ->leftjoin('t_quote', 't_quote.matter_no', 'main.matter_no')
                // ->leftjoin(DB::raw('('.$quoteDetailQuery->toSql().') as quote_detail'), function ($join) {
                //     $join
                //         ->on('quote_detail.quote_no', '=', 't_quote.quote_no' )
                //         ->whereRaw("JSON_CONTAINS(main.quote_request_kbn, CAST(quote_detail.construction_id AS CHAR))")
                //         ;
                // })
                // ->mergeBindings($quoteDetailQuery)
                ->where(function ($query) {
                    $query
                        ->whereNull('t_quote.id')
                        ->orWhere('t_quote.status', config('const.quoteStatus.val.incomplete'));
                })
                ;

        $data = $query->select([
                    'main.id                      AS quote_request_id',
                    'main.quote_request_kbn       AS quote_request_kbn',
                    't_quote.id                   AS quote_id',
                    't_quote.quote_no             AS quote_no',
                    // 'quote_detail.construction_id AS construction_id',
                    // 'quote_detail.complete_flg    AS complete_flg',
                ])
                ->get()
                ;

        return $data;
    }
    
    /**
     * 見積依頼一覧 作成された見積依頼項目を取得する
     *
     * @param int $quoteIDs
     * @return void
     */
    public function getCreatedQuoteRequestKbn($ids){
        $where = [];
        $where[] = array('quote_request.del_flg', '=', config('const.flg.off'));

        $quoteQuery = DB::table('t_quote')
                            ->select([
                                'quote_no', 'matter_no'
                            ])
                            ->where('del_flg', config('const.flg.off'))
                            ;
        $quoteDetailQuery = DB::table('t_quote_detail')
                            ->select([
                                'id', 'quote_no', 'construction_id', 'complete_flg',
                            ])
                            ->where('del_flg', config('const.flg.off'))
                            ->where('depth', config('const.quoteConstructionInfo.depth'))
                            ;

        $data = $this
                    ->from('t_quote_request as quote_request')
                    ->leftjoin(
                        DB::raw('('.$quoteQuery->toSql().') as quote'),
                        'quote_request.matter_no', '=', 'quote.matter_no' 
                    )
                    ->mergeBindings($quoteQuery)
                    ->leftjoin(
                        DB::raw('('.$quoteDetailQuery->toSql().') as quote_detail'),
                        'quote.quote_no', '=', 'quote_detail.quote_no' 
                    )
                    ->mergeBindings($quoteDetailQuery)
                    ->where($where)
                    ->whereIn('quote_request.id', $ids)
                    ->select(
                        'quote_request.id',
                        'quote_detail.id AS detail_id',
                        'quote_detail.construction_id AS construction_id',
                        'quote_detail.complete_flg AS complete_flg'
                    )
                    ->get()
                    ;
        return $data;
    }

    /**
     * 論理削除
     *
     * @param int $id 見積依頼ID
     * @return void
     */
    public function deleteById($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            // 更新内容
            $items['del_flg'] = config('const.flg.on');
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            // 更新
            $updateCnt = $this
                    ->where('id', $id)
                    ->update($items);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 担当者/担当部門変更
     *
     * @param  $params
     * @return void
     */
    public function updateStaffByMatterNo($params) 
    {
        $result = false;
        try {
            $where = [];
            $where[] = array('matter_no', '=', $params['matter_no']);

            // データ取得
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
                        ])
                        ;
                
                $result = ($updateCnt > 0);
            }
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 部門と担当者を変更する
     * @param $matterNo     案件番号
     * @param $departmrntId 部門ID
     * @param $staffId      担当者ID
     */
    public function updateDepartmentStaffByMatterNo($matterNo, $departmrntId = null, $staffId = null){
        $result = true;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();

            // Where句作成
            $where = [];
            $where[] = array('t_quote_request.del_flg', '=', config('const.flg.off'));
            $where[] = array('t_quote_request.matter_no', '=', $matterNo);

            // データ取得
            $data = $this
                    ->where($where)
                    ->get()
                    ;

            $updateData = [];
            if($departmrntId !== null){
                $updateData['department_id'] = $departmrntId;
            }

            if($staffId !== null){
                $updateData['staff_id']  = $staffId;
            }
            $updateData['update_user']   = Auth::user()->id;
            $updateData['update_at']     = Carbon::now();

            foreach($data as $row){
                $LogUtil->putByData($row, config('const.logKbn.update'), $this->table);
                // 更新
                $updateCnt = $this
                            ->where('id', $row->id)
                            ->update($updateData)
                            ;
                if($result){
                    $result = ($updateCnt === 1);
                }
            }

        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }
}