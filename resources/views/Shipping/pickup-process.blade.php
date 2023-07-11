@extends('layouts.mobile')
@section('title', '引取手続')

@section('content')


<pickupprocess-component 
    :is-editable="{{ $isEditable }}"
    :temporary-shelf-id="{{ $temporaryShelfId }}"
></pickupprocess-component>


@endsection