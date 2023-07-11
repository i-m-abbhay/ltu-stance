<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;


 /**
 * 仕入先マスタ
 */
class Supplier extends Model
{
    // テーブル名
    protected $table = 'm_supplier';
    // タイムスタンプ自動更新Off
    public $timestamps = false;


    /**
     * 一覧取得(仕入先／メーカー一覧)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句
        $where = [];
        $kbn = [];
        $where[] = array('supp.del_flg', '=', config('const.flg.off'));

        if (!empty($params['supplier_name'])) {
            $where[] = array('s.supplier_name', 'LIKE', '%'.$params['supplier_name'].'%');
        }
        if (!empty($params['maker_name'])) {
            $where[] = array('s.supplier_name', 'LIKE', '%'.$params['maker_name'].'%');
        }
        if (!empty($params['corporate_number'])) {
            $where[] = array('supp.corporate_number', 'LIKE', '%'.$params['corporate_number'].'%');
        }
        if (!empty($params['from_created_date'])) {
            $where[] = array('supp.created_at', '>=', $params['from_created_date']);
        }
        if (!empty($params['to_created_date'])) {
            $where[] = array('supp.created_at', '<=', $params['to_created_date']);
        }
        if (!empty($params['direct_flg'])) {
            $kbn[] = config('const.supplierMakerKbn.direct');
        }
        if (!empty($params['supplier_flg'])) {
            $kbn[] = config('const.supplierMakerKbn.supplier');
        }
        if (!empty($params['maker_flg'])) {
            $kbn[] = config('const.supplierMakerKbn.maker');
        }

        $nameQuery = $this
                    ->from('m_supplier as s')
                    ->leftJoin('m_general', function($join) {
                        $join->on('s.juridical_code', '=', 'm_general.value_code')
                             ->where('m_general.category_code', '=', ':juridical')
                             ;
                    })
                    ->selectRaw('
                        s.id as id,
                        CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(s.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as supplier_name
                    ')
                    ->toSql()
                    ;
        
        // データ取得
        $data = $this
                ->from('m_supplier as supp')
                // ->leftJoin('m_general', function($join) {
                //     $join->on('supp.juridical_code', '=', 'm_general.value_code')
                //          ->where('m_general.category_code', '=', config('const.general.juridical'))
                //          ;
                // })
                ->leftjoin(DB::raw('('.$nameQuery.') as s'), function($join){
                    $join->on('s.id', 'supp.id');
                })
                ->setBindings([':juridical' => config('const.general.juridical')])
                // CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(s.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as supplier_name,
                ->selectRaw('
                        supp.id,
                        s.supplier_name as supplier_name,
                        supp.supplier_kana,
                        supp.tel as tel,
                        supp.fax as fax,
                        supp.product_line as product_line,
                        supp.contractor_kbn as contractor_kbn,
                        CONCAT(COALESCE(supp.address1, \'\'), COALESCE(supp.address2, \'\')) as address,
                        CASE 
                          WHEN supp.supplier_maker_kbn IS NULL 
                            THEN \'\' 
                          WHEN supp.supplier_maker_kbn = '. config('const.supplierMakerKbn.direct'). ' 
                            THEN \''. config('const.supplierMakerKbn.list.'. config('const.supplierMakerKbn.direct')). '\' 
                          WHEN supp.supplier_maker_kbn = '. config('const.supplierMakerKbn.supplier'). ' 
                            THEN \''. config('const.supplierMakerKbn.list.'. config('const.supplierMakerKbn.supplier')). '\' 
                          WHEN supp.supplier_maker_kbn = '. config('const.supplierMakerKbn.maker'). ' 
                            THEN \''. config('const.supplierMakerKbn.list.'. config('const.supplierMakerKbn.maker')). '\' 
                          END AS supplier_maker_kbn 
                        ')
                ->where($where)
                ->Where(function ($query) use ($kbn) {
                    if(count($kbn) > 1)
                    {
                        // 区分が複数の場合
                        $query->where('supp.supplier_maker_kbn', '=', $kbn[0]);
                        for ($i = 1; $i < count($kbn); $i++)
                        {
                            $query->orWhere('supp.supplier_maker_kbn', '=', $kbn[$i]);
                        }
                    }else if (count($kbn) == 1)
                    {
                        // 区分が一つの場合
                        $query->where('supp.supplier_maker_kbn', '=', $kbn[0]);
                    }
                })
                ->orderBy('id', 'asc')
                ->get()
                ;
        
        return $data;
    }

    /**
     * IDで取得
     * @param int $id 仕入先ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftJoin('m_staff', 'm_staff.id', '=', 'm_supplier.update_user')
                ->leftJoin('m_general', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->where(['m_supplier.id' => $id])
                ->selectRaw('
                    m_supplier.id as id,
                    m_supplier.supplier_name,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name,
                    m_supplier.supplier_short_name,
                    m_supplier.supplier_kana,
                    m_supplier.corporate_number,
                    m_supplier.juridical_code,
                    m_supplier.honorific,
                    m_supplier.product_line,
                    m_supplier.contractor_kbn,
                    m_supplier.supplier_maker_kbn,
                    m_supplier.payee_id,
                    m_supplier.zipcode,
                    m_supplier.address1,
                    m_supplier.address2,
                    m_supplier.pref,
                    m_supplier.tel,
                    m_supplier.fax,
                    m_supplier.email,
                    m_supplier.url,
                    m_supplier.bank_code,
                    m_supplier.branch_code,
                    m_supplier.account_type,
                    m_supplier.account_number,
                    m_supplier.account_name,
                    m_supplier.electricitydebtno,
                    m_supplier.closing_day,
                    m_supplier.payment_sight,
                    m_supplier.payment_day,
                    m_supplier.cash_rate,
                    m_supplier.check_rate,
                    m_supplier.bill_rate,
                    m_supplier.transfer_rate,
                    m_supplier.bill_min_price,
                    m_supplier.bill_deadline,
                    m_supplier.bill_fee,
                    m_supplier.bill_issuing_bank_id,
                    m_supplier.fee_kbn,
                    m_supplier.safety_org_cost,
                    m_supplier.receive_rebate,
                    m_supplier.sponsor_cost,
                    m_supplier.memo,
                    m_supplier.print_exclusion_flg,
                    m_supplier.del_flg,
                    m_supplier.created_user,
                    m_supplier.created_at,
                    m_supplier.update_user,
                    m_supplier.update_at,
                    m_staff.staff_name as update_user_name'
                )
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
                    'supplier_name' => $params['supplier_name'],
                    'supplier_short_name' => $params['supplier_short_name'],
                    'supplier_kana' => $params['supplier_kana'],
                    'corporate_number' => $params['corporate_number'],
                    'juridical_code' => $params['juridical_code'],
                    'honorific' => $params['honorific'],
                    'product_line' => $params['product_line'],
                    'contractor_kbn' => $params['contractor_kbn'],
                    'supplier_maker_kbn' => $params['supplier_maker_kbn'],
                    'payee_id' => $params['payee_id'],
                    'zipcode' => $params['zipcode'],
                    // 'pref' => $params['pref'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
                    'tel' => $params['tel'],
                    'fax' => $params['fax'],
                    'email' => $params['email'],
                    'url' => $params['url'],
                    'bank_code' => $params['bank_code'],
                    'branch_code' => $params['branch_code'],
                    'account_type' => $params['account_type'],
                    'account_number' => $params['account_number'],
                    'account_name' => $params['account_name'],
                    'electricitydebtno' => $params['electricitydebtno'],
                    'closing_day' => $params['closing_day'],
                    'payment_sight' => $params['payment_sight'],
                    'payment_day' => $params['payment_day'],
                    'cash_rate' => $params['cash_rate'],
                    'check_rate' => $params['check_rate'],
                    'bill_rate' => $params['bill_rate'],
                    'transfer_rate' => $params['transfer_rate'],
                    'bill_min_price' => $params['bill_min_price'],
                    'bill_deadline' => $params['bill_deadline'],
                    'bill_fee' => $params['bill_fee'],
                    'bill_issuing_bank_id' => $params['bill_issuing_bank_id'],
                    'fee_kbn' => $params['fee_kbn'],
                    'safety_org_cost' => $params['safety_org_cost'],
                    'receive_rebate' => $params['receive_rebate'],
                    'sponsor_cost' => $params['sponsor_cost'],
                    'memo' => $params['memo'],
                    'print_exclusion_flg' => $params['print_exclusion_flg'],
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
                        'supplier_name' => $params['supplier_name'],
                        'supplier_short_name' => $params['supplier_short_name'],
                        'supplier_kana' => $params['supplier_kana'],
                        'corporate_number' => $params['corporate_number'],
                        'juridical_code' => $params['juridical_code'],
                        'honorific' => $params['honorific'],
                        'product_line' => $params['product_line'],
                        'contractor_kbn' => $params['contractor_kbn'],
                        'supplier_maker_kbn' => $params['supplier_maker_kbn'],
                        'payee_id' => $params['payee_id'],
                        'zipcode' => $params['zipcode'],
                        // 'pref' => $params['pref'],
                        'address1' => $params['address1'],
                        'address2' => $params['address2'],
                        'tel' => $params['tel'],
                        'fax' => $params['fax'],
                        'email' => $params['email'],
                        'url' => $params['url'],
                        'bank_code' => $params['bank_code'],
                        'branch_code' => $params['branch_code'],
                        'account_type' => $params['account_type'],
                        'account_number' => $params['account_number'],
                        'account_name' => $params['account_name'],
                        'electricitydebtno' => $params['electricitydebtno'],
                        'closing_day' => $params['closing_day'],
                        'payment_sight' => $params['payment_sight'],
                        'payment_day' => $params['payment_day'],
                        'cash_rate' => $params['cash_rate'],
                        'check_rate' => $params['check_rate'],
                        'bill_rate' => $params['bill_rate'],
                        'transfer_rate' => $params['transfer_rate'],
                        'bill_min_price' => $params['bill_min_price'],
                        'bill_deadline' => $params['bill_deadline'],
                        'bill_fee' => $params['bill_fee'],
                        'bill_issuing_bank_id' => $params['bill_issuing_bank_id'],
                        'fee_kbn' => $params['fee_kbn'],
                        'safety_org_cost' => $params['safety_org_cost'],
                        'receive_rebate' => $params['receive_rebate'],
                        'sponsor_cost' => $params['sponsor_cost'],
                        'memo' => $params['memo'],
                        'print_exclusion_flg' => $params['print_exclusion_flg'],
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
     * @param int $id 仕入先ID
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


    /**
     * 区分で取得 仕入先メーカー
     *
     * @param $kbn
     * @return 仕入先データ
     */
    public function getBySupplierKbn(array $kbn) {
        

        $data = $this                
                ->Where(function ($query) use ($kbn) {
                    if(count($kbn) > 1)
                    {
                        // 区分が複数の場合
                        $query->where('supplier_maker_kbn', '=', $kbn[0]);
                        for ($i = 1; $i < count($kbn); $i++)
                        {
                            $query->orWhere('supplier_maker_kbn', '=', $kbn[$i]);
                        }
                    }else if (count($kbn) == 1)
                    {
                        // 区分が一つの場合
                        $query->where('supplier_maker_kbn', '=', $kbn[0]);
                    }
                })
                ->leftJoin('m_general', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->where('m_supplier.del_flg', '=', config('const.flg.off'))
                ->selectRaw('
                    m_supplier.id as id,
                    m_supplier.supplier_name,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name,
                    m_supplier.supplier_short_name,
                    m_supplier.supplier_kana,
                    m_supplier.corporate_number,
                    m_supplier.juridical_code,
                    m_supplier.honorific,
                    m_supplier.product_line,
                    m_supplier.contractor_kbn,
                    m_supplier.supplier_maker_kbn,
                    m_supplier.payee_id,
                    m_supplier.zipcode,
                    m_supplier.pref,
                    m_supplier.tel,
                    m_supplier.fax,
                    m_supplier.email,
                    m_supplier.url,
                    m_supplier.bank_code,
                    m_supplier.branch_code,
                    m_supplier.account_type,
                    m_supplier.account_number,
                    m_supplier.account_name,
                    m_supplier.closing_day,
                    m_supplier.payment_sight,
                    m_supplier.payment_day,
                    m_supplier.cash_rate,
                    m_supplier.check_rate,
                    m_supplier.bill_rate,
                    m_supplier.transfer_rate,
                    m_supplier.bill_min_price,
                    m_supplier.bill_deadline,
                    m_supplier.bill_fee,
                    m_supplier.bill_issuing_bank_id,
                    m_supplier.fee_kbn,
                    m_supplier.safety_org_cost,
                    m_supplier.receive_rebate,
                    m_supplier.sponsor_cost,
                    m_supplier.memo,
                    m_supplier.del_flg,
                    m_supplier.created_user,
                    m_supplier.created_at,
                    m_supplier.update_user,
                    m_supplier.update_at,
                    CONCAT(COALESCE(m_supplier.address1, \'\'), COALESCE(m_supplier.address2, \'\')) as address,
                    m_general.id as general_id,
                    m_general.category_code,
                    m_general.value_code,
                    m_general.category_name,
                    m_general.value_text_1,
                    m_general.value_text_2,
                    m_general.value_text_3,
                    m_general.value_num_1,
                    m_general.value_num_2,
                    m_general.value_num_3,
                    m_general.display_order
                ')
                ->get()
                ;

        return $data;
    }
    
    /**
     * 区分で取得 仕入先メーカー
     *
     * @param $kbn
     * @return 仕入先データ
     */
    public function getBySupplierKbnThin(array $kbn) {

        $data = $this                
                ->Where(function ($query) use ($kbn) {
                    if(count($kbn) > 1)
                    {
                        // 区分が複数の場合
                        $query->where('supplier_maker_kbn', '=', $kbn[0]);
                        for ($i = 1; $i < count($kbn); $i++)
                        {
                            $query->orWhere('supplier_maker_kbn', '=', $kbn[$i]);
                        }
                    }else if (count($kbn) == 1)
                    {
                        // 区分が一つの場合
                        $query->where('supplier_maker_kbn', '=', $kbn[0]);
                    }
                })
                ->leftJoin('m_general', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->where('m_supplier.del_flg', '=', config('const.flg.off'))
                ->selectRaw('
                    m_supplier.id as id,
                    m_supplier.supplier_name,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name
                ')
                ->get()
                ;

        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @return 
     */
    public function getComboList() {
        // Where句
        $where = [];
        $where[] = array('m_supplier.del_flg', '=', config('const.flg.off'));


        $data = $this
                ->where($where)
                ->leftJoin('m_general', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->selectRaw('
                    m_supplier.id,
                    m_supplier.supplier_name,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name,
                    m_supplier.supplier_short_name,
                    m_supplier.supplier_kana,
                    m_supplier.corporate_number,
                    m_supplier.juridical_code,
                    m_supplier.honorific,
                    m_supplier.product_line,
                    m_supplier.contractor_kbn,
                    m_supplier.supplier_maker_kbn,
                    m_supplier.payee_id,
                    m_supplier.zipcode,
                    m_supplier.pref,
                    m_supplier.address1,
                    m_supplier.address2,
                    m_supplier.tel,
                    m_supplier.fax,
                    m_supplier.email,
                    m_supplier.url,
                    m_supplier.bank_code,
                    m_supplier.branch_code,
                    m_supplier.account_type,
                    m_supplier.account_number,
                    m_supplier.account_name,
                    m_supplier.closing_day,
                    m_supplier.payment_sight,
                    m_supplier.payment_day,
                    m_supplier.cash_rate,
                    m_supplier.check_rate,
                    m_supplier.bill_rate,
                    m_supplier.transfer_rate,
                    m_supplier.bill_min_price,
                    m_supplier.bill_deadline,
                    m_supplier.bill_fee,
                    m_supplier.bill_issuing_bank_id,
                    m_supplier.fee_kbn,
                    m_supplier.safety_org_cost,
                    m_supplier.receive_rebate,
                    m_supplier.sponsor_cost,
                    m_supplier.memo
                ')
                ->get()
                ;

        return $data;
    }

    /**
     * 入荷コンボボックス用
     *
     * @param $kbn
     * @return 仕入先データ
     */
    public function getOrder(array $kbn) {
         // Where句作成
         $where = [];
         $where[] = array('o.arrival_complete_flg', '=', config('const.flg.off'));
         $where[] = array('o.del_flg', '=', config('const.flg.off'));
         $where[] = array('o.status', '=', 4);
         $where[] = array('o.delivery_address_kbn', '=', 2);
         $where[] = array('od.product_id', '<>', 0);
         $where[] = array('p.intangible_flg ', '<>', 1);

        $data =  DB::table('t_order_detail as od')
                ->join('t_order as o', 'od.order_no', '=', 'o.order_no')
                ->leftJoin(DB::raw('(select order_no, (count(product_id) - 1) as product_count from t_order_detail group by order_no) as odt'), 'odt.order_no', '=', 'o.order_no')
                ->leftjoin('t_matter as m', 'o.matter_no', '=', 'm.matter_no')
                ->leftjoin('m_staff as s', 'm.staff_id', '=', 's.id')
                ->leftjoin('m_department as d', 'm.department_id', '=', 'd.id')
                ->leftJoin('m_customer as c', 'c.id', '=', 'm.customer_id')
                ->leftJoin('m_general as c_general', function($join) {
                    $join->on('c.juridical_code', '=', 'c_general.value_code')
                        ->where('c_general.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftjoin('m_product as p', 'p.id', '=', 'od.product_id')
                ->leftjoin('m_warehouse as w', 'o.delivery_address_id', '=', 'w.id')
                ->leftjoin('m_address as a', 'o.delivery_address_id', '=', 'a.id')

                ->leftjoin('m_supplier as m_supplier', 'o.supplier_id', '=', 'm_supplier.id')
                ->leftJoin('m_general as g', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'g.value_code')
                        ->where('g.category_code', '=', config('const.general.juridical'))
                        ;
                })
                ->leftjoin('m_supplier as maker', 'o.maker_id', '=', 'maker.id')
                ->leftJoin('m_general as maker_general', function($join) {
                    $join->on('maker.juridical_code', '=', 'maker_general.value_code')
                        ->where('maker_general.category_code', '=', config('const.general.juridical'))
                        ;
                })        
                ->Where(function ($query) use ($kbn) {
                    if(count($kbn) > 1)
                    {
                        // 区分が複数の場合
                        $query->where('m_supplier.supplier_maker_kbn', '=', $kbn[0]);
                        for ($i = 1; $i < count($kbn); $i++)
                        {
                            $query->orWhere('m_supplier.supplier_maker_kbn', '=', $kbn[$i]);
                        }
                    }else if (count($kbn) == 1)
                    {
                        // 区分が一つの場合
                        $query->where('m_supplier.supplier_maker_kbn', '=', $kbn[0]);
                    }
                })
                ->leftJoin('m_general', function($join) {
                    $join->on('m_supplier.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->where($where)
                ->where('m_supplier.del_flg', '=', config('const.flg.off'))
                ->selectRaw('
                    m_supplier.id as id,
                    m_supplier.supplier_name,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_supplier.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name,
                    m_supplier.supplier_short_name,
                    m_supplier.supplier_kana,
                    m_supplier.corporate_number,
                    m_supplier.juridical_code,
                    m_supplier.honorific,
                    m_supplier.product_line,
                    m_supplier.contractor_kbn,
                    m_supplier.supplier_maker_kbn,
                    m_supplier.payee_id,
                    m_supplier.zipcode,
                    m_supplier.pref,
                    m_supplier.tel,
                    m_supplier.fax,
                    m_supplier.email,
                    m_supplier.url,
                    m_supplier.bank_code,
                    m_supplier.branch_code,
                    m_supplier.account_type,
                    m_supplier.account_number,
                    m_supplier.account_name,
                    m_supplier.closing_day,
                    m_supplier.payment_sight,
                    m_supplier.payment_day,
                    m_supplier.cash_rate,
                    m_supplier.check_rate,
                    m_supplier.bill_rate,
                    m_supplier.transfer_rate,
                    m_supplier.bill_min_price,
                    m_supplier.bill_deadline,
                    m_supplier.bill_fee,
                    m_supplier.bill_issuing_bank_id,
                    m_supplier.fee_kbn,
                    m_supplier.safety_org_cost,
                    m_supplier.receive_rebate,
                    m_supplier.sponsor_cost,
                    m_supplier.memo,
                    m_supplier.del_flg,
                    m_supplier.created_user,
                    m_supplier.created_at,
                    m_supplier.update_user,
                    m_supplier.update_at,
                    CONCAT(COALESCE(m_supplier.address1, \'\'), COALESCE(m_supplier.address2, \'\')) as address,
                    m_general.id as general_id,
                    m_general.category_code,
                    m_general.value_code,
                    m_general.category_name,
                    m_general.value_text_1,
                    m_general.value_text_2,
                    m_general.value_text_3,
                    m_general.value_num_1,
                    m_general.value_num_2,
                    m_general.value_num_3,
                    m_general.display_order
                ')
                ->distinct()
                ->get()
                ;

        return $data;
    }


    /**
     * 仕入先IDからメーカ取得
     * 入荷 or 返品が存在するメーカーのみ
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getMakerBySupplierId($supplier_id)
    {
        // Where句
        $where = [];
        $where[] = array('maker.del_flg', '=', config('const.flg.off'));
        // $where[] = array('maker.id', '=', $supplier_id);

        // 入荷 or 返品が存在するメーカーID
        $arrivalMakerIDs = $this
                ->from('t_arrival as ar')
                ->leftJoin('t_order_detail AS od', 'ar.order_detail_id', '=', 'od.id')
                ->leftJoin('t_warehouse_move AS wm', 'wm.order_detail_id', '=', 'od.id')
                ->leftJoin('t_return AS re', 're.warehouse_move_id', '=', 'wm.id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->where([
                    ['od.supplier_id', '=', $supplier_id]
                    // ['pro.del_flg', '=', config('const.flg.off')]
                ])
                ->select([
                    'od.maker_id'
                ])
                ->distinct()
                ->get()
                ;

        $returnMakerIDs = $this
                ->from('t_return as re')
                ->leftJoin('t_warehouse_move as wm', 'wm.id', '=', 're.warehouse_move_id')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->where([
                    ['od.supplier_id', '=', $supplier_id]
                    // ['pro.del_flg', '=', config('const.flg.off')]
                ])
                ->select([
                    'od.maker_id'
                ])
                ->distinct()
                ->get()
                ;

        $makerIDs = array_values(array_unique(array_merge(collect($arrivalMakerIDs)->pluck('maker_id')->toArray(), collect($returnMakerIDs)->pluck('maker_id')->toArray())));

        $data = $this
                ->from('m_supplier AS maker')
                ->leftJoin('m_general', function($join) {
                    $join->on('maker.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->whereIn('maker.id', $makerIDs)
                ->where($where)
                ->selectRaw('
                    maker.id as id,
                    maker.supplier_name,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(maker.supplier_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as juridical_supplier_name,
                    maker.supplier_short_name,
                    maker.supplier_kana
                ')
                ->distinct()
                ->get()
                ;

        return $data;
    }
}