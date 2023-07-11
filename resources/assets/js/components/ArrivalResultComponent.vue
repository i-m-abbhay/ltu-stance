<template>
  <div>
    <!-- ステップ -->
    <!-- <div class="col-md-12 subheader-step">
      <br />
      <el-steps :active="2" finish-status="success">
        <el-step title="Step 1"></el-step>
        <el-step title="Step 2"></el-step>
        <el-step title="Step 3"></el-step>
      </el-steps>
      <div class="col-md-2">ＳＴＥＰ２：</div>
      <div class="col-md-6">入荷情報の検索結果から入荷処理する項目を選択します。</div>
      <br />
    </div>-->

    <!-- 検索結果グリッド -->
    <div class="grid-form" id="gridForm">
      <form
        id="gridForm"
        name="gridForm"
        class="form-horizontal"
        method="post"
        action="/base-list/search"
      >
        <div class="pull-right">
          <p class="pull-right search-count">検索結果： {{ tableData.length }}件</p>
        </div>
        <el-table
          :data="tableData"
          :default-sort="{ prop: 'id', order: 'ascending' }"
          v-loading="loading"
          :cell-class-name="tableCellClassName"
          style="width: 100%"
          @selection-change="handleSelectionChange"
        >
          <el-table-column type="selection" property="check_box" width="35">
          </el-table-column>

          <el-table-column label="入荷予定日" width="160" prop="arrival_plan_date">
            <el-table-column label="発注番号" width="160" prop="order_no">
              <template slot-scope="scope">
                {{ scope.row.arrival_plan_date }}

                <br />

                {{ scope.row.order_no }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="案件番号" prop="matter_no" width="200">
            <el-table-column label="案件名" prop="matter_name" width="200">
              <template slot-scope="scope">
                {{ scope.row.matter_no }}
                <br />
                {{ scope.row.matter_name }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="主要商品名" prop="product_name" width="250">
            <el-table-column label="その他商品数" prop="product_count" width="250">
              <template slot-scope="scope">
                {{ scope.row.product_name }}
                <br />
                {{ scope.row.product_count }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="メーカー" width="200" prop="maker_name">
            <el-table-column label="仕入先" width="200" prop="supplier_name">
              <template slot-scope="scope">
                {{ scope.row.maker_name }}
                <br />
                {{ scope.row.supplier_name }}
              </template>
            </el-table-column>
          </el-table-column>

          <el-table-column label="得意先" width="200" prop="customer_name">
            <el-table-column label="届け先" width="200" prop="warehouse_name">
              <template slot-scope="scope">
                {{ scope.row.customer_name }}
                <br />
                {{ scope.row.warehouse_name }}
              </template>
            </el-table-column>
          </el-table-column>
        </el-table>
        <div class="col-md-12 text-center">
          <br />

          <button type="button" class="btn btn-warning btn-back" v-on:click="back">
            戻る
          </button>
          <button
            type="button"
            class="btn btn-warning btn-back"
            v-on:click="clickProcessBtn"
          >
            次へ
          </button>
          <br />
          <br />
        </div>
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
    arrivalCol: 1,
    tableData: [],
    today: new Date(),
    multipleSelection: [],
    urlparam: "",
  }),
  props: {},
  mounted: function () {
    var query = window.location.search;
    if (query.length > 1) {
      // 検索条件セット
      this.setSearchParams(query, this.searchParams);
      // 検索
      this.search();
    }
  },
  methods: {
    // 検索
    search() {
      this.loading = true;

      var params = {
        customer_id: this.searchParams.customer_id,
        department_name: this.searchParams.department_name,
        staff_name: this.searchParams.staff_name,
        matter_no: this.searchParams.matter_no,
        matter_name: this.searchParams.matter_name,
        product_code: this.searchParams.product_code,
        maker_name: this.searchParams.maker_name,
        supplier_name: this.searchParams.supplier_name,
        order_no: this.searchParams.order_no,
        warehouse_name: this.searchParams.warehouse_name,
        from_date: this.searchParams.from_date,
        to_date: this.searchParams.to_date,
      };
      axios
        .post("/arrival-result/search", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.tableData = response.data;
              for (var i = 0; i < this.tableData.length; i++) {
                this.tableData[i].editurl =
                  "arrival-process/" + this.tableData[i].order_no + this.urlparam;
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
    handleSelectionChange(val) {
      this.multipleSelection = val;
    },
    // 処理ボタン
    clickProcessBtn() {
      // this.urlparam = "?";
      // this.urlparam += "customer_id=" + encodeURIComponent(this.searchParams.customer_id);
      // this.urlparam += "&" + "department_name=" + encodeURIComponent(this.searchParams.department_name);
      // this.urlparam += "&" + "staff_name=" + encodeURIComponent(this.searchParams.staff_name);
      // this.urlparam += "&" + "matter_no=" + encodeURIComponent(this.searchParams.matter_no);
      // this.urlparam += "&" + "matter_name=" + encodeURIComponent(this.searchParams.matter_name);
      // this.urlparam += "&" + "product_code=" + encodeURIComponent(this.searchParams.product_code);
      // this.urlparam += "&" + "maker_name=" + encodeURIComponent(this.searchParams.maker_name);
      // this.urlparam += "&" + "supplier_name=" + encodeURIComponent(this.searchParams.supplier_name);
      // this.urlparam += "&" + "order_no=" + encodeURIComponent(this.searchParams.order_no);
      // this.urlparam += "&" + "warehouse_name=" + encodeURIComponent(this.searchParams.warehouse_name);
      // this.urlparam += "&" + "from_date=" + encodeURIComponent(this.searchParams.from_date);
      // this.urlparam += "&" + "to_date=" + encodeURIComponent(this.searchParams.to_date);

      if (this.multipleSelection.length > 0) {
        //複数倉庫はNG
        var warehouse = null;
        var isError = false;
        this.multipleSelection.forEach((element) => {
          if (warehouse === null) {
            warehouse = element.warehouse_name;
          } else if (warehouse != element.warehouse_name) {
            isError = true;
          }
        });
        if (isError) {
          alert("届け先倉庫が2つ以上選択されています。");
        } else {
          //次画面に遷移
          localStorage.setItem(
            "multipleSelection",
            JSON.stringify(this.multipleSelection)
          );
          var listUrl = "/arrival-process" + window.location.search;
          window.onbeforeunload = null;
          location.href = listUrl;
        }
      } else {
        alert("入荷対象を選択してください。");
      }
    },
    // 戻る
    back() {
      var listUrl = "/arrival-search" + window.location.search;
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    tableCellClassName({ row, column, rowIndex, columnIndex }) {
      if (
        row.arrival_plan_date != null &&
        columnIndex == this.arrivalCol &&
        row.arrival_plan_date ==
          this.today.getFullYear() +
            "-" +
            ("00" + (this.today.getMonth() + 1)).slice(-2) +
            "-" +
            ("00" + this.today.getDate()).slice(-2)
      ) {
        return "arrival-cell";
      }
    },
  },
};
</script>
<style>
.el-table .arrival-cell {
  background-color: rgb(243, 255, 75) !important;
}
</style>
