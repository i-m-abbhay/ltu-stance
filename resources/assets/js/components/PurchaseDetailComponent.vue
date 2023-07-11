<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="col-md-12 col-sm-12 search-body">
                    <h4>基本情報</h4>
                    <div class="row">
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.supplier_id != '') }">
                            <label class="control-label">仕入先名<span style="color:red;">※</span></label>
                            <wj-auto-complete class="form-control"
                                search-member-path="supplier_name"
                                display-member-path="supplier_name" 
                                :items-source="supplierlist" 
                                selected-value-path="id"
                                :selected-value="searchParams.supplier_id"
                                :selectedIndexChanged="selectSupplier"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initSupplierName"
                                :max-items="supplierlist.length"
                            ></wj-auto-complete>
                            <p class="text-danger">{{ errors.supplier_id }}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">メーカー名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="supplier_name"
                                display-member-path="supplier_name" 
                                :items-source="makerlist" 
                                selected-value-path="id"
                                :selected-value="searchParams.maker_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initMakerName"
                                :max-items="makerlist.length"
                            ></wj-auto-complete>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">発注番号</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="order_no"
                                display-member-path="order_no" 
                                :items-source="orderlist" 
                                selected-value-path="order_no"
                                :selected-value="searchParams.order_no"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initOrderNo"
                                :max-items="orderlist.length"
                            ></wj-auto-complete>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">発注担当者名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="staff_name"
                                display-member-path="staff_name" 
                                :items-source="orderstafflist" 
                                selected-value-path="id"
                                :selected-value="searchParams.order_staff_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initOrderStaff"
                                :max-items="orderstafflist.length"
                            ></wj-auto-complete>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">明細確定担当者名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="staff_name"
                                display-member-path="staff_name" 
                                :items-source="confirmstafflist" 
                                selected-value-path="id"
                                :selected-value="searchParams.confirm_staff_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initClosingStaff"
                                :max-items="confirmstafflist.length"
                            ></wj-auto-complete>
                        </div>                    
                        <div class="col-md-2">
                            <label class="control-label">仕入先請求番号</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="vendors_request_no"
                                display-member-path="vendors_request_no"
                                selected-value-path="vendors_request_no"
                                :selected-value="searchParams.vendors_request_no"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initRequestNo"
                                :items-source="requestnolist"
                                :max-items="requestnolist.length"
                            ></wj-auto-complete>
                        </div>
                        <!-- <div class="col-md-3">
                            <label class="control-label">支払番号</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="staff_name"
                                display-member-path="staff_name" 
                                selected-value-path="staff_name"
                                :selected-value="searchParams.confirm_staff"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initPayment"
                            ></wj-auto-complete>                        
                                :items-source="staffList" 
                                :max-items="staffList.length"
                        </div> -->
                    </div>

                    <div class="row">
                        <div class="col-sm-4">
                            <label>入荷日</label>
                            <div class="input-group">
                                <wj-input-date class="form-control"
                                    :value="searchParams.from_arrival_date"
                                    :selected-value="searchParams.from_arrival_date"
                                    :initialized="initFromArrivalDate"
                                    :isRequired=false
                                ></wj-input-date>
                                <span class="input-group-addon lbl-addon-ex">～</span>
                                <wj-input-date class="form-control"
                                    :value="searchParams.to_arrival_date"
                                    :selected-value="searchParams.to_arrival_date"
                                    :initialized="initToArrivalDate"
                                    :isRequired=false
                                ></wj-input-date>
                            </div>
                        </div>
                        <div class="col-sm-2">
                            <label>締め日</label>
                            <div class="input-group">
                                <wj-input-date class="form-control"
                                    :value="searchParams.closing_day"
                                    :selected-value="searchParams.closing_day"
                                    :initialized="initCloseDate"
                                    :isRequired=false
                                ></wj-input-date>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">部門名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="department_name"
                                display-member-path="department_name" 
                                :items-source="departmentlist" 
                                selected-value-path="id"
                                :selected-value="searchParams.department_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initDepartment"
                                :max-items="departmentlist.length"
                            ></wj-auto-complete>
                        </div>   
                        <div class="col-md-2">
                            <label class="control-label">営業担当者</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="staff_name"
                                display-member-path="staff_name" 
                                :items-source="stafflist" 
                                selected-value-path="id"
                                :selected-value="searchParams.staff_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initStaffName"
                                :max-items="stafflist.length"
                            ></wj-auto-complete>
                        </div>   
                        <div class="col-md-2">
                            <label class="control-label">得意先名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="customer_name"
                                display-member-path="customer_name" 
                                :items-source="customerlist" 
                                selected-value-path="id"
                                :selected-value="searchParams.customer_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initCustomer"
                                :max-items="customerlist.length"
                            ></wj-auto-complete>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-2">
                            <label class="control-label">大分類</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="class_big_name"
                                display-member-path="class_big_name" 
                                :items-source="classbiglist" 
                                selected-value-path="id"
                                :selected-value="searchParams.class_big_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initClassBig"
                                :max-items="classbiglist.length"
                            ></wj-auto-complete>
                        </div>   
                        <div class="col-md-2">
                            <label class="control-label">工事区分</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="construction_name"
                                display-member-path="construction_name" 
                                :items-source="constlist" 
                                selected-value-path="id"
                                :selected-value="searchParams.construction_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initConstruction"
                                :max-items="constlist.length"
                            ></wj-auto-complete>
                        </div>  
                        <div class="col-md-2">
                            <label class="control-label">商品名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="product_id"
                                :initialized="initProduct"
                                :selected-value="searchParams.product_id"
                                :min-length=1
                                :is-required="false"
                                :items-source-function="filterProductItemsSouceFunction">
                            </wj-auto-complete>
                        </div>  
                        <div class="col-md-2">
                            <label class="control-label">案件名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="matter_name"
                                display-member-path="matter_name" 
                                :items-source="matterlist" 
                                selected-value-path="matter_no"
                                :selected-value="searchParams.matter_no"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initMatterName"
                                :max-items="matterlist.length"
                            ></wj-auto-complete>
                        </div>   
                        <div class="col-md-2">
                            <label class="control-label">支払番号</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="payment_no"
                                display-member-path="payment_no" 
                                :items-source="paymentlist" 
                                selected-value-path="payment_no"
                                :selected-value="searchParams.payment_no"
                                :isRequired=false
                                :initialized="initPayment"
                                :max-items="paymentlist.length"
                            ></wj-auto-complete>
                        </div>
                        <div class="col-md-2" style="padding-top: 20px;">
                            <div class="pull-right">
                                <button type="submit" class="btn btn-search btn-md">&emsp;検索&emsp;</button>
                                &emsp;
                                <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                            </div>
                        </div>
                    </div>


                </div>
             </form>
        </div>

        <!-- 検索結果グリッド -->
        <div class="container-fluid result-body" v-show="tableData > 0">            
            <div class="row">
                <h3 class="col-sm-12"><b>仕入詳細</b></h3>
                <div class="col-sm-6">
                    <div class="filter-body input-group">
                        <label class="col-md-12"><u>絞り込み機能</u></label>
                        <el-checkbox class="col-md-2" v-model="filterChk.fix_detail" true-label="1" false-label="0" @input="filter($event)">調整明細</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.payment_confirm" true-label="1" false-label="0" @input="filter($event)">支払確定済</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.purchase_confirm" true-label="1" false-label="0" @input="filter($event)">仕入確定済</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.unsettled" true-label="1" false-label="0" @input="filter($event)">仕入未確定</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.sales_unsettled" true-label="1" false-label="0" @input="filter($event)">売上未計上</el-checkbox>
                    </div>
                    <!-- <div class="input-group">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-search"></span>
                        </div>
                        <input @input="filter($event)" class="form-control">
                    </div> -->
                </div>
                <div class="col-sm-6">
                    <div class="pull-right">
                        <button type="button" class="btn btn-save btn-md" @click="requestNoSetting">仕入先請求番号設定</button>
                            &emsp;
                        <button type="button" class="btn btn-save btn-md" @click="save">仕入確定</button>                        
                            &emsp;
                        <button type="button" class="btn btn-delete btn-md" @click="cancel">確定解除</button>
                    </div>
                    <p class="col-sm-12">&emsp;</p>
                    <div class="col-sm-12">
                        <p class="pull-right">選択中の合計金額：　{{ sumChkPurchasePrice | comma_format }}円</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12">
                    <div id="wjHeaderGrid" class="wjPurchaseHeaderGrid"></div>
                </div>
            </div>
            <!-- <div class="row">
                <div class="col-md-12">
                    <div class="col-md-2">
                        <label class="col-md-12">仕入先名</label>
                        <label class="col-md-12">{{ main.supplier_name }}</label>
                    </div>
                    <div class="col-sm-2">
                        <label>締め日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="main.closing_day"
                                :selected-value="main.closing_day"
                                :initialized="initMainCloseDate"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="col-md-12">仕入期間</label>
                        <label class="col-md-5">{{ main.from_purchase_date }}</label>
                        <label class="col-md-1">～</label>
                        <label class="col-md-5">{{ main.to_purchase_date }}</label>
                    </div>
                    <div class="col-md-2">
                        <label>支払予定日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="main.payment_date"
                                :selected-value="main.payment_date"
                                :initialized="initMainPaymentDate"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="col-md-2">
                        <label class="col-md-12">商品仕入合計</label>
                        <label class="col-md-12">{{ main.sum_purchase_amount | comma_format }}</label>
                    </div>
                    <div class="col-md-2">
                        <label class="col-md-12">仕入外請求</label>
                        <label class="col-md-12">{{ main.sum_not_purchase | comma_format }}</label>
                    </div>
                    <div class="col-md-2">
                        <label class="col-md-12">商品外値引き</label>
                        <label class="col-md-12">{{ main.sum_discount | comma_format }}</label>
                    </div>
                    <div class="col-md-2">
                        <label class="col-md-12">リベートその他</label>
                        <label class="col-md-12">{{ main.sum_rebete | comma_format }}</label>
                    </div>
                    <div class="col-md-2">
                        <label class="col-md-12">請求合計</label>
                        <label class="col-md-12">{{ main.sum_cost_total | comma_format }}</label>
                    </div>
                </div>

            </div> -->
            <div class="row">
                <div class="col-md-12">
                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData }}件</p>
                </div>
                <div id="wjPurchaseGrid"></div>
                <!-- <wj-multi-row
                    :itemsSource="products"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row> -->
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="pull-left">
                        <button type="button" class="btn btn-save btn-md" @click="showAddRowDlg">明細追加</button>
                        <button type="button" class="btn btn-delete btn-md" @click="deleteRow">明細削除</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="center-block">
                        <button type="button" class="btn btn-save btn-md" v-show="false">変更明細出力</button>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="pull-right">
                        <button type="button" class="btn btn-save btn-md" @click="back">&emsp;戻る&emsp;</button>
                        <button type="button" class="btn btn-save btn-md" @click="backPayment">&emsp;支払予定一覧へ&emsp;</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- 明細追加ダイアログ -->
        <el-dialog title="仕入明細追加" :visible.sync="showDlgAddDetail" :closeOnClickModal=false>
            <div class="row">
                <div class="col-md-12" v-bind:class="{'has-error': (errors.customer_name != '') }">
                    <label class="control-label">得意先名</label>
                    <wj-auto-complete class="form-control"
                        search-member-path="customer_name"
                        display-member-path="customer_name" 
                        :selected-value="''"
                        :items-source="customerlist" 
                        selected-value-path="customer_name"
                        :selectedIndexChanged="selectCustomerName"
                        :isDisabled="isReadOnly"
                        :isRequired=false
                        :initialized="initDlgCustomer"
                        :max-items="customerlist.length"
                    ></wj-auto-complete>
                    <p class="text-danger">{{ errors.customer_name }}</p>
                </div>
                <div class="col-md-12" v-bind:class="{'has-error': (errors.matter_no != '') }">
                    <label class="control-label">案件名</label>
                    <wj-auto-complete class="form-control"
                        search-member-path="matter_name"
                        display-member-path="matter_name" 
                        :selected-value="''"
                        :items-source="matterlist" 
                        selected-value-path="matter_name"
                        :isDisabled="isReadOnly"
                        :isRequired=false
                        :initialized="initDlgMatter"
                        :max-items="matterlist.length"
                    ></wj-auto-complete>
                    <p class="text-danger">{{ errors.matter_no }}</p>
                </div>
                <div class="col-md-8" v-bind:class="{'has-error': (errors.cost_kbn != '') }">
                    <label class="control-label">仕入種別</label>
                    <wj-combo-box class="form-control"
                        search-member-path="value_text_1"
                        display-member-path="value_text_1" 
                        :items-source="purchasetypelist" 
                        :selected-value="''"
                        :selectedIndexChanged="dlgKbnChanged"
                        selected-value-path="value_code"
                        :isDisabled="isReadOnly"
                        :isRequired=false
                        :initialized="initDlgPurchaseType"
                        :max-items="purchasetypelist.length"
                    ></wj-combo-box>
                    <p class="text-danger">{{ errors.cost_kbn }}</p>
                </div>
                <div class="col-md-12" v-bind:class="{'has-error': (errors.product_name != '') }">
                    <label class="control-label">商品名</label>
                    <input type="text" class="form-control" v-model="wjDlgParams.product_name" v-bind:readonly="isReadOnly">
                    <p class="text-danger">{{ errors.product_name }}</p>
                </div>
                <div class="col-md-4" v-bind:class="{'has-error': (errors.quantity != '') }">
                    <label class="control-label">数量</label>
                    <input type="number" class="form-control" v-model="wjDlgParams.quantity" v-bind:readonly="isReadOnly" step="0.01">
                    <p class="text-danger">{{ errors.quantity }}</p>
                </div>
                <div class="col-md-4" v-bind:class="{'has-error': (errors.unit != '') }">
                    <label class="control-label">単位</label>
                    <input type="text" class="form-control" v-model="wjDlgParams.unit" v-bind:readonly="isReadOnly">
                    <p class="text-danger">{{ errors.unit }}</p>
                </div>
                <div class="col-md-4" v-bind:class="{'has-error': (errors.price != '') }">
                    <label class="control-label">単価</label>
                    <input type="number" class="form-control" v-model="wjDlgParams.price" v-bind:readonly="isReadOnly">
                    <p class="text-danger">{{ errors.price }}</p>
                </div>
                <div class="col-md-12" v-bind:class="{'has-error': (errors.discount_reason != '') }">
                    <label class="control-label">値引・調整の理由</label>
                    <input type="text" class="form-control" v-model="wjDlgParams.discount_reason" v-bind:readonly="isReadOnly">
                    <p class="text-danger">{{ errors.discount_reason }}</p>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <div class="col-md-6 pull-left">
                    <el-button @click="dlgParamClear" class="btn btn-clear btn-md">クリア</el-button>
                </div>
                <div class="col-md-6 pull-right">
                    <el-button @click="addDetail" class="btn btn-md btn-save">登録</el-button>
                    <el-button @click="showDlgAddDetail=false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>

        <!-- 明細追加ダイアログ -->
        <el-dialog title="仕入明細追加" :visible.sync="showDlgRequestNo" :closeOnClickModal=false width="600px">
            <div class="row">
                <div class="col-md-12" v-bind:class="{'has-error': (errors.vendors_request_no != '') }">
                    <div class="col-md-12">
                        <label class="control-label">仕入先請求番号</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="vendors_request_no"
                            display-member-path="vendors_request_no"
                            selected-value-path="vendors_request_no"
                            :selected-value="requestNoParams.vendors_request_no"
                            :isDisabled="isReadOnly"
                            :isRequired=false
                            :initialized="initDlgRequestNo"
                            :items-source="allrequestnolist"
                            :max-items="allrequestnolist.length"
                        ></wj-auto-complete>
                    </div>
                    <!-- <label class="control-label">仕入先請求番号</label>
                    <input type="text" maxlength="20" class="form-control" v-model="requestNoParams.vendors_request_no"> -->
                    <p class="text-danger">{{ errors.vendors_request_no }}</p>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <!-- <div class="col-md-6 pull-left">
                </div> -->
                <div class="col-md-8 pull-right">
                    <el-button @click="settingRequestNo" class="btn btn-md btn-save">登録</el-button>
                    <el-button @click="requestNoClear" class="btn btn-clear btn-md" style="margin-top: 0px;">クリア</el-button>
                    <el-button @click="showDlgRequestNo=false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>

    </div>
</template>


<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,

        ADD_ROW_STATUS: '未確定',
        ADD_ROW_PRODUCT_CODE: {
            DISCOUNT: 'NEBIKI',
            REBATE: 'REBATE',
            SPONSOR: 'KYOUSANKIN',
            WARRANTY: 'HOSHOUKIN',
            INCOME: 'ZATUSHUNYU',
            REQUEST: 'SHIREGAI',
        },

        FLG_LIST: {
            UNSETTLED: 0,   // 未確定
            FIXED: 1,       // 確定分
            PENDING: 2,     // 承認待
            APPROVED: 3,    // 承認済
            PAID: 4,        // 支払済
            CLOSING: 5,     // 締済
        },
        
        PURCHASETYPE: {
            DISCOUNT: 0,
            REBATE: 1,
            SPONSOR: 2,
            WARRANTY: 3,
            INCOME: 4,
            REQUEST: 5,
        },

        createdFlg: {
            arrival: 0,
            return: 1,
            confirm: 2,
        },

        showDlgAddDetail: false,
        showDlgRequestNo: false,

        FLG_ON: 1,
        FLG_OFF: 0,

        tableData: 0,
        sumChkPurchasePrice: 0,

        errors: {
            supplier_id: '',

            matter_no: '',
            customer_name: '',
            cost_kbn: '',
            product_name: '',
            quantity: '',
            unit: '',
            price: '',
            discount_reason: '',

            vendors_request_no: '',
        },

        searchParams: {
            supplier_id: '',
            maker_id: '',
            order_no: '',
            order_staff_id: '',
            confirm_staff_id: '',
            vendors_request_no: '',
            from_arrival_date: null,
            to_arrival_date: null,
            closing_day: null,
            department_id: '',
            staff_id: '',
            customer_id: '',
            matter_no: '',
            class_big_id: '',
            construction_id: '',
            product_id: null,
            payment_no: '',
        },
        wjSearchObj: {
            supplier_id: {},
            maker_id: {},
            order_no: {},
            order_staff_id: {},
            confirm_staff_id: {},
            vendors_request_no: {},
            from_arrival_date: {},
            to_arrival_date: {},
            closing_day: {},
            department_id: {},
            staff_id: {},
            customer_id: {},
            matter_no: {},
            class_big_id: {},
            construction_id: {},
            product_id: {},
            payment_no: {},
        },
        wjDlgParams: {
            customer_name: {},
            matter_no: {},
            cost_kbn: {},
            vendors_request_no: {},
            product_name: '',
            quantity: '0.00',
            unit: '',
            price: 0,
            discount_reason: '',
        },
        requestNoParams: {
            vendors_request_no: '',
        },

        filterChk: {
            payment_confirm: "0",
            purchase_confirm: "1", 
            unsettled: "1",
            fix_detail: "0",
            sales_unsettled: "0",
        },
        filterVal: {
            payment_confirm: 2,
            purchase_confirm: 1, 
            unsettled: 0,
            fix_detail: 3,
        },

        main: {
            id: '',
            payment_id: '',
            status: null,
            supplier_name: '',
            closing_day: null,
            from_purchase_date: '',
            to_purchase_date: '',
            payment_date: null,
            payment_sight: '',
            payment_day: '',
            sum_purchase_amount: '',
            sum_discount: '',
            sum_not_purchase: '',
            sum_rebete: '',
            sum_cost_total: '',
        },
        wjMainObj: {
            closing_day: {},
            payment_date: {},
        },

        layoutDefinition: null,
        layoutHeader: null,
        keepDOM: {},
        urlparam: '',

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [14],
        },
        gridPKCol: 14,

        wjPurchaseGrid: null,
        wjHeaderGrid: null,

        cgeClosingDate: null,
        cgePaymentDate: null,
        cgeStartDate: null,
        cgeFixedDate: null,
    }),
    props: {
        isEditable: Number,
        matterlist: Array,
        orderlist: Array,
        stafflist: Array,
        allrequestnolist: Array,
        requestnolist: Array,
        departmentlist: Array,
        staffdepartlist: Array,
        customerlist: Array,
        classbiglist: Array,
        constlist: Array,
        // productlist: Array,
        supplierlist: Array,
        makerlist: Array,
        orderstafflist: Array,
        confirmstafflist: Array,
        purchasetypelist: Array,
        pricelist: Array,
        paymentlist: Array,
        initparams: {},
    },
    created: function() {
        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.from_arrival_date == "") { this.searchParams.from_arrival_date = null };
            if (this.searchParams.to_arrival_date == "") { this.searchParams.to_arrival_date = null };
            if (this.searchParams.closing_day == "") { this.searchParams.closing_day = null };
        }
        this.layoutDefinition = this.getLayout();
        this.layoutHeader = this.getHeaderLayout();
    },
    mounted: function() {
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            this.setSearchParams(query, this.searchParams);
            // 検索
            this.search();
        }
        this.$nextTick(function() {
            this.wjPurchaseGrid = this.createGridCtrl('#wjPurchaseGrid', new wjCore.CollectionView([]));
            this.wjHeaderGrid = this.createHeaderGrid('#wjHeaderGrid', new wjCore.CollectionView([]));
        });

        this.wjSearchObj.supplier_id.onSelectedIndexChanged();
    },
    methods:{
        getHeaderLayout() {
            return [
                {
                    header: '仕入先名', cells: [
                        { binding: 'supplier_name', name: 'supplier_name', header: '仕入先名', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },                        
                    ]
                },
                {
                    header: '締め日', cells: [
                        { binding: 'closing_day', name: 'closing_day', header: '締め日', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: false },
                    ]
                },
                {
                    header: '仕入開始日', cells: [
                        { binding: 'from_purchase_date', name: 'from_purchase_date', header: '仕入開始日', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: false },
                    ]
                },
                {
                    header: '仕入終了日', cells: [
                        { binding: 'to_purchase_date', name: 'to_purchase_date', header: '仕入終了日', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },
                    ]
                },
                {
                    header: '支払予定日', cells: [
                        { binding: 'payment_date', name: 'payment_date', header: '支払予定日', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: false },
                    ]
                },
                {
                    header: '商品仕入合計', cells: [
                        { binding: 'sum_purchase_amount', name: 'sum_purchase_amount', header: '商品仕入合計', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },
                    ]
                },
                {
                    header: '仕入外請求', cells: [
                        { binding: 'sum_not_purchase', name: 'sum_not_purchase', header: '仕入外請求', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },
                    ]
                },
                {
                    header: '商品外値引き', cells: [
                        { binding: 'sum_discount', name: 'sum_discount', header: '商品外値引き', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },
                    ]
                },
                {
                    header: 'リベートその他', cells: [
                        { binding: 'sum_rebete', name: 'sum_rebete', header: 'リベートその他', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },
                    ]
                },
                {
                    header: '請求合計', cells: [
                        { binding: 'sum_cost_total', name: 'sum_cost_total', header: '請求合計', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },
                    ]
                },
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0, isReadOnly: true},
                    ]
                },
                {
                    header: 'supplier_id', cells: [
                        { binding: 'supplier_id', header: 'supplier_id', width: 0, isReadOnly: true},
                    ]
                },
                {
                    header: 'payment_sight', cells: [
                        { binding: 'payment_sight', header: 'payment_sight', width: 0, isReadOnly: true},
                    ]
                },
            ]
        },
        requestNoClear() {
            this.requestNoParams.vendors_request_no = ''
            this.wjDlgParams.vendors_request_no.text = ''
        },
        settingRequestNo() {
            var num = this.wjDlgParams.vendors_request_no.text;

            if (num.length > 20) {
                alert('20' + MSG_ERROR_LIMIT_OVER)
                return;
            }
            this.wjPurchaseGrid.itemsSource.forEach(row => {
                if (row.chk) {
                    row.vendors_request_no = num;
                }
            });

            this.wjPurchaseGrid.refresh();

            this.showDlgRequestNo = false;
        },
        requestNoSetting() {
            // 操作権限
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            var items = [];
            var okFlg = true;
            this.wjPurchaseGrid.itemsSource.forEach(row => {
                if (row.chk) {
                    items.push(row);
                    if (row.purchase_status_val != this.filterVal.unsettled) {
                        okFlg = false;
                    }
                }
            });
    
            if (!okFlg) {
                alert(MSG_ERROR_NOT_UNSETTLED)
                return;
            }

            if (items.length == 0) {
                alert(MSG_ERROR_NO_SELECT)
                return;
            }
            this.showDlgRequestNo = true;
        },
        filterProductItemsSouceFunction(text, maxItems, callback){
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合
            if(this.wjSearchObj.product_id.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH){
                return;
            }
            this.setASyncAutoCompleteList(this.wjSearchObj.product_id, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
        },
        calcHeaderPrice() {
            if (this.wjPurchaseGrid.itemsSource.length > 0) {
                var sumDiscount = 0;
                var sumPurchaseAmount = 0;
                var sumRebate = 0;
                var sumNotPurchase = 0;
                var sumCostTotal = 0;
                this.wjPurchaseGrid.itemsSource.forEach(element => {
                    if (element.payment_status_val == this.FLG_OFF && element.purchase_status_val == this.FLG_ON) {
                        if (element.createdBy == this.createdFlg.arrival 
                            || element.createdBy == this.createdFlg.return) 
                        {
                            // 商品仕入合計
                            sumPurchaseAmount = this.roundDecimalStandardPrice(this.bigNumberPlus(sumPurchaseAmount, element.fix_cost_total));
                        }
                        if (element.purchase_type == this.PURCHASETYPE.REQUEST) {
                            // 仕入外請求
                            sumNotPurchase = this.roundDecimalStandardPrice(this.bigNumberPlus(sumNotPurchase, element.fix_cost_total));
                        }
                        if (element.purchase_type == this.PURCHASETYPE.DISCOUNT) {
                            // 商品外値引き
                            sumDiscount = this.roundDecimalStandardPrice(this.bigNumberPlus(sumDiscount, element.fix_cost_total));
                        }
                        if (element.purchase_type == this.PURCHASETYPE.REBATE 
                        || element.purchase_type == this.PURCHASETYPE.SPONSOR 
                        || element.purchase_type == this.PURCHASETYPE.WARRANTY 
                        || element.purchase_type == this.PURCHASETYPE.INCOME) {
                            // リベートその他
                            sumRebate = this.roundDecimalStandardPrice(this.bigNumberPlus(sumRebate, element.fix_cost_total));
                        }
                    }   
                });

                sumCostTotal = sumPurchaseAmount + sumNotPurchase + sumDiscount + sumRebate;
                this.main.sum_purchase_amount = sumPurchaseAmount;
                this.main.sum_discount = sumDiscount;
                this.main.sum_not_purchase = sumNotPurchase;
                this.main.sum_rebete = sumRebate;
                this.main.sum_cost_total = sumCostTotal;

                var headerItem = [];
                headerItem.push(this.main);
                
                this.wjHeaderGrid.dispose();

                this.wjHeaderGrid = this.createHeaderGrid('#wjHeaderGrid', new wjCore.CollectionView(headerItem));
                this.wjHeaderGrid.refresh();
            }
        },
        dlgKbnChanged(sender) {
            var item = sender.selectedItem;

            if (item != undefined && item != null) {
                this.wjDlgParams.product_name = item.value_text_1;
            }
        },
        deleteRow() {
            // 操作権限
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            if (!confirm(MSG_CONFIRM_CANCEL_FIX_DETAIL)) {
                return;
            }
            this.loading = true;
            var params = new URLSearchParams();

            var items = [];
            var delArr = [];
            var OkFlg = true;
            var err = '';
            this.wjPurchaseGrid.itemsSource.forEach((row, i) => {
                if (row.chk == this.FLG_ON) {
                    if (row.payment_status_val != this.FLG_OFF) {
                        OkFlg = false;
                        err = MSG_ERROR_CANCEL_PURCHASE + '\n';
                    }
                    if (row.purchase_status_val != this.FLG_OFF) {
                        OkFlg = false;
                        err = MSG_ERROR_FIX_PURCHASE + '\n';
                    }
                    if (row.add_flg == this.FLG_OFF) {
                        OkFlg = false;
                        err = MSG_ERROR_DELETE_PURCHASE_ADD_ROW;
                    }
                    if (this.rmUndefinedZero(row.id) == 0) {
                        delArr.push(row);
                    } else {
                        items.push(row);
                    }
                }
            });

            if (items.length == 0 && delArr.length == 0) {
                alert(MSG_ERROR_NO_SELECT)
                return;
            }
            
            if (!OkFlg) {
                alert(err);
                this.loading = false;
            } else if (delArr.length > 0 && items.length == 0) {
                delArr.forEach(row => {
                    this.wjPurchaseGrid.collectionView.remove(row);
                });
                this.loading = false;
            } else {
                params.append('gridData', JSON.stringify(items));

                axios.post('/purchase-detail/delete', params)

                .then( function (response) {
                    if (response.data) {
                        var listUrl = '/purchase-detail' + this.urlparam;
                        location.href = listUrl

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
                        // window.onbeforeunload = null;
                        // location.reload()
                    }
                    // this.loading = false
                    // if (error.response.data.message) {
                    //     alert(error.response.data.message)
                    // } else {
                    //     alert(MSG_ERROR)
                    // }
                    // location.reload()
                }.bind(this))
            }
        },
        // 明細追加ダイアログ表示
        showAddRowDlg() {
            // 操作権限
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }
            
            this.showDlgAddDetail = true;
        },
        selectCustomerName: function(sender){
            // 得意先を変更したら案件名を絞り込む
            var tmpMatter = this.matterlist;
            if (sender.selectedItem) {
                tmpMatter = [];
                for(var key in this.matterlist) {
                    if (sender.selectedItem.id == this.matterlist[key].customer_id) {
                        tmpMatter.push(this.matterlist[key]);
                    }
                }             
            }
            this.wjDlgParams.matter_no.itemsSource = tmpMatter;
            this.wjDlgParams.matter_no.selectedIndex = -1;
        },
        // 明細追加処理
        addDetail() {
            var row = {};
            this.initErr(this.errors);
            var isValid = true;
            // 入力値チェック
            // if (this.wjDlgParams.matter_no.selectedItem == undefined) {
            //     // 案件
            //     this.errors.matter_no = MSG_ERROR_NO_SELECT;
            //     isValid = false;
            // }
            // if (this.wjDlgParams.customer_name.selectedItem == undefined) {
            //     // 得意先
            //     this.errors.customer_name = MSG_ERROR_NO_SELECT;
            //     isValid = false;
            // }
            if (this.wjDlgParams.cost_kbn.selectedItem == undefined) {
                // 仕入種別
                this.errors.cost_kbn = MSG_ERROR_NO_SELECT;
                isValid = false;
            }
            if (this.rmUndefinedBlank(this.wjDlgParams.product_name) == '') {
                this.errors.product_name = MSG_ERROR_NO_INPUT;
                isValid = false;
            }
            if (this.rmUndefinedZero(this.wjDlgParams.quantity) == 0) {
                this.errors.quantity = MSG_ERROR_NO_INPUT;
                isValid = false;
            }
            if (this.rmUndefinedBlank(this.wjDlgParams.unit) == '') {
                this.errors.unit = MSG_ERROR_NO_INPUT;
                isValid = false;
            }
            if (this.rmUndefinedZero(this.wjDlgParams.price) == 0) {
                this.errors.price = MSG_ERROR_NO_INPUT;
                isValid = false;
            }

            if (isValid) {
                // データ作成
                var cusName = '';
                var matName = '';

                row.id = '';
                row.chk = true;
                row.fixed_date = '';
                row.sales_date = '';
                row.purchase_status = this.ADD_ROW_STATUS;
                row.arrival_date = null;
                row.request_no = '';
                row.product_name = this.wjDlgParams.product_name;
                row.model = '';
                row.maker_id = null;
                row.maker_name = '';
                if (this.wjDlgParams.matter_no.selectedItem == undefined || this.wjDlgParams.matter_no.selectedItem == null) {
                    row.matter_id = 0;
                    row.matter_name = '';
                } else {
                    row.matter_id = this.wjDlgParams.matter_no.selectedItem.id;
                    row.matter_name = this.wjDlgParams.matter_no.selectedItem.matter_name;
                }
                if (this.wjDlgParams.customer_name.selectedItem == undefined || this.wjDlgParams.customer_name.selectedItem == null) {
                    row.customer_id = 0;
                    row.customer_name = '';
                    row.only_customer_name = '';
                } else {
                    row.customer_id = this.wjDlgParams.customer_name.selectedItem.id;
                    row.customer_name = this.wjDlgParams.customer_name.selectedItem.customer_name
                    if (this.wjDlgParams.matter_no.selectedItem != undefined || this.wjDlgParams.matter_no.selectedItem == null) {
                        row.customer_name += '／' + this.wjDlgParams.matter_no.selectedItem.owner_name;
                    }
                    row.only_customer_name = this.wjDlgParams.customer_name.selectedItem.customer_name;
                }
                var quantityNum = this.rmUndefinedZero(parseFloat(this.wjDlgParams.quantity)).toFixed(2);

                row.arrival_id = 0;
                row.return_id = 0;
                row.supplier_id = this.main.id;
                row.supplier_name = this.main.supplier_name;
                row.order_id = 0;
                row.order_no = '';
                row.order_detail_id = 0;
                row.quantity = quantityNum;
                row.unit = this.wjDlgParams.unit;
                row.regular_price = this.wjDlgParams.price;
                row.cost_unit_price = this.wjDlgParams.price;
                row.fix_cost_unit_price = this.wjDlgParams.price;
                row.cost_makeup_rate = 100.00;
                row.fix_cost_makeup_rate = 100.00;
                row.cost_total = this.bigNumberTimes(this.wjDlgParams.price, quantityNum);
                row.fix_cost_total = this.bigNumberTimes(this.wjDlgParams.price, quantityNum);
                row.discount_flg = this.FLG_OFF;
                row.rebate_flg = this.FLG_OFF;
                row.product_id = 0;
                row.min_quantity = 1.00;
                row.stock_quantity = 1.00;
                row.discount_reason = this.wjDlgParams.discount_reason;
                row.purchase_status_val = this.filterVal.unsettled;
                row.payment_status_val = 0;
                row.add_flg = this.FLG_ON;
                row.cost_kbn = 0;
                row.vendors_request_no = null;
                
                var selectedKbn = this.wjDlgParams.cost_kbn.selectedItem;
                // 仕入種別ごとに商品番号割り振り
                if (selectedKbn !== undefined) {
                    row.purchase_type = selectedKbn.value_code;
                    if (selectedKbn.value_code == this.PURCHASETYPE.DISCOUNT) {
                        row.discount_flg = this.FLG_ON;
                        row.product_code = this.ADD_ROW_PRODUCT_CODE.DISCOUNT;
                    }
                    if (selectedKbn.value_code == this.PURCHASETYPE.REBATE) {
                        row.rebate_flg = this.FLG_ON;
                        row.product_code = this.ADD_ROW_PRODUCT_CODE.REBATE;
                    }
                    if (selectedKbn.value_code == this.PURCHASETYPE.SPONSOR) {
                        row.product_code = this.ADD_ROW_PRODUCT_CODE.SPONSOR;
                    }
                    if (selectedKbn.value_code == this.PURCHASETYPE.WARRANTY) {
                        row.product_code = this.ADD_ROW_PRODUCT_CODE.WARRANTY;
                    }
                    if (selectedKbn.value_code == this.PURCHASETYPE.INCOME) {
                        row.product_code = this.ADD_ROW_PRODUCT_CODE.INCOME;
                    }
                    if (selectedKbn.value_code == this.PURCHASETYPE.REQUEST) {
                        row.product_code = this.ADD_ROW_PRODUCT_CODE.REQUEST;
                    }
                }
                // 行追加
                this.wjPurchaseGrid.collectionView.sourceCollection.push(row);

                this.wjPurchaseGrid.collectionView.refresh();

                this.filter();

                this.calcHeaderPrice();

                this.dlgParamClear();
                // ダイアログを閉じる
                this.showDlgAddDetail = false;
            }
        },
        save() {
            var items = [];
            var OkFlg = true;
            var paymentDateOver = false;
            var periodErr = false;
            var nowDate = moment().format('YYYY/MM/DD');
            var msg = '';

            this.wjHeaderGrid.itemsSource.items.forEach(header => {
                if (header.payment_date < nowDate) {
                    paymentDateOver = true;
                }

                if (header.from_purchase_date > header.to_purchase_date) {
                    periodErr = true;
                }
            }) 

            if (periodErr) {
                alert(MSG_ERROR_PURCHASE_PERIOD);
                return;
            }

            // 支払予定日が過去日
            if (paymentDateOver) {
                if (!confirm(MSG_ERROR_PAYMENT_DATE_OVER)) {
                    return;
                }
            }

            this.wjPurchaseGrid.itemsSource.forEach(row => {
                if (row.chk == this.FLG_ON) {
                    if (row.purchase_status_val != this.filterVal.unsettled) {
                        OkFlg = false;
                        msg = MSG_ERROR_FIX_PURCHASE;
                    }
                    items.push(row);
                }
            });
            if (msg.length > 0) {
                alert(msg);
                return;
            }
            if (items.length == 0) {
                OkFlg = false;
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            if (!OkFlg) {
                this.loading = false;
                return;
            }

            // 操作権限
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            if (!confirm(MSG_CONFIRM_FIX_PURCHASE)) {
                return;
            }

            this.loading = true;
            var params = new URLSearchParams();
            
            if (OkFlg) {
                params.append('supplier_id', this.rmUndefinedZero(this.main.id));
                params.append('payment_id', this.rmUndefinedZero(this.main.payment_id));
                params.append('status', this.rmUndefinedZero(this.main.status));
                params.append('supplier_name', this.rmUndefinedBlank(this.main.supplier_name));
                params.append('closing_day', this.rmUndefinedBlank(this.main.closing_day));
                params.append('payment_sight', this.rmUndefinedBlank(this.main.payment_sight));
                // params.append('payment_date', this.rmUndefinedBlank(this.main.payment_date));
                params.append('payment_date', this.rmUndefinedBlank(this.main.payment_date));
                params.append('payment_period_sday', this.rmUndefinedBlank(this.main.from_purchase_date));
                params.append('payment_period_eday', this.rmUndefinedBlank(this.main.to_purchase_date));
                params.append('gridData', JSON.stringify(items));

                axios.post('/purchase-detail/save', params)

                .then( function (response) {
                    if (response.data.status) {
                        if(this.rmUndefinedBlank(response.data.message) !== ''){
                            alert(response.data.message.replace(/\\n/g, '\n'));
                        }

                        var listUrl = '/purchase-detail' + this.urlparam;
                        location.href = listUrl
                    } else {
                        if (this.rmUndefinedBlank(response.data.message) != '') {
                            alert(response.data.message);
                        }
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
                        // window.onbeforeunload = null;
                        // location.reload()
                    }
                    // this.loading = false
                    // if (error.response.data.message) {
                    //     alert(error.response.data.message)
                    // } else {
                    //     alert(MSG_ERROR)
                    // }
                    // location.reload()
                }.bind(this))
            }
        },
        cancel() {
            // 操作権限
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            if (!confirm(MSG_CONFIRM_CANCEL_PURCHASE)) {
                return;
            }

            this.loading = true;
            var params = new URLSearchParams();

            var items = [];
            var OkFlg = true;
            var err = '';
            this.wjPurchaseGrid.itemsSource.forEach(row => {
                if (row.chk == this.FLG_ON) {
                    if (this.rmUndefinedZero(row.payment_status_val) != this.FLG_OFF) {
                        OkFlg = false;
                        err = MSG_ERROR_CANCEL_PURCHASE;
                    }
                    if (this.rmUndefinedBlank(row.confirmed_staff_id) == '') {
                        OkFlg = false;
                        err = MSG_ERROR_CANCEL_PURCHASE_NOT_FIXED;
                    }
                    if (row.purchase_status_val != this.FLG_ON) {
                        OkFlg = false;
                        err = MSG_ERROR_CANCEL_PURCHASE_NOT_FIXED;
                    }
                    items.push(row);
                }
            });

            if (items.length == 0) {
                OkFlg = false;
                alert(MSG_ERROR_NO_SELECT);
            }
            
            if (!OkFlg) {
                if (err.length > 0) {
                    alert(err);
                }
                this.loading = false;
            } else {
                params.append('gridData', JSON.stringify(items));

                axios.post('/purchase-detail/cancel', params)

                .then( function (response) {
                    if (response.data) {
                        var listUrl = '/purchase-detail' + this.urlparam;
                        location.href = listUrl

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
                        // window.onbeforeunload = null;
                        // location.reload()
                    }
                    // this.loading = false
                    // if (error.response.data.message) {
                    //     alert(error.response.data.message)
                    // } else {
                    //     alert(MSG_ERROR)
                    // }
                    // location.reload()
                }.bind(this))
            }
        },
        search() {
            this.loading = true;
            var params = new URLSearchParams();

            this.initErr(this.errors);

            params.append('supplier_id', this.rmUndefinedBlank(this.wjSearchObj.supplier_id.selectedValue));
            params.append('maker_id', this.rmUndefinedBlank(this.wjSearchObj.maker_id.selectedValue));
            params.append('order_no', this.rmUndefinedBlank(this.wjSearchObj.order_no.text));
            params.append('order_staff_id', this.rmUndefinedBlank(this.wjSearchObj.order_staff_id.selectedValue));
            params.append('confirm_staff_id', this.rmUndefinedBlank(this.wjSearchObj.confirm_staff_id.selectedValue));
            params.append('vendors_request_no', this.rmUndefinedBlank(this.wjSearchObj.vendors_request_no.text));
            params.append('payment_no', this.rmUndefinedBlank(this.wjSearchObj.payment_no.text));

            params.append('from_arrival_date', this.rmUndefinedBlank(this.wjSearchObj.from_arrival_date.text));
            params.append('to_arrival_date', this.rmUndefinedBlank(this.wjSearchObj.to_arrival_date.text));
            params.append('closing_day', this.rmUndefinedBlank(this.wjSearchObj.closing_day.text));
            params.append('department_id', this.rmUndefinedBlank(this.wjSearchObj.department_id.selectedValue));
            params.append('staff_id', this.rmUndefinedBlank(this.wjSearchObj.staff_id.selectedValue));
            params.append('customer_id', this.rmUndefinedBlank(this.wjSearchObj.customer_id.selectedValue));

            params.append('class_big_id', this.rmUndefinedBlank(this.wjSearchObj.class_big_id.selectedValue));
            params.append('construction_id', this.rmUndefinedBlank(this.wjSearchObj.construction_id.selectedValue));
            params.append('product_id', this.rmUndefinedBlank(this.wjSearchObj.product_id.selectedValue));
            params.append('matter_no', this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));

            axios.post('/purchase-detail/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'supplier_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_id.selectedValue));
                    this.urlparam += '&' + 'maker_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.maker_id.selectedValue));
                    this.urlparam += '&' + 'order_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_no.selectedValue));
                    this.urlparam += '&' + 'order_staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.order_staff_id.selectedValue));
                    this.urlparam += '&' + 'confirm_staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.confirm_staff_id.selectedValue));
                    this.urlparam += '&' + 'vendors_request_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.vendors_request_no.selectedValue));
                    this.urlparam += '&' + 'from_arrival_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_arrival_date.text));
                    this.urlparam += '&' + 'to_arrival_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_arrival_date.text));
                    this.urlparam += '&' + 'closing_day=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.closing_day.selectedValue));
                    this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_id.selectedValue));
                    this.urlparam += '&' + 'staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff_id.selectedValue));
                    this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_id.selectedValue));
                    this.urlparam += '&' + 'class_big_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.class_big_id.selectedValue));
                    this.urlparam += '&' + 'construction_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.construction_id.selectedValue));
                    this.urlparam += '&' + 'product_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product_id.selectedValue));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'payment_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.payment_no.selectedValue));

                    var itemsSource = [];
                    var rowIdx = 0;
                    var dataLength = 0;
                    this.sumChkPurchasePrice = 0;

                    var res = response.data;
                    // ヘッダー情報格納
                    this.main.id = res.id;
                    this.main.payment_id = res.payment_id;
                    this.main.status = res.status;
                    this.main.supplier_name = res.supplier_name;
                    this.main.closing_day = res.closing_day;
                    this.main.payment_date = res.payment_date;
                    this.main.from_purchase_date = res.purchase_start_date;
                    this.main.to_purchase_date = res.purchase_end_date;
                    this.main.payment_sight = res.payment_sight;
                    this.main.payment_day = res.payment_day;

                    res.gridData.forEach(element => {
                        // DOM生成
                        this.keepDOM[element.id] = {

                        }

                        itemsSource.push({
                            chk: false,
                            id: element.id,
                            payment_id: element.payment_id,
                            status: element.status,
                            sales_recording: element.sales_recording,
                            sales_recording_txt: element.sales_recording_txt,
                            sales_date: element.sales_date,
                            sales_unit_price: element.sales_unit_price,
                            sales_total: element.sales_total,
                            purchase_status: element.purchase_status,
                            purchase_status_val: element.purchase_status_val,
                            payment_status: element.payment_status,
                            payment_status_val: element.payment_status_val,
                            purchase_type: element.purchase_type,
                            arrival_date: element.arrival_date,
                            request_no: element.request_no,
                            vendors_request_no: element.vendors_request_no,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            model: element.model,
                            maker_id: element.maker_id,
                            maker_name: element.maker_name,
                            supplier_id: element.supplier_id,
                            supplier_name: element.supplier_name,
                            customer_name: element.customer_name,
                            only_customer_name: element.only_customer_name,
                            order_no: element.order_no,
                            quantity: element.quantity,
                            min_quantity: element.min_quantity,
                            stock_quantity: element.stock_quantity,
                            unit: element.unit,
                            regular_price: element.regular_price,
                            cost_kbn: element.cost_kbn,
                            cost_unit_price: element.cost_unit_price,
                            fix_cost_unit_price: element.fix_cost_unit_price,
                            cost_makeup_rate: element.cost_makeup_rate,
                            fix_cost_makeup_rate: element.fix_cost_makeup_rate,
                            cost_total: element.cost_total,
                            fix_cost_total: element.fix_cost_total,
                            order_id: element.order_id,
                            order_detail_id: element.order_detail_id,
                            arrival_id: element.arrival_id,
                            return_id: element.return_id,
                            customer_id: element.customer_id,
                            matter_id: element.matter_id,
                            matter_no: element.matter_no,
                            department_id: element.department_id,
                            staff_id: element.staff_id,
                            confirmed_staff_id: element.confirmed_staff_id,
                            fixed_date: element.fixed_date,
                            department_name: element.department_name,
                            discount_reason: element.discount_reason,
                            discount_flg: element.discount_flg,
                            rebate_flg: element.rebate_flg,
                            add_flg: element.add_flg,
                            createdBy: element.createdBy,
                        });
                        dataLength++
                    })
                    this.wjPurchaseGrid.dispose()
                    
                    this.wjPurchaseGrid = this.createGridCtrl('#wjPurchaseGrid', itemsSource);
                    // this.wjPurchaseGrid.itemsSource = itemsSource;
                    this.tableData = dataLength;

                    this.filter();

                    // var headerItem = [];
                    // headerItem.push(this.main);
                    // this.wjHeaderGrid.itemsSource = headerItem;
                    // this.wjHeaderGrid.dispose();
                    // this.wjHeaderGrid = this.createHeaderGrid('#wjHeaderGrid', new wjCore.CollectionView(headerItem));

                    // this.wjHeaderGrid.refresh();
                    // this.wjPurchaseGrid.refresh();
                    this.calcHeaderPrice();
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
                    // window.onbeforeunload = null;
                    // location.reload()
                }
                // this.loading = false
                // if (error.response.data.message) {
                //     alert(error.response.data.message)
                // } else {
                //     alert(MSG_ERROR)
                // }
                // location.reload()
            }.bind(this))
        },
        // ダイアログの入力をクリア
        dlgParamClear() {
            this.wjDlgParams.customer_name.selectedIndex = -1;
            this.wjDlgParams.matter_no.selectedIndex = -1;
            this.wjDlgParams.cost_kbn.selectedIndex = -1;
            this.wjDlgParams.product_name = '';
            this.wjDlgParams.quantity = '0.00';
            this.wjDlgParams.unit = '';
            this.wjDlgParams.price = 0;
            this.wjDlgParams.discount_reason = '';
        },
        // 選択した仕入先からメーカー取得
        selectSupplier(sender) {
            // this.loading = true;
            var params = new URLSearchParams();
            var item = sender.selectedItem;
            if (item != null) {
                params.append('supplier_id', this.rmUndefinedBlank(item.id));

                axios.post('/purchase-detail/selectSupplier', params)

                .then( function (response) {
                    if (response.data) {
                        this.wjSearchObj.construction_id.itemsSource = response.data.constList;
                        this.wjSearchObj.construction_id.selectedIndex = -1;

                        this.wjSearchObj.order_no.itemsSource = response.data.orderNoList;
                        this.wjSearchObj.order_no.selectedIndex = -1;

                        this.wjSearchObj.matter_no.itemsSource = response.data.matterList;
                        this.wjSearchObj.matter_no.selectedIndex = -1;

                        this.wjSearchObj.confirm_staff_id.itemsSource = response.data.confirmStaffList;
                        this.wjSearchObj.confirm_staff_id.selectedIndex = -1;

                        this.wjSearchObj.order_staff_id.itemsSource = response.data.orderStaffList;
                        this.wjSearchObj.order_staff_id.selectedIndex = -1;

                        this.wjSearchObj.vendors_request_no.itemsSource = response.data.requestNoList;
                        this.wjSearchObj.vendors_request_no.selectedIndex = -1;

                        this.wjDlgParams.vendors_request_no.itemsSource = response.data.requestNoList;
                        this.wjDlgParams.vendors_request_no.selectedIndex = -1;
                        this.allrequestnolist = response.data.requestNoList;

                        this.wjSearchObj.department_id.itemsSource = response.data.departmentList;
                        this.wjSearchObj.department_id.selectedIndex = -1;

                        this.wjSearchObj.staff_id.itemsSource = response.data.staffList;
                        this.wjSearchObj.staff_id.selectedIndex = -1;

                        // this.customerlist = response.data.customerList;
                        this.wjSearchObj.customer_id.itemsSource = response.data.customerList;
                        this.wjSearchObj.customer_id.selectedIndex = -1;

                        this.wjSearchObj.payment_no.itemsSource = response.data.paymentList;
                        this.wjSearchObj.payment_no.selectedIndex = -1;

                        this.wjSearchObj.class_big_id.itemsSource = response.data.classBigList;
                        this.wjSearchObj.class_big_id.selectedIndex = -1;

                        this.wjSearchObj.maker_id.itemsSource = response.data.makerList;
                        this.wjSearchObj.maker_id.selectedIndex = -1;

                        this.wjSearchObj.product_id.itemsSource = response.data.productList;
                        this.wjSearchObj.product_id.selectedIndex = -1;

                    }
                    // this.loading = false
                }.bind(this))
                .catch(function (error) {
                    // this.loading = false
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    // location.reload()
                }.bind(this))
            }
        },
        // 戻る
        back() {
            var listUrl = '/';
            location.href = listUrl;
        },
        backPayment() {
            var closingMonth = moment(this.wjHeaderGrid.itemsSource.items[0].closing_day, 'YYYY/MM/DD').format('YYYY/MM');
            var listUrl = '/payment-list' + window.location.search;

            if (closingMonth !== null && listUrl.indexOf('payment_date') == -1) {
                if (this.rmUndefinedBlank(window.location.search) == '') {
                    listUrl += '?';
                }
                listUrl += '&payment_date=' + closingMonth;
            }
            // var listUrl = '/payment-list' + window.location.search;
            location.href = listUrl;
        },
        calcGridChangeUnitPrice(row){
            row.fix_cost_unit_price = this.roundDecimalStandardPrice(row.fix_cost_unit_price);
            // 仕入掛率 =  仕入単価 / 定価 * 100
            row.fix_cost_makeup_rate = this.roundDecimalRate(this.bigNumberTimes(this.bigNumberDiv(row.fix_cost_unit_price, row.regular_price, true), 100, true));
        },
        calcGridChangeMakeupRate(row){
            // 仕入単価 = 定価 * (仕入掛率 / 100)
            row.fix_cost_makeup_rate = this.roundDecimalRate(row.fix_cost_makeup_rate);
            row.fix_cost_unit_price = this.roundDecimalStandardPrice(this.bigNumberTimes(row.regular_price, this.bigNumberDiv(row.fix_cost_makeup_rate, 100, true), true));

        },
        calcGridFixCostTotal(row){
            row.fix_cost_total = this.bigNumberTimes(row.fix_cost_unit_price, row.quantity);
        },
        createHeaderGrid(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.layoutHeader,
                showSort: false,
                allowSorting: false,
            });

            gridCtrl.rows.defaultSize = 35;

            // gridCtrl.isReadOnly = readOnlyFlg;
            // 行ヘッダ非表示
            gridCtrl.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 列設定
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.index != undefined && this.gridSetting.invisible_col.indexOf(element.index) >= 0) {
                    element.visible = false;
                }
            });

            // セル内カスタマイズ
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                var col = panel.columns[c];
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    // 列ヘッダのセンタリング
                    cell.style.textAlign = 'center';
                }

                if (panel.cellType == wjGrid.CellType.Cell) {
                    // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);                    
                    // スタイルリセット
                    cell.style.color = '';
                    cell.style.backgroundColor = '';
                    cell.style.textAlign = '';

                    // 横スクロールで文字位置がおかしくなるのでtextArignは明示的に設定
                    var colName = this.wjPurchaseGrid.getBindingColumn(panel, r, c).name;

                    var dataItem = panel.rows[r].dataItem;
                    if(dataItem !== undefined) {
                    }

                    switch (colName) {
                        case 'supplier_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'closing_day':
                            cell.style.textAlign = 'left';
                            break;
                        case 'from_purchase_date':
                            cell.style.textAlign = 'left';
                            break;
                        case 'to_purchase_date':
                            cell.style.textAlign = 'left';
                            break;
                        case 'payment_date':
                            cell.style.textAlign = 'left';
                            break;
                        case 'payment_sight':
                            cell.style.textAlign = 'left';
                            break;
                        case 'sum_purchase_amount':
                            cell.style.textAlign = 'right';
                            break;
                        case 'sum_discount':
                            cell.style.textAlign = 'right';
                            break;
                        case 'sum_not_purchase':
                            cell.style.textAlign = 'right';
                            break;
                        case 'sum_rebete':
                            cell.style.textAlign = 'right';
                            break;
                        case 'sum_cost_total':
                            cell.style.textAlign = 'right';
                            break;
                    };
                }
            }.bind(this);
            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var col = this.wjPurchaseGrid.getBindingColumn(e.panel, e.row, e.col);       // 取れるのは上段の列名
                // 編集行データ取得
                var row = s.collectionView.currentItem;

                switch (col.name) {
                    case 'closing_day':
                        this.main.closing_day = row.closing_day;
                        this.main.to_purchase_date = row.closing_day;
                        var dayStr = row.closing_day.substr(0, 8) + this.main.payment_day;
                        var cday = moment(new Date(dayStr)).add(1, 'months').format('YYYY/MM/DD');

                        var sDay = row.closing_day.substr(0, 8) + '01';
                        this.main.from_purchase_date = sDay;                        
                        this.main.payment_date = cday;
                        break;
                    case 'payment_date':
                        this.main.payment_date = row.payment_date;
                        break;
                }
                gridCtrl.endUpdate();
            }.bind(this));

            var cge_closing_date = new CustomGridEditor(gridCtrl, 'closing_day', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 1, 1, 1);
            this.cgeClosingDate = cge_closing_date;

            var cge_payment_date = new CustomGridEditor(gridCtrl, 'payment_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 1, 1, 1);
            this.cgePaymentDate = cge_payment_date;

            var cge_start_date = new CustomGridEditor(gridCtrl, 'from_purchase_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 1, 1, 1);
            this.cgeStartDate = cge_start_date;

            return gridCtrl;
        },
        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowSorting: false,
            });


            var _this = this;
            // gridCtrl.isReadOnly = readOnlyFlg;
            // 行ヘッダ非表示
            gridCtrl.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 列設定
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.index != undefined && this.gridSetting.invisible_col.indexOf(element.index) >= 0) {
                    element.visible = false;
                }
            });

            // セル内カスタマイズ
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                var col = panel.columns[c];
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    // 列ヘッダのセンタリング
                    cell.style.textAlign = 'center';

                    // 全チェック用のチェックボックス生成
                    if (col.name == 'chk') {
                        var checkedCount = 0;
                        for (var i = 0; i < gridCtrl.rows.length; i++) {
                            if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                        }

                        // ヘッダ部にチェックボックス追加
                        var checkBox = '<input type="checkbox">';
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                        // 明細行にチェック状態を反映   TODO:新規行にチェックを付けたくない
                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                gridCtrl.setCellData(i, c, checkBox.checked);
                            }

                            _this.calcSumGridFixCostTotal()

                            gridCtrl.endUpdate();
                        });
                    }
                }

                if (panel.cellType == wjGrid.CellType.Cell) {
                    // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);                    
                    // スタイルリセット
                    cell.style.color = '';
                    cell.style.backgroundColor = '';
                    cell.style.textAlign = '';

                    // 横スクロールで文字位置がおかしくなるのでtextArignは明示的に設定
                    var colName = this.wjPurchaseGrid.getBindingColumn(panel, r, c).name;

                    var dataItem = panel.rows[r].dataItem;
                    if(dataItem !== undefined) {
                        // 値引き行の色を変更
                        if (dataItem.discount_flg == this.FLG_ON) {
                            cell.style.backgroundColor = '#F9D6D6';
                        }
                        // リベート行の色変更
                        if (dataItem.rebate_flg == this.FLG_ON) {
                            cell.style.backgroundColor = '#D6EBF9';
                        }
                    }

                    switch (colName) {
                        case 'chk':                     // チェック
                            cell.style.textAlign = 'center';
                            break;  
                        case 'sales_date':         
                            cell.style.textAlign = 'center';
                            break;                                 
                        case 'fixed_date':            
                            cell.style.textAlign = 'center';
                            break;                  
                        case 'arrival_date':
                            cell.style.textAlign = 'center';
                            break;   
                        case 'vendors_request_no':
                            cell.style.textAlign = 'center';
                            break;      
                        case 'product_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'product_code':
                            cell.style.textAlign = 'left';
                            break;
                        case 'model':
                            cell.style.textAlign = 'left';
                            break;
                        case 'maker_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'unit':            
                            cell.style.textAlign = 'left';
                            break;
                        case 'order_no':
                            cell.style.textAlign = 'left';
                            break;
                        case 'quantity':
                            cell.style.textAlign = 'right';
                            break;
                        case 'regular_price':
                            cell.style.textAlign = 'right';
                            break;
                        case 'cost_kbn':
                            cell.style.textAlign = 'left';
                            break;
                        case 'cost_unit_price':
                            cell.style.textAlign = 'right';
                            break;
                        case 'fix_cost_unit_price':
                            cell.style.textAlign = 'right';
                            break;
                        case 'cost_makeup_rate':      
                            cell.style.textAlign = 'right';
                            break;                      
                        case 'fix_cost_makeup_rate':
                            cell.style.textAlign = 'right';
                            break;
                        case 'cost_total':
                            cell.style.textAlign = 'right';
                            break;
                        case 'fix_cost_total':
                            cell.style.textAlign = 'right';
                            break;
                        case 'sales_unit_price':             
                            cell.style.textAlign = 'right';
                            if (this.rmUndefinedZero(dataItem.sales_recording) == this.FLG_OFF) {
                                cell.style.backgroundColor = '#F06060'
                            }
                            break;             
                        case 'sales_total':           
                            cell.style.textAlign = 'right';
                            if (this.rmUndefinedZero(dataItem.sales_recording) == this.FLG_OFF) {
                                cell.style.backgroundColor = '#F06060'
                            }
                            break;  
                    };
                }
            }.bind(this);


            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;

                switch(col.name){
                    case 'fixed_date':
                        if (row.purchase_status_val == this.FLG_OFF && row.payment_status_val == this.FLG_OFF) {
                            this.cgeFixedDate.control.isDisabled = false;
                        } else {
                            this.cgeFixedDate.control.isDisabled = true;
                            e.cancel = true;
                        }
                        break;
                }
            }.bind(this));

            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var col = this.wjPurchaseGrid.getBindingColumn(e.panel, e.row, e.col);       // 取れるのは上段の列名
                // 編集行データ取得
                var row = s.collectionView.currentItem;

                switch (col.name) {
                    case 'chk':
                        this.calcSumGridFixCostTotal()
                        break;
                    case 'fixed_date':
                        
                        if (row.purchase_status_val == this.FLG_OFF && row.payment_status_val == this.FLG_OFF) {
                            var tmpDate = this.cgeFixedDate.control.value
                            if (tmpDate != null) {
                                row.fixed_date = moment(new Date(tmpDate)).format('YYYY/MM/DD')
                            }
                        } else {
                            console.log(this.cgeFixedDate.control)
                            row.fixed_date = this.cgeFixedDate.control._oldText
                        }
                        break;
                    case 'fix_cost_total':
                        // this.calcHeaderPrice();
                        // if (row.add_flg == this.FLG_ON) {
                        //     row.fix_cost_unit_price = row.fix_cost_total;
                        //     row.regular_price = row.fix_cost_total;
                        // }
                        this.calcSumGridFixCostTotal()
                        break;
                    case 'fix_cost_unit_price':
                        this.calcGridChangeUnitPrice(row)
                        this.calcGridFixCostTotal(row);
                        this.calcHeaderPrice();
                        this.calcSumGridFixCostTotal()
                        // if (row.add_flg == this.FLG_ON) {
                        //     row.fix_cost_total = row.fix_cost_unit_price;
                        //     row.regular_price = row.fix_cost_unit_price;
                        // }
                        break;
                    case 'fix_cost_makeup_rate':
                        this.calcGridChangeMakeupRate(row)
                        this.calcGridFixCostTotal(row);
                        this.calcHeaderPrice();
                        this.calcSumGridFixCostTotal()
                        // if (row.add_flg == this.FLG_ON) {
                        //     row.fix_cost_total = row.fix_cost_unit_price;
                        //     row.regular_price = row.fix_cost_unit_price;
                        // }
                        break;
                }
                gridCtrl.endUpdate();
            }.bind(this));

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                
                switch (col.name) {      
                    case 'arrival_date':  
                    case 'fixed_date':
                    case 'vendors_request_no':
                    case 'product_name':
                    case 'product_code':
                    case 'model':
                    case 'maker_name':
                    case 'unit':
                    case 'order_no':
                    case 'quantity':
                    case 'regular_price':
                    case 'cost_kbn':
                    case 'cost_unit_price':
                    case 'fix_cost_unit_price':
                    case 'cost_makeup_rate':      
                    case 'fix_cost_makeup_rate':
                    case 'cost_total':
                    case 'fix_cost_total':
                        if(row.purchase_status_val == this.FLG_ON){
                            e.cancel = true;
                        }
                        break;
                }
                gridCtrl.collectionView.commitEdit();
            }.bind(this));

            // 仕入確定日
            this.cgeFixedDate = new CustomGridEditor(gridCtrl, 'fixed_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 2, 1, 1);

            return gridCtrl;
        },
        filter() {
            this.wjPurchaseGrid.collectionView.filter = data => {
                var result = false;
                
                if (this.filterChk.unsettled == this.FLG_ON && data.purchase_status_val == this.filterVal.unsettled) {
                    // 未確定
                    result = true;
                }

                if (this.filterChk.purchase_confirm == this.FLG_ON && data.purchase_status_val == this.filterVal.purchase_confirm
                    && this.rmUndefinedZero(data.payment_status_val) == this.FLG_OFF) {
                    // 仕入確定
                    result = true;
                }

                if (this.filterChk.payment_confirm == this.FLG_ON && data.payment_status_val >= this.FLG_ON) {
                    // 支払確定
                    result = true;
                }

                if (this.filterChk.fix_detail == this.FLG_ON && data.add_flg == this.FLG_ON) {
                    // 調整明細
                    result = true;
                }

                if (this.filterChk.sales_unsettled == this.FLG_ON && this.rmUndefinedZero(data.sales_recording) == this.FLG_OFF) {
                    // 売上未計上
                    result = true;
                }

                if (this.filterChk.unsettled == this.FLG_OFF && this.filterChk.purchase_confirm == this.FLG_OFF && this.filterChk.payment_confirm == this.FLG_OFF && this.filterChk.fix_detail == this.FLG_OFF && this.filterChk.sales_unsettled == this.FLG_OFF) {
                    result = true;
                }

                return result;
            }
        },

        calcSumGridFixCostTotal() {
            this.sumChkPurchasePrice = 0;
            this.wjPurchaseGrid.itemsSource.forEach(item => {
                if (item.chk == true) {
                    this.sumChkPurchasePrice += this.bigNumberPlus(item.fix_cost_total);
                }
            });
        },
        // レイアウト取得
        getLayout() {
            var priceKbnMap = new wjGrid.DataMap(this.pricelist, 'value_code', 'value_text_1');
            return [
                {
                    header: 'chk', cells: [
                        { binding: 'chk', name: 'chk', header: 'chk', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: false },
                    ]
                },
                // {
                //     header: '売上計上', cells: [
                //         { binding: 'sales_recording_txt', name: 'sales_recording_txt', header: '売上計上', minWidth: GRID_COL_MIN_WIDTH, width: 100, isRequired: false, isReadOnly: true },
                //     ]
                // },
                {
                    header: '状況', cells: [
                        // { binding: 'purchase_status', name: 'purchase_status', header: '仕入確定', minWidth: GRID_COL_MIN_WIDTH, width: 170, isRequired: false, isReadOnly: true},
                        { binding: 'fixed_date', name: 'fixed_date', header: '仕入確定日', minWidth: GRID_COL_MIN_WIDTH, width: 170, isRequired: false, isReadOnly: false },
                        { binding: 'sales_date', name: 'sales_date', header: '売上計上', minWidth: GRID_COL_MIN_WIDTH, width: 100, isRequired: false, isReadOnly: true },
                    ]
                },
                {
                    header: '入荷日', cells: [
                        { binding: 'arrival_date', name: 'arrival_date', header: '入荷日', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },                        
                        { binding: 'vendors_request_no', name: 'vendors_request_no', header: '仕入先請求番号', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                    ]
                },
                {
                    header: '商品', cells: [
                        { binding: 'product_name', name: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true },
                        { binding: 'product_code', name: 'product_name', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true },
                    ]
                },
                {
                    header: '商品', cells: [
                        { binding: 'model', name: 'model', header: '型式・規格', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },
                        { binding: 'maker_name', name: 'maker_name', header: 'メーカー名', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },
                    ]
                },
                {
                    header: '得意先', cells: [
                        { binding: 'customer_name', name: 'customer_name', header: '得意先名／施主等名', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },
                        { binding: 'order_no', name: 'order_no', header: '発注番号', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },
                    ]
                },
                {
                    header: '数量', cells: [
                        { binding: 'quantity', name: 'quantity', header: '数量', minWidth: GRID_COL_MIN_WIDTH, width: 100, format: 'n2', isReadOnly: true },
                        { binding: 'unit', name: 'unit', header: '単位', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },
                    ]
                },
                {
                    header: '定価', cells: [
                        { binding: 'regular_price', name: 'regular_price', header: '定価', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'cost_kbn', name: 'cost_kbn', header: '仕入区分', dataMap: priceKbnMap, minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                    ]
                },
                {
                    header: '販売単価', cells: [
                        { binding: 'sales_unit_price', name: 'sales_unit_price', header: '販売単価', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },
                        { binding: 'sales_total', name: 'sales_total', header: '売上金額', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },
                    ]
                },
                {
                    header: '仕入単価', cells: [
                        { binding: 'cost_unit_price', name: 'cost_unit_price', header: '仕入単価', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },
                        { binding: 'fix_cost_unit_price', name: 'fix_cost_unit_price', header: '単価調整', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: false },
                    ]
                },
                {
                    header: '仕入掛率', cells: [
                        { binding: 'cost_makeup_rate', name: 'cost_makeup_rate', header: '仕入掛率', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true , format: 'f2'},
                        { binding: 'fix_cost_makeup_rate', name: 'fix_cost_makeup_rate', header: '掛率調整', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: false, format: 'f2' },
                    ]
                },
                {
                    header: '仕入金額', cells: [
                        { binding: 'cost_total', name: 'cost_total', header: '仕入金額', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'fix_cost_total', name: 'fix_cost_total', header: '調整後金額', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0},
                    ]
                },
            ]
        },
        clearSearch: function() {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = null;
            });
        },
        initSupplierName(sender) {
            this.wjSearchObj.supplier_id = sender;
        },
        initMakerName(sender) {
            this.wjSearchObj.maker_id = sender;
        },
        initOrderNo(sender) {
            this.wjSearchObj.order_no = sender;
        },
        initOrderStaff(sender) {
            this.wjSearchObj.order_staff_id = sender;
        },
        initClosingStaff(sender) {
            this.wjSearchObj.confirm_staff_id = sender;
        },
        initRequestNo(sender) {
            this.wjSearchObj.vendors_request_no = sender;
        },
        initDlgRequestNo(sender) {
            this.wjDlgParams.vendors_request_no = sender;
        },
        // initPayment(sender) {
        //     this.wjSearchObj. = sender;
        // },
        initFromArrivalDate(sender) {
            this.wjSearchObj.from_arrival_date = sender;
        },
        initToArrivalDate(sender) {
            this.wjSearchObj.to_arrival_date = sender;
        },
        initCloseDate(sender) {
            this.wjSearchObj.closing_day = sender;
        },
        initDepartment(sender) {
            this.wjSearchObj.department_id = sender;
        },
        initStaffName(sender) {
            this.wjSearchObj.staff_id = sender;
        },
        initCustomer(sender) {
            this.wjSearchObj.customer_id = sender;
        },
        initMatterName(sender) {
            this.wjSearchObj.matter_no = sender;
        },
        initClassBig(sender) {
            this.wjSearchObj.class_big_id = sender;
        },
        initConstruction(sender) {
            this.wjSearchObj.construction_id = sender;
        },
        initProduct(sender) {
            this.wjSearchObj.product_id = sender;
        },
        initPayment(sender) {
            this.wjSearchObj.payment_no = sender;
        },
        initMainCloseDate(sender) {
            this.wjMainObj.closing_day = sender;
        },
        initMainPaymentDate(sender) {
            this.wjMainObj.payment_date = sender;
        },
        initDlgCustomer(sender) {
            this.wjDlgParams.customer_name = sender;
        },
        initDlgMatter(sender) {
            this.wjDlgParams.matter_no = sender;
        },
        initDlgPurchaseType(sender) {
            this.wjDlgParams.cost_kbn = sender;
        },
    }
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
.filter-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.wjPurchaseHeaderGrid {
    margin-top: 10px;
    margin-bottom: 10px;
    height: 67px !important;
}
.wj-flexgrid {
    height: 500px;
}

.el-dialog__body {
    height: 500px;
    width: 80%;
}
.el-dialog__footer {
    height: 80px;
}

</style>


