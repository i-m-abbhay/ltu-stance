@extends('layouts.default')
@section('title', 'カレンダー')

<script src="{{ url('./js/DHTMLX/scheduler/codebase/dhtmlxscheduler.js') }}"></script>
<script src="{{ url('./js/DHTMLX/scheduler/codebase/ext/dhtmlxscheduler_year_view.js') }}"></script>
<script src="{{ url('./js/DHTMLX/scheduler/codebase/ext/dhtmlxscheduler_tooltip.js') }}"></script>
<script src="{{ url('./js/DHTMLX/scheduler/codebase/locale/locale_jp.js') }}" charset="utf-8"></script>
<link rel="stylesheet" href="{{ url('./js/DHTMLX/gantt/codebase/dhtmlxgantt.css') }}">
<link rel="stylesheet" href="{{ url('./js/DHTMLX/scheduler/codebase/dhtmlxscheduler_material.css') }}">

@section('content')

<calendardata-component 
    :has-auth="{{ $hasAuth }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :lock-key="{{ $lockKey }}"
    :year-list="{{ $yearList }}"
    :now-year="{{ $nowYear }}" 
    :businessday-kbn="{{ $businessdayKbn }}" 
></calendardata-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>