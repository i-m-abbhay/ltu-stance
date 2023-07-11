<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use DB;
use Auth;
use App\Libs\LogUtil;

/**
 * 仕様項目ヘッダーテーブル
 */
class SpecItemHeader extends Model
{
    // テーブル名
    protected $table = 't_spec_item_header';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 見積依頼入力テンプレートデータ取得
     *
     * @return 
     */
    public function getQuoteRequestTemplateData() {

        // 仕様項目ヘッダー
        $templateQuery = DB::table('t_spec_item_header')
                            ->select([
                                'quote_request_kbn',
                                DB::raw('max(start_date) as start_date')
                            ])
                            ->whereraw('del_flg = '.config('const.flg.off'))
                            ->whereraw("start_date <= '".Carbon::today()->format('Y/m/d')."'")
                            ->groupBy('quote_request_kbn')
                            ;
        // 仕様項目ヘッダー
        $headerQuery = DB::table('t_spec_item_header')
                            ->whereraw('del_flg = '.config('const.flg.off'))
                            ;
        // 仕様項目明細
        $detailQuery = DB::table('t_spec_item_detail')
                        ->whereraw('del_flg = '.config('const.flg.off'))
                        ;
        // 項目マスタ
        $itemQuery = DB::table('m_item')
                        ->whereraw('del_flg = '.config('const.flg.off'))
                        ;

        // データ取得
        $data = $this
                ->join(
                    DB::raw('('.$headerQuery->toSql().') as header'), 
                    't_spec_item_header.id', '=', 'header.id'
                )
                ->join(
                    DB::raw('('.$templateQuery->toSql().') as template'), 
                    function($q) {
                        $onwhere = [];
                        $onwhere[] = array('template.quote_request_kbn', '=', 'header.quote_request_kbn');
                        $onwhere[] = array('template.start_date', '=', 'header.start_date');
                        $q->on($onwhere);
                    } 
                )
                ->leftjoin(
                    DB::raw('('.$detailQuery->toSql().') as detail'),
                    'header.id', '=', 'detail.spec_item_header_id' 
                )
                ->leftjoin(
                    DB::raw('('.$itemQuery->toSql().') as item'),
                    'item.id', '=', 'detail.item_id' 
                )
                ->orderBy('template.quote_request_kbn', 'asc')
                ->orderBy('detail.display_order', 'asc')
                ->select([
                    'header.id AS header_id',
                    'header.quote_request_kbn',
                    'detail.id AS detail_id',
                    'detail.item_group',
                    'detail.item_id',
                    'item.item_name',
                    'item.item_front_label',
                    'item.item_back_label',
                    'item.item_type',
                    'item.choice_keyword',
                    'item.placeholder',
                    'item.required_flg',
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 仕様項目ヘッダIDで取得
     *
     * @param int $id 仕様項目ヘッダID
     * @return void
     */
    public function getQuoteRequestTemplateDataById($id) {

        // 仕様項目ヘッダー
        $headerQuery = DB::table('t_spec_item_header')
                            ->select([
                                'id',
                                'quote_request_kbn',
                            ])
                            ->whereraw('id = '.$id)
                            ;
        // 仕様項目明細
        $detailQuery = DB::table('t_spec_item_detail')
                        ->whereraw('del_flg = '.config('const.flg.off'))
                        ;
        // 項目マスタ
        $itemQuery = DB::table('m_item')
                        ->whereraw('del_flg = '.config('const.flg.off'))
                        ;

        // データ取得
        $data = $this
                ->join(
                    DB::raw('('.$headerQuery->toSql().') as header'), 
                    't_spec_item_header.id', '=', 'header.id'
                )
                ->leftjoin(
                    DB::raw('('.$detailQuery->toSql().') as detail'),
                    'header.id', '=', 'detail.spec_item_header_id' 
                )
                ->leftjoin(
                    DB::raw('('.$itemQuery->toSql().') as item'),
                    'item.id', '=', 'detail.item_id' 
                )
                ->orderBy('detail.display_order', 'asc')
                ->select([
                    'header.id AS header_id',
                    'header.quote_request_kbn',
                    'detail.id AS detail_id',
                    'detail.item_group',
                    'detail.item_id',
                    'item.item_name',
                    'item.item_front_label',
                    'item.item_back_label',
                    'item.item_type',
                    'item.choice_keyword',
                    'item.placeholder',
                    'item.required_flg',
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 見積依頼項目ごとに取得
     *
     * @param int $quoteRequestKbn 見積依頼項目
     * @return void
     */
    public function getQuoteRequestTemplateDataByQuoteRequestKbn($quoteRequestKbn) {

        // 仕様項目ヘッダー
        $templateQuery = DB::table('t_spec_item_header')
                            ->select([
                                'quote_request_kbn',
                                DB::raw('max(start_date) as start_date')
                            ])
                            ->whereraw('del_flg = '.config('const.flg.off'))
                            ->whereraw('quote_request_kbn = '.$quoteRequestKbn)
                            ->whereraw("start_date <= '".Carbon::today()->format('Y/m/d')."'")
                            // ->groupBy('quote_request_kbn')
                            ;
        // 仕様項目ヘッダー
        $headerQuery = DB::table('t_spec_item_header')
                            ->whereraw('del_flg = '.config('const.flg.off'))
                            ;
        // 仕様項目明細
        $detailQuery = DB::table('t_spec_item_detail')
                        ->whereraw('del_flg = '.config('const.flg.off'))
                        ;
        // 項目マスタ
        $itemQuery = DB::table('m_item')
                        ->whereraw('del_flg = '.config('const.flg.off'))
                        ;

        // データ取得
        $data = $this
                ->join(
                    DB::raw('('.$headerQuery->toSql().') as header'), 
                    't_spec_item_header.id', '=', 'header.id'
                )
                ->join(
                    DB::raw('('.$templateQuery->toSql().') as template'), 
                    function($q) {
                        $onwhere = [];
                        $onwhere[] = array('template.quote_request_kbn', '=', 'header.quote_request_kbn');
                        $onwhere[] = array('template.start_date', '=', 'header.start_date');
                        $q->on($onwhere);
                    } 
                )
                ->leftjoin(
                    DB::raw('('.$detailQuery->toSql().') as detail'),
                    'header.id', '=', 'detail.spec_item_header_id' 
                )
                ->leftjoin(
                    DB::raw('('.$itemQuery->toSql().') as item'),
                    'item.id', '=', 'detail.item_id' 
                )
                ->orderBy('detail.display_order', 'asc')
                ->select([
                    'header.id AS header_id',
                    'header.quote_request_kbn',
                    'detail.id AS detail_id',
                    'detail.item_group',
                    'detail.item_id',
                    'item.item_name',
                    'item.item_front_label',
                    'item.item_back_label',
                    'item.item_type',
                    'item.choice_keyword',
                    'item.placeholder',
                    'item.required_flg',
                ])
                ->get()
                ;
        
        return $data;
    }


     /**
     * コンボボックス用リスト取得
     *
     * @return void
     */
    public function getComboList() 
    {
        // Where句
        $where = [];
        $where[] = array('header.del_flg', '=', config('const.flg.off'));
        $where[] = array('con.del_flg', '=', config('const.flg.off'));
        
        $data = $this
                ->from('t_spec_item_header as header')
                ->leftJoin('m_construction as con', 'con.id', '=', 'header.quote_request_kbn')
                ->where($where)
                ->select(
                    'header.*',
                    'con.id as construction_id',
                    'con.construction_name'
                )
                ->get()
                ;

        return $data;
    }


    /**
     * 一覧取得
     *
     * @param $params
     * @return void
     */
    public function getList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('header.del_flg', '=', config('const.flg.off'));
        $hasType = false;

        if (!empty($params['construction_name'])) {
            $where[] = array('con.construction_name', 'LIKE', '%'.$params['construction_name'].'%');
        }
        if (!empty($params['from_start_date'])) {
            $where[] = array('header.start_date', '>=', $params['from_start_date']);
        }
        if (!empty($params['to_start_date'])) {
            $where[] = array('header.start_date', '<=', $params['to_start_date']);
        }
        if (!empty($params['item_type'])) {
            $hasType = true;
        }

        $data = $this
                ->from('t_spec_item_header as header')
                ->leftJoin('m_construction as con', 'con.id', '=', 'header.quote_request_kbn')
                ->selectRaw('
                        header.id,
                        con.construction_name,
                        DATE_FORMAT(header.created_at, "%Y/%m/%d %H:%i:%s") as created_at,
                        DATE_FORMAT(header.update_at, "%Y/%m/%d %H:%i:%s") as update_at,
                        DATE_FORMAT(header.start_date, "%Y/%m/%d") as start_date
                ')
                ->where($where)
                ->orderBy('start_date', 'desc')
                ->get()
                ;

        return $data;
    }

     /**
     * IDで取得
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->from('t_spec_item_header as header')
                ->leftJoin('m_staff as staff', 'header.update_user', '=', 'staff.id')
                ->select('header.*',
                         'staff.staff_name as update_user_name'
                        )
                ->where(['header.id' => $id])
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
            $result = $this->insertGetId([
                    'start_date' => $params['start_date'],
                    'quote_request_kbn' => $params['quote_request_kbn'],
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
                        'start_date' => $params['start_date'],
                        'quote_request_kbn' => $params['quote_request_kbn'],
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
     * 論理削除
     * @param int $id ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteById($id) {
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
}