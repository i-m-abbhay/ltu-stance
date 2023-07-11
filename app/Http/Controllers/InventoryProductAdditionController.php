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
use App\Models\Product;
use App\Models\NumberManage;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\Supplier;
use App\Models\ProductstockShelf;
use App\Models\ProductstockCheck;
use App\Models\General;
use Carbon\Carbon;
use App\Libs\SystemUtil;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * 棚卸
 */
class InventoryProductAdditionController extends Controller
{
    const SCREEN_NAME = 'inventory-product-addition';

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
        $Customer = new Customer();
        $Supplier = new Supplier();

        try {
            //リスト取得
            $matterList = $Matter->getCustomerComboList();
            $customerList = $Customer->getComboList();
            $params['maker_flg'] = config('const.supplierMakerKbn.maker');
            $makerList = $Supplier->getList($params);
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Stock.inventory-product-addition')
            ->with('isEditable', $isEditable)
            ->with('matterList', $matterList)
            ->with('makerList', $makerList)
            ->with('customerList', $customerList);
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

            $Product = new Product();
            $productInfo = $Product->getByNameAndMaker($params);
            if ($productInfo['id'] != null) {
                $params['product_id'] = $productInfo['id'];
                $params['product_code'] = $productInfo['product_code'];
                $params['product_name'] = $productInfo['product_name'];
                $params['draft_flg'] = $productInfo['draft_flg'];

                if ($params['draft_flg'] == config('const.flg.on')) {
                    return \Response::json("draftError");
                }
            } else {
                return \Response::json("productError");
            }

            // 同在庫チェック
            if ($params['stock_flg'] == 1) {
                $ProductstockShelf = new ProductstockShelf();
                $list = $ProductstockShelf->getList($params);
                if (count($list) > 0) {
                    return \Response::json("stockError");
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($params);
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
        $resultSts = true;

        $QrDetail = new QrDetail();
        $Qr = new Qr();
        $ProductstockCheck = new ProductstockCheck();
        if ($paramData['matter_id']  == null) {
            $paramData['matter_id']  = 0;
        }
        $paramData['customer_id'] = $paramData['customer_id'];
        if ($paramData['customer_id']  == null) {
            $paramData['customer_id']  = 0;
        }

        //印刷用の配列
        $printArray = [];
        $printNumber = 1;
        DB::beginTransaction();

        try {
            $saveResult = false;

            //在庫品以外の場合、QR発行
            if ($paramData['stock_flg'] != 1) {
                //QR発番
                $NumberManage = new NumberManage();
                $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                $paramData['qr_code'] = $newQrCode;
                $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => $printNumber);
                //印刷用配列更新
                array_push($printArray, $newQrInfo);
                //QR追加
                $QrSaveResult = $Qr->add($newQrInfo);
                //QRDetail追加
                $newQrInfo = $Qr->getList(array('qr_code' => $newQrCode));
                foreach ($newQrInfo as $item) {
                    $newQrId = $item->id;
                }
                if ($paramData['matter_id'] == null) {
                    $paramData['matter_id'] = 0;
                }
                if ($paramData['customer_id'] == null) {
                    $paramData['customer_id'] = 0;
                }
                $newQrDetailInfo = array(
                    'qr_id' => $newQrId,
                    'product_id' => $paramData['product_id'],
                    'matter_id' => $paramData['matter_id'],
                    'customer_id' => $paramData['customer_id'],
                    'arrival_id' => null,
                    'warehouse_id' => $paramData['warehouse_id'],
                    'shelf_number_id' => $paramData['shelf_number_id'],
                    'quantity' => $paramData['quantity_real']
                );
                $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);

                if (!$QrSaveResult || !$QrDetailSaveResult) {
                    // SQL実行エラー
                    throw new \Exception();
                }
            }


            //棚卸レコード作成

            $SaveResult = $ProductstockCheck->add($paramData);
            if (!$SaveResult) {
                // SQL実行エラー
                throw new \Exception();
            }


            //入出庫トランザクションの作成
            $params['move_kind'] = 6;
            $params['move_flg'] = 0;
            $params['quantity'] = $paramData['quantity_real'];
            $params['from_warehouse_id'] = 0;
            $params['from_shelf_number_id'] = 0;
            $params['to_warehouse_id'] = $paramData['warehouse_id'];
            $params['to_shelf_number_id'] = $paramData['shelf_number_id'];
            $params['product_id'] = $paramData['product_id'];
            $params['product_code'] = $paramData['product_code'];
            $params['matter_id'] = $paramData['matter_id'];
            $params['customer_id'] = $paramData['customer_id'];
            $params['stock_flg'] = $paramData['stock_flg'];
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
            $saveResult = true;

            DB::commit();

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
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchProduct(Request $request)
    {
        $result = null;
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            $isProductName = $params['isProductName'];

            $Product = new Product();
            if (array_key_exists('product_code', $params)) {
                $productInfo = $Product->getByCodeAndMaker($params);

                if ($isProductName) {
                    $result =  $productInfo;
                } else {
                    $result =  $productInfo;
                }
            }
        } catch (\Exception $e) {
            return \Response::json($result);
        }
        return \Response::json($result);
    }
}
