@extends('layouts.mobile')
@section('title', '倉庫内移動')

@section('content')


<shelfmove-component 
    :is-editable="{{ $isEditable }}"
    :return-shelf-list="{{ $returnShelfList }}"
></shelfmove-component>


@endsection