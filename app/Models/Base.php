<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 拠点マスタ
 */
class Base extends Model
{
    // テーブル名
    protected $table = 'm_base';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    public function warehouse(){
        return $this->belongsTo('App\Models\Warehouse');
    }
    
    /**
     * 一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getList($params) {
        // Where句作成
        $where = [];
        $where[] = array('m_base.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('id', '=', $params['id']);
        }
        if (!empty($params['base_name'])) {
            $where[] = array('base_name', 'LIKE', '%'.$params['base_name'].'%');
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
     * IDで取得
     * @param int $id 拠点ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->leftjoin('m_staff', 'm_staff.id', '=', 'm_base.update_user')
                ->select(
                    'm_base.*', 
                    'm_staff.staff_name AS update_user_name'
                )
                ->where(['m_base.id' => $id])
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
                    'base_name' => $params['base_name'],
                    'base_short_name' => $params['base_short_name'],
                    'zipcode' => $params['zipcode'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
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
                        'base_name' => $params['base_name'],
                        'base_short_name' => $params['base_short_name'],
                        'zipcode' => $params['zipcode'],
                        'address1' => $params['address1'],
                        'address2' => $params['address2'],
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
                    'base_name',
                ])
                ->get()
                ;
        
        return $data;
    }
}