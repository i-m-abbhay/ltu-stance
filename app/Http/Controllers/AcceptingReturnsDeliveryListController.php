<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Matter;
use App\Models\QrDetail;
use App\Models\Loading;
use App\Models\ProductMove;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * 納品一覧
 */
class AcceptingReturnsDeliveryListController extends Controller
{
    const SCREEN_NAME = 'accepting-returns-delevery-list';

    /**
     * コンストラクタ
     */
    public function __construct()
    {
        // ログインチェック
        $this->middleware('auth');
    }

    /**
     * 初期表示
     * @return type
     */
    public function index()
    {
        // TODO: 権限チェック
        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;

        return view('Returns.accepting-returns-delivery-list')
            ->with('isEditable', $isEditable);
    }
}
