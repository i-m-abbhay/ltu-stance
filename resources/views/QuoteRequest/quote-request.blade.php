@extends('layouts.default')
@section('title', '見積依頼入力')

@section('content')

<quoterequest-component
    :main-data="{{ $mainData }}"
    :customer-list="{{ $customerList }}" 
    :owner-list="{{ $ownerList }}"
    :architecture-list="{{ $architectureList }}" 
    :spec-list="{{ $specList }}" 
    :qreq-list="{{ $quoteReqList }}" 
    :matter-list="{{ $matterList }}" 
    :templates="{{ $templates }}" 
    :choice-list="{{ $choiceList }}" 
></quoterequest-component>

@endsection