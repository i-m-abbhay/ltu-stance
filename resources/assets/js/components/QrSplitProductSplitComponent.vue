<template>
  <div>
    <loading-component :loading="loading" />
    <div class="row" style="padding: 30px">
      <div class="col-sm-5">
        <label class="control-label" for="acDepartment">QR管理番号</label>
        <input
          type="text"
          class="form-control"
          v-model="qrInfo[0].qr_code"
          v-bind:readonly="true"
        />
      </div>
    </div>

    <div class="col-sm-8">
      <div v-for="(object, index) in this.qrInfo" v-bind:key="index" class="col-sm-12">
        <div class="form-group">
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
      </div>
    </div>

    <div class="row" style="padding: 30px">
      <div class="col-sm-12">商品単位に分割してよろしいですか？</div>
      <div class="col-sm-8">
        <el-button type="primary" @click="process" style="width: 100%; margin-top: 10px" :disabled="isClicked"
          >確認</el-button
        >
      </div>
    </div>

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
    processDialog: false,
    loading: false,
    qrPrintCount: 0,
    printDialog: false,
    rePrintDialog: false,
    rePrintFlg: false,
    qrPrintList: [],
    isClicked:false,
  }),
  props: {},
  computed: {},
  created: function () {},
  mounted: function () {
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];

    this.search();
  },
  methods: {
    logErrors(promise) {
      promise.catch(console.error);
    },
    addForm: function () {
      this.splitList.push({ quantity: "" });
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
    search() {
      this.loading = true
      var params = new URLSearchParams();

      params.append('qr_info', JSON.stringify(this.qrInfo));

      axios.post('/qr-split-product-split/search', params)
      .then( function (response) {
          if (!response.data) {
            // QRが存在しない
            alert('スキャンしたQRコードは存在しません。')
            var url = "/qr-split";
            window.onbeforeunload = null;
            location.href = url;
          } else {
            this.loading = false
          }
      }.bind(this))
      .catch(function (error) {
          this.loading = false
          if (error.response.data.message) {
              alert(error.response.data.message)
          } else {
              alert(MSG_ERROR)
          }
          location.reload()
      }.bind(this))
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

      axios
        .post("/qr-split-product-split/save", this.qrInfo)

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

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
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

      if (this.qrPrintCount < this.qrPrintList.length) {
        axios
          .post("/qr-split-product-split/print", {
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
                  this.rePrintDialog=true;
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
