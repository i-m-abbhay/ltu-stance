@extends('Shipping.photoLayout')
@section('title', '出荷納品一覧')

@section('content')


<shippingdeliverylistphoto-component 
    :shipment-data="{{ $shipmentData }}"
    :delivery-list="{{ $deliveryList }}"
></shippingdeliveryphoto-component>

@endsection