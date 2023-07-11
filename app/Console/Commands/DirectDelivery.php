<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Delivery;
use App\Models\Arrival;
use DB;

use Carbon\Carbon;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;

use App\Console\Commands\SendToChatwork;

//直送納品データ生成バッチ
class DirectDelivery extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'directdelivery';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $log_path =  storage_path() . '/logs/batch/directdelivery.log'; //出力ファイルパス
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
            $this->log->addInfo("直送バッチ実行", ['dateTime' => $nowDate]);


            //対象レコード取得
            $OrderDetail = new OrderDetail();
            $data = $OrderDetail->getDirectDelivery();

            //DB更新
            $Delivery = new Delivery();
            $Order = new Order();
            $Arrival = new Arrival();

            DB::beginTransaction();

            $orderNo = '';
            $deliveryDate = '';
            $DeliveryNo = '';
            foreach ($data as $params) {
                // 発注番号ごと、入荷予定日ごとに採番
                if ($orderNo != $params['order_no'] || $deliveryDate != $params['delivery_date']) {
                    $orderNo = $params['order_no'];
                    $deliveryDate = $params['delivery_date'];

                    // 納品番号採番
                    // $DeliveryNo = "";
                    $LastDeliveryNo = $Delivery->getDeliveryNo($params['delivery_date']);
                    if ($LastDeliveryNo != null) {
                        $seqNo = (int)substr($LastDeliveryNo['delivery_no'], -3);
                        $seqNo += 1;
                        $DeliveryNo = substr($LastDeliveryNo['delivery_no'], 0, 8) . sprintf('%03d', $seqNo);
                    } else {
                        // $DeliveryNo = 'NH' . substr(Carbon::now()->format('Y'), -2) .  Carbon::now()->format('md') . '001';
                        $DeliveryNo = 'NH' . substr(Carbon::parse($params['delivery_date'])->format('Ymd'), 2) . '001';
                    }
                }

                $params['delivery_no'] = $DeliveryNo;

                //納品データ作成
                $Delivery->add($params);

                //発注明細レコード更新
                $p['order_detail_id'] = $params['order_detail_id'];
                $p['arrival_quantity'] = $params['delivery_quantity'];
                $OrderDetail->updateQuantity($p);

                //発注レコード更新
                $Order->updateComplete($params);

                //入荷レコード作成
                $arrivalParams['product_id'] = $params['product_id'];
                $arrivalParams['product_code'] = $params['product_code'];
                $arrivalParams['to_warehouse_id'] = 0;
                $arrivalParams['to_shelf_number_id'] = 0;
                $arrivalParams['quantity'] = $params['delivery_quantity'];
                $arrivalParams['order_id'] = $params['order_id'];
                $arrivalParams['order_no'] = $params['order_no'];
                $arrivalParams['order_detail_id'] = $params['order_detail_id'];
                $arrivalParams['arrival_date'] = $params['delivery_date'];
                $arrivalParams['reserve_id'] = null;
                
                $Arrival->add($arrivalParams);
            }
            DB::commit();

            // 作成データ件数取得
            $cnt = count($data);
            $this->log->addInfo("直送バッチ実行完了," . $cnt . "件作成");
        } catch (\Exception $e) {
            DB::rollBack();
            $send = new SendToChatwork();
            $send->sendMessage("バッチ名：directdelivery" . PHP_EOL . "Message：" . $e->getMessage() . PHP_EOL . "Line：" . $e->getLine(), "直送バッチ実行失敗");
            $this->log->error('直送バッチ実行失敗', ['error' => $e]);
        }
    }
}
