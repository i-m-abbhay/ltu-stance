<?php
namespace App\Http\Controllers;

use Validator;
use Illuminate\Http\Request;
use DB;
use Session;
use Illuminate\Support\Facades\Log;
use Auth;
use Carbon\Carbon;
use App\Models\Authority;
use App\Models\LockManage;
use App\Models\Product;
use App\Models\ProductNickname;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;


class ProductNicknameController extends Controller
{
    const SCREEN_NAME = 'product-nickname';
    
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     *  初期表示
     *  @params $id ID
     *  @return type
     */
    public function index(Request $request, $id = null)
    {
        
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $ProductNickname = new ProductNickname();
        $nicknameData = null;
        $LockManage = new LockManage();
        $lockData = null;

        $Product = new Product();
        $ProductNickName = new ProductNickname();

        // $productList = $Product->getComboList();

        $parentProduct = $Product->getById($id);
        $isExist = $ProductNickName->isExistNickname($id);

        try{
            if (!$isExist) {
                // 新規
                $nicknameList = $ProductNickname;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $nicknameList = $ProductNickname->getByProductId($id);
            }
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
                        && $lockData->screen_name == self::SCREEN_NAME && $lockData->table_name == $tableName
                        && $lockData->key_id == $keys[$i] && $lockData->lock_user_id == Auth::user()->id) {
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

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Product.product-nickname')
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('parentProduct', $parentProduct)
                ->with('nicknameList', $nicknameList)
                // ->with('productList', $productList)
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

        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->request->all();
            $nameList = json_decode($params['name_list'], true);

            // バリデーションチェック
            $this->isValid($request);
        }
        
        $ProductNickName = new ProductNickname();
        DB::beginTransaction();
        try {
            $delLock = false;     
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }

            foreach ($nameList as $key => $val) {
                $nameList[$key]['product_id'] = $params['product_id'];
            
                $newFlg = false;
                if (empty($nameList[$key]['id'])) {
                    $newFlg = true;
                }

                $saveResult = false;            
                if ($newFlg) {
                    // 登録
                    $saveResult = $ProductNickName->add($nameList[$key]);
                    $delLock = true;
                } else {
                    if ($nameList[$key]['del_flg']) {
                        // 削除
                        $saveResult = $ProductNickName->deleteById($nameList[$key]['id']);
                    } else {
                        // 更新
                        $saveResult = $ProductNickName->updateById($nameList[$key]);
                    }                    
                }
            }
            // ロック解放
            $keys = array($params['product_id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            
            if ($saveResult) {
                // && $delLock
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
     * 削除
     * @param \App\Http\Controllers\Request $request
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
            $ProductNickName = new ProductNickName();

            // 削除
            $deleteResult = $ProductNickName->deleteByProductId($params['id']);

            // ロック解放
            $keys = array($params['id']);
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

            if ($deleteResult && $delLock) {
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
     *
     * @param \App\Http\Controllers\Request $request
     * @return boolean
     */
    public function isValid(Request $request) 
    {   
        
        $this->validate($request, [
            'product_id' => 'required',
        ]);
    }  
}