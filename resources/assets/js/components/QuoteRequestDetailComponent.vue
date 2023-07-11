<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">

            <div class="col-md-12">
                <label class="col-md-3">案件番号： {{ main.matter_no }}</label>
                <label class="col-md-3">案件名： {{ main.matter_name }}</label>
                <label class="col-md-3">施主名： {{ main.owner_name }}</label>
            </div>

            <div class="col-md-12">
                <label class="col-md-3">得意先名： {{ main.customer_name }}</label>
                <label class="col-md-3">建築種別： {{ main.architecture_type_text }}</label>
                <div class="col-md-4">
                    <label class="control-label">見積提出期限</label>
                    <wj-input-date
                        :value="main.quote_limit_date"
                        :isRequired=false
                        :isDisabled="isConf"
                        :initialized="initLimitDate"
                    ></wj-input-date>
                    <label class="text-danger">{{ errors.quote_limit_date }}</label>
                </div>
                <!-- <label class="col-md-4">見積提出期限： {{ main.quote_limit_date|date_format }}</label> -->
            </div>
            
            <div class="col-md-12">
                <label class="col-md-3">仕様区分： {{ main.spec_kbn_text }}</label>
                <label class="col-md-offset-1 col-md-3" v-show="main.semi_fireproof_area_flg">省令準耐火</label>
            </div>
                
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="">
                <!-- エラーメッセージ表示 -->
                <div v-for="(errMsg, errIndex) in fileErrors" :key="errIndex">
                    <p class="text-danger" v-if="fileErrors[errIndex - 1] != errMsg">{{ errMsg }}</p>
                </div>

                <quoterequest-main-component
                    :id=mainData.quote_request_id
                    :qreq-list=qreqList
                    :select-qreq-list=selectQreqList
                    :templates=lclTemplates
                    :choice-list=lclChoiceList
                    :is-conf=isConf
                    :is-detail=isDetail
                    @saveDetail="saveDetail"
                    @showDlgAddRequest="showDlgAddRequest"
                    ref="mainComponent"
                ></quoterequest-main-component>
            </form>
        </div>
        <!-- 項目追加ダイアログ -->
        <el-dialog title="項目追加" :visible.sync="showDlgAddRequestKbn">
            <el-checkbox-group v-model="addRequestKbnList">
                <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" :disabled="selectQuoteRequest.indexOf(row.construction_id) >= 0" >{{ row.construction_name }}</el-checkbox>
            </el-checkbox-group>
            <span slot="footer" class="dialog-footer">
                <el-button @click="addRequestKbn" class="btn-create">追加</el-button>
                <el-button @click="showDlgAddRequestKbn = false" class="btn-cancel">キャンセル</el-button>
            </span>
        </el-dialog>
    </div>
</template>

<style>
dl.rakuraku-box {
    display: block;
    height: 350px;
    padding: 10px;
    border: 2px solid #ccd0d2;
    background-color: #fff;

}
dl.rakuraku-box dt {
    padding-left: 15px;
}
dl.rakuraku-box dd {
    padding-left: 20px;
}
.in-el-radio-group{
    font-size: 14px;
}
</style>

<script>
export default {
    data: () => ({
        loading: false,
        showDlgAddRequestKbn: false,

        // メインデータ
        main: {},
        limitDate: {},
        // 見積依頼項目
        selectQuoteRequest: [],
        selectQuoteRequested: [],
        addRequestKbnList: [],
        // タブ表示用
        selectQreqList: [QUOTE_REQ_ATTACH_TAB],
        
        // 確認画面フラグ
        isConf: false,
        // 保存済みフラグ
        savedFlg: true,
        // 見積依頼詳細画面フラグ（quoterequest-main-componentを入力と詳細で使用）：詳細画面
        isDetail: true,

        lclTemplates: [],
        lclChoiceList: [],
        
        errors: {
            quote_limit_date: '',
        },
        fileErrors: [],
    }),
    props: {
        mainData: {},
        pathList: {},
        qreqList: Array,
        qreqDataList: Array,
        templates: Array,
        choiceList: Array
    },
    created() {
        this.lclTemplates = this.templates;
        this.lclChoiceList = this.choiceList;
    },
    mounted() {
        this.init()
    },
    methods: {
        // 項目追加ダイアログを開く
        showDlgAddRequest() {
            this.addRequestKbnList = [];
            this.showDlgAddRequestKbn = true;
        },
        // 選択した依頼項目を追加する
        addRequestKbn() {
            // this.loading = true;
            // 見積依頼項目を配列化
            var quoteReqArr = [];
            var qreqItems = this.qreqList;
            qreqItems.forEach(function(val) {
                quoteReqArr[val.construction_id] = val.construction_name;
            })

            // チェックが付いた見積依頼項目のconstruction_idとconstruction_nameをそれぞれ1次元配列に格納
            var tmpArrVal = []
            var tmpArrTxt = []
            var cnt = 0

            this.addRequestKbnList.forEach(function(constructionId) {
                tmpArrVal[cnt] = constructionId
                tmpArrTxt[cnt] = quoteReqArr[constructionId]
                cnt++
            })
            // オブジェクト化
            for(var i = 0; i<tmpArrVal.length; i++) {
                this.selectQreqList.push({
                    construction_id: tmpArrVal[i],
                    construction_name: tmpArrTxt[i],
                })

                this.selectQuoteRequest.push(tmpArrVal[i]);
            }
            // エラーの初期化
            this.initErr(this.errors);
            
            // 入力値を取得
            var params = new URLSearchParams();
            params.append('quote_request_kbn', JSON.stringify(this.rmUndefinedBlank(this.selectQuoteRequest)));

            axios.post('/quote-request-edit/get-template-data', params)
            .then( function (response) {
                // this.loading = false

                if (response.data) {
                    // 成功
                    // 入力フォームテンプレートデータ
                    this.lclTemplates = response.data.templates;
                    this.lclChoiceList = response.data.choiceList;
                } else {
                    // 失敗
                    alert(MSG_ERROR)
                }
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

            this.showDlgAddRequestKbn = false;
        },
        // 初期表示（タブ作成）
        init() {
            this.main = this.mainData
            this.selectQuoteRequest = this.mainData.quote_request_kbn_arr
            this.selectQuoteRequested = this.mainData.quote_requested_kbn_arr

            // 初期化
            this.selectQreqList = []

            // 見積依頼項目を配列化
            var quoteReqArr = [];
            var qreqItems = this.qreqList;
            qreqItems.forEach(function(val) {
                quoteReqArr[val.construction_id] = val.construction_name;
            })

            // チェックが付いた見積依頼項目のconstruction_idとconstruction_nameをそれぞれ1次元配列に格納
            var tmpArrVal = []
            var tmpArrTxt = []
            var cnt = 0
            this.mainData.quote_request_kbn_arr.forEach(function(constructionId) {
                tmpArrVal[cnt] = constructionId
                tmpArrTxt[cnt] = quoteReqArr[constructionId]
                cnt++
            })
            // オブジェクト化
            for(var i = 0; i<tmpArrVal.length; i++) {
                this.selectQreqList.push({
                    construction_id: tmpArrVal[i],
                    construction_name: tmpArrTxt[i],
                })
            }
            // 必ず表示するタブを追加
            this.selectQreqList.push(QUOTE_REQ_ATTACH_TAB)

            // mainComponentのsetDataメソッドを呼び出してデータをセット
            this.$refs.mainComponent.setData(this.qreqDataList, this.mainData.file_list)

            // 初期表示タブの指定確認
            const TAB_PARAM = 'tabid=';
            var item = 0;
            var query = window.location.search
            query = query.substring(1) // ?除去
            var tmpArr = query.split('&');
            for (var i = 0; i < tmpArr.length; i++) {
                if (tmpArr[i].indexOf(TAB_PARAM) >= 0) {
                    var items = tmpArr[i].split('=');
                    item = parseInt(items[1]);
                    break;
                }
            }
            this.$nextTick(function() {
                if (item != 0) {
                    // mainComponentのtabclickメソッドを呼び出し
                    this.$refs.mainComponent.tabclick(item)
                }
            });
        },
        // 保存
        saveDetail(inputData, uploadFileList, deleteFileList) {
            this.loading = true

            // エラーメッセージ初期化
            this.initErr(this.errors);
            this.fileErrors = [];
            
            // 入力値を取得
            var params = new FormData();
            params.append('quote_request_id', this.rmUndefinedBlank(this.main.quote_request_id));
            params.append('matter_no', this.rmUndefinedBlank(this.main.matter_no));
            params.append('matter_name', this.rmUndefinedBlank(this.main.matter_name));
            params.append('customer_id', this.rmUndefinedBlank(this.main.customer_id));
            params.append('owner_name', this.rmUndefinedBlank(this.main.owner_name));
            params.append('quote_limit_date', this.rmUndefinedBlank(this.limitDate.text));
            params.append('architecture_type', this.rmUndefinedBlank(this.main.architecture_type));
            params.append('spec_kbn', this.rmUndefinedBlank(this.main.spec_kbn));
            params.append('semi_fireproof_area_flg', this.rmUndefinedBlank(this.main.semi_fireproof_area_flg));
            params.append('builder_standard_flg', this.rmUndefinedBlank(this.main.builder_standard_flg));
            params.append('use_ref_quote_flg', this.rmUndefinedBlank(this.main.use_ref_quote_flg));
            params.append('ref_matter_no', this.rmUndefinedBlank(this.main.ref_matter_no));
            params.append('ref_quote_no', this.rmUndefinedBlank(this.main.ref_quote_no));
            params.append('quote_request', this.rmUndefinedBlank(this.selectQuoteRequest));
            params.append('quote_requested', this.rmUndefinedBlank(this.selectQuoteRequested));
            params.append('delete_files', this.rmUndefinedBlank(deleteFileList));

            // 添付ファイル
            for (var i = 0; i < uploadFileList.length; i++) {
                if (this.rmUndefinedBlank(uploadFileList[i].file) != '') {
                    params.append('upload_file_' + i, uploadFileList[i].file);
                }
            }

            // 可変項目
            var tmpInputKeyList = [];
            var tmpInputValList = [];
            var cnt = 0;
            Object.keys(inputData).forEach(function(key) {
                tmpInputKeyList[cnt] = key;
                tmpInputValList[cnt] = inputData[key];
                cnt = cnt + 1;
            })
            for (var i = 0; i < tmpInputKeyList.length; i++) {
                params.append(tmpInputKeyList[i], this.rmUndefinedBlank(tmpInputValList[i]));
            }

            // 保存処理
            axios.post('/quote-request-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                this.loading = false

                if (response.data.status) {
                    // 成功
                    var listUrl = '/quote-request-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    alert(MSG_ERROR)
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    // this.showErrMsg(error.response.data.errors, this.errors)

                    // エラーを配列に格納
                    var errList = this.fileErrors;
                    var errItems = error.response.data.errors;
                    var errors = this.errors;
                    var limitDateCtrl = this.limitDate;
                    Object.keys(errItems).forEach(function(key) {
                        errItems[key].forEach((val) => {
                            if (key == 'quote_limit_date') {
                                errors.quote_limit_date = val;
                                limitDateCtrl.focus();
                            } else {
                                errList.push(val)
                            }
                        })
                    })
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
        initLimitDate(sender) {
            this.limitDate = sender;
        },
    },
};

</script>
