@extends('layouts.mobile')
@section('title', 'トラック登録')

@section('content')


<shippingtruck-component 
    :shelfnumberList="{{ $shelfnumberList }}" 
></shippingtruck-component>


@endsection