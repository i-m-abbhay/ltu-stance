<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Product;
use App\Models\Qr;
use App\Models\Warehouse;
use App\Models\WarehouseMove;
use DB;
use Session;

/**
 * 在庫移管一覧
 */
class StockTransferListController extends Controller
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
        // 閲覧権限チェック
        // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
        //     throw new NotFoundHttpException();
        // }

        $warehouseList = (new Warehouse)->getComboList();
        $qrcodeList = (new Qr)->getComboList();

        return view('Stock.stock-transfer-list')
                ->with('warehouseList', $warehouseList)
                ->with('qrcodeList', $qrcodeList)
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
            $list = (new WarehouseMove)->getTransferList($params);

            foreach ($list as $key => $value) {
                // class付与
                $moveKey = $list[$key]['move_status'];
                $list[$key]['move_status'] = array(
                    'id' => $list[$key]['id'],
                    'text' => config('const.warehouseMoveStatus.'. $moveKey. '.text'),
                    'class' => config('const.warehouseMoveStatus.'. $moveKey. '.class'),
                    'val' => config('const.warehouseMoveStatus.val.'. $moveKey),
                );
            }

            
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
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
            // // 編集権限チェック
            // if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit')) === config('const.authority.none')) {
            //     throw new \Exception();
            // }

            $WarehouseMove = new WarehouseMove();

            if (empty($params['qr_code'])) {
                // IDから削除
                $deleteResult = $WarehouseMove->deleteById($params['id']);
            } else {
                // QRから削除
                $deleteResult = $WarehouseMove->deleteByQrCode($params);
            }

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
}