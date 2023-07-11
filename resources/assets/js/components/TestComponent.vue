<template>
    <div>
        <loading-component :loading="loading" />

        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal">
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 text-right">
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                        <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                        <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                        <div class="pull-right">
                            <p class="btn btn-default btn-editing" v-show="(!isLocked)">編集中</p>
                        </div>
                    </div>
                </div>

                <div class="input-area-body area-border">
                    <div class="input-area-body-grid">
                        <div class="row tree-grid-operation-area">           
                            <div class="col-md-9 form-inline tree-grid-btn-area" style="margin-top:20px;">
                                <button type="button" class="btn btn-primary btn-new" @click="showDlgAddTreeLayerPrepare" v-bind:disabled="isReadOnly">工事種別追加</button>
                                <button type="button" class="btn btn-save " v-on:click="toLayer" v-bind:disabled="(isReadOnly)">階層作成</button>
                                <button type="button" class="btn btn-save " v-on:click="addRows" v-bind:disabled="(isReadOnly)">行挿入</button>
                                <button type="button" class="btn btn-danger" v-on:click="deleteRows" v-bind:disabled="(isReadOnly)">行削除</button>
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

        <!-- 新規見積初回ダイアログ -->
        <el-dialog title="新規作成" :visible.sync="showMatterDialog" :showClose=false :closeOnClickModal=false :closeOnPressEscape=false>
            <div v-if="showMatterDialog">
                <p>案件情報を入力してください。</p>
                <label class="control-label">得意先</label>
                <wj-auto-complete class="form-control"
                    search-member-path="customer_name, customer_short_name, customer_kana" 
                    display-member-path="customer_name" 
                    :initialized="initCustomerCombo"
                    :selectedIndexChanged="changeCustomerCombo"
                    :items-source="customerList" 
                    selected-value-path="id"
                    :isRequired=false
                    :max-items="customerList.length"
                >
                </wj-auto-complete>
                <p class="text-danger">{{ errors.customer_id }}</p>

                <label class="control-label">施主名</label>
                <wj-auto-complete class="form-control"
                    search-member-path="owner_name" 
                    display-member-path="owner_name" 
                    :initialized="initOwnerCombo"
                    :items-source="ownerList" 
                    selected-value-path="owner_name"
                    :selected-index=-1
                    :isRequired=false
                    :max-items="ownerList.length"
                >
                </wj-auto-complete>
                <p class="text-danger">{{ errors.owner_name }}</p>

                <label class="control-label">建築種別</label>
                <el-radio-group v-model="architectureType">
                    <el-radio v-for="arch in architectureList" :key="arch.value_code" :label="arch.value_code">{{ arch.value_text_1 }}</el-radio>
                </el-radio-group>
                <p class="text-danger">{{ errors.architecture_type }}</p>

                <p class="text-danger">{{ errors.matter_no }}</p>
                <div class="dlg-footer">
                    <el-button @click="confirmMatter" class="btn-create">案件作成</el-button>
                    <el-button @click="back" class="btn-cancel">キャンセル</el-button>
                </div>
            </div>
        </el-dialog>
        <!-- 工事種別追加ダイアログ -->
        <el-dialog title="工事種別追加" :visible.sync="showDlgAddTreeLayer">
            <p>見積作成項目を選択してください&nbsp;<el-checkbox v-model="isQuoteRequestAll" :indeterminate="isQuoteRequestIndeterminate"  @change="changeAllQreq()"></el-checkbox></p>
            <el-checkbox-group v-model="addTreeLayerList">
                <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" :disabled="existConstructionIds.indexOf(row.construction_id) >= 0" >{{ row.construction_name }}</el-checkbox>
            </el-checkbox-group>
            <span slot="footer" class="dialog-footer">
                <el-button @click="addTreeLayer" class="btn-create">追加</el-button>
                <el-button @click="showDlgAddTreeLayer = false" class="btn-cancel">キャンセル</el-button>
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
    product_code: '',
    btn_no_product_code: '',
    product_name: '',
    model: '',
    maker_name: '',
    supplier_name: '',
    quote_quantity: 0.00,
    order_quantity: 0.00,
    unit: '',
    stock_quantity: 0,
    stock_unit: '',
    regular_price: 0,
    sales_kbn: 2,
    cost_unit_price: 0,
    sales_unit_price: 0,
    cost_makeup_rate: 0.00,
    sales_makeup_rate: 0.00,
    cost_total: 0,
    sales_total: 0,
    gross_profit_rate: 0.00,
    profit_total: 0,
    memo: '',
    sales_use_flg: false,
    product_auto_flg: false,
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
    product_maker_id: 0,    // 商品マスタのメーカーID　階層化などでクリアする必要あり
    supplier_id: 0,
    cost_kbn: 2,
    intangible_flg: 1,
    add_kbn: 1,
    
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
        isReadOnly: false,

        isLocked: false,
        showMatterDialog: false,
        showDlgAddTreeLayer: false,
        architectureType: '',
        matterModel: {},
        addressModel: {},
        
        existConstructionIds: [],
        addTreeLayerList: [],

        costProductPriceList: {},
        salesProductPriceList: {},

        todayYYYYMMDD: 0,

        searchParams: {
            matter_no: null,
            matter_name: null,
            arrival_plan_date_from: null,
            arrival_plan_date_to: null,
            hope_arrival_plan_date_from: null,
            hope_arrival_plan_date_to: null,
        },

        main: {},
        lock: {},

        // 階層データ
        wjTreeViewControl: {},
        // グリッドデータ
        wjMultiRowControle: {},
        
        gridLayout: [],
        // 非表示カラム
        INVISIBLE_COLS: [
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
            'product_id',
            'min_quantity',
            'order_lot_quantity',

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

            'maker_id',
            'product_maker_id',
            'supplier_id',
            'cost_kbn',
            'intangible_flg',
            'add_kbn',
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
            //'sales_use_flg',
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
            //'cost_kbn',
            'add_kbn',
            'product_auto_flg',
        ],

        ADD_KBN: {
            DEFAULT: 0,
            ORDER: 1,
            ORDER_DETAIL: 2,
        },

        errors: {
            customer_id: '',
            owner_name: '',
            architecture_type: '',
            matter_no: '',
            maker_id: '',
            supplier_id: '',
            delivery_address: '',
            detail_file_list: '',
        },
        urlparam: '',

        uploadFileList : [],

        cgeProductCode : null,
        cgeProductName : null,
        cgeMaker : null,
        cgeSupplier : null,

        headSupplierList: [],
        headPersonList: [],
        wjDeliveryAddress : {},

        headSupplierCombo: null,

        isQuoteRequestAll: false,
        isQuoteRequestIndeterminate: false,

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
        INIT_ROW_ORDER_LOT_QUANTITY: 1.00,
        tmpDeliveryAddressUniqueKey: '',

        
        
    }),
    props: {
        customerList: Array,
        ownerList: Array,
        customerOwnerList: {},
        architectureList: Array,
        pMatterModel: {},
        pAddressModel: {},
        deliveryAddressKbnList: {},
        qreqList: Array,             // 工事区分マスタの内、見積依頼項目のデータ
        
        isOwnLock: Number,
        lockdata: {},
        orderModel: {},
        gridDataList: Array,
        quoteLayerList: Array,
        makerList: {},
        priceList: Array,
        supplierList: Array,
        supplierMakerList: {},
        supplierFileList: {},
        pCostProductPriceList: {},
        pSalesProductPriceList: {},
        personList: {},
        noProductCode: {},
    },
    watch: {
        // 見積項目（見積版作成ダイアログで使用）
        addTreeLayerList: function (newItem, oldItem) {
            this.isQuoteRequestAll = newItem.length === this.qreqList.length;
            this.isQuoteRequestIndeterminate = (!this.isQuoteRequestAll && newItem.length > 0) ? true:false; 
        },
    },
    created() {
        // propsで受け取った値をローカル変数に入れる
        this.main = this.orderModel
        this.lock = this.lockdata
        this.todayYYYYMMDD = this.getToday();





        this.showMatterDialog   = true;
        this.costProductPriceList   = this.pCostProductPriceList;
        this.salesProductPriceList  = this.pSalesProductPriceList;
        this.matterModel            = this.pMatterModel;
        this.addressModel            = this.pAddressModel;



        // ロック中判定
        if (this.rmUndefinedBlank(this.lock.id) != '' && this.isOwnLock != this.FLG_ON) {
            this.isLocked = true;
            this.isReadOnly = true;
        }

        // グリッドレイアウトセット
        this.gridLayout = this.getGridLayout()
    },
    mounted() {
        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        this.$nextTick(function() {
            var gridItemSource = new wjCore.CollectionView([], {
                newItemCreator: function () {
                    return Vue.util.extend({}, INIT_ROW);
                }
            });
            this.wjMultiRowControle = this.createGridCtrl('#orderDetailGrid', gridItemSource);

            var treeCtrl = this.createTreeCtrl('#quoteLayerTree', this.quoteLayerList);
            this.wjTreeViewControl = treeCtrl;
            
            this.selectTree(this.wjTreeViewControl, 'top_flg', this.FLG_ON);

        });

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
            var contextMenuCtrl = this.setTreeGridRightCtrl(wjGrid, wjcInput, gridCtrl, 'layoutOrder');
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
                        // 階層の場合、コンボボックスを表示させない
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
                        // if(col.name === 'product_code'){
                        //     this.cgeProductCode.communicatingFlg = false;
                        // }else{
                        //     this.cgeProductName.communicatingFlg = false;
                        // }
                        break;
                    case 'maker_name':
                        // 商品マスタにメーカーIDがある場合
                        if(this.rmUndefinedZero(row.product_maker_id) !== 0){
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
                        if(this.isKeyPressDeleteOrBackspace()){
                            // オートコンプリート上でdelete key無効化
                            e.cancel = true;
                        }
                        break;
                    case 'order_quantity':
                        if(row.layer_flg === this.FLG_ON || row.set_flg === this.FLG_ON){
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
                                    record.product_id = INIT_ROW.product_id;
                                    record.product_maker_id = INIT_ROW.product_maker_id;
                                    if(record.intangible_flg === this.FLG_ON){
                                        record.min_quantity = this.INIT_ROW_MIN_QUANTITY;
                                        record.order_lot_quantity = this.INIT_ROW_ORDER_LOT_QUANTITY;
                                    }
                                    record.intangible_flg = this.FLG_OFF;
                                    record.product_code = this.noProductCode.value_text_1;
                                    this.calcTreeGridRowData(record, 'order_quantity');
                                    this.calcGridCostSalesTotal();
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
            contextMenuCtrl.itemClicked.addHandler(function(s, e) {
                // メニュークリック
                var rowList = [];
                // クリックした行
                var clickRowDataItem = contextMenuCtrl.row;
                // 選択した行
                var selectedRows = gridCtrl.selectedRows;
                // クリックした行が何番目かを取得する
                var clickRowDataItemIndx = contextMenuCtrl.rowIndex;
                // クリップボードにコピーした文字列
                var clipboardText = this.rightClickInfo.clipboardText;
                // 今開いている階層の情報
                var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);
                // コピー行の貼り付け時のみ使用
                var isAddUnder = true;

                // 選択した項目によって処理を分岐させる
                switch(contextMenuCtrl.selectedValue){
                    case 'copy':
                        // コピー
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else{
                            this.wjMultiRowCopyClipboard(wjCore.Clipboard, gridCtrl, selectedRows);
                        }
                        break;
                    case 'paste':
                        // 貼付け
                        var layount = gridCtrl.layoutDefinition;
                        var clipboardData = this.toWjMultiRowPasteTextFormat(clipboardText);
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else{
                            if(this.wjMultiRowClipBoardValidation(gridCtrl, clipboardText)){ 
                                var pastedRowList = this.wjMultiRowPasteClipboard(clipboardData, gridCtrl, this.NON_PASTING_COLS);
                                for(let i in pastedRowList){
                                    // 階層とグリッドのチェックボックスを外す
                                    this.changeGridCheckBox(pastedRowList[i]);
                                    // 階層の場合、階層名変更
                                    this.changeProduct(null, pastedRowList[i], false);
                                    // 行の金額を計算
                                    this.calcTreeGridRowData(pastedRowList[i], 'order_quantity');
                                }
                                // 全体の計算
                                this.calcGridCostSalesTotal();
                            }
                        }
                        break;
                    case 'upAddRowPaste':
                        // コピーした行を上に貼り付け
                        isAddUnder = false;
                    case 'downAddRowPaste':
                        // コピーした行を下に貼り付け
                        if(this.addRowIsValid(gridCtrl, kbnIdList)){
                            var clipboardData = this.toWjMultiRowPasteTextFormat(clipboardText);
                            var isValid = true;
                            if(clipboardData.length%2 !== 0 || clipboardData.length === 0){
                                alert(MSG_ERROR_PASTE_FORMAT);
                                isValid = false;
                            }else{
                                // 列数が同じかどうかのチェック
                                for(var j=0; j<clipboardData.length && isValid; j++){
                                    var clipboardDataRecord = clipboardData[j].split(this.WJ_MULTI_ROW_CLIPBOARD_CTRL_OPTION.DELIMITER);
                                    if(gridCtrl.layoutDefinition.length !== clipboardDataRecord.length){
                                        alert(MSG_ERROR_PASTE_FORMAT);
                                        isValid = false;
                                        break;
                                    }
                                }
                            }
                            
                            if(isValid){
                                var addList = this.addTreeGridByRightClick(gridCtrl, this.wjTreeViewControl, clickRowDataItem, clickRowDataItemIndx, clipboardData, this.NON_PASTING_COLS, INIT_ROW, isAddUnder); 
                                for(let i in addList){
                                    // 階層とグリッドのチェックボックスを外す
                                    this.changeGridCheckBox(addList[i]);
                                    // 階層の場合、階層名変更
                                    this.changeProduct(null, addList[i], false);
                                    // 行の金額を計算
                                    this.calcTreeGridRowData(addList[i], 'order_quantity');
                                }
                                // 全体の計算
                                this.calcGridCostSalesTotal();
                            }
                        }
                        break;
                    case 'toLayer':
                        // 階層作成
                        rowList.push(clickRowDataItem);
                        if(this.toLayerIsValid(clickRowDataItem)){
                            this.treeGridDetailRecordToLayer(gridCtrl.itemsSource.items, this.wjTreeViewControl, rowList);
                            this.toLayerInitProp(clickRowDataItem);
                            // ツリー再読み込み
                            this.wjTreeViewControl.loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                            // 再計算
                            this.calcTreeGridRowData(clickRowDataItem, 'order_quantity');
                            // 全体の計算
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'toSet':
                        // 一式作成
                        rowList.push(clickRowDataItem);
                        if(this.toSetIsValid(clickRowDataItem)){
                            // 一式化する
                            this.treeGridDetailRecordToSet(this.wjMultiRowControle.itemsSource.items, this.wjTreeViewControl, rowList);
                            // ツリー再読み込み
                            this.wjTreeViewControl.loadTree();
                            // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                            this.checkedAllTreeGrid(false);
                            // 全体の計算
                            this.calcGridCostSalesTotal();
                        }
                        break;
                    case 'addRow':
                        // 行追加
                        if(this.addRowIsValid(gridCtrl, kbnIdList)){
                            // 全体から見て何番目かを取得
                            rowList[clickRowDataItemIndx] = clickRowDataItem;
                            var addList = this.addTreeGrid(gridCtrl, this.wjTreeViewControl, kbnIdList.depth, kbnIdList.filter_tree_path, INIT_ROW, false, rowList);
                            // 階層とグリッドのチェックボックスを外す(今開いている階層のみにしか追加しないので末尾の行を渡す)
                            this.changeGridCheckBox(addList[addList.length-1]);

                            // グリッド再描画
                            gridCtrl.collectionView.refresh();
                        }
                        break;
                    case 'deleteRow':
                        // 行削除
                        if(selectedRows.length === 0){
                            alert(MSG_ERROR_NO_SELECT);
                        }else if(selectedRows.length % 2 !== 0){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else if(gridCtrl.selectedRanges[0].columnSpan !== gridCtrl.columns.length){
                            alert(MSG_ERR_GRID_SELECTED_ROW);
                        }else{
                            var result = window.confirm(MSG_CONFIRM_DELETE);
                            if (result) {
                                for (const key in selectedRows) {
                                    const item = selectedRows[key];
                                    if(item.dataItem !== undefined){
                                        this.deleteTreeGridRow(gridCtrl.collectionView.sourceCollection, this.wjTreeViewControl, item.dataItem.filter_tree_path);
                                    }
                                }
                                this.wjTreeViewControl.loadTree();
                                // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                                this.checkedAllTreeGrid(false);
                                this.calcGridCostSalesTotal();
                            }
                        }
                        break;
                    default :
                        break;
                }
                
            }.bind(this));

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
                            // 存在しない列だがテンポラリーとして使用する
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
                                // 存在しない列だがテンポラリーとして使用する
                                row.is_cancel = this.changingProduct(this.cgeProductName, row, false);
                            }
                        }
                        break;
                    case 'order_quantity':
                        if(row.layer_flg === this.FLG_OFF){
                            var orderQuantity = s.activeEditor == null ? 0 : s.activeEditor.value;
                            if(!this.treeGridQuantityIsMultiple(orderQuantity, row.order_lot_quantity)){
                                alert(MSG_ERROR_NOT_ORDER_LOT_QUANTITY_MULTIPLE + ' 現在の発注ロット数：' + row.order_lot_quantity);
                                e.cancel = true;
                            }
                        }else{
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
                        //this.changeProduct(product, row, true);
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
                        var supplier = this.cgeSupplier.selectedItem;
                        if(supplier !== null){
                            row.supplier_id = supplier.supplier_id;
                        }else{
                            row.supplier_id = INIT_ROW.supplier_id;
                            row.supplier_name = INIT_ROW.supplier_name;
                        }
                        break;
                    case 'order_quantity':
                        // 発注数
                        row.quote_quantity = row.order_quantity
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'regular_price':
                        //  定価
                        this.calcTreeGridChangeRegularPrice(row);
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
                        this.calcTreeGridChangeGrossProfitRate(row);
                        //this.calcTreeGridChangeUnitPrice(row, true);
                        this.calcTreeGridChangeUnitPrice(row, false);
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                    case 'sales_kbn':
                        // 販売区分
                        if(
                            e.data !== row.sales_kbn &&
                            this.rmUndefinedBlank(row.sales_kbn) !== ''　&& this.rmUndefinedZero(row.product_id) !== 0)
                        {
                            this.changeCostSalesKbn(row);
                        }
                        break;
                    case 'sales_use_flg':
                        // 販売額利用フラグ
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                        break;
                }
                gridCtrl.collectionView.commitEdit();
                var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);
                this.filterGrid(kbnIdList.top_flg, kbnIdList.layer_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.add_flg, kbnIdList.to_layer_flg);
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
                            // チェックボックス生成
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
                                    gridCtrl.collectionView.items[i].product_auto_flg = checkBox.checked;
                                }
                                gridCtrl.endUpdate();
                            }.bind(this));
                            break;
                    }
                }else if (panel.cellType == wjGrid.CellType.Cell) {
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = gridCtrl.getBindingColumn(panel, r, c);
                    var colName = col.name;
                    var dataItem = panel.rows[r].dataItem;
                    var disabled = this.isReadOnly?'disabled':'';

                    if(dataItem !== undefined){
                        // 販売額利用 or 一式の場合の色変更
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
                        case 'order_quantity':
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
                        case 'sales_use_flg' :
                            if(dataItem !== undefined){
                                if(dataItem.set_flg === this.FLG_ON){
                                    cell.childNodes[0].disabled = true;
                                }
                            }
                            break;
                        case 'product_auto_flg':                     // 本登録
                            if(dataItem !== undefined){
                                if(dataItem.layer_flg !== this.FLG_ON && this.rmUndefinedZero(dataItem.product_id) === 0){
                                    cell.style.backgroundColor = this.TREE_GRID_COLOR_CODE.PRODUCT_AUTO_CELL;
                                }
                            }
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
                    this.wjMultiRowClipboardCtrl(wjCore.Clipboard, gridCtrl, this.NON_PASTING_COLS, this.wjMultiRowClipBoardValidation, function(pastedRowList){
                        for(let i in pastedRowList){
                            // 階層とグリッドのチェックボックスを外す
                            this.changeGridCheckBox(pastedRowList[i]);
                            // 階層の場合、階層名変更
                            //this.changeProduct(null, pastedRowList[i], false);
                            // 行の金額を計算
                            this.calcTreeGridRowData(pastedRowList[i], 'order_quantity');
                        }
                        // 全体の計算
                        this.calcGridCostSalesTotal();
                    }.bind(this));
                }

                // 選択行削除(DELETEキー押下時の処理)
                var selectedRowList = gridCtrl.selectedRows;
                if (event.keyCode == 46 && selectedRowList.length > 0 && selectedRowList.length%2 === 0 && gridCtrl.selectedRanges[0].columnSpan === gridCtrl.columns.length) {
                    // 削除するか確認
                    var result = window.confirm(MSG_CONFIRM_DELETE);
                    if (result) {
                        for (const key in selectedRowList) {
                            const item = selectedRowList[key];
                            if(item.dataItem !== undefined){
                                this.deleteTreeGridRow(gridCtrl.collectionView.sourceCollection, this.wjTreeViewControl, item.dataItem.filter_tree_path);
                            }
                        }
                        this.wjTreeViewControl.loadTree();
                        // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                        this.checkedAllTreeGrid(false);
                        this.calcGridCostSalesTotal();
                    }
                }
            }.bind(this), true);

            // 商品コードオートコンプリート
            this.cgeProductCode = new CustomGridEditor(gridCtrl, 'product_code', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "product_code",
                displayMemberPath: "product_code",
                //itemsSource: this.productCodeList,
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
                itemsSourceFunction: (text, maxItems, callback) => {
                    // 編集中の行
                    var row = gridCtrl.collectionView.currentItem;
                    // 階層の場合は何もしない
                    if(row === null || row.layer_flg === this.FLG_ON){
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

                    this.setASyncAutoCompleteList(this.cgeProductCode, '/test/get-product-code-list', text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST, callback, this.getProductCodeAutoCompleteFilterData);
                }
            }, 2, 1, 2);
            // 商品名オートコンプリート
            this.cgeProductName = new CustomGridEditor(gridCtrl, 'product_name', wjcInput.AutoComplete, {
                delay: 50,
                searchMemberPath: "product_name, product_short_name",
                displayMemberPath: "product_name",
                //itemsSource: null,
                selectedValuePath: "product_id",
                isRequired: false,
                minLength: 1,
                itemsSourceFunction: (text, maxItems, callback) => {
                    // 編集中の行
                    var row = gridCtrl.collectionView.currentItem;
                    // 階層の場合は何もしない
                    if(row === null || row.layer_flg === this.FLG_ON){
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

                    this.setASyncAutoCompleteList(this.cgeProductName, '/test/get-product-name-list', text, PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT, PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST, callback, this.getProductNameAutoCompleteFilterData);
                    // 検索
                    // wijmo.httpRequest('/test/get-product-name-list', {
                    //     data: {
                    //         text: text,
                    //     },
                    //     success: (xhr) => {
                    //         let response = JSON.parse(xhr.response);
                    //         if(response.length <= this.maxProductListCnt){
                    //             callback(response);
                    //             this.cgeProductName.temporarySourceCollection = response;
                    //             if(response.length >= 1){
                    //                 this.cgeProductName.communicatingFlg = true;
                    //             }
                    //         }else{
                    //             //callback([]);
                    //         }
                    //     }
                    // });
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
                allowDragging: !this.isReadOnly,
                
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
        // 工事種別を全チェック
        changeAllQreq(){
            this.addTreeLayerList = JSON.parse(JSON.stringify(this.existConstructionIds));
            if (this.isQuoteRequestAll) {
                for (const key in this.qreqList) {
                    const item = this.qreqList[key];
                    if (this.addTreeLayerList.indexOf(item.construction_id) === -1) {
                        this.addTreeLayerList.push(item.construction_id);
                    }
                }
            }
        },
        // 工事種別追加ダイアログを開く
        showDlgAddTreeLayerPrepare() {
            this.addTreeLayerList.length = 0;
            var control = this.wjTreeViewControl;
            var node = control.nodes[0]; //トップノード
            this.existConstructionIds.length = 0;
            for (var i = 0; i < node.dataItem.items.length; i++) {
                var tmp = node.nodes[i].dataItem;
                this.existConstructionIds.push(tmp.construction_id);
            }
            // ディープコピー
            this.addTreeLayerList = JSON.parse(JSON.stringify(this.existConstructionIds));

            this.showDlgAddTreeLayer = true;
        },
        // 工事種別追加
        addTreeLayer() {
            // TreeViewに追加
            var node = this.wjTreeViewControl.nodes[0]; //トップノード
            var newFilterTreePath = 0;
            var newSeqNo = 1;
            for (var i = 0; i < this.addTreeLayerList.length; i++) {
                var constructionId = this.addTreeLayerList[i];
                // 元々あった階層は飛ばす
                if (this.existConstructionIds.indexOf(constructionId) >= 0) continue;
                var qreqKbn = this.qreqList.filter(function(qreqData, key){
                    if(qreqData.construction_id === constructionId) return true;
                }.bind(this))[0];

                // 工事区分の階層作成
                if(node.dataItem.items.length >= 1){ 
                    newFilterTreePath = Math.max.apply(null,node.dataItem.items.map(function(o){return parseInt(o.filter_tree_path);})) + 1;
                    newSeqNo = Math.max.apply(null,node.dataItem.items.map(function(o){return parseInt(o.seq_no);})) + 1;
                }
                
                var newItem = {
                    id: 0,
                    construction_id:qreqKbn.construction_id,
                    layer_flg:      this.FLG_ON,
                    parent_quote_detail_id: 0,
                    seq_no:         newSeqNo,
                    depth:          this.QUOTE_CONSTRUCTION_DEPTH,
                    tree_path:      '',
                    sales_use_flg:  false,
                    product_name:   qreqKbn.construction_name,
                    add_flg:        this.FLG_OFF,
                    set_flg:        this.FLG_OFF,
                    top_flg:        this.FLG_OFF,
                    header:         qreqKbn.construction_name,
                    filter_tree_path:   newFilterTreePath.toString(),
                    to_layer_flg:   this.FLG_OFF,
                    tab_id:         qreqKbn.construction_id,
                    items:          [],
                };
                this.wjTreeViewControl.selectedNode = node.addChildNode(node.dataItem.items.length, newItem);

                var rowData = Vue.util.extend({}, INIT_ROW);
                rowData.construction_id = newItem.construction_id;
                rowData.depth           = newItem.depth,
                rowData.product_name    = newItem.product_name;
                rowData.layer_flg       = newItem.layer_flg;
                rowData.filter_tree_path= newItem.filter_tree_path;
                rowData.seq_no          = newItem.seq_no;
                rowData.min_quantity    = this.INIT_ROW_MIN_QUANTITY;
                rowData.order_lot_quantity  = rowData.min_quantity;
                rowData.order_quantity  = rowData.order_lot_quantity;
                rowData.quote_quantity  = rowData.order_quantity;
                // 再計算
                this.calcTreeGridRowData(rowData, 'order_quantity');
                
                this.wjMultiRowControle.collectionView.sourceCollection.push(rowData);
            }
            
            // ツリー再読み込み
            this.wjTreeViewControl.loadTree();
            this.checkedAllTreeGrid(false);
            
            this.wjMultiRowControle.collectionView.refresh();
            var kbnIdList = this.getTreeKbnId(this.wjTreeViewControl);
            this.filterGrid(kbnIdList.top_flg, kbnIdList.layer_flg, kbnIdList.depth, kbnIdList.filter_tree_path, kbnIdList.add_flg, kbnIdList.to_layer_flg);
            // ダイアログを閉じる
            this.showDlgAddTreeLayer = false;
            
        },
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
            }else if(this.treeGridDetailRecordConstructionIsAddLayer(this.wjMultiRowControle, row)){
                // 追加部材は階層化できない
                alert(MSG_ERROR_ADD_KBN_CREATE_LAYER);
                result = false;
            }else if(this.treeGridDetailRecordParentIsSetFlg(this.wjMultiRowControle, row)){
                // 一式配下(子部品)は階層化できない
                alert(MSG_ERROR_SET_PRODUCT_CREATE_LAYER);
                result = false;
            }
            return result;
        },
        // 階層化したあとに初期値をセット
        toLayerInitProp(row){
            //row.sales_use_flg    = true;
            row.intangible_flg   = this.FLG_ON;
            row.product_id       = INIT_ROW.product_id;
            row.product_maker_id = INIT_ROW.product_maker_id
            row.min_quantity     = this.INIT_ROW_MIN_QUANTITY;
            row.order_lot_quantity = row.min_quantity;
            row.order_quantity   = row.order_lot_quantity;
            row.quote_quantity   = row.order_quantity;
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

            var result = window.confirm(MSG_CONFIRM_DELETE);
            if (result) {
                // 行削除
                this.deleteTreeGridByGrid(this.wjMultiRowControle, this.wjTreeViewControl, kbnIdList.top_flg, kbnIdList.depth, kbnIdList.filter_tree_path);

                this.wjTreeViewControl.loadTree();
                // ツリー読み込み時にツリーのチェックボックスがクリアされるのでグリッドも連動させる
                this.checkedAllTreeGrid(false);
                this.calcGridCostSalesTotal();
                //this.wjMultiRowControle.collectionView.refresh();
            }
        },

        // グリッドに貼り付けしたときのチェック
        wjMultiRowClipBoardValidation(wjMultiRowControle, text){
            var result = true;
            var layount = wjMultiRowControle.layoutDefinition;
            var selectedRowList = wjMultiRowControle.selectedRows;
            var clipboardData = this.toWjMultiRowPasteTextFormat(text);

            if(clipboardData.length%2 !== 0 || clipboardData.length === 0){
                alert(MSG_ERROR_PASTE_FORMAT);
                result = false;
            }else{
                for(var i=0;i<selectedRowList.length && result;i++){
                    if(typeof selectedRowList[i].dataItem === 'undefined'){
                        //alert('追加済みの行を選択して下さい');
                        //result = false;
                        continue;
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
            this.wjMultiRowControle.collectionView.filter = record => {
                return this.isTreeGridVisibleTarget(record, topFlg, depth, filterTreePath);
            }
            this.setGridCtrl(topFlg);
            
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

        // グリッドの商品変更時の最小単位数チェック
        changingProduct(product, row, isCode){
            var isCancel = false;
            if(row.layer_flg === this.FLG_OFF && row.set_flg === this.FLG_OFF){
                var selectedItem = product.selectedItem;
                var activeProductCode = row.product_code;
                if(isCode){
                    activeProductCode = product.control.text;
                }

               
                var isErr = false;

                if(selectedItem !== null){
                    if(selectedItem.product_id === PRODUCT_AUTO_COMPLETE_SETTING.DEFAULT_PRODTCT_ID){
                        alert(MSG_ERROR_NO_SELECT_PRODUCT);
                        isErr = true;
                    }
                }

                if (!isErr && isCode) {
                    if (!this.isMatchPattern(PRODUCT_CODE_REGEX, product.control.text)) {
                        alert(MSG_ERROR_ILLEGAL_VALUE + "\n" + MSG_ERROR_PRODUCT_CODE_REGEX);
                        isErr = true;
                    }   
                }

                

                if(isErr){
                    // 変更前に選択していた商品を取得する
                    // var newLine = product.temporarySourceCollection.filter(function(item, index){
                    //     if (item['product_id'] === row.product_id) return true;
                    // });
                    // if(newLine.length === 1){
                    //     // 変更前の値を選択させる
                    //     product.control.selectedItem = newLine[0];
                    // }else{
                    //     // 変更前が未選択だった場合、未選択に戻す
                    //     product.control.selectedItem = null;
                    // }

                    // 入力していた値の復元
                    if(isCode){
                        product.control.text = row.product_code;
                    }else{
                        product.control.text = row.product_name;
                    }

                    isCancel = true;
                }else{
                    if(selectedItem === null){
                        row.product_id = INIT_ROW.product_id;
                        row.product_maker_id = INIT_ROW.product_maker_id;
                        if(this.rmUndefinedBlank(activeProductCode) === ''){
                            if(isCode){
                                // 無形品への変更
                                if(row.intangible_flg !== this.FLG_ON){
                                    alert(MSG_ALERT_TO_INTANGIBLE.replace('$min_quantity', INIT_ROW.min_quantity));
                                }
                                row.min_quantity = INIT_ROW.min_quantity;
                                row.order_lot_quantity = INIT_ROW.order_lot_quantity;
                                row.intangible_flg = this.FLG_ON;
                            }
                        }else{
                            if(row.intangible_flg === this.FLG_ON){
                                row.min_quantity = this.INIT_ROW_MIN_QUANTITY;
                                row.order_lot_quantity = this.INIT_ROW_ORDER_LOT_QUANTITY;
                            }
                            row.intangible_flg = this.FLG_OFF;
                        }

                        if(!this.treeGridQuantityIsMultiple(row.order_quantity, row.order_lot_quantity)){
                            // 発注数が発注ロットの倍数で無い場合に発注数をクリア
                            row.order_quantity = INIT_ROW.order_quantity;            // 発注数
                            row.quote_quantity = row.order_quantity;                 // 見積数
                        }
                        // 再計算
                        this.calcTreeGridRowData(row, 'order_quantity');
                        this.calcGridCostSalesTotal();
                    }
                }
                
            }
            return isCancel;
        },



        /*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_
        /* オートコンプリート
        /*_/_/_/_/_/_/_/_/_/_/_/_/_/_/_/_

        /**
         * 商品変更時に呼び出す
         * @param product
         * @param row
         * @param isCode
         */
        async changeProduct(product, row, isCode){
            if(product !== null){
                // 商品を変更したか
                if(row.product_id !== product.product_id && row.is_cancel !== true){
                    // 非同期で取得
                    var productInfo                 = await this.getProductInfo(product.product_id, this.matterModel.customer_id);
                    if(productInfo !== undefined){
                        var productData             = productInfo['product'];

                        if(isCode){
                            row.product_name        = productData.product_name;             // 商品名
                        }else{
                            row.product_code        = productData.product_code;             // 商品コード
                        }
                        row.product_id              = productData.product_id;               // 商品ID
                        row.model                   = productData.model;                    // 型式・規格
                        row.stock_unit              = productData.stock_unit;               // 管理数単位
                        row.min_quantity            = parseFloat(productData.min_quantity);         // 最小単位数
                        row.order_lot_quantity      = parseFloat(productData.order_lot_quantity);   // 発注ロット数
                        row.intangible_flg          = productData.intangible_flg;           // 無形品フラグ

                        row.quantity_per_case       = productData.quantity_per_case;        // 入り数
                        row.set_kbn                 = productData.set_kbn;                  // セット区分
                        row.class_big_id            = productData.class_big_id;             // 大分類ID
                        row.class_middle_id         = productData.class_middle_id;          // 中分類ID
                        row.class_small_id          = productData.class_small_id_1;         // 小分類ID
                        row.tree_species            = productData.tree_species;             // 樹種
                        row.grade                   = productData.grade;                    // 等級
                        row.length                  = productData.length;                   // 長さ
                        row.thickness               = productData.thickness;                // 厚み
                        row.width                   = productData.width;                    // 幅

                        if(!this.treeGridQuantityIsMultiple(row.order_quantity, row.order_lot_quantity)){
                            row.order_quantity      = INIT_ROW.order_quantity;              // 発注数
                            row.quote_quantity      = row.order_quantity;                   // 見積数
                        }

                        row.regular_price           = productData.price;                    // 定価
                        row.unit                    = productData.unit;                     // 仕入単位
                        row.cost_kbn                = row.sales_kbn;                        // 販売区分と仕入区分を同じものにする
                        this.setTreeGridUnitPriceNew(row, true, productInfo['costProductPriceList'], false);       // 仕入単価
                        this.setTreeGridUnitPriceNew(row, false, productInfo['salesProductPriceList'], false);     // 販売単価

                        this.calcTreeGridChangeUnitPrice(row, false);                       // 仕入掛率再計算
                        this.calcTreeGridChangeUnitPrice(row, true);                        // 販売掛率 
                        
                        row.product_maker_id        = productData.maker_id;                 // 商品マスタのメーカーID
                            
                        var isFind = false;
                        // 選んだ商品のメーカーが無い場合は変更しない
                        if(this.rmUndefinedZero(row.product_maker_id) !== 0){
                            for(var i = 0; i < this.supplierList.length; i++){
                                if (this.supplierList[i].id == row.product_maker_id) {
                                    row.maker_id    = row.product_maker_id;                    // メーカーID
                                    row.maker_name  = this.supplierList[i].supplier_name;    // メーカー
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
                                row.supplier_id = INIT_ROW.supplier_id;                      // 仕入先ID
                                row.supplier_name = INIT_ROW.supplier_name;                  // 仕入先名
                            }
                        }else{
                            row.supplier_id = INIT_ROW.supplier_id;                      // 仕入先ID
                            row.supplier_name = INIT_ROW.supplier_name;                  // 仕入先名
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
                        
                    //}
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
            row.cost_kbn = row.sales_kbn; // 販売区分変更時は仕入区分も同じものに変更する
            // 非同期で取得
            var unitPriceInfo           = await this.getUnitPrice(row.product_id, this.matterModel.customer_id);
            if(unitPriceInfo !== undefined){
                this.setTreeGridUnitPriceNew(row, true, unitPriceInfo['costProductPriceList'], false);       // 仕入単価
                this.calcTreeGridChangeUnitPrice(row, true);
                this.setTreeGridUnitPriceNew(row, false, unitPriceInfo['salesProductPriceList'], false);     // 販売単価
                this.calcTreeGridChangeUnitPrice(row, false);
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
            }else{
                this.wjMultiRowControle.allowAddNew = !this.isReadOnly
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

        // グリッドレイアウト定義取得
        getGridLayout () {
            // 価格区分
            var priceKbnMap = new wjGrid.DataMap(this.priceList, 'value_code', 'value_text_1');

            return [
                { cells: [
                    { name: 'btn_up', binding: 'btn_up', header: ' ', width: 30, isReadOnly: true },
                    { name: 'btn_down', binding: 'btn_down', header: ' ', width: 30, isReadOnly: true },
                ] },
                { cells: [{ name: 'chk', binding: 'chk', header: '選択', width: 30, isReadOnly: false }] },
                { cells: [{ name: 'product_code', binding: 'product_code', header: '品番', width: 150, isReadOnly: false, isRequired: false }] },
                { cells: [{ name: 'btn_no_product_code', binding: 'btn_no_product_code', header: '　', width: 30, isReadOnly: true }] },
                { cells: [
                    { name: 'product_name', binding: 'product_name', header: '商品名', width: 210, isReadOnly: false, isRequired: false },
                    { name: 'model', binding: 'model', header: '型式・規格', width: 210, isReadOnly: false, isRequired: false },
                ] },
                { cells: [
                    { name: 'maker_name', binding: 'maker_name', header: 'メーカー名', width: 130, isReadOnly: false },
                    { name: 'supplier_name', binding: 'supplier_name', header: '仕入先名', width: 130, isReadOnly: false },
                ] },
                { cells: [{ name: 'quote_quantity', binding: 'quote_quantity', header: '見積数', width: 60, isReadOnly: true }] },
                { cells: [
                    { name: 'order_quantity', binding: 'order_quantity', header: '発注数', width: 60, isReadOnly: false, isRequired: false },
                    { name: 'unit', binding: 'unit', header: '単位', width: 60, isReadOnly: true }
                    
                ] },
                { cells: [
                    { name: 'stock_quantity', binding: 'stock_quantity', header: '管理数', width: 85, isReadOnly: true },
                    { name: 'stock_unit', binding: 'stock_unit', header: '管理数単位', width: 85, isReadOnly: true }
                    
                ] },
                { cells: [
                    { name: 'regular_price', binding: 'regular_price', header: '定価', width: 110, isReadOnly: false, isRequired: false},
                    { name: 'sales_kbn', binding: 'sales_kbn', header: '販売区分', width: 110, dataMap: priceKbnMap, isReadOnly: false },
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
                    { name: 'cost_total', binding: 'cost_total', header: '仕入総額', width: 75, isReadOnly: true },
                    { name: 'sales_total', binding: 'sales_total', header: '販売総額', width: 75, isReadOnly: true },
                ] },
                { cells: [
                    { name: 'gross_profit_rate', binding: 'gross_profit_rate', header: '粗利率', width: 75, isReadOnly: false, isRequired: false },
                    { name: 'profit_total', binding: 'profit_total', header: '粗利総額', width: 75, isReadOnly: true },
                ] },
                { cells: [{ name: 'memo', binding: 'memo', header: '備考', width: 125, isReadOnly: false, isRequired: false }] },
                { cells: [{ name: 'sales_use_flg', binding: 'sales_use_flg', header: '階層販売金額利用', wordWrap: true, width: 70, isReadOnly: false }] },
                { cells: [{ name: 'product_auto_flg', binding: 'product_auto_flg', header: '本登録', wordWrap: true, width: 70, isReadOnly: false}] },

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
                { cells: [{ name: 'class_big_id', binding: 'class_big_id', header: '大分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_middle_id', binding: 'class_middle_id', header: '中分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'class_small_id', binding: 'class_small_id', header: '小分類ID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'tree_species', binding: 'tree_species', header: '樹種', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'grade', binding: 'grade', header: '等級', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'length', binding: 'length', header: '長さ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'thickness', binding: 'thickness', header: '厚み', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'width', binding: 'width', header: '幅', visible: false, isReadOnly: true }] },
                
                { cells: [{ name: 'maker_id', binding: 'maker_id', header: 'メーカーID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'product_maker_id', binding: 'product_maker_id', header: '商品メーカーID', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'supplier_id', binding: 'supplier_id', header: '仕入先ID', visible: false, isReadOnly: true }] },
                { cells: [
                    { name: 'cost_kbn', binding: 'cost_kbn', header: '仕入区分', width: 120, visible: false, isReadOnly: true },
                ] },
                { cells: [{ name: 'intangible_flg', binding: 'intangible_flg', header: '無形品フラグ', visible: false, isReadOnly: true }] },
                { cells: [{ name: 'add_kbn', binding: 'add_kbn', header: '追加区分', visible: false, isReadOnly: true }] },
            ];
        },
/*************** 便利関数 終了 ***************/

/*************** モーダル 終了 ***************/
        // 得意先オートコンプリート初期化
        initCustomerCombo: function(sender){
            this.matterCustomerCombo = sender;
            this.$nextTick(function() {
                // changeイベント発火
                this.matterCustomerCombo.onSelectedIndexChanged();
            });
        },
        // 施主名オートコンプリート初期化
        initOwnerCombo: function(sender){
            this.ownerNameCombo = sender;
        },
        // 案件オートコンプリート
        initSameMatterCombo: function(sender) {
          this.sameMatterCombo = sender;
        },
        // 得意先オートコンプリート変更イベント
        changeCustomerCombo: function(sender){
            // 施主名を保存
            var tmpText = this.ownerNameCombo.text;

            // 得意先を施主名を絞込む
            var tmpOwnerList = this.ownerList;
            if (sender.selectedValue) {
                tmpOwnerList = this.customerOwnerList[sender.selectedValue];
            }
            this.ownerNameCombo.itemsSource = tmpOwnerList;
            this.ownerNameCombo.selectedIndex = -1;

            // 得意先変更前に施主名が既に入力済みだった場合は内容を復帰
            if (!tmpText == '') {
                this.ownerNameCombo.text = tmpText;
            }
        },
        // 案件確認
        confirmMatter() {
            // エラーの初期化
            this.initErr(this.errors);

            this.loading = true
            // this.$set(this.main, 'customer_id', this.matterCustomerCombo.selectedValue);
            // this.$set(this.main, 'owner_name', this.ownerNameCombo.text);
            // this.$set(this.main, 'architecture_type', this.architectureType);

            // 入力値の取得
            var params = new URLSearchParams();
            params.append('customer_id', this.rmUndefinedBlank(this.matterCustomerCombo.selectedValue));
            params.append('owner_name', this.rmUndefinedBlank(this.ownerNameCombo.text));
            params.append('architecture_type', this.rmUndefinedBlank(this.architectureType));
            
            axios.post('/order-sudden/confirm-matter', params)
            .then( function (response) {
                this.loading = false;
                if (response.data) {
                    // 成功
                    if (response.data.invalid) {
                        // 同じ案件があればエラー
                        this.errors.matter_no = MSF_ERROR_EXIST_QUOTE;
                    }else{
                        this.showMatterDialog = false;
                        this.$set(this.matterModel, 'customer_id', this.matterCustomerCombo.selectedValue);
                        this.$set(this.matterModel, 'customer_name', this.matterCustomerCombo.text);
                        this.$set(this.matterModel, 'owner_name', this.ownerNameCombo.text);
                        this.$set(this.matterModel, 'architecture_type', this.architectureType);
                        this.$set(this.matterModel, 'department_id', response.data.department_id);
                        this.$set(this.matterModel, 'staff_id', response.data.staff_id);
                        this.showDlgAddTreeLayerPrepare();
                    }
                } else {
                    // 失敗
                    alert(MSG_ERROR);
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message);
                    } else {
                        alert(MSG_ERROR);
                    }
                }
                this.loading = false;
            }.bind(this))
        },
/*************** モーダル 終了 ***************/

/*************** オートコンプリート ***************/
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
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
        
        // 戻る
        back() {
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/order-list';
            }
            var listUrl = rtnUrl + query;

            if (!this.isReadOnly) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'order-sudden');
                params.append('keys', this.rmUndefinedBlank(this.matterModel.id));
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
            params.append('screen', 'order-sudden');
            params.append('keys', this.rmUndefinedBlank(this.matterModel.id));
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
            params.append('screen', 'order-sudden');
            params.append('keys', this.rmUndefinedBlank(this.matterModel.id));
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
.tree-grid-btn-area{
    padding-bottom: 5px;
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
    width: 99.8%;
    height: 100%;
    padding: 0;
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