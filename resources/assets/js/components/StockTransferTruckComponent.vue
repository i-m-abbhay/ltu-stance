<template>
  <div class>
    <loading-component :loading="loading" />
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <div class="row">
        <div class="col-md-12">
          <h4>
            <u>トラック登録</u>
          </h4>
        </div>
        <div class="col-md-3">
          <label class="control-label">配送トラックを選択してください。</label>
          <wj-auto-complete
            class="form-control"
            id="acShelfnumber"
            search-member-path="shelf_area"
            display-member-path="shelf_area"
            :items-source="shelfnumberlist"
            selected-value-path="shelf_area"
            :min-length="1"
            :lost-focus="selectShelfnumberList"
          ></wj-auto-complete>
        </div>
      </div>
    </div>

    <!-- 検索結果グリッド -->
    <div class="col-md-12">
      <h4>
        <u>移管先一覧</u>
      </h4>
    </div>
    <div class="grid-form">
      <el-table
        :data="tableData"
        :default-sort="{prop: 'product_code', order: 'ascending'}"
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label="移管先倉庫" width="300">
          <template slot-scope="scope">{{scope.row.to_warehouse_name}}</template>
        </el-table-column>

        <el-table-column label="商材数" width="100">
          <template slot-scope="scope">{{scope.row.product_quantity}}</template>
        </el-table-column>
      </el-table>
    </div>
    <br />
    <div class="col-md-12 text-center">
      <label class="control-label">これでよろしければ移管登録ボタンを押してください。</label>

      <br />
      <a class="btn btn-primary" @click="back">戻る</a>
      <button class="btn btn-primary" v-bind:disabled="isSaved" @click="process">移管登録</button>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    isSaved: false,
    searchParams: {
      warehouse_id: "",
      from_date: "",
      to_date: ""
    },
    tableCount: 0,
    tableData: [],
    productData: [],
    id: [], //倉庫移管テーブルの主キーリスト
    searchParams: {
      shelf_area: ""
    },
    selectedIndex: 0
  }),

  props: {
    shelfnumberlist: Array
  },
  created: function() {
    // コンボボックスの先頭に空行追加
    this.shelfnumberlist.splice(0, 0, "");
  },
  mounted: function() {
    this.productData = JSON.parse(localStorage.getItem("productData")) || [];
    this.loading = true;

    for (var i = 0; i < this.productData.length; i++) {
      this.id.push(this.productData[i].id);
    }
    var params = { id: this.id };
    axios
      .post("/stock-transfer-truck/search", params)

      .then(
        function(response) {
          if (response.data) {
            this.tableData = response.data;
          }

          this.loading = false;
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
  methods: {
    logErrors(promise) {
      promise.catch(console.error);
    },
    process() {
      if (this.selectedIndex > 0) {
        this.loading = true;
        this.isSaved = true;

        axios
          .post("/stock-transfer-truck/save", {
            id: this.id,
            productData: this.productData,
            to_warehouse_id: this.shelfnumberlist[this.selectedIndex]
              .warehouse_id,
            to_shelf_number_id: this.shelfnumberlist[this.selectedIndex]
              .id
          })

          .then(
            function(response) {
              if (response.data) {
                var listUrl = "/stock-transfer";
                window.onbeforeunload = null;
                location.href = listUrl;
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
      }
    },

    selectShelfnumberList: function(sender) {
      this.searchParams.shelf_area =
        sender.selectedValue !== undefined && sender.selectedValue !== null
          ? sender.selectedValue
          : "";
      this.selectedIndex = sender.selectedIndex;
    },
    // 戻る
    back() {
      var listUrl = "/stock-transfer-search";
      window.onbeforeunload = null;
      location.href = listUrl;
    }
  }
};
</script>

<style>
</style>
