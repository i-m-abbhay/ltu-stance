<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">分類名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="category_name"
                            display-member-path="category_name"
                            selected-value-path="category_name"
                            :selected-index="-1"
                            :selected-value="searchParams.category_name"
                            :is-required="false"
                            :initialized="initCategory"
                            :max-items="categorynamelist.length"
                            :min-length="1"
                            :items-source="categorynamelist">
                        </wj-auto-complete>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-primary btn-clear" @click="clearSearch">クリア</button>
                        <button type="submit" class="btn btn-primary btn-search">検索</button>
                    </div>
                    <div class="clearfix"></div>  
                </div>
            </form>
        </div>
        <br>
        <!-- 検索結果グリッド -->
        <div class="col-md-12 col-sm-12 col-xs-12 result-body">
            <div class="container-fluid">
                <div class="row">
                    <!-- <div class="col-md-6 col-sm-6 col-xs-6">
                        <div class="input-group">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-search"></span>
                            </div>
                            <input v-model="filterText" @input="filter()" class="form-control">
                        </div>
                    </div> -->
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <div id="wjGeneralGrid"></div>
                <!-- <wj-multi-row
                    :itemsSource="choices"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row> -->
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
        isLocked: false,
        isShowEditBtn: true,
        isReadOnly: true,
        selectedGrid: false,

        FLG_ON : 1,
        FLG_OFF: 0,

        queryParam: '',
        urlparam: '',

        tableData: 0,

        searchParams: {
            category_name: '',
        },

        // データ
        wjSearchObj: {
            category_name: {},
        },
        
        keepDOM: {},
        products: new wjCore.CollectionView(),
        
        layoutDefinition: null,

        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 3,

        // 以下,initializedで紐づける変数
        wjGeneralGrid: null,
    }),
    props: {
        categorynamelist: Array,
    },
    created: function() {
        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);
        }else{
            // 初回の検索条件をセット
            // this.setInitSearchParams(this.searchParams, this.initsearchparams);
        }
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        if (this.queryParam.length > 1) {
            this.search();
        }

        // グリッド初期表示
        var targetDiv = '#wjGeneralGrid';
        var itemsSource = [];

        this.$nextTick(function() {
            var _this = this;
            var gridItemSource = new wjCore.CollectionView(itemsSource);
            // gridItemSource.refresh();
            this.wjGeneralGrid = this.createGrid(targetDiv, gridItemSource);
            this.applyGridSettings(this.wjGeneralGrid);
        });

    },
    methods: {
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('category_name', this.rmUndefinedBlank(this.wjSearchObj.category_name.text));

            axios.post('/general-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'category_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.category_name.text));

                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            edit_btn: document.createElement('a'),
                        }

                        // 編集ボタン
                        this.keepDOM[element.id].edit_btn.innerHTML = "編集";
                        this.keepDOM[element.id].edit_btn.href = '/general-edit/'+ element.category_code + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].edit_btn.classList.add('btn', 'bnt-sm', 'btn-edit');

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            category_name: element.category_name,
                            category_code: element.category_code,
                        })
                    });
                    // データセット
                    this.wjGeneralGrid.itemsSource = itemsSource;

                    this.tableData = dataCount;

                    // 設定更新
                    this.wjGeneralGrid = this.applyGridSettings(this.wjGeneralGrid);
                    // 描画更新
                    this.wjGeneralGrid.refresh();
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
        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clearSearch: function() {
            // this.searchParams = this.initParams;
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
                wjSearchObj[key].text = null;
            });
        },
        // グリッド生成
        createGrid(divId, gridItems) {
            var gridCtrl = new wjMultiRow.MultiRow(divId, {
                itemsSource: gridItems,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowAddNew: false,
                allowDelete: false,
                allowSorting: true,
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
                    this.setGridCellReadOnlyColor(gridCtrl, r, c, cell);
                    var col = panel.columns[c];
                    switch(col.name) {
                        case 'category_name':
                            cell.style.textAlign = 'left'
                            break;
                        case 'edit_btn':
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].edit_btn);
                            break;
                    }


                }
            }.bind(this);

            return gridCtrl;
        },

        getLayout() {
            return [
                {
                    header: '分類名', cells: [
                        { binding: 'category_name', name: 'category_name', header: '分類名', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },  
                    ]
                },
                {
                    header: '編集', cells: [
                        { name: 'edit_btn', header: '編集', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },  
                    ]
                },
                /* 非表示 */
                {
                    header: 'category_code', cells: [
                        { binding: 'category_code', name: 'category_code', header: 'category_code', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
                    ]
                },  
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', maxWidth: 0, width: 0, isReadOnly: true, isRequired: false },
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
        /* 以下オートコンプリード設定 */
        initCategory(sender) {
            this.wjSearchObj.category_name = sender
        },
    }
}
</script>

<style>
/*********************************
    枠サイズ等
**********************************/
/* 検索項目 */
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
.btn-save {
    width: 80px;
}

/* グリッド */
.wj-multirow {
    height: 400px;
    margin: 6px 0;
}
.btn {
    width: 80px;
    height: 34.2px;
}
.btn-edit {
    width: 100%;
    height: 25px;
    padding-top: 5px;
}

</style>