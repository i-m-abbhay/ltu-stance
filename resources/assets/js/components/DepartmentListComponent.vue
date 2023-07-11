<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">部門名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="department_name"
                            :selected-index="-1"
                            :selected-value="searchParams.department_name"
                            :is-required="false"
                            :initialized="initDepartName"
                            :max-items="departmentlist.length"
                            :items-source="departmentlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">部門記号</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="department_symbol"
                            display-member-path="department_symbol"
                            selected-value-path="department_symbol"
                            :selected-index="-1"
                            :selected-value="searchParams.department_symbol"
                            :is-required="false"
                            :initialized="initDepartSymbol"
                            :max-items="departmentlist.length"
                            :items-source="departmentlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">拠点名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="base_name"
                            display-member-path="base_name"
                            selected-value-path="base_name"
                            :selected-index="-1"
                            :selected-value="searchParams.base_name"
                            :is-required="false"
                            :initialized="initBase"
                            :max-items="baselist.length"
                            :items-source="baselist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">部門長名</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="staff_name"
                            :selected-index="-1"
                            :selected-value="searchParams.staff_name"
                            :is-required="false"
                            :initialized="initStaff"
                            :max-items="stafflist.length"
                            :items-source="stafflist">
                        </wj-auto-complete>
                    </div>
                </div>
                <br>
                <!-- ボタン -->
                <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                    <button type="button" class="btn btn-primary btn-clear" @click="clearSearch">クリア</button>
                    <a type="button" class="btn btn-primary btn-new" href="/department-edit/new">新規作成</a>
                    <button type="submit" class="btn btn-primary btn-search">検索</button>
                </div>
                    
                <div class="clearfix"></div>       
            </form>
        </div>
        <br>
        <!-- 検索結果グリッド -->
        <div class="col-sm-12 result-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6">
                        <div class="input-group">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-search"></span>
                            </div>
                            <input v-model="filterText" @input="filter()" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <wj-multi-row
                    :itemsSource="departments"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row>
            </div>
        </div>
    </div>
</template>

<script>
/* TODO:app.jsで読み込んでいるので、出来るなら二重インポートしたくない */
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
        filterText: '',

        keepDOM: {},
        departments: new wjCore.CollectionView(),
        layoutDefinition: null,
        searchParams: {
            department_name: null,
            department_symbol: null,
            base_name: null,
            staff_name: null,
        },

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[  ]
            deny_resizing_col: [0, 6, 7],
            // 非表示[ ID ]
            invisible_col: [],
        },
        gridPKCol: 0,
        // 以下,initializedで紐づける変数
        wjDepartmentGrid: null,
        wjSearchObj: {
            department_name: {},
            department_symbol: {},
            base_name: {},
            staff_name: {},
        },
    }),
    props: {
        departmentlist: Array,
        baselist: Array,
        stafflist: Array,
        
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
    },
    methods: {
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('department_name', this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
            params.append('department_symbol', this.rmUndefinedBlank(this.wjSearchObj.department_symbol.text));
            params.append('base_name', this.rmUndefinedBlank(this.wjSearchObj.base_name.text));
            params.append('staff_name', this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));
           
            axios.post('/department-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'department_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_name.text));
                    this.urlparam += '&' + 'department_symbol=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department_symbol.text));
                    this.urlparam += '&' + 'base_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.base_name.text));
                    this.urlparam += '&' + 'staff_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff_name.text));

                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            detail_btn: document.createElement('a'),
                            edit_btn: document.createElement('a'),
                        }

                        // 詳細ボタン
                        this.keepDOM[element.id].detail_btn.innerHTML = "詳細"
                        this.keepDOM[element.id].detail_btn.href = '/department-edit/'+ element.id + this.urlparam;
                        this.keepDOM[element.id].detail_btn.classList.add('btn', 'bnt-sm', 'btn-detail', 'btn-department');

                        // 編集ボタン
                        this.keepDOM[element.id].edit_btn.innerHTML = "編集";
                        this.keepDOM[element.id].edit_btn.href = '/department-edit/'+ element.id + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].edit_btn.classList.add('btn', 'bnt-sm', 'btn-detail', 'btn-department');

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            department_name: element.department_name,
                            department_symbol: element.department_symbol,
                            base_name: element.base_name,
                            parent_dep: element.parent_dep,
                            staff_name: element.staff_name,
                        })
                    });
                    // データセット
                    this.wjDepartmentGrid.itemsSource = itemsSource;

                    this.filter();
                    this.tableData = dataCount;

                    // 設定更新
                    this.wjDepartmentGrid = this.applyGridSettings(this.wjDepartmentGrid);             
                    // 描画更新
                    this.wjDepartmentGrid.refresh();
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
                //  var item = this.wjArrivalGrid.rows[r];
                switch (c) {
                    case 0: // ID
                        cell.style.textAlign = 'center';
                        break;
                    case 1: // 部門名
                        cell.style.textAlign = 'left';
                        break;
                    case 2: // 部門記号
                        cell.style.textAlign = 'left';
                        break;
                    case 3: // 拠点名
                        cell.style.textAlign = 'left';
                        break;
                    case 4: // 親部門
                        cell.style.textAlign = 'left';
                        break;
                    case 5: // 部門長
                        cell.style.textAlign = 'left';
                        break;  
                    case 6: // 詳細
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].detail_btn);
                        break;
                    case 7: // 編集
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].edit_btn);
                        break;
                }
            }
        },
        getLayout() {
            return [
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: '部門ID', width: 80, isReadOnly: true },
                    ]
                },
                {
                    header: '部門', cells: [
                        { binding: 'department_name', header: '部門名', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                    ]
                },
                {
                    header: '部門記号', cells: [
                        { binding: 'department_symbol', header: '部門記号', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true},
                    ]
                },
                {
                    header: '所属拠点', cells: [
                        { binding: 'base_name', header: '所属拠点名', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },                        
                    ]
                },
                {
                    header: '親部門', cells: [
                        { binding: 'parent_dep', header: '親部門名', minWidth: GRID_COL_MIN_WIDTH, width: '210*', isReadOnly: true },                        
                    ]
                },
                {
                    header: '部門長', cells: [
                        { binding: 'staff_name', header: '部門長', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },                        
                    ]
                },
                {
                    header: '詳細', cells: [
                        { header: '詳細', minWidth: GRID_COL_MIN_WIDTH, width: 80, isReadOnly: true },                        
                    ]
                },
                {
                    header: '編集', cells: [
                        { header: '編集', minWidth: GRID_COL_MIN_WIDTH, width: 80 , isReadOnly: true},                        
                    ]
                },
                // 以下非表示
                // {
                //     header: 'ID', cells: [
                //         { binding: 'id', header: 'ID' },
                //     ]
                // },           
            ]
        },
        // フィルター
        filter() {
            var filter = this.filterText.toLowerCase();
            this.wjDepartmentGrid.collectionView.filter = dep => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (dep.id != null && (dep.id).toString().toLowerCase().indexOf(filter) > -1) ||
                    (dep.department_name != null && (dep.department_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (dep.department_symbol != null && (dep.department_symbol).toString().toLowerCase().indexOf(filter) > -1) ||
                    (dep.base_name != null && (dep.base_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (dep.parent_dep != null && (dep.parent_dep).toString().toLowerCase().indexOf(filter) > -1) ||
                    (dep.staff_name != null && (dep.staff_name).toString().toLowerCase().indexOf(filter) > -1)
                );
            };
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
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 35;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;

            this.wjDepartmentGrid = multirow;
        },
        /* 以下オートコンプリード設定 */
        initDepartName(sender) {
            this.wjSearchObj.department_name = sender
        },
        initDepartSymbol(sender) {
            this.wjSearchObj.department_symbol = sender
        },
        initBase(sender) {
            this.wjSearchObj.base_name = sender
        },
        initStaff(sender) {
            this.wjSearchObj.staff_name = sender
        },
    },

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
/* 検索結果 */
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-new {
    margin-top:7px;
}
.btn-search {
    width: 120px;
}

.btn-department {
    width: 100%;
    height: 100%;
    padding: 0px;
}
/* グリッド */
.wj-multirow {
    height: 500px;
    margin: 6px 0;
}

</style>