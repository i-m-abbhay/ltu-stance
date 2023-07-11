<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use DB;

/**
 * 出荷積込
 */
class Loading extends Model
{
    // テーブル名
    protected $table = 't_loading';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
        'id',
        'shipment_id',
        'shipment_detail_id',
        'order_detail_id',
        'quote_id',
        'quote_detail_id',
        'shipment_kind',
        'zipcode',
        'pref',
        'address1',
        'address2',
        'latitude_jp',
        'longitude_jp',
        'latitude_world',
        'longitude_world',
        'product_id',
        'product_code',
        'from_warehouse_kbn',
        'from_warehouse_id',
        'loading_date',
        'loading_quantity',
        'delivery_finish_flg',
        'del_flg',
        'created_user',
        'created_at',
        'update_user',
        'update_at'
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
        $where[] = array('t_loading.del_flg', '=', config('const.flg.off'));
        // if (!empty($params['shipment_id'])) {
        //     $where[] = array('t_loading.shipment_id', '=', $params['shipment_id']);
        // }
        if (!empty($params['shipment_detail_id'])) {
            $where[] = array('t_loading.shipment_detail_id', '=', $params['shipment_detail_id']);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->orderBy('t_loading.id', 'asc')
            ->select('t_loading.id')
            ->distinct()
            ->get();

        return $data;
    }

    /**
     * 案件一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getListMatter($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_loading.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_loading.delivery_finish_flg', '=', config('const.flg.off'));
        if (!empty($params['shelf_area']) && $params['shelf_area'] != "　") {
            $where[] = array('m_warehouse.truck_flg', '=', config('const.flg.on'));
            $where[] = array('m_shelf_number.shelf_area', '=', $params['shelf_area']);
        }

        // データ取得
        $data = $this
            ->where($where)
            ->leftjoin('t_shipment', 't_shipment.id', '=', 't_loading.shipment_id')
            ->leftjoin('t_matter', 't_matter.id', '=', 't_loading.matter_id')
            ->leftjoin('m_address', 'm_address.id', '=', 't_matter.address_id')
            ->leftjoin('m_shelf_number', 'm_shelf_number.id', '=', 't_loading.to_shelf_number_id')
            ->leftjoin('m_warehouse', 'm_warehouse.id', '=', 't_loading.to_warehouse_id')
            ->orderBy('t_matter.matter_no', 'asc')
            ->select(
                't_matter.matter_no',
                DB::raw('max(t_matter.matter_name) as matter_name'),
                // DB::raw('max(CONCAT(m_address.address1, m_address.address2)) as address'),
                DB::raw('max(CONCAT(COALESCE(m_address.address1, \'\'), COALESCE(m_address.address2, \'\'))) as address'),
                DB::raw('count(t_loading.product_code) as product_count'),
                DB::raw('max(t_shipment.shipment_comment) as shipment_comment')
            )
            ->groupBy('t_matter.matter_no')
            ->get();

        return $data;
    }

    /**
     * 案件詳細一覧取得
     * @param array $params 検索条件の配列
     * @return type 検索結果データ
     */
    public function getListDetail($params)
    {
        // Where句作成
        $where = [];
        $where[] = array('t_loading.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_loading.delivery_finish_flg', '=', config('const.flg.off'));
        if ($params['matter_no'] == "null") {
            $where[] = array('t_matter.matter_no', '=', null);
        } else if (!empty($params['matter_no'])) {
            $where[] = array('t_matter.matter_no', '=', $params['matter_no']);
        }

        if ($params['shelf_area'] !== null && $params['shelf_area'] !== '') {
            $where[] = array('m_shelf_number.shelf_area', '=',  $params['shelf_area']);
        } 

        // データ取得
        $data = $this
            ->where($where)
            ->leftjoin('t_order_detail', 't_order_detail.id', '=', 't_loading.order_detail_id')
            ->leftjoin('t_quote_detail', 't_quote_detail.id', '=', 't_loading.quote_detail_id')
            ->leftjoin('t_matter', 't_matter.id', '=', 't_loading.matter_id')
            ->leftjoin('m_product', 'm_product.id', '=', 't_loading.product_id')
            ->leftjoin('t_shipment_detail', 't_shipment_detail.id', '=', 't_loading.shipment_detail_id')
            ->leftjoin('m_shelf_number', 'm_shelf_number.id', '=', 't_loading.to_shelf_number_id')
            ->leftjoin('t_reserve', 't_reserve.id', '=', 't_shipment_detail.reserve_id')
            // ->leftjoin('t_qr_detail', function($join){
            //     $join->on('t_loading.product_id', '=', 't_qr_detail.product_id');
            //     $join->on('t_loading.matter_id', '=', 't_qr_detail.matter_id');
            // })            
            // ->leftjoin('t_qr', 't_qr_detail.qr_id', '=', 't_qr.id')
            ->select(
                't_matter.matter_name',
                't_matter.customer_id',
                'm_product.product_name',
                't_quote_detail.model',
                't_loading.*',
                't_loading.id as loading_id',
                DB::raw('0 as delivery_quantity')
                //'t_reserve.stock_flg'
                // 't_qr.qr_code',
                // 't_qr_detail.quantity as qr_quantity'
            )
            ->orderBy('t_loading.product_id', 'asc')
            ->orderBy('t_loading.loading_quantity', 'desc')
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
                'matter_id' => $params['matter_id'],
                'shipment_id' => $params['shipment_id'],
                'shipment_detail_id' => $params['shipment_detail_id'],
                'order_detail_id' => $params['order_detail_id'],
                'quote_id' => $params['quote_id'],
                'quote_detail_id' => $params['quote_detail_id'],
                'shipment_kind' => $params['shipment_kind'],
                'zipcode' => $params['zipcode'],
                'address1' => $params['address1'],
                'address2' => $params['address2'],
                'latitude_jp' => $params['latitude_jp'],
                'longitude_jp' => $params['longitude_jp'],
                'latitude_world' => $params['latitude_world'],
                'longitude_world' => $params['longitude_world'],
                'product_id' => $params['product_id'],
                'product_code' => $params['product_code'],
                'from_warehouse_kbn' => $params['from_warehouse_kbn'],
                'from_warehouse_id' => $params['from_warehouse_id'],
                'to_warehouse_id' => $params['to_warehouse_id'],
                'to_shelf_number_id' => $params['to_shelf_number_id'],
                'loading_date' => Carbon::now(),
                'loading_quantity' => $params['loading_quantity'],
                'stock_flg' => $params['stock_flg'],
                'delivery_finish_flg' => 0,
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

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'matter_id' => $params['matter_id'],
                    'shipment_id' => $params['shipment_id'],
                    'shipment_detail_id' => $params['shipment_detail_id'],
                    'order_detail_id' => $params['order_detail_id'],
                    'quote_id' => $params['quote_id'],
                    'quote_detail_id' => $params['quote_detail_id'],
                    'shipment_kind' => $params['shipment_kind'],
                    'zipcode' => $params['zipcode'],
                    'address1' => $params['address1'],
                    'address2' => $params['address2'],
                    'latitude_jp' => $params['latitude_jp'],
                    'longitude_jp' => $params['longitude_jp'],
                    'latitude_world' => $params['latitude_world'],
                    'longitude_world' => $params['longitude_world'],
                    'product_id' => $params['product_id'],
                    'product_code' => $params['product_code'],
                    'from_warehouse_kbn' => $params['from_warehouse_kbn'],
                    'from_warehouse_id' => $params['from_warehouse_id'],
                    'to_warehouse_id' => $params['to_warehouse_id'],
                    'to_shelf_number_id' => $params['to_shelf_number_id'],
                    'stock_flg' => $params['stock_flg'],
                    'loading_date' => Carbon::now(),
                    'loading_quantity' => $params['loading_quantity'],
                    'delivery_finish_flg' => 0,
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
     * 数量更新
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateQuantity($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('shipment_id', $params['shipment_id'])
                ->where('shipment_detail_id', $params['shipment_detail_id'])
                ->update([
                    'loading_date' => $params['loading_date'],
                    'loading_quantity' => $params['loading_quantity'],
                    'update_user' => Auth::user()->id,
                    'update_at' => Carbon::now()
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 納品完了
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateDeliveryFinish($params)
    {
        $result = false;
        try {
            $updateCnt = $this
                ->where('id', $params['loading_id'])
                ->update([
                    'delivery_finish_flg' => 1,
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
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $id, config('const.logKbn.soft_delete'));

            $updateCnt = $this
                ->where('id', $id)
                ->update([
                    'del_flg' => config('const.flg.on'),
                    'modified_user' => Auth::user()->id,
                    'modified' => Carbon::now(),
                ]);
            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 移動完了
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateMoveFinish($params)
    {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            $updateCnt = $this
                ->where('id', $params['id'])
                ->update([
                    'delivery_finish_flg' => 2,
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
     * 特定の出荷指示に対するデータがあるか
     * @param array $shipmentId 出荷指示ID
     * @return type 検索結果データ
     */
    public function isExistByShipmentId($shipmentId)
    {
        $result = false;
        // Where句作成
        $where = [];
        $where[] = array('t_loading.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_loading.shipment_id', '=', $shipmentId);


        // データ取得
        $data = $this
            ->where($where)
            ->first();

        if ($data !== null) {
            $result = true;
        }

        return $result;
    }

    /**
     * 案件番号を元に積込中が存在するか確認
     *
     * @param int $matterId 案件ID
     * @return boolean true：存在する false：存在しない
     */
    public function hasLoadingByMatter($matterId) {
        $where = [];
        $where[] = array('matter_id', '=', $matterId);
        $where[] = array('delivery_finish_flg', '=', config('const.deliveryFinishFlg.val.unfinished'));
        $where[] = array('del_flg', '=', config('const.flg.off'));

        // 存在チェック
        $result = $this
            ->where($where)
            ->exists();
            
        return $result;
    }
}
