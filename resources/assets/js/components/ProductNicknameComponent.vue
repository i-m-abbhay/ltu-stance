<template>
    <div>
        <loading-component :loading="loading" />   
        <div class="form-horizontal">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/item-edit/save">
                <!-- モード変更 -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                    <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                    <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                </div>
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <h4><u>対象商品</u></h4>
                    <div class="form-group">
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <label class="control-label">商品番号</label>
                            <h4><u>{{ parentproduct.product_code }}</u></h4>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <label class="control-label">商品名</label>
                            <h4><u>{{ parentproduct.product_name }}</u></h4>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <label class="control-label">型式／規格</label>
                            <h4><u>{{ parentproduct.model }}</u></h4>
                            <!-- <wj-auto-complete class="form-control"
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="parentproduct.id"
                                :is-required="false"
                                :initialized="initProductName"
                                :max-items="productlist.length"
                                :min-length="1"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                    </div>
                </div>
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <h4><u>呼び名設定</u></h4>
                    <div v-for="(name, index) in activeData" :key="index" class="col-md-12 col-sm-12 col-xs-12">
                        <div class="col-xs-10 col-sm-8 col-md-8" v-bind:class="{'has-error': (name.errors.another_name != '') }">
                            <label for="anName" class="inp">
                                <input id="anName" type="text" class="form-control" placeholder=" " v-model="name.another_name" v-bind:readonly="isReadOnly">
                                <span class="label">呼び名</span>
                                <span class="border"></span>
                            </label>                            
                            <p class="text-danger">{{ name.errors.another_name }}</p>                            
                        </div>
                        <el-button type="danger" style="margin-top:12px;" icon="el-icon-delete" circle size="mini" @click="deleteForm(index)" v-bind:disabled="isReadOnly"></el-button>
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 input-margin">
                        <p class="text-center" style="font-size:2em;">呼び名追加 <button type="button" class="btn btn-primary" style="width:200px;" @click="addForm" v-bind:disabled="isReadOnly">＋</button></p>
                    </div>
                </div>

                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-2 control-label">更新日時</label>
                            <p v-if="nicknamelist.length != 0" class="col-md-8 col-sm-10 form-control-static">{{ nicknamelist[0].update_at|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-2 control-label">更新者</label>
                            <p v-if="nicknamelist.length != 0" class="col-md-8 col-sm-10 form-control-static">{{ nicknamelist[0].update_user_name }}</p>
                        </div>
                    </div>
                    <div class="col-md-12" v-show="(this.rmUndefinedBlank(this.lockdata.id) != '')">
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
                <div class="col-md-12 col-sm-12 col-xs-12 text-center">                    
                    <button type="button" class="btn btn-primary btn-save" v-show="(isEditable && !isReadOnly)" v-on:click="save">登録</button>                    
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    <button type="button" class="btn btn-danger btn-delete" v-show="(nicknamelist.length != 0 && isEditable && !isReadOnly)" v-on:click="del">全て削除</button>
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
        isShowEditBtn: false,
        isLocked: false,

        FLG_OFF: 0,
        FLG_ON: 1,

        product_name: '',
        nameList: [],

        wjInputObj: {
            product_id: {},
        },

        errors: {
            another_name: '',
        }
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        parentproduct: {},
        nicknamelist: Array,
        // productlist: Array,
    },
    created() {
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.nicknamelist.length == 0) {
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

        if (!this.isEditable) {
            this.isReadOnly = true;
        }
        // 呼び名をローカル変数へ
        if (this.nicknamelist.length == 0) {
            this.addForm();
        } else {
            var arr = [];
            this.nicknamelist.forEach(el => {
                el.del_flg = this.FLG_OFF;
                el.errors = {
                    another_name: '',
                };
                arr.push(el)
            });
            this.nameList = arr;
        }
    },
    computed: {
        // 削除フラグ"0"のレコードのみ表示
        activeData: function() {
            return this.nameList.filter(function (row) {
                return row.del_flg == 0;
            })
        },
    },
    methods: {
        // 選択肢追加
        addForm: function() {
            var addName　= {
                id: '',
                another_name: '',
                del_flg: 0,
                errors: {
                    another_name: '',
                },
            };
            this.nameList.push(addName);
        },
        // 選択肢削除
        deleteForm(index) {
            if(this.activeData[index].id != ''){
                this.$set(this.activeData[index], 'del_flg', 1);
            }else {
                this.nameList.forEach((el, i) => {
                    if (el == this.activeData[index]) {
                        this.nameList.splice(i, 1);
                    }
                })                
            }
        },
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.nameList.forEach(element => {
               this.initErr(element.errors); 
            });
            var postFlg = true;

            for (var i = 0; i < this.nameList.length; i++) {
                if (this.nameList[i].del_flg == this.FLG_OFF) {
                    if (this.rmUndefinedBlank(this.nameList[i].another_name) == '') {
                        // 呼び名の空欄チェック
                        this.nameList[i].errors.another_name = MSG_ERROR_NO_INPUT;
                        postFlg = false;
                    }      

                    for (var j = i + 1; j < this.nameList.length; j++) {
                        if (this.nameList[j].del_flg == this.FLG_OFF && this.nameList[i].another_name == this.nameList[j].another_name
                            && this.rmUndefinedBlank(this.nameList[i].another_name) != '' 
                            && this.rmUndefinedBlank(this.nameList[j].another_name) != '') {
                            // 呼び名重複チェック
                            this.nameList[i].errors.another_name = MSG_ERROR_SAME_NICKNAME;
                            this.nameList[j].errors.another_name = MSG_ERROR_SAME_NICKNAME;
                            postFlg = false;
                        }
                    }
                }
            }

            if (!postFlg) {
                this.loading = false;
            } else {
                // 入力値を取得
                var params = new URLSearchParams();
                // params.append('id', this.rmUndefinedBlank(this.nicknamelist[0].id));
                params.append('product_id', this.rmUndefinedBlank(this.parentproduct.id));
                params.append('name_list', JSON.stringify(this.nameList));

                axios.post('/product-nickname/save', params)

                .then( function (response) {
                    this.loading = false

                    if (response.data) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/product-list' + window.location.search
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
            }
        },
        // 削除
        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.parentproduct.id));

            axios.post('/product-nickname/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/product-list' + window.location.search
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
            var listUrl = '/product-list' + window.location.search

            if (!this.isReadOnly && this.parentproduct.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'product-nickname');
                params.append('keys', this.rmUndefinedBlank(this.parentproduct.id));
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
            params.append('screen', 'product-nickname');
            params.append('keys', this.rmUndefinedBlank(this.parentproduct.id));
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
            params.append('screen', 'product-nickname');
            params.append('keys', this.rmUndefinedBlank(this.parentproduct.id));
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
        /* オートコンプリート紐付け */
        initProductName(sender) {
            this.wjInputObj.product_name = sender;
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