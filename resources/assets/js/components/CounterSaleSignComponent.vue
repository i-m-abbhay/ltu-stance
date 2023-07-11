<template>
  <div id="app">
    <div class="col-xs-12 text-center">
      <br />
      <center style="margin: 10px">
        <FreeDrawing ref="draw" />
      </center>
    </div>
    <div class="col-xs-12 text-center">
      <br />
      <!-- <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button> -->
      <button type="button" class="btn btn-primary gold-back" v-on:click="clear">
        クリア
      </button>
      <button type="button" class="btn btn-primary btn-save" v-on:click="process">
        OK
      </button>
    </div>
    <br />
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
    tableData: [],
    beforeImg: null,
  }),
  mounted: function () {
    this.init();
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.beforeImg = this.$refs.draw.getImage();
  },
  methods: {
    init: function () {
      this.mode = this.defaultMode;
      this.brushColor = this.defaultBrushColor;
    },
    // // 戻る
    // back() {
    //   var listUrl = "/counter-sale-confirm";
    //   localStorage.setItem("tableData", JSON.stringify(this.tableData));
    //   window.onbeforeunload = null;
    //   location.href = listUrl;
    // },

    // クリア
    clear() {
      this.$refs.draw.clearCanvas();
      localStorage.setItem("sign", null);
    },
    // 実行
    process() {
      var listUrl = "/counter-sale-confirm";
      if (this.$refs.draw.getImage() != this.beforeImg) {
        localStorage.setItem("sign", JSON.stringify(this.$refs.draw.getImage()));
      } else {
        localStorage.setItem("sign", null);
      }
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      window.onbeforeunload = null;
      location.href = listUrl;
    },
  },
};
</script>

<style>
.gold-back {
  background: rgb(119, 117, 1);
  border-color: rgb(107, 105, 1);
}
.gold-back:active {
  background: rgb(90, 89, 0) !important ;
  border-color: rgb(83, 82, 1) !important ;
}
.gold-back:hover {
  background: rgb(148, 145, 0);
  border-color: rgb(134, 132, 0);
}
</style>
