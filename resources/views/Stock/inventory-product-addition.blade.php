@extends('layouts.mobile')
@section('title', '棚卸')

@section('content')


<inventoryproductaddition-component 
    :is-editable="{{ $isEditable }}"
    :matter-list="{{ $matterList }}"
    :customer-list="{{ $customerList }}"
    :maker-list="{{ $makerList }}"
></inventoryproductaddition-component>


@endsection