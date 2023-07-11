<template>
    <div>
        <loading-component :loading="loading" />


        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm" v-if="isEdit === false">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">　
                    <div class="col-sm-3">
                        <label>※案件番号</label>
                        <wj-auto-complete class="form-control" id="acMatterNo"
                            search-member-path="matter_no"
                            display-member-path="matter_no"
                            selected-value-path="matter_no"
                            :initialized="initMatterNo"
                            :selected-value="searchParams.matter_no"
                            :is-required="false"
                            :items-source="matterList"
                            :selectedIndexChanged="selectMatterNo"
                            :textChanged="setTextChanged"
                            :max-items="matterList.length">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-6">
                        <label>※案件名</label>
                        <wj-auto-complete class="form-control" id="acMatterName"
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_no"
                            :initialized="initMatterName"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_name"
                            :is-required="false"
                            :items-source="matterList"
                            :selectedIndexChanged="selectMatterName"
                            :textChanged="setTextChanged"
                            :max-items="matterList.length">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>入荷予定日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="(searchParams.arrival_plan_date_from == '' ? null : searchParams.arrival_plan_date_from)"
                                :initialized="initArrivalPlanDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="(searchParams.arrival_plan_date_to == '' ? null : searchParams.arrival_plan_date_to)"
                                :initialized="initArrivalPlanDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <label>出荷予定日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="(searchParams.hope_arrival_plan_date_from == '' ? null : searchParams.hope_arrival_plan_date_from)"
                                :initialized="initHopeArrivalPlanDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="(searchParams.hope_arrival_plan_date_to == '' ? null : searchParams.hope_arrival_plan_date_to)"
                                :initialized="initHopeArrivalPlanDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                </div>
                 <div class="pull-right">
                    <button type="button" class="btn btn-primary btn-clear" @click="clear">クリア</button>
                        <button type="submit" class="btn btn-search">出荷案件検索</button>
                </div>
                <div class="clearfix"></div>
            </form>
        </div>


        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                        <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                        <div class="pull-right">
                            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn)">編集中</p>
                        </div>
                    </div>
                </div>

                
                <!-- タブ -->
                <div class="tab-area">
                    <el-tabs type="border-card" @tab-click="tabClick">
                        <el-tab-pane v-for="(verData) in main.version_list" :key="verData.tab_no"
                            :label="`${verData.warehouse_name}`" >
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label class="control-label">案件</label>
                                            <input type="text" class="form-control" v-bind:readonly="true" v-model="verData.matter_name">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="control-label">得意先名</label>
                                            <input type="text" class="form-control" v-bind:readonly="true" v-model="verData.customer_name">
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12" >
                                            <label class="control-label">案件住所</label>
                                            <div class="input-group">
                                                <input type="text" class="form-control" v-bind:readonly="true" v-model="verData.matter_address">
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-save form-control" v-bind:disabled="isReadOnly" v-on:click="inputMaterAddress" >住所入力</button>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-2">
                                    <label class="control-label">出荷制限事項</label>
                                    <el-checkbox-group v-model="verData.shipping_limit_check_list">
                                        <div class="checkbox" v-for="checkbox in shippingLimitList" :key="checkbox.val">
                                            <el-checkbox class="shipping-limit-list" :label="checkbox.val" v-bind:disabled="isReadOnly">{{ checkbox.text }}</el-checkbox>
                                        </div>
                                    </el-checkbox-group>
                                    <!-- <span class="text-danger">{{ errors.shippingLimitCheckList }}</span> -->
                                </div>
                                <div class="col-md-6">
                                    <div class="row">
                                        <div class="col-md-4" v-bind:class="{'has-error': (tabErrors[verData.tab_no].shipment_kind != '') }">
                                            <label class="control-label">届け先</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="lbl" 
                                                display-member-path="lbl" 
                                                :items-source="shipmentKindList[verData.tab_no]" 
                                                selected-value-path="unique_key"
                                                :selected-value="(verData.shipment_kind + '_' + verData.shipment_kind_id)"
                                                :isDisabled="isReadOnly"
                                                :isRequired=false
                                                :selectedIndexChanged="selectShipmentKind"
                                                :textChanged="setTextChanged"
                                                :max-items="shipmentKindList[verData.tab_no].length"
                                                :itemsSourceChanged="itemsSourceChangedShipmentKind"
                                            >
                                            </wj-auto-complete>
                                            <p class="text-danger">{{ tabErrors[verData.tab_no].shipment_kind }}</p>
                                        </div>
                                        <div class="col-md-4" v-bind:class="{'has-error': (tabErrors[verData.tab_no].delivery_date != '') }">
                                            <label class="control-label">出荷日</label>
                                            <wj-input-date
                                                class="form-control"
                                                :value="verData.delivery_date"
                                                :isRequired=false
                                                :isDisabled="isReadOnly"
                                                :lost-focus="inputDeliveryDate"
                                            ></wj-input-date>
                                            <p class="text-danger">{{ tabErrors[verData.tab_no].delivery_date }}</p>
                                        </div>
                                        <div class="col-md-4" v-bind:class="{'has-error': (tabErrors[verData.tab_no].delivery_time != '') }">
                                            <label class="control-label">納品希望時間</label>
                                                <wj-input-time
                                                    class="form-control"
                                                    :value="verData.delivery_time"
                                                    format="HH:mm"
                                                    :step="30"
                                                    :is-required="false"
                                                    :isDisabled="isReadOnly"
                                                    :lost-focus="inputDeliveryTime">
                                                </wj-input-time>
                                            
                                            <p class="text-danger">{{ tabErrors[verData.tab_no].delivery_time }}</p>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-8" v-bind:class="{'has-error': (tabErrors[verData.tab_no].address1 != '' || tabErrors[verData.tab_no].address2 != '') }">
                                            <label class="control-label">届け先住所</label>
                                            <input type="text" class="form-control" v-model="verData.shipment_address" v-bind:disabled="true">
                                            <p class="text-danger">{{ tabErrors[verData.tab_no].address1 }}</p>
                                            <p class="text-danger">{{ tabErrors[verData.tab_no].address2 }}</p>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="control-label">&nbsp;</label>
                                            <div class="btn-group col-md-12" role="group">
                                                <button type="button" class="btn btn-save form-control" v-on:click="save" v-bind:disabled="(isReadOnly || (tabSaveBtnReadOnly[verData.tab_no] && !isEdit) || isSaveLock)">&nbsp;出荷登録&nbsp;</button>
                                                <!-- <button type="button" class="btn btn-delete" v-on:click="suddenDelivery" v-show="!isEdit" v-bind:disabled="(isEdit || (isReadOnly || (tabSaveBtnReadOnly[verData.tab_no] && !isEdit) || isSaveLock))">出荷納品登録</button> -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-12 quote-ver-row">&nbsp;</div>
                            <!-- 階層 -->
                            
                            <div class="col-md-2 layer-div">
                                <div v-bind:id="'quoteLayerTree-' + verData.tab_no"></div>
                            </div>
                            
                            <!-- グリッド -->
                            <div class="col-md-10 shipment-grid-div">
                                <div v-bind:id="'shipmentGrid-' + verData.tab_no"></div>
                            </div>

                            <!-- コメント入力欄 -->
                            <div class="col-md-12 quote-ver-row">&nbsp;</div>
                            <div class="col-md-12">
                                <div class="col-md-2">
                                </div>
                                <div class="col-md-5" v-bind:class="{'has-error': (tabErrors[verData.tab_no].shipment_comment != '') }">
                                    <label class="control-label">配送用コメント</label>
                                    <textarea class="form-control" v-model="verData.shipment_comment" v-bind:readonly="isReadOnly"></textarea>
                                    <p class="text-danger">{{ tabErrors[verData.tab_no].shipment_comment }}</p>
                                </div>
                            </div>
                        </el-tab-pane>
                    </el-tabs>
                </div>

                <!-- ボタン -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-warning btn-back pull-right" v-on:click="back">戻る</button>
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
.shipment-grid-div {
    padding-right: 0 !important;
    padding-left: 0 !important;
    height: 500px;
}
.tab-area {
    margin-bottom: 10px;
}
.quote-ver-row {
    margin-bottom: 5px;
}
.btn-clear{
    padding-top: 5px;
    height: 33.24px;
}
</style>

<script>
// グリッド初期値
// true/falseのデータ行があると、その列はチェックボックスになる
// 数値のデータ行があると、その行は数値しか入力できなくなる
// var INIT_ROW = [{
//     product_code: '',
//     product_name: '',
//     model: '',
//     shipment_quantity: 0,
//     unit: '',
//     reserve_quantity:'',
//     order_quantity:'',
//     sum_delivery_quantity_graph: '',
//     sum_delivery_quantity: 0,
//     arrival_plan_date: '',
//     hope_arrival_plan_date: '',
//     arrival_quantity_status: '',
//     arrival_quantity: '',
//     chk: true,
//     quote_detail_id: 0,
//     quote_layer_id: 0,
//     construction_id: 0,
//     class_big_id: 0,
//     class_middle_id: 0,
//     class_small_id: 0,
// }];

//var gridReadOnlyFlg = true;
var readOnlyFlg = true;

import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjDetail from '@grapecity/wijmo.grid.detail';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';

export default {
    data: () => ({
        isSaveLock: false,
        isEdit: true,
        loading: false,
        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,
        FLG_ON: 1,
        FLG_OFF: 0,
        ORDER_FLG: 0,
        STOCK_FLG: 1,
        KEEP_FLG: 2,

        searchParams: {
            matter_no: null,
            matter_name: null,
            arrival_plan_date_from: null,
            arrival_plan_date_to: null,
            hope_arrival_plan_date_from: null,
            hope_arrival_plan_date_to: null,
        },
        wjSearchObj: {
            matter_no: {},
            matter_name: {},
            arrival_plan_date_from: {},
            arrival_plan_date_to: {},
            hope_arrival_plan_date_from: {},
            hope_arrival_plan_date_to: {},
        },

        main: {},
        lock: {},

        // 新規作成
        matterCustomerCombo: null,
        ownerNameCombo: null,

        // 開いている版
        focusTabNo: 0,

        // 階層データ
        wjTreeViewControl: [],

        // グリッドデータ
        wjMultiRowControle: [],
        wjDetailGrid: null,
        // スピナーボタン
        cgeSpinerBtn: [],

        gridLayout: [],
        gridChildLayout: [],
        // 非表示カラム
        INVISIBLE_COLS: [
            'quote_detail_id',
        ],

        allReserveList: [],
        tabSaveBtnReadOnly: [],
        tabSaveIdList: [],
        tabErrors: [],
        errorsTemplate: {
            shipment_kind: '',
            delivery_date: '',
            delivery_time: '',
            address1: '',
            address2: '',
            shipment_comment: '',
            shipment_quantity: '',
        },
        shipmentKindList: Array,
        urlparam: '',

        LBL_BLANK_DATA: '―',
    }),
    props: {
        matterList: Array,
        isOwnLock: Number,
        lockdata: {},
        maindata: {},
        shippingLimitList: {},
        gridDataList: Array,
        treeDataList: Array,
        shipmentKindDataList: Array,
        reserveList: Array,
    },
    created() {
        // propsで受け取った値をローカル変数に入れる
        this.main = this.maindata
        this.lock = this.lockdata
        this.shipmentKindList = this.shipmentKindDataList;

        // 編集可
        this.isShowEditBtn = true;
        if (this.rmUndefinedZero(this.main.id) === 0) {
            this.isEdit = false;
            this.isShowEditBtn = false;
            this.isReadOnly = false;
            readOnlyFlg = false;
        }

        // ロック中判定
        if (this.rmUndefinedBlank(this.lock.id) != '' && this.isOwnLock != 1) {
            this.isLocked = true;
            this.isShowEditBtn = false;
            this.isReadOnly = true;
            readOnlyFlg = true;
        }

        // グリッドレイアウトセット
        this.gridLayout = this.getGridLayout()
        this.gridChildLayout = this.getChildGridLayout();
        // タブ内のエラー初期化
        this.tabErrors.push(JSON.parse(JSON.stringify(this.errorsTemplate)));
        this.tabSaveBtnReadOnly.push(false);
        this.tabSaveIdList.push(0);
        
    },
    mounted() {
        
        if (this.isLocked) {
            // ロック中
            this.isShowEditBtn = false;
            this.isReadOnly = true;
            readOnlyFlg = true;
        } else {
            // 編集モードで開くか判定
            var query = window.location.search;
            //if (this.isOwnLock == 1 || this.isEditMode(query, this.isReadOnly, this.isEditable)) {
            if (this.isOwnLock == 1) {
                this.isReadOnly = false
                readOnlyFlg = false;
                this.isShowEditBtn = false
            }
        }

        // 照会モードの場合
        if (this.isReadOnly || !this.isEdit) {
            window.onbeforeunload = null;
        }
                
        // 編集時に来る
        if(this.isEdit && this.gridDataList.length !== 0){
            var detailArr = [];
            var recordArr = [];
            this.main.version_list = [];
            this.allReserveList = [];
            
            this.wjMultiRowControle = [];
            this.wjTreeViewControl = [];
            this.cgeSpinerBtn = [];

            for(var i in this.gridDataList) {
                var rec = this.gridDataList[i];

                // 受注数合計
                rec['sum_all_reserve_quantity'] = 
                parseInt(this.rmUndefinedZero(rec['order_reserve_quantity'])) + 
                parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity'])) +
                parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity']));

                // 納品数合計
                rec['sum_all_delivery_quantity'] = 
                parseInt(this.rmUndefinedZero(rec['sum_order_delivery_quantity'])) + 
                parseInt(this.rmUndefinedZero(rec['sum_stock_delivery_quantity'])) +
                parseInt(this.rmUndefinedZero(rec['sum_keep_delivery_quantity']));

                // 出荷数＋出荷指示数合計
                // rec['sum_all_delivery_quantity'] = 
                // parseInt(this.rmUndefinedZero(rec['sum_order_shipment_quantity'])) + 
                // parseInt(this.rmUndefinedZero(rec['sum_stock_shipment_quantity'])) +
                // parseInt(this.rmUndefinedZero(rec['sum_keep_shipment_quantity']));

                // 出荷合計
                // rec['sum_all_shipment_quantity'] = 
                // parseInt(this.rmUndefinedZero(rec['order_shipment_quantity'])) + 
                // parseInt(this.rmUndefinedZero(rec['stock_shipment_quantity'])) +
                // parseInt(this.rmUndefinedZero(rec['keep_shipment_quantity']));

                rec['sum_all_shipment_quantity'] = 
                    parseInt(this.rmUndefinedZero(rec['order_reserve_quantity_validity'])) + 
                    parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity_validity'])) +
                    parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity_validity']));

                /* 出荷登録数 */
                // 発注
                rec['order_shipment_quantity'] = parseInt(this.rmUndefinedZero(rec['order_shipment_quantity']));
                // 在庫
                rec['stock_shipment_quantity'] = parseInt(this.rmUndefinedZero(rec['stock_shipment_quantity']));
                // 預かり
                rec['keep_shipment_quantity'] = parseInt(this.rmUndefinedZero(rec['keep_shipment_quantity']));


                // if(this.rmUndefinedZero(rec['order_reserve_id']) === 0){
                //     rec['order_reserve_quantity'] = this.LBL_BLANK_DATA;
                // }
                // if(this.rmUndefinedZero(rec['stock_reserve_id']) === 0){
                //     rec['stock_reserve_quantity'] = this.LBL_BLANK_DATA;
                // }
                // if(this.rmUndefinedZero(rec['keep_reserve_id']) === 0){
                //     rec['keep_reserve_quantity'] = this.LBL_BLANK_DATA;
                // }
                if(this.rmUndefinedZero(rec['order_reserve_id']) === 0){
                    rec['order_reserve_quantity_validity'] = this.LBL_BLANK_DATA;
                }
                if(this.rmUndefinedZero(rec['stock_reserve_id']) === 0){
                    rec['stock_reserve_quantity_validity'] = this.LBL_BLANK_DATA;
                }
                if(this.rmUndefinedZero(rec['keep_reserve_id']) === 0){
                    rec['keep_reserve_quantity_validity'] = this.LBL_BLANK_DATA;
                }
                
                // 選択チェックボックス
                rec['chk'] = true;
                
                detailArr.push(rec);
            }
            recordArr.push(detailArr);


            var tmp = ('0000' + this.main.delivery_time).slice(-4);
            var deliveryTime = tmp.slice(0, 2) + ':' + tmp.slice(2);
            
            var shippingLimitCheckList = [];
            for(var key in this.shippingLimitList){
                var tmpRecord = this.shippingLimitList[key];
                if(this.main[key]){
                    shippingLimitCheckList.push(tmpRecord['val']);
                }
            }

            this.main.version_list.push({
                tab_no: 0,
                matter_id: this.main.matter_id,
                shipment_kind: this.main.shipment_kind,
                shipment_kind_id: this.main.shipment_kind_id,
                zipcode : this.main.zipcode,
                pref : this.main.pref,
                address1 : this.main.address1,
                address2 : this.main.address2,
                latitude_jp : this.main.latitude_jp,
                longitude_jp : this.main.longitude_jp,
                latitude_world : this.main.latitude_world,
                longitude_world : this.main.longitude_world,
                from_warehouse_id: this.main.from_warehouse_id,
                delivery_date : this.main.delivery_date,
                delivery_time : deliveryTime,
                shipping_limit_check_list: shippingLimitCheckList,
                shipment_address: this.main.address1 + this.rmUndefinedBlank(this.main.address2),
                shipment_comment: this.main.shipment_comment,

                warehouse_name: this.main.warehouse_name,
                matter_name: this.main.matter_name,
                customer_name: this.main.customer_name,
                matter_address: this.rmUndefinedBlank(this.main.matter_address1) + this.rmUndefinedBlank(this.main.matter_address2),
            });
            detailArr = [];
            var reserveList = [];
            this.reserveList.forEach(element => {
                var shipmentQuantity = this.rmUndefinedZero(element.reserve_quantity) - this.rmUndefinedZero(element.sum_shipment_quantity);
                if (shipmentQuantity >= 0) {
                    element.shipment_quantity = this.rmUndefinedZero(element.reserve_quantity) - this.rmUndefinedZero(element.sum_shipment_quantity);
                } else {
                    element.shipment_quantity = 0;
                }
                reserveList.push(element);
            });
            this.allReserveList = reserveList
            
            this.$nextTick(function() {
            for (var i = 0; i < recordArr.length; i++) {
                var detailArr = recordArr[i];
                var gridItemSource = new wjCore.CollectionView(detailArr);
                var rowCtrl = this.createGridCtrl('#shipmentGrid-' + i, gridItemSource);
                this.wjMultiRowControle.push(rowCtrl);

                var treeCtrl = this.createTreeCtrl('#quoteLayerTree-' + i, this.treeDataList);
                this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
                this.wjTreeViewControl.push(treeCtrl);

                // スピナーボタン追加
                this.cgeSpinerBtn.push({
                    order: this.createGridSpinerBtn(rowCtrl, 'order_reserve_quantity', 2),
                    stock: this.createGridSpinerBtn(rowCtrl, 'stock_reserve_quantity', 2),
                    keep: this.createGridSpinerBtn(rowCtrl, 'keep_reserve_quantity', 2),
                });
            }
            });        
        }else{
            // 検索条件セット
            var query = window.location.search;
            if (query.length > 1) {
                this.setSearchParams(query, this.searchParams);
                // 検索
                this.$nextTick(function() {
                    this.search();
                });
            }
        }

    },
    methods: {
        // ***** 検索条件 *****
        // 案件番号
        initMatterNo: function(sender){
            this.wjSearchObj.matter_no = sender;
        },
        // 案件名
        initMatterName: function(sender){
            this.wjSearchObj.matter_name = sender;
        },
        // 入荷予定日from
        initArrivalPlanDateFrom: function(sender){
            this.wjSearchObj.arrival_plan_date_from = sender;
        },
        // 入荷予定日to
        initArrivalPlanDateTo: function(sender){
            this.wjSearchObj.arrival_plan_date_to = sender;
        },
        // 希望出荷予定日from
        initHopeArrivalPlanDateFrom: function(sender){
            this.wjSearchObj.hope_arrival_plan_date_from = sender;
        },
        // 希望出荷予定日to
        initHopeArrivalPlanDateTo: function(sender){
            this.wjSearchObj.hope_arrival_plan_date_to = sender;
        },
        clear: function() {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = null;
            });
            this.searchParams = {
                matter_no: null,
                matter_name: null,
                arrival_plan_date_from: null,
                arrival_plan_date_to: null,
                hope_arrival_plan_date_from: null,
                hope_arrival_plan_date_to: null,
            };
        },

        // ******************** 案件 ********************
        // 得意先オートコンプリート初期化
        initCustomerCombo: function(sender){
            this.matterCustomerCombo = sender;
        },
        // 施主名オートコンプリート初期化
        initOwnerCombo: function(sender){
            this.ownerNameCombo = sender;
        },

        // 検索
        search() {
            var params = new URLSearchParams();
            // 案件ID [ 案件番号 案件名 ]
            var matterNo = "";
            if (this.wjSearchObj.matter_no.selectedValue) {
               matterNo = this.wjSearchObj.matter_no.selectedValue;
            } else if(this.wjSearchObj.matter_name.selectedValue) {
               matterNo = this.wjSearchObj.matter_name.selectedValue;
            }

            if(this.rmUndefinedBlank(matterNo) === ''){
                alert(MSG_ERROR_MATTER_NO_OR_MATTER_NAME_NO_SELECT);
                return;
            }

            this.loading = true
            params.append('matter_no', this.rmUndefinedZero(matterNo));
            // 入荷予定日（FROM-TO）
            params.append('arrival_plan_date_from', this.rmUndefinedBlank(this.wjSearchObj.arrival_plan_date_from.text));
            params.append('arrival_plan_date_to', this.rmUndefinedBlank(this.wjSearchObj.arrival_plan_date_to.text));
            // 希望出荷予定日（FROM-TO）
            params.append('hope_arrival_plan_date_from', this.rmUndefinedBlank(this.wjSearchObj.hope_arrival_plan_date_from.text));
            params.append('hope_arrival_plan_date_to', this.rmUndefinedBlank(this.wjSearchObj.hope_arrival_plan_date_to.text));

            axios.post('/shipping-instruction/search', params)
            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'arrival_plan_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.arrival_plan_date_from.text));
                    this.urlparam += '&' + 'arrival_plan_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.arrival_plan_date_to.text));
                    this.urlparam += '&' + 'hope_arrival_plan_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.hope_arrival_plan_date_from.text));
                    this.urlparam += '&' + 'hope_arrival_plan_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.hope_arrival_plan_date_to.text));
                    
                    var shipmentDetailList = response.data['shipmentDetailList'];
                    var shipmentKindDataList = response.data['shipmentKindDataList'];
                    var treeDataList = response.data['treeDataList'];
                    this.allReserveList = response.data['allReserveList'];

                    this.allReserveList.forEach(element => {
                        var sumShipment = this.rmUndefinedZero(element.reserve_quantity_validity) - this.rmUndefinedZero(element.sum_shipment_quantity);
                        if (sumShipment > 0) {
                            element.shipment_quantity = sumShipment;
                        } else {
                            element.shipment_quantity = 0;
                        }
                    });

                    var tabNo = 0;
                    var detailArr = [];
                    var recordArr = [];
                    
                    this.main.version_list = [];
                    this.tabErrors = [];
                    this.tabSaveBtnReadOnly = [];
                    this.tabSaveIdList = [];
                    this.shipmentKindList = [];
                    for(var i = 0 ; i < this.wjMultiRowControle.length ; i ++){
                        this.wjMultiRowControle[i].dispose();
                        if(i === 0){
                            document.getElementById('tab-0').click();
                        }
                    }
                    for(var i = 0 ; i < this.wjTreeViewControl.length ; i ++){
                        this.wjTreeViewControl[i].dispose();
                    }
                    // for(var i = 0 ; i < this.cgeSpinerBtn.length ; i ++){
                    //     this.cgeSpinerBtn[i]['order'].control.disposeAll();
                    //     this.cgeSpinerBtn[i]['stock'].control.disposeAll();
                    //     this.cgeSpinerBtn[i]['keep'].control.disposeAll();
                    // }
                    this.wjMultiRowControle = [];
                    this.wjTreeViewControl = [];
                    this.cgeSpinerBtn = [];

                    for(var warehouseId in shipmentDetailList) {
                        
                        if(shipmentDetailList.hasOwnProperty(warehouseId)) {
                            var record = shipmentDetailList[warehouseId];
                            var matterId ='';
                            var matterName ='';
                            var matterAddress ='';
                            var customerName ='';
                            var fromWarehouseId ='';
                            var warehouseName ='';
                            for (var i = 0; i < record.length; i++) {
                                var rec = record[i];
                                if (i === 0) {
                                    matterId    = rec['matter_id'];
                                    matterName      = rec['matter_name'];
                                    matterAddress   = rec['matter_address'];
                                    customerName    = rec['customer_name'];
                                    fromWarehouseId   = rec['from_warehouse_id'];
                                    warehouseName   = rec['warehouse_name'];
                                }



                                // 受注数合計(各引当数の合計)
                                rec['sum_all_reserve_quantity'] = 
                                parseInt(this.rmUndefinedZero(rec['order_reserve_quantity'])) + 
                                parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity'])) +
                                parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity']));

                                // 納品数合計
                                rec['sum_all_delivery_quantity'] = 
                                parseInt(this.rmUndefinedZero(rec['sum_order_delivery_quantity'])) + 
                                parseInt(this.rmUndefinedZero(rec['sum_stock_delivery_quantity'])) +
                                parseInt(this.rmUndefinedZero(rec['sum_keep_delivery_quantity']));

                                // 出荷数＋出荷指示数合計
                                // rec['sum_all_delivery_quantity'] = 
                                // parseInt(this.rmUndefinedZero(rec['sum_order_shipment_quantity'])) + 
                                // parseInt(this.rmUndefinedZero(rec['sum_stock_shipment_quantity'])) +
                                // parseInt(this.rmUndefinedZero(rec['sum_keep_shipment_quantity']));

                                // 出荷合計
                                rec['sum_all_shipment_quantity'] = 
                                parseInt(this.rmUndefinedZero(rec['order_reserve_quantity_validity'])) + 
                                parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity_validity'])) +
                                parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity_validity']));

                                /* 出荷登録数 */
                                // 発注
                                rec['order_shipment_quantity'] = 
                                    parseInt(this.rmUndefinedZero(rec['order_reserve_quantity'])) - parseInt(this.rmUndefinedZero(rec['order_reserve_quantity_validity']));
                                // parseInt(this.rmUndefinedZero(rec['order_reserve_quantity'])) - parseInt(this.rmUndefinedZero(rec['sum_order_shipment_quantity']));
                                // 在庫
                                rec['stock_shipment_quantity'] = 
                                    parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity'])) - parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity_validity']));
                                // parseInt(this.rmUndefinedZero(rec['stock_reserve_quantity'])) - parseInt(this.rmUndefinedZero(rec['sum_stock_shipment_quantity']));
                                // 預かり
                                rec['keep_shipment_quantity'] = 
                                    parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity'])) - parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity_validity']));
                                // parseInt(this.rmUndefinedZero(rec['keep_reserve_quantity'])) - parseInt(this.rmUndefinedZero(rec['sum_keep_shipment_quantity']));
                                if(rec['order_shipment_quantity'] < 0){
                                    rec['order_shipment_quantity'] = 0;
                                }
                                if(rec['stock_shipment_quantity'] < 0){
                                    rec['stock_shipment_quantity'] = 0;
                                }
                                if(rec['keep_shipment_quantity'] < 0){
                                    rec['keep_shipment_quantity'] = 0;
                                }


                                if(this.rmUndefinedZero(rec['order_reserve_id']) === 0){
                                    rec['order_reserve_quantity_validity'] = this.LBL_BLANK_DATA;
                                }
                                if(this.rmUndefinedZero(rec['stock_reserve_id']) === 0){
                                    rec['stock_reserve_quantity_validity'] = this.LBL_BLANK_DATA;
                                }
                                if(this.rmUndefinedZero(rec['keep_reserve_id']) === 0){
                                    rec['keep_reserve_quantity_validity'] = this.LBL_BLANK_DATA;
                                }
                                
                                // 選択チェックボックス
                                rec['chk'] = false;
                                
                                detailArr.push(rec);
                            }
                            recordArr.push(detailArr);

                            this.tabErrors.push(JSON.parse(JSON.stringify(this.errorsTemplate)));
                            this.tabSaveBtnReadOnly.push(true);
                            this.tabSaveIdList.push(0);
                            this.shipmentKindList.push(shipmentKindDataList[warehouseId]);

                            this.main.version_list.push({
                                tab_no: tabNo,
                                matter_id: matterId,
                                shipment_kind: '',
                                shipment_kind_id: '',
                                from_warehouse_id: fromWarehouseId,
                                warehouse_name: warehouseName,
                                matter_name: matterName,
                                customer_name: customerName,
                                matter_address: matterAddress,
                                shipping_limit_check_list: [],
                                delivery_date: null,
                                delivery_time : '00:00',
                            });
                            detailArr = [];
                            tabNo++;
                        }
                    }

                    this.$nextTick(function() {
                        for (var i = 0; i < recordArr.length; i++) {
                            var detailArr = recordArr[i];
                            var gridItemSource = new wjCore.CollectionView(detailArr);
                            var rowCtrl = this.createGridCtrl('#shipmentGrid-' + i, gridItemSource);
                            this.wjMultiRowControle.push(rowCtrl);

                            //var treeCtrl = this.createTreeCtrl('#quoteLayerTree-' + i, this.parseTreeData(quoteLayer[i]));
                            var treeCtrl = this.createTreeCtrl('#quoteLayerTree-' + i, treeDataList[detailArr[0]['from_warehouse_id']]);
                            this.selectTree(treeCtrl, 'top_flg', this.FLG_ON);
                            this.wjTreeViewControl.push(treeCtrl);

                            // スピナーボタン追加
                            this.cgeSpinerBtn.push({
                                order: this.createGridSpinerBtn(rowCtrl, 'order_reserve_quantity', 2),
                                stock: this.createGridSpinerBtn(rowCtrl, 'stock_reserve_quantity', 2),
                                keep: this.createGridSpinerBtn(rowCtrl, 'keep_reserve_quantity', 2),
                            });
                        }
                    });                    
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


        // ******************** 版 ********************
        
        // タブクリック
        tabClick(tab, event) {
            this.focusTabNo = parseInt(tab.index);
        },

        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayout,
                showSort: false,
                allowSorting: false,
                keyActionEnter: wjGrid.KeyAction.None,
            });

            gridCtrl.isReadOnly = readOnlyFlg;

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;
                var cgeSpinerBtn = this.cgeSpinerBtn[this.focusTabNo];
                switch(col.name){
                    case 'order_shipment_quantity': 
                        var spinnerCtrl = cgeSpinerBtn['order'].control;
                        if(this.rmUndefinedZero(row.order_reserve_id) === 0){
                            spinnerCtrl.isReadOnly = true;
                            spinnerCtrl.showSpinner = false;
                        }else{
                            spinnerCtrl.isReadOnly = false;
                            spinnerCtrl.showSpinner = true;
                        }
                    break;
                    case 'stock_shipment_quantity': 
                        var spinnerCtrl = cgeSpinerBtn['stock'].control;
                        if(this.rmUndefinedZero(row.stock_reserve_id) === 0){
                            spinnerCtrl.isReadOnly = true;
                            spinnerCtrl.showSpinner = false;
                        }else{
                            spinnerCtrl.isReadOnly = false;
                            spinnerCtrl.showSpinner = true;
                        }
                    break;
                    case 'keep_shipment_quantity': 
                        var spinnerCtrl = cgeSpinerBtn['keep'].control;
                        if(this.rmUndefinedZero(row.keep_reserve_id) === 0){
                            spinnerCtrl.isReadOnly = true;
                            spinnerCtrl.showSpinner = false;
                        }else{
                            spinnerCtrl.isReadOnly = false;
                            spinnerCtrl.showSpinner = true;
                        }
                    break;
                }

                if (this.gridIsReadOnlyCell(gridCtrl, e.row, e.col)) {
                    e.cancel = true;
                }
            }.bind(this));
            
            // 行編集開始後イベント：グリッドの非表示カラムに階層情報をセット
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var cgeSpinerBtn = this.cgeSpinerBtn[this.focusTabNo];
                var ctrlName = '';
                switch (col.name) {
                    case 'order_shipment_quantity':
                        var spinnerCtrl = cgeSpinerBtn['order'].control;
                        if(spinnerCtrl.isReadOnly){
                            spinnerCtrl.text = row.order_shipment_quantity;
                        }
                        break;
                    case 'stock_shipment_quantity':
                        var spinnerCtrl = cgeSpinerBtn['stock'].control;
                        if(spinnerCtrl.isReadOnly){
                            spinnerCtrl.text = row.order_stock_quantity;
                        }
                        break;
                    case 'keep_shipment_quantity':
                        var spinnerCtrl = cgeSpinerBtn['keep'].control;
                        if(spinnerCtrl.isReadOnly){
                            spinnerCtrl.text = row.order_keep_quantity;
                        }
                        break;
                }
                
            }.bind(this));
            // 表編集後イベント
            gridCtrl.rowEditEnded.addHandler(function (s, e) {
                //var gridData = s.itemsSource.items;
            });
            // セル編集後イベント
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                // var isOdd = ((e.row % 2) == 0);
                // var editColNm = e.panel.columns[e.col].name;
                // var row = s.collectionView.currentEditItem
            });
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });

            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    var _this = this;
                    // チェックボックス生成
                    if (panel.columns[c].name == 'chk') {
                        var checkedCount = 0;
                        for (var i = 0; i < gridCtrl.rows.length; i++) {
                            if (this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != '') {
                                if (gridCtrl.rows[i].dataItem.chk == true) {checkedCount++;}
                            }
                        }
                        var checkBox = '<input type="checkbox">';
                        if(this.isReadOnly || this.isEdit){
                            checkBox = '<input type="checkbox" disabled="true">';
                        }
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (_this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != '') {
                                    var chk = checkBox.checked
                                    gridCtrl.rows[i].dataItem.chk = chk;
                                    gridCtrl.setCellData(i, c, checkBox.checked);
                                }
                            }
                            gridCtrl.endUpdate();
                        });
                    }

                    if (panel.columns[c].name == 'order_reserve_quantity') {
                        var str = cell.innerHTML;
                        var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                        cell.innerHTML = title;
                    }
                    if (panel.columns[c].name == 'stock_reserve_quantity') {
                        var str = cell.innerHTML;
                        var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                        cell.innerHTML = title;
                    }
                    if (panel.columns[c].name == 'keep_reserve_quantity') {
                        var str = cell.innerHTML;
                        var title = str.substr(0, 3) + '<br/>' +  str.substr(3, str.length);
                        cell.innerHTML = title;
                    }
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    //this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var dataItem = panel.rows[r].dataItem;
                    cell.style.color = '';
                    switch(col.name){
                        case 'chk':
                            // データ取得
                            cell.style.textAlign = 'center';
                            if (this.rmUndefinedBlank(dataItem) != ''){
                                if (dataItem.chk == true) {
                                    var box = '<input type="checkbox" checked>';
                                } else {
                                    var box = '<input type="checkbox">';
                                }
                                
                                if(this.isReadOnly || this.isEdit){
                                    box = '<input type="checkbox" disabled="true">';
                                }
                                // var box = '<input type="checkbox">';
                                cell.innerHTML = box;
                                var checkBox = cell.firstChild;
                                checkBox.addEventListener('click', function (e) {
                                    dataItem.chk = !dataItem.chk;
                                    gridCtrl.collectionView.commitEdit();
                                    gridCtrl.refresh();
                                });
                            }
                            // var checkBox = cell.firstChild;
                            // checkBox.disabled = false;
                            // // チェック時にすぐに編集を確定
                            // checkBox.addEventListener('mousedown', function (e) {
                            //     // dataItem.chk = !dataItem.chk;
                            //     gridCtrl.collectionView.commitEdit();
                            // });
                            break;
                        case 'hope_arrival_plan_date':
                            var hope_arrival_plan_date = this.rmUndefinedBlank(dataItem.hope_arrival_plan_date);
                            var next_businessday = this.rmUndefinedBlank(dataItem.next_businessday);
                            if(hope_arrival_plan_date !== '' && next_businessday !== ''){
                                if(hope_arrival_plan_date !== next_businessday){
                                    cell.style.color = 'red';
                                }
                            }
                            break;
                        case 'sum_delivery_quantity_graph':
                            cell.style.padding = '1.8px';
                            var headHtml = '<div class="grid-graph-cell">';
                            var cellHtml = '';

                            var reserveQuantity = this.rmUndefinedZero(dataItem.sum_all_reserve_quantity);
                            var sumDeliveryQuantity = this.rmUndefinedZero(dataItem.sum_all_delivery_quantity);
                            var width = 0;
                            if(reserveQuantity > 0 && sumDeliveryQuantity > 0){
                                width = Math.floor((sumDeliveryQuantity/reserveQuantity) * 100);
                                if(width > 100){
                                    width = 100;
                                }
                            }
                            var bgColor = (width === 100) ? 'bg-green' : 'bg-yellow';

                            cellHtml = '<div class="' + bgColor + '" style="width:' + width + '%"></div>';
                            cell.innerHTML = headHtml + cellHtml + '</div>';
                            break;
                        case 'sum_all_delivery_quantity_area':
                            if(dataItem !== undefined){
                                // 表示加工
                                cell.innerHTML ='<div style="float: left;">' + 
                                                    this.$options.filters.comma_format(dataItem.sum_all_delivery_quantity) +
                                                '</div>';

                                cell.innerHTML += '<div style="float: right;">' +
                                                    this.$options.filters.comma_format(dataItem.sum_all_reserve_quantity) +
                                                '</div>';
                            }
                            break;
                        case 'arrival_quantity_status':
                            // 発注明細の発注数と入荷済数を比較
                            // var orderReservId = this.rmUndefinedZero(dataItem.order_reserve_id);
                            // var arrivalQuantity = this.rmUndefinedZero(dataItem.arrival_quantity);
                            // var stockQuantity = this.rmUndefinedZero(dataItem.order_stock_quantity);

                            // var lbl = '一部入荷';
                            // var bgColor = 'bg-yellow';

                            // if(orderReservId === 0){
                            //     lbl = '引当/預';
                            //     bgColor = 'bg-green';
                            // }else if(arrivalQuantity === 0){
                            //     lbl = '未入荷';
                            //     bgColor = 'bg-red';
                            // }else if(arrivalQuantity >= stockQuantity){
                            //     lbl = '入荷済';
                            //     bgColor = 'bg-light-blue';
                            // }
                            // cell.innerHTML = '<span class="label arrival-quantity-status '+ bgColor+ '">' + lbl +'</span>';
                            
                            // break;

                    }
                }
            }.bind(this);

            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            }.bind(this), true);

            // 列ヘッダー結合
            gridCtrl.allowMerging = wjGrid.AllowMerging.All;
            // grid.columnHeaders.rows.splice(0, 0, new wjGrid.Row());
            for (var i = 0; i < gridCtrl.columns.length; i++) {
                if (i == 0 || i == 4 || i == 5 || i == 6 || i == 7){
                    gridCtrl.columnHeaders.setCellData(0, i, gridCtrl.columnHeaders.getCellData(1, i));
                    gridCtrl.columnHeaders.columns[i].allowMerging = true;
                }
            }  

            var _this = this;
            // 子グリッド定義
            var detailCtrl = new wjDetail.FlexGridDetailProvider(gridCtrl, {
                isAnimated: false,
                maxHeight: 500,
                // detailVisibilityMode: wjDetail.DetailVisibilityMode.ExpandMulti,

                createDetailCell: function (row) {
                    var cell = document.createElement('div');
                    gridCtrl.hostElement.appendChild(cell);
                    var detailGrid = new wjMultiRow.MultiRow(cell, {
                        headersVisibility: wjGrid.HeadersVisibility.Column,
                        autoGenerateColumns: false,
                        itemsSource: _this.getDetails(row.dataItem),
                        layoutDefinition: _this.gridChildLayout,
                        validateEdits: true,
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
                            switch(col.name){
                                case 'arrival_plan_date':
                                    cell.style.textAlign = 'right';
                                    break;
                                case 'stock_flg_txt':
                                    cell.style.textAlign = 'center';
                                    break;
                                case 'reserve_quantity':
                                case 'sum_shipment_quantity':
                                case 'sum_move_quantity':
                                    cell.style.textAlign = 'right';
                                    break;
                                case 'shipment_quantity':
                                    // エラー表示クラス設定
                                    // if ((_this.rmUndefinedZero(parseInt(dataItem.shipment_quantity)) + _this.rmUndefinedZero(parseInt(dataItem.sum_shipment_quantity))) > dataItem.reserve_quantity) {
                                    //     wjCore.addClass(cell, "wj-grid-invalid");
                                    // } else {
                                    //     wjCore.removeClass(cell, "wj-grid-invalid");
                                    // }
                                    cell.style.textAlign = 'right';
                                    break;
                                case 'hope_arrival_plan_date':
                                    var hope_arrival_plan_date = _this.rmUndefinedBlank(dataItem.hope_arrival_plan_date);
                                    var next_businessday = _this.rmUndefinedBlank(dataItem.next_businessday);
                                    if(hope_arrival_plan_date !== '' && next_businessday !== ''){
                                        if(hope_arrival_plan_date !== next_businessday){
                                            cell.style.color = 'red';
                                        }
                                    }
                                    cell.style.textAlign = 'right';
                                    break;
                                case 'arrival_quantity_status':
                                    // 発注明細の発注数と入荷済数を比較
                                    var orderReservId = _this.rmUndefinedZero(dataItem.order_reserve_id);
                                    var arrivalQuantity = _this.rmUndefinedZero(dataItem.arrival_quantity);
                                    var stockQuantity = _this.rmUndefinedZero(dataItem.order_stock_quantity);

                                    var lbl = '一部入荷';
                                    var bgColor = 'bg-yellow';

                                    if(orderReservId == 0){
                                        lbl = '引当/預';
                                        bgColor = 'bg-green';
                                    }else if(arrivalQuantity === 0){
                                        lbl = '未入荷';
                                        bgColor = 'bg-red';
                                    }else if(arrivalQuantity >= stockQuantity){
                                        lbl = '入荷済';
                                        bgColor = 'bg-light-blue';
                                    }
                                    cell.innerHTML = '<span class="label arrival-quantity-status '+ bgColor+ '">' + lbl +'</span>';
                                    
                                    break;

                            }
                        }
                    }.bind(this);

                    // セル編集後イベント
                    detailGrid.cellEditEnded.addHandler(function(s, e) {
                        detailGrid.beginUpdate();
                        // 編集したカラムを特定
                        var editColNm = detailGrid.getBindingColumn(e.panel, e.row, e.col).name;       // 取れるのは上段の列名
                        // 編集行データ取得
                        var row = s.collectionView.currentItem;

                        switch (editColNm) {
                            case 'shipment_quantity':
                                // if ((_this.rmUndefinedZero(parseInt(row.shipment_quantity)) + _this.rmUndefinedZero(parseInt(row.sum_shipment_quantity))) > row.reserve_quantity) {
                                //     _this.isErrorDetailGrid(true);
                                //     s.startEditing(true, e.row, e.col)
                                //     e.cancel = true;
                                // } else {
                                //     _this.isErrorDetailGrid(false);
                                _this.changeShipmentQuantity(row);
                                // }
                                break;
                        }
                        detailGrid.endUpdate();
                    });
                    
                    cell.parentElement.removeChild(cell);
                    return cell;
                },
                
                // 奇数行の展開ボタン削除
                rowHasDetail: function (row) {
                    return row.recordIndex % 2 == 1;
                }
            })

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
        isErrorDetailGrid(isErr) {
            if (isErr) {
                this.isSaveLock = true;
            } else {
                this.isSaveLock = false;
            }
        },
        // 出荷登録数入力イベント
        changeShipmentQuantity(row) {  
            this.allReserveList.forEach(data => {
                if (data.reserve_id == row.reserve_id) {
                    data.shipment_quantity = this.rmUndefinedZero(row.shipment_quantity);                    
                }
            });
            this.wjMultiRowControle.forEach(multirow => {
                var gridRows = multirow.itemsSource.items;
                gridRows.forEach(item => {
                    item.order_shipment_quantity = 0;
                    item.stock_shipment_quantity = 0;
                    item.keep_shipment_quantity = 0;
                    item.reserve_ids.forEach(id => {
                        this.allReserveList.forEach(data => {
                            if (data.reserve_id == id) {
                                if (data.stock_flg == this.ORDER_FLG) {
                                    item.order_shipment_quantity = this.bigNumberPlus(item.order_shipment_quantity, data.shipment_quantity);
                                    item.order_shipment_quantity = this.bigNumberPlus(item.order_shipment_quantity, data.sum_shipment_quantity);
                                }
                                if (data.stock_flg == this.STOCK_FLG) {
                                    item.stock_shipment_quantity = this.bigNumberPlus(item.stock_shipment_quantity, data.shipment_quantity);
                                    item.stock_shipment_quantity = this.bigNumberPlus(item.stock_shipment_quantity, data.sum_shipment_quantity);
                                    // item.stock_shipment_quantity = data.shipment_quantity;
                                }
                                if (data.stock_flg == this.KEEP_FLG) {
                                    item.keep_shipment_quantity = this.bigNumberPlus(item.keep_shipment_quantity, data.shipment_quantity);
                                    item.keep_shipment_quantity = this.bigNumberPlus(item.keep_shipment_quantity, data.sum_shipment_quantity);
                                    // item.keep_shipment_quantity = data.shipment_quantity;
                                }
                            }
                        });
                    });
                    var sum_shipment = 
                        this.rmUndefinedZero(item.order_shipment_quantity) +
                        this.rmUndefinedZero(item.stock_shipment_quantity) +
                        this.rmUndefinedZero(item.keep_shipment_quantity);
                    
                    // item.sum_all_shipment_quantity = sum_shipment;
                });
                multirow.refresh();
            });
        },
        getDetails(row) {
            var reserveIds = row.reserve_ids;
            var itemsSource = [];
            this.allReserveList.forEach(element => {
                reserveIds.forEach(id => {
                    if (id == element.reserve_id) {
                        itemsSource.push({
                            reserve_id: element.reserve_id,
                            order_reserve_id: element.order_reserve_id,
                            stock_flg_txt: element.stock_flg_txt,
                            stock_flg: element.stock_flg,
                            arrival_plan_date: element.arrival_plan_date,
                            hope_arrival_plan_date: element.hope_arrival_plan_date,
                            arrival_quantity_status: element.arrival_quantity_status,
                            reserve_quantity: this.rmUndefinedZero(element.reserve_quantity),
                            reserve_quantity_validity: this.rmUndefinedZero(element.reserve_quantity_validity),
                            sum_shipment_quantity: this.rmUndefinedZero(element.sum_shipment_quantity),
                            sum_move_quantity: element.sum_move_quantity,
                            shipment_quantity: this.rmUndefinedZero(element.shipment_quantity),
                            arrival_quantity: element.arrival_quantity,
                            order_stock_quantity: element.order_stock_quantity,
                        })
                    }
                }); 
            });

            var cv = new wjCore.CollectionView(itemsSource, {
                getError: function(item, property) {
                    switch (property) {
                        case 'shipment_quantity':
                            item[property] < 0 ? this.isSaveLock = true : this.isSaveLock = false;
                            return item[property] < 0
                            ? '0未満は入力できません。'
                            : null;
                    }
                    return null;
                }.bind(this)
            });

            return cv;
        },
        // ツリーコントロール作成
        createTreeCtrl(targetTreeDivId, treeItemsSource) {
            var treeCtrl = new wjNav.TreeView(targetTreeDivId, {
                itemsSource: treeItemsSource,
                displayMemberPath: "header",
                childItemsPath: "items",
            });

            // TreeView選択イベントに処理を紐付け
            treeCtrl.selectedItemChanged.addHandler(function(sender) {
                if(sender.selectedItem === null){return;}
                var topFlg  = sender.selectedItem.top_flg;
                var id      = sender.selectedItem.id;
                var layerFlg= sender.selectedItem.layer_flg; 
                var depth   = sender.selectedItem.depth;
                var treePath= sender.selectedItem.tree_path;
                this.filterGrid(topFlg, id, layerFlg, depth, treePath);
            }.bind(this));

            return treeCtrl;
        },
       
        //　階層選択時にグリッドのフィルター機能で表示をしぼる(配下全て表示)
        filterGrid(topFlg, id, layerFlg, depth, treePath) {
            var currentGridCtrl = this.wjMultiRowControle[this.focusTabNo];
            currentGridCtrl.isReadOnly = readOnlyFlg;
            if(layerFlg === this.FLG_ON){
                currentGridCtrl.collectionView.filter = function(item) {
                    var result = false;
                    if(topFlg === this.FLG_ON){
                        result = true;
                    }else{
                        if(depth === this.QUOTE_CONSTRUCTION_DEPTH){
                            if(item.tree_path === id.toString() || item.tree_path.indexOf(id + this.TREE_PATH_SEPARATOR) === 0){
                                result = true;
                            }
                        }else{
                            if(item.tree_path === (treePath + this.TREE_PATH_SEPARATOR + id) || item.tree_path.indexOf(treePath + this.TREE_PATH_SEPARATOR + id + this.TREE_PATH_SEPARATOR) === 0){
                                result = true;
                            }
                        }
                    }
                    return result;
                }.bind(this)
            }
        },

        // ******************** グリッド ********************
        // グリッドレイアウト定義取得
        getGridLayout () {
            // 仕入先／メーカー名
            //var supplierMap = new wjGrid.DataMap(this.supplierList, 'id', 'supplier_name');

            return [
                { cells: [{ name: 'product_code', binding: 'product_code', header: '品番', width: 230, minWidth: GRID_COL_MIN_WIDTH, isReadOnly: true }] },
                { cells: [
                    { name: 'product_name', binding: 'product_name', header: '商品名', width: '*', minWidth: 300, isReadOnly: true },
                    { name: 'model', binding: 'model', header: '型式・規格', width: '*', minWidth: 300, isReadOnly: true },
                ] },
                // { cells: [
                //     { name: 'stock_quantity', binding: 'stock_quantity', header: '見積数', width: 90, isReadOnly: true, cssClass: 'text-right' },
                //     { name: 'quote_unit', binding: 'quote_unit', header: '見積単位', width: 90, isReadOnly: true },
                // ] },
                { cells: [
                    { name: 'sum_delivery_quantity_graph', binding: 'sum_delivery_quantity_graph', header: '受注数', width: 180, isReadOnly: true},
                    { name: 'sum_all_delivery_quantity_area', binding: 'sum_all_delivery_quantity_area', header: '納品数／引当数', width: 180, isReadOnly: true},
                ] },
                { cells: [
                    { name: 'stock_unit', binding: 'stock_unit', header: '出荷単位', width: 80, isReadOnly: true },
                    { name: 'sum_all_shipment_quantity', binding: 'sum_all_shipment_quantity', header: '出荷合計', width: 80, isReadOnly: true, cssClass: 'text-right' },
                ] },
                { cells: [
                    { name: 'order_reserve_quantity', binding: 'order_reserve_quantity_validity', header: '発注品出荷数', width: 80, isReadOnly: true, cssClass: 'text-right'},
                    // { name: 'order_shipment_quantity', binding: 'order_shipment_quantity', header: '発注品出荷登録数', width: 130, isReadOnly: true, format: 'n0'},
                ] },
                { cells: [
                    { name: 'stock_reserve_quantity', binding: 'stock_reserve_quantity_validity', header: '在庫品出荷数', width: 80, isReadOnly: true, cssClass: 'text-right' },
                    // { name: 'stock_shipment_quantity', binding: 'stock_shipment_quantity', header: '在庫品出荷登録数', width: 130, isReadOnly: true, format: 'n0' },
                ] },
                { cells: [
                    { name: 'keep_reserve_quantity', binding: 'keep_reserve_quantity_validity', header: '預り品出荷数', width: 80, isReadOnly: true, cssClass: 'text-right' },
                    // { name: 'keep_shipment_quantity', binding: 'keep_shipment_quantity', header: '預り品出荷登録数', width: 130, isReadOnly: true, format: 'n0' },
                ] },
                // { cells: [
                //     { name: 'arrival_plan_date', binding: 'arrival_plan_date', header: '入荷予定日', width: 130, isReadOnly: true },
                //     { name: 'hope_arrival_plan_date', binding: 'hope_arrival_plan_date', header: '希望出荷予定日', width: 130, isReadOnly: true },
                // ] },
                // { cells: [
                //     { name: 'arrival_quantity_status', binding: 'arrival_quantity_status', header: '入荷状況', width: 80, isReadOnly: true },
                //     { name: 'arrival_quantity', binding: 'arrival_quantity', header: '入荷数', width: 80, isReadOnly: true, cssClass: 'text-right' },
                // ] },
                { cells: [{ name: 'chk', binding: 'quote_detail_id', header: 'chk', cssClass: 'chkColumn', width: 50, isReadOnly: true, allowMerging: false}] },
                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', header: 'ID', visible: false }] },
            ];
        },
        // 子グリッド定義
        getChildGridLayout () {
            return [
                { cells: [{ name: 'stock_flg_txt', binding: 'stock_flg_txt', header: '在庫種別', width: 100, isReadOnly: true }] },
                { cells: [{ name: 'arrival_plan_date', binding: 'arrival_plan_date', header: '入荷予定日', width: 130, isReadOnly: true }] },
                { cells: [{ name: 'hope_arrival_plan_date', binding: 'hope_arrival_plan_date', header: '希望出荷予定日', width: 130, isReadOnly: true }] },
                { cells: [{ name: 'arrival_quantity_status', binding: 'arrival_quantity_status', header: '入荷状況', width: 110, isReadOnly: true }] },
                { cells: [{ name: 'reserve_quantity_validity', binding: 'reserve_quantity_validity', header: '引当数', width: 80, isReadOnly: true }] },
                { cells: [{ name: 'sum_shipment_quantity', binding: 'sum_shipment_quantity', header: '登録済数', width: 80, isReadOnly: true }] },
                { cells: [{ name: 'sum_move_quantity', binding: 'sum_move_quantity', header: '移管予定数', width: 90, isReadOnly: true }] },
                { cells: [{ name: 'shipment_quantity', binding: 'shipment_quantity', header: '出荷登録数', width: 90, isReadOnly: this.isEdit }] },
                { cells: [{ name: 'reserve_id', binding: 'reserve_id', header: 'reserve_id', width: 0, isReadOnly: true }] },
            ];
        },

        // ******************** 値セット ********************
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },
        selectMatterNo: function(sender) {
            var value = this.rmUndefinedBlank(sender.selectedValue);
            this.searchParams.matter_name = value;
        },
        selectMatterName: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.matter_no = item.matter_no;
            }else{
                this.searchParams.matter_no = '';
            }
        },
        itemsSourceChangedShipmentKind:  function(sender, e) {
            sender.selectedItem = null;
        },
        selectShipmentKind: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.main.version_list[this.focusTabNo].shipment_kind = item.shipment_kind;
                this.main.version_list[this.focusTabNo].shipment_kind_id = item.id;
                this.main.version_list[this.focusTabNo].shipment_address = item.address1 + this.rmUndefinedBlank(item.address2);
                this.main.version_list[this.focusTabNo].zipcode = item.zipcode;
                this.main.version_list[this.focusTabNo].pref = item.pref;
                this.main.version_list[this.focusTabNo].address1 = item.address1;
                this.main.version_list[this.focusTabNo].address2 = item.address2;
                this.main.version_list[this.focusTabNo].latitude_jp = item.latitude_jp;
                this.main.version_list[this.focusTabNo].longitude_jp = item.longitude_jp;
                this.main.version_list[this.focusTabNo].latitude_world = item.latitude_world;
                this.main.version_list[this.focusTabNo].longitude_world = item.longitude_world;
                if(this.rmUndefinedZero(this.tabSaveIdList[this.focusTabNo]) === 0){
                    this.tabSaveBtnReadOnly[this.focusTabNo] = false;
                }
            }else{
                this.main.version_list[this.focusTabNo].shipment_kind = null;
                this.main.version_list[this.focusTabNo].shipment_kind_id = null;
                this.main.version_list[this.focusTabNo].shipment_address = null;
                this.main.version_list[this.focusTabNo].zipcode = null;
                this.main.version_list[this.focusTabNo].pref = null;
                this.main.version_list[this.focusTabNo].address1 = null;
                this.main.version_list[this.focusTabNo].address2 = null;
                this.main.version_list[this.focusTabNo].latitude_jp = null;
                this.main.version_list[this.focusTabNo].longitude_jp = null;
                this.main.version_list[this.focusTabNo].latitude_world = null;
                this.main.version_list[this.focusTabNo].longitude_world = null;
                this.tabSaveBtnReadOnly[this.focusTabNo] = true;
            }
        },
        inputDeliveryDate: function(sender) {
            if(this.rmUndefinedBlank(sender.text) !== ''){
                this.main.version_list[this.focusTabNo].delivery_date = sender.text;
            }else{
                this.main.version_list[this.focusTabNo].delivery_date = null;
            }
        },
        inputDeliveryTime: function(sender) {
            if(this.rmUndefinedBlank(sender.text) !== ''){
                this.main.version_list[this.focusTabNo].delivery_time = sender.text;
            }else{
                this.main.version_list[this.focusTabNo].delivery_time = null;
            }
        },
        // 出荷納品登録
        suddenDelivery() {
            var gridDataList = this.wjMultiRowControle[this.focusTabNo].collectionView.sourceCollection;
            var saveGridDataList = [];
            var saveShippingInstruction = [];
            var noInputShipmentQuantityFlg = false;
            for(var i=0;i<gridDataList.length;i++){
                if(gridDataList[i].chk){
                    saveGridDataList.push(gridDataList[i]);
                }
            }
            if(saveGridDataList.length === 0){
                alert(MSG_ERROR_CHECKBOX_NO_SELECT);
                return;
            }else if(!this.isEdit){
                for(let i in saveGridDataList){
                    if(this.rmUndefinedZero(saveGridDataList[i].order_shipment_quantity) === 0 &&
                       this.rmUndefinedZero(saveGridDataList[i].stock_shipment_quantity) === 0 &&
                       this.rmUndefinedZero(saveGridDataList[i].keep_shipment_quantity) === 0){
                           noInputShipmentQuantityFlg = true;
                    }
                    saveGridDataList[i].reserve_ids.forEach(grid_reserve_id => {
                        this.allReserveList.forEach(data => {
                            if (data.reserve_id == grid_reserve_id && data.shipment_quantity != 0) {
                                // data.quote_id = saveGridDataList[i].quote_id;
                                // data.quote_detail_id = saveGridDataList[i].quote_detail_id;
                                // data.order_detail_id = saveGridDataList[i].order_detail_id;
                                saveShippingInstruction.push(data);
                            }
                        });
                    });
                }
            }
            if(!this.isEdit) {
                if (saveShippingInstruction.length === 0) {
                    alert(MSG_ERROR_NO_INPUT_SHIPMENT_QUANTITY);
                    return;
                }

                if(noInputShipmentQuantityFlg){
                    alert(MSG_ERROR_NO_INPUT_SHIPMENT_QUANTITY);
                    return; 
                }
            }

            this.loading = true
            this.initErr(this.tabErrors[this.focusTabNo]);
            var params = new URLSearchParams();
            var tabData = this.main.version_list[this.focusTabNo];
            //var gridDataList = this.wjMultiRowControle[this.focusVersion].itemsSource.items;
            var deliveryTime = this.rmUndefinedBlank(tabData.delivery_time).replace(':', '');
            var now = moment().format('YYYY/MM/DD');

            // 出荷日が未来日の場合エラー
            if(tabData.delivery_date > now){
                alert(MSG_ERROR_SUDDEN_DELIVERY_DATE);
                return;
            }
            
            var jsonData = JSON.stringify(saveGridDataList);
            params.append('grid_data', jsonData);
            params.append('shipment_id', this.rmUndefinedBlank(this.main.id));  // 編集時は1タブ(倉庫)しか存在しない
            params.append('matter_id', this.rmUndefinedBlank(tabData.matter_id));
            params.append('shipment_kind', this.rmUndefinedBlank(tabData.shipment_kind));
            params.append('shipment_kind_id', this.rmUndefinedBlank(tabData.shipment_kind_id));
            params.append('zipcode', this.rmUndefinedBlank(tabData.zipcode));
            params.append('pref', this.rmUndefinedBlank(tabData.pref));
            params.append('address1', this.rmUndefinedBlank(tabData.address1));
            params.append('address2', this.rmUndefinedBlank(tabData.address2));
            params.append('latitude_jp', this.rmUndefinedBlank(tabData.latitude_jp));
            params.append('longitude_jp', this.rmUndefinedBlank(tabData.longitude_jp));
            params.append('latitude_world', this.rmUndefinedBlank(tabData.latitude_world));
            params.append('longitude_world', this.rmUndefinedBlank(tabData.longitude_world));
            params.append('from_warehouse_id', this.rmUndefinedBlank(tabData.from_warehouse_id));
            params.append('delivery_date', this.rmUndefinedBlank(tabData.delivery_date));
            params.append('delivery_time', deliveryTime);
            params.append('shipping_limit_check_list', this.rmUndefinedBlank(tabData.shipping_limit_check_list));
            params.append('shipment_comment', this.rmUndefinedBlank(tabData.shipment_comment));
            params.append('shipment_list', JSON.stringify(saveShippingInstruction));

            axios.post('/shipping-instruction/delivery', params)
            .then( function (response) {
                this.loading = false
                if (response.data) {
                    if (response.data.status == true) {
                        if(this.isEdit){
                            window.onbeforeunload = null;
                            if(response.data.is_delete){
                                this.back();
                            }else{
                                location.reload();
                            }
                        }else{
                            if (response.data.message) {
                                alert(response.data.message)
                            }
                            this.tabSaveBtnReadOnly[this.focusTabNo] = true;
                            this.tabSaveIdList[this.focusTabNo] = response.data.id;
                        }
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
                    this.showErrMsg(error.response.data.errors, this.tabErrors[this.focusTabNo])
                    if(this.rmUndefinedBlank(this.tabErrors[this.focusTabNo]['address1']) !== '' && this.rmUndefinedBlank(this.tabErrors[this.focusTabNo]['address2']) !== ''){
                        // TODO
                        this.tabErrors[this.focusTabNo]['address1'] = this.tabErrors[this.focusTabNo]['address2'];
                        this.tabErrors[this.focusTabNo]['address2'] = '';
                    }
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
        // 出荷指示登録
        save(){
            var gridDataList = this.wjMultiRowControle[this.focusTabNo].collectionView.sourceCollection;
            var saveGridDataList = [];
            var saveShippingInstruction = [];
            var noInputShipmentQuantityFlg = false;
            for(var i=0;i<gridDataList.length;i++){
                if(gridDataList[i].chk){
                    saveGridDataList.push(gridDataList[i]);
                }
            }
            if(saveGridDataList.length === 0){
                alert(MSG_ERROR_CHECKBOX_NO_SELECT);
                return;
            }else if(!this.isEdit){
                for(let i in saveGridDataList){
                    if(this.rmUndefinedZero(saveGridDataList[i].order_shipment_quantity) === 0 &&
                       this.rmUndefinedZero(saveGridDataList[i].stock_shipment_quantity) === 0 &&
                       this.rmUndefinedZero(saveGridDataList[i].keep_shipment_quantity) === 0){
                           noInputShipmentQuantityFlg = true;
                    }
                    saveGridDataList[i].reserve_ids.forEach(grid_reserve_id => {
                        this.allReserveList.forEach(data => {
                            if (data.reserve_id == grid_reserve_id && data.shipment_quantity != 0) {
                                // data.quote_id = saveGridDataList[i].quote_id;
                                // data.quote_detail_id = saveGridDataList[i].quote_detail_id;
                                // data.order_detail_id = saveGridDataList[i].order_detail_id;
                                saveShippingInstruction.push(data);
                            }
                        });
                    });
                }
            }
            if(!this.isEdit) {
                if (saveShippingInstruction.length === 0) {
                    alert(MSG_ERROR_NO_INPUT_SHIPMENT_QUANTITY);
                    return;
                }

                if(noInputShipmentQuantityFlg){
                    alert(MSG_ERROR_NO_INPUT_SHIPMENT_QUANTITY);
                    return; 
                }
            }


            this.loading = true
            this.initErr(this.tabErrors[this.focusTabNo]);
            var params = new URLSearchParams();
            var tabData = this.main.version_list[this.focusTabNo];
            //var gridDataList = this.wjMultiRowControle[this.focusVersion].itemsSource.items;
            var deliveryTime = this.rmUndefinedBlank(tabData.delivery_time).replace(':', '');
            
            var jsonData = JSON.stringify(saveGridDataList);
            params.append('grid_data', jsonData);
            params.append('shipment_id', this.rmUndefinedBlank(this.main.id));  // 編集時は1タブ(倉庫)しか存在しない
            params.append('matter_id', this.rmUndefinedBlank(tabData.matter_id));
            params.append('shipment_kind', this.rmUndefinedBlank(tabData.shipment_kind));
            params.append('shipment_kind_id', this.rmUndefinedBlank(tabData.shipment_kind_id));
            params.append('zipcode', this.rmUndefinedBlank(tabData.zipcode));
            params.append('pref', this.rmUndefinedBlank(tabData.pref));
            params.append('address1', this.rmUndefinedBlank(tabData.address1));
            params.append('address2', this.rmUndefinedBlank(tabData.address2));
            params.append('latitude_jp', this.rmUndefinedBlank(tabData.latitude_jp));
            params.append('longitude_jp', this.rmUndefinedBlank(tabData.longitude_jp));
            params.append('latitude_world', this.rmUndefinedBlank(tabData.latitude_world));
            params.append('longitude_world', this.rmUndefinedBlank(tabData.longitude_world));
            params.append('from_warehouse_id', this.rmUndefinedBlank(tabData.from_warehouse_id));
            params.append('delivery_date', this.rmUndefinedBlank(tabData.delivery_date));
            params.append('delivery_time', deliveryTime);
            params.append('shipping_limit_check_list', this.rmUndefinedBlank(tabData.shipping_limit_check_list));
            params.append('shipment_comment', this.rmUndefinedBlank(tabData.shipment_comment));
            params.append('shipment_list', JSON.stringify(saveShippingInstruction));
            


            axios.post('/shipping-instruction/save', params)
            .then( function (response) {
                this.loading = false
                if (response.data) {
                    if (response.data.status == true) {
                        if(this.isEdit){
                            window.onbeforeunload = null;
                            if(response.data.is_delete){
                                this.back();
                            }else{
                                location.reload();
                            }
                        }else{
                            if (response.data.message) {
                                alert(response.data.message)
                            }
                            this.tabSaveBtnReadOnly[this.focusTabNo] = true;
                            this.tabSaveIdList[this.focusTabNo] = response.data.id;
                        }
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
                    this.showErrMsg(error.response.data.errors, this.tabErrors[this.focusTabNo])
                    if(this.rmUndefinedBlank(this.tabErrors[this.focusTabNo]['address1']) !== '' && this.rmUndefinedBlank(this.tabErrors[this.focusTabNo]['address2']) !== ''){
                        // TODO
                        this.tabErrors[this.focusTabNo]['address1'] = this.tabErrors[this.focusTabNo]['address2'];
                        this.tabErrors[this.focusTabNo]['address2'] = '';
                    }
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

        // 案件住所入力
        inputMaterAddress(){
            var urlparam = '?';
            var fromURL = '';
            if(this.isEdit){
                fromURL = 'url=/shipping-instruction/' + this.main.id;
            }else{
                fromURL += '&url=/shipping-instruction/new';
                urlparam = this.urlparam;
            }
            var matterId = this.main.version_list[this.focusTabNo].matter_id;
            var link = '/address-edit/' + matterId + urlparam + fromURL;
            location.href = link;
        },
        
        // 戻る
        back() {
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/shipping-delivery-list';
            }
            var listUrl = rtnUrl + query;

            if (!this.isReadOnly && this.isEdit) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'shipping-instruction');
                params.append('keys', this.rmUndefinedBlank(this.main.id));
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
        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'shipping-instruction');
            params.append('keys', this.rmUndefinedBlank(this.main.id));
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
            params.append('screen', 'shipping-instruction');
            params.append('keys', this.rmUndefinedBlank(this.main.id));
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
        // グリッドにスピナーボタン追加
        createGridSpinerBtn(gridCtrl, binding, row){
            return new CustomGridEditor(gridCtrl, binding, wjcInput.InputNumber, {
                format: 'n0',
                step: 1,
                min: 0,
            },2 , row , 1);
        }
    },
};

</script>

<style>
.search-body{
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.shipping-limit-list{
    padding-left: 0px !important;
}
.arrival-quantity-status{
    display: block;
    width: 100%;
    line-height: normal;
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
  background:#ffffff;
  border: 1px solid #929292;
  flex-direction: row; /* 要素を横に並べる */
}
.wj-glyph-plus {
    margin-top: 12px;
}
.wj-glyph-minus{
    margin-top: 12px;
}
.shipment-grid-div  .wj-detail{
    /* margin: 6px 0;
    left: 0px;
    top: 60px; */
    width: 830px !important;
    height: 200px !important;
    margin-left: 280px;
    /* margin: 0 0 0 auto !important; */
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
</style>