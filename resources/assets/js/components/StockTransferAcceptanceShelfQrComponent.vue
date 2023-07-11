<template>
  <div>
    <div class="col-md-12 text-center">
      <button type="submit" class="qr-button" @click="qrRead">
        <p>棚QRをスキャンしてください</p>
        <svg class="svg-icon">
          <use width="45.088" height="45.088" xlink:href="#qrIcon" />
        </svg>
      </button>
      <br />
    </div>

    <div v-show="qr_read">
      <p class="message">{{ message }}</p>
      <p class="decode-result">
        QRコード:
        <b>{{ shelfNumberId }}</b>
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
          <button type="button" class="btn btn-info" v-on:click="onDecode(qr_code)">QR手入力</button>
        </div>

        <div class="col-md-12">倉庫：{{wareHouseName}}</div>
        <div class="col-md-12">棚：{{shelfArea}}</div>
      </div>
    </div>

    <div class="col-md-12 text-center">
      <br />
      <a class="btn btn-primary" @click="back">戻る</a>
      <a class="btn btn-primary" @click="process">決定</a>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    searchedWareHouseId: null,
    shelfNumberId: null,
    wareHouseId: null,
    return_flg: null,
    wareHouseName: "",
    shelfArea: "",
    message: "",
    noStreamApiSupport: false,
    qr_read: false,
    paused: false,
    tableData: [],
    scanData: [],
    qr_code: ""
  }),

  props: {},
  created: function() {},
  mounted: function() {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.scanData = JSON.parse(localStorage.getItem("scanData")) || [];
    this.return_flg = JSON.parse(localStorage.getItem("return_flg"));
    this.searchedWareHouseId = JSON.parse(
      localStorage.getItem("searchedWareHouseId")
    );
  },
  methods: {
    onDecode(result) {
      this.shelfNumberId = result;
      this.loading = true;
      var params = new URLSearchParams();
      //移動先倉庫ID取得
      params.append("shelfNumberId", this.shelfNumberId);
      axios
        .post("/stock-transfer-acceptance-shelf-qr/search", params)

        .then(
          function(response) {
            this.loading = false;

            if (response.data) {
              var data = response.data;

              if (data["warehouse_id"] != null) {
                
                if(this.return_flg && data["shelf_kind"] != 3){
                  alert("スキャンしたQRは返品棚ではありません。")
                } else if (!this.return_flg && data["shelf_kind"] == 3) {
                  // 倉庫移管受入、荷積受入は返品棚不可
                  alert("返品棚への移動はできません。")
                }
                else if (
                  data["warehouse_id"] == this.searchedWareHouseId ||
                  this.searchedWareHouseId == null ||
                  this.searchedWareHouseId == 0
                ) {
                  this.wareHouseId = data["warehouse_id"];
                  this.wareHouseName = data["warehouse_name"];
                  this.shelfArea = data["shelf_area"];
                } else {
                  alert("異なる倉庫への受入はできません。");
                }
              } else {
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
    // QR読取り
    qrRead() {
      if (this.qr_read) {
        this.qr_read = false;
      } else {
        this.qr_read = true;
        this.shelfNumberId = "";
      }
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    // 戻る
    back() {
      var listUrl = "/stock-transfer-acceptance";
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

    process() {
      if ((this.shelfNumberId !=null && this.wareHouseId != null)) {
        this.loading = true;
        localStorage.setItem("tableData", JSON.stringify(this.tableData));
        localStorage.setItem("scanData", JSON.stringify(this.scanData));
        localStorage.setItem(
          "shelfNumberId",
          JSON.stringify(this.shelfNumberId)
        );

        localStorage.setItem("wareHouseId", JSON.stringify(this.wareHouseId));
        localStorage.setItem(
          "wareHouseName",
          JSON.stringify(this.wareHouseName)
        );
        localStorage.setItem("shelfArea", JSON.stringify(this.shelfArea));

        var listUrl = "/stock-transfer-acceptance-check";
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    }
  }
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
.form-group {
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
</style>
