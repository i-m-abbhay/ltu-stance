<template>
    <div>
        <loading-component :loading="loading" />

        <comment-modal
            v-bind:show="commentModal.show"
            v-bind:header="commentModal.header"
            v-on:onCommentModal="onCommentModal(false)"
        >
            <div slot="body">
                <el-input
                    v-model="commentModal.params.comment"
                    type="textarea"
                    rows=10
                    resize=none
                    placeholder="コメントを入力"
                    :readonly="commentModal.readonly">
                </el-input>
                <p class="text-danger">{{ commentModal.errors.comment }}</p>
            </div>
            <button slot="footer" v-if="!commentModal.readonly" type="button" class="btn btn-save" @click="judge">登録</button>
        </comment-modal>

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
                        <label>見積番号</label>
                        <input type="text" class="form-control" v-model="searchParams.quote_no">
                    </div>
                    <div class="col-sm-2">
                        <label>得意先別特別単価の使用</label>
                        <div class="col-sm-12">
                            <el-radio v-model="searchParams.special_flg_id" :label="FLG_ON">あり</el-radio>
                            <el-radio v-model="searchParams.special_flg_id" :label="FLG_OFF">なし</el-radio>
                        </div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm-5">
                        <label>見積作成日</label>
                        <div class="input-group">
                            <wj-input-date class="form-control"
                                :value="searchParams.create_date_from"
                                :selected-value="searchParams.create_date_from"
                                :initialized="initQuoteCreateDateFrom"
                                :isRequired=false
                            ></wj-input-date>
                            <span class="input-group-addon lbl-addon-ex">～</span>
                            <wj-input-date class="form-control"
                                :value="searchParams.create_date_to"
                                :selected-value="searchParams.create_date_to"
                                :initialized="initQuoteCreateDateTo"
                                :isRequired=false
                            ></wj-input-date>
                        </div>
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
                    <div class="col-sm-2">
                        <label>見積項目</label>
                        <wj-auto-complete class="form-control" id="acQuoteItem"
                            search-member-path="construction_name"
                            display-member-path="construction_name"
                            selected-value-path="construction_id"
                            :initialized="initQuoteItem"
                            :selected-index="-1"
                            :selected-value="searchParams.quote_item_id"
                            :is-required="false"
                            :max-items="quoteItemData.length"
                            :items-source="quoteItemData">
                        </wj-auto-complete>
                    </div>
                </div>
                <br>
                <div class="row row-center-item">
                    <div class="col-sm-12">
                        <button type="submit" class="btn btn-search btn-md">見積検索</button>
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
                    <div class="col-sm-4">
                        <el-checkbox v-model="searchParams.fil_chk_latest_only_id" :true-label="1" :false-label="0" @input="filter()">最新見積のみを表示</el-checkbox>
                        <el-checkbox v-model="searchParams.fil_chk_not_approved_only_id" :true-label="1" :false-label="0" @input="filter()">未承認のみを表示</el-checkbox>
                    </div>
                    <div class="col-sm-8">
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
import CommentModal from './CommentModal.vue';
// import ReportComponent from './ActiveReportsComponent.vue';

export default {
    components: { CommentModal },
    // components: {  },
    data: () => ({
        loading: false,
        tableData: 0,
        urlparam: '',
        queryparam: '',
        FLG_ON: 1,
        FLG_OFF: 0,
        commentModal: {
            show: false,
            readonly: false,
            header: null,
            params: {
                is_approval: null,
                process_id: null,
                comment: null,
            },
            errors: {
                comment: '',
            },
        },
        layoutDefinition: null,
        // keepDOM: {},
        // グリッド設定用
        gridSetting: {
            // リサイジング不可[ 承認、詳細、印刷 ]
            deny_resizing_col: [ 'approval', 'btn_detail', 'btn_print'],
            // 非表示[ ID ]
            invisible_col: [ 'hid_id', 'hid_quote_item', 'hid_attached_documents', 'hid_approval', 'hid_is_latest', 'hid_is_not_approved' ],
        },
        searchParams: {
            matter_no: null,
            matter_name: null,
            quote_no: null,
            customer_id: null,
            department_id: null,
            staff_id: null,
            special_flg_id: 0,
            create_date_from: null,
            create_date_to: null,
            quote_item_id: null,
            // ↓IDではないが、検索条件復帰の共通関数に通すため『_id』
            fil_chk_latest_only_id: 0,
            fil_chk_not_approved_only_id: 0,
        },
        clearSearchParams: {},

        hidGridData: Object,

        // 以下,initializedで紐づける変数
        wjQuoteGrid: null,
        wjSearchObj: {
            matter_no: {},
            matter_name: {},
            quote: {},
            customer: {},
            department: {},
            staff: {},
            create_date_from: {},
            create_date_to: {},
            quote_item: {},
        },

        tooltip : new wjCore.Tooltip(),
    }),
    props: {
        // quoteData: Array,
        matterData: Array,
        customerData: Array,
        departmentData: Array,
        staffData: Array,
        staffDepartmentData: Object,
        quoteItemData: Array,
        initSearchParams: {
            type: Object,
            staff_id: Number,
            department_id: Number,
        },
    },
    created: function() {
        // クリアボタン用
        this.clearSearchParams = Vue.util.extend({}, this.searchParams);

        // created(vue)⇒initialized(wijmo)⇒mouted(vue)の順で実行される
        // 検索条件復帰はcreatedで行う。又はinitializedでsender.selectdValueにsearchParamsの値を直接指定する
        // 再検索はmountedでやる必要がある。 ※createdやmountedで値のセットと検索の両方を行うとwijmoのオブジェクトが正しく動かない
        this.queryparam = window.location.search;
        if (this.queryparam.length > 1) {
            // 検索条件セット
            this.setSearchParams(this.queryparam, this.searchParams);
            // 日付に復帰させる検索条件がない場合はnullをセット
            if (this.searchParams.create_date_from == "") { this.searchParams.create_date_from = null };
            if (this.searchParams.create_date_to == "") { this.searchParams.create_date_to = null };
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
        if (this.queryparam.length > 1) {
            this.search();
        }
    },
    methods: {
        onCommentModal: function(dialogVisible) {
            this.commentModal.show = dialogVisible;
        },
        initCustomer: function(sender){
            this.wjSearchObj.customer = sender;
        },
        initMatterNo: function(sender){
            this.wjSearchObj.matter_no = sender;
        },
        initMatterName: function(sender){
            this.wjSearchObj.matter_name = sender;
        },
        // initQuote: function(sender){
        //     this.wjSearchObj.quote = sender;
        // },
        initDepartment: function(sender){
            this.wjSearchObj.department = sender;
        },
        initStaff: function(sender){
            this.wjSearchObj.staff = sender;
        },
        initQuoteCreateDateFrom: function(sender){
            this.wjSearchObj.create_date_from = sender;
        },
        initQuoteCreateDateTo: function(sender){
            this.wjSearchObj.create_date_to = sender;
        },
        initQuoteItem: function(sender){
            this.wjSearchObj.quote_item = sender;
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

            this.wjQuoteGrid = multirow;
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
                var colName = this.wjQuoteGrid.getBindingColumn(panel, r, c).name;

                // スタイルリセット
                cell.style.color = '';
                cell.style.textAlign = '';

                // c（0始まり）
                // 例1：1列目(c=0)を非表示にしている場合は『case 0:～』と書いたとしてその中に入ることはない。
                // 例2：1列目(c=0)が何らかの理由で隠れている場合(横スクロールして1列目が見えていない等)は『case 0:～』と書いたとしてその中に入ることはない。
                switch (colName) {
                    case 'sales_total':     // 見積金額
                    case 'profit_total':    // 粗利額
                    case 'profit_per':      // 粗利率
                        cell.style.textAlign = 'right';
                        break;
                    case 'quote_item':      // 見積項目
                        var quoteItem = this.hidGridData[panel.rows[r].dataItem.id].quote_item;
                        quoteItem.forEach(value => {
                            var elem = document.createElement('svg');
                            elem.innerHTML = '<use xlink:href="#' + value.icon_info.icon + '" />';
                            elem.classList.add('quote-icon', value.icon_info.class);
                            cell.innerHTML += elem.outerHTML + '&nbsp;';
                        });
                        break;
                    case 'attached_documents': // 添付書類
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
                    case 'approval': // 承認、差戻ボタン
                        var approval = this.hidGridData[panel.rows[r].dataItem.id].approval;
                        if (approval) {
                            var contentElem = document.createElement('div');
                            contentElem.classList.add('btn-toolbar', 'approval-col');
                            // 承認、差戻履歴
                            if (approval.histories) {
                                approval.histories.forEach(value => {
                                    var examNameElem = document.createElement('div');
                                    examNameElem.innerHTML = value.staff_name;
                                    examNameElem.dataset.comment = value.comment;
                                    examNameElem.classList.add('approval-exam-name', value.class);

                                    var examAtElem = document.createElement('div');
                                    examAtElem.innerHTML = value.date;
                                    examAtElem.classList.add('approval-exam-at');

                                    contentElem.innerHTML += examNameElem.outerHTML + examAtElem.outerHTML;
                                })
                            }
                            // 差戻、承認ボタン
                            if (approval.judge_btn.show) {
                                var disabled = (approval.judge_btn.is_valid)? '' : ' disabled ';
                                var dataAttr = {
                                    is_approval: this.FLG_OFF,
                                    process_id: panel.rows[r].dataItem.id,
                                };

                                contentElem.innerHTML += '<div class="btn-group"><button data-workflow=' + JSON.stringify(dataAttr) + ' class="btn btn-sm btn-sendback ' + disabled + '">' + '差戻' + '</button></div>';
                                dataAttr.is_approval = this.FLG_ON;
                                contentElem.innerHTML += '<div class="btn-group"><button data-workflow=' + JSON.stringify(dataAttr) + ' class="btn btn-sm btn-approval' + disabled + '">' + '承認' + '</button></div>';
                            }
                            // 以下、承認列のイベント追加
                            const showCommentModal = (workflow) => {
                                var data = JSON.parse(workflow);
                                this.initErr(this.commentModal.errors);
                                this.commentModal.params.comment = '';
                                this.commentModal.params.is_approval = data.is_approval;
                                this.commentModal.params.process_id = data.process_id;

                                this.commentModal.header = '承認コメント（任意）';
                                if (!data.is_approval) {
                                    this.commentModal.header = "差戻コメント（必須）";
                                }
                                this.commentModal.readonly = false;
                                this.commentModal.show = true;
                            };
                            const showHistoryModal = (comment) => {
                                this.initErr(this.commentModal.errors);
                                this.commentModal.params.comment = comment;
                                this.commentModal.header = 'コメント';
                                this.commentModal.readonly = true;
                                this.commentModal.show = true;
                            };
                            contentElem.addEventListener('click', function(e) {
                                // 承認,差戻のボタンを押下されたか？
                                if (e.target.dataset.workflow) {
                                    showCommentModal(e.target.dataset.workflow);
                                }else if (e.target.dataset.comment) {
                                    showHistoryModal(e.target.dataset.comment)
                                }
                            });
                            cell.appendChild(contentElem);
                        }
                        break;
                    case 'btn_detail': // 詳細ボタン
                        var elem = document.createElement('a');
                        elem.innerHTML = '詳細';
                        var matterId = this.hidGridData[panel.rows[r].dataItem.id].matter_id;
                        var quoteVersion = this.hidGridData[panel.rows[r].dataItem.id].quote_version;
                        elem.href = '/quote-edit/' + matterId + this.urlparam +
                            '&fil_chk_latest_only_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.fil_chk_latest_only_id)) +
                            '&fil_chk_not_approved_only_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.fil_chk_not_approved_only_id)) +
                            '&url=/quote-list&' + QUOTE_VERSION_TAB_PARAM + quoteVersion;
                        elem.classList.add('btn', 'bnt-sm', 'btn-detail', 'quote-detail');
                        cell.appendChild(elem);
                        break;
                    case 'btn_print': // 印刷ボタン
                        var elem = document.createElement('a');
                        elem.innerHTML = '印刷';
                        elem.target = '_blank';
                        elem.href = '/quote-report/print/' + panel.rows[r].dataItem.id;
                        elem.classList.add('btn', 'bnt-sm', 'btn-print', 'quote-print');
                        cell.appendChild(elem);
                        break;
                }
            }
        },
        // フィルター
        filter: function() {
            this.wjQuoteGrid.collectionView.filter = quote => {
                var showQuote = true;
                if (this.searchParams.fil_chk_latest_only_id && !this.hidGridData[quote.id].is_latest) {
                    showQuote = false;
                }
                if (this.searchParams.fil_chk_not_approved_only_id && !this.hidGridData[quote.id].is_not_approved) {
                    showQuote = false
                }
                return showQuote;
            };
        },
        // 検索条件クリア(searchParamsの値を変更しても1回目しかリセットが反応しない為wijmoの値を変更する)
        clearSearch() {
            
            this.setInitSearchParams(this.searchParams, this.clearSearchParams);

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
            // 見積番号
            // params.append('quote_no', this.rmUndefinedZero(this.wjSearchObj.quote.selectedValue));
            params.append('quote_no', this.rmUndefinedZero(this.searchParams.quote_no));
            // 得意先別特別単価の使用
            params.append('special_flg', this.rmUndefinedZero(this.searchParams.special_flg_id));
            // 部門ID
            params.append('department_id', this.rmUndefinedZero(this.wjSearchObj.department.selectedValue));
            // 担当者ID
            params.append('staff_id', this.rmUndefinedZero(this.wjSearchObj.staff.selectedValue));
            // 見積依頼作成日（FROM-TO）
            params.append('create_date_from', this.rmUndefinedBlank(this.wjSearchObj.create_date_from.text));
            params.append('create_date_to', this.rmUndefinedBlank(this.wjSearchObj.create_date_to.text));
            // 見積項目
            params.append('quote_item', this.rmUndefinedZero(this.wjSearchObj.quote_item.selectedValue));

            axios.post('/quote-list/search', params)

            .then( function (response) {
                if (response.data) {
                    // URLパラメータ作成
                    this.urlparam = '?'
                    this.urlparam += 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer.selectedValue));
                    this.urlparam += '&' + 'matter_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
                    this.urlparam += '&' + 'matter_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_name.selectedValue));
                    // this.urlparam += '&' + 'quote_no=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.quote.selectedValue));
                    this.urlparam += '&' + 'quote_no=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.quote_no));
                    this.urlparam += '&' + 'special_flg_id=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.special_flg_id));
                    this.urlparam += '&' + 'create_date_from=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.create_date_from.text));
                    this.urlparam += '&' + 'create_date_to=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.create_date_to.text));
                    this.urlparam += '&' + 'department_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.department.selectedValue));
                    this.urlparam += '&' + 'staff_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.staff.selectedValue));
                    this.urlparam += '&' + 'quote_item_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.quote_item.selectedValue));

                    var itemsSource = [];
                    response.data.forEach(element => {
                        this.hidGridData[element.id] = {
                            matter_id: element.matter_id,
                            quote_version: element.quote_version,
                            quote_item: element.quote_item,
                            attached_documents: element.attached_documents,
                            approval: element.approval,
                            is_latest: element.isLatest,
                            is_not_approved: element.isNotApproved,
                        };
                        itemsSource.push({
                            matter_no: element.matter_no,
                            matter_name: element.matter_name,
                            department_name: element.department_name,
                            staff_name: element.staff_name,
                            create_date: element.create_date,
                            quote_no: element.quote_no + '-' + element.quote_version,
                            sales_total: element.sales_total,
                            profit_total: element.profit_total,
                            profit_per: element.profit_per,
                            id: element.id,
                        })
                    });
                    // データセット
                    this.wjQuoteGrid.itemsSource = itemsSource;
                    this.filter();
                    this.tableData = itemsSource.length;

                    // 設定更新
                    this.applyGridSettings(this.wjQuoteGrid);

                    // 描画更新
                    this.wjQuoteGrid.refresh();
                }
                this.loading = false
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
                    window.onbeforeunload = null;
                    location.reload()
                }
                this.loading = false
            }.bind(this))
        },
        // 添付ファイルダウンロード
        async downloadFile(quoteVerId, fileName) {
            var existsUrl = '/quote-edit/exists/' + quoteVerId + '/' + encodeURIComponent(fileName);
            var result = await this.existsFile(existsUrl);
            if (result != undefined && result) {
                var downloadUrl = '/quote-edit/download/' + quoteVerId + '/' + encodeURIComponent(fileName);
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
        judge() {
            this.loading = true;

            // エラーの初期化
            this.initErr(this.commentModal.errors);

            var params = new URLSearchParams();
            params.append('is_approval', this.rmUndefinedZero(this.commentModal.params.is_approval));
            params.append('process_id', this.rmUndefinedZero(this.commentModal.params.process_id));
            params.append('comment', this.rmUndefinedBlank(this.commentModal.params.comment));
            axios.post('/quote-list/approval/judge', params)
            .then( function (response) {
                if (response.data) {
                    if (response.data.status == true) {
                        // 成功
                        this.commentModal.show = false;
                        this.search();
                    }else{
                        if(response.data.msg){
                            alert(response.data.msg);
                        }else{
                            alert(MSG_ERROR);
                        }
                        this.loading = false
                    }
                } else {
                    // 失敗
                    alert(MSG_ERROR)
                    this.loading = false
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.commentModal.errors)
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
                    { name: 'matter_no', binding: 'matter_no', header: '案件番号', isReadOnly: true, width: 250, minWidth: GRID_COL_MIN_WIDTH },
                    { name: 'matter_name', binding: 'matter_name', header: '案件名', isReadOnly: true, width: 250, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'department_name', binding: 'department_name', header: '部門', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                    { name: 'staff_name', binding: 'staff_name', header: '担当者', isReadOnly: true, width: 150, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'create_date', binding: 'create_date', header: '見積作成日', isReadOnly: true, width: 120, minWidth: GRID_COL_MIN_WIDTH },
                    { name: 'quote_no', binding: 'quote_no', header: '見積番号', isReadOnly: true, width: 120, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'sales_total', binding: 'sales_total', header: '見積金額', isReadOnly: true, width: 110, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'profit_total', binding: 'profit_total', header: '粗利額', isReadOnly: true, width: 110, minWidth: GRID_COL_MIN_WIDTH },
                    { name: 'profit_per', binding: 'profit_per', header: '粗利率', isReadOnly: true, width: 110, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'quote_item', header: '見積項目', isReadOnly: true, wordWrap: true, width: 400, minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'attached_documents', header: '添付書類', isReadOnly: true, wordWrap: true, width: '*', minWidth: GRID_COL_MIN_WIDTH },
                ]},
                { cells: [
                    { name: 'approval', header: '承認', isReadOnly: true, width: 120 },
                ]},
                { cells: [
                    { name: 'btn_detail', header: '詳細', isReadOnly: true, width: 80 },
                ]},
                { cells: [
                    { name: 'btn_print', header: '印刷', isReadOnly: true, width: 80 },
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
/* グリッド */
.wj-multirow {
    height: 835px;
    margin: 6px 0;
}

/*********************************
    グリッド各項目
**********************************/
/* 見積項目,添付書類 */
.quote-icon, .attached-document-icon{
    width: 30px;
    height: 30px;
    color:#000000;
}
.quote-icon.editing, .quote-icon.sendback{
    color:#FF3B30;
}
.quote-icon.applying{
    color:#5AC8FA;
}
.quote-icon.approved{
    color:#4CD964;
}
.attached-document-icon{
    cursor: pointer;
}
/* 詳細,印刷列 */
.quote-detail, .quote-print{
    display: block !important;
    width: 100%;
    height: 40px;
    line-height: 30px;
    text-align: center;
}
.quote-detail > a, .quote-print > a {
    width: 100%;
    height: 100%;;
}
/* 承認列 */
.approval-col, .approval-history{
    display: block !important;
}
.approval-exam-name,
.approval-exam-at{
    display: block !important;
    text-align: center;
}
.approval-exam-name{
    cursor: pointer;
    color: #ffffff;
    font-size: 12px;
}
.approval-exam-name.approved{
    background-color: #5CB85C;
}
.approval-exam-name.sendback{
    background-color: #CB2E25;
}
.btn-group{
    width:45%;
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