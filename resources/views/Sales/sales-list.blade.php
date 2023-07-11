@extends('layouts.default')
@section('title', '売上一覧')

@section('content')

<saleslist-component
    :login-info="{{ $loginInfo }}"
    :sales-status-list="{{ $salesStatusList }}"
    :request-status-list="{{ $requestStatusList }}"
    :sales-detail-filter-info-list="{{ $salesDetailFilterInfoList }}"
    :department-list="{{ $departmentList }}" 
    :staff-department-list="{{ $staffDepartmentList }}" 
    :staff-list="{{ $staffList }}" 
    :customer-list="{{ $customerList }}" 
></saleslist-component>

@endsection

<script>
// window.onbeforeunload = function(e) {
//     return MSG_CONFIRM_LEAVE;
// };
</script>