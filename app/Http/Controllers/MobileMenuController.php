<?php

namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Session;
use Illuminate\Support\Facades\Log;
use App\Models\Warehouse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * スマホ用メニュー
 */
class MobileMenuController extends Controller
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
         // 倉庫リスト取得
         $Warehouse = new Warehouse();
         $params['truck_flg'] = 0;
         $warehouseList = $Warehouse->getList($params);
         $warehouseId = Session::get('warehouse_id');

        // データ取得
        return view('Mobile.mobile-menu')
            ->with('warehouseId', $warehouseId === null ? 0:$warehouseId)
            ->with('warehouseList', $warehouseList)
            ;
    }

    /**
     * セッション保存
     * @return type
     */
    public function save(Request $request){
        try {
            // リクエスト取得
            $params = $request->request->all();
            Session::put('warehouse_id',$params['warehouse_id']);

        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json(true);
    }
}
