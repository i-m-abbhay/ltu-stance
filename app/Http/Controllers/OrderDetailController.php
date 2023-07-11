<?php
namespace App\Http\Controllers;

use DB;
use Storage;
use Auth;
use Session;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
// use App\Models\Authority;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Person;
use App\Models\Matter;
use App\Models\Product;
use App\Models\Supplier;
// use App\Models\SupplierMaker;
use App\Models\General;
// use App\Models\Warehouse;
use App\Models\LockManage;
use App\Models\QuoteDetail;
use App\Models\QuoteVersion;
use App\Models\Reserve;
// use App\Models\Delivery;
// use App\Models\ProductPrice;
// use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\UpdateHistory;
use App\Libs\SystemUtil;
use App\Libs\Common;
use App\Models\Construction;
use App\Models\ClassBig;
use App\Models\ClassMiddle;
use App\Providers\OrderServiceProvider;

/**
 * 発注明細
 */
class OrderDetailController extends Controller
{
    const SCREEN_NAME = 'order-detail';

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
     * @param $id 発注ID
     * @return type
     */
    public function index(Request $request, $id = null)
    {
        $Order          = new Order();
        $OrderDetail    = new OrderDetail();
        $Construction   = new Construction();
        $QuoteDetail    = new QuoteDetail();
        $Person         = new Person();
        // $Product        = new Product();
        // $ProductPrice   = new ProductPrice();
        $Supplier       = new Supplier();
        // $SupplierMaker  = new SupplierMaker();
        $General        = new General();
        // $Warehouse      = new Warehouse();
        $Reserve        = new Reserve();
        $ClassBig       = new ClassBig();
        $ClassMiddle    = new ClassMiddle();
        $LockManage     = new LockManage();

        // 発注データ取得
        $orderData = $Order->getOrderDetailEditData($id);
        if (is_null($orderData)) {
            throw new NotFoundHttpException();
        }
         
        DB::beginTransaction();
        try {

            // ログイン者がロックしているフラグ
            $isOwnLock = config('const.flg.off');

            $orderData->delivery_address = '';
            // 発注明細データ
            $orderDetailData = $OrderDetail->getOrderDetailEditData($orderData->quote_no, $orderData->id);
            // 全一式商品データ
            $setProductList = $QuoteDetail->getSetProductList($orderData->quote_no, config('const.quoteCompVersion.number'));
            // 全子部品データ
            $tmpList = $QuoteDetail->getChildPartsList($orderData->quote_no, config('const.quoteCompVersion.number'))->toArray();
            $childPartsList = [];
            foreach($tmpList as $key => $record){
                $childPartsList[$record['parent_quote_detail_id']][] = $record;
            }
            // 見積明細と倉庫と引当フラグ毎の引当数(グリッド上の棒グラフ用の値)
            $tmpList = $Reserve->getSumEachWarehouseAndDetail($orderData->quote_id, $orderData->quote_no);
            $reserveData = [];
            // 在庫や発注の引当数を算出
            foreach($tmpList as $record){
                if(!isset($reserveData[$record->quote_detail_id])){
                    $reserveData[$record->quote_detail_id] = 0;
                }
                $tmpReturnQuantity = $record->return_quantity === null ? 0 : $record->return_quantity;
                $reserveData[$record->quote_detail_id] += $record->sum_warehouse_min_reserve_quantity - $tmpReturnQuantity;
            }
            
            // データ加工
            foreach($orderDetailData as $key => $record){
                // 引当数をグリッド項目にセット
                if(isset($reserveData[$record['quote_detail_id']])){
                    $orderDetailData[$key]['reserve_quantity'] = $reserveData[$record['quote_detail_id']] - $record['order_quantity'];
                }else{
                    $orderDetailData[$key]['reserve_quantity'] = 0;
                }
            }

            // 修正ログ取得
            $orderDetailLogs = collect($this->getGridFixLog(config('const.updateHistoryKbn.val.order_detail'), $orderData->id));
            // 見積依頼項目リスト取得
            $qreqList = $Construction->getQreqData(config('const.flg.on'));
            // パーソンリスト取得
            $personList = $Person->getComboList(config('const.person_type.supplier'), $orderData->supplier_id);
            //$makerProductList = $Product->getQuoteProductList();
            // 仕入先リスト取得
            $supplierList = $Supplier->getComboList();
            // 仕入先メーカーリスト取得(裏で保持しておく)
            // $tmpList = $SupplierMaker->getComboListByMakerId();
            // $supplierMakerList = $tmpList->mapToGroups(function ($item, $key) {
            //     return [$item['maker_id'] => $item];
            // });
            // 価格区分リスト取得
            $priceList = $General->getCategoryList(config('const.general.price'));
            // 大分類リスト取得
            $classBigList = $ClassBig->getIdNameList();
            // 中分類リスト取得
            $classMiddleList = $ClassMiddle->getIdNameList();
            
            // 商品コード無し取得
            $noProductCode = $General->getCategoryList(config('const.general.noproductcode'))->first();
            
            // 入荷先リスト取得
            $deliveryAddressList = $this->OrderServiceProvider->getDeliveryAddressList($orderData->supplier_id, $orderData->matter_address_id);

            // 当社在庫品発注の場合
            if(Common::isFlgOn($orderData->own_stock_flg)){
                // 自社倉庫のみ
                $deliveryAddressList = $deliveryAddressList->where('delivery_address_kbn', config('const.deliveryAddressKbn.val.company'));
                $orderData->customer_name = config('const.LBL_TEXT.OWN_STOCK_CUSTOMER_NAME');
            }

            // フォルダ内のファイル全取得
            $fileList = Storage::files($this->OrderServiceProvider->getUploadFilePath($orderData->matter_id, $orderData->id));
            $orderFileNameList = [];
            foreach ($fileList as $file) {
                $filePath = Storage::url($file);
                $tmps = explode('/', $filePath);
                $fileName = $tmps[count($tmps) - 1];

                $orderFileNameList[] = ['name' => $fileName, 'storage_file_name' => $fileName];
            }

            // ロック管理
            // 画面名から対象テーブルを取得
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            $keys = array($orderData->quote_id);

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

        return view('Order.order-detail')
            ->with('orderData', $orderData)
            ->with('orderDetailData', json_encode($orderDetailData))
            ->with('setProductList', json_encode($setProductList))
            ->with('orderDetailLogs', $orderDetailLogs)
            ->with('isOwnLock', $isOwnLock)
            ->with('lockData', $lockData)
            ->with('qreqList', $qreqList)
            ->with('personList', $personList)
            //->with('makerProductList', $makerProductList)
            ->with('supplierList', $supplierList)
            ->with('classBigList', $classBigList)
            ->with('classMiddleList', $classMiddleList)
            ->with('priceList', $priceList)
            ->with('noProductCode', $noProductCode)
            ->with('deliveryAddressList', $deliveryAddressList)
            ->with('orderFileNameList', json_encode($orderFileNameList))
            ->with('addKbnList', json_encode(config('const.orderDetailAddKbn')))
            ->with('orderStatusList', json_encode(config('const.orderStatus')))
            ->with('childPartsList',  json_encode($childPartsList))
            ;
    }

    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $SystemUtil     = new SystemUtil();

        $Order          = new Order();
        $QuoteDetail    = new QuoteDetail();
        $QuoteVersion   = new QuoteVersion();
        $OrderDetail    = new OrderDetail();
        $Reserve        = new Reserve();
        $ShipmentDetail = new ShipmentDetail();

        // リクエストデータ取得
        $params = $request->request->all();
        $gridDataList = json_decode($params['grid_data']);

        // // 入力チェック
        $this->OrderServiceProvider->isValidOrder($request);
        // 添付ファイル
        $this->OrderServiceProvider->isValidFile($request);

        DB::beginTransaction();
        
        try {
            $createReserveFlg = false;
            $params['delivery_address_kbn'] = intVal($params['delivery_address_kbn']);
            $params['delivery_address_id']  = intVal($params['delivery_address_id']);
            // 入荷先区分を変更した時に今日以前の日付があるかチェックする必要があるか
            $isValidChkChangeDeliveryAddressKbn = false;

            // ロック確認
            $this->OrderServiceProvider->isOwnLocked($params['quote_id']);
            // 変更前の発注ヘッダー
            $beforeOrderData    = $Order->getById($params['id']);
            // 変更前の発注明細(変更履歴やチェック処理に使用)
            $beforeOrderDetailList = $OrderDetail->getByOrderId($params['id']);
            // 入荷先情報
            $oldKey = $beforeOrderData->delivery_address_kbn. '_'. $beforeOrderData->delivery_address_id;
            $newKey = $params['delivery_address_kbn']. '_'. $params['delivery_address_id'];

            if(Common::isFlgOn($params['own_stock_flg'])){
                // 当社在庫品
                if($newKey !== $oldKey){
                    // 入荷先に変更あり
                    // 自身の入荷済数をチェック
                    foreach($beforeOrderDetailList as $orderDetailData){
                        if($orderDetailData->arrival_quantity >= 1){
                            throw new \Exception(config('message.error.order_detail.change_delivery_address_arrival_complete'));
                        }
                    }
                }

                // 商品変更時のチェック
                $this->chkShipmentArrival($params['quote_no'], $gridDataList);
                // 入荷予定日のチェック
                $this->chkArrivalPlanDate($gridDataList, $beforeOrderDetailList);
                // // 発注ヘッダーの更新
                // $this->updateOrder($params);
                // 見積明細登録/更新
                $saveQuoteDetailData = $this->updateOrInsertQuoteDetail($params['quote_no'], $gridDataList, $params['customer_id'], false);
                // 発注明細の削除
                $this->deleteOrderDetail($gridDataList);
                // 発注明細の登録/更新
                $orderCostTotal = $this->updateOrInsertOrderDetail($saveQuoteDetailData['savedQuoteDetailDataList'], $params['id'], $params['order_no'], true);
                // // 見積の情報を発注に反映する
                // $this->OrderServiceProvider->updateQuoteToOrder($params['quote_no']);
                
                // 発注ヘッダーの更新
                $params['cost_total'] = $orderCostTotal;
                $this->updateOrder($params, true);
            }else{
                $createReserveFlg = $beforeOrderData->status === config('const.orderStatus.val.ordered');
                if($newKey !== $oldKey){
                    // 入荷先に変更あり
                    // 入荷済と出荷積込データがあれば変更させない
                    $shipmentDetailDataList = $ShipmentDetail->getByOrderId($params['id']);
                    foreach($shipmentDetailDataList as $shipmentDetail){
                        if($shipmentDetail->loading_finish_flg === config('const.flg.on')){
                            throw new \Exception(config('message.error.order_detail.change_delivery_address_loading_finish'));
                        }
                    }
                    // 自身の入荷済数をチェック
                    foreach($beforeOrderDetailList as $orderDetailData){
                        if($orderDetailData->arrival_quantity >= 1){
                            throw new \Exception(config('message.error.order_detail.change_delivery_address_arrival_complete'));
                        }
                    }
                }

                // 元の入荷先区分
                switch($beforeOrderData->delivery_address_kbn){
                    case config('const.deliveryAddressKbn.val.site'):
                    case config('const.deliveryAddressKbn.val.supplier'):
                        // メーカー倉庫
                        // 直送
                        if($params['delivery_address_kbn'] === config('const.deliveryAddressKbn.val.company')){
                            // 自社倉庫に変更
                            // 引当作成
                        }else{
                            $createReserveFlg = false;
                        }
                        break;
                    case config('const.deliveryAddressKbn.val.company'):
                        // 自社倉庫
                        if($params['delivery_address_kbn'] === config('const.deliveryAddressKbn.val.site') || $params['delivery_address_kbn'] === config('const.deliveryAddressKbn.val.supplier')){
                            // 直送 or　メーカーに変更
                            // 引当/出荷指示 削除
                            $createReserveFlg = false;
                            $isValidChkChangeDeliveryAddressKbn = true;
                            $Reserve->deleteByOrderData($params['id']);
                        }else{
                            // 自社倉庫のまま
                            // 引当の出荷元倉庫更新 出荷指示削除
                            if($params['delivery_address_id'] !== $beforeOrderData->delivery_address_id){
                                $reserveList = $Reserve->getByOrderId($params['id'])->toArray();
                                foreach($reserveList as $reserveData){
                                    // 引当の出荷元倉庫更新
                                    $reserveData['from_warehouse_id'] = $params['delivery_address_id'];
                                    $Reserve->updateById($reserveData);
                                }
                                foreach($reserveList as $reserveData){
                                    // 出荷指示削除
                                    $ShipmentDetail->deleteByReserveId($reserveData['id']);
                                }
                            }
                            
                        }
                        break;
                }

                // 変更前の見積版を取得
                $beforeQuoteVersionData = $QuoteVersion->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
                // 変更前の納品を取得
                $deliveryDataList       = $QuoteDetail->getDeliveryDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();
                // 変更前の売上明細を取得
                $salesDetailDataList    = $QuoteDetail->getSalesDetailDataList($params['quote_no'], config('const.quoteCompVersion.number'))->toArray();

                // 商品変更時のチェック
                $this->chkShipmentArrival($params['quote_no'], $gridDataList);
                // 入荷予定日のチェック
                $this->chkArrivalPlanDate($gridDataList, $beforeOrderDetailList);
                // 入荷予定日に今日以前の日付がないかチェック
                if($isValidChkChangeDeliveryAddressKbn){
                    $this->chkChangeDeliveryAddressKbn($gridDataList);
                }

                // 発注ヘッダーの更新
                $this->updateOrder($params);
                // 見積明細登録/更新
                $saveQuoteDetailData = $this->updateOrInsertQuoteDetail($params['quote_no'], $gridDataList, $params['customer_id'], true);
                // 発注明細の削除
                $this->deleteOrderDetail($gridDataList);
                // 発注明細の登録/更新
                $this->updateOrInsertOrderDetail($saveQuoteDetailData['savedQuoteDetailDataList'], $params['id'], $params['order_no']);
                // 一式配下の見積明細の連番を更新
                foreach($saveQuoteDetailData['insertSetProductInfo'] as $quoteDetailId => $data){
                    $childPartsList = $QuoteDetail->getChildQuoteDetailData($params['quote_no'], $quoteDetailId)->toArray();
                    $seqNo = 0;
                    foreach($childPartsList as $row){
                        $row['seq_no'] = ++$seqNo;
                        $QuoteDetail->updateByIdEx($row['id'], $row);
                    }
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

                // 引当の作成
                if ($createReserveFlg) {
                    $notReserveOrderDetailDataList = $OrderDetail->getNotReserveOrderData($params['id'])->toArray();
                    // 引当データ登録
                    $reserveData = [];
                    foreach ($notReserveOrderDetailDataList as $key => $orderDetail) {
                        if($orderDetail['intangible_flg'] === config('const.flg.off')){
                            $rec = [];
                            $rec['stock_flg']       = config('const.stockFlg.val.order');
                            $rec['matter_id']       = $params['matter_id'];
                            $rec['order_id']        = $params['id'];
                            $rec['order_no']        = $params['order_no'];
                            $rec['order_detail_id'] = $orderDetail['order_detail_id'];
                            $rec['quote_id']        = $params['quote_id'];
                            $rec['quote_detail_id'] = $orderDetail['quote_detail_id'];
                            $rec['product_id']      = $orderDetail['product_id'];
                            $rec['product_code']    = $orderDetail['product_code'];
                            $rec['from_warehouse_id']   = $params['delivery_address_id'];
                            $rec['reserve_quantity']= $orderDetail['stock_quantity'];
                            $rec['reserve_quantity_validity']   = $rec['reserve_quantity'];
                            $rec['issue_quantity']  = 0;
                            $rec['finish_flg']      = config('const.flg.off');

                            $reserveData[]          = $rec;
                        }
                    }
                    if(count($reserveData) >= 1){
                        if (!$Reserve->addList($reserveData)) {
                            throw new \Exception(config('message.error.save'));
                        }
                    }
                }

                // 見積の粗利率が基準値を下回った場合の処理
                $SystemUtil->quoteFallBelowGrossProfitRateProcessing($params['quote_no'], $beforeQuoteVersionData);

                // 変更後の見積明細
                $afterQuoteDetailData = $QuoteDetail->getByVer($params['quote_no'], config('const.quoteCompVersion.number'));
                // 納品データのチェック
                $this->OrderServiceProvider->isValidQuoteChangeState($deliveryDataList, $afterQuoteDetailData, true);
                // 売上明細データのチェック
                $this->OrderServiceProvider->isValidQuoteChangeState($salesDetailDataList, $afterQuoteDetailData, false);

            }

            $path = $this->OrderServiceProvider->getUploadFilePath($params['matter_id'], $params['id']);
            $deleteFiles = json_decode($params['delete_file_list']);
            // ファイル削除
            $this->OrderServiceProvider->deleteFile($path, $deleteFiles);

            // ファイルアップロード
            // 添付ファイル
            $uploadFileList = $request->files;
            foreach ($uploadFileList as $fileNm => $fileObj) {
                $request->file($fileNm)->storeAs($path, $fileObj->getClientOriginalName());
            }

            // 入荷完了フラグ判定
            $Order->judgeArrivalCompleteFlg($params['id']);

            // 変更後の発注明細
            $afterOrderDetailList = $OrderDetail->getByOrderId($params['id'])->keyBy('id')->toArray();
            $beforeOrderDetailList = $beforeOrderDetailList->keyBy('id')->toArray();
            // 変更履歴の登録
            foreach($beforeOrderDetailList as $key => $record){
                if(isset($afterOrderDetailList[$key])){
                    if (!$SystemUtil->addUpdateHistory(
                        config('const.updateHistoryKbn.val.order_detail'),
                        intval($params['id']),
                        $record,
                        $afterOrderDetailList[$key]
                    )) {
                        throw new \Exception(config('message.error.save'));
                    }
                }
            }

            // ロック解除
            $this->OrderServiceProvider->unLock($params['quote_id']);

            DB::commit();
            $result['status'] = true;
            $result['message'] = config('message.success.save');
            Session::flash('flash_success', config('message.success.save'));
           
        } catch (\Exception $e) {
            DB::rollBack();
            $result['message'] = $e->getMessage();
            $result['status'] = false;
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * 商品変更のチェック【全発注】
     * @param string $quoteNo   見積番号
     * @param array $gridData グリッドのデータ
     */
    private function chkShipmentArrival($quoteNo, $gridData){
        $QuoteDetail    = new QuoteDetail();
        // 更新前の出荷指示を取得
        $shipmentDataList   = $QuoteDetail->getShipmentDataList($quoteNo, config('const.quoteCompVersion.number'));
        // 更新前の発注明細を取得
        $orderDetailDataList= $QuoteDetail->getOrderDetailList($quoteNo, config('const.quoteCompVersion.number'));

        foreach ($gridData as $row) {
            $quoteDetailData = json_decode(json_encode($row), true);

            if (Common::nullorBlankToZero($quoteDetailData['quote_detail_id']) !== 0) {
                if(!$quoteDetailData['is_delete']){
                    // 更新
                    $quoteDetailShipment = $shipmentDataList->where('quote_detail_id', $quoteDetailData['quote_detail_id']);
                    $orderDetailData = $orderDetailDataList->where('quote_detail_id', $quoteDetailData['quote_detail_id']);
                    
                    // 発注明細で入荷済み数が入っている状態での商品変更 or 入荷済みの階層データが存在すればエラー(階層化エラー)
                    if($orderDetailData->count() >= 1){
                        if($quoteDetailData['product_id'] !== $orderDetailData->first()->product_id){
                            foreach($orderDetailData as $rowOrderDetailData){
                                if($rowOrderDetailData->arrival_quantity >= 1){
                                    throw new \Exception(str_replace('$product_code', $rowOrderDetailData->product_code, config('message.error.order.arrival_complete')));
                                }
                            }
                        }
                    }
                    // 出荷指示の出荷積込フラグが立っていればエラー or 出荷積込フラグが立っている階層データが存在すればエラー(階層化エラー)
                    if($quoteDetailShipment->count() >= 1){
                        if($quoteDetailData['product_id'] !== $quoteDetailShipment->first()->product_id){
                            foreach($quoteDetailShipment as $rowShipment){
                                if($rowShipment->loading_finish_flg === config('const.flg.on')){
                                    throw new \Exception(str_replace('$product_code', $rowShipment->product_code, config('message.error.order.loading_finish')));
                                }
                            }
                        }
                    }

                }
            }
        }
    }

    /**
     * 入荷予定日のチェック【該当発注のみ】
     * 入荷済みの状態で入荷日の変更はNG
     * @param array $gridData           グリッドのデータ
     * @param array $orderDetailData    変更前の発注明細データ
     */
    private function chkArrivalPlanDate($gridData, $orderDetailData){
        foreach ($gridData as $row) {
            $quoteDetailData = json_decode(json_encode($row), true);
            if(!$quoteDetailData['is_delete']){
                if (Common::nullorBlankToZero($quoteDetailData['id']) !== 0) {
                    $orderDetail = $orderDetailData->firstWhere('id', $quoteDetailData['id']);
                    $beforeArrivalPlanDate  = Common::nullToBlank($orderDetail->arrival_plan_date);
                    $afterArrivalPlanDate   = Common::nullToBlank($quoteDetailData['arrival_plan_date']);
                    if($orderDetail->arrival_quantity >= 1){
                        if($beforeArrivalPlanDate !== $afterArrivalPlanDate){
                            $beforeArrivalPlanDate = (new Carbon($beforeArrivalPlanDate));
                            $afterArrivalPlanDate  = (new Carbon($afterArrivalPlanDate));
                            if(!$beforeArrivalPlanDate->eq($afterArrivalPlanDate)){
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order_detail.change_arrival_plan_date')));
                            }
                        }
                    }
                }
            }
        }
    }

    /**
     * 自社倉庫から直送/メーカーに変更した際に入荷予定日をチェック【該当発注のみ】
     * @param array $gridData           グリッドのデータ
     */
    private function chkChangeDeliveryAddressKbn($gridData){
        $toDay = Carbon::today();
        foreach ($gridData as $row) {
            $quoteDetailData = json_decode(json_encode($row), true);
            if(!$quoteDetailData['is_delete']){
                $arrivalPlanDate   = Common::nullToBlank($quoteDetailData['arrival_plan_date']);
                if($arrivalPlanDate != ''){
                    $arrivalPlanDate  = (new Carbon($arrivalPlanDate.'00:00:00'));
                    if($arrivalPlanDate->lte($toDay)){
                        throw new \Exception(config('message.error.order_detail.change_delivery_address_kbn_arrival_plan_date'));
                    }
                }
            }
        }
    }

    /**
     * 発注ヘッダーの更新
     *
     * @param array $params
     * @param boolean $isOwnFlg
     * @return void
     */
    private function updateOrder($params, $isOwnFlg = false){
        $Order  = new Order();
        // 発注ヘッダーの更新
        $orderData = [];
        $orderData['id']                    = $params['id'];
        $orderData['account_customer_name'] = $params['account_customer_name'];
        $orderData['account_owner_name']    = $params['account_owner_name'];
        $orderData['delivery_address_id']   = $params['delivery_address_id'];
        $orderData['delivery_address_kbn']  = $params['delivery_address_kbn'];
        $orderData['map_print_flg']         = $params['map_print_flg'];
        $orderData['person_id']             = $params['person_id'];
        $orderData['sales_support_comment'] = $params['sales_support_comment'];
        $orderData['supplier_comment']      = $params['supplier_comment'];
        $orderData['desired_delivery_date'] = empty($params['desired_delivery_date']) ? null : $params['desired_delivery_date'];

        if ($isOwnFlg) {
            $orderData['cost_total']        = $params['cost_total'];
        }

        if (!$Order->updateById($orderData)) {
            throw new \Exception(config('message.error.save'));
        }
    }

    /**
     * 見積もり明細の登録/更新
     * @param string $quoteNo   見積番号
     * @param array $gridData グリッドのデータ
     * @param array $customerId 得意先ID
     * @param bool $saveProductFlg 商品マスタ登録更新を行うか
     * @return $savedDetailIds  保存登録したフィルターパスごとの見積明細IDリスト
     */
    private function updateOrInsertQuoteDetail($quoteNo, $gridData, $customerId, $saveProductFlg){
        $result = [];
        $result['savedQuoteDetailDataList'] = [];
        $result['insertSetProductInfo'] = [];

        $General        = new General();
        $QuoteDetail    = new QuoteDetail();
        $SystemUtil     = new SystemUtil();
        $Product        = new Product();

        // 品番なしの商品番号取得
        $noProductCodeData = $General->getCategoryList(config('const.general.noproductcode'))->first();
        $noProductCode = $noProductCodeData->value_text_1;

        // システム日時（今日）
        $toDay = new Carbon();
        
        $productList = null;
        $autoProductList = null;
        if ($saveProductFlg) {
            // 見積0版で使用されている商品データを一括取得
            $productList = $QuoteDetail->getProductsByQuoteVersion($quoteNo, config('const.quoteCompVersion.number'));
            // グリッド上で使用されている1回限り登録の商品データを取得
            $autoProductList = $productList->where('auto_flg', config('const.flg.on'))->keyBy('id');
        } else {
            // グリッド上で使用されている商品IDの商品データを一括取得
            $productIdList = array_column($gridData, "product_id");
            $productList = $Product->getByIds($productIdList);
            // グリッド上で使用されている1回限り登録の商品データを取得
            $autoProductList = $productList->where('auto_flg', config('const.flg.on'))->keyBy('id');
        }

        // 全一式商品データ
        $setProductList     = $QuoteDetail->getSetProductList($quoteNo, config('const.quoteCompVersion.number'));

        // 更新前の発注明細を取得
        $orderDetailDataList = $QuoteDetail->getOrderDetailList($quoteNo, config('const.quoteCompVersion.number'));

        foreach ($gridData as $row) {
            $quoteDetailData = json_decode(json_encode($row), true);

            // 発注取消以外
            if(!$quoteDetailData['is_delete']){
                // 紐づく発注明細に入荷済みが存在するか判定
                $isArrivedFlg = false;
                if (isset($quoteDetailData['quote_detail_id'])) {
                    $orderDetails = $orderDetailDataList->where('quote_detail_id', $quoteDetailData['quote_detail_id']);
                    foreach ($orderDetails as $rec) {
                        if ($rec->arrival_quantity > 0) {
                            $isArrivedFlg = true;
                        }
                    }
                }

                // 商品マスタ登録更新
                $resultSaveProduct = $this->OrderServiceProvider->saveProduct($quoteDetailData, $productList, $autoProductList, $noProductCode, $toDay, $isArrivedFlg, $saveProductFlg, true);
                $quoteDetailData = $resultSaveProduct['quoteDetailData'];
                $autoProductList = $resultSaveProduct['autoProductList'];

                $quoteDetailData['quote_no']        = $quoteNo;
                $quoteDetailData['quote_version']   = config('const.quoteCompVersion.number');

                // 子部品の場合は一式の情報をセットする
                if(Common::isFlgOn($quoteDetailData['child_parts_flg'])){
                    $parentQuoteDetailData = $setProductList->where('quote_detail_id', $quoteDetailData['parent_quote_detail_id'])->first();
                    $quoteDetailData['construction_id'] = $parentQuoteDetailData->construction_id;
                    $quoteDetailData['depth']           = intval($parentQuoteDetailData->depth) + 1;
                }

                // 管理数
                $quoteDetailData['stock_quantity'] = $SystemUtil->calcStock($quoteDetailData['quote_quantity'], $quoteDetailData['min_quantity']);
                // 仕入総額 = 数量 * 仕入単価
                $quoteDetailData['cost_total'] = $SystemUtil->calcTotal($quoteDetailData['quote_quantity'], $quoteDetailData['cost_unit_price'], true);
                // 販売総額 = 数量 * 販売単価
                $quoteDetailData['sales_total'] = $SystemUtil->calcTotal($quoteDetailData['quote_quantity'], $quoteDetailData['sales_unit_price'], false);
                // 粗利総額 = 販売総額 - 仕入総額
                $quoteDetailData['profit_total'] = $SystemUtil->calcProfitTotal($quoteDetailData['sales_total'], $quoteDetailData['cost_total']);
                // 粗利率 = 粗利総額 / 販売総額 * 100
                $quoteDetailData['gross_profit_rate'] = $SystemUtil->calcRate($quoteDetailData['profit_total'], $quoteDetailData['sales_total']); 

                $result['savedQuoteDetailDataList'][] = $quoteDetailData;
                
                if (Common::nullorBlankToZero($quoteDetailData['quote_detail_id']) !== 0) {
                    // 更新
                    unset($quoteDetailData['memo']);
                    $saveResult = $QuoteDetail->updateByIdEx($quoteDetailData['quote_detail_id'], $quoteDetailData, $customerId);
                    if (!$saveResult) {
                        throw new \Exception(config('message.error.save'));
                    }

                } else {
                    // 新規登録
                    $quoteDetailData['seq_no'] = 9999;
                    $quoteDetailData['row_print_flg'] = config('const.flg.on');
                    $detailId = $QuoteDetail->addEx($quoteDetailData, $customerId);
                    $result['savedQuoteDetailDataList'][count($result['savedQuoteDetailDataList'])-1]['quote_detail_id'] = $detailId;
                    $parentQuoteDetailId = $quoteDetailData['parent_quote_detail_id'];
                    // 連番を振りなおすための情報
                    if(!isset($result['insertSetProductInfo'][$parentQuoteDetailId])){
                        $result['insertSetProductInfo'][$parentQuoteDetailId] = [];
                        $result['insertSetProductInfo'][$parentQuoteDetailId]['depth'] = $quoteDetailData['depth'];
                        $result['insertSetProductInfo'][$parentQuoteDetailId]['construction_id'] = $quoteDetailData['construction_id'];
                    }
                }
            }

        }
        return $result;
    }

    /**
     * 発注明細の削除【該当発注のみ】
     * 　トリガー：取消ボタン/一式化
     * 　発注引当 それに紐づく出荷指示の削除
     * @param $gridData
     */
    private function deleteOrderDetail($gridData){
        $OrderDetail    = new OrderDetail();
        $Reserve        = new Reserve();
        $ShipmentDetail = new ShipmentDetail();
        foreach ($gridData as $row) {
            $quoteDetailData = json_decode(json_encode($row), true);
            if(Common::nullorBlankToZero($quoteDetailData['id']) !== 0){
                // 発注データを取得
                $orderDetailData = $OrderDetail->getById($quoteDetailData['id']);
                // 取消ボタン or 一式明細に対して階層化を行った発注明細データ
                if($quoteDetailData['is_delete'] || (Common::isFlgOn($quoteDetailData['layer_flg']) && Common::isFlgOn($quoteDetailData['set_flg']))){
                    // 発注明細に紐づく出荷指示明細の出荷積込をチェック
                    $shipmentDetailDataList = $ShipmentDetail->getByOrderDetailId($quoteDetailData['id']);
                    foreach($shipmentDetailDataList as $shipmentDetail){
                        if($shipmentDetail->loading_finish_flg === config('const.flg.on')){
                            throw new \Exception(str_replace('$product_code', $shipmentDetail->product_code, config('message.error.order_detail.delete_loading_finish')));
                        }
                    }
                    // 自身の入荷済数をチェック
                    if($orderDetailData->arrival_quantity >= 1){
                        throw new \Exception(str_replace('$product_code', $orderDetailData->product_code, config('message.error.order_detail.delete_arrival_complete')));
                    }

                    if(!$OrderDetail->physicalDeleteListByIds([$orderDetailData->id])){
                        throw new \Exception(config('message.error.save'));
                    }
                    // 引当と出荷指示削除
                    $Reserve->deleteByOrderData($orderDetailData->order_id, $orderDetailData->id);
                }
            }
        }
    }

    /**
     * 発注明細の登録【該当発注のみ】
     *
     * @param array $gridData
     * @param int $orderId
     * @param string $orderNo
     * @param boolean $isOwnFlg
     * @return $orderCostTotal（当社在庫発注の場合のみ戻り値が有効）
     */
    private function updateOrInsertOrderDetail($gridData, $orderId, $orderNo, $isOwnFlg = false){
        $orderCostTotal = 0;

        $OrderDetail    = new OrderDetail();
        $ShipmentDetail = new ShipmentDetail();
        $Reserve        = new Reserve();
        $SystemUtil     = new SystemUtil();
        $saveOrderDetail = [];
        $seqNo = 0;
        // // 適用開始日のチェック(階層を含んでいても商品IDが無いので問題無い)
        // $this->OrderServiceProvider->productStartDateIsValid($gridData, false);

        foreach ($gridData as $row) {
            if(Common::nullorBlankToZero($row['id']) === 0 && !Common::isFlgOn($row['layer_flg'])){
                // 発注明細IDを持たない明細行 ⇒INSERTする
                $seqNo++;
                $orderDetailRecord = [];
                $orderDetailRecord['add_kbn']       = config('const.orderDetailAddKbn.val.order_detail');
                $orderDetailRecord['order_id']      = $orderId;
                $orderDetailRecord['order_no']      = $orderNo;
                $orderDetailRecord['seq_no']        = $seqNo;
                $orderDetailRecord['quote_detail_id']   = $row['quote_detail_id'];
                $orderDetailRecord['product_id']    = $row['product_id'];
                $orderDetailRecord['product_code']  = $row['product_code'];
                $orderDetailRecord['product_name']  = $row['product_name'];
                $orderDetailRecord['model']         = $row['model'];
                $orderDetailRecord['maker_id']      = $row['maker_id'];
                $orderDetailRecord['maker_name']    = $row['maker_name'];
                $orderDetailRecord['supplier_id']   = $row['supplier_id'];
                $orderDetailRecord['supplier_name'] = $row['supplier_name'];
                $orderDetailRecord['order_quantity'] = $row['order_quantity'];
                $orderDetailRecord['min_quantity']  = $row['min_quantity'];
                $orderDetailRecord['stock_quantity'] = $SystemUtil->calcStock($row['order_quantity'], $row['min_quantity']);
                $orderDetailRecord['unit']          = $row['unit'];
                $orderDetailRecord['stock_unit']    = $row['stock_unit'];
                $orderDetailRecord['regular_price'] = $row['regular_price'];
                $orderDetailRecord['sales_kbn']     = $row['sales_kbn'];
                $orderDetailRecord['cost_unit_price']   = $row['cost_unit_price'];
                $orderDetailRecord['sales_unit_price']  = $row['sales_unit_price'];
                $orderDetailRecord['cost_makeup_rate']  = $row['cost_makeup_rate'];
                $orderDetailRecord['sales_makeup_rate'] = $row['sales_makeup_rate'];
                $orderDetailRecord['cost_total']        = $SystemUtil->calcTotal($row['order_quantity'], $row['cost_unit_price'], true);
                $orderDetailRecord['sales_total']       = $SystemUtil->calcTotal($row['order_quantity'], $row['sales_unit_price'], false);
                $orderDetailRecord['profit_total']      = $SystemUtil->calcProfitTotal($orderDetailRecord['sales_total'], $orderDetailRecord['cost_total']);
                $orderDetailRecord['gross_profit_rate'] = $SystemUtil->calcRate($orderDetailRecord['profit_total'], $orderDetailRecord['sales_total']); 
                $orderDetailRecord['hope_arrival_plan_date']    = empty($row['hope_arrival_plan_date']) ? null : $row['hope_arrival_plan_date'];
                $orderDetailRecord['arrival_plan_date'] = empty($row['arrival_plan_date']) ? null : $row['arrival_plan_date'];
                $orderDetailRecord['arrival_quantity']  = 0;
                $orderDetailRecord['memo']  = $row['memo'];
                $orderDetailRecord['parent_order_detail_id']    = 0;
                $saveOrderDetail[] = $orderDetailRecord;
            }else{
                if(Common::nullorBlankToZero($row['id']) !== 0 && !Common::isFlgOn($row['layer_flg'])){
                    // 発注明細IDを持つ明細行 ⇒UPDATEする
                    $orderDetailData = $OrderDetail->getById($row['id']);
                    $seqNo++;
                    $orderDetailRecord = [];
                    $orderDetailRecord['id']            = $row['id'];
                    $orderDetailRecord['product_id']    = $row['product_id']; // 入荷予定日をクリアさせない
                    $orderDetailRecord['seq_no']        = $seqNo;
                    $orderDetailRecord['order_quantity'] = $row['order_quantity'];
                    $orderDetailRecord['stock_quantity'] = $SystemUtil->calcStock($row['order_quantity'], $row['min_quantity']);
                    $orderDetailRecord['hope_arrival_plan_date']    = empty($row['hope_arrival_plan_date']) ? null : $row['hope_arrival_plan_date'];
                    $orderDetailRecord['arrival_plan_date']         = empty($row['arrival_plan_date']) ? null : $row['arrival_plan_date'];
                    $orderDetailRecord['memo']          = $row['memo'];

                    if ($isOwnFlg) {
                        $orderDetailRecord['product_code']  = $row['product_code'];
                        $orderDetailRecord['product_name']  = $row['product_name'];
                        $orderDetailRecord['model']         = $row['model'];
                        // $orderDetailRecord['maker_id']      = $row['maker_id'];
                        // $orderDetailRecord['maker_name']    = $row['maker_name'];
                        // $orderDetailRecord['supplier_id']   = $row['supplier_id'];
                        // $orderDetailRecord['supplier_name'] = $row['supplier_name'];
                        $orderDetailRecord['unit']          = $row['unit'];
                        $orderDetailRecord['stock_unit']    = $row['stock_unit'];
                        $orderDetailRecord['regular_price'] = $row['regular_price'];
                        $orderDetailRecord['sales_kbn']     = $row['sales_kbn'];
                        $orderDetailRecord['cost_unit_price']   = $row['cost_unit_price'];
                        $orderDetailRecord['sales_unit_price']  = $row['sales_unit_price'];
                        $orderDetailRecord['cost_makeup_rate']  = $row['cost_makeup_rate'];
                        $orderDetailRecord['sales_makeup_rate'] = $row['sales_makeup_rate'];
                        // 仕入総額 = 数量 * 仕入単価
                        $orderDetailRecord['cost_total']        = $SystemUtil->calcTotal($row['order_quantity'], $orderDetailRecord['cost_unit_price'], true);
                        // 販売総額 = 数量 * 販売単価
                        $orderDetailRecord['sales_total']       = $SystemUtil->calcTotal($row['order_quantity'], $orderDetailRecord['sales_unit_price'], false);
                        // 粗利総額 = 販売総額 - 仕入総額
                        $orderDetailRecord['profit_total']      = $SystemUtil->calcProfitTotal($orderDetailRecord['sales_total'], $orderDetailRecord['cost_total']);
                        // 粗利率 = 粗利総額 / 販売総額 * 100
                        $orderDetailRecord['gross_profit_rate'] = $SystemUtil->calcRate($orderDetailRecord['profit_total'], $orderDetailRecord['sales_total']);

                        $orderCostTotal += $orderDetailRecord['cost_total'];
                    }

                    $OrderDetail->updateById($orderDetailRecord);
                    if(((double)$orderDetailData->order_quantity) !== ((double)$orderDetailRecord['order_quantity'])){
                        // 発注数を変更した
                        // 発注明細に紐づく出荷指示明細の出荷積込をチェック
                        $shipmentDetailDataList = $ShipmentDetail->getByOrderDetailId($orderDetailData->id);
                        foreach($shipmentDetailDataList as $shipmentDetail){
                            if($shipmentDetail->loading_finish_flg === config('const.flg.on')){
                                throw new \Exception(str_replace('$product_code', $shipmentDetail->product_code, config('message.error.order_detail.change_order_quantity_loading_finish')));
                            }
                        }
                        // 自身の入荷済数をチェック
                        if(intval($orderDetailData->arrival_quantity) >= 1){
                            throw new \Exception(str_replace('$product_code', $orderDetailData->product_code, config('message.error.order_detail.change_order_quantity_arrival_complete')));
                        }
                        // 引当と出荷指示削除
                        $Reserve->deleteByOrderData($orderDetailData->order_id, $orderDetailData->id);
                    }
                }
            }
        }
        if(count($saveOrderDetail) >= 1){
            if(!$OrderDetail->addList($saveOrderDetail)){
                throw new \Exception(config('message.error.save'));
            }
        }

        return $orderCostTotal;
    }

    /**
     * ファイルが存在するか
     *
     * @param Request $request
     * @param int $matterId
     * @param int $orderId
     * @param string $fileName
     * @return void
     */
    public function existsFile(Request $request, $matterId, $orderId, $fileName){
        $fileName = urldecode($fileName);
        $result = Storage::exists($this->OrderServiceProvider->getUploadFilePath($matterId, $orderId).'/'.$fileName);
        return \Response::json($result);
    }

    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param int $matterId
     * @param int $orderId
     * @param string $fileName
     * @return void
     */
    public function download(Request $request, $matterId, $orderId, $fileName){
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path($this->OrderServiceProvider->getUploadFilePath($matterId, $orderId).'/'.$fileName), $fileName, $headers);
    }

    /**
     * 住所を取得
     * 
     * @param Request $request
     */
    public function getMatterAddress(Request $request){
        // リクエストデータ取得
        $params = $request->request->all();

        $matter = Matter::find($params['matter_id']);

        $value['matter_address']    = Address::find($matter->address_id);
        $value['delivery_address']  =  $this->OrderServiceProvider->getDeliveryAddressList($params['supplier_id'], $matter->address_id);

        return \Response::json($value);
    }

    /**
     * 受注確定データ検索
     *
     * @param Request $request
     * @return void
     */
    public function searchReceivedOrder(Request $request){
        // リクエストデータ取得
        $params = $request->request->all();

        // 受注確定データ
        $QuoteDetail    = new QuoteDetail();
        $receivedOrderData = $QuoteDetail->getReceivedOrderListForOrderDetail($params)->toArray();
        foreach($receivedOrderData as $key => $row){
            $receivedOrderData[$key]['reserve_quantity'] = (Common::nullorBlankToZero($row['order_quantity']) + Common::nullorBlankToZero($row['reserve_quantity']) - Common::nullorBlankToZero($row['return_quantity']));
        }
        return \Response::json($receivedOrderData);
    }

    /**
     * csvを配列にして返す
     * 列のマッピング情報を返す
     * 商品情報のリストを返す
     * 商品単価リストを返す
     *
     * @param Request $request
     * @return void
     */
    public function csvToArray(Request $request){
        $result = [
            'csv'=>[],
            'colNumberList'         => [],
            'productList'           => [],
            //'costProductPriceList'  => [],
            'salesProductPriceList' => [],
        ];

        $SystemUtil = new SystemUtil();
        $Product    = new Product();


        // リクエストデータ取得
        $file = $request->file('child_parts_file');
        $params =  $request->all();
        $colNameList = json_decode($params['col_name_list']);
        foreach($colNameList as $physicalName => $colName){
            // カラム位置情報初期化
            $result['colNumberList'][$physicalName] = -1;
        }

        // CSVを配列に変換
        $csv = $SystemUtil->csvToArray($file);

        foreach($csv as $rowNumber => $row){
            // スペース除去
            foreach($row as $colNumber => $data){
                $str = preg_replace('/\s+/', '', $data);
                $csv[$rowNumber][$colNumber] = $str;
            }

            if($rowNumber === 0){
                foreach($colNameList as $physicalName => $colName){
                    // カラム名称からカラムの位置を特定
                    $index = array_search($colName, $csv[$rowNumber]);
                    if($index !== false){
                        $result['colNumberList'][$physicalName] = $index;
                    }
                }
            }
        }
        
        // 商品コードカラムの位置を保持
        $productCodeNumber = $result['colNumberList']['product_code'];
        
        
        if($productCodeNumber !== -1){
            // 商品コードカラムが存在する
            $productCodeList = [];
            foreach($csv as $rowNumber => $row){
                if($rowNumber === 0){
                    continue;
                }

                if(isset($row[$productCodeNumber]) && Common::nullToBlank($row[$productCodeNumber]) !== ''){
                    $productCodeList[] = $row[$productCodeNumber];
                }
            }

            // CSVファイル内の商品コードのリストから商品マスタを検索して商品情報を取得
            $tmpProductList = $Product->getByProductCodes($productCodeList, null, config('const.flg.off'));
            // 該当商品コードとメーカーに一致する商品情報取得
            $result['productList'] = $tmpProductList->where('maker_id', $params['maker_id'])->toArray();

            $productIdList = [];
            foreach($result['productList'] as $row){
                $productIdList[] = $row['product_id'];
            }
            // 商品IDから単価情報を取得する
            $priceList = $Product->getPriceData($params['customer_id'], $productIdList);
            foreach ($priceList as $record) {
                if ($record->cost_sales_kbn === config('const.costSalesKbn.cost')) {
                    // 仕入単価
                    //$result['costProductPriceList'][$record->product_id][$record->unit_price_kbn] = $record;
                } else {
                    // 販売単価
                    $result['salesProductPriceList'][$record->product_id][$record->unit_price_kbn] = $record;
                }
            }
        }


        $result['csv'] = $csv;
        return \Response::json($result);
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
            unset($columns['seq_no']);
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

    // /**
    //  * 発注明細を色々処理
    //  *
    //  * @param [collection] $gridData[発注明細ID]
    //  * @param [array] $quoteDetailIDs
    //  * @return void
    //  */
    // private function makeOrderDetailData($gridData, $quoteDetailIDs){
    //     // キーを発注明細.IDに変更
    //     $gridData = $gridData->keyBy('id');

    //     // グリッド上の発注明細と紐づいている出荷指示がある場合は更新NG
    //     if (Delivery::whereIn('order_detail_id', $gridData->pluck('id')->toArray())->exists()) {
    //         throw new \Exception(config('message.error.department.save_shipment_detail'));
    //     }

    //     // 同一の見積明細IDを参照している発注明細を取得（キーを発注明細.IDに変更）
    //     $orderDetails = OrderDetail::whereIn('quote_detail_id', $quoteDetailIDs)->get();
    //     $orderDetails = $orderDetails->keyBy('id');
    //     // 発注明細に紐づく発注を取得（キーを発注.IDに変更）
    //     $orders = Order::WhereIn('id', $orderDetails->pluck('order_id')->unique()->toArray())->get();
    //     $orders = $orders->keyBy('id');
    //     // 発注明細に紐づく発注を取得（キーを納品.IDに変更）
    //     $deliveries = Delivery::WhereIn('id', $orderDetails->pluck('id')->unique()->toArray())->get();
    //     $deliveries = $deliveries->keyBy('id');

    //     $data = collect();
    //     foreach ($orderDetails as $key => $oDetail) {
    //         // 同一の見積明細を参照しているグリッドデータを取得(発注明細.見積明細ID)
    //         $gridRec = $gridData->where('quote_detail_id', $oDetail->quote_detail_id)->first(); 

    //         $rec = [];
    //         $rec['is_grid'] = false;
    //         $rec['is_add'] = false;
    //         $rec['is_delete'] = false;

    //         $rec['id'] = $oDetail->id;
    //         $rec['quote_detail_id'] = $oDetail->quote_detail_id;
    //         $rec['order_id'] = $oDetail->order_id;
    //         $rec['order_status'] = $orders[$oDetail->order_id]->status;

    //         $salesTotal = $oDetail->order_quantity * $gridRec->sales_unit_price;
    //         $costTotal = $oDetail->order_quantity * $oDetail->cost_unit_price;
    //         $profitTotal = $salesTotal - $costTotal;

    //         $rec['sales_kbn'] = $gridRec->sales_kbn;
    //         $rec['sales_unit_price'] = $gridRec->sales_unit_price;
    //         $rec['sales_makeup_rate'] = $gridRec->sales_makeup_rate;
    //         $rec['sales_total'] = $salesTotal;

    //         // 発注明細に紐づく納品データが無い場合は仕入も更新対象。
    //         // グリッドの値を使用するので粗利を再度処理。
    //         if ($deliveries->where('order_detail_id', $gridRec->id)->count() > 0) {
    //             $costTotal = $oDetail->order_quantity * $gridRec->cost_unit_price;
    //             $profitTotal = $salesTotal - $costTotal;

    //             $rec['cost_unit_price'] = $gridRec->cost_unit_price;
    //             $rec['cost_makeup_rate'] = $gridRec->cost_makeup_rate;
    //             $rec['cost_total'] = $costTotal;

    //         }
    //         $rec['profit_total'] = $profitTotal;
    //         $rec['gross_profit_rate'] = Common::ceil_plus($profitTotal / $salesTotal * 100, 2);

    //         // グリッドデータの場合は上書き
    //         if (isset($gridData[$oDetail->id])) {
    //             $rec['is_grid'] = true;
    //             $rec['is_add'] = $gridRec->is_add;
    //             $rec['is_delete'] = $gridRec->is_delete;
    //             $rec['order_quantity'] = $gridRec->order_quantity;
    //             $rec['sales_kbn'] = $gridRec->sales_kbn;
    //             $rec['cost_unit_price'] = $gridRec->cost_unit_price;
    //             $rec['sales_unit_price'] = $gridRec->sales_unit_price;
    //             $rec['cost_makeup_rate'] = $gridRec->cost_makeup_rate;
    //             $rec['sales_makeup_rate'] = $gridRec->sales_makeup_rate;
    //             $rec['cost_total'] = $gridRec->cost_total;
    //             $rec['sales_total'] = $gridRec->sales_total;
    //             $rec['gross_profit_rate'] = $gridRec->gross_profit_rate;
    //             $rec['profit_total'] = $gridRec->profit_total;
    //             $rec['hope_arrival_plan_date'] = $gridRec->hope_arrival_plan_date;
    //             $rec['arrival_plan_date'] = $gridRec->arrival_plan_date;
    //             $rec['memo'] = $gridRec->memo;
    //         }
            
    //         $data->put($key, $rec);
    //     }

    //     return $data;
    // }
}