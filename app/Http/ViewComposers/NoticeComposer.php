<?php

namespace App\Http\ViewComposers;

use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use App\Models\Notice;

class NoticeComposer
{
    /**
     * userリポジトリの実装
     *
     * @var UserRepository
     */
    protected $users;

    /**
     * 新しいプロフィールコンポーザの生成
     *
     * @param  UserRepository  $users
     * @return void
     */
    // public function __construct(UserRepository $users)
    // {
    //     // 依存はサービスコンテナにより自動的に解決される
    //     // $this->users = $users;
    // }

    /**
     * データをビューと結合
     *
     * @param  View  $view
     * @return void
     */
    public function compose(View $view)
    {
        // // ログイン中のユーザー情報
        // if (Auth::check()) {
        //     $Notices = new Notice();
        //     $data = $Notices->getNoticesByStaffId(Auth::id());
        //     $view->with('notices', $data);
        // }
    }
}