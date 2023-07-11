<template>
    <div>
        <loading-component :loading="loading" />   
        <div class="form-horizontal">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/choice-edit/save">
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <!-- キーワード -->
                    <p style="color:red;">{{ msg }}</p>
                    <div class="col-xs-12 col-sm-12 col-md-12" v-bind:class="{'has-error': (errors.choice_keyword != '') }">
                        <label for="keyword" class="inp">
                            <input type="text" id="keyword" class="form-control" v-model="choice_keyword" placeholder=" " maxlength="20" v-bind:readonly="isReadOnly">
                            <span for="keyword" class="label"><span style="color:red;">＊</span>キーワード</span>
                            <span class="border"></span>
                        </label>
                        <p class="text-danger">{{ errors.choice_keyword }}</p>
                    </div>
                </div>
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <!-- メイン -->
                    <div v-for="(data, index) in activeData" v-bind:key="index" class="col-md-12 col-sm-12 col-xs-12 main-body"  style="margin-top:10px;">
                        <div class="col-md-7 col-sm-12 col-xs-12 input-margin">
                            <div class="form-group">
                                <div class="col-xs-12 col-sm-10 col-md-10" v-bind:class="{'has-error': (data.errors.choice_name != '') }">
                                    <label v-bind:for="data.index" class="inp">
                                        <input type="text" v-bind:key="data.index" class="form-control" placeholder=" " v-model="data.choice_name" v-bind:readonly="isReadOnly">
                                        <span class="label"><span style="color:red;">＊</span>選択肢名</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ data.errors.choice_name }}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <div class="col-xs-6 col-sm-6 col-md-4" v-bind:class="{'has-error': (data.errors.display_order != '') }">
                                    <label v-bind:for="data.index" class="inp">
                                        <input type="number" v-bind:key="data.index" class="form-control" placeholder=" " v-model="data.display_order" v-bind:readonly="isReadOnly">
                                        <span class="label">表示順</span>
                                        <span class="border"></span>
                                    </label>
                                    <p class="text-danger">{{ data.errors.display_order }}</p>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="col-md-12 col-sm-12 col-xs-12">入力エリア</label>
                                <el-radio-group v-model="data.input_area_flg" v-bind:disabled="isReadOnly">
                                    <div class="radio col-md-6">
                                        <el-radio :label="FLG_OFF">なし</el-radio>
                                    </div>
                                    <div class="radio col-md-6">
                                        <el-radio :label="FLG_ON">あり</el-radio>
                                    </div>
                                </el-radio-group>
                            </div>
                        </div>
                        <div class="col-xs-12 col-sm-7 col-md-5">
                            <p class="help-text">画像　<i class="el-icon-paperclip"></i></p>
                            <label class="imagePreview">
                                <img class="imagePreview" v-bind:key="data.index" v-show="data.image != ''" v-bind:src="data.image">
                                <span>
                                    <input type="file" class="uploadfile" accept="image/png, image/jpeg" style="display:none" @change="imageChanged(index, $event)" v-bind:disabled="isReadOnly">
                                </span>
                            </label>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-12">
                            <button type="button" class="btn btn-danger pull-right" @click="deleteForm(index)" v-bind:disabled="isReadOnly">選択肢削除</button>
                        </div>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 input-margin">
                        <p class="text-center" style="font-size:2em;">選択肢追加 <button type="button" class="btn btn-primary" style="width:200px;" @click="addForm" v-bind:disabled="isReadOnly">＋</button></p>
                    </div>
                </div>
                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">更新日時</label>
                            <p v-show="isOwnData" class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ choiceInfo.update_at|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">更新者</label>
                            <p v-show="isOwnData" class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ choiceInfo.update_user_name }}</p>
                        </div>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="col-md-12 col-sm-12 col-xs-12 text-center">                    
                    <button type="button" class="btn btn-primary btn-save" v-show="(isEditable && !isused)" v-on:click="save">登録</button>                    
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    <button type="button" class="btn btn-danger btn-delete" v-show="(isOwnData && isEditable && !isused)" v-on:click="del">削除</button>
                </div>
            </form>
        </div>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: false,
        isOwnData: false,
        isReadOnly: false,
        choiceInfo: {},

        FLG_OFF: 0,
        FLG_ON: 1,
        msg: '',

        choice_keyword: '',
        inputData: [],

        errors: {
            choice_keyword: '',
            display_order: '',
            choice_name: '',
            input_area_flg: '',
            image_path: '',
        },
    }),
    props: {
        isEditable: Number,
        choicedata: {},
        isused: Number,
    },
    created() {
        
    },
    mounted() {
        // propsデータをローカル変数へ
        if (this.choicedata.length != 0) {
            this.isOwnData = true;
            this.inputData = this.choicedata;
            this.choice_keyword = this.choicedata[0].choice_keyword;
            this.choiceInfo = {update_at: this.choicedata[0].update_at, update_user_name: this.choicedata[0].update_user_name};

            this.inputData.forEach((data, i) => {
                var tmpErr = { choice_name: '', display_order: ''};
                data.errors = tmpErr;
                if (data.image == undefined || data.image == null) {
                    data.image = '';
                }
                data.image_file = null;
                // this.photoList.push({imagePreview: data.image});
            })
        } else {
            this.addForm();
        }

        if (!this.isEditable) {
            this.isReadOnly = true;
        }

        if (this.isused) {
            this.isReadOnly = true;
            this.msg = MSG_USED_CHOICE;
        }
    },
    computed: {
        // 削除フラグ"0"のレコードのみ表示
        activeData: function() {
            return this.inputData.filter(function (data) {
                return data.del_flg == 0;
            })
        },
    },
    methods: {
        // キーワード 全角入力削除
        // checkText() {
        //     var str = this.choice_keyword;
        //     while(str.match(/[^A-Z^a-z\d\-]/))
        //     {
        //         str = str.replace(/[^A-Z^a-z\d\-]/,"");
        //     }
        //     this.choice_keyword = str;
        // },
        // 画像プレビュー
        imageChanged(index, e) {
            let files = e.target.files[0];
            let reader = new FileReader();
            reader.onload = (e) => {
                var result = e.target.result;
                this.$set(this.activeData[index], "image", result);
            };
            this.$set(this.activeData[index], "image_file", e.target.files[0]);
            reader.readAsDataURL(files);
        },
        // 選択肢追加
        addForm: function() {
            if (this.inputData != undefined || this. inputData != null) {
                // 表示順の最大値算出
                var targetArr = this.inputData;
                var i = targetArr.length;
                var max = -Infinity;
                while (i--) {
                    if (targetArr[i].display_order > max) {
                        max = targetArr[i].display_order
                    }
                }
            }
            var addChoice　= {
                id: '',
                choice_keyword: '',
                display_order: 1,
                choice_name: '',
                input_area_flg: 0,
                image_path: '',
                image_file: '',                
                image: '',
                del_flg: 0,
                errors: {
                    choice_name: '',
                    display_order: '',
                },
            };
            if (max != undefined && max != null && max != -Infinity && max != NaN) {
                // 表示順セット
                addChoice.display_order = parseInt(max) + 1
            }
            this.inputData.push(addChoice);
        },
        // 選択肢削除
        deleteForm(index) {
            if(this.activeData[index].id != ''){
                this.$set(this.activeData[index], 'del_flg', '1');
            }else {
                this.inputData.forEach((el, i) => {
                    if (el == this.activeData[index]) {
                        this.inputData.splice(i, 1);
                    }
                })
                
            }
        },
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            var choiceIds = [];
            if (this.choicedata.length != 0) {
                this.choicedata.forEach((data, i) => {
                    choiceIds.push(data.id);
                })
            }

            // 入力値を取得
            var submitFlg = true;
            var params = new FormData();
            params.append('id', this.rmUndefinedBlank(this.choiceIds));
            params.append('choice_keyword', this.rmUndefinedBlank(this.choice_keyword));
            this.inputData.forEach((data, i) => {
                this.initErr(data.errors);
                
                if (this.rmUndefinedBlank(data.choice_name) == '') { 
                    // 選択肢名が未入力
                    data.errors.choice_name = MSG_ERROR_NO_INPUT;   
                    submitFlg = false;                               
                }
                if (this.rmUndefinedZero(data.display_order) <= 0) {
                    // 表示順が0以下
                    data.errors.display_order = '1' + MSG_ERROR_LOWER_NUMBER;
                    submitFlg = false;   
                } 
                if (!submitFlg) {   
                    return false;
                }else {
                    params.append('choiceList[' + i + ']' + '[id]', this.rmUndefinedBlank(data.id));
                    params.append('choiceList[' + i + ']' + '[choice_keyword]', this.rmUndefinedBlank(this.choice_keyword));
                    params.append('choiceList[' + i + ']' + '[choice_name]', this.rmUndefinedBlank(data.choice_name));
                    params.append('choiceList[' + i + ']' + '[display_order]', this.rmUndefinedBlank(data.display_order));
                    params.append('choiceList[' + i + ']' + '[input_area_flg]', this.rmUndefinedBlank(data.input_area_flg));
                    params.append('choiceList[' + i + ']' + '[del_flg]', this.rmUndefinedZero(data.del_flg));

                    if (this.choicedata.length == 0) {
                        // 新規
                        params.append('choiceList[' + i + ']' + '[image_path]', '');
                        params.append(i, data.image_file);
                    } else {
                        if(data.image_file != null || data.image != this.choicedata[i].image){
                            // 画像変更
                            params.append('choiceList[' + i + ']' + '[image_path]', '');
                            params.append(i, data.image_file);
                        } else {
                            // 未変更
                            params.append('choiceList[' + i + ']' + '[image_path]', this.rmUndefinedBlank(data.image_path));
                        }
                    }
                }
            });
            if (!submitFlg) {
                this.loading = false;
                return false;
            }

            axios.post('/choice-edit/save', params, {headers: {'Content-Type': 'multipart/form-data'}})

            .then( function (response) {
                this.loading = false

                if (response.data.error != undefined) {
                    // キーワードが既に存在している場合
                    this.errors.choice_keyword = response.data.error;
                } else if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/choice-list' + window.location.search
                    location.href = listUrl
                }else {
                    // 失敗
                    // window.onbeforeunload = null;
                    location.reload();
                    // alert(MSG_ERROR)
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
            this.choicedata.forEach((data, i) => {
                if (this.rmUndefinedBlank(data.id) != '') {
                    params.append(i, this.rmUndefinedBlank(data.id));
                }
            })
            axios.post('/choice-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/choice-list' + window.location.search
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
            var listUrl = '/choice-list' + window.location.search
            window.onbeforeunload = null;
            location.href = listUrl            
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
.imagePreview {
    /* width: 265px;
    height: 270px; */
    width:100%;
    height: 270px;
    background-position: center center;
    background-size: cover;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    display: inline-block;
    cursor: pointer;
}
.help-text {
    padding-top: 30px;
}
.btn {
    margin-top:10px;
}
.btn-save {
    width: 80px;
}
.btn-back {
    width: 60px;
}
.btn-delete {
    margin-left: 20px;
    width: 50px;
}


</style>