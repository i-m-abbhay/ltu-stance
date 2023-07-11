@extends('layouts.default')
@section('title', '担当者登録')


<!-- CSRF対策 -->
<meta name="csrf-token" content="{{ csrf_token() }}">

@section('content')

<staffedit-component
        :is-editable="{{ $isEditable }}" 
        :is-own-lock="{{ $isOwnLock }}"
        :lockdata="{{ $lockData }}"
        :staffdata="{{ $staffData }}"
        :stdepartdata="{{ $stDepartData }}"
        :departmentdata="{{ $departmentData }}"
        :posdata="{{ $posData }}">
</staffedit-component>

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>

@endsection