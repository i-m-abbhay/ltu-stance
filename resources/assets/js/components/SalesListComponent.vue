<template>
    <div>
        <loading-component :loading="loading" />
        <div class="search-form main-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="btnSearch">
                 <div class="row">
                    <div class="col-md-2">
                        <p class="item-label item-label-title">基本情報</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-3">
                        <label class="control-label">部門名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="id"
                            :initialized="initDepartment"
                            :selected-value="searchParams.department_id"
                            :is-required="false"
                            :items-source="departmentList"
                            :selectedIndexChanged="selectDepartment"
                            :textChanged="setTextChanged"
                            :max-items="departmentList.length"
                            :min-length=1>
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-3">
                        <label class="control-label">担当者名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :initialized="initStaff"
                            :selected-value="searchParams.staff_id"
                            :is-required="false"
                            :items-source="staffList"
                            :selectedIndexChanged="selectStaff"
                            :textChanged="setTextChanged"
                            :max-items="staffList.length"
                            :min-length=1>
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-3">
                        <label class="control-label">顧客名</label>
                        <wj-auto-complete class="form-control" id="acMatterName"
                            search-member-path="customer_name"
                            display-member-path="customer_name"
                            selected-value-path="id"
                            :initialized="initCustomer"
                            :selected-value="searchParams.customer_id"
                            :is-required="false"
                            :items-source="customerList"
                            :textChanged="setTextChanged"
                            :max-items="customerList.length"
                            :min-length=1>
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-3">
                        <label class="control-label">&nbsp;</label>
                        <div class="text-right">
                            <button type="submit" class="btn btn-search btn-md">検索</button>
                            &emsp;
                            <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                       </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="form-horizontal save-form">
            <div class="row">
            </div>
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <div class="main-body" v-show="(urlparam !== '')">
                    <div class="row">
                        <div class="col-md-12">
                            <div v-bind:id="'requestGrid'" style="max-height:140px; border:none;"></div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4" style="margin-top:8px">
                            <div class="input-group tree-grid-operation-input">
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-search"></span>
                                </div>
                                <input v-model="filterText" class="form-control" @input="filterGrid()" v-bind:disabled="isReadOnly">
                            </div>
                        </div>
                        <div class="col-md-8 text-right" style="margin-top:8px">
                            <button type="button" class="btn btn-save " v-on:click="btnConfirmSave" v-bind:disabled="(isReadOnly || btnSave)">確定</button>
                            <button type="button" class="btn btn-danger" v-on:click="release" v-bind:disabled="(isReadOnly || btnRelease)">確定解除</button>
                            <button type="button" class="btn btn-warning" v-on:click="salesDelete" v-bind:disabled="(isReadOnly || btnDelete)">売上期間変更</button>
                        </div>
                    </div>


                    <div class="sales-area">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="row" style="padding:0px 15px 3px 15px">
                                    <p class="item-label item-label-title">案件一覧</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- グリッド -->
                            <div class="col-md-12 sales-grid-div">
                                <div v-bind:id="'salesGrid'"></div>
                            </div>
                        </div>
                    </div>
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
    height: 500px;
}
.sales-area {
    margin-top: 10px;
    margin-bottom: 10px;
}
.btn-request{
    margin-top: 8px;
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

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        btnSave: false,
        btnRelease: false,
        btnDelete: false,
        isSalesComplete: false,


        // メイングリッド
        gridCtrl: null,
        requestGridCtrl: null,
        gridLayout: [],
        gridRequestLayout: [],

        urlparam: '',


        

       

        // 非表示カラム
        INVISIBLE_COLS: [
            // 'quote_detail_id',
            // 'sales_id',
            // 'construction_id',
            // 'layer_flg',
            // 'set_flg',
            // 'parent_quote_detail_id',
            // 'seq_no',
            // 'depth',
            // 'tree_path',
            // 'filter_tree_path',
            // 'sales_use_flg',
            // 'quote_quantity',
            // 'min_quantity',
            // 'sales_flg',
            // 'regular_price',
            // 'update_cost_unit_price',
            // 'update_cost_unit_price_d',
            // 'bk_sales_unit_price',
            // 'sales_all_quantity',
            // 'default_gross_profit_rate',
            // 'invalid_unit_price_flg',
        ],

        

        filterText: '',

        wjSearchObj: {
            department: {},
            staff: {},
            customer: {},
        },
        searchParams: {
            department_id: 0,
            staff_id: 0,
            customer_id: 0,
        },
        cgeDate: {
            requestEDay: {},
            expecteddepositAt: {},
        },
    }),
    props: {
        loginInfo: {},
        salesStatusList: {},
        requestStatusList: {},
        salesDetailFilterInfoList: {},
        
        departmentList: Array,
        staffDepartmentList: Array,
        staffList: Array,
        customerList: Array,
        
    },
    created() {
        // TODO
        this.isReadOnly     = false;

        // グリッドレイアウトセット
        this.gridRequestLayout      = this.getRequestGridLayout();
        this.gridLayout             = this.getGridLayout();
        
    },
    mounted() {

        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);

            this.wjSearchObj.department.selectedValue = this.rmUndefinedZero(this.searchParams.departmant_id);
            this.setStaffList(this.wjSearchObj.department.selectedValue);
            this.wjSearchObj.staff.selectedValue =  this.rmUndefinedZero(this.searchParams.staff_id);
            this.setCustomerList(this.wjSearchObj.department.selectedValue, this.wjSearchObj.staff.selectedValue);
            this.wjSearchObj.customer.selectedValue = this.rmUndefinedZero(this.searchParams.customer_id);
            // 検索
            if(this.rmUndefinedZero(this.wjSearchObj.customer.selectedValue) != 0){
                this.btnSearch();
            }
        }else{
            this.wjSearchObj.department.selectedValue = this.loginInfo.department_id;
            this.setStaffList(this.wjSearchObj.department.selectedValue);
            this.wjSearchObj.staff.selectedValue = this.loginInfo.id ;
            this.setCustomerList(this.wjSearchObj.department.selectedValue, this.wjSearchObj.staff.selectedValue);
        }

    },
    methods: {  
        // 部門
        initDepartment: function(sender){
            this.wjSearchObj.department = sender;
        },
        // 担当者
        initStaff: function(sender){
            this.wjSearchObj.staff = sender;
        },
        // 得意先
        initCustomer: function(sender){
            this.wjSearchObj.customer = sender;
        },

        // 部門選択時に呼ぶ
        selectDepartment(sender){
            if(sender.selectedItem !== null){
                this.setStaffList(sender.selectedItem.id);
                this.setCustomerList(sender.selectedItem.id, this.wjSearchObj.staff.selectedValue);
            }else{
                this.setStaffList(0);
                this.setCustomerList(0, this.wjSearchObj.staff.selectedValue);
            }
        },

        // 担当者のリストをセットする
        setStaffList(departmentId){
            var tmpStaff = this.staffList;
            if (this.rmUndefinedZero(departmentId) !== 0) {
                var tmpArr = [];
                for(var key in this.staffDepartmentList) {
                    if (departmentId === this.staffDepartmentList[key].department_id) {
                        tmpArr.push(this.staffDepartmentList[key]);
                    }
                }
                tmpStaff = [];
                for(var key in this.staffList) {
                    for(var i = 0; i < tmpArr.length; i++) {
                        if (tmpArr[i].staff_id === this.staffList[key].id) {
                            tmpStaff.push(this.staffList[key]);
                            break;
                        }
                    }
                }      
            }
            
            this.wjSearchObj.staff.itemsSource = tmpStaff;
            this.wjSearchObj.staff.selectedIndex = -1;
        },
        selectStaff(sender){
            if(sender.selectedItem !== null){
                this.setCustomerList(this.wjSearchObj.department.selectedValue, sender.selectedItem.id);
            }else{
                this.setCustomerList(this.wjSearchObj.department.selectedValue);
            }
        },
        // 得意先リストをセットする
        setCustomerList(departmentId, staffId){
            var tmpStaff = this.customerList;
            if (this.rmUndefinedZero(departmentId) !== 0 && this.rmUndefinedZero(staffId) !== 0) {
                var tmpStaff = [];
                for(var key in this.customerList) {
                    if (departmentId === this.customerList[key].charge_department_id && staffId === this.customerList[key].charge_staff_id) {
                        tmpStaff.push(this.customerList[key]);
                    }
                }
            }
            this.wjSearchObj.customer.itemsSource = tmpStaff;
            this.wjSearchObj.customer.selectedIndex = -1;
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

            // セル編集確定前イベント
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    case 'request_e_day':
                        // var isDisable = true;
                        // var sumAmount = row.receivable + row.different_amount;
                        // if(this.rmUndefinedZero(row.id) === 0 && sumAmount === 0 && row.is_closed){
                        //     // 過去の請求が「締済」のみ、売掛残＋違算が0円の場合は編集可
                        //     // 売掛 + 違算
                        //     isDisable = false;
                        // }
                        if (this.rmUndefinedZero(row.id) !== 0) {
                            this.cgeDate.requestEDay.control.isDisabled = true;
                            e.cancel = true;
                        }else{
                            this.cgeDate.requestEDay.control.isDisabled = false;
                        }
                        if(this.isKeyPressDeleteOrBackspace()){
                            // delete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'expecteddeposit_at':
                        if(this.isSalesComplete){
                            this.cgeDate.expecteddepositAt.control.isDisabled = true;
                            e.cancel = true;
                        }else{
                            this.cgeDate.expecteddepositAt.control.isDisabled = false;
                        }
                        if(this.isKeyPressDeleteOrBackspace()){
                            // delete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'discount_amount':
                        if(this.isSalesComplete){
                            e.cancel = true;
                        }
                        break;
                }
            }.bind(this));

            // 行編集開始後イベント
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    case 'request_e_day':
                        // 元の日付に戻す
                        var requestEDay = this.cgeDate.requestEDay.control.text;
                        if(this.rmUndefinedBlank(requestEDay) !== ''){
                            if (this.rmUndefinedZero(row.before_id) != 0) {
                                if(!this.isSalesPeriod(requestEDay, row.request_s_day, row.be_request_e_day)){
                                    // if (row.is_closed) {
                                    //     // 過去の請求が全て締まっている
                                    alert(MSG_ERROR_BE_SALES_DATE_PERIOD_OUTSIDE);
                                    // } else {
                                    // 過去の請求が締まっていない
                                    // alert(MSG_ERROR_NOT_CLOSED_SALES_DATE);
                                    // }
                                    e.cancel = true;
                                }
                            }
                        }
                        break;
                }
            }.bind(this));

            // セル編集後イベント
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

                switch (col.name) {
                    case 'request_e_day':
                        // 売上終了日
                        if(e.cancel){
                            row.request_e_day = this.cgeDate.requestEDay.beforeText;
                        }
                        if(this.rmUndefinedBlank(row.request_e_day) !== ''){
                            if(this.cgeDate.requestEDay.beforeText !== row.request_e_day){
                                // 検索
                                this.triggerSearch(row.customer_id, row.request_e_day);
                            }
                        }
                        break;
                    case 'discount_amount':
                        this.calcRequestRow(row);
                        break;
                }
                gridCtrl.collectionView.commitEdit();
                this.gridCtrl.refresh();
            }.bind(this));

            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                var col = gridCtrl.getBindingColumn(panel, r, c);
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    switch(col.name){
                        case 'display_total_sales':
                            cell.innerHTML = '税込<br>当月売上合計';
                            break;
                        case 'request_amount':
                            cell.innerHTML = '税込<br>当月請求金額';
                            break;

                    }
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    var dataItem = panel.rows[r].dataItem;
                    cell.style.color = '';

                    cell.style.textAlign = 'left';
                    if(cell.classList.contains('text-right')){
                        cell.style.textAlign = 'right';
                    }
                    
                    switch(col.name){
                        case 'be_request_e_day':
                            // 締め日
                            if(dataItem !== undefined){
                                var closingText = '';

                                if (dataItem.closing_day === 0) {
                                    closingText = '随時';
                                } else if (dataItem.closing_day === 99) {
                                    closingText = '末日';
                                } else {
                                    closingText = dataItem.be_request_e_day.substr(-2) + '日';
                                }
                                cell.innerHTML = closingText;
                            }
                            break;
                        case 'status_text':
                            // 請求ステータス
                            if(dataItem !== undefined){
                                cell.innerHTML = this.requestStatusList.list[dataItem.status];
                            }
                            break;
                        case 'gross_profit_rate':
                            // 粗利
                            if(dataItem !== undefined){
                                cell.innerHTML = dataItem.gross_profit_rate + '％';
                            }
                            break;
                    }
                }
            }.bind(this);
        
            // 売上終了日
            this.cgeDate.requestEDay        = this.createCgeDate(gridCtrl, 'request_e_day', 2, 1, 2);
            // 入金予定日
            this.cgeDate.expecteddepositAt  = this.createCgeDate(gridCtrl, 'shipment_at', 2, 2, 1);

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
            //gridCtrl.rowHeaders.columns[0].width = 20;
            // 行ヘッダ非表示
            gridCtrl.headersVisibility = wjGrid.HeadersVisibility.Column;

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    
                }
                gridCtrl.collectionView.commitEdit();
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
                    
                }
                gridCtrl.collectionView.commitEdit();
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
                        case '':
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

                    // 請求情報取得
                    var requestInfo         = this.getRequestInfo();
                    var REQUEST_MON       = requestInfo.request_mon;
                    var REQUEST_S_DAY       = requestInfo.request_s_day;
                    var REQUEST_E_DAY       = requestInfo.request_e_day;
                    var EXPECTEDDEPOSIT_AT  = requestInfo.expecteddeposit_at;


                    if(dataItem !== undefined){
                        var linkUrl = '/sales-detail/' + dataItem.matter_id + this.urlparam;
                    }

                    switch(col.name){
                        case 'owner_name':
                            if(dataItem !== undefined){
                                var link = document.createElement('a');
                                link.innerHTML = cell.innerText;
                                link.href = linkUrl + 
                                        '&request_mon=' + encodeURIComponent(REQUEST_MON) +
                                        '&request_s_day=' + encodeURIComponent(REQUEST_S_DAY) +
                                        '&request_e_day=' + encodeURIComponent(REQUEST_E_DAY) +
                                        '&expecteddeposit_at=' + encodeURIComponent(EXPECTEDDEPOSIT_AT);
                                link.classList.add('grid-link-text');
                                cell.replaceChild(link, cell.lastChild);
                            }
                            break;
                        case 'delivery_area':
                            if(dataItem !== undefined){
                                cell.innerHTML = this.$options.filters.comma_format(dataItem.delivery_quantity) + '／' +  this.$options.filters.comma_format(dataItem.return_quantity);
                            }
                            break;
                        case 'btn_status_area':
                            cell.style.padding = '0px';
                            if(dataItem !== undefined){
                                var rId1 = 'status-approve-' + dataItem.matter_id;
                                var rId2 = 'status-sendback-' + dataItem.matter_id;
                                
                                
                                var isDisable = this.isReadOnly;
                                if(!isDisable){
                                    if(this.isSalesComplete){
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
                                    this.btnChangeSalesStatus(true, dataItem.matter_id, dataItem.quote_id);
                                }.bind(this));

                                div.appendChild(btnApprove);

                                var btnSendback = document.createElement('button');
                                btnSendback.type = 'button';
                                btnSendback.id = rId2;
                                btnSendback.classList.add('btn', 'btn-danger', 'btn-status');
                                btnSendback.innerHTML = '否';
                                btnSendback.disabled = isDisable;

                                btnSendback.addEventListener('click', function (e) {
                                    this.btnChangeSalesStatus(false, dataItem.matter_id, dataItem.quote_id);
                                }.bind(this));

                                div.appendChild(btnSendback);

                                cell.appendChild(div);
                                
                            }
                            break;
                        case 'gross_profit_rate':
                            // 粗利
                            if(dataItem !== undefined){
                                cell.innerHTML = dataItem.gross_profit_rate + '％';
                            }
                            break;
                        case 'zero_sales_cnt':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedZero(dataItem.zero_sales_cnt) === 0){
                                    cell.innerHTML = 'なし';
                                }else{
                                    var link = document.createElement('a');
                                    link.innerHTML = cell.innerText;
                                    link.href = linkUrl + 
                                                '&request_e_day=' + encodeURIComponent(REQUEST_E_DAY) +
                                                '&expecteddeposit_at=' + encodeURIComponent(EXPECTEDDEPOSIT_AT) +
                                                '&zero_sales=' + encodeURIComponent(this.salesDetailFilterInfoList.OTHER.key.zero_sales);
                                    link.classList.add('grid-link-text');
                                    cell.replaceChild(link, cell.lastChild);
                                }
                            }
                            break;
                        case 'update_cost_unit_price_cnt':
                            if(dataItem !== undefined){
                                if(this.rmUndefinedZero(dataItem.update_cost_unit_price_cnt) === 0){
                                    cell.innerHTML = 'なし';
                                }else{
                                    var link = document.createElement('a');
                                    link.innerHTML = cell.innerText;
                                    link.href = linkUrl + 
                                                '&request_e_day=' + encodeURIComponent(REQUEST_E_DAY) +
                                                '&cost_edit=' + encodeURIComponent(this.salesDetailFilterInfoList.OTHER.key.cost_edit);
                                    link.classList.add('grid-link-text');
                                    cell.replaceChild(link, cell.lastChild);
                                }
                            }
                            break;
                        case 'matter_complete':
                            cell.style.padding = '0px';
                            if(dataItem !== undefined && dataItem.use_sales_flg == this.FLG_OFF){
                                var rId1 = 'matter-complete-' + dataItem.matter_id;
                                
                                var isDisable = this.isReadOnly;

                                var div = document.createElement('div');
                                div.classList.add('btn-group', 'status-btn-group');
                                div.setAttribute("data-toggle","buttons");

                                var btnComplete = document.createElement('button');
                                btnComplete.type = 'button';
                                btnComplete.id = rId1;
                                btnComplete.classList.add('btn', 'btn-danger', 'btn-complet');
                                btnComplete.innerHTML = '案件完了';
                                btnComplete.disabled = isDisable;

                                btnComplete.addEventListener('click', function (e) {
                                    this.btnConfirmMatterComplete(dataItem.matter_id);
                                }.bind(this));

                                div.appendChild(btnComplete);

                                cell.appendChild(div);
                                
                            }
                            break;       
                    }
                }
            }.bind(this);

            return gridCtrl;
        },
        

        // ******************** グリッド ********************
        // グリッドレイアウト定義取得
        getRequestGridLayout () {
            return [
                { cells: [{ name: 'request_mon_text', binding: 'request_mon_text', header: '計上月', width: 100, isReadOnly: true }] },
                { cells: [{ name: 'customer_name', binding: 'customer_name', header: '得意先名', width: 180, isReadOnly: true }] },
                { cells: [{ name: 'status_text', binding: 'status_text', header: '確定処理', width: 80, isReadOnly: true}] },
                { cells: [{ name: 'be_request_e_day', binding: 'be_request_e_day', header: '締め日', width: 60, isReadOnly: true }] },
                { cells: [{ name: 'request_s_day', binding: 'request_s_day', header: '売上開始日', width: 100, isReadOnly: true }] },
                { cells: [{ name: 'request_e_day', binding: 'request_e_day', header: '売上終了日', width: 130, isReadOnly: false }] },
                { cells: [
                    { name: 'shipment_at', binding: 'shipment_at', header: '請求発送予定', width: 130, isReadOnly: true },
                    { name: 'expecteddeposit_at', binding: 'expecteddeposit_at', header: '入金予定日', width: 130, isReadOnly: false },
                ] },
                { cells: [{ name: 'lastinvoice_amount', binding: 'lastinvoice_amount', header: '前回請求額', width: 90, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [
                    { name: 'offset_amount', binding: 'offset_amount', header: '相殺その他', width: 90, isReadOnly: true, cssClass: 'text-right' },
                    { name: 'deposit_amount', binding: 'deposit_amount', header: '入金額', width: 90, isReadOnly: true, cssClass: 'text-right' },
                ] },
                { cells: [{ name: 'carryforward_amount', binding: 'carryforward_amount', header: '繰越金額', width: 90, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'purchase_volume', binding: 'purchase_volume', header: '当月仕入高', width: 90, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [
                    { name: 'sales', binding: 'sales', header: '当月売上高', width: 120, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'additional_discount_amount', binding: 'additional_discount_amount', header: '(内 値引追加額)', width: 120, isReadOnly: true, cssClass: 'text-right' }
                    ] },
                { cells: [
                    { name: 'profit_total', binding: 'profit_total', header: '粗利額', width: 100, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 100, isReadOnly: true, cssClass: 'text-right'}
                ] },
                { cells: [
                    { name: 'display_consumption_tax_amount', binding: 'display_consumption_tax_amount', header: '消費税額', width: 120, isReadOnly: true, cssClass: 'text-right'},
                    { name: 'discount_amount', binding: 'discount_amount', header: '税調整額', width: 120, isReadOnly: false, format: 'n0', cssClass: 'text-right'}
                ] },
                { cells: [{ name: 'display_total_sales', binding: 'display_total_sales', header: '税込当月売上高', width: 100, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'request_amount', binding: 'request_amount', header: '税込当月請求金額', width: 100, isReadOnly: true, cssClass: 'text-right'}] },
            ];
        },
        getGridLayout () {
            return [
                { cells: [{ name: 'owner_name', binding: 'owner_name', header: '施主棟名', width: 280, isReadOnly: true },] },
                { cells: [{ name: 'department_name', binding: 'department_name', header: '部門名', width: 180, isReadOnly: true },] },
                { cells: [{ name: 'staff_name', binding: 'staff_name', header: '案件担当者', width: 180, isReadOnly: true },] },
                { cells: [{ name: 'delivery_area', binding: 'delivery_area', header: '納品数/返品数', width: 150, isReadOnly: true }] },
                { cells: [{ name: 'purchase_volume', binding: 'purchase_volume', header: '当月仕入高', width: 110, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'sales', binding: 'sales', header: '当月売上高', width: 110, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'additional_discount_amount', binding: 'additional_discount_amount', header: '(内 値引追加額)', width: 120, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'profit_total', binding: 'profit_total', header: '粗利額', width: 100, isReadOnly: true, cssClass: 'text-right'}] },
                { cells: [{ name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 80, isReadOnly: true, format: 'n0', cssClass: 'text-right'}] },
                { cells: [{ name: 'btn_status_area', binding: 'btn_status_area', header: '申請', width: 80, minWidth: 80, maxWidth: 80, isReadOnly: true, }] },
                { cells: [{ name: 'zero_sales_cnt', binding: 'zero_sales_cnt', header: '売価ゼロ', width: 80, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'update_cost_unit_price_cnt', binding: 'update_cost_unit_price_cnt', header: '仕入調整', width: 80, isReadOnly: true, cssClass: 'text-right' }] },
                { cells: [{ name: 'matter_complete', binding: 'matter_complete', header: '案件完了', width: 80, isReadOnly: true }] },

            ];
        },

        // ******************** 値セット ********************
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },


        /****** UTIL系 ******/
        /**
         * 請求データの行計算
         * @param row   行
         */
        calcRequestRow(row){
            // 税込当月売上合計 = 当月売上高 + 税額 + 税調整額 + 違算 + (-相殺)
            row.total_sales     = this.bigNumberPlus(this.bigNumberPlus(row.sales, row.consumption_tax_amount), this.bigNumberPlus(row.different_amount, this.bigNumberPlus(row.discount_amount, (row.payment_offset * -1))));
            // 税込当月請求額 = 当月売上高 + 売掛金
            row.request_amount  = this.bigNumberPlus(row.total_sales, row.receivable);
            // 消費税 = 消費税 + 税調整額
            row.display_consumption_tax_amount  = this.bigNumberPlus(row.consumption_tax_amount, row.discount_amount);
            // 画面表示用:税込当月売上合計 = 当月売上高 + 消費税額 + 税調整額
            row.display_total_sales = this.bigNumberPlus(this.bigNumberPlus(row.sales, row.consumption_tax_amount), row.discount_amount);
        },

        /**
         * 日付を加算する
         * @param yyyyMMdd  元の日付
         * @param addDay    加算日数
         */
        getAddDay(yyyyMMdd, addDay){
            return moment(yyyyMMdd).add(addDay, 'd').format(FORMAT_DATE);
        },

        /**
         * グリッドのカレンダーコントロールを生成する
         * グリッド, 列名, 多段数, n段目, 結合段数
         */
        createCgeDate(gridCtrl, name, multirows, row, rowspan){
            return new CustomGridEditor(gridCtrl, name, wjcInput.InputDate, {
                format: "d",
                isRequired: true,
            }, multirows, row, rowspan);
        },

        /**
         * 請求情報を返す
         */
        getRequestInfo(){
            return this.requestGridCtrl.collectionView.sourceCollection[0];
        },

        /**
         * 期間内か
         * @param salesDate
         * @param requestSday
         * @param requestEday
         * @param isClose
         */
        isSalesPeriod(salesDate, requestSday, requestEday){
            var result = false;
            var salesDate   = this.strToTime(salesDate);
            var requestSday = this.strToTime(requestSday);
            var requestEday = this.strToTime(requestEday);
            if(salesDate !== null){
                // if (isClose) {
                    if(requestSday <= salesDate){
                        result = true;
                    }
                }
                //  else {
                //     if(requestSday <= salesDate && requestEday >= salesDate){
                //         result = true;
                //     }
                // }
                
            // }
            return result;
        },
        
        /**
         * 押したキーがdeleteかbackspaceか
         * @returns result
         */
        isKeyPressDeleteOrBackspace(){
            var result = false;
            if(event.keyCode == 46 || event.keyCode == 8){
                result = true;
            }
            return result;
        },


        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clearSearch() {
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
            });
        },

        /**
         * フィルター
         */
        filterGrid() {
            var filter = this.filterText.toLowerCase();
            this.gridCtrl.collectionView.filter = detail => {
                var result = false;
                // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                result =
                (filter.length == 0) ||
                (detail.owner_name != null && (detail.owner_name).toString().toLowerCase().indexOf(filter) > -1)
                
                return result;
            };
        },

        /**
         * 【ボタンクリック】検索
         */
        btnSearch() {
            if(this.rmUndefinedZero(this.wjSearchObj.customer.selectedValue) !== 0){
                this.triggerSearch(this.wjSearchObj.customer.selectedValue);
            }else{
                alert(MSG_ERROR_CUSTOMER_NAME_NO_INPUT);
            }
        },

        /**
         * 検索
         */
        async triggerSearch(customerId, requestEDay) {
            var params = new URLSearchParams();
            params.append('customer_id', customerId);
            params.append('request_e_day',  this.rmUndefinedBlank(requestEDay));
            var result = await this.search(params);
            if(result !== undefined && result.status === true){
                if(requestEDay === undefined){
                    this.urlparam = '?'
                    this.urlparam += 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue));
                    this.urlparam += '&' + 'staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue));
                    this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue));
                }
                this.initRequestGrid(result.request_info);
                this.initSalesGrid(result.sales_info);
                if(result.message !== ''){
                    alert(result.message);
                }
            }else{
                if(this.rmUndefinedBlank(result.message) !== ''){
                    alert(result.message);
                    if (this.rmUndefinedBlank(this.cgeDate.requestEDay.beforeText) !== '') {
                        this.requestGridCtrl.itemsSource[0].request_e_day = this.cgeDate.requestEDay.beforeText;
                        this.requestGridCtrl.refresh();
                    }
                }else{
                    // 失敗
                    alert(MSG_ERROR);
                }
            }
        },

        /**
         * 請求グリッドの初期化
         * @param requestData 請求データ
         */
        initRequestGrid(requestInfo){
            if(this.requestGridCtrl !== null){
                this.requestGridCtrl.dispose();
                this.cgeDate.requestEDay.control.dispose();
                this.cgeDate.expecteddepositAt.control.dispose();
            }

            // 請求情報の型変換
            requestInfo.lastinvoice_amount = parseInt(this.rmUndefinedZero(requestInfo.lastinvoice_amount));
            requestInfo.offset_amount      = parseInt(this.rmUndefinedZero(requestInfo.offset_amount));
            requestInfo.deposit_amount     = parseInt(this.rmUndefinedZero(requestInfo.deposit_amount));
            requestInfo.receivable         = parseInt(this.rmUndefinedZero(requestInfo.receivable));
            requestInfo.different_amount   = parseInt(this.rmUndefinedZero(requestInfo.different_amount));
            requestInfo.carryforward_amount= parseInt(this.rmUndefinedZero(requestInfo.carryforward_amount));
            requestInfo.purchase_volume    = parseInt(this.rmUndefinedZero(requestInfo.purchase_volume));
            requestInfo.sales              = parseInt(this.rmUndefinedZero(requestInfo.sales));
            requestInfo.additional_discount_amount = parseInt(this.rmUndefinedZero(requestInfo.additional_discount_amount));
            requestInfo.consumption_tax_amount = parseInt(this.rmUndefinedZero(requestInfo.consumption_tax_amount));
            requestInfo.discount_amount    = parseInt(this.rmUndefinedZero(requestInfo.discount_amount));
            requestInfo.profit_total       = parseInt(this.rmUndefinedZero(requestInfo.profit_total));
            requestInfo.gross_profit_rate  = parseFloat(this.rmUndefinedZero(requestInfo.gross_profit_rate));
            requestInfo.total_sales        = parseInt(this.rmUndefinedZero(requestInfo.total_sales));
            requestInfo.display_total_sales        = parseInt(this.rmUndefinedZero(requestInfo.display_total_sales));
            requestInfo.display_consumption_tax_amount        = parseInt(this.rmUndefinedZero(requestInfo.display_consumption_tax_amount));
            requestInfo.request_amount     = parseInt(this.rmUndefinedZero(requestInfo.request_amount));
            requestInfo.payment_offset     = parseInt(this.rmUndefinedZero(requestInfo.payment_offset));

            // 確定/確定解除のボタンの制御
            if(requestInfo.status === this.requestStatusList.val.unprocessed){
                this.isSalesComplete    = false;
                this.btnSave    = false;
                this.btnRelease = true;
                if(this.rmUndefinedZero(requestInfo.id) === 0){
                    this.btnDelete  = true;
                }else{
                    this.btnDelete  = false;
                }
            }else{
                this.isSalesComplete    = true;
                this.btnSave    = true;
                this.btnRelease = false;
                this.btnDelete  = true;
            }

            // 売上開始日になっていない
            if(!requestInfo.is_sales_started){
                this.btnSave    = true;
            }

            // 一時削除された請求が存在する
            if(requestInfo.is_temporary_deleted){
                this.btnDelete  = true;
            }
            
            
            // 自動計算
            if(requestInfo.status === this.requestStatusList.val.unprocessed){
                this.calcRequestRow(requestInfo);
            }

            this.$nextTick(function() {
                var ctrl = this.createRequestGridCtrl('#requestGrid', [requestInfo]);
                this.requestGridCtrl = ctrl;
            });
        },

        /**
         * 売上グリッドの初期化
         * @param salesInfo 売上データ
         */
        initSalesGrid(salesInfo){
            if(this.gridCtrl !== null){
                this.gridCtrl.dispose();
            }
            var gridDataList = [];
            for(var i in salesInfo){
                var row = salesInfo[i];
                row.delivery_quantity   = parseInt(this.rmUndefinedZero(row.delivery_quantity));
                row.return_quantity     = parseInt(this.rmUndefinedZero(row.return_quantity));
                row.purchase_volume     = parseInt(this.rmUndefinedZero(row.purchase_volume));
                row.sales               = parseInt(this.rmUndefinedZero(row.sales));
                row.additional_discount_amount = parseInt(this.rmUndefinedZero(row.additional_discount_amount));
                row.profit_total        = parseInt(this.rmUndefinedZero(row.profit_total));
                row.gross_profit_rate   = parseFloat(this.rmUndefinedZero(row.gross_profit_rate));
                row.zero_sales_cnt      = parseInt(this.rmUndefinedZero(row.zero_sales_cnt));
                row.update_cost_unit_price_cnt = parseInt(this.rmUndefinedZero(row.update_cost_unit_price_cnt));
                gridDataList.push(row);
            }

            this.$nextTick(function() {
                var gridCtrl = this.createGridCtrl('#salesGrid', gridDataList);
                this.gridCtrl = gridCtrl;
            });
        },

        /**
         * 検索
         * @param params
         */
        search(params) {
            this.loading = true;

            var promise = axios.post('/sales-list/search', params)
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

        /**
         * 【ボタンクリック】承認状態の変更
         * @param isApprove         承認押下か
         * @param quoteId           見積ID
         */
        async btnChangeSalesStatus(isApprove, matterId, quoteId){

            var confirmMsg = isApprove ? MSG_CONFIRM_SALES_STATUS_APPLY : MSG_CONFIRM_SALES_STATUS_SENDBACK;
            if(!confirm(confirmMsg)){
                return;
            }

            var requestInfo = this.getRequestInfo();
            var params = new URLSearchParams();
            params.append('matter_id',      matterId);
            params.append('quote_id',       quoteId);
            params.append('request_id',     requestInfo.id);
            params.append('customer_id',    requestInfo.customer_id);
            params.append('customer_name',  requestInfo.customer_name);
            params.append('is_approve',     isApprove);
            
            var result = await this.changeSalesStatus(params);
            if(result !== undefined && result.status === true){
                this.triggerSearch(requestInfo.customer_id);
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

            var promise = axios.post('/sales-list/change-status', params)
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

        /**
         * 【ボタンクリック】案件完了
         * @param matterId           案件ID
         */
        async btnConfirmMatterComplete(matterId){

            if(!confirm(MSG_CONFIRM_MATTER_COMPLETE)){
                return;
            }
            var requestInfo = this.getRequestInfo();

            var params = new URLSearchParams();
            params.append('matter_id',      matterId);
            
            var result = await this.matterComplete(params);
            if(result !== undefined && result.status === true){
                this.triggerSearch(requestInfo.customer_id);
            }else{
                if(this.rmUndefinedBlank(result.msg) !== ''){
                    alert(result.msg);
                }else{
                    // 失敗
                    alert(MSG_ERROR);
                }
                // location.reload()
            }
        },
        
        /**
         * 案件完了
         * @param params
         */
        matterComplete(params) {
            this.loading = true;

            var promise = axios.post('/sales-list/matter-complete', params)
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

        /**
         * 保存ボタンクリック
         */
        async btnConfirmSave(){
            var message = await this.confirmSave();
            if(message !== undefined && this.rmUndefinedBlank(message) !== ''){
                this.save(message);
            }else{
                alert(MSG_ERROR)
            }
        },

        /**
         * 販売額利用配下に納品があるかチェック
         */
        confirmSave(){
            this.loading = true
            var params = new URLSearchParams();
            
            var requestInfo = this.getRequestInfo();
            var params = new URLSearchParams();
            params.append('customer_id',    this.rmUndefinedZero(requestInfo.customer_id));
            var promise = axios.post('/sales-list/confirm-save', params)
                .then( function (response) {
                    if (response.data && response.data.status) {
                        // 成功
                        return response.data.message;
                    }
                }.bind(this))
                .catch(function (error) {
                }.bind(this))
                .finally(function () {
                    this.loading = false;
                }.bind(this));
            return promise;
        },

        /**
         * 保存
         * @param messsage  確認メッセージ
         */
        save(message){
            if (!confirm(message.replace(/\\n/g, '\n'))) {
                return;
            }
            
            this.loading = true
            var params = new URLSearchParams();
            
            var requestInfo = this.getRequestInfo();

            var params = new URLSearchParams();
            params.append('request_id',     this.rmUndefinedZero(requestInfo.id));                  // チェック用
            params.append('customer_id',    this.rmUndefinedZero(requestInfo.customer_id));
            params.append('request_s_day',  this.rmUndefinedBlank(requestInfo.request_s_day));      // チェック用
            params.append('request_e_day',  this.rmUndefinedBlank(requestInfo.request_e_day));
            params.append('shipment_at',    this.rmUndefinedBlank(requestInfo.shipment_at));
            params.append('expecteddeposit_at', this.rmUndefinedBlank(requestInfo.expecteddeposit_at));
            params.append('discount_amount',    this.rmUndefinedZero(requestInfo.discount_amount));
            params.append('request_mon',  this.rmUndefinedBlank(requestInfo.request_mon));

            axios.post('/sales-list/save', params)
            .then( function (response) {
                if (response.data) {
                    if (response.data.status) {
                        // 再検索を実行
                        this.triggerSearch(requestInfo.customer_id);
                    } else {
                        if(this.rmUndefinedBlank(response.data.message) !== ''){
                            alert(response.data.message.replace(/\\n/g, '\n'));
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

        // 確定解除
        release(){

            if (!confirm(MSG_CONFIRM_SALES_RELEASE)) {
                return;
            }
            
            this.loading = true
            var params = new URLSearchParams();
            
            var requestInfo = this.getRequestInfo();
            var params = new URLSearchParams();
            params.append('request_id',     requestInfo.id);
            params.append('customer_id',    requestInfo.customer_id);

            axios.post('/sales-list/release', params)
            .then( function (response) {
                if (response.data) {
                    if (response.data.status) {
                        // 再検索を実行
                        this.triggerSearch(requestInfo.customer_id);
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

        // 請求情報削除
        salesDelete(){

            if (!confirm(MSG_CONFIRM_SALES_DELETE)) {
                return;
            }
            
            this.loading = true
            var params = new URLSearchParams();
            
            var requestInfo = this.getRequestInfo();
            var params = new URLSearchParams();
            params.append('request_id',     requestInfo.id);
            params.append('customer_id',    requestInfo.customer_id);

            axios.post('/sales-list/delete', params)
            .then( function (response) {
                if (response.data) {
                    if (response.data.status) {
                        // 再検索を実行
                        this.triggerSearch(requestInfo.customer_id);
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
.sales-grid-div .wj-detail{
    margin: 6px 0;
    left: 0px;
    top: 60px;
    width: 1085px !important;
    height: 200px !important;
    /*margin-left: 150px;*/
    margin-left: 370px;
}

.item-label{
    margin-bottom: 0px;
}
.item-label-title{
    font-size: 17px;
}
.wj-cells .wj-cell.wj-state-selected {
    background: #0085c7 !important;
}
.wj-cells .wj-cell.wj-state-multi-selected {
    background: #80adbf !important;
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
.btn-complete{
    padding:3px 11.8px;
    border-radius: 0px;
    width: 80px;
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
.grid-link-text{
 color: #337ab7;
}
.wj-cell.wj-state-selected > .grid-link-text{
 color: #fff;
}
.wj-cell.wj-state-multi-selected > .grid-link-text{
 color: #fff;
}
</style>