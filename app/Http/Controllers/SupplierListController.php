<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Log;
use App\Models\Authority;
use Auth;
use App\Models\Supplier;
use App\Models\ClassBig;
use App\Models\Construction;
use App\Models\General;
use App\Models\Department;
use App\Models\StaffDepartment;
use Carbon\Carbon;

class SupplierListController extends Controller
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
     * 
     * 初期表示
     * @return type
     */
    public function index()
    {
        // 閲覧権限チェック
        if (Authority::hasAuthority(Auth::user()->id, config('const.auth.master.inquiry')) === config('const.authority.none')) {
            throw new NotFoundHttpException();
        }
        // 編集権限
        $isEditable = Authority::hasAuthority(Auth::user()->id, config('const.auth.master.edit'));

        // リスト取得
        // 仕入先リスト取得
        $Supplier = new Supplier();
        $kbn[] = config('const.supplierMakerKbn.supplier');
        $supplierList = $Supplier->getBySupplierKbn($kbn);
        // メーカーリスト取得
        $kbn = [];
        $kbn[] = config('const.supplierMakerKbn.direct');
        $kbn[] = config('const.supplierMakerKbn.maker');
        $makerList = $Supplier->getBySupplierKbn($kbn);

        $ClassBig = new ClassBig();
        $bigList = $ClassBig->getComboList();

        $Construction = new Construction();
        $constList = $Construction->getComboList();

        $Department = new Department();
        // 初期検索条件
        // $initSearchParams = collect([
        //     'from_created_date' => Carbon::today()->subMonth(1)->format('Y/m/d'),
        //     'to_created_date' => Carbon::today()->format('Y/m/d'),
        // ]);

        return view('Supplier.supplier-list')
                ->with('isEditable', $isEditable)
                ->with('supplierList', $supplierList)
                ->with('makerList', $makerList)
                ->with('bigList', $bigList)
                ->with('constList', $constList)
                // ->with('initSearchParams', $initSearchParams)
                ;
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
            $Supplier = new Supplier();
            $list = $Supplier->getList($params);
            // 大分類データ取得
            $ClassBig = new ClassBig();
            $bigList = $ClassBig->getComboList();
            $bigList = $bigList->keyBy('id')->toArray();
            // 工事区分データ取得
            $Construction = new Construction();
            $constList = $Construction->getComboList();
            $constList = $constList->keyBy('id')->toArray();

            // 取扱品目、施工業者区分JOIN
            foreach ($list as $key => $row) {
                // 取扱品目
                if (!empty($row['product_line'])) {
                    // 先頭文字("[")、末尾文字("]")、空白を排除
                    $pLines = $row['product_line'];
                    $pLines = str_replace(array(" ", "　"), "", $pLines);
                    $pLines = substr($pLines , 1, strlen($pLines)-2);
                    // 配列化              
                    $pLines = explode(',', $pLines);
                    for ($i = 0; $i < count($pLines); $i++) {
                        $pLines[$i] = (int)$pLines[$i];
                        foreach ($bigList as $cBig) {
                            if ($pLines[$i] == $cBig['id']) {
                                $pLines[$i] = $cBig['class_big_name'];
                            }
                        }                    
                    }
                    $pLines = implode('／', $pLines);
                    $row['product_line'] = $pLines;
                }
                // 施工業者区分
                if (!empty($row['contractor_kbn'])) {
                    // 先頭文字("[")、末尾文字("]")、空白を排除
                    $cKbn = $row['contractor_kbn'];
                    $cKbn = str_replace(array(" ", "　"), "", $cKbn);
                    $cKbn = substr($cKbn , 1, strlen($cKbn)-2);
                    // 配列化              
                    $cKbn = explode(',', $cKbn);
                    for ($i = 0; $i < count($cKbn); $i++) {
                        $cKbn[$i] = (int)$cKbn[$i];
                        foreach ($constList as $cList) {
                            if ($cKbn[$i] == $cList['id']) {
                                $cKbn[$i] = $cList['construction_name'];
                            }
                        }                    
                    }
                    $cKbn = implode('／', $cKbn);
                    $row['contractor_kbn'] = $cKbn;
                }
            }
            // 取扱品目 部分一致で返す
            if (!empty($params['product_line'])) {
                $list = collect($list)->filter(function ($item) use ($params) {
                    return false !== stristr($item->product_line, $params['product_line']);
                });
            }
            // 施工業者区分 部分一致で返す
            if (!empty($params['construction_name'])) {
                $list = collect($list)->filter(function ($item) use ($params) {
                    return false !== stristr($item->contractor_kbn, $params['construction_name']);
                });
            }
            // filterで乱れた添字振り直し
            $list = array_values($list->toArray());
        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($list);
    }
    
}