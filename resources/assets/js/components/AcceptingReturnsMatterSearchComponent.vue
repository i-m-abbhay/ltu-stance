<template>
  <div>
    <br />

    <div class="form-group">
      <div v-bind:class="{ 'has-error': errors.matter_no != '' }">
        <div class="col-xs-3 text-center" style="padding-top: 15px">
          <label>案件番号</label>
        </div>

        <div class="col-xs-9">
          <wj-auto-complete
            class="form-control"
            id="acMatterNo"
            search-member-path="matter_no"
            display-member-path="matter_no"
            selected-value-path="matter_no"
            :is-required="false"
            :items-source="matterList"
            :selected-value="matter_no"
            :selectedIndexChanged="selectMatterNo"
            :max-items="matterList.length"
          ></wj-auto-complete>
          <p class="text-danger">{{ errors.matter_no }}</p>
        </div>
      </div>
      <div v-bind:class="{ 'has-error': errors.matter_name != '' }">
        <div class="col-xs-3 text-center" style="padding-top: 15px; padding-left: 15px">
          <label>案件名</label>
        </div>
        <div class="col-xs-9">
          <wj-auto-complete
            class="form-control"
            id="acMatterName"
            search-member-path="matter_name"
            display-member-path="matter_name"
            selected-value-path="matter_name"
            :selected-index="-1"
            :is-required="false"
            :items-source="matterList"
            :selected-value="matter_name"
            :selectedIndexChanged="selectMatterName"
            :max-items="matterList.length"
          ></wj-auto-complete>
          <p class="text-danger">{{ errors.matter_name }}</p>
        </div>
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

      <div class="col-xs-3 text-center" style="padding-top: 15px">
        <label>工事区分</label>
      </div>
      <div class="col-xs-9">
        <wj-auto-complete
          class="form-control"
          id="acproductName"
          search-member-path="id"
          display-member-path="construction_name"
          selected-value-path="id"
          :selected-index="-1"
          :is-required="false"
          :items-source="constructionList"
          :lost-focus="selectConstructionId"
          :max-items="constructionList.length"
        ></wj-auto-complete>
        <p class="text-danger">{{ errors.product_name }}</p>
      </div>
      <div class="col-xs-3 text-center" style="padding-top: 15px">
        <label>納品日</label>
      </div>
      <div class="col-xs-9">
        <input type="date" class="form-control" name="date1" v-model="delivery_date" />
      </div>

      <div class="col-xs-12 text-center">
        <br />
        <el-button type="primary" @click="process">検索</el-button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    urlparam: "",
    tableData: [],
    delivery_date: null,
    product_name: "",
    matter_id: null,
    matter_no: null,
    matter_name: null,
    product_id: null,
    product_code: null,
    product_name: null,
    construction_id: null,
    wjSearchObj: {
      productName: {},
      productCode: {},
    },
    errors: {
      matter_no: "",
      matter_name: "",
      product_code: "",
      product_name: "",
    },
  }),
  props: {
    matterList: Array,
    constructionList: Array,
  },
  created: function () {},
  mounted: function () {
    var today = new Date();
    today.setDate(today.getDate());
    var yyyy = today.getFullYear();
    var mm = ("0" + (today.getMonth() + 1)).slice(-2);
    var dd = ("0" + today.getDate()).slice(-2);
    this.delivery_date = yyyy + "-" + mm + "-" + dd;
  },
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

    selectMatterNo: function (sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.customer_id = item.customer_id;
        this.matter_id = item.id;
        this.matter_no = item.matter_no;
        this.matter_name = item.matter_name;
        this.errors.matter_no = "";
        this.errors.matter_name = "";
      } else {
        this.customer_id = null;
        this.matter_id = null;
        this.matter_no = null;
        this.matter_name = null;
      }
    },
    selectMatterName: function (sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.customer_id = item.customer_id;
        this.matter_id = item.id;
        this.matter_no = item.matter_no;
        this.matter_name = item.matter_name;
        this.errors.matter_no = "";
        this.errors.matter_name = "";
      } else {
        this.customer_id = null;
        this.matter_id = null;
        this.matter_no = null;
        this.matter_name = null;
      }
    },

    selectConstructionId: function (sender) {
      this.construction_id =
        sender.selectedValue !== undefined && sender.selectedValue !== null
          ? sender.selectedValue
          : "";
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    //検索
    process() {
      if (this.matter_no == null || this.matter_no == "") {
        this.errors.matter_no = "必須です。";
        return;
      }

      if (this.matter_name == null || this.matter_name == "") {
        this.errors.matter_name = "必須です。";
        return;
      }

      this.loading = true;

      // this.loading = true;
      // localStorage.setItem("matter_id", JSON.stringify(this.matter_id));
      // localStorage.setItem(
      //   "product_id",
      //   JSON.stringify(this.wjSearchObj.product.selectedValue)
      // );
      // localStorage.setItem("construction_id", JSON.stringify(this.construction_id));

      var params = {
        warehouse_id: this.warehouse_id,
        matter_id: this.matter_id,
        construction_id: this.construction_id,
        delivery_date: this.delivery_date,
        product_code: this.wjSearchObj.productCode.text,
        product_name: this.wjSearchObj.productName.text,
      };
      // var params = new URLSearchParams();
      // params.append("matter_id", this.matter_id);
      // params.append("product_id", this.wjSearchObj.product.selectedValue);
      // params.append("construction_id", this.construction_id);
      // params.append("delivery_date", this.delivery_date);
      axios
        .post("/accepting-returns-matter-search/search", params)

        .then(
          function (response) {
            if (response.data) {
              this.tableData = response.data;

              if (this.tableData.length > 0) {
                localStorage.setItem("tableData", JSON.stringify(this.tableData));
                var listUrl = "/accepting-returns-delivery-list";
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
