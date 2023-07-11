<?php
namespace App\Http\Controllers;

use DB;
use Session;
use Auth;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\LockManage;
use App\Models\Product;
use App\Models\SupplierFile;

/**
 * 共通
 * 共通的なアクションメソッド用
 */
class CommonController extends Controller
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    public function index()
    {
    }

    /**
     * ロック
     * @param Request $request
     * @return type
     */
    public function lock(Request $request)
    {
        $result = array('status' => false, 'isLocked' => false, 'lockdata'=> null);
        $LockManage = new LockManage();

        // リクエストデータ取得
        $params = $request->request->all();

        DB::beginTransaction();
        try {
            if ($params['screen'] && $params['keys']) {
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.$params['screen']);
                // キー分割
                $keyArr = explode(config('const.joinKey'), $params['keys']);

                $lockDt = Carbon::now();
                $isLocked = false;
                $hasLock = false;
                foreach($tableNameList as $i => $tableName) {
                    // ロック確認
                    $isLocked = $LockManage->isLocked($tableName, intval($keyArr[$i]));
                    if ($isLocked) {
                        $result['isLocked'] = true;
                        break;
                    }
                    // ロック取得
                    $hasLock = $LockManage->gainLock($lockDt, $params['screen'], $tableName, intval($keyArr[$i]));
                    if (!$hasLock) {
                        break;
                    }
                }

                if ($isLocked) {
                    DB::commit();
                    $result['status'] = true;
                } else {
                    // すべてのロックが取得できたらコミット、それ以外はロールバック
                    if ($hasLock) {
                        DB::commit();
                        // ロックデータ取得（最後の1テーブルのみ）
                        $result['lockdata'] = $LockManage->getLockData($tableName, $params['keys']);
                        $result['status'] = true;
                    } else {
                        DB::rollBack();
                        $result['status'] = false;
                        Session::flash('flash_error', config('message.error.getlock'));
                    }
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.getlock'));
        }
        return \Response::json($result);
    }

    /**
     * ロック確認
     * @param Request $request
     * @return type
     */
    public function isLocked(Request $request)
    {
        $isLocked = false;
        $LockManage = new LockManage();

        // リクエストデータ取得
        $params = $request->request->all();

        try {
            if ($params['screen'] && $params['keys']) {
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.$params['screen']);
                // キー分割
                $keyArr = explode(config('const.joinKey'), $params['keys']);

                foreach($tableNameList as $i => $tableName) {
                    // ロック確認
                    $isLocked = $LockManage->isLocked($tableName, intval($keyArr[$i]));
                    if ($isLocked) {
                        break;
                    }
                }
            }
        } catch (\Exception $e) {
            $isLocked = false;
            Log::error($e);
        }

        return \Response::json($isLocked);
    }

    /**
     * ロック獲得
     * @param Request $request
     */
    public function gainLock(Request $request)
    {
        $result = array('status' => false, 'lockdata' => null);
        $LockManage = new LockManage();

        // リクエストデータ取得
        $params = $request->request->all();

        DB::beginTransaction();
        try {
            $hasLock = false;
            $lockTblNm = '';
            $lockKeyId = 0;
            if ($params['screen'] && $params['keys']) {
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.$params['screen']);
                // キー分割
                $keyArr = explode(config('const.joinKey'), $params['keys']);

                $lockDt = Carbon::now();
                foreach($tableNameList as $i => $tableName) {
                    // ロック獲得
                    $hasLock = $LockManage->gainLock($lockDt, $params['screen'], $tableName, intval($keyArr[$i]), true);
                    $lockTblNm = $tableName;
                    $lockKeyId = $keyArr[$i];
                    if (!$hasLock) {
                        break;
                    }
                }
            }

            // すべてのロックが獲得できたらコミット、それ以外はロールバック
            if ($hasLock) {
                DB::commit();
                // ロックデータ取得（最後の1テーブルのみ）
                $result['lockdata'] = $LockManage->getLockData($lockTblNm, $lockKeyId);
                $result['status'] = true;
            } else {
                DB::rollBack();
                $result['status'] = false;
                Session::flash('flash_error', config('message.error.getlock'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return \Response::json($result);
    }

    /**
     * ロック解除
     * @param Request $request
     */
    public function releaseLock(Request $request)
    {
        $result = false;

        // リクエストデータ取得
        $params = $request->request->all();

        DB::beginTransaction();
        try {
            // ロックデータ削除
            $keys = explode(config('const.joinKey'), $params['keys']);
            $LockManage = new LockManage();
            $LockManage->deleteLockInfo($params['screen'], $keys, Auth::user()->id);

            DB::commit();
            $result = true;
        } catch (\Exception $e) {
            DB::rollBack();
            $result = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return \Response::json($result);
    }

    /**
     * メーカー仕入先価格ファイルを表示（PDF）
     * 
     * @param Request $request
     */
    public function openSupplierFile($supplier_id)
    {
        $SupplierFile = new SupplierFile();

        try {
            $fileName = $SupplierFile->getBySupplierId($supplier_id)->file_name;
            $file = Storage::get(config('const.uploadPath.supplier_file'). $supplier_id. '/'. $fileName);
            return response($file, 200)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $fileName . '"');
        } catch (\Exception $e) {
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
    }

    /**
     * 商品1件の情報を取得する
     * @param $request
     * @return type
     */
    public function getProductInfo(Request $request){
        $result = [
            'product'               => null,
            'costProductPriceList'  => [],
            'salesProductPriceList' => [],
        ];
        $params = $request->request->all();
        $Product = new Product();
        $result['product'] = $Product->getProductInfo($params['product_id']);
        
        $priceList = $Product->getPriceDataList($params['customer_id'], $params['product_id']);
        $result['costProductPriceList'] = $priceList['costProductPriceList'];
        $result['salesProductPriceList'] = $priceList['salesProductPriceList'];

        return \Response::json($result);
    }

    /**
     * 商品1件の価格情報を取得する
     * @param $request
     * @return type
     */
    public function getProductUnitPriceList(Request $request){
        $result = [
            'costProductPriceList'  => [],
            'salesProductPriceList' => [],
        ];
        $params = $request->request->all();
        $Product = new Product();
        $priceList = $Product->getPriceDataList($params['customer_id'], $params['product_id']);
        $result['costProductPriceList'] = $priceList['costProductPriceList'];
        $result['salesProductPriceList'] = $priceList['salesProductPriceList'];

        return \Response::json($result);
    }

    /**
     * 商品コードからオートコンプリートの選択肢の配列を取得する
     * @param $request
     * @return type
     */
    public function getProductCodeComboList(Request $request){
        $params = $request->request->all();
        $Product = new Product();
        $result = $Product->getProductCodeComboList($params['text'], config('const.flg.off'), config('const.flg.off'));
        
        return \Response::json($result);
    }

    /**
     * 商品コードからオートコンプリートの選択肢の配列を取得する(自動登録を含む)
     * @param $request
     * @return type
     */
    public function getProductCodeComboListIncludeAutoFlg(Request $request){
        $params = $request->request->all();
        $Product = new Product();
        $result = $Product->getProductCodeComboList($params['text'], config('const.flg.off'), null);
        
        return \Response::json($result);
    }

    /**
     * 商品コードからオートコンプリートの選択肢の配列を取得する(自動登録と仮登録を含む)
     * @param $request
     * @return type
     */
    public function getProductCodeComboListAll(Request $request){
        $params = $request->request->all();
        $Product = new Product();
        $result = $Product->getProductCodeComboList($params['text'], null, null);
        
        return \Response::json($result);
    }

    /**
     * 商品名からオートコンプリートの選択肢の配列を取得する
     * @param $request
     * @return type
     */
    public function getProductNameComboList(Request $request){
        $params = $request->request->all();
        $Product = new Product();
        $result = $Product->getProductNameComboList($params['text'], config('const.flg.off'), config('const.flg.off'));
        
        return \Response::json($result);
    }

    /**
     * 商品名からオートコンプリートの選択肢の配列を取得する(自動登録を含む)
     * @param $request
     * @return type
     */
    public function getProductNameComboListludeAutoFlg(Request $request){
        $params = $request->request->all();
        $Product = new Product();
        $result = $Product->getProductNameComboList($params['text'], config('const.flg.off'), null);
        
        return \Response::json($result);
    }
}