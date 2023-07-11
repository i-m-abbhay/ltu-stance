<template>
  <div>
    <div class="col-xs-12">
      <div v-for="(object,index) in this.tableData" v-bind:key="index" class="col-xs-12">
        <div class="col-xs-12 col-sm-8">
          <label class="control-label" for="acDepartment">納品日</label>
          <input
            type="text"
            class="form-control"
            v-model="tableData[index].delivery_date"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">工事区分</label>
          <input
            type="text"
            class="form-control"
            v-model="tableData[index].construction_name"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">納品現場</label>
          <input
            type="text"
            class="form-control"
            v-model="tableData[index].address_name"
            v-bind:readonly="true"
          />
          <label class="control-label" for="acDepartment">商品番号</label>
          <input
            type="text"
            class="form-control"
            v-model="tableData[index].product_code"
            v-bind:readonly="true"
          />

          <label class="control-label" for="acDepartment">商品名</label>
          <input
            type="text"
            class="form-control"
            v-model="tableData[index].product_name"
            v-bind:readonly="true"
          />

          <label class="control-label" for="acDepartment">納品数量</label>
          <input
            type="text"
            class="form-control"
            v-model="tableData[index].delivery_quantity"
            v-bind:readonly="true"
          />

          <div class="col-xs-8 col-xs-offset-2">
            <br />
            <button
              type="button"
              class="btn btn-info"
              style="width:100%;"
              v-on:click="process(index)"
            >選択</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    tableData: []
  }),
  props: {},
  created: function() {},
  mounted: function() {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
  },
  methods: {
    // 実行
    process(index) {
      this.loading = true;
      this.processDialog = false;

      this.loading = true;
      localStorage.setItem("tableData", JSON.stringify(this.tableData[index]));
      var listUrl = "/accepting-returns-quantity-input";
      window.onbeforeunload = null;
      location.href = listUrl;
    },
    logErrors(promise) {
      promise.catch(console.error);
    }
  }
};
</script>
<style>
.el-table .normal-row {
  background: rgb(255, 255, 255);
}

.el-table .loading-row {
  background: rgb(76, 217, 100);
}

.form-group {
  padding-left: 10px;
  padding-right: 10px;
  padding-top: 10px;
  display: inline-block;
}
</style>
