@extends('layouts.default')
@section('title', '選択肢一覧(見積依頼項目)')

@section('content')

<choicelist-component 
    :is-editable="{{ $isEditable }}"
    :cnamelist="{{ $cNameList }}"
    :ckeywordlist="{{ $cKeywordList }}"
></choicelist-component>

@endsection