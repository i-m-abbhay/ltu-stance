<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 自社銀行マスタ
 */
class OwnBank extends Model
{
    // テーブル名
    protected $table = 'm_own_bank';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;
    


    /**
     * コンボボックス用データ取得
     *
     * @return 
     */
    public function getComboList() {
        // Where句作成
        $where = [];
        $where[] = array('own_bank.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->from('m_own_bank as own_bank')
                ->leftJoin('m_bank as bank', function($join) {
                    $join->on('own_bank.bank_code', '=', 'bank.bank_code')
                         ->on('own_bank.branch_code', '=', 'bank.branch_code')
                         ;
                })
                ->where($where)
                ->orderBy('own_bank.id', 'asc')
                ->selectRaw('
                            own_bank.id as id,
                            own_bank.bank_code,
                            own_bank.branch_code,
                            CONCAT(COALESCE(bank.bank_name, \'\'), \'／\', COALESCE(bank.branch_name, \'\')) as bank_name,
                            account_type,
                            account_number,
                            account_name
                        ')
                ->get()
                ;
        
        return $data;
    }
}
