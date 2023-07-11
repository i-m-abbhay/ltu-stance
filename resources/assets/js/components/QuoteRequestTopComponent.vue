<template>
    <div>
        <div class="col-md-12">
            <div class="col-md-6">
                <div class="form-group">
                    <div class="col-md-6">
                        <div v-bind:class="{'has-error': (errors.customer_id != '') }">
                            <label class="control-label">得意先名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="customer_name, customer_short_name, customer_kana" 
                                display-member-path="customer_name" 
                                :items-source="customerList" 
                                selected-value-path="id"
                                :selected-value="main.customer_id"
                                :lost-focus="selectCustomerId"
                                :isRequired=false
                                :isDisabled="savedFlg == true"
                                :max-items="customerList.length"
                            >
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.customer_id }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div v-bind:class="{'has-error': (errors.owner_name != '') }">
                            <label class="control-label">施主名等</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="owner_name" 
                                display-member-path="owner_name" 
                                :items-source="ownerList" 
                                selected-value-path="owner_name"
                                :isDisabled="savedFlg == true"
                                :max-items="ownerList.length"
                                :initialized="initOwnerName"
                            >
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.owner_name }}</p>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-6">
                        <div v-bind:class="{'has-error': (errors.quote_limit_date != '') }">
                            <label class="control-label">見積提出期限</label>
                            <wj-input-date
                                :value="main.quote_limit_date"
                                :lost-focus="inputQuoteLimitDate"
                                :isRequired=false
                            ></wj-input-date>
                            <p class="text-danger">{{ errors.quote_limit_date }}</p>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-9">
                        <dl class="rakuraku-box">
                            <h4>らくらく見積機能</h4>
                            <dt>・工務店標準仕様を採用しますか？</dt>
                            <dd>
                                <el-radio-group v-model="main.builder_standard_flg" v-bind:disabled="rmUndefinedBlank(this.mainData.quote_request_id) != '' || main.use_ref_quote_flg == FLG_ON">
                                    <el-radio class="col-md-10" :label="FLG_ON">採用する。</el-radio>
                                    <el-radio class="col-md-10" :label="FLG_OFF">採用しない。</el-radio>
                                </el-radio-group>
                            </dd>
                            <dt>・以前作成した見積を採用しますか？</dt>
                            <dd>
                                <el-radio-group v-model="main.use_ref_quote_flg" @change="changeUseOldQuote" v-bind:disabled="rmUndefinedBlank(this.mainData.quote_request_id) != '' || main.builder_standard_flg == FLG_ON">>
                                    <el-radio class="col-md-10" :label="FLG_ON">採用する。</el-radio>
                                    <div class="form-group">
                                        <div class="col-xs-11 col-xs-offset-1 col-md-11 col-md-offset-1">
                                            <div>
                                                <label class="control-label in-el-radio-group">案件名</label>
                                                <wj-auto-complete class="form-control"
                                                    search-member-path="matter_name" 
                                                    display-member-path="matter_name" 
                                                    :items-source="matterList" 
                                                    selected-value-path="matter_no"
                                                    :selected-value="main.ref_matter_no"
                                                    :isDisabled="isUseRefQuote"
                                                    :isRequired=false
                                                    :lost-focus="selectRefMatterNo"
                                                    :max-items="matterList.length"
                                                >
                                                </wj-auto-complete>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-group">
                                        <div class="col-xs-11 col-xs-offset-1 col-md-11 col-md-offset-1">
                                            <div>
                                                <label class="control-label in-el-radio-group">見積番号</label>
                                                <input type="text" class="form-control" v-model="main.ref_quote_no" v-bind:readonly="isUseRefQuote">
                                            </div>
                                        </div>
                                    </div>
                                    <el-radio class="col-md-10" :label="FLG_OFF">採用しない。</el-radio>
                                </el-radio-group>
                            </dd>
                        </dl>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="form-group">
                    <div class="col-md-11" v-bind:class="{'has-error': (errors.architecture_type != '') }">
                        <label class="control-label">建築種別</label>
                        <el-radio-group v-model="main.architecture_type" v-bind:disabled="savedFlg == true">
                            <el-radio v-for="row in architectureList" :key="row.value_code" :label="row.value_code" class="lengthways">{{ row.value_text_1 }}</el-radio>
                        </el-radio-group>
                        <p class="text-danger">{{ errors.architecture_type }}</p>
                    </div>
                </div>
                <div class="form-group">
                    <div class="col-md-11" v-bind:class="{'has-error': (errors.spec_kbn != '') }">
                        <label class="control-label">仕様区分</label>
                        <el-radio-group v-model="main.spec_kbn">
                            <el-radio v-for="row in specList" :key="row.value_code" :label="row.value_code" class="lengthways">{{ row.value_text_1 }}</el-radio>
                        </el-radio-group>
                        <p class="text-danger">{{ errors.spec_kbn }}</p>
                        <el-checkbox v-model="main.semi_fireproof_area_flg" :label="FLG_ON" :true-label="FLG_ON" :false-label="FLG_OFF">省令準耐火</el-checkbox>
                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <div class="col-md-12">
                    <label class="control-label">見積依頼項目</label>
                    <el-checkbox :indeterminate="isQuoteRequestIndeterminate" v-model="quoteRequestAll" v-bind:disabled="main.use_ref_quote_flg == FLG_ON" @change="handleQuoteRequestAll"></el-checkbox>
                    <el-checkbox-group class="col-md-12" v-model="selectQuoteRequest" v-bind:disabled="main.use_ref_quote_flg == FLG_ON" @change="handleQuoteRequestChange">
                        <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" class="lengthways">{{ row.construction_name }}</el-checkbox>
                    </el-checkbox-group>
                </div>
            </div>
            <div class="col-md-2">
                <div class="col-md-12">
                    <label class="control-label">見積依頼済項目</label>
                    <el-checkbox :indeterminate="isQuoteRequestedIndeterminate" v-model="quoteRequestedAll" v-bind:disabled="main.use_ref_quote_flg == FLG_ON" @change="handleQuoteRequestedAll"></el-checkbox>
                    <el-checkbox-group class="col-md-12" v-model="selectQuoteRequested" v-bind:disabled="main.use_ref_quote_flg == FLG_ON" @change="handleQuoteRequestedChange">
                        <el-checkbox v-for="row in qreqList" :label="row.construction_id" :key="row.construction_id" class="lengthways">{{ row.construction_name }}</el-checkbox>
                    </el-checkbox-group>
                </div>
            </div>
            
            <div class="col-md-12 text-center btn-box">
                <button type="button" class="btn btn-save" v-on:click="showDialog">案件選択／登録</button>
                <button type="button" class="btn btn-back" v-on:click="back">戻る</button>
            </div>
        </div>
    <!-- </form> -->
    
    <!-- 案件選択ダイアログ -->
    <el-dialog title="案件選択／新規登録" :visible.sync="showDlgMatterSelect">
        <p>入力した情報から既存案件を選択もしくは新規案件を登録します。</p>
        <el-radio-group v-model="selectDialog">
            <el-radio class="col-md-10" :label="MATTER_NEW">新規</el-radio>
            <div class="form-group">
                <div class="col-xs-11 col-xs-offset-1 col-md-11 col-md-offset-1">
                    <label class="control-label in-el-radio-group">{{ newMatterName }}</label>
                </div>
            </div>
            <el-radio class="col-md-10" :label="MATTER_USE">選択</el-radio>
            <div class="form-group">
                <div class="col-xs-11 col-xs-offset-1 col-md-11 col-md-offset-1">
                    <wj-auto-complete class="form-control"
                        search-member-path="matter_name" 
                        display-member-path="matter_no_name" 
                        :items-source="sameMatterList" 
                        selected-value-path="matter_no"
                        :selected-value="main.matter_no"
                        :initialized="selectMatterNo"
                        :lost-focus="selectMatterNo"
                        :selected-index="-1"    
                        :isReadonly="true"
                        :max-items="sameMatterList.length"
                    >
                    </wj-auto-complete>
                </div>
            </div>
        </el-radio-group>
        <p class="text-danger">{{ errors.matter_no }}</p>

        <span slot="footer" class="dialog-footer">
            <el-button @click="decideMatter">仕様入力へ</el-button>
            <el-button @click="showDlgMatterSelect = false">キャンセル</el-button>
        </span>
    </el-dialog>
    </div>
</template>

<style>
.btn-box {
    margin: 10px 0 20px;
}
.lengthways {
    width: 100%;
}
</style>

<script>
export default {
    data: () => ({
        main: {},

        MATTER_NEW: 1,
        MATTER_USE: 0,
        FLG_ON: 1,
        FLG_OFF: 0,

        qreqIdList: [],
        quoteRequestAll: false,
        isQuoteRequestIndeterminate: false,
        quoteRequestedAll: false,
        isQuoteRequestedIndeterminate: false,
        selectQuoteRequest: [],
        selectQuoteRequested: [],
        isUseRefQuote: true,
        
        // 案件選択ダイアログ
        showDlgMatterSelect: false,
        newMatterName: '',
        sameMatterList: [],
        selectDialog: 1,

        // 工務店標準仕様データ・参照データ
        itemDataList: [],
        defaultFileList: [],

        // 入力フォーム
        inputTemplates: null,

        errors: {
            customer_id: '',
            owner_name: '',
            quote_limit_date: '',
            architecture_type: '',
            spec_kbn: '',
            matter_no: ''
        },
    }),
    props: {
        mainData: {},
        savedFlg: Boolean,
        customerList: Array,
        ownerList: Array,
        architectureList: Array,
        specList: Array,
        qreqList: Array,
        matterList: Array,
    },
    created() {
        // propsの値をローカル変数にセット
        this.main = this.mainData
        if (this.rmUndefinedBlank(this.mainData.quote_request_id) != '') {
            this.selectQuoteRequest = this.mainData.quote_request_kbn_arr
            this.selectQuoteRequested = this.mainData.quote_requested_kbn_arr
        }
    },
    mounted() {
        var qreqIdList = this.qreqIdList;
        this.qreqList.forEach((item) => {
            this.qreqIdList.push(item.construction_id);
        })
    },
    methods: {
        // 戻る
        back() {
            // var listUrl = '/quote-request-list' + window.location.search
            // location.href = listUrl 
            
            // 遷移元URL取得
            var query = window.location.search
            var rtnUrl = this.getLocationUrl(query)
            if (rtnUrl == '') {
                rtnUrl = '/quote-request-list';
            }
            var listUrl = rtnUrl + query;
            location.href = listUrl 
        },

        selectCustomerId: function(sender) {
            // LostFocus時に選択した得意先IDを取得。
            this.main.customer_id = this.rmUndefinedBlank(sender.selectedValue)
        },
        // inputOwnerName: function(sender) {
        //     // LostFocus時に入力した施主名を取得。
        //     this.main.owner_name = this.rmUndefinedBlank(sender.text)
        // },
        initOwnerName: function(sender){
            // 施主名
            if (this.rmUndefinedBlank(this.mainData.quote_request_id) == '') {
                // 新規時は未選択状態にする
                sender.selectedIndex = -1;
            } else {
                sender.selectedValue = this.mainData.owner_name;
            }
            this.main.owner_name = sender;
        },
        inputQuoteLimitDate: function(sender) {
            // LostFocus時に選択した見積提出期限を取得。
            this.main.quote_limit_date = this.rmUndefinedBlank(sender.text)
        },
        selectMatterNo: function(sender) {
            // LostFocus時に入力した案件番号を取得。
            this.main.matter_no = this.rmUndefinedBlank(sender.selectedValue)

            if (this.sameMatterList.length != 0) {
                for (var i = 0; i < this.sameMatterList.length; i++) {
                    // 選択した案件番号がすでに見積依頼データを持っているなら、警告表示
                    if (this.sameMatterList[i].matter_no == this.main.matter_no) {
                        if (this.sameMatterList[i].quote_request_id != '') {
                            this.errors.matter_no = MSG_ERROR_EXIST_QREQ
                            break;
                        } else {
                            this.errors.matter_no = '';
                            break;
                        }
                    }
                }
            }
        },
        // 以前作成見積利用
        changeUseOldQuote(val) {
            this.isUseRefQuote = !this.isUseRefQuote
        },
        selectRefMatterNo: function(sender) {
            // LostFocus時に入力した参照案件番号を取得。
            this.main.ref_matter_no = this.rmUndefinedBlank(sender.selectedValue)
        },
        // 見積依頼項目　全選択チェックボックス
        handleQuoteRequestAll(val) {
            this.selectQuoteRequest = val ? this.qreqIdList : [];
            this.isQuoteRequestIndeterminate = false;
        },
        handleQuoteRequestChange(val) {
            var checkedCount = val.length;
            this.quoteRequestAll = (checkedCount === this.qreqList.length);
            this.isQuoteRequestIndeterminate = (checkedCount > 0) && (checkedCount < this.qreqList.length);
        },
        // 見積依頼済項目　全選択チェックボックス
        handleQuoteRequestedAll(val) {
            this.selectQuoteRequested = val ? this.qreqIdList : [];
            this.isQuoteRequestedIndeterminate = false;
        },
        handleQuoteRequestedChange(val) {
            var checkedCount = val.length;
            this.quoteRequestedAll = (checkedCount === this.qreqList.length);
            this.isQuoteRequestedIndeterminate = (checkedCount > 0) && (checkedCount < this.qreqList.length);
        },

        // 案件選択ダイアログ表示
        showDialog() {
            // エラーの初期化
            this.initErr(this.errors);
            
            // 入力値を取得
            var params = new URLSearchParams();
            params.append('quote_request_id', this.rmUndefinedBlank(this.main.quote_request_id));
            params.append('customer_id', this.rmUndefinedBlank(this.main.customer_id));
            params.append('owner_name', this.rmUndefinedBlank(this.main.owner_name.text));
            params.append('quote_limit_date', this.rmUndefinedBlank(this.main.quote_limit_date));
            params.append('architecture_type', this.rmUndefinedBlank(this.main.architecture_type));
            params.append('spec_kbn', this.rmUndefinedBlank(this.main.spec_kbn));
            params.append('builder_standard_flg', this.rmUndefinedBlank(this.main.builder_standard_flg));
            params.append('use_ref_quote_flg', this.rmUndefinedBlank(this.main.use_ref_quote_flg));
            params.append('ref_matter_no', this.rmUndefinedBlank(this.main.ref_matter_no));
            params.append('ref_quote_no', this.rmUndefinedBlank(this.main.ref_quote_no));
            params.append('quote_request_kbn', JSON.stringify(this.rmUndefinedBlank(this.selectQuoteRequest)));

            axios.post('/quote-request-edit/get-matter', params)
            .then( function (response) {
                this.loading = false

                if (response.data.status) {
                    // 成功

                    // 入力フォームテンプレートデータ
                    this.inputTemplates = response.data.templates;

                    this.itemDataList = [];
                    this.defaultFileList = [];
                    if (this.savedFlg == true) {
                        // 保存済みの見積依頼は案件が確定しているため、案件選択ダイアログを出さずに、STEP2へ
                        this.itemDataList = response.data.qreq_data;

                        this.selectDialog = this.MATTER_USE;
                        this.showDlgMatterSelect = false;
                        this.defaultFileList = null;   // 保存済み or 標準登録ファイルが0件 を判断するためにnullを代入
                        this.$emit('finStep1', this.main, this.selectDialog, this.selectQuoteRequest, this.selectQuoteRequested, this.itemDataList, this.inputTemplates, this.defaultFileList)
                    } else {
                        // 案件リストセット
                        this.sameMatterList = response.data.matter_data;
                        if (this.sameMatterList.length != 0) {
                            this.selectDialog = this.MATTER_USE
                        }
                        // 案件名セット
                        this.newMatterName = response.data.matter_name;

                        if (this.main.builder_standard_flg == this.FLG_ON) {
                            // 工務店標準仕様 利用
                            this.itemDataList = response.data.qreq_data;
                            this.defaultFileList = response.data.file_list;
                        } else if (this.main.use_ref_quote_flg == this.FLG_ON) {
                            // 見積依頼参照 利用
                            this.itemDataList = response.data.qreq_data;

                            // 見積依頼項目、見積依頼済み項目　セット
                            this.selectQuoteRequest = response.data.ref_quote_request_kbn;
                            this.selectQuoteRequested = [];
                        }

                        this.$nextTick(function() {
                            // ダイアログ表示
                            this.showDlgMatterSelect = true
                        });
                    }

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
        },

        // 仕様入力へボタンイベント
        decideMatter() {
            this.errors.matter_no = ''
            var usedFlg = '';

            // 案件
            if (this.selectDialog == this.MATTER_NEW) {
                // 新規
                this.main.matter_no = '';
                this.main.matter_name = this.newMatterName;
            } else {
                // 選択
                if (this.rmUndefinedBlank(this.main.matter_no) == '') {
                    usedFlg = true;
                    this.errors.matter_no = MSG_ERROR_NO_SELECT
                }
                //  else {
                    // if (this.sameMatterList.length != 0) {
                    //     for (var i = 0; i < this.sameMatterList.length; i++) {
                    //         // 選択した案件番号がすでに見積依頼データを持っているなら、選択不可
                    //         if (this.sameMatterList[i].matter_no == this.main.matter_no) {
                    //             if (this.sameMatterList[i].quote_request_id != '') {
                    //                 this.errors.matter_no = MSG_ERROR_EXIST_QREQ
                    //                 usedFlg = true;
                    //                 break;
                    //             }
                    //         }
                    //     }
                    // }
                // }

                this.main.matter_no = this.main.matter_no;
                this.main.matter_name = '';
            }

            if (usedFlg == false) {
                // エラーなし
                this.errors.matter_no = ''
                this.showDlgMatterSelect = false
                this.$emit('finStep1', this.main, this.selectDialog, this.selectQuoteRequest, this.selectQuoteRequested, this.itemDataList, this.inputTemplates, this.defaultFileList)
            }
        }
    },
};

</script>
