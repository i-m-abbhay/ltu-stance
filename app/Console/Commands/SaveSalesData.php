<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Illuminate\Support\Facades\Auth;
use App\Libs\Common;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use App\Libs\LogUtil;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;
use App\Libs\SystemUtil;
use App\Models\Credited;
use App\Models\Customer;
use App\Models\Delivery;
use App\Models\Department;
use App\Models\LockManage;
use App\Models\Matter;
use App\Models\Notice;
use App\Models\Quote;
use App\Models\QuoteDetail;
use App\Models\Requests;
use App\Models\Returns;
use App\Models\Sales;
use App\Models\SalesDetail;
use App\Models\System;
use Mockery\Undefined;
use stdClass;

class SaveSalesData extends Command
{
    const SCREEN_NAME = 'batch-save-sales';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:save-sales-data {customerId? : 得意先ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '売上に保存されていない納品・返品の売上データを作成する。';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->log = new Logger($this->signature);
        $log_path =  storage_path() .'/logs/batch/save-sales-data.log';     //出力ファイルパス
        $log_max_files =  config('app.log_max_files');                      //日別ログファイルの最大数
        $log_level =  config('app.log_level');                              // ログレベル
        $this->log->pushHandler(new RotatingFileHandler($log_path, $log_max_files, $log_level));
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        try {
            // タイムスタンプ用
            $nowDate = Carbon::now();
            $this->log->addInfo("売上集計バッチ実行",['dateTime' => $nowDate]);
    
            // バッチ引数取得
            $customer_id = $this->argument("customerId");

            $customerIdList  = null;
            if (empty($customer_id)) {
                // データ抽出
                $customerIdList = $this->searchTarget();
            } else {
                $customerIdClass = new stdClass;
                $customerIdClass->customer_id = $customer_id;

                $customerIdList = [];
                $customerIdList[] = $customerIdClass;
            }

            
            // 通知データ登録
            // DB::beginTransaction();

            // 売上データ作成
            $noticeList = [];
            $num = 0;
            if (!empty($customerIdList)) {
                foreach ($customerIdList as $key => $customerId) {
                    $saveResult = $this->createSales($customerId->customer_id);

                    if (count($saveResult['noticeList']) > 0) {
                        $noticeList = array_merge($noticeList, $saveResult['noticeList']);
                    }
                    if ($saveResult['status']) {
                        $num += 1;
                    }
                }
            }

            if (count($noticeList) > 0) {
                DB::beginTransaction();
                $cnt = $this->createNotice($noticeList);
                DB::commit();
            }

            // DB::commit();

            $this->log->addInfo("売上集計バッチ実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：save_sales_data".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"売上集計バッチ実行失敗");
            $this->log->error('売上集計バッチ実行失敗', ['error' => $e]);
        }
    }

    
    /**
     * 売上に保存されていない納品/返品取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget()
    {
        $data = null;

        $where1 = [];
        $where1[] = array('del.del_flg', '=', config('const.flg.off'));
        $where1[] = array('del.delivery_date', '>=', config('const.salesDeliveryValidDate'));
        $where1[] = array('c.use_sales_flg', '=', config('const.flg.off'));
        // $where1[] = array('req.sales_category', '=', config('const.sales_category.val.sales'));
        
        $deliveryData = DB::table('t_delivery AS del')
            ->leftJoin('t_sales_detail AS sd', function($join) {
                $join
                    ->on('sd.delivery_id', '=', 'del.id')
                    ->where('sd.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('t_sales AS s', 's.id', '=', 'sd.sales_id')
            ->leftJoin('t_request AS req', function($join) {
                $join
                    ->on('req.id', '=', 's.request_id')
                    // ->where('req.sales_category', '=', config('const.sales_category.val.sales'))
                    ;
            })
            ->leftJoin('t_matter AS m', 'm.id', '=', 'del.matter_id')
            ->leftJoin('m_customer AS c', 'c.id', '=', 'm.customer_id')
            ->select([
                // 'del.*',
                'c.id AS customer_id'
            ])
            ->where($where1)
            ->whereNull('sd.id')
            ->whereNotNull('c.id')
            ->distinct()
            ->orderBy('c.id')
            // ->get()
            ;


        $where2 = [];
        $where2[] = array('ret.del_flg', '=', config('const.flg.off'));
        $where2[] = array('ret.return_kbn', '!=', config('const.returnKbn.keep'));
        $where2[] = array('ret.return_finish', '=', config('const.returnFinish.val.completed'));
        $where2[] = array('ret.del_flg', '=', config('const.flg.off'));
        $where2[] = array('t_warehouse_move.to_product_move_at', '>=', config('const.salesReturnValidDate'));
        $where2[] = array('t_warehouse_move.delivery_id', '<>', 0);
        // $where2[] = array('req.sales_category', '=', config('const.sales_category.val.sales'));

        $returnData = DB::table('t_return AS ret')
            ->join('t_warehouse_move', 'ret.warehouse_move_id', '=', 't_warehouse_move.id')
            ->whereNotNull('t_warehouse_move.delivery_id')
            ->leftJoin('t_sales_detail AS sd', function($join) {
                $join
                    ->on('sd.return_id', '=', 'ret.id')
                    ->where('sd.del_flg', '=', config('const.flg.off'));
            })
            ->leftJoin('t_sales AS s', 's.id', '=', 'sd.sales_id')
            ->leftJoin('t_request AS req', function($join) {
                $join
                    ->on('req.id', '=', 's.request_id')
                    // ->where('req.sales_category', '=', config('const.sales_category.val.sales'))
                    ;
            })
            ->leftJoin('t_matter AS m', 'm.id', '=', 'ret.matter_id')
            ->leftJoin('m_customer AS c', 'c.id', '=', 'm.customer_id')
            ->select([
                // 'ret.*',
                'c.id AS customer_id'
            ])
            ->where($where2)
            ->whereNull('sd.id')
            ->whereNotNull('c.id')
            ->distinct()
            ->orderBy('c.id')
            // ->get()
            ;



        $data = $deliveryData->union($returnData)
                            ->distinct()
                            ->orderBy('customer_id')
                            ->get();

        return $data->toArray();
    }


    /**
     * 保存
     * @param Request $request
     * @return type
     */
    public function createSales($customerId)
    {
        $result = array('status' => false, 'message' => '', 'noticeList' => []);

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
        $System         = new System();
        $SystemUtil     = new SystemUtil();
        $Credited       = new Credited();

        // 請求情報格納
        $requestInfo    = [];
        $noticeList     = [];

        $requestInfo['customer_id']    = $customerId;

        if($this->lockTransaction($requestInfo['customer_id'], true)){

            // ロックを持っているorロックを誰も持っていない
            $lockReleaseFlg = false;
            DB::beginTransaction();

            try {
                $userId     = config('const.batchUser');         // 申請者
                $now        = Carbon::now();            // 確定日
                $systemList = $System->getByPeriod();
                $TAX_RATE   = $systemList['tax_rate'];

                // $tmpRequestInfo                 = $SystemUtil->getStrictCurrentMonthRequestPeriod($requestInfo['customer_id']);
                $tmpRequestInfo                 = $this->getLastRequestData($requestInfo['customer_id']);

                if (empty(Common::nullorBlankToZero($tmpRequestInfo->id))) {
                    // 過去の請求データなし(新規)
                    if(!isset($tmpRequestInfo->id)){
                        // $tmpDateData = $SystemUtil->getNewCustomerMonthRequestPeriod($params['customer_id'], $params['request_e_day']);
                        $tmpDateData = $SystemUtil->getNewCustomerStrictCurrentMonthRequestPeriod($requestInfo['customer_id'], $tmpRequestInfo['request_e_day']);
                        
                        $tmpRequestInfo->request_mon        = $tmpDateData['request_mon'];
                        $tmpRequestInfo->request_mon_text   = $tmpDateData['request_mon_text'];
                        $tmpRequestInfo->closing_day        = $tmpDateData['closing_day'];
                        $tmpRequestInfo->request_s_day      = $tmpDateData['request_s_day'];
                        $tmpRequestInfo->request_e_day      = $tmpDateData['request_e_day'];
                        $tmpRequestInfo->expecteddeposit_at = $tmpDateData['expecteddeposit_at'];
                        // $tmpRequestInfo->shipment_at        = (new Carbon($params['request_e_day']))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');   // 請求発想予定日
                        $tmpRequestInfo->status             = config('const.requestStatus.val.unprocessed');
                    }
                }

                $requestInfo['id']                  = $tmpRequestInfo->id;              // ID
                // $requestInfo['request_no']          = $tmpRequestInfo->request_no;      // 請求番号
                $requestInfo['customer_name']       = $tmpRequestInfo->customer_name;   // 得意先名  
                $requestInfo['charge_department_id']    = $tmpRequestInfo->charge_department_id;        // 担当部門ID
                $requestInfo['charge_department_name']  = $tmpRequestInfo->charge_department_name;      // 担当部門名
                $requestInfo['charge_staff_id']         = $tmpRequestInfo->charge_staff_id;             // 担当者ID
                $requestInfo['charge_staff_name']       = $tmpRequestInfo->charge_staff_name;           // 担当者名
                $requestInfo['request_mon']         = $tmpRequestInfo->request_mon;     // 請求月
                $requestInfo['closing_day']         = $tmpRequestInfo->closing_day;     // 締日
                $requestInfo['request_s_day']       = $tmpRequestInfo->request_s_day;   // 売上開始日
                $requestInfo['request_e_day']       = $tmpRequestInfo->request_e_day;   // 売上終了日
                
                $requestInfo['expecteddeposit_at']  = $tmpRequestInfo->expecteddeposit_at;
                $requestInfo['shipment_at']         = $tmpRequestInfo->shipment_at;

                $requestInfo['be_request_e_day']    = $tmpRequestInfo->be_request_e_day;// 変更前の売上終了日
                $requestInfo['receivable']          = Common::nullorBlankToZero($tmpRequestInfo->receivable);           // 売掛金
                $requestInfo['different_amount']    = Common::nullorBlankToZero($tmpRequestInfo->different_amount);     // 違算
                $requestInfo['carryforward_amount'] = Common::nullorBlankToZero($tmpRequestInfo->carryforward_amount);  // 繰越金額
                $requestInfo['purchase_volume']     = 0;    // 当月仕入高(計算)
                $requestInfo['sales']               = 0;    // 当月売上高(計算)
                $requestInfo['additional_discount_amount']  = 0;// 値引き追加額(計算)
                $requestInfo['consumption_tax_amount']      = 0;// 消費税額(計算)   未使用
                $requestInfo['total_sales']         = 0;        // 売上合計(計算)
                // $requestInfo['display_total_sales']         = 0;        // 売上合計(表示)
                // $requestInfo['display_consumption_tax_amount']         = 0;        // 消費税額(表示)
                $requestInfo['request_amount']      = 0;        // 請求金額(計算)
                $requestInfo['sales_category']      = config('const.sales_category.val.sales');
                $requestInfo['status']              = $tmpRequestInfo->status;


                // 前回請求金額、入金金額、相殺その他
                // $tmpRequestAmount                   = $this->setRequestAmount($requestInfo['customer_id'], $requestInfo['request_s_day'], $requestInfo['request_e_day']);
                $requestInfo['lastinvoice_amount']  = 0;     // 前回請求金額
                $requestInfo['deposit_amount']      = 0;    // 入金金額
                $requestInfo['offset_amount']       = 0;     // 相殺その他


                // // 請求のエラーチェック
                // $errMessage = $this->chkRequestData($requestInfo, $requestInfo['id'], $requestInfo['request_s_day']);
                // if($errMessage !== ''){
                //     $result['message'] = $errMessage;
                //     throw new \Exception();
                // }

                
                // 請求データ作成/更新
                if(!isset($requestInfo['id']) || Common::nullorBlankToZero($requestInfo['id']) == 0){
                    // 新規請求
                    $requestSaveData    = $requestInfo;
                    unset($requestSaveData['id']);
                    unset($requestSaveData['display_total_sales']);
                    unset($requestSaveData['display_consumption_tax_amount']);
                    $requestInfo['id'] = $Requests->addToSalesBatch($requestSaveData);
                }

                // 申請中の売上リスト(キーは案件ID)
                $salesApplyingList  = [];

                // 案件データ取得
                $salesInfo          = $Matter->getSalesListData($requestInfo);

                /* 売上データの登録更新 */
                /* 案件の数だけループ */
                foreach($salesInfo as $cnt => $matter){
                    $salesData = $Sales->getSalesDetailDataList($matter->matter_id, $matter->matter_no, $matter->quote_id, $requestInfo['id']);
                    $salesList = $salesData['salesDataList'];
                    $salesDetailList = $salesData['salesDetailDataList'];

                    // 売上データの新規登録
                    foreach($salesList as $key => $row){
                        $salesId                = $row['sales_id'];
                        $filterTreePath         = $row['filter_tree_path'];
                        
                        if(count($salesDetailList[$filterTreePath]) >= 1){
                            $row['matter_id']       = $matter->matter_id;
                            $row['matter_department_id']    = $matter->department_id;
                            $row['matter_staff_id'] = $matter->staff_id;
                            $row['quote_id']        = $matter->quote_id;
                            $row['update_cost_unit_price_d']    = empty($row['update_cost_unit_price_d']) ? null : $row['update_cost_unit_price_d'];
                            if(Common::nullorBlankToZero($salesId) === 0){
                                // 新規
                                $row['request_id']  = $requestInfo['id'];
                                $row['status_dep']  = $requestInfo['charge_department_id'];
                                $row['sales_id']    = $Sales->addToSalesBatch($row);
                            }
                            $salesList[$key] = $row;


                            // 承認が終わっていない申請中のデータのチェック
                            if($row['status'] === config('const.salesStatus.val.applying')){
                                if(!isset($salesApplyingList[$matter->matter_id])){
                                    $salesApplyingList[$matter->matter_id] = $matter->owner_name;
                                }
                            }
                        }
                    }


                    // 売上確定済み以降に納品されたフラグ
                    $noticeFlg                  = false;

                    // 売上明細の登録更新
                    foreach($salesList as $key => $salesRow){
                        $salesId            = $salesRow['sales_id'];
                        $filterTreePath     = $salesRow['filter_tree_path'];
                        $salesTotal = 0;
                        $costTotal  = 0;

                        // 期間外の納品が存在するフラグ
                        $outsideSalesPeriodExistFlg = false;
                        // 未確定の未納フラグ
                        $createNotDeliveryExistFlg  = false;

                        // 売上明細が無い場合は処理をしない(無駄な登録/削除を減らすための条件)
                        if(count($salesDetailList[$filterTreePath]) === 0){
                            continue;
                        }

                        // 売上明細の登録更新 売上の金額計算
                        foreach($salesDetailList[$filterTreePath] as $key => $salesDetailRow){
                            if(Common::nullToBlank($salesDetailRow['sales_update_date']) !== ''){
                                // 確定分はスキップ
                                continue;
                            }
                            $salesDetailRow['sales_id']     = $salesId;
                            $salesDetailRow['matter_id']    = $matter->matter_id;
                            $salesDetailRow['quote_id']     = $matter->quote_id;
                            
                            // 売上明細ID
                            $salesDetailId = $salesDetailRow['sales_detail_id'];

                            switch($salesDetailRow['sales_flg']) {
                                case config('const.salesFlg.val.discount'):
                                case config('const.salesFlg.val.production'):
                                case config('const.salesFlg.val.cost_adjust'):
                                    // 値引き、その他、調整金額明細の親は請求IDを更新する
                                    $salesRow['request_id']  = $requestInfo['id'];
                                    break;
                            }


                            // チェック用のフラグ
                            if($salesDetailRow['sales_flg'] === config('const.salesFlg.val.not_delivery')){
                                // 未確定の未納データ
                                $createNotDeliveryExistFlg = true;
                            }


                            
                            // 売上データ用の金額計算
                            if($this->isSalesPeriod($salesDetailRow['sales_date'], $requestInfo['request_s_day'], $requestInfo['request_e_day'])){
                                if($salesDetailRow['notdelivery_flg'] !== config('const.notdeliveryFlg.val.invalid')){
                                    $salesTotal += Common::nullorBlankToZero($salesDetailRow['sales_total']);
                                    $costTotal  += Common::nullorBlankToZero($salesDetailRow['cost_total']);
                                }

                                switch($salesDetailRow['sales_flg']){
                                    case config('const.salesFlg.val.discount'):
                                        // 当月の値引き額
                                        $requestInfo['additional_discount_amount'] += Common::nullorBlankToZero($salesDetailRow['sales_total']);
                                        break;
                                }
                                $salesDetailRow['request_id']           = $requestInfo['id'];
                                // $salesDetailRow['sales_update_date']    = $now;
                                // $salesDetailRow['sales_update_user']    = $userId;
                            }else{
                                $salesDate      = new Carbon($salesDetailRow['sales_date']);
                                $requestSDay    = new Carbon($requestInfo['request_s_day']);
                                // if($requestSDay->gt($salesDate)){
                                //     // 売上日が開始日よりも前になってますん
                                //     $result['message'] = config('message.error.sales.not_sales_date');
                                //     $result['message'] .= '\n・'.$matter->owner_name;
                                //     throw new \Exception();
                                // }
                                
                                // チェック用のフラグ
                                if($salesDetailRow['sales_flg'] === config('const.salesFlg.val.delivery')){
                                    // 期間外の納品データ
                                    $outsideSalesPeriodExistFlg = true;
                                }
                            }
                            if(Common::nullorBlankToZero($salesDetailId) === 0){
                                // 新規
                                $salesDetailRow['sales_detail_id']      = $SalesDetail->addToSalesBatch($salesDetailRow);

                                if ($requestInfo['status'] >= config('const.requestStatus.val.complete') && $this->isSalesPeriod($salesDetailRow['sales_date'], $requestInfo['request_s_day'], $requestInfo['request_e_day'])) {
                                    // ステータスが売上確定以降に納品が作成された場合に通知する。
                                    $noticeFlg = true;
                                }
                            }else{
                                // 編集
                                $salesDetailRow['update_user'] = config('const.batchUser');
                                $SalesDetail->updateById($salesDetailId, $salesDetailRow);
                            }

                            //$salesDetailList[$filterTreePath][$key]     = $salesDetailRow;
                        }

                        // if($outsideSalesPeriodExistFlg && $createNotDeliveryExistFlg){
                        //     // 未確定の未納データがあるため納品データの売上日を売上期間外には設定できません
                        //     $result['message'] =  config('message.error.sales.outside_period_sales_date');
                        //     throw new \Exception();
                        // }



                        // 粗利
                        $salesRow['profit_total']             = $SystemUtil->calcProfitTotal($salesTotal, $costTotal);
                        // 粗利率
                        $salesRow['gross_profit_rate']        = $SystemUtil->calcRate($salesRow['profit_total'], $salesTotal);

                        // 請求用の売上総額
                        $requestInfo['sales']           += $salesTotal;
                        $requestInfo['purchase_volume'] += $costTotal;

                        // 担当部門、担当者更新
                        $salesRow['matter_department_id']   = $matter->department_id;
                        $salesRow['matter_staff_id']        = $matter->staff_id;

                        $salesRow['update_user']        = config('const.batchUser');


                        // 売上データの更新
                        if($salesRow['sales_flg'] !== config('const.salesDetailFlg.val.quote')){
                            $tmpSalesDetailRow = $salesDetailList[$salesRow['filter_tree_path']][0];
                            if(Common::nullToBlank($tmpSalesDetailRow['sales_update_date']) !== ''){
                                // 確定行
                            }else{
                                // 更新
                                $Sales->updateById($salesId, $salesRow);
                            }
                        }else{
                            // 更新
                            $Sales->updateById($salesId, $salesRow);
                        }

                        $salesList[$key] = $salesRow;
                    }

                    // 売上明細が紐づいていない売上を削除する
                    // 当月の子グリッドが無くても見積明細に紐づく過去の売上明細があれば保存対象とされるので削除
                    // 未来に伸ばした納品が当月に含まれた場合に紐づかなくなった過去の売上データも削除する
                    $Sales->deleteNoRelatedSalesData($matter->matter_id);

                    
                    // 通知作成
                    $noticeData = null;
                    if ($noticeFlg) {
                        $noticeData['staff_id'] = $matter->staff_id;
                        $noticeData['content']  = $matter->matter_name. '：請求に含まれていない納品データがあります。確認してください。';
                        $noticeData['redirect_url'] = '/sales-list?customer_id='. $customerId;

                        $noticeList[] = $noticeData;
                    }
                }


                // 承認が終わっていない申請中のデータ
                if(count($salesApplyingList) >= 1){
                    $result['message'] = config('message.error.sales.applying');
                    foreach($salesApplyingList as $name){
                        $result['message'] .= '\n・'.$name;
                    }
                    throw new \Exception();
                }


                // // 請求の更新
                // // 違算、売掛、繰越
                // $tmpCarryForwardAmount              = $this->getCarryforwardAmount($requestInfo['customer_id']);
                // $requestInfo['different_amount']    = $tmpCarryForwardAmount['different_amount'];
                // $requestInfo['receivable']          = $tmpCarryForwardAmount['receivable'];
                // $requestInfo['carryforward_amount'] = $tmpCarryForwardAmount['carryforward_amount'];

                // // 税額
                // $requestInfo['consumption_tax_amount']    = $this->roundTax($requestInfo['sales'] * ($TAX_RATE/100));
                // // 税込当月売上合計 = 当月売上高 + 税額 + 税調整額 + 違算 + (-相殺)
                // $requestInfo['total_sales']               = $requestInfo['sales'] + $requestInfo['consumption_tax_amount'] + $requestInfo['discount_amount'] + $requestInfo['different_amount'] + ($tmpCarryForwardAmount['payment_offset'] * -1);
                // // 税込当月請求合計 = 税込当月売上合計 + 売掛金
                // $requestInfo['request_amount']            = $requestInfo['total_sales'] + $requestInfo['receivable'];

                // $Requests->updateById($requestInfo['id'], $requestInfo);


                // ロック解除
                if($this->unLock($requestInfo['customer_id'])){
                    DB::commit();
                    $result['status'] = true;
                    $result['noticeList'] = $noticeList;
                } else {
                    DB::rollBack();
                    $result['status'] = false;
                    $result['message'] = config('message.error.getlock');
                }
                $lockReleaseFlg = true;

            } catch (\Exception $e) {
                DB::rollBack();
                $result['status'] = false;
                if($result['message'] === ''){
                    $result['message'] = config('message.error.error');
                }
                $this->log->error('売上集計バッチ実行失敗', ['customerId' => $customerId, 'error' => $e]);
            } finally {
                if (!$lockReleaseFlg) {
                    // ロックの解除
                    if(!$this->lockTransaction($requestInfo['customer_id'], false)){
                        // ロック解除に失敗
                    }
                }
            }
        }else{
            $result['message'] = config('message.error.getlock');
        }

        return $result;
    }

    /**
     * 通知データ作成
     * ステータスが【1:売上確定】以降に納品された場合
     *
     * @param [type] $data
     * @param [type] $now
     * @return int
     */
    private function createNotice($data)
    {
        //データが空の場合終了
        if(empty($data)){
           return 0;
        }
        // データ件数取得
        $datacount = count($data);

        $insertList = [];

        //通知データ成型
        foreach ($data as $param) {
                $insertList[] = [
                    'notice_flg'    => config('const.flg.off'),
                    'staff_id'      => $param['staff_id'],
                    'content'       => $param['content'],
                    'redirect_url'  => $param['redirect_url'],
                    'del_flg'       => config('const.flg.off'),
                    'created_user'  => config('const.batchUser'),
                    'created_at'    => Carbon::now(),
                    'update_user'   => config('const.batchUser'),
                    'update_at'     => Carbon::now(),
                ];
        }
        DB::table('t_notice')->insert($insertList);
        
        return $datacount;
    }

    /**
     * 最新の請求取得
     *
     * @return void
     */
    public function getLastRequestData($customerId) {

        $Customer       = new Customer();
        $Requests       = new Requests();
        $Credited       = new Credited();
        $SystemUtil     = new SystemUtil();
        $DAY = Carbon::now()->subDay();

        $customerData   = $Customer->getChargeData($customerId);
        $requestInfo    = $Requests->getLatestData($customerId);

        if ($requestInfo == null) {
            $requestInfo                = $Requests;
            Common::initModelProperty($requestInfo);
            $tmpDateData    = $SystemUtil->getCurrentMonthRequestPeriod($customerId, $DAY);
            $requestInfo->request_mon   = $tmpDateData['request_mon'];
            $requestInfo->closing_day   = $tmpDateData['closing_day'];
            $requestInfo->request_s_day = $tmpDateData['request_s_day'];
            $requestInfo->request_e_day = $tmpDateData['request_e_day'];

            // 請求データにはない回収サイトの情報を追加
            $requestInfo->collection_sight  = $customerData->collection_sight;
            $requestInfo->collection_day    = $customerData->collection_day;

            // 請求データを再表示しない場合は、得意先の情報をセット
            // if (!$activeRequestFlg) {
            // 得意先の情報をセット
            $requestInfo->customer_id           = $customerData->id;
            $requestInfo->customer_name         = $customerData->customer_name;
            $requestInfo->be_request_e_day      = $requestInfo->request_e_day;
            $requestInfo->status                = config('const.requestStatus.val.unprocessed');

            // 請求書発送予定日を算出 売上終了日の3日後
            $requestInfo->shipment_at           = (new Carbon($requestInfo->request_e_day))->addDay(config('const.shipmentAtAddDay'))->format('Y/m/d');

            // 入金予定日を算出
            $currentCollectionDay = '';
            switch (Common::nullorBlankToZero($requestInfo->collection_day)) {
                case config('const.customerCollectionDay.val.any_time'):
                case config('const.customerCollectionDay.val.month_end'):
                    $tmpCurrentCollectionDay    = $SystemUtil->formatRequestMon($requestInfo->request_mon, false, 1);
                    if (Common::nullorBlankToZero($requestInfo->collection_sight) !== 0) {
                        // 改修サイトの月数だけ増やす
                        $tmpCurrentCollectionDay = (new Carbon($tmpCurrentCollectionDay))->addMonth($requestInfo->collection_sight)->format('Y/m/d');
                    }
                    $currentCollectionDay       = (new Carbon($tmpCurrentCollectionDay))->endOfMonth()->format('Y/m/d');              // 今月末
                    break;
                default:
                    $currentCollectionDay = $SystemUtil->formatRequestMon($requestInfo->request_mon, false, $requestInfo->collection_day);
                    if (Common::nullorBlankToZero($requestInfo->collection_sight) !== 0) {
                        // 改修サイトの月数だけ増やす
                        $currentCollectionDay   = (new Carbon($currentCollectionDay))->addMonth($requestInfo->collection_sight)->format('Y/m/d');
                    }
                    break;
            }

            $requestInfo->expecteddeposit_at    = $currentCollectionDay;
            // }

            // 得意先担当部門、担当者を再取得
            $requestInfo->charge_department_id  = $customerData->charge_department_id;
            $requestInfo->charge_department_name = $customerData->department_name;
            $requestInfo->charge_staff_id       = $customerData->charge_staff_id;
            $requestInfo->charge_staff_name     = $customerData->staff_name;

            // 請求月を日本語化
            $requestInfo->request_mon_text = '';
            $requestInfo->request_mon_text       = $SystemUtil->formatRequestMon($requestInfo->request_mon, true);

            $NOW = Carbon::now();
            $requestSDay = new Carbon($requestInfo->request_s_day);
            $requestEDay = new Carbon($requestInfo->request_e_day);
            // 今日が開始日になっているか
            $requestInfo->is_sales_started = true;
            // if ($requestSDay->gt($NOW)) {
            //     // 今日が開始日になっていない
            //     $requestInfo->is_sales_started = false;
            // }

            // 一時削除された請求取得
            $requestInfo->is_temporary_deleted = false;
            $tmpDeleteRequestData = $Requests->getTemporaryDeleteRequestData($requestInfo->customer_id);
            if ($tmpDeleteRequestData !== null) {
                // 一時削除データ存在した場合は売上期間変更不可
                $requestInfo->is_temporary_deleted = true;
            }
        }

        return $requestInfo;
    }

     /**
     * 期間内か
     * @param $salesDate
     * @param $requestSDay
     * @param $requestEDay
     */
    private function isSalesPeriod($salesDate, $requestSDay, $requestEDay){
        $result = false;
        $salesDate       = new Carbon($salesDate);
        $requestSDay    = new Carbon($requestSDay);
        $requestEDay    = new Carbon($requestEDay);
        if($salesDate->gte($requestSDay) && $salesDate->lte($requestEDay)){
            $result = true;
        }
        return $result;
    }

    /**
     * 請求データのチェック
     * @param $requestInfo  請求データの連想配列
     * @param $requestId    検索時に保持していた請求ID
     * @param $screenRequestSDay    画面上の売上開始日
     */
    private function chkRequestData($requestInfo, $requestId, $screenRequestSDay){
        $errMessage     = '';
        $Requests       = new Requests();

        $requestSDay        = new Carbon($requestInfo['request_s_day']);
        $requestEDay        = new Carbon($requestInfo['request_e_day']);
        $expecteddepositAt  = new Carbon($requestInfo['expecteddeposit_at']);
        $toDay              = new Carbon(Carbon::now()->format('Y/m/d'));


        if(empty($requestInfo['request_e_day'])){
            // 売上終了日が空白
            $errMessage = config('message.error.sales.required_request_e_day');
        }else if(empty($requestInfo['expecteddeposit_at'])){
            // 入金予定日が空白
            $errMessage = config('message.error.sales.required_expecteddeposit_at');
        }else if($requestSDay->gt($requestEDay)){
            // 開始日と終了日が逆転している
            $errMessage = config('message.error.sales.sales_date');
        }else if($toDay->gt($expecteddepositAt)){
            // 入金予定日が今日より前
            $errMessage = config('message.error.sales.expecteddeposit_at');
        }else{
            if($Requests->isExistByRequestSDay($requestInfo['customer_id'], $requestId, $screenRequestSDay)){
                // 同じ開始日の請求データがある(画面を開いている間に誰かが作成した)
                $errMessage = config('message.error.sales.exist_request_data');
            }else if(Common::nullorBlankToZero($requestId) != 0){
                $requestData = $Requests->getById($requestId);
                if($requestData == null || $requestData->status === config('const.requestStatus.val.complete')){
                    // 請求データが消えている or 既に確定している
                    $errMessage = config('message.error.sales.complete_request_status');
                }
            }
        }
        return $errMessage;
    }


    
    /**
     * ロック処理まとめ(別トランザクション)
     * @param $customerId
     * @param $isGetLock    true:自身がロックを持っていなければロックを取得する　false:ロックを解除する
     * @return false        失敗
     */
    private function lockTransaction($customerId, $isGetLock=true){
        $result = true;

        DB::beginTransaction();
        try{
            if($isGetLock){
                // ロックを持っているか確認
                if(!$this->isOwnLocked($customerId)){
                    if(!$this->getLock($customerId)){
                        // 他の人がロックを持っている
                        $result = false;
                        throw new \Exception();
                    }
                }
            }else{
                // ロック解除
                if(!$this->unLock($customerId)){
                    $result = false;
                    throw new \Exception();
                }
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
        return $result;
    }

    /**
     * ロック獲得
     * @param Datetime $lockDt ロック日時
     * @param string $screenName 画面名
     * @param string $tableName テーブル名
     * @param int $key キー
     * @param boolean $unlockFlg ロック解除フラグ
     * @return boolean True:成功 False:失敗 
     */
    public function gainLock($lockDt, $screenName, $tableName, $key, $unlockFlg = false) {
        $result = false;

        $LockManage = new LockManage();
        $LogUtil = new LogUtil();
        try {
            // WHERE句
            $where = [];
            $where['table_name'] = $tableName;
            $where['key_id'] = $key;

            if ($unlockFlg) {
                // ロックデータ取得
                $lockData = DB::table('t_lock_manage')
                            ->where($where)
                            ->first();
                if (!is_null($lockData)) {
                    
                    // WHERE句
                    $where2 = [];
                    $where2['screen_name'] = $lockData->screen_name;
                    $where2['lock_user_id'] = $lockData->lock_user_id;
                    $where2['lock_dt'] = $lockData->lock_dt;

                    // ログテーブルへ書き込み
                    // $LogUtil->putByData($lockData, config('const.logKbn.delete'));
                    $LogUtil->putByWhere('t_lock_manage', $where2, config('const.logKbn.delete'));

                    // ロックデータ削除
                    DB::table('t_lock_manage')
                        ->where($where2)
                        ->delete();
                }
            }

            // 登録
            $LockManage->insert([
                'screen_name' => $screenName,
                'table_name' => $tableName,
                'key_id' => $key,
                'lock_user_id' => config('const.batchUser'),
                'lock_dt' => $lockDt,
            ]);

            // TODO:ユニーク制約つければカウント要らないかも
            // WHERE句
            $where = [];
            $where['table_name'] = $tableName;
            $where['key_id'] = $key;
            
            // データ件数取得
            $count = DB::table('t_lock_manage')
                    ->where($where)
                    ->count()
                    ;

            if ($count === 1) {
                $result = true;
            } else {
                $result = false;
            }
        } catch (\Exception $e) {
            $result = false;
            throw $e;
        }
        return $result;
    }

    /**
     * ロック取得
     * @param $customerId
     * @return false 誰かがロックしている
     */
    private function getLock($customerId){
        $result = false;

        $LockManage = new LockManage();

        // 画面名から対象テーブルを取得
        $tableNameList = config('const.lockList.'.self::SCREEN_NAME);
        $keys = array(intval($customerId));

        $lockCnt = 0;
        foreach ($tableNameList as $i => $tableName) {
            // ロック確認
            $isLocked = $LockManage->isLocked($tableName, $keys[$i]);

            // if (!$isLocked) {
            // 編集モードかつ、ロックされていない場合はロック取得
            $lockDt = Carbon::now();
            $this->gainLock($lockDt, self::SCREEN_NAME, $tableName, $keys[$i], $isLocked);
            // }

            // ロックデータ取得
            $lockData = $LockManage->getLockData($tableName, $keys[$i]);
            if (!is_null($lockData) 
                && $lockData->screen_name === self::SCREEN_NAME && $lockData->table_name === $tableName
                && $lockData->key_id === $keys[$i] && $lockData->lock_user_id === config('const.batchUser')) {
                    $lockCnt++;
            }
        }
        if (count($tableNameList) === $lockCnt) {
            $result = true;
        } 
        return $result;
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
        if(!$this->isOwnLocks($SCREEN_NAME, $tableNameList, $keys)){
            // throw new \Exception(config('message.error.getlock'));
            $result = false;
        }
        return $result;
    }

    /**
     * 自分がロックしているか確認
     * @param string $screenName 画面名
     * @param array $tableNameList テーブル名リスト
     * @param int $keys キーリスト
     * @return boolean True:全てのテーブルをロック中 False:ロックしていない 
     */
    public function isOwnLocks($screenName, $tableNameList, $keys){
        $lockCnt = 0;
        foreach ($tableNameList as $i => $tableName) {
            // ロックデータ取得
            $lockData = $this->isCustomerOwnLocked($screenName, $tableName, $keys[$i]);
            if ($lockData){
                $lockCnt++;
            }
        }
        return count($tableNameList) === $lockCnt;
    }

    /**
     * 自分がロックしているか確認
     * @param string $screenName 画面名
     * @param string $tableName テーブル名
     * @param int $key キー
     * @return boolean True:ロック中 False:ロックしていない 
     */
    public function isCustomerOwnLocked($screenName, $tableName, $key) {
        $where = [];
        $where['screen_name'] = $screenName;
        $where['table_name'] = $tableName;
        $where['key_id'] = $key;
        $where['lock_user_id'] = config('const.batchUser');
        
        $count = DB::table('t_lock_manage')
                ->where($where)
                ->count()
                ;
        
        return ($count === 1);
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
        if(!$LockManage->deleteLockInfo($SCREEN_NAME, $keys, config('const.batchUser'))){
            $result = false;
        }
        return $result;
    }
    

}
