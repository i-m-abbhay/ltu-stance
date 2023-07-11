@extends('layouts.default')
@section('title', '仕様項目編集(見積依頼項目)')

@section('content')

<specitemedit-component 
    :is-editable="{{ $isEditable }}" 
    :specitem="{{ $specItem }}"
    :specdetail="{{ $specDetail }}"
    :itemlist="{{ $itemList }}"
    :constlist="{{ $constList }}"
    :itemnamelist="{{ $itemNameList }}"
    :typelist="{{ $typeList }}"
></specitemedit-component>

@endsection
