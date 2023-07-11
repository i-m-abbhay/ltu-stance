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
                    <h4><u>対象倉庫</u></h4>
                    <div class="form-group">
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <label class="control-label">倉庫名</label>
                            <h4><u>{{ warehousedata.warehouse_name }}</u></h4>
                        </div>
                        <div class="col-md-4 col-sm-4 col-xs-12">
                            <label class="control-label">倉庫名略称</label>
                            <h4><u>{{ warehousedata.warehouse_short_name }}</u></h4>
                        </div>
                        <div class="col-md-3 col-sm-4 col-xs-12">
                            <label class="control-label">拠点名</label>
                            <h4><u>{{ basedata.base_name }}</u></h4>                            
                        </div>
                        <div class="col-md-1 col-sm-12 col-xs-12">
                            <button type="button" class="btn btn-primary btn-save btn-csv" v-on:click="csvExport">CSV出力</button>  
                        </div>
                    </div>
                </div>
                <div class="main-body col-md-9 col-sm-12 col-xs-12">
                    <h4><u>棚番設定</u></h4>
                    <div v-for="(data, index) in activeData" :key="index" class="col-md-6 col-sm-6 col-xs-12 main-body">
                        <div class="form-group">
                            <div class="col-xs-12 col-sm-12 col-md-12 input-body" v-bind:class="{'has-error': (data.errors.shelf_area != '') }">
                                <label v-bind:for="'slfArea-' + index" class="inp">
                                    <input v-bind:id="'slfArea-' + index" type="text" class="form-control" placeholder=" " v-model="data.shelf_area" v-bind:readonly="isReadOnly">
                                    <span class="label">棚番（エリア）</span>
                                    <span class="border"></span>
                                </label>                            
                                <p class="text-danger">{{ data.errors.shelf_area }}</p>                            
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="col-xs-8 col-sm-6 col-md-6 input-body" v-bind:class="{'has-error': (data.errors.shelf_steps != '') }">
                                <label v-bind:for="'slfSteps-' + index" class="inp">
                                    <input v-bind:id="'slfSteps-' + index" type="number" class="form-control" placeholder=" " v-model="data.shelf_steps" v-bind:readonly="isReadOnly">
                                    <span class="label">棚番（段数）</span>
                                    <span class="border"></span>
                                </label>                            
                                <p class="text-danger">{{ data.errors.shelf_steps }}</p>                            
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="col-md-12 col-sm-12 col-xs-12">棚種別</label>
                            <el-radio-group class="col-md-12 col-sm-12 col-xs-12" v-model="data.shelf_kind" v-bind:disabled="(isReadOnly || data.used_flg == FLG_ON)">
                                <div class="radio col-md-3">
                                    <el-radio :label="SHELF_NORMAL">通常</el-radio>
                                </div>
                                <div class="radio col-md-3">
                                    <el-radio :label="SHELF_TEMPORARY" v-bind:disabled="kindLock.temporary">入荷一時置場</el-radio>
                                </div>
                                <div class="radio col-md-3">
                                    <el-radio :label="SHELF_RETURN" v-bind:disabled="kindLock.return">返品</el-radio>
                                </div>
                            </el-radio-group>
                        </div>
                        <button type="button" class="btn btn-danger pull-right" v-bind:disabled="(isReadOnly || data.used_flg == FLG_ON)" @click="deleteForm(index)">棚番の削除</button>
                        <!-- <el-button type="danger" style="margin-top:12px;" icon="el-icon-delete" circle size="mini" @click="deleteForm(index)" v-bind:disabled="isReadOnly"></el-button> -->
                    </div>
                    <div class="col-md-12 col-sm-12 col-xs-12 input-margin">
                        <p class="text-center" style="font-size:2em;">棚番追加 <button type="button" class="btn btn-primary" style="width:200px;" @click="addForm" v-bind:disabled="isReadOnly">＋</button></p>
                    </div>
                </div>

                <!-- サブ -->
                <div class="col-md-3 col-sm-12 col-xs-12 sub-body">
                    <div class="col-md-12">
                        <div class="form-group">
                            <label class="col-md-4 col-sm-2 control-label">更新日時</label>
                            <p v-if="shelfnumberlist.length != 0" class="col-md-8 col-sm-10 form-control-static">{{ shelfnumberlist[0].update_at|datetime_format }}</p>
                        </div>
                        <div class="form-group">
                            <label class="col-md-4 col-sm-2 control-label">更新者</label>
                            <p v-if="shelfnumberlist.length != 0" class="col-md-8 col-sm-10 form-control-static">{{ shelfnumberlist[0].update_user_name }}</p>
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
                    <button type="button" class="btn btn-danger btn-delete" v-show="(shelfnumberlist.length != 0 && isEditable && !isReadOnly)" v-on:click="del">全て削除</button>    
                    <button type="button" class="btn btn-primary btn-save" v-show="(isEditable && !isReadOnly)" v-on:click="save">登録</button>                    
                    <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>                    
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

        SHELF_NORMAL: 0,
        SHELF_TEMPORARY: 1,
        SHELF_KEEP: 2,
        SHELF_RETURN: 3,

        kindLock: {
            temporary: false,
            return: false,
        },

        product_name: '',
        shelfList: [],

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
        isShelfLock: Number,
        shelfnumberlist: Array,
        warehousedata: {},
        basedata: {},
    },
    created() {
        if (this.isEditable == FLG_EDITABLE) {
            // 編集可
            this.isShowEditBtn = true;
            if (this.shelfnumberlist.length == 0) {
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

        if (this.isShelfLock) {
            this.kindLock.return = true;
        }
        // 呼び名をローカル変数へ
        if (this.shelfnumberlist.length == 0) {
            this.addForm();
        } else {
            var arr = [];
            var aCnt = 0,
                rCnt = 0;
            this.shelfnumberlist.forEach(el => {
                el.del_flg = this.FLG_OFF;
                el.shelf_id = null;
                el.errors = {
                    shelf_area: '',
                    shelf_steps: '',
                };
                arr.push(el)
                if (el.shelf_kind == this.SHELF_TEMPORARY) {
                    aCnt++;
                }
                if (el.shelf_kind == this.SHELF_RETURN) {
                    rCnt++;
                }
            });
            this.shelfList = arr;
            // 入荷一時置場　制限
            if (aCnt > 0) {
                this.kindLock.temporary = true;
            }
            // 返品置場　制限
            if (rCnt > 0) {
                this.kindLock.return = true;
                this.isShelfLock = this.FLG_OFF;
            }
        }
    },
    computed: {
        // 削除フラグ"0"のレコードのみ表示
        activeData: function() {
            return this.shelfList.filter(function (row) {
                return row.del_flg == 0;
            })
        },
    },
    watch: {
        shelfList: {
            handler: function(list) {
                var aCnt = 0,
                    rCnt = 0;
                list.forEach(element => {
                    if (element.shelf_kind == this.SHELF_TEMPORARY) {
                        aCnt++;
                    }
                    if (element.shelf_kind == this.SHELF_RETURN) {
                        rCnt++;
                    }
                });
                if (aCnt > 0) {
                    this.kindLock.temporary = true;
                } else {
                    this.kindLock.temporary = false;
                }

                if (rCnt > 0 || this.isShelfLock) {
                    this.kindLock.return = true;
                } else {
                    this.kindLock.return = false;
                }
            },
            deep: true
        }
    },
    methods: {
        csvExport() {
            var list = this.shelfList;      
            if (this.shelfList.length == 0 || this.rmUndefinedZero(list[0].id) == 0) {
                alert(MSG_ERROR_NO_DATA);
                return false;
            }
            // this.loading = true

            var params = new URLSearchParams();
            params.append('warehouse_id', this.rmUndefinedBlank(this.warehousedata.id));

            axios.post('/shelf-number-edit/export', params, {responseType: 'blob' })
            .then( function (response) {
                // ContentDispositionからファイル名取得
                const contentDisposition = response.headers['content-disposition'];
                const regex = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/;
                const matches = regex.exec(contentDisposition);
                var filename = '';
                if (matches != null && matches[1]) {
                    const name = matches[1].replace(/['"]/g, '');
                    filename = decodeURI(name)
                } else {
                    filename = null;
                }

                // CSVファイルのダウンロード
                const url = URL.createObjectURL(new Blob([response.data]));
                const link = document.createElement('a');
                link.href = url;
                link.setAttribute('download', filename); 
                document.body.appendChild(link);
                link.click();
                URL.revokeObjectURL(link);

                // this.loading = false
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
        // 選択肢追加
        addForm: function() {
            var addShelf = {
                id: '',
                shelf_id: null,
                shelf_area: '',
                shelf_steps: '',
                shelf_kind: 0,
                del_flg: 0,
                errors: {
                    shelf_area: '',
                    shelf_steps: '',
                },
            };
            this.shelfList.push(addShelf);
        },
        // 選択肢削除
        deleteForm(index) {
            if(this.activeData[index].id != ''){
                this.$set(this.activeData[index], 'del_flg', 1);
            }else {
                this.shelfList.forEach((el, i) => {
                    if (el == this.activeData[index]) {
                        this.shelfList.splice(i, 1);
                    }
                })                
            }
        },
        // 保存
        save() {
            this.loading = true

            // エラーの初期化
            this.shelfList.forEach(element => {
               this.initErr(element.errors); 
            });
            var OkFlg = true;

            if (this.shelfList.length == 0) {
                alert(MSG_ERROR_NO_DATA)
                OkFlg = false;
            }

            for (var i = 0; i < this.shelfList.length; i++) {
                if (this.shelfList[i].del_flg == this.FLG_OFF) {
                    if (this.rmUndefinedBlank(this.shelfList[i].shelf_area) == '') {
                        // 空欄チェック
                        this.shelfList[i].errors.shelf_area = MSG_ERROR_NO_INPUT;
                        OkFlg = false;
                    }
                    if (this.shelfList[i].shelf_area.length > 30) {
                        // 文字数チェック
                        this.shelfList[i].errors.shelf_area = '30' + MSG_ERROR_LIMIT_OVER;
                        OkFlg = false;
                    }
                    if (this.rmUndefinedBlank(this.shelfList[i].shelf_steps) == '') {
                        // 空欄チェック
                        this.shelfList[i].errors.shelf_steps = MSG_ERROR_NO_INPUT;
                        OkFlg = false;
                    }

                    for (var j = i + 1; j < this.shelfList.length; j++) {
                        if (this.shelfList[i].del_flg == this.FLG_OFF && this.shelfList[j].del_flg == this.FLG_OFF && this.shelfList[i].shelf_area == this.shelfList[j].shelf_area
                            && this.shelfList[i].shelf_steps == this.shelfList[j].shelf_steps
                            && this.rmUndefinedBlank(this.shelfList[i].shelf_area) != '' 
                            && this.rmUndefinedBlank(this.shelfList[j].shelf_area) != '') {
                            // 重複チェック
                            this.shelfList[i].errors.shelf_area = MSG_ERROR_SAME_INPUT;
                            this.shelfList[j].errors.shelf_area = MSG_ERROR_SAME_INPUT;
                            this.shelfList[i].errors.shelf_steps = MSG_ERROR_SAME_INPUT;
                            this.shelfList[j].errors.shelf_steps = MSG_ERROR_SAME_INPUT;
                            OkFlg = false;
                        }
                    }
                }
            }

            if (!OkFlg) {
                this.loading = false;
            } else {
                // 入力値を取得
                var params = new URLSearchParams();
                // params.append('id', this.rmUndefinedBlank(this.shelfnumberlist[0].id));
                params.append('warehouse_id', this.rmUndefinedBlank(this.warehousedata.id));
                params.append('shelfList', JSON.stringify(this.shelfList));

                axios.post('/shelf-number-edit/save', params)

                .then( function (response) {
                    this.loading = false

                    if (response.data) {
                        // 成功
                        window.onbeforeunload = null;
                        var listUrl = '/warehouse-list' + window.location.search
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
            params.append('id', this.rmUndefinedBlank(this.warehousedata.id));

            axios.post('/shelf-number-edit/delete', params)

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
                params.append('screen', 'shelf-number-edit');
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
            params.append('screen', 'shelf-number-edit');
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
            params.append('screen', 'shelf-number-edit');
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
.input-body {
    min-height: 82px;
}
.btn-csv {
    margin-top: 45px;
}

</style>