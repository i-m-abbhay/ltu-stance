<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;

/**
 * 入金用取込ファイル履歴
 */
class ImportHistory extends Model
{
    // テーブル名
    protected $table = 't_import_history';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params)
    {
        $result = false;
        try {
            $result = $this->insertGetId([
                'importfile_name'  => $params['importfile_name'],
                'importfile_create'  => $params['importfile_create'],
                'crc' => $params['crc'],
                'del_flg'       => config('const.flg.off'),
                'created_user'  => Auth::user()->id,
                'created_at'    => Carbon::now(),
                'update_user'   => Auth::user()->id,
                'update_at'     => Carbon::now(),
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }


     /**
     * CRC値で取得
     * @param  $crc
     * @return type 検索結果データ
     */
    public function getByCrc($crc,$fileName)
    {
        // Where句作成
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));
        $where[] = array('crc', '=', $crc);
        $where[] = array('importfile_name', '=', $fileName);

        // データ取得
        $data = $this
            ->from('t_import_history')
            ->where($where)
            ->selectRaw(
                '
                    id,
                    importfile_name,
                    importfile_create,
                    crc
                '
            )
            ->first();

        return $data;
    }

}
