<template>
  <div>
    <div class="row" v-loading="loading">
      <!-- <loading-component :loading="loading" /> -->
      <div class="col-lg-12 col-md-12">
        <!-- ヘッダ -->
        <div class="header-box col-lg-11 col-md-11">
          <div class="pull-left"><h4>売上情報</h4></div>
          <div class="pull-left left-border">Sales Info<br />DATA</div>
        </div>
        <!-- 更新ボタン -->
        <div class="col-lg-1 col-md-1 text-right">
          <span class="input-group-btn">
            <el-button
              icon="el-icon-refresh"
              circle
              class="input-group-circle-btn"
              v-on:click="btnSearch"
            ></el-button>
          </span>
        </div>
      </div>
      <!-- グラデーション -->
      <div class="col-lg-12 col-md-12">
        <div class="gradation-line"></div>
      </div>
      <br />

      <!-- メイン -->
      <label class="col-lg-12 col-md-12">&emsp;</label>
      <div class="col-lg-12 col-md-12">
        <div class="col-lg-9 col-md-12">
          <label class="col-lg-12 col-md-12">&emsp;</label>
          <div class="col-lg-12 col-md-12 text-right">
            <h4>単位 （円）</h4>
          </div>

          <!-- 目標額 -->
          <div class="col-lg-6 col-md-12">
            <div class="target-salesarea">
              <!-- <label class="text-left title-text">目標額</label> (PROFIT AMOUNT)-->
              <label class="text-left title-text">利益額</label>
              <!-- el-icon-setting -->
              <br />
              <div class="text-right">
                <label class="money-text"
                  >￥{{
                    totalAmounts.totalSales
                      | salesData.targetSales
                      | comma_format
                  }}</label
                >
              </div>
            </div>
          </div>
          <!-- 売上実績 (SALES AMOUNT)-->
          <div class="col-lg-6 col-md-12">
            <div class="sales-amountarea">
              <label class="text-left title-text">売上額</label>
              <div class="text-right">
                <label class="money-text"
                  >￥{{
                    totalAmounts.totalProfit
                      | salesData.salesAmount
                      | comma_format
                  }}</label
                >
              </div>
            </div>
          </div>
        </div>

        <!-- 半円グラフ -->
        <div class="col-lg-3 col-md-12">
          <div class="gauge-area">
            <label class="pull-left">目標達成率</label>
            <div class="gauge" id="salesGauge">
              {{ salesData.targetAchievementRate }}%
            </div>
          </div>
        </div>
      </div>
    </div>
    <!-- <div>
      {{ searchParams}}
    </div>
    <div>
      {{ wjSearchObj.department_id.selectedValue }}
    </div>
    <div>
      {{ wjSearchObj.staff_id.selectedValue }}
    </div>
    <div>
      {{ wjSearchObj.customer_id.selectedValue }}
    </div>
    <div>
      {{ wjSearchObj.sales_date.text}}
    </div> -->
    <!-- <div>
      {{ wjSearchObj }}
    </div>  -->
  </div>
</template>

<script>
/* TODO:app.jsで読み込んでいるので、出来るなら二重インポートしたくない */
import * as wjGrid from "@grapecity/wijmo.grid";
import * as wjCore from "@grapecity/wijmo";
import * as gauge from "@grapecity/wijmo.gauge";
import axios from "axios";

var DEFAULT_DEPARTMENT_DATA = {
  id: 99999999999,
  base_id: 99999999999,
  department_name: "すべて",
  department_symbol: "",
  open: true,
  parent_id: 99999999999,
};
var DEFAULT_STAFF_DATA = {
  id: 99999999999,
  staff_name: "すべて",
};
var DEFAULT_CUSTOMER_DATA = {
  id: 99999999999,
  customer_name: "すべて",
  charge_department_id: 99999999999,
  charge_staff_id: 99999999999,
  customer_short_name: "すべて",
};
export default {
  data: () => ({
    loading: false,
    FLG_ON: 1,
    FLG_OFF: 0,

    urlparam: "",
    totalAmounts: {
      totalSales: "",
      totalProfit: "",
    },

    salesData: {
      targetSales: 0,
      salesAmount: 0,
      targetAchievementRate: 0,
    },

    salesGaugeCtrl: null,
    gaugeRanges: null,
    gaugeObj: {
      sales: 0,
      prospect: 0,
      process: 0,
    },

    errors: {
      customer_id: "",
      staff_id: "",
      department_id: "",
      sales_date: "",
    },
  }),
  props: {
    searchParams: {
      type: Object,
      default: () => ({}), // Initialize selectstaff as an empty object
    },
    wjSearchObj: {},
  },
  created: function() {},
  watch: {
    searchParams(newValue) {
      console.log("staff Id changed:", newValue);
      this.fetchTotalAmount();
    },
  },
  mounted: function() {
    this.gaugeRanges = this.getGaugeRanges();

    this.salesGaugeCtrl = this.createGaugeCtrl("#salesGauge");
    // var total = this.bigNumberPlus(this.gaugeObj.sales, this.bigNumberPlus(this.gaugeObj.prospect, this.gaugeObj.process))
    // this.salesGaugeCtrl.value = total;

    this.btnSearch();

    this.fetchTotalAmount();
    console.log("total amount", this.totalAmounts);
  },
  methods: {
    /**
     * 【ボタンクリック】検索
     */
    fetchTotalAmount() {
      if (this.searchParams.staff_id === "") {
        console.log("I am here");
        return;
      } // No staff selected, do nothing

      console.log("staff id", this.searchParams.staff_id);

      axios
        .get(`/totalamount/${this.searchParams.staff_id}`)
        .then((response) => {
          console.log("total amounts", response);
          if (!response.data) return;
          this.totalAmounts.totalSales =
            response.data.result.total_sales_unit_price; // Assuming you only get one row of data
          this.totalAmounts.totalProfit = response.data.result.total_profit; // Assuming you only get one row of data
        })
        .catch((error) => {
          console.error(error);
        });
    },

    btnSearch() {
      // this.rmUndefinedZero(this.wjSearchObj.department_id.selectedValue) !== 0 &&
      if (this.rmUndefinedBlank(this.wjSearchObj.sales_date.text) !== "") {
        this.initErr(this.errors);
        this.$emit("sendError", this.errors);
        this.triggerSearch();
      } else {
        // this.errors.department_id = MSG_ERROR_NO_INPUT;
        this.errors.sales_date = MSG_ERROR_NO_INPUT;
        this.$emit("sendError", this.errors);
      }

      // GET TOTAL_SALES AND TOTAL_PROFIT
    },
    /**
     * 検索
     */
    async triggerSearch() {
      var params = new URLSearchParams();

      params.append(
        "department_id",
        this.rmUndefinedBlank(this.wjSearchObj.department_id.selectedValue)
      );
      params.append(
        "staff_id",
        this.rmUndefinedBlank(this.wjSearchObj.staff_id.selectedValue)
      );
      params.append(
        "customer_id",
        this.rmUndefinedBlank(this.wjSearchObj.customer_id.selectedValue)
      );
      params.append(
        "sales_date",
        this.rmUndefinedBlank(this.wjSearchObj.sales_date.text)
      );
      params.append(
        "period_kbn",
        this.rmUndefinedZero(this.searchParams.period_kbn)
      );

      var result = await this.search(params);
      console.log(result.total_profit.sum);
      if (result !== undefined) {
        this.salesData.salesAmount = result.total_sales.sum;
        this.salesData.targetSales = result.total_profit.sum;
      }
      // if (result !== undefined && result.status === true) {
      //   this.urlparam = "?";
      //   this.urlparam +=
      //     "department_id=" +
      //     encodeURIComponent(
      //       this.rmUndefinedBlank(this.wjSearchObj.department_id.selectedValue)
      //     );
      //   this.urlparam +=
      //     "&" +
      //     "staff_id=" +
      //     encodeURIComponent(
      //       this.rmUndefinedBlank(this.wjSearchObj.staff_id.selectedValue)
      //     );
      //   this.urlparam +=
      //     "&" +
      //     "customer_id=" +
      //     encodeURIComponent(
      //       this.rmUndefinedBlank(this.wjSearchObj.customer_id.selectedValue)
      //     );
      //   this.urlparam +=
      //     "&" +
      //     "sales_date=" +
      //     encodeURIComponent(
      //       this.rmUndefinedBlank(this.wjSearchObj.sales_date.text)
      //     );
      //   this.urlparam +=
      //     "&" +
      //     "period_kbn=" +
      //     encodeURIComponent(
      //       this.rmUndefinedBlank(this.searchParams.period_kbn)
      //     );

      //   // if (this.salesGaugeCtrl !== null) {
      //   //     this.salesGaugeCtrl.dispose();
      //   // }
      //   this.salesData.salesAmount = result.sum(profit_total);
      //   console.log('errorfree')
      //   if (this.salesData.targetSales !== 0) {
      //     this.salesData.targetAchievementRate = Math.floor(
      //       (this.salesData.salesAmount / this.salesData.targetSales) * 100
      //     );
      //   } else {
      //     this.salesData.targetAchievementRate = 0;
      //   }
      //   // this.createGaugeCtrl('#salesGauge');
      //   if (result.message !== "") {
      //     alert(result.message);
      //   }
      // } else {
      //   if (this.rmUndefinedBlank(result.message) !== "") {
      //     alert(result.message);
      //   } else {
      //     // 失敗
      //     alert(MSG_ERROR);
      //   }
      // }
    },
    // 検索
    search(params) {
      const data = {
        department_id: this.searchParams.department_id,
        staff_id: this.searchParams.staff_id,
        customer_id: this.searchParams.customer_id,
        sales_date: this.searchParams.sales_date,
        period_kbn: this.searchParams.period_kbn,
      };
      console.log(data);
      var promise = axios
        .post("/dashboard-sales-info/fetch", params)
        .then(
          function(response) {
            if (response.data) {
              // console.log(response.data);
              return response.data;
            }
          }.bind(this)
        )
        .catch(function(error) {}.bind(this))
        .finally(
          function() {
            this.loading = false;
          }.bind(this)
        );
      return promise;
      // this.loading = true;
      // console.log(params)
      // var promise = axios
      //   .post("/dashboard-sales-info/search", params)

      //   .then(
      //     function(response) {
      //       if (response.data) {
      //         // 成功
      //         console.log(response.data);
      //         return response.data;
      //       }
      //     }.bind(this)
      //   )
      //   .catch(
      //     function(error) {
      //       //alert(MSG_ERROR);
      //     }.bind(this)
      //   )
      //   .finally(
      //     function() {
      //       this.loading = false;
      //     }.bind(this)
      //   );
      // return promise;
    },

    // 半円グラフ作成
    createGaugeCtrl(targetDivId) {
      var total = this.bigNumberPlus(
        this.gaugeObj.sales,
        this.bigNumberPlus(this.gaugeObj.prospect, this.gaugeObj.process)
      );

      var gaugeCtrl = new gauge.RadialGauge(targetDivId, {
        isReadOnly: true,
        min: 0,
        max: 200,
        startAngle: 0,
        sweepAngle: 180,
        value: this.salesData.targetAchievementRate,
        // showRanges: true,
        autoScale: true,
        hasShadow: false,
        stackRanges: false,
        ranges: this.getGaugeRanges(),
      });

      // %の文字追加
      gaugeCtrl.getText = function(gauge, part, value, text) {
        switch (part) {
        }
        var total = this.bigNumberPlus(
          this.gaugeObj.sales,
          this.bigNumberPlus(this.gaugeObj.prospect, this.gaugeObj.process)
        );

        return total + "%";
      }.bind(this);

      return gaugeCtrl;
    },

    getGaugeRanges() {
      // 売上額
      // var salesMin = 0;
      var salesMax = this.gaugeObj.sales;
      // 見込み
      var adjust = 0;
      if (this.gaugeObj.sales > 0) {
        adjust = 1;
      }
      var prospectMin = this.bigNumberPlus(this.gaugeObj.sales, adjust);
      var prospectMax = this.bigNumberPlus(
        this.gaugeObj.sales,
        this.gaugeObj.prospect
      );
      // 仕掛
      adjust = 0;
      if (prospectMax > 0) {
        adjust = 1;
      }
      var processMin = this.bigNumberPlus(prospectMax, adjust);
      var processMax = this.bigNumberPlus(prospectMax, this.gaugeObj.process);

      return [
        { min: 0, max: 0, color: "#DF7128" },
        { min: 0, max: 0, color: "#E8C01A" },
        { min: 0, max: 0, color: "#9FAAA9" },
      ];
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
.target-salesarea {
  width: 100%;
  height: 180px;
  background: #ffffff;
  padding: 15px;
  margin-bottom: 20px;
  -webkit-box-shadow: 0 0 3px 3px #898989;
  box-shadow: 0 0 3px 3px #898989;
}
.sales-amountarea {
  width: 100%;
  height: 180px;
  background: #ffffff;
  padding: 15px;
  -webkit-box-shadow: 0 0 3px 3px #df7128;
  box-shadow: 0 0 3px 3px #df7128;
}
.gauge-area {
  width: 100%;
  height: 250px;
  background: #ffffff;
  padding: 15px;
  margin: 10px;
  -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
  box-shadow: 0 0 1px 1px rgba(0, 0, 0, 0.3);
}
.gauge {
  height: 200px;
}
.title-text {
  font-size: 36px;
}
.money-text {
  font-size: 36px;
}

.header-box {
  display: flex; /* 子要素をフレックスボックスで横並びにする */
  justify-content: left; /* 等間隔で左右に並べる */
  flex-wrap: wrap;
  margin-bottom: 10px;
}
.header-box div {
  width: 150px; /* 幅を適度な大きさに指定 */
  /* background: #ddd;       背景色をグレーに指定 */
  /* border: 1px solid #000; borderを引く */
}
.input-group-btn {
  width: 150px; /* 幅を適度な大きさに指定 */
}

.left-border {
  border-left: 1px solid rgba(0, 0, 0, 0.3); /* borderを引く */
  padding-left: 15px;
}

.gradation-line {
  background: -moz-linear-gradient(left, #2096d5, #003987);
  background: -webkit-linear-gradient(left, #2096d5, #003987);
  background: linear-gradient(to right, #2096d5, #003987);
  height: 10px;
  width: 100%;
  position: absolute;
  left: 0px;
  /* top: 70px; */
}
.loading {
  display: block;
  position: fixed;
  /* top: 0;
    left: 0; */
  width: 100%;
  height: 100%;
  z-index: 9999;
}
</style>
