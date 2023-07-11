<template>
  <div>
    <loading-component :loading="loading" />
    <div class="col-xs-12">
      <div class="col-xs-8">
        <div><label>倉庫名：</label>{{ warehouse_name }}</div>
        <div><label>棚名：</label>{{ shelf_area }}</div>
      </div>
      <div class="col-xs-4 text-right">
        <a class="btn btn-primary" @click="productAddition">商材追加</a>
      </div>

      <!-- 検索結果グリッド -->
      <div class="grid-form">
        <br />
        <el-table
          :data="qrInfo"
          @row-click="handleClick"
          :default-sort="{ prop: 'product_code', order: 'ascending' }"
          v-loading="loading"
          style="width: 100%"
          :row-class-name="tableRowClassName"
        >
          <el-table-column label="QR番号" width="150">
            <template slot-scope="scope">
              <div v-if="scope.row.qr_code != null">{{ scope.row.qr_code }}</div>
              <div v-if="scope.row.qr_code == null">在庫品</div>
            </template>
          </el-table-column>

          <el-table-column label="商品CD" width="100">
            <template slot-scope="scope">{{ scope.row.product_code }}</template>
          </el-table-column>

          <el-table-column label="商品名" width="150">
            <template slot-scope="scope">{{ scope.row.product_name }}</template>
          </el-table-column>

          <el-table-column label="在庫数量" width="100">
            <template slot-scope="scope">{{ scope.row.quantity }}</template>
          </el-table-column>

          <el-table-column label="確認数量" width="140">
            <template slot-scope="scope" v-if="scope.row.shelf_kind !== 3">
              <el-input
                v-model="scope.row.confirmed_quantity"
                size="small"
                controls-position="left"
              />
            </template>
          </el-table-column>

          <el-table-column label="" width="140">
            <template slot-scope="scope">
              <button
                v-if="scope.row.qr_code != null"
                type="button"
                class="btn btn-primary"
                @click="printQr(scope.row.qr_code)"
              >
                QR印刷
              </button>
            </template>
          </el-table-column>
        </el-table>
      </div>

      <div class="col-md-12 text-right">
        <br />
        <a class="btn btn-primary" @click="back">戻る</a>
        <a class="btn btn-primary" v-show="qrInfo.length > 0" @click="showDialog">登録</a>
      </div>
    </div>

    <!-- 確認画面 -->
    <el-dialog title="棚卸結果" :visible.sync="processDialog" width="80%">
      <div class="row">
        <div class="col-xs-12">
          <div class="col-xs-12" v-if="isAllConfirmed">
            登録を実行してもよろしいですか？
          </div>
          <div class="col-xs-12" v-if="!isAllConfirmed">
            数量確認していない商材があります。
          </div>
          <div class="col-xs-12" v-if="!isAllConfirmed">
            このまま実行した場合、登録されなかった商材は再度棚卸登録が必要になります。
          </div>
          <div class="col-xs-12" v-if="!isAllConfirmed">
            <br />
          </div>
          <div class="col-xs-12" v-if="!isAllConfirmed">
            そのまま実行してもよろしいですか？
          </div>
          <div class="col-xs-12">
            <br />
          </div>
          <el-button @click="processDialog = false">キャンセル</el-button>
          <el-button type="primary" v-bind:disabled="isSaved" @click="process">登録</el-button>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    isSaved: false,
    processDialog: false,
    isAllConfirmed: true,
    qrInfo: [],
    shelf_number_id: null,
    shelf_area: null,
    warehouse_id: null,
    warehouse_name: null,
    clickedPrint: false,
  }),
  props: {},
  created: function () {},
  mounted: function () {
    this.loading=true;
    this.shelf_number_id = JSON.parse(localStorage.getItem("shelf_number_id")) || [];
    this.shelf_area = JSON.parse(localStorage.getItem("shelf_area")) || [];
    this.warehouse_id = JSON.parse(localStorage.getItem("warehouse_id")) || [];
    this.warehouse_name = JSON.parse(localStorage.getItem("warehouse_name")) || [];
    var qrCode = JSON.parse(localStorage.getItem("qrCode")) || [];

    var params = new URLSearchParams();
    params.append("qr_code", qrCode);
    axios
      .post("/inventory/search", params)

      .then(
        function (response) {
          this.loading = false;

          if (response.data) {
            var result = response.data;
            this.qrInfo = result.list;
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
  methods: {
    //戻る
    back() {
      var listUrl = "/inventory";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // 実行
    process() {
      this.loading = true;
      this.isSaved = true;
      axios
        .post("/inventory-product-list/save", {
          qrInfo: this.qrInfo,
        })

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.processDialog = false;
              var listUrl = "/inventory";
              window.onbeforeunload = null;
              location.href = listUrl;
            } else {
              alert(MSG_ERROR);
              this.isSaved = false;
            }
          }.bind(this)
        )
        .catch(
          function (error) {
            this.loading = false;
            this.isSaved = false;

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
            return;
          }.bind(this)
        );
    },
    //QR印刷
    printQr(qr_code) {
      this.clickedPrint = true;
      axios
        .post("/inventory-product-list/print", {
          qr_code: qr_code,
        })
        .then(
          function (response) {
            this.loading = false;
            if (response.data == "printError") {
              alert("印刷エラーが発生しました。");
              this.clickedPrint = false;
            } else if (response.data) {
              var url = response.data;
              var pattern = "smapri:";
              if (url.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = url;
              }
              this.clickedPrint = false;
            } else {
              // 失敗
              this.clickedPrint = false;
              window.onbeforeunload = null;
              alert(MSG_ERROR);
              location.reload();
            }
          }.bind(this)
        )
        .catch(
          function (error) {
            this.loading = false;
            this.clickedPrint = false;

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
            return;
          }.bind(this)
        );
    },
    showDialog() {
      this.isAllConfirmed = true;
      var minusFlg = false;

      //確認数量にNULLがあったら確認メッセージを変える
      for (var i = 0; i < this.qrInfo.length; i++) {
        if (
          this.qrInfo[i].confirmed_quantity == null ||
          this.qrInfo[i].confirmed_quantity == ""
        ) {
          this.isAllConfirmed = false;
        }

        if (Number(this.qrInfo[i].confirmed_quantity) < 0) {
          minusFlg = true;
        }
      }

      //マイナス値は不可
      if (minusFlg) {
        alert("マイナス値は入力できません。");
      } else {
        this.processDialog = true;
      }
    },
    // 商材追加
    productAddition() {
      this.loading = true;
      this.processDialog = false;
      localStorage.setItem("qrInfo", JSON.stringify(this.qrInfo));
      localStorage.setItem("shelf_number_id", JSON.stringify(this.shelf_number_id));
      localStorage.setItem("shelf_area", JSON.stringify(this.shelf_area));
      localStorage.setItem("warehouse_id", JSON.stringify(this.warehouse_id));
      localStorage.setItem("warehouse_name", JSON.stringify(this.warehouse_name));
      var listUrl = "/inventory-product-addition";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    tableRowClassName({ row, rowIndex }) {
      if (row.confirmed_quantity != null && row.confirmed_quantity != "") {
        return "acceptance-row";
      } else {
        return "normal-row";
      }
    },
    handleClick(row) {
      if (!this.clickedPrint && row.shelf_kind !== 3) {
        //タップで全数ON/OFF
        row.confirmed_quantity = row.quantity;
      }
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

.el-table .normal-row {
  background: rgb(255, 255, 255);
}

.el-table .acceptance-row {
  background: rgb(245, 139, 209);
}

.form-group {
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
</style>
