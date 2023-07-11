@extends('layouts.mobile')
@section('title', '引取一覧')

@section('content')


<pickuplist-component 
    :warehouseList="{{ $warehouseList }}" 
    :defaultWarehouseId="{{ $defaultWarehouseId }}" 
></pickuplist-component>


@endsection