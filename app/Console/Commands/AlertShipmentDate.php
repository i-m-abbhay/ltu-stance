<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertShipmentDate extends Command
{
    protected $log;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-shipment-date {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '出荷予定日を過ぎても出荷がない場合、通知データを作成';

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
        $log_path =  storage_path() .'/logs/batch/alert-shipment-date.log'; //出力ファイルパス
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
            $this->log->addInfo("出荷予定日超過通知バッチ実行",['dateTime' => $nowDate]);

            // バッチ引数取得
            $executeDate = $this->argument("setDate");
            if(empty($executeDate)){
                $executeDate = Carbon::today();
            }else{
                echo $executeDate;
                $executeDate = new Carbon($executeDate);//2020-12-14
            }
            
            // 超過データ取得
            $dataList = $this->searchTarget($executeDate);
            
            // 通知データ作成
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$nowDate);
            DB::commit();
            
            $this->log->addInfo("出荷予定日超過通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-shipment-date".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"出荷予定日超過通知実行失敗");
            $this->log->error('出荷予定日超過通知実行失敗', ['error' => $e]);
        }
    }

    
    /**
     * 出荷予定超過データ取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate)
    {
        // 出荷指示の出荷予定日を過ぎても出荷データがないケースを抽出する
        $data = DB::table('t_shipment')
            ->distinct()
            ->join('t_shipment_detail', function ($join) {
                $join->on('t_shipment.id', '=', 't_shipment_detail.shipment_id')
                     ->where('t_shipment_detail.loading_finish_flg', '=', config('const.flg.off'));
            })
            ->join('t_matter', 't_shipment.matter_id', '=', 't_matter.id')
            ->where([
                ['t_shipment.loading_finish_flg', '=', config('const.flg.off')],
                ['t_shipment.delivery_date', '<', $executeDate->format('Y-m-d H:i:s')],
            ])
            ->select([
                't_matter.staff_id as staff_id',
                DB::raw("CONCAT('【出荷遅延】', t_matter.matter_name) as content"),
                DB::raw("CONCAT('/shipping-delivery-list?matter_no=', t_matter.matter_no, '&issue_to_date=','".$executeDate->format('Y-m-d')."','&notLoadingFlg=1&endLoadingFlg=0&deliveryFinishFlg=0') as redirect_url"),
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
