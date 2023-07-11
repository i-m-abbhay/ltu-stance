@extends('layouts.default')
@section('title', '見積登録')

@section('content')

<quoteedit-component 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}" 
    :maindata="{{ $mainData }}" 
    :quote-layer-list="{{ $quoteLayerList }}" 
    :quote-detail-list="{{ $quoteDetailList }}" 
    :customer-list="{{ $customerList }}"
    :owner-list="{{ $ownerList }}"
    :customer-owner-list="{{ $customerOwnerList }}"
    :architecture-list="{{ $architectureList }}"
    :person-list="{{ $personList }}"
    :requested-list="{{ $requestedList }}"
    :qreq-list="{{ $qreqList }}"
    :paycon-list="{{ $payConList }}"
    :price-list="{{ $priceList }}"
    :alloc-list="{{ $allocList }}"
    :maker-list="{{ $makerList }}"
    :supplier-list="{{ $supplierList }}"
    :supplier-maker-list="{{ $supplierMakerList }}"
    :supplier-file-list="{{ $supplierFileList }}"
    :wood-list="{{ $woodList }}"
    :grade-list="{{ $gradeList }}"
    :matter-list="{{ $matterList }}"
    :quote-list="{{ $quoteList }}"
    :init-tree="{{ $initTree }}"
    :init-construction-branch = "{{ $initConstructionBranch }}"
    :tax-rate-lock-flg = "{{ $taxRateLockFlg }}"
    :add-layer-id = "{{ $addLayerId }}"
    :quote-version-default = "{{ $quoteVersionDefault }}"
></quoteedit-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>