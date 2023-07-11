<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 検索条件 -->
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                <div class="row">
                    <div class="col-sm-3">
                        <label>得意先名</label>
                        <wj-auto-complete class="form-control" id="acCustomer"
                            search-member-path="customer_name"
                            display-member-path="customer_name"
                            selected-value-path="id"
                            :initialized="initCustomer"
                            :selectedIndexChanged="changeIdxCustomer"
                            :selected-index="-1"
                            :selected-value="searchParams.customer_id"
                            :is-required="false"
                            :max-items="customerData.length"
                            :items-source="customerData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>案件番号</label>
                        <wj-auto-complete class="form-control" id="acMatterNo"
                            search-member-path="matter_no"
                            display-member-path="matter_no"
                            selected-value-path="matter_no"
                            :initialized="initMatterNo"
                            :selected-value="searchParams.matter_no"
                            :is-required="false"
                            :max-items="matterData.length"
                            :items-source="matterData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-3">
                        <label>案件名　</label>
                        <wj-auto-complete class="form-control" id="acMatterName"
                            search-member-path="matter_name"
                            display-member-path="matter_name"
                            selected-value-path="matter_no"
                            :initialized="initMatterName"
                            :selected-index="-1"
                            :selected-value="searchParams.matter_name"
                            :is-required="false"
                            :max-items="matterData.length"
                            :items-source="matterData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>部門名</label>
                        <wj-auto-complete class="form-control" id="acDepartment"
                            search-member-path="department_name"
                            display-member-path="department_name"
                            selected-value-path="id"
                            :initialized="initDepartment"
                            :selectedIndexChanged="changeIdxDepartment"
                            :selected-index="-1"
                            :selected-value="searchParams.department_id"
                            :is-required="false"
                            :max-items="departmentData.length"
                            :items-source="departmentData">
                        </wj-auto-complete>
                    </div>
                    <div class="col-sm-2">
                        <label>担当者名</label>
                        <wj-auto-complete class="form-control" id="acStaff"
                            search-member-path="staff_name"
                            display-member-path="staff_name"
                            selected-value-path="id"
                            :initialized="initStaff"
                            :selected-index="-1"
                            :selected-value="searchParams.staff_id"
                            :is-required="false"
                            :max-items="staffData.length"
                            :items-source="staffData">
                        </wj-auto-complete>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>見積依頼日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="searchParams.request_date_from"
                                :selected-value="searchParams.request_date_from"
                                :initialized="initQuoteRequestCreateDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="searchParams.request_date_to"
                                :selected-value="searchParams.request_date_to"
                                :initialized="initQuoteRequestCreateDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                    <div class="col-sm-2">
                        <label>見積提出期限日</label>
                        <wj-input-date class="form-control"
                            :value="searchParams.quote_limit_date"
                            :selected-value="searchParams.quote_limit_date"
                            :initialized="initQuoteLimitDate"
                            :isRequired=false
                        ></wj-input-date>
                    </div>
                    <div class="col-sm-2">
                        <label>見積依頼項目</label>
                        <wj-auto-complete class="form-control" id="acQuoteRequestKbn"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="construction_id"
                            :initialized="initQuoteRequestKbn"
                            :selected-index="-1"
                            :selected-value="searchParams.quote_request_kbn_id"
                            :is-required="false"
                            :max-items="quoteRequestKbnData.length"
                            :items-source="quoteRequestKbnData">
                        </wj-auto-complete>
                    </div>
                </div>
                <br>
                <div class="row row-center-item">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-search btn-md">見積依頼検索</button>
                        &emsp;
                        <button type="button" class="btn btn-clear btn-md" v-on:click="clearSearch">クリア</button>
                    </div>
                </div>
                <div class="clearfix"></div>
            </form>
        </div>
        <br>
        <!-- 検索結果グリッド -->
        <div class="col-sm-12 result-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-12">
                        <p class="col-md-12 col-xs-12 pull-right search-count">検索結果：{{ tableData }}件</p>
                    </div>
                </div>
                <wj-multi-row
                    class="multirow-nonscrollbar"
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
        tableData: 0,
        layoutDefinition: null,
        urlparam: '',
        queryParam: '',
        // グリッド設定用
        gridSetting: {
            // リサイジング不可[ 作成状況,依頼内容,見積作成 ]
            deny_resizing_col: ['creation_status', 'request_content', 'create_quote'],
            // 非表示[ ID ]
            invisible_col: ['hid_id'],
        },
        searchParams: {
            matter_no: null,
            matter_name: null,
            customer_id: null,
            department_id: null,
            staff_id: null,
            request_date_from: null,
            request_date_to: null,
            quote_limit_date: null,
            quote_request_kbn_id: null,
        },
        
        hidGridData: Object,

        // 以下,initializedで紐づける変数
        wjQuoteRequestGrid: null,
        wjSearchObj: {
            matter_no: {},
            matter_name: {},
            customer: {},
            department: {},
            staff: {},
            request_date_from: {},
            request_date_to: {},
            quote_limit_date: {},
            quote_request_kbn: {},
        },

        tooltip : new wjCore.Tooltip(),
    }),
    props: {
        matterData: Array,
        customerData: Array,
        departmentData: Array,
        staffData: Array,
        staffDepartmentData: Object,
        quoteRequestKbnData: Array,
        initSearchParams: {
            type: Object,
            staff_id: Number,
            department_id: Number,
            request_date_from: String,
            request_date_to: String,
        },
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
            if (this.searchParams.request_date_from == "") { this.searchParams.request_date_from = null };
            if (this.searchParams.request_date_to == "") { this.searchParams.request_date_to = null };
            if (this.searchParams.quote_limit_date == "") { this.searchParams.quote_limit_date = null };
        } else {
            // 初回の検索条件をセット
            this.setInitSearchParams(this.searchParams, this.initSearchParams);
        }
        this.layoutDefinition = this.getLayout();
    },
    mounted: function() {
        var tmpStaff = this.wjSearchObj.staff.selectedValue;
        this.wjSearchObj.department.onSelectedIndexChanged();
        this.wjSearchObj.staff.selectedValue = tmpStaff;
        
        if (this.queryParam.length > 1) {
            this.search();
        }
    },
    methods: {
        initCustomer: function(sender){
            this.wjSearchObj.customer = sender;
        },
        initMatterNo: function(sender){
            this.wjSearchObj.matter_no = sender;
        },
        initMatterName: function(sender){
            this.wjSearchObj.matter_name = sender;
        },
        initDepartment: function(sender){
            this.wjSearchObj.department = sender;
        },
        initStaff: function(sender){
            this.wjSearchObj.staff = sender;
        },
        initQuoteRequestCreateDateFrom: function(sender){
            this.wjSearchObj.request_date_from = sender;
        },
        initQuoteRequestCreateDateTo: function(sender){
            this.wjSearchObj.request_date_to = sender;
        },
        initQuoteLimitDate: function(sender){
            this.wjSearchObj.quote_limit_date = sender;
        },
        initQuoteRequestKbn: function(sender){
            this.wjSearchObj.quote_request_kbn = sender;
        },
        initMultiRow: function(multirow) {
            multirow.itemsSource = new wjCore.CollectionView();
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            this.applyGridSettings(multirow);
            // 高さ自動調整
            multirow.autoRowHeights = true,

            this.wjQuoteRequestGrid = multirow;
        },
        changeIdxCustomer: function(sender){
            // 得意先を変更したら案件名を絞り込む
            var tmpMatter = this.matterData;
            if (sender.selectedValue) {
                tmpMatter = [];
                for(var key in this.matterData) {
                    if (sender.selectedValue == this.matterData[key].customer_id) {
                        tmpMatter.push(this.matterData[key]);
                    }
                }             
            }
            this.wjSearchObj.matter_name.itemsSource = tmpMatter;
            this.wjSearchObj.matter_name.selectedIndex = -1;
        },
        changeIdxDepartment: function(sender){
            this.wjSearchObj.department = sender;
            // 部門を変更したら担当者を絞り込む
            var tmpStaff = this.staffData;
            if (sender.selectedValue) {
                tmpStaff = [];
                if (this.staffDepartmentData[sender.selectedValue]) {
                    tmpStaff = this.staffData.filter(rec => {
                        return (this.staffDepartmentData[sender.selectedValue].indexOf(rec.id) !== -1)
                    });
                }
            }
            this.wjSearchObj.staff.itemsSource = tmpStaff;
            this.wjSearchObj.staff.selectedIndex = -1;
        },
        itemFormatter: function (panel, r, c, cell) {
            // 列ヘッダのセンタリング
            if (panel.cellType == wjGrid.CellType.ColumnHeader) {
                cell.style.textAlign = 'center';
            }

            if (panel.cellType == wjGrid.CellType.Cell) {
                var colName = this.wjQuoteRequestGrid.getBindingColumn(panel, r, c).name;

                // スタイルリセット
                cell.style.color = '';
                cell.style.textAlign = '';

                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (colName) {
                    case 'quote_request_kbn': // 見積依頼項目
                        var quoteRequestKbn = this.hidGridData[panel.rows[r].dataItem.id].quote_request_kbn;
                        quoteRequestKbn.forEach(value => {
                            var elem = document.createElement('svg');
                            elem.innerHTML = '<use xlink:href="#' + value.icon + '" />';
                            elem.classList.add('request-item-icon', value.class);
                            cell.innerHTML += elem.outerHTML + '&nbsp;';
                        });
                        break;
                    case 'attached_documents': // 添付ファイル
                        var attachedDocuments = this.hidGridData[panel.rows[r].dataItem.id].attached_documents;
                        var seqNo = 0;
                        attachedDocuments.forEach(value => {
                            var elem = document.createElement('svg');
                            elem.id = 'icon_' + panel.rows[r].dataItem.id + '_' + seqNo;
                            elem.innerHTML = '<use xlink:href="#' + value.icon + '" />';
                            elem.classList.add('attached-document-icon');
                            this.$nextTick(function() {
                                // 要素が生成されてない時がある為チェック
                                if (document.getElementById(elem.id)) {
                                    this.tooltip.setTooltip(
                                        '#' + elem.id,
                                        value.filename,
                                    )
                                }
                            });

                            var elemSvgLbl = document.createElement('label');
                            elemSvgLbl.innerHTML += elem.outerHTML + '&nbsp;';
                            elemSvgLbl.addEventListener('click', function(e){
                                this.downloadFile(panel.rows[r].dataItem.id, value.filename);
                            }.bind(this));
                            cell.appendChild(elemSvgLbl);
                            seqNo++;
                        });
                        break;
                    case 'creation_status': // 作成状況
                        var creationStatus = this.hidGridData[panel.rows[r].dataItem.id].creation_status;
                        var elem = document.createElement('div');
                        elem.innerHTML = creationStatus.text;
                        elem.classList.add(creationStatus.class, 'creation-status');
                        cell.appendChild(elem);
                        break;
                    case 'btn_request_content': // 依頼内容
                        var elem = document.createElement('a');
                        elem.innerHTML = "詳細";
                        elem.href = '/quote-request-edit/' +  panel.rows[r].dataItem.id + this.urlparam + '&url=/quote-request-list';;
                        elem.classList.add('btn', 'bnt-sm', 'btn-detail', 'quote-request-detail');
                        cell.appendChild(elem);
                        break;
                    case 'btn_create_quote': // 見積作成
                        // 見積作成ボタン
                        var matterId = this.hidGridData[panel.rows[r].dataItem.id].matter_id;
                        var btnCreateQuote = this.hidGridData[panel.rows[r].dataItem.id].elem_ctrl.btn_create_quote;
                        var elem = document.createElement('a');
                        elem.innerHTML = "見積作成";
                        elem.href = '/quote-edit/' + matterId + this.urlparam + '&url=/quote-request-list';
                        elem.classList.add('btn', 'bnt-sm', 'btn-new', 'quote-request-new-quote');
                        if (btnCreateQuote.disabled) {
                            elem.classList.add('disabled');
                        }
                        cell.appendChild(elem);
                        break;
                }
            }
        },
        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clearSearch() {
            var wjSearchObj = this.wjSearchObj;
            Object.keys(wjSearchObj).forEach(function (key) {
                wjSearchObj[key].selectedValue = null;
                wjSearchObj[key].value = null;
            });
        },
        // 検索
        search() {
            this.loading = true

            var params = new URLSearchParams();

            // 得意先ID
            params.append('customer_id', this.rmUndefinedZero(this.wjSearchObj.customer.selectedValue));
            // 案件ID [ 案件番号 案件名 ]
            var matterNo = "";
            if (this.wjSearchObj.matter_no.selectedValue) {
               matterNo = this.wjSearchObj.matter_no.selectedValue;
            } else if(this.wjSearchObj.matter_name.selectedValue) {
               matterNo = this.wjSearchObj.matter_name.selectedValue;
            }
            params.append('matter_no', this.rmUndefinedZero(matterNo));
            // 部門ID
            params.append('department_id', this.rmUndefinedZero(this.wjSearchObj.department.selectedValue));
            // 担当者ID
            params.append('staff_id', this.rmUndefinedZero(this.wjSearchObj.staff.selectedValue));
            // 見積依頼作成日（FROM-TO）
            params.append('request_date_from', this.rmUndefinedBlank(this.wjSearchObj.request_date_from.text));
            params.append('request_date_to', this.rmUndefinedBlank(this.wjSearchObj.request_date_to.text));
            // 見積期限日
            params.append('quote_limit_date', this.rmUndefinedBlank(this.wjSearchObj.quote_limit_date.text));
            // 見積依頼区分
            params.append('quote_request_kbn', this.rmUndefinedZero(this.wjSearchObj.quote_request_kbn.selectedValue));
            axios.post('/quote-request-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
                    this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue));
                    this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue));
                    this.urlparam += '&' + 'staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue));
                    this.urlparam += '&' + 'request_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.request_date_from.text));
                    this.urlparam += '&' + 'request_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.request_date_to.text));
                    this.urlparam += '&' + 'quote_limit_date=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.quote_limit_date.text));
                    this.urlparam += '&' + 'quote_request_kbn_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.quote_request_kbn.selectedValue));

                    var itemsSource = [];
                    response.data.forEach(element => {
                        this.hidGridData[element.id] = {
                            matter_id: element.matter_id,
                            quote_request_kbn: element.quote_request_kbn,
                            attached_documents: element.attached_documents,
                            creation_status: element.creation_status,
                            elem_ctrl: element.elem_ctrl,
                        };
                        itemsSource.push({
                            // フィルター機能で参照される為quote_request,quote,orderにはtext(未実施等...)をセット
                            // itemFormatterでDOM要素を書き換えてもフィルター機能でヒットするのはitemsSourceにセットした時の値
                            matter_no: element.matter_no,
                            matter_name: element.matter_name,
                            department_name: element.department_name,
                            staff_name: element.staff_name,
                            request_date: element.request_date,
                            quote_limit_date: element.quote_limit_date,
                            id: element.id,
                            quote_request_kbn: element.quote_request_kbn,
                            attached_documents: element.attached_documents,
                            creation_status: element.creation_status,
                        })
                    });
                    // データセット
                    this.wjQuoteRequestGrid.itemsSource = itemsSource;
                    this.tableData = itemsSource.length;

                    // 設定更新
                    this.applyGridSettings(this.wjQuoteRequestGrid);

                    // 描画更新
                    this.wjQuoteRequestGrid.refresh();
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
        // 添付ファイルダウンロード
        async downloadFile(quoteVerId, fileName) {
            var existsUrl = '/quote-request-edit/exists/' + quoteVerId + '/' + encodeURIComponent(fileName);
            var result = await this.existsFile(existsUrl);
            if (result != undefined && result) {
                var downloadUrl = '/quote-request-edit/download/' + quoteVerId + '/' + encodeURIComponent(fileName);
                location.href = downloadUrl;
            }
        },
        existsFile(url){
            var promise = axios.get(url)
            .then( function (response) {
                if (response.data) {
                    // 成功
                    return response.data;
                } else {
                    // 失敗
                    alert(MSG_ERROR_NOT_EXISTS_FILE);
                }
            }.bind(this))
            .catch(function (error) {
                alert(MSG_ERROR); 
            }.bind(this))
            return promise;
        },
        // グリッドに設定適用（itemsSource更新時に設定が消えるもののみ）
        applyGridSettings(grid) {
            // リサイジング設定
            grid.columns.forEach(element => {
                if (element.name != undefined && this.gridSetting.deny_resizing_col.indexOf(element.name) >= 0) {
                    element.allowResizing = false;
                }
            });
            // 非表示設定
            grid.columns.forEach(element => {
                if (element.name != undefined && this.gridSetting.invisible_col.indexOf(element.name) >= 0) {
                    element.visible = false;
                }
            });
        },
        // グリッドレイアウト
        getLayout() {
            return [
                { cells: [
                    { name: 'matter_no', binding: 'matter_no', header: '案件番号', isReadOnly: true, width: 120, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'matter_name', binding: 'matter_name', header: '案件名', isReadOnly: true, wordWrap: true, width: 250, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'department_name', binding: 'department_name', header: '部門', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                    { name: 'staff_name', binding: 'staff_name', header: '担当者', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'request_date', binding: 'request_date', header: '見積依頼日', isReadOnly: true, width: 140, minWidth: GRID_COL_MIN_WIDTH },
                    { name: 'quote_limit_date', binding: 'quote_limit_date', header: '見積提出期限日', isReadOnly: true, width: 140, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'quote_request_kbn', header: '見積依頼項目', isReadOnly: true, wordWrap: true, width: 400, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'attached_documents', header: '添付書類', isReadOnly: true, wordWrap: true, width: '*', minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'creation_status', header: '作成状況', isReadOnly: true, width: 90  },
                ]},
                { cells: [
                    { name: 'btn_request_content', header: '依頼内容', isReadOnly: true, width: 90 },
                ]},
                { cells: [
                    { name: 'btn_create_quote', header: '見積作成', isReadOnly: true, width: 90 },
                ]},
                /* 以降、非表示カラム */
                { cells: [
                    { name: 'hid_id', binding: 'id' },
                ]},
            ]
        }
    }
};
</script>

<style>
/*********************************
    枠サイズ等
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
.wj-multirow {
    height: 835px;
    margin: 6px 0;
}
/*********************************
    グリッド各項目
**********************************/
/** 見積依頼項目 */
.request-item-icon, .attached-document-icon{
    width: 30px;
    height: 30px;
    color:#000000;
}
.request-item-icon.not-treated{
    color:#FF3B30;
}
.request-item-icon.making{
    color:#5AC8FA;
}
.request-item-icon.complete{
    color:#4CD964;
}
.attached-document-icon{
     cursor: pointer;
 }
/** 作成状況 */
.creation-status{
    display: block !important;
    height:50px;
    width:100%;
    line-height: 50px;
    text-align: center
}
div.not-treated, div.making, div.editing, div.complete {
    color: #FFFFFF;
}
div.not-treated{
    background-color:#FF3B30;
}
div.making{
    background-color:#5AC8FA;
}
div.editing{
    background-color:#707070;
}
div.complete{
    background-color:#4CD964;
}
/* 依頼内容,見積作成 */
.quote-request-detail, .quote-request-new-quote{
    display: block !important;
    width: 100%;
    height: 40px;
    line-height: 30px;
    text-align: center;
}
.quote-request-detail > a, .quote-request-new-quote > a {
    width: 100%;
    height: 100%;;
}
/*********************************
    その他
**********************************/
.lbl-addon-ex{
    border: none;
    background: none;
}
.search-count{
    text-align: right;
}
.row-center-item{
    text-align: center;
}
</style>