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
use App\Models\Loading;
use App\Models\WarehouseMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use App\Models\ProductstockCheck;
use App\Models\ProductstockShelf;
use Auth;

/**
 * 返品情報入力
 */
class AcceptingReturnsInfoInputController extends Controller
{
    const SCREEN_NAME = 'accepting-returns-info-input';

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

        return view('Returns.accepting-returns-info-input')
            ->with('isEditable', $isEditable);
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $rtnStatus = ["status" => false, "warehouse_move_id" => null, "message" => ""];
        // リクエストデータ取得
        $paramData = $request->request->all();
        $tableData = $paramData['tableData'];
        $returns_quantity = $paramData['returns_quantity'];
        $returns_date = $paramData['returns_date'];

        DB::beginTransaction();
        $WarehouseMove = new WarehouseMove();
        $ProductstockShelf = new ProductstockShelf();

        try {
            // $saveResult = false;

            if (isset($tableData['stock_flg'])) {
                $params['stock_flg'] = $tableData['stock_flg'];
            }
            //発注品
            elseif (($tableData['matter_id'] != null && $tableData['customer_id'] != null) || ($tableData['matter_id'] != 0 && $tableData['customer_id'] != 0)) {
                $params['stock_flg'] = 0;
            }
            //在庫品
            elseif (($tableData['matter_id'] == null && $tableData['customer_id'] == null) || ($tableData['matter_id'] == 0 && $tableData['customer_id'] == 0)) {
                $params['stock_flg'] = 1;
            }
            //預かり品
            else {
                $params['stock_flg'] = 2;
            }
            $tableData['from_warehouse_id'] = $tableData['warehouse_id'];

            // 重複チェック
            $isExist = false;
            if ($params['stock_flg'] != config('const.stockFlg.val.stock')) {
                // QR返品
                $isExist = $WarehouseMove->isExistReturnQr($tableData['qr_code']);
                if ($isExist) {
                    // エラー
                    $rtnStatus['message'] = '返品受入済みです。';
                    throw new \Exception("返品受入済みです。");
                }
            } else {
                // 在庫品
                $stockData = $ProductstockShelf->getAcceptingReturnsList($tableData)->first();
                
                $returnedData = $WarehouseMove->getSumStockReturnedQuantity($tableData);
                $sumQuantity = Common::nullorBlankToZero($stockData['quantity']) - Common::nullorBlankToZero($returnedData['quantity']);
                if ($stockData != null) {
                    if ($sumQuantity < $returns_quantity) {
                        $rtnStatus['message'] = '返品可能数を超えています。確認してください。';

                        throw new \Exception($rtnStatus['message']);
                    }
                }

            }


            //倉庫移動レコードの作成
            $params['move_kind'] = 2; //返品
            $params['from_warehouse_id'] = $tableData['warehouse_id'];
            $params['to_warehouse_id'] = 0;
            $params['finish_flg'] = 0;
            $params['from_product_move_id'] = null;
            $params['from_product_move_staff_id'] = null;
            $params['from_product_move_at'] = null;
            $params['to_product_move_id'] = null;
            $params['to_product_move_staff_id'] = null;
            $params['to_product_move_at'] = null;
            $params['memo'] = null;
            $params['move_date'] = $returns_date;


            $params['product_code'] = $tableData['product_code'];
            $params['product_id'] = $tableData['product_id'];
            $params['quantity'] = $returns_quantity;
            if(isset($tableData['qr_code'])){
                $params['qr_code'] = $tableData['qr_code'];
            }else{
                $params['qr_code'] = null;
            }
            $params['shipment_detail_id'] =null;
            $params['approval_status'] = 0;
            $params['approval_user'] = null;
            $params['approval_at'] = null;
            $params['matter_id'] = $tableData['matter_id'];
            $params['customer_id'] = $tableData['customer_id'];
            $params['delivery_id'] = null;
            $params['order_detail_id'] = $tableData['order_detail_id'];
            $params['quote_id'] = $tableData['quote_id'];
            $params['quote_detail_id'] = $tableData['quote_detail_id'];

            $rtnStatus['warehouse_move_id'] = $WarehouseMove->add($params);
            $rtnStatus['status'] = true;

            if ($rtnStatus['status'] === false) {
                throw new \Exception(config('message.error.save'));
            }

            DB::commit();

            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $rtnStatus['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($rtnStatus);
    }
}
