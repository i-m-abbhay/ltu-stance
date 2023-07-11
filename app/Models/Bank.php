<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 銀行マスタ
 */
class Bank extends Model
{
    // テーブル名
    protected $table = 'm_bank';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;


    /**
     * コンボボックス用データ取得
     *
     * @return 
     */
    public function getComboList()
    {
        // Where句作成
        $where = [];
        $where[] = array('bank.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->from('m_bank as bank')
            ->where($where)
            ->orderBy('bank.id', 'asc')
            ->selectRaw('
                            bank.id as id,
                            bank.bank_code,
                            bank.branch_code,
                            bank.bank_name,
                            bank.branch_name,
                            CONCAT(COALESCE(bank.bank_name, \'\'), \'／\', COALESCE(bank.branch_name, \'\')) as join_name
                           ')
            ->get();

        return $data;
    }

    /**
     * 銀行リストを取得
     *
     * @return 
     */
    public function getBankList()
    {
        // Where句作成
        $where = [];
        $where[] = array('bank.del_flg', '=', config('const.flg.off'));
        // $where[] = array('bank.branch_code', '=', '000');

        // データ取得
        $data = $this
            ->from('m_bank as bank')
            ->where($where)
            ->orderBy('bank.bank_code', 'asc')
            // bank.id as id,
            ->selectRaw('
                    bank.bank_code,
                    MIN(bank.bank_name) as bank_name
                ')
            ->groupBy('bank_code')
            ->get();

        return $data;
    }

    /**
     * 支店リストを取得
     *
     * @return 
     */
    public function getBankBranchList()
    {
        // Where句作成
        $where = [];
        $where[] = array('bank.del_flg', '=', config('const.flg.off'));
        // $where[] = array('bank.branch_code', '>', '000');

        // データ取得
        $data = $this
            ->from('m_bank as bank')
            ->where($where)
            ->orderBy('bank.id', 'asc')
            ->selectRaw('
                    bank.id as id,
                    bank.bank_code,
                    bank.branch_code,
                    bank.bank_name,
                    bank.branch_name,
                    CONCAT(COALESCE(bank.bank_name, \'\'), \'／\', COALESCE(bank.branch_name, \'\')) as join_name
                ')
            ->get();

        return $data;
    }

    /**
     * 銀行コードから支店リストを取得
     *
     * @return 
     */
    public function getBranchByBankCode($bankCode) {
        // Where句作成
        $where = [];
        $where[] = array('bank.del_flg', '=', config('const.flg.off'));
        $where[] = array('bank.bank_code', '=', $bankCode);
        // $where[] = array('bank.branch_code', '>', '000');
        
        // データ取得
        $data = $this
                ->from('m_bank as bank')
                ->where($where)
                ->orderBy('bank.id', 'asc')
                ->selectRaw('
                    bank.id as id,
                    bank.bank_code,
                    bank.branch_code,
                    bank.bank_name,
                    bank.branch_name,
                    CONCAT(COALESCE(bank.bank_name, \'\'), \'／\', COALESCE(bank.branch_name, \'\')) as join_name
                ')
                ->get()
                ;
        
        return $data;
    }

    /**
     * 銀行コードから支店リストを取得
     *
     * @return 
     */
    public function getBranchListByBankCode(array $bankCodeList) {
        // Where句作成
        $where = [];
        $where[] = array('bank.del_flg', '=', config('const.flg.off'));
        // $where[] = array('bank.bank_code', '=', $bankCodeList);
        // $where[] = array('bank.branch_code', '>', '000');
        
        // データ取得
        $data = $this
                ->from('m_bank as bank')
                ->where($where)
                ->whereIn('bank_code', $bankCodeList)
                ->orderBy('bank.id', 'asc')
                ->selectRaw('
                    bank.id as id,
                    bank.bank_code,
                    bank.branch_code,
                    bank.bank_name,
                    bank.branch_name,
                    CONCAT(COALESCE(bank.bank_name, \'\'), \'／\', COALESCE(bank.branch_name, \'\')) as join_name
                ')
                ->get()
                ;
        
        return $data;
    }

    /**
     * データ取得
     *
     * @return 
     */
    public function getBankBranch($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('bank.del_flg', '=', config('const.flg.off'));
        if (array_key_exists('bank_code', $params)) {
            $where[] = array('bank.bank_code', '=', $params['bank_code']);
        }
        if (array_key_exists('branch_code', $params)) {
            $where[] = array('bank.branch_code', '=', $params['branch_code']);
        }

        // データ取得
        $data = $this
            ->from('m_bank as bank')
            ->where($where)
            ->orderBy('bank.id', 'asc')
            ->selectRaw('
                    *
                ')
            ->first();

        return $data;
    }
}
