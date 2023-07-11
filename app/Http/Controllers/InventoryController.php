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
use App\Models\QrDetail;
use App\Models\ShelfNumber;
use App\Models\ProductMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * 棚卸
 */
class InventoryController extends Controller
{
    const SCREEN_NAME = 'inventory';

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

        return view('Stock.inventory')
            ->with('isEditable', $isEditable);
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

            //棚存在チェック
            $ShelfNumber = new ShelfNumber();
            $list = $ShelfNumber->getWarehouseById($params['qr_code']);
            if ($list == null || $list['id'] == null) {
                return \Response::json("SHELF_ERROR");
            }
            $shelf_number_id = $list['id'];
            $shelf_area = $list['shelf_area'];
            $warehouse_id = $list['warehouse_id'];
            $warehouse_name = $list['warehouse_name'];

            // 一覧データ取得
            $QrDetail = new QrDetail();
            $list = $QrDetail->getShelfMoveQr($params['qr_code']);
            $list = $list->where('product_id', '<>', null);
            $result = [
                'shelf_number_id' => $shelf_number_id,
                'shelf_area' => $shelf_area,
                'warehouse_id' => $warehouse_id,
                'warehouse_name' => $warehouse_name,
                'list' => $list
            ];
          
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($result);
    }
}
