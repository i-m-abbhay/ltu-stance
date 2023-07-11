@extends('layouts.mobile')
@section('title', 'サイン')

@section('content')


<countersalesign-component 
    :is-editable="{{ $isEditable }}"
></countersalesign-component>


@endsection