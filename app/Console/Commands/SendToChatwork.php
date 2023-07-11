<?php

namespace App\Console\Commands;

use DB;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Monolog\Logger;
use Monolog\Handler\RotatingFileHandler;
use PhpParser\Node\Stmt\TryCatch;

class SendToChatwork extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'batch:send-to-chatwork {message = null : メッセージ}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'チャットワークへメッセージを送信する';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->log = new Logger($this->signature);
        $log_path =  storage_path() .'/logs/batch/send-to-chatwork.log'; //出力ファイルパス
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
        // バッチ引数取得
        $message = $this->argument("message");
        if(empty($message)){
            $message = "";
        }
        $this->sendMessage($message);
    }


    /**
     * Undocumented function
     *
     * @param [type] $msg
     * @return void
     */
    public function sendMessage($msg,$title = null,$to = null){
        try {      
            //タイトル指定時
            if(!empty($title)){
                $msg = "[info][title]".$title."[/title]".$msg.PHP_EOL."[/info]";     
            }
            //To指定時
            if(!empty($to)){
                $msg = $to.PHP_EOL.$msg;
            }
            
            //送信したいルームIDを指定
            //$rid = 149832895;//富建グループチャット
            $rid = 207505780;//バッチ通知マイチャット
            //チャットワークAPItokenを指定
            $token = "cadf8bf97c05bebf3ab8444e257c3978";

            header( "Content-type: text/html; charset=utf-8" );
            $data = array(
            'body' => $msg
            );

            $opt = array(
            CURLOPT_URL => "https://api.chatwork.com/v2/rooms/{$rid}/messages",
            CURLOPT_HTTPHEADER => array( 'X-ChatWorkToken: ' . $token ),
            CURLOPT_RETURNTRANSFER => TRUE,
            CURLOPT_SSL_VERIFYPEER => FALSE,
            CURLOPT_POST => TRUE,
            CURLOPT_POSTFIELDS => http_build_query( $data, '', '&' )
            );

            $ch = curl_init();
            curl_setopt_array( $ch, $opt );
            $res = curl_exec( $ch );
            curl_close( $ch );

            $res = json_decode($res);
            // 送信成功かをHTTPレスポンスから調べる
            if(!empty($res->errors)){
                echo "送信エラー";
                throw new \Exception("送信エラー");
            }
            $this->log->addInfo('チャットワークへメッセージ送信', ['message' => $msg]);
            
        } catch (\Exception $e) {
            $this->log->addInfo('チャットワークへメッセージ送信失敗', ['error' => $e->getMessage(),'result' => $res]);
        }
    }
}
