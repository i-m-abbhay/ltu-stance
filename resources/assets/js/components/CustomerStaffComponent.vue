<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form main-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-2 col-sm-3 col-xs-12">
                        <label>部門名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="id"
                            :initialized="initDepartment"
                            :selectedIndexChanged="changeIdxSearchDepartment"
                            :selected-index="-1"
                            :selected-value="searchParams.department_name"
                            :is-required="false"
                            :max-items="departmentlist.length"
                            :items-source="departmentlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-2 col-sm-3 col-xs-12">
                        <label>営業担当者名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :initialized="initStaff"
                            :selected-value="searchParams.staff_name"
                            :is-required="false"
                            :max-items="stafflist.length"
                            :items-source="stafflist">
                        </wj-auto-complete>
                    </div>
                    <!-- <div class="col-md-3 col-sm-3 col-xs-12">
                        <label>得意先名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="customer_name"
                            display-member-path="customer_name"
                            selected-value-path="id"
                            :initialized="initCustomer"
                            :selected-index="-1"
                            :selected-value="searchParams.customer_name"
                            :is-required="false"
                            :max-items="customerlist.length"
                            :items-source="customerlist">
                        </wj-auto-complete>
                    </div> -->
                     <div class="col-md-3 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.apply_date != '') }">
                        <label>担当変更予定日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="searchParams.apply_date"
                                :selected-value="searchParams.apply_date"
                                :initialized="initFromApplyDate"
                                :isRequired=false
                            ></wj-input-date>
                            <p class="text-danger">{{ errors.apply_date }}</p>
                            <!-- <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="searchParams.to_apply_date"
                                :selected-value="searchParams.to_apply_date"
                                :initialized="initToApplyDate"
                                :isRequired=false
                            ></wj-input-date> -->
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="form-group">
                        <div class="col-sm-offset-5 col-sm-1">
                            <button type="submit" class="btn btn-search btn-sm form-control">検索</button>
                        </div>
                        <div class="col-sm-1">
                            <button type="button" class="btn btn-clear btn-sm form-control" v-on:click="clearSearch">クリア</button>
                        </div>
                    </div>
                </div>
                <div class="clearfix"></div>
            </form>
        </div>
        <br>

        <div class="result-body col-md-2 col-sm-3 col-xs-12">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    <h5>担当者設定</h5>
                </div>
                <div class="col-md-10 col-sm-12 col-xs-12 col-md-offset-3" v-show="rmUndefinedBlank(inputData.apply_date) != ''">
                    <div class="row">
                        <div class="col-md-4 col-sm-6 col-xs-12">
                            <p>申請者コメント</p>
                            <textarea class="form-control" v-model="inputData.apply_staff_comment" rows="5" v-bind:disabled="inputData.updateLock"></textarea>
                        </div>
                        <div class="col-md-2 col-sm-6 col-xs-12">
                            <label>担当変更予定日</label>
                            <div class="input-group">
                                <wj-input-date class="form-control"
                                    :value="inputData.apply_date"
                                    :selected-value="inputData.apply_date"
                                    :initialized="initApplyDate"
                                    :isRequired=false
                                    :isReadOnly="true"
                                ></wj-input-date>
                            </div>
                            <!-- 承認ステータス -->
                            <div class="col-md-12 col-sm-12 col-xs-12" v-show="rmUndefinedBlank(inputData.id) != ''">
                                <div class="status" v-bind:class="inputData.approval_status.class">{{ inputData.approval_status.text }}</div>
                            </div>

                            <div class="col-sm-6">
                                <button type="button" class="btn btn-delete btn-sm form-control" @click="del" v-show="rmUndefinedBlank(inputData.id) != ''" v-bind:disabled="inputData.updateLock">取消</button>
                            </div>
                            <div class="col-sm-6">
                                <button type="button" class="btn btn-search btn-sm form-control" @click="requestSave" v-bind:disabled="inputData.updateLock">申請</button>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-7 col-xs-12">
                            <p>承認者コメント</p>
                            <textarea class="form-control" v-model="inputData.approval_comment" rows="5" v-bind:readonly="!inputData.isEditable" v-bind:disabled="inputData.updateLock"></textarea>
                        </div>
                        <div class="col-md-2 col-sm-5 col-xs-12" style="padding-top:25px;">
                            <div class="col-sm-8">
                                <button type="button" class="btn btn-search btn-md" @click="approval" v-show="inputData.isEditable">承認</button>
                            </div>
                            <div class="col-sm-8">
                                <button type="button" class="btn btn-delete btn-md" @click="rejection" v-show="inputData.isEditable">否認</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="main-body col-md-12 col-sm-12 col-xs-12">
            <div class="scroll-area" v-show="rmUndefinedBlank(inputData.apply_date) != ''">
                <div class="scroll-item" v-for="(data, index) in cusStaffList" v-bind:key="index" v-bind:id="'cs-' + index">
                    <div class="row">
                        <div class="col-md-6 col-sm-8 col-xs-8">
                            <label>部門名</label>
                            <div class="input-group">
                                <div class="form-control" v-bind:id="'acDep-' + index"></div>
                            <!-- <wj-auto-complete class="form-control" v-bind:id="'AutoDep-' + index"
                                search-member-path="department_name"
                                display-member-path="department_name"
                                selected-value-path="id"
                                :selectedIndexChanged="changeIdxDepartment"
                                :initialized="initDepartments"
                                :selected-index="-1"
                                :selected-value="cusStaffList[index].department_id"
                                :isReadOnly="(rmUndefinedBlank(cusStaffList[index].department_id) != '' && inputData.updateLock)"
                                :is-required="false"
                                :max-items="departmentlist.length"
                                :items-source="departmentlist">
                            </wj-auto-complete> -->
                            </div>
                        </div>
                        <div class="col-md-6 col-sm-8 col-xs-8">
                            <label>担当者名</label>
                            <div class="input-group">
                                <div class="form-control" v-bind:id="'acSt-' + index"></div>
                            <!-- <wj-auto-complete class="form-control" v-bind:id="'AutoStaff-' + index"
                                search-member-path="staff_name"
                                display-member-path="staff_name"
                                selected-value-path="id"
                                :selected-value="cusStaffList[index].staff_id"
                                :isReadOnly="(rmUndefinedBlank(cusStaffList[index].staff_id) != '' && inputData.updateLock)"
                                :initialized="initStaffs"
                                :is-required="false"
                                :max-items="stafflist.length"
                                :items-source="stafflist">
                            </wj-auto-complete> -->
                            </div>
                            <!-- :selectedIndexChanged="searchCustomer(index)" -->
                        </div>
                    </div>
                    <button type="button" class="btn btn-search btn-sm"
                        v-bind:disabled="(rmUndefinedBlank(cusStaffList[index].staff_id) == ''
                        || rmUndefinedBlank(cusStaffList[index].department_id) == ''
                        || inputData.updateLock
                        || cusStaffList[index].updatedFlg)"
                        @click="searchCustomer(index)"
                    >取得
                    </button>

                    <div class="panel panel-primary" v-show="(data.targetSales != null && data.staff_id != null && data.department_id != null)">
                        <div class="panel-heading row">
                            <div class="col-xs-6">
                                <div class="pull-left">
                                    <div class="col-xs-12"><p>担当得意先月平均売上総計</p></div>
                                    <div class="col-xs-12 text-right"><p>{{ data.avgSales | comma_format }}</p></div>

                                    <div class="col-xs-12"><p>先月売上：{{ data.lastMonthSales | comma_format }}</p></div>                                    
                                </div>
                            </div>
                            <div class="col-xs-6">
                                <div class="pull-right">
                                    <div class="col-xs-12 text-right"><p>担当件数</p></div>
                                    <div class="col-xs-12 text-right"><p>{{ data.count }}件</p></div>

                                    <div class="col-xs-12 text-left"><p>総粗利率：{{ data.gross_profit_rate | comma_format }}%</p></div>
                                </div>
                            </div>
                            <div class="col-xs-12">
                                <div class="col-xs-8"><h4>売上目標：{{ data.targetSales | comma_format }}</h4></div>
                                <div class="col-xs-4 text-right"><button class="btn btn-edit" @click="moveCustomer(index)" v-bind:disabled="inputData.updateLock">追加</button></div>
                            </div>
                        </div>
                        <div class="panel-body row">
                            <div v-for="(c, i) in data.cus" v-bind:key="i">
                                <div class="col-xs-12 customer-area" v-bind:class="{ 'from-customer': c.fromFlg, 'to-customer': c.toFlg, 'updated-customer': c.updatedFlg }">
                                    <div class="col-xs-8"><p>{{ c.customer_name }}</p></div>
                                    <div class="col-xs-4 chk-area" v-show="(c.fromFlg == false && c.toFlg == false  && c.updatedFlg == false)"><el-checkbox v-model="c.isCheck" v-bind:disabled="(c.toFlg || c.fromFlg || inputData.updateLock)"></el-checkbox></div>
                                    <div class="col-xs-4 chk-area" v-show="(c.toFlg == true && !inputData.updateLock)"><button type="button" class="btn btn-sm btn-delete" @click="cancelMove(index, i)" v-bind:disabled="inputData.updateLock">取消</button></div>
                                    <div class="col-xs-4 chk-area" v-show="c.updatedFlg == true"><label>{{ inputData.approval_at }}変更</label></div>

                                    <div class="col-xs-6">
                                        <div class="pull-left">
                                            <div class="text-left"><p>{{ c.address }}</p></div>

                                            <div class="text-left"><p>先月売上：{{ c.sales.subMonthSales | comma_format }}</p></div>                                    
                                        </div>
                                    </div>
                                    <div class="col-xs-6">
                                        <div class="pull-right">
                                            <div class="text-right"><p>総粗利率：{{ c.sales.total_gross_profit_rate | comma_format }}%</p></div>
                                            <div class="text-right"><p>月平均：{{ c.sales.avgSales | comma_format }}</p></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="clearfix"></div>
                </div>    
            </div>
        </div>
    </div>
</template>

<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';

export default {
    data: () => ({
        loading: false,
        isLocked: false,
        isShowEditBtn: true,
        isReadOnly: true,
        isEdit: false,

        FLG_ON : 1,
        FLG_OFF: 0,

        queryParam: '',
        urlparam: '',

        searchParams: {
            department_name: '',
            staff_name: '',
            customer_name: '',
            apply_date: null,
            to_apply_date: null,
        },
        wjSearchObj: {
            department_name: {},
            staff_name: {},
            customer_name: {},
            apply_date: {},
            to_apply_date: {},
        },
        wjInputObj: {
            apply_date: {},
        },

        inputData: {
            id: '',
            apply_staff_comment: '',
            approval_comment: '',
            apply_date: null,
            isEditable: false,
            updateLock: false,
            approval_status: {
                text: '',
                class: '',
            }
        },
        // データ
        cusStaffList: [],
        cusStaffAutoComp: [],
        deleteDetailId: [],

        errors: {
            apply_date: '',
        },
    }),
    props: {
        customerlist: Array,
        stafflist: Array,
        departmentlist: Array,
        staffdeartlist: Array,
    },
    created: function() {
        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.apply_date == "") { this.searchParams.apply_date = null };
        }
    },
    mounted: function() {
        if (this.queryParam.length > 1) {
            this.search();
        }
    },
    methods: {
        // 移動取消
        cancelMove(listIndex, cIndex) {
            var fromStaffId = this.cusStaffList[listIndex].cus[cIndex].from_staff_id
            var customerId = this.cusStaffList[listIndex].cus[cIndex].id

            // 移動元担当者特定
            this.cusStaffList.forEach(element => {
                if (element.staff_id == fromStaffId) {
                    element.cus.forEach(cRow => {
                        if (cRow.id == customerId) {
                            cRow.toFlg = false;
                            cRow.fromFlg = false;
                            cRow.from_staff_id = '';
                            cRow.to_staff_id = '';
                            cRow.cancelFlg = true;
                            cRow.isEdit = true;
                        }
                    });
                }
            });
            if (this.rmUndefinedZero(this.cusStaffList[listIndex].cus[cIndex].detail_id) != 0) {
                this.deleteDetailId.push(this.cusStaffList[listIndex].cus[cIndex].detail_id);
            }
            this.cusStaffList[listIndex].cus.splice(cIndex, 1);

            this.calcStaffSales();

        },
        createAutoComplete(divIndex, itemsSrc) {
            var acControl = {};

            var targetId = '#acSt-'+divIndex;
            acControl.staff_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'staff_name',
                displayMemberPath: 'staff_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                maxItems: this.stafflist.length,
                itemsSource: this.stafflist,
            });
            // selectedValueで指定できなかったので、後からセット      
            acControl.staff_name.selectedValue = this.cusStaffList[divIndex].staff_id;
            acControl.staff_name.selectedIndexChanged.addHandler(function (sender) {
                var ro = ((acControl.staff_name.selectedItem != null && this.cusStaffList[divIndex].updatedFlg) || this.inputData.updateLock);
                acControl.staff_name.isReadOnly = ro;
                if (!ro) {
                    // 部門を選択したら担当者を絞り込む
                    var item = sender.selectedItem;
                    if(item !== null) {
                        this.cusStaffList[divIndex].staff_id = item.id;
                    } else{
                        this.cusStaffList[divIndex].staff_id = '';
                    }   
                }
            }.bind(this));
            acControl.staff_name.onSelectedIndexChanged();


            var targetId = '#acDep-'+divIndex;
            acControl.department_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'department_name',
                displayMemberPath: 'department_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                maxItems: this.departmentlist.length,
                itemsSource: this.departmentlist,
            });
            // selectedValueで指定できなかったので、後からセット
            acControl.department_name.selectedValue = this.cusStaffList[divIndex].department_id;
            acControl.department_name.selectedIndexChanged.addHandler(function (sender) {
                var ro = ((acControl.department_name.selectedItem != null && this.cusStaffList[divIndex].updatedFlg) || this.inputData.updateLock);
                acControl.department_name.isReadOnly = ro;
                if (!ro) {
                    // 部門を選択したら担当者を絞り込む
                    var item = sender.selectedItem;
                    var tmpArr = this.staffdeartlist;
                    var tmpStaff = this.stafflist;
                    if(item !== null) {
                        tmpArr = [];
                        for(var key in this.staffdeartlist) {
                            if (item.id == this.staffdeartlist[key].department_id) {
                                tmpArr.push(this.staffdeartlist[key]);
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
                        acControl.staff_name.itemsSource = tmpStaff;
                        acControl.staff_name.selectedIndex = -1;  
                        this.cusStaffList[divIndex].department_id = item.id;
                    } else{
                        acControl.staff_name.itemsSource = tmpStaff;
                        acControl.staff_name.selectedIndex = -1; 
                    }
                }
            }.bind(this));
            acControl.department_name.onSelectedIndexChanged();


            return acControl;
        },
        // 検索
        search() {
            this.loading = true
            // エラーの初期化
            this.initErr(this.errors);

            var params = new URLSearchParams();

            params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
            params.append('staff_name', this.rmUndefinedBlank(this.wjSearchObj.staff_name.selectedValue));
            params.append('customer_name', this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
            params.append('apply_date', this.rmUndefinedBlank(this.wjSearchObj.apply_date.text));

            axios.post('/customer-staff/search', params)

            .then( function (response) {
                this.inputData = {
                    id: '',
                    apply_staff_comment: '',
                    approval_comment: '',
                    apply_date: null,
                    isEditable: false,
                    updateLock: false,
                    approval_status: {
                        text: '',
                        class: '',
                    }
                };
                // データ
                this.cusStaffList = [];
                
                for(var i = 0 ; i < this.cusStaffAutoComp.length ; i ++){
                    this.cusStaffAutoComp[i].department_name.dispose();
                    this.cusStaffAutoComp[i].staff_name.dispose();
                }
                this.cusStaffAutoComp = [];
                this.deleteDetailId = [];
                this.inputData.apply_date = this.wjSearchObj.apply_date.text;

                // URLパラメータ作成
                this.urlparam = '?'
                this.urlparam += 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
                this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));
                this.urlparam += '&' + 'customer_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.text));
                this.urlparam += '&' + 'apply_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.apply_date.text));

                if (this.rmUndefinedBlank(response.data.headerData) != '') {
                    this.inputData = response.data.headerData;
                    this.cusStaffList = response.data.cusStaffData;   
                    this.cusStaffList.forEach((row, index) => {
                        this.cusStaffList[index].updatedFlg = true;

                        this.$nextTick(function () {                            
                            this.cusStaffAutoComp.push({
                                department_name: {},
                                staff_name: {},
                            })
                            this.cusStaffAutoComp[index] = this.createAutoComplete(index, row);
                        })
                    });
                    var len = this.cusStaffList.length;

                    if (!this.inputData.updateLock) {
                        this.cusStaffList.push({
                            staff_id: null,
                            department_id: null,
                            updatedFlg: false,
                            department_name: '',
                            staff_name: '',
                            from_staff_id: '',
                            to_staff_id: '',
                            avgSales: 0,
                            gross_profit_rate: 0,
                            lastMonthSales: 0,
                            cus:[]
                        });
                        this.$nextTick(function () {                            
                            this.cusStaffAutoComp.push({
                                department_name: {},
                                staff_name: {},
                            })
                            this.cusStaffAutoComp[len] = this.createAutoComplete(len, this.cusStaffList[len]);
                        })
                    }
                } else {
                    this.cusStaffList.push({
                        staff_id: '',
                        department_id: '',
                        updatedFlg: false,
                        department_name: '',
                        staff_name: '',
                        from_staff_id: '',
                        to_staff_id: '',
                        avgSales: 0,
                        gross_profit_rate: 0,
                        lastMonthSales: 0,
                        cus:[]
                    });
                    this.$nextTick(function () {
                        this.cusStaffAutoComp.push({
                            department_name: {},
                            staff_name: {},
                        })
                        this.cusStaffAutoComp[0] = this.createAutoComplete(0, this.cusStaffList[0]);
                    })
                }

                this.calcStaffSales();
                this.loading = false
            }.bind(this))

            .catch(function (error) {
                this.loading = false
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
                    // window.onbeforeunload = null;
                    // location.reload()
                }
                // location.reload()
            }.bind(this))
        },
        // 担当者の先月売上／粗利率／月平均計算
        calcStaffSales() {
            var list = this.cusStaffList;

            list.forEach((row, index) => {
                var purchase = 0;
                var totalSales = 0;
                var avgSales = 0;
                var lastMonthSales = 0;
                var gross_profit_rate = 0;
                var cnt = 0;
                row.cus.forEach(c => {                    
                    if (c.fromFlg == this.FLG_OFF && c.updatedFlg == this.FLG_OFF) {
                        lastMonthSales = this.bigNumberPlus(lastMonthSales, c.sales.subMonthSales);
                        avgSales = this.bigNumberPlus(avgSales, c.sales.avgSales);
                        purchase = this.bigNumberPlus(purchase, c.sales.purchase_volume);
                        totalSales = this.bigNumberPlus(totalSales, c.sales.total_sales);
                        cnt++;
                    }
                });

                if ((totalSales - purchase) > 0) {
                    gross_profit_rate = Math.round(this.bigNumberTimes(this.bigNumberDiv(this.bigNumberPlus(totalSales - purchase), totalSales), 100));
                }

                row.lastMonthSales = lastMonthSales;
                row.avgSales = avgSales;
                row.gross_profit_rate = gross_profit_rate;
                row.count = cnt;
            });


        },
        // 担当者を選んだら担当得意先検索
        searchCustomer(index) {
            if (!this.loading) {
                if (this.rmUndefinedBlank(this.cusStaffAutoComp[index].department_name.selectedValue) != ''
                    && this.rmUndefinedBlank(this.cusStaffAutoComp[index].staff_name.selectedValue) != '') 
                {     
                    this.loading = true;
                    
                    this.initErr(this.errors);  
                    // パラメータセット
                    var params = new URLSearchParams();
                    params.append('staff_id', this.rmUndefinedBlank(this.cusStaffAutoComp[index].staff_name.selectedValue));
                    params.append('department_id', this.rmUndefinedBlank(this.cusStaffAutoComp[index].department_name.selectedValue));

                    axios.post('/customer-staff/searchCustomer', params)
                    .then( function (response) {
                        if (response.data) {
                            // 成功
                            var list = response.data;
                            list.cus.forEach(rec => {
                                rec.fromFlg = false;
                                rec.toFlg = false;
                                rec.cancelFlg = false;
                                rec.isEdit = false;
                                rec.updatedFlg = false;
                            });


                            this.cusStaffList[index].staff_id = this.cusStaffAutoComp[index].staff_name.selectedValue;
                            this.cusStaffList[index].from_staff_id = this.cusStaffAutoComp[index].staff_name.selectedValue;
                            this.cusStaffList[index].department_id = this.cusStaffAutoComp[index].department_name.selectedValue;
                            this.cusStaffList[index].targetSales = list.targetSales;
                            this.cusStaffList[index].cus = list.cus;
                            this.cusStaffList[index].count = list.cus.length;
                            this.cusStaffList[index].updatedFlg = true;

                            var newData = {
                                staff_id: null,
                                department_id: null,
                                updatedFlg: false,
                                department_name: '',
                                staff_name: '',
                                avgSales: 0,
                                lastMonthSales: 0,
                                gross_profit_rate: 0,
                                cus: [],
                            }

                            var autoComp = {
                                department_name: {},
                                staff_name: {},
                            }

                            this.cusStaffList.push(newData);
                            this.cusStaffAutoComp.push(autoComp);
                            this.cusStaffAutoComp[index].department_name.onSelectedIndexChanged();
                            this.cusStaffAutoComp[index].staff_name.onSelectedIndexChanged();
                            setTimeout(function() {
                                this.cusStaffAutoComp[index + 1] = this.createAutoComplete(index + 1, newData);
                            }.bind(this), 800);
                            
                            this.calcStaffSales();
                        } else {
                            // 失敗
                            // alert(MSG_ERROR)
                            // location.reload();
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
                            location.reload()
                        }
                    }.bind(this))
                }
            }
        },
        // オートコンプリート紐付け
        initDepartments(s) {
            var elementId = s.hostElement.id;
            var index = elementId.substr(8, 1)
            this.cusStaffAutoComp[index].department_name = s;
        },
        initStaffs(s) {
            var elementId = s.hostElement.id;
            var index = elementId.substr(10, 1)
            this.cusStaffAutoComp[index].staff_name = s;
        },
        // 得意先移動
        moveCustomer(index) {
            var cusArr = [];
            this.cusStaffList.forEach((rec, i) => {
                if (i != index) {
                    rec.cus.forEach(c => {
                        if (c.isCheck) {
                            var data = {};
                            data.id = c.id;
                            data.address = c.address;
                            data.charge_department_id = c.charge_department_id;
                            data.charge_staff_id = c.charge_staff_id;
                            data.from_staff_id = c.charge_staff_id;
                            data.customer_code = c.customer_code;
                            data.customer_kana = c.customer_kana;
                            data.customer_name = c.customer_name;
                            data.customer_short_name = c.customer_short_name;
                            data.sales = c.sales;
                            data.isCheck = false;
                            data.fromFlg = false;
                            data.updatedFlg = false;
                            data.toFlg = true;
                            data.isEdit = true;
                            data.cancelFlg = false;

                            cusArr.push(data);


                            c.fromFlg = true;
                            c.isCheck = false;
                            c.isEdit = true;
                        }
                    });
                }
            });
            var toStaffId = this.cusStaffList[index].staff_id;
            var toDepId = this.cusStaffList[index].department_id;
            for (var i = 0; i < cusArr.length; i++) {
                cusArr[i].to_staff_id = toStaffId;
                cusArr[i].to_department_id = toDepId;
                this.cusStaffList[index].cus.push(cusArr[i])
                this.cusStaffList[index].updatedFlg = true;
            }
            
            this.calcStaffSales();
        },
        // 部門選択で担当者絞り込み
        changeIdxSearchDepartment: function(sender){
            // 部門を選択したら担当者を絞り込む
            var tmpArr = this.staffdeartlist;
            var tmpStaff = this.stafflist;
            if (sender.selectedItem) {
                tmpArr = [];
                for(var key in this.staffdeartlist) {
                    if (sender.selectedItem.id == this.staffdeartlist[key].department_id) {
                        tmpArr.push(this.staffdeartlist[key]);
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
        changeIdxDepartment: function(sender){
            var elementId = sender.hostElement.id;
            var index = elementId.substr(8, 1)
            // 部門を選択したら担当者を絞り込む
            var tmpArr = this.staffdeartlist;
            var tmpStaff = this.stafflist;
            if (sender.selectedItem) {
                tmpArr = [];
                for(var key in this.staffdeartlist) {
                    if (sender.selectedItem.id == this.staffdeartlist[key].department_id) {
                        tmpArr.push(this.staffdeartlist[key]);
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
            this.cusStaffAutoComp[index].staff_name.itemsSource = tmpStaff;
            this.cusStaffAutoComp[index].staff_name.selectedIndex = -1;
        },
        // 検索条件のクリア
        clearSearch: function() {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = null;
            });
        },
        // 承認
        approval() {
            this.loading = true;
            this.initErr(this.errors);

            // パラメータセット
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('approval_comment', this.rmUndefinedBlank(this.inputData.approval_comment));

            axios.post('/customer-staff/approval', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var listUrl = '/customer-staff/' +  this.urlparam;
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
                    // window.onbeforeunload = null;
                    // location.reload();
                }
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
                    // window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
        },
        // 否認
        rejection() {
            this.loading = true;
            this.initErr(this.errors);

            // パラメータセット
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('approval_comment', this.rmUndefinedBlank(this.inputData.approval_comment));

            axios.post('/customer-staff/rejection', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var listUrl = '/customer-staff/' +  this.urlparam;
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
                    // window.onbeforeunload = null;
                    // location.reload();
                }
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
                    // window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
        },
        del() {
            this.loading = true;
            this.initErr(this.errors);

            // パラメータセット
            var params = new URLSearchParams();
            params.append('customer_staff_id', this.rmUndefinedBlank(this.inputData.id));

            axios.post('/customer-staff/delete', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var listUrl = '/customer-staff/' +  this.urlparam;
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
                    // window.onbeforeunload = null;
                    // location.reload();
                }
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
                    // window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
        },
        // 保存
        requestSave() {
            this.loading = true;
            this.initErr(this.errors);
        
            // パラメータセット
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('apply_staff_comment', this.rmUndefinedBlank(this.inputData.apply_staff_comment));
            params.append('apply_date', this.rmUndefinedBlank(this.wjInputObj.apply_date.text));
            params.append('deleteDetailId', JSON.stringify(this.deleteDetailId));

            var data = this.cusStaffList;
            var checkList = [];

            var getCircularReplacer = () => {
                return (key, value) => {
                    // 初回用
                    if( key==='' ){
                        checkList.push(value);
                        return value;
                    }
                    // Node,Elementの類はカット
                    if( value instanceof Node ){
                        return undefined;
                    }
                    // Object,Arrayなら循環参照チェック
                    if( typeof value==='object' && value!==null ){
                        return checkList.every(function(v,i,a){
                            return value!==v;
                        }) ? value: undefined;
                    }
                    return value; 
                };
            };
            params.append('list', JSON.stringify(data, getCircularReplacer()));

            axios.post('/customer-staff/save', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/customer-staff/' +  this.urlparam;
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
                    // window.onbeforeunload = null;
                    // location.reload();
                }
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
                    // window.onbeforeunload = null;
                    // location.reload()
                }
            }.bind(this))
        },
        /* 以下オートコンプリード設定 */
        initStaff(sender) {
            this.wjSearchObj.staff_name = sender
        },
        initCustomer(sender) {
            this.wjSearchObj.customer_name = sender
        },
        initDepartment(sender) {
            this.wjSearchObj.department_name = sender
        },
        initFromApplyDate(sender) {
            this.wjSearchObj.apply_date = sender
        },
        initApplyDate(sender) {
            this.wjInputObj.apply_date = sender
        },
    }
}
</script>

<style>
/*********************************
    枠サイズ等
**********************************/
/* 検索項目 */
.main-body {
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
.scroll-area {
    width: 100%;
    height: 500px;
    overflow-x: auto;
    white-space: nowrap;
}
.customer-area {
    width: 95%;
    padding: 5px;
    margin: 15px 10px;
    -webkit-box-shadow: 0 0 3px 3px rgba(0, 0, 0, .3);
    box-shadow: 0 0 3px 3px rgba(0, 0, 0, .3);
}
.scroll-item {
    font-size: 12px;
    vertical-align: top;    
    margin: 10px;
    width: 400px;
    display: inline-block;
}
.panel-heading.row {
    padding-left: 2px;
    padding-right: 2px;
    margin: 0;
}
.panel-body.row {
    padding: 0px;
    margin: 0;
}
.btn-save {
    width: 80px;
}
.btn-delete {
    margin-top: 7px;
}
.from-customer {
    background-color: #C8C7CC;
}
.to-customer {
    background-color: #FFCC00;
}
.updated-customer {
    background-color: #5AC8FA;
    text-align: left;
}
.chk-area {
    text-align: right;
}
.status {
    width: 100%;
    height: 30px;
    margin-top: 15px;
    padding-top: 5px;
    text-align: center;
}
.not-approval{
    background: #CB2E25;
    color: #fff;
}
.approvaled{
    background: #4CD964;
    color: #fff;
}
.rejection{
    background: #CB2E25;
    color: #fff;
}


</style>