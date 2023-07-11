<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use Log;
use App\Models\Authority;
use App\Models\ClassBig;
use Auth;
use App\Models\Product;
use App\Models\ClassMiddle;
use App\Models\ClassSmall;
use App\Models\Construction;
use App\Models\Supplier;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ProductListController extends Controller
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

        // $Product = new Product();
        // $productData = $Product->getComboList();
        // $productData = $Product->getFormalComboList();
        // メーカーリスト取得
        $Supplier = new Supplier();
        $supplierData = $Supplier->getComboList();
        // 分類リスト取得
        $ClassBig = new ClassBig();
        $classBigData = $ClassBig->getProductClass(config('const.flg.on'));
        $ClassMid = new ClassMiddle();
        $classMidData = $ClassMid->getQreqMiddleList($classBigData);
        $ClassSmall = new ClassSmall();
        $classSmallData = $ClassSmall->getComboList();
        // 工事区分取得
        $Construction = new Construction();
        $constData = $Construction->getComboList();


        return view('Product.product-list')
                ->with('isEditable', $isEditable)
                // ->with('productData', $productData)
                ->with('supplierData', $supplierData)
                ->with('constData', $constData)
                ->with('classBigData', $classBigData)
                ->with('classMidData', $classMidData)
                ->with('classSmallData', $classSmallData)
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
            $Product = new Product();
            $data = $Product->getList($params);

        } catch (\Exception $e) {
            Log::error($e);
        }
        
        return \Response::json($data);
    }    
}