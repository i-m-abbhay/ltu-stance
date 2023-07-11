<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\Order;
use App\Models\ShelfNumber;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


/**
 * 商品一覧
 */
class PurchaseDeliveryListController extends Controller
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
        return view('Delivery.purchase-delivery-list')
                ;
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
            $orderNo = $params['order_no']; 
            
            // 一覧データ取得
            $Order = new Order();
            $result = $Order->getMakerWarehouse($orderNo);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($result);
    }
    
}