<template>
  <div>
    <loading-component :loading="loading" />
    <div class="row">
      <div class="col-md-12 text-center">
        <FreeDrawing ref="draw" />
      </div>
    </div>
    <div class="row">
      <div class="col-md-12 text-center">
        <br />
        <button type="button" class="btn btn-warning btn-back" v-on:click="back">
          戻る
        </button>
        <button type="button" class="btn btn-primary btn-save" v-on:click="clear">
          クリア
        </button>
        <button type="button" class="btn btn-primary btn-save" v-bind:disabled="isSaved" v-on:click="process">
          実行
        </button>
        <br />
        <br />
      </div>
    </div>
    <!-- 納品書印刷ダイアログ -->
    <el-dialog
      title="納品書印刷"
      :visible.sync="showDlgDeliveryLabelPrint"
      :close-on-click-modal="false"
      :close-on-press-escape="false"
      :showClose="false"
    >
      <div class="row">
        <p class="control-label">登録が完了しました</p>
        <p class="control-label">納品書を印刷してください</p>
      </div>
      <div class="row">
        <div class="text-center">
          <el-button
            @click="deliveryLabelPrint"
            v-bind:disabled="actPrintBtn"
            class="btn btn-create btn-primary"
            >印刷</el-button
          >
        </div>
      </div>
      <span slot="footer" class="dialog-footer">
        <el-button @click="backHome" class="btn-cancel">Home</el-button>
      </span>
    </el-dialog>
  </div>
</template>

<script>
import FreeDrawing from "./DeliveryDrawComponent.vue";
export default {
  name: "CallCanvas",
  components: {
    FreeDrawing,
  },
  data: () => ({
    mode: "",
    brushColor: "",
    defaultMode: "brush",
    defaultBrushColor: "#FFFFFF",
    canvasImage: null,
    searchParams: {
      order_no: "",
    },
    tableData: [],
    captures: [],
    deliveryIds: [],
    showDlgDeliveryLabelPrint: false,
    actPrintBtn: false,
    loading: false,
    isSaved: false,
  }),
  mounted: function () {
    this.init();
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.captures = JSON.parse(localStorage.getItem("captures")) || [];
    var query = window.location.search;
    if (query.length > 1) {
      // 検索条件セット
      this.setSearchParams(query, this.searchParams);
    }

    this.search()
  },
  methods: {
    init: function () {
      this.mode = this.defaultMode;
      this.brushColor = this.defaultBrushColor;
    },
    search() {
      this.loading = true
      var params = new URLSearchParams();

      params.append('tableData', JSON.stringify(this.tableData));

      axios.post('/purchase-delivery-sign/search', params)
      .then( function (response) {
          if (!response.data.status) {
            // 既に納品済みの場合エラー
            alert(response.data.message.replace(/\\n/g, '\n'))
            var url = "/purchase-delivery-search";
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
    // 納品書印刷
    deliveryLabelPrint() {
      this.actPrintBtn = true;
      this.loading = true;
      axios
        .post("/purchase-delivery-sign/print", { deliveryIds: this.deliveryIds })
        .then(
          function (response) {
            this.loading = false;
            if (response.data) {
              if (this.rmUndefinedBlank(response.data.url) != "") {
                window.location.href = response.data.url;
              }
              this.actPrintBtn = false;
            } else {
              // 失敗
              this.loading = false;
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
    // ホームへ戻る
    backHome() {
      var listUrl = "/purchase-delivery-search";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // 戻る
    back() {
      var listUrl = "/purchase-delivery-photo?order_no=" + this.searchParams.order_no;
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // クリア
    clear() {
      this.$refs.draw.clearCanvas();
    },
    // 実行
    process() {
      this.loading = true;
      this.isSaved = true;
      axios
        .post("/purchase-delivery-sign/save", {
          tableData: this.tableData,
          captures: this.captures,
          sign: this.$refs.draw.getImage(),
        })

        .then(
          function (response) {
            if (response.data) {
              this.deliveryIds = response.data;
              this.loading = false;
              this.showDlgDeliveryLabelPrint = true;
            } else {
              // 失敗
              this.loading = false;
              window.onbeforeunload = null;
              alert(MSG_ERROR);
              this.isSaved = false;
              location.reload();
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            this.loading = false;
            this.isSaved = false;
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
  },
};
</script>

<style>
.btn-create {
  width: 50%;
  color: white;
  text-align: center;
  background-color: #4d00bb !important;
}
</style>
