<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertReturn extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-return {setDate? : 指定日}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '未処理の返品が存在する場合、通知データを作成';

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
        $log_path =  storage_path() .'/logs/batch/alert_return.log'; //出力ファイルパス
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
            $this->log->addInfo("返品未処理アラートバッチ実行",['dateTime' => $nowDate]);
    
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

            $this->log->addInfo("返品未処理アラート通知実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-return".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"返品未処理アラートバッチ実行失敗");
            $this->log->error('返品未処理アラート通知実行失敗', ['error' => $e]);
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
        $date[0] =  $executeDate->copy()->subDays(3);
        $date[1] = $executeDate->copy()->subDays(10);

        $sub = DB::table('t_return')
            ->select([
                't_return.warehouse_move_id',
                DB::raw("SUM(t_return.return_quantity) as sum_return"),
                't_warehouse_move.id',
                't_warehouse_move.move_date'
            ])

            ->join('t_warehouse_move', function ($join) use ($date) {
                $join->on('t_return.warehouse_move_id', '=', 't_warehouse_move.id')
                ->whereIn('t_warehouse_move.move_date', [$date[0]->format('Y-m-d') , $date[1]->format('Y-m-d')])
                ;
            })
            ->groupBy('t_return.warehouse_move_id')
        ;

        $data = DB::table('t_warehouse_move')
            ->distinct()
            ->select([
                "m_department.chief_staff_id as staff_id",
                DB::raw("CONCAT('【返品処理】',t_matter.matter_name, m_product.product_name, t_warehouse_move.quantity, m_product.unit) as content"),
                DB::raw("CONCAT('/return-process?matter_no=', t_matter.matter_no, '&product_code=',t_warehouse_move.product_code) as redirect_url"),
            ])
            ->leftJoin(DB::raw('('. $sub->toSql() .') as t_return'), 't_warehouse_move.id', '=', 't_return.warehouse_move_id')
            //サブクエリのWHERE句の値をバインド
            ->mergeBindings($sub)
            ->join('t_matter', 't_warehouse_move.matter_id', '=', 't_matter.id')
            ->join('m_product', 't_warehouse_move.product_id ', '=', 'm_product.id')
            ->join('m_department', 't_matter.department_id', '=', 'm_department.id')
            ->whereIn('t_warehouse_move.move_date', [$date[0]->format('Y-m-d') , $date[1]->format('Y-m-d')])  
            ->where([
                ['t_warehouse_move.move_kind', '=', config('const.moveKind.return')],
                [DB::raw("COALESCE(t_return.sum_return, 0)"), '<', DB::raw('t_warehouse_move.quantity')]
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
