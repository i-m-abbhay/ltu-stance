@extends('layouts.mobile')
@section('title', '移管受入')

@section('content')


<stocktransferacceptancecheck-component 
    :is-editable="{{ $isEditable }}"
></stocktransferacceptancecheck-component>


@endsection