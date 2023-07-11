<template>
    <div>
        <loading-component :loading="loading" />   
        <div class="col-md-12 col-sm-12 col-xs-12">
            <!-- モード変更 -->
            <div class="col-md-12">
                <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
            </div>
        </div>
        <div class="form-horizontal">
            <form id="saveForm" name="saveForm" class="form-horizontal" method="post" action="/department-edit/save">
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <!-- メイン -->
                    <div v-show="rmUndefinedBlank(depData.id) != ''" class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1">部門ID</label>
                        <div class="col-md-11 col-sm-10 col-xs-12">
                            <p class="control-label form-control-static">{{ depData.id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1">部門名</label>
                        <div class="col-xs-12 col-sm-12 col-md-6" v-bind:class="{'has-error': (errors.department_name != '') }">
                            <label for="depName" class="inp">
                                <input type="text" id="depName" class="form-control" v-model="depData.department_name" placeholder=" " v-bind:readonly="isReadOnly">
                                <span for="depName" class="label"><span style="color:red;">＊</span>部門名</span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.department_name }}</p>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4" v-bind:class="{'has-error': (errors.department_symbol != '') }">
                            <label for="depSymbol" class="inp">
                                <input type="text" id="depSymbol" class="form-control" v-model="depData.department_symbol" placeholder=" " v-bind:readonly="isReadOnly">
                                <span for="depSymbol" class="label">部門記号</span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.customer_name }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1"><span style="color:red;">＊</span>拠点名</label>
                        <div class="col-xs-12 col-sm-12 col-md-5" v-bind:class="{'has-error': (errors.base_id != '') }">
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="base_name"
                                display-member-path="base_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.base_id"
                                :is-required="false"
                                :initialized="initBase"
                                :isReadOnly="isReadOnly"
                                :max-items="baselist.length"
                                :items-source="baselist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.base_id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1">親部門</label>
                        <div class="col-xs-12 col-sm-12 col-md-5" v-bind:class="{'has-error': (errors.parent_id != '') }">
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="department_name"
                                display-member-path="department_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.parent_id"
                                :is-required="false"
                                :initialized="initDepartment"
                                :isReadOnly="isReadOnly"
                                :max-items="activeDepartment.length"
                                :items-source="activeDepartment">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.parent_id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1">連絡先</label>
                        <div class="col-xs-12 col-sm-12 col-md-4" v-bind:class="{'has-error': (errors.tel != '') }">
                            <label for="depTel" class="inp">
                                <input type="text" id="depTel" class="form-control" v-model="depData.tel" placeholder=" " v-bind:readonly="isReadOnly">
                                <span for="depTel" class="label">部門代表TEL</span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.tel }}</p>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-4" v-bind:class="{'has-error': (errors.fax != '') }">
                            <label for="depFax" class="inp">
                                <input type="text" id="depFax" class="form-control" v-model="depData.fax" placeholder=" " v-bind:readonly="isReadOnly">
                                <span for="depFax" class="label">部門代表FAX</span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.fax }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1">取引銀行</label>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12 col-sm-12 col-md-5 col-md-offset-1" v-bind:class="{'has-error': (errors.own_bank_id != '') }">
                            <label class="control-label col-xs-12 col-sm-2 col-md-3">銀行1</label>
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="bank_name"
                                display-member-path="bank_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.own_bank_id_1"
                                :is-required="false"
                                :initialized="initBank_1"
                                :isReadOnly="isReadOnly"
                                :max-items="banklist.length"
                                :items-source="banklist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.own_bank_id }}</p>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-5" v-bind:class="{'has-error': (errors.own_bank_id != '') }">
                            <label class="control-label col-xs-12 col-sm-2 col-md-3">銀行2</label>
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="bank_name"
                                display-member-path="bank_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.own_bank_id_2"
                                :is-required="false"
                                :initialized="initBank_2"
                                :isReadOnly="isReadOnly"
                                :max-items="banklist.length"
                                :items-source="banklist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.own_bank_id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12 col-sm-12 col-md-5 col-md-offset-1" v-bind:class="{'has-error': (errors.own_bank_id != '') }">
                            <label class="control-label col-xs-12 col-sm-2 col-md-3">銀行3</label>
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="bank_name"
                                display-member-path="bank_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.own_bank_id_3"
                                :is-required="false"
                                :initialized="initBank_3"
                                :isReadOnly="isReadOnly"
                                :max-items="banklist.length"
                                :items-source="banklist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.own_bank_id }}</p>
                        </div>
                        <div class="col-xs-12 col-sm-12 col-md-5" v-bind:class="{'has-error': (errors.own_bank_id != '') }">
                            <label class="control-label col-xs-12 col-sm-2 col-md-3">銀行4</label>
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="bank_name"
                                display-member-path="bank_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.own_bank_id_4"
                                :is-required="false"
                                :initialized="initBank_4"
                                :isReadOnly="isReadOnly"
                                :max-items="banklist.length"
                                :items-source="banklist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.own_bank_id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-xs-12 col-sm-12 col-md-5 col-md-offset-1" v-bind:class="{'has-error': (errors.own_bank_id != '') }">
                            <label class="control-label col-xs-12 col-sm-2 col-md-3">銀行5</label>
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="bank_name"
                                display-member-path="bank_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.own_bank_id_5"
                                :is-required="false"
                                :initialized="initBank_5"
                                :max-items="banklist.length"
                                :isReadOnly="isReadOnly"
                                :items-source="banklist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.own_bank_id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1"><span style="color:red;">＊</span>部門長</label>
                        <div class="col-xs-12 col-sm-12 col-md-5" v-bind:class="{'has-error': (errors.chief_staff_id != '') }">
                            <wj-auto-complete class="form-control"  v-bind:readonly="isReadOnly"
                                search-member-path="staff_name"
                                display-member-path="staff_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="depData.chief_staff_id"
                                :is-required="false"
                                :initialized="initStaff"
                                :max-items="stafflist.length"
                                :isReadOnly="isReadOnly"
                                :items-source="stafflist">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.chief_staff_id }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label col-xs-12 col-sm-2 col-md-1">承認設定</label>
                        <div class="col-xs-12 col-sm-12 col-md-3" v-bind:class="{'has-error': (errors.standard_gross_profit_rate != '') }">
                            <label for="depRate" class="inp">
                                <input type="text" id="depRate" class="form-control" v-model="depData.standard_gross_profit_rate" placeholder=" " v-bind:readonly="isReadOnly">
                                <span for="depRate" class="label">基準粗利率</span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.standard_gross_profit_rate }}</p>
                        </div>
                        <!-- <div class="col-xs-12 col-sm-12 col-md-3" v-bind:class="{'has-error': (errors.standard_cost_total != '') }">
                            <label for="depCost" class="inp">
                                <input type="text" id="depCost" class="form-control" v-model="depData.standard_cost_total" placeholder=" " v-bind:readonly="isReadOnly">
                                <span for="depCost" class="label">基準発注額</span>
                                <span class="border"></span>
                            </label>
                            <p class="text-danger">{{ errors.standard_cost_total }}</p>
                        </div> -->
                    </div>
                </div>
                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">更新日時</label>
                            <p class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ departmentdata.update_at|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">更新者</label>
                            <p class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ departmentdata.update_user_name }}</p>
                        </div>
                    </div>
                    <div class="col-md-12" v-show="(this.rmUndefinedBlank(this.lockdata.id) != '')">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">ロック日時</label>
                            <p class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ lockdata.lock_dt|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-5 col-xs-12 control-label">ロック者</label>
                            <p class="col-md-8 col-sm-7 col-xs-12 control-label form-control-static">{{ lockdata.lock_user_name }}</p>
                        </div>
                    </div>
                </div>
                <!-- ボタン -->
                <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
                    <button type="button" class="btn btn-primary btn-save" v-show="!isReadOnly" v-on:click="save">登録</button>
                    <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && depData.id)" v-on:click="del">削除</button>
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

        depList: null,

        depData: {
            id: '',
            department_name: '',
            base_id: '',
            parent_id: '',
            department_symbol: '',
            tel: '',
            fax: '',
            own_bank_id_1: '',
            own_bank_id_2: '',
            own_bank_id_3: '',
            own_bank_id_4: '',
            own_bank_id_5: '',
            chief_staff_id: '',
            standard_gross_profit_rate: '',
            // standard_cost_total: '',
        },

        wjInputObj: {
            base_id: {},
            parent_id: {},
            own_bank_id_1: {},
            own_bank_id_2: {},
            own_bank_id_3: {},
            own_bank_id_4: {},
            own_bank_id_5: {},
            chief_staff_id: {},
        },

        errors: {
            department_name: '',
            base_id: '',
            parent_id: '',
            department_symbol: '',
            tel: '',
            fax: '',
            own_bank_id: '',
            chief_staff_id: '',
            standard_gross_profit_rate: '',
            // standard_cost_total: '',
        },
    }),
    props: {
        isEditable: Number,
        isOwnLock: Number,
        lockdata: {},
        departmentdata: {},
        baselist: Array,
        departmentlist: Array,
        banklist: Array,
        stafflist: Array,
    },
    created() {
        // TODO:ロック中の場合は編集ボタンを押せないようにして、ロック中の表示を出す
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.departmentdata.id == null) {
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
        if(this.isLocked) {
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
        this.depData = this.departmentdata;

    },
    computed: {
        activeDepartment() {
            // 親部門の選択肢から自部門を削除
            var arr = [];
            this.departmentlist.forEach(element => {
                if (element.id != this.departmentdata.id) {
                    arr.push(element);
                }
            });

            return arr;
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
            params.append('id', this.rmUndefinedBlank(this.depData.id));
            params.append('department_name', this.rmUndefinedBlank(this.depData.department_name));
            params.append('department_symbol', this.rmUndefinedBlank(this.depData.department_symbol));
            params.append('base_id', this.rmUndefinedZero(this.wjInputObj.base_id.selectedValue));
            params.append('parent_id', this.rmUndefinedZero(this.wjInputObj.parent_id.selectedValue));
            params.append('tel', this.rmUndefinedBlank(this.depData.tel));
            params.append('fax', this.rmUndefinedBlank(this.depData.fax));
            params.append('own_bank_id_1', this.rmUndefinedZero(this.wjInputObj.own_bank_id_1.selectedValue));
            params.append('own_bank_id_2', this.rmUndefinedZero(this.wjInputObj.own_bank_id_2.selectedValue));
            params.append('own_bank_id_3', this.rmUndefinedZero(this.wjInputObj.own_bank_id_3.selectedValue));
            params.append('own_bank_id_4', this.rmUndefinedZero(this.wjInputObj.own_bank_id_4.selectedValue));
            params.append('own_bank_id_5', this.rmUndefinedZero(this.wjInputObj.own_bank_id_5.selectedValue));
            params.append('chief_staff_id', this.rmUndefinedZero(this.wjInputObj.chief_staff_id.selectedValue));
            params.append('standard_gross_profit_rate', this.rmUndefinedZero(this.depData.standard_gross_profit_rate));
            // params.append('standard_cost_total', this.rmUndefinedZero(this.depData.standard_cost_total));

            axios.post('/department-edit/save', params)

            .then( function (response) {
                this.loading = false

                if (response.data.result) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/department-list' + window.location.search
                    location.href = listUrl
                } else if(!response.data.validSts) {
                    // バリデーションエラー
                    this.errors = response.data.errors;
                } else {
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
                    window.onbeforeunload = null;
                    location.reload()
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
            params.append('id', this.rmUndefinedBlank(this.depData.id));
            axios.post('/department-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/department-list' + window.location.search
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
            var listUrl = '/department-list' + window.location.search

            if (!this.isReadOnly && this.depData.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'department-edit');
                params.append('keys', this.rmUndefinedBlank(this.depData.id));
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
            params.append('screen', 'department-edit');
            params.append('keys', this.rmUndefinedBlank(this.depData.id));
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
            params.append('screen', 'department-edit');
            params.append('keys', this.rmUndefinedBlank(this.depData.id));
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
        initBase(sender) {
            this.wjInputObj.base_id = sender
        },
        initDepartment(sender) {
            this.wjInputObj.parent_id = sender
        },
        initStaff(sender) {
            this.wjInputObj.chief_staff_id = sender
        },
        initBank_1(sender) {
            this.wjInputObj.own_bank_id_1 = sender
        },
        initBank_2(sender) {
            this.wjInputObj.own_bank_id_2 = sender
        },
        initBank_3(sender) {
            this.wjInputObj.own_bank_id_3 = sender
        },
        initBank_4(sender) {
            this.wjInputObj.own_bank_id_4 = sender
        },
        initBank_5(sender) {
            this.wjInputObj.own_bank_id_5 = sender
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