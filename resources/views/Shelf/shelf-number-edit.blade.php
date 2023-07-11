@extends('layouts.default')
@section('title', '棚番編集')

@section('content')
<shelfnumberedit-component 
    :is-editable="{{ $isEditable }}"
    :shelfnumberlist="{{ $shelfNumberList }}"
    :is-own-lock="{{ $isOwnLock }}"
    :is-shelf-lock="{{ $isShelfLock }}"
    :lockdata="{{ $lockData }}"
    :warehousedata="{{ $warehouseData }}"
    :basedata="{{ $baseData }}"
></shelfnumberedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>


