<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use App\Libs\LogUtil;
use DB;

/**
 * 廃棄
 */
class Discard extends Model
{
    // テーブル名
    protected $table = 't_discard';
    // タイムスタンプ自動更新Off
    public $timestamps = false;

    /**
     * 廃棄一覧を取得
     * 
     * @return 
     */
    public function getListDiscard(array $params)
    {
        $where = [];
        $where[] = array('d.discard_finish', '=', config('const.flg.off'));
        if ($params['warehouse_id'] != null && $params['warehouse_id'] != ""  && $params['warehouse_id'] != 0) {
            $where[] = array('d.warehouse_id', '=', $params['warehouse_id']);
        }
        $where[] = array('d.approval_status', '=', config('const.flg.on'));
        $where[] = array('d.del_flg', '=', config('const.flg.off'));
        $joinwhere = [];
        $joinwhere['product'][] = array('p.del_flg', '=', config('const.flg.off'));
        $joinwhere['matter'][] = array('m.del_flg', '=', config('const.flg.off'));
        $joinwhere['qr'][] = array('q.del_flg', '=', config('const.flg.off'));
        $joinwhere['qr_detail'][] = array('qd.del_flg', '=', config('const.flg.off'));

        $data = $this
            ->from('t_discard as d')
            ->leftJoin('m_product as p', function ($join) use ($joinwhere) {
                $join->on('p.id', '=', 'd.product_id')
                    ->where($joinwhere['product']);
            })

            ->leftJoin('t_qr as q', function ($join) use ($joinwhere) {
                $join->on('q.qr_code', '=', 'd.qr_code')
                    ->where($joinwhere['qr']);
            })

            ->leftJoin('t_qr_detail as qd', function ($join) use ($joinwhere) {
                $join->on('qd.qr_id', '=', 'q.id')
                    ->where($joinwhere['qr_detail']);
            })

            ->leftJoin('t_matter as m', function ($join) use ($joinwhere) {
                $join->on('m.id', '=', 'qd.matter_id')
                    ->where($joinwhere['matter']);
            })

            ->where($where)
            ->selectRaw(
                "d.id,
                    d.warehouse_move_id,
                    d.product_id,
                    p.product_code,
                    p.model,
                    p.product_name,
                    d.discard_quantity as quantity,
                    p.stock_unit as unit,
                    ifnull(qd.matter_id,0) as matter_id,
                    ifnull(m.customer_id,0) as customer_id,
                    d.warehouse_id as warehouse_id,
                    d.shelf_number_id as shelf_number_id,
                    d.qr_code as qr_code,
                    q.id as qr_id,
                    0 as returns_quantity"
            )
            ->get();

        return $data;
    }

    /**
     * 更新（廃棄画面）
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateByIdDiscard($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'discard_finish' => $params['discard_finish'],
                    'discard__move_id' => $params['discard__move_id'],
                    'image_sign' => $params['image_sign'],
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
     * 登録
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function add($params) {
        $result = false;
        try {
            $result = $this->insertGetId([
                    'discard_kind' => $params['discard_kind'],
                    'warehouse_move_id' => $params['warehouse_move_id'],
                    'qr_code' => $params['qr_code'],
                    'product_id' => $params['product_id'],
                    'warehouse_id' => $params['warehouse_id'],
                    'shelf_number_id' => $params['shelf_number_id'],
                    'discard_quantity' => $params['discard_quantity'],
                    'approval_status' => $params['approval_status'],
                    'approval_user' => $params['approval_user'],
                    'approval_at' => $params['approval_at'],
                    'discard_finish' => $params['discard_finish'],
                    'discard__move_id' => $params['discard__move_id'],
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
     * 論理削除
     * @param int $qr_code QRCODE
     * @return boolean True:成功 False:失敗 
     */
    public function deleteByQrCode($qr_code) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();

            $where = [];
            $where[] = array('qr_code', '=', $qr_code);
            
            $data = $this
                    ->where($where)
                    ->get()
                    ;

            foreach ($data as $key => $row) {
                $LogUtil->putById($this->table, $row->id, config('const.logKbn.soft_delete'));

                $updateCnt = $this
                    ->where('id', $row->id)
                    ->update([
                        'del_flg' => config('const.flg.on'),
                        'update_user' => Auth::user()->id,
                        'update_at' => Carbon::now(),
                    ])
                    ;
            }
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }
}
