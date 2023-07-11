<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use DB;
use Session;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Address;
use App\Models\Warehouse;
use Illuminate\Support\Facades\Schema;

/**
 * 発注書
 */
class OrderReportController extends Controller
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
     * 初期表示（印刷）
     *
     * @param [type] $orderId
     * @return void
     */
    public function print($orderId){
        $Order = new Order();
        $Address = new Address();
        $Warehouse = new Warehouse();
        $SystemUtil = new SystemUtil();

        // データ更新に失敗してもとりあえず出力
        $data = $SystemUtil->getOrderReportData($orderId);
            
        // 地図データ取得
        $order = $Order->getById($orderId);
        $addressData = null;
        if ($order->delivery_address_kbn == config('const.deliveryAddressKbn.val.site')) {
            $addressData = $Address->getById($order->delivery_address_id);
        }else{
            $addressData = $Warehouse->getById($order->delivery_address_id);
        }
        
        if (!$addressData) {
            $columnList = Schema::getColumnListing($Address->getTable());
            foreach ($columnList as $columnName) {
                $addressData[$columnName] = null;
            }
        }

        return view('Order.order-report')
            ->with('order', $Order->getById($orderId))
            ->with('dataSource', json_encode($data))
            ->with('addressData', json_encode($addressData))
        ;
    }
}