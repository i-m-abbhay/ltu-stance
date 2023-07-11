<template>
    <div>
        <loading-component :loading="loading" />

        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 text-right">
                        <label v-show="base.complete_flg == FLG_ON" class="attention-color">案件完了済&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                        <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked" v-bind:disabled="base.complete_flg == FLG_ON">ロック解除</button>
                        <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn" v-bind:disabled="base.complete_flg == FLG_ON">編集</button>
                        <div class="pull-right">
                            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn)">編集中</p>
                        </div>
                    </div>
                </div>

                <div class="input-area-body area-border">
                    <div class="input-area-body-top">
                        <div class="row">
                            <div class="col-md-2">
                                <p class="item-label">発注登録情報</p>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-2">
                                <label class="control-label">得意先名</label>
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="base.customer_name">
                            </div>
                            <div class="col-md-3">
                                <label class="control-label">案件名</label>
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="base.matter_name">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">案件番号</label>
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="base.matter_no">
                            </div>
                            <div class="col-md-1">
                                                    
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">部門名</label>
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="base.department_name">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">担当者名</label>
                                <input type="text" class="form-control" v-bind:readonly="true" v-model="base.staff_name">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-2">
                                <label class="control-label">見積受注総額</label>
                                <input type="text" class="form-control text-right" v-bind:readonly="true" v-bind:value="base.sales_total|comma_format">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">発注総額</label>
                                <input type="text" class="form-control text-right" v-bind:readonly="true" v-bind:value="base.sum_cost_total|comma_format">
                            </div>
                            <div class="col-md-2">
                                <label class="control-label">見積受注総額-発注総額</label>
                                <input type="text" class="form-control text-right" v-bind:readonly="true" v-bind:value="(base.sales_total - base.sum_cost_total)|comma_format">
                            </div>

                            <div class="col-md-6">
                                <label class="control-label">現場住所</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" v-bind:readonly="true" v-bind:value="(rmUndefinedBlank(base.matter_address1) + rmUndefinedBlank(base.matter_address2))">
                                    <span class="input-group-btn">
                                        <button type="button" class="btn btn-save form-control" v-on:click="inputMaterAddress" v-bind:disabled="(isReadOnly)">住所登録</button>
                                    </span>
                                    <span class="input-group-btn">
                                        <el-button icon="el-icon-refresh" circle class="input-group-circle-btn" v-bind:disabled="(isReadOnly)" v-on:click="getMatterInfo(base.matter_id)"></el-button>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="input-area-body-middle area-border container-fluid">
                            <div class="row">
                                <div class="col-md-9">
                                    <div class="row">
                                        <div class="col-md-3" v-bind:class="{'has-error': (errors['maker_id'] != '') }">
                                            <label class="control-label">メーカー</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="supplier_name" 
                                                display-member-path="supplier_name" 
                                                :items-source="makerList" 
                                                selected-value-path="id"
                                                :selected-value="main.maker_id"
                                                :isDisabled="isReadOnly"
                                                :isRequired=false
                                                :selectedIndexChanged="selectHeadMaker"
                                                :textChanged="setTextChanged"
                                                :max-items="makerList.length"
                                            >
                                            </wj-auto-complete>
                                            <p class="text-danger">{{ errors['maker_id'] }}</p>
                                        </div>
                                        <div class="col-md-3" v-bind:class="{'has-error': (errors['supplier_id'] != '') }">
                                            <label class="control-label">仕入先</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="supplier_name" 
                                                display-member-path="supplier_name" 
                                                :items-source="headSupplierList" 
                                                selected-value-path="unique_key"
                                                :selected-value="(main.maker_id+'_'+main.supplier_id)"
                                                :isDisabled="isReadOnly"
                                                :isRequired=false
                                                :selectedIndexChanged="selectHeadSupplier"
                                                :textChanged="setTextChanged"
                                                :max-items="headSupplierList.length"
                                            >
                                            </wj-auto-complete>
                                            <p class="text-danger">{{ errors['supplier_id'] }}</p>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="control-label">メーカー／仕入先担当者</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="name" 
                                                display-member-path="name" 
                                                :items-source="headPersonList" 
                                                selected-value-path="id"
                                                :selected-value="main.person_id"
                                                :isDisabled="isReadOnly"
                                                :isRequired=false
                                                :selectedIndexChanged="selectPerson"
                                                :textChanged="setTextChanged"
                                                :max-items="headPersonList.length"
                                            >
                                            </wj-auto-complete>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="control-label">入荷先</label>
                                            <wj-auto-complete class="form-control"
                                                search-member-path="warehouse_name" 
                                                display-member-path="warehouse_name" 
                                                :items-source="deliveryAddressList" 
                                                selected-value-path="unique_key"
                                                :selected-value="(main.delivery_address_kbn + '_' + main.delivery_address_id)"
                                                :isDisabled="isReadOnly"
                                                :isRequired=false
                                                :selectedIndexChanged="selectDeliveryAddress"
                                                :initialized="initDeliveryAddress"
                                                :textChanged="setTextChanged"
                                                :max-items="deliveryAddressList.length"
                                            >
                                            </wj-auto-complete>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-7" v-bind:class="{'has-error': (errors['delivery_address'] != '') }">
                                            <label class="control-label">入荷先住所</label>
                                            <input type="text" class="form-control" v-bind:readonly="true" v-model="main.delivery_address">
                                            <p class="text-danger">{{ errors['delivery_address'] }}</p>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="control-label">地図印刷</label>
                                            <div class="">
                                                <el-checkbox class="col-md-3" v-model="main.map_print_flg" :true-label="1" :false-label="0" v-bind:disabled="isReadOnly">入荷先地図を発注時に印刷</el-checkbox>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label class="control-label">納期希望日</label>
                                            <wj-input-date
                                                class="form-control"
                                                :value="main.desired_delivery_date"
                                                :isRequired=false
                                                :isDisabled="isReadOnly"
                                                :lost-focus="inputDesiredDeliveryDate"
                                            ></wj-input-date>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="accountCustomerName" class="inp" style="margin-top:29px">
                                                <input type="text" id="accountCustomerName" class="form-control" placeholder=" " v-model="main.account_customer_name" v-bind:disabled="isReadOnly">
                                                <span for="accountCustomerName" class="label">使用口座先得意先名</span>
                                                <span class="border"></span>
                                            </label>
                                        </div>
                                        <div class="col-md-4">
                                            <label for="accountOwnerName" class="inp" style="margin-top:29px">
                                                <input type="text" id="accountOwnerName" class="form-control" placeholder=" " v-model="main.account_owner_name" v-bind:disabled="isReadOnly">
                                                <span for="accountOwnerName" class="label">使用口座先施主名</span>
                                                <span class="border"></span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 file-area">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <label class="control-label">見積添付ファイル</label>
                                            <div class="file-operation-area well well-sm" style="height:180px;">
                                                <ul class="detail-file-ul">
                                                    <li v-for="(file, key) in quoteFileNameList" :key="key" class="detail-file-li">
                                                        <span>{{file.storage_file_name}}</span>
                                                        <el-button type="success" icon="el-icon-download" circle size="mini" v-show="file.storage_file_name" @click="fileDownLoad(file.storage_file_name)"></el-button>
                                                    </li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-md-12" v-bind:class="{'has-error': (errors['detail_file_list'] != '') }">
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
                                                    </li>
                                                </ul>
                                            </div>
                                            <p class="text-danger">{{ errors['detail_file_list'] }}</p>
                                        </div>
                                    </div>
                                </div>
                                <!-- 特殊なグリッドシステム -->
                                <div class="col-md-9 filter-area">
                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="row">
                                                <div class="col-md-2">
                                                    <p class="item-label">絞り込み機能</p>
                                                </div>
                                            </div>
                                            <div class="filter-detail area-border">
                                                <div class="row">
                                                    <div class="col-md-2">
                                                        <label class="control-label">発注状況</label>
                                                        <el-checkbox-group v-model="filterInfo.orderStatusCheckList">
                                                            <div class="checkbox" v-for="checkbox in orderStatusList" :key="checkbox.val">
                                                                <el-checkbox class="order-status-list" :label="checkbox.val" v-bind:disabled="isReadOnly">{{ checkbox.text }}</el-checkbox>
                                                            </div>
                                                        </el-checkbox-group>
                                                    </div>
                                                    <div class="col-md-10">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <label class="control-label">
                                                                    <el-checkbox v-model="filterInfo.maker.valid" :true-label="1" :false-label="0" v-bind:disabled="isReadOnly">メーカー</el-checkbox>
                                                                </label>
                                                                <wj-auto-complete class="form-control"
                                                                search-member-path="supplier_name" 
                                                                display-member-path="supplier_name" 
                                                                :items-source="makerList" 
                                                                selected-value-path="id"
                                                                :selected-value="this.filterInfo.maker.id"
                                                                :isDisabled="isReadOnly"
                                                                :isRequired=false
                                                                :selectedIndexChanged="selectFilterMaker"
                                                                :textChanged="setTextChanged"
                                                                :max-items="makerList.length"
                                                            >
                                                            </wj-auto-complete>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="control-label">
                                                                    <el-checkbox v-model="filterInfo.order.valid" :true-label="1" :false-label="0" v-bind:disabled="isReadOnly">発注番号</el-checkbox>
                                                                </label>
                                                                <wj-auto-complete class="form-control"
                                                                    search-member-path="order_no" 
                                                                    display-member-path="order_no" 
                                                                    :items-source="orderList" 
                                                                    selected-value-path="id"
                                                                    :selected-value="this.filterInfo.order.id"
                                                                    :isDisabled="isReadOnly"
                                                                    :isRequired=false
                                                                    :selectedIndexChanged="selectFilterOrder"
                                                                    :textChanged="setTextChanged"
                                                                    :max-items="orderList.length"
                                                                >
                                                                </wj-auto-complete>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="control-label">
                                                                    <el-checkbox v-model="filterInfo.start_date.valid" :true-label="1" :false-label="0" v-bind:disabled="isReadOnly">発注予定日</el-checkbox>
                                                                </label>
                                                                <wj-input-date class="form-control"
                                                                    :value=null
                                                                    :isRequired=false
                                                                    :isDisabled="isReadOnly"
                                                                    :textChanged="textChangedMatterDetailStartDate"
                                                                ></wj-input-date>
                                                            </div>
                                                        </div>
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <label class="control-label">
                                                                    <el-checkbox v-model="filterInfo.supplier.valid" :true-label="1" :false-label="0" v-bind:disabled="isReadOnly">仕入先</el-checkbox>
                                                                </label>
                                                                <wj-auto-complete class="form-control"
                                                                    search-member-path="supplier_name" 
                                                                    display-member-path="supplier_name" 
                                                                    :items-source="filterInfo.supplier.list" 
                                                                    selected-value-path="unique_key"
                                                                    :selected-value="(this.filterInfo.maker.id+'_'+this.filterInfo.supplier.id)"
                                                                    :isDisabled="isReadOnly"
                                                                    :isRequired=false
                                                                    :selectedIndexChanged="selectFilterSupplier"
                                                                    :textChanged="setTextChanged"
                                                                    :max-items="filterInfo.supplier.list.length"
                                                                >
                                                                </wj-auto-complete>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label class="control-label">
                                                                    <el-checkbox v-model="filterInfo.product.valid" :true-label="1" :false-label="0" v-bind:disabled="isReadOnly">商品番号</el-checkbox>
                                                                </label>
                                                                <wj-auto-complete class="form-control"
                                                                    search-member-path="product_code" 
                                                                    display-member-path="product_code" 
                                                                    selected-value-path="product_id"
                                                                    :selected-value="this.filterInfo.product.id"
                                                                    :isDisabled="isReadOnly"
                                                                    :isRequired=false
                                                                    :selectedIndexChanged="selectFilterProduct"
                                                                    :min-length=1
                                                                    :itemsSourceFunction="filterProductItemsSouceFunction"
                                                                    :initialized="initFilterProduct"
                                                                >
                                                                </wj-auto-complete>
                                                            </div>
                                                            <div class="col-md-offset-1 col-md-3">
                                                                <label class="control-label">&nbsp;</label>
                                                                <button type="button" class="btn btn-save form-control" v-on:click="btnFilterClick" v-bind:disabled="isReadOnly">絞り込み</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            

                        </div>
                    </div>

                    <div class="input-area-body-grid">
                        <div class="row tree-grid-operation-area">
                            <div class="col-md-4 col-sm-12 col-xs-12">
                                <div class="input-group tree-grid-operation-input">
                                    <div class="input-group-addon">
                                        <span class="glyphicon glyphicon-search"></span>
                                    </div>
                                    <input v-model="filterInfo.filerText" class="form-control" @input="inputDefaultFilterText()" v-bind:disabled="isReadOnly">
                                </div>
                            </div>
                            <!-- // 機能削除対応 20200803 -->                    
                            <!--<div class="col-md-3 col-sm-9 col-xs-10 form-inline">
                                 <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="toLayer" v-bind:disabled="(isReadOnly)">階層作成</button>
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="toSet" v-bind:disabled="(isReadOnly)">一式作成</button>
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="addRows" v-bind:disabled="(isReadOnly || isFiltering)">行挿入</button>
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="deleteRows" v-bind:disabled="(isReadOnly)">行削除</button>
                            </div>-->
                            <div class="col-md-offset-2 col-md-1 col-sm-3 col-xs-2 form-inline">
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="reserve">引当</button>
                            </div>
                            <div class="col-md-5 col-sm-9 col-xs-10 form-inline">
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="application" v-bind:disabled="(isReadOnly)">発注登録</button>
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="save" v-bind:disabled="(isReadOnly)">一時保存</button>
                                <button type="button" class="btn btn-save tree-grid-operation-input" v-on:click="showDlgCreateOrderNoPrepare" v-bind:disabled="(isReadOnly || showDlgCreateOrderNo)">発注番号採番</button>
                            </div>
                        </div>
                        <!-- 階層 -->
                        <div class="col-md-2 layer-div">
                            <div v-bind:id="'quoteLayerTree'"></div>
                        </div>
                        
                        <!-- グリッド -->
                        <div class="col-md-10 grid-div">
                            <div v-bind:id="'orderDetailGrid'"></div>
                        </div>

                        <!-- コメント入力欄 -->
                        <div class="col-md-12 quote-ver-row">&nbsp;</div>
                        <div class="row">
                            <div class="col-md-2">
                            </div>
                            <div class="col-md-5">
                                <label class="control-label">営業支援用コメント</label>
                                <textarea class="form-control" v-model="main.sales_support_comment" v-bind:readonly="isReadOnly"></textarea>
                            </div>
                            <div class="col-md-5">
                                <label class="control-label">メーカー／仕入先宛コメント</label>
                                <textarea class="form-control" v-model="main.supplier_comment" v-bind:readonly="isReadOnly"></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ボタン -->
                <div class="row">
                <div class="col-md-12">
                    <button type="button" class="btn btn-warning btn-back pull-right" v-on:click="back">戻る</button>
                </div>
                </div>
            </form>
        </div>

        <!-- 発注番号採番ダイアログ -->
        <el-dialog title="発注番号採番" :visible.sync="showDlgCreateOrderNo">
            <div class="row">
                <div class="col-md-8" v-bind:class="{'has-error': (createOrderNoInfo.errors.maker_id != '') }">
                    <label class="control-label">メーカー</label>
                    <wj-auto-complete class="form-control"
                        search-member-path="supplier_name" 
                        display-member-path="supplier_name" 
                        :items-source="makerList" 
                        selected-value-path="id"
                        :selected-value="createOrderNoInfo.maker.id"
                        :isDisabled="isReadOnly"
                        :isRequired=false
                        :selectedIndexChanged="selectCreateOrderNoMaker"
                        :textChanged="setTextChanged"
                        :max-items="makerList.length"
                    >
                    </wj-auto-complete>
                    <p class="text-danger">{{ createOrderNoInfo.errors.maker_id }}</p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-8" v-bind:class="{'has-error': (createOrderNoInfo.errors.supplier_id != '') }">
                    <label class="control-label">仕入先</label>
                    <wj-auto-complete class="form-control"
                        search-member-path="supplier_name" 
                        display-member-path="supplier_name" 
                        :items-source="createOrderNoInfo.supplier.list" 
                        selected-value-path="unique_key"
                        :selected-value="(this.createOrderNoInfo.maker.id+'_'+this.createOrderNoInfo.supplier.id)"
                        :isDisabled="isReadOnly"
                        :isRequired=false
                        :selectedIndexChanged="selectCreateOrderNoSupplier"
                        :textChanged="setTextChanged"
                        :max-items="createOrderNoInfo.supplier.list.length"
                    >
                    </wj-auto-complete>
                    <p class="text-danger">{{ createOrderNoInfo.errors.supplier_id }}</p>
                </div>
            </div>
            <div class="row" v-if="(createOrderNoInfo.order_no != '')">
                <div class="col-md-8">
                    <label class="control-label">発注番号を採番しました</label>
                    <div class="alert alert-info" style="margin-bottom:0px"><strong>発注番号：</strong>{{ createOrderNoInfo.order_no }}</div>
                </div>
            </div>
            <span slot="footer" class="dialog-footer">
                <el-button @click="createOrderNo" class="btn-create" v-bind:disabled="(createOrderNoInfo.order_no !== '')">採番</el-button>
                <el-button @click="showDlgCreateOrderNo = false" class="btn-cancel">閉じる</el-button>
            </span>
        </el-dialog>

    </div>
</template>

<style>
.layer-div {
    border: 1px solid #bbb;
    padding-right: 0 !important;
    padding-left: 0 !important;
}
.grid-div {
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
</style>

<script>
// グリッド初期値
// true/falseのデータ行があると、その列はチェックボックスになる
// 数値のデータ行があると、その行は数値しか入力できなくなる
var INIT_ROW = {
    btn_up: '',
    btn_down: '',
    chk: false,
    fix: '',
    matter_detail_start_date: '',
    product_code: '',
    btn_no_product_code: '',
    product_name: '',
    model: '',
    maker_name: '',
    supplier_name: '',
    order_status_graph: '',
    order_status_value: '',
    stock_reserve_quantity: 0,
    order_quantity: 0.00,
    unit: '',
    stock_quantity: 0,
    stock_unit: '',
    regular_price: 0,
    cost_kbn: 2,
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
    
    sales_use_flg: false,
    quote_detail_id: 0,
    
    construction_id: 0,
    layer_flg: 0,
    set_flg: 0,
    parent_quote_detail_id: 0,
    seq_no: 0,
    depth: 0,
    tree_path: '',
    filter_tree_path : '',
    add_flg: 0,

    product_id: 0,
    min_quantity: 0.01,
    order_lot_quantity: 0.01,
    
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

    maker_id: 0,
    supplier_id: 0,
    order_id_list: '',
    delivery_id_list: '',
    save_order_detail_id: 0,
    quote_quantity: 0,
    sum_reserve_quantity: 0,
    sum_order_quantity: 0,
    sales_kbn: 2,
    allocation_kbn: 0,
    intangible_flg: 1,
    add_kbn: 1,
    child_parts_flg: 0,
    product_maker_id: 0,
    
};


import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';

export default {
    data: () => ({
        loading: false,
        isReadOnly: true,
        isShowEditBtn: true,
        isLocked: false,

        todayYYYYMMDD: 0,

        searchParams: {
            matter_no: null,
            matter_name: null,
            arrival_plan_date_from: null,
            arrival_plan_date_to: null,
            hope_arrival_plan_date_from: null,
            hope_arrival_plan_date_to: null,
        },

        base: {},
        main: {},
        lock: {},

        // filterClassMiddleList: [{class_middle_id:0, class_middle_name:'', class_big_id:0}],

        // 階層データ
        wjTreeViewControl: {},
        // グリッドデータ
        wjMultiRowControle: {},
        
        gridLayout: [],
        // 非表示カラム
        INVISIBLE_COLS: [
            'btn_up',           // 機能削除対応 20200803
            'btn_down',         // 機能削除対応 20200803
            'quote_detail_id',
            'construction_id',
            'layer_flg',
            'set_flg',
            'cost_total',
            'sales_total',
            'sales_use_flg',
            'parent_quote_detail_id',
            'seq_no',
            'depth',
            'tree_path',
            'filter_tree_path',
            
            'add_flg',
            'product_id',
            'min_quantity',
            'order_lot_quantity',

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

            'maker_id',
            'supplier_id',
            'order_id_list',
            'delivery_id_list',
            'save_order_detail_id',
            'quote_quantity',
            'sum_reserve_quantity',
            'sum_order_quantity',
            'sales_kbn',
            'allocation_kbn',
            'intangible_flg',
            'add_kbn',
            'child_parts_flg',
            'product_maker_id'
        ],
        // 文字入力カラム
        STR_COLS: [
            'product_code',
            'product_name',
            'model',
            'maker_name',
            'supplier_name',
            'memo',
        ],
        // コピペ時に貼り付け対象としない列
        NON_PASTING_COLS: [
            'matter_detail_start_date',
            'sales_use_flg',
            'quote_detail_id',
            'construction_id',
            'layer_flg',
            'set_flg',
            'parent_quote_detail_id',
            'seq_no',
            'depth',
            'tree_path',
            'filter_tree_path',
            'add_flg',
            'order_id_list',
            'delivery_id_list',
            'save_order_detail_id',
            'quote_quantity',
            'sum_reserve_quantity',
            'sum_order_quantity',
            'stock_reserve_quantity',
            'sales_kbn',
            'allocation_kbn',
            'add_kbn',
            'child_parts_flg',
            'product_auto_flg',
            'product_definitive_flg',
        ],


        ADD_KBN: {
            DEFAULT: 0,
            ORDER: 1,
            ORDER_DETAIL: 2,
        },

        errors: {
            maker_id: '',
            supplier_id: '',
            delivery_address: '',
            detail_file_list: '',
        },
        urlparam: '',

        uploadFileList : [],
        deleteFileList : [],

        cgeProductCode : null,
        cgeProductName : null,
        cgeMaker : null,
        cgeSupplier : null,

        headSupplierList: [],
        headPersonList: [],
        deliveryAddressList : [],
        wjDeliveryAddress : {},

        headSupplierCombo: null,

        filterInfo: {
            maker: {
                id: 0,
                valid :0
            },
            supplier: {
                list: [],
                id: 0,
                valid :0
            },
            order: {
                id: 0,
                valid :0
            },
            product: {
                id: 0,
                valid :0
            },
            start_date: {
                text: '',
                valid :0
            },
            filerText: '',
            orderStatusCheckList: []
        },
        filterProductAutoComplate: null,
        activeFilterInfo: null,
        isFiltering: false,

        showDlgCreateOrderNo: false,
        createOrderNoInfo: {
            maker: {
                id: null,
                name: '',
            },
            supplier: {
                list: [],
                id: null,
                name :'',
            },
            errors: {
                maker_id: '',
                supplier_id: '',
            },
            order_no: ''
        },

        tooltip : new wjCore.Tooltip(),
        
        // 一式作成のエラーメッセージ
        MSG_LIST_TREE_GRID_CHK_KBN_TO_SET: {
            0: '',
            1: MSG_ERROR_CREATE_SET_PRODUCT,
            2: MSG_ERROR_CREATE_SET_PRODUCT_CONSTRUCTION_LAYER,
            3: MSG_ERROR_CREATE_SET_PRODUCT_EXISTS_LAYER,
            4: MSG_ERROR_CREATE_SET_PRODUCT_EXISTS_SET_PRODUCT,
            5: MSG_ERROR_CREATE_SET_PRODUCT_INSIDE_SET_PRODUCT,
            6: MSG_ERROR_CREATE_SET_PRODUCT_INSIDE_ADD_CONSTRUCTION_LAYER,
        },

        DRAG_FILTER_TREE_PATH: '', 

        INIT_ROW_MIN_QUANTITY: 1.00,

        tmpDeliveryAddressUniqueKey: '',
    }),
    props: {
        baseData : {},
        isOwnLock: Number,
        lockdata: {},
        maindata: {},
        shippingLimitList: {},
        gridDataList: Array,
        quoteLayerList: Array,
        shipmentKindDataList: Array,
        makerList: {},
        orderStatusList: {},
        classBigList: Array,
        classMiddleList: Array,
        priceList: Array,
        // allocList: Array,
        supplierList: Array,
        supplierMakerList: {},
        supplierFileList: {},
        personList: {},
        quoteFileNameList: Array,
        orderFileNameList: Array,
        sumEachWarehouseAndDetailList: {},
        orderList: {},
        propDeliveryAddressList: {},
        stockFlg: {},
        noProductCode: {},
        updateHistoryDataList: {},
    },
    created() {
        // propsで受け取った値をローカル変数に入れる
        this.base = this.baseData
        this.main = this.maindata
        this.lock = this.lockdata
        this.deliveryAddressList = this.propDeliveryAddressList;
        this.todayYYYYMMDD = this.getToday();

        // 大分類リスト　先頭に未選択を追加
        this.classBigList.unshift({class_big_id:0, class_big_name:''});
        // 中分類リスト　先頭に未選択を追加
        this.classMiddleList.unshift({class_middle_id:0, class_middle_name:'', class_big_id:0});

        // ロック中判定
        if (this.rmUndefinedZero(this.lock.id) !== 0 && this.isOwnLock === this.FLG_OFF) {
            this.isLocked = true;
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        }

        this.activeFilterInfo = JSON.parse(JSON.stringify(this.filterInfo));
        // グリッドレイアウトセット
        this.gridLayout = this.getGridLayout()


        // 一時保存データのセット
        if(this.rmUndefinedZero(this.main.maker_id) !== 0 && this.main.maker_id !== ''){
            this.headSupplierList = this.supplierMakerList[this.main.maker_id];
        }
        if(this.personList[this.main.supplier_id] !== undefined){
            this.headPersonList = this.personList[this.main.supplier_id];
        }
        
    },
    mounted() {
        
        if (!this.isLocked) {
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


 
        var detailArr = [];
        for(var i in this.gridDataList) {
            var rec = this.gridDataList[i];
                
            // 選択チェックボックス
            rec['chk'] = false;

            // 一時保存発注明細ID
            rec['save_order_detail_id'] = this.rmUndefinedZero(rec['save_order_detail_id']);
            
            rec['regular_price'] = parseInt(this.rmUndefinedZero(rec['regular_price']));
            rec['cost_unit_price'] = parseInt(this.rmUndefinedZero(rec['cost_unit_price']));
            rec['sales_unit_price'] = parseInt(this.rmUndefinedZero(rec['sales_unit_price']));
            rec['cost_total'] = parseInt(this.rmUndefinedZero(rec['cost_total']));
            rec['sales_total'] = parseInt(this.rmUndefinedZero(rec['sales_total']));
            rec['profit_total'] = parseFloat(this.rmUndefinedZero(rec['profit_total']));
            rec['cost_makeup_rate'] = parseFloat(this.rmUndefinedZero(rec['cost_makeup_rate']));
            rec['sales_makeup_rate'] = parseFloat(this.rmUndefinedZero(rec['sales_makeup_rate']));
            rec['gross_profit_rate'] = parseFloat(this.rmUndefinedZero(rec['gross_profit_rate']));

            rec['quote_quantity'] = parseFloat(this.rmUndefinedZero(rec['quote_quantity']));
            rec['order_id_list'] = this.rmUndefinedBlank(rec['order_id_list']);
            rec['delivery_id_list'] = this.rmUndefinedBlank(rec['delivery_id_list']);
            
        
            rec['order_quantity'] = parseFloat(this.rmUndefinedZero(rec['order_quantity']));
            rec['stock_quantity'] = parseInt(this.rmUndefinedZero(rec['stock_quantity']));

            // 最小単位数量
            rec['min_quantity'] = parseFloat(this.rmUndefinedZero(rec['min_quantity']));
            if(this.bigNumberEq(rec['min_quantity'], 0)){
                if(this.rmUndefinedBlank(rec['product_code']) !== ''){
                    rec['min_quantity'] = this.INIT_ROW_MIN_QUANTITY;
                }else{
                    rec['min_quantity'] = INIT_ROW.min_quantity;
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


            rec['sales_use_flg'] = rec['sales_use_flg'] === this.FLG_ON;
            rec['add_kbn'] = this.ADD_KBN.DEFAULT;

            // 在庫引当
            rec['stock_reserve_quantity'] = 0;
            // 発注引当
            rec['sum_order_quantity'] = 0;
            // 引当発注済数合計
            rec['sum_reserve_quantity'] = 0;
            var quoteDetailId = this.rmUndefinedZero(rec['quote_detail_id']);
            for(let fromWarehouseId in this.sumEachWarehouseAndDetailList[quoteDetailId]) {
                var warehouseReservInfo = this.sumEachWarehouseAndDetailList[quoteDetailId][fromWarehouseId];
                for(let ii in warehouseReservInfo) {
                    var reserveQuantity = parseFloat(this.rmUndefinedZero(warehouseReservInfo[ii]['sum_warehouse_min_reserve_quantity']));
                    rec['sum_reserve_quantity'] = this.bigNumberPlus(rec['sum_reserve_quantity'], reserveQuantity);
                    if(warehouseReservInfo[ii]['stock_flg'] === this.stockFlg['val']['order']){
                        rec['sum_order_quantity'] = this.bigNumberPlus(rec['sum_order_quantity'], reserveQuantity);
                    }else{
                        rec['stock_reserve_quantity'] = this.bigNumberPlus(rec['stock_reserve_quantity'], reserveQuantity);
                    }
                }
            }
            // 発注数 数量超過判定に使用する(sum_order_quantity + stock_reserve_quantity + order_quantity)
            //rec['sum_order_quantity'] = parseFloat(this.rmUndefinedZero(rec['sum_order_quantity']));

            if (rec['auto_flg'] === this.FLG_ON) {
                // 1回限り登録商品は初期表示時にチェック済みにする
                rec['product_auto_flg'] = true;
            } else {
                // 子部品には1回限り登録にチェックを入れる
                rec['product_auto_flg'] = rec['child_parts_flg'] === this.FLG_ON;
            }
            // 本登録チェック
            rec['product_definitive_flg'] = false;

            detailArr.push(rec);
        }

        this.$nextTick(function() {
            var gridItemSource = new wjCore.CollectionView(detailArr, {
                newItemCreator: function () {
                    return Vue.util.extend({}, INIT_ROW);
                }
            });
            this.wjMultiRowControle = this.createGridCtrl('#orderDetailGrid', gridItemSource);

            var treeCtrl = this.createTreeCtrl('#quoteLayerTree', this.quoteLayerList);
            this.wjTreeViewControl = treeCtrl;

            var gridData = this.wjMultiRowControle.itemsSource.items;
            for (var i = 0; i < gridData.length; i++) {
                this.calcTreeGridRowData(gridData[i], 'order_quantity');
            }
            this.calcGridCostSalesTotal();
            
            this.selectTree(this.wjTreeViewControl, 'top_flg', this.FLG_ON);
        });        

        // 検索条件セット
        // var query = window.location.search;
        // if (query.length > 1) {
        //     this.setSearchParams(query, this.searchParams);
        //     // 検索
        //     this.$nextTick(function() {
        //         this.search();
        //     });
        // }
        

    },
    methods: {
        // 【グリッド】作成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.gridLayout,
                showSort: false,
                allowAddNew: !this.isReadOnly,
                allowDelete: !this.isReadOnly,
                allowSorting: false,
                keyActionEnter: wjGrid.KeyAction.None,
                autoClipboard: false,
            });
            // グリッドに対して右クリックメニューを紐づける
            //var contextMenuCtrl = this.setTreeGridRightCtrl(wjGrid, wjcInput, gridCtrl);
            gridCtrl.isReadOnly = this.isReadOnly;
            gridCtrl.rowHeaders.columns[0].width = 30;
            
            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;
                // 通常セルに対してctrl+vを有効にするため
                s.autoClipboard = true;
                switch(col.name){
                    case 'product_code':
                    case 'product_name':
                        // 階層と一式の場合、コンボボックスを表示させない
                        if(row.layer_flg !== this.FLG_ON && row.set_flg !== this.FLG_ON){
                            this.cgeProductCode.changeItemsSource([]);
                            this.cgeProductName.changeItemsSource([]);
                        }else{
                            this.cgeProductCode.changeItemsSource(null);
                            this.cgeProductName.changeItemsSource(null);
                        }
                        
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'maker_name':
                        // 商品マスタにメーカーIDがある場合　or　既に発注している場合はメーカー変更不可
                        if(this.rmUndefinedZero(row.product_maker_id) !== 0 || this.rmUndefinedBlank(row.order_id_list) !== ''){
                            this.cgeMaker.control.isReadOnly = true;
                            e.cancel = true;
                        }else{
                            this.cgeMaker.control.isReadOnly = false;
                        }

                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'supplier_name':
                        this.cgeSupplier.changeItemsSource(this.supplierMakerList[row.maker_id]);
                        // 既に発注している場合は仕入先変更不可
                        if(this.rmUndefinedBlank(row.order_id_list) !== ''){
                            this.cgeSupplier.control.isReadOnly = true;
                            e.cancel = true;
                        }else{
                            this.cgeSupplier.control.isReadOnly = false;
                        }

                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'cost_makeup_rate':
                    case 'sales_makeup_rate':
                        if(this.rmUndefinedZero(row.regular_price) === 0){
                            e.cancel = true;
                        }
                        break;
                }

                if (this.gridIsReadOnlyCell(gridCtrl, e.row, e.col)) {
                    e.cancel = true;
                }
            }.bind(this));

            // クリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        var loadTreeFlg = false;
                        switch (col.name) {
                            case 'btn_up':
                                if(!this.isReadOnly){
                                    loadTreeFlg = this.treeGridUpDownBtnClick(gridCtrl, this.wjTreeViewControl, record, true);
                                }
                                break;
                            case 'btn_down':
                                if(!this.isReadOnly){
                                    loadTreeFlg = this.treeGridUpDownBtnClick(gridCtrl, this.wjTreeViewControl, record, false);
                                }
                                break;
                            case 'btn_no_product_code':
                                if(!this.isReadOnly){
                                    var clearValidFlg = true;
                                    
                                    if(this.rmUndefinedZero(record.quote_detail_id) !== 0){
                                        if(record.intangible_flg === this.FLG_ON){
                                            alert(MSG_ERROR_HINBANNASHI);
                                            clearValidFlg = false;
                                        }
                                    }

                                    if(clearValidFlg){
                                        record.product_id = INIT_ROW.product_id;
                                        record.product_maker_id = INIT_ROW.product_maker_id;
                                        record.product_code = this.noProductCode.value_text_1;
                                        record.intangible_flg = this.FLG_OFF;
                                        this.calcTreeGridRowData(record, 'order_quantity');
                                        this.calcGridCostSalesTotal();
                                    }
                                }
                                break;
                        }

                        if(loadTreeFlg){
                            this.wjTreeViewControl.loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                        }
                    }       
                }
            });
            // ダブルクリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "dblclick", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.RowHeader){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined' && record.layer_flg === this.FLG_ON){
                        this.selectTree(this.wjTreeViewControl, 'filter_tree_path', record.filter_tree_path);
                    }
                }
            });

            // 右クリックイベント
            // contextMenuCtrl.itemClicked.addHandler(function(s, e) {
            //     // メニュークリック
            //     var rowList = [];
            //     // クリックした行
            //     var clickRowDataItem = contextMenuCtrl.row;
            //     // クリックした行が何番目かを取得する
            //     var clickRowDataItemIndx = contextMenuCtrl.rowIndex;
            //     // クリップボードにコピーした文字列
            //     var clipboardText = this.rightClickInfo.clipboardText;
            //     // 今開いている階層の情報
            //     var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);
            //     // コピー行の貼り付け時のみ使用
            //     var isAddUnder = true;

            //     // 選択した項目によって処理を分岐させる
            //     switch(contextMenuCtrl.selectedValue){
            //         case 'copy':
            //             // コピー
            //             this.wjMultiRowCopyClipboardByRightClick(wjCore.Clipboard, gridCtrl, clickRowDataItem);
            //             break;
            //         case 'paste':
            //             // 貼付け
            //             var layount = gridCtrl.layoutDefinition;
            //             var clipboardData = this.toWjMultiRowPasteTextFormat(clipboardText);
            //             if(clipboardData.length%2 !== 0){
            //                 alert(MSG_ERROR_PASTE_FORMAT);
            //             }else{
            //                 if(this.wjMultiRowClipBoardDetailValidation(clipboardData, layount, clickRowDataItem, 0)){
            //                     this.wjMultiRowPasteClipboard(clipboardData, layount, clickRowDataItem, 0, this.NON_PASTING_COLS);
            //                     // 階層の場合、階層名変更
            //                     this.changeProduct(null, clickRowDataItem, false);
            //                     // 行の金額を計算
            //                     this.calcTreeGridRowData(clickRowDataItem, 'order_quantity');
            //                     // 全体の計算
            //                     this.calcGridCostSalesTotal();
            //                 }
            //             }
            //             break;
            //         case 'upAddRowPaste':
            //             // コピーした行を上に貼り付け
            //             isAddUnder = false;
            //         case 'downAddRowPaste':
            //             // コピーした行を下に貼り付け
            //             var layount = gridCtrl.layoutDefinition;
            //             var clipboardData = this.toWjMultiRowPasteTextFormat(clipboardText);

            //             if(this.addRowIsValid(gridCtrl, kbnIdList)){
            //                 if(clipboardData.length%2 !== 0){
            //                     alert(MSG_ERROR_PASTE_FORMAT);
            //                 }else{
            //                     // 全体から見て何番目かを取得
            //                     rowList[clickRowDataItemIndx] = clickRowDataItem;
            //                     var addList = this.addTreeGrid(gridCtrl, this.wjTreeViewControl, kbnIdList.depth, kbnIdList.filter_tree_path, INIT_ROW, isAddUnder, rowList);

            //                     if(this.wjMultiRowClipBoardDetailValidation(clipboardData, layount, addList[addList.length-1], 0) && clipboardData.length !== 0){
            //                         this.wjMultiRowPasteClipboard(clipboardData, layount, addList[addList.length-1], 0, this.NON_PASTING_COLS);
            //                         // 階層とグリッドのチェックボックスを外す(今開いている階層のみにしか追加しないので末尾の行を渡す)
            //                         this.changeGridCheckBox(addList[addList.length-1]);
            //                         // 階層の場合、階層名変更
            //                         this.changeProduct(null, clickRowDataItem, false);
            //                         // 行の金額を計算
            //                         this.calcTreeGridRowData(clickRowDataItem, 'order_quantity');
            //                         // 全体の計算
            //                         this.calcGridCostSalesTotal();
            //                     }else{
            //                         // 追加した行を削除
            //                         var delIndex = isAddUnder ? clickRowDataItemIndx+1 : clickRowDataItemIndx;
            //                         gridCtrl.collectionView.sourceCollection.splice(delIndex, 1);
            //                     }
                                
            //                     // グリッド再描画
            //                     gridCtrl.collectionView.refresh();
            //                 }
            //             }
            //             break;
            //         case 'toLayer':
            //             // 階層作成
            //             rowList.push(clickRowDataItem);
            //             if(this.toLayerIsValid(clickRowDataItem)){
            //                 this.treeGridDetailRecordToLayer(gridCtrl.itemsSource.items, this.wjTreeViewControl, rowList);
            //                 this.toLayerInitProp(clickRowDataItem);
                
            //                 // ツリー再読み込み
            //                 this.wjTreeViewControl.loadTree();
            //                 // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            //                 this.checkedAllTreeGrid(false);
            //                 // 再計算
            //                 this.calcTreeGridRowData(clickRowDataItem, 'order_quantity');
            //                 // グリッド再描画
            //                 this.calcGridCostSalesTotal();
            //             }
            //             break;
            //         case 'toSet':
            //             // 一式作成
            //             rowList.push(clickRowDataItem);
            //             if(this.toSetIsValid(clickRowDataItem)){
            //                 // 一式化する
            //                 this.treeGridDetailRecordToSet(this.wjMultiRowControle.itemsSource.items, this.wjTreeViewControl, rowList);
            //                 // ツリー再読み込み
            //                 this.wjTreeViewControl.loadTree();
            //                 // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            //                 this.checkedAllTreeGrid(false);
            //                 // グリッド再描画
            //                 this.calcGridCostSalesTotal();
            //             }
            //             break;
            //         case 'addRow':
            //             // 行追加
            //             if(this.addRowIsValid(gridCtrl, kbnIdList)){
            //                 // 全体から見て何番目かを取得
            //                 rowList[clickRowDataItemIndx] = clickRowDataItem;
            //                 var addList = this.addTreeGrid(gridCtrl, this.wjTreeViewControl, kbnIdList.depth, kbnIdList.filter_tree_path, INIT_ROW, false, rowList);
            //                 // 階層とグリッドのチェックボックスを外す(今開いている階層のみにしか追加しないので末尾の行を渡す)
            //                 this.changeGridCheckBox(addList[addList.length-1]);

            //                 // グリッド再描画
            //                 gridCtrl.collectionView.refresh();
            //             }
            //             break;
            //         case 'deleteRow':
            //             // 行削除
            //             if(this.rmUndefinedZero(clickRowDataItem.quote_detail_id) !== 0){
            //                 alert(MSG_ERROR_DELETE_ROW);
            //             }else{
            //                 var result = window.confirm(MSG_CONFIRM_DELETE);
            //                 if (result) {
            //                     this.deleteTreeGridRow(gridCtrl.collectionView.sourceCollection, this.wjTreeViewControl, clickRowDataItem.filter_tree_path);
            //                     this.wjTreeViewControl.loadTree();
            //                     // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            //                     this.checkedAllTreeGrid(false);
            //                     this.calcGridCostSalesTotal();
            //                 }
            //             }
            //             break;
            //         default :
            //             break;
            //     }
                
            // }.bind(this));

            // 行追加イベント：グリッドの非表示カラムに階層情報をセット
            gridCtrl.rowAdded.addHandler(function (s, e) {
                if(this.rmUndefinedBlank(this.wjTreeViewControl.selectedNode) !== ''){
                    this.setTreeGridInvisibleData(s, this.wjTreeViewControl);
                    this.changeGridCheckBox(s.collectionView.currentAddItem);
                }
            }.bind(this));

            // 行削除イベント
            gridCtrl.deletingRow.addHandler(function (s, e) {
                e.cancel = true;
            }.bind(this));

            // 表編集後の確定前イベント
            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                // クリップボードを有効にさせるため編集後はfalseへ
                s.autoClipboard = false;
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    case 'product_code':
                        row.is_cancel = false;
                        if(this.rmUndefinedBlank(this.cgeProductCode.control.text) === row.product_code){
                            e.cancel = true;
                        }else{
                            row.is_cancel = this.changingProduct(this.cgeProductCode, row, true);
                        }
                        break;
                    case 'product_name':
                        row.is_cancel = false;
                        if(this.rmUndefinedBlank(this.cgeProductName.control.text) === row.product_name){
                            e.cancel = true;
                        }else{
                            if(row.layer_flg === this.FLG_ON){
                                // 階層名(商品名)は必須
                                // if(this.rmUndefinedBlank(this.cgeProductName.control.text) === ''){
                                //     this.cgeProductName.control.text = row.product_name;
                                //     alert(MSG_ERROR_LAYER_NAME_NO_INPUT);
                                //     e.cancel = true;
                                // }
                            }else{
                                row.is_cancel = this.changingProduct(this.cgeProductName, row, false);
                            }
                        }
                        break;
                    case 'order_quantity':
                        var orderQuantity = s.activeEditor == null ? 0 : s.activeEditor.value;
                        if(!this.treeGridQuantityIsMultiple(orderQuantity, row.order_lot_quantity)){
                            alert(MSG_ERROR_NOT_ORDER_LOT_QUANTITY_MULTIPLE + ' 現在の発注ロット数：' + row.order_lot_quantity);
                            e.cancel = true;
                        }
                        break;
                    // case 'cost_makeup_rate':
                    // case 'sales_makeup_rate':
                    //     if(this.rmUndefinedZero(row.regular_price) === 0){
                    //         e.cancel = true;
                    //     }
                    //     break;
                }
            }.bind(this));
            // セル編集後イベント：行内のデータ自動セット
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                switch (col.name) {
                    case 'chk' :
                        this.changeGridCheckBox(s.collectionView.currentItem);
                        break;
                    case 'product_code':
                        // 商品コード
                        var product = this.cgeProductCode.selectedItem;
                        this.changeProduct(product, row, true);
                        break;
                    case 'product_name':
                        // 商品名
                        var product = this.cgeProductName.selectedItem;
                        this.changeProduct(product, row, false);
                        break;
                    case 'maker_name':
                        // メーカー名
                        if(!this.cgeMaker.control.isReadOnly){
                            var maker = this.cgeMaker.selectedItem;
                            if(maker !== null){
                                if(maker.id !== row.maker_id){
                                    row.maker_id        = maker.id;
                                    this.cgeSupplier.changeItemsSource(this.supplierMakerList[row.maker_id]);
                                    // 仕入先リストの中からに選択されている仕入先名と同一のものを検索
                                    var findIdx = -1;
                                    if(this.cgeSupplier.control.itemsSource !== null){
                                        findIdx = this.cgeSupplier.control.itemsSource.findIndex((rec) => {
                                            return (rec.supplier_name == row.supplier_name);
                                        });
                                    }
                                    if (findIdx == -1) {
                                        row.supplier_id     = INIT_ROW.supplier_id;
                                        row.supplier_name   = INIT_ROW.supplier_name;
                                    }
                                }
                            }else{
                                row.maker_id        = INIT_ROW.maker_id;
                                row.maker_name      = INIT_ROW.maker_name;
                                row.supplier_id     = INIT_ROW.supplier_id;
                                row.supplier_name   = INIT_ROW.supplier_name;
                            }
                        }else{
                            if(this.rmUndefinedZero(row.maker_id) !== 0){
                                for(var i = 0; i < this.supplierList.length; i++){
                                    if (this.supplierList[i].id == row.maker_id) {
                                        row.maker_name = this.supplierList[i].supplier_name;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'supplier_name':
                        // 仕入先名
                        if(!this.cgeSupplier.control.isReadOnly){
                            var supplier = this.cgeSupplier.selectedItem;
                            if(supplier !== null){
                                row.supplier_id = supplier.supplier_id;
                            }else{
                                row.supplier_id = INIT_ROW.supplier_id;
                                row.supplier_name = INIT_ROW.supplier_name;
                            }
                        }else{
                            if(this.rmUndefinedZero(row.supplier_id) !== 0){
                                for(var i = 0; i < this.supplierList.length; i++){
                                    if (this.supplierList[i].id == row.supplier_id) {
                                        row.supplier_name = this.supplierList[i].supplier_name;
                                        break;
                                    }
                                }
                            }
                        }
                        break;
                    case 'order_quantity':
                        // 発注数
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'regular_price':
                        // 定価
                        this.calcTreeGridChangeRegularPriceEx(row);
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'cost_unit_price':
                        // 仕入単価
                        this.calcTreeGridChangeUnitPrice(row, true);
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'sales_unit_price':
                        // 販売単価
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'cost_makeup_rate':
                        // 仕入掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, true);
                            this.calcTreeGridRowData(row, 'order_quantity');
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'sales_makeup_rate':
                        // 販売掛率
                        if(this.rmUndefinedZero(row.regular_price) !== 0){
                            this.calcTreeGridChangeMakeupRate(row, false);
                            this.calcTreeGridRowData(row, 'order_quantity');
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'gross_profit_rate':
                        // 粗利率
                        // this.calcTreeGridChangeGrossProfitRateEx(row);
                        // this.calcTreeGridChangeUnitPrice(row, true);
                        // this.calcTreeGridRowData(row, 'order_quantity');
                        // this.calcGridCostSalesTotal();
                        break;
                    case 'cost_kbn':
                        // 仕入区分
                        if(e.data !== row.cost_kbn &&
                            this.rmUndefinedBlank(row.cost_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row);
                            
                        }
                        break;
                    case 'class_big_id':
                        // 大分類
                        if(e.data !== row.class_big_id){
                            // 大分類を変更したら中分類をクリア
                            row.class_middle_id = 0;
                            this.wjMultiRowControle.collectionView.refresh();
                        }

                        break;
                    case 'sales_use_flg':
                        // 販売額利用フラグ
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                }
                gridCtrl.collectionView.commitEdit();
                // TODO 都度フィルターを掛けるか？
                this.defaultFilter();
            }.bind(this));

            gridCtrl.columns.forEach(element => {
                // 非表示設定
                if (element.name != undefined && this.INVISIBLE_COLS.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                var col = gridCtrl.getBindingColumn(panel, r, c);
                var colName = col.name;
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    
                    switch(colName){
                        case 'chk':
                            // チェックボックス生成
                            var checkedCount = 0;
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                            }

                            var checkBox = '<input type="checkbox">';
                            if(this.isReadOnly){
                                checkBox = '<input type="checkbox" disabled="true">';
                            }
                            cell.innerHTML = checkBox;
                            var checkBox = cell.firstChild;
                            checkBox.checked = checkedCount > 0;
                            checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                            checkBox.addEventListener('click', function (e) {
                                gridCtrl.beginUpdate();
                                for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                    gridCtrl.collectionView.items[i].chk = checkBox.checked;
                                    this.changeGridCheckBox(gridCtrl.collectionView.items[i]);
                                }
                                gridCtrl.endUpdate();
                            }.bind(this));
                            break;
                        case 'product_auto_flg':
                            // 1回限り登録チェックボックス生成
                            var checkedCount = 0;
                            for (var i = 0; i < gridCtrl.rows.length; i++) {
                                if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                            }

                            var checkBox = '<input type="checkbox">1回限り登録';
                            if(this.isReadOnly){
                                checkBox = '<input type="checkbox" disabled="true">1回限り登録';
                            }
                            cell.innerHTML = checkBox;
                            var checkBox = cell.firstChild;
                            checkBox.checked = checkedCount > 0;
                            checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                            checkBox.addEventListener('click', function (e) {
                                gridCtrl.beginUpdate();
                                for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                                    // 子部品のチェックボックスは変更しない
                                    //if(gridCtrl.collectionView.items[i].child_parts_flg === this.FLG_OFF){
                                        gridCtrl.collectionView.items[i].product_auto_flg = checkBox.checked;
                                    //}
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
                        case 'stock_reserve_quantity':
                            cell.innerHTML = '在庫<br/>引当';
                            break;
                    }
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    
                    var dataItem = panel.rows[r].dataItem;
                    var disabled = this.isReadOnly?'disabled':'';
                    var quoteDetailId = 0;
                    if(dataItem !== undefined){
                        quoteDetailId = dataItem.quote_detail_id;
                        // 修正履歴のあるセルはフォントカラー変更
                        if (this.updateHistoryDataList[quoteDetailId] && this.updateHistoryDataList[quoteDetailId].fix_columns.indexOf(colName) != -1) {
                            cell.style.color = '#FFAA00';
                        }else{
                            cell.style.color = '';
                        }
                        // 販売額利用 or 一式の場合の行の色変更
                        if(dataItem.sales_use_flg || dataItem.set_flg === this.FLG_ON){
                            if(cell.style.backgroundColor !== 'lightgrey'){
                                cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.SALES_USE_ROW;
                            }
                        }
                    }
                    
                    switch(colName){
                        case 'btn_up':
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default multi-grid-btn" '+disabled+'><i class="el-icon-arrow-up"></i></button>';
                            break;
                        case 'btn_down':
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default multi-grid-btn" '+disabled+'><i class="el-icon-arrow-down"></i></button>';
                            break;
                        case 'btn_no_product_code':
                            cell.style.padding = '0px';
                            cell.innerHTML = '<button type="button" class="btn btn-default single-grid-btn-sm" '+disabled+'><i class="el-icon-caret-left"></i></button>';
                            break;
                        case 'chk':
                            // データ取得
                            // var checkBox = cell.firstChild;
                            // // チェック時にすぐに編集を確定
                            // checkBox.addEventListener('mousedown', function (e) {
                            //     gridCtrl.collectionView.commitEdit();
                            // });
                            break;
                        case 'fix':
                            // 修正
                            if(dataItem !== undefined){
                                var fixLog = document.createElement('div');
                                fixLog.id = 'grid_fixlog_cell_' + quoteDetailId +'-'+ dataItem.filter_tree_path;
                                
                                if (this.updateHistoryDataList[quoteDetailId] !== undefined) {
                                    cell.style.backgroundColor = '';    // 背景色リセット
                                    fixLog.classList.add('grid-fixlog-cell', 'bg-yellow', 'disabled');
                                    const fixLogs = this.updateHistoryDataList[quoteDetailId].fix_logs;

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
                                    
                                    cell.appendChild(fixLog);
                                    this.tooltip.setTooltip(
                                        '#' + fixLog.id,
                                        fixLogContent
                                    )
                                    
                                }
                                
                            }
                            break;
                        case 'maker_name':
                            if(dataItem !== undefined){
                                if (this.supplierFileList[dataItem.maker_id]) {
                                    var elem = document.createElement('a');
                                    elem.target = '_blank';
                                    elem.rel = 'noopener';
                                    elem.href = '/supplier-file-open/' + dataItem.maker_id
                                    elem.innerHTML = '<i class="el-icon-notebook-2"></i>';
                                    cell.insertBefore(elem, cell.firstChild);
                                }
                            }
                            break;
                        case 'supplier_name':
                            if (dataItem !== undefined) {
                                if(this.supplierFileList[dataItem.supplier_id]){
                                    var elem = document.createElement('a');
                                    elem.target = '_blank';
                                    elem.rel = 'noopener';
                                    elem.href = '/supplier-file-open/' + dataItem.supplier_id
                                    elem.innerHTML = '<i class="el-icon-notebook-2"></i>';
                                    cell.insertBefore(elem, cell.firstChild);
                                }
                            }
                            break;
                        case 'order_status_graph':
                            // グラフ用
                            if(dataItem !== undefined){
                                if(dataItem.layer_flg !== this.FLG_ON){
                                    cell.style.backgroundColor = '';
                                }
                                // 引当発注済数がある時グラフ描画作成
                                if(this.rmUndefinedZero(dataItem.sum_reserve_quantity) > 0){
                                    
                                    cell.style.padding = '0px';
                                    
                                    // 見積数量
                                    var quoteQuantity = parseFloat(dataItem.quote_quantity);
                                    // 引当数量の合計
                                    var sumMinReserveQuantity = parseFloat(dataItem.sum_reserve_quantity);

                                    var idVal = 'grid_graph_cell_' + quoteDetailId +'-'+ dataItem.filter_tree_path;
                                    var headHtml = '<div class="grid-graph-cell" id="' + idVal +'">';
                                    var cellHtml = '';
                                    var tooltipVal = '';
                                    
                                    if(quoteQuantity === sumMinReserveQuantity){
                                        cellHtml = '<div class="bg-green" style="width:100%"></div>';
                                    }else if(quoteQuantity < sumMinReserveQuantity){
                                        cellHtml = '<div class="bg-green" style="width:100%; border:solid 2px #ff0000;"></div>';
                                    }else{
                                        // 引当データの倉庫リスト
                                        var colorCnt = 0;
                                        for(let fromWarehouseId in this.sumEachWarehouseAndDetailList[quoteDetailId]) {
                                            var warehouseReservInfo = this.sumEachWarehouseAndDetailList[quoteDetailId][fromWarehouseId];
                                            for(let i in warehouseReservInfo) {
                                                // 最大2回ループ(在庫からの引当 or 発注ありの引当)
                                                var currentWRQ = parseFloat(this.rmUndefinedZero(warehouseReservInfo[i]['sum_warehouse_min_reserve_quantity']));
                                                //var widthVal = Math.floor((currentWRQ / quoteQuantity) * 100);
                                                var widthVal = (currentWRQ / quoteQuantity) * 100;
                                                var styleVal = 'width:' + widthVal + '%;';
                                                var classVal = 'item ' + 'bg-warehouse' + (colorCnt % 5); // 0～4までの5色
                                                cellHtml += '<div class="' + classVal + '" style="' + styleVal + '"></div>';

                                                if(this.stockFlg['val']['order'] === warehouseReservInfo[i]['stock_flg']){
                                                    tooltipVal += this.stockFlg['list'][warehouseReservInfo[i]['stock_flg']] + '：' + currentWRQ + '<br>';
                                                }else{
                                                    tooltipVal += warehouseReservInfo[i]['warehouse_name'] + '(' + (this.stockFlg['list'][warehouseReservInfo[i]['stock_flg']]) + ')' + '：' + currentWRQ + '<br>';
                                                }
                                                colorCnt++;
                                            }
                                            
                                        }
                                    }
                                    
                                    cell.innerHTML = headHtml + cellHtml + '</div>';
                                
                                    // 内訳　TODO
                                    this.tooltip.setTooltip(
                                        "#" + idVal,
                                        tooltipVal
                                    );

                                }
                            }
                            break;
                        case 'order_status_value' :
                            if(dataItem !== undefined){
                                // 表示加工
                                cell.innerHTML ='<div style="float: left;">' + 
                                                    this.$options.filters.comma_format(dataItem.sum_reserve_quantity) +
                                                '</div>';

                                cell.innerHTML += '<div style="float: right;">' +
                                                    this.$options.filters.comma_format(dataItem.quote_quantity) +
                                                '</div>';
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
                        case 'sales_use_flg':
                            if(dataItem !== undefined){
                                if(dataItem.layer_flg === this.FLG_OFF || dataItem.set_flg === this.FLG_ON){
                                    cell.childNodes[0].disabled = true;
                                }
                            }
                            break;
                        case 'product_auto_flg':
                            if(dataItem !== undefined){
                                // if(dataItem.child_parts_flg === this.FLG_ON){
                                //     cell.childNodes[0].disabled = true;
                                // }
                                if(dataItem.layer_flg !== this.FLG_ON && this.rmUndefinedZero(dataItem.product_id) === 0){
                                    cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.PRODUCT_AUTO_CELL;
                                }
                            }
                            break;
                        case 'product_definitive_flg':
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
                    // 行に対する読み取り専用
                    //panel.rows[r].isReadOnly = true;
                }
            }.bind(this);

            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                // 読み取り専用セルスキップ
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
                // クリップボード処理
                if(gridCtrl.autoClipboard === false){
                    // 機能削除対応 20200803
                    // this.wjMultiRowClipboardCtrl(wjCore.Clipboard, gridCtrl, this.NON_PASTING_COLS, this.wjMultiRowClipBoardValidation, function(pastedRowList){
                    //     for(let i in pastedRowList){
                    //         // 階層の場合、階層名変更
                    //         this.changeProduct(null, pastedRowList[i], false);
                    //         // 行の金額を計算
                    //         this.calcTreeGridRowData(pastedRowList[i], 'order_quantity');
                    //     }
                    //     // 全体の計算
                    //     this.calcGridCostSalesTotal();
                    // }.bind(this));
                }

                // 選択行削除(DELETEキー押下時の処理)
                var selectedRowList = gridCtrl.selectedRows;
                if (event.keyCode == 46 && selectedRowList.length > 0 && selectedRowList.length%2 === 0) {
                    // 機能削除対応 20200803
                    // for (const key in selectedRowList) {
                    //     const item = selectedRowList[key].dataItem;
                    //     var findReceivedOrderIdx = gridCtrl.collectionView.sourceCollection.findIndex((rec) => {
                    //         // 選択行に見積明細IDがある
                    //         return (this.rmUndefinedZero(item.quote_detail_id) !== 0)
                    //     });
                    //     if (findReceivedOrderIdx != -1) {
                    //         alert(MSG_ERROR_DELETE_ROW);
                    //         return;
                    //     }
                    // }

                    // // 削除するか確認
                    // var result = window.confirm(MSG_CONFIRM_DELETE);
                    // if (result) {
                    //     for (const key in selectedRowList) {
                    //         const item = selectedRowList[key];
                    //         this.deleteTreeGridRow(gridCtrl.collectionView.sourceCollection, this.wjTreeViewControl, item.dataItem.filter_tree_path);
                    //     }
                    //     this.wjTreeViewControl.loadTree();
                    //     // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                    //     this.checkedAllTreeGrid(false);
                    //     this.calcGridCostSalesTotal();
                    // }
                }
            }.bind(this), true);

            // 商品コードオートコンプリート
            this.cgeProductCode = new CustomGridEditor(gridCtrl, 'product_code', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "product_code",
                displayMemberPath: "product_code",
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
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
                    if(this.cgeProductCode.loadingFlg){
                        return;
                    }

                    if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                        return;
                    }

                    this.setASyncAutoCompleteList(this.cgeProductCode, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
                }
            }, 2, 1, 2);
            // 商品名オートコンプリート
            this.cgeProductName = new CustomGridEditor(gridCtrl, 'product_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "product_name, product_short_name",
                displayMemberPath: "product_name",
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
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
                    if(this.cgeProductName.loadingFlg){
                        return;
                    }

                    if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH){
                        return;
                    }

                    this.setASyncAutoCompleteList(this.cgeProductName, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
                }
            }, 2, 1, 1);
            // メーカー名オートコンプリート
            this.cgeMaker = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "supplier_name, supplier_short_name",
                displayMemberPath: "supplier_name",
                itemsSource: this.makerList,
                selectedValuePath: "id",
                isRequired: false,
                maxItems: this.makerList.length,
                textChanged: this.setTextChanged,
                minLength: 1,
            }, 2, 1, 1);
            // 仕入先名オートコンプリート
            this.cgeSupplier = new CustomGridEditor(gridCtrl, 'maker_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "supplier_name, supplier_short_name",
                displayMemberPath: "supplier_name",
                itemsSource: null,
                selectedValuePath: "unique_key",
                isRequired: false,
                textChanged: this.setTextChanged,
                minLength: 1,
            }, 2, 2, 1);

            return gridCtrl;
        },

        // 【ツリー】作成
        createTreeCtrl(targetTreeDivId, treeItemsSource) {
            var treeCtrl = new wjNav.TreeView(targetTreeDivId, {
                itemsSource: treeItemsSource,
                displayMemberPath: "header",
                childItemsPath: "items",
                showCheckboxes: !this.isReadOnly,
                //allowDragging: !this.isReadOnly,  // 機能削除対応 20200803
                
                // ドラッグ開始
                dragStart: () =>{
                    this.DRAG_FILTER_TREE_PATH = '';
                },
                // ドロップ
                drop: (s, e) => {
                    // ドラッグした階層
                    var target = e.dragSource;
                    // ドロップ位置の親階層　　　position 0:before(何も無いところに置いた) 2:into(階層に置いた)
                    var parent = e.position === wjNav.DropPosition.Into ? e.dropTarget :  e.dropTarget.parentNode;
                    //var position = e.position

                    var isCancel = false;
                    switch(target.level){
                        case 1: // 工事区分
                            if(parent === null || parent.level !== 0){
                                // 工事区分が別階層に移動しようとした
                                alert(MSG_ERROR_MOVE_TO_OTHER_LAYER);
                                isCancel = true;
                            }
                            break;
                        default :
                            if(parent === null || parent.dataItem['top_flg'] === this.FLG_ON){
                                // 通常の階層が トップ または 工事区分 に移動しようとした
                                alert(MSG_ERROR_MOVE_TO_CONSTRUCTION_LAYER);
                                isCancel = true;
                            }else{
                                var parentIsAddLayer = this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControle, parent.dataItem);
                                var targetIsAddLayer = this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControle, target.dataItem);

                                var parentIsSetProduct = this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControle, parent.dataItem);
                                var targetIsSetProduct = this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControle, target.dataItem);
                                if(parentIsAddLayer && !targetIsAddLayer){
                                    // 通常の階層が 追加部材 に移動しようとした
                                    alert(MSG_ERROR_MOVE_TO_ADD_CONSTRUCTION_LAYER);
                                    isCancel = true;
                                }else if(!parentIsAddLayer && targetIsAddLayer){
                                    // 追加部材配下から別の階層に移動しようとした
                                    alert(MSG_ERROR_MOVE_FROM_ADD_CONSTRUCTION_LAYER);
                                    isCancel = true;
                                }else if(parent.dataItem['set_flg'] === this.FLG_ON || parentIsSetProduct){
                                    // 一式に移動しようとした
                                    alert(MSG_ERROR_MOVE_TO_SET_PRODUCT);
                                    isCancel = true;
                                }else if(target.dataItem['set_flg'] === this.FLG_ON || targetIsSetProduct){
                                    // 一式が移動しようとした
                                    // alert('一式が移動しようとした');
                                    // isCancel = true;
                                }
                            }
                            break;
                    }

                    if(isCancel){
                        e.cancel = true;
                    }else{
                        this.DRAG_FILTER_TREE_PATH = target.dataItem['filter_tree_path'];
                    }
                },
                // ドラッグ ドロップ 完了
                dragEnd: (wjTreeViewControl, e)=>{
                    if(this.rmUndefinedBlank(this.DRAG_FILTER_TREE_PATH) !== ''){
                        var gridData = this.getUpdateFilterTreePathData(this.wjMultiRowControle, wjTreeViewControl);

                        var gridItemSource = new wjCore.CollectionView(gridData, {
                            newItemCreator: function () {
                                return Vue.util.extend({}, INIT_ROW);
                            }
                        });

                        this.wjMultiRowControle.dispose();
                        this.wjMultiRowControle = this.createGridCtrl('#orderDetailGrid', gridItemSource);
                        
                        this.wjTreeViewControl.loadTree();
                        // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                        this.checkedAllTreeGrid(false);
                        this.selectTree(wjTreeViewControl, 'top_flg', this.FLG_ON);
                        this.calcGridCostSalesTotal();
                    }
                }
            });

            // 選択イベントに処理を紐付け
            treeCtrl.selectedItemChanged.addHandler(function(sender) {
                if(sender.selectedItem === null){return;}
                var kbnIdList = this.getTreeKbnId(sender);
                this.filterGrid(kbnIdList.top_flg, kbnIdList.layer_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.add_flg, kbnIdList.to_layer_flg);
            }.bind(this));
            
            // チェック状態変更イベント
            treeCtrl.isCheckedChanging.addHandler(function (s, e) {
                // 親階層へのチェック除去↓
                var checked = !e.node.isChecked;
                e.node.element.childNodes[0].checked = checked;
                if(checked){
                   e.cancel = true; 
                }

                var dataItem = e.node.dataItem;

                for (var i = 0; i < this.wjMultiRowControle.collectionView.sourceCollection.length; i++) {
                    var record = this.wjMultiRowControle.collectionView.sourceCollection[i];
                    if(dataItem.top_flg === this.FLG_ON){
                        record.chk = checked;
                    }else{
                        if(record.filter_tree_path === dataItem.filter_tree_path || record.filter_tree_path.indexOf(dataItem.filter_tree_path + this.TREE_PATH_SEPARATOR) === 0){
                            record.chk = checked;
                        }
                    }
                }
                if(!checked){
                    // 上階層のグリッドのチェックを外す
                    this.checkedUpTreeGrid(dataItem.filter_tree_path, checked);
                }

                // 階層の以下チェック
                this.checkedDownTree(dataItem['items'], checked);
                // グリッド再描画
                this.wjMultiRowControle.collectionView.refresh();
            }.bind(this));

            return treeCtrl;
        },

/*************** 各種ボタン ***************/       
        // 一式作成ボタン
        toSet(){
            var rowList = [];

            var gridData = this.wjMultiRowControle.itemsSource.items;
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk) {
                    rowList.push(gridData[i]);
                }
            }
            if (rowList.length === 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            for (var i = 0; i < rowList.length; i++) {
                if(!this.toSetIsValid(rowList[i])){
                    return;
                }
            }
            // 一式化する
            this.treeGridDetailRecordToSet(this.wjMultiRowControle.itemsSource.items, this.wjTreeViewControl);
            // ツリー再読み込み
            this.wjTreeViewControl.loadTree();
            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            this.checkedAllTreeGrid(false);
            // グリッド再描画
            this.calcGridCostSalesTotal();

        },
        // 一式化できるかチェック
        toSetIsValid(row){
            var result = true;
            var chkResult = this.treeGridDetailRecordChkToSet(this.wjMultiRowControle, row);
            if(chkResult !== this.TREE_GRID_CHK_KBN_LIST.VALID){
                alert(this.MSG_LIST_TREE_GRID_CHK_KBN_TO_SET[chkResult]);
                result = false;
            }
            return result;
        },
        // 階層作成ボタン
        toLayer(){
            var rowList = [];

            var gridData = this.wjMultiRowControle.itemsSource.items;
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk) {
                    rowList.push(gridData[i]);
                }
            }
            if (rowList.length === 0) {
                alert(MSG_ERROR_NO_SELECT);
                return;
            }
            for (var i = 0; i < rowList.length; i++) {
                if(!this.toLayerIsValid(rowList[i])){
                    return;
                }
            }
            // 階層へ
            this.treeGridDetailRecordToLayer(this.wjMultiRowControle.itemsSource.items, this.wjTreeViewControl);
            // 階層化したタイミングで販売額利用フラグを立てる
            for (var i = 0; i < rowList.length; i++) {
                this.toLayerInitProp(rowList[i]);
                // 再計算
                this.calcTreeGridRowData(rowList[i], 'order_quantity');
            }
            // ツリー再読み込み
            this.wjTreeViewControl.loadTree();
            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
            this.checkedAllTreeGrid(false);
            // グリッド再描画
            //this.wjMultiRowControle.collectionView.refresh();
            this.calcGridCostSalesTotal();

        },
        // 階層化ができるかチェック
        toLayerIsValid(row){
            var result = true;
            if(row.layer_flg === this.FLG_ON){
                alert(MSG_ERROR_CREATE_LAYER);
                result = false;
            }else if(this.rmUndefinedBlank(row.order_id_list) !== ''){
                // 一度発注した明細は階層化できないようにする
                alert(MSG_ERROR_CREATE_LAYER);
                result = false;
            }else if(this.rmUndefinedZero(row.stock_reserve_quantity) !== 0){ 
                // 在庫引当した明細は階層化できないようにする
                alert(MSG_ERROR_CREATE_LAYER);
                result = false;
            }else if(this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControle, row)){
                // 追加部材は階層化できない
                alert(MSG_ERROR_ADD_KBN_CREATE_LAYER);
                result = false;
            }else if(this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControle, row)){
                // 一式配下(子部品)は階層化できない
                alert(MSG_ERROR_SET_PRODUCT_CREATE_LAYER);
                result = false;
            }else if(this.rmUndefinedZero(row.quote_detail_id) === 0){
                // alert('階層作成できるのは見積もり時に作成した明細のみです');
                // result = false;
            }
            return result;
        },
        // 階層化したあとに初期値をセット
        toLayerInitProp(row){
            //row.sales_use_flg       = true;
            row.intangible_flg      = this.FLG_ON;
            row.product_id          = INIT_ROW.product_id;
            row.min_quantity        = this.INIT_ROW_MIN_QUANTITY;
            row.order_lot_quantity  = row.min_quantity;
            row.order_quantity      = row.order_lot_quantity;
        },
        // 行追加（上）
        addRows() {
            // チェックが付いている明細行を特定
            //var gridData = this.wjMultiRowControle.collectionView.sourceCollection;
            // 対象のチェックボックスが正しいかは見えている行に対して検査する
            var gridData = this.wjMultiRowControle.itemsSource.items;
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);

            // 今開いている階層のチェックが付いたレコードを取得
            var rowList = [];
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk) {
                    rowList[i] = gridData[i];
                }
            }

            if(rowList.length === 0){
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            if(!this.addRowIsValid(this.wjMultiRowControle, kbnIdList)){
                return;
            }

            // 行追加
            var addList = this.addTreeGrid(this.wjMultiRowControle, this.wjTreeViewControl, kbnIdList.depth, kbnIdList.filter_tree_path, INIT_ROW, false);
            // 階層とグリッドのチェックボックスを外す(今開いている階層のみにしか追加しないので末尾の行を渡す)
            this.changeGridCheckBox(addList[addList.length-1]);

            // グリッド再描画
            this.wjMultiRowControle.collectionView.refresh();
        },
        // 行追加ができるかチェック
        addRowIsValid(wjMultiRowControle, kbnIdList){
            var result = true;
            if (kbnIdList.top_flg === this.FLG_ON){
                alert(MSG_ERROR_ADD_ROW);
                result = false;
            }else if(!wjMultiRowControle.allowAddNew){
                alert(MSG_ERROR_ADD_ROW);
                result = false;
            }
            return result;
        },
        // 行削除
        deleteRows() {
            // チェックが付いている明細行を特定
            var rowList = [];
            //var gridData = this.wjMultiRowControle.collectionView.sourceCollection;
            // 対象のチェックボックスが正しいかは見えている行に対して検査する
            var gridData = this.wjMultiRowControle.itemsSource.items;
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);
            for (var i = 0; i < gridData.length; i++) {
                if (gridData[i].chk) {
                    if((kbnIdList.top_flg === this.FLG_ON && gridData[i].depth === this.QUOTE_CONSTRUCTION_DEPTH) || 
                      (gridData[i].depth === (kbnIdList.depth+1) && gridData[i].filter_tree_path.indexOf(kbnIdList.filter_tree_path) === 0)){
                        rowList[i] = gridData[i];
                    }
                }
            }
            if (rowList.length === 0) {
                // チェックが付いている明細なし
                alert(MSG_ERROR_NO_SELECT);
                return;
            }

            for(let i in rowList) {
                if(this.rmUndefinedZero(rowList[i].quote_detail_id) !== 0){
                    alert(MSG_ERROR_DELETE_ROW);
                    return;
                }
            }

            var result = window.confirm(MSG_CONFIRM_DELETE);
            if (result) {
                // 行削除
                this.deleteTreeGridByGrid(this.wjMultiRowControle, this.wjTreeViewControl, kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path);

                this.wjTreeViewControl.loadTree();
                // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                this.checkedAllTreeGrid(false);
                this.calcGridCostSalesTotal();
            }
            //this.wjMultiRowControle.collectionView.refresh();
        },

        // グリッドに貼り付けしたときのチェック
        wjMultiRowClipBoardValidation(wjMultiRowControle, text){
            var result = true;
            var layount = wjMultiRowControle.layoutDefinition;
            var selectedRowList = wjMultiRowControle.selectedRows;
            var clipboardData = this.toWjMultiRowPasteTextFormat(text);

            if(clipboardData.length%2 !== 0){
                alert(MSG_ERROR_PASTE_FORMAT);
                result = false;
            }else{
                for(var i=0;i<selectedRowList.length && result;i++){
                    if(typeof selectedRowList[i].dataItem === 'undefined'){
                        //alert('追加済みの行を選択して下さい');
                        result = false;
                        break;
                    }
                    
                    // 列数が同じかどうかのチェック
                    for(var j=0; j<clipboardData.length && result; j++){
                        var clipboardDataRecord = clipboardData[j].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                        if(layount.length !== clipboardDataRecord.length){
                            alert(MSG_ERROR_PASTE_FORMAT);
                            result = false;
                            break;
                        }
                    }

                    if(i%this.WJ_MULTI_ROW_CNT === 0 && result){
                        // 選択したグリッド行をソースコレクションから取得(自身の行を取得)
                        var targetGridRecord = this.getChildGridDataList(wjMultiRowControle, selectedRowList[i].dataItem.filter_tree_path, selectedRowList[i].dataItem.depth);
                        result = this.wjMultiRowClipBoardDetailValidation(clipboardData, layount, targetGridRecord[0], i);
                    }
                }
            }
            return result;
        },
        // グリッドに貼り付けしたときのチェック(詳細)
        wjMultiRowClipBoardDetailValidation(clipboardData, layount, targetGridRecord, rowCnt){
            var result = true;
            for(var multiCnt=0; multiCnt<this.WJ_MULTI_ROW_CNT && result; multiCnt++){
                if(typeof clipboardData[rowCnt + multiCnt] !== 'undefined'){
                    var clipboardDataRecord = clipboardData[rowCnt + multiCnt].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                    if(layount.length !== clipboardDataRecord.length){
                        alert(MSG_ERROR_PASTE_FORMAT);
                        result = false;
                        break;
                    }
                    // 対象貼り付け位置の値とクリップボードの値チェック
                    for(var j=0; j<layount.length && result; j++){
                        if(typeof layount[j].cells[multiCnt] !== 'undefined'){
                            // グリッドのカラム名取得
                            var bindingName = layount[j].cells[multiCnt].binding;
                            var cellValue = clipboardDataRecord[j];
                            switch(bindingName){
                                case 'product_name':
                                    if(targetGridRecord.layer_flg === this.FLG_ON){
                                        // if(this.rmUndefinedBlank(cellValue) === ''){
                                        //     alert(MSG_ERROR_LAYER_NAME_NO_INPUT);
                                        //     result = false;
                                        //     break;
                                        // }
                                    }
                                    break;
                                case 'maker_id':
                                    if(this.rmUndefinedBlank(targetGridRecord.order_id_list) !== ''){
                                        if(!isFinite(cellValue) || targetGridRecord.maker_id !== parseInt(cellValue)){
                                            alert(MSG_ERROR_ORDER_COMPLETE_MAKER_CHANGE);
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                                case 'min_quantity':
                                    if(this.rmUndefinedZero(targetGridRecord.quote_detail_id) !== 0){
                                        if(!isFinite(cellValue) || targetGridRecord.min_quantity !== parseFloat(cellValue)){
                                            alert(MSG_ERROR_CHANGE_PRODUCT_DUE_TO_MIN_QUANTITY_DIFFERENT);
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                                case 'layer_flg':
                                    if(targetGridRecord.layer_flg === this.FLG_ON){
                                        if(!isFinite(cellValue) || parseInt(cellValue) !== this.FLG_ON){
                                            alert(MSG_ERROR_PASTE_FROM_DETAIL_TO_LAYER);
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                                case 'set_flg':
                                    if(targetGridRecord.set_flg === this.FLG_ON){
                                        if(!isFinite(cellValue) || parseInt(cellValue) !== this.FLG_ON){
                                            alert(MSG_ERROR_PASTE_TO_SET_PRODUCT);
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                                case 'intangible_flg':
                                    if(this.rmUndefinedZero(targetGridRecord.quote_detail_id) !== 0){
                                        if(!isFinite(cellValue) || targetGridRecord.intangible_flg !== parseInt(cellValue)){
                                            if(targetGridRecord.intangible_flg === this.FLG_ON){
                                                alert(MSG_ERROR_FROM_INTANGIBLE_TO_TANGIBLE);
                                            }else{
                                                alert(MSG_ERROR_FROM_TANGIBLE_TO_INTANGIBLE);
                                            }
                                            result = false;
                                            break;
                                        }
                                    }
                                    break;
                                default:
                                    break;
                            }
                            
                        }
                    }
                }
            }
                    
                
            return result;
        },
/*************** ボタン 終了***************/


/*************** グリッド フィルター ***************/
        // 直近の配下のみ
        filterGrid(topFlg, layerFlg, depth, filterTreePath, addFlg, toLayerFlg) {
            // this.wjMultiRowControle.isReadOnly = this.isReadOnly;

            // TODO
            // if(addFlg === this.FLG_ON || toLayerFlg === this.FLG_ON){
            //     this.wjMultiRowControle.allowAddNew = !this.isReadOnly;
            //     this.wjMultiRowControle.allowDelete = !this.isReadOnly;
            // }

            // if(layerFlg === this.FLG_ON){
            //     this.wjMultiRowControle.collectionView.filter = record => {
            //         return this.isTreeGridVisibleTarget(record, topFlg, depth, filterTreePath);
            //     }
            // }
            this.defaultFilter();

            
        },
        btnFilterClick(){
            // 絞り込み
            this.activeFilterInfo = JSON.parse(JSON.stringify(this.filterInfo));
            var selectFilterTreePath = this.defaultFilter();
            if(selectFilterTreePath !== ''){
                this.selectTree(this.wjTreeViewControl, 'filter_tree_path', selectFilterTreePath);
            }else{
                this.selectTree(this.wjTreeViewControl, 'top_flg', this.FLG_ON);
            }
        },
        inputDefaultFilterText(){
            // 絞り込み
            this.defaultFilter();
        },

        // 絞り込み
        defaultFilter() {
            //var filter = this.filterInfo.filerText.toLowerCase();
            var statusVal = this.activeFilterInfo.orderStatusCheckList;
            var gridFilterDisplayList = [];
            var selectTreePath = '';

             var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);

            for(let i in this.wjMultiRowControle.collectionView.sourceCollection){
                var record = this.wjMultiRowControle.collectionView.sourceCollection[i];
                var result = false;
                var quoteQuantity = this.rmUndefinedZero(record.quote_quantity);
                var sumReserveQuantity = this.rmUndefinedZero(record.sum_reserve_quantity);

                // OR条件
                if(statusVal.length === 0){
                    result = true;
                }else{
                    var isOrderStatusCheckValid = false;
                    if(statusVal.indexOf(this.orderStatusList['not_order']['val']) != -1){
                        isOrderStatusCheckValid = true;
                        result = result || (sumReserveQuantity === 0.00 ? true : false);
                    }
                    if(statusVal.indexOf(this.orderStatusList['part_order']['val']) != -1){
                        isOrderStatusCheckValid = true;
                        result = result || (sumReserveQuantity !== 0.00 && sumReserveQuantity < quoteQuantity ? true : false);
                    }
                    if(statusVal.indexOf(this.orderStatusList['done_order']['val']) != -1){
                        isOrderStatusCheckValid = true;
                        result = result || (sumReserveQuantity !== 0.00 && sumReserveQuantity >= quoteQuantity ? true : false);
                    }
                    if(isOrderStatusCheckValid){
                        // 発注状況の検索は階層の場合は全てFALSEにする
                        if(record.layer_flg === this.FLG_ON){
                            result = false;
                        }
                    }
                }

                // AND条件
                if(this.activeFilterInfo.maker.valid){
                    // メーカー
                    result = result && (record.maker_id == this.activeFilterInfo.maker.id);
                }
                if(this.activeFilterInfo.supplier.valid){
                    // 仕入先
                    result = result && (record.supplier_id == this.activeFilterInfo.supplier.id);
                }
                if(this.activeFilterInfo.order.valid){
                    // 発注番号
                    var orderIdList = record.order_id_list.split(',');
                    if(orderIdList.length >= 1 && this.rmUndefinedZero(this.activeFilterInfo.order.id) !== 0){
                        result = result && (orderIdList.indexOf(this.activeFilterInfo.order.id.toString()) > -1);
                    }else{
                        result = false;
                    }
                }
                if(this.activeFilterInfo.product.valid){
                    // 商品コード
                    result = result && (record.product_id == this.activeFilterInfo.product.id);
                }
                if(this.activeFilterInfo.start_date.valid){
                    // 発注予定日
                    result = result && (this.rmUndefinedBlank(record.matter_detail_start_date) == this.rmUndefinedBlank(this.activeFilterInfo.start_date.text));
                }
                // AND条件
                // if(filter.length >= 1){
                //     result = result && ((record.product_code != null && (record.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                //             (record.product_name != null && (record.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                //             (record.model != null && (record.model).toString().toLowerCase().indexOf(filter) > -1) ||
                //             (record.maker_name != null && (record.maker_name).toString().toLowerCase().indexOf(filter) > -1) ||
                //             (record.supplier_name != null && (record.supplier_name).toString().toLowerCase().indexOf(filter) > -1));
                // }

                if(result){
                    gridFilterDisplayList.push(record);
                }
            };
            // 階層を非表示にする
            var parentFilterTreePathList = this.defaultFilterTree(gridFilterDisplayList);
            if(parentFilterTreePathList.length === 1){
                selectTreePath = parentFilterTreePathList[0];
            }

            // グリッドを非表示にする
            this.defaultFilterTreeGrid(gridFilterDisplayList);
            // 絞り込みの条件が入っているか
            this.isFiltering = this.isFilterValid();
            // 行追加や削除可能かどうかをグリッドにセットする
            this.setGridCtrl(kbnIdList.top_flg);

            return selectTreePath;
        },
        // 階層の表示を切り替える
        defaultFilterTree(gridFilterDisplayList){
            // 階層全て非表示
            this.displayDownTree(this.wjTreeViewControl.itemsSource[0]['items'], 'none');

            var parentFilterTreePathList = [];
            for(var i=0 ; i<gridFilterDisplayList.length ; i++){
                var displayGridRow = gridFilterDisplayList[i];
                var item = null;
                var displayFilterTreeParh = '';
                if(displayGridRow.layer_flg === this.FLG_ON){
                    displayFilterTreeParh = displayGridRow.filter_tree_path;
                    item = this.findTree(this.wjTreeViewControl.itemsSource, 'filter_tree_path', displayFilterTreeParh);
                }else{
                    displayFilterTreeParh = this.getParentFilterTreePath(displayGridRow.filter_tree_path);
                    item = this.findTree(this.wjTreeViewControl.itemsSource, 'filter_tree_path', displayFilterTreeParh);
                }
                
                var treeNode = this.wjTreeViewControl.getNode(item);
                treeNode.element.style.display="";
                // 1つ上の階層を保持
                if(parentFilterTreePathList.indexOf(treeNode.dataItem['filter_tree_path']) == -1){
                    parentFilterTreePathList.push(treeNode.dataItem['filter_tree_path']);
                }
                // 一致した階層以下は全て表示
                if(displayGridRow.layer_flg === this.FLG_ON){
                    if(treeNode.parentNode !== null){
                        // nullはありえない想定
                        this.displayDownTree(treeNode.parentNode.dataItem['items'], '');
                    }
                }else{
                    this.displayDownTree(treeNode.dataItem['items'], '');
                }
 
                // 親階層を表示する
                while(treeNode.parentNode !== null){
                    treeNode = treeNode.parentNode;
                    treeNode.element.style.display="";
                }
                
            }
            return parentFilterTreePathList;
        },
        // グリッドの表示を切り替える
        defaultFilterTreeGrid(gridFilterDisplayList){
            // 今選択している階層情報
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);
            // フリーテキストはリアルタイムに検索させるので「this.filterInfo」を使用する
            var filter = this.filterInfo.filerText.toLowerCase();

            this.wjMultiRowControle.collectionView.filter = record => {
                var result = false;
                if(this.isTreeGridVisibleTarget(record, kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path)){
                    // 今選択している階層
                    for(var i=0 ; i<gridFilterDisplayList.length ; i++){
                        if(record.filter_tree_path === gridFilterDisplayList[i].filter_tree_path){
                            // 一致したデータ
                            result = true;
                        }else{
                            if(gridFilterDisplayList[i].depth === this.QUOTE_CONSTRUCTION_DEPTH){
                                result = true;
                            }else{
                                var parentFilterTreePath = this.getParentFilterTreePath(gridFilterDisplayList[i].filter_tree_path);
                                if(record.depth === gridFilterDisplayList[i].depth && 
                                    (record.filter_tree_path === parentFilterTreePath || record.filter_tree_path.indexOf(parentFilterTreePath + this.TREE_PATH_SEPARATOR) === 0)){
                                    // 同じ階層内の明細と階層は全て表示
                                    result = true;
                                }else{
                                    if(gridFilterDisplayList[i].filter_tree_path === record.filter_tree_path || gridFilterDisplayList[i].filter_tree_path.indexOf(record.filter_tree_path + this.TREE_PATH_SEPARATOR) === 0){
                                        // 上の階層
                                        result = true;
                                    }else if(record.depth > gridFilterDisplayList[i].depth && (record.filter_tree_path === parentFilterTreePath || record.filter_tree_path.indexOf(parentFilterTreePath + this.TREE_PATH_SEPARATOR) === 0)){
                                        // 下の階層と明細
                                        result = true;
                                    }
                                }
                            }
                        }
                    }
                    if(filter.length >= 1){
                        var costKbn = this.priceList.find((rec) => {
                            return (rec.value_code === record.cost_kbn);
                        });
                        // フリーテキスト
                        result = result && ((this.rmUndefinedBlank(record.product_code) !== '' && (record.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.matter_detail_start_date) !== '' && (record.matter_detail_start_date).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.product_name) !== '' && (record.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.model) !== '' && (record.model).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.maker_name) !== '' && (record.maker_name).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.supplier_name) !== '' && (record.supplier_name).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.stock_reserve_quantity) !== '' && (record.stock_reserve_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.order_quantity) !== '' && (record.order_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.unit) !== '' && (record.unit).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.stock_quantity) !== '' && (record.stock_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.stock_unit) !== '' && (record.stock_unit).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.regular_price) !== '' && (record.regular_price).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(costKbn) !== '' && (costKbn.value_text_1).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.cost_unit_price) !== '' && (record.cost_unit_price).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.sales_unit_price) !== '' && (record.sales_unit_price).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.cost_makeup_rate) !== '' && (record.cost_makeup_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.sales_makeup_rate) !== '' && (record.sales_makeup_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.cost_total) !== '' && (record.cost_total).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.sales_total) !== '' && (record.sales_total).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.gross_profit_rate) !== '' && (record.gross_profit_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.profit_total) !== '' && (record.profit_total).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.memo) !== '' && (record.memo).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.quote_quantity) !== '' && (record.quote_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                                (this.rmUndefinedBlank(record.sum_reserve_quantity) !== '' && (record.sum_reserve_quantity).toString().toLowerCase().indexOf(filter) > -1)
                                );
                    }
                }

                return result;
            }
        },
        displayDownTree(items, displayText){
            for(var i =0;i<items.length;i++){
                this.wjTreeViewControl.getNode(items[i]).element.style.display=displayText;
                this.displayDownTree(items[i]['items'], displayText);
            }
        },
/*************** グリッド フィルター 終了 ***************/


/*************** 関数 ***************/

        // 選択している階層の分類などのIDを返す
        getTreeKbnId(selectedTree){
            var result = {
                top_flg     : null,
                layer_flg   : null,
                depth       : null,
                filter_tree_path    : null,
                construction_id     : null,
                add_flg     : null,
                to_layer_flg : null,
            }
            
            result.top_flg  = selectedTree.selectedItem.top_flg;
            result.layer_flg= selectedTree.selectedItem.layer_flg; 
            result.depth    = selectedTree.selectedItem.depth;
            result.filter_tree_path = selectedTree.selectedItem.filter_tree_path;
            result.construction_id  = selectedTree.selectedItem.construction_id;
            result.add_flg  = selectedTree.selectedItem.add_flg;
            result.to_layer_flg  = selectedTree.selectedItem.to_layer_flg;
            return result;
        },

        // グリッドのチェックボックス変更時に呼ぶ
        // 引数で渡ってきたグリッドの行のチェック状態に合わせてグリッドと階層のチェックを外す
        changeGridCheckBox(row){
            var filterTreePath = row.filter_tree_path;
            var chk = row.chk;
            
            if(row.layer_flg === this.FLG_ON){
                var item = this.findTree(this.wjTreeViewControl.itemsSource, 'filter_tree_path', filterTreePath);
                var treeNode = this.wjTreeViewControl.getNode(item);
                treeNode.isChecked = chk;
            }else{
                if(!chk){
                    var item = this.findTree(this.wjTreeViewControl.itemsSource, 'filter_tree_path', this.getParentFilterTreePath(filterTreePath));
                    var treeNode = this.wjTreeViewControl.getNode(item);
                    // TODO
                    this.checkedUpTree(treeNode, chk);
                }
            }

            if(!chk){
                // 上階層のグリッドのチェックを外す
                this.checkedUpTreeGrid(filterTreePath, chk);
            }  
        },

        // 上の階層のチェックを変更する(イベント発生させない)
        checkedUpTree(node, checked){  
            if(node != undefined){
                node.element.childNodes[0].checked = checked;
                this.checkedUpTree(node.parentNode, checked);
            }else{
                return;
            }
        },
        // 下の階層のチェックを変更する(イベント発生させない)
        checkedDownTree(items, checked){
            for(var i =0;i<items.length;i++){
                //this.wjTreeViewControl.getNode(items[i]).isChecked = checked;
                // イベント発生させない
                this.wjTreeViewControl.getNode(items[i]).element.childNodes[0].checked = checked;
                this.checkedDownTree(items[i]['items'], checked);
            }
        },
        // 上の階層のグリッドのチェックを変更する
        checkedUpTreeGrid(filterTreePath, checked){
            var record = this.wjMultiRowControle.collectionView.sourceCollection.find((rec) => {
                    return (rec.filter_tree_path === filterTreePath);
                });
            if(typeof record === 'undefined'){
                return;
            }
            record.chk = checked;
            if(filterTreePath.lastIndexOf(this.TREE_PATH_SEPARATOR) !== -1){
                this.checkedUpTreeGrid(filterTreePath.slice(0, filterTreePath.lastIndexOf(this.TREE_PATH_SEPARATOR)), checked);
            }
        },
        // 全グリッド行のチェックを変更する
        checkedAllTreeGrid(checked){
            for(let i in this.wjMultiRowControle.collectionView.sourceCollection){
                this.wjMultiRowControle.collectionView.sourceCollection[i].chk = checked;
            }
        },

        // グリッドの商品変更時の最小単位数チェック　商品IDのクリアなど
        changingProduct(product, row, isCode){
            var isCancel = false;
            if(row.layer_flg === this.FLG_OFF && row.set_flg === this.FLG_OFF){
                var selectedItem = product.selectedItem;


                var isErr = false;

                if(selectedItem !== null){
                    if(selectedItem.product_id === PRODUCT_AUTO_COMPLETE_SETTING.DEFAULT_PRODTCT_ID){
                        // 上限数メッセージを取得した
                        alert(MSG_ERROR_NO_SELECT_PRODUCT);
                        isErr = true;
                    }else{
                        if(this.rmUndefinedZero(row.quote_detail_id) !== 0){
                            if(!this.bigNumberEq(row.min_quantity, selectedItem.min_quantity)){
                                alert(MSG_ERROR_CHANGE_PRODUCT_DUE_TO_MIN_QUANTITY_DIFFERENT + ' 現在の最小単位数：' + row.min_quantity);
                                isErr = true;
                            }else if(this.rmUndefinedBlank(row.order_id_list) !== '' && this.rmUndefinedZero(selectedItem.maker_id) !== 0 && selectedItem.maker_id !== row.maker_id){
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
                    }
                }else{
                    if(this.rmUndefinedZero(row.quote_detail_id) !== 0){
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
                        // row.product_id = INIT_ROW.product_id;
                        // row.product_maker_id = INIT_ROW.product_maker_id;
                        if(isCode){
                            row.product_id = INIT_ROW.product_id;
                            row.product_maker_id = INIT_ROW.product_maker_id;
                            if(this.rmUndefinedBlank(product.control.text) === ''){
                                row.intangible_flg = this.FLG_ON;
                            }else{
                                row.intangible_flg = this.FLG_OFF;
                            }
                        }
                    }
                    if(!isCode && this.rmUndefinedBlank(product.control.text) !== row.product_name && row.intangible_flg === this.FLG_ON){
                        // 商品マスタから選択して商品コードがある無形品は商品名変更時に商品コードを空白にする
                        row.product_code = INIT_ROW.product_code;
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
                    var productInfo                 = await this.getProductInfo(product.product_id, this.base.customer_id);
                    if(productInfo !== undefined){
                        var productData             = productInfo['product'];

                        row.product_code = productData.product_code;                     // 商品コード
                        if(isCode){
                            row.product_name = productData.product_name;                 // 商品名
                        }else{
                            // row.product_code = productData.product_code;                 // 商品コード
                        }
                        row.product_id = productData.product_id                          // 商品ID
                        row.model = productData.model;                                   // 型式・規格
                        row.stock_unit = productData.stock_unit;                         // 管理数単位
                        row.min_quantity = parseFloat(productData.min_quantity);         // 最小単位数
                        row.order_lot_quantity = parseFloat(productData.order_lot_quantity);   // 発注ロット数
                        row.intangible_flg = productData.intangible_flg;                 // 無形品フラグ

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
                            row.order_quantity = INIT_ROW.order_quantity;            // 発注数
                        }

                        row.regular_price = productData.price;                           // 定価
                        row.unit = productData.unit                                      // 仕入単位
                        //row.cost_kbn = row.sales_kbn;                                // 販売区分と仕入区分を同じものにする
                        this.setTreeGridUnitPriceNew(row, true, productInfo['costProductPriceList'], false);       // 仕入単価
                        //this.setTreeGridUnitPriceNew(row, false, false);                // 販売単価
                        this.calcTreeGridChangeUnitPrice(row, true);                // 仕入掛率再計算
                        this.calcTreeGridChangeUnitPrice(row, false);               // 販売掛率再計算
                        
                        row.product_maker_id        = productData.maker_id;                 // 商品マスタのメーカーID
                        
                            
                        var isFind = false;
                        // 選んだ商品のメーカーが無い場合は変更しない
                        if(this.rmUndefinedZero(row.product_maker_id) !== 0){
                            for(var i = 0; i < this.supplierList.length; i++){
                                if (this.supplierList[i].id == row.product_maker_id) {
                                    row.maker_id = row.product_maker_id;                     // メーカーID
                                    row.maker_name = this.supplierList[i].supplier_name; // メーカー
                                    isFind = true;
                                    break;
                                }
                            }
                            if(!isFind){
                                row.maker_id = INIT_ROW.maker_id;                       // メーカーID
                                row.maker_name = INIT_ROW.maker_name;                   // メーカー名
                            }
                        }

                        // 選択している仕入先が新しく選択したメーカーのリストに存在するか
                        if(this.supplierMakerList[row.maker_id] !== undefined){
                            var findIdx = this.supplierMakerList[row.maker_id].findIndex((rec) => {
                                return (rec.supplier_id == row.supplier_id);
                            });
                            if(findIdx === -1){
                                row.supplier_id = INIT_ROW.supplier_id;                 // 仕入先ID
                                row.supplier_name = INIT_ROW.supplier_name;             // 仕入先名
                            }
                        }else{
                            row.supplier_id = INIT_ROW.supplier_id;                     // 仕入先ID
                            row.supplier_name = INIT_ROW.supplier_name;                 // 仕入先名
                        }

                        // 1回限りチェックを外す
                        if (row.child_parts_flg === this.FLG_OFF) {
                            row.product_auto_flg = this.FLG_OFF;
                        }

                        if(this.rmUndefinedBlank(productData.end_date) !== '' &&
                        parseInt(this.trimHyphen(productData.end_date)) < this.todayYYYYMMDD){
                            if(this.rmUndefinedZero(productData.new_product_id) === 0){
                                // 新品番無しの廃版
                                alert(MSG_WARNING_END_DATE);
                            }else{
                                // 新品番あり
                                alert(MSG_WARNING_NEW_PRODUCT)
                            }
                        }
                        // 再計算
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                    }
                }
            }else{
                // 階層名変更の場合
                if(row.layer_flg === this.FLG_ON){
                    var productName = row.product_name;
                    //if(this.rmUndefinedBlank(productName) !== ''){
                        var item = this.findTree(this.wjTreeViewControl.itemsSource, 'filter_tree_path', row.filter_tree_path);
                        item['header'] = productName;
                        this.wjTreeViewControl.loadTree();

                        //this.wjTreeViewControl.getNode(item).refresh();
                        this.checkedDownTree(this.wjTreeViewControl.nodes[0].dataItem['items'], false);
                        // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                        this.checkedAllTreeGrid(false);
                    //}
                }else{
                    
                }
            }
        },
        /**
         * 仕入/販売区分変更時に呼び出す
         * @param row   行
         */
        async changeCostSalesKbn(row){
            // 非同期で取得
            var unitPriceInfo           = await this.getUnitPrice(row.product_id, this.base.customer_id);
            if(unitPriceInfo !== undefined){
                this.setTreeGridUnitPriceNew(row, true, unitPriceInfo['costProductPriceList'], false);       // 仕入単価
                this.calcTreeGridChangeUnitPrice(row, true);
                this.calcTreeGridRowData(row, 'order_quantity');
                this.calcGridCostSalesTotal();
            }
        },
       
        // 仕入れ総額と販売総額を階層ごとに計算する
        calcGridCostSalesTotal(){
            this.calcTreeGridCostSalesTotal(this.wjMultiRowControle, this.wjTreeViewControl.nodes[0].dataItem['items'], 'order_quantity');
            this.wjMultiRowControle.collectionView.refresh();
        },
        setGridCtrl(topFlg){
            this.wjMultiRowControle.isReadOnly = this.isReadOnly;
            if(topFlg === this.FLG_ON){
                this.wjMultiRowControle.allowAddNew = false;
                //this.wjMultiRowControle.allowDelete = false;
            }else{
                if(this.isFiltering){
                    this.wjMultiRowControle.allowAddNew = false;
                }else{
                    // 機能削除対応 20200803
                    //this.wjMultiRowControle.allowAddNew = !this.isReadOnly;
                    this.wjMultiRowControle.allowAddNew = false;
                }
                this.wjMultiRowControle.allowDelete = !this.isReadOnly;
            }
        },
        // 現在日時を取得
        getToday(){
            var date = new Date();
            var result = date.getFullYear();
            result += (date.getMonth() + 1).toString().padStart(2, '0') + date.getDate().toString().padStart(2, '0');
            return result;
        },
        // ハイフン除去
        trimHyphen(str){
            return str.replace(/-/g, "");
        },
        // 絞り込み系が有効になっているか
        isFilterValid(){
            var result = false;
            // 発注状況の絞り込みが有効か
            var statusVal = this.activeFilterInfo.orderStatusCheckList;
            if(statusVal.indexOf(this.orderStatusList['not_order']['val']) != -1){
                result = true;
            }else if(statusVal.indexOf(this.orderStatusList['part_order']['val']) != -1){
                result = true;
            }else if(statusVal.indexOf(this.orderStatusList['done_order']['val']) != -1){
                result = true;
            }

            // コンボの絞り込みが有効化
            if(!result){
                if(this.activeFilterInfo.maker.valid){
                    result = true;
                }
                if(this.activeFilterInfo.supplier.valid){
                    result = true;
                }
                if(this.activeFilterInfo.order.valid){
                    result = true;
                }
                if(this.activeFilterInfo.product.valid){
                    result = true;
                }
            }

            // フリーテキストの絞り込みが有効か
            if(!result){
                if(this.filterInfo.filerText !== ''){
                    result = true;
                }
            }
            return result;
        },

        // グリッドレイアウト定義取得
        getGridLayout () {
            // 価格区分
            var priceKbnMap = new wjGrid.DataMap(this.priceList, 'value_code', 'value_text_1');
            // 引当区分
            //var allocKbnMap = new wjGrid.DataMap(this.allocList, 'value_code', 'value_text_1');
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
                { cells: [
                    { name: 'btn_up', binding: 'btn_up', header: ' ', width: 30, isReadOnly: true, visible: false},     // 機能削除対応 20200803
                    { name: 'btn_down', binding: 'btn_down', header: ' ', width: 30, isReadOnly: true, visible: false}, // 機能削除対応 20200803
                ] },
                { cells: [{ name: 'chk', binding: 'chk', header: '選択', width: 30, isReadOnly: false }] },
                { cells: [{ name: 'fix', header: '修正',  width: 60, isReadOnly: true }] },
                { cells: [{ name: 'matter_detail_start_date', binding: 'matter_detail_start_date', header: '発注予定日',  width: 100, isReadOnly: true }] },
                { cells: [{ name: 'product_code', binding: 'product_code', header: '品番', width: 150, isReadOnly: false, isRequired: false }] },
                { cells: [{ name: 'btn_no_product_code', binding: 'btn_no_product_code', header: '　', width: 30, isReadOnly: true }] },
                { cells: [
                    { name: 'product_name', binding: 'product_name', header: '商品名', width: 200, isReadOnly: false, isRequired: false },
                    { name: 'model', binding: 'model', header: '型式・規格', width: 200, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'maker_name', binding: 'maker_name', header: 'メーカー名', width: 110, isReadOnly: false },
                    { name: 'supplier_name', binding: 'supplier_name', header: '仕入先名', width: 110, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'order_status_graph', binding: 'order_status_graph', header: '見積受注数', width: 120, isReadOnly: true },
                    { name: 'order_status_value', binding: 'order_status_value', header: '引当発注数', width: 120, isReadOnly: true },
                ] },
                { cells: [{ name: 'stock_reserve_quantity', binding: 'stock_reserve_quantity', header: '在庫引当', width: 50, isReadOnly: true }] },
                { cells: [
                    { name: 'order_quantity', binding: 'order_quantity', header: '発注数', width: 60, isReadOnly: false, isRequired: false },
                    { name: 'unit', binding: 'unit', header: '単位', width: 60, isReadOnly: false }
                    
                ] },
                { cells: [
                    { name: 'stock_quantity', binding: 'stock_quantity', header: '管理数', width: 85, isReadOnly: true },
                    { name: 'stock_unit', binding: 'stock_unit', header: '管理数単位', width: 85, isReadOnly: false }
                    
                ] },
                { cells: [
                    { name: 'regular_price', binding: 'regular_price', header: '定価', width: 110, isReadOnly: false },
                    { name: 'cost_kbn', binding: 'cost_kbn', header: '仕入区分', width: 110, dataMap: priceKbnMap, isReadOnly: false },
                ] },
                { cells: [
                    { name: 'cost_unit_price', binding: 'cost_unit_price', header: '仕入単価', width: 75, isReadOnly: false, isRequired: false },
                    { name: 'sales_unit_price', binding: 'sales_unit_price', header: '販売単価', width: 75, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'cost_makeup_rate', binding: 'cost_makeup_rate', header: '仕入掛率', width: 70, isReadOnly: false, isRequired: false },
                    { name: 'sales_makeup_rate', binding: 'sales_makeup_rate', header: '販売掛率', width: 70, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'cost_total', binding: 'cost_total', header: '仕入総額', width: 75, isReadOnly: true, visible: false },
                    { name: 'sales_total', binding: 'sales_total', header: '販売総額', width: 75, isReadOnly: true, visible: false },
                ] },
                { cells: [
                    { name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 75, isReadOnly: true, isRequired: false },
                    { name: 'profit_total', binding: 'profit_total', header: '粗利総額', width: 75, isReadOnly: true },
                ] },
                { cells: [{ name: 'memo', binding: 'memo', header: '備考', width: 60, isReadOnly: false, isRequired: false }] },
                { cells: [{ name: 'product_auto_flg', binding: 'product_auto_flg', header: '1回限り登録', wordWrap: true, width: 70, isReadOnly: false}] },
                { cells: [{ name: 'product_definitive_flg', binding: 'product_definitive_flg', header: '本登録', wordWrap: true, width: 70, isReadOnly: false}] },
                { cells: [
                    { name: 'class_big_id', binding: 'class_big_id', header: '大分類', width: 110, dataMap: classBigMap, isReadOnly: false, isRequired: false },
                    { name: 'class_middle_id', binding: 'class_middle_id', header: '中分類', width: 110, dataMap: classMiddleMap, isReadOnly: false, isRequired: false },
                ] },

                { cells: [{ name: 'sales_use_flg', binding: 'sales_use_flg', header: '階層販売金額利用', wordWrap: true, width: 70, isReadOnly: true, visible: false }] },           // 機能削除対応 20200803

                { cells: [{ name: 'quote_detail_id', binding: 'quote_detail_id', header: '見積明細ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'construction_id', binding: 'construction_id', header: '工事区分ID', visible: false, isReadOnly: true}] },
                { cells: [{ name: 'layer_flg', binding: 'layer_flg', header: '階層フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'set_flg', binding: 'set_flg', header: '一式フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'parent_quote_detail_id', binding: 'parent_quote_detail_id', header: '親見積もり明細ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'seq_no', binding: 'seq_no', header: '連番', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'depth', binding: 'depth', header: '深さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'tree_path', binding: 'tree_path', header: '階層パス', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'filter_tree_path', binding: 'filter_tree_path', header: 'フィルター階層パス', visible: false, isReadOnly: true}] },
                
                { cells: [{ name: 'add_flg', binding: 'add_flg', header: '追加部材フラグ', visible: false, isReadOnly: true }] },

                { cells: [{ name: 'product_id', binding: 'product_id', header: '商品ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'min_quantity', binding: 'min_quantity', header: '最小単位数', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'order_lot_quantity', binding: 'order_lot_quantity', header: '発注ロット数', visible: false, isReadOnly: true }] },

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

                { cells: [{ name: 'maker_id', binding: 'maker_id', header: 'メーカーID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'supplier_id', binding: 'supplier_id', header: '仕入先ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'order_id_list', binding: 'order_id_list', header: '発注IDリスト', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'delivery_id_list', binding: 'delivery_id_list', header: '納品IDリスト', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'save_order_detail_id', binding: 'save_order_detail_id', header: '一時保存発注明細ID', visible: false, isReadOnly: true }] },

                { cells: [
                    { name: 'quote_quantity', binding: 'quote_quantity', header: '見積受注数', width: 100, visible: false, isReadOnly: true },
                    { name: 'sum_reserve_quantity', binding: 'sum_reserve_quantity', header: '引当発注済数', width: 100, visible: false, isReadOnly: true },    // 未使用
                ] },
                { cells: [{ name: 'sum_order_quantity', binding: 'sum_order_quantity', header: '発注数(未処理/差戻 除外)', visible: false, isReadOnly: true }] },
                { cells: [
                    { name: 'sales_kbn', binding: 'sales_kbn', header: '販売区分', width: 120, visible: false, isReadOnly: true },
                    { name: 'allocation_kbn', binding: 'allocation_kbn', header: '引当区分', width: 120, visible: false, isReadOnly: true },
                ] },
                { cells: [{ name: 'intangible_flg', binding: 'intangible_flg', header: '無形品フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'add_kbn', binding: 'add_kbn', header: '追加区分', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'child_parts_flg', binding: 'child_parts_flg', header: '子部品フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'product_maker_id', binding: 'product_maker_id', header: '商品マスタメーカーID', visible: false, isReadOnly: true }] },
            ];
        },

/*************** 便利関数 ***************/
        filterProductItemsSouceFunction(text, maxItems, callback){
            if (!text) {
                callback([]);
                return;
            }

            // サーバ通信中の場合 グリッド以外のオートコンプリートには存在しないプロパティのため初回はundefined
            if(this.filterProductAutoComplate.loadingFlg){
                return;
            }

            if(text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH){
                return;
            }

            this.setASyncAutoCompleteList(this.filterProductAutoComplate, PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_ALL_URL, text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
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
         * 【粗利率変更時】に呼び出す 未使用
         * cost_unit_price(仕入単価)を計算してセットする
         * @param {*} row   グリッドの行データ
         */
        // calcTreeGridChangeGrossProfitRateEx(row){
        //     // 100以上の値は0に変換する
        //     var grossProfitRate = 0;
        //     row.gross_profit_rate = this.roundDecimalRate(row.gross_profit_rate);
        //     if (row.gross_profit_rate > 99) {
        //         row.gross_profit_rate = 0;
        //     }else{
        //         grossProfitRate = this.bigNumberMinus(100, row.gross_profit_rate, true);
        //     }
        //     // 仕入単価 = 販売単価 * (1－(粗利率/100))
        //     row.cost_unit_price = this.roundDecimalStandardPrice(this.bigNumberDiv(this.bigNumberTimes(row.sales_unit_price, grossProfitRate, true), 100, true));
        // },

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
/*************** 便利関数 終了 ***************/


/*************** オートコンプリート ***************/
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },
        selectHeadMaker: function(sender) {
            var item = sender.selectedItem;
            this.main.maker_id = this.rmUndefinedZero(sender.selectedValue);
            this.headSupplierList = [];
            if(item !== null){
                this.main.maker_name = this.rmUndefinedBlank(item.supplier_name);
                if(this.supplierMakerList[item['id']] !== undefined){
                    this.headSupplierList = this.supplierMakerList[item['id']];
                }
            }else{
                this.main.maker_name = '';
            }
        },
        selectHeadSupplier: function(sender) {
            var item = sender.selectedItem;
            this.headPersonList = [];
            //this.deliveryAddressList = []
            if(item !== null){
                this.main.supplier_id = this.rmUndefinedZero(item.supplier_id);
                this.main.supplier_name = this.rmUndefinedBlank(item.supplier_name);
                this.getDeliveryAddressList(item['supplier_id'], this.base.address_id);
                if(this.personList[item['supplier_id']] !== undefined){
                    this.headPersonList = this.personList[item['supplier_id']];
                }
            }else{
                this.main.supplier_id = 0;
                this.main.supplier_name = '';
                this.getDeliveryAddressList(null, this.base.address_id);
            }
        },
        selectPerson: function(sender) {
            this.main.person_id = this.rmUndefinedZero(sender.selectedValue);
        },
        initDeliveryAddress: function(sender){
            this.wjDeliveryAddress = sender;
        },
        selectDeliveryAddress: function(sender){
            var item = sender.selectedItem;
            if(this.tmpDeliveryAddressUniqueKey === ''){
                if (item !== null) {
                    this.main.delivery_address = item.address;
                    this.main.delivery_address_kbn = item.delivery_address_kbn;
                    this.main.delivery_address_id = item.id;
                }else{
                    this.main.delivery_address = '';
                    this.main.delivery_address_kbn = 0;
                    this.main.delivery_address_id = 0;
                }
            }else{
                sender.selectedValue = this.tmpDeliveryAddressUniqueKey;
                this.tmpDeliveryAddressUniqueKey = '';
                if(sender.selectedValue === null){
                    this.main.delivery_address = '';
                    this.main.delivery_address_kbn = 0;
                    this.main.delivery_address_id = 0;
                }
            }
        },
        initFilterProduct: function(sender){
            this.filterProductAutoComplate = sender;
        },
        textChangedMatterDetailStartDate: function(v, e){
            this.filterInfo.start_date.text = v.text;
        },

        // フィルター
        selectFilterMaker: function(sender) {
            var item = sender.selectedItem;
            this.filterInfo.maker.id = this.rmUndefinedZero(sender.selectedValue);
            this.filterInfo.supplier.list = [];
            if(item !== null){
                if(this.supplierMakerList[item['id']] !== undefined){
                    this.filterInfo.supplier.list = this.supplierMakerList[item['id']];
                }
            }
        },
        selectFilterSupplier: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.filterInfo.supplier.id = this.rmUndefinedZero(item.supplier_id);
            }else{
                this.filterInfo.supplier.id = 0;
            }
        },
        selectFilterOrder: function(sender) {
            this.filterInfo.order.id = this.rmUndefinedZero(sender.selectedValue);
        },
        selectFilterProduct: function(sender) {
            this.filterInfo.product.id = this.rmUndefinedZero(sender.selectedValue);
        },

        // 発注番号採番
        selectCreateOrderNoMaker: function(sender) {
            var item = sender.selectedItem;
            this.createOrderNoInfo.maker.id  = this.rmUndefinedZero(sender.selectedValue);
            this.createOrderNoInfo.supplier.list = [];
            if(item !== null){
                this.createOrderNoInfo.maker.name = this.rmUndefinedBlank(item.supplier_name);
                if(this.supplierMakerList[item['id']] !== undefined){
                    this.createOrderNoInfo.supplier.list = this.supplierMakerList[item['id']];
                }
            }else{
                this.createOrderNoInfo.maker.name = '';
            }
        },
        selectCreateOrderNoSupplier: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.createOrderNoInfo.supplier.id = this.rmUndefinedZero(item.supplier_id);
                this.createOrderNoInfo.supplier.name = this.rmUndefinedBlank(item.supplier_name);
            }else{
                this.createOrderNoInfo.supplier.id = 0;
                this.createOrderNoInfo.supplier.name = "";
            }
        },
/*************** オートコンプリート 終了 ***************/

/*************** 日付 ***************/
        inputDesiredDeliveryDate: function(sender) {
            if(this.rmUndefinedBlank(sender.text) !== ''){
                this.main.desired_delivery_date = sender.text;
            }else{
                this.main.desired_delivery_date = null;
            }
        },
/*************** 日付 終了 ***************/

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
        fileDownLoad:async function(storageFileName){
            var path = this.base.quote_version_id + '/' + encodeURIComponent(storageFileName);
            var existsUrl = '/order-edit/exists/' + path;
            var result = await this.existsFile(existsUrl);
            if (result != undefined && result) {
                var downloadUrl = '/order-edit/download/' + path;
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
        /* ファイル関連 END */



        /* サーバー側の処理 */
        // 添付ファイルが存在するか
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

        // 登録
        save(){
            this.loading = true;
            this.initErr(this.errors);

            var errMsg = '';
            var gridDataList = this.wjMultiRowControle.collectionView.sourceCollection;
            for(var i=0;i<gridDataList.length;i++){
                var totalQuantity = this.bigNumberPlus(gridDataList[i].sum_reserve_quantity, gridDataList[i].order_quantity);
                // 一式の発注は1個のみ
                if(!this.treeGridSetProductSaveIsValid(gridDataList[i], totalQuantity)){
                    errMsg = MSG_ERROR_SET_PRODUCT_ORDER;
                    break;
                }
                if(this.rmUndefinedZero(gridDataList[i].maker_id) === 0){
                    if(this.isProductFormalRegistrationTarget(gridDataList[i])){
                        // 本登録対象はメーカーは必須入力
                        errMsg = MSG_ERROR_ORDER_MAKER_NO_SELECT;
                        break;
                    }
                }
                // 空選択肢ありのDataMapはnullが入ることがあるため置換
                gridDataList[i].class_big_id = this.rmUndefinedZero(gridDataList[i].class_big_id);
                gridDataList[i].class_middle_id = this.rmUndefinedZero(gridDataList[i].class_middle_id);
            }
            if (errMsg !== '') {
                this.loading = false;
                alert(errMsg);
                return;
            }
            
            var params = this.getOrderSaveData();
            params.append('grid_all_data', JSON.stringify(gridDataList));
            params.append('cost_total', 0);
            params.append('sales_total', 0);
            params.append('profit_total', 0);

            axios.post('/order-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                // this.loading = false
                if (response.data) {
                    if (response.data.status) {
                        window.onbeforeunload = null;
                        location.reload();
                    }else{
                        this.loading = false
                        if(response.data.message){
                            alert(response.data.message);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                } else {
                    this.loading = false
                    alert(MSG_ERROR);
                }
                
            }.bind(this))

            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false
            }.bind(this))
        },

        /* サーバー側の処理 */
        // 登録
        application(){
            this.loading = true;
            this.initErr(this.errors);

            var errMsg = MSG_ERROR_CHECKBOX_NO_SELECT;
            var gridDataList = this.wjMultiRowControle.collectionView.sourceCollection;
            var saveGridAllDataList = [];

            var costTotal = 0;
            var salesTotal = 0;
            var profitTotal = 0;
            for(var i=0;i<gridDataList.length;i++){
                saveGridAllDataList[i] = Vue.util.extend({}, gridDataList[i]);
                var totalQuantity = this.bigNumberPlus(saveGridAllDataList[i].sum_reserve_quantity, saveGridAllDataList[i].order_quantity);
                // 一式の発注は1個のみ
                if(!this.treeGridSetProductSaveIsValid(saveGridAllDataList[i], totalQuantity)){
                    errMsg = MSG_ERROR_SET_PRODUCT_ORDER;
                    break;
                }
                if(this.rmUndefinedZero(saveGridAllDataList[i].maker_id) === 0){
                    if(this.isProductFormalRegistrationTarget(gridDataList[i])){
                        // 1回限り登録・本登録対象はメーカーは必須入力
                        errMsg = MSG_ERROR_ORDER_MAKER_NO_SELECT;
                        break;
                    }
                }
                // 空選択肢ありのDataMapはnullが入ることがあるため置換
                saveGridAllDataList[i].class_big_id = this.rmUndefinedZero(saveGridAllDataList[i].class_big_id);
                saveGridAllDataList[i].class_middle_id = this.rmUndefinedZero(saveGridAllDataList[i].class_middle_id);

                if(gridDataList[i].chk && gridDataList[i].layer_flg === this.FLG_OFF){
                    errMsg = '';  // エラーなし
                    if(this.rmUndefinedZero(this.main.maker_id) !== 0){
                        if(gridDataList[i].maker_id == this.main.maker_id){
                            if(this.bigNumberGte(this.rmUndefinedZero(gridDataList[i].order_quantity), 0.01)){
                                if(this.rmUndefinedBlank(gridDataList[i].order_id_list) !== '' && saveGridAllDataList[i].supplier_id !== this.rmUndefinedBlank(this.main.supplier_id)){
                                    // 発注データと仕入先不一致
                                    errMsg = MSG_ERROR_UNMATCHED_PRODUCT_SUPPLIER_SELECT;
                                    break;
                                }else{
                                    // 発注対象の見積明細データの仕入先をヘッダーの情報に更新
                                    saveGridAllDataList[i].supplier_id = this.rmUndefinedBlank(this.main.supplier_id);
                                    saveGridAllDataList[i].supplier_name = this.rmUndefinedBlank(this.main.supplier_name);
                                    
                                    // 子部品の発注は見積数を超えた場合に反映する
                                    if(this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControle, gridDataList[i])){
                                        if(this.bigNumberLt(saveGridAllDataList[i].quote_quantity, totalQuantity)){
                                            saveGridAllDataList[i].quote_quantity = totalQuantity;
                                        }
                                    }

                                    costTotal += this.rmUndefinedZero(gridDataList[i].cost_total);
                                    //salesTotal += this.rmUndefinedZero(gridDataList[i].sales_total);
                                    //profitTotal += this.rmUndefinedZero(gridDataList[i].profit_total);
                                }
                            }else{
                                // 発注数未入力
                                errMsg = MSG_ERROR_NO_INPUT_ORDER_QUANTITY;
                                break;
                            }
                        }else{
                            // メーカー不一致
                            errMsg = MSG_ERROR_UNMATCHED_PRODUCT_MAKER_SELECT;
                            break;
                        }
                    }
                }
            }
            if (errMsg !== '') {
                this.loading = false;
                alert(errMsg);
                return;
            }
            
            var params = this.getOrderSaveData();
            
            params.append('grid_all_data', JSON.stringify(saveGridAllDataList));// 全グリッドのデータ
            params.append('cost_total', costTotal);
            params.append('sales_total', salesTotal);
            params.append('profit_total', profitTotal);
   
            axios.post('/order-edit/application', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                // this.loading = false
                if (response.data) {
                    if (response.data.status) {
                        window.onbeforeunload = null;
                        location.reload();
                    }else{
                        this.loading = false
                        if(response.data.message){
                            alert(response.data.message);
                        }else{
                            alert(MSG_ERROR);
                        }
                    }
                } else {
                    this.loading = false
                    alert(MSG_ERROR);
                }
                
            }.bind(this))

            .catch(function (error) {
                this.setErrorInfo(error);
                this.loading = false
            }.bind(this))
        },
        // 発注番号採番ダイアログを開く
        showDlgCreateOrderNoPrepare(){
            this.createOrderNoInfo.order_no = '';
            this.initErr(this.createOrderNoInfo.errors);
            this.showDlgCreateOrderNo = true;
        },
        // 発注番号採番
        createOrderNo(){
            this.loading = true
            this.initErr(this.createOrderNoInfo.errors);
            var params = new FormData();
            params.append('quote_id', this.rmUndefinedZero(this.base.quote_id));
            params.append('matter_id', this.rmUndefinedZero(this.base.matter_id));
            params.append('department_id', this.rmUndefinedZero(this.base.department_id));
            params.append('staff_id', this.rmUndefinedZero(this.base.staff_id));
            params.append('customer_id', this.rmUndefinedZero(this.base.customer_id));
            params.append('quote_no', this.rmUndefinedBlank(this.base.quote_no));
            params.append('matter_no', this.rmUndefinedBlank(this.base.matter_no));
            params.append('maker_id', this.rmUndefinedZero(this.createOrderNoInfo.maker.id));
            params.append('maker_name', this.rmUndefinedBlank(this.createOrderNoInfo.maker.name));
            params.append('supplier_id', this.rmUndefinedZero(this.createOrderNoInfo.supplier.id));
            params.append('supplier_name', this.rmUndefinedBlank(this.createOrderNoInfo.supplier.name));
            params.append('person_id', 0);
            params.append('delivery_address', '');
            params.append('delivery_address_kbn', 0);
            params.append('delivery_address_id', 0);
            params.append('account_customer_name', '');
            params.append('account_owner_name', '');
            params.append('map_print_flg', this.FLG_OFF);
            params.append('desired_delivery_date', 0);
            params.append('sales_support_comment', '');
            params.append('supplier_comment', '');
            params.append('cost_total', 0);
            params.append('sales_total', 0);
            params.append('profit_total', 0);

            

            axios.post('/order-edit/create-order-no', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                this.loading = false
                if (response.data) {
                    if (response.data.status) {
                        this.createOrderNoInfo.order_no = response.data.orderNo;
                        alert(response.data.message);
                    }else{
                        if(response.data.message){
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
                this.setErrorInfo(error, true);
                this.loading = false
            }.bind(this))
        },
        

        // 一時保存/申請で共通してPOSTするデータを返す
        getOrderSaveData(){
            var params = new FormData();
            params.append('quote_id', this.rmUndefinedZero(this.base.quote_id));
            params.append('matter_id', this.rmUndefinedZero(this.base.matter_id));
            params.append('department_id', this.rmUndefinedZero(this.base.department_id));
            params.append('staff_id', this.rmUndefinedZero(this.base.staff_id));
            params.append('customer_id', this.rmUndefinedZero(this.base.customer_id));
            params.append('quote_no', this.rmUndefinedBlank(this.base.quote_no));
            params.append('matter_no', this.rmUndefinedBlank(this.base.matter_no));
            params.append('maker_id', this.rmUndefinedZero(this.main.maker_id));
            params.append('maker_name', this.rmUndefinedBlank(this.main.maker_name));
            params.append('supplier_id', this.rmUndefinedZero(this.main.supplier_id));
            params.append('supplier_name', this.rmUndefinedBlank(this.main.supplier_name));
            params.append('person_id', this.rmUndefinedZero(this.main.person_id));
            params.append('delivery_address', this.rmUndefinedBlank(this.main.delivery_address));
            params.append('delivery_address_kbn', this.rmUndefinedZero(this.main.delivery_address_kbn));
            params.append('delivery_address_id', this.rmUndefinedZero(this.main.delivery_address_id));
            params.append('account_customer_name', this.rmUndefinedBlank(this.main.account_customer_name));
            params.append('account_owner_name', this.rmUndefinedBlank(this.main.account_owner_name));
            params.append('map_print_flg', this.rmUndefinedZero(this.main.map_print_flg));
            params.append('desired_delivery_date', this.rmUndefinedZero(this.main.desired_delivery_date));
            params.append('sales_support_comment', this.rmUndefinedBlank(this.main.sales_support_comment));
            params.append('supplier_comment', this.rmUndefinedBlank(this.main.supplier_comment));

            for(var i=0 ; i < this.uploadFileList.length ; i++){
                params.append('detail_file_list_' + i, this.uploadFileList[i]);
            }
            params.append('delete_file_list', JSON.stringify(this.deleteFileList));

            return params;
        },

        // 一時保存/申請時/発注番号採番のエラー処理
        setErrorInfo(error, isCreateOrderNo){
            if (error.response.data.errors) {
                // エラーメッセージ表示
                if(typeof isCreateOrderNo === 'undefined' || !isCreateOrderNo){
                    this.showErrMsg(error.response.data.errors, this.errors)
                    for(var i=0 ; i < this.uploadFileList.length ; i++){
                        var fileKey = 'detail_file_list_' + i;
                        if(this.errors['detail_file_list'] === '' && error.response.data.errors[fileKey] !== undefined && this.rmUndefinedBlank(error.response.data.errors[fileKey]) !== ''){
                            this.errors['detail_file_list'] = error.response.data.errors[fileKey];
                        }
                    }
                }else{
                    this.showErrMsg(error.response.data.errors, this.createOrderNoInfo.errors)
                }
            } else {
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }
        },

        // 案件情報取得
        getMatterInfo(matterId){
            this.loading = true;
            var params = new URLSearchParams();
            this.tmpDeliveryAddressUniqueKey = this.wjDeliveryAddress.selectedValue;
            params.append('matter_id', matterId);
            params.append('supplier_id', this.main.supplier_id);
            axios.post('/order-edit/get-matter-info', params)
            .then( function (response) {
                var data = response.data;
                var address = data['matter_address'];
                if(address !== null){
                    this.base.address_id = address.id;
                    this.base.matter_address1 = address.address1;
                    this.base.matter_address2 = address.address2;
                }else{
                    this.base.address_id = 0;
                    this.base.matter_address1 = '';
                    this.base.matter_address2 = '';
                }
                this.deliveryAddressList = data['delivery_address_list'];
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
                this.loading = false;
            }.bind(this))
        },
        // 入荷先リスト取得
        getDeliveryAddressList(supplierId, matterAddressId){
            var params = new URLSearchParams();
            this.tmpDeliveryAddressUniqueKey = this.wjDeliveryAddress.selectedValue;
            params.append('supplier_id', supplierId);
            params.append('matter_address_id', matterAddressId);
            axios.post('/order-edit/get-delivery-address-list', params)
            .then( function (response) {
                this.deliveryAddressList = response.data;
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
            }.bind(this))
        },
        // 案件住所入力
        inputMaterAddress(){
            // var urlparam = '?';
            // var fromURL = 'url=/order-edit/' + this.base.matter_id;
            var matterId = this.base.matter_id;
            var link = '/address-edit/' + matterId;
            window.open(link, '_blank')
        },

        // 引当画面へのリンク
        reserve(){
            var link = '/stock-allocation/?matter_no=' + this.base.matter_no;
            // ロック解除
            this.releaseLock(link);
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
            // ロック解除
            this.releaseLock(listUrl);
        },

        // ロック解除
        releaseLock(url){
            this.loading = true;
            if (!this.isReadOnly) {

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'order-edit');
                params.append('keys', this.rmUndefinedBlank(this.base.quote_id));
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    // this.loading = false
                    if (response.data) {
                        window.onbeforeunload = null;
                        location.href = url;
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
                location.href = url;
            }
        },

        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'order-edit');
            params.append('keys', this.rmUndefinedBlank(this.base.quote_id));
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
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'order-edit');
            params.append('keys', this.rmUndefinedBlank(this.base.quote_id));
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
    },
};

</script>

<style>
.input-area-body{
    width: 100%;
    background: #ffffff;
    margin-bottom: 15px;
}
.item-label{
    margin-bottom: 0px;
}
.area-border{
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.input-area-body-top{
    background: #ffffff;
    padding: 10px;
    margin-bottom: 15px;
}
.input-area-body-middle{
    padding: 10px;
    margin-top: 25px;
}
.filter-area{
    margin-top: 10px;
}
.filter-detail{
    padding: 10px; 
}
/*.input-area-body-grid{

}*/

.tree-grid-operation-area{
    padding: 0px 10px;
}
.tree-grid-operation-input{
    margin-bottom: 5px;
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

.order-status-list{
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
.file-up{
    margin-bottom: 0px;
    vertical-align: bottom;
}
.svg-icon {
    height: 25px !important;
    width: 25px !important;
}

.grid-graph-cell {
  height: 100%;
  width: 100%;
  display: flex;
  padding:0px;
  flex-direction: row; /* 要素を横に並べる */
}

.item {
  box-sizing: border-box;
  background-color: #FFFFFF;
}
.bg-warehouse0 {
  background-color: #FF9500;
}
.bg-warehouse1 {
  background-color:#5AC8FA;
}
.bg-warehouse2 {
  background-color: brown;
}
.bg-warehouse3 {
  background-color: hotpink;
}
.bg-warehouse4 {
  background-color: navy;
}
.input-group-circle-btn{
    font-size: 26px;
    padding: 4px !important;
    border: none;
}
.multi-grid-btn{
    border: none; 
    border-radius: 0px;
    width: 100%;
    height: 100%;
}
.single-grid-btn-sm{
    border: none; 
    border-radius: 0px;
    width: 100%;
    height: 100%;
    padding:0px;
}
.ctx-menu {
    padding: 2px;
    min-width: 230px;
}
.grid-fixlog-cell{
    display: block !important;
    width: 100%;
    height: 25px;
    line-height: 25px;
    text-align: center;
    color:#ffffff;
}
.wj-cells .wj-cell.wj-state-selected {
    background: #0085c7 !important;
}
.wj-cells .wj-cell.wj-state-multi-selected {
    background: #80adbf !important;
}
@media screen and (min-width: 992px){
    .file-area {
        float: right;
    }
}
</style>