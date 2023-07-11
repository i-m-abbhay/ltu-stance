<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" enctype="multipart/form-data" action="/staff-edit/save">
                <!-- モード変更 -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                    <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                    <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                </div>
                <!-- ユーザ情報入力フォーム -->
                <!-- CSRF対策 -->
                <input type="hidden" name="_token" :value="csrf">
                <div class="col-md-9">
                    <div class="form-group">
                        <label class="control-label col-sm-2">ID</label>
                        <div class="col-md-10">
                            <p class="form-control-static">{{ staffdata.id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>担当者名</label>
                        <div class="col-md-4"  v-bind:class="{'has-error': (errors.staff_name != '') }">
                            <input type="text" class="form-control" v-model="inputData.staff_name" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.staff_name }}</span>
                        </div>
                         
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>担当者かな</label>
                        <div class="col-md-4"  v-bind:class="{'has-error': (errors.staff_kana != '') }">
                            <input type="text" class="form-control" v-model="inputData.staff_kana" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.staff_kana }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">担当者記号</label>
                        <div class="col-md-10" v-bind:class="{'has-error': (errors.staff_short_name != '') }">
                            <input type="text" class="form-control" v-model="inputData.staff_short_name" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.staff_short_name }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2">社員番号</label>
                        <div class="col-md-4"  v-bind:class="{'has-error': (errors.employee_code != '') }">
                            <input type="text" class="form-control" v-model="inputData.employee_code" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.employee_code }}</span>
                        </div>
                        <label class="control-label col-sm-2">役職</label>
                        <div class="col-md-4"  v-bind:class="{'has-error': (errors.position_code != '') }">
                            <input type="text" class="form-control" v-model="inputData.position_code" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.position_code }}</span>
                        </div>
                        <!-- <label class="control-label col-sm-2" for="inputSelect">役職</label>
                        <div class="col-md-4" v-bind:class="{'has-error': (errors.position_code != '') }">
                            <wj-auto-complete class="form-control" id="acPos"  v-bind:readonly="isReadOnly" @change="onChange"
                                search-member-path="value_text_1"
                                display-member-path="value_text_1" 
                                :items-source="posdata" 
                                selected-value-path="value_code"
                                :selected-value="inputData.position_code"
                                :isReadOnly="isReadOnly"
                                :min-length=1
                                :max-items="posdata.length"
                                :lost-focus="selectPosId">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.position_code }}</span>
                        </div> -->
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>部門(メイン)</label>
                            <div class="col-md-4" v-bind:class="{'has-error': (errors.department_main != '') }">
                            <wj-auto-complete class="form-control" id="acDepartment"  v-bind:readonly="isReadOnly" @change="onChange"
                                search-member-path="department_name"
                                display-member-path="department_name" 
                                :items-source="departmentdata" 
                                selected-value-path="id"
                                :isReadOnly="isReadOnly"
                                :selected-value="inputData.department_main"
                                :min-length=1
                                :lost-focus="selectDepartmentMainId"
                                :max-items="departmentdata.length">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.department_main }}</span>
                            </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-2">部門(サブ)</label>
                            <div class="col-md-4" v-bind:class="{'has-error': (errors.department_sub1 != '') }">
                            <wj-auto-complete class="form-control" id="acDepartment"  v-bind:readonly="isReadOnly" @change="onChange"
                                search-member-path="department_name" 
                                display-member-path="department_name" 
                                :items-source="departmentdata" 
                                selected-value-path="id"
                                :isReadOnly="isReadOnly"
                                :selected-value="inputData.department_sub1"
                                :min-length=1
                                :max-items="departmentdata.length"
                                :lost-focus="selectDepartmentSub1Id">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.department_sub1 }}</span>
                            </div>
                            <div class="col-md-4 col-md-offset-2" v-bind:class="{'has-error': (errors.department_sub2 != '') }">
                            <wj-auto-complete class="form-control" id="acDepartment"  v-bind:readonly="isReadOnly" @change="onChange"
                                search-member-path="department_name"
                                display-member-path="department_name" 
                                :items-source="departmentdata" 
                                selected-value-path="id"
                                :isReadOnly="isReadOnly"
                                :selected-value="inputData.department_sub2"
                                :min-length=1
                                :max-items="departmentdata.length"
                                :lost-focus="selectDepartmentSub2Id">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.department_sub2 }}</span>
                            </div>
                    </div>

                    <div class="form-group">
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>ログインID</label>
                        <div class="col-md-10"  v-bind:class="{'has-error': (errors.login_id != '') }">
                            <input type="text" class="form-control" v-model="inputData.login_id" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.login_id }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>E-Mail</label>
                        <div class="col-md-10"  v-bind:class="{'has-error': (errors.email != '') }">
                            <input type="text" class="form-control" v-model="inputData.email" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.email }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>パスワード</label>
                        <div class="col-md-10" v-bind:class="{'has-error': (errors.password != '') }">
                            <input type="password" class="form-control" v-model="inputData.password" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.password }}</span>
                        </div>
                    </div>  
                    <div class="form-group">
                        <label class="control-label col-sm-2">TEL1</label>
                        <div class="col-md-4"  v-bind:class="{'has-error': (errors.tel_1 != '') }">
                            <input type="text" class="form-control" v-model="inputData.tel_1" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.tel_1 }}</span>
                        </div>
                        <label class="control-label col-sm-2">TEL2</label>
                        <div class="col-md-4" v-bind:class="{'has-error': (errors.tel_2 != '') }">                            
                            <input type="text" class="form-control" v-model="inputData.tel_2" v-bind:readonly="isReadOnly" @change="onChange">
                            <span class="text-danger">{{ errors.tel_2 }}</span>
                        </div>
                    </div>
                    <div class="form-group">
                        <div v-bind:class="{'has-error': (errors.mobile_email != '') }">
                            <label class="control-label col-sm-2">携帯E-Mail</label>
                            <div class="col-md-10">
                                <input type="text" class="form-control" v-model="inputData.mobile_email" v-bind:readonly="isReadOnly" @change="onChange">
                                <span class="text-danger">{{ errors.mobile_email }}</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group"> 
                        <label class="control-label col-md-2 col-sm-2">印影</label>
                        <div class="col-md-10" v-bind:class="{'has-error': (errors.stamp != '') }">
                            <label class="file_label col-md-4 col-sm-4">
                                <input type="file" id="file" @change="fileSelected" accept="image/png, image/jpeg" v-bind:disabled="isReadOnly">
                                <span for="file">{{ frontStampLabel }}</span>
                                <span class="text-danger">{{ errors.stamp }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="form-group"> 
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>退職区分</label>
                        <div class="col-md-4" v-bind:class="{'has-error': (errors.retirement_kbn != '') }">
                            <label class="form-label" for="radios1">
                                <input type="radio" class="form-check-input" name="radios" id="radios1" value="0" v-model="inputData.retirement_kbn" v-bind:disabled="isReadOnly">
                            在職</label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" for="radios2">
                                <input type="radio" class="form-check-input" name="radios" id="radios2" value="1" v-model="inputData.retirement_kbn" v-bind:disabled="isReadOnly">
                            退職</label>
                            <span class="text-danger">{{ errors.retirement_kbn }}</span>
                        </div>
                    </div>
                </div>
                <!-- サブ -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新日時</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ staffdata.update_at|datetime_format }}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新者</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ staffdata.update_user_name }}</p>
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
                <div class="col-md-12 text-center">
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    <button type="button" class="btn btn-primary btn-save" v-show="!isReadOnly" v-on:click="save">登録</button>
                    <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && this.staffdata.id)" v-on:click="del">削除</button>
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
        // CSRF対策
        csrf: document.querySelector('meta[name="csrf-token"]').getAttribute('content'),

        frontStampLabel: 'ファイルを選択してください',
        inputData: {
            staff_name: '',
            staff_kana: '',
            staff_short_name: '',
            employee_code: '',
            position_code: '',
            department_main: '',
            department_sub1: '',
            department_sub2: '',
            login_id: '',
            email: '',
            password: '',
            tel_1: '',
            tel_2: '',
            mobile_email: '',
            retirement_kbn: '',
            stamp: '',
        },
        
        errors: {
            staff_name: '', 
            staff_kana: '',
            staff_short_name: '',
            employee_code: '',
            position_code : '',
            department_main: '',
            department_sub1: '',
            department_sub2: '',
            login_id: '',
            email: '',
            password: '',
            tel_1: '',
            tel_2: '',
            mobile_email: '',
            stamp: '',
            retirement_kbn: '',
        },
    }),
    props: {
         staffdata: Object,
         updatestaff: Object,
         stdepartdata: Array,
         departmentdata: Array,
         posdata: Array,
         isEditable: Number,
         isOwnLock: Number,
         lockdata: Object,
    },

    created() {
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.staffdata.id == null) {
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
        
        // 初期値設定
        if(this.staffdata.retirement_kbn == undefined && this.staffdata.retirement_kbn == null)
        {
            this.inputData.retirement_kbn = '0';
        }
        if(this.staffdata !== undefined && this.staffdata !== null && this.staffdata !== '')
        {
            if(this.staffdata.stamp !== undefined && this.staffdata.stamp !== null && this.staffdata.stamp !== ''){
                this.frontStampLabel = this.staffdata.stamp;
                this.inputData.stamp = this.staffdata.stamp;
            }
        }

        // 各コンボボックスの先頭に空行追加
        this.posdata.splice(0, 0, '')
        this.departmentdata.splice(0, 0, '')

        // if(this.staffdata !== undefined && this.staffdata !== null)
        // {
        //     this.inputData.position_code = parseInt(this.inputData.position_code)
        // }
        // if(this.stdepartdata[0] !== undefined && this.stdepartdata[0] !== null && this.stdepartdata[0].main_flg == 1)
        // {
        //     // メイン部門
        //     this.inputData.department_main = this.stdepartdata[0].department_id
        // } else if(this.stdepartdata[0] !== undefined && this.stdepartdata[0] !== null) {
        //     // メイン部門を持っていない
        //     this.inputData.department_sub1 = this.stdepartdata[0].department_id
        // }

        // if(this.stdepartdata[1] !== undefined && this.stdepartdata[1] !== null && this.inputData.department_sub1 == null && this.inputData.department_sub1 == '')
        // {
        //     this.inputData.department_sub1 = this.stdepartdata[1].department_id
        // } else if(this.stdepartdata[1] !== undefined && this.stdepartdata[1] !== null) 
        // {
        //     this.inputData.department_sub2 = this.stdepartdata[1].department_id
        // }

        // if(this.stdepartdata[2] !== undefined && this.stdepartdata[2] !== null)
        // {
        //     this.inputData.department_sub2 = this.stdepartdata[2].department_id
        // }
        var arr = [];
        if (this.stdepartdata != undefined) {
            for (var i = 0; i < this.stdepartdata.length; i++) {
                if (this.stdepartdata[i].main_flg) {
                    this.inputData.department_main = this.stdepartdata[i].department_id;
                } else if(this.rmUndefinedBlank(this.inputData.department_sub1) == '') {
                    this.inputData.department_sub1 =  this.stdepartdata[i].department_id;
                } else {
                    this.inputData.department_sub2 =  this.stdepartdata[i].department_id;
                }
            }
        }
        


    },
    mounted() {
        if(this.idLocked) {
            // ロック中
            this.isShowEditBtn = false;
            this.isReadOnly = true;
        }else {
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
        // propsデータを置き換え
        if(this.staffdata.id != null){
            this.inputData.staff_name = this.staffdata.staff_name;
            this.inputData.staff_kana = this.staffdata.staff_kana;
            this.inputData.staff_short_name = this.staffdata.staff_short_name;
            this.inputData.employee_code = this.staffdata.employee_code;
            this.inputData.position_code = this.staffdata.position_code;
            this.inputData.login_id = this.staffdata.login_id;
            this.inputData.password = this.staffdata.password;
            this.inputData.email = this.staffdata.email;
            this.inputData.tel_1 = this.staffdata.tel_1;
            this.inputData.tel_2 = this.staffdata.tel_2;
            this.inputData.mobile_email = this.staffdata.mobile_email;
            this.inputData.retirement_kbn = this.staffdata.retirement_kbn;
        }
    },

    methods: {
        save() {
            this.loading = true
            if(this.inputData.stamp == undefined && this.inputData.stamp == null && this.inputData.stamp == ''){
                this.inputData.stamp = this.staffdata.stamp;
            }
            // if(isNaN(this.inputData.position_code)){
            //     this.inputData.position_code = 0;
            // }
            
            
            // エラーの初期化
            this.initErr(this.errors);

            var params = new FormData();
            params.append('id', (this.staffdata.id !== undefined) ? this.staffdata.id : '');
            params.append('staff_name', this.rmUndefinedBlank(this.inputData.staff_name));
            params.append('staff_kana', this.rmUndefinedBlank(this.inputData.staff_kana));
            params.append('staff_short_name', this.rmUndefinedBlank(this.inputData.staff_short_name));
            params.append('employee_code', this.rmUndefinedBlank(this.inputData.employee_code));
            params.append('position_code', this.rmUndefinedZero(this.inputData.position_code));
            params.append('email', this.rmUndefinedBlank(this.inputData.email));
            params.append('login_id', this.rmUndefinedBlank(this.inputData.login_id));
            params.append('password', this.rmUndefinedBlank(this.inputData.password));
            params.append('tel_1', this.rmUndefinedBlank(this.inputData.tel_1));
            params.append('tel_2', this.rmUndefinedBlank(this.inputData.tel_2));
            params.append('mobile_email', this.rmUndefinedBlank(this.inputData.mobile_email));
            params.append('retirement_kbn', this.rmUndefinedZero(this.inputData.retirement_kbn));
            params.append('department_main', this.rmUndefinedBlank(this.inputData.department_main));
            params.append('department_sub1', this.rmUndefinedBlank(this.inputData.department_sub1));
            params.append('department_sub2', this.rmUndefinedBlank(this.inputData.department_sub2));
            params.append('stamp', this.inputData.stamp);
            axios.post('/staff-edit/save', params, {
                                                headers: {
                                                    'Content-Type': 'multipart/form-data'
                                                }
                                            })
            .then( function (response) {
                this.loading = false

                if (response.data.status == true) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/staff-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    alert(MSG_ERROR)
                    window.onbeforeunload = null;
                    // location.reload()
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
                    window.onbeforeunload = null;
                    // location.reload()
                }
                this.loading = false
            }.bind(this))
        },

    del() {
        if(!confirm(MSG_CONFIRM_DELETE)){
            return;
        }

        this.loading = true

            var params = new URLSearchParams();
            params.append('id', (this.staffdata.id !== undefined) ? this.staffdata.id : '');
            axios.post('/staff-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/staff-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    window.onbeforeunload = null;
                    alert(MSG_ERROR)
                    // location.reload();
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
            var listUrl = '/staff-list' + window.location.search
            if (!this.isReadOnly && this.staffdata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'staff-edit');
                params.append('keys', this.rmUndefinedBlank(this.staffdata.id));
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
            params.append('screen', 'staff-edit');
            params.append('keys', this.rmUndefinedBlank(this.staffdata.id));
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
            params.append('screen', 'staff-edit');
            params.append('keys', this.rmUndefinedBlank(this.staffdata.id));
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
        selectPosId: function(sender) {
            // LostFocus時に選択した役職を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.position_code = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },        
        selectDepartmentMainId: function(sender) {
            // LostFocus時に選択した部門を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.department_main = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },     
        selectDepartmentSub1Id: function(sender) {
            // LostFocus時に選択した部門を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.department_sub1 = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },
        selectDepartmentSub2Id: function(sender) {
            // LostFocus時に選択した部門を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.inputData.department_sub2 = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');        
        },

        // エラーメッセージ初期化
        onChange() {
            this.initErr(this.errors);
        },

        // 印影ファイル情報の取得
        fileSelected(event) {
            this.inputData.stamp = event.target.files[0]
            this.frontStampLabel = event.target.files[0].name
        },
        
    },

}
</script>

<style>
input[type="file"] {
    display: none;
}
.file_label {
   background: #ddd;
   padding: 3px 15px;
   display: inline-block;
   position: relative;
   border-radius: 5%;
   box-shadow: 0 2px 6px rgba(146, 156, 146, 0.8);
   cursor: pointer;
   overflow: hidden;
   text-overflow: ellipsis;
   white-space: nowrap;
}
.file_label:hover{
    background: #a9a9a9;
    color: white;   
}
.file_label:active{
    background: #696969;
}

</style>

