<template>
  <div class="col-xs-12 text-center">
    <loading-component :loading="loading" />
    <br />
    <div class="col-xs-12">
      <label>追加する商材を登録する</label>
    </div>

    <div class="form-group">
      <div v-bind:class="{ 'has-error': errors.supplier_name != '' }">
        <div class="col-xs-3" style="padding-top: 15px">
          <div>メーカー名</div>
        </div>

        <div class="col-xs-9">
          <wj-auto-complete
            class="form-control"
            id="acWarehouse"
            search-member-path="id"
            display-member-path="supplier_name"
            :items-source="makerList"
            selected-value-path="id"
            :selected-index="-1"
            :initialized="initMaker"
            :max-length="makerList.length"
          ></wj-auto-complete>
          <p class="text-danger">{{ errors.supplier_name }}</p>
        </div>
      </div>

      <div v-bind:class="{ 'has-error': errors.product_code != '' }">
        <div class="col-xs-3" style="padding-top: 15px">
          <div>商品番号</div>
        </div>

        <div class="col-xs-9">
          <wj-auto-complete
            class="form-control"
            id="acproductNo"
            search-member-path="product_code"
            display-member-path="product_code"
            selected-value-path="product_code"
            :initialized="initProductCode"
            :is-required="false"
            :selected-value="product_code"
            :selectedIndexChanged="changeProductCode"
            :items-source-function="productCodeItemsSourceFunction"
            :items-source-changed="changeCodeItems"
          ></wj-auto-complete>
          <p class="text-danger">{{ errors.product_code }}</p>
        </div>
      </div>
      <div v-bind:class="{ 'has-error': errors.product_name != '' }">
        <div class="col-xs-3" style="padding-top: 15px">
          <div>商品名</div>
        </div>
        <div class="col-xs-9">
          <wj-auto-complete
            class="form-control"
            search-member-path="product_name"
            display-member-path="product_name"
            selected-value-path="product_code"
            :initialized="initProductName"
            :selected-value="product_code"
            :selectedIndexChanged="changeProductName"
            :is-required="false"
            :items-source-function="productItemsSourceFunction"
            :items-source-changed="changeNameItems"
          >
          </wj-auto-complete>
          <p class="text-danger">{{ errors.product_name }}</p>
        </div>
      </div>

      <div class="col-md-12 col-sm-12 col-xs-12 text-left">
        <div class="radio">
          <div class="row">
            <el-radio-group v-model="stock_flg">
              <el-radio :label="STOCK"> 在庫品 </el-radio>
              <br />

              <br />
            </el-radio-group>
          </div>
          <div class="row">
            <br />
          </div>

          <div class="row">
            <el-radio-group v-model="stock_flg">
              <el-radio :label="CUSTODY"> 預かり品 </el-radio>
            </el-radio-group>
          </div>

          <div v-bind:class="{ 'has-error': errors.id != '' }">
            <div class="col-md-3 col-sm-3 col-xs-12"></div>

            <div class="col-xs-9">
              得意先番号
              <wj-auto-complete
                class="form-control"
                id="accustomerNo"
                search-member-path="id"
                display-member-path="id"
                selected-value-path="id"
                :is-required="false"
                :items-source="customerList"
                :selected-value="id"
                :selectedIndexChanged="selectCustomerNo"
                :textChanged="setTextChanged"
                :max-items="customerList.length"
                :is-read-only="stock_flg != CUSTODY"
              ></wj-auto-complete>
              <p class="text-danger">{{ errors.id }}</p>
            </div>
          </div>
          <div v-bind:class="{ 'has-error': errors.customer_name != '' }">
            <div class="col-md-3 col-sm-3 col-xs-12"></div>
            <div class="col-xs-9">
              得意先名
              <wj-auto-complete
                class="form-control"
                id="accustomerName"
                search-member-path="customer_name"
                display-member-path="customer_name"
                selected-value-path="customer_name"
                :selected-index="-1"
                :is-required="false"
                :items-source="customerList"
                :selected-value="customer_name"
                :selectedIndexChanged="selectCustomerName"
                :textChanged="setTextChanged"
                :max-items="customerList.length"
                :is-read-only="stock_flg != CUSTODY"
              ></wj-auto-complete>
              <p class="text-danger">{{ errors.customer_name }}</p>
            </div>
          </div>
          <div class="row">
            <br />
          </div>
          <div class="row">
            <br />
          </div>

          <div class="row">
            <el-radio-group v-model="stock_flg">
              <el-radio :label="ORDER"> 発注品 </el-radio></el-radio-group
            >
          </div>
          <div v-bind:class="{ 'has-error': errors.matter_no != '' }">
            <div class="col-md-3 col-sm-3 col-xs-12"></div>

            <div class="col-xs-9">
              案件番号
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
                :textChanged="setTextChanged"
                :max-items="matterList.length"
                :is-read-only="stock_flg != ORDER"
              ></wj-auto-complete>
              <p class="text-danger">{{ errors.matter_no }}</p>
            </div>
          </div>
          <div v-bind:class="{ 'has-error': errors.matter_name != '' }">
            <div class="col-md-3 col-sm-3 col-xs-12"></div>
            <div class="col-xs-9">
              案件名

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
                :textChanged="setTextChanged"
                :max-items="matterList.length"
                :is-read-only="stock_flg != ORDER"
              ></wj-auto-complete>
              <p class="text-danger">{{ errors.matter_name }}</p>
            </div>
          </div>
          <br />

          <br />
        </div>
      </div>
      <div class="col-xs-12">
        <a class="btn btn-primary" @click="back">キャンセル</a>
        <el-button type="primary" @click="showDialog">決定</el-button>
      </div>
    </div>

    <!-- 確認画面 -->
    <el-dialog title="商材追加数量入力" :visible.sync="processDialog" width="80%" :closeOnClickModal="false">
      <div class="row">
        <div class="col-xs-12 text-center">
          <div class="col-xs-12">追加する商材の数量を登録する</div>
          <div class="form-group">
            <label class="control-label col-xs-4 text-left" style="padding-top: 5px"
              >商品CD</label
            >
            <div class="col-xs-8">
              <input
                type="text"
                class="form-control"
                v-model="product_code"
                v-bind:readonly="true"
              />
            </div>

            <label class="control-label col-xs-4 text-left" style="padding-top: 5px"
              >商品名</label
            >
            <div class="col-xs-8">
              <input
                type="text"
                class="form-control"
                v-model="product_name"
                v-bind:readonly="true"
              />
            </div>

            <label class="control-label col-xs-4 text-left" style="padding-top: 5px"
              >在庫種別</label
            >
            <div class="col-xs-8">
              <input
                type="text"
                class="form-control"
                v-model="stock_flg_name"
                v-bind:readonly="true"
              />
            </div>

            <label class="control-label col-xs-4 text-left" style="padding-top: 5px"
              >案件名</label
            >
            <div class="col-xs-8">
              <input
                type="text"
                class="form-control"
                v-model="matter_name"
                v-bind:readonly="true"
              />
            </div>

            <label class="control-label col-xs-4 text-left" style="padding-top: 5px"
              >得意先名</label
            >
            <div class="col-xs-8">
              <input
                type="text"
                class="form-control"
                v-model="customer_name"
                v-bind:readonly="true"
              />
            </div>

            <div v-bind:class="{ 'has-error': errors.quantity != '' }">
              <label class="control-label col-xs-4 text-left" style="padding-top: 5px"
                >数量</label
              >
              <div class="col-xs-8">
                <input
                  type="text"
                  class="form-control"
                  v-model="quantity"
                  v-bind:readonly="false"
                />
                <p class="text-danger">{{ errors.quantity }}</p>
              </div>
            </div>

            <el-button type="primary" v-bind:disabled="isSaved" @click="process">登録</el-button>
          </div>
        </div>
      </div>
    </el-dialog>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    isSaved: false,
    processDialog: false,
    REQUIRED: "必須です。",
    qrInfo: [],
    ORDER: 0, // 発注品
    STOCK: 1, //在庫品
    CUSTODY: 2, //預かり品
    stock_flg_name: "在庫品",
    stock_flg: 1,
    matter_id: null,
    customer_id: null,
    matter_no: null,
    matter_name: null,
    product_id: null,
    product_code: null,
    product_name: null,
    shelf_number_id: null,
    shelf_area: null,
    warehouse_id: null,
    warehouse_name: null,
    id: null, //顧客ID
    customer_name: null,
    quantity: 0,
    wjSearchObj: {
      productCode: {},
      productName: {},
      maker: {},
    },

    errors: {
      matter_no: "",
      matter_name: "",
      product_code: "",
      product_name: "",
      supplier_name: "",
      id: "",
      customer_name: "",
      quantity: "",
    },
  }),
  watch: {
    stock_flg: function (val, oldVal) {
      this.matter_id = null;
      this.matter_no = null;
      this.matter_name = null;
      this.customer_id = null;
      this.id = null;
      this.customer_name = null;
    },
  },
  props: {
    matterList: Array,
    customerList: Array,
    makerList: Array,
  },
  created: function () {},
  mounted: function () {
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];
    this.shelf_number_id = JSON.parse(localStorage.getItem("shelf_number_id")) || [];
    this.shelf_area = JSON.parse(localStorage.getItem("shelf_area")) || [];
    this.warehouse_id = JSON.parse(localStorage.getItem("warehouse_id")) || [];
    this.warehouse_name = JSON.parse(localStorage.getItem("warehouse_name")) || [];
    this.qrInfo = JSON.parse(localStorage.getItem("qrInfo")) || [];
  },
  methods: {
    back() {
      var listUrl = "/inventory-product-list";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    initProductCode: function (sender) {
      this.wjSearchObj.productCode = sender;
    },
    initProductName: function (sender) {
      this.wjSearchObj.productName = sender;
    },
    initMaker: function (sender) {
      this.wjSearchObj.maker = sender;
    },
    changeNameItems(sender){
      this.wjSearchObj.productCode.itemsSource = sender.itemsSource;
    },
    changeCodeItems(sender){
      this.wjSearchObj.productName.itemsSource = sender.itemsSource;
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
      // this.setASyncAutoCompleteList(
      //   this.wjSearchObj.productCode,
      //   PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_NAME_URL,
      //   text,
      //   PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT,
      //   PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_NAME_LIST,
      //   callback,
      //   this.getProductNameAutoCompleteFilterData
      // );
    },
    productCodeItemsSourceFunction: function (text, maxItems, callback) {
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
      // this.setASyncAutoCompleteList(
      //   this.wjSearchObj.productName,
      //   PRODUCT_AUTO_COMPLETE_SETTING.PRODUCT_CODE_URL,
      //   text,
      //   PRODUCT_AUTO_COMPLETE_SETTING.MAX_LIST_COUNT,
      //   PRODUCT_AUTO_COMPLETE_SETTING.OVER_PRODUCT_CODE_LIST,
      //   callback,
      //   this.getProductNameAutoCompleteFilterData
      // );
    },
    showDialog() {
      //必須チェック
      // if (this.product_code == null) {
      //   this.errors.product_code = this.REQUIRED;
      //   return;
      // }
      if ((this.wjSearchObj.productCode.selectedValue == null && this.product_id == null) || this.wjSearchObj.productCode.text == '') {
        this.errors.product_code = this.REQUIRED;
        return;
      }

      // if (this.wjSearchObj.product.selectedItem != null) {
      //   this.product_code = this.wjSearchObj.product.selectedItem.product_code;
      //   this.product_name = this.wjSearchObj.product.selectedItem.product_name;
      // } else {
      //   return;
      // }

      //預かり品の場合
      if (this.stock_flg == this.CUSTODY) {
        if (this.id == null) {
          this.errors.id = this.REQUIRED;
          return;
        }
        if (this.customer_name == null) {
          this.errors.customer_name = this.REQUIRED;
          return;
        }

        this.stock_flg_name = "預かり品";
      }

      //発注品の場合
      if (this.stock_flg == this.ORDER) {
        if (this.matter_no == null) {
          this.errors.matter_no = this.REQUIRED;
          return;
        }
        if (this.matter_name == null) {
          this.errors.matter_name = this.REQUIRED;
          return;
        }
        this.stock_flg_name = "発注品";
      }

      //在庫品の場合
      if (this.stock_flg == this.STOCK) {
        this.stock_flg_name = "在庫品";
      }

      //在庫品の場合、既に同じ倉庫に同じ商品がないかどうか
      this.loading = true;
      axios
        .post("/inventory-product-addition/search", {
          maker_id: this.wjSearchObj.maker.selectedValue,
          product_id: this.wjSearchObj.productCode.selectedValue !== null ? this.wjSearchObj.productCode.selectedItem.product_id : this.product_id ,
          warehouse_id: this.warehouse_id,
          stock_flg: this.stock_flg,
        })

        .then(
          function (response) {
            this.loading = false;

            if (response.data == "productError") {
              alert("選択された商品が存在しません。");
            } else if (response.data == "draftError") {
              alert("仮登録の商品の在庫は追加できません");
              return;
            } else if (response.data == "stockError") {
              alert("既に同じ倉庫内に商品が登録されています。");
            } else if (response.data) {
              var data = response.data;
              this.product_id = data.product_id;
              this.product_code = data.product_code;
              this.product_name = data.product_name;

              this.processDialog = true;
            }
          }.bind(this)
        )
        .catch(
          function (error) {
            this.loading = false;
            return;
          }.bind(this)
        );
    },

    // 実行
    process(index) {
      if (this.quantity == null || this.quantity == 0) {
        this.errors.quantity = "数量は0より大きい値を入力してください。";
        return;
      }
      this.loading = true;
      this.isSaved = true;

      //預かり品の場合は表示されている得意先ID、発注品の場合は案件IDから取得して内部保持している得意先ID
      var cusId = this.id;
      if (this.customer_id != null) {
        cusId = this.customer_id;
      }
      axios
        .post("/inventory-product-addition/save", {
          stock_flg: this.stock_flg,
          qr_code: null,
          product_id: this.product_id,
          product_code: this.product_code,
          warehouse_id: this.warehouse_id,
          shelf_number_id: this.shelf_number_id,
          matter_id: this.matter_id,
          customer_id: cusId,
          quantity_logic: 0,
          quantity_real: this.quantity,
        })

        .then(
          function (response) {
            this.loading = false;

            if (response.data == "printError") {
              alert("印刷エラーが発生しました。");
              this.processDialog = false;
              var listUrl = "/inventory-product-list";
              window.onbeforeunload = null;
              this.isSaved = false;
              location.href = listUrl;
            } else if (response.data) {
              this.processDialog = false;
              var listUrl = "/inventory-product-list";
              window.onbeforeunload = null;
              location.href = listUrl;

              listUrl = response.data;
              var pattern = "smapri:";
              if (listUrl.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = listUrl;
              }
            } else {
              alert(MSG_ERROR);
              this.isSaved = false;
            }
          }.bind(this)
        )
        .catch(
          function (error) {
            this.loading = false;
            // this.isSaved = false;
            
            if (error.response == undefined) {
              this.processDialog = false;
              var listUrl = "/inventory-product-list";
              window.onbeforeunload = null;
              location.href = listUrl;
            }

            if (error.response.data.message) {
              alert(error.response.data.message);
              this.isSaved = false;
            } else {
              alert(MSG_ERROR);
              this.isSaved = false;
            }
            location.reload();
            return;
          }.bind(this)
        );
    },
    setTextChanged: function (sender) {
      this.setAutoCompleteValue(sender);
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
        this.customer_name = item.customer_name;
        this.matter_id = item.id;
        this.matter_no = item.matter_no;
        this.matter_name = item.matter_name;
        this.errors.matter_no = "";
        this.errors.matter_name = "";
      } else {
        this.customer_id = null;
        this.customer_name = null;
        this.matter_id = null;
        this.matter_no = null;
        this.matter_name = null;
      }
    },

    selectCustomerNo: function (sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.id = item.id;
        this.customer_name = item.customer_name;
        this.errors.id = "";
        this.errors.customer_name = "";
      } else {
        this.id = null;
        this.customer_name = null;
      }
    },
    selectCustomerName: function (sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.id = item.id;
        this.customer_name = item.customer_name;
        this.errors.id = "";
        this.errors.customer_name = "";
      } else {
        this.id = null;
        this.customer_name = null;
      }
    },
    changeProductName: function (sender) {
      var item = sender.selectedItem;
      if(item != null){
          this.product_code = item.product_code;
      }else{
          this.product_code = '';
      }
      // axios
      //   .post("/inventory-product-addition/searchProduct", {
      //     product_code: this.wjSearchObj.productCode.text,
      //     product_name: this.wjSearchObj.productName.text,
      //     maker_id: this.wjSearchObj.maker.selectedValue,
      //     isProductName: true,
      //   })

      //   .then(
      //     function (response) {
      //        if (response.data != null && response.data.product_id) {
      //         var productInfo = response.data;
      //         this.wjSearchObj.productCode.text = productInfo.product_code;
      //         this.product_id = productInfo.product_id;
      //       }else{
      //         this.product_id = null;
      //         // this.wjSearchObj.productCode.text = '';
      //       }
      //     }.bind(this)
      //   )
      //   .catch(
      //     function (error) {
      //       this.loading = false;
      //       return;
      //     }.bind(this)
      //   );
    },
    changeProductCode: function (sender) {
      // var item = sender.selectedItem;
      // if(item != null){
      //     this.product_name = item.product_name;
      // }else{
      //     this.product_name = '';
      // }

      var value = this.rmUndefinedBlank(sender.selectedValue);
      this.product_name = value;
      
      // axios
      //   .post("/inventory-product-addition/searchProduct", {
      //     product_code: this.wjSearchObj.productCode.text,
      //     product_name: this.wjSearchObj.productName.text,
      //     maker_id: this.wjSearchObj.maker.selectedValue,
      //     isProductName: false,
      //   })

      //   .then(
      //     function (response) {
      //       this.loading = false;
      //        if (response.data != null && response.data.product_id) {
      //         var productInfo = response.data;
      //         this.wjSearchObj.productName.text = productInfo.product_name;
      //         // this.wjSearchObj.productCode.selectedValue = productInfo.id;
      //       }else{
      //         // this.wjSearchObj.productName.text = '';
      //       }
      //     }.bind(this)
      //   )
      //   .catch(
      //     function (error) {
      //       this.loading = false;
      //       return;
      //     }.bind(this)
      //   );
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
