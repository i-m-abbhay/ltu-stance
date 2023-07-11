<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">得意先名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="customer_name"
                            display-member-path="customer_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="searchParams.customer_name"
                            :is-required="false"
                            :initialized="initCustomer"
                            :max-items="customerlist.length"
                            :items-source="customerlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2 col-sm-2">
                        <label class="control-label">案件番号</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="matter_no"
                            display-member-path="matter_no"
                            selected-value-path="matter_no"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_no"
                            :is-required="false"
                            :initialized="initMatterNo"
                            :max-items="matterlist.length"
                            :items-source="matterlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">案件名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_name"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_name"
                            :is-required="false"
                            :initialized="initMatterName"
                            :max-items="matterlist.length"
                            :items-source="matterlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2 col-sm-2">
                        <label class="control-label">入荷倉庫</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="warehouse_name"
                            :selected-index="-1"
                            :selected-value="searchParams.warehouse_name"
                            :is-required="false"
                            :initialized="initWarehouse"
                            :max-items="warehouselist.length"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2 col-sm-2">
                        <label class="control-label">部門名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="department_name"
                            :selected-index="-1"
                            :selected-value="searchParams.department_name"
                            :selectedIndexChanged="changeIdxDepartment"
                            :is-required="false"
                            :initialized="initDepartment"
                            :max-items="departmentlist.length"
                            :items-source="departmentlist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">発注番号</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="order_no"
                            display-member-path="order_no"
                            selected-value-path="order_no"
                            :selected-index="-1"
                            :selected-value="searchParams.order_no"
                            :is-required="false"
                            :initialized="initOrder"
                            :max-items="orderlist.length"
                            :items-source="orderlist">
                        </wj-auto-complete>
                    </div>
                    <!-- TODO: 仕入先、メーカーを分ける -->
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">メーカー名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="supplier_name"
                            :selected-index="-1"
                            :selected-value="searchParams.maker_name"
                            :is-required="false"
                            :initialized="initMaker"
                            :max-items="makerlist.length"
                            :items-source="makerlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">仕入先名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="supplier_name"
                            :selected-index="-1"
                            :selected-value="searchParams.supplier_name"
                            :is-required="false"
                            :initialized="initSupplier"
                            :max-items="supplierlist.length"
                            :items-source="supplierlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">営業担当者名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="staff_name"
                            :selected-index="-1"
                            :selected-value="searchParams.staff_name"
                            :is-required="false"
                            :initialized="initStaff"
                            :max-items="stafflist.length"
                            :items-source="stafflist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">商品管理ID</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="qr_code"
                            display-member-path="qr_code"
                            selected-value-path="qr_code"
                            :selected-index="-1"
                            :selected-value="searchParams.qr_code"
                            :is-required="false"
                            :initialized="initQr"
                            :max-items="qrcodelist.length"
                            :items-source="qrcodelist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">商品番号</label>
                        <input type="text" class="form-control" v-model="searchParams.product_code">
                        <!-- <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="product_code"
                            display-member-path="product_code"
                            selected-value-path="product_code"
                            :selected-index="-1"
                            :selected-value="searchParams.product_code"
                            :is-required="false"
                            :initialized="initProductCode"
                            :max-items="productlist.length"
                            :items-source="productlist">
                        </wj-auto-complete> -->
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">商品名</label>
                        <input type="text" class="form-control" v-model="searchParams.product_name">
                        <!-- <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="product_name"
                            display-member-path="product_name"
                            selected-value-path="product_name"
                            :selected-index="-1"
                            :selected-value="searchParams.product_name"
                            :is-required="false"
                            :initialized="initProductName"
                            :max-items="productlist.length"
                            :items-source="productlist">
                        </wj-auto-complete> -->
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2">
                        <label>発注日</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.from_order_date"
                            :selected-value="searchParams.from_order_date"
                            :initialized="initFromOrderDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-1">
                        <label>&nbsp;</label>
                        <label class="help-block" style="text-align:center;">～</label>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.to_order_date"
                            :selected-value="searchParams.to_order_date"
                            :initialized="initToOrderDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-2">
                        <label>入荷予定日</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.from_arrival_date"
                            :selected-value="searchParams.from_arrival_date"
                            :initialized="initFromArrivalDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-1">
                        <label>&nbsp;</label>
                        <label class="help-block" style="text-align:center;">～</label>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.to_arrival_date"
                            :selected-value="searchParams.to_arrival_date"
                            :initialized="initToArrivalDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-2">
                        <label>出荷予定日</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.from_shipping_date"
                            :selected-value="searchParams.from_shipping_date"
                            :initialized="initFromShippingDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-1">
                        <label>&nbsp;</label>
                        <label class="help-block" style="text-align:center;">～</label>
                    </div>
                    <div class="col-sm-2">
                        <label>&nbsp;</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.to_shipping_date"
                            :selected-value="searchParams.to_shipping_date"
                            :initialized="initToShippingDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                </div>
                <div class="row">
                    <div class="form-group">
                        <div class="col-sm-offset-5 col-sm-1">
                            <button type="submit" class="btn btn-search btn-sm form-control">検索</button>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-clear btn-sm form-control" v-on:click="clear">クリア</button>
                        </div>
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
                    <div class="col-sm-4">
                        <div class="input-group">
                            <el-checkbox class="col-md-3 col-xs-12" v-model="filterList.not_arrival" true-label="0" false-label="-1" @input="filter($event)">未入荷</el-checkbox>
                            <el-checkbox class="col-md-3 col-xs-12" v-model="filterList.part_arrival" true-label="2" false-label="-1" @input="filter($event)">一部入荷</el-checkbox>
                            <el-checkbox class="col-md-3 col-xs-12" v-model="filterList.done_arrival" true-label="1" false-label="-1" @input="filter($event)">入荷済</el-checkbox>
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">　検索結果：{{ tableData }}件</p>
                        <div class="col-md-7 pull-right">
                            <button type="button" class="btn btn-print" v-on:click="exportExcel">印刷</button>
                        <wj-collection-view-navigator
                            headerFormat="{currentPage: n0} / {pageCount: n0}"
                            :byPage="true"
                            :cv="arrivals" 
                        />
                        </div>
                    </div>
                </div>
                <wj-multi-row
                    :itemsSource="arrivals"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row>
            </div>
        </div>
    </div>
</template>

<script>
/* TODO:app.jsで読み込んでいるので、出来るなら二重インポートしたくない */
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';

export default {
    data: () => ({
        loading: false,
        FLG_ON : 1,
        FLG_OFF: 0,

        NOT_FLG: 0,
        DONE_FLG: 1,
        PART_FLG: 2,

        filterList: {
            not_arrival: -1,
            part_arrival: -1,
            done_arrival: -1,
        },

        tableData: 0,
        urlparam: '',
        queryParam: '',
        lastSearchParam: null,

        keepDOM: {},
        arrivals: new wjCore.CollectionView(),
        layoutDefinition: null,
        // クエリパラメータ復帰時,初回表示にしか使うな（created⇒initialized以降値を変更してもwijmoに反映されたりされなかったり）
        searchParams: {
            customer_name: null,
            matter_no: null,
            matter_name: null,
            warehouse_name: null,
            department_name: null,
            staff_name: null,
            order_no: null,
            supplier_name: null,
            maker_name: null,
            qr_code: null,
            product_code: null,
            product_name: null,
            from_order_date: null,
            to_order_date: null,
            from_arrival_date: null,
            to_arrival_date: null,
            from_shipping_date: null,
            to_shipping_date: null,
        },
        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ 入荷状況　チェックボックス　ID ]
            deny_resizing_col: [0, 7],
            // 非表示[ ID ]
            invisible_col: [8],
        },
        gridPKCol: 8,
        // 以下,initializedで紐づける変数
        wjArrivalGrid: null,
        wjSearchObj: {
            customer_name: {},
            matter_no: {},
            matter_name: {},
            warehouse_name: {},
            department_name: {},
            staff_name: {},
            order_no: {},
            supplier_name: {},
            maker_name: {},
            qr_code: {},
            product_code: {},
            product_name: {},
            from_order_date: {},
            to_order_date: {},
            from_arrival_date: {},
            to_arrival_date: {},
            from_shipping_date: {},
            to_shipping_date: {},
        },
    }),
    props: {
        customerlist: Array,
        matterlist: Array,
        warehouselist: Array,
        stafflist: Array,
        orderlist: Array,
        supplierlist: Array,
        makerlist: Array,
        // productlist: Array,
        departmentlist: Array,
        qrcodelist: Array,
        staffDepartmentData: Object,
        initsearchparams: {
            type: Object,
            department_name: String,
            from_order_date: String,
            to_order_date: String,
            from_arrival_date: String,
            to_arrival_date: String,
            from_shipping_date: String,
            to_shipping_date: String,
        }
    },
    created: function() {
        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.from_order_date == "") { this.searchParams.from_order_date = null };
            if (this.searchParams.to_order_date == "") { this.searchParams.to_order_date = null };
            if (this.searchParams.from_arrival_date == "") { this.searchParams.from_arrival_date = null };
            if (this.searchParams.to_arrival_date == "") { this.searchParams.to_arrival_date = null };
            if (this.searchParams.from_shipping_date == "") { this.searchParams.from_shipping_date = null };
            if (this.searchParams.to_shipping_date == "") { this.searchParams.to_shipping_date = null };
        }else{
            // 初回の検索条件をセット
            this.setInitSearchParams(this.searchParams, this.initsearchparams);
        }
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        if (this.queryParam.length > 1) {
            this.search();
        }
    },
    methods: {
        changeIdxDepartment: function(sender){
            // 部門を変更したら担当者を絞り込む
            var tmpStaff = this.stafflist;
            if (sender.selectedValue) {
                tmpStaff = [];
                if (this.staffDepartmentData[sender.selectedItem.id]) {
                    tmpStaff = this.stafflist.filter(rec => {
                        return (this.staffDepartmentData[sender.selectedItem.id].indexOf(rec.id) !== -1)
                    });
                }
            }
            this.wjSearchObj.staff_name.itemsSource = tmpStaff;
            this.wjSearchObj.staff_name.selectedIndex = -1;
        },
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;

            this.wjArrivalGrid = multirow;
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('customer_name', this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));
            params.append('matter_no', this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
            params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
            params.append('warehouse_name', this.rmUndefinedBlank(this.wjSearchObj.warehouse_name.text));
            params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
            params.append('order_no', this.rmUndefinedBlank(this.wjSearchObj.order_no.text));
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('maker_name', this.rmUndefinedBlank(this.wjSearchObj.maker_name.text));
            params.append('staff_name', this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));
            params.append('qr_code', this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('from_order_date', this.rmUndefinedBlank(this.wjSearchObj.from_order_date.text));
            params.append('to_order_date', this.rmUndefinedBlank(this.wjSearchObj.to_order_date.text));
            params.append('from_arrival_date', this.rmUndefinedBlank(this.wjSearchObj.from_arrival_date.text));
            params.append('to_arrival_date', this.rmUndefinedBlank(this.wjSearchObj.to_arrival_date.text));
            params.append('from_shipping_date', this.rmUndefinedBlank(this.wjSearchObj.from_shipping_date.text));
            params.append('to_shipping_date', this.rmUndefinedBlank(this.wjSearchObj.to_shipping_date.text));

            axios.post('/arrival-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                    this.urlparam += '&' + 'warehouse_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.warehouse_name.text));
                    this.urlparam += '&' + 'order_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_no.text));
                    this.urlparam += '&' + 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                    this.urlparam += '&' + 'maker_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.maker_name.text));
                    this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));
                    this.urlparam += '&' + 'qr_code=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'from_order_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_order_date.text));
                    this.urlparam += '&' + 'to_order_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_order_date.text));
                    this.urlparam += '&' + 'from_arrival_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_arrival_date.text));
                    this.urlparam += '&' + 'to_arrival_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_arrival_date.text));
                    // this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product_name.text));
                    this.urlparam += '&' + 'from_shipping_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_shipping_date.text));
                    this.urlparam += '&' + 'to_shipping_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_shipping_date.text));
                    // 検索条件保持
                    this.lastSearchParam = params;

                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            arrivalStatus: document.createElement('div'),
                            cancel_arrival_btn: document.createElement("div"),
                        }

                        // 入荷状況
                        this.keepDOM[element.id].arrivalStatus.innerHTML = element.arrival_status.text;
                        this.keepDOM[element.id].arrivalStatus.classList.add(element.arrival_status.class, 'status');

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            order_id: element.order_id,
                            arrival_id: element.arrival_id,
                            delivery_date: element.delivery_date,
                            arrival_plan_date: element.arrival_plan_date,
                            arrival_date: element.arrival_date,
                            matter_id: element.matter_id,
                            matter_name: element.matter_name,
                            customer_id: element.customer_id,
                            order_no: element.order_no,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            model: element.model,
                            qr_code: element.qr_code,
                            supplier_name: element.supplier_name,
                            warehouse_id: element.warehouse_id,
                            warehouse_name: element.warehouse_name,
                            order_quantity: element.order_quantity,
                            stock_quantity: element.stock_quantity,
                            arrival_quantity: element.arrival_quantity,
                            arrival_status: element.arrival_status,
                            qr_cnt: element.qr_cnt,
                            load_cnt: element.load_cnt,
                            purchase_cnt: element.purchase_cnt,
                            own_stock_flg: element.own_stock_flg,
                            deletable: element.deletable,
                        })
                    });
                    // データセット
                    // グリッドのページング設定
                    var view = new wjCore.CollectionView(itemsSource, {
                        pageSize: 5,
                    })
                    this.arrivals = view;
                    this.wjArrivalGrid.itemsSource = this.arrivals;

                    this.filter();
                    this.tableData = dataCount;

                    // 設定更新
                    this.wjArrivalGrid = this.applyGridSettings(this.wjArrivalGrid);
                    // 描画更新
                    this.wjArrivalGrid.refresh();
                }
                this.loading = false

            }.bind(this))

            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                location.reload()
            }.bind(this))
        },
        itemFormatter: function (panel, r, c, cell) {
            if (this.keepDOM === undefined || this.keepDOM === null) {
                return;
            }

            // 列ヘッダのセンタリング
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                cell.style.textAlign = 'center';
            }

            if (panel.cellType == wjGrid.CellType.Cell) {
                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                 var item = this.wjArrivalGrid.rows[r];
                 var dataItem = panel.rows[r].dataItem;
                switch (c) {
                    case 0: // 入荷状況
                        if (item.recordIndex == 0) {
                            if (dataItem !== null) {
                                var div = document.createElement('div');
                                div.innerHTML = dataItem.arrival_status.text;
                                div.classList.add(dataItem.arrival_status.class, 'status');

                                cell.appendChild(div);
                            }
                        } else if (item.recordIndex == 1) {
                            cell.style.textAlign = 'right';
                        }
                        break;
                    case 1: // 入荷日
                        cell.style.textAlign = 'right';
                        break;
                    case 5: 

                        break;
                    case 6: 
                        cell.style.textAlign = 'right';
                        break;
                    case 7: //入荷取り消し
                        // cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].cancel_arrival_btn);

                        //入荷取消ボタン
                        cell.style.padding = '0px';
                        if(dataItem !== undefined){
                            var rId1 = 'cancel-arrival-' + dataItem.id;
                            // QRまとめ、配送済、仕入確定済み、入荷一時置場に存在しない場合は不可
                            var isDisable = false;
                            if (dataItem.arrival_status.val == this.FLG_OFF) {
                                isDisable = true;
                            }

                            // QRが統合されている
                            if (dataItem.qr_cnt > 1) {
                                isDisable = true;
                            }

                            if (dataItem.own_stock_flg == this.FLG_OFF) {
                                // 発注品

                                // 入荷時のQRが存在しない
                                if (dataItem.qr_cnt == 0) {
                                    isDisable = true;
                                }
                                // 出荷配送済は取消不可
                                if (dataItem.load_cnt > 0) {
                                    isDisable = true;
                                }
                                // 仕入確定済みは取消不可
                                if (dataItem.purchase_cnt > 0) {
                                    isDisable = true;
                                }
                            } else if (dataItem.own_stock_flg == this.FLG_ON) {
                                // 在庫発注品
                                // 仕入確定済みは取消不可
                                if (dataItem.purchase_cnt > 0) {
                                    isDisable = true;
                                }
                                // 入荷一時置場の在庫が足りない場合は取消不可
                                if (dataItem.deletable == this.FLG_OFF) {
                                    isDisable = true;
                                }
                            }


                            if (!isDisable) {
                                var div = document.createElement('div');
                                div.classList.add('btn-group', 'status-btn-group');
                                div.setAttribute("data-toggle","buttons");

                                var btnCancel = document.createElement('button');
                                btnCancel.type = 'button';
                                btnCancel.id = rId1;
                                btnCancel.classList.add('btn', 'btn-delete', 'btn-cancel');
                                btnCancel.innerHTML = '取消';
                                if (isDisable) {
                                    btnCancel.classList.add('btn-disable');
                                    btnCancel.disabled = isDisable;
                                }

                                btnCancel.addEventListener('click', function (e) {
                                    this.onClickCancelArrival(dataItem);
                                }.bind(this));

                                div.appendChild(btnCancel);

                                cell.appendChild(div);
                            }
                        }

                        break;
                }
            }
        },
        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clear: function() {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = null;
            });
            var searchParams = this.searchParams;
            Object.keys(searchParams).forEach(function (key) {
                searchParams[key] = '';
            });
        },
        getLayout() {
            return [
                {
                    header: '入荷状況', cells: [
                        { header: '入荷状況', width: 100, isReadOnly: true, },
                        { binding: 'delivery_date', header: '出荷予定日', width: 100, isReadOnly: true, },
                    ]
                },
                {
                    header: '入荷日', cells: [
                        { binding: 'arrival_plan_date', header: '入荷予定日', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true,},
                        { binding: 'arrival_date', header: '入荷日', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true,},
                    ]
                },
                {
                    header: '案件／発注', cells: [
                        { binding: 'matter_name', header: '案件名', minWidth: GRID_COL_MIN_WIDTH, width: 280, isReadOnly: true, },                        
                        { binding: 'order_no', header: '発注番号', minWidth: GRID_COL_MIN_WIDTH, width: 280, isReadOnly: true, },
                    ]
                },
                {
                    header: '商品情報１', cells: [
                        { binding: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 210 , isReadOnly: true,},
                        { binding: 'qr_code', header: '商品管理ID', minWidth: GRID_COL_MIN_WIDTH, width: 210 , isReadOnly: true,},
                    ]
                },
                {
                    header: '商品情報２', cells: [
                        { binding: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true, },
                        { binding: 'model', header: '型式・規格', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true, },
                    ]
                },
                {
                    header: '仕入先／倉庫', cells: [
                        { binding: 'supplier_name', header: 'メーカー／仕入先', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true, },
                        { binding: 'warehouse_name', header: '入荷先倉庫', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true, },
                    ]
                },
                {
                    header: '入荷数', cells: [
                        { binding: 'stock_quantity', header: '予定数', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true, },
                        { binding: 'arrival_quantity', header: '入荷済数', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true, },
                    ]
                },
                  {
                    header: '入荷取消', cells: [
                        { name: 'cancel_arrival', header: '入荷取消', minWidth: GRID_COL_MIN_WIDTH, width: 80 },
                    ]
                },
                // 以下非表示
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID' },
                    ]
                },           
            ]
        },
        applyGridSettings(grid) {
            // リサイジング設定
            grid.columns.forEach(element => {
                if (this.gridSetting.deny_resizing_col.indexOf(element.index) >= 0) {
                    element.allowResizing = false;
                }
            });
            // 非表示設定
            grid.columns.forEach(element => {
                if (this.gridSetting.invisible_col.indexOf(element.index) >= 0) {
                    element.visible = false;
                }
            });
            
            return grid;
        },
        
        filter(e) {
            this.wjArrivalGrid.collectionView.filter = arrival => {
                var showList = true;
                // or検索
                if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.part_arrival == this.PART_FLG && this.filterList.done_arrival == this.DONE_FLG)
                {
                    // 未入荷＆一部入荷＆入荷済
                    showList = true;
                }
                else if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.part_arrival == this.PART_FLG && this.filterList.done_arrival != this.DONE_FLG) 
                {
                    if (this.filterList.not_arrival != arrival.arrival_status.val && this.filterList.part_arrival != arrival.arrival_status.val) {
                        // 未入荷＆一部入荷
                        showList = false;
                    }
                } 
                else if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.done_arrival == this.DONE_FLG && this.filterList.part_arrival != this.PART_FLG) 
                {
                    if (this.filterList.not_arrival != arrival.arrival_status.val && this.filterList.done_arrival != arrival.arrival_status.val) {
                        // 未入荷＆入荷済
                        showList = false;
                    }
                } 
                else if (this.filterList.part_arrival == this.PART_FLG && this.filterList.done_arrival == this.DONE_FLG && this.filterList.not_arrival != this.NOT_FLG) 
                {
                    if(this.filterList.part_arrival != arrival.arrival_status.val && this.filterList.done_arrival != arrival.arrival_status.val) {
                    // 一部入荷＆入荷済
                    showList = false;
                    }
                } 
                else if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.not_arrival != arrival.arrival_status.val)
                {
                    // 未入荷
                    showList = false;
                } 
                else if (this.filterList.part_arrival == this.PART_FLG && this.filterList.part_arrival != arrival.arrival_status.val) 
                {
                    // 一部入荷
                    showList = false;
                } 
                else if (this.filterList.done_arrival == this.DONE_FLG && this.filterList.done_arrival != arrival.arrival_status.val) 
                {
                    // 入荷済
                    showList = false;
                }

                return showList;
            }
        },
        // エクセル出力(TODO)
        exportExcel: function() {
            if(this.lastSearchParam === null){
                return;
            }
            
            axios.post('/arrival-list/exportExcel', this.lastSearchParam, {responseType: 'blob' })
            .then( function (response) {
                // ContentDispositionからファイル名取得
                const contentDisposition = response.headers['content-disposition'];
                const regex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = regex.exec(contentDisposition);
                var filename = '';
                if (matches != null && matches[1]) {
                    const name = matches[1].replace(/['"]/g, '');
                    filename = decodeURI(name)
                } else {
                    filename = null;
                }

                const url = URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', filename); 
                document.body.appendChild(link);
                link.click();
                URL.revokeObjectURL(link);
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
                }
                this.loading = false
            }.bind(this))
        },
        onClickCancelArrival(data){
            if (!confirm(MSG_CONFIRM_ARRIVAL_CANCEL)) {
                return;
            }
            this.loading = true;

            var params = new URLSearchParams();

            params.append('order_id', this.rmUndefinedZero(data.order_id));
            params.append('order_detail_id', this.rmUndefinedZero(data.id));
            params.append('order_no', this.rmUndefinedZero(data.order_no));
            params.append('arrival_id', this.rmUndefinedZero(data.arrival_id));
            params.append('product_id', this.rmUndefinedZero(data.product_id));
            params.append('product_code', this.rmUndefinedZero(data.product_code));
            params.append('warehouse_id', this.rmUndefinedZero(data.warehouse_id));
            params.append('matter_id', this.rmUndefinedZero(data.matter_id));
            params.append('customer_id', this.rmUndefinedZero(data.customer_id));
            params.append('qr_code', this.rmUndefinedBlank(data.qr_code));
            params.append('arrival_quantity', this.rmUndefinedZero(data.arrival_quantity));
            params.append('own_stock_flg', this.rmUndefinedZero(data.own_stock_flg));
            
            axios.post('/arrival-list/cancel', params)
            .then( function (response) {
                if (response.data) {
                    this.loading = false;
                    if (response.data.status) {
                        window.onbeforeunload = null;
                        var listUrl = '/arrival-list' + this.urlparam;
                        location.href = listUrl;
                    } else {
                        if(this.rmUndefinedBlank(response.data.message) !== ''){
                            alert(response.data.message);
                        }else{
                            alert(MSG_ERROR);
                        }
                        
                        var listUrl = '/arrival-list' + this.urlparam;
                        location.href = listUrl;
                        // location.reload();
                    }
                } else {
                    alert(MSG_ERROR);
                }
                this.loading = false;
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
                }
                this.loading = false
            }.bind(this))
        },
        /* 以下オートコンプリード設定 */
        initCustomer(sender) {
            this.wjSearchObj.customer_name = sender
        },
        initMatterNo(sender) {
            this.wjSearchObj.matter_no = sender
        },
        initMatterName(sender) {
            this.wjSearchObj.matter_name = sender
        },
        initWarehouse(sender) {
            this.wjSearchObj.warehouse_name = sender
        },
        initDepartment(sender) {
            this.wjSearchObj.department_name = sender
        },
        initStaff(sender) {
            this.wjSearchObj.staff_name = sender
        },
        initOrder(sender) {
            this.wjSearchObj.order_no = sender
        },
        initSupplier(sender) {
            this.wjSearchObj.supplier_name = sender
        },
        initMaker(sender) {
            this.wjSearchObj.maker_name = sender
        },
        initQr(sender) {
            this.wjSearchObj.qr_code = sender
        },
        // initProductCode(sender) {
        //     this.wjSearchObj.product_code = sender
        // },
        // initProductName(sender) {
        //     this.wjSearchObj.product_name = sender
        // },
        initFromOrderDate(sender) {
            this.wjSearchObj.from_order_date = sender
        },
        initToOrderDate(sender) {
            this.wjSearchObj.to_order_date = sender
        },
        initFromArrivalDate(sender) {
            this.wjSearchObj.from_arrival_date = sender
        },
        initToArrivalDate(sender) {
            this.wjSearchObj.to_arrival_date = sender
        },
        initFromShippingDate(sender) {
            this.wjSearchObj.from_shipping_date = sender
        },
        initToShippingDate(sender) {
            this.wjSearchObj.to_shipping_date = sender
        },
    }
};

</script>

<style>
.search-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.input-group {
    width: 100%;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-print {
    background: #6200EE;
    color: #fff;
}
/* 入荷状況 */
.not-arrival{
    background: #CB2E25;
    color: #fff;
}
.part-arrival{
    background: #FFCC00;
    color: #fff;
}
.done-arrival{
    background: #5AC8FA;
    color: #fff;
}
.status {
    display: block !important;
    width: 100%;
    height: 22px;
    text-align: center;
    padding: 0px 0px !important;
}

.container-fluid .wj-multirow {
    height: 380px;
    margin: 6px 0;
}
.container-fluid  .wj-detail{
    padding-left: 225px !important;
}

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
    text-align: center !important;
}
.status-btn-group{
    width: 80px;
    height: 58.75px;
}
.btn-cancel{
    padding:3px 11.8px;
    border-radius: 0px;
    width: 80px;
    height: 58.75px;
}
.btn-disable {
    background: #ccc;
}

/* custom styling for a MultiRow */
.container-fluid .multirow-css .wj-row .wj-cell.wj-record-end:not(.wj-header) {
  border-bottom: 1px solid #000; /* thin black lines between records */
}
/* .container-fluid .multirow-css .wj-row .wj-cell.wj-group-end {
  border-right: 2px solid #00b68c; 
} */
.container-fluid .multirow-css .wj-cell.id {
  color: #c0c0c0;
}
.container-fluid .multirow-css .wj-cell.amount {
  color: #014701;
  font-weight: bold;
}
.container-fluid .multirow-css .wj-cell.email {
  color: #0010c0;
  text-decoration: underline;
}

</style>
