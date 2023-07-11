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
use App\Models\ProductMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * 案件検索
 */
class AcceptingReturnsMatterSearchController extends Controller
{
    const SCREEN_NAME = 'accepting-returns-matter-search';

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
        $Matter = new Matter();
        $Construction = new Construction();
        $Product = new Product();

        try {
            //リスト取得
            $matterList = $Matter->getComboList();
            $constructionList = $Construction->getComboList();

            return view('Returns.accepting-returns-matter-search')
            ->with('isEditable', $isEditable)
            ->with('matterList', $matterList)
            ->with('constructionList', $constructionList);
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
            $Delivery = new Delivery();
            $list = $Delivery->getListAcceptingReturns($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }
}
