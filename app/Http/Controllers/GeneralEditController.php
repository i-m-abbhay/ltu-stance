<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Models\General;
use Storage;


/**
 * 共通名称マスタ編集
 */
class GeneralEditController extends Controller
{
    const SCREEN_NAME = 'general-edit';

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
     * @param Request $request
     * @param $id 商品ID
     * @return type
     */
    public function index(Request $request, $caegory_code = null)
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');
        $General = new General();
        $LockManage = new LockManage();
        $lockData = null;
        $categoryList = null;
        $categoryData = null;
        $id = null;


        try {
            if (is_null($caegory_code)) {
                throw new NotFoundHttpException();
            } else {
                // 編集
                // データ取得
                $categoryList = $General->getListByCategoryCode($caegory_code);

                $id = collect($categoryList)->min('id');
                $categoryData = collect($categoryList)->where('id', $id)->first();


                // 編集権限がある場合
                if ($isEditable === config('const.authority.has')) {
                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                    $keys = array($id);

                    DB::beginTransaction();
                    $lockCnt = 0;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // GETパラメータからモード取得
                        $mode = $request->query(config('const.query.mode'));
                        if (!$isLocked && $mode == config('const.mode.edit')) {
                            // 編集モードかつ、ロックされていない場合はロック取得
                            $lockDt = Carbon::now();
                            $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $keys[$i]);
                        }
                        // ロックデータ取得
                        $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                        if (!is_null($lockData) 
                            && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                            && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                                $lockCnt++;
                        }
                    }
                    if (count($tableNameList) === $lockCnt) {
                        $isOwnLock = config('const.flg.on');
                        DB::commit();
                    } else {
                        DB::rollBack();
                    }
                }
            }

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('General.general-edit') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('categoryData', $categoryData)
                ->with('categoryList', $categoryList)
                ;
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;
        $General = new General();
    
        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->request->all();

            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }

            // バリデーションチェック
            $this->isValid($request);
        }
        
        try{
            DB::beginTransaction();
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;
            $delLock = false;
            if ($newFlg) {
                // 登録
                $General->add($params);
                
                $delLock = true;
                $saveResult = true;
            } else {
                // 更新
                $saveResult = $General->updateById($params);
            }
            // ロック解放
            $keys = array($params['category_id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

            if ($saveResult && $delLock) {
                DB::commit();
                $resultSts = true;
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
     * バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request)
    {
        $this->validate($request, [
            'category_code' => 'required',
            'category_name' => 'required',
            'value_text_1' => 'max:20',
            'value_text_2' => 'max:20',
            'value_text_3' => 'max:20',
            'value_num_1' => 'nullable|numeric',
            'value_num_2' => 'nullable|numeric',
            'value_num_3' => 'nullable|numeric',
            // 重複チェック　除外：自身のID　条件：同一分類の中で表示順は単一
            'display_order' => 'required|numeric|min:0|unique:m_general,display_order,'. $request->request->get('id') .',id,category_code,'. $request->request->get('category_code'),
        ]);

    }
}