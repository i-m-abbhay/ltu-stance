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
            <div class="col-md-4 col-sm-4 col-xs-6">
                <label class="control-label">メーカー名</label>
                <input type="text" class="form-control" v-model="supplierdata.supplier_name" v-bind:readonly="true">                
            </div>
            <div class="col-md-8 col-sm-8 col-xs-6 text-right">
                <button type="button" class="btn btn-save"  v-show="(!isLocked && !isShowEditBtn && isEditable)" v-on:click="save">保存</button>
                <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
            </div>
            <!-- 保存対象 -->
            <div class="container-fluid">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-sm-6 col-sm-6 col-xs-12">
                            <h4 class="col-md-12 col-xs-12 pull-left">保存対象</h4>
                        </div>
                        <div class="col-sm-6 col-sm-6 col-xs-12">
                            <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">対照件数：{{ contLength }}件</p>                            
                        </div>
                    </div>
                </div>
                <!-- グリッド -->
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <wj-multi-row
                        :itemsSource="contrasts"
                        :layoutDefinition="contrastLayout"
                        :initialized="initContrastGrid"                        
                    ></wj-multi-row>
                </div>                    
            </div>
            <div class="col-md-12 col-sm-12 col-xs-12" style="margin-bottom:10px;">
                <div class="col-md-6 col-sm-6 col-xs-6">
                    <button type="button" class="btn btn-primary btn-add center-block" @click="addContrast" v-bind:disabled="isReadOnly"><i class="el-icon-caret-top arrow"></i></button>
                </div>
                <div class="col-md-6 col-sm-6 col-xs-6">
                    <button type="button" class="btn btn-primary btn-del center-block" @click="delContrast" v-bind:disabled="isReadOnly"><i class="el-icon-caret-bottom arrow"></i></button>
                </div>
            </div>
            <!-- 仕入先一覧 -->
            <div class="container-fluid">
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <div class="row">
                        <div class="col-sm-2 col-sm-12 col-xs-12">
                            <h4 class="col-md-12 col-sm-12 col-xs-12">仕入先一覧</h4>
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
                            <p class="col-md-12 col-xs-12 pull-right search-count" style="text-align:right;">検索結果：{{ supLength }}件</p>
                        </div>                        
                    </div>
                </div>
                <!-- グリッド -->
                <div class="col-md-12 col-sm-12 col-xs-12">
                    <wj-multi-row
                        :itemsSource="suppliers"
                        :layoutDefinition="layoutDefinition"
                        :initialized="initSupplierGrid"                        
                    ></wj-multi-row>
                </div>       
            </div>            
        </div>
        <!-- ボタン -->
        <div class="col-md-12 col-sm-12 col-xs-12 text-center">
            <button type="button" class="btn btn-back" v-on:click="back">戻る</button>
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
        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,
        FLG_ON : 1,
        FLG_OFF: 0,       

        supLength: 0,
        contLength: 0,
        filterText: '',

        searchParams: {
            supplier_name: '',
            product_line: '',
            construction_name: '',
        },
        contrastIDs: [],
        updateList: [],
        deleteList: [],

        wjSearchObj: {
            supplier_name: {},
            product_line: {},
            construction_name: {},
        },

        keepDOM: {},
        contrasts: new wjCore.CollectionView(),
        suppliers: new wjCore.CollectionView(),
        
        layoutDefinition: null,
        contrastLayout: null,

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ チェックボックス, ID ]
            deny_resizing_col: [0, 1],
            // 非表示[  ]
            invisible_col: [],
        },
        gridPKCol: 1,
        // 以下,initializedで紐づける変数
        wjContrastGrid: null,
        wjSupplierGrid: null,
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        supplierdata: {},
        supplierlist: Array,
        contrastdata: Array,
        biglist: Array,
        constlist: Array,
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
        
        this.layoutDefinition = this.getLayout();
        this.contrastLayout = this.getContrastLayout();
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

        var cArr = [];
        var cSrc = null;
        if (this.rmUndefinedBlank(this.contrastdata) != '') {         
            // 対照データをグリッドへセット   
            for (var i = 0; i < this.contrastdata.length; i++) {
                cSrc = this.contrastdata[i];
                cSrc.chk = false;
                this.contrastIDs.push(this.contrastdata[i].id);
                
                cArr.push(cSrc);
            }
            this.wjContrastGrid.itemsSource = cArr;
            this.contLength = cArr.length;
        }        
        this.updateList = this.contrastdata;

        var sArr = [];
        var sSrc = null;
        if (this.rmUndefinedBlank(this.supplierlist) != '') {         
            // 仕入先一覧をグリッドへセット   
            for (var i = 0; i < this.supplierlist.length; i++) {
                sSrc = this.supplierlist[i];
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
            this.wjSupplierGrid.itemsSource = sArr;
            this.supLength = sArr.length;
        }
        // itemFormatterセット
        this.gridFormatter(this.wjContrastGrid);
        this.gridFormatter(this.wjSupplierGrid);
    },
    methods: {
        // 保存
        save() {
            this.loading = true;

            // パラメータセット
            var params = new URLSearchParams();
            
            params.append('maker_id', this.rmUndefinedZero(this.supplierdata.id));
            if (this.updateList != undefined && this.updateList != null) {
                for (var i = 0; i < this.updateList.length; i++) {
                    var src = this.updateList[i];
                    params.append('supplier[' + i + '][maker_id]', this.rmUndefinedZero(this.supplierdata.id));
                    params.append('supplier[' + i + '][sup_maker_id]', this.rmUndefinedZero(src.sup_maker_id));
                    params.append('supplier[' + i + '][supplier_id]', this.rmUndefinedZero(src.id));
                    params.append('supplier[' + i + '][priority_rank]', this.rmUndefinedBlank(src.priority_rank));
                }
            }
            if (this.deleteList != undefined && this.deleteList != null) {
                for (var i = 0; i < this.deleteList.length; i++) {
                    var src = this.deleteList[i];
                    params.append('delete_supplier[' + i + '][supplier_id]', this.rmUndefinedZero(src.id));
                    params.append('delete_supplier[' + i + '][maker_id]', this.rmUndefinedZero(this.supplierdata.id));
                    // params.append('delete_supplier[' + i + ']', this.rmUndefinedZero(src.sup_maker_id));
                }
            }

            
            axios.post('/supplier-maker-contrast/save', params)
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/supplier-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    // alert(MSG_ERROR)
                    // location.reload();
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
                    // location.reload()
                }
            }.bind(this))
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
                        if(this.isReadOnly){
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
                    // チェックボックスのクリック判定をセルに
                    // grid.beginningEdit.addHandler(function (s, e) {
                    //     if (e.col == 0) {
                    //         e.cancel = true;
                    //     }
                    // });
                    // grid.hostElement.addEventListener('mousedown', function (e) {
                    //     var ht = grid.hitTest(e.pageX, e.pageY);
                    //     if (ht.cellType == wjGrid.CellType.Cell && ht.col == 0) {
                    //         var data = grid.getCellData(ht.row, ht.col);
                    //         grid.setCellData(ht.row, ht.col, !data);
                    //     }
                    // });            

                    // c（0始まり）
                    // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                    // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                    // var item = grid.rows[r];
                    switch (c) {
                        case 0:

                            break;
                        case 1: // ID
                            cell.style.textAlign = 'center';
                            break;
                        case 2: // 仕入先名
                            cell.style.textAlign = 'left';
                            break;
                        case 3: // 仕入先名カナ
                            cell.style.textAlign = 'left';
                            break;
                        case 4: // 取扱い品目
                            cell.style.textAlign = 'left';
                            break;
                        case 5: // 施工業者
                            cell.style.textAlign = 'left';
                            break;
                        case 6: // 住所
                            cell.style.textAlign = 'left';
                            break;  
                        case 7: // 優先度
                            cell.style.textAlign = 'center'; 
                            if (_this.isError) {
                                cell.style.backgroundColor  = '#FF3B30';
                            }
                            break;  
                    }
                }
            }
        },
        // 保存対象に追加
        addContrast() {
            var cArr = [];
            var sArr = [];
            var cSrc = null;
            var sSrc = null;
            var cArrayIds = [];
            if (this.wjContrastGrid != undefined || this.wjContrastGrid != null) {
                for (var i = 0; i < this.wjContrastGrid.rows.length; i++) {
                    if (this.wjContrastGrid.itemsSource[i] != undefined || this.wjContrastGrid.itemsSource[i] != null) {
                        // 元配列
                        cSrc = this.wjContrastGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(cSrc.priority_rank) == '') {
                            cSrc.priority_rank = 0;
                        }

                        cArr.push(this.wjContrastGrid.itemsSource[i]);
                    }
                }
            }
            if (this.wjSupplierGrid.collectionView.items != undefined || this.wjSupplierGrid.collectionView.items != null) {
                for (var i = 0; i < this.wjSupplierGrid.rows.length; i++) {
                    if (this.wjSupplierGrid.collectionView.items[i].chk == true) {
                        // 移動する配列
                        cSrc = this.wjSupplierGrid.collectionView.items[i];
                        cSrc.chk = false;
                        // 優先度自動入力
                        if (cArr[0] != null || cArr[0] != undefined) {
                            // 10刻みで連番
                            var str = (cArr.length + 1).toString();
                            var num = str.length + 1;
                            for (var j = 0; str.length < num; str += 0);
                            cSrc.priority_rank = str;
                        } else {
                            cSrc.priority_rank = 10;
                        }

                        cArr.push(cSrc);
                        // 保存用配列に格納
                        this.updateList.push(cSrc);   
                        for (var j = 0; j < this.deleteList.length; j++) {
                            if (this.deleteList[j].id == cSrc.id) {
                                this.deleteList.splice(j, 1);
                            }
                        }                      
                    // } else {
                    //     // 移動しない配列
                    //     sSrc = this.wjSupplierGrid.itemsSource[i];
                    //     if (this.rmUndefinedBlank(sSrc.priority_rank) == '') {
                    //         sSrc.priority_rank = 0;
                    //     }
                    //     // sSrc.chk = this.wjSupplierGrid.itemsSource[i].chk;

                    //     sArr.push(sSrc);
                    // }
                    }
                }

                cArr.forEach(rec => {
                    cArrayIds.push(rec.id);
                });

                this.supplierlist.forEach(element => {
                    element.chk = false;
                    
                    if(cArrayIds.indexOf(element.id) == -1) {
                        sArr.push(element);
                    }
                    // sArr.push(element);
                });
                // for (var i = 0; i < sArr.length; i++) {
                //     sArr.chk = false;
                //     for(var j = 0; j < cArr.length; j++) {
                //         if (sArr[i].id == cArr[j].id) {
                //             sArr.splice(i, 1);
                //         }
                //     }
                // }
            }
            // 件数セット
            this.contLength = cArr.length;
            this.supLength = sArr.length;
            
            // データセット
            this.wjContrastGrid.itemsSource = cArr;
            this.wjSupplierGrid.itemsSource = sArr;
            this.filter(this.wjSupplierGrid);
        },
        // 保存対象から除外
        delContrast() {
            var cArr = [];
            var sArr = [];
            var cSrc = null;
            var sSrc = null;
            if (this.wjSupplierGrid != undefined || this.wjSupplierGrid != null) {
                for (var i = 0; i < this.wjSupplierGrid.rows.length; i++) {
                    if (this.wjSupplierGrid.itemsSource[i] != undefined || this.wjSupplierGrid.itemsSource[i] != null) {
                        // 元配列
                        sSrc = this.wjSupplierGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(sSrc.priority_rank) == '') {
                            sSrc.priority_rank = 0;
                        }

                        sArr.push(sSrc);                        
                    }
                }
            }
            if (this.wjContrastGrid != undefined || this.wjContrastGrid != null) {
                for (var i = 0; i < this.wjContrastGrid.rows.length; i++) {
                    if (this.wjContrastGrid.itemsSource[i].chk == true) {
                        // 移動する配列
                        sSrc = this.wjContrastGrid.itemsSource[i];
                        sSrc.chk = false;
                        if (this.rmUndefinedBlank(sSrc.priority_rank) == '') {
                            sSrc.priority_rank = 0;
                        }

                        sArr.push(sSrc);

                        // 削除用配列に格納
                        for (var j = 0; j < this.contrastIDs.length; j++) {
                            if (this.contrastIDs[j] == sSrc.id) {
                                this.deleteList.push(sSrc);
                            }
                        }
                        for (var j = 0; j < this.updateList.length; j++) {
                            if (this.updateList[j].id == sSrc.id) {
                                this.updateList.splice(j, 1);
                            }
                        } 
                    } else {
                        // 移動しない配列
                        cSrc = this.wjContrastGrid.itemsSource[i];
                        if (this.rmUndefinedBlank(cSrc.priority_rank) == '') {
                            cSrc.priority_rank = 0;
                        }
                        // sSrc.chk = this.wjContrastGrid.itemsSource[i].chk;

                        cArr.push(cSrc);
                    }
                }
            }
            // 件数セット
            this.contLength = cArr.length;
            this.supLength = sArr.length;
            
            // データセット
            this.wjContrastGrid.itemsSource = cArr;
            this.wjSupplierGrid.itemsSource = sArr;
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('product_line', this.rmUndefinedBlank(this.wjSearchObj.product_line.text));
            params.append('construction_name', this.rmUndefinedBlank(this.wjSearchObj.construction_name.text));
           
            axios.post('/supplier-maker-contrast/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    // this.urlparam = '?'
                    // this.urlparam += 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
                    // this.urlparam += '&' + 'product_line=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.product_line.text));
                    // this.urlparam += '&' + 'construction_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.construction_name.text));
                    
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
                                supplier_name: element.supplier_name,
                                supplier_kana: element.supplier_kana,
                                address: element.address,
                                contractor_kbn: element.contractor_kbn,
                                product_line: element.product_line,
                                chk: false,
                            })
                        }
                    });
                    this.supplierlist = itemsSource;
                    // データセット
                    this.wjSupplierGrid.itemsSource = itemsSource;

                    this.filter();
                    this.supLength = dataCount;

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
        // 戻る
        back() {
            var listUrl = '/supplier-list' + window.location.search

            if (!this.isReadOnly && this.supplierdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'supplier-maker-contrast');
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
            params.append('screen', 'supplier-maker-contrast');
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
            params.append('screen', 'supplier-maker-contrast');
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
        getLayout() {
            return [
                {
                    cells: [
                        { name: 'chk', binding: 'chk', header: '選択', width: 50,   allowSorting: false, isReadOnly: false }
                    ]
                }, 
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 60, isReadOnly: true },
                    ]
                },   
                {
                    header: '仕入先名', cells: [
                        { binding: 'supplier_name', header: '仕入先名', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },
                    ]
                },
                {
                    header: '仕入先名', cells: [
                        { binding: 'supplier_kana', header: '仕入先名カナ', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },  
                    ]
                },
                {
                    header: '取扱い品目', cells: [
                        { binding: 'product_line', header: '取扱い品目', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },  
                    ]
                },
                {
                    header: '取扱い品目', cells: [
                        { binding: 'contractor_kbn', header: '施工業者区分', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },                         
                    ]
                },
                {
                    header: '住所', cells: [
                        { binding: 'address', header: '住所', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },                        
                    ]
                },
            ]
        },
        getContrastLayout() {
            return [
                {
                    cells: [
                        { name: 'chk', binding: 'chk', header: '選択', width: 50,   allowSorting: false, isReadOnly: false }
                    ]
                }, 
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID', width: 60, isReadOnly: true },
                    ]
                },   
                {
                    header: '仕入先名', cells: [
                        { binding: 'supplier_name', header: '仕入先名', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },
                    ]
                },
                {
                    header: '仕入先名', cells: [
                        { binding: 'supplier_kana', header: '仕入先名カナ', minWidth: GRID_COL_MIN_WIDTH, width: 210, isReadOnly: true },  
                    ]
                },
                {
                    header: '取扱い品目', cells: [
                        { binding: 'product_line', header: '取扱い品目', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },  
                    ]
                },
                {
                    header: '取扱い品目', cells: [
                        { binding: 'contractor_kbn', header: '施工業者区分', minWidth: GRID_COL_MIN_WIDTH, width: 220, isReadOnly: true },                         
                    ]
                },
                {
                    header: '住所', cells: [
                        { binding: 'address', header: '住所', minWidth: GRID_COL_MIN_WIDTH, width: '*', isReadOnly: true },                        
                    ]
                },
                {
                    header: '優先度', cells: [
                        { binding: 'priority_rank', header: '優先度', minWidth: GRID_COL_MIN_WIDTH, width: 80 },                        
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
        },
        initContrastGrid: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;
            // 高さ自動調整
            // multirow.autoRowHeights = true,

            this.wjContrastGrid = multirow;
        },
        initSupplierGrid: function(multirow) {
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            multirow = this.applyGridSettings(multirow);
            // セルを押下してもカーソルがあたらないように変更
            // multirow.selectionMode = wjGrid.SelectionMode.None;
            // 高さ自動調整
            // multirow.autoRowHeights = true,

            this.wjSupplierGrid = multirow;
        },
        /* 以下オートコンプリード設定 */
        initName(sender) {
            this.wjSearchObj.supplier_name = sender
        },
        initProductLine(sender) {
            this.wjSearchObj.product_line = sender
        },
        initConstruct(sender) {
            this.wjSearchObj.construction_name = sender
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
.arrow {
    font-size: 20px;
}
/* グリッド */
.wj-multirow {
    height: 160px;
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