<?php

namespace App\Models;

use DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;


 /**
 * 仕入先メーカー対照マスタ
 */
class SupplierMaker extends Model
{
    // テーブル名
    protected $table = 'm_supplier_maker';
    // タイムスタンプ自動更新Off
    public $timestamps = false;



    public function upsert($params) 
    {
        $result = false;
        try {
            $result = $this
                    ->updateOrInsert(
                        ['maker_id' => $params['maker_id'], 'supplier_id' => $params['supplier_id']],
                        [
                            'maker_id' => $params['maker_id'],
                            'supplier_id' => $params['supplier_id'],
                            'priority_rank' => $params['priority_rank'],
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
     * コンボボックス用データ取得
     * 
     * @param $makerId
     * @return 
     */
    public function getComboListByMakerId($makerId = null) {
        // Where句
        $where = [];
        $where[] = array('m_supplier.del_flg', '=', config('const.flg.off'));
        $subWhere = $where;
        if(!empty($makerId)){
            $where[] = array('m_supplier_maker.maker_id', '=', $makerId);
            $subWhere[] = array('m_supplier.id', '=', $makerId);
        }

        $directSql = DB::table('m_supplier')
            ->leftJoin('m_general', function($join) {
                $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'))
                    ;
            })
            ->where($subWhere)
            ->where('supplier_maker_kbn', '=', config('const.supplierMakerKbn.direct'))
            ->select([
                DB::raw('CONCAT(m_supplier.id, \'_\', m_supplier.id) AS unique_key'),
                DB::raw('NULL AS id'),
                'm_supplier.id as maker_id',
                'm_supplier.id AS supplier_id',
                DB::raw('0 AS priority_rank'),  // orderした時に直接取引が一番上にくるようにする為
                'm_supplier.supplier_name',
                DB::raw('CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(value_text_3, \'\')) as juridical_supplier_name'),   // 法人格結合
                'm_supplier.supplier_short_name',
                'm_supplier.supplier_kana',
                'm_supplier.product_line',
                'm_supplier.supplier_maker_kbn',
                'm_supplier.payee_id',
                'm_supplier.zipcode',
                'm_supplier.pref',
                'm_supplier.address1',
                'm_supplier.address2',
                'm_supplier.tel',
                'm_supplier.fax',
                'm_supplier.bank_code',
                'm_supplier.branch_code',
                'm_supplier.account_type',
                'm_supplier.account_number',
                'm_supplier.account_name',
                'm_supplier.closing_day',
                'm_supplier.payment_sight',
                'm_supplier.payment_day',
                'm_supplier.cash_rate',
                'm_supplier.check_rate',
                'm_supplier.bill_rate',
                'm_supplier.transfer_rate',
                'm_supplier.bill_min_price',
                'm_supplier.bill_deadline',
                'm_supplier.bill_fee',
                'm_supplier.bill_issuing_bank_id',
                'm_supplier.fee_kbn',
                'm_supplier.safety_org_cost',
                'm_supplier.receive_rebate',
                'm_supplier.sponsor_cost',
                'm_supplier.memo',
            ])
            ;

        $data = $this
                ->join('m_supplier', 'm_supplier_maker.supplier_id', 'm_supplier.id')
                ->leftJoin('m_general', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->select([
                    DB::raw('CONCAT(m_supplier_maker.maker_id, \'_\', m_supplier_maker.supplier_id) AS unique_key'),
                    'm_supplier_maker.id',
                    'm_supplier_maker.maker_id',
                    'm_supplier_maker.supplier_id',
                    'm_supplier_maker.priority_rank',
                    'm_supplier.supplier_name',
                    DB::raw('CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name'),
                    'm_supplier.supplier_short_name',
                    'm_supplier.supplier_kana',
                    'm_supplier.product_line',
                    'm_supplier.supplier_maker_kbn',
                    'm_supplier.payee_id',
                    'm_supplier.zipcode',
                    'm_supplier.pref',
                    'm_supplier.address1',
                    'm_supplier.address2',
                    'm_supplier.tel',
                    'm_supplier.fax',
                    'm_supplier.bank_code',
                    'm_supplier.branch_code',
                    'm_supplier.account_type',
                    'm_supplier.account_number',
                    'm_supplier.account_name',
                    'm_supplier.closing_day',
                    'm_supplier.payment_sight',
                    'm_supplier.payment_day',
                    'm_supplier.cash_rate',
                    'm_supplier.check_rate',
                    'm_supplier.bill_rate',
                    'm_supplier.transfer_rate',
                    'm_supplier.bill_min_price',
                    'm_supplier.bill_deadline',
                    'm_supplier.bill_fee',
                    'm_supplier.bill_issuing_bank_id',
                    'm_supplier.fee_kbn',
                    'm_supplier.safety_org_cost',
                    'm_supplier.receive_rebate',
                    'm_supplier.sponsor_cost',
                    'm_supplier.memo',
                ])
                ->union($directSql)
                ->where($where)
                ->orderBy('priority_rank', 'asc')
                ->get()
                ;
        // 重複を取り除く
        $data = $data->unique('unique_key');
        return $data;
    }
    /**
     * コンボボックス用データ取得
     * 
     * @param $makerId
     * @return 
     */
    public function getComboListByMakerIdThin($makerId = null) {
        // Where句
        $where = [];
        $where[] = array('m_supplier.del_flg', '=', config('const.flg.off'));
        $subWhere = $where;
        if(!empty($makerId)){
            $where[] = array('m_supplier_maker.maker_id', '=', $makerId);
            $subWhere[] = array('m_supplier.id', '=', $makerId);
        }

        $directSql = DB::table('m_supplier')
            // ->leftJoin('m_general', function($join) {
            //     $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
            //         ->where('m_general.category_code', '=', config('const.general.juridical'))
            //         ;
            // })
            ->where($subWhere)
            ->where('supplier_maker_kbn', '=', config('const.supplierMakerKbn.direct'))
            ->select([
                DB::raw('CONCAT(m_supplier.id, \'_\', m_supplier.id) AS unique_key'),
                DB::raw('NULL AS id'),
                'm_supplier.id as maker_id',
                'm_supplier.id AS supplier_id',
                DB::raw('0 AS priority_rank'),  // orderした時に直接取引が一番上にくるようにする為
                // 'm_supplier.supplier_name',
                // DB::raw('CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(value_text_3, \'\')) as juridical_supplier_name'),   // 法人格結合
                // 'm_supplier.supplier_short_name',
                // 'm_supplier.supplier_kana',
                // 'm_supplier.product_line',
                'm_supplier.supplier_maker_kbn',
                // 'm_supplier.payee_id',
                // 'm_supplier.zipcode',
                // 'm_supplier.pref',
                // 'm_supplier.address1',
                // 'm_supplier.address2',
                // 'm_supplier.tel',
                // 'm_supplier.fax',
                // 'm_supplier.bank_code',
                // 'm_supplier.branch_code',
                // 'm_supplier.account_type',
                // 'm_supplier.account_number',
                // 'm_supplier.account_name',
                // 'm_supplier.closing_day',
                // 'm_supplier.payment_sight',
                // 'm_supplier.payment_day',
                // 'm_supplier.cash_rate',
                // 'm_supplier.check_rate',
                // 'm_supplier.bill_rate',
                // 'm_supplier.transfer_rate',
                // 'm_supplier.bill_min_price',
                // 'm_supplier.bill_deadline',
                // 'm_supplier.bill_fee',
                // 'm_supplier.bill_issuing_bank_id',
                // 'm_supplier.fee_kbn',
                // 'm_supplier.safety_org_cost',
                // 'm_supplier.receive_rebate',
                // 'm_supplier.sponsor_cost',
                // 'm_supplier.memo',
            ])
            ;

        $data = $this
                ->join('m_supplier', 'm_supplier_maker.supplier_id', 'm_supplier.id')
                // ->leftJoin('m_general', function($join) {
                //     $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                //          ->where('m_general.category_code', '=', config('const.general.juridical'))
                //          ;
                // })
                ->select([
                    DB::raw('CONCAT(m_supplier_maker.maker_id, \'_\', m_supplier_maker.supplier_id) AS unique_key'),
                    'm_supplier_maker.id',
                    'm_supplier_maker.maker_id',
                    'm_supplier_maker.supplier_id',
                    'm_supplier_maker.priority_rank',
                    // 'm_supplier.supplier_name',
                    // DB::raw('CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name'),
                    // 'm_supplier.supplier_short_name',
                    // 'm_supplier.supplier_kana',
                    // 'm_supplier.product_line',
                    'm_supplier.supplier_maker_kbn',
                    // 'm_supplier.payee_id',
                    // 'm_supplier.zipcode',
                    // 'm_supplier.pref',
                    // 'm_supplier.address1',
                    // 'm_supplier.address2',
                    // 'm_supplier.tel',
                    // 'm_supplier.fax',
                    // 'm_supplier.bank_code',
                    // 'm_supplier.branch_code',
                    // 'm_supplier.account_type',
                    // 'm_supplier.account_number',
                    // 'm_supplier.account_name',
                    // 'm_supplier.closing_day',
                    // 'm_supplier.payment_sight',
                    // 'm_supplier.payment_day',
                    // 'm_supplier.cash_rate',
                    // 'm_supplier.check_rate',
                    // 'm_supplier.bill_rate',
                    // 'm_supplier.transfer_rate',
                    // 'm_supplier.bill_min_price',
                    // 'm_supplier.bill_deadline',
                    // 'm_supplier.bill_fee',
                    // 'm_supplier.bill_issuing_bank_id',
                    // 'm_supplier.fee_kbn',
                    // 'm_supplier.safety_org_cost',
                    // 'm_supplier.receive_rebate',
                    // 'm_supplier.sponsor_cost',
                    // 'm_supplier.memo',
                ])
                ->union($directSql)
                ->where($where)
                ->orderBy('priority_rank', 'asc')
                ->get()
                ;
        // 重複を取り除く
        $data = $data->unique('unique_key');
        return $data;
    }

    /**
     * メーカーIDから対照リスト取得
     *
     * @param  $id
     * @return void
     */
    public function getByMakerId($id) 
    {
        $where = [];
        $where[] = array('maker.del_flg', '=', config('const.flg.off'));
        $where[] = array('sup.del_flg', '=', config('const.flg.off'));
        $where[] = array('maker.id', '=', $id);

        $data = $this
                ->from('m_supplier_maker as sup_maker')
                ->leftJoin('m_supplier as maker', 'maker.id', '=', 'sup_maker.maker_id')
                ->leftJoin('m_supplier as sup', 'sup.id', '=', 'sup_maker.supplier_id')
                ->leftJoin('m_general', function($join) {
                    $join->on('sup.juridical_code', '=', 'm_general.value_code')
                            ->where('m_general.category_code', '=', config('const.general.juridical'))
                            ;
                })
                ->where($where)
                ->selectRaw('
                    sup_maker.id as sup_maker_id,
                    maker.id as maker_id,
                    sup.id as id,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(sup.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as supplier_name,
                    sup.supplier_kana,
                    sup.product_line,
                    sup.contractor_kbn,
                    sup_maker.priority_rank,
                    CONCAT(COALESCE(sup.address1, \'\'), COALESCE(sup.address2, \'\')) as address
                ')
                ->orderBy('sup_maker.priority_rank', 'asc')
                ->get()
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
            $result = $this->insert([
                    'maker_id' => $params['maker_id'],
                    'supplier_id' => $params['supplier_id'],
                    'priority_rank' => $params['priority_rank'],
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
                        'maker_id' => $params['maker_id'],
                        'supplier_id' => $params['supplier_id'],
                        'priority_rank' => $params['priority_rank'],
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
     * 物理削除
     * @param int $id メーカーID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteByMakerId($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('maker_id', $id)
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     * @param int $id 仕入先ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteBySupplierId($id) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('supplier_id', $id)
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * メーカーID, 仕入先IDで物理削除
     *
     * @param $params
     * @return void
     */
    public function deleteBySupMakerId($params) 
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['maker_id'], config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('maker_id', $params['maker_id'])
                ->where('supplier_id', $params['supplier_id'])
                ->delete();
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 仕入先メーカー対照マスタの存在チェック
     *
     * @param $id　仕入先ID
     * @return boolean
     */
    public function isExist($id) 
    {
        $result = $this
                    ->where('supplier_id', '=', $id)
                    ->orWhere('maker_id', '=', $id)
                    ->exists()
                    ;

        return $result;
    }
  
}