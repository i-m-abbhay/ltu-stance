<?php

namespace App\Http\Controllers;

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
use App\Models\Loading;
use App\Models\WarehouseMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;
use Hamcrest\NullDescription;

/**
 * 返品数入力
 */
class AcceptingReturnsQuantityInputController extends Controller
{
    const SCREEN_NAME = 'accepting-returns-quantity-input';

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

        return view('Returns.accepting-returns-quantity-input')
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
        $result = ['status' => false, 'message' => ''];
        // リクエストデータ取得
        $paramData = $request->request->all();
        $tableData = $paramData['tableData'];
        $returns_quantity = $paramData['returns_quantity'];
        $returns_date = $paramData['returns_date'];

        DB::beginTransaction();
        $WarehouseMove = new WarehouseMove();

        try {

            // 返品済み数取得
            $returnedData = $WarehouseMove->getSumReturnedQuantity($tableData['delivery_id']);
            if ($returnedData != null) {
                $sumQuantity = Common::nullorBlankToZero($returnedData['quantity']) + Common::nullorBlankToZero($returns_quantity);
                if ($sumQuantity > $tableData['delivery_quantity']) {
                    $result['message'] = '返品可能数を超えています。確認してください。';
                    
                    throw new \Exception();
                }
            }

            //倉庫移動レコードの作成
            $params['move_kind'] = 2; //返品
            $params['from_warehouse_id'] = 0;
            $params['to_warehouse_id'] = 0;
            $params['finish_flg'] = 0;
            $params['from_product_move_id'] = null;
            $params['from_product_move_staff_id'] = null;
            $params['from_product_move_at'] = null;
            $params['to_product_move_id'] = null;
            $params['to_product_move_staff_id'] = null;
            $params['to_product_move_at'] = null;
            $params['memo'] = null;
            $params['move_date'] = $returns_date;
            $params['stock_flg'] = $tableData['stock_flg'];
            $params['product_code'] = $tableData['product_code'];
            $params['product_id'] = $tableData['product_id'];
            $params['quantity'] = $returns_quantity;
            $params['qr_code'] = null;
            $params['shipment_detail_id'] = $tableData['shipment_detail_id'];
            $params['approval_status'] = 0;
            $params['approval_user'] = null;
            $params['approval_at'] = null;
            $params['matter_id'] = $tableData['matter_id'];
            $params['customer_id'] = $tableData['customer_id'];
            $params['delivery_id'] = $tableData['delivery_id'];
            $params['order_detail_id'] = $tableData['order_detail_id'];
            $params['quote_id'] = $tableData['quote_id'];
            $params['quote_detail_id'] = $tableData['quote_detail_id'];

            $result['status'] = $WarehouseMove->add($params);

            if ($result['status'] === false) {
                throw new \Exception(config('message.error.save'));
            }

            DB::commit();

            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($result);
    }
}
