<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form" id="searchForm">
             <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="col-md-12 col-sm-12 card-body">
                    <h4>検索情報</h4>
                    <!-- 使用分類 -->            
                    <div class="col-md-4 col-sm-12 col-xs-12">
                        <div class="col-md-12 col-sm-12 col-xs-12 card-body">
                            <h5><u>使用分類</u></h5>
                            <label class="col-md-12 col-sm-12 col-xs-12">大分類</label>
                            <div class="col-md-10 col-sm-12 col-xs-12">
                                <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="class_big_name"
                                display-member-path="class_big_name"
                                selected-value-path="class_big_id"
                                :selected-index="-1"
                                :selected-value="searchParams.class_big_id"
                                :is-required="false"
                                :selectedIndexChanged="changeIdxClassBig"
                                :initialized="selectBig"
                                :max-items="classbigdata.length"
                                :items-source="classbigdata">
                                </wj-auto-complete>
                            </div>

                            <label class="col-md-11 col-md-offset-1 col-sm-11 col-xs-11">中分類</label>
                            <div class="col-md-10 col-sm-10 col-md-offset-1 col-sm-11 col-xs-11">
                                <wj-auto-complete class="form-control" id="acClassMid"
                                search-member-path="class_middle_name"
                                display-member-path="class_middle_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.class_middle_id"
                                :is-required="false"
                                :initialized="selectMiddle"
                                :max-items="classmiddata.length"
                                :items-source="classmiddata">
                                </wj-auto-complete>
                            </div>

                            <label class="col-md-12 col-sm-12 col-xs-12">工事区分</label>
                            <div class="col-md-12 col-sm-12 col-sm-12 col-xs-12">
                                <wj-auto-complete class="form-control" id="acConst"
                                search-member-path="construction_name"
                                display-member-path="construction_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.construction_id"
                                :selectedIndexChanged="changeIdxConst"
                                :is-required="false"
                                :initialized="selectConstruction"
                                :max-items="constdata.length"
                                :items-source="constdata">
                                </wj-auto-complete>
                            </div>

                            <label class="col-md-12 col-sm-12 col-xs-12">工程</label>
                            <div class="col-md-12 col-sm-12 col-sm-12 col-xs-12">
                                <wj-auto-complete class="form-control" id="acUsage"
                                search-member-path="class_small_name"
                                display-member-path="class_small_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.class_small_id"
                                :is-required="false"
                                :initialized="selectSmall"
                                :max-items="classsmalldata.length"
                                :items-source="classsmalldata">
                                </wj-auto-complete>
                            </div>
                        </div>
                    </div>
                    <!-- 商品詳細 -->
                    <div class="col-md-5 col-sm-12 col-xs-12">
                        <div class="col-md-12 col-sm-12 col-xs-12 card-body">
                            <h5><u>商品詳細</u></h5>
                            <label class="col-md-12 col-sm-12 col-xs-12">商品番号</label>
                            <div class="col-md-6 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" v-model="searchParams.product_code">
                            </div>

                            <label class="col-md-12 col-sm-12 col-xs-12">商品名</label>
                            <div class="col-md-10 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" v-model="searchParams.product_name">
                            </div>

                            <label class="col-md-12 col-sm-12 col-xs-12">型式／規格</label>
                            <div class="col-md-9 col-sm-12 col-xs-12">
                                <input type="text" class="form-control" v-model="searchParams.model">
                            </div>

                            <label class="col-md-12 col-sm-12 col-xs-12">メーカー名</label>
                            <div class="col-md-8 col-sm-12 col-xs-12">
                                <wj-auto-complete class="form-control" id="acMaker"
                                search-member-path="supplier_name"
                                display-member-path="supplier_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :is-required="false"
                                :selected-value="searchParams.maker_id"
                                :initialized="selectMakerId"
                                :max-items="supplierdata.length"
                                :items-source="supplierdata">
                                </wj-auto-complete>
                            </div>
                        </div>
                    </div>
                    <!-- 日付詳細 -->
                    <div class="col-md-3 col-sm-12 col-xs-12">
                        <div class="col-md-12 col-sm-12 col-xs-12 card-body">
                            <h5><u>日付詳細</u></h5>
                            <label class="col-md-12 col-sm-12 col-xs-12">登録日</label>
                            <div class="col-md-11 col-sm-12 col-xs-12">                                
                                <wj-input-date id="inputDate"
                                    :value="searchParams.created_from_date"
                                    :selected-value="searchParams.created_from_date"
                                    placeholder=" "
                                    :initialized="selectCreatedFromDate"
                                    :isRequired="false"
                                ></wj-input-date>
                            </div>
                            <label class="col-md-6 col-sm-6 col-xs-6 text-center">～</label>
                            <div class="col-md-11 col-sm-11 col-md-offset-1 col-xs-11">                                
                                <wj-input-date id="inputDate"
                                    :value="searchParams.created_to_date"
                                    :selected-value="searchParams.created_to_date"
                                    placeholder=" "
                                    :initialized="selectCreatedToDate"
                                    :isRequired="false"
                                ></wj-input-date>
                            </div>
                            <label class="col-md-12 col-sm-12 col-xs-12">更新日</label>
                            <div class="col-md-11 col-sm-12 col-xs-12">                                
                                <wj-input-date id="inputDate"
                                    :value="searchParams.updated_from_date"
                                    :selected-value="searchParams.updated_from_date"
                                    placeholder=" "
                                    :initialized="selectUpdateFromDate"
                                    :isRequired="false"
                                ></wj-input-date>
                            </div>
                            <label class="col-md-6 col-sm-6 col-xs-6 text-center">～</label>
                            <div class="col-md-11 col-sm-11 col-md-offset-1 col-xs-12">                                
                                <wj-input-date id="inputDate"
                                    :value="searchParams.updated_to_date"
                                    :selected-value="searchParams.updated_to_date"
                                    placeholder=" "
                                    :initialized="selectUpdateToDate"
                                    :isRequired="false"
                                ></wj-input-date>
                            </div>
                        </div>
                        <div class="col-md-12 col-sm-12 col-xs-12">
                            <label class="control-label">&nbsp;</label>
                            <div class="row">
                                <el-checkbox class="col-md-3" v-model="searchCheckBox.draft_flg" :true-label="FLG_ON" :false-label="FLG_OFF">本登録</el-checkbox>
                                <el-checkbox class="col-md-3" v-model="searchCheckBox.auto_flg" :true-label="FLG_ON" :false-label="FLG_OFF">1回限り登録</el-checkbox>
                            </div>
                            <label class="control-label">&nbsp;</label>
                        </div>
                    </div>
                    <!-- ボタン -->
                    <div class="col-md-12 col-sm-12 col-xs-12 text-right">
                        <button type="button" class="btn btn-primary" @click="clear">クリア</button>
                        <a type="button" class="btn btn-primary btn-new" href="/product-edit/new">新規作成</a>
                        <button type="submit" class="btn btn-primary btn-search" @click="search">検索</button>
                    </div>
                </div>
                    
                <div class="clearfix"></div>
             </form>
        </div>

        <!-- 検索結果グリッド -->
        <div class="container-fluid card-body">
            <div class="row">
                <div class="col-sm-4">
                    <div class="input-group">
                        <div class="input-group-addon">
                            <span class="glyphicon glyphicon-search"></span>
                        </div>
                        <input @input="filter($event)" class="form-control">
                    </div>
                </div>
                <div class="col-xs-8">
                    <p class="pull-right search-count" style="text-align:right;">検索結果： {{ tableData }}件</p>
                </div>
                <wj-multi-row
                    :itemsSource="products"
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
import { send } from 'q';

export default {
    data: () => ({
        loading: false,
        tableData: 0,

        searchParams: {
            class_big_id: '',
            class_middle_id: '',
            construction_id: '',
            class_small_id: '',
            product_code: '',
            product_name: '',
            model: '',
            maker_id: '',
            created_from_date: null,
            created_to_date: null,
            updated_from_date: null,
            updated_to_date: null,
        },
        searchCheckBox: {
            draft_flg: 1,
            auto_flg: 0,
        },

        products: new wjCore.CollectionView(),
        layoutDefinition: null,
        keepDOM: {},
        urlparam: '',

        gridSetting: {
            deny_resizing_col: [9, 10, 11, 12, 13],

            invisible_col: [12, 13],
        },
        gridPKCol: 12,

        wjProductGrid: null,
        wjSearchObj: {
            class_big_id: {},
            class_middle_id: {},
            construction_id: {},
            class_small_id: {},
            product_code: {},
            product_name: {},
            model: {},
            maker_id: {},
            created_from_date: {},
            created_to_date: {},
            updated_from_date: {},
            updated_to_date: {},
        },
    }),
    props: {
        classbigdata: Array,
        classmiddata: Array,
        classsmalldata: Array,
        constdata: Array,
        // productdata: Array,
        supplierdata: Array,
        initSearchParams: Object,
    },
    created: function() {
        // 初回の検索条件をセット
        // this.setInitSearchParams(this.searchParams, this.initSearchParams);

        var query = window.location.search;
        if (query.length > 1) {
            // 検索条件セット
            this.setSearchParams(query, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.created_from_date == "") { this.searchParams.created_from_date = null };
            if (this.searchParams.created_to_date == "") { this.searchParams.created_to_date = null };
            if (this.searchParams.updated_from_date == "") { this.searchParams.updated_from_date = null };
            if (this.searchParams.updated_to_date == "") { this.searchParams.updated_to_date = null };

            this.setSearchCheckBox(query, this.searchCheckBox);
        }
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        // searchParamsの値にNULLが無いなら再検索を行う。
        // for(var key in this.searchParams) {
        //     if (this.searchParams[key]) {
        //         this.search();
        //         break;
        //     }
        // }      
        // 検索条件セット
        var query = window.location.search;
        if (query.length > 1) {
            this.setSearchParams(query, this.searchParams);
            this.setSearchCheckBox(query, this.searchCheckBox);
            // 検索
            this.search();
        }
    },
    methods:{
        setSearchCheckBox(query, searchParams) {
            query = query.substring(1)
            var tmpArr = query.split('&');
            for (var i = 0; i < tmpArr.length; i++) {
                var item = tmpArr[i].split('=');
                if (item.length == 2) {
                    var srcParam = searchParams;
                    Object.keys(srcParam).forEach(function(key) {
                        if (key == item[0]) {
                            if (item[0].length >= 3 && item[0].indexOf('_flg') >= 0 
                                && item[1].length > 0 && isFinite(item[1])) {
                                srcParam[key] = parseInt(decodeURIComponent(item[1]));
                            } else {
                                srcParam[key] = 0;
                            }
                        }
                    })
                }
            }
        },
        changeIdxClassBig: function(sender){
            // 大分類選択で中分類を絞り込む
            // TODO: 大分類を選択したら工事区分絞り込み？
            //       大分類⇒中分類
            //       (大分類⇒)工事区分⇒小分類
            // 工事区分大分類マスタがないので一旦保留、　小分類と工事区分の紐付きが難しい。
            var tmpMid = this.classmiddata;
            if (sender.selectedValue) {
                tmpMid = [];
                for(var key in this.classmiddata) {
                    if (sender.selectedValue == this.classmiddata[key].class_big_id) {
                        tmpMid.push(this.classmiddata[key]);
                    }
                }             
            }
            this.wjSearchObj.class_middle_id.itemsSource = tmpMid;
            this.wjSearchObj.class_middle_id.selectedIndex = -1;
        },
        changeIdxConst: function(sender){
            // 工事区分で工程(小分類)を絞り込む
            var tmpSam = this.classsmalldata;
            if (sender.selectedValue) {
                tmpSam = [];
                for(var key in this.classsmalldata) {
                    if (sender.selectedValue == this.classsmalldata[key].construction_id) {
                        tmpSam.push(this.classsmalldata[key]);
                    }
                }             
            }
            this.wjSearchObj.class_small_id.itemsSource = tmpSam;
            this.wjSearchObj.class_small_id.selectedIndex = -1;
        },
        clear: function() {
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
            var searchCheckBox = this.searchCheckBox;
            Object.keys(searchCheckBox).forEach(function (key) {
                searchCheckBox[key] = 0;
            });
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

            this.wjProductGrid = multirow;
        },
        // 検索
        search() {
            this.loading = true
            var params = new URLSearchParams();

            params.append('class_big_id', this.rmUndefinedBlank(this.wjSearchObj.class_big_id.selectedValue));
            params.append('class_middle_id', this.rmUndefinedBlank(this.wjSearchObj.class_middle_id.selectedValue));
            params.append('class_small_id', this.rmUndefinedBlank(this.wjSearchObj.class_small_id.selectedValue));
            params.append('construction_id', this.rmUndefinedBlank(this.wjSearchObj.construction_id.selectedValue));

            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('model', this.rmUndefinedBlank(this.searchParams.model));
            params.append('maker_id', this.rmUndefinedBlank(this.wjSearchObj.maker_id.selectedValue))

            // 登録日
            params.append('created_from_date', this.rmUndefinedBlank(this.wjSearchObj.created_from_date.text));
            params.append('created_to_date', this.rmUndefinedBlank(this.wjSearchObj.created_to_date.text));
            // 更新日
            params.append('updated_from_date', this.rmUndefinedBlank(this.wjSearchObj.updated_from_date.text));
            params.append('updated_to_date', this.rmUndefinedBlank(this.wjSearchObj.updated_to_date.text));
            // チェックボックス
            params.append('draft_flg', this.rmUndefinedZero(this.searchCheckBox.draft_flg));
            params.append('auto_flg', this.rmUndefinedZero(this.searchCheckBox.auto_flg));


            axios.post('/product-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'class_big_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.class_big_id.selectedValue));
                    this.urlparam += '&' + 'class_middle_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.class_middle_id.selectedValue));
                    this.urlparam += '&' + 'class_small_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.class_small_id.selectedValue));
                    this.urlparam += '&' + 'construction_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.construction_id.selectedValue));
                    this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
                    this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
                    this.urlparam += '&' + 'model=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.model));
                    this.urlparam += '&' + 'maker_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.maker_id.selectedValue));
                    this.urlparam += '&' + 'created_from_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.created_from_date.text));
                    this.urlparam += '&' + 'created_to_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.created_to_date.text));
                    this.urlparam += '&' + 'updated_from_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.updated_from_date.text));
                    this.urlparam += '&' + 'updated_to_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.updated_to_date.text));
                    this.urlparam += '&' + 'draft_flg=' + encodeURIComponent(this.rmUndefinedBlank(this.searchCheckBox.draft_flg));
                    this.urlparam += '&' + 'auto_flg=' + encodeURIComponent(this.rmUndefinedBlank(this.searchCheckBox.auto_flg));

                    var itemsSource = [];
                    var rowIdx = 0;
                    var dataLength = 0;
                    response.data.forEach(element => {
                        // DOM生成
                        // itemFormatterでDOM要素が消えないようにする為、行ごとに生成するDOMを保存しておく必要がある
                        this.keepDOM[element.id] = {
                            price: document.createElement('div'),
                            normal_sales_price: document.createElement('div'),
                            detail_btn: document.createElement('a'),
                            edit_btn: document.createElement('a'),
                            nickname_btn: document.createElement('a'),
                            capmaign_btn: document.createElement('a'),
                        }
                        // 詳細ボタン
                        this.keepDOM[element.id].detail_btn.innerHTML = "詳細";
                        this.keepDOM[element.id].detail_btn.href = '/product-edit/' + element.id + this.urlparam;
                        this.keepDOM[element.id].detail_btn.classList.add('btn', 'btn-md', 'btn-detail', 'product-detail');

                        // 編集ボタン
                        this.keepDOM[element.id].edit_btn.innerHTML = "編集";
                        this.keepDOM[element.id].edit_btn.href = '/product-edit/'+ element.id + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].edit_btn.classList.add('btn', 'btn-md', 'btn-edit', 'product-edit');

                        // 呼び名設定ボタン
                        this.keepDOM[element.id].nickname_btn.innerHTML = "呼び名設定";
                        this.keepDOM[element.id].nickname_btn.href = '/product-nickname/'+ element.id + this.urlparam + '&mode=2';
                        this.keepDOM[element.id].nickname_btn.classList.add('btn', 'btn-md', 'btn-edit', 'product-edit');

                        // キャンペーン価格設定ボタン
                        this.keepDOM[element.id].capmaign_btn.innerHTML = "キャンペーン価格設定";
                        this.keepDOM[element.id].capmaign_btn.href = '/product-campaign-price/'+ element.id + this.urlparam;
                        this.keepDOM[element.id].capmaign_btn.classList.add('btn', 'btn-md', 'btn-edit', 'product-detail');

                        element.created_at = moment(element.created_at).format('YYYY/MM/DD HH:mm:ss');
                        element.update_at = moment(element.update_at).format('YYYY/MM/DD HH:mm:ss');

                        itemsSource.push({
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            class_big_name: element.class_big_name,
                            class_middle_name: element.class_middle_name,
                            product_code: element.product_code,
                            product_name: element.product_name,
                            supplier_name: element.supplier_name,
                            model: element.model,
                            quantity_per_case: Number(element.quantity_per_case),
                            order_lot_quantity: Number(element.order_lot_quantity),
                            stock_unit: element.stock_unit,
                            unit: element.unit,
                            min_quantity: element.min_quantity,
                            price: Number(element.price),
                            normal_purchase_price: Number(element.normal_purchase_price),
                            normal_sales_price: Number(element.normal_sales_price),
                            sales_makeup_rate: element.sales_makeup_rate + '%',
                            id: element.id,
                            created_at: element.created_at,
                            update_at: element.update_at,
                            auto_flg: element.auto_flg,
                        })

                        dataLength++;
                    });
                    // データセット
                    this.wjProductGrid.itemsSource = itemsSource;
                    this.tableData = dataLength;

                    // 設定更新
                    this.wjProductGrid = this.applyGridSettings(this.wjProductGrid);

                    // 描画更新
                    this.wjProductGrid.refresh();
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
                cell.style.textAlign = 'left';
                cell.style.backgroundColor = '';
                var dataItem = panel.rows[r].dataItem;

                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (c) {
                    case 1:
                        // 商品番号
                        if (dataItem.auto_flg === this.FLG_ON) {
                            if (r % 2 === 0) {
                                cell.style.backgroundColor = 'yellow';
                            }
                        }
                        break;
                    case 3: // 入り数／ロット数
                        cell.style.textAlign = 'right';
                        break;
                    case 4: // 単位
                        cell.style.textAlign = 'center';
                        break;
                    case 5: // 定価／仕入単価
                        cell.style.textAlign = 'right';
                        break;
                    case 6: // 標準販売単価・掛率
                        cell.style.textAlign = 'right';
                        break;
                    case 7: // 
                        cell.style.textAlign = 'right';
                        break;
                    case 8: // 登録日／更新日
                        cell.style.textAlign = 'center';
                        break;
                    case 9: // 詳細ボタン
                        cell.appendChild(this.keepDOM[this.wjProductGrid.getCellData(r, this.gridPKCol)].detail_btn);
                        break;
                    case 10: // 編集ボタン
                        if (r % 2 == 0) {
                            cell.appendChild(this.keepDOM[this.wjProductGrid.getCellData(r, this.gridPKCol)].edit_btn);
                        } else {
                            cell.appendChild(this.keepDOM[this.wjProductGrid.getCellData(r, this.gridPKCol)].nickname_btn);
                        }
                        break;
                    case 11: 
                        cell.appendChild(this.keepDOM[this.wjProductGrid.getCellData(r, this.gridPKCol)].capmaign_btn);
                        break;                    
                }
            }
        },
        filter: function(e) {
            var filter = e.target.value.toLowerCase();
            this.wjProductGrid.collectionView.filter = product => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (product.class_big_name != null && (product.class_big_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.class_middle_name != null && (product.class_middle_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.product_code != null && (product.product_code).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.product_name != null && (product.product_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.supplier_name != null && (product.supplier_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.model != null && (product.model).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.quantity != null && (product.quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.order_lot_quantity != null && (product.order_lot_quantity).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.purchase_unit != null && (product.purchase_unit).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.sales_unit != null && (product.sales_unit).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.price != null && (product.price).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.normal_purchase_price != null && (product.normal_purchase_price).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.normal_sales_price != null && (product.normal_sales_price).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.sales_makeup_rate != null && (product.sales_makeup_rate).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.created_at != null && (product.created_at).toString().toLowerCase().indexOf(filter) > -1) ||
                    (product.update_at != null && (product.update_at).toString().toLowerCase().indexOf(filter) > -1) 
                );
            };
        },
        sortProductGrid(sender){
            var sort = new wjCore.SortDescription(sender.selectedItem.item, sender.selectedItem.ordering);
            this.wjProductGrid.collectionView.sortDescriptions.clear();
            this.wjProductGrid.collectionView.sortDescriptions.push(sort);
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

        getLayout() {
            return [
                {
                    header: '分類', cells: [
                        { binding: 'class_big_name', header: '大分類', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true },
                        { binding: 'class_middle_name', header: '中分類', minWidth: GRID_COL_MIN_WIDTH, width: 120, isReadOnly: true},
                    ]
                },
                {
                    header: '商品', cells: [
                        { binding: 'product_code', header: '商品番号', minWidth: GRID_COL_MIN_WIDTH, width: 300, isReadOnly: true},
                        { binding: 'product_name', header: '商品名', minWidth: GRID_COL_MIN_WIDTH, width: 300, isReadOnly: true},
                    ]
                },
                {
                    header: 'メーカー・型式', cells: [
                        { binding: 'supplier_name', header: 'メーカー名', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },                        
                        { binding: 'model', header: '型式・規格', minWidth: GRID_COL_MIN_WIDTH, width: 200, isReadOnly: true },
                    ]
                },
                {
                    header: '入り数・ロット数', cells: [
                        { binding: 'quantity_per_case', header: '入り数', width: 80, isReadOnly: true },
                        { binding: 'order_lot_quantity', header: 'ロット数', minWidth: GRID_COL_MIN_WIDTH, width: 80, isReadOnly: true },
                    ]
                },
                {
                    header: '最小単位数量', cells: [
                        { binding: 'min_quantity', header: '最小単位数量', width: 100, isReadOnly: true },
                    ]
                },
                {
                    header: '単位', cells: [
                        { binding: 'stock_unit', header: '管理数単位', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },
                        { binding: 'unit', header: '単位', minWidth: GRID_COL_MIN_WIDTH, width: 100, isReadOnly: true },                        
                    ]
                },
                {
                    header: '単価', cells: [
                        { binding: 'price', header: '定価', width: 90, isReadOnly: true },
                        { binding: 'normal_purchase_price', header: '仕入単価', minWidth: GRID_COL_MIN_WIDTH, width: 90, isReadOnly: true },
                    ]
                },
                {
                    header: '販売単価・掛率', cells: [
                        { binding: 'normal_sales_price', header: '標準販売単価', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                        { binding: 'sales_makeup_rate', header: '販売掛率', minWidth: GRID_COL_MIN_WIDTH, width: 110, isReadOnly: true },
                    ]
                },
                {
                    header: '登録日／更新日', cells: [
                        { binding: 'created_at', header: '登録日', minWidth: GRID_COL_MIN_WIDTH, width: 170, isReadOnly: true },
                        { binding: 'update_at', header: '更新日', minWidth: GRID_COL_MIN_WIDTH, width: 170, isReadOnly: true },
                    ]
                },
                {
                    header: '詳細', cells: [
                        { header: '詳細', isReadOnly: true, width: 80, isReadOnly: true },
                    ]
                },
                {
                    header: 'マスタ登録', cells: [
                        { header: '編集', isReadOnly: true, width: 90, isReadOnly: true },
                        { header: '呼び名設定', isReadOnly: true, width: 90, isReadOnly: true },
                    ]
                },
                {
                    header: '価格設定', cells: [
                        { header: 'キャンペーン価格設定', isReadOnly: true, width: 180, isReadOnly: true },
                    ]
                },
                {
                    header: 'ID', cells: [
                        { binding: 'id', header: 'ID'},
                    ]
                },
                {
                    header: 'フラグ', cells: [
                        { binding: 'auto_flg', header: '1回限り登録フラグ', visible: false, isReadOnly: true },
                    ]
                },
            ]
        },
        selectProductCode: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.product_code = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.product_code = sender;
        },
        selectProductName: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.product_name = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.product_name = sender;
        },
        selectModel: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.model = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.model = sender;
        },
        selectMakerId: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.maker_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.maker_id = sender;
        },
        selectConstruction: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.construction_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.construction_id = sender;
        },
        selectBig: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.class_big_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.class_big_id = sender;
        },
        selectMiddle: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.class_middle_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.class_middle_id = sender;
        },
        selectSmall: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.class_small_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.class_small_id = sender;
        },
        selectCreatedFromDate: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.created_from_date = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.created_from_date = sender;
        },
        selectCreatedToDate: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.created_to_date = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.created_to_date = sender;
        },
        selectUpdateFromDate: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.updated_from_date = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.updated_from_date = sender;
        },
        selectUpdateToDate: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            // this.searchParams.updated_to_date = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
            this.wjSearchObj.updated_to_date = sender;
        },
    }
}
</script>

<style>
.card-body {
    width: 100%;
    height: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-search {
    width: 120px;
    margin-top: 0px !important;
}
.product-detail {
    display: block !important;
    width: 100%;
    height: 40px;
    text-align: center;
}
.product-edit {
    display: block !important;
    width: 100%;
    height: 23px;
    text-align: center;
    font-size: 12px;
    padding: 4px 4px;
}

.product-detail > a, .product-edit > a {
    width: 100%;
    height: 50%;
}
/*********************************
    以下wijmo系
**********************************/
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

.container-fluid .wj-multirow {
    height: 400px;
    margin: 6px 0;
}

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
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


