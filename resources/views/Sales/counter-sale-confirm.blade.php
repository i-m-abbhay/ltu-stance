@extends('layouts.mobile')
@section('title', '販売確認')

@section('content')


<countersaleconfirm-component 
    :customerList="{{ $customerList }}" 
    :taxRate="{{ $taxRate }}" 
></countersaleconfirm-component>


@endsection