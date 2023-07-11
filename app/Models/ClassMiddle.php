<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Auth;
use App\Libs\LogUtil;

/**
 * 中分類マスタ
 */
class ClassMiddle extends Model
{
    // テーブル名
    protected $table = 'm_class_middle';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

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
     *  見積依頼項目(大分類)の中分類を取得
     *
     * @param array $params
     * @return void
     */
    public function getQreqMiddleList($params) {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        
        $data = $this
                ->where($where)
                ->where( function($query) use($params){
                    foreach($params as $col) {
                        $query->orwhere('class_big_id', '=', $col['class_big_id']);
                    }
                })
                ->get()
                ;

        return $data;
    }

    /**
     * 中分類マスタ一覧
     *
     * @param  $params
     * @return void
     */
    public function getList($params) {
        $where = [];
        $where[] = array('middle.del_flg', '=', config('const.flg.off'));

        if (!empty($params['id'])) {
            $where[] = array('middle.id', '=', $params['id']);
        }
        if (!empty($params['class_middle_name'])) {
            $where[] = array('middle.class_middle_name', 'LIKE', '%'.$params['class_middle_name'].'%');
        }
        if (!empty($params['class_big_name'])) {
            $where[] = array('big.class_big_name', 'LIKE', '%'.$params['class_big_name'].'%');
        }

        $data = $this
                ->from('m_class_middle AS middle')
                ->leftJoin('m_class_big AS big', 'middle.class_big_id', '=', 'big.id')
                ->where($where)
                ->selectRaw('
                    middle.id,
                    middle.class_big_id,
                    middle.class_middle_name,
                    big.class_big_name,
                    DATE_FORMAT(middle.update_at, \'%Y/%m/%d %H:%i:%s\') AS update_at,
                    DATE_FORMAT(middle.created_at, \'%Y/%m/%d %H:%i:%s\') AS created_at
                ')
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
                ->leftjoin('m_staff', 'm_staff.id', '=', 'm_class_middle.update_user')
                ->select(
                    'm_class_middle.*', 
                    'm_staff.staff_name AS update_user_name'
                )
                ->where(['m_class_middle.id' => $id])
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
                    'class_big_id' => $params['class_big_id'],
                    'class_middle_name' => $params['class_middle_name'],
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
                        'class_big_id' => $params['class_big_id'],
                        'class_middle_name' => $params['class_middle_name'],
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
     * ID、中分類名、大分類IDのみを取得
     *
     * @return void
     */
    public function getIdNameList() {
        // Where句
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->select([
                    'id AS class_middle_id',
                    'class_middle_name',
                    'class_big_id'
                ])
                ->get()
                ;

        return $data;
    }
}
