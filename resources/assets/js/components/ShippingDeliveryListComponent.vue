<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 --> 
            <div class="main-body col-md-12 col-sm-12 col-xs-12">
                <div class="search-form" id="searchForm">
                <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                    <div class="col-md-12 col-sm-12">
                        <div class="col-md-3 col-sm-3">
                            <label class="control-label">得意先名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="customer_name"
                                display-member-path="customer_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.customer_id"
                                :is-required="false"
                                :initialized="initCustomer"
                                :max-items="customerdata.length"
                                :items-source="customerdata">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-2 col-sm-2">
                            <label class="control-label">案件番号</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="matter_no"
                                display-member-path="matter_no"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.matter_no"
                                :is-required="false"
                                :initialized="initMatterNo"
                                :max-items="matterdata.length"
                                :items-source="matterdata">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-3 col-sm-3">
                            <label class="control-label">案件名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="matter_name"
                                display-member-path="matter_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.matter_name"
                                :is-required="false"
                                :initialized="initMatterName"
                                :max-items="matterdata.length"
                                :items-source="matterdata">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-2 col-sm-2">
                            <label class="control-label">現場名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="address_name"
                                display-member-path="address_name"
                                selected-value-path="address_name"
                                :selected-index="-1"
                                :selected-value="searchParams.field_name"
                                :is-required="false"
                                :initialized="initAddress"
                                :max-items="fielddata.length"
                                :items-source="fielddata">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-2 col-sm-2">
                            <label class="control-label">部門名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="department_name"
                                display-member-path="department_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.department_name"
                                :selectedIndexChanged="changeIdxDepartment"
                                :is-required="false"
                                :initialized="initDepartment"
                                :max-items="departmentdata.length"
                                :items-source="departmentdata">
                            </wj-auto-complete>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12">
                        <div class="col-md-2 col-sm-2">
                            <label class="control-label">発注番号</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="order_no"
                                display-member-path="order_no"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.order_no"
                                :is-required="false"
                                :initialized="initOrderNo"
                                :max-items="orderdata.length"
                                :items-source="orderdata">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-4 col-sm-4">
                            <label class="control-label">仕入先／メーカー名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="supplier_name"
                                display-member-path="supplier_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.supplier_name"
                                :is-required="false"
                                :initialized="initSupplier"
                                :max-items="supplierdata.length"
                                :items-source="supplierdata">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-4 col-sm-4">
                            <label class="control-label">商品名</label>
                            <input type="text" class="form-control" v-model="searchParams.product_name">
                            <!-- <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="product_name"
                                :selected-index="-1"
                                :selected-value="searchParams.product_name"
                                :is-required="false"
                                :initialized="initProduct"
                                :max-items="productdata.length"
                                :items-source="productdata">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-md-2 col-sm-2">
                            <label class="control-label">営業担当者名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="staff_name"
                                display-member-path="staff_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.sales_staff_id"
                                :is-required="false"
                                :initialized="initSalesStaff"
                                :max-items="staffdata.length"
                                :items-source="staffdata">
                            </wj-auto-complete>
                        </div>
                    </div>
                    <div class="col-md-5 col-sm-5 col-xs-12">
                        <label class="col-md-12 col-xs-12 text-left">出荷日</label>
                        <wj-input-date id="inputDate1" class="col-md-5 col-sm-4"
                            :value="searchParams.issue_from_date"
                            :selected-value="searchParams.issue_from_date"
                            placeholder=" "
                            :initialized="initIssueFromDate"
                            :isRequired="false"
                        ></wj-input-date>
                        <label class="col-md-1 col-sm-1 text-center">～</label>
                        <wj-input-date id="inputDate2" class="col-md-5 col-sm-4"
                            :value="searchParams.issue_to_date"
                            :selected-value="searchParams.issue_to_date"
                            placeholder=" "
                            :initialized="initIssueToDate"
                            :isRequired="false"
                        ></wj-input-date>
                    </div>
                    <div class="col-md-2 col-sm-2 col-xs-12">
                        <label class="control-label">出荷元倉庫</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="searchParams.from_warehouse"
                            :is-required="false"
                            :initialized="initFromWarehouse"
                            :max-items="warehousedata.length"
                            :items-source="warehousedata">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2 col-sm-2 col-xs-12">
                        <label class="control-label">出荷担当者名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="searchParams.shipping_staff_id"
                            :is-required="false"
                            :initialized="initShippingStaff"
                            :max-items="staffdata.length"
                            :items-source="staffdata">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-2 col-xs-12">
                        <label class="control-label">発送状況</label>
                        <div class="col-md-12">
                            <el-checkbox class="col-md-3" v-model="checkStatus.notLoadingFlg" :true-label="1" :false-label="0">未発送</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkStatus.endLoadingFlg" :true-label="1" :false-label="0">配送中</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkStatus.deliveryFinishFlg" :true-label="1" :false-label="0">納品済</el-checkbox>
                        </div>
                    </div>
                    
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="pull-right">
                            <button type="button" class="btn btn-clear" @click="clear">クリア</button>
                            <button type="submit" class="btn btn-primary btn-search" @click="search">出荷納品検索</button>
                        </div>
                    </div>
                </form>
            </div>
        </div> 
        <br>
         <!-- 絞り込み -->
        <div class="container-fluid main-body col-md-12 col-sm-12 col-xs-12">
            <div class="col-sm-8">
                <div class="input-group">
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.not_arrival" true-label="0" false-label="-1" @input="filter($event)">未入荷</el-checkbox>
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.part_arrival" true-label="2" false-label="-1" @input="filter($event)">一部入荷</el-checkbox>
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.done_arrival" true-label="1" false-label="-1" @input="filter($event)">入荷済</el-checkbox>
                    <div class="col-md-1 col-xs-2"></div>
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.heavy_flg" @input="filter($event)">重量物有</el-checkbox>
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.unic_flg" @input="filter($event)">要ユニック</el-checkbox>
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.rain_flg" @input="filter($event)">雨延期</el-checkbox>
                    <el-checkbox class="col-md-1 col-xs-2" v-model="filterList.transport_flg" @input="filter($event)">小運搬有</el-checkbox>
                    <div class="col-md-6 col-xs-12" style="padding-left:0px;">
                        <el-checkbox class="col-md-3 col-xs-5" v-model="filterList.shipping_only_flg" @input="filter($event)">出荷日</el-checkbox>
                        <wj-input-date class="col-md-6 col-xs-7"
                            placeholder=""
                            :is-required=false
                            :initialized="initShippingDate"
                            :valueChanged="filter"
                        ></wj-input-date>
                    </div>
                    <div class="col-md-6 col-xs-12" style="padding-left:0px;">
                        <el-checkbox class="col-md-4 col-xs-5" v-model="filterList.delivery_time_flg" @input="filter($event)">納品希望時刻</el-checkbox>
                        <div class="col-md-5 col-xs-6">   
                        <!-- <input type="time" class="form-control" id="time" v-model="filterList.delivery_time" @input="filter($event)"> -->
                            <wj-input-time
                                value="00:00"
                                format="HH:mm"
                                :is-required=false
                                :initialized="initIssueTime"
                                :step="30"
                                :text-changed="filter"
                                >
                            </wj-input-time>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-xs-12">
                <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                <div class="pull-right">
                    <button type="button" class="btn btn-primary" style="background-color: #6200EE;" @click="download">Excel出力</button>
                    <button type="button" class="btn btn-primary" @click="OpenCloseDetail">全詳細の開閉</button>
                </div>
            </div>
            <!-- 検索結果グリッド -->
            <wj-multi-row
                :itemsSource="shippings"
                :isReadOnly="true"
                :layoutDefinition="layoutDefinition"
                :initialized="initMultiRow"
                :itemFormatter="itemFormatter"
            > 
               <wj-flex-grid-detail :maxHeight="250" :initialized="initFlexGridDetail" :rowHasDetail="rwhas">
                   <template slot-scope="row">
                    <wj-multi-row 
                        :itemsSource="getDetails(row.item.id)"
                        :layoutDefinition="detailLayoutDefinition"
                        :is-read-only="true"
                        :initialized="initDetailRow"
                        :itemFormatter="detailItemFormatter"
                    ></wj-multi-row>
                   </template>
                </wj-flex-grid-detail>
           </wj-multi-row>
        </div>
    </div>
</template>



<script>
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';
import * as wjDetail from '@grapecity/wijmo.grid.detail';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';

export default {
    data: ()=> ({
        loading: false,    
        FLG_ON : 1,
        FLG_OFF: 0,

        NOT_FLG: 0,
        DONE_FLG: 1,
        PART_FLG: 2,

        tableData: 0,
        detailNo: 1,

        filterList: {
            not_arrival: -1,
            part_arrival: -1,
            done_arrival: -1,
            
            shipping_only_flg: false,
            delivery_date: {},
            delivery_time_flg: false,
            delivery_time: {},

            heavy_flg: false,
            unic_flg: false,
            rain_flg: false,
            transport_flg: false,
        },

        checkStatus: {
            notLoadingFlg: 1,
            endLoadingFlg: 1,
            deliveryFinishFlg: 1,
        },

        searchParams: {
            customer_id: null,
            customer_name: '',
            matter_no_id: null,
            matter_no: '',
            matter_name_id: null,
            matter_name: '',
            field_name: '',
            department_id: null,
            department_name: '',
            order_id: null,
            order_no: '',
            supplier_id: null,
            supplier_name: '',
            product_name: '',
            sales_staff_id: null,
            sales_staff: '',
            issue_from_date: null,
            issue_to_date: null,
            from_warehouse_id: null,
            from_warehouse: '',
            shipping_staff_id: null,
            shipping_staff: '',
        },
        SEARCH_TEXT_ARR: [
            'customer_name',
            'matter_no',
            'matter_name',
            'field_name',
            'department_name',
            'order_no',
            'supplier_name',
            'sales_staff',
            'from_warehouse',
            'shipping_staff',
        ],
        clearSearchParams: {},

        shippings: new wjCore.CollectionView(),
        shippingDetails: new wjCore.CollectionView(),
        shippingSrc: [],

        layoutDefinition: null,
        detailLayoutDefinition: null,
        keepDOM: {},
        keepDetailDOM: {},
        urlparam: '',

        gridSetting: {
            // リサイジング不可 [入荷状況, 出荷制限情報, 出荷／納品状況, 編集／削除、ID]
            deny_resizing_col: [0, 6, 7, 8, 9, 10],
            // 非表示列
            invisible_col: [9],
        },
        detailSetting: {
            // リサイジング不可 [連番,　入荷状況]
            deny_resizing_col: [0, 1, 7],
            // 非表示列
            invisible_col: [7],
        },
        gridPKCol: 9,
        gridDetailPKCol: 7,

        wjShippingGrid: null,
        wjDetailGrid: null,
        wjDetailItemsSource: [],
        wjFlexDetailSetting: null,
        wjSearchObj: {
            customer_name: {},
            matter_no: {},
            matter_name: {},
            field_name: {},
            department_name: {},
            order_no: {},
            supplier_name: {},
            product_name: {},
            sales_staff: {},
            issue_from_date: {},
            issue_to_date: {},
            from_warehouse: {},
            shipping_staff: {},
        },
    }),
    props: {
        supplierdata: Array,
        customerdata: Array,
        fielddata: Array,
        departmentdata: Array,
        orderdata: Array,
        // productdata: Array,
        matterdata: Array,
        staffdata: Array,
        warehousedata: Array,
        issuetimedata: Array,
        staffdepartmentlist: Array,
        initsearchparams: {
            type: Object,
            department_id: Number,
            issue_from_date: String,
            issue_to_date: String,
        }
    },
    created: function() {
        // クリアボタン用
        this.clearSearchParams = Vue.util.extend({}, this.searchParams);
        // 納品希望時刻空行追加
        this.issuetimedata.splice(0, 0, '')

        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.issue_from_date == "") { this.searchParams.issue_from_date = null };
            if (this.searchParams.issue_to_date == "") { this.searchParams.issue_to_date = null };
        } else {
            // 初回の検索条件をセット
            this.setInitSearchParams(this.searchParams, this.initsearchparams);
        }
        // this.search();
        this.layoutDefinition = this.getLayout();
        this.detailLayoutDefinition = this.getDetailLayout();
    },
    mounted: function() {
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                if(this.SEARCH_TEXT_ARR.indexOf(key) >= 0) {
                    wjSearchObj[key].text = this.searchParams[key];
                }
            }.bind(this));

            this.setSearchParams(query, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.issue_from_date == "") { this.searchParams.issue_from_date = null };
            if (this.searchParams.issue_to_date == "") { this.searchParams.issue_to_date = null };

            query = query.substring(1)
            var tmpArr = query.split('&');
            for (var i = 0; i < tmpArr.length; i++) {
                var item = tmpArr[i].split('=');
                if (item.length == 2) {
                    if (item[0].indexOf('notLoadingFlg') > -1) {
                        this.checkStatus.notLoadingFlg = parseInt(item[1]);
                    }
                    if (item[0].indexOf('endLoadingFlg') > -1) {
                        this.checkStatus.endLoadingFlg = parseInt(item[1]);
                    }
                    if (item[0].indexOf('deliveryFinishFlg') > -1) {
                        this.checkStatus.deliveryFinishFlg = parseInt(item[1]);
                    }
                }
            }

            // 検索
            this.search();
        }
        if (this.initsearchparams.length > 1) {
            this.searchParams.issue_from_date = this.initsearchparams.issue_from_date;
            this.searchParams.issue_to_date = this.initsearchparams.issue_to_date;
            this.searchParams.department_name = this.initsearchparams.department_id;
        }
        this.wjSearchObj.department_name.onSelectedIndexChanged();     
    },
    methods: {
        download() {
            var len = this.wjShippingGrid.itemsSource.length;
            if (this.rmUndefinedZero(len) == 0) {
                return;
            }

            this.loading = true;

            var params = new URLSearchParams();

            params.append('customer_id', this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));
            params.append('customer_name', this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
            params.append('matter_no_id', this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
            params.append('matter_no', this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
            params.append('matter_name_id', this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
            params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
            params.append('field_name', this.rmUndefinedBlank(this.wjSearchObj.field_name.text));
            params.append('department_id', this.rmUndefinedBlank(this.wjSearchObj.department_name.selectedValue));
            params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
            params.append('order_id', this.rmUndefinedBlank(this.wjSearchObj.order_no.selectedValue));
            params.append('order_no', this.rmUndefinedBlank(this.wjSearchObj.order_no.text));
            params.append('supplier_id', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.selectedValue));
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('sales_staff_id', this.rmUndefinedBlank(this.wjSearchObj.sales_staff.selectedValue));
            params.append('sales_staff', this.rmUndefinedBlank(this.wjSearchObj.sales_staff.text));
            params.append('issue_from_date', this.rmUndefinedBlank(this.wjSearchObj.issue_from_date.text));
            params.append('issue_to_date', this.rmUndefinedBlank(this.wjSearchObj.issue_to_date.text));
            params.append('from_warehouse_id', this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.selectedValue));
            params.append('from_warehouse', this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.text));
            params.append('shipping_staff_id', this.rmUndefinedBlank(this.wjSearchObj.shipping_staff.selectedValue));
            params.append('shipping_staff', this.rmUndefinedBlank(this.wjSearchObj.shipping_staff.text));
            params.append('notLoadingFlg', this.rmUndefinedBlank(this.checkStatus.notLoadingFlg));      // 未発送フラグ
            params.append('endLoadingFlg', this.rmUndefinedBlank(this.checkStatus.endLoadingFlg));      // 配送中フラグ
            params.append('deliveryFinishFlg', this.rmUndefinedBlank(this.checkStatus.deliveryFinishFlg));  // 納品済フラグ

            axios.post('/shipping-delivery-list/exportExcel', params, {responseType: 'blob' })
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
        changeIdxDepartment: function(sender){
            // 部門を選択したら担当者を絞り込む
            var keepSalesStaffSelectedValue = this.wjSearchObj.sales_staff.selectedValue;
            var keepAvailableFlg = true;
            var tmpArr = this.staffdepartmentlist;
            var tmpStaff = this.staffdata;
            if (sender.selectedItem) {
                keepAvailableFlg = false;
                tmpArr = [];
                for(var key in this.staffdepartmentlist) {
                    if (sender.selectedItem.id == this.staffdepartmentlist[key].department_id) {
                        tmpArr.push(this.staffdepartmentlist[key]);
                    }
                }
                tmpStaff = [];
                for(var key in this.staffdata) {
                    for(var i = 0; i < tmpArr.length; i++) {
                        if (tmpArr[i].staff_id == this.staffdata[key].id) {
                            tmpStaff.push(this.staffdata[key]);
                            if (keepSalesStaffSelectedValue == tmpArr[i].staff_id) {
                                keepAvailableFlg = true;
                            }
                            break;
                        }
                    }
                }      
            }
            this.wjSearchObj.sales_staff.itemsSource = tmpStaff;
            if (keepAvailableFlg) {
                this.wjSearchObj.sales_staff.selectedValue = keepSalesStaffSelectedValue;
            } else {
                this.wjSearchObj.sales_staff.selectedIndex = -1;
            }
        },
        // 出荷指示の物理削除
        del: function (id) {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }

            this.loading = true

            var params = new URLSearchParams();
            params.append('id', id);
            axios.post('/shipping-delivery-list/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data.status) {
                    // 成功
                    var listUrl = '/shipping-delivery-list/' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    if (response.data.message) {
                        alert(response.data.message)
                    } else {
                        alert(MSG_ERROR);
                    }
                    this.search();
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
        // 奇数行の展開ボタンを削除
        rwhas: function(r) {
            return r.recordIndex % 2 == 1;
        },
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行高さの自動調整
            // multirow.autoRowHeights = true;
            // 行ヘッダ非表示
            // multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            
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
            

            this.wjShippingGrid = multirow;
        },
        initDetailRow: function(detailrow) {
            // 行高さ
            detailrow.rows.defaultSize = 40;
            // 行高さの自動調整
            // detailrow.autoRowHeights = true;
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
        getDetails(shipmentID) {
            var arr = [];

            this.shippingSrc.forEach(function (detail) {
                if(detail.shipment_id == shipmentID) {
                    arr.push(detail)
                }
            });
            var itemDetailSource = [];
            var cnt = 0;
            arr.forEach(element => {
                // DOM生成
                // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                this.keepDetailDOM[element.shipment_detail_id] = {
                    arrivalDetailStatus: document.createElement('div'),
                    shippingDetailStatus: document.createElement('div'),
                    deliveryDetailStatus: document.createElement('div'),
                    approval_btn: document.createElement('a'),
                }
                // 入荷状況
                if (element.arrival_status !== undefined) {
                    this.keepDetailDOM[element.shipment_detail_id].arrivalDetailStatus.innerHTML = element.arrival_status.text;
                    this.keepDetailDOM[element.shipment_detail_id].arrivalDetailStatus.classList.add(element.arrival_status.class, 'status');
                }

                // 出荷状況
                this.keepDetailDOM[element.shipment_detail_id].shippingDetailStatus.classList.add('status');
                if (element.shipping_status !== undefined && element.shipping_status.time != null) { 
                    // 出荷済み
                    this.keepDetailDOM[element.shipment_detail_id].shippingDetailStatus.innerHTML += '<div class="shipping-staff-name">' + element.shipping_status.text + '</div>';
                    this.keepDetailDOM[element.shipment_detail_id].shippingDetailStatus.innerHTML += '<div class="shipping-time">' + this.datefilter(element.shipping_status.time) + '</div>';
                } else {   
                    // 未／一部出荷
                    this.keepDetailDOM[element.shipment_detail_id].shippingDetailStatus.innerHTML = element.shipping_status.text;   
                    this.keepDetailDOM[element.shipment_detail_id].shippingDetailStatus.classList.add(element.shipping_status.class);
                }

                // 納品状況
                this.keepDetailDOM[element.shipment_detail_id].deliveryDetailStatus.classList.add('status');
                if(element.delivery_status !== undefined && element.delivery_status.time != null) {  
                    // 納品済み
                    this.keepDetailDOM[element.shipment_detail_id].deliveryDetailStatus.innerHTML += '<div class="delivery-staff-name">' + element.delivery_status.text  + '</div>';
                    this.keepDetailDOM[element.shipment_detail_id].deliveryDetailStatus.innerHTML += '<div class="delivery-time">' + this.datefilter(element.delivery_status.time)  + '</div>';
                } else {    
                    // 未／一部納品
                    this.keepDetailDOM[element.shipment_detail_id].deliveryDetailStatus.innerHTML = element.delivery_status.text;
                    this.keepDetailDOM[element.shipment_detail_id].deliveryDetailStatus.classList.add(element.delivery_status.class);
                }
                cnt++;
            
                itemDetailSource.push({
                    // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                    DetailNo: cnt,
                    shipment_detail_id: element.shipment_detail_id,
                    product_code: element.product_code,
                    rack: element.rack,
                    product_name: element.product_name,
                    model: element.model,
                    unit: element.unit,
                    stock_quantity: element.stock_quantity,
                    quote_quantity: element.quote_quantity,
                    shipment_quantity: element.shipment_quantity,
                    return_quantity: element.return_quantity,
                    id: element.shipment_detail_id
                })   
                this.wjDetailItemsSource[element.shipment_detail_id] = element;
            });

            return itemDetailSource;
        },
        // 検索
        search() {
            // submit.preventの影響で多重送信されていたためフラグ判定
            if (!this.loading){
                this.loading = true;
                this.tableData = 0;
                var params = new URLSearchParams();

                params.append('customer_id', this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));
                params.append('customer_name', this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
                params.append('matter_no_id', this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                params.append('matter_no', this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
                params.append('matter_name_id', this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
                params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                params.append('field_name', this.rmUndefinedBlank(this.wjSearchObj.field_name.text));

                params.append('department_id', this.rmUndefinedBlank(this.wjSearchObj.department_name.selectedValue));
                params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
                params.append('order_id', this.rmUndefinedBlank(this.wjSearchObj.order_no.selectedValue));
                params.append('order_no', this.rmUndefinedBlank(this.wjSearchObj.order_no.text));
                params.append('supplier_id', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.selectedValue));
                params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
                params.append('sales_staff_id', this.rmUndefinedBlank(this.wjSearchObj.sales_staff.selectedValue));
                params.append('sales_staff', this.rmUndefinedBlank(this.wjSearchObj.sales_staff.text));
                params.append('issue_from_date', this.rmUndefinedBlank(this.wjSearchObj.issue_from_date.text));
                params.append('issue_to_date', this.rmUndefinedBlank(this.wjSearchObj.issue_to_date.text));
                params.append('from_warehouse_id', this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.selectedValue));
                params.append('from_warehouse', this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.text));
                params.append('shipping_staff_id', this.rmUndefinedBlank(this.wjSearchObj.shipping_staff.selectedValue));
                params.append('shipping_staff', this.rmUndefinedBlank(this.wjSearchObj.shipping_staff.text));
                
                params.append('notLoadingFlg', this.rmUndefinedBlank(this.checkStatus.notLoadingFlg));      // 未発送フラグ
                params.append('endLoadingFlg', this.rmUndefinedBlank(this.checkStatus.endLoadingFlg));      // 配送中フラグ
                params.append('deliveryFinishFlg', this.rmUndefinedBlank(this.checkStatus.deliveryFinishFlg));  // 納品済フラグ
            
            axios.post('/shipping-delivery-list/search', params)
            

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
                    this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.text));
                    this.urlparam += '&' + 'matter_no_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                    this.urlparam += '&' + 'matter_name_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
                    this.urlparam += '&' + 'field_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.field_name.text));
                    this.urlparam += '&' + 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
                    this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_name.selectedValue));
                    this.urlparam += '&' + 'order_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_no.text));
                    this.urlparam += '&' + 'order_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_no.selectedValue));
                    this.urlparam += '&' + 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                    this.urlparam += '&' + 'supplier_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.selectedValue));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'sales_staff=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.sales_staff.text));
                    this.urlparam += '&' + 'sales_staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.sales_staff.selectedValue));
                    this.urlparam += '&' + 'issue_from_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.issue_from_date.text));
                    this.urlparam += '&' + 'issue_to_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.issue_to_date.text));
                    this.urlparam += '&' + 'from_warehouse=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.text));
                    this.urlparam += '&' + 'from_warehouse_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.selectedValue));
                    this.urlparam += '&' + 'shipping_staff=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.shipping_staff.text));
                    this.urlparam += '&' + 'shipping_staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.shipping_staff.selectedValue));
                    this.urlparam += '&' + 'notLoadingFlg=' + encodeURIComponent(this.checkStatus.notLoadingFlg);
                    this.urlparam += '&' + 'endLoadingFlg=' + encodeURIComponent(this.checkStatus.endLoadingFlg);
                    this.urlparam += '&' + 'deliveryFinishFlg=' + encodeURIComponent(this.checkStatus.deliveryFinishFlg);

                    var itemsSource = [];
                    var itemDetailSource = [];
                    var shipments = response.data.shipments;
                    var shippingDetails = response.data.details;
                    this.shippingSrc = response.data.details;

                    var idx = 0;
                    var dataLength = 0;
                    for(var i in shipments){
                        var element = shipments[i];
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            shipping_limit: document.createElement('div'),
                            matter_no: document.createElement('div'),
                            fromWarehouse: document.createElement('div'),
                            arrivalStatus: document.createElement('div'),
                            shipmentLimit: document.createElement('div'),
                            shippingStatus: document.createElement('div'),
                            deliveryStatus: document.createElement('div'),
                            edit_btn: document.createElement('a'),
                            delete_btn: document.createElement('div'),
                            photo_btn: document.createElement('a'),
                        }

                        // 入荷状況
                        this.keepDOM[element.id].arrivalStatus.innerHTML = element.arrival_status.text;
                        this.keepDOM[element.id].arrivalStatus.classList.add(element.arrival_status.class, 'status');

                        // 納品希望時刻フォーマット
                        var time = element.delivery_time.toString();
                        if (this.rmUndefinedZero(time) == 0) {
                            // 日時が0
                            element.delivery_time = '';
                        } else if(time.length == 4) {
                            // 日時が4桁　例：1230 => 12:30
                            element.delivery_time = time.substr(0, 2) + ':' + time.substr(2, 2);
                        } else if (time.length == 3) {
                            // 日時が3桁　例：130 => 01:30
                            element.delivery_time = '0' + time.substr(0, 1) + ':' + time.substr(1, 2);
                        } else if (time.length == 2) {
                            // 日時が2桁　例：30 => 00:30
                            element.delivery_time = '00:' + time.substr(0, 2);
                        } else if (time.length == 1) {
                            // 日時が1桁　例:3 => 00:03
                            element.delivery_time = '00:0' + time.substr(0, 1);
                        } else {
                            element.delivery_time = '';
                        }

                        // 出荷元倉庫が同値の場合に結合されないよう、一旦キープしておく。
                        this.keepDOM[element.id].fromWarehouse.innerHTML = element.warehouse_name;
                        this.keepDOM[element.id].matter_no.innerHTML = element.matter_no;

                        // SVGアイコン取得
                        var tmpLimit = document.createElement('span');                        
                        if (element.heavy_flg) {
                            tmpLimit.innerHTML = '<svg class="svg-icon"><use width="25" height="25" xlink:href="#heavyIcon" /></svg>'
                            this.keepDOM[element.id].shipping_limit.innerHTML += tmpLimit.outerHTML;
                        }
                        if (element.unic_flg) {
                            tmpLimit.innerHTML = '<svg class="svg-icon"><use width="25" height="25" xlink:href="#unicIcon" /></svg>'
                            this.keepDOM[element.id].shipping_limit.innerHTML += tmpLimit.outerHTML;
                        }
                        if (element.rain_flg) {
                            tmpLimit.innerHTML = '<svg class="svg-icon"><use width="25" height="25" xlink:href="#rainIcon" /></svg>'
                            this.keepDOM[element.id].shipping_limit.innerHTML += tmpLimit.outerHTML;
                        }
                        if (element.transport_flg) {
                            tmpLimit.innerHTML = '<svg class="svg-icon"><use width="25" height="25" xlink:href="#transportIcon" /></svg>'
                            this.keepDOM[element.id].shipping_limit.innerHTML += tmpLimit.outerHTML;
                        }
                        
                        // 出荷状況
                        this.keepDOM[element.id].shippingStatus.innerHTML = element.shipping_status.text;   
                        this.keepDOM[element.id].shippingStatus.classList.add(element.shipping_status.class, 'status');

                        // 既に出荷積込済の場合は編集／削除不可
                        if (element.shipping_status.val == 0) {
                            // 編集／削除可
                            // 編集ボタン
                            this.keepDOM[element.id].edit_btn.innerHTML = "修正";
                            this.keepDOM[element.id].edit_btn.href = '/shipping-instruction/' + element.id + this.urlparam + '&mode=2';
                            this.keepDOM[element.id].edit_btn.classList.add('btn', 'btn-edit','ship-edit');

                            // 削除ボタン
                            var _this = this;
                            this.keepDOM[element.id].delete_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-danger btn-delete ship-delete">削除</button>';
                            this.keepDOM[element.id].delete_btn.addEventListener('click', function(e) {
                                if (e.target.dataset.id)
                                var data = JSON.parse(e.target.dataset.id);
                                _this.del(data);
                            })   
                        } else {
                            // 編集／削除不可
                            // 編集ボタン
                            this.keepDOM[element.id].edit_btn.innerHTML = '<button class="btn btn-edit ship-edit btn-disabled" disabled>修正</button>';
                            // 削除ボタン
                            this.keepDOM[element.id].delete_btn.innerHTML = '<button class="btn btn-danger btn-delete ship-delete btn-disabled" disabled>削除</button>';
                        }

                        // 納品状況
                        var isDisabled = true;
                        if (element.delivery_status.class == 'done-delivery' || element.delivery_status.class == 'part-delivery') {
                            isDisabled = false;

                            this.keepDOM[element.id].deliveryStatus.addEventListener('click', function(e) {
                                // if (e.target.dataset.id)
                                // var data = JSON.parse(e.target.dataset.id);
                                var data = e.target.dataset.id;
                                this.showSignAndPhoto(data);
                            }.bind(this))
                        }
                        this.keepDOM[element.id].deliveryStatus.innerHTML = '<div data-id=' + JSON.stringify(element.id) + ' class="status ' + element.delivery_status.class + '">'+element.delivery_status.text+'</div>';
                        this.keepDOM[element.id].deliveryStatus.disabled = isDisabled;
                        // this.keepDOM[element.id].deliveryStatus.innerHTML = element.delivery_status.text;
                        // this.keepDOM[element.id].deliveryStatus.classList.add(element.delivery_status.class, 'status');


                        itemsSource.push({
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            delivery_date: element.delivery_date,
                            delivery_time: element.delivery_time,
                            matter_no: element.matter_no,
                            matter_name: element.matter_name,
                            customer_name: element.customer_name,
                            address: element.address,
                            product_name: element.product_name,
                            from_warehouse_name: element.warehouse_name,
                            heavy_flg: element.heavy_flg,
                            unic_flg: element.unic_flg,
                            rain_flg: element.rain_flg,
                            transport_flg: element.transport_flg,
                            arrival_status: element.arrival_status,
                            shipping_status: element.shipping_status,
                            delivery_status: element.delivery_status,
                        })

                        dataLength++;
                    }
                    this.tableData = dataLength;

                    // データセット
                    this.wjShippingGrid.itemsSource = itemsSource;
                    this.filter();

                    // 設定更新
                    this.wjShippingGrid = this.applyGridSettings(this.wjShippingGrid);
                    
                    // 描画更新
                    this.wjShippingGrid.refresh();
                    
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
            }
        },
        showSignAndPhoto(id) {
            if (id !== null) {
                var listUrl = '/shipping-delivery-list/show/' + id + this.urlparam;
                console.log(listUrl)
                window.open(listUrl, '_blank');
            }
        },
        // 出荷指示itemFormatter
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
                var item = this.wjShippingGrid.rows[r];
                switch (c) {
                    case 0: // 入荷状況
                        // var item = this.wjShippingGrid.rows[r];
                        if (item.dataItem == undefined || item.dataItem == null) {
                            break;
                        } else {
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].arrivalStatus);
                        }
                        break;
                    case 1: // 出荷日／納品希望時刻
                        cell.style.textAlign = 'right';
                        break;
                    case 2: // 案件番号
                        cell.style.textAlign = 'left';
                        cell.innerHTML = '';
                        if (item.dataItem != null) {
                            cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].matter_no);
                        }
                        break;
                    case 3: // 案件名／得意先名
                        cell.style.textAlign = 'left';
                        break;
                    case 4: // お届け先／商品名
                        cell.style.textAlign = 'left';
                        break;
                    case 5: // 出荷元倉庫
                        cell.style.textAlign = 'center';
                        if (item.dataItem == undefined || item.dataItem == null) {
                            break;
                        } else {
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].fromWarehouse);
                        }
                        break;
                    case 6: // 出荷制限情報
                        cell.style.textAlign = 'left';
                        if (item.dataItem == undefined || item.dataItem == null) {
                            break;
                        } else {
                            cell.innerHTML = '';
                            cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].shipping_limit);
                        }
                        break;
                    case 7: // 出荷／納品状況
                        if (item.dataItem == undefined || item.dataItem == null) {
                            break;
                        } else {
                            if (item.recordIndex == 0){ // 上段
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].shippingStatus);
                            } else if (item.recordIndex == 1) {     // 下段
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].deliveryStatus);
                            }
                        }
                        break;
                    case 8: // 編集ボタン
                         if (item.dataItem == undefined || item.dataItem == null) {
                            break;
                        } else {
                            if (item.recordIndex == 0){ // 上段
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].edit_btn);
                            } else if(item.recordIndex == 1) {     // 下段
                                cell.innerHTML = '';
                                cell.appendChild(this.keepDOM[this.wjShippingGrid.getCellData(r, this.gridPKCol)].delete_btn);
                            }
                        }
                        break;
                    }
            }           
        },
        // 明細itemFormatter
        detailItemFormatter: function (panel, r, c, cell) {
            if (this.keepDetailDOM === undefined || this.keepDetailDOM === null) {
                return;
            }
            // 列のセンタリング
            // if (panel.cellType = wjGrid.CellType.ColumnHeader) {
            //     cell.style.textAlign = 'center';
            // }

            if (panel.cellType == wjGrid.CellType.Cell) {
                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (c) {
                    case 0: // 連番
                        cell.style.textAlign = 'center';
                        break;
                    case 1: // 入荷状況
                        cell.appendChild(this.keepDetailDOM[panel.getCellData(r, this.gridDetailPKCol)].arrivalDetailStatus);
                        break;
                    case 2: 
                        cell.style.textAlign = 'left';
                        break;
                    case 3: 
                        cell.style.textAlign = 'left';
                        break;
                    case 4: // 受注数／単位
                        cell.style.textAlign = 'right';
                        break;
                    case 5:
                        cell.style.textAlign = 'right';
                        break;
                    case 6: // 出荷状況／納品状況
                        if(r % 2 == 0) {
                            cell.appendChild(this.keepDetailDOM[panel.getCellData(r, this.gridDetailPKCol)].shippingDetailStatus);
                        } else if (r % 2 ==  1) {
                            cell.appendChild(this.keepDetailDOM[panel.getCellData(r, this.gridDetailPKCol)].deliveryDetailStatus);
                        }
                        break;
                    }
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
            // grid.columnHeaders.rows.splice(0, 0, new wjGrid.Row());
            for (var i = 0; i < grid.columns.length; i++) {
                if (i == 0 || i == 2 || i == 5 || i == 6){
                    grid.columnHeaders.setCellData(0, i, grid.columnHeaders.getCellData(1, i));
                    grid.columnHeaders.columns[i].allowMerging = true;
                }
            }  
            
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
            
            return grid;
        },
        getDetailLayout() {
            return [
                {
                    header: '連番', cells: [
                        { binding: 'DetailNo', header: 'No.', width: 80, isReadOnly: true },
                    ]
                },
                {
                    header: '入荷状況', cells: [
                        { header: '入荷状況', width: 90, isReadOnly: true },
                    ]
                },
                {
                    header: '品番／棚番', cells: [
                        { binding: 'product_code', header: '品番', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                        { binding: 'rack', header: '棚番', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                    ]
                },                
                {
                    header: '商品', cells: [
                        { binding: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                        { binding: 'model', header: '型式・規格', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                    ]
                },
                {
                    header: '受注数', cells: [
                        { binding: 'stock_quantity', header: '受注数(管理数)', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                        { binding: 'quote_quantity', header: '受注数(入力数)', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                    ]
                },
                {
                    header: '出荷数', cells: [
                        { binding: 'shipment_quantity', header: '出荷数', minWidth: GRID_COL_MIN_WIDTH, width: 100 , isReadOnly: true},
                        { binding: 'return_quantity', header: '返品数', minWidth: GRID_COL_MIN_WIDTH, width: 100 , isReadOnly: true},
                    ]
                },
                {
                    header: '状況', cells: [
                        { header: '出荷状況', width: 110 , isReadOnly: true},
                        { header: '納品状況', width: 110 , isReadOnly: true},
                    ]
                },
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'id' }
                    ]
                }
            ]
        },
        getLayout() {
            return [
                {
                    header: '入荷状況', cells: [
                        { binding: 'id', header: '入荷状況', width: 90 , isReadOnly: true},
                    ]
                },
                {
                    header: '納品', cells: [
                        { binding: 'delivery_date', header: '出荷日', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true},
                        { binding: 'delivery_time', header: '納品希望時刻', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true},
                    ]
                },
                {
                    header: '案件番号', cells: [
                        { binding: 'id', name: 'matter_no', header: '案件番号', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                    ]
                },
                {
                    header: '案件名／得意先名', cells: [
                        { binding: 'matter_name', header: '案件名', minWidth: GRID_COL_MIN_WIDTH, width: 330, isReadOnly: true },                        
                        { binding: 'customer_name', header: '得意先名', minWidth: GRID_COL_MIN_WIDTH, width: 330 , isReadOnly: true},
                    ]
                },
                {
                    header: '明細', cells: [
                        { binding: 'address', header: 'お届け先住所', minWidth: GRID_COL_MIN_WIDTH, minWidth: 300, width: '*' , isReadOnly: true},
                        { binding: 'product_name', header: '商品名・明細数', minWidth: GRID_COL_MIN_WIDTH, minWidth: 300, width: '*', isReadOnly: true },
                    ]
                },
                {
                    header: '倉庫', cells: [
                        { binding: 'id', header: '出荷元倉庫', minWidth: GRID_COL_MIN_WIDTH, width: 155 , isReadOnly: true }
                    ]
                },
                {
                    header: '制限情報', cells: [
                        { binding: 'id', header: '出荷制限情報', width: 130 , isReadOnly: true},
                    ]
                },
                {
                    header: '状況', cells: [
                        { header: '出荷状況', width: 90 , isReadOnly: true},
                        { header: '納品状況', width: 90 , isReadOnly: true},
                    ]
                },
                // {
                //     header: '写真', cells: [
                //         { name: 'photo', header: '写真', width: 90 , isReadOnly: true},
                //     ]
                // },
                {
                    header: '編集', cells: [
                        { header: '編集', width: 90 , isReadOnly: true},
                        { header: '削除', width: 90, isReadOnly: true },
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
        // 検索条件のクリア
        clear: function() {
            this.setInitSearchParams(this.searchParams, this.clearSearchParams);
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = '';
            });
            // var searchParams = this.searchParams;
            // Object.keys(searchParams).forEach(function (key) {
            //     searchParams[key] = '';
            // });
            this.checkStatus = {
                notLoadingFlg: 0,
                endLoadingFlg: 0,
                deliveryFinishFlg: 0,
            }
        },
        datefilter: function (date) {
            return moment(date).format('MM/DD HH:mm')
        },
        filter: function(e) {
            this.wjShippingGrid.collectionView.filter = shipping => {
                var showList = true;
                // 入荷系はor検索
                if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.part_arrival == this.PART_FLG && this.filterList.done_arrival == this.DONE_FLG)
                {
                    // 未入荷＆一部入荷＆入荷済
                    showList = true;
                }
                else if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.part_arrival == this.PART_FLG && this.filterList.done_arrival != this.DONE_FLG) 
                {
                    if (this.filterList.not_arrival != shipping.arrival_status.val && this.filterList.part_arrival != shipping.arrival_status.val) {
                        // 未入荷＆一部入荷
                        showList = false;
                    }
                } 
                else if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.done_arrival == this.DONE_FLG && this.filterList.part_arrival != this.PART_FLG) 
                {
                    if (this.filterList.not_arrival != shipping.arrival_status.val && this.filterList.done_arrival != shipping.arrival_status.val) {
                        // 未入荷＆入荷済
                        showList = false;
                    }
                } 
                else if (this.filterList.part_arrival == this.PART_FLG && this.filterList.done_arrival == this.DONE_FLG && this.filterList.not_arrival != this.NOT_FLG) 
                {
                    if(this.filterList.part_arrival != shipping.arrival_status.val && this.filterList.done_arrival != shipping.arrival_status.val) {
                    // 一部入荷＆入荷済
                    showList = false;
                    }
                } 
                else if (this.filterList.not_arrival == this.NOT_FLG && this.filterList.not_arrival != shipping.arrival_status.val)
                {
                    // 未入荷
                    showList = false;
                } 
                else if (this.filterList.part_arrival == this.PART_FLG && this.filterList.part_arrival != shipping.arrival_status.val) 
                {
                    // 一部入荷
                    showList = false;
                } 
                else if (this.filterList.done_arrival == this.DONE_FLG && this.filterList.done_arrival != shipping.arrival_status.val) 
                {
                    // 入荷済
                    showList = false;
                }
                // and検索
                if (this.filterList.heavy_flg && !shipping.heavy_flg) 
                {
                    // 重量物
                    showList = false;
                } 
                if (this.filterList.unic_flg && !shipping.unic_flg) 
                {
                    // 要ユニック
                    showList = false;
                } 
                if (this.filterList.rain_flg && !shipping.rain_flg) 
                {
                    // 雨延期
                    showList = false;
                } 
                if (this.filterList.transport_flg && !shipping.transport_flg) 
                {
                    // 小運搬
                    showList = false;
                }
                if (this.filterList.shipping_only_flg && this.filterList.delivery_date.text != shipping.delivery_date) 
                {
                    // 出荷日
                    showList = false;
                } 
                if (this.filterList.delivery_time_flg && this.filterList.delivery_time.text < shipping.delivery_time) 
                {
                    // 納品希望時刻
                    showList = false;
                } 
                return showList;
            }
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
        initAddress: function(sender){
            this.wjSearchObj.field_name = sender;
        },
        initDepartment: function(sender){
            this.wjSearchObj.department_name = sender;
        },
        initOrderNo: function(sender){
            this.wjSearchObj.order_no = sender;
        },
        initSupplier: function(sender){
            this.wjSearchObj.supplier_name = sender;
        },
        // initProduct: function(sender){
        //     this.wjSearchObj.product_name = sender;
        // },
        initSalesStaff: function(sender){
            this.wjSearchObj.sales_staff = sender;
        },
        initFromWarehouse: function(sender){
            this.wjSearchObj.from_warehouse = sender;
        },
        initShippingStaff: function(sender){
            this.wjSearchObj.shipping_staff = sender;
        },
        initIssueFromDate: function(sender){
            this.wjSearchObj.issue_from_date = sender;
        },
        initIssueToDate: function(sender){
            this.wjSearchObj.issue_to_date = sender;
        },
        // 絞り込み 選択肢
        initIssueTime: function(sender){
            this.filterList.delivery_time = sender;
        },
        initShippingDate: function(sender){
            this.filterList.delivery_date = sender;
        },
    },
}
</script>

<style>
.main-body {
    width: 100%;
    height: 100%;
    background: #ffffff;
    padding: 15px;
    margin-bottom: 20px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.el-checkbox {
    margin: 10px 30px 10px 0px;
}
#inputDate1 {
    height: 45px;
    margin-left: 15px;
}
#inputDate2 {
    height: 45px;
}
#time {
    text-align: center;
    font-size: 16px;
}
.ship-delete, .ship-edit{
    display: block !important;
    width: 100%;
    height: 22px;
    text-align: center;
    padding: 0px 0px !important;
}
.svg-icon {
    height: 30px !important;
    width: 30px !important;
    margin-top: 8px;
    color: #ff2d55;
}
.ship-delete > a, .ship-edit > a {
    width: 100%;
    height: 100%;
}
.approval_btn{
    display: block !important;
    width: 100%;
    height: 22px;
    text-align: center;
    padding: 0px 0px !important;
    background: #8A8A8F;
}
.btn-disabled {
    background: #8A8A8F;
}
.approval_btn > a{
    width: 100%;
    height: 100%;
}

.status {
    display: block !important;
    width: 100%;
    height: 22px;
    text-align: center;
    padding: 0px 0px !important;
}
.shipping-time, .delivery-time {
    display: block !important;
    text-align: center;
    font-size: 10px;
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
.stock-reserve{
    background: #4CD964;
    color: #fff;
}
/* 出荷状況 */
.not-shipping{
    background: #CB2E25;
    color: #fff;
}
.part-shipping{
    background: #FFCC00;
    color: #fff;
}
.done-shipping{
    background: #6200EE;
    color: #fff;
}
.shipping-staff-name{
    background: #4CD964;
    color: #fff;
    font-size: 12px;
}
/* 納品状況 */
.not-delivery{
    background: #CB2E25;
    color: #fff;
}
.part-delivery{
    background: #FFCC00;
    color: #fff;
}
.done-delivery{
    background: #6200EE;
    color: #fff;
}
.delivery-staff-name {
    background: #4CD964;
    color: #fff;
}
.rtn-delivery{
    background: #FF3B30;
    color: #fff;
}


/*********************************
    以下wijmo系
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

.container-fluid .wj-multirow {
    height: 800px;
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

