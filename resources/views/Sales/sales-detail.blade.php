@extends('layouts.default')
@section('title', '売上明細')

@section('content')

<salesdetail-component
    :login-info="{{ $loginInfo }}"
    :price-list="{{ $priceList }}"
    :sales-detail-flg-list="{{ $salesDetailFlgList }}"
    :sales-flg-list="{{ $salesFlgList }}"
    :sales-status-list="{{ $salesStatusList }}"
    :notdelivery-flg-list="{{ $notdeliveryFlgList }}"
    :sales-detail-filter-info-list="{{ $salesDetailFilterInfoList }}"
    :request-status-list="{{ $requestStatusList }}"
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :default-filter-info="{{ $defaultFilterInfo }}"

    :quote-info="{{ $quoteInfo }}" 
    :request-info="{{ $requestInfo }}" 
    :tree-data-list="{{ $treeDataList }}" 
    :grid-data-list="{{ $gridDataList }}"
    :grid-detail-data-list="{{ $gridDetailDataList }}"
    :add-layer-info="{{ $addLayerInfo }}"
></salesdetail-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>