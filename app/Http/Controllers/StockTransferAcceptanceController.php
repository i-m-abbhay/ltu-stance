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
 * 移管受入
 */
class StockTransferAcceptanceController extends Controller
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

        $tmpKbn = config('const.warehouseMoveKind');
        $moveKind = [];
        $cnt = 0;
        foreach ($tmpKbn['val'] as $key => $value) {
            $moveKind[$cnt]['text'] = $tmpKbn['text'][$value];
            $moveKind[$cnt]['value'] = $value;
            $cnt++;
        }

        return view('Transfer.stock-transfer-acceptance')
            ->with('defaultWarehouseId', Session::get('warehouse_id'))
            ->with('warehouseList', $warehouseList)
            ->with('moveKindList', json_encode($moveKind))
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
            $list = $WarehouseMove->getListTransferAcceptance($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
    
}