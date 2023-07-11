<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">
            <!-- ステップ -->
            <div class="col-md-12 subheader-step">
                <el-steps :active="activeStep" finish-status="success">
                    <el-step title="Step 1"></el-step>
                    <el-step title="Step 2"></el-step>
                    <el-step title="Step 3"></el-step>
                    <el-step title="Step 4"></el-step>
                </el-steps>
                <div v-show="activeStep == STEP.ONE">
                    <div class="col-md-2">ＳＴＥＰ１：</div>
                    <div class="col-md-10">工務店名と施主名を入力し、工事区分、仕様区分を選択、見積依頼と依頼済項目を選択しよう。<br>
                    得意先名と施主名が案件名（「得意先名（略称）_施主名等_年月」）となります。<br>
                    らくらく見積機能を使用することで設定された仕様が予め入力されます。</div>
                </div>
                <div v-show="activeStep == STEP.TWO">
                    <div class="col-md-2">ＳＴＥＰ２：</div>
                    <div class="col-md-10">依頼する項目ごとに仕様を入力してください。</div>
                </div>
                <div v-show="activeStep == STEP.THREE">
                    <div class="col-md-2">ＳＴＥＰ３：</div>
                    <div class="col-md-10">入力確認<br>
                    見積依頼の入力内容について誤りがあれば修正してください。</div>
                </div>
                <div v-show="activeStep == STEP.FOUR">
                    <div class="col-md-2">ＳＴＥＰ４：</div>
                    <div class="col-md-10">完了</div>
                </div>
            </div>
            
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="">
                <!-- エラーメッセージ表示 -->
                <div v-for="(errMsg, errIndex) in fileErrors" :key="errIndex">
                    <p class="text-danger" v-if="fileErrors[errIndex - 1] != errMsg">{{ errMsg }}</p>
                </div>

                <!-- STEP 1 -->
                <div v-show="activeStep == STEP.ONE">
                    <quoterequest-top-component
                        :main-data=mainData
                        :saved-flg=savedFlg
                        :customer-list=customerList
                        :owner-list=ownerList
                        :architecture-list=architectureList
                        :spec-list=specList
                        :qreq-list=qreqList
                        :matter-list=matterList
                        @finStep1="finStep1"
                    ></quoterequest-top-component>
                </div>
                <!-- STEP 2.3 -->
                <div v-show="(activeStep == STEP.TWO || activeStep == STEP.THREE)">
                    <quoterequest-main-component
                        :id=mainData.quote_request_id
                        :customer-id=mainData.customer_id
                        :qreq-list=qreqList
                        :select-qreq-list=selectQreqList
                        :templates=inputTemplates
                        :choice-list=choiceList
                        :is-conf=isStep3
                        :is-detail=isDetail
                        @backToStep1="backToStep1"
                        @finStep2="finStep2"
                        @backToStep2="backToStep2"
                        @finStep3="finStep3"
                        @saveDraft="saveDraft"
                        @finDefault="finDefault"
                        ref="mainComponent"
                    ></quoterequest-main-component>
                </div>
                <!-- STEP 4 -->
                <div v-show="activeStep == STEP.FOUR">
                    <quoterequest-complete-component
                        :complete-data=completeData
                    ></quoterequest-complete-component>
                </div>
            </form>
        </div>
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

        // ステップ（定数）
        STEP: {
            ONE: 0,
            TWO: 1,
            THREE: 2,
            FOUR: 3,
        },
        // ステップ表示用
        activeStep: 0,

        // 一時保存フラグ
        DRAFT_FLG: 1,

        // メインデータ
        main: {},
        // 入力フォーム
        inputTemplates: null,
        // 見積依頼項目
        selectQuoteRequest: [],
        selectQuoteRequested: [],
        // STEP2とSTEP3のタブ表示用
        selectQreqList: [QUOTE_REQ_ATTACH_TAB],
        // STEP2とSTEP3の判定用
        isStep3: false,
        // STEP4に渡すデータ格納用
        completeData: {
            matter_name: '',
            matter_no: '',
            quote_limit_date: '',
            wait_count: []
        },
        // 保存済みフラグ
        savedFlg: false,

        // 見積依頼詳細画面フラグ（quoterequest-main-componentを入力と詳細で使用）：入力画面
        isDetail: false,
        
        errors: {},
        fileErrors: [],
    }),
    props: {
        mainData: {},
        customerList: Array,
        ownerList: Array,
        architectureList: Array,
        specList: Array,
        qreqList: Array,
        matterList: Array,
        templates: Array,
        choiceList: Array
    },
    created() {
        this.inputTemplates = this.templates;
    },
    mounted() {
        if (this.rmUndefinedBlank(this.mainData.quote_request_id) != '') {
            this.savedFlg = true
        }
    },
    methods: {
        // 戻る
        back() {
            var listUrl = '/quote-request-list' + window.location.search
            location.href = listUrl
        },
        // STEP1 → STEP2
        finStep1(mainStep1, selectDialog, checkedQuoteReqArr, checkedQuoteReqedArr, refItemDataList, inputTemplate, defaultFileList) {
            this.loading = true

            this.main = mainStep1
            this.selectQuoteRequest = checkedQuoteReqArr
            this.selectQuoteRequested = checkedQuoteReqedArr
            if (this.rmUndefinedBlank(inputTemplate) != '') {
                this.inputTemplates = inputTemplate;
            }

            // 初期化
            this.selectQreqList = []

            // 見積依頼項目を配列化
            var quoteReqArr = [];
            var qreqItems = this.qreqList;
            qreqItems.forEach(function(val) {
                quoteReqArr[val.construction_id] = val.construction_name;
            })
            // construction_id順にソート
            // checkedQuoteReqArr.sort(function (a, b) {
            //     return a - b;
            // })
            // タブ表示用に見積依頼項目の表示順に並び替え
            var checkedQreqArr = [];
            this.qreqList.forEach((item) => {
                for (var chkId in checkedQuoteReqArr) {
                    if (item.construction_id == checkedQuoteReqArr[chkId]) {
                        checkedQreqArr.push(item.construction_id);
                        break;
                    }
                }
            })
            
            // チェックが付いた見積依頼項目のconstruction_idとconstruction_nameをそれぞれ1次元配列に格納
            var tmpArrVal = []
            var tmpArrTxt = []
            var cnt = 0
            checkedQreqArr.forEach(function(constructionId) {
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
            if (defaultFileList !== null) {
                this.main.file_list = null;
                if (defaultFileList.length != 0) {
                    this.main.file_list = defaultFileList;
                }
            }

            this.$refs.mainComponent.setData(refItemDataList, this.main.file_list)

            this.activeStep = this.STEP.TWO
            this.loading = false
        },
        // STEP2 → STEP1
        backToStep1() {
            this.activeStep = this.STEP.ONE
            if (this.rmUndefinedBlank(this.main.quote_request_flg) != '') {
                this.savedFlg = true
            }
        },
        // STEP2 → STEP3
        finStep2() {
            this.activeStep = this.STEP.THREE
            this.isStep3 = true
        },
        // STEP3 → STEP2
        backToStep2() {
            this.activeStep = this.STEP.TWO
            this.isStep3 = false
        },
        saveDraft(inputData, uploadFileList, deleteFileList) {
            this.finStep3(inputData, uploadFileList, deleteFileList, this.DRAFT_FLG)
        },
        finDefault(inputData, uploadFileList, deleteFileList) {
            this.loading = true

            // エラーメッセージ初期化
            this.fileErrors = [];
            
            // 入力値を取得
            var params = new FormData();
            params.append('quote_request_id', this.rmUndefinedBlank(this.main.quote_request_id));
            params.append('customer_id', this.rmUndefinedBlank(this.main.customer_id));
            params.append('quote_request_kbn', JSON.stringify(this.rmUndefinedBlank(this.selectQuoteRequest)));
            params.append('builder_standard_flg', this.rmUndefinedBlank(this.main.builder_standard_flg));
            params.append('use_ref_quote_flg', this.rmUndefinedBlank(this.main.use_ref_quote_flg));
            params.append('ref_matter_no', this.rmUndefinedBlank(this.main.ref_matter_no));
            params.append('ref_quote_no', this.rmUndefinedBlank(this.main.ref_quote_no));
            params.append('delete_files', this.rmUndefinedBlank(deleteFileList));

            // 添付ファイル
            for (var i = 0; i < uploadFileList.length; i++) {
                params.append('upload_file_' + i, uploadFileList[i].file);
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

            var saveUrl = '/quote-request-edit/save-default'

            // 保存処理
            axios.post(saveUrl, params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    // リダイレクトせずにメッセージ出力して画面に留まる（No.134）
                    alert(MSG_SUCCESS_SAVE_CUSTOMER_DEFAULT);
                    // location.reload();
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
                    Object.keys(errItems).forEach(function(key) {
                        errItems[key].forEach((val) => {
                            errList.push(val)
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
        // STEP3 → STEP4（保存）
        finStep3(inputData, uploadFileList, deleteFileList,  status) {
            this.loading = true

            // エラーメッセージ初期化
            this.fileErrors = [];
            
            // 入力値を取得
            var params = new FormData();
            params.append('quote_request_id', this.rmUndefinedBlank(this.main.quote_request_id));
            params.append('matter_no', this.rmUndefinedBlank(this.main.matter_no));
            params.append('matter_name', this.rmUndefinedBlank(this.main.matter_name));
            params.append('customer_id', this.rmUndefinedBlank(this.main.customer_id));
            params.append('owner_name', this.rmUndefinedBlank(this.main.owner_name.text));
            params.append('quote_limit_date', this.rmUndefinedBlank(this.main.quote_limit_date));
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
                params.append('upload_file_' + i, uploadFileList[i].file);
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

            var saveUrl = '/quote-request-edit/save'
            if (status == this.DRAFT_FLG) {
                saveUrl = '/quote-request-edit/save-draft'
            }

            // 保存処理
            axios.post(saveUrl, params, {headers: {'Content-Type': 'multipart/form-data'}})
            .then( function (response) {
                this.loading = false

                if (response.data.status) {
                    // 成功
                    if (status == this.DRAFT_FLG) {
                        // 一時保存の場合、一覧画面に遷移
                        this.savedFlg = true
                        this.main.quote_request_id = response.data.data.quote_request_id
                        // var listUrl = '/quote-request-edit/' + this.main.quote_request_id
                        // location.href = listUrl
                        var listUrl = '/quote-request-list' + window.location.search
                        location.href = listUrl
                    } else {
                        this.activeStep = this.STEP.FOUR
                        // 戻りデータセット
                        this.completeData.matter_name = response.data.data.matter_name
                        this.completeData.matter_no = response.data.data.matter_no
                        this.completeData.quote_limit_date = response.data.data.quote_limit_date
                        this.completeData.wait_count = response.data.data.wait_count
                    }
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
                    Object.keys(errItems).forEach(function(key) {
                        errItems[key].forEach((val) => {
                            errList.push(val)
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

    },
};

</script>
