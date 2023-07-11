<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 部門マスタ
 */
class Department extends Model
{
    // テーブル名
    protected $table = 'm_department';
    public $timestamps = false;

    protected $appends = ['open'];

    public function getOpenAttribute() {
        return true;
    }
    
    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句
        $where = [];
        $where[] = array('m_department.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('id', '=', $params['id']);
        }
        if (!empty($params['department_name'])) {
            $where[] = array('department_name', 'LIKE', '%'.$params['department_name'].'%');
        }

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->get()
                ;
        
                return $data;
    }

    /**
     * 一覧取得(部門一覧)
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getDepartmentList($params) {
        // Where句
        $where = [];
        $where[] = array('dep.del_flg', '=', config('const.flg.off'));
        if (!empty($params['department_name'])) {
            $where[] = array('dep.department_name', 'LIKE', '%'.$params['department_name'].'%');
        }
        if (!empty($params['department_symbol'])) {
            $where[] = array('dep.department_symbol', 'LIKE', '%'.$params['department_symbol'].'%');
        }
        if (!empty($params['base_name'])) {
            $where[] = array('base.base_name', 'LIKE', '%'.$params['base_name'].'%');
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('staff.staff_name', 'LIKE', '%'.$params['staff_name'].'%');
        }

        // データ取得
        $data = $this
                ->from('m_department as dep')
                ->leftJoin('m_department as parent', 'parent.id', '=', 'dep.parent_id')
                ->leftJoin('m_base as base', 'base.id', '=', 'dep.base_id')
                ->leftJoin('m_staff as staff', 'staff.id', '=', 'dep.chief_staff_id')
                ->select(
                        'dep.id',
                        'dep.department_name',
                        'dep.department_symbol',
                        'parent.department_name as parent_dep',
                        'base.base_name',
                        'staff.staff_name'
                        )
                ->where($where)
                ->orderBy('id', 'asc')
                ->get()
                ;
        
        return $data;
    }

    /**
     * IDで取得
     * @param int $id 部門ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftJoin('m_staff', 'm_staff.id', '=', 'm_department.update_user')
                ->leftJoin('m_staff as chief', 'chief.id', '=', 'm_department.chief_staff_id')
                ->where(['m_department.id' => $id])
                ->select([
                        'm_department.*',
                        'chief.id as chief_staff_id',
                        'm_staff.staff_name as update_user_name'
                ])
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
            $result = $this->insert([
                    'department_name' => $params['department_name'],
                    'base_id' => $params['base_id'],
                    'parent_id' => $params['parent_id'],
                    'department_symbol' => $params['department_symbol'],
                    'tel' => $params['tel'],
                    'fax' => $params['fax'],
                    'own_bank_id_1' => $params['own_bank_id_1'],
                    'own_bank_id_2' => $params['own_bank_id_2'],
                    'own_bank_id_3' => $params['own_bank_id_3'],
                    'own_bank_id_4' => $params['own_bank_id_4'],
                    'own_bank_id_5' => $params['own_bank_id_5'],
                    'chief_staff_id' => $params['chief_staff_id'],
                    'standard_gross_profit_rate' => $params['standard_gross_profit_rate'],
                    // 'standard_cost_total' => $params['standard_cost_total'],
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
                        'department_name' => $params['department_name'],
                        'base_id' => $params['base_id'],
                        'parent_id' => $params['parent_id'],
                        'department_symbol' => $params['department_symbol'],
                        'tel' => $params['tel'],
                        'fax' => $params['fax'],
                        'own_bank_id_1' => $params['own_bank_id_1'],
                        'own_bank_id_2' => $params['own_bank_id_2'],
                        'own_bank_id_3' => $params['own_bank_id_3'],
                        'own_bank_id_4' => $params['own_bank_id_4'],
                        'own_bank_id_5' => $params['own_bank_id_5'],
                        'chief_staff_id' => $params['chief_staff_id'],
                        'standard_gross_profit_rate' => $params['standard_gross_profit_rate'],
                        // 'standard_cost_total' => $params['standard_cost_total'],
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
     * @param int $id 拠点ID
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
     * コンボボックス用データ取得
     *
     * @return 
     */
    public function getComboList() {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->select([
                    'id',
                    'department_name',
                    'department_symbol',
                    'parent_id',
                    'base_id'
                ])
                ->get()
                ;
        
        return $data;
    }


    /**
     * 仕入先IDから部門取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getDepartmentBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);
        // $where[] = array('pro.del_flg', '=', config('const.flg.off'));
        // $data = $this
        //         ->from('t_order_detail AS od')
        //         ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
        //         ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
        //         ->leftJoin('m_department AS d','d.id', '=', 'm.department_id')
        //         ->select([
        //             'd.id',
        //             'd.department_name'
        //         ])
        //         ->where($where)
        //         ->whereNotNull('d.department_name')
        //         ->distinct()
        //         ->get()
        //         ;

        $arrivalData = $this
                ->from('t_arrival as ar')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'ar.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->leftJoin('t_matter as m', 'm.matter_no', '=', 'o.matter_no')
                ->leftJoin('m_department as dep', 'dep.id', '=', 'm.department_id')
                ->where($where)
                ->select([
                    'dep.id',
                    'dep.department_name',
                ])
                ->distinct()
                ;

        $returnData = $this
                ->from('t_return as re')
                ->leftJoin('t_warehouse_move as wm', 'wm.id', '=', 're.warehouse_move_id')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->leftJoin('t_matter as m', 'm.matter_no', '=', 'o.matter_no')
                ->leftJoin('m_department as dep', 'dep.id', '=', 'm.department_id')
                ->where($where)
                ->select([
                    'dep.id',
                    'dep.department_name',
                ])
                ->distinct()
                ;

        $data = $arrivalData->union($returnData)->get();

        return $data;
    }

    /**
     * 仕入先IDから案件担当者取得
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getMatterStaffBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);
        // $where[] = array('pro.del_flg', '=', config('const.flg.off'));

        // $data = $this
        //         ->from('t_order_detail AS od')
        //         ->leftJoin('t_order AS o', 'o.id', '=', 'od.order_id')
        //         ->leftJoin('t_matter AS m', 'm.matter_no', '=', 'o.matter_no')
        //         ->leftJoin('m_staff AS s','s.id', '=', 'm.staff_id')
        //         ->select([
        //             's.id',
        //             's.staff_name'
        //         ])
        //         ->where($where)
        //         ->whereNotNull('s.staff_name')
        //         ->distinct()
        //         ->get()
        //         ;

        $arrivalData = $this
                ->from('t_arrival as ar')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'ar.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->leftJoin('t_matter as m', 'm.matter_no', '=', 'o.matter_no')
                ->leftJoin('m_staff as st', 'st.id', '=', 'm.staff_id')
                ->where($where)
                ->select([
                    'st.id',
                    'st.staff_name',
                ])
                ->distinct()
                ;

        $returnData = $this
                ->from('t_return as re')
                ->leftJoin('t_warehouse_move as wm', 'wm.id', '=', 're.warehouse_move_id')
                ->leftJoin('t_order_detail as od', 'od.id', '=', 'wm.order_detail_id')
                ->leftJoin('t_order as o', 'o.id', '=', 'od.order_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'od.product_id')
                ->leftJoin('t_matter as m', 'm.matter_no', '=', 'o.matter_no')
                ->leftJoin('m_staff as st', 'st.id', '=', 'm.staff_id')
                ->where($where)
                ->select([
                    'st.id',
                    'st.staff_name',
                ])
                ->distinct()
                ;

        $data = $arrivalData->union($returnData)->get();

        return $data;
    }

    /**
     * 部門IDから親部門情報取得
     *
     * @param [type] $id
     * @return void
     */
    public function getParentDepartmentById($id)
    {
        $where = [];
        $where[] = array('d.del_flg', '=', config('const.flg.off'));
        $where[] = array('d.id', '=', $id);

        $data = $this
                ->from('m_department AS d')
                ->leftJoin('m_department AS parent','parent.id', '=', 'd.parent_id')
                ->selectRaw('*')
                ->where($where)
                ->first()
                ;

        return $data;
    }

      /**
     * IDで取得
     * @param int $id 部門ID
     * @return type 検索結果データ（1件）
     */
    public function getByChief($id) {
        $data = $this
                ->where(['m_department.id' => $id])
                ->select([
                        'm_department.*'
                ])
                ->first()
                ;

        return $data;
    }
}