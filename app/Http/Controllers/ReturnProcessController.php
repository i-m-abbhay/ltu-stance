<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Illuminate\Http\Request;
// use App\Models\Return;
use Session;
use Auth;
use DB;
use App\Libs\Common;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Department;
use App\Models\General;
use App\Models\Matter;
use App\Models\Product;
use App\Models\ProductstockShelf;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\ReturnMaker;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Supplier;
use App\Models\Returns;
use App\Models\Discard;
use App\Models\NumberManage;
use App\Models\ProductMove;
use App\Models\PurchaseLineItem;
use App\Models\Reserve;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\ShelfNumber;
use App\Models\ShipmentDetail;
use App\Models\Warehouse;
use App\Models\WarehouseMove;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Mockery\Undefined;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ReturnProcessController extends Controller
{

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 
     * 初期表示
     * @return type
     */
    public function index()
    {
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.fullApproval'));

        // オートコンプリート取得
        $customerList = (new Customer())->getComboList();
        $matterList = (new Matter())->getComboList();
        // $productList = (new Product())->getComboList();
        $supplierList = (new Supplier())->getComboList();
        $qrCodeList = (new Qr())->getComboList();
        $departmentList = (new Department())->getComboList();
        $staffList = (new Staff())->getComboList();
        $staffDepartList = (new StaffDepartment())->getCurrentTermList();

        $warehouseList = (new Warehouse())->getExistReturnShelfWarehouse();

        $kbn = config('const.general.rtnprocess');
        $processList = (new General())->getCategoryList($kbn);

        // 部門長取得
        $chelfStaffList = (new Staff())->getChiefStaffList();

        $isAuthProc = config('const.flg.off');
        if ($chelfStaffList->where('id', '=', Auth::user()->id)->count() > 0) {
            // 返品処置追加権限あり
            $isAuthProc = config('const.flg.on');
        }

        return view('Return.return-process')
                ->with('isEditable', $isEditable)
                ->with('customerList', $customerList)
                ->with('matterList', $matterList)
                // ->with('productList', $productList)
                ->with('qrCodeList', $qrCodeList)
                ->with('supplierList', $supplierList)
                ->with('departmentList', $departmentList)
                ->with('staffList', $staffList)
                ->with('staffDepartList', $staffDepartList)
                ->with('warehouseList', $warehouseList)
                ->with('processList', $processList)
                ->with('isAuthProc', $isAuthProc)
                ;
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();
            
            $list = [];
            $returnList = [];
            
            // 一覧データ取得
            $WarehouseMove = new WarehouseMove();
            $list = $WarehouseMove->getReturnList($params);
            $Retrun = new Returns();
            $ProductstockShelf = new ProductstockShelf();
            $returnList = $Retrun->getReturnList();

            // class,text付与
            foreach ($list as $key => $value) {
                $list[$key]['approval_auth'] = config('const.flg.off');
                $list[$key]['maker_return_flg'] = config('const.flg.off');

                $appStatus = $list[$key]['approval_status'];
                $list[$key]['approval_status'] = array(
                    'id' => $list[$key]['id'],
                    'text' => config('const.returnApprovalStatus.'. $appStatus. '.text'),
                    'class' => config('const.returnApprovalStatus.'. $appStatus. '.class'),
                    'val' => config('const.returnApprovalStatus.val.'. $appStatus),
                );

                // 承認権限
                if ($list[$key]['matter_staff_id'] == Auth::user()->id) {
                    $list[$key]['approval_auth'] = config('const.flg.on');
                }
                if ($list[$key]['matter_staff_chief_id'] == Auth::user()->id) {
                    $list[$key]['approval_auth'] = config('const.flg.on');
                }
                // 全承認権限者
                if (Authority::hasAuthority(Auth::user()->id, config('const.auth.fullApproval'))) {
                    $list[$key]['approval_auth'] = config('const.flg.on');
                }

                // メーカー返品のスマホ処理不要が可能か
                $moveId = $list[$key]['id'];
                $isExist = $Retrun->isExistCanceledMakerReturnByMoveId($moveId);
                if ($isExist) {
                    $list[$key]['maker_return_flg'] = config('const.flg.on');
                }

                // 自社在庫に同商品があるか
                $shelfList = $ProductstockShelf->getStockShelfList($list[$key]['product_id'], $list[$key]['to_warehouse_id'], config('const.stockFlg.val.stock'));
                foreach ($shelfList as $sKey => $sRow) {
                    $shelfList[$sKey]['shelf_name'] = $shelfList[$sKey]['shelf_area'] . '　'. $shelfList[$sKey]['shelf_steps']. '段目'; 
                }
                $list[$key]['shelf_list'] = collect($shelfList)->toArray();

                // 自社在庫に預かり品の同商品があるか
                $keepShelfList = $ProductstockShelf->getStockShelfList($list[$key]['product_id'], $list[$key]['to_warehouse_id'], config('const.stockFlg.val.keep'), $list[$key]['customer_id']);
                foreach ($keepShelfList as $sKey => $sRow) {
                    $keepShelfList[$sKey]['shelf_name'] = $keepShelfList[$sKey]['shelf_area'] . '　'. $keepShelfList[$sKey]['shelf_steps']. '段目'; 
                }
                $list[$key]['keep_shelf_list'] = collect($keepShelfList)->toArray();
            }

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json(['moveList' => $list, 'returnList' => $returnList]);
    }    

    /**
     * 承認
     *
     * @return void
     */
    public function approval(Request $request)
    {
        $resultSts = false;
        $Return = new Returns();
        $Reserve = new Reserve();
        $Qr = new Qr();
        $QrDetail = new QrDetail();

        $params = $request->request->all();
        $WarehouseMove = new WarehouseMove();
        $saveResult = false;

        // $moveId = $params['id'];
        if (!empty($params['id'])) {
            DB::beginTransaction();
            try {
                // 承認
                $saveResult = $WarehouseMove->applyApprovalProcess($params, config('const.returnApprovalStatus.val.approvaled'));

                $moveData = $WarehouseMove->getById($params['id']);
                if ($moveData !== null && Common::nullorBlankToZero($params['delivery_id']) == 0 && $moveData['stock_flg'] == config('const.stockFlg.val.order')) {
                    // 引当取得
                    // $reserveData = $Reserve->getByWarehouseAndOrderDetailId($moveData['from_warehouse_id'], $moveData['order_detail_id']);

                    // if ($reserveData !== null) {
                    //     // 引当レコードから有効引当数減算
                    //     $updateData = [];
                    //     $updateData['id'] = $reserveData['id'];
                    //     $updateData['reserve_quantity_validity'] = Common::nullorBlankToZero($reserveData['reserve_quantity_validity']) - Common::nullorBlankToZero($params['quantity']);
                    //     if ($updateData['reserve_quantity_validity'] <= 0) {
                    //         $updateData['finish_flg'] = config('const.flg.on');
                    //     } else {
                    //         $updateData['finish_flg'] = config('const.flg.off');
                    //     }
                    //     $Reserve->updateById($updateData);
                    // }

                    // 引当からマイナスする
                    $Reserve->minusReserveQuantityByWarehouseAndOrder($moveData['from_warehouse_id'], $moveData['order_detail_id'], (int)Common::nullorBlankToZero($params['quantity']));

                }


                if ($saveResult) {
                    DB::commit();
                    $resultSts = true;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $resultSts = false;
                Log::error($e);
                Session::flash('flash_error', config('message.error.error'));
            }
        }
        return \Response::json($resultSts);
    }

    /**
     * 却下
     *
     * @return void
     */
    public function rejection(Request $request)
    {
        $resultSts = false;
        $Return = new Returns();

        $params = $request->request->all();
        $WarehouseMove = new WarehouseMove();
        $saveResult = false;

        // $moveId = $params['id'];
        if (!empty($params['id'])) {
            DB::beginTransaction();
            try {
                // 承認
                $saveResult = $WarehouseMove->applyApprovalProcess($params, config('const.returnApprovalStatus.val.rejection'));

                if ($saveResult) {
                    DB::commit();
                    $resultSts = true;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $resultSts = false;
                Log::error($e);
                Session::flash('flash_error', config('message.error.error'));
            }
        }
        return \Response::json($resultSts);
    }

    /**
     * 返品処理
     *
     * @return void
     */
    public function process(Request $request)
    {
        $Return = new Returns();
        $Productstock = new ProductstockShelf();
        $Discard = new Discard();
        $ReturnMaker = new ReturnMaker();
        $ProductMove = new ProductMove();
        $SystemUtil = new SystemUtil();
        $ShelfNumber = new ShelfNumber();
        $WarehouseMove = new WarehouseMove();
        $Qr = new Qr();
        $QrDetail = new QrDetail();

        $resultSts = false;
        $resultQrCode = "";
        
        $saveResult = false;
        $returnShelfId = 0;
        $params = $request->request->all();
        $shelf = $ShelfNumber->getByWarehouseIdAndShelfKind($params['to_warehouse_id'], config('const.shelfKind.return'));
        if (count($shelf) > 0) {
            $returnShelfId = $shelf->pluck('id')[0];
        }
        
        $rKbn = $params['return_kbn'];
        $params['quantity'] = $params['return_quantity'];

        $rtnData = [];
        $rtnData['warehouse_move_id']    = $params['warehouse_move_id'];
        $rtnData['warehouse_id']         = $params['to_warehouse_id'];
        $rtnData['to_warehouse_id']      = $params['to_warehouse_id'];
        $rtnData['to_shelf_number_id']   = $returnShelfId;
        $rtnData['from_warehouse_id']    = 0;
        $rtnData['from_shelf_number_id'] = 0;
        $rtnData['qr_code']              = $params['qr_code'];
        $rtnData['stock_flg']            = $params['stock_flg'];
        $rtnData['sp_flg']               = $params['sp_flg'];
        $rtnData['product_id']           = $params['product_id'];
        $rtnData['product_code']         = $params['product_code'];
        $rtnData['matter_id']            = $params['matter_id'];
        $rtnData['customer_id']          = $params['customer_id'];
        $rtnData['return_quantity']      = $params['return_quantity'];
        $rtnData['quantity']             = $params['return_quantity'];
        $rtnData['return_kbn']           = $params['return_kbn'];
        $rtnData['order_id']             = 0;
        $rtnData['order_no']             = 0;
        $rtnData['order_detail_id']      = 0;
        $rtnData['reserve_id']           = 0;
        $rtnData['shipment_id']          = 0;
        $rtnData['shipment_detail_id']   = 0;
        $rtnData['arrival_id']           = 0;
        $rtnData['sales_id']             = 0;
        $rtnData['product_move_id']      = 0;
        $rtnData['maker_return_date']    = null;

        $this->isValid($request);
        $saveCnt = 0;
        DB::beginTransaction();
        try {
            switch($rKbn) {
                case config('const.returnKbn.keep'):
                    // 預かり品 
                    // $keepShelf = $ShelfNumber->getByWarehouseIdAndShelfKind($params['to_warehouse_id'], config('const.shelfKind.keep'));
                    // if (count($shelf) > 0) {
                    //     $keepShelfId = $keepShelf->pluck('id')[0];
                    // } else if (count($shelf) == 0){
                    //     $keepShelfId = $returnShelfId;
                    // }
                    // $rtnData['shelf_kind'] = config('const.shelfKind.normal');

                    // $stockShelf = $ShelfNumber->getByWarehouseIdAndShelfKind($params['to_warehouse_id'], config('const.shelfKind.normal'));
                    
                    // 預かり在庫があれば加算、なければ返品棚に追加
                    $rtnData['shelf_kind'] = config('const.shelfKind.keep');
                    $rtnData['matter_id'] = 0;
                    $rtnData['stock_flg'] = config('const.stockFlg.keep');
                    $list = $Productstock->getListMoveInventory($rtnData);
                    if (count($list) > 0) {
                        $keepShelfId = $list[0]['shelf_number_id'];
                    } else if (count($list) == 0) {
                        $keepShelfData = $ShelfNumber->getList($rtnData)->first();
                        if (!empty($keepShelfData)) {
                            $keepShelfId = $keepShelfData['id'];
                        } else {
                            $keepShelfId = $returnShelfId;
                        }
                    }

                    // QR分割
                    $rtnData['stock_flg'] = $params['stock_flg'];
                    $rtnData['to_shelf_number_id'] = $params['keep_shelf_number_id'];
                    $qrCode = $this->qrSplit($rtnData);
                    $rtnData['qr_code'] = $qrCode;

                    $rtnData['matter_id'] = $params['matter_id'];
                    $rtnData['move_kind'] = config('const.productMoveKind.return');
                    $rtnData['move_flg'] = config('const.flg.off');
                    // $rtnData['shelf_kind'] = config('const.shelfKind.keep');
                    $rtnData['return_finish'] = config('const.flg.on');
                    $rtnData['shelf_number_id'] = 0;
                    // 返品レコード作成
                    $rtnId = $Return->add($rtnData);

                    // 在庫化
                    // 返品棚から減らす
                    $rtnData['is_return_shelf_from'] = true;
                    $rtnData['shelf_kind'] = config('const.shelfKind.return');
                    $rtnData['from_warehouse_id']    = $params['to_warehouse_id'];
                    $rtnData['from_shelf_number_id'] = $returnShelfId;
                    $rtnData['to_warehouse_id']      = 0;
                    $rtnData['to_shelf_number_id']   = 0;
                    $SystemUtil->MoveInventory($rtnData);

                    // 移動先へ
                    $rtnData['matter_id']            = 0;
                    $rtnData['from_warehouse_id']    = 0;
                    $rtnData['from_shelf_number_id'] = 0;
                    $rtnData['to_warehouse_id']      = $params['to_warehouse_id'];
                    $rtnData['to_shelf_number_id']   = $params['keep_shelf_number_id'];
                    $rtnData['shelf_kind'] = config('const.shelfKind.normal');
                    $rtnData['stock_flg'] = config('const.stockFlg.val.keep');
                    $SystemUtil->MoveInventory($rtnData);

                    $saveResult = true;

                    break;
                case config('const.returnKbn.stock'):
                    // 自社在庫化            
                    $rtnData['shelf_kind'] = config('const.shelfKind.normal');

                    // $stockShelf = $ShelfNumber->getByWarehouseIdAndShelfKind($params['to_warehouse_id'], config('const.shelfKind.normal'));

                    // 棚番在庫があれば加算、なければ返品棚に追加                    
                    $rtnData['matter_id'] = 0;
                    $rtnData['customer_id'] = 0;
                    $rtnData['stock_flg'] = config('const.stockFlg.stock');
                    // $list = $Productstock->getListMoveInventory($rtnData);
                    // if (count($list) > 0) {
                    //     $stockShelfId = $list[0]['shelf_number_id'];
                    // } else if (count($list) == 0) {
                    //     $normalShelfData = $ShelfNumber->getList($rtnData)->first();
                    //     if (!empty($normalShelfData)) {
                    //         $stockShelfId = $normalShelfData['id'];
                    //     } else {
                    //         $stockShelfId = $returnShelfId;
                    //     }
                    // }
                    // QR分割
                    $rtnData['stock_flg'] = $params['stock_flg'];
                    $qrCode = $this->qrSplit($rtnData);
                    $rtnData['qr_code'] = $qrCode;

                    $rtnData['matter_id'] = $params['matter_id'];
                    $rtnData['customer_id'] = $params['customer_id'];
                    $rtnData['move_kind'] = config('const.productMoveKind.return');
                    $rtnData['move_flg'] = config('const.flg.off');
                    // $rtnData['shelf_kind'] = config('const.shelfKind.normal');
                    $rtnData['return_finish'] = config('const.flg.on');
                    // $rtnData['shelf_number_id'] = 0;
                    // 返品レコード作成
                    $rtnId = $Return->add($rtnData);

                    // 在庫化
                    if ($rtnData['stock_flg'] == config('const.stockFlg.val.stock')) {
                        $rtnData['matter_id'] = 0;
                        $rtnData['customer_id'] = 0;
                    }
                    $rtnData['is_return_shelf_from'] = true;
                    $rtnData['from_warehouse_id']    = $params['to_warehouse_id'];
                    $rtnData['from_shelf_number_id'] = $returnShelfId;
                    $rtnData['to_warehouse_id']      = 0;
                    $rtnData['to_shelf_number_id']   = 0;
                    $rtnData['shelf_kind'] = config('const.shelfKind.return');
                    $SystemUtil->MoveInventory($rtnData);

                    
                    $rtnData['matter_id'] = 0;
                    $rtnData['customer_id'] = 0;
                    $rtnData['from_warehouse_id']    = 0;
                    $rtnData['from_shelf_number_id'] = 0;
                    $rtnData['to_warehouse_id']      = $params['to_warehouse_id'];                    
                    $rtnData['to_shelf_number_id'] = $params['shelf_number_id'];
                    // $rtnData['to_shelf_number_id']   = $stockShelfId;
                    $rtnData['shelf_kind'] = config('const.shelfKind.normal');
                    $rtnData['stock_flg'] = config('const.stockFlg.val.stock');
                    $SystemUtil->MoveInventory($rtnData);

                    $saveResult = true;

                    break;
                case config('const.returnKbn.maker'):
                    // メーカー返品
                    $rtnData['maker_return_date'] = $params['move_date'];

                    if ($params['sp_flg'] == config('const.flg.off')) {
                        // スマホ処理有
                        // QR分割
                        $qrCode = $this->qrSplit($rtnData);
                        $rtnData['qr_code'] = $qrCode;
                        $rtnData['return_finish'] = config('const.flg.off');
                        // 返品レコード作成
                        $rtnId = $Return->add($rtnData);
                    } else {
                        //　スマホ処理無
                        $qrData = $Qr->getByQrCode($params['qr_code']);
                        $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                        $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);

                        $data = null;
                        // QRから数量を減算
                        if (count($detailData) == 1) {
                            $data['id'] = $detailData[0]['id'];
                            $data['quantity'] = (int)$detailData[0]['quantity'] - (int)$params['return_quantity'];

                            if ($data['quantity'] > 0) {
                                $qrDetailUpdateResult = $QrDetail->updateById($data);
                            } else {
                                $qrDetailUpdateResult = $QrDetail->deleteByDetailId($data['id']);
                                if (count($qrDetailData) == 1) {
                                    $qrDeleteResult = $Qr->deleteByQrId($qrData['qr_id']);
                                }
                            }
                        }

                        $rtnData['return_finish'] = config('const.flg.on');  
                        // 返品レコード作成
                        $rtnId = $Return->add($rtnData);
                        
                        $rtnData['is_return_shelf_from']    = true;
                        $rtnData['from_warehouse_id']       = $params['to_warehouse_id'];
                        $rtnData['from_shelf_number_id']    = $returnShelfId;
                        $rtnData['to_warehouse_id']         = 0;
                        $rtnData['to_shelf_number_id']      = 0;
                        $rtnData['move_kind']               = config('const.productMoveKind.return');
                        $rtnData['move_flg']                = config('const.flg.off');
                        $rtnData['supplier_id']             = $params['supplier_id'];
                        // $rtnData['move_date']               = Carbon::now();

                        $moveId = $SystemUtil->MoveInventory($rtnData);

                        $rtnData['return_id']               = $rtnId;
                        $rtnData['product_move_id']         = $moveId;
                        $rtnData['cancel_kbn']              = 0;
                        $rtnData['cancel_product_move_id']  = $moveId;

                        // メーカー返品
                        $saveResult = $ReturnMaker->add($rtnData);
                    }
                    $saveResult = true;
                    break;
                case config('const.returnKbn.discard'):
                    // 廃棄
                    $rtnData['discard_kind'] = config('const.discardKind.return');
                    $rtnData['discard_quantity'] = $params['return_quantity'];

                    if ($params['sp_flg'] == config('const.flg.off')) {
                        // スマホ処理有
                        // QR分割
                        $qrCode = $this->qrSplit($rtnData);
                        $rtnData['qr_code'] = $qrCode;
                        $rtnData['return_finish'] = config('const.flg.off'); 
                        $rtnId = $Return->add($rtnData);

                        $rtnData['discard__move_id'] = null;
                        $rtnData['shelf_number_id'] = $returnShelfId;
                        $rtnData['approval_status'] = config('const.returnApprovalStatus.val.approvaled');
                        $rtnData['approval_user']   = null;
                        $rtnData['approval_at']     = null;
                        $rtnData['discard_finish']  = config('const.flg.off');

                        // 廃棄予定作成
                        $saveResult = $Discard->add($rtnData);
                    } else {
                        //　スマホ処理無
                        $qrData = $Qr->getByQrCode($params['qr_code']);
                        $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                        $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);

                        $data = null;
                        // QRから数量を減算
                        if (count($detailData) == 1) {
                            $data['id'] = $detailData[0]['id'];
                            $data['quantity'] = (int)$detailData[0]['quantity'] - (int)$params['return_quantity'];

                            if ($data['quantity'] > 0) {
                                $qrDetailUpdateResult = $QrDetail->updateById($data);
                            } else {
                                $qrDetailUpdateResult = $QrDetail->deleteByDetailId($data['id']);
                                if (count($qrDetailData) == 1) {
                                    $qrDeleteResult = $Qr->deleteByQrId($qrData['qr_id']);
                                }
                            }
                        }
                        
                        $rtnData['return_finish'] = config('const.flg.on'); 
                        $rtnId = $Return->add($rtnData);

                        $rtnData['is_return_shelf_from']    = true;
                        $rtnData['from_warehouse_id']       = $params['to_warehouse_id'];
                        $rtnData['from_shelf_number_id']    = $returnShelfId;
                        $rtnData['to_warehouse_id']         = 0;
                        $rtnData['to_shelf_number_id']      = 0;
                        $rtnData['move_kind']               = config('const.productMoveKind.discard');
                        $rtnData['move_flg']                = config('const.flg.off');
                        $rtnData['move_date']               = Carbon::now();
                        $rtnData['order_id']                = 0;
                        $rtnData['order_no']                = 0;
                        $rtnData['order_detail_id']         = 0;
                        $rtnData['reserve_id']              = 0;
                        $rtnData['shipment_id']             = 0;
                        $rtnData['shipment_detail_id']      = 0;
                        $rtnData['arrival_id']              = 0;
                        $rtnData['sales_id']                = 0;

                        $moveId = $SystemUtil->MoveInventory($rtnData);

                        $rtnData['discard__move_id']  = $moveId;
                        $rtnData['shelf_number_id']   = $returnShelfId;
                        $rtnData['approval_status']   = config('const.returnApprovalStatus.val.approvaled');
                        $rtnData['approval_user']     = Auth::user()->id;
                        $rtnData['approval_at']       = Carbon::now();
                        $rtnData['discard_finish']    = config('const.flg.on');

                        // 廃棄レコード作成
                        $saveResult = $Discard->add($rtnData);
                    }
                    $saveResult = true;
                    break;
            }
            if ($saveResult) {
                DB::commit();
                $resultSts = true;

                // 自社在庫化以外ならQRコードを返す
                if ($rKbn != config('const.returnKbn.stock')) {
                    $resultQrCode = $rtnData['qr_code'];
                }
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json(['status' => $resultSts, 'qr_code' => $resultQrCode]);
    }

    /**
     * 取り消し
     *
     * @return void
     */
    public function cancel(Request $request)
    {
        $resultSts = ['status' => false, 'message' => ''];

        $Return = new Returns();
        $ReturnMaker = new ReturnMaker();
        $ProductstockShelf = new ProductstockShelf();
        $WarehouseMove = new WarehouseMove();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        $NumberManage = new NumberManage();
        $Discard = new Discard();
        $Sales = new Sales();
        $SalesDetail = new SalesDetail();
        $PurchaseLineItem = new PurchaseLineItem();

        $saveResult = false;


        $params = $request->request->all();

        DB::beginTransaction();
        try {
            // メーカー返品
            // 売上、仕入のチェック処理
            if ($params['return_kbn'] == config('const.returnKbn.maker')) {
                $message = '選択された商品は、既に売上もしくは支払対象として確定しています。\n解除の上、再度実行してください。\n確定している処理:\n';

                // 売上チェック
                $hasSalesData = $Sales->hasReturnSalesByReturnId($params['id']);
                if ($hasSalesData) {
                    $message .= '・売上\n';
                }
                // 仕入チェック
                $hasPurchaseData = $PurchaseLineItem->hasReturnPurchaseByReturnId($params['id']);
                if ($hasPurchaseData) {
                    $message .= '・仕入';
                }

                if (!$hasSalesData && !$hasPurchaseData) {
                    // 売上明細削除
                    $SalesDetail->deleteByReturnId($params['id']);
                    // 仕入明細削除
                    $PurchaseLineItem->deleteByReturnId($params['id']);
                } else {
                    $resultSts['message'] = $message;
                    throw new \Exception();
                }
            }

            // 返品レコード削除
            $saveResult = $Return->deleteById($params['id']);

            // メーカー返品だった場合、在庫を戻す
            if ($params['return_kbn'] == config('const.returnKbn.maker') && $params['return_finish'] == config('const.flg.on')) {
                // 実施後
                $ReturnMaker->cancelByReturnId($params['id']);

                $params['shelf_kind'] = null;
                if (isset($params['shelf_kind'])) {
                    $params['shelf_kind']  = $params['shelf_kind'];
                }
                // $params['to_shelf_number_id'] = 0;
                $params['warehouse_id'] = $params['to_warehouse_id'];

                $params['shelf_kind'] = config('const.shelfKind.return');

                $params['id'] = null;
                if ($params['shelf_kind'] != null) {
                    $list = $ProductstockShelf->getListMoveInventory($params);
                } else {
                    $list = $ProductstockShelf->getList($params);
                }

                if (count($list) > 0) {
                    // 更新
                    $p['id'] = $list->pluck('id')[0];
                    $p['stock_flg'] = $params['stock_flg'];
                    $p['product_id'] = $params['product_id'];
                    $p['product_code'] = $params['product_code'];
                    $p['warehouse_id'] = $params['warehouse_id'];
                    $p['shelf_number_id'] = $params['shelf_number_id'];
                    $p['quantity'] = $list->pluck('quantity')[0] + $params['quantity'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                    $saveResult = $ProductstockShelf->updateById($p);
                } else {
                    // 登録
                    $p['stock_flg'] = $params['stock_flg'];
                    $p['product_id'] = $params['product_id'];
                    $p['product_code'] = $params['product_code'];
                    $p['warehouse_id'] = $params['warehouse_id'];
                    $p['shelf_number_id'] = $params['shelf_number_id'];
                    $p['quantity'] = $params['quantity'];
                    $p['matter_id'] = $params['matter_id'];
                    $p['customer_id'] = $params['customer_id'];
                    $saveResult = $ProductstockShelf->add($p);
                }

                if ($params['stock_flg'] == config('const.stockFlg.stock')) {
                    // 在庫品の場合、新規QRは発行しない
                    $QrDetailSaveResult = true;
                } else {
                    // 元QRと統合
                    $qrData = $Qr->getByQrCode($params['parent_qr_code']);
                    if (!empty($qrData)) {
                        // 更新
                        $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                        $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);
                        $data = null;

                        if (count($detailData) == 1) {
                            $data['id'] = $detailData[0]['id'];
                            $data['quantity'] = (int)$detailData[0]['quantity'] + (int)$params['quantity'];

                            if ($data['quantity'] > 0) {
                                $qrDetailUpdateResult = $QrDetail->updateById($data);
                            }
                        }
                    } else {
                        // 新規QR作成
                        // $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                        
                        // 倉庫移動.QRコードで再発行する
                        $newQrCode = $params['parent_qr_code'];
                        $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => 1);
                        //QR追加
                        $newQrId = $Qr->add($newQrInfo);
                        //QRDetail追加
                        $newQrInfo = $Qr->getById(array('qr_code' => $newQrCode));

                        $newQrDetailInfo = array(
                            'qr_id' => $newQrId,
                            'product_id' => $params['product_id'],
                            'matter_id' => $params['matter_id'],
                            'customer_id' => $params['customer_id'],
                            'arrival_id' => 0,
                            'warehouse_id' => $params['warehouse_id'],
                            'shelf_number_id' => $params['shelf_number_id'],
                            'quantity' => $params['quantity']
                        );
                        $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                    }
                }
            } else if ($params['return_kbn'] == config('const.returnKbn.maker') && $params['return_finish'] == config('const.flg.off')) {
                // 実施前
                // QR削除
                $qrData = $Qr->getByQrCode($params['qr_code']);
                $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);

                $data = null;
                // QRから数量を減算
                if (count($detailData) == 1) {
                    $data['id'] = $detailData[0]['id'];

                    $qrDetailUpdateResult = $QrDetail->deleteByDetailId($data['id']);
                    if (count($qrDetailData) == 1) {
                        $qrDeleteResult = $Qr->deleteByQrId($qrData['qr_id']);
                    } else {
                        $qrDeleteResult = true;
                    }
                }

                if ($qrDetailUpdateResult && $qrDeleteResult) {
                    $saveResult = true;
                }

                // 元QRと統合
                $qrData = $Qr->getByQrCode($params['parent_qr_code']);
                if (!empty($qrData)) {
                    // 更新
                    $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                    $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);
                    $data = null;

                    if (count($detailData) == 1) {
                        $data['id'] = $detailData[0]['id'];
                        $data['quantity'] = (int)$detailData[0]['quantity'] + (int)$params['quantity'];

                        if ($data['quantity'] > 0) {
                            $qrDetailUpdateResult = $QrDetail->updateById($data);
                        }
                    }
                } else {
                    // 倉庫移動.QRコードで再発行する
                    $newQrCode = $params['parent_qr_code'];
                    $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => 1);
                    //QR追加
                    $newQrId = $Qr->add($newQrInfo);
                    //QRDetail追加
                    $newQrInfo = $Qr->getById(array('qr_code' => $newQrCode));

                    $newQrDetailInfo = array(
                        'qr_id' => $newQrId,
                        'product_id' => $params['product_id'],
                        'matter_id' => $params['matter_id'],
                        'customer_id' => $params['customer_id'],
                        'arrival_id' => 0,
                        'warehouse_id' => $params['to_warehouse_id'],
                        'shelf_number_id' => $params['shelf_number_id'],
                        'quantity' => $params['quantity']
                    );
                    $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                }
            }

            // 廃棄の場合
            if ($params['return_kbn'] == config('const.returnKbn.discard') && $params['return_finish'] == config('const.flg.off')) {
                // 廃棄レコード削除
                $delResult = $Discard->deleteByQrCode($params['qr_code']);

                // QR削除
                $qrData = $Qr->getByQrCode($params['qr_code']);
                $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);

                $data = null;
                // QRから数量を減算
                if (count($detailData) == 1) {
                    $data['id'] = $detailData[0]['id'];
                    
                    $qrDetailUpdateResult = $QrDetail->deleteByDetailId($data['id']);
                    if (count($qrDetailData) == 1) {
                        $qrDeleteResult = $Qr->deleteByQrId($qrData['qr_id']);
                    } else {
                        $qrDeleteResult = true;
                    }
                }

                if ($qrDetailUpdateResult && $qrDeleteResult) {
                    $saveResult = true;
                }

                // 元QRと統合
                $qrData = $Qr->getByQrCode($params['parent_qr_code']);
                if (!empty($qrData)) {
                    // 更新
                    $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                    $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);
                    $data = null;

                    if (count($detailData) == 1) {
                        $data['id'] = $detailData[0]['id'];
                        $data['quantity'] = (int)$detailData[0]['quantity'] + (int)$params['quantity'];

                        if ($data['quantity'] > 0) {
                            $qrDetailUpdateResult = $QrDetail->updateById($data);
                        }
                    }
                } else {
                    // 倉庫移動.QRコードで再発行する
                    $newQrCode = $params['parent_qr_code'];
                    $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => 1);
                    //QR追加
                    $newQrId = $Qr->add($newQrInfo);
                    //QRDetail追加
                    $newQrInfo = $Qr->getById(array('qr_code' => $newQrCode));

                    $newQrDetailInfo = array(
                        'qr_id' => $newQrId,
                        'product_id' => $params['product_id'],
                        'matter_id' => $params['matter_id'],
                        'customer_id' => $params['customer_id'],
                        'arrival_id' => 0,
                        'warehouse_id' => $params['to_warehouse_id'],
                        'shelf_number_id' => $params['shelf_number_id'],
                        'quantity' => $params['quantity']
                    );
                    $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                }
            }

            if ($saveResult) {
                DB::commit();
                $resultSts['status'] = true;
                Session::flash('flash_success', config('message.success.cancel'));
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return \Response::json($resultSts);
    }

    /**
     * QR分割、新規発行
     *
     * @param  $qrCode
     * @return 成功：QRCODE 失敗：null
     */
    private function qrSplit($params)
    {
        $rtnResult = null;
        $Return = new Returns();
        $Qr = new Qr();
        $QrDetail = new QrDetail();

        $WarehouseMove = new WarehouseMove();
        $saveResult = false;
        $qrDetailUpdateResult = false;
        $QrDetailSaveResult = false;
        if (!empty($params)) {
            try {
                $qrData = $Qr->getByQrCode($params['qr_code']);
                $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);

                $data = null;
                // QRから数量を減算
                if (count($detailData) == 1) {
                    $data['id'] = $detailData[0]['id'];
                    $data['quantity'] = (int)$detailData[0]['quantity'] - (int)$params['quantity'];

                    if ($data['quantity'] > 0) {
                        $qrDetailUpdateResult = $QrDetail->updateById($data);
                    } else {
                        $qrDetailUpdateResult = $QrDetail->deleteByDetailId($data['id']);
                        if (count($qrDetailData) == 1) {
                            $qrDeleteResult = $Qr->deleteByQrId($qrData['qr_id']);
                        }
                    }
                } else {
                    $qrDetailUpdateResult = true;
                }

                $newQrCode = '';
                if ($params['return_kbn'] == config('const.returnKbn.stock')) {
                    // 在庫品の場合、新規QRは発行しない
                    $QrDetailSaveResult = true;
                } else {
                    // 新規QR作成
                    //QR発番
                    $NumberManage = new NumberManage();
                    $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                    $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => 1);
                    //QR追加
                    $newQrId = $Qr->add($newQrInfo);
                    //QRDetail追加
                    $newQrInfo = $Qr->getById(array('qr_code' => $newQrCode));

                    $newQrDetailInfo = array(
                        'qr_id' => $newQrId,
                        'product_id' => $params['product_id'],
                        'matter_id' => $params['matter_id'],
                        'customer_id' => $params['customer_id'],
                        'arrival_id' => $params['arrival_id'],
                        'warehouse_id' => $params['to_warehouse_id'],
                        'shelf_number_id' => $params['to_shelf_number_id'],
                        'quantity' => $params['quantity']
                    );
                    $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                }

                if ($qrDetailUpdateResult && $QrDetailSaveResult) {
                    $rtnResult = $newQrCode;
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $rtnResult = null;
                Log::error($e);
                Session::flash('flash_error', config('message.error.error'));
            }
        }
        return $rtnResult;
    }

    /**
     * QR印刷
     * @param Request $request
     * @return type
     */
    public function print(Request $request)
    {
        // リクエスト取得
        $params = $request->request->all();

        try {
            $Qr = new Qr();
            $printNum = $Qr->getPrintNumber($params);
            $SystemUtil = new SystemUtil();
            $result = $SystemUtil->qrPrintManager($params, $printNum['print_number']);

        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($result);
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
            'return_kbn' => 'required',
            'return_quantity' => 'required',
        ]);
    }  
    
}