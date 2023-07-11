<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="col-md-12 col-sm-12 search-body">
                    <div class="row">
                        <div class="col-md-2">
                            <label class="control-label">ID</label>
                            <input type="text" class="form-control" v-model="searchParams.id">
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">中分類名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="class_middle_name"
                                display-member-path="class_middle_name" 
                                :items-source="classmiddlelist" 
                                selected-value-path="class_middle_name"
                                :selected-value="searchParams.class_middle_name"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initMiddle"
                                :selectedIndexChanged="changeIdxMiddle"
                                :max-items="classmiddlelist.length"
                            ></wj-auto-complete>
                        </div>
                        <div class="col-md-3">
                            <label class="control-label">大分類名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="class_big_name"
                                display-member-path="class_big_name" 
                                :items-source="classbiglist" 
                                selected-value-path="class_big_name"
                                :selected-value="searchParams.class_big_name"
                                :isDisabled="isReadOnly"
                                :isRequired=false
                                :initialized="initBig"
                                :max-items="classbiglist.length"
                            ></wj-auto-complete>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12" style="padding-top: 20px;">        
                            <div class="pull-right">                    
                                <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                                &emsp;
                                <a type="button" class="btn btn-search btn-md" href="/class-middle-edit/new">新規作成</a>
                                &emsp;
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
                <div id="wjMiddleGrid"></div>
            </div>
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
import { forEachRight } from 'lodash';

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        FLG_ON: 1,
        FLG_OFF: 0,
        tableData: 0,

        searchParams: {
            id: '',
            class_middle_name: '',
            class_big_name: '',
        },
        wjSearchObj: {
            class_middle_name: {},
            class_big_name: {},
        },

        layoutDefinition: null,
        keepDOM: {},
        urlparam: '',

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 0,

        wjMiddleGrid: null,
    }),
    props: {
        isEditable: Number,
        classmiddlelist: Array,
        classbiglist: Array,
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
            this.wjMiddleGrid = this.createGridCtrl('#wjMiddleGrid', new wjCore.CollectionView([]));
        });
    },
    methods:{
        changeIdxMiddle: function(sender){
            var tmpBig = this.classbiglist;
            if (sender.selectedValue) {
                var item = sender.selectedItem;
                tmpBig = [];
                for(var key in this.classbiglist) {
                    if (item.class_big_id == this.classbiglist[key].id) {
                        tmpBig.push(this.classbiglist[key]);
                    }
                }
            }
            this.wjSearchObj.class_big_name.itemsSource = tmpBig;
            this.wjSearchObj.class_big_name.selectedIndex = -1;
        },
        search() {
            this.loading = true;
            var params = new URLSearchParams();

            params.append('id', this.rmUndefinedBlank(this.searchParams.id));
            params.append('class_middle_name', this.rmUndefinedBlank(this.wjSearchObj.class_middle_name.text));
            params.append('class_big_name', this.rmUndefinedBlank(this.wjSearchObj.class_big_name.text));
            
            axios.post('/class-middle-list/search', params)
            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.id));
                    this.urlparam += '&' + 'class_middle_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.class_middle_name.text));
                    this.urlparam += '&' + 'class_big_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.class_big_name.text));

                    var itemsSource = [];
                    var dataLength = 0;

                    response.data.forEach(element => {
                        this.keepDOM[element.id] = {
                            edit_btn: document.createElement('a'),
                        }
                        // 詳細ボタン
                        this.keepDOM[element.id].edit_btn.innerHTML = "編集";
                        this.keepDOM[element.id].edit_btn.href = '/class-middle-edit/' + element.id + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].edit_btn.classList.add('btn', 'btn-md', 'btn-edit', 'edit-link');

                        itemsSource.push({
                            id: element.id,
                            class_middle_name: element.class_middle_name,
                            class_big_name: element.class_big_name,
                            class_big_id: element.class_big_id,
                            update_at: element.update_at,
                            created_at: element.created_at,
                        });
                        dataLength++
                    })
                    this.wjMiddleGrid.dispose();

                    this.wjMiddleGrid = this.createGridCtrl('#wjMiddleGrid', new wjCore.CollectionView(itemsSource));

                    // this.wjMiddleGrid.itemsSource = itemsSource;
                    this.tableData = dataLength;

                    // 描画更新
                    this.wjMiddleGrid.refresh();
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

                    }
                }

                if (panel.cellType == wjGrid.CellType.Cell) {
                    // 背景色デフォルト設定
                    cell.style.backgroundColor = '';
                    // ReadOnlyセルの背景色設定
                    // this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);                
                    // スタイルリセット
                    cell.style.color = '';
                    // cell.style.textAlign = '';

                    // 横スクロールで文字位置がおかしくなるのでtextArignは明示的に設定
                    var colName = this.wjMiddleGrid.getBindingColumn(panel, r, c).name;

                    var dataItem = panel.rows[r].dataItem;
                    if(dataItem !== undefined) {

                    }

                    switch (colName) {
                        case 'id':
                            cell.style.textAlign = 'right';
                            break;
                        case 'class_middle_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'class_big_name':
                            cell.style.textAlign = 'left';
                            break;
                        case 'created_at':
                            cell.style.textAlign = 'left';
                            break;
                        case 'update_at':
                            cell.style.textAlign = 'left';
                            break;
                        case 'btn':
                            cell.style.textAlign = 'center';
                            cell.appendChild(this.keepDOM[gridCtrl.getCellData(r, this.gridPKCol)].edit_btn);
                            break;
                    };
                }
            }.bind(this);
            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var col = this.wjMiddleGrid.getBindingColumn(e.panel, e.row, e.col);       // 取れるのは上段の列名
                // 編集行データ取得
                var row = s.collectionView.currentItem;

                switch (col.name) {

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

            return gridCtrl;
        },
        // レイアウト取得
        getLayout() {
            return [
                {
                    cells: [
                        { binding: 'id', name: 'id', header: 'ID', minWidth: GRID_COL_MIN_WIDTH, width: 130, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'class_middle_name', name: 'class_middle_name', header: '中分類名', minWidth: GRID_COL_MIN_WIDTH, width: 380, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'class_big_name', name: 'class_big_name', header: '大分類名', minWidth: GRID_COL_MIN_WIDTH, width: 380, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'created_at', name: 'created_at', header: '作成日時', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'update_at', name: 'update_at', header: '更新日時', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { name: 'btn', header: '操作', width: 150, isReadOnly: true, isRequired: false},
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

.edit-link {
    display: block !important;
    width: 100%;
    height: 23px;
    text-align: center;
    font-size: 12px;
    padding: 4px 4px;
}
</style>