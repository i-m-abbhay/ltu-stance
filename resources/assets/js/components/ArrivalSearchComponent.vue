<template>
  <div>
    <!-- ステップ -->
    <!-- <div class="col-md-12 subheader-step">
            <br />
            <el-steps :active="1" finish-status="success">
            <el-step title="Step 1"></el-step>
            <el-step title="Step 2"></el-step>
            <el-step title="Step 3"></el-step>
            </el-steps> 
            <div class="col-md-2">ＳＴＥＰ１：</div>
            <div class="col-md-6">入荷情報を検索します。 </div>
            <br />
    </div>-->

    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <form
        id="searchForm"
        name="searchForm"
        class="form-horizontal"
        @submit.prevent="search"
      >
        <div class="row">
          <div class="col-md-6">
            <label class="control-label" for="acCustomer">得意先名</label>
            <wj-auto-complete
              class="form-control"
              id="acClassBig"
              search-member-path="customer_name"
              display-member-path="customer_name"
              selected-value-path="id"
              :selected-index="-1"
              :selected-value="searchParams.customer_id"
              :initialized="initCustomer"
              :max-items="customerlist.length"
              :items-source="customerlist"
            ></wj-auto-complete>
          </div>
          <div class="col-md-3">
            <label class="control-label">部門名</label>
            <wj-auto-complete
              class="form-control"
              id="acDepartment"
              search-member-path="department_name"
              display-member-path="department_name"
              :items-source="departmentlist"
              selected-value-path="department_name"
              :selected-value="searchParams.department_name"
              :selectedIndexChanged="changeIdxDepartment"
              :selected-index="-1"
              :initialized="initDepartment"
              :max-items="departmentlist.length"
            ></wj-auto-complete>
          </div>
          <div class="col-md-3">
            <label class="control-label">担当者名</label>
            <wj-auto-complete
              class="form-control"
              id="acStaff"
              search-member-path="staff_name"
              display-member-path="staff_name"
              :items-source="stafflist"
              selected-value-path="staff_name"
              :selected-value="searchParams.staff_name"
              :selected-index="-1"
              :initialized="initStaff"
              :max-items="stafflist.length"
            ></wj-auto-complete>
          </div>
        </div>
        <div class="row">
          <div class="col-md-3">
            <label class="control-label">案件番号</label>
            <wj-auto-complete
              class="form-control"
              id="acMatter"
              search-member-path="matter_no"
              display-member-path="matter_no"
              :items-source="matterlist"
              selected-value-path="matter_no"
              :selected-value="searchParams.matter_no"
              :selected-index="-1"
              :initialized="initMatterNo"
              :max-items="matterlist.length"
            ></wj-auto-complete>
          </div>
          <div class="col-md-6">
            <label class="control-label">案件名</label>
            <wj-auto-complete
              class="form-control"
              id="acMatter"
              search-member-path="matter_name"
              display-member-path="matter_name"
              :items-source="matterlist"
              selected-value-path="matter_name"
              :selected-value="searchParams.matter_name"
              :selected-index="-1"
              :initialized="initMatterName"
              :max-items="matterlist.length"
            ></wj-auto-complete>
          </div>
          <div class="col-md-3">
            <label class="control-label" for="acDepartment">商品番号</label>
            <input type="text" class="form-control" v-model="searchParams.product_code" />
            <!-- <wj-auto-complete
              class="form-control"
              id="acProduct"
              search-member-path="product_code"
              display-member-path="product_code"
              :items-source="productlist"
              selected-value-path="product_code"
              :min-length="1"
              :lost-focus="selectProduct"
            ></wj-auto-complete> -->
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <label class="control-label" for="acDepartment">メーカー名</label>
            <wj-auto-complete
              class="form-control"
              id="acMaker"
              search-member-path="supplier_name"
              display-member-path="supplier_name"
              :items-source="makerlist"
              selected-value-path="supplier_name"
              :selected-value="searchParams.maker_name"
              :selected-index="-1"
              :initialized="initMaker"
              :max-items="makerlist.length"
            ></wj-auto-complete>
          </div>
          <div class="col-md-6">
            <label class="control-label" for="acDepartment">仕入先名</label>
            <wj-auto-complete
              class="form-control"
              id="acSupplier"
              search-member-path="supplier_name"
              display-member-path="supplier_name"
              :items-source="supplierlist"
              selected-value-path="supplier_name"
              :selected-value="searchParams.supplier_name"
              :selected-index="-1"
              :initialized="initSupplier"
              :max-items="supplierlist.length"
            ></wj-auto-complete>
          </div>
        </div>
        <div class="row">
          <div class="col-md-6">
            <label class="control-label" for="acDepartment">発注番号</label>
            <wj-auto-complete
              class="form-control"
              id="acOrder"
              search-member-path="order_no"
              display-member-path="order_no"
              :items-source="orderlist"
              selected-value-path="order_no"
              :selected-value="searchParams.order_no"
              :selected-index="-1"
              :initialized="initOrder"
              :max-items="orderlist.length"
            ></wj-auto-complete>
          </div>
          <div class="col-md-6">
            <label class="control-label" for="acDepartment">届け先名</label>
            <wj-auto-complete
              class="form-control"
              id="acWarehouse"
              search-member-path="warehouse_id"
              display-member-path="warehouse_name"
              :items-source="orderdeliverylist"
              selected-value-path="warehouse_id"
              :selected-value="defaultwarehouseid"
              :selected-index="-1"
              :initialized="initWarehouse"
              :max-items="orderdeliverylist.length"
            ></wj-auto-complete>
          </div>
        </div>
        <div class="row">
          <label class="col-md-12 text-left">入荷予定日</label>
          <div class="col-md-3">
            <input
              type="date"
              class="form-control"
              name="date1"
              v-model="searchParams.from_date"
            />
          </div>
          <span class="col-md-1 text-center">～</span>
          <div class="col-md-3">
            <input
              type="date"
              class="form-control"
              name="date2"
              v-model="searchParams.to_date"
            />
          </div>
        </div>
        <div class="col-md-12 text-center">
          <br />
          <button type="submit" class="btn btn-primary btn-search">検索</button>
          <br />
          <br />
        </div>

        <div class="clearfix"></div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    searchParams: {
      customer_id: null,
      department_name: "",
      staff_name: "",
      matter_no: "",
      matter_name: "",
      product_code: "",
      maker_name: "",
      supplier_name: "",
      order_no: "",
      warehouse_name: "",
      from_date: "",
      to_date: "",
    },
    wjSearchObj: {
      customer: {},
      department_name: {},
      staff_name: {},
      matter_no: {},
      matter_name: {},
      maker_name: {},
      supplier_name: {},
      order_no: {},
      warehouse_name: {},

    },
    urlparam: "",
  }),
  props: {
    isEditable: Number,
    customerlist: Array,
    departmentlist: Array,
    stafflist: Array,
    matterlist: Array,
    makerlist: Array,
    staffDepartmentData: Object,
    supplierlist: Array,
    orderlist: Array,
    orderdeliverylist: Array,
    defaultwarehouseid: null,
  },
  created: function () {
    // コンボボックスの先頭に空行追加
    this.customerlist.splice(0, 0, "");
    this.departmentlist.splice(0, 0, "");
    this.stafflist.splice(0, 0, "");
    this.matterlist.splice(0, 0, "");
    this.makerlist.splice(0, 0, "");
    this.supplierlist.splice(0, 0, "");
    this.orderlist.splice(0, 0, "");
    this.orderdeliverylist.splice(0, 0, "");
  },
  mounted: function () {
    // 検索条件セット
    var date = new Date();
    this.searchParams.from_date =
      date.getFullYear() +
      "-" +
      ("00" + (date.getMonth() + 1)).slice(-2) +
      "-" +
      ("00" + date.getDate()).slice(-2);

    var query = window.location.search;
    if (query.length > 1) {
      this.setSearchParams(query, this.searchParams);
      // 日付に復帰させる検索条件がない場合はnullをセット
      // if (this.searchParams.from_date == "") { this.searchParams.from_date = null };
      // if (this.searchParams.to_date == "") { this.searchParams.to_date = null };
    }
  },
  methods: {
    initCustomer(sender) {
      this.wjSearchObj.customer = sender;
    },
    initDepartment(sender) {
      this.wjSearchObj.department_name = sender;
    },
    initStaff(sender) {
      this.wjSearchObj.staff_name = sender;
    },
    initMatterNo(sender) {
      this.wjSearchObj.matter_no = sender;
    },
    initMatterName(sender) {
      this.wjSearchObj.matter_name = sender;
    },
    initMaker(sender) {
      this.wjSearchObj.maker_name = sender;
    },
    initSupplier(sender) {
      this.wjSearchObj.supplier_name = sender;
    },
    initOrder(sender) {
      this.wjSearchObj.order_no = sender;
    },
    initWarehouse(sender) {
      this.wjSearchObj.warehouse_name = sender;
    },
    changeIdxDepartment: function(sender){
      this.wjSearchObj.department_name = sender;
      // 部門を変更したら担当者を絞り込む
      var tmpSalesStaff = this.stafflist;
      if (sender.selectedValue) {
          tmpSalesStaff = [];
          if (this.staffDepartmentData[sender.selectedValue]) {
              tmpSalesStaff = this.stafflist.filter(rec => {
                  return (this.staffDepartmentData[sender.selectedValue].indexOf(rec.id) !== -1)
              });
          }
      }
      this.wjSearchObj.staff_name.itemsSource = tmpSalesStaff;
      this.wjSearchObj.staff_name.selectedIndex = -1;
    },
    // 検索
    search() {
      this.urlparam = "?";
      this.urlparam +=
        "customer_id=" + (this.wjSearchObj.customer.selectedValue == null ? '' : this.wjSearchObj.customer.selectedValue);
      this.urlparam +=
        "&" + "department_name=" + encodeURIComponent(this.wjSearchObj.department_name.text);
      this.urlparam +=
        "&" + "staff_name=" + encodeURIComponent(this.wjSearchObj.staff_name.text);
      this.urlparam +=
        "&" + "matter_no=" + encodeURIComponent(this.wjSearchObj.matter_no.text);
      this.urlparam +=
        "&" + "matter_name=" + encodeURIComponent(this.wjSearchObj.matter_name.text);
      this.urlparam +=
        "&" + "product_code=" + encodeURIComponent(this.searchParams.product_code);
      this.urlparam +=
        "&" + "maker_name=" + encodeURIComponent(this.wjSearchObj.maker_name.text);
      this.urlparam +=
        "&" + "supplier_name=" + encodeURIComponent(this.wjSearchObj.supplier_name.text);
      this.urlparam += "&" + "order_no=" + encodeURIComponent(this.wjSearchObj.order_no.text);
      this.urlparam +=
        "&" + "warehouse_name=" + encodeURIComponent(this.wjSearchObj.warehouse_name.text);
      this.urlparam +=
        "&" + "from_date=" + encodeURIComponent(this.searchParams.from_date);
      this.urlparam += "&" + "to_date=" + encodeURIComponent(this.searchParams.to_date);
      var listUrl = "/arrival-result" + this.urlparam;
      window.onbeforeunload = null;
      location.href = listUrl;
    },
  },
};
</script>
