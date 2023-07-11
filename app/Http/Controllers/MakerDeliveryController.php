<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\WarehouseMove;
use App\Models\Supplier;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Returns;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * メーカー引渡
 */
class MakerDeliveryController extends Controller
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
        // メーカーリスト取得
        $Supplier = new Supplier();
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);

        return view('Returns.maker-delivery')
            ->with('makerList', $makerList);
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
            $Returns = new Returns();
            $list = $Returns->getListMakerDelivery($params);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($list);
    }
}
