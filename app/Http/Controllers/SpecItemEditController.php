<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Choice;
use App\Models\Construction;
use App\Models\General;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DB;
use Session;
use Storage;
use Carbon\Carbon;
use App\Models\Item;
use App\Models\QuoteRequestDetail;
use App\Models\SpecDefault;
use App\Models\SpecItemHeader;
use App\Models\SpecItemDetail;
use Illuminate\Validation\Rule;

/**
 * 仕様項目編集
 */
class SpecItemEditController extends Controller
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

        // 一覧データ取得
        $Item = new Item();
        $itemList = $Item->getAllList();       
        $constList = (new Construction)->getQuoteRequestFlgList();
        $itemNameList = $Item->getNameList();
        $category = config('const.general.itemtype');
        $typeList = (new General)->getCategoryList($category);
        
        $SpecItemHeader = new SpecItemHeader();
        $SpecItemDetail = new SpecItemDetail();

        try {
            if (is_null($id)) {
                // 新規
                $specItem = $SpecItemHeader;
                $specDetail = $SpecItemDetail;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $specItem = $SpecItemHeader->getById($id);
                $specDetail = $SpecItemDetail->getByHeaderId($id);              
                
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }        

        return view('Item.spec-item-edit') 
                ->with('isEditable', $isEditable)
                ->with('specItem', $specItem)
                ->with('specDetail', $specDetail)
                ->with('itemList', $itemList)
                ->with('constList', $constList)
                ->with('itemNameList', $itemNameList)
                ->with('typeList', $typeList)
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
            $specItems = $params['spec_item'];
            $specItems = json_decode($specItems, true);

            // JSON化した明細整形
            foreach(json_decode($params['spec_item'], true) as $key => $val) {
                if (!empty($val['detail_id'])) {
                    $specItems[$key]['id'] = $val['detail_id'];
                } else {
                    $specItems[$key]['id'] = null;
                }
                $specItems[$key]['item_id'] = $val['id'];
                $specItems[$key]['spec_item_header_id'] = $params['id'];
            } 
            if (!empty($params['delete_item'])) {
                $delItems = $params['delete_item'];
                $delItems = json_decode($delItems, true);
            }

            // バリデーションチェック
            $this->isValid($request);
        }
        $newFlg = false;
        if (empty($params['id'])) {
            $newFlg = true;
        } else {
            $isExist = (new QuoteRequestDetail())->isExistSpecItem($params['id']);
            if ($isExist) {
                // 見積依頼に使用されている
                $hoge = config('message.error.spec_item.used');
                Session::flash('flash_error', config('message.error.spec_item.used'));
                return \Response::json($resultSts);
            }
        }

        $SpecItemHeader = new SpecItemHeader();
        $SpecItemDetail = new SpecItemDetail();

        DB::beginTransaction();        
        try {
            if (!$hasEditAuth) {
                // 編集権限なし
                throw new \Exception();
            }
            // ヘッダー保存
            $saveResult = false;
            if ($newFlg) {  
                // 登録
                $headerId = $SpecItemHeader->add($params);      
                $saveResult = true;                           
            } else {
                // 更新
                $saveResult = $SpecItemHeader->updateById($params);                
            }
            // 仕様項目明細 保存
            foreach ($specItems as $key => $val) {
                $newChildFlg = false;
                if ($newFlg) {
                    // ヘッダID付与
                    $specItems[$key]['spec_item_header_id'] = $headerId;
                }
                if (empty($specItems[$key]['id'])) {
                    $newChildFlg = true;
                }

                if ($newChildFlg) {
                    // 新規
                    $result = $SpecItemDetail->add($specItems[$key]);
                    $saveResult = true;
                } else {
                    // 更新
                    $saveResult = $SpecItemDetail->updateById($specItems[$key]);
                }
            }
            // 仕様項目明細 削除
            if (!empty($delItems)) {
                foreach ($delItems as $key => $val) {
                    $result = $SpecItemDetail->physicalDeleteById($val);
                }
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
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
            $resultSts = false;
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

            $SpecItemHeader = new SpecItemHeader();
            $SpecItemDetail = new SpecItemDetail();

            // 削除
            $deleteResult = $SpecItemHeader->deleteById($params['id']);
            $deleteResult = $SpecItemDetail->deleteByHeaderId($params['id']);

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
            // 一覧データ取得
            $category = config('const.general.itemtype');
            $list = (new Item)->getList($params, $category);

            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }

    /**
     * バリデーションチェック
     * @param Request $request
     * @return type
     */
    private function isValid($request)
    {
        $this->validate($request, [
            'quote_request_kbn' => 'required',
            // 見積依頼区分、開始日で同じデータがあればエラー
            'start_date' => ['required', Rule::unique('t_spec_item_header', 'start_date')->ignore($request->id)->where(function($query) use($request) {
                $query->where('quote_request_kbn', $request->quote_request_kbn)
                      ->where('del_flg', config('const.flg.off'))
                      ;
            })]
        ]);
    }

}