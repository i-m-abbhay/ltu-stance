@extends('layouts.mobile')
@section('title', '入荷処理')

@section('content')

<arrivalprocess-component 
    :is-editable="{{ $isEditable }}"
></arrivalprocess-component>

@endsection