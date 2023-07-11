@extends('layouts.default')
@section('title', '発注登録')

@section('content')

<orderedit-component 
    :base-data="{{ $baseData }}"
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :maindata="{{ $mainData }}" 
    :quote-layer-list="{{ $quoteLayerList }}" 
    :shipping-limit-list="{{ $shippingLimitList }}"
    :grid-data-list="{{ $gridDataList }}"
    :maker-list="{{ $makerList }}"
    :order-status-list="{{ $orderStatusList }}"
    :class-big-list="{{ $classBigList }}"
    :class-middle-list="{{ $classMiddleList }}"
    :price-list="{{ $priceList }}"
    :supplier-list="{{ $supplierList }}"
    :supplier-maker-list="{{ $supplierMakerList }}"
    :supplier-file-list="{{ $supplierFileList }}"
    :person-list="{{ $personList }}"
    :quote-file-name-list="{{ $quoteFileNameList }}"
    :order-file-name-list="{{ $orderFileNameList }}"
    :sum-each-warehouse-and-detail-list="{{ $sumEachWarehouseAndDetailList }}"
    :order-list="{{ $orderList }}"
    :prop-delivery-address-list="{{ $deliveryAddressList }}"
    :stock-flg="{{ $stockFlg }}"
    :no-product-code="{{ $noProductCode }}"
    :update-history-data-list="{{ $updateHistoryDataList }}"
></orderedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>