@extends('layouts.mobile')
@section('title', '廃棄')

@section('content')


<discardlist-component 
    :warehouseList="{{ $warehouseList }}" 
    :defaultWarehouseId="{{ $defaultWarehouseId }}" 
></discardlist-component>


@endsection