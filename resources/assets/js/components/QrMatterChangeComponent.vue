<template>
  <div>
    <div class="col-xs-12">
      <div v-for="(object,index) in this.qrInfo" v-bind:key="index" class="col-xs-12">
        <div class="col-xs-12 col-sm-8">
          <label class="control-label" for="acDepartment">QR管理番号</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].qr_code"
            v-bind:readonly="true"
          />

          <label class="control-label" for="acDepartment">商品名</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].product_name"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">数量</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].quantity"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">倉庫</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].warehouse_name"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">棚</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].shelf_area"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">案件番号</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].matter_id"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">案件名</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].matter_name"
            v-bind:readonly="true"
          />
        </div>
        <div class="col-xs-12">
          <br />
        </div>
      </div>
      <div id="app" v-show="read_count >= 1">
        <div class="col-xs-12 col-sm-8" v-bind:class="{'has-error': (errors.matter_no != '') }">
          <label>案件番号</label>
          <wj-auto-complete
            class="form-control"
            id="acMatterNo"
            search-member-path="matter_no"
            display-member-path="matter_no"
            selected-value-path="matter_no"
            :is-required="false"
            :items-source="matterList"
            :selected-value="matter_no"
            :selectedIndexChanged="selectMatterNo"
            :max-items="matterList.length"
          ></wj-auto-complete>
          <p class="text-danger">{{errors.matter_no}}</p>
        </div>
        <div class="col-xs-12 col-sm-8" v-bind:class="{'has-error': (errors.matter_name != '') }">
          <label>案件名</label>
          <wj-auto-complete
            class="form-control"
            id="acMatterName"
            search-member-path="matter_name"
            display-member-path="matter_name"
            selected-value-path="matter_name"
            :selected-index="-1"
            :is-required="false"
            :items-source="matterList"
            :selected-value="matter_name"
            :selectedIndexChanged="selectMatterName"
            :max-items="matterList.length"
          ></wj-auto-complete>
          <p class="text-danger">{{errors.matter_name}}</p>
        </div>

        <div class="col-xs-8 col-xs-offset-2">
          <br />
          <el-button type="primary" @click="showDialogMatter" style="width:100%;">案件を変換</el-button>
        </div>
        <div class="col-xs-8 col-xs-offset-2">
          <br />
          <el-button type="primary" @click="showDialogStock" style="width:100%;">在庫品に変換</el-button>
        </div>
        <div class="col-xs-12">
          <br />
        </div>
      </div>
    </div>

    <!-- スキャン -->
    <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="qrRead">
      <div id="app" v-show="qr_read">
        <div class="col-xs-12 text-center">案件変更するQRを</div>
        <div class="col-xs-12 text-center">スキャンしてください</div>

        <p class="message">{{ message }}</p>
        <p class="decode-result">
          QRコード:
          <b>{{ result }}</b>
        </p>
        <qrcode-drop-zone @decode="onDecode" @init="logErrors">
          <qrcode-stream @decode="onDecode" @init="onInit" />
        </qrcode-drop-zone>

        <qrcode-capture v-if="noStreamApiSupport" @decode="onDecode" />

        <div class="row">
          <div class="col-xs-6">
            <div class="form-group">
              <input type="tel" class="form-control" v-model="qr_code" />
            </div>
            <button type="button" class="btn btn-info" v-on:click="onDecode(qr_code)">QR手入力</button>
          </div>
        </div>
      </div>

      <br />
    </form>

    <!-- 確認画面 -->
    <el-dialog title="案件変更確認" :visible.sync="processDialog" width="80%" :before-close="handleClose">
      <div class="col-xs-12">
        <div v-for="(object,index) in this.qrInfo" v-bind:key="index" class="col-xs-12">
          <div class="col-xs-12 col-sm-8">
            <label class="control-label" for="acDepartment">QR管理番号</label>
            <input
              type="text"
              class="form-control"
              v-model="qrInfo[index].qr_code"
              v-bind:readonly="true"
            />

            <label class="control-label" for="acDepartment">商品名</label>
            <input
              type="text"
              class="form-control"
              v-model="qrInfo[index].product_name"
              v-bind:readonly="true"
            />
            <label class="control-label" for="acDepartment">数量</label>
            <input
              type="text"
              class="form-control"
              v-model="qrInfo[index].quantity"
              v-bind:readonly="true"
            />
            <label class="control-label" for="acDepartment">倉庫</label>
            <input
              type="text"
              class="form-control"
              v-model="qrInfo[index].warehouse_name"
              v-bind:readonly="true"
            />
            <label class="control-label" for="acDepartment">棚</label>
            <input
              type="text"
              class="form-control"
              v-model="qrInfo[index].shelf_area"
              v-bind:readonly="true"
            />
          </div>
          <div class="col-xs-12">
            <br />
          </div>
        </div>
        <div id="app" v-show="read_count >= 1 && isMatterChange">
          <div class="col-xs-12 col-sm-8">
            <label class="control-label" for="acDepartment">案件番号</label>
            <input type="text" class="form-control" v-model="matter_no" v-bind:readonly="true" />
          </div>
          <div class="col-xs-12 col-sm-8">
            <label class="control-label" for="acDepartment">案件名</label>
            <input type="text" class="form-control" v-model="matter_name" v-bind:readonly="true" />
          </div>
          <div class="col-xs-12">
            <br />
          </div>
        </div>
        <div class="col-xs-12 text-center" v-show="isMatterChange">案件を変換してよろしいですか？</div>
        <div class="col-xs-12 text-center" v-show="!isMatterChange">在庫品に変換してよろしいですか？</div>
        <div class="col-xs-12">
          <br />
        </div>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button @click="processDialog = false">キャンセル</el-button>
        <el-button type="primary" @click="process">確認</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from "vue-qrcode-reader";
export default {
  components: { QrcodeStream, QrcodeDropZone, QrcodeCapture },
  data: () => ({
    loading: false,
    result: "",
    message: "",
    noStreamApiSupport: false,
    qr_code: "",
    qr_read: true,
    qrInfo: [],
    processDialog: false,
    read_count: 0,
    matter_no: null,
    matter_name: null,
    isMatterChange: false,
    errors: {
      matter_no: "",
      matter_name: ""
    }
  }),
  props: {
    matterList: Array
  },
  created: function() {},
  mounted: function() {},
  methods: {
    showDialogMatter: function() {
      if (this.matter_no == null || this.matter_no == "") {
        this.$set(this.errors, "matter_no", MSG_ERROR_NO_INPUT);
        this.$set(this.errors, "matter_name", MSG_ERROR_NO_INPUT);
      } else {
        this.$set(this.errors, "matter_no", "");
        this.$set(this.errors, "matter_name", "");
        this.processDialog = true;
        this.isMatterChange = true;
      }
    },
    showDialogStock: function() {
      this.$set(this.errors, "matter_no", "");
      this.$set(this.errors, "matter_name", "");
      this.processDialog = true;
      this.isMatterChange = false;
    },
    // 実行
    process() {
      this.loading = true;
      this.processDialog = false;

      if (!this.isMatterChange) {
        this.matter_no = null;
      }

      axios
        .post("/qr-matter-change/save", {
          qrInfo: this.qrInfo,
          matterNo: this.matter_no
        })

        .then(
          function(response) {
            this.loading = false;

            if (response.data == "printError") {
              alert("印刷エラーが発生しました。");
              var url = "/qr-matter-change";
              window.onbeforeunload = null;
              location.href = url;
            } else if (response.data) {
              var url = "/qr-matter-change";
              window.onbeforeunload = null;
              location.href = url;

              url = response.data;
              var pattern = "smapri:";
              if (url.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = url;
              }
            } else {
              alert(MSG_ERROR);
            }
          }.bind(this)
        )
        .catch(
          function(error) {
            this.loading = false;

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
          }.bind(this)
        );
    },
    selectMatterNo: function(sender) {
       var item = sender.selectedItem;
      if (item !== null) {
        this.matter_name = item.matter_name;
        this.matter_no = item.matter_no;
      } else {
        this.matter_name = "";
        this.matter_no = "";
      }
    },
    selectMatterName: function(sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.matter_name = item.matter_name;
        this.matter_no = item.matter_no;
      } else {
        this.matter_name = "";
        this.matter_no = "";
      }
    },
    // QR読取り
    qrRead() {
      if (this.qr_read) {
        this.qr_read = false;
      } else {
        this.qr_read = true;
        this.result = "";
      }
    },
    async onInit(promise) {
      try {
        await promise;
      } catch (error) {
        if (error.name === "NotAllowedError") {
          this.error = "ERROR: you need to grant camera access permisson";
        } else if (error.name === "NotFoundError") {
          this.error = "ERROR: no camera on this device";
        } else if (error.name === "NotSupportedError") {
          this.error = "ERROR: secure context required (HTTPS, localhost)";
        } else if (error.name === "NotReadableError") {
          this.error = "ERROR: is the camera already in use?";
        } else if (error.name === "OverconstrainedError") {
          this.error = "ERROR: installed cameras are not suitable";
        } else if (error.name === "StreamApiNotSupportedError") {
          this.error = "ERROR: Stream API is not supported in this browser";
        }
      }
    },
     handleClose(done) {
      done();
    },
    //読取
    onDecode(result) {
      this.result = result;
      this.loading = true;
      var params = new URLSearchParams();
      params.append("qr_code", this.result);
      axios
        .post("/qr-split/search", params)

        .then(
          function(response) {
            this.loading = false;

            if (response.data) {
              var data = response.data;

              if (data.length <= 0) {
                alert("スキャンしたQRコードは存在しません。");
                return;
              }

              //統合可能なQRか判断
              var canIntegrated = true;
              var detail_id = data[0].detail_id;

              for (var i = 0, len = this.qrInfo.length; i < len; ++i) {
                if (this.qrInfo[i].detail_id == detail_id) {
                  canIntegrated = false;
                }
              }

              if (canIntegrated) {
                //表示する一覧に登録
                for (var i = 0, len = data.length; i < len; ++i) {
                  this.qrInfo.push(data[i]);
                }
                this.qr_read = false;
                this.read_count += 1;
              }
            }
          }.bind(this)
        )
        .catch(
          function(error) {
            this.loading = false;

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
          }.bind(this)
        );
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    logErrors(promise) {
      promise.catch(console.error);
    }
  }
};
</script>
<style>
.el-table .normal-row {
  background: rgb(255, 255, 255);
}

.el-table .loading-row {
  background: rgb(76, 217, 100);
}

.form-group {
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
</style>
