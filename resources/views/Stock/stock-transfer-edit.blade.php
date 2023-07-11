@extends('layouts.default')
@section('title', '在庫移管編集')

@section('content')

<stocktransferedit-component 
    :movedata="{{ $moveData }}"
    :initparams="{{ $initParams }}"
    :warehouselist="{{ $warehouseList }}"
    :matterlist="{{ $matterList }}"
    :stockquantity="{{ $stockQuantity }}"
></stocktransferedit-component>

@endsection
