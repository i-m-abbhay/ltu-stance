<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Matter;
use App\Models\ShipmentDetail;
use App\Models\Loading;
use App\Models\QrDetail;
use App\Models\ProductMove;
use App\Models\Delivery;
use App\Models\WarehouseMove;
use App\Models\ProductstockShelf;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use App\Libs\SystemUtil;
use Auth;
use Carbon\Carbon;

/**
 * 納品登録
 */
class DeliveryProcessController extends Controller
{
    const SCREEN_NAME = 'delivery-process';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 初期表示
     * @return type
     */
    public function index()
    {
        // TODO: 権限チェック

        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Delivery.delivery-process')
            ->with('isEditable', $isEditable);
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();

            // 一覧データ取得
            $Loading = new Loading();
            $list = $Loading->getListDetail($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $reqData = $request->request->all();
        $tableData = $reqData['tableData'];
        $scanData = $reqData['scanData'];

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();

        $Loading = new Loading();
        $Delivery = new Delivery();
        $WarehouseMove = new WarehouseMove();
        $ShipmentDetail = new ShipmentDetail();
        $ProductstockShelf = new ProductstockShelf();

        //納品番号採番
        // 採番ルール
        // 頭:NH(納品の略)
        // 西暦:20(下2桁)
        // 月日:0717(7月17日)
        // 順番:001(当日出荷数の順番)←その日の納品作業の回数的な
        // 例:NH200717001

        $DeliveryNo = "";
        $LastDeliveryNo = $Delivery->getDeliveryNo();
        if ($LastDeliveryNo != null) {
            $seqNo = (int)substr($LastDeliveryNo['delivery_no'], -3);
            $seqNo += 1;
            $DeliveryNo = substr($LastDeliveryNo['delivery_no'], 0, 8) . sprintf('%03d', $seqNo);
        } else {
            $DeliveryNo = 'NH' . substr(Carbon::now()->format('Y'), -2) .  Carbon::now()->format('md') . '001';
        }
        try {
            $saveResult = false;
            foreach ($tableData as $params) {
                // 't_matter.matter_name',
                // 'm_product.product_name',
                // 'm_product.model',
                // 't_loading.id as loading_id',
                // '0 as delivery_quantity',
                // 't_qr.qr_code',
                // 't_qr_detail.quantity as qr_quantity'
                // matter_id				
                // shipment_id				
                // shipment_detail_id				
                // order_detail_id				
                // quote_id				
                // quote_detail_id				
                // shipment_kind				
                // zipcode				
                // pref				
                // address1				
                // address2				
                // latitude_jp				
                // longitude_jp				
                // latitude_world				
                // longitude_world				
                // product_id				
                // product_code				
                // from_warehouse_kbn				
                // from_warehouse_id				
                // to_warehouse_id				
                // to_shelf_number_id				
                // loading_date				
                // loading_quantity				
                // delivery_finish_flg	
                $params['delivery_no'] = $DeliveryNo;
                if ($params['delivery_quantity'] != 0) {
                    // 納品登録
                    $list = $Delivery->getList($params);
                    if (count($list) > 0) {
                        // 更新
                        $params['id'] = $list[0]->id;
                        $saveResult = $Delivery->updateById($params);
                    } else {
                        // 追加
                        $saveResult = $Delivery->add($params);
                    }
                    if (!$saveResult) {
                        throw new \Exception(config('message.error.save'));
                    }
                    // 入出庫登録
                    $params['move_kind'] = 8;
                    $params['move_flg'] = 0;
                    $params['quantity'] = $params['delivery_quantity'];
                    $params['from_warehouse_id'] = $params['to_warehouse_id'];
                    $params['from_shelf_number_id'] = $params['to_shelf_number_id'];
                    $params['to_warehouse_id'] = 0;
                    $params['to_shelf_number_id'] = 0;
                    $params['order_id'] = 0;
                    $params['order_no'] = 0;
                    $params['reserve_id'] = 0;
                    $params['arrival_id'] = 0;
                    $params['sales_id'] = 0;
                    //在庫品の場合、案件と顧客IDは0
                    if ($params['stock_flg'] == 1) {
                        $params['matter_id'] = 0;
                        $params['customer_id'] = 0;
                    }
                    try {
                        $SystemUtil = new SystemUtil();
                        $saveResult = $SystemUtil->MoveInventory($params);

                        foreach ($scanData as $qr) {
                            $SystemUtil->deleteQr($qr['qr_id']);
                        }
                    } catch (\Exception $e) {
                        throw new \Exception(config('message.error.save'));
                    }
                    // 納品完了フラグ更新
                    $saveResult = $ShipmentDetail->updateDeliveryFinish($params);
                    $saveResult = $Loading->updateDeliveryFinish($params);

                    //倉庫移動レコード作成（再配送がある場合のみ）
                    if ($params['delivery_quantity'] != $params['loading_quantity']) {

                        $moveParams['move_kind'] = 1;
                        $moveParams['from_warehouse_id'] = 0;
                        $moveParams['finish_flg'] = 1;
                        $moveParams['from_product_move_id'] = null;
                        $moveParams['to_product_move_id'] = null;
                        $moveParams['product_code'] = $params['product_code'];
                        $moveParams['product_id'] = $params['product_id'];
                        $moveParams['quantity'] = $params['loading_quantity'] - $params['delivery_quantity'];
                        $moveParams['shipment_detail_id'] = $params['shipment_detail_id'];
                        $moveParams['approval_status'] = 0;
                        $moveParams['approval_user'] = null;
                        $moveParams['approval_at'] = null;
                        $moveParams['returnproc_kbn'] = 0;
                        $moveParams['returnproc_date'] = null;
                        // $moveParams['returnproc_check'] = null;
                        // $moveParams['returnproc_check_user'] = 0;
                        $moveParams['returnproc_finish'] = 0;
                        $moveParams['matter_id'] = $params['matter_id'];
                        $moveParams['customer_id'] = $params['customer_id'];
                        $moveParams['delivery_id'] = null;
                        $moveParams['order_detail_id'] = $params['order_detail_id'];
                        $moveParams['quote_id'] = $params['quote_id'];
                        $moveParams['quote_detail_id'] = $params['quote_detail_id'];

                        $saveResult = $WarehouseMove->add($moveParams);

                        if (!$saveResult) {
                            throw new \Exception(config('message.error.save'));
                        }
                    }
                }
            }
            DB::commit();
            $resultSts = true;
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 数量更新
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function quantity(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();
        $ShipmentDetail = new ShipmentDetail();
        try {
            $saveResult = $ShipmentDetail->updateQuantity($params);
            if (!$saveResult) {
                throw new \Exception(config('message.error.save'));
            }
            DB::commit();
            $resultSts = true;
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 論理削除
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            // 編集権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.customer.edit')) === config('const.authority.none')) {
                throw new \Exception();
            }
            $Customer = new Customer();

            // 削除
            $deleteResult = $Customer->deleteById($params['id']);

            if ($deleteResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.delete'));
            } else {
                throw new \Exception(config('message.error.delete'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request)
    {
        $this->validate($request, [
            'change_quantity' => 'numeric',
        ]);
    }
}
