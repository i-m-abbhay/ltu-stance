<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="col-md-12 col-sm-12 search-body">
                    <div class="row">
                        <div class="col-md-3">
                            <label class="control-label">商品番号</label>
                            <input type="text" class="form-control" v-model="searchParams.product_code">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">商品名</label>
                            <input type="text" class="form-control" v-model="searchParams.product_name">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">型式／規格</label>
                            <input type="text" class="form-control" v-model="searchParams.model">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12" style="padding-top: 20px;">        
                            <div class="pull-right">                    
                                <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                                <button type="submit" class="btn btn-search btn-md">&emsp;検索&emsp;</button>
                            </div>
                        </div>
                    </div>
                </div>
             </form>
        </div>

        <!-- 検索結果グリッド -->
        <div class="container-fluid result-body">
            <div class="row">
                <div class="col-md-12">
                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData }}件</p>
                </div>
                <div id="wjWoodGrid"></div>
            </div>

            <div class="row">
                <div class="pull-right">
                    <button type="button" class="btn btn-save" @click="save">&emsp;単価登録&emsp;</button>
                </div>
            </div>
        </div>
        <!-- <div class="row">
            <div class="text-center">
                <button type="button" class="btn btn-back" @click="back">&emsp;戻る&emsp;</button>
            </div>
        </div> -->
    </div>
</template>


<script>
import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';
import { BigNumber } from 'bignumber.js';

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        FLG_ON: 1,
        FLG_OFF: 0,
        tableData: 0,

        searchParams: {
            product_code: '',
            product_name: '',
            model: '',
        },
        wjSearchObj: {

        },

        layoutDefinition: null,
        keepDOM: {},
        urlparam: '',

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 0,

        cgePriceDate: null,
        wjWoodGrid: null,
    }),
    props: {

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

        this.$nextTick(function() {
            this.wjWoodGrid = this.createGridCtrl('#wjWoodGrid', new wjCore.CollectionView([]));
        });
    },
    methods:{
        save() {
            this.loading = true;
            var params = new URLSearchParams();

            var okFlg = true;
            var today = moment().format('YYYY/MM/DD');
            var items = [];
            var errMsg = '';
            this.wjWoodGrid.itemsSource.items.forEach(row => {
                if (row.chk) {
                    if (row.price_date <= today) {
                        // 改定日が過去日であればエラー
                        okFlg = false;
                        errMsg = MSG_ERROR_PAST_PRICE_DATE;
                    }
                    if (this.rmUndefinedBlank(row.price_date) == '') {
                        // 改定日が未入力
                        okFlg = false;
                        errMsg = '改定日を' + MSG_ERROR_NO_INPUT;
                    }
                    if (this.rmUndefinedZero(row.unitprice) == 0) {
                        // 立米原価が未入力
                        okFlg = false;
                        errMsg = '立米原価を' + MSG_ERROR_NO_INPUT;
                    }

                    items.push(row);
                }
            });

            if (items.length == 0) {
                okFlg = false;
                errMsg = MSG_ERROR_NO_SELECT;
            }

            if (!okFlg) {
                alert(errMsg);
                this.loading = false;
                return;
            }

            params.append('gridData', JSON.stringify(items));
            axios.post('/wood-unit-price-edit/save', params)
            .then( function (response) {
                if (response.data) {
                    var listUrl = '/wood-unit-price-edit' + this.urlparam;
                    location.href = listUrl;
                } else {
                    alert(MSG_ERROR);
                    location.reload();
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
            }.bind(this))
        },
        search() {
            this.loading = true;
            var params = new URLSearchParams();

            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('model', this.rmUndefinedBlank(this.searchParams.model));
            
            axios.post('/wood-unit-price-edit/search', params)
            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'model=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.model));

                    var itemsSource = [];
                    var dataLength = 0;

                    response.data.forEach(element => {
                        this.keepDOM[element.id] = {
                            analysis_btn: document.createElement('a'),
                        }
                        // 分析ボタン
                        // TODO
                        this.keepDOM[element.id].analysis_btn.innerHTML = "分析";
                        this.keepDOM[element.id].analysis_btn.href = '#';
                        this.keepDOM[element.id].analysis_btn.classList.add('btn', 'btn-md', 'btn-edit', 'edit-link');

                        itemsSource.push({
                            chk: false,
                            id: element.id,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            tree_species: element.tree_species,
                            grade: element.grade,
                            size: element.size,
                            unit: element.unit,
                            last_purchase_date: element.last_purchase_date,
                            last_purchasing_cost: element.last_purchasing_cost,
                            price_date: element.price_date,
                            unitprice: element.unitprice,
                            purchasing_cost: element.purchasing_cost,
                            purchase_unit_price: element.purchase_unit_price,
                            sales_unit_price: element.sales_unit_price,
                            calc_size: element.calc_size,
                            length: element.length,
                            thickness: element.thickness,
                            width: element.width,
                        });
                        dataLength++
                    })
                    this.wjWoodGrid.dispose();
                    this.wjWoodGrid = this.createGridCtrl('#wjWoodGrid', new wjCore.CollectionView(itemsSource));

                    // this.wjWoodGrid.itemsSource = new wjCore.CollectionView(itemsSource);
                    this.tableData = dataLength;

                    // 描画更新
                    this.wjWoodGrid.refresh();
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
            }.bind(this))
        },
        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.layoutDefinition,
                showSort: true,
                allowSorting: true,
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
                    var colName = this.wjWoodGrid.getBindingColumn(panel, r, c).name;

                    var dataItem = panel.rows[r].dataItem;
                    if(dataItem !== undefined) {

                    }

                    switch (colName) {
                        case 'chk':
                            cell.style.textAlign = 'center';
                            break;
                        case 'unit':
                            cell.style.textAlign = 'center';
                            break;
                        case 'product_code':
                            cell.style.textAlign = 'left';
                            break;
                        case 'product_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'last_purchasing_cost':
                            cell.style.textAlign = 'right';
                            break;
                        case 'last_purchase_date':
                            cell.style.textAlign = 'right';
                            break;
                        case 'unitprice':
                            cell.style.textAlign = 'right';
                            break;
                        case 'purchasing_cost':
                            cell.style.textAlign = 'right';
                            break;
                        case 'purchase_unit_price':
                            cell.style.textAlign = 'right';
                            break;
                        case 'sales_unit_price':
                            cell.style.textAlign = 'right';
                            break;
                        case 'btn':
                            cell.textAlign = 'center';
                            if(dataItem !== undefined){
                                var rId = 'btn-' + dataItem.id;

                                var div = document.createElement('div');
                                div.classList.add('btn-group');
                                div.setAttribute("data-toggle","buttons");

                                // 分析ボタン
                                var btnAnalysis = document.createElement('button');
                                btnAnalysis.type = 'button';
                                btnAnalysis.id = rId;
                                btnAnalysis.classList.add('btn', 'btn-edit');
                                btnAnalysis.innerHTML = '分析';
                                // btnDetail.disabled = isDisable;

                                btnAnalysis.addEventListener('click', function (e) {
                                    this.btnClickAnalysis(dataItem);
                                }.bind(this));

                                div.appendChild(btnAnalysis);

                                cell.appendChild(div);
                            }
                            break;
                        default: 
                            cell.textAlign = 'left';
                            break;
                    };
                }
            }.bind(this);
            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var col = this.wjWoodGrid.getBindingColumn(e.panel, e.row, e.col);       // 取れるのは上段の列名
                // 編集行データ取得
                var row = s.collectionView.currentItem;

                switch (col.name) {
                    case 'price_date':
                        if (row.price_date != null) {
                            row.price_date = moment(row.price_date).format('YYYY/MM/DD');
                        }
                        break;
                    case 'unitprice':
                        if (row !== undefined) {
                            row.unitprice = this.roundDecimalStandardPrice(row.unitprice);

                            this.calcPrice(row);
                        }
                        break;
                }
                gridCtrl.endUpdate();
            }.bind(this));

            // セル編集直前のイベント：コンボをセットする
            gridCtrl.beginningEdit.addHandler(function (s, e) {
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                var row = s.collectionView.currentItem;

                switch(col.name){

                }
            }.bind(this));

            gridCtrl.cellEditEnding.addHandler(function (s, e) {
                gridCtrl.beginUpdate();
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);
                // var row = s.collectionView.currentItem;
                // 入力された値
                // var newVal = null;
                // if (gridCtrl.activeEditor !== undefined) {
                //     newVal = gridCtrl.activeEditor.value;
                // }

                switch(col.name){
                    case 'unitprice':
                        // if (isNaN(newVal)) {
                        //     alert(MSG_ERROR_NOT_NUMBER)
                        //     e.cancel = true;
                        // }
                        break;
                }
                gridCtrl.endUpdate();
            }.bind(this));

            // クリックイベント
            gridCtrl.addEventListener(gridCtrl.hostElement, "click", e => {
                let ht = gridCtrl.hitTest(e);
                if(ht.cellType === wjGrid.CellType.Cell){
                    var record = ht.panel.rows[ht.row].dataItem;
                    if(typeof record !== 'undefined'){
                        var col = gridCtrl.getBindingColumn(ht.panel, ht.row, ht.col);
                        switch (col.name) {

                        }
                    }
                }
            });

            this.cgePriceDate = new CustomGridEditor(gridCtrl, 'price_date', wjcInput.InputDate, {
                format: "d",
                isRequired: false,
            }, 2, 1, 2);

            return gridCtrl;
        },
        calcPrice(row) {
            if (row !== undefined) {
                var calcSize = row.calc_size;

                // 立米原価をもとに、仕入原価、仕入単価、販売単価を計算する
                row.purchasing_cost = this.bigNumberTimes(calcSize, row.unitprice);
                row.purchase_unit_price = this.roundDecimalSalesPrice(this.bigNumberDiv(row.purchasing_cost, WOOD_UNIT_PRICE_RATE.PURCHASE_RATE));
                row.sales_unit_price = this.roundDecimalSalesPrice(this.bigNumberDiv(row.purchase_unit_price, WOOD_UNIT_PRICE_RATE.SALES_RATE) / 10) * 10;
            }
        },
        roundDecimalWoodPrice(price){
            var tmp = price;
            if(!BigNumber.isBigNumber(tmp)){
                tmp = BigNumber(price);
            }
            return this.rmInvalidNumZero(BigNumber(tmp).decimalPlaces(4).toNumber());
        },
        // TODO
        btnClickAnalysis() {
            
        },
        // レイアウト取得
        getLayout() {
            return [
                {
                    cells: [
                        { binding: 'chk', name: 'chk', header: 'chk', minWidth: GRID_COL_MIN_WIDTH, width: 60, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'product_code', name: 'product_code', header: '商品番号', minWidth: 200, width: '*', isReadOnly: true },
                        { binding: 'product_name', name: 'product_name', header: '商品名', minWidth: 200, width: '*', isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'tree_species', name: 'tree_species', header: '樹種', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'grade', name: 'grade', header: '等級', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'size', name: 'size', header: '長さ×厚み×幅', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'unit', name: 'unit', header: '単位', minWidth: GRID_COL_MIN_WIDTH, width: 70, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'last_purchase_date', name: 'last_purchase_date', header: '最終改定日', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true },
                        { binding: 'last_purchasing_cost', name: 'last_purchasing_cost', header: '最終仕入原価', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true, format: 'n0' },
                    ]
                },
                {
                    cells: [
                        { binding: 'price_date', name: 'price_date', header: '改定日', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: false },
                    ]
                },
                {
                    cells: [
                        { binding: 'unitprice', name: 'unitprice', header: '立米原価', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: false, format: 'n0' },
                    ]
                },
                {
                    cells: [
                        { binding: 'purchasing_cost', name: 'purchasing_cost', header: '仕入原価', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true, format: 'f4' },
                    ]
                },
                {
                    cells: [
                        { binding: 'purchase_unit_price', name: 'purchase_unit_price', header: '仕入単価', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true, format: 'n0'  },
                    ]
                },
                {
                    cells: [
                        { binding: 'sales_unit_price', name: 'sales_unit_price', header: '販売単価', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true, format: 'n0'  },
                    ]
                },
                {
                    cells: [
                        { name: 'btn', header: '操作', width: 70, isReadOnly: true, isRequired: false},
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
            var searchParams = this.searchParams;
            Object.keys(searchParams).forEach(function (key) {
                searchParams[key] = '';
            });
        },
        back() {
            var listUrl = '/';
            location.href = listUrl;
        },
        // 以下オートコンプリートの値取得
        initMiddle: function(sender) {
            this.wjSearchObj.class_middle_name = sender
        },
        initBig: function(sender){
            this.wjSearchObj.class_big_name = sender;
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
.container-fluid .wj-multirow {
    height: 650px;
    margin: 6px 0;
}

</style>