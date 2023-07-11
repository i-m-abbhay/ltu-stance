@extends('layouts.mobile')
@section('title', '入荷検索')

@section('content')

<arrivalsearch-component 
    :matterlist="{{ $matterList }}"
    :stafflist="{{ $staffList }}"
    :departmentlist="{{ $departmentList }}"
    :customerlist="{{ $customerList }}"
    :makerlist="{{ $makerList }}"
    :supplierlist="{{ $supplierList }}"
    :orderlist="{{ $orderList }}"
    :orderdeliverylist="{{ $orderDeliveryList }}"
    :is-editable="{{ $isEditable }}"
    :staff-department-data="{{ $staffDepartmentData }}"
    :defaultWarehouseId="{{ $defaultWarehouseId }}" 
></arrivalsearch-component>

@endsection