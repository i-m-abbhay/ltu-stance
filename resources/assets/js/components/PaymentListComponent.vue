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
                            <label class="control-label">仕入先名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="supplier_name"
                                display-member-path="supplier_name" 
                                :items-source="supplierlist" 
                                selected-value-path="id"
                                :selected-value="searchParams.supplier_id"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initSupplier"
                                :max-items="supplierlist.length"
                            ></wj-auto-complete>
                            <p class="text-danger">{{ errors.supplier_id }}</p>
                        </div>
                        <div class="col-sm-4" v-bind:class="{'has-error': (errors.payment_date != '') }">
                            <label>支払月<span style="color:red;">※</span></label>
                            <div class="input-group">
                                <wj-input-date class="form-control"
                                    :value="searchParams.payment_date"
                                    :selected-value="searchParams.payment_date"
                                    :initialized="initPaymentDate"
                                    :isRequired=false
                                    selectionMode="Month"
                                    format="yyyy/MM"
                                ></wj-input-date>
                                <p class="text-danger">{{ errors.payment_date }}</p>
                            </div>
                        </div>
                                
                        <div class="col-md-6" style="padding-top: 20px;">
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
                <h3 class="col-sm-12"><b>支払予定一覧</b></h3>
                <div class="col-sm-6">
                    <div class="filter-body input-group">
                        <label class="col-md-12"><u>絞り込み機能</u></label>
                        <el-checkbox class="col-md-2" v-model="filterChk.unsettled" :true-label="1" :false-label="0" @input="filter($event)">未確定</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.fixed" :true-label="1" :false-label="0" @input="filter($event)">確定分</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.pending" :true-label="1" :false-label="0" @input="filter($event)">承認待</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.approved" :true-label="1" :false-label="0" @input="filter($event)">承認済</el-checkbox>
                        <el-checkbox class="col-md-2" v-model="filterChk.paid" :true-label="1" :false-label="0" @input="filter($event)">支払済</el-checkbox>
                    </div>
                </div>
            </div>
            <div class="row">

            </div>
            <div class="row">
                <div class="col-md-12">
                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData }}件</p>
                </div>
                <div id="wjPaymentGrid"></div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <div class="col-md-1 col-md-offset-3">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">&emsp;領収書印刷&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">&emsp;領収書印刷&emsp;</button>
                    </div>
                    <div class="col-md-1 col-md-offset-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">出金予定表印刷</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">&emsp;振込出力&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">&emsp;手形出力&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">&emsp;電債出力&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">&emsp;送付状印刷&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">会計データ出力</button>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 10px;">
                <div class="col-md-12">
                    <div class="col-md-1 col-md-offset-10">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">仕入データ出力</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">支払データ出力</button>
                    </div>
                </div>
            </div>

            <div class="row" style="margin-top: 20px;">
                <div class="col-md-12">
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-bind:disabled="!this.isEditable" @click="showRequestDialog">&emsp;申請&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-delete" v-bind:disabled="!this.isEditable" @click="requestCancel">&emsp;申請取消&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-bind:disabled="!this.isApprovalStaff" @click="showApprovalDialog">&emsp;承認処理&emsp;</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false" v-bind:disabled="!this.isEditable">支払予定表印刷</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false">仕入先別集計印刷</button>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-save" v-show="false">支払通知書印刷</button>
                    </div>

                    <div class="col-md-1 col-md-offset-2">
                        <button type="button" class="btn btn-delete" @click="paymentClosing">&emsp;支払締め&emsp;</button>
                    </div>

                    <div class="col-md-1 col-md-offset-2">
                        <button type="button" class="btn btn-save" @click="back">&emsp;戻る&emsp;</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- リベート内訳ダイアログ -->
        <el-dialog :visible.sync="showDlgRebateDetail" :closeOnClickModal=false width="75%">
            <span slot="title">
                <span class="dialog-title">リベートその他</span>
                <span>&emsp;支払先:{{ rebateParams.supplier_name }}</span>
                <span>&emsp;支払日:{{ rebateParams.planned_payment_date }}</span>
            </span>
            <div class="col-md-12">
                <div class="col-md-5">
                    <span>リベート合計:{{ rebateParams.rebate | comma_format  }}</span>
                </div>
                <div class="col-md-5">
                    <span>差 額:{{ rebateParams.difference | comma_format  }}</span>
                </div>
                <div class="col-md-1">
                    <el-button @click="addRebateRow" v-bind:disabled="rebateParams.isLock" class="btn btn-sm btn-cancel">明細行追加</el-button>
                </div>
            </div>
            
            <div class="dialog-section">
                <table class="sticky_table">
                    <thead>
                        <tr>
                            <th>&emsp;</th>
                            <th>種類</th>
                            <th>得意先名</th>
                            <th>案件名</th>
                            <th>部門</th>
                            <th>数量</th>
                            <th>単位</th>
                            <th>単価</th>
                            <th>金額</th>
                            <th>摘要</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(data, index) in rebateArray" :key="index" v-show="data.del_flg == 0">
                            <td><el-button class="margin-left: 5px;" type="danger" icon="el-icon-close" circle size="mini" v-bind:disabled="rebateParams.isLock" @click="deleteRebateRow(index)"></el-button></td>
                            <td><div><div class="dialog-autocomp" v-bind:id="'acPtype-' + index"></div></div></td>
                            <td><div><div class="dialog-autocomp" v-bind:id="'acCus-' + index"></div></div></td>
                            <td><div><div class="dialog-autocomp" v-bind:id="'acMatter-' + index"></div></div></td>
                            <td><div><div class="dialog-autocomp" v-bind:id="'acDep-' + index"></div></div></td>
                            <td><div><input class="dialog-input" type="number" step="0.01" v-model="data.quantity" v-bind:disabled="rebateParams.isLock" @input="calcRebateUnitPrice(index)"></div></td>
                            <td><div><input class="dialog-input" type="text" v-model="data.unit" v-bind:disabled="rebateParams.isLock"></div></td>
                            <td><div><input class="dialog-input" type="number" v-model="data.cost_unit_price" v-bind:disabled="rebateParams.isLock" @input="calcRebateUnitPrice(index)"></div></td>
                            <td><div v-bind:class="{'has-error': (rebateParams.difference != 0) }" ><input class="dialog-input" type="number" v-model="data.cost_total" disabled @input="calcRebatePrice"></div></td>
                            <td><div><input class="dialog-input" type="text" v-model="data.discount_reason" v-bind:disabled="rebateParams.isLock"></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="col-md-12" v-show="rebateParams.difference != 0">
                <span style="color: red;">{{ rebateErrorMsg }}</span>
            </div>
            <span slot="footer" class="dialog-footer">
                <div class="col-md-7 pull-left">
                    <div class="col-md-5">
                        <span>合計:{{ rebateParams.sum_amount | comma_format  }}</span>
                    </div>
                </div>

                <div class="col-md-5 pull-right">
                    <el-button @click="saveRebate" class="btn btn-md btn-save" v-bind:disabled="(rebateParams.difference != 0 || rebateParams.isLock)">登録</el-button>
                    <el-button @click="showDlgRebateDetail=false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>

        <!-- 手形情報ダイアログ -->
        <el-dialog title="手形情報" :visible.sync="showDlgBillsDetail" :closeOnClickModal=false width="75%">
            <span slot="title">
                <span class="dialog-title">手形情報入力</span>
                <span>&emsp;支払先:{{ billsParams.supplier_name }}</span>
                <span>&emsp;支払日:{{ billsParams.payment_date }}</span>
            </span>
            <div class="col-md-12">
                <div class="col-md-10">
                    <span>手形金額:{{ billsParams.bills | comma_format  }}</span>
                </div>
                <div class="col-md-2">
                    <el-button @click="addBillsRow" v-bind:disabled="billsParams.isLock" class="btn btn-sm btn-cancel">明細行追加</el-button>
                </div>
            </div>
            
            <div class="dialog-section">
                <table class="sticky_table">
                    <thead>
                        <tr>
                            <th>&emsp;</th>
                            <th>金額</th>
                            <th>種類</th>
                            <th>銀合名</th>
                            <th>支店名</th>
                            <th>手形期日</th>
                            <th>廻し</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(data, index) in billsArray" :key="index" v-show="data.del_flg == 0">
                            <td><el-button class="margin-left: 5px;" type="danger" icon="el-icon-close" circle size="mini" @click="deleteBillsRow(index)" v-bind:disabled="billsParams.isLock"></el-button></td>
                            <td><input class="dialog-input" type="number" v-model="data.bills" @input="calcBillsPrice()" v-bind:disabled="billsParams.isLock"></td>
                            <td><div class="dialog-autocomp" v-bind:id="'acType-' + index"></div></td>
                            <td><div class="dialog-autocomp" v-bind:id="'acBank-' + index"></div></td>
                            <td><div class="dialog-autocomp" v-bind:id="'acBranch-' + index"></div></td>
                            <td><div class="dialog-autocomp" v-bind:id="'acDate-' + index"></div></td>
                            <td><div class="text-center"><el-checkbox v-model="data.endorseed_bill" :true-label="1" :false-label="0" v-bind:disabled="billsParams.isLock"></el-checkbox></div></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <!-- <div class="col-md-12" v-show="billsParams.difference != 0">
                <span style="color: red;">{{ checkErrorMsg }}</span>
            </div> -->
            <span slot="footer" class="dialog-footer">
                <div class="col-md-5 pull-right">
                    <el-button @click="saveBills" class="btn btn-md btn-save" v-bind:disabled="(billsParams.isLock)">登録</el-button>
                    <el-button @click="showDlgBillsDetail=false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>

        <!-- 申請メニューダイアログ -->
        <el-dialog title="申請メニュー" :visible.sync="showDlgRequest" :closeOnClickModal=false>
            <div class="col-md-12">
                <label class="control-label">特記事項/コメント</label>
                <textarea class="form-control" v-model="requestParams.apply_comment" rows="15"></textarea>
            </div>
            <div class="col-md-12">
                <label class="control-label">添付ファイル</label>
                <label class="file-up" for="file-up">
                    <svg class="svg-icon"><use width="25" height="25" xlink:href="#clipIcon"/></svg>
                </label>
                <!-- <input type="file" class="uploadfile" accept="image/png, image/jpeg" style="display:none" @input="selectFile"> -->
                <div class="file-operation-area well well-sm" style="height:50px;" @dragleave.prevent @dragover.prevent @drop.prevent="selectFile">
                    <input style="display:none;" type="file" @change="selectFile" id="file-up">
                    <ul class="detail-file-ul">
                        <span>{{ uploadFile.name }}</span>
                    </ul>
                </div>
            </div>

            <span slot="footer" class="dialog-footer">
                <div class="col-md-5 pull-right">
                    <el-button @click="sendRequest" class="btn btn-md btn-save">申請</el-button>
                    <el-button @click="showDlgRequest=false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>

        <!-- 承認ダイアログ -->
        <el-dialog title="承認処理" width="80%" :visible.sync="showDlgApproval" :closeOnClickModal=false>
            <span slot="title">
                <span class="dialog-title">承認処理</span>
                <span>&emsp;{{ approvalParams.payment_date }}</span>
            </span>
            <div class="col-md-12">
                <div class="col-md-2">
                    <label class="control-label">申請日</label>
                </div>
                <div class="col-md-2">
                    <label class="control-label">申請者</label>
                </div>
                <div class="col-md-2">
                    <label class="control-label">支払日</label>
                </div>
                <div class="col-md-2">
                    <label class="control-label">申請金額</label>
                </div>
            </div>
            
            <div class="dialog-section">
                <div v-for="(data, index) in approvalArray" :key="index">
                    <div class="col-md-12">
                        <div class="col-md-2">
                            <label>{{ data.apply_date }}</label>
                        </div>
                        <div class="col-md-2">
                            <label>{{ data.apply_staff_name }}</label>
                        </div>
                        <div class="col-md-2">
                            <label>{{ data.planned_payment_date }}</label>
                        </div>
                        <div class="col-md-2">
                            <label>{{ data.total_amount | comma_format }}</label>
                        </div>
                        <div class="col-md-2">
                            <label style="cursor:pointer;" @click="download(data)"><u>添付ファイル</u></label>
                        </div>
                        <div class="col-md-2">
                            <el-radio-group v-model="data.approval_flg" v-bind:disabled="data.status == 4">
                                <div class="radio">
                                    <el-radio class="col-md-2" :label="1">承認</el-radio>
                                    <el-radio class="col-md-2" :label="2">否認</el-radio>
                                    <el-radio class="col-md-2" :label="0">なし</el-radio>
                                </div>
                            </el-radio-group>
                        </div>
                    </div>

                    <div class="col-md-12" style="padding-bottom: 60px;">
                        <div class="col-md-8">
                            <label class="col-md-12">申請者コメント</label>
                            <textarea class="col-md-12" rows="5" v-model="data.apply_comment" disabled></textarea>
                        </div>

                        <div class="col-md-3">
                            <label class="col-md-12">承認者コメント</label>
                            <textarea class="col-md-12" rows="5" v-model="data.approval_comment" v-bind:disabled="data.status == 3 || data.status == 4"></textarea>
                        </div>
                        <div class="col-md-1">
                            <div class="status" v-bind:class="data.status_class">{{ data.status_text }}</div>
                            <!-- <button type="button" class="btn btn-save" style="margin-top:60px;">通知</button> -->
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12" v-show="billsParams.difference != 0">
                <span style="color: red;">{{ checkErrorMsg }}</span>
            </div>
            <span slot="footer" class="dialog-footer">
                <div class="col-md-5 pull-right">
                    <el-button @click="saveApproval" class="btn btn-md btn-save">完了</el-button>
                    <el-button @click="showDlgApproval=false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>

    </div>
</template>


<script>
import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';
import { forEachRight } from 'lodash';

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        isApprovalStaff: false,

        AUTH_LIST: {
            STAFF: 13,
            APP_1: 14,
            APP_2: 15,
            APP_3: 16,
        },

        FLG_LIST: {
            UNSETTLED: 0,
            FIXED: 1,
            PENDING: 2,
            APPROVED: 3,
            PAID: 4,
            CLOSING: 5,
        },

        // リベート内訳ダイアログ
        showDlgRebateDetail: false,
        rebateParams: {
            payment_id: '',
            supplier_id: '',
            supplier_name: '',
            planned_payment_date: '',
            rebate: 0,
            difference: 0,
            sum_amount: 0,
            isLock: false,
            error_message: '',
        },
        rebateArray: [],
        rebateErrorMsg: MSG_ERROR_PURCHASE_DIFFERENCE,
        wjRebateAC: [],
        // 手形情報ダイアログ
        showDlgBillsDetail: false,
        checkReadOnly: false,
        billsParams: {
            id: '',
            supplier_name: '',
            payment_date: '',
            bills: '',
            difference: 0,
            calc_date: '',
            bills_total: 0,
            isLock: false,
        },
        billsArray: [],
        checkErrorMsg: MSG_ERROR_BILLS_DIFFERENCE,
        wjBillsAC: [],

        // 申請ダイアログ
        showDlgRequest: false,
        requestParams: {
            id: '',
            apply_comment: '',
            payment_date: '',
            status: 0,
        },
        requestArray: [],
        uploadFile: {},

        // 承認ダイアログ
        showDlgApproval: false,
        approvalParams: {   
            payment_date: '',
        },
        approvalArray: [],

        focusTarget: {},

        FLG_ON: 1,
        FLG_OFF: 0,

        tableData: 0,

        filterChk: {
            unsettled: 1,
            fixed: 1,
            pending: 1,
            approved: 1,
            paid: 0,
        },

        errors: {
            supplier_id: '',
            payment_date: '',
        },

        searchParams: {
            supplier_id: '',
            payment_date: null,
        },

        wjSearchObj: {
            supplier_id: {},
            payment_date: {},
        },

        layoutDefinition: null,
        keepDOM: {},
        urlparam: '',

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 0,

        wjPaymentGrid: null,
        cgePaymentDate: null,

        branchlist: [],
    }),
    props: {
        isEditable: Number,
        authList: {},
        supplierlist: Array,
        safetyfeelist: Array,
        cashlist: Array,
        purchasetypelist: Array,
        customerlist: Array,
        matterlist: Array,
        departmentlist: Array,
        banklist: Array,
        billstype: Array,
    },
    created: function() {
        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.payment_date == "") { this.searchParams.payment_date = null };
        }
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {     
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            this.setSearchParams(query, this.searchParams);
            // 検索
            this.search();
        }

        var item = this.authList;
        Object.keys(this.authList).forEach(function (key) {
            if (item[key] == this.FLG_ON) {
                this.isApprovalStaff = true;
            }
        }.bind(this));

        this.$nextTick(function() {
            this.wjPaymentGrid = this.createGridCtrl('#wjPaymentGrid', new wjCore.CollectionView([]));
        });

    },
    methods:{
        download(data) {
            if (data.attachments_file != '') {
                var downloadUrl = '/payment-list/download/' + data.id + '/' + encodeURIComponent(data.attachments_file);

                location.href = downloadUrl;
            } else {
                alert(MSG_ERROR_NO_DATA);
                return;
            }
        },
        // 承認
        saveApproval() {
            // 権限がない
            if (!this.isApprovalStaff) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            var hasAuth = false;
            this.approvalArray.forEach(row => {
                if (row.status == 0 && this.authList.approval_staff_1) {
                    hasAuth = true;
                }
                if (row.status == 1 && this.authList.approval_staff_2) {
                    hasAuth = true;
                }
                if (row.status == 2 && this.authList.approval_staff_3) {
                    hasAuth = true;
                }
            });

            if (!hasAuth) {
                alert(MSG_ERROR_REQUEST_PAYMENT_APPROVAL_AUTH);
                return;
            }

            if (!confirm(MSG_CONFIRM_PAYMENT_REQUEST)) {
                return;
            }

            var approvalList = [];
            var denialList = [];
            this.approvalArray.forEach(row => {
                if (row.approval_flg == 1) {
                    approvalList.push(row);
                }
                if (row.approval_flg == 2) {
                    denialList.push(row);
                }
            });
            this.loading = true;
            var params = new URLSearchParams();

            var url = '/payment-list' + this.urlparam;

            params.append('approvalList', JSON.stringify(approvalList));
            params.append('denialList', JSON.stringify(denialList));
            params.append('redirect_url', url);
        
            axios.post('/payment-list/approval', params)
            .then( function (response) {
                this.loading = false
                if (response.data.result) {
                    // 成功
                    if (this.rmUndefinedBlank(response.data.message) != '') {
                        alert(response.data.message);
                    }
                    var listUrl = '/payment-list' + this.urlparam;
                    location.href = (listUrl);
                } else {
                    // 失敗
                    alert(response.data.message)
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
                    // location.reload()
                }
            }.bind(this))
        },
        showApprovalDialog() {
            // 権限がない
            if (!this.isApprovalStaff) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            // this.loading = true;

            var params = new URLSearchParams();

            params.append('payment_date', this.wjSearchObj.payment_date.text);
        
            axios.post('/payment-list/searchRequest', params)
            .then( function (response) {
                this.loading = false
                if (response.data) {
                    // 成功
                    this.approvalParams.payment_date = response.data.payment_date;

                    var items = [];
                    response.data.list.forEach(row => {
                        row.approval_flg = 0;
                        items.push(row);
                    });
                    this.approvalArray = items;

                    this.showDlgApproval = true;
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
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
                    // location.reload()
                }
            }.bind(this))
        },
        paymentClosing() {
            var requestList = [];
            this.wjPaymentGrid.itemsSource.items.forEach(row => {
                if (row.chk) {
                    requestList.push(row);
                }
            });

            // 権限がない
            if (!this.isEditable) {
                alert(MSG_ERROR_PAYMENT_CLOSING);
                return;
            }

            // チェックが付いた行が0件
            if (requestList.length == 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            // TODO:支払済でなければエラー
            var isError = false;
            // requestList.forEach(row => {
            //     if (row.status != this.FLG_LIST.PAID) {
            //         isError = true;
            //     }
            // });

            // 仕様変更のため、承認済みで実行できるようにする
            requestList.forEach(row => {
                if (row.status != this.FLG_LIST.APPROVED) {
                    isError = true;
                }
            });

            if (isError) {
                alert(MSG_ERROR_NOT_REQUESTED);
                return;
            } 

            if (!confirm(MSG_CONFIRM_PAYMENT_CLOSING)) {
                return;
            }
            
            this.loading = true;
            var url = '/payment-list' + this.urlparam;

            var params = new URLSearchParams();

            params.append('requestList', JSON.stringify(requestList));
            params.append('redirect_url', url);
        
            axios.post('/payment-list/closing', params)
            .then( function (response) {                
                this.loading = false
                if (response.data) {
                    // 成功
                    var listUrl = '/payment-list' + this.urlparam
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
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
                    // location.reload()
                }
            }.bind(this))
        },
        requestCancel() {
            var requestList = [];
            this.wjPaymentGrid.itemsSource.items.forEach(row => {
                if (row.chk) {
                    requestList.push(row);
                }
            });

            // 権限がない
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            // チェックが付いた行が0件
            if (requestList.length == 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            // 申請済でなければエラー
            var isError = false;
            requestList.forEach(row => {
                if (row.status != this.FLG_LIST.PENDING) {
                    isError = true;
                }
            });

            if (isError) {
                alert(MSG_ERROR_NOT_REQUESTED);
                return;
            } 

            if (!confirm(MSG_CONFIRM_PAYMENT_REQUEST_CANCEL)) {
                return;
            }

            this.loading = true;
            var url = '/payment-list' + this.urlparam;

            var params = new URLSearchParams();

            params.append('requestList', JSON.stringify(requestList));
            params.append('redirect_url', url);
        
            axios.post('/payment-list/cancelRequest', params)
            .then( function (response) {                
                this.loading = false
                if (response.data) {
                    // 成功
                    var listUrl = '/payment-list' + this.urlparam
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
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
                    // location.reload()
                }
            }.bind(this))
        },
        back() {
            var listUrl = '/';
            location.href = (listUrl);
        },
        selectFile(event) {
            if(!this.isReadOnly){
                let fileList = event.target.files ? 
                                event.target.files:
                                event.dataTransfer.files;
                let files = [];
                if (fileList.length == 1) {
                    this.uploadFile = fileList[0];
                }
            }
        },
        // 申請
        sendRequest() {
            this.loading = true;
            var url = '/payment-list' + this.urlparam;

            var params = new FormData();

            params.append('id', this.requestParams.id);
            params.append('apply_comment', this.requestParams.apply_comment);
            params.append('payment_date', this.requestParams.payment_date);
            params.append('status', this.requestParams.status);
            params.append('requestList', JSON.stringify(this.requestArray));
            params.append('uploadFile', this.uploadFile);
            params.append('redirect_url', url);
        
            axios.post('/payment-list/paymentRequest', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {                
                this.loading = false
                if (response.data) {
                    // 成功
                    // this.showDlgRequest = false;
                    var listUrl = '/payment-list' + this.urlparam
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
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
                    // location.reload()
                }
            }.bind(this))
        },
        // 申請メニュー
        showRequestDialog() {
            var requested = false;
            var unsettled = false;
            var requestList = [];
            this.wjPaymentGrid.itemsSource.items.forEach(row => {
                if (row.chk) {
                    requestList.push(row);

                    if (row.status >= this.FLG_LIST.PENDING) {
                        // 申請済
                        requested = true;
                    }
                    if (row.status == this.FLG_LIST.UNSETTLED) {
                        // 未確定
                        unsettled = true;
                    }
                }
            });

            // 権限がない
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }

            // チェックが付いた行が0件
            if (requestList.length == 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            // 未確定状態
            if (unsettled) {
                alert(MSG_ERROR_NOT_FIXED)
                return;
            }

            // 申請済
            if (requested) {
                alert(MSG_ERROR_REQUESTED_PAYMENT)
                return;
            }

            var counts = {};
            // 支払日チェック
            for(var i=0;i< requestList.length;i++) {
                var key = requestList[i].payment_date;
                counts[key] = (counts[key])? counts[key] + 1 : 1 ;
            }

            if (Object.keys(counts).length >= 2) {
                alert(MSG_ERROR_PAYMENT_DATE_DIFFERENCE);
                return;
            }

            this.requestParams.payment_date = requestList[0]['payment_date'];
            this.requestArray = requestList;

            this.showDlgRequest = true;
        },
        // 手形情報　行追加
        addBillsRow() {
            var addRow = {
                id: '',
                payment_id: this.billsParams.payment_id,
                bills: 0,
                type: 0,
                bank_code: '',
                branch_code: '',
                bill_of_exchange_due: this.billsParams.calc_date,
                endorseed_bill: 0,
                del_flg: 0,
            }

            var lastIndex = this.billsArray.length;
            this.billsArray.push(addRow);

            this.$nextTick(function () {         
                this.wjBillsAC.push({
                    type: {},
                    bank_name: {},
                    branch_name: {},
                    bill_of_exchange_due: {},
                })
                
                this.wjBillsAC[lastIndex] = this.createBillsAutoComp(lastIndex, addRow);
            })
        },
        // 手形情報　行削除
        deleteBillsRow(target) {
            this.billsArray[target].del_flg = this.FLG_ON;

            this.calcBillsPrice();
        },
        // 手形情報　総額計算
        calcBillsPrice() {
            var total = 0;
            var diff = 0;
            this.billsArray.forEach((element, i) => {
                if (element.del_flg == this.FLG_OFF){
                    total = this.bigNumberPlus(total, this.billsArray[i].bills);
                }
            });
            
            diff = this.bigNumberMinus(total, this.billsParams.bills);

            this.billsParams.bills_total = total;
            this.billsParams.difference = diff;
        },
        // リベート内訳　削除
        deleteRebateRow(target) {
            this.rebateArray[target].del_flg = this.FLG_ON;

            this.calcRebatePrice();
        },
        // リベート内訳　単価×数量
        calcRebateUnitPrice(index) {
            this.rebateArray.forEach((element, i) => {
                if (i == index) {
                    var unitPrice = this.rebateArray[i].cost_unit_price;
                    var quantity = this.rebateArray[i].quantity;
                    
                    this.rebateArray[i].cost_total = this.bigNumberTimes(unitPrice, quantity);
                }
            });

            this.calcRebatePrice();
        },
        // リベート内訳　総額計算
        calcRebatePrice() {
            var total = 0;
            var diff = 0;
            this.rebateArray.forEach((element, i) => {
                if (element.del_flg == this.FLG_OFF){
                    total = this.bigNumberPlus(total, this.rebateArray[i].cost_total);
                }
            });
            
            diff = this.bigNumberMinus(total, this.rebateParams.rebate);

            this.rebateParams.sum_amount = total;
            this.rebateParams.difference = diff;
        },
        createBillsAutoComp(divIndex, itemsSrc) {
            var acControl = {};

            var readonly = (this.billsParams.isLock || this.checkReadOnly);

            var targetId = '#acBranch-'+divIndex;
            acControl.branch_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'branch_name',
                displayMemberPath: 'branch_name',
                selectedValuePath: 'branch_code',
                selectedIndex: -1,
                isReadOnly: readonly,
                isRequired: false,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.branchlist.length,
                itemsSource: this.branchlist,
            });
            acControl.branch_name.selectedValue = itemsSrc.branch_code;
            acControl.branch_name.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    this.billsArray[divIndex].branch_code = items.branch_code;
                    this.billsArray[divIndex].branch_name = items.bank_name;
                } else {
                    this.billsArray[divIndex].branch_code = '';
                    this.billsArray[divIndex].branch_name = '';
                }
            }.bind(this));

            var targetId = '#acBank-'+divIndex;
            acControl.bank_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'bank_name',
                displayMemberPath: 'bank_name',
                selectedValuePath: 'bank_code',
                selectedIndex: -1,
                isReadOnly: readonly,
                isRequired: false,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.banklist.length,
                itemsSource: this.banklist,
            });
            acControl.bank_name.selectedValue = itemsSrc.bank_code;
            acControl.bank_name.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    if (items != null) {
                        var params = new URLSearchParams();

                        params.append('bank_code', items.bank_code);
                    
                        axios.post('/payment-list/searchBranch', params)
                        .then( function (response) {
                            if (response.data) {
                                // 成功
                                // this.branchlist = response.data;

                                acControl.branch_name.itemsSource = response.data;

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
                                // location.reload()
                            }
                        }.bind(this))
                    }

                    this.billsArray[divIndex].bank_code = items.bank_code;
                    this.billsArray[divIndex].bank_name = items.bank_name;
                } else {
                    this.billsArray[divIndex].bank_code = '';
                    this.billsArray[divIndex].bank_name = '';
                }
            }.bind(this));

            var targetId = '#acDate-'+divIndex;
            acControl.bill_of_exchange_due = new wjcInput.InputDate(targetId, {
                // value: null,
                isReadOnly: readonly,
                isRequired: false,
            });
            if (itemsSrc.bill_of_exchange_due != undefined || itemsSrc.bill_of_exchange_due != null) {
                acControl.bill_of_exchange_due.value = itemsSrc.bill_of_exchange_due;
            } else {
                acControl.bill_of_exchange_due.value = null;
            }
            acControl.bill_of_exchange_due.valueChanged.addHandler(function (sender) {
                var item = sender.text;
                if(item !== null) {
                    this.billsArray[divIndex].bill_of_exchange_due = item;
                }else{
                    this.billsArray[divIndex].bill_of_exchange_due = '';
                }
            }.bind(this));

            var targetId = '#acType-'+divIndex;
            acControl.type = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'value_text_1',
                displayMemberPath: 'value_text_1',
                selectedValuePath: 'value_code',
                selectedIndex: -1,
                isReadOnly: readonly,
                isRequired: false,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.billstype.length,
                itemsSource: this.billstype,
            });
            acControl.type.selectedValue = itemsSrc.type;
            acControl.type.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    this.billsArray[divIndex].type = items.value_code;

                    if (items.value_code == this.FLG_ON) {
                        // 種類が電債の場合
                        acControl.bank_name.selectedIndex = -1;
                        acControl.bank_name.isReadOnly = true;
                        
                        acControl.branch_name.selectedIndex = -1;
                        acControl.branch_name.isReadOnly = true;
                    } else {
                        acControl.bank_name.isReadOnly = false;
                        acControl.branch_name.isReadOnly = false;
                    }
                } else {
                    this.billsArray[divIndex].type = null;
                }
            }.bind(this));

            return acControl;
        },
        createRebateAutoComp(divIndex, itemsSrc) {
            var acControl = {};

            var targetId = '#acPtype-'+divIndex;
            acControl.purchase_type = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'value_text_1',
                displayMemberPath: 'value_text_1',
                selectedValuePath: 'value_code',
                selectedIndex: -1,
                isReadOnly: this.rebateParams.isLock,
                isRequired: false,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.purchasetypelist.length,
                itemsSource: this.purchasetypelist,
            });
            acControl.purchase_type.selectedValue = itemsSrc.purchase_type;
            acControl.purchase_type.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    this.rebateArray[divIndex].purchase_type = items.value_code;
                } else {
                    this.rebateArray[divIndex].purchase_type = null;
                }
            }.bind(this));

            var targetId = '#acDep-'+divIndex;
            acControl.department_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'department_name',
                displayMemberPath: 'department_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.rebateParams.isLock,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.departmentlist.length,
                itemsSource: this.departmentlist,
            });
            acControl.department_name.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    this.rebateArray[divIndex].charge_department_id = items.id;
                    this.rebateArray[divIndex].charge_department_name = items.department_name;
                } else {
                    this.rebateArray[divIndex].charge_department_id = 0;
                    this.rebateArray[divIndex].charge_department_name = '';
                }
            }.bind(this));
            
            var targetId = '#acMatter-'+divIndex;
            acControl.matter_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'matter_name',
                displayMemberPath: 'matter_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.rebateParams.isLock,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.matterlist.length,
                itemsSource: this.matterlist,
            });
            acControl.matter_name.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    this.rebateArray[divIndex].matter_id = items.id;
                } else {
                    this.rebateArray[divIndex].matter_id = 0;
                }

                // 案件を変更したら部門名を絞り込む
                var tmpDepartment = this.departmentlist;
                if (items) {
                    tmpDepartment = [];
                    for(var key in this.departmentlist) {
                        if (items.id == this.departmentlist[key].id) {
                            tmpDepartment.push(this.departmentlist[key]);
                        }
                    }             
                }
                acControl.department_name.itemsSource = tmpDepartment;
                acControl.department_name.selectedIndex = -1;
            }.bind(this));


            var targetId = '#acCus-'+divIndex;
            acControl.customer_name = new wjcInput.AutoComplete(targetId, {
                searchMemberPath: 'customer_name',
                displayMemberPath: 'customer_name',
                selectedValuePath: 'id',
                selectedIndex: -1,
                isRequired: false,
                isReadOnly: this.rebateParams.isLock,
                // selectedValue: itemsSrc.construction_id_1,
                // selectedIndexChanged: this.selectConstructionByIndex_1,
                maxItems: this.customerlist.length,
                itemsSource: this.customerlist,
            });
            acControl.customer_name.selectedIndexChanged.addHandler(function (sender) {
                var items = sender.selectedItem;
                if (items !== null) {
                    this.rebateArray[divIndex].customer_id = items.id;
                    this.rebateArray[divIndex].customer_name = items.customer_name;
                } else {
                    this.rebateArray[divIndex].customer_id = 0;
                    this.rebateArray[divIndex].customer_name = '';
                }
                
                // 得意先を変更したら案件名を絞り込む
                var tmpMatter = this.matterlist;
                if (items) {
                    tmpMatter = [];
                    for(var key in this.matterlist) {
                        if (items.id == this.matterlist[key].customer_id) {
                            tmpMatter.push(this.matterlist[key]);
                        }
                    }             
                }
                acControl.matter_name.itemsSource = tmpMatter;
                acControl.matter_name.selectedIndex = -1;
            }.bind(this));
            acControl.customer_name.selectedValue = itemsSrc.customer_id;
            acControl.matter_name.selectedValue = itemsSrc.matter_id;
            acControl.department_name.selectedValue = itemsSrc.charge_department_id;

            return acControl;
        },
        // リベート内訳　明細追加
        addRebateRow() {
            var addRow = {
                id: '',
                payment_id: this.rebateParams.payment_id,
                discount_reason: '',
                purchase_type: 0,
                supplier_id: this.rebateParams.supplier_id,
                supplier_name: this.rebateParams.supplier_name,
                planned_payment_date: '',
                customer_id: 0,
                customer_name: '',
                matter_id: 0,
                matter_name: '',
                charge_department_id: 0,
                department_name: '',
                quantity: '1.00',
                unit: '',
                cost_unit_price: 0,
                cost_total: 0,
                del_flg: 0,
            }

            var lastIndex = this.rebateArray.length;
            this.rebateArray.push(addRow);

            this.$nextTick(function () {         
                this.wjRebateAC.push({
                    purchase_type: {},
                    customer_name: {},
                    matter_name: {},
                    department_name: {},
                })
                
                this.wjRebateAC[lastIndex] = this.createRebateAutoComp(lastIndex, addRow);
            })
        },
        saveRebate() {
            var record = this.focusTarget;

            // 権限がない場合は終了
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_STAFF);
                return;
            }
            // 会計データ出力済の場合終了
            if (record.accounting_flg) {
                alert(MSG_ERROR_ACCOUNT_OUTPUTTED);
                return;
            }
            // 差額があった場合は元に戻る
            if (this.rebateParams.rebate != this.rebateParams.sum_amount) {
                alert(MSG_ERROR_PURCHASE_DIFFERENCE);
                return;
            }

            if (!confirm(MSG_CONFIRM_UPDATE_REBATE)) {
                return;
            }

            this.loading = true;

            var params = new URLSearchParams();

            params.append('paymentData', JSON.stringify(record));
            params.append('rebateList', JSON.stringify(this.rebateArray));
        
            axios.post('/payment-list/saveRebate', params)
            .then( function (response) {                
                this.loading = false
                if (response.data) {
                    // 成功
                    this.showDlgRebateDetail = false;
                    // var listUrl = '/payment-list' + this.urlparam
                    // location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
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
                    // location.reload()
                }
            }.bind(this))
        },
        saveBills() {
            var record = this.focusTarget;

            // 権限がない場合は終了
            if (!this.isEditable) {
                alert(MSG_ERROR_NOT_PURCHASE_AUTH_BILLS);
                return;
            }
            // 手形出力済の場合は戻る
            if (record.bills_flg) {
                alert(MSG_ERROR_BILLS_OUTPUTTED);
                return;
            }
            // 差額があった場合は元に戻る
            // if (this.billsParams.difference != 0) {
            //     alert(MSG_ERROR_PURCHASE_DIFFERENCE);
            //     return;
            // }

            // 確認メッセージ
            if (!confirm(MSG_CONFIRM_UPDATE_BILLS)) {
                return;
            }

            this.loading = true;

            var params = new URLSearchParams();

            params.append('paymentData', JSON.stringify(record));
            params.append('billsList', JSON.stringify(this.billsArray));
        
            axios.post('/payment-list/saveBills', params)
            .then( function (response) {
                this.loading = false
                if (response.data.result) {
                    // 成功
                    var isUpdate = false;
                    this.wjPaymentGrid.itemsSource.items.forEach(element => {
                        if (isUpdate) {
                            return;
                        }
                        if (record.id == element.id) {
                            element.has_bills_data = this.FLG_ON;
                            element.bills = response.data.billsTotal;
                            isUpdate = true;
                        }
                    });
                    this.wjPaymentGrid.refresh();
                    this.showDlgBillsDetail = false;
                    this.search();
                    // var listUrl = '/payment-list' + this.urlparam
                    // location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
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
                    // location.reload()
                }
            }.bind(this))
        },
        search() {
            this.loading = true;
            var params = new URLSearchParams();

            this.initErr(this.errors);

            params.append('supplier_id', this.rmUndefinedBlank(this.wjSearchObj.supplier_id.selectedValue));
            params.append('payment_date', this.rmUndefinedBlank(this.wjSearchObj.payment_date.text));
            
            axios.post('/payment-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'supplier_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_id.selectedValue));
                    this.urlparam += '&' + 'payment_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.payment_date.text));

                    var itemsSource = [];
                    var rowIdx = 0;
                    var dataLength = 0;

                    response.data.forEach(element => {
                        itemsSource.push({
                            chk: false,
                            id: element.id,
                            supplier_name: element.supplier_name,
                            payment_condition: element.payment_condition,
                            requested_amount: element.requested_amount,
                            rebate: element.rebate,
                            safety_fee: element.safety_fee,
                            safety_fee_type: element.safety_fee_type,
                            offset: element.offset,
                            receivable: element.receivable,
                            paid_rebate: element.paid_rebate,
                            cash: element.cash,
                            cash_type: element.cash_type,
                            check: element.check,
                            transfer: element.transfer,
                            fee: element.fee,
                            bills: element.bills,
                            issuance_fee: element.issuance_fee,
                            bill_deadline: element.bill_deadline,
                            bank_name: element.bank_name,
                            type: element.type,
                            endorseed_bill: element.endorseed_bill,
                            payment_date: element.payment_date,
                            status: element.status,
                            status_text: element.status_text,
                            download_status: element.download_status,
                            payment_no: element.payment_no,
                            supplier_id: element.supplier_id,
                            closing_day: element.closing_day,
                            payment_sight: element.payment_sight,
                            purchase_closing_day: element.purchase_closing_day,
                            payment_period_sday: element.payment_period_sday,
                            payment_period_eday: element.payment_period_eday,
                            confirmed_staff_id: element.confirmed_staff_id,
                            fixed_date: element.fixed_date,
                            request_payment_id: element.request_payment_id,
                            membershipfee_flg: element.membershipfee_flg,
                            offset_flg: element.offset_flg,
                            cash_flg: element.cash_flg,
                            transfer_flg: element.transfer_flg,
                            bills_flg: element.bills_flg,
                            electricitydebt_flg: element.electricitydebt_flg,
                            transmittal_flg: element.transmittal_flg,
                            accounting_flg: element.accounting_flg,
                            safety_org_cost: element.safety_org_cost,
                            sponsor_cost: element.sponsor_cost,
                            cash_rate: element.cash_rate,
                            check_rate: element.check_rate,
                            bill_rate: element.bill_rate,
                            transfer_rate: element.transfer_rate,
                            calc_date: element.calc_date,
                            has_bills_data: element.has_bills_data,
                            update_at: element.update_at,
                        });
                        dataLength++
                    })
                    this.wjPaymentGrid.dispose();

                    this.wjPaymentGrid = this.createGridCtrl('#wjPaymentGrid', new wjCore.CollectionView(itemsSource));

                    // this.wjPaymentGrid.itemsSource = itemsSource;
                    this.tableData = dataLength;

                    // 描画更新
                    this.wjPaymentGrid.refresh();

                    this.filter();
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
        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowSorting: false,
                autoClipboard: false,
            });

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
                var col = gridCtrl.getBindingColumn(panel, r, c);
                // var col = panel.columns[c];
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    // 列ヘッダのセンタリング
                    cell.style.textAlign = 'center';
                    cell.style.backgroundColor = '';

                    switch(col.name) {
                        case 'chk':
                            // 全チェック用のチェックボックス生成
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
                                gridCtrl.endUpdate();
                            });
                            break;
                        case 'supplier_name':
                        case 'payment_condition':
                        case 'requested_amount':
                        case 'rebate':
                            cell.style.backgroundColor = '';
                            break;

                        case 'safety_fee':
                        case 'safety_fee_type':
                        case 'offset':
                        case 'receivable':
                        case 'paid_rebate':
                            cell.classList.add('wj-red-color');
                            break;

                        case 'cash':
                        case 'cash_type':
                        case 'check':
                        case 'transfer':
                        case 'fee':
                            cell.classList.add('wj-green-color');
                            break;

                        case 'bills':
                        case 'issuance_fee':
                        case 'bill_deadline':
                        case 'bank_name':
                        case 'type':
                        case 'endorseed_bill':
                            cell.classList.add('wj-yellow-color');
                            break;

                        case 'payment_date':
                        case 'status_text':

                        case 'confirm':
                        case 'detail':
                            // cell.style.backgroundColor = '';
                            break;
                        

                    }
                }

                if (panel.cellType == wjGrid.CellType.Cell) {
                    // 背景色デフォルト設定
                    cell.style.backgroundColor = '';
                    // ReadOnlyセルの背景色設定
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);                
                    // スタイルリセット
                    cell.style.color = '';
                    // cell.style.textAlign = '';

                    // 横スクロールで文字位置がおかしくなるのでtextArignは明示的に設定
                    var colName = this.wjPaymentGrid.getBindingColumn(panel, r, c).name;

                    var dataItem = panel.rows[r].dataItem;
                    if(dataItem !== undefined) {

                    }

                    switch (colName) {
                        case 'supplier_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'payment_condition':
                            cell.style.textAlign = 'left';
                            break;
                        case 'requested_amount':
                            cell.style.textAlign = 'right';
                            break;
                        case 'rebate':
                            cell.style.textAlign = 'right';
                            cell.style.backgroundColor = '';
                            break;
                        case 'safety_fee':
                            cell.style.textAlign = 'right';
                            break;
                        case 'safety_fee_type':
                            cell.style.textAlign = 'left';
                            break;
                        case 'offset':
                            cell.style.textAlign = 'right';
                            break;
                        case 'receivable':
                            cell.style.textAlign = 'center';
                            break;
                        case 'paid_rebate':
                            cell.style.textAlign = 'right';
                            cell.style.backgroundColor = '';
                            break;

                        case 'cash':
                            cell.style.textAlign = 'right';
                            break;
                        case 'cash_type':
                            cell.style.textAlign = 'left';
                            break;
                        case 'check':
                            cell.style.textAlign = 'right';
                            break;
                        case 'transfer':
                            cell.style.textAlign = 'right';
                            break;
                        case 'fee':
                            cell.style.textAlign = 'right';
                            cell.style.backgroundColor = '';
                            break;

                        case 'bills':
                            cell.style.textAlign = 'right';
                            break;
                        case 'issuance_fee':
                            cell.style.textAlign = 'right';
                            break;
                        case 'bill_deadline':
                            cell.style.textAlign = 'right';
                            break;
                        case 'bank_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'type':
                            cell.style.textAlign = 'left';
                            break;
                        case 'endorseed_bill':
                            cell.style.textAlign = 'center';
                            break;
                        case 'payment_date':
                            // cell.style.textAlign = 'left';
                            break;
                        case 'status_text':
                            cell.style.textAlign = 'center';
                            // 状況
                            if (dataItem.status == this.FLG_LIST.UNSETTLED) {
                                cell.style.backgroundColor = '';
                            } else if (dataItem.status == this.FLG_LIST.FIXED) {
                                cell.style.backgroundColor = '#add8e6';
                            } else if (dataItem.status == this.FLG_LIST.PENDING) {
                                cell.style.backgroundColor = '#FFCC4E';
                            } else if (dataItem.status == this.FLG_LIST.APPROVED) {
                                cell.style.backgroundColor = '#99FF66';
                            } else if (dataItem.status == this.FLG_LIST.PAID) {
                                cell.style.backgroundColor = 'lightgrey';
                            }
                            break;
                        case 'btn_group':
                            cell.style.textAlign = 'center';
                            cell.style.padding = '0px';
                            if(dataItem !== undefined){
                                var rId1 = 'confirm-' + dataItem.id;
                                var rId2 = 'detail-' + dataItem.id;
                                var rId3 = 'cancel-' + dataItem.id;
                                
                                var isDisable = !this.isEditable;
                                // var isDisable = dataItem.status !== this.salesStatusList['val']['applying'];
                                // if(!isDisable){
                                //     // 承認部門の部門長以外でのログイン時はボタン非活性
                                //     isDisable = dataItem.chief_staff_id !== this.loginInfo.id;
                                // }                                

                                var div = document.createElement('div');
                                div.classList.add('btn-group');
                                div.setAttribute("data-toggle","buttons");

                                // 詳細ボタン
                                var btnDetail = document.createElement('button');
                                btnDetail.type = 'button';
                                btnDetail.id = rId2;
                                btnDetail.classList.add('btn', 'btn-edit');
                                btnDetail.innerHTML = '詳細';
                                // btnDetail.disabled = isDisable;

                                btnDetail.addEventListener('click', function (e) {
                                    this.btnClickDetail(dataItem);
                                }.bind(this));

                                div.appendChild(btnDetail);

                                // 確定ボタン
                                var confirmLock = false;
                                if (dataItem.status != this.FLG_OFF) {
                                    // 確定以降
                                    confirmLock = true;
                                } 
                                var btnConfirm = document.createElement('button');
                                btnConfirm.type = 'button';
                                btnConfirm.id = rId1;
                                btnConfirm.classList.add('btn', 'btn-edit');
                                btnConfirm.innerHTML = '確定';
                                btnConfirm.disabled = (isDisable || confirmLock);

                                btnConfirm.addEventListener('click', function (e) {
                                    this.btnClickConfirm(dataItem);
                                }.bind(this));

                                div.appendChild(btnConfirm);

                                // 確定解除ボタン
                                var cancelLock = false;
                                if (dataItem.status != this.FLG_LIST.FIXED) {
                                    // 確定以降
                                    cancelLock = true;
                                } 
                                var btnCancel = document.createElement('button');
                                btnCancel.type = 'button';
                                btnCancel.id = rId3;
                                btnCancel.classList.add('btn', 'btn-delete');
                                btnCancel.innerHTML = '確定解除';
                                btnCancel.disabled = (isDisable || cancelLock);

                                btnCancel.addEventListener('click', function (e) {
                                    this.btnClickCancel(dataItem);
                                }.bind(this));

                                div.appendChild(btnCancel);


                                cell.appendChild(div);
                                
                            }
                            break;
                        case 'download_status':
                            cell.style.textAlign = 'center';
                            cell.style.backgroundColor = '';
                            cell.style.padding = '0px';
                            if(dataItem !== undefined){
                                var div = document.createElement('div');
                                div.classList.add('status_text');
                                // div.setAttribute("data-toggle","buttons");

                                var rId1 = 'member-' + dataItem.id;
                                var rId2 = 'offset-' + dataItem.id;
                                var rId3 = 'cash-' + dataItem.id;
                                var rId4 = 'transfer-' + dataItem.id;
                                var rId5 = 'bills-' + dataItem.id;
                                var rId6 = 'elect-' + dataItem.id;
                                var rId7 = 'trans-' + dataItem.id;
                                var rId8 = 'account-' + dataItem.id;

                                // 安全会費領収書
                                var memberText = document.createElement('text');
                                memberText.type = 'text';
                                memberText.id = rId1;
                                memberText.innerHTML = '安 ';
                                if (dataItem.membershipfee_flg == this.FLG_ON) {
                                    memberText.classList.add('valid-text');
                                } else {
                                    memberText.classList.add('invalid-text');
                                }

                                div.appendChild(memberText);

                                // 相殺領収書
                                var offsetText = document.createElement('text');
                                offsetText.type = 'text';
                                offsetText.id = rId2;
                                offsetText.innerHTML = '相 ';
                                if (dataItem.offset_flg == this.FLG_ON) {
                                    offsetText.classList.add('valid-text');
                                } else {
                                    offsetText.classList.add('invalid-text');
                                }

                                div.appendChild(offsetText);

                                // 出金予定表印刷
                                var cashText = document.createElement('text');
                                cashText.type = 'text';
                                cashText.id = rId3;
                                cashText.innerHTML = '金 ';
                                if (dataItem.cash_flg == this.FLG_ON) {
                                    cashText.classList.add('valid-text');
                                } else {
                                    cashText.classList.add('invalid-text');
                                }

                                div.appendChild(cashText);

                                // 振込出力
                                var transferText = document.createElement('text');
                                transferText.type = 'text';
                                transferText.id = rId4;
                                transferText.innerHTML = '振 ';
                                if (dataItem.transfer_flg == this.FLG_ON) {
                                    transferText.classList.add('valid-text');
                                } else {
                                    transferText.classList.add('invalid-text');
                                }

                                div.appendChild(transferText);

                                // 手形出力
                                var billsText = document.createElement('text');
                                billsText.type = 'text';
                                billsText.id = rId5;
                                billsText.innerHTML = '手 ';
                                if (dataItem.bills_flg == this.FLG_ON) {
                                    billsText.classList.add('valid-text');
                                } else {
                                    billsText.classList.add('invalid-text');
                                }

                                div.appendChild(billsText);

                                // 電債出力
                                var electText = document.createElement('text');
                                electText.type = 'text';
                                electText.id = rId6;
                                electText.innerHTML = '電 ';
                                if (dataItem.electricitydebt_flg == this.FLG_ON) {
                                    electText.classList.add('valid-text');
                                } else {
                                    electText.classList.add('invalid-text');
                                }

                                div.appendChild(electText);

                                // 送付状印刷
                                var tranText = document.createElement('text');
                                tranText.type = 'text';
                                tranText.id = rId7;
                                tranText.innerHTML = '送 ';
                                if (dataItem.transmittal_flg == this.FLG_ON) {
                                    tranText.classList.add('valid-text');
                                } else {
                                    tranText.classList.add('invalid-text');
                                }

                                div.appendChild(tranText);

                                // 会計データ出力
                                var accText = document.createElement('text');
                                accText.type = 'text';
                                accText.id = rId8;
                                accText.innerHTML = '会 ';
                                if (dataItem.accounting_flg == this.FLG_ON) {
                                    accText.classList.add('valid-text');
                                } else {
                                    accText.classList.add('invalid-text');
                                }

                                div.appendChild(accText);

                                cell.appendChild(div);
                            }

                            break;
                    };
                }
            }.bind(this);
            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var col = this.wjPaymentGrid.getBindingColumn(e.panel, e.row, e.col);       // 取れるのは上段の列名
                // 編集行データ取得
                var row = s.collectionView.currentItem;

                switch (col.name) {
                    case 'payment_date':
                        
                        break;


                }
                gridCtrl.endUpdate();
            }.bind(this));

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;

                switch(col.name){
                    case 'payment_date':
                        if (row.status == this.FLG_LIST.UNSETTLED) {
                            this.cgePaymentDate.control.isDisabled = false;
                        } else {
                            this.cgePaymentDate.control.isDisabled = true;
                            e.cancel = true;
                        }
                        break;
                    case 'safety_fee':                 
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }
                        break;
                    case 'safety_fee_type':   
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }            
                        break;
                    case 'offset':     
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }          
                        break;
                    case 'paid_rebate':        
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }       
                        break;
                    case 'cash':        
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }      
                        break;
                    case 'cash_type':     
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }        
                        break;
                    case 'check':      
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }       
                        break;
                    case 'transfer':      
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }       
                        break;
                    case 'fee':       
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }      
                        break;
                    case 'issuance_fee':    
                        if (row.status != this.FLG_LIST.UNSETTLED) {  
                            e.cancel = true;
                        }            
                        break;
                }
            }.bind(this));

            // クリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        switch (col.name) {
                            case 'rebate':
                                if (record.accounting_flg == this.FLG_ON) {
                                    break;
                                }
                                if (record.rebate == 0) {
                                    alert(MSG_ERROR_SHOW_DIALOG_REBATE);
                                } else {
                                    this.focusTarget = record;
                                    this.showDlgRebate(record);
                                }
                                break;
                            case 'bills':
                                if (record.bills_flg == this.FLG_ON) {
                                    this.checkReadOnly = true;
                                } else {
                                    this.checkReadOnly = false;
                                }
                                // if (record.bills == 0) {
                                //     alert(MSG_ERROR_SHOW_DIALOG_BILLS);
                                // } else {
                                this.focusTarget = record;
                                this.showDlgBills(record);
                                // }
                                break;
                        }
                    }
                }
            });

            /* カスタムグリッド */
            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            }.bind(this), true);
            
            this.cgePaymentDate = new CustomGridEditor(gridCtrl, 'payment_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 2, 1, 1);

            return gridCtrl;
        },
        // リベート内訳ダイアログ表示
        showDlgRebate(row) {
            // this.loading = true;
            var params = new URLSearchParams();

            params.append('id', this.rmUndefinedBlank(row.id));
            
            axios.post('/payment-list/rebateInfo', params)

            .then( function (response) {
                if (response.data) {
                    var itemsSource = [];

                    var hd = response.data.header;

                    this.rebateParams.payment_id = hd.payment_id;
                    this.rebateParams.supplier_id = hd.supplier_id;
                    this.rebateParams.supplier_name = hd.supplier_name;
                    this.rebateParams.planned_payment_date = hd.planned_payment_date;
                    this.rebateParams.sum_amount = hd.sum_amount;
                    this.rebateParams.rebate = row.rebate;

                    var lock = false;
                    if (row.status != this.FLG_LIST.UNSETTLED) {
                        lock = true;
                    }
                    this.rebateParams.isLock = lock;

                    response.data.list.forEach((element, index) => {
                        itemsSource.push({
                            id: element.id,
                            payment_id: element.payment_id,
                            purchase_type: element.purchase_type,
                            discount_reason: element.discount_reason,
                            supplier_id: element.supplier_id,
                            supplier_name: element.supplier_name,
                            planned_payment_date: element.planned_payment_date,
                            customer_id: element.customer_id,
                            customer_name: element.customer_name,
                            matter_id: element.matter_id,
                            matter_name: element.matter_name,
                            charge_department_id: element.charge_department_id,
                            department_name: element.department_name,
                            quantity: element.quantity,
                            unit: element.unit,
                            cost_unit_price: element.cost_unit_price,
                            cost_total: element.cost_total,
                            del_flg: 0,
                        });

                        for(var i = 0 ; i < this.wjRebateAC.length ; i ++){
                            this.wjRebateAC[i].purchase_type.dispose();
                            this.wjRebateAC[i].customer_name.dispose();
                            this.wjRebateAC[i].matter_name.dispose();
                            this.wjRebateAC[i].department_name.dispose();
                        }

                        this.wjRebateAC = [];
                        this.$nextTick(function () {                            
                            this.wjRebateAC.push({
                                purchase_type: {},
                                customer_name: {},
                                matter_name: {},
                                department_name: {},
                            })
                            this.wjRebateAC[index] = this.createRebateAutoComp(index, element);
                        })
                        // this.wjRebateAC = this.createRebateAutoComp();
                    })
                }
                this.rebateArray = itemsSource;

                this.calcRebatePrice();

                this.showDlgRebateDetail = true;
                // this.loading = false
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
        // 手形情報ダイアログ
        showDlgBills(row) {
            // this.loading = true;
            var params = new URLSearchParams();
            if (row.bills_flg == this.FLG_ON) {
                this.checkReadOnly = true;
            }

            params.append('id', this.rmUndefinedBlank(row.id));
            
            axios.post('/payment-list/billsInfo', params)

            .then( function (response) {
                this.billsArray = [];
                this.branchlist = [];
                if (response.data) {
                    for(var i = 0 ; i < this.wjBillsAC.length ; i ++){
                        this.wjBillsAC[i].type.dispose();
                        this.wjBillsAC[i].bank_name.dispose();
                        this.wjBillsAC[i].branch_name.dispose();
                        this.wjBillsAC[i].bill_of_exchange_due.dispose();
                    }

                    this.billsParams.payment_id = row.id;
                    this.billsParams.bills = row.bills;
                    this.billsParams.payment_date = row.payment_date;
                    this.billsParams.supplier_name = row.supplier_name;
                    this.billsParams.calc_date = row.calc_date;

                    this.billsParams.isLock = false;
                    if (row.status != this.FLG_LIST.UNSETTLED) {
                        this.billsParams.isLock = true;
                    }

                    this.billsArray = response.data.list;
                    this.branchlist = response.data.branchList;

                    this.billsArray.forEach((element, index) => {
                        this.wjBillsAC = [];
                        
                        this.wjBillsAC.push({
                            type: {},
                            bank_name: {},
                            branch_name: {},
                            bill_of_exchange_due: {},
                        })
                        this.$nextTick(function () {
                            this.wjBillsAC[index] = this.createBillsAutoComp(index, element);
                        })
                    });

                    this.calcBillsPrice();
                }

                this.showDlgBillsDetail = true;
                // this.loading = false
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
        // 確定ボタン
        btnClickConfirm(row) {
            if (!this.isEditable) {
                alert(MSG_ERROR_CONFRIM_PURCHASE_AUTH);
                return;
            }

            // 売掛金が売掛残を超える
            if (row.receivable < row.offset) {
                alert(MSG_ERROR_OFFSET_AMOUNT_OVER);
                return;
            }

            // 安全会費、相殺、支払リベート、現金、小切手、振込、手形金額の合計金額が、請求金額と一致しない
            var sum = 0;
            sum = this.bigNumberPlus(sum, row.safety_fee);
            sum = this.bigNumberPlus(sum, row.offset);
            sum = this.bigNumberPlus(sum, row.paid_rebate);
            sum = this.bigNumberPlus(sum, row.cash);
            sum = this.bigNumberPlus(sum, row.check);
            sum = this.bigNumberPlus(sum, row.transfer);
            sum = this.bigNumberPlus(sum, row.bills);
            if (sum != row.requested_amount) {
                alert(MSG_ERROR_SUM_AMOUNTS_DIFFERENCE);
                return;
            }

            if (!confirm(MSG_CONFIRM_PAYMENT_CONFIRM)) {
                return;
            }

            if (row.bills > 0 && row.has_bills_data == this.FLG_OFF) {
                alert(MSG_ERROR_NO_INPUT_BILLS_DATA);
                return;
            }
            
            this.loading = true;
            var params = new URLSearchParams();

            params.append('paymentData', JSON.stringify(row));
        
            axios.post('/payment-list/save', params)
            .then( function (response) {
                if (response.data.result) {
                    // 成功
                    var listUrl = '/payment-list' + this.urlparam
                    location.href = (listUrl)
                } else {
                    // 失敗
                    alert(response.data.message);
                    this.search();
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
                    // location.reload()
                }
            }.bind(this))
        },
        // 詳細ボタン
        btnClickDetail(row) {
            if (!confirm(MSG_CONFIRM_TRANSITION_PURCHASE)) {
                return;
            }

            var listUrl = '';
            listUrl = '/purchase-detail?payment_no=' + row.payment_no + '&supplier_id=' + row.supplier_id + '&payment_date=' + this.rmUndefinedBlank(this.wjSearchObj.payment_date.text);
            location.href = (listUrl);
        },
        // 確定解除ボタン
        btnClickCancel(row) {
            if (!this.isEditable) {
                alert(MSG_ERROR_CONFRIM_PURCHASE_AUTH);
                return;
            }

            // ステータスが確定済み以外の場合
            if (row.status != this.FLG_ON) {
                alert(MSG_ERROR_PURCHASE_CANCEL);
                return;
            }

            if (!confirm(MSG_CONFIRM_PURCHASE_CANCEL)) {
                return;
            }

            this.loading = true;
            var params = new URLSearchParams();

            params.append('paymentData', JSON.stringify(row));
        
            axios.post('/payment-list/cancel', params)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    var listUrl = '/payment-list' + this.urlparam
                    location.href = (listUrl)
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
                    // location.reload()
                }
            }.bind(this))            
        },
        filter() {
            this.wjPaymentGrid.collectionView.filter = payment => {
                var show = false;

                if (this.filterChk.unsettled && payment.status == this.FLG_LIST.UNSETTLED) {
                    show = true;
                }
                if (this.filterChk.fixed && payment.status == this.FLG_LIST.FIXED) {
                    show = true;
                }
                if (this.filterChk.pending && payment.status == this.FLG_LIST.PENDING) {
                    show = true;
                }
                if (this.filterChk.approved && payment.status == this.FLG_LIST.APPROVED) {
                    show = true;
                }
                if (this.filterChk.paid && payment.status == this.FLG_LIST.PAID) {
                    show = true;
                }

                if (!this.filterChk.unsettled && !this.filterChk.fixed && !this.filterChk.pending && !this.filterChk.approved && !this.filterChk.paid) {
                    show = true;
                }

                return show;
            };
        },
        // レイアウト取得
        getLayout() {
            var safetyFeeMap = new wjGrid.DataMap(this.safetyfeelist, 'value_code', 'value_text_1');
            var cashMap = new wjGrid.DataMap(this.cashlist, 'value_code', 'value_text_1');

            return [
                {
                    cells: [
                        { binding: 'chk', name: 'chk', header: 'chk', minWidth: GRID_COL_MIN_WIDTH, width: 40, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'supplier_name', name: 'supplier_name', header: '仕入先名', minWidth: GRID_COL_MIN_WIDTH, width: 180, isReadOnly: true},
                        { binding: 'payment_condition', name: 'payment_condition', header: '支払条件', minWidth: GRID_COL_MIN_WIDTH, width: 180, isReadOnly: true},
                    ]
                },
                {
                    cells: [
                        { binding: 'requested_amount', name: 'requested_amount', header: '請求金額', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },                        
                        { binding: 'rebate', name: 'rebate', header: 'リベート等内訳', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'safety_fee', name: 'safety_fee', header: '安全会費', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                        { binding: 'safety_fee_type', name: 'safety_fee_type', dataMap: safetyFeeMap, header: '種別', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'offset', name: 'offset', header: '相殺', minWidth: GRID_COL_MIN_WIDTH,  width: 110, isReadOnly: false },
                        { binding: 'receivable', name: 'receivable', header: '(売掛残)',  minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'paid_rebate', name: 'paid_rebate', header: '支払時リベート', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                        // { binding: 'order_no', name: 'order_no', header: '発注番号', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'cash', name: 'cash', header: '現金', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                        { binding: 'cash_type', name: 'cash_type', header: '種別', dataMap: cashMap, minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'check', name: 'check', header: '小切手', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                        // { binding: 'cost_kbn', name: 'cost_kbn', header: '仕入区分', dataMap: priceKbnMap, minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'transfer', name: 'transfer', header: '振込', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                        { binding: 'fee', name: 'fee', header: '振込料', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'bills', name: 'bills', header: '手形金額', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'issuance_fee', name: 'issuance_fee', header: '発行手数料', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'bill_deadline', name: 'bill_deadline', header: '手形期日', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'bank_name', name: 'bank_name', header: '発行銀行', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'type', name: 'type', header: '種類', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'endorseed_bill', name: 'endorseed_bill', header: '発行数(廻し)', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'payment_date', name: 'payment_date', header: '支払日', width: 110, isReadOnly: false, isRequired: false},
                        { binding: 'status_text', name: 'status_text', header: '状況', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { name: 'btn_group', header: '操作', minWidth: GRID_COL_MIN_WIDTH, width: 190, isReadOnly: true },
                        // { name: 'detail', header: '詳細', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },
                        { binding: 'download_status',  name: 'download_status', header: '出力状況', minWidth: GRID_COL_MIN_WIDTH, width: 190, isReadOnly: true },
                    ]
                },
                // {
                //     header: '出力状況', cells: [
                //         { binding: 'download_status',  name: 'download_status', header: '出力状況', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                //     ]
                // },
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
        isKeyPressDeleteOrBackspace(){
            var result = false;
            if(event.keyCode == 46 || event.keyCode == 8){
                result = true;
            }
            return result;
        },
        // 以下オートコンプリートの値取得
        initSupplier: function(sender) {
            this.wjSearchObj.supplier_id = sender
        },
        initPaymentDate: function(sender){
            this.wjSearchObj.payment_date = sender;
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
.dialog-title {
    font-size: 25px;
    font-weight: 600;
}
.dialog-header {
    /* margin: 50px auto 20px;
	padding: 5px;
	width: 100%;
    height: 50px;
    margin: 50px auto 0px; */

    display: block;
    white-space: nowrap;
    font-size: 18px;
    padding: 1em;
    /* width: 100%; */
    background-color: #fff; /* 背景色 */
    border: 1px solid #ccc; /* 枠線 */
    box-sizing: inherit;
}
.dialog-section {
	overflow: scroll;
	/* margin: 50px auto 20px; */
	/* padding: 5px; */
	width: 100%;
	height: 350px;
	/* border: 2px solid #ccc; */
}
.sticky_table {
    overflow: scroll;
    width: 100%;
    white-space: nowrap;
    border-collapse: collapse;
    text-align: left;
    line-height: 1.5;
    /* border: 1px solid #ccc; */
    font-size: 14px;
}
.sticky_table th {
    padding: 10px;
    text-align: center;
    white-space: nowrap;
    color: #FFF;
    font-size: 16px;
    font-weight: bold;
    /* border-top: 1px solid #ccc;
    border-right: 1px solid #ccc;
    border-bottom: 2px solid #ccc; */
    /* border: 1px solid #787878; */
    /* background: #cee7ff; */
    background: #43425D;

    /* border-collapse: separate; */
    /* background-clip: padding-box; */

    /* 縦スクロール時に固定する */
    position: -webkit-sticky;
    position: sticky;
    top: 0;
    /* tbody内のセルより手前に表示する */
    z-index: 10;
}
.sticky_table th::before{
    content : "" ;
    position : absolute ;
    top : 0 ;
    left : 0 ;
    width : 100% ;
    height : 100% ;
    border-top: 0px;
    border : 1px solid #787878;
}
.sticky_table td {
    height: 80px;
    z-index: -1;
}
table {
    border-collapse: collapse;
}
.wj-flexgrid {
    height: 500px;
}
.dialog-autocomp {
    width: 100%;
}
.dialog-input {
    width: 100px;
}
.valid-text {
    color: #000;
}
.invalid-text {
    color: #a9a9a9;
}
.status {
    width: 100px;
    height: 30px;
    margin-top: 15px;
    padding-top: 5px;
    text-align: center;
} 
.not-approval {
    background: lightgray;
    color: #000;
}
.approvaled {
    background: #FFCC4E;
    color: #000;
}
.send-back {
    background: #FF3B30;
    color: #000;
}
.wj-red-color {
    background: #F596EE !important;
    color: #43425D !important;
}
.wj-green-color {
    background: #40B5B1 !important;
    color: #43425D !important;
}
.wj-yellow-color {
    background: #F1DF69 !important;
    color: #43425D !important;
}
.el-dialog__header {
    height: 80px;
    /* width: 95% !important; */
}
.el-dialog__body {
    height: 500px;
    /* width: 95% !important; */
}
.el-dialog__footer {
    height: 80px;
    /* width: 95% !important; */
}
.dialog-content {
    /* margin: 2em auto; */
    padding: 1em;
    /* width: 90%; */
    background-color: #fff; /* 背景色 */
    /* border: 1px solid #ccc; 枠線 */
}
.approval-dialog {
  max-width: inherit;
  width: 98%;
  margin-left: 15px;
}
.request-dialog {
    height: 200px !important;
}
.file-up{
    margin-bottom: 0px;
    vertical-align: bottom;
    width: 25px;
    height: 25px;
}
.filter-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
} 
</style>


