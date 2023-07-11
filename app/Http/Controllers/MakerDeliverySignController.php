<?php

namespace App\Http\Controllers;

use Session;
use DB;
use Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Shipment;
use App\Libs\SystemUtil;
use Auth;

/**
 * サイン
 */
class MakerDeliverySignController extends Controller
{
    const SCREEN_NAME = 'maker-delivery-sign';

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

        return view('Returns.maker-delivery-sign')
            ->with('isEditable', $isEditable);
    }
}
