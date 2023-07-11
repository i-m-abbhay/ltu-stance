<template>
    <div>
        <loading-component :loading="loading" />
        <div class="col-md-12 main-body" @dragleave.prevent @dragover.prevent @drop.prevent="selectFile($event)">
            <div class="row">
                <div class="col-md-12">
                    <label class="col-md-2 col-sm-12 col-xs-12">フォーマット</label>
                    <div class="col-md-12 col-sm-12 col-xs-12" v-bind:class="{'has-error': (errors.format_kbn != '') }">
                        <el-radio-group v-model="inputData.format_kbn" v-bind:disabled="isReadOnly">
                            <div class="radio">
                                <el-radio :label="PRODUCT_FORMAT">商品マスタフォーマット</el-radio>
                                <el-radio :label="QUOTE_FORMAT">Ｂｅｅ－Ｃｏｎｎｅｃｔ共通フォーマット</el-radio>
                            </div>
                            <p class="text-danger">{{ errors.format_kbn }}</p>
                        </el-radio-group>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-12"> 
                    <label class="col-md-2 col-sm-12 col-xs-12">CSV</label>
                    <div class="col-md-12">
                        <label class="file_label col-md-4 col-sm-6">
                            <input type="file" id="file" class="file-upload-btn" @change="selectFile($event)" accept=".csv" v-bind:disabled="isReadOnly">
                            <span for="file">{{ inputData.file_label }}</span>
                        </label>
                        <!-- <el-button type="danger" icon="el-icon-delete" circle size="mini" v-show="item.file != ''" @click="deleteFile(iCnt)" v-bind:disabled="isConf"></el-button>
                        <el-button type="success" icon="el-icon-download" circle size="mini" v-show="item.file_name != ''" @click="downloadFile(item.file_name)" v-bind:disabled="isConf"></el-button> -->
                    </div>
                    <p class="col-md-12 text-danger">{{ errors.file }}</p>
                </div>
            </div>
            
            <div class="row">
                <div class="col-md-12 col-sm-12 col-xs-12 text-center">
                    <button type="button" class="btn btn-save" @click="save" v-bind:disabled="isReadOnly">取込</button>
                </div>
            </div>

            <div class="row">
                <div class="col-md-12">
                    <h4 class="control-label">取込結果</h4>
                </div>
            </div>
            <div class="result-body">
                <div class="row">
                    <div class="col-md-12">
                        <label class="col-md-4 col-sm-4 col-xs-12">対象：{{ resultData.csvCount }}件</label>
                    </div>  
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label class="col-md-2 col-sm-4 col-xs-12">新規：{{ resultData.addCount }}件</label>
                        <label class="col-md-2 col-sm-4 col-xs-12">更新：{{ resultData.updateCount }}件</label>
                        <label class="col-md-2 col-sm-4 col-xs-12">エラー：{{ resultData.errCount }}件</label>
                    </div>  
                </div>
                <div class="row">
                    <div class="col-md-12">
                        <label class="col-md-12 col-sm-12 col-xs-12">エラー内容：</label>
                        <!-- <label class="col-md-12 col-sm-12 col-xs-12">{{ resultData.errMsg }}</label> -->
                        <label id="errMsg" class="col-md-12 col-sm-12 col-xs-12"></label>
                    </div>  
                </div>
                
            </div>
        </div>
    </div>
</template>


<script>
export default {
    data: () => ({
        loading: false,
        isReadOnly: false,

        PRODUCT_FORMAT: 0,
        QUOTE_FORMAT: 1,

        inputData: {
            format_kbn: 0,
            file_name: '',
            file_label: LBL_FILE,
            file: '',
        },

        resultData: {
            csvCount: 0,
            addCount: 0,
            updateCount: 0,
            errCount: 0,
            errMsg: "",
        },

        errors: {
            file: '',

        },



    }),
    props: {


    },
    created: function() {


    },
    mounted: function() {
        var err = document.getElementById('errMsg');

        // err.innerHTML = "3件目：product_code：必須です<br>10件目：model：形式が違います"


    },
    methods:{
        // 添付ファイル情報の取得
        selectFile(event) {
            let fileList = event.target.files ? 
                               event.target.files:
                               event.dataTransfer.files;
            let files = [];

            this.errors.file = '';

            for(let i = 0; i < fileList.length; i++){
                if (fileList[i].name.indexOf('.csv') > -1) {
                    // CSV形式の場合
                    this.inputData.file = fileList[i]
                    this.inputData.file_name = ''
                    this.inputData.file_label = fileList[i].name
                } else {
                    // その他(error)
                    this.errors.file = MSG_ERROR_ILLEGAL_FILE_EXTENSION;
                }
            }            
        },
        save() {
            if (!confirm(MSG_CONFIRM_PRODUCT_CSV_IMPORT)) {
                return;
            }
            this.loading = true;
            this.initErr(this.errors);

            var params = new FormData();

            params.append('format_kbn', this.inputData.format_kbn);
            params.append('file', this.inputData.file);

            axios.post('/product-import/import', params)
            .then( function (response) {

                if (response.data.result) {
                    // エラーメッセージ
                    var err = document.getElementById('errMsg');
                    err.innerHTML = response.data.errorMessage;
                    err.style.color = 'red';

                    this.resultData.csvCount = response.data.cnt.target;
                    this.resultData.addCount = response.data.cnt.add;
                    this.resultData.updateCount = response.data.cnt.update;
                    this.resultData.errCount = response.data.cnt.error;

                    alert(MSG_SUCCESS_PRODUCT_CSV_IMPORT);
                } else {
                    // 失敗
                    var err = document.getElementById('errMsg');
                    err.innerHTML = response.data.errorMessage;
                    err.style.color = 'red';
                    // alert(MSG_ERROR)
                    // location.reload();
                }
                this.loading = false
            }.bind(this))
            .catch(function (error) {
                if (error.response.data.errors) {
                    this.loading = false
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
            }.bind(this))
        },


    }
}
</script>

<style>
.main-body {
    width: 100%;
    background: #ffffff;
    padding: 15px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.result-body {
    width: 100%;
    background: #ffffff;
    margin-top: 10px;
    padding: 15px;
    -webkit-box-shadow: 0 0 2px 2px rgba(0, 0, 0, .3);
    box-shadow: 0 0 2px 2px rgba(0, 0, 0, .3);
}
</style>


