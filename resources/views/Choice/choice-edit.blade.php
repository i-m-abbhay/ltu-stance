@extends('layouts.default')
@section('title', '選択肢編集(見積依頼項目)')

@section('content')

<choiceedit-component 
    :is-editable="{{ $isEditable }}" 
    :choicedata="{{ $choiceData }}"
    :isused="{{ $isUsed }}"
></choiceedit-component>

@endsection
