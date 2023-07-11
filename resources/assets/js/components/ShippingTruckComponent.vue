<template>
  <div>
    <loading-component :loading="loading" />
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <div class="row">
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
            :selected-index="-1"
            :lost-focus="selectShelfnumberList"
          ></wj-auto-complete>
        </div>
      </div>
    </div>

    <!-- 検索結果グリッド -->
    <div class="grid-form">
      <el-table
        :data="groupTable()"
        :default-sort="{prop: 'id', order: 'ascending'}"
        border
        v-loading="loading"
        style="width: 100%"
      >
        <el-table-column prop="matter_name" label="案件名" width="300"></el-table-column>
        <el-table-column prop="product_count" label="商材数" width="150"></el-table-column>
      </el-table>
      <!-- 配送確認ダイアログ -->
      <el-dialog title="荷積完了" :visible.sync="processDialog" width="80%" :before-close="handleClose" :showClose="false" :closeOnClickModal="false" :closeOnPressEscape="false">
        <div class="row">
          <div class="col-md-10">
            荷積を行いました。
            <br />
          </div>
        </div>
        <span slot="footer" class="dialog-footer">
          <el-button type="primary" @click="back_search">閉じる</el-button>
        </span>
      </el-dialog>
    </div>
    <div class="col-md-12 text-center">
      <br />
      <button type="button" class="btn btn-warning btn-back" v-on:click="back">戻る</button>
      <button type="button" class="btn btn-primary btn-save" v-bind:disabled="isSaved" v-on:click="process">荷積完了</button>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    isSaved: false,

    searchParams: {
      shipment_id: [],
      shipment_detail_id: [],
      loading_quantity: [],
      shelf_area: "",
      warehouse_id: null,
      shelf_number_id: null
    },
    errors: {
      change_quantity: ""
    },
    tableData: [],
    detailData: [],
    urlparam: "",
    id: "",
    numberDialog: false,
    processDialog: false,
    product_name: "",
    shipment_quantity: 0,
    change_quantity: 0,
    current: 0,
    result: "",
    message: "",
    noStreamApiSupport: false,
    detail_index: 0
  }),
  props: {
    shelfnumberlist: Array
  },
  created: function() {
  },
  mounted: function() {
    this.searchParams.shipment_id =
      JSON.parse(localStorage.getItem("shipment_id")) || [];
    this.searchParams.shipment_detail_id =
      JSON.parse(localStorage.getItem("shipment_detail_id")) || [];
    this.searchParams.loading_quantity =
      JSON.parse(localStorage.getItem("loading_quantity")) || [];
    this.detailData = JSON.parse(localStorage.getItem("tableData"));

    var query = window.location.search;
    // 検索条件セット
    this.setSearchParams(query, this.searchParams);
    // // 検索
    // this.search();
  },
  methods: {
    groupTable: function() {
      var group = this.detailData.reduce(function(result, current) {
        var element = result.find(function(p) {
          return p.matter_id == current.matter_id;
        });
        if (element) {
          element.product_count++; // count
        } else {
          result.push({
            matter_id:current.matter_id,
            matter_name: current.matter_name,
            product_count: 1
          });
        }
        return result;
      }, []);

      return group;
    },
    // search() {
    //   this.loading = true;

    //   var params = new URLSearchParams();
    //   params.append("shipment_id", this.searchParams.shipment_id);
    //   axios
    //     .post("/shipping-truck/search", params)

    //     .then(
    //       function(response) {
    //         this.loading = false;

    //         if (response.data) {
    //           this.tableData = response.data;
    //         }
    //       }.bind(this)
    //     )

    //     .catch(
    //       function(error) {
    //         this.loading = false;

    //         if (error.response.data.message) {
    //           alert(error.response.data.message);
    //         } else {
    //           alert(MSG_ERROR);
    //         }
    //         location.reload();
    //       }.bind(this)
    //     );
    // },
    // searchDetail() {
    //     var params = new URLSearchParams();
    //     params.append('shipment_id', this.searchParams.shipment_id);
    //     axios.post('/shipping-process/search', params)

    //     .then( function (response) {
    //         if (response.data) {
    //             this.detailData = response.data
    //             this.progressMain()
    //         }

    //     }.bind(this))

    //     .catch(function (error) {
    //         if (error.response.data.message) {
    //             alert(error.response.data.message)
    //         } else {
    //             alert(MSG_ERROR)
    //         }
    //         location.reload()

    //     }.bind(this))
    // },
    // 戻る
    back() {
      var listUrl = "/shipping-process";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    // 検索へ
    back_search() {
      this.processDialog = false;
      var listUrl = "/shipping-list";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    handleClose(done) {
      done();
    },
    selectShelfnumberList: function(sender) {
      this.searchParams.shelf_area =
        sender.selectedValue !== undefined && sender.selectedValue !== null
          ? sender.selectedValue
          : "";

      var item = sender.selectedItem;
      if (item !== null) {
        this.searchParams.warehouse_id = item.warehouse_id;
        this.searchParams.shelf_number_id = item.id;
      } else {
        this.searchParams.warehouse_id = null;
        this.searchParams.shelf_number_id = null;
      }
    },
    // 実行
    process() {
      if (this.detailData.length == 0) {
        return;
      }
      if (this.searchParams.shelf_area == "") {
        return;
      }
      // エラーの初期化
      this.initErr(this.errors);

      this.loading = true;
      this.isSaved = true;
      // // 配送数量マッチング
      // for (var i = 0; i < this.detailData.length; i++) {
      //     for (var j = 0; j < this.searchParams.shipment_detail_id.length; j++) {
      //         if (this.detailData[i].shipment_detail_id == this.searchParams.shipment_detail_id[i]) {
      //             this.detailData[i].loading_quantity = this.searchParams.loading_quantity[i]
      //         }
      //     }
      // }
      axios
        .post("/shipping-truck/save", {
          tableData: this.detailData,
          to_warehouse_id: this.searchParams.warehouse_id,
          to_shelf_number_id: this.searchParams.shelf_number_id
        })

        .then(
          function(response) {
            this.loading = false;
            if (response.data.status) {
              // 成功
              // this.processDialog = true;
              var listUrl = "/shipping-list";
              window.onbeforeunload = null;
              location.href = listUrl;
            } else {
              // 失敗
              window.onbeforeunload = null;
              if (this.rmUndefinedBlank(response.data.message) != '') {
                // 既に出荷済
                alert(response.data.message);
                var listUrl = "/shipping-list";
                window.onbeforeunload = null;
                location.href = listUrl;
              } else {
                alert(MSG_ERROR);
                location.reload();
              }
              this.isSaved = false;
            }
          }.bind(this)
        )

        .catch(
          function(error) {
            this.loading = false;
            if (error.response.data.errors) {
              // エラーメッセージ表示
              this.showErrMsg(error.response.data.errors, this.errors);
            } else {
              if (error.response.data.message) {
                alert(error.response.data.message);
                var listUrl = "/shipping-list";
                window.onbeforeunload = null;
                location.href = listUrl;
              } else {
                alert(MSG_ERROR);
              }
              window.onbeforeunload = null;
              location.reload();
            }
          }.bind(this)
        );
    }
  }
};
</script>
<style>
.el-table th {
  background-color: #f5f8fa;
}
</style>