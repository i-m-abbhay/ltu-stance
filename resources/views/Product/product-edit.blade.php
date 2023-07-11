@extends('layouts.default')
@section('title', '商品マスタ登録')

@section('content')

<productedit-component 
    :is-editable="{{ $isEditable }}"
    :is-own-lock="{{ $isOwnLock }}"
    :lockdata="{{ $lockData }}"
    :productdata="{{ $productData }}"
    :taxtypedata="{{ $taxtypeData }}"
    :taxkbndata="{{ $taxkbnData }}"
    :supplierdata="{{ $supplierData }}"
    :constbigdata="{{ $constBigData }}"
    :constdata="{{ $constData }}"
    :classbigdata="{{ $classBigData }}"
    :classmiddata="{{ $classMidData }}"
    :classsmalldata="{{ $classSmallData }}"
    :wooddata="{{ $woodData }}"
    :gradedata="{{ $gradeData }}"
    :no-product-code="{{ $noProductCode }}"
></productedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>