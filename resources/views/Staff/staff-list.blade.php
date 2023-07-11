@extends('layouts.default')
@section('title', '担当者一覧')

@section('content')

<stafflist-component 
    :departmentlist="{{ $departmentList }}"
    :positionlist="{{ $positionList }}" 
    :is-editable="{{ $isEditable }}"
></stafflist-component>


@endsection