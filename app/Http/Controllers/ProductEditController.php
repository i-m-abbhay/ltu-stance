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
use App\Models\Product;
use App\Models\General;
use App\Models\Supplier;
use App\Models\Construction;
use App\Models\ClassBig;
use App\Models\ClassMiddle;
use App\Models\ClassSmall;
use App\Models\ConstructionBig;
use App\Models\ProductstockShelf;
use App\Models\QuoteDetail;
use Storage;


/**
 * 商品編集
 */
class ProductEditController extends Controller
{
    const SCREEN_NAME = 'product-edit';

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

        $Product = new Product();
        $General = new General();
        $Supplier = new Supplier();
        $Construction = new Construction();
        $ClassBig = new ClassBig();
        $ClassMid = new ClassMiddle();
        $ClassSmall = new ClassSmall();
        $ConstructionBig = new ConstructionBig();
        $QuoteDetail = new QuoteDetail();
        $ProductstockShelf = new ProductstockShelf();
        $constBigData = $ConstructionBig->getDataList();
        $constData = $Construction->getComboList();
        $classBigData = $ClassBig->getProductClass(config('const.flg.on'));
        $classMidData = $ClassMid->getQreqMiddleList($classBigData);
        $classSmallData = $ClassSmall->getComboList();
        // $productList = $Product->getComboList();

        $productData = null;
        $supplierData = null;
        $LockManage = new LockManage();
        $lockData = null;


        // $constData = $Construction->getComboList();
        $taxtypeData = $General->getCategoryList(config('const.general.taxtype'));
        $taxkbnData = $General->getCategoryList(config('const.general.taxkbn'));
        $woodData = $General->getCategoryList(config('const.general.wood'));
        $gradeData = $General->getCategoryList(config('const.general.grade'));
        // 商品コード無し取得
        $noProductCode = $General->getCategoryList(config('const.general.noproductcode'))->first();
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');

        $supplierData = $Supplier->getBySupplierKbn($kbn);

        try {
            if (is_null($id)) {
                // 新規
                $productData = $Product;
                Common::initModelProperty($productData);
                $productData['is_exist_quote'] = false;
                $productData['is_exist_stock'] = false;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $productData = $Product->getById($id);
                $productData['is_exist_quote'] = false;
                $productData['is_exist_stock'] = false;
                if (!empty($productData['new_product_id'])) {
                    $newProduct = $Product->getById($productData['new_product_id']);
                    $productData['new_product_text'] = $newProduct['product_code'];
                }
                // 見積で使用されているか
                $quoteData = $QuoteDetail->getDataByProduct($id);
                $productData['is_exist_quote'] = count($quoteData) > 0;

                // 在庫に存在するか
                $productData['is_exist_stock'] = $ProductstockShelf->isExistByProductId($id);

                // 画像データ取得
                $path = config('const.uploadPath.product');
                $files = Storage::files($path);
                if(isset($files)){
                    foreach($files as $file){
                        $fileInfo = pathinfo($path.$file);
                        if($fileInfo['basename'] == $productData['product_image']){
                            if($fileInfo['extension'] == 'png' || $fileInfo['extension'] == 'PNG'){
                                $productData['product_image'] = config('const.encode.png').base64_encode(Storage::get($file));
                            }
                            if($fileInfo['extension'] == 'jpeg' || $fileInfo['extension'] == 'jpg' || $fileInfo['extension'] == 'JPEG' || $fileInfo['extension'] == 'JPG'){
                                $productData['product_image'] = config('const.encode.jpeg').base64_encode(Storage::get($file));
                            }
                        }
                    }           
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

        return view('Product.product-edit') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('productData', $productData)
                ->with('taxtypeData', $taxtypeData)
                ->with('taxkbnData', $taxkbnData)
                ->with('supplierData', $supplierData)
                ->with('constBigData', $constBigData)
                ->with('constData', $constData)
                ->with('classBigData', $classBigData)
                ->with('classMidData', $classMidData)
                ->with('classSmallData', $classSmallData)
                // ->with('productList', $productList)
                ->with('woodData', $woodData)
                ->with('gradeData', $gradeData)
                ->with('noProductCode',   $noProductCode)
                ;
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        // $resultSts = false;
        $result = array('status' => false, 'message' => '');
        $Product = new Product();
    
        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->request->all();

            // フロント側のshowErrMsgにて、err[length]がエラーとなるためtree_lengthに格納している。
            $params['length'] = $params['tree_length'];

            $newFlg = false;
            if (empty($params['id'])) {
                $newFlg = true;
            }

            // 商品画像の有無
            $hasImage = $request->hasFile('product_image');
            
            if($hasImage){
                $uploadPath = config('const.uploadPath.product');
                $this->imageValid($request);
                // ファイル名取得
                $fileName = $request->file('product_image')->getClientOriginalName();
                $params['product_image'] = $fileName;
            }else {
                $params['product_image'] = '';
            }
            // バリデーションチェック
            $this->isValid($request);

            $ClassBig = new ClassBig();
            $classBigData = null;
            if (!empty($params['class_big_id'])) {
                $classBigData = $ClassBig->getProductClass();
                $classBigData = collect($classBigData)->where('class_big_id', $params['class_big_id'])->first();
            }
            // 木材バリデーション
            if ($classBigData['format_kbn'] == config('const.flg.on')) {
                $this->isValidWood($request);
            }
        }
        
        try{
            DB::beginTransaction();
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            
            // 本登録商品との重複チェック
            
            $isExist = $Product->isExistRegistered($params['product_code'], $params['maker_id'], $params['id']);
            if ($isExist) {
                $result['message'] = config('message.error.product.exist_product_code');
                throw new \Exception();
            }

            $saveResult = false;
            $delLock = false;
            if ($newFlg) {
                // 登録
                if($hasImage){
                    // アップロード
                    $request->file('product_image')->storeAs($uploadPath, $fileName);
                }
                $Product->add($params);
                
                $delLock = true;
                $saveResult = true;
            } else {
                if($hasImage){
                    // 更新元の商品情報を取得 (前回アップロードしたファイル削除に使用)
                    $productInfo = $Product->getById($params['id']);                    

                    // 同商品のアップロードファイル削除
                    $files = Storage::files($uploadPath);
                    foreach($files as $file) {
                        if ($file != $productInfo['product_image']){
                            Storage::delete($uploadPath.$file);
                        }
                    }
                    // アップロード
                    $request->file('product_image')->storeAs($uploadPath, $fileName);
                } else {
                    $data = $Product->getById($params['id']);
                    $params['product_image'] = $data['product_image'];
                }

                // 更新
                $saveResult = $Product->updateById($params);

                // ロック解放
                $keys = array($params['id']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            }
            
            if ($saveResult && $delLock) {
                DB::commit();
                $result['status'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if($result['message'] === ''){
                $result['message'] = config('message.error.error');
            }
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
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
            $Product = new Product();

            //適用終了日セット
            $saveResult = $Product->updateEndDate($params);

            // 削除
            $deleteResult = $Product->deleteById($params['id']);

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
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request)
    {
        $this->validate($request, [
            'product_code' => 'required|max:255|regex:'.config('const.productCodeRegex'),
            // 'product_code' => 'required|max:255|regex:'.config('const.productCodeRegex').'|unique:m_product,product_code,'. $request->request->get('id') .',id,product_code,'. $request->request->get('product_code'). ',maker_id,'. $request->request->get('maker_id') .',del_flg,'. config('const.flg.off') .',draft_flg,'. config('const.flg.off'),
            'product_name' => 'required',
            'product_short_name' => 'max:30',
            'class_big_id' => 'required|integer',
            'class_middle_id' => 'numeric|nullable',
            'construction_id_1'	 => 'required|integer',
            'construction_id_2' => 'numeric|nullable',
            'construction_id_3' => 'numeric|nullable',
            'class_small_id_1' => 'numeric|nullable',
            'class_small_id_2' => 'numeric|nullable',
            'class_small_id_3' => 'numeric|nullable',
            'model' => 'max:255',
            'maker_id' => 'required|integer',
            'weight' => 'nullable|numeric|between:0.000,99999.999',
            'price' => 'required|numeric|digits_between:0,9',
            'open_price_flg' => 'required|integer',	
            'min_quantity' => 'required|numeric|between:0.00,9999999.99',
            'stock_unit' => 'max:30',
            'quantity_per_case' => 'numeric|nullable|between:0.00,9999999.99',
            'lead_time' => 'numeric|nullable',
            'order_lot_quantity' => 'required|numeric|between:0.00,9999999.99',			
            'purchase_makeup_rate' => 'numeric|nullable|between:0.00,100.00',
            'normal_purchase_price' => 'numeric|nullable|between:0,999999999',
            'unit' => 'max:30',
            'sales_makeup_rate' => 'numeric|nullable|between:0.00,100.00',		
            'normal_sales_price' => 'numeric|nullable|between:0,999999999',
            'normal_gross_profit_rate' => 'numeric|nullable|between:0.00,100.00',			
            'tax_type' => 'numeric|nullable',
            'start_date' => 'date|nullable',
            'end_date' => 'date|nullable',
            'warranty_term' => 'numeric|nullable',		
            'housing_history_transfer_kbn'  => 'numeric|nullable',
            'new_product_id' => 'numeric|nullable|min:0',
            'intangible_flg' => 'numeric|required',
        ]);

    }

    /**
     * 木材バリデーションチェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValidWood(Request $request)
    {
        $this->validate($request, [
            'tree_species' => 'required',
            'grade' => 'required',
            'tree_length' => 'required|integer|digits_between:0,10|between:0,2147483647',	
            'thickness' => 'required|integer|digits_between:0,10|between:0,2147483647',		
            'width' => 'required|integer|digits_between:0,10|between:0,2147483647',	
        ]);
    }

    /**
     * 画像バリデーションチェック
     *
     * @param Request $request
     * @return void
     */
    protected function imageValid(Request $request) 
    {
        $this->validate($request, [
            'product_image' => 'required|file|image|mimes:jpeg,png,jpg',
        ]);
    }
}