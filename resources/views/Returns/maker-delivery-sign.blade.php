@extends('layouts.mobile')
@section('title', 'サイン')

@section('content')


<makerdeliverysign-component 
    :is-editable="{{ $isEditable }}"
></makerdeliverysign-component>


@endsection