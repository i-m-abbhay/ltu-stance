<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Customer;
use App\Models\Department;
use App\Models\Staff;
use App\Models\Matter;
use App\Models\Product;
use App\Models\StaffDepartment;
use Session;
use App\Models\Supplier;
use Illuminate\Support\Facades\Log;

/**
 * 入荷検索
 */
class ArrivalSearchController extends Controller
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
     * 初期表示
     * @return type
     */
    public function index()
    {
        // TODO: 権限チェック

        // TODO: 編集権限　0:権限なし 1:権限あり
        $isEditable = 1;
        // 得意先リスト取得
        $Customer = new Customer();
        $customerList = $Customer->getComboList();
        // 案件リスト取得
        $Matter = new Matter();
        $matterList = $Matter->getComboList(config('const.flg.off'), null, config('const.flg.off'));
        // 部門リスト取得
        $Department = new Department();
        $departmentList = $Department->getComboList(array());
        // 担当者リスト取得
        $Staff = new Staff();
        $staffList = $Staff->getComboList(array());

         // 担当者部門
         $staffDepartmentData = (new StaffDepartment)->getCurrentTermList();
         $staffDepartmentData = $staffDepartmentData->mapToGroups(function ($item, $key) {
             return [
                 $item->department_id => $item->staff_id,
             ];
         });

        // 仕入先リスト取得
        $Supplier = new Supplier();
        $kbn[] = config('const.supplierMakerKbn.supplier');
        $supplierList = $Supplier->getOrder($kbn);
        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);
        // 発注リスト取得
        $Order = new Order();
        $orderList = $Order->getComboList();

        //届け先リスト
        $orderDeliveryList = $Order->getArrivalComboList();

        return view('Arrival.arrival-search')
            ->with('isEditable', $isEditable)
            ->with('customerList', $customerList)
            ->with('matterList', $matterList)
            ->with('departmentList', $departmentList)
            ->with('staffList', $staffList)
            ->with('staffDepartmentData', $staffDepartmentData)
            ->with('supplierList', $supplierList)
            ->with('makerList', $makerList)
            ->with('orderList', $orderList)
            ->with('defaultWarehouseId', Session::get('warehouse_id'))
            ->with('orderDeliveryList', $orderDeliveryList);
    }

    /**
     * 検索
     * @param \App\Http\Controllers\Request $request
     * @return type
     */
    public function searchData(Request $request)
    {
        try {
            // リクエストから検索条件取得
            $params = $request->request->all();

            // 一覧データ取得
            $Order = new Order();
            $list = $Order->getList($params);
        } catch (\Exception $e) {
            Log::error($e);
        }

        return \Response::json($list);
    }
}
