<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-4 col-sm-4">
                        <label class="control-label">仕入先名</label>
                        <wj-auto-complete class="form-control" 
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="supplier_name"
                            :selected-index="-1"
                            :selected-value="searchParams.supplier_name"
                            :is-required="false"
                            :initialized="initName"
                            :max-items="supplierlist.length"
                            :items-source="supplierlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-4 col-sm-4">
                        <label class="control-label">メーカー名</label>
                        <wj-auto-complete class="form-control" 
                            search-member-path="supplier_name"
                            display-member-path="supplier_name"
                            selected-value-path="supplier_name"
                            :selected-index="-1"
                            :selected-value="searchParams.maker_name"
                            :is-required="false"
                            :initialized="initMaker"
                            :max-items="makerlist.length"
                            :items-source="makerlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">法人番号</label>
                        <wj-auto-complete class="form-control" 
                            search-member-path="corporate_number"
                            display-member-path="corporate_number"
                            selected-value-path="corporate_number"
                            :selected-index="-1"
                            :selected-value="searchParams.corporate_number"
                            :is-required="false"
                            :initialized="initNumber"
                            :max-items="supplierlist.length"
                            :items-source="supplierlist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">取扱品目</label>
                        <wj-auto-complete class="form-control" 
                            search-member-path="class_big_name"
                            display-member-path="class_big_name"
                            selected-value-path="class_big_name"
                            :selected-index="-1"
                            :selected-value="searchParams.product_line"
                            :is-required="false"
                            :initialized="initProductLine"
                            :max-items="biglist.length"
                            :items-source="biglist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">施工業者区分</label>
                        <wj-auto-complete class="form-control" 
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="construction_name"
                            :selected-index="-1"
                            :selected-value="searchParams.construction_name"
                            :is-required="false"
                            :initialized="initConstruct"
                            :max-items="constlist.length"
                            :items-source="constlist">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-4 col-md-3">
                        <label>登録日</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.from_created_date"
                            :selected-value="searchParams.from_created_date"
                            :initialized="initFromDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-1">
                        <label>&nbsp;</label>
                        <label class="help-block" style="text-align:center;">～</label>
                    </div>
                    <div class="col-sm-4 col-md-3">
                        <label>&nbsp;</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.to_created_date"
                            :selected-value="searchParams.to_created_date"
                            :initialized="initToDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>  
                    <div class="col-md-6 col-sm-12">
                        <label class="control-label">区分</label>
                        <div class="col-md-12">
                            <el-checkbox class="col-md-4" v-model="checkList.direct" :true-label="FLG_ON" :false-label="FLG_OFF">メーカー直接取引</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkList.supplier" :true-label="FLG_ON" :false-label="FLG_OFF">仕入先</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkList.maker" :true-label="FLG_ON" :false-label="FLG_OFF">メーカー</el-checkbox>
                        </div>  
                    </div>
                </div>
                <br>
                <!-- ボタン -->
                <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                    <button type="button" class="btn btn-primary btn-clear" @click="clearSearch">クリア</button>
                    <a type="button" class="btn btn-primary btn-new" href="/supplier-edit/new">新規作成</a>
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
                    <div class="col-sm-4">
                        <div class="input-group">
                            <div class="input-group-addon">
                                <span class="glyphicon glyphicon-search"></span>
                            </div>
                            <input v-model="filterText" @input="filter()" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-8">
                        <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <wj-multi-row
                    :itemsSource="suppliers"
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

        // FLG_ONをセットするとデフォルトでチェックが入らない
        checkList: {
            direct: 1,
            supplier: 1,
            maker: 1
        },

        searchParams: {
            supplier_name: '',
            maker_name: '',
            corporate_number: '',
            product_line: '',
            construction_name: '',
            from_created_date: null,
            to_created_date: null,
        },

        wjSearchObj: {
            supplier_name: {},
            maker_name: {},
            corporate_number: {},
            product_line: {},
            construction_name: {},
            from_created_date: {},
            to_created_date: {},
        },

        keepDOM: {},
        suppliers: new wjCore.CollectionView(),
        layoutDefinition: null,

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ ID, 詳細, 編集 ]
            deny_resizing_col: [0, 6, 7],
            // 非表示[  ]
            invisible_col: [],
        },
        gridPKCol: 0,
        // 以下,initializedで紐づける変数
        wjSupplierGrid: null,
    }),
    props: {
        supplierlist: Array,
        makerlist: Array,
        biglist: Array,
        constlist: Array,
    },
    created: function() {
        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.from_created_date == "") { this.searchParams.from_created_date = null };
            if (this.searchParams.to_created_date == "") { this.searchParams.to_created_date = null };

            if (this.queryParam.indexOf('direct=1') > -1) {
                this.checkList.direct = 1;
            } else {
                this.checkList.direct = 0;
            }
            if (this.queryParam.indexOf('supplier=1') > -1) {
                this.checkList.supplier = 1;
            } else {
                this.checkList.supplier = 0;
            }
            if (this.queryParam.indexOf('maker=1') > -1) {
                this.checkList.maker = 1;
            } else {
                this.checkList.maker = 0;
            }
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
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('maker_name', this.rmUndefinedBlank(this.wjSearchObj.maker_name.text));
            params.append('corporate_number', this.rmUndefinedBlank(this.wjSearchObj.corporate_number.text));
            params.append('product_line', this.rmUndefinedBlank(this.wjSearchObj.product_line.text));
            params.append('construction_name', this.rmUndefinedBlank(this.wjSearchObj.construction_name.text));
            params.append('from_created_date', this.rmUndefinedBlank(this.wjSearchObj.from_created_date.text));
            params.append('to_created_date', this.rmUndefinedBlank(this.wjSearchObj.to_created_date.text));
            params.append('direct_flg', this.rmUndefinedZero(this.checkList.direct));
            params.append('supplier_flg', this.rmUndefinedZero(this.checkList.supplier));
            params.append('maker_flg', this.rmUndefinedZero(this.checkList.maker));
           
            axios.post('/supplier-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                    this.urlparam += '&' + 'maker_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.maker_name.text));
                    this.urlparam += '&' + 'corporate_number=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.corporate_number.text));
                    this.urlparam += '&' + 'product_line=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product_line.text));
                    this.urlparam += '&' + 'construction_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.construction_name.text));
                    this.urlparam += '&' + 'from_created_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_created_date.text));
                    this.urlparam += '&' + 'to_created_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_created_date.text));
                    this.urlparam += '&' + 'direct=' + encodeURIComponent(this.rmUndefinedBlank(this.checkList.direct));
                    this.urlparam += '&' + 'supplier=' + encodeURIComponent(this.rmUndefinedBlank(this.checkList.supplier));
                    this.urlparam += '&' + 'maker=' + encodeURIComponent(this.rmUndefinedBlank(this.checkList.maker));
                    
                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            detail_btn: document.createElement('a'),
                            edit_btn: document.createElement('a'),
                            file_btn: document.createElement('a'),
                            contrast_btn: document.createElement('a'),
                        }
                        // 仕入先メーカー対照マスタボタン
                        this.keepDOM[element.id].contrast_btn.innerHTML = "\n仕入先設定"
                        this.keepDOM[element.id].contrast_btn.href = '/supplier-maker-contrast/'+ element.id + this.urlparam;
                        this.keepDOM[element.id].contrast_btn.classList.add('btn', 'bnt-sm', 'btn-detail', 'btn-contrast');

                        // 詳細ボタン
                        this.keepDOM[element.id].detail_btn.innerHTML = "詳細"
                        this.keepDOM[element.id].detail_btn.href = '/supplier-edit/'+ element.id + this.urlparam;
                        this.keepDOM[element.id].detail_btn.classList.add('btn', 'bnt-sm', 'btn-detail', 'btn-supplier');

                        // 編集ボタン
                        this.keepDOM[element.id].edit_btn.innerHTML = "編集";
                        this.keepDOM[element.id].edit_btn.href = '/supplier-edit/'+ element.id + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].edit_btn.classList.add('btn', 'bnt-sm', 'btn-detail', 'btn-supplier');

                        // 価格ファイルボタン
                        this.keepDOM[element.id].file_btn.innerHTML = "価格ファイル";
                        this.keepDOM[element.id].file_btn.href = '/supplier-file/'+ element.id + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].file_btn.classList.add('btn', 'bnt-sm', 'btn-detail', 'btn-supplier');

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            supplier_name: element.supplier_name,
                            supplier_kana: element.supplier_kana,
                            supplier_maker_kbn: element.supplier_maker_kbn,
                            address: element.address,
                            tel: element.tel,
                            fax: element.fax,
                            contractor_kbn: element.contractor_kbn,
                            product_line: element.product_line,
                        })
                    });
                    // データセット
                    this.wjSupplierGrid.itemsSource = itemsSource;

                    // this.filter();
                    this.tableData = dataCount;

                    // 設定更新
                    this.wjSupplierGrid = this.applyGridSettings(this.wjSupplierGrid);             
                    // 描画更新
                    this.wjSupplierGrid.refresh();
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
                 var item = this.wjSupplierGrid.rows[r];
                switch (c) {
                    case 0: // ID
                        cell.style.textAlign = 'center';
                        break;
                    case 1: // 仕入先名
                        cell.style.textAlign = 'left';
                        break;
                    case 2: // 区分
                        cell.style.textAlign = 'center';
                        if (item.dataItem.supplier_maker_kbn.indexOf('メーカー') > -1) {
                            cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].contrast_btn);
                        }
                        break;
                    case 3: // 取扱い品目
                        cell.style.textAlign = 'left';
                        break;
                    case 4: // 住所
                        cell.style.textAlign = 'left';
                        break;
                    case 5: // TEL FAX
                        cell.style.textAlign = 'left';
                        break;  
                    case 6: // 詳細
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].detail_btn);
                        break;
                    case 7: // 編集
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].edit_btn);
                        break;
                    case 8: // 価格ファイル
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].file_btn);
                        break;
                }
            }
        },
        getLayout() {
            return [
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 80, isReadOnly: true },
                    ]
                },   
                {
                    header: '仕入先名', cells: [
                        { binding: 'supplier_name', header: '仕入先名', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },
                        { binding: 'supplier_kana', header: '仕入先名カナ', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },  
                    ]
                },
                {
                    header: '仕入先メーカー区分', cells: [
                        { binding: 'supplier_maker_kbn', header: '仕入先メーカー区分', wordWrap: true, minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },                   
                    ]
                },
                {
                    header: '取扱い品目', cells: [
                        { binding: 'product_line', header: '取扱い品目', wordWrap: true, minWidth: GRID_COL_MIN_WIDTH, width: 230 , isReadOnly: true},  
                        { binding: 'contractor_kbn', header: '施工業者区分', wordWrap: true, minWidth: GRID_COL_MIN_WIDTH, width: 230, isReadOnly: true },                         
                    ]
                },
                {
                    header: '住所', cells: [
                        { binding: 'address', header: '住所', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },                        
                    ]
                },
                {
                    header: 'TEL', cells: [
                        { binding: 'tel', header: 'TEL', minWidth: GRID_COL_MIN_WIDTH, width: 150 , isReadOnly: true},
                        { binding: 'fax', header: 'FAX', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },                    
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
                {
                    header: '価格ファイル', cells: [
                        { header: '価格ファイル', minWidth: GRID_COL_MIN_WIDTH, width: 120 , isReadOnly: true},                        
                    ]
                },    
            ]
        },
        // フィルター
        filter() {
            var filter = this.filterText.toLowerCase();
            this.wjSupplierGrid.collectionView.filter = sup => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (sup.id != null && (sup.id).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.supplier_name != null && (sup.supplier_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.supplier_kana != null && (sup.supplier_kana).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.supplier_maker_kbn != null && (sup.supplier_maker_kbn).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.product_line != null && (sup.product_line).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.address != null && (sup.address).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.tel != null && (sup.tel).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.fax != null && (sup.fax).toString().toLowerCase().indexOf(filter) > -1) ||
                    (sup.construction_name != null && (sup.construction_name).toString().toLowerCase().indexOf(filter) > -1)
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
            var params = {
                supplier_name: '',
                maker_name: '',
                corporate_number: '',
                product_line: '',
                construction_name: '',
                from_created_date: null,
                to_created_date: null,
            };
            this.searchParams = params;
            var check = {
            direct: 0,
            supplier: 0,
            maker: 0
            };
            this.checkList = check;
        },
        initMultiRow: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;
            // 高さ自動調整
            multirow.autoRowHeights = true,

            this.wjSupplierGrid = multirow;
        },
        /* 以下オートコンプリード設定 */
        initName(sender) {
            this.wjSearchObj.supplier_name = sender
        },
        initMaker(sender) {
            this.wjSearchObj.maker_name = sender
        },
        initNumber(sender) {
            this.wjSearchObj.corporate_number = sender
        },
        initProductLine(sender) {
            this.wjSearchObj.product_line = sender
        },
        initConstruct(sender) {
            this.wjSearchObj.construction_name = sender
        },
        initFromDate(sender) {
            this.wjSearchObj.from_created_date = sender
        },
        initToDate(sender) {
            this.wjSearchObj.to_created_date = sender
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
    margin-top: 7px;
}
.btn-search {
    width: 120px;
}
.btn-supplier {
    width: 100%;
}
.btn-contrast {
    width: 100%;
    font-size: 12px;
}
.el-checkbox {
    padding-top: 10px;
}
/* グリッド */
.wj-multirow {
    height: 500px;
    margin: 6px 0;
}

</style>