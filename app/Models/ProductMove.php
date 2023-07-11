<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use DB;
use App\Libs\LogUtil;


/**
 * 入出庫トラン
 */
class ProductMove extends Model
{
    // テーブル名
    protected $table = 't_product_move';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    public function ProductMove()
    {
        return $this->belongsTo('App\Models\ProductMove');
    }

    protected $fillable = [
        'product_id',
        'product_code',
        'from_warehouse_id',
        'from_shelf_number_id',
        'to_warehouse_id',
        'to_shelf_number_id',
        'move_kind',
        'move_flg',
        'quantity',
        'move_date',
        'order_id',
        'order_no',
        'order_detail_id',
        'reserve_id',
        'shipment_detail_id',
        'arrival_id',
        'sales_id',
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
        $where[] = array('t_product_move.del_flg', '=', config('const.flg.off'));

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('id', 'asc')
            ->get();

        return $data;
    }

    /**
     * 重複チェック
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getId($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_product_move.product_id', '=', $params['product_id']);
        $where[] = array('t_product_move.from_warehouse_id', '=', $params['from_warehouse_id']);
        $where[] = array('t_product_move.from_shelf_number_id', '=', $params['from_shelf_number_id']);
        $where[] = array('t_product_move.to_warehouse_id', '=', $params['to_warehouse_id']);
        $where[] = array('t_product_move.to_shelf_number_id', '=', $params['to_shelf_number_id']);
        $where[] = array('t_product_move.move_kind', '=', $params['move_kind']);
        $where[] = array('t_product_move.created_user', '=',  Auth::user()->id);
        $where[] = array('t_product_move.created_at', '=', Carbon::now());

        // データ取得
        $data = $this
            ->where($where)
            ->selectRaw('id')
            ->first();

        return $data == null?null:$data['id'];
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
                'stock_flg' => $params['stock_flg'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'from_warehouse_id' => $params['from_warehouse_id'],
                'from_shelf_number_id' => $params['from_shelf_number_id'],
                'to_warehouse_id' => $params['to_warehouse_id'],
                'to_shelf_number_id' => $params['to_shelf_number_id'],
                'move_kind' => $params['move_kind'],
                'move_flg' => $params['move_flg'],
                'quantity' => $params['quantity'],
                'move_date' => Carbon::now(),
                'order_id' => $params['order_id'],
                'order_no' => $params['order_no'],
                'order_detail_id' => $params['order_detail_id'],
                'reserve_id' => $params['reserve_id'],
                'shipment_id' => $params['shipment_id'],
                'shipment_detail_id' => $params['shipment_detail_id'],
                'arrival_id' => $params['arrival_id'],
                'sales_id' => $params['sales_id'],
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
            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'product_id' => $params['product_id'],
                    'product_code' => $params['product_code'],
                    'from_warehouse_id' => $params['from_warehouse_id'],
                    'from_shelf_number_id' => $params['from_shelf_number_id'],
                    'to_warehouse_id' => $params['to_warehouse_id'],
                    'to_shelf_number_id' => $params['to_shelf_number_id'],
                    'move_kind' => $params['move_kind'],
                    'move_flg' => $params['move_flg'],
                    'quantity' => $params['quantity'],
                    'move_date' => Carbon::now(),
                    'order_id' => $params['order_id'],
                    'order_no' => $params['order_no'],
                    'order_detail_id' => $params['order_detail_id'],
                    'reserve_id' => $params['reserve_id'],
                    'shipment_detail_id' => $params['shipment_detail_id'],
                    'arrival_id' => $params['arrival_id'],
                    'sales_id' => $params['sales_id'],
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
     * 論理削除
     * @param $id
     * @return void
     */
    public function deleteById($id)
    {
        $result = false;
        try {
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
     * IDで取得
     * @param int $id ID
     * @return type 入荷データ
     */
    public function getById($id)
    {
        $data = false;

        $data = $this
            ->where(['id' => $id])
            ->where(['del_flg' => config('const.flg.off')])
            ->get();

        return $data;
    }

    /**
     * IDから移動先棚情報を取得
     * @param int $id ID
     * @return 
     */
    public function getToShelfInfo($id)
    {

        $data = $this
            ->from('t_product_move as p')
            ->leftJoin('m_shelf_number as s', function ($join) {
                $join->on('s.id', '=', 'p.to_shelf_number_id');
            })
            ->where(['p.id' => $id])
            ->where(['p.del_flg' => config('const.flg.off')])
            ->first();

        return $data;
    }
}
