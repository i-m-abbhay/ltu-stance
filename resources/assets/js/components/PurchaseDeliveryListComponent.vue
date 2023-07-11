<template>
  <div>
    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="tableData"
        :row-class-name="tableRowClassName"
        border
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label="選択" width="35">
          <template slot-scope="scope">
            <el-checkbox
              true-label="1"
              false-label="0"
              v-model="tableData[scope.$index].check_box"
              :disabled="
                scope.row.draft_flg == 1 ||
                scope.row.quantity <= scope.row.arrival_quantity
              "
            ></el-checkbox>
          </template>
        </el-table-column>

        <el-table-column label="商品番号" width="150">
          <template slot-scope="scope">{{ scope.row.product_code }}</template>
        </el-table-column>

        <el-table-column label="商品名" width="250">
          <el-table-column label="商品型式/規格" width="280">
            <template slot-scope="scope">
              {{ scope.row.product_name }}
              <br />
              {{ scope.row.model }}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="予定数量" width="100">
          <template slot-scope="scope">{{ scope.row.quantity }}</template>
        </el-table-column>
      </el-table>
    </div>

    <div class="col-md-12 text-center">
      <br />
      <a class="btn btn-primary" @click="process">実行</a>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    searchParams: {
      order_no: "",
    },
    tableData: [],
  }),
  // props: {
  //   shelfnumberlist: Array,
  // },
  created: function () {},
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
    search() {
      this.loading = true;

      this.tableData = [];
      var params = new URLSearchParams();
      params.append("order_no", this.searchParams.order_no);
      axios
        .post("/purchase-delivery-list/search", params)
        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              this.tableData = response.data;
            }
          }.bind(this)
        )

        .catch(
          function (error) {
            this.loading = false;

            alert(MSG_ERROR);

            location.reload();
          }.bind(this)
        );
    },
    process() {
      //チェック確認
      var isChecked = false;
      this.tableData.forEach((element) => {
        if (element.check_box==1) {
          isChecked = true;
        }
      });
      if (!isChecked) {
        alert("対象の行にチェックをつけてください。");
      } else {
        //写真撮影へ
        this.loading = true;
        var checkTable = this.tableData.filter((row) => row.check_box==1);
        localStorage.setItem("tableData", JSON.stringify(checkTable));
        var listUrl = "/purchase-delivery-photo?order_no=" + this.searchParams.order_no;
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },
    tableRowClassName({ row, rowIndex }) {
      if (row.draft_flg == 1 || row.quantity <= row.arrival_quantity) {
        return "draft-row";
      }
    },
  },
};
</script>
<style>
.el-table .draft-row {
  background: rgb(230, 230, 230);
}
</style>
