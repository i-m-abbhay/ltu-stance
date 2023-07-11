<?php

namespace App\Models;

use App\Libs\Common;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * 得意先マスタ
 */
class Customer extends Model
{
    // テーブル名
    protected $table = 'm_customer';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'customer_name',
        'customer_short_name',
        'customer_kana',
        'corporate_number',
        'company_category',
        'zipcode',
        'address1',
        'address2',
        'tel',
        'fax',
        'email',
        'url',
        'latitude_jp',
        'longitude_jp',
        'latitude_world',
        'longitude_world',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];


    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {
        // Where句作成
        $where = [];
        $whereDate = [];
        $hasDate = false;
        $where[] = array('m_customer.del_flg', '=', $params['del_flg']);
        if (!empty($params['customer_name'])) {
            $where[] = array('cName.customer_name', 'LIKE', '%' . $params['customer_name'] . '%');
        }
        if (!empty($params['matter_no'])) {
            $where[] = array('mat.matter_no', '=', $params['matter_no']);
        }
        if (!empty($params['matter_name'])) {
            $where[] = array('mat.matter_name', 'LIKE', '%' . $params['matter_name'] . '%');
        }
        if (!empty($params['department_name'])) {
            $where[] = array('dep.department_name', 'LIKE', '%' . $params['department_name'] . '%');
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('st.staff_name', 'LIKE', '%' . $params['staff_name'] . '%');
        }
        if (!empty($params['from_date'])) {
            $hasDate = true;
            $whereDate[] = array('m_customer.created_at', '>=', $params['from_date'] . ' 00:00:00');
        }
        if (!empty($params['to_date'])) {
            $hasDate = true;
            $whereDate[] = array('m_customer.created_at', '<=', $params['to_date'] . ' 23:59:59');
        }

        $cNameQuery = $this
            ->from('m_customer as cus')
            ->leftJoin('m_general', function ($join) {
                $join->on('cus.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', ':juridical');
            })
            ->selectRaw('
                            cus.id as customer_id,
                            CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name
                        ')
            ->toSql();

        // クエリ作成
        $query = $this
            ->leftJoin('m_department as dep', 'm_customer.charge_department_id', '=', 'dep.id')
            ->leftJoin('m_staff as st', 'm_customer.charge_staff_id', '=', 'st.id')
            // ->leftJoin('m_general', function($join) {
            //     $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
            //          ->where('m_general.category_code', '=', config('const.general.juridical'))
            //          ;5
            // })
            ->leftjoin(
                DB::raw('(' . $cNameQuery . ') as cName'),
                'cName.customer_id',
                '=',
                'm_customer.id'
            );

        if (!empty($params['matter_no']) || !empty($params['matter_name'])) {
            $query->leftJoin('t_matter as mat', 'mat.customer_id', '=', 'm_customer.id');
        }


        // データ取得
        $data = $query
            ->selectRaw('
                        m_customer.*,
                        dep.department_name,
                        st.staff_name,
                        cName.customer_name
                ')
            ->setBindings([':juridical' => config('const.general.juridical')])
            ->where($where)
            ->when($hasDate, function ($query) use ($whereDate) {
                return $query->where($whereDate);
            })
            ->orderBy('m_customer.id', 'asc')
            ->get();

        return $data;
    }

    /**
     * 新規登録
     *
     * @param array $params
     * @return $result 得意先ID (画像アップロード先に使用)
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'customer_name' => $params['customer_name'],
                'customer_kana' => $params['customer_kana'],
                'customer_short_name' => $params['customer_short_name'],
                'corporate_number' => $params['corporate_number'],
                'juridical_code' => $params['juridical_code'],
                'honorific' => $params['honorific'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_staff_id' => $params['charge_staff_id'],
                'customer_code' => $params['customer_code'],
                'customer_rank' => $params['customer_rank'],
                'company_category' => $params['company_category'],
                'bill_customer_id' => $params['bill_customer_id'],
                'tel' => $params['tel'],
                'fax' => $params['fax'],
                'email' => $params['email'],
                'url' => $params['url'],
                'zipcode' => $params['zipcode'],
                'address1' => $params['address1'],
                'address2' => $params['address2'],
                'closing_day' => $params['closing_day'],
                'collection_sight' => $params['collection_sight'],
                'collection_day' => $params['collection_day'],
                'collection_kbn' => $params['collection_kbn'],
                'bill_min_price' => $params['bill_min_price'],
                'bill_rate' => $params['bill_rate'],
                'fee_kbn' => $params['fee_kbn'],
                'tax_calc_kbn' => $params['tax_calc_kbn'],
                'tax_rounding' => $params['tax_rounding'],
                'offset_supplier_id' => $params['offset_supplier_id'],
                'bill_sight' => $params['bill_sight'],
                'housing_history_login_id' => $params['housing_history_login_id'],
                'housing_history_password' => $params['housing_history_password'],
                'latitude_jp' => $params['latitude_jp'],
                'longitude_jp' => $params['longitude_jp'],
                'latitude_world' => $params['latitude_world'],
                'longitude_world' => $params['longitude_world'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);


            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'customer_name' => $params['customer_name'],
                    'customer_kana' => $params['customer_kana'],
                    'customer_short_name' => $params['customer_short_name'],
                    'corporate_number' => $params['corporate_number'],
                    'juridical_code' => $params['juridical_code'],
                    'customer_code' => $params['customer_code'],
                    'customer_rank' => $params['customer_rank'],
                    'honorific' => $params['honorific'],
                    'charge_department_id' => $params['charge_department_id'],
                    'charge_staff_id' => $params['charge_staff_id'],
                    'company_category' => $params['company_category'],
                    'bill_customer_id' => $params['bill_customer_id'],
                    'tel' => $params['tel'],
                    'fax' => $params['fax'],
                    'email' => $params['email'],
                    'url' => $params['url'],
                    'zipcode' => $params['zipcode'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
                    'closing_day' => $params['closing_day'],
                    'collection_sight' => $params['collection_sight'],
                    'collection_day' => $params['collection_day'],
                    'collection_kbn' => $params['collection_kbn'],
                    'bill_min_price' => $params['bill_min_price'],
                    'bill_rate' => $params['bill_rate'],
                    'fee_kbn' => $params['fee_kbn'],
                    'tax_calc_kbn' => $params['tax_calc_kbn'],
                    'tax_rounding' => $params['tax_rounding'],
                    'offset_supplier_id' => $params['offset_supplier_id'],
                    'bill_sight' => $params['bill_sight'],
                    'housing_history_login_id' => $params['housing_history_login_id'],
                    'housing_history_password' => $params['housing_history_password'],
                    'latitude_jp' => $params['latitude_jp'],
                    'longitude_jp' => $params['longitude_jp'],
                    'latitude_world' => $params['latitude_world'],
                    'longitude_world' => $params['longitude_world'],
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
     * 担当者更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateStaffById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $result = $this
                ->where('id', $params['id'])
                ->update([
                    'charge_department_id' => $params['charge_department_id'],
                    'charge_staff_id' => $params['charge_staff_id'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param $id
     * @return void
     */
    public function deleteById($id)
    {
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
     * 有効化
     * @param $id
     * @return void
     */
    public function activateById($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.off'),
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
     * IDで取得
     * @param int $id 得意先ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id)
    {
        $data = $this
            // ->leftJoin('m_general as g', 'm_customer.company_category', '&', 'g.value_num_1')
            // ->select('m_customer.*',
            //          'g.value_num_1')
            ->where(['m_customer.id' => $id])
            // ->where(['del_flg' => config('const.flg.off')])
            ->first();

        return $data;
    }

    /**
     * 得意先IDで担当者情報取得
     *
     * @param [type] $id
     * @return void
     */
    public function getChargeData($id)
    {
        $data = $this
            ->where(['m_customer.id' => $id])
            ->where(['m_customer.del_flg' => config('const.flg.off')])
            ->leftJoin('m_department', 'm_department.id', 'm_customer.charge_department_id')
            ->leftJoin('m_staff', 'm_staff.id', 'm_customer.charge_staff_id')
            ->select(
                'm_customer.*',
                'm_department.department_name',
                'm_staff.staff_name'
            )
            ->first();

        return $data;
    }

    /**
     * IDで取得 企業業種
     * @param int $id 得意先ID
     * @return type 
     */
    public function getCompanyCategory($id)
    {
        $where = [];
        $where[] = array('m_customer.id', '=', $id);
        $where[] = array('g.category_code', '=', config('const.general.com'));

        $data = $this
            ->leftJoin('m_general as g', 'm_customer.company_category', '&', 'g.value_code')
            ->where($where)
            ->get();

        return $data;
    }

    /**
     * コンボボックス用データ取得
     * 未承認の得意先は除外（条件：担当部門が 0 or null または 担当営業が 0 or null）
     * 
     * @param int $useSalesFlg  売上利用フラグ config('const.flg.off') または config('const.flg.on') ※nullが渡ってきた場合は条件に使用しない
     *
     * @return 
     */
    public function getComboList($useSalesFlg = 0)
    {
        // Where句作成
        $where = [];
        $where[] = array('m_customer.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_customer.charge_staff_id', '<>', 0);
        $where[] = array('m_customer.charge_department_id', '<>', 0);
        if (!is_null($useSalesFlg)) {
            $where[] = array('m_customer.use_sales_flg', '=', $useSalesFlg);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->leftJoin('m_general', function ($join) {
                $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->whereNotNull('m_customer.charge_department_id')
            ->whereNotNull('m_customer.charge_staff_id')
            ->orderBy('m_customer.id', 'asc')
            ->selectRaw('
                    m_customer.id,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                    m_customer.customer_short_name
                ')
            ->get();

        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @return 
     */
    public function getAllList()
    {
        // Where句作成
        $where = [];
        $where[] = array('m_customer.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->leftJoin('m_general', function ($join) {
                $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->orderBy('m_customer.id', 'asc')
            ->selectRaw('
                    m_customer.id,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                    m_customer.customer_short_name,
                    charge_department_id,
                    charge_staff_id
                ')
            ->get();

        return $data;
    }

    /**
     * コンボボックス用データ取得
     * 担当部門が 0 or null は除外
     * 担当営業が 0 or null は除外
     * 締日が null は除外
     * 回収サイトが null は除外
     * 回収日が null は除外
     * 回収区分が 0 or null は除外
     * @param $useSalesFlg
     * @return 
     */
    public function getDetailInfoRegisterComboList($useSalesFlg = null)
    {
        // Where句作成
        $where = [];
        $where[] = array('m_customer.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_customer.charge_department_id', '<>', 0);
        $where[] = array('m_customer.charge_staff_id', '<>', 0);
        $where[] = array('m_customer.collection_kbn', '<>', 0);

        if ($useSalesFlg !== null) {
            $where[] = array('m_customer.use_sales_flg', '=', $useSalesFlg);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->leftJoin('m_general', function ($join) {
                $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->whereNotNull('m_customer.charge_department_id')
            ->whereNotNull('m_customer.charge_staff_id')
            ->whereNotNull('m_customer.closing_day')
            ->whereNotNull('m_customer.collection_sight')
            ->whereNotNull('m_customer.collection_day')
            ->whereNotNull('m_customer.collection_kbn')
            ->orderBy('m_customer.id', 'asc')
            ->select([
                'm_customer.id',
                DB::raw('CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name'),
                'm_customer.customer_short_name',
                'm_customer.charge_department_id',
                'm_customer.charge_staff_id',
            ])
            ->get();

        return $data;
    }

    /**
     * 担当部門、担当者IDから取得
     *
     * @param $departmentId
     * @param $staffId
     * @return void
     */
    public function getByChargeId($departmentId, $staffId)
    {
        $where = [];
        if (!empty($departmentId)) {
            $where[] = array('c.charge_department_id', '=', $departmentId);
        }
        if (!empty($staffId)) {
            $where[] = array('c.charge_staff_id', '=', $staffId);
        }
        $where[] = array('c.del_flg', '=', config('const.flg.off'));

        // TODO: 売上取得

        $data = $this
            ->from('m_customer as c')
            ->leftJoin('m_general', function ($join) {
                $join->on('c.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_staff', 'm_staff.id', '=', 'c.charge_staff_id')
            ->leftJoin('m_department', 'm_department.id', '=', 'c.charge_department_id')
            ->where($where)
            ->selectRaw('
                    c.id,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                    c.customer_short_name,
                    c.customer_kana,
                    c.customer_code,
                    c.charge_department_id,
                    m_department.department_name,
                    c.charge_staff_id,
                    m_staff.staff_name,
                    CONCAT(COALESCE(c.address1, \'\'), COALESCE(c.address2, \'\')) as address
                ')
            ->get();

        return $data;
    }

    /**
     * ログインしたユーザーの部門から取得（得意先「deptded」固定）
     *
     * @param $period
     * @return
     */
    public function getByLoginUser($period)
    {
        $where = [];
        $where[] = array('sd.staff_id', '=', Auth::user()->id);
        $where[] = array('sd.main_flg', '=', config('const.flg.on'));
        $where[] = array('sd.period', '=', $period);

        $data = $this
            ->from('m_staff_department as sd')


            ->leftJoin('m_department as d', function ($join) {
                $join->on('d.id', '=', 'sd.department_id')
                    ->where('d.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('m_staff as s', function ($join) {
                $join->on('s.id', '=', 'sd.staff_id')
                    ->where('s.del_flg', '=', config('const.flg.off'));
            })

            ->leftJoin('m_customer as c', function ($join) {
                $join
                    ->on('c.charge_department_id', '=', 'sd.department_id')
                    ->where('c.del_flg', '=', config('const.flg.off'))
                    ->where('c.use_sales_flg', '=', config('const.flg.on'));
            })
            ->leftJoin('t_matter as m', function ($join) {
                $join->on('c.id', '=', 'm.customer_id')
                    ->where('m.use_sales_flg', '=', config('const.flg.on'))
                    ->where('m.own_stock_flg', '=',  config('const.flg.off'))
                    ->where('m.complete_flg', '=',  config('const.flg.off'))
                    ->where('m.del_flg', '=',  config('const.flg.off'));
            })
            ->where($where)
            ->selectRaw('
                        distinct
                        c.id as customer_id
                        ,sd.department_id
                        ,sd.staff_id
                        ,m.id as matter_id
                        ,m.matter_no
                        ,m.matter_name
                        ,d.department_name
                        ,d.chief_staff_id
                        ,s.staff_name
                ')
            ->get();

        return $data;
    }

    /**
     * 新規登録(いきなり売上)
     *
     * @param $params
     * @return $result 得意先ID
     */
    public function addOfCounterSale($params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'customer_name' => $params['customer_name'],
                'customer_kana' => $params['customer_kana'],
                'customer_short_name' => $params['customer_short_name'],
                'charge_department_id' => $params['charge_department_id'],
                'charge_staff_id' => $params['charge_staff_id'],
                'use_sales_flg' => $params['use_sales_flg'],
                'closing_day' => $params['closing_day'],
                'collection_sight' => $params['collection_sight'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);

            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }


    /**
     * 仕入先IDから取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getCustomerBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);


        $data = $this
            ->from('t_order_detail AS od')
            ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
            ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
            ->leftJoin('m_customer AS cus', 'cus.id', '=', 'm.customer_id')
            ->where($where)
            ->select([
                'cus.id',
                'cus.customer_name'
            ])
            ->whereNotNull('cus.customer_name')
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 受注確定に必要な項目が入力されているか
     * @param $customerId   得意先ID
     */
    public function isValidReceivedOrder($customerId)
    {
        $result = true;
        $customer = $this->getById($customerId);
        if ($customerId !== null) {
            if (Common::nullorBlankToZero($customer->charge_department_id) === 0) {
                // 担当部門
                $result = false;
            } else if (Common::nullorBlankToZero($customer->charge_staff_id) === 0) {
                // 担当営業
                $result = false;
            } else if ($customer->closing_day === null) {
                // 締日
                // 0、99、1～28
                $result = false;
            } else if ($customer->collection_sight === null) {
                // 回収サイト
                // 0～4
                $result = false;
            } else if ($customer->collection_day === null) {
                // 回収日
                // 0、99、1～28
                $result = false;
            } else if (Common::nullorBlankToZero($customer->collection_kbn) === 0) {
                // 回収区分
                // 1～9
                $result = false;
            }
        } else {
            $result = false;
        }
        return $result;
    }

    /**
     * 入金一覧用得意先一覧取得
     *
     * @param $departmentId
     * @param $staffId
     * @return void
     */
    public function getCredited($params)
    {
        $where = [];
        $where[] = array('c.id', '=', $params['customer_id']);

        $where[] = array('c.del_flg', '=', config('const.flg.off'));

        // TODO: 売上取得

        $data = $this
            ->from('m_customer as c')
            ->leftJoin('m_general', function ($join) {
                $join->on('c.juridical_code', '=', 'm_general.value_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'));
            })
            ->leftJoin('m_staff as s', 's.id', '=', 'c.charge_staff_id')
            ->leftJoin('m_department as d', 'd.id', '=', 'c.charge_department_id')
            ->where($where)
            ->selectRaw('
                    c.id as customer_id,
                    CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(c.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                    c.charge_department_id,
                    d.department_name,
                    c.charge_staff_id,
                    s.staff_name,
                    c.closing_day,
                    c.collection_sight,
                    c.collection_day,
                    c.collection_kbn,
                    c.bill_min_price,
                    c.bill_rate,
                    c.fee_kbn,
                    c.tax_calc_kbn,
                    c.tax_rounding,
                    c.offset_supplier_id,
                    c.bill_sight
                ')
            ->first();

        return $data;
    }

    /**
     * 相殺仕入先IDから取得
     *
     * @param [type] $supplierId
     * @return void
     */
    public function getOffsetSupplier($supplierId) 
    {
        $where = [];
        $where[] = array('m_customer.offset_supplier_id', '=', $supplierId);
        $where[] = array('m_customer.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->select([
                    'm_customer.id',
                ])
                ->get()
                ;
                
        return $data;
    }
}
