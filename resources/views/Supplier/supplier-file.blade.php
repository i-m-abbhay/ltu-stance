@extends('layouts.default')
@section('title', '仕入先／メーカー価格ファイル編集')

@section('content')

<supplierfile-component 
    :is-editable="{{ $isEditable }}" 
    :is-own-lock="{{ $isOwnLock }}" 
    :lockdata="{{ $lockData }}"
    :supplierdata="{{ $supplierData }}"
    :filelist="{{ $fileList }}"
></supplierfile-component>

@endsection

<script>
window.onbeforeunload = function(e) {
    return MSG_CONFIRM_LEAVE;
};
</script>
