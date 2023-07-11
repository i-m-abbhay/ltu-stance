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
use App\Models\ShipmentDetail;
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use App\Models\ShelfNumber;
use Auth;

/**
 * 出荷手続
 */
class ShippingProcessController extends Controller
{
    const SCREEN_NAME = 'shipping-process';

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

        // 入荷棚のID取得
        $ShelfNumber = new ShelfNumber();
        $shelfList = $ShelfNumber->getList([]);
        $tempShelfList = collect($shelfList)->where('shelf_kind', config('const.shelfKind.temporary'))->pluck('id')->toArray();
        
        return view('Shipping.shipping-process')
                ->with('isEditable', $isEditable)
                ->with('temporaryShelfId', json_encode($tempShelfList))
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
            $ShipmentDetail = new ShipmentDetail();
            $list = $ShipmentDetail->getList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }
        return \Response::json($list);
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
        $params = $request->request->all();

        // 一覧データ取得
        $Loading = new Loading();
        $list = $Loading->getList($params);

        $ProductMove = new ProductMove();
        DB::beginTransaction();
        try {
            if (count($list) == 0) {
                // 出荷積込登録
                $saveResult = $Loading->add($params);
                if (!$saveResult) {
                    throw new \Exception(config('message.error.save'));
                }
            }
            else {
                // 出荷積込更新
                $params['id'] = $list->pluck('id')[0];
                $saveResult = $Loading->updateById($params);
                if (!$saveResult) {
                    throw new \Exception(config('message.error.save'));
                }    
            }
            // 入出庫トラン登録
            $saveResult = $ProductMove->add($params, $saveResult);
            if (!$saveResult) {
                throw new \Exception(config('message.error.save'));
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

     /**
     * 数量更新
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function quantity(Request $request)
    {
        $resultSts = false;

        $params = $request->request->all();

        // バリデーションチェック
        $this->isValid($request);

        DB::beginTransaction();
        $ShipmentDetail = new ShipmentDetail();
        try {
            $saveResult = $ShipmentDetail->updateQuantity($params);
            if (!$saveResult) {
                throw new \Exception(config('message.error.save'));
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

    /**
     * 論理削除
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function delete(Request $request)
    {
        $resultSts = false;
        // リクエストデータ取得
        $params = $request->request->all();
        DB::beginTransaction();
        try {
            $Customer = new Customer();

            // 削除
            $deleteResult = $Customer->deleteById($params['id']);

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
     *
     * @param Request $request
     * @return boolean
     */
    protected function isValid(Request $request) 
    {
        $this->validate($request, [
            'change_quantity' => 'numeric',
        ]);
    }
}
