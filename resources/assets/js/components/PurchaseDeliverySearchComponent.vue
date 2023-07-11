<template>
  <div>
    <!-- 検索条件 -->
    <div class="search-form" id="searchForm">
      <form
        id="searchForm"
        name="searchForm"
        class="form-horizontal"
        @submit.prevent="search"
      >
        <div class="row">
          <div class="col-md-3">
            <label class="control-label">発注番号</label>
            <input type="text" class="form-control" v-model="searchParams.order_no" />
          </div>
        </div>
        <div class="pull-right">
          <button type="submit" class="btn btn-primary btn-search">検索</button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    loading: false,
    searchParams: {
      order_no: "",
    },
  }),
  props: {},
  created: function () {},
  mounted: function () {},
  methods: {
    search() {
      this.loading = true;
      var params = new URLSearchParams();
      params.append("order_no", this.searchParams.order_no);
      axios
        .post("/purchase-delivery-search/search", params)

        .then(
          function (response) {
            this.loading = false;

            if (response.data) {
              var listUrl = "/purchase-delivery-list?order_no=" + this.searchParams.order_no;
              window.onbeforeunload = null;
              location.href = listUrl;
            }else{
              alert('対象のデータが存在しません。')
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
  },
};
</script>
<style></style>
