<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShelfNumber;
use App\Models\NumberManage;
use App\Models\Loading;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\WarehouseMove;
use App\Models\ProductstockShelf;
use App\Models\Warehouse;
use App\Libs\SystemUtil;
use Carbon\Carbon;
use App\Libs\Common;
use DB;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\OrderDetail;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Reserve;
use App\Models\ShipmentDetail;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 移管受入
 */
class StockTransferAcceptanceCheckController extends Controller
{

    /**
     * 定数
     */
    const WAREHOUSE_MOVE = '0'; //倉庫移動
    const REDELIVERY = '1'; //再配送
    const RETURNS = '2'; //返品
    const LOADING = '3'; //積み込み品

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 
     * 初期表示
     * @return type
     */
    public function index()
    {
        // TODO: 権限チェック
        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Transfer.stock-transfer-acceptance-check')
            ->with('isEditable', $isEditable);;
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
            $ShelfNumber = new ShelfNumber();
            $list = $ShelfNumber->getById($params['shelfNumberId']);
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
        // リクエストデータ取得
        $paramData = $request->request->all();
        $tableData = $paramData['table'];
        $scanData = $paramData['scanData'];
        $wareHouseId = $paramData['wareHouseId'];
        $shelfNumberId = $paramData['shelfNumberId'];
        //印刷用の配列
        $printArray = [];
        $printNumber = 1;

        DB::beginTransaction();
        $Loading = new Loading();
        $WarehouseMove = new WarehouseMove();
        $ShipmentDetail = new ShipmentDetail();
        $Reserve = new Reserve();
        $ProductstockShelf = new ProductstockShelf();
        $ShelfNumber = new ShelfNumber();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        $OrderDetail = new OrderDetail();
        $Quote = new Quote();
        $QuoteDetail = new QuoteDetail();
        $newQrId = null;

        try {
            $saveResult = false;
            foreach ($tableData as $data) {
                $params = [];

                //入出庫トランザクションの作成
                //返品の場合
                if ($data['move_kind'] == self::RETURNS) {
                    // $params['from_warehouse_id'] = 0;
                    // $params['from_shelf_number_id'] = 0;

                    // 倉庫移動の完了フラグチェック
                    $moveData = $WarehouseMove->getById($data['id']);
                    if ($moveData['finish_flg'] == 2) {
                        return \Response::json(["stockError", "受入が完了している商品があります。確認してください。"]);
                    }

                    $params['move_kind'] = 4;
                    $params['is_return_shelf'] = true;
                } else {
                    $params['move_kind'] = 3;
                }

                $params['from_warehouse_id'] = $data['from_warehouse_id'];
                $params['from_shelf_number_id'] = $data['from_shelf_number_id'];
                $params['to_warehouse_id'] = 0;
                $params['to_shelf_number_id'] = 0;
                $params['move_flg'] = 0;
                $params['quantity'] = $data['quantity'];
                $params['product_id'] = $data['product_id'];
                $params['product_code'] = $data['product_code'];
                if ($data['stock_flg'] == 0) {
                    $params['matter_id'] = $data['matter_id'];
                    $params['customer_id'] = $data['customer_id'];
                } elseif ($data['stock_flg'] == 1) {
                    $params['matter_id'] = 0;
                    $params['customer_id'] = 0;
                } else {
                    $params['matter_id'] = 0;
                    $params['customer_id'] = $data['customer_id'];
                }

                $params['stock_flg'] = $data['stock_flg'];
                $params['order_id'] = 0;
                $params['order_detail_id'] = 0;
                $params['order_no'] = 0;
                $params['reserve_id'] = 0;
                $params['shipment_id'] = 0;
                $params['shipment_detail_id'] = 0;
                $params['arrival_id'] = 0;
                $params['sales_id'] = 0;

                //移管先棚チェック
                if ($params['stock_flg'] == 1) {
                    $p["product_id"] = $params["product_id"];
                    $p["warehouse_id"] = $params["to_warehouse_id"];
                    $p["matter_id"] = 0;
                    $p["stock_flg"] = 1;

                    //返品棚かどうか
                    if ($params['move_kind'] == 4) {
                        $p["shelf_kind"] = 3;
                    } else {
                        $p["shelf_kind"] = 0;
                    }
                    $list = $ProductstockShelf->getListMoveInventory($p);

                    if (count($list) > 0 && $list[0]["shelf_number_id"] !=  $params['to_shelf_number_id']) {
                        $shelfInfo = $ShelfNumber->getById($list[0]["shelf_number_id"]);
                        $shelf_area = $shelfInfo["shelf_area"];
                        return \Response::json(["stockError", "同じ商品の自社在庫が" . $shelf_area . "にあります。" . $shelf_area . "で受入してください。"]);
                    }
                }

                $SystemUtil = new SystemUtil();
                $SystemUtil->MoveInventory($params);

                $params['from_warehouse_id'] = 0;
                $params['from_shelf_number_id'] = 0;
                $params['to_warehouse_id'] = $wareHouseId;
                $params['to_shelf_number_id'] = $shelfNumberId;
                // 棚取得
                $shelfData = $ShelfNumber->getById($params['to_shelf_number_id']);
                $params['shelf_kind'] = $shelfData['shelf_kind'];
                $to_product_move_id = $SystemUtil->MoveInventory($params);

                //有効引当数更新
                switch($data['move_kind']) {
                    case 0:
                        // 倉庫移動受入
                        if ($data['stock_flg'] == config('const.stockFlg.val.order') && Common::nullorBlankToZero($data['order_detail_id']) != 0) {
                            // 受入先の引当レコード取得
                            $rsvData = $Reserve->getByWarehouseAndOrderDetailId($wareHouseId, $data['order_detail_id']);
                            
                            if ($rsvData !== null) {
                                // 更新
                                $reserveParams = [];
                                $reserveParams['id']                        = $rsvData['id'];
                                // $reserveParams['from_warehouse_id']         = $wareHouseId;
                                $reserveParams['reserve_quantity']          = $rsvData['reserve_quantity'] + $data['quantity'];
                                $reserveParams['reserve_quantity_validity'] = $rsvData['reserve_quantity_validity'] + $data['quantity'];

                                $saveResult = $Reserve->updateById($reserveParams);
                            } else {
                                // 登録
                                $orderDetailData = $OrderDetail->getById(Common::nullorBlankToZero($data['order_detail_id']));
                                if ($orderDetailData !== null) {
                                    $quoteData = $QuoteDetail->getQuoteByOrderDetailId($orderDetailData['quote_detail_id']);
                                    
                                    $data['quote_id'] = $quoteData['quote_id'];
                                    $data['quote_detail_id'] = $orderDetailData['quote_detail_id'];
                                    $data['order_no'] = $orderDetailData['order_no'];
                                    $data['order_id'] = $orderDetailData['order_id'];
                                }

                                $reserveParams = [];
                                $reserveParams['from_warehouse_id']         = $wareHouseId;
                                $reserveParams['from_shelf_number_id']      = $shelfNumberId;
                                $reserveParams['stock_flg']                 = $data['stock_flg'];
                                $reserveParams['matter_id']                 = $data['matter_id'];
                                $reserveParams['product_id']                = $data['product_id'];
                                $reserveParams['product_code']              = $data['product_code'];
                                $reserveParams['reserve_quantity']          = $data['quantity'];
                                $reserveParams['reserve_quantity_validity'] = $data['quantity'];
                                $reserveParams['issue_quantity']            = 0;
                                $reserveParams['quote_id']                  = Common::nullorBlankToZero($data['quote_id']);
                                $reserveParams['quote_detail_id']           = Common::nullorBlankToZero($data['quote_detail_id']);
                                $reserveParams['order_no']                  = Common::nullToBlank($data['order_no']);
                                $reserveParams['order_id']                  = Common::nullorBlankToZero($data['order_id']);
                                $reserveParams['order_detail_id']           = Common::nullorBlankToZero($data['order_detail_id']);
                                
                                $saveResult = $Reserve->addList([$reserveParams]);
                            }
                        }
                    break;
                    case 1:
                    case 3:
                        if (Common::nullorBlankToZero($data['order_detail_id']) != 0) {
                            // 荷積み受入
                            $reserveParams = [];
                            $reserveParams['from_warehouse_id']         = $wareHouseId;
                            $reserveParams['order_detail_id']           = $data['order_detail_id'];
                            $reserveParams['finish_flg']                = 0;
                            $reserveParams['reserve_quantity_validity'] = $data['reserve_quantity_validity'] + $data['quantity'];
                            $reserveParams['issue_quantity']            = (int)$data['issue_quantity'] - (int)$data['quantity'];

                            $Reserve->updateReserveByOrderDetailAndWarehouseId($reserveParams);
                        } else if ($data['stock_flg'] == config('const.stockFlg.val.stock')) {
                            // 在庫品の再配送
                            $reserveParams = [];
                            $reserveParams['id']                        = $data['reserve_id'];
                            $reserveParams['finish_flg']                = 0;
                            $reserveParams['reserve_quantity_validity'] = $data['reserve_quantity_validity'] + $data['quantity'];
                            $reserveParams['issue_quantity']            = (int)$data['issue_quantity'] - (int)$data['quantity'];

                            $Reserve->updateById($reserveParams);
                        }
                    break;
                }

                //倉庫移管テーブル更新
                if ($data['is_transfar']) {
                    $params['id'] = $data['id'];
                    $params['to_product_move_id'] = $to_product_move_id;
                    $saveResult = $WarehouseMove->updateMoveFinish($params);
                }
                //出荷積込テーブル更新
                else {
                    $params['id'] = $data['id'];
                    $saveResult = $Loading->updateMoveFinish($params);
                }

                if ($saveResult === false) {
                    throw new \Exception(config('message.error.save'));
                }

                //返品、再配送の場合QRの再発行
                if (
                    ($data['move_kind'] == self::RETURNS ||
                    $data['move_kind'] == self::REDELIVERY) && $data['stock_flg'] != 1
                ) {
                    //既存QRの削除
                    $SystemUtil->deleteQr($data['qr_id']);

                    //QR発番
                    $NumberManage = new NumberManage();
                    $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                    $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => $printNumber);
                    //印刷用配列更新
                    array_push($printArray, $newQrInfo);
                    //QR追加
                    $saveResult = $Qr->add($newQrInfo);
                    if ($saveResult === false) {
                        throw new \Exception(config('message.error.save'));
                    }

                    //QRDetail追加
                    $newQrInfo = $Qr->getList(array('qr_code' => $newQrCode));
                    foreach ($newQrInfo as $item) {
                        $newQrId = $item->id;
                    }

                    $arrivalId = 0;                    
                    if (!empty($data['qrData'])) {
                        $qrList = $data['qrData'];
                        if (!empty($qrList[0]['arrival_id'])) {
                            $arrivalId = $qrList[0]['arrival_id'];
                        }
                    }

                    $newQrDetailInfo = array(
                        'qr_id' => $newQrId,
                        'product_id' => $data['product_id'],
                        'matter_id' => $data['matter_id'],
                        'customer_id' => $data['customer_id'],
                        'arrival_id' => $arrivalId,
                        'warehouse_id' => $wareHouseId,
                        'shelf_number_id' => $shelfNumberId,
                        'quantity' => $data['quantity']
                    );
                    $saveResult = $QrDetail->add($newQrDetailInfo);
                    $params['qr_code'] = $newQrCode;
                    $saveResult = $WarehouseMove->updateQr($params);

                    if ($saveResult === false) {
                        throw new \Exception(config('message.error.save'));
                    }
                }

                $qr = null;
                if ($newQrId != null) {
                    $qr = $newQrId;
                } elseif ($data['qr_id'] != null) {
                    $qr = $data['qr_id'];
                }
                if ($qr != null) {
                    $QrDetail->updateWareHouseAndShelf($qr, $wareHouseId, $shelfNumberId);
                }

                if ($data['move_kind'] == self::LOADING && $data['stock_flg'] != 1) {
                    foreach ($scanData as $data) {
                        $QrDetail->updateWareHouseAndShelf($data['qr_id'], $wareHouseId, $shelfNumberId);
                    }
                }
            }

            DB::commit();

            //QR印刷
            $SystemUtil = new SystemUtil();
            for ($i = 0; $i < count($printArray); $i++) {
                $resultSts = $SystemUtil->qrPrintManager(array('qr_code' => $printArray[$i]['qr_code']), $printArray[$i]['print_number']);
                if (!$resultSts) {
                    return \Response::json("printError");
                }
            }
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $saveResult = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($saveResult);
    }
}
