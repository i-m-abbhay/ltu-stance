@extends('layouts.mobile')
@section('title', '在庫移管')

@section('content')


<stocktransfer-component 
    :warehouseList="{{ $warehouseList }}" 
    :defaultWarehouseId="{{ $defaultWarehouseId }}" 
></stocktransfer-component>


@endsection