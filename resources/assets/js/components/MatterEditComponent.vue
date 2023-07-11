<template>
    <div>
        <loading-component :loading="loading" />
        <div class="row">
            <div class="col-md-12 text-right">
                <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック日時：{{ lockdata.lock_dt|datetime_format }}&emsp;</label>
                <label class="form-control-static" v-show="(rmUndefinedBlank(lockdata.id) != '')">ロック者：{{ lockdata.lock_user_name }}&emsp;</label>
                <button type="button" class="btn btn-danger pull-right btn-unlock" v-on:click="unlock" v-show="isLocked" v-bind:disabled="matterData.complete_flg == FLG_ON">ロック解除</button>
                <button type="button" class="btn btn-primary pull-right btn-edit" v-on:click="edit" v-show="isShowEditBtn" v-bind:disabled="matterData.complete_flg == FLG_ON">編集</button>
                <div class="pull-right">
                    <p class="btn btn-default btn-editing" v-show="(!isReadOnly)">編集中</p>
                </div>
            </div>
        </div>
        <div class="main-body col-sm-12">
            <div class="form-horizontal save-form">
                <form id="saveForm">
                    <!-- メイン -->
                    <div class="form-group">
                        <div class="col-sm-12">
                            <p class="text-danger">{{ errors.duplicated }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4" v-bind:class="{'has-error': (errors.customer_id != '')}">
                            <label class="control-label">得意先名</label>
                            <wj-auto-complete class="form-control" id="acCustomer" :initialized="initCustomer"
                                search-member-path="customer_name"
                                display-member-path="customer_name"
                                selected-value-path="id"
                                :is-required="false"
                                :max-items="customerData.length"
                                :isDisabled="!isNew"
                                :items-source="customerData"
                                v-bind:readonly="isReadOnly">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.customer_id }}</p>
                        </div>
                        <div class="col-sm-offset-2 col-sm-4" v-bind:class="{'has-error': (errors.department_id != '')}">
                            <label class="control-label">担当部門</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="department_name"
                                display-member-path="department_name"
                                selected-value-path="id"
                                :initialized="initDepartment"
                                :selected-value="matterData.department_id"
                                :is-required="false"
                                :items-source="departmentList"
                                :selectedIndexChanged="selectDepartment"
                                :textChanged="setTextChanged"
                                :max-items="departmentList.length"
                                :min-length=1
                                v-bind:readonly="isReadOnly">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.department_id }}</p>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-4" v-bind:class="{'has-error': (errors.owner_name != '')}">
                            <label class="control-label">施主名</label>
                            <wj-auto-complete class="form-control" id="acOwner" :initialized="initOwner"
                                search-member-path="owner_name"
                                display-member-path="owner_name"
                                selected-value-path="owner_name" 
                                :max-items="ownerData.length"
                                :items-source="ownerData" 
                                :is-editable="true"
                                v-bind:readonly="isReadOnly">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.owner_name }}</p>
                        </div>
                        <div class="col-sm-offset-2 col-sm-4" v-bind:class="{'has-error': (errors.staff_id != '')}" >
                            <label class="control-label">担当者</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="staff_name"
                                display-member-path="staff_name"
                                selected-value-path="id"
                                :initialized="initStaff"
                                :selected-value="matterData.staff_id"
                                :is-required="false"
                                :textChanged="setTextChanged"
                                :min-length=1
                                v-bind:readonly="isReadOnly">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.staff_id }}</p>
                        </div>    
                    </div>
                    <div class="row">
                        <div class="col-sm-6" v-bind:class="{'has-error': (errors.architecture_type != '')}">
                            <div class="text-left">
                                <label class="control-label" style="font-weight:400">建築種別</label>
                            </div>
                            <el-radio-group v-model="selectedArchitectureType" v-bind:disabled="isReadOnly">
                                <div class="radio" v-for="architecture in architectureData" :key="architecture.value_code">
                                    <el-radio :label="architecture.value_code">{{ architecture.value_text_1 }}</el-radio>
                                </div>
                            </el-radio-group>
                            <p class="text-danger">{{ errors.architecture_type }}</p>
                        </div>
                    </div>
                        
                    <div class="row btn-area">
                        <div class="col-sm-offset-4 col-sm-3 col">
                            <button type="button" class="btn btn-save" v-on:click="save" v-bind:disabled="isReadOnly">案件情報登録</button>
                        </div>
                        <div class="col-sm-offset-2 col-sm-3 col">
                            <a type="button" class="btn btn-edit" v-show="!isNew" :href="'/address-edit/' + matterData.id + '?url=/matter-list'">住所情報入力</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <div>
            <div class="col-sm-offset-11 col-sm-1">
                <button type="button" class="btn bnt-sm btn-back" v-on:click="back">戻る</button>
            </div>
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
        isNew: true,
        selectedArchitectureType: 1,


        

        wjMatterObj: {
            type: Object,
            customer: null,
            owner: null,
            department: null,
            staff: null,
        },

        errors: {
            duplicated: '',
            customer_id: '',
            owner_name: '',
            architecture_type: '',
            department_id: '',
            staff_id: '',
        },
    }),
    props: {
        isOwnLock: Number,
        lockdata: {},
        matterData: {},
        customerData: Array,
        ownerData: Array,
        architectureData: Array,
        departmentList: Array,
        staffDepartmentList: Object,
        staffList: Array,
    },
    created() {
        if(this.matterData.id != null){
            this.selectedArchitectureType = this.matterData.architecture_type;
            this.isNew = false;
        }

        // ロックされているか
        if(this.isNew){
            // 新規作成
            this.isShowEditBtn  = false;
            this.isReadOnly     = false;
            this.isLocked       = false;
        }else{
            // 編集
            if(this.rmUndefinedZero(this.lockdata.id) !== 0){
                if(this.isOwnLock === this.FLG_ON){
                    // 自分がロックしている
                    this.isShowEditBtn  = false;
                    this.isReadOnly     = false;
                    this.isLocked       = false;
                }else{
                    this.isShowEditBtn  = false;
                    this.isReadOnly     = true;
                    this.isLocked       = true;
                }
            }else{
                // ロックしていない場合は読み取り専用で編集ボタン表示
                this.isShowEditBtn  = true;
                this.isReadOnly     = true;
            }
        }
    },
    mounted() {
        if(this.matterData.id != null){
            this.setStaffList(this.matterData.department_id);
            this.wjMatterObj.staff.selectedValue =  this.rmUndefinedZero(this.matterData.staff_id);
        }
        // 照会モードの場合
        if (this.isReadOnly || this.isNew) {
            window.onbeforeunload = null;
        }
    },
    methods: {
        setTextChanged: function(sender) {
            this.setAutoCompleteValue(sender);
        },
        initCustomer: function(sender){
            if (this.matterData.id == null) {
                // 新規案件の場合は初期値空白
                sender.selectedIndex = -1;
            } else {
                sender.selectedValue = this.matterData.customer_id;
            }
            this.wjMatterObj.customer = sender;
        },
        initOwner: function(sender){
            if (this.matterData.id == null) {
                // 新規案件の場合は初期値空白
                sender.selectedIndex = -1;
            } else{
                sender.selectedValue = this.matterData.owner_name;    
            }
            this.wjMatterObj.owner = sender;
        },
        initDepartment: function(sender){
            if (this.matterData.id == null) {
                // 新規案件の場合は初期値空白
                sender.selectedIndex = -1;
            } else{
                sender.selectedValue = this.matterData.department_id;    
            }
            this.wjMatterObj.department = sender;
        },
        initStaff: function(sender){
            sender.itemsSource = [];
            if (this.matterData.id == null) {
                // 新規案件の場合は初期値空白
                sender.selectedIndex = -1;
            } else{
                sender.selectedValue = this.matterData.staff_id;    
            }
            this.wjMatterObj.staff = sender;
        },

        // 部門選択時に呼ぶ
        selectDepartment(sender){
            if(sender.selectedItem !== null){
                this.setStaffList(sender.selectedItem.id);
            }else{
                this.setStaffList(0);
            }
        },
        // 担当者のリストをセットする
        setStaffList(departmentId){
            var tmpStaff = [];
            if(this.wjMatterObj.staff !== null){
                if (this.rmUndefinedZero(departmentId) !== 0 && this.staffDepartmentList[departmentId] != undefined) {
                    tmpStaff = this.staffList.filter(rec => {
                        return (this.staffDepartmentList[departmentId].indexOf(rec.id) !== -1)
                    })   
                }
                
                this.wjMatterObj.staff.itemsSource = tmpStaff;
                this.wjMatterObj.staff.selectedIndex = -1;
            }
        },
        
        save() {
            this.loading = true
            
            // エラーの初期化
            this.initErr(this.errors);
            var params = new URLSearchParams();
            params.append('id', (this.matterData.id !== undefined) ? this.matterData.id : '');
            params.append('customer_id', this.rmUndefinedBlank(this.wjMatterObj.customer.selectedValue));
            params.append('owner_name', this.rmUndefinedBlank(this.wjMatterObj.owner.text));
            params.append('architecture_type', this.rmUndefinedBlank(this.selectedArchitectureType));
            params.append('department_id', this.rmUndefinedBlank(this.wjMatterObj.department.selectedValue));
            params.append('staff_id', this.rmUndefinedBlank(this.wjMatterObj.staff.selectedValue));

            axios.post('/matter-edit/save', params)
            .then( function (response) {
                if (response.data.status == true) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/matter-edit/' + response.data.id + window.location.search
                    location.href = (listUrl)
                } else {
                    // 失敗
                    // if (response.data.message) {
                    //     alert(response.data.message)
                    // } else {
                    //     alert(MSG_ERROR);
                    // }
                    window.onbeforeunload = null;
                    location.reload()
                }
                
            }.bind(this))

            .catch(function (error) {
                if (error.response.data.errors) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                    this.loading = false
                } else {
                    if (error.response.data.message) {
                        alert(error.response.data.message)
                    } else {
                        alert(MSG_ERROR)
                    }
                    window.onbeforeunload = null;
                    location.reload()
                }
            }.bind(this))
        },
        // 編集モード
        edit() {
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'matter-edit');
            params.append('keys', this.rmUndefinedZero(this.matterData.id));
            axios.post('/common/lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    if (response.data.isLocked) {
                        alert(MSG_EDITING);
                        location.reload();
                    } else {
                        window.onbeforeunload = null;
                        location.reload();
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
            this.loading = true
            var params = new URLSearchParams();
            params.append('screen', 'matter-edit');
            params.append('keys', this.rmUndefinedZero(this.matterData.id));
            axios.post('/common/gain-lock', params)

            .then( function (response) {
                this.loading = false
                if (response.data.status) {
                    window.onbeforeunload = null;
                    location.reload();
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
        // 戻る
        back() {
            var listUrl = '/matter-list' + window.location.search;
            if (!this.isReadOnly && !this.isNew) {
                this.loading = true;

                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'matter-edit');
                params.append('keys', this.rmUndefinedZero(this.matterData.id));
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
    }
};
</script>
<style>
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn-area > .col {
    margin-top: 5px;
}
button.btn-back {
    margin-top: 10px;
}
</style>
