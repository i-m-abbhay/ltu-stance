<template>
  <div class="row">
    <div class="col-xs-12 text-center">
      <div class="col-xs-3"></div>
      <div class="col-xs-3">
        <p>数量</p>
      </div>
      <div class="col-xs-3">
        <p>QR1</p>
      </div>
      <div class="col-xs-3">
        <p>QR2</p>
      </div>
      <div v-for="(object, index) in this.qrInfo" v-bind:key="index" class="col-xs-12">
        <br />
        <div class="col-xs-3 text-left">{{ qrInfo[index].product_name }}</div>
        <div class="col-xs-3">{{ qrInfo[index].quantity }}</div>
        <div class="col-xs-3" v-bind:class="{ 'has-error': errors[index] != '' }">
          <input
            type="text"
            v-bind:key="object.index"
            class="form-control"
            placeholder="分割数量"
            v-model="splitList[index].quantity"
            style="margin-top: -8px"
          />
          <p class="text-danger">{{ errors[index] }}</p>
        </div>
        <div class="col-xs-3">
          {{ qrInfo[index].quantity - splitList[index].quantity }}
        </div>
      </div>
      <div class="col-xs-4 col-xs-offset-4">
        <br />
        <el-button type="primary" @click="split" style="width: 100%; margin-top: 10px" :disabled="isClicked"
          >分割</el-button
        >
      </div>
    </div>

    <!-- 確認画面 -->
    <el-dialog title="分割確認" :visible.sync="processDialog" width="80%">
      <div class="row">
        <div class="col-xs-12">以下で分割してよろしいですか？</div>
        <div class="col-xs-12">
          <br />
        </div>
        <div class="col-xs-12 text-center">
          <div class="col-xs-4"></div>
          <div class="col-xs-4">
            <p>QR1</p>
          </div>
          <div class="col-xs-4">
            <p>QR2</p>
          </div>
          <div
            v-for="(object, index) in this.qrInfo"
            v-bind:key="index"
            class="col-xs-12"
          >
            <br />
            <div class="col-xs-4 text-left">{{ qrInfo[index].product_name }}</div>

            <div class="col-xs-4">{{ splitList[index].quantity }}</div>
            <div class="col-xs-4">
              {{ qrInfo[index].quantity - splitList[index].quantity }}
            </div>
          </div>
        </div>
      </div>

      <span slot="footer" class="dialog-footer">
        <el-button @click="processDialog = false">キャンセル</el-button>
        <el-button type="primary" @click="process" :disabled="isClicked">確認</el-button>
      </span>
    </el-dialog>

    <!-- QR印刷ダイアログ -->
    <el-dialog title :visible.sync="printDialog" width="80%">
      <div class="col-xs-12 text-center">
        <div>QR発行確認後に完了ボタンを押してください。</div>
      </div>
      <div class="row">
        <br />
      </div>
      <div class="row">
        <div class="col-xs-12 text-center">
          <el-button type="primary" @click="rePrintProc">完了</el-button>
        </div>
      </div>
    </el-dialog>
    <!-- QR再発行ダイアログ -->
    <el-dialog title :visible.sync="rePrintDialog" width="80%">
      <div class="col-xs-12 text-center">
        <div>QRの分割が完了しました。</div>
      </div>
      <div class="row">
        <br />
      </div>
      <div class="row">
        <div class="col-xs-12 text-center">
          <el-button type="primary" @click="rePrint">　印刷　</el-button>
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
export default {
  components: {},
  data: () => ({
    qrInfo: [],
    splitList: [],
    processDialog: false,
    loading: false,
    qrPrintCount: 0,
    printDialog: false,
    rePrintDialog: false,
    rePrintFlg: false,
    qrPrintList: [],
    splitCount: 2,
    errors: [],
    isClicked:false,

  }),
  props: {},
  computed: {},
  created: function () {},
  mounted: function () {
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];
    this.splitList = JSON.parse(localStorage.getItem("qrInfo")) || [];
    this.errors = new Array(this.qrInfo.length).fill("");
  },
  methods: {
    logErrors(promise) {
      promise.catch(console.error);
    },
    //分割
    split: function () {
      var checkInput = true;
      for (let i in this.splitList) {
        var input = this.splitList[i].quantity;

        if (
          isNaN(input) ||
          input === "" ||
          input < 0 ||
          input > this.qrInfo[i].quantity
        ) {
          this.errors.splice(i, 1, "元の数量以下で0以上の数値を指定してください。");
          checkInput = false;
        } else {
          this.errors.splice(i, 1, "");
        }
      }

      if (checkInput) {
        this.processDialog = true;
      }
    },
    // 実行
    process() {
      this.qrPrintList = [];
      this.qrPrintCount = 0;
      this.processDialog = false;
      this.loading = true;
      this.isClicked = true;
      this.save();
    },
    //戻る
    back() {
      var url = "/qr-split";
      window.onbeforeunload = null;
      location.href = url;
    },

    // 実行
    save() {
      this.loading = true;
      this.processDialog = false;

      // パラメータ作成
      var params = { qrInfo: this.qrInfo, splitList: this.splitList };
      axios
        .post("/qr-split-quantity-designation/save", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.qrPrintList = response.data;
              this.rePrint();
            } else {
              alert(MSG_ERROR);
            }
          }.bind(this)
        )
        .catch(
          function (error) {
            this.loading = false;
            alert(MSG_ERROR);
            location.reload();
          }.bind(this)
        );
    },
    //再印刷用処理
    rePrint() {
      this.rePrintFlg = true;
      this.qrPrintCount = 0;
      this.processDialog = false;
      this.loading = true;
      this.rePrintProc();
    },
    rePrintProc() {
      this.loading = true;
      this.printDialog = false;

      if (this.qrPrintCount < this.splitCount) {
        axios
          .post("/qr-split-quantity-designation/print", {
            qr_code: this.qrPrintList[this.qrPrintCount],
          })

          .then(
            function (response) {
              this.loading = false;
              if (response.data == "printError") {
                alert("印刷エラーが発生しました。");
              } else if (response.data) {
                var url = response.data;

                var pattern = "smapri:";
                if (typeof url == 'string' && url.indexOf(pattern) > -1) {
                  // iosの場合
                  window.location.href = url;
                }

                this.qrPrintCount += 1;

                if (this.qrPrintCount < this.qrPrintList.length) {
                  if (
                    !alert(
                      "QR印刷完了後に表示される発行完了画面の完了を押すと、次のQRが印刷されます。"
                    )
                  ) {
                    this.printDialog = true;
                  }
                } else {
                  this.rePrintDialog = true;
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
      } else {
        this.loading = false;
      }
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
  padding: 20px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
</style>
