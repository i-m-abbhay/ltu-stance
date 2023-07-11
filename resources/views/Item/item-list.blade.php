@extends('layouts.default')
@section('title', '項目一覧(見積依頼項目)')

@section('content')

<itemlist-component 
    :is-editable="{{ $isEditable }}"
    :itemlist="{{ $itemList }}"
    :typelist="{{ $typeList }}"
></itemlist-component>

@endsection