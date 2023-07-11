@extends('layouts.default')
@section('title', '案件詳細')

<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<title>案件詳細</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script src="{{ url('./js/DHTMLX/gantt/codebase/dhtmlxgantt.js') }}"></script>
<script src="{{ url('./js/DHTMLX/scheduler/codebase/dhtmlxscheduler.js') }}"></script>
<script src="{{ url('./js/DHTMLX/scheduler/codebase/ext/dhtmlxscheduler_tooltip.js') }}"></script>
<link rel="stylesheet" href="{{ url('./js/DHTMLX/gantt/codebase/dhtmlxgantt.css') }}">
<link rel="stylesheet" href="{{ url('./js/DHTMLX/scheduler/codebase/dhtmlxscheduler.css') }}">

@section('content')

<matterdetail-component 
:p-screen-name="{{ $screenName }}"
:p-const="{{ $constData }}"
:p-message="{{ $messageData }}"
:p-is-own-lock="{{ $isOwnLock }}"
:p-lock-data="{{ $lockData }}"
:p-main-data="{{ $mainData }}" 
:p-matter-rain-data="{{ $matterRainData }}" 
:p-matter-combo-data="{{ $matterComboData }}" 
:p-construction-data="{{ $constructionData }}" 
:p-parent-quote-layer-name-data="{{ $parentQuoteLayerNameData }}" 
:p-gantt-holiday-list="{{ $ganttHolidayList }}" 
:p-company-holiday-list="{{ $companyHolidayList }}" 
:p-quote-ver-file-list="{{ $quoteVerFileList }}" 
:p-order-file-list="{{ $orderFileList }}" 
></matterdetail-component>

@endsection