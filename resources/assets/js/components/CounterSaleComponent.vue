<template>
  <div class>
    <loading-component :loading="loading" />
    <div class="col-xs-12">
      <h3>
        <u>商材選択</u>
      </h3>
    </div>

    <div class="col-xs-11">
      <div v-bind:class="{ 'has-error': errors.warehouse != '' }">
        <label class="control-label">部門倉庫</label>
        <wj-auto-complete
          class="form-control"
          id="acWarehouse"
          search-member-path="id"
          display-member-path="warehouse_name"
          :items-source="warehouselist"
          selected-value-path="id"
          :selectedIndexChanged="changeIdxWarehouse"
          :selected-value="searchParams.warehouse_id"
          :initialized="initWarehouse"
          :max-length="warehouselist.length"
        ></wj-auto-complete>
        <p class="text-danger">{{ errors.warehouse }}</p>
      </div>
    </div>

    <div class="col-xs-11">
      <label class="control-label">メーカー名</label>
      <wj-auto-complete
        class="form-control"
        id="acWarehouse"
        search-member-path="maker_id"
        display-member-path="supplier_name"
        :items-source="mList"
        selected-value-path="maker_id"
        :selectedIndexChanged="changeIdxMaker"
        :selected-index="-1"
        :initialized="initMaker"
        :max-length="pList.length"
      ></wj-auto-complete>
    </div>

    <div class="col-xs-11">
      <label class="control-label">商品名</label>
      <wj-auto-complete
        class="form-control"
        id="acWarehouse"
        search-member-path="id"
        display-member-path="product_name"
        :items-source="pList"
        selected-value-path="id"
        :selected-index="-1"
        :selectedIndexChanged="changeIdxProductName"
        :initialized="initProductName"
        :max-length="pList.length"
      ></wj-auto-complete>
    </div>

    <div class="col-xs-11">
      <div v-bind:class="{ 'has-error': errors.product_code != '' }">
        <label class="control-label">品番</label>
        <wj-auto-complete
          class="form-control"
          id="acWarehouse"
          search-member-path="id"
          display-member-path="product_code"
          :items-source="pList"
          selected-value-path="id"
          :selected-index="-1"
          :selectedIndexChanged="changeIdxProductCode"
          :initialized="initProductCode"
          :max-length="pList.length"
        ></wj-auto-complete>
        <p class="text-danger">{{ errors.product_code }}</p>
      </div>
    </div>

    <div class="col-xs-11">
      <br />
      <label class="control-label">型式・規格</label>
      <div class="col-xs-12">{{ searchParams.model }}</div>
    </div>

    <div class="col-xs-12">
      <h3>
        <u>数量</u>
      </h3>
    </div>

    <div class="col-xs-6">
      <div v-bind:class="{ 'has-error': errors.quantity != '' }">
        <input type="number" class="form-control" v-model="searchParams.quantity" />
        <p class="text-danger">{{ errors.quantity }}</p>
      </div>
    </div>

    <div class="col-xs-6">
      <button type="button" class="btn btn-info btn-minus" v-on:click="minus_quantity()">
        －
      </button>
      <button type="button" class="btn btn-info btn-plus" v-on:click="plus_quantity()">
        ＋
      </button>
    </div>

    <div class="col-xs-12">
      <br />
      <label class="control-label">単位</label>
      <div class="col-xs-12">{{ searchParams.unit }}</div>
    </div>

    <div class="col-xs-12">
      <br />
      <label class="control-label">最小単位数</label>
      <div class="col-xs-12">{{ searchParams.min_quantity }}</div>
    </div>

    <div class="col-xs-11">
      <br />
      <label class="control-label">在庫数</label>
      <div class="col-xs-12">
        {{ comma_format(searchParams.actual_quantity) }}
      </div>
    </div>

    <div class="col-xs-11">
      <br />
      <label class="control-label">入荷予定数</label>
      <div class="col-xs-12">
        {{ comma_format(searchParams.arrival_quantity) }}
      </div>
    </div>

    <div class="col-xs-11">
      <br />
      <label class="control-label">入荷予定日</label>
      <div class="col-xs-12">{{ searchParams.next_arrival_date }}</div>
    </div>

    <div class="col-xs-12">
      <h3>
        <u>販売価格</u>
      </h3>
    </div>
    <div class="col-xs-12">
      <div class="col-xs-12 gray-back">定価</div>

      <div class="col-xs-12">{{ comma_format(searchParams.price) }}</div>
    </div>
    <div class="col-xs-12">
      <div class="col-xs-12 gray-back">標準販売単価</div>
      <div class="col-xs-12">
        <input
          type="number"
          class="form-control"
          v-model="searchParams.normal_sales_price"
        />
      </div>
    </div>

    <div class="col-xs-12">
      <div class="col-xs-12 gray-back">数量（入力数）</div>
      <div class="col-xs-12">
        <div class="col-xs-12">
          {{
            comma_format(
              (searchParams.quantity == null ? 0 : searchParams.quantity) *
                (searchParams.min_quantity == null ? 0 : searchParams.min_quantity)
            )
          }}
        </div>
      </div>
    </div>

    <div class="col-xs-12">
      <h4>金額</h4>
      <div class="col-xs-12">
        {{
          comma_format(
            (searchParams.normal_sales_price == null
              ? 0
              : searchParams.normal_sales_price) *
              (searchParams.quantity == null ? 0 : searchParams.quantity) *
              (searchParams.min_quantity == null ? 0 : searchParams.min_quantity)
          )
        }}
      </div>
    </div>

    <div class="col-xs-5 text-left">
      <br />
      <a class="btn btn-primary" @click="back">戻る（販売確認）</a>
    </div>
    <div class="col-xs-3 text-center">
      <br />
      <a class="btn btn-primary" @click="process">決定</a>
    </div>
    <div class="col-xs-3 text-right">
      <br />
      <a class="btn btn-primary gold-back" @click="next">次の商材</a>
    </div>
  </div>
</template>

<script>
import { forEach } from "lodash";
export default {
  data: () => ({
    loading: false,
    pList: [],
    mList: [],
    searchParams: {
      warehouse_id: 0,
      product_id: null,
      product_code: null,
      product_name: null,
      quantity: 0,
      model: null,
      unit: null,
      normal_sales_price: null,
      normal_purchase_price: null,
      price: null,
      actual_quantity: null,
      min_quantity: null,
      next_arrival_date: null,
      arrival_quantity: null,
      maker_id: null,
      supplier_name: null,
      purchase_price: null,
      stock_unit: null,
    },
    tableData: [],
    selectedWareHouseList: null,
    tableData: [],
    wjSearchObj: {
      warehouse: {},
      maker: {},
      product_name: {},
      product_code: {},
    },
    errors: {
      warehouse: "",
      product_code: "",
      quantity: "",
    },
    REQUIRED: "必須です",
    REQUIRED_QUANTITY: "1以上の数値を入力してください",
  }),
  props: {
    warehouselist: Array,
    productlist: Array,
    makerlist: Array,
    default_warehouse_id: null,
    taxrate: null,
  },
  created: function () {},
  mounted: function () {
    this.pList = this.productlist;
    this.mList = this.makerlist;
    localStorage.setItem("sign", null);
    var is_back = JSON.parse(localStorage.getItem("is_back")) || false;
    if (is_back === true) {
      localStorage.setItem("is_back", false);
      this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    } else {
      localStorage.setItem("tableData", null);
    }
    this.searchParams.warehouse_id = this.default_warehouse_id;
  },
  watch: {
    "searchParams.quantity": function (val) {
      if (val > 0) {
        this.errors.quantity = "";
      }
    },
  },
  methods: {
    isRequired() {
      var r = true;
      if (this.wjSearchObj.warehouse.selectedIndex == -1) {
        this.errors.warehouse = this.REQUIRED;
        r = false;
      }
      if (this.wjSearchObj.product_code.selectedIndex == -1) {
        this.errors.product_code = this.REQUIRED;
        r = false;
      }
      if (this.searchParams.quantity == null || this.searchParams.quantity <= 0) {
        this.errors.quantity = this.REQUIRED_QUANTITY;
        r = false;
      }
      return r;
    },
    tablePush() {
      var result = true;

      //同一商品選択チェック
      this.tableData.forEach((data) => {
        if (data.product_id == this.searchParams.product_id) {
          if (result) {
            alert(
              "既にこの製品は選択されています。他製品を選択、もしくは「販売確認」一覧を確認してください。"
            );
            result = false;
          }
        }
      });

      //在庫数超過チェック
      if (
        Number(this.searchParams.actual_quantity) < Number(this.searchParams.quantity) &&
        result
      ) {
        alert("在庫数を超える数量を設定することはできません。");
        result = false;
      }

      if (result) {
        // 販売金額
        var salesAmount =
            this.roundDecimalSalesPrice(
              this.searchParams.normal_sales_price *
              this.searchParams.quantity *
              this.searchParams.min_quantity);

        // 消費税額
        var consumptionTax = 
            this.roundDecimalStandardPrice(
              this.searchParams.normal_sales_price *
              this.searchParams.quantity *
              this.searchParams.min_quantity *
              this.taxrate);

        // 販売金額×消費税額
        var taxSalesAmount = this.roundDecimalSalesPrice(salesAmount + consumptionTax);

        this.tableData.push({
          warehouse_id: this.wjSearchObj.warehouse.selectedValue,
          product_id: this.searchParams.product_id,
          product_code: this.searchParams.product_code,
          product_name: this.searchParams.product_name,
          model: this.searchParams.model,
          quantity: this.searchParams.quantity,
          price: this.searchParams.price,
          actual_quantity: this.searchParams.actual_quantity,
          min_quantity: this.searchParams.min_quantity,
          arrival_quantity: this.searchParams.arrival_quantity,
          next_arrival_date: this.searchParams.next_arrival_date,
          normal_sales_price: parseInt(this.searchParams.normal_sales_price),
          normal_purchase_price: parseInt(this.searchParams.normal_purchase_price),
          maker_id: this.searchParams.maker_id,
          supplier_name: this.searchParams.supplier_name,
          purchase_price: this.searchParams.purchase_price,
          stock_unit: this.searchParams.stock_unit,
          unit: this.searchParams.unit,

          sales_amount: salesAmount,
            // this.roundDecimalSalesPrice(
            //   this.searchParams.normal_sales_price *
            //   this.searchParams.quantity *
            //   this.searchParams.min_quantity),

          consumption_tax: consumptionTax,
            // this.roundDecimalStandardPrice(
            //   this.searchParams.normal_sales_price *
            //   this.searchParams.quantity *
            //   this.searchParams.min_quantity *
            //   this.taxrate),

          tax_sales_amount: taxSalesAmount,
            // this.roundDecimalSalesPrice(
            //   this.searchParams.normal_sales_price *
            //   this.searchParams.quantity *
            //   this.searchParams.min_quantity *
            //   (1 + this.taxrate)),
        });
      }

      return result;
    },
    process() {
      if (this.wjSearchObj.product_code.selectedIndex == -1 || this.isRequired()) {
        if (this.wjSearchObj.product_code.selectedIndex != -1) {
          if (!this.tablePush()) {
            return;
          }
        }

        if (this.tableData.length <= 0) {
          alert("商材がありません。\n一つ以上、商材を選択してください。");
          return;
        }
        this.loading = true;
        localStorage.setItem("tableData", JSON.stringify(this.tableData));
        var listUrl = "/counter-sale-confirm";
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },
    next() {
      if (this.isRequired()) {
        if (!this.tablePush()) {
          return;
        }

        this.searchParams.quantity = 0;
        this.wjSearchObj.warehouse.selectedValue = this.default_warehouse_id;
        this.wjSearchObj.maker.selectedIndex = -1;
        this.wjSearchObj.product_name.selectedIndex = -1;
        this.wjSearchObj.product_code.selectedIndex = -1;
      }
    },
    back() {
      if (this.tableData.length > 0 && this.loading == false) {
        this.loading = true;
        localStorage.setItem("tableData", JSON.stringify(this.tableData));
        var listUrl = "/counter-sale-confirm";
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },
    minus_quantity() {
      var q = this.searchParams.quantity;
      if (!Number(q)) {
        this.searchParams.quantity = 0;
      } else if (q > 0) {
        this.searchParams.quantity = Number(q) - 1;
      }
    },
    plus_quantity() {
      var q = this.searchParams.quantity;
      if (!Number(q)) {
        this.searchParams.quantity = 1;
      } else {
        this.searchParams.quantity = Number(q) + 1;
      }
    },

    changeIdxWarehouse: function (sender) {
      if (
        this.wjSearchObj.warehouse.selectedValue !== null &&
        this.wjSearchObj.warehouse.selectedIndex !== -1 &&
        this.wjSearchObj.warehouse.text !== null &&
        this.wjSearchObj.warehouse.text !== ""
      ) {
        this.loading = true;
        axios
          .post("/counter-sale/getlist", {
            warehouseId: this.wjSearchObj.warehouse.selectedValue,
          })
          .then(
            function (response) {
              if (response.data) {
                // 成功
                var data = response.data;
                this.pList = data.productList;
                this.mList = data.makerList;
                //倉庫を変更したらメーカーを絞り込む
                this.wjSearchObj.maker.itemsSource = this.mList;
                this.wjSearchObj.maker.selectedIndex = -1;

                // 倉庫を変更したら商品名を絞り込む
                this.wjSearchObj.product_name.itemsSource = this.pList;
                this.wjSearchObj.product_name.selectedIndex = -1;

                // 倉庫を変更したら商品コードを絞り込む
                this.wjSearchObj.product_code.itemsSource = this.pList;
                this.wjSearchObj.product_code.selectedIndex = -1;
                this.loading = false;
              } 
              this.loading = false;
            }.bind(this)
          )

          .catch(
            function (error) {
            }.bind(this)
          );
      } else {
        this.pList = [];
        this.mList = [];
      }
    },
    changeIdxMaker: function (sender) {
        this.loading = true;
      // メーカーを変更したら商品名を絞り込む
      var tmpProductName = this.wjSearchObj.product_name.itemsSource;
      if (sender.selectedValue) {
        tmpProductName = [];
        for (var key in this.pList) {
          for (var index in this.pList[key].makerIdList) {
            if (
              sender.selectedValue == this.pList[key].makerIdList[index] &&
              this.wjSearchObj.warehouse.selectedValue ==
                this.pList[key].warehouseList[index]
            ) {
              tmpProductName.push(this.pList[key]);
              break;
            }
          }
        }
      }
      this.wjSearchObj.product_name.itemsSource = tmpProductName;
      this.wjSearchObj.product_name.selectedIndex = -1;

      // メーカーを変更したら商品コードを絞り込む
      var tmpProductCode = this.pList;
      if (sender.selectedValue) {
        tmpProductCode = [];
        for (var key in this.pList) {
          for (var index in this.pList[key].makerIdList) {
            if (
              sender.selectedValue == this.pList[key].makerIdList[index] &&
              this.wjSearchObj.warehouse.selectedValue ==
                this.pList[key].warehouseList[index]
            ) {
              tmpProductCode.push(this.pList[key]);
              break;
            }
          }
        }
      }
      this.wjSearchObj.product_code.itemsSource = tmpProductCode;
      this.wjSearchObj.product_code.selectedIndex = -1;
        this.loading = false;

    },
    changeIdxProductName: function (sender) {
        this.loading = true;
      // 商品名を変更したら商品コードを絞り込む
      var tmpProductCode = this.pList;
      if (sender.selectedValue) {
        tmpProductCode = [];
        for (var key in this.pList) {
          for (var index in this.pList[key].warehouseList) {
            if (
              sender.selectedValue == this.pList[key].id &&
              this.wjSearchObj.warehouse.selectedValue ==
                this.pList[key].warehouseList[index] &&
              (this.wjSearchObj.maker.selectedIndex == -1 ||
                this.wjSearchObj.maker.selectedValue ==
                  this.pList[key].makerIdList[index])
            ) {
              tmpProductCode.push(this.pList[key]);
              break;
            }
          }
        }
      }
      this.wjSearchObj.product_code.itemsSource = tmpProductCode;
      if (
        this.wjSearchObj.product_name.text != null &&
        this.wjSearchObj.product_name.text != ""
      ) {
        this.wjSearchObj.product_code.selectedIndex = 0;
      } else {
        this.wjSearchObj.product_code.selectedIndex = -1;
      }
        this.loading = false;
    },

    changeIdxProductCode: function (sender) {
      // 倉庫と商品コードから関連情報取得
      if (sender.selectedValue && this.wjSearchObj.warehouse.selectedIndex != -1) {
        this.loading = true;
        axios
          .post("/counter-sale/search", {
            productId: sender.selectedValue,
            warehouseId: this.wjSearchObj.warehouse.selectedValue,
          })
          .then(
            function (response) {
              if (response.data) {
                // 成功
                var data = response.data;

                this.errors.product_code = "";
                if (sender.selectedValue == data.id) {
                  this.searchParams.product_id = data.id;
                  this.searchParams.product_code = data.product_code;
                  this.searchParams.product_name = data.product_name;
                  this.searchParams.model = data.model;
                  this.searchParams.unit = data.unit;
                  this.searchParams.price = data.price;
                  this.searchParams.maker_id = data.maker_id;
                  this.searchParams.stock_unit = data.stock_unit;

                  this.searchParams.supplier_name = data.supplier_name;
                  this.searchParams.purchase_price = data.purchase_price;
                  this.searchParams.normal_purchase_price = parseInt(data.normal_purchase_price);

                  this.searchParams.normal_sales_price = parseInt(data.normal_sales_price);
                  this.searchParams.min_quantity = data.min_quantity;
                  this.searchParams.actual_quantity = data.actual_quantity;
                  this.searchParams.arrival_quantity = data.arrival_quantity;
                  this.searchParams.next_arrival_date = data.next_arrival_date;
                }
                this.printDialog = true;
                this.loading = false;
              }
              this.loading = false;
            }.bind(this)
          )

          .catch(
            function (error) {
              this.loading = false;
            }.bind(this)
          );
      } else {
        this.loading = true;
        this.searchParams.product_id = null;
        this.searchParams.product_code = null;
        this.searchParams.product_name = null;
        this.searchParams.model = null;
        this.searchParams.unit = null;
        this.searchParams.price = null;
        this.searchParams.normal_sales_price = null;
        this.searchParams.normal_purchase_price = null;
        this.searchParams.min_quantity = null;
        this.searchParams.actual_quantity = null;
        this.searchParams.arrival_quantity = null;
        this.searchParams.next_arrival_date = null;
        this.searchParams.maker_id = null;
        this.searchParams.supplier_name = null;
        this.searchParams.purchase_price = null;
        this.searchParams.stock_unit = null;
        this.loading = false;
      }
    },

    logErrors(promise) {
      promise.catch(console.error);
    },

    /* 以下オートコンプリード設定 */
    initWarehouse(sender) {
      this.wjSearchObj.warehouse = sender;
    },
    initMaker(sender) {
      this.wjSearchObj.maker = sender;
    },
    initProductName(sender) {
      this.wjSearchObj.product_name = sender;
    },
    initProductCode(sender) {
      this.wjSearchObj.product_code = sender;
    },
    // 3桁ずつカンマ区切り
    comma_format: function (val) {
      if (val == undefined || val == "") {
        return 0;
      }
      val = parseInt(val);
      return val.toLocaleString();
    },
  },
};
</script>

<style>
.btn-minus {
  background: rgb(231, 5, 5);
  border-color: rgb(211, 2, 2);
}
.btn-minus:hover {
  background: rgb(253, 16, 16);
  border-color: rgb(212, 12, 12);
}
.btn-minus:active {
  background: rgb(190, 4, 4) !important ;
  border-color: rgb(158, 3, 3) !important ;
}

.btn-plus {
  background: rgb(23, 4, 190);
}
.gray-back {
  background: rgb(231, 230, 230);
  padding: 10px;
  margin-top: 5px;
  margin-bottom: 3px;
}
.gold-back {
  background: rgb(119, 117, 1);
  border-color: rgb(107, 105, 1);
}
.gold-back:active {
  background: rgb(90, 89, 0) !important ;
  border-color: rgb(83, 82, 1) !important ;
}
.gold-back:hover {
  background: rgb(148, 145, 0);
  border-color: rgb(134, 132, 0);
}
</style>
