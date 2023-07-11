@extends('layouts.mobile')
@section('title', '出荷手続')

@section('content')


<shippingprocess-component 
    :is-editable="{{ $isEditable }}"
    :temporary-shelf-id="{{ $temporaryShelfId }}"
></shippingprocess-component>


@endsection