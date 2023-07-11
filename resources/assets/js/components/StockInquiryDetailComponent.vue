<template>
    <div>
        <loading-component :loading="loading" />
        <div class="col-md-12 col-sm-12 col-xs-12">
            <button type="button" class="btn btn-back btn-sm form-control" @click="backToList">戻る</button>
        </div>
        <div class="main-body col-sm-12 col-md-12 col-xs-12">
            <!-- 商品情報 -->
            <div class="col-sm-12 col-md-12 col-xs-12">
                <p>倉庫名</p>
                <p class="read-text col-sm-12 col-md-12 col-xs-12"><u>{{ mainList.warehouse_name }}</u></p>
            </div>
            <div class="col-sm-12 col-md-12 col-xs-12">
                <p>商品名</p>
                <p class="read-text col-sm-12 col-md-12 col-xs-12"><u>{{ mainList.product_name }}</u></p>
            </div>
            <div v-show="this.rmUndefinedBlank(mainList.image) != ''" class="col-sm-12 col-md-12 col-xs-12">
                <p>商品画像</p>
                <div class="col-md-8">
                    <img class="image-preview" v-show="mainList.image" :src="mainList.image">
                </div>
            </div>
            <div class="col-sm-12 col-md-12 col-xs-12">
                <p>商品番号</p>
                <p class="read-text col-sm-12 col-md-12 col-xs-12"><u>{{ mainList.product_code }}</u></p>
            </div>
            <div class="col-sm-12 col-md-12 col-xs-12">
                <p>型式／規格</p>
                <p class="read-text col-sm-12 col-md-12 col-xs-12"><u>{{ mainList.model }}</u></p>
            </div>
            <div class="col-sm-12 col-md-12 col-xs-12">
                <p>メーカー名</p>
                <p class="read-text col-sm-12 col-md-12 col-xs-12"><u>{{ mainList.supplier_name }}</u></p>
            </div>
            <!-- 在庫系 -->
            <div class="col-md-12">
                <div class="quantity-block">
                    <div class="quantity-header col-sm-12 col-md-12 col-xs-12">
                        <p class="col-sm-3 col-md-3 col-xs-3">実在庫</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">有効在庫</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">引当</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">入荷予定</p>
                    </div>
                </div>
                <div class="quantity-block">
                    <div class="quantity-body col-sm-12 col-md-12 col-xs-12">
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ mainList.actual_quantity }}</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ mainList.active_quantity }}</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ mainList.reserve_quantity }}</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ mainList.arrival_quantity }}</p>
                    </div>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-3 col-md-3 col-xs-3">
                        <p>次回入荷日</p>
                    </div>
                    <p class="quantity-body col-sm-3 col-md-3 col-xs-3">{{ mainList.next_arrival_date }}</p>
                    <div class="quantity-header col-sm-3 col-md-3 col-xs-3">
                        <p>最終入荷日</p>
                    </div>
                    <p class="quantity-body col-sm-3 col-md-3 col-xs-3">{{ mainList.last_arrival_date }}</p>
                </div>
            </div>
        </div>

        <div class="main-body col-sm-12 col-md-12 col-xs-12">
            <h3><u>引当状況</u></h3>
            <div class="container-fluid">
                <div class="col-sm-12">
                    <p class="search-count" style="text-align:right;">引当件数：{{ reserveData.length }}件</p>
                </div>
                <wj-multi-row
                    class="multirow-reserve"
                    :itemsSource="reserveCollection"
                    :layoutDefinition="layoutDefinition"
                    :initialized="initMultiRow"
                    :itemFormatter="itemFormatter"
                ></wj-multi-row>
            </div>
        </div>
        <div class="main-body col-sm-12 col-md-12 col-xs-12">
            <h3><u>QR情報</u></h3>
            <!-- v-forで件数分 -->
            <div v-for="(qr, index) in qrList" :key="qr.qr_id">
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>得意先名</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ qr.customer_name }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>案件名</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ qr.matter_name }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>棚</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ qr.shelf_area }}{{ qr.shelf_steps }}段目</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>QR管理番号</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ qr.qr_code }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>数量</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ qr.quantity }}</span>
                </div>
                <button type="button" class="btn btn-print btn-sm form-control" @click="qrPrint(index)">印刷</button>
            </div>
        </div>
        <br />
        <div class="col-md-12 col-sm-12 col-xs-12">
        <button type="button" class="btn btn-back btn-sm form-control" @click="backToList">戻る</button>
        </div>
    </div>
</template>
<script>
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';
export default {
    data: () => ({
        loading: false,
        mainList: null,
        reserveList: null,
        qrList: null,

        keepDOM: {},
        reserveCollection: new wjCore.CollectionView(),
        layoutDefinition: null,

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ 入荷状況　チェックボックス　ID ]
            deny_resizing_col: [],
            // 非表示[ ID ]
            invisible_col: [],
        },
        gridPKCol: 0,
        // 以下,initializedで紐づける変数
        wjReserveGrid: null,

    }),
    props: {
        mainData: {},
        reserveData: Array,
        qrData: Array,
        shelflist: Array,
        matterlist: Array,
        customerlist: Array,
    },
    created: function() {
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
    },
    watch: {
        mainData: function() {
            this.mainList = this.mainData;
        },
        qrData: function() {
            this.qrList = this.qrData;
        },
        reserveData: function() {
            this.reserveList = this.reserveData;
            // var dataCount = 0;
            var itemsSource = [];
            this.reserveList.forEach(element => {
                // DOM生成
                // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                this.keepDOM[element.id] = {
                
                }


                // dataCount++;
                itemsSource.push({
                    // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                    // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                    id: element.id,
                    matter_name: element.matter_name,
                    address: element.address,
                    reserve_date: element.reserve_date,
                    delivery_date: element.delivery_date,
                    reserve_quantity: element.reserve_quantity,
                })
            });
            // データセット
            this.reserveCollection = new wjCore.CollectionView(itemsSource);
            this.wjReserveGrid.itemsSource = itemsSource;
            // this.tableData = dataCount;

            // 設定更新
            this.wjReserveGrid = this.applyGridSettings(this.wjReserveGrid);
            // 描画更新
            this.wjReserveGrid.refresh();
        }

    },
    methods: {
        qrPrint(index) {
            // QR印刷
            var data = this.qrList[index];

            var params = new URLSearchParams();
            
            params.append('id', this.rmUndefinedBlank(data.detail_id));
            params.append('qr_id', this.rmUndefinedBlank(data.qr_id));
            params.append('qr_code', this.rmUndefinedBlank(data.qr_code));
            
            axios.post('/stock-inquiry/print', params)

            .then( function (response) {
                if (response.data) { 
                    var url = response.data;
                    var pattern = 'smapri:';
                    if (url.indexOf(pattern) > -1) {
                        // iosの場合
                        window.location.href = url                        
                    }
                }
                // this.loading = false;
            }.bind(this))
            .catch(function (error) {
                // this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                location.reload()
            }.bind(this))
        },
        backToList: function() {
            // 一覧画面へ
            this.mainList = null;
            this.reserveList = null;
            this.qrList = null;
            this.$emit('backToList');
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

            this.wjReserveGrid = multirow;
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
                switch (c) {
                    case 0:
                        
                        break;
                    case 1:
                        cell.style.textAlign = 'right';
                        break;
                    case 5: 

                        break;
                    case 6: 

                        break;
                    case 7: 

                        break;
                    case 8: 

                        break;
                }
            }
        },
        getLayout() {
            return [
                {
                    header: '入荷日', cells: [
                        { binding: 'matter_name', header: '案件名', minWidth: GRID_COL_MIN_WIDTH, width: '180*', isReadOnly: true},
                        { binding: 'address', header: '住所', minWidth: GRID_COL_MIN_WIDTH, width: '180*', isReadOnly: true},
                    ]
                },
                {
                    header: '案件／発注', cells: [
                        { binding: 'delivery_date', header: '出荷予定日', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'reserve_quantity', header: '引当数', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },                        
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
    }
}
</script>

<style>
.main-body {
    width: 100%;
    background: #ffffff;
    margin-top: 10px;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.read-text {
    font-size: 20px;
}

.quantity-block {
    line-height: 1.5;
    display: flex;
}
.quantity-header{
    background-color: #43425D;
    color: #FFFFFF;
    text-align: center;
    font-size: 14px;
    padding-top: 10px;
    /* height: 42px; */
    font-weight: bold;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    white-space: nowrap;
}
.quantity-body {
    background: #ffffff;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    text-align: center;
    font-size: 14px;
    padding-top: 10px;
    word-wrap: break-word;
    /* height: 42px; */
}
.btn-print{
    margin-bottom: 30px;
}
.btn-back {
    margin: 10px 0px; 
}
.container-fluid .wj-multirow  {
    margin: 6px 0;
    font-size: 10px;
}
.container-fluid .multirow-reserve {
    height: 220px;
}
/* .container-fluid  .wj-detail{
    padding-left: 225px !important;
} */

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
    text-align: center !important;
}

.wj-cell {
    font-size: 1.5rem;
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