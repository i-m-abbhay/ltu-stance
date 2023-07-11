<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;

/**
 * パーソンマスタ
 */
class Person extends Model
{
    // テーブル名
    protected $table = 'm_person';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'type',
        'company_id',
        'seq_no',
        'name',
        'kana',
        'belong_name',
        'position',
        'tel1',
        'tel2',
        'fax',
        'email1',
        'email2',
        'image_file',
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
        $where[] = array('m_customer.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('id', '=', $params['id']);
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
     * @return $result 登録されたパーソンID (画像アップロード先に使用)
     */
    public function add(array $params, $type, $parentId, $fileName = '')
    {
        $result = false;

        try{
            $result = $this->insertGetId([
                'type' => $type,
                'seq_no' => $params['seq'],
                'company_id' => $parentId,
                'name' => $params['name'],
                'kana' => $params['kana'],
                'belong_name' => $params['belong_name'],
                'position' => $params['position'],
                'tel1' => $params['tel1'],
                'tel2' => $params['tel2'],
                'email1' => $params['email1'],
                'email2' => $params['email2'],
                'image_file' => $fileName,
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
    public function updateById($params, $fileName = '') {
        $result = false;
        try {
             // ログテーブルへの書き込み
             $LogUtil = new LogUtil();
             $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));
 

            if(isset($fileName) || !empty($fileName)){
                $result = $this
                        ->where(['id' => $params['id']])
                        ->update([
                            'name' => $params['name'],
                            'kana' => $params['kana'],
                            'belong_name' => $params['belong_name'],
                            'position' => $params['position'],
                            'tel1' => $params['tel1'],
                            'tel2' => $params['tel2'],
                            'email1' => $params['email1'],
                            'email2' => $params['email2'],
                            'image_file' => $fileName,
                            'del_flg' => $params['del_flg'],
                            'update_user' => Auth::user()->id,
                            'update_at' => Carbon::now(),
                        ]);
            }else {
                $result = $this
                        ->where(['id' => $params['id']])
                        ->update([
                            'name' => $params['name'],
                            'kana' => $params['kana'],
                            'belong_name' => $params['belong_name'],
                            'position' => $params['position'],
                            'tel1' => $params['tel1'],
                            'tel2' => $params['tel2'],
                            'email1' => $params['email1'],
                            'email2' => $params['email2'],
                            'del_flg' => $params['del_flg'],
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
     * @return type 得意先に所属したパーソン
     */
    public function getById($id) {
        $data = $this
                ->where(['company_id' => $id])
                ->where(['del_flg' => config('const.flg.off')])
                ->get()
                ;

        return $data;
    }

    /**
     * コンボボックス用データ取得
     *
     * @param int $personType パーソンマスタの企業種別（const.person_type）
     * @param int $companyId 企業ID（得意先ID or 仕入先ID）
     * @return void
     */
    public function getComboList($personType, $companyId = null) {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('type', '=', $personType);
        if (!is_null($companyId)) {
            $where[] = array('company_id', '=', $companyId);
        }
        
        // データ取得
        $data = $this
                ->where($where)
                ->orderBy('company_id', 'asc')
                ->orderBy('seq_no', 'asc')
                ->select([
                    'id',
                    'company_id',
                    'seq_no',
                    'name',
                    'kana'
                ])
                ->get()
                ;
        
        return $data;
    }

    /**
     * company_idで取得
     * @param int $id 企業ID
     * @return type 
     */
    public function getByCompanyId($companyId, $type) {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('company_id', '=', $companyId);
        $where[] = array('type', '=', $type);

        $data = $this
                ->where($where)
                ->orderBy('id')
                ->get()
                ;

        return $data;
    }

}