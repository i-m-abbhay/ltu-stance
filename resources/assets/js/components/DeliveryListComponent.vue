<template>
  <div>
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
        <div class="row">
          <div class="col-md-3">
            <label class="control-label">トラック</label>
            <wj-auto-complete
              class="form-control"
              id="acShelfnumber"
              search-member-path="shelf_area"
              display-member-path="shelf_area"
              :items-source="shelfnumberlist"
              selected-value-path="shelf_area"
              :selected-index="-1"
              :min-length="1"
              :lost-focus="selectShelfnumber"
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
        border
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column label width="120">
          <template slot-scope="scope">
            <a class="btn btn-info" @click="showComment(scope.$index)">コメント</a>
          </template>
        </el-table-column>

        <el-table-column label="案件名" width="200">
          <template slot-scope="scope">{{scope.row.matter_name}}</template>
        </el-table-column>

        <el-table-column prop="address" label="住所" width="200"></el-table-column>
        <el-table-column prop="product_count" label="商材数" width="150"></el-table-column>
        <el-table-column label="納品処理" width="100">
          <template slot-scope="scope">
            <a class="btn btn-info" :href="scope.row.editurl">納品</a>
          </template>
        </el-table-column>
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
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,

    searchParams: {
      shelf_area: ""
    },
    tableData: [],
    urlparam: "",
    comment: "",
    commentDialog: false
  }),
  props: {
    shelfnumberlist: Array
  },
  created: function() {
    // コンボボックスの先頭に空行追加
    this.shelfnumberlist.splice(0, 0, { shelf_area: "　" });
  },
  mounted: function() {
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
      params.append("shelf_area", this.searchParams.shelf_area);
      axios
        .post("/delivery-list/search", params)

        .then(
          function(response) {
            this.loading = false;

            if (response.data) {
              this.tableData = response.data;
              for (var i = 0; i < this.tableData.length; i++) {
                this.tableData[i].editurl =
                  "delivery-process?matter_no=" + this.tableData[i].matter_no + "&shelf_area=" +  this.searchParams.shelf_area;
              }
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
    showComment($index) {
      this.comment = this.tableData[$index].shipment_comment;
      this.commentDialog = true;
    },
    handleClose(done) {
      done();
    },
    selectShelfnumber: function(sender) {
      this.searchParams.shelf_area =
        sender.selectedValue !== undefined && sender.selectedValue !== null
          ? sender.selectedValue
          : "";
    }
  }
};
</script>
<style>
.el-table th {
  background-color: #f5f8fa;
}
</style>