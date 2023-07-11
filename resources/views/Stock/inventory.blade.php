@extends('layouts.mobile')
@section('title', '棚卸')

@section('content')


<inventory-component 
    :is-editable="{{ $isEditable }}"
></inventory-component>


@endsection