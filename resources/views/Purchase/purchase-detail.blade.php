@extends('layouts.default')
@section('title', '仕入詳細')

@section('content')

<purchase-detail-component 
    :is-editable="{{ $isEditable }}"
    :matterList="{{ $matterList }}"
    :orderList="{{ $orderList }}"
    :staffList="{{ $staffList }}"
    :allRequestNoList="{{ $allRequestNoList }}"
    :requestNoList="{{ $requestNoList }}"
    :departmentList="{{ $departmentList }}"
    :staffDepartList="{{ $staffDepartList }}"
    :customerList="{{ $customerList }}"
    :classBigList="{{ $classBigList }}"
    :constList="{{ $constList }}"
    :supplierList="{{ $supplierList }}"
    :makerList="{{ $makerList }}"
    :orderstafflist="{{ $orderStaffList }}"
    :confirmstafflist="{{ $confirmStaffList }}"
    :purchasetypelist="{{ $purchasetypeList }}"
    :pricelist="{{ $priceList }}"
    :initparams="{{ $initParams }}"
    :paymentList="{{ $paymentList }}"
></purchase-detail-component>

@endsection