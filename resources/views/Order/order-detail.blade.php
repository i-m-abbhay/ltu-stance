@extends('layouts.default')
@section('title', '発注詳細')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>発注詳細</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section('content')

<orderdetail-component 
    :order-data="{{ $orderData }}" 
    :order-detail-data="{{ $orderDetailData }}"
    :set-product-list="{{ $setProductList }}"
    :order-detail-logs="{{ $orderDetailLogs }}"
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :qreq-list="{{ $qreqList }}" 
    :person-list="{{ $personList }}" 
    :supplier-list="{{ $supplierList }}" 
    :class-big-list="{{ $classBigList }}"
    :class-middle-list="{{ $classMiddleList }}"
    :price-list="{{ $priceList }}"
    :no-product-code="{{ $noProductCode }}" 
    :delivery-address-list="{{ $deliveryAddressList }}" 
    :order-file-name-list="{{ $orderFileNameList }}" 
    :add-kbn-list="{{ $addKbnList }}" 
    :order-status-list="{{ $orderStatusList }}" 
    :child-parts-list="{{ $childPartsList }}" 
></orderdetail-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>