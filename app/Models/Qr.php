<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * QRコード管理
 */
class Qr extends Model
{
    // テーブル名
    protected $table = 't_qr';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'qr_code',
        'qr_datetime',
        'print_number',
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
    public function getList($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_qr.del_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('t_qr.id', '=', $params['id']);
        }
        if (!empty($params['qr_code'])) {
            $where[] = array('t_qr.qr_code', '=', $params['qr_code']);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('t_qr.id', 'asc')
            ->select(
                't_qr.*'
            )
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 新規登録
     *
     * @param array $params
     * @return $result
     */
    public function add(array $params)
    {
        $result = false;

        try {
            $result = $this->insertGetId([
                'qr_code' => $params['qr_code'],
                'qr_datetime' => Carbon::now(),
                'print_number' => $params['print_number'],
                'del_flg' => config('const.flg.off'),
                'created_user' => Auth::user()->id,
                'created_at' => Carbon::now(),
                'update_user' => Auth::user()->id,
                'update_at' => Carbon::now(),
            ]);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * 更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $result = $this->insertGetId([
                'qr_code' => $params['qr_code'],
                'qr_datetime' => Carbon::now(),
                'print_number' => $params['print_number'],
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
     * 物理削除
     * @param $id 
     * @return void
     */
    public function deleteByQrId($id)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.delete'));

            $result = $this
                ->where('id', $id)
                ->delete();
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 最新ID取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getLastId($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_qr.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->select(
                DB::raw('max(t_qr.id) as qr_id')
            )
            ->get();

        return $data[0];
    }

    /**
     * コンボボックス用取得
     *
     * @return void
     */
    public function getComboList()
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $data = $this
            ->where($where)
            ->select([
                'id',
                'qr_code'
            ])
            ->orderBy('created_at', 'desc')
            ->get();

        return $data;
    }

    /**
     * IDで取得
     *
     * @param  $id
     * @return void
     */
    public function getById($id) 
    {
        $where = [];
        $where[] = array('qr.del_flg', '=', config('const.flg.off'));
        $where[] = array('qr.id', '=', $id);

        $data = $this
                ->from('t_qr as qr')
                ->leftJoin('t_qr_detail as qr_detail', 'qr_detail.qr_id', '=', 'qr.id')
                ->leftJoin('t_matter as mat', 'mat.id', '=', 'qr_detail.matter_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'qr_detail.product_id')
                ->leftJoin('t_arrival as arr', 'arr.id', '=', 'qr_detail.arrival_id')
                ->leftJoin('m_customer as cus', 'cus.id', '=', 'qr_detail.customer_id')
                ->selectRaw('
                        qr.id as qr_id,
                        qr.qr_code,
                        COALESCE(mat.matter_name, \'\') AS matter_name,
                        COALESCE(DATE_FORMAT(arr.arrival_date, "%Y/%m/%d"), \'\') AS arrival_date,
                        COALESCE(cus.customer_name, \'\') AS customer_name,
                        COALESCE(pro.product_code, \'\') AS product_code,
                        COALESCE(pro.product_name, \'\') AS product_name,
                        qr_detail.quantity as quantity
                ')
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * QRコードで取得
     *
     * @param  $id
     * @return void
     */
    public function getByQrCode($qr_code) 
    {
        $where = [];
        $where[] = array('qr.del_flg', '=', config('const.flg.off'));
        $where[] = array('qr.qr_code', '=', $qr_code);

        $data = $this
                ->from('t_qr as qr')
                ->leftJoin('t_qr_detail as qr_detail', 'qr_detail.qr_id', '=', 'qr.id')
                ->leftJoin('t_matter as mat', 'mat.id', '=', 'qr_detail.matter_id')
                ->leftJoin('m_product as pro', 'pro.id', '=', 'qr_detail.product_id')
                ->leftJoin('t_arrival as arr', 'arr.id', '=', 'qr_detail.arrival_id')
                ->leftJoin('m_customer as cus', 'cus.id', '=', 'qr_detail.customer_id')
                ->selectRaw('
                        qr.id as qr_id,
                        qr.qr_code,
                        COALESCE(mat.matter_name, \'\') AS matter_name,
                        COALESCE(DATE_FORMAT(arr.arrival_date, "%Y/%m/%d"), \'\') AS arrival_date,
                        COALESCE(cus.customer_name, \'\') AS customer_name,
                        COALESCE(pro.product_code, \'\') AS product_code,
                        COALESCE(pro.product_name, \'\') AS product_name,
                        qr_detail.quantity as quantity
                ')
                ->where($where)
                ->first()
                ;

        return $data;
    }

    /**
     * ID or QR_CODEで取得
     *
     * @param  $params
     * @return 印刷枚数['print_number']
     */
    public function getPrintNumber($params) 
    {
        $where = [];
        $where[] = array('qr.del_flg', '=', config('const.flg.off'));
        if (!empty($params['qr_id'])) {
            $where[] = array('qr.id', '=', $params['qr_id']);
        }
        if (!empty($params['qr_code'])) {
            $where[] = array('qr.qr_code', '=', $params['qr_code']);
        }


        $data = $this
                ->from('t_qr as qr')
                ->select(
                        'qr.print_number'
                        )
                ->where($where)
                ->first()
                ;

        return $data;
    }


    public function getQrDetailCounts($params) 
    {
        $where = [];
        $where[] = array('qr.del_flg', '='. config('const.flg.off'));
        $where[] = array('qr_detail.del_flg', '='. config('const.flg.off'));

        if (!empty($params['qr_id'])) {
            $where[] = array('qr.id', '=', $params['qr_id']);
        }
        if (!empty($params['qr_code'])) {
            $where[] = array('qr.qr_code', '=', $params['qr_code']);
        }

        $data = $this
                ->from('t_qr AS qr')
                ->leftJoin('t_qr_detail AS qr_detail', 'qr_detail.qr_id', '=', 'qr.id')
                ->where($where)
                ->selectRaw('
                    qr.id,
                    COUNT(qr_detail.id) AS count
                ')
                ->groupBy('qr.id')
                ->first()
                ;

        return $data;
    }
}
