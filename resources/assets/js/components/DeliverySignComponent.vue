<template>
  <div id="app">
    <loading-component :loading="loading" />
    <div class="row">
      <div class="col-md-12 text-center">
        <FreeDrawing ref="draw" />
      </div>
    </div>
    <div class="row">
      <div class="col-md-12 text-center">
        <br />
        <button
          type="button"
          class="btn btn-warning btn-back"
          v-on:click="back"
        >
          戻る
        </button>
        <button
          type="button"
          class="btn btn-primary btn-save"
          v-on:click="clear"
        >
          クリア
        </button>
        <button
          type="button"
          class="btn btn-primary btn-save"
          v-on:click="process"
          v-bind:disabled="isSaved"
        >
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
      :closeOnClickModal="false"
      :closeOnPressEscape="false"
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
    loading: false,
    isSaved: false,

    mode: "",
    brushColor: "",
    defaultMode: "brush",
    defaultBrushColor: "#FFFFFF",
    canvasImage: null,
    tableData: [],
    scanData: [],
    showDlgDeliveryLabelPrint: false,
    actPrintBtn: false,
  }),
  mounted: function () {
    this.init();
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.scanData = JSON.parse(localStorage.getItem("scanData")) || [];
  },
  methods: {
    init: function () {
      this.mode = this.defaultMode;
      this.brushColor = this.defaultBrushColor;
    },
    // 納品書印刷
    deliveryLabelPrint() {
      this.actPrintBtn = true;
      var params = new URLSearchParams();

      params.append("tableData", JSON.stringify(this.tableData));

      axios
        .post("/delivery-sign/print", params)
        .then(
          function (response) {
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
      var listUrl = "/delivery-list";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // 戻る
    back() {
      var listUrl = "/delivery-photo";
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
        .post("/delivery-sign/save", {
          tableData: this.tableData,
          scanData: this.scanData,
          sign: this.$refs.draw.getImage(),
        })

        .then(
          function (response) {
            if (response.data) {
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
