<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;

/**
 * 得意先マスタ
 */
class CustomerBranch extends Model
{
    // テーブル名
    protected $table = 'm_customer_branch';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'type',
        'customer_id',
        'seq_no',
        'branch_name',
        'branch_kana',
        'tel',
        'fax',
        'email',
        'zipcode',
        'pref',
        'address1',
        'address2',
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
    public function getList($params) {
        // Where句作成
        $where = [];
        $where[] = array('m_customer_branch.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('id', '=', $params['id']);
        }
        if (!empty($params['customer_name'])) {
            $where[] = array('branch_name', 'LIKE', '%'.$params['branch_name'].'%');
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
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params, $customerId)
    {
        $result = false;

        try{
            $result = $this->insertGetId([
                'customer_id' => $customerId,
                'seq_no' => $params['seq'],
                'branch_name' => $params['branch_name'],
                'branch_kana' => $params['branch_kana'],
                'tel' => $params['tel'],
                'fax' => $params['fax'],
                'email' => $params['email'],
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


            return $result;
        }catch (\Exception $e){
            throw $e;
        }
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params, $id) {
        $result = false;
        try {
             // ログテーブルへの書き込み
             $LogUtil = new LogUtil();
             $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));
 
            $result = $this
                ->where('id', $params['id'])
                // ->where(['seq_no' => $params['seq']])   
                ->update([
                    'branch_name' => $params['branch_name'],
                    'branch_kana' => $params['branch_kana'],
                    'tel' => $params['tel'],
                    'fax' => $params['fax'],
                    'email' => $params['email'],
                    'zipcode' => $params['zipcode'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
                    'latitude_jp' => $params['latitude_jp'],
                    'longitude_jp' => $params['longitude_jp'],
                    'latitude_world' => $params['latitude_world'],
                    'longitude_world' => $params['longitude_world'],
                    'del_flg' => $params['del_flg'],
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
     * @param int $id 得意先ID
     * @return type 全件
     */
    public function getById($id) {
        $data = $this
                ->where(['customer_id' => $id])
                ->where(['del_flg' => config('const.flg.off')])
                ->get()
                ;

        return $data;
    }

}