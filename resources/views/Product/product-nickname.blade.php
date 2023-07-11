@extends('layouts.default')
@section('title', '呼び名登録')

@section('content')

<productnickname-component 
    :is-editable="{{ $isEditable }}"
    :is-own-lock="{{ $isOwnLock }}"
    :lockdata="{{ $lockData }}"
    :parentproduct="{{ $parentProduct }}"
    :nicknamelist="{{ $nicknameList }}"
></productnickname-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>