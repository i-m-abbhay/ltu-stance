@extends('layouts.default')
@section('title', '項目編集(見積依頼項目)')

@section('content')

<itemedit-component 
    :is-editable="{{ $isEditable }}" 
    :itemdata="{{ $itemData }}"
    :typedata="{{ $typeData }}"
    :keywordlist="{{ $keywordList }}"
    :isused="{{ $isUsed }}"
></itemedit-component>

@endsection
