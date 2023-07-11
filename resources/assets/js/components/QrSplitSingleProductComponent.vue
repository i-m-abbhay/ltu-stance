<template>
  <div>
    <loading-component :loading="loading" />
    <div class="search-form" id="searchForm">
      <div class="col-md-6">
        <label class="control-label" for="acDepartment">QR管理番号</label>
        <input
          type="text"
          class="form-control"
          v-model="qrInfo[0].qr_code"
          v-bind:readonly="true"
        />
        <label class="control-label" for="acDepartment">商品名</label>
        <input
          type="text"
          class="form-control"
          v-model="qrInfo[0].product_name"
          v-bind:readonly="true"
        />
        <label class="control-label" for="acDepartment">数量</label>
        <input
          type="text"
          class="form-control"
          v-model="qrInfo[0].quantity"
          v-bind:readonly="true"
        />
        <label class="control-label" for="acDepartment">倉庫</label>
        <input
          type="text"
          class="form-control"
          v-model="qrInfo[0].warehouse_name"
          v-bind:readonly="true"
        />
        <label class="control-label" for="acDepartment">棚</label>
        <input
          type="text"
          class="form-control"
          v-model="qrInfo[0].shelf_area"
          v-bind:readonly="true"
        />

        <br />
        <div class="col-sm-12">分割する数量を入力してください</div>

        <div
          v-for="(object, index) in this.splitList"
          v-bind:key="index"
          class="col-sm-12"
        >
          <label v-bind:for="object.index" class="inp">
            <input
              type="number"
              min="0"
              v-bind:key="object.index"
              class="form-control"
              placeholder="分割数量"
              v-model="object.quantity"
            />
            <br />
          </label>
        </div>

        <div class="col-sm-12 text-center">
          <button
            type="button"
            class="btn btn-primary"
            style="border-radius: 100%"
            @click="addForm"
          >
            ＋
          </button>
          <br />
          <br />
          <button
            type="button"
            class="btn btn-primary"
            style="width: 100%"
            @click="split"
          >
            分割
          </button>
        </div>
      </div>
      <br />
    </div>

    <!-- 確認画面 -->
    <el-dialog title="分割確認" :visible.sync="processDialog" width="80%">
      <div class="row">
        <div class="col-md-10">
          <div class="search-form" id="searchForm">
            <div class="col-md-6">
              <label class="control-label" for="acDepartment">QR管理番号</label>
              <input
                type="text"
                class="form-control"
                v-model="qrInfo[0].qr_code"
                v-bind:readonly="true"
              />
              <label class="control-label" for="acDepartment">商品名</label>
              <input
                type="text"
                class="form-control"
                v-model="qrInfo[0].product_name"
                v-bind:readonly="true"
              />
              <label class="control-label" for="acDepartment">数量</label>
              <input
                type="text"
                class="form-control"
                v-model="qrInfo[0].quantity"
                v-bind:readonly="true"
              />
              <label class="control-label" for="acDepartment">倉庫</label>
              <input
                type="text"
                class="form-control"
                v-model="qrInfo[0].warehouse_name"
                v-bind:readonly="true"
              />
              <label class="control-label" for="acDepartment">棚</label>
              <input
                type="text"
                class="form-control"
                v-model="qrInfo[0].shelf_area"
                v-bind:readonly="true"
              />
              <br />
              <div class="col-sm-12">以下で分割してよろしいですか？</div>

              <div
                v-for="(object, index) in activeSplitList"
                v-bind:key="index"
                class="col-sm-12"
              >
                <label v-bind:for="object.index" class="inp">
                  <input
                    type="text"
                    v-bind:key="object.index"
                    v-bind:readonly="true"
                    class="form-control"
                    v-model="object.quantity"
                    style="text-align: right"
                  />
                </label>
              </div>
            </div>
            <br />
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
    splitList: [{ quantity: "" }, { quantity: "" }],
    splitArray: [],
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
  computed: {
    activeSplitList: function () {
      return this.splitList.filter(function (object) {
        return !isNaN(object.quantity) && object.quantity != "";
      });
    },
  },
  created: function () {},
  mounted: function () {
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];

    this.search();
  },
  methods: {
    search() {
      this.loading = true
      var params = new URLSearchParams();

      params.append('qr_info', JSON.stringify(this.qrInfo));

      axios.post('/qr-split-single-product/search', params)
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
    logErrors(promise) {
      promise.catch(console.error);
    },
    addForm: function () {
      this.splitList.push({ quantity: "" });
    },

    split: function () {
      //数量と分割数量合計の差異をチェック
      this.splitArray = [];
      var sum = 0;
      var arr = this.splitList;
      var j = 0;
      var minusFlg = false;
      for (var i = 0, len = arr.length; i < len; ++i) {
        var val = arr[i].quantity;
        if (!isNaN(val) && val != "") {
          if (Number(arr[i].quantity) < 0) {
            minusFlg = true;
          }
          sum += Number(arr[i].quantity);
          this.splitArray[j] = Number(arr[i].quantity);
          j++;
        }
      }
      if (minusFlg) {
        alert("マイナス値は入力できません。");
      } else if (sum != this.qrInfo[0].quantity) {
        alert("分割数量の合計が元の数量と異なります");
      } else {
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
      var params = {
      detail_id: this.qrInfo[0].detail_id,
      qr_id: this.qrInfo[0].qr_id,
      product_id: this.qrInfo[0].product_id,
      matter_id: this.qrInfo[0].matter_id,
      customer_id: this.qrInfo[0].customer_id,
      arrival_id: this.qrInfo[0].arrival_id,
      warehouse_id: this.qrInfo[0].warehouse_id,
      shelf_number_id: this.qrInfo[0].shelf_number_id,
      splitArray: this.splitArray};
      axios
        .post("/qr-split-single-product/save", params)

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

      if (this.qrPrintCount < this.qrPrintList.length) {
        axios
          .post("/qr-split-single-product/print", {
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
                  //発行＆再印刷ダイアログ
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
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
</style>
