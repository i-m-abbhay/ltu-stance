<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertQuoteRequestDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-quote-request-date {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '見積依頼の提出期限日を過ぎても、積算完了していない見積依頼項目が存在する場合、通知データを作成';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

       // ログ出力用
        $this->log = new Logger($this->signature);
        $log_path =  storage_path() .'/logs/batch/alert-quote-request-date.log'; //出力ファイルパス
        $log_max_files =  config('app.log_max_files'); //日別ログファイルの最大数
        $log_level =  config('app.log_level'); // ログレベル
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
            $this->log->addInfo("見積依頼期限超過バッチ実行",['dateTime' => $nowDate]);

            // バッチ引数取得
            $executeDate = $this->argument("setDate");
            if(empty($executeDate)){
                $executeDate = Carbon::today();
            }else{
                $executeDate = new Carbon($executeDate);//2020-12-14
            }

            // データ抽出
            $dataList = $this->searchTarget($executeDate);
            // 通知先データ作成
            $dataList = $this->setNoticeDestination($dataList,$executeDate);

            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$nowDate);
            DB::commit();
            
            $this->log->addInfo("見積依頼期限超過バッチ実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-quote-request-date".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"見積依頼期限超過バッチ実行失敗");
            $this->log->error('見積依頼期限超過バッチ実行失敗', ['error' => $e]);
        }
    }

    /**
     * 見積依頼の提出期限日を過ぎても、積算完了していない見積依頼項目が存在する場合を抽出する
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate){
        $dataList = DB::table('t_quote_request')
            ->distinct()
            ->select([
                't_quote_request.created_user',
                't_quote_request.staff_id',
                't_matter.matter_no',
                't_matter.matter_name',
                // DB::raw("CONCAT('【見積依頼超過】', t_matter.matter_name) as content"),
                // DB::raw("CONCAT('/quote-request-list?matter_no=', t_matter.matter_no) as redirect_url"),
                't_quote_request.quote_request_kbn',
                'quote_detail.construction_id',
                't_quote_request.id as quote_request_id'
            ])
            ->whereNotNull('t_quote_request.quote_limit_date')
            ->where([
                [DB::raw('DATE_SUB(t_quote_request.quote_limit_date, INTERVAL 1 DAY)'), '<', $executeDate->format('Y-m-d')],
                ['t_quote_request.status', '=', config('const.quoteRequestStatus.val.requested')],
                ['t_quote_request.del_flg', '=', config('const.flg.off')]
            ])

            ->join('t_matter', function ($join) {
                $join->on('t_quote_request.matter_no', '=', 't_matter.matter_no')
                    ->select([
                        't_matter.matter_no',
                        't_matter.matter_name',
                    ])
                    ->where([
                        ['t_matter.del_flg', '=', config('const.flg.off')],
                        ['t_matter.complete_flg', '=', config('const.flg.off')],
                        ['t_matter.own_stock_flg', '=', config('const.flg.off')],
                        ['t_matter.use_sales_flg', '=', config('const.flg.off')]
                    ]);
            })
            ->leftJoin('t_quote as quote', 't_matter.matter_no', '=', 'quote.matter_no')
            ->leftJoin('t_quote_detail as quote_detail', function ($join) {
                $join->on('quote_detail.quote_no', '=', 'quote.quote_no')
                    ->distinct()
                    ->select([
                        'quote_detail.quote_no ',
                        'quote_detail.construction_id',
                    ])
                    ->where([
                        ['quote_detail.depth', '=', config('const.flg.off')],
                        ['quote_detail.complete_flg', '=', config('const.flg.on')]
                    ]);
            })
            ->orderBy('t_matter.matter_no', 'asc')
            ->orderBy('t_quote_request.id', 'asc')
            ->get()
        ;

        //データが空の場合終了
        if(empty($dataList)){
            return 0;
         }
       
        // 清算完了していないデータ
        $targetList = [];
        $kbnList = json_decode($dataList[0]->quote_request_kbn);
        $prevRow = null;
        foreach ($dataList as $key => $row) {
            if(!empty($prevRow)){
                if ($prevRow->quote_request_id != $row->quote_request_id) {
                    // 見積依頼項目から積算完了した工事区分IDを除いて残りがある場合
                    if(!empty($kbnList))
                    {
                        // 1つ前のループのrowを追加 
                        $prevRow->kbnList = $kbnList;
                        $targetList[] = clone $prevRow;  
                    }
                    // 見積依頼項目で初期化する
                    $kbnList = json_decode($row->quote_request_kbn);
                }
            }
            
            foreach (json_decode($row->quote_request_kbn) as $item) {
                if($item == $row->construction_id){
                    //配列変数からitemを除外する
                    $tmp = array_diff($kbnList, array($item));

                    //indexを詰める
                    $tmp = array_values($tmp);
                    $kbnList = $tmp;
                    break;
                }
            }
            $prevRow = clone $row;
        }
        if(!empty($dataList)){
            $prevRow->kbnList = $kbnList;
            $targetList[] = clone $prevRow;
        }

        return $targetList;
    }


    /**
     * 通知先設定
     *
     * @param [type] $dataList
     * @return object
     */
    private function setNoticeDestination($dataList,$executeDate){
        
        // データが空の場合終了
        if(empty($dataList)){
            return 0;
         }

         $noticeData = [];
         
        // 通知データ作成
        foreach ($dataList as $row) {
            $noticeDestination = null;
            //通知先データ作成
            $staff_id_list=[];
            $staff_id_list[] = $row->created_user;
            $staff_id_list[] = $row->staff_id;

            // 工事区分ごとの通知先
            foreach ($row->kbnList as $kbn) {

                $subQuery = DB::table('t_notice_setting')
                ->select([
                    't_notice_setting.id',
                    't_notice_setting.kbn'
                ])
                ->where([
                    ['t_notice_setting.send_alert', '=', config('const.flg.on')],// アラートflag
                    ['t_notice_setting.del_flg', '=', config('const.flg.off')],
                    ['t_notice_setting.type', '=', config('const.notice_type.quote_request')],
                    ['t_notice_setting.from_department_id', '=', config('const.flg.off')],
                    ['t_notice_setting.kbn', '=', $kbn],//工事区分ID
                    ['t_notice_setting.start_date', '<=', $executeDate->format('Y-m-d')]
                ])
                ->orderBy('start_date', 'desc')
                ->limit(1)
                ;

                $noticeDestination = DB::table(DB::raw("(" . $subQuery->toSql() . ") as notice_setting"))
                ->mergeBindings($subQuery)
                ->select([
                    'notice_setting.id',
                    'notice_setting.kbn',
                    't_notice_setting_detail.to_staff_id'
                ])
                ->leftJoin('t_notice_setting_detail', 't_notice_setting_detail.notice_setting_id', '=', 'notice_setting.id')
                ->get()
                ;

                foreach($noticeDestination as $data){
                    if(!empty($data)){
                        $staff_id_list[] = $data->to_staff_id;
                    }
                }
            }

            // 重複削除
            $staff_id_list = array_values(array_unique($staff_id_list));

            // matter_noに紐づける
            if(empty($noticeData[$row->matter_no])){
                // 存在しない場合
                $noticeData[$row->matter_no] = [];
                $noticeData[$row->matter_no]['matter_no'] = $row->matter_no;
                $noticeData[$row->matter_no]['matter_name'] = $row->matter_name;
                $noticeData[$row->matter_no]['staff_id_list'] = $staff_id_list;
            }else{
                // 既存のstaff_id_listに追加
                array_merge($noticeData[$row->matter_no]['staff_id_list'],$staff_id_list);

                // 重複削除
                $noticeData[$row->matter_no]['staff_id_list'] = array_values(array_unique($noticeData[$row->matter_no]['staff_id_list']));
            }
            
        }
        return $noticeData;
    }

    /**
     * 通知データ登録
     *
     * @param [type] $data
     * @param [type] $now
     * @return int
     */
    private function createNotice($data,$nowDate){
        //データが空の場合終了
        if(empty($data)){
           return 0;
        }
        // データ件数取得
        $datacount = 0;

        $insertList = [];
        //通知データ成型
        // 各staff_id_list文だけ通知データを作成する
        foreach ($data as $row) {
            foreach ($row['staff_id_list'] as $staff_id) {
                $insertList[] = [
                    'notice_flg' => config('const.flg.on'),
                    'staff_id' => $staff_id,
                    'content' => '【見積依頼超過】'.$row['matter_name'],
                    'redirect_url' => '/quote-request-list?matter_no='.$row['matter_no'],
                    'del_flg' => config('const.flg.off'),
                    'created_user' => config('const.batchUser'),
                    'created_at' =>  $nowDate->format('Y-m-d H:i:s'),
                    'update_user' => config('const.batchUser'),
                    'update_at' =>  $nowDate->format('Y-m-d H:i:s'),
                ];
                $datacount++;
            }
        }
        DB::table('t_notice')->insert($insertList);
        
        return $datacount;
    }
}