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
                        <label>案件登録日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="searchParams.matter_create_date_from"
                                :selected-value="searchParams.matter_create_date_from"
                                :initialized="initMatterCreateDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="searchParams.matter_create_date_to"
                                :selected-value="searchParams.matter_create_date_to"
                                :initialized="initMatterCreateDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
                    </div>
                    <div class="col-sm-5">
                        <label>住所</label>
                        <wj-auto-complete class="form-control" id="acAddress"
                            search-member-path="address"
                            display-member-path="address"
                            selected-value-path="address"
                            :initialized="initAddress"
                            :selected-index="-1"
                            :selected-value="searchParams.address"
                            :is-required="false"
                            :max-items="addressData.length"
                            :items-source="addressData">
                        </wj-auto-complete>
                    </div>
                </div>
                <br>
                <div class="row row-center-item">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-search btn-md">案件検索</button>
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
                        <button v-if="hasMatterAuth==FLG_ON" type="button" class="btn btn-csv btn-md pull-right" @click="downloadCSV">CSV出力</button>
                    </div>
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
        urlparam: '',
        queryParam: '',
        filterText: '',
        layoutDefinition: null,
        // クエリパラメータ復帰時,初回表示にしか使うな（created⇒initialized以降値を変更してもwijmoに反映されたりされなかったり）
        searchParams: {
            matter_no: null,
            matter_name: null,
            customer_id: null,
            department_id: null,
            staff_id: null,
            matter_create_date_from: null,
            matter_create_date_to: null,
            address: null,
        },

        hidGridData: Object,

        // グリッド設定等
        gridSetting: {
            // リサイジング不可[ 案件番号,見積依頼,見積,発注,詳細 ]
            deny_resizing_col: [ 'matter_no', 'quote_request', 'quote', 'order', 'btn_edit', 'btn_detail' ],
            // 非表示[ ID, 案件登録日 ]
            invisible_col: [ 'hid_id' ],
        },
        gridPKCol: 9,
        // 以下,initializedで紐づける変数
        wjMatterGrid: null,
        wjSearchObj: {
            matter_no: {},
            matter_name: {},
            customer: {},
            department: {},
            staff: {},
            matter_create_date_from: {},
            matter_create_date_to: {},
            address: {},
        },
    }),
    props: {
        matterData: Array,
        customerData: Array,
        departmentData: Array,
        staffData: Array,
        staffDepartmentData: Object,
        addressData: Array,
        hasMatterAuth: Number,
        initSearchParams: {
            type: Object,
            staff_id: Number,
            department_id: Number,
            matter_create_date_from: String,
            matter_create_date_to: String,
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
            if (this.searchParams.matter_create_date_from == "") { this.searchParams.matter_create_date_from = null };
            if (this.searchParams.matter_create_date_to == "") { this.searchParams.matter_create_date_to = null };
        }else{
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
        // 「init～」wijmo初期処理
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
        initMatterCreateDateFrom: function(sender){
            this.wjSearchObj.matter_create_date_from = sender;
        },
        initMatterCreateDateTo: function(sender){
            this.wjSearchObj.matter_create_date_to = sender;
        },
        initAddress: function(sender){
            this.wjSearchObj.address = sender;
        },
        initMultiRow: function(multirow) {
            multirow.itemsSource = new wjCore.CollectionView();
            // 行高さ
            multirow.rows.defaultSize = 30;
            // 行ヘッダ非表示
            multirow.headersVisibility = wjGrid.HeadersVisibility.Column;
            // 設定更新
            this.applyGridSettings(multirow);

            this.wjMatterGrid = multirow;
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
                var colName = this.wjMatterGrid.getBindingColumn(panel, r, c).name;

                // スタイルリセット
                cell.style.color = '';
                cell.style.textAlign = '';

                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (colName) {
                    case 'address': // 住所ボタン
                        var elem = document.createElement('a');
                        elem.innerHTML = '住所登録';
                        elem.href = '/address-edit/' + panel.rows[r].dataItem.id + this.urlparam + '&url=/matter-list'
                        elem.style.display = 'inherit';
                        elem.classList.add('btn', 'bnt-sm', 'btn-new');
                        cell.appendChild(elem);
                        break;
                    case 'quote_request': // 進捗状況（見積依頼）
                        var quoteRequestStatus = this.hidGridData[panel.rows[r].dataItem.id].quote_request_status;
                        var elem = document.createElement('a');
                        elem.innerHTML = quoteRequestStatus.text;
                        elem.href = '/quote-request-edit/' + quoteRequestStatus.id + this.urlparam + '&url=/matter-list';
                        elem.classList.add(quoteRequestStatus.class, 'quote-request-status');
                        cell.innerHTML = elem.outerHTML;
                        break;
                    case 'quote': // 進捗状況（見積）
                        var quoteStatus = this.hidGridData[panel.rows[r].dataItem.id].quote_status;
                        var elem = document.createElement('a');
                        elem.innerHTML = quoteStatus.text;
                        elem.href = '/quote-edit/' + panel.rows[r].dataItem.id + this.urlparam + '&url=/matter-list';
                        elem.classList.add(quoteStatus.class, 'quote-status');
                        cell.innerHTML = elem.outerHTML;
                        break;
                    case 'order': // 進捗状況（発注）
                        var orderStatus = this.hidGridData[panel.rows[r].dataItem.id].order_status;
                        var elem = document.createElement('a');
                        elem.innerHTML = orderStatus.text;
                        elem.href = '/order-edit/' + panel.rows[r].dataItem.id + this.urlparam + '&url=/matter-list';
                        elem.classList.add(orderStatus.class, 'order-status');
                        cell.innerHTML = elem.outerHTML;
                        break;
                    case 'btn_edit': // 編集ボタン
                        // 編集
                        var elem = document.createElement('a');
                        elem.innerHTML = '編集';
                        elem.href = '/matter-edit/' + panel.rows[r].dataItem.id + '/' + this.urlparam + '&url=/matter-list';
                        elem.classList.add('btn', 'bnt-sm', 'btn-edit', 'matter-edit');
                        cell.appendChild(elem);
                        break;
                    case 'btn_detail': // 詳細ボタン
                        // 詳細ボタン
                        var elem = document.createElement('a');
                        elem.innerHTML = '詳細';
                        elem.href = '/matter-detail/' + panel.rows[r].dataItem.id + '/' + this.urlparam + '&url=/matter-list';
                        elem.classList.add('btn', 'bnt-sm', 'btn-detail', 'matter-detail');
                        cell.appendChild(elem);
                        break;
                }
            }
        },
        // フィルター
        filter() {
            var filter = this.filterText.toLowerCase();
            this.wjMatterGrid.collectionView.filter = matter => {
                return (
                    // toLowerCaseは文字列が対象の為、NULLの除外と要素の文字キャスト
                    filter.length == 0 ||
                    (matter.matter_no != null && (matter.matter_no).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.matter_name != null && (matter.matter_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.customer_name != null && (matter.customer_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.department_name != null && (matter.department_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.staff_name != null && (matter.staff_name).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.address != null && (matter.address).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.quote_request != null && (matter.quote_request).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.quote != null && (matter.quote).toString().toLowerCase().indexOf(filter) > -1) ||
                    (matter.order != null && (matter.order).toString().toLowerCase().indexOf(filter) > -1)
                );
            };
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
            // 案件登録日（FROM-TO）
            params.append('matter_create_date_from', this.rmUndefinedBlank(this.wjSearchObj.matter_create_date_from.text));
            params.append('matter_create_date_to', this.rmUndefinedBlank(this.wjSearchObj.matter_create_date_to.text));
            // 住所ID
            params.append('address', this.rmUndefinedBlank(this.wjSearchObj.address.text));
            
            axios.post('/matter-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
                    this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue));
                    this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue));
                    this.urlparam += '&' + 'staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue));
                    this.urlparam += '&' + 'matter_create_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_create_date_from.text));
                    this.urlparam += '&' + 'matter_create_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_create_date_to.text));
                    this.urlparam += '&' + 'address=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.address.text));

                    var itemsSource = [];
                    var dataCount = 0;
                    response.data.forEach(element => {
                        this.hidGridData[element.id] = {
                            quote_request_status: element.quote_request_status,
                            quote_status: element.quote_status,
                            order_status: element.order_status,
                        };

                        dataCount++;
                        itemsSource.push({
                            matter_no: element.matter_no,
                            matter_name: element.matter_name,
                            customer_name: element.customer_name,
                            department_name: element.department_name,
                            staff_name: element.staff_name,
                            address: element.address,
                            quote_request: element.quote_request_status.text,
                            quote: element.quote_status.text,
                            order: element.order_status.text,
                            id: element.id,
                        })
                    });
                    // データセット
                    this.wjMatterGrid.itemsSource = itemsSource;
                    this.filter();
                    this.tableData = dataCount;

                    // 設定更新
                    this.applyGridSettings(this.wjMatterGrid);
                    // 描画更新
                    this.wjMatterGrid.refresh();
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
        // CSVダウンロード
        downloadCSV: function() {
            // グリッドの表示されているデータ取得
            var data = this.wjMatterGrid.itemsSource;
            if (this.tableData == 0) {
                alert(MSG_ERROR_NO_DATA);
                return false;
            }

            var params = new URLSearchParams();
            var matterIdList = [];
            for (var i = 0; i < data.length; i++) {
                matterIdList.push(this.rmUndefinedBlank(data[i].id));
                // params.append('matter_id[' + i + ']', this.rmUndefinedBlank(data[i].id));
            }
            params.append('matter_id', JSON.stringify(matterIdList));

            axios.post('/matter-list/download', params, {responseType: 'blob' })
            .then( function (response) {
                // ContentDispositionからファイル名取得
                const contentDisposition = response.headers['content-disposition'];
                const regex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = regex.exec(contentDisposition);
                var filename = '';
                if (matches != null && matches[1]) {
                    const name = matches[1].replace(/['"]/g, '');
                    filename = decodeURI(name)
                } else {
                    filename = null;
                }

                // CSVファイルのダウンロード
                const url = URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', filename); 
                document.body.appendChild(link);
                link.click();
                URL.revokeObjectURL(link);
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                }
                this.loading = false
            }.bind(this))
        },
        // グリッドレイアウト
        getLayout() {
            return [
                { cells: [
                    { name: 'matter_no', binding: 'matter_no', header: '案件番号', isReadOnly: true, width: 120 },
                ]},
                { cells: [
                    { name: 'matter_name', binding: 'matter_name', header: '案件名', isReadOnly: true, width: 250 },
                ]},
                { cells: [
                    { name: 'customer_name', binding: 'customer_name', header: '得意先名', isReadOnly: true, width: 200 },
                ]},
                { cells: [
                    { name: 'department_name', binding: 'department_name', header: '部門', isReadOnly: true, width: 150 },
                    { name: 'staff_name', binding: 'staff_name', header: '担当者', isReadOnly: true, width: 150 },
                ]},
                { cells: [
                    { name: 'address', binding: 'address', header: '住所', isReadOnly: true, width:'*' },
                ]},
                { colspan:3, cells: [
                    // { header: '進捗状況', colspan:3 },
                    { name: 'quote_request', binding: 'quote_request', header: '見積依頼', isReadOnly: true, width: 90 },
                    { name: 'quote', binding: 'quote', header: '見積', isReadOnly: true, width: 90 },
                    { name: 'order', binding: 'order', header: '発注', isReadOnly: true, width: 90 },
                ]},
                { cells: [
                    { name: 'btn_edit', header: '編集', isReadOnly: true, width: 90 },
                ]},
                { cells: [
                    { name: 'btn_detail', header: '詳細', isReadOnly: true, width: 90 },
                ]},
                /* 以降、非表示カラム */
                { cells: [
                    { name: 'hid_id', binding: 'id', header: 'ID' },
                ]},
            ]
        }
    }
};
</script>

<style>
.quote-request-status, .quote-status, .order-status{
    display: block !important;
    text-decoration:none !important;
    color:#FFFFFF;
    height:50px;
    width:100%;
    line-height: 50px;
    text-align: center;
}
.quote-request-status:visited, .quote-status:visited, .order-status:visited,
.quote-request-status:hover, .quote-status:hover, .order-status:hover{
    color: #FFFFFF;
}
.not-implemented{
    pointer-events: none;
    background-color:#666666;
}
.not-treated{
    background-color:#FF3B30;
}
.halfway{
    background-color:#5AC8FA;
}
.complete{
    background-color:#4CD964;
}
.matter-edit, .matter-detail{
    display: block !important;
    width: 100%;
    height: 40px;
    line-height: 30px;
    text-align: center;
}

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

.container-fluid .wj-multirow {
    height: 800px;
    margin: 6px 0;
}
/*********************************
    その他
**********************************/
.row-center-item{
    text-align: center;
}
.lbl-addon-ex{
    border: none;
    background: none;
}
</style>