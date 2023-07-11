<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertNotOrdering extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-not-ordering {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '発注予定日を過ぎても、引当データ（発注or在庫引当）が存在しない場合、アラートデータを作成する。但し、アラートデータは1案件につき1つ。';

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
         $log_path =  storage_path() .'/logs/batch/alert-not-ordering.log'; //出力ファイルパス
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
            $this->log->addInfo("未発注アラートバッチ実行",['dateTime' => $nowDate]);

            // バッチ引数取得
            $executeDate = $this->argument("setDate");
            if(empty($executeDate)){
                $executeDate = Carbon::today();
            }else{
                $executeDate = new Carbon($executeDate);//2020-12-14
            }


            // データ抽出
            $dataList = $this->searchTarget($executeDate);

            $noticeList = $this->searchNoticeTarget($executeDate);
            // dd($NoticeList);
            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$noticeList,$nowDate);
            DB::commit();

            $this->log->addInfo("未発注アラート通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-not-ordering".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"未発注アラートバッチ実行失敗");
            $this->log->error('未発注アラート通知実行失敗', ['error' => $e]);
        }
    }


    
    /**
     *データ取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate)
    {

        $data = DB::table('t_matter_detail as main')
            ->distinct()
            ->select([
                DB::raw("COALESCE(t_quote_detail.stock_quantity, 0) as stock_quantity"),
                DB::raw("COALESCE(t_reserve.reserve_quantity, 0) as reserve_quantity"),
                DB::raw("COALESCE(henpin.quantity, 0) as quantity"),
            ])
            ->where([
                ['main.type', '=', config('const.matterTaskType.val.order_timing')],
                ['main.start_date', '<', $executeDate->format('Y-m-d H:i:s')],
            ])
            ->join("t_matter", function ($join) {
                $join->on('main.matter_id', '=', 't_matter.id')
                ->where([
                    ['t_matter.complete_flg', '=', config('const.flg.off')]
                ]);
            })
            ->join('t_quote_detail', 't_quote_detail.id', '=', 'main.quote_detail_id')
            ->leftJoin('t_reserve', 't_reserve.quote_detail_id', '=', 'main.quote_detail_id')
            ->leftJoin("t_warehouse_move as henpin", function ($join) {
                $join->on("henpin.quote_detail_id","=","main.quote_detail_id")
                ->select([
                    'henpin.quote_detail_id',
                    DB::raw("SUM(henpin.quantity) as quantity"),
                ])
                ->groupby('henpin.quote_detail_id')
                ;
            })
            ->where([
                [DB::raw("(COALESCE(stock_quantity, 0) - COALESCE(reserve_quantity, 0) + COALESCE(quantity, 0))"), '>', 0],
            ])
            ->select([
                'main.matter_id',
                't_matter.matter_name',
                't_matter.staff_id',
                DB::raw("CONCAT('【発注遅延】',  t_matter.matter_name) as content"),
                // DB::raw("CONCAT(/order-edit/) as redirect_url"),
                DB::raw("CONCAT('/order-edit/', t_matter.id) as redirect_url"),
            ])
            ->get()
            ->toArray()
        ;
        
        return $data;
    }


    /**
     *通知先データ取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchNoticeTarget($executeDate)
    {
    $subQuery = DB::table('t_notice_setting')
        ->select([
            't_notice_setting.id'
        ])
        ->where([
            ['t_notice_setting.send_alert', '=', config('const.flg.on')],// アラートflag
            ['t_notice_setting.del_flg', '=', config('const.flg.off')],
            ['t_notice_setting.type', '=', config('const.notice_type.order_fixing')],
            ['t_notice_setting.from_department_id', '=', config('const.flg.off')],
            ['t_notice_setting.start_date', '<=', $executeDate->format('Y-m-d')],
        ])
        ->orderBy('start_date', 'desc')
        ->limit(1)
    ;

    $data = DB::table(DB::raw("(" . $subQuery->toSql() . ") as notice_setting"))
        ->mergeBindings($subQuery)
        ->select([
            'notice_setting.id',
            'notice_setting_detail.to_staff_id',
        ])
        ->leftJoin('t_notice_setting_detail as notice_setting_detail', 'notice_setting.id', '=', 'notice_setting_detail.notice_setting_id')
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
    private function createNotice($data,$noticeList,$nowDate)
    {
        //データが空の場合終了
        if(empty($data)){
           return 0;
        }
        // データ件数取得
        $datacount = 0;
        $insertList = [];

        //通知対象データ用
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
            $datacount++;
            //通知先データ用
            foreach ($noticeList as $t) {
                $insertList[] = [
                    'notice_flg' => config('const.flg.on'),
                    'staff_id' => $t->to_staff_id,
                    'content' => $param->content,
                    'redirect_url' => $param->redirect_url,
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
