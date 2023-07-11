@extends('layouts.mobile')
@section('title', '移管受入')

@section('content')


<stocktransferacceptance-component 
    :warehouseList="{{ $warehouseList }}" 
    :defaultWarehouseId="{{ $defaultWarehouseId }}" 
    :move-kind-list="{{ $moveKindList }}" 
></stocktransferacceptance-component>


@endsection