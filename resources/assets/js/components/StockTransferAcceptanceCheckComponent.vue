<template>
  <div class>
        <loading-component :loading="loading" />
    <div class="col-md-12">
      <h4>
        <u>倉庫名</u>
      </h4>
    </div>
    <div class="col-md-12">
      <p>{{wareHouseName}}</p>
    </div>
    <div class="col-md-12">
      <h4>
        <u>棚番名</u>
      </h4>
    </div>
    <div class="col-md-12">
      <p>{{shelfArea}}</p>
    </div>

    <!-- 検索結果グリッド -->
    <div class="col-md-12">
      <h4>
        <u>移管受入商材</u>
      </h4>
    </div>
    <div class="grid-form">
      <el-table
        :data="checkTable()"
        :default-sort="{prop: 'product_code', order: 'ascending'}"
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label="商品名" width="400">
          <template slot-scope="scope">{{scope.row.product_name_only}}</template>
        </el-table-column>

        <el-table-column label="数量" width="100">
          <template slot-scope="scope">{{scope.row.quantity}}</template>
        </el-table-column>
      </el-table>
    </div>

    <div class="col-md-12 text-center">
      <br />
      <a class="btn btn-primary" @click="back">戻る</a>
      <button type="button" class="btn btn-primary" @click="process" :disabled="isComp">受入完了</button>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    shelfNumberId: null,
    wareHouseId: null,
    wareHouseName: null,
    shelfArea: "",
    tableCount: 0,
    tableData: [],
    scanData: [],
    isComp:false,
    stockFlg: {
      itemsOrdered: 0, //発注品
      stock: 1, //在庫品
      custody: 2 //預かり品
    },
    moveKind: {
      warehouseMovement: 0, //倉庫移動
      redelivery: 1, //再配送
      returns: 2 //返品
    }
  }),

  props: {},
  created: function() {},
  mounted: function() {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.scanData = JSON.parse(localStorage.getItem("scanData")) || [];
    this.shelfNumberId =
      JSON.parse(localStorage.getItem("shelfNumberId")) || [];
    this.wareHouseId = JSON.parse(localStorage.getItem("wareHouseId")) || [];
    this.wareHouseName =
      JSON.parse(localStorage.getItem("wareHouseName")) || [];
    this.shelfArea = JSON.parse(localStorage.getItem("shelfArea")) || [];
  },

  methods: {
    checkTable: function() {
      return this.tableData.filter(
        row => row.quantity == row.acceptance_quantity && row.quantity != 0
      );
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    process() {
      this.isComp = true;
      axios
        .post("/stock-transfer-acceptance-check/save", {
          table: this.checkTable(),
          scanData:this.scanData,
          wareHouseId: this.wareHouseId,
          shelfNumberId: this.shelfNumberId
        })

        .then(
          function(response) {
            var data = response.data;
            if (data == "printError") {
              alert("印刷エラーが発生しました。");
              var url = "/stock-transfer-acceptance";
              window.onbeforeunload = null;
              location.href = url;
            }else if(Array.isArray(data) && data.length > 0 && data[0] == "stockError"){
              alert(data[1]);
              this.isComp = false;
            }
            else if (response.data) {
              var url = "/stock-transfer-acceptance";
              window.onbeforeunload = null;
              location.href = url;

              url = response.data;
              var pattern = "smapri:";
              if (url.indexOf(pattern) > -1) {
                // iosの場合
                window.location.href = url;
              }
            } else {
              alert(MSG_ERROR);
            }
          }.bind(this)
        )
        .catch(
          function(error) {
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
    // 戻る
    back() {
      var listUrl = "/stock-transfer-acceptance-shelf-qr";
      window.onbeforeunload = null;
      location.href = listUrl;
    }
  }
};
</script>

<style>
</style>
