<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShelfNumber;
use App\Models\Warehouse;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 移管受入
 */
class StockTransferAcceptanceShelfQrController extends Controller
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
        // TODO: 権限チェック
        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Transfer.stock-transfer-acceptance-shelf-qr')
            ->with('isEditable', $isEditable);
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
            $ShelfNumber = new ShelfNumber();
            $list = $ShelfNumber->getWarehouseById($params['shelfNumberId']);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
    
}