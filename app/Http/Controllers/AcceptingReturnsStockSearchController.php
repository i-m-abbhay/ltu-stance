<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Construction;
use App\Models\Product;
use App\Models\Matter;
use App\Models\QrDetail;
use App\Models\Delivery;
use App\Models\Warehouse;
use App\Models\ProductstockShelf;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * 在庫検索
 */
class AcceptingReturnsStockSearchController extends Controller
{
    const SCREEN_NAME = 'accepting-returns-stock-search';

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
        $Warehouse = new Warehouse();
        $Product = new Product();

        try {
            // 倉庫リスト取得
            $Warehouse = new Warehouse();
            $params['truck_flg'] = 0;
            $warehouseList = $Warehouse->getList($params);
            // $warehouseList = $Warehouse->getComboList();

            return view('Returns.accepting-returns-stock-search')
                ->with('isEditable', $isEditable)
                ->with('warehouseList', $warehouseList);
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
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
            $ProductstockShelf = new ProductstockShelf();
            $list = $ProductstockShelf->getAcceptingReturnsList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }
}
