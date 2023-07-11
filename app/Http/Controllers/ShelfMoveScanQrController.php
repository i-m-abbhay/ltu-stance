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
use App\Models\QrDetail;
use App\Models\ShelfNumber;
use App\Models\ProductstockShelf;
use App\Models\General;
use Storage;
use App\Libs\SystemUtil;
use App\Libs\Common;
use App\Models\Authority;
use App\Models\ProductMove;
use Auth;

/**
 * 倉庫内移動
 */
class ShelfMoveScanQrController extends Controller
{
    const SCREEN_NAME = 'shelf-move-scan-qr';

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

        return view('Stock.shelf-move-scan-qr')
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
            $QrDetail = new QrDetail();
            $list = $QrDetail->getShelfMoveScanQr($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }

    /**
     * 棚検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchShelf(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();

            // 一覧データ取得
            $ShelfNumber = new ShelfNumber();
            $list = $ShelfNumber->getById($params);
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
        // リクエストデータ取得
        $paramData = $request->request->all();
        $shelf_number_id = $paramData['shelf_number_id'];
        $warehouse_id = $paramData['warehouse_id'];
        $scanData = $paramData['scanData'];

        // 一覧データ取得
        $QrDetail = new QrDetail();
        $ProductMove = new ProductMove();
        $ProductstockShelf = new ProductstockShelf();
        $ShelfNumber = new ShelfNumber();

        DB::beginTransaction();

        try {
            $saveResult = false;
            foreach ($scanData as $data) {
                $params = [];

                //既に別棚に自社在庫があるかどうか
                if ($data['qr_id'] === null) {
                    $stockData =  $ProductstockShelf->isExistOwnStock($warehouse_id, $shelf_number_id, $data['shelf_number_id'], $data['product_id']);

                    if ($stockData !== null && count($stockData) > 0) {
                        $result['status'] = false;
                        $result['message'] = "stockError";
                        throw new \Exception($result['message']);
                        // return \Response::json("stockError");
                    }
                }

                //入出庫トランザクションの作成
                $params['move_kind'] = 3;
                $params['move_flg'] = 0;
                $params['quantity'] = $data['quantity'];
                $params['from_warehouse_id'] = $data['warehouse_id'];
                $params['from_shelf_number_id'] = $data['shelf_number_id'];
                $params['to_warehouse_id'] = 0;
                $params['to_shelf_number_id'] = 0;
                $params['product_id'] = $data['product_id'];
                $params['product_code'] = $data['product_code'];
                $params['matter_id'] = $data['matter_id'];
                if ($params['matter_id']  == null) {
                    $params['matter_id']  = 0;
                }
                $params['customer_id'] = $data['customer_id'];
                if ($params['customer_id']  == null) {
                    $params['customer_id']  = 0;
                }

                if ($params['matter_id'] != 0 && $params['customer_id'] != 0) {
                    $params['stock_flg'] = 0; //発注品
                } else if ($params['matter_id'] == 0 && $params['customer_id'] != 0) {
                    $params['stock_flg'] = 2; //預かり品
                } else {
                    $params['stock_flg'] = 1; //在庫品
                }
                $params['order_id'] = 0;
                $params['order_detail_id'] = 0;
                $params['order_no'] = 0;
                $params['reserve_id'] = 0;
                $params['shipment_id'] = 0;
                $params['shipment_detail_id'] = 0;
                $params['arrival_id'] = 0;
                $params['sales_id'] = 0;

                // 移動元の在庫が存在するか
                $isOwnStock = $ProductstockShelf->isOwnStock($data['product_id'], $data['warehouse_id'], $data['shelf_number_id'], $params['stock_flg'], $params['matter_id'], $params['customer_id']);
                if (!$isOwnStock) {
                    // 存在しない場合
                    $result['status'] = false;
                    $result['message'] = "stockNotExist";
                    throw new \Exception($result['message']);
                    // return \Response::json("stockNotExist");
                }


                $SystemUtil = new SystemUtil();
                // 移動元倉庫の在庫を減らす
                $SystemUtil->MoveInventory($params);
                
                // 移動先倉庫の在庫を増やす
                // 棚取得
                $shelfData = $ShelfNumber->getById($shelf_number_id);
                $params['from_warehouse_id'] = 0;
                $params['from_shelf_number_id'] = 0;
                $params['to_warehouse_id'] = $warehouse_id;
                $params['to_shelf_number_id'] = $shelf_number_id;
                $params['shelf_kind'] = $shelfData['shelf_kind'];

                $product_move_id = $SystemUtil->MoveInventory($params);
                $ShelfInfo = $ProductMove->getToShelfInfo($product_move_id);

                

                $result['status'] = true;
                $result['message'] = $ShelfInfo['shelf_area'];

                //QRを移動
                if (
                    $data['qr_id'] != null
                ) {
                    $QrDetail->updateWareHouseAndShelf($data['qr_id'], $warehouse_id, $shelf_number_id);
                }
            }

            DB::commit();

            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }
}
