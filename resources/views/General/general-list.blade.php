@extends('layouts.default')
@section('title', '共通名称マスタ一覧')

@section('content')

<generallist-component 
    :is-editable="{{ $isEditable }}"
    :categorynamelist="{{ $categoryNameList }}"
></generallist-component>

@endsection