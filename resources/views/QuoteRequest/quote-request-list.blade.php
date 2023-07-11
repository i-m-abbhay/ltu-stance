@extends('layouts.default')
@section('title', '見積依頼一覧')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>見積依頼一覧</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@section('content')

<quoterequestlist-component 
:matter-data="{{ $matterData }}" 
:customer-data="{{ $customerData }}" 
:department-data="{{ $departmentData }}" 
:staff-data="{{ $staffData }}" 
:staff-department-data="{{ $staffDepartmentData }}" 
:quote-request-kbn-data="{{ $quoteRequestKbnData }}" 
:init-search-params="{{ $initSearchParams }}"
></quoterequestlist-component>

@endsection