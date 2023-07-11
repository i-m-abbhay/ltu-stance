<template>
  <div>
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
        <div class="row">
          <label class="col-md-12 text-left">出荷日</label>
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
              :min-length="1"
              :selected-value="searchParams.warehouse_id"
              :lost-focus="selectWarehouse"
            ></wj-auto-complete>
          </div>
          <!--                <br />
                    <div class="col-md-3">
                        <el-checkbox true-label="1" false-label="0" v-model="searchParams.show_only_mine">自部門倉庫出荷のみを表示</el-checkbox>
                    </div>
          -->
        </div>
        <div class="pull-right">
          <button type="submit" class="btn btn-primary btn-search">検索</button>
        </div>

        <div class="clearfix"></div>
      </form>
    </div>

    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="tableData"
        :default-sort="{prop: 'id', order: 'ascending'}"
        v-loading="loading"
        border
        style="width: 100%"
      >
        <el-table-column label width="120">
          <template slot-scope="scope">
            <a class="btn btn-info" @click="showComment(scope.$index)">コメント</a>
          </template>
        </el-table-column>
        <el-table-column prop="delivery_date" label="日付" width="150"></el-table-column>
        <el-table-column label="時間指定" width="100">
          <template
            slot-scope="scope"
          >{{("00" + tableData[scope.$index].delivery_time).substr(-4, 2) + ":" + ("00" + tableData[scope.$index].delivery_time).substr(-2, 2) }}</template>
        </el-table-column>
        <el-table-column prop="matter_name" label="案件名" width="200"></el-table-column>


          <el-table-column label="配送先名" width="300">
          <el-table-column label="配送先住所" width="300">
            <template slot-scope="scope">
              <span v-show="scope.row.shipment_kind==0">案件現場</span>
              <span v-show="scope.row.shipment_kind==1">{{  scope.row.customer_name}}</span>
              <span v-show="scope.row.shipment_kind==2">{{  scope.row.branch_name}}</span>
              <span v-show="scope.row.shipment_kind==3">引取</span>
              <br />
              {{tableData[scope.$index].address1 + tableData[scope.$index].address2}}
            </template>
          </el-table-column>
        </el-table-column>

        <el-table-column label="選択" width="100">
          <template slot-scope="scope">
            <!--<el-checkbox v-model="scope.row.isEnabled" ></el-checkbox>-->
            <el-checkbox true-label="1" false-label="0" v-model="tableData[scope.$index].check"></el-checkbox>
          </template>
        </el-table-column>
        <!--            <el-table-column
                    label="出荷手続"
                    width="100">             
                    <template slot-scope="scope">
                        <a class="btn btn-edit" :href="scope.row.url + urlparam">出荷</a>
                    </template>
                </el-table-column>
        -->
      </el-table>
      <!-- コメントダイアログ -->
      <el-dialog title="コメント" :visible.sync="commentDialog" width="80%" :before-close="handleClose">
        <div class="row">
          <div class="col-md-10">
            <label class="control-label">{{this.comment}}</label>
          </div>
        </div>
        <span slot="footer" class="dialog-footer">
          <el-button @click="commentDialog = false">閉じる</el-button>
        </span>
      </el-dialog>
      <div class="col-md-12 text-center">
        <br />
        <a class="btn btn-primary" @click="process">決定</a>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,

    searchParams: {
      shipment_id: [],
      from_date: "",
      to_date: "",
      warehouse_id: 0,
    },
    tableData: [],
    urlparam: "",
    comment: "",
    commentDialog: false,
  }),
  props: {
    warehouselist: Array,
    defaultwarehouseid: null,
  },
  created: function () {
    // コンボボックスの先頭に空行追加
    this.warehouselist.splice(0, 0, { id: 0, warehouse_name: "　" });
  },
  mounted: function () {
    // localStorage 初期化
    localStorage.removeItem("shipment_id");
    localStorage.removeItem("shipment_detail_id");
    localStorage.removeItem("loading_quantity");
    this.searchParams.warehouse_id = this.defaultwarehouseid;
  },
  methods: {
    search() {
      this.loading = true;

      this.tableData = [];
      var params = new URLSearchParams();
      params.append("from_date", this.searchParams.from_date);
      params.append("to_date", this.searchParams.to_date);
      params.append("warehouse_id", this.searchParams.warehouse_id);
      axios
        .post("/shipping-list/search", params)

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

            if (error.response.data.message) {
              alert(error.response.data.message);
            } else {
              alert(MSG_ERROR);
            }
            location.reload();
          }.bind(this)
        );
    },
    showComment($index) {
      this.comment = this.tableData[$index].shipment_comment;
      this.commentDialog = true;
    },
    handleClose(done) {
      done();
    },
    selectWarehouse: function (sender) {
      var item = sender.selectedItem;
      if (item !== null) {
        this.searchParams.warehouse_id = item.id;
      } else {
        this.searchParams.warehouse_id = 0;
      }
    },
    process() {
      this.searchParams.shipment_id = [];
      for (var i = 0; i < this.tableData.length; i++) {
        if (this.tableData[i].check == "1") {
          this.searchParams.shipment_id.push(this.tableData[i].id);
        }
      }
      if (this.searchParams.shipment_id.length > 0) {
        localStorage.setItem(
          "shipment_id",
          JSON.stringify(this.searchParams.shipment_id)
        );
        var listUrl = "/shipping-process";
        window.onbeforeunload = null;
        location.href = listUrl;
      }
    },
  },
};
</script>

<style>
.el-table th {
  background-color: #f5f8fa;
}
</style>
