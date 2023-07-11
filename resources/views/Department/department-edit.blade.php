@extends('layouts.default')
@section('title', '部門編集')

<script src="{{ url('./js/yubinbango-core.js') }}"></script>

@section('content')

<departmentedit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :departmentdata="{{ $departmentData }}"
    :baselist="{{ $baseList }}"
    :departmentlist="{{ $departmentList }}"
    :banklist="{{ $bankList }}"
    :stafflist="{{ $staffList }}"
></departmentedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>