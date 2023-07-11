<?php

namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Arrival;
use App\Models\Matter;
use App\Models\QrDetail;
use App\Models\Qr;
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\NumberManage;
use Carbon\Carbon;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * QR商品別分割
 */
class QrMatterChangeController extends Controller
{
    /**
     * 定数
     */
    const ORDERED_GOODS = '0'; //発注品
    const STOCK = '1'; //在庫品
    const CUSTODY = '2'; //預かり品
    const SCREEN_NAME = 'qr-matter-change';


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
        $Matter = new Matter();

        try {
            // 案件リスト取得
            $matterList = $Matter->getComboList();
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Stock.qr-matter-change')
            ->with('isEditable', $isEditable)
            ->with('matterList', $matterList);
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $param = $request->request->all();
        $qrInfo = $param['qrInfo'];
        $matterNo = $param['matterNo'];

        //印刷用の配列
        $printArray = [];
        $printNumber = 2;

        DB::beginTransaction();
        $QrDetail = new QrDetail();
        $Matter = new Matter();
        $Arrival = new Arrival();
        $SystemUtil = new SystemUtil();

        try {
            $QrDetailSaveResult = false;
            $ProductMoveResult = false;
            $delLock = false;

            //案件IDに紐づく得意先IDを取得
            $matterId = '';
            $customerId = '';
            if ($matterNo == null || $matterNo == "" || $matterNo ==  0) {
                $matterNo = 0;
                $matterId = 0;
                $customerId = 0;
            } else {
                $matterNo = intval($matterNo);
                $matterInfo = $Matter->getByMatterNo($matterNo);
                $matterId = $matterInfo["id"];
                $customerId = $matterInfo["customer_id"];
            }

            //印刷用配列更新
            array_push($printArray, array('qr_code' => $qrInfo[0]['qr_code'], 'print_number' => $printNumber));

            //案件ID変換、棚番在庫更新
            for ($i = 0; $i < count($qrInfo); $i++) {

                //QRDetail更新
                $newQrDetailInfo = array(
                    'id' => $qrInfo[$i]['detail_id'],
                    'qr_id' => $qrInfo[$i]['qr_id'],
                    'product_id' => $qrInfo[$i]['product_id'],
                    'matter_id' => $matterId,
                    'customer_id' => $customerId,
                    'arrival_id' => $qrInfo[$i]['arrival_id'],
                    'warehouse_id' => $qrInfo[$i]['warehouse_id'],
                    'shelf_number_id' => $qrInfo[$i]['shelf_number_id'],
                    'quantity' => $qrInfo[$i]['quantity']
                );
                $QrDetailSaveResult = $QrDetail->updateById($newQrDetailInfo);

                //在庫品の場合QR削除
                if ($matterId == 0 && $customerId == 0) {
                    $SystemUtil->deleteQr($qrInfo[$i]['qr_id']);
                }

                //入荷情報の取得
                $arrivalInfo = $Arrival->getListById($qrInfo[$i]['arrival_id']);
                foreach ($arrivalInfo as $item) {
                    $orderId = $item->order_id;
                    $orderNo = $item->order_no;
                    $orderDetailId = $item->order_detail_id;
                    $reserveId = $item->reserve_id;
                }

                //在庫移動(移動元)
                $MoveInventoryParams['from_warehouse_id'] = $qrInfo[$i]['warehouse_id'];
                $MoveInventoryParams['from_shelf_number_id'] = $qrInfo[$i]['shelf_number_id'];
                $MoveInventoryParams['to_warehouse_id'] = 0;
                $MoveInventoryParams['to_shelf_number_id'] = 0;
                $MoveInventoryParams['matter_id'] = $qrInfo[$i]['matter_id'];
                $MoveInventoryParams['customer_id'] = $qrInfo[$i]['customer_id'];
                $MoveInventoryParams['product_id'] = $qrInfo[$i]['product_id'];
                $MoveInventoryParams['product_code'] = $qrInfo[$i]['product_code'];
                $MoveInventoryParams['quantity'] = $qrInfo[$i]['quantity'];
                $MoveInventoryParams['move_kind'] = 9;
                $MoveInventoryParams['move_flg'] = 0;
                $MoveInventoryParams['order_id'] = $orderId;
                $MoveInventoryParams['order_no'] = $orderNo;
                $MoveInventoryParams['order_detail_id'] = $orderDetailId;
                $MoveInventoryParams['reserve_id'] = $reserveId;
                $MoveInventoryParams['shipment_id'] = null;
                $MoveInventoryParams['shipment_detail_id'] = null;
                $MoveInventoryParams['arrival_id'] = $qrInfo[$i]['arrival_id'];
                $MoveInventoryParams['sales_id'] = null;
                $MoveInventoryParams['del_flg'] = 0;

                if ($MoveInventoryParams['matter_id'] != 0 && $MoveInventoryParams['customer_id'] != 0) {
                    $MoveInventoryParams['stock_flg'] = self::ORDERED_GOODS;
                } elseif ($MoveInventoryParams['matter_id'] == 0 && $MoveInventoryParams['customer_id'] != 0) {
                    $MoveInventoryParams['stock_flg'] = self::CUSTODY;
                } else {
                    $MoveInventoryParams['stock_flg'] = self::STOCK;
                }

                $ProductMoveResult = $SystemUtil->MoveInventory($MoveInventoryParams);

                //在庫移動(移動先)
                $MoveInventoryParams['from_warehouse_id'] = 0;
                $MoveInventoryParams['from_shelf_number_id'] = 0;
                $MoveInventoryParams['to_warehouse_id'] = $qrInfo[$i]['warehouse_id'];
                $MoveInventoryParams['to_shelf_number_id'] = $qrInfo[$i]['shelf_number_id'];
                $MoveInventoryParams['matter_id'] = $matterId;
                $MoveInventoryParams['customer_id'] = $customerId;
                if ($MoveInventoryParams['matter_id'] != 0 && $MoveInventoryParams['customer_id'] != 0) {
                    $MoveInventoryParams['stock_flg'] = self::ORDERED_GOODS;
                } elseif ($MoveInventoryParams['matter_id'] == 0 && $MoveInventoryParams['customer_id'] != 0) {
                    $MoveInventoryParams['stock_flg'] = self::CUSTODY;
                } else {
                    $MoveInventoryParams['stock_flg'] = self::STOCK;
                }
                $ProductMoveResult = $SystemUtil->MoveInventory($MoveInventoryParams);

                if (!$ProductMoveResult) {
                    // SQL実行エラー
                    throw new \Exception();
                }
            }

            $delLock = true;

            if ($delLock) {

                DB::commit();
                Session::flash('flash_success', config('message.success.save'));

                //在庫品でない場合印刷
                if ($matterNo != 0) {
                    for ($i = 0; $i < count($printArray); $i++) {
                        $resultSts = $SystemUtil->qrPrintManager(array('qr_code' => $printArray[$i]['qr_code']), $printArray[$i]['print_number']);
                        if (!$resultSts) {
                            return \Response::json("printError");
                        }
                    }
                } else {
                    $resultSts = true;
                }
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
}
