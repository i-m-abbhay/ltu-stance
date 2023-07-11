<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Shipment;
use App\Models\Warehouse;
use App\Models\Loading;
use App\Models\ShelfNumber;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 納品先検索
 */
class DeliveryListController extends Controller
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

        return view('Delivery.delivery-list')
                ->with('shelfnumberList', $shelfnumberList)
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
            
            // 一覧データ取得
            $Loading = new Loading();
            $list = $Loading->getListMatter($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
    
}