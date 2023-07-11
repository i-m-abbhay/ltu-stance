@extends('layouts.mobile')
@section('title', 'QR分割')

@section('content')


<qrsplitproductsplit-component 
    :is-editable="{{ $isEditable }}"
></qrsplitproductsplit-component>


@endsection