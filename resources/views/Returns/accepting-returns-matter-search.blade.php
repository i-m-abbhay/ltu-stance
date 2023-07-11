@extends('layouts.mobile')
@section('title', '案件検索')

@section('content')


<acceptingreturnsmattersearch-component 
    :is-editable="{{ $isEditable }}"
    :matter-list="{{ $matterList }}"
    :construction-list="{{ $constructionList }}"
></acceptingreturnsmattersearch-component>


@endsection