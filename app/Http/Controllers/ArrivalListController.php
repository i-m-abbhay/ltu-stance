<?php
namespace App\Http\Controllers;

use Auth;
use Carbon\Carbon;
use App\Libs\Common;
use DB;
use Session;
use App\Libs\SystemUtil;
use Illuminate\Http\Request;
use App\Models\Order;
use Illuminate\Support\Facades\Log;
use App\Models\Arrival;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\Matter;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Customer;
use App\Models\Loading;
use App\Models\OrderDetail;
use App\Models\ProductstockShelf;
use App\Models\PurchaseLineItem;
use App\Models\StaffDepartment;
use App\Models\Warehouse;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\ShelfNumber;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\IOFactory;
 
/**
 * 入荷一覧
 */
class ArrivalListController extends Controller
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
    public function index()
    {
        // データ取得
        $Customer = new Customer();
        $Matter = new Matter();
        $Department = new Department();
        $Staff = new Staff();
        $Order = new Order();
        $Supplier = new Supplier();
        // $Product = new Product();
        $Warehouse = new Warehouse();
        $StaffDepartment = new StaffDepartment();
        $Qr = new Qr();

        $customerList = $Customer->getComboList();
        $matterList = $Matter->getComboList(null, null, config('const.flg.off'));
        $departmentList = $Department->getComboList();
        $staffList = $Staff->getComboList();
        $orderList = $Order->getComboList();
        // 仕入先リスト取得
        $kbn[] = config('const.supplierMakerKbn.supplier');
        $supplierList = $Supplier->getBySupplierKbn($kbn);
        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);
        // $productList = $Product->getComboList();
        $warehouseList = $Warehouse->getAllWarehouseComboList();
        $QrcodeList = $Qr->getComboList();
        // 担当者部門
        $staffDepartmentData = $StaffDepartment->getCurrentTermList();
        $staffDepartmentData = $staffDepartmentData->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });

        $departmentId = (new StaffDepartment())->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $departmentName = $Department->getById($departmentId)['department_name'];
        $fromDate = Carbon::today()->subMonth(1)->format('Y/m/d');
        $toDate = Carbon::today()->format('Y/m/d');
        $initSearchParams = collect([
            'staff_id' => Auth::id(),
            'department_name' => $departmentName,
            'from_order_date' => $fromDate,
            'to_order_date' => $toDate,
            'from_arrival_date' => $fromDate,
            'to_arrival_date' => $toDate,
            'from_shipping_date' => $fromDate,
            'to_shipping_date' => $toDate,
        ]);
    

        return view('Arrival.arrival-list')
                ->with('customerList', $customerList)
                ->with('matterList', $matterList)
                ->with('departmentList', $departmentList)
                ->with('staffList', $staffList)
                ->with('orderList', $orderList)
                ->with('supplierList', $supplierList)
                ->with('makerList', $makerList)
                // ->with('productList', $productList)
                ->with('warehouseList', $warehouseList)
                ->with('qrcodeList', $QrcodeList)
                ->with('initSearchParams', $initSearchParams)
                ->with('staffDepartmentData', $staffDepartmentData)
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

            // 一覧データ取得
            $Arrival = new Arrival();
            $ProductstockShelf = new ProductstockShelf();
            $list = $Arrival->getList($params);

            // 入荷ステータスにclass、text付与
            foreach ($list as $key => $value) {
                // 入荷
                $arrivalKey = $list[$key]['arrival_status'];
                $list[$key]['arrival_status'] = array(
                    'id' => $list[$key]['id'],
                    'text' => config('const.arrivalStatus.status.'. $arrivalKey. '.text'),
                    'class' => config('const.arrivalStatus.status.'. $arrivalKey. '.class'),
                    'val' => config('const.arrivalStatus.val.'. $arrivalKey),
                );

                $arrivalId = $list[$key]['arrival_id'];
                // 入荷一時置場の在庫数チェック
                $shelfKind = config('const.shelfKind.temporary');
                $stockFlg = config('const.stockFlg.val.stock');
                $list[$key]['deletable'] = config('const.flg.off');
                if ($list[$key]['own_stock_flg'] == config('const.flg.on')) {
                    // 在庫品発注
                    $stockData = $ProductstockShelf->getStockByFlgAndKind($list[$key]['product_id'], $list[$key]['warehouse_id'], $stockFlg, $shelfKind);

                    if (!empty($stockData)) {
                        if ((int)$list[$key]['arrival_quantity'] <= (int)$stockData['inventory_quantity']) {
                            $list[$key]['deletable'] = config('const.flg.on');
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }

    /**
     * エクセル出力
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function exportExcel(Request $request) 
    {
        $response = new StreamedResponse();
        $spreadsheet = null;
        $params = $request->request->all();
        $gridData =  (new Arrival())->getList($params);

        // ファイル名
        $FILE_NAME = 'arrival_list';
        // 開始列
        $START_COL = 'A';
        // 開始行
        $START_ROW = 2;
        // 終了列
        $END_COL = 'N';
        
        $HEADER_INFO = [
            'arrival_status',
            'delivery_date',
            'arrival_plan_date',
            'arrival_date',
            'matter_name',
            'order_no',
            'product_code',
            'qr_code',
            'product_name',
            'model',
            'supplier_name',
            'warehouse_name',
            'order_quantity',
            'arrival_quantity',
        ];

        try{
            // スプレッドシートを作成
            //$spreadsheet = new Spreadsheet();
            $spreadsheet = IOFactory::load(resource_path(config('const.templatePath').DIRECTORY_SEPARATOR.config('const.excelTemplateName.arrivalList')));
            
            // ファイルのプロパティを設定
            $properties = $spreadsheet->getProperties();
            $properties->setCreator(Auth::user()->staff_name);
            $properties->setLastModifiedBy(Auth::user()->staff_name);
            
            // シート作成
            $spreadsheet->getActiveSheet('Sheet1')->UnFreezePane();
            $sheet = $spreadsheet->getActiveSheet();

            // 値
            $outputDataList = [];
            foreach($gridData as $key => $record){
                $val = [];
                foreach($HEADER_INFO as $column){
                    if($column === 'arrival_status'){
                        $val[] = config('const.arrivalStatus.status.'. $record->{$column}. '.text');
                    }else{
                        $val[] = $record->{$column};
                    }
                }
                $outputDataList[] = $val;
            }


            $sheet->fromArray($outputDataList, null, $START_COL. $START_ROW, true);
            $style = $sheet->getStyle($START_COL. $START_ROW.':'.$END_COL. ((count($outputDataList)-1) + $START_ROW));
            $style->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);


            ob_end_clean();
            $response->setCallBack(function() use($spreadsheet){
                $writer = new XlsxWriter($spreadsheet);
                $writer->save('php://output');
            });

            $response->setStatusCode(200);
            $response->headers->set('Content-Description', 'File Transfer');
            $response->headers->set('Content-Disposition', 'attachment; filename='.$FILE_NAME.'_'.Carbon::now()->format('YmdHis').'.xlsx');
            $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $response->headers->set('Content-Transfer-Encoding', 'binary');
            $response->headers->set('Expires', '0');
            $response->send();
        } catch (\Exception $ex) {
            
        } finally {
            if(!is_null($spreadsheet)){
                $spreadsheet->disconnectWorksheets();
                unset($spreadsheet);
            }
        }
        return $response;
    }

    /**
     * 入荷取消
     * @param Request $request
     * @return type
     */
    public function cancel(Request $request)
    {
        $result = array('status' => false, 'message' => '');
        
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $Arrival = new Arrival();
        $ProductstockShelf = new ProductstockShelf();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        $SystemUtil = new SystemUtil();
        $ShelfNumber = new ShelfNumber();

        // リクエストデータ取得
        $params = $request->request->all();

        // 入力チェック
        //$this->isValidShipment($request);

        DB::beginTransaction();
        
        try {

            // チェック処理
            $arrivalData = $Arrival->getById($params['arrival_id'])->first();
            if ($arrivalData == null || Common::nullorBlankToZero($arrivalData['id']) == null) {
                $result['message'] = config('message.error.arrival.canceled_arrival');
                throw new \Exception();
            }

            // 発注
            // 発注の入荷完了フラグを0に
            $orderUpdateData = [];
            $orderUpdateData['id'] = $params['order_id'];
            $orderUpdateData['arrival_complete_flg'] = config('const.flg.off');
            $Order->updateById($orderUpdateData);

            // 発注明細の入荷数量を減算
            $orderDetailData = $OrderDetail->getById($params['order_detail_id']);
            $detailUpdateData = [];
            $detailUpdateData['id'] = $params['order_detail_id'];
            $detailUpdateData['arrival_quantity'] = Common::nullorBlankToZero($orderDetailData['arrival_quantity']) - Common::nullorBlankToZero($params['arrival_quantity']);
            $OrderDetail->updateById($detailUpdateData);

            // 入荷
            // 削除
            $Arrival->physicalDeleteById($params['arrival_id']);

            $moveData = [];
            if ($params['own_stock_flg'] == config('const.flg.off')) {
                // 発注品
                $moveData['stock_flg'] = config('const.stockFlg.val.order');
            } else {
                // 在庫品
                $moveData['stock_flg'] = config('const.stockFlg.val.stock');
            }
            // 発注品
            $qrData = [];
            $qrDetailData = [];
            if (!empty($params['qr_code'])) {
                // QR詳細の倉庫、棚の在庫数を減らす
                $qrData = $Qr->getByQrCode($params['qr_code']);
                if (!empty($qrData)) {
                    $qrDetailData = $QrDetail->getByQrId($qrData['qr_id']);
                    $detailData = collect($qrDetailData)->where('product_id', $params['product_id'])->first();

                    // $moveData['shelf_kind']           = config('const.shelfKind.return');
                    $moveData['stock_flg']              = config('const.stockFlg.val.order');
                    $moveData['move_kind']              = config('const.moveKind.warehouse_transfer');
                    $moveData['move_flg']               = config('const.flg.off');
                    $moveData['product_id']             = $params['product_id'];
                    $moveData['product_code']           = $params['product_code'];
                    $moveData['matter_id']              = $params['matter_id'];
                    $moveData['customer_id']            = $params['customer_id'];
                    $moveData['from_warehouse_id']      = $params['warehouse_id'];
                    $moveData['from_shelf_number_id']   = $detailData['shelf_number_id'];
                    $moveData['to_warehouse_id']        = 0;
                    $moveData['to_shelf_number_id']     = 0;
                    $moveData['quantity']               = $params['arrival_quantity'];
                    $moveData['order_id']               = $params['order_id'];
                    $moveData['order_no']               = $params['order_no'];
                    $moveData['order_detail_id']        = $params['order_detail_id'];
                    $moveData['reserve_id']             = 0;
                    $moveData['shipment_id']            = 0;
                    $moveData['shipment_detail_id']     = 0;
                    $moveData['arrival_id']             = $params['arrival_id'];
                    $moveData['sales_id']               = 0;

                    $SystemUtil->MoveInventory($moveData);
                }
            }

            // 在庫 自社在庫
            if ($params['own_stock_flg'] == config('const.flg.on')) {
                // 入庫した倉庫内の入荷一時置き場棚の該当在庫の数量を減らす
                $shelfData = $ShelfNumber->getByWarehouseIdAndShelfKind($params['warehouse_id'], config('const.shelfKind.temporary'));

                if (!empty($shelfData)) {
                    $stockUpdateData = [];
                    $stockUpdateData['stock_flg']              = config('const.stockFlg.val.stock');
                    $stockUpdateData['shelf_kind']             = config('const.shelfKind.temporary');
                    $stockUpdateData['move_kind']              = config('const.moveKind.warehouse_transfer');
                    $stockUpdateData['move_flg']               = config('const.flg.off');
                    $stockUpdateData['product_id']             = $params['product_id'];
                    $stockUpdateData['product_code']           = $params['product_code'];
                    $stockUpdateData['matter_id']              = 0;
                    $stockUpdateData['customer_id']            = 0;
                    $stockUpdateData['from_warehouse_id']      = $params['warehouse_id'];
                    $stockUpdateData['from_shelf_number_id']   = $shelfData[0]['id'];
                    $stockUpdateData['to_warehouse_id']        = 0;
                    $stockUpdateData['to_shelf_number_id']     = 0;
                    $stockUpdateData['quantity']               = $params['arrival_quantity'];
                    $stockUpdateData['order_id']               = $params['order_id'];
                    $stockUpdateData['order_no']               = $params['order_no'];
                    $stockUpdateData['order_detail_id']        = $params['order_detail_id'];
                    $stockUpdateData['reserve_id']             = 0;
                    $stockUpdateData['shipment_id']            = 0;
                    $stockUpdateData['shipment_detail_id']     = 0;
                    $stockUpdateData['arrival_id']             = $params['arrival_id'];
                    $stockUpdateData['sales_id']               = 0;

                    $SystemUtil->MoveInventory($stockUpdateData);
                }
            }

            // QRレコード
            if (!empty($qrData)) {
                // 該当QRを削除
                $detailData = collect($qrDetailData)->where('product_id', $params['product_id']);

                $data = null;
                // QRから数量を減算
                if (count($detailData) == 1) {
                    $data['id'] = $detailData[0]['id'];
                    
                    $qrDetailUpdateResult = $QrDetail->deleteByDetailId($data['id']);
                    if (count($qrDetailData) == 1) {
                        $qrDeleteResult = $Qr->deleteByQrId($qrData['qr_id']);
                    }
                }
            }

            
            DB::commit();
            $result['status'] = true;
            Session::flash('flash_success', config('message.success.save'));

        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if($result['message'] === ''){
                $result['message'] = config('message.error.error');
            }
            Log::error($e);
        }

        return \Response::json($result);
    }
}