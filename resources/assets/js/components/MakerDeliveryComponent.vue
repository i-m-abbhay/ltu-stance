<template>
  <div class>
    <div class="col-md-3">
      <label class="control-label">メーカー</label>
      <wj-auto-complete
        class="form-control"
        id="acMaker"
        search-member-path="id"
        display-member-path="supplier_name"
        :items-source="makerlist"
        :selected-value-path="id"
        :selected-index="-1"
        :max-length="makerlist.length"
        :lost-focus="selectMaker"
      ></wj-auto-complete>
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
              :class="backColor(scope.row.qr_code)"
              style="width: 60px; text-align: center"
            >
              <font align="center" color="white">{{ scope.$index + 1 }}</font>
            </div>
          </template>
        </el-table-column>
        <el-table-column label="商品番号" width="300">
          <el-table-column label="商品名/形式・規格" width="300">
            <template slot-scope="scope">
              {{ scope.row.product_code }}
              <br />
              {{ scope.row.product_name }} / {{ scope.row.model }}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="返品数量" width="100">
          <el-table-column label="単位" width="100">
            <template slot-scope="scope">
              {{ scope.row.return_quantity }}
              <br />
              {{ scope.row.unit }}
            </template>
          </el-table-column>
        </el-table-column>
      </el-table>
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
      maker_id: 0,
    },
    id: null,
    selectedMakerList: null,
    tableData: [],
    qr_code: "",
    scanData: [],
  }),

  props: {
    makerlist: Array,
  },
  created: function () {},
  mounted: function () {},
  methods: {
    search() {
      this.loading = true;

      if (this.selectedMakerList != null && this.selectedMakerList.text == "") {
        this.searchParams.maker_id = null;
      }
      var params = new URLSearchParams();
      params.append("maker_id", this.searchParams.maker_id);
      axios
        .post("/maker-delivery/search", params)

        .then(
          function (response) {
            if (response.data) {
              this.tableData = response.data;
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

              for (var i = 0; i < qrInfo.length; i++) {
                var isDuplication = false; //QR重複読込フラグ

                //スキャン済みQRはスルー
                for (var j = 0; j < this.scanData.length; j++) {
                  if (qrInfo[i].detail_id == this.scanData[j].detail_id) {
                    isDuplication = true;
                    break;
                  }
                }

                if (isDuplication) {
                  continue;
                }

                //QRで比較
                for (var j = 0; j < this.tableData.length; j++) {
                  if (this.tableData[j].qr_code == qrInfo[i].qr_code) {
                    //既にスキャンされている場合次の行へ
                    if (
                      this.tableData[j].return_quantity ==
                      this.tableData[j].input_quantity
                    ) {
                      continue;
                    }

                    //実績数追加
                    var v = this.tableData[j].return_quantity;

                    this.tableData.splice(j, 1, {
                      ...this.tableData[j],
                      input_quantity: v,
                    });

                    //スキャン済みQRリストに追加
                    this.scanData.push({
                      qr_code: qrInfo[i].qr_code,
                    });

                    break;
                  }
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
      if (row.return_quantity == row.input_quantity) {
        return "acceptance-row";
      } else {
        return "normal-row";
      }
    },
    backColor(qr_code) {
      if (qr_code == null || qr_code == "") {
        return "black";
      } else {
        return "red";
      }
    },
    handleClick(row) {
      if (
        row.input_quantity == 0 ||
        (row.input_quantity != 0 && window.confirm("選択を解除しますか？"))
      ) {
        //在庫品の場合
        if (row.qr_code == null || row.qr_code == "") {
          if (row.input_quantity == 0) {
            row.input_quantity = row.return_quantity;
          } else {
            row.input_quantity = 0;
          }
        }
        //QRの場合
        else {
          if (row.input_quantity != 0) {
            //同じQRの行は全てOFF
            for (var i = 0; i < this.tableData.length; i++) {
              if (this.tableData[i].qr_code == row.qr_code) {
                this.tableData.splice(i, 1, {
                  ...this.tableData[i],
                  input_quantity: 0,
                });
              }
            }
            //スキャン済みQR配列から対象QRを全て削除
            this.scanData = this.scanData.filter((d) => !(d.qr_code == row.qr_code));
          }
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

    process() {
      var returnCnt = 0;
      for (var i = 0; i < this.tableData.length; i++) {
        returnCnt += this.tableData[i].input_quantity;
      }
      if (returnCnt <= 0) {
        return;
      }

      this.loading = true;
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      localStorage.setItem("sign", null);
      var listUrl = "/maker-delivery-confirmation";
      window.onbeforeunload = null;
      location.href = listUrl;
    },

    selectMaker: function (sender) {
      var item = sender.selectedItem;
      this.selectedMakerList = sender;

      if (item != null) {
        this.searchParams.maker_id =
          item.id !== undefined && item.id !== null ? item.id : "";
        //this.search();
      }
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
