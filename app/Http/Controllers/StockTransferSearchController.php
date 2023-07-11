<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseMove;
use App\Models\Warehouse;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 在庫移管
 */
class StockTransferSearchController extends Controller
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
        // 倉庫リスト取得
        $Warehouse = new Warehouse();
        $params['truck_flg'] = 0;
        $warehouseList = $Warehouse->getList($params);

        return view('Transfer.stock-transfer-search')
                ->with('warehouseList', $warehouseList)
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
            $WarehouseMove = new WarehouseMove();
            $list = $WarehouseMove->getListStockTransfer($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
    
}