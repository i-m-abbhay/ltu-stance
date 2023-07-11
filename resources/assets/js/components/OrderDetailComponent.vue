<template>
    <div>
        <loading-component :loading="loading" />

        <el-dialog 
            titles="Tips"
            width="66%"
            :visible.sync="isShowAddMaterialsModal"
            :destroy-on-close="true"
        >
            <div class="dialog-body">
                <div class="row">
                    <div class="col-md-3">
                        <label class="control-label">工事区分</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="construction_id"
                            :selected-index="-1"
                            :is-required="false"
                            :initialized="initModalSearchConstruction"
                            :selectedIndexChanged="changeIdxModalSearchConstruction"
                            :items-source="qreqList"
                            :textChanged="setTextChanged"
                            :max-items="qreqList.length"
                            :min-length=1
                            :delay=50>
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label">階層名</label>
                        <input type="text" class="form-control" v-model="modal.parent_layer_name">
                    </div>
                    <div class="col-md-3">
                        <label class="control-label">品番</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="product_code"
                            display-member-path="product_code"
                            selected-value-path="product_id"
                            :selected-index="-1"
                            :is-required="false"
                            :initialized="initModalSearchProduct"
                            :selectedIndexChanged="changeIdxModalSearchProductCode"
                            :itemsSourceFunction="modalProductCodeItemsSouceFunction"
                            :min-length=1
                            :delay=50>
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3">
                        <label class="control-label">商品名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="product_name, product_short_name"
                            display-member-path="product_name"
                            selected-value-path="product_id"
                            :selected-index="-1"
                            :is-required="false"
                            :initialized="initModalSearchProduct"
                            :selectedIndexChanged="changeIdxModalSearchProductName"
                            :itemsSourceFunction="modalProductNameItemsSouceFunction"
                            :min-length=1
                            :delay=50>
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-2">
                        <label class="control-label">&nbsp;</label>
                        <div><el-checkbox v-model="modal.not_treated" :true-label="1" :false-label="0">未処理</el-checkbox></div>
                    </div>
                    <div class="col-md-2 col-md-offset-8">
                        <label class="control-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-search" @click="searchAddMaterialsModal">検索</button>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <div class="modal-grid" v-bind:id="'addMaterialsGrid'"></div>
                    </div>
                </div>
            </div>
            <div class="dialog-footer">
                <div class="row">
                    <div class="col-md-2 col-md-offset-5">
                        <label class="control-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-save" @click="showAddMaterialsModal(false)">閉じる</button>
                        </div>
                    </div>
                    <div class="col-md-2 col-md-offset-3">
                        <label class="control-label">&nbsp;</label>
                        <div>
                            <button type="button" class="btn btn-save" @click="addOrderDetailsGrid">追加</button>
                        </div>
                    </div>
                </div>
            </div>
        </el-dialog>

        <el-dialog title="子部品取込" :visible.sync="isShowChildPartsImportModal" :closeOnClickModal=false>
            <div class="col-md-12" @dragleave.prevent @dragover.prevent>
                <label id="childPartsImportFile" class="file_label col-md-6 col-sm-8">
                    <input type="file" id="importFile" class="file-upload-btn" :accept="childPartsImportFileInfo.accept" v-bind:disabled="isReadOnly" />
                    <span for="file">{{ childPartsImportFileInfo.label }}</span>
                </label> 
            </div>
            <div class="col-md-12">
                <p class="text-danger" style="margin-top:5px;" v-html="childPartsImportFileInfo.errorMsg"></p>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="childPartsImport" class="btn-import" v-bind:disabled="isProcessing()">取込</el-button>
                <el-button @click="isShowChildPartsImportModal=false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>

        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 text-right">
                        <label v-show="order.complete_flg == FLG_ON" class="attention-color">案件完了済&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                        <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked" v-bind:disabled="order.complete_flg == FLG_ON">ロック解除</button>
                        <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn" v-bind:disabled="order.complete_flg == FLG_ON">編集</button>
                        <div class="pull-right">
                            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn)">編集中</p>
                        </div>
                    </div>
                </div>
                <!-- 得意先名, 案件名, 案件番号, 現場住所(&住所登録ボタン) -->
                <div class="order-info">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="control-label">得意先名</label>
                            <input type="text" class="form-control" v-model="order.customer_name" :readonly="true">
                        </div>
                        
                        <div v-if="!isOwnStock">
                            <div class="col-md-2">
                                <label class="control-label">案件名</label>
                                <input type="text" class="form-control" v-model="order.matter_name" :readonly="true">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">案件番号</label>
                                <input type="text" class="form-control" v-model="order.matter_no" :readonly="true">
                            </div>
                            <div class="col-md-6">
                                <label class="control-label">現場住所</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" v-model="order.matter_address" :readonly="true">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-save form-control" v-on:click="inputMaterAddress" v-bind:disabled="(isReadOnly)">住所登録</button>
                                    </span>
                                    <span class="input-group-btn">
                                        <el-button icon="el-icon-refresh" circle class="input-group-circle-btn" v-bind:disabled="(isReadOnly)" v-on:click="reloadAddress"></el-button>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-2">
                            <label class="control-label">発注番号</label>
                            <input type="text" class="form-control" v-model="order.order_no" :readonly="true">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">入荷先</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="warehouse_name"
                                display-member-path="warehouse_name"
                                selected-value-path="unique_key"
                                :isDisabled="isReadOnly"
                                :initialized="initDeliveryAddress"
                                :selectedIndexChanged="changeIdxDeliveryAddress"
                                :selected-index="-1"
                                :selected-value="order.delivery_address_kbn + '_' + order.delivery_address_id"
                                :is-required="false"
                                :textChanged="setTextChanged"
                                :items-source="deliveryAddressList"
                                :max-items="deliveryAddressList.length">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-5" v-bind:class="{'has-error': (errors['delivery_address'] != '') }">
                            <label class="control-label">入荷先住所</label>
                            <input type="text" class="form-control" v-model="order.delivery_address" :readonly="true">
                            <p class="text-danger">{{ errors['delivery_address'] }}</p>
                        </div>
                        <div class="col-md-2">
                            <label class="control-label">地図印刷</label>
                            <div class="">
                                <el-checkbox v-model="order.map_print_flg" :disabled="isReadOnly" :true-label="1" :false-label="0">入荷先地図を発注時に印刷</el-checkbox>
                            </div>
                        </div>
                    </div>
                    <!-- メーカー/仕入先, メーカー/仕入先担当者, 使用口座得意先名, 使用口座施主名 -->
                    <div class="row">
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="control-label">メーカー</label>
                                    <input type="text" class="form-control" v-model="order.maker_name" :readonly="true">
                                </div>
                                <div class="col-md-4">
                                    <label class="control-label">仕入先</label>
                                    <input type="text" class="form-control" v-model="order.supplier_name" :readonly="true">
                                </div>
                                <div class="col-md-4">
                                    <label class="control-label">メーカー／仕入先担当者</label>
                                    <wj-auto-complete id="acPerson" class="form-control"
                                        search-member-path="name"
                                        display-member-path="name"
                                        selected-value-path="id"
                                        :isDisabled="isReadOnly"
                                        :initialized="initPerson"
                                        :selected-index="-1"
                                        :selected-value="order.person_id"
                                        :is-required="false"
                                        :items-source="personList"
                                        :textChanged="setTextChanged"
                                        :max-items="personList.length"
                                        >
                                    </wj-auto-complete>
                                </div>
                            </div>
                            <!-- 納期希望日, 発注登録日, 発注承認日, 発注日, 発注修正日, 保存ボタン -->
                            <div class="row">
                                <div class="col-md-4">
                                    <label class="control-label">納期希望日</label>
                                    <wj-input-date
                                        class="form-control"
                                        :value="order.desired_delivery_date"
                                        :initialized="initDesiredDeliveryDate"
                                        :isRequired=false
                                        :isDisabled="isReadOnly"
                                    ></wj-input-date>
                                </div>
                                <div v-if="!isOwnStock">
                                    <div class="col-md-4">
                                        <label for="accountCustomerName" class="inp" style="margin-top:29px">
                                            <input type="text" id="accountCustomerName" class="form-control" placeholder=" " v-model="order.account_customer_name" v-bind:disabled="isReadOnly">
                                            <span for="accountCustomerName" class="label">使用口座先得意先名</span>
                                            <span class="border"></span>
                                        </label>
                                    </div>
                                    <div class="col-md-4">
                                        <label for="accountOwnerName" class="inp" style="margin-top:29px">
                                            <input type="text" id="accountOwnerName" class="form-control" placeholder=" " v-model="order.account_owner_name" v-bind:disabled="isReadOnly">
                                            <span for="accountOwnerName" class="label">使用口座先施主名</span>
                                            <span class="border"></span>
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3">
                                    <label class="control-label">発注登録日</label>
                                    <input type="text" class="form-control" v-model="order.order_apply_datetime" :readonly="true">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">発注日</label>
                                    <input type="text" class="form-control" v-model="order.order_datetime" :readonly="true">
                                </div>
                                <div class="col-md-3">
                                    <label class="control-label">発注後修正日</label>
                                    <input type="text" class="form-control" v-model="order.update_at" :readonly="true">
                                </div>
                                <div class="col-md-offset-2 col-md-1">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <button type="button" class="btn btn-primary btn-save" @click="save" v-bind:disabled="isReadOnly">保存</button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-md-3">
                            <div class="row">
                                <div class="col-md-12" v-bind:class="{'has-error': (errors['upload_file_list'] != '') }">
                                    <label class="control-label">明細</label>
                                    <label class="file-up" for="file-up">
                                        <svg class="svg-icon"><use width="25" height="25" xlink:href="#clipIcon"/></svg>
                                    </label>
                                    <input style="display:none;" type="file" multiple="multiple" @change="onDrop" id="file-up" v-bind:disabled="isReadOnly">
                                    <div class="file-operation-area well well-sm" style="height:180px;" @dragleave.prevent @dragover.prevent @drop.prevent="onDrop">
                                        <ul class="detail-file-ul">
                                            <li v-for="(file, key) in uploadFileList" :key="key" class="detail-file-li">
                                                <span>{{file.name}}</span>                                              
                                                <el-button type="danger" icon="el-icon-delete" circle size="mini" @click="fileDelete(key)" v-bind:disabled="isReadOnly"></el-button>
                                                <el-button type="success" icon="el-icon-download" circle size="mini" v-show="file.storage_file_name" class="btn-file-download" @click="fileDownLoad(file.storage_file_name)"></el-button>
                                            </li>
                                        </ul>
                                    </div>
                                    <p class="text-danger">{{ errors['upload_file_list'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- グリッド -->
                    <div class="container-fluid grid-area">
                        <div class="row">
                            <div class="col-md-3">
                                <label class="control-label">検索</label>
                                <div class="input-group">
                                    <div class="input-group-addon">
                                        <span class="glyphicon glyphicon-search"></span>
                                    </div>
                                    <el-input v-model="filterText" @input="filterGrid()" v-bind:disabled="isReadOnly"></el-input>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">希望出荷予定日一括入力</label>
                                <div class="input-group">
                                    <wj-input-date class="form-control"
                                        :value=null
                                        :isRequired=false
                                        :initialized="initHopeArrivalPlanDate"
                                        :isDisabled="isReadOnly"
                                    ></wj-input-date>
                                    <span class="input-group-btn">
                                        <el-button type="primary" v-bind:disabled="isReadOnly" plain @click="rewriteGridDate('hope_arrival_plan_date')">適用</el-button>
                                    </span>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">入荷予定日一括入力</label>
                                <div class="input-group">
                                    <wj-input-date class="form-control"
                                        :value=null
                                        :isRequired=false
                                        :initialized="initArrivalPlanDate"
                                        :isDisabled="isReadOnly"
                                    ></wj-input-date>
                                    <span class="input-group-btn">
                                        <el-button type="primary" v-bind:disabled="(isReadOnly || this.order.status !== this.orderStatusList.val.ordered)" plain @click="rewriteGridDate('arrival_plan_date')">適用</el-button>
                                    </span>
                                </div>
                            </div>
                            <div v-if="!isOwnStock">
                                <div class="col-md-1">
                                    <label class="control-label">&nbsp;</label>
                                    <div>
                                        <el-button type="primary" @click="showAddMaterialsModal(true)" v-bind:disabled="isReadOnly">商材追加</el-button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <label class="control-label">&nbsp;</label>
                                    <div style="">
                                        <el-button type="success" @click="showChildPartsImport" v-bind:disabled="(isReadOnly || this.order.status !== this.orderStatusList.val.ordered)">子部品追加</el-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12">
                                <div v-bind:id="'orderDetailGrid'"></div>
                            </div>
                            <div class="col-md-12 col-sm-12">
                                <div class="col-md-4 col-sm-12">
                                    <label class="control-label">営業支援コメント</label>
                                    <textarea class="col-md-12 col-sm-8 form-control" v-model="order.sales_support_comment" v-bind:readonly="isReadOnly"></textarea>
                                </div>
                                <div class="col-md-4 col-sm-12">
                                    <label class="control-label">メーカー／仕入先宛コメント</label>
                                    <textarea class="col-md-12 col-sm-8 form-control" v-model="order.supplier_comment" v-bind:readonly="isReadOnly"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
        <!-- ボタン -->
        <div class="col-md-12">
            <button type="button" class="btn btn-warning btn-back pull-right" @click="back">戻る</button>
        </div>
    </div>
</template>

<script>

import * as wjCore from '@grapecity/wijmo';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import * as wjcInput from '@grapecity/wijmo.input';
import { CustomGridEditor } from '../CustomGridEditor.js';
import { head } from 'lodash';

export default {
    data: () => ({

        todayYYYYMMDD: 0,

        INIT_ROW: {
            chk: false,
            fix: null,
            order_cancel: null,
            hope_arrival_plan_date: '',
            arrival_plan_date: '',
            product_code: '',
            btn_no_product_code: '',
            product_name: '',
            model: '',
            maker_name: '',
            supplier_name: '',
            order_quantity: 0.0,
            unit: '',
            stock_quantity: 0.0,
            stock_unit: '',
            regular_price: 0,
            cost_kbn: 0,
            cost_unit_price: 0,
            sales_unit_price: 0,
            cost_makeup_rate: 0.00,
            sales_makeup_rate: 0.00,
            cost_total: 0,
            sales_total: 0,
            gross_profit_rate: 0.00,
            profit_total: 0,
            memo: '',
            product_auto_flg: false,
            product_definitive_flg: false,

            id: 0,
            quote_detail_id: 0,
            product_id: 0,
            maker_id: 0,
            supplier_id: 0,
            add_kbn: 2,
            is_add: true,
            is_delete: false,

            construction_id:0,
            parent_quote_detail_id: 0,
            layer_flg: 0,
            tree_path: '',
            set_flg: 0,
            min_quantity: 0.01,
            order_lot_quantity: 0.01,
            quote_quantity: 0,
            reserve_quantity: 0,
            sales_kbn: 2,
            intangible_flg: 1,
            draft_flg:1,
            child_parts_flg: 0,
            sales_use_flg: 0,
            unique_key: 0,

            quantity_per_case: 0.0, // 仮登録
            set_kbn: '',            // 仮登録
            class_big_id: 0,        // 仮登録
            class_middle_id: 0,     // 仮登録
            class_small_id: 0,      // 仮登録
            tree_species: 0,        // 仮登録
            grade: 0,               // 仮登録
            length: 0,              // 仮登録
            thickness: 0,           // 仮登録
            width: 0,               // 仮登録
        },

        INIT_ROW_MIN_QUANTITY: 1.00,

        loading: false,
        isReadOnly: true,
        isShowEditBtn: true,
        isLocked: false,

        isOwnStock: false,
        isShowAddMaterialsModal: false,
        isShowChildPartsImportModal: false,
        
        uploadFileList : [],
        deleteFileList : [],

        errors: {
            delivery_address: '',
            upload_file_list: '',
        },

        order: {},
        lock: {},

        gridLayout: null,
        gridLayoutModal: null,

        filterText: '',

        wjDeliveryAddress: null,
        wjPerson: null,
        wjDesiredDeliveryDate: null,
        wjHopeArrivalPlanDate: null,
        wjArrivalPlanDate: null,
        wjGridDetails: null,
        wjGridCustomItem: {
            hope_arrival_plan_date: null,
            arrival_plan_date: null,
            product_code: null,
            product_name: null,
            maker_name: null,
            supplier_name: null,
        },

        // 非表示カラム
        INVISIBLE_COLS: [
            'id',
            'quote_detail_id',
            'product_id',
            'maker_id',
            'supplier_id',
            'add_kbn',
            'is_add',
            'is_delete',

            'construction_id',
            'parent_quote_detail_id',
            'layer_flg',
            'tree_path',
            'set_flg',
            'min_quantity',
            'order_lot_quantity',
            'quote_quantity',
            'reserve_quantity',
            'sales_kbn',
            'intangible_flg',
            'draft_flg',
            'child_parts_flg',
            'sales_use_flg',
            'unique_key',

            'quantity_per_case',
            'set_kbn',
            // 'class_big_id',
            // 'class_middle_id',
            'class_small_id',
            'tree_species',
            'grade',
            'length',
            'thickness',
            'width',
        ],

        // 以下モーダル
        modal: {
            grid: null,
            construction: null,
            parent_layer_name: null,
            product_code: null,
            product_name: null,
            not_treated: 1,
        },
        // modalMaterialList: [{
        //     construction: null,
        //     product_code: null,
        //     product_name: null,
        //     order_quantity: null,
        // }],
        childPartsImportFileInfo: {
            accept: '.csv',
            label: LBL_FILE,
            content: null,
            errorMsg: '',
            DISPLAY_ERR_CNT: 10,
            ERR_MSG_REQUIRED: '$n行目：「$key」は必須です',
            ERR_MSG_MULTIPLE: '$n行目：「$key1」が「$key2」の倍数になっていません',
            ERR_MSG_COL_NOT_FOUND: '列がありません',
            ERR_MSG_FILE_CONFIRM: 'ファイルを確認してください',
            COL_NAME_LIST : {
                desired_delivery_date: '発注者希望納期',    // ヘッダー：納期希望日
                arrival_plan_date: '届け日',                // 入荷予定日
                product_code:   '品番',                     // 商品コード
                product_name:   '品名',                     // 商品名 + ' ' + 規格
                model:          '規格',                     // 規格(商品名と合体)
                order_quantity: '数量',                     // 発注数
                unit:           '単位',                     // 単位
                regular_price:  '定価単価',                 // 定価
                cost_unit_price:'発注単価',                 // 仕入単価
                memo:           '摘要／備考',               // 備考(備考2と合体)
                memo2:          '摘要2／備考2',             // 備考2 + '   ' + 備考
                min_quantity:   '最小単位数',               // 最小単位数
                stock_unit:     '管理数単位',               // 管理数単位
                order_lot_quantity:     '発注ロット数',     // 発注ロット数
            },
            // COL_NUMBER_LIST: {
            //     desired_delivery_date: 0,
            //     arrival_plan_date: 0,
            //     product_code:   0,
            //     product_name:   0,
            //     model:          0,
            //     order_quantity: 0,
            //     unit:           0,
            //     regular_price:  0,
            //     cost_unit_price:0,
            //     memo:           0,
            //     memo2:          0,
            //     min_quantity:   0,
            //     stock_unit:     0,
            //     order_lot_quantity:     0,
            // }
        },
        INVISIBLE_COLS_MODAL: [
            'quote_detail_id',
            'product_id',
            'unit',
            'model',
            'regular_price',
            'cost_unit_price',
            'cost_makeup_rate',
            'sales_unit_price',
            'sales_makeup_rate',
            'cost_kbn',
            'sales_kbn',
            'supplier_id',
            'add_kbn',
            'order_quantity',
            'construction_id',
            'parent_quote_detail_id',
            'tree_path',
            'set_flg',
            'min_quantity',
            'order_lot_quantity',
            'intangible_flg',
            'draft_flg',
            'sales_use_flg',
            
            'quantity_per_case',
            'set_kbn',
            'class_big_id',
            'class_middle_id',
            'class_small_id',
            'tree_species',
            'grade',
            'length',
            'thickness',
            'width',
        ],


        tooltip : new wjCore.Tooltip(),
    }),
    props: {
        orderData: {},
        orderDetailData: {},
        setProductList: {},
        orderDetailLogs: {},
        isOwnLock: Number,
        lockdata: {},
        qreqList: Array,
        personList: Array,
        //makerProductList: Array,
        supplierList: Array,
        classBigList: Array,
        classMiddleList: Array,
        priceList: Array,
        noProductCode: {},
        deliveryAddressList: Array,
        orderFileNameList: Array,
        addKbnList: {},
        orderStatusList: {},
        childPartsList: {},
    },
    watch: {
        // 子部品取込ダイアログ
        isShowChildPartsImportModal: function (newItem, oldItem) {
            if (newItem) {
                this.childPartsImportFileInfo.errorMsg = '';
                this.childPartsImportFileInfo.label = LBL_FILE;
                this.childPartsImportFileInfo.content = null;

                this.$nextTick(function() {
                    document.getElementById('importFile').value = '';
                    document.getElementById('childPartsImportFile').addEventListener('drop', () => {
                        event.preventDefault();
                        let fileList = event.target.files ? event.target.files:event.dataTransfer.files;
                        if(fileList.length === 1){
                            this.childPartsImportFileInfo.content = fileList[0];
                            this.childPartsImportFileInfo.label = fileList[0].name;
                        }else{
                            this.childPartsImportFileInfo.label = LBL_FILE;
                            this.childPartsImportFileInfo.content = null;
                        }
                    });
                    document.getElementById('importFile').addEventListener('change', () => {
                        let fileList = event.target.files ? event.target.files:event.dataTransfer.files;
                        if(fileList.length === 1){
                            this.childPartsImportFileInfo.content = fileList[0];
                            this.childPartsImportFileInfo.label = fileList[0].name;
                        }else{
                            this.childPartsImportFileInfo.label = LBL_FILE;
                            this.childPartsImportFileInfo.content = null;
                        }
                    });
                }.bind(this));         
            }
        }
    },
    created: function() {
        // propsで受け取った値をローカル変数に入れる
        this.order = this.orderData;
        this.lock = this.lockdata;
        
        // 大分類リスト　先頭に未選択を追加
        this.classBigList.unshift({class_big_id:0, class_big_name:''});
        // 中分類リスト　先頭に未選択を追加
        this.classMiddleList.unshift({class_middle_id:0, class_middle_name:'', class_big_id:0});

        // ロック中判定
        if (this.rmUndefinedZero(this.lock.id) != 0 && this.isOwnLock === this.FLG_OFF) {
            this.isLocked = true;
        }
        
        // (グリッド)レイアウトセット
        this.gridLayout = this.getGridLayout();
        this.gridLayoutModal = this.getAddMaterialsGridLayout();
        this.todayYYYYMMDDSlashFormat = this.getTodaySlashFormat();
        this.isOwnStock = this.order.own_stock_flg === this.FLG_ON;
    },
    mounted() {
        if (this.isLocked) {
            // ロック中
            this.isShowEditBtn = false;
        } else {
            // 編集モードで開くか判定
            var query = window.location.search;
            
            if (this.isOwnLock === this.FLG_ON) {
                this.isReadOnly = false
                this.isShowEditBtn = false
            }
        }

        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        this.uploadFileList = this.orderFileNameList;

        // 編集時に来る
        var gridData = [];
        for (var i in this.orderDetailData) {
            var rec = this.orderDetailData[i];
            
            // 選択チェックボックス
            rec['chk'] = false;
            rec['unique_key'] = parseInt(i);

            rec['arrival_plan_date']        = this.rmUndefinedBlank(rec['arrival_plan_date']);
            rec['hope_arrival_plan_date']   = this.rmUndefinedBlank(rec['hope_arrival_plan_date']);

            // decimal型は文字列になっているので数値キャスト
            rec['quote_quantity']       = parseFloat(rec['quote_quantity']);    // 見積数
            rec['order_quantity']       = parseFloat(rec['order_quantity']);    // 発注数
            rec['order_lot_quantity']   = parseFloat(rec['order_lot_quantity']);// 発注ロット数
            rec['reserve_quantity']     = parseFloat(rec['reserve_quantity']);  // 引当数
            rec['regular_price']        = parseInt(rec['regular_price']);       // 定価
            rec['cost_unit_price']      = parseInt(rec['cost_unit_price']);     // 仕入単価
            rec['sales_unit_price']     = parseInt(rec['sales_unit_price']);    // 販売単価
            rec['cost_total']           = parseInt(rec['cost_total']);          // 仕入総額
            rec['sales_total']          = parseInt(rec['sales_total']);         // 販売総額
            rec['profit_total']         = parseFloat(rec['profit_total']);      // 粗利総額
            rec['cost_makeup_rate']     = parseFloat(rec['cost_makeup_rate']);  // 仕入掛率
            rec['sales_makeup_rate']    = parseFloat(rec['sales_makeup_rate']); // 販売掛率
            rec['gross_profit_rate']    = parseFloat(rec['gross_profit_rate']); // 粗利率
            rec['draft_flg']            = parseInt(this.rmUndefinedZero(rec['draft_flg'])); // 仮登録フラグ
            rec['sales_use_flg']        = parseInt(rec['sales_use_flg']);       // 販売額利用フラグ
            // rec['product_auto_flg']     = rec['child_parts_flg'] === this.FLG_ON;   // 本登録フラグ
            // 1回限り登録チェック
            if (rec['auto_flg'] === this.FLG_ON) {
                // 1回限り登録商品は初期表示時にチェック済みにする
                rec['product_auto_flg'] = true;
            } else {
                // 子部品には1回限り登録にチェックを入れる
                rec['product_auto_flg'] = rec['child_parts_flg'] === this.FLG_ON;
            }
            // 本登録チェック
            rec['product_definitive_flg'] = false;

            rec['add_kbn']              = parseInt(this.rmUndefinedZero(rec['add_kbn']));   // 追加区分
            rec['is_add'] = false;
            rec['is_delete'] = false;

            // 最小単位数量
            rec['min_quantity'] = parseFloat(this.rmUndefinedZero(rec['min_quantity']));
            if(this.bigNumberEq(rec['min_quantity'], 0)){
                if(this.rmUndefinedBlank(rec['product_code']) !== ''){
                    rec['min_quantity'] = this.INIT_ROW_MIN_QUANTITY;
                }else{
                    rec['min_quantity'] = this.INIT_ROW.min_quantity;
                }
            }

            // 発注ロット数
            rec['order_lot_quantity'] = this.rmUndefinedZero(rec['order_lot_quantity']);
            if(!this.bigNumberEq(rec['order_lot_quantity'], 0)){
                rec['order_lot_quantity'] = parseFloat(rec['order_lot_quantity']);
            }else{
                rec['order_lot_quantity'] = rec['min_quantity'];
            }

            // 無形品フラグ
            if(rec['intangible_flg'] === null){
                // 商品マスタに紐づいていないデータの場合
                if(this.rmUndefinedZero(rec['layer_flg']) === this.FLG_ON){
                    // 階層は無形品(未使用)
                    rec['intangible_flg'] = this.FLG_ON;
                }else if(this.rmUndefinedBlank(rec['product_code']) === ''){
                    // 商品コードが空の場合は無形品として扱う
                    rec['intangible_flg'] = this.FLG_ON;
                }else{
                    // 商品コードが入っていれば有形品として扱う
                    rec['intangible_flg'] = this.FLG_OFF;
                }
            }

            gridData.push(rec);
        }
        this.wjGridDetails = this.createGridCtrl('#orderDetailGrid', new wjCore.CollectionView(gridData));
        
    },
    methods: {
        // 初期処理、イベント等
        initDeliveryAddress: function(sender){
            this.wjDeliveryAddress = sender;
            if (sender.selectedItem) {
                this.order.delivery_address = sender.selectedItem.address;
            }else{
                this.order.delivery_address = '';
            }
        },
        changeIdxDeliveryAddress: function(sender){
            if (sender.selectedItem) {
                this.order.delivery_address = sender.selectedItem.address;
            }else{
                this.order.delivery_address = '';
            }
        },
        initPerson: function(sender){
            this.wjPerson = sender;
        },
        initDesiredDeliveryDate: function(sender){
            this.wjDesiredDeliveryDate = sender;
        },
        initHopeArrivalPlanDate: function(sender){
            this.wjHopeArrivalPlanDate = sender;
        },
        initArrivalPlanDate: function(sender){
            this.wjArrivalPlanDate = sender;
        },
        // グリッドフィルター
        filterGrid() {
            var filter = this.filterText.toLowerCase();
            this.wjGridDetails.collectionView.filter = detail => {
                var result = false;
                // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                if(!detail.is_delete){
                    var costKbn = this.priceList.find((rec) => {
                        return (rec.value_code === detail.cost_kbn);
                    });
                    result =
                    (filter.length == 0) ||
                    (detail.hope_arrival_plan_date != null && (detail.hope_arrival_plan_date).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.arrival_plan_date != null && (detail.arrival_plan_date).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.product_code != null && (detail.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.product_name != null && (detail.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.model != null && (detail.model).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.maker_name != null && (detail.maker_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.supplier_name != null && (detail.supplier_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    //(detail.reserve_quantity != null && (detail.reserve_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.order_quantity != null && (detail.order_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.unit != null && (detail.unit).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.stock_quantity != null && (detail.stock_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.stock_unit != null && (detail.stock_unit).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.regular_price != null && (detail.regular_price).toString().toLowerCase().indexOf(filter) > -1) ||
                    (costKbn != null && (costKbn.value_text_1).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.cost_unit_price != null && (detail.cost_unit_price).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.sales_unit_price != null && (detail.sales_unit_price).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.cost_makeup_rate != null && (detail.cost_makeup_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.sales_makeup_rate != null && (detail.sales_makeup_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.cost_total != null && (detail.cost_total).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.sales_total != null && (detail.sales_total).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.gross_profit_rate != null && (detail.gross_profit_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.profit_total != null && (detail.profit_total).toString().toLowerCase().indexOf(filter) > -1) ||
                    (detail.memo != null && (detail.memo).toString().toLowerCase().indexOf(filter) > -1)
                }
                return result;
            };
        },
        // 案件住所入力
        inputMaterAddress(){
            var matterId = this. order.matter_id;
            var link = '/address-edit/' + matterId;
            window.open(link, '_blank')
        },
        // 現場住所変更
        reloadAddress() {
            var params = new URLSearchParams();

            // 得意先ID
            params.append('matter_id', this.rmUndefinedZero(this.order.matter_id));
            params.append('supplier_id', this.rmUndefinedZero(this.order.supplier_id));
            axios.post('/order-detail/matter-address/get', params)
            .then( function (response) {
                if (response.data) {
                    if(this.wjDeliveryAddress.selectedItem !== null){
                        var selectedValue = this.wjDeliveryAddress.selectedItem.delivery_address_kbn + '_' + this.wjDeliveryAddress.selectedItem.id;
                        this.wjDeliveryAddress.itemsSource = response.data.delivery_address;
                        this.wjDeliveryAddress.selectedValue = selectedValue;
                    }else{
                        this.wjDeliveryAddress.itemsSource = response.data.delivery_address;
                    }

                    var address = response.data.matter_address;
                    this.order.matter_address_id = this.rmUndefinedZero(address.id);
                    this.order.matter_address = this.rmUndefinedBlank(address.address1) + this.rmUndefinedBlank(address.address2);
                }
            }.bind(this))
            .catch(function (error) {
            }.bind(this))
        },
        // チェックしている行の希望出荷予定日、入荷予定日を書き換える
        rewriteGridDate(colName) {
            this.wjGridDetails.beginUpdate();
            var gridDataList = this.getVisibleChkDataList();
            switch(colName){
                case 'hope_arrival_plan_date':
                    for (var i in gridDataList) {
                        if (gridDataList[i].chk) {
                            if(this.isHopeArrivalDateValid(gridDataList[i])){
                                gridDataList[i][colName] = this.wjHopeArrivalPlanDate.text;
                            }
                        }
                    }
                    break;
                case 'arrival_plan_date':
                    var arrivalPlanDate = this.rmUndefinedBlank(this.wjArrivalPlanDate.text);
                    // 入荷予定日の入力可能範囲は制限なしに修正
                    // if(arrivalPlanDate !== '' && this.strToTime(arrivalPlanDate) <= this.strToTime(this.todayYYYYMMDDSlashFormat)){
                    //     alert(MSG_ERROR_ORDER_ARRIVAL_PLAN_DATE);
                    // }else{
                        var hopeArrivalPlanDate = '';
                        if(arrivalPlanDate !== ''){
                            hopeArrivalPlanDate = this.getNextDay(arrivalPlanDate);
                        }
                        for (var i in gridDataList) {
                            if (gridDataList[i].chk) {
                                if(this.isArrivalDateValid(gridDataList[i])){
                                    gridDataList[i][colName] = arrivalPlanDate;
                                    if(this.rmUndefinedBlank(gridDataList[i]['hope_arrival_plan_date']) === '' && this.rmUndefinedBlank(hopeArrivalPlanDate) !== ''){
                                        if(this.isHopeArrivalDateValid(gridDataList[i])){
                                            // 希望出荷予定日が空欄の場合、希望出荷予定日に入荷予定日の翌日をセット
                                            gridDataList[i]['hope_arrival_plan_date'] = hopeArrivalPlanDate;
                                        }
                                    }
                                }
                            }
                        }
                    // }
                    break;
            }
            this.wjGridDetails.endUpdate();
        },
        // 商材追加モーダル表示
        showAddMaterialsModal(isShow) {
            this.isShowAddMaterialsModal = isShow;
            this.$nextTick(function() {
                if(isShow){
                    this.modal.grid = this.createAddMaterialsGridCtrl('#addMaterialsGrid', null);
                    this.settingModalGrid(this.modal.grid);
                }else{
                    this.modal.grid.dispose();
                    this.modal.grid = null;
                }
            });

        },
        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayout,
                showSort: false,
                allowSorting: false,
                autoClipboard: false,
            });

            gridCtrl.isReadOnly = this.isReadOnly;
            // 行ヘッダ非表示
            gridCtrl.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 列設定
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS.indexOf(element.name) >= 0) {
                    element.visible = false;
                }else if(this.isOwnStock 
                    && (element.name === 'product_auto_flg' || element.name === 'product_definitive_flg'
                    || element.name === 'class_big_id' || element.name === 'class_middle_id')){
                    // 自社在庫の場合は常に1回限り登録フラグ・本登録フラグ・大分類・中分類は非表示
                    element.visible = false;
                }
            });

            // セル内カスタマイズ
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                var colName = this.wjGridDetails.getBindingColumn(panel, r, c).name;
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    // 列ヘッダのセンタリング
                    cell.style.textAlign = 'center';

                    switch(colName){
                        case 'order_cancel':
                            cell.innerHTML = '発注<br/>取消';
                            break;
                        case 'chk':
                            var checkedCount = 0;
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                            }

                            // ヘッダ部にチェックボックス追加
                            var checkBox = '<input type="checkbox">';
                            if(this.isReadOnly){
                                checkBox = '<input type="checkbox" disabled="true">';
                            }
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
                        case 'product_auto_flg':
                            // 1回限り登録チェックボックス生成
                            var checkedCount = 0;
                            
                            var childPartsCnt = 0;
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (this.rmUndefinedBlank(gridCtrl.rows[i].dataItem) != '') {
                                    if(gridCtrl.rows[i].dataItem.child_parts_flg === this.FLG_ON){
                                        // 子部品は常にチェックが付いている
                                        childPartsCnt++;
                                    }else if (gridCtrl.rows[i].dataItem.product_auto_flg) {
                                        // 子部品を除外したチェックの数
                                        checkedCount++;
                                    }
                                }
                            }

                            var checkBox = '<input type="checkbox">1回限り登録';
                            if(this.isReadOnly){
                                checkBox = '<input type="checkbox" disabled="true">1回限り登録';
                            }
                            cell.innerHTML = checkBox;
                            var checkBox = cell.firstChild;
                            checkBox.checked = checkedCount > 0;
                            checkBox.indeterminate = checkedCount > 0 && checkedCount < (gridCtrl.rows.length - childPartsCnt);

                            checkBox.addEventListener('click', function (e) {
                                gridCtrl.beginUpdate();
                                for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                    // 全チェック外しの場合に、子部品のチェックボックスは変更しない
                                    if(gridCtrl.collectionView.items[i].child_parts_flg === this.FLG_OFF){
                                        gridCtrl.collectionView.items[i].product_auto_flg = checkBox.checked;
                                    }
                                }
                                gridCtrl.endUpdate();
                            }.bind(this));
                            break;
                        case 'product_definitive_flg':
                            // 本登録チェックボックス生成
                            var checkedCount = 0;
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                            }

                            var checkBox = '<input type="checkbox">本登録';
                            if(this.isReadOnly){
                                checkBox = '<input type="checkbox" disabled="true">本登録';
                            }
                            cell.innerHTML = checkBox;
                            var checkBox = cell.firstChild;
                            checkBox.checked = checkedCount > 0;
                            checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                            checkBox.addEventListener('click', function (e) {
                                gridCtrl.beginUpdate();
                                for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                    gridCtrl.collectionView.items[i].product_definitive_flg = checkBox.checked;
                                }
                                gridCtrl.endUpdate();
                            }.bind(this));
                            break;
                    }
                }

                if (panel.cellType == wjGrid.CellType.Cell) {
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);

                    var colName = this.wjGridDetails.getBindingColumn(panel, r, c).name;
                    var dataItem = panel.rows[r].dataItem;
                    var orderDetailId = dataItem.id;
                    var disabled = this.isReadOnly?'disabled':'';

                    // スタイルリセット
                    cell.style.color = '';
                    cell.style.textAlign = '';
                    cell.style.textAlign = 'left';
                    if(cell.classList.contains('text-right')){
                        cell.style.textAlign = 'right';
                    }

                    // 修正履歴のあるセルはフォントカラー変更
                    if (this.orderDetailLogs[orderDetailId] && this.orderDetailLogs[orderDetailId].fix_columns.indexOf(colName) != -1) {
                        cell.style.color = '#FF0000';
                    }
                    if(dataItem !== undefined){
                        // 販売額利用 or 一式の場合の行の色変更
                        if(dataItem.set_flg === this.FLG_ON || dataItem.sales_use_flg === this.FLG_ON){
                            if(cell.style.backgroundColor !== 'lightgrey'){
                                cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.SALES_USE_ROW;
                            }
                        }else if(dataItem.child_parts_flg === this.FLG_ON){
                            if(cell.style.backgroundColor !== 'lightgrey'){
                                cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.CHILD_PARTS_ROW;
                            }
                        }
                    }

                    switch (colName) {
                        case 'fix':
                            if(dataItem !== undefined){
                                // 修正
                                cell.style.backgroundColor = '';    // 背景色リセット
                                var fixLog = document.createElement('div');
                                fixLog.id = 'grid_fixlog_cell_' + orderDetailId;
                                fixLog.classList.add('grid-fixlog-cell', 'disabled');
                                if (this.orderDetailLogs[orderDetailId] !== undefined) {
                                    const fixLogs = this.orderDetailLogs[orderDetailId].fix_logs;

                                    fixLog.innerText = '修正有';
                                    fixLog.classList.remove('disabled');
                                    
                                    var fixLogContent = '';
                                    fixLogs.forEach(element => {
                                        fixLogContent += element.date + '&emsp;';
                                        fixLogContent += element.name + '&emsp;';
                                        element.columns.forEach(column => {
                                            fixLogContent += column + '&nbsp;'
                                        });
                                        fixLogContent += '<br>'
                                    });
                                    this.$nextTick(function() {
                                        this.tooltip.setTooltip(
                                            '#' + fixLog.id,
                                            fixLogContent
                                        )
                                    });
                                }
                                cell.appendChild(fixLog);
                                // 追注
                                var addOrder = document.createElement('div');
                                addOrder.classList.add('grid-addorder-cell', 'disabled');
                                if (panel.rows[r].dataItem.add_kbn === this.addKbnList.val.order_detail) {
                                    addOrder.innerText = '追注';
                                    addOrder.classList.remove('disabled')
                                }
                                cell.appendChild(addOrder);
                            }
                            break;
                        case 'order_cancel':
                            if(dataItem !== undefined){
                                cell.style.backgroundColor = '';    // 背景色リセット
                                cell.style.padding = '0px';
                                var orderCancelBtn = document.createElement('button');
                                orderCancelBtn.type = 'button';
                                orderCancelBtn.innerHTML = '取消';
                                orderCancelBtn.classList.add('btn-delete', 'ex');
                                if (this.isReadOnly || dataItem.layer_flg === this.FLG_ON) {
                                    orderCancelBtn.classList.add('disabled');
                                }
                                cell.appendChild(orderCancelBtn);
                            }
                            break;
                        case 'btn_no_product_code':
                            cell.style.padding = '0px';
                            if(this.isOwnStock){
                               disabled = disabled === ''?'disabled':disabled;
                            }
                            cell.innerHTML = '<button type="button" class="btn btn-default single-grid-btn-sm" '+disabled+'><i class="el-icon-caret-left"></i></button>';
                            break;
                        case 'chk':                     // チェック
                            cell.style.textAlign = 'center';
                            break;
                        case 'order_quantity':          // 発注数
                        case 'regular_price':           // 定価
                        case 'cost_unit_price':         // 仕入単価
                        case 'cost_makeup_rate':        // 仕入掛率
                        case 'cost_kbn':                // 仕入区分
                            if(dataItem !== undefined){
                                if(dataItem.layer_flg === this.FLG_ON){
                                    cell.style.backgroundColor = 'lightgrey';
                                }
                            }
                            break;
                        case 'stock_quantity':
                            if(dataItem !== undefined){
                                // 無形品フラグがたっておる場合、管理数をブランクにする
                                if (dataItem.intangible_flg === this.FLG_ON) {
                                    cell.innerHTML = '';   
                                }   
                            }
                            break;
                        case 'product_auto_flg':                     // 1回限り登録  TODO:後で確認する
                            cell.style.textAlign = 'center';
                            if(dataItem !== undefined){
                                if(dataItem.child_parts_flg === this.FLG_ON){
                                    cell.childNodes[0].disabled = true;
                                }
                                if(dataItem.layer_flg !== this.FLG_ON && this.rmUndefinedZero(dataItem.product_id) === 0){
                                    cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.PRODUCT_AUTO_CELL;
                                }
                            }
                            break;
                        case 'product_definitive_flg':               // 本登録
                            cell.style.textAlign = 'center';
                            if(dataItem !== undefined){
                                // if(dataItem.child_parts_flg === this.FLG_ON){
                                //     cell.childNodes[0].disabled = true;
                                // }
                                if(dataItem.layer_flg !== this.FLG_ON && (this.rmUndefinedZero(dataItem.product_id) === 0 || dataItem.draft_flg === this.FLG_ON)){
                                    cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.PRODUCT_AUTO_CELL;
                                }
                            }
                            break;
                        default:
                            // nop
                            break;
                    }
                }
            }.bind(this);

            // クリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        switch (col.name) {
                            case 'order_cancel':
                                // 発注取消ボタン
                                if (!this.isReadOnly && record.layer_flg !== this.FLG_ON) {
                                    var result = window.confirm(MSG_CONFIRM_DELETE);
                                    if (result) {
                                        record.is_delete = true;
                                        // 削除行判定
                                        var rowIndex = gridCtrl.collectionView.sourceCollection.findIndex((value) => {
                                            return (value.unique_key === record.unique_key);
                                        });
                                        // 新規追加行 && 削除行は元データから削除（サーバに送信しない為）
                                        if (this.rmUndefinedZero(record.id) === 0 &&
                                            record.is_delete) {
                                            gridCtrl.collectionView.sourceCollection.splice(rowIndex, 1);
                                        }
                                        
                                        if(record.child_parts_flg === this.FLG_ON){
                                            this.setParentRowPrice(record.parent_quote_detail_id);
                                            var currentGridDataList = this.getChildPartsList(record.parent_quote_detail_id, false);
                                            if(currentGridDataList.length === 0){
                                                // 一式削除(非表示)
                                                var parentRow = gridCtrl.collectionView.sourceCollection.find((value) => {
                                                    return (value.quote_detail_id === record.parent_quote_detail_id);
                                                });
                                                parentRow.is_delete = true;
                                            }
                                        }

                                        // 削除判定したものはフィルタリング
                                        this.filterGrid();
                                    }
                                }
                                break;
                            case 'btn_no_product_code':
                                if(!this.isReadOnly && !this.isOwnStock){
                                    var clearValidFlg = true;

                                    if(record.intangible_flg === this.FLG_ON){
                                        alert(MSG_ERROR_HINBANNASHI);
                                        clearValidFlg = false;
                                    }
                                    if(clearValidFlg){
                                        record.arrival_plan_date= this.INIT_ROW.arrival_plan_date;
                                        record.product_id       = this.INIT_ROW.product_id;
                                        record.product_code     = this.noProductCode.value_text_1;
                                        record.intangible_flg   = this.FLG_OFF;
                                        gridCtrl.refresh();
                                        //this.calcTreeGridRowData(record, 'order_quantity');
                                    }
                                }
                                break;
                        }
                    }
                }
            });

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;

                s.autoClipboard = true;

                switch(col.name){
                    case 'hope_arrival_plan_date':
                        if(!this.isHopeArrivalDateValid(row)){
                            this.wjGridCustomItem.hope_arrival_plan_date.control.isDisabled = true;
                            e.cancel = true;
                        }else{
                             this.wjGridCustomItem.hope_arrival_plan_date.control.isDisabled = false;
                        }
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                    case 'arrival_plan_date':
                        if(!this.isArrivalDateValid(row)){
                            this.wjGridCustomItem.arrival_plan_date.control.isDisabled = true;
                            e.cancel = true;
                        }else{
                            this.wjGridCustomItem.arrival_plan_date.control.isDisabled = false;
                        }
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'product_code':
                    case 'product_name':
                        // 階層の場合、コンボボックスを表示させない
                        if(row.layer_flg !== this.FLG_ON && row.set_flg !== this.FLG_ON){
                            this.wjGridCustomItem.product_code.changeItemsSource([]);
                            this.wjGridCustomItem.product_name.changeItemsSource([]);
                        }else{
                            this.wjGridCustomItem.product_code.changeItemsSource(null);
                            this.wjGridCustomItem.product_name.changeItemsSource(null);
                        }
                        
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'order_quantity':
                    case 'regular_price':
                    case 'cost_unit_price':
                    case 'cost_kbn':
                        if(row.layer_flg === this.FLG_ON){
                            e.cancel = true;
                        }
                        break;
                    case 'cost_makeup_rate':
                        if(this.rmUndefinedZero(row.regular_price) === 0 || row.layer_flg === this.FLG_ON){
                            e.cancel = true;
                        }
                        break;
                    case 'unit':
                    case 'stock_unit':
                        if (this.isOwnStock) {
                            // 当社在庫の場合、商品登録更新しないので編集不可
                            e.cancel = true;
                        }
                        break;
                    default :
                        // nop
                        break;
                }

                if (this.gridIsReadOnlyCell(gridCtrl, e.row, e.col)) {
                    e.cancel = true;
                }
            }.bind(this));
            // 表編集後の確定前イベント
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                // クリップボードを有効にさせるため編集後はfalseへ
                s.autoClipboard = false;
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    case 'arrival_plan_date':
                        if(!this.wjGridCustomItem.arrival_plan_date.control.isDisabled){
                            var beforeArrivalPlanDate = this.rmUndefinedBlank(row.arrival_plan_date);
                            var afterArrivalPlanDate = this.rmUndefinedBlank(this.wjGridCustomItem.arrival_plan_date.control.text);
                            if(afterArrivalPlanDate !== beforeArrivalPlanDate && afterArrivalPlanDate !== ''){
                                // 入荷予定日の入力可能範囲は制限なしに修正
                                // if(this.strToTime(afterArrivalPlanDate) <= this.strToTime(this.todayYYYYMMDDSlashFormat)){
                                //     alert(MSG_ERROR_ORDER_ARRIVAL_PLAN_DATE);
                                //     this.wjGridCustomItem.arrival_plan_date.control.text = beforeArrivalPlanDate;
                                //     e.cancel = true;
                                // }
                            }
                        }
                        break;
                    case 'product_code':
                        row.is_cancel = false;
                        if(this.rmUndefinedBlank(this.wjGridCustomItem.product_code.control.text) === row.product_code){
                            e.cancel = true;
                        }else{
                            row.is_cancel = this.changingProduct(this.wjGridCustomItem.product_code, row, true);
                        }
                        break;
                    case 'product_name':
                        row.is_cancel = false;
                        if(this.rmUndefinedBlank(this.wjGridCustomItem.product_name.control.text) === row.product_name){
                            e.cancel = true;
                        }else{
                            row.is_cancel = this.changingProduct(this.wjGridCustomItem.product_name, row, false);
                        }
                        break;
                    case 'order_quantity':
                        var orderQuantity = s.activeEditor == null ? 0 : s.activeEditor.value;
                        if(!this.treeGridQuantityIsMultiple(orderQuantity, row.order_lot_quantity)){
                            alert(MSG_ERROR_NOT_ORDER_LOT_QUANTITY_MULTIPLE + ' 現在の発注ロット数：' + row.order_lot_quantity);
                            e.cancel = true;
                        }
                        break;
                }
            }.bind(this));
            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var editColNm = this.wjGridDetails.getBindingColumn(e.panel, e.row, e.col).name;       // 取れるのは上段の列名
                // 編集行データ取得　　　　　TODO: たまにrowが取得できていなくてエラーが出る
                var row = s.collectionView.currentItem;

                switch (editColNm) {
                    case 'arrival_plan_date':
                        if(this.rmUndefinedBlank(row.hope_arrival_plan_date) === '' && this.rmUndefinedBlank(row.arrival_plan_date) !== ''){
                            if(this.isHopeArrivalDateValid(row)){
                                // 入荷予定日の翌日をセット
                                row.hope_arrival_plan_date = this.getNextDay(row.arrival_plan_date);
                            }
                        }
                        break;
                     case 'product_code':
                        // 商品コード
                        var product = this.wjGridCustomItem.product_code.selectedItem;
                        this.changeProduct(product, row, true);
                        break;
                    case 'product_name':
                        // 商品名
                        var product = this.wjGridCustomItem.product_name.selectedItem;
                        this.changeProduct(product, row, false);
                        break;
                    case 'cost_kbn':
                        // 仕入区分
                        if(
                            e.data !== row.cost_kbn &&
                            this.rmUndefinedBlank(row.cost_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row);
                        }
                        break;
                    case 'order_quantity':
                        // 発注数
                        if(this.rmUndefinedZero(row.quote_detail_id) === 0){
                            row.quote_quantity = row.order_quantity
                        }
                        this.calcTreeGridRowData(row, 'order_quantity');
                        break;
                    case 'regular_price':
                        // 定価
                        this.calcTreeGridChangeRegularPriceEx(row);
                        this.calcTreeGridRowData(row, 'order_quantity');
                        break;
                     case 'cost_unit_price':
                        // 仕入単価
                        this.calcTreeGridChangeUnitPrice(row, true);
                        this.calcTreeGridRowData(row, 'order_quantity');
                        break;
                    case 'cost_makeup_rate':
                        // 仕入掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, true);
                            this.calcTreeGridRowData(row, 'order_quantity');
                        }
                        break;
                    case 'class_big_id':
                        // 大分類
                        if(e.data !== row.class_big_id){
                            // 大分類を変更したら中分類をクリア
                            row.class_middle_id = 0;
                            this.wjGridDetails.collectionView.refresh();
                        }

                        break;
                }
                if(row.child_parts_flg === this.FLG_ON){
                    this.setParentRowPrice(row.parent_quote_detail_id);
                }
                gridCtrl.endUpdate();
            }.bind(this));

            /* カスタムグリッド */
            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            }.bind(this), true);

            this.wjGridCustomItem.hope_arrival_plan_date = new CustomGridEditor(gridCtrl, 'hope_arrival_plan_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 2, 1, 1);
            this.wjGridCustomItem.arrival_plan_date = new CustomGridEditor(gridCtrl, 'hope_arrival_plan_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 2, 2, 1);
            // 商品コードオートコンプリート
            this.wjGridCustomItem.product_code = new CustomGridEditor(gridCtrl, 'product_code', wjcInput.AutoComplete, {
                searchMemberPath: "product_code",
                displayMemberPath: "product_code",
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
                delay: 50,
                itemsSourceFunction: (text, maxItems, callback) => {
                    // 編集中の行
                    var row = gridCtrl.collectionView.currentItem;
                    // 階層の場合は何もしない
                    if(row === null || row.layer_flg === this.FLG_ON || row.set_flg === this.FLG_ON){
                        return;
                    }

                    if (!text) {
                        callback([]);
                        return;
                    }

                    // サーバ通信中の場合
                    if(this.wjGridCustomItem.product_code.loadingFlg){
                        return;
                    }

                    if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                        return;
                    }

                    this.setASyncAutoCompleteList(this.wjGridCustomItem.product_code, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
                }
            }, 2, 1, 2);
            // 商品名オートコンプリート
            this.wjGridCustomItem.product_name = new CustomGridEditor(gridCtrl, 'product_name', wjcInput.AutoComplete, {
                searchMemberPath: "product_name, product_short_name",
                displayMemberPath: "product_name",
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
                delay: 50,
                itemsSourceFunction: (text, maxItems, callback) => {
                    // 編集中の行
                    var row = gridCtrl.collectionView.currentItem;
                    // 階層の場合は何もしない
                    if(row === null || row.layer_flg === this.FLG_ON || row.set_flg === this.FLG_ON){
                        return;
                    }

                    if (!text) {
                        callback([]);
                        return;
                    }

                    // サーバ通信中の場合
                    if(this.wjGridCustomItem.product_name.loadingFlg){
                        return;
                    }

                    if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH){
                        return;
                    }

                    this.setASyncAutoCompleteList(this.wjGridCustomItem.product_name, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
                }
            }, 2, 1, 1);
            // メーカー名オートコンプリート
            // this.wjGridCustomItem.maker_name = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
            //     searchMemberPath: "supplier_name",
            //     displayMemberPath: "supplier_name",
            //     itemsSource: this.supplierList,
            //     selectedValuePath: "id",
            //     isRequired: false,
            //     isEditable: false,
            // }, 2, 1, 1);
            // // 仕入先名オートコンプリート
            // this.wjGridCustomItem.supplier_name = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
            //     searchMemberPath: "supplier_name",
            //     displayMemberPath: "supplier_name",
            //     itemsSource: this.supplierList,
            //     selectedValuePath: "id",
            //     isRequired: false,
            //     isEditable: false,
            // }, 2, 2, 1);

            return gridCtrl;
        },
        existsFile(url){
            var promise = axios.get(url)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } else {
                    // 失敗
                    alert(MSG_ERROR_NOT_EXISTS_FILE);
                }
            }.bind(this))
            .catch(function (error) {
                alert(MSG_ERROR); 
            }.bind(this))
            return promise;
        },
        // 保存
        save() {
            this.loading = true;
            this.initErr(this.errors);

            var gridData = this.wjGridDetails.collectionView.sourceCollection;
            var saveGridDataList = [];

            var errMsg = '';
            for(var i=0;i<gridData.length;i++){
                saveGridDataList[i] = Vue.util.extend({}, gridData[i]);
                
                if(!saveGridDataList[i].is_delete){
                    var totalQuantity = this.bigNumberPlus(saveGridDataList[i].reserve_quantity, saveGridDataList[i].order_quantity);
                    // 一式の発注は1個のみ
                    if(!this.treeGridSetProductSaveIsValid(saveGridDataList[i], totalQuantity)){
                        errMsg = MSG_ERROR_SET_PRODUCT_ORDER;
                        break;
                    }
                    // 発注数が未入力
                    if(!this.bigNumberGte(this.rmUndefinedZero(saveGridDataList[i].order_quantity), 0.01)){
                        errMsg = MSG_ERROR_NO_INPUT_ORDER_QUANTITY;
                        break;
                    }
                    // 当社在庫品の場合のチェック
                    if(this.isOwnStock){
                        if(this.rmUndefinedZero(saveGridDataList[i].product_id) === 0){
                            // 当社在庫品の場合、商品マスタにある商品のみ発注可能
                            errMsg = MSG_ERROR_NOT_PRODUCT_MASTER_DATA_ORDER;
                            break;
                        }
                        if(this.rmUndefinedZero(saveGridDataList[i].intangible_flg) === this.FLG_ON){
                            // 当社在庫品の場合、無形品は発注できない
                            errMsg = MSG_ERROR_INTANGIBLE_ORDER;
                            break;
                        }
                        // 見積数 = 発注数にする
                        saveGridDataList[i].quote_quantity = saveGridDataList[i].order_quantity; 
                    }
                    // 子部品の発注は見積数を超えた場合に反映する
                    if(saveGridDataList[i].child_parts_flg === this.FLG_ON){
                        if(this.bigNumberLt(saveGridDataList[i].quote_quantity, totalQuantity)){
                            saveGridDataList[i].quote_quantity = totalQuantity;
                        }
                    }
                    
                    // 空選択肢ありのDataMapはnullが入ることがあるため置換
                    saveGridDataList[i].class_big_id = this.rmUndefinedZero(saveGridDataList[i].class_big_id);
                    saveGridDataList[i].class_middle_id = this.rmUndefinedZero(saveGridDataList[i].class_middle_id);
                }
            }
            
            if (errMsg !== '') {
                this.loading = false;
                alert(errMsg);
                return;
            }

            var jsonData = JSON.stringify(saveGridDataList);

            var params = new FormData();

            // params.append('old_order_data', JSON.stringify(this.orderData));
            // params.append('old_grid_data', JSON.stringify(this.orderDetailData));

            params.append('grid_data', jsonData);
            params.append('id', this.rmUndefinedZero(this.order.id));
            params.append('order_no', this.rmUndefinedZero(this.order.order_no));
            params.append('quote_id', this.rmUndefinedZero(this.order.quote_id));
            params.append('quote_no', this.rmUndefinedZero(this.order.quote_no));
            params.append('matter_id', this.rmUndefinedZero(this.order.matter_id));
            params.append('map_print_flg', this.rmUndefinedZero(this.order.map_print_flg));
            params.append('maker_id', this.rmUndefinedZero(this.order.maker_id));
            params.append('supplier_id', this.rmUndefinedZero(this.order.supplier_id));
            params.append('customer_id', this.rmUndefinedZero(this.order.customer_id));
            params.append('own_stock_flg', this.rmUndefinedZero(this.order.own_stock_flg));
            params.append('desired_delivery_date', this.rmUndefinedBlank(this.wjDesiredDeliveryDate.text));

            var deliveryAddressKbn = null;
            var deliveryAddressId = null;
            if (this.wjDeliveryAddress.selectedItem) {
                deliveryAddressKbn = this.wjDeliveryAddress.selectedItem.delivery_address_kbn;
                deliveryAddressId = this.wjDeliveryAddress.selectedItem.id;
            }
            params.append('delivery_address_kbn', this.rmUndefinedZero(deliveryAddressKbn));
            params.append('delivery_address_id', this.rmUndefinedZero(deliveryAddressId));
            params.append('delivery_address', this.rmUndefinedBlank(this.order.delivery_address));

            params.append('account_customer_name', this.rmUndefinedBlank(this.order.account_customer_name));
            params.append('account_owner_name', this.rmUndefinedBlank(this.order.account_owner_name));
            params.append('person_id', this.rmUndefinedZero(this.order.person_id));
            params.append('sales_support_comment', this.rmUndefinedBlank(this.order.sales_support_comment));
            params.append('supplier_comment', this.rmUndefinedBlank(this.order.supplier_comment));

            // ファイル
            for(var i=0 ; i < this.uploadFileList.length ; i++){
                params.append('upload_file_list_' + i, this.uploadFileList[i]);
            }
            params.append('delete_file_list', JSON.stringify(this.deleteFileList));


            axios.post('/order-detail/save', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                // this.loading = false
                if (response.data) {
                    if (response.data.status == true) {
                        window.onbeforeunload = null;
                        location.reload();
                    } else {
                        this.loading = false;
                        if(response.data.message){
                            alert(response.data.message);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                } else {
                    this.loading = false;
                    alert(MSG_ERROR);
                }
            }.bind(this))

            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                    for(var i=0 ; i < this.uploadFileList.length ; i++){
                        var fileKey = 'upload_file_list_' + i;
                        if(this.errors['upload_file_list'] === '' && error.response.data.errors[fileKey] !== undefined && this.rmUndefinedBlank(error.response.data.errors[fileKey]) !== ''){
                            this.errors['upload_file_list'] = error.response.data.errors[fileKey];
                        }
                    }
                }else{
                    this.loading = false;
                    alert(MSG_ERROR);
                }
                this.loading = false
            }.bind(this))
        },
        // 戻る
        back() {
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/order-list';
            }
            var listUrl = rtnUrl + query;

            if (!this.isReadOnly && this.order.quote_id) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'order-detail');
                params.append('keys', this.rmUndefinedBlank(this.order.quote_id));
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    // this.loading = false
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
            params.append('screen', 'order-detail');
            params.append('keys', this.rmUndefinedBlank(this.order.quote_id));
            axios.post('/common/lock', params)

            .then( function (response) {
                // this.loading = false
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
            this.loading = true;
            var params = new URLSearchParams();
            params.append('screen', 'order-detail');
            params.append('keys', this.rmUndefinedBlank(this.order.quote_id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                // this.loading = false
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
        // グリッドレイアウト
        getGridLayout() {
            // 価格区分
            var priceKbnMap = new wjGrid.DataMap(this.priceList, 'value_code', 'value_text_1');
            var visibleProductInfoFlg = !this.isOwnStock;
            // 大分類
            var classBigMap = new wjGrid.DataMap(this.classBigList, 'class_big_id', 'class_big_name');
            // 中分類
            var classMiddleMap = new wjGrid.DataMap(this.classMiddleList, 'class_middle_id', 'class_middle_name');
            // 大分類IDで中分類の選択肢を絞り込む
            classMiddleMap.getDisplayValues = (dataItem) => {
                var filterMiddleList = this.classMiddleList.filter(middle => (middle.class_big_id === dataItem.class_big_id || middle.class_big_id === 0));
                return filterMiddleList.map(middle => middle.class_middle_name);
            };

            return [
                { cells: [{ name: 'chk', binding: 'chk', header: '選択', width: 30}] },
                { cells: [{ name: 'fix', binding: 'fix', header: '修正',  width: 70, isReadOnly: true }] },
                { cells: [{ name: 'order_cancel', binding: 'order_cancel', header: '発注取消',  width: 70, isReadOnly: true }]},
                { cells: [
                    { name: 'hope_arrival_plan_date', binding: 'hope_arrival_plan_date', header: '希望出荷予定日',  width: 130, isRequired: false },
                    { name: 'arrival_plan_date', binding: 'arrival_plan_date', header: '入荷予定日',  width: 130, isReadOnly: this.order.editable_arrival_plan_date, isRequired: false },
                ]},
                { cells: [
                    { name: 'product_code',binding: 'product_code', header: '品番',  width: 150, isReadOnly: false},
                ]},
                { cells: [{ name: 'btn_no_product_code', binding: 'btn_no_product_code', header: '　', width: 30, isReadOnly: true }] },
                { cells: [
                    { name: 'product_name', binding: 'product_name', header: '商品名',  width: 210, isReadOnly: false},
                    { name: 'model', binding: 'model', header: '型式・規格',  width: 210, isReadOnly: false, isRequired: false },
                ]},
                { cells: [
                    { name: 'maker_name', binding: 'maker_name', header: 'メーカー名',  width: 180, isReadOnly: true },
                    { name: 'supplier_name', binding: 'supplier_name', header: '仕入先名',  width: 180, isReadOnly: true },
                ]},
                { cells: [
                    { name: 'order_quantity', binding: 'order_quantity', header: '発注数',  width: 90, isReadOnly: false, cssClass: 'text-right', isRequired: false },
                    { name: 'unit', binding: 'unit', header: '単位',  width: 90, isReadOnly: false }
                ]},
                { cells: [
                    { name: 'stock_quantity', binding: 'stock_quantity', header: '管理数',  width: 100, isReadOnly: true, cssClass: 'text-right' },
                    { name: 'stock_unit', binding: 'stock_unit', header: '管理数単位',  width: 100, isReadOnly: false }
                ]},
                { cells: [
                    { name: 'regular_price', binding: 'regular_price', header: '定価',  width: 110, isReadOnly: false, cssClass: 'text-right', isRequired: false },
                    { name: 'cost_kbn', binding: 'cost_kbn', header: '仕入区分', dataMap: priceKbnMap, width: 110 },
                ]},
                { cells: [
                    { name: 'cost_unit_price', binding: 'cost_unit_price', header: '仕入単価', width: 90, isReadOnly: false, cssClass: 'text-right', isRequired: false },
                    { name: 'sales_unit_price', binding: 'sales_unit_price', header: '販売単価',  width: 90, isReadOnly: true, cssClass: 'text-right' },
                ]},
                { cells: [
                    { name: 'cost_makeup_rate', binding: 'cost_makeup_rate', header: '仕入掛率', width: 90 ,isReadOnly: false, cssClass: 'text-right', isRequired: false },
                    { name: 'sales_makeup_rate', binding: 'sales_makeup_rate', header: '販売掛率', width: 90, isReadOnly: true, cssClass: 'text-right' },
                ]},
                { cells: [
                    { name: 'cost_total', binding: 'cost_total', header: '仕入総額', width: 90, isReadOnly: true, cssClass: 'text-right' },
                    { name: 'sales_total', binding: 'sales_total', header: '販売総額', width: 90, isReadOnly: true, cssClass: 'text-right' },
                ]},
                { cells: [
                    { name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 90, isReadOnly: true, cssClass: 'text-right' },
                    { name: 'profit_total', binding: 'profit_total', header: '粗利総額', width: 90, isReadOnly: true, cssClass: 'text-right' },
                ]},
                { cells: [{ name: 'memo', binding: 'memo', header: '備考', width: 135, isReadOnly: false, isRequired: false }]},
                { cells: [{ name: 'product_auto_flg', binding: 'product_auto_flg', header: '1回限り登録', wordWrap: true, width: 70, isReadOnly: false, visible: visibleProductInfoFlg}] },
                { cells: [{ name: 'product_definitive_flg', binding: 'product_definitive_flg', header: '本登録', wordWrap: true, width: 70, isReadOnly: false, visible: visibleProductInfoFlg}] },
                { cells: [
                    { name: 'class_big_id', binding: 'class_big_id', header: '大分類', width: 110, dataMap: classBigMap, isReadOnly: false, isRequired: false, visible: visibleProductInfoFlg },
                    { name: 'class_middle_id', binding: 'class_middle_id', header: '中分類', width: 110, dataMap: classMiddleMap, isReadOnly: false, isRequired: false, visible: visibleProductInfoFlg },
                ] },

                // 非表示
                { cells: [{ name: 'id', binding: 'id', header: '発注明細ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', header: '見積明細ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'product_id', binding: 'product_id', header: '商品ID', visible:false, isReadOnly: true }]},
                { cells: [{ name: 'maker_id', binding: 'maker_id', header: 'メーカーID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'supplier_id', binding: 'supplier_id', header: '仕入先ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'add_kbn', binding: 'add_kbn', header: '追加区分', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'is_add', binding: 'is_add', header: '新規行判定', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'is_delete', binding: 'is_delete', header: '削除行判定', visible: false, isReadOnly: true }]},

                { cells: [{ name: 'construction_id', binding: 'construction_id', header: '工事区分ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'parent_quote_detail_id', binding: 'parent_quote_detail_id', header: '親見積明細ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'layer_flg', binding: 'layer_flg', header: '階層フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'tree_path', binding: 'tree_path', header: 'ツリーパス', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'set_flg', binding: 'set_flg', header: '一式フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'min_quantity', binding: 'min_quantity', header: '最小単位数', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'order_lot_quantity', binding: 'order_lot_quantity', header: '発注ロット数', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'quote_quantity', binding: 'quote_quantity', header: '見積数', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'reserve_quantity', binding: 'reserve_quantity', header: '全引当数', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'sales_kbn', binding: 'sales_kbn', header: '販売区分', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'intangible_flg', binding: 'intangible_flg', header: '無形品フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'draft_flg', binding: 'draft_flg', header: '仮登録フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'child_parts_flg', binding: 'child_parts_flg', header: '子部品フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'sales_use_flg', binding: 'sales_use_flg', header: '販売額利用フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'unique_key', binding: 'unique_key', header: '一意キー', visible: false, isReadOnly: true }]},

                { cells: [{ name: 'quantity_per_case', binding: 'quantity_per_case', header: '入り数', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'set_kbn', binding: 'set_kbn', header: 'セット区分', visible: false, isReadOnly: true }] },
                // { cells: [{ name: 'class_big_id', binding: 'class_big_id', header: '大分類ID', visible: false, isReadOnly: true }] },
                // { cells: [{ name: 'class_middle_id', binding: 'class_middle_id', header: '中分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_small_id', binding: 'class_small_id', header: '小分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'tree_species', binding: 'tree_species', header: '樹種', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'grade', binding: 'grade', header: '等級', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'length', binding: 'length', header: '長さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'thickness', binding: 'thickness', header: '厚み', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'width', binding: 'width', header: '幅', visible: false, isReadOnly: true }] },
            ]
        },

        /* ファイル関連 */
        onDrop: function(event){
            if(!this.isReadOnly){
                let fileList = event.target.files ? 
                                event.target.files:
                                event.dataTransfer.files;
                let files = [];
                for(let i = 0; i < fileList.length; i++){
                    this.uploadFileList.push(fileList[i]);
                }
            }
        },
        fileDownLoad: async function(storageFileName){
            var path = this.order.matter_id + '/' + this.order.id + '/' + encodeURIComponent(storageFileName);
            var existsUrl = '/order-detail/exists/' + path;
            var result = await this.existsFile(existsUrl);
            if (result != undefined && result) {
                var downloadUrl = '/order-detail/download/' + path;
                var tmp = window.onbeforeunload;
                window.onbeforeunload = null;
                location.href = downloadUrl;
                window.onbeforeunload = tmp;
            }

        },
        fileDelete: function(key){
            // 削除するファイル名
            if(this.rmUndefinedBlank(this.uploadFileList[key].storage_file_name) !== ''){
                this.deleteFileList.push(this.uploadFileList[key].storage_file_name);
            }
            this.uploadFileList.splice(key, 1);
            document.getElementById('file-up').value = '';
        },
        /*************************************
         * 以下、モーダル関連
         *************************************/
        // 検索（工事区分）
        initModalSearchConstruction: function(sender){
            this.modal.construction = sender;
        },
        changeIdxModalSearchConstruction: function(sender){
            this.modal.construction.selectedValue = sender.selectedValue;
        },
        // 検索（商品名, 商品コード）
        initModalSearchProduct: function(sender){
            var key = sender.displayMemberPath;
            this.modal[key] = sender;
        },
        changeIdxModalSearchProductCode: function(sender){
            // this.modal.product_code.selectedValue = sender.selectedValue;
            // if(sender.selectedItem !== null){
            //     this.modal.product_name.text = sender.selectedItem.product_name;
            // }
        },
        changeIdxModalSearchProductName: function(sender){
            // this.modal.product_name.selectedValue = sender.selectedValue;
            // if(sender.selectedItem !== null){
            //     this.modal.product_code.text = sender.selectedItem.product_code;
            // }
        },
        /**
         * モーダルの商品番号のオートコンプリートの選択肢生成
         * @param text
         * @param maxItems
         * @param cakkback
         */
        modalProductCodeItemsSouceFunction(text, maxItems, callback){
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合 グリッド以外のオートコンプリートには存在しないプロパティのため初回はundefined
            if(this.modal.product_code.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                return;
            }

            this.setASyncAutoCompleteList(this.modal.product_code, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_INCLUDE_AUTO_FLG_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
        },
        /**
         * モーダルの商品番号のオートコンプリートの選択肢生成
         * @param text
         * @param maxItems
         * @param cakkback
         */
        modalProductNameItemsSouceFunction(text, maxItems, callback){
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合 グリッド以外のオートコンプリートには存在しないプロパティのため初回はundefined
            if(this.modal.product_name.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH){
                return;
            }

            this.setASyncAutoCompleteList(this.modal.product_name, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_INCLUDE_AUTO_FLG_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
        },

        
        // 商材追加（メインのグリッドに行挿入）
        addOrderDetailsGrid() {
            if (this.modal.grid && this.modal.grid.collectionView !== undefined) {
                this.modal.grid.collectionView.items.forEach(element => {
                    if (element.chk) {
                        var rec = this.getInitRow(element);

                        // 引当数(発注含む)が0 かつ 見積数が発注ロットの倍数の場合、発注数に見積数をセットする
                        if(this.bigNumberEq(rec['reserve_quantity'], 0) && this.treeGridQuantityIsMultiple(rec['quote_quantity'], rec['order_lot_quantity'])){
                            rec.order_quantity = rec['quote_quantity'];
                        }else{
                            rec.order_quantity = 0;
                        }
                        this.calcTreeGridRowData(rec, 'order_quantity');
                        
                        var gridData = this.wjGridDetails.collectionView.sourceCollection;

                        // メイングリッドに同一の受注確定データが存在するレコードに関しては追加しない
                        const recExists = gridData.find((value) => {
                            return (value.quote_detail_id === rec['quote_detail_id']);
                        });
                        if (recExists === undefined) {
                            rec['unique_key'] = this.createGridUniqueKey();

                            var setQuoteDetail = this.setProductList.find((value) => {
                                return (value.quote_detail_id === rec['parent_quote_detail_id']);
                            });
                            if(setQuoteDetail !== undefined){
                                // 子部品
                                rec['child_parts_flg']  = this.FLG_ON;
                                // 1回限り登録フラグ
                                rec['product_auto_flg'] = true;
                                // 本登録チェック
                                rec['product_definitive_flg'] = false;

                                // 親の位置を特定
                                var parentRowIndex = gridData.findIndex((value) => {
                                    return (value.quote_detail_id === rec['parent_quote_detail_id']);
                                });
                                if(parentRowIndex !== -1){
                                    // 親が存在する
                                    gridData[parentRowIndex].is_delete = false;
                                    var insertIndex = parentRowIndex + 1;
                                    for(;insertIndex < gridData.length; insertIndex++){
                                        if(gridData[parentRowIndex].quote_detail_id !== gridData[insertIndex].parent_quote_detail_id){
                                            break;
                                        }
                                    }
                                    gridData.splice(insertIndex, 0, rec);
                                }else{
                                    // 親を作成する
                                    var parentRow = this.getInitRow(this.setProductList.find((value) => {
                                        return (value.quote_detail_id === rec['parent_quote_detail_id']);
                                    }));
                                    parentRow.unique_key = rec['unique_key'];
                                    parentRow.order_quantity = parentRow.order_lot_quantity;
                                    this.calcTreeGridRowData(parentRow, 'order_quantity');
                                    rec['unique_key']++;
                                    // 親階層を追加
                                    gridData.push(parentRow);
                                    gridData.push(rec);
                                }
                                this.setParentRowPrice(rec.parent_quote_detail_id);
                            }else{
                                // 通常の商品
                                gridData.push(rec);
                            }
                        }else{
                            // 元からある発注明細データは非表示にしてある状態のため復活させる
                            recExists.is_delete = false;
                            // 親の位置を特定(子部品の場合は親が削除されてる可能性があるので復活させる)
                            var parentRowIndex = gridData.find((value) => {
                                return (value.quote_detail_id === recExists.parent_quote_detail_id);
                            });
                            if(parentRowIndex !== undefined){
                                // 親がある = 子部品を復活させた
                                parentRowIndex.is_delete = false;
                                this.setParentRowPrice(recExists.parent_quote_detail_id);
                            }
                        }
                    }
                });   
            }
            this.wjGridDetails.collectionView.refresh();
            this.showAddMaterialsModal(false);
        },
        // グリッド生成（モーダル）
        createAddMaterialsGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayoutModal,
                headersVisibility: wjGrid.HeadersVisibility.Column,
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

                        checkBox.addEventListener('click', function (e) {
                            gridCtrl.beginUpdate();
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                gridCtrl.setCellData(i, c, checkBox.checked);
                            }
                            gridCtrl.endUpdate();
                        });
                    }
                }

                if (panel.cellType == wjGrid.CellType.Cell) {
                    // 横スクロールで文字位置がおかしくなるのでtextAlignは明示的に設定
                    switch (col.name) {
                        case 'product_code':            // 品番
                        case 'product_name':            // 品名,型式・規格
                            cell.style.textAlign = 'left';
                            break;
                        case 'reserve_quantity':        // 受注数
                        case 'quote_quantity':          // 発注数
                            cell.style.textAlign = 'right';
                            break;
                    }
                }
            }.bind(this);

            return gridCtrl;
        },
        // グリッド設定
        settingModalGrid(gridCtrl) {
            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS_MODAL.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });
        },
        // 検索
        searchAddMaterialsModal(){
            this.loading = true;
            var params = new URLSearchParams();

            // 見積番号
            params.append('quote_no', this.rmUndefinedZero(this.order.quote_no));
            params.append('quote_id', this.rmUndefinedZero(this.order.quote_id));
            //params.append('maker_id', this.rmUndefinedZero(this.order.maker_id));
            params.append('construction_id', this.rmUndefinedZero(this.modal.construction.selectedValue));
            params.append('parent_layer_name', this.rmUndefinedBlank(this.modal.parent_layer_name));
            params.append('product_code', this.rmUndefinedBlank(this.modal.product_code.text));
            params.append('product_name', this.rmUndefinedBlank(this.modal.product_name.text));
            params.append('not_treated', this.rmUndefinedZero(this.modal.not_treated));
            axios.post('/order-detail/received-order/search', params)
            .then( function (response) {
                if (response.data) {
                    var itemsSource = [];
                    for (var i in response.data) {
                        response.data[i]['chk'] = false;
                        // メイングリッドと見積明細IDが重複するものは排除
                        const existsIdx = this.wjGridDetails.collectionView.sourceCollection.findIndex((value) => {
                            var result = false;
                            if(value.quote_detail_id === response.data[i]['quote_detail_id'] && !value.is_delete){
                                result = true;
                            }else if(this.rmUndefinedZero(response.data[i]['product_maker_id']) !== 0 && this.order.maker_id !== response.data[i]['product_maker_id']){
                                result = true;
                            }else if(this.order.maker_id !== response.data[i]['maker_id'] && this.bigNumberGt(response.data[i]['order_quantity'], 0.01)){
                                result = true;
                            }else if(this.order.supplier_id !== response.data[i]['supplier_id'] && this.bigNumberGt(response.data[i]['order_quantity'], 0.01)){
                                result = true;
                            }
                            return result;
                        });
                        if (existsIdx == -1) {
                            // 無形品フラグ
                            if(response.data[i]['intangible_flg'] === null){
                                // 商品マスタに紐づいていないデータの場合
                                if(this.rmUndefinedZero(response.data[i]['layer_flg']) === this.FLG_ON){
                                    // 階層は無形品(未使用)
                                    response.data[i]['intangible_flg'] = this.FLG_ON;
                                }else if(this.rmUndefinedBlank(response.data[i]['product_code']) === ''){
                                    // 商品コードが空の場合は無形品として扱う
                                    response.data[i]['intangible_flg'] = this.FLG_ON;
                                }else{
                                    // 商品コードが入っていれば有形品として扱う
                                    response.data[i]['intangible_flg'] = this.FLG_OFF;
                                }
                            }
                            itemsSource.push(response.data[i]);
                        }
                    }

                    this.modal.grid.itemsSource = itemsSource;
                    this.settingModalGrid(this.modal.grid);
                }
                this.loading = false;
            }.bind(this))
            .catch(function (error) {
                this.loading = false;
            }.bind(this))
        },
        // レイアウト
        getAddMaterialsGridLayout() {
            return [
                { cells: [{ name: 'chk', binding: 'chk', header: '選択', width: 30 } ]},
                { cells: [{ name: 'construction_name', binding: 'construction_name', header: '工事区分',  width: 130, isReadOnly: true } ]},
                { cells: [{ name: 'parent_layer_name', binding: 'parent_layer_name', header: '階層名',  width: 130, isReadOnly: true } ]},
                { cells: [{ name: 'product_code', binding: 'product_code', header: '品番',  minWidth: 180, isReadOnly: true } ]},
                { cells: [{ name: 'product_name', binding: 'product_name', header: '商品名',  minWidth: 180, width:'*', isReadOnly: true } ]},
                { cells: [{ name: 'reserve_quantity', binding: 'reserve_quantity', header: '引当発注数',  width: 85, isReadOnly: true } ]},
                { cells: [{ name: 'quote_quantity', binding: 'quote_quantity', header: '受注数',  width: 85, isReadOnly: true } ]},
                
                // 非表示
                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', visible: false, isReadOnly: true } ]},
                { cells: [{ name: 'product_id', binding: 'product_id', visible: false, isReadOnly: true } ]},
                { cells: [{ name: 'unit', binding: 'unit', visible: false, isReadOnly: true } ]},
                { cells: [{ name: 'model', binding: 'model', visible: false, isReadOnly: true } ]},
                { cells: [{ name: 'regular_price', binding: 'regular_price', visible: false, isReadOnly: true } ]},
                { cells: [{ name: 'cost_unit_price', binding: 'cost_unit_price', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'cost_makeup_rate', binding: 'cost_makeup_rate', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'sales_unit_price', binding: 'sales_unit_price', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'sales_makeup_rate', binding: 'sales_makeup_rate', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'cost_kbn', binding: 'cost_kbn', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'sales_kbn', binding: 'sales_kbn', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'supplier_id', binding: 'supplier_id', header: '仕入先ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'add_kbn', binding: 'add_kbn', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'order_quantity', binding: 'order_quantity', header: '発注数', visible:false, isReadOnly: true } ]},
                { cells: [{ name: 'construction_id', binding: 'construction_id', header: '工事区分ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'parent_quote_detail_id', binding: 'parent_quote_detail_id', header: '親見積明細ID', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'tree_path', binding: 'tree_path', header: 'ツリーパス', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'set_flg', binding: 'set_flg', header: '一式フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'min_quantity', binding: 'min_quantity', header: '最小単位数', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'order_lot_quantity', binding: 'order_lot_quantity', header: '発注ロット数', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'intangible_flg', binding: 'intangible_flg', header: '無形品フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'draft_flg', binding: 'draft_flg', header: '仮登録フラグ', visible: false, isReadOnly: true }]},
                { cells: [{ name: 'sales_use_flg', binding: 'sales_use_flg', header: '販売額フラグ', visible: false, isReadOnly: true }]},

                { cells: [{ name: 'quantity_per_case', binding: 'quantity_per_case', header: '入り数', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'set_kbn', binding: 'set_kbn', header: 'セット区分', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_big_id', binding: 'class_big_id', header: '大分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_middle_id', binding: 'class_middle_id', header: '中分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_small_id', binding: 'class_small_id', header: '小分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'tree_species', binding: 'tree_species', header: '樹種', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'grade', binding: 'grade', header: '等級', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'length', binding: 'length', header: '長さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'thickness', binding: 'thickness', header: '厚み', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'width', binding: 'width', header: '幅', visible: false, isReadOnly: true }] },
                
            ]
        },


        // 子部品取込ダイアログを開く
        showChildPartsImport(){
            var gridDataList = this.getVisibleChkDataList();
            if(gridDataList.length === 1){
                if(gridDataList[0].set_flg === this.FLG_ON){
                    this.isShowChildPartsImportModal = true;
                }else{
                    alert(MSG_ERROR_NO_SELECT_SET_PRODUCT);
                }
            }else if(gridDataList.length === 0){
                alert(MSG_ERROR_NO_SELECT);
            }else{
                alert(MSG_ERROR_MULTIPLE_SELECTION);
            }
        },
        // 子部品CSV取込
        childPartsImport(){
            try {
                this.startProcessing();
                // エラー初期化
                this.childPartsImportFileInfo.errorMsg = '';
                if(this.childPartsImportFileInfo.content === null){
                    return;
                }
                if (this.childPartsImportFileInfo.accept.indexOf(this.getExt(this.childPartsImportFileInfo.content.name, true)) === -1) {
                    // エラー時の処理
                    alert(MSG_ERROR_ILLEGAL_FILE_EXTENSION);
                    return;
                }else{
                    this.loading = true;
                    var params = new FormData();
                    params.append('child_parts_file', this.childPartsImportFileInfo.content);
                    params.append('col_name_list', JSON.stringify(this.childPartsImportFileInfo.COL_NAME_LIST));
                    params.append('maker_id', this.order.maker_id);
                    params.append('customer_id', this.rmUndefinedZero(this.order.customer_id));

                    axios.post('/order-detail/csv-to-array', params)
                    .then( function (response) {
                        this.loading = false
                        var response = response.data;

                        var resProductList = response['productList'];
                        var productList = Object.keys(resProductList).map(function (key) {return resProductList[key]});
                        // グリッドにセットする
                        this.setGridChilPartsData(response['csv'], response['colNumberList'], productList, response['salesProductPriceList']);
                    }.bind(this))

                    .catch(function (error) {
                        if(error.request) {
                            this.childPartsImportFileInfo.errorMsg = MSG_ERROR_FILE_RESELECTION;
                        } 
                        this.loading = false
                    }.bind(this))
                }
            } finally {
                setTimeout(() => {
                    this.endProcessing();
                }, 500);   
            }
        },
        /**
         * グリッドに子部品行を追加する
         * @param csvDataList
         */
        setGridChilPartsData(csvDataList, COL_NUMBER_LIST, productList, salesProductPriceList){
            var gridDataList = this.getVisibleChkDataList();
            var parentGridRow = gridDataList[0];
            var parentRowIndex = this.wjGridDetails.collectionView.sourceCollection.findIndex((value) => {
                return (value.quote_detail_id === parentGridRow.quote_detail_id);
            });
            var uniqueKey = this.createGridUniqueKey();

            if (csvDataList.length >= 1) {
                var header = csvDataList[0];
                var errorMsg = [];
                for(let physicalName in this.childPartsImportFileInfo.COL_NAME_LIST){
                    var colName = this.childPartsImportFileInfo.COL_NAME_LIST[physicalName];
                    if(COL_NUMBER_LIST[physicalName] === -1){
                        errorMsg.push(`「${colName}」`);
                    }
                }

                var newRowList = [];
                if(errorMsg.length === 0){
                    // 先頭行の発注者希望納期を退避
                    var desiredDeliveryDate = '';
                    for(var i = 1; i<csvDataList.length; i++){
                        var csvRow = csvDataList[i];
                        var initRow = Vue.util.extend({}, this.INIT_ROW);
                        if(i === 1){
                            // 先頭行の発注者希望納期をヘッダーの納期希望日にセットする
                            desiredDeliveryDate     = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['desired_delivery_date']]);
                            desiredDeliveryDate     = desiredDeliveryDate !== '' ? moment(desiredDeliveryDate).format(FORMAT_DATE) : null;  
                        }
                        initRow.arrival_plan_date       = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['arrival_plan_date']]);
                        if(initRow.arrival_plan_date !== ''){
                            initRow.arrival_plan_date       = moment(initRow.arrival_plan_date).format(FORMAT_DATE);
                            initRow.hope_arrival_plan_date  = this.getNextDay(initRow.arrival_plan_date);
                        }
                        initRow.product_code    = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['product_code']]);
                        initRow.product_name    = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['product_name']]);
                        
                        initRow.order_quantity  = this.rmInvalidNumZero(parseFloat(csvRow[COL_NUMBER_LIST['order_quantity']]));
                        initRow.unit            = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['unit']]);
                        initRow.regular_price   = this.rmInvalidNumZero(parseInt(csvRow[COL_NUMBER_LIST['regular_price']]));
                        initRow.cost_unit_price = this.rmInvalidNumZero(parseInt(csvRow[COL_NUMBER_LIST['cost_unit_price']]));
                        initRow.memo            = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['memo2']]);
                        initRow.min_quantity    = this.rmInvalidNumZero(parseFloat(csvRow[COL_NUMBER_LIST['min_quantity']]));
                        initRow.stock_unit      = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['stock_unit']]);
                        initRow.order_lot_quantity    = this.rmInvalidNumZero(parseFloat(csvRow[COL_NUMBER_LIST['order_lot_quantity']]));
                        var memo                = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['memo']]);
                        var model               = this.rmUndefinedBlank(csvRow[COL_NUMBER_LIST['model']]);

                        if(model !== ''){
                            initRow.product_name= initRow.product_name + ' ' + model;
                        }

                        if(memo !== ''){
                            initRow.memo        = initRow.memo + '　　　' + memo;
                        }

                        // 最小単位数が0または空白の場合「1」をセット
                        if(initRow.min_quantity === 0){
                            initRow.min_quantity = this.INIT_ROW_MIN_QUANTITY;
                        }
                        // 発注ロット数が0または空白の場合「1」をセット
                        if(initRow.order_lot_quantity === 0){
                            initRow.order_lot_quantity = this.INIT_ROW_MIN_QUANTITY;
                        }


                        var product = null;

                        var tmpProducts = productList.filter(rec => {
                            return (rec.product_code === initRow.product_code);
                        });
                        if(tmpProducts.length === 1){
                            product = tmpProducts[0];
                        }

                        initRow.maker_id                = this.order.maker_id;              // メーカーID
                        initRow.maker_name              = this.order.maker_name;            // メーカー名
                        initRow.supplier_id             = this.order.supplier_id;           // 仕入先ID
                        initRow.supplier_name           = this.order.supplier_name;         // 仕入先名
                        initRow.quote_quantity          = initRow.order_quantity;           // 見積数(発注数)
                        // initRow.min_quantity            = this.INIT_ROW_MIN_QUANTITY        // 最小単位数
                        // initRow.order_lot_quantity      = initRow.min_quantity;             // 発注ロット数
                        initRow.intangible_flg          = this.FLG_OFF;                     // 無形品フラグ

                        initRow.child_parts_flg         = this.FLG_ON;                      // 子部品フラグ
                        initRow.product_auto_flg        = true;                             // 1回限り登録フラグ
                         initRow.product_definitive_flg = false;                            // 本登録フラグ
                        initRow.construction_id         = parentGridRow.construction_id;    // 工事区分ID
                        initRow.parent_quote_detail_id  = parentGridRow.quote_detail_id;    // 親見積明細ID
                        initRow.tree_path               = parentGridRow.tree_path + '_' + initRow.parent_quote_detail_id;   // ツリーパス
                        initRow.unique_key              = uniqueKey;                        // 一意キー

                        if(product !== null){
                            // 商品マスタの情報をセットする
                            initRow.product_id          = product.product_id;               // 商品ID
                            initRow.stock_unit          = product.stock_unit;               // 管理数単位
                            initRow.min_quantity        = parseFloat(product.min_quantity); // 最小単位数
                            initRow.order_lot_quantity  = parseFloat(product.order_lot_quantity);   // 発注ロット数
                            initRow.intangible_flg      = product.intangible_flg;           // 無形品フラグ
                            //initRow.draft_flg           = product.draft_flg;                // 仮登録フラグ
                            initRow.draft_flg           = this.FLG_OFF

                            initRow.quantity_per_case   = product.quantity_per_case;         // 入り数
                            initRow.set_kbn             = product.set_kbn;                   // セット区分
                            initRow.class_big_id        = product.class_big_id;              // 大分類ID
                            initRow.class_middle_id     = product.class_middle_id;           // 中分類ID
                            initRow.class_small_id      = product.class_small_id_1;          // 小分類ID
                            initRow.tree_species        = product.tree_species;              // 樹種
                            initRow.grade               = product.grade;                     // 等級
                            initRow.length              = product.length;                    // 長さ
                            initRow.thickness           = product.thickness;                 // 厚み
                            initRow.width               = product.width;                     // 幅
                            // 得意先別標準があれば販売単価をセットする　無ければ標準販売単価をセットする
                            this.setTreeGridUnitPriceNew(initRow, false, salesProductPriceList[initRow.product_id], true);       // 販売単価
                        }
                        // エラーチェック
                        this.setCsvRowErrMsg(errorMsg, initRow, i);
                        
                        // 行計算
                        this.calcTreeGridChangeRegularPriceEx(initRow);
                        this.calcTreeGridRowData(initRow, 'order_quantity');
                        newRowList.push(initRow);
                        uniqueKey++;
                    }


                    if(errorMsg.length === 0){
                        // 先頭行の発注者希望納期をヘッダーの納期希望日にセットする
                        this.order.desired_delivery_date    = desiredDeliveryDate;
                        this.wjDesiredDeliveryDate.text     = this.rmUndefinedBlank(desiredDeliveryDate);

                        var insertIndex = parentRowIndex + 1;
                        for(;insertIndex < this.wjGridDetails.collectionView.sourceCollection.length; insertIndex++){
                            if(parentGridRow.quote_detail_id !== this.wjGridDetails.collectionView.sourceCollection[insertIndex].parent_quote_detail_id){
                                break;
                            }
                        }
                        for(let i in newRowList){
                            var initRow = newRowList[i];
                            this.wjGridDetails.collectionView.sourceCollection.splice(insertIndex + parseInt(i), 0, initRow);
                        }
                        // 行追加
                        // 一式行のフラグ変更
                        parentGridRow.layer_flg         = this.FLG_ON;  // 階層フラグ
                        parentGridRow.sales_use_flg     = this.FLG_ON;  // 販売額利用
                        parentGridRow.intangible_flg    = this.FLG_ON;  // 無形品フラグ
                        parentGridRow.product_id        = this.INIT_ROW.product_id;  // 商品ID
                        parentGridRow.order_quantity    = parentGridRow.order_lot_quantity;  // 発注数
                        this.setParentRowPrice(parentGridRow.quote_detail_id);
                        this.wjGridDetails.collectionView.refresh();
                        this.isShowChildPartsImportModal = false;
                    }else{
                        if(errorMsg.length > this.childPartsImportFileInfo.DISPLAY_ERR_CNT){
                            errorMsg.splice(this.childPartsImportFileInfo.DISPLAY_ERR_CNT, errorMsg.length - this.childPartsImportFileInfo.DISPLAY_ERR_CNT);
                        }
                        
                        this.childPartsImportFileInfo.errorMsg = errorMsg.join('<br>');
                        this.childPartsImportFileInfo.errorMsg += '<br>' + this.childPartsImportFileInfo.ERR_MSG_FILE_CONFIRM;
                    }
                }else{
                    this.childPartsImportFileInfo.errorMsg = errorMsg.join('') + this.childPartsImportFileInfo.ERR_MSG_COL_NOT_FOUND;
                }
            } else {
                this.childPartsImportFileInfo.errorMsg = MSG_ERROR_NO_DATA;
            }
        },


        /* 便利系の関数 */
        // 現在日時を取得
        getTodaySlashFormat(){
            var date = new Date();
            var result = date.getFullYear();
            result += '/'+(date.getMonth() + 1).toString().padStart(2, '0') +'/'+ date.getDate().toString().padStart(2, '0');
            return result;
        },
        // 翌日を取得
        getNextDay(yyyyMMdd){
            return moment(yyyyMMdd).add(1, 'd').format(FORMAT_DATE);
        },
        // ハイフン除去
        trimHyphen(str){
            return str.replace(/-/g, "");
        },
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
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
        /**
         * ファイル取込時のエラーチェック
         * @param errorMsg
         * @param initRow
         * @param i
         */
        setCsvRowErrMsg(errorMsg, initRow, i){
            // 論理名
            const COL_NAME_LIST = this.childPartsImportFileInfo.COL_NAME_LIST;
            // 必須チェック
            if(this.rmUndefinedBlank(initRow.product_code) === ''){
                errorMsg.push(this.createRequiredErrMsg(i, COL_NAME_LIST.product_code));
            }
            // 最小単位と発注ロット数の倍数チェック
            if(!this.treeGridQuantityIsMultiple(initRow.order_lot_quantity, initRow.min_quantity)){
                errorMsg.push(this.createMultipleErrMsg(i, COL_NAME_LIST.order_lot_quantity, COL_NAME_LIST.min_quantity));
            }
            // 発注ロット数と発注数の倍数チェック
            if(!this.treeGridQuantityIsMultiple(initRow.order_quantity, initRow.order_lot_quantity)){
                errorMsg.push(this.createMultipleErrMsg(i, COL_NAME_LIST.order_quantity, COL_NAME_LIST.order_lot_quantity));
            }
        },
        /**
         * ファイル取込時の必須エラーメッセージを生成
         * @param rowNo
         * @param key
         */
        createRequiredErrMsg(rowNo, key){
            return this.childPartsImportFileInfo.ERR_MSG_REQUIRED.replace('$n',rowNo).replace('$key', key);
        },
        /**
         * ファイル取込時の倍数エラーメッセージを生成
         * @param rowNo
         * @param key
         */
        createMultipleErrMsg(rowNo, key1, key2){
            return this.childPartsImportFileInfo.ERR_MSG_MULTIPLE.replace('$n',rowNo).replace('$key1', key1).replace('$key2', key2);
        },
        // グリッドの商品変更時の最小単位数チェック　商品IDのクリアなど
        changingProduct(product, row, isCode){
            var isCancel = false;
            if(row.layer_flg === this.FLG_OFF){
                var selectedItem = product.selectedItem;

                var isErr = false;

                if(selectedItem !== null){
                    if(selectedItem.product_id === PRODUCT_AUTO_COMPLETE_SETTING.DEFAULT_PRODTCT_ID){
                        // 上限数メッセージを取得した
                        alert(MSG_ERROR_NO_SELECT_PRODUCT);
                        isErr = true;
                    }else{
                        if(!this.bigNumberEq(row.min_quantity, selectedItem.min_quantity)){
                            alert(MSG_ERROR_CHANGE_PRODUCT_DUE_TO_MIN_QUANTITY_DIFFERENT + ' 現在の最小単位数：' + row.min_quantity);
                            isErr = true;
                        }else if(this.rmUndefinedZero(selectedItem.maker_id) !== 0 && selectedItem.maker_id !== row.maker_id){
                            // 選択した商品のメーカーが違う
                            alert(MSG_ERROR_ORDER_COMPLETE_MAKER_CHANGE);
                            isErr = true;
                        }else if(row.intangible_flg === this.FLG_ON){
                            // 無形品
                            if(selectedItem.intangible_flg !== this.FLG_ON){
                                // 無形品から有形品に変更
                                alert(MSG_ERROR_FROM_INTANGIBLE_TO_TANGIBLE);
                                isErr = true;
                            }
                        }else{
                            // 有形品
                            if(selectedItem.intangible_flg === this.FLG_ON){
                                // 有形品から無形品に変更
                                alert(MSG_ERROR_FROM_TANGIBLE_TO_INTANGIBLE);
                                isErr = true;
                            }
                        }
                    }
                }else{
                    if(row.intangible_flg === this.FLG_ON){
                        if(isCode && this.rmUndefinedBlank(product.control.text) !== ''){
                            // 有形品の商品コードが空白
                            alert(MSG_ERROR_CHANGE_INTANGIBLE_PRODUCT_CODE);
                            isErr = true;
                        }
                    }else{
                        if(isCode && this.rmUndefinedBlank(product.control.text) === ''){
                            // 無形品の商品コードを変更
                            alert(MSG_ERROR_REQUIRED_TANGIBLE_PRODUCT_CODE);
                            isErr = true;
                        }
                    }
                }

                if (isCode && !isErr) {
                    if (!this.isMatchPattern(PRODUCT_CODE_REGEX, product.control.text)) {
                        alert(MSG_ERROR_ILLEGAL_VALUE + "\n" + MSG_ERROR_PRODUCT_CODE_REGEX);
                        isErr = true;
                    }   
                }

                if(isErr){
                    // 入力していた値の復元
                    if(isCode){
                        product.control.text = row.product_code;
                    }else{
                        product.control.text = row.product_name;
                    }

                    isCancel = true;
                }else{
                    if(selectedItem === null){
                        // row.product_id          = this.INIT_ROW.product_id;
                        // row.arrival_plan_date   = this.INIT_ROW.arrival_plan_date;
                        if(isCode){
                            row.product_id          = this.INIT_ROW.product_id;
                            row.arrival_plan_date   = this.INIT_ROW.arrival_plan_date;
                            if(this.rmUndefinedBlank(product.control.text) === ''){
                                row.intangible_flg = this.FLG_ON;
                            }else{
                                row.intangible_flg = this.FLG_OFF;
                            }
                        }
                    }
                    if(!isCode && this.rmUndefinedBlank(product.control.text) !== row.product_name && row.intangible_flg === this.FLG_ON){
                        // 商品マスタから選択して商品コードがある無形品は商品名変更時に商品コードを空白にする
                        row.product_code = this.INIT_ROW.product_code;
                    }
                }
                
            }
            return isCancel;
        },
        // グリッドの商品変更時
        async changeProduct(product, row, isCode){
            if(product !== null){
                // 商品を変更したか
                if(row.product_id !== product.product_id && row.is_cancel !== true){
                    // 非同期で取得
                    var productInfo                 = await this.getProductInfo(product.product_id, this.rmUndefinedZero(this.order.customer_id));
                    if(productInfo !== undefined){
                        var productData             = productInfo['product'];
                        
                        row.product_code    = productData.product_code;                     // 商品コード
                        if(isCode){
                            row.product_name    = productData.product_name;                 // 商品名
                        }else{
                            // row.product_code    = productData.product_code;                 // 商品コード
                        }
                        row.product_id          = productData.product_id;                   // 商品ID
                        row.model               = productData.model;                        // 型式・規格
                        row.stock_unit          = productData.stock_unit;                   // 管理数単位
                        row.min_quantity        = parseFloat(productData.min_quantity);     // 最小単位数
                        row.order_lot_quantity  = parseFloat(productData.order_lot_quantity);   // 発注ロット数
                        row.intangible_flg      = productData.intangible_flg;               // 無形品フラグ
                        row.draft_flg           = productData.draft_flg;                    // 仮登録フラグ

                        row.quantity_per_case   = productData.quantity_per_case;         // 入り数
                        row.set_kbn             = productData.set_kbn;                   // セット区分
                        row.class_big_id        = productData.class_big_id;              // 大分類ID
                        row.class_middle_id     = productData.class_middle_id;           // 中分類ID
                        row.class_small_id      = productData.class_small_id_1;          // 小分類ID
                        row.tree_species        = productData.tree_species;              // 樹種
                        row.grade               = productData.grade;                     // 等級
                        row.length              = productData.length;                    // 長さ
                        row.thickness           = productData.thickness;                 // 厚み
                        row.width               = productData.width;                     // 幅

                        if(!this.treeGridQuantityIsMultiple(row.order_quantity, row.order_lot_quantity)){
                            row.order_quantity = this.INIT_ROW.order_quantity;            // 発注数
                        }

                        row.regular_price       = productData.price;                 // 定価
                        row.unit                = productData.unit                   // 仕入単位
                        this.setTreeGridUnitPriceNew(row, true, productInfo['costProductPriceList'], false);       // 仕入単価
                        this.calcTreeGridChangeUnitPrice(row, true);                 // 仕入掛率再計算
                        this.calcTreeGridChangeUnitPrice(row, false);                // 販売掛率再計算

                        row.arrival_plan_date   = this.INIT_ROW.arrival_plan_date;   // 入荷予定日

                        // 1回限りチェックを外す
                        if (row.child_parts_flg === this.FLG_OFF) {
                            row.product_auto_flg = this.FLG_OFF;
                        }

                        if(this.rmUndefinedBlank(product.end_date) !== '' &&
                        this.strToTime(product.end_date + ' 00:00:00') < this.strToTime(this.todayYYYYMMDDSlashFormat)){
                            if(this.rmUndefinedZero(product.new_product_id) === 0){
                                // 新品番無しの廃版
                                alert(MSG_WARNING_END_DATE);
                            }else{
                                // 新品番あり
                                alert(MSG_WARNING_NEW_PRODUCT)
                            }
                        }
                        // 再計算
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.wjGridDetails.refresh();
                    }
                }
                    
                
            }else{

            }
        },
        /**
         * 仕入/販売区分変更時に呼び出す
         * @param row   行
         */
        async changeCostSalesKbn(row){
            // 非同期で取得
            var unitPriceInfo           = await this.getUnitPrice(row.product_id, this.rmUndefinedZero(this.order.customer_id));
            if(unitPriceInfo !== undefined){
                this.setTreeGridUnitPriceNew(row, true, unitPriceInfo['costProductPriceList'], false);       // 仕入単価
                this.calcTreeGridChangeUnitPrice(row, true);
                this.calcTreeGridRowData(row, 'order_quantity');
                this.wjGridDetails.refresh();
            }
        },

        // 希望出荷予定日と入荷予定日を入れられるか
        isHopeArrivalDateValid(row){
            var result = true;
            if(row.layer_flg === this.FLG_ON){
                result = false;
            }else if(row.set_flg === this.FLG_ON){
                result = false;
            }else if(row.intangible_flg === this.FLG_ON){
                result = false;
            }
            return result;
        },
        // 入荷予定日
        isArrivalDateValid(row){
            var result = true;
            if(this.order.status !== this.orderStatusList.val.ordered){
                // 発注済みでない
                result = false;
            }else{
                if(row.child_parts_flg === this.FLG_ON){
                    if(row.intangible_flg === this.FLG_ON){
                        // 無形品 ありえない想定
                        //result = false;
                    }
                }else{
                    if(row.layer_flg === this.FLG_ON){
                        // 階層
                        result = false;
                    }else if(row.set_flg === this.FLG_ON){
                        // 一式
                        result = false;
                    }else if(this.rmUndefinedZero(row.product_id) === 0 && row.intangible_flg === this.FLG_OFF){
                        // 商品マスタに存在しない有形品
                        result = false;
                    }else if(row.draft_flg === this.FLG_ON){
                        // 仮登録
                        result = false;
                    }
                }
            }
            return result;
        },
        /**
         * グリッド行の連番を生成する
         */
        createGridUniqueKey(){
            var uniqueKey = 0;
            if(this.wjGridDetails.collectionView.sourceCollection.length >= 1){
                uniqueKey = this.wjGridDetails.collectionView.sourceCollection.reduce((a,b)=>parseInt(a.unique_key) > parseInt(b.unique_key)?a:b).unique_key;
                uniqueKey++;
            }
            return uniqueKey;
        },
        /**
         * 行追加時など型変換をした新規行を返す
         * @param row
         * @returns rec
         */
        getInitRow(row){
            var rec = Vue.util.extend({}, this.INIT_ROW);
            rec['product_id']       = row.product_id;
            rec['product_code']     = row.product_code;
            rec['product_name']     = row.product_name;
            rec['model']            = row.model;
            rec['maker_id']         = this.order.maker_id;
            rec['maker_name']       = this.order.maker_name;
            rec['supplier_id']      = this.order.supplier_id;
            rec['supplier_name']    = this.order.supplier_name;
            rec['order_quantity']   = this.rmUndefinedZero(row.order_quantity);
            rec['unit']             = row.unit;
            rec['stock_unit']       = row.stock_unit;
            rec['regular_price']    = parseInt(this.rmUndefinedZero(row.regular_price));
            rec['cost_kbn']         = parseInt(this.rmUndefinedZero(row.cost_kbn));
            rec['cost_unit_price']  = parseInt(this.rmUndefinedZero(row.cost_unit_price));
            rec['sales_unit_price'] = parseInt(this.rmUndefinedZero(row.sales_unit_price));
            rec['cost_makeup_rate'] = parseFloat(this.rmUndefinedZero(row.cost_makeup_rate));
            rec['sales_makeup_rate']= parseFloat(this.rmUndefinedZero(row.sales_makeup_rate));
            
            rec['quote_detail_id']  = row.quote_detail_id;
            rec['construction_id']  = row.construction_id;
            rec['parent_quote_detail_id']  = row.parent_quote_detail_id;
            rec['layer_flg']        = parseInt(this.rmUndefinedZero(row.layer_flg));
            rec['tree_path']        = this.rmUndefinedBlank(row.tree_path);
            rec['set_flg']          = parseInt(this.rmUndefinedZero(row.set_flg));
            rec['min_quantity']     = parseFloat(this.rmUndefinedZero(row.min_quantity));
            rec['order_lot_quantity']   = parseFloat(this.rmUndefinedZero(row.order_lot_quantity));
            rec['quote_quantity']   = parseFloat(this.rmUndefinedZero(row.quote_quantity));           
            rec['intangible_flg']   = parseInt(this.rmUndefinedZero(row.intangible_flg));
            rec['draft_flg']        = parseInt(this.rmUndefinedZero(row.draft_flg));
            rec['reserve_quantity'] = parseFloat(this.rmUndefinedZero(row.reserve_quantity));
            rec['sales_kbn']        = parseInt(this.rmUndefinedZero(row.sales_kbn));
            rec['sales_use_flg']    = parseInt(this.rmUndefinedZero(row.sales_use_flg));

            rec['quantity_per_case']= parseFloat(this.rmUndefinedZero(row.quantity_per_case));    // 入り数
            rec['set_kbn']          = this.rmUndefinedBlank(row.set_kbn);                   // セット区分
            rec['class_big_id']     = parseInt(this.rmUndefinedZero(row.class_big_id));     // 大分類ID
            rec['class_middle_id']  = parseInt(this.rmUndefinedZero(row.class_middle_id));  // 中分類ID
            rec['class_small_id']   = parseInt(this.rmUndefinedZero(row.class_small_id)); // 小分類ID
            rec['tree_species']     = parseInt(this.rmUndefinedZero(row.tree_species)); // 樹種
            rec['grade']            = parseInt(this.rmUndefinedZero(row.grade));        // 等級
            rec['length']           = parseInt(this.rmUndefinedZero(row.length));       // 長さ
            rec['thickness']        = parseInt(this.rmUndefinedZero(row.thickness));    // 厚み
            rec['width']            = parseInt(this.rmUndefinedZero(row.width));        // 幅

            return rec;
        },
        /**
         * 【定価変更時】に呼び出す
         * cost_unit_price(仕入単価)を計算してセットする
         * sales_makeup_rate(販売掛率)を計算してセットする
         * 
         * 掛率が0の場合と販売単価計算時は単価から掛率を算出する
         * 定価が0の場合は掛率を0にする
         * @param {*} row 
         */
        calcTreeGridChangeRegularPriceEx(row){
            row.regular_price = this.roundDecimalStandardPrice(row.regular_price);
            if(this.bigNumberGt(row.regular_price, 0)){
                if(this.bigNumberEq(row.cost_makeup_rate, 0)){
                    this.calcTreeGridChangeUnitPrice(row, true);
                }else{
                    // 仕入単価 = 定価 * 仕入掛率 / 100
                    row.cost_unit_price = this.roundDecimalStandardPrice(this.bigNumberDiv(this.bigNumberTimes(row.regular_price, row.cost_makeup_rate, true), 100, true));
                }
                this.calcTreeGridChangeUnitPrice(row, false);
            }else{
                row.cost_makeup_rate = 0;
                row.sales_makeup_rate = 0;
            }
        },
        /**
         * 一式の金額に反映する(子部品時にのみ呼び出す)
         * @param {*} row 編集した行の親見積明細ID
         */
        setParentRowPrice(parentQuoteDetailId){
            var gridDataList = this.wjGridDetails.collectionView.sourceCollection;
            var currentChildPartsList = this.childPartsList[parentQuoteDetailId];
            var costTotal = this.toBigNumber(0);

            var currentGridDataList = this.getChildPartsList(parentQuoteDetailId, false);

            var tmpChildPartsList = [];
            if(currentChildPartsList !== undefined){
                tmpChildPartsList = currentChildPartsList.filter((rec) => {
                    var res = true;
                    for(let i in currentGridDataList){
                        if(parseInt(rec.quote_detail_id) === parseInt(currentGridDataList[i].quote_detail_id)){
                            res = false;
                            break;
                        }
                    }
                    return res;
                });
            }

            // 発注している子部品
            // 発注数 + 引当数 と 見積数　を比較し大きい方を金額計算の対象数量とする
            for(let i in currentGridDataList){
                // 入力値 + 過去の発注数
                var reserveQuantity = this.bigNumberPlus(currentGridDataList[i].order_quantity, currentGridDataList[i].reserve_quantity, true);
                var quantity = currentGridDataList[i].quote_quantity;
                if(this.bigNumberGt(reserveQuantity, quantity)){
                    quantity = reserveQuantity;
                }
                costTotal = this.bigNumberPlus(costTotal, this.bigNumberTimes(quantity, currentGridDataList[i].cost_unit_price, true), true);
            }
            // 発注していない子部品
            for(let i in tmpChildPartsList){
                costTotal   = this.bigNumberPlus(costTotal, tmpChildPartsList[i].cost_total, true);
            }
            
            var setRow = gridDataList.find((rec) => {
                return parentQuoteDetailId === rec.quote_detail_id;
            });
            if(setRow !== undefined){
                setRow.cost_total       = costTotal.toNumber();
                setRow.cost_unit_price  = this.bigNumberDiv(costTotal, setRow.order_quantity);
                this.calcTreeGridChangeUnitPrice(setRow, true);
                this.calcTreeGridRowData(setRow, 'order_quantity');
            }
            
        },
        /**
         * 子部品を取得する
         * @param parentQuoteDetailId 親見積もり明細ID
         * @param isDelete 削除フラグ
         */
        getChildPartsList(parentQuoteDetailId, isDelete){
            var currentGridDataList = this.wjGridDetails.collectionView.sourceCollection.filter((rec) => {
                var result = false;
                if(isDelete === undefined){
                    if(parentQuoteDetailId === rec.parent_quote_detail_id){
                        result = true;
                    }
                }else{
                    if(parentQuoteDetailId === rec.parent_quote_detail_id && rec.is_delete === isDelete){
                        result = true;
                    }
                }
                return result;
            });
            return currentGridDataList;
        },
        /**
         * 表示されている行でチェックが付いている行を返す
         */
        getVisibleChkDataList(){
            var result = [];
            for (var i in this.wjGridDetails.collectionView.items) {
                if (this.wjGridDetails.collectionView.items[i].chk) {
                    result.push(this.wjGridDetails.collectionView.items[i]);
                }
            }
            return result;
        }
    }
};
</script>
<style>
.order-info {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-delete.ex {
    width: 100%;
    height: 100%;
}
/* グリッド */
.wj-multirow {
    height: 500px;
    margin: 6px 0;
}
.container-fluid.grid-area{
    margin-top: 20px;
    padding: 10px;
    border: 1px solid rgba(0, 0, 0, .3);
}
i.el-icon-plus {
    font-size: 35px;
    vertical-align: top;
    cursor: pointer;
    color: #0040FF;
}
i.el-icon-plus:hover {
    color: #0101DF;
}
.modal-grid{
    background: #ffffff;
    height: 300px;
    margin-top: 30px;
    margin-bottom: 30px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.dialog-footer{
    margin-top: 5px;
}

.grid-fixlog-cell{
    display: block !important;
    width: 100%;
    height: 25px;
    line-height: 25px;
    margin: 2px;
    text-align: center;
    background-color: #FF2D55;
    color:#ffffff;
}
.grid-addorder-cell{
    display: block !important;
    width: 100%;
    height: 25px;
    line-height: 25px;
    margin: 2px;
    text-align: center;
    background-color: #4CD964;
    color:#ffffff;
}
.grid-fixlog-cell.disabled, .grid-addorder-cell.disabled{
    background-color: transparent;
}
.single-grid-btn-sm{
    border: none; 
    border-radius: 0px;
    width: 100%;
    height: 100%;
    padding:0px;
}
.input-group-circle-btn{
    font-size: 26px;
    padding: 4px !important;
    border: none;
}
.file-operation-area{
    overflow-x: auto;
    white-space: nowrap;
}
.detail-file-ul{
    list-style: none;
    padding-left: 0;
}
.detail-file-li{
    margin-bottom:3px;
}
.file-up{
    margin-bottom: 0px;
    vertical-align: bottom;
}
.svg-icon {
    height: 25px !important;
    width: 25px !important;
}
.btn-file-download {
    margin-left: 1px !important;
}

/* フォーカス時フォントカラーは白 */
/* .wj-cell.wj-state-selected {
    color: #FFFFFF !important;
} */
.wj-cells .wj-cell.wj-state-selected {
    background: #0085c7 !important;
}
.wj-cells .wj-cell.wj-state-multi-selected {
    background: #80adbf !important;
}
</style>
