<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Auth;
use App\Libs\LogUtil;

/**
 * 小分類マスタ
 */
class ClassSmall extends Model
{
    // テーブル名
    protected $table = 'm_class_small';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'construction_id',
        'class_small_name',
        'construction_period_days',
        'base_date_type',
        'base_class_small_id',
        'date_calc_flg',
        'start_date_calc_days',
        'order_timing',
        'rain_flg',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',
    ];

    // 全データ取得
    public function getAll(){
        $data = $this->where('del_flg', '=', config('const.flg.off'))->get();
        return $data;
    }

    public function getComboList() {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        
        $data = $this
                ->where($where)
                ->get()
                ;

        return $data;
    }


    /**
     * 工程一覧
     *
     * @param [type] $params
     * @return void
     */
    public function getList($params)
    {
        $where = [];
        $where[] = array('m_class_small.del_flg', '=', config('const.flg.off'));

        if (!empty($params['id'])) {
            $where[] = array('m_class_small.id', '=', $params['id']);
        }
        if (!empty($params['class_small_name'])) {
            $where[] = array('m_class_small.class_small_name', 'LIKE', '%'.$params['class_small_name'].'%');
        }
        if (!empty($params['construction_name'])) {
            $where[] = array('m_construction.construction_name', 'LIKE', '%'.$params['construction_name'].'%');
        }

        $data = $this
                ->leftJoin('m_construction', 'm_construction.id', '=', 'm_class_small.construction_id')
                ->where($where)
                ->select([
                    'm_class_small.*',
                    'm_construction.construction_name',
                ])
                ->get()
                ;

        return $data;
    }

    /**
     * IDで取得
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftjoin('m_staff', 'm_staff.id', '=', 'm_class_small.update_user')
                ->select(
                    'm_class_small.*', 
                    'm_staff.staff_name AS update_user_name'
                )
                ->where(['m_class_small.id' => $id])
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
                    'construction_id' => $params['construction_id'],
                    'class_small_name' => $params['class_small_name'],
                    'construction_period_days' => $params['construction_period_days'],
                    'base_date_type' => $params['base_date_type'],
                    'base_class_small_id' => $params['base_class_small_id'],
                    'date_calc_flg' => $params['date_calc_flg'],
                    'start_date_calc_days' => $params['start_date_calc_days'],
                    'order_timing' => $params['order_timing'],
                    'rain_flg' => $params['rain_flg'],
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


            $items = [];
            $registerCol = $this->getFillable();
            foreach($registerCol as $colName){
                if(array_key_exists($colName, $params)){
                    switch($colName){
                        case 'created_user':
                        case 'created_at':
                            break;
                        default:
                            $items[$colName] = $params[$colName];
                            break;
                    }
                }
            }
            $items['update_user'] = Auth::user()->id;
            $items['update_at'] = Carbon::now();

            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($items)
                    ;
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 論理削除
     * @param int $id ID
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
}
