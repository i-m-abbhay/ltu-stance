<template>
    <div>
        <div class="col-md-12">
            <el-tabs type="border-card">
                <el-tab-pane v-for="(row, tabNo) in selectQreqList" :key="row.construction_id" :label="row.construction_name">

                    <div v-for="(item, index) in templates" :key="index">
                        <div v-if="row.construction_id == item.quote_request_kbn">
                            
                            <!-- 項目グループ名表示部分 -->
                            <div class="col-md-12" v-if="(item.item_group != '') && (index != 0) && (prevItemGroup[index-1] != item.item_group)">
                                <label class="control-label">{{ item.item_group }}</label>
                            </div>
                            <div style="display:none;">{{ prevItemGroup[index] = item.item_group }}</div>

                            <!-- テキストボックス -->
                            <div class="col-md-6 qreq-space qreq-height" v-if="item.item_type == ITEM_TYPE.TEXT" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <!-- <label class="control-label">{{ item.item_front_label }}</label>
                                <input type="text" :placeholder="item.placeholder" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:readonly="isConf">
                                <label class="control-label">{{ item.item_back_label }}</label> -->

                                <label class="inp">
                                    <input type="text" v-bind:id="item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" class="form-control" placeholder=" " v-bind:readonly="isConf">
                                    <span v-bind:for="item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id" class="label"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</span>
                                    <span class="border"></span>
                                </label>
                                <label class="control-label qreq-back-label">{{ item.item_back_label }}</label>
                            </div>
                            
                            <!-- テキストエリア -->
                            <div class="col-md-12 qreq-space" v-if="item.item_type == ITEM_TYPE.TEXTAREA" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <label class="control-label txtarea-label"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</label>
                                <textarea class="qreq-textarea-init" :placeholder="item.placeholder" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:readonly="isConf"></textarea>
                                <label class="control-label">{{ item.item_back_label }}</label>
                            </div>
                            <!-- セレクトボックス -->
                            <div class="col-md-6 qreq-space qreq-height" v-if="item.item_type == ITEM_TYPE.SELECT" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <label class="control-label"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</label>
                                <select v-if="(index != 0) && (prevSelectbox[index-1] != item.detail_id)" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:readonly="isConf">
                                    <option v-for="choice in choiceList" :key="item.detail_id + '_' + choice.id" v-show="sqeezeChoiceList(choice, item)" :value="item.detail_id + '_' + choice.id" v-bind:disabled="isConf">{{ choice.choice_name }}</option>
                                </select>
                                <label class="control-label">{{ item.item_back_label }}</label>
                            </div>
                            <div style="display:none;">{{ prevSelectbox[index] = item.detail_id }}</div>
                            <!-- チェックボックス -->
                            <div class="col-md-6 qreq-space qreq-height" v-if="item.item_type == ITEM_TYPE.CHECK" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <label class="control-label" v-bind:for="item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</label>
                                <!-- <el-checkbox class="chkbox-space" label="1" true-label="1" false-label="0" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:disabled="isConf" @change="changeCheckbox">{{ item.item_back_label }}</el-checkbox> -->
                                <input type="checkbox" class="chkbox-space"  true-value='1' false-value='0' v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:disabled="isConf" v-bind:id="item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id">
                                <label v-bind:for="item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id">{{ item.item_back_label }}</label>
                            </div>
                            <!-- ラジオボタン -->
                            <div class="col-md-12 qreq-space" v-if="item.item_type == ITEM_TYPE.RADIO" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <label class="control-label"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</label>
                                <div v-for="choice in choiceList" :key="item.detail_id + '_' + choice.id">
                                    <div class="col-md-12" v-if="item.choice_keyword == choice.choice_keyword">
                                        <input type="radio" :name="item.detail_id" :id="item.detail_id + '_' + choice.id" :value="choice.id" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:disabled="isConf"><label class="rdo-label" :for="item.detail_id + '_' + choice.id">{{ choice.choice_name }}</label>
                                        <input v-if="choice.input_area_flg == FLG_ON" type="text" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id + JOIN + INPUTAREA]" v-bind:readonly="isConf">
                                        <div class="">
                                            <img v-if="rmUndefinedBlank(choice.image_path) != ''" :src="choice.image_path" class="rdo-img" />
                                        </div>
                                    </div>
                                </div>
                                <label class="control-label">{{ item.item_back_label }}</label>
                            </div>
                            <!-- 数値 -->
                            <div class="col-md-6 qreq-space qreq-height" v-if="item.item_type == ITEM_TYPE.NUMBER" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <label class="control-label"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</label>
                                <input type="number" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:readonly="isConf">
                                <label class="control-label">{{ item.item_back_label }}</label>
                            </div>
                            <!-- 日付 -->
                            <div class="col-md-6 qreq-space qreq-height" v-if="item.item_type == ITEM_TYPE.DATE" v-bind:class="{'has-error': (rmUndefinedBlank(errors[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]) != '') }">
                                <label class="control-label"><span v-show="item.required_flg == FLG_ON" class="required-mark">*</span>{{ item.item_front_label }}</label>
                                <input type="date" v-model="inputData[SYMBOL + item.quote_request_kbn + JOIN + item.header_id + JOIN + item.detail_id]" v-bind:readonly="isConf">
                                <label class="control-label">{{ item.item_back_label }}</label>
                            </div>

                        </div>
                    </div>
                    
                    <!-- 添付タブ -->
                    <div v-if="row.construction_id == attachmantTab.construction_id">
                        <div class="upload-area" @dragleave.prevent @dragover.prevent @drop.prevent="selectFile($event)">
                            <div v-for="(item, iCnt) in uploadFileList" :key="iCnt">
                                <div class="form-group"> 
                                    <div class="col-md-12">
                                        <label class="file_label col-md-4 col-sm-6">
                                            <input type="file" v-bind:id="'file-' + iCnt" class="file-upload-btn" multiple="multiple" @change="selectFile($event)" v-bind:disabled="(isConf || item.file_name != '' || item.file != '')">
                                            <span for="`file-${iCnt}`">{{ item.file_label }}</span>
                                        </label>
                                        <el-button type="danger" icon="el-icon-delete" circle size="mini" v-show="item.file != ''" @click="deleteFile(iCnt)" v-bind:disabled="isConf"></el-button>
                                        <el-button type="success" icon="el-icon-download" circle size="mini" v-show="item.file_name != ''" @click="downloadFile(item.file_name)" v-bind:disabled="isConf"></el-button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-12 text-right" v-if="isDetail == true && row.construction_id != attachmantTab.construction_id">
                        <button type="button" class="btn btn-delete" v-on:click="deleteRequestKbn(row.construction_id, tabNo)">削除</button>
                    </div>
                </el-tab-pane>
            </el-tabs>
        </div>

        <div class="col-md-12 text-center btn-box" v-if="isDetail == false">
            <!-- 見積依頼入力画面用ボタン -->
            <button type="button" class="btn btn-delete" v-on:click="saveDefault" v-show="isConf">工務店標準仕様として登録</button>
            <button type="button" class="btn btn-save" v-on:click="saveDraft">一時保存</button>

            <button type="button" class="btn btn-confirm" v-on:click="toConfirm" v-show="!isConf">入力内容確認</button>
            <button type="button" class="btn btn-save" v-on:click="complete" v-show="isConf" v-bind:disabled="selectQreqList.length <= 1">見積依頼</button>
            <button type="button" class="btn btn-delete" v-show="id != undefined" v-on:click="deleteRequest">見積依頼削除</button>

            <button type="button" class="btn btn-back" v-on:click="backStep1" v-show="!isConf">戻る</button>
            <button type="button" class="btn btn-back" v-on:click="backStep2" v-show="isConf">戻る</button>
        </div>
        <div  class="col-md-12 text-center btn-box" v-if="isDetail == true">
            <!-- 見積依頼詳細画面用ボタン -->
            <button type="button" class="btn btn-print" v-on:click="print">印刷</button>
            <button type="button" class="btn btn-print" v-on:click="showRequestDlg">依頼項目追加</button>
            <button type="button" class="btn btn-save" v-on:click="saveDetail">修正して保存</button>
            <button type="button" class="btn btn-delete" v-on:click="deleteRequest">見積依頼削除</button>
            <button type="button" class="btn btn-back" v-on:click="backList">戻る</button>
        </div>
    </div>
</template>

<style>
/* 各項目 */
.qreq-space {
    margin-top: 3px;
    margin-bottom: 10px;
}
.qreq-height {
    height: 40px;
}
/* テキストボックス */
.inp {
    width: 70% !important;
}
.qreq-text-space {
    margin-top: 3px;
}
/* テキストエリア */
.txtarea-label {
    float: left;
    margin-right: 5px;
}
.qreq-textarea-init {
    width: 80%;
    height: 160px;
}
/* ラジオボタン */
.rdo-label {
    margin-left: 5px;
    margin-right: 5px;
}
.rdo-img {
    height: 120px;
    margin-bottom: 10px;
}
/* チェックボックス */
.chkbox-space {
    margin-left: 5px;
}
/* セレクトボックス */
.selectbox-space {
    margin-left: 5px;
}
/* ボタンエリア */
.btn-box {
    margin: 10px 0 20px;
}
/* 印刷非表示 */
.print-none {
    display: none;
}
/* 添付タブ */
.upload-area {
    padding: 5px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
</style>

<script>
export default {
    data: () => ({
        // 項目種別（定数）
        ITEM_TYPE: {
            TEXT: 1,
            TEXTAREA: 2,
            SELECT: 3,
            CHECK: 4,
            RADIO: 5,
            NUMBER: 6,
            DATE: 7,
        },
        attachmantTab: QUOTE_REQ_ATTACH_TAB,
        fileLabel: LBL_FILE,

        // 仕様項目目印（定数）
        SYMBOL: '@@@',
        JOIN: '___',
        INPUTAREA: 'inputarea',

        // 入力値格納用
        inputData: [],
        // ファイルアップロード
        uploadFileList: [{file: '', file_name: '', file_label: LBL_FILE}],
        // 削除ファイル
        deleteFileList: [],
        
        // グループ作成用ワーク変数
        prevItemGroup: [],
        // セレクトボックス作成用ワーク変数
        prevSelectbox: [],

        errors: []
    }),
    props: {
        id: Number,
        customerId: Number,
        qreqList: Array,
        selectQreqList: Array,
        templates: Array,
        choiceList: Array,
        isConf: Boolean,
        isDetail: Boolean
    },
    created() {
    },
    mounted() {
    },
    methods: {
        showRequestDlg() {
             this.$emit('showDlgAddRequest')
        },
        // 戻る
        backStep1() {
            this.$emit('backToStep1')
        },
        backStep2() {
            // 入力チェック初期化
            this.errors = [];

            this.$emit('backToStep2')
        },
        backList() {
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/quote-request-list';
            }
            var listUrl = rtnUrl + query;
            location.href = listUrl 
        },
        // 一時保存ボタン
        saveDraft() {
            this.$emit('saveDraft', this.inputData, this.uploadFileList, this.deleteFileList)
        },
        // 確認画面へボタン
        toConfirm() {
            // 入力チェック
            for (var i = 0; i < this.selectQreqList.length; i++) {
                var row = this.selectQreqList[i];
                for (var j = 0; j < this.templates.length; j++) {
                    var item = this.templates[j];
                    if (item.required_flg == this.FLG_ON && row.construction_id == item.quote_request_kbn) {
                        var keyStr = this.SYMBOL + item.quote_request_kbn + this.JOIN + item.header_id + this.JOIN + item.detail_id;
                        if (this.rmUndefinedBlank(this.inputData[keyStr]) == '') {
                            this.errors[keyStr] = "*";
                        }
                    }
                }
            }

            this.$emit('finStep2')
        },
        // 見積依頼ボタン
        complete() {
            this.$emit('finStep3', this.inputData, this.uploadFileList, this.deleteFileList)
        },
        // 工務店標準として登録ボタン
        saveDefault() {
            this.$emit('finDefault', this.inputData, this.uploadFileList, this.deleteFileList)
        },
        // 修正して保存ボタン
        saveDetail() {
            this.$emit('saveDetail', this.inputData, this.uploadFileList, this.deleteFileList)
        },
        // 印刷ボタン
        print() {
            // TODO: 印刷用に不要箇所を除去しようとしたが値が消えるのでコメントアウト
            // // 印刷する要素取得
            // var printTarget = document.getElementById('saveForm');
            // var printTargetHtml = printTarget.innerHTML;
            // var div = document.createElement('div');
            // div.setAttribute('id', 'printTarget');
            // div.innerHTML = printTargetHtml;
            // // 印刷する要素以外を非表示
            // document.getElementById('app').classList.add('print-none');
            // // 印刷する要素追加
            // document.body.appendChild(div);

            // 印刷プレビュー
            window.print();

            // // 元に戻す
            // document.getElementById('printTarget').parentNode.removeChild(document.getElementById('printTarget'));
            // document.getElementById('app').classList.remove('print-none');
        },
        // セレクトボックス内のデータを絞る
        sqeezeChoiceList(choice, item) {
            if (choice.choice_keyword == item.choice_keyword) {
                return true;
            } else {
                return false;
            }
        },
        
        // 添付ファイル情報の取得
        selectFile(event) {
            if (this.isConf) {
                return;
            }

            let fileList = event.target.files ? 
                               event.target.files:
                               event.dataTransfer.files;
            let files = [];

            var listSize = this.uploadFileList.length - 1;
            for(let i = 0; i < fileList.length; i++){
                this.uploadFileList[listSize + i].file = fileList[i];
                this.uploadFileList[listSize + i].file_name = '';
                this.uploadFileList[listSize + i].file_label = fileList[i].name;
                this.uploadFileList.push({file: '', file_name: '', file_label: LBL_FILE});
            }            
        },
        // 添付ファイル情報削除
        deleteFile(index) {
            // 削除するファイル名を保持
            if (this.uploadFileList[index].file_name !== '') {
                this.deleteFileList.push(this.uploadFileList[index].file_name);
            }
            // 表示上削除
            this.uploadFileList.splice(index, 1);

            var listSize = this.uploadFileList.length - 1;
            document.getElementById('file-' + listSize).value = '';
        },
        // 添付ファイルダウンロード
        downloadFile(fileName) {
            var downloadUrl = '';
            if (this.id == undefined) {
                var downloadUrl = '/quote-request-edit/download/default/' + this.customerId + '/' + encodeURIComponent(fileName);
            } else {
                var downloadUrl = '/quote-request-edit/download/' + this.id + '/' + encodeURIComponent(fileName);
            }
            location.href = downloadUrl;
        },

        // データを入れる（親コンポーネントから呼ばれるメソッド）
        setData(refItemDataList, fileList) {
            // 初期化
            var iData = this.inputData;
            Object.keys(iData).forEach(function(key) {
                iData[key] = '';
            })
            // データセット
            if (refItemDataList != null && refItemDataList.length > 0) {
                for (var i = 0; i < refItemDataList.length; i++) {
                    var key = this.SYMBOL + refItemDataList[i].quote_request_kbn + this.JOIN + refItemDataList[i].spec_item_header_id + this.JOIN + refItemDataList[i].spec_item_detail_id
                    this.inputData[key] = refItemDataList[i].input_value01

                    // 入力値2に値がある場合
                    if (this.rmUndefinedBlank(refItemDataList[i].input_value02) != '') {
                        switch (refItemDataList[i].item_type) {
                            case this.ITEM_TYPE.RADIO:
                                // ラジオボタンの場合、入力エリア
                                var key = this.SYMBOL + refItemDataList[i].quote_request_kbn + this.JOIN + refItemDataList[i].spec_item_header_id + this.JOIN + refItemDataList[i].spec_item_detail_id + this.JOIN + this.INPUTAREA
                                this.inputData[key] = refItemDataList[i].input_value02
                                break;
                            default:
                                // nop
                                break;
                        }
                    }
                }
            }
            // ファイルセット
            this.deleteFileList = [];
            this.uploadFileList = [];
            if (this.rmUndefinedBlank(fileList) != '') {
                for (var i = 0; i < fileList.length; i++) {
                    var fileName = fileList[i];
                    
                    this.uploadFileList.push({
                        // file: '', 
                        file_name: fileName,
                        file_label: fileName
                    })
                }
            }
            this.uploadFileList.push({file: '', file_name: '', file_label: LBL_FILE})
        },
        // タブクリック
        tabclick(item) {
            this.$nextTick(function() {
                if (item != 0) {
                    for (var i = 0; i < this.selectQreqList.length; i++) {
                        if (item == this.selectQreqList[i].construction_id) {
                            var eleId = 'tab-' + i;
                            document.getElementById(eleId).click();
                        }
                    }
                }
            });
        },
        // 全削除
        deleteRequest() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('quote_request_id', this.rmUndefinedBlank(this.id));

            axios.post('/quote-request-edit/delete-request', params)
            .then( function (response) {
                if (response.data == true) {
                    // 成功
                    // window.onbeforeunload = null;
                    var listUrl = '/quote-request-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    window.onbeforeunload = null;
                    // location.reload();
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                }
            }.bind(this))
            // .finally(() => {
            //     this.loading = false
            //     location.reload();
            // })
        },
        // 見積詳細で見積依頼項目削除（タブ削除）
        deleteRequestKbn(classConId, tabNo) {
            if (tabNo == 0 && this.selectQreqList[tabNo+1] && this.selectQreqList[tabNo+1].construction_id == QUOTE_REQ_ATTACH_TAB.construction_id) {
                // 左から2番目のタブが添付タブの時に、左端のタブを削除すると依頼項目がなくなるため、削除させない
                alert(MSG_ERROR_NO_ITEM_CNT);
                return;
            }

            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('quote_request_id', this.rmUndefinedBlank(this.id));
            params.append('quote_request_kbn_id', this.rmUndefinedBlank(classConId));

            axios.post('/quote-request-edit/delete-request-kbn', params)
            .then( function (response) {
                if (response.data == true) {
                    // 成功
                } else {
                    // 失敗
                }
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                }
            }.bind(this))
            .finally(() => {
                this.loading = false
                location.reload();
            })
        },

        // // チェックボックス
        // changeCheckbox() {
        //     // 詳細画面で即時反映されない対策
        //     this.$forceUpdate();
        // }
    },
};

</script>
