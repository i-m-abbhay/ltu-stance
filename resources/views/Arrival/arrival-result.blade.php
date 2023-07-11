@extends('layouts.mobile')
@section('title', '入荷検索結果')

@section('content')

<arrivalresult-component 
    :is-editable="{{ $isEditable }}"
></arrivalresult-component>

@endsection