@extends('layouts.mobile')
@section('title', 'メニュー')

@section('content')

<mobilemenu-component 
:warehouseId="{{ $warehouseId }}"
:warehouseList="{{ $warehouseList }}"
></mobilemenu-component>

@endsection

