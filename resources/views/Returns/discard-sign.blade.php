@extends('layouts.mobile')
@section('title', 'サイン')

@section('content')


<discardsign-component 
    :is-editable="{{ $isEditable }}"
></discardsign-component>


@endsection