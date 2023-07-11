<template>
  <div>
    <!-- スキャン -->
    <br />
    <div class="search-form" id="searchForm">
      <div class="col-md-12 text-center">分割するQRを</div>
      <div class="col-md-12 text-center">スキャンしてください</div>
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

    <!-- 確認画面 -->
    <el-dialog
      title="分割モード選択"
      :visible.sync="processDialog"
      width="80%"
      :before-close="handleClose"
    >
      <div class="row">
        <div class="col-sm-12 text-center">
          <div class="col-sm-12">商品単位に分割する</div>
          <button
            type="button"
            class="btn btn-primary"
            style="width:60%;height:100px;"
            @click="productSplit"
          >商品分割</button>
        </div>
        <div class="col-sm-12 text-center">
          <br />
        </div>
        <div class="col-sm-12 text-center">
          <div class="col-sm-12">数量を指定して分割する</div>
          <button
            type="button"
            class="btn btn-primary"
            style="width:60%;height:100px;"
            @click="quantityDesignation"
          >数量指定分割</button>
          <br />
        </div>
      </div>
    </el-dialog>
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
        .post("/qr-split/search", params)

        .then(
          function(response) {
            this.loading = false;

            if (response.data) {
              this.qrInfo = response.data;

              //単商品
              if (this.qrInfo.length == 1) {
                this.loading = true;
                localStorage.setItem("qrInfo", JSON.stringify(this.qrInfo));
                var listUrl = "/qr-split-single-product";
                window.onbeforeunload = null;
                location.href = listUrl;
              }
              //複数商品
              else if (this.qrInfo.length > 1) {
                this.processDialog = true;
              }else{
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
     handleClose(done) {
      done();
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
