<template>
    <div>
        <loading-component :loading="loading" />
        <div class="search-form search-body col-sm-12 col-md-12" id="searchForm">
            <div class="col-md-12 col-sm-12 col-xs-12">
                <div class="row">
                    <h4><u>商品選択</u></h4>
                    <div class="col-md-4 col-sm-4 col-xs-8">
                        <label class="control-label">管理ID</label>
                        <wj-auto-complete class="form-control" id="acClassBig" 
                            search-member-path="qr_code"
                            display-member-path="qr_code"
                            selected-value-path="qr_code"
                            :selected-index="-1"
                            :text="result"
                            :selected-value="searchParams.qr_code"
                            :is-required="false"
                            :initialized="initQr"
                            :max-items="qrcodelist.length"
                            :items-source="qrcodelist">
                        </wj-auto-complete>
                    </div>
                    <div class="col-md-4 col-sm-4 col-xs-4 qr-form">
                        <!-- <div class="col-md-3 col-sm-5 col-xs-5"> -->
                            <button type="submit" class="qr-button" @click="qrRead"><svg class="svg-icon"><use width="45.088" height="45.088" xlink:href="#qrIcon" /></svg></button>
                        <!-- </div> -->
                        <!-- <button type="submit" class="btn btn-info" @click="qrRead">QR読取り</button> -->
                        <br />
                    </div>
                    <div v-show="qr_read" class="col-md-12 col-sm-12 col-xs-12">
                        <p class="message">{{ message }}</p>
                        <p class="decode-result">QRコード: <b>{{ result }}</b></p>
                        <qrcode-drop-zone @decode="onDecode">
                            <qrcode-stream @decode="onDecode" @init="onInit" />
                        </qrcode-drop-zone>

                        <qrcode-capture v-if="noStreamApiSupport" @decode="onDecode" />
                    </div>
                </div>
            </div>
            <form id="searchForm" name="searchForm" class="form-horizontal" @submit.prevent="search">
                    <h4><u>倉庫選択</u></h4>
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-12" v-bind:class="{'has-error': (errors.warehouse_name != '') }">
                            <label class="control-label"><span class="required-mark">＊</span>倉庫名</label>
                            <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="warehouse_name"
                                display-member-path="warehouse_name"
                                selected-value-path="warehouse_name"
                                :selected-value="searchParams.default_warehouse_name"
                                :selectedIndexChanged="changeIdxWarehouse"
                                :is-required="false"
                                :initialized="initWarehouse"
                                :max-items="warehouselist.length"
                                :items-source="warehouselist">
                            </wj-auto-complete>
                            <span class="text-danger">{{ errors.warehouse_name }}</span>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">商品番号</label>
                            <input type="text" class="form-control" v-model="searchParams.product_code">
                            <!-- <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="product_code"
                                display-member-path="product_code"
                                selected-value-path="product_code"
                                :selected-index="-1"
                                :selected-value="searchParams.product_code"
                                :is-required="false"
                                :initialized="initProductCode"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                    </div>
                    <div class="row">                        
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">メーカー名</label>
                            <wj-auto-complete class="form-control"
                                search-member-path="supplier_name"
                                display-member-path="supplier_name"
                                selected-value-path="supplier_name"
                                :selected-index="-1"
                                :selected-value="searchParams.supplier_name"
                                :is-required="false"
                                :initialized="initMaker"
                                :max-items="makerlist.length"
                                :items-source="makerlist">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">商品名</label>
                            <input type="text" class="form-control" v-model="searchParams.product_name">
                            <!-- <wj-auto-complete class="form-control" id="acClassBig" 
                                search-member-path="product_name"
                                display-member-path="product_name"
                                selected-value-path="product_name"
                                :selected-index="-1"
                                :selected-value="searchParams.product_name"
                                :is-required="false"
                                :initialized="initProductName"
                                :max-items="productlist.length"
                                :items-source="productlist">
                            </wj-auto-complete> -->
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">棚名</label>
                            <wj-auto-complete class="form-control" id="acWarehouse" 
                                search-member-path="shelf_area"
                                display-member-path="shelf_area"
                                selected-value-path="shelf_area"
                                :selected-index="-1"
                                :selected-value="searchParams.shelf_area"
                                :is-required="false"
                                :initialized="initShelf"
                                :max-items="shelflist.length"
                                :items-source="shelflist">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">得意先名</label>
                            <wj-auto-complete class="form-control" id="acWarehouse" 
                                search-member-path="customer_name"
                                display-member-path="customer_name"
                                selected-value-path="id"
                                :selected-index="-1"
                                :selected-value="searchParams.customer_name"
                                :is-required="false"
                                :initialized="initCustomer"
                                :max-items="customerlist.length"
                                :items-source="customerlist">
                            </wj-auto-complete>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">案件番号</label>
                            <wj-auto-complete class="form-control" id="acWarehouse" 
                                search-member-path="matter_no"
                                display-member-path="matter_no"
                                selected-value-path="id"
                                :selectedIndexChanged="selectMatterNo"
                                :selected-index="-1"
                                :selected-value="searchParams.matter_no"
                                :is-required="false"
                                :initialized="initMatterNo"
                                :max-items="matterlist.length"
                                :items-source="matterlist">
                            </wj-auto-complete>
                        </div>
                        <div class="col-md-6 col-sm-6 col-xs-12">
                            <label class="control-label">案件名</label>
                            <wj-auto-complete class="form-control" id="acWarehouse" 
                                search-member-path="matter_name"
                                display-member-path="matter_name"
                                selected-value-path="id"
                                :selectedIndexChanged="selectMatterName"
                                :selected-index="-1"
                                :selected-value="searchParams.matter_name"
                                :is-required="false"
                                :initialized="initMatterName"
                                :max-items="matterlist.length"
                                :items-source="matterlist">
                            </wj-auto-complete>
                        </div>
                    </div>
                <!-- <br /> -->
                <div class="row">
                    <button type="submit" class="btn btn-search btn-sm form-control">決定</button>
                </div>
            </form>
        </div>
    </div>
</template>
<script>
import { QrcodeStream, QrcodeDropZone, QrcodeCapture } from 'vue-qrcode-reader'
export default {
    components: { 
        QrcodeStream, QrcodeDropZone, QrcodeCapture,
    },
    data: () => ({
        loading: false,

        // QR関連
        result: '',
        message: '',
        noStreamApiSupport: false,
        qr_read: false,
        paused: false,

        urlparam: '',

        searchParams: {
            qr_code: null,
            default_warehouse_name:null,
            warehouse_name: null,
            product_code: null,
            product_name: null,
            supplier_name: null,
            shelf_area: null,
            customer_name: null,
            matter_no: null,
            matter_name: null,
        },

        wjSearchObj: {
            qr_code: {},

            warehouse_name: {},
            product_code: {},
            product_name: {},
            supplier_name: {},
            shelf_area: {},
            customer_name: {},
            matter_no: {},
            matter_name: {},
        },

        errors: {
            warehouse_name: '',
        },
    }),
    props: {
        value: Boolean,
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
        if(this.defaultwarehouse != null){
            this.searchParams.default_warehouse_name = this.defaultwarehouse[0].warehouse_name;
        }
    },
    methods: {
        selectMatterNo: function(sender) {
            var value = this.rmUndefinedBlank(sender.selectedItem.id);
            this.searchParams.matter_name = value;
        },
        selectMatterName: function(sender) {
            var item = sender.selectedItem;
            if(item !== null){
                this.searchParams.matter_no = item.id;
            }else{
                this.searchParams.matter_no = '';
            }
        },
        changeIdxWarehouse: function(sender){
            // 倉庫を変更したら棚を絞り込む
            var tmpShelf = this.shelflist;
            if (sender.selectedValue) {
                var item = sender.selectedItem;
                tmpShelf = [];
                for(var key in this.shelflist) {
                    if (item.id == this.shelflist[key].warehouse_id) {
                        tmpShelf.push(this.shelflist[key]);
                    }
                }
            }
            this.wjSearchObj.shelf_area.itemsSource = tmpShelf;
            this.wjSearchObj.shelf_area.selectedIndex = -1;
        },
        search() {
            this.loading = true;

            // エラーの初期化
            this.initErr(this.errors);

            // URLパラメータ作成
            this.urlparam = '?'
            this.urlparam += 'qr_code=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
            this.urlparam += '&' + 'warehouse_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.warehouse_name.text));
            this.urlparam += '&' + 'product_code=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_code));
            this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
            this.urlparam += '&' + 'supplier_name=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            this.urlparam += '&' + 'shelf_area=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.shelf_area.text));
            this.urlparam += '&' + 'matter_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
            // this.urlparam += '&' + 'product_name=' + encodeURIComponent(this.rmUndefinedBlank(this.searchParams.product_name));
            this.urlparam += '&' + 'customer_id=' + encodeURIComponent(this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));

            var params = new URLSearchParams();
            params.append('qr_code', this.rmUndefinedBlank(this.wjSearchObj.qr_code.text));
            params.append('warehouse_name', this.rmUndefinedBlank(this.wjSearchObj.warehouse_name.text));
            params.append('product_code', this.rmUndefinedBlank(this.searchParams.product_code));
            params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('supplier_name', this.rmUndefinedBlank(this.wjSearchObj.supplier_name.text));
            params.append('shelf_area', this.rmUndefinedBlank(this.wjSearchObj.shelf_area.text));
            params.append('matter_id', this.rmUndefinedBlank(this.wjSearchObj.matter_no.selectedValue));
            // params.append('product_name', this.rmUndefinedBlank(this.searchParams.product_name));
            params.append('customer_id', this.rmUndefinedBlank(this.wjSearchObj.customer_name.selectedValue));

            axios.post('/stock-inquiry/search', params)
            .then( function (response) {
                if (response.data.length == 0) {
                    location.reload() 
                } else if(response.data.mainData) {
                    this.$emit('singleResult', response.data);
                } else {
                    var returnData = [];
                    returnData.data = response.data;
                    returnData.params = this.wjSearchObj;
                    this.$emit('finSearch', returnData);
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
                // location.reload()
            }.bind(this))
        },
        onDecode (content) {            
            this.result = content;

            this.paused = true;
        },
        // QR読取り
        qrRead() {
            if (this.qr_read) {
                this.qr_read = false;
            }
            else {
                this.qr_read = true;
                this.result = "";
            }
        },
        async onInit (promise) {
            try {
                await promise
            } catch (error) {
                if (error.name === 'NotAllowedError') {
                    this.error = "ERROR: you need to grant camera access permisson"
                } else if (error.name === 'NotFoundError') {
                    this.error = "ERROR: no camera on this device"
                } else if (error.name === 'NotSupportedError') {
                    this.error = "ERROR: secure context required (HTTPS, localhost)"
                } else if (error.name === 'NotReadableError') {
                    this.error = "ERROR: is the camera already in use?"
                } else if (error.name === 'OverconstrainedError') {
                    this.error = "ERROR: installed cameras are not suitable"
                } else if (error.name === 'StreamApiNotSupportedError') {
                    this.error = "ERROR: Stream API is not supported in this browser"
                }
            }
        },
        /* 以下オートコンプリード設定 */
        initQr(sender) {
            this.wjSearchObj.qr_code = sender
        },
        initWarehouse(sender) {
            this.wjSearchObj.warehouse_name = sender
        },
        // initProductCode(sender) {
        //     this.wjSearchObj.product_code = sender
        // },
        // initProductName(sender) {
        //     this.wjSearchObj.product_name = sender
        // },
        initMaker(sender) {
            this.wjSearchObj.supplier_name = sender
        },
        initShelf: function(sender) {
            this.wjSearchObj.shelf_area = sender
        },
        initMatterNo: function(sender) {
            this.wjSearchObj.matter_no = sender
        },
        initMatterName: function(sender) {
            this.wjSearchObj.matter_name = sender
        },
        initCustomer: function(sender) {
            this.wjSearchObj.customer_name = sender
        },
    }
}
</script>

<style>
.svg-icon {
    background-color: #C03570 !important;
    color: #C03570;
    border-radius: 10px 10px 10px 10px;
    width: 45.088px;
    height: 45.088px;
}
/* QRボタンの枠線を消す */
.qr-button{
    background-color: transparent;
    border: none;
    cursor: pointer;
    outline: none;
    padding: 0;
    appearance: none;
    padding-top: 25px;
}
.wrapper[data-v-1f90552a] {
    height: 50%;
    width: 50%;
}

.search-input-area {
    height: 70px;
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