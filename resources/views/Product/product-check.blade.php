@extends('layouts.default')
@section('title', '商品マスタチェック')

@section('content')

<productcheck-component 
    :is-editable="{{ $isEditable }}"
    :lockdata="{{ $lockData }}"
    :productlist="{{ $productList }}"
    :constlist="{{ $constList }}"
    :matterlist="{{ $matterList }}"
    :orderlist="{{ $orderList }}"
    :orderdetaillist="{{ $orderDetailList }}"
    :classbiglist="{{ $classBigList }}"
    :classmidlist="{{ $classMidList }}"
    :classsmalllist="{{ $classSmallList }}"
    :taxtypelist="{{ $taxtypeList }}"
    :woodlist="{{ $woodList }}"
    :gradelist="{{ $gradeList }}"
    :makerlist="{{ $makerList }}"
    :housing-history-transfer-kbn="{{ $housingHistoryTransferKbn }}"
    :no-product-code="{{ $noProductCode }}"
></productcheck-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>