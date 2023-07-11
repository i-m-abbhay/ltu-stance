@extends('layouts.default')
@section('title', '工程一覧')

@section('content')

<class-small-list-component 
    :is-editable="{{ $isEditable }}"
    :class-small-list="{{ $classSmallList }}"
    :construction-list="{{ $constructionList }}"
></class-small-list-component>

@endsection