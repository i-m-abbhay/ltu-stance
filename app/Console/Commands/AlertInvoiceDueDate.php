<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertInvoiceDueDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-Invoice-due-date {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '請求書発送予定をすぎて請求書印刷がされていない場合、通知データを作成';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->log = new Logger($this->signature);
        $log_path =  storage_path() .'/logs/batch/alert-Invoice-due-date.log'; //出力ファイルパス
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
            $this->log->addInfo("請求予定日超過バッチ実行",['dateTime' => $nowDate]);
    
            // バッチ引数取得
            $executeDate = $this->argument("setDate");
            if(empty($executeDate)){
                $executeDate = Carbon::today();
            }else{
                echo $executeDate;
                $executeDate = new Carbon($executeDate);//2020-12-14
            }

            // データ抽出
            $dataList = $this->searchTarget($executeDate);
            
            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$nowDate);
            DB::commit();

            $this->log->addInfo("請求予定日超過通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-Invoice-due-date".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"請求予定日超過バッチ実行失敗");
            $this->log->error('請求予定日超過バッチ実行失敗', ['error' => $e]);
        }
    }

    
    /**
     *請求予定日超過データ取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate)
    {
        $data = DB::table('t_request')
            ->distinct()
            ->select([
                DB::raw("t_request.charge_staff_id as staff_id"),
                DB::raw("CONCAT('【請求遅延】', t_request.customer_name) as content"),
                DB::raw("CONCAT('/request-list?request_no=', t_request.request_no,'&customer_id=',t_request.customer_id) as redirect_url"),
            ])
            ->whereNull("t_request.request_day")
            ->where([
                ['t_request.shipment_at', '<', $executeDate->format('Y-m-d')],
                ['t_request.status', '=',  config('const.requestStatus.val.complete')],
                ['t_request.del_flg', '=', config('const.flg.off')]
            ])
            ->get()
        ;
        return $data;
    }

    
    
    /**
     * 通知データ作成
     *
     * @param [type] $data
     * @param [type] $now
     * @return int
     */
    private function createNotice($data,$nowDate)
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
                'notice_flg' => config('const.flg.on'),
                'staff_id' => $param->staff_id,
                'content' => $param->content,
                'redirect_url' => $param->redirect_url,
                'del_flg' => config('const.flg.off'),
                'created_user' => config('const.batchUser'),
                'created_at' =>  $nowDate->format('Y-m-d H:i:s'),
                'update_user' => config('const.batchUser'),
                'update_at' =>  $nowDate->format('Y-m-d H:i:s'),
                ];
        }
        DB::table('t_notice')->insert($insertList);
        
        return $datacount;
    }
}
