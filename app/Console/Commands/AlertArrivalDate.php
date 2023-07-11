<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertArrivalDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-arrival-date {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '入荷予定日を過ぎても入荷がない場合、通知データを作成';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->log = new Logger($this->signature);
        $log_path =  storage_path() .'/logs/batch/alert-arrival-date.log'; //出力ファイルパス
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
            $this->log->addInfo("入荷予定日超過バッチ実行",['dateTime' => $nowDate]);
    
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

            $this->log->addInfo("入荷予定日超過通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-arrival-date".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"入荷予定日超過バッチ実行失敗");
            $this->log->error('入荷予定日超過バッチ実行失敗', ['error' => $e]);
        }
    }

    
    /**
     *入荷予定日超過データ取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate)
    {
        $data = DB::table('t_order_detail as order_detail')
            ->distinct()
            ->select([
                "t_order.order_staff_id as staff_id",
                DB::raw("CONCAT('【入荷遅延】',  t_matter.matter_name) as content"),
                DB::raw("CONCAT('/arrival-list?matter_no=', t_matter.matter_no, '&to_arrival_date=','".$executeDate->format('Y-m-d')."') as redirect_url"),
            ])
            ->join('t_order', 'order_detail.order_id', '=', 't_order.id')
            ->join('t_matter', 't_order.matter_no', '=', 't_matter.matter_no')
            ->whereRaw('order_detail.arrival_quantity < order_detail.order_quantity')
            ->where([
                ['order_detail.arrival_plan_date', '<', $executeDate->format('Y-m-d')],
            ])
            ->get();
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
