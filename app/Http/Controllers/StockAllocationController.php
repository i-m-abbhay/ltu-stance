<?php
namespace App\Http\Controllers;

use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Arrival;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Base;
use App\Models\Construction;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Storage;
use Carbon\Carbon;
use App\Models\Matter;
use App\Models\Customer;
use App\Models\Department;
use App\Models\LockManage;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\ProductstockShelf;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\QuoteVersion;
use App\Models\Reserve;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\StaffDepartment;
use App\Models\Staff;
use App\Models\Warehouse;
use App\Models\WarehouseMove;
use DB;
use Session;

/**
 * 在庫引当
 */
class StockAllocationController extends Controller
{
    const SCREEN_NAME = 'stock-allocation';

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
     * @return type
     */
    public function index()
    {
        // データ取得
        $customerList = (new Customer())->getComboList();
        // $matterList = (new Matter())->getComboList(config('const.flg.off'));

        // 案件リスト取得
        // $matterList = (new Matter())->getComboList();
        $matterList = (new Matter())->getHasQuoteComboList();

        $departmentList = (new Department())->getComboList();
        $staffList = (new Staff())->getComboList();
        $warehouseList = (new Warehouse())->getComboList();

        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $departmentName = (new Department())->getById($departmentId)['department_name'];
        $staffName = (new Staff())->getById(Auth::id())['staff_name'];
        $departData = (new Department())->getById($departmentId);
        $kbn = config('const.ownerKbn.company');
        $warehouseData = (new Warehouse())->getByOwnerId($departData['base_id'], $kbn)->first();
        $warehouseName = $warehouseData['warehouse_name'];
        // $productList = (new Product())->getComboList();
        $warehouseId = $warehouseData['id'];

        $initSearchParams = collect([
            'staff_name' => $staffName,
            'department_name' => $departmentName,
            'warehouse_id' => $warehouseId,
            'warehouse_name' => $warehouseName,
        ]);

        return view('Stock.stock-allocation')
                ->with('customerList', $customerList)
                ->with('matterList', $matterList)
                ->with('departmentList', $departmentList)
                ->with('staffList', $staffList)
                ->with('warehouseList', $warehouseList)
                // ->with('productList', $productList)
                ->with('initSearchParams', $initSearchParams)
                ;
    }
    
    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();

            // ログイン者がロックしているフラグ
            $isOwnLock = config('const.flg.off');
            $isEditable = config('const.flg.on');

            $LockManage = new LockManage();
            // $Construction = new Construction();
            $Reserve = new Reserve();
            $ProductstockShelf = new ProductstockShelf();
            $Reserve = new Reserve();
            // $Order = new Order();
            // $OrderDetail = new OrderDetail();
            $Matter = new Matter();
            $lockData = null;
            $orderData = null;
            $matterData = null;

            if (!empty($params['quote_id_list'])) {
                // ロック解放
                $keys = array($params['quote_id_list']);
                $LockManage = new LockManage();
                $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            }
            
            // 一覧データ取得
            $Arrival = new Arrival();
            $parentList = (new QuoteVersion())->getVersionZeroByMatter($params);
            
            $matterData = $Matter->getByMatterNo($params['matter_no']);
            $customerId = $matterData['customer_id'];
            // $quantityList = (new ProductstockShelf())->getAllQuantity();

            // 見積に存在する商品の在庫データ取得
            $productIdList = collect($parentList)->pluck('product_id')->unique()->toArray();
            $quantityList = $ProductstockShelf->getQuantityList($customerId, $productIdList);
            $quantityList = json_decode(json_encode($quantityList), true);
            $reserveList = $Reserve->getReserveByMatterId($matterData['id']);

            // グリッド用に整形
            $convertData = $this->convertData($quantityList, $params['matter_no']);

            $matterInfo = collect([
                'from_warehouse_id_1' => 0,
                'from_warehouse_id_2' => 0,
            ]);

            $orderData = [];
            $orderData['matter_id'] = null;
            if (count($parentList) > 0) {
                $quoteNo = collect($parentList)->pluck('quote_no')->unique()->toArray();

                // $orderData = collect((new Order())->getByMatterQuote($params['matter_no'], $quoteNo))->sortByDesc('update_at')->first();

                $parentIDs = collect($parentList)->keyBy('id')->pluck('id')->toArray();
                $matterId = collect($parentList)->keyBy('matter_id')->pluck('matter_id')->first();
                $matterData = $Matter->getById($matterId);
                $orderData['matter_id'] = $matterId;

                $matterInfo = collect([
                    'from_warehouse_id_1' => $matterData['from_warehouse_id1'],
                    'from_warehouse_id_2' => $matterData['from_warehouse_id2'],
                    'complete_flg' => $matterData['complete_flg'],
                ]);

                $quoteIdList = collect($parentList)->pluck('quote_id')->unique()->toArray();

                foreach ($parentList as $key => $row) {
                    $orderReserveData = $Reserve->getSumOrderReserveQuantity($parentList[$key]['id']);
                    $stockReserveData = $Reserve->getSumReserveQuantityByStockFlg($parentList[$key]['id'], config('const.stockFlg.val.stock'));
                    $keepReserveData = $Reserve->getSumReserveQuantityByStockFlg($parentList[$key]['id'], config('const.stockFlg.val.keep'));

                    $parentList[$key]['sum_order_reserve_quantity'] = $orderReserveData['sum_reserve_quantity'];
                    $parentList[$key]['sum_stock_reserve_quantity'] = $stockReserveData['sum_reserve_quantity'];
                    $parentList[$key]['sum_keep_reserve_quantity'] = $keepReserveData['sum_reserve_quantity'];
                }

                // 編集権限がある場合
                if ($isEditable === config('const.authority.has')) {
                    // 画面名から対象テーブルを取得
                    $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                    $keys = $quoteIdList;

                    DB::beginTransaction();
                    $lockCnt = 0;
                    foreach ($tableNameList as $i => $tableName) {
                        // ロック確認
                        $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                        // // GETパラメータからモード取得
                        // $mode = config('const.mode.show');

                        if (!$isLocked && $matterData['complete_flg'] === config('const.flg.off')) {
                            // 編集モードかつ、ロックされていないかつ、案件完了していない場合はロック取得
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

        return \Response::json(
                [
                    'parentList' => $parentList, 
                    'quantityList' => $convertData, 
                    'reserveList' => $reserveList, 
                    'matterInfo' => $matterInfo,
                    'orderData' => $orderData,
                    'isOwnLock' => $isOwnLock,
                    'isEditable' => $isEditable,
                    'lockData' => $lockData,
                ]);
    }

    /**
     * 子グリッドデータ用に整形
     *
     * @param [type] $list
     * @return void
     */
    private function convertData($list, $matterNo) 
    {
        $convertData = [];
        $Reserve = new Reserve();
        $Arrival = new Arrival();
        
        $reserveList = $Reserve->getReserveQuantityGroupByWarehouseAndProduct();
        $arrivalList = $Arrival->getArrivalDataByWarehouseAndProduct($matterNo);

        foreach($list as $key => $row) { 
            // 初期値セット
            $record = [];
            $record['warehouse_id']             = $row['warehouse_id'];
            $record['product_id']               = $row['product_id'];  
            $record['inventory_quantity']       = (int)Common::nullorBlankToZero($row['inventory_quantity']);
            $record['actual_quantity']          = (int)Common::nullorBlankToZero($row['quantity']);
            $record['keep_quantity']            = (int)Common::nullorBlankToZero($row['keep_quantity']);
            $record['customer_keep_quantity']   = (int)Common::nullorBlankToZero($row['customer_keep_quantity']);
            
            $record['order_reserve_quantity'] = 0;
            $record['stock_reserve_quantity'] = 0;
            $record['keep_reserve_quantity'] = 0;
            $record['order_arrival_quantity'] = 0;
            $record['stock_arrival_quantity'] = 0;
            $record['next_arrival_date'] = '';
            // $arrivalPlanData = $Order->getNextArrivalDate($quantityList[$key]['warehouse_id'], $quantityList[$key]['product_id']);
            // $reserveData = $Reserve->getReserveQuantityByWarehouseAndProduct($list[$key]['warehouse_id'], $list[$key]['product_id']);
            // $arrivalData = $Arrival->getArrivalDataByWarehouseAndProduct($list[$key]['warehouse_id'], $list[$key]['product_id'], $matterNo);

            $reserveData = collect($reserveList)->where('from_warehouse_id', $list[$key]['warehouse_id'])->where('product_id', $list[$key]['product_id']);
            $arrivalData = collect($arrivalList)->where('delivery_address_id', $list[$key]['warehouse_id'])->where('product_id', $list[$key]['product_id']);
            foreach($reserveData as $i => $data) {
                switch($reserveData[$i]['stock_flg']) {
                    case config('const.stockFlg.val.order'):
                        $record['order_reserve_quantity'] = (int)Common::nullorBlankToZero($reserveData[$i]['reserve_quantity']);
                        break;
                    case config('const.stockFlg.val.stock'):
                        $record['stock_reserve_quantity'] = (int)Common::nullorBlankToZero($reserveData[$i]['reserve_quantity']);
                        break;
                    case config('const.stockFlg.val.keep'):
                        $record['keep_reserve_quantity'] = (int)Common::nullorBlankToZero($reserveData[$i]['reserve_quantity']);
                        break;
                }
            }

            foreach($arrivalData as $i => $data) {
                switch($arrivalData[$i]['own_stock_flg']) {
                    case config('const.flg.off'):
                        $record['order_arrival_quantity'] = (int)Common::nullorBlankToZero($arrivalData[$i]['arrival_quantity']);
                        $record['next_arrival_date'] = $arrivalData[$i]['next_arrival_date'];
                        break;
                    case config('const.flg.on'):
                        $record['stock_arrival_quantity'] = (int)Common::nullorBlankToZero($arrivalData[$i]['arrival_quantity']);
                        $record['next_arrival_date'] = $arrivalData[$i]['next_arrival_date'];
                        break;
                }
            }
            // 有効在庫
            $record['active_actual_quantity'] = $record['inventory_quantity'] - $record['stock_reserve_quantity'];
            // 有効預かり在庫
            $record['active_keep_quantity'] = $record['customer_keep_quantity'] - $record['keep_reserve_quantity'];
            $record['customer_keep_quantity'] = $record['customer_keep_quantity'] - $record['keep_reserve_quantity'];

            $convertData[] = $record;
        }

        return $convertData;
    }

    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = false;   
        $resultMsg = '';  
        // リクエストデータ取得
        $params = $request->request->all();
        $header = json_decode($params['headerData'], true);
        $reserveList = json_decode($params['reserveList'], true);

        // バリデーションチェック
        // $this->isValid($request);

        DB::beginTransaction();
        $Reserve = new Reserve();
        $Matter = new Matter();
        $Order = new Order();
        $QuoteDetail = new QuoteDetail();
        $Construction = new Construction();
        $SystemUtil = new SystemUtil();
        $QuoteVersion = new QuoteVersion();
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        $Common = new Common();
        $WarehouseMove = new WarehouseMove();

        $keys = [];
        try {
            $saveResult = false;
            $quoteUpdateFlg = false;
            $overQuoteUpdateFlg = false;
            $isUpdateError = false;

            $quoteNo = '';
            $quoteId = '';
            if (!empty($header[0])) {                
                $quoteNo = $header[0]['quote_no'];
                $quoteId = $header[0]['quote_id'];
            }

            $setQuoteDetailList = $QuoteDetail->getSetProductList($quoteNo, config('const.quoteCompVersion.number'));

            foreach ($header as $key => $hdr) {
                $updateReserve = false;
                $beforeInputQuantity = 0;

                $matterId = $hdr['matter_id'];
                foreach ($reserveList[$hdr['id']] as $quoteDetailId => $rec) {
                    $saveFlg = true;
                    // 倉庫未設定を除外
                    if (empty($rec['from_warehouse_id'])) {
                        $saveFlg = false;
                    }
                    // データ整形
                    $rec['matter_id'] = $matterId;
                    $rec['quote_id'] = $hdr['quote_id'];
                    // 在庫品
                    $newFlg = false;
                    if ($saveFlg) {
                        if (empty($rec['stock_id'])) {
                            $newFlg = true;
                        }
                        if (!empty($rec['input_actual_quantity'])) {
                            $rec['id'] = $rec['stock_id'];
                            if ($newFlg) {
                                // 登録
                                $rec['allocation_date'] = Carbon::now();
                                $rec['reserve_quantity'] = $rec['input_actual_quantity'];
                                $rec['reserve_quantity_validity'] = $rec['reserve_quantity'];
                                $saveResult = $Reserve->add($rec, config('const.stockFlg.val.stock'));
                                $updateReserve = true;
                            } else {
                                // 更新
                                $own_reserve = $Reserve->getById($rec['id']);
                                $beforeInputQuantity += (int)$own_reserve['reserve_quantity'];
                                if (!empty($own_reserve['reserve_quantity']) && $rec['input_actual_quantity'] != 0 && ($own_reserve['reserve_quantity'] + $rec['input_actual_quantity']) != 0) {
                                // if (!empty($own_reserve['reserve_quantity']) && $rec['input_actual_quantity'] != $own_reserve['reserve_quantity']) {
                                    // 入力値チェック
                                    $this->validInputReserveQuantity($rec['id'], $rec['input_actual_quantity']);

                                    $reserveQuantityValidity = $own_reserve['reserve_quantity_validity'];
                                    // 入力値と元引当数の差分
                                    // $diffQuantity = $rec['input_actual_quantity'] - $own_reserve['reserve_quantity'];
                                    $rec['reserve_quantity'] = $own_reserve['reserve_quantity'] + $rec['input_actual_quantity'];
                                    $rec['reserve_quantity_validity'] = $reserveQuantityValidity + $rec['input_actual_quantity'];
                                    $rec['finish_flg'] = config('const.flg.off');
                                    if ($rec['reserve_quantity_validity'] == 0) {
                                        // 有効引当数が0であれば完了フラグを立てる
                                        $rec['finish_flg'] = config('const.flg.on');
                                    }

                                    if ($rec['input_actual_quantity'] > 0) {
                                        $rec['allocation_date'] = Carbon::now();
                                    }

                                    $saveResult = $Reserve->updateById($rec);
                                    $updateReserve = true;
                                } else if($own_reserve['reserve_quantity'] + $rec['input_actual_quantity'] == 0) {
                                    $rec['id'] = $rec['stock_id'];
                                    $own_reserve = $Reserve->getById($rec['id']);
                                    $beforeInputQuantity += (int)$own_reserve['reserve_quantity'];
                                    if (!empty($own_reserve['reserve_quantity']) && ($own_reserve['reserve_quantity'] + $rec['input_actual_quantity']) == 0) {
                                    // if (!empty($own_reserve['reserve_quantity']) && $rec['input_actual_quantity'] != $own_reserve['reserve_quantity']) {
                                        // 入力値チェック
                                        $this->validInputReserveQuantity($rec['id'], $rec['input_actual_quantity']);
        
                                        // 引当数を0に変更(引当解除)
                                        $saveResult = $Reserve->deleteById($rec['stock_id']);
                                        $updateReserve = true;
                                    }
                                }
                            }
                        }
                    }
                    // 預かり品
                    $newFlg = false;
                    if ($saveFlg) {
                        if (empty($rec['keep_id'])) {
                            $newFlg = true;
                        }
                        if (!empty($rec['input_keep_quantity'])) {
                            $rec['id'] = $rec['keep_id'];
                            if ($newFlg) {
                                // 登録
                                $rec['allocation_date'] = Carbon::now();
                                $rec['reserve_quantity'] = $rec['input_keep_quantity'];
                                $rec['reserve_quantity_validity'] = $rec['reserve_quantity'];
                                $saveResult = $Reserve->add($rec, config('const.stockFlg.val.keep'));
                                $updateReserve = true;
                            } else {
                                // 更新
                                $own_reserve = $Reserve->getById($rec['id']);
                                $beforeInputQuantity += (int)$own_reserve['reserve_quantity'];
                                if (!empty($own_reserve['reserve_quantity']) && $rec['input_keep_quantity'] != 0 && ($own_reserve['reserve_quantity'] + $rec['input_keep_quantity']) != 0) {
                                    // 入力値チェック
                                    $this->validInputReserveQuantity($rec['id'], $rec['input_keep_quantity']);

                                    $reserveQuantityValidity = $own_reserve['reserve_quantity_validity'];
                                    // 入力値と元引当数の差分
                                    // $diffQuantity = $rec['input_keep_quantity'] - $own_reserve['reserve_quantity'];
                                    $rec['reserve_quantity'] = $own_reserve['reserve_quantity'] + $rec['input_keep_quantity'];
                                    $rec['reserve_quantity_validity'] = $reserveQuantityValidity + $rec['input_keep_quantity'];
                                    $rec['finish_flg'] = config('const.flg.off');
                                    if ($rec['reserve_quantity_validity'] == 0) {
                                        // 有効引当数が0であれば完了フラグを立てる
                                        $rec['finish_flg'] = config('const.flg.on');
                                    }
                                    if ($rec['input_keep_quantity'] > 0) {
                                        $rec['allocation_date'] = Carbon::now();
                                    }

                                    $saveResult = $Reserve->updateById($rec);
                                    $updateReserve = true;
                                } else if($own_reserve['reserve_quantity'] + $rec['input_keep_quantity'] == 0) {
                                    $rec['id'] = $rec['keep_id'];
                                    $own_reserve = $Reserve->getById($rec['id']);
                                    $beforeInputQuantity += (int)$own_reserve['reserve_quantity'];
                                    if (!empty($own_reserve['reserve_quantity']) && ($own_reserve['reserve_quantity'] + $rec['input_keep_quantity']) == 0) {
                                        // 入力値チェック
                                        $this->validInputReserveQuantity($rec['id'], $rec['input_keep_quantity']);
        
                                        // 引当数を0に変更(引当解除)
                                        $saveResult = $Reserve->deleteById($rec['keep_id']);
                                        $updateReserve = true;
                                    }
                                }
                            }
                        }
                    }
                }

                // 発注数取得
                $quoteDetailId = $header[$key]['id'];
                $orderData = $Order->getSumOrderQuantity($quoteDetailId)->first();
                // 元見積明細取得
                $quoteDetailData = $QuoteDetail->getDataListByIds(array($quoteDetailId))->first();
                $beforeQuoteDetailData  = $QuoteDetail->getByVer($quoteNo, config('const.quoteCompVersion.number'))->toArray();
                
                // 返品数取得
                $returnQuantity = $WarehouseMove->getSumReturnByQuoteDetailAndProduct($quoteDetailId);

                $rtnQuant = 0;
                $Common = new Common();
                // foreach ($tmpList as $row) {
                //     $rtnQuant += (int)$Common->nullorBlankToZero($row['return_quantity']);
                // }
                $reserveData = $Reserve->getSumOrderReserveQuantity($quoteDetailId);
                $minusQuantity = Common::nullorBlankToZero($returnQuantity['return_quantity']) + $reserveData['sum_reserve_quantity'];
                $sum_quantity = ((int)$orderData['sum_stock_quantity'] + (int)$header[$key]['total_reserve_quantity']) - $minusQuantity;
                $updateData = [];

                // 数量超過チェック
                if ((int)$header[$key]['stock_quantity'] < $sum_quantity && $updateReserve) {
                    $parentQuoteDetailData = $setQuoteDetailList->where('quote_detail_id', $quoteDetailData['parent_quote_detail_id'])->first();
                    $setFlg = config('const.flg.off');
                    if ($parentQuoteDetailData['set_flg'] == config('const.flg.on')){
                        $setFlg = config('const.flg.on');
                    }

                    if ($setFlg == config('const.flg.on')) {
                        // 一式商品の子部品である (見積数更新)
                        // 登録データ整形
                        $updateData['id'] = $quoteDetailId;
                        $updateData['quote_quantity'] = $SystemUtil->calcQuantity($sum_quantity, $quoteDetailData['min_quantity']);
                        $updateData['stock_quantity'] = $sum_quantity;
                        $updateData['cost_total'] = $SystemUtil->calcTotal($updateData['quote_quantity'], $quoteDetailData['cost_unit_price'], true);
                        $updateData['sales_total'] = $SystemUtil->calcTotal($updateData['quote_quantity'], $quoteDetailData['sales_unit_price'], false);
                        $updateData['profit_total'] = $SystemUtil->calcProfitTotal($updateData['sales_total'], $updateData['cost_total']);
                        $updateData['gross_profit_rate'] = $SystemUtil->calcRate($updateData['profit_total'], $updateData['sales_total']);

                        $saveResult = $QuoteDetail->updateQuantityById($updateData);
                        $quoteUpdateFlg = true;
                    }
                    // $SystemUtil->deleteNotLayerRecord($overTreeData[0]);
                    if ($setFlg == config('const.flg.off')) {
                        // 一式商品の子部品でない (追加部材登録)
                        // 追加部材データ取得
                        $constData = $Construction->getAddFlgData();
                        $overTreeData = $QuoteDetail->getTreeData($quoteNo, $header[$key]['quote_version'], config('const.flg.on'));

                        $overTreeData = collect($overTreeData[0]['items'])->where('construction_id', $constData['id'])->first();
                        // 登録データ整形
                        $updateData['quote_no']                 = $quoteNo;
                        $updateData['quote_version']            = 0;
                        $updateData['layer_flg']                = config('const.flg.off');
                        $updateData['construction_id']          = $constData['id'];
                        $updateData['quote_quantity']           = $SystemUtil->calcQuantity(($sum_quantity - (int)$header[$key]['stock_quantity']), $quoteDetailData['min_quantity']);
                        $updateData['parent_quote_detail_id']   = $overTreeData['id'];
                        $updateData['seq_no']                   = 1;
                        $updateData['depth']                    = 1;
                        $updateData['tree_path']                = $overTreeData['id'];	
                        $updateData['set_flg']                  = $quoteDetailData['set_flg'];
                        $updateData['sales_use_flg']            = config('const.quoteConstructionInfo.sales_use_flg');
                        $updateData['product_id']               = $quoteDetailData['product_id'];
                        $updateData['product_code']             = $quoteDetailData['product_code'];
                        $updateData['product_name']             = $quoteDetailData['product_name'];
                        $updateData['model']                    = $quoteDetailData['model'];
                        $updateData['maker_id']                 = $quoteDetailData['maker_id'];
                        $updateData['maker_name']               = $quoteDetailData['maker_name'];
                        $updateData['supplier_id']              = $quoteDetailData['supplier_id'];
                        $updateData['supplier_name']            = $quoteDetailData['supplier_name'];
                        $updateData['min_quantity']             = $quoteDetailData['min_quantity'];
                        $updateData['stock_quantity']           = $sum_quantity - (int)$header[$key]['stock_quantity'] ;
                        $updateData['unit']                     = $quoteDetailData['unit'];
                        $updateData['stock_unit']               = $quoteDetailData['stock_unit'];
                        $updateData['order_lot_quantity']       = $quoteDetailData['order_lot_quantity'];
                        $updateData['quantity_per_case']        = $quoteDetailData['quantity_per_case'];
                        $updateData['set_kbn']                  = $quoteDetailData['set_kbn'];
                        $updateData['class_big_id']             = $quoteDetailData['class_big_id'];
                        $updateData['class_middle_id']          = $quoteDetailData['class_middle_id'];
                        $updateData['class_small_id']           = $quoteDetailData['class_small_id'];
                        $updateData['tree_species']             = $quoteDetailData['tree_species'];
                        $updateData['grade']                    = $quoteDetailData['grade'];
                        $updateData['length']                   = $quoteDetailData['length'];
                        $updateData['thickness']                = $quoteDetailData['thickness'];
                        $updateData['width']                    = $quoteDetailData['width'];
                        $updateData['regular_price']            = $quoteDetailData['regular_price'];
                        $updateData['allocation_kbn']           = $quoteDetailData['allocation_kbn'];
                        $updateData['cost_kbn']                 = $quoteDetailData['cost_kbn'];
                        $updateData['sales_kbn']                = $quoteDetailData['sales_kbn'];
                        $updateData['cost_unit_price']          = $quoteDetailData['cost_unit_price'];
                        $updateData['sales_unit_price']         = $quoteDetailData['sales_unit_price'];
                        $updateData['cost_makeup_rate']         = $quoteDetailData['cost_makeup_rate'];
                        $updateData['sales_makeup_rate']        = $quoteDetailData['sales_makeup_rate'];
                        $updateData['cost_total']               = $SystemUtil->calcTotal($updateData['quote_quantity'], $quoteDetailData['cost_unit_price'], true);
                        $updateData['sales_total']              = $SystemUtil->calcTotal($updateData['quote_quantity'], $quoteDetailData['sales_unit_price'], false);
                        $updateData['profit_total']             = $SystemUtil->calcProfitTotal($updateData['sales_total'], $updateData['cost_total']);
                        $updateData['gross_profit_rate']        = $SystemUtil->calcRate($updateData['profit_total'], (int)$updateData['sales_total']);
                        $updateData['memo']                     = '';
                        $updateData['row_print_flg']            = config('const.flg.off');
                        $updateData['price_print_flg']          = config('const.flg.off');
                        $updateData['received_order_flg']       = config('const.flg.off');
                        $updateData['complete_flg']             = config('const.flg.off');
                        $updateData['copy_quote_detail_id']     = 0;
                        $updateData['copy_received_order_flg']  = config('const.flg.off');
                        $updateData['copy_complete_flg']        = config('const.flg.off');
                        $updateData['add_flg']                  = config('const.flg.off');	
                        $updateData['over_quote_detail_id']     = $quoteDetailId;

                        // 見積明細 追加部材(数量超過登録/更新)
                        $detailId = $QuoteDetail->saveOverQuantity($updateData);
                        $overQuoteUpdateFlg = true;

                    }
                } else if((int)$header[$key]['stock_quantity'] >= $sum_quantity && $beforeInputQuantity >= ($beforeInputQuantity + (int)$header[$key]['total_input_quantity']) && $updateReserve) {
                    // 数量を減算した通常明細
                    $parentQuoteDetailData = $setQuoteDetailList->where('quote_detail_id', $quoteDetailData['parent_quote_detail_id'])->first();
                    $setFlg = config('const.flg.off');
                    if ($parentQuoteDetailData['set_flg'] == config('const.flg.on')){
                        $setFlg = config('const.flg.on');
                    }
                    $constData = $Construction->getAddFlgData();
                    $overTreeData = $QuoteDetail->getTreeData($quoteNo, $header[$key]['quote_version'], config('const.flg.on'));

                    $overTreeData = collect($overTreeData[0]['items'])->where('construction_id', $constData['id'])->first();

                    if ($setFlg == config('const.flg.off')) {
                        // 追加部材階層の見積明細を削除する
                        $quoteDetailParams = [];
                        $quoteDetailParams['over_quote_detail_id'] = $quoteDetailData['id'];
                        $quoteDetailParams['quote_quantity'] =  $SystemUtil->calcQuantity(($sum_quantity - (int)$header[$key]['stock_quantity']), $quoteDetailData['min_quantity']);
                        $detailId = $QuoteDetail->saveOverQuantity($quoteDetailParams);
                        $overQuoteUpdateFlg = true;
                    }
                }
            }                
            
            if ($overQuoteUpdateFlg) {
                // 追加部材直下の連番振りなおし
                $addQuoteDetailInfo = $QuoteDetail->getConstractionDepthData($quoteNo, config('const.quoteCompVersion.number'), $constData['id'], (config('const.quoteConstructionInfo.depth')+1))->toArray();
                $seqNo = 0;
                foreach($addQuoteDetailInfo as $row){
                    $row['seq_no'] = ++$seqNo;
                    $QuoteDetail->updateByIdEx($row['id'], $row);
                }
            }
            if ($overQuoteUpdateFlg || $quoteUpdateFlg) {
                // 階層ごとの金額計算
                $targetList = $SystemUtil->calcQuoteLayersTotal($quoteNo, config('const.quoteCompVersion.number'));
                foreach ($targetList as $id => $item) {
                    if ($id === 0) continue;
                    
                    $updateResult = $QuoteDetail->updateTotalKng($id, $item);
                }

                // 見積0版金額更新
                $updateResult = $QuoteVersion->updateTotalKng($quoteNo, config('const.quoteCompVersion.number'));
            }
            
            $keys = array($quoteId);

            // 倉庫1,2登録
            $params['id'] = $matterId;
            $mData = $Matter->getById($matterId);
            $upData = [];
            if (empty($mData['from_warehouse_id1']) && !empty($params['from_warehouse_id_1'])) {
                $upData['id'] = $matterId;
                $upData['from_warehouse_id1'] = $params['from_warehouse_id_1'];
            }
            if (empty($mData['from_warehouse_id2']) && !empty($params['from_warehouse_id_2'])) {
                $upData['id'] = $matterId;
                $upData['from_warehouse_id2'] = $params['from_warehouse_id_2'];
            }
            if (!empty($upData)) {
                $result = $Matter->updateWarehouseById($upData);
            }
            
            // ロック解放
            $keys = collect($keys)->unique()->toArray();
            $LockManage = new LockManage();
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);

            $saveResult = true;

            if ($saveResult && $delLock && !$isUpdateError) {
                DB::commit();
                $resultSts= true;
                Session::flash('flash_success', config('message.success.save'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            $resultMsg = $e->getMessage();
            Log::error($e);
            // Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json(['resultSts' => $resultSts, 'resultMsg' => $resultMsg]);
    }

    /**
     * 入力値チェック
     * 出荷済み数以下はエラー
     *
     * @param $row チェックする行
     * @return Exception
     */
    private function validInputReserveQuantity($reserveId, $quantity)
    {
        $Reserve = new Reserve();
        $ShipmentDetail = new ShipmentDetail();
        if (!empty($reserveId)) {
            $own_reserve = $Reserve->getById($reserveId);
            $untreatedShipment = $ShipmentDetail->getUntreatedQuantityByReserveId($reserveId);

            $reserveQuantityValidity = $own_reserve['reserve_quantity_validity'];
            // 有効引当数
            $calcReserveQuantity = $own_reserve['reserve_quantity'] - $quantity;
            // $calcReserveQuantity = $untreatedShipment['sum_shipment_quantity'] + $own_reserve['reserve_quantity'] - $quantity;
            // エラーチェック
            // 未出荷の出荷指示があるか
            if (Common::nullorBlankToZero($untreatedShipment['sum_shipment_quantity']) > 0) {
                throw new \Exception(config('message.error.allocation.not_shipment_exist'));
            }
            // 有効引当数が0未満になるか
            if (($reserveQuantityValidity + $quantity) < 0) {
                throw new \Exception(config('message.error.allocation.reserve_quantity_validity'));
            }
        }
    }
}