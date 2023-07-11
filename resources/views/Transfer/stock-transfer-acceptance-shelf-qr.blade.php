@extends('layouts.mobile')
@section('title', '移管受入')

@section('content')


<stocktransferacceptanceshelfqr-component 
    :is-editable="{{ $isEditable }}"
></stocktransferacceptanceshelfqr-component>


@endsection