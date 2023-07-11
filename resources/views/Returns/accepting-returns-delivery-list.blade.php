@extends('layouts.mobile')
@section('title', '納品一覧')

@section('content')


<acceptingreturnsdeliverylist-component 
    :is-editable="{{ $isEditable }}"
></acceptingreturnsdeliverylist-component>


@endsection