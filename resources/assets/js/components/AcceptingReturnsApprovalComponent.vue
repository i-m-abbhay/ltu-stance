<template>
  <div>
    <br />
    <div class="row">
      <div class="col-sm-12 text-center">
        <button
          type="button"
          class="btn btn-primary"
          style="width:60%;height:70px;"
          @click="process"
        >承認確認</button>
      </div>
      <br />
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    urlparam: "",
    processDialog: false,
    warehouse_move_id: null
  }),
  props: {},
  created: function() {},
  mounted: function() {
    this.warehouse_move_id =
      JSON.parse(localStorage.getItem("warehouse_move_id")) || [];
  },
  methods: {
    logErrors(promise) {
      promise.catch(console.error);
    },
    //承認確認
    process() {
      this.loading = true;

      axios
        .post("/accepting-returns-approval/search", {
          warehouse_move_id: this.warehouse_move_id
        })

        .then(
          function(response) {
            //未承認
            if (response.data == 0) {
              alert("承認者が未承認です。");
              return;
            }
            //承認済
            else if (response.data == 1) {
              alert("返品は承認されました。");

              var listUrl = "/accepting-returns-input-select";
              window.onbeforeunload = null;
              location.href = listUrl;
            }
            //否認
            else if (response.data == 2) {
              alert("返品は否認されました。");

              var listUrl = "/accepting-returns-input-select";
              window.onbeforeunload = null;
              location.href = listUrl;
            } else {
              alert(MSG_ERROR);
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
