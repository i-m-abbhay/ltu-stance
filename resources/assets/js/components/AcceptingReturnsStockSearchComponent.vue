<template>
  <div>
    <br />

    <div class="col-xs-3 text-center" style="padding-top: 15px">
      <label>倉庫</label>
    </div>
    <div class="col-xs-9">
      <wj-auto-complete
        class="form-control"
        id="acproductName"
        search-member-path="id"
        display-member-path="warehouse_name"
        selected-value-path="id"
        :selected-index="-1"
        :is-required="false"
        :items-source="warehouseList"
        :max-items="warehouseList.length"
        :lost-focus="selectWarehouse"
      ></wj-auto-complete>
      <p class="text-danger">{{ errors.warehouse_id }}</p>
    </div>

    <div v-bind:class="{ 'has-error': errors.product_code != '' }">
      <div class="col-xs-3 text-center" style="padding-top: 15px; padding-left: 15px">
        <label>商品番号</label>
      </div>

      <div class="col-xs-9">
        <wj-auto-complete
          class="form-control"
          search-member-path="product_code"
          display-member-path="product_code"
          selected-value-path="product_code"
          :initialized="initProductCode"
          :is-required="false"
          :items-source-function="productCodeSourceFunction"
        >
        </wj-auto-complete>
        <p class="text-danger">{{ errors.product_code }}</p>
      </div>
    </div>
    <div v-bind:class="{ 'has-error': errors.product_name != '' }">
      <div class="col-xs-3 text-center" style="padding-top: 15px">
        <label>商品名</label>
      </div>
      <div class="col-xs-9">
        <wj-auto-complete
          class="form-control"
          search-member-path="product_name"
          display-member-path="product_name"
          selected-value-path="product_name"
          :initialized="initProductName"
          :is-required="false"
          :items-source-function="productItemsSourceFunction"
        >
        </wj-auto-complete>
        <p class="text-danger">{{ errors.product_name }}</p>
      </div>
    </div>
    <div class="col-xs-12 text-center">
      <br />
      <el-button type="primary" @click="process">検索</el-button>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    urlparam: "",
    tableData: [],
    warehouse_id: null,
    wjSearchObj: {
      productName: {},
      productCode: {},
    },
    errors: {
      warehouse_id: "",
      product_code: "",
      product_name: "",
    },
  }),
  props: {
    warehouseList: Array,
  },
  created: function () {},
  mounted: function () {},
  methods: {
    initProductName: function (sender) {
      this.wjSearchObj.productName = sender;
    },
    initProductCode: function (sender) {
      this.wjSearchObj.productCode = sender;
    },
    productItemsSourceFunction: function (text, maxItems, callback) {
      if (!text) {
        callback([]);
        return;
      }

      // サーバ通信中の場合
      if (this.wjSearchObj.productName.loadingFlg) {
        return;
      }

      if (text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_NAME_LENGTH) {
        return;
      }
      this.setASyncAutoCompleteList(
        this.wjSearchObj.productName,
        PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL,
        text,
        PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT,
        PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST,
        callback,
        this.getProductNameAutoCompleteFilterData
      );
    },
    productCodeSourceFunction: function (text, maxItems, callback) {
      if (!text) {
        callback([]);
        return;
      }

      // サーバ通信中の場合
      if (this.wjSearchObj.productCode.loadingFlg) {
        return;
      }

      if (text.length < PRODUCT_AUTO_COMPLETE_SETTING.SEARCH_PRODUCT_CODE_LENGTH) {
        return;
      }
      this.setASyncAutoCompleteList(
        this.wjSearchObj.productCode,
        PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL,
        text,
        PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT,
        PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST,
        callback,
        this.getProductCodeAutoCompleteFilterData
      );
    },

    logErrors(promise) {
      promise.catch(console.error);
    },
    //検索
    process() {
      if (this.warehouse_id == null || this.warehouse_id == "") {
        this.errors.warehouse_id = "必須です。";
        return;
      }
       if (this.wjSearchObj.productCode.text == "" && this.wjSearchObj.productName.text=="") {
        alert("商品番号もしくは商品名を入力してください。");
        return;
      }

      this.loading = true;

      var params = {warehouse_id:this.warehouse_id,
      product_code:this.wjSearchObj.productCode.text ,
      product_name: this.wjSearchObj.productName.text};
      axios
        .post("/accepting-returns-stock-search/search", params)

        .then(
          function (response) {
            if (response.data) {
              this.tableData = response.data;

              if (this.tableData.length > 0) {
                localStorage.setItem("tableData", JSON.stringify(this.tableData[0]));
                var listUrl = "/accepting-returns-info-input";
                window.onbeforeunload = null;
                location.href = listUrl;
              } else {
                alert("検索結果が存在しません。");
              }
            }

            this.loading = false;
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
    selectWarehouse: function (sender) {
      this.warehouse_id =
        sender.selectedValue !== undefined && sender.selectedValue !== null
          ? sender.selectedValue
          : "";
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
