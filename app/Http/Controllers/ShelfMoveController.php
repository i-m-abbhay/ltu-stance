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
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use App\Models\ShelfNumber;
use Auth;

/**
 * 倉庫内移動
 */
class ShelfMoveController extends Controller
{
    const SCREEN_NAME = 'shelf-move';

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

        // 返品棚取得
        $ShelfNumber = new ShelfNumber();
        $shelfList = $ShelfNumber->getList([]);
        $returnShelfList = collect($shelfList)->where('shelf_kind', config('const.shelfKind.return'))->pluck('id')->toArray();

        return view('Stock.shelf-move')
            ->with('isEditable', $isEditable)
            ->with('returnShelfList', json_encode($returnShelfList))
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
            $QrDetail = new QrDetail();
            $list = $QrDetail->getShelfMove($params['qr_code']);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }

}
