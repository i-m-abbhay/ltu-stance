<?php

namespace App\Models;

use DB;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;

/**
 * 採番マスタ
 */
class NumberManage extends Model
{
    // 独自コネクション名
    const DB_CONNECTION = 'mysql_number_manage';

    // 独自コネクション
    protected $connection = self::DB_CONNECTION;

    // テーブル名
    protected $table = 'm_number_manage';

    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected static function boot()
    {
        parent::boot();

        // デフォルトコネクションをコピーして独自コネクションを作る
        config(['database.connections.' . self::DB_CONNECTION =>
            config('database.connections.' . config('database.default')),
        ]);
    }

    /**
     * 連番取得
     * @param array $kbn(const.phpの"number_manage.kbn"の定数を使用してください) 
     * @param string $ym 年月(yyyyMM)　※スラッシュなし
     * @param string $departmentSymbol　部門.部門記号(発注番号生成時のみ必須)
     * @param string $staffShortName　担当者.担当者記号(発注番号生成時のみ必須)
     * @return int -1なら失敗
     */
    public function getSeqNo($kbn, $ym, $departmentSymbol = null, $staffShortName = null) {

        $rtn = config('const.number_manage.result.fail');
        $result = false;

        DB::beginTransaction();
        try {
            $where = [];
            $where[] = array('kbn', '=', $kbn);
            $where[] = array('ym', '=', $ym);

            switch($kbn){
                case config('const.number_manage.kbn.order'):
                    $where[] = array('department_symbol', '=', $departmentSymbol);
                break;
            }
    
            $tmpNo = ($this->lockForUpdate()->where($where)->first())['seq_no'];
            if (is_null($tmpNo)) {
                $seqNo = config('const.number_manage.default_value');
                $result = $this->insert([
                    'kbn' => $kbn,
                    'ym' => $ym,
                    'department_symbol' => $departmentSymbol,
                    'seq_no' => $seqNo + 1,
                    'created_user' => Auth::user()->id,
                    'created_at' => Carbon::now(),
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now(),
                ]);
            } else {
                $seqNo = $tmpNo;
                $result = $this
                    ->where($where)
                    ->update([
                        'seq_no' => $seqNo + 1,
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ]);
            }

            if (!$result || $result == 0) {
                throw new \Exception();
            }

            $rtn = $this->createSeqNo($kbn, $ym, $seqNo, $departmentSymbol, $staffShortName);
            DB::commit();
        } catch (\Exception $e) {
            $rtn = config('const.number_manage.result.fail');
            DB::rollBack();

            throw $e;
        }

        return $rtn;
    }

    /**
     * 各種番号生成
     * TODO: 番号の付け方の合意を取っていない
     *
     * @param string $kbn
     * @param string $ym
     * @param int $seqNo
     * @param string $departmentSymbol
     * @param string $staffShortName
     * @return string
     */
    private function createSeqNo($kbn, $ym, $seqNo, $departmentSymbol = null, $staffShortName = null) {
        $resultNo = '';
        switch ($kbn) {
            case config('const.number_manage.kbn.matter'):
                // 案件番号 yyMMnnnnn
                $resultNo = substr($ym, -4).sprintf("%05d", $seqNo);
                break;
            case config('const.number_manage.kbn.quote'):
                // 見積番号 yyMMnnnnn
                $resultNo = substr($ym, -4).sprintf("%05d", $seqNo);
                break;
            case config('const.number_manage.kbn.order'):
                // 発注番号 部門記号yyMMnnnn担当者記号3桁
                $resultNo = $departmentSymbol.substr($ym, -4).sprintf("%04d", $seqNo).$staffShortName;
                break;
            case config('const.number_manage.kbn.qr'):
                // QRコード yyMMnnnnnn
                $ym = substr($ym, 2);
                $resultNo = $ym.sprintf("%06d", $seqNo);
                break;
            case config('const.number_manage.kbn.request'):
                // 請求番号 SKyyMMnnnnn
                $resultNo = "SK".substr($ym, -4).sprintf("%05d", $seqNo);
                break;
            case config('const.number_manage.kbn.credited'):
                // 入金番号 NKyyMMnnnnn
                $resultNo = "NK".substr($ym, -4).sprintf("%05d", $seqNo);
                break;
            case config('const.number_manage.kbn.payment'):
                // 支払番号 SHyyMMnnnnn
                $resultNo = "SH".substr($ym, -4).sprintf("%05d", $seqNo);
                break;
            default:
            $resultNo = $seqNo;
            break;
            
        }
        return $resultNo;
    }
}