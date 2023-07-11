<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 大分類マスタ
 */
class ClassBig extends Model
{
    // テーブル名
    protected $table = 'm_class_big';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    public function getComboList() {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        
        $data = $this
                ->where($where)
                ->get()
                ;

        return $data;
    }

    /**
     * 見積依頼項目データ取得
     *
     * @param int $quoteRequestFlg  見積依頼項目フラグ const.flg
     * @return void
     */
    public function getQreqData($quoteRequestFlg = null) {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        if (!is_null($quoteRequestFlg)) {
            $where[] = array('quote_request_flg', '=', $quoteRequestFlg);
        }

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->select([
                    'id             AS class_big_id',
                    'class_big_name AS class_big_name'
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 工事区分データ取得
     *
     * @param int $quoteRequestFlg  見積依頼項目フラグ const.flg
     * @return void
     */
    public function getConstructData($quoteRequestFlg = null) {
        // Where句作成
        $where = [];
        $where[] = array('m_class_big.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_construction.del_flg', '=', config('const.flg.off'));
        if (!is_null($quoteRequestFlg)) {
            $where[] = array('m_class_big.quote_request_flg', '=', $quoteRequestFlg);
        }

        // データ取得
        $data = $this
                ->leftjoin('m_construction_big', 'm_construction_big.class_big_id', 'm_class_big.id')
                ->leftjoin('m_construction', 'm_construction.id', 'm_construction_big.construction_id')
                ->where($where)
                ->orderBy('m_construction.display_order', 'asc')
                ->select([
                    'm_class_big.id                    AS class_big_id',
                    'm_class_big.class_big_name        AS class_big_name',
                    'm_construction.id                 AS construction_id',
                    'm_construction.construction_name  AS construction_name',
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 中分類・小分類データ取得
     *
     * @param int $quoteRequestFlg  見積依頼項目フラグ const.flg
     * @return void
     */
    public function getMiddleSmall($quoteRequestFlg = null) {
        // Where句作成
        $where = [];
        $where[] = array('m_class_big.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_class_middle.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_class_small.del_flg', '=', config('const.flg.off'));
        if (!is_null($quoteRequestFlg)) {
            $where[] = array('m_class_big.quote_request_flg', '=', $quoteRequestFlg);
        }

        // データ取得
        $data = $this
                ->leftjoin('m_class_middle', 'm_class_middle.class_big_id', 'm_class_big.id')
                ->leftjoin('m_class_small', 'm_class_small.class_middle_id', 'm_class_middle.id')
                ->where($where)
                ->orderBy('m_class_big.id', 'asc')
                ->orderBy('m_class_middle.id', 'asc')
                ->orderBy('m_class_small.id', 'asc')
                ->select([
                    'm_class_big.id                    AS class_big_id',
                    'm_class_big.class_big_name        AS class_big_name',
                    'm_class_middle.id                 AS class_middle_id',
                    'm_class_middle.class_middle_name  AS class_middle_name',
                    'm_class_small.id                  AS class_small_id',
                    'm_class_small.class_small_name    AS class_small_name',
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     *  大分類データ取得　（フォーマット区分付）
     *
     * @param int $quoteRequestFlg  見積依頼項目フラグ const.flg
     * @return void
     */
    public function getProductClass($quoteRequestFlg = null) {
        // Where句作成
        $where = [];
        $where[] = array('m_class_big.del_flg', '=', config('const.flg.off'));
        if (!is_null($quoteRequestFlg)) {
            $where[] = array('quote_request_flg', '=', $quoteRequestFlg);
        }

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('m_class_big.id', 'asc')
                ->select([
                    'm_class_big.id             AS class_big_id',
                    'm_class_big.class_big_name AS class_big_name',
                    'm_class_big.format_kbn     AS format_kbn',
                ])
                ->get()
                ;
        
        return $data;
    }


    /**
     * 仕入先IDから大分類リスト取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getClassBigBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('big.del_flg', '=', config('const.flg.off'));

        $supplierData = $this
                        ->from('m_supplier')
                        ->where('id', '=', $supplier_id)
                        ->first()
                        ;

        $Ids = json_decode($supplierData['product_line'], true);
        
        if (!empty($Ids)) {
            $data = $this
                    ->whereIn('id', $Ids)
                    ->orderBy('id')
                    ->get()
                    ;
        } else {
            $data = [];
        }

        return $data;
    }


    /**
     * フォーマット区分で取得
     * 
     * @param [type] $formatKbn config('const.classBigFormatKbn')から選択
     * @return void
     */
    public function getByFormatKbn($formatKbn) 
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('format_kbn', '=', $formatKbn);

        $data = $this
                ->where($where)
                ->get()
                ;
                
        return $data;
    }

    /**
     * IDと大分類名のみを取得
     *
     * @return void
     */
    public function getIdNameList() {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->select([
                    'id AS class_big_id',
                    'class_big_name',
                ])
                ->get()
                ;

        return $data;
    }
}
