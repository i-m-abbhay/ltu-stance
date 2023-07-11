@extends('layouts.default')
@section('title', '共通名称マスタ編集')

@section('content')

<generaledit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}"
    :categorydata="{{ $categoryData }}"
    :categorylist="{{ $categoryList }}"
></generaledit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>
