@extends('layouts.mobile')
@section('title', '出荷一覧')

@section('content')


<shippinglist-component 
    :warehouseList="{{ $warehouseList }}" 
    :defaultWarehouseId="{{ $defaultWarehouseId }}" 
></shippinglist-component>


@endsection