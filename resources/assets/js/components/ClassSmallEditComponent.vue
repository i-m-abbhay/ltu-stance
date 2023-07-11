<template>
    <div>
        <loading-component :loading="loading" />   
        <div class="form-horizontal">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/class-small-edit/save">
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
                            <p class="form-control-static">{{ classSmallData.id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label form-reg"><span class="required-mark">＊</span>工程名称</label>
                        <div class="col-md-7" v-bind:class="{'has-error': (errors.class_small_name != '') }">
                            <label class="inp">
                                <input type="text" class="form-control" placeholder=" " v-model="inputData.class_small_name" v-bind:readonly="isReadOnly">
                                <span class="label"></span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.class_small_name }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><span class="required-mark">＊</span>工事区分名</label>
                        <div class="col-md-10" v-bind:class="{'has-error': (errors.construction_id != '') }">
                            <div class="col-md-8">
                                <wj-auto-complete class="form-control"
                                    search-member-path="construction_name"
                                    display-member-path="construction_name" 
                                    :items-source="constructionList" 
                                    selected-value-path="id"
                                    :selected-value="inputData.construction_id"
                                    :isDisabled="isReadOnly"
                                    :initialized="initConstruction"
                                    :max-items="constructionList.length"
                                ></wj-auto-complete>
                                <p class="text-danger">{{ errors.construction_id }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label form-reg"><span class="required-mark">＊</span>工期日数</label>
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.construction_period_days != '') }">
                            <label class="inp">
                                <input type="number" step="1" min="1" class="form-control" placeholder=" " v-model="inputData.construction_period_days" v-bind:readonly="isReadOnly">
                                <span class="label"></span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.construction_period_days }}</p>
                        </div>
                        <div class="col-md-1">
                            <label class="control-label form-reg">日間</label>
                        </div>
                        <div class="col-md-4" v-bind:class="{'has-error': (errors.date_calc_flg != '') }">
                            <div class="form-reg">
                                <el-checkbox v-model="inputData.date_calc_flg" :false-label=0 :true-label=1 v-bind:disabled="isReadOnly">営業日のみ</el-checkbox>
                                <p class="text-danger">{{ errors.date_calc_flg }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label form-reg"><span class="required-mark">＊</span>基準日</label>
                        <div class="col-md-4">
                            <el-radio-group class="col-md-12 form-reg" v-model="inputData.base_date_type" v-bind:disabled="isReadOnly" @input="changeBaseDateType">
                                <el-radio class="col-md-3" :label="BASE_DATE_TYPE.CONSTRUCTION">着工日</el-radio>
                                <el-radio class="col-md-3" :label="BASE_DATE_TYPE.RAISING">上棟日</el-radio>
                                <el-radio class="col-md-3" :label="BASE_DATE_TYPE.PROCESS">工程</el-radio>
                            </el-radio-group>
                            <p class="text-danger">{{ errors.base_date_type }}</p>
                        </div>

                        <div class="col-md-4" v-bind:class="{'has-error': (errors.base_class_small_id != '') }">
                            <div class="col-md-10">
                                <wj-auto-complete class="form-control"
                                    search-member-path="class_small_name"
                                    display-member-path="class_small_name" 
                                    :items-source="classSmallList" 
                                    selected-value-path="id"
                                    :selected-value="inputData.base_class_small_id"
                                    :isDisabled="(isReadOnly || inputData.base_date_type != null)"
                                    :initialized="initSmall"
                                    :max-items="classSmallList.length"
                                ></wj-auto-complete>
                                <p class="text-danger">{{ errors.base_class_small_id }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label form-reg"><span class="required-mark">＊</span>基準日からの日数</label>
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.start_date_calc_days != '') }">
                            <label class="inp">
                                <input type="number" step="1" class="form-control" placeholder=" " v-model="inputData.start_date_calc_days" v-bind:readonly="isReadOnly">
                                <span class="label"></span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.start_date_calc_days }}</p>
                        </div>
                        <div class="col-md-1">
                            <label class="control-label form-reg">日</label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="col-md-2 control-label form-reg"><span class="required-mark">＊</span>発注タイミング</label>
                        <div class="col-md-2" v-bind:class="{'has-error': (errors.order_timing != '') }">
                            <label class="inp">
                                <input type="number" min="0" step="1" class="form-control" placeholder=" " v-model="inputData.order_timing" v-bind:readonly="isReadOnly">
                                <span class="label"></span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.order_timing }}</p>
                        </div>
                        <div class="col-md-1">
                            <label class="control-label form-reg">日前</label>
                        </div>
                        <div class="col-md-4" v-bind:class="{'has-error': (errors.rain_flg != '') }">
                            <div class="form-reg">
                                <el-checkbox v-model="inputData.rain_flg" :false-label=0 :true-label=1 v-bind:disabled="isReadOnly">雨延期</el-checkbox>
                                <p class="text-danger">{{ errors.rain_flg }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新日時</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ classSmallData.update_at|datetime_format }}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新者</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ classSmallData.update_user_name }}</p>
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
                        <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && isEditable && classSmallData.id)" v-on:click="del">削除</button>   
                        <button type="button" class="btn btn-primary btn-save" v-show="(isEditable && !isReadOnly)" v-on:click="save">登録</button>             
                    </div>    
                    <div class="col-md-1 text-right">    
                        <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    </div>
                </div>
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

        BASE_DATE_TYPE: {
            CONSTRUCTION: 1,
            RAISING: 2,
            PROCESS: null,
        },

        inputData: {
            id: '',
            construction_id: '',
            class_small_name: '',
            construction_period_days: '',
            base_date_type: 1,
            base_class_small_id: '',
            date_calc_flg: '',
            start_date_calc_days: '',
            order_timing: '',
            rain_flg: '',
            del_flg: '',
            created_user: '',
            created_at: '',
            update_user: '',
            update_at: '',
        },
        wjInputObj: {
            construction_id: {},
            base_class_small_id: {},
        },

        errors: {
            construction_id: '',
            class_small_name: '',
            construction_period_days: '',
            base_date_type: '',
            base_class_small_id: '',
            date_calc_flg: '',
            start_date_calc_days: '',
            order_timing: '',
            rain_flg: '',
            del_flg: '',
            created_user: '',
            created_at: '',
            update_user: '',
            update_at: '',
        },
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        classSmallData: {},
        constructionList: Array,
        classSmallList: Array,
    },
    created() {
        // propsで受け取った値をローカル変数に入れる
        if (this.classSmallData.id != null) {
            this.inputData = this.classSmallData
        }

        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.classSmallData.id == null) {
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
        changeBaseDateType() {
            if (this.inputData.base_date_type != this.BASE_DATE_TYPE.PROCESS) {
                this.wjInputObj.base_class_small_id.selectedValue = -1;
            }
        },
        // 保存
        save() {
            this.loading = true
            var OkFlg = true;

            // 基準日が工程の場合、基準日からの日数を正の数のみとする
            if (this.inputData.base_date_type == this.BASE_DATE_TYPE.PROCESS) {
                if (this.inputData.start_date_calc_days < 0) {
                    this.errors.start_date_calc_days = MSG_ERROR_START_DATE_CALC_DAYS;
                    OkFlg = false;
                }
            }

            if (!OkFlg) {
                this.loading = false;
                return;
            }

            // エラーの初期化
            this.initErr(this.errors);
            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedBlank(this.inputData.id));
            params.append('construction_id', this.rmUndefinedBlank(this.wjInputObj.construction_id.selectedValue));
            params.append('class_small_name', this.rmUndefinedBlank(this.inputData.class_small_name));
            params.append('construction_period_days', this.rmUndefinedBlank(this.inputData.construction_period_days));
            params.append('base_date_type', this.rmUndefinedBlank(this.inputData.base_date_type));
            params.append('base_class_small_id', this.rmUndefinedBlank(this.wjInputObj.base_class_small_id.selectedValue));
            params.append('date_calc_flg', this.rmUndefinedBlank(this.inputData.date_calc_flg));
            params.append('start_date_calc_days', this.rmUndefinedBlank(this.inputData.start_date_calc_days));
            params.append('order_timing', this.rmUndefinedBlank(this.inputData.order_timing));
            params.append('rain_flg', this.rmUndefinedBlank(this.inputData.rain_flg));

            axios.post('/class-small-edit/save', params)

            .then( function (response) {
                this.loading = false
                
                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/class-small-list' + window.location.search
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
            params.append('id', this.rmUndefinedBlank(this.classSmallData.id));
            axios.post('/class-small-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/class-small-list' + window.location.search
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
            var listUrl = '/class-small-list' + window.location.search

            if (!this.isReadOnly && this.classSmallData.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'class-small-edit');
                params.append('keys', this.rmUndefinedBlank(this.classSmallData.id));
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
            params.append('screen', 'class-small-edit');
            params.append('keys', this.rmUndefinedBlank(this.classSmallData.id));
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
            params.append('screen', 'class-small-edit');
            params.append('keys', this.rmUndefinedBlank(this.classSmallData.id));
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
        initSmall: function(sender){
            this.wjInputObj.base_class_small_id = sender;
        },
        initConstruction: function(sender){
            this.wjInputObj.construction_id = sender;
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
.form-reg {
    padding-top: 15px !important;
}

</style>