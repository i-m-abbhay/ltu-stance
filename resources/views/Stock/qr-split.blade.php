@extends('layouts.mobile')
@section('title', 'QR分割')

@section('content')


<qrsplit-component 
    :is-editable="{{ $isEditable }}"
></qrsplit-component>


@endsection