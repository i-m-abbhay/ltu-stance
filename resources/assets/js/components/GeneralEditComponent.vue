<template>
    <div>
        <loading-component :loading="loading" />
        <!-- モード変更 -->
        <div class="col-md-12 text-right">
            <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
            <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
            <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
            <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="(isShowEditBtn && isEditable)">編集</button>
            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
        </div>
        <div class="main-body col-md-12 col-sm-12 col-xs-12y">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    <p class="col-md-2 col-sm-6 col-xs-6">分類名：</p>
                    <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ categorydata.category_name }}</u></p>
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
                    <div id="wjGeneralGrid"></div>
                    <!-- <wj-multi-row
                        :allowAddNew="true"
                        :itemsSource="products"
                        :layoutDefinition="layoutDefinition"
                        :initialized="initMultiRow"                        
                    ></wj-multi-row> -->
                </div>            
            </div>
        </div>
        <div class="result-body col-md-12 col-sm-12 col-xs-12">
            <div class="row">
                <div class="col-md-6 col-sm-6 col-xs-12">
                    <p class="col-md-2 col-sm-2 col-xs-6">No</p>
                    <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ inputData.no }}</u></p>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.value_text_1 != '') }">
                    <div class="col-md-2">
                        <label class="control-label">文字１</label>
                    </div>
                    <div class="col-md-10">
                        <input type="text" class="form-control" v-model="inputData.value_text_1" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.value_text_1 }}</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.value_text_2 != '') }">
                    <div class="col-md-2">
                        <label class="control-label">文字２</label>
                    </div>
                    <div class="col-md-10">
                        <input type="text" class="form-control" v-model="inputData.value_text_2" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.value_text_2 }}</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.value_text_3 != '') }">
                    <div class="col-md-2">
                        <label class="control-label">文字３</label>
                    </div>
                    <div class="col-md-10">
                        <input type="text" class="form-control" v-model="inputData.value_text_3" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.value_text_3 }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.value_num_1 != '') }">
                    <div class="col-md-2">
                        <label class="control-label">数値１</label>
                    </div>
                    <div class="col-md-10">
                        <input type="number" class="form-control" v-model="inputData.value_num_1" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.value_num_1 }}</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.value_num_2 != '') }">
                    <div class="col-md-2">
                        <label class="control-label">数値２</label>
                    </div>
                    <div class="col-md-10">
                        <input type="number" class="form-control" v-model="inputData.value_num_2" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.value_num_2 }}</p>
                    </div>
                </div>
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.value_num_3 != '') }">
                    <div class="col-md-2">
                        <label class="control-label">数値３</label>
                    </div>
                    <div class="col-md-10">
                        <input type="number" class="form-control" v-model="inputData.value_num_3" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.value_num_3 }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.display_order != '') }">
                    <div class="col-md-2">
                        <label class="control-label">表示順</label>
                    </div>
                    <div class="col-md-10">
                        <input type="number" class="form-control" v-model="inputData.display_order" v-bind:readonly="isReadOnly">
                        <p class="text-danger">{{ errors.display_order }}</p>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-4 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.del_flg != '') }">
                    <label class="col-md-2 col-sm-12 col-xs-12">状態</label>
                    <el-radio-group v-model="inputData.del_flg" v-bind:disabled="isReadOnly">
                        <div class="radio">
                            <el-radio :label="FLG_OFF">使用する</el-radio>
                            <el-radio :label="FLG_ON">使用しない</el-radio>
                        </div>
                        <p class="text-danger">{{ errors.del_flg }}</p>
                    </el-radio-group>
                </div>
            </div>

            <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                <button type="button" class="btn btn-primary" @click="clear" v-bind:disabled="isReadOnly">クリア</button>
                <button type="button" class="btn btn-save" @click="save" v-bind:disabled="isReadOnly">保存</button>
                <!-- <button type="button" class="btn btn-delete" @click="del" v-bind:disabled="(!selectedGrid || isReadOnly)">削除</button> -->
            </div>
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12 text-right">
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

        FLG_PLUS: 1,
        FLG_MULTI: 2,

        queryParam: '',
        urlparam: '',

        resultlen: 0,

        inputData: {
            id: '',
            no: '',
            value_text_1: '',
            value_text_2: '',
            value_text_3: '',
            value_num_1: '',
            value_num_2: '',
            value_num_3: '',
            display_order: '',
            del_flg: 0,
        },

        // データ
        wjInputObj: {
            start_date: {},
            end_date: {},
        },
        errors: {
            id: '',
            no: '',
            value_text_1: '',
            value_text_2: '',
            value_text_3: '',
            value_num_1: '',
            value_num_2: '',
            value_num_3: '',
            display_order: '',
            del_flg: 0,
        },
        
        keepDOM: {},
        products: new wjCore.CollectionView(),
        
        layoutDefinition: null,

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 0,

        // 以下,initializedで紐づける変数
        wjGeneralGrid: null,
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        categorydata: {},
        categorylist: Array,
    },
    created: function() {
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.categorydata.id == null) {
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
        this.categorylist.forEach(element => {
            dataCount++;
            element.price = parseInt(this.rmUndefinedZero(element.price));
            itemsSource.push({
                no: dataCount,
                id: element.id,
                category_code: element.category_code,
                category_name: element.category_name,
                value_code: element.value_code,
                value_text_1: element.value_text_1,
                value_text_2: element.value_text_2,
                value_text_3: element.value_text_3,
                value_num_1: element.value_num_1,
                value_num_2: element.value_num_2,
                value_num_3: element.value_num_3,
                del_flg: element.del_flg,
                display_order: element.display_order,
                status: element.status,
                chk: false,
            });
        });
        this.resultlen = dataCount;

        // グリッド初期表示
        var targetDiv = '#wjGeneralGrid';

        this.$nextTick(function() {
            var _this = this;
            var gridItemSource = new wjCore.CollectionView(itemsSource);
            // gridItemSource.refresh();
            this.wjGeneralGrid = this.createGrid(targetDiv, gridItemSource);
            this.applyGridSettings(this.wjGeneralGrid);
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
            this.inputData = {
                id: '',
                no: '',
                value_text_1: '',
                value_text_2: '',
                value_text_3: '',
                value_num_1: '',
                value_num_2: '',
                value_num_3: '',
                display_order: '',
                del_flg: 0,
            }
            this.wjGeneralGrid.itemsSource.items.forEach(element => {
                element.chk = false;
            })
            this.wjGeneralGrid.refresh();

            this.selectedGrid = false;
        },
        gridChecked(row) {
            if (row !== undefined || row !== null) {
                this.wjGeneralGrid.itemsSource.items.forEach(element => {
                    if (row.id == element.id) {
                        this.inputData = {
                            no: element.value_code,
                            id: element.id,
                            category_code: element.category_code,
                            category_name: element.category_name,
                            value_code: element.value_code,
                            value_text_1: element.value_text_1,
                            value_text_2: element.value_text_2,
                            value_text_3: element.value_text_3,
                            value_num_1: element.value_num_1,
                            value_num_2: element.value_num_2,
                            value_num_3: element.value_num_3,
                            del_flg: element.del_flg,
                            display_order: element.display_order,
                            status: element.status,
                        }
                    } else {
                        element.chk = false;
                    }
                });

                this.initErr(this.errors);
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
                            cell.style.textAlign = 'center';
                            break;
                        case 'chk':
                            // データ取得
                            var checkBox = cell.firstChild;
                            // チェック時にすぐに編集を確定
                            checkBox.addEventListener('mousedown', function (e) {
                                gridCtrl.collectionView.commitEdit();
                            });
                            break;
                        case 'value_text_1':
                            cell.style.textAlign = 'left';
                            break;
                        case 'value_text_2':
                            cell.style.textAlign = 'left';
                            break;
                        case 'value_text_3':
                            cell.style.textAlign = 'left';
                            break;
                        case 'value_num_1':
                            cell.style.textAlign = 'right';
                            break;
                        case 'value_num_2':
                            cell.style.textAlign = 'right';
                            break;
                        case 'value_num_3':
                            cell.style.textAlign = 'right';
                            break;
                        case 'display_order':
                            cell.style.textAlign = 'center';
                            break;
                        case 'status':
                            cell.style.textAlign = 'center';
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
            
            var maxNum = 0;
            var arr = [];
            // value_codeの最大値
            this.categorylist.forEach(element => {
                arr.push(parseInt(this.rmUndefinedZero(element.value_code)));
            });
            maxNum = arr.reduce((a , b) => a > b ? a : b);

            // value_code計算
            if (this.categorydata.edit_kbn == this.FLG_PLUS) {
                maxNum++;
            } else if (this.categorydata.edit_kbn == this.FLG_MULTI) {
                maxNum = maxNum * 2;
            }
        
            // パラメータセット
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('category_id', this.rmUndefinedBlank(this.categorydata.id));
            params.append('category_code', this.rmUndefinedBlank(this.categorydata.category_code));
            params.append('category_name',this.rmUndefinedBlank(this.categorydata.category_name));
            params.append('value_code',this.rmUndefinedBlank(maxNum));
            params.append('value_text_1', this.rmUndefinedBlank(this.inputData.value_text_1));
            params.append('value_text_2', this.rmUndefinedBlank(this.inputData.value_text_2));
            params.append('value_text_3', this.rmUndefinedBlank(this.inputData.value_text_3));
            params.append('value_num_1', this.rmUndefinedBlank(this.inputData.value_num_1));
            params.append('value_num_2', this.rmUndefinedBlank(this.inputData.value_num_2));
            params.append('value_num_3', this.rmUndefinedBlank(this.inputData.value_num_3));
            params.append('display_order', this.rmUndefinedBlank(this.inputData.display_order));
            params.append('edit_kbn', this.rmUndefinedZero(this.categorydata.edit_kbn));
            params.append('del_flg', this.rmUndefinedZero(this.inputData.del_flg));

            axios.post('/general-edit/save', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/general-edit/' + this.categorydata.category_code +  window.location.search
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
                    window.onbeforeunload = null;
                    location.reload();
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
                    window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
        },
        getLayout() {
            return [
                {
                    header: 'No', cells: [
                        { binding: 'value_code', name: 'no', header: 'No', minWidth: GRID_COL_MIN_WIDTH, width: 60, isReadOnly: true },  
                    ]
                },
                {
                    header: 'chk', cells: [
                        { binding: 'chk', name: 'chk', header: '選択', minWidth: GRID_COL_MIN_WIDTH, width: 70, isReadOnly: false},  
                    ]
                },
                {
                    header: '文字１', cells: [
                        { binding: 'value_text_1', name: 'value_text_1', header: '文字１', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },  
                    ]
                },
                {
                    header: '文字２', cells: [
                        { binding: 'value_text_2', name: 'value_text_2', header: '文字２', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },  
                    ]
                },
                {
                    header: '文字３', cells: [
                        { binding: 'value_text_3', name: 'value_text_3', header: '文字３', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },  
                    ]
                },
                {
                    header: '数値１', cells: [
                        { binding: 'value_num_1', name: 'value_num_1', header: '数値１', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true  },  
                    ]
                },
                {
                    header: '数値２', cells: [
                        { binding: 'value_num_2', name: 'value_num_2', header: '数値２', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true  },  
                    ]
                },
                {
                    header: '数値１', cells: [
                        { binding: 'value_num_3', name: 'value_num_3', header: '数値３', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true  },  
                    ]
                },
                {
                    header: '表示順', cells: [
                        { binding: 'display_order', name: 'display_order', header: '表示順', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                    ]
                },
                {
                    header: '状態', cells: [
                        { binding: 'status', name: 'status', header: '状態', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                    ]
                },
                /* 非表示 */
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },     
                {
                    header: 'del_flg', cells: [
                        { binding: 'del_flg', name: 'del_flg', header: 'del_flg', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },   
            ]
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
            var listUrl = '/general-list' + window.location.search

            if (!this.isReadOnly && this.categorydata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'general-edit');
                params.append('keys', this.rmUndefinedBlank(this.categorydata.id));
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
            params.append('screen', 'general-edit');
            params.append('keys', this.rmUndefinedBlank(this.categorydata.id));
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
            params.append('screen', 'general-edit');
            params.append('keys', this.rmUndefinedBlank(this.categorydata.id));
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
        // initStartDate(sender) {
        //     this.wjInputObj.start_date = sender
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