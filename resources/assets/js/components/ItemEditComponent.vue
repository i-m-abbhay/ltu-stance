<template>
    <div>
        <loading-component :loading="loading" />   
        <div class="form-horizontal">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/item-edit/save">
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <p style="color:red;">{{ msg }} </p>
                    <div class="col-md-7 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <div class="col-xs-12 col-sm-10 col-md-10" v-bind:class="{'has-error': (errors.item_name != '') }">
                                <label for="imNam" class="inp">
                                    <input id="imNam" type="text" class="form-control" placeholder=" " v-model="inputData.item_name" v-bind:readonly="isReadOnly">
                                    <span class="label"><span style="color:red;">＊</span>項目名</span>
                                    <span class="border"></span>
                                </label>
                                <p class="text-danger">{{ errors.item_name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <div class="col-xs-12 col-sm-12 col-md-6" v-bind:class="{'has-error': (errors.item_front_label != '') }">
                                <label for="imFront" class="inp">
                                    <input id="imFront" type="text" class="form-control" placeholder=" " v-model="inputData.item_front_label" v-bind:readonly="isReadOnly">
                                    <span class="label">前ラベル</span>
                                    <span class="border"></span>
                                </label>
                                <p class="text-danger">{{ errors.item_front_label }}</p>
                            </div>
                            <div class="col-xs-12 col-sm-12 col-md-6" v-bind:class="{'has-error': (errors.item_back_label != '') }">
                                <label for="imFront" class="inp">
                                    <input id="imFront" type="text" class="form-control" placeholder=" " v-model="inputData.item_back_label" v-bind:readonly="isReadOnly">
                                    <span class="label">後ラベル</span>
                                    <span class="border"></span>
                                </label>
                                <p class="text-danger">{{ errors.item_back_label }}</p>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-12 col-sm-8 col-md-8">
                                <div class="text-left">種別</div>
                                <el-radio-group v-model="inputData.item_type" v-bind:disabled="isReadOnly">
                                    <div class="radio col-xs-12 col-sm-4 col-md-4" v-for="type in typedata" :key="type.value_code">
                                        <el-radio :label="type.value_code">{{ type.value_text_1 }}</el-radio>
                                    </div>
                                </el-radio-group>
                                <p class="text-danger">{{ errors.item_type }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <div class="col-md-4 col-sm-4 col-xs-12">
                            <label class="control-label">選択肢キーワード</label>
                            <wj-auto-complete class="form-control" v-bind:readonly="(!isChoiceKeyword || isReadOnly)"
                                search-member-path="choice_keyword"
                                display-member-path="choice_keyword"
                                selected-value-path="choice_keyword"
                                :selected-index="-1"
                                :selected-value="inputData.choice_keyword"
                                :is-required="false"
                                :initialized="initKeyword"
                                :isReadOnly="!isChoiceKeyword"
                                :max-items="keywordlist.length"
                                :min-length="1"
                                :items-source="keywordlist">
                            </wj-auto-complete>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12">
                        <div class="form-group">
                            <label class="col-md-12 col-sm-12 col-xs-12">必須</label>
                            <el-radio-group v-model="inputData.required_flg" v-bind:disabled="isReadOnly">
                                <div class="radio col-md-6">
                                    <el-radio :label="FLG_OFF">なし</el-radio>
                                </div>
                                <div class="radio col-md-6">
                                    <el-radio :label="FLG_ON">あり</el-radio>
                                </div>
                            </el-radio-group>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.memo != '') }">
                        <label>備考</label>
                        <el-input v-bind:readonly="isReadOnly"
                            type="textarea"
                            :rows="3"
                            v-model="inputData.memo">
                        </el-input>
                        <p class="text-danger">{{ errors.memo }}</p>
                    </div>

                </div>
                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">更新日時</label>
                            <p v-show="itemdata" class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ itemdata.update_at|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">更新者</label>
                            <p v-show="itemdata" class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ itemdata.update_user_name }}</p>
                        </div>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="col-md-12 col-sm-12 col-xs-12 text-center">                    
                    <button type="button" class="btn btn-primary btn-save" v-show="(isEditable && !isused)" v-on:click="save">登録</button>                    
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    <button type="button" class="btn btn-danger btn-delete" v-show="(itemdata.id != undefined && isEditable && !isused)" v-on:click="del">削除</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: false,
        isReadOnly: false,
        isChoiceKeyword: false,

        msg: '',

        FLG_OFF: 0,
        FLG_ON: 1,

        inputData: {
            item_name: '',
            item_front_label: '',
            item_back_label: '',
            item_type: 1,
            choice_keyword: '',
            placeholder: '',
            required_flg: 0,
            memo: '',
        },

        wjInputObj: {
            choice_keyword: {},
        },

        errors: {
            item_name: '',
            item_front_label: '',
            item_back_label: '',
            item_type: '',
            choice_keyword: '',
            placeholder: '',
            required_flg: '',
            memo: '',
        }
    }),
    props: {
        isEditable: Number,
        itemdata: {},
        typedata: Array,
        keywordlist: Array,
        isused: Number,
    },
    created() {
        if (this.rmUndefinedBlank(this.itemdata.id) != '') {
            this.inputData = this.itemdata;
        }
    },
    mounted() {
        if (!this.isEditable) {
            this.isReadOnly = true;
        }

        this.typedata.forEach(el => {
            if (el.value_code == this.itemdata.item_type) {
                if (el.value_num_1 == this.FLG_ON) {
                    this.isChoiceKeyword = true;
                } else {
                    this.isChoiceKeyword = false;
                    this.inputData.choice_keyword = null;
                }
            }
        });

        if (this.isused) {
            this.isReadOnly = true;
            this.msg = MSG_USED_CHOICE;
        }
    },
    watch:{
        // value_num_1 にフラグが立っている場合のみ
        // 選択肢キーワード入力可
        inputData: {
            handler: function (val, oldVal) {
                this.typedata.forEach(el => {
                    if (el.value_code == val.item_type) {
                        if (el.value_num_1 == this.FLG_ON) {
                            this.isChoiceKeyword = true;
                        } else {
                            this.isChoiceKeyword = false;
                            this.wjInputObj.choice_keyword.selectedIndex = -1;
                        }
                    }
                });
            },
            deep: true
        }
    },
    methods: {
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            // 入力値を取得
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.itemdata.id));
            params.append('item_name', this.rmUndefinedBlank(this.inputData.item_name));
            params.append('item_front_label', this.rmUndefinedBlank(this.inputData.item_front_label));
            params.append('item_back_label', this.rmUndefinedBlank(this.inputData.item_back_label));
            params.append('item_type', this.rmUndefinedBlank(this.inputData.item_type));
            params.append('choice_keyword', this.rmUndefinedBlank(this.wjInputObj.choice_keyword.selectedValue));
            params.append('required_flg', this.rmUndefinedBlank(this.inputData.required_flg));
            params.append('memo', this.rmUndefinedBlank(this.inputData.memo));

            axios.post('/item-edit/save', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/item-list' + window.location.search
                    location.href = listUrl
                }else {
                    // 失敗
                    // window.onbeforeunload = null;
                    // location.reload();
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
                    // window.onbeforeunload = null;
                    // location.reload()
                }
                this.loading = false
            }.bind(this))
        },
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.itemdata.id));

            axios.post('/item-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/item-list' + window.location.search
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
        // 戻る
        back() {
            var listUrl = '/item-list' + window.location.search
            location.href = listUrl            
        },
        /* オートコンプリート紐付け */
        initKeyword(sender) {
            this.wjInputObj.choice_keyword = sender;
        },
    },
};
</script>

<style>
/* 入力項目 */
.main-body {
    /* width: 100%; */
    margin-top: 10px;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
/* サブ項目 */
.sub-body {
    /* width: 100%; */
    margin-top: 10px;
    background: #ffffff;
    padding: 20px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.control-label {
    font-size: 12px;
    margin-top: 5px;
    padding-bottom: 10px;
    text-align: left !important;
}

</style>