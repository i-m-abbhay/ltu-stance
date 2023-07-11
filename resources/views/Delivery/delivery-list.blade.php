@extends('layouts.mobile')
@section('title', '納品先選択')

@section('content')


<deliverylist-component 
    :shelfnumberList="{{ $shelfnumberList }}" 
></deliverylist-component>


@endsection