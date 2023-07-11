@extends('layouts.default')
@section('title', '仕入先／メーカー一覧')

@section('content')

<supplierlist-component 
    :is-editable="{{ $isEditable }}"
    :supplierlist="{{ $supplierList }}"
    :makerlist="{{ $makerList }}"
    :biglist="{{ $bigList }}"
    :constlist="{{ $constList }}"
></supplierlist-component>

@endsection