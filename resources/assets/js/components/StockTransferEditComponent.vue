<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form main-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="save">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <h4>移管登録</h4>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-3 col-sm-3 col-xs-12" v-bind:class="{'has-error': (errors.from_warehouse_id != '') }">
                        <label class="control-label"><span style="color:red;">＊</span>移管元倉庫</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="movedata.from_warehouse_id"
                            :selectedIndexChanged="changeIdxFromWarehouse"
                            :is-required="false"
                            :initialized="initFromWarehouse"
                            :max-items="warehouselist.length"
                            :min-length="1"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                        <p class="text-danger">{{ errors.from_warehouse_id }}</p>
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-12" v-bind:class="{'has-error': (errors.to_warehouse_id != '') }">
                        <label class="control-label"><span style="color:red;">＊</span>移管先倉庫</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="movedata.to_warehouse_id"
                            :is-required="false"
                            :initialized="initToWarehouse"
                            :max-items="warehouselist.length"
                            :min-length="1"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                        <p class="text-danger">{{ errors.to_warehouse_id }}</p>
                    </div>
                    <div class="col-sm-2 col-xs-4" v-bind:class="{'has-error': (errors.move_date != '') }">
                        <label><span style="color:red;">＊</span>移管予定日</label>
                        <!-- <input type="text" class="form-control" /> -->
                        <wj-input-date class="form-control"
                            :value="movedata.move_date"
                            :selected-value="movedata.move_date"
                            :initialized="initDate"
                            :isRequired=false
                        ></wj-input-date>
                        <p class="text-danger">{{ errors.move_date }}</p>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12" style="text-align:right;">
                        <button type="submit" class="btn btn-save" v-bind:disabled="!isSearched">登録</button>
                    </div>
                </div>
                <h4><u>商品検索</u></h4>
                <div class="row">
                    <div class="col-md-3 col-sm-12 col-xs-12">
                        <label class="control-label">商品番号</label>
                        <input type="text" class="form-control" v-model="searchParams.product_code">
                    </div>
                    <div class="col-md-3 col-sm-12 col-xs-12">
                        <label class="control-label">商品名</label>
                        <input type="text" class="form-control" v-model="searchParams.product_name">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-12 col-xs-12">
                        <label class="control-label">型式／規格</label>
                        <input type="text" class="form-control" v-model="searchParams.model">
                    </div>
                    <div class="col-md-4 col-sm-12 col-xs-12">
                        <label class="control-label">案件名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_name"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_name"
                            :is-required="false"
                            :initialized="initMatter"
                            :max-items="matterlist.length"
                            :min-length="1"
                            :items-source="matterlist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 col-sm-12">
                        <label class="control-label">在庫種別</label>
                        <div class="col-md-12">
                            <el-checkbox class="col-md-3" v-model="checkList.order" :true-label="FLG_ON" :false-label="FLG_OFF">発注品</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkList.stock" :true-label="FLG_ON" :false-label="FLG_OFF">在庫品</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkList.keep" :true-label="FLG_ON" :false-label="FLG_OFF">預かり品</el-checkbox>
                        </div>  
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12" style="text-align:right;">
                        <button type="button" class="btn btn-primary" @click="clearSearch">クリア</button>
                        <button type="button" class="btn btn-save" @click="search">検索</button>
                    </div>
                </div>
                
                <div class="container-fluid">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="row">
                            <div class="col-sm-12 col-sm-12 col-xs-12">
                                <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">件数：{{ resultlen }}件</p>                            
                            </div>
                        </div>
                    </div>
                    <!-- グリッド -->
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div id="wjMoveGrid"></div>
                        <!-- <wj-multi-row
                            :allowAddNew="true"
                            :itemsSource="moves"
                            :layoutDefinition="layoutDefinition"
                            :initialized="initMultiRow"                        
                        ></wj-multi-row> -->
                    </div>            
                </div>
                <div class="col-md-12 col-sm-12">
                    <label class="col-md-12 col-sm-12">備考</label>
                    <textarea class="col-md-12 col-sm-12" v-model="mData.memo"></textarea>
                </div>
            </form>            
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
            <button type="button" class="btn btn-back" @click="back">戻る</button>
        </div>
    </div>
</template>

<script>
import * as wjNav from '@grapecity/wijmo.nav';
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';

var INIT_ROW = {
    // add: null,
    chk: false,
    product_code: '',
    product_name: '',
    model: '',
    quantity: 0,
    from_actual_quantity: 0,
    from_active_quantity: 0,
    from_reserve_quantity: 0,
    from_arrival_quantity: 0,
    from_next_arrival_date: '',
    from_last_arrival_date: '',    
    to_actual_quantity: 0,
    to_active_quantity: 0,
    to_reserve_quantity: 0,
    to_arrival_quantity: 0,
    to_next_arrival_date: '',
    to_last_arrival_date: '',
    id: '',
    product_id: 0,
};

export default {
    data: () => ({
        loading: false,
        loadEndFlg: false,
        isSearched: false,
        isNewFlg: 0,

        ORDER_FLG: 0,
        STOCK_FLG: 1,
        KEEP_FLG: 2,

        FLG_ON : 1,
        FLG_OFF: 0,       

        queryParam: '',
        urlparam: '',

        resultlen: 0,
        filterText: '',

        // データ
        mData: {
            memo: '',
        },
        wjInputObj: {
            from_warehouse_id: {},
            to_warehouse_id: {},
            move_date: {},
        },
        searchParams: {
            product_code: '',
            product_name: '',
            model: '',
            matter_name: '',
        },
        wjSearchObj: {
            product_code: {},
            product_name: {},
            model: {},
            matter_name: {},
        },
        checkList: {
            order: 1,
            stock: 1,
            keep: 1,
        },
        errors: {
            from_warehouse_id: '',
            to_warehouse_id: '',
            move_date: '',
        },
        IDs: [],

        // グリッド内オートコンプリート
        acProductCodeList: [],
        acProductNameList: [],

        preProductId : 0,
        
        keepDOM: {},
        moves: new wjCore.CollectionView(),
        
        layoutDefinition: null,

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ チェックボックス, ID ]
            deny_resizing_col: [0, 10, 11],
            // 非表示[ ID ]
            invisible_col: [10, 11, 12],
        },
        gridPKCol: 11,
        // 以下,initializedで紐づける変数
        wjMoveGrid: null,
    }),
    props: {
        movedata: {},
        warehouselist: Array,
        matterlist: Array,
        initparams: {},
        stockquantity: Array,
    },
    created: function() {
        // グリッドレイアウトセット
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        this.wjInputObj.from_warehouse_id.selectedValue = this.initparams.id;
        this.movedata.forEach(element => {
            this.IDs.push(element.id);
        });
        if(this.IDs.length == 0) {
            this.isNewFlg = this.FLG_ON;
        }

        // グリッド初期表示
        var targetDiv = '#wjMoveGrid';

        this.$nextTick(function() {
            var _this = this;
            var gridItemSource = new wjCore.CollectionView(this.movedata, {

            })
            gridItemSource.refresh();
            this.wjMoveGrid = this.createGrid(targetDiv, gridItemSource);
            this.applyGridSettings(this.wjMoveGrid);
        });

        this.loadEndFlg = true;        
    },
    methods: {
        clearSearch() {
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
            this.checkList = {
                order: 0,
                stock: 0,
                keep: 0,
            }
        },
        search() {
            this.loading = true
            this.isSearched = true;

            var params = new URLSearchParams();
            params.append('from_warehouse', this.rmUndefinedBlank(this.wjInputObj.from_warehouse_id.selectedValue));
            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('model', this.rmUndefinedBlank(this.searchParams.model));
            params.append('matter_name', this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
            params.append('order', this.checkList.order);
            params.append('stock', this.checkList.stock);
            params.append('keep', this.checkList.keep);

            axios.post('/stock-transfer-edit/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'from_warehouse=' + encodeURIComponent(this.rmUndefinedBlank(this.wjInputObj.from_warehouse_id.text));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'model=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.model));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.text));
                    this.urlparam += '&' + 'order=' + encodeURIComponent(this.rmUndefinedBlank(this.checkList.order));
                    this.urlparam += '&' + 'stock=' + encodeURIComponent(this.rmUndefinedBlank(this.checkList.stock));
                    this.urlparam += '&' + 'keep=' + encodeURIComponent(this.rmUndefinedBlank(this.checkList.keep));

                    // 検索条件保持
                    this.queryParam = params;

                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.stockList.forEach((element, i) => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {

                        }

                        element.order_detail_id = this.rmUndefinedZero(element.order_detail_id);
                        element.quote_id = this.rmUndefinedZero(element.quote_id);
                        element.quote_detail_id = this.rmUndefinedZero(element.quote_detail_id);

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            qr_code: element.qr_code,
                            stock_status: element.stock_status,
                            stock_flg: element.stock_flg,
                            matter_name: element.matter_name,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            model: element.model,
                            actual_quantity: element.actual_quantity,
                            active_quantity: element.active_quantity,
                            quantity: element.quantity,
                            all_quantity: element.active_quantity,
                            from_warehouse_id: element.warehouse_id,
                            matter_id: element.matter_id,
                            customer_id: element.customer_id,
                            order_detail_id: element.order_detail_id,
                            quote_id: element.quote_id,
                            quote_detail_id: element.quote_detail_id,
                            chk: false,
                        })
                    });
                    this.resultlen = dataCount;
                    if (dataCount == 0) {
                        this.isSearched = false;
                    }
                    // データセット
                    this.wjMoveGrid.itemsSource = itemsSource;

                    // this.filter();
                    this.tableData = dataCount;

                    // 設定更新
                    this.wjMoveGrid = this.applyGridSettings(this.wjMoveGrid);
                    // 描画更新
                    this.wjMoveGrid.refresh();
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
        },
        changeQuantity(row) {
            // 有効在庫数計算
            if (this.rmUndefinedBlank(row) !== '') {
                row.active_quantity = parseInt(this.rmUndefinedZero(row.all_quantity));
                row.active_quantity = parseInt(this.rmUndefinedZero(row.active_quantity)) - parseInt(this.rmUndefinedZero(row.quantity));
                this.wjMoveGrid.refresh();
            }
        },
        changeIdxFromWarehouse: function(sender){
            // 移管元倉庫を選択すると移管先を絞り込む
            var tmpHouse = this.warehouselist;
            if (sender.selectedValue) {
                tmpHouse = [];
                for(var key in this.warehouselist) {
                    if (sender.selectedValue != this.warehouselist[key].id) {
                        tmpHouse.push(this.warehouselist[key]);
                    }
                }             
            }
            this.wjInputObj.to_warehouse_id.itemsSource = tmpHouse;
            this.wjInputObj.to_warehouse_id.selectedIndex = -1;
        },
        // 同一QRのチェック状態変更
        chkGrid(row, qrCode) {
            if (this.rmUndefinedBlank(qrCode) !== '') {
                var isChecked = row.chk;

                this.wjMoveGrid.collectionView.items.forEach(element => {
                    if (element.qr_code == qrCode) {
                        element.chk = isChecked;
                    }
                });
                this.wjMoveGrid.refresh();
            }
        },
        // グリッド生成
        createGrid(divId, gridItems) {
            var grid = new wjMultiRow.MultiRow(divId, {
                itemsSource: gridItems,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowAddNew: false,
                allowDelete: false,
                allowSorting: true,
                headersVisibility: wjGrid.HeadersVisibility.Column,
                // allowMerging: wjGrid.AllowMerging.All,
                keyActionEnter: wjGrid.KeyAction.None,
            });

            // 行高さ
            grid.rows.defaultSize = 30;
            
            // セル結合を有効
            grid.allowMerging = wjGrid.AllowMerging.All;

            // エラーメッセージ表示
            var tip = new wjCore.Tooltip(),
                rng = null;
            tip.cssClass = 'wj-tooltip-invalid';                        
            grid.hostElement.addEventListener('mousemove', function(e) {
                var ht = grid.hitTest(e.pageX, e.pageY);
                if (!ht.range.equals(rng)) {
                    if (ht.cellType == wjGrid.CellType.Cell && ht.col == 8) {
                        rng = ht.range;
                        var cellElement = document.elementFromPoint(e.clientX, e.clientY),
                            cellBounds = grid.getCellBoundingRect(ht.row, ht.col),
                            // data = wjCore.escapeHtml(MSG_ERROR_ACTIVE_STOCK_OVER);
                            data = wjCore.escapeHtml(MSG_ERROR_ACTIVE_STOCK_OVER);
                        if (cellElement.className.indexOf('wj-cell') > -1 && cellElement.className.indexOf('wj-grid-invalid') > -1) {
                            // 表示
                            tip.show(grid.hostElement, data, cellBounds);
                        } else {
                            // 非表示
                            tip.hide();
                        }
                    }
                }
            })

            // セル編集後イベント：行内のデータ自動セット
            grid.cellEditEnded.addHandler(function(s, e) {
                var isOdd = ((e.row % 2) == 0);                    // 0始まりなので2で割り切れると奇数行(上段)
                var editColNm = e.panel.columns[e.col].name;       // 取れるのは上段の列名
                // 編集行データ取得
                // var row = s.collectionView.currentEditItem;
                var row = s.collectionView.currentItem;
                switch (editColNm) {
                    case 'chk':
                        this.chkGrid(row, row.qr_code);
                        break;
                    case 'quantity':
                        // 出庫数
                        this.changeQuantity(row);
                }
            }.bind(this));

            // 編集前イベント
            grid.beginningEdit.addHandler((s, e) => {
                var editColNm = e.panel.columns[e.col].name; 
                var row = s.rows[e.row].dataItem;
                // 在庫品以外の編集イベントキャンセル
                switch(editColNm) {
                    case 'quantity':
                        if (row.stock_flg != this.STOCK_FLG) {
                            e.cancel = true;
                        }
                }
            });

            var _this = this;
            // itemFormatterセット
            grid.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                var dataItem = panel.rows[r].dataItem;
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                    // チェックボックス生成
                    if (panel.columns[c].name == 'chk') {
                        var checkedCount = 0;
                        for (var i = 0; i < grid.rows.length; i++) {
                            if (grid.getCellData(i, c) == true) checkedCount++;
                        }

                        var checkBox = '<input type="checkbox">';
                        if(this.isReadOnly){
                            checkBox = '<input type="checkbox" disabled="true">';
                        }
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < grid.rows.length;

                        checkBox.addEventListener('click', function (e) {
                            grid.beginUpdate();
                            for (var i = 0; i < grid.collectionView.items.length; i++) {
                                grid.collectionView.items[i].chk = checkBox.checked;
                                // this.changeGridCheckBox(grid.collectionView.items[i]);
                            }
                            grid.endUpdate();
                        }.bind(this));
                    }

                } else if (panel.cellType == wjGrid.CellType.Cell) {
                    this.setGridCellReadOnlyColor(grid, r, c, cell);
                    var col = panel.columns[c];
                    switch(col.name) {
                        case 'qr_code':
                            break;
                        case 'stock_status':
                            break;
                        case 'matter_name':
                            break;
                        case 'product_code':
                            cell.style.textAlign = 'left';
                            break;
                        case 'product_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'model':
                            cell.style.textAlign = 'left';
                            break;
                        case 'active_quantity':
                            cell.style.textAlign = 'right';
                            break;
                        case 'quantity':
                            // エラー時のスタイル設定
                            if (dataItem.quantity > dataItem.all_quantity) {
                                wjCore.addClass(cell, "wj-grid-invalid");
                            } else {
                                wjCore.removeClass(cell, "wj-grid-invalid");
                            }
                            cell.style.textAlign = 'right';
                            break;
                    }


                }
            }.bind(this);

            return grid;
        },
        // 保存
        save() {
            this.loading = true;
            this.initErr(this.errors);

            var OkFlg = true;
            this.errors.move_date = '';
            var date = moment().format('YYYY/MM/DD');
            if (this.wjInputObj.move_date.text < date) {
                OkFlg = false;
                this.errors.move_date = MSG_ERROR_NEXT_DATE;
            }
            var items = [];
            var isNoInput = false;
            if (this.wjMoveGrid.collectionView.items.length != 0 && this.wjMoveGrid.itemsSource != undefined) {
                this.wjMoveGrid.itemsSource.forEach(element => {
                    if (element.chk == true) {
                        items.push(element);
                    }
                    if (element.chk == true && element.quantity == 0) {
                        isNoInput = true;
                    }
                });
            }
            if (items.length == 0) {
                OkFlg = false;
                alert(MSG_ERROR_NO_SELECT);
            } else if(isNoInput) {
                OkFlg = false;
                alert(MSG_ERROR_EMPTY_MOVE_INPUT);
            }

            if (OkFlg) {
                // パラメータセット
                var params = new URLSearchParams();
                params.append('from_warehouse_id', this.rmUndefinedBlank(this.wjInputObj.from_warehouse_id.selectedValue));
                params.append('to_warehouse_id', this.rmUndefinedBlank(this.wjInputObj.to_warehouse_id.selectedValue));
                params.append('move_date', this.rmUndefinedBlank(this.wjInputObj.move_date.text));
                params.append('memo', this.rmUndefinedBlank(this.mData.memo));
                // 移管データ
                params.append('move_items', JSON.stringify(items));

                axios.post('/stock-transfer-edit/save', params)
                .then( function (response) {
                    this.loading = false

                    if (response.data.status) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/stock-transfer-list' + window.location.search
                        location.href = (listUrl)
                    } else {
                        // 失敗
                        if(this.rmUndefinedBlank(response.data.message) !== ''){
                            alert(response.data.message.replace(/\\n/g, '\n'));
                        } else {
                            alert(MSG_ERROR);
                        }
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
            } else {
                this.loading = false;
            }
        },
        // 戻る
        back() {
            var listUrl = '/stock-transfer-list' + window.location.search
            location.href = listUrl;
        },
        getLayout() {
            return [
                {
                    header: 'chk', cells: [
                        { binding: 'chk', name: 'chk', header: 'chk', minWidth: GRID_COL_MIN_WIDTH, width: 70, isReadOnly: false, allowMerging: true },  
                    ]
                },
                {
                    header: 'QR番号', cells: [
                        { binding: 'qr_code', name: 'qr_code', header: 'QR番号', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true, allowMerging: true  },  
                    ]
                },
                {
                    header: '在庫種別', cells: [
                        { binding: 'stock_status', name: 'stock_status', header: '在庫種別', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true, allowMerging: true  },  
                    ]
                },
                {
                    header: '案件名', cells: [
                        { binding: 'matter_name', name: 'matter_name', header: '案件名', minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true, allowMerging: true  },  
                    ]
                },
                {
                    header: '商品番号', cells: [
                        { binding: 'product_code', name: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 190, isReadOnly: true  },  
                    ]
                },
                {
                    header: '商品名', cells: [
                        { binding: 'product_name', name: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 190, isReadOnly: true },  
                    ]
                },
                {
                    header: '型式／規格', cells: [
                        { binding: 'model', name: 'model', header: '型式／規格', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },                         
                    ]
                },
                {
                    header: '実在庫数', cells: [
                        { binding: 'actual_quantity', name: 'actual_quantity', header: '実在庫数', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true, isRequired: false },                        
                    ]
                }, 
                {
                    header: '有効在庫数', cells: [
                        { binding: 'active_quantity', name: 'active_quantity', header: '有効在庫数', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true, isRequired: false },                        
                    ]
                }, 
                {
                    header: '出庫数', cells: [
                        { binding: 'quantity', name: 'quantity', header: '出庫数', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: false, isRequired: false },                        
                    ]
                },
                /* 非表示 */
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },     
                {
                    header: 'product_id', cells: [
                        { binding: 'product_id', name: 'product_id', header: 'product_id', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },  
                {
                    header: 'from_warehouse_id', cells: [
                        { binding: 'from_warehouse_id', name: 'from_warehouse_id', header: 'from_warehouse_id', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },  
                {
                    header: 'stock_flg', cells: [
                        { binding: 'stock_flg', name: 'stock_flg', header: '在庫種別', minWidth: GRID_COL_MIN_WIDTH, width: 0, maxWidth: 0, isReadOnly: false  },  
                    ]
                },
                {
                    header: 'all_quantity', cells: [
                        { binding: 'all_quantity', name: 'all_quantity', header: 'all_quantity', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },                        
                    ]
                }, 
                {
                    header: 'matter_id', cells: [
                        { binding: 'matter_id', name: 'matter_id', header: 'matter_id', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },                        
                    ]
                }, 
                {
                    header: 'customer_id', cells: [
                        { binding: 'customer_id', name: 'customer_id', header: 'customer_id', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },                        
                    ]
                }, 
            ]
        },
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.specitem.id));

            axios.post('/spec-item-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/spec-item-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
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
                window.onbeforeunload = null;
                location.reload()
            }.bind(this))
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
            // grid.allowMerging = wjGrid.AllowMerging.All;
            
            return grid;
        },
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            multirow.selectionMode = wjGrid.SelectionMode.Cell;

            this.wjSpecItemGrid = multirow;
        },
        /* 以下オートコンプリード設定 */
        initFromWarehouse(sender) {
            this.wjInputObj.from_warehouse_id = sender
        },
        initToWarehouse(sender) {
            this.wjInputObj.to_warehouse_id = sender
        },
        initDate(sender) {
            this.wjInputObj.move_date = sender
        },
        // initProCode(sender) {
        //     this.wjSearchObj.product_code = sender
        // },
        // initProName(sender) {
        //     this.wjSearchObj.product_name = sender
        // },
        // initModel(sender) {
        //     this.wjSearchObj.model = sender
        // },
        initMatter(sender) {
            this.wjSearchObj.matter_name = sender
        },
    },
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
/* 検索結果 */
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-save {
    width: 80px;
}
.wj-grid-invalid {
    background-color: #FF3B30 !important;
}
.wj-tooltip-invalid {
    color: #FF3B30 !important;
}
.wj-state-readonly {
    opacity: 5 !important;
    background-color: #ccc !important;
}
/* グリッド */
.wj-multirow {
    height: 400px;
    margin: 6px 0;
}
.container-fluid .wj-multirow  {
    margin: 6px 0;
    font-size: 10px;
}
.container-fluid .multirow-reserve {
    height: 220px;
}
/* .container-fluid  .wj-detail{
    padding-left: 225px !important;
} */

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
    text-align: center !important;
}

.wj-cell {
    font-size: 1.5rem;
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