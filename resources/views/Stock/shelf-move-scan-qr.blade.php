@extends('layouts.mobile')
@section('title', '倉庫内移動')

@section('content')


<shelfmovescanqr-component 
    :is-editable="{{ $isEditable }}"
></shelfmovescanqr-component>


@endsection