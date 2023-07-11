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
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\General;
use Storage;
use Carbon\Carbon;
use App\Libs\SystemUtil;
use App\Models\ProductstockShelf;
use App\Models\ProductstockCheck;
use App\Libs\Common;
use App\Models\Authority;
use App\Models\ShelfNumber;
use Auth;

/**
 * 棚卸
 */
class InventoryProductListController extends Controller
{
    const SCREEN_NAME = 'inventory-product-list';

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

        return view('Stock.inventory-product-list')
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
            $list = $QrDetail->getShelfMoveQr($params['qr_code']);
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
        $qrInfo = $paramData["qrInfo"];

        $QrDetail = new QrDetail();
        $Qr = new Qr();
        $ProductstockCheck = new ProductstockCheck();
        $ShelfNumber = new ShelfNumber();

        DB::beginTransaction();

        try {
            $saveResult = false;
            for ($i = 0; $i < count($qrInfo); $i++) {

                if ($qrInfo[$i]['confirmed_quantity'] != null && $qrInfo[$i]['confirmed_quantity'] != "") {

                    if ($qrInfo[$i]['matter_id']  == null) {
                        $qrInfo[$i]['matter_id']  = 0;
                    }
                    $qrInfo[$i]['customer_id'] = $qrInfo[$i]['customer_id'];
                    if ($qrInfo[$i]['customer_id']  == null) {
                        $qrInfo[$i]['customer_id']  = 0;
                    }


                    //棚卸レコード作成 
                    $qrInfo[$i]['quantity_logic'] = $qrInfo[$i]['quantity'];
                    $qrInfo[$i]['quantity_real'] = $qrInfo[$i]['confirmed_quantity'];
                    $SaveResult = $ProductstockCheck->add($qrInfo[$i]);
                    if (!$SaveResult) {
                        // SQL実行エラー
                        throw new \Exception();
                    }

                    //棚番在庫更新
                    if ($qrInfo[$i]['confirmed_quantity'] != $qrInfo[$i]['quantity']) {
                        //入出庫トランザクションの作成
                        $params['move_kind'] = 6;
                        $params['move_flg'] = 0;

                        // 棚取得
                        $shelfData = $ShelfNumber->getById($qrInfo[$i]['shelf_number_id']);
                        $params['shelf_kind'] = $shelfData['shelf_kind'];

                        //実在庫の方が多かった場合
                        if ($qrInfo[$i]['quantity_real'] > $qrInfo[$i]['quantity_logic']) {
                            $params['quantity'] = $qrInfo[$i]['quantity_real'] - $qrInfo[$i]['quantity_logic'];
                            $params['from_warehouse_id'] = 0;
                            $params['from_shelf_number_id'] = 0;
                            $params['to_warehouse_id'] = $qrInfo[$i]['warehouse_id'];
                            $params['to_shelf_number_id'] = $qrInfo[$i]['shelf_number_id'];
                        } else {
                            $params['quantity'] = $qrInfo[$i]['quantity_logic'] - $qrInfo[$i]['quantity_real'];
                            $params['from_warehouse_id'] = $qrInfo[$i]['warehouse_id'];
                            $params['from_shelf_number_id'] = $qrInfo[$i]['shelf_number_id'];
                            $params['to_warehouse_id'] = 0;
                            $params['to_shelf_number_id'] = 0;
                        }

                        $params['product_id'] = $qrInfo[$i]['product_id'];
                        $params['product_code'] = $qrInfo[$i]['product_code'];
                        $params['matter_id'] = $qrInfo[$i]['matter_id'];
                        $params['customer_id'] = $qrInfo[$i]['customer_id'];
                        $params['stock_flg'] = $qrInfo[$i]['stock_flg'];
                        $params['order_id'] = 0;
                        $params['order_detail_id'] = 0;
                        $params['order_no'] = 0;
                        $params['reserve_id'] = 0;
                        $params['shipment_id'] = 0;
                        $params['shipment_detail_id'] = 0;
                        $params['arrival_id'] = 0;
                        $params['sales_id'] = 0;
                        $SystemUtil = new SystemUtil();
                        $from_product_move_id = $SystemUtil->MoveInventory($params);
                    }

                    //QR更新
                    if ($qrInfo[$i]['qr_code'] != null && $qrInfo[$i]['qr_code'] != "") {
                        if ($qrInfo[$i]['quantity_real'] == 0) {
                            $SaveResult =  $SystemUtil->deleteQr($qrInfo[$i]['qr_id']);
                        } else {
                            $SaveResult = $QrDetail->updateQuantity($qrInfo[$i]['qr_detail_id'], $qrInfo[$i]['quantity_real']);
                        }
                    }
                }
                DB::commit();
            }

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

    /**
     * QR印刷
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function print(Request $request)
    {
        $resultSts = true;
        // リクエストデータ取得
        $data = $request->request->all();
        $qr_code = $data['qr_code'];

        $SystemUtil = new SystemUtil();

        try {
            //印刷
            $resultSts = $SystemUtil->qrPrintManager(array('qr_code' => $qr_code),  1);
            if (!$resultSts) {
                return \Response::json("printError");
            }
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
}
