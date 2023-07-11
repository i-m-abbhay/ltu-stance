<template>
  <div>
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <form
        id="searchForm"
        name="searchForm"
        class="form-horizontal"
        @submit.prevent="qrRead"
      >
        <!-- 基本情報 -->
        <div class="row">
          <div class="col-md-6">
            <label class="control-label" for="acCustomer">案件名</label>
            <input
              type="text"
              class="form-control"
              v-model="baseInfo.matter_name"
              v-bind:readonly="true"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-2">
            <label class="control-label" for="acCustomer">商材数</label>
            <input
              type="text"
              class="form-control"
              v-model="baseInfo.item_count"
              v-bind:readonly="true"
            />
          </div>
          <div class="col-md-2">
            <label class="control-label" for="acDepartment">確認完了</label>
            <input
              type="text"
              class="form-control"
              v-model="baseInfo.complete_count"
              v-bind:readonly="true"
            />
          </div>
          <div class="col-md-2">
            <label class="control-label" for="acStaff">確認未了</label>
            <input
              type="text"
              class="form-control"
              v-model="baseInfo.incomplete_count"
              v-bind:readonly="true"
            />
          </div>
        </div>
        <div class="clearfix"></div>

        <div class="pull-right">
          <br />
          <button type="submit" class="btn btn-info">QR読取り</button>
        </div>
        <br />
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
        <div class="clearfix"></div>
      </form>
    </div>

    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="tableData"
        　　
        @row-click="handleClick"
        v-loading="loading"
        style="width: 100%"
        :row-class-name="tableRowClassName"
      >
        <el-table-column label="No." sortable width="100">
          <template slot-scope="scope">{{ scope.$index + 1 }}</template>
        </el-table-column>
        <el-table-column
          prop="product_code"
          label="商品番号"
          sortable
          width="150"
        ></el-table-column>
        <el-table-column
          prop="product_name"
          label="商品名"
          sortable
          width="200"
        ></el-table-column>
        <el-table-column
          prop="model"
          label="型式・規格"
          sortable
          width="150"
        ></el-table-column>
        <el-table-column
          prop="loading_quantity"
          label="積込数量"
          sortable
          width="100"
        ></el-table-column>
        <el-table-column
          prop="delivery_quantity"
          label="納品数量"
          sortable
          width="100"
        ></el-table-column>
        <!-- <el-table-column prop="qr_code" label="QRコード" sortable width="150"></el-table-column> -->
        <el-table-column label="数量変更" width="100">
          <template slot-scope="scope">
            <el-button
              type="text"
              v-show="(tableData[scope.$index].delivery_quantity != 0 && tableData[scope.$index].isReaded)"
              @click="showNumberDialog(scope.$index)"
              >数量変更</el-button
            >
          </template>
        </el-table-column>
      </el-table>
      <!-- 数量変更ダイアログ -->
      <el-dialog
        title="数量変更"
        :visible.sync="numberDialog"
        width="80%"
        :before-close="handleClose"
      >
        <div class="row">
          <div class="col-md-10">
            <label class="control-label" for="acDepartment">商品名</label>
            <input
              type="text"
              class="form-control"
              v-model="product_name"
              v-bind:readonly="true"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <label class="control-label" for="acDepartment">積込数量</label>
            <input
              type="text"
              class="form-control"
              v-model="loading_quantity"
              v-bind:readonly="true"
            />
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <label class="control-label" for="acDepartment">納品数量</label>
            <div
              class="form-group"
              v-bind:class="{ 'has-error': errors.delivery_quantity != '' }"
            >
              <input
                type="text"
                class="form-control"
                v-model="delivery_quantity"
                v-bind:readonly="false"
              />
              <div class="text-danger">{{ errors.delivery_quantity }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <label class="control-label" for="acDepartment"></label>
            <br />
            <button type="button" class="btn btn-info" v-on:click="minus">－</button>
            <button type="button" class="btn btn-info" v-on:click="plus">＋</button>
            <button type="button" class="btn btn-info" v-on:click="max">全部</button>
          </div>
        </div>
        <span slot="footer" class="dialog-footer">
          <el-button @click="numberDialog = false">キャンセル</el-button>
          <el-button type="primary" @click="showRedeliveryDialog">再配送</el-button>
        </span>
      </el-dialog>
      <!-- 配送確認ダイアログ -->
      <el-dialog
        title="配送確認"
        :visible.sync="processDialog"
        width="80%"
        :before-close="handleClose"
      >
        <div class="row">
          <div class="col-md-10">
            QR読取りしていない商材があります。
            <br />このまま実行した場合、搭載されなかった商材は再度出荷指示が必要になります。
            <br />
            <br />そのまま実行してもよろしいですか？
            <br />
          </div>
        </div>
        <span slot="footer" class="dialog-footer">
          <el-button @click="processDialog = false">キャンセル</el-button>
          <el-button type="primary" @click="process">実行</el-button>
        </span>
      </el-dialog>
      <!-- 再配送ダイアログ -->
      <el-dialog
        title="再配送"
        :visible.sync="redeliveryDialog"
        width="80%"
        :before-close="handleClose"
      >
        <div class="row">
          <div class="col-md-10">
            再配送を実行した場合、搭載されなかった商材は再度出荷指示が必要になります。
            <br />
            <br />そのまま実行してもよろしいですか？
            <br />
          </div>
        </div>
        <span slot="footer" class="dialog-footer">
          <el-button @click="redeliveryDialog = false">キャンセル</el-button>
          <el-button type="primary" @click="updateQuantity">実行</el-button>
        </span>
      </el-dialog>
    </div>
    <div class="col-md-12 text-center">
      <br />
      <button type="button" class="btn btn-warning btn-back" v-on:click="back">
        戻る
      </button>
      <button type="button" class="btn btn-primary btn-save" v-on:click="process">
        実行
      </button>
    </div>
  </div>
</template>

<script>
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from "vue-qrcode-reader";
export default {
  components: { QrcodeStream, QrcodeDropZone, QrcodeCapture },
  data: () => ({
    loading: false,

    searchParams: {
      matter_no: "",
      shelf_area: "",
    },
    errors: {
      delivery_quantity: "",
    },
    baseInfo: {
      matter_name: "",
      item_count: 0,
      complete_count: 0,
      incomplete_count: 0,
    },
    tableData: [],
    urlparam: "",
    id: "",
    numberDialog: false,
    processDialog: false,
    redeliveryDialog: false,
    product_name: "",
    loading_quantity: 0,
    delivery_quantity: 0,
    current: 0,
    result: "",
    message: "",
    noStreamApiSupport: false,
    qr_read: false,
    detail_index: 0,
    qr_code: "",
    qrInfo: [],
    scanData: [],
  }),
  props: {},
  created: function () {},
  mounted: function () {
    var query = window.location.search;
    // 検索条件セット
    this.setSearchParams(query, this.searchParams);
    // 検索
    this.search();
  },
  methods: {
    search() {
      this.loading = true;

      var params = new URLSearchParams();
      params.append("matter_no", this.searchParams.matter_no);
      params.append("shelf_area", this.searchParams.shelf_area);
      axios
        .post("/delivery-process/search", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.tableData = response.data;
              // 基本情報設定
              this.baseInfo.matter_name = this.tableData[0].matter_name;
              this.baseInfo.item_count = this.tableData.length;
              this.baseInfo.complete_count = 0;
              this.baseInfo.incomplete_count = this.tableData.length;
              for (var i = 0; i < this.tableData.length; i++) {
                if (this.tableData[i].delivery_quantity == null) {
                  this.tableData[i].delivery_quantity = 0;
                  this.tableData[i].isReaded = false;
                }
              }
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
    // 数量変更
    showNumberDialog($index) {
      this.current = $index;
      this.product_name = this.tableData[this.current].product_name;
      this.loading_quantity = this.tableData[this.current].loading_quantity;
      this.delivery_quantity = this.tableData[this.current].delivery_quantity;
      this.numberDialog = true;
    },
    // 数量更新
    updateQuantity() {
      this.numberDialog = false;
      this.tableData[this.current].delivery_quantity = this.delivery_quantity;
      this.updateCount();
      this.redeliveryDialog = false;
    },
    // 再配送
    showRedeliveryDialog() {
      if (Number(this.loading_quantity) < Number(this.delivery_quantity)) {
        alert("積込数量以下の数を入力してください。");
      } else if(this.delivery_quantity <= 0) {
        alert("1以上の数を入力してください。");
      } else {
        this.numberDialog = false;
        this.redeliveryDialog = true;
      }
    },
    // 戻る
    back() {
      var listUrl = "/delivery-list";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    handleClose(done) {
      done();
      this.updateCount();
    },
    // マイナス
    minus() {
      if (this.delivery_quantity == 0) {
        return;
      }
      this.delivery_quantity--;
    },
    // プラス
    plus() {
      if (Number(this.delivery_quantity) < Number(this.loading_quantity)) {
        this.delivery_quantity++;
      }
    },
    // 最大
    max() {
      this.delivery_quantity = this.loading_quantity;
    },
    async onInit(promise) {
      try {
        await promise;
      } catch (error) {
        this.message = error;
        if (error.name === "StreamApiNotSupportedError") {
          this.noStreamApiSupport = true;
        }
      }
    },
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
              this.qrInfo = response.data;
              for (var j = 0; j < this.qrInfo.length; j++) {
                this.qrInfo[j].is_scan = false;
              }

              //エラーチェック
              if (this.qrInfo.length <= 0) {
                alert("スキャンしたQRの情報が存在しません。");
                return;
              } else {
                for (var i = 0; i < this.scanData.length; i++) {
                  if (this.qrInfo[0].qr_code == this.scanData[i].qr_code) {
                    alert("既にスキャン済みのQRです。");
                    return;
                  }
                }
              }

              //クローンテーブルでQRスキャン動作を行いスキャン可能かを判断
              var isQrError = false;
              var errMsg = "";
              var qrCount = 0;
              var tableClone = [];
              var productArray = [];
              for (let i = 0; i < this.tableData.length; i++) {
                tableClone.push(Object.assign({}, this.tableData[i]));
                productArray.push(this.tableData[i].product_id);
              }

              for (var j = 0; j < this.qrInfo.length; j++) {
                //読み込んだ商品が存在しない場合エラー
                if (productArray.indexOf(this.qrInfo[j].product_id) == -1) {
                  alert("納品予定に含まれていない商品があるのでQRは読み込めません");
                  return;
                }

                // 行検索
                for (var i = 0; i < tableClone.length; i++) {
                  if (tableClone[i].loading_quantity == tableClone[i].delivery_quantity) {
                    continue;
                  }

                  //QRの商品、案件、得意先が一致した場合
                  if (
                    ((tableClone[i].stock_flg == 0 &&
                      tableClone[i].product_id == this.qrInfo[j].product_id &&
                      tableClone[i].matter_id == this.qrInfo[j].matter_id &&
                      tableClone[i].customer_id == this.qrInfo[j].customer_id) ||
                      (tableClone[i].stock_flg == 2 &&
                        tableClone[i].product_id == this.qrInfo[j].product_id &&
                        tableClone[i].customer_id == this.qrInfo[j].customer_id)) &&
                    Number(tableClone[i].loading_quantity) >=
                      Number(tableClone[i].delivery_quantity) +
                        Number(this.qrInfo[j].quantity)
                  ) {
                    if (tableClone[i].to_warehouse_id != this.qrInfo[j].warehouse_id || tableClone[i].to_shelf_number_id != this.qrInfo[j].shelf_number_id) {
                      // 積み込まれたトラック/棚と違った場合
                      isQrError = true;
                      errMsg = "指定されたトラックの商材ではありません。";
                      continue;
                    }
                    this.qrInfo[j].is_scan = true;

                    //配送数量を加算
                    tableClone[i].delivery_quantity =
                      Number(tableClone[i].delivery_quantity) +
                      Number(this.qrInfo[j].quantity);

                    if (tableClone[i].delivery_quantity == tableClone[i].loading_quantity) {
                      tableClone[i].isReaded = true;
                    }

                    // //スキャンしたQR情報をテーブルに保持
                    // if (tableClone[i].qrData == null) {
                    //   tableClone[i].qrData = [];
                    //   tableClone[i].qrData.push(this.qrInfo[j]);
                    // } else {
                    //   tableClone[i].qrData.push(this.qrInfo[j]);
                    // }
                    isQrError = false;
                    errMsg = "";
                    qrCount += 1;

                    break;
                  } else {
                    isQrError = true;
                    errMsg = "納品予定にセットできない商品があるのでQRは読み込めません";
                  }
                }
                if (isQrError) {
                  alert(errMsg);
                  return;
                }
              }
              for (var j = 0; j < this.qrInfo.length; j++) {
                if (!this.qrInfo[j].is_scan) {
                  // 読み込まれていない商品が存在する
                  alert("納品予定にセットできない商品があるのでQRは読み込めません")
                  return;
                }
              }

              if (isQrError) {
                alert(errMsg);
              } else {
                //スキャン実施
                for (var j = 0; j < this.qrInfo.length; j++) {
                  // 行検索
                  for (var i = 0; i < this.tableData.length; i++) {
                    if (
                      this.tableData[i].loading_quantity ==
                      this.tableData[i].delivery_quantity
                    ) {
                      continue;
                    }

                    //QRの商品、案件、得意先が一致した場合
                    if (
                      ((this.tableData[i].stock_flg == 0 &&
                        this.tableData[i].product_id == this.qrInfo[j].product_id &&
                        this.tableData[i].matter_id == this.qrInfo[j].matter_id &&
                        this.tableData[i].customer_id == this.qrInfo[j].customer_id) ||
                        (this.tableData[i].stock_flg == 2 &&
                          this.tableData[i].product_id == this.qrInfo[j].product_id &&
                          this.tableData[i].customer_id == this.qrInfo[j].customer_id)) &&
                      Number(this.tableData[i].loading_quantity) >=
                        Number(this.tableData[i].delivery_quantity) +
                          Number(this.qrInfo[j].quantity)
                    ) {
                      //配送数量を加算
                      this.tableData[i].delivery_quantity =
                        Number(this.tableData[i].delivery_quantity) +
                        Number(this.qrInfo[j].quantity);

                      if (this.tableData[i].delivery_quantity == this.tableData[i].loading_quantity) {
                        this.tableData[i].isReaded = true;
                      }

                      //スキャンしたQR情報をテーブルに保持
                      if (this.tableData[i].qrData == null) {
                        this.tableData[i].qrData = [];
                        this.tableData[i].qrData.push(this.qrInfo[j]);
                      } else {
                        this.tableData[i].qrData.push(this.qrInfo[j]);
                      }

                      //スキャン済みデータを保持
                      this.scanData.push({
                        qr_id: this.qrInfo[j].qr_id,
                        qr_code: this.qrInfo[j].qr_code,
                      });
                      break;
                    }
                  }
                }
              }

              this.updateCount();
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
    tableRowClassName({ row, rowIndex }) {
      // if (row.delivery_quantity != 0) {
      //   return "loading-row";
      // } else {
      //   return "normal-row";
      // }
      if (
        row.isReaded || 
        row.delivery_quantity === row.loading_quantity ||
        (row.stock_flg === 1 && row.delivery_quantity != 0)
      ) {
        return "loading-row";
      } else if (row.delivery_quantity == 0) {
        return "normal-row";
      } else if (row.stock_flg != 1) {
        return "error-row";
      }
    },
    handleClick(row) {
      //数量変更押下時は未処理
      if (!this.numberDialog) {
        if (
          row.delivery_quantity == 0 ||
          (row.delivery_quantity != 0 && window.confirm("選択を解除しますか？"))
        ) {
          // 在庫品の場合
          if (row.stock_flg == 1) {
            if (row.delivery_quantity == 0) {
              row.delivery_quantity = row.loading_quantity;
              row.isReaded = true;
            } else {
              row.delivery_quantity = 0;
              row.isReaded = false;
            }
          }
          //QRの場合
          else {
            //スキャン済みQR配列から対象QRを全て削除
            if (row.delivery_quantity != 0) {
              //QRがスキャンされている場合
              if (row.qrData != null) {
                //スキャンされたQRの繰り返し
                var qrCount = row.qrData.length;
                for (var i = qrCount - 1; i >= 0; i--) {
                  //タップした行のQRコード、数量を取得
                  if (qrCode == row.qrData[i].qr_code) {
                    continue;
                  }
                  var qrCode = row.qrData[i].qr_code;
                  //スキャン済みリストからQRを削除
                  if (this.scanData.length > 0 && row.qrData != null) {
                    this.scanData = this.scanData.filter(
                      (d) => !(d.qr_code == row.qrData[i].qr_code)
                    );
                  }

                  //タップされた行以外も同一QRでスキャンされている可能性があるため削除
                  for (var j = 0; j < this.tableData.length; j++) {
                    if (this.tableData[j].qrData != null) {
                      for (var k = 0; k < this.tableData[j].qrData.length; k++) {
                        if (qrCode == this.tableData[j].qrData[k].qr_code) {
                          // this.tableData[j].delivery_quantity z= this.tableData[j].qrData[
                          //   k
                          // ].quantity;
                          this.tableData[j].delivery_quantity = 0;

                          this.tableData[j].isReaded = false;
                        }
                      }
                      this.tableData[j].qrData = this.tableData[j].qrData.filter(
                        (d) => !(d.qr_code == qrCode)
                      );
                      if (this.tableData[j].qrData.length <= 0) {
                        this.tableData[j].qrData = null;
                      }
                    }
                  }
                }
              }
            }
          }
        }
        this.updateCount();
      }
    },
    // QR読取り
    qrRead() {
      if (this.qr_read) {
        this.qr_read = false;
      } else {
        this.qr_read = true;
      }
    },
    // 実行
    process() {
      // エラーの初期化
      this.initErr(this.errors);
      this.loading = true;
      this.processDialog = false;

      // 0件の場合
      if (this.tableData.length == 0 || this.baseInfo.complete_count <= 0) {
        this.loading = false;
        return;
      }

      var isError = false;
      var isDeliveryZero = false;
      this.tableData.forEach(element => {
        if (element.delivery_quantity != 0 && (element.loading_quantity > element.delivery_quantity && !element.isReaded)) {
          isError = true;
        }
        if (element.delivery_quantity == 0) {
          isDeliveryZero = true;
        }
      });
      if (isError) {          
        this.loading = false;
        alert('数量が足りない商品があります。');
        return;
      }
      if (isDeliveryZero && !confirm('納品されなかった商材は再度出荷指示が必要になります。\nよろしいですか？')){
        this.loading = false;
        return;
      }

      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      localStorage.setItem("scanData", JSON.stringify(this.scanData));
      var listUrl = "/delivery-photo";
      window.onbeforeunload = null;
      location.href = listUrl;

      // axios
      //   .post("/delivery-process/save", {
      //     tableData: this.tableData,
      //     scanData: this.scanData
      //   })

      //   .then(
      //     function(response) {
      //       if (response.data) {
      //         this.loading = false;
      //         localStorage.setItem("tableData", JSON.stringify(this.tableData));
      //         var listUrl = "/delivery-photo";
      //         window.onbeforeunload = null;
      //         location.href = listUrl;
      //       } else {
      //         // 失敗
      //         this.loading = false;
      //         window.onbeforeunload = null;
      //         alert(MSG_ERROR);
      //         location.reload();
      //       }
      //     }.bind(this)
      //   )

      //   .catch(
      //     function(error) {
      //       this.loading = false;
      //       if (error.response.data.errors) {
      //         // エラーメッセージ表示
      //         this.showErrMsg(error.response.data.errors, this.errors);
      //       } else {
      //         if (error.response.data.message) {
      //           alert(error.response.data.message);
      //         } else {
      //           alert(MSG_ERROR);
      //         }
      //         window.onbeforeunload = null;
      //         location.reload();
      //       }
      //     }.bind(this)
      //   );
    },
    updateCount() {
      this.baseInfo.complete_count = 0;
      for (var i = 0; i < this.tableData.length; i++) {
        if (this.tableData[i].delivery_quantity != 0) {
          this.baseInfo.complete_count++;
        }
      }
      this.baseInfo.incomplete_count =
        this.baseInfo.item_count - this.baseInfo.complete_count;
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
.el-table .error-row {
  background: rgb(255, 158, 46);
}

.form-group {
  padding-left: 0px;
  padding-right: 10px;
  padding-top: 0px;
  display: inline-block;
}
</style>
