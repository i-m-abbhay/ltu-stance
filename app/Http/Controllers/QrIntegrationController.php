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
 * QR統合
 */
class QrIntegrationController extends Controller
{
    const SCREEN_NAME = 'qr-integration';

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

        return view('Stock.qr-integration')
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

            // 一覧データ取得
            $QrDetail = new QrDetail();
            $list = $QrDetail->getQrSplitList($params['qr_code']);
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
        $resultSts = false;

        // リクエストデータ取得
        $params = $request->request->all();
        $qrInfo = json_decode($params['qrInfo'], true);
        $printNumber = $params['print_number'];

        //印刷用の配列
        $printArray = [];

        DB::beginTransaction();
        $Qr = new Qr();
        $QrDetail = new QrDetail();
        try {

            // $printNumber = 0;
            // $qrIdList = collect($qrInfo)->pluck('qr_id')->unique()->toArray();
            // foreach ($qrIdList as $i => $qrId) {
            //     $qrData = $Qr->getPrintNumber(['qr_id' => $qrId]);
            //     $printNumber += $qrData['print_number'];
            // }

            //QR発番
            $NumberManage = new NumberManage();
            $newQrCode = $NumberManage->getSeqNo(config('const.number_manage.kbn.qr'), Carbon::today()->format('Ym'));
            $newQrInfo = array('qr_code' => $newQrCode, 'print_number' => $printNumber);
            //印刷用配列更新
            array_push($printArray, $newQrInfo);
            //QR追加
            $QrSaveResult = $Qr->add($newQrInfo);
            //新規QRコードの取得
            $newQrList = $Qr->getList(array('qr_code' => $newQrCode));
            $newQrId = '';
            foreach ($newQrList as $item) {
                $newQrId = $item->id;
            }

            //既存QR情報を削除して統合データを集計する
            $newQrDetailInfo = [];
            for ($i = 0; $i < count($qrInfo); $i++) {
                //QR削除
                $QrDeleteResult = $Qr->deleteByQrId($qrInfo[$i]['qr_id']);
                //QRDetail削除
                $QrDetailDeleteResult = $QrDetail->deleteByDetailId($qrInfo[$i]['detail_id']);


                // //商品ごとに合算する場合はコメントアウト部分を活性化
                // //商品ごとにQR明細を集計
                // $productArray = array_column($newQrDetailInfo, 'product_id');
                // $result = array_search($qrInfo[$i]['product_id'], $productArray);

                // if ($result === false) {
                    array_push($newQrDetailInfo, array(
                        'qr_id' => $newQrId,
                        'product_id' => $qrInfo[$i]['product_id'],
                        'matter_id' => $qrInfo[$i]['matter_id'],
                        'customer_id' => $qrInfo[$i]['customer_id'],
                        'arrival_id' => $qrInfo[$i]['arrival_id'],
                        'warehouse_id' => $qrInfo[$i]['warehouse_id'],
                        'shelf_number_id' => $qrInfo[$i]['shelf_number_id'],
                        'quantity' => $qrInfo[$i]['quantity']
                    ));
                // } 
                // //商品ごとに合算する場合の処理
                // else {
                //     $newQrDetailInfo[$result]['quantity'] += $qrInfo[$i]['quantity'];
                //     //入荷IDは最大値を使用
                //     if ($newQrDetailInfo[$result]['arrival_id'] < $qrInfo[$i]['arrival_id']) {
                //         $newQrDetailInfo[$result]['arrival_id'] = $qrInfo[$i]['arrival_id'];
                //     }
                // }
            }

            //QRDetail追加
            for ($i = 0; $i < count($newQrDetailInfo); $i++) {
                $QrDetailSaveResult = $QrDetail->add($newQrDetailInfo[$i]);
            }

            DB::commit();
            Session::flash('flash_success', config('message.success.save'));
            $resultSts = true;

            $SystemUtil = new SystemUtil();
            try {
                $resultSts = $SystemUtil->qrPrintManager(array('qr_code' => $newQrCode), 1);
            } catch (\Exception $e) {
                return \Response::json("printError");
            }
            if (!$resultSts) {
                return \Response::json("printError");
            }

            $result = ['qr_code' => $newQrCode, 'result' => $resultSts];
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
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
            return \Response::json("printError");
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
        // return \Response::json(true);
    }
}
