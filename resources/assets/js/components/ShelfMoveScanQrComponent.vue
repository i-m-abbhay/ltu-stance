<template>
  <div>
    <loading-component :loading="loading" />
    <div class="col-xs-12">
      <div v-for="(object, index) in this.scanData" v-bind:key="index" class="col-xs-12">
        <div class="col-xs-12 col-sm-8">
          <label class="control-label" for="acDepartment">商品番号</label>
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].product_code"
            v-bind:readonly="true"
          />

          <label class="control-label" for="acDepartment">商品名</label>
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].product_name"
            v-bind:readonly="true"
          />

          <label
            class="control-label"
            for="acDepartment"
            v-if="scanData[index].matter_name != null"
            >案件名</label
          >
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].matter_name"
            v-bind:readonly="true"
            v-if="scanData[index].matter_name != null"
          />

          <label
            class="control-label"
            for="acDepartment"
            v-if="
              scanData[index].customer_name != null && scanData[index].customer_name != ''
            "
            >得意先</label
          >
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].customer_name"
            v-bind:readonly="true"
            v-if="
              scanData[index].customer_name != null && scanData[index].customer_name != ''
            "
          />

          <label class="control-label" for="acDepartment">在庫数量</label>
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].quantity"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">倉庫</label>
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].warehouse_name"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">棚</label>
          <input
            type="text"
            class="form-control"
            v-model="scanData[index].shelf_area"
            v-bind:readonly="true"
          />
        </div>
        <div class="col-xs-12">
          <br />
        </div>
        <div class="col-xs-12">
          <hr size="4" color="black" />
        </div>
        <div class="col-xs-12">
          <br />
        </div>
      </div>
    </div>

    <!-- スキャン -->
    <form
      id="searchForm"
      name="searchForm"
      class="form-horizontal"
      @submit.prevent="qrRead"
    >
      <div id="app" v-show="qr_read">
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
            <button type="button" class="btn btn-info" :disabled="isSaved" v-on:click="onDecode(qr_code)">
              QR手入力
            </button>
          </div>
        </div>
      </div>

      <br />
      <div class="col-xs-8 col-xs-offset-2">
        <br />
        <button type="submit" class="btn btn-info" style="width: 100%">
          棚QRスキャン
        </button>
      </div>
      <div class="col-xs-2"></div>
      <div class="col-xs-12">
        <br />
      </div>
    </form>
  </div>
</template>

<script>
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from "vue-qrcode-reader";
export default {
  components: { QrcodeStream, QrcodeDropZone, QrcodeCapture },
  data: () => ({
    loading: false,
    result: "",
    message: "",
    noStreamApiSupport: false,
    qr_code: "",
    qr_read: false,
    qrInfo: [],
    scanData: [],
    warehouse_id: 0,
    shelf_number_id: 0,
    shelf_area:"",
    isSaved:false
  }),
  props: {},
  created: function () {},
  mounted: function () {
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];

    this.loading = true;
    this.processDialog = false;

    axios
      .post("/shelf-move-scan-qr/search", this.qrInfo)

      .then(
        function (response) {
          this.loading = false;

          if (response.data) {
            this.scanData = response.data;
          } else {
            alert(MSG_ERROR);
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
  methods: {
    // QR読取り
    qrRead() {
      if (this.qr_read) {
        this.qr_read = false;
      } else {
        this.qr_read = true;
        this.result = "";
      }
    },
    //読取
    onDecode(result) {
      this.result = result;
      this.loading = true;
      var params = new URLSearchParams();
      params.append("shelfNumberId", this.result);
      axios
        .post("/shelf-move-scan-qr/search-shelf", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              var data = response.data;
              if (data.shelf_kind == 3) {
                alert("返品棚への移動はできません。")
                return
              }

              if (
                data.warehouse_id != null &&
                this.scanData[0].warehouse_id == data.warehouse_id && 
                this.scanData[0].shelf_number_id != data.id 
              ) {
                this.warehouse_id = data.warehouse_id;
                this.shelf_number_id = data.id;
                this.shelf_area = data.shelf_area;
                this.save();
              } else if (this.scanData[0].warehouse_id != data.warehouse_id) {
                alert("スキャンした棚QRが移動元の倉庫と異なります。");
              }else if (this.scanData[0].shelf_number_id == data.id) {
                alert("移動元、移動先が同一です。");
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
    save() {
              this.isSaved=true;

      axios
        .post("/shelf-move-scan-qr/save", {
          shelf_number_id: this.shelf_number_id,
          warehouse_id: this.warehouse_id,
          scanData: this.scanData,
        })
        .then( function (response) {
          if (response.data.status) {
            // if (response.data.message == "stockError") {
            //   alert("すでに同じ商品が自社倉庫に存在するため移動できません。");
            //   this.isSaved=false;

            // } else if (response.data.message == "stockNotExist") {
            //   alert("すでに同じ商品が自社倉庫に存在するため移動できません。");
            //   this.isSaved=false;

            // } else 
            // if (response.data) {
            // var data = response.data;
            this.loading = false;
            this.isSaved=true;
            alert(this.shelf_area + "に移動しました");

            var listUrl = "/shelf-move";
            window.onbeforeunload = null;
            location.href = listUrl;

            // } 
            
          } else {
            if (response.data.message == "stockError") {
              alert("すでに同じ商品が自社倉庫に存在するため移動できません。");
              this.isSaved=false;

            } else if (response.data.message == "stockNotExist") {
              alert("移動元の棚に在庫が存在しないため、移動できません。");
              this.isSaved=false;

            }
            // alert(MSG_ERROR);
            // location.reload();
          }
          this.loading = false;
        }.bind(this))
        .catch( function (error) {
            this.loading = false;

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
          }.bind(this)
        )
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
    },
  },
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
