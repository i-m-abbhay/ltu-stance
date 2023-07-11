<template>
  <div>
    <!-- スキャン -->
    <br />
    <div class="search-form" id="searchForm">
      <div id="app">
        <p class="message">{{ message }}</p>
        <p class="decode-result">
          QRコード:
          <b>{{ result }}</b>
        </p>
        <qrcode-drop-zone @decode="onDecode" @init="logErrors">
          <qrcode-stream @decode="onDecode" @init="onInit" />
        </qrcode-drop-zone>

        <qrcode-capture v-if="noStreamApiSupport" @decode="onDecode" />

        <div class="row">
          <div class="col-md-6">
            <div class="form-group">
              <input type="tel" class="form-control" v-model="qr_code" />
            </div>
            <button type="button" class="btn btn-info" v-on:click="onDecode(qr_code)">QR手入力</button>
          </div>
        </div>
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
    //読取
    onDecode(result) {
      this.result = result;
      this.loading = true;
      this.qrInfo = [];
      var params = new URLSearchParams();
      params.append("qr_code", this.result);
      axios
        .post("/accepting-returns-qr/search", params)

        .then(
          function(response) {
            this.loading = false;

            if (response.data) {
              this.qrInfo = response.data;

              //単商品
              if (this.qrInfo.length == 1) {
                this.loading = true;
                localStorage.setItem("tableData", JSON.stringify(this.qrInfo[0]));
                var listUrl = "/accepting-returns-info-input";
                window.onbeforeunload = null;
                location.href = listUrl;
              }
              //複数商品
              else if (this.qrInfo.length > 1) {
                alert('QRに複数の商品が含まれています。');
              } else {
                alert("スキャンしたQRコードは存在しません。");
              }
            }
          }.bind(this)
        )
        .catch(
          function(error) {
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
    //商品分割
    productSplit() {
      this.loading = true;
      localStorage.setItem("qrInfo", JSON.stringify(this.qrInfo));
      var listUrl = "/qr-split-product-split";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    //数量指定分割
    quantityDesignation() {
      this.loading = true;
      localStorage.setItem("qrInfo", JSON.stringify(this.qrInfo));
      var listUrl = "/qr-split-quantity-designation";
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
