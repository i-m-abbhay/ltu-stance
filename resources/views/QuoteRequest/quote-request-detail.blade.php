@extends('layouts.default')
@section('title', '見積依頼詳細')

@section('content')

<quoterequestdetail-component
    :main-data="{{ $mainData }}"
    :qreq-list="{{ $quoteReqList }}" 
    :qreq-data-list="{{ $qreqDataList }}"
    :templates="{{ $templates }}" 
    :choice-list="{{ $choiceList }}" 
></quoterequestdetail-component>

@endsection