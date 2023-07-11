<?php

namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Matter;
use App\Models\QrDetail;
use App\Models\Qr;
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\NumberManage;
use Carbon\Carbon;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * QR商品別分割
 */
class QrSplitProductSplitController extends Controller
{
    const SCREEN_NAME = 'qr-split-product-split';

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

        return view('Stock.qr-split-product-split')
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
            $qrInfo = json_decode($params['qr_info']);
            
            $Qr = new Qr();
            // QR検索
            $isExist = false;
            foreach($qrInfo as $i => $row) { 
                $list = $Qr->getByQrCode($row->qr_code);

                if (!empty($list)) {
                    $isExist = true;
                }
            }
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($isExist);
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        // リクエストデータ取得
        $qrInfo = $request->request->all();

        DB::beginTransaction();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        try {
            //QR削除
            $QrDeleteResult = $Qr->deleteByQrId($qrInfo[0]['qr_id']);
            $result = [];

            //分割数分の新規QRを採番
            for ($i = 0; $i < count($qrInfo); $i++) {

                //QRDetail削除
                $QrDetailDeleteResult = $QrDetail->deleteByDetailId($qrInfo[$i]['detail_id']);

                //QR発番
                $NumberManage = new NumberManage();
                $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => 1);
                //QR追加
                $QrSaveResult = $Qr->add($newQrInfo);
                //QRDetail追加
                $newQrInfo = $Qr->getList(array('qr_code' => $newQrCode));
                foreach ($newQrInfo as $item) {
                    $newQrId = $item->id;
                }
                $newQrDetailInfo = array(
                    'qr_id' => $newQrId,
                    'product_id' => $qrInfo[$i]['product_id'],
                    'matter_id' => $qrInfo[$i]['matter_id'],
                    'customer_id' => $qrInfo[$i]['customer_id'],
                    'arrival_id' => $qrInfo[$i]['arrival_id'],
                    'warehouse_id' => $qrInfo[$i]['warehouse_id'],
                    'shelf_number_id' => $qrInfo[$i]['shelf_number_id'],
                    'quantity' => $qrInfo[$i]['quantity']
                );
                $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                $result[] = $newQrCode;
            }

            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }
    /**
     * 印刷
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function print(Request $request)
    {
        // リクエストデータ取得
        $data = $request->request->all();

        try {
            $SystemUtil = new SystemUtil();
            $resultSts = $SystemUtil->qrPrintManager(array('qr_code' => $data['qr_code']), 1);
            if (!$resultSts) {
                return \Response::json("printError");
            }
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
        // return \Response::json(true);
    }
}
