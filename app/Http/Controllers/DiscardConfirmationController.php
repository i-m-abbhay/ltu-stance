<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ShelfNumber;
use App\Models\NumberManage;
use App\Models\Loading;
use App\Models\Qr;
use App\Models\QrDetail;
use App\Models\WarehouseMove;
use App\Models\Discard;
use App\Models\Returns;
use App\Libs\SystemUtil;
use Carbon\Carbon;
use DB;
use Session;
use Auth;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * 廃棄商材
 */
class DiscardConfirmationController extends Controller
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
        // TODO: 権限チェック
        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Returns.discard-confirmation')
            ->with('isEditable', $isEditable);
    }

    /**
     * 保存
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        // リクエストデータ取得
        $paramData = $request->request->all();
        $tableData = $paramData['tableData'];
        $sign = $paramData['sign'];

        DB::beginTransaction();
        $Discard = new Discard();
        $Returns = new Returns();

        try {
            $saveResult = false;
            foreach ($tableData as $data) {

                //入出庫トランザクションの作成
                $params['move_kind'] = 7; //廃棄
                $params['move_flg'] = 0;
                $params['is_discarded'] = true;
                $params['quantity'] = $data['returns_quantity'];
                $params['from_warehouse_id'] = $data['warehouse_id'];
                $params['from_shelf_number_id'] = $data['shelf_number_id'];
                $params['to_warehouse_id'] = 0;
                $params['to_shelf_number_id'] = 0;
                $params['product_id'] = $data['product_id'];
                $params['product_code'] = $data['product_code'];
                $params['matter_id'] = $data['matter_id'];
                $params['customer_id'] = $data['customer_id'];

                //発注品
                if ($params['matter_id']  != 0 && $params['customer_id'] != 0) {
                    $params['stock_flg'] = 0;
                }
                //在庫品
                elseif ($params['matter_id']  == 0 && $params['customer_id'] == 0) {
                    $params['stock_flg'] = 1;
                }
                //預かり品
                else {
                    $params['stock_flg'] = 2;
                }

                $params['order_id'] = 0;
                $params['order_detail_id'] = 0;
                $params['order_no'] = 0;
                $params['reserve_id'] = 0;
                $params['shipment_id'] = 0;
                $params['shipment_detail_id'] = 0;
                $params['arrival_id'] = 0;
                $params['sales_id'] = 0;
                $SystemUtil = new SystemUtil();
                $to_product_move_id = $SystemUtil->MoveInventory($params);

                //QR削除
                $SystemUtil->deleteQr($data['qr_id']);

                //廃棄テーブル更新
                $params['id'] = $data['id'];
                $params['discard_finish'] = 1;
                $params['discard__move_id'] = $to_product_move_id;
                $params['image_sign'] = $sign;
                $saveResult = $Discard->updateByIdDiscard($params);

                //返品テーブル更新
                $params['warehouse_move_id'] = $data['warehouse_move_id'];
                $params['return_kbn'] = 4;
                $params['return_finish'] = 1;
                $saveResult = $Returns->updateByWarehouseMoveId($params);
            }

            DB::commit();
            $saveResult  = true;

            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $saveResult = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($saveResult);
    }
}
