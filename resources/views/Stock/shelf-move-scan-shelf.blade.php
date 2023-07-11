@extends('layouts.mobile')
@section('title', '倉庫内移動')

@section('content')


<shelfmovescanshelf-component 
    :is-editable="{{ $isEditable }}"
></shelfmovescanshelf-component>


@endsection