@extends('layouts.default')
@section('title', 'いきなり発注登録')

<script src="{{ url('./js/yubinbango-core.js') }}"></script>

@section('content')

<ordersudden-component 
    :own-stock-flg="{{ $ownStockFlg }}" 
    :customer-list="{{ $customerList }}" 
    :owner-list="{{ $ownerList }}" 
    :customer-owner-list="{{ $customerOwnerList }}"
    :architecture-list="{{ $architectureList }}" 
    :p-matter-model="{{ $matterModel }}" 
    :p-address-model="{{ $addressModel }}" 
    :delivery-address-kbn-list="{{ $deliveryAddressKbnList }}"
    :qreq-list="{{ $qreqList }}" 

    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :order-model="{{ $orderModel }}" 
    :quote-layer-list="{{ $quoteLayerList }}" 
    :grid-data-list="{{ $gridDataList }}"
    :maker-list="{{ $makerList }}"
    :class-big-list="{{ $classBigList }}"
    :class-middle-list="{{ $classMiddleList }}"
    :price-list="{{ $priceList }}"
    :supplier-list="{{ $supplierList }}"
    :supplier-maker-list="{{ $supplierMakerList }}"
    :supplier-file-list="{{ $supplierFileList }}"
    :person-list="{{ $personList }}"
    :p-delivery-address-list="{{ $deliveryAddressList }}"
    :no-product-code="{{ $noProductCode }}"
    
></ordersudden-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>