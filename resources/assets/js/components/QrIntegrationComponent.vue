<template>
  <div>
    <div class="col-xs-12">
      <div v-for="(object, index) in this.qrInfo" v-bind:key="index" class="col-xs-12">
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
    </div>

    <!-- スキャン -->
    <form
      id="searchForm"
      name="searchForm"
      class="form-horizontal"
      @submit.prevent="qrRead"
    >
      <div id="app" v-show="qr_read">
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
          <div class="col-md-6">
            <div class="form-group">
              <input type="tel" class="form-control" v-model="qr_code" />
            </div>
            <button type="button" class="btn btn-info" v-on:click="onDecode(qr_code)">
              QR手入力
            </button>
          </div>
        </div>
      </div>

      <br />
      <div class="col-xs-12 text-center">統合するQRを</div>
      <div class="col-xs-12 text-center">スキャンしてください</div>
      <div class="col-xs-8 col-xs-offset-2">
        <br />
        <button type="submit" class="btn btn-info" style="width: 100%">QRスキャン</button>
      </div>
      <div class="col-xs-2"></div>
      <div class="col-xs-12">
        <br />
      </div>

      <div id="app" v-show="read_count >= 2">
        <div class="col-xs-12 text-center">以上を統合してよろしいですか？</div>
        <div class="col-xs-8 col-xs-offset-2">
          <label class="control-label">印刷枚数</label>
          <input type="number" class="form-control" v-model="print_number">
        </div>
        <div class="col-xs-8 col-xs-offset-2">
          <br />
          <el-button type="primary" @click="process" style="width: 100%" :disabled="isClicked">確認</el-button>
        </div>
        <div class="col-xs-12">
          <br />
        </div>
      </div>
    </form>

    <!-- QR再発行ダイアログ -->
    <el-dialog title :visible.sync="rePrintDialog" width="80%">
      <div class="col-xs-12 text-center">
        <div>QRの統合が完了しました。</div>
      </div>
      <div class="row">
        <br />
      </div>
      <div class="row">
        <div class="col-xs-12 text-center">
          <el-button type="primary" @click="rePrintProc">　印刷　</el-button>
        </div>
      </div>
      <div class="row">
        <div class="col-xs-12 text-center">
          <br />
        </div>
      </div>
      <div class="row">
        <div class="col-xs-12 text-center">
          <el-button type="primary" @click="back">　完了　</el-button>
        </div>
      </div>
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
    qr_read: false,
    qrInfo: [],
    read_count: 0,
    print_number: 1,
    rePrintDialog: false,
    printedQrCode: null,
    isClicked:false,
  }),
  props: {},
  created: function () {},
  mounted: function () {},
  methods: {
    // 実行
    process() {
      if (this.rmUndefinedZero(this.print_number) <= 0) {
        alert('1以上の' + MSG_ERROR_NOT_NUMBER)
        return
      }

      var params = new URLSearchParams();
      params.append('qrInfo', JSON.stringify(this.qrInfo));
      params.append('print_number', this.print_number)

      this.loading = true;
      this.isClicked = true;
      this.processDialog = false;

      axios
        .post("/qr-integration/save", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data == "printError") {
              alert("印刷エラーが発生しました。");
              var url = "/qr-integration";
              window.onbeforeunload = null;
              location.href = url;
              this.isClicked = false;

            } else if (response.data) {
              var url = response.data.result;
              this.printedQrCode = response.data.qr_code;
              var pattern = "smapri:";
              if (typeof url == 'string' && url.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = url;
              }

              this.isClicked = false;

              //発行完了＆再印刷ダイアログ
              this.rePrintDialog = true;
            } else {
              alert(MSG_ERROR);
            }
          }.bind(this)
        )
        .catch(
          function (error) {
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
    //戻る
    back() {
      var url = "/qr-integration";
      window.onbeforeunload = null;
      location.href = url;
    },
    rePrintProc() {
      this.loading = true;
      this.printDialog = false;

      axios
        .post("/qr-integration/print", {
          qr_code: this.printedQrCode,
        })

        .then(
          function (response) {
            this.loading = false;
            if (response.data == "printError") {
              alert("印刷エラーが発生しました。");
            } else if (response.data) {
              var url = response.data;

              var pattern = "smapri:";
              if (url.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = url;
              }
            } else {
              // 失敗
              window.onbeforeunload = null;
              alert(MSG_ERROR);
              location.reload();
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            this.loading = false;
            if (error.response.data.errors) {
              // エラーメッセージ表示
              this.showErrMsg(error.response.data.errors, this.errors);
            } else {
              if (error.response.data.message) {
                alert(error.response.data.message);
              } else {
                alert(MSG_ERROR);
              }
              window.onbeforeunload = null;
              location.reload();
            }
          }.bind(this)
        );
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
          function (response) {
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
              var matter_id = data[0].matter_id;
              var customer_id = data[0].customer_id;
              var warehouse_id = data[0].warehouse_id;
              var shelf_number_id = data[0].shelf_number_id;
              for (var i = 1, len = data.length; i < len; ++i) {
                if (data[i].matter_id != matter_id) {
                  canIntegrated = false;
                }
                if (data[i].customer_id != customer_id) {
                  canIntegrated = false;
                }
                if (data[i].warehouse_id != warehouse_id) {
                  canIntegrated = false;
                }
                if (data[i].shelf_number_id != shelf_number_id) {
                  canIntegrated = false;
                }
              }
              for (var i = 0, len = this.qrInfo.length; i < len; ++i) {
                if (this.qrInfo[i].detail_id == detail_id) {
                  canIntegrated = false;
                }
                if (this.qrInfo[i].matter_id != matter_id) {
                  canIntegrated = false;
                }
                if (this.qrInfo[i].customer_id != customer_id) {
                  canIntegrated = false;
                }
                if (this.qrInfo[i].warehouse_id != warehouse_id) {
                  canIntegrated = false;
                }
                if (this.qrInfo[i].shelf_number_id != shelf_number_id) {
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
              } else {
                alert("スキャンしたQRは統合できません。");
              }

              this.qr_code = "";
            }
          }.bind(this)
        )
        .catch(
          function (error) {
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
    },
  },
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
