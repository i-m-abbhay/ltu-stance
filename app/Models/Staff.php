<?php

namespace App\Models;

use DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Auth;
use App\Models\System;
use App\Libs\LogUtil;


/**
 * 担当者マスタ
 */
class Staff extends Model
{
    // テーブル名
    protected $table = 'm_staff';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'staff_name', 
        'staff_kana',
        'staff_short_name',
        'employee_code',
        'position_code',
        'email', 
        'login_id',
        'password',
        'tel_1',
        'tel_2',
        'mobile_email',
        'retirement_kbn', 
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
        // Where句
        $where = [];
        $where[] = array('m_staff.del_flg', '=', config('const.flg.off'));
        $where[] = array('msd.main_flg', '=', config('const.flg.on'));
        // $where[] = array('sys.period', '=', 'msd.period');
        if (!empty($params['id'])) {
            $where[] = array('m_staff.id', '=', $params['id']);
        }
        if (!empty($params['staff_name'])) {
            $where[] = array('m_staff.staff_name', 'LIKE', '%'.$params['staff_name'].'%');
        }
        if (!empty($params['staff_kana'])) {
            $where[] = array('m_staff.staff_kana', 'LIKE', '%'.$params['staff_kana'].'%');
        }
        if (!empty($params['employee_code'])) {
            $where[] = array('m_staff.employee_code', 'LIKE', '%'.$params['employee_code'].'%');
        }
        if (!empty($params['position_code'])) {
            $where[] = array('m_staff.position_code', 'LIKE', '%'.$params['position_code'].'%');
        }
        if (!empty($params['department_id'])) {
            $where[] = array('subMsd.department_id', '=', $params['department_id']);
        }

        // データ取得
        $data = $this
                ->leftJoin('m_staff_department as msd', 'm_staff.id', '=', 'msd.staff_id')
                ->leftJoin('m_department as dep', 'dep.id', '=', 'msd.department_id')
                // ->leftJoin('m_general as pos', function($join) {
                //             $join->on('m_staff.position_code', '=', 'pos.value_code')
                //                 ->where('pos.category_code', '=', 'POS');
                //             })
                ->leftJoin('m_system as sys','sys.period', '=', 'msd.period')
                ->leftJoin('m_staff_department as subMsd', 'subMsd.staff_id', '=', 'm_staff.id')
                ->select('m_staff.*',
                        'dep.department_name'
                        )
                ->where($where)
                ->whereNotNull('sys.period')
                ->distinct()
                // ->groupBy('m_staff.id')
                ->orderBy('m_staff.id', 'asc')
                ->get()
                ;
                
        
        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add(array $params) {
        $result = false;
    
        try {
            $result = $this->insertGetId([
                'staff_name' => $params['staff_name'],
                'staff_kana' => $params['staff_kana'],
                'staff_short_name' => $params['staff_short_name'],
                'employee_code' => $params['employee_code'],
                'position_code' => $params['position_code'],
                'email' => $params['email'],
                'login_id' => $params['login_id'],
                'password' => Hash::make($params['password']), // パスワードの暗号化
                'tel_1' => $params['tel_1'],
                'tel_2' => $params['tel_2'],
                'mobile_email' => $params['mobile_email'],
                'stamp' => $params['stamp'],
                'retirement_kbn' => $params['retirement_kbn'],
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
     *
     * @param array $params
     * @return void
     */
    public function updateById($params)
    {        
        $result = false;

        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if($params['password'] !== config('const.default.default')) {
                $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'staff_name' => $params['staff_name'],
                        'staff_kana' => $params['staff_kana'],
                        'staff_short_name' => $params['staff_short_name'],
                        'employee_code' => $params['employee_code'],
                        'position_code' => $params['position_code'],
                        'email' => $params['email'],
                        'login_id' => $params['login_id'],
                        'password' => Hash::make($params['password']), // パスワードの暗号化
                        'tel_1' => $params['tel_1'],
                        'tel_2' => $params['tel_2'],
                        'mobile_email' => $params['mobile_email'],
                        'stamp' => $params['stamp'],
                        'retirement_kbn' => $params['retirement_kbn'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                ]);
            }else {
                $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update([
                        'staff_name' => $params['staff_name'],
                        'staff_kana' => $params['staff_kana'],
                        'staff_short_name' => $params['staff_short_name'],
                        'employee_code' => $params['employee_code'],
                        'position_code' => $params['position_code'],
                        'email' => $params['email'],
                        'login_id' => $params['login_id'],
                        'tel_1' => $params['tel_1'],
                        'tel_2' => $params['tel_2'],
                        'mobile_email' => $params['mobile_email'],
                        'stamp' => $params['stamp'],
                        'retirement_kbn' => $params['retirement_kbn'],
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                ]);
            }
            $result = ($updateCnt > 0);
        }catch (\Exception $e) {
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
        try{
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
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * IDで取得
     * @param int $id 担当者ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftJoin('m_staff as ms2', 'm_staff.update_user', '=', 'ms2.id')
                ->leftJoin('m_staff_department', 'm_staff_department.staff_id', '=', 'm_staff.id')
                ->leftJoin('m_department', 'm_department.id', '=', 'm_staff_department.department_id')
                ->select('m_staff.*',
                         'ms2.staff_name as update_user_name',
                         'm_department.id AS department_id',
                         'm_department.department_name AS department_name'
                        )
                ->where(
                    [
                    'm_staff.id' => $id,
                    'm_staff_department.main_flg' => config('const.flg.on')
                    ]
                )
                ->first()
                ;

        return $data;
    }

    /**
     * 営業支援課の担当者一覧取得
     * TODO:営業支援課の条件
     *
     * @return void
     */
    public function getSalesSupportStaffList() {
        // Where句
        $where = [];
        $where[] = array('m_staff.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->join('m_staff_department as main_staff_dept', function($join) {
                    $join
                        ->on('m_staff.id', '=', 'main_staff_dept.staff_id')
                        ->where('main_staff_dept.main_flg', '=', config('const.flg.on'))
                        ;
                })
                ->join('m_system', 'm_system.period', '=', 'main_staff_dept.period')
                ->join('m_department as main_dept', function($join) {
                    $join
                        ->on('main_dept.id', '=', 'main_staff_dept.department_id')
                        ->where('main_dept.del_flg', '=', config('const.flg.off'))
                        ;
                })
                ->select('m_staff.id')
                ->where($where)
                ->orderBy('m_staff.id', 'asc')
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
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('retirement_kbn', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
                ->select([
                    'id',
                    'staff_name',
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * 特定の権限を保持している担当者を抽出
     *
     * @param [type] $authCode
     * @return void
     */
    public function getStaffByAuthCode($authCode) {
        // Where句作成
        $where = [];
        $where[] = array('staff.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->join('m_authority AS auth', function($join) use($authCode) {
                    $join->on('staff.id', '=', 'auth.staff_id')
                        ->where('auth.authority_code', '=', $authCode)
                        ->where('auth.del_flg', '=', config('const.flg.off'))
                        ;
                    })
                ->from('m_staff as staff')
                ->where($where)
                ->select([
                    'staff.id',
                ])
                ->get()
                ;
        return $data;
    }


    /**
     * 部門長に登録されている担当者のみ取得
     *
     * @return 
     */
    public function getChiefStaffList() {
        // Where句作成
        $where = [];
        $where[] = array('m_staff.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_department.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->where($where)
                ->leftJoin('m_department', 'm_department.chief_staff_id', '=', 'm_staff.id')
                ->orderBy('m_staff.id', 'asc')
                ->select([
                    'm_staff.id',
                    'm_staff.staff_name',
                ])
                ->groupBy('m_staff.id')
                ->get()
                ;
        
        return $data;
    }

    /**
     * 担当者の権限情報を取得
     *
     * @param [type] $staffIDs
     * @return [ staff => xx, authority_codes => [1, 3, 5...]]
     */
    public function getStaffAuthorityList($params){
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        /* 対象スタッフ */
        $sysData = (new System())->getByPeriod();
        $whereTargetStaff = [];
        $whereTargetStaff[] = array('period', '=',  $sysData->period);
        // 部門ID
        if (!empty($params['department_id'])) {
            $whereTargetStaff[] = array('department_id', '=', $params['department_id']);
        }
        // 担当者ID
        if (!empty($params['staff_id'])) {
            $whereTargetStaff[] = array('staff_id', '=', $params['staff_id']);
        }
        $targetStaffQuery = DB::table('m_staff_department')
                ->where($whereTargetStaff)
                ->distinct()
                ->select([
                    'staff_id',
                ]);

        /* 権限情報 */
        $authorityCodesQuery = DB::table('m_authority')
                ->where($where)
                ->select([
                    'staff_id',
                    DB::raw('JSON_ARRAYAGG(authority_code) AS authority_codes'),
                ])
                ->groupBy('staff_id');

        $data = $this
            ->join(
                DB::raw('('. $targetStaffQuery->toSql(). ') as target_staff'),
                'm_staff.id', '=', 'target_staff.staff_id'
            )
            ->mergeBindings($targetStaffQuery)
            ->leftJoin(
                DB::raw('('. $authorityCodesQuery->toSql(). ') AS auth'),
                'm_staff.id', '=', 'auth.staff_id'
            )
            ->mergeBindings($authorityCodesQuery)
            ->where($where)
            ->select([
                'm_staff.id',
                'm_staff.staff_name',
                DB::raw('
                  CASE
                    WHEN auth.staff_id IS NULL
                      THEN \'[]\'
                      ELSE auth.authority_codes
                  END AS authority_codes
                '),
            ])
            ->get();

        foreach ($data as $key => $value) {
            $data[$key]->authority_codes = json_decode($value->authority_codes);
        }

        return $data;
    }


    /**
     * 担当者及び部門の取得
     *
     * @return 
     */
    public function getStaffAndDepartmentList() {
        // Where句作成
        $where = [];
        $where[] = array('m_staff.del_flg', '=', config('const.flg.off'));
        
        // データ取得
        $data = $this
                ->where($where)
                ->whereRaw('m_staff_department.period = (select max(period) from m_system where del_flg = 0)')
                ->leftJoin('m_staff_department', 'm_staff_department.staff_id', '=', 'm_staff.id')
                ->orderBy('m_staff.id', 'asc')
                ->select([
                    'm_staff.id',
                    'm_staff.staff_name',
                    'm_staff_department.department_id',
                ])
                ->get()
                ;
        
        return $data;
    }


    /**
     * 仕入先IDから取得
     * 入荷 or 返品がある発注担当者のみ
     *
     * @param [type] $supplier_id
     * @return void
     */
    public function getOrderStaffBySupplierId($supplier_id)
    {
        $where = [];
        $where[] = array('od.del_flg', '=', config('const.flg.off'));
        $where[] = array('od.supplier_id', '=', $supplier_id);

        $data = $this
                ->from('t_order_detail AS od')
                ->leftJoin('t_order AS or', 'or.id', '=', 'od.order_id')
                ->leftJoin('t_arrival AS ar', 'ar.order_id', '=', 'od.id')
                ->leftJoin('t_warehouse_move AS wm', 'wm.order_detail_id', '=', 'od.id')
                ->leftJoin('t_return AS re', 're.warehouse_move_id', '=', 'wm.id')
                ->leftJoin('m_staff AS order_staff', 'order_staff.id', '=', 'or.order_staff_id')
                ->where($where)
                ->select([
                    'order_staff.id',
                    'order_staff.staff_name',
                ])
                // ->whereNotNull('ar.id')
                // ->whereNotNull('re.id')
                ->whereNotNull('order_staff.staff_name')
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * IDで取得
     * @param int $id 担当者ID
     * @return type 検索結果データ（1件）
     */
    public function getByIdWithDepartmentId($staffId, $departmentId = null) 
    {
        $where = [];
        $where[] = array('m_staff.id', '=', $staffId);

        $data = $this
                ->leftJoin('m_staff as ms2', 'm_staff.update_user', '=', 'ms2.id')
                ->leftJoin('m_staff_department', 'm_staff_department.staff_id', '=', 'm_staff.id')
                ->leftJoin('m_department', 'm_department.id', '=', 'm_staff_department.department_id')
                ->select('m_staff.*',
                         'ms2.staff_name as update_user_name',
                         'm_department.id AS department_id',
                         'm_department.department_name AS department_name',
                         'm_staff_department.main_flg'
                        )
                ->where($where)
                ->get()
                ;
        
        if (!empty($departmentId)) {
            $data = $data->where('department_id', $departmentId);
        } else {
            $data = $data->where('main_flg', config('const.flg.on'));
        }

        return $data->first();
    }
}