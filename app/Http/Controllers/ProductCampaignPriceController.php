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
use App\Models\ProductCampaignPrice;
use Storage;


/**
 * キャンペーン価格設定
 */
class ProductCampaignPriceController extends Controller
{
    const SCREEN_NAME = 'product-campaign-price';

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
        $ProductCampaignPrice = new ProductCampaignPrice();
        $General = new General();
        $LockManage = new LockManage();
        $lockData = null;

        try {
            if (is_null($id)) {
                // IDなし
                throw new \Exception();
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $productData = $Product->getById($id);
                $campaignList = $ProductCampaignPrice->getByProductId($id);

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

        return view('Product.product-campaign-price') 
                ->with('isEditable', $isEditable)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                ->with('productData', $productData)
                ->with('campaignList', $campaignList)
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
        $ProductCampaignPrice = new ProductCampaignPrice();
    
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

            // 日付バリデーション
            $dateResult = true;
            $campaignList = (new ProductCampaignPrice())->getByProductId($params['product_id']);
            $startDate = $params['start_date'];
            $endDate = $params['end_date'];
            $kbn = $params['cost_sales_kbn'];
            $id = $params['id'];
            $now = date('Y/m/d');
            $message = '';

            if ($id != null && $startDate < $now && $endDate < $now) {
                $dateResult = true;
            } else {
                // 開始日が終了日よりも前か
                if ($startDate > $endDate) {
                    $dateResult = false;
                    $message = config('message.error.campaign_price.error_date');
                }

                // 他レコードと適用日が被っているか
                $filtered = collect($campaignList)->filter(function($row, $key) use($id, $kbn, $startDate, $endDate) {
                    $rtn = false;

                    if ($row->start_date <= $startDate && $row->end_date >= $startDate) {
                        $rtn = true;
                    }

                    if ($row->start_date <= $endDate && $row->end_date >= $endDate) {
                        $rtn = true;
                    }

                    // 自レコードは返さない
                    if ($row->id == $id) {
                        $rtn = false;
                    }

                    if ($row->cost_sales_kbn != $kbn) {
                        $rtn = false;
                    }

                    return $rtn;
                });

                // 1件でも被っていれば
                if (count($filtered) > 0) {
                    $dateResult = false;
                    $message = config('message.error.campaign_price.existDate');
                }

                if (!$dateResult) {
                    return \Response::json(['result' => $resultSts, 'message' => $message]);
                }
            }
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
                $ProductCampaignPrice->add($params);

                $saveResult = true;
            } else {
                // 更新
                $saveResult = $ProductCampaignPrice->updateById($params);                
            }
            // ロック解放
            $keys = array($params['product_id']);
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
        return \Response::json(['result' => $resultSts]);
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
            $ProductCampaignPrice = new ProductCampaignPrice();

            // 削除
            $deleteResult = $ProductCampaignPrice->deleteById($params['id']);

            // ロック解放
            $keys = array($params['product_id']);
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
            'product_id' => 'required',
            'price' => 'required|numeric|min:0',
            'cost_sales_kbn' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
        ]);

    }
}