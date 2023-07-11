@extends('layouts.mobile')
@section('title', '納品登録')

@section('content')


<deliveryprocess-component 
    :is-editable="{{ $isEditable }}"
></deliveryprocess-component>


@endsection