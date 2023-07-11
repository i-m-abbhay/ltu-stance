@extends('layouts.mobile')
@section('title', 'QR統合')

@section('content')


<qrintegration-component 
    :is-editable="{{ $isEditable }}"
></qrintegration-component>


@endsection