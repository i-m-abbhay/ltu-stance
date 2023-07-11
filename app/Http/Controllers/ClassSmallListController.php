<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Base;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\ClassSmall;
use App\Models\Construction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 工程一覧
 */
class ClassSmallListController extends Controller
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

        $ClassSmall = new ClassSmall();
        $classSmallList = $ClassSmall->getComboList();
        $Construction = new Construction();
        $constructionList = $Construction->getComboList(false);

        return view('ClassSmall.class-small-list')
                ->with('isEditable', $isEditable)
                ->with('classSmallList', $classSmallList)
                ->with('constructionList', $constructionList)
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
            $ClassSmall = new ClassSmall();
            $list = $ClassSmall->getList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}