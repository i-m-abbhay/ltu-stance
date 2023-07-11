@extends('layouts.default')
@section('title', '仕入先／メーカー編集')

<script src="{{ url('./js/yubinbango-core.js') }}"></script>

@section('content')

<supplieredit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}"
    :supplierdata="{{ $supplierData }}"
    :persondata="{{ $personData }}"
    :warehousedata="{{ $warehouseData }}"
    :classbiglist="{{ $classBigList }}"
    :constlist="{{ $constList }}"
    :feekbn="{{ $feeKbn }}"
    :acckbn="{{ $accKbn }}"
    :paysight="{{ $paySight }}"
    :banklist="{{ $bankList }}"
    :branchlist="{{ $branchList }}"
    :supplierlist="{{ $supplierList }}"
    :juridlist="{{ $juridList }}"
    :iskbnlock="{{ $isKbnLock }}"
></supplieredit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRYQj4Z6N9YcJNaNrE7Mc39OXtZBMDgzQ&callback=initMap" async defer></script>