@extends('layouts.default')
@section('title', '案件一覧')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>案件一覧</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section('content')

<matterlist-component 
:matter-data="{{ $matterData }}" 
:customer-data="{{ $customerData }}" 
:department-data="{{ $departmentData }}" 
:staff-data="{{ $staffData }}" 
:staff-department-data="{{ $staffDepartmentData }}" 
:address-data="{{ $addressData }}"
:has-matter-auth="{{ $hasMatterAuth }}"
:init-search-params="{{ $initSearchParams }}"
></matterlist-component>

@endsection