@extends('layouts.mobile')
@section('title', '商材選択')

@section('content')


<countersale-component 
    :warehouseList="{{ $warehouseList }}" 
    :default_warehouse_id="{{ $default_warehouse_id }}" 
    :productList="{{ $productList }}" 
    :makerList="{{ $makerList }}" 
    :taxRate="{{ $taxRate }}" 
></countersale-component>


@endsection