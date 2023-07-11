<?php
namespace App\Http\Controllers;

use App\Exceptions\ValidationException;
use Session;
use Storage;
use DB;
use Validator;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Models\Matter;
use App\Models\QuoteVersion;
use App\Models\QuoteLayer;
use App\Models\Customer;
use App\Models\General;
use App\Models\LockManage;
use App\Models\NumberManage;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\Address;
use App\Models\CalendarData;
use App\Models\CustomerBranch;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Person;
use App\Models\QuoteDetail;
use App\Models\Shipment;
use App\Models\ShipmentDetail;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\SupplierMaker;
use App\Models\ProductPrice;
use App\Models\Reserve;
use App\Models\Construction;
use App\Models\Department;
use App\Models\ProductCampaignPrice;
use App\Models\Quote;
use App\Models\Staff;
use App\Models\SupplierFile;
use App\Models\System;
use App\Models\UpdateHistory;
use App\Providers\TestServiceProvider;
use Exception;

/**
 * テスト発注
 */
class TestController extends Controller
{
    //const SCREEN_NAME = 'order-sudden';

    // サービス
    private $TestServiceProvider;

const comment = 
'いきなり発注画面をベースにプロトタイプとして用意した画面のため
　・得意先
　・施主名
　・建築種別
子画面にあります、上記3項目は必須となっております
商品は仮登録を除く全てを対象としています

■商品コード
■商品名
　・3文字以上かつ結果が1000件以内の場合にオートコンプリートの選択肢をサーバーから取得する
　・※サーバーから応答が来るまでは再取得しない　結果応答があり次第、再取得可能状態になる
　　応答が来るまでは直前に保持している選択肢の中での絞り込みを行う
■販売区分変更時
　・金額の情報をサーバーから取得する
　※ローディングあり
■商品選択時
　・関連データをサーバーから取得し、グリッドの各項目へセットする
　※ローディングあり

*-- END --*';

    

    /**
     * コンストラクタ
     */
    public function __construct(TestServiceProvider $TestServiceProvider)
    {
        // ログインチェック
        $this->middleware('auth');
        $this->TestServiceProvider = $TestServiceProvider;
        $this->TestServiceProvider->initialize($this);
    }

    /**
     * 初期表示
     * @param Request $request
     * @param int $isStock 当社在庫品フラグ
     * @return type
     */
    public function index(Request $request)
    {
        // ログイン者がロックしているフラグ
        $isOwnLock = config('const.flg.off');
        $Customer = new Customer();


        $Matter     = new Matter();
        $QuoteLayer = new QuoteLayer();
        $QuoteDetail= new QuoteDetail();
        $LockManage = new LockManage();
        $Supplier   = new Supplier();
        $General    = new General();
        $Product    = new Product();
        $SupplierMaker  = new SupplierMaker();
        $SupplierFile   = new SupplierFile();
        $Person     = new Person();
        $Order      = new Order();
        $Address    = new Address();
        $Construction = new Construction();

        // モーダル項目
        $customerList   = null;
        $ownerList      = null;
        $customerOwnerList = null;
        $architectureList   = null;

        $matterModel    = null;
        $addressModel   = null;




        $orderModel = null;
        $qreqList   = null;
        $lockData   = $LockManage;
        $gridDataList   = [];
        $quoteLayerList = $QuoteLayer;

        $priceList              = null;
        $supplierList           = null;
        $supplierMakerList      = null;
        $supplierFileList       = null;
        $personList             = [];

        DB::beginTransaction();
        try {
            // 得意先名リスト取得
            $customerList = $Customer->getComboList();
            // 施主名リスト取得
            $ownerList = $Matter->getOwnerList();
            // 顧客配下の施主名取得
            $customerOwnerList = $Matter->getCustomerOwnerList();
            // 建築種別リスト取得
            $architectureList = $General->getCategoryList(config('const.general.arch'));
            $addressModel = $Address;
            Common::initModelProperty($addressModel);
            // 発注
            $orderModel = $Order; 
            Common::initModelProperty($orderModel);
            $orderModel->maker_name = '';
            $orderModel->supplier_name = '';
            $orderModel->delivery_address = '';
            $orderModel->desired_delivery_date = null;
            $orderModel->map_print_flg = config('const.flg.on');
            $orderModel->sales_support_comment = self::comment;

            // 価格区分リスト取得
            $priceList = $General->getCategoryList(config('const.general.price'));


            $matterModel = $Matter;
            Common::initModelProperty($matterModel);
            

            // 見積依頼項目リスト取得
            $qreqList = $Construction->getQreqData(config('const.flg.on'));
            // トップ階層を取得
            $quoteLayerList = $QuoteDetail::getTopTreeData();

            $kbn = [config('const.supplierMakerKbn.direct'), config('const.supplierMakerKbn.maker')];
            // メーカーリスト取得
            $makerList = $Supplier->getBySupplierKbn($kbn);
            // 仕入先リスト取得
            $supplierList = $Supplier->getComboList();
            // 仕入先メーカーリスト取得(裏で保持しておく)
            $tmpList = $SupplierMaker->getComboListByMakerId();
            $supplierMakerList = $tmpList->mapToGroups(function ($item, $key) {
                return [$item['maker_id'] => $item];
            });
            // 仕入先ファイル
            $supplierFileList = $SupplierFile->getAll()->mapWithKeys(function ($item, $key) {
                return [
                    $item->supplier_id => $item->file_name,
                ];
            });

            // 担当者リスト取得(裏で保持しておく)
            $tmpList = $Person->getComboList(config('const.person_type.supplier'));
            $personList = $tmpList->mapToGroups(function ($item, $key) {
                return [$item['company_id'] => $item];
            });

            // 商品コード無し取得
            $noProductCode = $General->getCategoryList(config('const.general.noproductcode'))->first();


            
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error($e);
            Session::flash('flash_error', config('message.error.error'));
        }

        return view('Order.test') 
                ->with('customerList', $customerList)
                ->with('ownerList', $ownerList)
                ->with('customerOwnerList', $customerOwnerList)
                ->with('architectureList', $architectureList)
                ->with('matterModel', $matterModel)
                ->with('addressModel', $addressModel)
                ->with('deliveryAddressKbnList', json_encode(config('const.deliveryAddressKbn')))
                ->with('qreqList', $qreqList)
                
                ->with('orderModel',  $orderModel)
                ->with('isOwnLock', $isOwnLock)
                ->with('lockData',  $lockData)
                ->with('quoteLayerList',    json_encode($quoteLayerList))
                ->with('gridDataList',      json_encode($gridDataList))
                ->with('makerList',         $makerList)
                ->with('priceList',         $priceList)
                ->with('supplierList',      $supplierList)
                ->with('supplierMakerList', $supplierMakerList)
                ->with('supplierFileList', $supplierFileList)
                ->with('personList',        $personList)
                ->with('noProductCode',     $noProductCode)
                ;
    }

    /**
     * 商品コードからオートコンプリートの選択肢の配列を取得する
     * @param $request
     * @return type
     */
    public function getProductCodeList(Request $request){
        $params = $request->request->all();
        $result = $this->TestServiceProvider->getProductCodeList($params['text']);
        
        return \Response::json($result);
    }

    /**
     * 商品名からオートコンプリートの選択肢の配列を取得する
     * @param $request
     * @return type
     */
    public function getProductNameList(Request $request){
        $params = $request->request->all();
        $result = $this->TestServiceProvider->getProductNameList($params['text']);
        
        return \Response::json($result);
    }

















    /**
     * 案件情報取得(案件住所の更新アイコンのボタン)
     * @param $request
     * @return type
     */
    public function getMatterInfo(Request $request){
        $params = $request->request->all();
        $matter = Matter::find($params['matter_id']);
        $value['matter_address'] = Address::find($matter->address_id);
        $value['delivery_address_list'] = $this->TestServiceProvider->getDeliveryAddressList($params['supplier_id'], $matter->address_id);
        
        return \Response::json($value);
    }

    /**
     * 入荷先リスト取得
     * @param $request
     * @return type
     */
    public function getDeliveryAddressList(Request $request){
        $params = $request->request->all();
        $deliveryAddressList = $this->TestServiceProvider->getDeliveryAddressList($params['supplier_id'], $params['matter_address_id']);
        $deliveryAddressList = $this->cnvDeliveryAddressList($params['own_stock_flg'], $deliveryAddressList);
        return \Response::json($deliveryAddressList);
    }
    /**
     * いきなり発注画面の場合、案件が無いので直送を追加
     * @param $ownStockFlg　当社在庫品専用か
     * @return $list        入荷先リスト
     */
    public function cnvDeliveryAddressList($ownStockFlg, $list){
        if(!Common::isFlgOn($ownStockFlg)){
            // 当社在庫では無い場合、直送の選択肢を追加
            $data = [
                'unique_key'=>config('const.deliveryAddressKbn.val.site').'_'.'0',
                'id'=>0,
                'warehouse_name'=>'直送',
                'address'=>'',
                'delivery_address_kbn'=>config('const.deliveryAddressKbn.val.site'),
                'display_order'=>9999
            ];
            $list->push($data);
        }else{
            // 当社在庫の場合、自社倉庫のみ表示
            $list = $list->where('delivery_address_kbn', config('const.deliveryAddressKbn.val.company'));
        }
        return $list;
    }

    /**
     * 案件入力内容確認
     *
     * @param Request $request
     * @return void
     */
    public function confirmMatter(Request $request) {
        $result = [];
        
        // リクエストデータ取得
        $params = $request->request->all();
        // バリデーションチェック
        $this->validate($request, [
            'customer_id' => 'required',
            'owner_name' => 'required|max:255',
            'architecture_type' => 'required',
        ]);

        // 得意先データ取得
        $Customer = new Customer();
        $customerData = $Customer->getChargeData((int)$params['customer_id']);

        // 案件名生成
        $SystemUtil = new SystemUtil();
        $matterName = $SystemUtil->createMatterName($params['owner_name'], $customerData['customer_short_name']);

        // 案件データ取得
        $Matter = new Matter();
        $chkDuplicateData = [
            'id'            => null,
            'customer_id'   => $params['customer_id'],
            'owner_name'    => $params['owner_name'],
        ];

        // 返却値セット
        $result['matter_name'] = $matterName;
        $result['department_name'] = $customerData['department_name'];
        $result['staff_name'] = $customerData['staff_name'];
        $result['department_id'] = $customerData['charge_department_id'];
        $result['staff_id'] = $customerData['charge_staff_id'];
        $result['invalid'] = $Matter->isDuplicate($chkDuplicateData);
        $result['status'] = true;
        
        return \Response::json($result);
    }

}