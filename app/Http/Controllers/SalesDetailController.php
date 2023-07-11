<?php
namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\QuoteVersion;
use App\Models\QuoteLayer;
use App\Models\Customer;
use App\Models\General;
use App\Models\LockManage;
use App\Models\NumberManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Address;
use App\Models\CalendarData;
use App\Models\Construction;
use App\Models\CustomerBranch;
use App\Models\Delivery;
use App\Models\Returns;
use App\Models\Department;
use App\Models\Loading;
use App\Models\Notice;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Requests;
use App\Models\Reserve;
use App\Models\Sales;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Staff;
use App\Models\StaffDepartment;
use App\Models\Warehouse;
use App\Models\SalesDetail;

/**
 * 売上明細
 */
class SalesDetailController extends Controller
{
    const SCREEN_NAME = 'sales-detail';


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
     * @param Request $request
     * @param $matterId
     * @return type
     */
    public function index(Request $request, $matterId)
    {
        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');

        $SystemUtil     = new SystemUtil();
        // $Department     = new Department();
        // $StaffDepartment= new StaffDepartment();
        // $Staff          = new Staff();
        $Customer       = new Customer();

        // モデル
        $Matter         = new Matter();
        //$QuoteVersion   = new QuoteVersion();
        $QuoteDetail    = new QuoteDetail();
        $LockManage     = new LockManage();
        $General        = new General();
        $Quote          = new Quote();
        $Sales          = new Sales();
        $SalesDetail    = new SalesDetail();
        $Delivery       = new Delivery();
        $Returns        = new Returns();
        $Construction   = new Construction();
        $Requests       = new Requests();


        $loginInfo          = null;
        $lockData           = null;
        
        // 請求情報
        $requestInfo        = null;

        $treeDataList       = [];
        $gridDataList       = [];
        $gridDetailDataList = [];
        $defaultFilterInfo  = [
            'other' => [],
        ];
        $addLayerInfo       = null;
        
        
        /** データ取得 **/
        $matter         = $Matter->getSalesDetailData($matterId);

        if($matter === null){
            throw new NotFoundHttpException();
        }

        DB::beginTransaction();
        try {
            $loginInfo  = Auth::user();
            /* デフォルトのフィルター状態 */
            $ZERO_SALES =  $request->query('zero_sales');
            $COST_EDIT  =  $request->query('cost_edit');
            if(Common::nullorBlankToZero($ZERO_SALES) !== 0 && intval($ZERO_SALES) === config('const.salesDetailFilterInfo.OTHER.key.zero_sales')){
                $defaultFilterInfo['other'][] = config('const.salesDetailFilterInfo.OTHER.key.zero_sales');
            }
            if(Common::nullorBlankToZero($COST_EDIT) !== 0 && intval($COST_EDIT) === config('const.salesDetailFilterInfo.OTHER.key.cost_edit')){
                $defaultFilterInfo['other'][] = config('const.salesDetailFilterInfo.OTHER.key.cost_edit');
            }
            /* 計上月 */
            $REQUEST_MON =  $request->query('request_mon');
            /* 売上期間の開始日 */
            $REQUEST_S_DAY =  $request->query('request_s_day');
            /* 売上期間の終了日 */
            $REQUEST_E_DAY =  $request->query('request_e_day');
            /* 入金予定日 */
            $EXPECTEDDEPOSIT_AT =  $request->query('expecteddeposit_at');


            // 請求データ情報(売上期間などをセット)
            $requestInfo    = $SystemUtil->getStrictCurrentMonthRequestPeriod($matter->customer_id);
            // 一覧から引数で渡ってきた計上月をセットする
            if(Common::nullorBlankToZero($requestInfo->id) === 0 && Common::nullToBlank($REQUEST_MON) !== ''){
                $requestInfo->request_mon = $REQUEST_MON;
                // 請求月を日本語化
                $requestInfo->request_mon_text = '';
                $requestInfo->request_mon_text       = $this->formatRequestMon($requestInfo->request_mon, true);
            }
            // 一覧から引数で渡ってきた売上期間の開始日をセットする
            if(Common::nullorBlankToZero($requestInfo->id) === 0 && Common::nullToBlank($REQUEST_S_DAY) !== ''){
                $requestInfo->request_s_day = $REQUEST_S_DAY;
            }
            // 一覧から引数で渡ってきた売上期間の終了日をセットする
            if(Common::nullorBlankToZero($requestInfo->id) === 0 && Common::nullToBlank($REQUEST_E_DAY) !== ''){
                $requestInfo->request_e_day = $REQUEST_E_DAY;
                $requestInfo->shipment_at   = (new Carbon($requestInfo->request_e_day))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');
            }
            // 一覧から引数で渡ってきた入金予定日をセットする
            if(Common::nullToBlank($EXPECTEDDEPOSIT_AT) !== ''){
                $requestInfo->expecteddeposit_at = $EXPECTEDDEPOSIT_AT;
            }

            $requestInfo->is_valid_sales_date = true;
            if((new Carbon($requestInfo->request_s_day))->gt((new Carbon($requestInfo->request_e_day)))){
                // 開始日と終了日が逆転している(前回の請求データを売上期間終了日をずらさずに作成し、請求締めを行っていない場合に発生する)
                $requestInfo->is_valid_sales_date = false;
            }
            

            // フロント用の情報
            $requestInfo->delivery_quantity = 0;
            $requestInfo->return_quantity   = 0;

            // 案件の情報をセット
            $requestInfo->owner_name                    = $matter->owner_name;
            $requestInfo->matter_charge_department_id   = $matter->department_id;
            $requestInfo->matter_charge_department_name = $matter->department_name;
            $requestInfo->matter_charge_staff_id        = $matter->staff_id;
            $requestInfo->matter_charge_staff_name      = $matter->staff_name;




            // 案件情報
            // グリッドデータ
            $tmpSalesData = $Sales->getSalesDetailDataList($matter->matter_id, $matter->matter_no, $matter->quote_id, $requestInfo->id);
            // 親グリッドデータ
            $gridDataList       = $tmpSalesData['salesDataList'];
            // 子グリッドのデータ
            $gridDetailDataList = $tmpSalesData['salesDetailDataList'];

            // 階層データ
            $treeDataList   = $QuoteDetail->getTreeData($matter->quote_no, config('const.quoteCompVersion.number'), null, null, array(), ['product_name' => 'トップ'], null);
            $SystemUtil->deleteNotLayerRecord($treeDataList[0]);

            // 追加部材の情報　「＋」「-」ボタン制御に使用
            $addLayerInfo   = $Construction->getAddFlgData();

            
            
            // 確定済み or 開始日になっていない or 開始日と終了日が逆転している 場合はロックを取らない
            if($requestInfo->status === config('const.requestStatus.val.unprocessed') && $requestInfo->is_sales_started && $requestInfo->is_valid_sales_date){
                // 画面名から対象テーブルを取得
                $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
                $keys = array($matter->customer_id);

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


        return view('Sales.sales-detail')
                ->with('loginInfo',             $loginInfo)
                ->with('priceList',             $General->getCategoryList(config('const.general.price')))
                ->with('salesDetailFlgList',    json_encode(config('const.salesDetailFlg')))
                ->with('salesFlgList',          json_encode(config('const.salesFlg')))
                ->with('salesStatusList',       json_encode(config('const.salesStatus')))
                ->with('notdeliveryFlgList',    json_encode(config('const.notdeliveryFlg')))
                ->with('salesDetailFilterInfoList', json_encode(config('const.salesDetailFilterInfo')))
                ->with('requestStatusList',     json_encode(config('const.requestStatus')))
                ->with('isOwnLock',             $isOwnLock)
                ->with('lockData',              $lockData)
                ->with('defaultFilterInfo',     json_encode($defaultFilterInfo))

                ->with('quoteInfo',             $matter)
                ->with('requestInfo',           $requestInfo)
                ->with('treeDataList',          json_encode($treeDataList))
                ->with('gridDataList',          json_encode($gridDataList))
                ->with('gridDetailDataList',    json_encode($gridDetailDataList))
                ->with('addLayerInfo',          $addLayerInfo)
                ;
    }

    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function save(Request $request)
    {
        $result = array('status' => false, 'message' => '');

        // モデル
        $Department     = new Department();
        $Matter         = new Matter();
        $Quote          = new Quote();
        $QuoteDetail    = new QuoteDetail();
        $Sales          = new Sales();
        $SalesDetail    = new SalesDetail();
        $Delivery       = new Delivery();
        $Returns        = new Returns();
        $Requests       = new Requests();
        $Notice         = new Notice();

        // リクエストデータ取得
        $params         = $request->request->all();

        // 請求
        $requestInfo    = json_decode($params['request_info'], true);
        // 売上
        $salesList      = json_decode($params['sales_list'], true);
        // 売上明細
        $salesDetailList= json_decode($params['sales_detail_list'], true);
        // 削除した売上
        $delSalesIdList      = json_decode($params['delete_sales_id_list'], true);
        // 削除した売上明細
        $delSalesDetailIdList= json_decode($params['delete_sales_detail_id_list'], true);
        
        // 入力チェック
        //$this->isValidShipment($request);

        DB::beginTransaction();
        
        try {

            // ロックを持っているか確認
            if(!$this->isOwnLocked($requestInfo['customer_id'])){
                $result['message'] = config('message.error.getlock');
                throw new \Exception();
            }

            $errMessage = $this->chkRequestData($requestInfo);
            if($errMessage !== ''){
                $result['message'] = $errMessage;
                throw new \Exception();
            }

            // 請求データ作成/更新
            if(!isset($requestInfo['id']) || Common::nullorBlankToZero($requestInfo['id']) == 0){
                // 新規請求
                //$requestInfo['id'];
                $requestSaveData    = [];

                $requestSaveData['customer_id']     = $requestInfo['customer_id'];
                $requestSaveData['customer_name']   = $requestInfo['customer_name'];
                $requestSaveData['charge_department_id'] = $requestInfo['charge_department_id'];
                $requestSaveData['charge_department_name'] = $requestInfo['charge_department_name'];
                $requestSaveData['charge_staff_id']     = $requestInfo['charge_staff_id'];
                $requestSaveData['charge_staff_name']   = $requestInfo['charge_staff_name'];
                $requestSaveData['request_mon']         = $requestInfo['request_mon'];
                $requestSaveData['closing_day']     = $requestInfo['closing_day'];
                $requestSaveData['request_s_day']   = $requestInfo['request_s_day'];
                $requestSaveData['request_e_day']   = $requestInfo['request_e_day'];
                $requestSaveData['be_request_e_day']= $requestInfo['be_request_e_day'];
                $requestSaveData['shipment_at']     = $requestInfo['shipment_at'];
                $requestSaveData['expecteddeposit_at']  = $requestInfo['expecteddeposit_at'];
                $requestSaveData['sales_category']  = config('const.sales_category.val.sales');
                $requestSaveData['status']          = config('const.requestStatus.val.unprocessed');

                $requestInfo['id'] = $Requests->add($requestSaveData);
            }else{
                // 請求の更新
                $requestUpdateData    = [];
                $requestUpdateData['expecteddeposit_at']  = $requestInfo['expecteddeposit_at'];
                $Requests->updateById($requestInfo['id'], $requestUpdateData);
            }




            // 売上削除
            if(count($delSalesIdList) >= 1){
                $Sales->deleteByIds($delSalesIdList);
            }

            // 売上明細削除
            if(count($delSalesDetailIdList) >= 1){
                $SalesDetail->deleteByIds($delSalesDetailIdList);
            }


            // ステータスをセットして申請中にする
            // $noticeCnt = $this->setSalesStatus($salesList, $salesDetailList);

            // 売上データの登録更新
            foreach($salesList as $key => $row){
                $salesId = $row['sales_id'];
                $row['matter_id']       = $params['matter_id'];
                $row['quote_id']        = $params['quote_id'];
                $row['update_cost_unit_price_d']    = empty($row['update_cost_unit_price_d']) ? null : $row['update_cost_unit_price_d'];

                $filterTreePath     = $row['filter_tree_path'];
                // 子グリッドが無い売上データは登録更新しない
                if(count($salesDetailList[$filterTreePath]) >= 1){
                    if(Common::nullorBlankToZero($salesId) === 0){
                        // 新規
                        $row['request_id']  = $requestInfo['id'];
                        $row['status_dep']  = $requestInfo['matter_charge_department_id'];
                        $row['sales_id']    = $Sales->add($row);
                    }else{
                        if($row['sales_flg'] !== config('const.salesDetailFlg.val.quote')){
                            $salesDetailRow = $salesDetailList[$row['filter_tree_path']][0];
                            if(Common::nullToBlank($salesDetailRow['sales_update_date']) !== ''){
                                // 確定行
                            }else{
                                // 更新
                                $Sales->updateById($salesId, $row);
                            }
                        }else{
                            // 更新
                            $Sales->updateById($salesId, $row);
                        }
                    }
                    $salesList[$key] = $row;
                }
            }

            // 売上明細の登録更新
            foreach($salesList as $salesRow){
                $salesId            = $salesRow['sales_id'];
                $filterTreePath     = $salesRow['filter_tree_path'];
                foreach($salesDetailList[$filterTreePath] as $key => $salesDetailRow){
                    if(Common::nullToBlank($salesDetailRow['sales_update_date']) !== ''){
                        // 確定分はスキップ
                        continue;
                    }
                    $salesDetailRow['sales_id']     = $salesId;
                    $salesDetailRow['matter_id']    = $params['matter_id'];
                    $salesDetailRow['quote_id']     = $params['quote_id'];
                    
                    $salesDetailId = $salesDetailRow['sales_detail_id'];
                    if(Common::nullorBlankToZero($salesDetailId) === 0){
                        // 新規
                        $salesDetailRow['sales_update_date'] = null;
                        $salesDetailRow['sales_detail_id']  = $SalesDetail->add($salesDetailRow);
                    }else{
                        // 編集
                        $SalesDetail->updateById($salesDetailId, $salesDetailRow);
                    }
                    $salesDetailList[$filterTreePath][$key] = $salesDetailRow;
                }
            }
            
            // $departmentData = $Department->getById($requestInfo['matter_charge_department_id']);
            // $noiceList = [];
            // $noticeData = [
            //     'notice_flg'    => config('const.flg.off'),
            //     'staff_id'      => $departmentData->chief_staff_id,
            //     'content'       => str_replace('$matter_name', $params['matter_name'], str_replace('$customer_name', $requestInfo['customer_name'], config('message.notice.sales_applying'))),
            //     'redirect_url'  => $this->createNoticeUrl($requestInfo['customer_id']),
            // ];
            // for($cnt=0; $cnt<$noticeCnt; $cnt++){
            //     $noiceList[] = $noticeData;
            // }
            // if(count($noiceList) >= 1){
            //     $noticeResult = $Notice->addList($noiceList);
            // }

            // 売上明細が紐づいていない売上を削除する
            $Sales->deleteNoRelatedSalesData($params['matter_id']);
    

            // ロック解除
            if(!$this->unLock($requestInfo['customer_id'])){
                throw new \Exception();
            }

            
            DB::commit();
            $result['status'] = true;
            Session::flash('flash_success', config('message.success.save'));

        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if($result['message'] === ''){
                $result['message'] = config('message.error.error');
            }
            Log::error($e);
        }

        return \Response::json($result);
    }

    /**
     * 承認ステータスを変更する
     * 
     */
    public function changeStatus(Request $request){
        $result = array('status' => false, 'message' => '', 'sales_status' => 0, 'bk_sales_unit_price' => 0, 'sales_unit_price' => 0);

        $Sales          = new Sales();
        $Department     = new Department();
        $SystemUtil     = new SystemUtil();
        $Notice         = new Notice();

        // リクエストデータ取得
        $params     = $request->request->all();
        
        DB::beginTransaction();

        try{

            // ロックを持っているか確認
            if(!$this->isOwnLocked($params['customer_id'])){
                $result['message'] = config('message.error.getlock');
                throw new \Exception();
            }

            $salesData = $Sales->getById($params['sales_id']);
            $statusDep = $salesData->status_dep;

            // 承認部門の担当部門の情報
            $departmentData = $Department->getById($statusDep);
            // 次の部門ID
            $parentDepartmentId = $departmentData->parent_id;

            $noticeData = [];
            $updateResult = $SystemUtil->changeSalesStatus(
                $params['is_approve'], 
                $salesData, 
                $params['request_id'], 
                $params['customer_name'], 
                $params['matter_name'], 
                $params['matter_charge_department_id'], 
                $parentDepartmentId, 
                $this->createNoticeUrl($params['customer_id']), 
                $noticeData
            );

            $result['sales_status']         = $updateResult['sales_status'];
            $result['bk_sales_unit_price']  = $updateResult['bk_sales_unit_price'];
            $result['sales_unit_price']     = $updateResult['sales_unit_price'];  
            $result['chief_staff_id']       = $updateResult['chief_staff_id'];

            foreach($noticeData as $key => $notice){
                // 1件のみ
                $Notice->add($notice);
            }


            DB::commit();
            $result['status']   = true;
            
        } catch (\Exception $e) {
            DB::rollBack();
            $result['status'] = false;
            if($result['message'] === ''){
                $result['message'] = config('message.error.error');
            }
            Log::error($e);
        }
        
        return \Response::json($result);
    }

    /**
     * 通知URLを作成
     * @param $customerId
     */
    private function createNoticeUrl($customerId){
        return url('sales-list?'.'customer_id='.$customerId);
    }

    /**
     * 売上データのステータスを申請中にする
     * 初期表示から粗利率が下がっていた場合のみ
     * @param $salesList        売上データ(参照)
     * @param $salesDetailList  売上明細データ　子グリッドがない売上の申請は起こさない
     * @return $noticeCnt       通知データの件数
     */
    private function setSalesStatus(&$salesList, $salesDetailList){
        $noticeCnt = 0;
        foreach($salesList as $key => $row){
            if($row['sales_flg'] === config('const.salesDetailFlg.val.quote')){
                if($row['status'] !== config('const.salesStatus.val.applying')){
                    // 未確定の売上明細があるもの
                    $filterTreePath     = $row['filter_tree_path'];
                    $isExist = false;
                    foreach($salesDetailList[$filterTreePath] as $salesDetailRow){
                        if(Common::nullToBlank($salesDetailRow['sales_update_date']) === ''){
                            $isExist = true;
                        }
                    }
                    // 既に申請中になっていないもの
                    if($isExist && $row['gross_profit_rate'] < $row['default_gross_profit_rate']){
                        // 表示時の粗利率より下がった場合に、ステータスを申請中にする
                        $salesList[$key]['status'] = config('const.salesStatus.val.applying');
                        $salesList[$key]['apply_staff_id']  = Auth::user()->id;     // 申請者
                        $salesList[$key]['apply_date']      = Carbon::now();        // 申請日
                        $salesList[$key]['approval_date']   = null;                 // 承認日をクリア
                        $noticeCnt++;
                    }
                }
            }

            if($row['sales_unit_price'] >= $row['bk_sales_unit_price']){
                $salesList[$key]['bk_sales_unit_price'] = $row['sales_unit_price'];
            }
        }
        return $noticeCnt;
    }

    /**
     * 請求データのチェック
     * @param $requestInfo   請求情報
     */
    private function chkRequestData($requestInfo){
        $errMessage     = '';
        $Requests       = new Requests();

        $customerId         = $requestInfo['customer_id'];
        $requestId          = isset($requestInfo['id']) ? $requestInfo['id'] : 0;
        $screenRequestSDay  = $requestInfo['request_s_day'];
        $updateAt           = isset($requestInfo['update_at_raw']) ? $requestInfo['update_at_raw'] : '';

        if($Requests->isExistByRequestSDay($customerId, $requestId, $screenRequestSDay)){
            // 同じ開始日の請求データがある(画面を開いている間に誰かが作成した)
            $errMessage = config('message.error.sales.exist_request_data');
        }else if(Common::nullorBlankToZero($requestId) != 0){
            $requestData = $Requests->getById($requestId);
            if($requestData == null || $updateAt != $requestData->update_at){
                // 請求データが消えている or 既に更新している
                $errMessage = config('message.error.sales.update_at_different');
            }
        }
        
        return $errMessage;
    }

    /**
     * 自分がロックしているか
     * @param $keys
     * @return true：ロックを持っている
     */
    private function isOwnLocked(...$keys){
        $result = true;

        $LockManage = new LockManage();

        $SCREEN_NAME = self::SCREEN_NAME;
        $tableNameList = config("const.lockList.$SCREEN_NAME");
        if(!$LockManage->isOwnLocks($SCREEN_NAME, $tableNameList, $keys)){
            // throw new \Exception(config('message.error.getlock'));
            $result = false;
        }
        return $result;
    }

    /**
     * ロック解除する
     * @param $keys
     * @return true：ロックを解除で来た
     */
    private function unLock(...$keys){
        $result = true;

        $LockManage = new LockManage();

        $SCREEN_NAME = self::SCREEN_NAME;
        if(!$LockManage->deleteLockInfo($SCREEN_NAME, $keys, Auth::user()->id)){
            $result = false;
        }
        return $result;
    }

    /**
     * 請求年月を文字列に変換する
     * @param $requestMon   yyyyMM
     * @param $isJp         / 区切りか 日本語か
     * @param $closingDay   締め日
     */
    private function formatRequestMon($requestMon, $isJp = false, $closingDay = null)
    {
        $result = '';
        if ($isJp) {
            $result = substr($requestMon, 0, 4) . '年' . substr($requestMon, 4, 2) . '月';
        } else {
            $result = substr($requestMon, 0, 4) . '/' . substr($requestMon, 4, 2);
        }

        if ($closingDay !== null) {
            $closingDay = str_pad($closingDay, 2, 0, STR_PAD_LEFT);
            if ($isJp) {
                $result = $result . $closingDay . '日';
            } else {
                $result = $result . '/' . $closingDay;
            }
        }
        return $result;
    }

}