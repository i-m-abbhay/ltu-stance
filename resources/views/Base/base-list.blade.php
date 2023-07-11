@extends('layouts.default')
@section('title', '拠点一覧')

@section('content')

<baselist-component 
    :is-editable="{{ $isEditable }}"
></baselist-component>

@endsection