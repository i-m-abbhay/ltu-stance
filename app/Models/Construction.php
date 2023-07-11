<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 工事区分マスタ
 */
class Construction extends Model
{
    // テーブル名
    protected $table = 'm_construction';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    // 全データ取得
    public function getAll(){
        $data = $this->where('del_flg', '=', config('const.flg.off'))->get();
        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @param boolean $containAddFlg 追加部材用データを含むかどうか（デフォルト：含まない）
     * @return void
     */
    public function getComboList($containAddFlg = false) {
        // Where句
        $where = [];
        $where[] = array('m_construction.del_flg', '=', config('const.flg.off'));
        if (!$containAddFlg) {
            $where[] = array('m_construction.add_flg', '=', config('const.flg.off'));
        }
        
        $data = $this
                ->where($where)
                ->orderBy('display_order', 'asc')
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
                ->orderBy('display_order', 'asc')
                ->select([
                    'id                AS construction_id',
                    'construction_name AS construction_name'
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 追加部材の工事区分データを取得（必ず1件の想定）
     *
     * @return void
     */
    public function getAddFlgData() {
        // Where句
        $where = [];
        $where[] = array('m_construction.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_construction.add_flg', '=', config('const.flg.on'));
        
        $data = $this
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * 見積依頼項目フラグのあるリスト取得
     *
     * @return void
     */
    public function getQuoteRequestFlgList() 
    {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('quote_request_flg', '=', config('const.flg.on'));
        
        $data = $this
                ->where($where)
                ->orderBy('display_order')
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
    public function getConstructionBySupplierId($supplier_id)
    {
        $where = [];

        $supplierData = $this
                        ->from('m_supplier')
                        ->where('id', '=', $supplier_id)
                        ->first()
                        ;

        $Ids = json_decode($supplierData['contractor_kbn'], true);

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
}
