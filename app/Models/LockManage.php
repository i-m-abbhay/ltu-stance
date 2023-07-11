<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * ロック管理テーブル
 */
class LockManage extends Model
{
    // テーブル名
    protected $table = 't_lock_manage';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    /**
     * ロックデータ取得
     * @param string $tableName テーブル名
     * @param string $key キー
     */
    public function getLockData($tableName, $key) {
        $where = [];
        $where['table_name'] = $tableName;
        $where['key_id'] = $key;

        $lockData = $this
                    ->leftjoin('m_staff', 'm_staff.id', '=', 't_lock_manage.lock_user_id')
                    ->where($where)
                    ->select([
                        't_lock_manage.*',
                        'm_staff.staff_name AS lock_user_name'
                    ])
                    ->first();
                
        return $lockData;
    }

    /**
     * ロックデータ取得(主に一覧画面で使う用)
     * @param string $tableName テーブル名
     * @param string $keys キー
     */
    public function getLockDataForList($screenName, $tableName, $keys) {
        $where = [];
        $where['screen_name'] = $screenName;
        $where['table_name'] = $tableName;
        $lockData = $this
                    ->leftjoin('m_staff', 'm_staff.id', '=', 't_lock_manage.lock_user_id')
                    ->where($where)
                    ->whereIn('key_id', $keys)
                    ->select([
                        't_lock_manage.*',
                        'm_staff.staff_name AS lock_user_name'
                    ])
                    ->get();
                
        return $lockData;
    }

    /**
     * ロック確認
     * @param string $tableName テーブル名
     * @param int $key キー
     * @return boolean True:ロック中 False:ロックされていない 
     */
    public function isLocked($tableName, $key) {
        $where = [];
        $where['table_name'] = $tableName;
        $where['key_id'] = $key;
        
        $count = $this
                ->where($where)
                ->count()
                ;
        
        return ($count > 0);
    }

    /**
     * 自分がロックしているか確認
     * @param string $screenName 画面名
     * @param string $tableName テーブル名
     * @param int $key キー
     * @return boolean True:ロック中 False:ロックしていない 
     */
    public function isOwnLocked($screenName, $tableName, $key) {
        $where = [];
        $where['screen_name'] = $screenName;
        $where['table_name'] = $tableName;
        $where['key_id'] = $key;
        $where['lock_user_id'] = Auth::user()->id;
        
        $count = $this
                ->where($where)
                ->count()
                ;
        
        return ($count === 1);
    }

    /**
     * 自分がロックしているか確認
     * @param string $screenName 画面名
     * @param array $tableNameList テーブル名リスト
     * @param int $keys キーリスト
     * @return boolean True:全てのテーブルをロック中 False:ロックしていない 
     */
    public function isOwnLocks($screenName, $tableNameList, $keys){
        $lockCnt = 0;
        foreach ($tableNameList as $i => $tableName) {
            // ロックデータ取得
            $lockData = $this->isOwnLocked($screenName, $tableName, $keys[$i]);
            if ($lockData){
                $lockCnt++;
            }
        }
        return count($tableNameList) === $lockCnt;
    }

    /**
     * ロック獲得
     * @param Datetime $lockDt ロック日時
     * @param string $screenName 画面名
     * @param string $tableName テーブル名
     * @param int $key キー
     * @param boolean $unlockFlg ロック解除フラグ
     * @return boolean True:成功 False:失敗 
     */
    public function gainLock($lockDt, $screenName, $tableName, $key, $unlockFlg = false) {
        $result = false;

        $LogUtil = new LogUtil();
        try {
            // WHERE句
            $where = [];
            $where['table_name'] = $tableName;
            $where['key_id'] = $key;

            if ($unlockFlg) {
                // ロックデータ取得
                $lockData = $this
                            ->where($where)
                            ->first();
                if (!is_null($lockData)) {
                    
                    // WHERE句
                    $where2 = [];
                    $where2['screen_name'] = $lockData->screen_name;
                    $where2['lock_user_id'] = $lockData->lock_user_id;
                    $where2['lock_dt'] = $lockData->lock_dt;

                    // ログテーブルへ書き込み
                    // $LogUtil->putByData($lockData, config('const.logKbn.delete'));
                    $LogUtil->putByWhere($this->table, $where2, config('const.logKbn.delete'));

                    // ロックデータ削除
                    $this
                        ->where($where2)
                        ->delete();
                }
            }

            // 登録
            $this->insert([
                'screen_name' => $screenName,
                'table_name' => $tableName,
                'key_id' => $key,
                'lock_user_id' => Auth::user()->id,
                'lock_dt' => $lockDt,
            ]);

            // TODO:ユニーク制約つければカウント要らないかも
            // WHERE句
            $where = [];
            $where['table_name'] = $tableName;
            $where['key_id'] = $key;
            
            // データ件数取得
            $count = $this
                    ->where($where)
                    ->count()
                    ;

            if ($count === 1) {
                $result = true;
            } else {
                $result = false;
            }
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * ロックデータ削除
     * @param string $screenName 画面名
     * @param array $keys キー配列
     * @param int $userId 担当者ID
     * @return boolean True:成功 False:失敗 
     */
    public function deleteLockInfo($screenName, $keys, $userId) {
        $result = true;
        
        $LogUtil = new LogUtil();
        try {
            // 画面名から対象テーブルを取得
            $tableNameList = config('const.lockList.'.$screenName);

            $where = [];
            $where['screen_name'] = $screenName;
            $where['lock_user_id'] = $userId;

            foreach($tableNameList as $i => $tableName) {
                $where['table_name'] = $tableName;
                $where['key_id'] = $keys[$i];

                // ログテーブルへ書き込み
                $LogUtil->putByWhere($this->table, $where, config('const.logKbn.delete'));

                $deleteCnt = $this
                    ->where($where)
                    ->delete();
                
                // ロックデータを削除できなかった場合、自分がロックできていなかったことになるので失敗を返す
                if ($deleteCnt != 1) {
                    $result = false;
                    break;
                }
            }
                
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }
}