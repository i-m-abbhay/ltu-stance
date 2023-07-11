<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 会社マスタ
 */
class Company extends Model
{
    // テーブル名
    protected $table = 'm_company';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * IDで取得
     * @param int $id 会社ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->where(['id' => $id])
                ->first()
                ;

        return $data;
    }

}
