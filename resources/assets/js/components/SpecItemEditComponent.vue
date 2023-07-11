<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-md-3 col-sm-3">
                        <label class="control-label">項目名</label>
                        <wj-auto-complete class="form-control" 
                            search-member-path="item_name"
                            display-member-path="item_name"
                            selected-value-path="item_name"
                            :selected-index="-1"
                            :selected-value="searchParams.item_name"
                            :is-required="false"
                            :initialized="initName"
                            :max-items="itemnamelist.length"
                            :items-source="itemnamelist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-9 col-sm-9 col-xs-12">
                        <label class="control-label">種別</label>
                        <el-checkbox-group v-model="searchParams.selectedType">
                            <el-checkbox class="col-md-2 col-sm-4 col-xs-6" v-for="row in typelist" :label="row.value_code" :key="row.value_code">{{ row.value_text_1 }}</el-checkbox>
                        </el-checkbox-group>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="row">
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-primary btn-clear" @click="clearSearch">クリア</button>
                        <button type="submit" class="btn btn-primary btn-search">検索</button>
                    </div>
                </div>
            </form>
        </div>
        <br>
        <!-- 検索結果グリッド -->
        <div class="col-sm-12 result-body">
            <p style="color:red;">{{ msg }}</p>
            <div class="col-md-8 col-sm-8 col-xs-12">
                <div class="form-group">
                    <div class="col-md-6 col-sm-6 col-xs-12" v-bind:class="{'has-error': (errors.quote_request_kbn != '') }">
                        <label class="control-label"><span style="color:red;">＊</span>見積依頼区分</label>
                        <wj-auto-complete class="form-control" v-bind:readonly="isReadOnly"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="id"
                            :selected-index="-1"
                            :selected-value="specitemdata.quote_request_kbn"
                            :is-required="false"
                            :initialized="initQuoteKbn"
                            :isReadOnly="isReadOnly"
                            :max-items="constlist.length"
                            :items-source="constlist">
                        </wj-auto-complete>
                        <p class="text-danger">{{ errors.quote_request_kbn }}</p>
                    </div>

                    <div class="col-md-4 col-sm-4 col-xs-12" v-bind:class="{'has-error': (errors.start_date != '') }">
                        <label><span style="color:red;">＊</span>適用開始日</label>
                        <!-- <input type="text" class="form-control" /> -->
                        <wj-input-date class="form-control"  v-bind:readonly="isReadOnly"
                            :value="specitemdata.start_date"
                            :selected-value="specitemdata.start_date"
                            :initialized="initStartDate"
                            :isReadOnly="isReadOnly"
                            :isRequired=false
                        ></wj-input-date>
                        <p class="text-danger">{{ errors.start_date }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4 col-sm-4 col-xs-12 text-right">
                <button id="copy" type="button" class="btn btn-new btn-copy" v-on:click="copy" v-bind:disabled="isNewFlg">複製して新規作成</button>                
                <button type="button" class="btn btn-save" v-on:click="save" v-bind:disabled="isReadOnly">保存</button>
            </div>
            <!-- 保存対象 -->
            <div class="container-fluid">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-sm-6 col-sm-6 col-xs-12">
                            <h4 class="col-md-12 col-xs-12 pull-left"><span style="color:red;">＊</span>保存対象</h4>
                        </div>
                        <div class="col-sm-6 col-sm-6 col-xs-12">
                            <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">項目件数：{{ specLength }}件</p>                            
                        </div>
                    </div>
                </div>
                <!-- グリッド -->
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <wj-multi-row
                        :itemsSource="specitems"
                        :layoutDefinition="specItemLayout"
                        :initialized="initSpecItemGrid"                        
                    ></wj-multi-row>
                </div>                    
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12" style="margin-bottom:10px;">
                <div class="col-md-6 col-sm-6 col-xs-6">
                    <button type="button" class="btn btn-primary btn-add center-block" @click="addSpecItem" v-bind:disabled="isReadOnly"><i class="el-icon-caret-top arrow"></i></button>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6">
                    <button type="button" class="btn btn-primary btn-del center-block" @click="delSpecItem" v-bind:disabled="isReadOnly"><i class="el-icon-caret-bottom arrow"></i></button>
                </div>
            </div>
            <!-- 一覧 -->
            <div class="container-fluid">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-sm-2 col-sm-12 col-xs-12">
                            <h4 class="col-md-12 col-sm-12 col-xs-12">項目一覧</h4>
                        </div>
                        <div class="col-sm-4 col-sm-6 col-xs-6">
                            <div class="input-group">
                                <div class="input-group-addon">
                                    <span class="glyphicon glyphicon-search"></span>
                                </div>
                                <input v-model="filterText" @input="filter()" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-6 col-sm-6 col-xs-6">
                            <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ itemLength }}件</p>
                        </div>                        
                    </div>
                </div>
                <!-- グリッド -->
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <wj-multi-row
                        :itemsSource="item"
                        :layoutDefinition="layoutDefinition"
                        :initialized="initItemsGrid"                        
                    ></wj-multi-row>
                </div>       
            </div>            
        </div>
        <!-- ボタン -->
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
            <button type="button" class="btn btn-back" v-on:click="back">戻る</button>
            <button type="button" class="btn btn-danger btn-delete" v-show="(specitem.id != '' && specitem.id != undefined && !isReadOnly)" v-on:click="del">削除</button>
        </div>  
    </div>
</template>

<script>
import * as wjGrid from '@grapecity/wijmo.grid';
import * as wjCore from '@grapecity/wijmo';
import * as wjMultiRow from '@grapecity/wijmo.grid.multirow';
import * as wjInput from '@grapecity/wijmo.input';

export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        isNewFlg: false,

        FLG_ON : 1,
        FLG_OFF: 0,       

        itemLength: 0,
        specLength: 0,
        filterText: '',
        msg: '',

        // 検索関連
        searchParams: {
            item_name: '',
            selectedType: [],
        },
        wjSearchObj: {
            item_name: {},
        },

        errors: {
            start_date: '',
            quote_request_kbn: '',
            msg: '',
        },
        // データ
        specitemdata: {
            start_date: null,
            quote_request_kbn: null,
        },
        wjInputObj: {
            start_date: {},
            quote_request_kbn: {},
        },
        IDs: [],
        updateList: [],
        deleteList: [],
        
        keepDOM: {},
        specitems: new wjCore.CollectionView(),
        item: new wjCore.CollectionView(),
        
        layoutDefinition: null,
        specItemLayout: null,

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ チェックボックス, ID ]
            deny_resizing_col: [0, 1],
            // 非表示[  ]
            invisible_col: [],
        },
        gridPKCol: 1,
        // 以下,initializedで紐づける変数
        wjSpecItemGrid: null,
        wjItemsGrid: null,
    }),
    props: {
        isEditable: Number,
        specitem: {},
        specdetail: {},
        itemlist: Array,
        constlist: Array,
        itemnamelist: Array,
        typelist: Array,
    },
    created: function() {
        // グリッドレイアウトセット
        this.layoutDefinition = this.getLayout();
        this.specItemLayout = this.getSpecItemLayout();
    },
    mounted: function() {
        if (!this.isEditable) {
            this.isReadOnly = true;
            this.isNewFlg = true;
        }
        // 適用開始日を超えたら保存不可
        // var validDay = moment(new Date).format('YYYY-MM-DD');
        // if (this.specitem.start_date != undefined && this.specitem.start_date != null && this.specitem.start_date <= validDay) {
        //     this.isReadOnly = true;
        //     this.msg = MSG_START_DATE_OVER;
        // }
        var cArr = [];
        var cSrc = null;
        if (this.rmUndefinedBlank(this.specitem) != '') {         
            // データをグリッドへセット   
            for (var i = 0; i < this.specdetail.length; i++) {
                cSrc = this.specdetail[i];
                cSrc.chk = false;
                this.IDs.push(this.specdetail[i].id);
                
                cArr.push(cSrc);
            }
            this.wjSpecItemGrid.itemsSource = cArr;
            this.specLength = cArr.length;
        } else {
            this.isNewFlg = true;
        }
        this.updateList = this.specdetail;
        this.specitemdata.start_date = this.specitem.start_date;
        this.specitemdata.quote_request_kbn = this.rmUndefinedBlank(this.specitem.quote_request_kbn) != '' ? this.specitem.quote_request_kbn : null;

        var sArr = [];
        var sSrc = null;
        if (this.rmUndefinedBlank(this.itemlist) != '') {         
            // 一覧をグリッドへセット   
            for (var i = 0; i < this.itemlist.length; i++) {
                sSrc = this.itemlist[i];
                sSrc.chk = false;                
                var flg = true;
                for (var j = 0; j < cArr.length; j++) {
                    // 既に対照として保存されている仕入先は一覧から除く
                    if (cArr[j].id == sSrc.id) {
                        var flg = false;
                    }
                }
                 if (flg) {                                                
                    sArr.push(sSrc);
                }
            }
            this.wjItemsGrid.itemsSource = sArr;
            this.itemLength = sArr.length;
        }

        // itemFormatterセット
        this.gridFormatter(this.wjSpecItemGrid);
        this.gridFormatter(this.wjItemsGrid);
        this.applyGridSettings(this.wjSpecItemGrid);
        this.applyGridSettings(this.wjItemsGrid);
    },
    methods: {
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.specitem.id));

            axios.post('/spec-item-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/spec-item-list' + window.location.search
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
                window.onbeforeunload = null;
                location.reload()
            }.bind(this))
        },
        // 複製して新規作成
        copy() {
            this.specitem.id = '';
            this.updateList.forEach(data => {
                data.detail_id = '';
            })
            this.deleteList = [];
            var targetDOM = document.getElementById("copy");
            targetDOM.textContent = '複製モード';
            
            this.isReadOnly = false;
            this.isNewFlg = true;
        },
        // 保存
        save() {
            this.loading = true;
            var isValid = false;
            this.initErr(this.errors);

            var inpDay = this.wjInputObj.start_date.text;
            var validDay = moment(new Date).format('YYYY/MM/DD');
            
            if (inpDay >= validDay) {
                isValid = true;
            } else {
                // 適用開始日が当日以前
                this.errors.start_date = MSG_ERROR_NEXT_DATE;
                this.loading = false;
            }

            if (isValid) {
                // パラメータセット
                var params = new URLSearchParams();
                // ヘッダーデータ
                params.append('id', this.rmUndefinedBlank(this.specitem.id));
                params.append('start_date', this.rmUndefinedBlank(this.wjInputObj.start_date.text));
                params.append('quote_request_kbn', this.rmUndefinedBlank(this.wjInputObj.quote_request_kbn.selectedValue));
                // detailデータ
                params.append('spec_item', JSON.stringify(this.updateList));
                // 削除データ
                params.append('delete_item', JSON.stringify(this.deleteList));

                axios.post('/spec-item-edit/save', params)
                .then( function (response) {
                    this.loading = false

                    if (response.data) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/spec-item-list' + window.location.search
                        location.href = (listUrl)
                    } else {
                        // 失敗
                        // alert(MSG_ERROR)
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
                        location.reload()
                    }
                }.bind(this))
            }
        },
        gridFormatter(grid) {
            var _this = this;
            grid.itemFormatter = function(panel, r, c, cell) {                
                // 列ヘッダのセンタリング
                if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                    cell.style.textAlign = 'center';
                
                    // チェックボックス生成
                    if (panel.columns[c].name == 'chk') {    
                        var checkedCount = 0;
                        for (var i = 0; i < grid.rows.length; i++) {
                            if (grid.getCellData(i, c) == true) checkedCount++;
                        }

                        var checkBox = '<input type="checkbox">';
                        if(_this.isReadOnly){
                            checkBox = '<input type="checkbox" disabled="true">';
                        }
                        cell.innerHTML = checkBox;
                        var checkBox = cell.firstChild;
                        checkBox.checked = checkedCount > 0;
                        checkBox.indeterminate = checkedCount > 0 && checkedCount < grid.rows.length;                        

                        checkBox.addEventListener('click', function (e) {
                            grid.beginUpdate();
                            for (var i = 0; i < grid.rows.length; i++) {
                                grid.setCellData(i, c, checkBox.checked);
                            }
                            grid.endUpdate();
                        });                        
                    }
                                   

                }
                if (panel.cellType == wjGrid.CellType.Cell) {      
                    // c（0始まり）
                    // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                    // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                    // var item = grid.rows[r];
                    if (panel.columns[c].name == 'id') {  
                        cell.style.backgroundColor = '#cccccc';
                        cell.style.color ='#000'
                    }

                    if (panel.columns[c].name == 'item_name') {  
                        cell.style.backgroundColor = '#cccccc';
                        cell.style.color ='#000'
                    }

                    if (panel.columns[c].name == 'item_front_label') {  
                        cell.style.backgroundColor = '#cccccc';
                        cell.style.color ='#000'
                    }

                    if (panel.columns[c].name == 'item_back_label') {  
                        cell.style.backgroundColor = '#cccccc';
                        cell.style.color ='#000'
                    }

                    if (panel.columns[c].name == 'value_text_1') {  
                        cell.style.backgroundColor = '#cccccc';
                        cell.style.color ='#000'
                    }     

                    if (panel.columns[c].name == 'item_group') {  
                        if (!_this.isEditable) {
                            cell.style.backgroundColor = '#cccccc';
                            cell.style.color ='#000'
                            grid.beginningEdit.addHandler(function (s, e) {
                                e.cancel = true;
                            }.bind(this));
                        }
                    } 

                    if (panel.columns[c].name == 'display_order') {  
                        if (!_this.isEditable) {
                            cell.style.backgroundColor = '#cccccc';
                            cell.style.color ='#000'
                            grid.beginningEdit.addHandler(function (s, e) {
                                e.cancel = true;
                            }.bind(this));
                        }
                    } 

                    switch (c) {
                        case 0: //chk

                            break;
                        case 1: // ID
                            cell.style.textAlign = 'center';
                            break;
                        case 2: // 項目名グループ
                            cell.style.textAlign = 'left';
                            break;
                        case 3: // 表示順
                            cell.style.textAlign = 'left';
                            break;
                        case 4: // 項目名
                            cell.style.textAlign = 'left';
                            break;
                        case 5: // 前ラベル
                            cell.style.textAlign = 'left';
                            break;
                        case 6: // 後ラベル
                            cell.style.textAlign = 'left';
                            break;  
                        case 7: // 種別
                            cell.style.textAlign = 'left'; 
                            break;  
                    }            
                }
            }
        },
        // 保存対象に追加
        addSpecItem() {
            var cArr = [];
            var sArr = [];
            var cSrc = null;
            var sSrc = null;
            var max = -Infinity;
            if (this.wjSpecItemGrid != undefined || this.wjSpecItemGrid != null) {
                for (var i = 0; i < this.wjSpecItemGrid.itemsSource.length; i++) {
                    if (this.wjSpecItemGrid.itemsSource[i] != undefined || this.wjSpecItemGrid.itemsSource[i] != null) {
                        // 元配列
                        cSrc = this.wjSpecItemGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(cSrc.display_order) == '') {
                            cSrc.display_order = 0;
                        }

                        cArr.push(this.wjSpecItemGrid.itemsSource[i]);
                    }                    
                }
                if (cArr[0] != null || cArr[0] != undefined) {
                    // 表示順の最大値算出
                    var targetArr = cArr;
                    var i = targetArr.length;
                    while (i--) {
                        if (targetArr[i].display_order > max) {
                            max = targetArr[i].display_order
                        }
                    }
                }
            }
            if (this.wjItemsGrid.itemsSource != undefined || this.wjItemsGrid.itemsSource != null) {
                for (var i = 0; i < this.wjItemsGrid.itemsSource.length; i++) {
                    if (this.wjItemsGrid.itemsSource[i].chk == true) {
                        // 移動する配列
                        cSrc = this.wjItemsGrid.itemsSource[i];
                        cSrc.chk = false;
                        cSrc.item_group = '';
                        // 表示順自動入力
                        if (cArr[0] != null || cArr[0] != undefined || max != -Infinity) {
                            max = parseInt(max) + 10;
                            cSrc.display_order = max;
                        } else {
                            max = 10;
                            cSrc.display_order = 10;
                        }                        

                        cArr.push(cSrc);
                        // 保存用配列に格納
                        this.updateList.push(cSrc);   
                        for (var j = 0; j < this.deleteList.length; j++) {
                            if (this.deleteList[j] == cSrc.detail_id) {
                                this.deleteList.splice(j, 1);
                            }
                        }                      
                    } else {
                        // 移動しない配列
                        sSrc = this.wjItemsGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(sSrc.display_order) == '') {
                            sSrc.display_order = 0;
                        }
                        // sSrc.chk = this.wjItemsGrid.itemsSource[i].chk;

                        sArr.push(sSrc);
                    }
                }
            }
            // 件数セット
            this.specLength = cArr.length;
            this.itemLength = sArr.length;
            
            // データセット
            this.wjSpecItemGrid.itemsSource = cArr;
            this.wjItemsGrid.itemsSource = sArr;
            this.filter();
        },
        // 保存対象から除外
        delSpecItem() {
            var cArr = [];
            var sArr = [];
            var cSrc = null;
            var sSrc = null;
            if (this.wjItemsGrid != undefined || this.wjItemsGrid != null) {
                for (var i = 0; i < this.wjItemsGrid.itemsSource.length; i++) {
                    if (this.wjItemsGrid.itemsSource[i] != undefined || this.wjItemsGrid.itemsSource[i] != null) {
                        // 元配列
                        sSrc = this.wjItemsGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(sSrc.display_order) == '') {
                            sSrc.display_order = 0;
                        }

                        sArr.push(sSrc);                        
                    }
                }
            }
            if (this.wjSpecItemGrid != undefined || this.wjSpecItemGrid != null) {
                for (var i = 0; i < this.wjSpecItemGrid.itemsSource.length; i++) {
                    if (this.wjSpecItemGrid.itemsSource[i].chk == true) {
                        // 移動する配列
                        sSrc = this.wjSpecItemGrid.itemsSource[i];
                        sSrc.chk = false;
                        if (this.rmUndefinedBlank(sSrc.display_order) == '') {
                            sSrc.display_order = 0;
                        }

                        sArr.push(sSrc);

                        // 削除用配列に格納
                        for (var j = 0; j < this.IDs.length; j++) {
                            if (this.IDs[j] == sSrc.id) {
                                this.deleteList.push(sSrc.detail_id);
                            }
                        }
                        for (var j = 0; j < this.updateList.length; j++) {
                            if (this.updateList[j].id == sSrc.id) {
                                this.updateList.splice(j, 1);
                            }
                        } 
                    } else {
                        // 移動しない配列
                        cSrc = this.wjSpecItemGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(cSrc.display_order) == '') {
                            cSrc.display_order = 0;
                        }
                        // sSrc.chk = this.wjSpecItemGrid.itemsSource[i].chk;

                        cArr.push(cSrc);
                    }
                }
            }
            // 件数セット
            this.specLength = cArr.length;
            this.itemLength = sArr.length;      

            // データセット
            this.wjSpecItemGrid.itemsSource = cArr;
            this.wjItemsGrid.itemsSource = sArr;
            this.filter();
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('item_name', this.rmUndefinedBlank(this.wjSearchObj.item_name.text));
            params.append('item_type', this.rmUndefinedBlank(this.searchParams.selectedType));
           
            axios.post('/spec-item-edit/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'item_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.item_name.text));
                    this.urlparam += '&' + 'item_type=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.selectedType));
                    
                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        var alreadyFlg = false;
                        // 保存対象に含まれている仕入先除外
                        for (var i = 0; i < this.updateList.length; i++) {
                            if (this.updateList[i].id == element.id) {
                                alreadyFlg = true;
                            }
                        }

                        if (!alreadyFlg) {
                            // DOM生成
                            // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                            this.keepDOM[element.id] = {
                            }

                            dataCount++;
                            itemsSource.push({
                                // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                                // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                                id: element.id,
                                item_name: element.item_name,
                                item_front_label: element.item_front_label,
                                item_back_label: element.item_back_label,
                                value_text_1: element.value_text_1,
                                chk: false,
                            })
                        }
                    });
                    // データセット
                    this.wjItemsGrid.itemsSource = itemsSource;

                    this.filter();
                    this.itemLength = dataCount;

                    // 設定更新
                    this.wjItemsGrid = this.applyGridSettings(this.wjItemsGrid);             
                    // 描画更新
                    this.wjItemsGrid.refresh();
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
        // 戻る
        back() {
            var listUrl = '/spec-item-list' + window.location.search
            location.href = listUrl;


        },
        getLayout() {
            return [
                {
                    cells: [
                        { name: 'chk', binding: 'chk', header: '選択', width: 50,   allowSorting: false, isReadOnly: false }
                    ]
                }, 
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', width: 60, isReadOnly: true },
                    ]
                },   
                {
                    header: '項目名', cells: [
                        { binding: 'item_name', name: 'item_name', header: '項目名', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },  
                    ]
                },
                {
                    header: '前ラベル', cells: [
                        { binding: 'item_front_label', name: 'item_front_label', header: '前ラベル', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },  
                    ]
                },
                {
                    header: '後ラベル', cells: [
                        { binding: 'item_back_label', name: 'item_back_label', header: '後ラベル', minWidth: GRID_COL_MIN_WIDTH, width: 250, isReadOnly: true },                         
                    ]
                },
                {
                    header: '種別', cells: [
                        { binding: 'value_text_1', name: 'value_text_1', header: '種別', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },                        
                    ]
                }, 
            ]
        },
        getSpecItemLayout() {
            return [
                {
                    cells: [
                        { name: 'chk', binding: 'chk', header: '選択', width: 50,   allowSorting: false, isReadOnly: false }
                    ]
                }, 
                {
                    header: 'ID', cells: [
                        { binding: 'id', name: 'id', header: 'ID', width: 60, isReadOnly: true },
                    ]
                },   
                {
                    header: '項目グループ名', cells: [
                        { binding: 'item_group', name: 'item_group', header: '項目グループ名', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: false },
                    ]
                },
                {
                    header: '表示順', cells: [
                        { binding: 'display_order', name: 'display_order', header: '表示順', minWidth: GRID_COL_MIN_WIDTH, width: 80, isReadOnly: false },                        
                    ]
                },
                {
                    header: '項目名', cells: [
                        { binding: 'item_name', name: 'item_name', header: '項目名', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },  
                    ]
                },
                {
                    header: '前ラベル', cells: [
                        { binding: 'item_front_label', name: 'item_front_label', header: '前ラベル', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },  
                    ]
                },
                {
                    header: '後ラベル', cells: [
                        { binding: 'item_back_label', name: 'item_back_label', header: '後ラベル', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },                         
                    ]
                },
                {
                    header: '種別', cells: [
                        { binding: 'value_text_1', name: 'value_text_1', header: '種別', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },                        
                    ]
                },                
            ]
        },
        // フィルター
        filter() {
            var filter = this.filterText.toLowerCase();
            this.wjItemsGrid.collectionView.filter = item => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (item.id != null && (item.id).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.item_name != null && (item.item_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.item_front_label != null && (item.item_front_label).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.item_back_label != null && (item.item_back_label).toString().toLowerCase().indexOf(filter) > -1) ||
                    (item.value_text_1 != null && (item.value_text_1).toString().toLowerCase().indexOf(filter) > -1)
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
            this.searchParams.selectedType = [];
        },
        initSpecItemGrid: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            multirow.selectionMode = wjGrid.SelectionMode.Cell;

            this.wjSpecItemGrid = multirow;
        },
        initItemsGrid: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            multirow.selectionMode = wjGrid.SelectionMode.Cell;

            this.wjItemsGrid = multirow;
        },
        /* 以下オートコンプリード設定 */
        initStartDate(sender) {
            this.wjInputObj.start_date = sender
        },
        initQuoteKbn(sender) {
            this.wjInputObj.quote_request_kbn = sender
        },
        initName(sender) {
            this.wjSearchObj.item_name = sender
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
.btn-search {
    width: 120px;
}
.btn-add {
    width: 40%;  
}
.btn-del {
    width: 40%;   
}
.btn-save {
    width: 80px;
}
.btn-save:disabled {
    pointer-events: none;
    background-color: gray;
    border-color: #ccc;
}
.btn-copy {
    width: 150px;
    background-color: #6200EE;
    color: white;
}
.btn-copy:disabled {
    pointer-events: none;
    background-color: gray;
    border-color: #ccc;
}
.arrow {
    font-size: 20px;
}
/* グリッド */
.wj-multirow {
    height: 200px;
    margin: 6px 0;
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