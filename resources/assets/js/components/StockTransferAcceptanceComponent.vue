<template>
  <div class>
    <div class="row">
      <div class="col-md-3">
        <label class="control-label">倉庫</label>
        <wj-auto-complete
          class="form-control"
          id="acWarehouse"
          search-member-path="id"
          display-member-path="warehouse_name"
          :items-source="warehouselist"
          selected-value-path="id"
          :selected-value="searchParams.warehouse_id"
          :max-length="warehouselist.length"
          :lost-focus="selectWarehouse"
        ></wj-auto-complete>
      </div>
    </div>

    <div class="row">
      <div class="col-md-3">
        <label class="control-label">&nbsp;</label>
        <label class="control-label">区分</label>
        <div>
            <el-radio-group v-model="searchParams.move_kind">
              <el-radio v-for="kbn in moveKindList" :key="kbn.value" :label="kbn.value">{{ kbn.text }}</el-radio>
            </el-radio-group>
        </div>
      </div>

      <div class="col-md-2 text-right">
        <button
          type="submit"
          class="btn btn-primary btn-search"
          @click="search"
          style="margin-top: 30px"
        >
          検索
        </button>
      </div>

      <div class="col-md-7 text-right">
        <button type="submit" class="qr-button" @click="qrRead">
          <svg class="svg-icon">
            <use width="45.088" height="45.088" xlink:href="#qrIcon" />
          </svg>
        </button>
        <br />
      </div>
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
        :data="tableData"
        @row-click="handleClick"
        :default-sort="{ prop: 'product_code', order: 'ascending' }"
        v-loading="loading"
        style="width: 100%"
        :row-class-name="tableRowClassName"
      >
        <el-table-column label="No." width="80">
          <template slot-scope="scope">
            <div
              :class="backColor(scope.row)"
              style="width: 60px; text-align: center"
            >
              <font align="center" color="white">{{ scope.$index + 1 }}</font>
            </div>
          </template>
        </el-table-column>

        <el-table-column label width="120">
          <template slot-scope="scope">
            <a class="btn btn-info" @click="showComment(scope.$index)">コメント</a>
          </template>
        </el-table-column>
        <el-table-column label="案件名" width="300">
          <template slot-scope="scope">
            {{ scope.row.matter_name }}
          </template>
        </el-table-column>
        <el-table-column label="商品番号" width="300">
          <el-table-column label="商品名/形式・規格" width="300">
            <template slot-scope="scope">
              {{ scope.row.product_code }}
              <br />
              {{ scope.row.product_name }}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="数量" width="100">
          <el-table-column label="単位" width="100">
            <template slot-scope="scope">
              {{ scope.row.quantity }}
              <br />
              {{ scope.row.unit }}
            </template>
          </el-table-column>
        </el-table-column>
      </el-table>
      <!-- コメントダイアログ -->
      <el-dialog
        title="コメント"
        :visible.sync="commentDialog"
        width="80%"
        :before-close="handleClose"
      >
        <div class="row">
          <div class="col-md-10">
            <label class="control-label">{{ this.comment }}</label>
          </div>
        </div>
        <span slot="footer" class="dialog-footer">
          <el-button @click="commentDialog = false">閉じる</el-button>
        </span>
      </el-dialog>
    </div>

    <div class="col-md-12 text-center">
      <br />
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
    comment: "",
    commentDialog: false,
    searchParams: {
      warehouse_id: 0,
      move_kind: 0,
    },
    selectedWareHouseList: null,
    searchedWareHouseId: 0,
    tableCount: 0,
    tableData: [],
    stockFlg: {
      itemsOrdered: 0, //発注品
      stock: 1, //在庫品
      custody: 2, //預かり品
    },
    moveKind: {
      warehouseMovement: 0, //倉庫移動
      redelivery: 1, //再配送
      returns: 2, //返品
      loading: 3, //積み込みデータ
    },
    qr_code: "",
    scanData: [],
  }),

  props: {
    warehouselist: Array,
    defaultwarehouseid: null,
    moveKindList: Array,
  },
  created: function () {},
  mounted: function () {
    this.searchParams.warehouse_id = this.defaultwarehouseid;
  },
  methods: {
    search() {
      this.loading = true;

      if (this.selectedWareHouseList != null && this.selectedWareHouseList.text == "") {
        this.searchParams.warehouse_id = null;
      }
      var params = new URLSearchParams();
      params.append("warehouse_id", this.searchParams.warehouse_id);
      params.append("move_kind", this.searchParams.move_kind);
      axios
        .post("/stock-transfer-acceptance/search", params)

        .then(
          function (response) {
            if (response.data) {
              this.tableData = response.data;
              this.searchedWareHouseId = this.searchParams.warehouse_id;
            }

            this.scanData = [];
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
              var isProductError = true;
              var errMsg = "";
              var qrCount = 0;
              var tableClone = [];
              var productArray = [];
              for (let i = 0; i < this.tableData.length; i++) {
                tableClone.push(Object.assign({}, this.tableData[i]));
                productArray.push(this.tableData[i].product_id);
              }

              for (var j = 0; j < qrInfo.length; j++) {
                // var isDuplication = false; //QR重複読込フラグ

                //スキャン済みQRはスルー
                // for (var j = 0; j < this.scanData.length; j++) {
                //   if (qrInfo[i].detail_id == this.scanData[j].detail_id) {
                //     isDuplication = true;
                //     break;
                //   }
                // }

                // if (isDuplication) {
                //   continue;
                // }

                //読み込んだ商品が存在しない場合エラー
                if(productArray.indexOf(qrInfo[j].product_id) == -1){
                      alert("含まれていない商品があるのでQRは読み込めません");
                      return;
                }

                // 行検索
                for (var i = 0; i < tableClone.length; i++) {
                  if (tableClone[i].quantity == tableClone[i].acceptance_quantity) {
                    continue;
                  }
                
                  //QRの商品、案件、得意先が一致した場合
                  if ((tableClone[i].move_kind == this.moveKind.warehouseMovement ||
                      tableClone[i].move_kind == this.moveKind.loading ||
                      tableClone[i].move_kind == this.moveKind.returns) &&
                    (
                      (
                        tableClone[i].move_kind == this.moveKind.returns &&
                        tableClone[i].stock_flg == 0 &&
                        tableClone[i].product_id == qrInfo[j].product_id &&
                        tableClone[i].matter_id == qrInfo[j].matter_id &&
                        tableClone[i].customer_id == qrInfo[j].customer_id && 
                        tableClone[i].qr_code == qrInfo[j].qr_code
                      ) ||
                      (
                        tableClone[i].move_kind != this.moveKind.returns &&
                        tableClone[i].stock_flg == 0 &&
                        tableClone[i].product_id == qrInfo[j].product_id &&
                        tableClone[i].matter_id == qrInfo[j].matter_id &&
                        tableClone[i].customer_id == qrInfo[j].customer_id
                      ) ||
                      (tableClone[i].stock_flg == 2 &&
                        tableClone[i].product_id == qrInfo[j].product_id &&
                        tableClone[i].customer_id == qrInfo[j].customer_id)) &&
                    Number(tableClone[i].quantity) >=
                      Number(tableClone[i].acceptance_quantity) +
                        Number(qrInfo[j].quantity)
                  ) {
                    if (
                      Number(tableClone[i].acceptance_quantity) +
                        Number(qrInfo[j].quantity) >
                      Number(tableClone[i].quantity)
                    ) {
                      alert("超過するため選択できません。");
                      return;
                    } else {
                      //配送数量を加算
                      tableClone[i].acceptance_quantity =
                        Number(tableClone[i].acceptance_quantity) +
                        Number(qrInfo[j].quantity);

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
                    }
                    break;
                  } else {
                    isQrError = true;
                    errMsg = "セットできない商品があるのでQRは読み込めません";
                  }
                }
               if(isQrError){
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
                  for (var i = 0; i < this.tableData.length; i++) {
                    if (
                      this.tableData[i].quantity ==
                      this.tableData[i].acceptance_quantity
                    ) {
                      continue;
                    }

                    //QRの商品、案件、得意先が一致した場合
                    if ((this.tableData[i].move_kind == this.moveKind.warehouseMovement ||
                        this.tableData[i].move_kind == this.moveKind.loading ||
                        this.tableData[i].move_kind == this.moveKind.returns) &&
                      (
                        (
                        this.tableData[i].move_kind == this.moveKind.returns &&
                        this.tableData[i].stock_flg == 0 &&
                        this.tableData[i].product_id == qrInfo[j].product_id &&
                        this.tableData[i].matter_id == qrInfo[j].matter_id &&
                        this.tableData[i].customer_id == qrInfo[j].customer_id && 
                        this.tableData[i].qr_code == qrInfo[j].qr_code
                      ) ||
                      (
                        this.tableData[i].move_kind != this.moveKind.returns &&
                        this.tableData[i].stock_flg == 0 &&
                        this.tableData[i].product_id == qrInfo[j].product_id &&
                        this.tableData[i].matter_id == qrInfo[j].matter_id &&
                        this.tableData[i].customer_id == qrInfo[j].customer_id) ||
                      (this.tableData[i].stock_flg == 2 &&
                        this.tableData[i].product_id == qrInfo[j].product_id &&
                        this.tableData[i].customer_id == qrInfo[j].customer_id)) &&
                        Number(this.tableData[i].quantity) >=
                          Number(this.tableData[i].acceptance_quantity) +
                            Number(qrInfo[j].quantity)
                    ) {
                      if (
                        Number(this.tableData[i].acceptance_quantity) +
                          Number(qrInfo[j].quantity) >
                        Number(this.tableData[i].quantity)
                      ) {
                        alert("超過するため選択できません。");
                        return;
                      } else {
                        //数量を加算
                        this.tableData[i].acceptance_quantity =
                          Number(this.tableData[i].acceptance_quantity) +
                          Number(qrInfo[j].quantity);

                        //スキャンしたQR情報をテーブルに保持
                        if (this.tableData[i].qrData == null) {
                          this.tableData[i].qrData = [];
                          this.tableData[i].qrData.push(qrInfo[j]);
                        } else {
                          this.tableData[i].qrData.push(qrInfo[j]);
                        }

                        //スキャン済みデータを保持
                        this.scanData.push({
                          detail_id: qrInfo[j].detail_id,
                          qr_id: qrInfo[j].qr_id,
                          qr_code: qrInfo[j].qr_code,
                          product_code: qrInfo[j].product_code,
                          quantity: qrInfo[j].quantity,
                          move_kind: this.tableData[i].move_kind,
                          stock_flg: this.tableData[i].stock_flg,
                        });
                        break;
                      }
                    }
                  }
                }
              }

                // var isMatchQr = false;

                // //まずQRで比較
                // for (var j = 0; j < this.tableData.length; j++) {
                //   if (
                //     this.tableData[j].qr_code == qrInfo[i].qr_code &&
                //     (this.tableData[j].move_kind == this.moveKind.warehouseMovement ||
                //       this.tableData[j].move_kind == this.moveKind.loading)
                //   ) {
                //     //既にスキャンされている場合次の行へ
                //     if (
                //       this.tableData[j].quantity == this.tableData[j].acceptance_quantity
                //     ) {
                //       continue;
                //     }

                //     //実績数追加
                //     var v = this.tableData[j].acceptance_quantity + qrInfo[i].quantity;

                //     this.tableData.splice(j, 1, {
                //       ...this.tableData[j],
                //       acceptance_quantity: v,
                //     });

                //     //スキャン済みQRリストに追加
                //     this.scanData.push({
                //       detail_id: qrInfo[i].detail_id,
                //       qr_id: qrInfo[i].qr_id,
                //       qr_code: qrInfo[i].qr_code,
                //       product_code: qrInfo[i].product_code,
                //       quantity: qrInfo[i].quantity,
                //       move_kind: this.tableData[j].move_kind,
                //       stock_flg: this.tableData[j].stock_flg,
                //     });

                //     isMatchQr = true;
                //     break;
                //   }
                // }

                // //Qrで一致した場合は案件などの比較なし
                // if (isMatchQr) {
                //   continue;
                // }

                // //商品、数量、案件、得意先、移動種別で比較して実績数量をカウントアップ
                // for (var j = 0; j < this.tableData.length; j++) {
                //   //在庫種別が一致しているか
                //   if (
                //     (qrInfo[i].matter_id != 0 &&
                //       qrInfo[i].customer_id != 0 &&
                //       this.tableData[j].stock_flg == this.stockFlg.itemsOrdered) ||
                //     (qrInfo[i].matter_id == 0 &&
                //       qrInfo[i].customer_id != 0 &&
                //       this.tableData[j].stock_flg == this.stockFlg.custody)
                //   ) {
                //     //倉庫移管
                //     if (
                //       (this.tableData[j].move_kind == this.moveKind.warehouseMovement ||
                //         this.tableData[j].move_kind == this.moveKind.loading) &&
                //       qrInfo[i].product_code == this.tableData[j].product_code
                //       //&& qrInfo[i].quantity == this.tableData[j].quantity
                //     ) {
                //       //既にスキャンされている場合次の行へ
                //       if (
                //         this.tableData[j].quantity ==
                //         this.tableData[j].acceptance_quantity
                //       ) {
                //         continue;
                //       }
                //       //実績数追加
                //       var v = this.tableData[j].acceptance_quantity + qrInfo[i].quantity;

                //       this.tableData.splice(j, 1, {
                //         ...this.tableData[j],
                //         acceptance_quantity: v,
                //       });

                //       //スキャン済みQRリストに追加
                //       this.scanData.push({
                //         detail_id: qrInfo[i].detail_id,
                //         qr_id: qrInfo[i].qr_id,
                //         qr_code: qrInfo[i].qr_code,
                //         product_code: qrInfo[i].product_code,
                //         quantity: qrInfo[i].quantity,
                //         move_kind: this.tableData[j].move_kind,
                //         stock_flg: this.tableData[j].stock_flg,
                //       });
                //       break;
                //     }


                    // //再配送、返送
                    // else if (
                    //   this.tableData[j].move_kind !=
                    //     this.moveKind.warehouseMovement &&
                    //   qrInfo[i].product_code == this.tableData[j].product_code
                    // ) {
                    //   //既にスキャンされている場合次の行へ
                    //   if (
                    //     this.tableData[j].quantity ==
                    //     this.tableData[j].acceptance_quantity
                    //   ) {
                    //     continue;
                    //   }

                    //   //実績数追加
                    //   var v = this.tableData[j].quantity;
                    //   this.tableData.splice(j, 1, {
                    //     ...this.tableData[j],
                    //     acceptance_quantity: v
                    //   });
                    //   //スキャン済みQRリストに追加
                    //   this.scanData.push({
                    //     detail_id: qrInfo[i].detail_id,
                    //     qr_code: qrInfo[i].qr_code,
                    //     product_code: qrInfo[i].product_code,
                    //     quantity: qrInfo[i].quantity,
                    //     move_kind: this.tableData[j].move_kind,
                    //     stock_flg: this.tableData[j].stock_flg
                    //   });
                    //   break;
                    // }
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
    //コメント
    showComment($index) {
      this.comment = this.tableData[$index].memo;
      this.commentDialog = true;
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
      if (row.quantity == row.acceptance_quantity) {
        return "acceptance-row";
      } else if (row.acceptance_quantity == 0) {
        return "normal-row";
      } else if (row.quantity != row.acceptance_quantity) {
        return "error-row";
      }
    },
    backColor(row) {
      if (
        row.stock_flg == this.stockFlg["stock"] ||
        (row.move_kind == this.moveKind["returns"] && this.rmUndefinedBlank(row.qr_code) == '') ||
        row.move_kind == this.moveKind["redelivery"]
      ) {
        return "black";
      } else {
        return "red";
      }
    },
    handleClick(row) {
      // 在庫品はタップで全数ON/OFF
      if (
        row.acceptance_quantity == 0 ||
        (row.acceptance_quantity != 0 && window.confirm("選択を解除しますか？"))
      ) {
        if (
          row.stock_flg == this.stockFlg["stock"] ||
          (row.move_kind == this.moveKind["returns"] && this.rmUndefinedBlank(row.qr_code) == '') ||
          row.move_kind == this.moveKind["redelivery"]
        ) {
          if (row.acceptance_quantity == 0) {
            row.acceptance_quantity = row.quantity;
          } else {
            row.acceptance_quantity = 0;
          }
        }
        //在庫品以外の場合はタップで全数OFF
        else {
          //スキャン済みQR配列から対象QRを全て削除
          if (row.acceptance_quantity != 0) {
            //QRがスキャンされている場合
            if (row.qrData != null) {
              //スキャンされたQRの繰り返し
              var qrCount = row.qrData.length;
              var qrCode = null;
              for (var i = qrCount - 1; i >= 0; i--) {
                //タップした行のQRコード、数量を取得
                if (qrCode == row.qrData[i].qr_code) {
                  continue;
                }
                qrCode = row.qrData[i].qr_code;
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
                        this.tableData[j].acceptance_quantity -= this.tableData[j].qrData[k].quantity;
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

          // if (row.acceptance_quantity != 0) {
          //   row.acceptance_quantity = 0;

          //   //スキャン済みQR配列から対象QRを全て削除
          //   this.scanData = this.scanData.filter(
          //     (d) =>
          //       !(
          //         d.product_code == row.product_code &&
          //         d.move_kind == row.move_kind &&
          //         d.stock_flg == row.stock_flg
          //       )
          //   );
          // }
        }
      }
    },
    handleClose(done) {
      done();
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
    checkTable: function () {
      return this.tableData.filter(
        (row) => row.quantity == row.acceptance_quantity && row.quantity != 0
      );
    },

    process() {
      if (this.checkTable().length > 0) {
        var move_kind = null;
        var errMsg = null;
        var return_flg = false;
        var return_cnt = 0;
        this.checkTable().forEach((data) => {
          if (data.move_kind == 2) {
            return_flg = true;
            return_cnt += 1;
          }
        });

        if (return_flg && return_cnt != this.checkTable().length) {
          alert("返品受入と通常移管受入は同時に行なえません。");
        } else {
          this.loading = true;
          localStorage.setItem("return_flg", JSON.stringify(return_flg));
          localStorage.setItem("tableData", JSON.stringify(this.tableData));
          localStorage.setItem("scanData", JSON.stringify(this.scanData));
          localStorage.setItem(
            "searchedWareHouseId",
            JSON.stringify(this.searchedWareHouseId)
          );
          var listUrl = "/stock-transfer-acceptance-shelf-qr";
          window.onbeforeunload = null;
          location.href = listUrl;
        }
      }
    },

    selectWarehouse: function (sender) {
      var item = sender.selectedItem;
      this.selectedWareHouseList = sender;
      this.searchParams.warehouse_id =
        item.id !== undefined && item.id !== null ? item.id : "";
      //this.search();
    },

    initMoveKind: function(sender) {
        this.wjSearchObj.move_kind = sender
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
  background: rgb(76, 217, 100);
}

.el-table .error-row {
  background: rgb(255, 158, 46);
}

.red {
  background: rgb(197, 6, 64);
}

.black {
  background: rgb(0, 0, 0);
}
</style>
