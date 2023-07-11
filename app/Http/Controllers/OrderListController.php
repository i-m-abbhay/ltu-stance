<?php
namespace App\Http\Controllers;

use App\Libs\SystemUtil;
use Illuminate\Http\Request;
use App\Models\Matter;
use App\Models\Quote;
// use App\Models\QuoteVersion;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Product;
use App\Models\Supplier;
// use App\Models\Authority;
// use App\Models\QuoteDetail;
use App\Models\SupplierMaker;
use App\Providers\OrderServiceProvider;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
// use App\Models\ApprovalDetail;
// use App\Models\ApprovalHeader;
use App\Models\LockManage;
use App\Models\OrderDetail;
use App\Models\Reserve;
use Illuminate\Support\Collection;
use DB;
use Storage;

/**
 * 受発注一覧
 */
class OrderListController extends Controller
{

    const SCREEN_NAME = 'order-list';

    // サービス
    private $OrderServiceProvider;

    /**
     * コンストラクタ
     */
    public function __construct(OrderServiceProvider $OrderServiceProvider)
    {
        // ログインチェック
        $this->middleware('auth');
        $this->OrderServiceProvider = $OrderServiceProvider;
        $this->OrderServiceProvider->initialize($this);
    }

    /**
     * 初期表示
     * @return type
     */
    public function index()
    {
        $Customer = new Customer();
        $Department = new Department();
        $Staff = new Staff();
        $StaffDepartment = new StaffDepartment();
        $Matter = new Matter();
        // $Quote = new Quote();
        // $Order = new Order();
        $Supplier = new Supplier();
        $SupplierMaker = new SupplierMaker();

        // 得意先
        $customerData = $Customer->getComboList(); 
        // 部門
        $departmentData = $Department->getComboList();
        // 担当
        $staffData = $Staff->getComboList();
        // 担当者部門
        $staffDepartmentData = $StaffDepartment->getCurrentTermList();
        $staffDepartmentData = $staffDepartmentData->mapToGroups(function ($item, $key) {
            return [
                $item->department_id => $item->staff_id,
            ];
        });

        // 案件
        $matterData = $Matter->getComboListThin(null, null, config('const.flg.off'));
        // 見積
        // $quoteData = $Quote->getComboList();
        // 発注
        // $orderData = $Order->getComboList();
        // 仕入先・メーカー名
        $makerData = $Supplier->getBySupplierKbnThin(array(config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.maker')));
        $supplierData = $Supplier->getBySupplierKbnThin(array(config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.supplier')));
        // 仕入先メーカーリスト取得
        $tmpSupplierMakerData = $SupplierMaker->getComboListByMakerIdThin();
        $supplierMakerData = $tmpSupplierMakerData->mapToGroups(function ($item, $key) {
            return [$item['maker_id'] => $item];
        });

        $departmentId = $StaffDepartment->getMainDepartmentByStaffId(Auth::id())['department_id'];
        $initSearchParams = collect([
            'sales_staff_id' => Auth::id(),
            'department_id' => $departmentId,
            'order_register_date_from' => Carbon::today()->subMonth(3)->format('Y/m/d'),
            'order_register_date_to' => Carbon::today()->format('Y/m/d'),
            'order_process_date_from' => Carbon::today()->subMonth(3)->format('Y/m/d'),
            'order_process_date_to' => Carbon::today()->format('Y/m/d'),
            'chk_not_ordering_id' => config('const.flg.on'),
            'chk_editing_id' => config('const.flg.on'),
            'chk_not_treated_id' => config('const.flg.on'),
        ]);
        
        return view('Order.order-list')
            ->with('matterData', $matterData)
            // ->with('quoteData', $quoteData)
            // ->with('orderData', $orderData)
            ->with('customerData', $customerData)
            ->with('departmentData', $departmentData)
            ->with('staffData', $staffData)
            ->with('staffDepartmentData', $staffDepartmentData)
            ->with('makerData', $makerData)
            ->with('supplierData', $supplierData)
            ->with('supplierMakerData', $supplierMakerData)
            ->with('initSearchParams', $initSearchParams)
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
            
            // ステータスチェックボックスがすべてチェックなしの場合、全ステータスを検索する
            if (!$params['not_ordering'] && !$params['editing'] && !$params['ordered'] && !$params['not_treated'] && !$params['reserved']) {
                $params['not_ordering'] = config('const.flg.on');
                $params['editing'] = config('const.flg.on');
                $params['ordered'] = config('const.flg.on');
                $params['not_treated'] = config('const.flg.on');
                $params['reserved'] = config('const.flg.on');
            }

            // 検索
            $Order = new Order();
            $list = $Order->getOrderList($params);

            $SystemUtil = new SystemUtil();

            // グリッド表示用整形
            $dispStatusList = config('const.orderListStatus.statusKey');
            $seqNo = 1;
            foreach ($list as $key => $val) {
                $list[$key]['seq_no'] = $seqNo;
                // 発注額(仕入額)
                if (!is_null($val['cost_total'])) {
                    $list[$key]['cost_total'] = intval($val['cost_total']);
                }
                // 販売額
                if (!is_null($val['sales_total'])) {
                    $list[$key]['sales_total'] = intval($val['sales_total']);
                }
                // 粗利率
                if (!is_null($val['profit_total'])) {
                    $list[$key]['profit_total'] = intval($val['profit_total']);
                    $list[$key]['gross_profit_rate'] = $SystemUtil->calcRate($val['profit_total'], $val['sales_total']);
                }
                // 状況
                $list[$key]['status'] = $dispStatusList[$val['disp_status']];
                // 添付ファイル
                $list[$key]['attach_file'] = '';
                // 詳細ボタン・印刷ボタン
                $list[$key]['btn_detail'] = [];
                $list[$key]['btn_print'] = [];
                if ($val['disp_status'] === config('const.orderStatus.val.approved') || $val['disp_status'] === config('const.orderStatus.val.ordered')) {
                    // 未発注・発注済の場合のみ、添付ファイル有無をチェックする
                    $dirPath = $this->OrderServiceProvider->getUploadFilePath($val['matter_id'], $val['order_id']);
                    $fileList = Storage::files($dirPath);
                    if (count($fileList) > 0) {
                        $list[$key]['attach_file'] = 'あり';
                    }
                    // 未発注・発注済の場合のみ、詳細ボタン・印刷ボタンを有効化
                    $list[$key]['btn_detail'] = ['is_valid' => true];
                    $list[$key]['btn_print'] = ['is_valid' => true];
                } else {
                    $list[$key]['btn_detail'] = ['is_valid' => false];
                    $list[$key]['btn_print'] = ['is_valid' => false];
                }

                $seqNo++;
            }

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }

    /**
     * データ更新。発注書データ取得。
     *
     * @param Request $request
     * @return void
     */
    public function getReportData(Request $request){
        $SystemUtil = new SystemUtil();

        $params = $request->request->all();
        $data = $SystemUtil->getOrderReportData($params['order_id']);
        return \Response::json($data);
    }

    /**
     * PDF保存
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function uploadPdf(Request $request)  
    {
        $result = array('status' => false, 'list_status' => null, 'message' => '');

        // リクエストから検索条件取得
        $params = $request->request->all();

        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $Matter = new Matter();
        $Quote = new Quote();
        $Reserve = new Reserve();
        $Product = new Product();
        $LockManage = new LockManage();

        try {
            // 案件完了チェック
            $matterId = (int)$params['matter_id'];
            $matterData = $Matter->getById($matterId);
            if ($matterData->complete_flg == config('const.flg.on')) {
                // 案件完了している場合、印刷処理させない
                $result['message'] = config('message.error.matter.completed');
                $result['status'] = false;
                return \Response::json($result);
            }
        }catch (\Exception $e) {
            Log::error($e);
            $result['message'] = config('message.error.error');
            $result['status'] = false;
            return \Response::json($result);
        }
        
        // ロック取得で1回コミットする
        $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
        $quoteId = (int)$params['quote_id'];
        $keys = array($quoteId);
        DB::beginTransaction();
        try {
            if ($quoteId <= 0) {
                throw new \Exception(config('message.error.error'));
            }

            $lockCnt = 0;
            foreach ($tableNameList as $i => $tableName) {
                // ロック確認
                $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                if (!$isLocked) {
                    // ロックされていない場合はロック取得
                    $lockDt = Carbon::now();
                    $LockManage->gainLock($lockDt, self::SCREEN_NAME, $tableName, $keys[$i]);
                }
                // ロックデータ取得
                $lockData = $LockManage->getLockData($tableName, $keys[$i]);
                if (!is_null($lockData) 
                    && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                    && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === Auth::user()->id) {
                        $lockCnt++;
                }
            }
            if (count($tableNameList) === $lockCnt) {
                DB::commit();
            } else {
                // 排他ロックが取得できなかった場合、ここで処理終了
                DB::rollBack();
                $result['message'] = config('message.error.getlock');
                $result['status'] = false;
                return \Response::json($result);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['message'] = config('message.error.error');
            $result['status'] = false;
            return \Response::json($result);
        }

        // ロック取得できた場合、別トランザクション開始
        DB::beginTransaction();
        try {
            $orderParams = [
                'id' => $params['order_id'],
                'order_datetime' => Carbon::now(),
                'status' => config('const.orderStatus.val.ordered'),
                'order_staff_id' => Auth::user()->id,
            ];

            $order = $Order->getById($params['order_id']); // 発注状況によって処理が変わる為、更新前に取得
            $matter = $Matter->getByMatterNo($order->matter_no);
            
            if (!$Order->updateById($orderParams)) {
                throw new \Exception(config('message.error.error'));
            }

            $orderDetail = $OrderDetail->getByOrderId($params['order_id']);     // 発注明細を取得
            $orderDetail = $orderDetail->where('product_code', '<>', '');       // 無形品除外
            $products = $Product->getByIds($orderDetail->pluck('product_id')->unique()->toArray()); // 発注明細で使用している商品を取得
            $tangibleProducts = $products->where('intangible_flg', config('const.flg.off'));        // 有形品のみ絞り込む
            if (count($tangibleProducts) > 0) {
                // 発注明細の商品を有形品のみに絞り込む
                $orderDetail = $orderDetail->whereIn('product_id', $tangibleProducts->pluck('id')->unique()->toArray());
            }

            // 当社在庫品でない & 自社倉庫 & 更新前の発注状況が『承認済』& 有形品の場合のみ
            if ($order->own_stock_flg == config('const.flg.off')
                && $order->delivery_address_kbn == config('const.deliveryAddressKbn.val.company')
                && $order->status == config('const.orderStatus.val.approved')
                && $orderDetail->count() > 0
            ) {
                // 有形品のみ引当データを作成する
                $quote = $Quote->getByQuoteNo($order->quote_no);

                $reserveData = [];
                foreach ($orderDetail as $key => $value) {
                    $rec = [];
                    $rec['stock_flg'] = config('const.stockFlg.val.order');
                    $rec['matter_id'] = $matter->id;
                    $rec['order_id'] =  $order->id;
                    $rec['order_no'] =  $order->order_no;
                    $rec['order_detail_id'] = $value->id;
                    $rec['quote_id'] = $quote->id;
                    $rec['quote_detail_id'] = $value->quote_detail_id;
                    $rec['product_id'] = $value->product_id;
                    $rec['product_code'] = $value->product_code;
                    $rec['from_warehouse_id'] = $order->delivery_address_id;
                    $rec['reserve_quantity'] = $value->stock_quantity;
                    $rec['reserve_quantity_validity'] = $rec['reserve_quantity'];
                    $rec['issue_quantity'] = 0;
                    $rec['finish_flg'] = config('const.flg.off');
                    $reserveData[] = $rec;
                }
                $Reserve->addList($reserveData);
            }
            $filePath = $this->OrderServiceProvider->getUploadFilePath($matter->id, $order->id);
            request()->file->storeAs($filePath, $order->order_no. '.pdf');

            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $keys, Auth::user()->id);
            if ($delLock) {
                $result['status'] = true;
                $result['list_status'] = config('const.orderListStatus.ordered');
                DB::commit();
            }else{
                throw new \Exception(config('message.error.save'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['message'] = config('message.error.error');
            $result['status'] = false;
        }
        
        return \Response::json($result);
    }

}