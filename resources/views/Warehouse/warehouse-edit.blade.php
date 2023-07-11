@extends('layouts.default')
@section('title', '倉庫編集')
<script src="{{ url('./js/yubinbango-core.js') }}"></script>


@section('content')


<warehouseedit-component 
    :is-editable="{{ $isEditable }}"
    :warehousedata="{{ $warehouseData }}"
    :baselist="{{ $baseList }}"
    :is-own-lock="{{ $isOwnLock }}"
    :lockdata="{{ $lockData }}"
></warehouseedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>


