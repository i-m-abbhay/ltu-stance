@extends('layouts.default')
@section('title', '受発注一覧')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>受発注一覧</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section('content')

<orderlist-component 
:matter-data="{{ $matterData }}" 
:customer-data="{{ $customerData }}" 
:department-data="{{ $departmentData }}" 
:staff-data="{{ $staffData }}"
:staff-department-data="{{ $staffDepartmentData }}"
:maker-data="{{ $makerData }}"
:supplier-data="{{ $supplierData }}"
:supplier-maker-data="{{ $supplierMakerData }}"
:init-search-params="{{ $initSearchParams }}" 
></orderlist-component>

@endsection