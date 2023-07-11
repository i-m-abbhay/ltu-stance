<template>
  <div>
    <!-- スキャン -->
    <br />
    <div class="row">
      <div class="col-sm-12 text-center">
        <button
          type="button"
          class="btn btn-primary"
          style="width:60%;height:70px;"
          @click="completedDelivery"
        >納品済みから返品</button>
      </div>
      <div class="col-sm-12 text-center">
        <br />
      </div>
      <div class="col-sm-12 text-center">在庫から返品</div>
      <div class="col-sm-12 text-center">
        <button
          type="button"
          class="btn btn-primary"
          style="width:60%;height:70px;"
          @click="qr"
        >QR返品</button>
        <br />
      </div>
      <div class="col-sm-12 text-center">
        <br />
      </div>
      <div class="col-sm-12 text-center">
        <button
          type="button"
          class="btn btn-primary"
          style="width:60%;height:70px;"
          @click="stock"
        >在庫返品</button>
        <br />
      </div>
    </div>
  </div>
</template>

<script>
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from "vue-qrcode-reader";
export default {
  components: { QrcodeStream, QrcodeDropZone, QrcodeCapture },
  data: () => ({
    loading: false,
    urlparam: "",
    numberDialog: false,
    processDialog: false,
    product_name: "",
    result: "",
    message: "",
    noStreamApiSupport: false,
    qr_code: "",
    qrInfo: []
  }),
  props: {},
  created: function() {},
  mounted: function() {},
  methods: {
    logErrors(promise) {
      promise.catch(console.error);
    },
    //納品済みからの返品
    completedDelivery() {
      this.loading = true;
      var listUrl = "/accepting-returns-matter-search";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    //QR返品
    qr() {
      this.loading = true;
      var listUrl = "/accepting-returns-qr";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    //在庫返品
    stock() {
      this.loading = true;
      var listUrl = "/accepting-returns-stock-search";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    logErrors(promise) {
      promise.catch(console.error);
    }
  }
};
</script>
<style>
.el-table .normal-row {
  background: rgb(255, 255, 255);
}

.el-table .loading-row {
  background: rgb(76, 217, 100);
}

.form-group {
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
</style>
