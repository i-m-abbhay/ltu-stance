<?php

namespace App\Console;

// バッチclass
use App\Console\Commands\AlertQuoteRequestDate;
use App\Console\Commands\AlertQuoteDate;
use App\Console\Commands\AlertNotOrdering;
use App\Console\Commands\AlertArrivalDate;
use App\Console\Commands\AlertShipmentDate;
use App\Console\Commands\AlertReturn;
use App\Console\Commands\AlertStockReplenishment;
use App\Console\Commands\AlertInvoiceDueDate;
use App\Console\Commands\AlertCollectionDeadline;
use App\Console\Commands\AlertWithTheLetter;
use App\Console\Commands\AlertOverTrust;
use App\Console\Commands\AlertLackOfGuaranteedFrameDigestion;



use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        //バッチクラス一覧
        \App\Console\Commands\DirectDelivery::class, //directdelivery

        AlertQuoteRequestDate::class,   //No.04：見積依頼期限超過バッチ
        AlertQuoteDate::class,          //No.09：見積期限超過バッチ
        AlertNotOrdering::class,        //No.12：未発注アラートバッチ
        AlertArrivalDate::class,        //No.13：入荷予定日超過バッチ
        AlertShipmentDate::class,       //No.14：出荷予定日超過バッチ
        AlertReturn::class,             //No.18：返品未処理アラートバッチ
        AlertStockReplenishment::class, //No.19：在庫補充アラートバッチ
        AlertInvoiceDueDate::class,     //No.27：請求期限超過バッチ
        AlertCollectionDeadline::class, //No.28：集金期限超過バッチ
        AlertWithTheLetter::class,      //No.39：与信警戒バッチ
        AlertOverTrust::class,          //No.40：与信オーバーバッチ
        AlertLackOfGuaranteedFrameDigestion::class, //No.41：保証枠消化不足バッチ

    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        //スケジュール一覧
        $schedule->command('directdelivery')->daily(); //直送納品データ作成（日次）

        $schedule->command('batch:alert-quote-request-date')->daily(); //見積期限超過通知データ作成（日次）
        $schedule->command('batch:alert-quote-date')->daily(); //見積提出期限超過データ作成（日次）
        $schedule->command('batch:alert-not-ordering')->daily(); //見積提出期限超過データ作成（日次）
        $schedule->command('batch:alert-arrival-date')->daily(); //入荷予定日超過データ作成（日次）
        $schedule->command('batch:alert-shipment-date')->daily(); //出荷予定日超過データ作成（日次）
        $schedule->command('batch:alert-return')->daily(); //返品未処理データ作成（日次）
        $schedule->command('batch:alert-stock-replenishment')->daily(); //在庫補充アラートデータ作成（日次）
        $schedule->command('batch:alert-Invoice-due-date')->daily(); //請求予定日超過通知データ作成（日次）
        $schedule->command('batch:alert-collection-deadline')->daily(); //入金予定日超過通知データ作成（日次）
        $schedule->command('batch:alert-with-the-letter')->daily(); //与信警戒データ通知作成（日次）
        $schedule->command('batch:alert-over-trust')->daily(); //与信オーバー通知データ作成（日次）
        $schedule->command('batch:alert-lack-of-guaranteed-frame-digestion')->daily(); //保証枠消化不足通知データ作成（日次）
        
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
