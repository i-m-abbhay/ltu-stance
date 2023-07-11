<?php

namespace App\Providers;

use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ComposerServiceProvider extends ServiceProvider
{
    /**
     * コンテナ結合の登録
     *
     * @return void
     */
    public function boot()
    {
        // クラスベースのコンポーザを使用する
        View::composer(
            '*', 'App\Http\ViewComposers\NoticeComposer'
        );

        // クロージャベースのコンポーザを使用する
        // View::composer('dashboard', function ($view) {
            //
        // });
    }

    /**
     * サービスプロバイダ登録
     *
     * @return void
     */
    public function register()
    {
        //
    }
}