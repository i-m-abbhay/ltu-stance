<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Monolog\Logger;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

class AlertStockReplenishment extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:alert-stock-replenishment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '拠点の在庫数が発注点以下になったら場合、通知データを作成';

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
        $log_path =  storage_path() .'/logs/batch/alert-stock-replenishment.log'; //出力ファイルパス
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
            $executeDate = Carbon::now();
            $this->log->addInfo("在庫補充アラートバッチ実行",['dateTime' => $executeDate]);
    

            // データ抽出
            $dataList = $this->searchTarget($executeDate);
            
            // 通知データ登録
            DB::beginTransaction();
            $num = $this->createNotice($dataList,$executeDate);
            DB::commit();

            $this->log->addInfo("在庫補充アラートバッチ実行完了,".$num."件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：alert-stock-replenishment".PHP_EOL."Message：".$e->getMessage().PHP_EOL."Line：".$e->getLine(),"在庫補充アラートバッチ実行失敗");
            $this->log->error('在庫補充アラートバッチ実行失敗', ['error' => $e]);
        }
    }

    /**
     * 見積依頼の提出期限日を過ぎても、積算完了していない見積依頼項目が存在する場合を抽出する
     *
     * @param [type] $executeDate
     * @return object
     */
    private function searchTarget($executeDate){


        // サブクエリ
        $subQuery_o = DB::table('t_order as o')
            ->where([
                ['o.delivery_address_kbn', '=', config('const.deliveryAddressKbn.val.company')],
                ['o.own_stock_flg', '=', config('const.flg.on')],
                ['od.del_flg', '=', config('const.flg.off')]
            ])
            ->leftJoin('t_order_detail as od', function ($join) {
                $join->on('o.id', '=', 'od.order_id');
            })
            ->select([
                'od.product_id',
                DB::raw("o.delivery_address_id as warehouse_id"),
                DB::raw("SUM(od.order_quantity - od.arrival_quantity) as order_quantity"),
            ])
            ->groupBy('od.product_id','o.delivery_address_id')
        ;


        $subQuery_s = DB::table('m_shelf_number as s')
            ->where([['s.del_flg', '=', config('const.flg.off')]])    
        ;


        $subQuery_re = DB::table('t_productstock_shelf as ps')
            ->leftJoin(DB::raw('('. $subQuery_s->toSql() .') as s'), [['s.id', '=', 'ps.shelf_number_id'],['s.warehouse_id', '=', 'ps.warehouse_id']])
            //サブクエリのWHERE句の値をバインド
            ->mergeBindings($subQuery_s)
            ->where([
                ['s.shelf_kind', '=', config('const.shelfKind.return')],
                ['ps.stock_flg', '=', config('const.flg.on')]
            ])
            ->select([
                'ps.product_id',
                'ps.warehouse_id',
                DB::raw("SUM(ps.quantity) as quantity")
            ])
            ->groupBy('ps.product_id','ps.warehouse_id')
        ;


        $subQuery_t = DB::table('t_productstock_shelf as ps')
            ->leftJoin('m_warehouse as w', function ($join) {
                $join->on('w.id', '=', 'ps.warehouse_id')
                    ->where([['w.del_flg', '=', config('const.flg.off')]]);
            })

            ->leftJoin('m_base as b', function ($join) {
                $join->on('b.id', '=', 'w.owner_id')
                    ->where([['b.del_flg', '=', config('const.flg.off')]]);
            })

            ->leftJoin('m_product as p', function ($join) {
                $join->on('p.id', '=', 'ps.product_id')
                    ->where([['p.del_flg', '=', config('const.flg.off')]]);
            })

            ->leftJoin('t_reserve as r', function ($join) {
                $join->on([['r.from_warehouse_id', '=', 'ps.warehouse_id'],['r.product_id', '=', 'ps.product_id']]) 
                    ->where([
                        ['r.stock_flg', '=', config('const.flg.on')],
                        ['r.finish_flg', '=', config('const.flg.on')]
                    ])
                    ->select([
                        'r.product_id',
                        DB::raw("r.from_warehouse_id as warehouse_id"),
                        DB::raw("SUM(r.reserve_quantity) as reserve_quantity")
                    ])
                    ->groupBy('r.product_id','r.from_warehouse_id');
            })

            ->leftJoin(DB::raw('('. $subQuery_o->toSql() .') as o'), [['o.warehouse_id', '=', 'ps.warehouse_id'],['o.product_id', '=', 'ps.product_id']])
            //サブクエリのWHERE句の値をバインド
            ->mergeBindings($subQuery_o)

            ->leftJoin(DB::raw('('. $subQuery_re->toSql() .') as re'), [['re.warehouse_id', '=', 'ps.warehouse_id'],['re.product_id', '=', 'ps.product_id']])
            //サブクエリのWHERE句の値をバインド
            ->mergeBindings($subQuery_re)

            ->select([
                'ps.warehouse_id',
                'w.owner_id as base_id',
                'w.warehouse_name',
                'ps.product_id',
                DB::raw("SUM(ps.quantity) - IFNULL(r.reserve_quantity, 0) - IFNULL(re.quantity, 0) as active_quantity"),               
                DB::raw("SUM(ps.quantity) as actual_quantity"),
                DB::raw("IFNULL(MIN(r.reserve_quantity), 0) as reserve_quantity"),
                DB::raw("IFNULL(MIN(o.order_quantity), 0) as order_quantity")
    
            ])
            ->where([
                ['ps.stock_flg', '=', config('const.flg.on')]
            ])
            ->groupBy(
                'o.order_quantity',
                'ps.warehouse_id',
                'ps.product_id',
                'r.reserve_quantity')
            ->orderBy('b.id','ps.product_id','ps.warehouse_id')
        ;


        $subQuery_ns = DB::table('m_department as ns')
            ->select([
                'ns.base_id',
                't_notice_setting_detail.to_staff_id',
            ])
            ->where([
                ['t_notice_setting.type', '=', config('const.notice_type.restocking')]
                ])
            ->Join('t_notice_setting', function ($join) {
                $join->on('ns.id', '=', 't_notice_setting.from_department_id')   
                ;
            })
            ->Join('t_notice_setting_detail', 't_notice_setting.id', '=', 't_notice_setting_detail.notice_setting_id')
        ;


        $dataList = DB::table('t_order_limit as ol')
           
            ->leftJoin(DB::raw('('. $subQuery_t->toSql() .') as t'), [['ol.base_id', '=', 't.base_id'],['ol.product_id', '=', 't.product_id']])
            //サブクエリのWHERE句の値をバインド
            ->mergeBindings($subQuery_t)

            ->join('m_product as p', function ($join) {
                $join->on('p.id', '=', 'ol.product_id');
            })

            ->join('m_base as b', function ($join) {
                $join->on('b.id', '=', 'ol.base_id');
            })
            
            ->Join(DB::raw('('. $subQuery_ns->toSql() .') as ns'), 'ol.base_id', '=', 'ns.base_id')
            //サブクエリのWHERE句の値をバインド
            ->mergeBindings($subQuery_ns)

            ->where([
                ['ol.del_flg', '=', config('const.flg.off')],
                ['ol.order_limit', '>', config('const.flg.off')],
                ['p.del_flg', '=', config('const.flg.off')],
                ['b.del_flg', '=', config('const.flg.off')]
            ])
            ->select([
                'ns.to_staff_id as staff_id',
                DB::raw("CONCAT('【在庫補充】', b.base_name, ' ', p.product_name) as content"),
                DB::raw("CONCAT('/order-point-list?product_code=', p.product_code, '&base_name=', b.base_name) as redirect_url"),
                'b.base_name',   //デバッグ用 t_notice には不要
                'p.product_name',    //デバッグ用 t_notice には不要
                'ol.base_id',    //デバッグ用 t_notice には不要
                'ol.order_limit',    //デバッグ用 t_notice には不要
                 DB::raw("COALESCE(SUM(t.active_quantity), 0) as zaiko ")    //デバッグ用 t_notice には不要
            ])
            ->groupBy(
                't.product_id',
                'ol.product_id',
                'ol.base_id',
                'b.base_name',   //デバッグ用
                'p.product_code',    //デバッグ用
                'p.product_name',    //デバッグ用
                'ol.order_limit',
                'ns.to_staff_id'
                )
            ->having( DB::raw("COALESCE(SUM(t.active_quantity), 0)"), '<', DB::raw('ol.order_limit'))
            ->orderBy('ol.base_id','p.id')
            ->get()
        ;

        return $dataList;
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
