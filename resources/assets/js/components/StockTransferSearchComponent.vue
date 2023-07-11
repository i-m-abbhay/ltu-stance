<template>
  <div class>
    <div class="col-md-7 text-right">
      <button type="submit" class="qr-button" @click="qrRead">
        <svg class="svg-icon">
          <use width="45.088" height="45.088" xlink:href="#qrIcon" />
        </svg>
      </button>
      <br />
    </div>

    <div v-show="qr_read" class="col-md-12">
      <p class="message">{{ message }}</p>
      <p class="decode-result">
        QRコード:
        <b>{{ result }}</b>
      </p>
      <qrcode-drop-zone @decode="onDecode">
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

    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="productData"
        @row-click="handleClick"
        :default-sort="{ prop: 'product_code', order: 'ascending' }"
        v-loading="loading"
        style="width: 100%"
        :row-class-name="tableRowClassName"
      >
        <el-table-column label="No." width="80px">
          <el-table-column label="単位" width="80px">
            <template slot-scope="scope">
              <div
                :class="backColor(scope.row.stock_flg)"
                style="width: 60px; text-align: center"
              >
                <font align="center" color="white">{{ scope.$index + 1 }}</font>
              </div>
              <br />
              {{ scope.row.unit }}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="商品番号" width="300">
          <el-table-column label="商品名/形式・規格" width="300">
            <el-table-column label="棚番" width="300">
              <template slot-scope="scope">
                {{ scope.row.product_code }}
                <br />
                {{ scope.row.product_name }} / {{ scope.row.model }}
                <br />
                {{ scope.row.shelf_area }}
              </template>
            </el-table-column>
          </el-table-column>
        </el-table-column>

        <el-table-column label="移管数量" width="100">
          <el-table-column label="配送数量" width="100">
            <template slot-scope="scope">
              {{ scope.row.quantity }}
              <br />
              {{ scope.row.delivery_quantity }}
              <br />

              <el-button
                type="text"
                v-show="scope.row.stock_flg == 1"
                @click="showNumberDialog(scope.$index)"
                >数量変更</el-button
              >
            </template>
          </el-table-column>
        </el-table-column>
      </el-table>
    </div>
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
          <label class="control-label" for="acDepartment">移管数量</label>
          <input
            type="text"
            class="form-control"
            v-model="quantity"
            v-bind:readonly="true"
          />
        </div>
      </div>
      <div class="row">
        <div class="col-md-3">
          <label class="control-label" for="acDepartment">変更数量</label>
          <div
            class="form-group"
            v-bind:class="{ 'has-error': errors.change_quantity != '' }"
          >
            <input
              type="text"
              class="form-control"
              v-model="change_quantity"
              v-bind:readonly="false"
            />
            <div class="text-danger">{{ errors.change_quantity }}</div>
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
        <el-button type="primary" @click="updateQuantity">実行</el-button>
      </span>
    </el-dialog>

    <!-- フッター -->
    <div class="col-md-12 text-center">
      <br />
      <a class="btn btn-primary" @click="back">戻る</a>
      <a class="btn btn-primary" @click="process">決定</a>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    // QR関連
    result: "",
    message: "",
    noStreamApiSupport: false,
    qr_read: false,
    paused: false,
    searchParams: {
      warehouse_id: 0,
    },
    to_date: "",
    from_date: "",
    tableCount: 0,
    numberDialog: false,
    tableData: [],
    productData: [],
    stockFlg: {
      itemsOrdered: 0, //発注品
      stock: 1, //在庫品
      custody: 2, //預かり品
    },
    qr_code: "",
    scanData: [],
    //数量変更ダイアログ
    product_name: "",
    quantity: 0,
    change_quantity: 0,
    current: 0,
    errors: {
      change_quantity: "",
    },
  }),

  props: {
    warehouselist: Array,
  },
  created: function () {
    // コンボボックスの先頭に空行追加
    this.warehouselist.splice(0, 0, "");
    //this.search();
  },
  mounted: function () {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.to_date = JSON.parse(localStorage.getItem("to_date")) || [];
    this.from_date = JSON.parse(localStorage.getItem("from_date")) || [];

    this.loading = true;

    var from_warehouse_id = [];
    var to_warehouse_id = [];

    for (var i = 0; i < this.tableData.length; i++) {
      from_warehouse_id.push(this.tableData[i].from_warehouse_id);
      to_warehouse_id.push(this.tableData[i].to_warehouse_id);
    }
    var params = {
      from_warehouse_id: from_warehouse_id,
      to_warehouse_id: to_warehouse_id,
      from_date: this.from_date,
      to_date: this.to_date,
    };
    axios
      .post("/stock-transfer-search/search", params)

      .then(
        function (response) {
          if (response.data) {
            this.productData = response.data;
          }

          this.loading = false;
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
    // 数量変更
    showNumberDialog($index) {
      this.current = $index;
      this.product_name = this.productData[$index].product_name;
      this.quantity = this.productData[$index].quantity;
      this.change_quantity = this.productData[$index].delivery_quantity;
      this.numberDialog = true;
    },
    // 数量更新
    updateQuantity() {
      this.numberDialog = false;
      this.productData[this.current].delivery_quantity = this.change_quantity;
    },
    // マイナス
    minus() {
      if (this.change_quantity == 0) {
        return;
      }
      this.change_quantity--;
    },
    // プラス
    plus() {
      this.change_quantity++;
    },
    // 最大
    max() {
      this.change_quantity = this.quantity;
    },
    onDecode(result) {
      this.result = result;
      //this.loading = true;
      var params = new URLSearchParams();
      params.append("qr_code", this.result);
      axios
        .post("/qr-split/search", params)

        .then(
          function (response) {
            //this.loading = false;

            if (response.data) {
              var qrInfo = response.data;

              // for (var i = 0; i < qrInfo.length; i++) {
              //   var isDuplication = false; //QR重複読込フラグ

              //エラーチェック
              if (qrInfo.length <= 0) {
                alert("スキャンしたQRの情報が存在しません。");
                return;
              } else {
                for (var i = 0; i < this.scanData.length; i++) {
                  if (qrInfo[0].qr_code == this.scanData[i].qr_code) {
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
              for (let i = 0; i < this.productData.length; i++) {
                tableClone.push(Object.assign({}, this.productData[i]));
                productArray.push(this.productData[i].product_id);
              }

              for (var j = 0; j < qrInfo.length; j++) {
                //読み込んだ商品が存在しない場合エラー
                if (productArray.indexOf(qrInfo[j].product_id) == -1) {
                  alert("納品予定に含まれていない商品があるのでQRは読み込めません");
                  return;
                }

                // 行検索
                for (var i = 0; i < tableClone.length; i++) {
                  if (tableClone[i].quantity == tableClone[i].delivery_quantity) {
                    continue;
                  }

                  //QRの商品、案件、得意先が一致した場合
                  if (
                    ((tableClone[i].stock_flg == 0 &&
                      tableClone[i].product_id == qrInfo[j].product_id &&
                      tableClone[i].matter_id == qrInfo[j].matter_id &&
                      tableClone[i].customer_id == qrInfo[j].customer_id) ||
                      (tableClone[i].stock_flg == 2 &&
                        tableClone[i].product_id == qrInfo[j].product_id &&
                        tableClone[i].customer_id == qrInfo[j].customer_id)) &&
                    Number(tableClone[i].quantity) ==
                      // Number(tableClone[i].delivery_quantity) +
                        Number(qrInfo[j].quantity)
                  ) {
                    //配送数量を加算
                    tableClone[i].delivery_quantity =
                      Number(tableClone[i].delivery_quantity) +
                      Number(qrInfo[j].quantity);

                    if (tableClone[i].delivery_quantity == tableClone[i].quantity) {
                      tableClone[i].isReaded = true;
                    }

                    // //スキャンしたQR情報をテーブルに保持
                    // if (tableClone[i].qrData == null) {
                    //   tableClone[i].qrData = [];
                    //   tableClone[i].qrData.push(qrInfo[j]);
                    // } else {
                    //   tableClone[i].qrData.push(qrInfo[j]);
                    // }
                    isQrError = false;
                    errMsg = "";
                    qrCount += 1;

                    break;
                  } else {
                    isQrError = true;
                    errMsg = "セットできない商品があるのでQRは読み込めません";
                  }
                }
                if (isQrError) {
                  alert(errMsg);
                  return;
                }
              }

              if (isQrError) {
                alert(errMsg);
              } else {
                //スキャン実施
                for (var j = 0; j < qrInfo.length; j++) {
                  // 行検索
                  for (var i = 0; i < this.productData.length; i++) {
                    if (
                      this.productData[i].quantity ==
                      this.productData[i].delivery_quantity
                    ) {
                      continue;
                    }

                    //QRの商品、案件、得意先が一致した場合
                    if (
                      ((this.productData[i].stock_flg == 0 &&
                        this.productData[i].product_id == qrInfo[j].product_id &&
                        this.productData[i].matter_id == qrInfo[j].matter_id &&
                        this.productData[i].customer_id == qrInfo[j].customer_id) ||
                        (this.productData[i].stock_flg == 2 &&
                          this.productData[i].product_id == qrInfo[j].product_id &&
                          this.productData[i].customer_id == qrInfo[j].customer_id)) &&
                      Number(this.productData[i].quantity) ==
                        // Number(this.productData[i].delivery_quantity) +
                          Number(qrInfo[j].quantity)
                    ) {
                      //配送数量を加算
                      this.productData[i].delivery_quantity =
                        Number(this.productData[i].delivery_quantity) +
                        Number(qrInfo[j].quantity);

                      if (this.productData[i].delivery_quantity == this.productData[i].quantity) {
                        this.productData[i].isReaded = true;
                      }

                      //スキャンしたQR情報をテーブルに保持
                      if (this.productData[i].qrData == null) {
                        this.productData[i].qrData = [];
                        this.productData[i].qrData.push(qrInfo[j]);
                      } else {
                        this.productData[i].qrData.push(qrInfo[j]);
                      }

                      //スキャン済みデータを保持
                      this.scanData.push({
                        qr_id: qrInfo[j].qr_id,
                        qr_code: qrInfo[j].qr_code,
                      });
                      break;
                    }
                  }
                }
              }

              // this.updateCount();
            }
          }.bind(this)
                //スキャン済みQRはスルー
          //       for (var j = 0; j < this.scanData.length; j++) {
          //         if (qrInfo[i].detail_id == this.scanData[j].detail_id) {
          //           isDuplication = true;
          //           break;
          //         }
          //       }

          //       if (isDuplication) {
          //         continue;
          //       }

          //       //QRで比較
          //       for (var j = 0; j < this.productData.length; j++) {
          //         if (
          //           this.productData[j].qr_code == qrInfo[i].qr_code &&
          //           this.productData[j].stock_flg != this.stockFlg["stock"]
          //         ) {
          //           //既にスキャンされている場合次の行へ
          //           if (
          //             this.productData[j].quantity ==
          //             this.productData[j].delivery_quantity
          //           ) {
          //             continue;
          //           }

          //           //実績数追加
          //           var v = this.productData[j].delivery_quantity + qrInfo[i].quantity;

          //           this.productData.splice(j, 1, {
          //             ...this.productData[j],
          //             delivery_quantity: v,
          //           });

          //           //スキャン済みQRリストに追加
          //           this.scanData.push({
          //             qr_code: qrInfo[i].qr_code,
          //           });

          //           break;
          //         }
          //       }
          //     }
          //   }
          // }.bind(this)
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
    // QR読取り
    qrRead() {
      if (this.qr_read) {
        this.qr_read = false;
      } else {
        this.qr_read = true;
        this.result = "";
      }
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    tableRowClassName({ row, rowIndex }) {
      if (row.quantity == row.delivery_quantity) {
        return "acceptance-row";
      } else {
        return "normal-row";
      }
    },
    backColor(stock_flg) {
      if (stock_flg == this.stockFlg["stock"]) {
        return "black";
      } else {
        return "red";
      }
    },
    handleClick(row) {
      if (!this.numberDialog) {
        // 在庫品はタップで全数ON/OFF
        if (
          row.delivery_quantity == 0 ||
          (row.delivery_quantity != 0 && window.confirm("選択を解除しますか？"))
        ) {
          if (row.stock_flg == this.stockFlg["stock"]) {
            if (row.delivery_quantity == 0) {
              row.delivery_quantity = row.quantity;
            } else {
              row.delivery_quantity = 0;
            }
          }
          //在庫品以外の場合はタップで全数OFF
          else {
            if (row.delivery_quantity != 0) {
              //同じQRの行は全てOFF
              for (var i = 0; i < this.productData.length; i++) {
                if (this.productData[i].qr_code == row.qr_code) {
                  this.productData.splice(i, 1, {
                    ...this.productData[i],
                    delivery_quantity: 0,
                  });
                }
              }
              //スキャン済みQR配列から対象QRを全て削除
              this.scanData = this.scanData.filter((d) => !(d.qr_code == row.qr_code));
            }
          }
        }
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

    process() {
      if (this.productData.filter((row) => row.delivery_quantity > 0).length > 0) {
        this.loading = true;
        localStorage.setItem(
          "productData",
          JSON.stringify(this.productData.filter((row) => row.delivery_quantity > 0))
        );
        var listUrl = "/stock-transfer-truck";
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },

    selectWarehouse: function (sender) {
      this.searchParams.warehouse_id =
        sender.selectedValue !== undefined && sender.selectedValue !== null
          ? sender.selectedValue
          : "";
      //this.search();
    },
    // 戻る
    back() {
      var listUrl = "/stock-transfer";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    handleClose(done) {
      done();
    },
  },
};
</script>

<style>
.svg-icon {
  background-color: #c03570 !important;
  color: #c03570;
  border-radius: 10px 10px 10px 10px;
  width: 45.088px;
  height: 45.088px;
}
/* QRボタンの枠線を消す */
.qr-button {
  background-color: transparent;
  border: none;
  cursor: pointer;
  outline: none;
  padding: 0;
  appearance: none;
  padding-top: 25px;
}
.el-table .normal-row {
  background: rgb(255, 255, 255);
}

.el-table .acceptance-row {
  background: rgb(245, 139, 209);
}
.red {
  background: rgb(197, 6, 64);
}

.black {
  background: rgb(0, 0, 0);
}
</style>
