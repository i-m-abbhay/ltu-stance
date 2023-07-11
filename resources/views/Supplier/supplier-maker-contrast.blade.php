@extends('layouts.default')
@section('title', '仕入先／メーカー対照設定')

@section('content')

<supplier-maker-contrast-component 
    :is-editable="{{ $isEditable }}"
    :is-own-lock="{{ $isOwnLock }}"
    :lockdata="{{ $lockData }}"
    :supplierdata="{{ $supplierData }}"
    :supplierlist="{{ $supplierList }}"
    :contrastdata="{{ $contrastData }}"
    :biglist="{{ $bigList }}"
    :constlist="{{ $constList }}"
></supplier-maker-contrast-component>

@endsection