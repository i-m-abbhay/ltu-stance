/**
 * First we will load all of this project's JavaScript dependencies which
 * includes Vue and other libraries. It is a great starting point when
 * building robust, powerful web applications using Vue and Laravel.
 */

require("./bootstrap");

window.Vue = require("vue");

import Vue from "vue";
import ElementUI from "element-ui";
import "element-ui/lib/theme-chalk/index.css";
import lang from "element-ui/lib/locale/lang/ja";
import locale from "element-ui/lib/locale";
import * as wjcCore from "@grapecity/wijmo";
import * as gauge from "@grapecity/wijmo.gauge";
import "@grapecity/wijmo.vue2.core";
import "@grapecity/wijmo.vue2.input";
import "@grapecity/wijmo.vue2.nav";
import "@grapecity/wijmo.vue2.grid";
import "@grapecity/wijmo.vue2.grid.detail";
import "@grapecity/wijmo.vue2.grid.multirow";
import "@grapecity/wijmo.vue2.grid.filter";
import "@grapecity/wijmo.vue2.gauge";
import "@grapecity/wijmo.styles/wijmo.css";
import "@grapecity/wijmo.cultures/wijmo.culture.ja.js";
import "../../../public/css/style.css";

import commonMixin from "./common";
import gridTreeUtilMixin from "./gridTreeUtil";

Vue.use(ElementUI);
locale.use(lang);
wjcCore.setLicenseKey(
   "internship1.stance.top,418587288317184#B0JpMIyNHZisnOiwmbBJye0ICRiwiI34TQTx4KEN6aD9WdKVkT9JUT4omW5QUdHpGbPt4MxgHVLVlVVZTcvUETQNFaxJlSidTZqtieT3WcN5WOJR6brZHOHxGRspETjVUdOZHSrRDZrp5KCt4K0Jmc8NEUxBHMapXW69WMXR4R5l5TVlzMJZHWntmbDJ6QSNkS0J4VhlGbXtUMvRVMrEXdZtUcYBjMipFSPl6N8NGevlTN9JlSLB7Tp5kd0R4Y5hTQStibjF7SzdXYPd5apRXOtJTU74kWRR5U6QUR8YUN7NzdQN6MRh6YtRDM85UVj3EZ7o4NTVjbEBHeQF6Q5klMP3SRrcjcCJEe8smSyZnZ8RWbod6dYFUOxUFeVpFcSFXN0FTWYRUa5p4Ti3SeMVEa6N5MkFDMBpXQUhjRO5GbWpGORBXe9AzNQB7K99GNVB5Q8RzYv44LmdUbRZTUUJWUlZDa826YysSUzEzKvUmI0IyUiwiI8AjMzIUQ7YjI0ICSiwCN4kTN5kDN9gTM0IicfJye#4Xfd5nIJBjMSJiOiMkIsIibvl6cuVGd8VEIgQXZlh6U8VGbGBybtpWaXJiOi8kI1xSfiUTSOFlI0IyQiwiIu3Waz9WZ4hXRgAicldXZpZFdy3GclJFIv5mapdlI0IiTisHL3JyS7gDSiojIDJCLi86bpNnblRHeFBCI73mUpRHb55EIv5mapdlI0IiTisHL3JCNGZDRiojIDJCLi86bpNnblRHeFBCIQFETPBCIv5mapdlI0IiTisHL3JyMDBjQiojIDJCLiUmcvNEIv5mapdlI0IiTisHL3JSV8cTQiojIDJCLi86bpNnblRHeFBCI4JXYoNEbhl6YuFmbpZEIv5mapdlI0IiTis7W0ICZyBlIsICO4YTM4ADI4ETNwMjMwIjI0ICdyNkIsICcvRnLlNmbhR7cuEDcph6cuJXZ49WaiojIz5GRiwiI0ub9M+a9AC88+S09ayL9Pyb9qCq9iojIh94QiwiI4gTM7EzM8gjM7gTN8EDNiojIklkIs4nIzYHMyAjMiojIyVmdiwSZwxZY"
);

Vue.mixin(commonMixin);
Vue.mixin(gridTreeUtilMixin);

/**
 * Next, we will create a fresh Vue application instance and attach it to
 * the page. Then, you may begin adding components to this application
 * or customize the JavaScript scaffolding to fit your unique needs.
 */

Vue.component(
   "example-component",
   require("./components/ExampleComponent.vue")
);
Vue.component("header-component", require("./components/HeaderComponent.vue"));
Vue.component(
   "sidemenu-component",
   require("./components/SideMenuComponent.vue")
);
Vue.component(
   "loading-component",
   require("./components/LoadingComponent.vue")
);
Vue.component(
   "mobilesidemenu-component",
   require("./components/MobileSideMenuComponent.vue")
);
Vue.component(
   "mobileheader-component",
   require("./components/MobileHeaderComponent.vue")
);
Vue.component(
   "smaprisuccess-component",
   require("./components/SmapriSuccessComponent.vue")
);
Vue.component(
   "smaprifailed-component",
   require("./components/SmapriFailedComponent.vue")
);

// ダッシュボード
Vue.component(
   "dashboard-component",
   require("./components/DashBoardComponent.vue")
);
Vue.component(
   "salesinfoboard-component",
   require("./components/SalesInfoBoardComponent.vue")
);

// 拠点
Vue.component(
   "baselist-component",
   require("./components/BaseListComponent.vue")
);
Vue.component(
   "baseedit-component",
   require("./components/BaseEditComponent.vue")
);
// 倉庫
Vue.component(
   "warehouselist-component",
   require("./components/WarehouseListComponent.vue")
);
Vue.component(
   "warehouseedit-component",
   require("./components/WarehouseEditComponent.vue")
);
// ログイン
Vue.component("login-component", require("./components/LoginComponent.vue"));
// 担当者登録
Vue.component(
   "stafflist-component",
   require("./components/StaffListComponent.vue")
);
Vue.component(
   "staffedit-component",
   require("./components/StaffEditComponent.vue")
);
// 得意先
Vue.component(
   "newcustomerlist-component",
   require("./components/NewCustomerListComponent.vue")
);
Vue.component(
   "newcustomeredit-component",
   require("./components/NewCustomerEditComponent.vue")
);
// 得意先担当者設定
Vue.component(
   "customerstaff-component",
   require("./components/CustomerStaffComponent.vue")
);
// GoogleMap
Vue.component(
   "googlemaps-component",
   require("./components/GoogleMapsComponent.vue")
);
// 見積依頼入力・見積依頼詳細
Vue.component(
   "quoterequest-component",
   require("./components/QuoteRequestComponent.vue")
);
Vue.component(
   "quoterequest-top-component",
   require("./components/QuoteRequestTopComponent.vue")
);
Vue.component(
   "quoterequest-main-component",
   require("./components/QuoteRequestMainComponent.vue")
);
Vue.component(
   "quoterequest-complete-component",
   require("./components/QuoteRequestCompleteComponent.vue")
);
Vue.component(
   "quoterequestdetail-component",
   require("./components/QuoteRequestDetailComponent.vue")
);
// 見積依頼一覧
Vue.component(
   "quoterequestlist-component",
   require("./components/QuoteRequestListComponent.vue")
);
// 見積登録
Vue.component(
   "quoteedit-component",
   require("./components/QuoteEditComponent.vue")
);
// 見積一覧
Vue.component(
   "quotelist-component",
   require("./components/QuoteListComponent.vue")
);
// 見積書
Vue.component(
   "quotereport-component",
   require("./components/QuoteReportComponent.vue")
);
// 発注
// Vue.component('orderlist-component', require('./components/OrderListComponent.vue'))
// Vue.component('orderedit-component', require('./components/OrdereditComponent.vue'))
// いきなり発注
Vue.component(
   "ordersudden-component",
   require("./components/OrderSuddenComponent.vue")
);
// 発注詳細
Vue.component(
   "orderdetail-component",
   require("./components/OrderDetailComponent.vue")
);
// 発注書
Vue.component(
   "orderreport-component",
   require("./components/OrderReportComponent.vue")
);
// 入荷
Vue.component(
   "arrivallist-component",
   require("./components/ArrivalListComponent.vue")
);
Vue.component(
   "arrivalsearch-component",
   require("./components/ArrivalSearchComponent.vue")
);
Vue.component(
   "arrivalresult-component",
   require("./components/ArrivalResultComponent.vue")
);
Vue.component(
   "arrivalprocess-component",
   require("./components/ArrivalProcessComponent.vue")
);
// 出荷納品一覧
Vue.component(
   "shippingdeliverylist-component",
   require("./components/ShippingDeliveryListComponent.vue")
);
Vue.component(
   "shippingdeliverylistphoto-component",
   require("./components/ShippingDeliveryListPhotoComponent.vue")
);
// 案件
Vue.component(
   "matterlist-component",
   require("./components/MatterListComponent.vue")
);
Vue.component(
   "matteredit-component",
   require("./components/MatterEditComponent.vue")
);
Vue.component(
   "matterdetail-component",
   require("./components/MatterDetailComponent.vue")
);
// 住所
Vue.component(
   "addressedit-component",
   require("./components/AddressEditComponent.vue")
);
// 出荷
Vue.component(
   "shippingsearch-component",
   require("./components/ShippingSearchComponent.vue")
);
Vue.component(
   "shippinglist-component",
   require("./components/ShippingListComponent.vue")
);
Vue.component(
   "shippingprocess-component",
   require("./components/ShippingProcessComponent.vue")
);
Vue.component(
   "shippingtruck-component",
   require("./components/ShippingTruckComponent.vue")
);
Vue.component(
   "shippinginstruction-component",
   require("./components/ShippingInstructionComponent.vue")
);
// 納品
Vue.component(
   "deliverylist-component",
   require("./components/DeliveryListComponent.vue")
);
Vue.component(
   "deliveryprocess-component",
   require("./components/DeliveryProcessComponent.vue")
);
Vue.component(
   "deliveryphoto-component",
   require("./components/DeliveryPhotoComponent.vue")
);
Vue.component(
   "deliverysign-component",
   require("./components/DeliverySignComponent.vue")
);
// 商品マスタ
Vue.component(
   "productlist-component",
   require("./components/ProductListComponent.vue")
);
Vue.component(
   "productedit-component",
   require("./components/ProductEditComponent.vue")
);
Vue.component(
   "productnickname-component",
   require("./components/ProductNicknameComponent.vue")
);
Vue.component(
   "productcampaignprice-component",
   require("./components/ProductCampaignPriceComponent.vue")
);
Vue.component(
   "productimport-component",
   require("./components/ProductImportComponent.vue")
);
// 商品マスタチェック
Vue.component(
   "productcheck-component",
   require("./components/ProductCheckComponent.vue")
);
// 在庫照会(PC)
Vue.component(
   "stocksearch-component",
   require("./components/StockSearchComponent.vue")
);
// 在庫照会(スマホ)
Vue.component(
   "stockinquiry-component",
   require("./components/StockInquiryComponent.vue")
);
Vue.component(
   "stockinquiry-top-component",
   require("./components/StockInquiryTopComponent.vue")
);
Vue.component(
   "stockinquiry-list-component",
   require("./components/StockInquiryListComponent.vue")
);
Vue.component(
   "stockinquiry-detail-component",
   require("./components/StockInquiryDetailComponent.vue")
);
// 在庫転換
Vue.component(
   "stock-conversion-component",
   require("./components/StockConversionComponent.vue")
);
// QR分割
Vue.component(
   "qrsplit-component",
   require("./components/QrSplitComponent.vue")
);
Vue.component(
   "qrsplitsingleproduct-component",
   require("./components/QrSplitSingleProductComponent.vue")
);
Vue.component(
   "qrsplitproductsplit-component",
   require("./components/QrSplitProductSplitComponent.vue")
);
Vue.component(
   "qrsplitquantitydesignation-component",
   require("./components/QrSplitQuantityDesignationComponent.vue")
);
// QR統合
Vue.component(
   "qrintegration-component",
   require("./components/QrIntegrationComponent.vue")
);
// QR案件変更
Vue.component(
   "qrmatterchange-component",
   require("./components/QrMatterChangeComponent.vue")
);
// スマホ用メニュー
Vue.component(
   "mobilemenu-component",
   require("./components/MobileMenuComponent.vue")
);
// 部門マスタ
Vue.component(
   "departmentlist-component",
   require("./components/DepartmentListComponent.vue")
);
Vue.component(
   "departmentedit-component",
   require("./components/DepartmentEditComponent.vue")
);
// 仕入先／メーカーマスタ
Vue.component(
   "supplierlist-component",
   require("./components/SupplierListComponent.vue")
);
Vue.component(
   "supplieredit-component",
   require("./components/SupplierEditComponent.vue")
);
// 仕入先／メーカー対照マスタ
Vue.component(
   "supplier-maker-contrast-component",
   require("./components/SupplierMakerContrastComponent.vue")
);
// 仕入先／メーカー価格ファイル
Vue.component(
   "supplierfile-component",
   require("./components/SupplierFileComponent.vue")
);
// 仕入詳細
Vue.component(
   "purchase-detail-component",
   require("./components/PurchaseDetailComponent.vue")
);
// 支払予定一覧
Vue.component(
   "payment-list-component",
   require("./components/PaymentListComponent.vue")
);
// 選択肢マスタ
Vue.component(
   "choicelist-component",
   require("./components/ChoiceListComponent.vue")
);
Vue.component(
   "choiceedit-component",
   require("./components/ChoiceEditComponent.vue")
);
// 項目マスタ
Vue.component(
   "itemlist-component",
   require("./components/ItemListComponent.vue")
);
Vue.component(
   "itemedit-component",
   require("./components/ItemEditComponent.vue")
);
// 仕様項目マスタ
Vue.component(
   "specitemlist-component",
   require("./components/SpecItemListComponent.vue")
);
Vue.component(
   "specitemedit-component",
   require("./components/SpecItemEditComponent.vue")
);
// 引当
Vue.component(
   "stockallocation-component",
   require("./components/StockAllocationComponent.vue")
);
// 在庫移管
Vue.component(
   "stocktransferlist-component",
   require("./components/StockTransferListComponent.vue")
);
Vue.component(
   "stocktransferedit-component",
   require("./components/StockTransferEditComponent.vue")
);
// 在庫移管受入れ
Vue.component(
   "stocktransferacceptance-component",
   require("./components/StockTransferAcceptanceComponent.vue")
);
Vue.component(
   "stocktransferacceptanceshelfqr-component",
   require("./components/StockTransferAcceptanceShelfQrComponent.vue")
);
Vue.component(
   "stocktransferacceptancecheck-component",
   require("./components/StockTransferAcceptanceCheckComponent.vue")
);
// 在庫移管
Vue.component(
   "stocktransfer-component",
   require("./components/StockTransferComponent.vue")
);
Vue.component(
   "stocktransfersearch-component",
   require("./components/StockTransferSearchComponent.vue")
);
Vue.component(
   "stocktransfertruck-component",
   require("./components/StockTransferTruckComponent.vue")
);
// 倉庫内移動
Vue.component(
   "shelfmove-component",
   require("./components/ShelfMoveComponent.vue")
);
Vue.component(
   "shelfmovescanshelf-component",
   require("./components/ShelfMoveScanShelfComponent.vue")
);
Vue.component(
   "shelfmovescanqr-component",
   require("./components/ShelfMoveScanQrComponent.vue")
);
// 棚卸
Vue.component(
   "inventory-component",
   require("./components/InventoryComponent.vue")
);
Vue.component(
   "inventoryproductlist-component",
   require("./components/InventoryProductListComponent.vue")
);
Vue.component(
   "inventoryproductaddition-component",
   require("./components/InventoryProductAdditionComponent.vue")
);
// 棚番マスタ
Vue.component(
   "shelfnumberedit-component",
   require("./components/ShelfNumberEditComponent.vue")
);
// 返品処理
Vue.component(
   "returnprocess-component",
   require("./components/ReturnProcessComponent.vue")
);
// 返品受入れ
Vue.component(
   "acceptingreturnsinputselect-component",
   require("./components/AcceptingReturnsInputSelectComponent.vue")
);
Vue.component(
   "acceptingreturnsmattersearch-component",
   require("./components/AcceptingReturnsMatterSearchComponent.vue")
);
Vue.component(
   "acceptingreturnsdeliverylist-component",
   require("./components/AcceptingReturnsDeliveryListComponent.vue")
);
Vue.component(
   "acceptingreturnsquantityinput-component",
   require("./components/AcceptingReturnsQuantityInputComponent.vue")
);
Vue.component(
   "acceptingreturnsapproval-component",
   require("./components/AcceptingReturnsApprovalComponent.vue")
);
Vue.component(
   "acceptingreturnsqr-component",
   require("./components/AcceptingReturnsQrComponent.vue")
);
Vue.component(
   "acceptingreturnsinfoinput-component",
   require("./components/AcceptingReturnsInfoInputComponent.vue")
);
Vue.component(
   "acceptingreturnsstocksearch-component",
   require("./components/AcceptingReturnsStockSearchComponent.vue")
);
//廃棄
Vue.component(
   "discardlist-component",
   require("./components/DiscardListComponent.vue")
);
Vue.component(
   "discardconfirmation-component",
   require("./components/DiscardConfirmationComponent.vue")
);
Vue.component(
   "discardsign-component",
   require("./components/DiscardSignComponent.vue")
);
//メーカー引渡
Vue.component(
   "makerdelivery-component",
   require("./components/MakerDeliveryComponent.vue")
);
Vue.component(
   "makerdeliveryconfirmation-component",
   require("./components/MakerDeliveryConfirmationComponent.vue")
);
Vue.component(
   "makerdeliverysign-component",
   require("./components/MakerDeliverySignComponent.vue")
);
// 共通名称マスタ
Vue.component(
   "generallist-component",
   require("./components/GeneralListComponent.vue")
);
Vue.component(
   "generaledit-component",
   require("./components/GeneralEditComponent.vue")
);
// 権限管理マスタ
Vue.component(
   "authorityedit-component",
   require("./components/AuthorityEditComponent.vue")
);
// 倉庫別在庫管理
Vue.component(
   "orderpointlist-component",
   require("./components/OrderPointListComponent.vue")
);
// 売上一覧
Vue.component(
   "saleslist-component",
   require("./components/SalesListComponent.vue")
);
// 売上明細
Vue.component(
   "salesdetail-component",
   require("./components/SalesDetailComponent.vue")
);
// 請求一覧
Vue.component(
   "requestlist-component",
   require("./components/RequestListComponent.vue")
);
// 店頭販売
Vue.component(
   "countersale-component",
   require("./components/CounterSaleComponent.vue")
);
Vue.component(
   "countersaleconfirm-component",
   require("./components/CounterSaleConfirmComponent.vue")
);
Vue.component(
   "countersalesign-component",
   require("./components/CounterSaleSignComponent.vue")
);
// テスト発注
// Vue.component("test-component", require("./components/testComponent.vue"));
// 入金一覧
Vue.component(
   "depositlist-component",
   require("./components/DepositListComponent.vue")
);
// カレンダー
Vue.component(
   "calendardata-component",
   require("./components/CalendarDataComponent.vue")
);
Vue.component(
   "calendaredit-component",
   require("./components/CalendarEditComponent.vue")
);
// 中分類
Vue.component(
   "class-middle-list-component",
   require("./components/ClassMiddleListComponent.vue")
);
Vue.component(
   "class-middle-edit-component",
   require("./components/ClassMiddleEditComponent.vue")
);
// 工程
Vue.component(
   "class-small-list-component",
   require("./components/ClassSmallListComponent.vue")
);
Vue.component(
   "class-small-edit-component",
   require("./components/ClassSmallEditComponent.vue")
);
// 木材立米単価入力
Vue.component(
   "wood-unit-price-edit-component",
   require("./components/WoodUnitPriceEditComponent.vue")
);
// 仕入倉庫受取納品
Vue.component(
   "purchase-delivery-list-component",
   require("./components/PurchaseDeliveryListComponent.vue")
);
Vue.component(
   "purchase-delivery-search-component",
   require("./components/PurchaseDeliverySearchComponent.vue")
);
Vue.component(
   "purchase-delivery-photo-component",
   require("./components/PurchaseDeliveryPhotoComponent.vue")
);
Vue.component(
   "purchase-delivery-sign-component",
   require("./components/PurchaseDeliverySignComponent.vue")
);
//引取
Vue.component(
   "pickuplist-component",
   require("./components/PickupListComponent.vue")
);
Vue.component(
   "pickupprocess-component",
   require("./components/PickupProcessComponent.vue")
);
Vue.component(
   "pickup-photo-component",
   require("./components/PickupPhotoComponent.vue")
);
Vue.component(
   "pickup-sign-component",
   require("./components/PickupSignComponent.vue")
);

const app = new Vue({
   el: "#app",
});
