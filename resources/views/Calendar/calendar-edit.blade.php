@extends('layouts.default')
@section('title', 'カレンダー入力')

@section('content')

<calendaredit-component 
    :has-auth="{{ $hasAuth }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :lock-key="{{$lockKey}}"
    :calendar-kbn="{{ $calendarKbn }}" 
    :calendar-repeat-kbn="{{ $calendarRepeatKbn }}" 
    :calendar-week="{{ $calendarWeek }}" 
    :repeat-kbn-ctrl="{{ $repeatKbnCtrl }}" 
    :grid-data="{{ $gridData }}" 
></calendaredit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>