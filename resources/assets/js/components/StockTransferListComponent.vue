<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-3 col-sm-3 col-xs-12">
                        <label class="control-label">移管元倉庫</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="warehouse_name"
                            :selected-index="-1"
                            :selected-value="searchParams.from_warehouse"
                            :is-required="false"
                            :initialized="initFromWarehouse"
                            :max-items="warehouselist.length"
                            :min-length="1"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-12">
                        <label class="control-label">移管先倉庫</label>
                        <wj-auto-complete class="form-control"
                            search-member-path="warehouse_name"
                            display-member-path="warehouse_name"
                            selected-value-path="warehouse_name"
                            :selected-index="-1"
                            :selected-value="searchParams.to_warehouse"
                            :is-required="false"
                            :initialized="initToWarehouse"
                            :max-items="warehouselist.length"
                            :min-length="1"
                            :items-source="warehouselist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2 col-xs-4">
                        <label>移管予定日</label>
                        <!-- <input type="text" class="form-control" /> -->
                        <wj-input-date class="form-control"
                            :value="searchParams.from_date"
                            :selected-value="searchParams.from_date"
                            :initialized="initFromDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-1 col-xs-1">
                        <label>&nbsp;</label>
                        <label class="help-block" style="text-align:center;">～</label>
                    </div>
                    <div class="col-sm-2 col-xs-4">
                        <label>&nbsp;</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.to_date"
                            :initialized="initToDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 col-sm-3 col-xs-12">
                        <label class="control-label">商品番号</label>
                        <input type="text" class="form-control" v-model="searchParams.product_code">
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-12">
                        <label class="control-label">商品名</label>
                        <input type="text" class="form-control" v-model="searchParams.product_name">
                    </div>
                    <div class="col-md-3 col-sm-3 col-xs-12">
                        <label class="control-label">型式／規格</label>
                        <input type="text" class="form-control" v-model="searchParams.model">
                    </div>
                    <div class="col-md-6 col-sm-12">
                        <label class="control-label">在庫種別</label>
                        <div class="col-md-12">
                            <el-checkbox class="col-md-3" v-model="checkList.order" :true-label="FLG_ON" :false-label="FLG_OFF">発注品</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkList.stock" :true-label="FLG_ON" :false-label="FLG_OFF">在庫品</el-checkbox>
                            <el-checkbox class="col-md-3" v-model="checkList.keep" :true-label="FLG_ON" :false-label="FLG_OFF">預かり品</el-checkbox>
                        </div>  
                    </div>
                </div>
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-primary btn-clear" @click="clearSearch">クリア</button>
                        <a type="button" class="btn btn-primary btn-new" href="/stock-transfer-edit/new">新規作成</a>
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
                    :itemsSource="moves"
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

        tableData: 0,
        urlparam: '',
        queryParam: '',
        lastSearchParam: null,
        filterText: '',

        keepDOM: {},
        moves: new wjCore.CollectionView(),
        layoutDefinition: null,

        checkList: {
            order: 1,
            stock: 1,
            keep: 1,
        },

        searchParams: {
            from_warehouse: null,
            to_warehouse: null,
            product_code: null,
            product_name: null,
            model: null,
            qr_code: null,
            from_date: null,
            to_date: null,
        },
        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ 状況, 削除, 編集, ID ]
            deny_resizing_col: [0, 9],
            // 非表示[ ID ]
            invisible_col: [10],
        },
        gridPKCol: 10,
        // 以下,initializedで紐づける変数
        wjMoveGrid: null,
        wjSearchObj: {
            from_warehouse: {},
            to_warehouse: {},
            product_code: {},
            product_name: {},
            model: {},
            qr_code: {},
            from_date: {},
            to_date: {},
        }
    }),
    props: {
        warehouselist: Array,
        // productlist: Array,
        qrcodelist: Array,
    },
    created: function() {
        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryParam = window.location.search;
        if (this.queryParam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryParam, this.searchParams);
            if (this.searchParams.from_date == "") { this.searchParams.from_date = null };
            if (this.searchParams.to_date == "") { this.searchParams.to_date = null };
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
         del: function (id) {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            this.loading = true
            
            var src;
            for (var i = 0; i < this.wjMoveGrid.rows.length; i++) {
                if (this.wjMoveGrid.itemsSource[i].id == id) {
                    src = this.wjMoveGrid.itemsSource[i];
                }
            }

            var params = new URLSearchParams();
            params.append('id', id);
            params.append('qr_code', src.qr_code);

            axios.post('/stock-transfer-list/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    var listUrl = '/stock-transfer-list/' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
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
                location.reload()
            }.bind(this))
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

            this.wjMoveGrid = multirow;
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('from_warehouse', this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.text));
            params.append('to_warehouse', this.rmUndefinedBlank(this.wjSearchObj.to_warehouse.text));
            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('model', this.rmUndefinedBlank(this.searchParams.model));
            params.append('qr_code', this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
            params.append('from_date', this.rmUndefinedBlank(this.wjSearchObj.from_date.text));
            params.append('to_date', this.rmUndefinedBlank(this.wjSearchObj.to_date.text));
            params.append('order', this.rmUndefinedZero(this.checkList.order));
            params.append('stock', this.rmUndefinedZero(this.checkList.stock));
            params.append('keep', this.rmUndefinedZero(this.checkList.keep));

            axios.post('/stock-transfer-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'from_warehouse=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_warehouse.text));
                    this.urlparam += '&' + 'to_warehouse=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_warehouse.text));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'model=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.model));
                    this.urlparam += '&' + 'qr_code=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
                    this.urlparam += '&' + 'from_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.from_date.text));
                    this.urlparam += '&' + 'to_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.to_date.text));
                    this.urlparam += '&' + 'order=' + encodeURIComponent(this.rmUndefinedZero(this.checkList.order));
                    this.urlparam += '&' + 'stock=' + encodeURIComponent(this.rmUndefinedZero(this.checkList.stock));
                    this.urlparam += '&' + 'keep=' + encodeURIComponent(this.rmUndefinedZero(this.checkList.keep));

                    // 検索条件保持
                    this.queryParam = params;

                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            del_btn: document.createElement('div'),
                            move_status: document.createElement('div'),
                        }
                        // 状況
                        this.keepDOM[element.id].move_status.innerHTML = element.move_status.text;
                        this.keepDOM[element.id].move_status.classList.add('status', element.move_status.class);

                        var _this = this;

                        // 削除ボタン
                        if (element.move_status.val === 0){
                            // 未処理
                            this.keepDOM[element.id].del_btn.innerHTML = '<button data-id=' + JSON.stringify(element.id) + ' class="btn btn-danger btn-delete">削除</button>';
                        } else {
                            // 移動中、移管済
                            this.keepDOM[element.id].del_btn.innerHTML = '<button disabled data-id=' + JSON.stringify(element.id) + ' class="btn btn-danger btn-delete">削除</button>';
                        }
                        this.keepDOM[element.id].del_btn.addEventListener('click', function(e) {
                            if (e.target.dataset.id)
                            var data = JSON.parse(e.target.dataset.id);
                            _this.del(data);
                        }) 

                        dataCount++;
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            id: element.id,
                            move_date: element.move_date,
                            from_warehouse_name: element.from_warehouse_name,
                            to_warehouse_name: element.to_warehouse_name,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            stock_status: element.stock_status,
                            qr_code: element.qr_code,
                            quantity: element.quantity,
                            move_status: element.move_status,

                        })
                    });
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
                    case 0: // 状況
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].move_status);
                        // cell.style.textAlign = 'center';
                        break;
                    case 1: // 予定日
                        cell.style.textAlign = 'left';
                        break;
                    case 2: // 移管元倉庫
                        cell.style.textAlign = 'left';
                        break;
                    case 3: // 移管先倉庫
                        cell.style.textAlign = 'left';
                        break;
                    case 4: // 在庫種別
                        cell.style.textAlign = 'center';
                        break;
                    case 5: // QR
                        cell.style.textAlign = 'left';
                        break;
                    case 6: // 商品番号
                        cell.style.textAlign = 'left';
                        break;
                    case 7: // 商品名
                        cell.style.textAlign = 'left';
                        break;
                    case 8: // 数量
                        cell.style.textAlign = 'right';
                        break;
                    case 9: // 削除
                        cell.appendChild(this.keepDOM[panel.getCellData(r, this.gridPKCol)].del_btn);
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
            var searchParams = this.searchParams;
            Object.keys(searchParams).forEach(function (key) {
                searchParams[key] = '';
            });

            this.checkList.order = 0;
            this.checkList.stock = 0;
            this.checkList.keep = 0;
        },
        getLayout() {
            return [
                {
                    header: '状況', cells: [
                        { header: '状況', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },
                    ]
                },
                {
                    header: '移管予定日', cells: [
                        { binding: 'move_date', header: '移管予定日', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true },
                    ]
                },
                {
                    header: '移管元倉庫', cells: [
                        { binding: 'from_warehouse_name', header: '移管元倉庫', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true },
                    ]
                },
                {
                    header: '移管先倉庫', cells: [
                        { binding: 'to_warehouse_name', header: '移管先倉庫', minWidth: GRID_COL_MIN_WIDTH, width: 160, isReadOnly: true },
                    ]
                },
                {
                    header: '在庫種別', cells: [
                        { binding: 'stock_status', header: '在庫種別', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },
                    ]
                },
                {
                    header: 'QR管理番号', cells: [
                        { binding: 'qr_code', header: 'QR管理番号', minWidth: GRID_COL_MIN_WIDTH, width: 140, isReadOnly: true },
                    ]
                },
                {
                    header: '商品番号', cells: [
                        { binding: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 190, isReadOnly: true },
                    ]
                },
                {
                    header: '商品名', cells: [
                        { binding: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 190, isReadOnly: true },
                    ]
                },
                {
                    header: '数量', cells: [
                        { binding: 'quantity', header: '数量', minWidth: GRID_COL_MIN_WIDTH, width: 90 , isReadOnly: true},
                    ]
                },
                {
                    header: '削除', cells: [
                        { header: '削除', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },
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
            grid.allowMerging = wjGrid.AllowMerging.All;
            
            return grid;
        },        
        // filter() {
        //     var filter = this.filterText.toLowerCase();
        //     this.wjMoveGrid.collectionView.filter = move => {
        //         return (
        //             // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
        //             filter.length == 0 ||
        //             (item.item_name != null && (item.item_name).toString().toLowerCase().indexOf(filter) > -1) ||
        //             (item.item_front_label != null && (item.item_front_label).toString().toLowerCase().indexOf(filter) > -1) ||
        //             (item.item_back_label != null && (item.item_back_label).toString().toLowerCase().indexOf(filter) > -1) ||
        //             (item.value_text_1 != null && (item.value_text_1).toString().toLowerCase().indexOf(filter) > -1)
        //         );
        //     };
        // },
        /* 以下オートコンプリード設定 */
        initFromWarehouse(sender) {
            this.wjSearchObj.from_warehouse = sender
        },
        initToWarehouse(sender) {
            this.wjSearchObj.to_warehouse = sender
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
        initQrCode(sender) {
            this.wjSearchObj.qr_code = sender
        },
        initFromDate(sender) {
            this.wjSearchObj.from_date = sender
        },
        initToDate(sender) {
            this.wjSearchObj.to_date = sender
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
.status {
    display: block !important;
    width: 100%;
    height: 22px;
    text-align: center;
    padding: 0px 0px !important;
}
.not-move{
    background: #CB2E25;
    color: #fff;
}
.moving{
    background: #FFCC00;
    color: #fff;
}
.moved{
    background: #5AC8FA;
    color: #fff;
}
.btn-new {
    margin-top: 7px;
}
.btn-search {
    width: 120px;
}
.btn-delete {
    width: 100%;
    height: 22px;
    font-size: 12px;
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
