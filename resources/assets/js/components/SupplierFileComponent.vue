<template>
    <div>
        <loading-component :loading="loading" />
        <!-- モード変更 -->
        <div class="col-md-12 text-right">
            <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
            <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
            <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
            <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
            <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
        </div>
        <div class="main-body col-sm-12 col-md-12">
            <div class="row">
                    <p class="col-md-3 col-sm-4 col-xs-6">仕入先／メーカー名：</p>
                    <p class="col-md-6 col-sm-6 col-xs-6"><u>{{ supplierdata.juridical_supplier_name }}</u></p>
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
                    <div id="wjFileGrid"></div>
                </div>            
            </div>
        </div>
        <!-- 入力フォーム -->
        <div class="main-body col-md-12 col-sm-12 col-xs-12" style="margin-top:15px;" @dragleave.prevent @dragover.prevent @drop.prevent="selectFile($event)">    
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.start_date != '') }">
                    <p class="col-md-2 col-sm-2 col-xs-12">適用開始日</p>
                    <wj-input-date
                        :value="inputData.start_date"
                        placeholder=" "
                        :isReadOnly="isReadOnly"
                        :initialized="initStartDate"
                        :isRequired="false"
                    ></wj-input-date>
                    <p class="text-danger">{{ errors.start_date }}</p>
                </div>
            </div>
            <div class="row">
                <div class="form-group"> 
                    <div class="col-md-12">
                        <label class="file_label col-md-4 col-sm-6">
                            <input type="file" id="file" class="file-upload-btn" @change="selectFile($event)" accept="application/pdf" v-bind:disabled="isReadOnly">
                            <span for="file">{{ inputData.file_label }}</span>
                        </label>
                        <!-- <el-button type="danger" icon="el-icon-delete" circle size="mini" v-show="item.file != ''" @click="deleteFile(iCnt)" v-bind:disabled="isConf"></el-button>
                        <el-button type="success" icon="el-icon-download" circle size="mini" v-show="item.file_name != ''" @click="downloadFile(item.file_name)" v-bind:disabled="isConf"></el-button> -->
                    </div>
                    <p class="col-md-12 text-danger">{{ errors.file }}</p>
                </div>
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                <button type="button" class="btn btn-save" @click="save" v-bind:disabled="isReadOnly">保存</button>
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

        FLG_ON : 1,
        FLG_OFF: 0,

        queryParam: '',
        urlparam: '',

        resultlen: 0,

        inputData: {
            id: '',
            start_date: null,
            file_name: '',
            file_label: LBL_FILE,
            file: '',
        },

        // データ
        wjInputObj: {
            start_date: {},
        },
        errors: {
            file: '',
            start_date: '',
        },
        
        keepDOM: {},
        files: new wjCore.CollectionView(),
        
        layoutDefinition: null,

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 4,

        // 以下,initializedで紐づける変数
        wjFileGrid: null,
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        supplierdata: {},
        filelist: Array,
    },
    created: function() {
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.supplierdata.id == null) {
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
        this.filelist.forEach(element => {
            // DOM生成
            this.keepDOM[element.id] = {
                del_btn: document.createElement('div'),
                view_file: document.createElement('a'),
            }

            // 表示ボタン
            this.keepDOM[element.id].view_file.innerHTML = '表示';
            this.keepDOM[element.id].view_file.target = '_blank';
            this.keepDOM[element.id].view_file.rel = 'noopener';
            this.keepDOM[element.id].view_file.href = '/supplier-file/openFile/' + element.id;
            this.keepDOM[element.id].view_file.classList.add('btn', 'btn-view');
           
            // 削除ボタン
            var _this = this;
            if (this.isReadOnly) {
                this.keepDOM[element.id].del_btn.innerHTML = '<button class="btn btn-danger btn-delete" disabled>削除</button>';
            } else {
                this.keepDOM[element.id].del_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-danger btn-delete">削除</button>';
                this.keepDOM[element.id].del_btn.addEventListener('click', function(e) {
                    if (e.target.dataset.id) {
                        var data = JSON.parse(e.target.dataset.id);
                        _this.del(data);
                    }
                });
            }


            itemsSource.push({
                id: element.id,
                file_name: element.file_name,
                supplier_id: element.supplier_id,
                start_date: element.start_date,
            });
            dataCount++;
        });

        this.resultlen = dataCount;

        // グリッド初期表示
        var targetDiv = '#wjFileGrid';

        this.$nextTick(function() {
            var _this = this;
            var gridItemSource = new wjCore.CollectionView(itemsSource);
            // gridItemSource.refresh();
            this.wjFileGrid = this.createGrid(targetDiv, gridItemSource);
            this.applyGridSettings(this.wjFileGrid);
        });

    },
    methods: {
        // 添付ファイル情報の取得
        selectFile(event) {
            let fileList = event.target.files ? 
                               event.target.files:
                               event.dataTransfer.files;
            let files = [];

            this.errors.file = '';

            for(let i = 0; i < fileList.length; i++){
                if (fileList[i].type.indexOf('pdf') > -1) {
                    // PDF形式の場合
                    this.inputData.file = fileList[i]
                    this.inputData.file_name = ''
                    this.inputData.file_label = fileList[i].name
                } else {
                    // その他(error)
                    this.errors.file = MSG_ERROR_ILLEGAL_FILE_EXTENSION;
                }
            }            
        },
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
                start_date: null,
                file_name: '',
                file: null,
            }
            this.wjFileGrid.itemsSource.items.forEach(element => {
                element.chk = false;
            })
            this.wjFileGrid.refresh();

            this.selectedGrid = false;
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

                } else if (panel.cellType == wjGrid.CellType.Cell) {
                    // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = panel.columns[c];
                    switch(col.name) {
                        case 'file_name':
                            cell.style.textAlign = "left";
                            break;
                        case 'start_date':
                            cell.style.textAlign = "center";
                            break;
                        case 'view_file':
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].view_file);
                            break;
                        case 'del_btn':
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].del_btn);
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

            // パラメータセット
            var params = new FormData();
            // params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('supplier_id', this.rmUndefinedBlank(this.supplierdata.id));
            params.append('start_date',this.rmUndefinedZero(this.wjInputObj.start_date.text));
            params.append('file', this.rmUndefinedBlank(this.inputData.file));

            axios.post('/supplier-file/save', params)
            .then( function (response) {
                this.loading = false

                if (response.data.message) {
                    // this.errors.start_date = response.data.message;
                }

                if (response.data.result) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/supplier-file/' + this.supplierdata.id +  window.location.search
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
                    header: 'ファイル名', cells: [
                        { binding: 'file_name', name: 'file_name', header: 'ファイル名', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },  
                    ]
                },
                {
                    header: '適用開始日', cells: [
                        { binding: 'start_date', name: 'start_date', header: '適用開始日', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true},  
                    ]
                },
                {
                    header: '表示', cells: [
                        { name: 'view_file', header: '表示', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
                    ]
                },
                {
                    header: '削除', cells: [
                        { name: 'del_btn', header: '削除', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },  
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
        del(id) {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var fileNm = '';
            this.filelist.forEach(element => {
                if (element.id == id) {
                    fileNm = element.file_name;
                }
            });

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(id));
            params.append('supplier_id', this.rmUndefinedBlank(this.supplierdata.id));
            params.append('file_name', this.rmUndefinedBlank(fileNm));

            axios.post('/supplier-file/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/supplier-file/' + this.supplierdata.id + window.location.search
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
                // window.onbeforeunload = null;
                // location.reload()
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
            var listUrl = '/supplier-list' + window.location.search

            if (!this.isReadOnly && this.supplierdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'supplier-file');
                params.append('keys', this.rmUndefinedBlank(this.supplierdata.id));
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
            params.append('screen', 'supplier-file');
            params.append('keys', this.rmUndefinedBlank(this.supplierdata.id));
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
                        this.filelist.forEach(element => {         
                            // 削除ボタン
                            var _this = this;
                            if (this.isReadOnly) {
                                this.keepDOM[element.id].del_btn.innerHTML = '<button class="btn btn-danger btn-delete" disabled>削除</button>';
                            } else {
                                this.keepDOM[element.id].del_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-danger btn-delete">削除</button>';
                                this.keepDOM[element.id].del_btn.addEventListener('click', function(e) {
                                    if (e.target.dataset.id) {
                                        var data = JSON.parse(e.target.dataset.id);
                                        _this.del(data);
                                    }
                                });
                            }
                        });
                        this.wjFileGrid.refresh();
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
            params.append('screen', 'supplier-file');
            params.append('keys', this.rmUndefinedBlank(this.supplierdata.id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    // 編集中状態へ
                    this.isLocked = false
                    this.isReadOnly = false
                    this.isShowEditBtn = false
                    this.lockdata = response.data.lockdata
                    this.filelist.forEach(element => {         
                        // 削除ボタン
                        var _this = this;
                        if (this.isReadOnly) {
                            this.keepDOM[element.id].del_btn.innerHTML = '<button class="btn btn-danger btn-delete" disabled>削除</button>';
                        } else {
                            this.keepDOM[element.id].del_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-danger btn-delete">削除</button>';
                            this.keepDOM[element.id].del_btn.addEventListener('click', function(e) {
                                if (e.target.dataset.id) {
                                    var data = JSON.parse(e.target.dataset.id);
                                    _this.del(data);
                                }
                            });
                        }
                    });
                    this.wjFileGrid.refresh();
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
    padding: 25px;
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
.btn-view {
    background-color: #4d00bb !important;
    color: #ffffff;
    height: 23px !important;
    padding-top: 0px;
}

.btn-delete {
    height: 23px !important;
    padding-top: 0px;
}

</style>