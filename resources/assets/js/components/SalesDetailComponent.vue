<template>
    <div>
        <loading-component :loading="loading" />

        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <div class="row">
                    <div class="col-md-12 text-right">
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                        <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                        <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                        <div class="pull-right">
                            <p class="btn btn-default btn-editing" v-show="(!isReadOnly)">編集中</p>
                        </div>
                    </div>
                </div>

                <div class="main-body">
                    <div class="row">
                        <div class="col-md-2">
                            <p class="item-label item-label-title">基本情報</p>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-3">
                            <label class="control-label">得意先名</label>
                            <input type="text" class="form-control" v-bind:readonly="true" v-model="requestInfo.customer_name">
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">部門名</label>
                            <input type="text" class="form-control" v-bind:readonly="true" v-model="requestInfo.charge_department_name">
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">得意先担当者名</label>
                            <input type="text" class="form-control" v-bind:readonly="true" v-model="requestInfo.charge_staff_name">
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">計上月</label>
                            <input type="text" class="form-control" v-bind:readonly="true" v-model="requestInfo.request_mon_text">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">売上期間</label>
                            <div class="input-group">
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="requestInfo.request_s_day">
                                <span class="input-group-addon">～</span>
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="requestInfo.request_e_day">
                            </div>
                        </div>
                    </div>


                    <div class="row" style="margin-top:20px">
                        <div class="col-md-12 request-grid-div">
                            <div v-bind:id="'requestGrid'" style="max-height:85px; border:none;"></div>
                        </div>
                    </div>

                    <div class="row btn-request-area">
                        <div class="col-md-12 text-right">
                            <button type="button" class="btn btn-save btn-request" v-on:click="save" v-bind:disabled="(isReadOnly)">保存</button>
                        </div>
                    </div>


                    <div class="sales-area">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="row" style="padding:0px 10px 5px 10px">
                                <p class="item-label item-label-title">案件売上明細</p>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-4">
                                <div style="padding:0px 10px 5px 10px">
                                    <div class="row">
                                        <p class="item-label">絞り込み機能</p>
                                    </div>
                                    <div class="row filter-area">
                                        <div class="col-md-4">
                                            <label class="control-label" style="padding-top:7px;">
                                                <el-checkbox v-model="filterInfo.pre_head" :indeterminate="filterInfo.pre_indeterminate" @change="changeFilterInfoChkBoxAllQreq('pre_head', 'pre', 'PRE', 'pre_indeterminate')">過去売上</el-checkbox>
                                            </label>
                                            <el-checkbox-group v-model="filterInfo.pre" style="margin-left:15px">
                                                <div class="checkbox" v-for="value in FILTER_INFO_LIST.PRE.key" :key="value">
                                                    <el-checkbox class="shipping-limit-list" :label="value" @change="changeFilterInfoChkBox('pre_head', 'pre', 'PRE', 'pre_indeterminate')">{{ FILTER_INFO_LIST.PRE.val[value] }}</el-checkbox>
                                                </div>
                                            </el-checkbox-group>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="control-label" style="padding-top:7px;">
                                                <el-checkbox v-model="filterInfo.active_head" :indeterminate="filterInfo.active_indeterminate" @change="changeFilterInfoChkBoxAllQreq('active_head', 'active', 'ACTIVE', 'active_indeterminate')">当月売上</el-checkbox>
                                            </label>
                                            <el-checkbox-group v-model="filterInfo.active" style="margin-left:15px">
                                                <div class="checkbox" v-for="value in FILTER_INFO_LIST.ACTIVE.key" :key="value">
                                                    <el-checkbox class="shipping-limit-list" :label="value" @change="changeFilterInfoChkBox('active_head', 'active', 'ACTIVE', 'active_indeterminate')">{{ FILTER_INFO_LIST.ACTIVE.val[value] }}</el-checkbox>
                                                </div>
                                            </el-checkbox-group>
                                        </div>
                                        <div class="col-md-4">
                                            <el-checkbox-group v-model="filterInfo.other">
                                                <div class="checkbox" v-for="value in FILTER_INFO_LIST.OTHER.key" :key="value">
                                                    <el-checkbox class="shipping-limit-list" :label="value" @change="changeFilterInfoOtherChkBox()">{{ FILTER_INFO_LIST.OTHER.val[value] }}</el-checkbox>
                                                </div>
                                            </el-checkbox-group>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-1">
                            </div>
                            
                            <div class="col-md-7">
                                <div style="margin-top:20px;">
                                    <table class="table table-bordered table-ex">
                                        <thead>
                                            <tr>
                                                <th>階層指定</th>
                                                <th style="width:120px">仕入金額合計</th>
                                                <th style="width:120px">売上金額合計</th>
                                                <th style="width:120px">粗利金額</th>
                                                <th style="width:80px">粗利率</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td>{{treeInfo.layer_name}}</td>
                                                <td class="text-right">{{treeInfo.cost_total|comma_format}}</td>
                                                <td class="text-right">{{treeInfo.sales_total|comma_format}}</td>
                                                <td class="text-right">{{treeInfo.profit_total|comma_format}}</td>
                                                <td class="text-right">{{treeInfo.gross_profit_rate|comma_format}}%</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>



                        <div class="row btn-sales-area">
                            <div class="col-md-12">
                                <button type="button" class="btn btn-yellow " v-on:click="btnAddSales(INIT_ROW_NEBIKI.sales_flg)" v-bind:disabled="(isReadOnly)">案件・階層値引き追加</button>
                                <button type="button" class="btn btn-save " v-on:click="btnAddSales(INIT_ROW_SALES.sales_flg)" v-bind:disabled="(isReadOnly)">新規売上明細追加</button>
                                <button type="button" class="btn btn-info " v-on:click="btnAddSales(INIT_ROW_COST_ADJUST.sales_flg)" v-bind:disabled="(isReadOnly)">調整金額追加</button>
                            </div>
                        </div>
                        <!-- 階層 -->
                        <div class="row">
                            <div class="col-md-2 layer-div">
                                <div v-bind:id="'quoteLayerTree'"></div>
                            </div>
                            
                            <!-- グリッド -->
                            <div class="col-md-10 sales-grid-div">
                                <div v-bind:id="'salesGrid'"></div>
                            </div>
                        </div>
                    </div>
                </div>
                

                <!-- ボタン -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-back pull-right" v-on:click="back">戻る</button>
                </div>
            </form>
            
        </div>
    </div>
</template>

<style>
.layer-div {
    border: 1px solid #bbb;
    padding-right: 0 !important;
    padding-left: 0 !important;
}
.sales-grid-div {
    padding-right: 0 !important;
    padding-left: 0 !important;
    height: 500px;
}
.sales-area {
    margin-top: 10px;
    margin-bottom: 10px;
}
.btn-request-area {
    margin-top: 3px;
    margin-bottom: 5px;
}
.btn-request{
    margin-top: 8px;
}
.btn-sales-area {
    margin-top: 10px;
    margin-bottom: 5px;
}
</style>

<script>

import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjDetail from '@grapecity/wijmo.grid.detail';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';
import { result } from 'lodash';

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        isShowEditBtn: false,
        isLocked: false,

        // グリッド初期値　値引き
        INIT_ROW_NEBIKI : {
            product_code: 'nebiki',
            order_no_list: '',
            product_name: '値引き',
            model: '',
            sales_stock_quantity: 0,
            sales_quantity: 0,
            stock_unit: '',
            unit: '',
            sales_graph: '',
            sales_graph_number: '',
            cost_unit_price: 0,
            cost_kbn: 0,
            cost_total: 0,
            cost_makeup_rate: 0.0,
            sales_unit_price: 0,
            sales_kbn: 0,
            sales_total: 0,
            sales_makeup_rate: 0.0,
            profit_total: 0,
            gross_profit_rate: 0.0,
            details_p_flg: true,
            details_c_flg: true,
            selling_p_flg: true,
            selling_c_flg: true,
            status: 0,
            btn_status_area: '',
            btn_add: '',
            btn_delete: '',

            quote_detail_id: 0,
            sales_id: 0,
            construction_id: 0,
            layer_flg: 0,
            set_flg: 0,
            parent_quote_detail_id: 0,
            seq_no: 0,
            depth: 0,
            tree_path: '0',
            filter_tree_path : '',
            sales_use_flg: 0,
            quote_quantity: 1.0,
            min_quantity: 1.0,
            sales_flg: 1,
            regular_price: 0,
            update_cost_unit_price: 0,
            update_cost_unit_price_d: '',
            bk_sales_unit_price: 0,
            sales_all_quantity:0,
            default_gross_profit_rate: 0.0,
            invalid_unit_price_flg: 0,
            intangible_flg: 1,
            draft_flg: 0,
        },
        // グリッド初期値　その他
        INIT_ROW_SALES : {
            product_code: 'sonota',
            order_no_list: '',
            product_name: 'その他',
            model: '',
            sales_stock_quantity: 0,
            sales_quantity: 0,
            stock_unit: '',
            unit: '',
            sales_graph: '',
            sales_graph_number: '',
            cost_unit_price: 0,
            cost_kbn: 0,
            cost_total: 0,
            cost_makeup_rate: 0.0,
            sales_unit_price: 0,
            sales_kbn: 0,
            sales_total: 0,
            sales_makeup_rate: 0.0,
            profit_total: 0,
            gross_profit_rate: 0.0,
            details_p_flg: true,
            details_c_flg: true,
            selling_p_flg: true,
            selling_c_flg: true,
            status: 0,
            btn_status_area: '',
            btn_add: '',
            btn_delete: '',

            quote_detail_id: 0,
            sales_id: 0,
            construction_id: 0,
            layer_flg: 0,
            set_flg: 0,
            parent_quote_detail_id: 0,
            seq_no: 0,
            depth: 0,
            tree_path: '0',
            filter_tree_path : '',
            sales_use_flg: 0,
            quote_quantity: 1.0,
            min_quantity: 1.0,
            sales_flg: 2,
            regular_price: 0,
            update_cost_unit_price: 0,
            update_cost_unit_price_d: '',
            bk_sales_unit_price: 0,
            sales_all_quantity:0,
            default_gross_profit_rate:0.0,
            invalid_unit_price_flg: 0,
            intangible_flg: 1,
            draft_flg: 0,
        },
        // グリッド初期値　仕入調整
        INIT_ROW_COST_ADJUST: {
            product_code: 'chousei',
            order_no_list: '',
            product_name: '調整',
            model: '',
            sales_stock_quantity: 0,
            sales_quantity: 0,
            stock_unit: '',
            unit: '',
            sales_graph: '',
            sales_graph_number: '',
            cost_unit_price: 0,
            cost_kbn: 0,
            cost_total: 0,
            cost_makeup_rate: 0.0,
            sales_unit_price: 0,
            sales_kbn: 0,
            sales_total: 0,
            sales_makeup_rate: 0.0,
            profit_total: 0,
            gross_profit_rate: 0.0,
            details_p_flg: true,
            details_c_flg: true,
            selling_p_flg: true,
            selling_c_flg: true,
            status: 0,
            btn_status_area: '',
            btn_add: '',
            btn_delete: '',

            quote_detail_id: 0,
            sales_id: 0,
            construction_id: 0,
            layer_flg: 0,
            set_flg: 0,
            parent_quote_detail_id: 0,
            seq_no: 0,
            depth: 0,
            tree_path: '0',
            filter_tree_path : '',
            sales_use_flg: 0,
            quote_quantity: 1.0,
            min_quantity: 1.0,
            sales_flg: 3,
            regular_price: 0,
            update_cost_unit_price: 0,
            update_cost_unit_price_d: '',
            bk_sales_unit_price: 0,
            sales_all_quantity:0,
            default_gross_profit_rate:0.0,
            invalid_unit_price_flg: 0,
            intangible_flg: 1,
            draft_flg: 0,
        },
        // 子グリッド
        INIT_ROW_SALES_DETAIL: {
            sales_sel_flg: true,
            sales_date: null,
            sales_flg: 1,
            delivery_date: null,
            delivery_no: '',
            sales_stock_quantity: 1,
            sales_quantity: 1,
            sales_unit_price: 0,
            sales_total: 0,
            memo: '',

            sales_detail_id: 0,
            quote_detail_id: 0,
            sales_id: 0,
            sales_update_date:'',
            delivery_id:0,
            return_id:0,
            request_id:0,
            
            filter_tree_path: '',
            min_quantity: 1.0,
            cost_unit_price:0,
            cost_total:0,
            notdelivery_flg:0,
            offset_delivery_id:0
        },


        // 絞り込み系
        FILTER_INFO_LIST: {},
        filterInfo: {
            pre_head:false,
            pre_indeterminate:false,
            pre:[],
            active_head:true,
            active_indeterminate:false,
            active:[],
            other:[],
            department_name: null,
            staff_name: null,
        },
        // 選択階層の情報表示
        treeInfo: {
            layer_name: '',
            cost_total: 0,
            sales_total: 0,
            gross_profit_rate: 0.0,
            profit_total: 0,
        },
        // 階層
        treeCtrl: null,
        // メイングリッド
        gridCtrl: null,
        // 子グリッドの管理者
        gridDetailProvider: null,
        // 子グリッドのデータ(sales_detail_list[filter_tree_path][連番])
        sales_detail_list: [],

        
        

        main: {},
        lock: {},


        requestGridCtrl: null,

        wjDetailGrid: null,

        gridLayout: [],
        gridSalesDetailLayout: [],
        // 非表示カラム
        INVISIBLE_COLS: [
            'quote_detail_id',
            'sales_id',
            'construction_id',
            'layer_flg',
            'set_flg',
            'parent_quote_detail_id',
            'seq_no',
            'depth',
            'tree_path',
            'filter_tree_path',
            'sales_use_flg',
            'quote_quantity',
            'min_quantity',
            'sales_flg',
            'regular_price',
            'update_cost_unit_price',
            'update_cost_unit_price_d',
            'bk_sales_unit_price',
            'sales_all_quantity',
            'default_gross_profit_rate',
            'invalid_unit_price_flg',
            'intangible_flg',
            'draft_flg',
            'details_p_flg',
            'selling_p_flg',
        ],

        // 削除した売上明細IDのリスト
        deleteSalesIdList: [],
        deleteSalesDetailIdList: [],

        // ツールチップ
        tooltip : new wjCore.Tooltip(),

        urlparam: '',
    }),
    props: {
        loginInfo: {},
        priceList: Array,
        salesDetailFlgList: {},
        salesFlgList: {},
        salesStatusList: {},
        notdeliveryFlgList: {},
        salesDetailFilterInfoList: {},
        requestStatusList: {},
        isOwnLock: Number,
        lockdata: {},
        defaultFilterInfo: {},
        //maindata: {},

        quoteInfo: {},
        requestInfo: {},
        treeDataList: Array,
        gridDataList: Array,
        gridDetailDataList: {},
        addLayerInfo: {},
    },
    created() {

        // ロックされているか
        if(this.rmUndefinedZero(this.lockdata.id) !== 0){
            if(this.isOwnLock === this.FLG_ON){
                // 自分がロックしている
                this.isShowEditBtn  = false;
                this.isReadOnly     = false;
                this.isLocked       = false;
            }else{
                this.isShowEditBtn  = false;
                this.isReadOnly     = true;
                this.isLocked       = true;
            }
        }else{
            // ロックしていない場合は読み取り専用で編集ボタン表示
            this.isShowEditBtn  = true;
            this.isReadOnly     = true;
        }

        // 確定済みの場合は読み取り専用なので編集ボタンも非表示 開始日になってない場合も読み取り専用
        if(this.requestInfo.status === this.requestStatusList.val.complete || !this.requestInfo.is_sales_started || !this.requestInfo.is_valid_sales_date){
            this.isShowEditBtn  = false;
        }


        // グリッドレイアウトセット
        this.gridRequestLayout      = this.getRequestGridLayout();
        this.gridLayout             = this.getGridLayout();
        this.gridSalesDetailLayout  = this.getSalesDetailGridLayout();

        // 定数
        this.FILTER_INFO_LIST = this.salesDetailFilterInfoList;
        // 当月売上をデフォルトで選択させる
        this.filterInfo.active = Object.values(this.FILTER_INFO_LIST.ACTIVE.key);
        this.filterInfo.other  = this.defaultFilterInfo.other;
        
    },
    mounted() {
        
        // if (!this.isLocked) {
        //     // 編集モードで開くか判定
        //     var query = window.location.search;
        //     if (this.isOwnLock === this.FLG_ON) {
        //         this.isReadOnly = false;
        //         this.isShowEditBtn = false;
        //     }else{
        //         // this.isReadOnly = true;
        //         // this.isShowEditBtn = false;
        //     }
        // }

        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }


        

        // 請求情報の型変換
        this.requestInfo.lastinvoice_amount = parseInt(this.rmUndefinedZero(this.requestInfo.lastinvoice_amount));
        this.requestInfo.offset_amount      = parseInt(this.rmUndefinedZero(this.requestInfo.offset_amount));
        this.requestInfo.deposit_amount     = parseInt(this.rmUndefinedZero(this.requestInfo.deposit_amount));
        this.requestInfo.receivable         = parseInt(this.rmUndefinedZero(this.requestInfo.receivable));
        this.requestInfo.different_amount   = parseInt(this.rmUndefinedZero(this.requestInfo.different_amount));
        this.requestInfo.carryforward_amount= parseInt(this.rmUndefinedZero(this.requestInfo.carryforward_amount));
        this.requestInfo.purchase_volume    = parseInt(this.rmUndefinedZero(this.requestInfo.purchase_volume));
        this.requestInfo.sales              = parseInt(this.rmUndefinedZero(this.requestInfo.sales));
        this.requestInfo.additional_discount_amount = parseInt(this.rmUndefinedZero(this.requestInfo.additional_discount_amount));
        this.requestInfo.consumption_tax_amount = parseInt(this.rmUndefinedZero(this.requestInfo.consumption_tax_amount));
        this.requestInfo.discount_amount    = parseInt(this.rmUndefinedZero(this.requestInfo.discount_amount));
        this.requestInfo.total_sales        = parseInt(this.rmUndefinedZero(this.requestInfo.total_sales));
        this.requestInfo.request_amount     = parseInt(this.rmUndefinedZero(this.requestInfo.request_amount));

        var gridDataList = [];
        for (var i = 0; i < this.gridDataList.length; i++) {
            var rec = this.gridDataList[i];
            
            rec['order_no_list']    = this.rmUndefinedBlank(rec['order_no_list']);
            rec['sales_id']         = parseInt(this.rmUndefinedZero(rec['sales_id']));
            rec['regular_price']    = parseInt(this.rmUndefinedZero(rec['regular_price']));
            rec['cost_unit_price']  = parseInt(this.rmUndefinedZero(rec['cost_unit_price']));
            rec['cost_total']       = parseInt(this.rmUndefinedZero(rec['cost_total']));
            rec['cost_makeup_rate'] = parseFloat(this.rmUndefinedZero(rec['cost_makeup_rate']));
            rec['sales_unit_price'] = parseInt(this.rmUndefinedZero(rec['sales_unit_price']));
            rec['sales_total']      = parseInt(this.rmUndefinedZero(rec['sales_total']));
            rec['sales_makeup_rate']= parseFloat(this.rmUndefinedZero(rec['sales_makeup_rate']));
            rec['profit_total']     = parseInt(this.rmUndefinedZero(rec['profit_total']));
            rec['gross_profit_rate']= parseFloat(this.rmUndefinedZero(rec['gross_profit_rate']));

            rec['quote_quantity']   = parseFloat(this.rmUndefinedZero(rec['quote_quantity']));
            rec['min_quantity']     = parseFloat(this.rmUndefinedZero(rec['min_quantity']));

            rec['selling_p_flg'] = this.FLG_ON === rec['selling_p_flg'];
            rec['selling_c_flg'] = this.FLG_ON === rec['selling_c_flg'];
            rec['details_p_flg'] = this.FLG_ON === rec['details_p_flg'];
            rec['details_c_flg'] = this.FLG_ON === rec['details_c_flg'];

            rec['status'] = this.rmUndefinedZero(rec['status']);
            gridDataList.push(rec);
        }

        // 子グリッドの値セット
        for(let filterTreePath in this.gridDetailDataList) {
            for(let salesDetailCnt in this.gridDetailDataList[filterTreePath]){
                var salesDetailRow = this.gridDetailDataList[filterTreePath][salesDetailCnt];
                salesDetailRow['sales_sel_flg']     = false;
                salesDetailRow['quote_id']          = parseInt(this.rmUndefinedZero(salesDetailRow['quote_id']));
                salesDetailRow['quote_detail_id']   = parseInt(this.rmUndefinedZero(salesDetailRow['quote_detail_id']));
                salesDetailRow['delivery_id']       = parseInt(this.rmUndefinedZero(salesDetailRow['delivery_id']));
                salesDetailRow['return_id']         = parseInt(this.rmUndefinedZero(salesDetailRow['return_id']));
                salesDetailRow['request_id']        = parseInt(this.rmUndefinedZero(salesDetailRow['request_id']));
                salesDetailRow['sales_flg']         = parseInt(this.rmUndefinedZero(salesDetailRow['sales_flg']));
                salesDetailRow['notdelivery_flg']   = parseInt(this.rmUndefinedZero(salesDetailRow['notdelivery_flg']));
                salesDetailRow['sales_stock_quantity']  = parseInt(this.rmUndefinedZero(salesDetailRow['sales_stock_quantity']));
                salesDetailRow['sales_quantity']    = parseFloat(this.rmUndefinedZero(salesDetailRow['sales_quantity']));
                salesDetailRow['sales_unit_price']  = parseInt(this.rmUndefinedZero(salesDetailRow['sales_unit_price']));
                salesDetailRow['sales_total']       = parseInt(this.rmUndefinedZero(salesDetailRow['sales_total']));
                salesDetailRow['cost_unit_price']   = parseInt(this.rmUndefinedZero(salesDetailRow['cost_unit_price']));
                salesDetailRow['cost_total']        = parseInt(this.rmUndefinedZero(salesDetailRow['cost_total']));
                salesDetailRow['delivery_quantity'] = parseInt(this.rmUndefinedZero(salesDetailRow['delivery_quantity']));
            }
        }
        this.sales_detail_list = this.gridDetailDataList;

        this.$nextTick(function() {
            var ctrl = this.createRequestGridCtrl('#requestGrid', [this.requestInfo]);
            this.requestGridCtrl = ctrl;

            var gridItemSource = new wjCore.CollectionView(gridDataList);
            var gridCtrl = this.createGridCtrl('#salesGrid', gridItemSource);
            this.gridCtrl = gridCtrl;

            var treeCtrl = this.createTreeCtrl('#quoteLayerTree', this.treeDataList);
            this.treeCtrl = treeCtrl;

            this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
            for (var i = 0; i < gridCtrl.collectionView.sourceCollection.length; i++) {
                var row = gridCtrl.collectionView.sourceCollection[i];
                this.setSalesDetailToGridRow(row.filter_tree_path, true);
            }
            var kbnIdList = this.getTreeKbnId(this.treeCtrl);
            this.setTreeInfo(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            this.setTreeSalesTotalToName();
            this.setRequestInfo();
            // 階層の名前に金額をセット
            //this.setTreeSalesTotalToName();
            //this.setRequestInfo();
            //this.filterGrid(this.FLG_ON,0,0,'トップ');
            //this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
        });
        

    },
    methods: {
        // ***** 検索条件 *****
        changeFilterInfoChkBox(headKay, valueKey, constKey, indeterminateKey){
            this.filterInfo[headKay] = this.filterInfo[valueKey].length === Object.keys(this.FILTER_INFO_LIST[constKey].key).length;
            this.filterInfo[indeterminateKey] = (!this.filterInfo[headKay] && this.filterInfo[valueKey].length > 0) ? true:false;
            // フィルターを掛ける
            var kbnIdList = this.getTreeKbnId(this.treeCtrl);
            this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            this.setTreeSalesTotalToName();
        },
        changeFilterInfoOtherChkBox(){
            // フィルターを掛ける
            var kbnIdList = this.getTreeKbnId(this.treeCtrl);
            this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            this.setTreeSalesTotalToName();
        },
        changeFilterInfoChkBoxAllQreq(headKay, valueKey, constKey, indeterminateKey){
            if(this.filterInfo[headKay]){
                this.filterInfo[valueKey] = Object.values(this.FILTER_INFO_LIST[constKey].key);
                this.filterInfo[indeterminateKey] = false;
            }else{
                this.filterInfo[valueKey] = [];
            }
            // フィルターを掛ける
            var kbnIdList = this.getTreeKbnId(this.treeCtrl);
            this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            this.setTreeSalesTotalToName();
        },

        /**
         * 【ボタンクリック】行追加
         * @param   値引き行か
         */
        btnAddSales(salesFlg){
            var gridData = this.gridCtrl.collectionView.sourceCollection;
            var checkTree = this.getCheckTree([this.treeCtrl.nodes[0].dataItem], []);
            if(checkTree.length === 0){
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            // チェック処理
            for(var i=0; i<checkTree.length; i++){
                var targetLayer = checkTree[i];
                if(targetLayer.top_flg !== this.FLG_ON){
                    if(this.parentIsSalesUseFlg(targetLayer.filter_tree_path)){
                        // 親に販売額利用の階層が含まれている
                        alert(MSG_ERROR_ADD_SALES_ROW);
                        return;
                    }
                }
            }

            for(var i=0; i<checkTree.length; i++){
                var targetLayer = checkTree[i];
                var emptyRow = null;
                switch(salesFlg){
                    case this.INIT_ROW_NEBIKI.sales_flg:
                        emptyRow = Vue.util.extend({}, this.INIT_ROW_NEBIKI);
                        break;
                    case this.INIT_ROW_SALES.sales_flg:
                        emptyRow = Vue.util.extend({}, this.INIT_ROW_SALES);
                        break;
                    case this.INIT_ROW_COST_ADJUST.sales_flg:
                        emptyRow = Vue.util.extend({}, this.INIT_ROW_COST_ADJUST);
                        break;
                }

                if(targetLayer.top_flg === this.FLG_ON){
                    var currentGridDataList = gridData.filter((rec) => {            
                        if(this.QUOTE_CONSTRUCTION_DEPTH === rec.depth){
                            return true;
                        }else{
                            return false;
                        }
                    });
                    var newFilterTreePath = 0;
                    var newSeqNo = 1;
                    if(currentGridDataList.length >= 1){
                        newFilterTreePath   = Math.max.apply(null,currentGridDataList.map(function(o){return parseInt(o.filter_tree_path);})) + 1;
                        newSeqNo            = Math.max.apply(null,currentGridDataList.map(function(o){return parseInt(o.seq_no);})) + 1;
                    }
                    emptyRow.depth      = this.QUOTE_CONSTRUCTION_DEPTH;      
                    emptyRow.construction_id    = 0;
                    emptyRow.filter_tree_path   = newFilterTreePath.toString();
                    emptyRow.seq_no             = newSeqNo;
                    //emptyRow.tree_path          = '0';
                    //emptyRow.parent_quote_detail_id   = 0;
                }else{
                    var endFilterTreePath = 0;
                    var newSeqNo          = 1;
                    var currentGridDataList = this.getChildGridDataList(this.gridCtrl, targetLayer.filter_tree_path, (targetLayer.depth+1));
                    if(currentGridDataList.length >= 1){
                        var tmpFilterTreePath   = currentGridDataList.reduce((a,b)=>this.getEndFilterTreePath(a.filter_tree_path)>this.getEndFilterTreePath(b.filter_tree_path)?a:b).filter_tree_path;
                        var endFilterTreePath   = this.getEndFilterTreePath(tmpFilterTreePath) + 1;
                        newSeqNo                = Math.max.apply(null,currentGridDataList.map(function(o){return parseInt(o.seq_no);})) + 1;
                    }
                    emptyRow.depth              = targetLayer.depth + 1;      
                    emptyRow.construction_id    = targetLayer.construction_id;
                    emptyRow.filter_tree_path   = targetLayer.filter_tree_path + this.TREE_PATH_SEPARATOR + endFilterTreePath;
                    emptyRow.seq_no             = newSeqNo;

                    // 選択した階層
                    var targetGridDataRow = this.gridCtrl.collectionView.sourceCollection.find((rec) => {
                        return (rec.filter_tree_path === targetLayer.filter_tree_path);
                    });
                    if(targetGridDataRow.depth >= 1){
                        emptyRow.tree_path          = targetGridDataRow.tree_path + this.TREE_PATH_SEPARATOR + targetGridDataRow.quote_detail_id;
                    }else{
                        emptyRow.tree_path          = targetGridDataRow.quote_detail_id;
                    }
                    emptyRow.parent_quote_detail_id   = targetGridDataRow.quote_detail_id;
                }
                // 行追加
                gridData.splice(gridData.length, 0, emptyRow);
                // 子グリッド追加
                this.addSalesDetail(emptyRow.filter_tree_path, emptyRow);
            }

            // グリッド再描画
            this.gridCtrl.collectionView.refresh();     
        },

        /**
         * 渡した行の親に販売額利用フラグかあるかどうか(再帰処理)
         * @param {*} filterTreePath    対象のフィルターツリーパス
         */
        parentIsSalesUseFlg(filterTreePath){
            var result = false;
            var row = this.gridCtrl.collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === filterTreePath);
                });
            if(row.sales_use_flg === this.FLG_ON){
                result = true;
            }else{
                if(row.depth !== this.QUOTE_CONSTRUCTION_DEPTH){
                    var filterTreePath = this.getParentFilterTreePath(row.filter_tree_path);
                    result = this.parentIsSalesUseFlg(filterTreePath);
                }
            }
            return result;
        },

        /**
         * 選択している階層を返す
         * @param items
         * @returns result
         */
        getCheckTree(items, result){
            for(var i =0;i<items.length;i++){
                if(this.treeCtrl.getNode(items[i]).element.childNodes[0].checked){
                    result.push(items[i]);
                }
                result = this.getCheckTree(items[i]['items'], result);
            }
            return result;
        },

        /**
         * 【ボタンクリック】子グリッドの行追加
         * @param rowNo 現在開いている階層の中でクリックした明細の行番号
         * @param row   クリックした明細
         */
        btnAddSalesDetail(rowNo, row){
            this.addSalesDetail(row.filter_tree_path, row);
            
            if(this.gridDetailProvider.isDetailVisible(rowNo)){
                this.gridDetailProvider.hideDetail(rowNo);
                this.gridDetailProvider.showDetail(rowNo);
            }
        },
        /**
         * 子グリッドの行追加
         * @param filterTreePath    明細のフィルターツリーパス
         * @param parentDetailRow   クリックした明細
         */
        addSalesDetail(filterTreePath, parentDetailRow){
            if(!Array.isArray(this.sales_detail_list[filterTreePath])){
                this.sales_detail_list[filterTreePath] = [];
            }
            var row = JSON.parse(JSON.stringify(this.INIT_ROW_SALES_DETAIL));
            row.quote_detail_id     = parentDetailRow.quote_detail_id;
            row.sales_id            = parentDetailRow.sales_id;
            row.filter_tree_path    = filterTreePath;
            // 売上種別
            switch(parentDetailRow.sales_flg){
                case this.INIT_ROW_NEBIKI.sales_flg:
                    // 値引き行の場合は「値引き」
                    row.sales_flg = this.salesFlgList['val']['discount'];
                    break;
                case this.INIT_ROW_SALES.sales_flg:
                    // 新規売上行の場合は「出来高」
                    row.sales_flg = this.salesFlgList['val']['production'];
                    break;
                case this.INIT_ROW_COST_ADJUST.sales_flg:
                    // 仕入調整行の場合は「仕入調整」
                    row.sales_flg = this.salesFlgList['val']['cost_adjust'];
                    break;
                default :
                    // 親の情報をセット
                    row.min_quantity        = parentDetailRow.min_quantity;
                    row.sales_unit_price    = parentDetailRow.sales_unit_price;
                    row.cost_unit_price     = parentDetailRow.cost_unit_price;
                    break;
            }
            this.sales_detail_list[filterTreePath].push(row);
            this.changeSalesDetailRowData(row);
        },
        /**
         * 【ボタンクリック】子グリッドの行削除
         * 子グリッドの行が無ければ親明細も削除する
         * @param rowNo 現在開いている階層の中でクリックした明細の行番号
         * @param row   クリックした明細
         */
        btnDeleteSalesDetail(rowNo, row){
            var filterTreePath = row.filter_tree_path;
            if(Array.isArray(this.sales_detail_list[filterTreePath])){
                var deleteCnt = 0;
                for(var i=0; i<this.sales_detail_list[filterTreePath].length; i++){
                    if(this.sales_detail_list[filterTreePath][i].sales_sel_flg){
                        var salesDetailId = this.sales_detail_list[filterTreePath][i].sales_detail_id;
                        if(this.rmUndefinedZero(salesDetailId) !== 0){
                            this.deleteSalesDetailIdList.push(salesDetailId);
                        }
                        this.sales_detail_list[filterTreePath].splice(parseInt(i), 1);
                        i--;
                        deleteCnt++;
                    }
                }
                
                if(deleteCnt === 0){
                    alert(MSG_ERROR_NO_SELECT);
                }else{
                    if(this.gridDetailProvider.isDetailVisible(rowNo)){
                        this.gridDetailProvider.hideDetail(rowNo);
                        this.gridDetailProvider.showDetail(rowNo);
                    }
                    // 子グリッドが無くなったら親グリッドを削除する
                    if(this.sales_detail_list[filterTreePath].length === 0){
                        switch(row.sales_flg){
                            case this.INIT_ROW_NEBIKI.sales_flg:
                            case this.INIT_ROW_SALES.sales_flg:
                            case this.INIT_ROW_COST_ADJUST.sales_flg:
                                // 値引き行
                                // 新規売上行
                                var sourceCollection = this.gridCtrl.collectionView.sourceCollection;
                                for (var i = 0; i < sourceCollection.length; i++) {
                                    if(sourceCollection[i].filter_tree_path === filterTreePath){
                                        var salesId = sourceCollection[i].sales_id;
                                        if(this.rmUndefinedZero(salesId) !== 0){
                                            this.deleteSalesIdList.push(salesId);
                                        }
                                        sourceCollection.splice(i, 1);
                                        break;
                                    }
                                }
                                break;
                        }
                        // TODO
                        this.gridCtrl.collectionView.refresh();
                    }
                    // 親にセットする
                    this.setSalesDetailToGridRow(filterTreePath);
                    // グリッドに対してリフレッシュ
                    this.gridCtrl.refresh();
                }
            }else{
                alert(MSG_ERROR_NO_SELECT);
            }
            //console.log(this.gridDetailProvider.grid_detail);    
        },

        /**
         * 開閉ボタンの表示可否
         * 販売額利用でない階層はfalse
         * @param row
         */
        isVisibleToggleBtn(row){
            var result = true;
            if(row.layer_flg === this.FLG_ON && row.sales_use_flg !== this.FLG_ON){
                result = false;
            }
            return result;
        },

        /**
         * 子グリッドに行追加できるか
         * @param row
         */
        isValidAddSalesDetailRow(row){
            var result = true;
            result = this.isVisibleToggleBtn(row);
            if(row.sales_flg === this.INIT_ROW_SALES.sales_flg || row.sales_flg === this.INIT_ROW_NEBIKI.sales_flg || row.sales_flg === this.INIT_ROW_COST_ADJUST.sales_flg){
                result = false;
            }else{
                if(row.construction_id === this.addLayerInfo.id){
                    // 追加部材配下はNG
                    result = false;
                }else if(row.status === this.salesStatusList['val']['applying']){
                    // 申請中の場合もNG
                    result = false;
                }else if(row.invalid_unit_price_flg === this.FLG_ON){
                    // 販売額利用フラグ配下や階層はNG
                    result = false;
                }else if(row.layer_flg === this.FLG_OFF && this.rmUndefinedZero(row.product_id) === 0 && row.intangible_flg === this.FLG_OFF){
                    // 階層ではなく商品IDがない有形品
                    result = false;
                }else if(row.layer_flg === this.FLG_OFF && row.draft_flg === this.FLG_ON){
                    // 階層でなく商品が仮登録
                    result = false;
                }else if(row.layer_flg === this.FLG_OFF && row.set_flg === this.FLG_ON){
                    // 明細状態の一式商品
                    result = false;
                }
            }
            return result;
        },

        /**
         * 子グリッドの行を削除できるか
         * 販売額利用でない階層はfalse
         * @param row
         */
        isValidDeleteSalesDetailRow(row){
            var result = true;
            result = this.isVisibleToggleBtn(row);
            if(row.invalid_unit_price_flg === this.FLG_ON){
                // 販売額利用フラグ配下や階層はNG
                result = false;
            }else if(row.status === this.salesStatusList['val']['applying']){
                // 申請中の場合もNG
                result = false;
            }
            return result;
        },        


        // グリッド生成
        createRequestGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridRequestLayout,
                showSort: false,
                allowSorting: false,
                //keyActionEnter: wjGrid.KeyAction.None,
                keyActionEnter:wjGrid.KeyAction.Cycle,
                keyActionTab:wjGrid.KeyAction.Cycle,
            });

            gridCtrl.rowHeaders.columns[0].width = 1;

            gridCtrl.isReadOnly = this.isReadOnly;


            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var dataItem = panel.rows[r].dataItem;
                    cell.style.color = '';

                    cell.style.textAlign = 'left';
                    if(cell.classList.contains('text-right')){
                        cell.style.textAlign = 'right';
                    }
                    
                    switch(col.name){
                        case 'delivery_area':
                            if(dataItem !== undefined){
                                cell.innerHTML = this.$options.filters.comma_format(dataItem.delivery_quantity) + '／' +  this.$options.filters.comma_format(dataItem.return_quantity);
                            }
                            break;
                        case 'gross_profit_rate':
                            // 粗利
                            if(dataItem !== undefined){
                                cell.innerHTML = dataItem.gross_profit_rate + '％';
                            }
                            break;
                        case 'status_text':
                            if(dataItem !== undefined){
                                cell.innerHTML = this.requestStatusList.list[dataItem.status];
                            }
                            break;
                        case 'discount_flg':
                            // 値引申請
                            if(dataItem !== undefined){
                                cell.innerHTML = dataItem.discount_flg ? 'あり':'なし';
                            }
                            break;
                        case 'cost_edit_flg':
                            // 仕入調整
                            if(dataItem !== undefined){
                                cell.innerHTML = dataItem.cost_edit_flg ? 'あり':'なし';
                            }
                            break;
                        

                    }
                }
            }.bind(this);

            // キーダウンイベント
            // gridCtrl.hostElement.addEventListener('keydown', function (e) {
            //     this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            // }.bind(this), true);

            // var tmp = new CustomGridEditor(gridCtrl, 'request_s_day', wjcInput.InputDate, {
            //     format: "d"
            // }, 2, 2, 1);
            // var tmp = new CustomGridEditor(gridCtrl, 'shipment_at', wjcInput.InputDate, {
            //     format: "d"
            // }, 2, 2, 1);
        
            return gridCtrl;
        },
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayout,
                showSort: false,
                allowSorting: false,
                //keyActionEnter: wjGrid.KeyAction.None,
                keyActionEnter:wjGrid.KeyAction.Cycle,
                keyActionTab:wjGrid.KeyAction.Cycle,
            });

            gridCtrl.isReadOnly = this.isReadOnly;
            gridCtrl.rowHeaders.columns[0].width = 30;

            // ダブルクリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "dblclick", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.RowHeader){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined' && record.layer_flg === this.FLG_ON){
                        this.selectTree(this.treeCtrl, 'filter_tree_path', record.filter_tree_path);
                    }
                }
            });

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

                // 値引き/出来高/仕入調整　売上確定している行は変更禁止
                if(row.sales_flg !== this.salesDetailFlgList.val.quote){
                    e.cancel = this.salesDetailIsAllComplete(row);
                }
                
                switch (col.name) {
                    case 'sales_unit_price':
                        // 販売単価
                        if(row.status === this.salesStatusList['val']['applying'] || row.invalid_unit_price_flg === this.FLG_ON){
                            e.cancel = true;
                        }
                        break;
                    case 'sales_makeup_rate':
                        // 販売掛率
                        if(this.rmUndefinedZero(row.regular_price) === 0 || row.status === this.salesStatusList['val']['applying'] || row.invalid_unit_price_flg === this.FLG_ON){
                            e.cancel = true;
                        }
                        break;
                    case 'gross_profit_rate':
                        // 粗利率
                        if(this.rmUndefinedZero(row.cost_unit_price) === 0 || row.status === this.salesStatusList['val']['applying'] || row.invalid_unit_price_flg === this.FLG_ON){
                            e.cancel = true;
                        }
                        break;
                }
                gridCtrl.collectionView.commitEdit();
                var kbnIdList = this.getTreeKbnId(this.treeCtrl);
                this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            }.bind(this));
            
            // 行編集開始後イベント
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                
            }.bind(this));

            // セル編集後イベント
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    case 'sales_unit_price':
                        // 販売単価
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.setSalesToSalesDetailGridRow(row);
                        this.setSalesDetailToGridRow(row.filter_tree_path);
                        break;
                    case 'sales_makeup_rate':
                        // 販売掛率
                        this.calcTreeGridChangeMakeupRate(row, false);
                        this.setSalesToSalesDetailGridRow(row);
                        this.setSalesDetailToGridRow(row.filter_tree_path);
                        break;
                    case 'gross_profit_rate':
                        // 粗利率
                        this.calcTreeGridChangeGrossProfitRate(row);
                        this.setSalesToSalesDetailGridRow(row);
                        this.setSalesDetailToGridRow(row.filter_tree_path);
                        break;
                }
                gridCtrl.collectionView.commitEdit();
                var kbnIdList = this.getTreeKbnId(this.treeCtrl);
                this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            }.bind(this));

            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });

            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                var disabled = this.isReadOnly?'disabled':'';
                var col = gridCtrl.getBindingColumn(panel, r, c);
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    var lblChkText = '';

                    switch(col.name){
                        case 'details_p_flg':
                            // lblChkText = '明細印字';
                        case 'details_c_flg':
                            lblChkText = lblChkText !== '' ? lblChkText : '明細印字';
                        case 'selling_p_flg':
                            // lblChkText = lblChkText !== '' ? lblChkText : '売価印字';
                        case 'selling_c_flg':
                            lblChkText = lblChkText !== '' ? lblChkText : '売価印字';
                            var checkedCount = 0;
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != '') {
                                    if (gridCtrl.rows[i].dataItem[col.name] == true) {checkedCount++;}
                                }
                            }
                            var checkBox = '<input type="checkbox" style="margin-right:3px;">' + lblChkText;
                            if(this.isReadOnly){
                                checkBox = '<input type="checkbox" style="margin-right:3px" disabled="true">' + lblChkText;
                            }
                            cell.innerHTML = checkBox;
                            var checkBox = cell.firstChild;
                            checkBox.checked = checkedCount > 0;
                            checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                            checkBox.addEventListener('click', function (e) {
                                gridCtrl.beginUpdate();
                                for (var i = 0; i < gridCtrl.rows.length; i++) {
                                    if (this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != '') {
                                        gridCtrl.rows[i].dataItem[col.name] = checkBox.checked;
                                        //gridCtrl.setCellData(i, c, checkBox.checked);
                                    }
                                }
                                gridCtrl.endUpdate();
                            }.bind(this));
                            break;
                    }
                    
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    var dataItem = panel.rows[r].dataItem;
                    cell.style.color = '';
                    cell.style.backgroundColor = ''


                    cell.style.textAlign = 'left';
                    if(cell.classList.contains('text-right')){
                        cell.style.textAlign = 'right';
                    }

                    // グリッドの行の色変更
                    if(dataItem !== undefined){
                        switch(dataItem.sales_flg){
                            case this.INIT_ROW_NEBIKI.sales_flg:
                                cell.style.backgroundColor = '#f2dede';
                                break;
                            case this.INIT_ROW_SALES.sales_flg:
                                cell.style.backgroundColor = '#fcf8e3';
                                break;
                            case this.INIT_ROW_COST_ADJUST.sales_flg:
                                cell.style.backgroundColor = '#ccf7fa';
                                break;
                        }
                        
                    }

                    switch(col.name){
                        case 'order_no_list':
                            if(dataItem !== undefined){
                                if(dataItem.sales_flg === this.INIT_ROW_NEBIKI.sales_flg || dataItem.sales_flg === this.INIT_ROW_SALES.sales_flg || dataItem.sales_flg === this.INIT_ROW_COST_ADJUST.sales_flg){
                                    var text = '';
                                    text = '－';
                                    cell.innerHTML = text;
                                }
                            }
                            break;
                        case 'sales_graph':
                            // 売上グラフ
                            if(dataItem !== undefined){
                                if(dataItem.layer_flg !== this.FLG_ON){
                                    cell.style.backgroundColor = '';
                                }
                                if(this.rmUndefinedZero(dataItem.quote_quantity) !== 0){
                                    cell.style.padding = '0px';
                                    
                                    // 見積数量
                                    var quoteQuantity = parseFloat(dataItem.quote_quantity);

                                    var headHtml = '<div class="grid-graph-cell">';
                                    var cellHtml = '';
                                    
                                    var detailRecordList = this.sales_detail_list[dataItem.filter_tree_path];

                                    var colorList = {
                                        grey:0,     // 確定
                                        blue:0,     // 未確定の納品
                                        yellow:0,   // 未確定の未納
                                    };

                                    var returnQuantity = {
                                        fixedReturn:0,
                                        unfixedReturn:0,
                                        remainingReturn:0,
                                    };

                                    var fixedReturn     = 0;
                                    var unfixedReturn   = 0;

                                    if(this.bigNumberGte(dataItem.sales_all_quantity, 0.01)){
                                        for(var i=0; Array.isArray(detailRecordList) && i < detailRecordList.length; i++){
                                            var row = detailRecordList[i];
                                            if(this.rmUndefinedBlank(row.sales_update_date) !== ''){
                                                if(row.sales_flg === this.salesFlgList['val']['return']){
                                                    // 確定の返品数
                                                    returnQuantity.fixedReturn = this.bigNumberPlus(returnQuantity.fixedReturn, row.sales_quantity, true);
                                                }
                                            }else{
                                                if(row.sales_flg === this.salesFlgList['val']['return']){
                                                    // 未確定の返品数
                                                    returnQuantity.unfixedReturn = this.bigNumberPlus(returnQuantity.unfixedReturn, row.sales_quantity, true);
                                                }
                                            }
                                        }

                                        for(var i=0; Array.isArray(detailRecordList) && i < detailRecordList.length; i++){
                                            var row = detailRecordList[i];
                                            if(this.rmUndefinedBlank(row.sales_update_date) !== ''){
                                                // 売上確定 
                                                // 無効行と未売は含まない
                                                if(row.notdelivery_flg !== this.notdeliveryFlgList['val']['invalid'] && row.sales_flg !== this.salesFlgList['val']['not_sales'] && row.sales_flg !== this.salesFlgList['val']['return']){
                                                    colorList.grey = this.bigNumberPlus(colorList.grey, row.sales_quantity, true);
                                                }
                                            }else{
                                                switch(row.sales_flg){
                                                    case this.salesFlgList['val']['delivery']:
                                                        // 売上確定
                                                        if(row.notdelivery_flg !== this.notdeliveryFlgList['val']['invalid']){
                                                            colorList.blue = this.bigNumberPlus(colorList.blue, row.sales_quantity, true);
                                                        }
                                                        break;
                                                    case this.salesFlgList['val']['not_delivery']:
                                                        colorList.yellow = this.bigNumberPlus(colorList.yellow, row.sales_quantity, true);
                                                        break;
                                                }
                                            }
                                        }

                                        // 確定済みから減らす　　相殺しきれなかった未確定分の返品数(returnQuantity.fixedReturn)
                                        this.calcFixedReturn(colorList, returnQuantity, 'fixedReturn');
                                        // 未確定の納品と未納から減らす　相殺しきれなかった未確定分の返品数(returnQuantity.unfixedReturn)
                                        this.calcUnfixedReturn(colorList, returnQuantity, 'unfixedReturn');
                                        
                                        // 相殺しきれなかった、確定/未確定分の返品数の合計
                                        returnQuantity.remainingReturn = this.bigNumberPlus(returnQuantity.fixedReturn, returnQuantity.unfixedReturn, true);
                                        // 残がマイナスの場合、確定か未確定のどちらか、または両方とも相殺しきれなかった
                                        if(this.bigNumberGt(0, returnQuantity.remainingReturn)){
                                            if(this.bigNumberGt(colorList.grey, 0)){
                                                // 確定済みから減らす
                                                this.calcFixedReturn(colorList, returnQuantity, 'remainingReturn');
                                            }else{
                                                // 未確定から減らす
                                                this.calcUnfixedReturn(colorList, returnQuantity, 'remainingReturn');
                                            }
                                        }


                                        for(let colorName in colorList) {
                                            // 色リスト分だけ回す
                                            var widthVal = this.bigNumberTimes(this.bigNumberDiv(colorList[colorName], quoteQuantity, true), 100);
                                            var styleVal = 'width:' + widthVal + '%;';
                                            var classVal = 'item ' + 'bg-' + colorName;
                                            cellHtml += '<div class="' + classVal + '" style="' + styleVal + '"></div>';
                                        }
                                            

                                        if(this.bigNumberGte(dataItem.sales_all_quantity, quoteQuantity)){
                                            cellHtml = '<div class="bg-green" style="width:100%"></div>';
                                        }

                                    }
                                    
                                    cell.innerHTML = headHtml + cellHtml + '</div>';
                                } else {
                                    cell.style.padding = '0px';
                                    
                                    // 見積数量
                                    var quoteQuantity = parseFloat(dataItem.quote_quantity);

                                    var headHtml = '<div class="grid-graph-cell">';
                                    var cellHtml = '';
                                    if(this.rmUndefinedZero(quoteQuantity) == 0 && this.rmUndefinedZero(dataItem.sales_all_quantity) > 0){
                                        // 見積数が0個で、納品が1個でもされている場合
                                        cellHtml = '<div class="bg-white" style="width:100%; border:solid 2px #ff0000;"></div>';
                                    }

                                    cell.innerHTML = headHtml + cellHtml + '</div>';
                                }
                            }
                            
                            if (cellHtml == undefined) {
                                cellHtml = '<div></div>';
                            }

                            cell.innerHTML = headHtml + cellHtml + '</div>';
                            break;
                        case 'sales_graph_number':
                            if(dataItem !== undefined){
                                // 表示加工
                                cell.innerHTML ='<div style="float: left;">' + 
                                                    this.$options.filters.comma_format(dataItem.sales_all_quantity) +
                                                '</div>';

                                cell.innerHTML += '<div style="float: right;">' +
                                                    this.$options.filters.comma_format(dataItem.quote_quantity) +
                                                '</div>';
                            }
                            break;
                        case 'cost_unit_price':
                            // 変更前仕入単価
                            if(dataItem !== undefined){
                                cell.id = 'update-cost-unit-price-cell-' + dataItem.filter_tree_path;
                                
                                if(this.bigNumberGt(this.rmUndefinedZero(dataItem.update_cost_unit_price), 1)){
                                    var content = this.$options.filters.comma_format(dataItem.update_cost_unit_price);

                                    this.tooltip.setTooltip(
                                        '#' + cell.id,
                                        content
                                    )
                                    
                                }
                                
                            }
                            break;
                        case 'cost_kbn':
                        case 'sales_kbn':
                            if(dataItem !== undefined){
                                var kbnText = '';
                                if(dataItem.sales_flg !== this.INIT_ROW_NEBIKI.sales_flg && dataItem.sales_flg !== this.INIT_ROW_SALES.sales_flg && dataItem.sales_flg !== this.INIT_ROW_COST_ADJUST.sales_flg){
                                    var kbnVal = this.priceList.find((rec) => {
                                        return (rec.value_code === dataItem[col.name]);
                                    });
                                    kbnText = kbnVal.value_text_1;
                                }
                                cell.innerHTML = kbnText;
                            }
                            break;
                        case 'details_p_flg':
                        case 'details_c_flg':
                        case 'selling_p_flg':
                        case 'selling_c_flg':
                            // データ取得
                            cell.style.textAlign = 'center';
                            if(dataItem !== undefined){
                                if (dataItem[col.name]) {
                                    var box = '<input type="checkbox" checked>';
                                } else {
                                    var box = '<input type="checkbox">';
                                }

                                var isDisabled = this.isReadOnly;
                                // 値引き/出来高/仕入調整　売上確定している行は変更禁止
                                if(!isDisabled && dataItem.sales_flg !== this.salesDetailFlgList.val.quote){
                                    isDisabled = this.salesDetailIsAllComplete(dataItem);
                                }
                                
                                if(isDisabled){
                                    box = '<input type="checkbox" disabled="true">';
                                }
                                cell.innerHTML = box;
                                var checkBox = cell.firstChild;
                                checkBox.addEventListener('click', function (e) {
                                    dataItem[col.name] = !dataItem[col.name];
                                    gridCtrl.collectionView.commitEdit();
                                    gridCtrl.refresh();
                                });
                            }
                            break;
                        case 'status':
                            cell.style.textAlign = 'center';
                            if(dataItem !== undefined){
                                cell.innerHTML = this.salesStatusList['list'][dataItem.status];

                                if(dataItem.invalid_unit_price_flg === this.FLG_ON && dataItem.status !== this.salesStatusList['val']['applying']){
                                    cell.innerHTML = '－';
                                }
                            }
                            break;
                        case 'btn_status_area':
                            cell.style.padding = '0px';
                            if(dataItem !== undefined){
                                var rId1 = 'status-approve-' + dataItem.filter_tree_path;
                                var rId2 = 'status-sendback-' + dataItem.filter_tree_path;
                                
                                
                                var isDisable = this.isReadOnly;
                                if(!isDisable){
                                    if(dataItem.status !== this.salesStatusList['val']['applying'] || dataItem.chief_staff_id !== this.loginInfo.id){
                                        // 申請中以外はボタン非活性
                                        // 承認部門の部門長以外でのログイン時はボタン非活性
                                        isDisable = true;
                                    }
                                }
                                

                                var div = document.createElement('div');
                                div.classList.add('btn-group', 'status-btn-group');
                                div.setAttribute("data-toggle","buttons");

                                var btnApprove = document.createElement('button');
                                btnApprove.type = 'button';
                                btnApprove.id = rId1;
                                btnApprove.classList.add('btn', 'btn-success', 'btn-status');
                                btnApprove.innerHTML = '認';
                                btnApprove.disabled = isDisable;

                                btnApprove.addEventListener('click', function (e) {
                                    this.btnChangeSalesStatus(true, dataItem.filter_tree_path);
                                }.bind(this));

                                div.appendChild(btnApprove);

                                var btnSendback = document.createElement('button');
                                btnSendback.type = 'button';
                                btnSendback.id = rId2;
                                btnSendback.classList.add('btn', 'btn-danger', 'btn-status');
                                btnSendback.innerHTML = '否';
                                btnSendback.disabled = isDisable;

                                btnSendback.addEventListener('click', function (e) {
                                    this.btnChangeSalesStatus(false, dataItem.filter_tree_path);
                                }.bind(this));

                                div.appendChild(btnSendback);

                                cell.appendChild(div);
                                
                            }
                            
                            break;
                        case 'btn_add':
                            if(dataItem !== undefined){
                                if(disabled === ''){
                                    if(!this.isValidAddSalesDetailRow(dataItem)){
                                        disabled = 'disabled';
                                    }
                                }
                            }
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-primary multi-grid-btn" '+disabled+'><i class="el-icon-plus"></i></button>';
                            break;
                        case 'btn_delete':
                            if(dataItem !== undefined){
                                if(disabled === ''){
                                    if(!this.isValidDeleteSalesDetailRow(dataItem)){
                                        disabled = 'disabled';
                                    }
                                }
                            }
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-delete multi-grid-btn" '+disabled+'><i class="el-icon-minus"></i></button>';
                            break;

                    }
                }
            }.bind(this);

            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                //this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            }.bind(this), true);

            // 列ヘッダー結合
            gridCtrl.allowMerging = wjGrid.AllowMerging.All;
            // for (var i = 0; i < gridCtrl.columns.length; i++) {
            //     if (i == 10){
            //         gridCtrl.columnHeaders.setCellData(0, i, gridCtrl.columnHeaders.getCellData(1, i));
            //         gridCtrl.columnHeaders.columns[i].allowMerging = true;
            //     }
            // }  

            // 子グリッド定義
            var tmpGridDetailProvider = new wjDetail.FlexGridDetailProvider(gridCtrl, {
                isAnimated: false,
                maxHeight: 500,

                createDetailCell: function (row) {
                    var cell = document.createElement('div');
                    gridCtrl.hostElement.appendChild(cell);
                    var detailGrid = new wjMultiRow.MultiRow(cell, {
                        headersVisibility: wjGrid.HeadersVisibility.Column,
                        autoGenerateColumns: false,
                        itemsSource: this.getDetails(row.dataItem),
                        layoutDefinition: this.gridSalesDetailLayout,
                        keyActionEnter:wjGrid.KeyAction.Cycle,
                        keyActionTab:wjGrid.KeyAction.Cycle,
                        showSort: false,
                        allowSorting: false,
                    });

                    detailGrid.itemFormatter = function(panel, r, c, cell) {
                        // 列ヘッダ中央寄せ
                        if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                            cell.style.textAlign = 'center';
                        }else if (panel.cellType == wjGrid.CellType.Cell) {
                            var col = gridCtrl.getBindingColumn(panel, r, c);
                            var dataItem = panel.rows[r].dataItem;
                            cell.style.color = '';
                            cell.style.backgroundColor = ''
                            cell.style.textAlign = 'left';
                            if(cell.classList.contains('text-right')){
                                cell.style.textAlign = 'right';
                            }

                            // グレーアウト
                            if(dataItem !== undefined){
                                if(this.rmUndefinedBlank(dataItem.sales_update_date) !== ''){
                                    // 確定行
                                    cell.style.backgroundColor = '#E1FAE1';
                                }
                                if(dataItem.notdelivery_flg === this.notdeliveryFlgList['val']['invalid']){
                                    // 無効行
                                    cell.style.backgroundColor = 'lightgrey';
                                }
                            }

                            switch(col.name){
                                case 'sales_sel_flg':
                                    if(dataItem !== undefined){
                                        cell.style.textAlign = 'center';
                                        var isDelivery = (dataItem.sales_flg === this.salesFlgList['val']['delivery'] || dataItem.sales_flg === this.salesFlgList['val']['return']);
                                        if(this.rmUndefinedBlank(dataItem.sales_update_date) !== '' || isDelivery || dataItem.notdelivery_flg === this.notdeliveryFlgList['val']['invalid']){
                                            cell.childNodes[0].disabled = true;
                                        }
                                    }
                                    break;
                            }
                        }
                    }.bind(this);

                    // セル編集確定前イベント
                    detailGrid.beginningEdit.addHandler(function (s, e) {
                        // 編集したカラムを特定
                        var editColNm = detailGrid.getBindingColumn(e.panel, e.row, e.col).name;
                        // 編集行データ取得
                        var row = s.collectionView.currentItem;
                        // 編集行の親明細を取得
                        var parentRow = this.getParentGridRow(row.filter_tree_path);
                        // 階層選択させない
                        this.treeCtrl.isDisabled = true;

                        // 値引 or 出来高 or 仕入調整
                        var isAddSales = (row.sales_flg === this.salesFlgList['val']['discount'] || row.sales_flg === this.salesFlgList['val']['production'] || row.sales_flg === this.salesFlgList['val']['cost_adjust']);
                        var isDelivery = (row.sales_flg === this.salesFlgList['val']['delivery'] || row.sales_flg === this.salesFlgList['val']['return']);

                        if(this.rmUndefinedBlank(row.sales_update_date) === '' && parentRow.status !== this.salesStatusList['val']['applying']){
                            // if(row.notdelivery_flg === this.notdeliveryFlgList['val']['create'] || (row.notdelivery_flg === this.notdeliveryFlgList['val']['invalid'] && row.sales_update_date !== null)){
                            if(this.rmUndefinedBlank(row.sales_update_date) !== ''){
                                // 確定済みのデータは売上日を変更できない
                                this.gridDetailProvider.cge_sales_date[row.filter_tree_path].control.isDisabled = true;
                            }else{
                                this.gridDetailProvider.cge_sales_date[row.filter_tree_path].control.isDisabled = false;
                            }
                            
                            switch (editColNm) {
                                case 'sales_flg':
                                    if(parentRow.sales_flg === this.INIT_ROW_NEBIKI.sales_flg ||
                                    parentRow.sales_flg === this.INIT_ROW_SALES.sales_flg ||
                                    parentRow.sales_flg === this.INIT_ROW_COST_ADJUST.sales_flg ||
                                    isDelivery){
                                        // 売上種別は変更できない
                                        e.cancel = true;
                                    }
                                    break;
                                case 'sales_stock_quantity':
                                    if(isAddSales || isDelivery){
                                        // 売上数は変更できない
                                        e.cancel = true;
                                    }
                                    break;
                                case 'sales_unit_price':
                                    if(!isAddSales){
                                        // 販売単価は変更できない
                                        e.cancel = true;
                                    }
                                    break;
                            }
                        }else{
                            // 売上確定データと売上が申請中の場合は変更できない
                            this.gridDetailProvider.cge_sales_date[row.filter_tree_path].control.isDisabled = true;
                            e.cancel = true;
                        }

                        if(e.cancel){
                            if(editColNm !== 'sales_date'){
                                // 売上日以外の時は編集モードに入らないので解除
                                this.treeCtrl.isDisabled = false;
                            }
                        }
                    }.bind(this));

                    // セル編集後イベント
                    detailGrid.cellEditEnded.addHandler(function(s, e) {
                        detailGrid.beginUpdate();
                        // 編集したカラムを特定
                        var editColNm = detailGrid.getBindingColumn(e.panel, e.row, e.col).name;       // 取れるのは上段の列名
                        // 編集行データ取得
                        var row = s.collectionView.currentItem;

                        // 親明細取得
                        var parentRow = this.getParentGridRow(row.filter_tree_path);

                        switch (editColNm) {
                            case 'sales_flg':
                                if(row.sales_flg === this.salesFlgList['val']['not_sales']){
                                    // 未売の販売単価は0
                                    row.sales_unit_price = 0;
                                }else{
                                    row.sales_unit_price = parentRow.sales_unit_price;
                                }
                                this.changeSalesDetailRowData(row);
                                break;
                            case 'sales_date':
                                if(this.rmUndefinedBlank(row.sales_date) !== ''){
                                    // 型変換
                                    row.sales_date = moment(row.sales_date).format(FORMAT_DATE);
                                }
                            case 'sales_stock_quantity':
                            case 'sales_unit_price':
                                this.changeSalesDetailRowData(row);
                                break;
                        }
                        this.treeCtrl.isDisabled = false;
                        detailGrid.endUpdate();
                    }.bind(this));

                    // エラーメッセージ表示
                    // var tip = new wjCore.Tooltip(),
                    //     rng = null;
                    // tip.cssClass = 'wj-tooltip-invalid';                        
                    // detailGrid.hostElement.addEventListener('mousemove', function(e) {
                    //     var ht = detailGrid.hitTest(e.pageX, e.pageY);
                    //     if (!ht.range.equals(rng)) {
                    //         if (ht.cellType == wjGrid.CellType.Cell && ht.col == 6) {
                    //             rng = ht.range;
                    //             var cellElement = document.elementFromPoint(e.clientX, e.clientY),
                    //                 cellBounds = detailGrid.getCellBoundingRect(ht.row, ht.col),
                    //                 data = wjCore.escapeHtml(MSG_ERROR_RESERVE_LIMIT_OVER);
                    //             if (cellElement.className.indexOf('wj-cell') > -1 && cellElement.className.indexOf('wj-grid-invalid') > -1) {
                    //                 // 表示
                    //                 tip.show(detailGrid.hostElement, data, cellBounds);
                    //             } else {
                    //                 // 非表示
                    //                 tip.hide();
                    //             }
                    //         }
                    //     }
                    // })
                    // cell.parentElement.removeChild(cell);

                    var filterTreePath = row.dataItem.filter_tree_path;

                    if(this.gridDetailProvider.cge_sales_date[filterTreePath] !== undefined){
                        this.gridDetailProvider.cge_sales_date[filterTreePath].control.dispose();
                    }
                    // インスタンスの再生成は必須
                    var tmpCgeSalesDate = this.createCgeDate(detailGrid, 'sales_date', 1, 1, 1);
                    this.gridDetailProvider.cge_sales_date[filterTreePath] = tmpCgeSalesDate;

                    // this.gridDetailProvider.cge_sales_date.control.gotFocus.addHandler(function (s, e) {
                    //     this.treeCtrl.isDisabled = true;
                    // }.bind(this));
                    // this.gridDetailProvider.cge_sales_date.control.lostFocus.addHandler(function (s, e) {
                    //     this.treeCtrl.isDisabled = false;
                    // }.bind(this));

                    this.gridDetailProvider.grid_detail = detailGrid;
                    return cell;
                }.bind(this),
                
                // 奇数行の展開ボタン削除
                rowHasDetail: function (row) {
                    var result = true;
                    if(row.recordIndex % 2 === 0){
                        result = false;
                    }
                    if(row.dataItem !== undefined){
                        if(!this.isVisibleToggleBtn(row.dataItem)){
                            result = false;
                        }
                    }
                    return result;
                }.bind(this)
            })

            // プロバイダーにカスタムグリッドエディタのプロパティ追加(売上日カレンダー)
            tmpGridDetailProvider.cge_sales_date = {};
            // プロバイダーにインスタンスをセットする
            this.gridDetailProvider = tmpGridDetailProvider;

            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e.pageX, e.pageY);
                switch(ht.cellType){
                    case wjGrid.CellType.RowHeader:
                        if (this.gridDetailProvider.isDetailVisible(ht.row)) {
                            this.gridDetailProvider.hideDetail(ht.row);
                        }else{
                            this.gridDetailProvider.showDetail(ht.row);
                        }
                        break;
                    case wjGrid.CellType.Cell:
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        var record = ht.panel.rows[ht.row].dataItem;

                        if(typeof record !== 'undefined'){
                            switch (col.name) {
                                case 'btn_status_area':
                                    // var approve = document.getElementById('status-approve-' + record.filter_tree_path);
                                    // console.log(approve.checked);
                                    break;
                                case 'btn_add':
                                    if(!this.isReadOnly && this.isValidAddSalesDetailRow(record)){
                                        this.btnAddSalesDetail(ht.row + 1, record);
                                    }
                                    break;
                                case 'btn_delete':
                                    if(!this.isReadOnly && this.isValidDeleteSalesDetailRow(record)){
                                        this.btnDeleteSalesDetail(ht.row, record);
                                    }
                                    break;
                            }
                        }
                        break;

                }             
            })


            return gridCtrl;
        },

        /**
         * グリッドのカレンダーコントロールを生成する
         * グリッド, 列名, 多段数, n段目, 結合段数
         */
        createCgeDate(gridCtrl, name, multirows, row, rowspan){
            return new CustomGridEditor(gridCtrl, name, wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, multirows, row, rowspan);
        },

        /**
         * 子グリッドの変更イベント
         * @param row   子グリッドの行
         */
        changeSalesDetailRowData(row) {
            // 子グリッドの行の金額計算
            this.calcSalesDetailRowData(row);
            // 子グリッドの情報を親に反映
            this.setSalesDetailToGridRow(row.filter_tree_path);
            // グリッドに対してリフレッシュ
            this.gridCtrl.refresh();
        },
        getDetails(row) {
            var key = row.filter_tree_path;
            return this.sales_detail_list[key];
        },
        // ツリーコントロール作成
        createTreeCtrl(targetTreeDivId, treeItemsSource) {
            var treeCtrl = new wjNav.TreeView(targetTreeDivId, {
                itemsSource: treeItemsSource,
                displayMemberPath: "header",
                childItemsPath: "items",
                showCheckboxes: !this.isReadOnly,
                // formatItem: function(s, e) {
                //     // console.log(e.dataItem.header);
                //     // e.element.innerHTML += '<span>asas</span>';
                // }
            });

            // TreeView選択イベントに処理を紐付け
            treeCtrl.selectedItemChanged.addHandler(function(sender) {
                if(sender.selectedItem === null){return;}
                var kbnIdList = this.getTreeKbnId(sender);
                this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);

                // 独自で付与した選択時のグレー色を削除
                var HTMLCollection = document.getElementsByClassName('wj-state-selected-ex');
                for (var i=0; i<HTMLCollection.length; i++) {
                    HTMLCollection[i].classList.remove('wj-state-selected-ex');
                }
            }.bind(this));

            // チェック状態変更イベント
            treeCtrl.isCheckedChanging.addHandler(function (s, e) {
                // チェック連動なし
                e.cancel = true;
                e.node.element.childNodes[0].checked = !e.node.element.childNodes[0].checked;
            }.bind(this));

            return treeCtrl;
        },
       
        //　階層選択時にグリッドのフィルター機能で表示をしぼる
        filterGrid(topFlg, depth, filterTreePath, header) {
            this.gridCtrl.isReadOnly = this.isReadOnly;
            this.gridCtrl.collectionView.filter = function(record) {
                var result = this.isTreeGridVisibleTarget(record, topFlg, depth, filterTreePath);
                if(result){
                    result = this.filtering(record);
                }
                return result;
            }.bind(this)

            // 階層の情報をセットする
            this.setTreeInfo(topFlg, depth, filterTreePath, header);
        },

        // 絞り込み機能
        filtering(record){
            var result = false;
            var detailRecordList = this.sales_detail_list[record.filter_tree_path];
            var preDetailRecordList = [];
            var activeDetailRecordList = [];
            var filterInfo  = this.filterInfo;
            var requestSday = this.strToTime(this.requestInfo.request_s_day);
            var requestEday = this.strToTime(this.requestInfo.request_e_day);

            if(filterInfo.pre.length >=1 || filterInfo.active.length >=1){
                if(Array.isArray(detailRecordList) && detailRecordList.length >= 1){
                    // 子グリッドがある(1件でも一致すればtrue)
                    for(var i=0; i<detailRecordList.length && !result; i++){
                        if(this.rmUndefinedBlank(detailRecordList[i].sales_date) !== ''){
                            var salesDate = this.strToTime(detailRecordList[i].sales_date);
                            // 過去分
                            if(requestSday > salesDate){
                                if(filterInfo.pre.indexOf(detailRecordList[i].sales_flg) !== -1){
                                    preDetailRecordList.push(detailRecordList[i]);
                                    result = true;
                                }
                            }
                            // 当月分
                            if(this.isSalesPeriod(salesDate, requestSday, requestEday) && !result){
                                if(filterInfo.active.indexOf(detailRecordList[i].sales_flg) !== -1){
                                    activeDetailRecordList.push(detailRecordList[i]);
                                    result = true;
                                }
                            }
                        }else{
                            // 売上日の入力が無い場合
                            preDetailRecordList.push(detailRecordList[i]);
                            activeDetailRecordList.push(detailRecordList[i]);
                            result = true;
                        }
                    }
                }else{
                    // 子グリッドが無い場合
                    result = true;
                }
            }else{
                // 過去と当月に一つもチェックがない場合は、全て表示対象
                result = true;
            }

            // 親グリッド
            if(result){
                // 売上未確定
                if(filterInfo.other.indexOf(this.FILTER_INFO_LIST.OTHER.key.sales_undecided) !== -1){
                    var tmpResult = false;

                    var targetDetailRecordList = [];
                    if(filterInfo.pre.length >=1 && filterInfo.active.length >=1){
                        // 過去＆当月
                        targetDetailRecordList = detailRecordList;
                    } else if(filterInfo.pre.length >= 1){
                        // 過去のみ
                        targetDetailRecordList = preDetailRecordList;
                    } else if (filterInfo.active.length >= 1){
                        // 当月のみ
                        targetDetailRecordList = activeDetailRecordList;
                    } else {
                        // 過去にも当月にもチェックなし
                        targetDetailRecordList = detailRecordList;
                    }

                    if(Array.isArray(targetDetailRecordList) && targetDetailRecordList.length >= 1){
                        for(var i=0; i<targetDetailRecordList.length && result; i++){
                            if(this.rmUndefinedBlank(targetDetailRecordList[i].sales_update_date) === ''){
                                tmpResult = true;
                                break;
                            }
                        }
                    }
                    result = tmpResult;
                }
                // 申請中
                if(filterInfo.other.indexOf(this.FILTER_INFO_LIST.OTHER.key.application) !== -1 && result){
                    if(record.status !== this.salesStatusList['val']['applying']){
                        result = false;
                    }
                }
                // 売価ゼロ
                if(filterInfo.other.indexOf(this.FILTER_INFO_LIST.OTHER.key.zero_sales) !== -1 && result){
                    if(!this.bigNumberEq(this.rmUndefinedZero(record.sales_unit_price), 0)){
                        result = false;
                    }else{
                        if(record.invalid_unit_price_flg === this.FLG_ON){
                            result = false;
                        }
                    }
                }
                // 仕入調整
                if(filterInfo.other.indexOf(this.FILTER_INFO_LIST.OTHER.key.cost_edit) !== -1 && result){
                    if(!this.bigNumberGt(this.rmUndefinedZero(record.update_cost_unit_price), 0)){
                        result = false;
                    }
                }
            }

            return result;
        },

        // 階層の情報をセットする
        setTreeInfo(topFlg, depth, filterTreePath, header){
            // 上部の階層ごとに金額をセットする
            var currentGridDataList = [];
            if(topFlg === this.FLG_ON){
                currentGridDataList = this.gridCtrl.collectionView.sourceCollection.filter((rec) => {            
                    if(this.QUOTE_CONSTRUCTION_DEPTH === rec.depth){
                        return true;
                    }else{
                        return false;
                    }
                });
            }else{
                currentGridDataList = this.getChildGridDataList(this.gridCtrl, filterTreePath, (depth+1));
            }

            var costTotal       = this.toBigNumber(0);
            var salesTotal      = this.toBigNumber(0);
            for(let i in currentGridDataList){
                var row = currentGridDataList[i];
                costTotal   = this.bigNumberPlus(costTotal, row.cost_total, true);
                salesTotal  = this.bigNumberPlus(salesTotal, row.sales_total, true);
            }

            // 階層名
            this.treeInfo.layer_name     = header;
            // 仕入金額
            this.treeInfo.cost_total     = costTotal;
            // 販売金額
            this.treeInfo.sales_total    = salesTotal;
            // 粗利額
            this.treeInfo.profit_total       = this.bigNumberMinus(this.treeInfo.sales_total, this.treeInfo.cost_total);
            // 粗利率
            this.treeInfo.gross_profit_rate  = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(this.treeInfo.profit_total, this.treeInfo.sales_total, true), 100, true)); 

        },

        // 階層の情報をセットする TODO
        setTreeSalesTotalToName(){
            var kbnIdList = this.getTreeKbnId(this.treeCtrl);
            //console.log(this.treeCtrl.selectedNode);
            this.calcTreeName(this.treeCtrl.nodes[0].nodes);
            //console.log(this.treeCtrl.selectedNode);
            var selectElement = this.treeCtrl.getNode(this.treeCtrl.selectedItem).element;
            // ノードリフレッシュ時に選択状態の背景色が解除されるのでクラスを追加する
            if(!selectElement.classList.contains('wj-state-selected-ex')){
                selectElement.classList.add('wj-state-selected-ex');
            }
            //this.treeCtrl.getNode(this.treeCtrl.selectedItem).select();
        },

        // 階層に金額をセットする
        calcTreeName(nodes){
            for(var i=0; (nodes !== null && i<nodes.length); i++){
                var item = nodes[i].dataItem;
                var currentGridDataList = this.getChildGridDataList(this.gridCtrl, item['filter_tree_path'], (item['depth']+1));
                var salesTotal = this.toBigNumber(0);
                if(this.filterInfo.active.length !== 0){
                    for(let i in currentGridDataList){
                        salesTotal = this.bigNumberPlus(salesTotal, currentGridDataList[i].sales_total, true);
                    }
                }
                item['header'] = item['product_name'] +'　　'+ salesTotal.toNumber().toLocaleString();
                nodes[i].refresh();
                this.calcTreeName(nodes[i].nodes);
            }
        },
        // 請求情報に金額をセットする
        setRequestInfo(){
            var gridDataList = this.gridCtrl.collectionView.sourceCollection;
            var costTotal   = this.toBigNumber(0);
            var salesTotal  = this.toBigNumber(0);
            var additionalDiscountAmount    = this.toBigNumber(0);
            var bkCostUnitPrice             = this.toBigNumber(0);
            var discountFlg                 = false;
            for(var i=0; i<gridDataList.length; i++){
                var row = gridDataList[i];
                costTotal   = this.bigNumberPlus(costTotal, row.cost_total, true);
                salesTotal  = this.bigNumberPlus(salesTotal, row.sales_total, true);
                if(row.sales_flg === this.INIT_ROW_NEBIKI.sales_flg){
                    additionalDiscountAmount = this.bigNumberPlus(additionalDiscountAmount, row.sales_total, true);
                }
                bkCostUnitPrice  = this.bigNumberPlus(bkCostUnitPrice, row.update_cost_unit_price, true);

                if(!discountFlg && row.status === this.salesStatusList['val']['applying']){
                    discountFlg = true;
                }
            }


            var deliveryQuantity    = this.toBigNumber(0);
            var returnQuantity      = this.toBigNumber(0);
            for(var filterTreePath in this.sales_detail_list){
                for(var i in this.sales_detail_list[filterTreePath]){
                    var row = this.sales_detail_list[filterTreePath][i];
                    if(this.isSalesPeriod(row.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day)){
                        switch(row.sales_flg){
                            case this.salesFlgList['val']['delivery']:
                                // 当月の納品数(実際の物としての納品数を合計するため相殺は対象外)
                                if(row.notdelivery_flg !== this.notdeliveryFlgList['val']['create']){
                                    deliveryQuantity   = this.bigNumberPlus(deliveryQuantity, row.sales_stock_quantity, true);
                                }
                                break;
                            case this.salesFlgList['val']['return']:
                                // 当月の返品数
                                returnQuantity   = this.bigNumberPlus(returnQuantity, row.sales_stock_quantity, true);
                                break;
                        }
                    }
                    
                }
            }


            // 納品数
            this.requestInfo.delivery_quantity  = deliveryQuantity.toNumber();
            // 返品数
            this.requestInfo.return_quantity    = returnQuantity.toNumber();

            // 当月仕入高
            this.requestInfo.purchase_volume    = costTotal.toNumber();
            // 当月販売額
            this.requestInfo.sales              = salesTotal.toNumber();
            // 値引き追加額
            this.requestInfo.additional_discount_amount = additionalDiscountAmount.toNumber();
            // 粗利額
            this.requestInfo.profit_total       = this.bigNumberMinus(this.requestInfo.sales, this.requestInfo.purchase_volume);
            // 粗利率
            this.requestInfo.gross_profit_rate  = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(this.requestInfo.profit_total, this.requestInfo.sales, true), 100, true)); 
            // 値引申請
            this.requestInfo.discount_flg       = discountFlg;
            // 仕入調整
            this.requestInfo.cost_edit_flg      = this.bigNumberGt(bkCostUnitPrice, 0); 

            this.requestGridCtrl.refresh();
        },

        // ******************** グリッド ********************
        // グリッドレイアウト定義取得
        getRequestGridLayout () {
            return [
                { cells: [{ name: 'owner_name', binding: 'owner_name', header: '施主名', width: 200, isReadOnly: true }] },
                { cells: [{ name: 'matter_charge_department_name', binding: 'matter_charge_department_name', header: '部門名', width: 280, isReadOnly: true }] },
                { cells: [{ name: 'matter_charge_staff_name', binding: 'matter_charge_staff_name', header: '案件担当者', width: 180, isReadOnly: true }] },
                { cells: [{ name: 'delivery_area', binding: 'delivery_area', header: '納品数/返品数', width: 120, isReadOnly: true }] },
                { cells: [{ name: 'purchase_volume', binding: 'purchase_volume', header: '当月仕入高', width: 110, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'sales', binding: 'sales', header: '当月売上高', width: 110, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'additional_discount_amount', binding: 'additional_discount_amount', header: '(内 値引追加額)', width: 120, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'profit_total', binding: 'profit_total', header: '粗利額', width: 100, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 80, isReadOnly: true, format: 'n0', cssClass: 'text-right'}] },
                { cells: [{ name: 'status_text', binding: 'status_text', header: '確定処理', width: 80, isReadOnly: true}] },
                { cells: [{ name: 'discount_flg', binding: 'discount_flg', header: '値引申請', width: 80, isReadOnly: true}] },
                { cells: [{ name: 'cost_edit_flg', binding: 'cost_edit_flg', header: '仕入調整', width: 80, isReadOnly: true}] },
                { cells: [{ name: 'update_at', binding: 'update_at', header: '最終更新日', width: 100, isReadOnly: true}] },
            ];
        },
        getGridLayout () {
            return [
                { cells: [
                    { name: 'product_code', binding: 'product_code', header: '品番', width: 180, isReadOnly: true },
                    { name: 'order_no_list', binding: 'order_no_list', header: '発注番号', width: 180, isReadOnly: true },
                ] },
                { cells: [
                    { name: 'product_name', binding: 'product_name', header: '商品名', width: 180, isReadOnly: this.isReadOnly, isRequired: true },
                    { name: 'model', binding: 'model', header: '型式・規格', width: 180, isReadOnly: this.isReadOnly },
                ] },
                { cells: [
                    { name: 'sales_stock_quantity', binding: 'sales_stock_quantity', header: '売上数(管理数)', width: 115, isReadOnly: true, cssClass: 'text-right' },
                    { name: 'sales_quantity', binding: 'sales_quantity', header: '売上数(入力数)', width: 115, isReadOnly: true, cssClass: 'text-right' },
                ] },
                { cells: [
                    { name: 'stock_unit', binding: 'stock_unit', header: '管理数単位', width: 85, isReadOnly: true, },
                    { name: 'unit', binding: 'unit', header: '単位', width: 85, isReadOnly: true, },
                ] },
                { cells: [
                    { name: 'sales_graph', binding: 'sales_graph', header: '売上状況', width: 125, isReadOnly: true},
                    { name: 'sales_graph_number', binding: 'sales_graph_number', header: '(数値)', width: 125, isReadOnly: true},
                ] },
                { cells: [
                    { name: 'cost_unit_price', binding: 'cost_unit_price', header: '仕入単価', width: 90, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'cost_kbn', binding: 'cost_kbn', header: '単価区分', width: 90, isReadOnly: true},
                ] },
                { cells: [
                    { name: 'cost_total', binding: 'cost_total', header: '仕入金額', width: 80, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'cost_makeup_rate', binding: 'cost_makeup_rate', header: '仕入掛率', width: 80, isReadOnly: true, cssClass: 'text-right'},
                ] },
                { cells: [
                    { name: 'sales_unit_price', binding: 'sales_unit_price', header: '販売単価', width: 90, isReadOnly: this.isReadOnly, cssClass: 'text-right'},
                    { name: 'sales_kbn', binding: 'sales_kbn', header: '単価区分', width: 90, isReadOnly: true},
                ] },
                { cells: [
                    { name: 'sales_total', binding: 'sales_total', header: '販売金額', width: 80, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'sales_makeup_rate', binding: 'sales_makeup_rate', header: '販売掛率', width: 80, isReadOnly: this.isReadOnly, cssClass: 'text-right', isRequired: false},
                ] },
                { cells: [
                    { name: 'profit_total', binding: 'profit_total', header: '粗利額', width: 80, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 80, isReadOnly: this.isReadOnly, cssClass: 'text-right', isRequired: false},
                ] },
                { cells: [
                    { name: 'blank', header: '', width: 115, isReadOnly: this.isReadOnly},
                    { name: 'details_c_flg', binding: 'details_c_flg', header: '明細印字(子)', width: 115, isReadOnly: this.isReadOnly, }
                ] },
                { cells: [
                    { name: 'blank', header: '', width: 115, isReadOnly: this.isReadOnly  },
                    { name: 'selling_c_flg', binding: 'selling_c_flg', header: '売価印字(子)', width: 115, isReadOnly: this.isReadOnly, }
                ] },
                { cells: [
                    { name: 'status', binding: 'status', header: '申請', width: 80, minWidth: 80, maxWidth: 80, isReadOnly: true, },
                    { name: 'btn_status_area', binding: 'btn_status_area', header: '　', width: 80, minWidth: 80, maxWidth: 80, isReadOnly: true, }
                ] },
                { cells: [
                    { name: 'btn_add', binding: 'btn_add', header: ' ', width: 40, minWidth: 40, maxWidth: 40, isReadOnly: true},
                    { name: 'btn_delete', binding: 'btn_delete', header: ' ', width: 40, minWidth: 40, maxWidth: 40, isReadOnly: true},
                ] },
                // 非表示データ
                { cells: [{ name: 'selling_p_flg', binding: 'selling_p_flg', header: 'selling_p_flg', visible: false }] },
                { cells: [{ name: 'details_p_flg', binding: 'details_p_flg', header: 'details_p_flg', visible: false }] },
                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', header: 'quote_detail_id', visible: false }] },
                { cells: [{ name: 'sales_id', binding: 'sales_id', header: 'sales_id', visible: false }] },
                { cells: [{ name: 'construction_id', binding: 'construction_id', header: '工事区分ID', visible: false, isReadOnly: true}] },
                { cells: [{ name: 'layer_flg', binding: 'layer_flg', header: '階層フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'set_flg', binding: 'set_flg', header: '一式フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'parent_quote_detail_id', binding: 'parent_quote_detail_id', header: '親見積もり明細ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'seq_no', binding: 'seq_no', header: '連番', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'depth', binding: 'depth', header: '深さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'tree_path', binding: 'tree_path', header: '階層パス', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'filter_tree_path', binding: 'filter_tree_path', header: 'フィルター階層パス', visible: false, isReadOnly: true}] },
                { cells: [{ name: 'sales_use_flg', binding: 'sales_use_flg', header: '販売額利用', isReadOnly: true, visible: false }] },
                { cells: [{ name: 'quote_quantity', binding: 'quote_quantity', header: '見積数', isReadOnly: true, visible: false }] },
                { cells: [{ name: 'min_quantity', binding: 'min_quantity', header: '最小単位', isReadOnly: true, visible: false }] },
                { cells: [{ name: 'sales_flg', binding: 'sales_flg', header: '売上種別', isReadOnly: true, visible: false }] },
                { cells: [{ name: 'regular_price', binding: 'regular_price', header: '定価', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'update_cost_unit_price', binding: 'update_cost_unit_price', header: '反映前_仕入単価', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'update_cost_unit_price_d', binding: 'update_cost_unit_price_d', header: '反映前_仕入単価更新日', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'bk_sales_unit_price', binding: 'bk_sales_unit_price', header: '前回_販売単価', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'sales_all_quantity', binding: 'sales_all_quantity', header: '全売上数(入力数)', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'default_gross_profit_rate', binding: 'default_gross_profit_rate', header: '初期表示時の粗利率', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'invalid_unit_price_flg', binding: 'invalid_unit_price_flg', header: '単価無効フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'intangible_flg', binding: 'intangible_flg', header: '無形品フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'draft_flg', binding: 'draft_flg', header: '仮登録フラグ', visible: false, isReadOnly: true }] },
            ];
        },
        // 子グリッド定義
        getSalesDetailGridLayout () {
            // 納品状況
            var mapSalesFlgList = new wjGrid.DataMap(this.salesFlgList['data'], 'val', 'text');
            mapSalesFlgList.getDisplayValues = function (dataItem) {
                var list = [];
                var res = null;
                var parentRow = this.getParentGridRow(dataItem.filter_tree_path);

                // 見積明細の場合は、未納/未売/出来高/
                if(parentRow.sales_flg === this.salesDetailFlgList.val.quote){
                    res = this.salesFlgList['quote_data'];
                }else{
                    res = this.salesFlgList['data'];
                }
                return res.map(row => row.text);
            }.bind(this);
            return [
                { cells: [{ name: 'sales_sel_flg', binding: 'sales_sel_flg', header: '売上', width: 50, isReadOnly: this.isReadOnly }] },
                { cells: [{ name: 'sales_date', binding: 'sales_date', header: '売上日', width: 130, isReadOnly: this.isReadOnly, isRequired: false }] },
                { cells: [{ name: 'sales_flg', binding: 'sales_flg', header: '納品状況', width: 80, dataMap: mapSalesFlgList, isReadOnly: this.isReadOnly }] },
                { cells: [{ name: 'delivery_date', binding: 'delivery_date', header: '納品日', width: 100, isReadOnly: true }] },
                { cells: [{ name: 'delivery_no', binding: 'delivery_no', header: '納品番号', width: 120, isReadOnly: true }] },
                { cells: [{ name: 'sales_stock_quantity', binding: 'sales_stock_quantity', header: '売上数(管理数)', width: 120, isReadOnly: this.isReadOnly, isRequired: false, cssClass: 'text-right' }] },
                { cells: [{ name: 'sales_quantity', binding: 'sales_quantity', header: '売上数(入力数)', width: 120, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'sales_unit_price', binding: 'sales_unit_price', header: '単価', width: 100, isReadOnly: this.isReadOnly, isRequired: false, cssClass: 'text-right' }] },
                { cells: [{ name: 'sales_total', binding: 'sales_total', header: '金額', width: 100, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'memo', binding: 'memo', header: '備考', width: 150, isReadOnly: this.isReadOnly, isRequired: false }] },
                // 非表示データ
                { cells: [{ name: 'sales_detail_id', binding: 'sales_detail_id', header: 'sales_detail_id', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', header: 'quote_detail_id', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'sales_id', binding: 'sales_id', header: 'sales_id', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'sales_update_date', binding: 'sales_update_date', header: 'sales_update_date', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'delivery_id', binding: 'delivery_id', header: 'delivery_id', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'return_id', binding: 'return_id', header: 'return_id', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'request_id', binding: 'request_id', header: 'request_id', width: 0, maxWidth: 0, isReadOnly: true }] },

                { cells: [{ name: 'filter_tree_path', binding: 'filter_tree_path', header: 'filter_tree_path', width: 0, maxWidth: 0, isReadOnly: true}] },
                { cells: [{ name: 'min_quantity', binding: 'min_quantity', header: 'min_quantity', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'cost_unit_price', binding: 'cost_unit_price', header: 'cost_unit_price', width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'cost_total', binding: 'cost_total', header: 'cost_total', width: 100, width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'notdelivery_flg', binding: 'notdelivery_flg', header: 'notdelivery_flg', width: 100, width: 0, maxWidth: 0, isReadOnly: true }] },
                { cells: [{ name: 'offset_delivery_id', binding: 'offset_delivery_id', header: 'offset_delivery_id', width: 100, width: 0, maxWidth: 0, isReadOnly: true }] },
            ];
        },

        // ******************** 値セット ********************
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },

        // 選択している階層の分類などのIDを返す
        getTreeKbnId(selectedTree){
            var result = {
                top_flg     : null,
                depth       : null,
                filter_tree_path    : null,
                construction_id     : null,
                header      : '',
            }
            
            result.top_flg  = selectedTree.selectedItem.top_flg;
            result.depth    = selectedTree.selectedItem.depth;
            result.filter_tree_path = selectedTree.selectedItem.filter_tree_path;
            result.construction_id  = selectedTree.selectedItem.construction_id;
            result.header   = selectedTree.selectedItem.product_name;
            return result;
        },
        // 子グリッドの親明細を取得
        getParentGridRow(filterTreePath){
            var parentRow = this.gridCtrl.collectionView.sourceCollection.find((rec) => {
                return (rec.filter_tree_path === filterTreePath);
            });
            return parentRow;
        },

        /****** 金額計算系　子グリッド ******/
        /**
         * 子グリッドに親グリッドの情報を反映する
         * 販売単価
         * @param parentRow     親グリッドの行
         */
        setSalesToSalesDetailGridRow(parentRow) {
            var gridData = this.gridCtrl.collectionView.sourceCollection;
            var currentSalesDetailData = this.sales_detail_list[parentRow.filter_tree_path];
            var salesUnitPrice  = parentRow.sales_unit_price;
            if(Array.isArray(currentSalesDetailData)){
                for(var i in currentSalesDetailData){
                    var salesDetail     = currentSalesDetailData[i];
                    if(salesDetail.sales_flg !== this.salesFlgList['val']['not_sales']){
                        // 未売の販売単価は0
                        if(this.rmUndefinedBlank(salesDetail.sales_update_date) === ''){
                            salesDetail.sales_unit_price = salesUnitPrice;
                        }
                    }
                    // 子グリッドの行の金額計算
                    this.calcSalesDetailRowData(salesDetail);
                }
            }
            
        },
        /**
         * 親グリッドに子グリッドの情報を反映する
         * 階層情報もセットする
         * 
         * 売上数(未売は売上数に含まない)
         * 仕入単価(値引/出来高は先頭1件目を反映させる　それ以外は反映しない)
         * 仕入金額
         * 販売単価(値引/出来高は先頭1件目を反映させる　それ以外は反映しない)
         * 販売金額(未売は仕入金額にのみ含む)
         * 粗利額
         * @param filterTreePath    
         * @param isInit            初期表示か
         */
        setSalesDetailToGridRow(filterTreePath, isInit) {
            if(isInit === undefined){
                isInit = false;
            }

            var currentSalesDetailData = this.sales_detail_list[filterTreePath];
            // 親明細取得
            var parentRow = this.getParentGridRow(filterTreePath);

            if(parentRow !== undefined){
                var salesStockQuantity = this.toBigNumber(0);
                var salesQuantity   = this.toBigNumber(0.0);
                var salesAllQuantity   = this.toBigNumber(0.0);
                var costUnitPrice   = 0;
                var costTotal       = this.toBigNumber(0);
                var salesUnitPrice  = 0;
                var salesTotal      = this.toBigNumber(0);
                if(Array.isArray(currentSalesDetailData)){
                    for(var i in currentSalesDetailData){
                        var salesDetail     = currentSalesDetailData[i];
                        if(i === '0'){
                            costUnitPrice       = salesDetail.cost_unit_price;
                            salesUnitPrice      = salesDetail.sales_unit_price;
                        }

                        // 未売は売上数に含まない 無効行は売上に含まない
                        if(salesDetail.sales_flg !== this.salesFlgList['val']['not_sales']  && salesDetail.notdelivery_flg !== this.notdeliveryFlgList['val']['invalid']){
                            // 全ての累計売上数
                            salesAllQuantity  = this.bigNumberPlus(salesAllQuantity, salesDetail.sales_quantity, true);
                        }

                        if(this.isSalesPeriod(salesDetail.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day) && salesDetail.notdelivery_flg !== this.notdeliveryFlgList['val']['invalid']){
                            costTotal           = this.bigNumberPlus(costTotal, salesDetail.cost_total, true);
                            // 未売は単価が0なので合計して問題無い
                            salesTotal          = this.bigNumberPlus(salesTotal, salesDetail.sales_total, true);
                            // 未売は売上数に含まない
                            if(salesDetail.sales_flg !== this.salesFlgList['val']['not_sales']){
                                salesStockQuantity  = this.bigNumberPlus(salesStockQuantity, salesDetail.sales_stock_quantity, true);
                                salesQuantity       = this.bigNumberPlus(salesQuantity, salesDetail.sales_quantity, true);
                            }
                        }
                    }
                }

                // 親グリッドの数量変更
                parentRow.sales_stock_quantity  = this.rmInvalidNumZero(salesStockQuantity.toNumber());
                parentRow.sales_quantity        = this.rmInvalidNumZero(salesQuantity.toNumber());
                parentRow.sales_all_quantity    = this.rmInvalidNumZero(salesAllQuantity.toNumber());

                // 売上明細は1件しかない
                if(parentRow.sales_flg === this.INIT_ROW_NEBIKI.sales_flg || parentRow.sales_flg === this.INIT_ROW_SALES.sales_flg || parentRow.sales_flg === this.INIT_ROW_COST_ADJUST.sales_flg){
                    // 仕入単価
                    parentRow.cost_unit_price   = this.rmUndefinedZero(costUnitPrice);
                    // 販売単価
                    parentRow.sales_unit_price  = this.rmUndefinedZero(salesUnitPrice);
                }
                // 仕入金額
                parentRow.cost_total            = this.rmInvalidNumZero(costTotal.toNumber());
                // 販売金額
                parentRow.sales_total           = this.rmInvalidNumZero(salesTotal.toNumber());
                // 粗利額
                parentRow.profit_total          = this.bigNumberMinus(parentRow.sales_total, parentRow.cost_total);
                // 粗利率
                parentRow.gross_profit_rate     = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(parentRow.profit_total, parentRow.sales_total, true), 100, true));
                // 初期表示時の粗利率
                if(isInit){
                    // この粗利率を下回った場合に、保存時に申請を出す
                    parentRow.default_gross_profit_rate = parentRow.gross_profit_rate;
                }
            }

            if(!isInit){
                var kbnIdList = this.getTreeKbnId(this.treeCtrl);
                this.setTreeInfo(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
                this.setTreeSalesTotalToName();
                this.setRequestInfo();
            }
        },

        /****** 金額計算系　子グリッド ******/
        /**
         * 数量変更や売上単価変更時に行に対する計算処理
         * @param {*} row           子グリッドの行データ
         */
        calcSalesDetailRowData(row){
            row.sales_stock_quantity    = this.roundDecimalStandardPrice(row.sales_stock_quantity);
            row.sales_unit_price        = this.roundDecimalSalesPrice(row.sales_unit_price);
            row.cost_unit_price         = this.roundDecimalSalesPrice(row.cost_unit_price);
            row.sales_quantity = this.rmInvalidNumZero(this.bigNumberTimes(row.sales_stock_quantity, row.min_quantity));
            // 販売総額 = 入力数 * 売上単価
            row.sales_total = this.roundDecimalSalesPrice(this.bigNumberTimes(row.sales_quantity, row.sales_unit_price));
            // 仕入総額 = 入力数 * 売上単価
            row.cost_total = this.roundDecimalSalesPrice(this.bigNumberTimes(row.sales_quantity, row.cost_unit_price));
        },



        /****** UTIL系 ******/

        /**
         * 期間内か
         * @param salesDate
         * @param requestSday
         * @param requestEday
         */
        isSalesPeriod(salesDate, requestSday, requestEday){
            var result = false;
            var salesDate   = this.strToTime(salesDate);
            var requestSday = this.strToTime(requestSday);
            var requestEday = this.strToTime(requestEday);
            if(salesDate !== null){
                if(requestSday <= salesDate && requestEday >= salesDate){
                    result = true;
                }
            }
            return result;
        },

        /**
         * 子グリッド全てが確定しているか
         * @param row 親グリッドの行
         */
        salesDetailIsAllComplete(row){
            var result = true;
            var detailRecordList = this.sales_detail_list[row.filter_tree_path];
            for(var i=0; Array.isArray(detailRecordList) && i < detailRecordList.length; i++){
                var row = detailRecordList[i];
                if(this.rmUndefinedBlank(row.sales_update_date) === ''){
                    result = false;
                }
            }
            return result;
        },

        /**
         * 確定済みの数量から確定済みの返品数を減らす
         * @param colorList         納品数：[確定][未確定の納品][未確定の未納]
         * @param returnQuantity    返品数：[確定分の返品数の合計][未確定分の返品数の合計]
         * @param keyName           
         */
        calcFixedReturn(colorList, returnQuantity, keyName){
            // 確定済みから返品数を減らした結果
            returnQuantity[keyName]     = this.bigNumberPlus(colorList.grey, returnQuantity[keyName], true);
            if(this.bigNumberGt(returnQuantity[keyName], 0)){
                // 正数の場合は相殺した確定済み数をセット
                colorList.grey          = returnQuantity[keyName];
                returnQuantity[keyName] = 0;
            }else{
                colorList.grey          = 0;
            }
        },

        /**
         * 未確定の納品から未確定の返品数を減らす
         * 未確定の納品で相殺しきれなかった場合は未確定の未納から減らす
         * @param colorList         納品数：[確定][未確定の納品][未確定の未納]
         * @param returnQuantity    返品数：[確定分の返品数の合計][未確定分の返品数の合計]
         * @param keyName
         */
        calcUnfixedReturn(colorList, returnQuantity, keyName){
            // 納品から返品数を減らした結果
            returnQuantity[keyName]     = this.bigNumberPlus(colorList.blue, returnQuantity[keyName], true);
            if(this.bigNumberGt(returnQuantity[keyName], 0)){
                colorList.blue          = returnQuantity[keyName];
                returnQuantity[keyName] = 0;
            }else{
                colorList.blue  = 0;
                // 未納から返品数を減らした結果
                returnQuantity[keyName]     = this.bigNumberPlus(colorList.yellow, returnQuantity[keyName], true);
                if(this.bigNumberGt(returnQuantity[keyName], 0)){
                    colorList.yellow        = returnQuantity[keyName];
                    returnQuantity[keyName] = 0;
                }else{
                    colorList.yellow        = 0;
                }
            }
        },

        /**
         * 【ボタンクリック】承認状態の変更
         * @param isApprove         承認押下か
         * @param filterTreePath    フィルターツリーパス
         */
        async btnChangeSalesStatus(isApprove, filterTreePath){

            var confirmMsg = isApprove ? MSG_CONFIRM_SALES_STATUS_APPLY : MSG_CONFIRM_SALES_STATUS_SENDBACK;
            if(!confirm(confirmMsg)){
                return;
            }

            // 押した行を取得
            var row = this.gridCtrl.collectionView.sourceCollection.find((rec) => {
                return (rec.filter_tree_path === filterTreePath);
            });


            var params = new URLSearchParams();

            params.append('sales_id',       row.sales_id);
            params.append('request_id',     this.requestInfo.id);
            params.append('customer_id',    this.requestInfo.customer_id);
            params.append('customer_name',  this.requestInfo.customer_name);
            params.append('matter_name',    this.quoteInfo.matter_name);
            params.append('matter_charge_department_id',    this.requestInfo.matter_charge_department_id);
            params.append('matter_charge_staff_id',         this.requestInfo.matter_charge_staff_id);
            params.append('is_approve',     isApprove);
            
            var result = await this.changeSalesStatus(params);
            if(result !== undefined && result.status === true){
                row.status                  = parseInt(result.sales_status);
                if(row.status === this.salesStatusList['val']['approved']){
                    // 承認済
                    row.bk_sales_unit_price     = parseInt(result.bk_sales_unit_price);
                }else if(row.status === this.salesStatusList['val']['applying']){
                    // 変更無し
                    row.chief_staff_id          = parseInt(result.chief_staff_id);
                }else{
                    // 否認
                    row.sales_unit_price        = parseInt(result.sales_unit_price);
                }
                this.calcTreeGridChangeUnitPrice(row, false);
                this.setSalesToSalesDetailGridRow(row);
                this.setSalesDetailToGridRow(row.filter_tree_path);
                // この粗利率を下回った場合に、保存時に申請を出す
                row.default_gross_profit_rate = row.gross_profit_rate;

                var kbnIdList = this.getTreeKbnId(this.treeCtrl);
                this.filterGrid(kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.header);
            }else{
                if(this.rmUndefinedBlank(result.message) !== ''){
                    alert(result.message);
                }else{
                    // 失敗
                    alert(MSG_ERROR);
                }
            }
        },

        /**
         * 承認状態の変更
         * @param params
         */
        changeSalesStatus(params) {
            this.loading = true;

            var promise = axios.post('/sales-detail/change-status', params)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } 
            }.bind(this))
            .catch(function (error) {
                //alert(MSG_ERROR); 
            }.bind(this))
            .finally(function () {
                this.loading = false;
            }.bind(this));
            return promise;
        },

        // 保存
        save(){
            if(this.saveDataIsErr()){
                return;
            }

            // 階層かつ販売額利用フラグの行リスト
            var createPlanDataList = this.gridCtrl.collectionView.sourceCollection.filter((rec) => {
                if(rec.sales_use_flg === this.FLG_ON && rec.layer_flg === this.FLG_ON && rec.invalid_unit_price_flg === this.FLG_OFF) return true;
            });


            // 確認メッセ―ジ
            var visibleConfirm = false;
            
            for(var i=0; i<createPlanDataList.length && !visibleConfirm; i++){
                var row = createPlanDataList[i];
                var filterTreePath = row.filter_tree_path;
                // 新しく作成した販売額利用配下の未納データ(1件取得)
                var createDataList = this.sales_detail_list[filterTreePath].find((rec) => {
                    // 仕様変更
                    // if(rec.sales_flg === this.salesFlgList['val']['not_delivery'] && this.rmUndefinedBlank(rec.sales_update_date) === ''){
                    //     return true;
                    // }
                    if(rec.sales_flg === this.salesFlgList['val']['not_delivery']){
                        return true;
                    }
                });
                
                if(createDataList === undefined){
                    // 納品データ
                    for(var detailFilterTreePath in this.sales_detail_list){
                        if(detailFilterTreePath.indexOf(filterTreePath + this.TREE_PATH_SEPARATOR) === 0){
                            var salesDetailList = this.sales_detail_list[detailFilterTreePath];
                            var deliveryDataList = salesDetailList.filter((rec) => {
                                // 仕様変更
                                // if(this.rmUndefinedBlank(rec.sales_update_date) === '' && rec.sales_flg === this.salesFlgList['val']['delivery']){
                                //     return true;
                                // }
                                if(rec.sales_flg === this.salesFlgList['val']['delivery']){
                                    return true;
                                }
                            });

                            if(deliveryDataList.length >= 1){
                                visibleConfirm = true;
                                break;
                            }
                        }
                        
                    }
                }
            }

            if(visibleConfirm){
                if (!confirm(MSG_CONFIRM_NOT_CREATE_NOTDELIVERY_DATA)) {
                    return;
                }
            }else{
                if (!confirm(MSG_CONFIRM_SAVE)) {
                    return;
                }
            }


            this.loading = true


            var params = new URLSearchParams();
            
            var salesList       = JSON.stringify(this.gridCtrl.collectionView.sourceCollection);
            var saleDetailList  = JSON.stringify(this.sales_detail_list);


            params.append('matter_id',      this.quoteInfo.matter_id);
            params.append('quote_id',       this.quoteInfo.quote_id);
            params.append('matter_name',    this.quoteInfo.matter_name);
            params.append('request_info',   JSON.stringify(this.requestInfo));
            params.append('sales_list', salesList);
            params.append('sales_detail_list',  saleDetailList);

            params.append('delete_sales_id_list',           JSON.stringify(this.deleteSalesIdList));
            params.append('delete_sales_detail_id_list',    JSON.stringify(this.deleteSalesDetailIdList));
            


            axios.post('/sales-detail/save', params)
            .then( function (response) {
                if (response.data) {
                    if (response.data.status) {
                        window.onbeforeunload = null;
                        location.reload();
                    } else {
                        if(this.rmUndefinedBlank(response.data.message) !== ''){
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
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    alert(MSG_ERROR);
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }
            }.bind(this))
            .finally(function () {
                this.loading = false;
            }.bind(this));
        },
        
        /**
         * 保存時の売上日などのエラーチェック
         */
        saveDataIsErr(){
            var isErr = false;
            
            all_break:
            for(var filterTreePath in this.sales_detail_list){
                var notDeliveryCnt = 0;
                for(var i in this.sales_detail_list[filterTreePath]){
                    var row = this.sales_detail_list[filterTreePath][i];
                    if(this.rmUndefinedBlank(row.sales_update_date) === ''){
                        // 未確定データ
                        switch(row.sales_flg){
                            case this.salesFlgList['val']['not_delivery']:
                                notDeliveryCnt++;
                                break;
                        }

                        
                        if(this.rmUndefinedBlank(row.sales_date) === ''){
                            // 売上日が未入力
                            alert(MSG_ERROR_SALES_DATE_NO_INPUT);
                            isErr = true;
                            break all_break;
                        }else{
                            if(this.strToTime(row.sales_date) < this.strToTime(this.requestInfo.request_s_day)){
                                // 売上開始日より前
                                alert(MSG_ERROR_SALES_DATE_SALES_PERIOD_THAN_ALSO_BEFORE);
                                isErr = true;
                                break all_break;
                            }
                        }
                    }
                }

                // if(notDeliveryCnt >= 2){
                //     alert(MSG_ERROR_SALES_PERIOD_MULTIPLE_CREATE_NOTDELIVERY);
                //     isErr = true;
                //     break;
                // }
            }


            if(!isErr){
                all_break:
                for(var filterTreePath in this.sales_detail_list){
                    // 未確定の未納データ
                    // var notDeliveryRow = this.sales_detail_list[filterTreePath].find((rec) => {
                    //     return (this.rmUndefinedBlank(rec.sales_update_date) === '' && rec.sales_flg === this.salesFlgList['val']['not_delivery']);
                    // });

                    // if(notDeliveryRow !== undefined){
                        // if(!this.isSalesPeriod(notDeliveryRow.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day)){
                        //     // 未確定の未納データが売上期間外になっています
                        //     alert(MSG_ERROR_OUTSIDE_SALES_PERIOD_NOT_DELIVERY_DATA);
                        //     isErr = true;
                        //     break all_break;
                        // }

                        // for(var i in this.sales_detail_list[filterTreePath]){
                        //     var row = this.sales_detail_list[filterTreePath][i];
                        //     if(this.rmUndefinedBlank(row.sales_update_date) === ''){
                        //         if(row.sales_flg === this.salesFlgList['val']['delivery']){
                        //             if(!this.isSalesPeriod(row.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day)){
                        //                 // 未確定の未納データがあるため納品データの売上日を売上期間外には設定できません
                        //                 alert(MSG_ERROR_DELIVERY_OUTSIDE_SALES_PERIOD);
                        //                 isErr = true;
                        //                 break all_break;
                        //             }
                        //         }
                        //     }
                        // }   
                    // }

                    // 未確定の期間外の未売
                    // var notSalesList = this.sales_detail_list[filterTreePath].filter((rec) => {
                    //     if(this.rmUndefinedBlank(rec.sales_update_date) === '' && 
                    //         !this.isSalesPeriod(rec.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day) && 
                    //         rec.sales_flg === this.salesFlgList['val']['not_sales']){
                    //         return true;
                    //     }
                    // });
                    // if(notSalesList.length >= 1){
                    //     alert(MSG_ERROR_OUTSIDE_SALES_PERIOD_CREATE_DATA);
                    //     isErr = true;
                    //     break all_break;
                    // }


                    // 相殺データ
                    var offsetRow = this.sales_detail_list[filterTreePath].find((rec) => {
                        return (this.rmUndefinedBlank(rec.sales_update_date) === '' && rec.notdelivery_flg === this.notdeliveryFlgList['val']['invalid']);
                    });
                    if(offsetRow !== undefined){
                        if(!this.isSalesPeriod(offsetRow.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day)){
                            // 相殺データが売上期間外になっています
                            alert(MSG_ERROR_OUTSIDE_SALES_PERIOD_OFFSET_DATA);
                            isErr = true;
                            break all_break;
                        }
                    }
                }
            }

            // if(!isErr){
            //     // 値引き/出来高/仕入調整/　は売上期間外に設定できない
            //     var salesPeriodTargetList = this.gridCtrl.collectionView.sourceCollection.filter((rec) => {
            //         if(rec.sales_flg !== this.salesDetailFlgList.val.quote) return true;
            //     });

            //     for(var i in salesPeriodTargetList){
            //         var filterTreePath = salesPeriodTargetList[i].filter_tree_path;

            //         var errList = this.sales_detail_list[filterTreePath].filter((rec) => {
            //             if(this.rmUndefinedBlank(rec.sales_update_date) === '' && !this.isSalesPeriod(rec.sales_date, this.requestInfo.request_s_day, this.requestInfo.request_e_day)){
            //                 return true;
            //             }
            //         });
            //         if(errList.length >= 1){
            //             //var salesName = this.salesDetailFlgList.list[salesPeriodTargetList[i].sales_flg];
            //             alert(MSG_ERROR_OUTSIDE_SALES_PERIOD_CREATE_DATA);
            //             isErr = true;
            //             break;
            //         }
            //     }

            // }



            return isErr;
        },
        
        // 戻る
        back() {
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/sales-list';
            }
            var listUrl = rtnUrl + query;

            //if (!this.isReadOnly) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'sales-detail');
                params.append('keys', this.rmUndefinedBlank(this.requestInfo.customer_id));
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
            // } else {
            //     window.onbeforeunload = null;
            //     location.href = listUrl
            // }
        },
        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'sales-detail');
            params.append('keys', this.rmUndefinedBlank(this.requestInfo.customer_id));
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING);
                        location.reload();
                    } else {
                        // グリッドのReadOnly解除のためにリロード
                        window.onbeforeunload = null;
                        location.reload();
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
            var params = new URLSearchParams();
            params.append('screen', 'sales-detail');
            params.append('keys', this.rmUndefinedBlank(this.requestInfo.customer_id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    window.onbeforeunload = null;
                    location.reload();
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
    },
};

</script>

<style>
.main-body{
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    margin-bottom: 10px;
}
.shipping-limit-list{
    padding-left: 0px !important;
}
.arrival-quantity-status{
    display: block;
    width: 100%;
    line-height: normal;
}
.bg-white{
    background: #ffffff;
}
.bg-green{
    background: #4CD964;
}
.bg-red{
    background: #CB2E25;
}
.bg-light-blue{
    background: #5AC8FA;    
}
.bg-yellow{
    background: #FFCC00;
}
.lbl-addon-ex{
    border: none;
    background: none;
}
.grid-graph-cell {
  height: 100%;
  width: 100%;
  display: flex;
  padding:0px;
  flex-direction: row; /* 要素を横に並べる */
}
.wj-glyph-plus {
    margin-top: 12px;
}
.wj-glyph-minus{
    margin-top: 12px;
}
.sales-grid-div .wj-detail{
    margin: 6px 0;
    left: 0px;
    top: 60px;
    width: 1085px !important;
    height: 200px !important;
    /*margin-left: 150px;*/
    margin-left: 370px;
}
.wj-grid-invalid {
    background-color: #FF3B30 !important;
}
.wj-tooltip-invalid {
    color: #FF3B30 !important;
}
.chkColumn {
    height: 60px !important;
}

.table-ex {
    background: #ffffff;
    border: 1px solid #787878 !important;
}
.table-ex tr th, .table-ex tr td{
    border: 1px solid #787878 !important;
}
.table-ex th{
    background: #43425D;
    color: #ffffff;
    vertical-align:middle !important;
    text-align: center;
    padding:5px !important;
}
.table-ex td{
    vertical-align:middle !important;
    padding:5px !important;
}
.table-ex .lbl{
    padding: 4px 6px 3px 6px !important;
}

.filter-area{
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    background: #f7f9ff !important;
}
.item-label{
    margin-bottom: 0px;
}
.item-label-title{
    font-size: 17px;
}
.multi-grid-btn{
    border: none; 
    border-radius: 0px;
    width: 100%;
    height: 100%;
}
.wj-cells .wj-cell.wj-state-selected {
    background: #0085c7 !important;
}
.wj-cells .wj-cell.wj-state-multi-selected {
    background: #80adbf !important;
}
.btn-yellow {
    color: #fff;
    background-color: #f0ad4e;
    border-color: #eea236;
}
.btn-yellow:hover, .btn-yellow:focus{
    color: #fff;
    background-color: #f0941c;
    border-color: #f0941c;
}
.status-btn-group{
    width: 80px;
    height: 30px;
}
.btn-status{
    padding:3px 11.8px;
    border-radius: 0px;
    width: 40px;
}
.item {
  box-sizing: border-box;
  background-color: #FFFFFF;
}
.bg-grey {
  background-color: slategrey;
}
.bg-blue {
  background-color:skyblue;
}
.bg-yellow {
  background-color: gold;
}
.grid-tooltip-cell{
    display: block !important;
    width: 100%;
    height: 25px;
    line-height: 25px;
    text-align: center;
    color:#ffffff;
}
.wj-treeview .wj-node.wj-state-selected-ex {
    color: inherit;
    background: rgba(0,0,0,.05);
}
</style>