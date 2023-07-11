<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use App\Models\Base;
use App\Models\LockManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Models\WoodUnitPrice;

/**
 * 木材立米単価登録
 */
class WoodUnitPriceEditController extends Controller
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
        
        // 編集権限
        // $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        return view('WoodUnitPrice.wood-unit-price-edit')
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
            $WoodUnitPrice = new WoodUnitPrice();
            $list = $WoodUnitPrice->getList($params);
            foreach($list as $key => $row) {
                $leng = 0;
                $thick = 0;
                $width = 0;
                if (!empty($list[$key]['length'])) {
                    $leng = $list[$key]['length'] / 1000;
                }
                if (!empty($list[$key]['thickness'])) {
                    $thick = $list[$key]['thickness'] / 1000;
                }
                if (!empty($list[$key]['width'])) {
                    $width = $list[$key]['width'] / 1000;
                }
                $calcSize = round($this->rmNullZero($leng) * $this->rmNullZero($thick) * $this->rmNullZero($width), 4);
                $list[$key]['calc_size'] = $calcSize;
                if (!empty($list[$key]['id'])) {   
                    $list[$key]['purchasing_cost'] = (float)$list[$key]['purchasing_cost'];
                    $list[$key]['purchase_unit_price'] = (int)$list[$key]['purchase_unit_price'];
                    $list[$key]['sales_unit_price'] = (int)$list[$key]['sales_unit_price'];
                    $list[$key]['unitprice'] = (int)$list[$key]['unitprice'];
                    // $list[$key]['purchasing_cost'] = round($calcSize * $this->rmNullZero($list[$key]['unitprice']), 4);
                    // $list[$key]['purchase_unit_price'] = round($this->rmNullZero($list[$key]['purchasing_cost']) / config('const.unitPriceRate.purchase') ,4);
                    // $list[$key]['sales_unit_price'] = round($list[$key]['purchase_unit_price'] / config('const.unitPriceRate.sales'), 4);
                } else {
                    $list[$key]['purchasing_cost'] = 0;
                    $list[$key]['purchase_unit_price'] = 0;
                    $list[$key]['sales_unit_price'] = 0;
                    $list[$key]['unitprice'] = 0;
                }
            }
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

        // リクエストから検索条件取得
        $params = $request->request->all();
        $woodList = json_decode($params['gridData'], true);

        $WoodUnitPrice = new WoodUnitPrice();
        DB::beginTransaction();
        try {
            $saveResult = false;
            foreach ($woodList as $key => $row) {
                $newFlg = false;

                $wupData = $WoodUnitPrice->getFutureDataByProductIdAndDate($woodList[$key]['product_id'], Carbon::now()->format('Y/m/d'));
                if (empty($wupData['id'])) {
                    $newFlg = true;
                }

                $insertData = [];
                if ($newFlg) {
                    // 登録
                    $insertData['product_id'] = $woodList[$key]['product_id'];
                    $insertData['price_date'] = $woodList[$key]['price_date'];
                    $insertData['unitprice'] = $woodList[$key]['unitprice'];
                    $insertData['purchasing_cost'] = $woodList[$key]['purchasing_cost'];
                    $insertData['purchase_unit_price'] = $woodList[$key]['purchase_unit_price'];
                    $insertData['sales_unit_price'] = $woodList[$key]['sales_unit_price'];

                    $result = $WoodUnitPrice->add($woodList[$key]);
                    $saveResult = true;
                } else {
                    // 更新
                    $delResult = $WoodUnitPrice->deleteById($wupData['id']);

                    $insertData['product_id'] = $woodList[$key]['product_id'];
                    $insertData['price_date'] = $woodList[$key]['price_date'];
                    $insertData['unitprice'] = $woodList[$key]['unitprice'];
                    $insertData['purchasing_cost'] = $woodList[$key]['purchasing_cost'];
                    $insertData['purchase_unit_price'] = $woodList[$key]['purchase_unit_price'];
                    $insertData['sales_unit_price'] = $woodList[$key]['sales_unit_price'];

                    $result = $WoodUnitPrice->add($woodList[$key]);
                    $saveResult = true;
                }
            }
            
            if ($saveResult) {
                DB::commit();
                $resultSts = true;
                Session::flash('flash_success', config('message.success.save'));
            } else {
                throw new \Exception(config('message.error.save'));
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
     * nullの場合0を返す
     * @param $data
     */
    private function rmNullZero($data){
        $result = 0;
        if($data != null){
            $result = $data;
        }
        return $result;
    }

    /**
     * バリデーションチェック
     *
     * @param \App\Http\Controllers\Request $request
     * @return boolean
     */
    public function isValid(Request $request) 
    {   
        
        $this->validate($request, [
        ]);
    }
}