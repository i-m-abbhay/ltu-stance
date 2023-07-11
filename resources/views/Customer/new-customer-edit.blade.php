@extends('layouts.default')
@section('title', '得意先編集')

<!-- <script src="{{ url('./js/ajaxzip3.js') }}"></script> -->
<script src="{{ url('./js/yubinbango-core.js') }}"></script>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDRYQj4Z6N9YcJNaNrE7Mc39OXtZBMDgzQ&callback=initMap" async defer></script>


@section('content')

<newcustomeredit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :customerdata="{{ $customerData }}"
    :categorydata="{{ $categoryData }}"
    :persondata="{{ $personData }}" 
    :branchdata="{{ $branchData }}"
    :checkboxdata="{{ $checkBoxData }}"
    :juridlist="{{ $juridList }}"
    :customerlist="{{ $customerList }}"
    :collectsight="{{ $collectSight }}"
    :collectkbn="{{ $collectKbn }}"
    :feekbn="{{ $feeKbn }}"
    :taxcalckbn="{{ $taxCalcKbn }}"
    :taxrounding="{{ $taxRounding }}"
    :supplierlist="{{ $supplierList }}"
    :stafflist="{{ $staffList }}"
    :departmentlist="{{ $departmentList }}"
    :staffdepartlist="{{ $staffDepartList }}"
></newcustomeredit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>