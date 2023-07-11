<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Carbon\Carbon;
use App\Models\Qr;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\ProductstockShelf;
use App\Models\Order;
use App\Models\Arrival;
use App\Models\QrDetail;
use App\Models\Reserve;
use Storage;
use Session;
use App\Libs\Common;
use App\Models\Customer;
use App\Models\Matter;
use App\Models\ShelfNumber;

/**
 * 在庫照会(スマホ)
 */
class StockInquiryController extends Controller
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
     * 初期表示
     * @return type
     */
    public function index()
    {
        // データ取得
        $Qr = new Qr();
        $Warehouse = new Warehouse();
        $Product = new Product();
        $Supplier = new Supplier();
        $ShelfNumber = new ShelfNumber();
        $Customer = new Customer();
        $Matter = new Matter();

        $QrcodeList = $Qr->getComboList();
        // $warehouseList = $Warehouse->getComboList();
        $warehouseList = $Warehouse->getCompanyComboList();
        // $productList = $Product->getComboList();
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);
        $defaultWarehouse = $Warehouse->getList(['id' => Session::get('warehouse_id')]);

        $shelfData = $ShelfNumber->getComboList();
        $customerData = $Customer->getComboList();
        $matterData = $Matter->getComboList();

        return view('Stock.stock-inquiry')
                ->with('qrcodeList', $QrcodeList)
                ->with('warehouseList', $warehouseList)
                // ->with('productList', $productList)
                ->with('makerList', $makerList)
                ->with('defaultWarehouse', $defaultWarehouse)
                ->with('shelfList', $shelfData)
                ->with('customerList', $customerData)
                ->with('matterList', $matterData)
                ;
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    { 
        // リクエストから検索条件取得
        $params = $request->request->all();

        $qrFlg = false;
        $isMultiple = false;

        $Arrival = new Arrival();
        $arrivalList = $Arrival->getArrivalDataByWarehouseAndProduct();

        if (!empty($params['qr_code'])) {
            $qrFlg = true;
        }
        if (!$qrFlg) {
            // 検索した場合
            // バリデーションチェック
            $this->isValid($request);
        }
        
        try {
            // 一覧データ取得
            $ProductstockShelf = new ProductstockShelf();
            $Order = new Order();
            $Arrival = new Arrival();
            $Reserve = new Reserve();
            $QrDetail = new QrDetail();

            if ($qrFlg) {
                // QRを読み込んだ場合
                
                // QRリスト取得
                $qrList = $QrDetail->getStockList(null, null, $params['qr_code'], $params);

                if (count($qrList) > 1) {
                    $isMultiple = true;
                }
                
                if ($isMultiple) {
                    $list = [];

                    foreach ($qrList as $qrKey => $qrVal) {

                        $stock = $ProductstockShelf->getStockByPWId($qrList[$qrKey]['product_id'], $qrList[$qrKey]['warehouse_id'], $params);

                        foreach ($stock as $skey => $val) {
                            // 次回入荷日取得
                            $nextDate = $Order->getNextArrivalDate($val['warehouse_id'], $val['product_id']);
                            // 最終入荷日取得
                            $lastDate = $Arrival->getLastArrivalDate($val['warehouse_id'], $val['product_id']);
        
                            $actual = (int)$stock[$skey]['actual_quantity'];
                            $arrQuantity = $Arrival->getArrivalQuantity($val['warehouse_id'], $val['product_id']);
                            $resQuantity = $Reserve->getReserveQuantity($val['warehouse_id'], $val['product_id']);

                            $stockReserveData = $Reserve->getReserveQuantityByWarehouseAndProduct($val['warehouse_id'], $val['product_id']);
                            $reserveQuantity = 0;
                            foreach ($stockReserveData as $rKey => $row) {
                                if ($stockReserveData[$rKey]['stock_flg'] == config('const.stockFlg.val.stock')) {
                                    // 自社在庫の引当数計算
                                    $reserveQuantity += $stockReserveData[$rKey]['reserve_quantity'];
                                }
                            }

                            $arrivalQuantity = 0;
                            $arrivalData = collect($arrivalList)->where('product_id', $val['product_id'])->where('delivery_address_id', $val['warehouse_id'])->toArray();
                            foreach($arrivalData as $i => $data) {
                                switch($arrivalData[$i]['own_stock_flg']) {
                                    case config('const.flg.on'):
                                        $arrivalQuantity = (int)Common::nullorBlankToZero($arrivalData[$i]['arrival_quantity']);
                                        break;
                                }
                            }
        
                            // 配列にセット
                            $stock[$skey]['next_arrival_date'] = $nextDate['next_arrival_date'];
                            $stock[$skey]['last_arrival_date'] = $lastDate['arrival_date'];
                            $stock[$skey]['active_quantity'] = $actual - $reserveQuantity + $arrivalQuantity;
                            $stock[$skey]['arrival_quantity'] = (int)$arrQuantity['arrival_quantity'];
                            $stock[$skey]['reserve_quantity'] = (int)$resQuantity['reserve_quantity'];
                            $stock[$skey]['qr_code'] = $qrList[$qrKey]['qr_code']; 
        
                            // 画像取得
                            $fileNm = $stock[$skey]['file_name'];
                            $path = config('const.uploadPath.product').$fileNm;
                            if (!empty($fileNm)) {
                                $fileInfo = pathinfo($path);
        
                                // バイナリ変換して出力する
                                if(in_array($fileInfo['extension'], config('const.extEncode.png'))){
                                    $stock[$skey]['image'] = config('const.encode.png').base64_encode(Storage::get($path));
                                }
                                if(in_array($fileInfo['extension'], config('const.extEncode.jpeg'))){
                                    $stock[$skey]['image'] = config('const.encode.jpeg').base64_encode(Storage::get($path));
                                }
                            }
                            $list[] = $stock[$skey]->toArray();
                        }
                    }
                } else {
                    // 引当リスト取得
                    $reserveList = $Reserve->getReserveList($qrList[0]['product_id'], $qrList[0]['warehouse_id']);
                    // 在庫情報取得
                    $stock = $ProductstockShelf->getStockByPWId($qrList[0]['product_id'], $qrList[0]['warehouse_id'], $params)->first();

                    // 次回入荷日取得
                    $nextDate = $Order->getNextArrivalDate($qrList[0]['warehouse_id'], $qrList[0]['product_id']);
                    // 最終入荷日取得
                    $lastDate = $Arrival->getLastArrivalDate($qrList[0]['warehouse_id'], $qrList[0]['product_id']);

                    $actual = (int)$stock['actual_quantity'];
                    $arrQuantity = $Arrival->getArrivalQuantity($qrList[0]['warehouse_id'], $qrList[0]['product_id']);
                    $resQuantity = $Reserve->getReserveQuantity($qrList[0]['warehouse_id'], $qrList[0]['product_id']);

                    $stockReserveData = $Reserve->getReserveQuantityByWarehouseAndProduct($qrList[0]['warehouse_id'], $qrList[0]['product_id']);
                    $reserveQuantity = 0;
                    foreach ($stockReserveData as $rKey => $row) {
                        if ($stockReserveData[$rKey]['stock_flg'] == config('const.stockFlg.val.stock')) {
                            // 自社在庫の引当数計算
                            $reserveQuantity += $stockReserveData[$rKey]['reserve_quantity'];
                        }
                    }

                    $arrivalQuantity = 0;
                    $arrivalData = collect($arrivalList)->where('product_id', $qrList[0]['product_id'])->where('delivery_address_id', $qrList[0]['warehouse_id'])->toArray();
                    foreach($arrivalData as $i => $data) {
                        switch($arrivalData[$i]['own_stock_flg']) {
                            case config('const.flg.on'):
                                $arrivalQuantity = (int)Common::nullorBlankToZero($arrivalData[$i]['arrival_quantity']);
                                break;
                        }
                    }

                    // 配列にセット
                    $stock['next_arrival_date'] = $nextDate['next_arrival_date'];
                    $stock['last_arrival_date'] = $lastDate['arrival_date'];
                    $stock['active_quantity'] = $actual - $reserveQuantity + $arrivalQuantity;;
                    $stock['arrival_quantity'] = (int)$arrQuantity['arrival_quantity'];
                    $stock['reserve_quantity'] = (int)$resQuantity['reserve_quantity'];

                    // 画像取得
                    $fileNm = $stock['file_name'];
                    $path = config('const.uploadPath.product').$fileNm;
                    if (!empty($fileNm)) {
                        $fileInfo = pathinfo($path);

                        // バイナリ変換して出力する
                        if(in_array($fileInfo['extension'], config('const.extEncode.png'))){
                            $stock['image'] = config('const.encode.png').base64_encode(Storage::get($path));
                        }
                        if(in_array($fileInfo['extension'], config('const.extEncode.jpeg'))){
                            $stock['image'] = config('const.encode.jpeg').base64_encode(Storage::get($path));
                        }
                    }
                }
            } else {
                // 検索した場合
                $isMultiple = true;
                $list = $ProductstockShelf->getStockRefList($params);

                if (count($list) == 0) {
                    // 検索結果が0件だった場合
                    Session::flash('flash_error', config('message.error.search.none'));
                    return \Response::json([]);
                }

                foreach ($list as $key => $val) {
                    // 次回入荷日取得
                    $nextDate = $Order->getNextArrivalDate($val['warehouse_id'], $val['product_id']);
                    // 最終入荷日取得
                    $lastDate = $Arrival->getLastArrivalDate($val['warehouse_id'], $val['product_id']);

                    // $actual = (int)$list[$key]['actual_quantity'];
                    $actual = (int)$list[$key]['inventory_quantity'];
                    $arrQuantity = $Arrival->getArrivalQuantity($val['warehouse_id'], $val['product_id']);
                    $resQuantity = $Reserve->getReserveQuantity($val['warehouse_id'], $val['product_id']);
                    $notNormalShelfQuantity = $ProductstockShelf->getReturnAndKeepQuantity($val['warehouse_id'], $val['product_id']);

                    $qrCode = $QrDetail->getStockList($val['product_id'], $val['warehouse_id'], null, $params);

                    $stockReserveData = $Reserve->getReserveQuantityByWarehouseAndProduct($val['warehouse_id'], $val['product_id']);
                    $reserveQuantity = 0;
                    foreach ($stockReserveData as $rKey => $row) {
                        if ($stockReserveData[$rKey]['stock_flg'] == config('const.stockFlg.val.stock')) {
                            // 自社在庫の引当数計算
                            $reserveQuantity += $stockReserveData[$rKey]['reserve_quantity'];
                        }
                    }

                    $arrivalQuantity = 0;
                    $arrivalData = collect($arrivalList)->where('product_id', $val['product_id'])->where('delivery_address_id', $val['warehouse_id'])->toArray();
                    foreach($arrivalData as $i => $data) {
                        switch($arrivalData[$i]['own_stock_flg']) {
                            case config('const.flg.on'):
                                $arrivalQuantity = (int)Common::nullorBlankToZero($arrivalData[$i]['arrival_quantity']);
                                break;
                        }
                    }

                    // 配列にセット
                    $list[$key]['next_arrival_date'] = $nextDate['next_arrival_date'];
                    $list[$key]['last_arrival_date'] = $lastDate['arrival_date'];
                    $list[$key]['active_quantity'] = $actual - $reserveQuantity + $arrivalQuantity;
                    $list[$key]['arrival_quantity'] = (int)$arrQuantity['arrival_quantity'];
                    $list[$key]['reserve_quantity'] = (int)$resQuantity['reserve_quantity'];

                    // 画像取得
                    $fileNm = $list[$key]['file_name'];
                    $path = config('const.uploadPath.product').$fileNm;
                    if (!empty($fileNm)) {
                        $fileInfo = pathinfo($path);

                        // バイナリ変換して出力する
                        if(in_array($fileInfo['extension'], config('const.extEncode.png'))){
                            $list[$key]['image'] = config('const.encode.png').base64_encode(Storage::get($path));
                        }
                        if(in_array($fileInfo['extension'], config('const.extEncode.jpeg'))){
                            $list[$key]['image'] = config('const.encode.jpeg').base64_encode(Storage::get($path));
                        }
                    }
                }
            }
            

        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.search.none'));
        }
        
        if ($isMultiple) {
            // 一覧へ
            return \Response::json($list);
        } else {
            // 詳細へ
            return \Response::json(['mainData' => $stock, 'reserveData' => $reserveList->values(), 'qrData' => $qrList->values()]);
        }
    }

    /**
     * 選択された棚番在庫の情報取得
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function getDetail(Request $request)
    { 
        // リクエストからメインデータを取得
        $params = $request->request->all();
        
        try {
            // 一覧データ取得
            $Reserve = new Reserve();
            $QrDetail = new QrDetail();

            // 引当リスト取得
            $reserveList = $Reserve->getReserveList($params['product_id'], $params['warehouse_id']);
            // QRリスト取得
            $qrList = $QrDetail->getStockList($params['product_id'], $params['warehouse_id'], $params['qr_code'], $params);
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.search.none'));
        }

        return \Response::json(['mainData' => $params, 'reserveData' => $reserveList->values(), 'qrData' => $qrList->values()]);
    }
    
    /**
     * QR印刷
     * @param Request $request
     * @return type
     */
    public function print(Request $request)
    {
        // リクエスト取得
        $params = $request->request->all();

        try {
            $Qr = new Qr();
            $printNum = $Qr->getPrintNumber($params);
            $SystemUtil = new SystemUtil();
            $result = $SystemUtil->qrPrintManager($params, $printNum['print_number']);

        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($result);
    }


    /**
     * バリデーションチェック
     *
     * @param \App\Http\Controllers\Request $request
     * @return boolean
     */
    public function isValid(Request $request) 
    {   
        
        $this->validate($request, [
            'warehouse_name' => 'required',
        ]);
    }  
}