<template>
    <div>
        <loading-component :loading="loading" />
        <!-- モード変更 -->
        <div class="col-md-12 text-right">
            <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
            <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
        </div>
        <div class="search-form main-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="save">
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <h4>対象商品</h4>
                    </div>
                </div>

                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">商品番号：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.product_code }}</u></p>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">商品名：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.product_name }}</u></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">型式／規格：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.model }}</u></p>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">メーカー名：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.maker_name }}</u></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">定価：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>￥{{ productdata.price|comma_format }}</u></p>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">オープン価格：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.open_price_txt }}</u></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">仕入掛率：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.purchase_makeup_rate }}％</u></p>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">標準仕入単価：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>￥{{ productdata.normal_purchase_price|comma_format }}</u></p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">販売掛率：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.sales_makeup_rate }}％</u></p>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">標準販売単価：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>￥{{ productdata.normal_sales_price|comma_format }}</u></p>
                        </div>
                        <div class="col-md-4 col-sm-12 col-xs-12">
                            <p class="col-md-4 col-sm-4 col-xs-6">標準粗利率：</p>
                            <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ productdata.normal_gross_profit_rate }}％</u></p>
                        </div>
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
                    <div class="col-md-10 col-sm-10 col-xs-12">
                        <div id="wjProductGrid"></div>
                        <!-- <wj-multi-row
                            :allowAddNew="true"
                            :itemsSource="products"
                            :layoutDefinition="layoutDefinition"
                            :initialized="initMultiRow"                        
                        ></wj-multi-row> -->
                    </div>            
                </div>
            </form>            
        </div>
        <div class="main-body col-md-12 col-sm-12 col-xs-12">
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <p class="col-md-2 col-sm-2 col-xs-6">No</p>
                    <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ inputData.no }}</u></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <label class="col-md-2 col-sm-12 col-xs-12">区分</label>
                    <el-radio-group v-model="inputData.cost_sales_kbn" v-bind:disabled="(isReadOnly || selectedGrid)">
                        <div class="radio">
                            <el-radio :label="FLG_COST">仕入</el-radio>
                            <el-radio :label="FLG_SALES">販売</el-radio>
                        </div>
                    </el-radio-group>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-12" v-bind:class="{'has-error': (errors.price != '') }">
                    <div class="col-md-4">
                        <label class="control-label">価格</label>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" v-model="inputData.price" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.price }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-6 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.start_date != '') }">
                    <p class="col-md-4 col-sm-4 col-xs-6">適用開始日</p>
                    <wj-input-date
                        :value="inputData.start_date"
                        placeholder=" "
                        :isReadOnly="isReadOnly"
                        :initialized="initStartDate"
                        :isRequired="false"
                    ></wj-input-date>
                    <p class="text-danger">{{ errors.start_date }}</p>
                </div>
                <div class="col-md-6 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.end_date != '') }">
                    <p class="col-md-4 col-sm-4 col-xs-6">適用終了日</p>
                    <wj-input-date
                        :value="inputData.end_date" 
                        placeholder=" "
                        :isReadOnly="isReadOnly"
                        :initialized="initEndDate"
                        :isRequired="false"
                    ></wj-input-date>
                    <p class="text-danger">{{ errors.end_date }}</p>
                </div>
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                <button type="button" class="btn btn-primary" @click="clear" v-bind:disabled="isReadOnly">クリア</button>
                <button type="button" class="btn btn-save" @click="save" v-bind:disabled="isReadOnly">保存</button>
                <button type="button" class="btn btn-delete" @click="del" v-bind:disabled="(!selectedGrid || isReadOnly)">削除</button>
            </div>
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12 main-body" style="margin-top:15px;" v-show="(this.rmUndefinedBlank(this.lockdata.id) != '')">
            <!-- <div class="col-md-12">
                <div class="form-group">
                    <label class="col-md-6 col-sm-5 col-xs-12 control-label">更新日時</label>
                    <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ productdata.update_at|datetime_format }}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-6 col-sm-5 col-xs-12 control-label">更新者</label>
                    <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ productdata.update_user_name }}</p>
                </div>
            </div> -->
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="form-group">
                    <label class="col-md-6 col-sm-5 col-xs-12 control-label">ロック日時</label>
                    <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ lockdata.lock_dt|datetime_format }}</p>
                </div>
                <div class="form-group">
                    <label class="col-md-6 col-sm-5 col-xs-12 control-label">ロック者</label>
                    <p class="col-md-6 col-sm-7 col-xs-12 control-label form-control-static">{{ lockdata.lock_user_name }}</p>
                </div>
            </div>
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
            <button type="button" class="btn btn-back" @click="back">戻る</button>
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
        selectedGrid: false,

        FLG_ON : 1,
        FLG_OFF: 0,       
        FLG_COST: 1,
        FLG_SALES: 2,

        queryParam: '',
        urlparam: '',

        resultlen: 0,

        inputData: {
            id: '',
            no: '',
            cost_sales_kbn: 1,
            price: '',
            start_date: null,
            end_date: null,
        },

        // データ
        wjInputObj: {
            start_date: {},
            end_date: {},
        },
        errors: {
            message: '',
            start_date: '',
            end_date: '',
            price: '',
            cost_sales_kbn: '',
        },
        IDs: [],
        
        keepDOM: {},
        products: new wjCore.CollectionView(),
        
        layoutDefinition: null,

        gridSetting: {
            deny_resizing_col: [0, 1],

            invisible_col: [7],
        },
        gridPKCol: 7,

        // 以下,initializedで紐づける変数
        wjProductGrid: null,
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        productdata: {},
        campaignlist: Array,
    },
    created: function() {
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.productdata.id == null) {
                this.isShowEditBtn = false;
                this.isReadOnly = false;
            }

            // ロック中判定
            if (this.rmUndefinedBlank(this.lockdata.id) != '' && this.isOwnLock != 1) {
                this.isLocked = true;
                this.isShowEditBtn = false;
                this.isReadOnly = true;
            }
        }
        // グリッドレイアウトセット
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        if(this.isLocked) {
            // ロック中
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        }else {
            // 編集モードで開くか判定
            var query = window.location.search;
            if (this.isOwnLock == 1 || this.isEditMode(query, this.isReadOnly, this.isEditable)) {
                    this.isReadOnly = false
                    this.isShowEditBtn = false
            }
        }
        // 新規の場合
        var currentUrl = location.href
        var separatorDir = currentUrl.split('/');
        if (separatorDir.pop() === 'new') {
            window.onbeforeunload = null;
        }
        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }

        var itemsSource = [];
        var dataCount = 0;
        this.campaignlist.forEach(element => {
            dataCount++;
            element.price = parseInt(this.rmUndefinedZero(element.price));
            itemsSource.push({
                no: dataCount,
                id: element.id,
                price: element.price,
                cost_sales_kbn: element.cost_sales_kbn,
                cost_sales_kbn_txt: element.cost_sales_kbn_txt,
                start_date: element.start_date,
                end_date: element.end_date,
                update_at: element.update_at,
                chk: false,
            });
        });
        this.resultlen = dataCount;

        // グリッド初期表示
        var targetDiv = '#wjProductGrid';

        this.$nextTick(function() {
            var _this = this;
            var gridItemSource = new wjCore.CollectionView(itemsSource);
            // gridItemSource.refresh();
            this.wjProductGrid = this.createGrid(targetDiv, gridItemSource);
            this.applyGridSettings(this.wjProductGrid);
        });

    },
    methods: {
        // 検索条件のクリア
        clear: function() {
            // this.searchParams = this.initParams;
            var wjInputObj = this.wjInputObj;
            Object.keys(wjInputObj).forEach(function (key) {
                wjInputObj[key].selectedValue = null;
                wjInputObj[key].value = null;
                wjInputObj[key].text = null;
            });
            var kbn = this.inputData.cost_sales_kbn;
            this.inputData = {
                id: '',
                no: '',
                cost_sales_kbn: kbn,
                price: '',
                start_date: null,
                end_date: null,
            }
            this.wjProductGrid.itemsSource.items.forEach(element => {
                element.chk = false;
            })
            this.wjProductGrid.refresh();

            this.selectedGrid = false;
        },
        gridChecked(row) {
            if (row !== undefined || row !== null) {
                this.wjProductGrid.itemsSource.items.forEach(element => {
                    if (row.id == element.id) {
                        this.inputData = {
                            id: element.id,
                            no: element.no,
                            price: element.price,
                            cost_sales_kbn: element.cost_sales_kbn,
                            start_date: element.start_date,
                            end_date: element.end_date,
                        }
                    } else {
                        element.chk = false;
                    }
                });

                this.selectedGrid = true;
            }
        },
        // グリッド生成
        createGrid(divId, gridItems) {
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
                        _this.gridChecked(row);
                        break;
                }

                gridCtrl.collectionView.commitEdit();
            }.bind(this));

            // 編集前イベント
            gridCtrl.beginningEdit.addHandler((s, e) => {

            });

            var _this = this;
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
                        case 'no':
                            cell.style.textAlign = 'right';
                            break;
                        case 'chk':
                            // データ取得
                            var checkBox = cell.firstChild;
                            // チェック時にすぐに編集を確定
                            checkBox.addEventListener('mousedown', function (e) {
                                gridCtrl.collectionView.commitEdit();
                            });
                            break;
                        case 'cost_sales_kbn':
                            cell.style.textAlign = 'center';
                            break;
                        case 'price':
                            cell.style.textAlign = 'right';
                            break;
                        case 'start_date':
                            cell.style.textAlign = 'right';
                            break;
                        case 'end_date':
                            cell.style.textAlign = 'right';
                            break;
                        case 'update_at':
                            cell.style.textAlign = 'right';
                            break;
                    }


                }
            }.bind(this);

            return gridCtrl;
        },
        // 保存
        save() {
            this.loading = true;
            this.initErr(this.errors);
            var OkFlg = true;

            var now = moment().format('YYYY/MM/DD');
            if (this.rmUndefinedZero(this.inputData.id) == 0) {
                if (this.inputData.start_date < now) {
                    OkFlg = false;
                    this.errors.start_date = MSG_ERROR_NEXT_DATE;
                }
                if (this.inputData.end_date < now) {
                    OkFlg = false;
                    this.errors.end_date = MSG_ERROR_NEXT_DATE;
                }
            } 

            if (OkFlg) {
                // パラメータセット
                var params = new URLSearchParams();
                params.append('id', this.rmUndefinedBlank(this.inputData.id));
                params.append('product_id', this.rmUndefinedBlank(this.productdata.id));
                params.append('cost_sales_kbn',this.rmUndefinedZero(this.inputData.cost_sales_kbn));
                params.append('price', this.rmUndefinedBlank(this.inputData.price));
                params.append('start_date', this.rmUndefinedBlank(this.wjInputObj.start_date.text));
                params.append('end_date', this.rmUndefinedBlank(this.wjInputObj.end_date.text));

                axios.post('/product-campaign-price/save', params)
                .then( function (response) {
                    this.loading = false

                    if (response.data.message) {
                        this.errors.start_date = response.data.message;
                        this.errors.end_date = response.data.message;
                    }

                    if (response.data.result) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/product-campaign-price/' + this.productdata.id +  window.location.search
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
            } else {
                this.loading = false;
            }
        },
        // 戻る
        back() {
            var listUrl = '/product-list' + window.location.search
            location.href = listUrl;
        },
        getLayout() {
            return [
                {
                    header: 'No', cells: [
                        { binding: 'no', name: 'no', header: 'No', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                    ]
                },
                {
                    header: 'chk', cells: [
                        { binding: 'chk', name: 'chk', header: 'チェック', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: false},  
                    ]
                },
                {
                    header: '区分', cells: [
                        { binding: 'cost_sales_kbn_txt', name: 'cost_sales_kbn', header: '区分', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true },  
                    ]
                },
                {
                    header: '価格', cells: [
                        { binding: 'price', name: 'price', header: '価格', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true },  
                    ]
                },
                {
                    header: '適用開始日', cells: [
                        { binding: 'start_date', name: 'start_date', header: '適用開始日', minWidth: GRID_COL_MIN_WIDTH, width: 180, isReadOnly: true },  
                    ]
                },
                {
                    header: '適用終了日', cells: [
                        { binding: 'end_date', name: 'end_date', header: '適用終了日', minWidth: GRID_COL_MIN_WIDTH, width: 180, isReadOnly: true  },  
                    ]
                },
                {
                    header: '更新日時', cells: [
                        { binding: 'update_at', name: 'update_at', header: '更新日時', minWidth: GRID_COL_MIN_WIDTH, width: 180, isReadOnly: true },  
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
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('product_id', this.rmUndefinedBlank(this.productdata.id));

            axios.post('/product-campaign-price/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/product-campaign-price/' + this.productdata.id + window.location.search
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
        // 戻る
        back() {
            var listUrl = '/product-list' + window.location.search

            if (!this.isReadOnly && this.productdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'product-campaign-price');
                params.append('keys', this.rmUndefinedBlank(this.productdata.id));
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
            var params = new URLSearchParams();
            params.append('screen', 'product-campaign-price');
            params.append('keys', this.rmUndefinedBlank(this.productdata.id));
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING)
                        location.reload()
                    } else {
                        this.isReadOnly = false
                        this.isShowEditBtn = false
                        this.lockdata = response.data.lockdata
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
            params.append('screen', 'product-campaign-price');
            params.append('keys', this.rmUndefinedBlank(this.productdata.id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    // 編集中状態へ
                    this.isLocked = false
                    this.isReadOnly = false
                    this.isShowEditBtn = false
                    this.lockdata = response.data.lockdata
                    window.onbeforeunload = function(e) {
                        return MSG_CONFIRM_LEAVE;
                    };
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
        /* 以下オートコンプリード設定 */
        initStartDate(sender) {
            this.wjInputObj.start_date = sender
        },
        initEndDate(sender) {
            this.wjInputObj.end_date = sender
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
.btn-save {
    width: 80px;
}

/* グリッド */
.wj-multirow {
    height: 200px;
    margin: 6px 0;
}
.btn {
    width: 80px;
    height: 34.2px;
}

</style>