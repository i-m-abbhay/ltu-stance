@extends('layouts.default')
@section('title', '新規案件情報入力')

<script src="{{ url('./js/ajaxzip3.js') }}"></script>

@section('content')

<matteredit-component 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :matter-data="{{ $matterData }}" 
    :customer-data="{{ $customerData }}" 
    :owner-data="{{ $ownerData }}" 
    :architecture-data="{{ $architectureData }}" 
    :department-list="{{ $departmentList }}" 
    :staff-department-list="{{ $staffDepartmentList }}" 
    :staff-list="{{ $staffList }}" 
></matteredit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>