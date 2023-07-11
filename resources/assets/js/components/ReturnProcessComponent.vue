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
                            selected-value-path="customer_name"
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
                        <label class="control-label">商品名</label>
                        <input type="text" class="form-control" v-model="searchParams.product_name">
                        <!-- <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="product_name"
                            display-member-path="product_name"
                            selected-value-path="product_name"
                            :selected-index="-1"
                            :selected-value="searchParams.product_name"
                            :is-required="false"
                            :initialized="initProName"
                            :max-items="productlist.length"
                            :items-source="productlist">
                        </wj-auto-complete> -->
                    </div>
                    <div class="col-md-2 col-sm-2">
                        <label class="control-label">仕入先／メーカー名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="supplier_name"
                            :selected-index="-1"
                            :selected-value="searchParams.supplier_name"
                            :is-required="false"
                            :initialized="initSuppName"
                            :max-items="supplierlist.length"
                            :items-source="supplierlist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
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
                            :initialized="initProCode"
                            :max-items="productlist.length"
                            :items-source="productlist">
                        </wj-auto-complete> -->
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">商品管理ID</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="qr_code"
                            display-member-path="qr_code"
                            selected-value-path="qr_code"
                            :selected-index="-1"
                            :selected-value="searchParams.qr_code"
                            :is-required="false"
                            :initialized="initQrCode"
                            :max-items="qrcodelist.length"
                            :items-source="qrcodelist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
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
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">担当者名</label>
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
                    <div class="col-sm-2">
                        <label>返品日</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.from_move_date"
                            :selected-value="searchParams.from_move_date"
                            :initialized="initFromMoveDate"
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
                            :value="searchParams.to_move_date"
                            :selected-value="searchParams.to_move_date"
                            :initialized="initToMoveDate"
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
                <div class="col-sm-8">
                    <div class="input-group search-body">
                        <div class="col-sm-6">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-search"></span>
                                </div>
                                <input v-model="filterText" @input="filter($event)" class="form-control">
                            </div>
                        </div>
                        <el-checkbox class="col-md-2 col-xs-12" v-model="filterChk.not_finish" true-label="1" false-label="0" @input="filter($event)">未処置</el-checkbox>
                        <el-checkbox class="col-md-2 col-xs-12" v-model="filterChk.finished" true-label="1" false-label="0" @input="filter($event)">処置済</el-checkbox>
                    </div>
                </div>
                <div class="col-md-4 col-sm-4 col-xs-4">
                    <div class="row">
                        <div class="col-sm-12 col-sm-12 col-xs-12">
                            <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">件数：{{ resultlen }}件</p>                            
                        </div>
                    </div>
                </div>
                <!-- グリッド -->
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div id="wjReturnGrid"></div>
                    <!-- <wj-multi-row
                        :allowAddNew="true"
                        :itemsSource="products"
                        :layoutDefinition="layoutDefinition"
                        :initialized="initMultiRow"                        
                    ></wj-multi-row> -->
                </div>            
            </div>
        </div>
        <!-- 承認ダイアログ -->
        <el-dialog title="承認" :visible.sync="showDlgApproval" :closeOnClickModal=false>
            <div class="col-md-5 col-sm-5">
                <label class="control-label">返却先倉庫</label>
                <wj-auto-complete class="form-control" 
                    search-member-path="warehouse_name"
                    display-member-path="warehouse_name"
                    selected-value-path="id"
                    :selected-index="-1"
                    :is-required="false"
                    :initialized="initDlgWarehouse"
                    :max-items="warehouselist.length"
                    :items-source="warehouselist">
                </wj-auto-complete>
                <!-- :selected-value="searchParams.matter_name" -->
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="approval" class="btn-change">承認</el-button>
                <el-button @click="showDlgApproval = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
        <!-- 返品追加ダイアログ -->
        <el-dialog title="返品処理追加" :visible.sync="showDlgProcess" :closeOnClickModal=false>
            <div class="el-dlg-body">
                <div class="col-md-8 col-sm-8 col-xs-8">
                    <label class="control-label">処置内容</label>
                    <wj-combo-box class="form-control" 
                        search-member-path="value_text_1"
                        display-member-path="value_text_1"
                        selected-value-path="value_code"
                        :selected-index="-1"
                        :is-required="false"
                        :selectedIndexChanged="changeIdxProcess"
                        :initialized="initProcess"
                        :max-items="processlist.length"
                        :items-source="processlist">
                    </wj-combo-box>
                </div>
                    <!-- <label class="control-label">処置内容</label>
                    <wj-combo-box
                        id="comboObject"
                        displayMemberPath="value_text_1"
                        selected-value-path="value_code"
                        :itemsSource="processlist"
                        :initialized="initProcess">
                    </wj-combo-box>
                </div> -->
                <div v-show="(processDlgData.return_kbn == FLG_MAKER_RETURN || processDlgData.return_kbn == FLG_DISCARD)" class="col-md-8 col-sm-8 col-xs-8">
                    <label class="control-label">スマホ処理不要</label>
                    <div><el-checkbox v-model="processDlgData.sp_flg" :true-label="1" :false-label="0" v-bind:disabled="(maker_return_flg && processDlgData.return_kbn == FLG_MAKER_RETURN)"></el-checkbox></div>
                </div>
                <div class="col-md-5 col-sm-5 col-xs-5" v-bind:class="{'has-error': (errors.return_quantity != '') }" >
                    <label class="control-label">数量</label>
                    <input type="number" class="form-control" v-model="processDlgData.return_quantity">
                    <p class="text-danger">{{ errors.return_quantity }}</p>
                </div>
                <div v-bind:class="{'has-error': (errors.move_date != '') }" v-show="(processDlgData.return_kbn == FLG_MAKER_RETURN && processDlgData.sp_flg == FLG_OFF)" class="col-md-8 col-sm-8 col-xs-8">
                    <label>メーカー返品予定日</label>
                    <wj-input-date class="form-control"
                        :value="processDlgData.move_date"
                        :selected-value="processDlgData.move_date"
                        :initialized="initReturnMoveDate"
                        :isRequired=false
                    ></wj-input-date>
                    <p class="text-danger">{{ errors.move_date }}</p>
                </div>

                <div v-bind:class="{'has-error': (errors.shelf_number_id != '') }" v-show="processDlgData.return_kbn == FLG_STOCK" class="col-md-8 col-sm-8 col-xs-8">
                    <label class="control-label">棚</label>
                    <wj-combo-box class="form-control" 
                        search-member-path="shelf_name"
                        display-member-path="shelf_name"
                        selected-value-path="id"
                        :selected-index="1"
                        :is-required="true"
                        :isReadOnly="processDlgData.isShelfReadOnly"
                        :selectedIndexChanged="changeIdxShelf"
                        :initialized="initShelf"
                        :max-items="shelfList.length"
                        :items-source="shelfList">
                    </wj-combo-box>
                    <p class="text-danger">{{ errors.shelf_number_id }}</p>
                </div>

                <div v-bind:class="{'has-error': (errors.keep_shelf_number_id != '') }" v-show="processDlgData.return_kbn == FLG_KEEP" class="col-md-8 col-sm-8 col-xs-8">
                    <label class="control-label">棚</label>
                    <wj-combo-box class="form-control" 
                        search-member-path="shelf_name"
                        display-member-path="shelf_name"
                        selected-value-path="id"
                        :selected-index="1"
                        :is-required="true"
                        :isReadOnly="processDlgData.isKeepShelfReadOnly"
                        :selectedIndexChanged="changeIdxKeepShelf"
                        :initialized="initKeepShelf"
                        :max-items="keepShelfList.length"
                        :items-source="keepShelfList">
                    </wj-combo-box>
                    <p class="text-danger">{{ errors.keep_shelf_number_id }}</p>
                </div>

            </div>

            <span slot="footer" class="dialog-footer">
                <el-button @click="addReturn" class="btn-change">登録</el-button>
                <el-button @click="showDlgProcess = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import * as wjDetail from '@grapecity/wijmo.grid.detail';

export default {
    data: () => ({
        loading: false,

        showDlgApproval: false,
        showDlgProcess: false,

        FLG_ON : 1,
        FLG_OFF: 0,

        FLG_NOT_APPROVAL: 0,
        FLG_APPROVAL: 1,
        FLG_REJECT: 2,

        FLG_KEEP: 1,
        FLG_STOCK: 2,
        FLG_MAKER_RETURN: 3,
        FLG_DISCARD: 4,

        FLG_FINISH_SHIPPED: 1,
        FLG_FINISH_MOVED: 2,

        STOCK_FLG: {
            ORDER: 0,
            STOCK: 1,
            KEEP: 2,
        },

        queryParam: '',
        urlparam: '',

        resultlen: 0,

        focusRowId: 0,
        focusRow: 0,
        maker_return_flg: false,

        dlgData: {
            warehouse_id: {},
        },

        filterText: '',
        filterChk: {
            not_finish: 0,
            finished: 0,
        },

        // 処置追加用データ
        processDlgData: {
            return_kbn: '',
            sp_flg: 0,
            return_quantity: '',
            move_date: null,
            shelf_number_id: '',
            keep_shelf_number_id: '',

            isShelfReadOnly: false,
            isKeepShelfReadOnly: false,
        },

        wjDlgParams: {
            return_kbn: {},
            shelf_number_id: {},
            keep_shelf_number_id: {},
        },

        // データ
        searchParams: {
            customer_name: '',
            matter_no: '',
            matter_name: '',
            product_code: '',
            product_name: '',
            supplier_name: '',
            qr_code: '',
            from_move_date: null,
            to_move_date: null,
            department_name: '',
            staff_name: '',
        },

        returnList: [],
        moveList: [],
        shelfList: [],
        keepShelfList: [],

        wjSearchObj: {
            customer_name: {},
            matter_no: {},
            matter_name: {},
            product_code: {},
            product_name: {},
            supplier_name: {},
            qr_code: {},
            from_move_date: {},
            to_move_date: {},
            department_name: {},
            staff_name: {},
        },
        errors: {
            return_quantity: '',
            move_date: '',
            shelf_number_id: '',
            keep_shelf_number_id: '',
        },
        
        keepDOM: {},
        keepDetailDOM: {},
        returns: new wjCore.CollectionView(),
        
        layoutDefinition: null,
        gridChildLayout: null,

        gridSetting: {
            deny_resizing_col: [5],

            invisible_col: [],
        },
        gridPKCol: 8,
        gridPKDetailCol: 5,

        // 以下,initializedで紐づける変数
        wjReturnGrid: null,
        wjDetailGrid: null,
    }),
    props: {
        isEditable: Number,
        isAuthProc: Number,
        // productlist: Array,
        customerlist: Array,
        matterlist: Array,
        qrcodelist: Array,
        supplierlist: Array,
        departmentlist: Array,
        stafflist: Array,
        staffdepartlist: Array,
        warehouselist: Array,
        processlist: Array,
    },
    created: function() {
        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.from_move_date == "") { this.searchParams.from_move_date = null };
            if (this.searchParams.to_move_date == "") { this.searchParams.to_move_date = null };
        }
        // グリッドレイアウトセット
        this.layoutDefinition = this.getLayout();
        this.gridChildLayout = this.getDetailLayout();
    },
    mounted: function() {
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            // 検索
            this.search();
        }

        // グリッド初期表示
        var targetDiv = '#wjReturnGrid';

        this.$nextTick(function() {
            var _this = this;
            // var gridItemSource = this.returns;
            // gridItemSource.refresh();
            this.wjReturnGrid = this.createGrid(targetDiv, this.returns);
            this.applyGridSettings(this.wjReturnGrid);
        });

    },
    methods: {
        // 展開済の詳細行を開きなおす
        RefreshDetail: function(row) {
            if (this.wjDetailGrid.isDetailVisible(1)) {
                for(var i = 0; i < this.wjReturnGrid.rows.length; i++) { 
                    this.wjDetailGrid.hideDetail(i);
                }
                for(var i = 0; i < this.wjReturnGrid.rows.length; i++) { 
                    this.wjDetailGrid.showDetail(i);
                }
            }
        },
        filter: function(e) {
            var filter = this.filterText.toLowerCase();
            this.wjReturnGrid.collectionView.filter = data => {
                var result =
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (data.warehouse_name != null && (data.warehouse_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.move_date != null && (data.move_date).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.product_code != null && (data.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.product_name != null && (data.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.model != null && (data.model).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.quantity != null && (data.quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.matter_no != null && (data.matter_no).toString().toLowerCase().indexOf(filter) > -1) ||
                    (data.maker_name != null && (data.maker_name).toString().toLowerCase().indexOf(filter) > -1) 
                    ;

                if (this.filterChk.not_finish == this.FLG_ON && data.fix_status_val != this.FLG_OFF) {
                    result = false;
                }
                if (this.filterChk.finished == this.FLG_ON && data.fix_status_val != this.FLG_ON) {
                    result = false;
                }

                return result;
            };
        },
        addReturn() {
            this.loading = true;
            // this.showDlgProcess = true;
            var params = new URLSearchParams();
            this.initErr(this.errors);

            var now = moment().format('YYYY/MM/DD');
            var OkFlg = true;
            if (this.processDlgData.return_kbn == this.FLG_MAKER_RETURN && this.processDlgData.sp_flg == this.FLG_OFF) {
                if (this.rmUndefinedBlank(this.processDlgData.move_date.text) == ''
                    || this.processDlgData.move_date.text < now) 
                    {
                        OkFlg = false;
                        this.errors.move_date = MSG_ERROR_NEXT_DATE;
                    }
            }

            var src = null;
            if (this.rmUndefinedBlank(this.focusRowId) != '') {
                this.wjReturnGrid.itemsSource.forEach(element => {
                    if (element.id == this.focusRowId) {
                        src = element;
                    }
                });
            }
            var quantity = 0;
            this.returnList.forEach(rec => {
                if (rec.warehouse_move_id == this.focusRowId) {
                    quantity += rec.return_quantity;
                }
            });
            if (this.rmUndefinedZero(parseInt(this.processDlgData.return_quantity)) < 1) {
                OkFlg = false;
                this.errors.return_quantity = '1' + MSG_ERROR_LOWER_NUMBER;
            }
            if ((this.rmUndefinedZero(parseInt(this.processDlgData.return_quantity)) + this.rmUndefinedZero(parseInt(quantity))) > src.quantity) {
                OkFlg = false;
                this.errors.return_quantity = src.quantity + MSG_ERROR_LIMIT_QUANTITY;
            }

            if (OkFlg) {
                if (this.processDlgData.return_kbn !== this.FLG_STOCK) {
                    alert('QRを印刷します、プリンタを準備してください。')
                }

                params.append('warehouse_move_id', this.rmUndefinedBlank(src.id));
                params.append('qr_code', this.rmUndefinedBlank(src.qr_code));
                params.append('product_id', this.rmUndefinedBlank(src.product_id));
                params.append('product_code', this.rmUndefinedBlank(src.product_code));
                params.append('matter_id', this.rmUndefinedBlank(src.matter_id));
                params.append('customer_id', this.rmUndefinedBlank(src.customer_id));
                params.append('from_warehouse_id', this.rmUndefinedBlank(src.from_warehouse_id));
                params.append('to_warehouse_id', this.rmUndefinedBlank(src.to_warehouse_id));
                params.append('stock_flg', this.rmUndefinedBlank(src.stock_flg));
                params.append('supplier_id', this.rmUndefinedZero(src.supplier_id));
                
                params.append('return_kbn', this.rmUndefinedBlank(this.processDlgData.return_kbn));
                params.append('sp_flg', this.rmUndefinedBlank(this.processDlgData.sp_flg));
                params.append('return_quantity', this.rmUndefinedBlank(this.processDlgData.return_quantity));
                params.append('move_date', this.rmUndefinedBlank(this.processDlgData.move_date.text));
                params.append('shelf_number_id', this.rmUndefinedZero(this.wjDlgParams.shelf_number_id.selectedValue));
                params.append('keep_shelf_number_id', this.rmUndefinedZero(this.wjDlgParams.keep_shelf_number_id.selectedValue));

                axios.post('/return-process/process', params)

                .then( function (response) {

                    if (response.data.status) {
                        // QR印刷
                        if (this.processDlgData.return_kbn !== this.FLG_STOCK) {
                            this.qrPrint(response.data.qr_code)
                        }
                        
                        // this.returnList = response.data.returnList;
                        // this.wjReturnGrid.refresh();
                        
                        // this.RefreshDetail();
                        // var listUrl = '/return-process/' + this.urlparam;
                        // location.href = listUrl;
                    }
                    this.loading = false;
                    this.showDlgProcess = false
                    this.search();
                }.bind(this))

                .catch(function (error) {
                    this.showDlgProcess = false
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    location.reload()
                }.bind(this))
            } else {
                this.loading = false;
                return;
            }
        },
        // 承認
        approval() {
            this.loading = true
            var id = this.focusRowId;
            var params = new URLSearchParams();

            params.append('id', this.rmUndefinedBlank(id));
            params.append('to_warehouse_id', this.rmUndefinedBlank(this.dlgData.warehouse_id.selectedValue));

            var data = null;
            this.moveList.forEach(element => {
                if (element.id == id)  {
                    data = element;
                }
            });

            if (this.rmUndefinedBlank(data) != '') {
                params.append('quantity', this.rmUndefinedBlank(data.quantity));
                params.append('delivery_id', this.rmUndefinedBlank(data.delivery_id));
                params.append('shipment_detail_id', this.rmUndefinedBlank(data.shipment_detail_id));
            }

            axios.post('/return-process/approval', params)

            .then( function (response) {
                if (response.data) {
                    var listUrl = '/return-process/' + this.urlparam;
                    location.href = listUrl;
                } else {
                    this.loading = false;
                    alert(MSG_ERROR)
                }
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
        // 却下
        rejection(id) {
            if (!confirm(MSG_CONFIRM_RETURN_REJECTION)) {
                return;
            }
            this.loading = true
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(id));

            axios.post('/return-process/rejection', params)

            .then( function (response) {
                if (response.data) {
                    var listUrl = '/return-process/' + this.urlparam;
                    location.href = listUrl;
                }
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
        // 検索
        search() {
            this.loading = true
            var params = new URLSearchParams();

            params.append('customer_name', this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
            params.append('matter_no', this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
            params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('qr_code', this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
            params.append('from_move_date', this.rmUndefinedBlank(this.wjSearchObj.from_move_date.text));
            params.append('to_move_date', this.rmUndefinedBlank(this.wjSearchObj.to_move_date.text));
            params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
            params.append('staff_name', this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));

            axios.post('/return-process/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                    this.urlparam += '&' + 'qr_code=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
                    this.urlparam += '&' + 'from_move_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_move_date.text));
                    this.urlparam += '&' + 'to_move_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_move_date.text));
                    this.urlparam += '&' + 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
                    this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));

                    var itemsSource = [];
                    var dataLength = 0;
                    this.returnList = response.data.returnList;
                    var movelist = response.data.moveList;
                    movelist.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            approval_status: document.createElement('div'),
                            fix_status: document.createElement('div'),
                            approval_btn: document.createElement('div'),
                            rejection_btn: document.createElement('div'),
                            add_btn: document.createElement('div'),
                            quantity: document.createElement('div'),
                        }
                        var _this = this;

                        var rtnExistFlg = false;
                        var quantity = 0;
                        var cnt = 0;
                        var finishCnt = 0;
                        this.returnList.forEach(rec => {
                            if (rec.warehouse_move_id == element.id) {
                                rtnExistFlg = true;
                                quantity += rec.return_quantity;
                                cnt++;
                                if (rec.return_finish == this.FLG_ON) {
                                    finishCnt++;
                                }
                            }
                        });
                        // 処置状況
                        if ((rtnExistFlg && cnt == finishCnt && element.quantity == quantity) || element.approval_status.val == this.FLG_REJECT) {
                            element.fix_status = '処置済';
                            element.fix_status_val = 1;
                            this.keepDOM[element.id].fix_status.classList.add('finish', 'status');
                        } else {
                            element.fix_status = '未処置';
                            element.fix_status_val = 0;
                            this.keepDOM[element.id].fix_status.classList.add('not-finish', 'status');
                        }
                        this.keepDOM[element.id].fix_status.innerHTML = element.fix_status;
                        

                        // 承認ステータス
                        this.keepDOM[element.id].approval_status.innerHTML = element.approval_status.text;
                        this.keepDOM[element.id].approval_status.classList.add(element.approval_status.class, 'status');

                        // 承認ボタン
                        if (element.approval_status.val == this.FLG_NOT_APPROVAL && (element.approval_auth == this.FLG_ON || this.isEditable == this.FLG_ON)) {
                            this.keepDOM[element.id].approval_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) +' class="btn btn-primary approval-btn">承認</button>';
                            this.keepDOM[element.id].approval_btn.addEventListener('click', function(e) {
                                if (e.target.dataset.id) {
                                    var data = JSON.parse(e.target.dataset.id);
                                    _this.focusRowId = data;

                                    _this.showDlgApproval = true;
                                    // _this.approval(data);
                                }
                            }) 
                        } else {
                            this.keepDOM[element.id].approval_btn.innerHTML = '<button class="btn btn-primary approval-btn btn-disabled" disabled>承認</button>';
                        }

                        // 却下ボタン
                        if (element.approval_status.val == this.FLG_NOT_APPROVAL && (element.approval_auth == this.FLG_ON || this.isEditable == this.FLG_ON)) {
                            this.keepDOM[element.id].rejection_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-delete approval-btn">却下</button>';
                            this.keepDOM[element.id].rejection_btn.addEventListener('click', function(e) {
                                if (e.target.dataset.id)
                                var data = JSON.parse(e.target.dataset.id);
                                _this.rejection(data);
                            }) 
                        } else {
                            this.keepDOM[element.id].rejection_btn.innerHTML = '<button class="btn btn-delete approval-btn btn-disabled" disabled>却下</button>';
                        }

                        // 追加ボタン
                        if (this.isAuthProc == this.FLG_ON && element.approval_status.val == this.FLG_APPROVAL && element.finish_flg == this.FLG_FINISH_MOVED && element.quantity > quantity) {
                            this.keepDOM[element.id].add_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' data-stock=' + JSON.stringify(element.stock_flg) + ' data-flg=' + JSON.stringify(element.maker_return_flg) + ' data-delivery=' + JSON.stringify(element.delivery_id) + ' class="btn btn-primary">追加</button>';
                            this.keepDOM[element.id].add_btn.addEventListener('click', function(e) {
                                if (e.target.dataset.id) {
                                    var data = JSON.parse(e.target.dataset.id);
                                    var maker_flg = JSON.parse(e.target.dataset.flg);
                                    var stock_flg = JSON.parse(e.target.dataset.stock);
                                    var delivery = JSON.parse(e.target.dataset.delivery);
                                    _this.focusRowId = data;
                                    if (maker_flg == _this.FLG_OFF) {
                                        _this.maker_return_flg = true;
                                        _this.processDlgData.sp_flg = 0;
                                    } else {
                                        _this.maker_return_flg = false;
                                    }
                                    
                                    _this.showDlgProcess = true;

                                    if (stock_flg == _this.STOCK_FLG.STOCK && _this.rmUndefinedZero(delivery) == 0) {
                                        var list = [];
                                        _this.processlist.forEach(element => {
                                            if (element.value_code == 3 || element.value_code == 4) {
                                                list.push(element);
                                            }
                                        });
                                        

                                        _this.$nextTick(function () {
                                            _this.wjDlgParams.return_kbn.itemsSource = list;
                                        })
                                    } else {
                                        _this.$nextTick(function () {
                                            _this.wjDlgParams.return_kbn.itemsSource = _this.processlist;
                                        })
                                    }
                                    
                                    _this.setShelfList(data);
                                    _this.setKeepShelfList(data);
                                    // _this.addReturn(data);
                                }
                            }) 
                        } else if(this.isAuthProc == this.FLG_ON) {
                            this.keepDOM[element.id].add_btn.innerHTML = '<button class="btn btn-primary btn-disabled" disabled>追加</button>';
                        }

                        // 数量
                        this.keepDOM[element.id].quantity.innerHTML = element.quantity;
                        this.keepDOM[element.id].quantity.classList.add('grid-quantity');

                        itemsSource.push({
                            id: element.id,
                            move_kind: element.move_kind,
                            from_warehouse_id: element.from_warehouse_id,
                            to_warehouse_id: element.to_warehouse_id,
                            shelf_number_id: element.to_shelf_number_id,
                            finish_flg: element.finish_flg,
                            move_date: element.move_date,
                            stock_flg: element.stock_flg,
                            delivery_id: element.delivery_id,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            matter_id: element.matter_id,
                            customer_id: element.customer_id,
                            matter_no: element.matter_no,
                            matter_name: element.matter_name,
                            quantity: element.quantity,
                            qr_code: element.qr_code,
                            approval_status: element.approval_status,
                            approval_user: element.approval_user,
                            approval_at: element.approval_at,
                            warehouse_name: element.warehouse_name,
                            maker_name: element.maker_name,
                            model: element.model,
                            fix_status_val: element.fix_status_val,
                            supplier_id: element.supplier_id,
                        })

                        dataLength++;
                    });
                    // データセット
                    this.wjReturnGrid.itemsSource = itemsSource;
                    this.resultlen = dataLength;
                    this.moveList = movelist;

                    // 設定更新
                    this.wjReturnGrid = this.applyGridSettings(this.wjReturnGrid);

                    // 描画更新
                    this.wjReturnGrid.refresh();
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
        setShelfList(id) {
            this.moveList.forEach(element => {
                if (element.id == id) {
                    this.shelfList = element.shelf_list;

                    if (element.shelf_list.length > 1) {
                        this.processDlgData.isShelfReadOnly = false;
                    } else {
                        this.processDlgData.isShelfReadOnly = true;
                    }
                }
            });

            // this.wjDlgParams.shelf_number_id.onSelectedValueChanged();
        },
        setKeepShelfList(id) {
            this.moveList.forEach(element => {
                if (element.id == id) {
                    this.keepShelfList = element.keep_shelf_list;

                    if (element.keep_shelf_list.length > 1) {
                        this.processDlgData.isKeepShelfReadOnly = false;
                    } else {
                        this.processDlgData.isKeepShelfReadOnly = true;
                    }
                }
            });

            // this.wjDlgParams.shelf_number_id.onSelectedValueChanged();
        },
        changeIdxDepartment: function(sender){
            // 部門を選択したら担当者を絞り込む
            var tmpArr = this.staffdepartlist;
            var tmpStaff = this.stafflist;
            if (sender.selectedItem) {
                tmpArr = [];
                for(var key in this.staffdepartlist) {
                    if (sender.selectedItem.id == this.staffdepartlist[key].department_id) {
                        tmpArr.push(this.staffdepartlist[key]);
                    }
                }
                tmpStaff = [];
                for(var key in this.stafflist) {
                    for(var i = 0; i < tmpArr.length; i++) {
                        if (tmpArr[i].staff_id == this.stafflist[key].id) {
                            tmpStaff.push(this.stafflist[key]);
                            break;
                        }
                    }
                }      
            }
            this.wjSearchObj.staff_name.itemsSource = tmpStaff;
            this.wjSearchObj.staff_name.selectedIndex = -1;
        },
        // 検索条件のクリア
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
        cancel(id) {
            if (!confirm(MSG_CONFIRM_PROCESS_CANCEL)) {
                return;
            }            
            this.loading = true

            var data = null;
            var parent = null;
            this.returnList.forEach(element => {
                if (element.id == id) {
                    data = element;
                }
            });
            this.moveList.forEach(element => {
                if (element.id == data.warehouse_move_id) {
                    parent = element;
                }
            });

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(id));
            params.append('warehouse_move_id', this.rmUndefinedBlank(data.warehouse_move_id));
            params.append('return_kbn', this.rmUndefinedBlank(data.return_kbn));
            params.append('return_finish', this.rmUndefinedBlank(data.return_finish));
            params.append('stock_flg', this.rmUndefinedBlank(data.stock_flg));
            params.append('product_id', this.rmUndefinedBlank(data.product_id));
            params.append('product_code', this.rmUndefinedBlank(data.product_code));
            params.append('to_warehouse_id', this.rmUndefinedBlank(parent.to_warehouse_id));
            params.append('shelf_number_id', this.rmUndefinedBlank(data.shelf_number_id));
            params.append('quantity', this.rmUndefinedBlank(data.return_quantity));
            params.append('matter_id', this.rmUndefinedBlank(data.matter_id));
            params.append('customer_id', this.rmUndefinedBlank(data.customer_id));
            params.append('qr_code', this.rmUndefinedBlank(data.qr_code));
            params.append('parent_qr_code', this.rmUndefinedBlank(parent.qr_code));

            axios.post('/return-process/cancel', params)

            .then( function (response) {
                this.loading = false

                if (response.data.status) {
                    // 成功
                    var listUrl = '/return-process/' + this.urlparam
                    location.href = listUrl
                } else {
                    // 失敗
                    // location.reload();
                    if(this.rmUndefinedBlank(response.data.message) !== ''){
                        alert(response.data.message.replace(/\\n/g, '\n'));
                    }
                    
                }
            }.bind(this))

            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                window.onbeforeunload = null;
                location.reload()
            }.bind(this))
        },
        qrPrint(qrCode) {
            this.loading = true;
            var params = new URLSearchParams();
            params.append('qr_code', this.rmUndefinedBlank(qrCode));

            axios.post('/return-process/print', params)

            .then( function (response) {
                if (response.data) {
                    // var listUrl = '/return-process/' + this.urlparam;
                    // location.href = listUrl;
                }
                this.loading = false;
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
        getDetails(row) {
            var itemsSource = []
            this.keepDetailDOM = {};
            this.returnList.forEach(rec => {
                if (rec.warehouse_move_id == row.id) {
                    this.keepDetailDOM[rec.id] = {
                        cancel_btn: document.createElement('div'),
                        print_btn: document.createElement('div'),
                        return_kbn_txt: document.createElement('div'),
                    }

                    var _this = this;
                    // 取消ボタン
                    if (this.rmUndefinedZero(rec.purchase_id) == 0 && (rec.return_kbn == this.FLG_MAKER_RETURN || (rec.return_kbn == this.FLG_DISCARD && rec.return_finish == this.FLG_OFF))) {
                        this.keepDetailDOM[rec.id].cancel_btn.innerHTML = '<button data-id=' + JSON.stringify(rec.id) + ' class="btn btn-delete cancel-btn">処置取消</button>';
                        this.keepDetailDOM[rec.id].cancel_btn.addEventListener('click', function(e) {
                            if (e.target.dataset.id)
                            var data = JSON.parse(e.target.dataset.id);
                            _this.cancel(data);
                        }) 
                    } else {
                        this.keepDetailDOM[rec.id].cancel_btn.innerHTML = '<button class="btn btn-delete cancel-btn btn-disabled" disabled>処置取消</button>';
                    }

                    // 印刷ボタン  
                    this.keepDetailDOM[rec.id].print_btn.innerHTML = '<button type="button" data-qr=' + JSON.stringify(rec.qr_code) + ' class="btn btn-edit print-btn">印刷</button>';
                    this.keepDetailDOM[rec.id].print_btn.addEventListener('click', function(e) {
                        if (e.target.dataset.qr) {
                            var data = JSON.parse(e.target.dataset.qr);
                            _this.qrPrint(data);
                        }
                    })

                    if (rec.return_finish == this.FLG_ON && rec.return_kbn == this.FLG_MAKER_RETURN) {
                        rec.return_finish_txt = '済';
                    }

                    itemsSource.push({
                        id: rec.id,
                        purchase_id: rec.purchase_id,
                        qr_code: rec.qr_code,
                        warehouse_move_id: rec.warehouse_move_id,
                        return_quantity: rec.return_quantity,
                        maker_return_date: rec.maker_return_date,
                        return_kbn: rec.return_kbn,
                        return_kbn_txt: rec.return_kbn_txt,
                        sp_flg: rec.sp_flg,
                        return_status_txt: rec.return_status_txt,
                        return_status: rec.return_status,
                        return_finish: rec.return_finish,
                        return_finish_txt: rec.return_finish_txt,

                    });
                }
            });

            return itemsSource;
        },
        // グリッド生成
        createGrid(divId, gridItems) {
            var gridCtrl = new wjMultiRow.MultiRow(divId, {
                itemsSource: gridItems,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowAddNew: false,
                allowDelete: false,
                allowSorting: false,
                // headersVisibility: wjGrid.HeadersVisibility.Column,
                keyActionEnter: wjGrid.KeyAction.None,
                isReadOnly: true,
                // selectionMode: wjGrid.SelectionMode.None,
            });

            var _this = this;

            // 行高さ
            gridCtrl.rows.defaultSize = 30;

            // セル編集後イベント
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

                switch (col.name) {

                }

                gridCtrl.collectionView.commitEdit();
            }.bind(this));

            // 編集前イベント
            gridCtrl.beginningEdit.addHandler((s, e) => {

            });

            var _this = this;
            // itemFormatterセット
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                var dataItem = panel.rows[r].dataItem;
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                //     // チェックボックス生成
                //     if (panel.columns[c].name == 'chk') {
                //         var checkedCount = 0;
                //         for (var i = 0; i < gridCtrl.rows.length; i++) {
                //             if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                //         }

                //         var checkBox = '<input type="checkbox">';
                //         if(this.isReadOnly){
                //             checkBox = '<input type="checkbox" disabled="true">';
                //         }
                //         cell.innerHTML = checkBox;
                //         var checkBox = cell.firstChild;
                //         checkBox.checked = checkedCount > 0;
                //         checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                //         checkBox.addEventListener('click', function (e) {
                //             gridCtrl.beginUpdate();
                //             for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                //                 gridCtrl.collectionView.items[i].chk = checkBox.checked;
                //                 // this.changeGridCheckBox(grid.collectionView.items[i]);
                //             }
                //             gridCtrl.endUpdate();
                //         }.bind(this));
                //     }

                } else if (panel.cellType == wjGrid.CellType.Cell) {
                    // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = panel.columns[c];
                    var item = panel.rows[r];
                    if (item.dataItem != undefined || item.dataItem != null) {
                        switch(col.name) {
                            case 'warehouse_name':
                            case 'matter_no':
                            case 'matter_name':
                            case 'product_code':
                            case 'product_name':
                            case 'maker_name':
                            case 'model':
                            case 'move_date':
                                cell.style.textAlign = 'left';
                                break;
                            case 'quantity':
                                cell.style.textAlign = 'right';
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].quantity);
                                break;
                            case 'approval_status':
                                if (item.recordIndex == 0) {
                                    cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].approval_status);
                                } else if (item.recordIndex == 1) {
                                    cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].fix_status);
                                }
                                break;
                            case 'approval':
                                if (item.recordIndex == 0) {
                                    cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].approval_btn);
                                }else {
                                    cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].rejection_btn);
                                }
                                break;
                            // case 'rejection':
                            //     cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].rejection_btn);
                            //     break;
                            case 'add':
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].add_btn);
                                break;
                        }
                    }
                }
            }.bind(this);

            var _this = this;
            // 子グリッド定義
            var detailCtrl = new wjDetail.FlexGridDetailProvider(gridCtrl, {
                isAnimated: false,
                maxHeight: 200,
                // detailVisibilityMode: wjDetail.DetailVisibilityMode.ExpandMulti,

                createDetailCell: function (row) {
                    var cell = document.createElement('div');
                    gridCtrl.hostElement.appendChild(cell);
                    var detailGrid = new wjMultiRow.MultiRow(cell, {
                        headersVisibility: wjGrid.HeadersVisibility.Column,
                        autoGenerateColumns: false,
                        itemsSource: _this.getDetails(row.dataItem),
                        layoutDefinition: _this.gridChildLayout,
                        // selectionMode: wjGrid.SelectionMode.None,
                    });

                    detailGrid.itemFormatter = function(panel, r, c, cell) {
                        // 列ヘッダ中央寄せ
                        if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                            cell.style.textAlign = 'center';
                        }else if (panel.cellType == wjGrid.CellType.Cell) {
                            //this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                            var col = gridCtrl.getBindingColumn(panel, r, c);
                            var dataItem = panel.rows[r].dataItem;
                            cell.style.color = '';
                            switch(col.name) {
                                case 'return_kbn_txt':
                                    cell.style.textAlign = 'center';
                                    break;
                                case 'return_status_txt':
                                    cell.style.textAlign = 'center';
                                    break;
                                case 'return_finish_txt':
                                    cell.style.textAlign = 'center';
                                    break;
                                case 'maker_return_date':
                                    if (r % 2 == 0) {
                                        cell.style.textAlign = 'right';
                                    } else {
                                        cell.style.textAlign = 'center';
                                    }
                                    break;
                                case 'return_quantity':
                                    cell.style.textAlign = 'right';
                                    break;
                                case 'cancel':
                                    // 取消ボタン
                                    if(dataItem !== undefined){
                                        var rId1 = 'cancelBtn' + dataItem.id;
                            
                                        var div = document.createElement('div');

                                        var btnCancel = document.createElement('button');
                                        btnCancel.type = 'button';
                                        btnCancel.id = rId1;

                                        var isDisable = false;
                                        if(this.rmUndefinedZero(dataItem.purchase_id) == 0 && (dataItem.return_kbn == this.FLG_MAKER_RETURN || (dataItem.return_kbn == this.FLG_DISCARD && dataItem.return_finish == this.FLG_OFF))){
                                            isDisable = false;
                                            // btnCancel.classList.add('btn-disabled');
                                        } else {
                                            isDisable = true;
                                            btnCancel.classList.add('btn-disabled');
                                        }
                                        btnCancel.classList.add('btn', 'btn-delete', 'cancel-btn');
                                        btnCancel.innerHTML = '処置取消'; 
                                        btnCancel.disabled = isDisable;

                                        btnCancel.addEventListener('click', function (e) {
                                            this.cancel(dataItem.id);
                                        }.bind(this));

                                        div.appendChild(btnCancel);
                                        cell.appendChild(div);
                                    }
                                    cell.style.textAlign = 'center';
                                    break;    
                                case 'print':
                                    // 印刷ボタン
                                    if(dataItem !== undefined){
                                        var rId1 = 'cancelBtn' + dataItem.id;
                            
                                        var div = document.createElement('div');

                                        var btnPrint = document.createElement('button');
                                        btnPrint.type = 'button';
                                        btnPrint.id = rId1;

                                        var isDisable = false;
                                        if (this.rmUndefinedBlank(dataItem.qr_code) == '') {
                                            isDisable = true;
                                            btnPrint.classList.add('btn-disabled');
                                        }
                                        btnPrint.classList.add('btn', 'btn-edit', 'print-btn');
                                        btnPrint.innerHTML = '印刷'; 
                                        btnPrint.disabled = isDisable;

                                        btnPrint.addEventListener('click', function (e) {
                                            this.qrPrint(dataItem.qr_code);
                                        }.bind(this));

                                        div.appendChild(btnPrint);
                                        cell.appendChild(div);
                                    }
                                    cell.style.textAlign = 'center';
                                    // this.keepDetailDOM[rec.id].print_btn.innerHTML = '<button type="button" data-qr=' + JSON.stringify(rec.qr_code) + ' class="btn btn-edit print-btn">印刷</button>';
                                    // this.keepDetailDOM[rec.id].print_btn.addEventListener('click', function(e) {
                                    //     if (e.target.dataset.qr) {
                                    //         var data = JSON.parse(e.target.dataset.qr);
                                    //         _this.qrPrint(data);
                                    //     }
                                    // })

                                    // cell.appendChild(_this.keepDetailDOM[panel.getCellData(r, _this.gridPKDetailCol)].print_btn);
                                    break;
                                
                            }
                        }
                    }.bind(this);

                    cell.parentElement.removeChild(cell);
                    return cell;
                }.bind(this),

                rowHasDetail: function (row) {
                    var rtn = true;
                    // 奇数行の展開ボタン削除 
                    if (row.recordIndex % 2 == 0) {
                        rtn = false;
                    };
                    // 未承認の親グリッドから展開ボタン削除
                    if (row.dataItem.approval_status.val == _this.FLG_NOT_APPROVAL || 
                        row.dataItem.approval_status.val == _this.FLG_REJECT) {
                        rtn = false;
                    }

                    return rtn;
                },
            });
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e.pageX, e.pageY);
                if(ht.cellType === wjGrid.CellType.RowHeader){
                    if (detailCtrl.isDetailVisible(ht.row)) {
                        detailCtrl.hideDetail(ht.row);
                    }else{
                        detailCtrl.showDetail(ht.row);
                    }
                }
            })

            this.wjDetailGrid = detailCtrl;

            return gridCtrl;
        },
        getLayout() {
            return [
                {
                    header: '状況', cells: [
                        { name: 'approval_status', header: '承認状況', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true }, 
                        { binding: 'fix_status', name: 'fix_status', header: '処置', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true }, 
                    ]
                },
                {
                    header: '倉庫', cells: [
                        { binding: 'warehouse_name', name: 'warehouse_name', header: '倉庫名', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true},  
                        { binding: 'move_date', name: '', header: '返品日', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true},  
                    ]
                },
                {
                    header: '案件', cells: [
                        { binding: 'matter_no', name: 'matter_no', header: '案件番号', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true },  
                        { binding: 'matter_name', name: 'matter_name', header: '案件名', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true }, 
                    ]
                },
                {
                    header: '商品', cells: [
                        { binding: 'product_code', name: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true },  
                        { binding: 'product_name', name: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true },  
                    ]
                },
                {
                    header: '商品', cells: [
                        { binding: 'maker_name', name: 'maker_name', header: 'メーカー名', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },  
                        { binding: 'model', name: 'model', header: '型式／規格', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },  
                    ]
                },
                {
                    header: '数量', cells: [
                        { binding: 'id', name: 'quantity', header: '数量', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true  },  
                    ]
                },
                {
                    header: '返品承認', cells: [
                        { name: 'approval', header: '承認', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                        { name: 'rejection', header: '却下', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true }, 
                    ]
                },
                {
                    header: '処置追加', cells: [
                        { binding: 'id', name: 'add', header: '追加', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true  },  
                    ]
                },
                /* 非表示 */
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },     
            ]
        },
        getDetailLayout() {
            return [
                {
                    header: '処置', cells: [
                        { binding: 'return_kbn_txt', name: 'return_kbn_txt', header: '処置', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true },  
                        { binding: 'return_status_txt', name: 'return_status', header: '処置状況', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true },  
                    ]
                },
                {
                    header: '返品情報', cells: [
                        { binding: 'maker_return_date', name: 'maker_return_date', header: 'メーカー返品日', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true},  
                        { binding: 'return_finish_txt', name: 'return_finish_txt', header: 'メーカー返品済', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true},  
                    ]
                },
                {
                    header: '数量', cells: [
                        { binding: 'return_quantity', name: 'return_quantity', header: '数量', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },  
                    ]
                },
                {
                    header: '取消', cells: [
                        { name: 'cancel', header: '取消', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                    ]
                },
                {
                    header: '印刷', cells: [
                        { name: 'print', header: 'QR印刷', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                    ]
                },
                /* 非表示 */
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },     
            ]
        },
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('product_id', this.rmUndefinedBlank(this.productdata.id));

            axios.post('/product-campaign-price/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/product-campaign-price/' + this.productdata.id + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    window.onbeforeunload = null;
                    location.reload();
                }
            }.bind(this))

            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                window.onbeforeunload = null;
                location.reload()
            }.bind(this))
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
            // 列ヘッダー結合
            grid.allowMerging = wjGrid.AllowMerging.All;
            for (var i = 0; i < grid.columns.length; i++) {
                if (i == 5 || i == 7){
                    grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(1, i));
                    grid.columnHeaders.columns[i].allowMerging = true;
                }
            }  
            // grid.allowMerging = wjGrid.AllowMerging.All;
            
            return grid;
        },
        // 戻る
        back() {
            var listUrl = '/product-list' + window.location.search

            if (!this.isReadOnly && this.productdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'product-campaign-price');
                params.append('keys', this.rmUndefinedBlank(this.productdata.id));
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    this.loading = false
                    if (response.data) {
                        window.onbeforeunload = null;
                        location.href = listUrl
                    } else {
                        window.onbeforeunload = null;
                        location.reload();
                    }
                }.bind(this))
                .catch(function (error) {
                    this.loading = false
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }.bind(this))
            } else {
                window.onbeforeunload = null;
                location.href = listUrl
            }
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
        // initProName(sender) {
        //     this.wjSearchObj.product_name = sender
        // },
        // initProCode(sender) {
        //     this.wjSearchObj.product_code = sender
        // },
        initSuppName(sender) {
            this.wjSearchObj.supplier_name = sender
        },
        initQrCode(sender) {
            this.wjSearchObj.qr_code = sender
        },
        initDepartment(sender) {
            this.wjSearchObj.department_name = sender
        },
        initStaff(sender) {
            this.wjSearchObj.staff_name = sender
        },
        initFromMoveDate(sender) {
            this.wjSearchObj.from_move_date = sender
        },
        initToMoveDate(sender) {
            this.wjSearchObj.to_move_date = sender
        },
        initDlgWarehouse(sender) {
            this.dlgData.warehouse_id = sender
        },
        initProcess(sender) {
            this.wjDlgParams.return_kbn = sender
        },
        initShelf(sender) {
            this.wjDlgParams.shelf_number_id = sender
        },
        changeIdxShelf(e) {
            this.processDlgData.shelf_number_id = e.selectedValue
        },
        initKeepShelf(sender) {
            this.wjDlgParams.keep_shelf_number_id = sender
        },
        changeIdxKeepShelf(e) {
            this.processDlgData.keep_shelf_number_id = e.selectedValue
        },
        changeIdxProcess(e) {
            this.processDlgData.return_kbn = e.selectedValue
        },
        initReturnMoveDate(sender) {
            this.processDlgData.move_date = sender
        }
    }
}
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
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.status {
    display: block !important;
    color: #ffffff;
    width: 100%;
    height: 22px;
    text-align: center;
    padding: 0px 0px !important;
}
.not-approval {
    background-color: #FF3B30;
}
.approvaled {
    background-color: #4CD964;
}
.not-finish {
    background-color: #FF3B30;
}
.finish {
    background-color: #4CD964;
}
.rejection {
    background-color: #FF3B30;
}
.btn-disabled {
    background-color: #cccccc;
}

.approval-btn {
    width: 100%;
    height: 25px !important;
    font-size: 12px;
}

/* グリッド */
.wj-multirow {
    height: 400px;
    margin: 6px 0;
}
.wj-detail {
    margin-left: 550px;
    width: 700px !important;
}
.grid-quantity {
    text-align: right;
    width: 88px;
    height: 34.2px;
}
.btn {
    width: 80px;
    height: 34.2px;
}
.el-dlg-body {
    height: 250px;
}
.dlg-footer {
    text-align: right;
}

</style>