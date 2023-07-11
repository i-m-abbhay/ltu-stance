@extends('layouts.mobile')
@section('title', 'ﾒｰｶｰ引渡')

@section('content')


<makerdelivery-component 
    :makerList="{{ $makerList }}" 
></makerdelivery-component>


@endsection