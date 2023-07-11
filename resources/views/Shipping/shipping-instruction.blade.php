@extends('layouts.default')
@section('title', '出荷指示')

@section('content')

<shippinginstruction-component 
    :matter-list="{{ $matterList }}"
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :maindata="{{ $mainData }}" 
    :tree-data-list="{{ $treeDataList }}" 
    :shipping-limit-list="{{ $shippingLimitList }}"
    :grid-data-list="{{ $gridDataList }}"
    :shipment-kind-data-list="{{ $shipmentKindDataList }}"
    :reserve-list="{{ $reserveList }}"
></shippinginstruction-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>