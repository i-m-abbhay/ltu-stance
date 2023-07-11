<?php
namespace App\Http\Controllers;

use Auth;
use App\Models\Notice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * 通知
 */
class NoticeController extends Controller
{
    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    public function index()
    {
    }

    /**
     * 既読 & リダイレクト
     *
     * @param $id
     * @return 
     */
    public function ReadNotice($id = null)
    {
        $Notice = new Notice();

        $notice = $Notice->find($id);
        // 読込日時がNULLの場合のみ既読にする
        if(is_null($notice['read_at'])){
            $Notice->MarkAsRead($id);
        }

        return redirect($notice['redirect_url']);
    }
    
    /**
     * 全て既読
     *
     * @param $id
     * @return 
     */
    public function ReadAllNotices()
    {
        try {
            $Notice = new Notice();
            
            $noticeData = $Notice->getNoticesByStaffId(Auth::id());
            foreach ($noticeData as $notice) {
                // 読込日時がNULLの場合のみ既読にする
                if(is_null($notice['read_at'])){
                    $Notice->MarkAsRead($notice['id']);
                }
            }
            $list = $Notice->getNoticesByStaffId(Auth::id());;
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }


    /**
     * 通知種別ごとに全て既読
     *
     * @param $id
     * @return 
     */
    public function ReadAllNoticesByNoticeflag($notice_flg)
    {
        try {
            $Notice = new Notice();
            
            $noticeData = $Notice->getNoticesByNoticeflag(Auth::id(), $notice_flg);
            foreach ($noticeData as $notice) {
                // 読込日時がNULLの場合のみ既読にする
                if(is_null($notice['read_at'])){
                    $Notice->MarkAsRead($notice['id']);
                }
            }
            $list = $Notice->getNoticesByNoticeflag(Auth::id(), $notice_flg);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }


    /**
     * 通知種別ごとに最新の200件取得
     *
     * @param $id
     * @return 
     */
    public function getNoticesByNoticeflag($notice_flg)
    {
        try {
            $Notice = new Notice();   
            $list = $Notice->getNoticesByNoticeflag(Auth::id(),$notice_flg);
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }


}