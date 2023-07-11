@extends('layouts.mobile')
@section('title', '納品サイン')

@section('content')


<deliverysign-component 
    :is-editable="{{ $isEditable }}"
></deliverysign-component>


@endsection