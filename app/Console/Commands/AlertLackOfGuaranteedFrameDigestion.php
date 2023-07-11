<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertLackOfGuaranteedFrameDigestion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-lack-of-guaranteed-frame-digestion {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '保証枠の30％以下の受注が3か月続いた得意先の通知データを作成';

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
        $log_path =  storage_path() .'/logs/batch/alert-lack-of-guaranteed-frame-digestion.log'; //出力ファイルパス
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
            $this->log->addInfo("保証枠消化不足アラートバッチ実行",['dateTime' => $nowDate]);

            // バッチ引数取得
            $executeDate = $this->argument("setDate");
            if(empty($executeDate)){
                $executeDate = Carbon::today();
            }else{
                $executeDate = new Carbon($executeDate);//2020-12-14
            }


            // データ抽出
            $noticeList = $this->searchTarget($executeDate);
            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($noticeList,$nowDate);
            DB::commit();

            $this->log->addInfo("保証枠消化不足アラート通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-lack-of-guaranteed-frame-digestion".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"保証枠消化不足バッチ実行失敗");
            $this->log->error('保証枠消化不足アラート通知実行失敗', ['error' => $e]);
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

        // 最新の与信枠取得用
        $subQuery = DB::table("m_trust_management as M1")
        ->select([
            'M1.id',
            'M1.customer_id',
            'M1.warranty_frame',
            ])
        ->leftJoin("m_trust_management as M2", function ($join) {
            $join->on("M1.customer_id","=","M2.customer_id")
                ->whereRaw(DB::raw("M1.line_of_credit_update_at < M2.line_of_credit_update_at"))
            ;
        })
        ->whereNull("M2.customer_id")
        ->where([
            ['M1.del_flg', '=', config('const.flg.off')],
            ])
        ;

        $data = DB::table('m_customer as M1')
        ->select([
            'M1.id',
            'M2.warranty_frame',

            DB::raw("M1.charge_staff_id as staff_id"),//(担当営業者id)
            'M3.chief_staff_id',//(部門長id)
            DB::raw("CONCAT('【売上不足】',  M1.customer_name) as content"),
            DB::raw("CONCAT('/') as redirect_url"),
            
        ])
        ->join('m_department as M3', 'M1.charge_department_id', '=', 'M3.id')
        ->join(DB::raw('('. $subQuery->toSql() .') as M2'), 'M1.id', '=', 'M2.customer_id')
        //サブクエリのWHERE句の値をバインド
        ->mergeBindings($subQuery)
        ->where([
            ['M1.use_sales_flg', '=', config('const.flg.off')],
            ['M1.del_flg', '=', config('const.flg.off')],
            ])
            ->get()
        ;


        // 債権管理対象の得意先3件（アップデート日で並び替え）
        $noticeList= [];
        $warrantyframeCheck = 0;
        foreach ($data as $key => $value) {
            $receivableList = DB::table('t_receivables_history as T1')
            ->select([
                'T1.receivable',
                ])
            ->where([
                ['T1.customer_id', '=', $value->id],
                ['T1.del_flg', '=', config('const.flg.off')],
                ])
                ->orderBy('update_at','desc')
                ->limit(3)
                ->get()
                ->toArray()
            ;


            if(!empty($receivableList)){
               
                // 取得した与信額と直近3件分の保証枠の30%を比較する
                foreach ($receivableList as $key => $receivable) {              
                    if($receivable->receivable < $value->warranty_frame*(30/100)){
                        $warrantyframeCheck++;
                    }
                }

                //三カ月下回った場合
                if($warrantyframeCheck >= 3){
                    $noticeList[] = $value;
                }
                $warrantyframeCheck = 0;
            }
           
        }
       
        return $noticeList;
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
            //(担当営業者)
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

            //(部門長)
            if($param->staff_id != $param->chief_staff_id ){
                $insertList[] = [
                    'notice_flg' => config('const.flg.on'),
                    'staff_id' => $param->chief_staff_id,
                    'content' => $param->content,
                    'redirect_url' => $param->redirect_url,
                    'del_flg' => config('const.flg.off'),
                    'created_user' => config('const.batchUser'),
                    'created_at' =>  $nowDate->format('Y-m-d H:i:s'),
                    'update_user' => config('const.batchUser'),
                    'update_at' =>  $nowDate->format('Y-m-d H:i:s'),
                    ];
            }
        }
        DB::table('t_notice')->insert($insertList);
        
        return $datacount;
    }
}
