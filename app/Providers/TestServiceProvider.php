<?php

namespace App\Providers;

use App\Http\Controllers\Controller;
use App\Http\Controllers\QuoteEditController;
use App\Models\Department;
use App\Models\NumberManage;
use App\Models\Construction;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Person;
use App\Models\Product;
use App\Models\QuoteDetail;
use App\Models\Staff;
use App\Models\Warehouse;
use Illuminate\Support\ServiceProvider;
use App\Libs\Common;
use App\Libs\SystemUtil;
use App\Models\LockManage;
use App\Models\ProductCampaignPrice;
use Auth;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Exception;
use Storage;
use Symfony\Component\HttpFoundation\FileBag;
use DB;

class TestServiceProvider extends ServiceProvider
{
    
    // 商品の上限数
    //CONST MAX_PRODUCT_LIST_CNT = 1000;


    // メンバ
    private $controller;        // 呼び出し元
    

    // メンバ モデル
    private $Construction;
    private $QuoteDetail;
    private $Order;
    private $OrderDetail;
    private $Product;
    private $Staff;
    private $NumberManage;
    private $Department;
    private $Warehouse;
    private $ProductCampaignPrice;
    private $LockManage;
    private $SystemUtil;

    public function __construct(){
        // モデル
        $this->Construction = new Construction();
        $this->QuoteDetail  = new QuoteDetail();
        $this->Order        = new Order();
        $this->OrderDetail  = new OrderDetail();
        $this->Product      = new Product();
        $this->Staff        = new Staff();
        $this->NumberManage = new NumberManage();
        $this->Department   = new Department();
        $this->Warehouse    = new Warehouse();
        $this->ProductCampaignPrice = new ProductCampaignPrice();
        $this->LockManage   = new LockManage();
        $this->SystemUtil   = new SystemUtil();
    }

    public function initialize(Controller $controller){
        $this->controller = $controller;    // アップキャストする　TODO:phpでは影響無い？
    }
    
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }




    /**
     * 商品情報取得
     * @param $productId 商品ID
     * @return void
     */
    public function getProductInfo($productId) {
        $where = [];
        $where[] = array('m_product.id', '=', $productId);
        //$where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));

        $data = DB::table('m_product')
                ->where($where)
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    'm_product.model',
                    'm_product.length',
                    'm_product.thickness',
                    'm_product.width',
                    'm_product.maker_id',
                    'm_product.price',
                    'm_product.min_quantity',
                    'm_product.stock_unit',
                    'm_product.order_lot_quantity',
                    'm_product.unit',
                    'm_product.purchase_makeup_rate',
                    'm_product.sales_makeup_rate',
                    'm_product.normal_purchase_price',
                    'm_product.normal_sales_price',
                    'm_product.tax_type',
                    'm_product.start_date',
                    'm_product.end_date',
                    'm_product.new_product_id',
                    'm_product.intangible_flg',
                    'm_product.quantity_per_case',
                    'm_product.set_kbn',
                    'm_product.class_big_id',
                    'm_product.class_middle_id',
                    'm_product.class_small_id_1',
                    'm_product.tree_species',
                    'm_product.grade',
                    'm_product.length',
                    'm_product.thickness',
                    'm_product.width'
                )
                ->get()->first()
                ;

        return $data;
    }

     /**
     * 商品コードのオートコンプリートのリストを取得する
     * 商品コードで部分一致
     * @param $productName
     * @return 
     */
    public function getProductCodeList($productCode) {
        $where = [];
        //$where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.product_code', 'LIKE', '%' . $productCode . '%');

        $data = DB::table('m_product') 
                ->where($where)
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    'm_product.maker_id',
                    'm_product.min_quantity',
                    'm_product.order_lot_quantity',
                    'm_product.intangible_flg'
                )
                ->limit(config('const.productAutoCompleteSetting.max_list_count') + 1)
                ->get()
                ;

        return $data;
    }


    /**
     * 商品名のオートコンプリートのリストを取得する
     * 商品名、略称、ニックネームから探す
     * @param $productName
     * @return 
     */
    public function getProductNameList($productName) {
        $where = [];
        //$where[] = array('m_product.del_flg', '=', config('const.flg.off'));
        $where[] = array('m_product.draft_flg', '=', config('const.flg.off'));
        

        $DELIMITER = ',';

        // 商品ごとに別称を取得
        $productNicknameQuery = DB::table('m_product_nickname') 
                ->select([
                    'product_id',
                    DB::raw("GROUP_CONCAT(m_product_nickname.another_name separator '{$DELIMITER}') AS another_name"),
                ])
                ->groupBy('product_id')
                ->toSql()
                ;

        $data = DB::table('m_product') 
                ->leftJoin(DB::raw("({$productNicknameQuery}) as m_product_nickname"), function($join){
                    $join->on('m_product.id', 'm_product_nickname.product_id');
                })
                ->where($where)
                ->where(function($query)use($productName){
                    $query->orWhere('m_product.product_name', 'LIKE', '%' . $productName . '%')
                          ->orWhere('m_product.product_short_name', 'LIKE', '%' . $productName . '%')
                          ->orWhere('m_product_nickname.another_name', 'LIKE', '%' . $productName . '%');
                })
                ->select(
                    'm_product.id AS product_id',
                    'm_product.product_code',
                    'm_product.product_name',
                    DB::raw("(
                        CASE 
                            WHEN COALESCE(m_product.product_short_name, '') <> '' 
                            AND COALESCE(m_product_nickname.another_name, '') <> '' 
                                THEN CONCAT( 
                                    COALESCE(m_product.product_short_name, ''), '{$DELIMITER}', COALESCE(m_product_nickname.another_name, '')
                                ) 
                            WHEN COALESCE(m_product.product_short_name, '') <> '' 
                                THEN m_product.product_short_name 
                            WHEN COALESCE(m_product_nickname.another_name, '') <> '' 
                                THEN m_product_nickname.another_name 
                            ELSE '' 
                        END) AS product_short_name,
                        'm_product.maker_id',
                        'm_product.min_quantity',
                        'm_product.order_lot_quantity',
                        'm_product.intangible_flg'
                    ")
                )
                ->limit(config('const.productAutoCompleteSetting.max_list_count') + 1)
                ->get()
                ;

        return $data;
    }



}
