<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Choice;
use App\Models\General;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DB;
use Session;
use Storage;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\SpecItemDetail;

/**
 * 項目編集
 */
class ItemEditController extends Controller
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
    public function index(Request $request, $id = null)
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        $Item = new Item();
        $General = new General();
        $category = config('const.general.itemtype');
        $typeData = $General->getCategoryList($category);
        $keywordList = (new Choice)->getKeywordList();     
        
        $isUsed = config('const.flg.off');

        try {
            if (is_null($id)) {
                // 新規
                $itemData = $Item;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $itemData = $Item->getById($id);

                foreach($itemData as $key => $val) {
                    // 仕様項目に使われているかチェック
                    $specItems = (new SpecItemDetail())->getByItemId($itemData['id'])->first();
                    if(!empty($specItems)) {
                        $isUsed = config('const.flg.on');
                    }
                }
               
                
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }        

        return view('Item.item-edit') 
                ->with('isEditable', $isEditable)
                ->with('itemData', $itemData)
                ->with('typeData', $typeData)
                ->with('keywordList', $keywordList)
                ->with('isUsed', $isUsed)
                ;
    }

    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;
            
            // リクエストデータ取得
            $params = $request->request->all();

            // バリデーションチェック
            $this->isValid($request);
        }
        $Item = new Item();

        $newFlg = false;
        if (empty($params['id'])) {
            $newFlg = true;
        }

        DB::beginTransaction();        
        try {
            if (!$hasEditAuth) {
                // 編集権限なし
                throw new \Exception();
            }

            $saveResult = false;
            if ($newFlg) {  
                // 登録
                $itemId = $Item->add($params);
                $saveResult = true;                    
            } else {
                // 更新
                $saveResult = $Item->updateById($params);
            }           
            
            if ($saveResult) {
                DB::commit();
                $resultSts= true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * 削除
     * @param Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            // 編集権限チェック
            if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
                throw new \Exception();
            }

            $Item = new Item();

            // 削除
            $deleteResult = $Item->deleteById($params['id']);

            if ($deleteResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.delete'));
            } else {
                throw new \Exception(config('message.error.delete'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }

    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid($request)
    {
        $this->validate($request, [
            'item_name' => 'required|max:50',
            'item_front_label' => 'max:50',
            'item_back_label' => 'max:50',
        ]);
    }

}