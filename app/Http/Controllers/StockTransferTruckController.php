<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseMove;
use App\Models\Warehouse;
use App\Models\QrDetail;
use App\Models\ShelfNumber;
use App\Libs\SystemUtil;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DB;
use App\Libs\Common;
use App\Models\Reserve;

/**
 * 在庫移管
 */
class StockTransferTruckController extends Controller
{

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

        // トラックリスト取得
        $ShelfNumber = new ShelfNumber();
        $shelfnumberList = $ShelfNumber->getTruckList();
        
        return view('Transfer.stock-transfer-truck')
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
            $WarehouseMove = new WarehouseMove();
            $list = $WarehouseMove->getListTransferTruck($params);
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
        $id = $paramData['id'];
        $productData = $paramData['productData'];
        $to_warehouse_id = $paramData['to_warehouse_id'];
        $to_shelf_number_id = $paramData['to_shelf_number_id'];

        // 一覧データ取得
        $WarehouseMove = new WarehouseMove();
        $QrDetail = new QrDetail();
        $Reserve = new Reserve();
        $params = array("id" => $id);
        $tableData = $WarehouseMove->getListStockTransferMoveInventory($params);

        for ($i = 0; $i < count($tableData); $i++) {
            if ($tableData[$i]['quantity'] == 0) {
                foreach ($productData as $product) {
                    if (
                        $product['from_warehouse_id'] == $tableData[$i]['from_warehouse_id']
                        && $product['shelf_number_id'] == $tableData[$i]['shelf_number_id']
                        && $product['stock_flg'] == $tableData[$i]['stock_flg']
                        && $product['product_id'] == $tableData[$i]['product_id']
                        && $product['matter_id'] == $tableData[$i]['matter_id']
                        && $product['customer_id'] == $tableData[$i]['customer_id']
                    ) {
                        $tableData[$i]['quantity'] += $product['delivery_quantity'];
                    }
                }
            }
        }

        DB::beginTransaction();
        $WarehouseMove = new WarehouseMove();

        try {
            $saveResult = false;
            foreach ($tableData as $data) {

                //入出庫トランザクションの作成
                $params['move_kind'] = 3;
                $params['move_flg'] = 0;
                $params['quantity'] = $data['quantity'];
                $params['from_warehouse_id'] = $data['from_warehouse_id'];
                $params['from_shelf_number_id'] = $data['shelf_number_id'];
                $params['to_warehouse_id'] = $to_warehouse_id;
                $params['to_shelf_number_id'] = $to_shelf_number_id;
                $params['product_id'] = $data['product_id'];
                $params['product_code'] = $data['product_code'];
                $params['matter_id'] = $data['matter_id'];
                $params['customer_id'] = $data['customer_id'];
                $params['stock_flg'] = $data['stock_flg'];
                $params['order_id'] = 0;
                $params['order_detail_id'] = $data['order_detail_id'];
                $params['order_no'] = 0;
                $params['reserve_id'] = 0;
                $params['shipment_id'] = 0;
                $params['shipment_detail_id'] = 0;
                $params['arrival_id'] = 0;
                $params['sales_id'] = 0;
                $SystemUtil = new SystemUtil();
                $from_product_move_id = $SystemUtil->MoveInventory($params);

                foreach ($productData as $product) {
                    if (
                        $product['from_warehouse_id'] == $data['from_warehouse_id']
                        && $product['shelf_number_id'] == $data['shelf_number_id']
                        && $product['stock_flg'] == $data['stock_flg']
                        && $product['product_id'] == $data['product_id']
                        && $product['matter_id'] == $data['matter_id']
                        && $product['customer_id'] == $data['customer_id']
                    ) {
                        //倉庫移管テーブル更新
                        $params['id'] = $product['id'];
                        $params['from_product_move_id'] = $from_product_move_id;
                        $saveResult = $WarehouseMove->updateMoveFinishFromProductMove($params);

                        if ($saveResult === false) {
                            throw new \Exception(config('message.error.save'));
                        }
                    }
                }
            }

            //QRを移動
            foreach ($productData as $product) {
                if (
                    $product['qr_id'] != null
                ) {
                    $saveResult = $QrDetail->updateWareHouseAndShelf($product['qr_id'], $to_warehouse_id, $to_shelf_number_id);
                    

                    // 引当テーブル更新
                    $updateParams = [];
                    if ($product['stock_flg'] == config('const.stockFlg.val.order')) {
                        $rsvData = $Reserve->getByWarehouseAndOrderDetailId($product['from_warehouse_id'], $product['order_detail_id']);

                        if ($rsvData != null && $rsvData['id'] != null) {
                            $updateParams['id'] = $rsvData['id'];

                            $updateParams['reserve_quantity'] = (int)$rsvData['reserve_quantity'] - (int)$product['quantity'];
                            $updateParams['reserve_quantity_validity'] = (int)$rsvData['reserve_quantity_validity'] - (int)$product['quantity'];
                            if (Common::nullorBlankToZero($updateParams['reserve_quantity_validity']) == 0) {
                                $updateParams['finish_flg'] = config('const.flg.on');
                            } else {
                                $updateParams['finish_flg'] = config('const.flg.off');
                            }
                            
                            // 引当数、出荷済数が0個なら削除
                            if (Common::nullorBlankToZero($updateParams['reserve_quantity']) == 0 && Common::nullorBlankToZero($rsvData['issue_quantity']) == 0) {
                                // 削除
                                $saveResult = $Reserve->deleteById($updateParams['id']);
                            } else {
                                // 更新
                                $saveResult = $Reserve->updateById($updateParams);
                            }
                        }
                    }

                    if ($saveResult === false) {
                        throw new \Exception(config('message.error.save'));
                    }
                }
            }

            DB::commit();
            $saveResult = true;

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
