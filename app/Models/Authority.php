<?php

namespace App\Models;

use Auth;
use Carbon\Carbon;
use App\Libs\LogUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * 拠点マスタ
 */
class Authority extends Model
{
    // テーブル名
    protected $table = "m_authority";
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'authority_code',
        'staff_id',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at',		
    ];

    /**
     * 全件取得
     *
     * @return Collection
     */
    public function getAll()
    {
        return $this->where('del_flg', config('const.flg.off'))->get();
    }

    /**
     * [配列]データ追加
     *
     * @param [type] $paramsList
     * @return void
     */
    public function addList($paramsList)
    {
        try {
            $insertData = [];
            $userId = Auth::user()->id;
            $now = Carbon::now();

            foreach ($paramsList as $data) {
                $record = [];
                foreach($this->fillable as $column){
                    switch($column){
                        case 'id' :
                            break;
                        case 'del_flg':
                            $record[$column] = config('const.flg.off');
                            break;
                        case 'created_user':
                        case 'update_user':
                            $record[$column] = $userId;
                            break;
                        case 'created_at':
                        case 'update_at':
                            $record[$column] = $now;
                            break;
                        default :
                            if(is_object($data)){
                                $record[$column] = $data->$column;
                            }else{
                                $record[$column] = $data[$column];
                            }
                            break;
                    }
                }
                $insertData[] = $record;
            }
            $result = $this->insert($insertData);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 物理削除
     *
     * @param [type] $IDs
     * @return void
     */
    public function physicalDeleteList($IDs)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            foreach ($IDs as $id) {
                $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));
            }

            $deleteCnt = $this
                ->whereIn('id', $IDs)
                ->delete();
            $result = ($deleteCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 操作（機能）する権限を保持しているか
     *
     * @param integer $staffId 担当者ID
     * @param integer $authorityCode 権限コード（const.phpの"auth"の定数を使用してください）
     * @return integer
     */
    public static function hasAuthority(int $staffId, int $authorityCode): int {
        // Where句作成
        $where = [];
        $where[] = array("staff_id", "=", $staffId);
        $where[] = array("authority_code", "=", $authorityCode);

        return self::where($where)->exists() ? config('const.authority.has') : config('const.authority.none');
    }

    /**
     * 権限を持つユーザーを取得
     *
     * @param integer $authorityCode 権限コード（const.phpの"auth"の定数を使用してください）
     * @return
     */
    public function getAuthorityStaff(int $authorityCode) 
    {
        // Where句作成
        $where = [];
        $where[] = array("authority_code", "=", $authorityCode);

        $data = $this
                ->where($where)
                ->get()
                ;

        return $data;
    }
}