@extends('layouts.mobile')
@section('title', '在庫照会')

@section('content')

<stockinquiry-component 
    :qrcodelist="{{ $qrcodeList }}"
    :warehouselist="{{ $warehouseList }}"
    :makerlist="{{ $makerList }}"
    :defaultwarehouse="{{ $defaultWarehouse }}" 
    :shelflist="{{ $shelfList }}"
    :matterlist="{{ $matterList }}"
    :customerlist="{{ $customerList }}"
></stockinquiry-component>

@endsection

