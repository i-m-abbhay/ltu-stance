@extends('layouts.mobile')
@section('title', '棚卸')

@section('content')


<inventoryproductlist-component 
    :is-editable="{{ $isEditable }}"
></inventoryproductlist-component>


@endsection