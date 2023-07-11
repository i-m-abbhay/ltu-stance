<template>
  <div>
    <div class="grid-form" id="gridForm">
      <form
        id="gridForm"
        name="gridForm"
        class="form-horizontal"
        method="post"
        action="/base-list/search"
      >
        <div class="col-md-12 col-sm-12 col-xs-12">
          <el-checkbox v-model="checkArrivalToday">本日入荷</el-checkbox>
          <el-checkbox v-model="checkArrivalNull">入荷未入力</el-checkbox>
        </div>

        <div class="col-md-4 col-sm-6 col-xs-12">
          <label class="control-label">品番</label>
          <div id="textarea">
            <input type="text" class="form-control" v-model="filProductCode" />
          </div>
        </div>
        <div class="col-md-4 col-sm-6 col-xs-12">
          <label class="control-label">品名</label>
          <div id="textarea">
            <input type="text" class="form-control" v-model="filProductName" />
          </div>
        </div>

        <!-- 検索結果グリッド -->
        <div class="col-md-12 col-sm-12 col-xs-12">
          <div class="pull-right">
            <br />
            <p class="pull-right search-count" style>明細行： {{ tableData.length }}件</p>
          </div>
        </div>
        <el-table
          :data="tableData"
          :row-class-name="tableRowClassName"
          ref="multipleTable"
          :default-sort="{ prop: 'product_id', order: 'ascending' }"
          v-loading="loading"
          @selection-change="handleSelectionChange"
          style="width: 100%"
        >
          <el-table-column label="入荷予定日" width="145">
            <el-table-column label="発注番号" width="145">
              <template slot-scope="scope">
                <div v-if="scope.row.arrival_plan_date != null">
                  {{ scope.row.arrival_plan_date.replace(/-/g, "/") }}
                </div>
                <div v-if="scope.row.arrival_plan_date == null">
                  <br />
                </div>
                <br />

                {{ scope.row.order_no }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="案件番号" prop="matter_no" width="215">
            <el-table-column label="案件名" prop="matter_name" width="215">
              <template slot-scope="scope" sortable>
                {{ scope.row.matter_no }}
                <br />
                {{ scope.row.matter_name }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="メーカー" width="200" prop="maker_name">
            <el-table-column label="仕入先" width="200" prop="supplier_name">
              <template slot-scope="scope" sortable>
                {{ scope.row.maker_name }}
                <br />
                {{ scope.row.supplier_name }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column
            prop="product_code"
            label="商品番号"
            width="150"
          ></el-table-column>

          <el-table-column label="商品名" width="200">
            <el-table-column label="商品型式/規格" width="200">
              <template slot-scope="scope">
                {{ scope.row.product_name }}
                <br />
                {{ scope.row.model }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="予定数量" width="100">
            <el-table-column label="入荷済数" width="100">
              <template slot-scope="scope">
                <div class="text-right">{{ Number(scope.row.order_quantity) }}</div>
                <div class="text-right">{{ scope.row.arrival_quantity }}</div>
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column
            type="selection"
            property="check_box"
            width="45"
            :selectable="canSelectRow"
          >
          </el-table-column>

          <el-table-column label="入荷予定日" width="185">
            <el-table-column label="検品数量" width="185">
              <template slot-scope="scope">
                <div v-if="scope.row.arrival_plan_date != null">
                  {{ scope.row.arrival_plan_date.replace(/-/g, "/") }}
                </div>

                <div class="text-center" v-if="scope.row.arrival_plan_date == null">
                  <br />
                </div>

                <div
                  v-if="
                    qr_print == false &&
                    scope.row.order_quantity > scope.row.arrival_quantity
                  "
                >
                  <button
                    :disabled="scope.row.draft_flg == 1"
                    type="button"
                    class="btn btn-info"
                    v-on:click="minus_check(scope.$index)"
                  >
                    －
                  </button>
                  <div class="form-group">
                    <input
                      :disabled="scope.row.draft_flg == 1"
                      type="text"
                      class="form-control text-right"
                      v-model="scope.row.check_quantity"
                      size="3"
                    />
                  </div>
                  <button
                    :disabled="scope.row.draft_flg == 1"
                    type="button"
                    class="btn btn-info"
                    v-on:click="plus_check(scope.$index)"
                  >
                    ＋
                  </button>
                </div>

                <div
                  v-if="
                    !(
                      qr_print == false &&
                      scope.row.order_quantity > scope.row.arrival_quantity
                    )
                  "
                >
                  <br />
                </div>
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="QR印刷数" width="170">
            <template slot-scope="scope">
              <button
                v-show="scope.row.own_stock_flg != 1"
                :disabled="
                  scope.row.draft_flg == 1 ||
                  (scope.row.order_quantity <= scope.row.arrival_quantity &&
                    scope.row.check_quantity == 0) ||
                  !(scope.row.check_quantity > 0 && isSaved)
                "
                type="button"
                class="btn btn-info"
                v-on:click="minus_print(scope.$index)"
              >
                －
              </button>
              <div class="form-group">
                <input
                  v-show="scope.row.own_stock_flg != 1"
                  :disabled="
                    scope.row.draft_flg == 1 ||
                    (scope.row.order_quantity <= scope.row.arrival_quantity &&
                      scope.row.check_quantity == 0) ||
                    !(scope.row.check_quantity > 0 && isSaved)
                  "
                  type="text"
                  class="form-control text-right"
                  v-model="scope.row.print_number"
                  size="1"
                />
              </div>
              <button
                v-show="scope.row.own_stock_flg != 1"
                :disabled="
                  scope.row.draft_flg == 1 ||
                  (scope.row.order_quantity <= scope.row.arrival_quantity &&
                    scope.row.check_quantity == 0) ||
                  !(scope.row.check_quantity > 0 && isSaved)
                "
                type="button"
                class="btn btn-info"
                v-on:click="plus_print(scope.$index)"
              >
                ＋
              </button>
            </template>
          </el-table-column>
        </el-table>
        <!-- 入荷完了ダイアログ -->
        <el-dialog
          title="入荷処理完了"
          :visible.sync="processDialog"
          width="80%"
          :before-close="handleClose"
        >
          <div class="row">
            <div class="col-md-10">
              商品名：{{ this.product_name }}
              <br />
              他{{ this.tableData.length - 1 }}品 <br />の入荷処理が完了しました。
            </div>
          </div>
          <span slot="footer" class="dialog-footer">
            <el-button @click="dialogClose">確認</el-button>
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
              <el-button type="primary" @click="printQr" v-bind:disabled="!actPrintBtn"
                >完了</el-button
              >
            </div>
          </div>
        </el-dialog>
      </form>
    </div>
    <br />
    <div class="commnet-form commnet-body col-sm-12 col-md-12" id="commnetForm">
      <form
        id="commnetForm"
        name="commnetForm"
        class="form-horizontal"
        @submit.prevent="process"
      >
        <div class="row">
          <div class="col-md-6">
            １：入荷した商品を検索し、数量に変更があれば加重ボタンで修正のうえ決定ボタンを押してください。
          </div>
          <div class="col-md-2">
            <button
              type="button"
              :disabled="isSaved"
              class="btn btn-primary btn-save"
              v-on:click="process"
            >
              入荷確定
            </button>
          </div>
        </div>
        <div class="row">
          <br />
          <div class="col-md-6">
            ２：別商品を一つのQRコードで管理する場合は、チェックボックスで商品を指定して、個口数に応じて印刷数量を設定のうえ、QRまとめボタンを押して印刷してください。
          </div>
          <div class="col-md-6">
            <button type="button" class="btn btn-info" v-on:click="minus_collect()">
              －
            </button>
            <div class="form-group">
              <input type="text" class="form-control" v-model="collect_number" size="4" />
            </div>
            <button type="button" class="btn btn-info" v-on:click="plus_collect()">
              ＋
            </button>
            <button
              type="button"
              :disabled="qrFinish || !isSaved"
              class="btn btn-primary btn-save"
              v-on:click="print(true)"
            >
              QRまとめ
            </button>
            <button
              type="button"
              :disabled="!isQrCollected || qrFinish"
              class="btn btn-primary btn-save"
              v-on:click="rePrint()"
            >
              再印刷
            </button>
          </div>
        </div>
        <div class="row">
          <br />
          <div class="col-md-6">
            ３：QRコードをまとめる必要がない商品は、個口数によりQR印刷数の加重ボタンで個口数量を設定したうえ、QR印刷ボタンを押してください。
          </div>
          <div class="col-md-2">
            <button
              type="button"
              class="btn btn-primary btn-save"
              :disabled="!isSaved"
              v-on:click="print(false)"
            >
              QR印刷
            </button>
          </div>
        </div>
      </form>
    </div>
    <div class="col-md-12 col-sm-12 text-center">
      <br />
      <a class="btn btn-primary btn-save" @click="next">倉庫内移動</a>
      <a class="btn btn-primary btn-save" @click="back">戻る</a>
      <br />
      <br />
    </div>
  </div>
</template>

<script>
import { forEach } from "lodash-es";
export default {
  data: () => ({
    loading: false,
    searchParams: {
      customer_id: "",
      department_id: "",
      staff_id: "",
      matter_id: "",
      matter_name: "",
    },
    printQrList: null,
    errors: {
      check_quantity: "",
      print_number: "",
    },
    isSaved: false,
    tableData: [],
    tableBase: [],
    checkTable: [],
    rePrintList: [],
    multipleSelection: [],
    checkArrivalToday: false,
    checkArrivalNull: false,
    filProductCode: "",
    filProductName: "",
    urlparam: "",
    initFlg: true,
    printDialog: false,
    actPrintBtn: false,
    today: new Date(),
    order_no: "",
    baseInfo: Object,
    product_name: "",
    processDialog: false,
    qr_print: false,
    qrFinish: false,
    update_count: 0,
    collect_number: 0,
    isQrCollected: false,
  }),
  props: {},
   watch: {
      checkArrivalToday: function (val, oldVal) {
        this.filterTable();
      },
       checkArrivalNull: function (val, oldVal) {
        this.filterTable();
      },
       filProductCode: function (val, oldVal) {
        this.filterTable();
      },
       filProductName: function (val, oldVal) {
        this.filterTable();
      },
    },
  mounted: function () {
    // var currentUrl = location.href.split("?")[0];
    // var separatorDir = currentUrl.split("/");
    // this.order_no = separatorDir.pop();

    // var query = window.location.search;
    // if (query.length > 1) {
    //   // 検索条件セット
    //   this.setSearchParams(query, this.searchParams);
    //   // 検索
    //   this.search();
    // }

    this.checkTable = JSON.parse(localStorage.getItem("multipleSelection")) || [];
    // 検索
    this.search();
  },
  methods: {
    //テーブルフィルター
    filterTable(){

      var today = this.today.getFullYear() +
            "-" +
            ("00" + (this.today.getMonth() + 1)).slice(-2) +
            "-" +
            ("00" + this.today.getDate()).slice(-2);
      
      this.tableData = this.tableBase.filter(
        row =>
         (row.product_code.indexOf(this.filProductCode) > -1 || this.filProductCode == '') &&
         (row.product_name.indexOf(this.filProductName) > -1 || this.filProductName == '') &&
         (
           (!this.checkArrivalToday && !this.checkArrivalNull)||
           (this.checkArrivalToday && !this.checkArrivalNull && row.arrival_plan_date == today)||
           (!this.checkArrivalToday && this.checkArrivalNull && row.arrival_plan_date == null)||
           (this.checkArrivalToday && this.checkArrivalNull && row.arrival_plan_date == today && row.arrival_plan_date == null)
         ) 
      );
    },
    // 検索
    search() {
      this.loading = true;
      var params = {
        checkTable: this.checkTable,
      };
      axios
        .post("/arrival-process/search", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.tableData = response.data;
              this.tableBase = response.data;
              this.collect_number = 1;
              // 基本情報設定
              this.baseInfo.order_no = this.tableData[0].order_no;
              this.baseInfo.maker_name = this.tableData[0].maker_name;
              this.baseInfo.supplier_name = this.tableData[0].supplier_name;
              this.baseInfo.matter_no = this.tableData[0].matter_no;
              this.baseInfo.matter_name = this.tableData[0].matter_name;
              this.baseInfo.customer_name = this.tableData[0].customer_name;
              this.baseInfo.arrival_plan_date = this.tableData[0].arrival_plan_date;
              this.baseInfo.warehouse_name = this.tableData[0].warehouse_name;
              this.baseInfo.department_name = this.tableData[0].department_name;
              this.baseInfo.staff_name = this.tableData[0].staff_name;
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
    handleSelectionChange(val) {
      this.multipleSelection = val;
    },
    canSelectRow(row, index) {
      return (
        row.own_stock_flg != 1 &&
        !(
          row.draft_flg == 1 ||
          row.qr_collect_print === true ||
          (row.order_quantity <= row.arrival_quantity && row.check_quantity == 0) ||
          !(row.check_quantity > 0 && this.isSaved)
        )
      );
    },
    // 戻る
    back() {
      // QR印刷を行なっていない && 確認ダイアログで『キャンセル』
      var notPrintList = this.tableData.filter(
        (row) =>
          row.check_quantity > 0 && row.qr_prinf_flg !== 1 && row.own_stock_flg != 1
      );
      if (
        notPrintList != null &&
        notPrintList.length > 0 &&
        this.initFlg === false &&
        !this.confirm("QR印刷が行われていませんがよろしいですか？")
      ) {
        return false;
      }

      var listUrl = "/arrival-result" + window.location.search;
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // 倉庫内移動
    next() {
      // QR印刷を行なっていない && 確認ダイアログで『キャンセル』
      var notPrintList = this.tableData.filter(
        (row) =>
          row.check_quantity > 0 && row.qr_prinf_flg !== 1 && row.own_stock_flg != 1
      );
      if (
        notPrintList != null &&
        notPrintList.length > 0 &&
        this.initFlg === false &&
        !this.confirm("QR印刷が行われていませんがよろしいですか？")
      ) {
        return false;
      }

      var listUrl = "/shelf-move";
      window.onbeforeunload = null;
      location.href = listUrl;
    },

    tableRowClassName({ row, rowIndex }) {
      if (row.draft_flg == 1 || row.qr_collect_print) {
        return "draft-row";
      } else if (
        row.arrival_plan_date != null &&
        row.arrival_plan_date ==
          this.today.getFullYear() +
            "-" +
            ("00" + (this.today.getMonth() + 1)).slice(-2) +
            "-" +
            ("00" + this.today.getDate()).slice(-2)
      ) {
        return "arrival-row";
      }
    },
    // 処理ボタン
    process() {
      if (this.tableData.length == 0) {
        return;
      }

      var overFlg = false;
      this.tableData.forEach((element) => {
        if (
          !overFlg &&
          Number(element.order_quantity) - Number(element.arrival_quantity) <
            Number(element.check_quantity) &&
          Number(element.order_quantity) - Number(element.arrival_quantity) > 0
        ) {
          alert("入荷予定数を超えた入荷は不可です");
          overFlg = true;
        }
      });
      if (overFlg) {
        return false;
      }

      // エラーの初期化
      this.initErr(this.errors);

      this.loading = true;
      this.isSaved = true;
      this.product_name = this.tableData[0].product_name;
      axios
        .post("/arrival-process/save", this.tableData)

        .then(
          function (response) {
            if (response.data) {
              if (response.data.status) {
                // 成功
                this.update_count = 0;
                this.tableData = response.data.tabledata;
                for (var i = 0; i < this.tableData.length; i++) {
                  if (this.tableData[i].check_quantity != 0) {
                    this.update_count++;
                  }
                }
                if (this.update_count != 0) {
                  this.processDialog = true;
                }
                this.qr_print = true;
                this.initFlg = false;
              } else {
                location.reload();
              }
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
    },
    // 印刷ボタン
    print(collect) {
      if (this.tableData.length == 0) {
        return;
      }
      // エラーの初期化
      this.initErr(this.errors);

      this.loading = true;
      for (var i = 0; i < this.tableData.length; i++) {
        //QRまとめの場合
        if (collect) {
          this.tableData[i].collect_number = this.collect_number;
        } else {
          this.tableData[i].collect_number = 0;
        }

        //チェック状態の反映&同一発注番号チェック
        var orderId = null;
        var isError = false;
        this.multipleSelection.forEach((element) => {
          if (this.tableData[i].order_detail_id === element.order_detail_id) {
            this.tableData[i].check_value = true;
          }

          if (orderId == null) {
            orderId = element.order_id;
          } else if (orderId != element.order_id) {
            isError = true;
          }
        });
      }
      var table = null;
      var indexes = [];
      //まとめの場合
      if (collect) {
        if (orderId == null) {
          alert("対象のレコードを選択してください。");
          this.loading = false;
          return;
        }
        if (isError) {
          alert("複数の発注に対してQRまとめは行えません。");
          this.loading = false;
          return;
        }

        //QRまとめ印刷済みフラグ
        for (let index = 0; index < this.tableData.length; index++) {
          if (
            this.tableData[index].check_value == true &&
            this.tableData[index].check_quantity > 0 &&
            this.tableData[index].collect_number > 0 &&
            this.tableData[index].arrival_id !== null
          ) {
            this.tableData[index].qr_collect_print = true;
            indexes.push(index);
          }
        }

        //印刷対象
        table = this.tableData.filter(
          (row) =>
            row.check_value == true &&
            row.check_quantity > 0 &&
            row.collect_number > 0 &&
            row.arrival_id !== null
        );
        //再印刷用のテーブルに格納
        this.rePrintList = table;

        for (let index = 0; index < this.tableData.length; index++) {
          this.tableData[index].check_value = false;
        }
        this.$refs.multipleTable.clearSelection();
        this.isQrCollected = true;

        axios
          .post("/arrival-process/print", { tableData: table, collect: collect })

          .then(
            function (response) {
              this.loading = false;
              if (response.data == "printError") {
                alert("印刷エラーが発生しました。");
              } else if (response.data) {
                var urls = response.data;
                var pattern = "smapri:";
                urls.forEach((url) => {
                  if (url.indexOf(pattern) > -1) {
                    // iosの場合
                    window.location.href = url;
                  }
                });
                this.setPrintFlg(indexes);
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
      }
      //全体印刷の場合
      else {
        this.qrFinish = true;
        for (let index = 0; index < this.tableData.length; index++) {
          if (
            this.tableData[index].qr_collect_print !== true &&
            this.tableData[index].check_quantity > 0 &&
            this.tableData[index].arrival_id != null &&
            this.tableData[index].print_number > 0 &&
            this.tableData[index].own_stock_flg != 1
          ) {
            indexes.push(index);
          }
        }
        var table = this.tableData.filter(
          (row) =>
            row.qr_collect_print !== true &&
            row.check_quantity > 0 &&
            row.arrival_id != null &&
            row.print_number > 0 &&
            row.own_stock_flg != 1
        );

        this.printQrList = table;
        this.printQrList.forEach((element) => {
          element.qr_prinf_flg = 0;
        });

        this.loading = false;
        this.printQr();
      }
    },

    //全体印刷wait用処理
    printQr() {
      this.loading = true;

      this.actPrintBtn = false;
      this.printDialog = false;

      //印刷済みでないindex取得
      var index = 0;
      var notPrintIndex = null;
      this.printQrList.forEach((element) => {
        if (element.qr_prinf_flg !== 1 && notPrintIndex === null) {
          notPrintIndex = index;
        }
        index++;
      });

      if (notPrintIndex !== null) {
        axios
          .post("/arrival-process/print", {
            tableData: [this.printQrList[notPrintIndex]],
            collect: false,
          })

          .then(
            function (response) {
              this.loading = false;
              if (response.data == "printError") {
                alert("印刷エラーが発生しました。");
              } else if (response.data) {
                var urls = response.data;
                var pattern = "smapri:";
                urls.forEach((url) => {
                  if (url.indexOf(pattern) > -1) {
                    // iosの場合
                    window.location.href = url;
                  }
                });

                this.printQrList[notPrintIndex].qr_prinf_flg = 1;
                this.setPrintFlg([notPrintIndex]);

                //印刷済みでないindex取得
                index = 0;
                notPrintIndex = null;
                this.printQrList.forEach((element) => {
                  if (element.qr_prinf_flg !== 1) {
                    notPrintIndex = index;
                  }
                  index++;
                });

                if (notPrintIndex !== null) {
                  if (
                    !alert(
                      "QR印刷完了後に表示される発行完了画面の完了を押すと、次のQRが印刷されます。"
                    )
                  ) {
                    this.actPrintBtn = true;
                    this.printDialog = true;
                  }
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

    // 再印刷ボタン
    rePrint() {
      if (this.tableData.length == 0) {
        return;
      }
      // エラーの初期化
      this.initErr(this.errors);

      this.loading = true;

      // var indexes = [];
      // for (let index = 0; index < this.tableData.length; index++) {
      //   if (this.tableData[index].qr_collect_print === true) {
      //     indexes.push(index);
      //   }
      // }
      // var table = this.tableData.filter((row) => row.qr_collect_print === true);
      axios
        .post("/arrival-process/reprint", { tableData: this.rePrintList })

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
              // this.setPrintFlg(indexes);
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

    //印刷済みフラグセット
    setPrintFlg(indexes) {
      for (let index = 0; index < indexes.length; index++) {
        this.tableData[indexes[index]].qr_prinf_flg = 1;
      }
    },

    // 検品数量マイナス
    minus_check(index) {
      if (this.tableData[index].check_quantity == 0) {
        return;
      }
      this.tableData[index].check_quantity--;
    },
    // 検品数量プラス
    plus_check(index) {
      if (
        Number(this.tableData[index].check_quantity) <
        Number(this.tableData[index].order_quantity) -
          Number(this.tableData[index].arrival_quantity)
      ) {
        this.tableData[index].check_quantity++;
      }
    },
    // QR印刷数マイナス
    minus_print(index) {
      if (this.tableData[index].print_number == 0) {
        return;
      }
      this.tableData[index].print_number--;
    },
    // QR印刷数プラス
    plus_print(index) {
      this.tableData[index].print_number++;
    },
    // まとめ印刷数マイナス
    minus_collect() {
      if (this.collect_number == 0) {
        return;
      }
      this.collect_number--;
    },
    // まとめ印刷数プラス
    plus_collect() {
      this.collect_number++;
    },
    dialogClose() {
      for (var i = 0; i < this.tableData.length; i++) {
        this.tableData[i].arrival_quantity =
          Number(this.tableData[i].arrival_quantity) +
          Number(this.tableData[i].check_quantity);
      }
      this.processDialog = false;
      //this.search();
    },
    handleClose(done) {
      this.search();
      done();
    },
    confirm(msg) {
      var result = window.confirm(msg);
      return result;
    },
  },
};
</script>
<style>
.form-group {
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
.commnet-body {
  width: 100%;
  background: #ffffff;
  padding: 15px;
}

.el-table .draft-row {
  background: rgb(230, 230, 230);
}

.el-table .arrival-row {
  background-color: rgb(243, 255, 75);
}

.danger {
  background: #ff0000;
  color: #ffffff;
  padding: 5px;
}
</style>
