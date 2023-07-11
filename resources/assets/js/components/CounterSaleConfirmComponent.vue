<template>
  <div class>
    <div class="col-xs-12 text-left">
      <div class="col-xs-3 text-left">
        <label class="control-label">案件名</label>
      </div>
      <div class="col-xs-9 text-left">{{ customerlist.matter_name }}</div>
    </div>

    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="tableData"
        :default-sort="{ prop: 'product_code', order: 'ascending' }"
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label="No." width="75">
          <template slot-scope="scope">
            <div class="text-center">{{ scope.$index + 1 }}</div>
            <br />
            <button
              type="button"
              class="btn btn-info btn-quantity-change"
              v-bind:disabled="isPrinted"
              v-on:click="onClickChangeQuantity(scope.$index)"
            >
              <div class="text-center">数量</div>
              <div class="text-center">変更</div>
            </button>
          </template>
        </el-table-column>

        <el-table-column label="商品番号" width="200">
          <el-table-column label="商品名/形式・規格" width="200">
            <template slot-scope="scope">
              {{ scope.row.product_code }}
              <br />
              {{ scope.row.product_name }} / {{ scope.row.model }}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="数量(入力数) " width="120">
          <el-table-column label="金額" width="120">
            <template slot-scope="scope">
              <div class="text-right">
                {{ comma_format(scope.row.quantity) }}({{
                  comma_format(scope.row.quantity * scope.row.min_quantity)
                }})
              </div>
              <br />
              <div class="text-right">{{ comma_format(scope.row.sales_amount) }}</div>
            </template>
          </el-table-column>
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
          <div class="col-xs-12">
            <u>
              <h4>商品名</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : tableData[this.activeRowIndex].product_name
              }}
            </div>
            <div class="col-xs-12">
              <button
                type="button"
                class="btn btn-info btn-delete"
                v-on:click="deleteRow"
              >
                明細削除
              </button>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>変更数量</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-6">
              <div
                class="form-group"
                v-bind:class="{ 'has-error': errors.quantity != '' }"
              >
                <input
                  type="number"
                  class="form-control"
                  v-model="changeQuantity"
                  v-bind:readonly="false"
                />
                <div class="text-danger">{{ errors.quantity }}</div>
              </div>
            </div>
            <div class="col-xs-6">
              <button
                type="button"
                class="btn btn-info btn-minus"
                v-on:click="minus_quantity"
              >
                －
              </button>
              <button
                type="button"
                class="btn btn-info btn-plus"
                v-on:click="plus_quantity"
              >
                ＋
              </button>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>単位</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null ? null : tableData[this.activeRowIndex].unit
              }}
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>最小単位数</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : tableData[this.activeRowIndex].min_quantity
              }}
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>在庫数</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : tableData[this.activeRowIndex].actual_quantity
              }}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>入荷予定数</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : tableData[this.activeRowIndex].arrival_quantity
              }}
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>入荷予定日</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : tableData[this.activeRowIndex].next_arrival_date
              }}
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>標準販売単価</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : tableData[this.activeRowIndex].normal_sales_price
              }}
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <u>
              <h4>数量（入力数）</h4>
            </u>
          </div>
          <div class="col-xs-12">
            <div class="col-xs-12">
              {{
                this.activeRowIndex == null
                  ? null
                  : this.changeQuantity * tableData[this.activeRowIndex].min_quantity
              }}
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-3">
            <u>
              <h4>金額</h4>
            </u>
          </div>
          <div class="col-xs-9 text-right">
            <h3>
              {{
                this.activeRowIndex == null
                  ? null
                  : comma_format(
                      Number(tableData[this.activeRowIndex].normal_sales_price) *
                        Number(changeQuantity) *
                        Number(tableData[this.activeRowIndex].min_quantity)
                    )
              }}
            </h3>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12 text-center">
            <el-button type="primary" @click="processChangeQuantity">OK</el-button>
          </div>
        </div>
      </el-dialog>

      <!-- 領収書印刷ダイアログ -->
      <el-dialog title :visible.sync="printDialog" width="80%">
        <div class="row">
          <div class="col-xs-12 text-center">
            <h3>領収書印刷</h3>
          </div>
        </div>

        <div class="col-xs-12">
          <div>領収書を下記の宛名で印刷します。</div>
        </div>

        <div class="row">
          <div class="col-xs-3" style="margin-top: 7px">
            <label class="control-label">宛名</label>
          </div>
          <div class="col-xs-9">
            <input
              type="text"
              class="form-control"
              v-model="address"
              v-bind:readonly="false"
            />
          </div>
        </div>

        <div class="row">
          <div class="col-xs-4 col-xs-offset-4 text-right" style="margin-top: 12px">
            <label class="control-label">宛名空欄</label>
          </div>
          <div class="col-xs-4 text-left" style="margin-top: 7px">
            <label class="switch">
              <input type="checkbox" v-model="isEmptyAddress" />
              <span class="slider round"></span>
            </label>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12">
            <font style="color: red">ロール紙を確認してから印刷実行してください。</font>
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12 text-center">
            <el-button
              type="primary"
              v-show="!showCopyPrintBtn"
              @click="printProcess"
              v-bind:disabled="actPrintBtn || isPrinted"
              >印刷実行</el-button
            >
          </div>
        </div>

        <div class="row">
          <div class="col-xs-12 text-center">
            <el-button
              type="primary"
              v-show="showCopyPrintBtn"
              @click="copyPrintProcess"
              v-bind:disabled="actPrintBtn || isPrinted"
              >控え印刷</el-button
            >
          </div>
        </div>
      </el-dialog>
    </div>

    <div class="col-xs-12 text-center">
      <br />これでよろしければサインをお願いします。
    </div>
    <div class="col-xs-12 text-center">
      <br />
    </div>
    <div class="col-xs-3 text-left">
      <u>
        <h4>
          <a @click="clickSign">サイン</a>
        </h4>
      </u>
      <img v-bind:src="sign" width="80px" height="80px" />
    </div>

    <div class="col-xs-6 text-left">クレジット払い</div>
    <div class="col-xs-3 text-right">
      <label class="switch">
        <input type="checkbox" v-model="isCredite" v-bind:disabled="isPrinted" />
        <span class="slider round"></span>
      </label>
    </div>

    <div class="col-xs-4 text-left">金額小計</div>
    <div class="col-xs-5 text-right">{{ comma_format(this.total) }}</div>

    <div class="col-xs-4 text-left">消費税</div>
    <div class="col-xs-5 text-right">{{ comma_format(this.tax) }}</div>

    <div class="col-xs-4 text-left">税込合計</div>
    <div class="col-xs-5 text-right">{{ comma_format(this.taxTotal) }}</div>

    <div class="col-xs-3 text-left">
      <button
        type="button"
        class="btn btn-save"
        @click="print"
        v-bind:disabled="isPrinted"
      >
        領収書印刷
      </button>
    </div>
    <div class="col-xs-6">
      <div class="col-xs-12 text-right">{{ printDate }} {{ printStatus }}</div>
    </div>

    <div class="col-xs-12">
      <div class="col-xs-6 text-left">
        <br />
        <a class="btn btn-primary" @click="back">ホームへ</a>
      </div>
      <div class="col-xs-6 text-right">
        <br />
        <button
          type="button"
          class="btn btn-primary gold-back"
          @click="next"
          v-bind:disabled="isPrinted"
        >
          商材追加
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    printDate: null,
    printStatus: null,
    tableData: [],
    sign: null,
    matter_name: null,
    numberDialog: false,
    printDialog: false,
    activeRowIndex: null,
    address: null, //宛名
    isEmptyAddress: true,
    isCredite: false,
    changeQuantity: 0,
    total: null,
    tax: null,
    taxTotal: null,
    purchase_price_total: null,
    errors: { quantity: "" },
    REQUIRED_QUANTITY: "1以上の数値を入力してください",
    actPrintBtn: false,
    isPrinted: false,
    showCopyPrintBtn: false,
  }),

  props: {
    customerlist: Object,
    taxrate: null,
  },
  created: function () {},
  mounted: function () {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.sign = JSON.parse(localStorage.getItem("sign"));
    this.isCredite = JSON.parse(localStorage.getItem("isCredite"));
    this.showTotal();
  },
  methods: {
    //控え印刷
    copyPrintProcess() {
      if (!this.isEmptyAddress && (this.address == null || this.address == "")) {
        alert("宛名が入力されていません。");
      } else {
        //領収書の発行処理---------------
        axios
          .post("/counter-sale-confirm/print", {
            tableData: this.tableData,
            customerList: this.customerlist,
            total: this.total,
            tax: this.tax,
            taxTotal: this.taxTotal,
            address: this.address,
            sign: this.sign,
            copy_flg: true,
          })
          .then(
            function (response) {
              if (response.data) {
                // 成功
                if (this.rmUndefinedBlank(response.data.url) != "") {
                  window.location.href = response.data.url;
                }
                this.actPrintBtn = false;
                this.isPrinted = true;

                this.printDate = this.date_format(new Date(), "YYYY/MM/DD hh:mm:ss");
                this.printStatus = "印刷済";
                // this.printDialog = false;
              } else {
                // 失敗
                window.onbeforeunload = null;
                alert(MSG_ERROR);
                location.reload();
              }
              this.loading = false;
            }.bind(this)
          )
          .catch(
            function (error) {
              this.loading = false;
              if (error.response.data.errors) {
                // エラーメッセージ表示
                this.showErrMsg(error.response.data.errors, this.errors);
              } else {
                alert(MSG_ERROR);

                window.onbeforeunload = null;
                location.reload();
              }
            }.bind(this)
          );
      }
    },
    //印刷実行
    printProcess() {
      if (!this.isEmptyAddress && (this.address == null || this.address == "")) {
        alert("宛名が入力されていません。");
      } else {
        if (window.confirm("領収書を印刷します。よろしいですか？")) {
          this.actPrintBtn = true;
          //領収書の発行処理---------------
          axios
            .post("/counter-sale-confirm/print", {
              tableData: this.tableData,
              customerList: this.customerlist,
              total: this.total,
              tax: this.tax,
              taxTotal: this.taxTotal,
              address: this.address,
              sign: this.sign,
              copy_flg: false,
            })
            .then(
              function (response) {
                if (response.data) {
                  // 成功
                  if (this.rmUndefinedBlank(response.data.url) != "") {
                    window.location.href = response.data.url;
                  }
                  this.actPrintBtn = false;
                  this.showCopyPrintBtn = true;

                  // this.printDate = this.date_format(
                  //   new Date(),
                  //   "YYYY/MM/DD hh:mm:ss"
                  // );
                  // this.printStatus = "印刷済";

                  // if (
                  alert(
                    "領収書(正)が印刷完了後に表示される発行完了画面の閉じるを押すと、\n続けて領収書(控え）が印刷されます。"
                  );
                  //   )
                  // ) {
                  //   this.copyPrintProcess();
                  // }
                  // this.printDialog = false;
                  // }
                } else {
                  // 失敗
                  window.onbeforeunload = null;
                  alert(MSG_ERROR);
                  location.reload();
                }
                this.loading = false;
              }.bind(this)
            )
            .catch(
              function (error) {
                this.loading = false;
                if (error.response.data.errors) {
                  // エラーメッセージ表示
                  this.showErrMsg(error.response.data.errors, this.errors);
                } else {
                  alert(MSG_ERROR);

                  window.onbeforeunload = null;
                  location.reload();
                }
              }.bind(this)
            );
        }
      }
    },
    //領収書印刷
    print() {
      if (this.sign == null) {
        alert("サインを入力してください。");
      } else {
        if (window.confirm("購入する製品を確定します。よろしいですか？")) {
          if (this.tableData.length == 0) {
            return;
          }

          // エラーの初期化
          this.initErr(this.errors);

          this.loading = true;
          var creditFlg = 0;
          if (this.isCredite) {
            creditFlg = 1;
          }
          axios
            .post("/counter-sale-confirm/save", {
              tableData: this.tableData,
              customerList: this.customerlist,
              total: this.total,
              tax: this.tax,
              sign: this.sign,
              taxTotal: this.taxTotal,
              purchaseTotal: this.purchase_price_total,
              creditFlg: creditFlg,
            })
            .then(
              function (response) {
                if (response.data === true) {
                  // 成功
                  this.printDialog = true;
                } else if (response.data != null && Array.isArray(response.data)) {
                  var errArray = response.data;
                  var errMsg =
                    "以下の商品の購入数量は、在庫数を超えています。再度確認してください。";
                  errArray.forEach((err) => {
                    errMsg += "\n" + err;
                  });
                  alert(errMsg);
                } else {
                  // 失敗
                  window.onbeforeunload = null;
                  alert(MSG_ERROR);
                  location.reload();
                }
                this.loading = false;
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
        }
      }
    },

    //合計表示
    showTotal() {
      this.total = 0;
      this.purchase_price_total = 0;
      this.tableData.forEach((data) => {
        this.total += data.sales_amount;
        this.purchase_price_total +=
          this.roundDecimalSalesPrice(data.purchase_price * data.quantity * data.min_quantity);
      });
      this.tax = this.roundDecimalStandardPrice(this.total * this.taxrate);
      this.taxTotal = this.roundDecimalSalesPrice(this.total + this.tax);
    },
    // 数量変更
    onClickChangeQuantity(rowIndex) {
      this.activeRowIndex = rowIndex;
      this.changeQuantity = this.tableData[rowIndex].quantity;
      this.tableData[rowIndex].normal_sales_price;
      this.numberDialog = true;
    },
    processChangeQuantity() {
      if (
        Number(this.changeQuantity) >
        Number(this.tableData[this.activeRowIndex].actual_quantity)
      ) {
        alert("在庫数を超える数量を設定することはできません。");
      } else if (this.changeQuantity <= 0) {
        this.errors.quantity = this.REQUIRED_QUANTITY;
      } else {
        this.tableData[this.activeRowIndex].quantity = this.changeQuantity;
        // 販売金額
        this.tableData[this.activeRowIndex].sales_amount =
          this.roundDecimalSalesPrice(
            this.changeQuantity *
            this.tableData[this.activeRowIndex].min_quantity *
            this.tableData[this.activeRowIndex].normal_sales_price);

        // 消費税額
        this.tableData[this.activeRowIndex].consumption_tax =
          this.roundDecimalStandardPrice(
            this.changeQuantity *
            this.tableData[this.activeRowIndex].min_quantity *
            this.tableData[this.activeRowIndex].normal_sales_price *
            this.taxrate);
        
        // 販売金額＋消費税額
        this.tableData[this.activeRowIndex].tax_sales_amount = 
          this.tableData[this.activeRowIndex].sales_amount + 
          this.tableData[this.activeRowIndex].consumption_tax;
            // this.changeQuantity *
            // this.tableData[this.activeRowIndex].min_quantity *
            // this.tableData[this.activeRowIndex].normal_sales_price *
            // (1 + this.taxrate);

        this.showTotal();
        this.numberDialog = false;
      }
    },
    deleteRow() {
      if (window.confirm("選択している商品の購入情報を削除します。よろしいですか？")) {
        this.tableData.splice(this.activeRowIndex, 1);
        localStorage.setItem("tableData", JSON.stringify(this.tableData));
        this.activeRowIndex = null;
        this.showTotal();
        this.numberDialog = false;
      }
    },
    minus_quantity() {
      var q = this.changeQuantity;
      if (!Number(q)) {
        this.changeQuantity = 0;
      } else if (q > 0) {
        this.changeQuantity = Number(q) - 1;
      }
    },
    plus_quantity() {
      var q = this.changeQuantity;
      if (!Number(q)) {
        this.changeQuantity = 1;
      } else {
        this.changeQuantity = Number(q) + 1;
      }
    },
    //商材追加
    next() {
      var listUrl = "/counter-sale";
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      localStorage.setItem("isCredite", JSON.stringify(this.isCredite));
      localStorage.setItem("is_back", true);
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    //ホームへ
    back() {
      var listUrl = "/mobile-menu";
      localStorage.setItem("tableData", null);
      localStorage.setItem("sign", null);
      localStorage.setItem("isCredite", null);
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    //サイン
    clickSign() {
      if (!this.isPrinted) {
        var listUrl = "/counter-sale-sign";
        localStorage.setItem("tableData", JSON.stringify(this.tableData));
        localStorage.setItem("isCredite", JSON.stringify(this.isCredite));
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },
    // 3桁ずつカンマ区切り
    comma_format: function (val) {
      if (val == undefined || val == "") {
        return 0;
      }
      val = parseInt(val);
      return val.toLocaleString();
    },
    date_format: function (date, format) {
      // フォーマット文字列内のキーワードを日付に置換する
      format = format.replace(/YYYY/g, date.getFullYear());
      format = format.replace(/MM/g, ("0" + (date.getMonth() + 1)).slice(-2));
      format = format.replace(/DD/g, ("0" + date.getDate()).slice(-2));
      format = format.replace(/hh/g, ("0" + date.getHours()).slice(-2));
      format = format.replace(/mm/g, ("0" + date.getMinutes()).slice(-2));
      format = format.replace(/ss/g, ("0" + date.getSeconds()).slice(-2));

      return format;
    },
    handleClose(done) {
      done();
      this.activeRowIndex = null;
    },
  },
};
</script>

<style>
.btn-quantity-change {
  background: rgb(231, 5, 5);
  border-color: rgb(211, 2, 2);
  width: 100%;
}
.btn-quantity-change:active {
  background: rgb(190, 4, 4) !important ;
  border-color: rgb(158, 3, 3) !important ;
  width: 100%;
}
.btn-quantity-change:hover {
  background: rgb(253, 16, 16);
  border-color: rgb(212, 12, 12);
  width: 100%;
}

.gold-back {
  background: rgb(119, 117, 1);
  border-color: rgb(107, 105, 1);
}
.gold-back :active {
  background: rgb(90, 89, 0) !important ;
  border-color: rgb(83, 82, 1) !important ;
}
.gold-back :hover {
  background: rgb(148, 145, 0);
  border-color: rgb(134, 132, 0);
}
.btn-minus {
  background: rgb(231, 5, 5);
  border-color: rgb(211, 2, 2);
}
.btn-minus:hover {
  background: rgb(253, 16, 16);
  border-color: rgb(212, 12, 12);
}
.btn-minus:active {
  background: rgb(190, 4, 4) !important ;
  border-color: rgb(158, 3, 3) !important ;
}

.btn-plus {
  background: rgb(23, 4, 190);
}

.btn-delete {
  background: rgb(231, 5, 5);
  border-color: rgb(211, 2, 2);
  margin: 10px;
  padding: 1px 10px;
}
.btn-delete:hover {
  background: rgb(253, 16, 16);
  border-color: rgb(212, 12, 12);
}
.btn-delete:active {
  background: rgb(190, 4, 4) !important ;
  border-color: rgb(158, 3, 3) !important ;
}

/* The switch - the box around the slider */
.switch {
  position: relative;
  display: inline-block;
  width: 60px;
  height: 34px;
}

/* Hide default HTML checkbox */
.switch input {
  opacity: 0;
  width: 0;
  height: 0;
}

/* The slider */
.slider {
  position: absolute;
  cursor: pointer;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background-color: #ccc;
  -webkit-transition: 0.4s;
  transition: 0.4s;
}

.slider:before {
  position: absolute;
  content: "";
  height: 26px;
  width: 26px;
  left: 4px;
  bottom: 4px;
  background-color: white;
  -webkit-transition: 0.4s;
  transition: 0.4s;
}

input:checked + .slider {
  background-color: #2196f3;
}

input:focus + .slider {
  box-shadow: 0 0 1px #2196f3;
}

input:checked + .slider:before {
  -webkit-transform: translateX(26px);
  -ms-transform: translateX(26px);
  transform: translateX(26px);
}

/* Rounded sliders */
.slider.round {
  border-radius: 34px;
}

.slider.round:before {
  border-radius: 50%;
}
</style>
