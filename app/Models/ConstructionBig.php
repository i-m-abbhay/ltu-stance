<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 工事区分大分類マスタ
 */
class ConstructionBig extends Model
{
    // テーブル名
    protected $table = 'm_construction_big';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * データ取得
     *
     * @return void
     */
    public function getDataList() {
        // Where句
        $where = [];
        $where[] = array('m_construction_big.del_flg', '=', config('const.flg.off'));
        
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->get()
                ;

        return $data;
    }
}
