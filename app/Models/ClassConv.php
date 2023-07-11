<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 分類変換テーブル
 */
class ClassConv extends Model
{
    // テーブル名
    protected $table = 'm_class_conv';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 中分類名から各分類IDを取得
     *
     * @return void
     */
    public function convertClassIdByMiddleName($middleName)
    {
        $where = [];
        $where[] = array('m_class_conv.middle_name', '=', $middleName);

        $data = $this
                ->where($where)
                ->select([
                    'm_class_conv.id',
                    'm_class_conv.construction_id',
                    'm_class_conv.class_big_id',
                    'm_class_conv.class_middle_id',
                    'm_class_conv.class_small_id',
                ])
                ->first()
                ;

        return $data;
    }



}
