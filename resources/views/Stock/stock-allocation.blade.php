@extends('layouts.default')
@section('title', '引当')

@section('content')

<stockallocation-component 
    :customerlist="{{ $customerList }}"
    :matterlist="{{ $matterList }}"
    :departmentlist="{{ $departmentList }}"
    :stafflist="{{ $staffList }}"
    :warehouselist="{{ $warehouseList }}"
    :initSearchParams="{{ $initSearchParams }}"
></stockallocation-component>

@endsection

