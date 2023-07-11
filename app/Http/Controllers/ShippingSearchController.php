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
use App\Models\General;
use Storage;
use App\Libs\Common;
use App\Models\Authority;
use Auth;

/**
 * 出荷検索
 */
class ShippingSearchController extends Controller
{
    const SCREEN_NAME = 'shipping-search';

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
    public function index($id = null)
    {
        // 得意先リスト取得
        $Customer = new Customer();
        $customerList = $Customer->getComboList();
        // 案件リスト取得
        $Matter = new Matter();
        $matterList = $Matter->getComboList();
        // 部門リスト取得
        $Department = new Department();
        $departmentList = $Department->getList(array());
         // 担当者リスト取得
        $Staff = new Staff();
        $staffList = $Staff->getList(array());

        return view('Shipping.shipping-search')
                ->with('customerList', $customerList)
                ->with('matterList', $matterList)
                ->with('departmentList', $departmentList)
                ->with('staffList', $staffList)
                ;
    }
}
