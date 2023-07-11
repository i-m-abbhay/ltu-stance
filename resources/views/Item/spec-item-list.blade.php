@extends('layouts.default')
@section('title', '仕様項目一覧(見積依頼項目)')

@section('content')

<specitemlist-component 
    :is-editable="{{ $isEditable }}"
    :speclist="{{ $specList }}"
    :constlist="{{ $constList }}"
></specitemlist-component>

@endsection