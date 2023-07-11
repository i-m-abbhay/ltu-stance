<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertQuoteDate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-quote-date {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '見積版ごとに、見積提出期限を過ぎても印刷されていない場合、通知データを作成';

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
        $log_path =  storage_path() .'/logs/batch/alert-quote-date.log'; //出力ファイルパス
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
            $this->log->addInfo("見積期限超過バッチ実行",['dateTime' => $nowDate]);

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
            
            // 上長宛通知データ作成
            $dataList = $this->addDestination($dataList);

            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$nowDate);
            DB::commit();
            
            $this->log->addInfo("見積期限超過バッチ実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-quote-date".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"見積期限超過バッチ実行失敗");
            $this->log->error('見積期限超過バッチ実行失敗', ['error' => $e]);
        }
    }



     /**
     * 見積版の提出期限日を過ぎても、見積版.印刷日が入っていないデータを、案件単位で抽出する
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate)
    {
        $data = DB::table('t_quote_version as quote_version')
            ->distinct()
            ->select([
                DB::raw("'1' as notice_flg"),
                DB::raw("matter.staff_id as staff_id"),
                DB::raw("CONCAT('【見積超過】', matter.matter_name) as content"),
                DB::raw("CONCAT('/quote-list?matter_no=', matter.matter_no, '&special_flg_id=".config('const.flg.off')."&fil_chk_latest_only_id=".config('const.flg.off')."&fil_chk_not_approved_only_id=".config('const.flg.off')."') as redirect_url"),
                'matter.department_id'
            ])
            ->whereNull('quote_version.print_date')
            ->whereNotNull('quote_version.quote_limit_date')
            ->where([['quote_version.quote_limit_date', '<', $executeDate->format('Y-m-d')]])
            ->join('t_quote as quote', 'quote_version.quote_no', '=', 'quote.quote_no')

            ->join('t_matter as matter', function ($join) {
            $join->on('matter.matter_no', '=', 'quote.matter_no')
                ->select([
                    'matter.matter_no',
                    'matter.matter_name',
                    'matter.staff_id',
                    'matter.department_id'
                ])
                ->where([
                    ['matter.del_flg', '=', config('const.flg.off')],
                    ['matter.complete_flg', '=', config('const.flg.off')],
                    ['matter.own_stock_flg', '=', config('const.flg.off')],
                    ['matter.use_sales_flg', '=', config('const.flg.off')]
                ]);
            })
            ->get()
        ;

        return $data;
    }
    


    /**
     * 部門長データ作成
     *
     * @return object
     */
    private function getDepartmentList(){
        $Departments = DB::table('m_department')
        ->select([
            'm_department.id',
            'm_department.chief_staff_id',
        ])
        ->where([
            ['m_department.del_flg', '=', config('const.flg.off')],
        ])
        ->get();

        // 連想配列作成
        $departmentList =[];
        foreach ($Departments as $key => $data) {   
            $departmentList[$data->id] = $data->chief_staff_id;
        }
        
        return $departmentList;
    }


    /**
     * 通知先に上長を追加する
     *
     * @param [type] $quoteDataList
     * @return object
     */
    private function addDestination($quoteDataList){
        
        // データが空の場合終了
        if(empty($quoteDataList)){
            return 0;
         }

        // 部門ごとの部門長データ取得
        $departmentList = $this->getDepartmentList();
        
        // 上長宛通知データ作成
        foreach ($quoteDataList as $key => $data) {
            
            // 部門IDが存在しない場合作成しない
            if(empty($data->department_id)){
                continue;
            }

            // 上長あてに通知データ複製
            if($data->staff_id != $departmentList[$data->department_id]){
                $tmp  = clone $data;
                $tmp->staff_id = $departmentList[$data->department_id];
                $quoteDataList[] =$tmp;
            }
            
        }
        return $quoteDataList;
    }


    /**
     * 通知データ登録
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
