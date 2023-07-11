@extends('layouts.mobile')
@section('title', '納品証明')

@section('content')


<deliveryphoto-component 
    :is-editable="{{ $isEditable }}"
></deliveryphoto-component>


@endsection