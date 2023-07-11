@extends('layouts.mobile')
@section('title', '出荷検索')

@section('content')

<shippingsearch-component 
    :customerList="{{ $customerList }}"
    :matterList="{{ $matterList }}"
    :departmentList="{{ $departmentList }}" 
    :staffList="{{ $staffList }}"
></shippingsearch-component>

@endsection