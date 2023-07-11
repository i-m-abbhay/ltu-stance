@extends('layouts.default')
@section('title', '倉庫一覧')

@section('content')


<warehouselist-component 
    :baselist="{{ $baseList }}" 
    :is-editable="{{ $isEditable }}"
></warehouselist-component>


@endsection