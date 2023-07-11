@extends('layouts.default')
@section('title', '拠点編集')

<!-- <script src="{{ url('./js/ajaxzip3.js') }}"></script> -->
<script src="{{ url('./js/yubinbango-core.js') }}"></script>

@section('content')

<baseedit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :basedata="{{ $baseData }}" 
></baseedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>