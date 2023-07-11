<?php

namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Auth;
use DB;
use Session;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\ProductstockShelf;
use App\Models\Product;
use App\Models\OrderLimit;
use App\Models\Base;
use App\Models\Reserve;
use App\Models\Order;
use App\Models\Arrival;
use Storage;
use App\Models\Qr;
use App\Models\QrDetail;
use Carbon\Carbon;
use App\Models\LockManage;

/**
 * 倉庫別在庫管理
 */
class OrderPointListController extends Controller
{
    const SCREEN_NAME = 'order-point-list';

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
        $Base = new Base();

        $baseData = $Base->getComboList();

        return view('Stock.order-point-list')
            ->with('baseList', $baseData);
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
            $warehouseList = $ProductstockShelf->getStockOrderPointWarehouse($params);
            $orderLimitList = $ProductstockShelf->getStockOrderPointBase($params);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json(['orderLimitList' => $orderLimitList, 'warehouseList' => $warehouseList]);
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
        $table = $request->request->all();

        // // バリデーションチェック
        // $this->isValid($request);

        DB::beginTransaction();
        $OrderLimit = new OrderLimit();
        try {
            $saveResult = false;

            foreach ($table as $params) {

                $newFlg = false;
                if (($params['order_limit'] === null)) {
                    $newFlg = true;
                }

                $params['order_limit'] = $params['input_order_limit'];
                $params['id'] = $params['order_limit_id'];
                if ($params['order_limit'] == 0) {
                    //削除
                    $saveResult = $OrderLimit->deleteById($params['order_limit_id']);
                } elseif ($newFlg) {
                    // 登録
                    $saveResult = $OrderLimit->add($params);
                } else {
                    // 更新
                    $saveResult = $OrderLimit->updateById($params);
                }
            }

            DB::commit();
            $resultSts = true;
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
}
