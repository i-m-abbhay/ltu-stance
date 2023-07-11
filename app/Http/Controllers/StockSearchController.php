<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\ProductstockShelf;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Supplier;
use App\Models\Reserve;
use App\Models\Order;
use App\Models\Arrival;
use Storage;
use App\Models\Qr;
use App\Models\QrDetail;
use Carbon\Carbon;
use App\Libs\Common;
use App\Models\Customer;
use App\Models\Matter;
use App\Models\ShelfNumber;

/**
 * 在庫照会
 */
class StockSearchController extends Controller
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
        $Product = new Product();
        $Warehouse = new Warehouse();
        $Supplier = new Supplier();
        $ShelfNumber = new ShelfNumber();
        $Customer = new Customer();
        $Matter = new Matter();

        // $productData = $Product->getComboList();
        $warehouseData = $Warehouse->getCompanyComboList();
        $supplierData = $Supplier->getComboList();
        $shelfData = $ShelfNumber->getComboList();
        $customerData = $Customer->getComboList();
        $matterData = $Matter->getComboList();

        return view('Stock.stock-search')
                // ->with('productList', $productData)
                ->with('warehouseList', $warehouseData)
                ->with('supplierList', $supplierData)
                ->with('shelfList', $shelfData)
                ->with('customerList', $customerData)
                ->with('matterList', $matterData)
                ;
    }
    
    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            // 一覧データ取得
            $ProductstockShelf = new ProductstockShelf();
            $Order = new Order();
            $Arrival = new Arrival();
            $Reserve = new Reserve();
            $QrDetail = new QrDetail();
            
            $stockList = $ProductstockShelf->getStockRefList($params);
            $childList = $QrDetail->getStockSearchQrList($params);
            // $qrList = $QrDetail->getStockList();
            // $stocks = $ProductstockShelf->getStockList();

            // $childList = array_merge($qrList->toArray(), $stocks->toArray());
            $reserveList = $Reserve->getReserveList();
            $arrivalList = $Arrival->getArrivalDataByWarehouseAndProduct();

            foreach ($stockList as $key => $val) {
                // 次回入荷日取得
                $nextDate = $Order->getNextArrivalDate($val['warehouse_id'], $val['product_id']);
                // 最終入荷日取得
                $lastDate = $Arrival->getLastArrivalDate($val['warehouse_id'], $val['product_id']);

                $actual = (int)$stockList[$key]['inventory_quantity'];
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

                // $notNormalShelfQuantity = $ProductstockShelf->getReturnAndKeepQuantity($val['warehouse_id'], $val['product_id']);

                // 配列にセット
                $stockList[$key]['next_arrival_date'] = $nextDate['next_arrival_date'];
                $stockList[$key]['last_arrival_date'] = $lastDate['arrival_date'];
                $stockList[$key]['active_quantity'] = $actual - $reserveQuantity + $arrivalQuantity;
                $stockList[$key]['arrival_quantity'] = (int)$arrQuantity['arrival_quantity'];
                $stockList[$key]['reserve_quantity'] = (int)$resQuantity['reserve_quantity'];

                // 画像取得
                $fileNm = $stockList[$key]['file_name'];
                $path = config('const.uploadPath.product').$fileNm;
                if (!empty($fileNm)) {
                    $fileInfo = pathinfo($path);

                    // バイナリ変換して出力する
                    if(in_array($fileInfo['extension'], config('const.extEncode.png'))){
                        $stockList[$key]['image'] = config('const.encode.png').base64_encode(Storage::get($path));
                    }
                    if(in_array($fileInfo['extension'], config('const.extEncode.jpeg'))){
                        $stockList[$key]['image'] = config('const.encode.jpeg').base64_encode(Storage::get($path));
                    }
                }
            }

            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json(['stock' => $stockList->values(), 'qr' => $childList, 'reserve' => $reserveList->values()]);
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
}