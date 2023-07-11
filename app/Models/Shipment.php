<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Auth;
use Exception;
use App\Libs\LogUtil;
use App\Libs\Common;
use DB;
// use Illuminate\Support\Facades\Log;

/**
 * 出荷指示
 */
class Shipment extends Model
{
    // テーブル名
    protected $table = 't_shipment';
    // タイムスタンプ自動更新オフ
    public $timestamps  = false;

    protected $fillable = [
		'id',				
		'matter_id',							
        'shipment_kind',
        'shipment_kind_id',
		'zipcode',				
		'pref',				
		'address1',				
		'address2',				
		'latitude_jp',				
		'longitude_jp',				
		'latitude_world',				
		'longitude_world',				
		'from_warehouse_id',				
		'delivery_date',				
		'delivery_time',				
		'heavy_flg',				
		'unic_flg',				
		'rain_flg',				
		'transport_flg',				
		'shipment_comment',				
		'loading_finish_flg',				
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
        $where[] = array('t_shipment.del_flg', '=', config('const.flg.off'));
        $where[] = array('t_shipment.loading_finish_flg', '=', config('const.flg.off'));
        if (!empty($params['id'])) {
            $where[] = array('t_shipment.id', '=', $params['id']);
        }
        if (!empty($params['from_date'])) {
            $where[] = array('t_shipment.delivery_date', '>=', $params['from_date']);
        }
        if (!empty($params['to_date'])) {
            $where[] = array('t_shipment.delivery_date', '<=', $params['to_date']);
        }
        if (!empty($params['warehouse_id']) && $params['warehouse_id'] != 0) {
            $where[] = array('m_warehouse.id', '=', $params['warehouse_id']);
        }
        if (array_key_exists('shipment_kind',$params) && !empty($params['shipment_kind'])) {
            $where[] = array('t_shipment.shipment_kind', '=', $params['shipment_kind']);
        }else{
            $where[] = array('t_shipment.shipment_kind', '<>', 4);
        }
        
        // データ取得
        $data = $this
                ->leftjoin('t_matter', 't_matter.id', '=', 't_shipment.matter_id')
                ->leftjoin('m_warehouse', 'm_warehouse.id', '=', 't_shipment.from_warehouse_id')
                ->leftjoin('m_department', 'm_department.base_id', '=', 'm_warehouse.owner_id')
                ->leftjoin('m_staff_department', 'm_staff_department.department_id', '=', 'm_department.id')
                ->leftjoin('m_address', 'm_address.id', '=', 't_matter.address_id')
                ->leftJoin('m_customer', 't_matter.customer_id', '=', 'm_customer.id')
                ->leftJoin('m_general as general', function ($join) {
                    $join->on('m_customer.juridical_code', '=', 'general.value_code')
                        ->where('general.category_code', '=', config('const.general.juridical'));
                })
                ->leftJoin('m_customer_branch', function ($join) {
                    $join->on('t_matter.customer_id', '=', 'm_customer_branch.customer_id')
                        ->where('m_customer_branch.seq_no', '=',1);
                })

                ->where($where)
                ->orderBy('t_shipment.delivery_date', 'asc')
                ->orderBy('t_shipment.delivery_time', 'asc')
                ->orderBy('t_shipment.matter_id', 'asc')
                ->selectRaw(
                        '
                        t_shipment.id,
                        t_shipment.delivery_date,
                        t_shipment.delivery_time,                       
                        t_shipment.matter_id,
                        t_matter.matter_no,
                        t_matter.matter_name,
                        t_shipment.address1,
                        ifnull(t_shipment.address2,\'\') as address2,
                        t_shipment.shipment_comment,
                        t_shipment.shipment_kind,
                        CONCAT(COALESCE(general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(general.value_text_3, \'\')) as customer_name,
                        m_customer_branch.branch_name
                        '                   
                )
                ->distinct()
                ->get()
                ;
        
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

        try{
            $result = $this->insertGetId([
                'matter_id' => $params['matter_id'],							
                'shipment_kind' => $params['shipment_kind'],
                'shipment_kind_id'    => $params['shipment_kind_id'],				
                'zipcode' => $params['zipcode'],				
                'pref' => $params['pref'],				
                'address1' => $params['address1'],				
                'address2' => $params['address2'],				
                'latitude_jp' => $params['latitude_jp'],				
                'longitude_jp' => $params['longitude_jp'],				
                'latitude_world' => $params['latitude_world'],				
                'longitude_world' => $params['longitude_world'],				
                'from_warehouse_id' => $params['from_warehouse_id'],				
                'delivery_date' => $params['delivery_date'],				
                'delivery_time' => $params['delivery_time'],							
                'heavy_flg' => $params['heavy_flg'],				
                'unic_flg' => $params['unic_flg'],				
                'rain_flg' => $params['rain_flg'],				
                'transport_flg' => $params['transport_flg'],				
                'shipment_comment' => $params['shipment_comment'],				
                'loading_finish_flg' => $params['loading_finish_flg'],				
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
     * 
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateById($params) {
        $result = false;
        try {
            // ログテーブルへの書き込み
            $LogUtil = new LogUtil();
            $LogUtil->putById($this->table, $params['id'], config('const.logKbn.update'));

            if(!isset($params['update_user']) || empty($params['update_user'])){
                $params['update_user'] = Auth::user()->id;
            }
            if(!isset($params['update_at']) || empty($params['update_at'])){
                $params['update_at'] = Carbon::now();
            }

            // 更新
            $updateCnt = $this
                    ->where('id', $params['id'])
                    ->update($params);

            $result = ($updateCnt > 0);
        } catch (\Exception $e) {
            throw $e;
        }
        return $result;
    }

    /**
     * 出荷積込完了
     * @param array $params 
     * @return boolean True:成功 False:失敗 
     */
    public function updateLoadingFinish($params) {
        $result = false;
        try {
            $updateCnt = $this
                    ->where('id', $params['shipment_id'])
                    ->update([
                        'loading_finish_flg' => 1,
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
     * 現場名取得
     *
     * @return void
     */
    public function getFieldList() {
        $data = $this
                ->where('del_flg', '=', config('const.flg.off'))
                ->selectRaw('CONCAT(COALESCE(address1, \'\'), COALESCE(address2, \'\')) as address_name')
                ->distinct()
                ->get()
                ;

        return $data;
    }

    /**
     * 物理削除
     * @param $id 出荷指示ヘッダID
     * @return void
     */
    public function deleteByShipmentId($id)
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
     * 物理削除（配列）
     *
     * @param [type] $idList
     * @return void
     */
    public function physicalDeleteByIdList($idList)
    {
        $where = [];
        $where[] = array('del_flg', '=', config('const.flg.off'));

        $query = $this->where($where)->whereIn('id', $idList);

        // 削除対象データ取得
        $deleteData = $query->get();

        $LogUtil = new LogUtil();
        foreach ($deleteData as $value) {
            $LogUtil->putByData($value, config('const.logKbn.delete'));
        }

        // 削除
        $deleteCnt = 0;
        if (count($deleteData) > 0) {
            $deleteCnt = $query->delete();
        }

        $result = ($deleteCnt > 0);

        return $result;
    }

    /**
     * 出荷納品一覧
     * 　出荷指示データ および 直送で納品済みの発注 を取得
     * @param [type] $params
     * @return void
     */
    public function getShipmentDeliveryList($params) {
        $hasDate = false;
        $whereDate = [];
        // Where句作成
        $where = [];
        $where[] = array('t_shipment.del_flg', '=', config('const.flg.off'));
        // $where[] = array('detail.del_flg', '=', config('const.flg.off'));

        // 発注のwhere句作成
        $orderWhere = [];
        $orderWhere[] = array('o.del_flg', '=', config('const.flg.off'));
        $orderWhere[] = array('o.status', '=', config('const.orderStatus.val.ordered'));
        $orderWhere[] = array('o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.site'));
        // $orderWhere[] = array('sum_delivery.sum_delivery_quantity', '>', 0);
        $orderWhere[] = array('od.sum_delivery_quantity', '>', 0);

        // 発注クエリ無効フラグ（true:発注を見ない / false:発注を見る)
        $inValidOrderQuery = false;

        // 出荷指示
        $query = $this
                // ->from('t_shipment_detail as detail')
                // ->leftJoin('t_shipment', 'detail.shipment_id', '=', 't_shipment.id')
                // ->leftJoin('m_product as pdt', 'pdt.id', '=', 'detail.product_id')
                // ->leftJoin('m_product_nickname as nickname', 'pdt.id', '=', 'nickname.product_id')
                // ->leftJoin('m_supplier as maker', 'maker.id', '=', 'pdt.maker_id')
                ->leftJoin('t_matter as m', 'm.id', '=', 't_shipment.matter_id')
                ->leftJoin('m_warehouse as w', 'w.id', '=', 't_shipment.from_warehouse_id')
                ->leftJoin('m_customer as cus', 'm.customer_id', '=', 'cus.id')
                ->leftJoin('m_staff as st', 'st.id', '=', 'm.staff_id')
                ->leftJoin('m_department as dep', 'dep.id', '=', 'm.department_id')
                // ->leftJoin('t_order_detail as odr_detail', 'odr_detail.id', '=', 'detail.order_detail_id')
                // ->leftJoin('t_order as odr', 'odr.order_no', '=', 'odr_detail.order_no')
                ->leftJoin('m_general', function($join) {
                    $join->on('m_general.value_code', '=', 'cus.juridical_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ;

        // 発注（現場直送分）
        $orderQuery = $this
            ->from('t_order as o')
            // ->leftJoin('t_order_detail as od', 'o.id', '=', 'od.order_id')
            // ->leftJoin('m_product as pdt', 'pdt.id', '=', 'od.product_id')
            // ->leftJoin('m_product_nickname as nickname', 'pdt.id', '=', 'nickname.product_id')
            ->leftJoin('m_supplier as maker', 'maker.id', '=', 'o.maker_id')
            ->leftJoin('t_matter as m', 'm.matter_no', '=', 'o.matter_no')
            ->leftJoin('m_address as matter_address', 'matter_address.id', '=', 'm.address_id')
            // ->leftJoin('m_warehouse as w', 'w.id', '=', 'o.delivery_address_id')
            ->leftJoin('m_customer as cus', 'm.customer_id', '=', 'cus.id')
            ->leftJoin('m_staff as st', 'st.id', '=', 'm.staff_id')
            ->leftJoin('m_department as dep', 'dep.id', '=', 'm.department_id')
            ->leftJoin('m_general', function($join) {
                $join->on('m_general.value_code', '=', 'cus.juridical_code')
                    ->where('m_general.category_code', '=', config('const.general.juridical'))
                    ;
            })
            ;

        if (!empty($params['matter_no_id'])) {
            $where[] = array('m.id', '=', $params['matter_no_id']);
            $orderWhere[] = array('m.id', '=', $params['matter_no_id']);
        } else {
            if (!empty($params['matter_no'])) {
                $where[] = array('m.matter_no', 'LIKE', '%'.$params['matter_no'].'%');
                $orderWhere[] = array('m.matter_no', 'LIKE', '%'.$params['matter_no'].'%');
            }
        }
        
        if (!empty($params['matter_name_id'])) {
            $where[] = array('m.id', '=', $params['matter_name_id']);
            $orderWhere[] = array('m.id', '=', $params['matter_name_id']);
        } else {
            if (!empty($params['matter_name'])) {
                $where[] = array('m.matter_name', 'LIKE', '%'.$params['matter_name'].'%');
                $orderWhere[] = array('m.matter_name', 'LIKE', '%'.$params['matter_name'].'%');
            }
        }

        if (!empty($params['department_id'])) {
            $where[] = array('dep.id', '=', $params['department_id']);
            $orderWhere[] = array('dep.id', '=', $params['department_id']);
        } else {
            if (!empty($params['department_name'])) {
                $where[] = array('dep.department_name', 'LIKE', '%'.$params['department_name'].'%');
                $orderWhere[] = array('dep.department_name', 'LIKE', '%'.$params['department_name'].'%');
            }
        }

        // if (!empty($params['order_no'])) {
        //     $where[] = array('odr.order_no', 'LIKE', '%'.$params['order_no'].'%');
        //     $orderWhere[] = array('o.order_no', 'LIKE', '%'.$params['order_no'].'%');
        // }
        // if (!empty($params['supplier_name'])) {
        //     $where[] = array('maker.supplier_name', 'LIKE', '%'.$params['supplier_name'].'%');
        //     $orderWhere[] = array('maker.supplier_name', 'LIKE', '%'.$params['supplier_name'].'%');
        // }
        
        if (!empty($params['sales_staff_id'])) {
            $where[] = array('st.id', '=', $params['sales_staff_id']);
            $orderWhere[] = array('st.id', '=', $params['sales_staff_id']);
        } else {
            if (!empty($params['sales_staff'])) {
                $where[] = array('st.staff_name', 'LIKE', '%'.$params['sales_staff'].'%');
                $orderWhere[] = array('st.staff_name', 'LIKE', '%'.$params['sales_staff'].'%');
            }
        }

        if (!empty($params['issue_from_date'])) {
            $hasDate = true;
            $whereDate[] = array('t_shipment.delivery_date', '>=', $params['issue_from_date']. ' 00:00:00');
            $inValidOrderQuery = true;
        }
        if (!empty($params['issue_to_date'])) {
            $hasDate = true;
            $whereDate[] = array('t_shipment.delivery_date', '<=', $params['issue_to_date']. ' 23:59:59');
            $inValidOrderQuery = true;
        }

        if (!empty($params['from_warehouse_id'])) {
            $where[] = array('w.id', '=', $params['from_warehouse_id']);
            $inValidOrderQuery = true;
        } else {
            if (!empty($params['from_warehouse'])) {
                $where[] = array('w.warehouse_name', 'LIKE', '%'.$params['from_warehouse'].'%');
                $inValidOrderQuery = true;
            }
        }

        if (!empty($params['shipping_staff'])){
            // $query = $query
            //         ->leftJoin('t_loading as load', 'load.shipment_detail_id', '=', 'detail.id')
            //         ->leftJoin('m_staff as shipStaff', 'load.created_user', '=', 'shipStaff.id')
            //         ->where('shipStaff.staff_name', 'LIKE', '%'.$params['shipping_staff'].'%')
            //         ;
            
            $inValidOrderQuery = true;
        }
        // if ($params['notLoadingFlg']) {
        //     // 未発送
        //     $query = $query->where(function ($q) use($params){
        //         $q->orWhere('detail.loading_finish_flg', '=', config('const.flg.off'))
        //             ;
        //         if ($params['endLoadingFlg']) {
        //             // 配送中
        //             $q->orWhere('detail.loading_finish_flg', '=', config('const.flg.on'))
        //                 ;
        //         }
        //         if ($params['deliveryFinishFlg']) {
        //             // 納品済
        //             $q->orWhere('detail.delivery_finish_flg', '=', config('const.flg.on'))
        //                 ;
        //         }
        //     });
        //     $inValidOrderQuery = true;
        // } else if($params['endLoadingFlg']){
        //      // 配送中
        //      $query = $query->where(function ($q) use($params){
        //         $q->Where('detail.loading_finish_flg', '=', config('const.flg.on'))
        //             ;
        //             if ($params['deliveryFinishFlg']) {
        //                 // 納品済
        //                 $q->orWhere('detail.delivery_finish_flg', '=', config('const.flg.on'))
        //                     ;
        //             }
        //     });
        //     $inValidOrderQuery = true;
        // } else if ($params['deliveryFinishFlg']) {
        //     // 納品済
        //     $query->Where('detail.delivery_finish_flg', '=', config('const.flg.on'))
        //     ;
        // }

        // 出荷登録合計
        $shipmentSumQuery = $this
                        ->from('t_shipment_detail')
                        ->selectRaw('
                            t_shipment_detail.shipment_id,
                            SUM(t_shipment_detail.shipment_quantity) AS sum_shipment_quantity
                        ')
                        ->groupBy('t_shipment_detail.shipment_id')
                        ->toSql()
                        ;

        $loadingSumQuery = $this
                        ->from('t_loading')
                        ->selectRaw('
                            t_loading.shipment_id,
                            SUM(t_loading.loading_quantity) AS sum_loading_quantity
                        ')
                        ->groupBy('t_loading.shipment_id')
                        ->toSql()
                        ;

        $deliverySumQuery = $this
                        ->from('t_delivery')
                        ->selectRaw('
                            t_delivery.shipment_id,
                            SUM(t_delivery.delivery_quantity) AS sum_delivery_quantity
                        ')
                        ->groupBy('t_delivery.shipment_id')
                        ->toSql()
                        ;
        
        // $orderDeliverySumQuery = $this
        //                 ->from('t_delivery')
        //                 ->selectRaw('
        //                     t_delivery.order_detail_id,
        //                     SUM(t_delivery.delivery_quantity) AS sum_delivery_quantity
        //                 ')
        //                 ->groupBy('t_delivery.order_detail_id')
        //                 ->toSql()
        //                 ;
        
        // 検索条件に 発注番号・仕入先／メーカー名・商品名・出荷担当者名 のいずれかの指定がある場合
        if (!empty($params['order_no']) || !empty($params['supplier_name']) || !empty($params['product_name']) || !empty($params['shipping_staff'])
            || Common::isFlgOn($params['notLoadingFlg']) || Common::isFlgOn($params['endLoadingFlg']) || Common::isFlgOn($params['deliveryFinishFlg'])) {

            $detailQuery = DB::table('t_shipment_detail as detail')
                            ->where('detail.del_flg', '=', config('const.flg.off'))
                            ->select('detail.shipment_id')
                            ->distinct()
                            ;

            if (!Common::isFlgOn($params['notLoadingFlg']) && !Common::isFlgOn($params['endLoadingFlg']) && !Common::isFlgOn($params['deliveryFinishFlg'])) {
                // 全てにチェックがない
            } else if (Common::isFlgOn($params['notLoadingFlg']) && Common::isFlgOn($params['endLoadingFlg']) && Common::isFlgOn($params['deliveryFinishFlg'])) {
                // 全てにチェックが付いている
            } else if (Common::isFlgOn($params['notLoadingFlg']) && !Common::isFlgOn($params['endLoadingFlg'])) {
                // 未発送 かつ 納品済
                if (Common::isFlgOn($params['deliveryFinishFlg'])) {
                    $detailQuery->where(function($q) {
                        $q
                            ->where('detail.loading_finish_flg', '=', config('const.flg.off'))
                            ->orWhere('detail.delivery_finish_flg', '=', config('const.flg.on'))
                            ;
                    });
                } 
                // 未発送のみ
                else {
                    $detailQuery
                        ->where('detail.loading_finish_flg', '=', config('const.flg.off'))
                        ->where('detail.delivery_finish_flg', '=', config('const.flg.off'))
                        ;
                }
            } else if (!Common::isFlgOn($params['notLoadingFlg']) && Common::isFlgOn($params['endLoadingFlg'])) {
                // 配送中 かつ 納品済
                if (Common::isFlgOn($params['deliveryFinishFlg'])) {
                    // $detailQuery->where(function($q) {
                    //     $q
                    //         ->where('detail.loading_finish_flg', '=', config('const.flg.on'))
                    //         ->orWhere('detail.delivery_finish_flg', '=', config('const.flg.on'))
                    //         ;
                    // });
                    $detailQuery
                        ->where('detail.loading_finish_flg', '=', config('const.flg.on'))
                        ;
                } 
                // 配送中のみ
                else {
                    $detailQuery
                        ->where('detail.loading_finish_flg', '=', config('const.flg.on'))
                        ->where('detail.delivery_finish_flg', '=', config('const.flg.off'))
                        ;
                }
            }
            else {
                // 納品済のみ
                if (Common::isFlgOn($params['deliveryFinishFlg'])) {
                    $detailQuery
                        ->where('detail.delivery_finish_flg', '=', config('const.flg.on'))
                        ;
                } 
                // 未発送 かつ 配送中
                else {
                    $detailQuery
                        ->where('detail.delivery_finish_flg', '=', config('const.flg.off'))
                        ;
                }
            }
            
            if (!empty($params['order_id'])) {
                $detailQuery
                    ->leftJoin('t_order_detail as odr_detail', 'odr_detail.id', '=', 'detail.order_detail_id')
                    ->where('odr_detail.order_id', '=', $params['order_id'])
                    ;
            } else {
                if (!empty($params['order_no'])) {
                    $detailQuery
                        ->leftJoin('t_order_detail as odr_detail', 'odr_detail.id', '=', 'detail.order_detail_id')
                        // ->leftJoin('t_order as odr', 'odr.order_no', '=', 'odr_detail.order_no')
                        ->where('odr_detail.order_no', 'LIKE', '%'.$params['order_no'].'%')
                        ;
                }
            }
            if (!empty($params['supplier_name']) || !empty($params['product_name'])) {
                $detailQuery
                    ->leftJoin('m_product as pdt', 'pdt.id', '=', 'detail.product_id')
                    ;
                if (!empty($params['product_name'])) {
                    $detailQuery
                        ->leftJoin('m_product_nickname as nickname', 'pdt.id', '=', 'nickname.product_id')
                        ->where(function($q) use ($params) {
                            $q
                                ->where('pdt.product_name', 'LIKE', '%'.$params['product_name'].'%')
                                ->orWhere('nickname.another_name', 'LIKE', '%'.$params['product_name'].'%')
                                ;
                        });
                }

                if (!empty($params['supplier_id'])) {
                    $detailQuery
                        ->leftJoin('m_supplier as maker', 'maker.id', '=', 'pdt.maker_id')
                        ->where('maker.id', '=', $params['supplier_id'])
                        ;
                } else {
                    if (!empty($params['supplier_name'])) {
                        $detailQuery
                            ->leftJoin('m_supplier as maker', 'maker.id', '=', 'pdt.maker_id')
                            ->where('maker.supplier_name', 'LIKE', '%'.$params['supplier_name'].'%')
                            ;
                    }
                }
            }

            if (!empty($params['shipping_staff_id'])) {
                $detailQuery
                    ->leftJoin('t_loading as load', 'load.shipment_detail_id', '=', 'detail.id')
                    ->leftJoin('m_staff as shipStaff', 'load.created_user', '=', 'shipStaff.id')
                    ->where('shipStaff.id', '=', $params['shipping_staff_id'])
                    ;
            } else {
                if (!empty($params['shipping_staff'])) {
                    $detailQuery
                        ->leftJoin('t_loading as load', 'load.shipment_detail_id', '=', 'detail.id')
                        ->leftJoin('m_staff as shipStaff', 'load.created_user', '=', 'shipStaff.id')
                        ->where('shipStaff.staff_name', 'LIKE', '%'.$params['shipping_staff'].'%')
                        ;
                }
            }

            $query
                ->join(DB::raw('('.$detailQuery->toSql().') as detail2'), function($join){
                    $join->on('t_shipment.id', 'detail2.shipment_id');
                })
                ->mergeBindings($detailQuery)
                ;
        }
        
        // 出荷指示データ取得
        $data = $query
                ->select('t_shipment.id as id',
                         't_shipment.delivery_time as delivery_time',         
                         't_shipment.heavy_flg',
                         't_shipment.unic_flg',
                         't_shipment.rain_flg',
                         't_shipment.transport_flg',
                         'sum_shipment.sum_shipment_quantity',
                         'sum_loading.sum_loading_quantity',
                         'sum_delivery.sum_delivery_quantity',
                         'm.id as matter_id',
                         'm.matter_no as matter_no',
                         'm.matter_name as matter_name',
                         'w.id as from_warehouse_id',
                         'w.warehouse_name as warehouse_name',
                        //  'pdt.product_name',
                        DB::raw("'' as product_name"), 
                        //  'odr.order_no',
                        DB::raw("'' as order_no"), 
                         'cus.customer_name as customer_name'
                         )
                ->leftjoin(DB::raw('('.$shipmentSumQuery.') as sum_shipment'), function($join){
                    $join->on('sum_shipment.shipment_id', 't_shipment.id');
                })
                ->leftjoin(DB::raw('('.$loadingSumQuery.') as sum_loading'), function($join){
                    $join->on('sum_loading.shipment_id', 't_shipment.id');
                })
                ->leftjoin(DB::raw('('.$deliverySumQuery.') as sum_delivery'), function($join){
                    $join->on('sum_delivery.shipment_id', 't_shipment.id');
                })
                ->where($where)
                ->when(!empty($params['customer_name']), function ($query) use ($params) {
                    if (!empty($params['customer_id'])) {
                        $query
                            ->where('cus.id', $params['customer_id']);
                    } else {
                        $query
                            ->whereRaw(
                                'CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) LIKE ?',
                                ['%' . $params['customer_name'] . '%']
                            );
                    }
                    return $query;
                })
                ->when(!empty($params['field_name']), function($query) use($params) {
                    return $query
                            ->whereRaw(
                                'CONCAT(COALESCE(t_shipment.address1, \'\'), COALESCE(t_shipment.address2, \'\')) LIKE ?',
                                ['%'. $params['field_name']. '%']
                            );
                })
                ->when($hasDate, function($query) use($whereDate) {
                    return $query->where($whereDate);
                })
                // ->when(!empty($params['product_name']), function($query) use($params) {
                //     $query->where('pdt.product_name', 'LIKE', '%'.$params['product_name'].'%')
                //           ->orWhere('nickname.another_name', 'LIKE', '%'.$params['product_name'].'%')
                //           ;
                // })
                ->selectRaw('
                             CONCAT(COALESCE(t_shipment.address1, \'\'), COALESCE(t_shipment.address2, \'\')) as address,
                             CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                             DATE_FORMAT(t_shipment.delivery_date, "%Y/%m/%d") as delivery_date
                            ')
                // ->distinct()
                ->orderBy('t_shipment.id', 'desc')
                ->get()
                ;


        // 現場名
        if(!empty($params['field_name'])){
            $inValidOrderQuery = true;
        }
        // 納品済にチェックなしで、未発送または配送中にチェックがある場合、直送を検索する必要がない
        if (!Common::isFlgOn($params['deliveryFinishFlg']) && (Common::isFlgOn($params['notLoadingFlg']) || Common::isFlgOn($params['endLoadingFlg']))) {
            $inValidOrderQuery = true;
        }
        
        // 現場直送の発注データ取得　MEMO：現場直送に出荷元倉庫は存在しない
        $data2 = null;
        if($inValidOrderQuery === false){
            $orderDetailWhere = [];
            $orderDetailWhere[] = array('od_sub.del_flg', '=', config('const.flg.off'));
            $orderDetailWhere[] = array('od_sub.arrival_quantity', '>', 0);   // 現場直送は納品完了しているものだけを出力する＝入荷済み

            if (!empty($params['order_id'])) {
                $orderWhere[] = array('o.id', '=', $params['order_id']);
                $orderDetailWhere[] = array('od_sub.order_id', '=', $params['order_id']);
            } else {
                if (!empty($params['order_no'])) {
                    $orderWhere[] = array('o.order_no', 'LIKE', '%'.$params['order_no'].'%');
                    $orderDetailWhere[] = array('od_sub.order_no', 'LIKE', '%'.$params['order_no'].'%');
                }
            }
            
            if (!empty($params['supplier_id'])) {
                $orderWhere[] = array('maker.id', '=', $params['supplier_id']);
                $orderDetailWhere[] = array('od_sub.maker_id', '=', $params['supplier_id']);
            } else {
                if (!empty($params['supplier_name'])) {
                    $orderWhere[] = array('maker.supplier_name', 'LIKE', '%'.$params['supplier_name'].'%');
                    $orderDetailWhere[] = array('od_sub.maker_name', 'LIKE', '%'.$params['supplier_name'].'%');
                }
            }

            $orderDetailQuery = DB::table('t_order_detail as od_sub')
                ->leftJoin('t_delivery as deli', 'od_sub.id', '=', 'deli.order_detail_id')
                ->where($orderDetailWhere)
                ->selectRaw('
                    od_sub.order_id,
                    od_sub.order_no,
                    SUM(COALESCE(od_sub.stock_quantity,0)) AS stock_quantity,
                    MIN(od_sub.arrival_plan_date) AS arrival_plan_date,
                    SUM(COALESCE(deli.delivery_quantity,0)) AS sum_delivery_quantity
                ')
                ->groupBy('od_sub.order_id', 'od_sub.order_no')
                ;

            if (!empty($params['product_name'])) {
                $orderDetailQuery
                    ->leftJoin('m_product as pdt', 'pdt.id', '=', 'od_sub.product_id')
                    ->leftJoin('m_product_nickname as nickname', 'pdt.id', '=', 'nickname.product_id')
                    ->where(function($q) use ($params) {
                        $q
                            ->where('pdt.product_name', 'LIKE', '%'.$params['product_name'].'%')
                            ->orWhere('nickname.another_name', 'LIKE', '%'.$params['product_name'].'%')
                            ;
                    })
                    ;
            }
            
            $orderQuery
                ->join(DB::raw('('.$orderDetailQuery->toSql().') as od'), function($join){
                    $join->on('o.id', 'od.order_id');
                })
                ->mergeBindings($orderDetailQuery)
                ;

            $data2 = $orderQuery
                ->select('o.id as id',
                        DB::raw('0 as delivery_time'),
                        DB::raw(config('const.flg.off').' as heavy_flg'),   
                        DB::raw(config('const.flg.off').' as unic_flg'),   
                        DB::raw(config('const.flg.off').' as rain_flg'),           
                        DB::raw(config('const.flg.off').' as transport_flg'), 
                        'od.stock_quantity as sum_shipment_quantity',
                        // 'sum_delivery.sum_delivery_quantity as sum_loading_quantity',
                        'od.sum_delivery_quantity as sum_loading_quantity',
                        // 'sum_delivery.sum_delivery_quantity',
                        'od.sum_delivery_quantity',
                        'm.id as matter_id',
                        'm.matter_no as matter_no',
                        'm.matter_name as matter_name',
                        // 'w.id as from_warehouse_id'
                        DB::raw("0 as from_warehouse_id"), 
                        //'w.warehouse_name as warehouse_name',
                        DB::raw("'' as warehouse_name"), 
                        // 'od.product_name',
                        DB::raw("'' as product_name"), 
                        'od.order_no',
                        'cus.customer_name as customer_name'
                        )
                // ->leftjoin(DB::raw('('.$orderDeliverySumQuery.') as sum_delivery'), function($join){
                //     $join->on('sum_delivery.order_detail_id', 'od.id');
                // })
                ->where($orderWhere)
                ->when(!empty($params['customer_name']), function ($query) use ($params) {
                    if (!empty($params['customer_id'])) {
                        $query
                            ->where('cus.id', $params['customer_id']);
                    } else {
                        $query
                            ->whereRaw(
                                'CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) LIKE ?',
                                ['%' . $params['customer_name'] . '%']
                            );
                    }
                    return $query;
                })
                // ->where(function($query){
                //     $query->where('pdt.intangible_flg', config('const.flg.on'))
                //           ->orWhere('od.product_id', 0);
                // })
                // ->when(!empty($params['product_name']), function($query) use($params) {
                //     $query->where('od.product_name', 'LIKE', '%'.$params['product_name'].'%')
                //         ->orWhere('nickname.another_name', 'LIKE', '%'.$params['product_name'].'%')
                //         ;
                // })
                ->selectRaw(
                            // '
                            // CASE
                            //     WHEN o.delivery_address_kbn = '.config('const.deliveryAddressKbn.val.site').'
                            //         THEN CONCAT(COALESCE(matter_address.address1, \'\'), COALESCE(matter_address.address2, \'\'))
                            //     ELSE CONCAT(COALESCE(w.address1, \'\'), COALESCE(w.address2, \'\'))
                            // END AS address,
                            '
                            CONCAT(COALESCE(matter_address.address1, \'\'), COALESCE(matter_address.address2, \'\')) AS address,
                            CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(cus.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name,
                            DATE_FORMAT(od.arrival_plan_date, "%Y/%m/%d") as delivery_date
                            ')
                // ->distinct()
                // ->orderBy('sum_delivery.sum_delivery_quantity', 'desc')
                ->orderBy('od.arrival_plan_date', 'desc')
                ->get()
                ;
        }

        // 戻り値
        $result = [
            'shipment' => $data,
            'order' => $data2,
        ];
        
        return $result;
    }

    /**
     * 出荷指示と紐付いた明細を１件取得
     *
     * @param [type] $shipmentID
     * @return void
     */
    public function getFirstDetailByID($shipmentID) {
        $where = [];
        $where[] = array('shipment.del_flg', '=', config('const.flg.off'));
        $where[] = array('shipment_detail.shipment_id', '=', $shipmentID);

        $joinWhere[] = array('shipment_detail.del_flg', '=', config('const.flg.off'));

        $data = $this
                ->from('t_shipment as shipment')
                ->leftJoin('t_shipment_detail as shipment_detail', function($join) use($joinWhere){
                    $join->on('shipment.id', '=', 'shipment_detail.shipment_id')
                         ->where($joinWhere)
                         ;
                })
                ->leftJoin('m_product as product', 'shipment_detail.product_id', '=', 'product.id')
                ->where($where)
                ->select('product.product_name')
                ->first()
                ;

        return $data;
    }

    /**
     * 出荷指示IDから明細数を取得
     * 
     * @param $shipmentID 出荷指示ID
     * @return void
     */
    public function getCountDetailByID($shipmentID)
    {
        // Where句作成
        $where = [];
        $where[] = array('shipment.del_flg', '=', config('const.flg.off'));
        $where[] = array('shipment_detail.del_flg', '=', config('const.flg.off'));

        $data = $this
                    ->from('t_shipment as shipment')
                    ->leftJoin('t_shipment_detail as shipment_detail', 'shipment.id', '=', 'shipment_detail.shipment_id')
                    ->where('shipment.id', '=', $shipmentID)
                    ->where($where)
                    ->selectRaw('
                            count(*) as count
                            ')
                    ->groupBy('shipment.id')
                    ->first()
                    ;

        return $data;
    }

     /**
     * 出荷指示IDから未完了数を取得
     * 
     * @param $shipmentID 出荷指示ID
     * @return void
     */
    public function getCountNotFinishDetail($shipmentID)
    {
        // Where句作成
        $where = [];
        $where[] = array('shipment.del_flg', '=', config('const.flg.off'));
        $where[] = array('shipment_detail.del_flg', '=', config('const.flg.off'));
        $where[] = array('shipment_detail.loading_finish_flg', '=', config('const.flg.off'));

        $data = $this
                    ->from('t_shipment as shipment')
                    ->leftJoin('t_shipment_detail as shipment_detail', 'shipment.id', '=', 'shipment_detail.shipment_id')
                    ->where('shipment.id', '=', $shipmentID)
                    ->where($where)
                    ->selectRaw('
                            count(*) as count
                            ')
                    ->groupBy('shipment.id')
                    ->first()
                    ;

        return $data;
    }

    /**
     * IDで取得
     * 
     * @param int $id ID
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
     * IDで関連データを含めて取得
     * 
     * @param int $id ID
     * @return type 検索結果データ（1件）
     */
    public function getAllDataById($id) {
        $data = $this
                ->select([
                    't_shipment.*',
                    'm_warehouse.warehouse_name',
                    't_matter.matter_no',
                    't_matter.matter_name',
                    't_matter.customer_id',
                    't_matter.address_id',
                    //'m_customer.customer_name',
                    'm_address.address1 as matter_address1',
                    'm_address.address2 as matter_address2',
                ])
                ->leftjoin('m_warehouse', 't_shipment.from_warehouse_id', 'm_warehouse.id')
                ->leftjoin('t_matter', 't_shipment.matter_id', 't_matter.id')
                ->leftjoin('m_customer', 't_matter.customer_id', 'm_customer.id')
                ->leftJoin('m_general', function($join) {
                    $join->on('m_customer.juridical_code', '=', 'm_general.value_code')
                         ->where('m_general.category_code', '=', config('const.general.juridical'))
                         ;
                })
                ->leftjoin('m_address', 't_matter.address_id', 'm_address.id')
                ->where(['t_shipment.id' => $id])
                ->where(['t_shipment.del_flg' => config('const.flg.off')])
                ->selectRaw(
                    'CONCAT(COALESCE(m_general.value_text_2, \'\'), COALESCE(m_customer.customer_name, \'\'), COALESCE(m_general.value_text_3, \'\')) as customer_name'
                )
                ->first()
                ;

        return $data;
    }

    /**
     * 出荷指示画面の編集データ取得
     * 
     * @param $matterId 出荷指示ID
     * @param $matterId 案件ID
     * @return 出荷指示画面の編集時に使用するデータ
     */
    public function getShipmentEditDataList($shipmentId, $matterId){
        $where = [];
        $where[] = array('t_reserve.del_flg', '=', config('const.flg.off'));


        // 編集対象の出荷指示明細の見積もり明細IDと倉庫IDを取得
        $tmpShipmentQuery = $this
                ->join('t_shipment_detail', 't_shipment.id', 't_shipment_detail.shipment_id')
                ->select([
                    't_shipment_detail.quote_detail_id',
                    't_shipment.from_warehouse_id',
                ])
                ->distinct()
                ->whereraw('t_shipment.del_flg = '.config('const.flg.off'))
                ->whereraw('t_shipment_detail.del_flg = '.config('const.flg.off'))
                ->whereraw('t_shipment.id = '.$shipmentId)
                ->toSql()
                ;

        // 過去の引当ごとの出荷指示数取得
        $sumShipmentQuantity  = DB::table('t_reserve')
                ->join('t_shipment_detail', function ($join) {
                    $join->on('t_reserve.id', 't_shipment_detail.reserve_id');
                    $join->on('t_reserve.product_id', 't_shipment_detail.product_id');
                })
                ->select([
                    't_reserve.id as reserve_id',
                ])
                ->selectRaw(
                    'SUM(t_shipment_detail.shipment_quantity - t_reserve.issue_quantity) as sum_shipment_quantity'
                )
                ->whereRaw('t_reserve.matter_id = '.$matterId)
                ->whereRaw('t_shipment_detail.del_flg = "'. config('const.flg.off') .'"')
                ->groupBy('t_reserve.id')
                ->toSql()
                ;

        
        // 引当ごとの納品数取得
        $sumDeliveryQuantity = DB::table('t_reserve')
                ->join('t_shipment_detail', function ($join) {
                    $join->on('t_reserve.id', 't_shipment_detail.reserve_id');
                    $join->on('t_reserve.product_id', 't_shipment_detail.product_id');
                })
                ->join('t_delivery', 't_shipment_detail.id', 't_delivery.shipment_detail_id')
                ->select([
                    't_reserve.id as reserve_id',
                ])
                ->selectRaw(
                    'SUM(t_delivery.delivery_quantity) as sum_delivery_quantity'
                )
                ->whereRaw('t_reserve.matter_id = '.$matterId)
                ->whereRaw('t_shipment_detail.del_flg = '. config('const.flg.off'))
                ->whereRaw('t_delivery.del_flg = '. config('const.flg.off'))
                ->groupBy('t_reserve.id')
                ->toSql()
                ;


        // データ取得
        $data = $this
                ->from('t_reserve')
                ->join(DB::raw('('.$tmpShipmentQuery.') as shipment_query'), function($join){
                    // 編集対象の出荷指示と同じ見積もり明細ID,倉庫IDを持つ引当を取得対象とする
                    $join->on('t_reserve.quote_detail_id', 'shipment_query.quote_detail_id');
                    $join->on('t_reserve.from_warehouse_id', 'shipment_query.from_warehouse_id');
                })
                ->leftJoin('t_shipment_detail', function ($join) use ($shipmentId) {
                    $join->on('t_reserve.id', 't_shipment_detail.reserve_id');
                    $join->where('t_shipment_detail.shipment_id', '=', $shipmentId);
                    $join->where('t_shipment_detail.del_flg', '=', config('const.flg.off'));
                })
                ->leftjoin('t_shipment', 't_shipment_detail.shipment_id', 't_shipment.id')

                ->leftJoin('t_order_detail', function ($join) {
                    $join->on('t_reserve.order_detail_id', 't_order_detail.id');
                    $join->on('t_reserve.product_id', 't_order_detail.product_id');
                    $join->where('t_order_detail.del_flg', '=', config('const.flg.off'));
                })
                ->leftjoin('t_quote', 't_reserve.quote_id', 't_quote.id')
                ->leftjoin('t_quote_detail', 't_reserve.quote_detail_id', 't_quote_detail.id')
                ->leftjoin(DB::raw('('.$sumShipmentQuantity.') as sum_shipment_detail'), function($join){
                    $join->on('t_reserve.id', 'sum_shipment_detail.reserve_id');
                })
                ->leftjoin(DB::raw('('.$sumDeliveryQuantity.') as t_delivery'), function($join){
                    $join->on('t_reserve.id', 't_delivery.reserve_id');
                })
                ->leftJoin('t_matter_detail', function($join) {
                    $join
                        ->on('t_matter_detail.quote_detail_id', '=', 't_reserve.quote_detail_id')
                        ->where('t_matter_detail.type', '=', config('const.matterTaskType.val.hope_arrival_plan_date'))
                        ;
                })
                ->where($where)
                ->select([
                    't_shipment.id as shipment_id',
                    't_shipment_detail.id as shipment_detail_id',
                    't_shipment_detail.shipment_quantity',
                    //'t_shipment_detail.loading_finish_flg', // 不要
                    //'t_shipment_detail.delivery_finish_flg', // 不要

                    't_reserve.id as reserve_id',
                    't_reserve.quote_id',
                    't_reserve.quote_detail_id',
                    't_reserve.product_id',
                    't_reserve.product_code',
                    't_reserve.stock_flg',
                    't_reserve.order_detail_id',
                    't_reserve.reserve_quantity',
                    't_reserve.reserve_quantity_validity',
                    't_reserve.from_warehouse_id',

                    'sum_shipment_detail.sum_shipment_quantity',

                    't_delivery.sum_delivery_quantity',

                    't_order_detail.id as order_detail_id',
                    't_order_detail.stock_quantity as order_stock_quantity',
                    't_order_detail.arrival_quantity',

                    't_quote.id as quote_id',
                    't_quote_detail.id as quote_detail_id',
                    't_quote_detail.quote_no',
                    't_quote_detail.quote_version',
                    't_quote_detail.construction_id',
                    't_quote_detail.layer_flg',
                    't_quote_detail.parent_quote_detail_id',
                    't_quote_detail.seq_no',
                    't_quote_detail.depth',
                    't_quote_detail.tree_path',
                    't_quote_detail.sales_use_flg',
                    't_quote_detail.product_name',
                    't_quote_detail.model',
                    't_quote_detail.stock_unit as quote_unit',
                    't_quote_detail.stock_unit',
                    't_quote_detail.stock_quantity',
                ])
                ->selectRaw(
                    'DATE_FORMAT(t_order_detail.arrival_plan_date, "%Y/%m/%d") as arrival_plan_date,
                    DATE_FORMAT(t_order_detail.hope_arrival_plan_date, "%Y/%m/%d") as hope_arrival_plan_date,
                    DATE_FORMAT(t_matter_detail.start_date, "%Y/%m/%d") as start_date'
                )
                ->orderBy('t_quote_detail.depth', 'asc')
                ->orderBy('t_quote_detail.seq_no', 'asc')
                ->get()
                ;

        return $data;
    }

    /**
     * 案件IDで取得
     *
     * @param [type] $matterId
     * @return void
     */
    public function getByMatterId($matterId, $params=[])
    {
        $where = [];
        $where[] = ['del_flg', '=', config('const.flg.off')];
        $where[] = ['matter_id', '=', $matterId];

        // 条件(必要な条件は適宜追加)
        
        // 出荷積込完了フラグ
        if (isset($params['loading_finish_flg'])) {
            $where[] = ['loading_finish_flg', '=', $params['loading_finish_flg']];
        }

        $data = $this
                ->where($where)
                ->get()
                ;
        return $data;
    }  
}
