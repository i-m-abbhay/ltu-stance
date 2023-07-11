@extends('layouts.default')
@section('title', 'テスト発注登録')

<script src="{{ url('./js/yubinbango-core.js') }}"></script>

@section('content')

<test-component 
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
    :price-list="{{ $priceList }}"
    :supplier-list="{{ $supplierList }}"
    :supplier-maker-list="{{ $supplierMakerList }}"
    :supplier-file-list="{{ $supplierFileList }}"
    :person-list="{{ $personList }}"
    :no-product-code="{{ $noProductCode }}"
    
></test-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>