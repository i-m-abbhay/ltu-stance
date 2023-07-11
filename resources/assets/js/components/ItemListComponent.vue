<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-4 col-sm-4">
                        <label class="control-label">項目名</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="item_name"
                            display-member-path="item_name"
                            selected-value-path="item_name"
                            :selected-index="-1"
                            :selected-value="searchParams.item_name"
                            :is-required="false"
                            :initialized="initItem"
                            :max-items="itemlist.length"
                            :min-length="1"
                            :items-source="itemlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-8 col-sm-8">
                        <label class="control-label">種別</label>
                        <el-checkbox-group v-model="selectedType">
                            <el-checkbox v-for="row in typelist" :label="row.value_code" :key="row.value_code">{{ row.value_text_1 }}</el-checkbox>
                        </el-checkbox-group>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-primary btn-clear" @click="clearSearch">クリア</button>
                        <a type="button" class="btn btn-primary btn-new" href="/item-edit/new">新規作成</a>
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
                    <div class="col-md-6 col-sm-6 col-xs-6">
                        <div class="input-group">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-search"></span>
                            </div>
                            <input v-model="filterText" @input="filter()" class="form-control">
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6 col-xs-6">
                        <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <wj-multi-row
                    :itemsSource="item"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row>
            </div>
        </div>
    </div>
</template>

<script>
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';

export default {
    data: () => ({
        loading: false,
        FLG_ON : 1,
        FLG_OFF: 0,

        tableData: 0,
        urlparam: '',
        queryParam: '',
        lastSearchParam: null,
        filterText: '',

        keepDOM: {},
        item: new wjCore.CollectionView(),
        layoutDefinition: null,

        searchParams: {
            item_name: null,
            text: null,
            textarea: null,
            selectbox: null,
            checkbox: null,
            radio: null,
            number: null,
            date: null,
        },
        selectedType: [],
        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ 編集 ]
            deny_resizing_col: [4, 5],
            // 非表示[ ID ]
            invisible_col: [5],
        },
        gridPKCol: 5,
        // 以下,initializedで紐づける変数
        wjItemGrid: null,
        wjSearchObj: {
            item_name: {},
            item_type: {},
        },
    }),
    props: {
        itemlist: Array,
        typelist: Array,
    },
    created: function() {
        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);

            var arr = [];
            Object.keys(this.searchParams).forEach(key => {
                if (key != 'item_name' && this.rmUndefinedBlank(this.searchParams[key]) != '') {
                    arr.push(parseInt(this.searchParams[key]));
                }
            })
            this.selectedType = arr;
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
    },
    methods: {        
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 35;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;

            this.wjItemGrid = multirow;
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('item_name', this.rmUndefinedBlank(this.wjSearchObj.item_name.text));
            params.append('item_type', this.rmUndefinedBlank(this.selectedType));

            axios.post('/item-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'item_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.item_name.text));
                    this.selectedType.forEach((val, i) => {
                        switch(val) {
                            case 1: // テキスト
                                this.urlparam += '&' + 'text=' + encodeURIComponent(val);
                                break;
                            case 2: // テキストエリア
                                this.urlparam += '&' + 'textarea=' + encodeURIComponent(val);
                                break;
                            case 3: // セレクトボックス
                                this.urlparam += '&' + 'selectbox=' + encodeURIComponent(val);
                                break;
                            case 4: // チェックボックス
                                this.urlparam += '&' + 'checkbox=' + encodeURIComponent(val);
                                break;
                            case 5: // ラジオボタン
                                this.urlparam += '&' + 'radio=' + encodeURIComponent(val);
                                break;
                            case 6: // 数値
                                this.urlparam += '&' + 'number=' + encodeURIComponent(val);
                                break;
                            case 7: // 日付
                                this.urlparam += '&' + 'date=' + encodeURIComponent(val);
                                break;                            
                        }
                    })
                    // 検索条件保持
                    this.queryParam = params;

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
                        this.keepDOM[element.id].edit_btn.href = '/item-edit/'+ element.id + this.urlparam;
                        this.keepDOM[element.id].edit_btn.classList.add('btn', 'bnt-sm', 'btn-edit', 'btn-item');

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            item_name: element.item_name,
                            value_text_1: element.value_text_1,
                            item_front_label: element.item_front_label,
                            item_back_label: element.item_back_label,
                        })
                    });
                    // データセット
                    this.wjItemGrid.itemsSource = itemsSource;

                    this.filter();
                    this.tableData = dataCount;

                    // 設定更新
                    this.wjItemGrid = this.applyGridSettings(this.wjItemGrid);
                    // 描画更新
                    this.wjItemGrid.refresh();
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
        itemFormatter: function (panel, r, c, cell) {
            if (this.keepDOM === undefined || this.keepDOM === null) {
                return;
            }

            // 列ヘッダのセンタリング
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                cell.style.textAlign = 'center';
            }

            if (panel.cellType == wjGrid.CellType.Cell) {
                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                //  var item = this.wjChoiceGrid.rows[r];
                switch (c) {
                    case 0: // 項目名
                        cell.style.textAlign = 'left';
                        break;
                    case 1: // 前ラベル
                        cell.style.textAlign = 'left';
                        break;
                    case 2: // 後ラベル
                        cell.style.textAlign = 'left';
                        break;
                    case 3: // 種別
                        cell.style.textAlign = 'left';
                        break;
                    case 4: // 編集
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].edit_btn);
                        break;
                }
            }
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
            this.selectedType = [];
        },
        getLayout() {
            return [
                {
                    header: '項目名', cells: [
                        { binding: 'item_name',  header: '項目名', minWidth: GRID_COL_MIN_WIDTH, width: 300, isReadOnly: true },
                    ]
                },
                {
                    header: '前ラベル', cells: [
                        { binding: 'item_front_label', header: '前ラベル', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },
                    ]
                },
                {
                    header: '後ラベル', cells: [
                        { binding: 'item_back_label', header: '後ラベル', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },
                    ]
                },
                {
                    header: '種別', cells: [
                        { binding: 'value_text_1', header: '種別', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true },
                    ]
                },
                {
                    header: '編集', cells: [
                        { header: '編集', minWidth: GRID_COL_MIN_WIDTH, width: 100 , isReadOnly: true},
                    ]
                },
                // 以下非表示
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID' },
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
            
            return grid;
        },        
        filter() {
            var filter = this.filterText.toLowerCase();
            this.wjItemGrid.collectionView.filter = item => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (item.item_name != null && (item.item_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.item_front_label != null && (item.item_front_label).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.item_back_label != null && (item.item_back_label).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.value_text_1 != null && (item.value_text_1).toString().toLowerCase().indexOf(filter) > -1)
                );
            };
        },
        /* 以下オートコンプリード設定 */
        initItem(sender) {
            this.wjSearchObj.item_name = sender
        },
    }
};

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
.btn-new {
    margin-top: 7px;
}
.btn-search {
    width: 120px;
}
.btn-item {
    width: 100%;
    height: 25px;
    font-size: 12px;
    padding-top: 3px;
}

.container-fluid .wj-multirow {
    height: 500px;
    margin: 6px 0;
}
.container-fluid  .wj-detail{
    padding-left: 225px !important;
}

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
    text-align: center !important;
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
