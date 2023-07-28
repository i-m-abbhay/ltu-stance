<template>
  <div>
    <loading-component :loading="loading" />
    <!-- 検索条件 -->
    <div class="col-md-12">
      <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
        <form
          id="searchForm"
          name="searchForm"
          class="form-horizontal"
          @submit.prevent="search"
        >
          <div class="row">
            <div
              class="col-md-2 col-sm-12"
              v-bind:class="{ 'has-error': errors.department_id != '' }"
            >
              <label class="control-label">部門名</label>
              <wj-auto-complete
                class="form-control"
                search-member-path="department_name"
                display-member-path="department_name"
                selected-value-path="id"
                :selected-index="-1"
                :selected-value="searchParams.department_id"
                :is-required="false"
                :selectedIndexChanged="changeIdxDepartment"
                :initialized="initDepartment"
                :max-items="departmentList.length"
                :items-source="departmentList"
              ></wj-auto-complete>
              <!-- :selectedIndexChanged="changeIdxDepartment" 
                              :selectedIndexChanged="changeDepartment"
-->
              <p class="text-danger">{{ errors.department_id }}</p>
            </div>
            <div
              class="col-md-2 col-sm-12"
              v-bind:class="{ 'has-error': errors.staff_id != '' }"
            >
              <label class="control-label">担当者名</label>
              <wj-auto-complete
                class="form-control"
                search-member-path="staff_name"
                display-member-path="staff_name"
                selected-value-path="id"
                :selected-index="-1"
                :selected-value="searchParams.staff_id"
                :is-required="false"
                :selectedIndexChanged="changeIdxStaff"
                :initialized="initStaff"
                :max-items="staffList.length"
                :items-source="staffList"
              ></wj-auto-complete>
              <!-- :selectedIndexChanged="changeIdxStaff" -->
              <p class="text-danger">{{ errors.staff_id }}</p>
            </div>
            <div
              class="col-md-3 col-sm-12"
              v-bind:class="{ 'has-error': errors.customer_id != '' }"
            >
              <label class="control-label">得意先名</label>
              <wj-auto-complete
                class="form-control"
                search-member-path="customer_name"
                display-member-path="customer_name"
                selected-value-path="id"
                :selected-index="-1"
                :selected-value="searchParams.customer_id"
                :selectedIndexChanged="changeIdxCustomer"
                :is-required="false"
                :initialized="initCustomer"
                :max-items="customerList.length"
                :items-source="customerList"
              ></wj-auto-complete>
              <p class="text-danger">{{ errors.customer_id }}</p>
            </div>
            <div
              class="col-md-2 col-sm-12"
              v-bind:class="{ 'has-error': errors.sales_date != '' }"
            >
              <label>対象月</label>
              <div class="input-group">
                <wj-input-date
                  class="form-control"
                  :value="searchParams.sales_date"
                  :selected-value="searchParams.sales_date"
                  :initialized="initDate"
                  :isRequired="false"
                  selectionMode="Month"
                  format="yyyy/MM"
                ></wj-input-date>
                <p class="text-danger">{{ errors.sales_date }}</p>
              </div>
            </div>
            <div class="form-group col-md-2 col-sm-12">
              <label class="col-md-12">&emsp;</label>
              <el-radio-group v-model="searchParams.period_kbn">
                <div class="radio col-md-6">
                  <el-radio :label="FLG_OFF">当月</el-radio>
                </div>
                <div class="radio col-md-6">
                  <el-radio :label="FLG_ON">全期</el-radio>
                </div>
              </el-radio-group>
            </div>

            <div class="col-md-1 col-sm-12" style="padding-top: 35px;">
              <div class="pull-right">
                <!-- <button type="submit" class="btn btn-search btn-md">
                  案件検索
                </button> -->
                &emsp;
                <button
                  type="button"
                  class="btn btn-clear btn-md"
                  v-on:click="clearSearch"
                >
                  クリア
                </button>
              </div>
            </div>
          </div>
          <br />
          <div class="clearfix"></div>
        </form>
      </div>
    </div>
    <br />
    <!-- <div>{{ searchParams.staff_id }}</div> -->
    <div class="col-md-12 col-sm-12">
      <div id="board-container" class="flex-container">
        <div id="f-contents-1" name="f-contents" class="search-body">
          <salesinfoboard-component
            :wjSearchObj="wjSearchObj"
            :searchParams="searchParams"
            @sendError="getError"
          ></salesinfoboard-component>
        </div>
      </div>
    </div>
  </div>
  <!-- </div> -->
</template>

<script>
import * as wjGrid from "@grapecity/wijmo.grid";
import * as wjCore from "@grapecity/wijmo";

// console.log("dashboard", searchParams.staff_id);

var DEFAULT_DEPARTMENT_DATA = {
  id: 99999999999,
  base_id: 0,
  department_name: "すべて",
  department_symbol: "",
  open: true,
  parent_id: 0,
};
var DEFAULT_STAFF_DATA = {
  id: 99999999999,
  staff_name: "すべて",
};
var DEFAULT_CUSTOMER_DATA = {
  id: 99999999999,
  customer_name: "すべて",
  charge_department_id: 0,
  charge_staff_id: 0,
  customer_short_name: "すべて",
};
export default {
  data: () => ({
    loading: false,
    FLG_ON: 1,
    FLG_OFF: 0,

    urlparam: "",

    layoutWidthValue: {
      hundled: "100%",
      fifty: "50%",
      twentyFive: "25%",
    },

    searchParams: {
      customer_id: "",
      staff_id: "",
      department_id: "",
      sales_date: null,
      period_kbn: 0,
    },
    wjSearchObj: {
      customer_id: {},
      staff_id: {},
      department_id: {},
      sales_date: {},
    },
    errors: {
      customer_id: "",
      staff_id: "",
      department_id: "",
      sales_date: "",
    },
  }),
  props: {
    initSearchParams: {},
    staffList: Array,
    departmentList: Array,
    staffDepartmentList: {},
    customerList: Array,
  },
  created: function() {
    this.queryparam = window.location.search;
    console.log(this.queryparam);
    if (this.queryparam.length > 1) {
      // 検索条件セット
      this.setSearchParams(this.queryparam, this.searchParams);
      // 日付に復帰させる検索条件がない場合はnullをセット
      if (this.searchParams.sales_date == "") {
        this.searchParams.sales_date = null;
      }
    } else {
      // 初回の検索条件をセット
      console.log(this.initSearchParams);
      this.setInitSearchParams(this.searchParams, this.initSearchParams);
    }
  },
  mounted: function() {
    console.log("mounted");
    var tmpStaff = this.wjSearchObj.staff_id.selectedValue;
    this.wjSearchObj.department_id.onSelectedIndexChanged();
    this.wjSearchObj.staff_id.selectedValue = tmpStaff;
    if (this.queryparam.length > 1) {
      // itemsSourceFunctionを動かす為
      this.search();
    }

    // 「すべて」をリストに追加
    // this.customerList.push(DEFAULT_CUSTOMER_DATA);
    // this.staffList.push(DEFAULT_STAFF_DATA);
    // this.departmentList.push(DEFAULT_DEPARTMENT_DATA);

    var boxList = document.getElementsByName("f-contents");
    // 各Boxへクラス付与
    boxList.forEach((element, i) => {
      // 並び順
      element.style.order = i + 1;
      // 横幅
      element.style.flexBasis = this.layoutWidthValue.hundled;

      element.classList.add("col-md-12");
    });
  },
  methods: {
    search() {
      console.log("search clicked");
      console.log(this.searchParams.department_id);
    },
    // エラー受け取り
    getError(err) {
      this.errors = err;
    },
    changeIdxDepartment: function(sender) {
      // this.wjSearchObj.department_id = sender;
      // 部門を変更したら担当者を絞り込む
      console.log("first");
      var tmpStaff = this.staffList;
      this.searchParams.department_id = sender.selectedValue;
      if (sender.selectedValue) {
        tmpStaff = [];
        if (this.staffDepartmentList[sender.selectedValue]) {
          tmpStaff = this.staffList.filter((rec) => {
            return (
              this.staffDepartmentList[sender.selectedValue].indexOf(rec.id) !==
              -1
            );
          });
        }
      }
      this.wjSearchObj.staff_id.itemsSource = tmpStaff;
      this.wjSearchObj.staff_id.selectedIndex = -1;
    },

    changeIdxStaff: function(sender) {
      // 担当者を変更したら得意先を絞り込む
      // console.log("second", searchParams);
      this.searchParams.staff_id = sender.selectedValue;
      var tmpCustomer = this.customerList;
      if (sender.selectedValue) {
        tmpCustomer = [];
        for (var key in this.customerList) {
          if (
            sender.selectedValue == this.customerList[key].charge_staff_id &&
            (this.wjSearchObj.department_id.selectedValue == null
              ? true
              : this.wjSearchObj.department_id.selectedValue ==
                this.customerList[key].charge_department_id)
          ) {
            tmpCustomer.push(this.customerList[key]);
          }
        }
      }
      this.wjSearchObj.customer_id.itemsSource = tmpCustomer;
      this.wjSearchObj.customer_id.selectedIndex = -1;
    },
    changeIdxCustomer: function(sender) {},
    clearSearch() {
      var searchParams = this.searchParams;
      Object.keys(searchParams).forEach(function(key) {
        searchParams[key] = "";
      });

      var wjSearchObj = this.wjSearchObj;
      Object.keys(wjSearchObj).forEach(function(key) {
        wjSearchObj[key].selectedValue = null;
        wjSearchObj[key].value = null;
      });
      // searchParams.sales_date = null;
      searchParams.period_kbn = this.FLG_OFF;
      searchParams.sales_date = this.initSearchParams.sales_date;
      wjSearchObj.sales_date.text = this.initSearchParams.sales_date;
    },
    initCustomer: function(sender) {
      this.wjSearchObj.customer_id = sender;
    },
    initStaff: function(sender) {
      this.wjSearchObj.staff_id = sender;
    },
    initDepartment: function(sender) {
      this.wjSearchObj.department_id = sender;
    },
    initDate: function(sender) {
      this.wjSearchObj.sales_date = sender;
    },
  },
};
</script>

<style>
/*********************************
    枠サイズ等
**********************************/
/* 検索項目 */
.search-body {
  width: 100%;
  background: #ffffff;
  padding: 15px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
/* 検索結果 */
.result-body {
  width: 100%;
  background: #ffffff;
  margin-top: 30px;
  padding: 15px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}

/* 枠サイズ */
.flex-container {
  display: flex;
  flex-wrap: wrap;
  justify-content: space-around;
  align-content: space-around;
}
.flex-item {
  /* 並び順 */
  order: 1;
  /* 横幅 */
  flex-basis: 100%;
}
</style>
