<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Auth;
use DB;
use App\Libs\LogUtil;

/**
 * 選択肢マスタ
 */
class Choice extends Model
{
    // テーブル名
    protected $table = 'm_choice';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 全選択肢データを取得
     *
     * @return Choice
     */
    public function getAllData() {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('choice_keyword', 'asc')
                ->orderBy('display_order', 'asc')
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
            $result = $this->insertGetId([
                    'choice_keyword' => $params['choice_keyword'],
                    'display_order' => $params['display_order'],
                    'choice_name' => $params['choice_name'],
                    'input_area_flg' => $params['input_area_flg'],
                    'image_path' => $params['image_path'],
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
                        'choice_keyword' => $params['choice_keyword'],
                        'display_order' => $params['display_order'],
                        'choice_name' => $params['choice_name'],
                        'input_area_flg' => $params['input_area_flg'],
                        'image_path' => $params['image_path'],
                        'del_flg' => $params['del_flg'],
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
     * @param int $id 選択肢ID
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
     * IDで取得
     * @param int $id 選択肢ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftjoin('m_staff', 'm_staff.id', '=', 'm_choice.update_user')
                ->select(
                    'm_choice.*', 
                    'm_staff.staff_name AS update_user_name'
                )
                ->where(['m_choice.id' => $id])
                ->first()
                ;

        return $data;
    }

    /**
     * 選択肢名リスト取得
     *
     * @return 
     */
    public function getNameList() {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
                ->where($where)
                ->selectRaw('
                    choice_name
                ')
                ->orderBy('choice_name', 'asc')
                ->groupBy('choice_name')
                ->get()
                ;
        
        return $data;
    }

    /**
     * キーワードリスト取得
     *
     * @return 
     */
    public function getKeywordList() {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        // $where[] = array('display_order', '=', 1);

        // データ取得
        $data = $this
                ->where($where)
                ->selectRaw('
                    MIN(id) as id,
                    choice_keyword
                ')
                ->orderBy('choice_keyword', 'asc')
                ->groupby('choice_keyword')
                ->get()
                ;
        
        return $data;
    }

    /**
     * キーワードから取得
     *
     * @return 
     */
    public function getByKeyword($keyword) {
        // Where句作成
        $where = [];
        $where[] = array('m_choice.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_choice.choice_keyword', '=', $keyword);

        // データ取得
        $data = $this
                ->where($where)
                ->leftJoin('m_staff', 'm_staff.id', '=', 'm_choice.update_user')
                ->select(
                    'm_choice.*',
                    'm_staff.staff_name as update_user_name'
                )
                ->orderBy('m_choice.display_order', 'asc')
                ->get()
                ;
        
        return $data;
    }

    /**
     * 選択肢名リスト取得
     *
     * @return 
     */
    public function getByNotInIDs($IDs) {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // データ取得
        // データ取得
        $data = $this
                ->where($where)
                ->whereNotIn('id', $IDs)
                ->select(
                    'id',
                    'choice_keyword'
                )
                ->orderBy('id', 'asc')
                ->get()
                ;
        
        return $data;
    }

}