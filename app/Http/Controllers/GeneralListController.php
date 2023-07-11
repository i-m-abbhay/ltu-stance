<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Choice;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\General;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 共通名称マスタ一覧
 */
class GeneralListController extends Controller
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

        // 一覧取得
        $categoryNameList = (new General())->getComboList();
        

        return view('General.general-list')
                ->with('isEditable', $isEditable)
                ->with('categoryNameList', $categoryNameList)
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
            $list = (new General())->getGeneralList($params);
            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}