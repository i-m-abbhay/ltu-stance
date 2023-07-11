<template>
  <div>
    <div class="main-body">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="col-md-4 col-sm-6 col-xs-8">
          <label class="control-label">倉庫</label>
          <wj-auto-complete
            class="form-control"
            id="acWarehouse"
            search-member-path="id"
            display-member-path="warehouse_name"
            :items-source="warehouselist"
            selected-value-path="id"
            :selected-value="select_warehouse_id"
            :initialized="initWarehouse"
          ></wj-auto-complete>
        </div>
        <div class="col-md-2 col-sm-2 col-xs-2">
          <br />
          <button
            type="button"
            class="btn btn-primary"
            style="margin-top: 8px"
            @click="process"
          >
            決定
          </button>
        </div>
      </div>

      <div class="col-md-3 col-sm-6 col-xs-6">
        <h3>
          <u>入出荷</u>
        </h3>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/arrival-search">
                <use width="60" height="60" xlink:href="#arrivalIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            発注入荷
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/shipping-list">
                <use width="60" height="60" xlink:href="#shippingIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            出荷配送
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/delivery-list">
                <use width="60" height="60" xlink:href="#deliveryIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            納品
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/purchase-delivery-search">
                <use width="60" height="60" xlink:href="#purchaseWarehouseIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            仕入倉庫受取納品
          </p>
        </div>
         
      </div>

      <div class="col-md-3 col-sm-6 col-xs-6">
        <h3>
          <u>QR操作</u>
        </h3>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/qr-split">
                <use width="60" height="60" xlink:href="#qrSplitIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            QR分割
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/qr-integration">
                <use width="60" height="60" xlink:href="#qrIntegrationIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            QR統合
          </p>
        </div>
        <!-- <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/qr-matter-change">
                <use width="60" height="60" xlink:href="#qrMatterChangeIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            QR案件変更
          </p>
        </div> -->
      </div>
      <div class="col-md-3 col-sm-6 col-xs-6">
        <h3>
          <u>返品処理</u>
        </h3>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/accepting-returns-input-select">
                <use width="60" height="60" xlink:href="#rtnAcceptanceIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            返品受入
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/maker-delivery">
                <use width="60" height="60" xlink:href="#makerPassingIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            メーカー引渡
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/discard-list">
                <use width="60" height="60" xlink:href="#disposalIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            廃棄
          </p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-xs-6">
        <h3>
          <u>移管処理</u>
        </h3>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/stock-transfer">
                <use width="60" height="60" xlink:href="#stockTransferIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            在庫移管
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/stock-transfer-acceptance">
                <use width="60" height="60" xlink:href="#transferAcceptanceIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            移管受入
          </p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-xs-6">
        <h3>
          <u>売上処理</u>
        </h3>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/counter-sale">
                <use width="60" height="60" xlink:href="#counterSaleIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            店頭販売
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/pickup-list">
                <use width="60" height="60" xlink:href="#pickupIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            引取
          </p>
        </div>
      </div>
      <div class="col-md-3 col-sm-6 col-xs-6">
        <h3>
          <u>在庫整理</u>
        </h3>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/shelf-move">
                <use width="60" height="60" xlink:href="#shelfMoveIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            倉庫内移動
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/stock-inquiry">
                <use width="60" height="60" xlink:href="#stockSearchIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            在庫照会
          </p>
        </div>
        <div class="row">
          <div class="col-md-3 col-sm-5 col-xs-5">
            <svg class="svg-icon">
              <a href="/inventory">
                <use width="60" height="60" xlink:href="#inventoryIcon" />
              </a>
            </svg>
          </div>
          <p class="control-label col-sm-offset-1 col-md-8 col-sm-6 col-xs-6 info-text">
            棚卸
          </p>
        </div>
      </div>
      <!-- <div class="col-md-4 col-sm-6 col-xs-6">
                <h3><u>売上処理</u></h3>
                <div class="row">
                    <div class="col-md-5 col-sm-5 col-xs-5">
                        <a class="col-md-2 col-sm-2 col-xs-2 btn btn-link" href="#"></a>
                    </div>
                    <p class="control-label col-md-7 col-sm-7 col-xs-7 info-text">店頭販売</p>
                </div>
      </div>-->
    </div>
  </div>
</template>

<script>
export default {
  data: () => ({
    select_warehouse_id: 0,
    wjSearchObj: {
      warehouse: {},
    },
  }),
  props: {
    warehouseid: null,
    warehouselist: Array,
  },
  created: function () {},
  mounted: function () {
    this.select_warehouse_id = this.warehouseid;
  },
  methods: {
    initWarehouse(sender) {
      this.wjSearchObj.warehouse = sender;
    },

    //決定ボタン押下
    process() {
      if(this.wjSearchObj.warehouse.selectedValue !== null && this.wjSearchObj.warehouse.selectedValue !== 0){
        axios
          .post("/mobile-menu/save", { warehouse_id: this.wjSearchObj.warehouse.selectedValue })

          .then(
            function (response) {
              if (response.data) {
                alert("デフォルトの倉庫設定を更新しました。");
                location.reload();
              }
              // this.loading = false;
            }.bind(this)
          )
          .catch(
            function (error) {
              // this.loading = false
              if (error.response.data.message) {
                alert(error.response.data.message);
              } else {
                alert(MSG_ERROR);
              }
              location.reload();
            }.bind(this)
          );
       }
    },
  },
};
</script>

<style>
.main-body {
  background-color: white;
  color: black;
  width: 100%;
}
.btn-link {
  background-color: #6200ee;
  color: #fff;
  width: 60px;
  height: 60px;
  border-radius: 10px 10px 10px 10px;
}
.info-text {
  font-size: 12px;
  padding-top: 15px;
  padding-left: 5px;
}
.svg-icon {
  background-color: #6200ee !important;
  color: #6200ee;
  border-radius: 10px 10px 10px 10px;
  width: 60px;
  height: 60px;
}
a {
  background-color: inherit;
  color: inherit;
}
.row {
  margin-top: 10px;
  margin-bottom: 10px;
  max-height: 60px;
}
</style>
