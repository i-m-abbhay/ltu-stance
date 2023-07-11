@extends('layouts.default')
@section('title', 'キャンペーン価格設定')

@section('content')

<productcampaignprice-component 
    :is-editable="{{ $isEditable }}"
    :is-own-lock="{{ $isOwnLock }}"
    :lockdata="{{ $lockData }}"
    :productdata="{{ $productData }}"
    :campaignlist="{{ $campaignList }}"
></productcampaignprice-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>