<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Base;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\ClassBig;
use App\Models\ClassMiddle;
use App\Models\ClassSmall;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 中分類一覧
 */
class ClassMiddleListController extends Controller
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

        // リスト取得
        $ClassMiddle = new ClassMiddle();
        $ClassBig = new ClassBig();
        
        $classMiddleList = $ClassMiddle->getComboList();
        $classBigList = $ClassBig->getComboList();

        return view('ClassMiddle.class-middle-list')
                ->with('isEditable', $isEditable)
                ->with('classMiddleList', $classMiddleList)
                ->with('classBigList', $classBigList)
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
            $ClassMiddle = new ClassMiddle();
            $list = $ClassMiddle->getList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}