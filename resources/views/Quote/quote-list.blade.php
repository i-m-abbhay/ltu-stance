@extends('layouts.default')
@section('title', '見積一覧')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>見積一覧</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section('content')

<quotelist-component 
:matter-data="{{ $matterData }}" 
:customer-data="{{ $customerData }}" 
:department-data="{{ $departmentData }}" 
:staff-data="{{ $staffData }}"
:staff-department-data="{{ $staffDepartmentData }}"
:quote-item-data="{{ $quoteItemData }}"
:init-search-params="{{ $initSearchParams }}" 
></quotelist-component>

@endsection