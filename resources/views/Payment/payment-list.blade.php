@extends('layouts.default')
@section('title', '支払予定一覧')

@section('content')

<payment-list-component 
    :is-editable="{{ $isEditable }}"
    :supplierlist="{{ $supplierList }}"
    :safetyfeelist="{{ $safetyFeeList }}"
    :cashlist="{{ $cashList }}"
    :purchasetypelist="{{ $purchaseTypeList }}"
    :customerlist="{{ $customerList }}"
    :departmentlist="{{ $departmentList }}"
    :matterlist="{{ $matterList }}"
    :billstype="{{ $billsType }}"
    :banklist="{{ $bankList }}"
    :auth-list="{{ $authList }}"
></payment-list-component>

@endsection
