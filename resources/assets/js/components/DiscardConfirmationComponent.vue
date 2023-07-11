<template>
  <div class>
    <loading-component :loading="loading" />
    <!-- 検索結果グリッド -->
    <div class="col-md-12">
      <h4>
        <u>廃棄商材</u>
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
          <template slot-scope="scope">{{scope.row.product_name}}</template>
        </el-table-column>

        <el-table-column label="数量" width="100">
          <template slot-scope="scope">{{scope.row.quantity}}</template>
        </el-table-column>
      </el-table>
    </div>

    <div class="col-md-12 text-left">
      <br />
      <p>これでよろしければ管理責任者のサインを受領してください。</p>
      <a class="btn btn-primary" @click="clickSign">サイン</a>
      <br />
      <img v-bind:src="sign" width="60%" />
    </div>

    <div class="col-md-12 text-center">
      <br />
    </div>
    <div class="col-md-12 text-center">
      <a class="btn btn-primary" @click="back">戻る</a>
      <button type="button" class="btn btn-primary" v-bind:disabled="isSaved" @click="process">廃棄完了</button>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    isSaved: false,
    shelfNumberId: null,
    wareHouseId: null,
    wareHouseName: null,
    shelfArea: "",
    tableCount: 0,
    tableData: [],
    sign: null
  }),

  props: {},
  created: function() {},
  mounted: function() {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    this.sign = JSON.parse(localStorage.getItem("sign"));
  },

  methods: {
    checkTable: function() {
      return this.tableData.filter(
        row => row.quantity == row.returns_quantity && row.quantity != 0
      );
    },
    logErrors(promise) {
      promise.catch(console.error);
    },
    process() {
      this.loading = true;
      this.isSaved = true;
      axios
        .post("/discard-confirmation/save", {
          tableData: this.checkTable(),
          sign: this.sign
        })

        .then(
          function(response) {
            if (response.data) {
              var url = "/discard-list";
              window.onbeforeunload = null;
              location.href = url;
            } else {
              alert(MSG_ERROR);
              this.isSaved = false;
            }
            this.loading = false;
          }.bind(this)
        )
        .catch(
          function(error) {
            this.loading = false;
            this.isSaved = false;
            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
          }.bind(this)
        );
    },
    //サイン
    clickSign() {
      var listUrl = "/discard-sign";
      localStorage.setItem("tableData", JSON.stringify(this.tableData));
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // 戻る
    back() {
      var listUrl = "/discard-list";
      window.onbeforeunload = null;
      location.href = listUrl;
    }
  }
};
</script>

<style>
</style>
