<?php
namespace App\Http\Controllers;

use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Authority;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Carbon\Carbon;

/**
 * QR印刷
 */
class SmapriController extends Controller
{

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 成功
     * @return type
     */
    public function success()
    {
        return view('Smapri.smapri-success')
                ;
    }

    /**
     * 失敗
     * @return type
     */
    public function failed()
    {
        return view('Smapri.smapri-failed')
                ;
    }
}