@extends('layouts.default')
@section('title', '中分類一覧')

@section('content')

<class-middle-list-component 
    :is-editable="{{ $isEditable }}"
    :classmiddlelist="{{ $classMiddleList }}"
    :classbiglist="{{ $classBigList }}"
></class-middle-list-component>

@endsection