@extends('layouts.mobile')
@section('title', '返品入力モード選択')

@section('content')


<acceptingreturnsinputselect-component 
    :is-editable="{{ $isEditable }}"
></acceptingreturnsinputselect-component>


@endsection