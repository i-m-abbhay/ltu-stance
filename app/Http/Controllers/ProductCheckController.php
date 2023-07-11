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
use App\Models\Matter;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Reserve;
use App\Models\ShipmentDetail;
use App\Models\UpdateHistory;
use Storage;


/**
 * 商品マスタチェック
 */
class ProductCheckController extends Controller
{
    const SCREEN_NAME = 'product-check';
    // 見積発注が存在しない場合のロック取得に使用
    const SCREEN_NAME_SELF = 'product-check-self';

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
     * @return type
     */
    public function index(Request $request)
    {
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // 権限チェック　※この画面は編集権限がないと開けない
        if ($isEditable === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }

        // // ログイン者がロックしているフラグ
        // $isOwnLock = config('const.flg.off');

        $Product = new Product();
        $LockManage = new LockManage();
        $lockData = null;
        $Matter = new Matter();
        $Construction = new Construction();
        $Order = new Order();
        // $OrderDetail = new OrderDetail();
        $Quote = new Quote();
        $Supplier = new Supplier();
        $ClassBig = new ClassBig();
        $ClassMiddle = new ClassMiddle();
        $ClassSmall = new ClassSmall();
        $General = new General();

        $id = [];
        try {
            // データ取得
            // 仮商品取得
            $productList = $Product->getDraftComboList();
            // メーカーリスト取得
            $kbn = [];
            $kbn[] = config('const.supplierMakerKbn.direct');
            $kbn[] = config('const.supplierMakerKbn.maker');
            $makerList = $Supplier->getBySupplierKbn($kbn);
            // 本登録商品リスト取得
            // $dlgProductList = $Product->getFormalComboList();
            // 工事区分リスト取得
            $constList = $Construction->getComboList();
            // 大分類リスト取得
            $classBigList = $ClassBig->getComboList();
            // 中分類リスト
            $classMidList = $ClassMiddle->getComboList();
            // 工程リスト
            $classSmallList = $ClassSmall->getComboList();
            // // 無形品リスト
            // $tangibleList = array(config('const.intangible_kbn.list'));
            // 樹種リスト
            $woodList = $General->getCategoryList(config('const.general.wood'));
            // 等級リスト
            $gradeList = $General->getCategoryList(config('const.general.grade'));
            // 税種別リスト
            $taxtypeList = $General->getCategoryList(config('const.general.taxtype'));
            // 商品コード無し取得
            $noProductCode = $General->getCategoryList(config('const.general.noproductcode'))->first();
            // 案件リスト取得
            $matterList = $Matter->getComboList();
            // 発注リスト取得
            $orderList = $Order->getComboList();
            // 絞り込み用のリスト取得
            // $orderDetailList = $OrderDetail->getList(array());
            $productIdList = $productList->pluck('id')->unique()->toArray();
            $orderDetailList =  $Quote->getMatterNoByProductIds($productIdList);
            // // メーカーリスト取得
            // $kbn = [];
            // $kbn[] = config('const.supplierMakerKbn.direct');
            // $kbn[] = config('const.supplierMakerKbn.maker');
            // $supplierList = $Supplier->getBySupplierKbn($kbn);

            $housingHistoryTransferKbn = array(['text' => 'しない', 'value' => 0], ['text' => 'する', 'value' => 1]);

            // $productData = collect($productList)->sortBy('created_at');
            // if (count($productData) == 0) {
            //     $productData = $Product;
            // } else {
                // $orderData = $OrderDetail->getMasterCheckList($productData->pluck('id')->toArray());

                // // 類似商品取得
                // $products = [];
                // // $products[] = $productData;
                // $list = [];
                // // 類似商品取得
                // foreach ($productData as $key => $val) {
                //     $products[] = $productData[$key];
                // }
                // foreach ($orderData as $key => $val) {
                //     $products[] = $orderData[$key];

                //     if (Common::nullorBlankToZero($productData[0]['maker_id']) == 0
                //         && $productData[0]['id'] == $orderData[$key]['product_id']) 
                //     {
                //         $productData[0]['maker_id'] = $orderData[$key]['maker_id'];
                //     }
                // }
                // foreach ($products as $key => $row) {
                //     $list = $this->getSimilarProduct($products[$key]);
                // }
                // // foreach ($orderData as $key => $val) {
                // //     $list = $this->getSimilarProduct($orderData[$key]);
                // // }
                // $simList = [];
                // $ids = [];
                // foreach ($list as $key => $val) {
                //     if (!empty($list[$key]['id'])) {
                //         $ids[] = (int)$list[$key]['id'];
                //     }
                //     if (!empty($list[$key]['product_id'])) {
                //         $ids[] = (int)$list[$key]['product_id'];
                //     }
                // }
                // $ids = collect($ids)->unique()->toArray();
                // $simList = $Product->getInId($ids);
                // // $simList = collect($simList)->toArray();

                // // 画面名から対象テーブルを取得
                // $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                // $keys = collect($orderData)->pluck('quote_id')->unique()->toArray();

                // $lockCnt = 0;
                // if (count($keys) > 0){
                //     foreach ($tableNameList as $i => $tableName) {
                //         foreach ($keys as $key) {
                //             // ロック確認
                //             $isLocked = $LockManage->isLocked($tableName, $key);
                //             $mode = config('const.mode.show');
                //             if (!$isLocked && $mode == config('const.mode.edit')) {
                //                 // 編集モードかつ、ロックされていない場合はロック取得
                //                 $lockDt = Carbon::now();
                //                 $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $key);
                //             }
                //             // ロックデータ取得
                //             $lockData = $LockManage->getLockData($tableName, $key);
                //             if (!is_null($lockData) 
                //                 && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                //                 && $lockData->key_id === $key && $lockData->lock_user_id === Auth::user()->id) {
                //                     $lockCnt++;
                //             }
                //         }
                //     }
                //     if (count($keys) === $lockCnt) {
                //         $isOwnLock = config('const.flg.on');                        
                //         $lockData = $LockManage->getLockDataForList(self::SCREEN_NAME, $tableName, $keys);
                //     } else {
                //         DB::rollBack();
                //     }
                // } else {
                //     $isOwnLock = config('const.flg.on');
                // }
            // }

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Product.product-check') 
                ->with('isEditable', $isEditable)
                // ->with('isOwnLock', $isOwnLock)
                ->with('lockData', $lockData)
                // ->with('productData', $productData)
                ->with('productList', json_encode($productList))
                // ->with('simList', $simList)
                ->with('constList', $constList)
                ->with('matterList', $matterList)
                ->with('orderList', $orderList)
                ->with('orderDetailList', $orderDetailList)
                // ->with('supplierList', $supplierList)
                ->with('classBigList', $classBigList)
                ->with('classMidList', $classMidList)
                ->with('classSmallList', $classSmallList)
                // ->with('tangibleList', $tangibleList)
                ->with('taxtypeList', $taxtypeList)
                ->with('woodList', $woodList)
                ->with('gradeList', $gradeList)
                // ->with('dlgProductList', $dlgProductList)
                ->with('makerList', $makerList)
                ->with('housingHistoryTransferKbn', json_encode($housingHistoryTransferKbn))
                ->with('noProductCode', $noProductCode)
                ;
    }

    /**
     * 検索（子画面）
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            // 一覧データ取得
            $Product = new Product();
            $data = $Product->getList($params);


        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($data);
    }    

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function selectedProduct(Request $request)
    {
        $resultStatus = false;
        $resultMessage = '';
        $lockData = null;
        $simList = [];
        $isOwnLock = config('const.flg.off');
        $lockInfo = array('lockType' => '', 'keyIds' => []);
        $LockManage = new LockManage();

        try {
            // リクエストから検索条件取得
            $params = $request->request->all();

            // 検索前に取得していたロックを解放（次のロックが取得できなくても解放する必要があるためトランザクション制御外）
            $prevLockInfo = json_decode($params['lock_info'], true);
            $lockType = $prevLockInfo['lock_type'];
            $keyIds = $prevLockInfo['key_ids'];
            if ($lockType === self::SCREEN_NAME || $lockType === self::SCREEN_NAME_SELF) {
                foreach ($keyIds as $key) {
                    $delLock = $LockManage->deleteLockInfo($lockType, array($key), Auth::user()->id);
                }
            }
            
            // 仮商品データ取得
            $Product = new Product();
            $productData = $Product->getProductCheckDataById($params['id']);
            // $productData['product_id'] = $productData['id'];
            if (is_null($productData) || $productData['draft_flg'] === config('const.flg.off') || $productData['del_flg'] === config('const.flg.on')) {
                // 仮登録状態の商品が取得できなかった場合、エラーにする
                $lockData = $LockManage;
                $resultMessage = config('message.error.product_check.not_draft');
                $resultStatus = false;
            } else {
                $QuoteDetail = new QuoteDetail();
                $OrderDetail = new OrderDetail();
                $UpdateHistory = new UpdateHistory();

                // 仮商品IDを持つ見積明細を取得する
                $quoteDetails = $QuoteDetail->getDataByProduct($params['id']);
                $targetQuoteDetailIdList = [];
                foreach ($quoteDetails as $rec) {
                    if (!isset($targetQuoteDetailIdList[$rec['quote_id']])) {
                        $targetQuoteDetailIdList[$rec['quote_id']] = [];
                    }
                    $targetQuoteDetailIdList[$rec['quote_id']][] = $rec['quote_detail_id'];
                }
                // 仮商品IDを持つ発注明細を取得する
                $orderDetails = $OrderDetail->getDataByProduct($params['id']);
                $targetMakerIdList = [];
                $targetOrderDetailIdList = [];
                foreach ($orderDetails as $rec) {
                    if (!isset($targetOrderDetailIdList[$rec['order_id']])) {
                        $targetOrderDetailIdList[$rec['order_id']] = [];
                    }
                    $targetOrderDetailIdList[$rec['order_id']][] = $rec['order_detail_id'];
                    
                    if (Common::nullorBlankToZero($rec['maker_id']) !== 0) {
                        $targetMakerIdList[] = $rec['maker_id'];
                    }
                }

                // 類似商品取得
                $similarIdList = [];
                // ・（メーカーIDが一致する）、かつ、商品番号が一致する
                // ・（メーカーIDが一致する）、かつ、商品番号が後継商品コードの商品番号と一致する
                $similarProductList = $Product->getSimilarProductData($productData['product_code'], $targetMakerIdList);
                if (count($similarProductList) > 0) {
                    $similarIds = $similarProductList->pluck("id")->toArray();
                    $similarIdList = array_merge($similarIdList, $similarIds);
                }
                // ・変更履歴テーブルで変更前商品番号が一致する
                $historyProductCodeList = [];
                foreach ($targetQuoteDetailIdList as $quoteId => $row) {
                    // 見積から商品コード変更分
                    $historys = $UpdateHistory->getByPColName(config('const.updateHistoryKbn.val.quote_detail'), $quoteId, $row, 'product_code')->pluck("from_val")->toArray();
                    $historyProductCodeList = array_merge($historyProductCodeList, $historys);
                }
                foreach ($targetOrderDetailIdList as $orderId => $row) {
                    // 発注から商品コード変更分
                    $historys = $UpdateHistory->getByPColName(config('const.updateHistoryKbn.val.order_detail'), $orderId, $row, 'product_code')->pluck("from_val")->toArray();
                    $historyProductCodeList = array_merge($historyProductCodeList, $historys);
                }
                foreach ($historyProductCodeList as $val) {
                    $historySimilarProductList = $Product->getSimilarProductData($val, array());
                    if (count($historySimilarProductList) > 0) {
                        $similarIds = $historySimilarProductList->pluck("id")->toArray();
                        $similarIdList = array_merge($similarIdList, $similarIds);
                    }
                }
                if (count($similarIdList) > 0) {
                    $simList = $Product->getInId($similarIdList);
                }

                
                DB::beginTransaction();
                
                $keys = collect($quoteDetails)->pluck('quote_id')->unique()->toArray();

                $isCommit = false;
                if (count($keys) > 0) {
                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);

                    $lockCnt = 0;
                    $lockDt = null;
                    $lockUserName = null;
                    $partLockFlg = false;
                    foreach ($tableNameList as $tableName) {
                        foreach ($keys as $key) {
                            // ロック確認
                            $isLocked = $LockManage->isLocked($tableName, $key);
                            $mode = config('const.mode.edit');
                            if (!$isLocked && $mode == config('const.mode.edit')) {
                                // 編集モードかつ、ロックされていない場合はロック獲得
                                $lockDt = Carbon::now();
                                $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $key);
                            }
                            // ロックデータ取得
                            $lockData = $LockManage->getLockData($tableName, $key);
                            if (!is_null($lockData)) {
                                $partLockFlg = true;
                                $lockDt = $lockData->lock_dt;
                                $lockUserName = $lockData->lock_user_name;
                                if ($lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                                    && $lockData->key_id === $key && $lockData->lock_user_id === Auth::user()->id) {
                                    $lockCnt++;
                                }
                            }
                        }
                    }
                    if (count($keys) === $lockCnt) {
                        $isOwnLock = config('const.flg.on');
                        $lockInfo['lockType'] = self::SCREEN_NAME;
                        $lockInfo['keyIds'] = $keys;
                        $isCommit = true;
                    } else {
                        $isCommit = false;
                        if ($partLockFlg && is_null($lockData)) {
                            $lockData = $LockManage;
                            $lockData->id = -1;
                            $lockData->lock_dt = $lockDt;
                            $lockData->lock_user_name = $lockUserName;
                        }
                    }
                } else {
                    // 見積明細や発注明細が存在しない仮商品の場合、仮商品IDでロック取得

                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME_SELF);
                    $keys = array(intval($params['id']));

                    $lockCnt = 0;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // GETパラメータからモード取得
                        $mode = config('const.mode.edit');
                        if (!$isLocked && $mode == config('const.mode.edit')) {
                            // 編集モードかつ、ロックされていない場合はロック取得
                            $lockDt = Carbon::now();
                            $LockManage->gainLock($lockDt, self::SCREEN_NAME_SELF, $tableName, $keys[$i]);
                        }
                        // ロックデータ取得
                        $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                        if (!is_null($lockData) 
                            && $lockData->screen_name === self::SCREEN_NAME_SELF && $lockData->table_name === $tableName
                            && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                                $lockCnt++;
                        }
                    }
                    if (count($tableNameList) === $lockCnt) {
                        $isOwnLock = config('const.flg.on');
                        $lockInfo['lockType'] = self::SCREEN_NAME_SELF;
                        $lockInfo['keyIds'] = $keys;
                        $isCommit = true;
                    } else {
                        $isCommit = false;
                    }
                }

                if (is_null($lockData)) {
                    $lockData = $LockManage;
                }

                if ($isCommit) {
                    DB::commit();
                } else {
                    DB::rollBack();
                }
                $resultStatus = true;
            }
        } catch (\Exception $e) {
            $resultStatus = false;
            DB::rollBack();
            Log::error($e);
            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
        }
        
        return \Response::json([
            'resultStatus' => $resultStatus, 'resultMessage' => $resultMessage, 'lockData' => $lockData, 
            'simList' => $simList, 'isOwnLock' => $isOwnLock, 'lockInfo' => $lockInfo
            ]);
    }    

    /**
     * 商品情報取得
     */
    public function getInfo(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            // 一覧データ取得
            $Product = new Product();
            $data = $Product->getProductCheckDataById($params['id']);

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($data);
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;
        $resultMsg = '';
        $lockData = null;
        $simList = [];
        $tmpErrors = [];
        $simErrors = [];
        $Product = new Product();
        $QuoteDetail = new QuoteDetail();
        $OrderDetail = new OrderDetail();
        $Reserve = new Reserve();
        $ShipmentDetail = new ShipmentDetail();
        $ClassBig = new ClassBig();
        $LockManage = new LockManage();
        $errMsg = '';
    
        // 編集権限チェック
        $hasEditAuth = false;
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            $hasEditAuth = true;

            // リクエストデータ取得
            $params = $request->request->all();
            $tmpPdt = json_decode($params['tmpPdt'], true);
            // $nextPdt = json_decode($params['nextProduct'], true);
            $simList = json_decode($params['simList'], true);
            $prevLockInfo = json_decode($params['lock_info'], true);
            $lockType = $prevLockInfo['lock_type'];
            $keyIds = $prevLockInfo['key_ids'];

        }
        
        try{
            DB::beginTransaction();
            if(!$hasEditAuth){
                // 編集権限なし
                throw new \Exception();
            }
            $saveResult = false;
            $simResult = false;
            // $delLock = false;
            
            $classBigData = $ClassBig->getProductClass();
            // 仮商品を本登録する場合、バリデーションチェック
            if ($tmpPdt['isSave'] == config('const.flg.on')) {
                // バリデーションチェック            
                $tmpErrors = [];
                $tmpErrors = array_merge($tmpErrors, $this->isValid($tmpPdt));
                
                $classBigData = null;
                $myCBigData = null;
                if (!empty($tmpPdt['class_big_id'])) {
                    $myCBigData = collect($classBigData)->where('class_big_id', $tmpPdt['class_big_id'])->first();
                }
                // 木材バリデーション
                if ($myCBigData['format_kbn'] == config('const.flg.on')) {
                    $tmpErrors = array_merge($tmpErrors, $this->isValidWood($tmpPdt));
                }

                // 発注ロットが最小単位数の倍数かどうか
                $tmpPdt['order_lot_quantity'] = Common::roundDecimalRate($tmpPdt['order_lot_quantity']);
                $tmpPdt['min_quantity'] = Common::roundDecimalRate($tmpPdt['min_quantity']);
                if (($tmpPdt['order_lot_quantity']*100) % ($tmpPdt['min_quantity']*100) !== 0) {
                    $tmpErrors['order_lot_quantity'] = ['発注ロット数が最小単位数の倍数ではありません。'];
                }           
            }

            $simErrors = [];        
            $errCnt = 0;    
            // 類似商品　保存対象のみバリデーションチェック
            foreach ($simList as $key => $row) {             
                $simErrors[$key] = [];
                if ($row['isUpdate'] == config('const.flg.on')) {
                    // バリデーションチェック   
                    $simErrors[$key] = array_merge($simErrors[$key], $this->isValid($simList[$key]));

                    $myCBigData = null;
                    if (!empty($simList[$key]['class_big_id'])) {
                        $myCBigData = collect($classBigData)->where('class_big_id', $simList[$key]['class_big_id'])->first();
                    }
                    // 木材バリデーション
                    if ($myCBigData['format_kbn'] == config('const.flg.on')) {
                        $simErrors[$key] = array_merge($simErrors[$key], $this->isValidWood($simList[$key]));
                    }

                    // 画面上ReadOnlyのため、チェックしない
                    // // 発注ロットが最小単位数の倍数かどうか
                    // if ($simList[$key]['order_lot_quantity'] % $simList[$key]['min_quantity'] != 0) {
                    //     $simErrors[$key]['order_lot_quantity'] = '発注ロット数が最小単位数の倍数ではありません。';
                    // }

                    // ロック中か確認
                    // $simErrors[$key]['product_locked'] = '';
                    $productData = $Product->getProductCheckDataById($row['id']);
                    if ($LockManage->isLocked('m_product', intval($row['id'])) || $row['update_at'] !== $productData['update_at']) {
                        $simErrors[$key]['product_locked'] = '商品マスタ('.$row['product_code'].')がロック中または変更されました。';
                    }

                    if (count($simErrors[$key]) > 0) {
                        $errCnt++;
                    }
                }
            }

            if (count($tmpErrors) == 0 && $errCnt == 0){
                $targetQuoteDetails = $QuoteDetail->getDataByProduct($tmpPdt['id']);
                $targetQuoteIdList = collect($targetQuoteDetails)->pluck('quote_id')->unique()->toArray();

                // 仮商品
                if ($tmpPdt['isSave'] == config('const.flg.off')) {
                    // 登録しない
                    $saveResult = $Product->deleteById($tmpPdt['id']);

                } else if ($tmpPdt['isSave'] == config('const.flg.on') && $tmpPdt['isOnce'] == config('const.flg.off')) {
                    // 登録する
                    // 本登録商品との重複チェック
                    $isExist = $Product->isExistRegistered($tmpPdt['product_code'], $tmpPdt['maker_id'], $tmpPdt['id']);
                    if ($isExist) {
                        $resultMsg = config('message.error.product.exist_product_code');
                        throw new \Exception(config('message.error.product.exist_product_code'));
                    }

                    $tmpPdt['draft_flg'] = config('const.flg.off');
                    $updateResult = $Product->updateById($tmpPdt);

                    // 見積明細更新
                    $quoteResult = $QuoteDetail->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    // 発注明細更新
                    $orderResult = $OrderDetail->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    // 引当更新
                    $reserveResult = $Reserve->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    // 出荷指示明細更新
                    $shipmentResult = $ShipmentDetail->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    if ($updateResult && $quoteResult && $orderResult && $reserveResult && $shipmentResult) {
                        $saveResult = true;
                    }                    
                } else if ($tmpPdt['isSave'] == config('const.flg.on') && $tmpPdt['isOnce'] == config('const.flg.on')) {
                    // 1回限り登録
                    $tmpPdt['draft_flg'] = config('const.flg.off');
                    $tmpPdt['auto_flg'] = config('const.flg.on');
                    $updateResult = $Product->updateById($tmpPdt);

                    // 見積明細更新
                    $quoteResult = $QuoteDetail->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    // 発注明細更新
                    $orderResult = $OrderDetail->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    // 引当更新
                    $reserveResult = $Reserve->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    // 出荷指示明細更新
                    $shipmentResult = $ShipmentDetail->updateByProductId($tmpPdt['id'], $tmpPdt, config('const.flg.off'));

                    if ($updateResult && $quoteResult && $orderResult && $reserveResult && $shipmentResult) {
                        $saveResult = true;
                    }
                }

                $saveCnt = 0;
                // 類似商品
                foreach ($simList as $key => $row) {
                    $updateResult = false;
                    $quoteResult = false;
                    $orderResult = false;
                    $reserveResult = false;
                    $shipmentResult = false;

                    $data = null;
                    // マスタ修正
                    if ($row['isUpdate'] == config('const.flg.off')) {
                        // 修正しない場合はマスタから情報取得
                        $data = $Product->getProductCheckDataById($row['id']);
                        $updateResult = true;
                    } else if ($row['isUpdate'] == config('const.flg.on')) {
                        $data = $row;
                        $row['product_image'] = null;
                        $updateResult = $Product->updateById($row);
                    }

                    // 統合
                    if ($row['isIntegrate'] == config('const.flg.on')) { 
                        // 見積明細更新
                        $quoteResult = $QuoteDetail->updateByProductId($tmpPdt['id'], $data, $row['isIntegrate']);

                        // 発注明細更新
                        $orderResult = $OrderDetail->updateByProductId($tmpPdt['id'], $data, $row['isIntegrate']);

                        // 引当更新
                        $reserveResult = $Reserve->updateByProductId($tmpPdt['id'], $data, $row['isIntegrate']);

                        // 出荷指示明細更新
                        $shipmentResult = $ShipmentDetail->updateByProductId($tmpPdt['id'], $data, $row['isIntegrate']);
                        
                        if ($updateResult && $quoteResult && $orderResult && $reserveResult && $shipmentResult) {
                            $saveCnt++;
                        }
                    } else {
                        $saveCnt++;
                    }
                }

                if (count($simList) == $saveCnt) {
                    $simResult = true;
                }

                // ロック解放
                if ($lockType !== self::SCREEN_NAME && $lockType !== self::SCREEN_NAME_SELF || count($keyIds) === 0) {
                    // 念の為
                    $resultMsg = config('message.error.getlock');
                    throw new \Exception(config('message.error.getlock'));
                }
                if ($lockType === self::SCREEN_NAME && count(array_diff($targetQuoteIdList, $keyIds)) !== 0) {
                    // 更新対象データの見積IDとロック取得している見積IDが異なる場合はロールバック
                    $resultMsg = config('message.error.getlock');
                    throw new \Exception(config('message.error.getlock'));
                }
                foreach ($keyIds as $key) {
                    if(!$LockManage->deleteLockInfo($lockType, array($key), Auth::user()->id)){
                        // ロック解放に1件でも失敗したら、自分がロックできていなかったことになるのでロールバック
                        $resultMsg = config('message.error.getlock');
                        throw new \Exception(config('message.error.getlock'));
                    }
                }

            }

            if ($saveResult && $simResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
            Log::error($e);
            // Session::flash('flash_error', config('message.error.error'));
            // return \Response::json(['result' => $resultSts, 'tmpErrors' => $tmpErrors, 'simErrors' => $simErrors]);
        }
        return \Response::json(['result' => $resultSts, 'resultMsg' => $resultMsg, 'lockData' => $lockData, 'simList' => $simList, 'tmpErrors' => $tmpErrors, 'simErrors' => $simErrors, 'errMsg' => $errMsg]);
    }


    /**
     * バリデーションチェック
     *
     * @param array $params
     * @return boolean
     */
    protected function isValid($params)
    {
        // $today = date("Y/m/d");
        $result = [];
        
        // validation（チェックのみ）
        $validator = Validator::make($params, [
            'product_code' => 'required|max:255|regex:'.config('const.productCodeRegex'),
            // 'product_code' => 'required|max:255|unique:m_product,product_code,'. $params['id'] .',id,product_code,'. $params['product_code']. ',maker_id,'. $params['maker_id'] .',del_flg,'. config('const.flg.off') .',draft_flg,'. config('const.flg.off'),
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
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
            'warranty_term' => 'numeric|nullable',		
            'housing_history_transfer_kbn'  => 'numeric|nullable',
            'new_product_id' => 'numeric|nullable',
            'intangible_flg' => 'numeric|required',
        ]);       

        if ($validator->fails()) {
            $result = $validator->messages()->toArray();
        }

        return $result;
    }

    /**
     * 木材バリデーションチェック
     *
     * @param array $params
     * @return boolean
     */
    protected function isValidWood($params)
    {
        $result = [];
        
        // validation（チェックのみ）
        $validator = Validator::make($params, [
            'tree_species' => 'required',
            'grade' => 'required',
            'length' => 'required|integer|digits_between:0,10|between:0,2147483647',	
            'thickness' => 'required|integer|digits_between:0,10|between:0,2147483647',		
            'width' => 'required|integer|digits_between:0,10|between:0,2147483647',	
        ]);       

        if ($validator->fails()) {
            $result = $validator->messages()->toArray();
        }

        return $result;
    }

    /**
     * ロック（編集ボタン）
     * @param Request $request
     * @return type
     */
    public function lock(Request $request)
    {
        $lockInfo = array('lockType' => '', 'keyIds' => []);
        $result = array('status' => false, 'isLocked' => false, 'lockdata'=> null, 'lockInfo' => $lockInfo);
        $LockManage = new LockManage();
        $QuoteDetail = new QuoteDetail();

        // リクエストデータ取得
        $params = $request->request->all();

        DB::beginTransaction();
        try {
            // ロック対象の見積IDを取得
            $quoteDetails = $QuoteDetail->getDataByProduct($params['id']);
            $keys = collect($quoteDetails)->pluck('quote_id')->unique()->toArray();

            $isCommit = false;
            if (count($keys) > 0) {
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);

                $lockDt = Carbon::now();
                $isLocked = false;
                $hasLock = false;
                $lastKey = 0;
                foreach($tableNameList as $tableName) {
                    foreach ($keys as $key) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, intval($key));
                        if ($isLocked) {
                            $result['isLocked'] = true;
                            break;
                        }
                        // ロック取得
                        $hasLock = $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, intval($key));
                        if (!$hasLock) {
                            break;
                        }
                        $lastKey = $key;
                    }
                }

                if ($isLocked) {
                    $isCommit = false;
                    $result['status'] = true;
                } else {
                    // すべてのロックが取得できたらコミット、それ以外はロールバック
                    if ($hasLock) {
                        $isCommit = true;
                        // ロックデータ取得
                        // $result['lockdata'] = $LockManage->getLockDataForList($params['screen'], $lockTblNm, $keys);
                        $result['lockdata'] = $LockManage->getLockData($tableName, $lastKey);
                        $lockInfo['lockType'] = self::SCREEN_NAME;
                        $lockInfo['keyIds'] = $keys;
                        $result['lockInfo'] = $lockInfo;
                        $result['status'] = true;
                    } else {
                        $isCommit = false;
                        $result['status'] = false;
                    }
                }

            } else {
                // 見積明細や発注明細が存在しない仮商品の場合、仮商品IDでロック取得

                $tableNameList = config('const.lockList.'.self::SCREEN_NAME_SELF);
                $keys = array($params['id']);

                $lockDt = Carbon::now();
                $isLocked = false;
                $hasLock = false;
                foreach($tableNameList as $i => $tableName) {
                    // ロック確認
                    $isLocked = $LockManage->isLocked($tableName, intval($keys[$i]));
                    if ($isLocked) {
                        $result['isLocked'] = true;
                        break;
                    }
                    // ロック取得
                    $hasLock = $LockManage->gainLock($lockDt, self::SCREEN_NAME_SELF, $tableName, intval($keys[$i]));
                    if (!$hasLock) {
                        break;
                    }
                }

                if ($isLocked) {
                    $isCommit = false;
                    $result['status'] = true;
                } else {
                    // すべてのロックが取得できたらコミット、それ以外はロールバック
                    if ($hasLock) {
                        $isCommit = true;
                        // ロックデータ取得（最後の1テーブルのみ）
                        $result['lockdata'] = $LockManage->getLockData($tableName, $params['id']);
                        $lockInfo['lockType'] = self::SCREEN_NAME_SELF;
                        $lockInfo['keyIds'] = $keys;
                        $result['lockInfo'] = $lockInfo;
                        $result['status'] = true;
                    } else {
                        $isCommit = false;
                        $result['status'] = false;
                    }
                }
            }
            
            if ($isCommit) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            if (!$result['status']) {
                Session::flash('flash_error', config('message.error.getlock'));
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
     * ロック獲得（強制ロック解除）
     * @param Request $request
     */
    public function gainLock(Request $request)
    {
        $result = array('status' => false, 'lockdata' => null);
        $LockManage = new LockManage();
        $QuoteDetail = new QuoteDetail();

        // リクエストデータ取得
        $params = $request->request->all();

        DB::beginTransaction();
        try {
            // ロック対象の見積IDを取得
            $quoteDetails = $QuoteDetail->getDataByProduct($params['id']);
            $keys = collect($quoteDetails)->pluck('quote_id')->unique()->toArray();

            $isCommit = false;
            if (count($keys) > 0) {
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);

                $lockDt = Carbon::now();
                $hasLock = false;
                $lockTblNm = '';
                $lockKeyId = 0;
                foreach($tableNameList as $tableName) {
                    foreach ($keys as $key) {
                        // ロック獲得
                        $hasLock = $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, intval($key), true);
                        $lockTblNm = $tableName;
                        $lockKeyId = intval($key);
                        if (!$hasLock) {
                            break;
                        }
                    }
                }

                // すべてのロックが獲得できたらコミット、それ以外はロールバック
                if ($hasLock) {
                    $isCommit = true;
                    // ロックデータ取得
                    $result['lockdata'] = $LockManage->getLockData($lockTblNm, $lockKeyId);
                    $result['status'] = true;
                } else {
                    $isCommit = false;
                    $result['status'] = false;
                }

            } else {
                // 見積明細や発注明細が存在しない仮商品の場合、仮商品IDでロック取得

                $tableNameList = config('const.lockList.'.self::SCREEN_NAME_SELF);
                $keys = array(intval($params['id']));

                $lockDt = Carbon::now();
                foreach($tableNameList as $i => $tableName) {
                    // ロック獲得
                    $hasLock = $LockManage->gainLock($lockDt, self::SCREEN_NAME_SELF, $tableName, $keys[$i], true);
                    $lockTblNm = $tableName;
                    $lockKeyId = $keys[$i];
                    if (!$hasLock) {
                        break;
                    }
                }

                // すべてのロックが獲得できたらコミット、それ以外はロールバック
                if ($hasLock) {
                    $isCommit = true;
                    // ロックデータ取得
                    $result['lockdata'] = $LockManage->getLockData($lockTblNm, $lockKeyId);
                    $result['status'] = true;
                } else {
                    $isCommit = false;
                    $result['status'] = false;
                }
            }
            
            if ($isCommit) {
                DB::commit();
            } else {
                DB::rollBack();
            }
            if (!$result['status']) {
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
     * 検索項目チェック
     *
     * @param Request $request
     * @return boolean
     */
    protected function isSearchValid(Request $request)
    {
        $this->validate($request, [
            'product_code' => 'required',
            'product_name' => 'required',
        ]);
    }
}