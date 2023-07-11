<template>
    <div>
        <loading-component :loading="loading" />
        <div class="search-form main-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search('0')">
                <!-- 転換元 -->
                <div class="main-body col-md-12">
                    <h4 class="col-md-12"><u>転換元商品</u></h4>
                    <label class="col-md-12">検索条件</label>
                    <div class="row">
                        <div class="col-sm-2 col-sm-offset-1" v-bind:class="{'has-error': (errors[0].warehouse_name != '') }">
                            <label>倉庫名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="warehouse_name"
                                display-member-path="warehouse_name"
                                selected-value-path="id"
                                :initialized="initWarehouse"
                                :selected-index="-1"
                                :selected-value="searchParams[0].warehouse_name"
                                :is-required="false"
                                :max-items="warehouselist.length"
                                :items-source="warehouselist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors[0].warehouse_name }}</p>
                        </div>
                        <div class="col-sm-2">
                            <label>商品番号</label>
                            <input type="text" class="form-control" v-model="searchParams[0].product_code">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_code"
                                display-member-path="product_code"
                                selected-value-path="product_code"
                                :initialized="initCode_1"
                                :selected-index="-1"
                                :selected-value="searchParams[0].product_code"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-sm-3">
                            <label>商品名</label>
                            <input type="text" class="form-control" v-model="searchParams[0].product_name">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="product_name"
                                :initialized="initPname_1"
                                :selected-index="-1"
                                :selected-value="searchParams[0].product_name"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-sm-2">
                            <label>型式／規格</label>
                            <input type="text" class="form-control" v-model="searchParams[0].model">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="model"
                                display-member-path="model"
                                selected-value-path="model"
                                :initialized="initModel_1"
                                :selected-index="-1"
                                :selected-value="searchParams[0].model"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="pull-right">
                            <button type="button" class="btn btn-primary btn-clear" @click="clear('0')">クリア</button>
                            <button type="submit" class="btn btn-search">検索</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="container-fluid col-xs-9">
                            <div class="row">
                                <div class="col-xs-12">
                                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData.one }}件</p>
                                </div>
                                <!-- グリッド -->
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div id="wjParentProductGrid"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3" style="margin-top:50px;">
                            <div class="form-group" v-bind:class="{'has-error': (errors[0].quantity != '') }">
                                <label class="control-label text-left">数量</label>
                                <div class="col-md-12">
                                    <input type="number" class="form-control" v-model="inputData[0].quantity">
                                    <p class="text-danger">{{ errors[0].quantity }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </form>
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search('1')">
                <!-- 転換先1 -->
                <div class="result-body col-md-12">
                    <h4 class="col-md-12"><u>転換先商品1</u></h4>
                    <label class="col-md-12">検索条件</label>
                    <div class="row">
                        <div class="col-sm-2 col-sm-offset-1">
                            <label>商品番号</label>
                            <input type="text" class="form-control" v-model="searchParams[1].product_code">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_code"
                                display-member-path="product_code"
                                selected-value-path="product_code"
                                :initialized="initCode_2"
                                :selected-index="-1"
                                :selected-value="searchParams[1].product_code"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-sm-3">
                            <label>商品名</label>
                            <input type="text" class="form-control" v-model="searchParams[1].product_name">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="product_name"
                                :initialized="initPname_2"
                                :selected-index="-1"
                                :selected-value="searchParams[1].product_name"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-sm-2">
                            <label>型式／規格</label>
                            <input type="text" class="form-control" v-model="searchParams[1].model">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="model"
                                display-member-path="model"
                                selected-value-path="model"
                                :initialized="initModel_2"
                                :selected-index="-1"
                                :selected-value="searchParams[1].model"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="pull-right">
                            <button type="button" class="btn btn-primary btn-clear" @click="clear('1')">クリア</button>
                            <button type="submit" class="btn btn-search">検索</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="container-fluid col-xs-9">
                            <div class="row">
                                <div class="col-xs-12">
                                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData.two }}件</p>
                                </div>
                                <!-- グリッド -->
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div id="wjChildGrid1"></div>
                                </div>        
                            </div>
                        </div>
                        <div class="col-md-3" style="margin-top:50px;">
                            <div class="form-group" v-bind:class="{'has-error': (errors[1].quantity != '') }">
                                <label class="control-label text-left">数量</label>
                                <div class="col-md-12">
                                    <input type="number" class="form-control" v-model="inputData[1].quantity">
                                    <p class="text-danger">{{ errors[1].quantity }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search('2')">
                <!-- 転換先2 -->
                <div class="result-body col-md-12">
                    <h4 class="col-md-12"><u>転換先商品2</u></h4>
                    <label class="col-md-12">検索条件</label>
                    <div class="row">
                        <div class="col-sm-2 col-sm-offset-1">
                            <label>商品番号</label>
                            <input type="text" class="form-control" v-model="searchParams[2].product_code">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_code"
                                display-member-path="product_code"
                                selected-value-path="product_code"
                                :initialized="initCode_3"
                                :selected-index="-1"
                                :selected-value="searchParams[2].product_code"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-sm-3">
                            <label>商品名</label>
                            <input type="text" class="form-control" v-model="searchParams[2].product_name">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="product_name"
                                :initialized="initPname_3"
                                :selected-index="-1"
                                :selected-value="searchParams[2].product_name"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="col-sm-2">
                            <label>型式／規格</label>
                            <input type="text" class="form-control" v-model="searchParams[2].model">
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="model"
                                display-member-path="model"
                                selected-value-path="model"
                                :initialized="initModel_3"
                                :selected-index="-1"
                                :selected-value="searchParams[2].model"
                                :is-required="false"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                        <div class="pull-right">
                            <button type="button" class="btn btn-primary btn-clear" @click="clear('2')">クリア</button>
                            <button type="submit" class="btn btn-search">検索</button>
                        </div>
                    </div>
                    <div class="row">
                        <div class="container-fluid col-xs-9">
                            <div class="row">
                                <p class="col-xs-4 text-left text-danger">{{ errors[2].msg }}</p>
                                <div class="col-xs-8">
                                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData.three }}件</p>
                                </div>
                                <!-- グリッド -->
                                <div class="col-md-12 col-sm-12 col-xs-12">
                                    <div id="wjChildGrid2"></div>
                                </div>        
                            </div>
                        </div>
                        <div class="col-md-3" style="margin-top:50px;">
                            <div class="form-group" v-bind:class="{'has-error': (errors[2].quantity != '') }">
                                <label class="control-label text-left">数量</label>
                                <div class="col-md-12">
                                    <input type="number" class="form-control" v-model="inputData[2].quantity">
                                    <p class="text-danger">{{ errors[2].quantity }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
            <div class="pull-right">
                <button type="button" class="btn btn-save" @click="save">登録</button>
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

        FLG_ON : 1,
        FLG_OFF: 0,

        queryParam: '',
        urlparam: '',

        resultlen: 0,

        tableData: {
            one: 0,
            two: 0,
            three: 0,
        },

        // データ
        searchParams: [
            {
                warehouse_name: '',
                product_code: '',
                product_name: '',
                model: '',
            },
            {
                warehouse_name: '',
                product_code: '',
                product_name: '',
                model: '',
            },
            {
                warehouse_name: '',
                product_code: '',
                product_name: '',
                model: '',
            },
        ],
        inputData: [
            {
                quantity: '',
            },
            {
                quantity: '',
            },
            {
                quantity: '',
            },
        ],

        wjSearchObj: [
            {
                warehouse_name: {},
                product_code: {},
                product_name: {},
                model: {},
            },
            {
                warehouse_name: {},
                product_code: {},
                product_name: {},
                model: {},
            },
            {
                warehouse_name: {},
                product_code: {},
                product_name: {},
                model: {},
            },
        ],
        errors: [
            {
                msg: '',
                warehouse_name: '',
                product_code: '',
                product_name: '',
                model: '',
                quantity: '',
            },
            {
                msg: '',
                warehouse_name: '',
                product_code: '',
                product_name: '',
                model: '',
                quantity: '',
            },
            {
                msg: '',
                warehouse_name: '',
                product_code: '',
                product_name: '',
                model: '',
                quantity: '',
            },
        ],

        keepDOM: {},
        products: new wjCore.CollectionView(),
        
        layoutDefinition: null,

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [6],
        },
        gridPKCol: 6,

        wjParentProductGrid: null,
        wjChildGrid1: null,
        wjChildGrid2: null,
        
    }),
    props: {
        isEditable: Number,
        // productlist: Array,
        warehouselist: Array,
    },
    created: function() {
        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams[0]);
            if (this.rmUndefinedBlank(this.searchParams[0].warehouse_name) != ''){
                this.searchParams[0].warehouse_name = parseInt(this.searchParams[0].warehouse_name);
            }
        }
        // グリッドレイアウトセット
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            // 検索
            this.search('0');
        }

        // グリッド初期表示
        var targetDiv1 = '#wjParentProductGrid';
        var targetDiv2 = '#wjChildGrid1';
        var targetDiv3 = '#wjChildGrid2';

        var itemsSource = [];

        this.$nextTick(function() {
            var _this = this;
            var gridItemSource = new wjCore.CollectionView(itemsSource);
            // gridItemSource.refresh();
            this.wjParentProductGrid = this.createGrid(targetDiv1, gridItemSource, 0);
            this.applyGridSettings(this.wjParentProductGrid);

            // 転換先1
            var gridChildItemSource1 = new wjCore.CollectionView(itemsSource);            
            this.wjChildGrid1 = this.createGrid(targetDiv2, gridChildItemSource1, 1);
            this.applyGridSettings(this.wjChildGrid1);

            // 転換先2
            var gridChildItemSource2 = new wjCore.CollectionView(itemsSource);
            this.wjChildGrid2 = this.createGrid(targetDiv3, gridChildItemSource2, 2);
            this.applyGridSettings(this.wjChildGrid2);
        });
    },
    methods: {
        save() {
            this.loading = true;
            this.errors = [
                {
                    msg: '',
                    warehouse_name: '',
                    product_code: '',
                    product_name: '',
                    model: '',
                    quantity: '',
                },
                {
                    msg: '',
                    warehouse_name: '',
                    product_code: '',
                    product_name: '',
                    model: '',
                    quantity: '',
                },
                {
                    msg: '',
                    warehouse_name: '',
                    product_code: '',
                    product_name: '',
                    model: '',
                    quantity: '',
                },
            ];
            var params = new URLSearchParams();

            var parentData = [];
            var convert1 = [];
            var convert2 = [];
            // 転換先
            this.wjParentProductGrid.itemsSource.forEach(record => {
                if (record.chk) {
                    parentData = record;
                    parentData.quantity = this.inputData[0].quantity;
                }
            });
            // 転換先1
            this.wjChildGrid1.itemsSource.forEach(record => {
                if (record.chk) {
                    convert1 = record;
                    convert1.quantity = this.inputData[1].quantity;
                }
            });
            // 転換先2
            this.wjChildGrid2.itemsSource.forEach(record => {
                if (record.chk) {
                    convert2 = record;
                    convert2.quantity = this.inputData[2].quantity;
                }
            });
            
            var validateFlg = true;
            var sumQuantity = 0;            
            // 転換元と転換先の商品が同じだった場合エラー
            if (parentData.product_id == convert1.product_id) {
                alert(MSG_ERROR_SAME_PRODUCT_ID);
                this.errors[1].msg = MSG_ERROR_SAME_PRODUCT_ID;
                validateFlg = false;
            }
            if (parentData.product_id == convert2.product_id) {
                alert(MSG_ERROR_SAME_PRODUCT_ID);
                this.errors[2].msg = MSG_ERROR_SAME_PRODUCT_ID;
                validateFlg = false;
            }

            if (this.rmUndefinedBlank(parentData) == '') {
                this.errors[0].msg = MSG_ERROR_NO_SELECT;
                validateFlg = false;
            } else if (this.inputData[0].quantity > parentData.stock_quantity) {
                this.errors[0].quantity = MSG_ERROR_ACTIVE_STOCK_OVER;
                validateFlg = false;
            }

            if (this.rmUndefinedBlank(convert1) == '' && this.rmUndefinedBlank(convert2) == '') {
                alert(MSG_ERROR_NO_SELECT);
                this.errors[1].msg = MSG_ERROR_NO_SELECT;
                this.errors[2].msg = MSG_ERROR_NO_SELECT;
                validateFlg = false;
            }

            // if (this.rmUndefinedBlank(convert1) != '') {
            //     sumQuantity += parseInt(this.rmUndefinedZero(this.inputData[1].quantity));
            // }
            // if (this.rmUndefinedBlank(convert2) != '') {
            //     sumQuantity += parseInt(this.rmUndefinedZero(this.inputData[2].quantity));
            // }


            // if (this.rmUndefinedZero(sumQuantity) != this.rmUndefinedZero(this.inputData[0].quantity)) {
            //     if (this.inputData[1].quantity > 0) {
            //         this.errors[1].quantity = '合計が' + this.inputData[0].quantity.toString() + 'になるように' + MSG_ERROR_NO_INPUT;
            //     }
            //     if (this.inputData[2].quantity > 0) {
            //         this.errors[2].quantity = '合計が' + this.inputData[0].quantity.toString() + 'になるように' + MSG_ERROR_NO_INPUT;
            //     }                
            //     validateFlg = false;
            // }

            
            if (!validateFlg) {
                this.loading = false;
                return;
            } else {
                params.append('warehouse_id', this.rmUndefinedZero(this.wjSearchObj[0].warehouse_name.selectedValue));
                params.append('parentData', JSON.stringify(parentData));
                params.append('convertObj1', JSON.stringify(convert1));
                params.append('convertObj2', JSON.stringify(convert2));

                axios.post('/stock-conversion/save', params)

                .then( function (response) {
                    if (response.data) {
                        var url = '/stock-conversion' + this.urlparam;

                        location.href = url;
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
        // グリッド生成
        createGrid(divId, gridItems, index) {
            var gridCtrl = new wjMultiRow.MultiRow(divId, {
                itemsSource: gridItems,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowAddNew: false,
                allowDelete: false,
                allowSorting: false,
                headersVisibility: wjGrid.HeadersVisibility.Column,
                keyActionEnter: wjGrid.KeyAction.None,
            });

            var _this = this;

            // 行高さ
            gridCtrl.rows.defaultSize = 30;

            // セル編集後イベント
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                var row = s.collectionView.currentItem;
                var col = gridCtrl.getBindingColumn(e.panel, e.row, e.col);

                switch (col.name) {
                    case 'chk':
                        _this.gridChecked(row, index);
                        break;
                }

                gridCtrl.collectionView.commitEdit();
            }.bind(this));

            // 編集前イベント
            gridCtrl.beginningEdit.addHandler((s, e) => {

            });

            var _this = this;
            var selectData = -1;
            // itemFormatterセット
            gridCtrl.itemFormatter = function(panel, r, c, cell) {
                // 列ヘッダ中央寄せ
                var dataItem = panel.rows[r].dataItem;
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                //     // チェックボックス生成
                //     if (panel.columns[c].name == 'chk') {
                //         var checkedCount = 0;
                //         for (var i = 0; i < gridCtrl.rows.length; i++) {
                //             if (gridCtrl.getCellData(i, c) == true) checkedCount++;
                //         }

                //         var checkBox = '<input type="checkbox">';
                //         if(this.isReadOnly){
                //             checkBox = '<input type="checkbox" disabled="true">';
                //         }
                //         cell.innerHTML = checkBox;
                //         var checkBox = cell.firstChild;
                //         checkBox.checked = checkedCount > 0;
                //         checkBox.indeterminate = checkedCount > 0 && checkedCount < gridCtrl.rows.length;

                //         checkBox.addEventListener('click', function (e) {
                //             gridCtrl.beginUpdate();
                //             for (var i = 0; i < gridCtrl.collectionView.items.length; i++) {
                //                 gridCtrl.collectionView.items[i].chk = checkBox.checked;
                //                 // this.changeGridCheckBox(grid.collectionView.items[i]);
                //             }
                //             gridCtrl.endUpdate();
                //         }.bind(this));
                //     }

                } else if (panel.cellType == wjGrid.CellType.Cell) {
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = panel.columns[c];
                    switch(col.name) {
                        case 'chk':
                            // データ取得
                            var checkBox = cell.firstChild;
                            // チェック時にすぐに編集を確定
                            checkBox.addEventListener('mousedown', function (e) {
                                gridCtrl.collectionView.commitEdit();
                            });
                            break;
                    }


                }
            }.bind(this);

            gridCtrl.hostElement.addEventListener('change', e => {
            });

            gridCtrl.hostElement.addEventListener('keydown', function(e) {
            })

            return gridCtrl;
        },
        gridChecked(row, index) {
            if (row !== undefined || row !== null) {
                if (index == 0) {
                    this.wjParentProductGrid.itemsSource.forEach(element => {
                        if (row.id != element.id) {
                            element.chk = false;
                        } else {
                            this.inputData[index].quantity = row.stock_quantity;
                        }
                    });
                } else if (index == 1) {
                    this.wjChildGrid1.itemsSource.forEach(element => {
                        if (row.product_id != element.product_id) {
                            element.chk = false;
                        }
                    });
                } else if (index == 2) {
                    this.wjChildGrid2.itemsSource.forEach(element => {
                        if (row.product_id != element.product_id) {
                            element.chk = false;
                        }
                    });
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
            // grid.allowMerging = wjGrid.AllowMerging.All;
            
            return grid;
        },
        getLayout() {
            return [
                {
                    header: 'chk', cells: [
                        { binding: 'chk', name: 'chk', header: '選択', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: false },  
                    ]
                },
                {
                    header: '商品番号', cells: [
                        { binding: 'product_code', name: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true},  
                    ]
                },
                {
                    header: '商品名', cells: [
                        { binding: 'product_name', name: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },  
                    ]
                },
                {
                    header: '型式／規格', cells: [
                        { binding: 'model', name: 'model', header: '型式／規格', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },  
                    ]
                },
                {
                    header: '棚名', cells: [
                        { binding: 'shelf_area', name: 'shelf_area', header: '棚名', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },  
                    ]
                },
                {
                    header: '有効在庫数', cells: [
                        { binding: 'stock_quantity', name: 'stock_quantity', header: '有効在庫数', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true  },  
                    ]
                },
                /* 非表示 */
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },     
            ]
        },
        // 検索
        search(index) {
            this.loading = true
            this.errors = [
                {
                    msg: '',
                    warehouse_name: '',
                    product_code: '',
                    product_name: '',
                    model: '',
                    quantity: '',
                },
                {
                    msg: '',
                    warehouse_name: '',
                    product_code: '',
                    product_name: '',
                    model: '',
                    quantity: '',
                },
                {
                    msg: '',
                    warehouse_name: '',
                    product_code: '',
                    product_name: '',
                    model: '',
                    quantity: '',
                },
            ];
            var params = new URLSearchParams();

            params.append('index', this.rmUndefinedZero(index));
            params.append('warehouse_name', this.rmUndefinedBlank(this.wjSearchObj[0].warehouse_name.selectedValue));
            params.append('product_code', this.rmUndefinedBlank(this.searchParams[index].product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams[index].product_name));
            params.append('model', this.rmUndefinedBlank(this.searchParams[index].model));

            axios.post('/stock-conversion/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'warehouse_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj[0].warehouse_name.selectedValue));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams[0].product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams[0].product_name));
                    this.urlparam += '&' + 'model=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams[0].model));

                    var item = response.data;
                    var itemsSource = [];
                    var dataLength = 0;
                    item.forEach(element => {
                        itemsSource.push({
                            chk: false,
                            id: element.id,
                            warehouse_id: element.warehouse_id,
                            product_id: element.product_id,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            model: element.model,
                            shelf_number_id: element.shelf_number_id,
                            shelf_area: element.shelf_area,
                            stock_quantity: element.stock_quantity,
                        })
                        dataLength++;
                    });

                    if (index == 0) {
                        // データセット
                        this.wjParentProductGrid.itemsSource = itemsSource;
                        this.tableData.one = dataLength;

                        // 設定更新
                        this.wjParentProductGrid = this.applyGridSettings(this.wjParentProductGrid);

                        // 描画更新
                        this.wjParentProductGrid.refresh();


                        this.wjChildGrid1.itemsSource = [];
                        this.tableData.two = 0;
                        // 設定更新
                        this.wjChildGrid1 = this.applyGridSettings(this.wjChildGrid1);
                        // 描画更新
                        this.wjChildGrid1.refresh();

                        this.wjChildGrid2.itemsSource = [];
                        this.tableData.three = 0;
                        // 設定更新
                        this.wjChildGrid2 = this.applyGridSettings(this.wjChildGrid2);
                        // 描画更新
                        this.wjChildGrid2.refresh();
                        
                    } else if (index == 1) {
                        // データセット
                        this.wjChildGrid1.itemsSource = itemsSource;
                        this.tableData.two = dataLength;

                        // 設定更新
                        this.wjChildGrid1 = this.applyGridSettings(this.wjChildGrid1);

                        // 描画更新
                        this.wjChildGrid1.refresh();

                    } else if (index == 2) {
                        // データセット
                        this.wjChildGrid2.itemsSource = itemsSource;
                        this.tableData.three = dataLength;

                        // 設定更新
                        this.wjChildGrid2 = this.applyGridSettings(this.wjChildGrid2);

                        // 描画更新
                        this.wjChildGrid2.refresh();
                    }
                }
                
                this.loading = false
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    this.loading = false
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors[0])
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
        // 検索条件のクリア
        clear: function(index) {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            wjSearchObj.forEach((obj, i) => {
                if (i == index){
                    Object.keys(obj).forEach(function (key) {
                        obj[key].selectedValue = null;
                        obj[key].value = null;
                        obj[key].text = null;
                    });
                }
            });    
            var searchParams = this.searchParams;
            searchParams.forEach((obj, i) => {
                if (i == index){
                    Object.keys(obj).forEach(function (key) {
                        obj[key] = '';
                    });
                }
            });        
        },
        /* 以下オートコンプリード設定 */
        initWarehouse(sender) {
            this.wjSearchObj[0].warehouse_name = sender
        },
        // initCode_1(sender) {
        //     this.wjSearchObj[0].product_code = sender
        // },
        // initPname_1(sender) {
        //     this.wjSearchObj[0].product_name = sender
        // },
        // initModel_1(sender) {
        //     this.wjSearchObj[0].model = sender
        // },
        // initCode_2(sender) {
        //     this.wjSearchObj[1].product_code = sender
        // },
        // initPname_2(sender) {
        //     this.wjSearchObj[1].product_name = sender
        // },
        // initModel_2(sender) {
        //     this.wjSearchObj[1].model = sender
        // },
        // initCode_3(sender) {
        //     this.wjSearchObj[2].product_code = sender
        // },
        // initPname_3(sender) {
        //     this.wjSearchObj[2].product_name = sender
        // },
        // initModel_3(sender) {
        //     this.wjSearchObj[2].model = sender
        // },
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
.btn-search {
    margin: 30px 10px 0px 0px;
    width: 80px;
}
.btn-clear {
    margin: 30px 10px 0px 0px;
    width: 70px;
}
.wj-multirow {
    height: 200px;
}
</style>