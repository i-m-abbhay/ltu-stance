<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use App\Models\Choice;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 選択肢一覧
 */
class ChoiceListController extends Controller
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

        $cNameList = (new Choice)->getNameList();
        $cKeywordList = (new Choice)->getKeywordList();

        return view('Choice.choice-list')
                ->with('isEditable', $isEditable)
                ->with('cNameList', $cNameList)
                ->with('cKeywordList', $cKeywordList)
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
            $Choice = new Choice();
            $cKeywordList = $Choice->getKeywordList();
            for ($i = 0; $i < count($cKeywordList); $i++) {      
                $data = $Choice->getByKeyword($cKeywordList[$i]['choice_keyword'])->pluck('choice_name')->toArray();
                $cName = implode('／', $data);
                $cKeywordList[$i]['choice_name'] = $cName;
            }

            // キーワード 部分一致で返す
            if (!empty($params['choice_keyword'])) {
                $cKeywordList = collect($cKeywordList)->filter(function ($item) use ($params) {
                    return false !== stristr($item->choice_keyword, $params['choice_keyword']);
                });
            }

            // 選択肢名 部分一致で返す
            if (!empty($params['choice_name'])) {
                $cKeywordList = collect($cKeywordList)->filter(function ($item) use ($params) {
                    return false !== stristr($item->choice_name, $params['choice_name']);
                });
            }
            // filterで乱れた添字振り直し
            $list = array_values($cKeywordList->toArray());   
            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
}