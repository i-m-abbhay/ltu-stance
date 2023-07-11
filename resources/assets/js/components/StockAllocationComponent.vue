<template>
    <div>
    <loading-component :loading="loading" />
    <!-- 検索条件 --> 
        <div class="search-body col-md-12 col-sm-12">
            <div class="search-form" id="searchForm">
                <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                    <div class="row">
                        <div class="col-md-3 col-sm-3 col-xs-12">
                            <label class="control-label">得意先名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="customer_name"
                                display-member-path="customer_name"
                                selected-value-path="customer_name"
                                :selected-index="-1"
                                :selected-value="searchParams.customer_name"
                                :is-required="false"
                                :initialized="initCustomer"
                                :max-items="customerlist.length"
                                :min-length="1"
                                :items-source="customerlist">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-12" v-bind:class="{'has-error': (errors.matter != '') }">
                            <label class="control-label">案件番号</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="matter_no"
                                display-member-path="matter_no"
                                selected-value-path="matter_no"
                                :selected-index="-1"
                                :selected-value="searchParams.matter_no"
                                :selectedIndexChanged="selectMatterNo"
                                :is-required="false"
                                :initialized="initMatterNo"
                                :max-items="matterlist.length"
                                :min-length="1"
                                :items-source="matterlist">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.matter }}</span>
                        </div>
                        <div class="col-md-3 col-sm-3 col-xs-12" v-bind:class="{'has-error': (errors.matter != '') }">
                            <label class="control-label">案件名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="matter_name"
                                display-member-path="matter_name"
                                selected-value-path="matter_name"
                                :selected-index="-1"
                                :selected-value="searchParams.matter_name"
                                :selectedIndexChanged="selectMatterName"
                                :is-required="false"
                                :initialized="initMatterName"
                                :max-items="matterlist.length"
                                :min-length="1"
                                :items-source="matterlist">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.matter }}</span>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-12">
                            <label class="control-label">部門名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="department_name"
                                display-member-path="department_name"
                                selected-value-path="department_name"
                                :selected-index="-1"
                                :selected-value="searchParams.department_name"
                                :is-required="false"
                                :initialized="initDepartment"
                                :max-items="departmentlist.length"
                                :min-length="1"
                                :items-source="departmentlist">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-2 col-sm-2 col-xs-12">
                            <label class="control-label">営業担当者名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="staff_name"
                                display-member-path="staff_name"
                                selected-value-path="staff_name"
                                :selected-index="-1"
                                :selected-value="searchParams.staff_name"
                                :is-required="false"
                                :initialized="initStaff"
                                :max-items="stafflist.length"
                                :min-length="1"
                                :items-source="stafflist">
                            </wj-auto-complete>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 text-right">
                        <button type="button" class="btn btn-clear" @click="clearParams">クリア</button>
                        <button type="submit" class="btn btn-search">検索</button>
                    </div>
                    <div class="clearfix"></div>
                </form>
            </div>
        </div>

        <div class="result-body col-sm-12 col-md-12">
            <div class="row">
                <p class="col-md-12 col-sm-12 col-xs-12 text-left"><u>優先設定</u></p>
                <div class="warehouse-body col-md-6 col-sm-6 col-xs-12">
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <label class="control-label" v-bind:isReadOnly="!isEditable.from_warehouse_id_1">引当倉庫１</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selectedIndexChanged="changeIdxWarehouse"
                            :selected-value="initParams.from_warehouse_id_1"
                            :is-required="false"
                            :initialized="initWarehouse1"
                            :isReadOnly="(!isEditable.from_warehouse_id_1 || isReadOnly)"
                            :isDisabled="!isEditable.from_warehouse_id_1"
                            :max-items="warehouselist.length"
                            :min-length="1"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <label class="control-label" v-bind:isReadOnly="!isEditable.from_warehouse_id_2">引当倉庫２</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="searchParams.from_warehouse_id_2"
                            :selectedIndexChanged="changeIdxWarehouse2"
                            :is-required="false"
                            :initialized="initWarehouse2"
                            :isReadOnly="(!isEditable.from_warehouse_id_2 || isReadOnly)"
                            :isDisabled="!isEditable.from_warehouse_id_2"
                            :max-items="warehouselist.length"
                            :min-length="1"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                        <span class="text-danger">{{ errors.from_warehouse }}</span>
                    </div>
                </div>

                <div class="col-md-6 col-sm-6 col-xs-12">
                    <div class="row text-right">
                        <div class="col-md-12 text-right">
                            <a class="btn btn-primary" v-bind:disabled="orderdata.matter_id == null" @click="orderLink">発注画面</a>
                            <button type="button" class="btn btn-danger btn-unlock btn-lock" v-bind:disabled="(main.length == 0 || matterCompleteFlg == FLG_ON)" @click="unlock" v-show="isLocked">ロック解除</button>
                            <button type="button" class="btn btn-primary btn-edit btn-lock" v-bind:disabled="(main.length == 0 || matterCompleteFlg == FLG_ON)" v-show="isShowEditBtn" @click="edit">編集</button>
                            <p class="btn btn-default btn-editing btn-lock" v-bind:disabled="(main.length == 0 || matterCompleteFlg == FLG_ON)" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12" style="float: right">
                            <label v-show="matterCompleteFlg == FLG_ON" class="attention-color">案件完了済</label>
                            <label class="form-control-static" v-show="(rmUndefinedBlank(lockData.id) != '')">ロック日時：{{ lockData.lock_dt|datetime_format }}&emsp;&emsp;</label>
                            <label class="form-control-static" v-show="(rmUndefinedBlank(lockData.id) != '')">ロック者：{{ lockData.lock_user_name }}&emsp;</label>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <button type="button" class="btn btn-primary btn-calc text-right" v-bind:disabled="(isReadOnly || main.length == 0 || matterCompleteFlg == FLG_ON)" @click="autoCalc">自動計算</button>
                            <button type="button" class="btn btn-save text-right" v-bind:disabled="(isLocked || editable == FLG_OFF || isReadOnly || hasError || main.length == 0 || matterCompleteFlg == FLG_ON)" @click="save">更新／登録</button>
                        </div>
                    </div>

                    <!-- <div class="row" style="margin-top: 5px;">
                        <div class="col-md-6">
                            <button type="button" class="btn btn-primary btn-calc text-right" v-bind:disabled="(isReadOnly || main.length == 0 || matterCompleteFlg == FLG_ON)" @click="autoCalc">自動計算</button>
                            <button type="button" class="btn btn-save text-right" v-bind:disabled="(isLocked || editable == FLG_OFF || isReadOnly || hasError || main.length == 0 || matterCompleteFlg == FLG_ON)" @click="save">更新／登録</button>
                        </div>
                        <div class="col-md-2 text-right">
                            <button type="button" class="btn btn-danger btn-unlock btn-lock" v-bind:disabled="(main.length == 0 || matterCompleteFlg == FLG_ON)" @click="unlock" v-show="isLocked">ロック解除</button>
                            <button type="button" class="btn btn-primary btn-edit btn-lock" v-bind:disabled="(main.length == 0 || matterCompleteFlg == FLG_ON)" v-show="isShowEditBtn" @click="edit">編集</button>
                            <p class="btn btn-default btn-editing btn-lock" v-bind:disabled="(main.length == 0 || matterCompleteFlg == FLG_ON)" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                        </div>
                    </div> -->
                </div>
            </div>
            
            <div class="row" style="margin-top: 5px;">
                <div class="col-sm-4">
                    <div class="input-group">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-search"></span>
                        </div>
                        <input v-model="filterText" @input="filter()" class="form-control">
                    </div>
                </div>
                <div class="col-sm-2">
                    <div class="input-group checkbox-group">
                        <el-checkbox v-model="filterChk.quantity_zero" :true-label="FLG_ON" :false-label="FLG_OFF" @input="filter()">総引当数0のみ</el-checkbox>
                    </div>
                </div>
                <div class="col-sm-6">
                    <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                </div>
            </div>
            <!-- グリッド -->
            <div class="col-md-12 grid-div" v-show="main.length > 0">
                <div v-bind:id="'wjHeaderGrid'"></div>
            </div>
        </div>
        <!-- ボタン -->
        <div class="row">
            <div class="col-md-12">
                <button type="button" class="btn btn-warning btn-back pull-right" v-on:click="back">戻る</button>
            </div>
        </div>
    </div>
</template>



<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjDetail from '@grapecity/wijmo.grid.detail';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';

var INIT_HEADER_ROW = {
    id: '',
    construction_name: '',
    product_code: '',
    product_name: '',
    model: '',
    maker_name: '',
    supplier_name: '',
    quote_quantity: 0,
    stock_quantity: 0,
    unit: '',
    stock_unit: '',
    total_reserve_quantity: 0,
    reserve_quantity_1: 0,
    active_quantity_1: 0,
    reserve_quantity_2: 0,
    active_quantity_2: 0,
};

var INIT_DETAIL_ROW = {
    id: '',
    quote_detail_id: '',
    warehouse_name: '',
    input_actual_quantity: 0,
    input_keep_quantity: 0,
    actual_quantity: 0,
    keep_quantity: 0,
    active_quantity: 0,
    arrival_quantity: 0,
    next_arrival_date: '',
};

export default {
    data: ()=> ({
        loading: false,
        isEditable: {
            from_warehouse_id_1: true,
            from_warehouse_id_2: true,
        },
        isLocked: false,
        isShowEditBtn: true,
        isReadOnly: true,
        hasError: false,

        editable: 0,
        isOwnLock: 0,
        lockData: {},

        matterCompleteFlg: 0,

        FLG_ON : 1,
        FLG_OFF: 0,

        ORDER_FLG: 0,
        STOCK_FLG: 1,
        KEEP_FLG: 2,

        tableData: 0,

        filterText: '',
        filterChk: {
            quantity_zero: 0,
        },
        searchParams: {
            customer_name: '',
            matter_no: '',
            matter_name: '',
            department_name: '',
            staff_name: '',
            from_warehouse_id_1: '',
            from_warehouse_id_2: '',
        },
        initParams: {
            from_warehouse_id_1: '',
            from_warehouse_id_2: '',
        },
        errors: {
            matter: '',
            from_warehouse: '',
        },

        main: [],
        details: {},
        quantity: [],
        reservelist: [],
        orderdata: {},
        arrivalList: [],

        headers: new wjCore.CollectionView(),
        reserves: new wjCore.CollectionView(),

        layoutDefinition: null,
        reserveLayout: null,
        keepDOM: {},
        keepDetailDOM: {},
        urlparam: '',
        queryparam: '',

        cgeWarehousename: null,

        gridSetting: {
            // リサイジング不可 [ ]
            deny_resizing_col: [12],
            // 非表示列
            invisible_col: [12],
        },
        detailSetting: {
            // リサイジング不可 [ ]
            deny_resizing_col: [0],
            // 非表示列
            invisible_col: [],
        },
        gridPKCol: 11,
        gridDetailPKCol: 8,
        gridDetailErrCol: 16,

        wjHeaderGrid: null,
        wjDetailGrid: null,
        wjFlexDetailSetting: null,
        wjSearchObj: {
            customer_name: {},
            matter_no: {},
            matter_name: {},
            department_name: {},
            staff_name: {},
        },
        wjInputObj : {
            from_warehouse_id_1: {},
            from_warehouse_id_2: {},
        },
    }),
    props: {
        customerlist: Array,
        matterlist: Array,
        departmentlist: Array,
        stafflist: Array,
        warehouselist: Array,
        initsearchparams: {
            type: Object,
            department_name: String,
        }
    },
    created: function() {
        // 検索条件セット
        this.queryparam = window.location.search;
        if (this.queryparam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryparam, this.searchParams);
        }
        // this.search();
        this.layoutDefinition = this.getLayout();
        this.reserveLayout = this.getDetailLayout();
    },
    mounted: function() {
        if (this.queryparam.length > 1) {
            this.search();
        } else {
            if (this.initsearchparams.department_name) {
                this.searchParams.department_name = this.initsearchparams.department_name;   
            }
            if (this.initsearchparams.staff_name) {
                this.searchParams.staff_name = this.initsearchparams.staff_name;   
            }   
            if (this.initsearchparams.warehouse_id) {
                this.initParams.from_warehouse_id_1 = this.initsearchparams.warehouse_id;   
            }
        }

        var arr = [];
        // arr.push(Vue.util.extend({}, INIT_HEADER_ROW));        

        this.$nextTick(function() {
            var gridItemSource = new wjCore.CollectionView(arr, {
                // newItemCreator: function () {
                //     return Vue.util.extend({}, INIT_HEADER_ROW);
                // }
            });
            this.wjHeaderGrid = this.createGridCtrl('#wjHeaderGrid', gridItemSource);
        });

        this.wjInputObj.from_warehouse_id_1.onSelectedIndexChanged();
        this.wjInputObj.from_warehouse_id_2.onSelectedIndexChanged();
    },
    methods: {
        // フィルター
        filter() {
            var filter = this.filterText.toLowerCase();
            this.wjHeaderGrid.collectionView.filter = row => {
                var result = true;
                var isMatch = false;
                // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                isMatch = filter.length == 0 ||
                (row.construction_name != null && (row.construction_name).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.product_code != null && (row.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.product_name != null && (row.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.model != null && (row.model).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.maker_name != null && (row.maker_name).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.supplier_name != null && (row.supplier_name).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.quote_quantity != null && (row.quote_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.stock_quantity != null && (row.stock_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.stock_unit != null && (row.stock_unit).toString().toLowerCase().indexOf(filter) > -1) ||
                (row.unit != null && (row.unit).toString().toLowerCase().indexOf(filter) > -1) || 
                (row.parent_name != null && (row.parent_name).toString().toLowerCase().indexOf(filter) > -1)

                if (!isMatch) {
                    result = false;
                }
                if (this.filterChk.quantity_zero == this.FLG_ON && this.rmUndefinedZero(row.total_reserve_quantity) != 0) {
                    result = false;
                }
                return result;
            };
        },
        selectMatterNo: function(sender) {
            var item = this.rmUndefinedBlank(sender.selectedItem);
            if(item !== null){
                this.searchParams.matter_name = item.matter_name;
            }else{
                this.searchParams.matter_name = '';
            }
        },
        selectMatterName: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.matter_no = item.matter_no;
            }else{
                this.searchParams.matter_no = '';
            }
        },
        // 自動計算
        autoCalc() {
            this.loading = true;
            var warehouse_1 = this.wjInputObj.from_warehouse_id_1.selectedValue;
            var warehouse_2 = this.wjInputObj.from_warehouse_id_2.selectedValue; 
            // ディープコピー    
            var list = JSON.parse(JSON.stringify(this.quantity)); 

            Object.keys(this.details).forEach(quote_detail_id => {    
                var EndFlg = false;
                var header;
                this.main.forEach(data => {
                    if (data.id == quote_detail_id) {
                        header = data;
                    }
                });
                var stockQuantity = this.bigNumberMinus(header.stock_quantity, header.sum_order_reserve_quantity);
                stockQuantity = this.bigNumberMinus(stockQuantity, header.sum_stock_reserve_quantity);
                stockQuantity = this.bigNumberMinus(stockQuantity, header.sum_keep_reserve_quantity);

                // 出荷指示がある場合は計算しない
                if (this.rmUndefinedZero(header.status) == this.FLG_OFF && stockQuantity > 0) {
                    // var qList = this.quantity;
                    // qList.forEach(rec => {
                    //     rec['actual_quantity'] = parseInt(this.rmUndefinedZero(rec['actual_quantity']))
                    //     rec['keep_quantity'] = parseInt(this.rmUndefinedZero(rec['keep_quantity']))
                    //     rec['calc_flg'] = this.FLG_ON;
                    // });

                    var act_quantity_1 = 0;
                    var act_quantity_2 = 0;
                    var kp_quantity_1 = 0;
                    var kp_quantity_2 = 0;
                    // 倉庫1(預かり品)
                    if (!EndFlg) {
                        list.forEach((quant, i) => {
                            if (quant.warehouse_id == this.details[quote_detail_id][0].from_warehouse_id 
                                && quant.product_id == this.details[quote_detail_id][0].product_id) 
                            {
                                if (stockQuantity <= quant.customer_keep_quantity) {
                                    // 受注全数分
                                    // qList[i].keep_quantity = parseInt(this.rmUndefinedZero(quant.keep_quantity)) - parseInt(this.rmUndefinedZero(sumQuantity));
                                    kp_quantity_1 = stockQuantity;
                                    // 在庫から減らす
                                    list[i].customer_keep_quantity = this.bigNumberMinus(list[i].customer_keep_quantity, stockQuantity);
                                    EndFlg = true;
                                } else if (quant.customer_keep_quantity > 0) {
                                    kp_quantity_1 = quant.customer_keep_quantity;
                                    // 在庫から減らす
                                    list[i].customer_keep_quantity = 0;
                                    // qList[i].keep_quantity = 0;
                                }
                            }
                        });
                    }
                    // 倉庫2(預かり品)
                    if (!EndFlg) {
                        list.forEach((quant, i) => {
                            if (quant.warehouse_id == this.details[quote_detail_id][1].from_warehouse_id
                                && quant.product_id == this.details[quote_detail_id][1].product_id) 
                            {
                                if (this.bigNumberMinus(stockQuantity, kp_quantity_1) <= quant.customer_keep_quantity) {
                                    // 受注全数分
                                    // qList[i].keep_quantity = parseInt(this.rmUndefinedZero(quant.keep_quantity)) - parseInt(this.rmUndefinedZero(sumQuantity));
                                    kp_quantity_2 = this.bigNumberMinus(stockQuantity, kp_quantity_1);
                                    // 在庫から減らす
                                    list[i].customer_keep_quantity = this.bigNumberMinus(list[i].customer_keep_quantity, kp_quantity_2);
                                    EndFlg = true;
                                } else if (quant.customer_keep_quantity > 0){
                                    kp_quantity_2 = quant.customer_keep_quantity;
                                    // 在庫から減らす
                                    list[i].customer_keep_quantity = 0;
                                    // qList[i].keep_quantity = 0;
                                }
                            }
                        });
                    }
                    // 倉庫1
                    if (!EndFlg) {
                        list.forEach((quant, i) => {
                            if (quant.warehouse_id == this.details[quote_detail_id][0].from_warehouse_id 
                                && quant.product_id == this.details[quote_detail_id][0].product_id) 
                            {
                                if (this.bigNumberMinus(stockQuantity, this.bigNumberPlus(kp_quantity_1, kp_quantity_2)) <= this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity)) {
                                    // 受注全数分
                                    // qList[i].actual_quantity = parseInt(this.rmUndefinedZero(quant.actual_quantity)) - parseInt(this.rmUndefinedZero(sumQuantity));
                                    act_quantity_1 = this.bigNumberMinus(stockQuantity, this.bigNumberPlus(kp_quantity_1, kp_quantity_2));
                                    // 在庫から減らす
                                    list[i].active_actual_quantity = this.bigNumberMinus(list[i].active_actual_quantity, act_quantity_1);
                                    EndFlg = true;
                                } else if (this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity) > 0) {
                                    act_quantity_1 = this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity);
                                    // 在庫から減らす
                                    list[i].active_actual_quantity = 0;
                                    // qList[i].keep_quantity = 0;
                                }
                            }
                        });
                    }
                    // 倉庫2
                    if (!EndFlg) {
                        list.forEach((quant, i) => {
                            if (quant.warehouse_id == this.details[quote_detail_id][1].from_warehouse_id 
                                && quant.product_id == this.details[quote_detail_id][1].product_id) 
                            {
                                if (this.bigNumberMinus(stockQuantity, this.bigNumberPlus(kp_quantity_1, this.bigNumberPlus(kp_quantity_2, act_quantity_1))) <= this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity)) {
                                    // 受注全数分
                                    // qList[i].actual_quantity = parseInt(this.rmUndefinedZero(quant.actual_quantity)) - parseInt(this.rmUndefinedZero(sumQuantity));
                                    act_quantity_2 = this.bigNumberMinus(stockQuantity, this.bigNumberPlus(kp_quantity_1, this.bigNumberPlus(kp_quantity_2, act_quantity_1)));
                                    // 在庫から減らす
                                    list[i].active_actual_quantity = this.bigNumberMinus(list[i].active_actual_quantity, act_quantity_2);
                                    EndFlg = true;
                                } else if (this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity) > 0) {
                                    act_quantity_2 = this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity);
                                    // 在庫から減らす
                                    list[i].active_actual_quantity = 0;
                                    // qList[i].keep_quantity = 0;
                                }
                            }
                        });
                    }

                    // 子グリッド倉庫1へ値セット
                    this.details[quote_detail_id][0].input_actual_quantity = act_quantity_1;
                    this.details[quote_detail_id][0].input_keep_quantity = kp_quantity_1;
                    this.details[quote_detail_id][0].active_actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][0].ret_active_actual_quantity, act_quantity_1);
                    this.details[quote_detail_id][0].active_keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][0].ret_active_keep_quantity, kp_quantity_1);

                    var activeQuantity = this.bigNumberPlus(this.details[quote_detail_id][0].active_actual_quantity, this.details[quote_detail_id][0].active_keep_quantity);
                    activeQuantity = this.bigNumberPlus(activeQuantity, this.details[quote_detail_id][0].stock_arrival_quantity);
                    this.details[quote_detail_id][0].active_quantity = activeQuantity;
                    // this.details[quote_detail_id][0].active_quantity = this.bigNumberPlus(this.bigNumberPlus(this.bigNumberMinus(this.details[quote_detail_id][0].inventory_quantity, this.details[quote_detail_id][0].stock_reserve_quantity), this.details[quote_detail_id][0].stock_arrival_quantity), this.bigNumberMinus(this.details[quote_detail_id][0].customer_keep_quantity, this.details[quote_detail_id][0].keep_reserve_quantity));

                    // 子グリッド倉庫2へ値セット
                    this.details[quote_detail_id][1].input_actual_quantity = act_quantity_2;
                    this.details[quote_detail_id][1].input_keep_quantity = kp_quantity_2;
                    // this.details[quote_detail_id][1].active_quantity = this.bigNumberPlus(this.bigNumberPlus(this.bigNumberMinus(this.details[quote_detail_id][1].inventory_quantity, this.details[quote_detail_id][1].stock_reserve_quantity), this.details[quote_detail_id][1].stock_arrival_quantity), this.bigNumberMinus(this.details[quote_detail_id][1].customer_keep_quantity, this.details[quote_detail_id][1].keep_reserve_quantity));
                    this.details[quote_detail_id][1].active_actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][1].ret_active_actual_quantity, act_quantity_2);
                    this.details[quote_detail_id][1].active_keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][1].ret_active_keep_quantity, kp_quantity_2);
                
                    var activeQuantity = this.bigNumberPlus(this.details[quote_detail_id][1].active_actual_quantity, this.details[quote_detail_id][1].active_keep_quantity);
                    activeQuantity = this.bigNumberPlus(activeQuantity, this.details[quote_detail_id][1].stock_arrival_quantity);
                    this.details[quote_detail_id][1].active_quantity = activeQuantity;

                    // 子グリッド倉庫3へ値セット
                    this.details[quote_detail_id][2].input_actual_quantity = 0;
                    this.details[quote_detail_id][2].input_keep_quantity = 0;
                    this.details[quote_detail_id][2].calc_flg = true;

                    this.calcParentQuantity();
                    // // 倉庫1 在庫数更新
                    // this.details[quote_detail_id][0].actual_quantity = parseInt(this.rmUndefinedZero(this.details[quote_detail_id][0].actual_quantity)) - act_quantity_1;
                    // this.details[quote_detail_id][0].keep_quantity = parseInt(this.rmUndefinedZero(this.details[quote_detail_id][0].keep_quantity)) - kp_quantity_1;
                    // this.details[quote_detail_id][0].active_quantity = parseInt(this.rmUndefinedZero(this.details[quote_detail_id][0].actual_quantity)) + parseInt(this.rmUndefinedZero(this.details[quote_detail_id][0].keep_quantity));
                    // // 倉庫2　在庫数更新
                    // this.details[quote_detail_id][1].actual_quantity = parseInt(this.rmUndefinedZero(this.details[quote_detail_id][1].actual_quantity)) - act_quantity_2;
                    // this.details[quote_detail_id][1].keep_quantity = parseInt(this.rmUndefinedZero(this.details[quote_detail_id][1].keep_quantity)) - kp_quantity_2;
                    // this.details[quote_detail_id][0].active_quantity = parseInt(this.rmUndefinedZero(this.details[quote_detail_id][1].actual_quantity)) + parseInt(this.rmUndefinedZero(this.details[quote_detail_id][1].keep_quantity));

                }
            });
            this.wjHeaderGrid.refresh();
            for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                    this.wjDetailGrid.hideDetail(i);
            } 
            this.loading = false;
        },
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            var isErr = false;
            var msg = '';
            // エラーの有無判定
            Object.keys(this.details).forEach(quote_detail_id => {
                this.details[quote_detail_id].forEach(element => {
                    if (this.rmUndefinedBlank(element.errMsg) != '') {
                        isErr = true;
                        msg = element.errMsg;
                    } 
                });
            })

            if (this.wjInputObj.from_warehouse_id_1.selectedValue == this.wjInputObj.from_warehouse_id_2.selectedValue) {
                isErr = true;
                this.errors.from_warehouse = MSG_ERROR_SAME_WAREHOUSE;
            }

            if (isErr) {
                // エラーメッセージ表示
                if (msg != '') {
                    alert(msg);
                }
                this.loading = false;
            } else {
                var params = new URLSearchParams();

                var warehouse1 = this.wjInputObj.from_warehouse_id_1.selectedItem;
                var warehouse2 = this.wjInputObj.from_warehouse_id_2.selectedItem;
                if (warehouse1 !== null && warehouse1 !== undefined) {
                    params.append('from_warehouse_id_1', this.rmUndefinedBlank(warehouse1.id));
                } else {
                    params.append('from_warehouse_id_1', 0);
                }
                if (warehouse2 !== null && warehouse2 !== undefined) {
                    params.append('from_warehouse_id_2', this.rmUndefinedBlank(warehouse2.id));
                } else {
                    params.append('from_warehouse_id_2', 0);
                }
                params.append('headerData', JSON.stringify(this.main));
                params.append('reserveList', JSON.stringify(this.details));
            
                axios.post('/stock-allocation/save', params)
                .then( function (response) {
                    if (response.data.resultSts) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/stock-allocation' + this.urlparam
                        location.href = (listUrl)
                    } else {
                        // 失敗
                        if (this.rmUndefinedBlank(response.data.resultMsg) != '') {
                            alert(response.data.resultMsg);
                        } else {
                            alert(MSG_ERROR)
                            // window.onbeforeunload = null;
                            location.reload();
                        }
                    }
                    this.loading = false
                }.bind(this))
                .catch(function (error) {
                    if (error.response.data.errors) {
                        this.loading = false
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
                }.bind(this))
            }
        },
        // グリッドの倉庫変更時
        changeWarehouse(warehouse, row){
            // 倉庫
            if(warehouse != null){
                if ((this.rmUndefinedZero(this.wjInputObj.from_warehouse_id_2.selectedValue) != 0 && this.wjInputObj.from_warehouse_id_1.selectedValue == warehouse.id)
                    || (this.rmUndefinedZero(this.wjInputObj.from_warehouse_id_2.selectedValue) != 0 && this.rmUndefinedZero(this.wjInputObj.from_warehouse_id_2.selectedValue) == warehouse.id)) {
                    alert(MSG_ERROR_SAME_WAREHOUSE);
                } else {
                    var changedFlg = false;
                    Object.keys(this.details).forEach(quote_detail_id => {
                        if (row.quote_detail_id == quote_detail_id) {
                            if(!changedFlg) {
                                this.details[quote_detail_id][2].from_warehouse_id = warehouse.id;
                                this.details[quote_detail_id][2].warehouse_name = warehouse.warehouse_name;

                                this.details[quote_detail_id][2].actual_quantity = 0;
                                this.details[quote_detail_id][2].keep_quantity = 0;
                                this.details[quote_detail_id][2].input_actual_quantity = 0;
                                this.details[quote_detail_id][2].input_keep_quantity = 0;
                                this.details[quote_detail_id][2].active_actual_quantity = 0;
                                this.details[quote_detail_id][2].active_keep_quantity = 0;
                                this.details[quote_detail_id][2].stock_arrival_quantity = 0;
                                this.details[quote_detail_id][2].order_arrival_quantity = 0;
                                this.details[quote_detail_id][2].arrival_quantity = 0;
                                this.details[quote_detail_id][2].next_arrival_date = '';
                                this.details[quote_detail_id][2].order_reserve_quantity = 0;
                                this.details[quote_detail_id][2].stock_reserve_quantity = 0;
                                this.details[quote_detail_id][2].keep_reserve_quantity = 0;
                                this.details[quote_detail_id][2].max_input_actual_quantity = 0;
                                this.details[quote_detail_id][2].max_input_keep_quantity = 0;        
                                this.details[quote_detail_id][2].active_quantity = 0;

                                this.quantity.forEach(quant => {
                                    if (warehouse.id == quant.warehouse_id && row.product_id == quant.product_id) {
                                        this.details[quote_detail_id][2].actual_quantity = quant.actual_quantity;
                                        this.details[quote_detail_id][2].keep_quantity = quant.keep_quantity;
                                        this.details[quote_detail_id][2].input_actual_quantity = 0;
                                        this.details[quote_detail_id][2].input_keep_quantity = 0;

                                        this.details[quote_detail_id][2].active_actual_quantity = quant.active_actual_quantity;
                                        this.details[quote_detail_id][2].active_keep_quantity = quant.active_keep_quantity;
                                        this.details[quote_detail_id][2].stock_arrival_quantity = quant.stock_arrival_quantity;
                                        this.details[quote_detail_id][2].order_arrival_quantity = quant.order_arrival_quantity;
                                        this.details[quote_detail_id][2].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                                        this.details[quote_detail_id][2].next_arrival_date = quant.next_arrival_date;
                                        this.details[quote_detail_id][2].order_reserve_quantity = quant.order_reserve_quantity;
                                        this.details[quote_detail_id][2].stock_reserve_quantity = quant.stock_reserve_quantity;
                                        this.details[quote_detail_id][2].keep_reserve_quantity = quant.keep_reserve_quantity;

                                        this.details[quote_detail_id][2].max_input_actual_quantity = this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity);
                                        this.details[quote_detail_id][2].max_input_keep_quantity = quant.customer_keep_quantity;

                                        var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.active_keep_quantity);
                                        activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
                
                                        this.details[quote_detail_id][2].active_quantity = activeQuantity;
                                    }
                                });

                                changedFlg = true;
                            }
                        }
                    });

                    for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                        if (this.wjDetailGrid.isDetailVisible(i)) {
                            if (i % 2 == 1) {
                                this.wjDetailGrid.hideDetail(i);
                                this.wjDetailGrid.showDetail(i);
                            }
                        }
                    }
                }
            } 
        },        
        chkQuantity(quantity, row) {
            var result = {'OkFlg': true, 'message': null};

            row.errMsg = '';
            Object.keys(this.details).forEach(quote_detail_id => {
                this.details[quote_detail_id].forEach((element, i) => {
                    if (row.quote_detail_id == element.quote_detail_id && row.from_warehouse_id == element.from_warehouse_id) {
                        this.details[quote_detail_id][i].errMsg = '';
                    } 
                });
            })
            
            // 実在庫 or 預かり在庫を超える
            if ((row.input_actual_quantity != 0 && row.input_actual_quantity > row.max_input_actual_quantity) || (row.input_keep_quantity != 0 && row.input_keep_quantity > row.max_input_keep_quantity)) {
                result.OkFlg = false;
                result.message = MSG_ERROR_STOCK_OVER;
                row.errMsg = MSG_ERROR_STOCK_OVER;
            }

            if (row.input_actual_quantity < 0 || row.input_keep_quantity < 0) {
                if (row.input_actual_quantity < row.min_input_actual_quantity || row.input_keep_quantity < row.min_input_keep_quantity) {
                    result.OkFlg = false;
                    result.message = MSG_ERROR_MIN_RESERVE_VALIDITY;
                    row.errMsg = MSG_ERROR_MIN_RESERVE_VALIDITY;
                }
            }

            // 引当数が管理数単位かどうか
            if (quantity > 0 && this.bigNumberMod(quantity, row.min_quantity) != 0) {
                result.OkFlg = false;
                result.message = MSG_ERROR_STOCK_QUANTITY_MULTIPLE;
                row.errMsg = MSG_ERROR_STOCK_QUANTITY_MULTIPLE;
            }

            // if (quantity < 0) {
            //     result.OkFlg = false;
            //     result.message = '0未満は入力できません';
            //     row.errMsg = '0未満は入力できません';
            // }

            // var regexp = new RegExp(/^[-]?([1-9]\d*|0)(\.\d+)?$/);
            // if (!regexp.test(quantity)) {
            //     result.OkFlg = false;
            //     result.message = MSG_ERROR_NOT_NUMBER;
            //     row.errMsg =  MSG_ERROR_NOT_NUMBER;
            // }

            Object.keys(this.details).forEach(quote_detail_id => {
                this.details[quote_detail_id].forEach((element, i) => {
                    if (row.quote_detail_id == element.quote_detail_id && row.from_warehouse_id == element.from_warehouse_id) {
                        this.details[quote_detail_id][i].errMsg = row.errMsg;
                    } 
                });
            })
            

            return result;
        },
        setParameter(row) {
            var idx = (row.seq_no - 1);
            Object.keys(this.details).forEach(quote_detail_id => {
                if (quote_detail_id == row.quote_detail_id) {
                    this.details[quote_detail_id][idx].input_actual_quantity = row.input_actual_quantity; 
                    this.details[quote_detail_id][idx].input_keep_quantity = row.input_keep_quantity;
                    this.details[quote_detail_id][idx].active_actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].ret_active_actual_quantity, this.bigNumberMinus(row.input_actual_quantity, row.stock_reserve_quantity));
                    this.details[quote_detail_id][idx].active_keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].ret_active_keep_quantity, this.bigNumberMinus(row.input_keep_quantity, row.keep_reserve_quantity));

                    var activeQuantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_actual_quantity, this.details[quote_detail_id][idx].active_keep_quantity);
                    activeQuantity = this.bigNumberPlus(activeQuantity, this.details[quote_detail_id][idx].stock_arrival_quantity);

                    this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                    
                    // 更新 
                    // this.wjDetailGrid.detail_grid.itemsSource = this.details[quote_detail_id];
                }
            });
            // for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
            //     if (this.wjDetailGrid.isDetailVisible(i)) {
            //         if (i % 2 == 1) {
            //             this.wjDetailGrid.hideDetail(i);
            //             this.wjDetailGrid.showDetail(i);
            //         }
            //     }
            // }

        },
        // 【グリッド】作成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowSorting: false,
                keyActionEnter: wjGrid.KeyAction.None,
            });

            // 子グリッド定義
            var detailCtrl = new wjDetail.FlexGridDetailProvider(gridCtrl, {
                isAnimated: false,
                maxHeight: 280,
                // detailVisibilityMode: wjDetail.DetailVisibilityMode.ExpandMulti,

                createDetailCell: function (row) {
                    var cell = document.createElement('div');
                    gridCtrl.hostElement.appendChild(cell);
                    var detailGrid = new wjMultiRow.MultiRow(cell, {
                        headersVisibility: wjGrid.HeadersVisibility.Column,
                        autoGenerateColumns: false,
                        itemsSource: this.getDetails(row.dataItem),
                        showSort: false,
                        allowSorting: false,
                        layoutDefinition: this.reserveLayout,
                        validateEdits: true,
                    });

                    var path = row.dataItem.path;

                    if(this.wjDetailGrid.cge_warehouse_name[path] !== undefined){
                        // this.wjDetailGrid.cge_warehouse_name.control.dispose();
                        this.wjDetailGrid.cge_warehouse_name[path].control.dispose();
                    }
                    var cgeWarehousename = this.createGridAutoComplete(detailGrid, 'warehouse_name', 2, 1, 2);
                    this.wjDetailGrid.cge_warehouse_name[path] = cgeWarehousename;

                    // インスタンスの再生成は必須
                    // detailCtrl.cge_warehouse_name = cgeWarehousename;

                    detailGrid.itemFormatter = function(panel, r, c, cell) {
                        // 列ヘッダ中央寄せ
                        if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                            cell.style.textAlign = 'center';
                            var col = gridCtrl.getBindingColumn(panel, r, c);
                            switch(col.name) {
                                case 'actual_reserve_validity':
                                    if (r % 2 == 0) {
                                        var str = cell.innerHTML;
                                        // var title = str.substr(0, 1) + '<br/>' +  str.substr(1, str.length);
                                        // cell.innerHTML = title;
                                        // 表示加工
                                        cell.innerHTML ='<div style="float: left;">' + 
                                                            str.substr(0, 1) +
                                                        '</div>';

                                        cell.innerHTML += '<div style="float: right;">' +
                                                            str.substr(1, str.length) +
                                                        '</div>';
                                    }
                                    break;
                                case 'keep_reserve_validity':
                                    if (r % 2 == 0) {
                                        var str = cell.innerHTML;
                                        // var title = str.substr(0, 1) + '<br/>' +  str.substr(1, str.length);
                                        // cell.innerHTML = title;
                                        // 表示加工
                                        cell.innerHTML ='<div style="float: left;">' + 
                                                            str.substr(0, 1) +
                                                        '</div>';

                                        cell.innerHTML += '<div style="float: right;">' +
                                                            str.substr(1, str.length) +
                                                        '</div>';
                                    }
                                    break;
                            }
                        }else if (panel.cellType == wjGrid.CellType.Cell) {
                            // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                            var col = detailGrid.getBindingColumn(panel, r, c);
                            var dataItem = panel.rows[r].dataItem;
                            if (dataItem != undefined) {
                                switch(col.name){
                                    case 'input_actual_quantity':
                                        // エラー時のスタイル設定
                                        // var validResult = this.chkQuantity(row.input_actual_quantity, row);
                                        if (this.rmUndefinedBlank(dataItem.errMsg) != '') {
                                            wjCore.addClass(cell, "wj-grid-invalid");
                                        } else {
                                            wjCore.removeClass(cell, "wj-grid-invalid");
                                        }

                                        cell.style.textAlign = 'right';
                                        break;
                                    case 'input_keep_quantity':
                                        // エラー時のスタイル設定
                                        // var validResult = this.chkQuantity(row.input_keep_quantity, row);
                                        if (this.rmUndefinedBlank(dataItem.errMsg) != '') {
                                            wjCore.addClass(cell, "wj-grid-invalid");
                                        } else {
                                            wjCore.removeClass(cell, "wj-grid-invalid");
                                        }

                                        cell.style.textAlign = 'right';
                                        break;
                                    case 'actual_reserve_validity':
                                        // 表示加工
                                        cell.innerHTML ='<div style="float: left;">' + 
                                                            this.$options.filters.comma_format(dataItem.actual_reserve_validity) +
                                                        '</div>';

                                        cell.innerHTML += '<div style="float: right;">' +
                                                            this.$options.filters.comma_format(dataItem.all_actual_reserve) +
                                                        '</div>';
                                        // cell.style.textAlign = 'right';
                                        cell.style.backgroundColor = '#ccc';
                                        break;
                                    case 'keep_reserve_validity':
                                        // 表示加工
                                        cell.innerHTML ='<div style="float: left;">' + 
                                                            this.$options.filters.comma_format(dataItem.keep_reserve_validity) +
                                                        '</div>';

                                        cell.innerHTML += '<div style="float: right;">' +
                                                            this.$options.filters.comma_format(dataItem.all_keep_reserve) +
                                                        '</div>';
                                        // cell.style.textAlign = 'right';
                                        cell.style.backgroundColor = '#ccc';
                                        break;
                                    case 'actual_quantity':
                                    case 'keep_quantity':
                                    case 'active_actual_quantity':
                                    case 'active_keep_quantity':
                                    case 'active_quantity':
                                    case 'arrival_quantity':
                                    case 'next_arrival_date':
                                    case 'unit':
                                    case 'actual_unit':
                                    case 'keep_unit':
                                        // 数量
                                        cell.style.textAlign = 'right';
                                        cell.style.backgroundColor = '#ccc';
                                        break;
                                    case 'warehouse_name':
                                        cell.style.textAlign = 'left';
                                        if (dataItem.seq_no != 3) {
                                            cell.style.backgroundColor = '#ccc';
                                            cell.selected = false;
                                        }
                                        break;
                                }      
                            }                  
                        }
                    }.bind(this);

                    // セル編集後イベント：行内のデータ自動セット
                    detailGrid.cellEditEnded.addHandler(function(s, e) {
                        detailGrid.beginUpdate();
                        var row = s.collectionView.currentItem;
                        var col = detailGrid.getBindingColumn(e.panel, e.row, e.col);
                        var path = row.path;

                        switch (col.name) {
                            case 'warehouse_name':
                                if (row.seq_no == 3) {
                                    var warehouse = this.wjDetailGrid.cge_warehouse_name[path].control.selectedItem;
                                    if (warehouse != null && warehouse.id != row.from_warehouse_id) {
                                        this.changeWarehouse(warehouse, row);
                                    }
                                }
                                break;
                            case 'input_actual_quantity':
                                // 通常在庫引当
                                var validResult = this.chkQuantity(row.input_actual_quantity, row);
                                if (validResult.OkFlg) {
                                    this.setParameter(row);
                                    this.calcParentQuantity();
                                    // this.detailGridReset(ht.row + 1, row);

                                    var isAllOk = true;
                                    Object.keys(this.details).forEach(quote_detail_id => {
                                        this.details[quote_detail_id].forEach((element, i) => {
                                            if (this.details[quote_detail_id][i].errMsg != "") {
                                                isAllOk = false;
                                            }
                                        });
                                    })

                                    if (isAllOk) {
                                        this.hasError = false;
                                    }
                                } else {
                                    this.hasError = true;
                                }
                                break;
                            case 'input_keep_quantity':
                                // 預かり品引当
                                var validResult = this.chkQuantity(row.input_keep_quantity, row);
                                if (validResult.OkFlg) {
                                    this.setParameter(row);
                                    this.calcParentQuantity();
                                    // this.detailGridReset(ht.row + 1, row);
                                    
                                    var isAllOk = true;
                                    Object.keys(this.details).forEach(quote_detail_id => {
                                        this.details[quote_detail_id].forEach((element, i) => {
                                            if (this.details[quote_detail_id][i].errMsg != "") {
                                                isAllOk = false;
                                            }
                                        });
                                    })

                                    if (isAllOk) {
                                        this.hasError = false;
                                    }
                                } else {
                                    this.hasError = true;
                                }
                                break;
                        }
                        detailGrid.endUpdate();
                        // detailGrid.collectionView.commitEdit();
                    }.bind(this));
                    
                    // セル編集直前のイベント：コンボをセットする
                    detailGrid.beginningEdit.addHandler(function (s, e) {
                        var col = detailGrid.getBindingColumn(e.panel, e.row, e.col);
                        var row = s.collectionView.currentItem;
                        var path = row.path;
                        switch(col.name) {
                            case 'warehouse_name':       
                                // 1,2行目にコンボボックスを表示させない
                                if(row.seq_no == 3){
                                    // this.wjDetailGrid.cge_warehouse_name[path].control.isDisabled = false;
                                    // this.wjDetailGrid.cge_warehouse_name[path].changeItemsSource(this.warehouselist); 
                                    // 倉庫1,倉庫2を選択肢から除外
                                    var tmpWarehouse = this.warehouselist;
                                    var selectedWarehouse = null;
                                    if (this.wjInputObj.from_warehouse_id_1.selectedValue) {
                                        tmpWarehouse = [];
                                        tmpWarehouse.push([]);
                                        for(var key in this.warehouselist) {
                                            if (this.wjInputObj.from_warehouse_id_1.selectedValue != this.warehouselist[key].id
                                                && this.wjInputObj.from_warehouse_id_2.selectedValue != this.warehouselist[key].id) {
                                                tmpWarehouse.push(this.warehouselist[key]);

                                                if (this.warehouselist[key].id == row.from_warehouse_id) {
                                                    selectedWarehouse = this.warehouselist[key];
                                                }
                                            }
                                        }             
                                    }
                                    this.wjDetailGrid.cge_warehouse_name[path].control.isDisabled = false;
                                    this.wjDetailGrid.cge_warehouse_name[path].control.itemsSource = tmpWarehouse;
                                    this.wjDetailGrid.cge_warehouse_name[path].control.selectedValue = row.from_warehouse_id;                               
                                }else{
                                    this.wjDetailGrid.cge_warehouse_name[path].control.isDisabled = true;
                                    // e.cancel = true;
                                }
                                break;
                        }
                    }.bind(this));
                    // cell.parentElement.removeChild(cell);

                    this.wjDetailGrid.detail_grid = detailGrid;

                    return cell;
                }.bind(this),

                // 奇数行の展開ボタン削除
                rowHasDetail: function (row) {
                    return row.recordIndex % 2 == 1;
                }
            })
            // プロバイダーにカスタムグリッドエディタのプロパティ追加
            detailCtrl.cge_warehouse_name = {};
            // プロバイダーにインスタンスをセットする
            this.wjDetailGrid = detailCtrl;

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

            gridCtrl.itemFormatter = function(panel, r, c, cell) {                
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';

                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    switch(col.name) {
                        // 列名改行
                        case 'total_reserve_quantity':
                            var str = cell.innerHTML;
                            var title = str.substr(0, 4) + '<br/>' +  str.substr(4, str.length);
                            cell.innerHTML = title;
                            break;
                        case 'reserve_quantity_1':
                            var str = cell.innerHTML;
                            var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                            cell.innerHTML = title;
                            break;
                        case 'active_quantity_1':
                            var str = cell.innerHTML;
                            var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                            cell.innerHTML = title;
                            break;
                        case 'reserve_quantity_2':
                            var str = cell.innerHTML;
                            var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                            cell.innerHTML = title;
                            break;
                        case 'active_quantity_2':
                            var str = cell.innerHTML;
                            var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                            cell.innerHTML = title;
                            break;
                    }
                    
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var dataItem = panel.rows[r].dataItem;

                    cell.style.backgroundColor = '';
                    cell.style.textAlign = '';
                    cell.style.color = '';
                    switch(col.name){
                        case 'product_code': 
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].product_code);
                                cell.style.textAlign = 'left';
                            }
                            break;
                        case 'parent_name': 
                            cell.innerHTML = '';
                            if (dataItem != null) {
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].parent_name);
                                cell.style.textAlign = 'center';
                            }
                            break;
                        case 'product_name': 
                        case 'model': 
                        case 'maker_name':
                        case 'supplier_name':  
                            cell.style.textAlign = 'left';
                            break;
                        case 'quote_quantity':
                        case 'stock_quantity':
                            cell.style.textAlign = 'right';
                            break;
                        case 'total_reserve_quantity':
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].total_reserve_quantity);
                                cell.style.textAlign = 'right';
                            }
                            break;
                        case 'reserve_quantity_1':
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].reserve_quantity_1);
                                cell.style.textAlign = 'right';
                            }
                            break;
                        case 'active_quantity_1':
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].active_quantity_1);
                                cell.style.textAlign = 'right';
                            }
                            break;
                        case 'reserve_quantity_2':
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].reserve_quantity_2);
                                cell.style.textAlign = 'right';
                            }
                            break;
                        case 'active_quantity_2':
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].active_quantity_2);
                                cell.style.textAlign = 'right';
                            }
                            break;
                        case 'construction_name': 
                            if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].construction_name);
                                cell.style.textAlign = 'center';
                            }
                            break;
                        case 'unit':
                        case 'stock_unit':                        
                            cell.style.textAlign = 'center';
                            break;
                    }
                    if (this.rmUndefinedZero(dataItem) != this.FLG_OFF) {
                        if (this.rmUndefinedZero(dataItem.status) == this.FLG_ON) {
                            cell.style.backgroundColor = '#ffd900';
                        }
                    }
                }
            }.bind(this);

            // キーダウンイベント
            // gridCtrl.hostElement.addEventListener('keydown', function (e) {
            //     // 読み取り専用セルスキップ
            //     this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            // }.bind(this), true);            

            return gridCtrl;
        },
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },
        createGridAutoComplete(gridCtrl, name, multirows, row, rowspan){
            return new CustomGridEditor(gridCtrl, name, wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "warehouse_name",
                displayMemberPath: "warehouse_name",
                itemsSource: this.warehouselist,
                selectedValuePath: "id",
                isRequired: false,
                // textChanged: this.setTextChanged,
                minLength: 1,
            }, multirows, row, rowspan);
            //     return new CustomGridEditor(gridCtrl, name, wjcInput.AutoComplete, {
            //         delay: 50,
            //         searchMemberPath: "warehouse_name",
            //         displayMemberPath: "warehouse_name",
            //         itemsSource: this.warehouselist,
            //         selectedValuePath: "id",
            //         minLength: 1,
            //         // selectedIndex: -1,
            //         isRequired: false,
            //         maxItems: this.warehouselist.length,
            //     }, multirows, row, rowspan);
        },
        changeIdxWarehouse: function(sender){
            // 各子グリッドの倉庫1へセット
            if (this.wjDetailGrid !== null) {
                // if (this.wjDetailGrid.isDetailVisible(1)) {
                //     for(var i = 0; i < this.wjHeaderGrid.rows.length; i++) { 
                //         this.wjDetailGrid.hideDetail(i);
                //     }
                // }
                for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                    if (this.wjDetailGrid.isDetailVisible(i)) {
                        if (i % 2 == 1) {
                            this.wjDetailGrid.hideDetail(i);
                            // this.wjDetailGrid.showDetail(i);
                        }
                    }
                }
            }
            // 得意先を変更したら案件名を絞り込む
            var tmpWarehouse = this.warehouselist;
            if (sender.selectedValue) {
                tmpWarehouse = [];
                for(var key in this.warehouselist) {
                    if (sender.selectedValue != this.warehouselist[key].id) {
                        tmpWarehouse.push(this.warehouselist[key]);
                    }
                }             
            }
            this.wjInputObj.from_warehouse_id_2.itemsSource = tmpWarehouse;
            this.wjInputObj.from_warehouse_id_2.selectedIndex = -1;
            
            if (this.wjHeaderGrid != null) {
                this.calcDetailQuantity();
                this.calcParentQuantity();
            }

            // var items = sender.selectedItem;
            // if (items !== undefined && items !== null) {
            //     Object.keys(this.details).forEach(quote_detail_id => {
            //         this.details[quote_detail_id][0].from_warehouse_id = items.id;
            //         this.details[quote_detail_id][0].warehouse_name = items.warehouse_name;
            //         this.details[quote_detail_id][0].input_actual_quantity = 0;
            //         this.details[quote_detail_id][0].input_keep_quantity = 0;
            //         this.details[quote_detail_id][0].actual_quantity = 0;
            //         this.details[quote_detail_id][0].keep_quantity = 0;
            //         this.details[quote_detail_id][0].actual_all_stock = 0;
            //         this.details[quote_detail_id][0].keep_all_stock = 0;
            //         this.details[quote_detail_id][0].arrival_quantity = 0;
            //     });
            // } else {
            //     Object.keys(this.details).forEach(quote_detail_id => {
            //         this.details[quote_detail_id][0].from_warehouse_id = 0;
            //         this.details[quote_detail_id][0].warehouse_name = '';
            //         this.details[quote_detail_id][0].input_actual_quantity = 0;
            //         this.details[quote_detail_id][0].input_keep_quantity = 0;
            //         this.details[quote_detail_id][0].actual_quantity = 0;
            //         this.details[quote_detail_id][0].keep_quantity = 0;
            //         this.details[quote_detail_id][0].actual_all_stock = 0;
            //         this.details[quote_detail_id][0].keep_all_stock = 0;
            //         this.details[quote_detail_id][0].arrival_quantity = 0;
            //     });
            // }
        },
        changeIdxWarehouse2: function(sender){
            // 各子グリッドの倉庫2へセット
            if (this.wjDetailGrid !== null) {
                // if (this.wjDetailGrid.isDetailVisible(1)) {
                //     for(var i = 0; i < this.wjHeaderGrid.rows.length; i++) { 
                //         this.wjDetailGrid.hideDetail(i);
                //     }
                // }
                for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                    if (this.wjDetailGrid.isDetailVisible(i)) {
                        if (i % 2 == 1) {
                            this.wjDetailGrid.hideDetail(i);
                            // this.wjDetailGrid.showDetail(i);
                        }
                    }
                }
            }


            if (this.wjHeaderGrid != null) {
                this.calcDetailQuantity();
                this.calcParentQuantity();
            }
            // var items = sender.selectedItem;
            // if (items !== undefined && items !== null) {
            //     Object.keys(this.details).forEach(quote_detail_id => {
            //         this.details[quote_detail_id][1].from_warehouse_id = items.id;
            //         this.details[quote_detail_id][1].warehouse_name = items.warehouse_name;
            //         this.details[quote_detail_id][1].input_actual_quantity = 0;
            //         this.details[quote_detail_id][1].input_keep_quantity = 0;
            //         this.details[quote_detail_id][1].actual_quantity = 0;
            //         this.details[quote_detail_id][1].keep_quantity = 0;
            //         this.details[quote_detail_id][1].actual_all_stock = 0;
            //         this.details[quote_detail_id][1].keep_all_stock = 0;
            //         this.details[quote_detail_id][1].arrival_quantity = 0;
            //     });
            // } else {
            //     Object.keys(this.details).forEach(quote_detail_id => {
            //         this.details[quote_detail_id][1].from_warehouse_id = 0;
            //         this.details[quote_detail_id][1].warehouse_name = '';
            //         this.details[quote_detail_id][1].input_actual_quantity = 0;
            //         this.details[quote_detail_id][1].input_keep_quantity = 0;
            //         this.details[quote_detail_id][1].actual_quantity = 0;
            //         this.details[quote_detail_id][1].keep_quantity = 0;
            //         this.details[quote_detail_id][1].actual_all_stock = 0;
            //         this.details[quote_detail_id][1].keep_all_stock = 0;
            //         this.details[quote_detail_id][1].arrival_quantity = 0;
            //     });
            // }
        },
        // 詳細行の一括展開・縮小
        OpenCloseDetail: function(row) {
            if (this.wjFlexDetailSetting.isDetailVisible(1)) {
                for(var i = 0; i < this.wjShippingGrid.rows.length; i++) { 
                    this.wjFlexDetailSetting.hideDetail(i);
                }
            }else {
                for(var i = 0; i < this.wjShippingGrid.rows.length; i++) { 
                    this.wjFlexDetailSetting.showDetail(i);
                }
            }
        },
        /**********************************************
         * 展開時にiemsSourceにセットされる
         **********************************************/
        getDetails(row) {
            var detailArr = [];
            Object.keys(this.details).forEach(quote_detail_id => {
                if (row.id == quote_detail_id) {
                    this.details[quote_detail_id].forEach((element, idx) => {
                        // if (this.wjInputObj.from_warehouse_id_1.selectedValue != null && idx == 0) {
                        //     // 引当倉庫１の在庫数セット
                        //     if (this.rmUndefinedBlank(this.details[quote_detail_id][idx].warehouse_name) == '') {
                        //         this.details[quote_detail_id][idx].warehouse_name = this.wjInputObj.from_warehouse_id_1.text;
                        //         this.details[quote_detail_id][idx].from_warehouse_id = this.wjInputObj.from_warehouse_id_1.selectedValue;
                        //     }
                        //     this.quantity.forEach(quant => {
                        //         if (row.product_id == quant.product_id && this.details[quote_detail_id][idx].from_warehouse_id == quant.warehouse_id) {
                        //             this.details[quote_detail_id][idx].actual_quantity = quant.actual_quantity;
                        //             this.details[quote_detail_id][idx].keep_quantity = quant.keep_quantity;

                        //             this.details[quote_detail_id][idx].active_actual_quantity = quant.active_actual_quantity;
                        //             this.details[quote_detail_id][idx].active_keep_quantity = quant.active_keep_quantity;
                        //             this.details[quote_detail_id][idx].stock_arrival_quantity = quant.stock_arrival_quantity;
                        //             this.details[quote_detail_id][idx].order_arrival_quantity = quant.order_arrival_quantity;
                        //             this.details[quote_detail_id][idx].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                        //             this.details[quote_detail_id][idx].next_arrival_date = quant.next_arrival_date;
                        //             this.details[quote_detail_id][idx].order_reserve_quantity = quant.order_reserve_quantity;
                        //             this.details[quote_detail_id][idx].stock_reserve_quantity = quant.stock_reserve_quantity;
                        //             this.details[quote_detail_id][idx].keep_reserve_quantity = quant.keep_reserve_quantity;

                        //             var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.active_keep_quantity);
                        //             activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
            
                        //             this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                        //         }
                        //     });
                        //     // 引当数セット
                        //     this.reservelist.forEach(reserve => {
                        //         if (reserve.quote_detail_id == quote_detail_id) {
                        //             if (row.product_id == reserve.product_id && element.from_warehouse_id == reserve.from_warehouse_id) {                                        
                        //                 if (reserve.stock_flg == this.STOCK_FLG) {
                        //                     this.details[quote_detail_id][idx].stock_id = reserve.reserve_id;
                        //                     if (!this.details[quote_detail_id][idx].stock_reserve_set_flg) {
                        //                         this.details[quote_detail_id][idx].input_actual_quantity = reserve.reserve_quantity;
                        //                     }
                        //                     this.details[quote_detail_id][idx].active_actual_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_actual_quantity, reserve.reserve_quantity);
                        //                     this.details[quote_detail_id][idx].active_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_quantity, reserve.reserve_quantity);
                        //                     // if (reserve.finish_flg == this.FLG_OFF) {
                        //                     //     this.details[quote_detail_id][idx].actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].actual_quantity, reserve.reserve_quantity);
                        //                     // }
                        //                     this.details[quote_detail_id][idx].stock_reserve_set_flg = true;
                        //                 } else if (reserve.stock_flg == this.KEEP_FLG) {
                        //                     this.details[quote_detail_id][idx].keep_id = reserve.reserve_id;
                        //                     if (!this.details[quote_detail_id][idx].keep_reserve_set_flg) {
                        //                         this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                        //                     }
                        //                     this.details[quote_detail_id][idx].active_keep_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_keep_quantity, reserve.reserve_quantity);
                        //                     this.details[quote_detail_id][idx].active_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_quantity, reserve.reserve_quantity);
                        //                     // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                        //                     // if (reserve.finish_flg == this.FLG_OFF) {
                        //                     //     this.details[quote_detail_id][idx].keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].keep_quantity, reserve.reserve_quantity);
                        //                     // }
                        //                 }
                        //                 this.details[quote_detail_id][idx].keep_reserve_set_flg = true;
                        //             }
                        //         }
                        //     });
                        // }
                        // if (this.wjInputObj.from_warehouse_id_2.selectedValue != null && idx == 1) {
                        //     // 引当在庫２の在庫数セット
                        //     this.details[quote_detail_id][idx].warehouse_name = this.wjInputObj.from_warehouse_id_2.text;
                        //     this.details[quote_detail_id][idx].from_warehouse_id = this.wjInputObj.from_warehouse_id_2.selectedValue;
                        //     this.quantity.forEach(quant => {
                        //         if (row.product_id == quant.product_id && this.details[quote_detail_id][idx].from_warehouse_id == quant.warehouse_id) {
                        //             this.details[quote_detail_id][idx].actual_quantity = quant.actual_quantity;
                        //             this.details[quote_detail_id][idx].keep_quantity = quant.keep_quantity;

                        //             this.details[quote_detail_id][idx].active_actual_quantity = quant.active_actual_quantity;
                        //             this.details[quote_detail_id][idx].active_keep_quantity = quant.active_keep_quantity;
                        //             this.details[quote_detail_id][idx].stock_arrival_quantity = quant.stock_arrival_quantity;
                        //             this.details[quote_detail_id][idx].order_arrival_quantity = quant.order_arrival_quantity;
                        //             this.details[quote_detail_id][idx].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                        //             this.details[quote_detail_id][idx].next_arrival_date = quant.next_arrival_date;
                        //             this.details[quote_detail_id][idx].order_reserve_quantity = quant.order_reserve_quantity;
                        //             this.details[quote_detail_id][idx].stock_reserve_quantity = quant.stock_reserve_quantity;
                        //             this.details[quote_detail_id][idx].keep_reserve_quantity = quant.keep_reserve_quantity;

                        //             var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.active_keep_quantity);
                        //             activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
            
                        //             this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                        //         }
                        //     });
                        //     // 引当数セット
                        //     this.reservelist.forEach(reserve => {
                        //         if (reserve.quote_detail_id == quote_detail_id) {
                        //             if (row.product_id == reserve.product_id && element.from_warehouse_id == reserve.from_warehouse_id) {
                        //                 if (reserve.stock_flg == this.STOCK_FLG) {
                        //                     this.details[quote_detail_id][idx].stock_id = reserve.reserve_id;
                        //                     if (!this.details[quote_detail_id][idx].stock_reserve_set_flg) {
                        //                         this.details[quote_detail_id][idx].input_actual_quantity = reserve.reserve_quantity;
                        //                     }
                        //                     this.details[quote_detail_id][idx].active_actual_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_actual_quantity, reserve.reserve_quantity);
                        //                     this.details[quote_detail_id][idx].active_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_quantity, reserve.reserve_quantity);
                        //                     // if (reserve.finish_flg == this.FLG_OFF) {
                        //                     //     this.details[quote_detail_id][idx].actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].actual_quantity, reserve.reserve_quantity);
                        //                     // }
                        //                     this.details[quote_detail_id][idx].stock_reserve_set_flg = true;
                        //                 } else if (reserve.stock_flg == this.KEEP_FLG) {
                        //                     this.details[quote_detail_id][idx].keep_id = reserve.reserve_id;
                        //                     if (!this.details[quote_detail_id][idx].keep_reserve_set_flg) {
                        //                         this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                        //                     }
                        //                     this.details[quote_detail_id][idx].active_keep_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_keep_quantity, reserve.reserve_quantity);
                        //                     this.details[quote_detail_id][idx].active_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_quantity, reserve.reserve_quantity);
                        //                     // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                        //                     // if (reserve.finish_flg == this.FLG_OFF) {
                        //                     //     this.details[quote_detail_id][idx].keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].keep_quantity, reserve.reserve_quantity);
                        //                     // }
                        //                     this.details[quote_detail_id][idx].keep_reserve_set_flg = true;
                        //                 }
                        //             }
                        //         }
                        //     });
                        // }
                        // if (idx == 2 && this.details[quote_detail_id][idx].from_warehouse_id != null) { 
                        //     // 引当数セット
                        //     this.reservelist.forEach(reserve => {
                        //         if (reserve.quote_detail_id == quote_detail_id) {
                        //             if (row.product_id == reserve.product_id && (this.wjInputObj.from_warehouse_id_1.selectedValue != reserve.from_warehouse_id && this.wjInputObj.from_warehouse_id_2.selectedValue != reserve.from_warehouse_id)) {
                        //                 this.details[quote_detail_id][idx].reserve_set_flg = true;
                        //                 this.details[quote_detail_id][idx].from_warehouse_id = reserve.from_warehouse_id;
                        //                 this.details[quote_detail_id][idx].warehouse_name = reserve.warehouse_name;
                        //                 if (reserve.stock_flg == this.STOCK_FLG) {
                        //                     this.details[quote_detail_id][idx].stock_id = reserve.reserve_id;
                        //                     if (!this.details[quote_detail_id][idx].stock_reserve_set_flg) {
                        //                         this.details[quote_detail_id][idx].input_actual_quantity = reserve.reserve_quantity;
                        //                     }
                        //                     this.details[quote_detail_id][idx].active_actual_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_actual_quantity, reserve.reserve_quantity);
                        //                     this.details[quote_detail_id][idx].active_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_quantity, reserve.reserve_quantity);
                        //                     // if (reserve.finish_flg == this.FLG_OFF) {
                        //                     //     this.details[quote_detail_id][idx].actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].actual_quantity, reserve.reserve_quantity);
                        //                     // }
                        //                     this.details[quote_detail_id][idx].stock_reserve_set_flg = true;
                        //                 } else if (reserve.stock_flg == this.KEEP_FLG) {
                        //                     this.details[quote_detail_id][idx].keep_id = reserve.reserve_id;
                        //                     if (!this.details[quote_detail_id][idx].keep_reserve_set_flg) {
                        //                         this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                        //                     }
                        //                     this.details[quote_detail_id][idx].active_keep_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_keep_quantity, reserve.reserve_quantity);
                        //                     this.details[quote_detail_id][idx].active_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].active_quantity, reserve.reserve_quantity);
                        //                     // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                        //                     // if (reserve.finish_flg == this.FLG_OFF) {
                        //                     //     this.details[quote_detail_id][idx].keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].keep_quantity, reserve.reserve_quantity);
                        //                     // }
                        //                     this.details[quote_detail_id][idx].keep_reserve_set_flg = true;
                        //                 }
                        //             }
                        //         }
                        //     });                           
                        //     // 引当在庫３の在庫数セット
                        //     this.quantity.forEach(quant => {
                        //         if (row.product_id == quant.product_id && this.details[quote_detail_id][idx].from_warehouse_id == quant.warehouse_id) {
                        //             this.details[quote_detail_id][idx].actual_quantity = quant.actual_quantity;
                        //             this.details[quote_detail_id][idx].keep_quantity = quant.keep_quantity;

                        //             this.details[quote_detail_id][idx].active_actual_quantity = quant.active_actual_quantity;
                        //             this.details[quote_detail_id][idx].active_keep_quantity = quant.active_keep_quantity;
                        //             this.details[quote_detail_id][idx].stock_arrival_quantity = quant.stock_arrival_quantity;
                        //             this.details[quote_detail_id][idx].order_arrival_quantity = quant.order_arrival_quantity;
                        //             this.details[quote_detail_id][idx].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                        //             this.details[quote_detail_id][idx].next_arrival_date = quant.next_arrival_date;
                        //             this.details[quote_detail_id][idx].order_reserve_quantity = quant.order_reserve_quantity;
                        //             this.details[quote_detail_id][idx].stock_reserve_quantity = quant.stock_reserve_quantity;
                        //             this.details[quote_detail_id][idx].keep_reserve_quantity = quant.keep_reserve_quantity;

                        //             var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.active_keep_quantity);
                        //             activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
            
                        //             this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                        //         }
                        //     });
                        // }
                        // element.actual_unit = (parseInt(this.rmUndefinedZero(element.input_actual_quantity)) * parseInt(this.rmUndefinedZero(row.order_lot_quantity))) + row.unit.toString();
                        this.details[quote_detail_id][idx].actual_unit = row.unit;
                        // element.keep_unit = (parseInt(this.rmUndefinedZero(element.input_keep_quantity)) * parseInt(this.rmUndefinedZero(row.order_lot_quantity))) + row.unit.toString();
                        this.details[quote_detail_id][idx].keep_unit = row.unit;
                        this.details[quote_detail_id][idx].errMsg = '';
                        var itemsSource = [];

                        // 子グリッドデータセット
                        itemsSource = {
                            id: element.id,
                            quote_detail_id: element.quote_detail_id,
                            warehouse_name: element.warehouse_name,
                            from_warehouse_id: element.from_warehouse_id,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            customer_id: element.customer_id,
                            input_actual_quantity: this.details[quote_detail_id][idx].input_actual_quantity,
                            input_keep_quantity: this.details[quote_detail_id][idx].input_keep_quantity,
                            actual_quantity: this.details[quote_detail_id][idx].actual_quantity,
                            active_actual_quantity: this.details[quote_detail_id][idx].active_actual_quantity,
                            actual_reserve_validity: this.details[quote_detail_id][idx].actual_reserve_validity,
                            all_actual_reserve: this.details[quote_detail_id][idx].all_actual_reserve,
                            keep_reserve_validity: this.details[quote_detail_id][idx].keep_reserve_validity,
                            all_keep_reserve: this.details[quote_detail_id][idx].all_keep_reserve,
                            actual_unit:  this.details[quote_detail_id][idx].actual_unit,
                            keep_quantity:  this.details[quote_detail_id][idx].keep_quantity,
                            active_keep_quantity: this.details[quote_detail_id][idx].active_keep_quantity,
                            keep_unit:  this.details[quote_detail_id][idx].keep_unit,
                            active_quantity: this.details[quote_detail_id][idx].active_quantity,
                            arrival_quantity: this.details[quote_detail_id][idx].arrival_quantity,
                            next_arrival_date: this.details[quote_detail_id][idx].next_arrival_date,
                            stock_arrival_quantity: this.details[quote_detail_id][idx].stock_arrival_quantity,
                            order_arrival_quantity: this.details[quote_detail_id][idx].order_arrival_quantity,
                            stock_reserve_quantity: this.details[quote_detail_id][idx].stock_reserve_quantity,
                            stock_reserve_quantity_validity: this.details[quote_detail_id][idx].stock_reserve_quantity_validity,
                            order_reserve_quantity: this.details[quote_detail_id][idx].order_reserve_quantity,
                            order_reserve_quantity_validity: this.details[quote_detail_id][idx].order_reserve_quantity_validity,
                            keep_reserve_quantity: this.details[quote_detail_id][idx].keep_reserve_quantity,
                            keep_reserve_quantity_validity: this.details[quote_detail_id][idx].keep_reserve_quantity_validity,
                            seq_no: element.seq_no,
                            unit: element.unit,
                            min_quantity: element.min_quantity,
                            order_lot_quantity: element.order_lot_quantity,
                            stock_id: this.details[quote_detail_id][idx].stock_id,
                            keep_id: this.details[quote_detail_id][idx].keep_id,
                            path: this.details[quote_detail_id][idx].path,
                            inventory_quantity: this.details[quote_detail_id][idx].inventory_quantity,
                            ret_active_actual_quantity: this.details[quote_detail_id][idx].ret_active_actual_quantity,
                            ret_active_keep_quantity: this.details[quote_detail_id][idx].ret_active_keep_quantity,
                            max_input_actual_quantity: this.details[quote_detail_id][idx].max_input_actual_quantity,
                            max_input_keep_quantity: this.details[quote_detail_id][idx].max_input_keep_quantity,
                            min_input_actual_quantity: this.details[quote_detail_id][idx].min_input_actual_quantity,
                            min_input_keep_quantity: this.details[quote_detail_id][idx].min_input_keep_quantity,
                            errMsg: '',
                        }

                        detailArr.push(itemsSource);
                    });                    
                }
            })
            
            var cv = new wjCore.CollectionView(detailArr);
            
            return cv;
        },
        // 親グリッドパラメータセット
        calcParentQuantity() {
            this.main.forEach((element, i) => {
                var reserve_quantity_1 = 0;
                var active_quantity_1 = 0;
                var reserve_validity_1 = 0;
                var reserve_quantity_2 = 0; 
                var active_quantity_2 = 0;
                var reserve_validity_2 = 0;
                var reserve_quantity_3 = 0; 
                var active_quantity_3 = 0;
                var reserve_validity_3 = 0;

                var total_stock_reserve_quantity = 0;
                // 引当数、在庫数取得
                this.quantity.forEach(quant => {
                    if (element.product_id == quant.product_id) {
                        if (this.wjInputObj.from_warehouse_id_1.selectedValue == quant.warehouse_id) {
                            active_quantity_1 = this.bigNumberPlus(active_quantity_1, quant.active_actual_quantity);
                            active_quantity_1 = this.bigNumberPlus(active_quantity_1, quant.active_keep_quantity);
                            // active_quantity_1 = this.bigNumberPlus(active_quantity_1, quant.stock_arrival_quantity);
                            // reserve_quantity_1 = this.bigNumberPlus(reserve_quantity_1, quant.stock_reserve_quantity);
                            // reserve_quantity_1 = this.bigNumberPlus(reserve_quantity_1, quant.order_reserve_quantity);
                            // reserve_quantity_1 = this.bigNumberPlus(reserve_quantity_1, quant.keep_reserve_quantity);
                        }
                        if (this.wjInputObj.from_warehouse_id_2.selectedValue == quant.warehouse_id) {
                            active_quantity_2 = this.bigNumberPlus(active_quantity_2, quant.active_actual_quantity);
                            active_quantity_2 = this.bigNumberPlus(active_quantity_2, quant.active_keep_quantity);
                            // active_quantity_2 = this.bigNumberPlus(active_quantity_2, quant.stock_arrival_quantity);
                            // reserve_quantity_2 = this.bigNumberPlus(reserve_quantity_1, quant.stock_reserve_quantity);
                            // reserve_quantity_2 = this.bigNumberPlus(reserve_quantity_1, quant.order_reserve_quantity);
                            // reserve_quantity_2 = this.bigNumberPlus(reserve_quantity_1, quant.keep_reserve_quantity);
                            
                        }
                    }
                });

                var total = 0;
                this.reservelist.forEach(reserve => {
                    if (element.id == reserve.quote_detail_id) {
                        // 親グリッドに引当倉庫1の引当数
                        if (reserve.product_id == element.product_id && reserve.from_warehouse_id == this.wjInputObj.from_warehouse_id_1.selectedValue) {
                            reserve_quantity_1 = this.bigNumberPlus(reserve_quantity_1, reserve.reserve_quantity);
                            reserve_validity_1 = this.bigNumberPlus(reserve_validity_1, reserve.reserve_quantity_validity);
                        }
                        // 親グリッドに引当倉庫2の引当数
                        if (reserve.product_id == element.product_id && reserve.from_warehouse_id == this.wjInputObj.from_warehouse_id_2.selectedValue) {
                            reserve_quantity_2 = this.bigNumberPlus(reserve_quantity_2, reserve.reserve_quantity);
                            reserve_validity_2 = this.bigNumberPlus(reserve_validity_2, reserve.reserve_quantity_validity);
                        }
                        // 親グリッドに引当倉庫3の引当数
                        if (reserve.product_id == element.product_id && reserve.from_warehouse_id != this.wjInputObj.from_warehouse_id_1.selectedValue && reserve.from_warehouse_id != this.wjInputObj.from_warehouse_id_2.selectedValue) {
                            reserve_quantity_3 = this.bigNumberPlus(reserve_quantity_3, reserve.reserve_quantity);
                            reserve_validity_3 = this.bigNumberPlus(reserve_validity_3, reserve.reserve_quantity_validity);
                        }

                        // if (reserve.product_id == element.product_id && (reserve.from_warehouse_id != this.wjInputObj.from_warehouse_id_1.selectedValue && reserve.from_warehouse_id != this.wjInputObj.from_warehouse_id_2.selectedValue)) {
                        //     total += parseInt(this.rmUndefinedZero(reserve.reserve_quantity));
                        // }
                    }
                });
                var total_input_quantity = 0; 
                var total_input_quantity_1 = 0;
                var total_input_quantity_2 = 0;
                var total_input_quantity_3 = 0;
                Object.keys(this.details).forEach(quote_detail_id => {
                    if (element.id == quote_detail_id) {
                        this.details[quote_detail_id].forEach((child, idx) => {
                            // 親グリッドに引当倉庫1の引当数
                            if (child.product_id == element.product_id && child.from_warehouse_id == this.wjInputObj.from_warehouse_id_1.selectedValue) {
                                total_input_quantity_1 = this.bigNumberPlus(total_input_quantity_1, child.input_actual_quantity);
                                total_input_quantity_1 = this.bigNumberPlus(total_input_quantity_1, child.input_keep_quantity);
                                total_stock_reserve_quantity = this.bigNumberPlus(total_stock_reserve_quantity, child.stock_reserve_quantity_validity);
                            }
                            // 親グリッドに引当倉庫2の引当数
                            if (child.product_id == element.product_id && child.from_warehouse_id == this.wjInputObj.from_warehouse_id_2.selectedValue) {
                                total_input_quantity_2 = this.bigNumberPlus(total_input_quantity_2, child.input_actual_quantity);
                                total_input_quantity_2 = this.bigNumberPlus(total_input_quantity_2, child.input_keep_quantity);
                                total_stock_reserve_quantity = this.bigNumberPlus(total_stock_reserve_quantity, child.stock_reserve_quantity_validity);
                            }
                            if (child.product_id == element.product_id && (child.from_warehouse_id != this.wjInputObj.from_warehouse_id_1.selectedValue && child.from_warehouse_id != this.wjInputObj.from_warehouse_id_2.selectedValue)) {
                                total_input_quantity_3 = this.bigNumberPlus(total_input_quantity_3, child.input_actual_quantity);
                                total_input_quantity_3 = this.bigNumberPlus(total_input_quantity_3, child.input_keep_quantity);
                                total_stock_reserve_quantity = this.bigNumberPlus(total_stock_reserve_quantity, child.stock_reserve_quantity_validity);
                            }
                        })
                    }
                });
                var inputQuantity = this.bigNumberPlus(this.bigNumberPlus(total_input_quantity_1, total_input_quantity_2), total_input_quantity_3);

                // 総引当数
                this.keepDOM[element.id].total_reserve_quantity.innerHTML = this.bigNumberPlus(this.bigNumberPlus(this.bigNumberPlus(reserve_quantity_1, reserve_quantity_2), reserve_quantity_3), inputQuantity);                
                // 引当数１
                this.keepDOM[element.id].reserve_quantity_1.innerHTML = this.bigNumberPlus(reserve_validity_1, total_input_quantity_1);
                // 有効在庫１
                this.keepDOM[element.id].active_quantity_1.innerHTML = active_quantity_1;
                // 引当数２
                this.keepDOM[element.id].reserve_quantity_2.innerHTML = this.bigNumberPlus(reserve_validity_2, total_input_quantity_2);
                // 有効在庫２
                this.keepDOM[element.id].active_quantity_2.innerHTML = active_quantity_2;

                // 総引当数セット
                this.main[i].active_quantity_1 = active_quantity_1;
                this.main[i].active_quantity_2 = active_quantity_2;
                this.main[i].reserve_quantity_1 = reserve_quantity_1;
                this.main[i].reserve_quantity_2 = reserve_quantity_2;

                this.main[i].total_reserve_quantity = this.bigNumberPlus(this.bigNumberPlus(this.bigNumberPlus(reserve_quantity_1, reserve_quantity_2), reserve_quantity_3), inputQuantity);
                this.main[i].total_input_quantity = inputQuantity;
            });
            // this.wjHeaderGrid.itemsSource = this.main;
            this.wjHeaderGrid.refresh();
        },
        // 子グリッドパラメータセット
        calcDetailQuantity() {
            Object.keys(this.details).forEach(quote_detail_id => {
                this.details[quote_detail_id].forEach((element, idx) => {
                    if (idx == 0) {
                        this.details[quote_detail_id][0].warehouse_name = '';
                        this.details[quote_detail_id][0].from_warehouse_id = 0;
                        this.details[quote_detail_id][0].actual_quantity = 0;
                        this.details[quote_detail_id][0].keep_quantity = 0;
                        this.details[quote_detail_id][0].inventory_quantity = 0;
                        this.details[quote_detail_id][0].ret_active_actual_quantity = 0;
                        this.details[quote_detail_id][0].ret_active_keep_quantity = 0;
                        this.details[quote_detail_id][0].active_actual_quantity = 0;
                        this.details[quote_detail_id][0].active_keep_quantity = 0;
                        this.details[quote_detail_id][0].stock_arrival_quantity = 0;
                        this.details[quote_detail_id][0].order_arrival_quantity = 0;
                        this.details[quote_detail_id][0].arrival_quantity = 0;
                        this.details[quote_detail_id][0].next_arrival_date = '';
                        this.details[quote_detail_id][0].order_reserve_quantity = 0;
                        this.details[quote_detail_id][0].stock_reserve_quantity = 0;
                        this.details[quote_detail_id][0].keep_reserve_quantity = 0;
                        this.details[quote_detail_id][0].max_input_actual_quantity = 0;
                        this.details[quote_detail_id][0].max_input_keep_quantity = 0;
                        this.details[quote_detail_id][0].active_quantity = 0;
                        this.details[quote_detail_id][0].input_actual_quantity = 0;
                        this.details[quote_detail_id][0].stock_reserve_quantity_validity = 0;
                        this.details[quote_detail_id][0].stock_reserve_set_flg = false;
                        this.details[quote_detail_id][0].input_keep_quantity = 0;
                        this.details[quote_detail_id][0].keep_reserve_quantity_validity = 0;
                        this.details[quote_detail_id][0].keep_reserve_set_flg = false;
                    }

                    if (idx == 1) {
                        this.details[quote_detail_id][1].warehouse_name = '';
                        this.details[quote_detail_id][1].from_warehouse_id = 0;
                        this.details[quote_detail_id][1].actual_quantity = 0;
                        this.details[quote_detail_id][1].keep_quantity = 0;
                        this.details[quote_detail_id][1].inventory_quantity = 0;
                        this.details[quote_detail_id][1].ret_active_actual_quantity = 0;
                        this.details[quote_detail_id][1].ret_active_keep_quantity = 0;
                        this.details[quote_detail_id][1].active_actual_quantity = 0;
                        this.details[quote_detail_id][1].active_keep_quantity = 0;
                        this.details[quote_detail_id][1].stock_arrival_quantity = 0;
                        this.details[quote_detail_id][1].order_arrival_quantity = 0;
                        this.details[quote_detail_id][1].arrival_quantity = 0;
                        this.details[quote_detail_id][1].next_arrival_date = '';
                        this.details[quote_detail_id][1].order_reserve_quantity = 0;
                        this.details[quote_detail_id][1].stock_reserve_quantity = 0;
                        this.details[quote_detail_id][1].keep_reserve_quantity = 0;
                        this.details[quote_detail_id][1].max_input_actual_quantity = 0;
                        this.details[quote_detail_id][1].max_input_keep_quantity = 0;
                        this.details[quote_detail_id][1].active_quantity = 0;
                        this.details[quote_detail_id][1].input_actual_quantity = 0;
                        this.details[quote_detail_id][1].stock_reserve_quantity_validity = 0;
                        this.details[quote_detail_id][1].stock_reserve_set_flg = false;
                        this.details[quote_detail_id][1].input_keep_quantity = 0;
                        this.details[quote_detail_id][1].keep_reserve_quantity_validity = 0;
                        this.details[quote_detail_id][1].keep_reserve_set_flg = false;
                    }

                    if (this.wjInputObj.from_warehouse_id_1.selectedValue != null && idx == 0) {
                        // 引当倉庫１の在庫数セット
                        this.details[quote_detail_id][idx].warehouse_name = this.wjInputObj.from_warehouse_id_1.text;
                        this.details[quote_detail_id][idx].from_warehouse_id = this.wjInputObj.from_warehouse_id_1.selectedValue;
                        this.quantity.forEach(quant => {
                            if (this.details[quote_detail_id][idx].product_id == quant.product_id && this.details[quote_detail_id][idx].from_warehouse_id == quant.warehouse_id) {
                                this.details[quote_detail_id][idx].actual_quantity = quant.actual_quantity;
                                this.details[quote_detail_id][idx].keep_quantity = quant.keep_quantity;

                                this.details[quote_detail_id][idx].inventory_quantity = quant.inventory_quantity;
                                this.details[quote_detail_id][idx].ret_active_actual_quantity = quant.active_actual_quantity;
                                this.details[quote_detail_id][idx].ret_active_keep_quantity = quant.customer_keep_quantity;

                                this.details[quote_detail_id][idx].active_actual_quantity = quant.active_actual_quantity;
                                this.details[quote_detail_id][idx].active_keep_quantity = quant.customer_keep_quantity;
                                this.details[quote_detail_id][idx].stock_arrival_quantity = quant.stock_arrival_quantity;
                                this.details[quote_detail_id][idx].order_arrival_quantity = quant.order_arrival_quantity;
                                this.details[quote_detail_id][idx].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                                this.details[quote_detail_id][idx].next_arrival_date = quant.next_arrival_date;
                                this.details[quote_detail_id][idx].order_reserve_quantity = quant.order_reserve_quantity;
                                this.details[quote_detail_id][idx].stock_reserve_quantity = quant.stock_reserve_quantity;
                                this.details[quote_detail_id][idx].keep_reserve_quantity = quant.keep_reserve_quantity;

                                this.details[quote_detail_id][idx].max_input_actual_quantity = this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity);
                                this.details[quote_detail_id][idx].max_input_keep_quantity = quant.customer_keep_quantity;

                                var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.customer_keep_quantity);
                                activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
        
                                this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                            }
                        });
                        // 引当数セット
                        this.reservelist.forEach(reserve => {
                            if (reserve.quote_detail_id == quote_detail_id) {
                                if (this.details[quote_detail_id][idx].product_id == reserve.product_id && this.details[quote_detail_id][idx].from_warehouse_id == reserve.from_warehouse_id) {                                        
                                    
                                    if (reserve.stock_flg == this.STOCK_FLG) {
                                        this.details[quote_detail_id][idx].stock_id = reserve.reserve_id;
                                        if (!this.details[quote_detail_id][idx].stock_reserve_set_flg) {
                                            // this.details[quote_detail_id][idx].input_actual_quantity = reserve.reserve_quantity;
                                            // this.details[quote_detail_id][idx].max_input_actual_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].max_input_actual_quantity, reserve.reserve_quantity);
                                            this.details[quote_detail_id][idx].min_input_actual_quantity = parseInt('-' + reserve.reserve_quantity_validity);
                                        }
                                        this.details[quote_detail_id][idx].actual_reserve_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].all_actual_reserve = reserve.reserve_quantity;
                                        this.details[quote_detail_id][idx].stock_reserve_quantity_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].active_actual_quantity = this.details[quote_detail_id][idx].active_actual_quantity;
                                        this.details[quote_detail_id][idx].active_quantity = this.details[quote_detail_id][idx].active_quantity;
                                        // if (reserve.finish_flg == this.FLG_OFF) {
                                        //     this.details[quote_detail_id][idx].actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].actual_quantity, reserve.reserve_quantity);
                                        // }
                                        this.details[quote_detail_id][idx].stock_reserve_set_flg = true;
                                    } else if (reserve.stock_flg == this.KEEP_FLG) {
                                        this.details[quote_detail_id][idx].keep_id = reserve.reserve_id;
                                        if (!this.details[quote_detail_id][idx].keep_reserve_set_flg) {
                                            // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                                            // this.details[quote_detail_id][idx].max_input_keep_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].max_input_keep_quantity, reserve.reserve_quantity);
                                            this.details[quote_detail_id][idx].min_input_keep_quantity = parseInt('-' + reserve.reserve_quantity_validity);
                                        }
                                        this.details[quote_detail_id][idx].keep_reserve_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].all_keep_reserve = reserve.reserve_quantity;
                                        this.details[quote_detail_id][idx].keep_reserve_quantity_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].active_keep_quantity = this.details[quote_detail_id][idx].active_keep_quantity;
                                        this.details[quote_detail_id][idx].active_quantity = this.details[quote_detail_id][idx].active_quantity;
                                        // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                                        // if (reserve.finish_flg == this.FLG_OFF) {
                                        //     this.details[quote_detail_id][idx].keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].keep_quantity, reserve.reserve_quantity);
                                        // }
                                        this.details[quote_detail_id][idx].keep_reserve_set_flg = true;
                                    }
                                }
                            }
                        });
                    }
                    if (this.wjInputObj.from_warehouse_id_2.selectedValue != null && idx == 1) {
                        // 引当在庫２の在庫数セット
                        this.details[quote_detail_id][idx].warehouse_name = this.wjInputObj.from_warehouse_id_2.text;
                        this.details[quote_detail_id][idx].from_warehouse_id = this.wjInputObj.from_warehouse_id_2.selectedValue;
                        this.quantity.forEach(quant => {
                            if (this.details[quote_detail_id][idx].product_id == quant.product_id && this.details[quote_detail_id][idx].from_warehouse_id == quant.warehouse_id) {
                                this.details[quote_detail_id][idx].actual_quantity = quant.actual_quantity;
                                this.details[quote_detail_id][idx].keep_quantity = quant.keep_quantity;

                                this.details[quote_detail_id][idx].inventory_quantity = quant.inventory_quantity;
                                this.details[quote_detail_id][idx].ret_active_actual_quantity = quant.active_actual_quantity;
                                this.details[quote_detail_id][idx].ret_active_keep_quantity = quant.customer_keep_quantity;

                                this.details[quote_detail_id][idx].active_actual_quantity = quant.active_actual_quantity;
                                this.details[quote_detail_id][idx].active_keep_quantity = quant.customer_keep_quantity;
                                this.details[quote_detail_id][idx].stock_arrival_quantity = quant.stock_arrival_quantity;
                                this.details[quote_detail_id][idx].order_arrival_quantity = quant.order_arrival_quantity;
                                this.details[quote_detail_id][idx].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                                this.details[quote_detail_id][idx].next_arrival_date = quant.next_arrival_date;
                                this.details[quote_detail_id][idx].order_reserve_quantity = quant.order_reserve_quantity;
                                this.details[quote_detail_id][idx].stock_reserve_quantity = quant.stock_reserve_quantity;
                                this.details[quote_detail_id][idx].keep_reserve_quantity = quant.keep_reserve_quantity;

                                this.details[quote_detail_id][idx].max_input_actual_quantity = this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity);
                                this.details[quote_detail_id][idx].max_input_keep_quantity = quant.customer_keep_quantity;

                                var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.customer_keep_quantity);
                                activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
        
                                this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                            }
                        });
                        // 引当数セット
                        this.reservelist.forEach(reserve => {
                            if (reserve.quote_detail_id == quote_detail_id) {
                                if (this.details[quote_detail_id][idx].product_id == reserve.product_id && this.details[quote_detail_id][idx].from_warehouse_id == reserve.from_warehouse_id) {
                                    if (reserve.stock_flg == this.STOCK_FLG) {
                                        this.details[quote_detail_id][idx].stock_id = reserve.reserve_id;
                                        if (!this.details[quote_detail_id][idx].stock_reserve_set_flg) {
                                            // this.details[quote_detail_id][idx].input_actual_quantity = reserve.reserve_quantity;
                                            // this.details[quote_detail_id][idx].max_input_actual_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].max_input_actual_quantity, reserve.reserve_quantity);
                                            this.details[quote_detail_id][idx].min_input_actual_quantity = parseInt('-' + reserve.reserve_quantity_validity);
                                        }
                                        this.details[quote_detail_id][idx].actual_reserve_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].all_actual_reserve = reserve.reserve_quantity;
                                        this.details[quote_detail_id][idx].stock_reserve_quantity_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].active_actual_quantity = this.details[quote_detail_id][idx].active_actual_quantity;
                                        this.details[quote_detail_id][idx].active_quantity = this.details[quote_detail_id][idx].active_quantity;
                                        // if (reserve.finish_flg == this.FLG_OFF) {
                                        //     this.details[quote_detail_id][idx].actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].actual_quantity, reserve.reserve_quantity);
                                        // }
                                        this.details[quote_detail_id][idx].stock_reserve_set_flg = true;
                                    } else if (reserve.stock_flg == this.KEEP_FLG) {
                                        this.details[quote_detail_id][idx].keep_id = reserve.reserve_id;
                                        if (!this.details[quote_detail_id][idx].keep_reserve_set_flg) {
                                            // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                                            // this.details[quote_detail_id][idx].max_input_keep_quantity = this.bigNumberPlus(this.details[quote_detail_id][idx].max_input_keep_quantity, reserve.reserve_quantity);
                                            this.details[quote_detail_id][idx].min_input_keep_quantity = parseInt('-' + reserve.reserve_quantity_validity);
                                        }
                                        this.details[quote_detail_id][idx].keep_reserve_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].all_keep_reserve = reserve.reserve_quantity;
                                        this.details[quote_detail_id][idx].keep_reserve_quantity_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].active_keep_quantity = this.details[quote_detail_id][idx].active_keep_quantity;
                                        this.details[quote_detail_id][idx].active_quantity = this.details[quote_detail_id][idx].active_quantity;
                                        // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                                        // if (reserve.finish_flg == this.FLG_OFF) {
                                        //     this.details[quote_detail_id][idx].keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].keep_quantity, reserve.reserve_quantity);
                                        // }
                                        this.details[quote_detail_id][idx].keep_reserve_set_flg = true;
                                    }
                                }
                            }
                        });
                    }
                    if (idx == 2 && this.details[quote_detail_id][idx].from_warehouse_id != null) { 
                        // 引当数セット
                        this.reservelist.forEach(reserve => {
                            if (reserve.quote_detail_id == quote_detail_id) {
                                if (this.details[quote_detail_id][idx].product_id == reserve.product_id && (this.wjInputObj.from_warehouse_id_1.selectedValue != reserve.from_warehouse_id && this.wjInputObj.from_warehouse_id_2.selectedValue != reserve.from_warehouse_id)) {
                                    this.details[quote_detail_id][idx].reserve_set_flg = true;
                                    this.details[quote_detail_id][idx].from_warehouse_id = reserve.from_warehouse_id;
                                    this.details[quote_detail_id][idx].warehouse_name = reserve.warehouse_name;
                                    if (reserve.stock_flg == this.STOCK_FLG) {
                                        this.details[quote_detail_id][idx].stock_id = reserve.reserve_id;
                                        if (!this.details[quote_detail_id][idx].stock_reserve_set_flg) {
                                            // this.details[quote_detail_id][idx].input_actual_quantity = reserve.reserve_quantity;
                                            // this.details[quote_detail_id][idx].max_input_actual_quantity = reserve.reserve_quantity;
                                            this.details[quote_detail_id][idx].min_input_actual_quantity = parseInt('-' + reserve.reserve_quantity_validity);
                                        }
                                        this.details[quote_detail_id][idx].actual_reserve_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].all_actual_reserve = reserve.reserve_quantity;
                                        this.details[quote_detail_id][idx].stock_reserve_quantity_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].active_actual_quantity = this.details[quote_detail_id][idx].active_actual_quantity;
                                        this.details[quote_detail_id][idx].active_quantity = this.details[quote_detail_id][idx].active_quantity;
                                        // if (reserve.finish_flg == this.FLG_OFF) {
                                        //     this.details[quote_detail_id][idx].actual_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].actual_quantity, reserve.reserve_quantity);
                                        // }
                                        this.details[quote_detail_id][idx].stock_reserve_set_flg = true;
                                    } else if (reserve.stock_flg == this.KEEP_FLG) {
                                        this.details[quote_detail_id][idx].keep_id = reserve.reserve_id;
                                        if (!this.details[quote_detail_id][idx].keep_reserve_set_flg) {
                                            // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                                            // this.details[quote_detail_id][idx].max_input_keep_quantity = reserve.reserve_quantity;
                                            this.details[quote_detail_id][idx].min_input_keep_quantity = parseInt('-' + reserve.reserve_quantity_validity);
                                        }
                                        this.details[quote_detail_id][idx].keep_reserve_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].all_keep_reserve = reserve.reserve_quantity;
                                        this.details[quote_detail_id][idx].keep_reserve_quantity_validity = reserve.reserve_quantity_validity;
                                        this.details[quote_detail_id][idx].active_keep_quantity = this.details[quote_detail_id][idx].active_keep_quantity;
                                        this.details[quote_detail_id][idx].active_quantity = this.details[quote_detail_id][idx].active_quantity;
                                        // this.details[quote_detail_id][idx].input_keep_quantity = reserve.reserve_quantity;
                                        // if (reserve.finish_flg == this.FLG_OFF) {
                                        //     this.details[quote_detail_id][idx].keep_quantity = this.bigNumberMinus(this.details[quote_detail_id][idx].keep_quantity, reserve.reserve_quantity);
                                        // }
                                        this.details[quote_detail_id][idx].keep_reserve_set_flg = true;
                                    }
                                }
                            }
                        });                           
                        // 引当在庫３の在庫数セット
                        this.quantity.forEach(quant => {
                            if (this.details[quote_detail_id][idx].product_id == quant.product_id && this.details[quote_detail_id][idx].from_warehouse_id == quant.warehouse_id) {
                                this.details[quote_detail_id][idx].actual_quantity = quant.actual_quantity;
                                this.details[quote_detail_id][idx].keep_quantity = quant.keep_quantity;

                                this.details[quote_detail_id][idx].inventory_quantity = quant.inventory_quantity;
                                this.details[quote_detail_id][idx].ret_active_actual_quantity = quant.active_actual_quantity;
                                this.details[quote_detail_id][idx].ret_active_keep_quantity = quant.customer_keep_quantity;

                                this.details[quote_detail_id][idx].active_actual_quantity = quant.active_actual_quantity;
                                this.details[quote_detail_id][idx].active_keep_quantity = quant.customer_keep_quantity;
                                this.details[quote_detail_id][idx].stock_arrival_quantity = quant.stock_arrival_quantity;
                                this.details[quote_detail_id][idx].order_arrival_quantity = quant.order_arrival_quantity;
                                this.details[quote_detail_id][idx].arrival_quantity = this.bigNumberPlus(quant.stock_arrival_quantity, quant.order_arrival_quantity);
                                this.details[quote_detail_id][idx].next_arrival_date = quant.next_arrival_date;
                                this.details[quote_detail_id][idx].order_reserve_quantity = quant.order_reserve_quantity;
                                this.details[quote_detail_id][idx].stock_reserve_quantity = quant.stock_reserve_quantity;
                                this.details[quote_detail_id][idx].keep_reserve_quantity = quant.keep_reserve_quantity;

                                this.details[quote_detail_id][idx].max_input_actual_quantity = this.bigNumberPlus(quant.active_actual_quantity, quant.stock_arrival_quantity);
                                this.details[quote_detail_id][idx].max_input_keep_quantity = quant.customer_keep_quantity;

                                var activeQuantity = this.bigNumberPlus(quant.active_actual_quantity, quant.customer_keep_quantity);
                                activeQuantity = this.bigNumberPlus(activeQuantity, quant.stock_arrival_quantity);
        
                                this.details[quote_detail_id][idx].active_quantity = activeQuantity;
                            }
                        });
                    }
                    
                });
            })
        },
        // 検索
        search() {
            this.loading = true;
            this.tableData = 0;
            var quoteIdList = [];
            if (this.main.length > 0) {
                quoteIdList.push(this.main[0].quote_id);
            }

            this.main = [];
            this.details = {};
            this.quantity = [];
            this.reservelist = [];
            this.orderdata = {};
            this.arrivalList = [];

            // エラーの初期化
            this.initErr(this.errors);

            // 案件の必須チェック
            // if (this.rmUndefinedBlank(this.wjSearchObj.matter_no.text) == '' && this.rmUndefinedBlank(this.wjSearchObj.matter_name.text) == '') {
            //     this.errors.matter = MSG_ERROR_NO_INPUT;
            //     this.loading = false;
            //     return false;
            // }
            var matterNo = "";
            if (this.wjSearchObj.matter_no.selectedValue) {
               matterNo = this.wjSearchObj.matter_no.selectedValue;
            } else if(this.wjSearchObj.matter_name.selectedValue) {
               matterNo = this.wjSearchObj.matter_name.selectedItem.matter_no;
            }
            if(this.rmUndefinedBlank(matterNo) === ''){
                alert(MSG_ERROR_MATTER_NO_OR_MATTER_NAME_NO_SELECT);
                this.errors.matter = MSG_ERROR_NO_INPUT;
                this.loading = false;
                return;
            }

            var params = new URLSearchParams();

            params.append('customer_name', this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
            params.append('matter_no', this.rmUndefinedBlank(matterNo));
            params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
            params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
            params.append('staff_name', this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));
            params.append('quote_id_list', quoteIdList);
            
            axios.post('/stock-allocation/search', params)
            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                    this.urlparam += '&' + 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
                    this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));

                    this.editable = response.data.isEditable;
                    this.isOwnLock = response.data.isOwnLock;
                    this.lockData = response.data.lockData;
                    this.orderdata = response.data.orderData;

                    this.isLocked = false;
                    this.isShowEditBtn = true;
                    this.isReadOnly = true;
                    if (this.editable == FLG_EDITABLE) {
                        // 編集可
                        this.isShowEditBtn = true;
                        if (response.data.parentList.length == 0) {
                            this.isShowEditBtn = false;
                            this.isReadOnly = false;
                        }

                        // ロック中判定
                        if (this.rmUndefinedBlank(this.lockData.id) != '' && this.isOwnLock != 1) {
                            this.isLocked = true;
                            this.isShowEditBtn = false;
                            this.isReadOnly = true;
                        }
                    }
                    if(this.isLocked) {
                        // ロック中
                        this.isShowEditBtn = false;
                        this.isReadOnly = true;
                    }else {
                        // 編集モードで開くか判定
                        var query = window.location.search;
                        if (this.isOwnLock == 1 || this.isEditMode(query, this.isReadOnly, this.editable)) {
                            this.isReadOnly = false;
                            this.isShowEditBtn = false;
                        }
                    }

                    this.reserveLayout = this.getDetailLayout();

                    // 案件完了フラグが立っている場合、編集不可
                    this.matterCompleteFlg = this.rmUndefinedZero(response.data.matterInfo.complete_flg);

                    // 引当倉庫1,2セット
                    if (this.rmUndefinedZero(response.data.matterInfo.from_warehouse_id_1) != 0) {
                        this.wjInputObj.from_warehouse_id_1.selectedValue = response.data.matterInfo.from_warehouse_id_1;
                        this.isEditable.from_warehouse_id_1 = false;
                    } else {
                        // this.wjInputObj.from_warehouse_id_1.selectedValue = -1;
                        this.isEditable.from_warehouse_id_1 = true;
                    }
                    if (this.rmUndefinedZero(response.data.matterInfo.from_warehouse_id_2) != 0) {
                        this.wjInputObj.from_warehouse_id_2.selectedValue = response.data.matterInfo.from_warehouse_id_2;
                        this.isEditable.from_warehouse_id_2 = false;
                    } else {
                        // this.wjInputObj.from_warehouse_id_2.selectedValue = -1;
                        this.isEditable.from_warehouse_id_2 = true;
                    }

                    if (response.data.quantityList.length != 0){
                        // for(var j in response.data.quantityList) {
                        //     var rec = response.data.quantityList[j];
                            
                        //     this.quantity.push(rec);
                        // }
                        this.quantity = response.data.quantityList;
                    }

                    // 親グリッドのデータをローカル変数へ
                    if(response.data.parentList.length != 0){
                        for(var i in response.data.parentList) {
                            var rec = response.data.parentList[i];
                            rec['total_reserve_quantity'] = parseInt(this.rmUndefinedZero(rec['total_reserve_quantity']));
                            rec['before_total_reserve_quantity'] = parseInt(this.rmUndefinedZero(rec['total_reserve_quantity']));
                            rec['reserve_quantity_1'] = parseInt(this.rmUndefinedZero(rec['reserve_quantity_1']));
                            rec['active_quantity_1'] = parseInt(this.rmUndefinedZero(rec['active_quantity_1']));
                            rec['reserve_quantity_2'] = parseInt(this.rmUndefinedZero(rec['reserve_quantity_2']));
                            rec['active_quantity_2'] = parseInt(this.rmUndefinedZero(rec['active_quantity_2']));
                            rec['total_input_quantity'] = 0;

                            this.main.push(rec);
                        }
                    }
                    // 引当リストをローカル変数へ
                    if (response.data.reserveList.length != 0){
                        // for(var i in response.data.reserveList) {
                        //     var rec = response.data.reserveList[i];
                            
                        //     this.reservelist.push(rec);
                        // }
                        this.reservelist = response.data.reserveList;
                    }

                    var dataLength = 0;
                    var path = 0;
                    var itemsSource = [];
                    this.main.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            construction_name: document.createElement('span'),
                            parent_name: document.createElement('span'),
                            product_code: document.createElement('span'),
                            total_reserve_quantity: document.createElement('span'),
                            reserve_quantity_1: document.createElement('span'),
                            active_quantity_1: document.createElement('span'),
                            reserve_quantity_2: document.createElement('span'),       
                            active_quantity_2: document.createElement('span'),            
                        }

                        element.path = path;

                        // 工事区分
                        this.keepDOM[element.id].construction_name.innerHTML = element.construction_name;
                        this.keepDOM[element.id].construction_name.classList.add('grid-text');

                        // 工事区分
                        this.keepDOM[element.id].parent_name.innerHTML = element.parent_name;
                        this.keepDOM[element.id].parent_name.classList.add('grid-text');

                        // 商品番号
                        this.keepDOM[element.id].product_code.innerHTML = element.product_code;
                        this.keepDOM[element.id].product_code.classList.add('grid-text');

                        // 総引当数
                        this.keepDOM[element.id].total_reserve_quantity.innerHTML = element.total_reserve_quantity;
                        this.keepDOM[element.id].total_reserve_quantity.classList.add('grid-text');
                        
                        // 引当数１
                        this.keepDOM[element.id].reserve_quantity_1.innerHTML = element.reserve_quantity_1;
                        this.keepDOM[element.id].reserve_quantity_1.classList.add('grid-text');

                        // 有効在庫１
                        this.keepDOM[element.id].active_quantity_1.innerHTML = element.active_quantity_1;
                        this.keepDOM[element.id].active_quantity_1.classList.add('grid-text');

                        // 引当数２
                        this.keepDOM[element.id].reserve_quantity_2.innerHTML = element.reserve_quantity_2;
                        this.keepDOM[element.id].reserve_quantity_2.classList.add('grid-text');

                        // 有効在庫２
                        this.keepDOM[element.id].active_quantity_2.innerHTML = element.active_quantity_2;
                        this.keepDOM[element.id].active_quantity_2.classList.add('grid-text');

                        itemsSource.push({
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            construction_name: element.construction_name,
                            parent_name: element.parent_name,   
                            product_id: element.product_id,                        
                            product_code: element.product_code,
                            product_name: element.product_name,
                            customer_id: element.customer_id,
                            model: element.model,
                            maker_name: element.maker_name,
                            supplier_name: element.supplier_name,
                            quote_quantity: element.quote_quantity,
                            stock_quantity: element.stock_quantity,
                            unit: element.unit,
                            stock_unit: element.stock_unit,
                            total_reserve_quantity: element.total_reserve_quantity,
                            // before_total_reserve_quantity: element.total_reserve_quantity,
                            sum_order_reserve_quantity: element.sum_order_reserve_quantity,
                            sum_stock_reserve_quantity: element.sum_stock_reserve_quantity,
                            sum_keep_reserve_quantity: element.sum_keep_reserve_quantity,
                            reserve_quantity_1: element.reserve_quantity_1,
                            active_quantity_1: element.active_quantity_1,
                            reserve_quantity_2: element.reserve_quantity_2,
                            active_quantity_2: element.active_quantity_2,
                            set_flg: element.set_flg,
                            min_quantity: element.min_quantity,
                            order_lot_quantity: element.order_lot_quantity,
                            path: path,
                            parent_quote_detail_id: element.parent_quote_detail_id,
                            total_input_quantity: 0,
                            // active_quantity_2: element.active_quantity_2,
                            // reserve_quantity_2: element.reserve_quantity_2,
                            // active_quantity_1: element.active_quantity_1,
                            // reserve_quantity_1: element.reserve_quantity_1,
                            // total_reserve_quantity: element.total_reserve_quantity,
                        })

                        this.details[element.id] = [{
                            id: '',
                            quote_detail_id: element.id,
                            from_warehouse_id: this.wjInputObj.from_warehouse_id_1.selectedValue,
                            warehouse_name: '',
                            customer_id: element.customer_id,
                            input_actual_quantity: 0,
                            input_keep_quantity: 0,
                            actual_quantity: 0,
                            keep_quantity: 0,
                            inventory_quantity: 0,
                            active_actual_quantity: 0,
                            active_keep_quantity: 0,
                            actual_reserve_validity: 0,
                            all_actual_reserve: 0,
                            keep_reserve_validity: 0,
                            all_keep_reserve: 0,
                            ret_active_actual_quantity: 0,
                            ret_active_keep_quantity: 0,
                            stock_reserve_quantity: 0,
                            order_reserve_quantity: 0,
                            keep_reserve_quantity: 0,
                            stock_arrival_quantity: 0,
                            order_arrival_quantity: 0,
                            stock_reserve_quantity_validity: 0,
                            keep_reserve_quantity_validity: 0,
                            active_quantity: 0,
                            arrival_quantity: 0,
                            next_arrival_date: '',
                            seq_no: 1,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            unit: element.stock_unit,
                            min_quantity: element.min_quantity,
                            order_lot_quantity: element.order_lot_quantity,
                            stock_id: 0,
                            keep_id: 0,
                            set_flg: element.set_flg,
                            quote_quantity: element.quote_quantity,
                            stock_quantity: element.stock_quantity,
                            path: path,
                            actual_finish_flg: 0,
                            keep_finish_flg: 0,
                            stock_reserve_set_flg: false,
                            keep_reserve_set_flg: false,
                            max_input_actual_quantity: 0,
                            max_input_keep_quantity: 0,
                            min_input_actual_quantity: 0,
                            min_input_keep_quantity: 0,
                        }, 
                        {
                            id: '',
                            quote_detail_id: element.id,
                            from_warehouse_id: this.wjInputObj.from_warehouse_id_2.selectedValue,
                            warehouse_name: '',
                            customer_id: element.customer_id,
                            input_actual_quantity: 0,
                            input_keep_quantity: 0,
                            actual_quantity: 0,
                            keep_quantity: 0,
                            actual_reserve_validity: 0,
                            all_actual_reserve: 0,
                            keep_reserve_validity: 0,
                            all_keep_reserve: 0,
                            inventory_quantity: 0,
                            stock_reserve_quantity: 0,
                            order_reserve_quantity: 0,
                            keep_reserve_quantity: 0,
                            stock_arrival_quantity: 0,
                            order_arrival_quantity: 0,
                            stock_reserve_quantity_validity: 0,
                            keep_reserve_quantity_validity: 0,
                            active_actual_quantity: 0,
                            active_keep_quantity: 0,
                            ret_active_actual_quantity: 0,
                            ret_active_keep_quantity: 0,
                            active_quantity: 0,
                            arrival_quantity: 0,
                            next_arrival_date: '',
                            seq_no: 2,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            unit: element.stock_unit,
                            min_quantity: element.min_quantity,
                            order_lot_quantity: element.order_lot_quantity,
                            stock_id: 0,
                            keep_id: 0,
                            set_flg: element.set_flg,
                            quote_quantity: element.quote_quantity,
                            stock_quantity: element.stock_quantity,
                            path: path,
                            actual_finish_flg: 0,
                            keep_finish_flg: 0,
                            stock_reserve_set_flg: false,
                            keep_reserve_set_flg: false,
                            max_input_actual_quantity: 0,
                            max_input_keep_quantity: 0,
                            min_input_actual_quantity: 0,
                            min_input_keep_quantity: 0,
                        },
                        {
                            id: '',
                            quote_detail_id: element.id,
                            from_warehouse_id: '',
                            warehouse_name: '',
                            customer_id: element.customer_id,
                            input_actual_quantity: 0,
                            input_keep_quantity: 0,
                            actual_quantity: 0,
                            keep_quantity: 0,
                            actual_reserve_validity: 0,
                            all_actual_reserve: 0,
                            keep_reserve_validity: 0,
                            all_keep_reserve: 0,
                            inventory_quantity: 0,
                            stock_reserve_quantity: 0,
                            order_reserve_quantity: 0,
                            keep_reserve_quantity: 0,
                            stock_arrival_quantity: 0,
                            order_arrival_quantity: 0,
                            stock_reserve_quantity_validity: 0,
                            keep_reserve_quantity_validity: 0,
                            active_actual_quantity: 0,
                            active_keep_quantity: 0,
                            ret_active_actual_quantity: 0,
                            ret_active_keep_quantity: 0,
                            active_quantity: 0,
                            arrival_quantity: 0,
                            next_arrival_date: '',
                            seq_no: 3,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            unit: element.stock_unit,
                            min_quantity: element.min_quantity,
                            order_lot_quantity: element.order_lot_quantity,
                            stock_id: 0,
                            keep_id: 0,
                            set_flg: element.set_flg,
                            quote_quantity: element.quote_quantity,
                            calc_flg: false,
                            path: path,
                            actual_finish_flg: 0,
                            keep_finish_flg: 0,
                            stock_reserve_set_flg: false,
                            keep_reserve_set_flg: false,
                            max_input_actual_quantity: 0,
                            max_input_keep_quantity: 0,
                            min_input_actual_quantity: 0,
                            min_input_keep_quantity: 0,
                        }]

                        path++;
                        dataLength++;
                    })
                    this.tableData = dataLength;
                    // データセット
                    this.wjHeaderGrid.itemsSource = this.main;

                    this.calcDetailQuantity();
                    this.calcParentQuantity();

                    this.wjHeaderGrid = this.applyGridSettings(this.wjHeaderGrid);

                    this.filter();
                    
                    // 描画更新
                    this.wjHeaderGrid.refresh();
                    for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                        this.wjDetailGrid.hideDetail(i);
                    }      
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
        // 戻る
        back() {
            var listUrl = '/';

            if (!this.isReadOnly && this.main.length > 0) {
                var quoteIdList = [];
                quoteIdList.push(this.main[0].quote_id);

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'stock-allocation');
                params.append('keys', this.rmUndefinedBlank(quoteIdList));
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
        // 戻る
        orderLink() {
            if (this.orderdata.matter_id == null || this.orderdata.matter_id == undefined) {
                return;
            }
            if (this.main.length > 0) {
                var quoteIdList = [];
                quoteIdList.push(this.main[0].quote_id);

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'stock-allocation');
                params.append('keys', this.rmUndefinedBlank(quoteIdList));
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    this.loading = false
                    if (response.data) {
                        var listUrl = '/order-edit/' + this.orderdata.matter_id;
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
                var listUrl = '/order-edit/' + this.orderdata.matter_id;
                window.onbeforeunload = null;
                location.href = listUrl
            }
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
                if (i == 0 || i == 1 || i == 2 || i == 7){
                    grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(1, i));
                    grid.columnHeaders.columns[i].allowMerging = true;
                }
                if (i == 8 || i == 10) {                  
                    grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(0, (i + 1)));
                    grid.columnHeaders.rows[0].allowMerging = true;
                    grid.columnHeaders.columns[i].allowMerging = true;
                    grid.columnHeaders.columns[i+1].allowMerging = true;
                }
            }      

            // var mm = new wjGrid.MergeManager(grid);

            // // 結合するセルの範囲
            // var ranges = [];
            // // 0 1 6 7 8 9 10
            // var itemlength = this.rmUndefinedZero(grid.itemsSource.length * 2);
            // for (var r = 0; r < itemlength; r++) {
            //     if (r % 2 == 0) {
            //         for (var c = 0; c <= 10; c++) {
            //             if (c == 0 || c == 1 || c == 6 || c == 7 || c == 8 || c == 9 || c == 10) {
            //                 ranges.push(
            //                     new wjGrid.CellRange(r, c, r+1, c)
            //                 );
            //             }
            //         }
            //     }
            // }

            // // getMergedRangeメソッドをオーバーライドします。
            // mm.getMergedRange = function(panel, r, c) {
            //     if (panel.cellType == wjGrid.CellType.Cell) {
            //         for (var i = 0; i < ranges.length; i++) {
            //             if (ranges[i].contains(r, c)) {
            //                 return ranges[i];
            //             }
            //         }
            //     }
            // };
            // grid.mergeManager = mm;
            
            return grid;
        },
        applyDetailSettings(grid) {
            // リサイジング設定
            grid.columns.forEach(element => {
                if (this.detailSetting.deny_resizing_col.indexOf(element.index) >= 0) {
                    element.allowResizing = false;
                }
            });
            // 非表示設定
            grid.columns.forEach(element => {
                if (this.detailSetting.invisible_col.indexOf(element.index) >= 0) {
                    element.visible = false;
                }
            });
            // 列ヘッダー結合
            grid.allowMerging = wjGrid.AllowMerging.All;
            for (var i = 0; i < grid.columns.length; i++) {
                if (i == 1 || i == 2){
                    grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(1, i));
                    grid.columnHeaders.columns[i].allowMerging = true;
                }
            }   
            
            return grid;
        },
        getDetailLayout() {
            return [
                {
                    header: '倉庫名', cells: [
                        { binding: 'warehouse_name', name: 'warehouse_name', header: '倉庫名', isReadOnly: this.isReadOnly, width: 190, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '引当数', cells: [
                        { binding: 'input_actual_quantity', name: 'input_actual_quantity', isReadOnly: this.isReadOnly, header: '通常在庫登録数', minWidth: GRID_COL_MIN_WIDTH, width: 130 },
                    ]
                },  
                {
                    header: '引当数', cells: [
                        { binding: 'input_keep_quantity', name: 'input_keep_quantity', isReadOnly: this.isReadOnly, header: '預かり在庫登録数', minWidth: GRID_COL_MIN_WIDTH, width: 130 },
                    ]
                },  
                {
                    header: '引当数', cells: [
                        { binding: 'actual_reserve_validity', name: 'actual_reserve_validity', isReadOnly: this.isReadOnly, header: '通常在庫引当数', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true },
                        { binding: 'unit', name: 'unit',isReadOnly: true, header: '残合計', minWidth: GRID_COL_MIN_WIDTH, width: 140 },
                    ]
                },      
                {
                    header: '引当数', cells: [
                        { binding: 'keep_reserve_validity', name: 'keep_reserve_validity', isReadOnly: this.isReadOnly, header: '預かり在庫引当数', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true },
                        { binding: 'unit', name: 'unit',isReadOnly: true, header: '残合計', minWidth: GRID_COL_MIN_WIDTH, width: 140 },
                    ]
                },    
                // {
                //     header: '引当数', cells: [
                //         { binding: 'all_actual_reserve', name: 'all_actual_reserve', isReadOnly: this.isReadOnly, header: '通常全引当数', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                //     ]
                // },  
                // {
                //     header: '引当数', cells: [
                //         { binding: 'all_keep_reserve', name: 'all_keep_reserve', isReadOnly: this.isReadOnly, header: '預かり全引当数', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true},
                //     ]
                // },
                {
                    header: '実在庫', cells: [
                        { binding: 'actual_quantity',  name: 'actual_quantity', header: '実在庫数', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 100 },
                    ]
                },
                {
                    header: '預かり在庫', cells: [
                        { binding: 'keep_quantity', name: 'keep_quantity',  header: '預かり在庫数', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 100 },
                    ]
                },
                {
                    header: '有効在庫数', cells: [
                        { binding: 'active_actual_quantity',  name: 'active_actual_quantity', header: '有効通常在庫数', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 130 },
                    ]
                },
                {
                    header: '有効在庫数', cells: [
                        { binding: 'active_keep_quantity', name: 'active_keep_quantity',  header: '有効預かり在庫数', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 130 },
                    ]
                },
                {
                    header: '入荷予定数', cells: [
                        { binding: 'arrival_quantity', name: 'arrival_quantity', isReadOnly: true,  header: '入荷予定数', minWidth: GRID_COL_MIN_WIDTH, width: 100 },
                    ]
                },
                {
                    header: '入荷予定日', cells: [
                        { binding: 'next_arrival_date', name: 'next_arrival_date', isReadOnly: true,  header: '入荷予定日', minWidth: GRID_COL_MIN_WIDTH, width: 120 }
                    ]
                },
                {
                    header: '有効在庫', cells: [
                        { binding: 'active_quantity', name: 'active_quantity', isReadOnly: true,  header: '入荷後有効在庫数', minWidth: GRID_COL_MIN_WIDTH, width: 140 },
                    ]
                },
                /** 非表示 **/
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0, maxWidth: 0 }
                    ]
                },
                {
                    header: 'from_warehouse_id', cells: [
                        { binding: 'from_warehouse_id', header: 'from_warehouse_id', width: 0, maxWidth: 0 }
                    ]
                },
                {
                    header: 'product_id', cells: [
                        { binding: 'product_id', header: 'product_id', width: 0, maxWidth: 0 },
                    ]
                }, 
                {
                    header: 'product_code', cells: [
                        { binding: 'product_code', header: 'product_code', width: 0, maxWidth: 0 },
                    ]
                },     
                {
                    header: 'seq_no', cells: [
                        { binding: 'seq_no', header: 'seq_no', width: 0, maxWidth: 0 }
                    ]
                },
                {
                    header: 'all_stock', cells: [
                        { binding: 'all_stock', header: 'all_stock', width: 0, maxWidth: 0 }
                    ]
                },
                {
                    header: 'stock_id', cells: [
                        { binding: 'stock_id', header: 'stock_id', width: 0, maxWidth: 0 }
                    ]
                },
                {
                    header: 'keep_id', cells: [
                        { binding: 'keep_id', header: 'keep_id', width: 0, maxWidth: 0 }
                    ]
                },
                {
                    header: 'errMsg', cells: [
                        { binding: 'errMsg', header: 'errMsg', width: 0, maxWidth: 0 }
                    ]
                },
            ]
        },
        getLayout() {
            return [
                {
                    header: '工事区分', cells: [
                        { binding: 'id', name: 'construction_name', isReadOnly: true, header: '工事区分', width: 90, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '階層名', cells: [
                        { binding: 'id', name: 'parent_name', isReadOnly: true, header: '階層名', width: 130, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '商品番号', cells: [
                        { binding: 'id', name: 'product_code', header: '商品番号', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 200 },
                    ]
                },
                {
                    header: '商品名', cells: [
                        { binding: 'product_name', name: 'product_name', header: '商品名', isReadOnly: true, minWidth: 190, width: '*' },                        
                        { binding: 'model', name: 'model', header: '型式／規格', isReadOnly: true, minWidth: 190, width: '*' },
                    ]
                },
                {
                    header: 'メーカー仕入先', cells: [
                        { binding: 'maker_name', name: 'maker_name', header: 'メーカー名', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 180 },
                        { binding: 'supplier_name', name: 'supplier_name', header: '仕入先名', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 180 },
                    ]
                },
                {
                    header: '受注数', cells: [
                        { binding: 'quote_quantity', name: 'quote_quantity', header: '受注数', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 90 },
                        { binding: 'stock_quantity',name: 'stock_quantity',  header: '管理数', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 90 }
                    ]
                },
                {
                    header: '単位', cells: [
                        { binding: 'unit', name: 'unit', header: '単位', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 90 },
                        { binding: 'stock_unit', name: 'stock_unit',  header: '管理数単位', isReadOnly: true, minWidth: GRID_COL_MIN_WIDTH, width: 90 }
                    ]
                },
                {
                    header: '総引当数', cells: [
                        { binding: 'id', name: 'total_reserve_quantity', isReadOnly: true, header: '総引当数(出荷済含む)', width: 110, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '引当倉庫１', cells: [
                        // { binding: 'reserve_quantity_1', name: 'header_1', isReadOnly: true, header: '引当', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', name: 'reserve_quantity_1', isReadOnly: true, header: '倉庫1引当数', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '引当倉庫１', cells: [
                        // { binding: 'active_quantity_1', name: 'header_1', isReadOnly: true, header: '引当', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', name: 'active_quantity_1', isReadOnly: true, header: '倉庫1有効在庫数', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '引当倉庫２', cells: [
                        // { binding: 'reserve_quantity_2', name: 'header_2', isReadOnly: true, header: '引当倉庫２', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', name: 'reserve_quantity_2', isReadOnly: true, header: '倉庫2引当数', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                {
                    header: '引当倉庫２', cells: [
                        // { binding: 'active_quantity_2', name: 'header_2', isReadOnly: true, header: '引当倉庫２', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                        { binding: 'id', name: 'active_quantity_2', isReadOnly: true, header: '倉庫2有効在庫数', width: 100, minWidth: GRID_COL_MIN_WIDTH },
                    ]
                },
                // 以下非表示
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0, maxWidth: 0 },
                    ]
                },   
                {
                    header: 'product_id', cells: [
                        { binding: 'product_id', header: 'product_id', width: 0, maxWidth: 0 },
                    ]
                },           
            ]
        },
        // 編集モード
        edit() {
            var quoteIdList = [];
            if (this.main.length > 0) {
                quoteIdList.push(this.main[0].quote_id);
            }

            var params = new URLSearchParams();
            params.append('screen', 'stock-allocation');
            params.append('keys', this.rmUndefinedBlank(quoteIdList));
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING)
                        location.reload()
                    } else {
                        this.isReadOnly = false
                        this.isShowEditBtn = false
                        this.lockData = response.data.lockdata

                        this.reserveLayout = this.getDetailLayout();
                        this.wjHeaderGrid.refresh();
                        for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                            this.wjDetailGrid.hideDetail(i);
                        } 
                    }
                } else {
                    window.onbeforeunload = null;
                    location.reload()
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
        },
        // ロック解除
        unlock() {
            if (!confirm(MSG_CONFIRM_UNLOCK)) {
                return;
            }

            var quoteIdList = [];
            if (this.main.length > 0) {
                quoteIdList.push(this.main[0].quote_id);
            }

            var params = new URLSearchParams();
            params.append('screen', 'stock-allocation');
            params.append('keys', this.rmUndefinedBlank(quoteIdList));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    // 編集中状態へ
                    this.isLocked = false
                    this.isReadOnly = false
                    this.isShowEditBtn = false
                    this.lockData = response.data.lockdata
                    this.reserveLayout = this.getDetailLayout();
                    this.wjHeaderGrid.refresh();
                    for (var i = 0; i < this.wjHeaderGrid.rows.length; i++) {
                        this.wjDetailGrid.hideDetail(i);
                    } 
                    window.onbeforeunload = function(e) {
                        return MSG_CONFIRM_LEAVE;
                    };
                } else {
                    // ロック取得失敗
                    window.onbeforeunload = null;
                    location.reload()
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
        },
        // 以下オートコンプリートの値取得
        initCustomer: function(sender) {
            this.wjSearchObj.customer_name = sender
        },
        initMatterNo: function(sender){
            this.wjSearchObj.matter_no = sender;
        },
        initMatterName: function(sender){
            this.wjSearchObj.matter_name = sender;
        },
        initDepartment: function(sender){
            this.wjSearchObj.department_name = sender;
        },
        initStaff: function(sender){
            this.wjSearchObj.staff_name = sender;
        },
        initWarehouse1: function(sender){
            this.wjInputObj.from_warehouse_id_1 = sender;
        },
        initWarehouse2: function(sender){
            this.wjInputObj.from_warehouse_id_2 = sender;
        },
    },
}
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
.warehouse-body {
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.checkbox-group {
    padding: 5px;
}
.grid-div {
    padding-right: 0 !important;
    padding-left: 0 !important;
    height: 700px;
}
.btn-lock {
    /* height: 35.74px; */
    min-width: 82.5px;
}
.wj-control[disabled] {
    opacity: .5 !important;
    background-color: #eeeeee !important;
}
.wj-grid-invalid {
    background-color: #FF3B30 !important;
}
.wj-tooltip-invalid {
    color: #FF3B30 !important;
}
/*********************************
    以下wijmo系
**********************************/
.container-fluid .wj-multirow {
    height: 700px;
    margin: 6px 0;
}
.container-fluid  .wj-detail{
    padding-left: 150px !important;
    height: 252px !important;
    width: auto !important;
}

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
    text-align: center !important;
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

