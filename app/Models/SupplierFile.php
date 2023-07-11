<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 住所マスタ
 */
class SupplierFile extends Model
{
    // テーブル名
    protected $table = 'm_supplier_file';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 全データ取得
     *
     * @return void
     */
    public function getAll(){
        $data = $this
                ->where(['del_flg' => config('const.flg.off')])
                ->get()
                ;

        return $data;
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
     * 仕入先IDで取得
     * @param int $supplierId 仕入先ID
     * @return type 検索結果データ（1件）
     */
    public function getBySupplierId($supplierId) {
        $data = $this
                ->where([
                    ['supplier_id', $supplierId],
                    ['del_flg', config('const.flg.off')]
                ])
                ->first()
                ;
        return $data;
    }

    /**
     * 仕入先IDで取得
     * @param int $supplierId 仕入先ID
     * @return type 検索結果データ（1件）
     */
    public function getFileListBySupplierId($supplierId) {
        $where = [];
        $where[] = array('supplier_id', '=', $supplierId);
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
                ->where($where)
                ->selectRaw('
                    m_supplier_file.id,
                    m_supplier_file.supplier_id,
                    m_supplier_file.file_name,
                    DATE_FORMAT(m_supplier_file.start_date, "%Y/%m/%d") AS start_date,
                    m_supplier_file.del_flg,
                    m_supplier_file.created_user,
                    m_supplier_file.created_at,
                    m_supplier_file.update_user,
                    m_supplier_file.update_at
                ')
                ->orderBy('m_supplier_file.start_date')
                ->get()
                ;

        return $data;
    }

    /**
     * 物理削除
     * @param $id
     * @return void
     */
    public function deleteById($id)
    {
        $result = false;
        try{
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $result = $this
                    ->where('id', $id)
                    ->delete()
                    ;
        } catch(\Exception $e) {
            throw $e;
        }
        return $result;
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
                    'supplier_id' => $params['supplier_id'],
                    'file_name' => $params['file_name'],
                    'start_date' => $params['start_date'],
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

}
