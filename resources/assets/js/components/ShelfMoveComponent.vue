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
  props: {
    returnShelfList: Array,
  },
  created: function() {},
  mounted: function() {},
  methods: {
    //読取
    onDecode(result) {
      this.result = result;
      this.loading = true;
      this.qrInfo = [];
      var params = new URLSearchParams();

      //スキャンしたQRの長さで商品か棚を判定（棚の桁数の最大11より長ければ商品）
      var url = "/qr-split/search";
      if (this.result.length < 10) {
        url = "/shelf-move/search";
      }

      params.append("qr_code", this.result);
      axios
        .post(url, params)

        .then(
          function(response) {
            this.loading = false;

            if (response.data) {
              this.qrInfo = response.data;

              if (this.qrInfo.length > 0) {
                if (this.returnShelfList.indexOf(this.qrInfo[0].shelf_number_id) !== -1) {
                  // 返品棚だとエラー
                  alert("返品棚からの移動はできません。");
                } else {
                  //棚QRスキャン
                  if (this.result.length < 10) {
                      this.loading = true;
                      localStorage.setItem("qrInfo", JSON.stringify(this.qrInfo));
                      var listUrl = "/shelf-move-scan-shelf";
                      window.onbeforeunload = null;
                      location.href = listUrl;
                  }
                  //商品Qrスキャン
                  else {
                      this.loading = true;
                      localStorage.setItem("qrInfo", JSON.stringify(this.qrInfo));
                      var listUrl = "/shelf-move-scan-qr";
                      window.onbeforeunload = null;
                      location.href = listUrl;
                  }
                }
              }else{
                alert("スキャンしたQRは存在しません。");
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
    logErrors(promise) {
      promise.catch(console.error);
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
