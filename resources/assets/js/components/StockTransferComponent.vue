<template>
  <div class>
    <div class="row">
      <label class="col-md-12 text-left">移管日</label>
      <div class="col-md-3">
        <input type="date" class="form-control" name="date1" v-model="searchParams.from_date" />
      </div>
      <span class="col-md-1 text-center">～</span>
      <div class="col-md-3">
        <input type="date" class="form-control" name="date2" v-model="searchParams.to_date" />
      </div>
    </div>

    <div class="row">
      <div class="col-md-3">
        <label class="control-label">倉庫</label>
        <wj-auto-complete
          class="form-control"
          id="acWarehouse"
          search-member-path="id"
          display-member-path="warehouse_name"
          :items-source="warehouselist"
          selected-value-path="id"
          :selected-value="searchParams.warehouse_id"
          :max-items="warehouselist.length"
          :lost-focus="selectWarehouse"
        ></wj-auto-complete>
      </div>

      <div class="col-md-2 text-right">
        <button
          type="submit"
          class="btn btn-primary btn-search"
          @click="search"
          style="margin-top:30px"
        >検索</button>
      </div>
    </div>

    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="tableData"
        :default-sort="{prop: 'product_code', order: 'ascending'}"
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label="移管元倉庫" width="300">
          <el-table-column label="移管先倉庫" width="300">
            <template slot-scope="scope">
              {{scope.row.from_warehouse_name}}
              <br />
              {{scope.row.to_warehouse_name}}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="商材数" width="100">
          <template slot-scope="scope">{{scope.row.quantity}}</template>
        </el-table-column>
        <el-table-column label="選択" width="100">
          <template slot-scope="scope">
            <el-checkbox true-label="1" false-label="0" v-model="tableData[scope.$index].check_box"></el-checkbox>
          </template>
        </el-table-column>
      </el-table>
    </div>

    <div class="col-md-12 text-center">
      <br />
      <a class="btn btn-primary" @click="process">決定</a>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    searchParams: {
      warehouse_id: 0,
      from_date: "",
      to_date: ""
    },
    tableCount: 0,
    tableData: []
  }),

  props: {
    warehouselist: Array,
    defaultwarehouseid: null,
  },
  created: function() {
  },
  mounted: function() {
    this.searchParams.warehouse_id = this.defaultwarehouseid;
  },
  methods: {
    checkTable: function() {
      return this.tableData.filter(row => row.check_box == true);
    },
    search() {
      this.loading = true;

      var params = new URLSearchParams();
      params.append("warehouse_id", this.searchParams.warehouse_id);
      params.append("from_date", this.searchParams.from_date);
      params.append("to_date", this.searchParams.to_date);
      axios
        .post("/stock-transfer/search", params)

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

    logErrors(promise) {
      promise.catch(console.error);
    },
    process() {
      if (this.checkTable().length > 0) {
        this.loading = true;
        localStorage.setItem("tableData", JSON.stringify(this.checkTable()));
        localStorage.setItem(
          "to_date",
          JSON.stringify(this.searchParams.to_date)
        );
        localStorage.setItem(
          "from_date",
          JSON.stringify(this.searchParams.from_date)
        );
        var listUrl = "/stock-transfer-search";
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },

     selectWarehouse: function (sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.searchParams.warehouse_id = item.id;
      } else {
        this.searchParams.warehouse_id = 0;
      }
    },
  }
};
</script>

<style>
</style>
