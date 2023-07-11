<template>
    <div>
        <loading-component :loading="loading" />
        <div class="form-horizontal save-form">
            <form id="editForm" name="editForm" class="form-horizontal" method="post" action="/warehouse-edit/save">
                <!-- モード変更 -->
                <div class="col-md-12">
                    <button type="button" class="btn btn-danger btn-unlock" v-on:click="unlock" v-show="isLocked">ロック解除</button>
                    <button type="button" class="btn btn-primary btn-edit" v-on:click="edit" v-show="isShowEditBtn">編集</button>
                    <p class="btn btn-default btn-editing" v-show="(!isLocked && !isShowEditBtn && isEditable)">編集中</p>
                </div>
                <!-- 編集入力 -->            
                <div class="col-md-8">
                    <div class="form-group">
                        <label class="control-label col-sm-2">倉庫ID</label>
                        <div class="col-md-10">
                            <p class="form-control-static">{{ warehousedata.id }}</p>
                        </div>
                    </div>
                    <div class="form-group" v-bind:class="{'has-error': (errors.warehouse_name != '') }">
                        <label class="control-label col-sm-2"><span class="control-label" style="color:red">＊</span>倉庫名</label>
                        <div class="col-md-10">
                            <input type="text" class="form-control" v-model="inputData.warehouse_name" v-bind:readonly="isReadOnly">
                            <p class="text-danger">{{ errors.warehouse_name }}</p>
                        </div>
                    </div>
                    <div class="form-group" v-bind:class="{'has-error': (errors.warehouse_short_name != '') }">
                        <label class="control-label col-sm-2">倉庫名略称</label>
                        <div class="col-md-10">
                            <input type="text" class="form-control" v-model="inputData.warehouse_short_name" v-bind:readonly="isReadOnly">
                            <p class="text-danger">{{ errors.warehouse_short_name }}</p>
                        </div>
                    </div>

                    <div class="form-group" v-bind:class="{'has-error': (errors.base_id != '') }">     
                        <label class="control-label col-sm-2" for="acBase"><span class="control-label" style="color:red">＊</span>拠点</label>
                        <div class="col-md-10">  
                            <wj-auto-complete class="form-control" id="acBase"  v-bind:readonly="isReadOnly"
                                search-member-path="base_name, base_short_name" 
                                display-member-path="base_name" 
                                :items-source="baselist" 
                                selected-value-path="id"
                                :isReadOnly="isReadOnly"
                                :selected-value="select_base_id"
                                :max-items="baselist.length"
                                :min-length=1
                                :lost-focus="selectBaseId">
                            </wj-auto-complete>
                            <p class="text-danger">{{ errors.base_id }}</p>
                        </div>
                    </div>
                    <div class="form-group" v-bind:class="{'has-error': (errors.zipcode != '') }">
                        <label class="col-sm-2 control-label">郵便番号</label>
                        <div class="col-sm-2">
                            <!-- <input type="text" class="form-control" v-model="basedata.zipcode" v-bind:readonly="isReadOnly" maxlength="7" onblur="AjaxZip3.zip2addr(this,'','address1','address1');"> -->
                            <input type="text" class="form-control" v-model="inputData.zipcode" v-bind:readonly="isReadOnly" maxlength="7" @change="getAddress">
                            <p class="text-danger">{{ errors.zipcode }}</p>
                        </div>
                    </div>
                    <div class="form-group" v-bind:class="{'has-error': (errors.address1 != '') }">
                        <label class="col-sm-2 control-label">住所1</label>
                        <div class="col-sm-10">
                            <input type="text" id="address1" class="form-control" v-model="inputData.address1" v-bind:readonly="isReadOnly">
                            <p class="text-danger">{{ errors.address1 }}</p>
                        </div>
                    </div>
                    <div class="form-group" v-bind:class="{'has-error': (errors.address2 != '') }">
                        <label class="col-sm-2 control-label">住所2</label>
                        <div class="col-sm-10">
                            <input type="text" class="form-control" v-model="inputData.address2" v-bind:readonly="isReadOnly">
                            <p class="text-danger">{{ errors.address2 }}</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">日本測地計</label>
                        <div class="col-sm-10">
                            <div v-bind:class="{'has-error': (errors.latitude_jp != '') }">
                                <label class="col-sm-2 control-label">緯度</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" v-model="inputData.latitude_jp" v-bind:readonly="isReadOnly">
                                    <p class="text-danger">{{ errors.latitude_jp }}</p>
                                </div>
                            </div>
                            <div v-bind:class="{'has-error': (errors.longitude_jp != '') }">
                                <label class="col-sm-2 control-label">経度</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" v-model="inputData.longitude_jp" v-bind:readonly="isReadOnly">
                                    <p class="text-danger">{{ errors.longitude_jp }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-sm-2 control-label">世界測地計</label>
                        <div class="col-sm-10">
                            <div v-bind:class="{'has-error': (errors.latitude_world != '') }">
                                <label class="col-sm-2 control-label">緯度</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" v-model="inputData.latitude_world" v-bind:readonly="isReadOnly">
                                    <p class="text-danger">{{ errors.latitude_world }}</p>
                                </div>
                            </div>
                            <div v-bind:class="{'has-error': (errors.longitude_world != '') }">
                                <label class="col-sm-2 control-label">経度</label>
                                <div class="col-sm-3">
                                    <input type="text" class="form-control" v-model="inputData.longitude_world" v-bind:readonly="isReadOnly">
                                    <p class="text-danger">{{ errors.longitude_world }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- <div class="form-group">
                        <label class="col-md-2 control-label">種別</label>
                        <el-radio-group v-model="inputData.truck_flg" v-bind:disabled="isReadOnly">
                            <div class="radio col-md-6">
                                <el-radio :label="FLG_OFF">倉庫</el-radio>
                            </div>
                            <div class="radio col-md-6">
                                <el-radio :label="FLG_ON">トラック</el-radio>
                            </div>
                        </el-radio-group>
                    </div> -->
                </div>

                <!-- サブ -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新日時</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ warehousedata.update_at|datetime_format }}</p>
                    </div>
                    <div class="form-group">
                        <label class="col-md-4 col-sm-2 control-label">更新者</label>
                        <p class="col-md-8 col-sm-10 form-control-static">{{ warehousedata.staff_name }}</p>
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
                    <button type="button" class="btn btn-danger btn-delete" v-show="(!isReadOnly && this.warehousedata.id)" v-on:click="del">削除</button>      
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
        FLG_ON: 1,
        FLG_OFF: 0,

        select_base_id: '',
        inputData: {
            warehouse_name: '',
            warehouse_short_name: '',
            zipcode: '',
            address1: '',
            address2: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: '',
            // truck_flg: '',
        }, 

        errors: {
            warehouse_name: '',
            warehouse_short_name: '',
            base_id: '',
            zipcode: '',
            address1: '',
            address2: '',
            latitude_jp: '',
            longitude_jp: '',
            latitude_world: '',
            longitude_world: '',
        },
        
    }),
    props: {
        warehousedata: Object,
        baselist: Array,
        isEditable: Number,
        isOwnLock: Number,
        lockdata: Object,
    },

    created() {
        // TODO:ロック中の場合は編集ボタンを押せないようにして、ロック中の表示を出す
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.warehousedata.id == null) {
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
        // 拠点コンボボックスの先頭に空行追加
        this.baselist.splice(0, 0, '')
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
        if(this.warehousedata.id !== undefined && this.warehousedata.id !== null)
        {
            this.select_base_id = this.warehousedata.owner_id
            this.inputData = this.warehousedata;
        } 
        // else {
        //     this.inputData.truck_flg = this.FLG_OFF;
        // }
    },

    methods: {
        // 郵便番号から住所取得
        getAddress() {
            var zipcode = this.inputData.zipcode
            var warehouse = this.inputData
            new YubinBango.Core(zipcode, function(addr) {
                var addr1 = addr.region + addr.locality + addr.street
                warehouse.address1 = addr1
            })
        },
        save() {
            this.loading = true

            // エラーの初期化
            this.initErr(this.errors);

            // 入力値の取得
            var params = new URLSearchParams();
            params.append('id', (this.warehousedata.id !== undefined) ? this.warehousedata.id : '');
            params.append('warehouse_name', this.rmUndefinedBlank(this.inputData.warehouse_name));
            params.append('warehouse_short_name', this.rmUndefinedBlank(this.inputData.warehouse_short_name));
            params.append('base_id', (this.select_base_id !== undefined && this.select_base_id !== 0) ? this.rmUndefinedBlank(this.select_base_id) : '');
            params.append('zipcode', this.rmUndefinedBlank(this.inputData.zipcode));
            params.append('address1', this.rmUndefinedBlank(this.inputData.address1));
            params.append('address2', this.rmUndefinedBlank(this.inputData.address2));
            params.append('latitude_jp', this.rmUndefinedBlank(this.inputData.latitude_jp));
            params.append('longitude_jp', this.rmUndefinedBlank(this.inputData.longitude_jp));
            params.append('latitude_world', this.rmUndefinedBlank(this.inputData.latitude_world));
            params.append('longitude_world', this.rmUndefinedBlank(this.inputData.longitude_world));
            // params.append('truck_flg', this.rmUndefinedZero(this.inputData.truck_flg));
            
            axios.post('/warehouse-edit/save', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/warehouse-list' + window.location.search
                    location.href = listUrl
                } else {
                    // 失敗
                    window.onbeforeunload = null;
                    location.reload()
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

        del() {
            if (!confirm(MSG_CONFIRM_DELETE)) {
                return;
            }
            
            this.loading = true

            var params = new URLSearchParams();
            params.append('id', (this.warehousedata.id !== undefined) ? this.warehousedata.id : '');
            axios.post('/warehouse-edit/delete', params)

            .then( function (response) {
                this.loading = false

                if (response.data) {
                    // 成功
                    window.onbeforeunload = null;
                    var listUrl = '/warehouse-list' + window.location.search
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
            var listUrl = '/warehouse-list' + window.location.search
            if (!this.isReadOnly && this.warehousedata.id) {
                // ロック解放
                var params = new URLSearchParams();
                params.append('screen', 'warehouse-edit');
                params.append('keys', this.rmUndefinedBlank(this.warehousedata.id));
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
            params.append('screen', 'warehouse-edit');
            params.append('keys', this.rmUndefinedBlank(this.warehousedata.id));
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
            params.append('screen', 'warehouse-edit');
            params.append('keys', this.rmUndefinedBlank(this.warehousedata.id));
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

        selectBaseId: function(sender) {
            // LostFocus時に選択した拠点を取得。TODO:メソッド使わずにプロパティで直接取得する方法がわからない
            this.select_base_id = ((sender.selectedValue !== undefined && sender.selectedValue !== null) ? sender.selectedValue : '');
        },        
    },


};


</script>
