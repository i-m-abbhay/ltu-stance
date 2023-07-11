@extends('layouts.default')
@section('title', '部門一覧')

@section('content')

<departmentlist-component 
    :is-editable="{{ $isEditable }}"
    :departmentlist="{{ $departmentList }}"
    :baselist="{{ $baseList }}"
    :stafflist="{{ $staffList }}"
    :initSearchParams="{{ $initSearchParams }}"
></departmentlist-component>

@endsection