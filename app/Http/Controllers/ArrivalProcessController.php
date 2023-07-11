<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Matter;
use App\Models\Arrival;
use App\Models\ProductMove;
use App\Models\OrderDetail;
use App\Models\ProductstockShelf;
use App\Models\ShelfNumber;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\NumberManage;
use App\Models\ProductCostPrice;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use DB;
use Carbon\Carbon;
use Session;
use Auth;
use App\Models\Authority;
use App\Libs\SystemUtil;
use App\Models\Reserve;

/**
 * 入荷処理
 */
class ArrivalProcessController extends Controller
{
    /**
     * 定数
     */
    const ORDERED_GOODS = '0'; //発注品
    const STOCK = '1'; //在庫品
    const CUSTODY = '2'; //預かり品

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
        // TODO: 権限チェック

        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Arrival.arrival-process')
            ->with('isEditable', $isEditable);
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
            $table = $params['checkTable'];
            $orderNoList = [];

            foreach ($table as $row) {
                $orderNoList[] = $row['order_no'];
            }

            // 一覧データ取得
            $OrderDetail = new OrderDetail();
            $list = $OrderDetail->getByOrderNos($orderNoList);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $resultSts = true;

        // リクエストデータ取得
        $tableData = $request->request->all();

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();
        $Arrival = new Arrival();
        $OrderDetail = new OrderDetail();
        $Order = new Order();
        $ShelfNumber = new ShelfNumber();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        try {
            $saveResult = false;
            $index = 0;
            foreach ($tableData as $params) {
                $params['to_shelf_number_id'] = null;

                if ($params['check_quantity'] > 0) {
                    // 入荷登録
                    $params['from_warehouse_id'] = 0;
                    $params['from_shelf_number_id'] = 0;
                    $params['to_warehouse_id'] = $params['delivery_address_id'];
                    $params['warehouse_id'] = $params['delivery_address_id'];
                    $params['shelf_kind'] = 1;
                    $list = $ShelfNumber->getList($params);
                    if (count($list) > 0) {
                        $params['to_shelf_number_id'] = $list->pluck('id')[0];
                    } else {
                        $params['to_shelf_number_id'] = 0;
                    }
                    $params['quantity'] = $params['check_quantity'];
                    // $params['order_id'] = 0;				
                    // $params['order_no'] = 0;				
                    // $params['order_detail_id'] = 0;	

                    $Reserve = new Reserve();
                    $ReserveParams['stock_flg'] = $params['own_stock_flg'];
                    $ReserveParams['matter_id'] = $params['matter_id'];
                    $ReserveParams['order_detail_id'] = $params['order_detail_id'];
                    $ReserveParams['quote_detail_id'] = $params['quote_detail_id'];
                    $ReserveParams['from_warehouse_id'] = $params['delivery_address_id'];
                    $reserveArray = $Reserve->getByOrder($ReserveParams);
                    $params['reserve_id'] = $reserveArray['id'] == null ? 0 : $reserveArray['id'];
                    if ($params['own_stock_flg'] == 1) {
                        $params['qr_prinf_flg'] = 1;
                        $params['qr_detail'] = null;
                    } else {
                        $params['qr_prinf_flg'] = 0;
                    }
                    $arrivalId = $Arrival->add($params);
                    $params['arrival_id'] = $arrivalId;
                    $params['shelf_number_id'] = $params['to_shelf_number_id'];

                    //自社在庫以外の場合QR発行＆入荷レコードにQR登録
                    if ($params['own_stock_flg'] != 1) {
                        $NumberManage = new NumberManage();
                        $qr_code = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                        $params['qr_code'] = $qr_code;
                        $qr_id = $Qr->add($params);
                        $tableData[$index]['qr_id'] = $qr_id;
                        // QR明細登録
                        $params['qr_id'] = $qr_id;
                        $qrDetailId = $QrDetail->add($params);
                        $tableData[$index]['qr_detail_id'] = $qrDetailId;

                        //入荷レコード更新
                        $params['id'] = $arrivalId;
                        $params['qr_detail'] = $qrDetailId;
                        $arrivalId = $Arrival->updateQr($params);
                    }

                    // 発注明細更新
                    $params['id'] = $params['order_detail_id'];
                    $params['arrival_quantity'] = $params['arrival_quantity'] + $params['check_quantity'];
                    $saveResult = $OrderDetail->updateQuantity($params);
                    if (!$saveResult) {
                        throw new \Exception(config('message.error.save'));
                    }

                    // 発注更新（入荷済フラグ）
                    // order_no				
                    $saveResult = $Order->updateComplete($params);

                    // 入出庫トラン登録
                    // product_id				
                    // product_code				
                    // to_warehouse_id				
                    // to_shelf_number_id				
                    $params['move_kind'] = 1;   // 入荷		
                    $params['move_flg'] = 0;    // 確定				
                    $params['quantity'] = $params['check_quantity'];
                    // order_id				
                    // order_no				
                    // order_detail_id				
                    $params['reserve_id'] = 0;
                    $params['shipment_id'] = 0;
                    $params['shipment_detail_id'] = 0;
                    $list = $Arrival->getLastId($params);
                    $params['arrival_id'] = $list['arrival_id'];
                    $tableData[$index]['arrival_id'] = $list['arrival_id'];
                    $list = $Arrival->getListById($params);
                    $tableData[$index]['warehouse_id'] = $list[0]['to_warehouse_id'];
                    $tableData[$index]['shelf_number_id'] = $list[0]['to_shelf_number_id'];

                    $params['sales_id'] = 0;

                    //在庫種別（当社在庫品フラグで判定）
                    if ($params['own_stock_flg'] == 1) {
                        $params['stock_flg'] = 1;
                        $params['matter_id'] = 0;
                        $params['customer_id'] = 0;
                    } else {
                        $params['stock_flg'] = 0;
                    }

                    $params['shelf_kind'] = 1;
                    // matter_id				
                    $SystemUtil = new SystemUtil();
                    $saveResult = $SystemUtil->MoveInventory($params);

                    //最終発注仕入単価書き込み(在庫品の場合のみ)
                    if ($params['own_stock_flg'] == 1) {
                        $ProductCostPrice = new ProductCostPrice();
                        if ($params['price'] == null) {
                            $params['price'] = 0;
                        }
                        $saveResult = $ProductCostPrice->add($params);
                    }
                }

                $index = $index + 1;
            }
            DB::commit();
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json(array('status' => $resultSts, 'tabledata' => $tableData));
    }

    /**
     * 印刷
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function print(Request $request)
    {
        $isPrinedtError = false;

        // リクエストデータ取得
        $data = $request->request->all();
        $tableData = $data['tableData'];
        $collect = $data['collect'];

        // バリデーションチェック
        $this->isValid($request);

        $Qr = new Qr();
        $QrDetail = new QrDetail();
        $Arrival = new Arrival();
        $SystemUtil = new SystemUtil();

        $result = []; //urlList or printError

        try {
            if ($tableData != null && count($tableData) > 0) {
                $collect_number =  $tableData[0]['collect_number'];

                DB::beginTransaction();

                //QRまとめの場合
                if ($collect) {
                    //新規QRの発行
                    $NumberManage = new NumberManage();
                    $qr_code = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                    $params['qr_code'] = $qr_code;
                    $params['print_number'] = $collect_number;
                    $qr_id = $Qr->add($params);

                    foreach ($tableData as $params) {
                        //入荷確定している場合
                        if ($params['check_quantity'] > 0 && $params['collect_number'] > 0 && $params['arrival_id'] !== null) {
                            //既存QRの削除
                            $Qr->deleteByQrId($params['qr_id']);

                            ////QR詳細を新規QRに紐づけ(同商品の重複をNGとする場合はコメントアウトを外す)
                            $qrDetailParams['id'] = $params['qr_detail_id'];
                            $qrDetailParams['qr_id'] = $qr_id;
                            // $qrDetailParams['product_id'] = $params['product_id'];
                            // $getDuplicateProduct = $QrDetail->getDuplicateProduct($qrDetailParams);
                            // $getById =  $QrDetail->getById($qrDetailParams['id']);

                            // //重複が存在する場合
                            // if ($getDuplicateProduct != null && $getDuplicateProduct['id'] != null) {
                            //     //QR詳細を削除して既存レコードに統合
                            //     $qrDetailParams['quantity'] = $getById['quantity'] + $getDuplicateProduct['quantity'];
                            //     $QrDetail->deleteByDetailId($qrDetailParams['id']);
                            //     $qrDetailParams['id'] = $getDuplicateProduct['id'];
                            //     $QrDetail->updateById($qrDetailParams);
                            // } else {
                                $QrDetail->updateById($qrDetailParams);
                            // }

                        }
                    }
                    DB::commit();

                    //印刷
                    try {
                        $isUrl = $SystemUtil->qrPrintManager(array('qr_code' => $qr_code),  $collect_number);
                    } catch (\Exception $e) {
                        return \Response::json("printError");
                    }
                    if (!$isUrl) {
                        $isPrinedtError = true;
                    } else {
                        DB::beginTransaction();
                        $result[] = $isUrl;
                        foreach ($tableData as $params) {
                            if ($params['check_quantity'] > 0 && $params['collect_number'] > 0 && $params['arrival_id'] !== null) {
                                //入荷レコード更新
                                $arrivalparams['id'] = $params['arrival_id'];
                                $arrivalparams['qr_prinf_flg'] = 1;
                                $arrivalId = $Arrival->updateQrPrinfFlg($arrivalparams);
                            }
                        }
                        DB::commit();
                    }
                }
                //まとめではない場合
                else {
                    foreach ($tableData as $params) {
                        //印刷
                        $isUrl = $SystemUtil->qrPrintManager(array('qr_id' => $params['qr_id']), $params['print_number']);
                        if (!$isUrl) {
                            DB::rollBack();
                            return \Response::json("printError");
                        } else {
                            $result[] = $isUrl;
                            if ($params['check_quantity'] > 0 && $params['print_number'] > 0 && $params['arrival_id'] !== null) {
                                //入荷レコード更新
                                $arrivalparams['id'] = $params['arrival_id'];
                                $arrivalparams['qr_prinf_flg'] = 1;
                                $arrivalId = $Arrival->updateQrPrinfFlg($arrivalparams);
                            }
                        }
                    }
                    DB::commit();
                }

                if ($isPrinedtError) {
                    return \Response::json("printError");
                }
            }
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }

    /**
     * 再印刷
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function rePrint(Request $request)
    {
        $resultSts = true;
        $isPrinedtError = false;

        // リクエストデータ取得
        $data = $request->request->all();
        $tableData = $data['tableData'];

        $Qr = new Qr();
        $QrDetail = new QrDetail();
        $Arrival = new Arrival();
        $SystemUtil = new SystemUtil();

        try {
            if ($tableData != null && count($tableData) > 0) {
                $collect_number =  $tableData[0]['collect_number'];
                $qr_detail_id = $tableData[0]['qr_detail_id'];
                $qrDetailInfo = $QrDetail->getById($qr_detail_id);
                if ($qrDetailInfo != null && $qrDetailInfo['qr_id']) {
                    $qr_id = $qrDetailInfo['qr_id'];

                    DB::beginTransaction();

                    //印刷
                    $resultSts = $SystemUtil->qrPrintManager(array('qr_id' => $qr_id),  $collect_number);
                    if (!$resultSts) {
                        $isPrinedtError = true;
                    } else {
                        foreach ($tableData as $params) {
                            if ($params['check_quantity'] > 0 && $params['collect_number'] > 0 && $params['arrival_id'] !== null) {
                                //入荷レコード更新
                                $arrivalparams['id'] = $params['arrival_id'];
                                $arrivalparams['qr_prinf_flg'] = 1;
                                $arrivalId = $Arrival->updateQrPrinfFlg($arrivalparams);
                            }
                        }
                    }
                    DB::commit();
                } else {
                    $isPrinedtError = true;
                }

                if ($isPrinedtError) {
                    return \Response::json("printError");
                }
            }
            //Session::flash('flash_success', config('message.success.save'));
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
            'check_quantity'  => 'numeric',
        ]);
    }
}
