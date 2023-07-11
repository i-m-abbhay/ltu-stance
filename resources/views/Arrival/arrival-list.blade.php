@extends('layouts.default')
@section('title', '入荷一覧')

@section('content')

<arrivallist-component 
    :customerlist="{{ $customerList }}"
    :matterlist="{{ $matterList }}"
    :warehouselist="{{ $warehouseList }}"
    :stafflist="{{ $staffList }}"
    :orderlist="{{ $orderList }}"
    :supplierlist="{{ $supplierList }}"
    :makerlist="{{ $makerList }}"
    :departmentlist="{{ $departmentList }}"
    :qrcodelist="{{ $qrcodeList }}"
    :initsearchparams="{{ $initSearchParams }}"
    :staff-department-data="{{ $staffDepartmentData }}"
></arrivallist-component>

@endsection