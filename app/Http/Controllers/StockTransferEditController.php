<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Base;
use App\Models\Choice;
use App\Models\Construction;
use App\Models\Department;
use App\Models\General;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DB;
use Session;
use Storage;
use Carbon\Carbon;
use App\Libs\Common;
use App\Models\Item;
use App\Models\Matter;
use App\Models\Product;
use App\Models\ProductstockShelf;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\Reserve;
use App\Models\ShipmentDetail;
use App\Models\SpecDefault;
use App\Models\SpecItemHeader;
use App\Models\SpecItemDetail;
use App\Models\Warehouse;
use App\Models\WarehouseMove;
use App\Models\StaffDepartment;
use Illuminate\Validation\Rule;

/**
 * 在庫移管編集
 */
class StockTransferEditController extends Controller
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
     * 初期表示
     * @return type
     */
    public function index(Request $request, $id = null)
    {
        // 閲覧権限チェック
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
        //     throw new NotFoundHttpException();
        // }
        
        // // 編集権限
        // $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // 一覧データ取得
        $WarehouseMove = new WarehouseMove();
        $moveData = null;
        $warehouseList = (new Warehouse)->getComboList();
        $matterList = (new Matter)->getComboList(config('const.flg.off'));

        // 移管元倉庫初期表示 (自部門倉庫)
        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $baseId = (new Department())->getComboList()->where('id', $departmentId)->pluck('base_id')->first();
        $attWarehouse = (new Warehouse)->getComboList()->where('owner_id', $baseId)->where('owner_kbn', config('const.ownerKbn.company'))->first();
        $initParams = $attWarehouse;

        // 全倉庫の在庫取得
        $Stock = new ProductstockShelf();
        $stockQuantity = $Stock->getAllQuantity();

        try {
            if (is_null($id)) {
                // 新規
                $moveData = $WarehouseMove;
            } else {
                // 編集
                $id = (int)$id;
                // データ取得
                $headerData = $WarehouseMove->getById($id);
                $moveData = $WarehouseMove->getMoveList($headerData);
                
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }        

        return view('Stock.stock-transfer-edit') 
                ->with('moveData', $moveData)
                ->with('initParams', $initParams)
                ->with('warehouseList', $warehouseList)
                ->with('matterList', $matterList)
                ->with('stockQuantity', $stockQuantity)
                ;
    }

    /**
     * 検索
     * @param Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        // 閲覧権限チェック
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
        //     throw new NotFoundHttpException();
        // }

        try {
            // リクエストから検索条件取得
            $params = $request->request->all();       
            // 一覧データ取得
            $stockList = (new ProductstockShelf())->getQrStockList($params);

            $trList = (new WarehouseMove())->getByMoveKind(config('const.moveKind.transfer'));
            
            // 指示済のQRを除外
            $trList = $trList->keyBy('qr_code')->pluck('qr_code')->toArray();
            $trArr = [];
            foreach($trList as $key => $qr) {
                if (!empty($qr)) {
                    $trArr[] = $qr;
                }
            }
            $Reserve = new Reserve();
            $WarehouseMove = new WarehouseMove();
            $moveList = $WarehouseMove->getMoveQuantity($params['from_warehouse'], config('const.moveKind.transfer'));
            foreach($stockList as $key => $value) {
                if ($stockList[$key]['stock_flg'] == config('const.stockFlg.val.stock')) {
                    $res = $Reserve->getReserveQuantityValidity($stockList[$key]['warehouse_id'], $stockList[$key]['product_id'], config('const.stockFlg.val.stock'));
                    // $res = $Reserve->getReserveQuantity($stockList[$key]['warehouse_id'], $stockList[$key]['product_id']);
                    foreach($moveList as $i => $v) {
                        if ($stockList[$key]['product_id'] == $moveList[$i]['product_id']) {
                            $stockList[$key]['active_quantity'] = (int)$stockList[$key]['active_quantity'] - (int)$moveList[$i]['quantity'];
                        }
                    }
                    $stockList[$key]['active_quantity'] = (int)$stockList[$key]['active_quantity'] - (int)$res['reserve_quantity'];
                }
            }
            $stockList = $stockList->filter(function ($value, $key) use($trArr) {
                foreach($trArr as $key => $qr) {
                    if ($value['qr_code'] == $qr || $value['active_quantity'] == 0) {
                        return false;
                    }
                }
                return true;
            });
            // filterで乱れた添字振り直し
            $stockList = array_values($stockList->toArray()); 
            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json(['stockList' => $stockList, 'transferList' => $trList]);
    }

    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result = ['status' => false, 'message' => ''];

        // 編集権限チェック
        // $hasEditAuth = false;
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.has')) {
            // $hasEditAuth = true;
            
            // リクエストデータ取得
            $params = $request->request->all();
            $items = json_decode($params['move_items'], true);

            // バリデーションチェック
            $this->isValid($request);
        // }

        $WarehouseMove = new WarehouseMove();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        $ProductstockShelf = new ProductstockShelf();
        $ShipmentDetail = new ShipmentDetail();
        $Reserve = new Reserve();

        DB::beginTransaction();        
        try {
            // if (!$hasEditAuth) {
            //     // 編集権限なし
            //     throw new \Exception();
            // }
            $saveResult = false;

            $qrList = [];
            $qrList = collect($items)->pluck('qr_code')->unique()->toArray();
            foreach($qrList as $key => $row) {
                if ($row !== '') {
                    // QRコードが存在する場合、まとめ商品が全て選択されているかチェック
                    // 商品名等で検索した時にまとめられている商品が選択されているとは限らない
                    $qrData = [];
                    $qrDetailData = [];
                    $qrData = $Qr->getByQrCode($items[$key]['qr_code']);
                    $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);

                    $qrGroupData = collect($items)->where('qr_code', $items[$key]['qr_code']);
                    if (count($qrGroupData) < count($qrDetailData)) {
                        // まとめられたQR商品と選択されている商品の数が違った場合
                        // 一覧データ取得
                        $params['from_warehouse'] = $params['from_warehouse_id'];
                        $stockList = $ProductstockShelf->getQrStockList($params);
                        $diffList = collect($stockList)->where('qr_code', $items[$key]['qr_code'])->whereNotIn('product_id', $items[$key]['product_id']);
                        
                        foreach($diffList as $dKey => $dRow) {
                            $dRow['from_warehouse_id'] = $params['from_warehouse_id'];
                            $newItem = $dRow;
                            array_push($items, $newItem);
                        }
                    }
                }
            }
            
            // 登録
            $isErr = false;
            $message = '';
            foreach ($items as $key => $val) {
                if ($items[$key]['quantity'] > 0) {
                    if ($items[$key]['stock_flg'] == config('const.stockFlg.val.order')) {
                        // 在庫数取得
                        $actStockQuantity = 0;
                        $stockData = $ProductstockShelf->getStockByProductIdAndWarehouseId($items[$key]['product_id'], $items[$key]['from_warehouse_id'], $items[$key]['stock_flg'], $items[$key]['matter_id'], $items[$key]['customer_id']);
                        if ($stockData !== null) {
                            $actStockQuantity = Common::nullorBlankToZero($stockData['quantity']);
                        }
                        
                        // 引当情報
                        $reserveData = $Reserve->getByWarehouseAndOrderDetailId($items[$key]['from_warehouse_id'], $items[$key]['order_detail_id']);

                        $untreatQuantity = 0;
                        if ($reserveData !== null) {
                            // 未出荷の出荷指示数
                            $untreatedShipment = $ShipmentDetail->getUntreatedQuantityByReserveId($reserveData['id']);
                            $untreatQuantity = Common::nullorBlankToZero($untreatedShipment['sum_shipment_quantity']);
                        }

                        // 移管予定数
                        $sumPlanData = $WarehouseMove->getNotFinishTransfer($items[$key]);
                        $sumPlanQuantity = 0;
                        if ($sumPlanData !== null) {
                            $sumPlanQuantity = Common::nullorBlankToZero($sumPlanData['sum_quantity']);
                        }

                        // 登録可能かチェック
                        // 倉庫在庫数 - 出荷指示(未出庫) - 移管予定数 < 移動させようとした数量
                        $calcQuantity = $actStockQuantity - $untreatQuantity - $sumPlanQuantity;
                        if ($calcQuantity < $items[$key]['quantity']) {
                            $isErr = true;
                            $message .= '・'. $items[$key]['product_name']. 'は出荷指示が作成済みです。\n';
                        }
                    }
                    
                    // $items[$key]['from_warehouse_id'] = $params['from_warehouse_id'];
                    $items[$key]['to_warehouse_id'] = $params['to_warehouse_id'];
                    $items[$key]['move_date'] = $params['move_date'];
                    $items[$key]['memo'] = $params['memo'];
                    $saveResult = $WarehouseMove->addWarehouseMove($items[$key]);
                }
            }

            if ($isErr) {
                $result['message'] = $message;
                throw new \Exception($message);
            }
                
            if ($saveResult) {
                DB::commit();
                $result['status'] = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
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
     * 削除
     * @param Request $request
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

            $SpecItemHeader = new SpecItemHeader();

            // 削除
            $deleteResult = $SpecItemHeader->deleteById($params['id']);

            if ($deleteResult) {
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
     * @param Request $request
     * @return type
     */
    private function isValid($request)
    {
        $this->validate($request, [
            'from_warehouse_id' => 'required',
            'to_warehouse_id' => 'required',
            'move_date' => 'required|date',
        ]);
    }

}