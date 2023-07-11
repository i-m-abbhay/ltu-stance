@extends('layouts.mobile')
@section('title', '承認確認')

@section('content')


<acceptingreturnsapproval-component 
    :is-editable="{{ $isEditable }}"
></acceptingreturnsapproval-component>


@endsection