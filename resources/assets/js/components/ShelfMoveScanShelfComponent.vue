<template>
  <div>
    <div class="col-xs-12">
      <div class="col-xs-4 text-center">
        <br />
        <button
          type="button"
          class="btn btn-info"
          style="width: 100%"
          v-on:click="allCheck()"
        >
          全選択
        </button>
      </div>
       <div class="col-xs-4 text-center">
        <br />
        <button
          type="button"
          class="btn btn-info"
          style="width: 100%"
          v-on:click="allClear()"
        >
          選択クリア
        </button>
      </div>

      <div class="col-xs-4 text-center">
        <br />
        <button
          type="button"
          class="btn btn-info"
          style="width: 100%"
          v-on:click="next()"
        >
          次へ
        </button>
        <br />
      </div>
      <div class="col-xs-12 text-center">
        <br />
      </div>

      <div v-for="(object, index) in this.qrInfo" v-bind:key="index" class="col-xs-12">
        <div class="col-xs-12 col-sm-8">
          <label class="control-label" for="acDepartment">商品番号</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].product_code"
            v-bind:readonly="true"
          />

          <label class="control-label" for="acDepartment">商品名</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].product_name"
            v-bind:readonly="true"
          />

          <label
            class="control-label"
            for="acDepartment"
            v-if="qrInfo[index].matter_name != null"
            >案件名</label
          >
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].matter_name"
            v-bind:readonly="true"
            v-if="qrInfo[index].matter_name != null"
          />

          <label
            class="control-label"
            for="acDepartment"
            v-if="
              qrInfo[index].customer_name != null && qrInfo[index].customer_name != ''
            "
            >得意先</label
          >
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].customer_name"
            v-bind:readonly="true"
            v-if="
              qrInfo[index].customer_name != null && qrInfo[index].customer_name != ''
            "
          />

          <label class="control-label" for="acDepartment">在庫数量</label>
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].quantity"
            v-bind:readonly="true"
          />
          <label
            class="control-label"
            for="acDepartment"
            v-if="qrInfo[index].qr_code != null"
            >QR番号</label
          >
          <input
            type="text"
            class="form-control"
            v-model="qrInfo[index].qr_code"
            v-bind:readonly="true"
            v-if="qrInfo[index].qr_code != null"
          />

          <div class="col-xs-12 text-center" v-if="qrInfo[index].qr_code == null">
            <br />
            <el-checkbox
              v-model="qrInfo[index].check_box"
              :true-label="1"
              :false-label="0"
              >選択</el-checkbox
            >
          </div>

          <div class="col-xs-6 text-center" v-if="qrInfo[index].qr_code != null">
            <br />
            <el-checkbox
              v-model="qrInfo[index].check_box"
              :true-label="1"
              :false-label="0"
              >選択</el-checkbox
            >
          </div>

          <div class="col-xs-6 text-center" v-if="qrInfo[index].qr_code != null">
            <br />
            <button
              type="button"
              class="btn btn-info"
              style="width: 100%"
              v-on:click="print(qrInfo[index].qr_code)"
            >
              QR印刷
            </button>
          </div>
          <div class="col-xs-12 text-center">
            <br />
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    qrInfo: [],
  }),
  props: {},
  created: function () {},
  mounted: function () {
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];
  },
  methods: {
    // 実行
    next() {
      this.loading = true;
      var data = this.qrInfo.filter((row) => row.check_box == true);
      if (data != null && data.length > 0) {
        localStorage.setItem("qrInfo", JSON.stringify(data));
        var listUrl = "/shelf-move-scan-qr";
        window.onbeforeunload = null;
        location.href = listUrl;
      } else {
        alert("移動する在庫を選択してください。");
      }
    },
    allCheck() {
      this.qrInfo.forEach((element) => {
        element.check_box = true;
      });
    },
     allClear() {
      this.qrInfo.forEach((element) => {
        element.check_box = false;
      });
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    // 実行
    print(qr_code) {
      this.loading = true;
      axios
        .post("/shelf-move-scan-shelf/print", { qr_code: qr_code })
        .then(
          function (response) {
            this.loading = false;

            if (response.data == "printError") {
              alert("印刷エラーが発生しました。");
            } else if (response.data) {
              var url = response.data;
              var pattern = "smapri:";
              if (url.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = url;
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
    logErrors(promise) {
      promise.catch(console.error);
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
