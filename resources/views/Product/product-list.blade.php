@extends('layouts.default')
@section('title', '商品マスタ登録')

@section('content')

<productlist-component 
    :is-editable="{{ $isEditable }}"
    :supplierdata="{{ $supplierData }}"
    :constdata="{{ $constData }}"
    :classbigdata="{{ $classBigData }}"
    :classmiddata="{{ $classMidData }}"
    :classsmalldata="{{ $classSmallData }}"
></productlist-component>

@endsection