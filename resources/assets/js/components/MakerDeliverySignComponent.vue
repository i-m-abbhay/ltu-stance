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
        <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
        <button type="button" class="btn btn-primary btn-save" v-on:click="clear">書き直し</button>
        <button type="button" class="btn btn-primary btn-save" v-bind:disabled="isSaved" v-on:click="process">OK</button>
        <br />
        <br />
      </div>
      <br />
    </div>
  </div>
</template>

<script>
import FreeDrawing from "./DeliveryDrawComponent.vue";
export default {
  name: "CallCanvas",
  components: {
    FreeDrawing
  },
  data: () => ({
    loading: false,
    isSaved: false,
    mode: "",
    brushColor: "",
    defaultMode: "brush",
    defaultBrushColor: "#FFFFFF",
    canvasImage: null,
    tableData: []
  }),
  mounted: function() {
    this.init();
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
  },
  methods: {
    init: function() {
      this.mode = this.defaultMode;
      this.brushColor = this.defaultBrushColor;
    },
    // 戻る
    back() {
      var listUrl = "/maker-delivery-confirmation";
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
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
      // this.isSaved = true;
      var listUrl = "/maker-delivery-confirmation";
      localStorage.setItem("sign", JSON.stringify(this.$refs.draw.getImage()));
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      window.onbeforeunload = null;
      location.href = listUrl;
    }
  }
};
</script>
