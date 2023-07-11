<template>
    <div>
        <loading-component :loading="loading" />
        <div class="main-body col-sm-12">
            <div class="col-sm-12">
                <p class="search-count" style="text-align:right;">検索結果：{{ stockList.length }}件</p>
            </div>
            <div class="col-sm-12" v-for="stock in stockList" :key="stock.id">
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>倉庫名</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.warehouse_name }}</span>
                </div>
                <div class="quantity-block">
                    <div v-show="rmUndefinedBlank(stock.qr_code) != ''" class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>管理ID</p>
                    </div>
                    <span  v-show="rmUndefinedBlank(stock.qr_code) != ''" class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.qr_code }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>商品番号</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.product_code }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>商品名</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.product_name }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>型式／規格</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.model }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>メーカー名</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.supplier_name }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>次回入荷日</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.next_arrival_date }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-4 col-md-4 col-xs-4">
                        <p>最終入荷日</p>
                    </div>
                    <span class="quantity-body col-sm-8 col-md-8 col-xs-8">{{ stock.last_arrival_date }}</span>
                </div>
                <div class="quantity-block">
                    <div class="quantity-header col-sm-12 col-md-12 col-xs-12">
                        <p class="col-sm-3 col-md-3 col-xs-3">実在庫</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">有効在庫</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">引当</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">入荷予定</p>
                    </div>
                </div>
                <div class="quantity-block">
                    <div class="quantity-body col-sm-12 col-md-12 col-xs-12">
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ stock.actual_quantity }}</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ stock.active_quantity }}</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ stock.reserve_quantity }}</p>
                        <p class="col-sm-3 col-md-3 col-xs-3">{{ stock.arrival_quantity }}</p>
                    </div>
                </div>
                <button type="button" class="btn btn-print btn-sm form-control" @click="selected(stock.id)">選択</button>
            </div>
        </div>
        <div class="col-md-12 col-sm-12 col-xs-12">
            <button type="button" class="btn btn-search btn-sm form-control" @click="backToTop">戻る</button>
        </div>
    </div>
</template>
<script>
export default {
    data: () => ({
        loading: false,

        urlparam: '',
        queryParam: '',
        stockList: null,

    }),
    props: {
        list: {},
        shelflist: Array,
        matterlist: Array,
        customerlist: Array,
        params: {},
    },
    created: function() {
    },
    mounted: function() {
    },
    watch: {
        list() {
            this.stockList = this.list;
        }
    },
    methods: {
        backToTop: function() {
            // 検索画面へ
            this.$emit('backToTop');
        },
        selected: function(id) {
            this.loading = true;
            var data = null;
            this.list.forEach(function (val) {
                if(val.id == id) {
                    data = val;
                }
            });

            console.log(this.params);

            var params = new URLSearchParams();
            params.append('id', this.rmUndefinedZero(data.id));
            params.append('warehouse_id', this.rmUndefinedBlank(data.warehouse_id));
            params.append('warehouse_name', this.rmUndefinedBlank(data.warehouse_name));
            params.append('product_id', this.rmUndefinedBlank(data.product_id));
            params.append('product_code', this.rmUndefinedBlank(data.product_code));
            params.append('product_name', this.rmUndefinedBlank(data.product_name));
            params.append('model', this.rmUndefinedBlank(data.model));
            params.append('supplier_name', this.rmUndefinedBlank(data.supplier_name));
            params.append('actual_quantity', this.rmUndefinedZero(data.actual_quantity));
            params.append('active_quantity', this.rmUndefinedZero(data.active_quantity));
            params.append('reserve_quantity', this.rmUndefinedZero(data.reserve_quantity));
            params.append('arrival_quantity', this.rmUndefinedZero(data.arrival_quantity));
            params.append('next_arrival_date', this.rmUndefinedBlank(data.next_arrival_date));
            params.append('last_arrival_date', this.rmUndefinedBlank(data.last_arrival_date));
            params.append('qr_code', this.rmUndefinedBlank(data.qr_code));
            params.append('image', this.rmUndefinedBlank(data.image));

            params.append('shelf_area', this.rmUndefinedBlank(this.params.shelf_area.text));
            params.append('matter_id', this.rmUndefinedBlank(this.params.matter_no.selectedValue));
            params.append('customer_id', this.rmUndefinedBlank(this.params.customer_name.selectedValue));

            axios.post('/stock-inquiry/detail', params)
            .then( function (response) {
                if (response.data.length != 0) {
                    this.$emit('selected', response.data);
                } else {
                    location.reload()
                }
                this.loading = false
            }.bind(this))

            .catch(function (error) {
                this.loading = false
                if (error.response.data.message) {
                    // エラーメッセージ表示
                    this.showErrMsg(error.response.data.errors, this.errors)
                    // alert(error.response.data.message)
                } else {
                    alert(MSG_ERROR)
                }
                location.reload()
            }.bind(this))
        },
    }
}
</script>

<style>
.main-body {
    width: 100%;
    background: #ffffff;
    margin-top: 30px;
    -webkit-box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
    box-shadow: 0 0 1px 1px rgba(0, 0, 0, .3);
}
.btn {
    padding: 6px 6px;
    font-size: 12px;
}

.container-fluid .wj-multirow {
    height: 450px;
    margin: 6px 0;
    font-size: 10px;
}
/* .container-fluid  .wj-detail{
    padding-left: 225px !important;
} */

.wj-header{
    background-color: #43425D !important;
    color: #FFFFFF !important;
    text-align: center !important;
}

/* custom styling for a MultiRow */
.container-fluid .multirow-css .wj-row .wj-cell.wj-record-end:not(.wj-header) {
  border-bottom: 1px solid #000; /* thin black lines between records */
}
/* .container-fluid .multirow-css .wj-row .wj-cell.wj-group-end {
  border-right: 2px solid #00b68c; 
} */
.container-fluid .multirow-css .wj-cell.id {
  color: #c0c0c0;
}
.container-fluid .multirow-css .wj-cell.amount {
  color: #014701;
  font-weight: bold;
}
.container-fluid .multirow-css .wj-cell.email {
  color: #0010c0;
  text-decoration: underline;
}

</style>