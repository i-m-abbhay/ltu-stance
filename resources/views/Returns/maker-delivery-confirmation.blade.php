@extends('layouts.mobile')
@section('title', 'ﾒｰｶｰ引渡')

@section('content')


<makerdeliveryconfirmation-component 
    :is-editable="{{ $isEditable }}"
></makerdeliveryconfirmation-component>


@endsection