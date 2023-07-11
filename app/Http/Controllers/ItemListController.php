<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\General;

/**
 * 項目一覧
 */
class ItemListController extends Controller
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
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        $itemList = (new Item)->getNameList();
        $code = config('const.general.itemtype');
        $typeList = (new General)->getCategoryList($code);

        return view('Item.item-list')
                ->with('isEditable', $isEditable)
                ->with('itemList', $itemList)
                ->with('typeList', $typeList)
                ;
    }
    
    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            if (!empty($params['item_type'])) {
                $params['item_type'] = array_map('intval', explode(',', $params['item_type']));
            }
            $type = config('const.general.itemtype');
            
            // 一覧データ取得
            $list = (new Item)->getList($params, $type);
            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}