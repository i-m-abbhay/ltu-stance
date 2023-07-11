@extends('layouts.mobile')
@section('title', 'QR分割')

@section('content')


<qrsplitquantitydesignation-component 
    :is-editable="{{ $isEditable }}"
></qrsplitquantitydesignation-component>


@endsection