<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Item;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Construction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\SpecDefault;
use App\Models\SpecItemHeader;
use App\Models\SpecItemDetail;

/**
 * 仕様項目一覧
 */
class SpecItemListController extends Controller
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

        $specList = (new SpecItemHeader)->getComboList();
        $constList = (new Construction())->getQuoteRequestFlgList();

        return view('Item.spec-item-list')
                ->with('isEditable', $isEditable)
                ->with('specList', $specList)
                ->with('constList', $constList)
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
            // 一覧データ取得
            $list = (new SpecItemHeader)->getList($params);

            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}