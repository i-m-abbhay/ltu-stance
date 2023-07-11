@extends('layouts.default')
@section('title', '返品処理')

@section('content')
<returnprocess-component   
    :is-editable="{{ $isEditable }}"
    :customerlist="{{ $customerList }}"
    :matterlist="{{ $matterList }}"
    :qrcodelist="{{ $qrCodeList }}"
    :supplierlist="{{ $supplierList }}"
    :departmentlist="{{ $departmentList }}"
    :stafflist="{{ $staffList }}"
    :staffdepartlist="{{ $staffDepartList }}"
    :warehouselist="{{ $warehouseList }}"
    :processlist="{{ $processList }}"
    :is-auth-proc="{{ $isAuthProc }}"
></returnprocess-component>

@endsection



