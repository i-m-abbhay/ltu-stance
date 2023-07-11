<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Shipment;
use App\Libs\SystemUtil;
use Auth;

/**
 * 納品証明
 */
class DeliveryPhotoController extends Controller
{
    const SCREEN_NAME = 'delivery-photo';

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

        return view('Delivery.delivery-photo')
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
        $resultSts = false;

        // リクエストデータ取得
        $data = $request->request->all();
        $imgData = $data['captures'];
        $shipmentData = $data['tableData'];

        // // バリデーションチェック
        // $this->isValid($request);

        DB::beginTransaction();
        $Shipment = new Shipment();
        try {
            $saveResult = false;

            for ($i = 0; $i < count($shipmentData); $i++) {

                $params['id'] = $shipmentData[$i]['shipment_id'];
                if (count($imgData) > 0) {
                    $params['image_photo1'] = $imgData[0];
                }
                if (count($imgData) > 1) {
                    $params['image_photo2'] = $imgData[1];
                }
                $saveResult = $Shipment->updateById($params);
            }

            DB::commit();
            $resultSts = true;
            //Session::flash('flash_success', config('message.success.save'));
        } catch (\Exception $e) {
            DB::rollBack();
            $resultSts = false;
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }
        return \Response::json($resultSts);
    }
}
