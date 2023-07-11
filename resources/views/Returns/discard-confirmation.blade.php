@extends('layouts.mobile')
@section('title', '廃棄')

@section('content')


<discardconfirmation-component 
    :is-editable="{{ $isEditable }}"
></discardconfirmation-component>


@endsection