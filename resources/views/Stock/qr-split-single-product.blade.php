@extends('layouts.mobile')
@section('title', 'QR分割')

@section('content')


<qrsplitsingleproduct-component 
    :is-editable="{{ $isEditable }}"
></qrsplitsingleproduct-component>


@endsection