@extends('layouts.default')
@section('title', '商品転換')

@section('content')

<stock-conversion-component 
    :is-editable="{{ $isEditable }}"
    :warehouselist="{{ $warehouseList }}"
></stock-conversion-component>

@endsection

