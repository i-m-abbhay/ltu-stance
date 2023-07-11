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
 * QR数量指定分割
 */
class QrSplitQuantityDesignationController extends Controller
{
    const SCREEN_NAME = 'qr-split-quantity-designation';

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

        return view('Stock.qr-split-quantity-designation')
            ->with('isEditable', $isEditable);
    }

    /**
     * 保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function save(Request $request)
    {
        // リクエストデータ取得
        $params = $request->request->all();
        $qrInfo = $params['qrInfo'];
        $splitList = $params['splitList'];

        DB::beginTransaction();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        try {
            //QR削除
            $QrDeleteResult = $Qr->deleteByQrId($qrInfo[0]['qr_id']);
            $result = [];

            //QRを2分割
            for ($i = 0; $i <= 1; $i++) {
                //QR発番
                $NumberManage = new NumberManage();
                $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
                $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => 1);
                $result[] = $newQrCode;

                //QR追加
                $QrSaveResult = $Qr->add($newQrInfo);
                //QRDetail追加
                $newQrInfo = $Qr->getList(array('qr_code' => $newQrCode));
                foreach ($newQrInfo as $item) {
                    $newQrId = $item->id;
                }

                for ($j = 0; $j < count($qrInfo); $j++) {

                    if ($i == 0) {
                        //QRDetail削除
                        $QrDetailDeleteResult = $QrDetail->deleteByDetailId($qrInfo[$j]['detail_id']);

                        $newQrDetailInfo = array(
                            'qr_id' => $newQrId,
                            'product_id' => $qrInfo[$j]['product_id'],
                            'matter_id' => $qrInfo[$j]['matter_id'],
                            'customer_id' => $qrInfo[$j]['customer_id'],
                            'arrival_id' => $qrInfo[$j]['arrival_id'],
                            'warehouse_id' => $qrInfo[$j]['warehouse_id'],
                            'shelf_number_id' => $qrInfo[$j]['shelf_number_id'],
                            'quantity' => $splitList[$j]['quantity']
                        );
                        if ($splitList[$j]['quantity'] != 0) {
                            $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                        } else {
                            $QrDetailSaveResult = true;
                        }
                    } else {
                        $newQrDetailInfo = array(
                            'qr_id' => $newQrId,
                            'product_id' => $qrInfo[$j]['product_id'],
                            'matter_id' => $qrInfo[$j]['matter_id'],
                            'customer_id' => $qrInfo[$j]['customer_id'],
                            'arrival_id' => $qrInfo[$j]['arrival_id'],
                            'warehouse_id' => $qrInfo[$j]['warehouse_id'],
                            'shelf_number_id' => $qrInfo[$j]['shelf_number_id'],
                            'quantity' => $qrInfo[$j]['quantity'] - $splitList[$j]['quantity']
                        );

                        if ($qrInfo[$j]['quantity'] - $splitList[$j]['quantity'] != 0) {
                            $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo);
                        } else {
                            $QrDetailSaveResult = true;
                        }
                    }
                }
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
