<?php

namespace App\Providers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\QuoteEditController;
use App\Models\Department;
use App\Models\NumberManage;
use App\Models\Construction;
use App\Models\Order;
use App\Models\OrderDetail;
// use App\Models\Person;
use App\Models\Product;
use App\Models\QuoteDetail;
use App\Models\Staff;
use App\Models\Warehouse;
use Illuminate\Support\ServiceProvider;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\General;
use App\Models\LockManage;
use App\Models\ProductCampaignPrice;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Storage;
use Symfony\Component\HttpFoundation\FileBag;

class OrderServiceProvider extends ServiceProvider
{
    // 商品コードとして有効な最低文字数
    const minProductCodeLen = 3;
    
    // メンバ
    private $controller;        // 呼び出し元
    private $isOrderSudden;     // いきなり発注か
    private $isQuote;           // 見積
    

    // メンバ モデル
    private $Construction;
    private $QuoteDetail;
    private $Order;
    private $OrderDetail;
    private $Product;
    private $Staff;
    private $NumberManage;
    private $Department;
    private $Warehouse;
    private $ProductCampaignPrice;
    private $LockManage;
    private $General;
    private $SystemUtil;

    public function __construct(){
        // モデル
        $this->Construction = new Construction();
        $this->QuoteDetail  = new QuoteDetail();
        $this->Order        = new Order();
        $this->OrderDetail  = new OrderDetail();
        $this->Product      = new Product();
        $this->Staff        = new Staff();
        $this->NumberManage = new NumberManage();
        $this->Department   = new Department();
        $this->Warehouse    = new Warehouse();
        $this->ProductCampaignPrice = new ProductCampaignPrice();
        $this->LockManage   = new LockManage();
        $this->General   = new General();
        $this->SystemUtil   = new SystemUtil();
    }

    public function initialize(Controller $controller, ?bool $isOrderSudden = false){
        $this->controller = $controller;    // アップキャストする　TODO:phpでは影響無い？
        $this->isOrderSudden = $isOrderSudden;
        $this->isQuote = (QuoteEditController::SCREEN_NAME == $controller::SCREEN_NAME);
    }
    
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }

    

    // /**
    //  * 商品ごとの仕入/販売単価のリストを返す
    //  * @param $customerId   得意先ID
    //  * @return $result      商品ごとの仕入/販売単価の配列
    //  *      ['costProductPriceList']    仕入単価リスト
    //  *      ['salesProductPriceList']   販売単価リスト
    //  */
    // public function getProductPriceList($customerId){
    //     $result = [
    //         'costProductPriceList'  =>[],
    //         'salesProductPriceList' =>[],
    //     ];
    //     $tmpList = $this->ProductCampaignPrice->getList($customerId);
    //     foreach ($tmpList as $record) {
    //         if ($record->cost_sales_kbn === config('const.costSalesKbn.cost')) {
    //             // 仕入単価
    //             $result['costProductPriceList'][$record->product_id][$record->unit_price_kbn] = $record;
    //         } else {
    //             // 販売単価
    //             $result['salesProductPriceList'][$record->product_id][$record->unit_price_kbn] = $record;
    //         }
    //     }
    //     return $result;
    // }

    /**
     * 発注データを登録可能な形のデータに変換して返す
     * @param $params   発注テーブルのカラムがセットされた配列
     * @param $now      現在日時
     * @param $isTmp    一時保存データか
     * @param $isOrderExec 発注登録ボタンの処理から呼ばれたか
     * @return $orderSaveData   発注の保存用に加工したデータ
     */
    public function getOrderSaveData($params, $now, $isTmp, $isOrderExec = false){
        $orderSaveData  = [];
        if($isTmp){
            $orderSaveData['order_no']  = null;
            $orderSaveData['status']    = config('const.orderStatus.val.not_ordering'); // 未処理
        }else{
            $departmentSymbol = $this->Department->getById($params['department_id'])->department_symbol;
            $staffShortName = $this->Staff->getById($params['staff_id'])->staff_short_name;
            $orderSaveData['order_no']  = $this->NumberManage->getSeqNo(config('const.number_manage.kbn.order'), $now->format('Ym'), $departmentSymbol, $staffShortName);
            //$orderSaveData['status']    = config('const.orderStatus.val.applying');     // 申請中
            $orderSaveData['status']    = config('const.orderStatus.val.approved');     // 承認済み
        }
        $orderSaveData['quote_no']      = $params['quote_no'];
        $orderSaveData['matter_no']     = $params['matter_no'];
        $orderSaveData['maker_id']      = $params['maker_id'];
        $orderSaveData['supplier_id']   = $params['supplier_id'];
        $orderSaveData['person_id']     = $params['person_id'];
        $orderSaveData['delivery_address_kbn']  = $params['delivery_address_kbn'];
        $orderSaveData['delivery_address_id']   = $params['delivery_address_id'];
        $orderSaveData['order_apply_datetime']      = $now;
        $orderSaveData['order_approval_datetime']   = null;
        $orderSaveData['order_datetime']    = null;
        $orderSaveData['order_staff_id']    = Auth::user()->id;
        $orderSaveData['cost_total']        = $params['cost_total'];
        //$orderSaveData['sales_total']       = $params['sales_total'];
        $orderSaveData['sales_total']       = 0;
        //$orderSaveData['profit_total']      = $params['profit_total'];
        $orderSaveData['profit_total']      = 0;
        $orderSaveData['account_customer_name'] = $params['account_customer_name'];
        $orderSaveData['account_owner_name']    = $params['account_owner_name'];
        $orderSaveData['map_print_flg']         = $params['map_print_flg'];
        $orderSaveData['desired_delivery_date'] = empty($params['desired_delivery_date']) ? null : $params['desired_delivery_date'];
        $orderSaveData['sales_support_comment'] = $params['sales_support_comment'];
        $orderSaveData['supplier_comment']      = $params['supplier_comment'];
        $orderSaveData['arrival_complete_flg']  = config('const.flg.off');
        $orderSaveData['own_stock_flg']         = isset($params['own_stock_flg']) ? $params['own_stock_flg'] : config('const.flg.off');

        if ($isTmp && $isOrderExec) {
            // 発注処理の一時保存の場合、一時保存データに入荷先・地図印刷フラグ・支援課コメント・メーカーコメントを保存しない
            $orderSaveData['delivery_address_kbn'] = 0;
            $orderSaveData['delivery_address_id'] = 0;
            $orderSaveData['map_print_flg'] = config('const.flg.off');
            $orderSaveData['sales_support_comment'] = '';
            $orderSaveData['supplier_comment'] = '';
        }

        return $orderSaveData;
    }

    /**
     * 発注明細データを登録可能な形に変換して返す
     * 数量超過分の見積明細データを登録可能な形に変換して返す
     * @param $params               quote_no,supplier_id,supplier_name　必須
     * @param $gridDataList         発注対象の見積明細データ
     * @param $quoteDetailIdList    updateOrInsertQuoteDetail関数の戻り値
     * @param $orderId              発注ID
     * @param $orderNo              発注番号　一時保存かどうかの判定にも使う
     * @return $saveDataList
     *         ['orderDetail']発注データ
     */
    public function getOrderDetailData($params, $gridDataList, $quoteDetailIdList, $orderId, $orderNo){
        $saveDataList = [
            'orderDetail'=> [],
        ];
        
        // // 商品の適用開始日チェック　MEMO:商品登録更新時にチェックするように変更
        // $this->productStartDateIsValid($gridDataList, empty($orderNo));

        $tmp_depth = [];
        $tmp_seqNo = [];
        $tmp_constructionId = [];

        $tmp_depth  = array_column($gridDataList, 'depth');
        $tmp_seqNo = array_column($gridDataList, 'seq_no');
        $tmp_constructionId = array_column($gridDataList, 'construction_id');

        array_multisort(
                $tmp_constructionId, SORT_ASC,
                $tmp_depth,SORT_ASC,
                $tmp_seqNo, SORT_ASC,
                $gridDataList);

        foreach($gridDataList as $key => $gridRecord){  
            $orderDetailRecord = [];
            $orderDetailRecord['order_id']      = $orderId;
            $orderDetailRecord['order_no']      = $orderNo;
            $orderDetailRecord['seq_no']        = ($key + 1);
            $quoteDetailId = $gridRecord['quote_detail_id'];
            if(empty($quoteDetailId)){
                $quoteDetailId = $quoteDetailIdList[$gridRecord['filter_tree_path']];
            }
            $orderDetailRecord['quote_detail_id']   = $quoteDetailId;
            $orderDetailRecord['add_kbn']       = $gridRecord['add_kbn'];
            $orderDetailRecord['product_id']    = $gridRecord['product_id'];
            $orderDetailRecord['product_code']  = $gridRecord['product_code'];
            $orderDetailRecord['product_name']  = $gridRecord['product_name'];
            $orderDetailRecord['model']         = $gridRecord['model'];
            $orderDetailRecord['maker_id']      = $gridRecord['maker_id'];
            $orderDetailRecord['maker_name']    = $gridRecord['maker_name'];
            $orderDetailRecord['supplier_id']   = $gridRecord['supplier_id'];
            $orderDetailRecord['supplier_name'] = $gridRecord['supplier_name'];
            $orderDetailRecord['order_quantity'] = $gridRecord['order_quantity'];
            $orderDetailRecord['min_quantity']  = $gridRecord['min_quantity'];
            $orderDetailRecord['stock_quantity'] = $this->SystemUtil->calcStock($gridRecord['order_quantity'], $gridRecord['min_quantity']);
            $orderDetailRecord['unit']          = $gridRecord['unit'];
            $orderDetailRecord['stock_unit']    = $gridRecord['stock_unit'];
            $orderDetailRecord['regular_price'] = $gridRecord['regular_price'];
            $orderDetailRecord['sales_kbn']     = $gridRecord['sales_kbn'];
            $orderDetailRecord['cost_unit_price']   = $gridRecord['cost_unit_price'];
            $orderDetailRecord['sales_unit_price']  = $gridRecord['sales_unit_price'];
            $orderDetailRecord['cost_makeup_rate']  = $gridRecord['cost_makeup_rate'];
            $orderDetailRecord['sales_makeup_rate'] = $gridRecord['sales_makeup_rate'];
            $orderDetailRecord['cost_total']        = $this->SystemUtil->calcTotal($gridRecord['order_quantity'], $gridRecord['cost_unit_price'], true);
            $orderDetailRecord['sales_total']       = $this->SystemUtil->calcTotal($gridRecord['order_quantity'], $gridRecord['sales_unit_price'], false);
            $orderDetailRecord['profit_total']      = $this->SystemUtil->calcProfitTotal($orderDetailRecord['sales_total'], $orderDetailRecord['cost_total']);
            $orderDetailRecord['gross_profit_rate'] = $this->SystemUtil->calcRate($orderDetailRecord['profit_total'], $orderDetailRecord['sales_total']); 
            $orderDetailRecord['hope_arrival_plan_date']    = null;
            $orderDetailRecord['arrival_plan_date'] = null;
            $orderDetailRecord['arrival_quantity']  = 0;
            $orderDetailRecord['memo']  = $gridRecord['memo'];
            $orderDetailRecord['parent_order_detail_id']    = 0;

            $saveDataList['orderDetail'][]  = $orderDetailRecord;
        }
        return $saveDataList;
    }

    /**
     * 見積明細の登録/更新
     * @param string $quoteNo   見積番号
     * @param array $allDetails グリッドのデータ
     * @param array $customerId 得意先ID
     * @param bool $saveProductFlg 商品マスタ登録更新を行うか
     * @param bool $isOrderExec 発注登録から呼ばれた処理か
     * @return $savedDetailIds  保存登録したフィルターパスごとの見積明細IDリスト
     */
    public function updateOrInsertQuoteDetail($quoteNo, $allDetails, $customerId, $saveProductFlg = false, $isOrderExec = false){

        // 更新前の対象の見積版の見積明細を全取得
        //$prevDetails        = $this->QuoteDetail->getPerVersion($quoteNo, config('const.quoteCompVersion.number'));
        // 更新前の出荷指示を取得
        $shipmentDataList   = $this->QuoteDetail->getShipmentDataList($quoteNo, config('const.quoteCompVersion.number'));
        // 更新前の発注明細を取得
        $orderDetailDataList= $this->QuoteDetail->getOrderDetailList($quoteNo, config('const.quoteCompVersion.number'));

        // 品番なしの商品番号取得
        $noProductCodeData = $this->General->getCategoryList(config('const.general.noproductcode'))->first();
        $noProductCode = $noProductCodeData->value_text_1;

        // システム日時（今日）
        $toDay = new Carbon();

        // グリッド上で使用されている商品IDの商品データを一括取得
        $productIdList = array_column($allDetails, "product_id");
        $productList = $this->Product->getByIds($productIdList);
        // グリッド上で使用されている1回限り登録の商品データを取得
        $autoProductList = $productList->where('auto_flg', config('const.flg.on'))->keyBy('id');

        // 配列化
        //$prevDetailIds = [];
        $savedDetailIds = [];
        $result = [
            'savedDetailIds' => [],
            'savedQuoteDetailDataList' => [],
        ];
        // foreach ($prevDetails as $rec) {
        //     $prevDetailIds[$rec['quote_detail_id']] = $rec['quote_detail_id'];
        // }

        // 親階層が配列上で前にいることが前提
        $constructionIds = [];
        $seqNoPerParent = [];
        foreach ($allDetails as $row) {
            // 表示用の階層パス
            $filterTreePath = $row->filter_tree_path;
            // 表示用階層パスと見積明細IDの紐付け
            if (!empty($row->quote_detail_id)) {
                $savedDetailIds[$filterTreePath] = $row->quote_detail_id;
            }

            // 表示用階層パスを分解
            $filterTreePathIds = explode(config('const.treePathSeparator'), $filterTreePath);

            $constructionId = 0;
            $parentQuoteDetailId = 0;
            $filterParentTreePath = '0';
            $treePath = '0';
            if (count($filterTreePathIds) >= 2) {
                $filterParentTreePath = '';
                $treePath = '';
                for ($i = 0; $i <= count($filterTreePathIds) - 2; $i++) {
                    if ($filterParentTreePath != '') {
                        $filterParentTreePath .= config('const.treePathSeparator');
                        $treePath .= config('const.treePathSeparator');
                    }
                    // 親階層の表示用階層パス
                    $filterParentTreePath .= (string)$filterTreePathIds[$i];
                    // 階層パス
                    $treePath .= $savedDetailIds[$filterParentTreePath];

                    // 工事区分ID
                    if ($i === 0) {
                        if (isset($constructionIds[$treePath])) {
                            $constructionId = $constructionIds[$treePath];
                        }
                    }
                }
                
                // 親見積明細ID
                $parentQuoteDetailId = $savedDetailIds[$filterParentTreePath];
            }

            // 連番
            if (isset($seqNoPerParent[$parentQuoteDetailId])) {
                $seqNoPerParent[$parentQuoteDetailId]++;
            } else {
                $seqNoPerParent[$parentQuoteDetailId] = 1;
            }

            // 行データを変換
            $quoteDetailData = json_decode(json_encode($row), true);

            // 紐づく発注明細に入荷済みが存在するか判定
            $isArrivedFlg = false;
            if (isset($savedDetailIds[$filterTreePath])) {
                $orderDetails = $orderDetailDataList->where('quote_detail_id', $savedDetailIds[$filterTreePath]);
                foreach ($orderDetails as $rec) {
                    if ($rec->arrival_quantity > 0) {
                        $isArrivedFlg = true;
                    }
                }
            }

            // 商品マスタ登録更新
            $resultSaveProduct = $this->saveProduct($quoteDetailData, $productList, $autoProductList, $noProductCode, $toDay, $isArrivedFlg, $saveProductFlg, $isOrderExec);
            $quoteDetailData = $resultSaveProduct['quoteDetailData'];
            $autoProductList = $resultSaveProduct['autoProductList'];

            $quoteDetailData['quote_no']        = $quoteNo;
            $quoteDetailData['quote_version']   = config('const.quoteCompVersion.number');
            $quoteDetailData['construction_id'] = ($constructionId === 0) ? $quoteDetailData['construction_id'] : $constructionId;
            $quoteDetailData['parent_quote_detail_id'] = $parentQuoteDetailId;
            if($this->isOrderSudden){
                // いきなり発注のみ連番を振りなおす
                $quoteDetailData['seq_no']      = $seqNoPerParent[$parentQuoteDetailId];
            }else{
                // 通常の発注は並び替えや行追加/削除がないため振りなおしは不要
                //unset($quoteDetailData['seq_no']);
            }
            $quoteDetailData['tree_path']       = $treePath;

            if (!isset($savedDetailIds[$filterTreePath])) {
                $quoteDetailData['quote_quantity'] = $quoteDetailData['order_quantity'];
                if($quoteDetailData['quote_quantity'] == 0){
                    // 一時保存などで発注数が0の場合、発注ロット数を見積数にセット
                    $quoteDetailData['quote_quantity'] = $quoteDetailData['order_lot_quantity'];
                }
                $quoteDetailData['over_quote_detail_id'] = 0;
            }
            // 管理数
            $quoteDetailData['stock_quantity'] = $this->SystemUtil->calcStock($quoteDetailData['quote_quantity'], $quoteDetailData['min_quantity']);
            // 仕入総額 = 数量 * 仕入単価
            $quoteDetailData['cost_total'] = $this->SystemUtil->calcTotal($quoteDetailData['quote_quantity'], $quoteDetailData['cost_unit_price'], true);
            // 販売総額 = 数量 * 販売単価
            $quoteDetailData['sales_total'] = $this->SystemUtil->calcTotal($quoteDetailData['quote_quantity'], $quoteDetailData['sales_unit_price'], false);
            // 粗利総額 = 販売総額 - 仕入総額
            $quoteDetailData['profit_total'] = $this->SystemUtil->calcProfitTotal($quoteDetailData['sales_total'], $quoteDetailData['cost_total']);
            // 粗利率 = 粗利総額 / 販売総額 * 100
            $quoteDetailData['gross_profit_rate'] = $this->SystemUtil->calcRate($quoteDetailData['profit_total'], $quoteDetailData['sales_total']); 
            
            // 一式の見積数チェック
            if(Common::isFlgOn($quoteDetailData['set_flg'])){
                if($quoteDetailData['quote_quantity'] != 1){
                    throw new \Exception(str_replace('$product_name', $quoteDetailData['product_name'], config('message.error.order.set_product')));
                }
            }

            $result['savedQuoteDetailDataList'][] = $quoteDetailData;
            
            if (isset($savedDetailIds[$filterTreePath])) {
                // 更新
                $quoteDetailShipment = $shipmentDataList->where('quote_detail_id', $savedDetailIds[$filterTreePath]);
                $orderDetailData = $orderDetailDataList->where('quote_detail_id', $savedDetailIds[$filterTreePath]);
                
                // 発注明細で入荷済み数が入っていればエラー
                if($orderDetailData->count() >= 1){
                    if($quoteDetailData['product_id'] !== $orderDetailData->first()->product_id){
                        foreach($orderDetailData as $rowOrderDetailData){
                            if($rowOrderDetailData->arrival_quantity >= 1){
                                throw new \Exception(str_replace('$product_code', $rowOrderDetailData->product_code, config('message.error.order.arrival_complete')));
                            }
                        }
                    }
                }
                // 出荷指示の出荷積込フラグが立っていればエラー
                if($quoteDetailShipment->count() >= 1){
                    if($quoteDetailData['product_id'] !== $quoteDetailShipment->first()->product_id){
                        foreach($quoteDetailShipment as $rowShipment){
                            if($rowShipment->loading_finish_flg === config('const.flg.on')){
                                throw new \Exception(str_replace('$product_code', $rowShipment->product_code, config('message.error.order.loading_finish')));
                            }
                        }
                    }
                }

                if (!$this->isQuote) {
                    unset($quoteDetailData['memo']);
                }
                $saveResult = $this->QuoteDetail->updateByIdEx($quoteDetailData['quote_detail_id'], $quoteDetailData, $customerId);
                if (!$saveResult) {
                    throw new \Exception(config('message.error.save'));
                }

                // 更新した見積明細IDは更新前配列から除去
                //unset($prevDetailIds[$quoteDetailData['quote_detail_id']]);
            } else {
                // 新規登録
                $detailId = $this->QuoteDetail->addEx($quoteDetailData, $customerId);

                $savedDetailIds[$filterTreePath] = $detailId;
            }

            if ($quoteDetailData['depth'] === config('const.quoteConstructionInfo.depth')) {
                // 一番上の階層（工事区分階層）の工事区分IDを保管
                $constructionIds[$savedDetailIds[$filterTreePath]] = $quoteDetailData['construction_id'];
            }
        }
        $result['savedDetailIds'] = $savedDetailIds;

        return $result;
    }

    /**
     * 発注グリッドからの商品マスタの登録更新
     *
     * @param Array $quoteDetailData
     * @param Collection $productList グリッドに含まれる全商品データ
     * @param Collection $autoProductList 同一案件内の1回限り登録商品データ
     * @param string $noProductCode 品番なし用の商品コード
     * @param Carbon $toDay システム日付
     * @param boolean $isArrivedFlg 入荷済みかどうか
     * @param bool $saveProductFlg 商品マスタ登録更新を行うか
     * @param bool $isOrderExec 発注登録から呼ばれた処理か
     * @return array('quoteDetailData' => $quoteDetailData, 'autoProductList' => $autoProductList)
     */
    public function saveProduct($quoteDetailData, $productList, $autoProductList, $noProductCode, $toDay, $isArrivedFlg, $saveProductFlg, $isOrderExec) {
        
        // 一式明細以外の明細行の場合、商品マスタの登録／更新を行うか判定する
        if ($quoteDetailData['layer_flg'] === config('const.flg.off') && $quoteDetailData['set_flg'] === config('const.flg.off')) {
            $productInfo = null;

            // 商品コードは3文字以上でないとオートコンプリート検索できないので、本登録商品は3文字以上を有効とする
            $isValidProductCode = false;
            if (isset($quoteDetailData['product_code']) && $quoteDetailData['product_code'] !== '' && $quoteDetailData['product_code'] !== $noProductCode) {
                if (mb_strlen($quoteDetailData['product_code']) >= self::minProductCodeLen) {
                    $isValidProductCode = true;
                }
            }
            // 仮登録済みフラグ
            $isDraftFlg = false;
            // 1回限り登録済みフラグ
            $isAutoFlg = false;
            // 本登録済みフラグ
            $isDefinitiveFlg = false;

            // メーカーIDがない場合は商品IDを破棄　※元仕様はメーカー指定なしでも仮登録していたが、仮登録しないように変更するため
            if (!isset($quoteDetailData['maker_id']) || $quoteDetailData['maker_id'] === 0) {
                $quoteDetailData['product_id'] = 0;
            }

            // 商品IDを持っている場合、商品IDを元に取得
            if ((isset($quoteDetailData['product_id']) && $quoteDetailData['product_id'] !== 0)) {
                $productInfo = $productList->where('id', $quoteDetailData['product_id'])->first();
                if (is_null($productInfo)) {
                    // 商品データが取得できなかった場合は、商品IDを抜く
                    $quoteDetailData['product_id'] = 0;
                } else {
                    $isDraftFlg = ($productInfo->draft_flg === config('const.flg.on'));
                    $isAutoFlg = ($productInfo->auto_flg === config('const.flg.on'));
                    $isDefinitiveFlg = ($productInfo->draft_flg === config('const.flg.off') && $productInfo->auto_flg === config('const.flg.off'));
                }
            }

            // 有効な商品番号 かつ メーカーIDを持っている かつ 本登録商品ではない 場合
            if ($isValidProductCode && (isset($quoteDetailData['maker_id']) && $quoteDetailData['maker_id'] !== 0) && !$isDefinitiveFlg) {
                // 1回限り登録チェック：OFF かつ (商品IDなし または 仮登録済み) の場合
                // または　本登録チェック：ON かつ 1回限り登録済み の場合
                if ((isset($quoteDetailData['product_auto_flg']) && !Common::isFlgOn($quoteDetailData['product_auto_flg'])
                    && ((!isset($quoteDetailData['product_id']) || $quoteDetailData['product_id'] === 0) || $isDraftFlg))
                    || (isset($quoteDetailData['product_definitive_flg']) && Common::isFlgOn($quoteDetailData['product_definitive_flg']) && $isAutoFlg)) {
                    // 商品番号とメーカーIDで本登録商品を検索　TODO：一旦ループ内で1件ずつ取得
                    $definitiveProductData = $this->Product->getProductByCodeAndMaker($quoteDetailData['product_code'], $quoteDetailData['maker_id']);
                    // データが取得できた場合
                    if (!is_null($definitiveProductData)) {
                        // 本登録チェック：ON かつ 1回限り登録済み かつ 入荷済み の場合、商品を寄せられないのでエラーにする
                        if (isset($quoteDetailData['product_definitive_flg']) && Common::isFlgOn($quoteDetailData['product_definitive_flg']) && $isAutoFlg 
                            && $isArrivedFlg && ($quoteDetailData['product_id'] !== $definitiveProductData->id)) {
                            throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.arrival_complete')));
                        }

                        // 有形品／無形品チェック
                        if ((int)$quoteDetailData['intangible_flg'] !== (int)$definitiveProductData['intangible_flg']) {
                            if (Common::isFlgOn($quoteDetailData['intangible_flg'])) {
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.intangible_flg_on')));
                            } else {
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.intangible_flg_off')));
                            }
                        }

                        // 最小単位数チェック
                        if ((float)$quoteDetailData['min_quantity'] !== (float)$definitiveProductData->min_quantity) {
                            throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.min_quantity')));
                        }
                        
                        // 発注ロット数の倍数チェック（見積から呼ばれた場合・発注一時保存から呼ばれた場合はこのチェックは不要）
                        if ($isOrderExec) {
                            if (!Common::isMultipleQunaity((float)$quoteDetailData['order_quantity'], (float)$definitiveProductData['order_lot_quantity'])) {
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.order_lot_quantity_by')));
                            }
                        }
                        
                        // 商品IDを特定
                        $quoteDetailData['product_id'] = $definitiveProductData->id;
                        $isDraftFlg = false;
                        $isDefinitiveFlg = true;
                        $productInfo = $definitiveProductData;
                    }
                }
                // 1回限り登録チェック：ON かつ 本登録チェック：OFF の場合
                else if (isset($quoteDetailData['product_auto_flg']) && Common::isFlgOn($quoteDetailData['product_auto_flg'])
                    && isset($quoteDetailData['product_definitive_flg']) && !Common::isFlgOn($quoteDetailData['product_definitive_flg'])) {
                    $autoProductData = null;
                    // 商品番号が品番なしではない かつ 入荷済みではない 場合、商品データを寄せる
                    if ($quoteDetailData['product_code'] !== $noProductCode && !$isArrivedFlg) {
                        // 商品番号とメーカーIDで同見積内の1回限り商品を検索
                        $autoProductData = $autoProductList
                                            ->where('product_code', $quoteDetailData['product_code'])
                                            ->where('maker_id', $quoteDetailData['maker_id'])
                                            ->first();
                    }
                    // データが取得できた場合
                    if (!is_null($autoProductData)) {
                        // 有形品／無形品チェック
                        if ((int)$quoteDetailData['intangible_flg'] !== (int)$autoProductData['intangible_flg']) {
                            if (Common::isFlgOn($quoteDetailData['intangible_flg'])) {
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.intangible_flg_on')));
                            } else {
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.intangible_flg_off')));
                            }
                        }

                        // 最小単位数チェック
                        if ((float)$quoteDetailData['min_quantity'] !== (float)$autoProductData['min_quantity']) {
                            throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.min_quantity')));
                        }
                        
                        // 発注ロット数の倍数チェック（見積から呼ばれた場合・発注一時保存から呼ばれた場合はこのチェックは不要）
                        if ($isOrderExec) {
                            if (!Common::isMultipleQunaity((float)$quoteDetailData['order_quantity'], (float)$autoProductData['order_lot_quantity'])) {
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.order_lot_quantity_by')));
                            }
                        }
                        
                        // 商品IDを特定
                        $quoteDetailData['product_id'] = $autoProductData['id'];
                        $isDraftFlg = false;
                        $isDefinitiveFlg = false;
                        $productInfo = $autoProductData;
                    }
                }
                else {
                    //nop
                }
            }

            // 適用開始日チェック（見積から呼ばれた場合・発注一時保存から呼ばれた場合は、すでに発注がある場合のみチェック。見積は品番変更機能がないのでチェックエラーになることはない）
            if (!is_null($productInfo)) {
                // // 1回限り登録商品は性質上、適用開始日チェックは行わなくて問題ない
                // if (!isset($productInfo['auto_flg']) || !Common::isFlgOn($productInfo['auto_flg'])) {
                    $startDate =  $productInfo['start_date'];
                    if (Common::nullToBlank($startDate) !== '') {
                        $startDate = Carbon::parse($startDate.' 00:00:00');
                        // <=
                        if (!$startDate->lt($toDay)) {
                            if ($isOrderExec) {
                                // 発注登録時
                                throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.start_date_1')));
                            } else {
                                // 発注一時保存時は発注した明細がある場合のみエラーにする
                                if (isset($quoteDetailData['order_id_list']) && Common::nullToBlank($quoteDetailData['order_id_list']) !== '') {
                                    throw new \Exception(str_replace('$product_code', $quoteDetailData['product_code'], config('message.error.order.start_date_2')));
                                }
                            }
                        }
                    }
                // }
            }
            
            // 商品番号がある　かつ　メーカーIDがある　場合、商品の登録・更新を行う（見積からは商品マスタを登録更新しない、当社在庫発注からは本登録商品の更新のみ）
            if ($saveProductFlg
                && (isset($quoteDetailData['product_code']) && $quoteDetailData['product_code'] !== '')
                && (isset($quoteDetailData['maker_id']) && $quoteDetailData['maker_id'] !== 0)) {

                // 商品マスタ更新用
                $updateProductData = [];
                if (isset($quoteDetailData['product_id']) && $quoteDetailData['product_id'] !== 0) {
                    if (isset($quoteDetailData['product_name'])) {
                        $updateProductData['product_name'] = $quoteDetailData['product_name'];
                    }
                    // 工事区分1～3のどれとも一致しない場合、空いているところにセット
                    if (!is_null($productInfo) 
                        && $productInfo['construction_id_1'] !== $quoteDetailData['construction_id']
                        && $productInfo['construction_id_2'] !== $quoteDetailData['construction_id'] 
                        && $productInfo['construction_id_3'] !== $quoteDetailData['construction_id']
                    ) {
                        if (empty($productInfo['construction_id_1'])) {
                            $updateProductData['construction_id_1'] = $quoteDetailData['construction_id'];
                        } else if (empty($productInfo['construction_id_2'])) {
                            $updateProductData['construction_id_2'] = $quoteDetailData['construction_id'];
                        } else if (empty($productInfo['construction_id_3'])) {
                            $updateProductData['construction_id_3'] = $quoteDetailData['construction_id'];
                        } else {
                            // nop(空いていない場合は反映しない)
                        }
                    }
                    $updateProductData['class_big_id'] = 0;
                    if (!empty($quoteDetailData['class_big_id'])) {
                        $updateProductData['class_big_id'] = $quoteDetailData['class_big_id'];
                    }
                    $updateProductData['class_middle_id'] = 0;
                    if (!empty($quoteDetailData['class_middle_id'])) {
                        $updateProductData['class_middle_id'] = $quoteDetailData['class_middle_id'];
                    }
                    if (!empty($quoteDetailData['unit'])) {
                        $updateProductData['unit'] = $quoteDetailData['unit'];
                    }
                    if (!empty($quoteDetailData['stock_unit'])) {
                        $updateProductData['stock_unit'] = $quoteDetailData['stock_unit'];
                    }
                    $updateProductData['price'] = Common::nullorBlankToZero($quoteDetailData['regular_price']);
                    $updateProductData['open_price_flg'] = ($updateProductData['price'] === 0) ? config('const.flg.on') : config('const.flg.off');
                    if ($quoteDetailData['cost_kbn'] == config('const.unitPriceKbn.normal')) {
                        // 仕入区分が標準の場合のみ仕入掛率・仕入単価を更新
                        $updateProductData['purchase_makeup_rate'] = $quoteDetailData['cost_makeup_rate'];
                        $updateProductData['normal_purchase_price'] = $quoteDetailData['cost_unit_price'];
                    }
                    if ($quoteDetailData['sales_kbn'] == config('const.unitPriceKbn.normal')) {
                        // 販売区分が標準の場合のみ販売掛率・販売単価を更新
                        $updateProductData['sales_makeup_rate'] = $quoteDetailData['sales_makeup_rate'];
                        $updateProductData['normal_sales_price'] = $quoteDetailData['sales_unit_price'];
                    }
                }

                // 本登録チェックがついている場合
                if (isset($quoteDetailData['product_definitive_flg']) && Common::isFlgOn($quoteDetailData['product_definitive_flg'])) {
                    if ($isValidProductCode) {
                        if (!isset($quoteDetailData['product_id']) || $quoteDetailData['product_id'] === 0) {
                            // 本登録としてINSERT
                            $quoteDetailData['product_id'] = $this->addProductAtDraft($quoteDetailData, config('const.flg.off'), config('const.flg.off'));
                        } else {
                            // 本登録へUPDATE or 本登録をUPDATE
                            $updateProductData['draft_flg'] = config('const.flg.off');
                            $updateProductData['auto_flg'] = config('const.flg.off');
                            $this->Product->updateFromOrder($quoteDetailData['product_id'], $updateProductData);

                            // // 1回限り商品リストに存在する場合は削除　　　MEMO:レコードの処理される順番で結果が変わることになるのでコメントアウト
                            // if ($autoProductList->has($quoteDetailData['product_id'])) {
                            //     $autoProductList->forget($quoteDetailData['product_id']);
                            // }
                            
                            // TODO: 元が本登録の商品は最初に一括した商品データを使い回しているため、グリッド上に複数存在する時に工事区分1～3がうまくセットされない。変数上で上書きする？

                        }
                    } else {
                        if (!isset($quoteDetailData['product_id']) || $quoteDetailData['product_id'] === 0) {
                            // 1回限りとしてINSERT
                            $quoteDetailData['product_id'] = $this->addProductAtDraft($quoteDetailData, config('const.flg.off'), config('const.flg.on'));

                            // 1回限り商品リストに追加
                            $addedAutoProductData = $quoteDetailData;
                            $addedAutoProductData['id'] = $quoteDetailData['product_id'];
                            $addedAutoProductData['construction_id_1'] = $quoteDetailData['construction_id'];
                            $addedAutoProductData['construction_id_2'] = 0;
                            $addedAutoProductData['construction_id_3'] = 0;
                            $addedAutoProductData['start_date'] = null;
                            $addedAutoProductList = collect([$addedAutoProductData]);
                            $addedAutoProductList = $addedAutoProductList->keyBy('id');
                            $autoProductList = $autoProductList->union($addedAutoProductList);

                        } else if ($isDraftFlg) {
                            // 仮登録を1回限りへUPDATE
                            $updateProductData['draft_flg'] = config('const.flg.off');
                            $updateProductData['auto_flg'] = config('const.flg.on');
                            $this->Product->updateFromOrder($quoteDetailData['product_id'], $updateProductData);

                            // 1回限り商品リストに追加
                            $addedAutoProductData = $quoteDetailData;
                            $addedAutoProductData['id'] = $quoteDetailData['product_id'];
                            $addedAutoProductData['construction_id_1'] = isset($updateProductData['construction_id_1']) ? $updateProductData['construction_id_1'] : 0;
                            $addedAutoProductData['construction_id_2'] = isset($updateProductData['construction_id_2']) ? $updateProductData['construction_id_2'] : 0;
                            $addedAutoProductData['construction_id_3'] = isset($updateProductData['construction_id_3']) ? $updateProductData['construction_id_3'] : 0;
                            $addedAutoProductData['start_date'] = null;
                            $addedAutoProductList = collect([$addedAutoProductData]);
                            $addedAutoProductList = $addedAutoProductList->keyBy('id');
                            $autoProductList = $autoProductList->union($addedAutoProductList);

                        } else {
                            // nop(1回限り登録商品データは、更新しない)
                        }
                    }
                }
                // 1回限り登録チェックが付いている場合 
                else if (isset($quoteDetailData['product_auto_flg']) && Common::isFlgOn($quoteDetailData['product_auto_flg'])) {
                    if (!isset($quoteDetailData['product_id']) || $quoteDetailData['product_id'] === 0) {
                        // 1回限りとしてINSERT
                        $quoteDetailData['product_id'] = $this->addProductAtDraft($quoteDetailData, config('const.flg.off'), config('const.flg.on'));

                        // 1回限り商品リストに追加
                        $addedAutoProductData = $quoteDetailData;
                        $addedAutoProductData['id'] = $quoteDetailData['product_id'];
                        $addedAutoProductData['construction_id_1'] = $quoteDetailData['construction_id'];
                        $addedAutoProductData['construction_id_2'] = 0;
                        $addedAutoProductData['construction_id_3'] = 0;
                        $addedAutoProductData['start_date'] = null;
                        $addedAutoProductList = collect([$addedAutoProductData]);
                        $addedAutoProductList = $addedAutoProductList->keyBy('id');
                        $autoProductList = $autoProductList->union($addedAutoProductList);

                    } else if ($isDraftFlg) {
                        // 仮登録を1回限りへUPDATE
                        $updateProductData['draft_flg'] = config('const.flg.off');
                        $updateProductData['auto_flg'] = config('const.flg.on');
                        $this->Product->updateFromOrder($quoteDetailData['product_id'], $updateProductData);

                        // 1回限り商品リストに追加
                        $addedAutoProductData = $quoteDetailData;
                        $addedAutoProductData['id'] = $quoteDetailData['product_id'];
                        $addedAutoProductData['construction_id_1'] = $quoteDetailData['construction_id'];
                        $addedAutoProductData['construction_id_2'] = 0;
                        $addedAutoProductData['construction_id_3'] = 0;
                        $addedAutoProductData['start_date'] = null;
                        $addedAutoProductList = collect([$addedAutoProductData]);
                        $addedAutoProductList = $addedAutoProductList->keyBy('id');
                        $autoProductList = $autoProductList->union($addedAutoProductList);

                    } else {
                        // nop(1回限り登録商品データ および 本登録商品データは、更新しない)
                    }
                }
                // チェックなし（仮登録） 
                else {
                    if (!isset($quoteDetailData['product_id']) || $quoteDetailData['product_id'] === 0) {
                        // 仮登録としてINSERT
                        $quoteDetailData['product_id'] = $this->addProductAtDraft($quoteDetailData, config('const.flg.on'), config('const.flg.off'));
                    } else if ($isDraftFlg) {
                        // 仮登録をUPDATE
                        $this->Product->updateFromOrder($quoteDetailData['product_id'], $updateProductData);
                    } else {
                        // nop(1回限り登録商品データ および 本登録商品データは、更新しない)
                    }
                }
            }
        }

        $result = array('quoteDetailData' => $quoteDetailData, 'autoProductList' => $autoProductList);
        return $result;
    }

    /**
     * 商品を仮登録する
     * @param array $params         グリッドの行
     * @param int $draftflg         仮登録登録フラグ
     * @param int $autoFlg          自動登録する場合
     * @return int $productId       商品ID
     */
    public function addProductAtDraft($params, $draftflg, $autoFlg){
        $productData = [];
        $productData['construction_id_1']   = $params['construction_id'];
        $productData['product_code']        = $params['product_code'];
        $productData['product_name']        = $params['product_name'];
        $productData['model']               = $params['model'];
        $productData['maker_id']            = $params['maker_id'];
        $productData['min_quantity']        = $params['min_quantity'];
        $productData['order_lot_quantity']  = $params['order_lot_quantity'];
        $productData['quantity_per_case']   = $params['quantity_per_case'];
        $productData['set_kbn']             = $params['set_kbn'];
        $productData['class_big_id']        = $params['class_big_id'];
        $productData['class_middle_id']     = $params['class_middle_id'];
        $productData['class_small_id_1']    = $params['class_small_id'];
        $productData['tree_species']        = $params['tree_species'];
        $productData['grade']               = $params['grade'];
        $productData['length']              = $params['length'];
        $productData['thickness']           = $params['thickness'];
        $productData['width']               = $params['width'];
        $productData['unit']                = $params['unit'];
        $productData['stock_unit']          = $params['stock_unit'];
        $productData['price']               = $params['regular_price'];
        if(intval(Common::nullorBlankToZero($productData['price'])) === 0){
            $productData['open_price_flg']  = config('const.flg.on');
        }

        $isNormal = true;
        // 標準仕入単価
        if($params['cost_kbn'] === config('const.unitPriceKbn.normal')){
            $productData['normal_purchase_price']   = $params['cost_unit_price'];
            $productData['purchase_makeup_rate']    = $params['cost_makeup_rate'];
        }else{
            $isNormal = false;
        }
        // 標準販売単価
        if($params['sales_kbn'] === config('const.unitPriceKbn.normal')){
            $productData['normal_sales_price']      = $params['sales_unit_price'];
            $productData['sales_makeup_rate']       = $params['sales_makeup_rate'];
        }else{
            $isNormal = false;
        }
        // 仕入・販売 区分の両方が標準の場合に粗利率をセット
        if($isNormal){
            $productData['normal_gross_profit_rate']    = $params['gross_profit_rate'];
        }

        $productData['draft_flg']           = $draftflg;
        $productData['auto_flg']            = $autoFlg;
        $productData['intangible_flg']      = config('const.flg.off');
        $productData['del_flg']             = config('const.flg.off');

        // 登録
        $productId = $this->Product->addDraft($productData);

        return $productId;
    }

    /**
     * 超過元見積明細の情報を数量超過見積明細に反映する
     * @param $quoteNo      見積番号
     * @param $customerId   得意先ID
     */
    public function updateQuoteToAddLayerQuote($quoteNo, $customerId){
        // 保存後の見積明細データ取得
        $afterQuoteDetailData = $this->QuoteDetail->getByVer($quoteNo, config('const.quoteCompVersion.number'));
        $overQuoteDetailDataList = $afterQuoteDetailData->where('over_quote_detail_id', '!=', 0)->toArray();
        foreach($overQuoteDetailDataList as $record){
            // 数量超過で追加された見積明細IDの更新
            // 超過元見積明細取得
            $overQuoteDetailData    = $afterQuoteDetailData->firstWhere('id', $record['over_quote_detail_id'])->toArray();
            $record['product_id']   = $overQuoteDetailData['product_id'];
            $record['product_code'] = $overQuoteDetailData['product_code'];
            $record['product_name'] = $overQuoteDetailData['product_name'];
            $record['model']        = $overQuoteDetailData['model'];
            $record['maker_id']     = $overQuoteDetailData['maker_id'];
            $record['maker_name']   = $overQuoteDetailData['maker_name'];
            $record['supplier_id']  = $overQuoteDetailData['supplier_id'];
            $record['supplier_name']= $overQuoteDetailData['supplier_name'];
            $record['min_quantity'] = $overQuoteDetailData['min_quantity'];
            $record['order_lot_quantity']   = $overQuoteDetailData['order_lot_quantity'];
            
            $record['quantity_per_case']= $overQuoteDetailData['quantity_per_case'];
            $record['set_kbn']          = $overQuoteDetailData['set_kbn'];
            $record['class_big_id']     = $overQuoteDetailData['class_big_id'];
            $record['class_middle_id']  = $overQuoteDetailData['class_middle_id'];
            $record['class_small_id']   = $overQuoteDetailData['class_small_id'];
            $record['tree_species']     = $overQuoteDetailData['tree_species'];
            $record['grade']            = $overQuoteDetailData['grade'];
            $record['length']           = $overQuoteDetailData['length'];
            $record['thickness']        = $overQuoteDetailData['thickness'];
            $record['width']            = $overQuoteDetailData['width'];
            
            $record['unit']         = $overQuoteDetailData['unit'];
            $record['stock_unit']   = $overQuoteDetailData['stock_unit'];
            $record['regular_price']    = $overQuoteDetailData['regular_price'];
            $record['allocation_kbn']   = $overQuoteDetailData['allocation_kbn'];
            $record['cost_kbn']     = $overQuoteDetailData['cost_kbn'];
            $record['sales_kbn']    = $overQuoteDetailData['sales_kbn'];
            $record['cost_unit_price']  = $overQuoteDetailData['cost_unit_price'];
            $record['sales_unit_price'] = $overQuoteDetailData['sales_unit_price'];
            $record['cost_makeup_rate'] = $overQuoteDetailData['cost_makeup_rate'];
            $record['sales_makeup_rate']= $overQuoteDetailData['sales_makeup_rate'];
            // 管理数
            $record['stock_quantity']   = $this->SystemUtil->calcStock($record['quote_quantity'], $record['min_quantity']);
            // 仕入総額 = 数量 * 仕入単価
            $record['cost_total']       = $this->SystemUtil->calcTotal($record['quote_quantity'], $record['cost_unit_price'], true);
            // 販売総額 = 数量 * 販売単価
            $record['sales_total']      = $this->SystemUtil->calcTotal($record['quote_quantity'], $record['sales_unit_price'], false);
            // 粗利総額 = 販売総額 - 仕入総額
            $record['profit_total']     = $this->SystemUtil->calcProfitTotal($record['sales_total'], $record['cost_total']);
            // 粗利率 = 粗利総額 / 販売総額 * 100
            $record['gross_profit_rate']= $this->SystemUtil->calcRate($record['profit_total'], $record['sales_total']); 
            $saveResult = $this->QuoteDetail->updateByIdEx($record['id'], $record, $customerId);
        }
    }

    /**
     * 見積の情報を発注に反映する
     * @param $quoteNo      見積番号
     */
    public function updateQuoteToOrder($quoteNo){
        // 全ての発注明細取得
        $orderDetailDataList = $this->QuoteDetail->getOrderDetailList($quoteNo, config('const.quoteCompVersion.number'));
        foreach($orderDetailDataList as $key => $record){
            // 発注明細の更新(見積明細のデータで上書き)
            $orderDetailData = [];
            // 商品変更時に発注明細の入荷予定日をクリア
            if($record['product_id'] !== $record['order_product_id']){
                $orderDetailData['arrival_plan_date'] = null;
            }
            $orderDetailData['id']           = $record['order_detail_id'];
            $orderDetailData['product_id']   = $record['product_id'];
            $orderDetailData['product_code'] = $record['product_code'];
            $orderDetailData['product_name'] = $record['product_name'];
            $orderDetailData['model']        = $record['model'];
            $orderDetailData['maker_id']     = $record['maker_id'];
            $orderDetailData['maker_name']   = $record['maker_name'];
            $orderDetailData['unit']         = $record['unit'];
            $orderDetailData['stock_unit']   = $record['stock_unit'];
            $orderDetailData['regular_price']= $record['regular_price'];
            $orderDetailData['sales_kbn']    = $record['sales_kbn'];
            $orderDetailData['cost_unit_price']  = $record['cost_unit_price'];
            $orderDetailData['sales_unit_price'] = $record['sales_unit_price'];
            $orderDetailData['cost_makeup_rate'] = $record['cost_makeup_rate'];
            $orderDetailData['sales_makeup_rate']= $record['sales_makeup_rate'];

            // 管理数
            $orderDetailData['stock_quantity']   = $this->SystemUtil->calcStock($record['order_quantity'], $record['min_quantity']);
            // 仕入総額 = 数量 * 仕入単価
            $orderDetailData['cost_total']       = $this->SystemUtil->calcTotal($record['order_quantity'], $orderDetailData['cost_unit_price'], true);
            // 販売総額 = 数量 * 販売単価
            $orderDetailData['sales_total']      = $this->SystemUtil->calcTotal($record['order_quantity'], $orderDetailData['sales_unit_price'], false);
            // 粗利総額 = 販売総額 - 仕入総額
            $orderDetailData['profit_total']     = $this->SystemUtil->calcProfitTotal($orderDetailData['sales_total'], $orderDetailData['cost_total']);
            // 粗利率 = 粗利総額 / 販売総額 * 100
            $orderDetailData['gross_profit_rate']= $this->SystemUtil->calcRate($orderDetailData['profit_total'], $orderDetailData['sales_total']); 
            if(!$this->OrderDetail->updateById($orderDetailData)){
                //throw new \Exception(config('message.error.save'));
            }
        }
     
        // 発注更新（仕入総計、販売総計、粗利総計）
        $this->Order->updateTotalKngByQuoteNo($quoteNo);
    }
    
    /**
     * 入荷先リスト取得
     * @param $supplierId       仕入先ID
     * @param $matterAddressId  案件の住所ID
     */
    public function getDeliveryAddressList($supplierId = null, $matterAddressId = null){
        return $this->Warehouse->getDeliveryAddressComboList($supplierId, $matterAddressId);
    }

    /**
     * 数量超過の見積明細の登録/更新/削除をする
     * @param $quoteId  見積ID
     * @param $quoteNo  見積番号
     */
    public function updateOverQuoteDetailData($quoteId, $quoteNo){
        // 追加部材情報取得
        $constructionAddFlgData = $this->Construction->getAddFlgData();
        
        $addQuoteDetailInfo = [];
        $tmp = $this->QuoteDetail->getConstractionDepthData($quoteNo, config('const.quoteCompVersion.number'), $constructionAddFlgData->id, config('const.quoteConstructionInfo.depth'))->first();

        $addQuoteDetailInfo['construction_id']  = $tmp->construction_id;
        $addQuoteDetailInfo['parent_quote_detail_id'] = $tmp->id;
        $addQuoteDetailInfo['depth']            = config('const.quoteConstructionInfo.depth') + 1;
        $addQuoteDetailInfo['tree_path']        = $tmp->id;
        
        $this->QuoteDetail->updateOverQuoteDetailData($quoteId, $quoteNo, $addQuoteDetailInfo);
        // 追加部材直下の連番振りなおし
        $addQuoteDetailInfo = $this->QuoteDetail->getConstractionDepthData($quoteNo, config('const.quoteCompVersion.number'), $addQuoteDetailInfo['construction_id'], (config('const.quoteConstructionInfo.depth')+1))->toArray();
        $seqNo = 0;
        foreach($addQuoteDetailInfo as $row){
            $row['seq_no'] = ++$seqNo;
            $this->QuoteDetail->updateByIdEx($row['id'], $row);
        }
    }

    /**
     * 商品の適用開始日チェック(getOrderDetailDataから呼び出す)
     * @param $gridDataList     グリッドデータ(対象データのため一時保存時はグリッド全体　発注時はチェックが付いた対象のデータのみの配列)
     * @param $isTmp            一時保存
     * @throws Exception
     */
    public function productStartDateIsValid($gridDataList, $isTmp){
        if (count($gridDataList) === 0) {
            return;
        }

        // 全ての商品マスタデータを取得
        // $productAllList     = $this->Product->getComboList();
        // グリッドに存在する商品IDで商品マスタデータを取得
        $productIdList = array_column($gridDataList, "product_id");
        $productList = $this->Product->getByIds($productIdList);

        $toDay              = new Carbon();
        foreach($gridDataList as $row){
            if ($row['product_id'] === 0) continue;

            // 商品の適用開始日チェック(発注済みのチェック)
            // $productInfo = $productAllList->where('id', $row['product_id'])->first();
            $productInfo = $productList->where('id', $row['product_id'])->first();
            if($productInfo !== null){
                $startDate =  $productInfo->start_date;
                if(Common::nullToBlank($startDate) !== ''){
                    $startDate = Carbon::parse($startDate.' 00:00:00');
                    // <=
                    if(!$startDate->lt($toDay)){
                        if($isTmp){
                            // 一時保存時のみチェック
                            if(isset($row['order_id_list']) && Common::nullToBlank($row['order_id_list']) !== ''){
                                throw new \Exception(str_replace('$product_code', $row['product_code'], config('message.error.order.start_date_2')));
                            }
                        }else{
                            // 発注登録時のみチェック
                            throw new \Exception(str_replace('$product_code', $row['product_code'], config('message.error.order.start_date_1')));
                        }
                    }
                }
            }
        }
    }

    // /**
    //  * 納品データのチェック
    //  * 該当の見積明細の納品がある場合、商品の変更と金額の変更はNG
    //  * 
    //  * @param string $quoteNo           見積番号
    //  * @param array $deliveryDataList   更新前の納品データと見積明細
    //  * @throws
    //  */
    // public function isValidDelivery($quoteNo, $deliveryDataList){
    //     $afterQuoteDetailData = $this->QuoteDetail->getByVer($quoteNo, config('const.quoteCompVersion.number'));

    //     // 納品データの数だけ回す
    //     foreach($deliveryDataList as $row){
    //         // 更新後の見積明細
    //         $afterRow = $afterQuoteDetailData->where('id', $row['id'])->first()->toArray();
    //         foreach($afterRow as $column => $data){
    //             switch($column){
    //                 case 'id':
    //                 case 'seq_no':
    //                 case 'memo':
    //                 case 'update_user':
    //                 case 'update_at':
    //                     break;
    //                 default:
    //                     if($row[$column] !== $afterRow[$column]){
    //                         throw new \Exception(str_replace('$product_code', $row['product_code'], config('message.error.order.delivery')));
    //                     }
    //                 break;
    //             }
    //         }
    //     }
    // }

    /**
     * 納品/売上データがある場合の見積明細の変更状態チェック
     * 該当の見積明細の納品/売上がある場合、商品の変更と金額の変更はNG
     * 
     * @param array $beforeList         更新前の見積明細データ
     * @param array $afterList          更新後の見積明細データ
     * @param array $isDelivery         納品のチェックか(エラーメッセージの共通化)
     * @throws
     */
    public function isValidQuoteChangeState($beforeList, $afterList, $isDelivery = true){
        // 納品/売上データの数だけ回す
        foreach($beforeList as $row){
            // 更新後の見積明細
            $afterRow = $afterList->where('id', $row['id'])->first()->toArray();
            foreach($afterRow as $column => $data){
                switch($column){
                    case 'id':
                    case 'seq_no':
                    case 'quantity_per_case':
                    case 'set_kbn':
                    case 'unit':
                    case 'stock_unit':
                    case 'class_big_id':
                    case 'class_middle_id':
                    case 'class_small_id':
                    case 'tree_species':
                    case 'grade':
                    case 'length':
                    case 'thickness':
                    case 'width':
                    case 'row_print_flg':
                    case 'price_print_flg':
                    case 'memo':
                    case 'update_user':
                    case 'update_at':
                        // 変更OK
                        break;
                    case 'regular_price':
                    case 'cost_unit_price':
                    case 'sales_unit_price':
                    case 'cost_makeup_rate':
                    case 'sales_makeup_rate':
                    case 'cost_total':
                    case 'sales_total':
                    case 'gross_profit_rate':
                    case 'profit_total':
                        // 売上データ存在時、変更NG
                        if($row[$column] !== $afterRow[$column]){
                            if(!$isDelivery){
                                throw new \Exception(str_replace('$product_name', $row['product_name'], config('message.error.order.sales_detail')));
                            }
                        }
                        break;
                    default:
                        if($row[$column] !== $afterRow[$column]){
                            if($isDelivery){
                                throw new \Exception(str_replace('$product_code', $row['product_code'], config('message.error.order.delivery')));
                            }else{
                                throw new \Exception(str_replace('$product_name', $row['product_name'], config('message.error.order.sales_detail')));
                            }
                        }
                    break;
                }
            }
        }
    }
    
    /**
     * 発注のアップロードファイルパスを返す
     * @param $matterId 案件ID
     * @param $orderId  発注ID
     */
    public function getUploadFilePath($matterId, $orderId){
        return config('const.uploadPath.order').$matterId.'/'.$orderId;
    }

    /**
     * 指定フォルダから対象のファイルを削除する
     * @param $deleteFolderName 削除対象のフォルダ
     * @param $fileNameList     削除対象のファイル名の配列
     */
    public function deleteFile($deleteFolderName, $fileNameList){
        $deleteList = [];
        foreach ($fileNameList as $fileName) {
            $deleteList[] = $deleteFolderName.'/'.$fileName;
        }
        Storage::delete($deleteList);
    }

    /**
     * フォルダを移動する
     * @param $beforeFolderName 移動前のフォルダパス
     * @param $afterFolderName  移動後のフォルダパス
     */
    public function moveFolder($beforeFolderName, $afterFolderName){
        $tmpFileNameList = Storage::files($beforeFolderName);
        if (count($tmpFileNameList) >= 1) {
            Storage::move($beforeFolderName, $afterFolderName);
        }
    }

    /**
     * 発注ヘッダーのバリデーションチェック
     * @param Request $request
     * @return type
     * @throws Exception
     */
    public function isValidOrder(Request $request){
        $this->controller->validate($request, [
            'maker_id'          => 'required|integer|min:1',
            'supplier_id'       => 'required|integer|min:1',
            'delivery_address'  => 'required',
        ],
        [
            'maker_id.min'      => '必須です',
            'supplier_id.min'   => '必須です',
        ]);
    }

    /**
     * アップロードファイルのバリデーションチェック
     * @param Request $request
     * @return boolean
     * @throws Exception
     */
    public function isValidFile(Request $request){
        $checkList = [];
        foreach ($request->files as $fileNm => $fileObj) {
            $checkList = array_merge($checkList, array($fileNm => 'mimes_except:'.config('const.forbidden_extention')));
        }

        if (count($checkList) > 0) {
            return $this->controller->validate($request, $checkList);
        } else {
            return true;
        }
    }

    /**
     * 自分がロックしているか
     * @param $keys      
     * @throws Exception
     */
    public function isOwnLocked(...$keys){
        $SCREEN_NAME = $this->controller::SCREEN_NAME;
        $tableNameList = config("const.lockList.$SCREEN_NAME");
        if(!$this->LockManage->isOwnLocks($SCREEN_NAME, $tableNameList, $keys)){
            throw new \Exception(config('message.error.getlock'));
        }
    }

    /**
     * ロック解除する
     * @param $keys
     * @throws Exception
     */
    public function unLock(...$keys){
        $SCREEN_NAME = $this->controller::SCREEN_NAME;
        if(!$this->LockManage->deleteLockInfo($SCREEN_NAME, $keys, Auth::user()->id)){
            throw new \Exception(config('message.error.getlock'));
        }
    }

}
