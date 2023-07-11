@extends('layouts.default')
@section('title', '出荷納品一覧')

@section('content')


<shippingdeliverylist-component 
    :supplierdata="{{ $supplierData }}"
    :customerdata="{{ $customerData }}"
    :fielddata="{{ $fieldData }}"
    :departmentdata="{{ $departmentData }}"
    :orderdata="{{ $orderData }}"
    :matterdata="{{ $matterData }}"
    :staffdata="{{ $staffData }}"
    :warehousedata="{{ $warehouseData }}"
    :issuetimedata="{{ $issueTimeData }}"
    :staffdepartmentlist="{{ $staffDepartmentList }}"
    :initsearchparams="{{ $initSearchParams }}"
></shippingdeliverylist-component>


@endsection