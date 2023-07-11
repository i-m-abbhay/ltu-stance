@extends('layouts.mobile')
@section('title', '返品情報入力')

@section('content')


<acceptingreturnsinfoinput-component 
    :is-editable="{{ $isEditable }}"
></acceptingreturnsinfoinput-component>


@endsection