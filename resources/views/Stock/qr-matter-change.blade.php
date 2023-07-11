@extends('layouts.mobile')
@section('title', 'QR案件変更')

@section('content')


<qrmatterchange-component 
    :is-editable="{{ $isEditable }}"
    :matter-list="{{ $matterList }}"
></qrmatterchange-component>


@endsection