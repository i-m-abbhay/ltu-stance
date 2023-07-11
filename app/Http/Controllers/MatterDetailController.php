<?php
namespace App\Http\Controllers;

use Auth;
use DB;
use Session;
use Storage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Models\Product;
use App\Models\Address;
use App\Models\CalendarData;
use App\Models\ClassSmall;
use App\Models\Construction;
use App\Models\Customer;
use App\Models\Matter;
use App\Models\QuoteRequest;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Reserve;
use App\Models\Arrival;
use App\Models\Delivery;
use App\Models\SalesDetail;
use App\Models\Requests;
use App\Models\Returns;
use App\Models\LockManage;
use App\Models\MatterDetail;
use App\Models\MatterDetailLink;
use App\Models\MatterDetailDefault;
use App\Models\MatterDetailDefaultLink;
use App\Models\MatterRain;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\WarehouseMove;
use App\Models\Loading;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\PurchaseLineItem;
use App\Models\QuoteVersion;
use App\Models\Sales;
use App\Models\ProductstockShelf;

/**
 * 案件詳細
 */
class MatterDetailController extends Controller
{
    const SCREEN_NAME = 'matter-detail';
    const UNKNOWN_CLASS_SMALL_ID = 0;

    const SCHEDULER = [
        'DISP_ICON_CNT' => 24,
        'TYPE_TEXT' => [
            'QUOTE_REQUEST' => '見積依頼',
            'QUOTE' => '見積',
            'QUOTE_FOLLOW' => '見積フォロー',
            'ORDER_RESERVE' => '発注／引当',
            'ARRIVAL' => '入荷',
            'SHIPMENT_ORDER' => '出荷指示',
            'SHIPMENT' => '配送',
            'SALES' => '売上確定',
        ],
        'ICON' => [
            'QUOTE_REQUEST' => 'requestIcon',
            'QUOTE' => 'quoteIcon',
            'QUOTE_FOLLOW' => 'contractIcon',
            'ORDER_RESERVE' => 'orderingIcon',
            'ARRIVAL' => 'arrivalIcon',
            'SHIPMENT_ORDER' => 'shippingIcon',
            'SHIPMENT' => 'truckIcon',
            'SALES' => 'saleIcon',
        ],
        'STATUS' => [
            'FINISHED' => 'finished',
            'UNFINISHED' => 'unfinished',
            'EXCEED' => 'exceed',
        ],
        'SORT' => [
            'FIRST' => [
                'FINISHED' => 3,
                'UNFINISHED' => 2,
                'EXCEED' => 1,
            ],
            'SECOND' => [
                'QUOTE_REQUEST' => 1,
                'QUOTE' => 2,
                'QUOTE_FOLLOW' => 3,
                'ORDER_RESERVE' => 4,
                'ARRIVAL' => 5,
                'SHIPMENT_ORDER' => 6,
                'SHIPMENT' => 7,
                'SALES' => 8,
            ],
        ],
    ];

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
    public function index(Request $request, $id = null)
    {
        $matterId = (int)$id;

        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $Matter = new Matter();
        $MatterRain = new MatterRain();
        $QuoteDetail = new QuoteDetail();
        $Construction = new Construction();
        $ClassSmall = new ClassSmall();
        $LockManage = new LockManage();

        $constData = null;
        $isOwnLock = config('const.flg.off');;
        $lockData = null;
        $mainData = null;
        $matterRainData = null;
        $matterComboData = null;
        $constructionData = null;
        $classSmallData = null;
        $parentQuoteLayerNameData = null;
        $ganttHolidayList = null;
        $companyHolidayList = null;
        $quoteVerFileList = null;
        $orderFileList = null;

        if (is_null($Matter->getById($matterId))) {
            throw new NotFoundHttpException();
        }

        DB::beginTransaction();
        try {
            $mainData = $Matter->getMatterDetailData($matterId);
            $matterRainData = $MatterRain->getListByMatterId($matterId);

            // 案件コンボ
            $matterComboData = $Matter->getComboList();

            $constructionDate = new Carbon($mainData->construction_date);

            // 休日データ
            $ganttHolidayList = null;
            $companyHolidayList = null;
            $this->generateHolidayList($constructionDate, $ganttHolidayList, $companyHolidayList);

            // 工事区分マスタ
            $constructionData = $Construction->getAll();
            $constructionData = $constructionData->keyBy('id');

            // 小分類マスタ
            $classSmallData = $ClassSmall->getAll();
            $classSmallData = $classSmallData->keyBy('id');

            // 見積が存在するなら
            $parentQuoteLayerNameData = $QuoteDetail->getNearestParentLayerName($mainData->quote_no, config('const.quoteCompVersion.number'));
            $parentQuoteLayerNameData = $parentQuoteLayerNameData->keyBy('quote_detail_id');

            // 見積ファイル
            $quoteVerIdList = json_decode($mainData->quote_version_id_list);
            sort($quoteVerIdList);
            $mainData->quote_version_id_list = $quoteVerIdList;

            $quoteVerFileList = [];
            foreach ($mainData->quote_version_id_list as $key => $quoteVerId) {
                // フォルダ内のファイル全取得
                $fileList = Storage::files(config('const.uploadPath.quote_version'). $quoteVerId);
                foreach ($fileList as $file) {
                    $filePath = Storage::url($file);
                    $tmps = explode('/', $filePath);
                    $fileName = $tmps[count($tmps) - 1];
                    $quoteVerFileList[] = array('id'=> $quoteVerId, 'file_name' => $fileName);
                }
            }

            // 発注ファイル
            $orderIdList = json_decode($mainData->order_id_list);
            sort($orderIdList);
            $mainData->order_id_list = $orderIdList;

            $orderFileList = [];
            foreach ($mainData->order_id_list as $key => $orderId) {
                // フォルダ内のファイル全取得
                $fileList = Storage::files(config('const.uploadPath.order'). $mainData->matter_id. '/'. $orderId);
                foreach ($fileList as $file) {
                    $filePath = Storage::url($file);
                    $tmps = explode('/', $filePath);
                    $fileName = $tmps[count($tmps) - 1];
                    $orderFileList[] = array('id'=> $orderId, 'file_name' => $fileName);
                }
            }

            // 画面名から対象テーブルを取得
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            $keys = array($matterId);

            $lockCnt = 0;
            foreach ($tableNameList as $i => $tableName) {
                // ロック確認
                $isLocked = $LockManage->isLocked($tableName, $keys[$i]);
                // GETパラメータからモード取得
                // $mode = config('const.mode.show');
                $mode = $request->query(config('const.query.mode'));
                if (!$isLocked && $mode == config('const.mode.edit')) {
                    // 編集モードかつ、ロックされていない場合はロック取得
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
                $isOwnLock = config('const.flg.on');
            } else {
                DB::rollBack();
            }
                
            if (is_null($lockData)) {
                $lockData = $LockManage;
            }
            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        $constData = [
            'SCREEN_NAME' => self::SCREEN_NAME,
            'BASE_DATE_TYPE' => config('const.classSmallBaseDateType.val'),
            'SERVER_TASK_TYPE' => config('const.matterTaskType.val'),
        ];
        $messageData = [
            'ERROR' => config('message.error.matter_detail'),
        ];

        return view('Matter.matter-detail')
            ->with('screenName', json_encode(self::SCREEN_NAME))
            ->with('constData', json_encode($constData))
            ->with('messageData', json_encode($messageData))
            ->with('isOwnLock', $isOwnLock)
            ->with('lockData', $lockData)
            ->with('mainData', $mainData)
            ->with('matterRainData', $matterRainData)
            ->with('matterComboData', $matterComboData)
            ->with('constructionData', $constructionData)
            ->with('parentQuoteLayerNameData', $parentQuoteLayerNameData)
            ->with('ganttHolidayList', json_encode($ganttHolidayList))
            ->with('companyHolidayList', json_encode($companyHolidayList))
            ->with('quoteVerFileList', json_encode($quoteVerFileList))
            ->with('orderFileList', json_encode($orderFileList))
        ;
    }

    /**
     * 週間カレンダー（スケジューラ）のデータ取得
     *
     * @param Request $request
     * @return void
     */
    public function getSchedulerData(Request $request)
    {
        $result = ['scheduler_data' => '[]'];

        // リクエストデータ取得
        $params = $request->request->all();

        $Customer = new Customer();
        $Matter = new Matter();
        $MatterDetail = new MatterDetail();
        $QuoteRequest = new QuoteRequest();
        $Quote = new Quote();
        $QuoteVersion = new QuoteVersion();
        $QuoteDetail = new QuoteDetail();
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $Reserve = new Reserve();
        $WarehouseMove = new WarehouseMove();
        $ShipmentDetail = new ShipmentDetail();
        $Construction = new Construction();
        $SalesDetail = new SalesDetail();
        $Requests = new Requests();

        // 今日
        $today = Carbon::today();

        // 工事区分データ
        $constructionData = $Construction->getAll()->keyBy('id');

        // 案件データ
        $matter = $Matter->getById($params['matter_id']);

        // 得意先データ
        $customer = $Customer->getById($matter->customer_id);

        // 案件詳細データ
        $matterDetailData = $MatterDetail->getByMatterId($matter->id);
        $matterDetailData = $matterDetailData->keyBy('id');

        // 見積データ
        $quote = $Quote->getByMatterNo($matter->matter_no);

        $data = [];

        $addTemplate = [
            'status' => null, 'visible' => false, 'disp_date' => null,
            'type_text'=> '', 'icon' => '', 'construction_name' => '', 'process_name' => '',
            'first_sort' => 0, 'second_sort' => 0, 'third_sort' => 0, 'forth_sort' => 0,
        ];
        
        // 見積依頼
        $addList = [];
        $quoteRequestData = $QuoteRequest->getQuoteRequestByMatterNo($matter->matter_no)->sortBy('id');
        $compList = ($quote) ? $QuoteDetail->getCompEstimationListByQuoteNo($quote->quote_no):[];
        foreach ($quoteRequestData as $key => $value) {
            $quoteLimitDate = Carbon::parse($value->quote_limit_date);
            $kbnList = json_decode($value->quote_request_kbn, true);
            foreach ($kbnList as $key => $constructionId) {
                $addValue = $addTemplate;
                $addValue['visible'] = true;
                $addValue['disp_date'] = $quoteLimitDate->format('Y/m/d');
                $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['QUOTE_REQUEST'];
                $addValue['icon'] = self::SCHEDULER['ICON']['QUOTE_REQUEST'];
                $addValue['construction_name'] = $constructionData[$constructionId]->construction_name;
                $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['QUOTE_REQUEST'];
                $addValue['third_sort'] = $constructionData[$constructionId]->display_order;

                if (in_array($constructionId, $compList)) {
                    // 【済】
                    $addValue['status'] = self::SCHEDULER['STATUS']['FINISHED'];
                    $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['FINISHED'];
                }else{
                    if ($today->gt($quoteLimitDate)) {
                        // 【超過】システム日付が見積提出期限よりでかい
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                    }else{
                        // 【未済】
                        $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['UNFINISHED'];
                    }
                }

                if (isset($addList[$constructionId])) {
                    // 追加用配列側が『済』では無いなら上書き
                    if ($addList[$constructionId]['status'] != self::SCHEDULER['STATUS']['FINISHED']) {
                        $addList[$constructionId] = $addValue;
                    }
                }else{
                    $addList[$constructionId] = $addValue;
                }                
            }
        }
        foreach ($addList as $value) {
            $data[] = $value;
        }

        if ($quote) {
            // 発注データ(一時保存データは取り除く)
            $orderData = $Order->getByMatterQuote($matter->matter_no, null)->filter(function($value, $key){
                return (!empty($value['order_no']));
            });
            $orderData = $orderData->keyBy('id');

            // 発注IDリスト
            $orderIdList = $orderData->pluck('id')->toArray();

            // 受注確定データ
            $receivedData = $QuoteDetail->getReceivedOrderForMatterDetail($matter->matter_no, $quote->quote_no);
            $receivedData = $receivedData->keyBy('id');

            // 発注明細データ ※発注済以外の発注明細を除く
            $orgOrderDetailData = $OrderDetail->getByOrderIds($orderIdList)->keyBy('id');
            $orderDetailData = [];  
            foreach ($orgOrderDetailData as $value) {
                $orderId = $value->order_id;
                if ($orderData[$orderId]->status != config('const.orderStatus.val.ordered')) {
                    continue;
                }

                $orderDetailId = $value->id;
                $quoteDetailId = $value->quote_detail_id;
                if (!isset($orderDetailData[$quoteDetailId])) {
                    $orderDetailData[$quoteDetailId] = [];
                }
                $orderDetailData[$quoteDetailId][$orderDetailId] = $value;
            }

            // 引当データ
            // reserveData[見積明細ID][引当ID] => データ
            // orderReserveData[見積明細ID][発注明細ID][引当ID] => データ ※発注引当
            // stockReserveData[見積明細ID][引当ID] => データ ※在庫引当
            $orgReserveData = $Reserve->getByMatterQuote($matter->id, null)->keyBy('id');
            $reserveData = $orderReserveData = $stockReserveData = [];
            foreach ($orgReserveData as $value) {
                $reserveId = $value->id;
                $quoteDetailId = $value->quote_detail_id;
                $orderDetailId = $value->order_detail_id;

                // 見積明細ごとの引当
                if (!isset($reserveData[$quoteDetailId])) {
                    $reserveData[$quoteDetailId] = [];
                }
                $reserveData[$quoteDetailId][$reserveId] = $value;

                if ($value->stock_flg == config('const.stockFlg.val.order')) {
                    // 見積明細-発注明細ごとの引当 ※発注引当
                    if (!isset($orderReserveData[$quoteDetailId])) {
                        $orderReserveData[$quoteDetailId] = [];
                    }
                    if (!isset($orderReserveData[$quoteDetailId][$orderDetailId])) {
                        $orderReserveData[$quoteDetailId][$orderDetailId] = [];
                    }
                    $orderReserveData[$quoteDetailId][$orderDetailId][$reserveId] = $value;
                }else{
                    // 見積明細ごとの引当 ※在庫引当
                    if (!isset($stockReserveData[$quoteDetailId])) {
                        $stockReserveData[$quoteDetailId] = [];
                    }
                    $stockReserveData[$quoteDetailId][$reserveId] = $value;
                }
            }

            // 倉庫移動データ
            // warehouseMoveData[見積明細ID][倉庫移動ID] => データ
            $orgWarehouseMoveData = $WarehouseMove->getByQuoteId($quote->id)->keyBy('id');
            $warehouseMoveData = [];
            foreach ($orgWarehouseMoveData as $value) {
                $warehouseMoveId = $value->id;
                $quoteDetailId = $value->quote_detail_id;
                if (!isset($warehouseMoveData[$quoteDetailId])) {
                    $warehouseMoveData[$quoteDetailId] = [];
                }
                $warehouseMoveData[$quoteDetailId][$warehouseMoveId] = $value;
            }

            // 見積
            $addList = [];
            $quoteVersionData = $QuoteVersion->getSchedulerQuoteForMatterDetail($quote->quote_no);
            foreach ($quoteVersionData as $key => $value) {
                $constructionList = json_decode($value->construction_id_list, true);
                foreach ($constructionList as $key => $constructionId) {
                    $quoteLimitDate = Carbon::parse($value->quote_limit_date);

                    $addValue = $addTemplate;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['QUOTE'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['QUOTE'];
                    $addValue['construction_name'] = $constructionData[$constructionId]->construction_name;
                    $addValue['disp_date'] = $quoteLimitDate->format('Y/m/d');
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['QUOTE'];
                    $addValue['third_sort'] = $constructionData[$constructionId]->display_order;

                    $addListKey = $constructionId;
                    if ($value->status == config('const.quoteVersionStatus.val.approved') && !is_null($value->print_date)) {
                        // 承認済 && 印刷済
                        // 【済】
                        $addValue['status'] = self::SCHEDULER['STATUS']['FINISHED'];
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['FINISHED'];
                        $addListKey = $addListKey. '_'. $quoteLimitDate->format('Ymd');
                    }else{
                        $addValue['visible'] = true;
                        if ($today->gt($quoteLimitDate)) {
                            // 【超過】システム日付が見積提出期限よりでかい
                            $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                            $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                            $addValue['disp_date'] = $today->format('Y/m/d');
                            $addListKey = $addListKey. '_'. $today->format('Ymd');
                        }else{
                            // 【未済】
                            $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                            $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['UNFINISHED'];
                            $addListKey = $addListKey. '_'. $quoteLimitDate->format('Ymd');
                        }
                    }

                    if (isset($addList[$addListKey])) {
                        // ステータスが済 && 追加用配列に存在するステータスが未済の場合は上書き
                        // ステータスが超過 && 追加用配列に存在する区分が超過以外の場合は上書き
                        if (
                            ($addValue['status'] == self::SCHEDULER['STATUS']['FINISHED'] && $addList[$addListKey]['status'] == self::SCHEDULER['STATUS']['UNFINISHED'])
                            || ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED'])
                        ) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }
            foreach ($addList as $value) {
                $data[] = $value;
            }

            // 見積フォロー
            $quoteFollowData = $QuoteDetail->getSchedulerQuoteFollowForMatterDetail($quote->quote_no);
            $receivedConstruction = $QuoteDetail->getReceivedConstruction($quote->quote_no);
            foreach ($quoteFollowData as $key => $value) {
                $quoteCreateDate = Carbon::parse($value->quote_create_date);
                if (!in_array($value->construction_id, $receivedConstruction)) {
                    if ($today->gt($quoteCreateDate->copy()->addDay(3))) {
                        $addValue = $addTemplate;
                        $addValue['visible'] = true;
                        $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['QUOTE_FOLLOW'];
                        $addValue['icon'] = self::SCHEDULER['ICON']['QUOTE_FOLLOW'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['construction_name'] = $constructionData[$value->construction_id]->construction_name;
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                        $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['QUOTE_FOLLOW'];
                        $addValue['third_sort'] = $constructionData[$value->construction_id]->display_order;
                        $data[] = $addValue; // スケジューラ用データに直接追加
                    }
                }
            }

            // 発注／引当
            $addList = [];
            $matterReceivedData = $matterDetailData->where('type', config('const.matterTaskType.val.detail'));  // 案件詳細の受注確定明細行取得
            $matterOrderTiming = $matterDetailData->where('type', config('const.matterTaskType.val.order_timing'))->keyBy('parent');    // 発注タイミング行（キーは親ID）
            foreach ($matterReceivedData as $key => $value) {
                $quoteDetailId = $value->quote_detail_id;
                $quoteDetail = $receivedData[$quoteDetailId] ?? [];
                $stockReserves = $stockReserveData[$quoteDetailId] ?? [];
                $orderDetails = $orderDetailData[$quoteDetailId] ?? [];

                $sumStockReserveQuantity = $this->arraySumByColumn($stockReserves, 'reserve_quantity');
                $sumOrderStockQuantity = $this->arraySumByColumn($orderDetails, 'stock_quantity');
                $warehouseMoves = $warehouseMoveData[$quoteDetailId] ?? [];
                $funcSumReturnQuantity = function($warehouseMoves){
                    $quantity = 0;
                    if (count($warehouseMoves) > 0) {
                        foreach ($warehouseMoves as $value) {
                            if ($value->move_kind == config('const.moveKind.return') && $value->approval_status == config('const.returnApprovalStatus.val.approvaled')) {
                                $quantity += $value['quantity'];
                            }
                        }
                    }
                    return $quantity;
                };
                if ($quoteDetail['stock_quantity'] > ($sumStockReserveQuantity + $sumOrderStockQuantity - $funcSumReturnQuantity($warehouseMoves))) {
                    // 子に発注タイミングが無い受注確定は除外
                    if (!isset($matterOrderTiming[$value->id])) {
                        continue;
                    }
                    $addValue = $addTemplate;
                    $addValue['visible'] = true;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['ORDER_RESERVE'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['ORDER_RESERVE'];
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['ORDER_RESERVE'];
                    $addValue['third_sort'] = $constructionData[$value->construction_id]->display_order;
                    $addValue['construction_name'] = $constructionData[$value->construction_id]->construction_name;

                    $mdParent = $matterDetailData[$value->parent];
                    $orderTimingDate = Carbon::parse($matterOrderTiming[$value->id]->start_date);

                    $addListKey = $mdParent->id;
                    if ($today->gt($orderTimingDate)) {
                        // 【超過】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addListKey = $addListKey. '_'. $today->format('Ymd');
                    }else{
                        // 【未済】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['UNFINISHED'];
                        $addValue['disp_date'] = $orderTimingDate->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                        $addListKey = $addListKey. '_'. $orderTimingDate->format('Ymd');
                    }

                    if ($mdParent->type == config('const.matterTaskType.val.construction')) {
                        // 親が工事区分
                    }else{
                        // 親が工程
                        $addValue['process_name'] = $matterDetailData[$value->parent]->text;
                        $addValue['forth_sort'] = $value->id;
                    }

                    if (isset($addList[$addListKey])) {
                        // ステータスが超過 && 追加用配列に存在するステータスが超過以外の場合は上書き
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED']) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }
            foreach ($addList as $value) {
                $data[] = $value;
            }

            // 入荷
            $addList = [];
            $orderArrivalData = $Order->getSchedulerArrivalForMatterDetail($matter->matter_no);
            foreach ($orderArrivalData as $value) { 
                if ($value->arrival_completed_flg == config('const.flg.off')) {
                    $addValue = $addTemplate;
                    $addValue['visible'] = true;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['ARRIVAL'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['ARRIVAL'];
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['ARRIVAL'];
                    $addValue['third_sort'] = $constructionData[$value->construction_id]->display_order;
                    $addValue['construction_name'] = $constructionData[$value->construction_id]->construction_name;

                    $arrivalPlanDate = Carbon::parse($value->arrival_plan_date);
                    $addListKey = $value->construction_id;
                    if ($today->gt($arrivalPlanDate)) {
                        // 【超過】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addListKey = $addListKey. '_'. $today->format('Ymd');
                    }else{
                        // 【未済】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['UNFINISHED'];
                        $addValue['disp_date'] = $arrivalPlanDate->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                        $addListKey = $addListKey. '_'. $arrivalPlanDate->format('Ymd');
                    }
                    if (isset($addList[$addListKey])) {
                        // ステータスが超過 && 追加用配列に存在するステータスが超過以外の場合は上書き
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED']) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }
            foreach ($addList as $value) {
                $data[] = $value;
            }

            // 出荷指示
            $addList = [];
            $scheduleShipmentOrderData = $Reserve->getSchedulerShipmentForMatterDetail($matter->id);
            foreach ($scheduleShipmentOrderData as $key => $value) {
                if ($value->sum_reserve_quantity > $value->sum_shipment_quantity) {
                    $addValue = $addTemplate;
                    $addValue['visible'] = true;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['SHIPMENT_ORDER'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['SHIPMENT_ORDER'];
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['SHIPMENT_ORDER'];
                    $addValue['third_sort'] = $constructionData[$value->construction_id]->display_order;
                    $addValue['construction_name'] = $constructionData[$value->construction_id]->construction_name;

                    $planDate = Carbon::parse($value->plan_date);
                    $addListKey = $value->construction_id;
                    if ($today->gt($planDate)) {
                        // 【超過】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addListKey = $addListKey. '_'. $today->format('Ymd');
                    }else{
                        // 【未済】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['UNFINISHED'];
                        $addValue['disp_date'] = $planDate->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                        $addListKey = $addListKey. '_'. $planDate->format('Ymd');
                    }
                    if (isset($addList[$addListKey])) {
                        // ステータスが超過 && 追加用配列に存在するステータスが超過以外の場合は上書き
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED']) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }
            foreach ($addList as $value) {
                $data[] = $value;
            }

            // 配送
            $addList = [];
            $scheduleShipmentData = $ShipmentDetail->getSchedulerShipmentForMatterDetail($matter->id);
            foreach ($scheduleShipmentData as $value) {
                if ($value->min_delivery_finish_flg != config('const.flg.on')) {
                    $addValue = $addTemplate;
                    $addValue['visible'] = true;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['SHIPMENT'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['SHIPMENT'];
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['SHIPMENT'];
                    $addValue['third_sort'] = $constructionData[$value->construction_id]->display_order;
                    $addValue['construction_name'] = $constructionData[$value->construction_id]->construction_name;

                    $planDate = Carbon::parse($value->delivery_date);
                    $addListKey = $value->construction_id;
                    if ($today->gt($planDate)) {
                        // 【超過】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['EXCEED'];
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addListKey = $addListKey. '_'. $today->format('Ymd');
                    }else{
                        // 【未済】
                        $addValue['first_sort'] = self::SCHEDULER['SORT']['FIRST']['UNFINISHED'];
                        $addValue['disp_date'] = $planDate->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                        $addListKey = $addListKey. '_'. $planDate->format('Ymd');
                    }
                    if (isset($addList[$addListKey])) {
                        // ステータスが超過 && 追加用配列に存在するステータスが超過以外の場合は上書き
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED']) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }
            foreach ($addList as $value) {
                $data[] = $value;
            }

            // 売上
            $addList = [];

            // 売上明細テーブルに存在しない納品データ、返品データを取得
            $schedulerSalesData = $SalesDetail->getSchedulerSalesForMatterDetail($matter->id);

            // 請求データの売上期間が最新の1件を取得する
            $latestRequests = $Requests->getLatestSalesPeriodRequests($customer->id);

            if ($latestRequests) {
                // 請求データが存在する
                $nextRequestEndDate = null;
                $latestRequestEDay = Carbon::parse($latestRequests->request_e_day);
                $latestBeRequestEDay = Carbon::parse($latestRequests->be_request_e_day);

                if ($customer->closing_day == config('const.customerClosingDay.val.month_end')) {
                    // 末締
                    $nextRequestEndDate = $latestRequestEDay->copy()->addMonthNoOverflow(1)->lastOfMonth();
                }else if($customer->closing_day != config('const.customerClosingDay.val.any_time')){
                    // 指定日締め
                    $nextRequestEndDate = $latestBeRequestEDay->copy()->addMonthNoOverflow(1);
                }

                foreach ($schedulerSalesData as $key => $dateValue) {
                    $date = Carbon::parse($dateValue)->startOfDay();

                    $addValue = $addTemplate;
                    $addValue['visible'] = true;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['SALES'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['SALES'];
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['SALES'];

                    if ($date->gt($latestRequestEDay)) {
                        // 納品日 > 請求.売上期間(終了日)
                        // 随時締の場合はcontinue
                        if ($customer->closing_day == config('const.customerClosingDay.val.any_time')) { continue; }
                        if ($date->gt($nextRequestEndDate)) {
                            // 納品日 > 次回締日
                            // 超過
                            $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        }else{
                            // 次回締日 ≧ 納品日 
                            if ($today->gt($nextRequestEndDate)) {
                                // システム日付 > 次回締日 
                                // 超過
                                $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                            }else{
                                // 次回締日 ≧ システム日付
                                // 未済
                                $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                            }
                        }
                        $addValue['disp_date'] = $nextRequestEndDate->format('Y/m/d');
                        $addListKey = $nextRequestEndDate->format('Ymd');
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED']) {
                            $addValue['disp_date'] = $today->format('Y/m/d');
                            $addListKey = $today->format('Ymd');
                        }
                    }else{
                        // 請求.売上期間(終了日) ≧ 納品日
                        if ($today->gt($latestRequestEDay)) {
                            // システム日付 > 請求.売上期間(終了日) 
                            if ($latestRequests->status == config('const.requestStatus.val.unprocessed')) {
                                // 超過
                                $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                            }else{
                                // 済
                                continue;
                            }
                        }else{
                            // 請求.売上期間(終了日) ≧ システム日付 
                            if ($latestRequests->status == config('const.requestStatus.val.unprocessed')) {
                                // 未済
                                $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                            }else{
                                // 超過
                                $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                            }
                        }
                        $addValue['disp_date'] = $latestRequestEDay->format('Y/m/d');
                        $addListKey = $latestRequestEDay->format('Ymd');
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED']) {
                            $addValue['disp_date'] = $today->format('Y/m/d');
                            $addListKey = $today->format('Ymd');
                        }
                    }
                    if (isset($addList[$addListKey])) {
                        // ステータスが超過 && 追加用配列に存在する区分が超過以外の場合は上書き
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED']) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }else if($customer->closing_day != config('const.customerClosingDay.val.any_time')){
                // 請求データが存在しない && 随時締でない
                $nextRequestEndDate = null;
                $prevRequestEndDate = null;
                if ($customer->closing_day == config('const.customerClosingDay.val.month_end')) {
                    // 末締
                    $nextRequestEndDate = $today->copy()->lastOfMonth(); // 月末をセット   
                    $prevRequestEndDate = $nextRequestEndDate->copy()->subMonthNoOverflow(1)->lastOfMonth(); // 月末をセット                 
                }else{
                    // 指定日締め
                    $nextRequestEndDate = Carbon::parse($today->year. '-'. $today->month. '-'. $customer->closing_day);
                    // 当日（日）が締日より大きい場合は、1ヵ月後をセット
                    if ($today->day > $customer->closing_day) {
                        $nextRequestEndDate->addMonthNoOverflow(1);
                    }
                    $prevRequestEndDate = $nextRequestEndDate->copy()->subMonthNoOverflow(1);
                }
                foreach ($schedulerSalesData as $key => $dateValue) {
                    $date = Carbon::parse($dateValue)->startOfDay();

                    $addValue = $addTemplate;
                    $addValue['visible'] = true;
                    $addValue['type_text'] = self::SCHEDULER['TYPE_TEXT']['SALES'];
                    $addValue['icon'] = self::SCHEDULER['ICON']['SALES'];
                    $addValue['second_sort'] = self::SCHEDULER['SORT']['SECOND']['SALES'];

                    if ($date->gt($prevRequestEndDate)) {
                        // 納品日 ＞ 前回締日
                        // 未済
                        $addValue['disp_date'] = $nextRequestEndDate->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['UNFINISHED'];
                        $addListKey = $nextRequestEndDate->format('Ymd');
                    }else{
                        // 前回締日 ≧ 納品日
                        // 超過
                        $addValue['disp_date'] = $today->format('Y/m/d');
                        $addValue['status'] = self::SCHEDULER['STATUS']['EXCEED'];
                        $addListKey = $today->format('Ymd');
                    }
                    if (isset($addList[$addListKey])) {
                        // ステータスが超過 && 追加用配列に存在する区分が超過以外の場合は上書き
                        if ($addValue['status'] == self::SCHEDULER['STATUS']['EXCEED'] && $addList[$addListKey]['status'] != self::SCHEDULER['STATUS']['EXCEED']) {
                            $addList[$addListKey] = $addValue;
                        }
                    }else{
                        $addList[$addListKey] = $addValue;
                    }
                }
            }
            foreach ($addList as $value) {
                $data[] = $value;
            }
        }

        $data = $this->generateSchedulerData($data);

        $result['scheduler_data'] = json_encode($data);

        return $result;
    }

    /**
     * 工程表（ガントチャート）のデータ取得
     *
     * @param Request $request
     * @return void
     */
    public function getIndexGantt(Request $request)
    {
        $result = [
            'next_id' => 1, 'tasks' => '[]', 'links' => '[]', 'works' => null
        ];

        $Matter = new Matter();
        $MatterDetail = new MatterDetail();
        $MatterDetailLink = new MatterDetailLink();
        $Quote = new Quote();
        $QuoteDetail = new QuoteDetail();
        $Product = new Product();
        $ClassSmall = new ClassSmall();

        // リクエストデータ取得
        $params = $request->request->all();

        $matterId = $params['matter_id'];
        $matter = $Matter->getById($matterId);
        $quote = $Quote->getByMatterNo($matter->matter_no);

        $matterDetails = $MatterDetail->getByMatterId($matterId);
        if (count($matterDetails) > 0) {
            $classSmallData = $ClassSmall->getAll();
            $classSmallData = $classSmallData->keyBy('id');
    
            $receivedForProcess = $receivedForConstruction = $workData = [];
            if ($quote) {
                // 受注確定データ
                $receivedData = $QuoteDetail->getReceivedOrderForMatterDetail($matter->matter_no, $quote->quote_no);
                $receivedData = $receivedData->keyBy('id');

                // 受注確定データで使用している商品データを取得
                $productData = $Product->getByIds($receivedData->pluck('product_id')->unique()->toArray());
                $productData = $productData->keyBy('id');

                // 作業（アイコン）データ作成
                $workData = $this->generateWorkData($matterId, $receivedData, $productData);
                $result['works'] = json_encode($workData);

                // receivedForProcess[工事区分ID][小分類ID][見積明細ID] => データ
                // receivedForConstruction[工事区分ID][見積明細ID] => データ
                $receivedForProcess = $receivedForConstruction = [];
                $receivedData = $receivedData->where('matter_detail_exists', config('const.flg.off'));  // 案件詳細に存在しないデータを抽出
                foreach ($receivedData as $quoteDetailId => $value) {
                    $classSmallId = self::UNKNOWN_CLASS_SMALL_ID;
                    $tmpProduct = $productData[$value->product_id] ?? null;
        
                    // 商品マスタに存在する
                    if ($tmpProduct) {
                        // 商品マスタで小分類IDが『0 Or null』の場合は0をセット
                        switch ($value->construction_id) {
                            case $productData[$value->product_id]->construction_id_1:
                                $classSmallId = $classSmallData[$tmpProduct->class_small_id_1]->id ?? $classSmallId;
                                break;
                            case $productData[$value->product_id]->construction_id_2:
                                $classSmallId = $classSmallData[$tmpProduct->class_small_id_2]->id ?? $classSmallId;
                                break;
                            case $productData[$value->product_id]->construction_id_3:
                                $classSmallId = $classSmallData[$tmpProduct->class_small_id_3]->id ?? $classSmallId;
                                break;
                            default:
                                break;
                        }
                    }
                    $newValue = [
                        'id' => $value->id,
                        'product_name' => $value->product_name, 
                    ];
                    if ($classSmallId == self::UNKNOWN_CLASS_SMALL_ID) {
                        if (!isset($receivedForConstruction[$value->construction_id])) {
                            $receivedForConstruction[$value->construction_id] = [];
                        }
                        $receivedForConstruction[$value->construction_id][$quoteDetailId] = $newValue;
                    }else{
                        if (!isset($receivedForProcess[$classSmallId])) {
                            $receivedForProcess[$classSmallId] = [];
                        }
                        $receivedForProcess[$classSmallId][$quoteDetailId] = $newValue;
                    }
                }
            }

            $resMatterDetails = [];
            $id = 1;
            $linkIdList = [];   // [案件詳細ID] => 新ID
            $constructionIdList = $classSmallIdList = [];   // [工事区分ID] => 新ID, [小分類ID] => 新ID
            $mergedConstructionLayer = $mergedProcessLayer = false;
            for ($i=0; $i<count($matterDetails); $i++) { 
                $value = $matterDetails[$i];

                // [除外データ１]
                //   案件詳細テーブルに『種別=発注タイミング』のデータが存在する
                //  『《発注／引当》【未済】【超過】』アイコンの表示条件に該当するデータが存在しない
                // [除外データ２]
                //   案件詳細テーブルに『種別=希望出荷予定日』のデータが存在する
                //  『【出荷指示 未済 Or 超過】※在庫引当』アイコンの表示条件に該当するデータが存在しない
                if (count($workData) > 0 
                    && ($value['type'] == config('const.matterTaskType.val.order_timing') || $value['type'] == config('const.matterTaskType.val.hope_arrival_plan_date'))
                ) {
                    $isFind = false;
                    foreach ($workData[$value['quote_detail_id']] as $workValue) {
                        if ($value['type'] == $workValue['type']) {
                            $isFind=true;break;
                        }
                    }
                    if (!$isFind) {
                        continue;
                    }
                }

                $linkIdList[$value['id']] = $id;

                $newRec = $value;
                $newRec['id'] = $id;
                $newRec['parent'] = $linkIdList[$value['parent']] ?? 0;
                $resMatterDetails[] = $newRec;

                // 種別 == 工事区分
                if ($value['type'] == config('const.matterTaskType.val.construction')) {
                    $constructionIdList[$value['construction_id']] = $id;
                    $mergedConstructionLayer = false;
                }
                // 種別 == 工程
                if ($value['type'] == config('const.matterTaskType.val.process')) {
                    $classSmallIdList[$value['class_small_id']] = $id;
                    $mergedProcessLayer = false;
                }
                $id++;

                // 種別 != マイルストーン
                if ($value['type'] != config('const.matterTaskType.val.milestone')) {
                    $nextValue = $matterDetails[$i+1] ?? null;

                    // 工程未分類の受注確定データ
                    // データが存在しない場合はマージ済扱い
                    if (!isset($receivedForConstruction[$value['construction_id']])) {
                        $mergedConstructionLayer = true;
                    }
                    // 未マージ && (次のデータが工事区分 Or 工程)
                    if (!$mergedConstructionLayer && ($nextValue['type'] == config('const.matterTaskType.val.construction') || $nextValue['type'] == config('const.matterTaskType.val.process'))) {
                        $receives = $receivedForConstruction[$value['construction_id']];
                        foreach ($receives as $receiveValue) {
                            $newValue = [];
                            $newValue['id'] = $id++;
                            $newValue['type'] = config('const.matterTaskType.val.detail');
                            $newValue['construction_id'] = $value['construction_id'];
                            $newValue['class_small_id'] = self::UNKNOWN_CLASS_SMALL_ID;
                            $newValue['quote_detail_id'] = $receiveValue['id'];
                            $newValue['text'] = $receiveValue['product_name'];
                            $newValue['parent'] = $constructionIdList[$value['construction_id']];
                            $resMatterDetails[] = $newValue;
                        }
                        $mergedConstructionLayer = true;
                    }

                    // 工程分類済の受注確定データ
                    // データが存在しない場合はマージ済扱い
                    if (!isset($receivedForProcess[$value['class_small_id']])) {
                        $mergedProcessLayer = true;
                    }
                    // 未マージ && (次のデータが工事区分 Or 工程)
                    if (!$mergedProcessLayer && ($nextValue['type'] == config('const.matterTaskType.val.construction') || $nextValue['type'] == config('const.matterTaskType.val.process'))) {
                        $receives = $receivedForProcess[$value['class_small_id']];
                        foreach ($receives as $receiveValue) {
                            $newValue = [];
                            $newValue['id'] = $id++;
                            $newValue['type'] = config('const.matterTaskType.val.detail');
                            $newValue['construction_id'] = $value['construction_id'];
                            $newValue['class_small_id'] = $value['class_small_id'];
                            $newValue['quote_detail_id'] = $receiveValue['id'];
                            $newValue['text'] = $receiveValue['product_name'];
                            $newValue['parent'] = $classSmallIdList[$value['class_small_id']];
                            $resMatterDetails[] = $newValue;
                        }
                        $mergedProcessLayer = true;
                    }
                }
            }
            $result['next_id'] = $id;

            $matterDetailLinks = $MatterDetailLink->getByMatterId($matterId);
            $resMatterDetailLinks = $matterDetailLinks->map(function($item, $key) use($linkIdList){
                return [
                    'type' => $item->type,
                    'source' => $linkIdList[$item->source],
                    'target' => $linkIdList[$item->target],
                ];
            });
            
            $result['tasks'] = json_encode($resMatterDetails);
            $result['links'] = json_encode($resMatterDetailLinks);
        }

        $result['status'] = true;
        return $result;
    }

    /**
     * 工程表（ガントチャート）のデータ取得
     *
     * @param Request $request
     * @return void
     */
    public function initGanttData(Request $request)
    {
        $result = ['construction_received_data' => '[]', 'class_small_received_data' => '[]', 'work_data' => '[]'];
        $result['status'] = true;

        $Product = new Product();
        $Matter = new Matter();
        $Quote = new Quote();
        $QuoteDetail = new QuoteDetail();
        $Construction = new Construction();
        $ClassSmall = new ClassSmall();

        // リクエストデータ取得
        $params = $request->request->all();

        $constructionDate = new Carbon($params['construction_date']);   // 着工日
        $matter = $Matter->getById($params['matter_id']);       // 案件データ
        $quote = $Quote->getByMatterNo($matter->matter_no);     // 見積データ

        // 休日データ
        $ganttHolidayList = null;
        $companyHolidayList = null;
        $this->generateHolidayList($constructionDate, $ganttHolidayList, $companyHolidayList);

        // 工事区分データ
        $constructionData = $Construction->getAll();
        $constructionData = $constructionData->where('add_flg', '=', config('const.flg.off'))
                                                ->sortBy('display_order')->keyBy('id');

        $resConstructionData = $constructionData->pluck('id')->toArray();

        // 小分類データ(key:工事区分ID ⇒ 小分類ID ⇒ 小分類データ)
        $classSmallData = $ClassSmall->getAll();
        $classSmallData = $classSmallData->keyBy('id');

        // resClassSmallData[工事区分ID][小分類ID] => データ
        // resBaseClassSmallData[工事区分ID][小分類ID] => データ ※基準日種別が着工日 or 上棟日
        $resClassSmallData = $resBaseClassSmallData = [];
        foreach ($classSmallData as $key => $rowValue) {
            $resClassSmallData[$rowValue->id] = $rowValue;
            if (!empty($rowValue->base_date_type)) {
                if (!isset($resBaseClassSmallData[$rowValue->construction_id])) {
                    $resBaseClassSmallData[$rowValue->construction_id] = [];
                }
                $resBaseClassSmallData[$rowValue->construction_id][$rowValue->id] = $rowValue;
            }
        }

        // 小分類のフローデータ
        // classSmallPair[工事区分ID][小分類ID] => [小分類ID, 小分類ID, ...]
        $resClassSmallPair = [];
        foreach ($classSmallData as $value) {
            $childClassSmallData = $classSmallData->where('base_class_small_id', $value->id);
            if (count($childClassSmallData) == 0) {
                continue;
            }
            if (!isset($resClassSmallPair[$value->id])) {
                $resClassSmallPair[$value->id] = [];
            }
            foreach ($childClassSmallData as $childValue) {
                $resClassSmallPair[$value->id][] = $childValue->id;
            }
        }

        if ($quote) {
            // 受注確定データ
            $receivedData = $QuoteDetail->getReceivedOrderForMatterDetail($matter->matter_no, $quote->quote_no);
            $receivedData = $receivedData->keyBy('id');

            // 受注確定データで使用している商品データを取得
            $productData = $Product->getByIds($receivedData->pluck('product_id')->unique()->toArray());
            $productData = $productData->keyBy('id');

            // resReceivedData[工事区分ID][小分類ID][見積明細ID] => データ
            $resConstructionReceivedData = $resClassSmallReceivedData = [];
            foreach ($receivedData as $quoteDetailId => $value) {
                $classSmallId = 0;
                $tmpProduct = $productData[$value->product_id] ?? null;

                // 商品マスタに存在する
                if ($tmpProduct) {
                    // 商品マスタで小分類IDが『0 Or null』の場合は0をセット
                    switch ($value->construction_id) {
                        case $productData[$value->product_id]->construction_id_1:
                            $classSmallId = $classSmallData[$tmpProduct->class_small_id_1]->id ?? $classSmallId;
                            break;
                        case $productData[$value->product_id]->construction_id_2:
                            $classSmallId = $classSmallData[$tmpProduct->class_small_id_2]->id ?? $classSmallId;
                            break;
                        case $productData[$value->product_id]->construction_id_3:
                            $classSmallId = $classSmallData[$tmpProduct->class_small_id_3]->id ?? $classSmallId;
                            break;
                        default:
                            break;
                    }
                }
                if ($classSmallId == 0) {
                    if (!isset($resConstructionReceivedData[$value->construction_id])) {
                        $resConstructionReceivedData[$value->construction_id] = [];
                    }
                    $resConstructionReceivedData[$value->construction_id][$quoteDetailId] = $value;
                }else{
                    if (!isset($resClassSmallReceivedData[$classSmallId])) {
                        $resClassSmallReceivedData[$classSmallId] = [];
                    }
                    $resClassSmallReceivedData[$classSmallId][$quoteDetailId] = $value;
                }
            }
            $result['construction_received_data'] = json_encode($resConstructionReceivedData);
            $result['class_small_received_data'] = json_encode($resClassSmallReceivedData);
    
            $resWorkData = $this->generateWorkData($matter->id, $receivedData, $productData);
            $result['work_data'] = json_encode($resWorkData);
        }

        $result['gantt_holiday_list'] = json_encode($ganttHolidayList);
        $result['company_holiday_list'] = json_encode($companyHolidayList);
        $result['construction_data'] = json_encode($resConstructionData);
        $result['class_small_data'] = json_encode($resClassSmallData);
        $result['base_class_small_data'] = json_encode($resBaseClassSmallData);
        $result['class_small_pair'] = json_encode($resClassSmallPair);
        return \Response::json($result);
    }

    /**
     * 工程表（ガントチャート）のデータ取得
     *
     * @param Request $request
     * @return void
     */
    public function save(Request $request)
    {
        $result = ['status' => true, 'msg' => ''];

        // リクエストデータ取得
        $params = $request->request->all();

        $matterId = $params['matter_id'];
        $constructionDate = $params['construction_date'];
        $ridgepoleRaisingDate = $params['ridgepole_raising_date'];
        $tasks = json_decode($params['tasks'], true);
        $links = json_decode($params['links'], true);

        $Matter = new Matter();
        $MatterDetail = new MatterDetail();
        $MatterDetailLink = new MatterDetailLink();

        DB::beginTransaction();
        try {
            $matter = $Matter->getById($matterId);

            $matterParamas = [
                'id' => $matterId,
                'construction_date' => $constructionDate,
                'ridgepole_raising_date' => $ridgepoleRaisingDate,
            ];

            // 案件更新
            $Matter->updateByIdEx($matterParamas);

            // 案件詳細,案件詳細リンク削除
            $MatterDetail->physicalDeleteByMatterId($matter->id);
            $MatterDetailLink->physicalDeleteByMatterId($matter->id);

            // 案件詳細登録
            $matterDetailParams = [];
            foreach ($tasks as $key => $value) {
                $rec = [];
                $rec['matter_id'] = $matter->id;
                $rec['matter_no'] = $matter->matter_no;
                $rec['type'] = $value['type'];
                $rec['add_flg'] = $value['add_flg'];
                $rec['construction_id'] = $value['construction_id'] ?? 0;
                $rec['class_small_id'] = $value['class_small_id'] ?? 0;
                $rec['quote_detail_id'] = $value['quote_detail_id'] ?? 0;
                $rec['text'] = $value['text'];
                $rec['duration'] = $value['duration'];
                $rec['base_date_type'] = $value['base_date_type'];
                $rec['start_date'] = $value['start_date'];
                $rec['sortorder'] = $value['sortorder'];
                $rec['construction_period_days'] = Carbon::parse($value['end_date'])->diffInDays($value['start_date']);
                $rec['date_calc_flg'] = $value['date_calc_flg'];
                $rec['start_date_calc_days'] = $value['start_date_calc_days'];
                $rec['order_timing'] = $value['order_timing'];
                $rec['rain_flg'] = $value['rain_flg'];
                $rec['hidden_flg'] = config('const.flg.off');
                $matterDetailParams[] = $rec;
            }
            $MatterDetail->addList($matterDetailParams);

            $matterDetail = $MatterDetail->getByMatterId($matter->id);
            $addedMatterDetail = $matterDetail->sortBy('id')->toArray();

            // リスト作成
            // [フロント側ID => DB側ID, ...]
            $realTaskIds = [];
            for ($i=0; $i < count($addedMatterDetail); $i++) {
                $realTaskIds[$tasks[$i]['id']] = $addedMatterDetail[$i]['id'];
            }

            // 配列⇒連想配列
            $tasks = array_column($tasks, null, 'id');

            // 案件詳細の親IDを更新（t_matter_detail.parent）
            foreach ($tasks as $id => $taskValue) {
                $tasks[$id]['matter_detail_id'] = $realTaskIds[$taskValue['id']];
                $tasks[$id]['matter_detail_parent_id'] = $realTaskIds[$taskValue['parent']] ?? null;
                if ($tasks[$id]['matter_detail_parent_id']) {
                    $MatterDetail->updateById(['id'=> $tasks[$id]['matter_detail_id'], 'parent' => $tasks[$id]['matter_detail_parent_id']]);
                }
            }

            // 案件詳細リンク登録
            $matterDetailLinkParams = [];
            foreach ($links as $key => $linkValue) {
                $rec = [];
                $rec['matter_id'] = $matter->id;
                $rec['matter_no'] = $matter->matter_no;
                $rec['type'] = $linkValue['type'];
                $rec['source'] = $realTaskIds[$linkValue['source']];
                $rec['target'] = $realTaskIds[$linkValue['target']];
                $matterDetailLinkParams[] = $rec;
            }
            $MatterDetailLink->addList($matterDetailLinkParams);

            if(!$this->isOwnLocked(self::SCREEN_NAME, $matterId)){
                $result['msg'] = config('message.error.getlock');
                throw new \Exception();
            }

            DB::commit();
            $result['msg'] = config('message.success.save');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['status'] = false;
        }

        return \Response::json($result);
    }
    /**
     * 得意先標準登録
     *
     * @param Request $request
     * @return void
     */
    public function saveCustomerStandard(Request $request){
        $result = ['status' => true, 'msg' => ''];

        // リクエストデータ取得
        $params = $request->request->all();

        $matterId = $params['matter_id'];
        $tasks = json_decode($params['tasks'], true);
        $links = json_decode($params['links'], true);

        $Matter = new Matter();
        $MatterDetailDefault = new MatterDetailDefault();
        $MatterDetailDefaultLink = new MatterDetailDefaultLink();

        DB::beginTransaction();
        try {
            $matter = $Matter->getById($matterId);

            // 案件詳細,案件詳細リンク削除
            $MatterDetailDefault->physicalDeleteByCustomerId($matter->customer_id);
            $MatterDetailDefaultLink->physicalDeleteByCustomerId($matter->customer_id);

            // 案件詳細登録
            $matterDetailParams = [];
            foreach ($tasks as $key => $value) {
                $rec = [];
                $rec['customer_id'] = $matter->customer_id;
                $rec['type'] = $value['type'];
                $rec['construction_id'] = $value['construction_id'] ?? 0;
                $rec['class_small_id'] = $value['class_small_id'] ?? 0;
                $rec['text'] = $value['text'];
                $rec['duration'] = $value['duration'];
                $rec['sortorder'] = $value['sortorder'];
                $rec['date_calc_flg'] = $value['date_calc_flg'];
                $rec['start_date_calc_days'] = $value['start_date_calc_days'];
                $rec['order_timing'] = $value['order_timing'];
                $rec['rain_flg'] = $value['rain_flg'];
                $rec['base_date_type'] = $value['base_date_type'];
                $rec['hidden_flg'] = config('const.flg.off');
                $matterDetailParams[] = $rec;
            }
            $MatterDetailDefault->addList($matterDetailParams);

            $matterDetail = $MatterDetailDefault->getByCustomerId($matter->customer_id);
            $addedMatterDetail = $matterDetail->sortBy('id')->toArray();

            // リスト作成
            // [フロント側ID => DB側ID, ...]
            $realTaskIds = [];
            for ($i=0; $i < count($addedMatterDetail); $i++) {
                $realTaskIds[$tasks[$i]['id']] = $addedMatterDetail[$i]['id'];
            }

            // 配列⇒連想配列
            $tasks = array_column($tasks, null, 'id');

            // 案件詳細の親IDを更新（t_matter_detail.parent）
            foreach ($tasks as $id => $taskValue) {
                $tasks[$id]['matter_detail_id'] = $realTaskIds[$taskValue['id']];
                $tasks[$id]['matter_detail_parent_id'] = $realTaskIds[$taskValue['parent']] ?? null;
                if ($tasks[$id]['matter_detail_parent_id']) {
                    $MatterDetailDefault->updateById(['id'=> $tasks[$id]['matter_detail_id'], 'parent' => $tasks[$id]['matter_detail_parent_id']]);
                }
            }

            // 案件詳細リンク登録
            $matterDetailLinkParams = [];
            foreach ($links as $key => $linkValue) {
                $rec = [];
                $rec['customer_id'] = $matter->customer_id;
                $rec['type'] = $linkValue['type'];
                $rec['source'] = $realTaskIds[$linkValue['source']];
                $rec['target'] = $realTaskIds[$linkValue['target']];
                $matterDetailLinkParams[] = $rec;
            }
            $MatterDetailDefaultLink->addList($matterDetailLinkParams);

            if(!$this->isOwnLocked(self::SCREEN_NAME, $matterId)){
                $result['msg'] = config('message.error.getlock');
                throw new \Exception();
            }

            DB::commit();
            $result['msg'] = config('message.success.save');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['status'] = false;
        }

        return \Response::json($result);
    }
    /**
     * チャートデータ呼出
     *
     * @param Request $request
     * @return void
     */
    public function getChartData(Request $request){
        $result = [
            'next_id' => 1, 'tasks' => '[]', 'links' => '[]', 'works' => null
        ];

        // リクエストデータ取得
        $params = $request->request->all();

        $ClassSmall = new ClassSmall();
        $Product = new Product();
        $Matter = new Matter();
        $MatterDetail = new MatterDetail();
        $MatterDetailLink = new MatterDetailLink();
        $MatterDetailDefault = new MatterDetailDefault();
        $MatterDetailDefaultLink = new MatterDetailDefaultLink();
        $Quote = new Quote();
        $QuoteDetail = new QuoteDetail();

        $matterId = $params['matter_id'];
        $callMatterId = $params['call_matter_id'];
        $matter = $Matter->getById($matterId);
        $quote = $Quote->getByMatterNo($matter->matter_no);

        $matterDetails = null;
        $matterDetailLinks = null;
        if ($callMatterId) {
            $matterDetails = $MatterDetail->getByMatterId($callMatterId);
            // 工事区分と工程のみに絞る
            $matterDetails = $matterDetails->filter(function($item, $key){
                return (
                    $item->type == config('const.matterTaskType.val.construction')
                    || $item->type == config('const.matterTaskType.val.process')
                );
            });
            $idList = $matterDetails->pluck('id')->toArray();
            $matterDetailLinks = $MatterDetailLink->getByMatterId($callMatterId);
            $matterDetailLinks = $matterDetailLinks->filter(function($item, $key) use($idList){
                return (in_array($item->source, $idList));
            })->values();
        }else{
            $matterDetails = $MatterDetailDefault->getByCustomerId($matter->customer_id);
            $matterDetailLinks = $MatterDetailDefaultLink->getByCustomerId($matter->customer_id);
        }

        if (count($matterDetails)) {
            // 小分類データ
            $classSmallData = $ClassSmall->getAll();
            $classSmallData = $classSmallData->keyBy('id');
    
            $receivedForProcess = $receivedForConstruction = [];
            if ($quote) {
                // 受注確定データ
                $receivedData = $QuoteDetail->getReceivedOrderForMatterDetail($matter->matter_no, $quote->quote_no);
                $receivedData = $receivedData->keyBy('id');
        
                // 受注確定データで使用している商品データを取得
                $productData = $Product->getByIds($receivedData->pluck('product_id')->unique()->toArray());
                $productData = $productData->keyBy('id');
        
                // 作業（アイコン）データ作成
                $workData = $this->generateWorkData($matterId, $receivedData, $productData);
                $result['works'] = json_encode($workData);
        
                // receivedForProcess[工事区分ID][小分類ID][見積明細ID] => データ
                // receivedForConstruction[工事区分ID][見積明細ID] => データ
                $receivedForProcess = $receivedForConstruction = [];
                foreach ($receivedData as $quoteDetailId => $value) {
                    $classSmallId = self::UNKNOWN_CLASS_SMALL_ID;
                    $tmpProduct = $productData[$value->product_id] ?? null;
        
                    // 商品マスタに存在する
                    if ($tmpProduct) {
                        // 商品マスタで小分類IDが『0 Or null』の場合は0をセット
                        switch ($value->construction_id) {
                            case $productData[$value->product_id]->construction_id_1:
                                $classSmallId = $classSmallData[$tmpProduct->class_small_id_1]->id ?? $classSmallId;
                                break;
                            case $productData[$value->product_id]->construction_id_2:
                                $classSmallId = $classSmallData[$tmpProduct->class_small_id_2]->id ?? $classSmallId;
                                break;
                            case $productData[$value->product_id]->construction_id_3:
                                $classSmallId = $classSmallData[$tmpProduct->class_small_id_3]->id ?? $classSmallId;
                                break;
                            default:
                                break;
                        }
                    }
                    $newValue = [
                        'id' => $value->id,
                        'product_name' => $value->product_name, 
                    ];
                    if ($classSmallId == self::UNKNOWN_CLASS_SMALL_ID) {
                        if (!isset($receivedForConstruction[$value->construction_id])) {
                            $receivedForConstruction[$value->construction_id] = [];
                        }
                        $receivedForConstruction[$value->construction_id][$quoteDetailId] = $newValue;
                    }else{
                        if (!isset($receivedForProcess[$classSmallId])) {
                            $receivedForProcess[$classSmallId] = [];
                        }
                        $receivedForProcess[$classSmallId][$quoteDetailId] = $newValue;
                    }
                }
            }

            $resMatterDetails = [];
            $id = 1;
            $linkIdList = [];   // [案件詳細ID] => 新ID
            $constructionIdList = $classSmallIdList = [];   // [工事区分ID] => 新ID, [小分類ID] => 新ID
            $mergedConstructionLayer = $mergedProcessLayer = false;
            foreach ($matterDetails as $value) {
                $linkIdList[$value['id']] = $id;

                $newRec = $value;
                $newRec['id'] = $id;
                $newRec['parent'] = $linkIdList[$value['parent']] ?? 0;
                $resMatterDetails[] = $newRec;

                // 種別 == 工事区分 && 工程未分類の受注確定データが存在
                if ($value['type'] == config('const.matterTaskType.val.construction')) {
                    $constructionIdList[$value['construction_id']] = $id;
                    $mergedConstructionLayer = false;
                }
                // 種別 == 工程
                if ($value['type'] == config('const.matterTaskType.val.process')) {
                    $classSmallIdList[$value['class_small_id']] = $id;
                    $mergedProcessLayer = false;
                }
                $id++;

                // 工程未分類の受注確定データ
                // 未マージ && データが存在する
                if (!$mergedConstructionLayer && isset($receivedForConstruction[$value['construction_id']])) {
                    $receives = $receivedForConstruction[$value['construction_id']];
                    foreach ($receives as $receiveValue) {
                        $newValue = [];
                        $newValue['id'] = $id++;
                        $newValue['type'] = config('const.matterTaskType.val.detail');
                        $newValue['construction_id'] = $value['construction_id'];
                        $newValue['class_small_id'] = self::UNKNOWN_CLASS_SMALL_ID;
                        $newValue['quote_detail_id'] = $receiveValue['id'];
                        $newValue['text'] = $receiveValue['product_name'];
                        $newValue['parent'] = $constructionIdList[$value['construction_id']];
                        $resMatterDetails[] = $newValue;
                    }
                    $mergedConstructionLayer = true;
                }

                // 工程分類済の受注確定データ
                // 未マージ && データが存在する
                if (!$mergedProcessLayer && isset($receivedForProcess[$value['class_small_id']])) {
                    $receives = $receivedForProcess[$value['class_small_id']];
                    foreach ($receives as $receiveValue) {
                        $newValue = [];
                        $newValue['id'] = $id++;
                        $newValue['type'] = config('const.matterTaskType.val.detail');
                        $newValue['construction_id'] = $value['construction_id'];
                        $newValue['class_small_id'] = $value['class_small_id'];
                        $newValue['quote_detail_id'] = $receiveValue['id'];
                        $newValue['text'] = $receiveValue['product_name'];
                        $newValue['parent'] = $classSmallIdList[$value['class_small_id']];
                        $resMatterDetails[] = $newValue;
                    }
                    $mergedProcessLayer = true;
                }
            }
            $result['next_id'] = $id;

            $resMatterDetailLinks = $matterDetailLinks->map(function($item, $key) use($linkIdList){
                return [
                    'type' => $item->type,
                    'source' => $linkIdList[$item->source],
                    'target' => $linkIdList[$item->target],
                ];
            });
            
            $result['tasks'] = json_encode($resMatterDetails);
            $result['links'] = json_encode($resMatterDetailLinks);
        }
        $result['status'] = true;
        return $result;
    }

    /**
     * 案件削除
     *
     * @param Request $request
     * @return void
     */
    public function delete(Request $request){
        $result = ['status' => true, 'msg' => ''];

        // リクエストデータ取得
        $params = $request->request->all();
        $matterId = $params['matter_id'];

        $Matter = new Matter();
        $QuoteRequest = new QuoteRequest();
        $Quote = new Quote();
        $Sales = new Sales();
        $PurchaseLineItem = new PurchaseLineItem();

        DB::beginTransaction();
        try {
            $matter = $Matter->getById($matterId);

            $quoteRequests = $QuoteRequest->getQuoteRequestByMatterNo($matter->matter_no);
            if (count($quoteRequests) > 0) {
                $result['msg'] = config('message.error.matter_detail.delete_quote_request');
                throw new \Exception();
            }
    
            $quote = $Quote->getByMatterNo($matter->matter_no);
            if ($quote) {
                $result['msg'] = config('message.error.matter_detail.delete_quote');
                throw new \Exception();
            }
    
            $sales = $Sales->getByMatterId($matter->id);
            if (count($sales) > 0) {
                $result['msg'] = config('message.error.matter_detail.delete_sales');
                throw new \Exception();
            }
    
            $purchaseLineItem = $PurchaseLineItem->getByMatterId($matter->id);
            if (count($purchaseLineItem) > 0) {
                $result['msg'] = config('message.error.matter_detail.delete_purchase_line_item');
                throw new \Exception();
            }
    
            $Matter->deleteById($matter->id);

            if(!$this->isOwnLocked(self::SCREEN_NAME, $matterId)){
                $result['msg'] = config('message.error.getlock');
                throw new \Exception();
            }

            $this->unlock(self::SCREEN_NAME, $matterId);    // ロック解放

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['status'] = false;
        }
        return $result;
    }

    /**
     * 案件完了
     *
     * @param Request $request
     * @return void
     */
    public function complete(Request $request){
        $result = ['status' => false, 'msg' => ''];

        // リクエストデータ取得
        $params = $request->request->all();
        $matterId = $params['matter_id'];

        $SystemUtil = new SystemUtil();
        $Matter = new Matter();
        $LockManage = new LockManage();

        DB::beginTransaction();
        try {
            $matter = $Matter->getById($matterId);
            if (is_null($matter)) {
                throw new \Exception(config('message.error.error').':'.$matterId);
            }
            
            // ロック確認
            $lockKeys = array($matter->id);
            $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
            if(!$LockManage->isOwnLocks(self::SCREEN_NAME, $tableNameList, $lockKeys)){
                $result['status'] = false;
                $result['msg'] = config('message.error.getlock');
                throw new \Exception(config('message.error.getlock').':'.$matterId);
            }

            // 案件完了
            $result = $SystemUtil->matterComplete($matterId);
            
            // ロック解放
            $delLock = $LockManage->deleteLockInfo(self::SCREEN_NAME, $lockKeys, Auth::user()->id);
            if (!$delLock) {
                $result['status'] = false;
                $result['msg'] = config('message.error.getlock');
                throw new \Exception(config('message.error.getlock').':'.$matterId);
            }
            
            if ($result['status']) {
                DB::commit();
                Session::flash('flash_success', config('message.success.complete'));
            } else {
                DB::rollBack();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['status'] = false;
            Session::flash('flash_error', config('message.error.error'));
        }
        return $result;
    }

    /**
     * 案件完了解除
     *
     * @param Request $request
     * @return void
     */
    public function cancelComplete(Request $request){
        $result = ['status' => true, 'msg' => ''];

        // リクエストデータ取得
        $params = $request->request->all();
        $matterId = $params['matter_id'];

        $Matter = new Matter();

        DB::beginTransaction();
        try {        
            $matterParams = ['id' => $matterId, 'complete_flg' => config('const.flg.off')];
            $Matter->updateByIdEx($matterParams);

            DB::commit();
            Session::flash('flash_success', config('message.success.release'));
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            $result['status'] = false;
            Session::flash('flash_error', config('message.error.error'));
        }
        return $result;
    }
    
    /**
     * 添付ファイル存在確認（見積）
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function existsQuoteFile(Request $request, $quoteVerId, $fileName)
    {
        $fileName = urldecode($fileName);
        $result = Storage::exists(config('const.uploadPath.quote_version'). $quoteVerId. '/'. $fileName);
        return \Response::json($result);
    }
    /**
     * 添付ファイル存在確認（発注）
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function existsOrderFile(Request $request, $matterId, $orderId, $fileName)
    {
        $fileName = urldecode($fileName);
        $result = Storage::exists(config('const.uploadPath.order').'/'. $matterId. '/'. $orderId. '/'. $fileName);
        return \Response::json($result);
    }
    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function downloadQuoteFile(Request $request, $quoteVerId, $fileName)
    {
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.quote_version').$quoteVerId.'/'.$fileName), $fileName, $headers);
    }

    /**
     * 添付ファイルダウンロード
     *
     * @param Request $request
     * @param string $fileName
     * @return void
     */
    public function downloadOrderFile(Request $request, $matterId, $orderId, $fileName)
    {
        $fileName = urldecode($fileName);
        $headers = ['Content-Type' => 'application/octet-stream'];
        return response()->download(Storage::path(config('const.uploadPath.order').'/'. $matterId. '/'. $orderId. '/'. $fileName), $fileName, $headers);
    }

    /**
     * 案件住所情報を取得
     * 
     * @param Request $request
     */
    public function getMatterAddress(Request $request){
        // リクエストデータ取得
        $params = $request->request->all();

        $matter = Matter::find($params['matter_id']);

        $value['matter_address'] = Address::find($matter->address_id);

        return \Response::json($value);
    }

    /**
     * DHTMLXスケジューラ用のデータに変換する
     *
     * @param [type] $data
     * @return void
     */
    private function generateSchedulerData($data){
        $result = [];

        $data = collect($data);
        $data = $data->mapToGroups(function ($item, $key) {
            return [Carbon::parse($item['disp_date'])->format('Ymd') => $item];
        });
        foreach ($data as $key => $group) {
            $group = $group->sort(function($item, $nextItem) {
                if ($item['first_sort'] == $nextItem['first_sort']) {
                    if ($item['second_sort'] == $nextItem['second_sort']) {
                        if ($item['third_sort'] == $nextItem['third_sort']) {
                            return $item['forth_sort'] > $nextItem['forth_sort'] ? 1 : -1;    
                        }
                        return $item['third_sort'] > $nextItem['third_sort'] ? 1 : -1;    
                    }
                    return $item['second_sort'] > $nextItem['second_sort'] ? 1 : -1;
                }
                return $item['first_sort'] < $nextItem['first_sort'] ? -1 : 1 ;
            });
            $group = $group->values();
            for ($i=0, $addCnt=0; $i < count($group); $i++) {
                $value = $group[$i];
                // 1日に表示出来るアイコンの限界に達したらbreak
                if ($addCnt > (self::SCHEDULER['DISP_ICON_CNT']-1)) {
                    break;
                }
                // 表示する必要の無いアイコンはcontinue
                if (!$value['visible']) {
                    continue;
                }
                $startDate = $value['disp_date']. ' '. sprintf('%02d', $addCnt). ':'. '00'. ':'. '00';
                $endDate = $value['disp_date']. ' '. sprintf('%02d', $addCnt). ':'. '59'. ':'. '59';
                $result[] = [
                    'start_date' => $startDate, 'end_date' => $endDate,
                    'ex_property' => [
                        'status' => $value['status'],
                        'type_text' => $value['type_text'],
                        'icon' => $value['icon'],
                        'construction_name' => $value['construction_name'],
                        'process_name' => $value['process_name'],
                    ]
                ];
                $addCnt++;
            }
        }
        return $result;
    }

    /**
     * 連想配列のkeyを指定して足し算
     *
     * @param  $data
     * @param  $column
     * @return void
     */
    private function arraySumByColumn($data, $column){
        return array_sum(array_column($data, $column));
    }

    /**
     * 売上対象期間の取得（ガントチャート用）
     *
     * @param [type] $customer 顧客情報
     * @param [type] $requestData 顧客の請求データ
     * @return void
     */
    private function getSalesPeriod($customer, $requestData)
    {
        $data = [];
        $nextRequestStartDate = $nextRequestEndDate = null;
        if ($customer->closing_day != config('const.customerClosingDay.val.any_time')) {
            $today = Carbon::today();

            foreach ($requestData as $value) {
                $data[] = [
                    'request_s_day' => $value->request_s_day, 'request_e_day' => $value->request_e_day
                ];
            }

            $nextRequestStartDate = null;
            $nextRequestEndDate = null;

            if (count($requestData) > 0) {
                // 最新の請求データを取得する
                $latestRequest = $requestData->firstWhere('request_e_day', $requestData->max('request_e_day'));
                $requestEDay = Carbon::parse($latestRequest->request_e_day);
                $beRequestEDay = Carbon::parse($latestRequest->be_request_e_day);

                // 売上期間（開始日）
                $nextRequestStartDate = Carbon::parse($latestRequest->request_e_day)->addDay(1);

                // 比較用の売上期間終了日を求める
                $compRequestEndDate = null;
                if ($customer->closing_day == config('const.customerClosingDay.val.month_end')) {
                    // 末締
                    $compRequestEndDate = $today->copy()->lastOfMonth(); // 月末をセット
                }else{
                    // 随時締以外
                    $compRequestEndDate = Carbon::parse($today->year. '-'. $today->month. '-'. $customer->closing_day);
                    // 当日（日）が締日より大きい場合は、1ヵ月後をセット
                    if ($today->day > $customer->closing_day) {
                        $compRequestEndDate->addMonthNoOverflow(1);
                    }
                }
                
                // 請求.元売上期間(終了日) > 請求.売上期間（終了日）の場合
                if ($beRequestEDay->gt($requestEDay)) {
                    $nextRequestEndDate = $beRequestEDay->copy();
                    if ($latestRequest->status == config('const.requestStatus.val.close')) {
                        $nextRequestEndDate->addMonthNoOverflow(1);
                        if ($customer->closing_day == config('const.customerClosingDay.val.month_end')) {
                            $nextRequestEndDate->lastOfMonth();
                        }
                    }
                }else{
                    $nextRequestEndDate = $compRequestEndDate->copy();
                }

                $data[] = [
                    'request_s_day' => $nextRequestStartDate->format('Y/m/d'),
                    'request_e_day' => $nextRequestEndDate->format('Y/m/d')
                ];
                // 比較用の売上期間（終了日）を売上期間（終了日）が超えるまで続ける
                while ($compRequestEndDate->gt($nextRequestEndDate)) {
                    $nextRequestStartDate = $nextRequestEndDate->copy()->addDay(1);
                    $nextRequestEndDate->addMonthNoOverflow(1);
                    if ($customer->closing_day == config('const.customerClosingDay.val.month_end')) {
                        $nextRequestEndDate->lastOfMonth();
                    }
                    $data[] = [
                        'request_s_day' => $nextRequestStartDate->format('Y/m/d'),
                        'request_e_day' => $nextRequestEndDate->format('Y/m/d')
                    ];
                }
            }else{
                if ($customer->closing_day == config('const.customerClosingDay.val.month_end')) {
                    // 末締
                    $nextRequestEndDate = $today->copy()->lastOfMonth(); // 月末をセット
                    $nextRequestStartDate = $nextRequestEndDate->copy()->subMonthNoOverflow(1)->lastOfMonth();
                }else{
                    // 随時締以外
                    $nextRequestEndDate = Carbon::parse($today->year. '-'. $today->month. '-'. $customer->closing_day);
                    // 当日（日）が締日より大きい場合は、1ヵ月後をセット
                    if ($today->day > $customer->closing_day) {
                        $nextRequestEndDate->addMonthNoOverflow(1);
                    }
                    $nextRequestStartDate = $nextRequestEndDate->copy()->subMonthNoOverflow(1);
                }
                $data[] = [
                    'request_s_day' => $nextRequestStartDate->format('Y/m/d'),
                    'request_e_day' => $nextRequestEndDate->format('Y/m/d')
                ];
            }
        }

        return $data;
    }

    /**
     * 作業データを作成（アイコン）
     *
     * @param [type] $matterId
     * @return void
     */
    private function generateWorkData($matterId, $receivedData, $productData){
        $Customer = new Customer();
        $Matter = new Matter();
        $Quote = new Quote();
        $Order = new Order();
        $OrderDetail = new OrderDetail();
        $Reserve = new Reserve();
        $Arrival = new Arrival();
        $Shipment = new Shipment();
        $ShipmentDetail = new ShipmentDetail();
        $WarehouseMove = new WarehouseMove();
        $Delivery = new Delivery();
        $SalesDetail = new SalesDetail();
        $Requests = new Requests();
        $Returns = new Returns();

        $matter = $Matter->getById($matterId);       // 案件データ
        $quote = $Quote->getByMatterNo($matter->matter_no);     // 見積データ
        $customer = $Customer->getById($matter->customer_id);   // 得意先データ
  
        // 発注データ(一時保存データは取り除く)
        $orderData = $Order->getByMatterQuote($matter->matter_no, null)->filter(function($value, $key){
            return (!empty($value['order_no']));
        });
        $orderData = $orderData->keyBy('id');

        // 発注IDリスト
        $orderIdList = $orderData->pluck('id')->toArray();

        // 発注明細データ ※発注済以外の発注明細を除く
        $orgOrderDetailData = $OrderDetail->getByOrderIds($orderIdList)->keyBy('id');
        $orderDetailData = [];  
        foreach ($orgOrderDetailData as $value) {
            $orderId = $value->order_id;
            if ($orderData[$orderId]->status != config('const.orderStatus.val.ordered')) {
                continue;
            }

            $orderDetailId = $value->id;
            $quoteDetailId = $value->quote_detail_id;
            if (!isset($orderDetailData[$quoteDetailId])) {
                $orderDetailData[$quoteDetailId] = [];
            }
            $orderDetailData[$quoteDetailId][$orderDetailId] = $value;
        }

        // 引当データ
        // reserveData[見積明細ID][引当ID] => データ
        // orderReserveData[見積明細ID][発注明細ID][引当ID] => データ ※発注引当
        // stockReserveData[見積明細ID][引当ID] => データ ※在庫引当
        $orgReserveData = $Reserve->getByMatterQuote($matter->id, null)->keyBy('id');
        $reserveData = $orderReserveData = $stockReserveData = [];
        foreach ($orgReserveData as $value) {
            $reserveId = $value->id;
            $quoteDetailId = $value->quote_detail_id;
            $orderDetailId = $value->order_detail_id;

            // 見積明細ごとの引当
            if (!isset($reserveData[$quoteDetailId])) {
                $reserveData[$quoteDetailId] = [];
            }
            $reserveData[$quoteDetailId][$reserveId] = $value;

            if ($value->stock_flg == config('const.stockFlg.val.order')) {
                // 見積明細-発注明細ごとの引当 ※発注引当
                if (!isset($orderReserveData[$quoteDetailId])) {
                    $orderReserveData[$quoteDetailId] = [];
                }
                if (!isset($orderReserveData[$quoteDetailId][$orderDetailId])) {
                    $orderReserveData[$quoteDetailId][$orderDetailId] = [];
                }
                $orderReserveData[$quoteDetailId][$orderDetailId][$reserveId] = $value;
            }else{
                // 見積明細ごとの引当 ※在庫引当
                if (!isset($stockReserveData[$quoteDetailId])) {
                    $stockReserveData[$quoteDetailId] = [];
                }
                $stockReserveData[$quoteDetailId][$reserveId] = $value;
            }
        }

        // 入荷データ
        // arrivalData[発注明細ID][入荷ID] => データ
        $orgArrivalData = $Arrival->getByOrderIds($orderIdList)->keyBy('id');
        $arrivalData = [];
        foreach ($orgArrivalData as $value) {
            $arrivalId = $value->id;
            $orderdetailId = $value->order_detail_id;

            if (!isset($arrivalData[$orderdetailId])) {
                $arrivalData[$orderdetailId] = [];
            }
            $arrivalData[$orderdetailId][$arrivalId] = $value;
        }

        // 倉庫移動データ
        // warehouseMoveData[見積明細ID][倉庫移動ID] => データ
        $orgWarehouseMoveData = $WarehouseMove->getByQuoteId($quote->id)->keyBy('id');
        $warehouseMoveData = [];
        foreach ($orgWarehouseMoveData as $value) {
            $warehouseMoveId = $value->id;
            $quoteDetailId = $value->quote_detail_id;
            if (!isset($warehouseMoveData[$quoteDetailId])) {
                $warehouseMoveData[$quoteDetailId] = [];
            }
            $warehouseMoveData[$quoteDetailId][$warehouseMoveId] = $value;
        }

        // 出荷指示データ
        $orgShipmentData = $Shipment->getByMatterId($matter->id)->keyBy('id');

        // 出荷指示明細データ
        // shipmentDetailDataRelatedQuote[見積明細ID][出荷指示明細ID] => データ
        // shipmentDetailDataRelatedReserve[見積明細ID][引当ID][出荷指示明細ID] => データ
        $orgShipmentDetailData = $ShipmentDetail->getByQuoteId($quote->id)->keyBy('id');
        $shipmentDetailDataRelatedQuote = $shipmentDetailDataRelatedReserve =[];
        foreach ($orgShipmentDetailData as  $value) {
            $shipmentDetailId = $value->id;
            $quoteDetailId = $value->quote_detail_id;
            $orderDetailId = $value->order_detail_id;
            $reserveId = $value->reserve_id;

            // 見積明細ごと
            if (!isset($shipmentDetailDataRelatedQuote[$quoteDetailId])) {
                $shipmentDetailDataRelatedQuote[$quoteDetailId] = [];
            }
            $shipmentDetailDataRelatedQuote[$quoteDetailId][$shipmentDetailId] = $value;

            // 引当ごと
            if (!isset($shipmentDetailDataRelatedReserve[$quoteDetailId])) {
                $shipmentDetailDataRelatedReserve[$quoteDetailId] = [];
            }
            if (!isset($shipmentDetailDataRelatedReserve[$quoteDetailId][$reserveId])) {
                $shipmentDetailDataRelatedReserve[$quoteDetailId][$reserveId] = [];
            }
            $shipmentDetailDataRelatedReserve[$quoteDetailId][$reserveId][$shipmentDetailId] = $value;
        }

        //　納品データ
        // deliveryData[見積明細ID][納品ID] => データ
        // deliveryDataRelatedShipment[見積明細ID][出荷指示明細ID][納品ID] => データ
        $orgDeliveryData = $Delivery->getByMatterId($matter->id)->keyBy('id');
        $deliveryData = $deliveryDataRelatedShipment = [];
        foreach ($orgDeliveryData as $value) {
            $deliveryId = $value->id;
            $quoteDetailId = $value->quote_detail_id;
            $shipmentDetailId = $value->shipment_detail_id;

            if (!isset($deliveryData[$quoteDetailId])) {
                $deliveryData[$quoteDetailId] = [];
                $deliveryDataRelatedShipment[$quoteDetailId] = [];
            }
            $deliveryData[$quoteDetailId][$deliveryId] = $value;

            if ($shipmentDetailId) {
                if (!isset($deliveryDataRelatedShipment[$quoteDetailId][$shipmentDetailId])) {
                    $deliveryDataRelatedShipment[$quoteDetailId][$shipmentDetailId] = [];
                }
                $deliveryDataRelatedShipment[$quoteDetailId][$shipmentDetailId] = $value;
            }
        }

        // 売上明細データ
        // salesDetailData[見積明細ID][売上明細ID] => データ
        $orgSalesDetail = $SalesDetail->getByMatterId($matter->id)->keyBy('id');
        $salesDetailData = [];
        foreach ($orgSalesDetail as $value) {
            $salesDetailId = $value->id;
            $quoteDetailId = $value->quote_detail_id;
            if (!isset($salesDetailData[$quoteDetailId])) {
                $salesDetailData[$quoteDetailId] = [];
            }
            $salesDetailData[$quoteDetailId][$salesDetailId] = $value;
        }

        // 請求データを取得
        $orgRequestData = $Requests->getByCustomerId($customer->id)->keyBy('id');

        // 売上期間の取得
        $salesPeriodList = $this->getSalesPeriod($customer, $orgRequestData);
        $funcFindSalesPeriod = function($date) use($salesPeriodList){
            $result = null;
            $date = Carbon::parse($date)->startOfDay();
            foreach ($salesPeriodList as $value) {
                $startDate = Carbon::parse($value['request_s_day']);
                $endDate = Carbon::parse($value['request_e_day']);
                if ($date->between($startDate, $endDate)) {
                    $result = $value;
                    break;
                }
            }
            return $result;
        };

        // 返品データ
        // returnsData[倉庫移動ID][返品ID] => データ
        $orgReturnsData = $Returns->getByMatterId($matter->id)->keyBy('id');
        $returnsData = [];
        foreach ($orgReturnsData as $value) {
            $rtnId = $value->id;
            $warehouseMoveId = $value->warehouse_move_id;
            if (!isset($returnsData[$warehouseMoveId])) {
                $returnsData[$warehouseMoveId] = [];
            }
            $returnsData[$warehouseMoveId][$rtnId] = $value;
        }

        $resWorkData = [];
        $template = [
            'text' => null,
            'type' => null, // サーバへの保存対象のみtypeに値が入る
            'start_date' => null, 'end_date' => null, 'is_date_ambiguous' => false,
            'icon_info' => null, 's_icon_info' => null, 'e_icon_info' => null,
        ];
        $iconTemplate = [
            'finished' => false, 'icon' => null,
        ];

        foreach ($receivedData as $quoteDetailId => $qdValue) {
            if (!isset($resWorkData[$quoteDetailId])) {
                $resWorkData[$quoteDetailId] = [];
            }

            $product = $productData[$qdValue['product_id']] ?? null;
            $orderDetails = $orderDetailData[$quoteDetailId] ?? [];

            // 【発注済】-【入荷済】or【入荷未済】 ※受注確定1行に対して複数ありえる
            // 発注した回数だけ表示される。入荷が発生する場合は入荷もセット。
            foreach ($orderDetails as $orderDetailId => $odValue) {
                $order = $orderData[$odValue['order_id']];  // 発注明細に紐づく発注
                // 発注済でない場合continue
                if ($order['status'] != config('const.orderStatus.val.ordered')){
                    continue;
                }

                $arrivals = $arrivalData[$orderDetailId] ?? [];

                $latestArrivalDate = null;
                foreach ($arrivals as $key => $value) {
                    $ymd = Carbon::parse($value['arrival_date']);
                    $latestYmd = null;
                    if ($latestArrivalDate) {
                        $latestYmd = Carbon::parse($latestArrivalDate);
                    }
                    if (!$latestArrivalDate || $ymd->gt($latestYmd)) {
                        $latestArrivalDate = $value['arrival_date'];
                    }
                }

                $newRow = $template;

                // 初期値セット
                $newRow['text'] = '発注';
                $newRow['start_date'] = Carbon::parse($order['order_datetime'])->format('Y/m/d');
                $newRow['end_date'] = Carbon::parse($order['order_datetime']);
                $newRow['icon_info'] = $iconTemplate;
                $newRow['icon_info']['finished'] = true;
                $newRow['icon_info']['icon'] = 'orderingIcon';
                
                // 商品マスタに存在する && 有形品 && 発注明細に紐づく入荷データが存在する
                if ($product && $product['intangible_flg'] == config('const.flg.off')) {
                    if ($order['delivery_address_kbn'] == config('const.deliveryAddressKbn.val.company')) {
                        // 自社倉庫
                        $newRow['text'] .= '～入荷';    // 入荷作業が発生するので文字列を結合

                        // タスクの両サイドにアイコンを表示する為の処理を行う
                        $newRow['s_icon_info'] = $newRow['e_icon_info'] = $iconTemplate;

                        $newRow['s_icon_info']['finished'] = true;

                        $newRow['s_icon_info']['icon'] = 'orderingIcon';
                        $newRow['e_icon_info']['icon'] = 'arrivalIcon';

                        // 終了日セット
                        if ($odValue['arrival_quantity'] >= $odValue['stock_quantity']) { 
                            // 【入荷済】入荷済数 >= 発注数（管理数）
                            $newRow['icon_info'] = null;
                            $newRow['end_date'] = $latestArrivalDate;
                            $newRow['e_icon_info']['finished'] = true;
                        }else{
                            // 【入荷未済】発注数（管理数）> 入荷済数
                            if ($odValue['arrival_plan_date'] || $odValue['hope_arrival_plan_date']) {
                                $newRow['icon_info'] = null;
                                $newRow['end_date'] = $odValue['arrival_plan_date'] ?? $odValue['hope_arrival_plan_date'];
                            }else{
                                $newRow['s_icon_info'] = $newRow['e_icon_info'] = null;
                            }
                        }
                    }else{
                        // 自社倉庫以外（現場・仕入先倉庫）
                        // 【入荷未済】発注数（管理数）> 入荷済数
                        if ($odValue['stock_quantity'] > $odValue['arrival_quantity']) {
                            // タスクの両サイドにアイコンを表示する為の処理を行う
                            $newRow['icon_info'] = null;
                            $newRow['s_icon_info'] = $newRow['e_icon_info'] = $iconTemplate;

                            $newRow['s_icon_info']['finished'] = true;

                            $newRow['s_icon_info']['icon'] = 'orderingIcon';
                            $newRow['e_icon_info']['icon'] = 'arrivalIcon';
                            $newRow['end_date'] = $latestArrivalDate;
                        }
                    }
                }
                $newRow['end_date'] = Carbon::parse($newRow['end_date'])->addDays(1)->format('Y/m/d');
                $resWorkData[$quoteDetailId][] = $newRow;
            }

            // 【引当済】 ※受注確定1行に対して複数ありえる
            // アイコン対象：在庫引当、預り品の引当データ
            $stockReserves = $stockReserveData[$quoteDetailId] ?? [];   // $stockReserves['引当ID']
            foreach ($stockReserves as $reserveValue) {
                if ($reserveValue->stock_flg == config('const.stockFlg.val.stock') || $reserveValue->stock_flg == config('const.stockFlg.val.keep')) {
                    $newRow =  $template;
                    $newRow['text'] = '引当';
                    $newRow['start_date'] = Carbon::parse($reserveValue['allocation_date'])->format('Y/m/d');
                    $newRow['end_date'] = Carbon::parse($reserveValue['allocation_date'])->addDays(1)->format('Y/m/d');
                    $newRow['icon_info'] = $iconTemplate;
                    $newRow['icon_info']['finished'] = true;
                    $newRow['icon_info']['icon'] = 'reserveIcon';

                    $resWorkData[$quoteDetailId][] = $newRow;
                }
            }

            // 【発注／引当 未済】 ※受注確定1行に対して最大1つ
            // アイコン対象：引当数合計（引当数 + 発注済数 - 返品数）が受注確定数未満である。
            $sumStockReserveQuantity = $this->arraySumByColumn($stockReserves, 'reserve_quantity');
            $sumOrderStockQuantity = $this->arraySumByColumn($orderDetails, 'stock_quantity');
            $warehouseMoves = $warehouseMoveData[$quoteDetailId] ?? [];
            $funcSumReturnQuantity = function($warehouseMoves){
                $quantity = 0;
                if (count($warehouseMoves) > 0) {
                    foreach ($warehouseMoves as $value) {
                        if ($value->move_kind == config('const.moveKind.return') && $value->approval_status == config('const.returnApprovalStatus.val.approvaled')) {
                            $quantity += $value['quantity'];
                        }
                    }
                }
                return $quantity;
            };
            if ($qdValue['stock_quantity'] > ($sumStockReserveQuantity + $sumOrderStockQuantity - $funcSumReturnQuantity($warehouseMoves))) {
                $newRow = $template;
                $newRow['text'] = '発注／引当';
                $newRow['is_date_ambiguous'] = true;
                $newRow['type'] = config('const.matterTaskType.val.order_timing');
                $newRow['icon_info'] = $iconTemplate;
                $newRow['icon_info']['icon'] = 'orderingIcon';

                $resWorkData[$quoteDetailId][] = $newRow;
            }

            // 【出荷指示 未済 Or 超過】 ※受注確定1行に対して複数ありえる
            // 発注引当が起因の未済アイコンは0～N個。在庫引当が起因の未済アイコンは0～1個。
            // アイコン対象：引当データに対して、引当数分の出荷指示が存在しなければアイコン表示する。
            $shipmentDetails = $shipmentDetailDataRelatedReserve[$quoteDetailId] ?? [];    // $shipmentDetails['見積明細ID']['引当ID']
            $funcSumShipmentQuantity = function($details){
                $quantity = 0;
                if (count($details) > 0) {
                    foreach ($details as $value) {
                        if ($value['delivery_finish_flg'] != config('const.deliveryStatus.val.part_delivery')) {
                            $quantity += $value['shipment_quantity'];
                        }
                    }
                }
                return $quantity;
            };
            // 発注品
            $orderReserves = $orderReserveData[$quoteDetailId] ?? [];   // $orderReserves['発注明細ID']['引当ID']
            foreach (array_keys($orderReserves) as $orderDetailId) {
                foreach ($orderReserves[$orderDetailId] as $reserveId => $reserveValue) {
                    $shipmentQuantity = (isset($shipmentDetails[$reserveId])) ? $funcSumShipmentQuantity($shipmentDetails[$reserveId]) : 0;
                    if ($reserveValue['reserve_quantity'] > $shipmentQuantity) {
                        // アイコン
                        $newRow = $template;
                        $newRow['text'] = '出荷指示';
                        $newRow['start_date'] = Carbon::parse($orderDetails[$reserveValue['order_detail_id']]['hope_arrival_plan_date'])->format('Y/m/d');
                        $newRow['end_date'] = Carbon::parse($orderDetails[$reserveValue['order_detail_id']]['hope_arrival_plan_date'])->addDays(1)->format('Y/m/d');
                        $newRow['icon_info'] = $iconTemplate;
                        $newRow['icon_info']['icon'] = 'shippingIcon';
                        $resWorkData[$quoteDetailId][] = $newRow;
                    }
                }
            }
            // 在庫引当品
            $stockReserves = $stockReserveData[$quoteDetailId] ?? [];   // $stockReserves['引当ID']
            foreach ($stockReserves as $reserveId => $reserveValue) {
                $shipmentQuantity = (isset($shipmentDetails[$reserveId])) ? $funcSumShipmentQuantity($shipmentDetails[$reserveId]) : 0;
                if ($reserveValue['reserve_quantity'] > $shipmentQuantity) {
                    // アイコン
                    $newRow = $template;
                    $newRow['text'] = '出荷指示(在庫)';
                    $newRow['is_date_ambiguous'] = true;
                    $newRow['type'] = config('const.matterTaskType.val.hope_arrival_plan_date');
                    $newRow['icon_info'] = $iconTemplate;
                    $newRow['icon_info']['icon'] = 'shippingIcon';
                    $resWorkData[$quoteDetailId][] = $newRow;
                    break;
                }
            }

            // 【納品済】 ※受注確定1行に対して複数ありえる
            // アイコン対象1：納品と紐づく出荷指示明細が存在し、納品完了済
            // アイコン対象2：納品と紐づく出荷指示明細が存在しないが、納品数量が0以上
            $deliveries = $deliveryData[$quoteDetailId] ?? [];
            foreach ($deliveries as $deliValue) {
                $showIcon = false;
                if ($deliValue['shipment_detail_id']) {
                    // 自社倉庫から出荷分の納品
                    $sdValue = $orgShipmentDetailData[$deliValue['shipment_detail_id']];
                    if ($sdValue['delivery_finish_flg'] == config('const.deliveryStatus.val.complete')) {
                        $showIcon = true;   // アイコン対象1
                    }
                }else{
                    // それ以外
                    if ($deliValue['delivery_quantity'] > 0) {
                        $showIcon = true;   // アイコン対象2
                    }
                } 
                if ($showIcon) {
                    $dispDate = Carbon::parse($deliValue['delivery_date']);
                    $newRow = $template;
                    $newRow['text'] = '出荷指示～納品';
                    $newRow['start_date'] = $dispDate->format('Y/m/d');
                    $newRow['end_date'] = $dispDate->copy()->addDays(1)->format('Y/m/d');
                    $newRow['icon_info'] = $iconTemplate;
                    $newRow['icon_info']['finished'] = true;
                    $newRow['icon_info']['icon'] = 'deliveryIcon';
                    $resWorkData[$quoteDetailId][] = $newRow;
                }
            }

            // 【配送 未済 or 超過】 ※受注確定1行に対して複数ありえる
            // アイコン対象：出荷指示データに対して、納品データが存在しない
            $shipmentDetails = $shipmentDetailDataRelatedQuote[$quoteDetailId] ?? [];
            foreach ($shipmentDetails as $shipmentDetailId => $sdValue) {
                if (!isset($deliveryDataRelatedShipment[$quoteDetailId][$shipmentDetailId])) {
                    // アイコン
                    $dispDate = Carbon::parse($orgShipmentData[$sdValue['shipment_id']]['delivery_date']);
                    $newRow = $template;
                    $newRow['text'] = '出荷指示～納品';
                    $newRow['start_date'] = $dispDate->format('Y/m/d');
                    $newRow['end_date'] = $dispDate->copy()->addDays(1)->format('Y/m/d');
                    $newRow['icon_info'] = $iconTemplate;
                    $newRow['icon_info']['icon'] = 'truckIcon';
                    $resWorkData[$quoteDetailId][] = $newRow;
                }
            }

            // 【売上確定済】より【売上確定未済 or 超過】の表示を優先する
            // 【売上確定済】データに紐づく請求データの売上期間終了日が【売上確定未済 or 超過】の売上期間終了日と被っている場合
            // 【売上確定済】は重複対象の判定を売上期間終了日でするが、表示する日付は売上確定日

            // 【売上確定済】 ※受注確定1行に対して複数ありえる
            // アイコン対象：売上確定が終わっている売上確定明細が存在する
            $addSalesFinishedList = [];
            $salesDetails = $salesDetailData[$quoteDetailId] ?? [];
            $existsDate = [];
            foreach ($salesDetails as $salesDetailId => $sdValue) {
                if ($sdValue['sales_update_date'] && (
                        $sdValue['sales_flg'] == config('const.salesFlg.val.delivery') ||
                        $sdValue['sales_flg'] == config('const.salesFlg.val.not_delivery') || 
                        $sdValue['sales_flg'] == config('const.salesFlg.val.return')
                    )
                ) {
                    if (!in_array($sdValue['sales_update_date'], $existsDate)) {
                        $existsDate[] = Carbon::parse($sdValue['sales_update_date'])->format('Ymd');

                        $dateKey = Carbon::parse($orgRequestData[$sdValue['request_id']]['request_e_day'])->format('Ymd');
                        $addSalesFinishedList[$dateKey] = [
                            'start_date' => Carbon::parse($sdValue['sales_update_date'])->format('Y/m/d'),
                            'finished' => true,
                        ];
                    }
                }
            }

            // 【売上確定 未済 Or 超過】 ※受注確定1行に対して最大1つ
            // 得意先が随時締めで無い場合は処理を行う
            $addSalesUnfinishedList = [];
            if ($customer->closing_day != config('const.customerClosingDay.val.any_time')) {
                // アイコン対象1：納品に対して売上明細が存在しないデータを取得
                // アイコン対象2：納品に紐づいている売上明細の売上確定が終わっていない
                $deliveries = $deliveryData[$quoteDetailId] ?? [];
                foreach ($deliveries as $deliveryId => $deliValue) {
                    $showIcon = false;
                    // 納品日が売上期間内に存在する
                    $salesPeriod = $funcFindSalesPeriod($deliValue['delivery_date']);
                    $salesEndDate = Carbon::parse($salesPeriod['request_e_day']);
                    $dateKey = $salesEndDate->format('Ymd');
                    
                    // 既に存在する場合はcontinue;
                    if (isset($addSalesUnfinishedList[$dateKey])) {
                        continue;
                    }

                    // 売上データを探索
                    $targetSalesDetail = null;
                    foreach ($salesDetails as $salesDetailId => $salesValue) {
                        if ($salesValue['delivery_id'] == $deliveryId) {
                            $targetSalesDetail = $salesValue;
                            break;
                        }
                    }

                    if ($targetSalesDetail) {
                        // 納品に紐づく売上が存在する
                        $salesPeriod = $funcFindSalesPeriod($targetSalesDetail['sales_date']);
                        $salesEndDate = Carbon::parse($salesPeriod['request_e_day']);
                        $dateKey = $salesEndDate->format('Ymd');

                        // 既に存在する場合はcontinue;
                        if (isset($addSalesUnfinishedList[$dateKey])) {
                            continue;
                        }

                        // 売上確定日がセットされていない && 売上日が売上期間内に存在 && (売上種別が納品 OR 返品)
                        if (!$targetSalesDetail['sales_update_date']
                            && ($targetSalesDetail['sales_flg'] == config('const.salesFlg.val.delivery') || $targetSalesDetail['sales_flg'] == config('const.salesFlg.val.return'))
                        ) {
                            // アイコン対象2
                            $showIcon = true;
                        }
                    }else{
                        // 納品に紐づく売上が存在しない
                        // アイコン対象1
                        $showIcon = true;
                    }
                    if ($showIcon) {
                        $addSalesUnfinishedList[$dateKey] = [
                            'start_date' => $salesEndDate->format('Y/m/d'),
                            'finished' => false,
                        ];
                        unset($addSalesFinishedList[$dateKey]);
                    }
                }

                // アイコン対象：返品に対して売上明細が存在しない
                $warehouseMoves = $warehouseMoveData[$quoteDetailId] ?? [];
                foreach ($warehouseMoves as $warehouseMoveId => $whmValue) {
                    $showIcon = false;
                    $salesPeriod = $funcFindSalesPeriod($whmValue['to_product_move_at']);
                    $salesEndDate = Carbon::parse($salesPeriod['request_e_day']);
                    $dateKey = $salesEndDate->format('Ymd');
                    
                    // 既に存在する場合はcontinue;
                    if (isset($addSalesUnfinishedList[$dateKey])) {
                        continue;
                    }

                    $returns = $returnsData[$warehouseMoveId] ?? [];
                    foreach ($returns as $rtnId => $rtnValue) {
                        // 返品処置区分が預かり品 && 返品処置完了区分が完了済
                        if ($rtnValue['return_kbn'] != config('const.returnKbn.keep') && $rtnValue['return_finish'] == config('const.returnFinish.val.completed')) {
                            $salesDetails = $salesDetailData[$quoteDetailId] ?? [];
                            if (count($salesDetails) == 0) {
                                $showIcon = true;
                                break;
                            }
                        }
                    }
                    if ($showIcon) {
                        $addSalesUnfinishedList[$dateKey] = [
                            'start_date' => $salesEndDate->format('Y/m/d'),
                            'finished' => false,
                        ];
                        unset($addSalesFinishedList[$dateKey]);
                    }
                }

                // アイコン対象：売上確定が終わっていない未納売上の売上明細が存在する
                $salesDetails = $salesDetailData[$quoteDetailId] ?? [];
                foreach ($salesDetails as $salesDetailId => $sdValue) {
                    $salesPeriod = $funcFindSalesPeriod($sdValue['sales_date']);
                    $salesEndDate = Carbon::parse($salesPeriod['request_e_day']);
                    $dateKey = $salesEndDate->format('Ymd');

                    // 既に存在する場合はcontinue;
                    if (isset($addSalesUnfinishedList[$dateKey])) {
                        continue;
                    }

                    // 売上確定日がnull && 売上種別が未納
                    if (!$sdValue['sales_update_date'] && $sdValue['sales_flg'] == config('const.salesFlg.val.not_delivery')) {
                        $addSalesUnfinishedList[$dateKey] = [
                            'start_date' => $salesEndDate->format('Y/m/d'),
                            'finished' => false,
                        ];
                        unset($addSalesFinishedList[$dateKey]);
                    }
                }
            }
            // マージ（未済・超過データ生成時に済をunsetしている為キーは絶対に被らない）
            $addSalesList = array_merge($addSalesFinishedList, $addSalesUnfinishedList);
            ksort($addSalesList);
            foreach ($addSalesList as $value) {
                $startDate = Carbon::parse($value['start_date']);
                $newRow = $template;
                $newRow['text'] = '売上確定';
                $newRow['start_date'] = $startDate->format('Y/m/d');
                $newRow['end_date'] = $startDate->copy()->addDays(1)->format('Y/m/d');
                $newRow['icon_info'] = $iconTemplate;
                $newRow['icon_info']['finished'] = $value['finished'];
                $newRow['icon_info']['icon'] = 'saleIcon';
                $resWorkData[$quoteDetailId][] = $newRow;
            }
        }
        return $resWorkData;
    }

    /**
     * 着工日を基準にガントチャート用休日と会社用休日を取得
     *
     * @param [type] $constructionDate
     * @return void
     */
    private function generateHolidayList($constructionDate, &$ganttHolidayList, &$companyHolidayList)
    {
        $CalendarData = new CalendarData();
        $from = $constructionDate->copy()->subMonthNoOverFlow(6);
        $to = $constructionDate->copy()->addYear(1);
        $holidayList = $CalendarData->getFromTo($from->format('Ymd'), $to->format('Ymd'));
        $ganttHolidayList = $holidayList->filter(function ($value, $key) {
            return ($value->holiday_flg == config('const.flg.on') || $value->calendar_week == config('const.calendarWeek.val.sunday'));
        });
        $ganttHolidayList->transform(function ($item, $key) {
            return Carbon::parse($item->calendar_date)->format('Y/m/d');
        });
        $ganttHolidayList = $ganttHolidayList->values()->toArray();

        $companyHolidayList = $holidayList->filter(function ($value, $key) {
            return ($value->businessday == config('const.businessdayKbn.val.holiday') || $value->businessday == config('const.businessdayKbn.val.vacation'));
        });
        $companyHolidayList->transform(function ($item, $key) {
            return Carbon::parse($item->calendar_date)->format('Y/m/d');
        });
        $companyHolidayList = $companyHolidayList->values()->toArray();
    }

    /**
     * 自分がロックしているか
     * @param $keys
     * @return true：ロックを持っている
     */
    private function isOwnLocked($SCREEN_NAME, ...$keys){
        $result = true;

        $LockManage = new LockManage();

        $tableNameList = config("const.lockList.$SCREEN_NAME");
        if(!$LockManage->isOwnLocks($SCREEN_NAME, $tableNameList, $keys)){
            $result = false;
        }
        return $result;
    }

    /**
     * ロック解除する
     * @param $keys
     * @return true：ロックを解除できた
     */
    private function unLock($SCREEN_NAME, ...$keys){
        $result = true;

        $LockManage = new LockManage();

        if(!$LockManage->deleteLockInfo($SCREEN_NAME, $keys, Auth::user()->id)){
            $result = false;
        }
        return $result;
    }
}