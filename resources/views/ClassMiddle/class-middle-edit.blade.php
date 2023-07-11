@extends('layouts.default')
@section('title', '中分類編集')

@section('content')

<class-middle-edit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :middledata="{{ $middleData }}"
    :classbiglist="{{ $classBigList }}"
></class-middle-edit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>