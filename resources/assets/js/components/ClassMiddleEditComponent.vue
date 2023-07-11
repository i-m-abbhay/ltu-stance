<template>
    <div>
        <loading-component :loading="loading" />   
        <div class="form-horizontal">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/class-middle-edit/save">
                <!-- モード変更 -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                    <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                    <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                </div>
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <div class="form-group">
                        <label class="col-sm-2 control-label">ID</label>
                        <div class="col-sm-10">
                            <p class="form-control-static">{{ middledata.id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><span class="required-mark">＊</span>中分類名</label>
                        <div class="col-md-8" v-bind:class="{'has-error': (errors.class_middle_name != '') }">
                            <label class="inp">
                                <input type="text" class="form-control" placeholder=" " v-model="middledata.class_middle_name" v-bind:readonly="isReadOnly">
                                <span class="label"></span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.class_middle_name }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><span class="required-mark">＊</span>大分類名</label>
                        <div class="col-md-10" v-bind:class="{'has-error': (errors.class_big_id != '') }">
                            <div class="col-md-8">
                                <wj-auto-complete class="form-control"
                                    search-member-path="class_big_name"
                                    display-member-path="class_big_name" 
                                    :items-source="classbiglist" 
                                    selected-value-path="id"
                                    :selected-value="inputData.class_big_id"
                                    :isDisabled="isReadOnly"
                                    :initialized="initBig"
                                    :max-items="classbiglist.length"
                                ></wj-auto-complete>
                                <p class="text-danger">{{ errors.class_big_id }}</p>
                            </div>
                        </div>
                    </div>

                </div>
                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新日時</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ middledata.update_at|datetime_format }}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新者</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ middledata.update_user_name }}</p>
                    </div>
                    <div v-show="(this.rmUndefinedBlank(this.lockdata.id) != '')">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-2 control-label">ロック日時</label>
                            <p class="col-md-8 col-sm-10 form-control-static">{{ lockdata.lock_dt|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-2 control-label">ロック者</label>
                            <p class="col-md-8 col-sm-10 form-control-static">{{ lockdata.lock_user_name }}</p>
                        </div>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="col-md-12">
                    <div class="col-md-10 col-md-offset-1 text-center">        
                        <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && isEditable && middledata.id)" v-on:click="del">削除</button>   
                        <button type="button" class="btn btn-primary btn-save" v-show="(isEditable && !isReadOnly)" v-on:click="save">登録</button>             
                    </div>    
                    <div class="col-md-1 text-right">    
                        <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    </div>
                </div>
                <div class=""
            </form>
        </div>
    </div>
</template>

<script>
export default {
    data: () => ({
        loading: false,
        isReadOnly: true,
        isShowEditBtn: false,
        isLocked: false,

        FLG_OFF: 0,
        FLG_ON: 1,

        inputData: {
            id: '',
            class_big_id: '',
            class_middle_name: '',
            del_flg: '',
            created_user: '',
            created_at: '',
            update_user: '',
            update_at: '',
        },
        wjInputObj: {
            class_big_id: {},
        },

        errors: {
            class_middle_name: '',
            class_big_id: '',
        },
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        middledata: {},
        classbiglist: Array,
    },
    created() {
        // propsで受け取った値をローカル変数に入れる
        this.inputData = this.middledata

        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.middledata.id == null) {
                this.isShowEditBtn = false;
                this.isReadOnly = false;
            }

            // ロック中判定
            if (this.rmUndefinedBlank(this.lockdata.id) != '' && this.isOwnLock != 1) {
                this.isLocked = true;
                this.isShowEditBtn = false;
                this.isReadOnly = true;
            }
        }
    },
    mounted() {
        if (this.isLocked) {
            // ロック中
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        } else {
            // 編集モードで開くか判定
            var query = window.location.search;
            if (this.isOwnLock == 1 || this.isEditMode(query, this.isReadOnly, this.isEditable)) {
                this.isReadOnly = false
                this.isShowEditBtn = false
            }
        }

        // 新規の場合
        var currentUrl = location.href
        var separatorDir = currentUrl.split('/');
        if (separatorDir.pop() === 'new') {
            window.onbeforeunload = null;
        }
        // 照会モードの場合
        if (this.isReadOnly) {
            window.onbeforeunload = null;
        }
    },
    methods: {
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('class_middle_name', this.rmUndefinedBlank(this.inputData.class_middle_name));
            params.append('class_big_id', this.rmUndefinedBlank(this.wjInputObj.class_big_id.selectedValue));

            axios.post('/class-middle-edit/save', params)

            .then( function (response) {
                this.loading = false
                
                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/class-middle-list' + window.location.search
                    location.href = listUrl
                }else {
                    // 失敗
                    window.onbeforeunload = null;
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
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.middledata.id));
            axios.post('/class-middle-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/class-middle-list' + window.location.search
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
            var listUrl = '/class-middle-list' + window.location.search

            if (!this.isReadOnly && this.middledata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'class-middle-edit');
                params.append('keys', this.rmUndefinedBlank(this.middledata.id));
                axios.post('/common/release-lock', params)

                .then( function (response) {
                    this.loading = false
                    if (response.data) {
                        window.onbeforeunload = null;
                        location.href = listUrl
                    } else {
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
                }.bind(this))
            } else {
                window.onbeforeunload = null;
                location.href = listUrl
            }
        },
        // 編集モード
        edit() {
            var params = new URLSearchParams();
            params.append('screen', 'class-middle-edit');
            params.append('keys', this.rmUndefinedBlank(this.middledata.id));
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING)
                        location.reload()
                    } else {
                        this.isReadOnly = false
                        this.isShowEditBtn = false
                        this.lockdata = response.data.lockdata
                    }
                } else {
                    window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
        },
        // ロック解除
        unlock() {
            if (!confirm(MSG_CONFIRM_UNLOCK)) {
                return;
            }
            var params = new URLSearchParams();
            params.append('screen', 'class-middle-edit');
            params.append('keys', this.rmUndefinedBlank(this.middledata.id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    // 編集中状態へ
                    this.isLocked = false
                    this.isReadOnly = false
                    this.isShowEditBtn = false
                    this.lockdata = response.data.lockdata
                    window.onbeforeunload = function(e) {
                        return MSG_CONFIRM_LEAVE;
                    };
                } else {
                    // ロック取得失敗
                    window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
            }.bind(this))
        },
        initBig: function(sender){
            this.wjInputObj.class_big_id = sender;
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
.help-text {
    padding-top: 30px;
}

</style>