<template>
    <div>
        <loading-component :loading="loading" />
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="col-md-12 col-sm-12">
                    <p>検索条件</p>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">商品番号</label>
                        <input type="text" class="form-control" v-model="searchParams.product_code">
                        <!-- <wj-auto-complete class="form-control" id="acProductCode" 
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
                        <!-- <wj-auto-complete class="form-control" id="acProductName" 
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
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">型式／規格</label>
                        <input type="text" class="form-control" v-model="searchParams.model">
                        <!-- <wj-auto-complete class="form-control" id="acModel" 
                            search-member-path="model"
                            display-member-path="model"
                            selected-value-path="model"
                            :selected-index="-1"
                            :selected-value="searchParams.model"
                            :is-required="false"
                            :initialized="initModel"
                            :max-items="productlist.length"
                            :items-source="productlist">
                        </wj-auto-complete> -->
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">メーカー名</label>
                        <wj-auto-complete class="form-control" id="acMaker" 
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
                        <label class="control-label">倉庫名</label>
                        <wj-auto-complete class="form-control" id="acWarehouse" 
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="warehouse_name"
                            :selected-index="-1"
                            :selected-value="searchParams.warehouse_name"
                            :selectedIndexChanged="changeIdxWarehouse"
                            :is-required="false"
                            :initialized="initWarehouse"
                            :max-items="warehouselist.length"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">棚名</label>
                        <wj-auto-complete class="form-control" id="acWarehouse" 
                            search-member-path="shelf_area"
                            display-member-path="shelf_area"
                            selected-value-path="shelf_area"
                            :selected-index="-1"
                            :selected-value="searchParams.shelf_area"
                            :is-required="false"
                            :initialized="initShelf"
                            :max-items="shelflist.length"
                            :items-source="shelflist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2 col-sm-2">
                        <label class="control-label">案件番号</label>
                        <wj-auto-complete class="form-control" id="acWarehouse" 
                            search-member-path="matter_no"
                            display-member-path="matter_no"
                            selected-value-path="id"
                            :selectedIndexChanged="selectMatterNo"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_no"
                            :is-required="false"
                            :initialized="initMatterNo"
                            :max-items="matterlist.length"
                            :items-source="matterlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <label class="control-label">案件名</label>
                        <wj-auto-complete class="form-control" id="acWarehouse" 
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="id"
                            :selectedIndexChanged="selectMatterName"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_name"
                            :is-required="false"
                            :initialized="initMatterName"
                            :max-items="matterlist.length"
                            :items-source="matterlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">得意先名</label>
                        <wj-auto-complete class="form-control" id="acWarehouse" 
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

                    <div class="col-md-12 col-sm-12 text-right">
                        <button type="button" class="btn btn-clear" @click="clearParams">クリア</button>
                        <button type="submit" class="btn btn-search">検索</button>
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
                    <div class="col-md-4 col-sm-4">
                        <div class="input-group">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-search"></span>
                            </div>
                            <input @input="textFilter($event)" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <wj-multi-row
                    :itemsSource="stock"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                    >
                    <wj-flex-grid-detail :maxHeight="250" :initialized="initFlexGridDetail" :rowHasDetail="rwhas">
                    <template slot-scope="row">
                        <wj-multi-row 
                            :itemsSource="getDetails(row.item)"
                            :layoutDefinition="layoutDetailDefinition"
                            :is-read-only="true"
                            :initialized="initDetailRow"
                            :itemFormatter="itemDetailFormatter"
                        ></wj-multi-row>
                    </template>
                    </wj-flex-grid-detail>
                </wj-multi-row>
                <wj-collection-view-navigator
                    headerFormat="{currentPage: n0} / {pageCount: n0}"
                    :byPage="true"
                    :cv="stock" 
                />
            </div>
        </div>
        <!-- 画像表示用ダイアログ -->
        <el-dialog title="商品写真" :visible.sync="showDlgImage" :closeOnClickModal=false>
            <img v-if="rmUndefinedBlank(imageList[focusImage]) != ''" :src="imageList[focusImage]" height="300" class="center-block" />
            <p v-if="rmUndefinedBlank(imageList[focusImage]) == ''">{{ ERROR_MSG }}</p>
            <span slot="footer" class="dialog-footer">
                <el-button @click="showDlgImage = false; focusImage = null" class="btn-cancel">閉じる</el-button>
            </span>
        </el-dialog>
        <!-- 引当ダイアログ -->
        <el-dialog title="引当状況" :visible.sync="showDlgReserve" :closeOnClickModal=false>
            <wj-multi-row
                    :itemsSource="reserve"
                    :layoutDefinition="layoutReserve"
                    :initialized="initReserveGrid"
                    :itemFormatter="reserveItemFormatter"
                    >
            </wj-multi-row>
            <span slot="footer" class="dialog-footer">
                <el-button @click="showDlgReserve = false; wjReserveGrid = null;" class="btn-cancel">閉じる</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import * as wjDetail from '@grapecity/wijmo.grid.detail';

export default {
    data: () => ({
        loading: false,
        tableData: 0,
        urlparam: '',
        queryparam: '',
        FLG_ON: 1,
        FLG_OFF: 0,
        ERROR_MSG: MSG_ERROR_NOT_IMAGE,

        stock: new wjCore.CollectionView(),
        shelf: new wjCore.CollectionView(),
        reserve: null,

        qrList: [],
        mainSrc: [],
        reserveList: [],

        layoutDefinition: null,
        layoutDetailDefinition: null,
        layoutReserve: null,
        keepDOM: {},
        keepDetailDOM: {},
        // グリッド設定用
        gridSetting: {
            // リサイジング不可[ 写真データ ]
            deny_resizing_col: [8],
            // 非表示[ ID ]
            invisible_col: [9, 10],
        },
        // 子グリッド設定用
        detailGridSetting: {
            // リサイジング不可[ 印刷 ]
            deny_resizing_col: [4],
            // 非表示[ ID ]
            invisible_col: [5],
        },
        gridPKCol: 9,
        detailGridPKCol: 5,

        showDlgImage: false,
        imageList: [],
        focusImage: null,
        showDlgReserve: false,

        searchParams: {
            product_code: '',
            product_name: '',
            model: '',
            supplier_name: '',
            warehouse_name: '',
            shelf_area: '',
            customer_name: '',
            matter_no: '',
            matter_name: '',
        },

        wjSearchObj: {
            product_code: {},
            product_name: {},
            model: {},
            supplier_name: {},
            warehouse_name: {},
            shelf_area: {},
            customer_name: {},
            matter_no: {},
            matter_name: {},
        },
        wjStockGrid: null,
        wjShelfGrid: null,
        wjReserveGrid: null,
    }),
    props: {
        // productlist: Array,
        warehouselist: Array,
        supplierlist: Array,
        shelflist: Array,
        matterlist: Array,
        customerlist: Array,
    },
    created: function() {
        this.queryparam = window.location.search;
        if (this.queryparam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryparam, this.searchParams);
        } else {
            // 初回の検索条件をセット
            // this.setInitSearchParams(this.searchParams, this.initSearchParams);
        }
        this.layoutDefinition = this.getLayout();
        this.layoutDetailDefinition = this.detailLayout();
        this.layoutReserve = this.reserveLayout();
    },
    mounted: function() {
        if (this.queryparam.length > 1) {
            this.search();
        }
    },
    methods: {
        selectMatterNo: function(sender) {
            var value = this.rmUndefinedBlank(sender.selectedItem.id);
            this.searchParams.matter_name = value;
        },
        selectMatterName: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.matter_no = item.id;
            }else{
                this.searchParams.matter_no = '';
            }
        },
        changeIdxWarehouse: function(sender){
            // 倉庫を変更したら棚を絞り込む
            var tmpShelf = this.shelflist;
            if (sender.selectedValue) {
                var item = sender.selectedItem;
                tmpShelf = [];
                for(var key in this.shelflist) {
                    if (item.id == this.shelflist[key].warehouse_id) {
                        tmpShelf.push(this.shelflist[key]);
                    }
                }
            }
            this.wjSearchObj.shelf_area.itemsSource = tmpShelf;
            this.wjSearchObj.shelf_area.selectedIndex = -1;
        },
        // テキストボックスフィルタ
        textFilter: function(e) {
            var filter = e.target.value.toLowerCase();
            this.wjStockGrid.collectionView.filter = stock => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (stock.warehouse_name != null && (stock.warehouse_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.product_code != null && (stock.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.product_name != null && (stock.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.supplier_name != null && (stock.supplier_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.model != null && (stock.model).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.active_quantity != null && (stock.active_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.actual_quantity != null && (stock.actual_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.reserve_quantity != null && (stock.reserve_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.arrival_quantity != null && (stock.arrival_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.next_arrival_date != null && (stock.next_arrival_date).toString().toLowerCase().indexOf(filter) > -1) ||
                    (stock.last_arrival_date != null && (stock.last_arrival_date).toString().toLowerCase().indexOf(filter) > -1)
                );
            };
        },
        // 詳細行データ取得
        getDetails(item) {
            var arr = [];
            var productId = item.product_id;
            var warehouseId = item.warehouse_id;

            this.qrList.forEach(function (qr) {
                if(qr.product_id == productId && qr.warehouse_id == warehouseId) {
                    arr.push(qr)
                }
            });
            var itemDetailSource = [];
            arr.forEach(element => {
                // DOM生成
                // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                this.keepDetailDOM[element.detail_id] = {
                    print_btn: document.createElement('a'),
                }

                var _this = this;
                // 印刷ボタン  
                if (this.rmUndefinedBlank(element.detail_id) == '') {
                    this.keepDetailDOM[element.detail_id].print_btn.innerHTML = '<button type="button" class="btn btn-disabled print-btn" disabled>印刷</button>';
                } else {
                    this.keepDetailDOM[element.detail_id].print_btn.innerHTML = '<button type="button" data-id=' + JSON.stringify(element.detail_id) + ' class="btn btn-edit print-btn">印刷</button>';
                    this.keepDetailDOM[element.detail_id].print_btn.addEventListener('click', function(e) {
                        if (e.target.dataset.id) {
                            var data = JSON.parse(e.target.dataset.id);
                            _this.qrPrint(data);
                        }
                    })
                }
                var showQuantity = 0;
                if (this.rmUndefinedZero(element.qr_quantity) > 0){
                    showQuantity = element.qr_quantity;
                } else {
                    showQuantity = element.quantity;
                }

                itemDetailSource.push({
                    id: element.detail_id,
                    shelf_area: element.shelf_area,
                    shelf_steps: element.shelf_steps + '段目',
                    quantity: showQuantity,
                    matter_name: element.matter_name,
                    customer_name: element.customer_name,
                    qr_code: element.qr_code,
                })
            });

            return itemDetailSource;
        },
        // 引当セルクリック時、引当情報を返す
        getReserveList: function(data) {
            var arr = [];
            this.reserve = null;
            // this.wjReserveGrid = null;

            var warehouseId = data.warehouse_id;
            var productId = data.product_id;

            // 倉庫・商品が一致したらpush
            this.reserveList.forEach(function (val) {
                if(val.product_id == productId && val.from_warehouse_id == warehouseId) {
                    arr.push(val)
                }
            });

            // グリッド用配列作成
            var reserveItemsSource = [];
            arr.forEach(element => {
                reserveItemsSource.push({
                    id: element.reserve_id,
                    matter_name: element.matter_name,
                    address: element.address,
                    reserve_date: element.reserve_date,
                    delivery_date: element.delivery_date,
                    reserve_quantity: element.reserve_quantity,
                })
            });
            // グリッドに配列セット
            var reserveGrid = new wjCore.CollectionView(reserveItemsSource);
            this.reserve = reserveGrid;

            if (reserveItemsSource.length == this.reserve.items.length) {
                this.showDlgReserve = true;
            }
        },
        // 奇数行の展開ボタンを削除
        rwhas: function(r) {
            return r.recordIndex % 2 == 1;
        },
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;

            multirow.addEventListener(multirow.hostElement, "click", e => {
                let ht = multirow.hitTest(e);
                if(ht.cellType === wjGrid.CellType.RowHeader){
                    if (this.wjFlexDetailSetting.isDetailVisible(ht.row)) {
                        this.wjFlexDetailSetting.hideDetail(ht.row);
                    }else{
                        this.wjFlexDetailSetting.showDetail(ht.row);
                    }
                }
            })

            // 引当データが存在した場合、イベント設定
            multirow.hostElement.addEventListener('click', function(e) {
                // クリックイベント
                let ht = multirow.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = multirow.getBindingColumn(ht.panel, ht.row, ht.col);
                        switch (col.name) {
                            case 'reserve_quantity':
                                if (this.rmUndefinedZero(record.reserve_quantity) != 0) {
                                    this.getReserveList(record);
                                }
                                break;
                        }
                    }       
                }
            }.bind(this));

            this.wjStockGrid = multirow;
        },
        initReserveGrid: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;

            multirow.headersVisibility = wjGrid.HeadersVisibility.Column

            this.wjReserveGrid = multirow;
        },
        initDetailRow: function(detailrow) {
            // 行高さ
            detailrow.rows.defaultSize = 30;
            // 行高さの自動調整
            // 行ヘッダ非表示
            detailrow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // セル押下してもカーソルがあたらないように変更
            // detailrow.selectionMode = wjGrid.SelectionMode.None;
           
            detailrow = this.applyDetailSettings(detailrow);

            this.wjDetailGrid = detailrow;
        },
        initFlexGridDetail: function(detailSetting){
            /** 詳細行設定 **************************
            * ExpandSingle => 1レコードのみ展開可能　
            * ExpandMulti  => 複数の詳細行を展開可能 ★ 
            ****************************************/
            detailSetting.detailVisibilityMode = wjDetail.DetailVisibilityMode.ExpandMulti;

            this.wjFlexDetailSetting = detailSetting;
        },
        // 写真データ表示
        viewImage(id) {
            this.focusImage = id - 1;

            this.showDlgImage = true;
        },
        qrPrint(id) {
            var data = null;
            for (var i = 0; i < this.qrList.length; i++) {
                if (this.qrList[i].detail_id == id) {
                    data = this.qrList[i];
                }
            }
            var params = new URLSearchParams();

            params.append('id', this.rmUndefinedBlank(data.detail_id));
            params.append('qr_id', this.rmUndefinedBlank(data.qr_id));
            params.append('qr_code', this.rmUndefinedBlank(data.qr_code));

            axios.post('/stock-search/print', params)

            .then( function (response) {
                if (response.data) {
                    var url = response.data;
                    var pattern = 'smapri:';
                    if (url.indexOf(pattern) > -1) {
                        // iosの場合
                        window.location.href = url
                    }
                }
                // this.loading = false;
            }.bind(this))
            .catch(function (error) {
                // this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                location.reload()
            }.bind(this))
        },
        // 検索
        search() {
            this.loading = true;

            this.imageList = [];

            var params = new URLSearchParams();

            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('model', this.rmUndefinedBlank(this.searchParams.model));
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('warehouse_name', this.rmUndefinedBlank(this.wjSearchObj.warehouse_name.text));
            params.append('shelf_area', this.rmUndefinedBlank(this.wjSearchObj.shelf_area.text));
            params.append('matter_id', this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
            // params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
            params.append('customer_id', this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));

            axios.post('/stock-search/search', params)
            
            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'model=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.model));
                    this.urlparam += '&' + 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                    this.urlparam += '&' + 'warehouse_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.warehouse_name.text));
                    this.urlparam += '&' + 'shelf_area=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.shelf_area.text));
                    this.urlparam += '&' + 'matter_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    // this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                    this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));

                    var itemsSource = [];

                    this.mainSrc = response.data.stock;
                    this.qrList = response.data.qr;
                    this.reserveList = response.data.reserve;

                    var dataLength = 0;
                    response.data.stock.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            warehouse_name: document.createElement('span'),
                            active: document.createElement('span'),
                            actual: document.createElement('span'),
                            reserve: document.createElement('span'),
                            arrival: document.createElement('span'),
                            photo_btn: document.createElement('div'),
                        }

                        // レコード間でのセル結合を防ぐため、一旦値を避難
                        this.keepDOM[element.id].warehouse_name.innerHTML = element.warehouse_name;
                        this.keepDOM[element.id].warehouse_name.classList.add('string-cell');

                        // 有効在庫数
                        var active = this.comma_format(element.active_quantity)
                        this.keepDOM[element.id].active.innerHTML = active;
                        this.keepDOM[element.id].active.classList.add('numeric-cell');

                        // 実在庫数
                        var actual = this.comma_format(element.actual_quantity)
                        this.keepDOM[element.id].actual.innerHTML = actual;
                        this.keepDOM[element.id].actual.classList.add('numeric-cell');

                        // 引当数
                        if (this.rmUndefinedZero(element.reserve_quantity) != 0) {
                            this.keepDOM[element.id].reserve.classList.add('reserve-link');
                        }
                        var reserve = this.comma_format(element.reserve_quantity)
                        this.keepDOM[element.id].reserve.innerHTML = reserve;
                        this.keepDOM[element.id].reserve.classList.add('reserve-cell');

                        // 入荷予定数
                        var arrival = this.comma_format(element.arrival_quantity)
                        this.keepDOM[element.id].arrival.innerHTML = arrival;
                        this.keepDOM[element.id].arrival.classList.add('numeric-cell');

                        // 写真ボタン
                        var _this = this;
                        this.keepDOM[element.id].photo_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn image-btn">表示</button>';
                        this.keepDOM[element.id].photo_btn.addEventListener('click', function(e) {
                            if (e.target.dataset.id) {
                                var data = JSON.parse(e.target.dataset.id);
                                _this.viewImage(data);
                            }
                        })
                        this.imageList.push(element.image);

                        itemsSource.push({
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            warehouse_id: element.warehouse_id,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            warehouse_name: element.warehouse_name,
                            supplier_name: element.supplier_name,
                            model: element.model,
                            active_quantity: active,
                            actual_quantity: actual,
                            reserve_quantity: reserve,
                            arrival_quantity: arrival,
                            next_arrival_date: element.next_arrival_date,
                            last_arrival_date: element.last_arrival_date,
                        })

                        dataLength++;
                    })
                    this.tableData = dataLength;

                    // データセット
                    // グリッドのページング設定
                    var view = new wjCore.CollectionView(itemsSource, {
                        pageSize: 5,
                    })
                    this.stock = view;
                    this.wjStockGrid.itemsSource = this.stock;
                    

                    // 設定更新
                    this.wjStockGrid = this.applyGridSettings(this.wjStockGrid);
                    
                    // 描画更新
                    this.wjStockGrid.refresh();
                    
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
        
        // 検索条件のクリア
        clearParams: function() {
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
                // var item = this.wjStockGrid.rows[r];
                var dataItem = panel.rows[r].dataItem;
                if (this.rmUndefinedBlank(dataItem) != ''){
                    switch (c) {
                        case 0: // 倉庫名
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].warehouse_name);
                            cell.style.textAlign = 'left';
                            break;
                        case 1: // 商品
                            cell.style.textAlign = 'left';
                            break;
                        case 2: // メーカー名／型式
                            cell.style.textAlign = 'left';

                            break;
                        case 3: // 有効在庫
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].active);
                            cell.style.textAlign = 'right';
                            break;
                        case 4: // 実在庫
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].actual);
                            cell.style.textAlign = 'right';
                            break;
                        case 5: // 引当
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].reserve);
                            cell.style.textAlign = 'right';
                            break;
                        case 6: //  入荷予定数
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].arrival);
                            cell.style.textAlign = 'right';
                            break;
                        case 7: //  入荷日
                            cell.style.textAlign = 'right';
                            break;
                        case 8: // 写真
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].photo_btn);
                            break;
                    }
                }
            }
        },
        itemDetailFormatter: function (panel, r, c, cell) {
            if (this.keepDOM === undefined || this.keepDOM === null) {
                return;
            }

            // 列ヘッダのセンタリング
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                cell.style.textAlign = 'center';
            }

            if (panel.cellType == wjGrid.CellType.Cell) {
                var dataItem = panel.rows[r].dataItem;
                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (c) {
                    case 0: // 棚
                        break;
                    case 1: // 数量
                        break;
                    case 2: // 案件名
                        break;
                    case 3: // QR
                        break;
                    case 4: // 印刷
                        // 印刷ボタン  
                        if(dataItem !== undefined){
                            var rId1 = 'printBtn' + dataItem.id;
                   
                            var div = document.createElement('div');
                            // div.classList.add('btn-group', 'status-btn-group');
                            // div.setAttribute("data-toggle","buttons");

                            var btnPrint = document.createElement('button');
                            btnPrint.type = 'button';
                            btnPrint.id = rId1;

                            var isDisable = false;
                            if(this.rmUndefinedBlank(dataItem.id) == ''){
                                isDisable = true;
                                btnPrint.classList.add('btn-disabled');
                            } else {
                                btnPrint.classList.add('btn-edit');   
                            }
                            btnPrint.classList.add('btn', 'print-btn');
                            btnPrint.innerHTML = '印刷'; 
                            btnPrint.disabled = isDisable;

                            btnPrint.addEventListener('click', function (e) {
                                this.qrPrint(dataItem.id);
                            }.bind(this));

                            div.appendChild(btnPrint);
                            cell.appendChild(div);
                        }
                        // cell.appendChild(this.keepDetailDOM[panel.getCellData(r, this.detailGridPKCol)].print_btn);
                        cell.style.textAlign = 'center';
                        break;
                }
            }
        },
        reserveItemFormatter: function (panel, r, c, cell) {
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
                switch (c) {
                    case 0: // 案件／住所
                        cell.style.textAlign = 'left';
                        break;
                    case 1: // 予定日／引当数
                        cell.style.textAlign = 'right';
                        break;
                }
            }
        },
        // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
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


            // 列ヘッダー結合
            grid.allowMerging = wjGrid.AllowMerging.All;
            for (var i = 0; i < grid.columns.length; i++) {
                if (i == 0 || i == 3 || i == 4 || i == 5 || i == 6 || i == 8){
                    grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(1, i));
                    grid.columnHeaders.columns[i].allowMerging = true;
                }
            }  

            return grid;
        },
        // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
        applyDetailSettings(grid) {
            // リサイジング設定
            grid.columns.forEach(element => {
                if (this.detailGridSetting.deny_resizing_col.indexOf(element.index) >= 0) {
                    element.allowResizing = false;
                }
            });
            // 非表示設定
            grid.columns.forEach(element => {
                if (this.detailGridSetting.invisible_col.indexOf(element.index) >= 0) {
                    element.visible = false;
                } 
            });

            return grid;
        },
        // グリッドレイアウト
        getLayout() {
            return [
                {
                    header: '倉庫', cells: [
                        // { binding: 'warehouse_name', header: '倉庫名', isReadOnly: true, width: 190, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', header: '倉庫名', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '商品', cells: [
                        { binding: 'product_code', header: '商品番号', isReadOnly: true, width: '*', minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'product_name', header: '商品名', isReadOnly: true, width: '*', minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: 'メーカー／型式', cells: [
                        { binding: 'supplier_name', header: 'メーカー名', isReadOnly: true, width: 220, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'model', header: '型式／規格', isReadOnly: true, width: 220, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '有効在庫数', cells: [
                        // { binding: 'active_quantity', header: '有効在庫数', isReadOnly: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', header: '有効在庫数', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '実在庫数', cells: [
                        // { binding: 'actual_quantity', header: '実在庫数', isReadOnly: true, wordWrap: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', header: '実在庫数', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH  },
                    ]
                },
                {
                    header: '引当数', cells: [
                        // { binding: 'reserve_quantity', header: '引当数', wordWrap: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', name: 'reserve_quantity', header: '引当数', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH  },
                    ]
                },
                {
                    header: '入荷予定数', cells: [
                        // { binding: 'arrival_quantity', header: '入荷予定数', isReadOnly: true, width: 90, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', header: '入荷予定数', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '入荷日', cells: [
                        { binding: 'next_arrival_date', header: '次回入荷日', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'last_arrival_date', header: '最終入荷日', isReadOnly: true, width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '写真データ', cells: [
                        { binding: 'id', header: '写真', isReadOnly: true, width: 70 },
                    ]
                },
                /* 以降、非表示カラム */
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0, maxWidth: 0},
                    ]
                },
                {
                    header: 'ID', cells: [
                        { binding: 'warehouse_id', header: 'warehouse_id', width: 0, maxWidth: 0},
                    ]
                },
            ]
        },
        // グリッドレイアウト
        detailLayout() {
            return [
                {
                    header: '棚', cells: [
                        { binding: 'shelf_area', header: '棚番名', isReadOnly: true, width: 230, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'shelf_steps', header: '棚番', isReadOnly: true, width: 230, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '数量', cells: [
                        { binding: 'quantity', header: '数量', isReadOnly: true, width: 110, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '案件名', cells: [
                        { binding: 'customer_name', header: '得意先名', isReadOnly: true, width: 270, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'matter_name', header: '案件名', isReadOnly: true, width: 270, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: 'QR', cells: [
                        { binding: 'qr_code', header: 'QR管理番号', isReadOnly: true, width: 230, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '印刷', cells: [
                        { header: '印刷', isReadOnly: true, width: 70, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                /* 以降、非表示カラム */
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0, maxWidth: 0},
                    ]
                },
            ]
        },
        // グリッドレイアウト
        // 引当ダイアログ
        reserveLayout() {
            return [
                {
                    header: '案件', cells: [
                        { binding: 'matter_name', header: '案件名', isReadOnly: true, width: 430, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'address', header: '住所', isReadOnly: true, width: 430, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '引当情報', cells: [
                        { binding: 'delivery_date', header: '出荷予定日', isReadOnly: true, width: 200, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'reserve_quantity', header: '引当数', isReadOnly: true, width: 200, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                /* 以降、非表示カラム */
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0, maxWidth: 0},
                    ]
                },
            ]
        },
        // 3桁ずつカンマ区切り
        comma_format: function (val) {
            if (val == undefined || val == '') {
                return 0;
            }
            if (typeof val !== 'number') {
                val = parseInt(val);
            }
            return val.toLocaleString();
        },
        dateFormat: function (date) {
            return moment(date).format('YYYY/MM/DD')
        },
        // 以下オートコンプリートの値取得
        // initProductCode: function(sender) {
        //     this.wjSearchObj.product_code = sender
        // },
        // initProductName: function(sender) {
        //     this.wjSearchObj.product_name = sender
        // },
        // initModel: function(sender) {
        //     this.wjSearchObj.model = sender
        // },
        initSupplier: function(sender) {
            this.wjSearchObj.supplier_name = sender
        },
        initWarehouse: function(sender) {
            this.wjSearchObj.warehouse_name = sender
        },
        initShelf: function(sender) {
            this.wjSearchObj.shelf_area = sender
        },
        initMatterNo: function(sender) {
            this.wjSearchObj.matter_no = sender
        },
        initMatterName: function(sender) {
            this.wjSearchObj.matter_name = sender
        },
        initCustomer: function(sender) {
            this.wjSearchObj.customer_name = sender
        },
    }
}
</script>

<style>
/*********************************
    枠サイズ等
**********************************/
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
.wj-multirow {
    height: 500px;
    margin: 6px 0;
}
.btn-search {
    height: 35px;
    width: 100px;
}
.btn-disabled {
    background-color: #cccccc !important;
}
.image-btn {
    background-color: #6200EE;
    color: #fff;
    height: 100%;
    width: 100%;
}
.print-btn {
    background-color: #6200EE;
    color: #fff;
    height: 80%;
    width: 100%;
}
.numeric-cell {
    text-align: right;
}
.reserve-link {
    color: #1111cc;
}
.reserve-link:hover {
    text-decoration: underline;
}

/*********************************
    以下wijmo系
**********************************/
.container-fluid .wj-multirow {
    height: 400px;
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
.wj-glyph-plus {
    margin-top: 10px;
}
.wj-glyph-minus {
    margin-top: 10px;
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

