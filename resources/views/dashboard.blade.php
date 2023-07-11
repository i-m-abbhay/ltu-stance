@extends('layouts.default')
@section('title', 'ダッシュボード')

@section('content')

<dashboard-component
    :customer-list="{{ $customerList }}" 
    :department-list="{{ $departmentList }}" 
    :staff-list="{{ $staffList }}"
    :staff-department-list="{{ $staffDepartmentList }}"
    :init-search-params="{{ $initSearchParams }}" 
></dashboard-component>

@endsection