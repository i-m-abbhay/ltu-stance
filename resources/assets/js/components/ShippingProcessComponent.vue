<template>
  <div>
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <div class="row">
        <div class="col-md-2">
          <label class="control-label" for="acDepartment">商材数</label>
          <input
            type="text"
            class="form-control"
            v-model="baseInfo.item_number"
            v-bind:readonly="true"
          />
        </div>
        <div class="col-md-2">
          <label class="control-label" for="acDepartment">確認完了</label>
          <input
            type="text"
            class="form-control"
            v-model="baseInfo.complete_number"
            v-bind:readonly="true"
          />
        </div>
        <div class="col-md-2">
          <label class="control-label" for="acDepartment">確認未了</label>
          <input
            type="text"
            class="form-control"
            v-model="baseInfo.incomplete_number"
            v-bind:readonly="true"
          />
        </div>
      </div>
      <form
        id="searchForm"
        name="searchForm"
        class="form-horizontal"
        @submit.prevent="qrRead"
      >
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

          <div class="col-xs-6">
            <div class="form-group">
              <input type="tel" class="form-control" v-model="qr_code" />
            </div>
          </div>
          <div class="col-xs-3">
            <button type="button" class="btn btn-info" v-on:click="onDecode(qr_code)">
              QR手入力
            </button>
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
        <el-table-column
          prop="matter_no"
          label="案件No."
          sortable
          width="150"
        ></el-table-column>
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

        <!-- <el-table-column
          prop=""
          label="予定数量"
          sortable
          width="100"
        ></el-table-column> -->
        <el-table-column label="予定数量" width="110" sortable>
          <el-table-column label="単位" width="110" sortable>
            <template slot-scope="scope">
              {{ scope.row.shipment_quantity }}
              <br />
              {{ scope.row.unit }}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column
          prop="loading_quantity"
          label="配送数量"
          sortable
          width="110"
        ></el-table-column>
        <!-- <el-table-column prop="qr_code" label="QRコード" sortable width="150"></el-table-column> -->
        <el-table-column label="数量変更" width="100">
          <template slot-scope="scope">
            <el-button
              type="text"
              v-show="tableData[scope.$index].stock_flg == 1"
              @click="showNumberDialog(scope.$index)"
              >数量変更</el-button
            >
          </template>
        </el-table-column>

        <!-- <el-table-column
          prop="stock_quantity"
          label="在庫数量テスト用"
          sortable
          width="100"
        ></el-table-column> -->
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
            <label class="control-label" for="acDepartment">予定数量</label>
            <input
              type="text"
              class="form-control"
              v-model="shipment_quantity"
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
            <br />このまま実行した場合、搭載されなかった商材は再度出荷配送が必要になります。
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
    </div>
    <div class="col-md-12 text-center">
      <br />
      <button type="button" class="btn btn-warning btn-back" v-on:click="back">
        戻る
      </button>
      <button
        type="button"
        class="btn btn-primary btn-save"
        v-on:click="showProcessDialog"
      >
        実行
      </button>
    </div>
  </div>
</template>

<script>
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from "vue-qrcode-reader";
import { forEach } from "lodash";
export default {
  components: { QrcodeStream, QrcodeDropZone, QrcodeCapture },
  data: () => ({
    loading: false,

    searchParams: {
      shipment_id: "",
      from_date: "",
      to_date: "",
      warehouse_name: "",
      shipment_detail_id: "",
    },
    errors: {
      change_quantity: "",
    },

    baseInfo: {
      item_number: 0,
      complete_number: 0,
      incomplete_number: 0,
    },
    tableData: [],
    urlparam: "",
    id: "",
    numberDialog: false,
    processDialog: false,
    product_name: "",
    shipment_quantity: 0,
    change_quantity: 0,
    current: 0,
    result: "",
    message: "",
    noStreamApiSupport: false,
    qr_read: false,
    detail_index: 0,
    shipment_detail_id: [],
    loading_quantity: [],
    qr_code: "",
    qrInfo: [],
    scanData: [],
  }),
  props: {
    temporaryShelfId: Array,
  },
  created: function () {},
  mounted: function () {
    this.searchParams.shipment_id = JSON.parse(localStorage.getItem("shipment_id")) || [];

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
      params.append("shipment_id", this.searchParams.shipment_id);
      axios
        .post("/shipping-process/search", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.tableData = response.data;
              // 基本情報設定
              this.baseInfo.item_number = this.tableData.length;
              this.baseInfo.complete_number = 0;
              this.baseInfo.incomplete_number = this.tableData.length;
              for (var i = 0; i < this.tableData.length; i++) {
                this.tableData[i].loading_quantity = 0;
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
            // location.reload();
          }.bind(this)
        );
    },
    //スキャン済みのテーブルのみ取得
    checkTable: function () {
      return this.tableData.filter((row) => row.loading_quantity != 0);
    },
    // 数量変更
    showNumberDialog($index) {
      this.current = $index;
      this.product_name = this.tableData[$index].product_name;
      this.shipment_quantity = this.tableData[$index].shipment_quantity;
      this.change_quantity = this.tableData[$index].loading_quantity;
      this.numberDialog = true;
    },
    // 配送確認
    showProcessDialog() {
      // 0件の場合
      if (this.checkTable().length == 0) {
        return;
      }

      //エラー判定
      var isError = false;
      for (let i = 0; i < this.tableData.length; i++) {
        if (
          this.tableData[i].stock_flg != 1 &&
          this.tableData[i].loading_quantity != 0 &&
          this.tableData[i].loading_quantity != this.tableData[i].shipment_quantity
        ) {
          isError = true;
        }
      }

      if (isError) {
        alert("予定数量と配送数量が異なります。");
      } else if (this.tableData.length == this.baseInfo.complete_number) {
        // 全部設定済み
        this.process();
      } else {
        // 一部設定なし
        this.processDialog = true;
      }
    },
    // 数量更新
    updateQuantity() {
      if (this.tableData[this.current].stock_flg == 1) {
        //同一商品（同一在庫種別、同一倉庫）の集計
        var loadingTotal = 0;
        loadingTotal += Number(this.change_quantity);
        this.tableData.forEach((data) => {
          if (
            this.tableData[this.current].product_id === data.product_id &&
            this.tableData[this.current].from_warehouse_id === data.from_warehouse_id &&
            this.tableData[this.current].stock_flg === data.stock_flg
          ) {
            loadingTotal += Number(data.loading_quantity);
          }
        });

        //有効引当数チェック
        if (
          this.change_quantity > this.tableData[this.current].reserve_quantity_validity
        ) {
          alert("有効引当数を超過するため選択できません。");
        }
        //配送数量が在庫数を超えようとする場合
        else if (loadingTotal > this.tableData[this.current].stock_quantity) {
          alert("在庫数を超過するため選択できません。");
        } else {
          this.numberDialog = false;
          this.tableData[this.current].loading_quantity = this.change_quantity;
          this.showNumber();
        }
      }
    },
    // 戻る
    back() {
      var listUrl = "/shipping-list";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    handleClose(done) {
      done();
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
      this.change_quantity = this.shipment_quantity;
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
    isQrArrivalShelf(qrData) {
      // 商品が入荷一時置場に置いてあった場合はQR読み取り処理を終了する
      var isErr = false;
      if (qrData !== null) {
        this.temporaryShelfId.forEach(id => {
          if (id == qrData.shelf_number_id) {
            isErr = true;
          }
        });
      }
      return isErr;
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
              var isProductError = true;
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
                if(productArray.indexOf(this.qrInfo[j].product_id) == -1){
                      alert("出荷指示に含まれていない商品があるのでQRは読み込めません");
                      return;
                }

                // QRの棚種別チェック 入荷棚だった場合はスキャンさせない
                var isArrivalShelf = this.isQrArrivalShelf(this.qrInfo[j]);
                if (isArrivalShelf) {
                  alert("入荷一時置場に存在する商品があるため、QRは読み込めません");
                  return;
                }

                // 行検索
                for (var i = 0; i < tableClone.length; i++) {
                  if (tableClone[i].shipment_quantity == tableClone[i].loading_quantity) {
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
                    Number(tableClone[i].shipment_quantity) >=
                      Number(tableClone[i].loading_quantity) +
                        Number(this.qrInfo[j].quantity)
                  ) {
                    if (tableClone[i].from_warehouse_id != this.qrInfo[j].warehouse_id) {
                      // 出荷元とQRの倉庫が異なる
                      isQrError = true;
                      errMsg = "出荷元倉庫とQRの倉庫が異なります。";
                      continue;
                    }

                    if (
                      Number(tableClone[i].loading_quantity) +
                        Number(this.qrInfo[j].quantity) >
                      Number(tableClone[i].reserve_quantity_validity)
                    ) {
                      alert("有効引当数を超過するため選択できません。");
                      return;
                    } else {
                      this.qrInfo[j].is_scan = true;

                      //配送数量を加算
                      tableClone[i].loading_quantity =
                        Number(tableClone[i].loading_quantity) +
                        Number(this.qrInfo[j].quantity);

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
                    }
                    break;
                  } else {
                    isQrError = true;
                    errMsg = "出荷指示にセットできない商品があるのでQRは読み込めません";
                  }
                }
               if(isQrError){
                  alert(errMsg);
                  return;
                }
              }
              for (var j = 0; j < this.qrInfo.length; j++) {
                if (!this.qrInfo[j].is_scan) {
                  // 読み込まれていない商品が存在する
                  alert("出荷指示にセットできない商品があるのでQRは読み込めません")
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
                      this.tableData[i].shipment_quantity ==
                      this.tableData[i].loading_quantity
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
                        Number(this.tableData[i].shipment_quantity) >=
                          Number(this.tableData[i].loading_quantity) +
                            Number(this.qrInfo[j].quantity)
                    ) {
                      if (
                        Number(this.tableData[i].loading_quantity) +
                          Number(this.qrInfo[j].quantity) >
                        Number(this.tableData[i].reserve_quantity_validity)
                      ) {
                        alert("有効引当数を超過するため選択できません。");
                        return;
                      } else {
                        //配送数量を加算
                        this.tableData[i].loading_quantity =
                          Number(this.tableData[i].loading_quantity) +
                          Number(this.qrInfo[j].quantity);

                        //スキャンしたQR情報をテーブルに保持
                        if (this.tableData[i].qrData == null) {
                          this.tableData[i].qrData = [];
                          this.tableData[i].qrData.push(this.qrInfo[j]);
                        } else {
                          this.tableData[i].qrData.push(this.qrInfo[j]);
                        }

                        //スキャン済みデータを保持
                        this.scanData.push({
                          qr_code: this.qrInfo[j].qr_code,
                        });
                        break;
                      }
                    }
                  }
                }
              }

              this.showNumber();
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
      if (
        row.loading_quantity === row.shipment_quantity ||
        (row.stock_flg === 1 && row.loading_quantity != 0)
      ) {
        return "loading-row";
      } else if (row.loading_quantity == 0) {
        return "normal-row";
      } else if (row.stock_flg != 1) {
        return "error-row";
      }
    },
    handleClick(row) {
      // 在庫品の場合
      if (!this.numberDialog) {
        if (
          row.loading_quantity == 0 ||
          (row.loading_quantity != 0 && window.confirm("選択を解除しますか？"))
        ) {
          if (row.stock_flg == 1) {
            if (row.loading_quantity == 0) {
              //同一商品（同一在庫種別、同一倉庫）の集計
              var loadingTotal = 0;
              loadingTotal += Number(row.shipment_quantity);
              this.tableData.forEach((data) => {
                if (
                  row.product_id === data.product_id &&
                  row.from_warehouse_id === data.from_warehouse_id &&
                  row.stock_flg === data.stock_flg
                ) {
                  loadingTotal += Number(data.loading_quantity);
                }
              });

              //配送数量が在庫数を超えようとする場合
              if (row.shipment_quantity > row.reserve_quantity_validity) {
                alert("有効引当数を超過するため選択できません。");
              } else if (loadingTotal > row.stock_quantity) {
                alert("在庫数を超過するため選択できません。");
              } else {
                row.loading_quantity = row.shipment_quantity;
                this.baseInfo.complete_number += 1;
              }
            } else {
              row.loading_quantity = 0;
              this.baseInfo.complete_number -= 1;
            }
            //QRの場合
          } else {
            //スキャン済みQR配列から対象QRを全て削除
            if (row.loading_quantity != 0) {
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
                          this.tableData[j].loading_quantity -= this.tableData[j].qrData[
                            k
                          ].quantity;
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

          this.showNumber();
        }
      }
    },
    // 数量再表示
    showNumber() {
      this.baseInfo.complete_number = 0;
      for (var i = 0; i < this.tableData.length; i++) {
        if (this.tableData[i].loading_quantity > 0) {
          this.baseInfo.complete_number += 1;
        }
      }
      this.baseInfo.incomplete_number =
        this.tableData.length - this.baseInfo.complete_number;
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
    // 実行
    process() {
      // エラーの初期化
      this.initErr(this.errors);
      var isError = false;
      //this.loading = true;
      this.processDialog = false;

      // パラメータ作成
      this.shipment_detail_id = [];
      this.loading_quantity = [];
      for (var i = 0; i < this.tableData.length; i++) {
        this.shipment_detail_id.push(this.tableData[i].shipment_detail_id);
        this.loading_quantity.push(this.tableData[i].loading_quantity);
      }
      if (this.shipment_detail_id.length > 0) {
        localStorage.setItem(
          "shipment_detail_id",
          JSON.stringify(this.shipment_detail_id)
        );
        localStorage.setItem("loading_quantity", JSON.stringify(this.loading_quantity));

        localStorage.setItem("tableData", JSON.stringify(this.checkTable()));
        var listUrl = "/shipping-truck";
        window.onbeforeunload = null;
        location.href = listUrl;
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
.el-table .error-row {
  background: rgb(255, 158, 46);
}

.form-group {
  padding-left: 0px;
  padding-right: 10px;
  padding-top: 0px;
}
</style>
