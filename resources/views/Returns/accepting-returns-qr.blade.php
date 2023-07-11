@extends('layouts.mobile')
@section('title', 'QRスキャン')

@section('content')


<acceptingreturnsqr-component 
    :is-editable="{{ $isEditable }}"
></acceptingreturnsqr-component>


@endsection