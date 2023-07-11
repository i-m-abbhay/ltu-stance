<template>
    <div>
        <loading-component :loading="loading" />
        <!-- 在庫照会(スマホ)画面コントロール用 -->
        <div v-show="showTop">
            <stockinquiry-top-component
                :qrcodelist="qrcodelist"
                :warehouselist="warehouselist"
                :makerlist="makerlist"
                :defaultwarehouse="defaultwarehouse"
                :shelflist="shelflist"
                :matterlist="matterlist"
                :customerlist="customerlist"
                @finSearch="finSearch"
                @singleResult="singleResult"
            ></stockinquiry-top-component>
        </div>
        
        <div v-show="showList">
            <stockinquiry-list-component
                :list="resultList"
                :params="searchParams"
                @selected="selected"
                @backToTop="backToTop"
            ></stockinquiry-list-component>
        </div>
        
        <div v-show="showDetail">
            <stockinquiry-detail-component
                :mainData="maindata"
                :reserveData="reservedata"
                :qrData="qrdata"
                @backToList="backToList"
            ></stockinquiry-detail-component>
        </div>

    </div>
</template>
<script>
export default {
    data: () => ({
        loading: false,

        showTop: false,
        showList: false,
        showDetail: false,

        searchParams: {},

        resultList: {},

        directFlg: false,
        maindata: null,
        reservedata: null,
        qrdata: null,
    }),
    props: {
        qrcodelist: Array,
        warehouselist: Array,
        // productlist: Array,
        makerlist: Array,
        defaultwarehouse: Array,
        shelflist: Array,
        matterlist: Array,
        customerlist: Array,
    },
    created: function() {
    },
    mounted: function() {
        this.showTop = true;
    },
    methods: {
        finSearch(result) {
            // 検索結果を格納
            this.directFlg = false;
            this.resultList = result.data;
            this.searchParams = result.params;
            
            this.showTop = false;
            this.showList = true;
        },
        singleResult(result) {
            // QR検索結果が１件
            this.directFlg = true;

            this.maindata = result.mainData;
            this.reservedata = result.reserveData;
            this.qrdata = result.qrData;

            this.showTop = false;
            this.showDetail = true;
        },
        selected(detail) {
            // 選択したデータ格納
            this.maindata = detail.mainData;
            this.reservedata = detail.reserveData;
            this.qrdata = detail.qrData;

            this.showList = false;
            this.showDetail = true;
        },
        backToTop() {
            // 一覧 → 検索画面へ
            this.showList = false;
            this.showTop = true;
        },
        backToList() {
            if (this.directFlg) {
                // 詳細 → 検索画面へ
                this.showDetail = false;
                this.showTop = true;
            } else {
                // 詳細 → 一覧画面へ
                this.showDetail = false;
                this.showList = true;
            }
        },
    }
}
</script>

<style>

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