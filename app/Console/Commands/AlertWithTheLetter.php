<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

use App\Models\Requests;

class AlertWithTheLetter extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-with-the-letter {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '受注額が与信額の80％を超える場合、通知データを作成';

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
        $log_path =  storage_path() .'/logs/batch/alert-with-the-letter.log'; //出力ファイルパス
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
            $this->log->addInfo("与信警戒アラートバッチ実行",['dateTime' => $nowDate]);

            // バッチ引数取得
            $executeDate = $this->argument("setDate");
            if(empty($executeDate)){
                $executeDate = Carbon::today();
            }else{
                $executeDate = new Carbon($executeDate);//2020-12-14
            }


            // データ抽出
            $dataList = $this->searchTarget($executeDate);
            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$nowDate);
            DB::commit();

            $this->log->addInfo("与信警戒アラート通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            // $send->sendMessage("バッチ名：alert-with-the-letter".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"与信警戒バッチ実行失敗");
            $this->log->error('与信警戒アラート通知実行失敗', ['error' => $e]);
        }
    }

    /**
     *返品未処理データ取得
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate)
    {


        //重複する場合line_of_credit_update_atが新しい方を持ってくる
        $subQuery = DB::table("m_trust_management as M2")
            ->select([
                'M2.customer_id',
                'M2.line_of_credit',
                'M2.line_of_credit_update_at',
                ])
            ->leftJoin("m_trust_management as M3", function ($join) {
                $join->on("M2.customer_id","=","M3.customer_id")
                    ->whereRaw(DB::raw("M2.line_of_credit_update_at < M3.line_of_credit_update_at"))
                ;
            })
            ->whereNull("M3.customer_id")
        ;


        //与信管理マスタに登録されている得意先取得、与信額も取得（設計書クエリ➀
        $data = DB::table('m_customer as M1')
        ->select([
            'M2.customer_id',
            'M2.line_of_credit',
            ])
        ->join(DB::raw('('. $subQuery->toSql() .') as M2'), 'M1.id', '=', 'M2.customer_id')
        //サブクエリのWHERE句の値をバインド
        ->mergeBindings($subQuery)

            ->get()
            ->toArray()
        ;


        
        foreach ($data as $key => $value) {
             //設計書クエリ➁
            $data[$key]->sales_total = DB::table('t_quote_detail as T1')
            ->select([
                DB::raw("SUM(T1.sales_total) as sales_total"),
                ])
            ->join('t_quote as T2', 'T1.quote_no', '=', 'T2.quote_no')
            ->join('t_matter as T3', 'T2.matter_no', '=', 'T3.matter_no')
            ->Join('t_sales_detail as T4', 'T1.id', '=', 'T4.quote_detail_id')

            ->where([
                ['T3.customer_id', '=', $value->customer_id],
                ['T1.id', '<>', 'T3.quote_detail_id'],
                ['T3.complete_flg', '=', config('const.flg.off')],
                ['T4.del_flg', '=', config('const.flg.off')],
            ])
            ->groupby('T3.customer_id')
            ->get()
            ->toArray()
            ;
        }


        
        // 売掛金を取得➂
        $Requests = new Requests(); 

        $noticeList = [];
        foreach ($data as $key => $value) {
            $data[$key]->notPaymentAmount = $Requests->getCustomerTotalSales($value->customer_id);

            // ➃2と3の合計
            if(!empty($data[$key]->sales_total)){
                // ⓹⓸の金額と➀で取得した与信額80%を比較し、⓸の販売金額が大きい場合アラートを送付する
                if($data[$key]->notPaymentAmount + $data[$key]->sales_total[0]->sales_total > $value->line_of_credit*(80/100)){
                    $noticeList[] = $value;
                }
            }  
       }


        $noticeData = [];
        if(!empty($noticeList)){
            foreach ($noticeList as $key => $value) {
                // 通知先
                $noticeData[] = $data = DB::table('m_customer as M1')
                ->distinct()
                ->select([
                    // 'M3.chief_staff_id',
                    DB::raw("M1.charge_staff_id as staff_id"),//(担当営業者)
                    'M2.chief_staff_id',//(部門長)
                    DB::raw("CONCAT('【与信警戒】',  M1.customer_name) as content"),
                    DB::raw("CONCAT('/') as redirect_url"),
                ])
     
                ->join('m_department as M2', 'M1.charge_department_id', '=', 'M2.id')
                ->where([
                    ['M1.id', '=', $value->customer_id],
                    ['M1.del_flg', '=', config('const.flg.off')],
                ])

                ->get()
                ;
            }
        }
        return $noticeData;
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
            foreach ($param as $p) {
                //(担当営業者)
                $insertList[] = [
                'notice_flg' => config('const.flg.on'),
                'staff_id' => $p->staff_id,
                'content' => $p->content,
                'redirect_url' => $p->redirect_url,
                'del_flg' => config('const.flg.off'),
                'created_user' => config('const.batchUser'),
                'created_at' =>  $nowDate->format('Y-m-d H:i:s'),
                'update_user' => config('const.batchUser'),
                'update_at' =>  $nowDate->format('Y-m-d H:i:s'),
                ];

                //(部門長)
                if($p->staff_id != $p->chief_staff_id ){
                    $insertList[] = [
                        'notice_flg' => config('const.flg.on'),
                        'staff_id' => $p->chief_staff_id,
                        'content' => $p->content,
                        'redirect_url' => $p->redirect_url,
                        'del_flg' => config('const.flg.off'),
                        'created_user' => config('const.batchUser'),
                        'created_at' =>  $nowDate->format('Y-m-d H:i:s'),
                        'update_user' => config('const.batchUser'),
                        'update_at' =>  $nowDate->format('Y-m-d H:i:s'),
                        ];
                }
            }
        }
        DB::table('t_notice')->insert($insertList);
        
        return $datacount;
    }
}
