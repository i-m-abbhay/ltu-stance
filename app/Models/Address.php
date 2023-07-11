<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 住所マスタ
 */
class Address extends Model
{
    // テーブル名
    protected $table = 'm_address';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;
    
    /**
     * 一覧取得
     * @param 案件ID 
     * @return type 検索結果データ
     */
    public function getList($id) {
        // Where句作成
        $where = [];
        $where[] = array('m_address.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_address.id', '=', $id);
        
        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('id', 'asc')
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

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
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
        }catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


    /**
     * IDで取得
     * @param int $id 住所ID
     * @return type 検索結果データ（1件）
     */
    public function getById($id) {
        $data = $this
                ->where(['id' => $id])
                ->first()
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
        
        // データ取得
        $data = $this
                ->where($where)    
                ->orderBy('id', 'asc')
                ->select([
                    'id',
                    'address1',
                    'address2'
                ])
                ->selectRaw('CONCAT(COALESCE(address1, \'\'), COALESCE(address2, \'\')) as address')
                ->get()
                ;
        
        return $data;
    }
}
