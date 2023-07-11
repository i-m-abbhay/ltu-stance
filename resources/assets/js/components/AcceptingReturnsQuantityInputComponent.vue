<template>
  <div>
    <loading-component :loading="loading" />
    <div class="col-xs-12 col-sm-8">
      <label class="control-label" for="acDepartment">納品日</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.delivery_date"
        v-bind:readonly="true"
      />
      <label class="control-label" for="acDepartment">工事区分</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.construction_name"
        v-bind:readonly="true"
      />
      <label class="control-label" for="acDepartment">納品現場</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.address_name"
        v-bind:readonly="true"
      />
      <label class="control-label" for="acDepartment">商品番号</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.product_code"
        v-bind:readonly="true"
      />

      <label class="control-label" for="acDepartment">商品名</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.product_name"
        v-bind:readonly="true"
      />

      <label class="control-label" for="acDepartment">納品数量</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.delivery_quantity"
        v-bind:readonly="true"
      />

       <label class="control-label" for="acDepartment">返品済数</label>
      <input
        type="text"
        class="form-control"
        v-model="tableData.returned_quantity"
        v-bind:readonly="true"
      />

      <div v-bind:class="{'has-error': (errors.returns_date != '') }">
        <label class="control-label" for="acDepartment">返品日</label>
        <input type="date" class="form-control" v-model="returns_date" v-bind:readonly="false" />
        <p class="text-danger">{{errors.returns_date}}</p>
      </div>

      <div v-bind:class="{'has-error': (errors.returns_quantity != '') }">
        <label class="control-label" for="acDepartment">返品数</label>
        <input
          type="number"
          class="form-control"
          v-model="returns_quantity"
          v-bind:readonly="false"
        />
        <p class="text-danger">{{errors.returns_quantity}}</p>
      </div>

      <div class="col-xs-8 col-xs-offset-2">
        <br />
        <button type="button" class="btn btn-info" style="width:100%;" v-bind:disabled="isSaved" v-on:click="process">確認</button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    isSaved: false,
    tableData: [],
    returns_quantity: 0,
    returns_date: null,
    errors: {
      returns_date: "",
      returns_quantity: "",
    },
  }),

  props: {},
  created: function () {},
  mounted: function () {
    this.tableData = JSON.parse(localStorage.getItem("tableData")) || [];
    var today = new Date();
    today.setDate(today.getDate());
    var yyyy = today.getFullYear();
    var mm = ("0" + (today.getMonth() + 1)).slice(-2);
    var dd = ("0" + today.getDate()).slice(-2);
    this.returns_date = yyyy + "-" + mm + "-" + dd;
  },
  methods: {
    // 実行
    process() {
      if (
        Number(this.tableData.delivery_quantity) < (Number(this.returns_quantity)+Number(this.tableData.returned_quantity))
      ) {
        alert("返品数が納品数量を超えています。");
        return;
      }
      // マイナス値はエラー
      if (this.rmUndefinedZero(this.returns_quantity) < 0) {
        this.errors.returns_quantity = '1'+MSG_ERROR_LOWER_NUMBER;
        return;
      }

      if (this.returns_date == null || this.returns_date == "") {
        this.errors.returns_date = "必須です。";
        return;
      }

      if (this.returns_quantity == null || this.returns_quantity == "") {
        this.errors.returns_quantity = "必須です。";
        return;
      }
      this.loading = true;
      this.isSaved = true;

      axios
        .post("/accepting-returns-quantity-input/save", {
          tableData: this.tableData,
          returns_quantity: this.returns_quantity,
          returns_date: this.returns_date,
        })

        .then(
          function (response) {
            if (response.data.status) {
              localStorage.setItem(
                "warehouse_move_id",
                JSON.stringify(response.data)
              );
              var listUrl = "/accepting-returns-approval";
              window.onbeforeunload = null;
              location.href = listUrl;
            } else {
              alert(response.data.message.replace(/\\n/g, '\n'));
              this.isSaved = false;
            }

            this.loading = false;
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
    logErrors(promise) {
      promise.catch(console.error);
    },
  },
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
