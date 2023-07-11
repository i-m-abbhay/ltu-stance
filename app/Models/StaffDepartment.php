<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 担当者部門マスタ
 */
class StaffDepartment extends Model
{
    // テーブル名
    protected $table = 'm_staff_department';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    protected $fillable = [
        'staff_id',
        'department_id',
        'main_flg',
        'period',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    // protected $fillable = [
    //     'staff_id',
    //     'department_id',
    //     'main_flg',
    //     'period',
    //     'created_user',
    //     'created_at',
    //     'update_user',
    //     'update_at',
    // ];
    
    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params)
    {
        // Where句
        $where = [];
        $where[] = array('m_staff_department.main_flg', '=', config('const.flg.on'));
        if (!empty($params['id'])) {
            $where[] = array('id', '=', $params['id']);
        }
        if(!empty($params['staff_id'])){
            $where[] = array('staff_id', '=', $params['staff_id']);
        }
        if(!empty($params['department_id'])){
            $where[] = array('department_id', '=', $params['department_id']);
        }
        

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('staff_id', 'asc')
                ->get()
                ;
        
                return $data;
    }    

    /**
     * 当期の担当者部門リスト取得
     *
     * @return void
     */
    public function getCurrentTermList()
    {
        $data = $this
                ->leftJoin('m_system', 'm_system.period', '=', 'm_staff_department.period')
                ->select(
                    'm_staff_department.*'
                )
                ->whereNotNull('m_system.period')
                ->orderBy('staff_id', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add(array $params, $newStaffId) {
        $result = false;
        // 期取得
        $system = new System();
        $period = $system->getByPeriod();
    
        try {
            // TODO: 短い文にしたい…(メインフラグの立て方)
            if($params['department_main']){
                $result = $this->insert([
                    'staff_id' => $newStaffId,
                    'department_id' => $params['department_main'],
                    'main_flg' => config('const.flg.on'),
                    'period' => $period['period'],
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            }
            if($params['department_sub1']){
                $result = $this->insert([
                    'staff_id' => $newStaffId,
                    'department_id' => $params['department_sub1'],
                    'main_flg' => config('const.flg.off'),
                    'period' => $period['period'],
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            }
            if($params['department_sub2']){
                $result = $this->insert([
                    'staff_id' => $newStaffId,
                    'department_id' => $params['department_sub2'],
                    'main_flg' => config('const.flg.off'),
                    'period' => $period['period'],
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            }


        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 更新
     *
     * @param $params　担当者パラメータ
     * @return void
     */
    public function updateById($params)
    {
        $result = false;
        $staff = new Staff();
        $system = new System();
        $staff_id = $params['id'];
        $CreatedStaff = $staff->getById($staff_id);
        $period = $system->getByPeriod();
        
        try{
            // 削除Where句
            $where = [];
            $where[] = array('staff_id', '=', $staff_id);
            $where[] = array('period', '=', $period->period);
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

            $this->where($where)
                 ->delete();
            if($params['department_main']){
                $this->insert(
                    [
                    'staff_id' => $staff_id, 
                    'department_id' => $params['department_main'], 
                    'main_flg' => config('const.flg.on'),
                    'period' => $period['period'], 
                    'created_user' => $CreatedStaff['created_user'],
                    'created_at' => $CreatedStaff['created_at'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                    ]
                );
            }
            if($params['department_sub1']){
                $this->insert(
                    [
                    'staff_id' => $staff_id, 
                    'department_id' => $params['department_sub1'], 
                    'main_flg' => config('const.flg.off'),
                    'period' => $period['period'],
                    'created_user' => $CreatedStaff['created_user'],
                    'created_at' => $CreatedStaff['created_at'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                    ]
                );
            }
            if($params['department_sub2']){
                $this->insert(
                    [
                    'staff_id' => $staff_id, 
                    'department_id' => $params['department_sub2'], 
                    'main_flg' => config('const.flg.off'),
                    'period' => $period['period'],
                    'created_user' => $CreatedStaff['created_user'],
                    'created_at' => $CreatedStaff['created_at'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                    ]
                );
            }

            $result = true;
        }catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * IDで取得
     * @param int $id 担当者部門ID
     * @return type
     */
    public function getById($id) {
        $data = $this
                ->leftJoin('m_system as sys', 'm_staff_department.period', '=', 'sys.period')
                ->leftJoin('m_department as department', 'department.id', '=', 'm_staff_department.department_id')
                ->where(['m_staff_department.staff_id' => $id])
                ->whereNotNull('sys.period')
                ->get()
                ;
                

        return $data;
    }

    /**
     * 担当者IDでメイン部門を取得（stdClass）
     *
     * @param int $staffId
     * @return \stdClass
     */
    public function getMainDepartmentByStaffId($staffId){
        $data = $this
                ->where(['staff_id' => $staffId])
                ->where(['main_flg' => config('const.flg.on')])
                ->whereRaw('period = (SELECT period FROM m_system)')
                ->first()
                ;
        return $data;
    }
}