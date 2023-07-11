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
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\ProductstockShelf;
use App\Models\QrDetail;
use App\Models\ShelfNumber;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use App\Libs\SystemUtil;
use App\Models\Reserve;
use Auth;

/**
 * トラック登録
 */
class ShippingTruckController extends Controller
{
    const SCREEN_NAME = 'shipping-truck';

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
        // トラックリスト取得
        $ShelfNumber = new ShelfNumber();
        $shelfnumberList = $ShelfNumber->getTruckList();

        return view('Shipping.shipping-truck')
            ->with('shelfnumberList', $shelfnumberList);
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
            $ShipmentDetail = new ShipmentDetail();
            $list = $ShipmentDetail->getMatterList($params);
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
        $result = ['status' => false, 'message' => ''];
        // $resultSts = false;

        // リクエストデータ取得
        $Data = $request->request->all();
        $tableData = $Data['tableData'];
        $to_warehouse_id = $Data['to_warehouse_id'];
        $to_shelf_number_id = $Data['to_shelf_number_id'];

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();
        $Loading = new Loading();
        $Reserve = new Reserve();
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        $QrDetail = new QrDetail();
        try {
            $saveResult = false;

            foreach ($tableData as $params) {

                if ($params['loading_quantity'] != 0) {
                    // 出荷指示が処理済みかどうか
                    $shipmentDetailData = $ShipmentDetail->getById($params['shipment_detail_id']);
                    if ($shipmentDetailData['loading_finish_flg'] == config('const.flg.on')) {
                        $result['message'] = config('message.error.loading.loaded_shipment');
                        throw new \Exception(config('message.error.loading.loaded_shipment'));
                    }

                    $params['to_warehouse_id'] = $to_warehouse_id;
                    $params['to_shelf_number_id'] = $to_shelf_number_id;
                    // 出荷積込登録
                    $params['from_warehouse_kbn'] = 1;
                    $list = $Loading->getList($params);
                    if (count($list) > 0) {
                        // 更新
                        $params['id'] = $list[0]->id;
                        $saveResult = $Loading->updateById($params);
                    } else {
                        // 追加
                        $saveResult = $Loading->add($params);
                    }
                    if (!$saveResult) {
                        throw new \Exception(config('message.error.save'));
                    }

                    // 入出庫登録(複数QRがスキャンされた場合は数に応じて入出庫も作成)
                    $params['move_kind'] = 2;
                    $params['move_flg'] = 0;
                    $params['order_id'] = 0;
                    $params['order_no'] = 0;
                    // $params['reserve_id'] = 0;
                    $params['arrival_id'] = 0;
                    $params['sales_id'] = 0;

                    //QRがスキャンされてない場合（在庫品）
                    if ($params['qrData'] == null) {
                        $params['quantity'] = $params['loading_quantity'];
                        $params['matter_id'] = 0;
                        $params['customer_id'] = 0;
                        $params['matter_no'] = 0;

                        $SystemUtil = new SystemUtil();
                        $saveResult = $SystemUtil->MoveInventory($params);
                    }
                    //QRがスキャンされている場合
                    else {
                        $qrGroup = [];
                        // foreach ($params['qrData'] as $qrInfo) {
                        //     // QRの倉庫、棚チェック
                        //     $qrData = $QrDetail->getById($qrInfo['detail_id']);
                        //     if ($qrData['warehouse_id'] != $qrInfo['warehouse_id'] || $qrData['shelf_number_id'] != $qrInfo['shelf_number_id']) {
                        //         $result['message'] = config('message.error.loading.location_different');
                        //         throw new \Exception(config('message.error.loading.location_different'));
                        //     }
                        // }
                        
                        foreach ($params['qrData'] as $qrInfo) {
                            // 移動前のQRデータ
                            $beforeQrData = $QrDetail->getById($qrInfo['detail_id']);

                            //QR移動
                            // $saveResult = $QrDetail->updateWareHouseAndShelf($qrInfo['qr_id'], $to_warehouse_id, $to_shelf_number_id);
                            $qrDetailParams = [];
                            $qrDetailParams['id'] = $qrInfo['detail_id'];
                            $qrDetailParams['warehouse_id'] = $to_warehouse_id;
                            $qrDetailParams['shelf_number_id'] = $to_shelf_number_id;
                            $saveResult = $QrDetail->updateById($qrDetailParams);

                            //QRを倉庫、棚ごとに集計
                            if (Count($qrGroup) > 0) {
                                $isUpdate = false;
                                for ($i = 0; $i < Count($qrGroup); $i++) {
                                    if ($qrGroup[$i]['warehouse_id'] == $qrInfo['warehouse_id'] && $qrGroup[$i]['shelf_number_id'] == $qrInfo['shelf_number_id']) {
                                        $qrGroup[$i]['quantity'] = $qrGroup[$i]['quantity'] + $qrInfo['quantity'];
                                        $isUpdate = true;
                                    }
                                }
                                if (!$isUpdate) {
                                    array_push($qrGroup, ['detail_id' => $qrInfo['detail_id'], 'beforeQrData' => $beforeQrData, 'warehouse_id' => $qrInfo['warehouse_id'], 'shelf_number_id' => $qrInfo['shelf_number_id'], 'quantity' => $qrInfo['quantity']]);
                                }
                            } else {
                                array_push($qrGroup, ['detail_id' => $qrInfo['detail_id'], 'beforeQrData' => $beforeQrData, 'warehouse_id' => $qrInfo['warehouse_id'], 'shelf_number_id' => $qrInfo['shelf_number_id'], 'quantity' => $qrInfo['quantity'], 'detail_id' => $qrInfo['detail_id']]);
                            }
                        }

                        //預かり品の場合、得意先ID=0
                        if ($params['stock_flg'] == 2) {
                            $params['matter_id'] = 0;
                        }

                        //入出庫トラン作成
                        foreach ($qrGroup as $qrInfo) {
                            $params['from_warehouse_id'] = $qrInfo['warehouse_id'];
                            $params['from_shelf_number_id'] = $qrInfo['shelf_number_id'];
                            $params['quantity'] = $qrInfo['quantity'];

                            // 棚移動されている可能性があるため、再取得したQRの棚番IDを指定する
                            if ($qrInfo['beforeQrData'] !== null && ($qrInfo['beforeQrData']['warehouse_id'] !== $qrInfo['warehouse_id'] || $qrInfo['beforeQrData']['shelf_number_id'] !== $qrInfo['shelf_number_id'])) {
                                $params['from_warehouse_id'] = $qrInfo['beforeQrData']['warehouse_id'];
                                $params['from_shelf_number_id'] = $qrInfo['beforeQrData']['shelf_number_id'];
                            }

                            $SystemUtil = new SystemUtil();
                            $saveResult = $SystemUtil->MoveInventory($params);
                        }
                    }

                    // 出荷完了フラグ更新
                    $saveResult = $ShipmentDetail->updateLoadingFinish($params);

                    //引当テーブル更新
                    // $rsvData = $Reserve->getById($params['reserve_id']);
                    if ($params['stock_flg'] == config('const.stockFlg.val.stock')) {
                        $rsvData = $Reserve->getById($params['reserve_id']);
                        if ($rsvData != null && $rsvData['id'] != null) {
                            $rsvParams['id'] = $rsvData['id'];
                            $rsvParams['issue_quantity'] = (int)$rsvData['issue_quantity'] + (int)$params['loading_quantity'];
                            $rsvParams['reserve_quantity_validity'] = (int)$rsvData['reserve_quantity_validity'] - (int)$params['loading_quantity'];
                            if ((int)$params['loading_quantity'] >= (int)$rsvData['reserve_quantity_validity']) {
                                $rsvParams['finish_flg'] = 1;
                            } else {
                                $rsvParams['finish_flg'] = 0;
                            }
                            $saveResult = $Reserve->updateShipping($rsvParams);
                        }                         
                    } else if (Common::nullorBlankToZero($params['order_detail_id']) !== 0) {
                        $rsvData = $Reserve->getByWarehouseAndOrderDetailId($params['from_warehouse_id'], $params['order_detail_id']);
                        if ($rsvData != null && $rsvData['id'] != null) {
                            $rsvParams['id'] = $rsvData['id'];
                            $rsvParams['issue_quantity'] = (int)$rsvData['issue_quantity'] + (int)$params['loading_quantity'];
                            $rsvParams['reserve_quantity_validity'] = (int)$rsvData['reserve_quantity_validity'] - (int)$params['loading_quantity'];
                            if ((int)$params['loading_quantity'] >= (int)$rsvData['reserve_quantity_validity']) {
                                $rsvParams['finish_flg'] = 1;
                            } else {
                                $rsvParams['finish_flg'] = 0;
                            }
                            $saveResult = $Reserve->updateShipping($rsvParams);
                        }
                    }
                    
                }

                //親テーブル更新
                // $notFinishData = $Shipment->getCountNotFinishDetail($params['shipment_id']);
                // $count = $notFinishData['count'];
                // if ($count <= 0) {
                $saveResult = $Shipment->updateLoadingFinish($params);
                // }
            }

            DB::commit();
            $result['status'] = true;
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if ($result['message'] == '') {
                $result['message'] = config('message.error.error');
                Session::flash('flash_error', config('message.error.error'));
            }
            Log::error($e);
        }
        return \Response::json($result);
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
