@extends('layouts.mobile')
@section('title', '返品数入力')

@section('content')


<acceptingreturnsquantityinput-component 
    :is-editable="{{ $isEditable }}"
></acceptingreturnsquantityinput-component>


@endsection