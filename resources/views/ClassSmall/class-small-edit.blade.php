@extends('layouts.default')
@section('title', '工程編集')

@section('content')

<class-small-edit-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}"
    :class-small-data="{{ $classSmallData }}"
    :construction-list="{{ $constructionList }}"
    :class-small-list="{{ $classSmallList }}"
></class-small-edit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>