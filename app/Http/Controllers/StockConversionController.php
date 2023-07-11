<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Auth;
use DB;
use Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Product;
use App\Models\ProductstockConversion;
use App\Models\ProductstockShelf;
use App\Models\ShelfNumber;
use App\Models\Warehouse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 商品転換
 */
class StockConversionController extends Controller
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
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // 商品リスト取得
        $Product = new Product();
        // $productList = $Product->getComboList();
        // 倉庫リスト取得
        $Warehouse = new Warehouse();
        $warehouseList = $Warehouse->getComboList();

        return view('Stock.stock-conversion')
                ->with('isEditable', $isEditable)
                ->with('warehouseList', $warehouseList)
                // ->with('productList', $productList)
                ;
    }
    
    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        $ProductStock = new ProductstockShelf();
        $Product = new Product();

        // リクエストから検索条件取得
        $params = $request->request->all();
        // バリデーションチェック
        $this->isSearchValid($request); 
        try {
            if ($params['index'] == config('const.flg.off')) {
                // 転換元
                // 一覧データ取得
                $list = $ProductStock->getProductList($params);
            } else {
                // 転換先
                $list = $Product->getProductList($params);

            }

            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
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
        $params = $request->request->all();
        $parentData = json_decode($params['parentData'], true);
        $convertObj1 = json_decode($params['convertObj1'], true);
        $convertObj2 = json_decode($params['convertObj2'], true);

        // バリデーションチェック
        // $this->isValid($request);
        
        $StockConversion = new ProductstockConversion();
        $Productstock = new ProductstockShelf();
        $SystemUtil = new SystemUtil();
        
        DB::beginTransaction();
        try{
            $saveResult = false;
            $isEqual = false;

            // 在庫転換データ
            $items = [];
            $stockData['shelf_kind'] = config('const.shelfKind.normal');
            // $items['conversion_datetime'] = Carbon::now();
            $items['org_product_id'] = $parentData['product_id'];
            $items['org_product_code'] = $parentData['product_code'];
            $items['org_warehouse_id'] = $parentData['warehouse_id'];
            $items['org_shelf_number_id'] = $parentData['shelf_number_id'];
            $items['org_quantity'] = $parentData['quantity'];
            if (!empty($convertObj1)) {
                $items['conv1_product_id'] = $convertObj1['product_id'];
                $items['conv1_product_code'] = $convertObj1['product_code'];
                $items['conv1_warehouse_id'] = $parentData['warehouse_id'];
                $items['conv1_shelf_number_id'] = $convertObj1['shelf_number_id'];
                if (empty($convertObj1['shelf_number_id'])) {
                    $convertObj1['shelf_kind'] = config('const.shelfKind.normal');
                    $list = $Productstock->getListMoveInventory($convertObj1);
                    if (count($list) > 0) {
                        $convertObj1['shelf_number_id'] = $list[0]['shelf_number_id'];
                        $items['conv1_shelf_number_id'] = $list[0]['shelf_number_id'];
                    } else {
                        $convertObj1['shelf_number_id'] = $parentData['shelf_number_id'];
                        $items['conv1_shelf_number_id'] = $parentData['shelf_number_id'];
                    }
                }
                if (!empty($convertObj2) && $convertObj1['product_id'] == $convertObj2['product_id']) {
                    // 転換先１、２が同商品だった場合
                    $isEqual = true;
                    $convertObj1['quantity'] = (int)$convertObj1['quantity'] + (int)$convertObj2['quantity'];
                } 
                $items['conv1_quantity'] = (int)$convertObj1['quantity'];
            }
            if (!empty($convertObj2) && !$isEqual) { 
                $items['conv2_product_id'] = $convertObj2['product_id'];
                $items['conv2_product_code'] = $convertObj2['product_code'];
                $items['conv2_warehouse_id'] = $parentData['warehouse_id'];
                $items['conv2_shelf_number_id'] = $convertObj2['shelf_number_id'];
                if (empty($convertObj2['shelf_number_id'])) {
                    $convertObj2['shelf_kind'] = config('const.shelfKind.normal');
                    $list = $Productstock->getListMoveInventory($convertObj2);
                    if (count($list) > 0) {
                        $convertObj2['shelf_number_id'] = $list[0]['shelf_number_id'];
                        $items['conv1_shelf_number_id'] = $list[0]['shelf_number_id'];
                    } else {
                        $convertObj2['shelf_number_id'] = $parentData['shelf_number_id'];
                        $items['conv1_shelf_number_id'] = $parentData['shelf_number_id'];
                    }
                }
                $items['conv2_quantity'] = $convertObj2['quantity'];
            }
            $newId = $StockConversion->add($items);

            // 転換元データ
            $stockData = [];
            $stockData['product_id'] = $parentData['product_id'];
            $stockData['stock_flg'] = config('const.stockFlg.val.stock');
            $stockData['product_code'] = $parentData['product_code'];
            $stockData['from_warehouse_id'] = $parentData['warehouse_id'];
            $stockData['from_shelf_number_id'] = $parentData['shelf_number_id'];
            $stockData['to_warehouse_id'] = 0;
            $stockData['to_shelf_number_id'] = 0;
            $stockData['move_kind'] = config('const.moveKind.convert');
            $stockData['move_flg'] = config('const.flg.off');
            $stockData['quantity'] = $parentData['quantity'];
            $stockData['order_id'] = 0;
            $stockData['order_no'] = 0;
            $stockData['order_detail_id'] = 0;
            $stockData['reserve_id'] = 0;
            $stockData['shipment_id'] = 0;
            $stockData['shipment_detail_id'] = 0;
            $stockData['arrival_id'] = 0;
            $stockData['sales_id'] = 0;
            $stockData['matter_id'] = 0;
            $stockData['customer_id'] = 0;           

            $SystemUtil->MoveInventory($stockData);

            //転換先の在庫増
            if (!empty($convertObj1)) {
                $stockData['product_id'] = $convertObj1['product_id'];
                $stockData['product_code'] = $convertObj1['product_code'];
                $stockData['from_warehouse_id']    = 0;
                $stockData['from_shelf_number_id'] = 0;
                $stockData['to_warehouse_id']      = $parentData['warehouse_id'];
                $stockData['to_shelf_number_id']   = $convertObj1['shelf_number_id'];
                $stockData['quantity'] = $convertObj1['quantity'];
                
                $SystemUtil->MoveInventory($stockData);
            }
            if (!empty($convertObj2) && !$isEqual) {
                $stockData['product_id'] = $convertObj2['product_id'];
                $stockData['product_code'] = $convertObj2['product_code'];
                $stockData['from_warehouse_id']    = 0;
                $stockData['from_shelf_number_id'] = 0;
                $stockData['to_warehouse_id']      = $parentData['warehouse_id'];
                $stockData['to_shelf_number_id']   = $convertObj2['shelf_number_id'];
                $stockData['quantity'] = $convertObj2['quantity'];
                
                $SystemUtil->MoveInventory($stockData);
            }

            $saveResult = true;

            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isSearchValid(Request $request) 
    {
        $this->validate($request, [
            'warehouse_name' => 'required',
        ]);
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
            'warehouse_name' => 'required',
        ]);
    }
}