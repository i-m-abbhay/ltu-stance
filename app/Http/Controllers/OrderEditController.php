<?php
namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use Session;
use Storage;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\QuoteVersion;
use App\Models\QuoteLayer;
// use App\Models\Customer;
use App\Models\General;
use App\Models\LockManage;
// use App\Models\NumberManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Address;
// use App\Models\CalendarData;
// use App\Models\CustomerBranch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Person;
use App\Models\QuoteDetail;
// use App\Models\Shipment;
use App\Models\ShipmentDetail;
// use App\Models\Warehouse;
use App\Models\Supplier;
// use App\Models\Product;
use App\Models\SupplierMaker;
// use App\Models\ProductPrice;
use App\Models\Reserve;
// use App\Models\Construction;
// use App\Models\Department;
// use App\Models\ProductCampaignPrice;
// use App\Models\Staff;
use App\Models\SupplierFile;
use App\Models\UpdateHistory;
use App\Models\ClassBig;
use App\Models\ClassMiddle;
use App\Providers\OrderServiceProvider;

/**
 * 発注登録
 */
class OrderEditController extends Controller
{
    const SCREEN_NAME = 'order-edit';

    const ORDER_STATUS_LIST = [
        'not_order' => [
            'text' => '未発注',
            'val' => 1,
        ],
        'part_order' => [
            'text' => '一部発注',
            'val' => 2,
        ],
        'done_order' => [
            'text' => '発注済',
            'val' => 3,
        ],
    ];

    // サービス
    private $OrderServiceProvider;

    /**
     * コンストラクタ
     */
    public function __construct(OrderServiceProvider $OrderServiceProvider)
    {
        // ログインチェック
        $this->middleware('auth');
        $this->OrderServiceProvider = $OrderServiceProvider;
        $this->OrderServiceProvider->initialize($this);
    }

    /**
     * 初期表示
     * @param Request $request
     * @param int $matterId 案件ID
     * @return type
     */
    public function index(Request $request, $matterId = null)
    {
        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $Matter     = new Matter();
        $QuoteLayer = new QuoteLayer();
        $QuoteDetail = new QuoteDetail();
        $LockManage = new LockManage();
        $Supplier   = new Supplier();
        $General    = new General();
        // $Product    = new Product();
        $SupplierMaker = new SupplierMaker();
        $SupplierFile = new SupplierFile();
        $Person     = new Person();
        $Order      = new Order();
        $Reserve    = new Reserve();
        $ClassBig   = new ClassBig();
        $ClassMiddle = new ClassMiddle();

        $SystemUtil = new SystemUtil();

        $mainData   = null;
        $baseData   = null;
        $lockData   = null;
        $gridDataList   = [];
        $quoteLayerList = $QuoteLayer;
        $sumEachWarehouseAndDetailList = [];

        $classBigList = null;
        $classMiddleList = null;
        $priceList = null;
        // $allocList = null;
        $supplierList  = null;
        $supplierMakerList = null;
        $supplierFileList = null;
        $personList = [];
        $orderList  = null;
        $deliveryAddressList = null;

        $updateHistoryDataList = null;

        $quoteFileNameList = [];
        $orderFileNameList = [];

        $baseData = $Matter->getOrderEditData($matterId);

        if($baseData === null){
            throw new NotFoundHttpException();
        }

        DB::beginTransaction();
        try {
            
            $mainData = $Order;
            $tmpSaveData = $Order->getTmpSaveDataByQuoteNo($baseData->quote_no);

            if($tmpSaveData !== null){
                $mainData = $tmpSaveData;
                // 初期表示の入荷先リスト
                $deliveryAddressList = $this->OrderServiceProvider->getDeliveryAddressList($mainData->supplier_id, $baseData->address_id);
                $tmpMakerInfo = $Supplier->getById($mainData->maker_id);
                $tmpSupplierInfo = $Supplier->getById($mainData->supplier_id);
                $mainData->maker_name = is_null($tmpMakerInfo) ? '':$tmpMakerInfo->supplier_name;
                $mainData->supplier_name = is_null($tmpSupplierInfo) ? '':$tmpSupplierInfo->supplier_name;
                $tmpAddressInfo = $deliveryAddressList->firstWhere('unique_key', $mainData->delivery_address_kbn.'_'.$mainData->delivery_address_id);
                $mainData->delivery_address = is_null($tmpAddressInfo)?'':$tmpAddressInfo->address;
            }else{
                Common::initModelProperty($mainData);
                // 初期表示の入荷先リスト
                $deliveryAddressList = $this->OrderServiceProvider->getDeliveryAddressList(null, $baseData->address_id);
                $mainData->maker_name = '';
                $mainData->supplier_name = '';
                $mainData->delivery_address = '';
                $mainData->desired_delivery_date = null;
                $mainData->map_print_flg = config('const.flg.off');
            }
            
            // メーカーリスト取得
            $supplierMakerKbn = [config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.maker')];
            $makerList = $Supplier->getBySupplierKbn($supplierMakerKbn);
            // 大分類リスト取得
            $classBigList = $ClassBig->getIdNameList();
            // 中分類リスト取得
            $classMiddleList = $ClassMiddle->getIdNameList();
            // 価格区分リスト取得
            $priceList = $General->getCategoryList(config('const.general.price'));
            // // 引当区分リスト取得
            // $allocList = $General->getCategoryList(config('const.general.alloc'));
            // 仕入先リスト取得
            $supplierList = $Supplier->getComboList();
            // 仕入先メーカーリスト取得(裏で保持しておく)
            $tmpList = $SupplierMaker->getComboListByMakerId();
            $supplierMakerList = $tmpList->mapToGroups(function ($item, $key) {
                return [$item['maker_id'] => $item];
            });
            // 仕入先ファイル
            $supplierFileList = $SupplierFile->getAll()->mapWithKeys(function ($item, $key) {
                return [
                    $item->supplier_id => $item->file_name,
                ];
            });

            // 担当者リスト取得(裏で保持しておく)
            $tmpList = $Person->getComboList(config('const.person_type.supplier'));
            $personList = $tmpList->mapToGroups(function ($item, $key) {
                return [$item['company_id'] => $item];
            });
            // 発注番号リスト取得
            $orderList = $Order->getComboList($baseData->matter_no); 


            // 見積階層データ取得
            $quoteLayerList = $QuoteDetail->getTreeData($baseData->quote_no, config('const.quoteCompVersion.number'), null, null, array(), array(), config('const.flg.on'));
            $SystemUtil->deleteNotLayerRecord($quoteLayerList[0]);
            // 見積明細データ取得
            $gridDataList = $SystemUtil->addFilterTreePathInfo($QuoteDetail->getDataListByQuoteVersion($baseData->quote_no, $baseData->quote_id, config('const.quoteCompVersion.number')));
            

            // 見積明細と倉庫と引当フラグ毎の引当数(グリッド上の棒グラフ用の値)
            $tmpList = $Reserve->getSumEachWarehouseAndDetail($baseData->quote_id, $baseData->quote_no);
            foreach($tmpList as $record){
                $tmpReturnQuantity = $record->return_quantity === null ? 0 : $record->return_quantity;
                $record->sum_warehouse_min_reserve_quantity = $record->sum_warehouse_min_reserve_quantity - $tmpReturnQuantity;
                $sumEachWarehouseAndDetailList[$record->quote_detail_id][$record->from_warehouse_id][] = $record;
            }

            
            // 変更履歴取得
            $updateHistoryDataList = collect($this->getGridFixLog(config('const.updateHistoryKbn.val.quote_detail'), $baseData->quote_id));

            // 商品コード無し取得
            $noProductCode = $General->getCategoryList(config('const.general.noproductcode'))->first();

            // フォルダ内のファイル全取得
            $fileList = Storage::files(config('const.uploadPath.quote_version').$baseData->quote_version_id);
            
            foreach ($fileList as $file) {
                $filePath = Storage::url($file);
                $tmps = explode('/', $filePath);
                $fileName = $tmps[count($tmps) - 1];

                $quoteFileNameList[] = ['name' => $fileName, 'storage_file_name' => $fileName];
            }

            // フォルダ内のファイル全取得
            $fileList = Storage::files($this->OrderServiceProvider->getUploadFilePath($baseData->matter_id, $mainData->id));
            
            foreach ($fileList as $file) {
                $filePath = Storage::url($file);
                $tmps = explode('/', $filePath);
                $fileName = $tmps[count($tmps) - 1];

                $orderFileNameList[] = ['name' => $fileName, 'storage_file_name' => $fileName];
            }


            // 画面名から対象テーブルを取得
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            $keys = array($baseData->quote_id);

            $lockCnt = 0;
            foreach ($tableNameList as $i => $tableName) {
                // ロック確認
                $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                // GETパラメータからモード取得
                // $mode = config('const.mode.show');
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
            } else {
                DB::rollBack();
            }
                

            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Order.order-edit') 
                ->with('baseData',  $baseData)
                ->with('mainData',  $mainData)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData',  $lockData)
                ->with('quoteLayerList',    json_encode($quoteLayerList))
                ->with('shippingLimitList', json_encode(config('const.shippingLimitList')))
                ->with('gridDataList',      json_encode($gridDataList))
                ->with('makerList',         $makerList)
                ->with('orderStatusList',   json_encode(self::ORDER_STATUS_LIST))
                ->with('classBigList',      $classBigList)
                ->with('classMiddleList',   $classMiddleList)
                ->with('priceList',         $priceList)
                // ->with('allocList',         $allocList)
                ->with('supplierList',      $supplierList)
                ->with('supplierMakerList', $supplierMakerList)
                ->with('supplierFileList', $supplierFileList)
                ->with('personList',        $personList)
                ->with('quoteFileNameList', json_encode($quoteFileNameList))
                ->with('orderFileNameList', json_encode($orderFileNameList))
                ->with('sumEachWarehouseAndDetailList', json_encode($sumEachWarehouseAndDetailList))
                ->with('orderList',         $orderList)
                ->with('deliveryAddressList',   $deliveryAddressList)
                ->with('stockFlg', json_encode(config('const.stockFlg')))
                ->with('noProductCode',   $noProductCode)
                ->with('updateHistoryDataList',   $updateHistoryDataList)
                ;
    }

    /**
     * 修正ログ取得
     *
     * @param [type] $kbn
     * @param [type] $headerKey
     * @return void
     */
    private function getGridFixLog($kbn, $headerKey){
        $UpdateHistory = new UpdateHistory();
        $histories = $UpdateHistory->getUpdatedColumnsByHeaderKey($kbn, $headerKey);

        $data = [];
        foreach ($histories as $key => $history) {
            $key = $history->detail_key;
            $columns = get_object_vars(json_decode($history->columns));

            unset($columns['update_at']);
            unset($columns['product_id']);
            unset($columns['maker_id']);
            unset($columns['supplier_id']);
            unset($columns['cost_total']);
            unset($columns['sales_total']);
            unset($columns['seq_no']);

            unset($columns['quantity_per_case']);
            unset($columns['set_kbn']);
            unset($columns['construction_id']);
            unset($columns['class_big_id']);
            unset($columns['class_middle_id']);
            unset($columns['class_small_id']);
            unset($columns['tree_species']);
            unset($columns['grade']);
            unset($columns['length']);
            unset($columns['thickness']);
            unset($columns['width']);

            if(count($columns) === 0){
                continue;
            }

            if (!isset($data[$key])) {
                $data[$key]['fix_columns'] = [];
            }

            // セルの文字色を変えるcolumnのリストを格納
            $data[$key]['fix_columns'] = array_values(array_unique(array_merge($data[$key]['fix_columns'], array_keys($columns))));
            $data[$key]['fix_logs'][] = [
                'date' => $history->created_at,
                'name' => $history->staff_name,
                'columns' => array_values($columns)
            ];
        }

        return $data;
    }
    
    /**
     * 案件情報取得(案件住所の更新アイコンのボタン)
     * @param $request
     * @return type
     */
    public function getMatterInfo(Request $request){
        $params = $request->request->all();
        $matter = Matter::find($params['matter_id']);
        $value['matter_address'] = Address::find($matter->address_id);
        $value['delivery_address_list'] = $this->OrderServiceProvider->getDeliveryAddressList($params['supplier_id'], $matter->address_id);
        
        return \Response::json($value);
    }

    /**
     * 入荷先リスト取得
     * @param $request
     * @return type
     */
    public function getDeliveryAddressList(Request $request){
        $params = $request->request->all();
        $deliveryAddressList = $this->OrderServiceProvider->getDeliveryAddressList($params['supplier_id'], $params['matter_address_id']);
        
        return \Response::json($deliveryAddressList);
    }

    /**
     * 一時保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request){
        $result = array('status' => false, 'message' => '', 'id' => 0);

        $SystemUtil     = new SystemUtil();
        $QuoteDetail    = new QuoteDetail();
        $QuoteVersion   = new QuoteVersion();
        
        $now = Carbon::now();
        // 添付ファイル
        $this->OrderServiceProvider->isValidFile($request);

        DB::beginTransaction();

        try{
            // リクエストデータ取得
            $params = $request->request->all();
            // 変更前の見積明細
            $beforeQuoteDetailData  = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
            // 変更前の見積版を取得
            $beforeQuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
            // 変更前の納品を取得
            $deliveryDataList       = $QuoteDetail->getDeliveryDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
            // 変更前の売上明細を取得
            $salesDetailDataList    = $QuoteDetail->getSalesDetailDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
            
            // 一時保存
            $tmpSaveResult = $this->temporarySave($request, $now, false, true);
            // 見積の粗利率が基準値を下回った場合の処理
            $SystemUtil->quoteFallBelowGrossProfitRateProcessing($params['quote_no'], $beforeQuoteVersionData);
            
            // 変更後の見積明細
            $afterQuoteDetailData = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
            // 納品データのチェック
            $this->OrderServiceProvider->isValidQuoteChangeState($deliveryDataList, $afterQuoteDetailData, true);
            // 売上明細データのチェック
            $this->OrderServiceProvider->isValidQuoteChangeState($salesDetailDataList, $afterQuoteDetailData, false);

            // 変更後の見積明細
            $afterQuoteDetailDataList = $afterQuoteDetailData->toArray();

            // 変更履歴の登録
            foreach($beforeQuoteDetailData as $key => $record){
                if(isset($afterQuoteDetailDataList[$key])){
                    if (!$SystemUtil->addUpdateHistory(
                        config('const.updateHistoryKbn.val.quote_detail'),
                        intval($params['quote_id']),
                        $record,
                        $afterQuoteDetailDataList[$key]
                    )) {
                        throw new \Exception(config('message.error.save'));
                    }
                }
            }

            // ロック解除
            $this->OrderServiceProvider->unLock($params['quote_id']);

            DB::commit();
            $result['status'] = true;
            $result['id'] = $tmpSaveResult['orderId'];
            //$result['message'] = config('message.success.save');
            Session::flash('flash_success', config('message.success.save'));
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getFirstMessage();
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * 一時保存処理
     * @param $request リクエストデータ
     * @param $now 現在日時
     * @param $isOrderExec 発注登録ボタンから呼ばれたかどうか
     * @param $validSaveDraftFlg 仮登録をするか
     * @return $result
     *         ['orderId']
     *         ['tmpFilePath']
     *         ['quoteDetailIdList']
     */
    private function temporarySave($request, $now, $isOrderExec, $validSaveDraftFlg = false){
        $result = [
            'orderId'=>0,
            'tmpFilePath'=>'',
            'quoteDetailIdList'=>[],
            'saveQuoteDetailDataList'=>[],
        ];

        $Order          = new Order();
        $OrderDetail    = new OrderDetail();
        $SystemUtil     = new SystemUtil();
        $QuoteVersion   = new QuoteVersion();
        $QuoteDetail    = new QuoteDetail();
        $Reserve        = new Reserve();
        $ShipmentDetail = new ShipmentDetail();
        

        // リクエストデータ取得
        $params = $request->request->all();

        // ロック確認
        $this->OrderServiceProvider->isOwnLocked($params['quote_id']);


        // 全てのグリッドデータ
        $gridAllDataList = json_decode($params['grid_all_data']);

        // 保存用に加工した発注ヘッダーデータを取得
        $orderSaveData = $this->OrderServiceProvider->getOrderSaveData($params, $now, true, $isOrderExec);
        // 一時保存データ取得
        $tmpSaveData = $Order->getTmpSaveDataByQuoteNo($params['quote_no']);

        if($tmpSaveData === null){
            // 一時保存データなしの場合、新規登録
            $result['orderId'] = $Order->add($orderSaveData);
        }else{
            $orderSaveData['id'] = $tmpSaveData->id;
            $Order->updateById($orderSaveData);
            $result['orderId'] = $orderSaveData['id'];
            // 一時保存発注明細全削除
            $OrderDetail->physicalDeleteByOrderId($result['orderId']);
        }
        
        if(!$result['orderId']){
            throw new \Exception(config('message.error.save'));
        }

        // 見積明細 登録/更新
        $updateOrInsertQuoteDetailResult = $this->OrderServiceProvider->updateOrInsertQuoteDetail($params['quote_no'], $gridAllDataList, $params['customer_id'], $validSaveDraftFlg, $isOrderExec);
        $result['quoteDetailIdList'] = $updateOrInsertQuoteDetailResult['savedDetailIds'];
        $result['saveQuoteDetailDataList'] = $updateOrInsertQuoteDetailResult['savedQuoteDetailDataList'];
        // 発注明細データの取得
        $orderDetailSaveData = $this->OrderServiceProvider->getOrderDetailData($params, $result['saveQuoteDetailDataList'], $result['quoteDetailIdList'], $result['orderId'], $orderSaveData['order_no']);

        // 発注明細の登録
        if(!$OrderDetail->addList($orderDetailSaveData['orderDetail'])){
            throw new \Exception(config('message.error.save'));
        }

        // 超過元の見積明細情報を数量超過した見積明細に反映する
        $this->OrderServiceProvider->updateQuoteToAddLayerQuote($params['quote_no'], $params['customer_id']);

        $afterQuoteDetailData = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
        foreach($afterQuoteDetailData as $key => $record){
            // 在庫引当の削除　発注引当の更新
            $Reserve->changeOrderProduct($record['id'], $record['product_id'], $record['product_code']);
            // 出荷指示の削除　出荷指示の更新
            $ShipmentDetail->changeOrderProduct($record['id'], $record['product_id'], $record['product_code']);
        }

        // 数量超過の明細 更新削除
        $this->OrderServiceProvider->updateOverQuoteDetailData($params['quote_id'], $params['quote_no']);
        

        // 階層ごとの金額計算
        $targetList = $SystemUtil->calcQuoteLayersTotal($params['quote_no'], config('const.quoteCompVersion.number'));
        foreach ($targetList as $id => $item) {
            if ($id === 0) continue;
            
            $updateResult = $QuoteDetail->updateTotalKng($id, $item);
        }

        // 見積0版金額更新
        $updateResult = $QuoteVersion->updateTotalKng($params['quote_no'], config('const.quoteCompVersion.number'));


        // 見積の情報を発注に反映する
        $this->OrderServiceProvider->updateQuoteToOrder($params['quote_no']);


        /* 関連データ更新 END */
        $result['tmpFilePath'] = $this->OrderServiceProvider->getUploadFilePath($params['matter_id'], $result['orderId']);
        $deleteFiles = json_decode($params['delete_file_list']);
        // ファイル削除
        $this->OrderServiceProvider->deleteFile($result['tmpFilePath'], $deleteFiles);

        // ファイルアップロード
        // 添付ファイル
        $uploadFileList = $request->files;
        foreach ($uploadFileList as $fileNm => $fileObj) {
            $request->file($fileNm)->storeAs($result['tmpFilePath'], $fileObj->getClientOriginalName());
        }

        return $result;
    }

    /**
     * 発注登録
     * @param Request $request
     * @return type
     */
    public function application(Request $request){
        $result = array('status' => false, 'message' => '', 'id' => 0);

        $Order          = new Order();
        $OrderDetail    = new OrderDetail();
        $SystemUtil     = new SystemUtil();
        $QuoteVersion   = new QuoteVersion();
        $QuoteDetail    = new QuoteDetail();

        $orderSaveData  = [];
        $orderDetailSaveData = [];
        

        // リクエストデータ取得
        $params = $request->request->all();
        

        // 入力チェック
        $this->OrderServiceProvider->isValidOrder($request);

        // 添付ファイル
        $this->OrderServiceProvider->isValidFile($request);
        
        $now = Carbon::now();

        DB::beginTransaction();

        try{
            // 変更前の見積明細
            $beforeQuoteDetailData  = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
            // 変更前の見積版を取得
            $beforeQuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
            // 変更前の納品を取得
            $deliveryDataList       = $QuoteDetail->getDeliveryDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
            // 変更前の売上明細を取得
            $salesDetailDataList    = $QuoteDetail->getSalesDetailDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();

            // 一時保存
            $tmpSaveResult = $this->temporarySave($request, $now, true, true);
            // 発注の登録用に加工したデータを取得
            $orderSaveData = $this->OrderServiceProvider->getOrderSaveData($params, $now, false);
            // 発注登録
            $orderId = $Order->add($orderSaveData);
            if(!$orderId){
                throw new \Exception(config('message.error.save'));
            }
            // 発注明細データの取得
            $gridDataList = [];
            foreach($tmpSaveResult['saveQuoteDetailDataList'] as $record){
                if($record['chk'] && $record['layer_flg'] === config('const.flg.off') && $record['maker_id'] === intval($orderSaveData['maker_id'])){
                    $gridDataList[] = $record;
                }
            }
            $orderDetailSaveData = $this->OrderServiceProvider->getOrderDetailData($params, $gridDataList, $tmpSaveResult['quoteDetailIdList'], $orderId, $orderSaveData['order_no']);

            // 発注明細の登録
            if(!$OrderDetail->addList($orderDetailSaveData['orderDetail'])){
                throw new \Exception(config('message.error.save'));
            }

            // 発注明細の一時保存データの数量をリセット
            $temporaryResetQuoteDetailIdList = [];
            foreach($orderDetailSaveData['orderDetail'] as $record){
                $temporaryResetQuoteDetailIdList[] = $record['quote_detail_id'];
            }
            // 発注した一時保存データの発注数を0にリセット
            if(!$OrderDetail->resetTemporaryQuantity($temporaryResetQuoteDetailIdList)){
                throw new \Exception(config('message.error.save'));
            }
            
            // 数量超過の明細 登録更新削除
            $this->OrderServiceProvider->updateOverQuoteDetailData($params['quote_id'], $params['quote_no']);

            // 階層ごとの金額計算
            $targetList = $SystemUtil->calcQuoteLayersTotal($params['quote_no'], config('const.quoteCompVersion.number'));
            foreach ($targetList as $id => $item) {
                if ($id === 0) continue;
                
                $updateResult = $QuoteDetail->updateTotalKng($id, $item);
            }

            // 見積0版金額更新
            $updateResult = $QuoteVersion->updateTotalKng($params['quote_no'], config('const.quoteCompVersion.number'));
            // 見積の粗利率が基準値を下回った場合の処理
            $SystemUtil->quoteFallBelowGrossProfitRateProcessing($params['quote_no'], $beforeQuoteVersionData);

            // 承認(申請)データの作成
            //$SystemUtil->applyForOrderProcessing($Order->getById($orderId));

            // 発注データの並び替え
            $OrderDetail->updateSortByQuoteData($orderId);
            
            // 変更後の見積明細
            $afterQuoteDetailData = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
            // 納品データのチェック
            $this->OrderServiceProvider->isValidQuoteChangeState($deliveryDataList, $afterQuoteDetailData, true);
            // 売上明細データのチェック
            $this->OrderServiceProvider->isValidQuoteChangeState($salesDetailDataList, $afterQuoteDetailData, false);

            // 変更後の見積明細
            $afterQuoteDetailDataList = $afterQuoteDetailData->toArray();
            // 変更履歴の登録
            foreach($beforeQuoteDetailData as $key => $record){
                if(isset($afterQuoteDetailDataList[$key])){
                    if (!$SystemUtil->addUpdateHistory(
                        config('const.updateHistoryKbn.val.quote_detail'),
                        intval($params['quote_id']),
                        $record,
                        $afterQuoteDetailDataList[$key]
                    )) {
                        throw new \Exception(config('message.error.save'));
                    }
                }
            }



            // 一時保存ファイルを移動
            $this->OrderServiceProvider->moveFolder($tmpSaveResult['tmpFilePath'], $this->OrderServiceProvider->getUploadFilePath($params['matter_id'], $orderId));
            
            // ロック解除
            $this->OrderServiceProvider->unLock($params['quote_id']);

            DB::commit();
            $result['status'] = true;
            $result['id'] = $orderId;
            //$result['message'] = config('message.success.save');
            Session::flash('flash_success', config('message.success.save'));
        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getFirstMessage();
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * 発注番号採番
     * @param Request $request
     * @return type
     */
    public function createOrderNo(Request $request){
        $result = array('status' => false, 'message' => '', 'id' => 0, 'orderNo' => '');

        $Order          = new Order();
        $orderSaveData  = [];
        

        // リクエストデータ取得
        $params = $request->request->all();
        // 入力チェック
        $this->isValidCreateOrderNo($request);

        $now = Carbon::now();

        DB::beginTransaction();

        try{
            // ロック確認
            $this->OrderServiceProvider->isOwnLocked($params['quote_id']);
            // 発注の登録用のに加工したデータを取得
            $orderSaveData = $this->OrderServiceProvider->getOrderSaveData($params, $now, false);
            // 発注登録
            $orderId = $Order->add($orderSaveData);
            if(!$orderId){
                throw new \Exception(config('message.error.save'));
            }
            
            DB::commit();
            $result['status'] = true;
            $result['id'] = $orderId;
            $result['orderNo'] = $orderSaveData['order_no'];
            $result['message'] = config('message.success.save');

        }catch(ValidationException $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getFirstMessage();
        }catch(\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            $result['message'] = $e->getMessage();
            Log::error($e);
        }

        return \Response::json($result);
    }
    /**
     * 発注番号採番時のバリデーションチェック
     * @param Request $request
     * @return type
     * @throws Exception
     */
    public function isValidCreateOrderNo(Request $request){
        $this->validate($request, [
            'maker_id'          => 'required|integer|min:1',
            'supplier_id'       => 'required|integer|min:1',
        ],
        [
            'maker_id.min'      => '必須です',
            'supplier_id.min'   => '必須です',
        ]);
    }

    /**
     * ファイルが存在するか
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function existsFile(Request $request, $id, $fileName){
        $fileName = urldecode($fileName);
        $result = Storage::exists(config('const.uploadPath.quote_version'). $id. '/'. $fileName);
        return \Response::json($result);
    }

    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function download(Request $request, $id, $fileName){
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.quote_version').$id.'/'.$fileName), $fileName, $headers);
    }

}