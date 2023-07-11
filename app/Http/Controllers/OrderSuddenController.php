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
use App\Models\Customer;
use App\Models\General;
use App\Models\LockManage;
use App\Models\NumberManage;
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
// use App\Models\ShipmentDetail;
use App\Models\Supplier;
// use App\Models\Product;
use App\Models\SupplierMaker;
// use App\Models\ProductPrice;
// use App\Models\Reserve;
use App\Models\Construction;
// use App\Models\Department;
// use App\Models\ProductCampaignPrice;
use App\Models\Quote;
// use App\Models\Staff;
use App\Models\SupplierFile;
use App\Models\System;
// use App\Models\UpdateHistory;
use App\Models\ClassBig;
use App\Models\ClassMiddle;
use App\Providers\OrderServiceProvider;

/**
 * いきなり発注
 */
class OrderSuddenController extends Controller
{
    const SCREEN_NAME = 'order-sudden';
    private $quoteVerDefault =null;

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
        $this->OrderServiceProvider->initialize($this, true);
        $this->quoteVerDefault = [
            'quote_enabled_limit_date' => '１か月',
            'payment_condition' => config('const.paycon.usual'),
        ];
    }

    /**
     * 初期表示
     * @param Request $request
     * @param int $isStock 当社在庫品フラグ
     * @return type
     */
    public function index(Request $request)
    {
        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');
        $ownStockFlg = config('const.flg.off');
        if($request->route()->named('order.sudden.ownstock')){
            $ownStockFlg = config('const.flg.on');
        }

        $Customer = new Customer();
        $Matter     = new Matter();
        $QuoteLayer = new QuoteLayer();
        $QuoteDetail= new QuoteDetail();
        $LockManage = new LockManage();
        $Supplier   = new Supplier();
        $General    = new General();
        //$Product    = new Product();
        $SupplierMaker  = new SupplierMaker();
        $SupplierFile   = new SupplierFile();
        $Person     = new Person();
        $Order      = new Order();
        $Address    = new Address();
        $Construction = new Construction();
        $ClassBig   = new ClassBig();
        $ClassMiddle = new ClassMiddle();

        // モーダル項目
        $customerList   = null;
        $ownerList      = null;
        $customerOwnerList = null;
        $architectureList   = null;

        $matterModel    = null;
        $addressModel   = null;
        $orderModel = null;
        $qreqList   = null;
        $lockData   = $LockManage;
        $gridDataList   = [];
        $quoteLayerList = $QuoteLayer;

        $classBigList           = null;
        $classMiddleList        = null;
        $priceList              = null;
        $supplierList           = null;
        $supplierMakerList      = null;
        $supplierFileList       = null;
        $personList             = [];
        $deliveryAddressList    = null;

        DB::beginTransaction();
        try {
            // 得意先名リスト取得
            $customerList = $Customer->getComboList();
            // 施主名リスト取得
            $ownerList = $Matter->getOwnerList();
            // 顧客配下の施主名取得
            $customerOwnerList = $Matter->getCustomerOwnerList();
            // 建築種別リスト取得
            $architectureList = $General->getCategoryList(config('const.general.arch'));
            $addressModel = $Address;
            Common::initModelProperty($addressModel);
            // 発注
            $orderModel = $Order; 
            Common::initModelProperty($orderModel);
            $orderModel->maker_name = '';
            $orderModel->supplier_name = '';
            $orderModel->delivery_address = '';
            $orderModel->desired_delivery_date = null;
            $orderModel->map_print_flg = config('const.flg.off');

            // 初期表示の入荷先リスト
            $deliveryAddressList = $this->OrderServiceProvider->getDeliveryAddressList(null, 0);
            $deliveryAddressList = $this->cnvDeliveryAddressList($ownStockFlg, $deliveryAddressList);

            // 大分類リスト取得
            $classBigList = $ClassBig->getIdNameList();
            // 中分類リスト取得
            $classMiddleList = $ClassMiddle->getIdNameList();
            // 価格区分リスト取得
            $priceList = $General->getCategoryList(config('const.general.price'));

            // 見積依頼項目リスト取得
            $qreqList = $Construction->getQreqData(config('const.flg.on'));
            // トップ階層を取得
            $quoteLayerList = $QuoteDetail::getTopTreeData();

            $kbn = [config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.maker')];
            // メーカーリスト取得
            $makerList = $Supplier->getBySupplierKbn($kbn);
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

            // 商品コード無し取得
            $noProductCode = $General->getCategoryList(config('const.general.noproductcode'))->first();

            if(Common::isFlgOn($ownStockFlg)){
                // 当社在庫品
                $matterModel = $Matter->getOwnStockMatter();
                if (is_null($matterModel)) {
                    throw new NotFoundHttpException();
                }

                $matterModel->customer_name = config('const.LBL_TEXT.OWN_STOCK_CUSTOMER_NAME');
                // 価格区分リストを標準のみに変更
                //$priceList = $priceList->where('value_code', config('const.unitPriceKbn.normal'));
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($matterModel->id);

                $lockCnt = 0;
                foreach ($tableNameList as $i => $tableName) {
                    // ロック確認
                    $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                    if (!$isLocked) {
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
            }else{
                $matterModel = $Matter;
                Common::initModelProperty($matterModel);
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Order.order-sudden') 
                ->with('ownStockFlg', $ownStockFlg)
                ->with('customerList', $customerList)
                ->with('ownerList', $ownerList)
                ->with('customerOwnerList', $customerOwnerList)
                ->with('architectureList', $architectureList)
                ->with('matterModel', $matterModel)
                ->with('addressModel', $addressModel)
                ->with('deliveryAddressKbnList', json_encode(config('const.deliveryAddressKbn')))
                ->with('qreqList', $qreqList)
                
                ->with('orderModel',  $orderModel)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData',  $lockData)
                ->with('quoteLayerList',    json_encode($quoteLayerList))
                ->with('gridDataList',      json_encode($gridDataList))
                ->with('makerList',         $makerList)
                ->with('classBigList',      $classBigList)
                ->with('classMiddleList',   $classMiddleList)
                ->with('priceList',         $priceList)
                ->with('supplierList',      $supplierList)
                ->with('supplierMakerList', $supplierMakerList)
                ->with('supplierFileList', $supplierFileList)
                ->with('personList',        $personList)
                ->with('deliveryAddressList',   $deliveryAddressList)
                ->with('noProductCode',   $noProductCode)
                ;
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
        $deliveryAddressList = $this->cnvDeliveryAddressList($params['own_stock_flg'], $deliveryAddressList);
        return \Response::json($deliveryAddressList);
    }
    /**
     * いきなり発注画面の場合、案件が無いので直送を追加
     * @param $ownStockFlg　当社在庫品専用か
     * @return $list        入荷先リスト
     */
    public function cnvDeliveryAddressList($ownStockFlg, $list){
        if(!Common::isFlgOn($ownStockFlg)){
            // 当社在庫では無い場合、直送の選択肢を追加
            $data = [
                'unique_key'=>config('const.deliveryAddressKbn.val.site').'_'.'0',
                'id'=>0,
                'warehouse_name'=>config('const.directDeliveryAddressName'),
                'address'=>'',
                'delivery_address_kbn'=>config('const.deliveryAddressKbn.val.site'),
                'display_order'=>9999
            ];
            $list->push($data);
        }else{
            // 当社在庫の場合、自社倉庫のみ表示
            $list = $list->where('delivery_address_kbn', config('const.deliveryAddressKbn.val.company'));
        }
        return $list;
    }

    /**
     * 案件入力内容確認
     *
     * @param Request $request
     * @return void
     */
    public function confirmMatter(Request $request) {
        $result = [];
        
        // リクエストデータ取得
        $params = $request->request->all();
        // バリデーションチェック
        $this->validate($request, [
            'customer_id' => 'required',
            'owner_name' => 'required|max:255',
            'architecture_type' => 'required',
        ]);

        // 得意先データ取得
        $Customer = new Customer();
        $customerData = $Customer->getChargeData((int)$params['customer_id']);

        // 案件名生成
        $SystemUtil = new SystemUtil();
        $matterName = $SystemUtil->createMatterName($params['owner_name'], $customerData['customer_short_name']);

        // 案件データ取得
        $Matter = new Matter();
        $chkDuplicateData = [
            'id'            => null,
            'customer_id'   => $params['customer_id'],
            'owner_name'    => $params['owner_name'],
        ];

        $result['err_message'] = '';
        // 得意先マスタに必須項目が入力されているか
        $invalid = !$Customer->isValidReceivedOrder($params['customer_id']);
        if($invalid){
            $result['err_message'] = config('message.error.customer.received_order_not_input');
        }else{
            // 既に存在する案件か
            $invalid = $Matter->isDuplicate($chkDuplicateData);
            if($invalid){
                $result['err_message'] = config('message.error.order.order_sudden_exist_matter');
            }
        }
        $result['invalid'] = $invalid;

        // 返却値セット
        $result['matter_name'] = $matterName;
        $result['department_name'] = $customerData['department_name'];
        $result['staff_name'] = $customerData['staff_name'];
        $result['department_id'] = $customerData['charge_department_id'];
        $result['staff_id'] = $customerData['charge_staff_id'];
        $result['status'] = true;
        
        return \Response::json($result);
    }
    
    /**
     * 一時保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request){
        $result = array('status' => false, 'message' => '', 'id' => 0, 'matterId'=>0);
       
        $now = Carbon::now();
        // 添付ファイル
        $this->OrderServiceProvider->isValidFile($request);

        DB::beginTransaction();

        try{
            // 一時保存
            $tmpSaveResult = $this->temporarySave($request, $now, false);
            
            DB::commit();
            $result['status']   = true;
            $result['id']       = $tmpSaveResult['orderId'];
            $result['matterId'] = $tmpSaveResult['matterId'];
            //$result['message']  = config('message.success.save');
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
     * @return $result
     */
    private function temporarySave($request, $now, $isOrderExec){
        $result = [
            'matterId'=>0,
            'matterNo'=>'',
            'addressId'=>0,
            'quoteId'=>0,
            'quoteNo'=>'',
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
        $Address        = new Address();
        $Matter         = new Matter();
        $Quote          = new Quote();
        $NumberManage   = new NumberManage();
        $System         = new System();
        $Construction   = new Construction();
        

        // リクエストデータ取得
        $params = $request->request->all();

        // 添付ファイル
        $uploadFileList = $request->files;

        // 案件住所登録
        if(!empty($params['address1'])){
            $addressData = [];
            $addressData['zipcode']     = $params['zipcode'];
            $addressData['address1']    = $params['address1'];
            $addressData['address2']    = $params['address2'];
            $addressData['latitude_jp'] =  null;
            $addressData['longitude_jp']=  null;
            $addressData['latitude_world']  =  null;
            $addressData['longitude_world'] =  null;
            $result['addressId']            = $Address->add($addressData);
        }

        // 案件登録
        $matterData = [];
        $matterData['customer_id']      = $params['customer_id'];
        $matterData['department_id']    = $params['department_id'];
        $matterData['staff_id']         = $params['staff_id'];
        $matterData['owner_name']       = $params['owner_name'];
        $matterData['architecture_type']   = $params['architecture_type'];
        $result['matterId']             = $Matter->add($matterData);
        if($result['addressId'] !== 0){
            $Matter->updateForAddress($result['matterId'], $result['addressId']);
        }
        $tmpMatter = $Matter->getById($result['matterId']);
        $result['matterNo'] = $tmpMatter->matter_no;

        // 見積登録
        $quoteData = [];
        $quoteData['quote_no']      = $NumberManage->getSeqNo(config('const.number_manage.kbn.quote'), Carbon::today()->format('Ym'));
        $quoteData['matter_no']     = $result['matterNo'];
        $result['quoteId']          = $Quote->add($quoteData);
        $result['quoteNo']          = $quoteData['quote_no'];

        // 税率取得
        $taxInfo = $System->getByPeriod();
        // 見積版0版登録
        $quoteVerData = [];
        $quoteVerData['quote_no']           = $result['quoteNo'];
        $quoteVerData['quote_version']      = config('const.quoteCompVersion.number');
        $quoteVerData['caption']            = config('const.quoteCompVersion.caption');
        $quoteVerData['department_id']      = $matterData['department_id'];
        $quoteVerData['staff_id']           = $matterData['staff_id'];
        //$quoteVerData['quote_enabled_limit_date'] = $this->quoteVerDefault['quote_enabled_limit_date'];
        $quoteVerData['payment_condition']  = $this->quoteVerDefault['payment_condition'];
        $quoteVerData['tax_rate']           = $taxInfo->tax_rate;

        $quoteVerData['status']             = config('const.quoteVersionStatus.val.approved');
        $QuoteVersion->add($quoteVerData);

        // パラメータにセット
        $this->setRegisterData($params, $result);

        // 全てのグリッドデータ
        $gridAllDataList = json_decode($params['grid_all_data']);

        // 保存用に加工した発注ヘッダーデータを取得
        $orderSaveData = $this->OrderServiceProvider->getOrderSaveData($params, $now, true, $isOrderExec);
        // 一時保存
        $result['orderId'] = $Order->add($orderSaveData);
        
        
        if(!$result['orderId']){
            throw new \Exception(config('message.error.save'));
        }

        // 見積もり明細 登録/更新
        $updateOrInsertQuoteDetailResult = $this->OrderServiceProvider->updateOrInsertQuoteDetail($params['quote_no'], $gridAllDataList, $params['customer_id'], true, $isOrderExec);
        $result['quoteDetailIdList'] = $updateOrInsertQuoteDetailResult['savedDetailIds'];
        $result['saveQuoteDetailDataList'] = $updateOrInsertQuoteDetailResult['savedQuoteDetailDataList'];
        
        $seqNo = 1;
        foreach($result['quoteDetailIdList'] as $filterTreePath => $quoteDetalId){
            if(strpos($filterTreePath, config('const.treePathSeparator')) === false){
                $seqNo++;
            }
        }
        // 追加部材用の工事区分データ取得
        $addLayer = $Construction->getAddFlgData();
        $quoteDetailAddLayerData = [];
        // 追加部材階層データを作成
        $quoteDetailAddLayerData['quote_no']        = $params['quote_no'];
        $quoteDetailAddLayerData['quote_version']   = config('const.quoteCompVersion.number');
        $quoteDetailAddLayerData['construction_id'] = $addLayer['id'];
        $quoteDetailAddLayerData['layer_flg']       = config('const.quoteConstructionInfo.layer_flg');
        $quoteDetailAddLayerData['parent_quote_detail_id'] = config('const.quoteConstructionInfo.parent_quote_detail_id');
        $quoteDetailAddLayerData['seq_no']          = $seqNo;
        $quoteDetailAddLayerData['depth']           = config('const.quoteConstructionInfo.depth');
        $quoteDetailAddLayerData['tree_path']       = config('const.quoteConstructionInfo.tree_path');
        $quoteDetailAddLayerData['sales_use_flg']   = config('const.quoteConstructionInfo.sales_use_flg');
        $quoteDetailAddLayerData['product_name']    = $addLayer['construction_name'];
        $quoteDetailAddLayerData['add_flg']         = config('const.flg.on');
        $quoteDetailAddLayerData['top_flg']         = config('const.quoteConstructionInfo.top_flg');
        //$quoteDetailAddLayerData['to_layer_flg']    = config('const.quoteConstructionInfo.to_layer_flg');
        $quoteDetailAddLayerData['quote_quantity']  = 1;
        $quoteDetailAddLayerData['min_quantity']    = 1;
        $quoteDetailAddLayerData['order_lot_quantity']  = 1;
        $quoteDetailAddLayerData['stock_quantity']  = 1;
        $quoteDetailAddLayerData['over_quote_detail_id'] = 0;
        $QuoteDetail->addEx($quoteDetailAddLayerData);
        
        
        // 発注明細データの取得
        $orderDetailSaveData = $this->OrderServiceProvider->getOrderDetailData($params, $result['saveQuoteDetailDataList'], $result['quoteDetailIdList'], $result['orderId'], $orderSaveData['order_no']);

        // 発注明細の登録
        if(!$OrderDetail->addList($orderDetailSaveData['orderDetail'])){
            throw new \Exception(config('message.error.save'));
        }

        


        // 階層ごとの金額計算
        $targetList = $SystemUtil->calcQuoteLayersTotal($params['quote_no'], config('const.quoteCompVersion.number'));
        foreach ($targetList as $id => $item) {
            if ($id === 0) continue;
            $updateResult = $QuoteDetail->updateTotalKng($id, $item);
        }

        // 見積0版金額更新
        $updateResult = $QuoteVersion->updateTotalKng($params['quote_no'], config('const.quoteCompVersion.number'));


        /* 関連データ更新 END */
        $result['tmpFilePath'] = $this->OrderServiceProvider->getUploadFilePath($params['matter_id'], $result['orderId']);

        // ファイルアップロード
        foreach ($uploadFileList as $fileNm => $fileObj) {
            $request->file($fileNm)->storeAs($result['tmpFilePath'], $fileObj->getClientOriginalName());
        }

        return $result;
    }

    /**
     * 発注申請
     * @param Request $request
     * @return type
     */
    public function application(Request $request){
        $result = array('status' => false, 'message' => '', 'id' => 0, 'matterId'=>0);

        $orderId        = null;
        $Order          = new Order();
        $OrderDetail    = new OrderDetail();
        $SystemUtil     = new SystemUtil();

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
            if(Common::isFlgOn($params['own_stock_flg'])){
                // 当社在庫品
                $ownStockSaveResult = $this->ownStockApplicationSave($request, $now);
                $orderId = $ownStockSaveResult['orderId'];
                $result['matterId'] = $params['matter_id'];
            }else{
                // いきなり発注
                // 一時保存
                $tmpSaveResult = $this->temporarySave($request, $now, true);
                // パラメータにセット
                $this->setRegisterData($params, $tmpSaveResult);

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


                // 承認(申請)データの作成
                //$SystemUtil->applyForOrderProcessing($Order->getById($orderId));

                // 発注データの並び替え
                $OrderDetail->updateSortByQuoteData($orderId);

                // 一時保存ファイルを移動
                $this->OrderServiceProvider->moveFolder($tmpSaveResult['tmpFilePath'], $this->OrderServiceProvider->getUploadFilePath($params['matter_id'], $orderId));
                $result['matterId'] = $tmpSaveResult['matterId'];
            }

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
     * 当社在庫品の申請処理
     * @param $request リクエストデータ
     * @param $now 現在日時
     * @return $result
     */
    private function ownStockApplicationSave($request, $now){
        $result = [
            'orderId'=>0,
            'quoteDetailIdList'=>[],
            'saveQuoteDetailDataList'=>[],
        ];

        $Order          = new Order();
        $OrderDetail    = new OrderDetail();
        $Matter         = new Matter();
        $Quote          = new Quote();
        

        // リクエストデータ取得
        $params = $request->request->all();

        // ロック確認
        $this->OrderServiceProvider->isOwnLocked($params['matter_id']);

        $matterData = $Matter->getById($params['matter_id']);
        $quoteData  = $Quote->getByMatterNo($matterData->matter_no);
        // パラメータにセット
        $params['matter_no']    = $matterData->matter_no;
        $params['quote_no']     = $quoteData->quote_no;

        // 全てのグリッドデータ
        $gridAllDataList = json_decode($params['grid_all_data']);

        // 保存用に加工した発注ヘッダーデータを取得
        $orderSaveData = $this->OrderServiceProvider->getOrderSaveData($params, $now, false);
        $result['orderId'] = $Order->add($orderSaveData);

        if(!$result['orderId']){
            throw new \Exception(config('message.error.save'));
        }

        // 見積もり明細 登録/更新
        $updateOrInsertQuoteDetailResult = $this->OrderServiceProvider->updateOrInsertQuoteDetail($params['quote_no'], $gridAllDataList, $params['customer_id'], false, true);
        $result['quoteDetailIdList'] = $updateOrInsertQuoteDetailResult['savedDetailIds'];
        $result['saveQuoteDetailDataList'] = $updateOrInsertQuoteDetailResult['savedQuoteDetailDataList'];
        
        // 発注明細データの取得
        $gridDataList = [];
            foreach($result['saveQuoteDetailDataList'] as $record){
                if($record['chk'] && $record['layer_flg'] === config('const.flg.off') && $record['maker_id'] === intval($orderSaveData['maker_id'])){
                    $gridDataList[] = $record;
                }
            }
        $orderDetailSaveData = $this->OrderServiceProvider->getOrderDetailData($params, $gridDataList, $result['quoteDetailIdList'], $result['orderId'], $orderSaveData['order_no']);

        // 発注明細の登録
        if(!$OrderDetail->addList($orderDetailSaveData['orderDetail'])){
            throw new \Exception(config('message.error.save'));
        }

        // 発注データの並び替え
        $OrderDetail->updateSortByQuoteData($result['orderId']);

        $filePath = $this->OrderServiceProvider->getUploadFilePath($params['matter_id'], $result['orderId']);
        // ファイルアップロード
        // 添付ファイル
        $uploadFileList = $request->files;
        foreach ($uploadFileList as $fileNm => $fileObj) {
            $request->file($fileNm)->storeAs($filePath, $fileObj->getClientOriginalName());
        }

        // ロック解除
        $this->OrderServiceProvider->unLock($params['matter_id']);

        return $result;
    }

    /**
     * 一時保存のタイミングで生成された「案件番号」「見積番号」「案件住所ID」をパラメータにセットする
     * @param &$params   POSTされたデータの配列
     * @param $result   一時保存の戻り値
     */
    private function setRegisterData(&$params, $result){
        // パラメータにセット
        $params['matter_no'] = $result['matterNo'];
        $params['matter_id'] = $result['matterId'];
        $params['quote_no'] = $result['quoteNo'];
        $params['delivery_address_id'] = $params['delivery_address_kbn'] === strval(config('const.deliveryAddressKbn.val.site')) ? $result['addressId']:$params['delivery_address_id'];
    }

}