<template>
    <div>
        <loading-component :loading="loading" />

        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-sm-3">
                        <label>得意先名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="customer_name"
                            display-member-path="customer_name"
                            selected-value-path="id"
                            :initialized="initCustomer"
                            :selectedIndexChanged="changeIdxCustomer"
                            :selected-value="searchParams.customer_id"
                            :is-required="false"
                            :max-items="customerData.length"
                            :items-source="customerData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>案件番号</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="matter_no"
                            display-member-path="matter_no"
                            selected-value-path="matter_no"
                            :initialized="initMatterNo"
                            :selected-value="searchParams.matter_no"
                            :is-required="false"
                            :max-items="matterData.length"
                            :items-source="matterData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-3">
                        <label>案件名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_no"
                            :initialized="initMatterName"
                            :selected-value="searchParams.matter_name"
                            :is-required="false"
                            :max-items="matterData.length"
                            :items-source="matterData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>部門名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="id"
                            :initialized="initDepartment"
                            :selectedIndexChanged="changeIdxDepartment"
                            :selected-value="searchParams.department_id"
                            :is-required="false"
                            :max-items="departmentData.length"
                            :items-source="departmentData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>営業担当者名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :initialized="initSalesStaff"
                            :selected-value="searchParams.sales_staff_id"
                            :is-required="false"
                            :max-items="staffData.length"
                            :items-source="staffData">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <label>受注登録日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="searchParams.order_register_date_from"
                                :selected-value="searchParams.order_register_date_from"
                                :initialized="initOrderRegisterDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="searchParams.order_register_date_to"
                                :initialized="initOrderRegisterDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <label>見積番号</label>
                        <input type="text" class="form-control" v-model="searchParams.quote_no">
                    </div>
                    <div class="col-sm-2">
                        <label>メーカー名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="id"
                            :initialized="initMaker"
                            :selectedIndexChanged="changeMakerCombo"
                            :selected-value="searchParams.maker_id"
                            :is-required="false"
                            :max-items="makerData.length"
                            :items-source="makerData">
                        </wj-auto-complete>
                    </div>                    
                    <div class="col-sm-2">
                        <label>仕入先</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="id"
                            :initialized="initSupplier"
                            :selected-value="searchParams.supplier_id"
                            :is-required="false"
                            :max-items="supplierData.length"
                            :items-source="supplierData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>発注登録者名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :initialized="initOrderStaff"
                            :selected-value="searchParams.order_staff_id"
                            :is-required="false"
                            :max-items="staffData.length"
                            :items-source="staffData">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4">
                        <label>発注登録日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="searchParams.order_process_date_from"
                                :selected-value="searchParams.order_process_date_from"
                                :initialized="initOrderProcessDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="searchParams.order_process_date_to"
                                :initialized="initOrderProcessDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <label>発注番号</label>
                        <!-- <wj-auto-complete class="form-control"
                            search-member-path="order_no"
                            display-member-path="order_no"
                            selected-value-path="order_no"
                            :initialized="initOrder"
                            :selected-value="searchParams.order_no"
                            :is-required="false"
                            :max-items="orderData.length"
                            :items-source="orderData">
                        </wj-auto-complete> -->
                                <input type="text" class="form-control" v-model="searchParams.order_no">
                    </div>
                    <div class="col-sm-4">
                        <label>商品名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="product_name"
                            display-member-path="product_name"
                            selected-value-path="product_id"
                            :initialized="initProduct"
                            :selected-value="searchParams.product_id"
                            :is-required="false"
                            :selectedIndexChanged="selectProductName"
                            :items-source-function="productItemsSourceFunction">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>商品番号</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="product_code"
                            display-member-path="product_code"
                            selected-value-path="product_id"
                            :initialized="initProductCode"
                            :selected-value="searchParams.product_code_id"
                            :is-required="false"
                            :selectedIndexChanged="selectProductCode"
                            :items-source-function="productCodeItemsSourceFunction">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <div><label>発注状況</label></div>
                        <el-checkbox v-model="searchParams.chk_not_ordering_id" :true-label="FLG_ON" :false-label="FLG_OFF">未発注</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_editing_id" :true-label="FLG_ON" :false-label="FLG_OFF">編集中</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_ordered_id" :true-label="FLG_ON" :false-label="FLG_OFF">発注済</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_not_treated_id" :true-label="FLG_ON" :false-label="FLG_OFF">未処理</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_reserved_id" :true-label="FLG_ON" :false-label="FLG_OFF">引当済</el-checkbox>
                    </div>
                    <div class="col-sm-4">
                        <div><label>入荷先</label></div>
                        <el-checkbox v-model="searchParams.chk_address_kbn_site_id" :true-label="FLG_ON" :false-label="FLG_OFF">現場直送</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_address_kbn_company_id" :true-label="FLG_ON" :false-label="FLG_OFF">自社倉庫</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_address_kbn_supplier_id" :true-label="FLG_ON" :false-label="FLG_OFF">メーカー仕入先倉庫</el-checkbox>
                    </div>
                    <div class="col-sm-3">
                        <div><label>その他</label></div>
                        <el-checkbox v-model="searchParams.chk_intangible_id" :true-label="FLG_ON" :false-label="FLG_OFF">無形品含む</el-checkbox>
                        <el-checkbox v-model="searchParams.chk_no_arrival_plan_date_id" :true-label="FLG_ON" :false-label="FLG_OFF">入荷予定日入力なし含む</el-checkbox>
                    </div>
                </div>
                <div class="row row-center-item">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-search btn-md">発注検索</button>
                        &emsp;
                        <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                    </div>
                </div>
                <div class="clearfix"></div>
            </form>
        </div>
        <br>
        <!-- 検索結果グリッド -->
        <div class="col-sm-12 result-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <p class="col-md-12 col-xs-12 pull-right search-count">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <wj-multi-row
                    class="multirow-nonscrollbar"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row>
            </div>
        </div>
    </div>
</template>

<script>
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';

export default {
    data: () => ({
        loading: false,
        tableData: 0,
        layoutDefinition: null,
        urlparam: '',
        queryparam: '',
        // グリッド設定用
        gridSetting: {
            // リサイジング不可
            deny_resizing_col: [ 'status', 'attach_file', 'btn_detail', 'btn_print' ],
            // 非表示
            invisible_col: [ 'hid_seq_no' ],
        },
        searchParams: {
            customer_id: null,
            matter_no: null,
            matter_name: null,
            department_id: null,
            sales_staff_id: null,
            order_register_date_from: null,
            order_register_date_to: null,
            quote_no: null,
            maker_id: null,
            supplier_id: null,
            order_staff_id: null,
            order_process_date_from: null,
            order_process_date_to: null,
            order_no: null,
            product_id: null,
            product_name: null,
            product_code_id: null,
            product_code: null,
            // IDじゃないが共通関数の仕様上の問題があるので末尾に_IDをつけている
            chk_not_ordering_id: 0,
            chk_editing_id: 0,
            chk_ordered_id: 0,
            chk_not_treated_id: 0,
            chk_reserved_id: 0,
            chk_address_kbn_site_id: 0,
            chk_address_kbn_company_id: 0,
            chk_address_kbn_supplier_id: 0,
            chk_intangible_id: 0,
            chk_no_arrival_plan_date_id: 0,
        },
        clearSearchParams: {},

        hidGridData: Object,

        // 以下,initializedで紐づける変数
        wjOrderGrid: null,
        wjSearchObj: {
            customer: {},
            matter_no: {},
            matter_name: {},
            department: {},
            sales_staff: {},
            order_register_date_from: {},
            order_register_date_to: {},
            quote: {},
            maker:{},
            supplier:{},
            order_staff: {},
            order_process_date_from: {},
            order_process_date_to: {},
            order: {},
            product: {},
            product_code: {}
        },

        fonts: [
                {
                    "name": "ＭＳ ゴシック",
                    "source": "/fonts/ms_gothic/msgothic.ttc",
                    "postscriptName": "MS-Gothic"
                },
                {
                    "name": "Arial",
                    "source": "/fonts/arial/arialbd.ttf"
                },
            ]
    }),
    props: {
        // quoteData: Array,
        matterData: Array,
        // orderData: Array,
        customerData: Array,
        departmentData: Array,
        staffData: Array,
        staffDepartmentData: Object,
        makerData: Array,
        supplierData: Array,
        supplierMakerData: {},
        initSearchParams: {
            // type: Object,
            // staff_id: Number,
            // department_id: Number,
            // order_register_date_from: String,
            // order_register_date_to: String,
            // order_process_date_from: String,
            // order_process_date_to: String,
        },
    },
    created: function() {
        // クリアボタン用
        this.clearSearchParams = Vue.util.extend({}, this.searchParams);

        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryparam = window.location.search;
        if (this.queryparam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryparam, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.order_register_date_from == "") { this.searchParams.order_register_date_from = null };
            if (this.searchParams.order_register_date_to == "") { this.searchParams.order_register_date_to = null };
            if (this.searchParams.order_process_date_from == "") { this.searchParams.order_process_date_from = null };
            if (this.searchParams.order_process_date_to == "") { this.searchParams.order_process_date_to = null };
        } else {
            // 初回の検索条件をセット
            this.setInitSearchParams(this.searchParams, this.initSearchParams);
        }
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        var tmpSalesStaff = this.wjSearchObj.sales_staff.selectedValue;
        this.wjSearchObj.department.onSelectedIndexChanged();
        this.wjSearchObj.sales_staff.selectedValue = tmpSalesStaff;
        if (this.queryparam.length > 1) {
            if (this.rmUndefinedBlank(this.searchParams.product_id) != "" ) {
                // 商品検索のitemsSourceを作成し、選択する
                this.wjSearchObj.product.itemsSource = [{
                    product_id: this.searchParams.product_id,
                    product_name: this.searchParams.product_name,
                }];
                this.wjSearchObj.product.selectedValue = this.searchParams.product_id;

                this.wjSearchObj.product_code.itemsSource = [{
                    product_id: this.searchParams.product_code_id,
                    product_code: this.searchParams.product_code,
                }];
                this.wjSearchObj.product_code.selectedValue = this.searchParams.product_code_id;   
            }
            // itemsSourceFunctionを動かす為
            this.wjSearchObj.product.text = '';
            this.wjSearchObj.product.text = this.searchParams.product_name;
            this.wjSearchObj.product_code.text = '';
            this.wjSearchObj.product_code.text = this.searchParams.product_code;
            
            this.search();
        }
    },
    methods: {
        initCustomer: function(sender){
            this.wjSearchObj.customer = sender;
        },
        initMatterNo: function(sender){
            this.wjSearchObj.matter_no = sender;
        },
        initMatterName: function(sender){
            this.wjSearchObj.matter_name = sender;
        },
        initDepartment: function(sender){
            this.wjSearchObj.department = sender;
        },
        initSalesStaff: function(sender){
            this.wjSearchObj.sales_staff = sender;
        },
        initOrderRegisterDateFrom: function(sender){
            this.wjSearchObj.order_register_date_from = sender;
        },
        initOrderRegisterDateTo: function(sender){
            this.wjSearchObj.order_register_date_to = sender;
        },
        // initQuote: function(sender){
        //     this.wjSearchObj.quote = sender;
        // },
        initMaker: function(sender){
            this.wjSearchObj.maker = sender;
        },
        initSupplier: function(sender){
            this.wjSearchObj.supplier = sender;
        },
        initOrderStaff: function(sender){
            this.wjSearchObj.order_staff = sender;
        },
        initOrderProcessDateFrom: function(sender){
            this.wjSearchObj.order_process_date_from = sender;
        },
        initOrderProcessDateTo: function(sender){
            this.wjSearchObj.order_process_date_to = sender;
        },
        // initOrder: function(sender){
        //     this.wjSearchObj.order = sender;
        // },
        initSupplier: function(sender){
            this.wjSearchObj.supplier = sender;
        },
        initProduct: function(sender){
            this.wjSearchObj.product = sender;
        },
        initProductCode: function(sender){
            this.wjSearchObj.product_code = sender;
        },
        initMultiRow: function(multirow) {
            multirow.itemsSource = new wjCore.CollectionView();
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // // 設定更新
            this.applyGridSettings(multirow);
            // 高さ自動調整
            multirow.autoRowHeights = true,

            this.wjOrderGrid = multirow;
        },
        changeIdxCustomer: function(sender){
            // 得意先を変更したら案件名を絞り込む
            var tmpMatter = this.matterData;
            if (sender.selectedValue) {
                tmpMatter = [];
                for(var key in this.matterData) {
                    if (sender.selectedValue == this.matterData[key].customer_id) {
                        tmpMatter.push(this.matterData[key]);
                    }
                }             
            }
            this.wjSearchObj.matter_name.itemsSource = tmpMatter;
            this.wjSearchObj.matter_name.selectedIndex = -1;
        },
        changeMakerCombo: function (sender) {
            // メーカーが選択されている場合は仕入先を絞り込む
            this.wjSearchObj.supplier.itemsSource = this.supplierData;
            if (sender.selectedValue) {
                var matchSuppliers = [];
                for (const key in this.supplierMakerData[sender.selectedValue]) {
                    matchSuppliers.push(this.supplierMakerData[sender.selectedValue][key].supplier_id);
                }
                var tmpSupplierData = this.supplierData.filter((rec) => {
                    return (matchSuppliers.indexOf(rec.id) != -1)
                });
                this.wjSearchObj.supplier.itemsSource = tmpSupplierData;
            }
            this.wjSearchObj.supplier.selectedIndex = -1;
        },
        changeIdxDepartment: function(sender){
            this.wjSearchObj.department = sender;
            // 部門を変更したら担当者を絞り込む
            var tmpSalesStaff = this.staffData;
            if (sender.selectedValue) {
                tmpSalesStaff = [];
                if (this.staffDepartmentData[sender.selectedValue]) {
                    tmpSalesStaff = this.staffData.filter(rec => {
                        return (this.staffDepartmentData[sender.selectedValue].indexOf(rec.id) !== -1)
                    });
                }
            }
            this.wjSearchObj.sales_staff.itemsSource = tmpSalesStaff;
            this.wjSearchObj.sales_staff.selectedIndex = -1;
        },
        itemFormatter: function (panel, r, c, cell) {
            // 列ヘッダのセンタリング
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                cell.style.textAlign = 'center';
            }

            if (panel.cellType == wjGrid.CellType.Cell) {
                var colName = this.wjOrderGrid.getBindingColumn(panel, r, c).name;

                // スタイルリセット
                cell.style.color = '';
                cell.style.textAlign = '';

                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (colName) {
                    case 'cost_total':  // 発注額
                    case 'gross_profit_rate':   // 粗利率
                        cell.style.textAlign = 'right';
                        break;
                    case 'status': // 状況
                        var status = this.hidGridData[panel.rows[r].dataItem.seq_no].status;
                        var matterId = this.hidGridData[panel.rows[r].dataItem.seq_no].matter_id;
                        var ownStockFlg = this.hidGridData[panel.rows[r].dataItem.seq_no].own_stock_flg;

                        var elem = document.createElement('a');
                        elem.innerHTML = status.text;
                        elem.href = '/order-edit/' + matterId + this.urlparam;
                        elem.classList.add('status', status.class);
                        if (ownStockFlg) {
                            elem.style.pointerEvents = "none";
                        }
                        cell.appendChild(elem);
                        break;
                    case 'attach_file': // 添付
                        cell.style.textAlign = 'center';
                        break;
                    case 'btn_detail': // 詳細ボタン
                        // 詳細ボタン
                        var orderId = this.hidGridData[panel.rows[r].dataItem.seq_no].order_id;
                        var btnDetail = this.hidGridData[panel.rows[r].dataItem.seq_no].btn_detail;

                        var elem = document.createElement('a');
                        elem.innerHTML = '詳細';
                        elem.href = '/order-detail/' + orderId + this.urlparam;
                        elem.classList.add('btn', 'bnt-sm', 'btn-detail', 'order-detail');
                        if (!btnDetail.is_valid) {
                            elem.classList.add('disabled');
                        }
                        cell.appendChild(elem);
                        break;
                    case 'btn_print': // 印刷ボタン
                        var quoteId = this.hidGridData[panel.rows[r].dataItem.seq_no].quote_id;
                        var orderId = this.hidGridData[panel.rows[r].dataItem.seq_no].order_id;
                        var matterId = this.hidGridData[panel.rows[r].dataItem.seq_no].matter_id;
                        var btnPrint = this.hidGridData[panel.rows[r].dataItem.seq_no].btn_print;
                        var completeFlg = this.hidGridData[panel.rows[r].dataItem.seq_no].complete_flg;

                        var elem = document.createElement('a');
                        elem.innerHTML = '印刷';
                        elem.classList.add('btn', 'bnt-sm', 'btn-print', 'order-print');
                        if (completeFlg === this.FLG_OFF && btnPrint.is_valid) {
                            elem.addEventListener('click', function(e) {
                                this.openOrderReportTab(panel.rows[r].dataItem.seq_no, quoteId, orderId, matterId);
                            }.bind(this));
                        }else{
                             elem.classList.add('disabled');
                        }
                        cell.appendChild(elem);
                        break;
                }
            }
        },
        // 商品名オートコンプリート　選択肢生成
        productItemsSourceFunction: function(text, maxItems, callback) {
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合
            if(this.wjSearchObj.product.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH){
                return;
            }
            this.setASyncAutoCompleteList(this.wjSearchObj.product, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
        },
        // 商品番号オートコンプリート　選択肢生成
        productCodeItemsSourceFunction: function(text, maxItems, callback) {
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合
            if(this.wjSearchObj.product_code.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                return;
            }
            this.setASyncAutoCompleteList(this.wjSearchObj.product_code, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
        },
        // 商品名を選択したら、商品番号もセット
        selectProductName: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.product_code = item.product_code;
                this.wjSearchObj.product_code.text = item.product_code;
            }
        },
        // 商品番号を選択したら、商品名もセット
        selectProductCode: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.product = item.product_name;
                this.wjSearchObj.product.text = item.product_name;
            }
        },
        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clearSearch() {

            this.setInitSearchParams(this.searchParams, this.clearSearchParams);

            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = '';
            });
            // this.searchParams.chk_not_ordering_id = this.FLG_OFF;
            // this.searchParams.chk_editing_id = this.FLG_OFF;
            // this.searchParams.chk_ordered_id = this.FLG_OFF;
            // this.searchParams.chk_not_treated_id = this.FLG_OFF;
            // this.searchParams.chk_reserved_id = this.FLG_OFF;
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();

            // 得意先ID
            params.append('customer_id', this.rmUndefinedZero(this.wjSearchObj.customer.selectedValue));
            // 案件番号 [ 案件番号 案件名 ]
            var matterNo = "";
            if (this.wjSearchObj.matter_no.selectedValue) {
               matterNo = this.wjSearchObj.matter_no.selectedValue;
            } else if(this.wjSearchObj.matter_name.selectedValue) {
               matterNo = this.wjSearchObj.matter_name.selectedValue;
            }
            params.append('matter_no', this.rmUndefinedZero(matterNo));
            // 部門ID
            params.append('department_id', this.rmUndefinedZero(this.wjSearchObj.department.selectedValue));
            // 担当者ID（営業担当）
            params.append('sales_staff_id', this.rmUndefinedZero(this.wjSearchObj.sales_staff.selectedValue));
            // 受注登録日（FROM-TO）
            params.append('order_register_date_from', this.rmUndefinedBlank(this.wjSearchObj.order_register_date_from.text));
            params.append('order_register_date_to', this.rmUndefinedBlank(this.wjSearchObj.order_register_date_to.text));
            // 見積番号
            // params.append('quote_no', this.rmUndefinedZero(this.wjSearchObj.quote.selectedValue));
            params.append('quote_no', this.rmUndefinedBlank(this.searchParams.quote_no));
            // メーカー
            params.append('maker_id', this.rmUndefinedZero(this.wjSearchObj.maker.selectedValue));
            // 仕入先
            params.append('supplier_id', this.rmUndefinedZero(this.wjSearchObj.supplier.selectedValue));
            // 担当者ID（発注担当）
            params.append('order_staff_id', this.rmUndefinedZero(this.wjSearchObj.order_staff.selectedValue));
            // 発注処理日（FROM-TO）
            params.append('order_process_date_from', this.rmUndefinedBlank(this.wjSearchObj.order_process_date_from.text));
            params.append('order_process_date_to', this.rmUndefinedBlank(this.wjSearchObj.order_process_date_to.text));
            // 発注番号
            // params.append('order_no', this.rmUndefinedZero(this.wjSearchObj.order.selectedValue));
            params.append('order_no', this.rmUndefinedBlank(this.searchParams.order_no));
            // // 商品ID
            // params.append('product_id', this.rmUndefinedZero(this.wjSearchObj.product.selectedValue));
            // 商品名
            params.append('product_name', this.rmUndefinedZero(this.wjSearchObj.product.text));
            // 商品番号
            params.append('product_code', this.rmUndefinedZero(this.wjSearchObj.product_code.text));
            // 未発注
            params.append('not_ordering', this.rmUndefinedBlank(this.searchParams.chk_not_ordering_id));
            // 編集中
            params.append('editing', this.rmUndefinedBlank(this.searchParams.chk_editing_id));
            // 発注済
            params.append('ordered', this.rmUndefinedBlank(this.searchParams.chk_ordered_id));
            // 未処理
            params.append('not_treated', this.rmUndefinedBlank(this.searchParams.chk_not_treated_id));
            // 引当済
            params.append('reserved', this.rmUndefinedBlank(this.searchParams.chk_reserved_id));
            // 入荷先　現場
            params.append('chk_address_kbn_site_flg', this.rmUndefinedBlank(this.searchParams.chk_address_kbn_site_id));
            // 入荷先　自社倉庫
            params.append('chk_address_kbn_company_flg', this.rmUndefinedBlank(this.searchParams.chk_address_kbn_company_id));
            // 入荷先　メーカー倉庫
            params.append('chk_address_kbn_supplier_flg', this.rmUndefinedBlank(this.searchParams.chk_address_kbn_supplier_id));
            // 無形品含むフラグ
            params.append('chk_intangible_flg', this.rmUndefinedBlank(this.searchParams.chk_intangible_id));
            // 入荷予定日なしフラグ
            params.append('chk_no_arrival_plan_date_flg', this.rmUndefinedBlank(this.searchParams.chk_no_arrival_plan_date_id));

            axios.post('/order-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
                    this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue));
                    this.urlparam += '&' + 'sales_staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.sales_staff.selectedValue));
                    this.urlparam += '&' + 'order_register_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_register_date_from.text));
                    this.urlparam += '&' + 'order_register_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_register_date_to.text));
                    // this.urlparam += '&' + 'quote_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.quote.selectedValue));
                    this.urlparam += '&' + 'quote_no=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.quote_no));
                    this.urlparam += '&' + 'maker_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.maker.selectedValue));
                    this.urlparam += '&' + 'supplier_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier.selectedValue));
                    this.urlparam += '&' + 'order_staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_staff.selectedValue));
                    this.urlparam += '&' + 'order_process_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_process_date_from.text));
                    this.urlparam += '&' + 'order_process_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_process_date_to.text));
                    // this.urlparam += '&' + 'order_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order.selectedValue));
                    this.urlparam += '&' + 'order_no=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.order_no));
                    this.urlparam += '&' + 'product_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product.selectedValue));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product.text));
                    this.urlparam += '&' + 'product_code_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product_code.selectedValue));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product_code.text));
                    this.urlparam += '&' + 'chk_not_ordering_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_not_ordering_id));
                    this.urlparam += '&' + 'chk_editing_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_editing_id));
                    this.urlparam += '&' + 'chk_ordered_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_ordered_id));
                    this.urlparam += '&' + 'chk_not_treated_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_not_treated_id));
                    this.urlparam += '&' + 'chk_reserved_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_reserved_id));
                    this.urlparam += '&' + 'chk_address_kbn_site_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_address_kbn_site_id));
                    this.urlparam += '&' + 'chk_address_kbn_company_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_address_kbn_company_id));
                    this.urlparam += '&' + 'chk_address_kbn_supplier_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_address_kbn_supplier_id));
                    this.urlparam += '&' + 'chk_intangible_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_intangible_id));
                    this.urlparam += '&' + 'chk_no_arrival_plan_date_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.chk_no_arrival_plan_date_id));

                    var itemsSource = [];
                    response.data.forEach(element => {
                        this.hidGridData[element.seq_no] = {
                            quote_id: element.quote_id,
                            order_id: element.order_id,
                            matter_id: element.matter_id,
                            status: element.status,
                            own_stock_flg: element.own_stock_flg,
                            btn_detail: element.btn_detail,
                            btn_print: element.btn_print,
                            complete_flg: element.complete_flg
                        };
                        itemsSource.push({
                            seq_no: element.seq_no,
                            order_no: element.order_no,
                            staff_name: element.staff_name,
                            department_name: element.department_name,
                            matter_no: element.matter_no,
                            matter_name: element.matter_name,
                            maker_name: element.maker_name,
                            supplier_name: element.supplier_name,
                            cost_total: element.cost_total,
                            gross_profit_rate: element.gross_profit_rate,
                            order_datetime: element.order_datetime,
                            order_staff_name: element.order_staff_name,
                            product_name: element.product_name,
                            sales_support_comment: element.sales_support_comment,
                            attach_file: element.attach_file
                            // address: element.address
                        })
                    });
                    // データセット
                    this.wjOrderGrid.itemsSource = itemsSource;
                    this.tableData = itemsSource.length;
                    // 設定更新
                    this.applyGridSettings(this.wjOrderGrid);
                    // 描画更新
                    this.wjOrderGrid.refresh();
                }
                this.loading = false
            }.bind(this))

            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    window.onbeforeunload = null;
                    location.reload()
                }
                this.loading = false
            }.bind(this))
        },
        openOrderReportTab(seqNo, quoteId, orderId, matterId){
            this.loading = true;
            var params = new URLSearchParams();
            params.append('order_id', orderId);
            axios.post('/order-list/get-report-data', params)
            .then( function (response) {
                if (response.data) {
                    var report = new GC.ActiveReports.Core.PageReport();
                    report.load('/template/reports/order-v2.rdlx-json')
                    .then(
                        function(){
                            var url = 
                            report._instance.definition.DataSources[0].ConnectionProperties.ConnectString = "jsondata=" + JSON.stringify(response.data);
                            return report.load(report._instance.definition);
                        })
                    .then(
                        function(){
                            var settings = {
                                pdfVersion:"1.7",
                                fonts: this.fonts,
                            }
                            report.run()
                            .then(function(pageDocument) {
                                return GC.ActiveReports.PdfExport.exportDocument(pageDocument, settings);
                            })
                            .then(function(result) {
                                // PDFアップロード
                                var params = new FormData();
                                params.append('quote_id', quoteId);
                                params.append('order_id', orderId);
                                params.append('matter_id', matterId);
                                params.append('file', result.data);
                                axios.post('/order-list/upload-pdf', params, {headers: {'Content-Type': 'multipart/form-data'}})
                                .then(function (response) {
                                    this.loading = false;
                                    if (response.data) {
                                        if (response.data.status) {
                                            // 成功
                                            this.hidGridData[seqNo].status = response.data.list_status;
                                            this.wjOrderGrid.refresh();
                                            window.open('/order-report/print/' + orderId);
                                        }else{
                                            // 失敗
                                            if (response.data.message) {
                                                alert(response.data.message);
                                            }else{
                                                alert(MSG_ERROR);
                                            }
                                        }
                                    } else {
                                        alert(MSG_ERROR);
                                    }
                                }.bind(this))
                                .catch(function (error) {
                                    this.loading = false;
                                    alert(MSG_ERROR);
                                }.bind(this))
                                // .finally(function() {
                                //     window.open('/order-report/print/' + orderId);
                                // }.bind(this))
                            }.bind(this))
                        }.bind(this)
                    )
                }else{
                    this.loading = false;
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false;
                alert(MSG_ERROR);
            }.bind(this))
        },
        // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
        applyGridSettings(grid) {
            // リサイジング設定
            grid.columns.forEach(element => {
                if (element.name != undefined && this.gridSetting.deny_resizing_col.indexOf(element.name) >= 0) {
                    element.allowResizing = false;
                }
            });
            // 非表示設定
            grid.columns.forEach(element => {
                if (element.name != undefined && this.gridSetting.invisible_col.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });
        },
        // グリッドレイアウト
        getLayout() {
            return [
                { cells: [
                    { name:'order_no', binding: 'order_no', header: '発注番号', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'department_name', binding: 'department_name', header: '部門', isReadOnly: true, width: 140, minWidth: GRID_COL_MIN_WIDTH },
                    { name:'staff_name', binding: 'staff_name', header: '担当者', isReadOnly: true, width: 140, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'maker_name', binding: 'maker_name', header: 'メーカー名', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                    { name:'supplier_name', binding: 'supplier_name', header: '仕入先名', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'matter_no', binding: 'matter_no', header: '案件番号', isReadOnly: true, width: 240, minWidth: GRID_COL_MIN_WIDTH },
                    { name:'matter_name', binding: 'matter_name', header: '案件名', isReadOnly: true, width: 240, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'cost_total', binding: 'cost_total', header: '発注額', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    { name:'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'status', header: '状況', isReadOnly: true, width: 90 },
                ]},
                { cells: [
                    { name:'order_datetime', binding: 'order_datetime', header: '発注登録日時', isReadOnly: true, width: 170, minWidth: GRID_COL_MIN_WIDTH },
                    { name:'order_staff_name', binding: 'order_staff_name', header: '発注登録者', isReadOnly: true, width: 170, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'product_name', binding: 'product_name', header: '発注商品', isReadOnly: true, width: 170, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'sales_support_comment', binding: 'sales_support_comment', header: '支援課コメント', isReadOnly: true, width:'*', minWidth: GRID_COL_MIN_WIDTH },
                    // { name:'address', binding: 'address', header: '住所', isReadOnly: true, width:'*', minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name:'attach_file', binding: 'attach_file', header: '添付', isReadOnly: true, width: 70 },
                ]},
                { cells: [
                    { name:'btn_detail', header: '詳細', isReadOnly: true, width: 90 },
                    { name:'btn_print', header: '印刷', isReadOnly: true, width: 90 },
                ]},
                /* 以降、非表示カラム */
                { cells: [
                    { name:'hid_seq_no', binding: 'seq_no' },
                ]},
            ]
        }
    }
};
</script>

<style>
/*********************************
    枠サイズ等
**********************************/
/* 検索項目 */
.search-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
/* 検索結果 */
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
/* グリッド */
.wj-multirow {
    height: 850px;
    margin: 6px 0;
}

/*********************************
    グリッド各項目
**********************************/
.btn-group{
    width:45%;
}
/** 状況 */
.status{
    display: block !important;
    height:50px;
    width:100%;
    line-height: 50px;
    text-align: center;
    color: #ffffff;
}
.status:visited, .status:hover{
    color: #FFFFFF;
}
.status.not-treated{
    background-color:#FF3B30;
}
.status.editing{
    background-color:#666666;
}
.status.not-ordering{
    background-color:#5AC8FA;
}
.status.ordered{
    background-color:#4CD964;
}
.status.reserved{
    background-color:#4CD964;
}
/* 詳細,印刷 */
.order-detail, .order-print{
    display: block !important;
    width: 100%;
    height: 100%;
    text-align: center;
}
.order-detail > a, .order-print > a {
    width: 100%;
    height: 100%;;
}
/*********************************
    その他
**********************************/
.lbl-addon-ex{
    border: none;
    background: none;
}
.search-count{
    text-align: right;
}
.row-center-item{
    text-align: center;
}
</style>