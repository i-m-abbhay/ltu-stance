<template>
    <div>
        <loading-component :loading="loading" />
        <div class="main-body">
            <div class="row">
                <p class="control-label col-md-12">納品写真1</p>
                <div class="text-center">
                    <img class="image-preview" v-show="shipmentData.image_photo1" :src="shipmentData.image_photo1">
                </div>
            </div>
            <hr>
            <div class="row">
                <p class="control-label col-md-12">納品写真2</p>
                <div class="text-center">
                    <img class="image-preview" v-show="shipmentData.image_photo2" :src="shipmentData.image_photo2">
                </div>
            </div>
            <hr>
            <div class="row">
                <p class="control-label col-md-12">サイン</p>
                <div class="text-center">
                    <img class="image-preview" v-show="shipmentData.image_sign" :src="shipmentData.image_sign">
                </div>
            </div>
        </div>

        <div class="row">
            <div class="text-center">
                <button type="button" class="btn btn-save" @click="showDialog">納品書印刷</button>
                <button type="button" class="btn btn-back" @click="close">閉じる</button>
            </div>
        </div>

        <!-- 納品書選択 -->
        <el-dialog title="納品書選択" :visible.sync="showDlgDelivery" :closeOnClickModal=false width="80%">
            <div class="col-md-12">
                <div id="wjDeliveryGrid"></div>
            </div>

            <span slot="footer" class="dialog-footer">
                <div class="col-md-5 pull-right">
                    <el-button @click="showDlgDelivery = false" class="btn btn-md btn-cancel">キャンセル</el-button>
                </div>
            </span>
        </el-dialog>
    </div>
</template>

<script>
import * as wjCore from '@grapecity/wijmo';
import * as wjcInput from '@grapecity/wijmo.input';
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import { CustomGridEditor } from '../CustomGridEditor.js';

export default {
    data: ()=> ({
        loading: false,


        showDlgDelivery: false,
        layoutDefinition: null,
        wjDeliveryGrid: null,
        gridSetting: {
            deny_resizing_col: [],

            invisible_col: [],
        },
        gridPKCol: 0,
    }),
    props: {
        shipmentData: {},
        deliveryList: Array,
    },
    created: function() {
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        // this.$nextTick(function() {
        //     this.wjDeliveryGrid = this.createGridCtrl('#wjDeliveryGrid', new wjCore.CollectionView([]));
        // });
    },
    methods: {
        close() {
            window.close();
        },
        // 納品書印刷
        print(item) {
            // this.loading = true;
            var params = new URLSearchParams();

            params.append('delivery_no', item.delivery_no);
        
            axios.post('/shipping-delivery-list/print', params)
            .then( function (response) {
                // this.loading = false;
                if (response.data) {
                    var url = response.data;
                    var pattern = 'smapri:';
                    if (url.indexOf(pattern) > -1) {
                        // iosの場合
                        window.location.href = url;
                    }
                    //  else {
                    //     window.open(url);
                    // }
                }
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
        showDialog() {
            var items = this.deliveryList;
            var itemsSource = [];
            items.forEach(element => {
                itemsSource.push({
                    id: element.delivery_id_list,
                    delivery_no: element.delivery_no,
                    delivery_date: element.delivery_date,
                    product_code_list: element.product_code_list,
                    created_at: element.created_at,
                })
            });
            if (this.wjDeliveryGrid != null) {
                this.wjDeliveryGrid.dispose();
            }
            this.showDlgDelivery = true;
            this.$nextTick(function() {
                this.wjDeliveryGrid = this.createGridCtrl('#wjDeliveryGrid', new wjCore.CollectionView(itemsSource));
            });
        },
        // グリッド生成
        createGridCtrl(targetGridDivId, gridItemSource) {
            var gridCtrl = new wjMultiRow.MultiRow(targetGridDivId, {
                itemsSource: gridItemSource,
                layoutDefinition: this.layoutDefinition,
                showSort: false,
                allowSorting: false,
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
                    var colName = this.wjDeliveryGrid.getBindingColumn(panel, r, c).name;

                    var dataItem = panel.rows[r].dataItem;
                    if (dataItem !== undefined) {
                        switch (colName) {
                            case 'btn':
                                cell.style.textAlign = 'center';
                                cell.style.padding = '0px';
                                if(dataItem !== undefined){
                                    var rId1 = 'print-' + dataItem.delivery_no;             

                                    var div = document.createElement('div');
                                    div.classList.add('btn-group', 'status-btn-group');
                                    div.setAttribute("data-toggle","buttons");

                                    // 詳細ボタン
                                    var btnPrint = document.createElement('button');
                                    btnPrint.type = 'button';
                                    btnPrint.id = rId1;
                                    btnPrint.classList.add('btn', 'btn-status', 'btn-edit');
                                    btnPrint.innerHTML = '印刷';
                                    // btnDetail.disabled = isDisable;

                                    btnPrint.addEventListener('click', function (e) {
                                        this.print(dataItem);
                                    }.bind(this));

                                    div.appendChild(btnPrint);

                                    cell.appendChild(div);
                                    
                                }
                                break;
                            case 'delivery_no':
                                cell.style.textAlign = 'left';
                                break;
                            case 'delivery_date':
                                cell.style.textAlign = 'left';
                                break;
                            case 'product_code_list':
                                cell.style.textAlign = 'left';
                                break;
                        };
                    }
                }
            }.bind(this);
            /* セル編集後イベント */
            gridCtrl.cellEditEnded.addHandler(function(s, e) {
                gridCtrl.beginUpdate();
                // 編集したカラムを特定
                var col = this.wjDeliveryGrid.getBindingColumn(e.panel, e.row, e.col);       // 取れるのは上段の列名
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

            /* カスタムグリッド */
            // キーダウンイベント
            gridCtrl.hostElement.addEventListener('keydown', function (e) {
                this.gridKeyDownSkipReadOnlyCell(gridCtrl, e, wjGrid);
            }.bind(this), true);

            return gridCtrl;
        },
        // レイアウト取得
        getLayout() {
            return [
                {
                    cells: [
                        { binding: 'btn', name: 'btn', header: '選択', minWidth: GRID_COL_MIN_WIDTH, width: 80, isReadOnly: true },
                    ]
                },
                {
                    cells: [
                        { binding: 'delivery_no', name: 'delivery_no', header: '納品番号', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true},
                    ]
                },
                {
                    cells: [
                        { binding: 'delivery_date', name: 'delivery_date', header: '納品日', minWidth: GRID_COL_MIN_WIDTH, width: 150, isReadOnly: true },                        
                    ]
                },
                {
                    cells: [
                        { binding: 'product_code_list', name: 'product_code_list', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },                        
                    ]
                },
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 0},
                    ]
                },
            ]
        },
    },
}
</script>

<style>
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
.image-preview{
    max-width: 80%;
    height: auto;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
    cursor: pointer;
}
.status-btn-group{
    width: 80px;
    height: 30px;
}
.btn-status{
    padding:3px 11.8px;
    border-radius: 0px;
    width: 80px;
}
.wj-flexgrid {
    height: 250px;
}
.el-dialog__header {
    height: 80px;
    /* width: 95% !important; */
}
.el-dialog__body {
    height: 300px;
    /* width: 95% !important; */
}
.el-dialog__footer {
    height: 80px;
    /* width: 95% !important; */
}
</style>

