@extends('layouts.mobile')
@section('title', '在庫検索')

@section('content')


<acceptingreturnsstocksearch-component 
    :is-editable="{{ $isEditable }}"
    :warehouse-list="{{ $warehouseList }}"
></acceptingreturnsstocksearch-component>


@endsection